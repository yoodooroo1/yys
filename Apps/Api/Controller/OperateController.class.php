<?php
namespace Api\Controller;
use Think\Controller;
class OperateController extends Controller {
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
}