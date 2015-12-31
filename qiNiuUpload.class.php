<?php
/*
 * 七牛上传，从服务器层(控制器或模型)上传使用类
 * 独立配置信息：laravel框架中的使用：confing/app.php中配置
 * author: liangfeng@shinc.net
 * from  : http://www.sexyphp.com
 */
 
namespace App\Libraries;
use App\Libraries\Qiniu\Auth;		//引入七牛
use Illuminate\Support\Facades\Config;   //引入配置文件
use App\Libraries\Qiniu\Storage\UploadManager;   //引入上传类
use Illuminate\Support\Facades\Response;

class QiNiuUpload{
	/*
	 * 上传操作
	 * $key         上传到七牛后保存的文件名(文件名)
	 * $filePath    要上传文件的本地路径(绝对路径)
	 */
	public function fileUpload($key,$dirname){

		// 要上传的空间
		$bucket             = Config::get('app.qiniu_backup2');
		$qiniuToken         = $this->qiniuToken($bucket);

		$token              = $qiniuToken->getData();
		// 初始化 UploadManager 对象并进行文件的上传。
		$uploadMgr          = new UploadManager();
		$uploadStatus       = $uploadMgr->putFile($token->uptoken, $key, $dirname.$key);

		//回删本地存储文件，节省空间
		if(!empty($uploadStatus[0])){
			$this->deldir($dirname);
		}

		return $uploadStatus;
	}

	/*
	 * 获取qiniu上传图片的token
	 */
	private function qiniuToken($image){

		$accessKey	= Config::get('app.qiniu_accessKey');
		$secretKey	= Config::get('app.qiniu_secretKey');
		$expires	  = Config::get('app.qiniu_expires');

		$qiniu	= new Auth($accessKey , $secretKey);

		$data['uptoken'] = $qiniu->uploadToken($image , '' , $expires);

		return Response::json($data);

	}

	/*
	 * 删除目录下的所有文件
	 */
	private function deldir($dir)
	{
		$dh = opendir($dir);
		while ($file = readdir($dh)) {
			if ($file != "." && $file != "..") {
				$fullpath = $dir . "/" . $file;
				if (!is_dir($fullpath)) {
					unlink($fullpath);
				} else {
					self::deldir($fullpath);
				}
			}
		}
	}
}


 
