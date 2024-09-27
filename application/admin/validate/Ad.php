<?php
namespace app\admin\validate;
use think\Validate;

class Ad extends Validate
{
    protected $rule = [
        'ad_name' => 'require|unique:ad|max:30',
        'cate_id' => 'require',
        'ad_pic' => 'require',
    ];

    protected $message = [
        'ad_name.require' => '广告名称不能为空',
        'ad_name.unique' => '广告名称已存在',
        'ad_name.max' => '广告名称最多30个字符',
        'cate_id.require' => '广告类型不能为空',
        'ad_pic.require' => '请上传广告图片',
    ];
    

}