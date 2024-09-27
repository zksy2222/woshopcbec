<?php
namespace app\admin\validate;
use think\Validate;

class IntegralShop extends Validate
{
    protected $rule = [
        'activity_name' => 'require|max:20',
        'goods_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'price'=>['require','regex'=>'/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/','gt'=>0],
        'stock'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'xznum'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'start_time' => 'require',
        'end_time' => 'require',
        'remark' => 'max:100',
        'shop_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
    ];
    
    protected $message = [
        'activity_name.require' => '活动名称不能为空',
        'activity_name.max' => '活动名称最多20个字符',
        'goods_id.require' => '请选择商品',
        'goods_id.regex' => '商品参数错误',
        'price.require' => '积分换购价格不能为空',
        'price.regex' => '积分换购价格格式错误',
        'price.gt' => '积分换购价格需大于0',
        'stock.require' => '积分换购库存不能为空',
        'stock.regex' => '积分换购库存一定要为正整数',
        'xznum.require' => '限购数量不能为空',
        'xznum.regex' => '限购数量一定要为正整数',
        'start_time.require' => '开始时间不能为空',
        'end_time.require' => '结束时间不能为空',
        'remark.max' => '活动描述最多100个字符',
        'shop_id.require' => '缺少商家参数',
        'shop_id.regex' => '缺少商家参数',
    ];

    protected $scene = [
        'hasoption' => ['activity_name,goods_id,xznum,start_time,end_time'],
        'nooption' => ['activity_name,price,stock,goods_id,xznum,start_time,end_time']
    ];

}