<?php

namespace addons\country\validate;
use think\Validate;

class Country extends Validate {
    
    protected $rule = [
        'country_cname' => 'require|unique:country',
        'country_ename' => 'require|unique:country',
        'country_bname' => 'require|unique:country',
        'country_code' => 'require|unique:country',
        'country_initials' => 'require',
        'lang_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'currency_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/']
    ];

    protected $message = [
        'country_cname.require' => '国家中文名称不能为空',
        'country_cname.unique' => '国家中文名称已存在',
        'country_ename.require' => '国家英文名称不能为空',
        'country_ename.unique' => '国家英文名称已存在',
        'country_bname.require' => '国家本国名称不能为空',
        'country_bname.unique' => '国家本国名称已存在',
        'country_code.require' => '国家代码不能为空',
        'country_code.unique' => '代码代码已存在',
        'country_initials.require' => '英文首字母不能为空',
	    'lang_id.require' => '缺少语言参数',
		'lang_id.unique' => '缺少语言参数',
        'currency_id.require' => '缺少货币参数',
        'currency_id.unique' => '缺少货币参数',
    ];
}
