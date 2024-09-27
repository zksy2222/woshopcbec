<?php

namespace app\common\model;
use think\Model;

class DistributionTempuser extends Model {
    
    public function getTempUser($userId, $userPid) {
        $where = array('user_id' => $userId, 'user_pid' => $userPid);
        return DistributionTempuser::where($where)->find();
    }
}
