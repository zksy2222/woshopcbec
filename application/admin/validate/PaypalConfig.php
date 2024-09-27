<?php
namespace app\admin\validate;
use think\Validate;

class PaypalConfig extends Validate

{
    protected $rule = [
        'client_id' => 'require',
        'secret' => 'require',
        'online' => 'require',
        'web_url' => 'require|url',
    ];

    protected $message = [
        'client_id.require' => '缺少client_id',
        'secret.require' => '缺少秘钥',
        'online.require' => '缺少环境',
        'web_url.url' => '网页支付跳转地址不正确',
    ];
    

}