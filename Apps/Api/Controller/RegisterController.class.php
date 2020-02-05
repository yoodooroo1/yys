<?php
namespace Api\Controller;
use Think\Controller;
class RegisterController extends Controller {
	public function __construct()
	{   
	    header("Content-Type: text/html;charset=utf-8");
		parent::__construct();
		$mall_db = $_GET['mall_db'];
		$auth_code = $_GET['auth_code'];
		$config = M('store_config');
		$where = array();
		$where['member_name'] = $mall_db;
		$where['auth_code'] = $auth_code;
		$check = $config->where($where)->find();
		if(empty($check)){ 
			$rt['result'] = -1;
			$rt['desc'] = '非法登录'; 
			echo json_encode($rt,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		    die;
		} 
	}  	
	/*用户购买套餐*/
    public function index(){
		header("Content-type: text/html; charset=utf-8");
		/*同步用户数据*/ 
		$sync_url = $this->getSyncUrl();
		file_get_contents($sync_url);	
		$result = array();
		$member_id = I('member_id');  
		if(empty($member_id)){ 
			$result['result'] = -1;
			$result['desc'] = '用户id不存在';
			echo json_encode($result,JSON_UNESCAPED_UNICODE);
			die; 
		}  
		$m = M('members');
		$where = array();
		$where['member_id'] = $member_id;
		$where['shield'] = 0;
		$info = $m->where($where)->find();
		if(empty($info)){
			$result['result'] = -1;
			$result['desc'] = '该用户不存在';
			echo json_encode($result,JSON_UNESCAPED_UNICODE);
			die;
		}
		
		$id_card = I('id_card');
		$tel = I('tel');
		$username = I('username');
		if(empty($tel) || empty($id_card)){
			$result['result'] = -1;
			$result['desc'] = '用户手机号或身份证号不能为空';
			echo json_encode($result,JSON_UNESCAPED_UNICODE);
			die;
		} 
		$packageid = I('packageid',0);
		/* if(($level !=1) && ($level !=2)){
			$result['result'] = -1;
			$result['desc'] = '用户等级错误';
			echo json_encode($result,JSON_UNESCAPED_UNICODE);
			die;  
		} */  
		$vip = M('vip_orders');
		$vw = array();
		$vw['member_id'] = $member_id;
		$rt = $vip->where($vw)->find();
		if(!empty($rt)){
			if($rt['status'] == 1){
				$result['result'] = -1;
				$result['desc'] = '该用户已经申请注册过了';
				echo json_encode($result,JSON_UNESCAPED_UNICODE);
				die;
			}
			elseif($rt['status'] == '0'){
				$vip->where($vw)->delete();
			}
		}   
		
		$packageinfo= M('package_list')->where(array('packageid'=>$packageid))->find();
		$paytype = I('paytype',1);
		if($paytype == 3){  //如果为余额支付
			$wallet = M('member_wallet');
			$w = array();
			$w['member_id'] = $member_id;
			$consume_money = $wallet->where($w)->getField('consume_money');
			$need_money = $packageinfo['price'];  
			if($consume_money < $need_money){
				$result['result'] = -1;
				$result['desc'] = '用户余额不足';  
				echo json_encode($result,JSON_UNESCAPED_UNICODE);
				die;
			}
			else{
				$wallet_record = M('member_wallet_record');   
				$params = array();
				$params['member_id'] = $member_id;  
				$params['type'] = '2';      //消费钱包
				$params['prices'] = 0-$need_money; 
				$params['periods'] = date('Y-m-d',mktime());;
				$params['description'] = '购买'.$packageinfo['name'];
				$params['addtime'] = mktime();
				$wallet_record->add($params);    
				$wallet->where(array("member_id"=>$member_id))->setDec('consume_money',$need_money);
			}  			   
		}
		   

		$data1 =array();
		$data1['truetel'] = I('truetel');
		$data1['truename'] = I('truename');
		$data1['id_card'] = $id_card;   
		$data1['member_address'] = I('address');
		$m->where(array('member_id'=>$member_id))->save($data1);
		$data = array();
		$data['member_id'] = $member_id;
		$data['orderSn'] = mktime().'_'.$member_id;  
		$data['packageid'] = $packageid;
		$data['content'] = I('content'); 
		$data['id_card'] = $id_card;
		$data['tel'] = $tel; 
		$data['username'] = $username; 
		$data['price'] = $packageinfo['price'];
		$data['recommend_name'] = I('recommend_name');  
		$data['province'] = I('province'); 
		$data['city'] = I('city'); 
		$data['area'] = I('area'); 
		$data['address'] = I('address'); 
		$data['up_level'] = $packageinfo['up_level'];    
		$data['paytype'] = $paytype; 
		$data['status'] = 0; 
		$data['applytime'] = mktime(); 
	    if($vip->add($data))
		{
			if($paytype == 3){
				$this->confirm($member_id);
			}    
			$result['result'] = 1;
			$result['orderSn'] = $data['orderSn'];
			$result['price']= $data['price']*100;
			echo json_encode($result,JSON_UNESCAPED_UNICODE);
		}
		else{
			$result['result'] = -1;
			$result['desc'] = '购买套餐失败';
			echo json_encode($result,JSON_UNESCAPED_UNICODE);
			die;
		}
    } 
	
	public function wxpay_confirm(){
		$ordersn = I('ordersn');
		$m = M('vip_orders'); 
		$result = array();
		$result['result'] = 1;     
		$member_id =$m->where(array('orderSn'=>$ordersn))->getField('member_id'); 
		$this->confirm($member_id);
		echo json_encode($result,JSON_UNESCAPED_UNICODE);
	}
 
	/*确定注册成为会员*/
	protected function confirm($member_id=''){
		//$member_id = I('member_id');   
		$m = M('vip_orders'); 
		$where = array();
		$where['member_id'] = $member_id;
		$where['status'] = array('neq',-1);
		$info =$m->where($where)->find();       
		if(empty($member_id) || empty($info)){
			$result = array();
			$result['result'] = -1;  
			$result['desc'] = '该用户未申请注册会员';
			echo json_encode($result,JSON_UNESCAPED_UNICODE);
			die;
		} 
		if($info['status'] == '1'){
			$result = array();
			$result['result'] = -1;
			$result['desc'] = '该用户已确认支付了';
			echo json_encode($result,JSON_UNESCAPED_UNICODE);
			die;
		} 
		/* if($info['type'] == 1){
			die('非法操作');
		} */  
		$packageinfo= M('package_list')->where(array('packageid'=>$info['packageid']))->find();
		$data = array(); 
		$data['status'] = 1; 
		$data['passtime'] = mktime();
		if($m->where($where)->save($data)){	
			/*更新会员等级和推荐人*/ 
			$member = M('members');
			$memberinfo = $member->where(array('member_id'=>$member_id))->find();
			/*更新推荐人，保存购买套餐id*/
			$recommend_id = $member->where(array('member_name'=>$info['recommend_name']))->getField('member_id');
			$rdata = array();
			$rdata['recommend_id'] = $recommend_id;
			$rdata['recommend_name'] = $info['recommend_name'];
			$rdata['package_id'] = $info['packageid'];   
			if($memberinfo['level']<$info['up_level']){
				$rdata['level'] = $info['up_level'];
			}
			$member->where(array("member_id"=>$member_id))->save($rdata);
			
			/*计算直推奖励*/
			/* if($info['up_level'] == 2){ */ 
				$sql ="SELECT tb2.member_name,tb2.member_id FROM lm_members AS tb1,lm_members AS tb2 WHERE tb1.recommend_name = tb2.member_name AND tb1.member_id=".$member_id;
				$p = M()->query($sql);
				$this->get_direct_push_money($p[0]);
			/* }  */   
			  
			$parents = $this->get_normal_parents($member_id);  
			$periods = date('Y-m-d',mktime());
			$level = M('member_level'); 
			$wallet_record = M('member_wallet_record');   
			$wallet = M('member_wallet');
			$lw = array();
			$lw['level_id'] = $info['up_level'];
			$lw['status'] = 1;
			/* $profit_share = $level->where($lw)->field('level_name,profit_share1,profit_share2,profit_share3')->find(); */
			$settlement_config = M('settlement_config');
			$sconfig = $settlement_config->where(array('status'=>1))->find();
			/*直接推荐人获得利润*/
			if(!empty($parents['p1'])){
				$money1 = $packageinfo['pv']* $packageinfo['percent1']/100;
				if($money1>0){
					$params1 = array();
					$params1['member_id'] = $parents['p1'];
					$params1['type'] = '2';      //消费钱包
					$params1['prices'] = $money1*$sconfig['award_shop_percent']/100;
					$params1['periods'] = $periods;
					$params1['source_order'] = $info['ordersn'];
					$params1['description'] = '发展'.$packageinfo['name'].'会员奖励（直接）';
					$params1['addtime'] = mktime();
					$wallet_record->add($params1);  
					$wallet->where(array("member_id"=>$parents['p1']))->setInc('consume_money',$money1*$sconfig['award_shop_percent']/100);
					  
					$params2 = array();
					$params2['member_id'] = $parents['p1'];
					$params2['type'] = '3';      //可提现钱包（现金钱包）
					$params2['prices'] = $money1*$sconfig['award_cash_percent']/100;
					$params2['source_order'] = $info['ordersn'];
					$params2['periods'] = $periods;
					$params2['description'] = '发展'.$packageinfo['name'].'会员奖励（直接）';
					$params2['addtime'] = mktime();
					$wallet_record->add($params2);   
					$wallet->where(array("member_id"=>$parents['p1']))->setInc('cash_money',$money1*$sconfig['award_cash_percent']/100);
				}   
			}
			/*间接推荐人获得利润*/
			if(!empty($parents['p2'])){
				$money2 =  $packageinfo['pv']* $packageinfo['percent2']/100;
				if($money2>0){
					$params3 = array();
					$params3['member_id'] = $parents['p2'];
					$params3['type'] = '2';      //消费钱包
					$params3['prices'] = $money2*$sconfig['award_shop_percent']/100;
					$params3['periods'] = $periods;
					$params3['source_order'] = $info['ordersn'];
					$params3['description'] ='发展'.$packageinfo['name'].'会员奖励（间接）';
					$params3['addtime'] = mktime();
					$wallet_record->add($params3);  
					$wallet->where(array("member_id"=>$parents['p2']))->setInc('consume_money',$money2*$sconfig['award_shop_percent']/100);
					$params4 = array();
					$params4['member_id'] = $parents['p2'];
					$params4['type'] = '3';      //可提现钱包（现金钱包）
					$params4['prices'] = $money2*$sconfig['award_cash_percent']/100;
					$params4['periods'] = $periods;
					$params4['source_order'] = $info['ordersn'];
					$params4['description'] = '发展'.$packageinfo['name'].'会员奖励（间接）';
					$params4['addtime'] = mktime();
					$wallet_record->add($params4);   
					$wallet->where(array("member_id"=>$parents['p2']))->setInc('cash_money',$money2*$sconfig['award_cash_percent']/100);
				}   
			}
			/*公益基金获得分红*/
			$money3 = $packageinfo['pv']* $sconfig['public_pv_percent']/100;
			if($money3 > 0){
				$params5 = array();
				$params5['type'] =1;
				$params5['style'] = '2';      
				$params5['link_orderid'] = $info['ordersn'];
				$params5['pay_name'] = ($info['paytype'] == 1) ? '线下支付' : (($info['paytype'] == 2) ? '微信支付' : '余额支付');  
				$params5['value'] = $money3;
				$params5['desc'] = '套餐分成【订单号：'.$info['ordersn'].'】';
				$params5['periods'] = $periods;
				$params5['addtime'] = mktime();  
				M('public_group_price_record')->add($params5);   
			}
			
			/*运营商分红*/
			$money4 = $packageinfo['pv']* $sconfig['operate_pv_percent']/100;
			if($money4 > 0){
				$operate_id = $memberinfo['operate_id'];
				$operate_name = M('operate_center')->where(array('id'=>$operate_id))->getField('operate_name');
				$data3 = array();
				$data3['operate_id'] = $operate_id ;
				$data3['link_orderid'] = $info['ordersn'];
				$data3['type'] = 2;
				$data3['pay_name'] = ($info['paytype'] == 1) ? '线下支付' : (($info['paytype'] == 2) ? '微信支付' : '余额支付'); 
				$data3['operate_name'] = $operate_name;
				$data3['value'] = $money4;
				$data3['desc'] = '套餐分成【订单号：'.$info['ordersn'].'】';
				$data3['periods'] = $periods;
				$data3['addtime'] = mktime();
				M('operate_price_record')->add($data3);
				$month =date('Y-m',mktime()); 
				$operate_total_price = M('operate_total_price');			
				$check1 = $operate_total_price->where(array('operate_id'=>$operate_id,'month'=>$month))->find();
				if(!empty($check1)){
					$operate_total_price->where(array('operate_id'=>$operate_id,'month'=>$month))->setInc('value', $money4);  
				}
				else{
					$totalda = array();
					$totalda['operate_id'] = $operate_id;
					$totalda['operate_name'] = $operate_name;
					$totalda['value'] = $money4;
					$totalda['month'] = $month;
					$totalda['month_time'] = strtotime($month);
					$operate_total_price->add($totalda);
				}
				/*运营商里股东分红*/	
				$shareholds = M('operate_shareholder')->where(array('operate_id'=>$operate_id,'status'=>1))->select();
			
				$operate_shareholder_price_record = M('operate_shareholder_price_record');
				$operate_shareholder_total_price = M('operate_shareholder_total_price');
				foreach($shareholds as $sharehold){
					$sharemoney = $money4*$sharehold['share_rate']/100;
					
					$sharedate = array();
					$sharedate['operate_id'] = $operate_id;
					$sharedate['operate_name'] = $operate_name;
					$sharedate['shareholder_id'] = $sharehold['id'];
					$sharedate['shareholder_name'] = $sharehold['shareholder_name'];
					$sharedate['type'] = '2';
					$sharedate['link_orderid'] = $info['ordersn'];
					$sharedate['pay_name'] = ($info['paytype'] == 1) ? '线下支付' : (($info['paytype'] == 2) ? '微信支付' : '余额支付');
					$sharedate['value'] = $sharemoney;
					$sharedate['desc'] = '套餐分成【订单号：'.$info['ordersn'].'】';
					$sharedate['periods'] = $periods;  
					$sharedate['addtime'] = mktime();
					         
					$operate_shareholder_price_record->add($sharedate);
					$check2 = $operate_shareholder_total_price->where(array('shareholder_id'=>$sharehold['id'],'month'=>$month))->find();
					if(!empty($check2)){
						$operate_shareholder_total_price->where(array('shareholder_id'=>$sharehold['id'],'month'=>$month))->setInc('value', $sharemoney);  
					}    
					else{   
						$totalda2 = array();
						$totalda2['operate_id'] = $operate_id;
						$totalda2['operate_name'] = $operate_name;
						$totalda2['shareholder_id'] = $sharehold['id'];
						$totalda2['shareholder_name'] = $sharehold['shareholder_name'];
						$totalda2['value'] = $sharemoney;   
						$totalda2['month'] = $month;
						$totalda2['month_time'] = strtotime($month);
						$operate_shareholder_total_price->add($totalda2);
					}
				}   
			}
			//$this->Up_parent_level($member_id);  
		}
		
	}  
	
	/*计算直推奖励*/
	protected  function get_direct_push_money($member){
		if(!empty($member)){ 
			$m = M('members');
			$wallet_record = M('member_wallet_record');   
			$wallet = M('member_wallet');
			$settlement_config = M('settlement_config');
			$sconfig = $settlement_config->where(array('status'=>1))->find();
			$w = array();
			$w['recommend_name'] = $member['member_name'];
			$w['shield'] = 0;  
			$w['package_id'] = array('gt',0);
			$count = $m->where($w)->count();
			$money1 = $sconfig['direct_push_award'];
			$money2 = $sconfig['more_push_award'];    
			$periods = date('Y-m-d',mktime());
			if($count == 3){ 
				$params1 = array(); 
				$params1['member_id'] = $member['member_id'];
				$params1['type'] = '2';      //消费钱包
				$params1['prices'] = $money1*$sconfig['award_shop_percent']/100;
				$params1['periods'] = $periods;
				$params1['description'] = '直推3个套餐奖励';
				$params1['addtime'] = mktime();
				$wallet_record->add($params1);  
				$wallet->where(array("member_id"=>$member['member_id']))->setInc('consume_money',$money1*$sconfig['award_shop_percent']/100);
				
				$params2 = array();
				$params2['member_id'] = $member['member_id'];
				$params2['type'] = '3';      //可提现钱包（现金钱包）
				$params2['prices'] = $money1*$sconfig['award_cash_percent']/100;
				$params2['periods'] = $periods;
				$params2['description'] =  '直推3个套餐奖励';
				$params2['addtime'] = mktime();
				$wallet_record->add($params2);   
				$wallet->where(array("member_id"=>$member['member_id']))->setInc('cash_money',$money1*$sconfig['award_cash_percent']/100);
			}  
			elseif($count > 3){
				$params1 = array(); 
				$params1['member_id'] = $member['member_id'];
				$params1['type'] = '2';      //消费钱包
				$params1['prices'] = $money2*$sconfig['award_shop_percent']/100;
				$params1['periods'] = $periods;
				$params1['description'] = '直推1个套餐奖励';
				$params1['addtime'] = mktime();
				$wallet_record->add($params1);  
				$wallet->where(array("member_id"=>$member['member_id']))->setInc('consume_money',$money2*$sconfig['award_shop_percent']/100);
				
				$params2 = array();
				$params2['member_id'] = $member['member_id'];
				$params2['type'] = '3';      //可提现钱包（现金钱包）
				$params2['prices'] = $money2*$sconfig['award_cash_percent']/100;
				$params2['periods'] = $periods;
				$params2['description'] = '直推1个套餐奖励';
				$params2['addtime'] = mktime();
				$wallet_record->add($params2);     
				$wallet->where(array("member_id"=>$member['member_id']))->setInc('cash_money',$money2*$sconfig['award_cash_percent']/100);
			} 
		}	
	}
	/**
	 * 获取一般三级父级用户（不紧缩，非会员直接跳过）
	 */
    protected function get_normal_parents($member_id){  
		$m = M('members');
		$parents =array();
		if(empty($member_id)){
			return $parents;
		}
		else{
			
			$sql ="SELECT tb2.member_id,tb2.level FROM lm_members AS tb1,lm_members AS tb2 WHERE tb1.recommend_name = tb2.member_name AND tb1.member_id=".$member_id;
			$p1 = M()->query($sql);
			if($p1[0]['level']>0){
				$parents['p1'] = $p1[0]['member_id'];
			}
			if(!empty($p1[0]['member_id'])){
				$sql ="SELECT tb2.member_id,tb2.level FROM lm_members AS tb1,lm_members AS tb2 WHERE tb1.recommend_name = tb2.member_name AND tb1.member_id=".$p1[0]['member_id'];
				$p2 = M()->query($sql);
				if($p2[0]['level']>0){
					$parents['p2'] = $p2[0]['member_id'];
				}
				if(!empty($p2[0]['member_id'])){
					$sql ="SELECT tb2.member_id,tb2.level FROM lm_members AS tb1,lm_members AS tb2 WHERE tb1.recommend_name = tb2.member_name AND tb1.member_id=".$p2[0]['member_id'];
					$p3 = M()->query($sql);
					if($p3[0]['level']>0){
						$parents['p3'] = $p3[0]['member_id'];
					}
				} 
			}
			return $parents;
		}
		
	}
	/**
	 * 升级父级用户等级（成为总经理或CEO）
	 */ 
	protected function Up_parent_level($member_id){
		if(!empty($member_id)){
			$sql ="SELECT tb2.member_id,tb2.level,tb2.role,tb2.member_name FROM lm_members AS tb1,lm_members AS tb2 WHERE tb1.recommend_name = tb2.member_name AND tb1.member_id=".$member_id;
			$parent_info = M()->query($sql);
			if(!empty($parent_info[0]['member_id'])){
				$role1 = M('role')->where(array('role_id'=>1,'status'=>1))->find();
				$role2 = M('role')->where(array('role_id'=>2,'status'=>1))->find();  
				$m = M('members');
				$recommend_name = $parent_info[0]['member_name'];
				$childrens = $this->get_all_childrens($recommend_name);
				$str = implode(',',$childrens);
				if($parent_info[0]['level'] == 2){  //父级是A套餐会员
					$w = array(); 
					$w['recommend_name'] = $recommend_name;
					$w['level'] = array('neq',0);
					$w['shield'] = 0;
					$count = $m->where($w)->count();  
					$w2 = array();
					$w2['level'] = array('neq',0);
					$w2['member_name'] = array('in',$str);
					$count2 = $m->where($w2)->count(); 
					if(($count >= $role1['vip_number']) && ($count2>= $role1['group_number'])){
						if($parent_info[0]['role'] == 0){
							$m->where(array('member_id'=>$parent_info[0]['member_id']))->save(array('role'=>1));
						}
						$w3 = array();  
						$w3['recommend_name'] = $recommend_name;
						$w3['role'] = array('egt',1);
						$w3['shield'] = 0;
						$count3 = $m->where($w3)->count();
						$w4 = array();
						$w4['role'] = array('egt',1);  
						$w4['member_name'] = array('in',$str);
						$count4 = $m->where($w4)->count();   
						if(($count3>=$role2['vip_number']) && ($count4>=$role2['group_number'])){
							$m->where(array('member_id'=>$parent_info[0]['member_id']))->save(array('role'=>2));
						} 
						$this->Up_parent_level($parent_info[0]['member_id']);  
					}  
				} 
				elseif($parent_info[0]['role'] == 1){  ////父级是总经理
					$w5 = array();
					$w5['recommend_name'] = $recommend_name;
					$w5['role'] = array('egt',1);
					$w5['shield'] = 0;
					$count5 = $m->where($w5)->count();
					$w6 = array();
					$w6['role'] = array('egt',1);
					$w6['member_name'] = array('in',$str);
					$count6 = $m->where($w6)->count(); 
					if(($count5>=$role2['vip_number']) && ($count6>=$role2['group_number'])){
						$m->where(array('member_id'=>$parent_info[0]['member_id']))->save(array('role'=>2));
						$this->Up_parent_level($parent_info[0]['member_id']);  
					} 
				}    
			}
		}
		
	}
	
	/** 
	 *获取所有团队成员(用于计算团队人数 ， 包括自己)
	 * return  array : 团队成员id   
	 */
	protected function get_all_childrens($member_name){
		$m = M('members');
		$w = array();
		$w['recommend_name'] = $member_name;
		$w['shield'] = 0;
		$rt = $m->where($w)->field('member_name')->select();
		$result = array();
		$result[] = $member_name;
		while(!empty($rt)){    
			$news = array();
			foreach($rt as $r){
				$result[] = $r['member_name'];
				$news[] = $r['member_name'];
			}
			
			$str = implode(',',$news);
			$where = array();
			$where['recommend_name'] = array('in',$str);
			$where['shield'] = 0;
			$rt = $m->where($where)->field('member_name')->select();
		}
		return $result;       
	}  
	   
	/*获取同步地址*/
	public function getSyncUrl(){
		$PHP_SELF=$_SERVER['PHP_SELF'];
		$url=dirname('http://'.$_SERVER['HTTP_HOST'].substr($PHP_SELF,0,strrpos($PHP_SELF,'/')+1));
		$config = M('store_config');
		$info = $config->where(array('id'=>1))->find();
		$sync_url = $url."/member?mall_db=".$info['member_name']."&auth_code=".$info['auth_code'];
		return $sync_url;     
	}     
}