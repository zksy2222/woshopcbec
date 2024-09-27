<?php

namespace app\admin\model;
use think\Model;
use app\common\Lookup;

class DistributionWithdraw extends Model{
    
    public function getWithdrawList($filter, $map = array()) {
        
        $where = array('c.checked' => Lookup::isShow, 'b.status' => Lookup::checkPass, 'b.is_distribution' => Lookup::isDistrib);
        
        switch ($filter){
            case 1:
                //待审核
                $where['a.status'] = Lookup::waitCheckWithdraw;
                $where['a.pay_status'] = Lookup::waitPayStatusWithdraw;
                break;
            case 2:
                //待打款
                $where['a.status'] = Lookup::passCheckWithdraw;
                $where['a.pay_status'] = Lookup::waitPayStatusWithdraw;
                break;
            case 3:
                //已完成
                $where['a.status'] = Lookup::passCheckWithdraw;
                $where['a.pay_status'] = Lookup::successPayStatusWithdraw;
                break;
            case 4:
                //打款失败
                $where['a.status'] = Lookup::passCheckWithdraw;
                $where['a.pay_status'] = Lookup::failPayStatusWithdraw;
                break;
            case 5:
                //审核未通过
                $where['a.status'] = Lookup::rfuseCheckWithdraw;
                $where['a.pay_status'] = Lookup::waitPayStatusWithdraw;
                break;
        }
        
        if (!empty($map['a.tx_number'])) {
            $where['a.tx_number'] = $map['a.tx_number'];
        }
        
        if (!empty($map['a.create_time'])) {
            $where['a.create_time'] = $map['a.create_time'];
        }
        
        return DistributionWithdraw::alias('a')->field('a.*,c.user_name,c.real_name,c.phone')
                ->join('sp_distribution_user b', 'a.user_id=b.user_id', 'left')
                ->join('sp_member c', 'b.user_id=c.id', 'left')
                ->where($where)
                ->order('a.create_time desc')
                ->paginate(Lookup::pageSize);
    }
    
    public function getWithdrawById($where) {
        $map = array('a.id' => $where['id']);
        if (isset($where['status'])) {
            $map['a.status'] = $where['status'];
        }
        if (isset($where['pay_status'])) {
            $map['a.pay_status'] = $where['pay_status'];
        }
        return DistributionWithdraw::alias('a')->field('a.*,b.id distrib_id,b.withdraw_amount,c.real_name,c.phone')
                ->join('sp_distribution_user b', 'a.user_id=b.user_id', 'left')
                ->join('sp_member c', 'b.user_id=c.id', 'left')
                ->where($map)
                ->find();
    }
    
    public function getCreateTimeAttr($time) {
        return $time;
    }
}
