<?php
namespace Home\Controller;
use Think\Controller;
 
/**
 * XUNXIN PC 后台管理 后台主页
 * ============================================================================
 * 版权所有 2005-2010 厦门微聚点科技有限公司，并保留所有权利。
 * 网站地址: http://www.vjudian.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: youyan 
 * $Id: IndexController.class.php
 */
class ShareholderController extends AdminController
{  
	
	public function index(){
		$info = $this->GetOperateInfo();
		$this->assign('info',$info);
		$ui['index'] = 'active';
		$this->assign('ui',$ui);	
		$this->display('Index/operateIndex');
	}

	/**
	 * 获取运营商信息
	 */ 
	public function GetOperateInfo(){
		$loginname = session('loginname');
		$operate_info= M('operate_center')->where(array('login_name'=>$loginname))->find();  
		$w = array();
		$w['operate_id'] = $operate_info['id'];
		$w['status'] = 1;
		/*团队成员数*/
		$operate_info['shareholder_num'] = M('operate_shareholder')->where($w)->count();
		$w2 = array();
		$w2['is_get'] = 0;   
		$w2['operate_id'] = $operate_info['id'];
		$unget_price = M('operate_total_price')->where($w2)->sum('value');
		/*未打款收益*/
		$operate_info['unget_price'] = empty($unget_price) ? '0.00' : $unget_price;
		$store = M('stores');
		$w3 = array();
		$w3['isdelete'] = 0;
		$w3['operate_id'] = $operate_info['id'];
		/*店铺总数量*/
		$operate_info['store_num'] = $store->where($w3)->count();
		$w4 = array();
		$w4['opentype'] = 2;
		$w4['isdelete'] = 0;
		$w4['operate_id'] = $operate_info['id'];
		/*直接开户店铺数量*/
		$operate_info['store_num2'] = $store->where($w4)->count();
		$w5 = array();
		$w5['is_try'] = 1;
		$w5['isdelete'] = 0;
		$w5['operate_id'] = $operate_info['id'];
		/*试用期店铺数量*/
		$operate_info['store_num3'] = $store->where($w4)->count();
		$w6 = array();
		$w6['isdelete'] = 0;
		$w6['package_id'] = 1;
		$w6['operate_id'] = $operate_info['id'];
		/*专业版店铺数量*/ 
		$operate_info['store_num4'] = $store->where($w6)->count();
		$w7 = array();
		$w7['isdelete'] = 0;
		$w7['package_id'] = 2;
		$w7['operate_id'] = $operate_info['id'];
		/*企业版店铺数量*/    
		$operate_info['store_num5'] = $store->where($w7)->count();
		$w8 = array();
		$w8['operate_id'] =  $operate_info['id'];
		$total_price = M('operate_total_price')->where($w8)->sum('value');
		/*运营中心总收益*/
		$operate_info['total_price'] = empty($total_price) ? '0.00' : $total_price;
		$w9 = array();
		$w9['is_get'] = 1;
		$w9['operate_id'] = $operate_info['id'];
		$get_price = M('operate_total_price')->where($w9)->sum('value');
		/*已打款收益*/  
		$operate_info['get_price'] = empty($get_price) ? '0.00' : $get_price;  
		$w10 = array();
		$w10['operate_id']	= $operate_info['id'];
		$w10['type'] = 2;
		$group_price = M('operate_shareholder_price_record')->where($w10)->sum('value');
		/*成员分红*/
		$operate_info['group_price'] = empty($group_price) ? '0.00' : $group_price; 
		$w11 = array();
		$w11['operate_id']	= $operate_info['id'];
		$w11['type'] = 1;
		$recommend_price = M('operate_shareholder_price_record')->where($w11)->sum('value');
		/*成员佣金*/   
		$operate_info['recommend_price'] = empty($recommend_price) ? '0.00' : $recommend_price; 
		$w12 = array();
		$w12['is_get']	= 0;
		$w12['operate_id']	= $operate_info['id'];
		$holder_ungetprice = M('operate_shareholder_total_price')->where($w12)->sum('value');
		/*成员总收益未打款*/
		$operate_info['holder_ungetprice'] = empty($holder_ungetprice) ? '0.00' : $holder_ungetprice; 
		
		/*分享链接*/
		$share_url = M('system_config')->where(array('status'=>1))->getField('share_url');
		$operate_info['share_url'] = $share_url.((strpos($share_url, '?') !== false) ? '&' : '?').'operate_sn='.$operate_info['operate_sn']; 
		$store_url = M('system_config')->where(array('status'=>1))->getField('store_url');
		$operate_info['pc_share_url'] = $store_url.'/admin.php?m=home&c=open_store&a=index&operate_sn='.$operate_info['operate_sn']; 
		return $operate_info; 
		 
	}
	/*预充值明细*/
	public function preloaded()
    {    
		$loginname = session('loginname');
		$operate_id = M('operate_center')->where(array('login_name'=>$loginname))->getField('id');  
		$trade_record = M('operate_trade_record');
		$info = array();
		$w  = array();
		$w['lm_operate_trade_record.status'] = 1;
		$w['operate_id'] = $operate_id;
		$order_sn = I('order_sn'); 
		if(!empty($order_sn)){
			$w['lm_operate_trade_record.order_sn'] = array('like',"%$order_sn%");
		}
		$type = I('type');
		if(!empty($type)){
			$w['lm_operate_trade_record.type'] = $type;
		}
		$Time1 = I('Time1');
		$Time2 = I('Time2');  
		if(!empty($Time1) && empty($Time2))  
		{
			$t = strtotime($Time1)+24*60*60 ;
			$w['_string']= "lm_operate_trade_record.addtime >= '".strtotime($Time1) ."'&& lm_operate_trade_record.addtime < '".$t."'";
		}         
		if(!empty($Time2) && empty($Time1))
		{  
			$w['_string']= "lm_operate_trade_record.addtime<= '". strtotime($Time2)."'";  
		}  
		if(!empty($Time2) && !empty($Time1))
		{   
			$t = strtotime($Time2)+24*60*60 ;
			$w['_string']= "lm_operate_trade_record.addtime >= '".strtotime($Time1) ."'&& lm_operate_trade_record.addtime < '" .$t."'";  
		}    
		$count = $trade_record->join('LEFT JOIN lm_operate_center ON lm_operate_center.id = lm_operate_trade_record.operate_id')->where($w)->count();
		$info['count'] = $count;
		$Page = new \Think\Page($count, 15); 
        $show = $Page->show();  
		$lists = $trade_record->join('LEFT JOIN lm_operate_center ON lm_operate_center.id = lm_operate_trade_record.operate_id')->where($w)->order('lm_operate_trade_record.addtime DESC,lm_operate_trade_record.id DESC')->field('lm_operate_trade_record.*,lm_operate_center.operate_name')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('page',$show);   
		$this->assign('lists',$lists);
		
		if(empty($type)){
			$w['lm_operate_trade_record.type'] = 1;
			$type1_value = $trade_record->join('LEFT JOIN lm_operate_center ON lm_operate_center.id = lm_operate_trade_record.operate_id')->where($w)->sum('value');
			$w['lm_operate_trade_record.type'] = 2;
			$type2_value = $trade_record->join('LEFT JOIN lm_operate_center ON lm_operate_center.id = lm_operate_trade_record.operate_id')->where($w)->sum('value');
		}else if($type == 1){
			$type1_value = $trade_record->join('LEFT JOIN lm_operate_center ON lm_operate_center.id = lm_operate_trade_record.operate_id')->where($w)->sum('value');
			$type2_value = 0;
		}else if($type == 2){
			$type1_value  = 0;
			$type2_value = $trade_record->join('LEFT JOIN lm_operate_center ON lm_operate_center.id = lm_operate_trade_record.operate_id')->where($w)->sum('value');
		}
		$info['type1_value'] = empty($type1_value) ? '0.00' : $type1_value;
		$info['type2_value'] = empty($type2_value) ? '0.00' : $type2_value;
		   
		
		$w2 = array();
		$w2['status'] = 1;
		$w2['type'] = 1;
		$w2['operate_id'] = $operate_id;
		$recharge = $trade_record->where($w2)->sum('value');
		$recharge = empty($recharge) ? '0.00' : $recharge;
		$w3 = array();
		$w3['operate_id'] = $operate_id;
		$w3['status'] = 1;
		$w3['type'] = 2;
		$used = $trade_record->where($w3)->sum('value');
		$used = empty($used) ? '0.00' : $used;
		$unused = floatval($recharge)+ floatval($used);
		 
		$info['recharge'] = $recharge;
		$info['unused'] = $unused;
		$this->assign('info',$info);
		
	   	$ui['preloaded'] = 'active';
        $this->assign('ui',$ui);
        $this->display('preloaded');  
    }

	/**我的资料*/
       
	public function operate_info(){   
		$center = M('operate_center');
		$member = M('members');
		$operate_total_price = M('operate_total_price'); 
		$w = array();
		$w['status'] = '1';  
		$id = M('operate_center')->where(array('login_name'=>session('loginname')))->getField('id'); 
		$this->assign('act','info');
		$w3 = array();
		$w3['id'] =$id;   
		$w3['status'] = 1;
		$info = $center->where($w3)->find();  
		$this->assign('info',$info);   	
		$ui['shareholder_operate_info'] = 'active';
		$this->assign('ui',$ui);   	
		$this->display('Shareholder:myprofile');
	}
	 
	public function operate_edit(){
		$act = $_POST['act'];  
		$center = M('operate_center');
		$admin = M('admin');
		$member = M('members');
		$data = array();
		$login_name = $_POST['login_name'];
		$login_password = $_POST['login_password'];
		$link_name = $_POST['link_name'];
		$link_tel = $_POST['link_tel'];
		$bank_name = $_POST['bank_name'];
		$bank_sn = $_POST['bank_sn'];
		$bank_username = $_POST['bank_username'];
		if(empty($login_name)){
			$this->error('登录账户不能为空');
			die;    
		}
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
		$data['status'] = 1;
		$data['edittime'] = mktime();  
		$id = $_POST['id'];
		if(!empty($login_password)){
			$admin->where(array('loginname'=>$login_name,'status'=>1))->save(array('password'=>md5($login_password)));
		}   
		$jg = $center->where(array('id'=>$id))->save($data);
		if($jg === false){
			$this->error('修改运营商失败');
		}else{
			$this->addAdminLog('3',"修改运营商操作,运营商ID：".$id);
			$this->success('修改运营商成功',U('Shareholder/operate_info'));
		}
		
	} 
	 
	/*运营商成员列表*/
	public function operate_shareholder_list(){
		$center = M('operate_center');
		$operate_id = $center->where(array('login_name'=>session('loginname')))->getField('id');
		   
		$shareholder = M('operate_shareholder');
		$info = $center->where(array('id'=>$operate_id,'status'=>1))->find();
		if(empty($info) || empty($operate_id)){
			$this->error('改运营商不存在');
			die;    
		}       
		$lists = $shareholder->where(array('operate_id'=>$operate_id,'status'=>1))->select();
		$this->assign('lists',$lists);
		$ui['group_list'] = 'active';
		$this->assign('operate_id',$operate_id);  
		$this->assign('ui',$ui);       
        $this->display('operate_shareholder_list');
		    
	} 
	
	/*运营商股东详情*/
	public function  operate_shareholder_info(){
		$id = I('id');
		$operate_id = I('operate_id');
		$shareholder = M('operate_shareholder');
		if(empty($id)){
			$act = 'insert';
		}else{   
			$act = 'info';
			$info = $shareholder->where(array('id'=>$id,'status'=>1))->find();
			$this->assign('info',$info);
			
		}
		$ui['shareholder_list'] = 'active';   
		$this->assign('operate_id',$operate_id);   
		$this->assign('ui',$ui); 
		$this->assign('act',$act); 
		$this->display('operate_shareholder_info');
	} 

	/*运营商股东编辑*/
	public function operate_shareholder_edit(){
		$act = $_POST['act'];  
		$shareholder = M('operate_shareholder');
		$member = M('members');
		$data = array();
		$operate_id = $_POST['operate_id'];  
		$shareholder_role = $_POST['shareholder_role'];
		$shareholder_name = $_POST['shareholder_name'];
		$shareholder_tel = $_POST['shareholder_tel'];
		$share_rate = $_POST['share_rate'];
		$share_rate = empty($share_rate) ? 0 : $share_rate;
		$bank_name = $_POST['bank_name'];
		$bank_sn = $_POST['bank_sn'];
		$bank_username = $_POST['bank_username'];
		$member_name = $_POST['member_name'];
		$member_info = $member->where(array('member_name'=>$member_name))->find();
		if(empty($member_info)){
			$this->error('绑定会员账号不存在');
			die;
		}
		
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
	
		$data['operate_id'] = $operate_id;
		$data['shareholder_name'] = $shareholder_name;
		$data['shareholder_role'] = $shareholder_role;
		$data['shareholder_tel'] = $shareholder_tel;
		$data['bank_name'] = $bank_name;
		$data['bank_username'] = $bank_username;
		$data['bank_sn'] = $bank_sn;
		$data['member_id'] = $member_info['member_id'];
		$data['member_name'] = $member_name;
		$data['share_rate'] = $share_rate;
		 
		$data['status'] = 1;
		$check1 = array();	
		$check2 = array();
		$check3 = array();
		
		if($act == 'insert'){
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
			$this->error('运营商股东分红比例和不能超过100%');   
		}   
		
		if($act == 'info'){
			$jg = $shareholder->where(array('id'=>$id))->save($data);
			if($jg === false){
				$this->error('修改股东信息失败');
			}else{
				$this->addAdminLog('4',"修改股东信息操作,股东ID：".$id);
				$this->success('修改股东信息成功',U('Shareholder/operate_shareholder_list',array('id'=>$operate_id)));
			}
		}   
		else if($act == 'insert'){
			if($sid = $shareholder->add($data)){    
				$this->addAdminLog('4',"添加股东信息操作,股东ID：".$sid);
				$this->success('添加股东信息成功',U('Shareholder/operate_shareholder_list',array('id'=>$operate_id)));
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
	
	/* 运营商每月分润明细 */
	public function operate_month_price_record(){
		$center = M('operate_center');
		$operate_id = $center->where(array('login_name'=>session('loginname')))->getField('id');
		$lists = array();
		if(!empty($operate_id)){
			$center = M('operate_center');
			$w = array();
			$w['id'] = $operate_id;
			$w['status'] = 1;
			$operate_info = $center->where($w)->find();
			$operate_total_price = M('operate_total_price');
			$w2 = array();
			$w2['operate_id'] = $operate_id;
			$lists = $operate_total_price->where($w2)->order('month_time DESC')->select();
			$operate_info['all_price'] = $operate_total_price->where($w2)->sum('value');
			$operate_info['all_price'] = empty($operate_info['all_price']) ? 0 : $operate_info['all_price'];   
			$w3 = array();
			$w3['operate_id'] = $operate_id;
			$w3['is_get'] = 1; 
			$operate_info['get_price'] = $operate_total_price->where($w3)->sum('value');
			$operate_info['get_price'] = empty($operate_info['get_price']) ? 0 : $operate_info['get_price'];
			if (I('get.act') == 'export'){
				$this->exportExcel($lists,$operate_info);
			}
			$this->assign('operate_info',$operate_info);
			$this->assign('lists',$lists);  
		}        
		$ui['fund_detail'] = 'active';   
		$this->assign('ui',$ui); 
		$this->display('operate_month_price_record');
	}
	
	/*确认给运营商打款*/
	public function ajax_operate_confirm_get(){
		$id = I('id');  
		$img = I('img_url'); 
		$operate_total_price = M('operate_total_price');
		$w = array();
		$w['id'] = $id; 
		$data = array();
		$info = $operate_total_price->where($w)->find();
		if(!file_get_contents($img)){
			$data['result'] = -1;
			$data['desc'] = '凭证未上传';   
		}     
		elseif($info['is_get'] == 1){
			$data['result'] = -1;
			$data['desc'] = '该笔记录已经打款过了';
		}
		elseif($info['month'] == date('Y-m')){  
			$data['result'] = -1;
			$data['desc'] = '现在暂不能打款该月的资金';
		}else{
			$check = $operate_total_price->where($w)->save(array('is_get'=>1,'get_time'=>mktime(),'payment_img'=>$img));
			if($check !== false){
				$da = array();
				$da['operate_id'] = $info['operate_id'];
				$da['operate_name'] = $info['operate_name'];
				$da['type'] = 2;   
				$da['value'] = 0-$info['value'];
				$da['desc'] = "打款".$info['month'].'月份分润';
				$da['pay_name'] = '线下支付';
				$da['periods'] = $info['month'];
				$da['addtime'] = mktime();   
				M('operate_price_record')->add($da);
				$data['result'] = 1;
			}else{
				$data['result'] = -1;
				$data['desc'] = '更新打款状态失败';
			}
			
		}  
		echo json_encode($data,JSON_UNESCAPED_UNICODE);
	}
	
	/*收益明细*/
	public function income_detail(){
		$id = I('id');
		$operate_id = M('operate_center')->where(array('login_name'=>session('loginname')))->getField('id');
		$w = array();
		$w['lm_vip_orders.operate_id'] = $operate_id;	
		if(!empty($id)){
			$record_info = M('operate_total_price')->where(array('id'=>$id))->find();
			$t1 = strtotime($record_info['month']);
			$t2 = strtotime(date('Y',$t1).'-'.(date('m',$t1)+1)); 
			$w['lm_vip_orders.rechargetime'] = array(array('egt',$t1),array('lt',$t2));
		}
		$ordersn = I('ordersn');
		if(!empty($ordersn)){
			$w['lm_vip_orders.orderSn'] = array("like","%$ordersn%");	
		}
		$package_id = I('package_id');
		if(!empty($package_id)){
			$w['lm_vip_orders.packageid'] = $package_id;	
		}
		$member_name = I('member_name');
		if(!empty($member_name)){
			$w['lm_vip_orders.member_name'] = array("like","%$member_name%");	
		}
		$recommend_code = I('recommend_code');
		if(!empty($recommend_code)){
			$w['lm_vip_orders.recommend_code'] = $recommend_code;	
		}
		$order = M('vip_orders');
		$lists = $order->join('LEFT JOIN lm_package_list ON lm_package_list.packageid = lm_vip_orders.packageid')->where($w)->field('lm_vip_orders.*,lm_package_list.name')->order('lm_vip_orders.id DESC')->select(); 
		$this->assign('lists',$lists);
		$package =M('package_list');
		$where = array();
		$where['status'] = 1;
		$package_list = $package->where($where)->order('sort DESC')->field('packageid,name')->select();
		$this->assign('package_list',$package_list);
		$ui['income_detail'] = 'active';
		$this->assign('ui',$ui);
		$this->display('income_detail');
	}  
	
	
   /* 运营商每日分润明细*/      
	public function operate_day_price_record(){
		$id = I('id'); 
		$operate_id = M('operate_center')->where(array('login_name'=>session('loginname')))->getField('id');		
		$w = array();
		if(!empty($id)){
			$record_info = M('operate_total_price')->where(array('id'=>$id))->find();
			$w['periods'] = array('like',$record_info['month']."%");
			$operate_name = $record_info['operate_name'];
		}
		if(!empty($Time1) && empty($Time2)){
			$w['_string'] = "periods ='".$Time1."'";
		}
		else if(!empty($Time1) && !empty($Time2)){
			$w['_string'] = "periods >='".$Time1."' and periods<'".$Time2."'";
		}
		else if(empty($Time1) && !empty($Time2)){
			$w['_string'] = "periods <='".$Time2."'";
		}
		if(!empty($operate_id)){
			$w['operate_id'] = $operate_id;
		}
		$style = I('style');
		if(!empty($style)){
			$w['type'] = $style;	
		} 
		$lists = array();
		$record =M('operate_price_record');   
		$lists =  $record->where($w)->field('operate_id,id,type,operate_name,value total,addtime,periods,desc')->select();
		
		$this->assign('lists',$lists);    
		$this->assign('Time1',$Time1); 
		$this->assign('Time2',$Time2);    
		$this->assign('operate_name',$operate_name); 
		$ui['operate_shareinfo'] = 'active';
		$this->assign('ui',$ui); 
		$this->display('operate_day_price_record');
	}
     
	/*运营商所有股东每月分润明细*/
	public function  all_holder_month_price_record(){
		$operate_id = M('operate_center')->where(array('login_name'=>session('loginname')))->getField('id');  
		$time = I('time');
		$where = array();
		$where['lm_operate_shareholder_total_price.operate_id'] = $operate_id;
		$where['lm_operate_shareholder_total_price.month'] = $time;
		$operate_shareholder_total_price = M('operate_shareholder_total_price');
		$lists = $operate_shareholder_total_price->join('LEFT JOIN lm_operate_shareholder ON lm_operate_shareholder.id=lm_operate_shareholder_total_price.shareholder_id')->where($where)->field('lm_operate_shareholder.*,lm_operate_shareholder_total_price.id mid,lm_operate_shareholder_total_price.value,lm_operate_shareholder_total_price.is_get,lm_operate_shareholder_total_price.get_time,lm_operate_shareholder_total_price.payment_img')->select();   
		$info = array();
		$info['month'] = $time;
		$info['total'] =$operate_shareholder_total_price->where($where)->sum('value');
		$info['status'] = M('operate_total_price')->where(array('operate_id'=>$operate_id,'month'=>$time))->getField('is_get');   
		$where['lm_operate_shareholder_total_price.is_get'] = 1;
		$get_price = $operate_shareholder_total_price->where($where)->sum('value');
		$info['get_price'] = empty($get_price) ? 0 : $get_price;
		$this->assign('info',$info);
		$ui['group_list'] = 'active';
		$this->assign('ui',$ui);  
		$this->assign('lists',$lists);
		$this->display('all_holder_month_price_record');
	}    
	
	/* 单个股东每月分润明细 (按股东查看)*/
	public function holder_month_price_record(){
		$shareholder_id = I('shareholder_id');
		$lists = array();
		if(!empty($shareholder_id)){
			$operate_shareholder = M('operate_shareholder');
			$w = array();
			$w['id'] = $shareholder_id;
			$w['status'] = 1;
			$shareholder_info = $operate_shareholder->where($w)->find();
			$shareholder_info['operate_name'] = M('operate_center')->where(array('id'=>$shareholder_info['operate_id']))->getField('operate_name');  
			$operate_shareholder_total_price = M('operate_shareholder_total_price');
			$w2 = array();
			$w2['shareholder_id'] = $shareholder_id;
			$lists = $operate_shareholder_total_price->where($w2)->order('month_time DESC')->select();
			foreach($lists as $k=>$list){
				$where = array();
				$where['operate_id'] = $list['operate_id'];
				$where['month'] = $list['month'];
				$lists[$k]['operate_get'] = M('operate_total_price')->where($where)->getField('is_get');
			}
			$shareholder_info['all_price'] = $operate_shareholder_total_price->where($w2)->sum('value');
			$shareholder_info['all_price'] = empty($shareholder_info['all_price']) ? 0 : $shareholder_info['all_price'];   
			$w3 = array();
			$w3['shareholder_id'] = $shareholder_id;
			$w3['is_get'] = 1; 
			$shareholder_info['get_price'] = $operate_shareholder_total_price->where($w3)->sum('value');
			$shareholder_info['get_price'] = empty($shareholder_info['get_price']) ? 0 : $shareholder_info['get_price'];
			$this->assign('shareholder_info',$shareholder_info);
			$this->assign('lists',$lists);  
		}           
		$ui['shareholder_list'] = 'active';
		$this->assign('ui',$ui);	
		$this->display('holder_month_price_record');
	}
		
	/* 运营商单日分润详情*/      
	public function operate_day_price_detail(){
		$operate_id = M('operate_center')->where(array('login_name'=>session('loginname')))->getField('id');  
		$time = I('time');
		$where = array();
		$where['operate_id'] = $operate_id;
		$where['periods'] = $time;
		$lists = M('operate_price_record')->where($where)->order('id DESC')->select();
		$this->assign('lists',$lists);
		$ui['operate_shareinfo'] = 'active';
		$this->assign('ui',$ui);     
		$this->display('operate_day_price_detail');
		
	}   
		  
	/* 股东每日分润明细*/   
	public function holder_day_price_record(){
		$id = I('id');
		$w = array(); 
		$operate_id = M('operate_center')->where(array('login_name'=>session('loginname')))->getField('id');  
		$w['operate_id'] = $operate_id;
		if(!empty($id)){
			$record_info = M('operate_shareholder_total_price')->where(array('id'=>$id))->find();
			$shareholder_id = $record_info['shareholder_id'];
			$Time1 = $record_info['month'].'-01';
			$t = strtotime($Time1);
			$Time2 = date('Y-m-d',mktime(0,0,0,date('m',$t)+1,1,date('Y',$t)));
			$shareholder_name = $record_info['shareholder_name'];
		}
		else{   
			$Time1 = I('Time1');
			$Time2 = I('Time2');
			$shareholder_name = I('shareholder_name');
			if(!empty($shareholder_name)){
				$w['shareholder_name'] = array('like','%'.$shareholder_name.'%');
			}
		}
		if(!empty($Time1) && empty($Time2)){
			$w['_string'] = "periods ='".$Time1."'";
		}
		else if(!empty($Time1) && !empty($Time2)){
			$w['_string'] = "periods >='".$Time1."' and periods<'".$Time2."'";
		}
		else if(empty($Time1) && !empty($Time2)){
			$w['_string'] = "periods <='".$Time2."'";
		}
		if(!empty($shareholder_id)){
			$w['shareholder_id'] = $shareholder_id;
		}
		$style = I('style');
		if(!empty($style)){
			if($style == 1){
				$w['type'] = array('elt',2);
			}else{
				$w['type'] = 3;
			}   
		}   
		$lists = array();
		$record =M('operate_shareholder_price_record');   
		$lists =  $record->where($w)->field('shareholder_id,id,value total,type,shareholder_name,value,addtime,periods,desc')->order('id DESC')->select(); 
		$this->assign('lists',$lists);   
		$this->assign('Time1',$Time1); 
		$this->assign('Time2',$Time2);       
		$this->assign('shareholder_name',$shareholder_name); 
		$ui['shareholder_shareinfo'] = 'active';
		$this->assign('ui',$ui);    
		$this->display('holder_day_price_record');
	}      
      
	/*确认给股东打款*/
	public function ajax_holder_confirm_get(){
		$id = I('id');
		$img = I('img_url');
		$operate_shareholder_total_price = M('operate_shareholder_total_price');
		$operate_id = M('operate_center')->where(array('login_name'=>session('loginname')))->getField('id'); 
		
		$w = array();   
		$w['id'] = $id;
		$w['operate_id'] = $operate_id;
		$data = array(); 
		$info = $operate_shareholder_total_price->where($w)->find();
		
		if(!file_get_contents($img)){
			$data['result'] = -1;
			$data['desc'] = '凭证未上传';  
		}  
		elseif($info['is_get'] == 1){
			$data['result'] = -1;
			$data['desc'] = '该笔记录已经打款过了';
		}
		elseif($info['month'] == date('Y-m')){  
			$data['result'] = -1;
			$data['desc'] = '现在暂不能打款该月的资金';
		}else{
			$check = $operate_shareholder_total_price->where($w)->save(array('is_get'=>1,'get_time'=>mktime(),'payment_img'=>$img));
			if($check !== false){
				$da = array();
				$da['operate_id'] = $info['operate_id'];
				$da['operate_name'] = $info['operate_name'];
				$da['shareholder_id'] = $info['shareholder_id'];
				$da['shareholder_name'] = $info['shareholder_name'];
				$da['type'] = 3;  
				$da['value'] = 0-$info['value'];
				$da['desc'] = "打款".$info['month'].'月份分润';
				$da['periods'] = date('Y-m-d',mktime());
				$da['addtime'] = mktime();
				M('operate_shareholder_price_record')->add($da);
				$data['result'] = 1;   
			}else{  
				$data['result'] = -1;
				$data['desc'] = '更新打款状态失败';
			}
			   
		}
		echo json_encode($data,JSON_UNESCAPED_UNICODE);
	}
	
	
	
	
	
	/**************************************************************************/
	/*********************************店铺模块*********************************/
	/**************************************************************************/
	
	
	
	/*运营商店铺*/
	public function shop_list()
    {
      	$package = M('package_list');
		$center = M('operate_center');
		$operate_id = $center->where(array('login_name'=>session('loginname')))->getField('id');
		$w =array();
		$w['status'] = 1;
		$package_list = $package->where($w)->field('packageid,name')->select();
		$this->assign('package_list',$package_list);
		$store = M('stores');
		$where = array();  
		$where['lm_stores.isdelete'] = 0;  
		$where['lm_stores.operate_id'] = $operate_id;
		$member_name = I('member_name');
		if(!empty($member_name)){
			$where['lm_stores.member_name'] = $member_name;
		}
		$store_name= I('store_name');
		if(!empty($store_name)){
			$where['lm_stores.store_name'] = array('like',"%$store_name%");
		}
		$lianxi_member_name= I('lianxi_member_name');
		if(!empty($lianxi_member_name)){
			$where['lm_stores.lianxi_member_name'] = array('like',"%$lianxi_member_name%");
		}
		$lianxi_member_tel= I('lianxi_member_tel');
		if(!empty($lianxi_member_tel)){
			$where['lm_stores.lianxi_member_tel'] = array('like',"%$lianxi_member_tel%");
		}
		$choose_level= I('choose_level');
		if(!empty($choose_level)){
			if($choose_level == '-1'){
				$where['lm_stores.is_try'] = 1;
			}else{
				$where['lm_stores.is_try'] = 0;
				$where['package_id'] = $choose_level;
			}
		}
		$store_parenttype_id= I('store_parenttype_id');
		if(!empty($store_parenttype_id)){
			$where['lm_stores.store_parenttype_id'] = $store_parenttype_id;
		}
		$store_childtype_id= I('store_childtype_id');
		if(!empty($store_childtype_id)){
			$where['lm_stores.store_childtype_id'] = $store_childtype_id;
		}
		$endtime = I('endtime');
		if(!empty($endtime)){
			if($endtime == '1'){
				$where['lm_stores.vip_endtime'] = array('lt',mktime());
			}else if($endtime == '2'){
				$where['lm_stores.vip_endtime'] = array('lt',mktime()+7*24*3600);
			}else if($endtime == '3'){
				$where['lm_stores.vip_endtime'] = array('lt',mktime()+30*24*3600);
			}
		}
		$opentype = I('opentype');
		if(!empty($opentype)){
			$where['lm_stores.opentype'] = $opentype;
		}
		$operation_number = I('operation_number');
		if(!empty($operation_number)){
			$where['lm_stores.operation_number'] = $operation_number;
		} 
		$Time1 = I('Time1'); 
		$Time2 = I('Time2');  
		if(!empty($Time1) && empty($Time2))  
		{
			$t = strtotime($Time1)+24*60*60 ;
			$where['_string']= "(lm_stores.account_time >= '".strtotime($Time1) ."'&& lm_stores.account_time < '".$t."') OR (lm_stores.recharge_time >= '".strtotime($Time1) ."'&& lm_stores.recharge_time < '".$t."')";
		}         
		if(!empty($Time2) && empty($Time1))  
		{  
			$where['_string']= "(lm_stores.account_time<= '". strtotime($Time2)."') OR (lm_stores.recharge_time<= '". strtotime($Time2)."')";  
		}  
		if(!empty($Time2) && !empty($Time1))
		{   
			$t = strtotime($Time2)+24*60*60 ;
			$where['_string']= "(lm_stores.account_time >= '".strtotime($Time1) ."'&& lm_stores.account_time < '" .$t."') OR (lm_stores.recharge_time >= '".strtotime($Time1) ."'&& lm_stores.recharge_time < '" .$t."')";  
		}
		  
		$count = $store->where($where)->count(); // 查询满足要求的总记录
        $Page = new \Think\Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show(); // 分页显示输出  
		$lists = $store->join('LEFT JOIN lm_package_list ON lm_package_list.packageid = lm_stores.package_id')->where($where)->order('lm_stores.store_id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->field('lm_stores.store_id,lm_stores.store_name,lm_stores.member_name,lm_stores.lianxi_member_name,lm_stores.lianxi_member_tel,lm_stores.store_parenttype_id,lm_stores.store_childtype_id,lm_stores.package_id,lm_stores.operation_number,lm_stores.account_time,lm_stores.recharge_time,lm_stores.vip_endtime,lm_stores.operate_id,lm_package_list.name package_name,lm_stores.opentype')->select(); 
		$commontype = M('commontype');
		 
		foreach($lists as $k=>$list){
			$lists[$k]['store_parenttype_name'] = $commontype->where(array('id'=>$list['store_parenttype_id'],'isdelete'=>0))->getField('store_type_name');
			$lists[$k]['store_childtype_name'] = $commontype->where(array('id'=>$list['store_childtype_id'],'isdelete'=>0))->getField('store_type_name');
			$lists[$k]['operate_name'] = $center->where(array('id'=>$list['operate_id']))->getField('operate_name');
		}
		
		$commontype_parent = $commontype->where(array('isdelete'=>0,'store_parenttype_id'=>0))->field('id,store_type_name')->select();
	    $commontype_child = array();
		foreach($commontype_parent as $k2=>$parent){
			$childinfo = $commontype->where(array('isdelete'=>0,'store_parenttype_id'=>$parent['id']))->field('id,store_type_name')->select();
			foreach($childinfo as $k3=>$child){
				$commontype_child[$k2][$k3][0] = $child['id'];
				$commontype_child[$k2][$k3][1] = $child['store_type_name'];
			}
		} 

		$this->assign('commontype_parent',$commontype_parent);
		$this->assign('childs',json_encode($commontype_child));
		$this->assign('lists',$lists); 
		$this->assign('count',$count);		
        $ui['shop_list'] = 'active';  
        $this->assign('ui',$ui);
		$this->assign('page',$show);
        $this->display("shop_list"); // 输出模板
    }  

	/**
     * 新增/查看店铺
     */
    
	public function shop_info() 
    {
		$center = M('operate_center');
		$operate_id = $center->where(array('login_name'=>session('loginname')))->getField('id');
		$operate_sn =  $center->where(array('login_name'=>session('loginname')))->getField('operate_sn');
		$add_data = array();
		$add_data['ad_id'] = $operate_sn; 
		DataRecoed($add_data);  
		$shopid = I('shop_id');
		$commontype = M('commontype');
		if(!empty($shopid)){
			$act = 'info';
			$shopinfo = M('stores')->where(array('store_id'=>$shopid,'operate_id'=>$operate_id))->find();
			if(empty($shopinfo)){
				$this->error('该店铺不存在');
			} 
			
			$shopinfo['try_time'] = round($shopinfo['try_time']/3600,0);
			$shopinfo['parenttype_name'] = $commontype->where(array('id'=>$shopinfo['store_parenttype_id'],'isdelete'=>0))->getField('store_type_name');
			$shopinfo['childtype_name'] = $commontype->where(array('id'=>$shopinfo['store_childtype_id'],'isdelete'=>0))->getField('store_type_name');
			$this->assign('shopinfo',$shopinfo); 
			
			$where = array();
			$where['packageid'] = $shopinfo['package_id'];
			$where['status'] = 1;
			$packageinfo = M('package_list')->where($where)->find();
			if($shopinfo['is_try'] == 1){
				$news = M('shareholder_package_edit')->where(array('operate_id'=>$shopinfo['operate_id'],'package_id'=>$shopinfo['package_id'],'status'=>1))->find();
				if(!empty($news)){
					$packageinfo['market_price'] = $news['package_price'];
					$packageinfo['market_price2'] = $news['package_price2'];
					$packageinfo['market_price3'] = $news['package_price3'];
				}             
			}
			$this->assign('packageinfo',$packageinfo);		
				
		}else{   
			$act = 'insert'; 
		}  
		 
		/*店铺行业列表*/
		
		$commontype_parent = $commontype->where(array('isdelete'=>0,'store_parenttype_id'=>0))->field('id,store_type_name')->select();
	    $commontype_child = array();
		foreach($commontype_parent as $k2=>$parent){
			$childinfo = $commontype->where(array('isdelete'=>0,'store_parenttype_id'=>$parent['id']))->field('id,store_type_name')->select();
			foreach($childinfo as $k3=>$child){
				$commontype_child[$k2][$k3][0] = $child['id'];
				$commontype_child[$k2][$k3][1] = $child['store_type_name'];
			}
		}    
		$this->assign('commontype_parent',$commontype_parent);
		$this->assign('childs',json_encode($commontype_child));
		
		/*套餐列表*/
		$package = M('package_list');
		$w = array();
		$w['is_show'] = 1;
		$w['status'] = 1;
		$package_list = $package->where($w)->order('sort DESC')->field('packageid,name,up_level')->select();
		$this->assign('package_list',$package_list); 
	
	    /*获取账号列表*/
		$param = array();
		$param['vip'] = $package_list[0]['up_level'];
		$param['channel_id'] = 0;
		$sync_url = M('system_config')->where(array('status'=>1))->getField('sync_url');    
		$url = "http://".$sync_url."/xxapi/index.php?act=operate_openaccount&op=selectXunxinnum";  
		$member_name_list = $this->postCurl($url,$param);
		$this->assign('member_name_list',json_decode($member_name_list,true));
	
		/*运营商列表*/
		$w2 = array();
		$w2['status'] = 1;
		$w2['id'] = $operate_id;
		$operate_list = $center->where($w2)->field('try_time,operate_name,operate_sn')->select();
		
		$shareholder = M('operate_shareholder');
		$w3 = array();
		$w3['status'] = 1;
		$w3['operate_id'] = $operate_id;
		$list2 = $shareholder->where($w3)->field('shareholder_name,shareholder_sn')->select();
		foreach($list2 as $list){
			$data = array();
			$data['try_time'] = $operate_list[0]['try_time'];
			$data['operate_name'] = $list['shareholder_name'];
			$data['operate_sn'] = $list['shareholder_sn'];
			$operate_list[] = $data;
		}
		$this->assign('operate_list',$operate_list);
		$this->assign('act',$act); 
	   	$ui['shop_info'] = 'active';
        $this->assign('ui',$ui);
        $this->display('shop_info');  
    }

   
	/**
	 * 编辑店铺
	 */
	public function shop_edit(){
		$files = $_FILES;
		if(!($files['fileImg1']['error'] == 4 && $files['fileImg2']['error'] == 4 && $files['fileImg3']['error'] == 4 && $files['fileImg4']['error'] == 4)){
			$imginfo = $this->upload($files);
		}else{
			$imginfo = array();
		}
		$center = M('operate_center');
		$operate_id = $center->where(array('login_name'=>session('loginname')))->getField('id');
		$datas = I();	
		$act = $datas['act'];
		if(!empty($imginfo['fileImg1'])){
			$url1 = ADMIN_URL .'/images/'.$imginfo['fileImg1']['savepath'].$imginfo['fileImg1']['savename'];
		}/* else{
			if($act == 'insert'){
				$this->error('营业执照必须上传！');
			}
		} */
		if(!empty($imginfo['fileImg2'])){
			$url2 = ADMIN_URL .'/images/'.$imginfo['fileImg2']['savepath'].$imginfo['fileImg2']['savename'];
		}/* else{
			if($act == 'insert'){
				$this->error('法人身份证正面必须上传！');
			}
		} */
		if(!empty($imginfo['fileImg3'])){
			$url3 = ADMIN_URL .'/images/'.$imginfo['fileImg3']['savepath'].$imginfo['fileImg3']['savename'];
		}/* else{
			if($act == 'insert'){
				$this->error('法人身份证反面必须上传！');
			}
		}  */
		if(!empty($imginfo['fileImg4'])){
			$url4 = ADMIN_URL .'/images/'.$imginfo['fileImg4']['savepath'].$imginfo['fileImg4']['savename'];
		} 
		     
		$sync_url = M('system_config')->where(array('status'=>1))->getField('sync_url');  
		if($act == 'insert'){		
			$operate_num = $datas['operate_num'];
			$operate_info = M('operate_center')->where(array('id'=>$operate_id))->find();
			$package_id = $datas['package_id'];
			$age_limit = I('age_limit',1);
			$is_try = $datas['is_try'];
			if($is_try == '0'){
				$cost_price = A('Admin')->getPackageCostPrice($age_limit,$package_id,$operate_info['id']);
				if($cost_price > $operate_info['money']){
					$this->error('该运营商预存资金为￥'.$operate_info['money'].',购买套餐成本为￥'.$cost_price.',预存资金不足');
					exit;
				}
			}
			$vip = M('package_list')->where(array('packageid'=>$package_id,'status'=>1))->getField('up_level');
			$params = array(); 
			$params['shopName'] = $datas['shopName'];
			$params['store_parenttype_id'] = $datas['store_parenttype_id'];
			$params['store_childtype_id'] = $datas['store_childtype_id'];
			$params['package_id'] = $datas['package_id'];
			$params['vip'] = $vip;
			$params['xunxin_num'] =$datas['xunxin_num'];
			$params['password'] = $datas['password'];
			$params['store_provincename'] = $datas['store_provincename'];
			$params['store_cityname'] = $datas['store_cityname'];
			$params['store_areaname'] = $datas['store_areaname'];
			$params['account_storeaddress'] = $datas['account_storeaddress'];
			$params['account_zhucehao'] = $datas['account_zhucehao'];
			$params['is_try'] = $datas['is_try'];
			if($datas['is_try'] == 1){
				$params['try_time'] = $datas['try_time']*3600;
			}
			$params['age_limit'] = $datas['age_limit'];
			$params['account_membername'] = $datas['account_membername'];
			$params['id_card'] = $datas['id_card'];
			$params['account_membertel'] = $datas['account_membertel'];
			$params['storelicense'] = $url1;
			$params['id_card_img_n'] = $url2;
			$params['id_card_img_r'] = $url3;
			if(!empty($url4)){
				$params['other_img'] = $url4;
			}
			if(!empty($datas['remark'])){
				$params['remark'] = $datas['remark'];
			}
			$params['operate_num'] = $datas['operate_num'];
			$params['operate_id'] = $operate_info['id']; 
			if(!empty($datas['bank_provincenae'])){
				$params['bank_provincenae'] = $datas['bank_provincenae'];
			}
			if(!empty($datas['bank_cityname'])){
				$params['bank_cityname'] = $datas['bank_cityname'];
			}
			if(!empty($datas['bank_areaname'])){
				$params['bank_areaname'] = $datas['bank_areaname'];
			} 
			if(!empty($datas['bank_name'])){
				$params['bank_name'] = $datas['bank_name'];
			}
			if(!empty($datas['bank_num'])){
				$params['bank_num'] = $datas['bank_num'];
			}
			  
			$url = "http://".$sync_url."/xxapi/index.php?act=operate_openaccount&op=applyStore";
			$json =  $this->postCurl($url,$params);      			
			$rt = json_decode($json,true);
			if($rt['result'] == -1){
				$this->error($rt['error']);
			}else{
				$account_id = $rt['datas']; 
				$order_id =A('Shop')->create_package_order($account_id,$datas['xunxin_num'],$datas['is_try'],$package_id,$age_limit,$operate_num,$datas['account_membertel'],1,1);
				if(empty($order_id)){
					$this->error('创建订单失败');
				}else{ 
					$pay_check = A('Shop')->pay_package_order($order_id);
					if($pay_check['status'] == -1){
						$this->error($pay_check['desc']);
					}else{
						$params2 = array();
						$params2['account_id'] = $account_id;
						$params2['opentype'] = 2;
						$params2['platform_type'] = 2;    
						$url2 = "http://".$sync_url."/xxapi/index.php?act=operate_openaccount&op=openStore";
						$json2 =  $this->postCurl($url2,$params2);      			
						$rt2 = json_decode($json2,true);
						if($rt2['result'] == '0' || $rt2['result'] == '-10' || $rt2['result'] == '1001'){
							$order_sn = M('vip_orders')->where(array('id'=>$order_id))->getField('orderSn');
							M('data_record')->where(array('order_sn'=>$order_sn))->save(array('open_result'=>'SUCCESS'));	
							$newdata = array(); 
							$newdata['rechargetime'] = mktime();
							$newdata['is_create'] = 1;
							$newdata['store_id'] = $rt2['datas'];
							M('vip_orders')->where(array('id'=>$order_id))->save($newdata);
							A('Shop')->settlement_package_order($order_id);  
							$this->checkOperateUplevel($operate_id); 	
							$auth_code = M('system_config')->where(array('status'=>1))->getField('auth_code');   
							$sync_url = ADMIN_URL .'/index.php?m=api&c=Store&a=index&auth_code='.$auth_code;
							$x = 1;   
							do{    
								$json = file_get_contents($sync_url);
								$result = json_decode($json,true);	
							}while (++$x<=3 && $result['result'] != '0' && $result['result'] != '1' );  
							if($rt2['result'] == '0'){
								$this->success('新增店铺成功',U('Shop/shop_list'));	 	
							}else{
								$this->success('新增店铺成功,发送短信失败',U('Shop/shop_list'));		
							}   
						}else{   
							$this->error($rt2['error']);
						}
					}
				}
				
			}		
		}else{
			$store_id = I('store_id');
			$store_info = M('stores')->where(array('store_id'=>$store_id,'isdelete'=>0,'operate_id'=>$operate_id))->find();
			if(empty($store_info)){
				$this->error('该店铺不存在');
			}
			$params3 = array();
			$params3['account_id'] = $store_info['account_id'];   			
			$password = I('password');
			if(!empty($password)){
				$params3['password'] = $password;
			}
			$params3['store_provincename'] = I('store_provincename');
			$params3['store_cityname'] = I('store_cityname');
			$params3['store_areaname'] = I('store_areaname');
			$params3['account_storeaddress'] = I('account_storeaddress');
			$params3['account_membername'] = I('account_membername');
			$params3['id_card'] = I('id_card');
			$params3['account_membertel'] = I('account_membertel');
			$params3['remark'] = I('remark');
			$params3['bank_provincenae'] = I('bank_provincenae');
			$params3['bank_cityname'] = I('bank_cityname');
			$params3['bank_areaname'] = I('bank_areaname');
			$params3['bank_name'] = I('bank_name');
			$params3['bank_num'] = I('bank_num'); 
			$params3['account_zhucehao'] = I('account_zhucehao');
			if(!empty($url1)){
				$params3['storelicense'] = $url1;
			}else{
				$params3['storelicense'] = $store_info['store_license'];
			} 
			if(!empty($url2)){
				$params3['id_card_img_n'] = $url2;
			}else{
				$params3['id_card_img_n'] = $store_info['lianxi_id_img1'];
			} 
			if(!empty($url3)){
				$params3['id_card_img_r'] = $url3;
			}else{
				$params3['id_card_img_r'] = $store_info['lianxi_id_img2'];
			} 
			if(!empty($url4)){
				$params3['other_img'] = $url4;
			}else{
				$params3['other_img'] = $store_info['other_img'];
			}
			$url3 = "http://".$sync_url."/xxapi/index.php?act=operate_openaccount&op=updateApplyStore";
			$json3 =  $this->postCurl($url3,$params3);      			
			$rt3 = json_decode($json3,true);
			if($rt3['result'] == -1){
				$this->error($rt3['error']);
			}else{
				$auth_code = M('system_config')->where(array('status'=>1))->getField('auth_code');
				$sync_url = ADMIN_URL .'/index.php?m=api&c=Store&a=index&auth_code='.$auth_code;
				file_get_contents($sync_url);
				$this->success('编辑店铺成功',U('Shareholder/shop_list'));
			}    
			    
		}

	}

	/**
	 * 检验运营商能否升级
	 */
	public function checkOperateUplevel($operate_id=''){
		$config = M('operate_config')->where(array('status'=>1))->find();
		$operate = M('operate_center');
		$order = M('vip_orders');
		$w = array();
		$w['id'] = $operate_id;
		$w['status'] = 1;
		$operate_info = $operate->where($w)->find();
		if(!empty($operate_info)){
			if($operate_info['level'] < 6){
				$w2 = array();
				$w2['operate_id'] = $operate_id;
				$w2['status'] = 1;
				$w2['is_create'] = 1;
				$total_price = $order->where($w2)->sum('actual_price');
				$total_price = empty($total_price) ? 0 : $total_price;
				$up_level = 0;
				if(($total_price >= $config['first_upprice']) && ($total_price < $config['secend_upprice'])){
					$up_level = 2;
				}else if(($total_price >= $config['secend_upprice']) && ($total_price < $config['third_upprice'])){
					$up_level = 3;
				}else if(($total_price >= $config['third_upprice']) && ($total_price < $config['fourth_upprice'])){
					$up_level = 4;
				}else if(($total_price >= $config['fourth_upprice']) && ($total_price < $config['fifth_discount'])){
					$up_level = 5;
				}else if($total_price >= $config['fifth_discount']){
					$up_level = 6;
				}
				if($up_level > $operate_info['level']){
					$operate->where($w)->save(array('level'=>$up_level));
					M('shareholder_package_edit')->where(array('operate_id'=>$operate_id))->save(array('status'=>0));
				}
				
			}
		}
	}	
	
 
	public function upload_img()
    {
		
		$info = $this->upload();
		
        $file = $info['file'];  
		$type = $_GET['type'];   
		$rt = array();	
		if (!empty($file['name']))
		{
			$url = ADMIN_URL . '/images/'.$file['savepath'].$file['savename'];
			$rt['status']  =1 ;
			$rt['url'] = $url;	
			echo json_encode($rt, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}    
		else{  
			echo json_encode($rt, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}	 
    }
	
	private function upload($files = '')
    {
        $upload = new \Think\Upload(); // 实例化上传类
        $upload->maxSize = 0; // 设置附件上传大小
        $upload->exts = array(
            'jpg',
            'gif',    
            'png',    
            'jpeg'
        ); // 设置附件上传类型 
        $upload->rootPath = './images/'; // 设置附件上传根目录
		//die($upload->roorPath);
        $upload->savePath = ''; // 设置附件上传（子）目录
        $upload->replace = true; // 上传文件 
        $info = $upload->upload($files);
	
        if (! $info) { // 上传错误提示错误信息 
            $this->error($upload->getError());
        } else { // 上传成功
                 // $this->success('上传成功！');
            return $info;
        }
    }
	
	public function ajax_renews(){ 
		$store_id = I('store_id');
		$age_limit = I('agelimit');
		$loginname = session('loginname');
		$pass = I('login_pass');
		$mp = M('admin'); 
		$rt = array();
		$wp = array();
		$wp['loginname'] = $loginname;
		$wp['password'] = md5($pass);
		$wp['status'] = 1;
		$check = $mp->where($wp)->find();
		if(empty($check)){ 
			$rt['status'] = -1;
			$rt['error'] = '登录密码错误';
			die(json_encode($rt,JSON_UNESCAPED_UNICODE));
		}
		$m = M('stores');
		$w = array();
		$w['store_id'] = $store_id;
		$w['isdelete'] = 0;
		$info = $m->where($w)->find();
		if(empty($info['package_id'])){
			$rt['status'] = -1;
			$rt['error'] = '该店铺没有购买套餐';
		}else{
			$cost_price = A('Admin')->getPackageCostPrice($age_limit,$info['package_id'],$info['operate_id'],$store_id);
			if(!empty($info['operate_id'])){
				$operate_info = M('operate_center')->where(array('id'=>$info['operate_id']))->find();
				if($cost_price > $operate_info['money']){
					$rt['status'] = -1;
					$rt['error'] = '该运营商预存资金为￥'.$operate_info['money'].',购买套餐成本为￥'.$cost_price.',预存资金不足';
					die(json_encode($rt,JSON_UNESCAPED_UNICODE));
				}
			}
		
			  
			$w2 = array();
			$w2['packageid'] = $info['package_id'];
			$w2['status'] = 1;
			$packageinfo = M('package_list')->where($w2)->find();
			if(empty($packageinfo)){
				$rt['status'] = -1; 
				$rt['error'] = '该套餐不存在';
				die(json_encode($rt,JSON_UNESCAPED_UNICODE));
			}     
			if($age_limit == 1){
				$market_price = $packageinfo['market_price'];
			}else if($age_limit == 2){
				$market_price = $packageinfo['market_price2'];
			}else if($age_limit == 3){
				$market_price = $packageinfo['market_price3'];
			}
			if($info['is_try'] == 1){
				$news = M('shareholder_package_edit')->where(array('operate_id'=>$info['operate_id'],'package_id'=>$info['package_id'],'status'=>1))->find();
				if(!empty($news)){
					if($age_limit == 1){
						$market_price = $news['package_price'];
					}else if($age_limit == 2){
						$market_price = $news['package_price2'];
					}else if($age_limit == 3){
						$market_price = $news['package_price3'];
					}
				}             
			} 
			$sync_url = M('system_config')->where(array('status'=>1))->getField('sync_url');    
			$url = "http://".$sync_url."/xxapi/index.php?act=operate&op=pcReNews"; 
			$out_trade_no = 'pcupgrade_'.date('ymdHis').'-'.$info['member_id'];
			$param = array();
			$param['store_id'] = $store_id;      
			$param['channel_id'] = 0;
			$param['store_id'] = $store_id;
			$param['out_trade_no'] = $out_trade_no;
			$param['age_limit'] = $age_limit;
			$param['money'] = $market_price; 
			$json = $this->postCurl($url,$param); 
			$datas = json_decode($json,true);
			if($datas['status'] == 1){
				if(!empty($info['operate_id'])){
					$da = array();
					$da['value'] = 0 -$cost_price;
					$da['final_value'] = $operate_info['money'] - $cost_price;
					$da['operate_id'] = $info['operate_id'];
					$da['type'] = 2;
					$da['order_sn'] = $out_trade_no;
					$da['periods'] = date('Y-m-d');
					$da['addtime'] = mktime();
					$da['editor'] =  session('loginname');
					$da['status'] = 1;
					if(M('operate_trade_record')->add($da)){
						$check = M('operate_center')->where(array('id'=>$info['operate_id']))->setDec('money',$cost_price);
						if($check !== false){ 
							$rt['status'] = 1;
						}else{
							$rt['status'] = -1;
							$rt['error'] = '更改运营商预存金额失败';
						}
					}else{
						$rt['status'] = -1;
						$rt['error'] = '插入运营商交易记录失败';
					}
				}else{
					$rt['status'] = 1;
				}
			}
			   
		} 
		
		echo json_encode($rt,JSON_UNESCAPED_UNICODE);      
	}
    
   
}
?>

