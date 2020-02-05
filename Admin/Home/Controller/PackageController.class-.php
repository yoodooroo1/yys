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
 * $Id: OrderController.class.php
 */
class PackageController extends AdminController
{
    
	public function __construct()
    {    
        parent::__construct();
        if(!$this->checkAuth()){
			$this->error('你没有该权限',U('Index/index'));
		} 
    } 
    /**
     * 套餐列表
     */
    public function lists()
    { 
        $package = M("package_list");
		$w = array();
		//$w['lm_package_list.is_show'] = 1;
		$count = $package->where($w)->count(); // 查询满足要求的总记录数
        $Page = new \Think\Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show(); // 分页显示输出 
		$list = $package->join('left join lm_member_level on lm_member_level.level_id=lm_package_list.up_level')->where($w)->limit($Page->firstRow . ',' . $Page->listRows)->field('lm_package_list.*,lm_member_level.level_name')->order('lm_package_list.sort')->select();
		$ui['package_list'] = 'active'; 
		$rate = M('settlement_config')->where(array('status'=>1))->field('public_pv_percent,operate_pv_percent')->find();
		$this->assign('rate',$rate);	
        $this->assign('ui', $ui);
        $this->assign('lists', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display("Package:list"); // 输出模板
    }

		/*
	 * 改变套餐显示状态
	 */
	public function changPackageShow()
	{
		$packageid =I('packageid');
		$state =I('state');
		$newstate = ($state == '0') ? 1 : 0;
		$w = array();
		$w['packageid'] = $packageid;		
		$m = M('package_list');
		$data = array();
			$data['is_show'] = $newstate;
		if($m->where($w)->save($data))
		{  
			echo $newstate;
		}
		else{
			echo 3;
		}	
	}
	
	
	
    /**
     * 套餐详情
     */
    public function info()
    {
		$packageid = I('pid');
		if(!empty($packageid)){
			$package = M("package_list");
			$w = array();
			$w['lm_package_list.is_show'] = 1;
			$w['lm_package_list.packageid'] =$packageid;
			$info= $package->join('left join lm_member_level on lm_member_level.level_id=lm_package_list.up_level')->where($w)->field('lm_package_list.*,lm_member_level.level_name')->find();
			$this->assign('info', $info); // 赋值数据集
		}
		
		$rate = M('settlement_config')->where(array('status'=>1))->field('public_pv_percent,operate_pv_percent')->find();
		$this->assign('rate',$rate);  
		$level = M('member_level');  
		$wl =array(); 
		$wl['status'] =1;   
		$wl['level_id'] = array('gt',0);
		$level_list = $level->where($wl)->select();
		$this->assign('level_list',$level_list);   
        $ui['package_info'] = 'active';           
        $this->assign('ui', $ui);
        $this->display("Package:info"); // 输出模板
    }
	
	/**
     * 更改或添加套餐
     */
    public function updates()
    {
		$package = M("package_list");
		$packageid = I('pid');
		$datas = I();
		$files = $_FILES;
	
		if($files['main_img']['error'] == '0' &&!empty($files['main_img']['name'])){
			$info = $this->upload();
			$imgurl = ADMIN_URL.'/Uploads/'.$info['main_img']['savepath'] . $info['main_img']['savename'];
			$datas['main_img'] = $imgurl;  
		}  
		if(!empty($packageid)){
			unset($datas['packageid']); 
			$w = array();
			$w['lm_package_list.is_show'] = 1;
			$w['lm_package_list.packageid'] =$packageid;
			$rt = $package->where($w)->save($datas);
			if($rt || ($rt =='0')){
				$this->addAdminLog('5',"修改套餐详情,套餐ID：".$packageid);
				$this->success('修改套餐详情成功',U('Package/lists'));
			} 
			      
		}else{
			$datas['addtime'] = mktime();
			if($pid = $package->add($datas)){
				$this->addAdminLog('5',"新增套餐,套餐ID：".$pid);
				$this->success('添加套餐成功',U('Package/lists'));
			}
		}
		  
    } 

	public function  del_package(){
		$id = I('pid');
		if(!empty($id)){
		
			 $rt = M('package_list')->where('packageid = '.$id)->delete();
			   
			if($rt){ 
				$this->addAdminLog('5',"删除套餐,套餐ID：".$id);
				$this->success('删除套餐成功',U('Package/lists'));
			}else{
				
				$this->error('删除套餐失败');
			}   
		}else{  
			$this->error('删除套餐失败');
		}
	}	
	 /**
     * 订单列表
     */
    public function order()
    {  
		$m = M('vip_orders');
		$member = M('members');
		$where = array();
		$where['lm_vip_orders.status'] = array('neq','-1');
		$Time1 = I('Time1');
		$Time2 = I('Time2');
		$nickname = I('nickname');
		$member_name = I('member_name');
		$ordersn = I('ordersn');
		$parent = I('parent');  
		$username = I('username');  //收货人 
		$order_status = I('order_status');  //收货人    
		if(!empty($Time1) && empty($Time2))  
		{
			$t = strtotime($Time1)+24*60*60 ;
			$where['_string']= "lm_vip_orders.applytime >= '".strtotime($Time1) ."'&&lm_vip_orders.applytime < '".$t."'";
		}
		if(!empty($Time2) && empty($Time1))
		{  
			$where['_string']= "lm_vip_orders.applytime<= '". strtotime($Time2)."'";  
		}  
		if(!empty($Time2) && !empty($Time1))
		{   
			$t = strtotime($Time2)+24*60*60 ;
			$where['_string']= "lm_vip_orders.applytime >= '".strtotime($Time1) ."'&&lm_vip_orders.applytime < '" .$t."'";     
		} 
		if(!empty($member_name)){    
			$where['lm_members.member_name'] = array('like','%'.$member_name.'%');
		}	
		if(!empty($nickname)){    
			$where['lm_members.member_nickname'] = array('like','%'.$nickname.'%');
		}
		if(!empty($ordersn)){
			$where['lm_vip_orders.ordersn'] = array('like','%'.$ordersn.'%');
		}
		if(!empty($username)){
			$where['lm_vip_orders.username'] = array('like','%'.$username.'%');
		}
		if(!empty($parent)){
			$where['lm_vip_orders.recommend_name'] = array('like','%'.$parent.'%');
		}
		if($order_status != ''){
			$where['lm_vip_orders.status'] = $order_status;
		} 
		$count = $m->where($where)->join('lm_members ON lm_members.member_id=lm_vip_orders.member_id')->count();
		$countprice = $m->where($where)->join('lm_members ON lm_members.member_id=lm_vip_orders.member_id')->sum('lm_vip_orders.price');
        $Page = new \Think\Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show(); // 分页显示输出 
		$lists = $m->where($where)->join('LEFT JOIN lm_members ON lm_members.member_id=lm_vip_orders.member_id')->field('lm_vip_orders.*,lm_members.member_nickname')->limit($Page->firstRow . ',' . $Page->listRows)->order('applytime desc')->select();
		$member_level = M('member_level'); 
		$package_list = M('package_list');
		foreach($lists as $key=>$list){
			$level_name = $member_level->where(array('level_id'=>$list['up_level']))->getField('level_name');
			$lists[$key]['level_name'] = $level_name;
			$name = $package_list->where(array('packageid'=>$list['packageid']))->getField('name'); 
			$lists[$key]['name'] = $name;
			if(!empty($list['recommend_name'])){
				$w = array();
				$w['lm_members.member_name'] = $list['recommend_name'];
				$parent_level = $member->where($w)->join('LEFT JOIN lm_member_level ON lm_members.level=lm_member_level.level_id')->getField('lm_member_level.level_name');
				$lists[$key]['parent_level'] = $parent_level;
			}  
			else{
				$lists[$key]['recommend_name'] = '无';
				$lists[$key]['parent_level'] = '无';
			}
			
		}   

        $ui['package_order'] = 'active';           
        $this->assign('ui', $ui);
        $this->assign('lists', $lists); // 赋值数据集
        $this->assign('count', $count); // 赋值数据集
        $this->assign('countprice', $countprice); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display("Package:order"); // 输出模板
    }
	/**
     * 管理员添加订单
     */
    public function order_add()
    {
		$list = M('package_list');
		$wc = array();
		//$wc['is_show'] = 1;
		$package_list = $list->where($wc)->field('packageid,name')->order('sort DESC')->select();
		$this->assign('package_list',$package_list);
        $ui['package_order_add'] = 'active';           
        $this->assign('ui', $ui);
        $this->display("Package:order_add"); // 输出模板
    }
		
	 /**
     * 管理员保存添加订单
     */  
    public function order_save()
    {
		$member_name = I('member_name');
		$package_id= I('package_id');
		$id_card = I('id_card');
		$tel = I('tel');
		$username = I('username');
		$member = M('members');
		$order = M('vip_orders');
		$recommend_name = I('recommend_name');
		$w1 = array();
		$w1['member_name'] = $member_name;
		$w1['shield'] = 0;
		$member_info = $member->where($w1)->field('member_id,recommend_name')->find();
		$member_id = $member_info['member_id'];
		if(empty($member_id)){
			$this->error('会员不存在');
			die;
		}
		$where = array();
		$where['member_name'] = $recommend_name;
		$rt = $member->where($where)->find();
		if(empty($rt) && !empty($recommend_name)){
			$this->error('该推荐人不存在');
			die;      
		}
  		if($member_name ==$recommend_name){
			$this->error('推荐人不能是你自己');
			die;   
		}
		$childrens = $this->getchildren($member_name);
		if(in_array($recommend_name,$childrens)){
			$this->error('该推荐人是你底下的成员，不能成为你的推荐人');
			die; 
		}   
		
		
		$package_info = M('package_list')->where(array('packageid'=>$package_id/* ,'is_show'=>1 */))->find();
		if(empty($package_info)){
			$this->error('该套餐不存在');
			die;
		}
		$w2 = array();
		$w2['member_id'] = $member_id;
		$rt = $order->where($w2)->find();
		if($rt['status'] == 1){
			$this->error('该会员已购买过套餐');
			die;
		}else{
			$data = array();
			$data['member_id'] = $member_id;
			$data['orderSn'] = mktime().'_'.$member_id;
			$data['packageid'] = $package_id;
			$data['price'] = $package_info['price'];
			$data['id_card'] = $id_card;
			$data['tel'] = $tel;
			$data['username'] = $username;
			$data['recommend_name'] = $recommend_name;
			$data['up_level'] = $package_info['up_level'];
			$data['paytype'] = 1;
			$data['status'] = 0;
			$data['applytime'] =mktime();
			$data['type'] =1;
			if(empty($rt)){
				if($order->add($data)){
					$this->success('添加订单成功',U('Package/order'));
				}else{
					$this->error('添加订单失败');
				}
			}else{
				$jg = $order->where(array('id'=>$rt['id']))->save($data);
				if($jg !== false){
					$this->success('更新订单成功',U('Package/order'));
				}else{
					$this->error('更新订单失败');
				}
			}
			
		}  
	
	}
	
	public function getchildren($member_name){
		$m = M('members');
		$w = array();  
		$w['recommend_name'] = $member_name;
		$rt = $m->where($w)->field('member_name')->select();
	
		$result = array();
		while(!empty($rt)){   
			$news = array();
			foreach($rt as $r){
				$result[] = $r['member_name'];
				$news[] = $r['member_name'];
			}
			
			$str = implode(',',$news);
			$where = array(); 
			$where['recommend_name'] = array('in',$str);
			$rt = $m->where($where)->field('member_name')->select();
		}  
		return $result;    
	}
	
	 /**
     * 订单详情
     */
    public function order_info()
    {
		$orderid = I('orderid');
		if(empty($orderid)){
			$this->error('该订单不存在',U('Index/index'));
		}
		else{ 
			$m = M('vip_orders');
			$info = $m->where(array('lm_vip_orders.id'=>$orderid))->join('LEFT JOIN lm_member_level ON lm_member_level.level_id=lm_vip_orders.up_level')->join('LEFT JOIN lm_package_list ON lm_package_list.packageid=lm_vip_orders.packageid')->join('lm_members ON lm_members.member_id=lm_vip_orders.member_id')->field('lm_vip_orders.*,lm_member_level.level_name,lm_package_list.name,lm_package_list.main_img,lm_members.member_nickname')->find();
			
		}   
	   
        $ui['package_order_info'] = 'active';           
        $this->assign('ui', $ui);
        $this->assign('order', $info); // 赋值数据集
        $this->display("Package:order_info"); // 输出模板
    } 
	
	 /**
     * 订单分润列表
     */
	public function order_share(){
		$order = M('vip_orders');
		$w = array();
		$record = M('member_wallet_record');
		$member_name  = I('member_name');
		$this->assign('member_name',$member_name);
		$ordersn = I('ordersn');
		$this->assign('ordersn',$ordersn);
		$Time1 = I('Time1');
		$this->assign('Time1',$Time1);
		$Time2 = I('Time2');
		$this->assign('Time2',$Time2);
		$paytype = I('paytype');
		$status = I('status');
		$w['lm_vip_orders.status'] = array('neq',-1);
		if(!empty($ordersn)){
			$w['lm_vip_orders.ordersn'] = array('like',"%".$ordersn."%");
		}
		if(!empty($paytype)){
			$w['lm_vip_orders.paytype'] = $paytype;
		}
		if($status != ''){   
			$w['lm_vip_orders.status'] = $status;
		}
		if(!empty($Time1) && empty($Time2))  
		{
			$t = strtotime($Time1)+24*60*60 ;
			$w['_string']= "lm_vip_orders.applytime >= '".strtotime($Time1) ."'&&lm_vip_orders.applytime < '".$t."'";
		}
		if(!empty($Time2) && empty($Time1))
		{     
			$w['_string']= "lm_vip_orders.applytime<= '". strtotime($Time2)."'";  
		}  
		if(!empty($Time2) && !empty($Time1))
		{   
			$t = strtotime($Time2)+24*60*60 ;
			$w['_string']= "lm_vip_orders.applytime >= '".strtotime($Time1) ."'&&lm_vip_orders.applytime < '" .$t."'";     
		}
		if(!empty($member_name)){
			$w['lm_members.member_name'] = array('like',"%".$memebr_name."%");
		}
		/* $count = $m->where($where)->join('lm_members ON lm_members.member_id=lm_vip_orders.member_id')->count();
		$countprice = $m->where($where)->join('lm_members ON lm_members.member_id=lm_vip_orders.member_id')->sum('lm_vip_orders.price');
        $Page = new \Think\Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show(); // 分页显示输出  
		$lists = $m->where($where)->join('LEFT JOIN lm_members ON lm_members.member_id=lm_vip_orders.member_id')->field('lm_vip_orders.*,lm_members.member_nickname')->limit($Page->firstRow . ',' . $Page->listRows)->select(); */
		$count = $order->join('LEFT JOIN lm_members ON lm_members.member_id =lm_vip_orders.member_id')->where($w)->count();  
		$Page = new \Think\Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数(25)
		$show = $Page->show(); // 分页显示输出  
		$lists = $order->join('LEFT JOIN lm_members ON lm_members.member_id =lm_vip_orders.member_id')->where($w)->field('lm_vip_orders.*')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		/* $lists = $order->join('LEFT JOIN lm_package_list ON lm_package_list.packageid = lm_vip_orders.packageid')->join('LEFT JOIN lm_operate_price_record ON lm_operate_price_record.link_orderid = lm_vip_orders.ordersn')->join('LEFT JOIN lm_public_group_price_record ON lm_public_group_price_record.link_orderid = lm_vip_orders.ordersn')->where($w)->field('lm_vip_orders.*,lm_package_list.pv,lm_operate_price_record.value operate_value,lm_operate_price_record.operate_name,lm_operate_price_record.operate_id,lm_public_group_price_record.value public_value')->select(); */
		$package_list =M('package_list');
		$operate_price_record = M('operate_price_record');
		$public_group_price_record = M('public_group_price_record');
		foreach($lists as $k=>$list){ 
			$w1 = array();
			$w1['packageid'] = $list['packageid'];
			$pv = $package_list->where($w1)->getField('pv');
			$lists[$k]['pv'] = $pv;
			$w2 = array();
			$w2['link_orderid'] = $list['ordersn'];
			$operate_info = $operate_price_record->where($w2)->field('value,operate_name,operate_id')->find();
			$lists[$k]['operate_value'] = $operate_info['value'];
			$lists[$k]['operate_name'] = $operate_info['operate_name'];
			$lists[$k]['operate_id'] = $operate_info['operate_id'];
			$value = $public_group_price_record->where($w2)->getField('value');
			$lists[$k]['public_value'] = $value;
			$where = array();
			$where['lm_member_wallet_record.source_order'] = $list['ordersn'];
			$where['lm_member_wallet_record.description'] = array('like',"%直接%");
			$info1 = $record->where($where)->join('LEFT JOIN lm_members ON lm_members.member_id=lm_member_wallet_record.member_id')->join('LEFT JOIN lm_member_level on lm_member_level.level_id=lm_members.level')->group('source_order')->field('sum(prices) total,lm_member_wallet_record.member_id,lm_members.member_name,lm_member_level.level_name')->find();
			$where2 = array();   
			$where2['lm_member_wallet_record.source_order'] = $list['ordersn'];
			$where2['lm_member_wallet_record.description'] = array('like',"%间接%");
			$info2 = $record->where($where2)->join('LEFT JOIN lm_members ON lm_members.member_id=lm_member_wallet_record.member_id')->join('LEFT JOIN lm_member_level on lm_member_level.level_id=lm_members.level')->group('source_order')->field('sum(prices) total,lm_member_wallet_record.member_id,lm_members.member_name,lm_member_level.level_name')->find();
			$lists[$k]['share1_value'] = $info1['total'];
			$lists[$k]['share1_member_name'] = $info1['member_name'];
			$lists[$k]['share1_level_name'] = $info1['level_name'];
			$lists[$k]['share2_value'] = $info2['total'];
			$lists[$k]['share2_member_name'] = $info2['member_name'];
			$lists[$k]['share2_level_name'] = $info2['level_name'];
			
			
		}
		$info = array();
		$info['all_prices'] = 0;
		$info['all_pv'] = 0; 
		$info['all_share1_value'] = 0;
		$info['all_share2_value'] = 0;
		$info['all_operate_value'] = 0;   
		$info['all_public_value'] = 0;
		foreach($lists as $list){
			$info['all_prices'] =  $info['all_prices'] + $list['price'];
			$info['all_pv'] =  $info['all_pv'] + $list['pv'];
			$info['all_share1_value'] =  $info['all_share1_value'] + $list['share1_value'];
			$info['all_share2_value'] =  $info['all_share2_value'] + $list['share2_value'];
			$info['all_operate_value'] =  $info['all_operate_value'] + $list['operate_value'];
			$info['all_public_value'] =  $info['all_public_value'] + $list['public_value'];
		} 
		$this->assign('count', $count); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出	
		$this->assign('info',$info);
		$ui['order_share'] = 'active';
		$this->assign('ui',$ui);
		$this->assign('lists',$lists);   
		$this->display('order_share');
		//var_dump($lists);  
		
	} 

	public function checkpay(){
		$result = array();
		$str= I('str');
		$orderid = I('orderid');
		$admin = M('admin');
		$w = array();
		$w['loginname'] = session('loginname');
		$password = $admin->where($w)->getField('password');
		if(md5($str) != $password){
			$result['result'] = '-1';
			$result['desc'] = '登录密码错误'; 
			die(json_encode($result,JSON_UNESCAPED_UNICODE));
		}
		// hjun 2017年3月9日 18:24:56
        if(!isset($_FILES["myfile"])){
			// 提示未上传支付凭证
            $result = array();
            $result['result'] = -1;
            $result['desc'] = '未上传支付凭证';
            echo json_encode($result,JSON_UNESCAPED_UNICODE);
            die;
        }
		$order = M('vip_orders');
		$member_id = $order->where(array('id'=>$orderid))->getField('member_id');
		$this->confirm($member_id);
	}  

	/*确定注册成为会员*/
	protected function confirm($member_id=''){
		//$member_id = I('member_id');   
		$m = M('vip_orders'); 
		$where = array();
		$where['member_id']= $member_id;
		$where['status'] = array('neq',-1);
		$info =$m->where($where)->find();       
		if(empty($member_id) || empty($info)){
			$result = array();
			$result['result'] = -1;   
			$result['desc'] = '该用户未申请注册会员';
			echo json_encode($result,JSON_UNESCAPED_UNICODE);
			die;
		}
		if($info['status'] == '1'){
			$result = array();  
			$result['result'] = -1;
			$result['desc'] = '该用户已确认支付了';
			echo json_encode($result,JSON_UNESCAPED_UNICODE);
			die;
		} 
		/* if($info['type'] == 1){
			die('非法操作');
		} */  
		$packageinfo= M('package_list')->where(array('packageid'=>$info['packageid']))->find();
		$data = array(); 
		$data['status'] = 1; 
		$data['passtime'] = mktime();
		if($m->where($where)->save($data)){
			$this->addAdminLog('6',"更改套餐订单为已支付，订单号：".$info['ordersn']);	
			/*更新会员等级和推荐人*/   
			$member = M('members');
			$memberinfo = $member->where(array('member_id'=>$member_id))->find();
			/*更新推荐人，保存购买套餐id*/
			$recommend_id = $member->where(array('member_name'=>$info['recommend_name']))->getField('member_id');
			$rdata = array();
			$rdata['recommend_id'] = $recommend_id;
			$rdata['recommend_name'] = $info['recommend_name'];
			$rdata['package_id'] = $info['packageid'];   
			if($memberinfo['level']<$info['up_level']){
				$rdata['level'] = $info['up_level'];
			}
			$member->where(array("member_id"=>$member_id))->save($rdata);
			
			/*计算直推奖励*/
			/* if($info['up_level'] == 2){ */ 
				$sql ="SELECT tb2.member_name,tb2.member_id FROM lm_members AS tb1,lm_members AS tb2 WHERE tb1.recommend_name = tb2.member_name AND tb1.member_id=".$member_id;
				$p = M()->query($sql);
				$this->get_direct_push_money($p[0]);
			/* }  */   
			  
			$parents = $this->get_normal_parents($member_id);  
			$periods = date('Y-m-d',mktime());
			$level = M('member_level'); 
			$wallet_record = M('member_wallet_record');   
			$wallet = M('member_wallet');
			$lw = array();
			$lw['level_id'] = $info['up_level'];
			$lw['status'] = 1;
			/* $profit_share = $level->where($lw)->field('level_name,profit_share1,profit_share2,profit_share3')->find(); */
			$settlement_config = M('settlement_config');
			$sconfig = $settlement_config->where(array('status'=>1))->find();
			/*直接推荐人获得利润*/
			if(!empty($parents['p1'])){
				$money1 = $packageinfo['pv']* $packageinfo['percent1']/100;
				if($money1>0){
					$params1 = array();
					$params1['member_id'] = $parents['p1'];
					$params1['type'] = '2';      //消费钱包
					$params1['prices'] = $money1*$sconfig['award_shop_percent']/100;
					$params1['periods'] = $periods;
					$params1['source_order'] = $info['ordersn'];
					$params1['description'] = '发展'.$packageinfo['name'].'会员奖励（直接）';
					$params1['addtime'] = mktime();
					$wallet_record->add($params1);  
					$wallet->where(array("member_id"=>$parents['p1']))->setInc('consume_money',$money1*$sconfig['award_shop_percent']/100);
					  
					$params2 = array();
					$params2['member_id'] = $parents['p1'];
					$params2['type'] = '3';      //可提现钱包（现金钱包）
					$params2['prices'] = $money1*$sconfig['award_cash_percent']/100;
					$params2['source_order'] = $info['ordersn'];
					$params2['periods'] = $periods;
					$params2['description'] = '发展'.$packageinfo['name'].'会员奖励（直接）';
					$params2['addtime'] = mktime();
					$wallet_record->add($params2);   
					$wallet->where(array("member_id"=>$parents['p1']))->setInc('cash_money',$money1*$sconfig['award_cash_percent']/100);
				}   
			}
			/*间接推荐人获得利润*/
			if(!empty($parents['p2'])){
				$money2 =  $packageinfo['pv']* $packageinfo['percent2']/100;
				if($money2>0){
					$params3 = array();
					$params3['member_id'] = $parents['p2'];
					$params3['type'] = '2';      //消费钱包
					$params3['prices'] = $money2*$sconfig['award_shop_percent']/100;
					$params3['periods'] = $periods;
					$params3['source_order'] = $info['ordersn'];
					$params3['description'] ='发展'.$packageinfo['name'].'会员奖励（间接）';
					$params3['addtime'] = mktime();
					$wallet_record->add($params3);  
					$wallet->where(array("member_id"=>$parents['p2']))->setInc('consume_money',$money2*$sconfig['award_shop_percent']/100);
					$params4 = array();
					$params4['member_id'] = $parents['p2'];
					$params4['type'] = '3';      //可提现钱包（现金钱包）
					$params4['prices'] = $money2*$sconfig['award_cash_percent']/100;
					$params4['periods'] = $periods;
					$params4['source_order'] = $info['ordersn'];
					$params4['description'] = '发展'.$packageinfo['name'].'会员奖励（间接）';
					$params4['addtime'] = mktime();
					$wallet_record->add($params4);   
					$wallet->where(array("member_id"=>$parents['p2']))->setInc('cash_money',$money2*$sconfig['award_cash_percent']/100);
				}   
			}
			/*公益基金获得分红*/
			$money3 = $packageinfo['pv']* $sconfig['public_pv_percent']/100;
			if($money3 > 0){
				$params5 = array();
				$params5['type'] =1;
				$params5['style'] = '2';      
				$params5['link_orderid'] = $info['ordersn'];
				$params5['pay_name'] = ($info['paytype'] == 1) ? '线下支付' : (($info['paytype'] == 2) ? '微信支付' : '余额支付');  
				$params5['value'] = $money3;
				$params5['desc'] = '套餐分成【订单号：'.$info['ordersn'].'】';
				$params5['periods'] = $periods;
				$params5['addtime'] = mktime();  
				M('public_group_price_record')->add($params5);   
			}
			
			/*运营商分红*/
			$money4 = $packageinfo['pv']* $sconfig['operate_pv_percent']/100;
			if($money4 > 0){
				$operate_id = $memberinfo['operate_id'];
				$operate_name = M('operate_center')->where(array('id'=>$operate_id))->getField('operate_name');
				$data3 = array();
				$data3['operate_id'] = $operate_id ;
				$data3['link_orderid'] = $info['ordersn'];
				$data3['type'] = 2;
				$data3['pay_name'] = ($info['paytype'] == 1) ? '线下支付' : (($info['paytype'] == 2) ? '微信支付' : '余额支付'); 
				$data3['operate_name'] = $operate_name;
				$data3['value'] = $money4;
				$data3['desc'] = '套餐分成【订单号：'.$info['ordersn'].'】';
				$data3['periods'] = $periods;
				$data3['addtime'] = mktime();
				M('operate_price_record')->add($data3);
				$month =date('Y-m',mktime()); 
				$operate_total_price = M('operate_total_price');			
				$check1 = $operate_total_price->where(array('operate_id'=>$operate_id,'month'=>$month))->find();
				if(!empty($check1)){
					$operate_total_price->where(array('operate_id'=>$operate_id,'month'=>$month))->setInc('value', $money4);  
				}
				else{
					$totalda = array();
					$totalda['operate_id'] = $operate_id;
					$totalda['operate_name'] = $operate_name;
					$totalda['value'] = $money4;
					$totalda['month'] = $month;
					$totalda['month_time'] = strtotime($month);
					$operate_total_price->add($totalda);
				}
				/*运营商里股东分红*/	
				$shareholds = M('operate_shareholder')->where(array('operate_id'=>$operate_id,'status'=>1))->select();
			
				$operate_shareholder_price_record = M('operate_shareholder_price_record');
				$operate_shareholder_total_price = M('operate_shareholder_total_price');
				foreach($shareholds as $sharehold){
					$sharemoney = $money4*$sharehold['share_rate']/100;
					
					$sharedate = array();
					$sharedate['operate_id'] = $operate_id;
					$sharedate['operate_name'] = $operate_name;
					$sharedate['shareholder_id'] = $sharehold['id'];
					$sharedate['shareholder_name'] = $sharehold['shareholder_name'];
					$sharedate['type'] = '2';
					$sharedate['link_orderid'] = $info['ordersn'];
					$sharedate['pay_name'] = ($info['paytype'] == 1) ? '线下支付' : (($info['paytype'] == 2) ? '微信支付' : '余额支付');
					$sharedate['value'] = $sharemoney;
					$sharedate['desc'] = '套餐分成【订单号：'.$info['ordersn'].'】';
					$sharedate['periods'] = $periods;  
					$sharedate['addtime'] = mktime();
					         
					$operate_shareholder_price_record->add($sharedate);
					$check2 = $operate_shareholder_total_price->where(array('shareholder_id'=>$sharehold['id'],'month'=>$month))->find();
					if(!empty($check2)){
						$operate_shareholder_total_price->where(array('shareholder_id'=>$sharehold['id'],'month'=>$month))->setInc('value', $sharemoney);  
					}    
					else{   
						$totalda2 = array();
						$totalda2['operate_id'] = $operate_id;
						$totalda2['operate_name'] = $operate_name;
						$totalda2['shareholder_id'] = $sharehold['id'];
						$totalda2['shareholder_name'] = $sharehold['shareholder_name'];
						$totalda2['value'] = $sharemoney;   
						$totalda2['month'] = $month;
						$totalda2['month_time'] = strtotime($month);
						$operate_shareholder_total_price->add($totalda2);
					}
				}   
			}
			//$this->Up_parent_level($member_id);  
		}
		$result = array();
		$result['result'] = '1';  
		$result['desc'] = '确认支付成功';

		// 设置支付凭证图片 hjun 2017年3月9日 18:25:24
        $order_id = I('orderid');
        if(isset($_FILES["myfile"])){
        	$file_img = array();
        	$file_img['fileImg'] = $_FILES["myfile"];
            $h_info = $this->upload($file_img);
            $imgurl = ADMIN_URL.'/Uploads/'.$h_info['fileImg']['savepath'] . $h_info['fileImg']['savename'];
            $data = array();
            $data['pay_order'] = $imgurl;
            M('vip_orders')->where(array('id'=>$order_id))->save($data);
        }

		die(json_encode($result,JSON_UNESCAPED_UNICODE));
		
	}
     
	/*计算直推奖励*/
	protected  function get_direct_push_money($member){
		if(!empty($member)){ 
			$m = M('members');
			$wallet_record = M('member_wallet_record');   
			$wallet = M('member_wallet');
			$settlement_config = M('settlement_config');
			$sconfig = $settlement_config->where(array('status'=>1))->find();
			$w = array();
			$w['recommend_name'] = $member['member_name'];
			$w['shield'] = 0;  
			$w['package_id'] = array('gt',0);
			$count = $m->where($w)->count();
			$money1 = $sconfig['direct_push_award'];
			$money2 = $sconfig['more_push_award'];    
			$periods = date('Y-m-d',mktime());
			if($count == 3){ 
				$params1 = array(); 
				$params1['member_id'] = $member['member_id'];
				$params1['type'] = '2';      //消费钱包
				$params1['prices'] = $money1*$sconfig['award_shop_percent']/100;
				$params1['periods'] = $periods;
				$params1['description'] = '直推3个套餐奖励';
				$params1['addtime'] = mktime();
				$wallet_record->add($params1);  
				$wallet->where(array("member_id"=>$member['member_id']))->setInc('consume_money',$money1*$sconfig['award_shop_percent']/100);
				
				$params2 = array();
				$params2['member_id'] = $member['member_id'];
				$params2['type'] = '3';      //可提现钱包（现金钱包）
				$params2['prices'] = $money1*$sconfig['award_cash_percent']/100;
				$params2['periods'] = $periods;
				$params2['description'] =  '直推3个套餐奖励';
				$params2['addtime'] = mktime();
				$wallet_record->add($params2);   
				$wallet->where(array("member_id"=>$member['member_id']))->setInc('cash_money',$money1*$sconfig['award_cash_percent']/100);
			}  
			elseif($count > 3){
				$params1 = array(); 
				$params1['member_id'] = $member['member_id'];
				$params1['type'] = '2';      //消费钱包
				$params1['prices'] = $money2*$sconfig['award_shop_percent']/100;
				$params1['periods'] = $periods;
				$params1['description'] = '直推1个套餐奖励';
				$params1['addtime'] = mktime();
				$wallet_record->add($params1);  
				$wallet->where(array("member_id"=>$member['member_id']))->setInc('consume_money',$money2*$sconfig['award_shop_percent']/100);
				
				$params2 = array();
				$params2['member_id'] = $member['member_id'];
				$params2['type'] = '3';      //可提现钱包（现金钱包）
				$params2['prices'] = $money2*$sconfig['award_cash_percent']/100;
				$params2['periods'] = $periods;
				$params2['description'] = '直推1个套餐奖励';
				$params2['addtime'] = mktime();
				$wallet_record->add($params2);     
				$wallet->where(array("member_id"=>$member['member_id']))->setInc('cash_money',$money2*$sconfig['award_cash_percent']/100);
			} 
		}	
	}
	
	/**
	 * 获取一般三级父级用户（不紧缩，非会员直接跳过）
	 */
    protected function get_normal_parents($member_id){  
		$m = M('members');
		$parents =array();
		if(empty($member_id)){
			return $parents;
		}
		else{ 
			$sql ="SELECT tb2.member_id,tb2.level FROM lm_members AS tb1,lm_members AS tb2 WHERE tb1.recommend_name = tb2.member_name AND tb1.member_id=".$member_id;
			$p1 = M()->query($sql);
			if($p1[0]['level']>0){
				$parents['p1'] = $p1[0]['member_id'];
			}
			if(!empty($p1[0]['member_id'])){
				$sql ="SELECT tb2.member_id,tb2.level FROM lm_members AS tb1,lm_members AS tb2 WHERE tb1.recommend_name = tb2.member_name AND tb1.member_id=".$p1[0]['member_id'];
				$p2 = M()->query($sql);
				if($p2[0]['level']>0){
					$parents['p2'] = $p2[0]['member_id'];
				}
				if(!empty($p2[0]['member_id'])){
					$sql ="SELECT tb2.member_id,tb2.level FROM lm_members AS tb1,lm_members AS tb2 WHERE tb1.recommend_name = tb2.member_name AND tb1.member_id=".$p2[0]['member_id'];
					$p3 = M()->query($sql);
					if($p3[0]['level']>0){
						$parents['p3'] = $p3[0]['member_id'];
					}
				} 
			}
			return $parents;
		}
		
	}
	/**
	 * 升级父级用户等级（成为总经理或CEO）
	 */ 
	protected function Up_parent_level($member_id){
		if(!empty($member_id)){
			$sql ="SELECT tb2.member_id,tb2.level,tb2.role,tb2.member_name FROM lm_members AS tb1,lm_members AS tb2 WHERE tb1.recommend_name = tb2.member_name AND tb1.member_id=".$member_id;
			$parent_info = M()->query($sql);
			if(!empty($parent_info[0]['member_id'])){
				$role1 = M('role')->where(array('role_id'=>1,'status'=>1))->find();
				$role2 = M('role')->where(array('role_id'=>2,'status'=>1))->find();  
				$m = M('members');
				$recommend_name = $parent_info[0]['member_name'];
				$childrens = $this->get_all_childrens($recommend_name);
				$str = implode(',',$childrens);
				if($parent_info[0]['level'] == 2){  //父级是A套餐会员
					$w = array(); 
					$w['recommend_name'] = $recommend_name;
					$w['level'] = array('neq',0);
					$w['shield'] = 0;
					$count = $m->where($w)->count();  
					$w2 = array();
					$w2['level'] = array('neq',0);
					$w2['member_name'] = array('in',$str);
					$count2 = $m->where($w2)->count(); 
					if(($count >= $role1['vip_number']) && ($count2>= $role1['group_number'])){
						if($parent_info[0]['role'] == 0){
							$m->where(array('member_id'=>$parent_info[0]['member_id']))->save(array('role'=>1));
						}
						$w3 = array();  
						$w3['recommend_name'] = $recommend_name;
						$w3['role'] = array('egt',1);
						$w3['shield'] = 0;
						$count3 = $m->where($w3)->count();
						$w4 = array();
						$w4['role'] = array('egt',1);  
						$w4['member_name'] = array('in',$str);
						$count4 = $m->where($w4)->count();   
						if(($count3>=$role2['vip_number']) && ($count4>=$role2['group_number'])){
							$m->where(array('member_id'=>$parent_info[0]['member_id']))->save(array('role'=>2));
						} 
						$this->Up_parent_level($parent_info[0]['member_id']);  
					}  
				} 
				elseif($parent_info[0]['role'] == 1){  ////父级是总经理
					$w5 = array();
					$w5['recommend_name'] = $recommend_name;
					$w5['role'] = array('egt',1);
					$w5['shield'] = 0;
					$count5 = $m->where($w5)->count();
					$w6 = array();
					$w6['role'] = array('egt',1);
					$w6['member_name'] = array('in',$str);
					$count6 = $m->where($w6)->count(); 
					if(($count5>=$role2['vip_number']) && ($count6>=$role2['group_number'])){
						$m->where(array('member_id'=>$parent_info[0]['member_id']))->save(array('role'=>2));
						$this->Up_parent_level($parent_info[0]['member_id']);  
					} 
				}    
			}
		}
		
	}
	
	/** 
	 *获取所有团队成员(用于计算团队人数 ， 包括自己)
	 * return  array : 团队成员id   
	 */
	protected function get_all_childrens($member_name){
		$m = M('members');
		$w = array();
		$w['recommend_name'] = $member_name;
		$w['shield'] = 0;
		$rt = $m->where($w)->field('member_name')->select();
		$result = array();
		$result[] = $member_name;
		while(!empty($rt)){    
			$news = array();
			foreach($rt as $r){  
				$result[] = $r['member_name'];
				$news[] = $r['member_name'];
			}
			
			$str = implode(',',$news);
			$where = array();
			$where['recommend_name'] = array('in',$str);
			$where['shield'] = 0;
			$rt = $m->where($where)->field('member_name')->select();
		}
		return $result;   
	} 
	
	 private function upload($files = '')
    {
        $upload = new \Think\Upload(); // 实例化上传类
        //$upload->maxSize = 102400; // 设置附件上传大小
        $upload->exts = array(
            'jpg',
            'gif',
            'png',   
            'jpeg'
        ); // 设置附件上传类型
       // $upload->rootPath = C('IMG_ROOT_PATH'); // 设置附件上传根目录
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

    public function pay_order(){
		$this->assign('oid',I('oid'));
		$this->display('pay_order');
	}
   
}

?>



