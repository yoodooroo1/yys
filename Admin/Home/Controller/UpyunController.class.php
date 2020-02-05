<?php
namespace Home\Controller;
use Think\Controller;
/**
 * XUNXIN PC 后台管理 商店管理
 * ============================================================================
 * 版权所有 2005-2010 厦门微聚点科技有限公司，并保留所有权利。
 * 网站地址: http://www.vjudian.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: youyan $
 * $Id: UpyunController.class.php
 */
class UpyunController extends AdminController
{
	public function _initialize()
	{
		parent::_initialize();
		$this->upload_type = C('upload_type') ? C('upload_type') : 'local';
		$this->siteUrl = $this->siteUrl ? $this->siteUrl : C('site_url');
	}
    
	public function kindedtiropic()
	{		
        $return = $this->localUpload('');

        if ($return['error']) {
            $this->alert($return['msg']);
        }
        else {
            header('Content-type: text/html; charset=UTF-8');
            echo json_encode(array('error' => 0, 'url' => $return['msg']));
            exit();
        }
	}

	public function localUpload($filetypes = '')
	{
        //import("Org.Util.UploadFile");
		$upload = new \Org\Util\UploadFile();
		$upload->maxSize = intval(C('up_size')) * 1024;

		if (!$filetypes) {
			$upload->allowExts = explode(',', C('up_exts'));
		}
		else {
			$upload->allowExts = $filetypes;
		}

		$upload->autoSub = 1;
		if (isset($_POST['width']) && intval($_POST['width'])) {
			$upload->thumb = true;
			$upload->thumbMaxWidth = $_POST['width'];
			$upload->thumbMaxHeight = $_POST['height'];
			$thumb = 1;
		}

		$upload->thumbRemoveOrigin = true;
		$upload->savePath = './Uploads/';  
		if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads') || !is_dir($_SERVER['DOCUMENT_ROOT'] . '/uploads')) {
			mkdir($_SERVER['DOCUMENT_ROOT'] . '/uploads', 511);
		}

		$firstLetterDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/zixun';
		if (!file_exists($firstLetterDir) || !is_dir($firstLetterDir)) {
			mkdir($firstLetterDir, 511);
		}

		$upload->hashLevel = 4;

		if (!$upload->upload()) {
			$error = 1;
			$msg = $upload->getErrorMsg();
		}
		else {
			$error = 0;  
			$info = $upload->getUploadFileInfo();
			//$this->siteUrl = $this->siteUrl ? $this->siteUrl : C('site_url');
			$this->siteUrl = ADMIN_URL .'/';
			if ($thumb == 1) {
				$paths = explode('/', $info[0]['savename']);
				$fileName = $paths[count($paths) - 1];
				$msg = $this->siteUrl . substr($upload->savePath, 1) . str_replace($fileName, 'thumb_' . $fileName, $info[0]['savename']);
			}
			else {
				$msg = $this->siteUrl . substr($upload->savePath, 1) . $info[0]['savename'];
			}
		}

		if ( $_GET['imgfrom'] == 'photo_list') {
			echo $msg;
			exit();
		}
		else {
			return array('error' => $error, 'msg' => $msg);
		}
	}

	public function alert($msg)
	{
		header('Content-type: text/html; charset=UTF-8');
		echo json_encode(array('error' => 1, 'message' => $msg));
		exit();
	}

	private function _mkdir($dirname)
	{
		if (!file_exists($dirname) || !is_dir($dirname)) {
			mkdir($dirname, 511, true);
		}
	} 
}

?>
