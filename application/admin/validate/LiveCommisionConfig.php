<?php
namespace app\admin\validate;
use think\Validate;

class LiveCommisionConfig extends Validate
{
    protected $rule = [
        'gift_commision_rate' => 'require',
        'goods_commision_rate' => 'require',
        'shop_id' => 'require|number|unique:LiveCommisionConfig',
    ];

    protected $message = [
        'gift_commision_rate.require' => '礼物分成比例不能为空',
        'goods_commision_rate.require' => '商品销售分成比例不能为空',
        'shop_id.require' => '店铺id不能为空',
        'shop_id.unique' => '该店铺已设置，请勿重复设置',
        'shop_id.number' => '店铺id一定要为数字！',
    ];

}