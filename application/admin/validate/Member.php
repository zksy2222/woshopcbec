<?php
namespace app\admin\validate;
use think\Validate;

class Member extends Validate

{
    protected $rule = [
        'user_name' => 'require|unique:member|chsAlphaNum|length:2,10',
        'phone' => 'require|unique:member',
        'wz_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'password'=> 'require|chsAlphaNum|length:6,20',
        'paypwd'=> 'require|number|length:6',
        'email' => 'email',
        'wxnum' => 'max:60',
        'qqnum' => 'max:60',
        'checked'=> 'require|in:0,1',
    ];

    protected $message = [
        'user_name.require' => '姓名不能为空',
        'user_name.unique' => '手机号已存在',
        'user_name.chsAlphaNum' => '姓名只能为汉字、字母和数字',
        'user_name.length' => '姓名只能为2到5位汉字',
        'phone.require' => '手机号不能为空',
        'phone.unique' => '手机号已存在',
        'wz_id.require' => '请选择销售职位',
        'wz_id.regex' => '销售职位参数错误',
        'password.require' => '密码不能为空',
        'password.chsAlphaNum' => '密码只能是汉字、字母和数字',
        'password.length' => '密码只能是6-20位汉字、字母和数字',
        'paypwd.require' => '支付密码不能为空',
        'paypwd.number' => '支付密码只能是6位数字',
        'paypwd.length' => '支付密码只能是6位数字',
        'email.email' => '邮箱格式错误',
        'wxnum.max' => '微信号最多20位',
        'qqnum.max' => 'qq号最多20位',
        'checked.require' => '请选择开启或关闭',
        'checked.in' => '选择开启或关闭参数错误',
    ];

    protected $scene = [
        'useradd' => ['user_name','phone','password','paypwd'],
        'useredit' => ['phone','password'=> 'chsAlphaNum|length:6,20','paypwd'=> 'number|length:6'],
        'saleadd'   =>  ['user_name','phone','wz_id','password','email','wxnum','qqnum','checked'],
        'saleedit'  =>  ['user_name','phone'=>'require','wz_id','password'=>'number|length:6','email','wxnum','qqnum','checked'],
        'strationadd' => ['user_name','phone','wz_id','password','email','wxnum','qqnum','checked'],
        'strationedit'  =>  ['user_name','phone'=>'require','wz_id','password'=>'number|length:6','email','wxnum','qqnum','checked'],
        'masteradd' => ['user_name','phone','password','email','wxnum','qqnum','checked'],
        'masteredit'  =>  ['user_name','phone'=>'require','password'=>'number|length:6','email','wxnum','qqnum','checked'],
    ];
    

}