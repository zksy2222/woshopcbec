<?php
namespace app\admin\validate;
use think\Validate;

class WxpayConfig extends Validate

{
    protected $rule = [
        'appid' => 'require',
        'mch_id' => 'require',
        'api_key' => 'require',
        'notify_url' => 'require|url',
        'app_appid' => 'require',
    ];

    protected $message = [
        'appid.require' => '缺少小程序应用id',
        'mch_id.require' => '缺少小程序商户id',
        'api_key.require' => '缺少小程序api密钥',
        'notify_url.require' => '缺少异步通知url地址',
        'notify_url.url' => '异步通知url地址格式不正确',
        'app_appid.require' => '缺少app支付应用id'
    ];
    

}