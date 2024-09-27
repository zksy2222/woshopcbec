<?php
namespace app\mobile\validate;
use think\Validate;

class Member extends Validate
{
    protected $rule = [
        'phone' => 'require|unique:member|regex:/^1[3456789]\d{9}$/',
        'phonecode' => ['require','length'=>6,'regex'=>'/^\+?[1-9][0-9]*$/'],
        'password' => 'require|regex:/^[a-zA-Z0-9]{6,10}$/',
        'repwd' => 'requireWith:password|confirm:password|regex:/^[a-zA-Z0-9]{6,10}$/',
        'xieyi' => 'require|in:0,1',
    ];

    protected $message = [
        'phone.require' => '手机号不能为空',
        'phone.unique' => '手机号已存在',
        'phone.regex' => '手机号格式不正确',
        'phonecode.require' => '验证码不能为空',
        'phonecode.length' => '验证码为6位数字',
        'phonecode.regex' => '验证码为6位数字',
        'password.require' => '密码不能为空',
        'password.regex' => '密码为6-10位字母或数字',
        'repwd.requireWith' => '确认密码不能为空',
        'repwd.confirm' => '确认密码不正确',
        'repwd.regex' => '密码为6-10位字母或数字',
        'xieyi.require' => '请同意注册协议',
        'xieyi.in' => '同意注册协议参数错误', 
    ];

}