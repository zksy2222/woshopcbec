<?php
namespace app\admin\validate;
use think\Validate;

class Agent extends Validate
{
    protected $rule = [
        'user_id' => 'require',
        'divide' => 'require|between:0,100',
    ];

    protected $message = [
        'user_id.require' => '会员不能为空',
        'divide.require' => '分成比例不能为空',
        'divide.between' => '分成比例只能在0到100之间',
    ];
    

}