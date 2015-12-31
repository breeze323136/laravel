<?php
/***
 *支付宝手机网站支付异步回调
 *@author  liangfeng@shinc.net
 *
 *@version  v1.0
 *@copyright shinc
 */
namespace  Laravel\Controller\Callback; //定义命名空间

use ApiController;							//引入接口公共父类，用于继承
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Laravel\Service\JnlService;
use Laravel\Service\RechargeService;//充值服务类
use Laravel\Service\PeriodService;//
use Laravel\Model\JnlAlipayModel;//
use App\Libraries\AlipayNotify;//引入支付宝移动支付异步服务器支付宝扩展
use App\Libraries\alipayConfig;//引入支付宝移动支付异步服务器配置文件

class  AlipayCallbackController extends  ApiController {

    protected $rechargeService;
    protected $periodService;
    protected $jnlAlipayModel;
    protected $jnlService;
    public function  __construct() {
        parent::__construct();
        $this->rechargeService = new RechargeService();
        $this->periodService = new PeriodService();
        $this->jnlAlipayModel = new JnlAlipayModel();
        $this->jnlService = new JnlService();
    }

    public function anyCallback(){

        //计算得出通知验证结果
        $alipayC  = new alipayConfig();
		$alipay_config = $alipayC->config();
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();
        Log::info('支付宝回调:' . var_export(Input::all(),true),array(__CLASS__));
        if($verify_result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代


            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——

            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表

            //商户订单号

            $out_trade_no = Input::get('out_trade_no');

            //支付宝交易号

            $trade_no = Input::get('trade_no');

            $data = array(
                'trade_no' => Input::get('trade_no'),
                'notify_type' => Input::get('notify_type'),
                'notify_id' => Input::get('notify_id'),
                'sign_type' => Input::get('sign_type'),
                'sign' => Input::get('sign'),
                'notify_time' => Input::get('notify_time'),
                'out_trade_no' => Input::get('out_trade_no'),
                'subject' => Input::get('subject'),
                'payment_type' => Input::get('payment_type'),
                'trade_status' => Input::get('trade_status'),
                'seller_id' => Input::get('seller_id'),
                'seller_email' => Input::get('seller_email'),
                'buyer_id' => Input::get('buyer_id'),
                'buyer_email' => Input::get('buyer_email'),
                'total_fee' => Input::get('total_fee'),
                'quantity' => Input::get('quantity'),
                'price' => Input::get('price'),
                'body' => Input::get('body'),
                'gmt_create' => Input::get('gmt_create'),
                'gmt_payment' => Input::get('gmt_payment'),
                'is_total_fee_adjust' => Input::get('is_total_fee_adjust'),
                'use_coupon' => Input::get('use_coupon'),
                'discount' => Input::get('discount'),
                'refund_status' => '',//退款状态
                'gmt_refund' => '');//退款时间

            $trade_status = Input::get('trade_status');
            $jnl_no = Input::get('out_trade_no');
            $pay_amount = Input::get('total_fee');

            $flag = false;
            if ($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {
                $alipayInfo = $this->jnlAlipayModel->load($trade_no);
                if (empty($alipayInfo)) {
                    $this->jnlAlipayModel->add($data);
                    $flag = true;
                } else {
                    $db_status = $alipayInfo->trade_status;
                    if ($db_status != $trade_status) {
                        $param = [
                            'trade_status' => $trade_status,
                            'notify_time' => Input::get('notify_time')
                        ];
                        $this->jnlAlipayModel->update($trade_no, $param);
                    }
                }
            }

            try {
                if ($flag) {
                    $id = $this->rechargeService->recharge($out_trade_no, Input::get('total_fee'), $trade_no, "0");
                    if ($id) {
                        if ($this->jnlService->payCallbackUpdateJnl($jnl_no, $pay_amount, $id, JnlService::JnlTrans_Status_pay_success,JnlService::Recharge_Channel_alipay)) {
                            $this->periodService->duobaoNeedRecharge($out_trade_no);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error(var_export($e, true), array(__CLASS__));
            }

            return 'success';
        }
        else {
            //验证失败
            return "fail";

            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }
    }

	public function getTest(){
        $alipayC  = new alipayConfig();
		$alipay_config = $alipayC->config();
		debug($alipay_config);
	}
}
