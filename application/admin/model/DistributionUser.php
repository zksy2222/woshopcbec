<?php

namespace app\admin\model;
use think\Model;
use think\Db;
use app\common\Lookup;

class DistributionUser extends Model{
    
    public function getDistribUserList($where='1') {
//        $where = array('a.is_distribution' => Lookup::isDistrib);
        if($where !=1){
            return Db::name('distribution_user')->alias('a')
                ->where('a.status','<>',3)
                ->field('a.id,a.user_id,b.user_name,a.phone,a.wxnum,b.headimgurl,a.real_name,c.grade_name,a.commission,a.status,a.grade_id,a.create_time')
                ->join('sp_member b', 'a.user_id = b.id', 'left')
                ->join('sp_distribution_grade c', 'a.grade_id = c.id', 'left')
//                ->where($where)
                ->order('a.create_time desc')
                ->select();
        }
        return Db::name('distribution_user')->alias('a')
                ->where('a.status','<>',3)
                ->field('a.id,a.user_id,b.user_name,a.phone,a.wxnum,b.headimgurl,a.real_name,c.grade_name,a.commission,a.status,a.grade_id,a.create_time')
                ->join('sp_member b', 'a.user_id = b.id', 'left')
                ->join('sp_distribution_grade c', 'a.grade_id = c.id', 'left')
//                ->where($where)
                ->order('a.create_time desc')
                ->paginate(Lookup::pageSize);
    }
    
    public function getLowerUserListByUserId($userid_arr, $level) {
        $userid_str = $this->getTotalUserId($userid_arr, $level);
        $userids_arr = explode(',', $userid_str);
        $userids_arr = array_filter($userids_arr);
        $where = array('a.user_id' => array('in', $userids_arr));
        return Db::name('distribution_user')->alias('a')
                ->field('a.id,a.user_id,b.user_name,b.phone,b.headimgurl,b.real_name,a.commission,a.status,a.create_time')
                ->join('sp_member b', 'a.user_id = b.id', 'left')
                ->where($where)
                ->order('a.create_time desc')
                ->select();
    }
    
    public function getTotalUserId($userid_arr, $level) {
        $userids = array();
        for($i = 1; $i <= $level; $i++) {
            $userids[] = $this->getLowerTotalUserId($userid_arr, $i);
        }
        $userIds = [];
        switch ($level){
            case 1:
                $userIds[] = $userids[0];
                break;
            case 2:
                $userIds[] = $userids[1];
                break;
            case 3:
                $userIds[] = $userids[2];
                break;
            case 10:
                $userIds = $userids;
                break;
        }
        return implode(',', $userIds);
    }
    
    public function getLowerTotalUserId($userid_arr, $level, $count = 0) {
        $where = array('user_pid' => array('in', $userid_arr));
        $userid_arr = DistributionUser::where($where)->column('user_id');
        $count++;
        if ($level != $count) {
            return $this->getLowerTotalUserId($userid_arr, $level, $count);
        }
        return implode(',', $userid_arr);
    }
    
    //递归 下级分销总人数
    public function getLowerTotalUser($userid_arr, $level, $count = 0) {
        $where = array('user_pid' => array('in', $userid_arr));
        $userid_arr = DistributionUser::where($where)->column('user_id');
        $count++;
        if ($level != $count) {
            return $this->getLowerTotalUser($userid_arr, $level, $count);
        }
        return count($userid_arr);
    }
    
    public function getTotalUser($userid_arr, $level) {
        $total_user = 0;
        for($i = 1; $i <= $level; $i++) {
            $total_user += $this->getLowerTotalUser($userid_arr, $i);
        }
        return $total_user;
    }
    
    public function getDiffLevelUser($userid_arr, $level) {
        if ($level == Lookup::levelOne) {
            $levelOne = $this->getLowerTotalUser($userid_arr, Lookup::levelOne);
            return array('levelOne' => $levelOne);
        } elseif ($level == Lookup::levelTwo) {
            $levelOne = $this->getLowerTotalUser($userid_arr, Lookup::levelOne);
            $levelTwo = $this->getLowerTotalUser($userid_arr, Lookup::levelTwo);
            return array('levelOne' => $levelOne, 'levelTwo' => $levelTwo);
        } elseif ($level == Lookup::levelThree) {
            $levelOne = $this->getLowerTotalUser($userid_arr, Lookup::levelOne);
            $levelTwo = $this->getLowerTotalUser($userid_arr, Lookup::levelTwo);
            $levelThr = $this->getLowerTotalUser($userid_arr, Lookup::levelThree);
            return array('levelOne' => $levelOne, 'levelTwo' => $levelTwo, 'levelThr' => $levelThr);
        }
    }
    
    public function getUserLevel($userId, $userPid, $level){
        $user_level = 0;
        for($i = 1; $i <= $level; $i++) {
            $user_level = $this->getUserLevelRecursion($userId, $userPid, $i);
        }
        return $user_level;
    }
    
    public function getUserLevelRecursion($userId, $userPid, $level, $user_level = 0) {
        $where = array('user_id' => $userId);
        $userpid = DistributionUser::where($where)->value('user_pid');
        $user_level++;
        if ($userPid != $userpid && $level != $user_level) {
            return $this->getUserLevelRecursion($userpid, $userPid, $level, $user_level);
        }
        return $user_level;
    }

    public function getDistribUserByUserId($userId) {
        $where = array('a.user_id' => $userId);
        return Db::name('distribution_user')->alias('a')
                ->field('a.*,b.user_name')
                ->join('sp_member b', 'a.user_id = b.id', 'left')
                ->where($where)->find();
    }
    
    public function withdrawSetInc($id, $amount, $field) {
        return DistributionUser::where('id', $id)->setInc($field, $amount);
    }
    
    public function withdrawSetDec($id, $amount, $field) {
        return DistributionUser::where('id', $id)->setDec($field, $amount);
    }
    
    public function getCreateTimeAttr($time) {
        return $time;
    }
    
}
