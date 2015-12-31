<?php
namespace App\Libraries;
/*
 * PHP上传图片插件，主要用于ace的点击图片上传，适合前台，手机网页和后台的点击图片上传使用。
 * 最明显的使用案例是更换用户头像
 */
use App\Libraries\QiNiuUpload;
class ClickUpload{
	/*
	 * 上传并移动临时图片
	 */
	public $fil_size     = 5242890;                                        //图片最大值，字节计算

	public function validateAndSave($file) {

		$result = array();
		if(!preg_match('/^image\//' , $file['type'])
			//if file type is not an image
			|| !preg_match('/\.(jpe?g|gif|png)$/' , $file['name'])
			//or extension is not valid
			|| getimagesize($file['tmp_name']) === FALSE
			//or file info such as its size can't be determined, so probably an invalid image file
		)
		{
			//then there is an error
			$result['status']       = 'ERR';
			$result['message']      = 'Invalid file format!';
		}
		else if($file['size'] > 5242890) {
			//if size is larger than what we expect
			$result['status']       = 'ERR';
			$result['message']      = 'Please choose a smaller file!';
		}
		else if($file['error']      != 0 || !is_uploaded_file($file['tmp_name'])) {
			//if there is an unknown error or temporary uploaded file is not what we thought it was
			$result['status']       = 'ERR';
			$result['message']      = 'Unspecified error!';
		}
		else {
			//save file inside current directory using a safer version of its name
			$save_path  = $_SERVER['DOCUMENT_ROOT'].'/upload/header/'.$file['name'];
			//thumbnail name is like filename-thumb.jpg
			$rand = date('YmdHis').rand(00000,99999).'thumb.jpg';

			$dirname = $_SERVER['DOCUMENT_ROOT'].'/upload/header/';
			if(!is_dir($dirname)){
				mkdir($dirname, 0777, true);
			}
			$thumb_path = $dirname.$rand;

			if(
				//if we were not able to move the uploaded file from its temporary location to our desired path
				!move_uploaded_file($file['tmp_name'] , $save_path)
				OR
				//or unable to resize image to our desired size
				!$this->resize($save_path, $thumb_path, 150)
			)
			{
				$result['status']   = 'ERR';
				$result['message']  = 'Unable to save file!';
			}
			else {

				//七牛上传
				$qiniu              = new QiNiuUpload();
				$qiniu->fileUpload($rand,$dirname);
					//everything seems OK
				$result['status']   = 'OK';
				$result['message']  = 'Avatar changed successfully!';
				//include new thumbnails `url` in our result and send to browser
				$result['url']      = 'http://7xlay6.com1.z0.glb.clouddn.com/'.$rand;
//				$result['url']      = '/upload/header/'.$rand;

				return $result;
			}
		}

		return $result;
	}



	/*
	 * 压缩图像 jpeg/png/jif
	 */
	private function resize($in_file, $out_file, $new_width, $new_height=FALSE)
	{
		$image         = null;
		$extension = strtolower(preg_replace('/^.*\./', '', $in_file));
		switch($extension)
		{
			case 'jpg':
			case 'jpeg':
				$image = imagecreatefromjpeg($in_file);
				break;
			case 'png':
				$image = imagecreatefrompng($in_file);
				break;
			case 'gif':
				$image = imagecreatefromgif($in_file);
				break;
		}
		if(!$image || !is_resource($image)) return false;


		$width = imagesx($image);
		$height = imagesy($image);
		if($new_height === FALSE)
		{
			$new_height = (int)(($height * $new_width) / $width);
		}


		$new_image      = imagecreatetruecolor($new_width, $new_height);
		imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

		$ret            = imagejpeg($new_image, $out_file, 80);

		imagedestroy($new_image);
		imagedestroy($image);

		return $ret;
	}
}