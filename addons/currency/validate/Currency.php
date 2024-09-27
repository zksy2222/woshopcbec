<?php

namespace addons\currency\validate;
use think\Validate;

class Currency extends Validate {
    
    protected $rule = [
        'currency_name' => 'require|unique:currency',
        'currency_symbol' => 'require|unique:currency',
        'currency_code' => 'require|unique:currency',
    ];

    protected $message = [
        'currency_name.require' => '货币名称不能为空',
        'currency_name.unique' => '货币名称已存在',
        'currency_symbol.require' => '货币符号不能为空',
        'currency_symbol.unique' => '货币符号已存在',
        'currency_code.require' => '货币代码不能为空',
        'currency_code.unique' => '货币代码已存在',
    ];
}
