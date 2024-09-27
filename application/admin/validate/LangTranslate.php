<?php

namespace app\admin\validate;
use think\Validate;

class LangTranslate extends Validate {
    
    protected $rule = [
        'key_name' => 'require|unique:lang_key'
    ];

    protected $message = [
        'key_name.require' => 'Lang键名不能为空',
        'key_name.unique' => 'Lang键名已存在'
    ];
}
