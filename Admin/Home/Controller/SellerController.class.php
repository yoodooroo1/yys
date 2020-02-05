<?php
namespace Home\Controller;
use Think\Controller;
/**
 * XUNXIN PC 后台管理 员工管理
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
class SellerController extends AdminController
{

    /**
     * 员工列表
     */
    public function seller_list()
    {
        
        
        $Seller = M();
        $table_pre = C('DB_PREFIX');
        $s = $table_pre . "seller";
        $m = $table_pre . "member";
        $where = array();
        $where['S.store_id'] = session('store_id');
        $list = $Seller->table($s . ' S')
            ->join($m . ' M on S.member_id=M.member_id')
            ->where($where)
            ->field('S.*,M.*')
            ->select();

         
        foreach($list as $k=>$v){
           if($v['member_id']==session('member_id')||$v['is_admin']==1) {
               $list[$k]['can_delete']=0;
           }else{
               $list[$k]['can_delete']=1;
           }
            
	}

        $ui['seller_list'] = 'active';
        $limit = $this->getLimitSellerNum();
        $num = $limit - count($list);
        if ($num <= 0) {
            $num = 0;
        }
        $this->assign('num',$num);
        $this->assign('ui', $ui);
        $this->assign('list', $list);
        $this->display('Seller:seller_list');
    }

    /**
     * 员工详情
     */
    public function seller_info()
    {
        if (IS_GET)
		{
            $seller_id = $_GET['seller_id'];
            $where = array();
            $where['seller_id'] = $seller_id;
            $Seller = M('Seller');
            $info = $Seller->where($where)->find();
            if (empty($info))
			{
                $this->error('数据出现异常--Seller');
			}
	    
            $Member = M('Member');
            $member_where = array();
            $member_where['member_id'] = $info['member_id'];
			$member_info = $Member->where($member_where)->find();
			// die(print_r($member_info));
            if (empty($member_info))
			{
                $this->error('数据出现异常--Member');
            }
            $info['member_truename'] = $member_info['member_truename'];
            $info['member_nickname'] = $member_info['member_nickname'];
            $info['member_sex'] = $member_info['member_sex'];
            $info['member_avatar'] = $member_info['member_avatar'];
            if (empty($info['member_avatar'])) 
			{
                $info['member_avatar'] = '/assets/images/defaultuser.jpg';
			} 

	    
			$ui['seller_info'] = 'active';
            $this->assign('ui', $ui);
            $this->assign('act', 'info');
            $this->assign('position', $this->getPosition());
            $this->assign('seller', $info);
            $this->display('Seller:seller_info');
        } else {
            $this->error('invalid params');
        }
    }

    /**
     * 添加员工
     */
    public function seller_add()
    {
        if(!$this->check()){
           $this->error('员工已经达到上限'); 
		}  
        $ui['seller_add'] = 'active';
        $this->assign('ui', $ui);
        $this->assign('act', 'add');
        $this->assign('position', $this->getPosition());
        $this->display('Seller:seller_info');
    }

    /**
     * 更新员工
     */
    public function seller_insert()
    {
        if (IS_POST) 
		{ 
            	
			$is_add = $_POST['act'] == 'add';
			if ($is_add)
			{
					if (! $this->check()) 
					{
						$this->error('员工已经达到上限',U('Seller/seller_list'));
					}
			}     
			$Member = M('Member');
			$member_name = $_POST['seller_name'];
			$member_passwd = $_POST['newMem_passwd'];
			if(empty($member_name))
			{
				$this->error("用户名为空");
			}
			$result=$this->checkNameANDPasswd($member_name, $member_passwd,$is_add);
			if($result['flag']==1)
			{
				$this->error($result['error']);
			}
			// 写入member的信息
			$mem_data = array();
			// 写入seller的信息
			$seller_data = array();
			if ($is_add)    
			{
			   
				$where = array();
				$where['member_name'] = $member_name;
				$info = $Member->where($where)->find();
				if (! empty($info)) 
				{
					$this->error('用户名已存在');
				}
			}
			else
			{
				$member_id = $_POST['member_id'];
				$seller_id = $_POST['seller_id'];
			}
			if (! $is_add) 
			{
				if (! empty($member_passwd))
				{
					$seller_data['member_passwd'] =md5($member_passwd);
				}
			}	
			$files = $_FILES;
			$name= $files['fileImg']['name'];
			
				// 有上传头像
			if ($files['fileImg']['error'] == 0&&!empty($name))
			{
				$info = $this->upload();
				
				$params = array();
				$params['file'] = "@" . realpath(dirname(__FILE__) . "/../../../") . "/images/" . $info['fileImg']['savepath'] . $info['fileImg']['savename'];
				$returnInfo = $this->uploadImgApi($params);
				
				$returnImg = json_decode($returnInfo, true);
				
				if ($returnImg[result] == 0) 
				{
					
					$url = $returnImg['datas']['ori_url'];
					$seller_data['member_avatar'] = $url;
				} 
				else   
				{  
					
					$this->error($returnImg['error']);
				}
			}
				// 头像上传结束	
			$seller_data['member_sex'] = $_POST['member_sex'];
			
			
			$seller_data['member_nickname'] = $_POST['member_nickname'];
			$Seller = M('Seller');
			$seller_role = $_POST['position'];
			if(empty($seller_role))
			{
			  $seller_role=$_POST['jobSelect'];
			}
			$seller_data['seller_role'] = trim($seller_role);
			$seller_data['store_id'] = session('store_id');
			$seller_data['seller_name'] = $member_name;
			$seller_data['description'] = $_POST['description'];
			$seller_data['member_truename'] = $_POST['member_truename'];
			$seller_data['allow_order']=$_POST['allow_order'];
			$seller_data['isserver']=$_POST['member_server'];
		 
			if ($is_add)    
			{   
				$seller_data['member_passwd'] = $member_passwd;
				$seller_data['member_name'] = $member_name;
				$seller_data['store_id'] = session('store_id');
				/* $member_id = $Member->data($mem_data)->add();
				$mem_data['member_name'] =$member_name ;
				
				if (! $member_id)
				{
					$this->error('添加失败--member');
				}
				$where=array();
				$where['member_name']=$member_name;
				$member_info=$Member->where($where)->find(); 
				if(empty($member_info))
				{
					$this->error('服务器数据库出错','Seller/seller_list');
				}
				$member_id=$member_info['member_id'];
				$seller_data['member_id'] = $member_id;
				$seller_id = $Seller->data($seller_data)->add();
				if (! $seller_id)
				{
					$this->error('添加失败--seller');
				} */
				$op = "add_seller";
				$param = array(
				"act" => 'seller',
				"op" => $op,
				"seller" => json_encode($seller_data,JSON_UNESCAPED_UNICODE)
				);  
				
				$returnInfo = $this->getReturnInfo($param);
				if($returnInfo['result']=='0')
				{
					
					$this->success('员工添加成功',U('Seller/seller_list'));
				}

			} 
			else 
			{	
			
				$seller_data['member_id'] = $member_id;
				$seller_data['seller_id'] = $seller_id;
				$seller_data['store_id'] = session('store_id');
				$op = "update_sellermember";
				$param = array(  
				"act" => 'seller',
				"op" => $op,
				"sellermember" => json_encode($seller_data,JSON_UNESCAPED_UNICODE)
				);  
				
				$returnInfo = $this->getReturnInfo($param);
				   
				
				if($returnInfo['result']=='0')
				$this->success('员工更新成功',U('Seller/seller_list'));
			    else     
				{	   
					$this->error($returnInfo['error']);
				}  
			}    
        } 
		else
		{
            $this->error('invalid params');
        }
    }
   
     /**
     * 员工表ajax调用公共接口
     * 目前用于服务和是否咨询的修改
     */
    public function ajax(){
	    if(IS_POST){
           
            $seller_id = $_POST['seller_id'];
            $type=$_POST['type'];
            $text=$_POST['text'];
			if($text=='0')
			{
				$text = '1';
			}
			else   
			{
				$text = '0';
			}
			$m = M('seller');
			
			$where = array();
			$where['store_id'] = session('store_id');
			$where['seller_id'] = array('NEQ',$seller_id);
			$info = $m->where($where)->field('seller_id,is_consult,isserver')->select();
			$where2 = array();
			$where2['store_id'] = session('store_id');
			$where2['seller_id'] = $seller_id;   
			$info2 = $m->where($where2)->field('seller_id,is_consult,isserver')->find();
		       
			if(($type =='isserver') && $text=='0')
			{
				if($info2['is_consult']=='1')
				{
					$returnInfo['result']	 = 	'2';	
					$returnInfo['error']	 = 	'关闭咨询客服才能关闭服务';  						
					echo json_encode($returnInfo,JSON_UNESCAPED_UNICODE);   
					exit;       
				}
				$tip = '0';   
				foreach($info as $in)  
				{  
					if($in['isserver']=='1')
					{ 
						$tip = '1'; 
					}
				}   
				if($tip == '0')   
				{   
					$returnInfo['result']	 = 	'1';	
					$returnInfo['error']	 = 	'至少需要一个员工开启服务';  						
					echo json_encode($returnInfo,JSON_UNESCAPED_UNICODE);   
					exit;     
				}
		  
			}
			if(($type =='is_consult') && $text=='0')
			{
				$tip = 0;
				foreach($info as $in)
				{
					if($in['is_consult']=='1')
					{
						$tip = '1';
					}
				}
				if($tip == '0')
				{
					$returnInfo['result']	 = 	'3';	
					$returnInfo['error']	 = 	'至少需要一个员工成为咨询客服';  						
					echo json_encode($returnInfo,JSON_UNESCAPED_UNICODE);   
					exit;   
				}
			}
			if(($type =='is_consult') && $text=='1')
			{
				if($info2['isserver']=='0')
				{
					$returnInfo['result']	 = 	'4';	
					$returnInfo['error']	 = 	'需要开启服务才能成为咨询客服';  						
					echo json_encode($returnInfo,JSON_UNESCAPED_UNICODE);   
					exit;   
					
				}   
			}
            




			
            $result = array();   

			$w = array();
			$w['store_id'] = session('store_id');
			$w['is_consult'] = '1';
				
			$oldid = $m->where($w)->getField('seller_id');
		
            if (! empty($seller_id))      
			{  
                if ($type == 'isserver')    
				{
					$op = "update_seller";
					$param = array(
					"act" => 'seller',
					"op" => $op,
					"seller" => json_encode(array('seller_id'=>$seller_id,'isserver'=>$text),JSON_UNESCAPED_UNICODE)
					);    
					$returnInfo = $this->getReturnInfo($param);
					$returnInfo['type']	 = 	$type;			
					$returnInfo['text']	 = 	$text;			
					echo json_encode($returnInfo,JSON_UNESCAPED_UNICODE);
				
                }    
				elseif ($type == 'is_consult') 
				{
					$rt = array();
					$rt['seller_id2'] = $seller_id;
					$rt['seller_id1'] = $oldid;
                    $op = "update_consult";
					$param = array(      
					"act" => 'seller',
					"op" => $op,
					"consult" => json_encode($rt)
					);       
					$returnInfo = $this->getReturnInfo($param);  
                    $returnInfo['type']	 = 	$type;	
					$returnInfo['text']	 = 	$text;  						
					echo json_encode($returnInfo,JSON_UNESCAPED_UNICODE);   
					 
						
                }       
			}   
	    
        }
    }
    
    
    /**
     * ajax  调用更改服务信息
     */
    private function server_update($seller_id,$is_server)
    {
         
                $where = array();
                $where['seller_id'] = $seller_id;
                $where['store_id'] = session('store_id');
                $Seller = M('Seller');
                $info = $Seller->where($where)->find();
                $data = array();
                $data['isserver'] = ($is_server + 1) % 2;
                $result=array();
                if (empty($info)) {
                    $result['code'] = '0';
                } else {
                    $num = $Seller->where($where)
                        ->data($data)
                        ->save();
                    if ($num) {
                        $result['code'] = '1';
                        $result['type']='isserver';
                        if($is_server){
                          $result['text'] ='0';
                        }else{
                          $result['text'] ='1';
                        }
                    } else {
                        $result['code'] = '0';
                    }
                }

         return $result;
     
    }

    /**
     * ajax  调用更改consult信息
     */
   private function consult_update($seller_id,$is_consult){
       
 
         
                $where = array();
                $where['seller_id'] = $seller_id;
                $where['store_id'] = session('store_id');
                $Seller = M('Seller');
                $info = $Seller->where($where)->find();
                $data = array();
		$data['is_consult'] = ($is_consult + 1) % 2;
		file_put_contents('text2222.txt',json_encode($data));
                $result=array();
                if (empty($info)) {
                    $result['code'] = '0';
                } else {
                    $num = $Seller->where($where)
                        ->data($data)
                        ->save();
		     	 
		    if ($num) {
		       
                        $result['code'] = '1';
                        $result['type'] ='is_consult';
                        if($is_consult){
                        $result['text'] = '0';
                        }else{
                        $result['text'] = '1';
                        }
                    } else {
                        $result['code'] = '0';
                    }
		}
		file_put_contents('text2.txt',json_encode($result)); 
               return  $result;
      

    }

    /**
     * 员工服务开启或则关闭
     */
    public function seller_server()
    {
        
        if (IS_GET) {
            $seller_id = $_GET['seller_id'];
            if ($seller_id <= 0) {
                $this->error('invalid params');
            }
            $is_server=$_GET['is_server'];
            $where = array();
            $where['seller_id'] = intval($seller_id);
            $where['store_id'] = session('store_id');
        
            $Seller = M('Seller');
            $info = $Seller->where($where)->find();
            if (empty($info)) {
                $this->error('invalid params');
            }
            $data=array();
	    $data['isserver']= $is_server;
	   
	    $num = $Seller->where($where)->data($data)->save();
	  
            if ($num !== false) {
                $this->success('服务修改成功');
            } else {
                $this->error('服务修改失败');
            }
        }
        
        
    }
    
    /**
     * 删除
     */
    public function seller_delete()
    {
        if (IS_GET) {
            $seller_id = $_GET['seller_id'];
            if ($seller_id <= 0) {
                $this->error('invalid params');
            }
            $where = array();
            $where['seller_id'] = intval($seller_id);
            $where['store_id'] = session('store_id');
            $where['is_admin']=0;
            $Seller = M('Seller');

	    $info= $Seller->where($where)->find();
            if(empty($info)||$info['is_admin']==1){
                $this->error('invalid params');
            }
            $num = $Seller->where($where)->delete();
            if ($num !== false) {
                $this->success('删除成功');
            } else {
                $this->error('删除成功');
            }
        }
    }

     /**
     * ajax  调用更改服务信息废除
     */
    public function server_update1()
    {
        if (IS_POST) {
            $is_ajax = $_POST['ajax'];
            if ($is_ajax) {
                $is_server = $_POST['isserver'];
                $seller_id = $_POST['seller_id'];
                
                $where = array();
                $where['seller_id'] = $seller_id;
                $where['store_id'] = session('store_id');
                $Seller = M('Seller');
                $info = $Seller->where($where)->find();
                $data = array();
                $data['isserver'] = ($is_server + 1) % 2;
                $result=array();
                if (empty($info)) {
                    $result['error'] = 1;
                } else {
                    $num = $Seller->where($where)
                        ->data($data)
                        ->save();
                    if ($num) {
                        $result['error'] = 0;
                        $result['isserver'] = $data['isserver'];
                    } else {
                        $result['error'] = 1;
                    }
                }
                $datas=json_encode($result);
                $this->ajaxReturn($datas, 'JSON');
            }
        }
    }

    /**
     * ajax  调用更改consult信息废除
     */
    public function consult_update1(){
        if (IS_POST) {
            $is_ajax = $_POST['ajax'];
            if ($is_ajax) {
                $is_consult = $_POST['is_consult'];
                $seller_id = $_POST['seller_id'];
        
                $where = array();
                $where['seller_id'] = $seller_id;
                $where['store_id'] = session('store_id');
                $Seller = M('Seller');
                $info = $Seller->where($where)->find();
                $data = array();
                $data['is_consult'] = ($is_consult + 1) % 2;
                $result=array();
                if (empty($info)) {
                    $result['error'] = 1;
                } else {
                    $num = $Seller->where($where)
                    ->data($data)
                    ->save();
                    if ($num) {
                        $result['error'] = 0;
                        $result['is_consult'] = $data['is_consult'];
                    } else {
                        $result['error'] = 1;
                    }
                }
                $datas=json_encode($result);
                $this->ajaxReturn($datas, 'JSON');
            }
        }
    }


    /**
     * 获得默认职位
     */
    private function getPosition()
    {
        $position = array();
        $position[0] = '总经理';
        $position[1] = '副总经理';
        $position[2] = '业务员';
        $position[3] = '销售客服';
        $position[4] = '售后客服';
        return $position;
    }

    /**
     * 图片上传
     *
     * @param string $files            
     */
    private function upload($files = '')
    {
        $upload = new \Think\Upload(); // 实例化上传类
        $upload->maxSize = 2048000; // 设置附件上传大小
        $upload->exts = array(
            'jpg',
            'gif',
            'png',
            'jpeg'
        ); // 设置附件上传类型
        $upload->rootPath = C('IMG_ROOT_PATH'); // 设置附件上传根目录
        $upload->savePath = ''; // 设置附件上传（子）目录
        $upload->replace = true; // 上传文件
        $info = $upload->upload($files);
		
        if (! $info){ // 上传错误提示错误信息
		
            $this->error($upload->getError());
        } else { // 上传成功
                 // $this->success('上传成功！');
            return $info;
        }
    }

    /**
     * 检查是否可以添加员工
     */
    private function check()
    {
        if (session('is_admin') && $this->getSellerCount() <= $this->getLimitSellerNum())
            return true;
        return false;
    }

    /**
     * 获得员工数目
     */
    private function getSellerCount()
    {
        $Seller = M('Seller');
        $where = array();
        $where['store_id'] = session('store_id');
        $num = $Seller->where($where)->count();
        return $num;
    }
    
    /**
     * 通过角色来设置权限
     * 返回数组资料
     */
    private function explanRole($role){
        $right=array(
            'is_server'=>0,
            'allow_store'=>0,
            'is-consult'=>0,
            
        );
        if($role=='管理员'||session('is_admin')){
            $right=array(
                'is_server'=>1,
                'allow_store'=>1,
                'is-consult'=>1,
            
            );
        }
        
        if($role=='')
        return $right;
    }



  /**
     * 检查账号密码
     * 
     * @param unknown $name            
     * @param unknown $psw            
     * @param string $act            
     */
    private function checkNameANDPasswd($name, $psw, $act = 0)
    {
        $result = array();
        $error='';
        if ($act) {
            $flag1 = $this->checkPasswd($psw);
            $flag2 = $this->checkName($name);
            
            if(!$flag1){
                $result['flag']=1;
                $error.='-密码由不少于6位数字或字母组成-';
            }
            if(!flag2){
                $result['flag']=1;
                $error.='-账号不合理-';
            }
            
        } else {
            if (! empty($psw)) {
                $flag = $this->checkPasswd($psw);
                if (! $flag) {
                    $result['flag'] = 1;
                    $error = '-密码由不少于6位数字或字母组成-';
                }
            }
        }
        $result['error']=$error;
        return $result;
    }

    /**
     * 检查名字
     * 
     * @param unknown $name            
     */
    private function checkName($name)
    {
        $match_num = '/^[0-9]{6,15}$/';
        $match = '/^[0-9a-zA-Z]{6,15}$/';
        
        if (! preg_match($match_num, $name)) {
            if (! preg_match($match, $name)) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * 检查密码
     * 
     * @param unknown $psw            
     */
    private function checkPasswd($psw)
    {
        $match_pwd = '/^[0-9a-zA-Z]{6,}$/';
        if (preg_match($match_pwd, $psw)) {
            return true;
        } else {
            return false;
        }
    }
   
    /**
     * 获得员工上限
     * 
     * @return Ambigous <mixed, NULL, unknown>
     */
    private function getLimitSellerNum()
    {
        if (! session('seller_num')) {
            $this->getLimiteNumSession();
        }
        $limit = session('seller_num');
        return $limit;
    }

}
?>
