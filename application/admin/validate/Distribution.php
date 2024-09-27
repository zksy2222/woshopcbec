<?php
namespace app\admin\validate;
use think\Validate;

class Distribution extends Validate

{
    protected $rule = [
        'is_open' => 'require|in:0,1',
        'one_profit' => ['require','regex'=>'/^\+?[1-9][0-9]*$/','egt'=>0,'elt'=>100],
        'two_profit' => ['require','regex'=>'/^\+?[1-9][0-9]*$/','egt'=>0,'elt'=>100],
    ];

    protected $message = [
        'is_open.require' => '请选择是否开启分销',
        'is_open.in' => '是否开启分销参数错误',
        'one_profit.require' => '缺少一级上线订单分成参数',
        'one_profit.regex' => '一级上线订单分成参数格式错误',
        'one_profit.egt' => '一级上线订单分成参数需在0到100之间',
        'one_profit.elt' => '一级上线订单分成参数需在0到100之间',
        'two_profit.require' => '缺少二级上线订单分成参数',
        'two_profit.regex' => '二级上线订单分成参数格式错误',
        'two_profit.egt' => '二级上线订单分成参数需在0到100之间',
        'two_profit.elt' => '二级上线订单分成参数需在0到100之间',
    ];
    

}