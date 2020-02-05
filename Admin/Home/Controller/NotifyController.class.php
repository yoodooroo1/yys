<?php
namespace Home\Controller;
use Think\Controller;
//use Think\Controller;

/**
 * XUNXIN PC 后台管理 消息提醒文件
 * ============================================================================
 * 版权所有 2005-2010 厦门微聚点科技有限公司，并保留所有权利。
 * 网站地址: http://www.vjudian.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: youyan $
 * $Id: NotifyController.class.php
 */
class NotifyController extends AdminController
{

    /**
     * 订单提醒
     */
    public function check_order()
    {
        
        if(!session('?last_check')){
             session('last_check', time()-1800);
        }
        $last_check = session('last_check');
        $data = array();
        $flag=$this->check_time($last_check);
        if(!$flag){
          $data['new_order'] = 0;
          $data['paid_order']= 0;
          $data['check_time'] = date("Y-m-d H:i:s",$last_check);
          $datas = json_encode($data);
          $this->ajaxReturn($datas, 'JSON');
          exit;
        }
        $Order = M('Mb_order');
        
        $where = array();
        $where['storeid'] = session('store_id');
        $where['create_time'] = array(
            'gt',
            $last_check
        );
        $where['isdelete'] = 0;
        $where['order_state'] = 0;
        $order_new_count = $Order->where($where)->count();
        $where['order_state'] = 3;
        $order_paid_count = $Order->where($where)->count();
        
        
        $data['new_order'] = $order_new_count;
        $data['paid_order'] = $order_paid_count;
        $data['check_time'] = date("Y-m-d H:i:s",$last_check);
        $datas = json_encode($data);
        //file_put_contents('order.txt', $datas);
        session('last_check', time());
        $this->ajaxReturn($datas, 'JSON');
    }
    
    /**
     * 看时间差是否正确
     * @param unknown $check_time
     */
    private function check_time($check_time,$time_out=3){
        $now=time();
        if(empty($check_time))return false;
        if($now-$check_time>=$time_out){
            return true;
        }else{
            return false;
        }
    }
}
?>
