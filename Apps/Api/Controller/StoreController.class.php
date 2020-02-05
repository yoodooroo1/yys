<?php
namespace Api\Controller;
use Think\Controller;
class StoreController extends Controller {
    public function index(){ 
		header("Content-Type: text/html;charset=utf-8");
		$auth_code=I('auth_code');
		$data_type = I('data_type',0);
		$m = M('system_config');  
		$where = array(); 
		$where['status'] = 1;
		$system_info = $m->where($where)->find();
		$store_version = $system_info['store_version'];
		$sync_url = $system_info['sync_url']; 
		$code = $system_info['auth_code'];
		if($code != $auth_code){
			$rt = array();
			$rt['result'] = -1;
			$rt['desc'] = 'auth_code参数错误';
			echo json_encode($rt,JSON_UNESCAPED_UNICODE);
			die();
		}
		$url = "http://".$sync_url."/xxapi/index.php?act=operate&op=sync_stores";  	  
		$inputArr = array();  
		$inputArr['data_version'] = $store_version;  
		$inputArr['auth_code'] = $auth_code;  
		$inputArr['data_type'] = $data_type;
		$inputArr['encrypt_code'] = md5($store_version.$auth_code);	
        
		$jsons = self::getdataCurl($url ,$inputArr);  
		if($data_type == 0)
		{     
			$datas = json_decode($jsons,true);
		}    
		else{
			$datas = $this->xmltoarray($jsons);
			$datas['data'] = $datas['data']['item'];
		}
		if($datas['result']=='0')
		{ 
			$store_version = $datas['data_version'];
			$count = $datas['data_count'];
			$result = $datas['data'];
			$m = M('stores');
			//$operate_center = M('operate_center');
			foreach($result as $da)
			{  
				$w = array();
				$w['store_id'] = $da['store_id'];
				$jg = $m->where($w)->find();
				if(empty($jg))
				{  
					$m->add($da);  
				}    
				else{	 
					$m->where($w)->save($da);
				}	
			}  
			$config = M('system_config');
			$arr = array();     
			$arr['store_version'] = $store_version;
			$config->where(array('status'=>'1'))->save($arr);  
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
		 
		$try = 0;     
        $curl_errno = -1;
		do{
			$url = $url."&encrypt_code=".$params['encrypt_code']."&auth_code=".$params['auth_code']."&data_type=".$params['data_type']."&data_version=".$params['data_version'];
			//初始化curl        
			$ch = curl_init();  
			//设置超时 
			curl_setopt($ch, CURLOPT_TIMEOUT, 500);
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
			curl_close($ch);
		} while ($curl_errno > 0 && ++$try < 3);
		
		//返回结果
		if($curl_errno=='0'){ 
			return $data;
		}else{  
           
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