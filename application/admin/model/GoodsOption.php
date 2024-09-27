<?php
namespace app\admin\model;
use think\Model;

class GoodsOption extends Model
{
  public function unSetGoodsOptionSeckillData($gooodId){
      $this::update(['is_seckill'=>0,'seckill_price'=>0,'seckill_stock'=>0],['goods_id'=>$gooodId]);
  }

  public function unSetGoodsOptionAssembleData($gooodId){
      $this::update(['is_assemble'=>0,'assemble_price'=>0,'assemble_stock'=>0],['goods_id'=>$gooodId]);
  }

  public function unSetGoodsOptionIntegralData($gooodId){
      $this::update(['is_integral'=>0,'integral_price'=>0,'integral_stock'=>0],['integral'=>0],['goods_id'=>$gooodId]);
  }
}