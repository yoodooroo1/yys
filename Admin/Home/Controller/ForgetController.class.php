<?php
namespace Home\Controller;
use Think\Controller;
/**
 * XUNXIN PC 后台管理 忘记密码
 * ============================================================================
 * 版权所有 2005-2010 厦门微聚点科技有限公司，并保留所有权利。
 * 网站地址: http://www.vjudian.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: youyan $
 * $Id: ForgetController.class.php
 */
class ForgetController extends Controller
{

    /**
     * step1
     * 获得用户账号
     */
    public function index()
    {
        if (IS_POST) {
            $member_name = $_POST['username'];
            if (empty($member_name)) {
                $this->error('用户名为空');
            }
            $Member = M('Member');
            $where = array();
            $where['member_name'] = $member_name;
            $info = $Member->where($where)->find();
            if (empty($info)) {
                $this->error('用户名不存在');
            }
            
            if ($info['isdelete'] == 1) {
                $this->error('用户名被禁');
            }
            $tel = $info['member_tel'];
            
            if (empty($tel) || ! $this->is_mobile($tel)) {
                $this->error('密保手机没设置或错误');
            }
            session('tel', $tel);
            session('username', $member_name);
        } else {
            session(null);
            $this->display('Forget:index');
        }
    }

    /**
     * step2
     * 获得验证码
     */
    public function code()
    {
        if (session('?tel') && session('?usernanme')) {
            if (IS_POST) {
                $code = $_POST['code'];
                if (empty($code)) {
                    $this->error('验证码为空');
                }
                $params = array(
                    'code' => $code,
                    'tel' => session('tel'),
                    'act' => 'sms_verification',
                    'op' => 'check_code'
                );
                
                $msg = $this->request_post($params);
                $returnInfo = json_decode($msg, true);
                if ($returnInfo['result'] == 0) {
                    session('success', true);
                    $this->success('请重新设置密码', 'update');
                } else {
                    session('success', false);
                    $this->error($returnInfo['error']);
                }
            } else {
                $this->display('Forget:code');
            }
        } else {
            $this->index();
        }
    }

    
    /**
     * step3
     * 填写修改的密码
     */
    public function update()
    {
        $suss = session('success');
        if (! $suss&&!session('?tel')&&!session('username')) {
            $this->index();
            exit();
        }
        if (IS_POST) {
            $psw1 = I('post.password');
            $psw2 = I('post.password2');
            if ($psw1 != $psw2) {
                $this->error('2次输入的密码有错误');
            }
            $Member = M('Member');
            $where['member_name'] = session('username');
            $member_info = $Member->where($where)->find();
            if (empty($member_info)) {
                $this->error('服务器数据出错请联系客服。。。');
            }
            $new['member_passwd'] = md5(I('post.password'));
            $new['version']=$this->getMaxVersion($Member)+1;
            $num = $Member->where($where)->save($new);
            if (empty($num)) {
                $this->error('密码修改不成功');
            } else {
                session(null);
                $this->success('密码修改成功', 'Auth/login');
            }
        } else {
            $this->display('Forget:update');
        }
    }
    
    /**
     * 点击获得验证码所调用的方法
     */
    public function sendMsg()
    {
        if (session('?tel') && session('?usernanme')) {
            
            $params = array(
                'username' => session('username'),
                'tel' => session('tel'),
                'channel_id' => 0,
                'act' => 'sms_verification',
                'op' => 'change_mm'
            );
            
            $msg = $this->request_post($params);
            $returnInfo = json_decode($msg, true);
            if ($returnInfo['result'] != 0) {
                $this->error($returnInfo['error']);
            }
        } else {
            $this->index();
        }
    }
    
    // 请求接口
    private function request_post($params = array())
    {
        if (empty($params)) {
            return false;
        }
        $url = "http://api.duinin.com/xxapi/index.php";
        $data = "";
        foreach ($params as $k => $v) {
            $data .= "$k=" . urlencode($v) . "&";
        }
        $param = substr($data, 0, - 1);
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
     * 获得该商店当前最高版本号如果没有商品设置为0
     *
     * @param MODEL $Goods            
     * @return number
     */
    private function getMaxVersion($Model)
    {
        $versions = $Model->field('version')
            ->order('version desc')
            ->find();
        
        $version = $versions['version'];
        if ($version >= 0)
            return $version;
        else
            return 0;
    }

    /**
     * 判断是否是手机号码
     * 
     * @param unknown $value            
     */
    private function is_mobile($value)
    {
        return preg_match('/^(?:13\d|15\d|17\d|14\d|18\d)\d{5}(\d{3}|\*{3})$/', $value);
    }
}

?>