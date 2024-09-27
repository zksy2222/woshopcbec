<?php
namespace app\api\model;

use think\Model;

class OrderGoods extends Model
{
    public function order(){
        return $this->belongsTo('order','order_id','id');
    }

    /**
     * @description 获取用户已买商品数量
     * @param int $userId  用户ID
     * @param int $goodsId 商品ID
     * @param string $type 商品类型：seckill-秒杀，integral-积分换购，assemble-拼团，normal-普通商品
     */
    public function getUserOrderGoodsCount($userId,$goodsId,$type="normal"){
        switch ($type){
            case 'normal':
                $activityType = 0;
                break;
            case 'seckill':
                $activityType = 1;
                break;
            case 'integral':
                $activityType = 2;
                break;
            case 'assemble':
                $activityType = 3;
                break;
            default:
                $activityType = 0;
        }


        $count = $this->join('sp_order o','o.id = sp_order_goods.order_id')
                      ->where('o.user_id',$userId)
                      ->where('o.state',1)
                      ->where('goods_id',$goodsId)
                      ->where('hd_type',$activityType)
                      ->sum('goods_num');
        return $count;
    }

    // 获取订单商品
    public function getOrderGoods($orderId){
        return $this->where('order_id',$orderId)->select();
    }
}