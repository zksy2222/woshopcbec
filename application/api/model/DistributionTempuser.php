<?php

namespace app\api\model;
use think\Model;

class DistributionTempuser extends Model {
    
    public function getTempUser($userId, $userPid) {
        $where = array('user_id' => $userId, 'user_pid' => $userPid);
        return DistributionTempuser::where($where)->find();
    }
    
    public function getTempUserPid($userId) {
        return DistributionTempuser::where('user_id', $userId)->value('user_pid');
    }
}
