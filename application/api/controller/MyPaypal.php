<?php
namespace app\api\controller;
use app\api\libs\paypal\Paypal;
use think\Controller;
use think\Db;
use app\admin\services\Upush;

class MyPaypal extends Controller{

    //支付宝支付回调地址
    public function paypalCallback(){
        $postData = input("post.");
        $getData = input("get.");
        if($postData && $getData){
            $data = array_merge($postData,$getData);
        }elseif ($postData){
            $data = $postData;
        }else{
            $data = $getData;
        }
        if(!empty($data)) {
            $write = json_encode($data);
            $write .= "-------------------------------------------------------------------------------------\n";
            $create_file = ROOT_PATH . "runtime/log/paypal.log";       //创建文件
            file_put_contents($create_file, $write, FILE_APPEND | LOCK_EX);
        }
//        $json_str = '{"id":"WH-1GJ89344EW841560R-62885708JB986413B","event_version":"1.0","create_time":"2022-09-09T14:12:34.344Z","resource_type":"payment","event_type":"PAYMENTS.PAYMENT.CREATED","summary":"Checkout payment is created and approved by buyer","resource":{"update_time":"2022-09-09T14:12:34Z","create_time":"2022-09-09T14:12:16Z","redirect_urls":{"return_url":"http:\/\/kjshop.html.enshitc.com\/h5\/#\/pagesC\/order\/allOrder?index=1&order_number=Z2022090922121310099515&paymentId=PAYID-MMNUTQI93W49117P1168870N","cancel_url":"http:\/\/kjshop.html.enshitc.com\/h5\/#\/pagesC\/order\/allOrder?index=1"},"links":[{"href":"https:\/\/api.sandbox.paypal.com\/v1\/payments\/payment\/PAYID-MMNUTQI93W49117P1168870N","rel":"self","method":"GET"},{"href":"https:\/\/api.sandbox.paypal.com\/v1\/payments\/payment\/PAYID-MMNUTQI93W49117P1168870N\/execute","rel":"execute","method":"POST"},{"href":"https:\/\/www.sandbox.paypal.com\/cgi-bin\/webscr?cmd=_express-checkout&token=EC-7BA127872N139591C","rel":"approval_url","method":"REDIRECT"}],"id":"PAYID-MMNUTQI93W49117P1168870N","state":"created","transactions":[{"amount":{"total":"34.90","currency":"USD","details":{"subtotal":"34.90","tax":"0.00","shipping":"0.00"}},"payee":{"merchant_id":"HP6BC255F5FFL","email":"sb-43cknz17233464@business.example.com"},"description":"\u5546\u54c1\u8ba2\u5355","invoice_number":"631b49be14a2b","item_list":{"items":[{"name":"\u5546\u54c1\u8ba2\u5355","price":"34.90","currency":"USD","quantity":1}],"shipping_address":{"recipient_name":"John Doe","line1":"1 Main St","city":"San Jose","state":"CA","postal_code":"95131","country_code":"US","default_address":false,"preferred_address":false,"primary_address":false,"disable_for_transaction":false}},"related_resources":[],"notify_url":"https:\/\/kjshop.api.enshitc.com\/api\/MyPaypal\/paypalCallback"}],"intent":"sale","payer":{"payment_method":"paypal","status":"VERIFIED","payer_info":{"email":"sb-j19qm20601217@business.example.com","first_name":"John","last_name":"Doe","payer_id":"669CZBTFNGQLL","shipping_address":{"recipient_name":"John Doe","line1":"1 Main St","city":"San Jose","state":"CA","postal_code":"95131","country_code":"US","default_address":false,"preferred_address":false,"primary_address":false,"disable_for_transaction":false},"country_code":"US","business_name":"Test Store"}},"cart":"7BA127872N139591C"},"links":[{"href":"https:\/\/api.sandbox.paypal.com\/v1\/notifications\/webhooks-events\/WH-1GJ89344EW841560R-62885708JB986413B","rel":"self","method":"GET"},{"href":"https:\/\/api.sandbox.paypal.com\/v1\/notifications\/webhooks-events\/WH-1GJ89344EW841560R-62885708JB986413B\/resend","rel":"resend","method":"POST"}]}';
//        $data = json_decode($json_str,true);
        if(!isset($data['resource']['id']) || !isset($data['resource']['payer']['payer_info']['payer_id'])){
            die('error');
        }
        $Paypal = new Paypal();
        $paypal = $Paypal->payRedirect($data['resource']['id'],$data['resource']['payer']['payer_info']['payer_id']);
        if($paypal['status']){   //验证成功
            $success_url = $data['resource']['redirect_urls']['return_url'];
            $paramsPost = $this->convertUrlQuery($success_url);
            $order_sn = isset($paramsPost['order_number']) ? $paramsPost['order_number'] : "";
            $pay = new Pay();
            $orderType = substr($order_sn,0,1);
            // $orderType Z-商品订单，C-充值订单，R-商家入驻保证金订单
            switch ($orderType){
                case "Z":
                    $spOrder = db("order_zong")->where("order_number","=",$order_sn)->find();
                    if(!empty($spOrder)){
                        $findOrderPaypal = db("order_zong_paypal")->where("order_id","=",$spOrder['id'])->find();
                        if(!$findOrderPaypal){
                            Db::name("order_zong_paypal")->insert([
                                "total_price"=>$paypal['data']['total'],
                                "order_number"=>$order_sn,
                                "order_id"=>$spOrder['id'],
                                "open_num"=>$paypal['data']['payNum'],
                                "payment_id"=>$paypal['data']['paymentID'],
                                "invoice_num"=>$paypal['data']['invoiceNum'],
                            ]);
                        }
                    }
                    $pay->doGooodsOrder($order_sn,8);
                    break;
                case "C":
                    $pay->doRechargeOrder($order_sn,8);
                    break;
                case "R":
                    $pay->doRzOrder($order_sn,8);
                    break;
                default:
                    die('error');
                    // 系统错误，未获取到订单类型
            }
            die('success');
        }else{
            die('error');
        }
    }

    /**
     * 支付退款
     */
    public function refundPaypalAmount(){
        $order_number = input("get.order_number");
        $orderPaypal = db("order_zong_paypal")->where("order_number","=",$order_number)->find();
        if(empty($orderPaypal)){
            return ['status'=>false,'msg'=>'没有找到订单'];
        }
        $Paypal = new Paypal();
        $pay = $Paypal->refundPaypalMoney($orderPaypal['open_num'],$orderPaypal['total_price']);
        if(!$pay['status']){
            datamsg(400,$pay['msg']);
        }
        datamsg(200,'获取成功');
    }


    //支付宝支付回调地址
    public function paypalNotify(){
        echo "<h3>我是支付</h3>";
    }

    //测试取消支付宝支付回调地址
    public function paypalCancel(){
        echo "<h3>我是取消支付</h3>";
    }

    //Privacy policy
    public function privacyPolicy(){

    }

    //agreement
    public function agreement(){

    }



    /**
     * 解析url中参数信息，返回参数数组
     */
    public function convertUrlQuery($query){
        $queryParts = explode('&', $query);
        $params = array();
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        return $params;
    }

}