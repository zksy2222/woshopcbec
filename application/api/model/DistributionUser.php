<?php
namespace app\api\model;
use think\Model;
use app\common\Lookup;
use think\Db;

class DistributionUser extends Model {
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;

    protected function getCreateTimeAttr($time) {
        return date('Y-m-d H:i:s',$time);
    }

    // 获取分销商信息
    public function getDistributionUserInfo($userId, $field = '*') {
        $where = array('user_id' => $userId);
        return DistributionUser::field($field)->where($where)->find();
    }
    
    public function getDistribUserParentInfo($userId) {
        $where = array('user_id' => $userId, 'status' => Lookup::checkPass, 'is_distribution' => Lookup::isDistrib);
        return DistributionUser::where($where)->find();
    }
    
    public function isDistributionUser($userId) {
        $where = array('user_id' => $userId, 'status' => Lookup::checkPass, 'is_distribution' => Lookup::isDistrib);
        return DistributionUser::where($where)->find();
    }
    
    public function getDistribGradeInfo($userId) {
        $where = array('a.user_id' => $userId, 'a.status' => Lookup::checkPass, 'a.is_distribution' => Lookup::isDistrib);
        return Db::name('distribution_user')->alias('a')
                ->field('a.id,b.one_level_rate,b.two_level_rate,b.three_level_rate')
                ->join('sp_distribution_grade b', 'a.grade_id=b.id', 'left')
                ->where($where)
                ->find();
    }
    
    public function updateCommission($id, $commission) {
        return DistributionUser::where('id', $id)->setInc('commission', $commission);
    }
    
    public function updateWithdrawAmount($id, $commission) {
        return DistributionUser::where('id', $id)->setInc('withdraw_amount', $commission);
    }
    
    //分销商个人中心数据
    public function getDistribCenterData($userId) {
        $where = array('a.user_id' => $userId, 'a.status' => Lookup::checkPass, 'a.is_distribution' => Lookup::isDistrib);
        return Db::name('distribution_user')->alias('a')
                ->field('a.user_pid,a.commission,a.withdraw_amount withdrawal,a.success_amount withdrawal_success,b.grade_name,c.phone,c.headimgurl')
                ->join('sp_distribution_grade b', 'a.grade_id=b.id', 'left')
                ->join('sp_member c', 'a.user_id=c.id', 'left')
                ->where($where)
                ->find();
    }
    
    public function getLowerUserInfo($userIdArr) {
        $where = array('a.user_id' => array('in', $userIdArr));
        $userList = self::alias('a')
                ->field('a.id,a.user_id,b.user_name,b.phone,b.headimgurl,b.real_name,a.commission,a.status,a.create_time')
                ->join('sp_member b', 'a.user_id = b.id', 'left')
                ->where($where)
                ->order('a.create_time desc')
                ->select();
        foreach ($userList as $k=>$v){
            $v['headimgurl'] = url_format($v['headimgurl'],get_config_value('weburl'));
        }
        return $userList;

    }
    
    //递归 下级分销总人数
    public function getLowerTotalUser($userid_arr, $level, $count = 0, $column = false) {
        $where = array('user_pid' => array('in', $userid_arr));
        $userid_arr = DistributionUser::where($where)->column('user_id');
        $count++;
        if ($level != $count) {
            return $this->getLowerTotalUser($userid_arr, $level, $count, $column);
        }
        if (!$column) {
            return count($userid_arr);
        }
        return $userid_arr;
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
    
    //获取分销商所有下级
    public function getTotalUserId($userid_arr, $level) {
        $userids = array();
        for($i = 1; $i <= $level; $i++) {
            $userids[] = $this->getLowerTotalUserId($userid_arr, $i);
            
        }
        $userids_str = implode(',', $userids);
        $userids_arr = explode(',', $userids_str);
        return array_filter($userids_arr);
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

    //获取分销佣金余额
    public function getDistributionUserMoney($userId){
        return $this->where('user_id',$userId)->value('withdraw_amount');
    }
}
