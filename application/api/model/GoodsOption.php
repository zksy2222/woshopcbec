<?php
/**
 * @Description: 商品规格详情
 * @Author : 梧桐 <2487937004@qq.com>
 * @Copyright : 武汉一一零七科技有限公司 All rights reserved.
 */
namespace app\api\model;
use think\Db;
use think\Model;
use app\api\model\Goods;

class GoodsOption extends Model
{
    public function getGoodsOption($goodsId,$goodsSpecItemIdStr){
        return $this->where(['goods_id'=>$goodsId,'specs'=>$goodsSpecItemIdStr])->find();
    }
}