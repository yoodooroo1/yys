<?php
namespace Api\Controller;
use Think\Controller;
class FundController extends Controller {
	public function __construct()
	{   
	    header("Content-Type: text/html;charset=utf-8");
		parent::__construct();  
		
		$auth_code = I('auth_code');
		$m = M('system_config');  
		$where = array();  
		$where['status'] = 1;
		$system_info = $m->where($where)->find();
		$code = $system_info['auth_code'];
		if($code != $auth_code){
			$rt = array();
			$rt['result'] = -1;
			$rt['desc'] = 'auth_code参数错误';
			echo json_encode($rt,JSON_UNESCAPED_UNICODE);
			die();
		} 
	}

	/**
	 * 获取续费套餐详情
	 * params int $package_id 套餐id
	 * params int $store_id 店铺id
	 * params string $auth_code 授权码
	 * return  json $result 套餐详情
	 */          
    public function getPackageInfo(){
		$m = M('package_list');
		$store = M('stores');
		$result = array();
		$package_id = I('package_id');
		$store_id = I('store_id');   
		$w1 = array();
		$w1['store_id'] = $store_id;
		$check1 = $store->where($w1)->find();
		if(empty($check1)){
			$result['result'] = -1;
			$result['error'] = '该店铺不存在';
			die(json_encode($result,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		}
		$w2 = array();
		$w2['packageid'] = $package_id;
		$w2['status'] = 1;
		$info = $m->where($w2)->find();
		if($check1['is_try'] == 1){
			$news = M('shareholder_package_edit')->where(array('operate_id'=>$check1['operate_id'],'package_id'=>$package_id,'status'=>1))->find();
			if(!empty($news)){
				$info['market_price'] = $news['package_price'];
				$info['market_price2'] = $news['package_price2'];
				$info['market_price3'] = $news['package_price3'];
			}             
		}
		if(empty($info)){
			$result['result'] = -1;
			$result['error'] = '套餐不存在';
			die(json_encode($result,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		}else{
			$result['result'] = 1;
			$result['datas'] = $info;
			echo json_encode($result,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}	
		   
    }

	/** 
	 * 同步续约订单
	 * params int store_id 店铺编号 
	 * params string order_sn 订单编号 
	 * params int age_limit 套餐年限
	 * params int paytype 支付方式  1-线下支付 2-微信支付 3-余额支付 4-支付宝支付 
	 * params string $auth_code 授权码
	 * return json  $result 同步结果 成功 array('result'=>1) ; 失败：array('result'=> -1 ,'error'=>'错误信息 ');
     */	 
	public function SyncExtendOrder(){
		$store_id = I('store_id');
		$age_limit = I('age_limit',1);
		$paytype = I('paytype'); 
		$order_sn = I('order_sn');
		$result = array();
		if(empty($store_id) || empty($age_limit) || empty($order_sn) || empty($paytype)){
			
			$result['result']  = -1;
			$result['error']  = '参数错误';
		}else{
			$order = M('vip_orders');
			$check = $order->where(array('orderSn'=>$order_sn))->find();
			if(!empty($check)){
				$result['result']  = -1;
				$result['error']  = '该订单已同步过了';
			}else{
				$m = M('stores');
				$w = array();
				$w['isdelete'] = 0;
				$w['store_id'] = $store_id;
				$storeinfo = $m->where($w)->find();
				if(empty($storeinfo)){
					$result['result']  = -1;
					$result['error']  = '该店铺不存在';
				}else{
					M('data_record')->where(array('order_sn'=>$order_sn))->save(array('open_result'=>'SUCCESS'));
					$datas = array();
					$datas['member_name'] = $storeinfo['member_name'];
					$datas['orderSn'] = $order_sn;
					$datas['packageid'] = $storeinfo['package_id'];
					$datas['account_id'] = $storeinfo['account_id'];
					$datas['store_id'] = $store_id;
					$cost_price = $this->getPackageCostPrice($age_limit,$storeinfo['package_id'],$storeinfo['operate_id'],$store_id); 
					$datas['cost_price'] = $cost_price;
					$datas['age_limit'] = $age_limit;
					$w2 = array();
					$w2['packageid'] = $storeinfo['package_id'];
					$w2['is_show'] =1;
					$w2['status'] =1;
					$marketinfo = M('package_list')->where($w2)->field('market_price,market_price2,market_price3')->find(); 
					if($storeinfo['is_try'] == 1){
						$news = M('shareholder_package_edit')->where(array('operate_id'=>$storeinfo['operate_id'],'package_id'=>$storeinfo['package_id'],'status'=>1))->find();
						if(!empty($news)){
							$marketinfo['market_price'] = $news['package_price'];
							$marketinfo['market_price2'] = $news['package_price2'];
							$marketinfo['market_price3'] = $news['package_price3'];
						}             
					} 	
					if($age_limit == 1){
						$market_price = $marketinfo['market_price'];
					}else if($age_limit == 2){
						$market_price = $marketinfo['market_price2'];
					}else if($age_limit == 3){
						$market_price = $marketinfo['market_price3'];
					}
					$isadmin = I('isadmin');
					if($isadmin == 1){
						$market_price = $cost_price;
					} 
					$datas['sale_price'] = $market_price;
					$actual_price = $market_price;
					$datas['actual_price'] = $actual_price;
					$shareholder_info = M('operate_shareholder')->where(array('shareholder_sn'=>$storeinfo['operation_number']))->find();
					if(!empty($shareholder_info)){
						$recommend_profit = ($actual_price-$cost_price)* $shareholder_info['recommend_rate']/100;
					}else{   
						$recommend_profit = 0;
					}       
					$datas['recommend_profit'] = $recommend_profit;    
					$datas['operate_profit'] = $market_price-$cost_price-$recommend_profit;
					$datas['tel'] = $storeinfo['lianxi_member_tel'];  
					$datas['recommend_code'] = $storeinfo['operation_number'];
					$datas['holder_id'] = $shareholder_info['id'];
					$datas['operate_id'] = $storeinfo['operate_id']; 
					$datas['up_level'] = $storeinfo['store_grade'];
					$datas['paytype'] = $paytype;
					$datas['status'] = 1;
					$datas['applytime'] = mktime();
					$datas['rechargetime'] = mktime();
					$datas['type'] = 1;
					$datas['is_create'] = 1;
					$datas['issettlement'] = 0;
					if($order_id = $order->add($datas)){
						$root_url = M('system_config')->where(array('status'=>1))->getField('root_url');
						$auth_code = M('system_config')->where(array('status'=>1))->getField('auth_code');   
						$sync_url = $root_url .'/index.php?m=api&c=Store&a=index&auth_code='.$auth_code;
						$rt = file_get_contents($sync_url); 
						$this->settlement_package_order($order_id);
						$this->checkOperateUplevel($storeinfo['operate_id']); 
						$result['result'] = 1;
					}else{
						$result['result']  = 1;
						$result['error']  = '同步订单失败';
					}
				}  
				 
			}	    
		} 
		echo json_encode($result,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		        
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
	 *获取套餐的最终成本价格
	 * param $age_limit int 年限
	 * param $package_id int 套餐id
	 * param $operate_id int 运营商id
	 * param $store_id int 店铺id
	 * return 套餐最终成本价
	 */   
	public function getPackageCostPrice($age_limit = '1',$package_id = '',$operate_id ='',$store_id=''){
		$packageinfo = M('package_list')->where(array('packageid'=>$package_id,'status'=>1))->find();
		$level = M('operate_center')->where(array('id'=>$operate_id))->getField('level');
		$config = M('operate_config')->where(array('status'=>1))->find();
		$discount = 10;
		$is_try = M('stores')->where(array('store_id'=>$store_id,'isdelete'=>0))->getField('is_try');
		if(empty($store_id) || $is_try == 1){ 
			
			if($level == 1){
				$discount = $config['first_discount'];
			}else if($level == 2){
				$discount = $config['secend_discount'];
			}else if($level == 3){
				$discount = $config['third_discount'];
			}else if($level == 4){
				$discount = $config['fourth_discount'];
			}else if($level == 5){
				$discount = $config['fifth_discount'];
			}else if($level == 6){
				$discount = $config['sixth_discount'];
			}
			
		}else{
			if($level == 1){
				$discount = $config['first_morediscount'];
			}else if($level == 2){
				$discount = $config['secend_morediscount'];
			}else if($level == 3){
				$discount = $config['third_morediscount'];
			}else if($level == 4){
				$discount = $config['fourth_morediscount'];
			}else if($level == 5){
				$discount = $config['fifth_morediscount'];
			}else if($level == 6){
				$discount = $config['sixth_morediscount'];
			}
		}
		
		if($age_limit == 1){
			$cost_price = $packageinfo['market_price']*$discount/10;
			$cost_price = ($cost_price < 0) ? $packageinfo['min_price'] : $cost_price;
			$cost_price = ($cost_price < $packageinfo['min_price']) ? $packageinfo['min_price'] :$cost_price;
		}else if($age_limit == 2){
			$cost_price = $packageinfo['market_price2']*$discount/10;
			$cost_price = ($cost_price < 0) ? $packageinfo['min_price2'] : $cost_price;
			$cost_price = ($cost_price > $packageinfo['min_price2']) ? $packageinfo['min_price2'] :$cost_price;
		}else if($age_limit == 3){
			$cost_price = $packageinfo['market_price3']*$discount/10; 
			$cost_price = ($cost_price < 0) ? $packageinfo['min_price3'] : $cost_price;
			$cost_price = ($cost_price < $packageinfo['min_price3']) ? $packageinfo['min_price3'] :$cost_price;
		}       
		return round($cost_price,2);
	}

	 
	/**
    *获取套餐价格
	* @params int package_id  套餐id
	* @params int store_id  店铺id
	* @params int operate_id  运营商id
	* @return  array  结果和最终价格
	*/	 
	public function get_package_price(){
		$package_id = I('packge_id');
		$store_id = I('store_id');
		$operate_id = I('operate_id');
		$age_limit = I('age_limit');
		$rt = array();
		if(empty($package_id) || empty($age_limit)){
			$rt['result'] = -1;
			$rt['desc'] = '参数错误'; 
		}
		else{ 
			$store = M('stores');
			$package = M('package_list');
			$edit = M('shareholder_package_edit');
			$w = array();
			$w['store_id'] = $store_id;
			$w['istry'] = 0; 
			$check = $store->where($w)->find();
			$final_price = 0;
			$w2 = array();
			$w2['packageid'] = $package_id;
			$w2['is_show'] =1;
			$w2['status'] =1;
			$marketinfo = $package->where($w2)->field('market_price,market_price2,market_price3')->find();
			if(empty($marketinfo)){
				$rt['result'] = -1;
				$rt['desc'] = '该套餐不存在';
				echo json_encode($rt,JSON_UNESCAPED_UNICODE);
				die();	
			}
			if($age_limit == 1){
				$market_price = $marketinfo['market_price'];
			}else if($age_limit == 2){
				$market_price = $marketinfo['market_price2'];
			}else{
				$market_price = $marketinfo['market_price3'];
			}
			
			if(empty($check)){
				$w3 = array();
				$w3['operate_id'] = $operate_id;
				$w3['package_id'] = $package_id;
				$w3['status'] = 1;
				$priceinfo = $edit->where($w3)->field('package_price,package_price2,package_price3')->find;
				if($age_limit == 1){
					$package_price = $priceinfo['package_price'];
				}else if($age_limit == 2){
					$package_price = $priceinfo['package_price2'];
				}else if($age_limit == 3){
					$package_price = $priceinfo['package_price3'];
				}
				$final_price = empty($package_price) ? $market_price : $package_price;
			}else{
				if($check['vip_endtime'] < mktime()){
					$final_price = $market_price;
				}else{
					$final_price = round($market_price*(($check['vip_endtime']-mktime())/($check['vip_endtime']-$check['recharge_time']+1)),2);
				}
				$rt['result'] = 1;
				$rt['price'] = $final_price;	
			}
			  
		}   
		echo json_encode($rt,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
		die;
	}

	
	
	/*检查支付，支付成功改变订单状态，并开户*/
	public function checkPay(){
		$ordersn = I('ordersn');
		$order = M('vip_orders');
		$order_info = $order->where(array('orderSn'=>$ordersn))->find();
		$rt = array();
		if(empty($order_info)){ 
			$rt['result'] = -1;
			$rt['desc'] = '该订单不存在';
		}else if($rt['status'] == 1){
			$rt['result'] = -1;
			$rt['desc'] = '该订单已经支付';
		}else{
			$order->where(array('id'=>$order_info['id']))->save(array('status'=>1)); 
			$sync_url = M('system_config')->where(array('status'=>1))->getField('sync_url');
			$root_url = M('system_config')->where(array('status'=>1))->getField('root_url');
			$params2 = array(); 
			$params2['account_id'] = $order_info['account_id'];
			$params2['opentype'] = 1;
			$params2['platform_type'] = 2;    
			$url2 = "http://".$sync_url."/xxapi/index.php?act=operate_openaccount&op=openStore";
			$json2 =  $this->postCurl($url2,$params2);      			
			$rt2 = json_decode($json2,true);    
			if($rt2['result'] == '0' || $rt2['result'] == '-10' || $rt2['result'] == '1001'){
				M('data_record')->where(array('order_sn'=>$ordersn))->save(array('open_result'=>'SUCCESS'));		
				$newdata = array();
				$newdata['rechargetime'] = mktime();  
				$newdata['is_create'] = 1;
				$newdata['store_id'] = $rt2['datas'];
				M('vip_orders')->where(array('id'=>$order_info['id']))->save($newdata);
				$this->settlement_package_order($order_info['id']); 
				$this->checkOperateUplevel($order_info['operate_id']);	 
				$auth_code = M('system_config')->where(array('status'=>1))->getField('auth_code');   
				$sync_url = $root_url .'/index.php?m=api&c=Store&a=index&auth_code='.$auth_code;
				$x = 1;     
				do {
					$result = file_get_contents($sync_url); 
				} while ($x<=3 && $result['result'] != 0 && $result['result'] != 1);   
				if($rt2['result'] == 0){
					$rt['result'] = 1;
				}else{ 
					$rt['result'] = -1;
					$rt['desc'] = '新增店铺成功,发送短信失败';
				}
			}else{ 
				$rt['result'] = -1;
				$rt['desc'] = $rt2['error'];
				
			}	  
			   
		}
		echo json_encode($rt,JSON_UNESCAPED_UNICODE);
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
 
	private static function getdataCurl($url ,$params = array(), $times = 1)
	{
		$url = $url."&mall_db=".$params['mall_db']."&auth_code=".$params['auth_code']."&data_type=".$params['data_type']."&data_version=".$params['data_version'];   		    
        //初始化curl        
       	$ch = curl_init(); 
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		//设置header
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		//运行curl
        $data = curl_exec($ch);
        $curl_errno = curl_errno($ch);
		//返回结果
		if($curl_errno=='0'){
			curl_close($ch);
			return $data;
		}else{  
            curl_close($ch);
            $resultdata['result'] = -1;
			$resultdata['desc'] = "curl出错，错误码:".$curl_errno;
			return json_encode($resultdata, JSON_UNESCAPED_UNICODE);
        }
		
	}
	
	/*保存运营商申请信息*/ 
	
	public function  ajax_operate_apply(){
		$data = I();
		$verify = new \Think\Verify();
		$tip = $verify->check($data['sendVerifyCode'], 1);
		if(!$tip){
			$rt['result'] = -1;
			$rt['desc'] = '验证码错误';
			echo 'callbacks('.json_encode($rt,JSON_UNESCAPED_UNICODE).')';
			exit;
		}  
		$params = array();   
		$params['operate_name'] = $data['sendName'];
		$params['link_name'] = $data['sendMan'];
		$params['link_tel'] = $data['sendPhone'];
		$params['link_qq'] = $data['sendQq'];
		$params['e_mail'] = $data['sendEmail'];
		$params['remark'] = $data['sendContent'];
		$params['addtime'] = mktime();
		$m = M('operate_apply');
		$check = $m->add($params);
		$rt = array();
		if($check){    
			$rt['result'] = 1;
		}else{
			$rt['result'] = -1;
			$rt['desc'] = 'auth_code参数错误';
		} 
		echo 'callbacks('.json_encode($rt,JSON_UNESCAPED_UNICODE).')';
	} 
	 
	public function verify()
    { 
        ob_clean();
        $verify = new \Think\Verify();
        $verify->useCurve=false;
        $verify->length=4;
        $verify->codeSet='123456789';
        $verify->entry(1);
    }
	
	public function getOrderInfo(){
		$order_sn = I('order_sn');
		$m = M('vip_orders');
		$w = array(); 
		$w['orderSn'] = $order_sn;
		$w['status'] = 0;
		$info = $m->where($w)->find();
		echo json_encode($info,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
	}
	
	
}