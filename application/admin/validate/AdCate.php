<?php

namespace app\admin\validate;

use think\Validate;

class AdCate extends Validate
{
    protected $rule = [
        'cate_name' => 'require|unique:ad_cate|max:60',
        'tag'       => 'require|unique:ad_cate',
        'width'     => 'require',
        'height'    => 'require',
    ];

    protected $message = [
        'cate_name.require' => '广告位名称不能为空',
        'cate_name.unique'  => '广告位名称已存在',
        'tag.require'       => '广告位标识不能为空',
        'tag.unique'        => '广告位标识已存在',
        'width.require'     => '广告位宽度不能为空',
        'height.require'    => '广告位高度不能为空',
    ];


}