<?php

namespace addons\lang\validate;
use think\Validate;

class Lang extends Validate {
    
    protected $rule = [
        'lang_name' => 'require|unique:lang',
        'lang_code' => 'require|unique:lang',
        'lang_code_front' => 'require|unique:lang',
    ];

    protected $message = [
        'lang_name.require' => '语言名称不能为空',
        'lang_name.unique' => '语言名称已存在',
        'lang_code.require' => '后端语言代码不能为空',
        'lang_code.unique' => '后端语言代码已存在',
        'lang_code_front.require' => '后端语言代码不能为空',
        'lang_code_front.unique' => '后端语言代码已存在',
    ];
}
