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
		set_time_limit(0);      
		M('store_config')->where(array('id'=>1))->save(array('tips'=>1));
		/*同步会员*/ 
		echo "同步会员数据：<br/>";
		$sync_member = A('Member')->index();
		echo "<br/>";
		/*同步店铺*/ 
		echo "同步店铺数据：<br/>";
		$sync_store = A('Store')->index();
		echo "<br/>";
		/*同步签到*/
		echo "同步签到数据：<br/>";
		$sync_sign = A('Sign')->index();
		echo "<br/>";
		/*同步订单*/
		echo "同步订单数据：<br/>";
		$sync_order = A('Order')->index();  
		echo "<br/>";   
		$config = M('settlement_config')->where(array('status'=>1))->field('award_cash_percent,award_shop_percent,public_pv_percent,operate_pv_percent,group_pv_percent,new_contribute_percent,old_contribute_percent,recommend_pv_percent,min_subsidy_value,subsidy_value')->find();
		    
		$operate_center = M('operate_center');
		$stores = M('stores');
		$contribute_record = M('contribute_record');
		$member_wallet = M('member_wallet');
		$member_wallet_record = M('member_wallet_record');
		$members = M('members');
		$orders = M('orders');
		$order_pv_record = M('order_pv_record');
		$operate_shareholder = M('operate_shareholder');
		$operate_price_record = M('operate_price_record');
		$operate_total_price = M('operate_total_price');
		$operate_shareholder_price_record = M('operate_shareholder_price_record');
		$operate_shareholder_total_price = M('operate_shareholder_total_price');
		$public_group_price_record = M('public_group_price_record');     
		$shop_rate = M('settlement_config')->where(array('status'=>1))->getField('shop_rate');//消费转成长值倍率
		$founder_rate = M('settlement_config')->where(array('status'=>1))->getField('founder_rate');
		$sw = array();	   
		$sw['issuccess'] = 1;
		$sw['create_time'] = array('lt',mktime(0,0,0,date("m"),date("d"),date("Y")));
		$sw['isbalance'] = 0;
		$success_order = $orders->where($sw)->select(); 
		$Alldata = array();	 
		$total_pv = 0;
		foreach($success_order as $so){  
			if($so['order_pv'] > 0){
				$total_pv = $total_pv+$so['order_pv'];
				$operate_id = $members->where(array('member_id'=>$so['buyer_id'],'status'=>1))->getField('operate_id');     
				$wm = array();       
				$wm['lm_members.member_id'] = $so['buyer_id'];
				$member_rateinfo = $members->join('LEFT JOIN lm_package_list ON lm_members.package_id=lm_package_list.packageid')->where($wm)->field('lm_members.original_member,lm_package_list.contribute_rate')->find(); 
				if($member_rateinfo['original_member'] == 1){
					$member_rate = $founder_rate;
				}else{
					$member_rate = $member_rateinfo['contribute_rate']; 
				}  
				$member_rate = empty($member_rate) ? 1 : $member_rate;
				$orderdata = array();
				$orderdata['member_id'] = $so['buyer_id']; 
				$orderdata['order_id'] = $so['order_id'];  
				$orderdata['datafrom'] = 2;   
				$orderdata['value'] = $so['order_pv'] * $shop_rate * $member_rate;
				$orderdata['desc'] = '通过消费获得 '.$orderdata['value'].' 成长值';
				$orderdata['status'] = '1';    
				$orderdata['addtime'] = mktime();
				if($contribute_record->add($orderdata)){
					$walletdata = array();  
					$walletdata['member_id'] = $so['buyer_id']; 
					$walletdata['source_order'] = $so['order_id'];
					$member_wallet->where(array('member_id'=>$so['buyer_id']))->setInc('contribute_value',$orderdata['value']);  		    			
				}

				//结算推荐奖励
				$recommend_id = $members->where(array('member_id'=>$so['buyer_id'],'status'=>1))->getField('recommend_id');
				if(!empty($recommend_id)){
					$recommend_value = $so['order_pv']*$config['recommend_pv_percent']/100;
					
					$params = array();    
					$params['member_id'] = $recommend_id;
					$params['type'] = '2';      //消费钱包
					$params['prices'] = $recommend_value*($config['award_shop_percent']/100) ;
					$params['periods'] = date("Y-m-d",strtotime("-1 day"));
					$params['description'] = '订单直推分红';
					$params['addtime'] = mktime();
					if($params['prices'] > 0){
						$member_wallet_record->add($params);  
						//更新用户消费钱包
						$member_wallet->where(array("member_id"=>$recommend_id))->setInc('consume_money', $recommend_value*($config['award_shop_percent']/100));  
					}  
					$params2 = array();
					$params2['member_id'] = $recommend_id;
					$params2['type'] = '3';      //现金钱包
					$params2['prices'] = $recommend_value*$config['award_cash_percent']/100;
					$params2['periods'] = date("Y-m-d",strtotime("-1 day"));
					$params2['description'] = '订单直推分红';
					$params2['addtime'] = mktime(); 
					if($params2['prices'] > 0){
						$member_wallet_record->add($params2);  
						//更新用户现金钱包 
						$member_wallet->where(array("member_id"=>$recommend_id))->setInc('cash_money',$recommend_value*$config['award_cash_percent']/100);
					}   
					
				} 	
				
				 
				//公益基金利润
				$data1 = array();
				$data1['pv'] = $so['order_pv'] * $config['public_pv_percent']/100;
				$data1['order_id'] = $so['order_id']; 
				$data1['pay_name'] = $so['pay_name'];
				$data1['operate_id'] = $operate_id;
				$data1['type'] = 1;
				$data1['periods'] =date("Y-m-d",strtotime("-1 day"));
				$data1['addtime'] = mktime();
				$Alldata[] = $data1;
				
				
				//团队激励利润
				$data2 = array();
				$data2['pv'] = $so['order_pv'] * $config['group_pv_percent']/100;
				$data2['order_id'] = $so['order_id']; 
				$data2['pay_name'] = $so['pay_name'];
				$data2['operate_id'] = $operate_id;
				$data2['type'] = 2;
				$data2['periods'] =date("Y-m-d",strtotime("-1 day"));
				$data2['addtime'] = mktime();
				$Alldata[] = $data2;
				  
				//新增成长值利润
				$data3 = array();
				$data3['pv'] = $so['order_pv'] * $config['new_contribute_percent']/100;
				$data3['order_id'] = $so['order_id']; 
				$data3['pay_name'] = $so['pay_name'];
				$data3['operate_id'] = $operate_id;
				$data3['type'] = 3;
				$data3['periods'] =date("Y-m-d",strtotime("-1 day"));
				$data3['addtime'] = mktime();
				$Alldata[] = $data3;
				
				
				//历史成长值利润
				$data4 = array();
				$data4['pv'] = $so['order_pv'] * $config['old_contribute_percent']/100;
				$data4['order_id'] = $so['order_id']; 
				$data4['pay_name'] = $so['pay_name'];
				$data4['operate_id'] = $operate_id;    
				$data4['type'] = 4;
				$data4['periods'] =date("Y-m-d",strtotime("-1 day"));
				$data4['addtime'] = mktime();
				$Alldata[] = $data4;
				
				
				//营运中心利润
				$data5 = array();
				$data5['pv'] = $so['order_pv'] * $config['operate_pv_percent']/100;
				$data5['order_id'] = $so['order_id']; 
				$data5['pay_name'] = $so['pay_name'];
				$data5['operate_id'] = $operate_id;
				$data5['type'] = 5;
				$data5['periods'] =date("Y-m-d",strtotime("-1 day"));
				$data5['addtime'] = mktime();
				$Alldata[] = $data5;
				
			}      	
			$orders->where(array('order_id'=>$so['order_id']))->save(array('isbalance'=>1));	
		}          
        $order_pv_record->addAll($Alldata);
		
		//公益基金利润结算
		$where1 = array();
		$where1['type'] = 1;
		$where1['status'] = 0;
		$where1['periods'] = date("Y-m-d",strtotime("-1 day"));
		$lists1 = $order_pv_record->where($where1)->select();
		$alldata1 = array();
		foreach($lists1 as $list1){
			$data1 = array();
			$data1['type'] = 1;
			$data1['style'] = 1;
			$data1['link_orderid'] = $list1['order_id'];
			$data1['pay_name'] = $list1['pay_name'];
			$data1['value'] = $list1['pv'];
			$data1['desc'] = '订单分成【订单号：'.$list1['order_id'].'】';
			$data1['periods'] = date("Y-m-d",strtotime("-1 day"));
			$data1['addtime'] = mktime();
			$alldata1[] = $data1;
			
		}
		$public_group_price_record->addAll($alldata1);
		$order_pv_record->where($where1)->save(array('status'=>1));
		
		//团队激励利润结算
		$where2 = array();
		$where2['type'] = 2;
		$where2['status'] = 0;
		$where2['periods'] = date("Y-m-d",strtotime("-1 day"));
		$lists2 = $order_pv_record->where($where2)->select();
		$alldata2 = array();
		foreach($lists2 as $list2){
			$data2 = array();
			$data2['type'] = 2;
			$data2['style'] = 1;
			$data2['link_orderid'] = $list2['order_id'];
			$data2['pay_name'] = $list2['pay_name'];
			$data2['value'] = $list2['pv'];
			$data2['desc'] = '订单分成【订单号：'.$list2['order_id'].'】';
			$data2['periods'] = date("Y-m-d",strtotime("-1 day"));
			$data2['addtime'] = mktime();
			$alldata2[] =$data2;
			
		}    
		$public_group_price_record->addAll($alldata2);
		$order_pv_record->where($where2)->save(array('status'=>1));
		
		//运营商利润结算	
		$where3 = array();
		$where3['lm_order_pv_record.type'] = 5;
		$where3['lm_order_pv_record.status'] = 0;
		$where3['lm_order_pv_record.periods'] = date("Y-m-d",strtotime("-1 day"));
		$lists3 = $order_pv_record->join("LEFT JOIN lm_operate_center ON lm_operate_center.id = lm_order_pv_record.operate_id")->where($where3)->field('lm_order_pv_record.*,lm_operate_center.operate_name')->select();
		$shareholders= $order_pv_record->where($where3)->group('operate_id')->field('operate_id')->select();   
		$share_holders = array();
		/*找出运营商里的股东信息*/
		foreach($shareholders as $shareholder){
			$share_holders[$shareholder['operate_id']] = M('operate_shareholder')->join("LEFT JOIN lm_operate_center ON lm_operate_center.id = lm_operate_shareholder.operate_id")->where(array('lm_operate_shareholder.operate_id'=>$shareholder['operate_id'],'lm_operate_shareholder.status'=>1))->field('lm_operate_shareholder.operate_id,lm_operate_shareholder.id,lm_operate_shareholder.shareholder_name,lm_operate_shareholder.share_rate,lm_operate_shareholder.member_id,lm_operate_shareholder.member_name,lm_operate_center.operate_name')->select();
		}
		$alldata3 = array();   
		$alldata3_holders = array();
		$total3 = array();
		$total3_holders = array();   		
		foreach($lists3 as $list3){
			if(!empty($lists['operate_id']))
			{
				$data3 = array();
				$data3['operate_id'] = $list3['operate_id'];
				$data3['link_orderid'] = $list3['order_id'];
				$data3['type'] = 1;
				$data3['pay_name'] = $list3['pay_name'];
				$data3['operate_name'] = $list3['operate_name'];
				$data3['value'] = $list3['pv'];
				$data3['desc'] = '订单分成【订单号：'.$list3['order_id'].'】';
				$data3['periods'] = date("Y-m-d",strtotime("-1 day"));
				$data3['addtime'] = mktime();
				$alldata3[] =$data3;
				$total3[$list3['operate_id']] = empty($total3[$list3['operate_id']]) ? $list3['pv'] : ($total3[$list3['operate_id']]+$list3['pv']);
				$holders = $share_holders[$list3['operate_id']];
				foreach($holders as $holder){
					$data3_holder = array();
					$data3_holder['operate_id'] =$holder['operate_id'];
					$data3_holder['operate_name'] =$holder['operate_name'];
					$data3_holder['shareholder_id'] =$holder['id'];
					$data3_holder['shareholder_name'] =$holder['shareholder_name'];
					$data3_holder['type'] =1;  
					$data3_holder['link_orderid'] = $list3['order_id'];
					$data3_holder['pay_name'] = $list3['pay_name'];
					$data3_holder['value'] = $list3['pv']*$holder['share_rate']/100;
					$data3_holder['desc'] = '订单分成【订单号：'.$list3['order_id'].'】';
					$data3_holder['periods'] = date("Y-m-d",strtotime("-1 day"));
					$data3_holder['addtime'] = mktime(); 
					$alldata3_holders[] = $data3_holder;
					$total3_holders[$holder['id']] = empty($total3_holders[$holder['id']]) ? $data3_holder['value'] : ($total3_holders[$holder['id']]+$data3_holder['value']);
				}
			}	
		}
		$operate_price_record->addAll($alldata3);
		$operate_shareholder_price_record->addAll($alldata3_holders); 
		$month = date("Y-m",strtotime("-1 day")); 
		 
		foreach($total3 as $k=>$t3){
			$t3 = empty($t3) ? 0 : $t3;
			$w = array();
			$w['operate_id'] = $k;
			$w['month'] = $month;
			$check1 = $operate_total_price->where($w)->find();
			if(!empty($check1)){
				$operate_total_price->where($w)->setInc('value', $t3);  
			}else{
				$totalda = array();
				$totalda['operate_id'] = $k;
				$totalda['operate_name'] = $operate_center->where(array('id'=>$k))->getField('operate_name');
				$totalda['value'] = $t3;
				$totalda['month'] = $month;
				$totalda['month_time'] = strtotime($month);
				$operate_total_price->add($totalda);
			}
		}
		foreach($total3_holders as $k2=>$th3){
			$th3 = empty($th3) ? 0 : $th3; 
			$w = array();
			$w['shareholder_id'] = $k2;
			$w['month'] = $month;
			$check2 = $operate_shareholder_total_price->where($w)->find();
			if(!empty($check2)){
				$operate_shareholder_total_price->where($w)->setInc('value', $th3);  
			}else{
				$totalda2 = array();
				$operate_info = $operate_shareholder->join('LEFT JOIN lm_operate_center ON lm_operate_center.id=lm_operate_shareholder.operate_id')->where(array('lm_operate_shareholder.id'=>$k2))->field('lm_operate_shareholder.id,lm_operate_shareholder.operate_id,lm_operate_shareholder.shareholder_name,lm_operate_center.operate_name')->find();
				$totalda2['operate_id'] = $operate_info['operate_id'];
				$totalda2['operate_name'] = $operate_info['operate_name'];
				$totalda2['shareholder_id'] = $k2;
				$totalda2['shareholder_name'] = $operate_info['shareholder_name'];
				$totalda2['value'] = $th3;
				$totalda2['month'] = $month;
				$totalda2['month_time'] = strtotime($month);
				$operate_shareholder_total_price->add($totalda2);
			}
		}	
		$order_pv_record->where($where3)->save(array('status'=>1));   
	    $subsidy = M('subsidy_record');	  
	    $sub_where = array();
		$sub_where['periods'] =  date("Y-m-d",strtotime("-1 day"));
		$sub_rt = $subsidy->where($sub_where)->find();
		if(empty($sub_rt)){
			if($total_pv < $config['min_subsidy_value']){
			$add_pv = $config['subsidy_value'];
			}else{ 
				$add_pv = 0;
			}
			$subsidy_data = array();
			$subsidy_data['value'] = $add_pv;
			$subsidy_data['periods'] = date("Y-m-d",strtotime("-1 day"));
			$subsidy_data['addtime'] = mktime();
			$subsidy_data['status'] = 1;
			$subsidy->add($subsidy_data);
		}else{
			$add_pv = 0;
		} 
		//新增成长值利润结算  
		$where4 = array();
		$where4['status'] = 0;
		$where4['type'] = 3;
		$where4['periods'] = date("Y-m-d",strtotime("-1 day")); 
		$all_pv3 = $order_pv_record->where($where4)->sum('pv');  
		$all_pv3 = $all_pv3 + $add_pv* $config['new_contribute_percent']/($config['old_contribute_percent']+$config['new_contribute_percent']); 
		$t =  strtotime(date("Y-m-d",strtotime("-1 day"))); //昨天开始时间
		//新增成长值
		$wc = array();
		$wc['addtime'] = array('egt',$t);
		$wc['status'] = 1;
		$contribute_list1 = $contribute_record->where($wc)->field('member_id,sum(value) totals')->group('member_id')->select();
		$contribute_total1 = 0;
		foreach($contribute_list1 as $c1){
			$contribute_total1 = $contribute_total1+$c1['totals'];
		}
		foreach($contribute_list1 as $clist1){
			$value1 = $all_pv3 * ($clist1['totals']/$contribute_total1);
			$params5 = array();
			$params5['member_id'] = $clist1['member_id'];
			$params5['type'] = '2';      //消费钱包
			$params5['prices'] = $value1*($config['award_shop_percent']/100) ;
			$params5['periods'] = date("Y-m-d",strtotime("-1 day"));
			$params5['description'] = '新增成长值分红';
			$params5['addtime'] = mktime();
			if($params5['prices'] > 0){
				$member_wallet_record->add($params5);  
				//更新用户消费钱包
				$member_wallet->where(array("member_id"=>$clist1['member_id']))->setInc('consume_money', $value1*($config['award_shop_percent']/100));  
			} 
			$params6 = array();
			$params6['member_id'] = $clist1['member_id'];
			$params6['type'] = '3';      //现金钱包
			$params6['prices'] = $value1*$config['award_cash_percent']/100;
			$params6['periods'] = date("Y-m-d",strtotime("-1 day"));
			$params6['description'] = '新增成长值分红';
			$params6['addtime'] = mktime();
			if($params6['prices'] > 0){
				$member_wallet_record->add($params6);  
				//更新用户现金钱包 
				$member_wallet->where(array("member_id"=>$clist1['member_id']))->setInc('cash_money',$value1*$config['award_cash_percent']/100);
			}  
		}
		$order_pv_record->where($where4)->save(array('status'=>1));   
		//历史成长值利润结算
		$where5 = array();
		$where5['status'] = 0;
		$where5['type'] = 4;
		$where5['periods'] = date("Y-m-d",strtotime("-1 day"));
		$all_pv4 = $order_pv_record->where($where5)->sum('pv');  
		$all_pv4 = $all_pv4 + $add_pv* $config['old_contribute_percent']/($config['old_contribute_percent']+$config['new_contribute_percent']);
		$wc2 = array();
		$wc2['addtime'] = array('lt',$t);
		$wc2['status'] = 1;
		$contribute_list2 = $contribute_record->where($wc2)->field('member_id,sum(value) totals')->group('member_id')->select();
		$contribute_total2 = 0;
		foreach($contribute_list2 as $c2){
			$contribute_total2 = $contribute_total2+$c2['totals'];
		}    
		foreach($contribute_list2 as $clist2){
			$value2 = $all_pv4 * ($clist2['totals']/$contribute_total2);
			$params7 = array();
			$params7['member_id'] = $clist2['member_id'];
			$params7['type'] = '2';      //消费钱包
			$params7['prices'] = $value2*($config['award_shop_percent']/100) ;
			$params7['periods'] = date("Y-m-d",strtotime("-1 day"));
			$params7['description'] = '历史成长值分红';
			$params7['addtime'] = mktime();
			if($params7['prices'] > 0){
				$member_wallet_record->add($params7);
				//更新用户消费钱包
				$member_wallet->where(array("member_id"=>$clist2['member_id']))->setInc('consume_money', $value2*($config['award_shop_percent']/100));  
			}
			$params8 = array();
			$params8['member_id'] = $clist2['member_id'];
			$params8['type'] = '3';      //现金钱包
			$params8['prices'] = $value2*$config['award_cash_percent']/100;
			$params8['periods'] = date("Y-m-d",strtotime("-1 day"));
			$params8['description'] ='历史成长值分红';
			$params8['addtime'] = mktime();
			if($params8['prices'] > 0)
			{
				$member_wallet_record->add($params8);     
				//更新用户现金钱包   
				$member_wallet->where(array("member_id"=>$clist2['member_id']))->setInc('cash_money',$value2*$config['award_cash_percent']/100);
			}
		} 
		$order_pv_record->where($where5)->save(array('status'=>1)); 
		echo "<br/>";  
		die('结算完成');     
		        
	}	   
	  
 
}