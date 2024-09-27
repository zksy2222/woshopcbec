<?php
namespace app\admin\validate;
use think\Validate;

class UsdtConfig extends Validate

{
    protected $rule = [
        // 'pic_id' => 'require',
        'TRC20_wallet' => 'require',
        // 'pic_id1' => 'require',
        'ERC20_wallet' => 'require',
    ];

    protected $message = [
        // 'pic_id.require' => '缺少TRC20收款码',
        'TRC20_wallet.require' => '缺少TRC20钱包地址',
        // 'pic_id1.require' => '缺少ERC20收款码',
        'ERC20_wallet.require' => '缺少ERC20钱包地址'
    ];


}