<?php

namespace app\api\model;

use think\Model;

class Withdraw extends Model
{
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;

    // 获取当月提现次数
    public function getMonthWithdrawCount($userId,$type){
        return $this->where(['user_id'=>$userId,'type'=>$type])->where('checked','<>',2)->where('complete','<>',2)->whereTime('create_time','m')->count();
    }
}
