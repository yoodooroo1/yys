<?php
namespace Home\Controller;
use Think\Controller;
/**
 * XUNXIN PC 后台管理 员工管理
 * ============================================================================
 * 版权所有 2005-2010 厦门微聚点科技有限公司，并保留所有权利。
 * 网站地址: http://www.vjudian.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: youyan $
 * $Id: EmployeeController.class.php
 */
class EmployeeController extends AdminController
{

    /**
     * hjun
     * 2017年3月8日 14:08:42
     * 好人生态增加员工权限管理
     */

    /**
     * 显示员工列表
     * 操作的表: Admin表, Role表
     */
    public function showEmployeeList()
    {
        // 联表查询 员工列表和对应的角色信息
        $model_employee = M('admin');
        $where = array();
        $where['a.status'] = 1;
        $employee_list = $model_employee
            ->alias('a')
            ->join('lm_role lr ON a.role = lr.role_id')
            ->where($where)
            ->select();
        $this->assign('list',$employee_list);
        //dump($employee_list);exit;

        $ui['employee_list'] = 'active';
        $this->assign('ui',$ui);
        $this->display('employee_list');
    }

    /**
     * 显示添加员工
     * @param string $act
     */
    public function showEmployeeAdd($act='insert')
    {
        // 查询角色信息
        $model_role = M('role');
        $where = array();
        $where['status'] = 1;
        $role_list = $model_role->where($where)->select();
        $this->assign('role_list',$role_list);

        // 如果是显示员工详情
        if ($act == 'info'){
            $id = I('id');
            $model_employee = M('admin');
            $where = array();
            $where['a.status'] = 1;
            $where['a.id'] = $id;
            $employee_info = $model_employee
                ->alias('a')
                ->join('lm_role lr ON a.role = lr.role_id')
                ->where($where)->find();
            $this->assign('employee',$employee_info);
            //dump($employee_info);exit;
        }

        $ui['employee_add'] = 'active';
        $this->assign('ui',$ui);
        $this->assign('act',$act);
        $this->display('employee_info');
    }

    /**
     * 显示员工详情
     */
    public function showEmployeeInfo(){
        $this->showEmployeeAdd('info');
    }

    /**
     * 添加员工
     */
    public function employeeCreate(){
        $role_id = I('role_name'); //角色id
        $login_name = I('seller_name'); //登录帐号
        // 帐号唯一，进行核对
        $model_employee = M('admin');
        $num = $model_employee->where(array('loginname'=>$login_name))->count();
        if ($num > 0 ){
            $this->error('帐号已经存在！');
        }
        $password = I('newMem_passwd'); // 密码
        $employee = array();
        $employee['loginname'] = $login_name;
        $employee['password'] = md5($password);
        $employee['role'] = $role_id;
        $employee_id = $model_employee->data($employee)->add();
        if ($employee_id){
            $this->success('添加员工成功！');
        }else {
            $this->error('添加员工失败！');
        }
    }

    /**
     * 修改员工
     */
    public function employeeUpdate(){
        $id = I('id'); //员工id
        $role_id = I('role_name'); //角色id
        $password = I('newMem_passwd'); // 密码
        $model_employee = M('admin');
        $employee = array();
        $employee['role'] = $role_id;
        if (!empty($password)){
            $employee['password'] = md5($password);
        }
        $result = $model_employee->where(array('id'=>$id))->save($employee);
        if ($result){
            $this->success('修改员工成功！');
        }else {
            $this->error('修改员工失败！');
        }
    }

    /**
     * 删除员工
     */
    public function employeeDel(){
        $id = I('id');
        $model_employee = M('admin');
        $result = $model_employee->where(array('id'=>$id))->delete();
        if ($result){
            $this->success('删除成功！');
            $this->error('删除失败！');
        }else {
        }
    }

    /**
     * 显示角色列表
     */
    public function showRoleList(){
        // 查询角色信息
        $model_role = M('role');
        $where = array();
        $where['status'] = 1;
        $role_list = $model_role->where($where)->select();
        $this->assign('role_list',$role_list);

        $ui['role_list'] = 'active';
        $this->assign('ui',$ui);
        $this->display('role_list');
    }

    /**
     * 显示新增角色
     */
    public function showRoleAdd(){

        $this->assign('act','insert');
        $ui['role_add'] = 'active';
        $this->assign('ui',$ui);
        $this->display('role_info');
    }

    /**
     * 显示角色详情
     */
    public function showRoleInfo(){
        $this->assign('act','info');
        $role_id = I('role_id');
        $model_role = M('role');
        $role_info = $model_role->where(array('role_id'=>$role_id))->find();
        $this->assign('role',$role_info);

        $ui['role_info'] = 'active';
        $this->assign('ui',$ui);
        $this->display('role_info');
    }

    /**
     * 新增角色
     */
    public function roleCreate(){
        $act = I('get.act');
        if ($act == 'info'){
            $this->roleUpdate();
        }

        $model_role = M('role');
        $role_name = I('role_name'); //角色名称
        $role = array();
        $role['role_name'] = $role_name;
        $result = $model_role->data($role)->add();
        if ($result){
            $this->success('添加角色成功！');
        }else {
            $this->error('添加角色失败！');
        }

    }
    /**
     * 修改角色
     */
    public function roleUpdate(){
        $role_id = I('role_id');
        $model_role = M('role');
        $role_name = I('role_name'); //角色名称
        $role = array();
        $role['role_name'] = $role_name;
        $result = $model_role->where(array('role_id'=>$role_id))->save($role);
        if ($result){
            $this->success('修改角色成功！');
        }else {
            $this->error('修改角色失败！');
        }
        exit;
    }

    /**
     * 删除角色
     */
    public function roleDel(){
        $role_id = I('role_id');
        $model_role = M('role');
        $result = $model_role->where(array('role_id'=>$role_id))->delete();
        if ($result){
            $this->ajaxReturn(1);
        }

    }


}
?>