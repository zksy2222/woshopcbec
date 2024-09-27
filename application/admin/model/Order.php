<?php

namespace app\admin\model;
use think\Model;

class Order extends Model{

    /**
     * @param int $shop_id  店铺id
     * @param int $filter   订单状态
     * @return int|string   返回值
     * @throws \think\Exception
     */
    public function getOrderCunt($shop_id=1,$filter=10) {
        if(!$filter || !in_array($filter, array(1,2,3,4,5,10))){
            $filter = 10;
        }
        switch ($filter){
            //待发货
            case 1:
                $where = array('shop_id'=>$shop_id,'state'=>1,'fh_status'=>0,'order_status'=>0);
                break;
            //已发货
            case 2:
                $where = array('shop_id'=>$shop_id,'state'=>1,'fh_status'=>1,'order_status'=>0);
                break;
            //已完成
            case 3:
                $where = array('shop_id'=>$shop_id,'state'=>1,'fh_status'=>1,'order_status'=>1);
                break;
            //待支付
            case 4:
                $where = array('shop_id'=>$shop_id,'state'=>0,'fh_status'=>0,'order_status'=>0);
                break;
            //已关闭
            case 5:
                $where = array('shop_id'=>$shop_id,'order_status'=>2);
                break;
            //全部
            case 10:
                $where = array('shop_id'=>$shop_id);
                break;
        }
        return $this->where($where)->count();
    }

}
