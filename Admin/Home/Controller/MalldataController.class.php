<?php
/**
 * design by yy
 * 我的佣金类
 * 获得佣金记录
 * 佣金提现
 * 我的佣金
 */     
namespace Home\Controller;
use Think\Controller;
Vendor('wxpaylib.WxPay#Api');      
Vendor('wxpaylib.WxPay#Notify');
Vendor('wxpaylib.WxPay#JsApiPay');
class MalldataController extends AdminController
{       
	public function __construct()
    {    
        parent::__construct();
        if(!$this->checkAuth()){
			$this->error('你没有该权限',U('Index/index'));
		} 
    } 
	public function  malldata_list(){
		$m= M('orders');  
		$w = array();
		$w['lm_orders.issuccess'] = 1;
		$Time1 = I('Time1');
		$Time2 = I('Time2');
		$store_name = I('store_name');
		$operate_name = I('operate_name');
		$member_name = I('member_name');
		$order_id = I('order_id');
		if(!empty($Time1) && empty($Time2))  
		{
			$t = strtotime($Time1)+24*60*60 ;
			$w['_string']= "lm_orders.receive_time >= '".strtotime($Time1) ."'&&lm_orders.receive_time < '".$t."'";
		}         
		if(!empty($Time2) && empty($Time1))
		{  
			$w['_string']= "lm_orders.receive_time<= '". strtotime($Time2)."'";  
		}  
		if(!empty($Time2) && !empty($Time1))
		{   
			$t = strtotime($Time2)+24*60*60 ;
			$w['_string']= "lm_orders.receive_time >= '".strtotime($Time1) ."'&&lm_orders.receive_time < '" .$t."'";  
		}
		if(!empty($store_name)){
			$w['lm_orders.store_name'] = array('like','%'.$store_name.'%');
			$this->assign('store_name',$store_name);
		}
		if(!empty($operate_name)){  
			$w['lm_operate_center.operate_name'] = array('like','%'.$operate_name.'%');
			$this->assign('operate_name',$operate_name);
		}  
		if(!empty($member_name)){
			$w['lm_members.member_name'] = array('like','%'.$member_name.'%');
			$this->assign('member_name',$member_name);
		} 
		if(!empty($order_id)){
			$w['lm_orders.order_id'] = array('like','%'.$order_id.'%');
			$this->assign('order_id',$order_id);
		}	
		      
		   
		
		$lists = $m->join('LEFT JOIN lm_members ON lm_members.member_id = lm_orders.buyer_id')->join('LEFT JOIN lm_operate_center ON lm_operate_center.id= lm_members.operate_id')->where($w)->field('lm_orders.*,lm_members.member_name,lm_members.operate_id,lm_operate_center.operate_name')->select();    
		$total_pv = $m->join('LEFT JOIN lm_members ON lm_members.member_id = lm_orders.buyer_id')->join('LEFT JOIN lm_operate_center ON lm_operate_center.id= lm_members.operate_id')->where($w)->field('lm_orders.*,lm_members.member_name,lm_members.operate_id,lm_operate_center.operate_name')->sum('order_pv');  
		$this->assign('total_pv',$total_pv);
		$total_price = $m->join('LEFT JOIN lm_members ON lm_members.member_id = lm_orders.buyer_id')->join('LEFT JOIN lm_operate_center ON lm_operate_center.id= lm_members.operate_id')->where($w)->field('lm_orders.*,lm_members.member_name,lm_members.operate_id,lm_operate_center.operate_name')->sum('totalprice');    
		$this->assign('total_price',$total_price);
		$config = M('settlement_config')->where(array('status'=>1))->find();
		$this->assign('config',$config);     
		$ui['malldata_list'] = 'active';
		$this->assign('lists',$lists);
		$this->assign('ui',$ui);  
		$this->display('malldata_list');    
	}
	
	public function malldata_shareinfo(){
		$Time1 = I('Time1');
		if(empty($Time1)){
			$Time1 = date("Y-m-d",strtotime("-1 day"));
		}  
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
		$this->assign('info',$info);
		$config = M('settlement_config')->where(array('status'=>1))->find();
		$this->assign('config',$config);    
		$ui['malldata_shareinfo'] = 'active';
		$this->assign('ui',$ui);    
		$this->display('malldata_shareinfo'); 
	}
	   
}   

?>

