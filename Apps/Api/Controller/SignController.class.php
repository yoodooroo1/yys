<?php
namespace Api\Controller;
use Think\Controller;
class SignController extends Controller {
    public function index(){ 
		header("Content-Type: text/html;charset=utf-8");
       		
		$mall_db = I('mall_db');
		$auth_code=I('auth_code');
		$data_type = I('data_type',0);
		$m = M('store_config');  
		$where = array();
		$where['member_name'] = $mall_db;
		$sign_version = $m->where($where)->getField('sign_version'); 
		$sync_url = $m->where($where)->getField('sync_url');      
		$url = "http://".$sync_url."/xxapi/index.php?act=sync&op=sync_signs";  	
		//$url = "http://121.40.130.78/xxapi/index.php?act=sync&op=sync_orders";    	
		$inputArr = array();  
		$inputArr['data_version'] = $sign_version;
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
			$sign_version = $datas['data_version'];
			$sign_rate = M('settlement_config')->where(array('status'=>1))->getField('sign_rate');
			$founder_rate = M('settlement_config')->where(array('status'=>1))->getField('founder_rate');
			$count = $datas['data_count'];  
			$result = $datas['data'];
			$sign = M('signs');
			$members = M('members');
			$contribute_record = M('contribute_record');
			$member_wallet = M('member_wallet');
			$member_wallet_record= M('member_wallet_record');
			foreach($result as $da)
			{    
				$jg = $sign->where(array('sign_id'=>$da['sign_id']))->find();   
				if(empty($jg)){
					$sign->add($da);  
					if(($da['isdelete'] == 0) && ($da['misdelete']== 0)){				
						$w = array();      
						$w['lm_members.member_id'] = $da['member_id'];
						$member_rateinfo = $members->join('LEFT JOIN lm_package_list ON lm_members.package_id=lm_package_list.packageid')->where($w)->field('lm_members.original_member,lm_package_list.contribute_rate')->find(); 
						if($member_rateinfo['original_member'] == 1){
							$member_rate = $founder_rate;
						}else{
							$member_rate = $member_rateinfo['contribute_rate']; 
						}   
						$member_rate = empty($member_rate) ? 1 : $member_rate;
						$signdata = array();
						$signdata['member_id'] = $da['member_id']; 
						$signdata['sign_id'] = $da['sign_id'];  
						$signdata['datafrom'] = 3;   
						$signdata['value'] = $da['sign_score'] * $sign_rate * $member_rate;
						$signdata['desc'] = '通过签到获得 '.$signdata['value'].' 成长值';
						$signdata['status'] = '1';  
						$signdata['addtime'] = mktime();
						if($contribute_record->add($signdata)){
							$member_wallet->where(array('member_id'=>$da['member_id']))->setInc('contribute_value',$signdata['value']); 
						}    
					}	 
				}else{            
					$sign->where(array('sign_id'=>$da['sign_id']))->save($da);
				} 
			}   
			$config = M('store_config');
			$arr = array();     
			$arr['sign_version'] = $sign_version;
			$config->where(array('id'=>'1'))->save($arr);  	
			\Think\Log::write('会员签到同步完成，共'.$count.'个会员签到完成同步','INFO');
		}          
		elseif($datas['result']==1){
			\Think\Log::write('暂无会员签到信息需要同步','INFO');
		}  
		elseif($datas['result']==-1){  
			\Think\Log::write('同步会员签到错误：'.$datas['desc'],'WARN');
		}
		elseif($datas['result'] == 2 || $datas['result'] == 3 || $datas['result'] == 3){
			\Think\Log::write('同步会员签到错误：'.$datas['desc'],'WARN');
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