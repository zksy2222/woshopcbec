<?php

namespace app\api\validate;
use think\Validate;

class DistributionUser extends Validate {
    protected $rule = [
        'real_name' => 'require|length:2,5',
        'phone' => 'require|regex:/^1[3456789]\d{9}$/',
        'wxnum' => 'require',
    ];

    protected $message = [
        'real_name.require' => '姓名不能为空',
        'real_name.length' => '姓名为2到5个字符',
        'phone.require' => '手机号不能为空',
        'phone.regex' => '手机号格式错误',
        'wxnum.require' => '微信号不能为空',
    ];
}
