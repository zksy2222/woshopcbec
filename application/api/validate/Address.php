<?php
namespace app\api\validate;
use think\Validate;

class Address extends Validate
{
    protected $rule = [
        'contacts' => 'require|length:2,10',
        'phone' => 'require|number',
//        'pro_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
//        'city_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
//        'area_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'province'=>'require',
        'city'=>'require',
        'area'=>'require',
        'address'=>'require',
        'is_default'=>'require|in:0,1'
    ];

    protected $message = [
        'contacts.require' => '姓名不能为空',
        'contacts.length' => '姓名为2到10个字符',
        'phone.require' => '手机号不能为空',
        'phone.number' => '手机号格式不正确',
//        'pro_id.require' => '请选择省份',
//        'pro_id.regex' => '省份参数错误',
//        'city_id.require' => '请选择城市',
//        'city_id.regex' => '城市参数错误',
//        'area_id.require' => '请选择区县',
//        'area_id.regex' => '区县参数错误',
        'province.require' => '省份不能为空',
        'city.require' => '城市不能为空',
        'area.require' => '区域不能为空',
        'address.require' => '地址不能为空',
        'is_default' => '请选择是否为默认地址',
        'is_default.in' => '设置为默认地址参数错误',
    ];
    
    
}