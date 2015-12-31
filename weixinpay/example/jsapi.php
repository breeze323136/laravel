<?php


/*
 * 微信预支付接口
 *
 * author:liangfeng@shinc.net
 * create_time:2015-11-17
 * detail:APP确定下单商品信息传来本服务器端,我们回调微信接口生成预下单后(统一下单),
 *        回传一个prepay_id给我们,我们生成一个签名算法。回传sign和prepay_id给app端,app请求微信支付信息
 *
 */
ini_set('date.timezone','Asia/Shanghai');
//error_reporting(E_ERROR);
require_once "../lib/WxPayApi.class.php";
require_once "WxPayJsApiPay.class.php";
//require_once 'log.php';

//接收安卓传回参数
//  session(用user_id暂时代替)
//  out_trade_no(订单号)
//  type(支付类型 2为微信)
//  goods_name(商品名称)
//  period_id(活动期数id)
//  total_fee(总价)
if( !empty( $_GET['user_id'] ) && !empty( $_GET['out_trade_no'] ) && !empty( $_GET['type'] )
    && !empty( $_GET['goods_name'] ) && !empty( $_GET['period_id'] ) && !empty( $_GET['total_fee'] ) ){

    $userId     =   $_GET['user_id'];
    $outTradeNo =   $_GET['out_trade_no'];
    $type       =   $_GET['type'];
    $goodsName  =   $_GET['goods_name'];
    $periodId   =   $_GET['period_id'];
    $totalFee   =   $_GET['total_fee'];

    file_put_contents( "log.txt", "\n\n".print_r( $outTradeNo,1 ),FILE_APPEND );
//    Log::write(1,$outTradeNo);

}else{
    file_put_contents( "log.txt", "\n\n参数错误",FILE_APPEND );
    echo json_encode(array("code"=>0,"msg"=>"参数错误"));
    return false;
}

//①、获取用户openid
//$tools = new JsApiPay();
//$openId = $tools->GetOpenid();

//②、统一下单   请求微信预下单
$input = new WxPayUnifiedOrder();
$input->SetBody($goodsName);
$input->SetAttach("test");
$input->SetOut_trade_no(WxPayConfig::MCHID.date("YmdHis"));
$input->SetTotal_fee($totalFee);
$input->SetTime_start(date("YmdHis"));
$input->SetTime_expire(date("YmdHis", time() + 7200));
$input->SetGoods_tag("test_goods");
$input->SetNotify_url("http://paysdk.weixin.qq.com/example/notify.php");
$input->SetTrade_type("APP");
//$input->SetOpenid($openId);
$order = WxPayApi::unifiedOrder($input);
//echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
//printf_info($order);

//判断微信生成预支付后的返回值-----
//返回状态码	return_code
//返回信息	return_msg
if($order['return_code']=='FAIL'){
    file_put_contents( "log.txt", "\n\n".print_r( "统一下单失败，请查看微信统一下单文档相关说明",1 ),FILE_APPEND );
    echo json_encode(array('code'=>0,'msg'=>$order['return_msg']));
    return false;
}else{
    //这里是写入数据库业务逻辑(信息在统一订单接口文档中)












    //成功后微信返回的数据，并存入数据库，与返回给安卓
    //步骤3：统一下单接口返回正常的prepay_id，再按签名规范重新生成签名后，将数据传输给APP。
    //参与签名的字段名为appId，partnerId，prepayId，nonceStr，timeStamp，package。注意：package的值格式为Sign=WXPay
    $timestamp = time();

    $arr = array();
    //公众账号ID
    $arr['appid'] = trim(WxPayConfig::APPID);
    //商户号
    $arr['partnerid'] = trim(WxPayConfig::MCHID);
    //预支付交易会话标识
    $arr['prepayid'] = $order['prepay_id'];
    //包的格式
    $arr['package'] = 'Sign=WXPay';
    //随机字符串
    $arr['noncestr'] = $order['nonce_str'];
    //时间戳
    $arr['timestamp'] = $timestamp;

    $obj = new WxPayDataBase();
    $obj->SetValues($arr);
    $sign = $obj->SetSign();

    $data = array();
    $data['return_code']  = $order['return_code'];
    $data['return_msg']   = $order['return_msg'];
    $data['prepay_id']    = $order['prepay_id'];
    $data['trade_type']   = $order['trade_type'];
    $data['nonce_str']    = $order['nonce_str'];
    $data['timestamp']    = $timestamp;
    $data['sign']         = $sign;
    $data['out_trade_no'] = $outTradeNo;

    $order = json_encode($order);
    file_put_contents( "log.txt", "\n\n预支付成功----$order",FILE_APPEND );
    echo json_encode(array('code'=>1,'msg'=>'成功','data'=>$data));

}

