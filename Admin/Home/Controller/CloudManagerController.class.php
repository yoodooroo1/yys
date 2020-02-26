<?php


namespace Home\Controller;


class CloudManagerController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        if(!$this->checkAuth()){
            $this->error('你没有该权限',U('Index/index'));
        }
    }

    public function qcloud_config(){
        $center = M('cloud_product_list');
        $w = [];
        $w['is_delete'] = 0;
        $count = $center->where($w)->count(); // 查询满足要求的总记录数
        $Page = new \Think\Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show(); // 分页显示输出
        $lists = $center->where($w)->order('addtime DESC,id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($lists as $k=>$list){
            if($list['package_id'] == 1){
                $lists[$k]['package_name'] = '商铺版';
            }elseif ($list['package_id'] == 2){
                $lists[$k]['package_name'] = '批发版';
            }
        }
        $this->assign('lists',$lists);
        $this->assign('page',$show);
        $ui['qcloud_config'] = 'active';
        $this->assign('ui', $ui);
        $this->display('CloudManager:qcloud_config');
    }

    public function qcloud_info(){
        $center = M('cloud_product_list');
        $w = array();
        $w['status'] = '1';
        $id = I('id');
        $ui = array();
        if(empty($id)){
            $this->assign('act','insert');
            $ui['qcloud_info'] = 'active';
        }
        else{
            $this->assign('act','info');
            $ui['qcloud_info'] = 'active';
            $w3 = array();
            $w3['id'] =$id;
            $w3['status'] = 1;
            $w3['is_delete'] = 0;
            $info = $center->where($w3)->find();
            $this->assign('info',$info);
        }
        $ui['qcloud_info'] = 'active';
        $this->assign('ui',$ui);
        $this->display('CloudManager:qcloud_info');
    }

    public function qcloud_edit(){
        $act = $_POST['act'];
        $center = M('cloud_product_list');
        $operate = M('operate_center');
        $data = array();
        $operate_id = $_POST['operate_id'];
        $package_id = $_POST['package_id'];
        $cloud_product_id = $_POST['cloud_product_id'];
        $spec_name = $_POST['spec_name'];
        $market_price1 = (int)$_POST['market_price1'];
        $market_price2 = (int)$_POST['market_price2'];
        $market_price3 = (int)$_POST['market_price3'];
        $try_day = (int)$_POST['try_day'];

        if(empty($operate_id)){
            $this->error('营运商ID不能为空');
            die;
        }

        if(empty($package_id)){
            $this->error('套餐不能为空');
            die;
        }

        if(empty($cloud_product_id)){
            $this->error('商品ID不能为空');
            die;
        }

        if(empty($spec_name)){
            $this->error('规格名称不能为空');
            die;
        }

        if(empty($market_price)){
            $this->error('商品价格不能为空');
            die;
        }

        if($market_price1<0||$market_price2<0||$market_price3<0){
            $this->error('价格格式错误');
            die;
        }

        if($try_day<0){
            $this->error('试用天数格式错误');
            die;
        }
        $data['operate_id'] = $operate_id;
        $data['package_id'] = $package_id;
        $data['cloud_product_id'] = $cloud_product_id;
        $data['spec_name'] = $spec_name;
        $data['market_price'] = $market_price;
        $data['try_day'] = $try_day;
        $data['status'] = 1;

        $w = [];
        $w['id'] = $operate_id;
        $w['status'] = 1;
        $check1 = $operate->where($w)->find();
        if(empty($check1)){
            $this->error('运营商不存在');
            die();
        }
        if($act == 'insert'){
            $data['addtime'] = mktime();
            $data['edittime'] = mktime();
            $data['cloud_market_type'] = 1;
            $id = $center->add($data);
            if($id){
                $this->addAdminLog('3',"添加腾讯商品,商品ID：".$id);
                $this->success('添加成功',U('CloudManager/qcloud_config'));
            }else{
                $this->error('添加失败');
            }
        }
        else if($act == 'info'){
            $data['edittime'] = mktime();
            $id = $_POST['id'];
            $ids = $center->where(array('id'=>$id))->save($data);
            if($ids){
                $this->addAdminLog('3',"修改腾讯商品,商品ID：".$id);
                $this->success('修改成功',U('CloudManager/qcloud_config'));
            }else{
                $this->error('修改失败');
            }
        }
    }

    public function delete_info(){
        $center = M('cloud_product_list');
        $id = I('id');
        $w = [];
        $w['id'] = $id;
        $del = $center->where($w)->save(array('is_delete'=>1));
        if($del){
            $this->addAdminLog('3',"删除腾讯商品,商品ID：".$id);
            $this->success('删除成功',U('CloudManager/qcloud_config'));
        }else{
            $this->error('删除失败');
        }
    }
}