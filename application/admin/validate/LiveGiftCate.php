<?php
namespace app\admin\validate;
use think\Validate;

class LiveGiftCate extends Validate
{
    protected $rule = [
        'cate_name' => 'require|unique:live_gift_cate',
    ];

    protected $message = [
        'cate_name.require' => '分类名称不能为空',
        'cate_name.unique' => '分类名称已存在',
    ];


}