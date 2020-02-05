<?php
/**
 * Created by PhpStorm.
 * User: Ydr
 * Date: 2019/12/18
 * Time: 10:54
 */

namespace Home\Controller;


use Think\Controller;

class MarketShopController extends Controller
{
    public function __construct()
    {
        header("Content-Type: text/html;charset=utf-8");
        parent::__construct();
    }

    private $store_parenttype_id = 1;

    private $store_childtype_id = 2;



    /**
     * 创建套餐订单
     * params  int $cloud_market_type 市场类型
     * params  tinyint $is_try 是否试用 0非试用 1-试用
     * params  int $cloud_product_id 套餐编号
     * params  int $age_limit 使用年限
     * params  string $qcloud_openId 腾讯云openId
     * params  string $remark 备注
     */
    public function index(){
        $market_type = I('cloud_market_type');
        $is_try = I('is_try');
        $cloud_product_id = I('cloud_product_id');
        $age_limit = I('age_limit');
        $openId = I('qcloud_openId');
        $remark  = I('remark');
        $center = M('operate_center');
        $marketConfig = $this->getMarketConfig($market_type);
        if(empty($marketConfig)){
            $this->output_error('未找到相应的配置');
        }
        $operate_sn = $marketConfig['operate_sn'];
        $operate_info = $center->where(array($operate_sn))->find();
        $cloud_product_info = M('cloud_product_list')->where(array('cloud_product_id'=>$cloud_product_id,'status'=>1))->find();
        $package_id = $cloud_product_info['packageid'];
        $vip = M('package_list')->where(array('packageid'=>$package_id,'status'=>1))->getField('up_level');
        $try_day = $cloud_product_info['try_day'];
        $xunxin_num = $this->getXunXinNum($vip);
        if(empty($xunXin_num)){
            $this->output_error('迅信帐号为空');
        }
        $passWard = randorderno(6);
        $params = array();
        $params['shopName'] = '测试001';
        $params['account_membertel'] = '17746071624';
        $params['qcloud_openId'] = $openId;
        $params['store_parenttype_id'] = $this->store_parenttype_id;
        $params['store_childtype_id'] = $this->store_childtype_id;
        $params['vip'] = $vip;
        $params['xunxin_num'] = $xunxin_num;
        $params['password'] = $passWard;
        $params['is_try'] = $is_try;
        if($is_try == '1'){
            $params['try_time'] = (int)$try_day * 84000;
        }
        $params['age_limit'] = $age_limit;
        $params['account_membername'] = '总后台';

        if(!empty($remark)){
            $params['remark'] = $remark;
        }
        $params['operate_num'] = $operate_sn;
        $params['operate_id'] = $operate_info['id'];
        $sync_url = M('system_config')->where(array('status'=>1))->getField('sync_url');
        $url = "http://".$sync_url."/xxapi/index.php?act=operate_openaccount&op=applyStore";

        $json =  $this->postCurl($url,$params);
        $rt = json_decode($json,true);
        if($rt['result'] == -1){
            $this->output_error($rt['error']);
        }else {
            $account_id = $rt['datas'];
            $order_id = $this->create_package_order($account_id, $xunxin_num, $is_try, $package_id, $age_limit, $operate_sn, $params['account_membertel'], 4, 0);
            if (empty($order_id)) {
                $this->output_error('创建订单失败');
            } else {
                $pay_check = A('Shop')->pay_package_order($order_id);
                if ($pay_check['status'] == -1) {
                    $this->output_error($pay_check['desc']);
                } else {
                    $params2 = array();
                    $params2['account_id'] = $account_id;
                    $params2['opentype'] = 2;
                    $params2['platform_type'] = 2;
                    $url2 = "http://" . $sync_url . "/xxapi/index.php?act=operate_openaccount&op=openStore";
                    $json2 = $this->postCurl($url2, $params2);
                    $rt2 = json_decode($json2, true);
                    if ($rt2['result'] == '0' || $rt2['result'] == '-10' || $rt2['result'] == '1001') {
//                        $order_sn = M('vip_orders')->where(array('id' => $order_id))->getField('orderSn');
//                        M('data_record')->where(array('order_sn' => $order_sn))->save(array('open_result' => 'SUCCESS'));
                        $newdata = array();
                        $newdata['rechargetime'] = mktime();
                        $newdata['is_create'] = 1;
                        $newdata['store_id'] = $rt2['datas'];
                        M('vip_orders')->where(array('id' => $order_id))->save($newdata);
                        A('Shop')->settlement_package_order($order_id);
                        A('Shop')->checkOperateUplevel($operate_info['id']);
                        $auth_code = M('system_config')->where(array('status' => 1))->getField('auth_code');
                        $sync_url = ADMIN_URL . '/index.php?m=api&c=Store&a=index&auth_code=' . $auth_code;
                        $x = 1;
                        do {
                            $json = file_get_contents($sync_url);
                            $result = json_decode($json, true);
                        } while (++$x <= 3 && $result['result'] != '0' && $result['result'] != '1');
//                        if ($rt2['result'] == '0') {
//                            $this->success('新增店铺成功', U('Shop/shop_list'));
//                        } else {
//                            $this->success('新增店铺成功,发送短信失败', U('Shop/shop_list'));
//                        }
                    } else {
                        $this->output_error($rt2['error']);
                    }
                }
            }
        }
    }

    function output_error($error = '')
    {
        $data = array();
        $data['result'] = -1;
        $data['code'] = 500;
        $data['error_msg'] = $error;
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die;
    }

    function output_data($datas, $msg = '')
    {
        $data = array();
        $data['result'] = 0;
        $data['code'] = 200;
        $data['msg'] = $msg;
        $data['info'] = $datas;
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die;
    }

    public function test(){
        $code = randorderno(6);
        var_dump($code);;
    }

    /**
     * 创建套餐订单
     * params  int $account_id 申请编号
     * params  string $member_name 用户账号
     * params  tinyint $is_try 是否试用 0非试用 1-试用
     * params  int $package_id 套餐编号
     * params  int $age_limit 使用年限
     * params  string $recommend_code 业务编号
     * params  string $tel 联系方式
     * params  tinyint $paytype 支付方式 1-线下支付 2-微信支付 3-余额支付 4-云支付
     * params  tinyint $type 下单方式 0会员下单 1-管理员添加订单 2-腾讯云下单
     * params  tinyint $store_id  店铺编号
     * return  int $order_id  订单编号
     */



    public function create_package_order($account_id,$member_name,$is_try,$package_id,$age_limit,$recommend_code,$tel,$paytype,$type,$store_id = ''){

        $shareholder_info = M('operate_shareholder')->where(array('shareholder_sn'=>$recommend_code))->find();
        if(!empty($shareholder_info)){
            $operate_id = $shareholder_info['operate_id'];
            $holder_id = $shareholder_info['id'];
        }else{
            $operate_id = M('operate_center')->where(array('operate_sn'=>$recommend_code))->getField('id');
        }
        $datas = array();
        $datas['member_name'] = $member_name;
        $datas['orderSn'] = 'v'.mktime().$account_id;
        $datas['packageid'] = $package_id;
        $datas['account_id'] = $account_id;
        if($is_try == 1){
            $cost_price = 0;
        }else{
            $cost_price = A('Admin')->getPackageCostPrice($age_limit,$package_id,$operate_id,$store_id);
        }
        $datas['cost_price'] = $cost_price;
        $datas['age_limit'] = $age_limit;
        /*获取用户套餐购买价格*/
        $w = array();
        $w['packageid'] = $package_id;
        $w['is_show'] = 1;
        $w['status'] = 1;
        $marketinfo = M('package_list')->where($w)->field('market_price,market_price2,market_price3')->find();
        if($is_try == 1){
            $market_price = 0;
        }else{
            if($age_limit == 1){
                $market_price = $marketinfo['market_price'];
            }else if($age_limit == 2){
                $market_price = $marketinfo['market_price2'];
            }else{
                $market_price = $marketinfo['market_price3'];
            }
        }
        $datas['sale_price'] = $market_price;
        /*管理员添加时，实际售价等于成本价*/
        if($type == 1){
            if($is_try == 1){
                $actual_price = 0;
            }else{
                $actual_price = $cost_price;
            }
        }else{
            if($is_try == 1){
                $actual_price = 0;
            }else{
                if(!empty($store_id)){
                    $actual_price = $market_price;
                }else{
                    $edit = M('shareholder_package_edit');
                    $w3 = array();
                    $w3['operate_id'] = $operate_id;
                    $w3['package_id'] = $package_id;
                    $w3['status'] = 1;
                    $priceinfo = $edit->where($w3)->field('package_price,package_price2,package_price3')->find();
                    if($age_limit == 1){
                        $package_price = $priceinfo['package_price'];
                    }else if($age_limit == 2){
                        $package_price = $priceinfo['package_price2'];
                    }else if($age_limit == 3){
                        $package_price = $priceinfo['package_price3'];
                    }
                    $actual_price = empty($package_price) ? $market_price : $package_price;
                }
            }
        }
        /*获取用户套餐购买价格结束*/
        $datas['actual_price'] = $actual_price;
        if(!empty($shareholder_info)){
            $recommend_profit = ($actual_price-$cost_price)* $shareholder_info['recommend_rate']/100;
        }else{
            $recommend_profit = 0;
        }
        $datas['recommend_profit'] = $recommend_profit;
        $datas['operate_profit'] = $actual_price-$cost_price-$recommend_profit;
        $datas['tel'] = $tel;
        $datas['recommend_code'] = $recommend_code;
        $datas['holder_id'] = $holder_id;
        $datas['operate_id'] = $operate_id;
        $up_level = M('package_list')->where(array('packageid'=>$package_id))->getField('up_level');
        $datas['up_level'] = $up_level;
        $datas['paytype'] = $paytype;
        $datas['type'] =1;
        $datas['status'] = 0;
        $datas['applytime'] = mktime();
        $datas['is_create'] = 0;
        $datas['issettlement'] = 0;
        $order_id = M('vip_orders')->add($datas);
        $add_data = array();
        $add_data['ad_id'] = $recommend_code;
        $add_data['event'] = 'click';
        $add_data['member_name'] = $member_name;
        $add_data['member_tel'] = $tel;
        $add_data['package_id'] = $package_id;
        $add_data['order_sn'] = $datas['orderSn'];
        DataRecoed($add_data);
        return $order_id;
    }

    public function pay_package_order($order_id = ''){
        $order = M('vip_orders');
        $order_info = $order->where(array('id'=>$order_id))->find();
        $rt = array();
        if(empty($order_info)){
            $rt['status'] = -1;
            $rt['desc'] = '该订单不存在';
        }else if($rt['status'] == 1){
            $rt['status'] = -1;
            $rt['desc'] = '该订单已经支付';
        }else{
            $w = array();
            $w['id'] = $order_info['operate_id'];
            $w['status'] = 1;
            $money = M('operate_center')->where($w)->getField('money');
            if($money < $order_info['cost_price']){
                $rt['status'] = -1;
                $rt['desc'] = '运营商预存金额不足';
            }else{
                $da = array();
                $da['value'] = 0 -$order_info['cost_price'];
                $da['final_value'] = $money - $order_info['cost_price'];
                $da['operate_id'] = $order_info['operate_id'];
                $da['type'] = 2;
                $da['order_sn'] = $order_info['ordersn'];
                $da['periods'] = date('Y-m-d');
                $da['addtime'] = mktime();
                $da['editor'] =  session('loginname');
                $da['status'] = 1;
                if(M('operate_trade_record')->add($da)){
                    $check = M('operate_center')->where($w)->setDec('money',$order_info['cost_price']);
                    if($check !== false){
                        $new = array();
                        $new['status'] = 1;
                        $order->where(array('id'=>$order_id))->save($new);
                        $rt['status'] = 1;
                    }else{
                        $rt['status'] = -1;
                        $rt['desc'] = '更改运营商预存金额失败';
                    }
                }else{
                    $rt['status'] = -1;
                    $rt['desc'] = '插入运营商交易记录失败';
                }
            }
        }
        return $rt;

    }

    /**
     * 检验运营商能否升级
     */
    public function checkOperateUplevel($operate_id=''){
        $config = M('operate_config')->where(array('status'=>1))->find();
        $operate = M('operate_center');
        $order = M('vip_orders');
        $w = array();
        $w['id'] = $operate_id;
        $w['status'] = 1;
        $operate_info = $operate->where($w)->find();
        if(!empty($operate_info)){
            if($operate_info['level'] < 6){
                $w2 = array();
                $w2['operate_id'] = $operate_id;
                $w2['status'] = 1;
                $w2['is_create'] = 1;
                $total_price = $order->where($w2)->sum('actual_price');
                $total_price = empty($total_price) ? 0 : $total_price;
                $up_level = 0;
                if(($total_price >= $config['first_upprice']) && ($total_price < $config['secend_upprice'])){
                    $up_level = 2;
                }else if(($total_price >= $config['secend_upprice']) && ($total_price < $config['third_upprice'])){
                    $up_level = 3;
                }else if(($total_price >= $config['third_upprice']) && ($total_price < $config['fourth_upprice'])){
                    $up_level = 4;
                }else if(($total_price >= $config['fourth_upprice']) && ($total_price < $config['fifth_discount'])){
                    $up_level = 5;
                }else if($total_price >= $config['fifth_discount']){
                    $up_level = 6;
                }
                if($up_level > $operate_info['level']){
                    $operate->where($w)->save(array('level'=>$up_level));
                    M('shareholder_package_edit')->where(array('operate_id'=>$operate_id))->save(array('status'=>0));
                }

            }
        }
    }

    /*获取账号列表*/
    public function getXunXinNum($vip = 0){
        $num = NULL;
        $param = array();
        $param['vip'] = $vip;
        $param['channel_id'] = 0;
        $sync_url = M('system_config')->where(array('status'=>1))->getField('sync_url');
        $url = "http://".$sync_url."/xxapi/index.php?act=operate_openaccount&op=selectXunxinnum";
        $member_name_list = $this->postCurl($url,$param);
        $member_name_list = json_decode($member_name_list,true);
        $rand = rand(0,count($member_name_list)-1);
        $num = $member_name_list[$rand]['xunxin_num_name'];
        return $num;
    }

    public function getMarketConfig($marketType = 0,$condition = []){
        $config = NULL;
        $m = M('cloud_market_config');
        $where = [];
        $where['is_delete'] = 0;
        $where['status'] = 1;
        $where['cloud_market_type'] = $marketType;
        if(!empty($condition)){
            $where['_complex'] = $condition;
        }
        $config = $m->where($where)->find();
        return $config;
    }


    public function postCurl($url='',$data = array()) {

        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data);
        $datas = curl_exec ($ch);

        $curl_errno = curl_errno($ch);
        if($curl_errno=='0'){
            curl_close($ch);
            return $datas;
        }else{
            curl_close($ch);
            $resultdata = array();
            $resultdata['result'] = -1;
            $resultdata['desc'] = "curl出错，错误码:".$curl_errno;
            return json_encode($resultdata, JSON_UNESCAPED_UNICODE);
        }
    }

}