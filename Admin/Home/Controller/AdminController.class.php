<?php
namespace Home\Controller;
use Think\Controller;
/**
 * XUNXIN PC 后台管理 公共根文件
 * ============================================================================
 * 版权所有 2005-2010 厦门微聚点科技有限公司，并保留所有权利。
 * 网站地址: http://www.vjudian.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: youyan $
 * $Id: AdminController.class.php
 */
class AdminController extends Controller
{
    public function _initialize()
    {
		   
        // 启动session 如果session默认开启注释掉这行代码
        // @session('[start]');
       header("Content-Type:text/html;Charset=utf-8"); 
        // 判断是否存在session
        if (!session('loginname') || !session('adminid')) {
            // 检查cookie是否存在
            if (cookie('loginname') && cookie('password')) {
                // 如果存在cookie 设置session
                $result = $this->getAdminInfo(cookie('loginname'),cookie('password'));
                if (! $result) {
                    $this->go_login();
					exit;					
                }
            } else {
                if (! $this->check_fun()) {
                    $this->go_login();
					exit;
                } else {
                    $url = $this->get_before_url();
                    if (! empty($url)) {
                        // 检查调转过来的URL
                        $this->check_url($url);
                    } else {
                        $this->go_login();
						exit;
                    }
                }
            }
        }else{
			 $admin_priv = session('admin_priv');
			 if(empty($admin_priv)){
				$admin_priv = M('admin')->where(array('loginname'=>session('loginname')))->getField('role');
                session('admin_priv',$admin_priv);
			}
		}  
		//$this->assign('admin_priv',session('admin_priv'));
    }   
     
    /**
     * 通过这用户名和密码
     * 获得管理员信息如果存在还要设置session [name] [id]
     * 返回$result
     * 如果$result=0 没获得该数据
     * 如果$result=1 有该数据
     * 用到的表xunxin_member ,xunxin_store 如果member 表中 有数据 且
     * m.member_id=s.member_id m.member_name=s.member_name 存在
     * 则找到store_id
     */
    private function getAdminInfo($loginname, $password)
    {    
        $admin = M('admin');
		$w = array();
		$w['loginname'] = $loginname;
		$w['password'] =md5($password);
		$result = $admin->where($w)->find();
        // 如果存在设置session
        if ($result) {
            session('loginname', $loginname);
            session('adminid', $result['id']);
            return true;
        }
        return false;   
    }

    /**
     * 获得跳转过来的路径
     */
    private function get_before_url()
    {
        $url = $_SERVER['HTTP_REFERER'];
        if (empty($url)) {
            return false;
        }
        $urlarr = parse_url($url);
        $scheme = trim($urlarr['scheme']);
        $host = trim($urlarr['host']);
        $path = trim($urlarr['path']);
        $url = strtolower($scheme . '://' . $host . $path);
        return $url;
    }

    /**
     * 检查路径 未实现
     *
     * @param unknown $url            
     */
    private function check_url($url)
    {
        // return false;
        $this->go_login();
    }

    /**
     * 跳转到登入界面
     */
    public function go_login()
    {
        // 具体日后完善 
        	// $url = ADMIN_URL .'/admin.php/Home/Auth/login';		
	     $this->redirect("Auth/login");
       // header("Location:$url");
         exit();          
    }   

    /**
     * 检查是否是允许的方法
     * 1.登录方法 2 忘记密码方法3 重设密码方法
     * 如果是允许的方法返回true 否则返回false
     */
    private function check_fun()
    {
        $allow_action = array(
            "login",
            "restpwd",
            "forgetpsw"
        );
        
        if (in_array(ACTION_NAME, $allow_action)) {
            return true;
        }
        return false;
    }

    /**
     * 出现方法未找到是调用
     * 调用服务器繁忙界面
     * 或则出现路径错误界面提示（未实现）
     */
    protected function _empty()
    {}
    
    
    /**
     * 检查权限
     * 目前主要检查是否是管理员
     * 有权限返回true
     * 没有返回false
     */
    protected function checkAuth(){
        $hasAuth=session('admin_priv');
        if($hasAuth=='0'){
           return true;
        }      
        else{  
			return false;
        }
	}
    /**
     * 获得表名称
     * @param unknown $tables
     * @return string
     */
    public function tables($tables)
    {
        $prefix = C('DB_PREFIX');
        return $prefix . $tables;
    }

    protected function all_insert($name = '', $back = '/index')
    {
        $name = $name ? $name : MODULE_NAME;
        $db = D($name);
        if ($db->create() === false) {
            $this->error($db->getError());
        } else {
            $id = $db->add();
            if ($id) {
                $this->success('操作成功', U(MODULE_NAME . $back));
            } else {
                $this->error('操作失败', U(MODULE_NAME . $back));
            }
        }
    }
    
    // 请求接口
    private function request_post_xunxin($param = '')
    {   
        //if(C('IS_TEST')){
            $apiUrl = "http://api.cnt.xunxin.devp.com.cn/xxapi/index.php";
       // }ELSE{   
          //  $apiUrl = "http://api.duinin.com/xxapi/index.php";
       // }
       
        return $this->request_post($apiUrl, $param);
    }
    // 请求接口
    protected function request_post($url = '', $param = '')
    {
      // die($url.$param);
        if (empty($url) || empty($param)) {
            return false;
        }
        
        $try = 0;
        $curl_errno = - 1;
        do {
            $postUrl = $url;
            $curlPost = $param;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $postUrl);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
            $data = curl_exec($ch);
            $curl_errno = curl_errno($ch);
            curl_close($ch);
        } while ($curl_errno > 0 && ++ $try < 3);
      
        return $data;
    }

    /**
     * 设置API的传递参数
     *
     * @return boolean
     */
    private function getApiParams($param = array())
    {
	    
        $key = $this->getApiToken();
        if (empty($key)) {
            return false;
        }
        $params = array(
            "user_type" => 'maller',
            "store_id" => session('store_id'),
            "key" => $key,
            "client" => "web"
        );
        foreach ($param as $k => $v) {
            $params[$k] = $v;
	}
	
        // 请求第一种方式
        $data = "";
        foreach ($params as $k => $v) {
            $data .= "$k=" . urlencode($v) . "&";
	}
	
	$data = substr($data, 0, - 1);

        return $data;
    }

    /**
     * 获得要调用的API的通行证
     */
    private  function getApiToken()
    {
        $Token = M('Mb_user_token');
        $where = array();
        $where['member_id'] = session('member_id');
        $key = $Token->where($where)->find();
        return $key['token'];
    }
    
    /**
     * xunxin接口返回值
     */
    protected function getReturnInfo($param){
	    //string
	    $data = $this->getApiParams($param);
	    
        $msg = $this->request_post_xunxin($data);
        $returnInfo=json_decode($msg,true);
        if($returnInfo['result'] == 0){
            $returnInfo['data']='操作成功';
        }
        return $returnInfo;
        
    }

    /**
     * 获得其他界面的补充条件
     * 
     * @param unknown $condition            
     * @param unknown $where            
     */
    protected function getOtherWhere($where,$condition)
    {
        if (!empty($condition) && is_array($condition)) {
            
            foreach ($condition as $k => $v) {
                $where[$k] = $v;
            }
        }
        return $where;
    }

    /**
     * 上传图片API
     */
    protected  function uploadImgApi($params=array()){
        $url = "http://file.duinin.com/upload.php";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);   
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $response = curl_exec($ch);
		
		 /* if($response === FALSE ){
		 var_dump(curl_error($ch));
		 exit;
		 } */ 
        return  $response;
        
    }
    /**
     * 将数组转化成post
     * @param unknown $params
     * @return string
     */
    private function parseArray($params){
        // 请求第一种方式
        $data = "";
        foreach ($params as $k => $v) {
            $data .= "$k=" . urlencode($v) . "&";
        }
        $data = substr($data, 0, - 1);
        return $data;
    }

    /**
     * 获得该商店的员工、会员、商品。。。上限
     */
    private function getLimitNum()
    {
        $arr = array();
        $Store = M('Store');
        $s_where = array();
        $s_where['store_id'] = session('store_id');
        $store_info = $Store->where($s_where)->find();
        if (empty($store_info)) {
            // $this->go_login();
        }
        $sellerNum = $store_info['extra_sellernum'];
        $memberNum = $store_info['extra_membernum'];
        $goodsNum = $store_info['extra_goodsnum'];
        $advertiseNum = $store_info['extra_advertisenum'];
        $goodscodeNum = $store_info['extra_goodscode'];
        $storegrade = $store_info['store_grade'];
        
        $Grade = M('Mb_storegrade');
        $g_where = array();
        $g_where['store_grade'] = $storegrade;
        $g_where['channelid'] = 0;
        $grade_info = $Grade->where($g_where)->find();
        
        $sellerNum += $grade_info['seller_num'];
        $memberNum += $grade_info['member_num'];
        $goodsNum += $grade_info['goods_num'];
        $advertiseNum += $grade_info['advertise_num'];
        $goodscodeNum += $grade_info['goods_code'];
        $arr['seller_num'] = $sellerNum;
        $arr['member_num'] = $memberNum;
        $arr['goods_num'] = $goodsNum;
        $arr['advertise_num'] = $advertiseNum;
        $arr['goods_code'] = $goodscodeNum;
        return $arr;
    }

    /**
     * 设置各种上限的session
     */
    private function setLimitNumSession()
    {
            $limit = $this->getLimitNum();
            session('seller_num', $limit['seller_num']);
            session('member_num', $limit['member_num']);
            session('goods_num', $limit['goods_num']);
            session('advertise_num', $limit['advertise_num']);
            session('goods_code', $limit['goods_code']);

    }

    /**
     * 获得session
     * @param string $flag
     */
    public function getLimiteNumSession($flag = false)
    {
        if ($flag) {
            $this->setLimitNumSession();
        } else {
            if (!session('?seller_num') || !session('?member_num') || !session('?goods_num')) {
                $this->setLimitNumSession();
            }
        }
    }
	   
	/*添加操作日志*/
	public function  addAdminLog($type='',$desc=''){
		$data = array();
		$data['admin_name'] = session('loginname');
		$data['desc'] = $desc;
		$data['ip'] = $this->getIP();
		$data['type'] = $type;
		$data['addtime'] = mktime();
		$data['status'] = 1;
		M('admin_log')->add($data);
		
	}
	
	public function getIp(){
		$ip='未知IP';
		if(!empty($_SERVER['HTTP_CLIENT_IP'])){
			return $this->is_ip($_SERVER['HTTP_CLIENT_IP'])?$_SERVER['HTTP_CLIENT_IP']:$ip;
		}elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
			return $this->is_ip($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$ip;
		}else{
			return $this->is_ip($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:$ip;
		}   
	}
	public function is_ip($str){
		$ip=explode('.',$str);
		for($i=0;$i<count($ip);$i++){  
			if($ip[$i]>255){  
				return false;  
			}  
		}  
		return preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/',$str);  
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
	

}

?>
