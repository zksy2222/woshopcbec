<?php

namespace app\api\model;
use think\Model;
use app\common\Lookup;

class DistributionCommissonDetail extends Model {
    
    public function getCommissonTotalNum($userId) {
        $where = array('user_pid' => $userId);
        return DistributionCommissonDetail::where($where)->count('id');
    }

    //获取分销佣金明细
    public function getAmountLst($distribUserId="",$status="",$page=1,$pageSize="") {
        $where = array('d.is_show' => Lookup::isShow, 'a.distrib_user_id' => array('in', $distribUserId));
        $sort = array('d.addtime'=>'desc','d.id'=>'desc');
        switch($status) {
            //全部
            case Lookup::allStatus:

                break;
            //待付款
            case Lookup::waitPayStatus:
                $where['d.state'] = Lookup::zeroStatus;
                $where['d.fh_status'] = Lookup::zeroStatus;
                $where['d.order_status'] = Lookup::zeroStatus;
                break;
            //已付款
            case Lookup::payStatus:
                $where['d.state'] = Lookup::oneStatus;
                $where['d.order_status'] = Lookup::zeroStatus;
                break;
            //已完成
            case Lookup::finishStatus:
                $where['d.state'] = Lookup::oneStatus;
                $where['d.fh_status'] = Lookup::oneStatus;
                $where['d.order_status'] = Lookup::oneStatus;
                break;
        }
        return $this->alias('a')
            ->field('d.id,d.user_id,b.user_name,b.phone,d.ordernumber,d.total_price,d.state,d.fh_status,d.order_status,d.shop_id,d.zdsh_time,d.addtime,d.pay_time,d.coll_time,a.amount commission,a.level')
            ->join('sp_member b','a.user_id = b.id')
            ->join('order d','a.order_id = d.id')
            ->where($where)
            ->order($sort)
            ->paginate($pageSize,false,['page'=>$page])
            ->each(function($item){
                $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                $item['pay_time'] = $item['pay_time'] ? date('Y-m-d H:i:s', $item['pay_time']) : $item['pay_time'];
                $item['coll_time'] = $item['coll_time'] ? date('Y-m-d H:i:s', $item['coll_time']) : $item['coll_time'];
                return $item;
            });
    }
}
