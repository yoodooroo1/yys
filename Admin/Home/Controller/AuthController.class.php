<?php
namespace Home\Controller;
use Think\Controller;
/**
 * XUNXIN PC 后台管理 登入管理
 * ============================================================================
 * 版权所有 2005-2010 厦门微聚点科技有限公司，并保留所有权利。
 * 网站地址: http://www.vjudian.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业
 * 目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: youyan $
 * $Id: AuthController.class.php
 */

class AuthController extends Controller
{
    public function login()
    {
       
        if (IS_POST)
        {	
            // 检查验证码   
			
			$verify = I('post.verify','');
            if(!check_verify($verify,1))
            {
                $this->error("亲，验证码输错了哦！",$this->site_url,9);
			}
            $member_passwd=$_POST['password'];
			$member_name=trim($_POST['username']);
			$this->loginCheck($member_name, $member_passwd);
		}
        else
		{  
			//保证登入界面没有session	
			session(null);
             $this->display('adminLogin');
        }  
    }
    /**
     * 用户退出 清除session cookie
     */
    public function logout()
    {
        session(null);
        setcookie('loginname',null);
        setcookie('password',null);
        $this->success ('安全退出',U('Auth/adminLogin'));
  
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

    /**
     * 重设置密码
     * 知道member_id,store_id
     * 用到的表示member
     */
    public function restpwd(){
        $this->display();
    }
    
    /**
     *重设置密码更新
     * 知道member_id,store_id
     * 用到的表示member
     * 要提供3个密码  1原密码 2 新密码3 确认新密码
     */
    public function updatepwd(){
        if(IS_POST){
            $pwd1=$_POST['pwd1'];
            $pwd2=$_POST['pwd2'];
            $pwd3=$_POST['pwd3'];
            if($pwd2!=$pwd3){}
            $Member=M('Member');
        }
    }
    public function frame()
    {
        $this->display();
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


     /**
     * 登入检查
     * 用到的表 table('member')table('seller')table('store')
     */
    private function loginCheck($member_name,$member_passwd)
    {
        $admin=M('admin');
        $where=array();
        $where['loginname']=$member_name;
        $where['status'] = 1;
        $admininfo= $admin->where($where)->find();
        if(empty($admininfo)){
            $this->error('登录失败,没有此账号', '?c=Auth&a=index', 3);
        }
        if($admininfo['password']!=md5($member_passwd)){
            $this->error('密码不对', '?c=Auth&a=index', 3);
        }
        //session 设置
        session('loginname', $member_name);
        session('adminid', $admininfo['id']);
		
		$data = array();
		$data['lasttime'] = date('Y-m-d H:i:s');
		$data['lastip'] = A('Admin')->getIp();
		$admin->where($where)->save($data);   
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // hjun 设置菜单权限和curd权限
       /*  $model_group = M('admin_group');
        $where = array();
        $where['group_id'] = $admininfo['group_id'];
        $group_info = $model_group->where($where)->find();
        session('LM_GROUP_MENU',$group_info['group_menu']);
        session('LM_GROUP_CURD',$group_info['group_curd']); */
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //  登入后其他操作 修改 1. 登入次数 2.最后登入时间

        if ($_POST['remember']) {
           cookie('loginname', $member_name);
           cookie('password', $member_passwd);
        }
        A('Admin')->addAdminLog('1','管理员登录');
        echo '{"status":1,"info":""}';
        exit();
    }      
    
    /**
     * 获得Seller 信息
     * @param unknown $member_id
     * @return Ambigous <mixed, boolean, string, NULL, multitype:, unknown, object>
     */
    private function getSellerInfo($member_id){
        $Seller=M('Seller');
        $where=array();
        $where['member_id']=$member_id;
        return $Seller->where($where)->find();
    }

    /**
     * 设置权限目前值判断是否是管理员
     * @param unknown $seller_info
     */
    private function setAuth($seller_info){
        if(empty($seller_info)||!is_array($seller_info)){
            $this->error('数据出错--权限', '?c=Auth&a=index', 3);
        }
               
        if($seller_info['is_admin']==1){
            session('AUTH','ALL');
        }else{
            session('AUTH','NO');
        }
    }
	
 
		
/* 	public function test(){
		$m = M('settlement_pv');
		$da = array();
		$da['member_id'] = 140;
		
		try{
			$m->add($da); 
		}catch(\Exception $e){
			\Think\Log::write('插入数据库出错，错误信息:'.$e->getMessage().' 会员ID:'.$da['member_id'],'ERROR');
		}    
		
		die('233');
	}   */
	 
	
	

}
?>
