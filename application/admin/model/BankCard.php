<?php

namespace app\admin\model;
use think\Model;

class BankCard extends Model{
    
    public function getBankCardInfo($id) {
        return BankCard::where('id', $id)->find();
    }
}
