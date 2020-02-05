<?php
/**
 * 会员管理
 * @author youyan
 *
 */
namespace Home\Controller;
use Think\Controller;
class OpenController  extends BaseController
{
 
    /**
     * 会员列表
     */
    public function open_list()
	{
		$user_role = $this->user_role;
	   if($user_role != 1)
	   {
		   $this->error ( '您没有开户的权限', './admin.php' );
	   } 
		
		$Member=M('peitao');     
		$where=array();  
		if(ISSET($_POST['keyname']))
			{ 
				 $keyname = $_POST['keyname'];   
				 $where['_string']="(user_name like '%".$keyname."%')  OR (mall_name like '%".$keyname."%')"; 
			}	   				 
        $count      = $Member->where($where)->count();// 查询满足要求的总记录数
        $Page       = new \Think\Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出
        $list = $Member->where($where)->order('addtime desc')->limit($Page->firstRow.','.$Page->listRows)->select();   
		$ui['open_list'] = 'active'; 
        $this->assign('ui',$ui);	 
        $this->assign('list',$list);// 赋值数据集   
        $this->assign('page',$show);// 赋值分页输出
        $this->display("open_list"); //输出模板
    }    

   public function open_add() 
   {
	   $m = M('peitao'); 
		if(session('adminRole')==2 )
		{
			$this->error ( '您没有新增开户的权限', './admin.php' );
		}     
		if(IS_POST) 
		{
			if(I('post.member_name') == '') 
			{  
				$this->error ( '用户名不能为空', ('./admin.php?c=Open&a=open_list') ); 
				exit;				
			}
			if(I('post.mall_name') == '') 
			{  
				$this->error( '营运中心不能为空', ('./admin.php?c=Open&a=open_list'));  
				exit;				
			} 
			
			$w['user_name'] = I('post.member_name');
			$w['from'] = I('post.from_name');
			$w['mall_name'] = I('post.mall_name');
			$rt = $m->where($w)->getField('id');
			if($rt !='')
			{
				$this->error ( '该用户已存在', ('./admin.php?c=Open&a=open_add') ); 
				exit;	  
			}
		    $add['user_name'] = I('post.member_name');
			$add['pt_type'] = I('post.pt_type');

			if(I('post.from_name')==1){
				$add['from']='盈联信';
			}else{
				$add['from']='易企盈';
			}

			 
			$add['mall_name'] = I('post.mall_name');
			$add['addtime'] = mktime();  
			$m->add($add);
			$this->success ( '操作成功','./admin.php?c=Open&a=open_list' ); //U (  'sys/admin' )
		}  
		else
		{  
			$this->assign('user', array()); 
			$ui['open_add'] = 'active';
            $this->assign('ui',$ui);  
			$this->display('open_add');
		} 
    } 
	

}

?>
