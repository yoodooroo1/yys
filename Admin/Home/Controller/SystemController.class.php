<?php
namespace Home\Controller;
use Think\Controller;
/**
 * XUNXIN PC 后台管理 系统管理
 * ============================================================================
 * 版权所有 2005-2010 厦门微聚点科技有限公司，并保留所有权利。
 * 网站地址: http://www.vjudian.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: youyan $
 * $Id: SysController.class.php
 */
class SystemController extends AdminController
{
   	
	public function __construct()
    {
        parent::__construct();
        if(!$this->checkAuth()){
			$this->error('你没有该权限',U('Index/index'));
		} 
    }   
	
    public function award_config()
    {
		$config = M('operate_config'); 
		$w = array();
		$w['status'] = 1;
        if (IS_POST) {
			$data = I();
			$rt = $config->where($w)->save($data);
			if($rt !== false){
				$this->addAdminLog('2','运营商设置操作');
				$this->success('操作成功', U('System/award_config'));
			}else{ 
				$this->error('编辑运营商操作失败'); 
			}
        }   
		else {
			$info = $config->where($w)->find();
			$this->assign('info',$info);   
            $ui['award_config'] = 'active';
            $this->assign('ui', $ui);
            $this->display('award_config');
        }
    }    
   
   

    

    /**
     * 清除缓存
     */
    public function clearcache()
    { 
        // 缓存路径
        $Webpath = './Admin/Runtime/';
        if (is_dir($Webpath)) {
        \Think\File::del_dir($Webpath);
        }
        $this->success('操作成功');
    }
	
	/*管理员操作日志*/
  
	public function AdminLogList(){   

		$m =M('admin_log');

		$w =array();

		$w['status'] = 1;

		$count = $m->where($w)->count(); // 查询满足要求的总记录数

        $Page = new \Think\Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数(25)

        $show = $Page->show(); // 分页显示输出  

		$lists = $m->where($w)->order('addtime DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();      

		$this->assign('lists', $lists);

		$ui['log_list'] = 'active';

		$this->assign('ui',$ui);   
		$this->assign('page',$show);	
		$this->display('log_list');

	}
	
	public function DelLog(){
		$str = I('str');
		if(!empty($str)){
			$w = array();
			$w['id'] =array('in',$str);
			M('admin_log')->where($w)->save(array('status'=>0));
			$this->addAdminLog('7',"删除日志操作，日志ID:".$str);
		}      
		$arr = array();
		$arr['status'] = 1;
		$arr['desc'] = '删除成功';
		echo json_encode($arr,JSON_UNESCAPED_UNICODE);	
	}

   
}

