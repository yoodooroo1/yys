<?php
namespace Api\Controller;
use Think\Controller;
class SettlementController extends Controller {
	private $parentid;    
	private $children=array(); 	
	public function __construct()
	{     
	    header("Content-Type: text/html;charset=utf-8");
		parent::__construct();
		$sc = M('store_config');
		$where = array();
		$where['id'] = 1;  
		$configs = $sc->where($where)->find();
		$mall_db = $_GET['mall_db'];
		$auth_code = $_GET['auth_code'];
		if($mall_db != $configs['member_name'] || $auth_code != $configs['auth_code']){
			die('所传参数错误，无法进行结算');
		}
		$h = intval(date('H'));
		if($h != $configs['count_day']){
			$data =array();   
			$data['tips'] = 0;    
			$sc->where(array('id'=>1))->save($data);
			die('还未到结算日期');
		} 
		if($configs['tips'] == 1){
			die('今天已经结算过了');
		} 
	} 
 	
    public function index(){
		die('结算不再使用该接口');
		//开始结算处理  
		$system = M('store_config');
		$data =array();
		$data['tips'] = 1;    
		$system->where(array('id'=>1))->save($data);
		  
		//$records = $this->Count_member_pv();   //统计上个月各会员总订单pv  
		//$this->update_member_pv($records);     //更新会员钱包   			
		//$this->settlement_pv();                //结算本期会员pv(紧缩)
		//$this->updateOrderParent();            //更新订单推荐关系，并把订单置为已结束
		/*计算并分发奖金*/    
		$time1 = mktime(0,0,0,date('m',mktime())-1,1,date('Y',mktime()));  //开始时间
		$periods = date('Y-m',$time1);
		/*会员重销部分*/
		$settlement = M('settlement_pv');
		$where = array();
		$where['periods'] = $periods;
		$infos = $settlement->where($where)->field('member_id')->select();
		$wallet = M('member_wallet');
		$wallet_record = M('member_wallet_record');
		$settlement_config = M('settlement_config')->where(array('status'=>'1'))->find(); 
		foreach($infos as $info){
			$childrens = array();
			$str1 = '';
			$str2 = '';  
			$str3 = '';  
			$count_pv1 = 0;     
			$count_pv2 = 0;
			$count_pv3 = 0;
			$childrens = $this->get_three_childrens($info['member_id']);
			$children1 = $childrens['children1'];
			if(!empty($children1)){  
				$str1 = implode(',',$children1);
				$w1 = array();
				$w1['periods'] = $periods;
				$w1['member_id'] = array('in',$str1);
				$count_pv1 = $settlement->where($w1)->sum('pv');
			}
			
			$children2 = $childrens['children2'];
			if(!empty($children2)){
				$str2 = implode(',',$children2);
				$w2 = array();
				$w2['periods'] = $periods;
				$w2['member_id'] = array('in',$str2);
				$count_pv2 = $settlement->where($w2)->sum('pv'); 
			}
			
			$children3 = $childrens['children3'];   			
			if(!empty($children3)){   
				$str3 = implode(',',$children3);
				$w3 = array();
				$w3['periods'] = $periods;
				$w3['member_id'] = array('in',$str3);
				$count_pv3 = $settlement->where($w3)->sum('pv');
			}
			$count_pv = $count_pv1*$settlement_config['recommend1_percent']/100 + $count_pv2*$settlement_config['recommend2_percent']/100 + $count_pv3*$settlement_config['recommend3_percent']/100;
			if($count_pv > 0){  
				$params1 = array();
				$params1['member_id'] = $info['member_id'];
				$params1['type'] = '2';      //消费钱包
				$params1['prices'] = $count_pv*$settlement_config['award_shop_percent']/100;
				$params1['periods'] = $periods;  
				$params1['description'] = '推广消费奖励';
				$params1['addtime'] = mktime();
				$wallet_record->add($params1);
				
				$params2 = array();
				$params2['member_id'] = $info['member_id'];
				$params2['type'] = '3';      //可提现钱包（现金钱包）
				$params2['prices'] = $count_pv*$settlement_config['award_cash_percent']/100; 
				$params2['periods'] = $periods;  
				$params2['description'] = '推广消费奖励';
				$params2['addtime'] = mktime();
				$wallet_record->add($params2);  
				//更新用户消费钱包
				$wallet->where(array("member_id"=>$info['member_id']))->setInc('consume_money',$count_pv*$settlement_config['award_shop_percent']/100);   
				//更新用户现金钱包
				$wallet->where(array("member_id"=>$info['member_id']))->setInc('cash_money',$count_pv*$settlement_config['award_cash_percent']/100);
			}
		}  
		/*会员重销部分计算结束*/
		
		/*计算总经理和CEO获得团队和育成团队奖金*/				
		$members = M('members');
		$mw = array(); 
		$mw['shield'] = 0;
		$mw['role'] = array('gt',0); 
		$Bosses = $members->where($mw)->field('member_id,role')->select();  
		$role = M('role');
		$rwhere = array();
		$rwhere['role_id'] = array('gt',0);
		$rwhere['status'] = 1;
		$role_config = $role->where($rwhere)->select();
		foreach($Bosses as $boss){
			$get_group_percent = 0;          //团队提成
			$get_breedgroup_percent = 0;     //育成团队提成
			foreach($role_config as $rconfig){
				if($boss['role'] == $rconfig['role_id']){
					$get_group_percent = $rconfig['get_group_percent']/100;                //团队提成
					$get_breedgroup_percent = $rconfig['get_breedgroup_percent']/100;      //育成团队提成
				}
			}    
			$group_str = '';
			$develop_group_str = '';
			$group_pv = 0;
			$develop_group_pv = 0;
			$groups = $this->get_group($boss['member_id']);
			$group = $groups['group'];  //团队
			$develop = $groups['develop_group']; 
			$develop_group = array();   //育成团队领导人
			foreach($develop as $dp){
				$develop_group[] = $dp;
				$d_groups = $this->get_group($dp);
				foreach($d_groups['group'] as $ds){
					$develop_group[] = $ds;
				}
			}    
			if(!empty($group)){
				$group_str = implode(',',$group);
				$w4 = array();
				$w4['periods'] = $periods;
				$w4['member_id'] = array('in',$group_str);
				$group_pv = $settlement->where($w4)->sum('pv'); 
				if($group_pv > 0){
					$params3 = array();
					$params3['member_id'] = $boss['member_id'];
					$params3['type'] = '2';      //消费钱包
					$params3['prices'] = $group_pv*$get_group_percent*$settlement_config['award_shop_percent']/100;
					$params3['periods'] = $periods;
					$params3['description'] = '总经理团队业绩分红';
					$params3['addtime'] = mktime();
					$wallet_record->add($params3);
					 
					$params4 = array();  
					$params4['member_id'] = $boss['member_id'];
					$params4['type'] = '3';      //可提现钱包（现金钱包）
					$params4['prices'] = $group_pv*$get_group_percent*$settlement_config['award_cash_percent']/100; 
					$params4['periods'] = $periods;  
					$params4['description'] = '总经理团队业绩分红';
					$params4['addtime'] = mktime();
					$wallet_record->add($params4);
					//更新用户消费钱包
					$wallet->where(array("member_id"=>$boss['member_id']))->setInc('consume_money',$group_pv*$get_group_percent*$settlement_config['award_shop_percent']/100);   
					//更新用户现金钱包
					$wallet->where(array("member_id"=>$boss['member_id']))->setInc('cash_money',$group_pv*$get_group_percent*$settlement_config['award_cash_percent']/100);
				}
			}  
			if(!empty($develop_group)){
				$develop_group_str = implode(',',$develop_group);
				$w5 = array();
				$w5['periods'] = $periods;  
				$w5['member_id'] = array('in',$develop_group_str);
				$develop_group_pv = $settlement->where($w5)->sum('pv'); 
				if($develop_group_pv > 0){
					$params5 = array();
					$params5['member_id'] = $boss['member_id'];
					$params5['type'] = '2';      //消费钱包
					$params5['prices'] = $develop_group_pv*$get_breedgroup_percent*$settlement_config['award_shop_percent']/100;
					$params5['periods'] = $periods;
					$params5['description'] = '总经理育成业绩分红';
					$params5['addtime'] = mktime();
					$wallet_record->add($params5);
					
					$params6 = array();  
					$params6['member_id'] = $boss['member_id'];
					$params6['type'] = '3';      //可提现钱包（现金钱包）
					$params6['prices'] = $develop_group_pv*$get_breedgroup_percent*$settlement_config['award_cash_percent']/100; 
					$params6['periods'] = $periods;   
					$params6['description'] = '总经理育成业绩分红';
					$params6['addtime'] = mktime(); 
					$wallet_record->add($params6);
					//更新用户消费钱包
					$wallet->where(array("member_id"=>$boss['member_id']))->setInc('consume_money',$develop_group_pv*$get_breedgroup_percent*$settlement_config['award_shop_percent']/100);   
					//更新用户现金钱包
					$wallet->where(array("member_id"=>$boss['member_id']))->setInc('cash_money',$develop_group_pv*$get_breedgroup_percent*$settlement_config['award_cash_percent']/100);
				}
			} 
		}
		/*计算总经理和CEO获得团队和育成团队奖金结束*/
			
		/* 计算CEO整体团队分红	 */ 
		
		$cw = array();  
		$cw['shield'] = 0;
		$cw['role'] = 2; 
		$CEO = $members->where($cw)->field('member_id')->select();
        $cwhere = array();
		$cwhere['role_id'] = 2;
		$cwhere['status'] = 1;		
		$get_allgroup_percent = $role->where($cwhere)->getField('get_allgroup_percent');           //整体团队提成
		$get_allgroup_percent = $get_allgroup_percent/100;
		foreach($CEO as $ceo){
			$ceo_str = array();
			$ceo_pv = 0; 
			$ceo_group = $this->get_all_group($ceo['member_id']);
			if(!empty($ceo_group)){    
				$ceo_str = implode(',',$ceo_group);
				$w6 = array();
				$w6['periods'] = $periods;  
				$w6['member_id'] = array('in',$ceo_str);
				$ceo_pv = $settlement->where($w6)->sum('pv'); 
				if($ceo_pv > 0){
					$params7 = array();
					$params7['member_id'] = $ceo['member_id'];
					$params7['type'] = '2';      //消费钱包
					$params7['prices'] = $ceo_pv*$get_allgroup_percent*$settlement_config['award_shop_percent']/100;
					$params7['periods'] = $periods;
					$params7['description'] = 'CEO业绩团队分红';
					$params7['addtime'] = mktime();
					$wallet_record->add($params7);
					
					$params8 = array();  
					$params8['member_id'] = $ceo['member_id'];
					$params8['type'] = '3';      //可提现钱包（现金钱包）
					$params8['prices'] = $ceo_pv*$get_allgroup_percent*$settlement_config['award_cash_percent']/100; 
					$params8['periods'] = $periods;  
					$params8['description'] = 'CEO业绩团队分红';
					$params8['addtime'] = mktime(); 
					$wallet_record->add($params8);  
					//更新用户消费钱包
					$wallet->where(array("member_id"=>$ceo['member_id']))->setInc('consume_money',$ceo_pv*$get_allgroup_percent*$settlement_config['award_shop_percent']/100);   
					//更新用户现金钱包 
					$wallet->where(array("member_id"=>$ceo['member_id']))->setInc('cash_money',$ceo_pv*$get_allgroup_percent*$settlement_config['award_cash_percent']/100);
				} 
			}
		}
		/*计算CEO整体团队分红结束*/         
	
    }    
	
	/*更新订单推荐关系，并把订单置为已结算*/
	public function updateOrderParent(){
		$time1 = mktime(0,0,0,date('m',mktime())-1,1,date('Y',mktime()));  //开始时间
		$time2 = mktime(0,0,0,date('m',mktime()),1,date('Y',mktime()));    //结束时间
		$periods = date('Y-m',$time1);
		
		$m = M('orders'); 
		$w= array();
		$w['issuccess'] = 1;
		$w['_string'] = "delivery_time>=$time1 AND delivery_time < $time2" ;
		$m->where($w)->save(array('p1'=>'0','p2'=>'0','p3'=>'0','isbalance'=>'1'));  
		$settlement = M('settlement_pv');
		$where = array();
		$where['periods'] = $periods;
		$lists = $settlement->where($where)->select();
		foreach($lists as $list){
			$orders = json_decode($list['contains_orderids'],true);
			$orderlist = array();
			foreach($orders as $order){
				$orderlist = array_merge($orderlist,$order['orders']);
			}
			$order_str = implode(',',$orderlist);
			$parents = $this->getCrunchParentFromSettlement($list['member_id']);
			$wo = array();
			$wo['order_id'] =array('in',$order_str);
			$data = array();
			$data['p1'] = $parents['p1'];
			$data['p2'] = $parents['p2'];
			$data['p3'] = $parents['p3'];
			$m->where($wo)->save($data);
		}  
	}
	
	
	/*通过结算列表获取紧缩三级父级id*/
	public function getCrunchParentFromSettlement($member_id){
		$time1 = mktime(0,0,0,date('m',mktime())-1,1,date('Y',mktime()));  //开始时间
		$periods = date('Y-m',$time1);
		$settlement = M('settlement_pv');
		$where = array();
		$where['periods'] = $periods;
		$where['member_id'] = $member_id;
		$p1 = $settlement->where($where)->getField('parent_id');
		if(empty($p1)){
			$p2 = 0;
			$p3 = 0;
		}
		else{
			$where2 = array();
			$where2['periods'] = $periods;
			$where2['member_id'] = $p1;
			$p2 = $settlement->where($where2)->getField('parent_id');
			if(empty($p2)){
				$p3 = 0;
			}
			else{
				$where3 = array();
				$where3['periods'] = $periods;
				$where3['member_id'] = $p2;
				$p3 = $settlement->where($where3)->getField('parent_id');
			}
		}
		$p =array();
		$p['p1'] = $p1;
		$p['p2'] = $p2;
		$p['p3'] = $p3;
		return $p;
	} 
	
	public function Count_member_pv(){
		$time1 = mktime(0,0,0,date('m',mktime())-1,1,date('Y',mktime()));  //开始时间
		$time2 = mktime(0,0,0,date('m',mktime()),1,date('Y',mktime()));    //结束时间
		$periods = date('Y-m',$time1);
		$sql = "SELECT sum(order_pv) as pv,buyer_id as member_id FROM lm_orders WHERE delivery_time>=$time1 AND delivery_time < $time2  AND issuccess='1' AND isbalance='0' GROUP BY buyer_id";
		$rt = M()->query($sql);
		$record = M('member_pv_record');
		$order = M('orders');
		foreach($rt as $key=>$r) 
		{
			$w = array();  
			$w['buyer_id'] = $r['member_id'];
			$w['_string'] = "delivery_time>=$time1 AND delivery_time < $time2";
			$orders_arr = $order->where($w)->field('order_id')->select();
			$order_arr = array(); 
			foreach($orders_arr as $oa){
				$order_arr[] = $oa['order_id']; 
			}   
			$orders_str =  json_encode($order_arr);  
			$rt[$key]["contain_orderids"] = $orders_str;
			$rt[$key]["periods"] = $periods;
			$record->add($rt[$key]); 
		}             
		return $rt; 
	} 
	 
	public function update_member_pv($params){  
		$wallet = M('member_wallet'); 
		$wallet_record = M('member_wallet_record');
		$time1 = mktime(0,0,0,date('m',mktime())-1,1,date('Y',mktime()));  //开始时间
		$periods = date('Y-m',$time1);
		foreach($params as $param){
			$w = array();
			$w['member_id'] = $param['member_id'];
			$info = $wallet->where($w)->find();
			if(empty($info)){
				$data = array();
				$data['member_id'] = $param['member_id'];
				$data['pv_amount'] = $param['pv'];
				$wallet->add($data);
			}  
			else{   
				$wallet->where(array("member_id"=>$param['member_id']))->setInc('pv_amount',$param['pv']);   //更新用户PV钱包
				$data2 = array();
				$data2['member_id'] = $param['member_id'];
				$data2['type'] = 1;
				$data2['prices'] = $param['pv'];
				$data2['description'] = '消费收入';
				$data2['periods'] = $periods;   
				$data2['addtime'] = mktime();
				$wallet_record->add($data2);          //用户PV更新记录插入数据库
			}
		}
	}
	
	public function  settlement_pv(){
		$wallet = M('member_wallet');
		$settlement = M('settlement_pv');
		$wallet_record = M('member_wallet_record');
		$time1 = mktime(0,0,0,date('m',mktime())-1,1,date('Y',mktime()));  //开始时间
		$periods = date('Y-m',$time1);
		$w = array();  
		$w['periods'] = $periods;
		$sql = M('member_pv_record')->field('member_id,pv,contain_orderids')->where($w)->select(false);
		$datas = $wallet->join("LEFT JOIN ($sql) tb2 ON lm_member_wallet.member_id=tb2.member_id")->field('lm_member_wallet.member_id,lm_member_wallet.pv_amount,tb2.pv,tb2.contain_orderids')->select();
		$m = M('settlement_pv');   
		$min_pv = M('settlement_config')->where(array('status'=>'1'))->getField('min_pv'); 
		foreach($datas as $da){  
			$pv = empty($da['pv']) ? 0 : $da['pv'];  //会员本月消费pv
			if($da['pv_amount']>=$min_pv){			
				$wallet->where(array("member_id"=>$da['member_id']))->setDec('pv_amount',$min_pv);   //满足条件钱包扣除300pv 
				$data2 = array();
				$data2['member_id'] = $da['member_id'];
				$data2['type'] = 1;
				$data2['prices'] = 0-$min_pv;  
				$data2['description'] = $periods.'月份消耗';
				$data2['periods'] = $periods;
				$data2['addtime'] = mktime();  
				$wallet_record->add($data2);          //用户PV更新记录插入数据库
				$info = array();
				$info = $settlement->where(array("member_id"=>$da['member_id']))->find();
				if(empty($info)){
					$this->get_crunch_parent($da['member_id']);   //获取紧缩父级
					$parent_id = $this->parentid;
					$contain = array();
					$contain[0]['mid'] = $da['member_id'];
					$contain[0]['orders'] = json_decode($da['contain_orderids'],true);
					$param = array();
					$param['member_id'] = $da['member_id'];
					$param['pv'] = $pv;    
					$param['parent_id'] = "$parent_id";      
					$param['periods'] = $periods;
					$param['contains_orderids'] = json_encode($contain);
					$settlement->add($param);  
				}   
				else{
					$contain = array();
					$contain = json_decode($info['contains_orderids'],true);
					$i = count($contain);
					$contain[$i]['mid'] = $da['member_id'];
					$contain[$i]['orders'] =json_decode($da['contain_orderids'],true);
					$settlement->where(array("member_id"=>$da['member_id']))->save(array('contains_orderids'=>json_encode($contain)));  
					$settlement->where(array("member_id"=>$da['member_id']))->setInc('pv',$pv);
				}				
			}     
			else{
				$this->get_crunch_parent($da['member_id']);   //获取紧缩父级  
				$member_id = $this->parentid;
				if(!empty($member_id)){    
					$info = array();
					$info = $settlement->where(array("member_id"=>$member_id))->find();
					if(empty($info)){ 
						$this->get_crunch_parent($member_id);   //获取紧缩父级 
						$parent_id = $this->parentid;
						$contain = array();
						$contain[0]['mid'] = $da['member_id'];
						$contain[0]['orders'] = json_decode($da['contain_orderids'],true);
						$param = array();  
						$param['member_id'] = $member_id;
						$param['pv'] = $pv;  
						$param['periods'] = $periods;
						$param['parent_id'] = "$parent_id";
						$param['contains_orderids'] = json_encode($contain);
						$settlement->add($param);  
						
					}   
					else{
						$contain = array();
						$contain = json_decode($info['contains_orderids'],true);
						$i = count($contain);  
						$contain[$i]['mid'] = $da['member_id'];
						$contain[$i]['orders'] =json_decode($da['contain_orderids'],true);
						$settlement->where(array("member_id"=>$member_id))->save(array('contains_orderids'=>json_encode($contain)));  
						$settlement->where(array("member_id"=>$member_id))->setInc('pv',$pv);
					}
				}  	
			}
		}  
		
	} 
	
	/**        
	 *获取紧缩父级  
	 */
	public function get_crunch_parent($member_id){
		if(empty($member_id)){
			$this->parentid = '';       
		}
		else{
			$m = M('members');
			$sql ="SELECT tb2.member_id FROM lm_members AS tb1,lm_members AS tb2 WHERE tb1.recommend_name = tb2.member_name AND tb1.member_id=".$member_id;
			$mem = array();
			$mem = M()->query($sql);
			$member_id = $mem[0]['member_id'];    
			
			if(empty($member_id)){      //无推荐人返回‘0’
				$this->parentid = ''; 
			}    
			else{
				$time1 = mktime(0,0,0,date('m',mktime())-1,1,date('Y',mktime()));  
				$periods = date('Y-m',$time1);                 //期数
				$settlement = M('settlement_pv');
				$w1 = array();
				$w1['member_id'] = $member_id;
				$w1['periods'] = $periods;
				$info = $settlement->where($w1)->find();
				if(!empty($info)){                            //不为空说明满足条件
					$this->parentid = $member_id;
				}
				else{
					$wallet = M('member_wallet');
			        $min_pv = M('settlement_config')->where(array('status'=>'1'))->getField('min_pv');					
					$w2 = array();
					$w2['member_id'] = $member_id;    
					$w2['pv_amount'] = array('egt',$min_pv);  	  	
					$info2 =$wallet->where($w2)->find();     //查看钱包里的pv是否大于300  
					if(!empty($info2)){  					 //不为空说明满足条件         
						$this->parentid = $member_id;
					}    
					else{  
						$this->get_crunch_parent($member_id);
					}    
				}  
			}
		}			
		
	}  
	  
	/**
	 *获取当期团队成员id 和育成团队领导人id
	 * return  array['group']:团队成员id   array['develop_group']:育成团队领导人id
	 */
	public function get_group($member_id){
		$time1 = mktime(0,0,0,date('m',mktime())-1,1,date('Y',mktime()));  //开始时间
		$periods = date('Y-m',$time1);
		$m = M('settlement_pv');
		$w = array();
		$w['lm_settlement_pv.parent_id'] = $member_id;
		$w['lm_settlement_pv.periods'] = $periods;
		$rt = $m->join('LEFT JOIN lm_members ON lm_members.member_id=lm_settlement_pv.member_id')->where($w)->field('lm_settlement_pv.member_id,lm_members.level,lm_members.role')->select();
		$result = array();
		while(!empty($rt)){
			$news = array();
			foreach($rt as $r){
				if($r['role']>0){
					$result['develop_group'][]=$r['member_id'];
				}
				else{
					$result['group'][] = $r['member_id'];
					$news[] = $r['member_id'];
				}
			}  
			$str = implode(',',$news);
			$where = array();
			$where['lm_settlement_pv.parent_id'] = array('in',$str);
			$where['lm_settlement_pv.periods'] = $periods;
			$rt = $m->join('LEFT JOIN lm_members ON lm_members.member_id=lm_settlement_pv.member_id')->where($where)->field('lm_settlement_pv.member_id,lm_members.level,lm_members.role')->select();
		}
		return $result;   
	} 

	/**
	 *获取当期所有团队成员(用于计算CEO奖金)
	 * return  array : 团队成员id   
	 */
	public function get_all_group($member_id){
		$time1 = mktime(0,0,0,date('m',mktime())-1,1,date('Y',mktime()));  //开始时间
		$periods = date('Y-m',$time1);
		$m = M('settlement_pv');
		$w = array();
		$w['parent_id'] = $member_id;
		$w['periods'] = $periods;
		$rt = $m->where($w)->field('member_id')->select();
		$result = array();
		while(!empty($rt)){  
			$news = array();
			foreach($rt as $r){
				$result[] = $r['member_id'];
				$news[] = $r['member_id'];
			}
			
			$str = implode(',',$news);
			$where = array();
			$where['parent_id'] = array('in',$str);
			$where['periods'] = $periods;
			$rt = $m->where($where)->field('member_id')->select();
		}
		return $result;   
	}   
	
	

	/**
	 * 获取底下的三级成员（紧缩）
	 */
	public function get_three_childrens($member_id){
		$time1 = mktime(0,0,0,date('m',mktime())-1,1,date('Y',mktime()));  //开始时间
		$periods = date('Y-m',$time1);
		$result = array();
		$m = M('settlement_pv');
		$w = array();
		$w['parent_id'] = $member_id;  
		$w['periods'] = $periods;
		$children1 = $m->where($w)->field('member_id')->select();
		if(!empty($children1)){
			foreach($children1 as $c1){
				$result['children1'][] = $c1['member_id'];
			}
		}
		if(!empty($result['children1'])){
			
			$str1 = implode(',',$result['children1']);
			$w2 = array();
			$w2['parent_id'] = array('in',$str1);
			$w2['periods'] = $periods;
			$children2 = $m->where($w2)->field('member_id')->select();
			if(!empty($children2)){
				foreach($children2 as $c2){
					$result['children2'][] = $c2['member_id'];
				}
			}
		}
		if(!empty($result['children2'])){
			$str2 = implode(',',$result['children2']);
			$w3 = array(); 
			$w3['parent_id'] = array('in',$str2);
			$w3['periods'] = $periods;
			$children3 = $m->where($w3)->field('member_id')->select();
			if(!empty($children3)){
				foreach($children3 as $c3){
					$result['children3'][] = $c3['member_id'];
				}
			}  
		}
		return $result;
	}

    
	public function settlement(){     
		M('store_config')->where(array('id'=>1))->save(array('tips'=>1));
		/*同步会员*/
		$sync_member = A('Member')->index();
		echo "<br/>";
		/*同步店铺*/
		$sync_store = A('Store')->index();
		echo "<br/>";
		/*同步签到*/
		$sync_sign = A('Sign')->index();
		echo "<br/>";
		/*同步订单*/
		$sync_order = A('Order')->index();  
		echo "<br/>";
		$config = M('settlement_config')->where(array('status'=>1))->field('award_cash_percent,award_shop_percent,main_pv_percent,operate_pv_percent,contribute_pv_percent,new_contribute_percent,old_contribute_percent')->find();
		
		$operate_center = M('operate_center');
		$stores = M('stores');
		$contribute_record = M('contribute_record');
		$member_wallet = M('member_wallet');
		$member_wallet_record = M('member_wallet_record');
		$members = M('members');
		$orders = M('orders');
		$order_pv_record = M('order_pv_record');
		$shop_rate = M('settlement_config')->where(array('status'=>1))->getField('shop_rate');
		$sw = array();	   
		$sw['issuccess'] = 1;
		$sw['create_time'] = array('lt',mktime(0,0,0,date("m"),date("d"),date("Y")));
		$sw['isbalance'] = 0;
		$success_order = $orders->where($sw)->select();
		foreach($success_order as $so){  
			if($so['order_pv'] > 0){
				$operate_store_id = $stores->where(array('store_id'=>$so['storeid'],'status'=>1))->getField('operate_store_id');   
				$wm = array();       
				$wm['lm_members.member_id'] = $so['buyer_id'];
				$member_rate = $members->join('LEFT JOIN lm_member_level ON lm_members.level=lm_member_level.level_id')->where($wm)->getField('lm_member_level.contribute_rate'); 
				$orderdata = array();
				$orderdata['member_id'] = $so['buyer_id']; 
				$orderdata['order_id'] = $so['order_id'];  
				$orderdata['datafrom'] = 2;   
				$orderdata['value'] = $so['order_pv'] * $shop_rate * $member_rate;
				$orderdata['desc'] = '通过消费获得 '.$orderdata['value'].' 贡献值';
				$orderdata['status'] = '1';    
				$orderdata['addtime'] = mktime();
				if($contribute_record->add($orderdata)){
					$walletdata = array();  
					$walletdata['member_id'] = $so['buyer_id']; 
					$walletdata['source_order'] = $so['order_id'];
					$member_wallet->where(array('member_id'=>$so['buyer_id']))->setInc('contribute_value',$orderdata['value']);  		    			
				}     
				
				//总部利润
				$data1 = array();
				$data1['pv'] = $so['order_pv'] * $config['main_pv_percent']/100;
				$data1['order_id'] = $so['order_id']; 
				$data1['operate_store_id'] = $operate_store_id;
				$data1['type'] = 1;
				$data1['periods'] =date("Y-m-d",strtotime("-1 day"));
				$data1['addtime'] = mktime();
				$order_pv_record->add($data1);
				
				//营运中心利润
				$data2 = array();
				$data2['pv'] = $so['order_pv'] * $config['operate_pv_percent']/100;
				$data2['order_id'] = $so['order_id']; 
				$data2['operate_store_id'] = $operate_store_id;
				$data2['type'] = 2;
				$data2['periods'] =date("Y-m-d",strtotime("-1 day"));
				$data2['addtime'] = mktime();
				$order_pv_record->add($data2);
				
				//贡献值利润   
				$data3 = array();
				$data3['pv'] = $so['order_pv'] * $config['contribute_pv_percent']/100;
				$data3['order_id'] = $so['order_id']; 
				$data3['operate_store_id'] = $operate_store_id;
				$data3['type'] = 3;
				$data3['periods'] =date("Y-m-d",strtotime("-1 day"));
				$data3['addtime'] = mktime();
				$order_pv_record->add($data3); 		
			} 
			$orders->where(array('order_id'=>$so['order_id']))->save(array('isbalance'=>1));	
		}    

		//总部利润结算
		$where1 = array();
		$where1['status'] = 0;
		$where1['type'] = 1;
		$where1['periods'] = date("Y-m-d",strtotime("-1 day"));
		$total1 = $order_pv_record->where($where1)->sum('pv');
		if($total1 > 0){
			$main_member_id = $operate_center->where(array('main_store'=>1,'status'=>1))->getField('member_id');
			$params1 = array();
			$params1['member_id'] = $main_member_id;
			$params1['type'] = '2';      //消费钱包
			$params1['prices'] = $total1*$config['award_shop_percent']/100;
			$params1['periods'] = date("Y-m-d",strtotime("-1 day"));
			$params1['description'] = date("Y-m-d",strtotime("-1 day")).' 期总部获得利润';
			$params1['addtime'] = mktime();
			$member_wallet_record->add($params1);
			//更新用户消费钱包
			$member_wallet->where(array("member_id"=>$main_member_id))->setInc('consume_money',$total1*$config['award_shop_percent']/100);  
			
			$params2 = array();
			$params2['member_id'] = $main_member_id;
			$params2['type'] = '3';      //现金钱包
			$params2['prices'] = $total1*$config['award_cash_percent']/100;
			$params2['periods'] = date("Y-m-d",strtotime("-1 day"));
			$params2['description'] = date("Y-m-d",strtotime("-1 day")).' 期总部获得利润';
			$params2['addtime'] = mktime();
			$member_wallet_record->add($params2);  
			//更新用户现金钱包 
			$member_wallet->where(array("member_id"=>$main_member_id))->setInc('cash_money',$total1*$config['award_cash_percent']/100);
		}
		$order_pv_record->where($where1)->save(array('status'=>1)); 
		   
		//运营中心利润结算
		$where2 = array();   
		$where2['status'] = 0;
		$where2['type'] = 2;
		$where2['periods'] = date("Y-m-d",strtotime("-1 day"));
		$total2_list = $order_pv_record->where($where2)->group('operate_store_id')->field("operate_store_id store_id,sum(pv) total_pv")->select();
		foreach($total2_list as $key=>$list1){  
			$total2_list[$key]['member_id']= $stores->where(array('store_id'=>$list1['store_id']))->getField('member_id');
		}  
		foreach($total2_list as $list2){
			if($list2['total_pv'] > 0){
				$params3 = array();
				$params3['member_id'] = $list2['member_id'];
				$params3['type'] = '2';      //消费钱包
				$params3['prices'] = $list2['total_pv']*$config['award_shop_percent']/100;
				$params3['periods'] = date("Y-m-d",strtotime("-1 day"));
				$params3['description'] = date("Y-m-d",strtotime("-1 day")).' 期运营中心获得利润';
				$params3['addtime'] = mktime();
				$member_wallet_record->add($params3);
				//更新用户消费钱包
				$member_wallet->where(array("member_id"=>$list2['member_id']))->setInc('consume_money',$list2['total_pv']*$config['award_shop_percent']/100);  
				
				$params4 = array();
				$params4['member_id'] = $list2['member_id'];
				$params4['type'] = '3';      //现金钱包
				$params4['prices'] = $list2['total_pv']*$config['award_cash_percent']/100;
				$params4['periods'] = date("Y-m-d",strtotime("-1 day"));
				$params4['description'] = date("Y-m-d",strtotime("-1 day")).' 期运营中心获得利润';
				$params4['addtime'] = mktime();
				$member_wallet_record->add($params4);  
				//更新用户现金钱包 
				$member_wallet->where(array("member_id"=>$list2['member_id']))->setInc('cash_money',$list2['total_pv']*$config['award_cash_percent']/100);
			}   
		}    
		$order_pv_record->where($where2)->save(array('status'=>1)); 
		
		//贡献值利润结算
		$where3 = array();
		$where3['status'] = 0;
		$where3['type'] = 3;
		$where3['periods'] = date("Y-m-d",strtotime("-1 day"));
		$total3 = $order_pv_record->where($where3)->sum('pv');
		$t =  strtotime(date("Y-m-d",strtotime("-1 day"))); //昨天开始时间
		//新增贡献值
		$wc = array();
		$wc['addtime'] = array('egt',$t);
		$wc['status'] = 1;
		$contribute_list1 = $contribute_record->where($wc)->field('member_id,sum(value) totals')->group('member_id')->select();
		$contribute_total1 = 0;
		foreach($contribute_list1 as $c1){
			$contribute_total1 = $contribute_total1+$c1['totals'];
		}
		foreach($contribute_list1 as $clist1){
			$value1 = $total3 * ($clist1['totals']/$contribute_total1) * ($config['new_contribute_percent']/100);
			$params5 = array();
			$params5['member_id'] = $clist1['member_id'];
			$params5['type'] = '2';      //消费钱包
			$params5['prices'] = $value1*($config['award_shop_percent']/100) ;
			$params5['periods'] = date("Y-m-d",strtotime("-1 day"));
			$params5['description'] = '新增贡献值分红';
			$params5['addtime'] = mktime();
			$member_wallet_record->add($params5);  
			//更新用户消费钱包
			$member_wallet->where(array("member_id"=>$clist1['member_id']))->setInc('consume_money', $value1*($config['award_shop_percent']/100));  
			
			$params6 = array();
			$params6['member_id'] = $clist1['member_id'];
			$params6['type'] = '3';      //现金钱包
			$params6['prices'] = $value1*$config['award_cash_percent']/100;
			$params6['periods'] = date("Y-m-d",strtotime("-1 day"));
			$params6['description'] = '新增贡献值分红';
			$params6['addtime'] = mktime();
			$member_wallet_record->add($params6);  
			//更新用户现金钱包 
			$member_wallet->where(array("member_id"=>$clist1['member_id']))->setInc('cash_money',$value1*$config['award_cash_percent']/100);
		}
		
		//历史贡献值
		$wc2 = array();
		$wc2['addtime'] = array('lt',$t);
		$wc2['status'] = 1;
		$contribute_list2 = $contribute_record->where($wc2)->field('member_id,sum(value) totals')->group('member_id')->select();
		$contribute_total2 = 0;
		foreach($contribute_list2 as $c2){
			$contribute_total2 = $contribute_total2+$c2['totals'];
		} 
		foreach($contribute_list2 as $clist2){
			$value2 = $total3 * ($clist2['totals']/$contribute_total2) * ($config['old_contribute_percent']/100);
			$params7 = array();
			$params7['member_id'] = $clist2['member_id'];
			$params7['type'] = '2';      //消费钱包
			$params7['prices'] = $value1*($config['award_shop_percent']/100) ;
			$params7['periods'] = date("Y-m-d",strtotime("-1 day"));
			$params7['description'] = '历史贡献值分红';
			$params7['addtime'] = mktime();
			$member_wallet_record->add($params7);
			//更新用户消费钱包
			$member_wallet->where(array("member_id"=>$clist2['member_id']))->setInc('consume_money', $value2*($config['award_shop_percent']/100));  
			
			$params8 = array();
			$params8['member_id'] = $clist2['member_id'];
			$params8['type'] = '3';      //现金钱包
			$params8['prices'] = $value1*$config['award_cash_percent']/100;
			$params8['periods'] = date("Y-m-d",strtotime("-1 day"));
			$params8['description'] ='历史贡献值分红';
			$params8['addtime'] = mktime();
			$member_wallet_record->add($params8);     
			//更新用户现金钱包   
			$member_wallet->where(array("member_id"=>$clist2['member_id']))->setInc('cash_money',$value2*$config['award_cash_percent']/100);
		} 
		$order_pv_record->where($where3)->save(array('status'=>1)); 
		echo "<br/>";
		die('结算完成');
		        
	}	  
	
 
}