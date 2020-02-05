<?php
namespace Home\Controller;
use Think\Controller;
class LoginController extends AdminController{
    
    public function index(){
        session('store_id')=189;
        session('member_id')=810;
        $this->display();
        file_put_contents("log.txt", $data);
    }
}

?>