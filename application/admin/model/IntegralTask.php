<?php

namespace app\admin\model;
use think\Model;

class IntegralTask extends Model{
    
    public function getTaskList($keyword, $pageSize,$page) {
        $where = array();
        if ($keyword) {
            $where['task_name|tag_name'] = array('like', "%{$keyword}%");
        }
        return $this->where($where)->order('sort DESC')->paginate($pageSize);
    }
    
    public function getTaskInfo($id) {
        return $this->where('id', $id)->find();
    }
    
    public function getCreateTimeAttr($time) {
        return $time;
    }
    
}
