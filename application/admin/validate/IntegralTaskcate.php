<?php
namespace app\admin\validate;
use think\Validate;

class IntegralTaskcate extends Validate
{
    protected $rule = [
        'cate_name' => 'require|unique:integral_taskcate',
    ];

    protected $message = [
        'cate_name.require' => '任务类别不能为空',
        'cate_name.unique' => '任务类别名称已存在',
    ];

}