<?php

namespace app\admin\validate;
use think\Validate;

class Lang extends Validate {
    
    protected $rule = [
        'lang_name' => 'require|unique:lang',
        'lang_code' => 'require|unique:lang',
    ];

    protected $message = [
        'lang_name.require' => '语言名称不能为空',
        'lang_name.unique' => '语言名称已存在',
        'lang_code.require' => '语言代码不能为空',
        'lang_code.unique' => '语言代码已存在',
    ];
}
