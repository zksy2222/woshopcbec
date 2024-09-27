<?php

namespace app\api\controller;
use think\Controller;
use app\api\model\DistributionUser as DistributionUserModel;
use app\common\model\DistributionConfig as DistributionConfigModel;
use app\api\model\DistributionCommissonDetail as DistributionCommissonDetailModel;
use app\api\model\DistributionTempuser as DistributionTempuserModel;
use app\api\model\DistributionGrade as DistributionGradeModel;
use app\api\model\Order as OrderModel;
use app\common\Lookup;

class DistributionCommon extends Controller {


    //佣金计算
    public function commissionCalculation($userId, $total_fee, $order_id) {
        $configModel = new DistributionConfigModel();
        $config = $configModel->getDistributionConfig();
        if ($config['is_open'] == Lookup::isClose) {
            return false;
        }

        
        for($i = 1; $i <= $config['level']; $i++) {
            $userPid = $this->getUserPid($userId, $i);
            if ($userPid) {
                $this->commissionDetail($userId, $userPid, $total_fee, $order_id, $i);
            }
        }
        return true;
    }
    
    /**
     * 成为下级条件：根据条件绑定上下级关系
     * @param type $userId
     * @param type $become_child 2:首次下单 3:首次付款
     * @return boolean
     */
    public function bindDistribUser($userId, $become_child = 3) {
        
        $configModel = new DistributionConfigModel();
        $config = $configModel->getDistributionConfig();
        if ($config['is_open'] == Lookup::isClose) {
            return false;
        }
        
        if ($config['become_child'] != $become_child) {
            return false;
        }
        $tempUserModwl = new DistributionTempuserModel();
        $userPid = $tempUserModwl->getTempUserPid($userId);
        if (!$userPid) {
            return false;
        }
        
        $distribUserModel = new DistributionUserModel();
        $user_parent = $distribUserModel->getDistribUserParentInfo($userPid);
        if (!$user_parent) {
            return false;
        }
        
        $user = $distribUserModel->getDistributionUserInfo($userId);
        if (!$user) {
            $data = array(
                'user_id' => $userId,
                'user_pid' => $userPid,
                'is_distribution' => Lookup::isNotDistrib
            );
            $addResult = $distribUserModel->save($data);
            if (!$addResult) {
                return false;
            }
            $tempUserModwl->destroy(array('user_id' => $userId, 'user_pid' => $userPid));
            return true;
        }
        
        if ($user && empty($user['user_pid'])) {
            $data = array('user_pid' => $userPid);
            $updateResult = $distribUserModel->update($data, array('id' => $user['id']));
            if (!$updateResult) {
                return false;
            }
            $tempUserModwl->destroy(array('user_id' => $userId, 'user_pid' => $userPid));
            return true;
        }
    }
    
    //分销商等级升级
    public function upGradeDistribution($userId) {
        $distribbModel = new DistributionUserModel();
        $user = $distribbModel->isDistributionUser($userId);
        if (!$user) {
            return false;
        }
        $userid_arr[] = $userId;
        $configModel = new DistributionConfigModel();
        $gradeModel = new DistributionGradeModel();
        $orderModel = new OrderModel();
        $config = $configModel->getDistributionConfig();
        $gradeList = $gradeModel->getGradeList();
        foreach ($gradeList as $grade) {
            if ($grade['upgrade'] == Lookup::upgradeByUserCount) {
                $total_user = $distribbModel->getTotalUser($userid_arr, $config['level']);
                if ($total_user >= $grade['user_count']) {  //满足邀请人数，升级
                    $this->updateDistributionUserGrade($userId, $grade['id']);
                }
            } elseif ($grade['upgrade'] == Lookup::upgradeByConsumeAmount) {
                $userid_arr = $distribbModel->getTotalUserId($userid_arr, $config['level']);
                $total_price = $orderModel->getDistributionOrderTotalAmount($userid_arr);
                if ($total_price >= $grade['consume_amount']) {
                    $this->updateDistributionUserGrade($userId, $grade['id']);
                }
            } elseif ($grade['upgrade'] == Lookup::upgradeByGoodsId) {
                $order = $orderModel->getOrderByGoodsId($grade['goods_id'], $userId);
                if ($order) {
                    $this->updateDistributionUserGrade($userId, $grade['id']);
                }
            }
        }
        
    }
    
    private function getCommission($amount, $level_rate) {
        return round(($amount * $level_rate)/Lookup::percent, Lookup::roundPrecision);
    }
    
    private function commissionDetail($userId, $userPid, $total_fee, $order_id, $level) {
        $DistributionUserModel = new DistributionUserModel();
        $distribUserGrade = $DistributionUserModel->getDistribGradeInfo($userPid);
        if (!$distribUserGrade) {
            return false;
        }
        //更新当前用户分销商的佣金总数
        if ($level == Lookup::levelOne) {
            $level_rate = $distribUserGrade['one_level_rate'];
        } elseif ($level == Lookup::levelTwo) {
            $level_rate = $distribUserGrade['two_level_rate'];
        } elseif ($level == Lookup::levelThree) {
            $level_rate = $distribUserGrade['three_level_rate'];
        }
        $commission = $this->getCommission($total_fee, $level_rate);
//        $DistributionUserModel->updateCommission($distribUserGrade['id'], $commission);        //更新总佣金
//        $DistributionUserModel->updateWithdrawAmount($distribUserGrade['id'], $commission);    //更新可提现金额
        //记录佣金明细
        $data = array(
            'user_id'       => $userId,                         //当前用户
            'distrib_user_id'      => $userPid,                 //分拥用户id
            'distrib_id'    => $distribUserGrade['id'],         //分销商表id
            'order_id'      => $order_id,                       //总订单id
            'level'         => $level,                          //分销层级
            'amount'        => $commission,                     //所得佣金
            'total_fee'     => $total_fee,                      //支付金额
            'level_rate'    => $level_rate,                     //佣金比例
            'create_time'   => time()
        );
        $distribDetailModel = new DistributionCommissonDetailModel();
        $saveResult = $distribDetailModel->save($data);
        if (!$saveResult) {
            return false;
        }
        return true;
    }
    
    private function getUserPid($userId, $level, $count = 0) {
        $DistributionUserModel = new DistributionUserModel();
        $distribUser = $DistributionUserModel->getDistributionUserInfo($userId);
        if (!$distribUser) {
            return false;
        } else {
            if (empty($distribUser['user_pid'])) {
                return false;
            }
            $userPid = $distribUser['user_pid'];
            $count++;
            if ($level != $count) {
                return $this->getUserPid($userPid, $level, $count);
            }
            return $userPid;
        }
    }
    
    private function updateDistributionUserGrade($userId, $grade_id) {
        $distribbModel = new DistributionUserModel();
        $data = array('grade_id' => $grade_id);
        $updateResult = $distribbModel->update($data, array('user_id' => $userId));
        if (!$updateResult) {
            return false;
        }
        return true;
    }

    //佣金结算
    public function commissionSettlement($order_id){
        $DistributionCommissonDetailModel = new DistributionCommissonDetailModel();
        $DistributionUserModel = new DistributionUserModel();
        $amountRes = $DistributionCommissonDetailModel->where('order_id',$order_id)->field('id,distrib_id,amount')->select();
        foreach ($amountRes as $k => $v){
            $DistributionCommissonDetailModel->where('id',$v['id'])->update(['complete_time'=>time()]);
            $DistributionUserModel->updateCommission($v['distrib_id'], $v['amount']);        //更新总佣金
            $DistributionUserModel->updateWithdrawAmount($v['distrib_id'], $v['amount']);    //更新可提现金额
        }
    }
}
