<?php

namespace app\api\model;
use think\Model;

class MemberIntegral extends Model{
    
    public function getIntegralRecord($userId, $type) {
        if($type == 4){
            $where = array('user_id' => $userId, 'type' => $type, 'class' => 0);
            return MemberIntegral::where($where)->count();
        }elseif ($type == 1){
            $where = array('user_id' => $userId,'class' => 0);
            return MemberIntegral::where($where)->whereIn("type",[11,12])->whereTime('addtime', 'today')->count();
        }else{
            $where = array('user_id' => $userId, 'type' => $type, 'class' => 0);
            return MemberIntegral::where($where)->whereTime('addtime', 'today')->count();
        }
    }
    
    public function getIntegralRecordByUserId($userId, $offset, $pageSize) {
        $where = array('user_id' => $userId, 'class' => 0);
        return MemberIntegral::field('id,integral,type,class,FROM_UNIXTIME(addtime) addtime')->where($where)->order('id desc')->limit($offset, $pageSize)->select();
    }
}
