<?php
namespace app\common\validate;
use think\Validate;

class EmailCode extends Validate
{
    protected $rule = [
        'email' => 'require|email|unique:member',
        'sms_code' => 'require|length:6|regex:/^\d{6}$/',
        'type' => 'require|in:1,2,3,4,5,6,10'
    ];

    protected $message = [
        'sms_code.require' => '验证码不能为空',
        'sms_code.regex' => '验证码为6位数字',
        'sms_code.length' => '验证码为错误',
        'type.require' => '验证码类型不能为空',
        'type.in' => '验证码类型参数错误',
        'email.email' => '邮箱格式错误',
        'email.require' => '邮箱不能为空',
        'email.unique' => '邮箱已存在',
    ];

    protected $scene = [
        'send' => ['email','type'],
        'check' => ['email','sms_code','type']
    ];

}