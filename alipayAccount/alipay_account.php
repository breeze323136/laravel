<?php
/*
 * 账务明细分页查询接口接入页
 * author:liangfeng@shinc.net
 */
$base = dirname(__FILE__);
include($base .'/libs/script_base.php');

//引入支付宝账务所需要的文件
include($base .'/alipayAccount/accountConfig.class.php');
include($base .'/alipayAccount/lib/accountSubmit.class.php'); 

date_default_timezone_set('PRC');

class alipay extends script_base{

	/*
	* 控制器
	*/
	public function anyAccount(){

		//实例化配置信息
        $accountConfig  = new accountConfig();
        $accountConfigInfo = $accountConfig->accountConfigs();

        // 初始页号 	必填，必须是正整数
        $pageNo = 1;

        //账务查询开始时间 格式为：yyyy-MM-dd HH:mm:ss
        // $gmtStartTime = date("Y-m-d 00:00:00",strtotime("-1 day"));
        $gmtStartTime = '2015-11-06 12:16:04';
        $gmtEndTime   = '2015-11-07 12:16:04';
        // $gmtStartTime = '2015-11-02 00:00:00';
        
        //账务查询结束时间  格式为：yyyy-MM-dd HH:mm:ss
        // $gmtEndTime   = date("Y-m-d 00:00:00");
       
        // $gmtEndTime   = '2015-11-03 00:00:00';


        //构造要请求的参数数组，无需改动
        $parameter      = array(
            "service"       => "account.page.query",
            "partner"       => trim($accountConfigInfo['partner']),
            "page_no"       => $pageNo,
            "gmt_start_time"=> $gmtStartTime,
            "gmt_end_time"  => $gmtEndTime,
            "_input_charset"=> trim(strtolower($accountConfigInfo['input_charset']))
        );

        //建立请求
        $alipaySubmit   = new AccountSubmit($accountConfigInfo);
        // $alipaySubmit   = new App\Libraries\AccountSubmit($accountConfigInfo);

        $htmlText       = $alipaySubmit->buildRequestHttp( $parameter );

        //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表

        $accountInfomation = json_decode( xml_to_json( $htmlText ) );

        // var_dump($accountInfomation);die;
        $tradeNo 	= array();
        $tradeInfo 	= array();
        // var_dump($accountInfomation);die;
        foreach ( $accountInfomation->response as $key => $accountLogList ) {
                
            foreach ( $accountLogList as $key => $AccountQuery ) {

                if( !isset( $AccountQuery ->AccountQueryAccountLogVO ) ){
                    continue;
                } 

                foreach ( $AccountQuery->AccountQueryAccountLogVO as  $value ) {
                	if(is_string($value->trade_no)){
                		// var_dump($value->trade_no);die;
                    	$tradeNo[] 	= 	$value->trade_no;
                	    $tradeInfo[]	=	$value;
                	}
                }  
            }  
        }


        //当天没有交易记录
        if( empty( $tradeNo ) ){
        	return false;
        }
    	//自定义函数，对象转数组
    	$tradeInfo = objectToArray($tradeInfo);
    	foreach ($tradeInfo as $key => $value) {
    		$tradeBuyyerInfo[] = array(
    			'trade_no' => $value['trade_no'],
    			'total_fee' => $value['total_fee'],
    			'buyer_id'	=> $value['buyer_account']
    			);
    	}
    	// var_dump($tradeBuyyerInfo);die;
        $billData 				= $this->getComparisonInfo( $tradeBuyyerInfo , $tradeNo , $gmtStartTime , $gmtEndTime );

        $billData['start_time'] =  $gmtStartTime;
        $billData['end_time']   =  $gmtEndTime;

        //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——   
        $ceateTime = "执行日期：".strftime("%Y%m%d%H%M%S",time())."\n";
        $dataInfo  = array(
        	'ceate_time' =>  $ceateTime,
        	'infomation' =>	 $billData
        );
        file_put_contents( 'log.txt', "\n\n".print_r( $dataInfo,1 ),FILE_APPEND );

        echo "\n\n执行时间:".date( 'Y-m-d H:i:s' ).":\n";
        var_dump( $billData );
        echo "\n\n";

    }

    /**
    ************************************************************************
	** 								模型主操作							****
	** 比较支付宝账务与本地订单账务是否相同									****
	** @param   $tradeInfo      购买者信息								****
	** @param 	$tradeNo		唯一交易号								****
	** @param 	$gmtStartTime 	开始时间									****
	** @param 	$gmtEndTime 	结束时间									****
	** 程序逻辑：判断三种情况： 											****
	**	1、明细账有而本地表没有的漏单行为异常 								****
	** 	2、明细账没有而本地有的黑客行为异常									****
	**	3、本地表有重复交易号的恶意黑客行为异常								****
	** 	4、本地表没有数据而明细账没有严重漏单的异常							****
	**																	****
	** type 0为账务明细有，本地alipay表没有   1为账务明细没有，本地alipay表有	****
	**																	****
	** @return $failTrade 	array 	 返回异常的交易号和其他说明信息			****
	*/
	public function getComparisonInfo( $tradeBuyyerInfo , $tradeNo , $gmtStartTime , $gmtEndTime ){
		//返回的数据
		$failTrade = array();

		//根据时间查询全部的本地alipay表的订单id和交易号
		$sql = "select id,trade_no,total_fee,buyer_id from sh_alipay where gmt_create between '$gmtStartTime' and '$gmtEndTime'";
		$alipayDataInfo = $this->fetchAll( $sql );
		//alipay有数据下的操作
		if( !empty( $alipayDataInfo ) ){

			$alipayTradeNo = array();
			$alipayTrueTradeNo = array();
			foreach ( $alipayDataInfo as $alipayData ) {

				foreach ($tradeNo as $key => $tradeNoInfo) {
					if($alipayData->trade_no == $tradeNoInfo){
						$alipayTrueTradeNo[] = $alipayData->trade_no;
					}
				}
				
				$alipayTradeNo[] = $alipayData->trade_no;
				$hostBuyyerInfo[] = array(
					'alipay_id'	=> $alipayData->id,
	    			'trade_no' 	=> $alipayData->trade_no,
	    			'total_fee' => 0,
	    			'buyer_id'	=> $alipayData->buyer_id
    			);
			}
			// 获取去掉重复数据的数组 
			$alipayRepeatTradeNo = array_unique( $alipayTradeNo );

			//1、明细账有而本地表没有的漏单行为异常 
			$differentTrade = array_diff( $tradeNo,$alipayRepeatTradeNo );
		    $failTrade[] 	= $this->codeReusable( $tradeBuyyerInfo , $differentTrade , $type =0 , $gmtStartTime , $gmtEndTime );
			
			//2、明细账没有而本地有的黑客行为异常 
			$arr1 = array_diff( $alipayRepeatTradeNo , $tradeNo );
			$failTrade[]  	=  $this->codeReusable( $hostBuyyerInfo , $arr1 , $type = 1 , $gmtStartTime , $gmtEndTime );
			
			//3、本地表有重复交易号的恶意黑客行为异常,真实交易重复
			$alipayRepeatTradeNo = array_unique( $alipayTrueTradeNo );
			$arr2 = array_diff_assoc( $alipayTrueTradeNo, $alipayRepeatTradeNo ); 	// 获取重复数据的数组
			
			$arr2 = array_unique($arr2);
			$failTrade[]  	=  $this->codeReusable( $tradeBuyyerInfo , $arr2 , $type = 2 , $gmtStartTime , $gmtEndTime );

			if( empty( $failNoTrade ) && empty( $failTrade ) && empty( $repeatArr ) ){
				$failTrade['desc'] ='完全匹配，账务和alipay表吻合';
			}

		}else{
			// var_dump($tradeNo);die;
			//4、本地表没有数据而明细账没有严重漏单的异常
			return $this->codeReusable( $tradeBuyyerInfo , $tradeNo , $type=0 , $gmtStartTime , $gmtEndTime );
		}

		return $failTrade;
	}
	
	/*
	* 公用方法------代码重用   查询异常和记录异常信息
	* 
	*/
	public function codeReusable( $tradeBuyyerInfo , $differentTrade , $type , $gmtStartTime , $gmtEndTime ){
		//返回数据的数组
		$lostOrder = array();

		if( $differentTrade ){
			// var_dump($differentTrade);die;
			//判断bill表里是否记录了当天异常情况
			$accountTradeNo  = "'" . implode( "','", $differentTrade ) . "'";
			$singleSql 		 = "select id from sh_bill where trade_no In ({$accountTradeNo})";
			$result 	 	 = $this->fetchAll($singleSql);
			//记录异常
			if( empty( $result ) ){
				
				$isertData = array();
				foreach ( $differentTrade as $failAccountTradeNo ) {
					foreach ($tradeBuyyerInfo as $buyyerInfo) {
						if($failAccountTradeNo == $buyyerInfo['trade_no']){
							$isertData[] = array(
								'alipay_id'	=> $buyyerInfo['alipay_id'],
								'trade_no' 	=> $failAccountTradeNo,
								'type'	   	=> $type,
								'start_date'=> $gmtStartTime,
								'end_date'	=> $gmtEndTime,
								'total_fee'	=> $buyyerInfo['total_fee'],
								'buyer_id'	=> $buyyerInfo['buyer_id']
							);
						}
					}
					
				}
				// file_put_contents( 'log.txt', "\n\n".print_r( $isertData,1 ),FILE_APPEND );
				//将异常插入数据库
				$insertStatus 	 = $this->insertIgnoreAll( $isertData , 'sh_bill' );		
				if( $insertStatus ){
					//成功插入
					$lostOrder['lost'] = $isertData;
					$lostOrder['type'] = $type;
					$lostOrder['desc'] ='记录异常情况记录起来';
				}else{	
					//未记录，程序错误	
					return false; 	
				}
			}else{
				//已记录异常
				$lostOrder['lost'] 	=	$differentTrade;
				$lostOrder['type']  =   $type;
				$lostOrder['desc']	=	'已记录异常情况';
			}
		}

		return $lostOrder;
	}

}

//ini_set('memory_limit','128M');
$obj = new alipay();
$obj->anyAccount();

