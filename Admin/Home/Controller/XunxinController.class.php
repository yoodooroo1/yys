<?php
namespace Home\Controller;
use Think\Controller;
/**
 * XUNXIN PC 对外接口查询文件
 * ============================================================================
 * 版权所有 2005-2010 厦门微聚点科技有限公司，并保留所有权利。
 * 网站地址: http://www.vjudian.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: youyan $
 * $Id: XunxinController.class.php
 */


class XunxinController extends Controller
{

    private $store_id;

    /**
     * 初始化方法
     * 判断有没有获得store_id
     * 如果没获得store_id 看看有没有获得store_name
     * 如果没有则返回无效参数
     */
    protected function _initialize()
    {
        if (C("SHOW_PAGE_TRACE")) {
            C("SHOW_PAGE_TRACE", false);
        }
        header("Content-Type:text/html;Charset=utf-8");
        if (IS_GET) {
            
            $id = $_GET['shop_id'];
            $name = $_GET['shop_name'];
            if (empty($id)) {
                if (! empty($name)) {
                    // $Store = M('Store');
                    $Store = $this->getM('Store');
                    
                    $where = array();
                    $where['store_name'] = $name;
                    $stores = $Store->where($where)->find();
                    $this->store_id = $stores['store_id'];
                    if (empty($this->store_id)) {
                        die("NO STORE");
                    }
                } else {
                    die("无效参数");
                }
            } else {
                $this->store_id = intval($id);
            }
        } else {
            
            die("无效参数");
        }
    }

    /**
     * 查询所有商品
     */
    public function goods()
    {
        $Goods = M('Goods');
        $where = array();
        $where['store_id'] = $this->store_id;
        $where['isdelete'] = 0;
        $count = $Goods->where($where)->count(); // 查询满足要求的总记录数
        $Page = new \Think\Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show(); // 分页显示输出
        $list = $Goods->where($where)
            ->order('goods_id')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display("goods_list"); // 输出模板
    }

    /**
     * 查询所有商品
     */
    public function good()
    {
        
        // $Goods = M('Goods');
        $Goods = $this->getM('Goods');
        $where = array();
        $where['store_id'] = $this->store_id;
        $where['isdelete'] = 0;
        if (! empty($_GET['key'])) {
            $where['goods_name'] = array(
                'like',
                '%' . $_GET['key'] . '%'
            );
        }
        if (! empty($_GET['price'])) {
            $where['goods_price'] = $_GET['price'];
        }
        $list = $Goods->where($where)
            ->order('goods_id')
            ->select();
        if (empty($list)) {
            echo "NO GOODS";
            return;
        }
        echo "以下是商店的ID为" . $this->store_id . "的所有商品" . "<hr>";
        for ($i = 0; $i < count($list); $i ++) {
            $goods = $list[$i];
            $this->print_goods($goods);
        }
    }

    /**
     * 输出商品信息
     * 
     * @param unknown $goods            
     */
    private function print_goods($goods)
    {
        $images = json_decode($goods['goods_figure']);
        echo "<hr>";
        echo "商品ID: " . $goods['goods_id'] . "<br>";
        echo "商品名称: " . $goods['goods_name'] . "<br>";
        echo "商品描述: " . $goods['goods_desc'] . "<br>";
        echo "商品价格: " . $goods['goods_price'] . "<br>";
        // echo "商品主图: ".$images[0]->url."<br>";
        for ($i = 0; $i < count($images); $i ++) {
            echo "商品主图: " . $images[$i]->url . "<br>";
        }
        echo "<hr>";
    }

    /**
     * 获得模型
     * 先判断是否是测试环境
     * 最后返回正式环境的数据库模型
     * 
     * @param unknown $model            
     */
    private function getM($model)
    {
        $istest = C('IS_TEST');
        if ($istest) {
            $pre = C('DB_PREFIX');
            $DNS = C('DNS');
            return M($model, $pre, $DNS);
        } else {
            return M($model);
        }
    }
}

?>


