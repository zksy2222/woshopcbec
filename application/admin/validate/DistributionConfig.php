<?php
namespace app\admin\validate;
use think\Validate;

class DistributionConfig extends Validate
{
    protected $rule = [
        'is_open' => 'require|in:0,1',
        'level' => 'require|in:1,2,3',
        'become_child' => 'require|in:1,2,3',
    ];

    protected $message = [
        'is_open.require' => '请选择是否开启分销',
        'is_open.in' => '开启分销参数错误',
        'level.require' => '请选择分销层级',
        'level.in' => '分销层级参数错误',
        'become_child.require' => '请选择成为下线条件',
        'become_child.in' => '成为下线条件参数错误',
    ];

}