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
class FundController extends AdminController
{     
	public function __construct()
    {    
        parent::__construct();
        if(!$this->checkAuth()){
			$this->error('你没有该权限',U('Index/index'));
		} 
    } 
	public function history_detail()
    {
		$m = M('operate_price_record');
		$m2 = M('operate_total_price');
      	$w =array();
		$periods = I('periods');
		if(!empty($periods)){
			$w['lm_operate_price_record.periods'] = array('like',"$periods%");
		}   
		$operate_name = I('operate_name');
		if(!empty($operate_name)){
			$w['lm_operate_price_record.operate_name'] = array('like',"%$operate_name%");
		}
		$operate_sn = I('operate_sn');
		if(!empty($operate_sn)){
			$operate_id = M('operate_center')->where(array('operate_sn'=>$operate_sn))->getField('id');
			$w['lm_operate_price_record.operate_id'] = $operate_id;
		}  
		$type = I('type');  
		if(!empty($type)){
			$w['lm_operate_price_record.type'] = $type;
		}
		$link_orderid = I('link_orderid');
		if(!empty($link_orderid)){
			$w['lm_operate_price_record.link_orderid'] = array('like',"%$link_orderid%");
		}
		$Time1 = I('Time1');
		$Time2 = I('Time2');  
		if(!empty($Time1) && empty($Time2))  
		{
			$t = strtotime($Time1)+24*60*60 ;
			$w['_string']= "lm_operate_price_record.addtime >= '".strtotime($Time1) ."'&& lm_operate_price_record.addtime < '".$t."'";
		}         
		if(!empty($Time2) && empty($Time1))
		{  
			$w['_string']= "lm_operate_price_record.addtime<= '". strtotime($Time2)."'";  
		}  
		if(!empty($Time2) && !empty($Time1))
		{   
			$t = strtotime($Time2)+24*60*60 ;
			$w['_string']= "lm_operate_price_record.addtime >= '".strtotime($Time1) ."'&& lm_operate_price_record.addtime < '" .$t."'";  
		} 
		$count = $m->join('LEFT JOIN lm_operate_center ON lm_operate_center.id = lm_operate_price_record.operate_id')->where($w)->count();	
		$Page = new \Think\Page($count, 15); 
        $show = $Page->show();  
		$lists = $m->join('LEFT JOIN lm_operate_center ON lm_operate_center.id = lm_operate_price_record.operate_id')->where($w)->order('lm_operate_price_record.addtime DESC,lm_operate_price_record.id DESC')->field('lm_operate_price_record.*,lm_operate_center.operate_sn')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		foreach($lists as $k=>$list){
			if($list['type'] == 2){
				$where = array();
				$where['operate_id'] = $list['operate_id'];
				$where['month'] = $list['periods'];
				$lists[$k]['payment_img'] = $m2->where($where2)->getField('payment_img');
			}
		}   	
		$info = array();
		$info['num'] = $count;
		$w2 = array();
		$w2['lm_operate_price_record.type'] = 1;
		if(!empty($w)){
			$w2['_complex'] = $w;
		}
		$totalmoney = $m->where($w2)->sum('value');
		$info['totalmoney'] = empty($totalmoney) ? '0.00' : round($totalmoney,2);
		$w3 = array();
		$w3['lm_operate_price_record.type'] = 2;
		if(!empty($w)){
			$w3['_complex'] = $w;
		}   
		$getmoney = $m->where($w3)->sum('value');
		$info['getmoney'] = empty($getmoney) ? '0.00' : round(0-$getmoney,2);
		$this->assign('info',$info);
		$this->assign('lists',$lists);	
		$this->assign('page',$show);    
        $ui['history_detail'] = 'active';
        $this->assign('ui',$ui);
        $this->display("history_detail"); // 输出模板
    }

	/*运营商利润 按月查看*/
    public function according_month()
    {
		$m = M('operate_total_price');
		$info = array();
		$info['total'] = $m->sum('value');
		$info['total'] = empty($info['total']) ? '0.00' : round($info['total'],2);
		$info['getmoney'] = $m->where(array('is_get'=>1))->sum('value');
		$info['getmoney'] = empty($info['getmoney']) ? '0.00' : round($info['getmoney'],2);
		$this->assign('info',$info);
		/*按月查看*/   
		$lists = $m->group('month')->field('sum(value) total,month')->select();    
		foreach($lists as $k=>$list){
			$w = array();
			$w['month'] = $list['month'];
			$w['is_get'] = 1;
			$w2 = array();
			$w2['month'] = $list['month'];
			$w2['is_get'] = 0;	
			$lists[$k]['Time1'] = $list['month'].'-01';
			$t = strtotime($lists[$k]['Time1']);
			$lists[$k]['Time2'] = date('Y-m-d',mktime(0,0,0,date('m',$t)+1,1,date('Y',$t)));
			$totalmoney = $m->where(array('month'=>$list['month']))->sum('value');
			$lists[$k]['totalmoney'] = empty($totalmoney) ? 0 : $totalmoney;
			$get_money = $m->where($w)->sum('value');
			$lists[$k]['get_money'] = empty($get_money) ? 0 : $get_money;
			$unget_money = $m->where($w2)->sum('value');
			$lists[$k]['unget_money'] = empty($unget_money) ? 0 : $unget_money;
			$lists[$k]['operate_num'] = $m->where(array('month'=>$list['month']))->group('operate_id')->count();
			
		}
		$this->assign('lists',$lists);
	   	$ui['according_month'] = 'active';
        $this->assign('ui',$ui);
        $this->display('according_month');  
    }
	
	/*运营商利润 按运营商名称查看*/
	public function according_name()
    {
		$m = M('operate_total_price');
		$where = array();	
		$operate_id = I('operate_id');
		$operate_name = I('operate_name');
		$link_name = I('link_name');
		$link_tel = I('link_tel');
		if(!empty($operate_id)){
			$where['lm_operate_total_price.operate_id'] = $operate_id;
		}
		if(!empty($operate_name)){
			$where['lm_operate_total_price.operate_name'] = array('like','%'.$operate_name.'%');
		}
		if(!empty($link_name)){
			$where['lm_operate_center.link_name'] = array('like',"%link_name%");
		}
		if(!empty($link_tel)){
			$where['lm_operate_center.link_tel'] = array('like',"%link_tel%");
		}    
		$lists = $m->where($where)->group('lm_operate_total_price.operate_id')->join('LEFT JOIN lm_operate_center ON lm_operate_center.id=lm_operate_total_price.operate_id')->field('sum(lm_operate_total_price.value) total,lm_operate_total_price.operate_id,lm_operate_total_price.month,lm_operate_total_price.operate_name,lm_operate_center.link_name,lm_operate_center.link_tel,lm_operate_center.operate_sn')->select();
		foreach($lists as $k=>$list){
			$w = array();
			$w['operate_id'] = $list['operate_id'];
			$w['is_get'] = 1;  
			$w2 = array();
			$w2['operate_id'] = $list['operate_id'];  
			$get_money = $m->where($w)->sum('value');
			$lists[$k]['get_money'] = empty($get_money) ? 0 : $get_money;
			$all_money = $m->where($w2)->sum('value');
			$lists[$k]['all_money'] = empty($all_money) ? 0 : $all_money;	
		
		}
		$this->assign('lists',$lists);
		
	   	$ui['according_name'] = 'active';
        $this->assign('ui',$ui);
        $this->display('according_name');  
    }
	/*运营商充值明细*/
    public function recharge_detail()
    {
		$trade_record = M('operate_trade_record');
		$info = array();
		$w  = array();
		$w['lm_operate_trade_record.status'] = 1;
		$operate_name = I('operate_name');
		if(!empty($operate_name)){
			$w['lm_operate_center.operate_name'] = array('like',"%$operate_name%");
		}
		$order_sn = I('order_sn');
		if(!empty($order_sn)){
			$w['lm_operate_trade_record.order_sn'] = array('like',"%$order_sn%");
		}
		$type = I('type');
		if(!empty($type)){
			$w['lm_operate_trade_record.type'] = $type;
		}
		$Time1 = I('Time1');
		$Time2 = I('Time2');  
		if(!empty($Time1) && empty($Time2))  
		{
			$t = strtotime($Time1)+24*60*60 ;
			$w['_string']= "lm_operate_trade_record.addtime >= '".strtotime($Time1) ."'&& lm_operate_trade_record.addtime < '".$t."'";
		}         
		if(!empty($Time2) && empty($Time1))
		{  
			$w['_string']= "lm_operate_trade_record.addtime<= '". strtotime($Time2)."'";  
		}  
		if(!empty($Time2) && !empty($Time1))
		{   
			$t = strtotime($Time2)+24*60*60 ;
			$w['_string']= "lm_operate_trade_record.addtime >= '".strtotime($Time1) ."'&& lm_operate_trade_record.addtime < '" .$t."'";  
		}    
		$count = $trade_record->join('LEFT JOIN lm_operate_center ON lm_operate_center.id = lm_operate_trade_record.operate_id')->where($w)->count();
		$info['count'] = $count;
		$Page = new \Think\Page($count, 15); 
        $show = $Page->show();  
		$lists = $trade_record->join('LEFT JOIN lm_operate_center ON lm_operate_center.id = lm_operate_trade_record.operate_id')->where($w)->order('lm_operate_trade_record.addtime DESC,lm_operate_trade_record.id DESC')->field('lm_operate_trade_record.*,lm_operate_center.operate_name')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('page',$show);   
		$this->assign('lists',$lists);
		
		if(empty($type)){
			$w['lm_operate_trade_record.type'] = 1;
			$type1_value = $trade_record->join('LEFT JOIN lm_operate_center ON lm_operate_center.id = lm_operate_trade_record.operate_id')->where($w)->sum('value');
			$w['lm_operate_trade_record.type'] = 2;
			$type2_value = $trade_record->join('LEFT JOIN lm_operate_center ON lm_operate_center.id = lm_operate_trade_record.operate_id')->where($w)->sum('value');
		}else if($type == 1){
			$type1_value = $trade_record->join('LEFT JOIN lm_operate_center ON lm_operate_center.id = lm_operate_trade_record.operate_id')->where($w)->sum('value');
			$type2_value = 0;
		}else if($type == 2){
			$type1_value  = 0;
			$type2_value = $trade_record->join('LEFT JOIN lm_operate_center ON lm_operate_center.id = lm_operate_trade_record.operate_id')->where($w)->sum('value');
		}
		$info['type1_value'] = empty($type1_value) ? '0.00' : $type1_value;
		$info['type2_value'] = empty($type2_value) ? '0.00' : $type2_value;
		   
		
		$w2 = array();
		$w2['status'] = 1;
		$w2['type'] = 1;
		$recharge = $trade_record->where($w2)->sum('value');
		$recharge = empty($recharge) ? '0.00' : $recharge;
		$w3 = array();
		$w3['status'] = 1;
		$w3['type'] = 2;
		$used = $trade_record->where($w3)->sum('value');
		$used = empty($used) ? '0.00' : $used;
		$unused = floatval($recharge)+floatval($used);
		
		$info['recharge'] = $recharge;
		$info['unused'] = $unused;
		$this->assign('info',$info);
	   	$ui['recharge_detail'] = 'active';
        $this->assign('ui',$ui);
        $this->display('recharge_detail');  
    } 
	
   
	
	public function PayMoney(){
		$m = M('member_wallet_record');
		$w = array();  
		$w['lm_member_wallet_record.type'] = 2;
		$Time1 = I('Time1');
		$Time2 = I('Time2');
		$nickname = I('nickname');
		$ispay = I('ispay');
		$style = I('style');
		$member_name = I('member_name');
		if(!empty($member_name)){
			$w['lm_members.member_name'] = array('like','%'.$member_name.'%');
		}
		if(!empty($Time1) && empty($Time2))  
		{
			$t = strtotime($Time1)+24*60*60 ;
			$w['_string']= "lm_member_wallet_record.addtime >= '".strtotime($Time1) ."'&&lm_member_wallet_record.addtime < '".$t."'";
		}         
		if(!empty($Time2) && empty($Time1))
		{  
			$w['_string']= "lm_member_wallet_record.addtime<= '". strtotime($Time2)."'";  
		}  
		if(!empty($Time2) && !empty($Time1))  
		{   
			$t = strtotime($Time2)+24*60*60 ;
			$w['_string']= " lm_member_wallet_record.addtime >= '".strtotime($Time1) ."'&&lm_member_wallet_record.addtime < '" .$t."'";  
		}  
		if(!empty($nickname)){    
			$w['lm_members.member_nickname'] = array('like','%'.$nickname.'%');
		}
		if(!empty($ispay)){     
			if($ispay == '1'){
				$w['lm_member_wallet_record.prices'] = array('egt',0);
			}else{
				$w['lm_member_wallet_record.prices'] = array('lt',0);
			}
		}  
		if(!empty($style)){  
			if($style == '1'){
				$w['lm_member_wallet_record.description'] = array('like',"%新增贡献值分红%");
			}elseif($style == '2'){
				$w['lm_member_wallet_record.description'] = array('like',"%历史贡献值分红%");
			}elseif($style == '3'){
				$w['lm_member_wallet_record.description'] = array('like',"%发展%");
			}elseif($style == '4'){
				$w['lm_member_wallet_record.description'] = array('like',"%直推%");
			}elseif($style == '5'){
				$w['lm_member_wallet_record.description'] = array('like',"%提现%");
			}       
		}
		
		$count = $m->join('LEFT JOIN lm_members on lm_members.member_id=lm_member_wallet_record.member_id')->where($w)->count(); // 查询满足要求的总记录数  
		$countmoney = $m->join('LEFT JOIN lm_members on lm_members.member_id=lm_member_wallet_record.member_id')->where($w)->sum('prices');    
        $Page = new \Think\Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show(); // 分页显示输出 
		$lists = $m->join('LEFT JOIN lm_members on lm_members.member_id=lm_member_wallet_record.member_id')->where($w)->field('lm_member_wallet_record.*,lm_members.member_nickname,lm_members.member_name')->order('lm_member_wallet_record.addtime DESC')-> limit($Page->firstRow . ',' . $Page->listRows)->select();   
		$ui['pay_money'] = 'active'; 
		$this->assign('Time1',$Time1);
    	$this->assign('Time2',$Time2); 	 	
    	$this->assign('nickname',$nickname); 	    	
        $this->assign('ui', $ui);  
		$this->assign('count',$count);
		$this->assign('countmoney',$countmoney);
		$this->assign('page',$show); // 赋值分页输出
		$this->assign('lists',$lists);
		$this->display("Fund:paymoney_list");     
		  
	}
    
	public function PVRecord(){
		$m = M('member_wallet_record');
		$w = array();  
		$w['lm_member_wallet_record.type'] = 1;
		$Time1 = I('Time1');
		$Time2 = I('Time2');
		$nickname = I('nickname');
		if(!empty($Time1) && empty($Time2))  
		{
			$t = strtotime($Time1)+24*60*60 ;
			$w['_string']= "lm_member_wallet_record.addtime >= '".strtotime($Time1) ."'&&lm_member_wallet_record.addtime < '".$t."'";
		}         
		if(!empty($Time2) && empty($Time1))
		{  
			$w['_string']= "lm_member_wallet_record.addtime<= '". strtotime($Time2)."'";  
		}  
		if(!empty($Time2) && !empty($Time1))  
		{   
			$t = strtotime($Time2)+24*60*60 ;
			$w['_string']= " lm_member_wallet_record.addtime >= '".strtotime($Time1) ."'&&lm_member_wallet_record.addtime < '" .$t."'";  
		}  
		if(!empty($nickname)){    
			$w['lm_members.member_nickname'] = array('like','%'.$nickname.'%');
		}		  
		$count = $m->join('LEFT JOIN lm_members on lm_members.member_id=lm_member_wallet_record.member_id')->where($w)->count(); // 查询满足要求的总记录数  
		$countmoney = $m->join('LEFT JOIN lm_members on lm_members.member_id=lm_member_wallet_record.member_id')->where($w)->sum('prices');    
        $Page = new \Think\Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show(); // 分页显示输出 
		$lists = $m->join('LEFT JOIN lm_members on lm_members.member_id=lm_member_wallet_record.member_id')->where($w)->field('lm_member_wallet_record.*,lm_members.member_nickname')->order('lm_member_wallet_record.addtime DESC')-> limit($Page->firstRow . ',' . $Page->listRows)->select();   
		$ui['pv_record'] = 'active'; 
		$this->assign('Time1',$Time1);
    	$this->assign('Time2',$Time2); 	 	
    	$this->assign('nickname',$nickname); 	    	
        $this->assign('ui', $ui);  
		$this->assign('count',$count);
		$this->assign('countmoney',$countmoney);
		$this->assign('page',$show); // 赋值分页输出
		$this->assign('lists',$lists);  
		$this->display("Fund:pv_list");     
		  
	}
	
	public function ContributeRecord(){
		$m = M('contribute_record');
		$w = array();  
		$w['lm_contribute_record.status'] = 1;
		$Time1 = I('Time1');
		$Time2 = I('Time2');
		$nickname = I('nickname');
		$member_name = I('member_name');
		if(!empty($member_name)){
			$w['lm_members.member_name'] = array('like','%'.$member_name.'%');
		}
		if(!empty($Time1) && empty($Time2))  
		{
			$t = strtotime($Time1)+24*60*60 ;
			$w['_string']= "lm_contribute_record.addtime >= '".strtotime($Time1) ."'&&lm_contribute_record.addtime < '".$t."'";
		}         
		if(!empty($Time2) && empty($Time1))
		{  
			$w['_string']= "lm_contribute_record.addtime<= '". strtotime($Time2)."'";  
		}  
		if(!empty($Time2) && !empty($Time1))  
		{   
			$t = strtotime($Time2)+24*60*60 ;
			$w['_string']= " lm_contribute_record.addtime >= '".strtotime($Time1) ."'&&lm_contribute_record.addtime < '" .$t."'";  
		}  
		if(!empty($nickname)){    
			$w['lm_members.member_nickname'] = array('like','%'.$nickname.'%');
		}		  
		$count = $m->join('LEFT JOIN lm_members on lm_members.member_id=lm_contribute_record.member_id')->where($w)->count(); // 查询满足要求的总记录数  
		$countvalue = $m->join('LEFT JOIN lm_members on lm_members.member_id=lm_contribute_record.member_id')->where($w)->sum('lm_contribute_record.value');  	
        $Page = new \Think\Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show(); // 分页显示输出 
		$lists = $m->join('LEFT JOIN lm_members on lm_members.member_id=lm_contribute_record.member_id')->where($w)->field('lm_contribute_record.*,lm_members.member_nickname,lm_members.member_name')->order('lm_contribute_record.addtime DESC')-> limit($Page->firstRow . ',' . $Page->listRows)->select();    
		$ui['contribute_record'] = 'active'; 
		$this->assign('Time1',$Time1);
    	$this->assign('Time2',$Time2); 	 	
    	$this->assign('nickname',$nickname); 	    	
        $this->assign('ui', $ui);  
		$this->assign('count',$count);  
		$this->assign('countvalue',$countvalue);
		$this->assign('page',$show); // 赋值分页输出
		$this->assign('lists',$lists);  
		$this->display("Fund:contribute_list");  
	}
	 
	public function Drwamoney_record(){
		$m = M('drawmoney_record');
		$where =  array();
		$where['1'] = '1';    
		$Time1 = I('Time1');
		$Time2 = I('Time2');
		$nickname = I('nickname');
		$drawtype = I('drawtype');
		$drawstatus = I('drawstatus');
		$member_name = I('member_name');
		if(!empty($member_name)){
			$where['lm_members.member_name'] = array('like','%'.$member_name.'%');
		}
		if(!empty($Time1) && empty($Time2))  
		{
			$t = strtotime($Time1)+24*60*60 ;
			$where['_string']= "lm_drawmoney_record.addtime >= '".strtotime($Time1) ."'&&lm_drawmoney_record.addtime < '".$t."'";
		}         
		if(!empty($Time2) && empty($Time1))
		{  
			$where['_string']= "lm_drawmoney_record.addtime<= '". strtotime($Time2)."'";  
		}  
		if(!empty($Time2) && !empty($Time1))  
		{       
			$t = strtotime($Time2)+24*60*60 ;
			$where['_string']= " lm_drawmoney_record.addtime >= '".strtotime($Time1) ."'&&lm_drawmoney_record.addtime < '" .$t."'";  
		}
		$w = array();
		if(!empty($nickname)){    
			$w['_string'] = "lm_members.member_name like '%'".$nickname."'% OR lm_members.member_nickname like '%".$nickname."%')";
		}
		if(!empty($w)){
			$where['_complex'] = $w;
		}
		if($drawtype !=''){
			$where['type'] = $drawtype;
		}
		if($drawstatus !=''){
			$where['status'] = $drawstatus;
		}  	  
		$count =$m->join('LEFT JOIN lm_members ON lm_members.member_id = lm_drawmoney_record.member_id')->where($where)->field('lm_drawmoney_record.*,lm_members.member_name,lm_members.member_nickname')->count();
		$Page = new \Think\Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show(); // 分页显示输出 
		$lists = $m->join('LEFT JOIN lm_members ON lm_members.member_id = lm_drawmoney_record.member_id')->field('lm_drawmoney_record.*,lm_members.member_name,lm_members.member_nickname,lm_members.member_name')->where($where)->order('id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$totalprice = $m->join('LEFT JOIN lm_members ON lm_members.member_id = lm_drawmoney_record.member_id')->sum('lm_drawmoney_record.drawmoney');
		$totalprice = empty($totalprice) ? 0 : $totalprice;
		$this->assign('totalprice',$totalprice);
		$this->assign('lists',$lists);	
		$this->assign('page',$show); // 赋值分页输出   
		$ui['drawmoney'] = 'active'; 
		$this->assign('ui', $ui); 
		$this->display('drawmoney_list');
	}
	 
	public function confirm_drawmoney(){ 
		$rt = array();
		$str = I('str');  
		$where = array(); 
		$where['lm_drawmoney_record.id'] = array('in',$str);
		$drawinfo = M('drawmoney_record')->join('LEFT JOIN lm_members ON lm_members.member_id = lm_drawmoney_record.member_id')->field('lm_drawmoney_record.*,lm_members.wx_openid')->where($where)->order('id DESC')->select();
		$desc = '';
		if(!empty($drawinfo)){
			$bank_check = I('bank_check');
			if(!empty($bank_check)){
				$password = I('password');
				$login_name = session('loginname');
				$pass = M('admin')->where(array('loginname'=>$login_name))->getField('password');
				if(md5($password) !=$pass){
					$desc = "登录密码错误，不能进行该操作";
				}else{
					foreach($drawinfo as $draw){
						if($draw['status'] == 1){
							$desc = $desc.'编号：'.$draw['id'].'--该提现申请已经完成'."\n";
						}else{
							\Think\Log::write('管理员:'.session('loginname') .' 同意订单号为'.$draw['order_sn'].'的提款申请，提现金额：'.$draw['drawmoney'].'--提款成功','INFO');
							A('Admin')->addAdminLog(8,'通过订单号为'.$draw['order_sn'].'的提款申请，提现金额：'.$draw['drawmoney']."(线下提款)");
							$news = array();  
							$news['status'] = 1; 
							$news['type'] = 2;       
							$news['confirm_time'] = mktime();
							M('drawmoney_record')->where(array('id'=>$draw['id']))->save($news); 
							M('member_wallet_record')->where(array('member_id'=>$draw['member_id'],'source_order'=>$draw['order_sn']))->save(array('description'=>'提现（提现成功）'));	
						}							
					}
				}
			}
			else{
				$wxpayconfig = M('wxpayconfig')->where(array('status'=>1))->find();
				define('WxPayConfig_APPID', $wxpayconfig['appid']);
				define('WxPayConfig_MCHID', $wxpayconfig['mchid']);
				define('WxPayConfig_KEY', $wxpayconfig['key']);
				define('WxPayConfig_APPSECRET', $wxpayconfig['appsecret']);
				define('WxPayConfig_SSLCERT_PATH', $wxpayconfig['sslcert_path']);
				define('WxPayConfig_SSLKEY_PATH', $wxpayconfig['sslkey_path']);
				define('WxPayConfig_ROOTCA_PATH', $wxpayconfig['rootca_path']);
				foreach($drawinfo as $draw){ 
					if(empty($draw['wx_openid'])){  
						$desc = $desc.'编号：'.$draw['id'].'--该用户不支持微信付款'."\n";
					}else if($draw['status'] == 1){
						$desc = $desc.'编号：'.$draw['id'].'--该提现申请已经完成'."\n";
					}else{    	
						$input = new \WxPayCompanyQuery();
						$input->SetPartner_trade_no($draw['order_sn']);
						$data = \WxpayApi::companyPayQuery($input);
						if(($data['return_code']=='SUCCESS')&&($data['result_code']=='SUCCESS')){
							if(($data['status']=='SUCCESS')||($data['status']=='PROCESSING')) //说明该申请已通过
							{
								\Think\Log::write('管理员:'.session('loginname') .' 通过查询微信支付状态，把订单号为:'.$draw['order_sn'].'的提款申请变为已提款成功','INFO');	
								$news = array();        
								$news['status'] = 1;
								$news['confirm_time'] = mktime();
								M('drawmoney_record')->where(array('id'=>$draw['id']))->save($news); 
								M('member_wallet_record')->where(array('member_id'=>$draw['member_id'],'source_order'=>$draw['order_sn']))->save(array('description'=>'提现（提现成功）'));	
							
							} 
						}  
						elseif(($data['return_code']=='SUCCESS')&&($data['err_code']=='NOT_FOUND')){
						
							$amount = round(($draw['drawmoney']-$draw['fee']),2)*100; 
							$input2 = new \WxPayCompany();
							$input2->SetPartner_trade_no($draw['order_sn']);
							$input2->SetAmount($amount);
							$input2->SetCheck_name("NO_CHECK");
							$input2->SetDesc('微信提款');
							$input2->SetOpenid($draw['wx_openid']);  
							$data2 = \WxpayApi::companyPay($input2);
							F('data',$data2);
							if($data2['return_code']=='SUCCESS'&&$data2['result_code']=='SUCCESS'){
								\Think\Log::write('管理员:'.session('loginname') .' 同意订单号为'.$draw['order_sn'].'的提款申请，提现金额：'.$draw['drawmoney'].'--提款成功','INFO');
								A('Admin')->addAdminLog(8,'通过订单号为'.$draw['order_sn'].'的提款申请，提现金额：'.$draw['drawmoney']."(微信提款)");
								$news = array();  
								$news['status'] = 1; 
								$news['confirm_time'] = mktime();
								M('drawmoney_record')->where(array('id'=>$draw['id']))->save($news); 
								M('member_wallet_record')->where(array('member_id'=>$draw['member_id'],'source_order'=>$draw['order_sn']))->save(array('description'=>'提现（提现成功）'));	
							}else{
								$desc = $desc.'编号：'.$draw['id'].'--'.$data2['err_code_des']."\n";
								\Think\Log::write('管理员:'.session('loginname') .' 同意订单号为'.$draw['order_sn'].'的提款申请，提现金额：'.$draw['drawmoney'].'--提款失败：'.$data2['return_msg'],'INFO');
							}       
						}         
						else{
							//F('data',$data);   
							$desc = $desc.'编号：'.$draw['id'].'--'.$data['return_msg']."\n";
						}	
						
					}  
				}
			}	
			if($desc == ''){
				$rt['result'] = 1;  
			}
			else{  
				$rt['result'] = -1; 
			 	$rt['desc'] = $desc;  
			}
			 	        
		}else{       
			$rt['result'] = -1;
			$rt['desc'] = '所传参数不能为空';
		}  
		echo json_encode($rt,JSON_UNESCAPED_UNICODE);
	}
	
	/*所有运营商每月分润明细*/
	public function  all_operate_month_price_record(){
		$showtype = I('showtype',1);
		$this->assign('showtype',$showtype);
		$m = M('operate_total_price');
		$info = array();
		$info['total'] = $m->sum('value');
		$info['getmoney'] = $m->where(array('is_get'=>1))->sum('value');
		$this->assign('info',$info);
		/*按月查看*/
		if($showtype== '1'){
			$lists = $m->group('month')->field('sum(value) total,operate_id,month,operate_name')->select();
		}else{  //按运营商查看
			$where = array();	
			$operate_id = I('operate_id');
			$operate_name = I('operate_name');
			$operate_info = I('operate_info');
			if(!empty($operate_id)){
				$where['lm_operate_total_price.operate_id'] = $operate_id;
				$this->assign('operate_id',$operate_id);
			}
			if(!empty($operate_name)){
				$where['lm_operate_total_price.operate_name'] = array('like','%'.$operate_name.'%');
				$this->assign('operate_name',$operate_name);
			}
			if(!empty($operate_info)){
				$where['_string'] = "lm_operate_center.link_name like '%".$operate_info."'% OR lm_operate_center.link_tel like '%".$operate_info."'%";
				$this->assign('operate_info',$operate_info);
			}   
			$lists = $m->where($where)->group('lm_operate_total_price.operate_id')->join('LEFT JOIN lm_operate_center ON lm_operate_center.id=lm_operate_total_price.operate_id')->field('sum(lm_operate_total_price.value) total,lm_operate_total_price.operate_id,lm_operate_total_price.month,lm_operate_total_price.operate_name,lm_operate_center.link_name,lm_operate_center.link_tel')->select();
		}        
		foreach($lists as $k=>$list){
			if($showtype == 1){
				$w = array();
				$w['month'] = $list['month'];
				$w['is_get'] = 1;
				$w2 = array();
				$w2['month'] = $list['month'];
				$w2['is_get'] = 0;	
				$lists[$k]['Time1'] = $list['month'].'-01';
				$t = strtotime($lists[$k]['Time1']);
				$lists[$k]['Time2'] = date('Y-m-d',mktime(0,0,0,date('m',$t)+1,1,date('Y',$t)));
			}else{   
				$w = array();
				$w['operate_id'] = $list['operate_id'];
				$w['is_get'] = 1;
				$w2 = array();
				$w2['operate_id'] = $list['operate_id'];
				$w2['is_get'] = 0;	   
			}  
			$get_money = $m->where($w)->sum('value');
			$lists[$k]['get_money'] = empty($get_money) ? 0 : $get_money;
			$unget_money = $m->where($w2)->sum('value');
			$lists[$k]['unget_money'] = empty($unget_money) ? 0 : $unget_money;
			if($showtype == 1){
				$lists[$k]['operate_num'] = M('operate_center')->where(array('status'=>1))->count();
			}
			
		}	
	
		$ui['operate_money'] = 'active';
		$this->assign('ui',$ui);  
		$this->assign('lists',$lists);  
		$this->display('all_operate_month_price_record');
	} 
	
	/*所有运营商单月分润明细*/
	public function  all_operate_onemonth_price_record(){
		$month = I('month');
		$m = M('operate_total_price');
		$center = M('operate_center');
		$w = array();  
		if(!empty($month)){
			$w['month'] = $month;
			$t = explode('-',$month);
			$this->assign('y',$t[0]);
			$this->assign('m',$t[1]);
		}else{  
			$yd = I('yd'); 
			$md = I('md');  
			$w['month'] = $yd.'-'.$md;	
			$this->assign('y',$yd);
			$this->assign('m',$md);
			
		}  		
		$info = array();
		$info['total'] = $m->where($w)->sum('value');
		$info['total'] =empty($info['total']) ? 0 : $info['total'] ;
		$w2 = $w;
		$w2['is_get'] = 1;     
		$info['getmoney'] = $m->where($w)->sum('value');
		$info['getmoney'] =empty($info['getmoney']) ? 0 : $info['getmoney'] ;
		$is_get = I('is_get');
		if(!empty($is_get)){
			$w['is_get'] = ($is_get == '1') ? 1 : 0;
		}  
	
		$operate_name = I('operate_name');
		if(!empty($operate_name)){    
			$w['operate_name'] = array('like','%'.$operate_name.'%');
		}
		$lists = $m->where($w)->select();
		foreach($lists as $k=>$list){
			$operateinfo = $center->where(array('id'=>$list['operate_id']))->find();
			$lists[$k]['bank_name'] = $operateinfo['bank_name'];
			$lists[$k]['bank_username'] = $operateinfo['bank_username'];
			$lists[$k]['bank_sn'] = $operateinfo['bank_sn'];
		}
	 
		$this->assign('info',$info);
		$ui['operate_money'] = 'active';  
		$this->assign('ui',$ui);  
		$this->assign('lists',$lists);     
		$this->display('all_operate_onemonth_price_record');
	}
	
	
	/* 单个运营商每月分润明细 (按运营商查看)*/
	public function operate_month_price_record(){
		$operate_id = I('param.operate_id');
		$lists = array();
		if(!empty($operate_id)){
			$center = M('operate_center');
			$w = array();
			$w['id'] = $operate_id;
			$w['status'] = 1;
			$operate_info = $center->where($w)->find();
			$operate_total_price = M('operate_total_price');
			$w2 = array();
			$w2['operate_id'] = $operate_id;
			$lists = $operate_total_price->where($w2)->order('month_time DESC')->select();
			$operate_info['all_price'] = $operate_total_price->where($w2)->sum('value');
			$operate_info['all_price'] = empty($operate_info['all_price']) ? 0 : $operate_info['all_price'];   
			$w3 = array();
			$w3['operate_id'] = $operate_id;
			$w3['is_get'] = 1; 
			$operate_info['get_price'] = $operate_total_price->where($w3)->sum('value');
			$operate_info['get_price'] = empty($operate_info['get_price']) ? 0 : $operate_info['get_price'];
			if (I('get.act') == 'export'){
				$this->exportExcel($lists,$operate_info);
			}
			$this->assign('operate_info',$operate_info);
			$this->assign('lists',$lists);  
		}        
		$ui['operate_money'] = 'active';   
		$this->assign('ui',$ui); 
		$this->display('operate_month_price_record');
	}

	/**
	 * hjun
	 * 2017-03-28 13:50:11
	 * 导出excel
	 * @param $list
	 * @param $operate_info
	 */
	private function exportExcel($list,$operate_info){
		vendor("PHPExcel.PHPExcel");
		$excel = new \PHPExcel();

		// 设置第二行 合并第二行单元格
		$x = 2;
		$excel->getActiveSheet()->mergeCells("B$x:H$x");
		$excel->getActiveSheet()->setCellValue("B$x","运营商名称：".$operate_info['operate_name']);
		$excel->getActiveSheet()->getStyle("B$x:H$x")->applyFromArray(array('font' => array ('bold' => true )));

		// 设置标题
		$excel->getActiveSheet()->setCellValue("B".($x+1),"联系人");
		$excel->getActiveSheet()->setCellValue("C".($x+1),"手机号");
		$excel->getActiveSheet()->setCellValue("D".($x+1),"开户银行");
		$excel->getActiveSheet()->setCellValue("E".($x+1),"收款名称");
		$excel->getActiveSheet()->setCellValue("F".($x+1),"收款名称");
		$excel->getActiveSheet()->setCellValue("G".($x+1),"总分润金额");
		$excel->getActiveSheet()->setCellValue("H".($x+1),"已打款金额");
		// 设置值
		$excel->getActiveSheet()->setCellValue("B".($x+2),$operate_info['link_name']);
		$excel->getActiveSheet()->setCellValue("C".($x+2),$operate_info['link_tel']);
		$excel->getActiveSheet()->setCellValue("D".($x+2),$operate_info['bank_name']);
		$excel->getActiveSheet()->setCellValue("E".($x+2),$operate_info['bank_username']);
		$excel->getActiveSheet()->setCellValue("F".($x+2),$operate_info['bank_sn']);
		$excel->getActiveSheet()->setCellValue("G".($x+2),$operate_info['all_price']);
		$excel->getActiveSheet()->setCellValue("H".($x+2),$operate_info['get_price']);

		// 设置第五行合并
		$excel->getActiveSheet()->mergeCells("B".($x+3).":H".($x+3));
		// 设置标题
		$excel->getActiveSheet()->mergeCells("B".($x+4).":C".($x+4));
		$excel->getActiveSheet()->setCellValue("B".($x+4),"月份");
		$excel->getActiveSheet()->mergeCells("D".($x+4).":E".($x+4));
		$excel->getActiveSheet()->setCellValue("D".($x+4),"利润分红：");
		$excel->getActiveSheet()->mergeCells("F".($x+4).":H".($x+4));
		$excel->getActiveSheet()->setCellValue("F".($x+4),"款项状态");
		$excel->getActiveSheet()->getStyle("B".($x+4).":H".($x+4))->applyFromArray(array('font' => array ('bold' => true )));
		// 设置值
		foreach ($list as $key=>$value){
			if ($value['is_get'] == 0){
				$list[$key]['is_get'] = '未打款';
			}else {
				$list[$key]['is_get'] = date('Y-m-d H:i:s',$value['get_time']).' 已打款';
			}
			$excel->getActiveSheet()->mergeCells("B".($key+$x+5).":C".($key+$x+5));
			$excel->getActiveSheet()->mergeCells("D".($key+$x+5).":E".($key+$x+5));
			$excel->getActiveSheet()->mergeCells("F".($key+$x+5).":H".($key+$x+5));
			$excel->getActiveSheet()->setCellValue("B".($key+$x+5),$value['month']);
			$excel->getActiveSheet()->setCellValue("D".($key+$x+5),$value['value']);
			$excel->getActiveSheet()->setCellValue("F".($key+$x+5),$list[$key]['is_get']);
		}

		// 设置列的宽度
		$excel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
		$excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
		$excel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
		$excel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
		$excel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
		$excel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
		$excel->getActiveSheet()->getColumnDimension('H')->setWidth(25);

		// 设置对齐方式
		$excel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$num = count($list);
		$excel->getActiveSheet()->getStyle("B".($x+1).":H".($x+4+$num))->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		// 设置边框
		$styleThinBlackBorderOutline = array(
			'borders' => array (
				'allborders' => array(
					'style' => \PHPExcel_Style_Border::BORDER_THIN,//内边框细
				),
				'outline'  => array (
					'style' => \PHPExcel_Style_Border::BORDER_THICK, // 外边框粗
				),
			),);
		$excel->getActiveSheet()->getStyle("B$x:H".($x+2))->applyFromArray($styleThinBlackBorderOutline);
		$excel->getActiveSheet()->getStyle("B".($x+4).":H".($x+4+$num))->applyFromArray($styleThinBlackBorderOutline);

		$name = '运营商分润详情_'.$operate_info['operate_name'].'.xls';
		$write = new \PHPExcel_Writer_Excel5($excel);
		ob_end_clean();//清除缓冲区,避免乱码
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");;
		header('Content-Disposition:attachment;filename='.$name);
		header("Content-Transfer-Encoding:binary");
		A('Admin')->addAdminLog(3,"导出运营商分润详情，运营商名称为：".$operate_info['operate_name']."，运营商id为：".$operate_info['id']);
		$write->save('php://output');
	}
	
	/* 运营商每日分润明细*/      
	public function operate_day_price_record(){
		$id = I('id');
		$w = array(); 
		if(!empty($id)){
			$record_info = M('operate_total_price')->where(array('id'=>$id))->find();
			$operate_id = $record_info['operate_id'];
			$w['periods'] = array('like',$record_info['month']."%");
			$operate_name = $record_info['operate_name'];
		}
		else{    
			$Time1 = I('Time1');
			$Time2 = I('Time2');
			$operate_name = I('operate_name');
			if(!empty($operate_name)){
				$w['operate_name'] = array('like','%'.$operate_name.'%');
			}
			$operate_sn = I('operate_sn');
			if(!empty($operate_sn)){ 
				$operate_id = M('operate_center')->where(array('operate_sn'=>$operate_sn))->getField('id');
				$operate_id = empty($operate_id) ? '0' : $operate_id;
			}
			if(!empty($Time1) && empty($Time2))  
			{
				$t = strtotime($Time1)+24*60*60 ;
				$w['_string']= " addtime >= '".strtotime($Time1) ."'&& addtime < '".$t."'";
			}          
			if(!empty($Time2) && empty($Time1))
			{  
				$w['_string']= " addtime<= '". strtotime($Time2)."'";  
			}  
			if(!empty($Time2) && !empty($Time1))  
			{       
				$t = strtotime($Time2)+24*60*60 ;
				$w['_string']= " addtime >= '".strtotime($Time1) ."'&& addtime < '" .$t."'";  
			}  
		} 		
		
		if($operate_id != '' ){
			$w['operate_id'] = $operate_id;
		} 
		$style = I('style');
		if(!empty($style)){
			$w['type'] = $style;
		}
		$lists = array();
		$record =M('operate_price_record');   
		$count = $record->where($w)->count();
		$Page = new \Think\Page($count, 15); 
        $show = $Page->show();  
		$lists =  $record->where($w)->field('operate_id,id,value,link_orderid,type,operate_name,value,addtime,periods,desc')->order('id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$m2 = M('operate_total_price');
		foreach($lists as $k=>$list){ 
			if($list['type'] == 2){
				$where = array();
				$where['operate_id'] = $list['operate_id'];
				$where['month'] = $list['periods'];
				$lists[$k]['payment_img'] = $m2->where($where2)->getField('payment_img');
			}
		}    	
   	
		$this->assign('lists',$lists);   
		$this->assign('Time1',$Time1); 
		$this->assign('Time2',$Time2);    
		$this->assign('operate_name',$operate_name); 
		$ui['operate_money'] = 'active';
		$this->assign('page',$show);
		$this->assign('ui',$ui); 
		$this->display('operate_day_price_record');
	}  
	/* 运营商单日分润详情*/      
	public function operate_day_price_detail(){
		$operate_id = I('operate_id');
		$time = I('time');
		$where = array();
		$where['operate_id'] = $operate_id;
		$where['periods'] = $time;
		$lists = M('operate_price_record')->where($where)->order('id DESC')->select();
		$this->assign('lists',$lists);
		$ui['operate_money'] = 'active';
		$this->assign('ui',$ui);     
		$this->display('operate_day_price_detail');
		
	}

	/*运营商所有股东每月分润明细*/
	public function  all_holder_month_price_record(){
		$operate_id = I('operate_id');
		$time = I('time'); 
		$where = array();
		$where['lm_operate_shareholder_total_price.operate_id'] = $operate_id;
		$where['lm_operate_shareholder_total_price.month'] = $time;
		$operate_shareholder_total_price = M('operate_shareholder_total_price');
		$lists = $operate_shareholder_total_price->join('LEFT JOIN lm_operate_shareholder ON lm_operate_shareholder.id=lm_operate_shareholder_total_price.shareholder_id')->where($where)->field('lm_operate_shareholder.*,lm_operate_shareholder_total_price.id mid,lm_operate_shareholder_total_price.value,lm_operate_shareholder_total_price.is_get,lm_operate_shareholder_total_price.get_time,lm_operate_shareholder_total_price.payment_img')->select();   
            
		$info = array();
		$info['month'] = $time;
		$info['total'] =$operate_shareholder_total_price->where($where)->sum('value');
		$info['status'] = M('operate_total_price')->where(array('operate_id'=>$operate_id,'month'=>$time))->getField('is_get');   
		$where['lm_operate_shareholder_total_price.is_get'] = 1;
		$get_price = $operate_shareholder_total_price->where($where)->sum('value');
		$info['get_price'] = empty($get_price) ? 0 : $get_price;
		$this->assign('info',$info);
		$ui['group_list'] = 'active';
		$this->assign('ui',$ui);  
		$this->assign('lists',$lists);
		$this->display('all_holder_month_price_record');
	} 
	   
	   
	/* 单个股东每月分润明细 (按股东查看)*/
	public function holder_month_price_record(){
		$shareholder_id = I('shareholder_id');
		$lists = array();
		if(!empty($shareholder_id)){
			$operate_shareholder = M('operate_shareholder');
			$w = array();
			$w['id'] = $shareholder_id;
			$w['status'] = 1;
			$shareholder_info = $operate_shareholder->where($w)->find();
			$shareholder_info['operate_name'] = M('operate_center')->where(array('id'=>$shareholder_info['operate_id']))->getField('operate_name');  
			$operate_shareholder_total_price = M('operate_shareholder_total_price');
			$w2 = array();
			$w2['shareholder_id'] = $shareholder_id;
			$lists = $operate_shareholder_total_price->where($w2)->order('month_time DESC')->select();
			foreach($lists as $k=>$list){
				$where = array();
				$where['operate_id'] = $list['operate_id'];
				$where['month'] = $list['month'];
				$lists[$k]['operate_get'] = M('operate_total_price')->where($where)->getField('is_get');
			}
			$shareholder_info['all_price'] = $operate_shareholder_total_price->where($w2)->sum('value');
			$shareholder_info['all_price'] = empty($shareholder_info['all_price']) ? 0 : $shareholder_info['all_price'];   
			$w3 = array();
			$w3['shareholder_id'] = $shareholder_id;
			$w3['is_get'] = 1; 
			$shareholder_info['get_price'] = $operate_shareholder_total_price->where($w3)->sum('value');
			$shareholder_info['get_price'] = empty($shareholder_info['get_price']) ? 0 : $shareholder_info['get_price'];
			$this->assign('shareholder_info',$shareholder_info);
			$this->assign('lists',$lists);  
		}           
		$ui['operate_list'] = 'active';  
		$this->assign('ui',$ui);     
		$this->display('holder_month_price_record');
	}
	   
	
	/* 股东每日分润明细*/   
	public function holder_day_price_record(){
		$id = I('id');
		$w = array();  
		if(!empty($id)){
			$record_info = M('operate_shareholder_total_price')->where(array('id'=>$id))->find();
			$shareholder_id = $record_info['shareholder_id'];
			$w['lm_operate_shareholder_price_record.periods'] = array('like',$record_info['month']."%");
			$shareholder_name = $record_info['shareholder_name'];
		}  
		else{    
			$Time1 = I('Time1');
			$Time2 = I('Time2');
			$shareholder_name = I('shareholder_name');
			if(!empty($shareholder_name)){
				$w['lm_operate_shareholder_price_record.shareholder_name'] = array('like','%'.$shareholder_name.'%');
			}
			$shareholder_sn = I('shareholder_sn');
			if(!empty($shareholder_sn)){
				$w['lm_operate_shareholder.shareholder_sn'] = $shareholder_sn;
			}
			$link_orderid = I('link_orderid');
			if(!empty($link_orderid)){
				$w['lm_operate_shareholder_price_record.link_orderid'] = $link_orderid;  
			}
			if(!empty($Time1) && empty($Time2))  
			{
				$t = strtotime($Time1)+24*60*60 ;
				$w['_string']= "lm_operate_shareholder_price_record.addtime >= '".strtotime($Time1) ."'&&lm_operate_shareholder_price_record.addtime < '".$t."'";
			}         
			if(!empty($Time2) && empty($Time1))
			{  
				$w['_string']= "lm_operate_shareholder_price_record.addtime<= '". strtotime($Time2)."'";  
			}  
			if(!empty($Time2) && !empty($Time1))  
			{       
				$t = strtotime($Time2)+24*60*60 ;
				$w['_string']= " lm_operate_shareholder_price_record.addtime >= '".strtotime($Time1) ."'&&lm_operate_shareholder_price_record.addtime < '" .$t."'";  
			}
		}
		
		$style = I('style');
		if(!empty($style)){
			$w['lm_operate_shareholder_price_record.type'] = $style;
		} 
		$lists = array();
		$record =M('operate_shareholder_price_record');   
		$lists =  $record->join('LEFT JOIN lm_operate_shareholder ON lm_operate_shareholder.id = lm_operate_shareholder_price_record.shareholder_id')->where($w)->field('lm_operate_shareholder_price_record.*,lm_operate_shareholder.shareholder_sn')->order('lm_operate_shareholder_price_record.id DESC')->select(); 
		$this->assign('lists',$lists);   
		$this->assign('Time1',$Time1);  
		$this->assign('Time2',$Time2);       
		$this->assign('shareholder_name',$shareholder_name); 
		$ui['operate_fund'] = 'active';
		$this->assign('ui',$ui);    
		$this->display('holder_day_price_record');
	}

	/*确认给运营商打款*/
	public function ajax_operate_confirm_get(){
		$id = I('id');  
		$img = I('img_url');
		$operate_total_price = M('operate_total_price');
		$w = array();
		$w['id'] = $id;
		$data = array();
		$info = $operate_total_price->where($w)->find();
		if(!file_get_contents($img)){
			$data['result'] = -1;
			$data['desc'] = '凭证未上传';  
		}     
		elseif($info['is_get'] == 1){
			$data['result'] = -1;
			$data['desc'] = '该笔记录已经打款过了';
		}
		elseif($info['month'] == date('Y-m')){  
			$data['result'] = -1;
			$data['desc'] = '现在暂不能打款该月的资金';
		}else{
			$check = $operate_total_price->where($w)->save(array('is_get'=>1,'get_time'=>mktime(),'payment_img'=>$img));
			if($check !== false){
				$da = array();
				$da['operate_id'] = $info['operate_id'];
				$da['operate_name'] = $info['operate_name'];
				$da['type'] = 2;   
				$da['value'] = 0-$info['value'];
				$da['desc'] = "打款".$info['month'].'月份分润';
				$da['pay_name'] = '线下支付';
				$da['periods'] = $info['month'];
				$da['addtime'] = mktime();   
				M('operate_price_record')->add($da);
				$data['result'] = 1;
			}else{
				$data['result'] = -1;
				$data['desc'] = '更新打款状态失败';
			}
			
		} 
		echo json_encode($data,JSON_UNESCAPED_UNICODE);
	}
    
	/*确认给股东打款*/
	public function ajax_holder_confirm_get(){
		$id = I('id');
		$img = I('img_url');
		$operate_shareholder_total_price = M('operate_shareholder_total_price');
		$w = array();   
		$w['id'] = $id; 
		$data = array();
		$info = $operate_shareholder_total_price->where($w)->find();
		if(!file_get_contents($img)){
			$data['result'] = -1;
			$data['desc'] = '凭证未上传';  
		}  
		elseif($info['is_get'] == 1){
			$data['result'] = -1;
			$data['desc'] = '该笔记录已经打款过了';
		}
		elseif($info['month'] == date('Y-m')){  
			$data['result'] = -1;
			$data['desc'] = '现在暂不能打款该月的资金';
		}else{
			$check = $operate_shareholder_total_price->where($w)->save(array('is_get'=>1,'get_time'=>mktime(),'payment_img'=>$img));
			if($check !== false){
				$da = array(); 
				$da['operate_id'] = $info['operate_id'];
				$da['operate_name'] = $info['operate_name'];
				$da['shareholder_id'] = $info['shareholder_id'];
				$da['shareholder_name'] = $info['shareholder_name'];
				$da['type'] = 3;     
				$da['value'] = 0-$info['value'];
				$da['desc'] = "打款".$info['month'].'月份分润';
				$da['pay_name'] = '线下支付';
				$da['periods'] = $info['month'];   
				$da['addtime'] = mktime();
				M('operate_shareholder_price_record')->add($da);
				$data['result'] = 1;   
			}else{  
				$data['result'] = -1;
				$data['desc'] = '更新打款状态失败';
			}
			   
		} 
		echo json_encode($data,JSON_UNESCAPED_UNICODE);
	}	
	
	
	
    public function upload_img()
    {
		
		$info = $this->upload();
		
        $file = $info['file'];  
		$type = $_GET['type'];   
		$rt = array();	
		if (!empty($file['name']))
		{
			$url = ADMIN_URL . '/images/'.$file['savepath'].$file['savename'];
			$rt['status']  =1 ;
			$rt['url'] = $url;	
			echo json_encode($rt, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}    
		else{  
			echo json_encode($rt, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}	 
    }
	
	 private function upload($files = '')
    {
        $upload = new \Think\Upload(); // 实例化上传类
        $upload->maxSize = 0; // 设置附件上传大小
        $upload->exts = array(
            'jpg',
            'gif',    
            'png',    
            'jpeg'
        ); // 设置附件上传类型 
        $upload->rootPath = './images/'; // 设置附件上传根目录
		//die($upload->roorPath);
        $upload->savePath = ''; // 设置附件上传（子）目录
        $upload->replace = true; // 上传文件 
        $info = $upload->upload($files);
	
        if (! $info) { // 上传错误提示错误信息 
            $this->error($upload->getError());
        } else { // 上传成功
                 // $this->success('上传成功！');
            return $info;
        }
    }
}   

?>

