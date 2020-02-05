<?php
/**
 * design by yy
 * 我的佣金类
 * 获得佣金记录
 * 佣金提现
 * 我的佣金
 */
namespace Home\Controller;
use Think\Controller;

class FundController extends Controller
{



/**
 * 初始化安全检查 如果ssession 存在说明是本网站管理员访问
 * 如果不存在检查URL
 */
    protected function _initialize()
    {

        if(!session('adminUser') || !session('adminUserid') || !session('adminRole') || session('adminRole')=='' )
		{
		    if(!session('url_checked'))
		    {
		     $this->checkURL();
		     $this->setSession();
		    }

		}
		else
		{
		    $this->user_id = session('adminUserid');
		    $this->user_role = session('adminRole');
		    $this->user_from = session('adminFrom');
		    if(IS_POST){
		        session('fund_from',$_POST['from']);
                session('fund_type',$_POST['type'] );
                session('fund_user_name',$_POST['user_name']);
                session('fund_mall_name',$_POST['mall_name']);

		    }else{

		        //调用所有的佣金列表
		    }
		}

    }

    /**
     * 默认方法我的佣金
     */
    public function index ()
    {
      $data=$this->getMember();
      if(empty($data)||$data===false)
      {
          die("参数出错");
      }

      $this->assign('fund_data',$data);
      $this->display('fund_list');
    }



    /**
     * 佣金提现
     */
    public function getMoney ()
    {
        //群名去实现
    }

    /**
     * 佣金提现记录
     */
    public function moneyRecord ()
    {
        //传递参数。。。


        $account_list=M("prize3_pv");
        $where=array();
        $count      = $account_list->where($where)->count();// 查询满足要求的总记录数
        $Page       = new \Think\Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出
        $list = $account_list->where($where)->order('add_time')->limit($Page->firstRow.','.$Page->listRows)->select();
        $ui['member_apply'] = 'active';
        $this->assign('ui',$ui);
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->display("fund_record"); //输出模板

    }

    /**
     * 获得跳转过来的URL
     * 如果URL错则返回false
     * 并且解析URL
     * 返回解析后的URL 数组
     */
    private function getBeforeURL ()
    {
        $url = $_SERVER['HTTP_REFERER'];
        if (empty($url))
        {
            return false;
        }
        $urlarr = parse_url($url);
        return $urlarr;
    }

    /**
     * 检查跳转过来的URL
     */
    private function checkURL ()
    {
        $url_arr = $this->getBeforeURL();
        if ($url_arr === false)
        {
            $this->goLogin();
        }
        else
        {
            $port=  trim($url_arr['port']);
            $host = trim($url_arr['host']);
            $path = trim($url_arr['path']);
            $url = strtolower($port.$host.$path);


                $url_list = $this->getURLs();
                if (in_array($url, $url_list))
                {
                    $this->succ();
                }
                else
                {

                    $this->goLogin();
                }

        }
    }

    /**
     * 获取数据库中允许跳转的URL
     * 返回数组
     */
    private function getURLs ()
    {
        $url_list = array();
        $Urls = M('Url');
        $data = $Urls->select();
        $len = count($data);
        for ($i = 0; $i < $len; $i ++)
        {
            $url = $data[$i]['url'];
            if (! empty($url))
            {
                $url=strtolower($url);
                $url_list[] = trim($url);
            }
        }

        return $url_list;
    }

    /**
     * 获得跳转过来的成员信息并且设置session
     */
    private function setSession ()
    {
        if(IS_GET){
           session('fund_type',$_GET['type'] );
           session('fund_from',$_GET['from']);
           session('fund_user_name',$_GET['user_name']);
           session('fund_mall_name',$_GET['mall_name']);
        }

    }
    /**
     * 获得一条数据库中的数据
     */
    private function getMember()
    {
        $Peitao=M('Peitao');
        $where=array();
        $where['from']=session('fund_from');
        $where['pt_type']=session('fund_type');
        $where['user_name']=session('fund_user_name');
        $where['mall_name']=session('fund_mall_name');
        $data=$Peitao->field(true)->where($where)->find();
        return $data;

    }

    /**
     * 获得所有的数据
     */
    private function getAllMember(){
        $Peitao=M('Peitao');
        $data=$Peitao->field(true)->select();
        return $data;
    }
    /**
     * URL 出错 跳转到登入界面
     */
    private function goLogin ()
    {
        header('Location: ' . './admin.php?c=Auth&a=login');
        exit;
    }
    /**
     * URL 成功 并且设置URL检查过了
     */
    private function succ()
    {
        session('url_checked',true);

    }
}

?>