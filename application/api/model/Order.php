<?php

namespace app\api\model;
use think\Model;
use think\Db;
use app\common\Lookup;

class Order extends Model {
    
    public function getOrderList($userid_arr, $status, $offset, $pageSize) {
        $where = array('a.is_show' => Lookup::isShow, 'a.user_id' => array('in', $userid_arr));
        $sort = array('a.addtime'=>'desc','a.id'=>'desc');
        switch($status) {
            //全部
            case Lookup::allStatus:
                
                break;
            //待付款
            case Lookup::waitPayStatus:
                $where['a.state'] = Lookup::zeroStatus;
                $where['a.fh_status'] = Lookup::zeroStatus;
                $where['a.order_status'] = Lookup::zeroStatus;
                break;
            //已付款
            case Lookup::payStatus:
                $where['a.state'] = Lookup::oneStatus;
                $where['a.order_status'] = Lookup::zeroStatus;
                break;
            //已完成
            case Lookup::finishStatus:
                $where['a.state'] = Lookup::oneStatus;
                $where['a.fh_status'] = Lookup::oneStatus;
                $where['a.order_status'] = Lookup::oneStatus;
                break;
        }
        return Db::name('order')->alias('a')
                ->field('a.id,a.user_id,c.user_name,c.phone,a.ordernumber,a.total_price,a.state,a.fh_status,a.order_status,a.shop_id,a.zdsh_time,a.time_out,a.addtime,pay_time,coll_time')
                ->join('sp_shops b','a.shop_id = b.id','INNER')
                ->join('sp_member c', 'a.user_id = c.id', 'INNER')
                ->where($where)
                ->order($sort)
                ->limit($offset, $pageSize)
                ->select();
        
    }
    
    //获得分销商邀请的人消费总金额
    public function getDistributionOrderTotalAmount($userid_arr) {
        $where = array(
            'a.is_show' => Lookup::isShow,
            'a.state' => Lookup::oneStatus,
            'a.fh_status' => Lookup::oneStatus,
            'a.order_status' => Lookup::oneStatus,
            'a.user_id' => array('in', $userid_arr)
        );
        
        return Order::alias('a')
                ->join('sp_shops b','a.shop_id = b.id','INNER')
                ->join('sp_member c', 'a.user_id = c.id', 'INNER')
                ->where($where)
                ->sum('total_price');
    }
    
    //是否购买指定的商品
    public function getOrderByGoodsId($goods_id, $userId) {
        $where = array(
            'a.is_show' => Lookup::isShow,
            'a.state' => Lookup::oneStatus,
            'a.fh_status' => Lookup::oneStatus,
            'a.order_status' => Lookup::oneStatus,
            'a.user_id' => $userId,
            'b.goods_id' => $goods_id
        );
        return Order::alias('a')
                ->join('sp_order_goods b', 'a.id = b.order_id', 'INNER')
                ->where($where)
                ->find();
    }
}
