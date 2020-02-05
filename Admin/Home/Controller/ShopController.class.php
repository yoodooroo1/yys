<?php
namespace Home\Controller;
use Think\Controller;
/**
 * XUNXIN PC 后台管理 礼品管理
 * ============================================================================
 * 版权所有 2005-2010 厦门微聚点科技有限公司，并保留所有权利。
 * 网站地址: http://www.vjudian.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: youyan $
 * $Id: MemberController.class.php
 */
class ShopController extends AdminController
{
    /**
     * 店铺列表
     */
    public function shop_list()
    {
      	$package = M('package_list');
		$center = M('operate_center');
		$w =array();
		$w['status'] = 1;
		$package_list = $package->where($w)->field('packageid,name')->select();
		$this->assign('package_list',$package_list);
		$store = M('stores');
		$where = array();    
		$where['lm_stores.isdelete'] = 0;
        $where['lm_stores.isshow'] = 0;
		$member_name = I('member_name');
		if(!empty($member_name)){
			$where['lm_stores.member_name'] = $member_name;
		}
		$store_name= I('store_name');
		if(!empty($store_name)){
			$where['lm_stores.store_name'] = array('like',"%$store_name%");
		}
		$lianxi_member_name= I('lianxi_member_name');
		if(!empty($lianxi_member_name)){
			$where['lm_stores.lianxi_member_name'] = array('like',"%$lianxi_member_name%");
		}
		$lianxi_member_tel= I('lianxi_member_tel');
		if(!empty($lianxi_member_tel)){
			$where['lm_stores.lianxi_member_tel'] = array('like',"%$lianxi_member_tel%");
		}
		$choose_level= I('choose_level');
		if(!empty($choose_level)){
			if($choose_level == '-1'){
				$where['lm_stores.is_try'] = 1;
			}else{
				$where['lm_stores.is_try'] = 0;
				$where['package_id'] = $choose_level;
			} 
		}
		$store_parenttype_id= I('store_parenttype_id');
		if(!empty($store_parenttype_id)){
			$where['lm_stores.store_parenttype_id'] = $store_parenttype_id;
		}
		$store_childtype_id= I('store_childtype_id');
		if(!empty($store_childtype_id)){
			$where['lm_stores.store_childtype_id'] = $store_childtype_id;
		}
		$endtime = I('endtime');
		if(!empty($endtime)){
			if($endtime == '1'){
				$where['lm_stores.vip_endtime'] = array('lt',mktime());
			}else if($endtime == '2'){
				$where['lm_stores.vip_endtime'] = array('lt',mktime()+7*24*3600);
			}else if($endtime == '3'){
				$where['lm_stores.vip_endtime'] = array('lt',mktime()+30*24*3600);
			}
		}
		$opentype = I('opentype');
		if(!empty($opentype)){
			$where['lm_stores.opentype'] = $opentype;
		}
		$operate_id = I('operate_id');
		if(!empty($operate_id)){
			$where['lm_stores.operate_id'] = $operate_id;
		}
		$operate_name = I('operate_name');
		if(!empty($operate_name)){
			$wo = array();
			$wo['operate_name'] = array('like',"%$operate_name%");
			$wo['status'] = 1;
			$op_array = $center->where($wo)->field('id')->select();
			$op_arr = array();
			foreach($op_array as $arr){
				$op_arr[] = $arr['id'];
			}
			$op_str = implode(',',$op_arr);
			if(empty($op_str)){
				$where['lm_stores.operate_id'] = -1;  //都没有
			}else{
				$where['lm_stores.operate_id'] = array('in',$op_str);
			}
			  
		}
		$operation_number = I('operation_number');
		if(!empty($operation_number)){
			$where['lm_stores.operation_number'] = $operation_number;
		} 
		$Time1 = I('Time1'); 
		$Time2 = I('Time2');  
		if(!empty($Time1) && empty($Time2))  
		{
			$t = strtotime($Time1)+24*60*60 ;
			$where['_string']= "(lm_stores.account_time >= '".strtotime($Time1) ."'&& lm_stores.account_time < '".$t."') OR (lm_stores.recharge_time >= '".strtotime($Time1) ."'&& lm_stores.recharge_time < '".$t."')";
		}         
		if(!empty($Time2) && empty($Time1))  
		{  
			$where['_string']= "(lm_stores.account_time<= '". strtotime($Time2)."') OR (lm_stores.recharge_time<= '". strtotime($Time2)."')";  
		}  
		if(!empty($Time2) && !empty($Time1))
		{   
			$t = strtotime($Time2)+24*60*60 ;
			$where['_string']= "(lm_stores.account_time >= '".strtotime($Time1) ."'&& lm_stores.account_time < '" .$t."') OR (lm_stores.recharge_time >= '".strtotime($Time1) ."'&& lm_stores.recharge_time < '" .$t."')";  
		}
		  
		$count = $store->where($where)->count(); // 查询满足要求的总记录
        $Page = new \Think\Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show(); // 分页显示输出  
		$lists = $store->join('LEFT JOIN lm_package_list ON lm_package_list.packageid = lm_stores.package_id')->where($where)->order('lm_stores.store_id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->field('lm_stores.store_id,lm_stores.store_name,lm_stores.member_name,lm_stores.lianxi_member_name,lm_stores.lianxi_member_tel,lm_stores.store_parenttype_id,lm_stores.store_childtype_id,lm_stores.package_id,lm_stores.operation_number,lm_stores.account_time,lm_stores.recharge_time,lm_stores.vip_endtime,lm_stores.operate_id,lm_package_list.name package_name,lm_stores.opentype')->select(); 
		$commontype = M('commontype');
		
		foreach($lists as $k=>$list){
			$lists[$k]['store_parenttype_name'] = $commontype->where(array('id'=>$list['store_parenttype_id'],'isdelete'=>0))->getField('store_type_name');
			$lists[$k]['store_childtype_name'] = $commontype->where(array('id'=>$list['store_childtype_id'],'isdelete'=>0))->getField('store_type_name');
			$lists[$k]['operate_name'] = $center->where(array('id'=>$list['operate_id']))->getField('operate_name');
			
		}
	
		$commontype_parent = $commontype->where(array('isdelete'=>0,'store_parenttype_id'=>0))->field('id,store_type_name')->select();
	    $commontype_child = array();
		foreach($commontype_parent as $k2=>$parent){
			$childinfo = $commontype->where(array('isdelete'=>0,'store_parenttype_id'=>$parent['id']))->field('id,store_type_name')->select();
			foreach($childinfo as $k3=>$child){
				$commontype_child[$k2][$k3][0] = $child['id'];
				$commontype_child[$k2][$k3][1] = $child['store_type_name'];
			}
		} 

		$this->assign('commontype_parent',$commontype_parent);
		$this->assign('childs',json_encode($commontype_child));
		$this->assign('lists',$lists); 
		$this->assign('count',$count);		
        $ui['shop_list'] = 'active';  
        $this->assign('ui',$ui);
		$this->assign('page',$show);
        $this->display("shop_list"); // 输出模板
    }  
	/**
     * 新增/查看店铺
     */
    public function shop_info()
    {
		DataRecoed(); 
		$shopid = I('shop_id');
		$commontype = M('commontype');
		if(!empty($shopid)){
			$act = 'info';
			$shopinfo = M('stores')->where(array('store_id'=>$shopid))->find();
			if(empty($shopinfo)){
				$this->error('该店铺不存在');
			}  
			$shopinfo['try_time'] = round($shopinfo['try_time']/3600,0);
			$shopinfo['parenttype_name'] = $commontype->where(array('id'=>$shopinfo['store_parenttype_id'],'isdelete'=>0))->getField('store_type_name');
			$shopinfo['childtype_name'] = $commontype->where(array('id'=>$shopinfo['store_childtype_id'],'isdelete'=>0))->getField('store_type_name');
			$this->assign('shopinfo',$shopinfo); 
			
			$where = array();
			$where['packageid'] = $shopinfo['package_id'];
			$where['status'] = 1;
			$packageinfo = M('package_list')->where($where)->find();
			if($shopinfo['is_try'] == 1){
				$news = M('shareholder_package_edit')->where(array('operate_id'=>$shopinfo['operate_id'],'package_id'=>$shopinfo['package_id'],'status'=>1))->find();
				if(!empty($news)){
					$packageinfo['market_price'] = $news['package_price'];
					$packageinfo['market_price2'] = $news['package_price2'];
					$packageinfo['market_price3'] = $news['package_price3'];
				}             
			}
			$this->assign('packageinfo',$packageinfo);	
				
		}else{   
			$act = 'insert'; 
		}  
		 
		/*店铺行业列表*/
		
		$commontype_parent = $commontype->where(array('isdelete'=>0,'store_parenttype_id'=>0))->field('id,store_type_name')->select();
	    $commontype_child = array();
		foreach($commontype_parent as $k2=>$parent){
			$childinfo = $commontype->where(array('isdelete'=>0,'store_parenttype_id'=>$parent['id']))->field('id,store_type_name')->select();
			foreach($childinfo as $k3=>$child){
				$commontype_child[$k2][$k3][0] = $child['id'];
				$commontype_child[$k2][$k3][1] = $child['store_type_name'];
			}
		}    
		$this->assign('commontype_parent',$commontype_parent);
		$this->assign('childs',json_encode($commontype_child));
		
		/*套餐列表*/
		$package = M('package_list');
		$w = array();
		$w['is_show'] = 1;
		$w['status'] = 1;
		$package_list = $package->where($w)->order('sort DESC')->field('packageid,name,up_level')->select();
		$this->assign('package_list',$package_list); 
	
	    /*获取账号列表*/
		$param = array();
		$param['vip'] = $package_list[0]['up_level'];
		$param['channel_id'] = 0;
		$sync_url = M('system_config')->where(array('status'=>1))->getField('sync_url');    
		$url = "http://".$sync_url."/xxapi/index.php?act=operate_openaccount&op=selectXunxinnum";  
		$member_name_list = $this->postCurl($url,$param);
		$this->assign('member_name_list',json_decode($member_name_list,true));
	
		/*运营商列表*/
		$operate = M('operate_center');
		$w2 = array();
		$w2['status'] = 1;
		if($act == 'info'){
			$w2['id'] = $shopinfo['operate_id'];
		}	   
		$operate_list = $operate->where($w2)->field('try_time,operate_name,operate_sn')->select();
		
		$this->assign('operate_list',$operate_list);
		$this->assign('act',$act); 
	   	$ui['shop_info'] = 'active';
        $this->assign('ui',$ui);
        $this->display('shop_info');  
    } 
	   
	public function shop_edit(){
		$files = $_FILES;
		if(!($files['fileImg1']['error'] == 4 && $files['fileImg2']['error'] == 4 && $files['fileImg3']['error'] == 4 && $files['fileImg4']['error'] == 4)){
			$imginfo = $this->upload($files);
		}else{
			$imginfo = array();
		}
		$datas = I();	
		$act = $datas['act'];
		if(!empty($imginfo['fileImg1'])){
			$url1 = ADMIN_URL .'/images/'.$imginfo['fileImg1']['savepath'].$imginfo['fileImg1']['savename'];
		}/* else{
			if($act == 'insert'){
				$this->error('营业执照必须上传！');
			}
		} */
		if(!empty($imginfo['fileImg2'])){
			$url2 = ADMIN_URL .'/images/'.$imginfo['fileImg2']['savepath'].$imginfo['fileImg2']['savename'];
		}/* else{
			if($act == 'insert'){
				$this->error('法人身份证正面必须上传！');
			}
		} */
		if(!empty($imginfo['fileImg3'])){
			$url3 = ADMIN_URL .'/images/'.$imginfo['fileImg3']['savepath'].$imginfo['fileImg3']['savename'];
		}/* else{
			if($act == 'insert'){
				$this->error('法人身份证反面必须上传！');
			}
		}  */ 
		if(!empty($imginfo['fileImg4'])){
			$url4 = ADMIN_URL .'/images/'.$imginfo['fileImg4']['savepath'].$imginfo['fileImg4']['savename'];
		}
		
		$sync_url = M('system_config')->where(array('status'=>1))->getField('sync_url');  
		if($act == 'insert'){ 
			$operate_num = $datas['operate_num'];
			$operate_info = M('operate_center')->where(array('operate_sn'=>$operate_num))->find();
			$package_id = $datas['package_id'];
			$age_limit = I('age_limit',1);
			$is_try = $datas['is_try'];
			if($is_try == '0'){
				$cost_price = A('Admin')->getPackageCostPrice($age_limit,$package_id,$operate_info['id']);
				if($cost_price > $operate_info['money']){
					$this->error('该运营商预存资金为￥'.$operate_info['money'].',购买套餐成本为￥'.$cost_price.',预存资金不足');
					exit;
				}
			}
			$vip = M('package_list')->where(array('packageid'=>$package_id,'status'=>1))->getField('up_level');
			$params = array(); 
			$params['shopName'] = $datas['shopName'];
			$params['store_parenttype_id'] = $datas['store_parenttype_id'];
			$params['store_childtype_id'] = $datas['store_childtype_id'];
			$params['package_id'] = $datas['package_id'];
			$params['vip'] = $vip;
			$params['xunxin_num'] =$datas['xunxin_num'];
			$params['password'] = $datas['password'];
			$params['store_provincename'] = $datas['store_provincename'];
			$params['store_cityname'] = $datas['store_cityname'];
			$params['store_areaname'] = $datas['store_areaname'];
			$params['account_storeaddress'] = $datas['account_storeaddress'];
			$params['account_zhucehao'] = $datas['account_zhucehao'];
			$params['is_try'] = $datas['is_try'];
			if($datas['is_try'] == 1){
				$params['try_time'] = $datas['try_time']*3600;
			}
			$params['age_limit'] = $datas['age_limit'];
			$params['account_membername'] = $datas['account_membername'];
			$params['id_card'] = $datas['id_card'];
			$params['account_membertel'] = $datas['account_membertel'];
			$params['storelicense'] = $url1;
			$params['id_card_img_n'] = $url2;
			$params['id_card_img_r'] = $url3;
			if(!empty($url4)){
				$params['other_img'] = $url4;
			}
			if(!empty($datas['remark'])){
				$params['remark'] = $datas['remark'];
			}
			$params['operate_num'] = $datas['operate_num'];
			$params['operate_id'] = $operate_info['id']; 
			if(!empty($datas['bank_provincenae'])){
				$params['bank_provincenae'] = $datas['bank_provincenae'];
			}
			if(!empty($datas['bank_cityname'])){
				$params['bank_cityname'] = $datas['bank_cityname'];
			}
			if(!empty($datas['bank_areaname'])){
				$params['bank_areaname'] = $datas['bank_areaname'];
			} 
			if(!empty($datas['bank_name'])){
				$params['bank_name'] = $datas['bank_name'];
			}
			if(!empty($datas['bank_num'])){
				$params['bank_num'] = $datas['bank_num'];
			}
			  
			$url = "http://".$sync_url."/xxapi/index.php?act=operate_openaccount&op=applyStore";
			$json =  $this->postCurl($url,$params);      			
			$rt = json_decode($json,true);
			if($rt['result'] == -1){
				$this->error($rt['error']);
			}else{
				$account_id = $rt['datas'];
				$order_id =$this->create_package_order($account_id,$datas['xunxin_num'],$datas['is_try'],$package_id,$age_limit,$operate_num,$datas['account_membertel'],1,1);
				if(empty($order_id)){
					$this->error('创建订单失败');
				}else{
					$pay_check = $this->pay_package_order($order_id);
					if($pay_check['status'] == -1){
						$this->error($pay_check['desc']);
					}else{  
						$params2 = array();
						$params2['account_id'] = $account_id;
						$params2['opentype'] = 2;
						$params2['platform_type'] = 2;     
						$url2 = "http://".$sync_url."/xxapi/index.php?act=operate_openaccount&op=openStore";
						$json2 =  $this->postCurl($url2,$params2);      			
						$rt2 = json_decode($json2,true);
						if($rt2['result'] == '0' || $rt2['result'] == '-10' || $rt2['result'] == '1001'){	
							$order_sn = M('vip_orders')->where(array('id'=>$order_id))->getField('orderSn');
							M('data_record')->where(array('order_sn'=>$order_sn))->save(array('open_result'=>'SUCCESS'));
							$newdata = array();
							$newdata['rechargetime'] = mktime();
							$newdata['is_create'] = 1;  
							$newdata['store_id'] = $rt2['datas'];
							M('vip_orders')->where(array('id'=>$order_id))->save($newdata);
							$this->settlement_package_order($order_id);
							$this->checkOperateUplevel($operate_info['id']); 
							$auth_code = M('system_config')->where(array('status'=>1))->getField('auth_code');   
							$sync_url = ADMIN_URL .'/index.php?m=api&c=Store&a=index&auth_code='.$auth_code;
							$x = 1;   
							do{     
								$json = file_get_contents($sync_url);
								$result = json_decode($json,true);	
							}while (++$x<=3 && $result['result'] != '0' && $result['result'] != '1' );  
							if($rt2['result'] == '0'){ 
								$this->success('新增店铺成功',U('Shop/shop_list'));	 	
							}else{
								$this->success('新增店铺成功,发送短信失败',U('Shop/shop_list'));		
							}   
						}else{        
							$this->error($rt2['error']); 
						}
					}
				}
				 
			}		
		}else{
			$store_id = I('store_id');
			$store_info = M('stores')->where(array('store_id'=>$store_id,'isdelete'=>0))->find();
			if(empty($store_info)){
				$this->error('该店铺不存在');
			}
			$params3 = array();
			$params3['account_id'] = $store_info['account_id'];  
			$password = I('password');
			if(!empty($password)){
				$params3['password'] = $password;
			}
			$params3['store_provincename'] = I('store_provincename');
			$params3['store_cityname'] = I('store_cityname');
			$params3['store_areaname'] = I('store_areaname');
			$params3['account_storeaddress'] = I('account_storeaddress');
			$params3['account_membername'] = I('account_membername');
			$params3['id_card'] = I('id_card');
			$params3['account_membertel'] = I('account_membertel');
			$params3['remark'] = I('remark');
			$params3['bank_provincenae'] = I('bank_provincenae');
			$params3['bank_cityname'] = I('bank_cityname');
			$params3['bank_areaname'] = I('bank_areaname');
			$params3['bank_name'] = I('bank_name');
			$params3['bank_num'] = I('bank_num');
			$params3['account_zhucehao'] = I('account_zhucehao');
			if(!empty($url1)){
				$params3['storelicense'] = $url1;
			}else{
				$params3['storelicense'] = $store_info['store_license'];
			} 
			if(!empty($url2)){
				$params3['id_card_img_n'] = $url2;
			}else{
				$params3['id_card_img_n'] = $store_info['lianxi_id_img1'];
			} 
			if(!empty($url3)){
				$params3['id_card_img_r'] = $url3;
			}else{
				$params3['id_card_img_r'] = $store_info['lianxi_id_img2'];
			} 
			if(!empty($url4)){
				$params3['other_img'] = $url4;
			}else{
				$params3['other_img'] = $store_info['other_img'];
			}
			$url3 = "http://".$sync_url."/xxapi/index.php?act=operate_openaccount&op=updateApplyStore";
			$json3 =  $this->postCurl($url3,$params3);      			
			$rt3 = json_decode($json3,true);
			if($rt3['result'] == -1){
				$this->error($rt3['error']);
			}else{
				$auth_code = M('system_config')->where(array('status'=>1))->getField('auth_code');
				$sync_url = ADMIN_URL .'/index.php?m=api&c=Store&a=index&auth_code='.$auth_code;
				file_get_contents($sync_url);
				$this->success('编辑店铺成功',U('Shop/shop_list'));
			}    
			    
		} 

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
	
	
	/**
	 * 结算套餐订单
	 * params  int $order_id 申请编号 
	 */
	public function settlement_package_order($order_id){
		$order = M('vip_orders');
		$operate_shareholder_total_price = M('operate_shareholder_total_price');
		$operate_total_price = M('operate_total_price');
		$shareholder = M('operate_shareholder'); 
		$where = array();
		$where['id'] = $order_id;
		$order_info = $order->where($where)->find();
		$month = date("Y-m",mktime());
		$package_name = M('package_list')->where(array('packageid'=>$order_info['packageid']))->getField('name');
		$operate_name = M('operate_center')->where(array('id'=>$order_info['operate_id']))->getField('operate_name');
		$pay_name = '';
		if($order_info['paytype'] == 1){
			$pay_name = '线下支付';
		}else if($order_info['paytype'] == 2){
			$pay_name = '微信支付';
		}else if($order_info['paytype'] == 3){
			$pay_name = '余额支付';
		} 
		if($order_info['issettlement'] == 0){
			/*运营商成员推荐结算*/
			if(!empty($order_info['holder_id']) && $order_info['recommend_profit'] > 0){
				
				$shareholder_info = $shareholder->where(array('id'=>$order_info['holder_id'],'status'=>1))->find();
				$holderdata = array();
				$holderdata['operate_id'] = $shareholder_info['operate_id'];
				$holderdata['operate_name'] = $operate_name;
				$holderdata['shareholder_id'] = $shareholder_info['id'];
				$holderdata['shareholder_name'] = $shareholder_info['shareholder_name'];
				$holderdata['type'] = 1;
				$holderdata['link_orderid'] = $order_id;
				$holderdata['pay_name'] = $pay_name;
				$holderdata['value'] = $order_info['recommend_profit'];
				$holderdata['desc'] = $package_name.'推广收益';
				$holderdata['periods'] = date('Y-m-d',mktime());
				$holderdata['addtime'] = mktime();  
				M('operate_shareholder_price_record')->add($holderdata);
				
				$w = array();
				$w['shareholder_id'] = $shareholder_info['id'];
				$w['month'] = $month;
				$check = $operate_shareholder_total_price->where($w)->find();
				if(!empty($check)){
				$operate_shareholder_total_price->where($w)->setInc('value', $order_info['recommend_profit']);  
				}else{
					$totaldata1 = array();
					$totaldata1['operate_id'] = $shareholder_info['operate_id'];
					$totaldata1['operate_name'] = $operate_name;
					$totaldata1['shareholder_id'] = $shareholder_info['id'];
					$totaldata1['shareholder_name'] = $shareholder_info['shareholder_name'];
					$totaldata1['value'] = $order_info['recommend_profit'];
					$totaldata1['month'] = $month;
					$totaldata1['month_time'] = strtotime($month);
					$totaldata1['is_get'] = 0;
					$operate_shareholder_total_price->add($totaldata1);
				}
			}
			/*运营商结算*/
			if(!empty($order_info['operate_id']) && $order_info['operate_profit'] > 0){
				$operatedata = array();
				$operatedata['operate_id'] = $order_info['operate_id'];
				$operatedata['link_orderid'] = $order_id;
				$operatedata['type'] = 1;
				$operatedata['pay_name'] = $pay_name;
				$operatedata['operate_name'] = $operate_name;
				$operatedata['value'] = $order_info['operate_profit'];
				$operatedata['desc'] =$package_name.'佣金收益';
				$operatedata['periods'] =date('Y-m-d',mktime());
				$operatedata['addtime'] = mktime();
				M('operate_price_record')->add($operatedata);
				$w2 = array();
				$w2['operate_id'] = $order_info['operate_id'];
				$w2['month'] = $month;
				$check2 = $operate_total_price->where($w2)->find();
				if(!empty($check2)){
				$operate_total_price->where($w2)->setInc('value', $order_info['operate_profit']);  
				}else{
					$totaldata2 = array();
					$totaldata2['operate_id'] = $order_info['operate_id'];
					$totaldata2['operate_name'] = $operate_name;
					$totaldata2['value'] = $order_info['operate_profit'];
					$totaldata2['month'] = $month;
					$totaldata2['month_time'] = strtotime($month);
					$totaldata2['is_get'] = 0;
					$operate_total_price->add($totaldata2);
				}
				
				$holder_list = M('operate_shareholder')->where(array('operate_id'=>$order_info['operate_id'],'status'=>1))->field('id')->select();
				foreach($holder_list as $list){
					$holderinfo = array();
					$holderinfo = $shareholder->where(array('id'=>$list['id']))->find();
					$profit = $order_info['operate_profit']*$holderinfo['share_rate']/100;
					if($profit > 0){
						$data = array();
						$data['operate_id'] = $holderinfo['operate_id'];
						$data['operate_name'] = $operate_name;
						$data['shareholder_id'] = $holderinfo['id'];
						$data['shareholder_name'] = $holderinfo['shareholder_name'];
						$data['type'] = 2;
						$data['link_orderid'] = $order_id;
						$data['pay_name'] = $pay_name;
						$data['value'] = $profit;
						$data['desc'] = $package_name.'股东分红';
						$data['periods'] = date('Y-m-d',mktime());
						$data['addtime'] = mktime();  
						M('operate_shareholder_price_record')->add($data);
						
						$w = array();
						$w['shareholder_id'] = $holderinfo['id'];
						$w['month'] = $month;
						$check = array();
						$check = $operate_shareholder_total_price->where($w)->find();
						if(!empty($check)){
							$operate_shareholder_total_price->where($w)->setInc('value', $profit);  
						}else{
							$totaldata1 = array();
							$totaldata1['operate_id'] = $holderinfo['operate_id'];
							$totaldata1['operate_name'] = $operate_name;
							$totaldata1['shareholder_id'] = $holderinfo['id'];
							$totaldata1['shareholder_name'] = $holderinfo['shareholder_name'];
							$totaldata1['value'] = $profit;
							$totaldata1['month'] = $month;
							$totaldata1['month_time'] = strtotime($month);
							$totaldata1['is_get'] = 0;
							$operate_shareholder_total_price->add($totaldata1);
						}
					}	
				}	
				
			}
			$order->where($where)->save(array('issettlement'=>1));   
		}
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
	 * params  tinyint $paytype 支付方式 1-线下支付 2-微信支付 3-余额支付
	 * params  tinyint $type 下单方式 0会员下单 1-管理员添加订单
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
		$w['is_show'] =1;
		$w['status'] =1;
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
	 
	/** 
	 * 支付套餐订单
	 * param int $order_id 订单id
	 * param array $rt 结果 -1:失败 ; 1:成功
	 * 支付套餐订单
	 */
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
	 * ajax判断当前剩余资金是否足够用来开户
	 */
	public function ajax_check_operate_money(){
		$age_limit = I('age_limit',1);
		$operate_num = I('operate_num');
		$package_id = I('package_id');
		if(empty($age_limit) || empty($operate_num) || empty($package_id)){
			echo '参数错误';
			exit;
		}
		$holder = M('operate_shareholder');
		$center = M('operate_center');
		$check1 = $holder->where(array('shareholder_sn'=>$operate_num,'status'=>1))->find();
		if(!empty($check1)){
			$operate_id = $check1['operate_id'];
		}else{
			$operate_id = $center->where(array('operate_sn'=>$operate_num,'status'=>1))->getField('id');
		}     
		$operate_info = $center->where(array('id'=>$operate_id))->find();
		$cost_price = A('Admin')->getPackageCostPrice($age_limit,$package_id,$operate_info['id']);	
		if($cost_price > $operate_info['money']){
			echo '该运营商预存资金为￥'.$operate_info['money'].',购买套餐成本为￥'.$cost_price.',预存资金不足';   
		}else{
			echo "该运营商预存资金为￥".$operate_info['money']."<br/>购买套餐成本为￥".$cost_price;  	
		
		}	
	}

	public function ajax_check_password(){
		$loginname = session('loginname');
		$pass = I('login_pass');
		$m = M('admin');
		$w = array();
		$w['loginname'] = $loginname;
		$w['password'] = md5($pass);
		$w['status'] = 1;
		$check = $m->where($w)->find();
		if(!empty($check)){
			echo 1;
		}else{
			echo -1;
		}
	}	
	/*图片上传*/
	private function upload($files = '')
    {
        $upload = new \Think\Upload(); // 实例化上传类
        $upload->maxSize = 2048000; // 设置附件上传大小
        $upload->exts = array(
            'jpg',
            'gif',
            'png',
            'jpeg'
        ); // 设置附件上传类型
        $upload->rootPath = C('IMG_ROOT_PATH'); // 设置附件上传根目录
        $upload->savePath = ''; // 设置附件上传（子）目录
        $upload->replace = true; // 上传文件
        $info = $upload->upload($files);
        if (!$info) { // 上传错误提示错误信息
            $this->error($upload->getError());
        } else { // 上传成功
            // $this->success('上传成功！');
            return $info;
        }
    }
	
	/*获取账号列表*/
	public function  ajax_get_memberlist(){
		$vip = I('vip',0);
		$sync_url = M('system_config')->where(array('status'=>1))->getField('sync_url');    
		$url = "http://".$sync_url."/xxapi/index.php?act=operate_openaccount&op=selectXunxinnum"; 
		$param = array();
		$param['vip'] = $vip;      
		$param['channel_id'] = 0;
		$member_name_list = $this->postCurl($url,$param);
		$data = json_decode($member_name_list,true);
		echo json_encode($data,JSON_UNESCAPED_UNICODE);
	}
	
	public function ajax_renews(){ 
		$store_id = I('store_id');
		$age_limit = I('agelimit');
		$loginname = session('loginname');
		$pass = I('login_pass');
		$mp = M('admin'); 
		$rt = array();
		$wp = array();
		$wp['loginname'] = $loginname;
		$wp['password'] = md5($pass);
		$wp['status'] = 1;
		$check = $mp->where($wp)->find();
		if(empty($check)){ 
			$rt['status'] = -1;
			$rt['error'] = '登录密码错误';
			die(json_encode($rt,JSON_UNESCAPED_UNICODE));
		}
		$m = M('stores');
		$w = array();
		$w['store_id'] = $store_id;
		$w['isdelete'] = 0;
		$info = $m->where($w)->find();
		if(empty($info['package_id'])){
			$rt['status'] = -1;
			$rt['error'] = '该店铺没有购买套餐';
		}else{
			$cost_price = A('Admin')->getPackageCostPrice($age_limit,$info['package_id'],$info['operate_id'],$store_id);
			if(!empty($info['operate_id'])){
				$operate_info = M('operate_center')->where(array('id'=>$info['operate_id']))->find();
				if($cost_price > $operate_info['money']){
					$rt['status'] = -1;
					$rt['error'] = '该运营商预存资金为￥'.$operate_info['money'].',购买套餐成本为￥'.$cost_price.',预存资金不足';
					die(json_encode($rt,JSON_UNESCAPED_UNICODE));
				}
			}
		
			  
			$w2 = array();
			$w2['packageid'] = $info['package_id'];
			$w2['status'] = 1;
			$packageinfo = M('package_list')->where($w2)->find();
			if(empty($packageinfo)){
				$rt['status'] = -1; 
				$rt['error'] = '该套餐不存在';
				die(json_encode($rt,JSON_UNESCAPED_UNICODE));
			}     
			if($age_limit == 1){
				$market_price = $packageinfo['market_price'];
			}else if($age_limit == 2){
				$market_price = $packageinfo['market_price2'];
			}else if($age_limit == 3){
				$market_price = $packageinfo['market_price3'];
			}
			if($info['is_try'] == 1){
				$news = M('shareholder_package_edit')->where(array('operate_id'=>$info['operate_id'],'package_id'=>$info['package_id'],'status'=>1))->find();
				if(!empty($news)){
					if($age_limit == 1){
						$market_price = $news['package_price'];
					}else if($age_limit == 2){
						$market_price = $news['package_price2'];
					}else if($age_limit == 3){
						$market_price = $news['package_price3'];
					}
				}             
			} 
			$sync_url = M('system_config')->where(array('status'=>1))->getField('sync_url');    
			$url = "http://".$sync_url."/xxapi/index.php?act=operate&op=pcReNews"; 
			$out_trade_no = 'pcupgrade_'.date('ymdHis').'-'.$info['member_id'];
			$param = array();
			$param['store_id'] = $store_id;      
			$param['channel_id'] = 0;
			$param['store_id'] = $store_id;
			$param['out_trade_no'] = $out_trade_no;
			$param['age_limit'] = $age_limit;
			$param['money'] = $market_price; 
			$json = $this->postCurl($url,$param); 
			$datas = json_decode($json,true);
			if($datas['status'] == 1){
				if(!empty($info['operate_id'])){
					$da = array();
					$da['value'] = 0 -$cost_price;
					$da['final_value'] = $operate_info['money'] - $cost_price;
					$da['operate_id'] = $info['operate_id'];
					$da['type'] = 2;
					$da['order_sn'] = $out_trade_no;
					$da['periods'] = date('Y-m-d');
					$da['addtime'] = mktime();
					$da['editor'] =  session('loginname');
					$da['status'] = 1;
					if(M('operate_trade_record')->add($da)){
						$check = M('operate_center')->where(array('id'=>$info['operate_id']))->setDec('money',$cost_price);
						if($check !== false){ 
							$rt['status'] = 1;
						}else{
							$rt['status'] = -1;
							$rt['error'] = '更改运营商预存金额失败';
						}
					}else{
						$rt['status'] = -1;
						$rt['error'] = '插入运营商交易记录失败';
					}
				}else{
					$rt['status'] = 1;
				}
			}
			   
		} 
		
		echo json_encode($rt,JSON_UNESCAPED_UNICODE);      
	}
	
	
    	 

  

}
?>






