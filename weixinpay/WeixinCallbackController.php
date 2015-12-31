<?php
/***
 *微信异步回调
 *@author  liangfeng@shinc.net
 *
 *@version  v1.0
 *@copyright shinc
 */
namespace  Laravel\Controller\Callback; //定义命名空间

use ApiController;							//引入接口公共父类，用于继承
use Illuminate\Support\Facades\Log;
use Laravel\Service\JnlService;
use Laravel\Service\RechargeService;//充值服务类
use Laravel\Service\PeriodService;//
use Laravel\Model\JnlWeixinModel;//
use Illuminate\Support\Facades\Response;
use App\Libraries\WxPayNotifyReply;
use App\Libraries\WxPayOrderQuery;
use App\Libraries\WxPayApi;

class WeixinCallbackController extends  ApiController {
    protected $nowTime;
    protected $rechargeService;
    protected $periodService;
    protected $jnlWeixinModel;
    protected $jnlService;

    public function  __construct() {
        parent::__construct();
        $this->nowTime = date('Y-m-d H:i:s');
        $this->rechargeService = new RechargeService();
        $this->periodService = new PeriodService();
        $this->jnlWeixinModel = new JnlWeixinModel();
        $this->jnlService = new JnlService();
    }

    public function postCallback(){
        //获取回调通知xml
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        Log::error(var_export($xml, true), array(__CLASS__));
        $reply = new WxPayNotifyReply();
        $data = $reply->FromXml($xml);
        Log::error(var_export($data, true), array(__CLASS__));


        if(!$data){
            Log::error(var_export('非法请求', true), array(__CLASS__));
            return Response::json( $this->response( '10006' ) );
        }

        $return_code = $data['return_code'];

        if($return_code=='FAIL'){
            $err_code_des = $data['err_code_des'];
            Log::error(var_export('异步回调通知错误FAIL', true), array(__CLASS__));
            return Response::json( $this->response( '0', $err_code_des) );
        }

        if($return_code=='SUCCESS'){
            //对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
            //微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
            //尽可能提高通知的成功率，但微信不保证通知最终能成功。
            $transaction_id = $data['transaction_id'];

            $input = new WxPayOrderQuery();
            $input->SetTransaction_id($transaction_id);
            $result = WxPayApi::orderQuery($input);

            if(array_key_exists("return_code", $result)
                && array_key_exists("result_code", $result)
                && $result["return_code"] == "SUCCESS"
                && $result["result_code"] == "SUCCESS")
            {
                $total_fee = $data['total_fee'] / 100;
                //插入数据库
                $weixinData = array(
                    'transaction_id'   =>   $data['transaction_id'],
                    'out_trade_no'     =>   $data['out_trade_no'],
                    'total_fee'        =>   $total_fee,
                    'nonce_str'        =>   $data['nonce_str'],
                    'sign'             =>   $data['sign'],
                    'create_time'      =>   $this->nowTime,
                    'time_expire'      =>   date("Y-m-d H:i:s", time() + 7200),
                    'time_end'         =>   $data['time_end'],
                    'is_subscribe'     =>   $data['is_subscribe'],
                    'trade_type'       =>   $data['trade_type'],
                    'bank_type'        =>   $data['bank_type'],
                    'fee_type'         =>   $data['fee_type'],
                    'cash_fee'         =>   $data['cash_fee'],
                    'appid'            =>   $data['appid'],
                    'mch_id'           =>   $data['mch_id'],
                    'openid'           =>   $data['openid'],
                    'return_code'      =>   $data['return_code'],
                );

                $trade_status = $data['return_code'];
                $out_trade_no = $data['out_trade_no'];
                $pay_amount   = $total_fee;


                $flag = false;
                if ($trade_status == 'SUCCESS') {
                    $weixinInfo = $this->jnlWeixinModel->load($transaction_id);
                    if (empty($weixinInfo)) {
                        $this->jnlWeixinModel->add($weixinData);
                        $flag = true;
                    } else {
                        $db_status = $weixinInfo->return_code;
                        if ($db_status != $trade_status) {
                            $param = [
                                'return_code' => $trade_status,
                                'create_time' => $this->nowTime
                            ];
                            $this->jnlWeixinModel->update( $transaction_id, $param);
                        }
                    }
                }

                try {
                    if ($flag) {
                        $id = $this->rechargeService->recharge($out_trade_no,  $pay_amount, $transaction_id, JnlService::Recharge_Channel_weixin);
                        if ($id) {
                            if ($this->jnlService->payCallbackUpdateJnl($out_trade_no, $pay_amount, $id, JnlService::JnlTrans_Status_pay_success,JnlService::Recharge_Channel_weixin)) {
                                $this->periodService->duobaoNeedRecharge($out_trade_no);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error(var_export($e, true), array(__CLASS__));
                }

                return 'SUCCESS';
            }
            return 'FAIL';


        } else {
            //验证失败
            return 'FAIL';

        }
    }

}
