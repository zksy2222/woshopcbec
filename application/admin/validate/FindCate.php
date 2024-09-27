<?php
namespace app\admin\validate;
use think\Validate;

class FindCate extends Validate
{
    protected $rule = [
        'name' => 'require|unique:find_cate',
        'title' => 'require|unique:find_cate',
        'sort' => 'require|number',
    ];

    protected $message = [
        'name.require' => '名称不能为空',
        'name.unique' => '分类名称已存在',
        'title.unique' => '标题已存在',
        'title.require' => '标题不能为空',
        'sort.require' => '排序不能为空',
        'sort.number' => '排序必须为数字',
    ];

}