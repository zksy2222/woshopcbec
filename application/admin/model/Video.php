<?php

namespace app\admin\model;
use think\Model;
use app\common\Lookup;

class Video extends Model{

    protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    
    public function getVideoList() {
        return Video::alias('a')->field('a.*,b.shop_name,c.goods_name')
                ->join('sp_shops b', 'a.shop_id = b.id', 'left')
                ->join('sp_goods c', 'a.goods_id = c.id', 'left')
                ->order('id desc')
                ->paginate(Lookup::pageSize);
    }
    
    public function getVideoInfoById($id) {
        return Video::alias('a')->field('a.*,b.shop_name,c.goods_name')->where('a.id', $id)
                ->join('sp_shops b', 'a.shop_id = b.id', 'left')
                ->join('sp_goods c', 'a.goods_id = c.id', 'left')
                ->find();
    }
    
    public function getVideoListByShopId($shop_id, $video_id, $keyword) {
        $where = array('shop_id' => $shop_id, 'status' => Lookup::videoCheckPass);
        if ($video_id) {
            $where['id'] = array('not in', $video_id);
        }
        if ($keyword) {
            $where['title'] = array('like', "%{$keyword}%");
        }
        return Video::field('id,title,describe,cover_img,video_path')
                ->where($where)
                ->order('id desc')
                ->paginate(Lookup::pageSize);
    }
    
    public function getVideoListByIds($video_ids) {
        $where = array('id' => array('in', $video_ids),'status' => Lookup::videoCheckPass);
        return Video::field('id,title,describe,cover_img,video_path')->where($where)->select();
    }
    
    public function getCreateTimeAttr($time) {
        return $time;
    }
}
