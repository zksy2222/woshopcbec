<?php
namespace app\api\validate;
use think\Validate;

class Withdraw extends Validate
{
    protected $rule = [
        'paypwd' => 'require|number|length:6',
        'type' => 'require|in:1,2',
        'price' => 'require|float',
        'user_id' => 'require',
        'card_number' => 'require',
        'zs_name' => 'require',
        'bank_name' => 'require',
        'shengshiqu' => 'require',
        'branch_name' => 'require',

    ];

    protected $message = [
        'type.require' => '缺少提现类型参数',
        'type.in' => '提现类型参数错误',
        'price.require' => '缺少提现金额参数',
        'price.float' => '提现金额格式错误',
        'user_id.require' => '缺少用户ID参数',
        'card_number.require' => '缺少银行卡号参数',
        'zs_name.require' => '缺少真实姓名参数',
        'bank_name.require' => '缺少银行名称参数',
        'shengshiqu.require' => '缺少银行卡省市区参数',
        'branch_name.require' => '缺少支行名称参数',
        'paypwd.require' => '缺少支付密码参数',
        'paypwd.number' => '支付密码必须为数字',
        'paypwd.length' => '支付密码为6位数字',
    ];

    protected $scene = [
        'get_withdraw_info' => ['type'],
        'do_withdraw' => ['paypwd','type','price','user_id','card_number','zs_name','bank_name','shengshiqu','branch_name']
    ];

}