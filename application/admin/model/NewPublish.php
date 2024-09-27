<?php

namespace app\admin\model;
use think\Model;

class NewPublish extends Model {

    public function getNewPublishList($keyword, $pageSize) {
        $where = array();
        if ($keyword) {
            $where['b.shop_name'] = array('like', "%{$keyword}%");
        }
        return NewPublish::alias('a')
                ->where($where)
                ->field('a.*,b.shop_name')
                ->join('sp_shops b', 'a.shop_id = b.id', 'LEFT')
                ->order('a.id desc')
                ->paginate($pageSize);
    }
    
    public function getNewPublishInfo($id) {
        return NewPublish::where('id', $id)->find();
    }
    
    public function getCreateTimeAttr($time) {
        return $time;
    }
}
