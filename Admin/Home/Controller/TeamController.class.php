<?php
namespace Home\Controller;
use Think\Controller;
/**
 * XUNXIN  团队管理管理
 * ============================================================================
 * 版权所有 2005-2010 厦门微聚点科技有限公司，并保留所有权利。
 * 网站地址: http://www.vjudian.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: youyan $
 * $Id: MemberController.class.php
 */
class TeamController extends AdminController
{
    /**
     * 成员列表
     */
    public function team_list()
    {
      	$center = M('operate_center');
		$operate_id = $center->where(array('login_name'=>session('loginname')))->getField('id');
		$shareholder = M('operate_shareholder');
		$info = $center->where(array('id'=>$operate_id,'status'=>1))->find();
		if(empty($info) || empty($operate_id)){
			$this->error('改运营商不存在');
			die;    
		}      
		$lists = $shareholder->where(array('operate_id'=>$operate_id,'status'=>1))->select();
		$record = M('operate_shareholder_total_price');
		foreach($lists as $key=>$list){
			$w1 = array();
			$w1['shareholder_id'] = $list['id'];
			$total = $record->where($w1)->sum('value');
			$lists[$key]['total'] = empty($total) ? 0 : $total;
			$w2 = array();  
			$w2['shareholder_id'] = $list['id'];
			$w2['is_get'] = 0;
			$unget = $record->where($w2)->sum('value');
			$lists[$key]['unget'] =empty($unget) ? 0 : $unget;
			
		}  
		$this->assign('lists',$lists);  
		$this->assign('operate_id',$operate_id);  
        $ui['team_list'] = 'active'; 
        $this->assign('ui',$ui);
        $this->display("team_list"); // 输出模板
    }

	
	
	
	
    public function team_info()
    { 
		$id = I('id');
		$center = M('operate_center');
		$operate_id = $center->where(array('login_name'=>session('loginname')))->getField('id');
		$shareholder = M('operate_shareholder');
		if(empty($id)){
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
			$act = 'info';
			$info = $shareholder->where(array('id'=>$id,'status'=>1))->find();	
		}  
		$this->assign('info',$info);
		$this->assign('act',$act); 
	   	$ui['team_info'] = 'active';
        $this->assign('ui',$ui);
        $this->display('team_info');  
    }
	
	/*运营商成员编辑*/
	public function team_edit(){
		$act = $_POST['act'];  
		$shareholder = M('operate_shareholder');
		$member = M('members');
		$data = array();
		$center = M('operate_center');
		$operate_id = $center->where(array('login_name'=>session('loginname')))->getField('id');
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
				$this->success('修改股东信息成功',U('Team/team_list'));
			}
		}      
		else if($act == 'insert'){ 
			if($sid = $shareholder->add($data)){  
				$this->addAdminLog('4',"添加股东信息操作,股东ID：".$sid);
				$this->success('添加股东信息成功',U('Team/team_list'));
			}else{
				$this->error('添加股东信息失败');   
			}
		}	      
	}
	
    /*删除成员*/	
	public function del_shareholder(){
		$id = $_GET['id'];
		$center = M('operate_shareholder');
		if($center->where(array('id'=>$id))->save(array('status'=>0))){
			$this->addAdminLog('4',"删除股东信息操作,股东ID：".$id);
			$this->success('删除股东成功',U('Team/team_list'));  
		}else{          
			$this->error('删除股东失败');
		}  
		  
	}
 
	public function marketing()
    {
		$center = M('operate_center');
		$try_time = I('try_time');
		if($try_time != ''){
			$center->where(array('login_name'=>session('loginname')))->save(array('try_time'=>$try_time));
		}
		$info = $center->where(array('login_name'=>session('loginname')))->field('id,level,try_time')->find();
		$this->assign('try_time',$info['try_time']);
		$operate_id = $info['id'];
		$level = $info['level'];
		$operate_config = M('operate_config')->where(array('status'=>1))->find();
		$discount = 10;
		if($level == 1){
			$discount = $operate_config['first_discount']/10;
		}else if($level == 2){
			$discount = $operate_config['secend_discount']/10;
		}else if($level == 3){
			$discount = $operate_config['third_discount']/10;
		}else if($level == 4){
			$discount = $operate_config['fourth_discount']/10;
		}else if($level == 5){
			$discount = $operate_config['fifth_discount']/10;
		}else if($level == 6){
			$discount = $operate_config['sixth_discount']/10;
		}
		$package = M('package_list');
		$w = array();
		$w['lm_shareholder_package_edit.operate_id'] = $operate_id;
		$w['lm_shareholder_package_edit.status'] = 1;
		$w['lm_package_list.status'] = 1;
		$sql = "SELECT A.*,B.package_price,B.package_price2,B.package_price3 FROM lm_package_list A LEFT JOIN(SELECT * FROM lm_shareholder_package_edit WHERE operate_id = $operate_id AND status =1) B ON A.packageid = B.package_id WHERE A.status=1";
		$lists = M()->query($sql);
		foreach($lists as &$list){  
			if(empty($list['package_price'])){
				$list['package_price'] = $list['market_price'];
			}
			if(empty($list['package_price2'])){
				$list['package_price2'] = $list['market_price2'];
			}
			if(empty($list['package_price3'])){
				$list['package_price3'] = $list['market_price3'];
			}
			$operate_price =  $list['market_price']*$discount;
			$list['operate_price'] = ($operate_price > $list['min_price']) ? $operate_price : $list['min_price'] ; 
			
			$operate_price2 =  $list['market_price2']*$discount;
			$list['operate_price2'] = ($operate_price2 > $list['min_price2']) ? $operate_price2 : $list['min_price2'] ; 
			
			$operate_price3 =  $list['market_price3']*$discount;
			$list['operate_price3'] = ($operate_price3 > $list['min_price3']) ? $operate_price3 : $list['min_price3'] ; 
			
		}
		$this->assign('lists',$lists);
	   	$ui['marketing'] = 'active';   
        $this->assign('ui',$ui);
        $this->display('marketing');  
    }
	
	/*设置套餐客户价格*/
	public function ajax_edit_package_price(){
		$package_id = I('package_id');
		$package_price = I('package_price');
		$package_price2 = I('package_price2');
		$package_price3 = I('package_price3');
		$center = M('operate_center');
		$operate_id = $center->where(array('login_name'=>session('loginname')))->getField('id');
		$rt = array();  
		if(!empty($package_id) && !empty($package_price)){
			$m = M('shareholder_package_edit');
			$w = array();
			$w['operate_id'] = $operate_id;
			$w['package_id'] = $package_id;
			$w['status'] = 1;
			$check = $m->where($w)->find();
			$data = array();
			if(empty($check)){
				$data['operate_id'] = $operate_id;
				$data['package_id'] = $package_id;
				$data['package_price'] = $package_price;
				$data['package_price2'] = $package_price2;
				$data['package_price3'] = $package_price3;
				$data['status'] = 1;
				$data['edittime'] = mktime();
				$jg = $m->add($data);
			}else{
				$data['package_price'] = $package_price;
				$data['package_price2'] = $package_price2;
				$data['package_price3'] = $package_price3;
				$data['status'] = 1;
				$data['edittime'] = mktime();
				$jg = $m->where($w)->save($data);
			} 
			if($jg !== false){
				$rt['status'] = 1;
			}else{
				$rt['status'] = -1;
				$rt['desc'] = '更改客户价格失败';	
			}
		}elseif(empty($operate_id)){
			$rt['status'] = -1;
			$rt['desc'] = '非法登录，该运营商ID不存在';	
		}else{
			$rt['status'] = -1;
			$rt['desc'] = '参数错误';	
		}
		echo json_encode($rt,JSON_UNESCAPED_UNICODE);
	}
 

  

}
?>






