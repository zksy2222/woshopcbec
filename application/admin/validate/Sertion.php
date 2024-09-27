<?php
namespace app\admin\validate;
use think\Validate;

class Sertion extends Validate
{
    protected $rule = [
        'ser_name' => ['require'],
        'ser_remark' => 'require',
        'sort'=>['require','regex'=>'/^[0-9]+$/'],
        'is_show'=>'require|in:0,1',
    ];

    protected $message = [
        'ser_name.require' => '服务名称不能为空',
        'ser_remark.require' => '服务说明不能为空',
        'sort.require' => '排序不能为空！',
        'sort.regex' => '排序一定要为数字！',
        'is_show.require' => '请选择显示或隐藏',
        'is_show.in' => '显示或隐藏参数错误',
    ];

}