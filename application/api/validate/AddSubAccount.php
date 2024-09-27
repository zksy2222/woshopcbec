<?php
namespace app\api\validate;
use think\Validate;

class AddSubAccount extends Validate
{
    protected $rule = [
        'phone' => 'require|regex:/^1[3456789]\d{9}$/',
        'sms_code' => 'require|regex:/\S{6}/'
    ];

    protected $message = [
        'phone.require' => '手机号不能为空',
        'phone.regex' => '手机号格式不正确',
        'sms_code.require' => '验证码不能为空',
        'sms_code.regex' => '验证码格式不正确',
    ];

}