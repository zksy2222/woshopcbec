<?php
namespace app\admin\validate;
use think\Validate;

class NavMenu extends Validate

{
    protected $rule = [
        'menu_name' => 'require|max:20',
        'menu_url'=>'require',
        'image_path' => 'require',
        'sort'=>['require','regex'=>'/^[0-9]+$/'],
        'nav_id' => 'require|number',
    ];

    protected $message = [
        'menu_name.require' => '菜单名称不能为空',
        'menu_name.max' => '菜单名称最多20个字符',
        'menu_url.require' => 'url地址不能为空',
        'image_path.require' => '请上传icon图片',
        'sort.require' => '排序不能为空',
        'sort.regex' => '排序为整数',
        'nav_id.require' => '所属导航位参数错误',
        'nav_id.number' => '所属导航位参数错误',
    ];
    

}