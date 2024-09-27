<?php
namespace app\api\validate;
use think\Validate;

class BankCard extends Validate
{
    protected $rule = [
        'name' => 'require',
        'telephone' => 'require',
        'card_number' => 'require',
        'bank_name' => 'require|max:100',
        'province' => 'require|max:100',
        'branch_name' => 'require|max:100',
    ];

    protected $message = [
        'name.require' => '真实姓名不能为空',
        'telephone.require' => '手机号不能为空',
        'card_number.require' => '请填写银行卡号',
        'bank_name.require' => '请填写所属银行名称',
        'bank_name.max' => '银行名称最多20个字符',
        'province.require' => '请填写省份名称',
        'province.max' => '省份名称最多20个字符',
        'branch_name.require' => '请填写所属支行',
        'branch_name.max' => '支行名称最多30个字符',
    ];

}