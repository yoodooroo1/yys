<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Think\Upload\Driver;
class Local{
    /**
     * 上传文件根目录
     * @var string
     */
    private $rootPath;

    /**
     * 本地上传错误信息
     * @var string
     */
    private $error = ''; //上传错误信息

    /**
     * 构造函数，用于设置上传根路径
     */
    public function __construct($config = null){

    }

    /**
     * 检测上传根目录
     * @param string $rootpath   根目录
     * @return boolean true-检测通过，false-检测失败
     */
    public function checkRootPath($rootpath){
        if(!(is_dir($rootpath) && is_writable($rootpath))){
            $this->error = '上传根目录不存在！请尝试手动创建:'.$rootpath;
            return false;
        }
        $this->rootPath = $rootpath;
        return true;
    }

    /**
     * 检测上传目录
     * @param  string $savepath 上传目录
     * @return boolean          检测结果，true-通过，false-失败
     */
    public function checkSavePath($savepath){
        /* 检测并创建目录 */
        if (!$this->mkdir($savepath)) {
            return false;
        } else {
            /* 检测目录是否可写 */
            if (!is_writable($this->rootPath . $savepath)) {
                $this->error = '上传目录 ' . $savepath . ' 不可写！';
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * 压缩图片
     */
    function ResizeImage($uploadfile,$maxsize,$picsize,$name,$type='')
    {
        //取得当前图片大小
        imagesavealpha($uploadfile,true);
        $width = imagesx($uploadfile);
        $height = imagesy($uploadfile);
        $i=1;
        //生成缩略图的大小
        if($maxsize<$picsize)
        {
            if($width > $height){
                $newwidth = 0.5*1024;
                $newheight =$height*(0.5*1024/$width);
            }
            else{
                $newheight = 0.5*1024;
                $newwidth =$width*(0.5*1024/$height);
            }
            /* $i = ceil(sqrt(($maxsize/$picsize))*100)/100;
            $widthratio = $maxwidth/$width;
            $heightratio = $maxheight/$height;
            if($widthratio < $heightratio)
            {
                $ratio = $widthratio;
            }
            else
            {
                $ratio = $heightratio;
            }
            //$newwidth = $width * $ratio;
            //$newheight = $height * $ratio;
            $newwidth = $width * $i;
            $newheight = $height * $i;*/
            if(function_exists("imagecopyresampled"))
            {
                $uploaddir_resize = imagecreatetruecolor($newwidth, $newheight);
                imagealphablending($uploaddir_resize,false);
                imagesavealpha($uploaddir_resize,true);
                imagecopyresampled($uploaddir_resize, $uploadfile, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            }
            else
            {
                $uploaddir_resize = imagecreate($newwidth, $newheight);
                imagealphablending($uploaddir_resize,false);
                imagesavealpha($uploaddir_resize,true);
                imagecopyresized($uploaddir_resize, $uploadfile, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            }
            if($type=='png')
            {
                imagepng($uploaddir_resize,$name);
            }
            elseif($type == 'gif')
            {
                imagegif($uploaddir_resize,$name);
            }
            else
            {
                ImageJpeg ($uploaddir_resize,$name);
            }

            ImageDestroy ($uploaddir_resize);
        }
        else
        {
            if($type=='png')
            {
                imagepng($uploadfile,$name);
            }
            elseif($type == 'gif')
            {
                imagegif($uploadfile,$name);
            }
            else
            {
                ImageJpeg ($uploadfile,$name);
            }

        }
    }

    /**
     * 保存指定文件
     * @param  array   $file    保存的文件信息
     * @param  boolean $replace 同名文件是否覆盖
     * @return boolean          保存状态，true-成功，false-失败
     */
    public function save($file, $replace=true) {
        $filename = $this->rootPath . $file['savepath'] . $file['savename'];
        $maxsize = 0.25*1024*1024;
        $picsize = $file['size'];
        $type='jpg';
        /* 不覆盖同名文件 */
        if (!$replace && is_file($filename)) {
            $this->error = '存在同名文件' . $file['savename'];
            return false;
        }
        if($file['type'] == "image/pjpeg" || $file['type'] == "image/jpg" || $file['type'] == "image/jpeg" )
        {
            $im = imagecreatefromjpeg($file['tmp_name']);
            file_put_contents('tups',$im);
            //$im = imagecreatefromjpeg($uploadfile);
        }
        elseif($file['type'] == "image/x-png" || $file['type'] =="image/png")
        {
            $im = imagecreatefrompng($file['tmp_name']);
            $type='png';
            //$im = imagecreatefromjpeg($uploadfile);
        }
        elseif($file['type'] == "image/gif")
        {
            $im = imagecreatefromgif($file['tmp_name']);
            $type='gif';
            //$im = imagecreatefromjpeg($uploadfile);
        }
        elseif($file['type'] == "application/octet-stream"){
            if($file['ext'] == 'png'){
                header( "Content-type: image/png");
                $type='png';
            }elseif($file['ext'] == 'gif'){
                $type = 'gif';
                header( "Content-type: image/gif");
            }else{
                $type = 'jpg';
                header( "Content-type: image/jpeg");
            }
            $picturedata = file_get_contents($file['tmp_name']);
            $im = imagecreatefromstring($picturedata);
        }
        else//默认jpg
        {
            $im = imagecreatefromjpeg($file['tmp_name']);
        }
        if($im)
        {
            $this->ResizeImage($im,$maxsize,$picsize,$filename,$type);
            ImageDestroy ($im);
            return true;
        }
        else{
            if (!move_uploaded_file($file['tmp_name'], $filename)) {
                $this->error = '文件上传保存错误！';
                return false;
            }else{
                return true;
            }
        }
        /* 移动文件 */
        /*  if (!move_uploaded_file($file['tmp_name'], $filename)) {
             $this->error = '文件上传保存错误！';
             return false;
         } */

    }

    /**
     * 创建目录
     * @param  string $savepath 要创建的穆里
     * @return boolean          创建状态，true-成功，false-失败
     */
    public function mkdir($savepath){
        $dir = $this->rootPath . $savepath;
        if(is_dir($dir)){
            return true;
        }

        if(mkdir($dir, 0777, true)){
            return true;
        } else {
            $this->error = "目录 {$savepath} 创建失败！";
            return false;
        }
    }

    /**
     * 获取最后一次上传错误信息
     * @return string 错误信息
     */
    public function getError(){
        return $this->error;
    }

}
