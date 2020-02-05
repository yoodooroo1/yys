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
   
    public function award_config()
    {
		$role = M('role');
        if (IS_POST) {
			$st = M('store_config');
			$se = M('settlement_config');
			$data1 = array();
			$data1['count_day'] = I('count_day');
			$w1 = array();
			$w1['id'] =  1;
			$st->where($w1)->save($data1);
	        $data2 = array();
			$data2['drawmoney_percent'] = I('drawmoney_percent');
			$data2['min_pv'] = I('min_pv');
			$data2['award_cash_percent'] = I('award_cash_percent');
			$data2['award_shop_percent'] = 100-I('award_cash_percent');
			$data2['recommend1_percent'] = I('recommend1_percent');
			$data2['recommend2_percent'] = I('recommend2_percent');
			$data2['recommend3_percent'] = I('recommend3_percent');
			$data2['direct_push_award'] = I('direct_push_award');
			$data2['more_push_award'] = I('more_push_award');
			$w2 = array();  
			$w2['status'] = 1;
			$se->where($w2)->save($data2);
			$data3 = array();
			$data3['vip_number'] = I('vip_number1');
			$data3['group_number'] = I('group_number1');
			$data3['get_group_percent'] = I('get_group_percent1');
			$data3['get_breedgroup_percent'] = I('get_breedgroup_percent1');
			$w3 = array();
			$w3['role_id'] = 1;
			$w3['status'] = 1;
			$role->where($w3)->save($data3);
			$data4 = array();
			$data4['vip_number'] = I('vip_number2');
			$data4['group_number'] = I('group_number2');
			$data4['get_group_percent'] = I('get_group_percent2');
			$data4['get_breedgroup_percent'] = I('get_breedgroup_percent2');
			$data4['get_allgroup_percent'] = I('get_allgroup_percent2');
			$w4 = array();  
			$w4['role_id'] = 2;    
			$w4['status'] = 1;
			$role->where($w4)->save($data4);
            $this->success('操作成功', U('Index/index'));
        }   
		else {
			$wm = array();
			$wm['role_id'] = 1;
			$wm['status'] = 1;
			$managerinfo = $role->where($wm)->find();
			$wc = array();
			$wc['role_id'] = 2;
			$wc['status'] = 1;
			$ceoinfo = $role->where($wc)->find();
			$where = array();
			$where['stc.id'] = 1;
			$where['sec.status'] = 1;  
			$info = M()->table('lm_store_config stc,lm_settlement_config sec')->where($where)->field('stc.count_day,sec.*')->find();
			  
			$this->assign('managerinfo',$managerinfo); 
			$this->assign('ceoinfo',$ceoinfo);
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

   
}

