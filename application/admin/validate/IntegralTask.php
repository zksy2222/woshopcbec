<?php
namespace app\admin\validate;
use think\Validate;

class IntegralTask extends Validate
{
    protected $rule = [
        'cate_id' => 'require|number',
        'task_name' => 'require|unique:integral_task',
        'integral' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'sort' => ['require','regex'=>'/^\+?[0-9][0-9]*$/'],
    ];

    protected $message = [
        'cate_id.require' => '请选择任务分类',
        'cate_id.number' => '任务分类参数错误',
        'task_name.require' => '任务名称不能为空',
        'task_name.unique' => '任务分类名称已存在',
        'integral.require' => '积分不能为空',
        'integral.regex' => '积分请填写正整数',
        'sort.require' => '排序不能为空',
        'sort.regex' => '排序请填写正整数',
    ];

}