<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
use app\admin\services\Upush;

class AliPay extends Controller{

    /*
     * app支付
     */
    public function getPrePayOrder($body, $total_amount, $product_code, $notify_url)
    {
        /**
         * 调用支付宝接口。
         */
        $alipayRes=db('alipay_config')->where('id',1)->find();

        $base_route = dirname(dirname(__FILE__)).'/libs/alipay/aop/';
        include($base_route.'AopClient.php');
        include($base_route.'/request/AlipayTradeAppPayRequest.php');
        $aop = new \AopClient();
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = $alipayRes['appid'];
        $aop->rsaPrivateKey = $alipayRes['private_key'];
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->signType = "RSA2";
        $aop->alipayrsaPublicKey = $alipayRes['public_key'];
        $request = new \AlipayTradeAppPayRequest();
        $arr['body'] = $body;
        $arr['subject'] = $body;
        $arr['out_trade_no'] = $product_code;
        $arr['timeout_express'] = '30m';
        $arr['total_amount'] = floatval($total_amount);
        $arr['product_code'] = 'QUICK_MSECURITY_PAY';

        $json = json_encode($arr);
        $request->setNotifyUrl($notify_url);
        $request->setBizContent($json);

        $response = $aop->sdkExecute($request);
        return $response;

    }

    // h5支付
    public function getWapPayInfo($out_trade_no,$body,$total_amount,$notify_url='',$return_url=''){
        $webUrl = get_config_value('weburl');
        $notify_url = !empty($notify_url) ? $notify_url : $webUrl.'/api/AliPay/aliNotify';  // 默认商品支付回调
        $return_url = !empty($return_url) ? $return_url : $webUrl.'/h5/#/pagesC/order/allOrder?index=2'; // 默认跳转到订单列表

        $alipayConfig=db('alipay_config')->where('id',1)->find();

        $base_route = dirname(dirname(__FILE__)).'/libs/alipay/wappay/';
        include($base_route.'service/AlipayTradeService.php');
        include($base_route.'buildermodel/AlipayTradeWapPayContentBuilder.php');

        //超时时间
        $timeout_express="1m";

        $payRequestBuilder = new \AlipayTradeWapPayContentBuilder();
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($body);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setTimeExpress($timeout_express);

        $config['app_id'] = $alipayConfig['appid'];
        $config['merchant_private_key'] = $alipayConfig['private_key'];
        $config['alipay_public_key'] = $alipayConfig['public_key'];

        $config['notify_url'] = $notify_url;
        $config['return_url'] = $return_url;
        $config['charset'] = 'UTF-8';
        $config['sign_type'] = 'RSA2';
        $config['gatewayUrl'] = 'https://openapi.alipay.com/gateway.do';
        $payResponse = new \AlipayTradeService($config);
        $result = $payResponse->wapPay($payRequestBuilder,$config['return_url'],$config['notify_url']);
        // $result = 1;
        return $result;
    }




    // PC支付
    public function getPcPayInfo($orderSn, $body, $money, $notify_url, $return_url){
        if(strpos($_SERVER['HTTP_ORIGIN'],'pc.') !== false){
            $return_url = $_SERVER['HTTP_ORIGIN']."/#/My?tabIndex=1";
        }
        /**
         * 调用支付宝接口。
         */
        $alipayRes=db('alipay_config')->where('id',1)->find();


        $base_route = dirname(dirname(__FILE__)).'/libs/alipay/aop/';
        include($base_route.'AopClient.php');
        include($base_route.'/request/AlipayTradeAppPayRequest.php');

        $aop = new \AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $alipayRes['appid'];
        $aop->rsaPrivateKey = $alipayRes['private_key'];
        $aop->alipayrsaPublicKey = $alipayRes['public_key'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        // //异步接收地址，仅支持http/https，公网可访问
        // $request->setNotifyUrl($notify_url);
        // //同步跳转地址，仅支持http/https
        // $request->setReturnUrl($return_url);
        $arr['out_trade_no'] = $orderSn;
        $arr['total_amount'] = $money;
        $arr['subject'] = $body;
        $arr['body'] = $body;
        $arr['timeout_express'] = '30m';
        //电脑网站支付场景固定传值FAST_INSTANT_TRADE_PAY
        $arr['product_code'] ='FAST_INSTANT_TRADE_PAY';
//        $arr['notifyUrl'] =$notify_url;
//        $arr['returnUrl'] =$return_url;


        include($base_route.'/request/AlipayTradePagePayRequest.php');
        $json = json_encode($arr);
        $request = new \AlipayTradePagePayRequest();
        $request->setNotifyUrl($notify_url);
        $request->setReturnUrl($return_url);
        $request->setBizContent($json);
        $result = $aop->pageExecute ( $request);
        return $result;
    }



    /***
     * 支付宝回调验签
     */
    public function payReturn($data){
        $alipayRes=db('alipay_config')->where('id',1)->find();

        define('IN_ECS', true);
        $base_route = dirname(dirname(__FILE__)).'/libs/alipay/aop/';
        include($base_route.'AopClient.php');
        include($base_route.'/request/AlipayTradeAppPayRequest.php');
        $aop = new \AopClient();
        $aop->alipayrsaPublicKey = $alipayRes['public_key'];
        $flag = $aop->rsaCheckV1($data, NULL, "RSA2");
        //记录支付回调日志
        $myfile = fopen("alipay.log", "a");
        fwrite($myfile, "\r\n");
        fwrite($myfile, json_encode($data));
        fclose($myfile);
        if($data['trade_status'] == 'TRADE_SUCCESS' ){
            //业务处理   验证签名成功
            return 1;
        }else{
            return 0;
        }
    }

    //支付宝支付回调地址
    public function aliNotify(){

        $data = $_POST;
        $return = $this->payReturn($data);
        // 打印日志文件 文件地址/public/logs/log.txt
        // file_put_contents('./logs/log.txt',$return,FILE_APPEND);
        $order_sn = $data['out_trade_no'];  //订单单号
        $price = $data['total_amount'];
        $pay = new Pay();
        if($return == 1){   //验证成功
            $orderType = substr( $data['out_trade_no'],0,1);
            // $orderType Z-商品订单，C-充值订单，R-商家入驻保证金订单
            switch ($orderType){
                case "Z":
                    $pay->doGooodsOrder($order_sn,1);
                    break;
                case "C":
                    $pay->doRechargeOrder($order_sn,1);
                    break;
                case "R":
                    $pay->doRzOrder($order_sn,1);
                    break;
                default:
                    // 系统错误，未获取到订单类型
            }

            echo 'success';
        }else{
            echo 'fail';
        }
    }

}