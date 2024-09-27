<?php

namespace app\api\model;
use think\Model;

class NewPublish extends Model{
    
    public function getNewPublishList($offset, $pageSize) {
        $where = array('a.status' => 1, 'b.open_status' => 1, 'b.normal' => 1);
        return NewPublish::alias('a')
                ->where($where)
                ->field('a.*,b.shop_name,b.logo shop_logo,b.shop_desc')
                ->join('sp_shops b', 'a.shop_id = b.id', 'LEFT')
                ->order('a.id desc')
                ->limit($offset, $pageSize)
                ->select();
    }
    
    public function getNewPublishIsPraise($id) {
        return NewPublish::where('id', $id)->value('is_praise');
    }
    
    //点赞+
    public function setIncPraise($id) {
        return NewPublish::where('id', $id)->setInc('praise_num', 1);
    }
    
    //点赞-
    public function setDecPraise($id) {
        return NewPublish::where('id', $id)->setDec('praise_num', 1);
    }
    
    public function getCreateTimeAttr($time) {
        return $time;
    }
}
