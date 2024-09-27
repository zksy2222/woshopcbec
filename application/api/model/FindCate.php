<?php

namespace app\api\model;
use think\Model;

class FindCate extends Model{
    
    public function getFindCateList() {
        return FindCate::field('create_time', true)->select();
    }
    
    public function getCreateTimeAttr($time) {
        return $time;
    }
}

