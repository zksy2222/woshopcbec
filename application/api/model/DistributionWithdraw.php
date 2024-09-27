<?php

namespace app\api\model;
use think\Model;
use app\common\Lookup;

class DistributionWithdraw extends Model {
    
    //每月提现次数
    public function getWithdrawMonthCount($userId) {
        return DistributionWithdraw::where('user_id', $userId)->whereTime('create_time', 'month')->count();
    }
    
    public function getWithdrawCount($userId) {
        return DistributionWithdraw::where('user_id', $userId)->count();
    }
    
    public function getWithdrawByNumber($number) {
        return DistributionWithdraw::where('tx_number', $number)->find();
    }
    
    public function getWithdrawList($userId, $status, $offset, $pageSize) {
        $where = array('user_id' => $userId);
        $whereOr = array();
        $sort = array('id'=>'desc');
        switch ($status) {
            //待审核
            case Lookup::waitCheck:
                $where['status'] = Lookup::waitCheckWithdraw;
                $where['pay_status'] = Lookup::waitPayStatusWithdraw;
                break;
            //待打款
            case Lookup::waitPay:
                $where['status'] = Lookup::passCheckWithdraw;
                $where['pay_status'] = Lookup::waitPayStatusWithdraw;
                break;
            //已打款
            case Lookup::finishPay:
                $where['status'] = Lookup::passCheckWithdraw;
                $where['pay_status'] = Lookup::successPayStatusWithdraw;
                break;
            //无效
            case Lookup::invalidStatus:
                $where['status'] = Lookup::rfuseCheckWithdraw;
                $whereOr = array('status' => Lookup::passCheckWithdraw, 'pay_status' => Lookup::failPayStatusWithdraw);
                break;
        }
        
        return DistributionWithdraw::where($where)->whereOr(function($query) use($whereOr){
                    $query->where($whereOr);
                })->order($sort)->limit($offset, $pageSize)->select();
        
    }
    
    public function getCreateTimeAttr($time) {
        return $time;
    }
}