<?php
namespace Home\Controller;
use Think\Controller;
/**
 * XUNXIN PC 后台管理 订单管理
 * ============================================================================
 * 版权所有 2005-2010 厦门微聚点科技有限公司，并保留所有权利。
 * 网站地址: http://www.vjudian.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: youyan $
 * $Id: XunxinInterFaceController.class.php
 */
class XunxinInterFaceController extends Controller{
    
  /**
   * 通信测试
   */  
  public function chat($name=''){
      $body = array();
      $type='chat';
      $body['store_id'] = session('store_id');
      $body['store_name'] = session('store_name');
      $body['sender_name'] = session('member_name');
      $body['sender_type'] = 'seller';
      $body['type'] = 0;
      $body['remote_url'] = '';
      $body['file_size'] = 0;
      $body['external_data'] = '';
      $body['voice_len'] = 0;
      $body['voice_url'] = '';
      $body['thumb_url'] = '';
      $body['text'] = '订单的商品发货了';
      if(empty($name)){
          $name='y12345678';
      }
      $tousers = $name;
      $result= $this->sendMSG($type, $tousers, $body);
      header("Content-Type:text/html;Charset=utf-8");

      foreach($body as $k=>$v){
      echo $k.'=>'.$v."<br>";
      }
      echo "---body end---". "<hr>";
     foreach($result as $k=>$v){
         echo $k.'=>'.$v."<br>";
     }
     
  }    
    
    
    
    
    /**
     * 检查商品库存
     * http://www.xunxin.biz/admin.php/Service/XunxinInterFace/chack_storage
     */
    public function chack_storage()
    {
        if (IS_GET) {
            $op = "chack_storage";
            $param = array(
                "act" => 'goods',
                "op" => $op,
                "goods_id" =>120190,
            );
            $data = $this->getApiParams($param);
            $msg = $this->request_post_xunxin($data);
            $returnInfo=json_decode($msg);
            if($returnInfo['result']==0){
                die("OK");
            }
            die(print_r($returnInfo ));
        } else {
            $this->error();
        }
    }
    
    
    /**
     * 发货操作
     *
     * @param unknown $order_id
     */
    public function deliveryOp()
    {
        $op = "delivery";
        $param = array(
            "act" => 'order',
            "op" => $op,
            "order_id" =>410,
        );
        $data = $this->getApiParams($param);
        $msg = $this->request_post_xunxin($data);
        die(print_r($msg ));
    }
    // 请求接口
    protected function request_post_xunxin($param = '')
    {
        //if(C('IS_TEST')){
            $apiUrl = "http://api.duinin.com/xxapi/index.php";
       // }ELSE{
           // $apiUrl = "http://api.duinin.com/xxapi/index.php";
        //}
         
        return $this->request_post($apiUrl, $param);
    }
    // 请求接口
    private function request_post($url = '', $param = '')
    {
    
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
    protected function getApiParams($param = array())
    {
        $key = $this->getApiToken();
    
        if (empty($key)) {
            return false;
        }
        $params = array(
            "user_type" => 'maller',
            "store_id" => 291,
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
    protected function getApiToken()
    {
        $Token = M('Mb_user_token');
        $where = array();
        $where['member_id'] = 601;
        $key = $Token->where($where)->find();
        return $key['token'];
    }
    
    
    /**
     * 发送消息
     *
     * @param unknown $type
     * @param unknown $tousers
     * @param unknown $body
     */
    private function sendMSG($type, $tousers, $body)
    {
        $url = 'http://api.duinin.com/xxapi/chat/sendmessage.php';
        //$info = $this->getMemberInfo();
        $member_name = '999';
        $member_passwd = 'e10adc3949ba59abbe56e057f20f883e';
    
        $params = array();
        $params['type'] = $type;
        $params['username'] = $member_name;
        $params['password'] = $member_passwd;
        $params['tousers'] = $tousers;
        $params['body'] = json_encode($body);
        $data = $this->parseArray($params);
      echo $data."<br>";
        $returnInfo = $this->request_post($url, $data);
        $result = json_decode($returnInfo, true);
        return $result;
    }
    /**
     * 将数组转化成post
     *
     * @param unknown $params
     * @return string
     */
    private function parseArray($params)
    {
        // 请求第一种方式
        $data = "";
        foreach ($params as $k => $v) {
            $data .= "$k=" . urlencode($v) . "&";
        }
        $data = substr($data, 0, - 1);
        return $data;
    }
}
?>
