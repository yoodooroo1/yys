<?php
namespace Home\Controller;
use Think\Controller;
/**
 * XUNXIN PC 后台管理 资讯分类
 * ============================================================================
 * 版权所有 2005-2010 厦门微聚点科技有限公司，并保留所有权利。
 * 网站地址: http://www.vjudian.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: youyan $
 * $Id: SellerController.class.php
 */
class OperateController extends AdminController
{
	public $operate_config = array();	
	public function __construct()
    {    
        parent::__construct();
        if(!$this->checkAuth()){
			$this->error('你没有该权限',U('Index/index'));
		}
		$this->operate_config = M('operate_config')->where(array('status'=>1))->find();	
    }
    /**       
	* 运营商列表
	*/
    public function operate_list()
    {
		$center = M('operate_center');
		$shareholder = M('operate_shareholder');
		$store = M('stores');
		$w = array();
		$w['status'] = '1'; 
		$operate_id = I('operate_id');
		$operate_name = I('operate_name');
		$choose_level = I('choose_level');
		$operate_sn = I('operate_sn');
		$link_name = I('link_name');
		$link_tel = I('link_tel');
		$Time1 = I('Time1');
		$Time2 = I('Time2');  
		if(!empty($operate_id)){
			$w['id'] = $operate_id;
		}
		if(!empty($operate_name)){
			$w['operate_name'] = array('like',"%$operate_name%");
		}
		if(!empty($choose_level)){
			$w['level'] = $choose_level;
		}
		if(!empty($operate_sn)){
			$w['operate_sn'] = array('like',"%$operate_sn%");
		}
		if(!empty($link_name)){
			$w['link_name'] = array('like',"%$link_name%");
		}
		if(!empty($link_tel)){   
			$w['link_tel'] = array('like',"%$link_tel%");
		}
		if(!empty($Time1) && empty($Time2))  
		{
			$t = strtotime($Time1)+24*60*60 ;
			$w['_string']= "addtime >= '".strtotime($Time1) ."'&& addtime < '".$t."'";
		}         
		if(!empty($Time2) && empty($Time1))
		{  
			$w['_string']= "addtime<= '". strtotime($Time2)."'";  
		}  
		if(!empty($Time2) && !empty($Time1))
		{   
			$t = strtotime($Time2)+24*60*60 ;
			$w['_string']= "addtime >= '".strtotime($Time1) ."'&& addtime < '" .$t."'";  
		}
		
		$count = $center->where($w)->count(); // 查询满足要求的总记录数
        $Page = new \Think\Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show(); // 分页显示输出  
		$lists = $center->where($w)->order('addtime DESC,id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select(); 
		
		foreach($lists as $k=>$list){
			$w2 = array();
			$w2['operate_id'] = $list['id'];
			$w2['status'] = '1';
			$lists[$k]['shareholder_num'] =$shareholder->where($w2)->count();
			$w3 = array();
			$w3['operate_id'] = $list['id'];
			$w3['isdelete'] = '0';
			$lists[$k]['store_num'] = $store->where($w3)->count();
			$lists[$k]['discount'] = $this->getDiscount($list['level']);	
		}     
		$this->assign('lists',$lists);  
		$this->assign('page',$show); 
		$ui['operate_list'] = 'active';
		$this->assign('ui',$ui); 
        $this->display('Operate:operate_list');
    }
	 
	/*运营商详情*/ 
	public function operate_info(){   
		$center = M('operate_center');
		$member = M('members');
		$operate_total_price = M('operate_total_price'); 
		$w = array();
		$w['status'] = '1';
		$id = I('id');
		$ui = array();
		if(empty($id)){ 
			$this->assign('act','insert');
			$ui['operate_add'] = 'active';
        }  
		else{    
			$this->assign('act','info');
			$ui['operate_info'] = 'active';
			$w3 = array(); 
			$w3['id'] =$id;   
			$w3['status'] = 1;
			$info = $center->where($w3)->find();  
			
			$w2 = array();
			$w2['operate_id'] = $id;
			$w2['isdelete'] = '0';
			$info['store_num'] = M('stores')->where($w2)->count();
			 
			$total_price = $operate_total_price->where(array('operate_id'=>$info['id']))->sum('value');
			$info['total_price'] =empty($total_price) ? 0 : round($total_price,2) ;
			
			$unget_price = $operate_total_price->where(array('operate_id'=>$info['id'],'is_get'=>0))->sum('value');
			$info['unget_price'] = empty($unget_price) ? 0 : round($unget_price,2) ;
			$this->assign('info',$info);
		}  
		$ui['operate_info'] = 'active';
		$this->assign('ui',$ui);    
		$this->display('Operate:operate_info');
	}
	 
	public function operate_edit(){
		$act = $_POST['act'];  
		$center = M('operate_center');
		$admin = M('admin');
		$member = M('members');
		$data = array();
		$operate_name = $_POST['operate_name'];
		$login_name = $_POST['login_name'];
		$login_password = $_POST['login_password'];
		$link_name = $_POST['link_name'];
		$link_tel = $_POST['link_tel'];
		$bank_name = $_POST['bank_name'];
		$bank_sn = $_POST['bank_sn'];
		$level = $_POST['level'];
		$e_mail = $_POST['e_mail'];
		$bank_username = $_POST['bank_username'];
		if(empty($operate_name)){
			$this->error('营运商名称不能为空');
			die;
		}
		/* 
		if(empty($login_name)){
			$this->error('登录账户不能为空');
			die;    
		} */
		
		if(empty($link_name)){
			$this->error('联系人不能为空');
			die;
		}  
		   
		if(empty($link_tel)){
			$this->error('联系人手机号不能为空');
			die;
		}
		
		$data['link_name'] = $link_name;
		$data['link_tel'] = $link_tel;
		$data['bank_name'] = $bank_name;
		$data['bank_sn'] = $bank_sn;
		$data['bank_username'] = $bank_username;
		$data['level'] = $level;
		$data['e_mail'] = $e_mail;
		   
		$data['status'] = 1;
		$check1 = array();	
		$check2 = array();
		$check3 = array();
		if($act == 'insert'){
			$data['addtime'] = mktime();
			$data['edittime'] = mktime();  		
			$check1 = $center->where(array('operate_name'=>$operate_name,'status'=>1))->find();
			if(!empty($check1)){
				$this->error('该营运商名称已存在');
				die;
			}
			if(empty($login_password)){
				$this->error('登录密码不能为空');
				die;
			}
		}
		else if($act == 'info'){
			$data['edittime'] = mktime();  
			$id = $_POST['id'];
			$w2 = array();
			$w2['status'] = 1;
			$w2['id'] = array('neq',$id);
			$w2['operate_name'] = $operate_name; 
			$check1 = $center->where($w2)->find();
			if(!empty($check1)){
				$this->error('该营运商名称已存在');
				die;
			}   
			if(!empty($login_password)){
				$admin->where(array('loginname'=>$login_name,'status'=>1))->save(array('password'=>md5($login_password)));
			}   
			
		}     
		
		$data['operate_name'] = $operate_name;
		
		if($act == 'info'){
			$jg = $center->where(array('id'=>$id))->save($data);
			if($jg === false){
				$this->error('修改运营商失败');
			}else{
				$this->addAdminLog('3',"修改运营商操作,运营商ID：".$id);
				$this->success('修改运营商成功',U('Operate/operate_list'));
			} 
		}
		else if($act == 'insert'){
			if($oid = $center->add($data)){   
				/*创建operate_sn*/
				$operate_sn = 10000+$oid;
				$center->where(array('id'=>$oid))->save(array('operate_sn'=>$operate_sn,'login_name'=>$operate_sn));
				$data2 = array();
				$data2['loginname'] = $operate_sn;
				$data2['password'] = md5($login_password);
				$data2['role'] = 1;
				$data2['status'] = 1;  
				$data2['addtime'] = mktime();   
				$admin->add($data2); 
				$this->addAdminLog('3',"添加运营商操作,运营商ID：".$oid);
				$param = array(
					"tel" => $link_tel,
					"username" => $operate_sn,
					"password" => $login_password,
				);   
				$sync_url = M('system_config')->where(array('status'=>1))->getField('sync_url');
				$apiUrl = "http://".$sync_url."/xxapi/index.php?act=sms_verification&op=send_open_operation_sms";   
				$sm = $this->request_post($apiUrl,$param);
				$this->success('添加运营商成功',U('Operate/operate_list'));
			}else{
				$this->error('添加运营商失败');   
			}
		}	 
	} 
	
	/*ajax 运营商充值*/
	public function  ajax_operate_recharge(){
		$operate_id = I('operate_id');
		$money = round(I('money'),2);
		$remark = I('remark');
		$pass = I('recharge_pass');
		$password = M('admin')->where(array('loginname'=>session('loginname'),'status'=>1))->getField('password');
		$rt = array();
		$center = M('operate_center');
		$where = array();
		$where['id'] = $operate_id;
		$where['status'] = 1;
		$info = $center->where($where)->find();
		if(empty($info)){
			$rt['status'] = '-1';
			$rt['desc'] = '该运营商不存在';
		}else{

			if(md5($pass) != $password ){
				$rt['status'] = '-1';
				$rt['desc'] = '登录密码错误';
			}else{
				$record = M('operate_trade_record');
				
				$data1 = array();
				$data1['operate_id'] = $operate_id;
				$data1['value'] = $money; 
				$data1['remark'] = $remark;
				$data1['type'] = 1;
				$data1['periods'] = date('Y-m-d');
				$data1['editor'] = session('loginname');
				$data1['addtime'] = mktime();
				if($rid = $record->add($data1)){
					$tip = $center->where($where)->setInc('money',$money);
					if($tip !== false){
						$final_value = $center->where($where)->getField('money');
						$record->where(array('id'=>$rid))->save(array('final_value'=>$final_value));  
						$rt['status'] = '1';
					}else{
						$rt['status'] = '-1';
						$rt['desc'] = '更新运营商预充值金额失败';
					}	
				}else{
					$rt['status'] = '-1';
					$rt['desc'] = '添加运营商充值记录失败';
				}
			}
		}
		echo json_encode($rt,JSON_UNESCAPED_UNICODE);  
		
		
	}
	
	/*运营商申请列表*/
	public function operate_apply_list(){
		$apply = M('operate_apply');
		$w2 = array();
		$w2['isdelete'] = 0;
		$w2['statue'] = 0;
		$undeal= $apply->where($w2)->count();
		
		$operate_name = I('operate_name');
		$link_name = I('link_name');
		$link_tel = I('link_tel');
		$e_mail = I('e_mail');
		$Time1 = I('Time1');
		$Time2 = I('Time2');  
		$w = array(); 
		$w['isdelete'] = 0;	
		if(!empty($operate_name)){
			$w['operate_name'] = array('like',"%$operate_name%");
		}
		if(!empty($link_name)){
			$w['link_name'] = array('like',"%$link_name%");
		}
		if(!empty($link_tel)){   
			$w['link_tel'] = array('like',"%$link_tel%");
		}
		if(!empty($e_mail)){
			$w['e_mail'] = array('like',"%$e_mail%");
		}   
		if(!empty($Time1) && empty($Time2))  
		{
			$t = strtotime($Time1)+24*60*60 ;
			$w['_string']= "addtime >= '".strtotime($Time1) ."'&& addtime < '".$t."'";
		}         
		if(!empty($Time2) && empty($Time1))
		{  
			$w['_string']= "addtime<= '". strtotime($Time2)."'";  
		}  
		if(!empty($Time2) && !empty($Time1))
		{   
			$t = strtotime($Time2)+24*60*60 ;
			$w['_string']= "addtime >= '".strtotime($Time1) ."'&& addtime < '" .$t."'";  
		} 
		$count = $apply->where($w)->count(); // 查询满足要求的总记录数
        $Page = new \Think\Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show(); // 分页显示输出  
		$lists = $apply->where($w)->order('addtime DESC,id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select(); 
		 $ui['operate_apply'] = 'active';  
		$this->assign('count',$count);
		$this->assign('undeal',$undeal);
      	$this->assign('ui',$ui);	
		$this->assign('lists',$lists);
		$this->display('apply_list');
	}
	
	/*处理运营商申请*/
	public function ajax_operate_apply_deal(){
		$id = I('id');
		$remark = I('remark');
		$m = M('operate_apply');
		$rt = array();
		$w = array();
		$w['id'] = $id;
		$info = $m->where($w)->find();
		if(empty($info)){
			$rt['status'] = '-1';
			$rt['desc'] = '该申请不存在';
		}else{
			if($info['status'] == 1){
				$rt['status'] = '-1';
				$rt['desc'] = '该申请已经处理过了';
			}else{
				$jg = $m->where($w)->save(array('deal_result'=>$remark,'status'=>1,'editor'=>session('loginname')));   
				if($jg !== false){
					$rt['status'] = 1; 
				}else{ 
					$rt['status'] = '-1';
					$rt['desc'] = '申请状态更改失败';
				}
			}
		}
		echo json_encode($rt,JSON_UNESCAPED_UNICODE);
		
	}
    
	public function del_operate(){
		$id = $_GET['id'];
		$center = M('operate_center');
		$login_name = $center->where(array('id'=>$id,'main_store'=>0))->getField('login_name');
		$admin = M('admin');
		if($center->where(array('id'=>$id,'main_store'=>0))->save(array('status'=>0))){
			$admin->where(array('loginname'=>$login_name))->save(array('status'=>0));
			$this->addAdminLog('3',"删除运营商操作,运营商ID：".$id);
			$this->success('删除运营商成功',U('Operate/operate_list'));
		}else{          
			$this->error('删除运营商失败');
		}
		
	} 
	
	/*运营商股东列表*/
	public function operate_shareholder_list(){
		$id = I('id');
		$center = M('operate_center');
		$shareholder = M('operate_shareholder');
		$info = $center->where(array('id'=>$id,'status'=>1))->find();
		if(empty($info) || empty($id)){
			$this->error('该运营商不存在');
			die;   
		}             
		$lists = $shareholder->where(array('operate_id'=>$id,'status'=>1))->order('addtime DESC')->select(); 
		/* var_dump($lists);
		exit; */
		$this->assign('lists',$lists);
		$ui['operate_list'] = 'active';
		$this->assign('operate_id',$id);  
		$this->assign('ui',$ui);       
        $this->display('Operate:operate_shareholder_list');
		    
	} 
	
	/*运营商股东详情*/
	public function  operate_shareholder_info(){
		$id = I('id'); 
		$operate_id = I('operate_id');
		$shareholder = M('operate_shareholder');
		$info = array();
		if(empty($id)){ 
			// 新增股东 
			$act = 'insert';
			$max_sn = $shareholder->where(array('operate_id'=>$operate_id))->order('id DESC')->getField('shareholder_sn');
			if(empty($max_sn)){
				$operate_sn = M('operate_center')->where(array('id'=>$operate_id))->getField('operate_sn');
				$operate_num =preg_replace('|[a-zA-Z]+|','',$operate_sn);
				$shareholder_sn = preg_replace('|[^a-zA-Z]+|','',$operate_sn).($operate_num*10000+1);	
			}else{
				$shareholder_sn = preg_replace('|[^a-zA-Z]+|','',$max_sn).(intval(preg_replace('|[a-zA-Z]+|','',$max_sn))+1);
			} 
			$rt = $shareholder->where(array('shareholder_sn'=>$shareholder_sn))->find();
			while(!empty($rt)){
				$shareholder_sn = preg_replace('|[^a-zA-Z]+|','',$shareholder_sn).(intval(preg_replace('|[a-zA-Z]+|','',$shareholder_sn))+1); 
				$rt = $shareholder->where(array('shareholder_sn'=>$shareholder_sn))->find();
			}   
			$info['shareholder_sn'] = $shareholder_sn;    
        }else{
			// 编辑股东 
			$act = 'info';
			$info = $shareholder->where(array('id'=>$id,'status'=>1))->find();
		}
		$this->assign('info',$info);
		$ui['operate_info'] = 'active';   
		$this->assign('operate_id',$operate_id);   
		$this->assign('ui',$ui); 
		$this->assign('act',$act);   
		$this->display('Operate:operate_shareholder_info');
	} 
   

	/*运营商成员编辑*/
	public function operate_shareholder_edit(){
		$act = $_POST['act'];  
		$shareholder = M('operate_shareholder');
		$member = M('members');
		$data = array();
		$operate_id = $_POST['operate_id'];  
		$shareholder_role = $_POST['shareholder_role'];
		$shareholder_name = $_POST['shareholder_name'];
		$shareholder_sn = $_POST['shareholder_sn']; 
		$shareholder_tel = $_POST['shareholder_tel'];
		$share_rate = $_POST['share_rate'];
		$share_rate = empty($share_rate) ? 0 : $share_rate;
		$recommend_rate = $_POST['recommend_rate'];  
		$recommend_rate = empty($recommend_rate) ? 0 : $recommend_rate;
		$password = $_POST['password'];
		$bank_name = $_POST['bank_name'];
		$bank_sn = $_POST['bank_sn'];
		$bank_username = $_POST['bank_username'];
		$member_name = $_POST['member_name'];
		$member_id = $_POST['member_id'];
		if(empty($shareholder_role)){  
			$this->error('股东角色不能为空');
			die;
		}
		
		if(empty($shareholder_name)){
			$this->error('股东名称不能为空');
			die;    
		}
 
		if(empty($shareholder_tel)){
			$this->error('联系人手机号不能为空');
			die;
		}
		  
		if(!empty($password)){
			$data['password'] = md5($password);
		}  
	  
		$data['member_id'] = $member_id;
		$data['member_name'] = $member_name;
		$data['operate_id'] = $operate_id;
		$data['shareholder_name'] = $shareholder_name;
		$data['shareholder_sn'] = $shareholder_sn;  
		$data['shareholder_role'] = $shareholder_role;
		$data['shareholder_tel'] = $shareholder_tel;
		$data['bank_name'] = $bank_name;
		$data['bank_username'] = $bank_username;
		$data['bank_sn'] = $bank_sn;
		$data['share_rate'] = $share_rate;
		$data['recommend_rate'] = $recommend_rate;
		$data['status'] = 1;  
		$check1 = array();	
		$check2 = array();
		$check3 = array();
		
		if($act == 'insert'){
			$w0 = array();
			$w0['shareholder_sn'] = $shareholder_sn;
			$check = $shareholder->where($w0)->find();
			if(!empty($check)){
				$this->error('该股东编号已存在，请刷新重试');
				die();
			}	
			$data['addtime'] = mktime(); 
			$data['edittime'] = mktime();  	
			$w1 = array();
			$w1['operate_id'] = $operate_id;
			$w1['status'] = 1;
			$count_rate = $shareholder->where($w1)->sum('share_rate');
			$count_rate = empty($count_rate) ? 0 : $count_rate;	
		}
		else if($act == 'info'){
			$data['edittime'] = mktime();  
			$id = $_POST['id'];
			$w1 = array();
			$w1['operate_id'] = $operate_id;
			$w1['id'] = array('neq',$id);
			$w1['status'] = 1;
			$count_rate = $shareholder->where($w1)->sum('share_rate');
			$count_rate = empty($count_rate) ? 0 : $count_rate;	
		}
		
		if(($count_rate + $share_rate) > 100){
			$this->error('成员持股总比例不能超过100%');
			die();	
		}
		  	
		
		if($act == 'info'){
			$jg = $shareholder->where(array('id'=>$id))->save($data);
			if($jg === false){
				$this->error('修改股东信息失败');
			}else{
				$this->addAdminLog('4',"修改股东信息操作,股东ID：".$id);
				$this->success('修改股东信息成功',U('Operate/operate_shareholder_list',array('id'=>$operate_id)));
			}
		}      
		else if($act == 'insert'){
			if($sid = $shareholder->add($data)){  
				$this->addAdminLog('4',"添加股东信息操作,股东ID：".$sid);
				$this->success('添加股东信息成功',U('Operate/operate_shareholder_list',array('id'=>$operate_id)));
			}else{
				$this->error('添加股东信息失败');   
			}
		}	      
	} 

	public function del_shareholder(){ 
		$id = $_GET['id'];
		$center = M('operate_shareholder');
		$info = $center->where(array('id'=>$id))->find();
		$admin = M('admin');
		if($center->where(array('id'=>$id))->save(array('status'=>0))){
			$this->addAdminLog('4',"删除股东信息操作,股东ID：".$id);
			$this->success('删除股东成功',U('Operate/operate_shareholder_list',array('id'=>$info['operate_id'])));  
		}else{          
			$this->error('删除股东失败');
		}
		
	}	
	
	   
	  

	/*生成随机数*/ 
	public function randomkeys($length)
	{
		$pattern='1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
		for($i=0;$i<$length;$i++)
		{
		$key .= $pattern{mt_rand(0,35)};    
		}
		return $key;   
	}
	
	public function  getDiscount($level){
		if(empty($level)){
			$level = I('level');
			$type = I('type');  //type = ‘ajax’ 为ajax接口
		}
		if($level < 0  || $level>6){
			$desc = '参数错误';
		}else{
			if($level == 1){
				$desc = '首年'.(($this->operate_config['first_discount']<0) ? '最低价' : ($this->operate_config['first_discount'] .'折')).'，次年'. (($this->operate_config['first_morediscount']<0) ? '最低价' : ($this->operate_config['first_morediscount'] .'折'));
			}else if($level == 2){
				$desc = '首年'.(($this->operate_config['secend_discount']<0) ? '最低价' : ($this->operate_config['secend_discount'] .'折')).'，次年'. (($this->operate_config['secend_morediscount']<0) ? '最低价' : ($this->operate_config['secend_morediscount'] .'折'));
			}else if($level == 3){
				$desc = '首年'.(($this->operate_config['third_discount']<0) ? '最低价' : ($this->operate_config['third_discount'] .'折')).'，次年'. (($this->operate_config['third_morediscount']<0) ? '最低价' : ($this->operate_config['third_morediscount'] .'折'));
			}else if($level == 4){	
				$desc = '首年'.(($this->operate_config['fourth_discount']<0) ? '最低价' : ($this->operate_config['fourth_discount'] .'折')).'，次年'. (($this->operate_config['fourth_morediscount']<0) ? '最低价' : ($this->operate_config['fourth_morediscount'] .'折'));
			}else if($level == 5){
				$desc = '首年'.(($this->operate_config['fifth_discount']<0) ? '最低价' : ($this->operate_config['fifth_discount'] .'折')).'，次年'. (($this->operate_config['fifth_morediscount']<0) ? '最低价' : ($this->operate_config['fifth_morediscount'] .'折'));	
			}else if($level == 6){ 
				$desc = '首年'.(($this->operate_config['sixth_discount']<0) ? '最低价' : ($this->operate_config['sixth_discount'] .'折')).'，次年'. (($this->operate_config['sixth_morediscount']<0) ? '最低价' : ($this->operate_config['sixth_morediscount'] .'折'));
			}else{
				$level = '';
			}   
		}
		if($type == 'ajax'){
			echo $desc;
		}else{
			return $desc;
		}
		
	}
	
	public function ajax_change_edit(){
		$operate_id = I('operate_id');
		$state = I('state');
		$new_state = ($state == '1') ? 0 : 1;
		$rt = M('operate_center')->where(array('id'=>$operate_id))->save(array('is_edit'=>$new_state));
		if($rt !== false){
			echo 1;
		}else{
			echo -1;
		}
	}
	 
	
	/*运营商个人资料*/
    public function myprofile()
    {
		
	   	$ui['myprofile'] = 'active';
        $this->assign('ui',$ui);
        $this->display('myprofile');  
    }
	
	
	
	
	
	  
	
	
	
	

}
?>
