<?php

namespace app\admin\model;
use think\Model;

class Goods extends Model{
    
    public function getGoodsInfoByGoodsId($goods_id) {
        $where = array('a.id' => $goods_id, 'a.onsale' => 1);
        return Goods::alias('a')->field('a.id,a.goods_name,a.thumb_url,a.shop_price,b.cate_name')
                ->join('sp_category b','a.cate_id = b.id','LEFT')
                ->where($where)
                ->find();
    }
    
    public function getGoodsInfo($goods_ids) {
        $where = array('id' => array('in', $goods_ids), 'onsale' => 1);
        return Goods::field('goods_name,shop_price')->where($where)->select();
    }
    
    public function getGoodsListByIds($goods_ids) {
        $where = array('a.id' => array('in', $goods_ids),'a.onsale' => 1);
        return Goods::alias('a')->field('a.id,a.goods_name,a.thumb_url,a.shop_price,b.cate_name')
                ->join('sp_category b','a.cate_id = b.id','LEFT')
                ->where($where)
                ->select();
    }
}
