<?php
namespace Api\Controller;
use Think\Controller;
class OrderController extends Controller {
    public function index(){
		header("Content-Type: text/html;charset=utf-8");	
		$mall_db = I('mall_db');
		$auth_code=I('auth_code');
		$data_type = I('data_type',0);
		$m = M('store_config');  
		$where = array();
		$where['member_name'] = $mall_db;
		$order_version = $m->where($where)->getField('order_version');  
		$sync_url = $m->where($where)->getField('sync_url');      
		$url = "http://".$sync_url."/xxapi/index.php?act=sync&op=sync_orders";  	
		//$url = "http://121.40.130.78/xxapi/index.php?act=sync&op=sync_orders";    	
		$inputArr = array();  
		$inputArr['data_version'] = $order_version;
		$inputArr['mall_db'] = $mall_db;
		$inputArr['auth_code'] = $auth_code;  
		$inputArr['data_type'] = $data_type; 
		F('t',1);
		$jsons = self::getdataCurl($url ,$inputArr); 
		if($data_type == 0)
		{
			$datas = json_decode($jsons,true);
		}     
		else{  
			$datas = $this->xmltoarray($jsons);
			$datas['data'] = $datas['data']['item'];
		}
		
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
					$parents = $this->getNormal_Three_Parents($da['buyer_id']);
					$da['p1'] = empty($parents['p1'])? 0 : $parents['p1'];
					$da['p2'] = empty($parents['p2'])? 0 : $parents['p2'];
					$da['p3'] = empty($parents['p3'])? 0 : $parents['p3'];
					try{
						$m->add($da);
					}catch(\Exception $e){
						\Think\Log::write('插入数据库出错，错误信息:'.$e->getMessage().' 订单ID:'.$da['order_id'],'ERROR');
					}     
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
			\Think\Log::write('同步订单错误：'.$datas['desc'],'WARN');
		}  
		elseif($datas['result'] == 2 || $datas['result'] == 3 || $datas['result'] == 4){
			\Think\Log::write('同步订单错误：'.$datas['desc'],'WARN');
		}    
		echo  json_encode($datas,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);          
    }

	/*获取上三级的父级（不紧缩）*/
	private function getNormal_Three_Parents($member_id){
		$m =M('members');
		$w1 = array();
		$w1['member_id'] = $member_id;
		$w1['shield'] = 0;
		$p1 = $m->where($w1)->getField('recommend_id');
		if(empty($p1)){
			$p2 = 0;
			$p3 = 0;
		}
		else{
			$w2 = array();
			$w2['member_id'] = $p1;
			$w2['shield'] = 0;
			$p2 = $m->where($w2)->getField('recommend_id');
			if(empty($p2)){
				$p3 = 0;
			}
			else{
				$w3 = array();
				$w3['member_id'] = $p2;
				$w3['shield'] = 0;
				$p3 = $m->where($w3)->getField('recommend_id');
			}
		}
		$rt = array();  
		$rt['p1'] = $p1;
		$rt['p2'] = $p2;
		$rt['p3'] = $p3;
		return $rt;
	}
	
	/**
	 * 以get方式提交数据到对应的接口url 
	 * @param string $params  需要get的数据
	 * @param string $url  url
	 * @return string $data 返回请求结果
	 */
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
	
	public function xmltoarray( $xml )
    {
        $arr = $this->xml_to_array($xml);
        $key = array_keys($arr);
        return $arr[$key[0]];
    }
	
	public function xml_to_array( $xml )
    {
        $reg = "/<(\\w+)[^>]*?>([\\x00-\\xFF]*?)<\\/\\1>/";
        if(preg_match_all($reg, $xml, $matches))
        {
            $count = count($matches[0]);
            $arr = array();
            for($i = 0; $i < $count; $i++)
            {
                $key = $matches[1][$i];
                $val = $this->xml_to_array( $matches[2][$i] );  // 递归
                if(array_key_exists($key, $arr))
                {
                    if(is_array($arr[$key]))
                    {
                        if(!array_key_exists(0,$arr[$key])) 
                        {
                            $arr[$key] = array($arr[$key]);
                        }
                    }else{
                        $arr[$key] = array($arr[$key]);
                    }
                    $arr[$key][] = $val;
                }else{
                    $arr[$key] = $val;
                }
            }
            return $arr;
        }else{
            return $xml;
        }
    }
}