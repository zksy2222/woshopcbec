<?php

namespace app\api\model;
use think\Model;

class Comment extends Model{
    
    public function getShopCommentList($shop_id, $offset, $pageSize) {
        $where = array('a.shop_id' => $shop_id, 'a.checked' => 1);
        return Comment::alias('a')->field('a.*,b.ordernumber,c.goods_name,c.thumb_url')
                ->join('sp_order b', 'a.order_id = b.id', 'left')
                ->join('sp_order_goods c', 'a.orgoods_id = c.id', 'left')
                ->where($where)
                ->order('time desc')
                ->limit($offset, $pageSize)
                ->select();
    }
}
