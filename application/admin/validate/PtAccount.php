<?php
namespace app\admin\validate;
use think\Validate;

class PtAccount extends Validate
{
    protected $rule = [
        'account_content' => 'require',
    ];

    protected $message = [
        'ad_name.require' => '平台账户信息不能为空',
    ];
    

}