<?php
/**
 * @Description: 商品规格
 * @Author : 梧桐 <2487937004@qq.com>
 * @Copyright : 武汉一一零七科技有限公司 All rights reserved.
 */
namespace app\api\model;
use think\Db;
use think\Model;

class GoodsSpec extends Model
{
    /**
     * @description 检测商品规格是否正确
     * @param Array $goodsSpecItemIdArr 商品规格项id数组
     */
    public function checkGoodsSpec($goodsId,$goodsSpecItemIdArr){
        if(!is_numeric($goodsId)){
            return array('status'=>400,'mess'=>'商品参数错误');
        }
        if(!is_array($goodsSpecItemIdArr)){
            return array('status'=>400,'mess'=>'商品参数错误');
        }
        $goodsModel = new Goods();
        $hasoption = $goodsModel->where('id',$goodsId)->value('hasoption');
        if($hasoption == 0){
            return array('status'=>400,'mess'=>'该商品为单规格商品！');
        }

        $gooodsOptionModel = new GoodsOption();
        $goodsSpecItemIdStr = implode('_',$goodsSpecItemIdArr);
        $goodsOption = $gooodsOptionModel->where(['goods_id'=>$goodsId,'specs'=>$goodsSpecItemIdStr])->find();
        if(!$goodsOption){
            return array('status'=>400,'mess'=>'请选择商品规格');
        }

        $goodsSpecItemIdArr = array_unique($goodsSpecItemIdArr);
        if(!is_array($goodsSpecItemIdArr)){
            return array('status'=>400,'mess'=>'商品属性参数错误');
        }

        foreach ($goodsSpecItemIdArr as $v){
            if(!is_numeric($v)) {
                return array('status'=>400,'mess'=>'商品属性参数错误');
            }
            $goodsSpecItem = Db::name('goods_spec_item')->where('id',$v)->find();
            if(!$goodsSpecItem){
                return array('status'=>400,'mess'=>'商品属性参数错误');
            }
            $goodsSpec = Db::name('goods_spec')->where('id',$goodsSpecItem['spec_id'])->find();
            if(!$goodsSpec){
                return array('status'=>400,'mess'=>'商品属性参数错误');
            }
            unset($goodsSpec);
            unset($goodsSpecItem);
        }
    }
}