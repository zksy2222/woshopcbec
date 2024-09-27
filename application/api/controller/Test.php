<?php
namespace app\api\controller;
use app\api\libs\paypal\Paypal;
use think\Controller;
use think\Db;
use app\api\controller\Common;

class Test extends Common{

    public function index(){
        $webconfig = $this->webconfig;
        $notify_url = $webconfig['weburl'] . "/api/MyPaypal/paypalNotify";
        $cancel_url = $webconfig['weburl'] . "/api/MyPaypal/paypalCancel";
        $body="这是一个测试商品";
        $money="33.00";

        $paypal = new Paypal();
        $responseResult = $paypal->createPayPal(1,$body,$money,$notify_url,$cancel_url);
        var_dump($responseResult);
    }
}