<?php

namespace app\common\model;
use think\Model;
use app\common\Lookup;
use app\common\model\DistributionTempuser;

class DistributionUser extends Model {
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    
    public function getDistribUserInfo($userId) {
        $where = array('user_id' => $userId);
        return self::where($where)->find();
    }
    
    public function getDistribUserParentInfo($userId) {
        $where = array('user_id' => $userId,'status' => Lookup::checkPass, 'is_distribution' => Lookup::isDistrib);
        return self::where($where)->find();
    }

    /**
     * 绑定关系：首次点击分享链接
     * @param type $userId
     * @param type $userPid
     * @return boolean
     */
    public function bindDistribUser($userId, $userPid) {

        $user_parent = self::getDistribUserParentInfo($userPid);
        if (!$user_parent) {
            return false;
        }

        $user = self::getDistribUserInfo($userId);
        if (!$user) {
            $data = array(
                'user_id' => $userId,
                'user_pid' => $userPid,
                'is_distribution' => Lookup::isNotDistrib,
                'status' => 3 // 下级用户注册
            );
            $addResult = self::save($data);
            if (!$addResult) {
                return false;
            }
            return true;
        }

        if ($user && empty($user['user_pid'])) {
            $data = array('user_pid' => $userPid);
            $updateResult = self::update($data, array('id' => $user['id']));
            if (!$updateResult) {
                return false;
            }
            return true;
        }
    }

    //无条件成为分销商
    public function insertDistribUser($userId) {
        $user = self::getDistribUserInfo($userId);
        if (!$user) {
            $data = array(
                'user_id' => $userId,
                'status' => Lookup::checkPass,
                'is_distribution' => Lookup::isDistrib
            );
            $addResult = self::save($data);
            if (!$addResult) {
                return false;
            }
            return true;
        } else {
            if ($user['is_distribution'] == Lookup::isNotDistrib) {
                $data = array(
                    'is_distribution' => Lookup::isDistrib,
                    'status' => Lookup::checkPass,
                    'create_time' => date('Y-m-d H:i:s')
                );
                $updateResult = self::update($data, array('id' => $user['id']));
                if (!$updateResult) {
                    return false;
                }
                return true;
            }
            return false;
        }
    }

    //首次下单、首次付款 上下级关系临时存储表
    public function bindDistribTempUser($userId, $userPid) {
        $tempUserModel = new DistributionTempuser();
        $temp_user = $tempUserModel->getTempUser($userId, $userPid);
        if ($temp_user) {
            return false;
        }
        $data = array(
            'user_id' => $userId,
            'user_pid' => $userPid
        );
        $addResult = $tempUserModel->save($data);
        if (!$addResult) {
            return false;
        }
        return true;
    }
}
