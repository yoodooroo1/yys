<?php
namespace Home\Controller;
use Think\Controller;
class SyncController extends Controller {
	/*同步会员*/
    public function sync_members(){
		$m = M('store_config');
		$rt = $m->find();
		$url = "http://121.40.130.78/xxapi/index.php?act=sync&op=sync_members";  	
		$inputArr = $rt;
		$inputArr['data_version'] = $rt['member_version'];
		$jsons = self::getdataCurl($url ,$inputArr); 
		$datas = json_decode($jsons,true);
		if($datas['result']==0)
		{
			$member_version = $datas['data_version'];
			$count = $datas['data_count'];
			$result = $datas['data'];
			$m = M('members');
			foreach($result as $da)
			{
				$w = array();
				$w['member_name'] = $da['member_name'];
				$jg = $m->where($w)->find();
				if(empty($jg))
				{
					$m->add($da);
				}
				else{	
					$m->where($w)->save($da);
				}	
			}  
			$config = M('store_config');
			$arr = array();
			$arr['member_version'] = $member_version;
			$config->where(array('id'=>'1'))->save($arr);
			\Think\Log::write('会员同步完成，共'.$count.'个会员完成同步','INFO');
		}          
		elseif($datas['result']==1){
			\Think\Log::write('暂无会员信息需要同步','INFO');
		}  
		elseif($datas['result']==-1){
			\Think\Log::write('同步会员错误：'.$datas['error'],'WARN');
		}
		elseif($datas['result'] == 2 || $datas['result'] == 3){
			\Think\Log::write('同步会员错误：'.$datas['desc'],'WARN');
		} 
    }  

 
	/*同步订单*/
    public function sync_orders(){
		$m = M('store_config');  
		$rt = $m->find();  
		$url = "http://121.40.130.78/xxapi/index.php?act=sync&op=sync_orders";    	
		$inputArr = $rt;  
		$inputArr['data_version'] = $rt['order_version'];
		$jsons = self::getdataCurl($url ,$inputArr); 
		$datas = json_decode($jsons,true);
		if($datas['result']==0)
		{
			$order_version = $datas['data_version'];
			$count = $datas['data_count'];
			$result = $datas['data'];
			$m = M('orders');
			foreach($result as $da)
			{
				$w = array();
				$w['order_id'] = $da['order_id'];
				$jg = $m->where($w)->find();
				if(empty($jg))
				{
					$m->add($da);
				}
				else{	 
					$m->where($w)->save($da);
				}	
			}  
			$config = M('store_config');
			$arr = array();
			$arr['order_version'] = $order_version;
			$config->where(array('id'=>'1'))->save($arr);  
			\Think\Log::write('订单同步完成，共'.$count.'个订单完成同步','INFO');
		}          
		elseif($datas['result']==1){
			\Think\Log::write('暂无订单信息需要同步','INFO');
		}  
		elseif($datas['result']==-1){
			\Think\Log::write('同步订单错误：'.$datas['error'],'WARN');
		}
		elseif($datas['result'] == 2 || $datas['result'] == 3){
			\Think\Log::write('同步订单错误：'.$datas['desc'],'WARN');
		} 
    }  
	
	/**
	 * 以get方式提交数据到对应的接口url 
	 * @param string $params  需要get的数据
	 * @param string $url  url
	 * @return string $data 返回请求结果
	 */
	private static function getdataCurl($url ,$params = array(), $times = 1)
	{
		$url = $url."&mall_db=".$params['member_name']."&auth_code=".$params['auth_code']."&data_type=0&data_version=".$params['data_version'];   		
        //初始化curl        
       	$ch = curl_init(); 
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
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
			$resultdata['error'] = "curl出错，错误码:".$curl_errno;
			return json_encode($resultdata, JSON_UNESCAPED_UNICODE);
        }
		
	}
}