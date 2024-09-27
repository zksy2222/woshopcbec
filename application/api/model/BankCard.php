<?php
namespace app\api\model;
use think\Model;

class BankCard extends Model {
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;

    public function getBankCardInfo($userId) {
        return $this->where('user_id', $userId)->field('id,card_number,name,card_number,bank_name,province,city,area,branch_name')->find();
    }

    public function getBackCardCount($userId){
        return $this->where('user_id',$userId)->count();
    }
}