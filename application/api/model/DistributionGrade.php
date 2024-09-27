<?php

namespace app\api\model;
use think\Model;

class DistributionGrade extends Model {
    
    public function getGradeInfoById($id) {
        return DistributionGrade::where('id', $id)->where('status', 1)->find();
    }
    
    public function getGradeList() {
        return DistributionGrade::where('status', 1)->select();
    }
}
