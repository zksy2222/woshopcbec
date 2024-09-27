<?php
namespace app\admin\validate;
use think\Validate;

class DistributionGrade extends Validate
{
    protected $rule = [
        'grade_name' => 'require',
        'one_level_rate' => 'require|number',
        'two_level_rate' => 'require|number',
        'three_level_rate' => 'require|number',
    ];

    protected $message = [
        'grade_name.require' => '请填写等级名称',
        'one_level_rate.require' => '请填写一级佣金比例',
        'one_level_rate.number' => '一级佣金比例请填写数字',
        'two_level_rate.require' => '请填写二级佣金比例',
        'two_level_rate.number' => '二级佣金比例请填写数字',
        'three_level_rate.require' => '请填写三级佣金比例',
        'three_level_rate.number' => '三级佣金比例请填写数字',
    ];

}