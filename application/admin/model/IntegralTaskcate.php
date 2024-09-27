<?php

namespace app\admin\model;
use think\Model;

class IntegralTaskcate extends Model{
    
    public function getTaskcateList($keyword, $pageSize) {
        $where = array();
        if ($keyword) {
            $where['cate_name'] = array('like', "%{$keyword}%");
        }
        return IntegralTaskcate::where($where)->order('id asc')->paginate($pageSize);
    }
    
    public function getTaskcateInfo($id) {
        return IntegralTaskcate::where('id', $id)->find();
    }
    
    public function getTaskcateSelect() {
        return IntegralTaskcate::field('id, cate_name')->order('id desc')->select();
    }
    
    public function getCreateTimeAttr($time) {
        return $time;
    }
    
}
