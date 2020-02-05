<?php
/**
 * 会员管理
 * @author youyan
 *
 */
namespace Home\Controller;
use Think\Controller;
class UserController  extends BaseController
{
 
    /**
     * 会员列表
     */
    public function member_list()
	{
		$user_role = $this->user_role;
		$user_from =$this->user_from; 
		
		$Member=M('Member');    
		$where=array();
		if(ISSET($_POST['id']))
			{
				$where['id']=$_POST['id'];
			}
		if(ISSET($_POST['from']))
			{
				$where['from']=$_POST['from'];
			}
		if(ISSET($_POST['mall_name']))
			{
				$where['mall_name']=array('like','%'.$_POST['mall_name'].'%');
			}
		if(ISSET($_POST['member_name']))
			{ 
				$where['member_name']=array('like','%'.$_POST['member_name'].'%');
			} 
		if(ISSET($_POST['keyname']))
			{ 
				$where['member_name']=array('like','%'.$_POST['keyname'].'%');
			}	  				
		if($user_role=='2')
		{ 
			$where['from'] = $user_from;
		}    
        $count      = $Member->where($where)->count();// 查询满足要求的总记录数
        $Page       = new \Think\Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出
        $list = $Member->where($where)->order('register_time')->limit($Page->firstRow.','.$Page->listRows)->select();
		$PV = M('member_pv'); 
		foreach($list as $key =>$li)
		{
			$w['member_name'] = $li['member_name'];
			if($user_role =='2')
			{
				$w['from'] = $li['from'];
			}
			$list[$key]['money'] = $PV->where($w)->field('level_pv,commission,balance,user_money,from')->select(); 
		}    
		$ui['member_list'] = 'active'; 
        $this->assign('ui',$ui);	 
        $this->assign('list',$list);// 赋值数据集   
        $this->assign('page',$show);// 赋值分页输出
        $this->display("member_list"); //输出模板
    }    

    /**
     * 提现 传入会员的id 然后调用提现接口
     * 接口传入会员id号
     */
    public function getMoney(){
        $id=ISSET($_POST['id'])?$_POST['id']:0;
        if($id==0){
            die("ID出错");

        }
       $Member_info= $this->check_has_member($id);
       if($Member_info==false){
           die("没有这个人");
       }
       //调用提现接口。。。
    }

    /**
     * 
     * 获得提现申请列表
     */
    public function member_apply()
	{
       /* $id=empty($id)?(ISSET($_POST['id'])?$_POST['id']:0):$id;
        if($id==0)
		{  
            die("ID出错");
        }
        $Member_info= $this->check_has_member($id);
        if($Member_info==false){
            die("没有这个人");
        }*/
		$account_list=M("account");
        $where=array();
		$user_role = $this->user_role; 
		$user_from =$this->user_from; 
		if($user_role == '1') 
		{
            if(ISSET($_POST['keyname']))
				{ 
					$where['member_name']=array('like','%'.$_POST['keyname'].'%');
				}	  				
		}  
		elseif($user_role =='2')
		{
			$where['from'] = $user_from;
			  if(ISSET($_POST['keyname']))
				{  
					$where['member_name']=array('like','%'.$_POST['keyname'].'%');
				} 
		}
		$where['is_sup'] = '0';
		$where['isdelete']='0';
		$where['process_type'] = '1'; 
        //$where['member_id']=$id;
        $count      = $account_list->where($where)->count();// 查询满足要求的总记录数
        $Page       = new \Think\Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出
        $list = $account_list->where($where)->order('is_paid,add_time')->limit($Page->firstRow.','.$Page->listRows)->select();
		 $ui['member_apply'] = 'active';
         $this->assign('ui',$ui);  
        $this->assign('list',$list);// 赋值数据集   
        $this->assign('page',$show);// 赋值分页输出
        $this->display("member_apply"); //输出模板

    }


/**
     * 数据中心添加会员
     */
    public function add ()
    {
        $m = M('Member');
       // $field=C('REG_FIELD')? C('REG_FIELD'):'email_varchar';
        if(IS_POST)
		{
            //$where['userid_int']=I('post.id');
            $userinfo=I('post.member_name');
            $userinfo=I('post.password');
            $userinfo=I('post.mall_name');
            $userinfo=I('post.member_email');
            $userinfo=I('post.member_sex');
            $userinfo=I('post.birthday');
            $userinfo=I('post.recommend_name');
            $userinfo=I('post.mb_tel');
            $userinfo=I('post.member_tel');
            $userinfo=I('post.nick_name');
            $userinfo['register_time'] = date('y-m-d H:i:s',time());
            //$userinfo['last_time'] = date('y-m-d H:i:s',time());
            $userinfo['createip_varchar'] = get_client_ip();
            $userinfo['lastip_varchar'] = get_client_ip();
           // $userinfo['end_time'] = 0;
           // $userinfo['headimg']='';

            //$field=C('REG_FIELD')? C('REG_FIELD'):'email_varchar';
            $where=array();
            $where['member_name']=$userinfo['member_name'];
            $where['mall_name']=$userinfo['mall_name'];
            $is_exist_id=$m->where($where)->getField('id');
            if($is_exist_id){


                $this->error ( '账号已经存在' );

            }


            if(I('post.password')){
                $userinfo['password']=md5(I('post.password'));
            }else{
                $this->assign('user', $userinfo);

                $this->error ( '密码不能为空'  );

            }

            $m->add($userinfo);

            $this->success ( '操作成功','./admin.php?c=User' );

        }else{

           // $this->assign('field', $field);

            $this->display('e');
        }



    }

    public function updata_member_paid()    //更新用户审核  
	{
		$id=$_POST['replayid'];
		if($id =='' )
		{
			die('非法操作');
		}
		$member_account =M('account');
		$w['id'] = $id;
		$account = $member_account->where($w)->find();
		if($account['is_paid'] == 1)
		{
			die('该申请已经提现');     
		}
		$PV = M('member_pv');
		$where['member_name'] = $account['member_name'];
		$where['from'] = $account['from'];
		$where['mall_name'] = $account['mall_name'];
		$balance = $PV->where($where)->getField('balance');    //用户可提现金额
		if($balance == '')
		{
			die('找不到提现用户'); 
		}			
		if($balance + $account['amount'] < 0 )
		{ 
	        $this->redirect('User/member_apply' ,'',2,'金额超过用户可提款金额，提现失败');  
			exit;    
		}    
		 
		$B = M('balance');   
		$add['from'] = $account['from'];
		$add['mall_name'] = $account['mall_name'];
		$add['member_name'] = $account['member_name'];
		$add['money'] = $account['amount'];
		$add['change_time'] = mktime();
		$add['type'] = 4; 
		$add['info'] = '提现扣款';
		$add['isdelete'] = 0 ;  
		$rt = $B->add($add);
		if($rt)  
		{
			$new['balance'] = $balance+$account['amount'];
			$PV->where($where)->save($new);
			$new2['is_paid'] = '1';
			$new2['paid_time'] = mktime();
			$member_account->where($w)->save($new2);
			$this->redirect('User/member_apply' ,'',2,'提现成功');  
			exit;   
		} 
		else
		{
			$this->redirect('User/member_apply' ,'',2,'提现失败');  
			exit;   
		}
	}  

    /**
     * 判断是否有这个ID 没有返回false 有放回这个记录
     * @param unknown $id
     */
    private function check_has_member($id)
	{

       $Member=M('Member');
       $result =$Member->find($id);
       return $result;
    }
	

}

?>
