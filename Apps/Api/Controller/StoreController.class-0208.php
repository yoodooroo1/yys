<?php
namespace Api\Controller;
use Think\Controller;
class StoreController extends Controller {
    public function index(){
		header("Content-Type: text/html;charset=utf-8");
       		
		$mall_db = I('mall_db');
		$auth_code=I('auth_code');
		$data_type = I('data_type',0);
		$m = M('store_config');  
		$where = array();
		$where['member_name'] = $mall_db;
		$store_version = $m->where($where)->getField('store_version');  
		$sync_url = $m->where($where)->getField('sync_url');      
		$url = "http://".$sync_url."/xxapi/index.php?act=sync&op=sync_stores";  	
		//$url = "http://121.40.130.78/xxapi/index.php?act=sync&op=sync_orders";    	
		$inputArr = array();  
		$inputArr['data_version'] = $store_version;
		$inputArr['mall_db'] = $mall_db;
		$inputArr['auth_code'] = $auth_code;  
		$inputArr['data_type'] = $data_type;  
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
			$store_version = $datas['data_version'];
			$count = $datas['data_count'];
			$result = $datas['data'];
			$m = M('stores');
			$operate_center = M('operate_center');
			foreach($result as $da)
			{
				$recommend_name = $da['recommend_name'];
				unset($da['recommend_name']);
				$operate_store_id = $operate_center->where(array('member_name'=>$recommend_name,'status'=>1))->getField('store_id');
				if(!empty($operate_store_id)){
					$da['operate_store_id'] = $operate_store_id;
				}
				$w = array();
				$w['store_id'] = $da['store_id'];
				$jg = $m->where($w)->find();
				if(empty($jg))
				{
					if($da['main_store'] == 1){
						$w2  =array();
						$w2['store_id'] = $da['store_id'] ;
						$w2['member_id'] = $da['member_id'] ;
						$r = M('operate_center')->where($w2)->find();
						if(empty($r)){
							$data = array();
							$data['store_id'] = $da['store_id'];
							$data['store_name'] = $da['store_name'];
							$data['member_id'] = $da['member_id'];
							$data['member_name'] = $da['member_name'];
							$data['main_store'] = 1;
							$data['addtime'] = mktime();   	
							M('operate_center')->add($data);
						}
						
					}    
					$m->add($da);
				}    
				else{	 
					$m->where($w)->save($da);
				}	
			}  
			/*运营中心为空默认为主商城*/
			$w3 = array();	
			$w3['operate_center'] = null;
			$store2 = $m->where($w3)->select();
			$main_storeid = $m->where(array('main_store'=>'1'))->getField('store_id');
			foreach($store2 as $s2){
				$m->where(array('store_id'=>$s2['store_id']))->save(array('operate_store_id'=>$main_storeid));
			}
			$config = M('store_config');
			$arr = array();     
			$arr['store_version'] = $store_version;
			$config->where(array('id'=>'1'))->save($arr);  
			\Think\Log::write('店铺同步完成，共'.$count.'个店铺完成同步','INFO');
		}          
		elseif($datas['result']==1){
			\Think\Log::write('暂无店铺信息需要同步','INFO');
		}  
		elseif($datas['result']==-1){  
			\Think\Log::write('同步店铺错误：'.$datas['desc'],'WARN');
		}
		elseif($datas['result'] == 2 || $datas['result'] == 3 || $datas['result'] == 3){
			\Think\Log::write('同步店铺错误：'.$datas['desc'],'WARN');
		}    
		echo  json_encode($datas,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
		        
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