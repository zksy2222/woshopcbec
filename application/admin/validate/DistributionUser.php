<?php
namespace app\admin\validate;
use think\Validate;

class DistributionUser extends Validate
{
    protected $rule = [
        'user_id' => 'require|unique:distribution_user',
        'real_name' => 'require|length:2,5',
        'grade_id' => 'require|number',
        'status' => 'in:0,1,2',
    ];

    protected $message = [
        'user_id.require' => '用户ID参数错误',
        'user_id.unique' => '用户ID必须唯一',
        'grade_id.require' => '分销商等级参数错误',
        'grade_id.number' => '分销商等级参数错误',
        'real_name.require' => '真实姓名不能为空',
        'real_name.length' => '真实姓名为2到5个字符',
        'status.in' => '审核状态参数错误',
    ];

}