<?php
namespace Home\Controller;
use Think\Controller;
 
/**
 * XUNXIN PC 后台管理 后台主页
 * ============================================================================
 * 版权所有 2005-2010 厦门微聚点科技有限公司，并保留所有权利。
 * 网站地址: http://www.vjudian.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: youyan $
 * $Id: IndexController.class.php
 */
class IndexController extends AdminController
{
 
    private $today;

    
    public function index()
    {
		$hasAuth=session('admin_priv');
        if($hasAuth=='0'){
			$info = array();
			$store = M('stores');
			$operate_total_price = M('operate_total_price');
			$order = M('vip_orders');
			$operate = M('operate_center');
			$shareholder = M('operate_shareholder');
			$trade_record = M('operate_trade_record');
			$w1 = array();
			$w1['is_get'] = 0;
			$value1 = $operate_total_price->where($w1)->sum('value');
			$info['unset_price'] = empty($value1) ? 0 : $value1; //推广费用未打款
			$t = strtotime(date('Y-m-d'));
			$w2 = array();
			$w2['_string'] = "account_time > $t OR recharge_time > $t ";
			$value2 = $store->where($w2)->count();
			$info['new_add_store'] = $value2;   //今日新增店铺
			$value3 = $store->count();
			$info['all_store'] = $value3; // 店铺总数
			$w4 = array();
			$w4['channel_type'] = '2';
			$w4['main_store'] = '1';
			$value4 = $store->where($w4)->count();
			$info['all_mall'] = $value4; // 商城总数量
			$t2 =  mktime(date('H'),date('i'),date('s'),date('m')+1,date('d'),date('Y')); //一个月后
			$w5 = array();
			$w5['vip_endtime'] = array(array('gt',$t),array('elt',$t2));
			$value5 = $store->where($w5)->count();
			$info['onemonth_limit_store'] = $value5; //一个月内到期
			$w6 = array();
			$w6['istry'] = 1;
			$value6 = $store->where($w6)->count();
			$info['try_store'] = $value6;  //试用期
			$w7 = array();
			$w7['package_id'] = 1;
			$value7 = $store->where($w7)->count();
			$info['major_store'] = $value7;	  //专业版
			$w8 = array();
			$w8['package_id'] = 2;
			$value8 = $store->where($w8)->count();
			$info['company_store'] = $value8;	  //企业版
			$w9 = array();
			$w9['vip_endtime'] = array('lt',mktime());
			$value9 = $store->where($w9)->count();   //已过期
			$info['overdue_store'] = $value9;
			$w10 = array();
			$w10['rechargetime'] = array('egt',$t);
			$w10['status'] = 1;
			$value10 = $order->where($w10)->sum('sale_price');
			$info['today_rechage'] = empty($value10) ? 0 : $value; //今日续费充值
			$w11 = array();
			$w11['status'] = 1;
			$value11 = $order->where($w11)->sum('sale_price');
			$info['total_rechage'] = empty($value11) ? 0 : $value11; //累计续费充值
			$value12 = $operate_total_price->sum('value');
			$info['total_price']  = empty($value12) ? 0 : $value12; //总计推广费用
			$value13 = $info['total_price'] - $info['unset_price'];
			$info['get_price'] = $value13; //推广已打款费用
			$w14 = array();
			$w14['status'] = 1;
			$value14 = $operate->where($w14)->count();
			$info['operate_num'] = $value14; //运营商数量
			$w15 = array();
			$w15['status'] = 1;
			$value15 = $shareholder->where($w15)->count();
			$info['shareholder_num'] = $value15; //推广成员总计
			$w16 = array();
			$w16['type'] = 1;
			$value16 = $trade_record->where($w16)->sum('value');
			$info['prestore_price'] = empty($value16) ? 0 : $value16; //运营商预存总计
			$w17 = array();
			$w17['type'] = 2;
			$value17 = $trade_record->where($w17)->sum('value');
			$info['unused_prestore_price'] = $info['prestore_price'] + $value17; //未使用预存款
			$w18 = array();
			$w18['opentype'] = 1;
			$value18 = $store->where($w18)->count();
			$info['type1_store'] = $value18; //运营商推广店铺
			$w19 = array();
			$w19['opentype'] = 2;
			$value19 = $store->where($w19)->count();
			$info['type2_store'] = $value19; //运营商直接开户店铺
			$w20 = array();
			$w20['opentype'] = 3;
			$value20 = $store->where($w20)->count(); 
			$info['type3_store'] = $value20; //运营商其他开户店铺
			$this->assign('info',$info);
			$month = date("Y-m",mktime(0,0,0,date('m')-1,1,date('Y')));
			$this->assign('month',$month);
			$ui['index'] = 'active';
			$this->assign('ui',$ui);
			$this->display('Index/index');  
        }else{
			$this->redirect('Shareholder/index');
		}         
		
	}
    public function data_analysis()
    {
		$record = M('data_record');
		$package = M('package_list');
		$where = array(); 
		$operate_sn = M('operate_center')->where(array('login_name'=>session('loginname')))->getField('operate_sn');
		if(!empty($operate_sn)){
			$where['ad_id'] = array('like',$operate_sn.'%');
		}  
		$search = I();
		if(!empty($search['time_style'])){
			if($search['time_style'] == 'today'){
				$where['addtime'] = array('gt',mktime(0,0,0,date('m'),date('d'),date('Y')));
			}else if($search['time_style'] == 'week'){
				$where['addtime'] = array('gt',mktime()-7*24*3600);
			}else if($search['zdy']){
				$Time1 = I('Time1'); 
				$Time2 = I('Time2');  
				if(!empty($Time1) && empty($Time2))  
				{
					$t = strtotime($Time1)+24*60*60 ;
					$where['_string']= "(addtime >= '".strtotime($Time1) ."'&& addtime < '".$t."')";
				}         
				if(!empty($Time2) && empty($Time1))  
				{  
					$where['_string']= "(addtime<= '". strtotime($Time2)."')";  
				}  
				if(!empty($Time2) && !empty($Time1))
				{   
					$t = strtotime($Time2)+24*60*60 ;
					$where['_string']= "(addtime >= '".strtotime($Time1) ."'&& addtime < '" .$t."')";  
				}
			}
		}
		if(!empty($search['device'])){
			$where['device'] = $search['device'];
		}
		if(!empty($search['domain_url'])){
			$where['domain_url'] = array('like',"%".$search['domain_url']."%");
		}
		if(!empty($search['ad_id'])){
			$where['ad_id'] = array('like',"%".$search['ad_id']."%");
		}		
		$this->assign('search',$search);
		$count = $record->where($where)->count(); // 查询满足要求的总记录
        $Page = new \Think\Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show(); // 分页显示输出  
		$lists =$record->where($where)->order('log_id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$operates = M('operate_center');
		$holders = M('operate_shareholder');
		foreach($lists as &$list){ 
			if(!empty($list['ad_id'])){
				$operate_name = $operates->where(array('operate_sn'=>$list['ad_id'],'status'=>1))->getField('operate_name');
				if(empty($operate_name)){
					$holderinfo = $holders->where(array('shareholder_sn'=>$list['ad_id'],'status'=>1))->field('operate_id,shareholder_name')->find();
					$operate_name = $holderinfo['shareholder_name'];
					if(!empty($holderinfo)){
						$name = $operates->where(array('id'=>$holderinfo['operate_id'],'status'=>1))->getField('operate_name');
						if(!empty($name)){
							$operate_name = $name.'-'.$operate_name;
						}
					}
				} 
				if(!empty($operate_name)){
					$list['operate_name'] = $operate_name.'('.$list['ad_id'].')';
				}
			}     
			
			if($list['open_result'] == 'SUCCESS'){
				$list['package_name'] = $package->where(array('packageid'=>$list['package_id']))->getField('name');
				
			} 
		}
		$this->assign('lists',$lists);
		$this->assign('page',$show);
		$ui['data_analysis'] = 'active';     
		$this->assign('ui',$ui);
		$this->display('Index/data_analysis');
	}
	public function getDataInfo(){
		$Time1 = date("Y-m-d",strtotime("-1 day"));
		$this->assign('Time1',$Time1);
		$t1 = strtotime($Time1);
		$t2 = $t1 + 24*60*60;
		$info = array();
		$order = M('orders'); 
		$contribute_record = M('contribute_record');	
		$w1 = array();
		$w1['issuccess'] = 1;
		$w1['receive_time'] =array('between',"$t1,$t2");
		$info['ordernum'] = $order->where($w1)->count();
		$info['orderprice'] = $order->where($w1)->sum('totalprice');
		$info['orderprice'] = empty($info['orderprice']) ? 0 : $info['orderprice'];
		$info['orderpv'] = $order->where($w1)->sum('order_pv');  
		$info['orderpv'] = empty($info['orderpv']) ? 0 : $info['orderpv'];
		$w2 = array();
		$w2['status'] = 1;
		$w2['addtime']= array('between',"$t1,$t2");
		$info['newcontribute'] = $contribute_record->where($w2)->sum('value');
		$info['newcontribute'] = empty($info['newcontribute']) ? 0 : $info['newcontribute'];
		$w3 = array();
		$w3['status'] = 1;
		$w3['addtime']= array('lt',$t1);	   
		$info['allcontribute'] = $contribute_record->where($w3)->sum('value');
		$info['allcontribute'] = empty($info['allcontribute']) ? 0 : $info['allcontribute'];
		$w4 = array();
		$w4['status'] = 1;
		$w4['periods'] = $Time1;
		$subsidy = M('subsidy_record')->where($w4)->getField('value');
		$info['subsidy'] = empty($subsidy) ? '0.00' : $subsidy;
		return $info;
	}
	/**
     * 获得首页所要的数据1
     */
	private function  getIndexInfo(){
		$index_info = array();
		$vip_order = M('vip_orders');
		$t1 = strtotime(date('Y-m-d'));
		$t2= $t1 + 24*3600;
		$w1 = array();
		$w1['status'] = 1;
		$w1['passtime'] = array(array('egt',$t1),array('lt',$t2));
		//今日套餐数量
		$index_info['package_num'] = $vip_order->where($w1)->count();
		//今日套餐额
		$index_info['package_price'] = $vip_order->where($w1)->sum('price');
		$index_info['package_price'] = empty($index_info['package_price']) ? 0 : $index_info['package_price'];
		$w2 = array();
		$w2['status'] = 1;
		//所有套餐数量
		$index_info['all_package_num'] = $vip_order->where($w2)->count();
		//所有套餐额
		$index_info['all_package_price'] = $vip_order->where($w2)->sum('price');
		$index_info['all_package_price'] = empty($index_info['all_package_price']) ? 0 : $index_info['all_package_price'];   
		$member = M('members');
		$w3 = array();
		$w3['shield'] = 0;
		$w3['member_time'] = array(array('egt',$t1),array('lt',$t2));
		//今日新增会员	
		$index_info['new_member_num'] = $member->where($w3)->count();
		$w4 = array();
		$w4['level'] = 1;
		$w4['shield'] = 0;
		//vip1会员
		$index_info['vip1_member_num'] = $member->where($w4)->count();
		$w5 = array();
		$w5['level'] = 2;
		$w5['shield'] = 0;
		//vip2会员
		$index_info['vip2_member_num'] = $member->where($w5)->count();
		$w6 = array();
		$w6['shield'] = 0; 
		//会员总数
		$index_info['all_member_num'] = $member->where($w6)->count();
		$drawmoney_record = M('drawmoney_record');
		$w7 = array();
		$w7['status'] = 1;
		//提现申请
		$index_info['drawmoney'] = $drawmoney_record->where($w7)->sum('drawmoney');
		$index_info['drawmoney'] = empty($index_info['drawmoney']) ? 0 : $index_info['drawmoney'];
		$member_wallet = M('member_wallet');
		
		//钱包余额
		$index_info['cash_money'] = $member_wallet->sum('cash_money');
		$index_info['cash_money'] = empty($index_info['cash_money']) ? 0 : $index_info['cash_money'];
		$member_wallet_record = M('member_wallet_record');
		$w8 = array();
		$w8['type'] = 3;
		$w8['prices'] = array('gt',0);
		//钱包总额
		$index_info['all_cash_money'] = $member_wallet_record->where($w8)->sum('prices');
		$index_info['all_cash_money'] = empty($index_info['all_cash_money']) ? 0 : $index_info['all_cash_money'];
		$contribute_record = M('contribute_record');
		$w9 = array();
		$w9['status'] = 1;	
		//总贡献值
		$index_info['all_contribute'] = $contribute_record->where($w9)->sum('value');
		$index_info['all_contribute'] = empty($index_info['all_contribute']) ? 0 : $index_info['all_contribute'];  
		$order = M('orders');
		$t3 =  $t1 - 24*3600;
		$w10 = array();
		$w10['issuccess'] = 1;
		$w10['receive_time'] = array(array('egt',$t3),array('lt',$t1));
		//昨日订单量
		$index_info['yesterday_order_num'] = $order->where($w10)->count();
		//昨日订单额
		$index_info['yesterday_order_price'] = $order->where($w10)->sum('totalprice');
		$index_info['yesterday_order_price'] = empty($index_info['yesterday_order_price']) ? 0 : $index_info['yesterday_order_price'];  
		$w11 = array();
		$w11['issuccess'] = 1;
		//总订单量
		$index_info['all_order_num'] = $order->where($w11)->count();
		//总订单额	
		$index_info['all_order_price'] = $order->where($w11)->sum('totalprice');
		$index_info['all_order_price'] = empty($index_info['all_order_price']) ? 0 : $index_info['all_order_price'];   
		return $index_info;
	}  
    /**
     * 获得所有首页所要的数据
     */
    private function getIndexInfo_old()
    {
        $index_info = array();
		/*今日产品订单*/
        $index_info['new_order_num'] = $this->get_today_order_count();
		/*总成交订单量*/
		$index_info['all_order_num'] = $this->get_all_order();
		/*今日成交金额*/
		$index_info['new_order_price'] = $this->get_today_order_price();
		/*总成交金额*/
		$index_info['all_order_price'] = $this->get_all_order_price();
		/*今日新增会员*/
		$index_info['new_member_num'] = $this->get_today_member_count();
		/*会员总数*/
		$index_info['all_member_num'] = $this->get_all_member_count();
		/*VIP1会员数*/
		$index_info['vip1_member_num'] = $this->get_vip1_member_count();
		/*VIP2会员数*/
		$index_info['vip2_member_num'] = $this->get_vip2_member_count();
		/*电子钱包余额*/
		$index_info['cash_money'] = $this->get_cash_money();
		/*总计电子钱包*/
		$index_info['all_cash_money'] = $this->get_all_cash_money();
		/*消费钱包余额*/
		$index_info['pay_money'] = $this->get_pay_money();
		/*总计消费钱包*/  
		$index_info['all_pay_money'] = $this->get_all_pay_money();
		/*PV余额*/
		$index_info['pv'] = $this->get_pv();
		/*总计PV*/    
		$index_info['all_pv'] = $this->get_all_pv();
        return $index_info;
    }
	
	
	/**
	 * pv余额
	 */
	private function get_pv(){
		$m = M('member_wallet');
		$sum = $m->sum('pv_amount');
	    if (empty($sum))
            return 0;
        return $sum;
	}
	
	/**  
	 * 总计pv
	 */
	private function get_all_pv(){
		$m = M('member_wallet_record');
		$where = array();
		$where['type'] =1;
		$where['prices'] = array('gt',0);
		$sum = $m->where($where)->sum('prices');
	    if (empty($sum))
			return 0;  
        return $sum;
	} 
	
	/**
	 * 电子钱包余额
	 */
	private function get_cash_money(){
		$m = M('member_wallet');
		$sum = $m->sum('cash_money');
	    if (empty($sum))
            return 0;
        return $sum;
	}
	
	/**
	 * 总计电子钱包
	 */
	private function get_all_cash_money(){
		$m = M('member_wallet_record');
		$where = array();
		$where['type'] =3;
		$where['prices'] = array('gt',0);
		$sum = $m->where($where)->sum('prices');
	    if (empty($sum))
			return 0;  
        return $sum;
	} 
	
	/**
	 * 消费钱包余额
	 */
	private function get_pay_money(){
		$m = M('member_wallet');
		$sum = $m->sum('consume_money');
	    if (empty($sum))
            return 0;
        return $sum;
	}
	
	/**
	 * 总计消费钱包
	 */
	private function get_all_pay_money(){
		$m = M('member_wallet_record');
		$where = array();
		$where['type'] =2;
		$where['prices'] = array('gt',0);
		$sum = $m->where($where)->sum('prices');
	    if (empty($sum))
			return 0;   
        return $sum;
	}  
	/**
	  * 获取vip1会员数
	  */
	 private function get_vip1_member_count()
	 {
		$Member = M('members');
        $where = array();
		$where['level'] = 1;
        $where['shield']=0;
        $count = $Member->where($where)->count(); // 查询满足要求的总记录数
        return $count;
	 }
	 
	 /**
	  * 获取vip2会员数
	  */
	 private function get_vip2_member_count()
	 {   
		$Member = M('members');
        $where = array();
		$where['level'] = 2;
        $where['shield']=0;
        $count = $Member->where($where)->count(); // 查询满足要求的总记录数
        return $count;
	 }
	 
    /**
     * 获得今天新增加的会员数目
     *
     * @return unknown
     */
    private function get_today_member_count()
    {
        $Member = M('members');
        $where = array();
        $where['register_date'] = array(
            'gt',
            $this->today
        );
        $where['shield']=0;
        $count = $Member->where($where)->count(); // 查询满足要求的总记录数
        return $count;
    }
	
	private function get_all_member_count()
	{
		$Member = M('members');
        $where = array();
        $where['shield']=0;
        $count = $Member->where($where)->count(); // 查询满足要求的总记录数
        return $count;
	}
    /**
	 * 获取总成交订单量
	 */
	private function get_all_order()
	{
		$where = array();
		$where['issuccess'] =1;
		$count = $this->get_order_count($where);
		return $count;
		
	}
    /**
     * 获得今天新增加订单数目  
     */
    private function get_today_order_count()
    {
        $where = array();
        $where['create_time'] = array(
            'gt',
            $this->today
        );
        $count = $this->get_order_count($where);
        return $count;
    }  

    

    /**
     * 获得所有订单数目
     */
    private function get_order_count($condition = array())
    {
        $Order = M('orders');
       
        $where['order_state'] = array(
            'in',
            '2,3'  
        );
        // 补充条件
        $where['_complex'] = $condition;
        
        $count = $Order->where($where)->count(); // 查询满足要求的总记录数
        return $count;
    }

    /**
     * 获得今天订单成交额
     */
    private function get_today_order_price()
    {
        $Order = M('orders');
        $where = array();
        $where['create_time'] = array(
            'gt',
            $this->today
        );
        $where['issuccess'] = 1;
        
        $sum = $Order->where($where)->sum('totalprice');
        if (empty($sum))
            return 0;
        return $sum;
    }
     
	/*总订单成交金额*/ 
    private function get_all_order_price()
	{
		$Order = M('orders');
        $where = array();
		$where['issuccess'] = 1;
		 $sum = $Order->where($where)->sum('totalprice');
        if (empty($sum))
            return 0;
        return $sum;
	}
	
	
	

	public function addinfo(){
		$m = M('members'); 
		$wallet = M('member_wallet');
		$contribute_record = M('contribute_record');
		$recommend_rate = M('settlement_config')->where(array('status'=>1))->getField('recommend_rate');
		$center = M('operate_center');
		//默认运营商ID
		$default_operate_id = $center->where(array('main_store'=>1,'status'=>1))->getField('id');
		/*推荐人的数组*/    
		$recommend_array = array();
		$ws= array();  
		$ws['_string']='operate_id is null';
		//$w['operate_id'] = array('eq',null);
		$lists = $m->where($ws)->select();
		var_dump($lists);   
		foreach($lists as $key=>$da){
			$data = array();  
			$data['member_id'] = $da['member_id']; 
			if(!empty($da['recommend_id'])){
				$recommend_array[$key]['recommend_id'] = $da['recommend_id'];
				$recommend_array[$key]['member_id'] = $da['member_id'];
			}
			$recommend_id = $da['recommend_id'];
			$operate_id = $center->where(array('member_id'=>$recommend_id,'status'=>1))->getField('id');
			while(!empty($recommend_id) && empty($operate_id)){
				$recommend_id = $m->where(array('member_id'=>$recommend_id))->getField('recommend_id');
				echo $recommend_id."<br/>";
				$operate_id = $center->where(array('member_id'=>$recommend_id,'status'=>1))->getField('id');
			}
			if(empty($operate_id)){
				$operate_id = $default_operate_id;  
			}
			$m->where(array('member_id'=>$da['member_id']))->save(array('operate_id'=>$operate_id));	
			$jg2 = $wallet->where($data)->find();
			if(empty($jg2)){
				$wallet->add($data);
			}    		
		}
		foreach($recommend_array as $recommend_mid){
			$rt = array();
			$rt = $m->where(array('member_id'=>$recommend_mid['recommend_id']))->find();
			if(!empty($rt)){
				$cw = array();
				$cw['children_uid'] =  $recommend_mid['member_id'];  
				$cw['datafrom'] = 1;
				$rt2 = $contribute_record->where($cw)->find();
				if(empty($rt2)){
					$w = array();          
					$w['lm_members.member_id'] = $recommend_mid['recommend_id'];
					$member_rate = $m->join('LEFT JOIN lm_package_list ON lm_members.package_id=lm_package_list.packageid')->where($w)->getField('lm_package_list.contribute_rate'); 
					$member_rate = empty($member_rate) ? 1 : $member_rate;
					$recommenddata = array();   
					$recommenddata['member_id'] = $recommend_mid['recommend_id']; 
					$recommenddata['children_uid'] = $recommend_mid['member_id'];  
					$recommenddata['datafrom'] = 1;   
					$recommenddata['value'] =  $recommend_rate * $member_rate;
					$recommenddata['desc'] = '通过直推会员获得 '.$recommenddata['value'].' 成长值';
					$recommenddata['status'] = '1';  
					$recommenddata['addtime'] = mktime();   
					if($contribute_record->add($recommenddata)){
						$wallet->where(array('member_id'=>$recommend_mid['recommend_id']))->setInc('contribute_value',$recommenddata['value']);  		    				
					}  
				}				
			}   
		}
		die('结算');
		
	}
      
}
?>

