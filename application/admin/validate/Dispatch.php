<?php

namespace app\admin\validate;

use think\Validate;

class Dispatch extends Validate
{
    protected $rule = [
        'citys'            => 'require',
        'citys_code'       => 'require',
        'first_weight'     => 'require|number|gt:0',
        'first_price'      => 'require|float|gt:0',
        'second_weight'    => 'require|number|gt:0',
        'second_price'     => 'require|float|gt:0',
        'first_num_price'  => 'require|float|gt:0',
        'first_num'        => 'require|number|gt:0',
        'second_num_price' => 'require|float|gt:0',
        'second_num'       => 'require|number|gt:0',
    ];

    protected $message = [
        'citys.require'            => '城市不能为空',
        'citys_code.require'       => '城市编码不能为空',
        'first_price.require'      => '首费不能为空',
        'first_price.float'        => '首费只能为数字',
        'first_price.gt'           => '首费必须大于0',
        'first_weight.require'     => '首重不能为空',
        'first_weight.number'      => '首重只能为数字',
        'first_weight.gt'          => '首重必须大于0',
        'second_price.require'     => '续费不能为空',
        'second_price.float'       => '续费只能为数字',
        'second_price.gt'          => '续费必须大于0',
        'second_weight.require'    => '续重不能为空',
        'second_weight.number'     => '续重只能为数字',
        'second_weight.gt'         => '续重必须大于0',
        'first_num_price.require'  => '首件费用不能为空',
        'first_num_price.float'    => '首件费用只能为数字',
        'first_num_price.gt'       => '首件费用必须大于0',
        'first_num.require'        => '首件不能为空',
        'first_num.number'         => '首件只能为数字',
        'first_num.gt'             => '首件必须大于0',
        'second_num_price.require' => '续件费用不能为空',
        'second_num_price.float'   => '续件费用只能为数字',
        'second_num_price.gt'      => '续件费用必须大于0',
        'second_num.require'       => '续件不能为空',
        'second_num.number'        => '续件只能为数字',
        'second_num.gt'            => '续件必须大于0',
    ];

    protected $scene = [
        'check_weight_price'         => ['citys', 'citys_code', 'first_price', 'first_weight', 'second_price', 'second_weight'],
        'check_num_price'            => ['citys', 'citys_code', 'first_num_price', 'first_num', 'second_num_price', 'second_num'],
        'check_weight_price_default' => ['first_price', 'first_weight', 'second_price', 'second_weight'],
        'check_num_price_default'    => ['first_num_price', 'first_num', 'second_num_price', 'second_num']
    ];

}