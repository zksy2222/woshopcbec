<?php
namespace app\admin\validate;
use think\Validate;

class News extends Validate
{
    protected $rule = [
        'author' => 'require',
        'source' => 'require',
        'cate_id' => 'require',
        'sort' => 'require|number',
	    'tag' => 'unique:news',
    ];

    protected $message = [
        'author.require' => '作者不能为空',
        'source.require' => '出处不能为空',
        'cate_id.require' => '请选择分类栏目',
        'sort.require' => '排序不能为空！',
        'sort.number' => '排序一定要为数字！',
	    'tag.unique' => '标识不能重复',
    ];

}