<?php
namespace Home\Controller;
/**
 * XUNXIN PC 后台管理 管理员管理
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
class AdminManagerController extends AdminController
{
	/**
     * 显示管理员列表
    */
	public function  Administrator_list(){
		$admin = M('admin');
		$w = array();
		$w['status'] = 1;
		$lists = $admin->where($w)->order('role ASC , id DESC')->select();
		$this->assign('lists',$lists);
		$ui = array();
		$ui['admin_list'] = 'active';
		$this->assign('ui',$ui);
		$this->display();   
	}
	
	/*管理员详情*/
	public function Administrator_info(){
		$id = I('id');
		if(!empty($id)){
			$w = array();
			$w['admin_infoid'] = $id;
			$w['status'] = 1;
			$info = M('admin')->where($w)->find();
			if(empty($info)){
				$this->error('账号不存在');
			}else{
				$ui = array();
				$ui['admin_list'] = 'active';
				$this->assign('ui',$ui);
				$this->assign('info',$info);
				$this->display();
			} 
			
		}else{
			$this->error('参数错误');
			
		}
	}
	/*更改管理员密码*/
	/* public function save_Administrator_info(){
		$loginName = I('login_name');
		$password = I('password');
		$w = array();
		if(!empty($password)){
			$w['loginname'] = $loginName;
			$data = array();
			$data['password'] = md5($password);
			$rt = M('admin')->where($w)->save($data);
			if($rt === false){
				$this->error('更新管理员数据失败'); 
			}else{ 
				$this->addAdminLog('3','更改运营商（'.$loginName.'）密码');
			}
		}
		$this->success('编辑管理员数据成功',U('AdminManager/Administrator_list'));   
	} */
	  /**
     * Ajax 更改管理员密码
     */
	 public function save_Administrator_info(){
	   
		$loginName = I('login_name');
		$password = I('password');
		$result = array();
		$w = array();
		if(!empty($password)){
			$w['loginname'] = $loginName;
			$data = array();
			$data['password'] = md5($password);
			$rt = M('admin')->where($w)->save($data);
			//var_dump(123);
			if($rt === false){
				$result['status'] = -1;
				$result['desc'] = '更新管理员数据失败';
				//$this->error('更新管理员数据失败'); 
			}else{ 
				$this->addAdminLog('3','更改运营商（'.$loginName.'）密码');
				$result['status'] = 1;
				
			}
		}
		echo  json_encode($result);
		
		//$this->ajaxReturn($url);
	
	} 
    
	
    
}

?>
