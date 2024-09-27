<?php

namespace app\api\model;

use think\Model;

class Wallet extends Model
{
    // 获取用户钱包金额
    public function getUserMoney($userId){
        return $this->where('user_id',$userId)->value('price');
    }
}
