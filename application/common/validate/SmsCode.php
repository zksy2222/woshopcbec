<?php
namespace app\common\validate;
use think\Validate;

class SmsCode extends Validate
{
    protected $rule = [
        'phone' => 'require|number',
        'sms_code' => 'require|length:6|regex:/^\d{6}$/',
        'type' => 'require|in:1,2,3,4,5,6'
    ];

    protected $message = [
        'phone.require' => '手机号不能为空',
        'phone.number' => '手机号格式不正确',
        'sms_code.require' => '验证码不能为空',
        'sms_code.regex' => '验证码为6位数字',
        'sms_code.length' => '验证码为错误',
        'type.require' => '验证码类型不能为空',
        'type.in' => '验证码类型参数错误'
    ];

    protected $scene = [
        'send' => ['phone','type'],
        'check' => ['phone','sms_code','type']
    ];

}