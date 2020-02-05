<?php
namespace Api\Controller;
use Think\Controller;
class MemberController extends Controller {
    public function index(){
		header("Content-Type: text/html;charset=utf-8");
		$mall_db = I('mall_db');
		$auth_code=I('auth_code');
		$data_type = I('data_type',0);
		$m = M('store_config');       
		$where = array();
		$where['member_name'] = $mall_db;
		$member_version = $m->where($where)->getField('member_version');  
		$sync_url = $m->where($where)->getField('sync_url');    
		$url = "http://".$sync_url."/xxapi/index.php?act=sync&op=sync_members";  	
		$inputArr = array();   
		$inputArr['data_version'] = $member_version;	
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
			$member_version = $datas['data_version'];
			$count = $datas['data_count'];
			$result = $datas['data'];
			$m = M('members');
			$wallet = M('member_wallet');
			$contribute_record = M('contribute_record');
			$recommend_rate = M('settlement_config')->where(array('status'=>1))->getField('recommend_rate');
			$founder_rate = M('settlement_config')->where(array('status'=>1))->getField('founder_rate'); 
			$center = M('operate_center');
			//默认运营商ID
			$default_operate_id = $center->where(array('main_store'=>1,'status'=>1))->getField('id');
			/*推荐人的数组*/  
			$recommend_array = array();
			foreach($result as $key=>$da)
			{
				$da['shield'] = empty($da['shield']) ? 0 : 	$da['shield'];	
				$w = array();
				$w['member_id'] = $da['member_id'];
				$jg = $m->where($w)->find();
				if(empty($jg))
				{
					$data = array();  
					$data['member_id'] = $da['member_id'];
					$w2  = array();
					$w2['member_id'] = $da['member_id'];
					$jg3 = $m->where($w2)->find();
					if(empty($jg3)){  
						try{
							$m->add($da);
						}catch(\Exception $e){
							\Think\Log::write('插入数据库出错，错误信息:'.$e->getMessage().' 会员ID:'.$da['member_id'],'ERROR');
						}    
					}  
					if(!empty($da['recommend_id'])){
						$recommend_array[$key]['recommend_id'] = $da['recommend_id'];
						$recommend_array[$key]['member_id'] = $da['member_id'];
					}
					
					$recommend_id = $da['recommend_id'];
					$operate_id = $center->where(array('member_id'=>$recommend_id,'status'=>1))->getField('id');
					while(!empty($recommend_id) && empty($operate_id)){
						$recommend_id = $m->where(array('member_id'=>$recommend_id))->getField('recommend_id');
						$operate_id = $center->where(array('member_id'=>$recommend_id,'status'=>1))->getField('id');
					}
					if(empty($operate_id)){
						$operate_id = $default_operate_id;  
					}
					$m->where(array('member_id'=>$da['member_id']))->save(array('operate_id'=>$operate_id));	
					
					$jg2 = $wallet->where($data)->find();
					if(empty($jg2)){
						$wallet->add($data);
					}   	
				}    
				else{
					unset($da['recommend_name']); 
					unset($da['recommend_id']); 
					$m->where($w)->save($da);  
				}	
			}
			foreach($recommend_array as $recommend_mid){
				$rt = array();
				$rt = $m->where(array('member_id'=>$recommend_mid['recommend_id']))->find();
				if(!empty($rt)){
					$w = array();          
					$w['lm_members.member_id'] = $recommend_mid['recommend_id'];
					$member_rateinfo = $m->join('LEFT JOIN lm_package_list ON lm_members.package_id=lm_package_list.packageid')->where($w)->field('lm_members.original_member,lm_package_list.contribute_rate')->find(); 
					if($member_rateinfo['original_member'] == 1){
						$member_rate = $founder_rate;
					}else{
						$member_rate = $member_rateinfo['contribute_rate']; 
					}
					$member_rate = empty($member_rate) ? 1 : $member_rate;
					$w2 = array();
					$w2['member_id'] = $recommend_mid['recommend_id']; 
					$w2['children_uid'] = $recommend_mid['member_id']; 
					$w2['datafrom'] = 1;
					$check = $contribute_record->where($w2)->find();
					if(empty($check)){
						$recommenddata = array();   
						$recommenddata['member_id'] = $recommend_mid['recommend_id']; 
						$recommenddata['children_uid'] = $recommend_mid['member_id'];  
						$recommenddata['datafrom'] = 1;   
						$recommenddata['value'] =  $recommend_rate * $member_rate;
						$recommenddata['desc'] = '通过直推会员获得 '.$recommenddata['value'].' 成长值';
						$recommenddata['status'] = '1';  
						$recommenddata['addtime'] = mktime();   
						if($contribute_record->add($recommenddata)){
							M('member_wallet')->where(array('member_id'=>$recommend_mid['recommend_id']))->setInc('contribute_value',$recommenddata['value']);  		    				
						}
					}	 
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
			\Think\Log::write('同步会员错误：'.$datas['desc'],'WARN');
		}
		elseif($datas['result'] == 2 || $datas['result'] == 3 || $datas['result'] == 4){
			\Think\Log::write('同步会员错误：'.$datas['desc'],'WARN');
		}
		    
		echo json_encode($datas,JSON_UNESCAPED_UNICODE); 
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