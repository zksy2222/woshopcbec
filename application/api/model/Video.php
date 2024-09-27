<?php

namespace app\api\model;

use think\Model;
use app\common\Lookup;

class Video extends Model{
    
    public function getVideoList($offset, $pageSize, $videoId=0) {
        $where = array('a.status' => Lookup::videoCheckPass, 'b.open_status' => Lookup::isOpen, 'c.onsale' => Lookup::isOpen);
        if($videoId > 0){
            $where['a.id'] = ['ELT',$videoId];
        }
        return $this->alias('a')
                ->field('a.*,b.shop_name,b.logo shop_logo,c.goods_name,c.thumb_url goods_img,c.shop_price')
                ->join('sp_shops b', 'a.shop_id = b.id', 'left')
                ->join('sp_goods c', 'a.goods_id = c.id', 'left')
                ->where($where)
                ->order('id desc')
                ->limit($offset, $pageSize)
                ->select();
    }
    
    public function getVideoListByIds($video_ids) {
        $where = array(
            'a.id' => array('in', $video_ids),
            'a.status' => Lookup::videoCheckPass,
            'b.onsale' => Lookup::isOpen
        );
        return $this->alias('a')
                ->field('a.goods_id,b.goods_name,b.shop_price,b.thumb_url goods_img,a.video_path video_url')
                ->join('sp_goods b', 'a.goods_id = b.id', 'left')
                ->where($where)
                ->select();
    }
    
    public function getCreateTimeAttr($time) {
        return $time;
    }
}
