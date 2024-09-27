<?php

namespace app\api\model;
use think\Model;

class CollGoods extends Model{
    
    public function getCollGoodsList($userId, $offset, $pageSize) {
        $where = array('a.user_id' => $userId);
        return $this->alias('a')
                ->field('a.id,a.cate_id,goods_name,shop_price,zs_price,thumb_url,FROM_UNIXTIME(a.addtime) addtime')
                ->join('sp_goods b', 'a.goods_id = b.id', 'INNER')
                ->where($where)
                ->limit($offset, $pageSize)
                ->select();
    }
}
