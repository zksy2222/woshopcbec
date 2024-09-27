<?php
namespace app\api\validate;
use think\Validate;

class Member extends Validate
{
    protected $rule = [
        'phone' => 'require|unique:member|number',
        'phonecode' => ['require','length'=>6,'regex'=>'/^\+?[1-9][0-9]*$/'],
        'paypwd' => ['require','length'=>6,'regex'=>'/^\+?[1-9][0-9]*$/'],
        'password' => 'require|regex:/^[a-zA-Z0-9]{6,10}$/',
        'repwd' => 'requireWith:password|confirm:password|regex:/^[a-zA-Z0-9]{6,10}$/',
        'xieyi' => 'require|in:0,1',
        'user_name' => 'length:1,20',
        'sex' => 'in:0,1,2',
        'birth' => 'regex:/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/',
        'email' => 'email|unique:member',
        'openid' => 'require',
        'app_openid' => 'require',
        'unionid' => 'require',
        'oauth' => 'require',
    ];

    protected $message = [
        'phone.require' => '手机号不能为空',
        'phone.unique' => '手机号已存在',
        'phone.number' => '手机号格式不正确',
        'phonecode.require' => '验证码不能为空',
        'phonecode.length' => '验证码为6位数字',
        'phonecode.regex' => '验证码为6位数字',
        'paypwd.require' => '支付密码不能为空',
        'paypwd.length' => '支付密码为6位数字',
        'paypwd.regex' => '支付密码为6位数字',
        'password.require' => '密码不能为空',
        'password.regex' => '密码为6-10位字母或数字',
        'repwd.requireWith' => '确认密码不能为空',
        'repwd.confirm' => '确认密码不正确',
        'repwd.regex' => '密码为6-10位字母或数字',
        'xieyi.require' => '请同意注册协议',
        'xieyi.in' => '同意注册协议参数错误', 
        'user_name.length' => '昵称为1到20位字符',
        'sex.in' => '性别错误',
        'birth.regex' => '生日格式错误',
        'email.email' => '邮箱格式错误',
        'email.unique' => '邮箱已存在',
        'openid.require' => '缺少openid参数',
        'app_openid.require' => '缺少openid参数',
        'unionid.require' => '缺少unionid参数',
        'user_name.require' => '未获取到用户名',
        'oauth.require' => '缺少登录授权类型参数',
    ];
    
    protected $scene = [
        'register' => ['phone','password'],
        'emailRegister' => ['email','password'],
        'pwd_login' => ['phone' => 'require|number','password'],
        'email_pwd_login' => ['email' => 'require|email','password'],
        'sms_login' => ['phone' => 'require|number'],
        'weixin_app_login' => ['app_openid','user_name'],
        'weixin_mp_login' => ['openid','user_name'],
        'weixin_app_register' => ['app_openid','user_name'=>'require','unionid'],
        'weixin_mp_register' => ['openid','user_name'],
        'edit' => ['user_name','sex','birth','email'],
        'shezhi' => ['phone','phonecode','password','repwd'],
        'find_back_pwd' => ['phone' => 'require|number','password','phonecode'],
        'edit_pwd' => ['phone' => 'require|number','password','phonecode'],
        'set_pay_pwd' => ['phone' => 'require|number','paypwd','phonecode'],
        'edit_phone' => ['phone' => 'require|number','phonecode'],
    ];

}