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

class GoodsSpecItem extends Model
{
    public function goodsSPec(){
        return $this->belongsTo('goodsSpec','spec_id');
    }

    public function getGoodsSpecAndSpecItemInfo($specItemId){

        if(is_array($specItemId)){
            return $this->with('goodsSpec')->where('id','in',$specItemId)->select();
        }elseif(is_numeric($specItemId)){
            return $this->with('goodsSpec')->where('id',$specItemId)->find;
        }else{
            return;
        }

    }
}