<?php
/* *
 * 功能：移动微信预支付操作
 * 版本：V3
 * 创建日期：2015-11-18
 * 作者：liangfeng@shinc.net
 * 说明：
 * 微信手机APP支付类型的预支付实现，与支付宝的业务逻辑不同，微信是需要在web服务器端实现预付款回传给APP端
 * APP根据服务器回传的内容去实现微信支付，然后再通过微信回调通知给web服务器端。
 *
 * 先生成一笔订单，然后根据回调通知来更新这笔订单是否已付款。
 *
 */


namespace  Laravel\Controller\Pay; //定义命名空间

use  ApiController;  //引入接口公共父类，用于继承
use  Illuminate\Support\Facades\View; //引入视图类
use  Illuminate\Support\Facades\Input;//引入参数类
use  App\Libraries\WxPayApi;
use  App\Libraries\WxPayConfig;
use  App\Libraries\WxPayUnifiedOrder;
use  App\Libraries\WxPayDataBase;
use  Illuminate\Support\Facades\Log;
use  Illuminate\Support\Facades\Response;

class  WeixinapiController extends  ApiController {

    protected $nowTime;
    public function  __construct() {
        parent::__construct();
        $this->nowTime = date('Y-m-d H:i:s');
    }

    /*
     * 微信预支付接口
     */
    public function anyWxPay(){

        if( Input::has('user_id') && Input::has('out_trade_no') && Input::has('goods_name')  && Input::has('total_fee') ){
            $outTradeNo =   Input::get('out_trade_no');
            $goodsName  =   Input::get('goods_name');
            if(Input::get('total_fee') <=1 ){
                $totalFee   = 100;

            }else{
                $totalFee   = (int)Input::get('total_fee') * 100;
            }

        }else{
            Log::error(var_export('参数错误', true), array(__CLASS__));
            return Response::json( $this->response( '10005' ) );
        }

        //②、统一下单   请求微信预下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody($goodsName);
        $input->SetOut_trade_no($outTradeNo);
        $input->SetTotal_fee($totalFee);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 7200));
        $input->SetGoods_tag("test_goods");
        $input->SetNotify_url(Input::root()."/callback/weixin/callback");
        $input->SetTrade_type("APP");
        //浏览器测试记得注释掉   $inputObj->SetSpbill_create_ip("1.1.1.1");
        $order = WxPayApi::unifiedOrder($input);
        if(array_key_exists('err_code',$order)){
            return Response::json( $this->response( '0',$order['err_code'],$order['err_code_des']) );
        }
        if($order['return_code']=='SUCCESS'){

            $timestamp = time();
            //参与签名的字段 无需修改  预支付后的返回值
            $arr = array();
            $arr['appid'] = trim(WxPayConfig::APPID);
            $arr['partnerid'] = trim(WxPayConfig::MCHID);
            $arr['prepayid'] = $order['prepay_id'];
            $arr['package'] = 'Sign=WXPay';
            $arr['noncestr'] = $order['nonce_str'];
            $arr['timestamp'] = $timestamp;
            $obj = new WxPayDataBase();
            $obj->SetValues($arr);
            $sign = $obj->SetSign();

            //返回给APP数据
            $data = array();
            $data['return_code']  = $order['return_code'];
            $data['return_msg']   = $order['return_msg'];
            $data['prepay_id']    = $order['prepay_id'];
            $data['trade_type']   = $order['trade_type'];
            $data['nonce_str']    = $order['nonce_str'];
            $data['timestamp']    = $timestamp;
            $data['sign']         = $sign;
            Log::error(var_export($data, true), array(__CLASS__));
            return Response::json( $this->response( '1','预订单成功',$data ) );
        }else{
            Log::error(var_export('微信回调错误', true), array(__CLASS__));
            return Response::json( $this->response( '0' ) );
        }
    }

}