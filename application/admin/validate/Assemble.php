<?php
namespace app\admin\validate;
use think\Validate;

class Assemble extends Validate
{
    protected $rule = [
        'pin_name' => 'require|max:20',
        'goods_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'price' => ['require','regex'=>'/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/','gt'=>0],
        'stock'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'pin_num' => ['require','regex'=>'/^\+?[1-9][0-9]*$/','elt'=>10],
        'start_time' => 'require',
        'end_time' => 'require',
        'remark' => 'max:100',
        'shop_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
    ];

    protected $message = [
        'pin_name.require' => '活动名称不能为空',
        'pin_name.max' => '活动名称最多20个字符',
        'goods_id.require' => '请选择商品',
        'goods_id.regex' => '商品参数错误',
        'price.require' => '拼团价格不能为空',
        'price.regex' => '拼团价格格式错误',
        'price.gt' => '拼团价格需大于0',
        'stock.require' => '拼团库存不能为空',
        'stock.regex' => '拼团库存一定要为正整数',
        'pin_num.require' => '请填写几人团数量',
        'pin_num.regex' => '几人团参数错误',
        'pin_num.elt' => '几人团不能大于10人',
        'start_time.require' => '开始时间不能为空',
        'end_time.require' => '结束时间不能为空',
        'remark.max' => '活动描述最多100个字符',
        'shop_id.require' => '缺少商家参数',
        'shop_id.regex' => '缺少商家参数',
    ];

    protected $scene = [
        'hasoption' => ['pin_name,goods_id,pin_num,start_time,end_time'],
        'nooption' => ['pin_name,price,stock,pin_num,goods_id,start_time,end_time']
    ];

}