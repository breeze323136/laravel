# laravel
#常用的一些类库，基于Laravel框架中编写的，稍微修改可以符合任何一个框架对这些类的使用。

#<?php
/*
 * 发送邮件类的使用
 */
namespace Laravel\Controller;
use App\Libraries\Smtp;

class Demo{
	private function sendSmtp($send_flag){
        $smtpserver         =   'smtp.126.com';   //服务器
        $smtpserverport     =   25;		//端口号
        $smtpusermail       =   "453454343@126.com";   //发送人邮箱
        $smtpemailto        =   "2345435634@qq.com";	  //收件人邮箱
        $smtpuser           =   "453454343";			  //发送人邮箱除去邮箱后缀
        $smtppass           =   "fsdfshsdk234235";	      //密码
        $mailsubject        =   "自动监控异常警告";		  //标题
        $mailbody           =   $send_flag;			      //内容
        $mailtype           =   "txt";					  //格式  txt/html
        $smtp               =   new smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);
        $smtp->debug        =   false;					 //是否打开调试模式
        if($smtp->sendmail($smtpemailto,$smtpusermail,$mailsubject,$mailbody,$mailtype)=="1"){
            echo "已发送";
        }else{
            echo "发送失败";
        }
    }
}
#?>






#<?php
/*
 * 七牛上传类的使用
 * 使用例子
 */
 namespace Laravel\Controller;
 use App\Libraries\QiNiuUpload;
 class ChannelController extends Controller {
   public function anyIndex(){
      //二维码图片名
      $qrCodeImg      = 'DBH'.date('YmdHis').rand(000000,999999).'.png';
      $dirname        = $_SERVER['DOCUMENT_ROOT'].'/upload/img/';
      $qiniu          = new QiNiuUpload();
      $qiniu->fileUpload($qrCodeImg,$dirname);
   }
 }

#?>


#<?php
/*
 * 生成二维码类的使用
 */
namespace Laravel\Controller;
use App\Libraries\QRcode;   //生成验证码类
//use App\Libraries\QiNiuUpload;
class Demo{
	//二维码图片名
	$qrCodeImg      		= 'DBH'.date('YmdHis').rand(000000,999999).'.png';
	$dirname        		= $_SERVER['DOCUMENT_ROOT'].'/upload/img/';
	if(!is_dir($dirname)){
		mkdir($dirname, 0777, true);
	}
	//生成二维码图片
	$value      			= '二维码内容,一般使用url,跳到你指定的页面'; //二维码内容
	$errorCorrectionLevel   = 'L';//容错级别
	$matrixPointSize        = 10;//生成图片大小

	$qrcode     			= new QRcode();
	qrcode::png($value,$qiniuImgPath, $errorCorrectionLevel, $matrixPointSize, 2);

	//如果想上传到七牛，可以组合七牛类一起使用

	/*
		$qiniu      = new QiNiuUpload();
		$qiniu->fileUpload($qrCodeImg,$dirname);
	*/
}

#?>



#<?php
/*
 * 2、我们公司常用的分页，可使用于Web分页，接口分页等。只推荐在接口中的分页,Web使用laravel的即可
 * 我们仅作为一个方法来使用，如果项目涉及分页多你可以放在基类，如果一般，可以独立成类，如果比一般低点，需要用到分页的控制器加上此方法即可。
 * 也是入门级的东东，容易上手
 * 
 * 该方法可以在任何框架或者原生PHP中使用，需把  Input::get('length')等的接受参数信息替换成你的即可。
 * Input::get('length')  意思是   $_GET['length']
 */
use Laravel\Model\ChannelModel;
class Demo{
	public  function anyIndex(){
		$pageinfo  				= $this->pageinfo();   //调用分页方法
		$channelModel       	= new ChannelModel();  
		$channelListDatas   	= $channelModel->getChannelListData($shopId, $pageinfo->offset, $pageinfo->length);   //使用分页offse和length
	}

	private function pageinfo($length=20){
		$pageinfo               = new \stdClass;
		$pageinfo->length       = Input::has('length') ? Input::get('length') : $length;;
		$pageinfo->page         = Input::has('page') ? Input::get('page') : 1;
		$pageinfo->end_id       = Input::has('end_id') ? Input::get('end_id') : 0;
		$pageinfo->offset		= $pageinfo->page<=1 ? 0 : ($pageinfo->page-1) * $pageinfo->length;
		//$page->totalNum       = (int)Product::getInstance()->getPurchaseTotalNum();
		$pageinfo->totalNum     = 0;
		$pageinfo->totalPage    = ceil($pageinfo->totalNum/$pageinfo->length);

		return $pageinfo;
	}
}


class ChannelModel(){

	/*
	 * 我的渠道列表
	 */
	public function getChannelListData( $shopId, $offset, $length){   //分页

		return DB::table('shop_channel')->where('shop_id',$shopId)->orderBy('create_time','desc')->skip($offset)->take($length)->get();  //分页
	}
}

#?>









