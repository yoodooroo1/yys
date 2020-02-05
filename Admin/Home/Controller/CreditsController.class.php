<?php
namespace Home\Controller;
use Think\Controller;
/**
 * XUNXIN PC 后台管理 积分管理
 * ============================================================================
 * 版权所有 2005-2010 厦门微聚点科技有限公司，并保留所有权利。
 * 网站地址: http://www.vjudian.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: youyan $
 * $Id: CreditsController.class.php
 */
class CreditsController extends AdminController
{
    
    /**
     * 获得积分的列表
     *
     * @param unknown $condition            
     */
    public function credits_list($condition = array())
    {
        $Credits = M('Mb_credits');
        $where = array();
        $where['storeid'] = session('store_id');
        $where['isdelete'] = 0;
        // 补充条件
        $where = $this->getOtherWhere($where, $condition);
        // 查询条件 默认显示未接单的
        if (IS_POST) {
            if (! empty($_POST['key_name'])) {
                $where['member_name'] = array(  
                    'like',
                    '%' . $_POST['key_name'] . '%'
                );
            }
            if (! empty($_POST['key_type'])) {
                $num = intval($_POST['key_type']);
                if($num>0&&$num<9){ 
                 $where['tid'] = $num;
                }
    
            }
        }
        
        $count = $Credits->where($where)->count(); // 查询满足要求的总记录数
        $Page = new \Think\Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show(); // 分页显示输出
        $list = $Credits->where($where)
            ->order('create_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $ui['credits_list'] = 'active';
        
        $this->assign('ui', $ui);
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display("Credits:credits_list"); // 输出模板
    }

    /**
     * 删除积分明细
     */
    public function credits_delete()
    {
        if (IS_GET) {
            $credits_id = $_GET['credits_id'];
            $Credits = M('Mb_credits');
            $where = array();
            $where['credits_id'] = $credits_id;
            $where['isdelete'] = 0;
            $data = array();
            $data['isdelete'] = 1;
            $num = $Credits->where($where)
                ->data($data)
                ->save();
            if ($num) {
                $this->success("删除明细成功");
            } else {
                $this->error("删除明细失败");
            }
        } else {
            $this->error("invalid params");
        }
    }

    /**
     * 积分兑换列表
     */
    public function credits_present_list($condition = array())
    {
        $Credits_Present = M('Mb_exchange');
        $where = array();
        $where['store_id'] = session('store_id');
        $where['sisdelete'] = 0;
        // 补充条件
        $where = $this->getOtherWhere($where, $condition);
        // 查询条件 默认显示未接单的
        if (IS_POST) {
            if (! empty($_POST['key_name'])) {
                $where['member_name'] = array(
                    'like',
                    '%' . $_POST['key_name'] . '%'
                );
				$this->assign('key_name',$_POST['key_name']);  
            }  
            if (! empty($_POST['key_type'])) {
                $num = intval($_POST['key_type']);
               if($num <4&&$num>=1){
                $where['exchange_type'] = $num-1;
				$this->assign('key_type',$_POST[key_type]);  
                }    
                
            }
			if(!empty($_POST['Time1']) && empty($_POST['Time2']))
			{
				$t = strtotime($_POST['Time1'])+24*60*60 ;
				$where['_string'] = "exchange_time >= ". strtotime($_POST['Time1']) ."&&exchange_time < ".$t;
			}   
			if(!empty($_POST['Time2']) && empty($_POST['Time1']))
			{
				$where['_string'] = "exchange_time <= ". strtotime($_POST['Time2']);
			}
			if(!empty($_POST['Time2']) && !empty($_POST['Time1']))
			{ 
				$t = strtotime($_POST['Time2'])+24*60*60 ;
				$where['_string'] = "exchange_time >= ". strtotime($_POST['Time1']) ."&&exchange_time < " .$t;
			}
        }  
        $this->assign('Time1',$_POST['Time1']);  
        $this->assign('Time2',$_POST['Time2']);    
        $count = $Credits_Present->where($where)->count(); // 查询满足要求的总记录数
        $Page = new \Think\Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $Page->show(); // 分页显示输出
        $list = $Credits_Present->where($where)
            ->order('exchange_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();     
        foreach ($list as $k => $v) {
         
		       $list[$k]['out_of_day']=date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s",$v['exchange_time']).'+'.$v['day'].' days'));
			   $out_of_day = $v['exchange_time']+$v['day']*24*3600;
			   $list[$k]['outofday'] = $out_of_day;
                if ($v['exchange_type'] == 2) {
                    $list[$k]['finish_time_desc'] = '自动发放';
                } elseif($v['exchange_type'] == '0') {
					if($out_of_day <mktime())
					{  
						$list[$k]['finish_time_desc'] = '已过期';
					}
					else  
					{ 
						$list[$k]['finish_time_desc'] = '未完成';
					}
                   
                }  
            else {
                $list[$k]['finish_time_desc'] = date('m-d H:i:s', $v['finished_time']);
	        }
  
   
	    if($v['type']%2==0){
                $list[$k]['gain_type_desc']='积分兑换';
            }else{
                $list[$k]['gain_type_desc']='摇摇中奖';
	    }

	    
        }
        
        $ui['credits_present_list'] = 'active';
        
        $this->assign('ui', $ui);
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display("Credits:credits_present_list"); // 输出模板
    }

    /**
     * 积分兑换删除
     */
    public function credits_present_delete()
    {
        if (IS_GET) {
            $exchange_id = $_GET['exchange_id'];
            $Credits_Present = M('Mb_exchange');
            $where = array();
            $where['exchange_id'] = $exchange_id;
            $where['sisdelete'] = 0;
            $data = array();
            $data['sisdelete'] = 1;
            $num = $Credits_Present->where($where)->save($data);
            if ($num) {   
                $this->success("删除兑换明细成功");  
            } else {
                $this->error("删除兑换明细失败".$num);
            }   
        } else {
            $this->error("invalid params");
        }
    }

    /**
     * 积分兑换发放
     */
    public function credits_present_give($exchange_id,$exchange_code)
    {
		$Credits_Present = M('Mb_exchange');
		$where = array();
		$where['exchange_id'] = $exchange_id;
		
		$info = $Credits_Present->where($where)->find();
		$info['exchange_type'] = 1;
		$op = "update_exchange";
		$param = array(
		"act" => 'exchange',
		"op" => $op,
		"exchange" => json_encode($info,JSON_UNESCAPED_UNICODE)
		);
          
        $returnInfo = $this->getReturnInfo($param);
		if($returnInfo['result']=='0')
		{
			$this->success($returnInfo['data']);
		} 
        else
		{
			$this->error($returnInfo['data']);
		}			
		
            /* $code = $exchange_code;
            $where = array();
            $where['exchange_id'] = $exchange_id;
           // die($exchange_id.session('store_id'));    
            $where['store_id'] = session('store_id');
            $where['sisdelete'] = 0;  
            $where['exchange_type'] = 0;  
			
            $Credits_Present = M('Mb_exchange');
            $info = $Credits_Present->where($where)->find();
            if (empty($info)) {
                $this->error("该奖品已发放完毕");
            }  
			else 
			{
                if ($info['exchange_code'] != $code) {
                    $this->error("兑换码出错");
					exit;
                }
				$out_of_day =$info['exchange_time']+$info['day']*3600*24;
				if($out_of_day<mktime())  
				{   
					 $this->error("该奖品已过期".$out_of_day);
					exit;
				}    
                $data = array();
                $data['exchange_type'] = 1;
                $data['finished_time'] = mktime(); 
                $num = $Credits_Present->where($where)->save($data);
                if ($num) {
                    $this->success("兑换成功");
                } else {
                    $this->error("服务器 数据出错...");
                }
            }   */
     
    }
	
	/*兑奖纪录导出EXCEL格式*/
	public function download_win_list()
	{         
		$Credits_Present = M('Mb_exchange');
        $where = array();
        $where['store_id'] = session('store_id');
        $where['sisdelete'] = 0; 
		if (! empty($_GET['key_name'])) {
			$where['member_name'] = array(
				'like',
				'%' . $_GET['key_name'] . '%'
			);
		}  
		if (! empty($_GET['key_type'])) {
			$num = intval($_GET['key_type']);
		    if($num <4&&$num>=1){
				$where['exchange_type'] = $num-1;  
			}    
		}  
		if(!empty($_GET['Time1']) && empty($_GET['Time2']))
		{
			$t = strtotime($_GET['Time1'])+24*60*60 ;
			$where['_string'] = "exchange_time >= ". strtotime($_GET['Time1']) ."&&exchange_time < ".$t;
		}
		if(!empty($_GET['Time2']) && empty($_GET['Time1']))
		{
			$where['_string'] = "exchange_time <= ". strtotime($_GET['Time2']);
		}
		if(!empty($_GET['Time2']) && !empty($_GET['Time1']))
		{ 
			$t = strtotime($_GET['Time2'])+24*60*60 ;
			$where['_string'] = "exchange_time >= ". strtotime($_GET['Time1']) ."&&exchange_time < " .$t;
		} 
		$lists = $Credits_Present->where($where)
            ->order('exchange_time desc')
            ->select(); 
		$data = array();
		foreach($lists as $k=>$list)
		{
			$data[$k]['username'] = empty($list['member_name']) ? '--' : $list['member_name'];
			$data[$k]['present_name'] =$list['present_name'];
			$data[$k]['exchange_time'] =empty($list['exchange_time'])? '' : date('y-m-d H:i',$list['exchange_time']);
			$data[$k]['out_of_day'] = date("y-m-d H:i",strtotime(date("Y-m-d H:i:s",$list['exchange_time']).'+'.$list['day'].' days'));  
			$out_of_day = $list['exchange_time']+$list['day']*24*3600;
			$data[$k]['finish_time_desc'] = ($list['exchange_type'] == '2') ? '自动发放' : (($list['exchange_type'] == '0') ? ($out_of_day <mktime() ? '已过期' : '未发放'  ): date('y-m-d H:i', $list['finished_time']));
			$data[$k]['exchange_code'] = $list['exchange_code'];
			$data[$k]['gain_type_desc'] = ($list['type']%2 == '0') ? '积分兑换' :'摇摇中奖' ;
		}  	  
		vendor("PHPExcel.PHPExcel");  

		$cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip;  
		$cacheSettings = array();     
		\PHPExcel_Settings::setCacheStorageMethod($cacheMethod,$cacheSettings);  
  	   
		$excel = new \PHPExcel();  
		$excel->getProperties()->setTitle("中奖纪录");
		$letter = array('A','B','C','D','E','F','G');  
		//表头数组
		$tableheader = array('账号','奖/礼品名称','兑换/中奖日期','过期时间','发放时间','奖/礼品码','获取方式');
		
		//填充表头信息 
		for($i = 0;$i < count($tableheader);$i++) {
		$excel->getActiveSheet()->setCellValue("$letter[$i]1","$tableheader[$i]");
		}  
		
		for ($i = 2;$i <= count($data) + 1;$i++) {  
			$j = 0;      
			foreach ($data[$i - 2] as $key=>$value) {
			$excel->getActiveSheet()->setCellValue("$letter[$j]$i",''."$value");
			$excel->getActiveSheet()->getStyle("$letter[$j]$i")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$j++;   
			}    
		}       
		$excel->getActiveSheet()->getColumnDimension('A')->setWidth(25);  
		$excel->getActiveSheet()->getColumnDimension('B')->setWidth(30);    
		$excel->getActiveSheet()->getColumnDimension('C')->setWidth(20); 	
		$excel->getActiveSheet()->getColumnDimension('D')->setWidth(20); 
		$excel->getActiveSheet()->getColumnDimension('E')->setWidth(20);       
		$excel->getActiveSheet()->getColumnDimension('F')->setWidth(40);  	  
		$excel->getActiveSheet()->getColumnDimension('G')->setWidth(20);  	  
		$excel->getActiveSheet()->getStyle('A1:G1')->getFont()->setBold(true);
		$excel->getActiveSheet()->getStyle('A1:G1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
		   
		$name = '中奖纪录.xls';                  
		$write = new \PHPExcel_Writer_Excel5($excel);
		ob_end_clean();//清除缓冲区,避免乱码
		header("Pragma: public");     
		header("Expires: 0");  
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");;
		header('Content-Disposition:attachment;filename='.$name);
		header("Content-Transfer-Encoding:binary");
		$write->save('php://output');		  
	}
	
}

?>
