<?php
namespace Home\Controller;
use Think\Controller;
/**
 * XUNXIN PC 后台管理 系统管理
 * ============================================================================
 * 版权所有 2005-2010 厦门微聚点科技有限公司，并保留所有权利。
 * 网站地址: http://www.vjudian.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: youyan $
 * $Id: SysController.class.php
 */
class SysController extends AdminController
{

    /**
     * 修改密码
     * 要求提供3个密码
     * 旧密码 2个新密码
     */
    public function edit()
    {
        if (! session('member_id')) {
            $this->go_login();
        }
        if (IS_POST) {
           
            $new = array();
            $where = array();
            $where['member_id'] = session('member_id');
            $where['member_name'] = session('member_name');
            $where['isdelete'] = 0;
            if (I('post.opwd') == '') {
                $this->error('用户密码不能为空', ('./admin.php?c=Sys&a=edit'));
                exit();
            }
            if (I('post.npwd1') == '' || I('post.npwd2') == '' || I('post.npwd1') != I('post.npwd2')) {
                $this->error('新密码不想等或为空', ('./admin.php?c=Sys&a=edit'));
                exit();
            }
            if (I('post.npwd1') == I('post.opwd')) {
                $this->error('新密码不能和原密码相同', ('./admin.php?c=Sys&a=edit'));
                exit();
            }
            $Member = M('Member');
            $member_info = $Member->where($where)->find();
            if(empty($member_info )){
                $this->error('服务器数据出错请下次修改', ('./admin.php?c=Auth&a=login'));
                exit();
            }
            $oldpwd=$member_info['member_passwd'];
            
            if ($oldpwd!= md5(I('post.opwd'))) {
                $this->error('用户密码不对', ('./admin.php?c=Sys&a=edit'));
                exit();
            }
            $new['member_passwd'] = md5(I('post.npwd1'));
            $Member->where($where)->save($new);
            $this->success('操作成功', U('index/index'));
        } else {
            
            $ui['sys_edit'] = 'active';
            $this->assign('ui', $ui);
            $member = array();
            $member['store_name'] = session('store_name');
            $member['member_id'] = session('member_id');
            $member['member_name'] = session('member_name');
            $member['store_id'] = session('store_id');
            $this->assign('member', $member);
            $this->display('Sys:edit');
        }
    }

    public function setmail()
    {
        if (IS_POST) {
            // $file=$this->_post('files');
            unset($_POST['files']);
            
            $_POST['countsz'] = base64_encode($_POST['countsz']);
            if ($this->update_config($_POST, CONF_PATH . 'setmail.php')) {
                $this->success('操作成功');
            } else {
                $this->success('操作失败');
            }
        } else {
            $ui['sys_setmail'] = 'active';
            $this->assign('ui', $ui);
            $this->display();
        }
    }

    public function homeset()
    {
        if (IS_POST) {
            // $file=$this->_post('files');
            unset($_POST['files']);
            if ($this->update_config($_POST, CONF_PATH . 'home70.php')) {
                $this->success('操作成功');
            } else {
                $this->success('操作失败');
            }
        } else {
            $ui['sys'] = 'active';
            $ui['sys_homeset'] = 'active';
            $this->assign('ui', $ui);
            $this->display();
        }
    }

    /**
     * 清除缓存
     */
    public function clearcache()
    {
        // 缓存路径
        $Webpath = './Admin/Runtime/';
        if (is_dir($Webpath)) {
            $this->update_config(array(
                'jsver' => time()
            ), CONF_PATH . 'jsvion.php');
            \Think\File::del_dir($Webpath);
        }
        $this->success('操作成功');
    }

    private function update_config($config, $config_file = '')
    {
        ! is_file($config_file) && $config_file = CONF_PATH . 'websetConfig.php';
        if (is_writable($config_file)) {
            
            file_put_contents($config_file, "<?php \nreturn " . stripslashes(var_export($config, true)) . ";", LOCK_EX);
            @unlink(RUNTIME_FILE);
            return true;
        } else {
            return false;
        }
    }
}

