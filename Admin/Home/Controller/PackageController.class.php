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
    
    /**
     * 套餐列表
     */
    public function lists()
    { 	
		$w = array();
		$w['status'] = 1;
		$m = M('package_list');
		$count = $m->where($w)->count();
	    $Page = new \Think\Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show(); // 分页显示输出  
		$lists = $m->where($w)->order('sort DESC,packageid DESC')->limit($Page->firstRow . ',' .$Page->listRows)->select(); 
		$this->assign('lists',$lists);  
		$this->assign('page',$show); 
		$ui['package_list'] = 'active';     	
        $this->assign('ui', $ui);
        $this->display("Package:lists"); // 输出模板
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
		$m = M('package_list');
		$w = array();
		$w['packageid'] = $packageid;
		$w['status'] = 1;
		$info = $m->where($w)->find();   
		$this->assign('info',$info);
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
		/* $files = $_FILES;
	 
		if($files['main_img']['error'] == '0' &&!empty($files['main_img']['name'])){
			$info = $this->upload();
			$imgurl = ADMIN_URL.'/Uploads/'.$info['main_img']['savepath'] . $info['main_img']['savename'];
			$datas['main_img'] = $imgurl;  
		}   */
		if(!empty($packageid)){
			$oldinfo = $package->where(array('packageid'=>$packageid))->find();
			unset($datas['packageid']); 
			$w = array();
			//$w['lm_package_list.is_show'] = 1;
			$w['lm_package_list.packageid'] =$packageid;
			$datas['edittime'] = mktime();
			$rt = $package->where($w)->save($datas);
			if($rt || ($rt =='0')){
				if(($oldinfo['market_price'] != $datas['market_price']) || ($oldinfo['min_price'] != $datas['min_price']) || ($oldinfo['market_price2'] != $datas['market_price2']) || ($oldinfo['min_price2'] != $datas['min_price2']) || ($oldinfo['market_price3'] != $datas['market_price3']) || ($oldinfo['min_price3'] != $datas['min_price3']) ){
					M('shareholder_package_edit')->where(array('package_id'=>$packageid))->delete();	
				} 
				$this->addAdminLog('5',"修改套餐详情,套餐ID：".$packageid);
				$this->success('修改套餐详情成功',U('Package/lists'));
			} 
			      
		}else{
			$datas['addtime'] = mktime();
			$datas['edittime'] = mktime();
			if($pid = $package->add($datas)){
				$this->addAdminLog('5',"新增套餐,套餐ID：".$pid);
				$this->success('添加套餐成功',U('Package/lists'));
			}
		}
		  
    } 

	public function  del_package(){  
		$id = I('pid');
		if(!empty($id)){
		
			 $rt = M('package_list')->where('packageid = '.$id)->save(array('status'=>0));
			   
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
		$order = M('vip_orders');
		$w = array();
		$w['lm_vip_orders.status'] = 1;
		$order_id = I('order_id');
		if(!empty($order_id)){
			$w['lm_vip_orders.id'] = $order_id;  
		}   
		$orderSn = I('orderSn');
		if(!empty($orderSn)){
			$w['lm_vip_orders.orderSn'] = $orderSn;
		}	
		$packageid = I('packageid');
		if(!empty($packageid)){
			$w['lm_vip_orders.packageid'] = $packageid;
		}	
		$paytype = I('paytype');
		if(!empty($paytype)){
			$w['lm_vip_orders.paytype'] = $paytype;
		}	
		$issettlement = I('issettlement');
		if($issettlement !== ''){   
			$w['lm_vip_orders.issettlement'] = $issettlement;
		}	
		$member_name = I('member_name');
		if(!empty($member_name)){
			$w['lm_vip_orders.member_name'] = array('like',"%$member_name%");
		}
		$tel = I('tel');
		if(!empty($tel)){
			$w['lm_vip_orders.tel'] = array('like',"%$tel%");
		}	
		$operate_name = I('operate_name');
		if(!empty($operate_name)){
			$w['lm_operate_center.operate_name'] = array('like',"%$operate_name%");
		}
		$recommend_code = I('recommend_code');
		if(!empty($recommend_code)){
			$w['lm_vip_orders.recommend_code'] = $recommend_code;
		}  
		$Time1 = I('Time1');
		$Time2 = I('Time2');
		if(!empty($Time1) && empty($Time2))  
		{
			$t = strtotime($Time1)+24*60*60 ;
			$w['_string']= "lm_vip_orders.rechargetime >= '".strtotime($Time1) ."'&& lm_vip_orders.rechargetime < '".$t."'";
		}         
		if(!empty($Time2) && empty($Time1))
		{  
			$w['_string']= "lm_vip_orders.rechargetime<= '". strtotime($Time2)."'";  
		}  
		if(!empty($Time2) && !empty($Time1))
		{   
			$t = strtotime($Time2)+24*60*60 ;
			$w['_string']= "lm_vip_orders.rechargetime >= '".strtotime($Time1) ."'&& lm_vip_orders.rechargetime < '" .$t."'";  
		} 
		$count = $order->where($w)->join('LEFT JOIN lm_package_list ON lm_package_list.packageid = lm_vip_orders.packageid')->join('LEFT JOIN lm_operate_center ON lm_operate_center.id = lm_vip_orders.operate_id')->count();
		$Page = new \Think\Page($count, 15);
		$lists = $order->where($w)->join('LEFT JOIN lm_package_list ON lm_package_list.packageid = lm_vip_orders.packageid')->join('LEFT JOIN lm_operate_center ON lm_operate_center.id = lm_vip_orders.operate_id')->field('lm_vip_orders.*,lm_package_list.name,lm_operate_center.operate_name')->order('lm_vip_orders.id DESC')->limit($Page->firstRow . ',' .$Page->listRows)->select(); 
		$info = array(); 
		$w2 = array();
		$w2['status'] = 1;
		$total_saleprice = $order->where($w2)->sum('sale_price');
		$info['total_saleprice'] = empty($total_saleprice) ? 0 : $total_saleprice;
		$total_operate_profit = $order->where($w2)->sum('operate_profit');
		$info['total_operate_profit'] = empty($total_operate_profit) ? 0 : $total_operate_profit;
		$trade = M('operate_trade_record');
		$w3 = array();
		$w3['status'] = 1;
		$w3['type'] = 1;
		$total_recharge = $trade->where($w3)->sum('value');
		$info['total_recharge'] = empty($total_recharge) ? 0 : $total_recharge;
		$w4 = array();
		$w4['status'] = 1;
		$w4['type'] = 2;
		$total_usedprice = $trade->where($w4)->sum('value');
		$info['total_usedprice'] = empty($total_usedprice) ? 0 : abs($total_usedprice);
		
		$package = M('package_list');
		$package_list = $package->where(array('status'=>1))->field('packageid,name')->select();
		$this->assign('package_list',$package_list);
		$show = $Page->show(); 
		$this->assign('page',$show);
		$this->assign('lists',$lists);
		$this->assign('info',$info);
        $ui['package_order'] = 'active';           
        $this->assign('ui', $ui);
  
        $this->display("Package:order"); // 输出模板
    }
	
	
	
	
}

?>



