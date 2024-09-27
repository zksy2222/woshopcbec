<?php
namespace app\api\validate;
use think\Validate;

class ComapplyInfo extends Validate
{
    protected $rule = [
        'indus_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'shop_name' => 'require|unique:shops',
        'shop_desc'=>'require|max:50',
        'cate_ids'=> 'require',
        'contacts' => 'require',
        'telephone' => 'require|number',
        'sfz_num'=>'require',
        // 'pro_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        // 'city_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        // 'area_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'province'=>'require',
        'city'=>'require',
        'area'=>'require',
        'address'=>'require|max:50',
    ];

    protected $message = [
        'indus_id.require' => '请选择行业',
        'indus_id.regex' => '请选择行业',
        'shop_name.require' => '店铺名称不能为空',
        'shop_name.unique' => '店铺名称已存在',
        'shop_desc.require' => '商铺描述不能为空',
        'shop_desc.max' => '店铺描述最多50个字符',
        'cate_ids.require' => '请选择经营类目信息',
        'contacts.require' => '联系人姓名不能为空',
        'telephone.require' => '手机号不能为空',
        'telephone.number' => '手机号格式不正确',
        'sfz_num.require' => '身份证号不能为空',
        // 'pro_id.require' => '请选择省份',
        // 'pro_id.regex' => '请选择省份',
        // 'city_id.require' => '请选择城市',
        // 'city_id.regex' => '请选择城市',
        // 'area_id.require' => '请选择区县',
        // 'area_id.regex' => '请选择区县',
        'province.require' => '请填写所在省份',
        'city.require' => '请填写所在城市',
        'area.require' => '请填写所在地区',
        'address.require' => '商铺详细地址不能为空',
        'address.max' => '商铺详细地址最多50个字符',
    ];


}
