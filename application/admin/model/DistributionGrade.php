<?php

namespace app\admin\model;
use think\Model;
use app\common\Lookup;

class DistributionGrade extends Model{
    
    public function getGradeList($page_size) {
        return DistributionGrade::order('id desc')->paginate($page_size);
    }
    
    public function getGradeInfoById($id) {
        $where = array('id' => $id);
        return DistributionGrade::where($where)->find();
    }
    
    public function getGradeSelect() {
        $where = array('status' => Lookup::isOpen);
        return DistributionGrade::field('id,grade_name')->where($where)->order('id desc')->select();
    }
    
    public function getCreateTimeAttr($time) {
        return $time;
    }
}
