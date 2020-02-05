<?php
namespace Api\Controller;
use Think\Controller;
class GetInfoController extends Controller {
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
	/**
	 *获取可购买套餐详情
	 */ 
    public function getPackageInfo(){
		$m = M('package_list');
		$w = array();
		$w['lm_package_list.is_show'] =1;
		$rt = $m->join('left join lm_member_level ON lm_member_level.level_id=lm_package_list.up_level')->where($w)->order('sort DESC')->field('lm_package_list.*,lm_member_level.level_name')->select();
		echo json_encode($rt,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		die;
    }     
	
	/*获取用户已购买套餐详情*/
	public function getMemebrPackage(){
		$member_id = I('member_id');
		$w = array();
		$w['lm_vip_orders.member_id'] = $member_id;
		$w['lm_vip_orders.status'] = 1;
		$member = M('vip_orders');
		$rt = $member->join('LEFT JOIN lm_members ON lm_members.member_id=lm_vip_orders.member_id')->join('LEFT JOIN lm_member_level ON lm_member_level.level_id=lm_vip_orders.up_level')->join('LEFT JOIN lm_package_list ON lm_package_list.packageid=lm_vip_orders.packageid')->where($w)->field('lm_member_level.level_name,lm_members.truename,lm_members.truetel,lm_vip_orders.*,lm_package_list.main_img,lm_package_list.name')->find();   
		echo json_encode($rt,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		die;    
	}    
   
	public function getWallet(){
		$member_id = I('member_id');
		$rt = array();
		if(empty($member_id)){
			$rt['result'] = -1;
			$rt['desc'] = '该用户不存在'; 
		}
		else{
			$m = M('member_wallet');
			$w = array();
			$w['member_id'] = $member_id;
			$rt = $m->where($w)->find();
			$rt['result'] = 1;
		}
		echo json_encode($rt,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
		die;
	} 
	
	/*获取用户所有的底下会员*/
	public function getchildren(){
		$member_name= I('member_name');
		$m = M('members');
		$w = array();  
		$w['recommend_name'] = $member_name;
		$rt = $m->where($w)->field('member_name')->select();
	
		$result = array();
		while(!empty($rt)){   
			$news = array();
			foreach($rt as $r){
				$result[] = $r['member_name'];
				$news[] = $r['member_name'];
			}
			
			$str = implode(',',$news);
			$where = array(); 
			$where['recommend_name'] = array('in',$str);
			$rt = $m->where($where)->field('member_name')->select();
		}  
		echo json_encode($result,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
		die;    
	}
	
	/*获取用户三级会员的详情*/
	public function getThreeChildren(){
		$member_name= I('member_name');
		$m = M('members');
		$w = array();  
		$w['member_name'] = $member_name;
		$result = array();
		$result['recommend_name'] = $m->where($w)->getField('recommend_name');
		$w1 = array();
		$w1['recommend_name'] = $member_name;
		$w1['shield'] = 0;
		$result['rank1'] = $m->where($w1)->field('member_name,level,member_avatar,member_nickname')->select();
		
		if(empty($result['rank1'])){
			$result['rank2'] = array();
			$result['rank3'] = array();
		}
		else{
			$rank1name = array();
			foreach($result['rank1'] as $rank1){
				$rank1name[] =$rank1['member_name']; 
			}
			$rank1_str = implode(',',$rank1name);
			$w2 = array();
			$w2['recommend_name'] = array('in',$rank1_str);
			$w2['shield'] = 0;
			$result['rank2'] = $m->where($w2)->field('member_name,level,member_avatar,member_nickname')->select();
			if(empty($result['rank2'])){
				$result['rank3'] = array();
			}
			else{
				$rank2name = array();
				foreach($result['rank2'] as $rank2){
					$rank2name[] =$rank2['member_name']; 
				}
				$rank2_str = implode(',',$rank2name);
				$w3 = array();
				$w3['recommend_name'] = array('in',$rank2_str);
				$w3['shield'] = 0;
				$result['rank3'] = $m->where($w3)->field('member_name,level,member_avatar,member_nickname')->select();
			}
		}
		echo json_encode($result,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
		die; 
		
	}
	
	/*获取我的业绩*/
	public function getAchievement_bak(){
		
		$config = M('settlement_config');
		$percent = $config->where(array('status'=>1))->field('recommend1_percent,recommend2_percent,recommend3_percent')->find(); 
		$member_id = I('member_id');
		if(empty($member_id)){
			$result = array();
			$result['result'] = -1;
			$result['desc'] = '用户不存在！';
			echo json_encode($result,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
			die;
			   
		}
		$m = M('orders');
		$allprice1=0;
		$allprice2=0;
		$allprice3=0;   
		/*已结算订单*/
		$w1 =array();
		$w1['_string'] = "p1=".$member_id." OR p2=".$member_id." OR p3=".$member_id;
		$w1['issuccess'] = 1;   
		$w1['isbalance'] = 1; 
		$rt1 = $m->where($w1)->field('order_id,create_time,p1,p2,p3,order_pv')->select();
		foreach($rt1 as $key=>$r1){  
			if($r1['p1'] == $member_id){
				$rt1[$key]['getpv'] = $r1['order_pv']*$percent['recommend1_percent']/100;
			}
			elseif($r1['p2'] == $member_id){
				$rt1[$key]['getpv'] = $r1['order_pv']*$percent['recommend2_percent']/100;
			}else{
				$rt1[$key]['getpv'] = $r1['order_pv']*$percent['recommend3_percent']/100;
			}
			$allprice1=$allprice1+$rt1[$key]['getpv'];
		}  

		/*待结算订单*/
		$w2 =array();
		$w2['_string'] = "p1=".$member_id." OR p2=".$member_id." OR p3=".$member_id;
		$w2['issuccess'] = 1;
		$w2['isbalance'] = 0;
		$rt2 = $m->where($w2)->field('order_id,create_time,p1,p2,p3,order_pv')->select();
		foreach($rt2 as $key=>$r2){
			if($r2['p1'] == $member_id){
				$rt2[$key]['getpv'] = $r2['order_pv']*$percent['recommend1_percent']/100;
			}
			elseif($r2['p2'] == $member_id){
				$rt2[$key]['getpv'] = $r2['order_pv']*$percent['recommend2_percent']/100;
			}else{
				$rt2[$key]['getpv'] = $r2['order_pv']*$percent['recommend3_percent']/100;
			}
			$allprice2=$allprice2+$rt2[$key]['getpv'];
		}
    
		/*无效订单*/
		$w3 =array();
		$w3['_string'] = "p1=".$member_id." OR p2=".$member_id." OR p3=".$member_id;
		$w3['issuccess'] = 0;
		$rt3 = $m->where($w3)->field('order_id,create_time,p1,p2,p3,order_pv')->select();
		foreach($rt3 as $key=>$r3){
			if($r3['p1'] == $member_id){
				$rt3[$key]['getpv'] = $r3['order_pv']*$percent['recommend1_percent']/100;
			}
			elseif($r3['p2'] == $member_id){
				$rt3[$key]['getpv'] = $r3['order_pv']*$percent['recommend2_percent']/100;
			}else{
				$rt3[$key]['getpv'] = $r3['order_pv']*$percent['recommend3_percent']/100;
			}
			$allprice3=$allprice3+$rt3[$key]['getpv'];
		}	  
		$result =array();
		$result['result'] =1;   
		$result['allprice1'] =$allprice1; 
		$result['allprice2'] =$allprice2; 
		$result['allprice3'] =$allprice3; 
		$result['balance'] = $rt1;
		$result['unbalance'] = $rt2;
		$result['out'] = $rt3;
		echo json_encode($result,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
		die;    
		
	}   
	
	/*获取我的业绩(新)*/
	public function getAchievement(){
		$member_id = I('member_id');
		if(empty($member_id)){
			$result = array();
			$result['result'] = -1;  
			$result['desc'] = '用户不存在！';
			echo json_encode($result,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
			die;
			   
		}
		$shop_rate = M('settlement_config')->where(array('status'=>1))->getField('shop_rate');
		$members = M('members');
		$wm = array();       
		$wm['lm_members.member_id'] = $member_id;
		$member_rate = $members->join('LEFT JOIN lm_member_level ON lm_members.level=lm_member_level.level_id')->where($wm)->getField('lm_member_level.contribute_rate'); 		
		$m = M('orders');
		$contribute_record = M('contribute_record');
		$allprice1=0;
		$allprice2=0;
		$allprice3=0;  
		/*已结算订单*/
		$w1 =array();
		$w1['buyer_id'] = $member_id;
		$w1['issuccess'] = 1;   
		$w1['isbalance'] = 1; 
		$rt1 = $m->where($w1)->field('order_id,create_time,order_pv')->select();
		foreach($rt1 as $key=>$r1){
			$value = $contribute_record->where(array('order_id'=>$r1['order_id']))->getField('value');
            $value = empty($value) ? 0 : $value; 			
			$allprice1=$allprice1+$value;
			$rt1[$key]['getcontribute'] = $value;
		}  

		/*待结算订单*/
		$w2 =array();
		$w2['buyer_id'] = $member_id;
		$w2['issuccess'] = 1;
		$w2['isbalance'] = 0;
		$rt2 = $m->where($w2)->field('order_id,create_time,order_pv')->select();
		   
		foreach($rt2 as $key=>$r2){
			$value = $r2['order_pv'] * $shop_rate * $member_rate;
			$allprice2=$allprice2+$value;
			$rt2[$key]['getcontribute'] = $value;
		}
    
		/*无效订单*/
		$w3 =array();
		$w3['buyer_id'] =$member_id;
		$w3['issuccess'] = 0;
		$rt3 = $m->where($w3)->field('order_id,create_time,order_pv')->select();
		foreach($rt3 as $key=>$r3){
			$value = $r3['order_pv'] * $shop_rate * $member_rate;
			$allprice3=$allprice3+$value;
			$rt3[$key]['getcontribute'] = $value;
		}	       
		$result =array();
		$result['result'] =1;      
		$result['allprice1'] =$allprice1; 
		$result['allprice2'] =$allprice2; 
		$result['allprice3'] =$allprice3; 
		$result['balance'] = $rt1;
		$result['unbalance'] = $rt2;
		$result['out'] = $rt3;
		echo json_encode($result,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
		die;    
		
	}
	
	
	
	
	/*获取用户可提现金额记录*/
	public function getCashInfo(){
		$member_id = I('member_id');
		//dump($member_id);exit;
		$result = array();
		$result['total'] = M('member_wallet')->where(array('member_id'=>$member_id))->getField('cash_money');
		$w = array();
		$w['member_id'] = $member_id;
		$w['type'] = 3;  	
		$m  = M('member_wallet_record');  
		$result['list'] =$m->where($w)->select();
		$w['prices'] = array('gt',0);   
		$count =  $m->where($w)->sum('prices');
		$result['count'] = empty($count) ? '0.00' : $count;
		echo json_encode($result,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
		die;
	}
	   
	/*获取用户可消费金额记录*/
	public function getConsumeInfo(){
		$member_id = I('member_id');
		$result = array();
		$result['total'] = M('member_wallet')->where(array('member_id'=>$member_id))->getField('consume_money');
		$w = array();
		$w['member_id'] = $member_id; 
		$w['type'] = 2;  
		$m  = M('member_wallet_record');    
		$count =  $m->where($w)->sum('prices');
		$result['count'] = empty($count) ? '0.00' : $count;
		$result['list'] =$m->where($w)->select();
		echo json_encode($result,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
		die;
	}	
	
	/*获取用户PV记录*/
	public function getPvInfo(){
		$member_id = I('member_id');
		$result = array();
		$result['total'] = M('member_wallet')->where(array('member_id'=>$member_id))->getField('pv_amount');
		$w = array();
		$w['member_id'] = $member_id;
		$w['type'] = 1;     
		$m  = M('member_wallet_record');    
		$count =  $m->where($w)->sum('prices');
		$result['count'] = empty($count) ? '0.00' : $count;
		$result['list'] =$m->where($w)->order("addtime desc")->select();
		echo json_encode($result,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
		die;
	}
	
	public function getContributeInfo(){
		$member_id = I('member_id');
		$result = array();
		$result['total'] = M('member_wallet')->where(array('member_id'=>$member_id))->getField('contribute_value');
		$w = array();
		$w['member_id'] = $member_id;
		$w['status'] = 1;     
		$m  = M('contribute_record');    
		$count =  $m->where($w)->sum('value');
		$result['count'] = empty($count) ? '0.00' : $count;
		$result['list'] =$m->where($w)->order("addtime desc")->select();
		echo json_encode($result,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
		die;  
	}
	
	   
	/*获取用户详情*/
	public function getMemberInfo(){
		$member_id = I('member_id');
		$w = array();
		$w['member_id'] = $member_id;
		$w['shield'] = 0;
		$m = M('members');
		$rt = $m->where($w)->find();
		echo json_encode($rt,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
		die;
	} 
	 
	/*获取用户所有基本信息*/
	public function getAllInfo(){
		$member_id = I('member_id');
		$w = array();
		$w['lm_members.member_id'] = $member_id;
		$member = M('members');
		$rt = $member->join('LEFT JOIN lm_member_wallet ON lm_member_wallet.member_id=lm_members.member_id')->join('LEFT JOIN lm_member_level ON lm_member_level.level_id=lm_members.level')->where($w)->field('lm_member_level.level_name,lm_members.package_id,lm_members.level,lm_members.member_avatar,lm_members.truename,lm_members.member_nickname,lm_members.member_name,lm_members.original_member,lm_member_wallet.pv_amount,lm_member_wallet.consume_money,lm_member_wallet.cash_money,lm_member_wallet.contribute_value')->find();
		$m = M('drawmoney_record');
		$w1 = array();   
		$w1['member_id'] = $member_id;
		$w1['status'] = 0;
		$drawing = $m->where($w1)->sum('drawmoney');
		$rt['drawing'] = empty($drawing) ? 0 : $drawing;
		$w2 = array();
		$w2['member_id'] = $member_id;
		$w2['status'] = 1;
		$drawed = $m->where($w2)->sum('drawmoney');      
		$rt['drawed'] = empty($drawed) ? 0 : $drawed;
		$contribute_record = M('contribute_record');
		$t1 = strtotime(date('Y-m-d'));
		  
		$w3 = array();
		$w3['member_id'] = 	$member_id;
		$w3['addtime'] = array('egt',$t1);
		$newcontribute = $contribute_record->where($w3)->sum('value');
		$rt['newcontribute'] = empty($newcontribute) ? 0 : $newcontribute;
		$w4 = array();
		$w4['member_id'] = 	$member_id;
		$w4['addtime'] = array('lt',$t1);   
		$oldcontribute = $contribute_record->where($w4)->sum('value');
		$rt['oldcontribute'] = empty($oldcontribute) ? 0 : $oldcontribute;
		$operate_center = M('operate_center');
		$w5 = array();
		$w5['member_id'] = $member_id;
		$w5['status'] = 1;
		$operate = $operate_center->where($w5)->find();
		
		if(!empty($operate)){
			$allprice = M('operate_total_price')->where(array('operate_id'=>$operate['id']))->sum('value');
			$operate['totalprice'] = empty($allprice) ? 0 :$allprice;
			$rt['is_operate'] = 1;
			$rt['operate_info'] = $operate;
		}
		
		$operate_shareholder = M('operate_shareholder');
		$w6 = array();
		$w6['member_id'] = $member_id;
		$w6['status'] = 1;
		$shareholder = $operate_shareholder->where($w6)->find(); 
		if(!empty($shareholder)){    
			$allprice2 =  M('operate_shareholder_total_price')->where(array('shareholder_id'=>$shareholder['id']))->sum('value');    
			$shareholder['totalprice'] = empty($allprice2) ? 0 :$allprice2;
			$rt['shareholder_info']['operate_name'] =$operate_center->where(array('id'=>$shareholder['operate_id']))->getField('operate_name');
			$rt['is_shareholder'] = 1;  
			$rt['shareholder_info'] = $shareholder;
		}    
		   
		
		echo json_encode($rt,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
		die;      
	}
	
	/*用户申请提现数据提交*/   
	public function drawmoney_apply(){
		$rt = array();   
		$member_id = I('member_id');
		$price = floatval(I('price'));
		$off = M('settlement_config')->where(array('status'=>1))->getField('drawmoney_percent');
		$real_price = $price - ($price*$off/100);
		$wallet = M('member_wallet');
		$cash_money = $wallet->where(array('member_id'=>$member_id))->getField('cash_money');
		if(empty($member_id)){  
			$rt['result'] = -1;
			$rt['desc'] = '用户不存在';
		}      
		elseif($real_price < 1){         
			$rt['result'] = -1;
			$rt['desc'] = '提现金额过少，无法提现';
		}elseif($price > $cash_money){
			$rt['result'] = -1;
			$rt['desc'] = '提现金额大于可提现金额，无法提现';
		}else{
			$r1 = $wallet->where(array('member_id'=>$member_id))->setDec('cash_money',$price);
			if($r1 !== false){
				$order_sn = 'draw'.time().$member_id; 
				 $check =  M('drawmoney_record')->where(array('order_sn'=>$order_sn))->find();
				while(!empty($check)){ 
					$time = time()+1;  
					$order_sn = 'draw'.$time.$member_id; 
					$check =  M('drawmoney_record')->where(array('order_sn'=>$order_sn))->find();
				}      
				$data = array();  
				$data['member_id'] = $member_id;
				$data['order_sn'] = $order_sn;   
				$data['drawmoney'] = $price;
				$data['fee'] = $price*$off/100;
				$data['type'] = 1;
				$data['status'] = 0;
				$data['addtime'] = mktime();
				$r2 = M('drawmoney_record')->add($data);
				if($r2 !== false){
					$data2 = array();
					$data2['member_id'] = $member_id;
					$data2['source_order'] =$data['order_sn'];
					$data2['type'] = 3;
					$data2['prices'] = 0-$price;
					$data2['description'] = '提现（正在审核）';
					$data2['periods'] = date('Y-m',mktime());
					$data2['addtime'] = mktime();
					M('member_wallet_record')->add($data2);
					$rt['result'] = 1;
				}else{ 
					$rt['result'] = -1;
					$rt['desc'] = '更新会员现金成功，但插入提现申请表失败，请联系商家解决';
				}     
			}else{ 
				$rt['result'] = -1;
				$rt['desc'] = '更新会员现金失败';
			}	
		} 
		echo json_encode($json,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
		die;	
	}   
	
	public function checkSync(){
		$member_id = I('member_id');
		$m = M('members');
		$w = array();  
		$w['member_id'] = $member_id;
		$rt = $m->where($w)->find();   
		$json = array();
		if(empty($rt)){   
			
			$mall_db = $_GET['mall_db'];  
			$auth_code = $_GET['auth_code'];
			$url = ROOT_URL .'/api/member?mall_db='.$mall_db.'&auth_code='.$auth_code; 
			$rt = file_get_contents($url);           		
			$arr = json_decode($rt,true);  
			if($arr['result']!='0' &&  $arr['result']!='1'){
				$json['result'] = '-1';
				$json['desc'] = '同步失败';
			}			
		} 
		echo json_encode($json,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
		die;       
	}  
	
	/*通过PV换算成长值*/
	public function PV_TO_Contribute(){
		$goods_sn = I('goods_sn');
		$pv = I('pv',0);
		$rt = array();
		if($pv > 0 ){    
			$member_name = I('member_name','');
			$members = M('members');
			$wm = array();           
			$wm['lm_members.member_name'] = $member_name; 
			$member_rate = $members->join('LEFT JOIN lm_package_list ON lm_members.package_id=lm_package_list.packageid')->where($wm)->getField('lm_package_list.contribute_rate');  	
			$member_rate = empty($member_rate) ? 1 : $member_rate;
			$shop_rate = M('settlement_config')->where(array('status'=>1))->getField('shop_rate');
			$rt['status'] = 1; 
			$rt['desc'] = '成长值：'.$pv*$member_rate*$shop_rate;
		}     
		else{
			$rt['status'] = 1;
			$rt['desc'] = '成长值：0';
		} 
		echo json_encode($rt,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
		die;     
		
	}
	
	/*获取结算配置*/
	public function getSettlementConfig(){
		$m = M('settlement_config');
		$w = array();
		$w['status'] = 1;
		$info = $m->where($w)->find();
		echo json_encode($info,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
		die;        
	}
	
	
	
	
	
	
	
}