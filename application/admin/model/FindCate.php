<?php

namespace app\admin\model;
use think\Model;

class FindCate extends Model{
    
    public function getFindCateList() {
        return FindCate::select();
    }
    
    public function getFindCateById($id) {
        $where = array('id' => $id);
        return FindCate::where($where)->find();
    }
    
    public function getCreateTimeAttr($time) {
        return $time;
    }
}
