<?php
namespace app\admin\validate;
use think\Validate;

class LiveGift extends Validate
{
    protected $rule = [
        'cid' => 'require',
        'name'=>'require',
        'gift_coin'=>'require|integer',
        'pic' => 'require',
    ];

    protected $message = [
        'cid.require' => '请选择礼物分类',
        'name.require' => '礼物名称不能为空',
        'gift_coin.require' => '金币不能为空',
        'gift_coin.integer' => '金币必须为整数',
        'pic.require' => '礼物图片不能为空',
    ];

}