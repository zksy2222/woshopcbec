<?php
/**
 * @Description: 商品参数
 * @Author : 梧桐 <2487937004@qq.com>
 * @Copyright : 武汉一一零七科技有限公司 All rights reserved.
 */
namespace app\api\model;
use think\Db;
use think\Model;

class GoodsParam extends Model
{
    public function getGoodsParam($goodsId){
        return $this->where('goods_id',$goodsId)->order('sort ASC')->select();
    }
}