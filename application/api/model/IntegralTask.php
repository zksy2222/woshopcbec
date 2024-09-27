<?php

namespace app\api\model;
use think\Model;
use app\common\Lookup;

class IntegralTask extends Model{
    
    public function getTaskList($offset, $pageSize) {
        $where = array('a.status' => Lookup::isOpen);
        return $this->alias('a')
                ->where($where)
                ->field('a.*')
                ->join('sp_integral_taskcate b', 'a.cate_id = b.id', 'INNER')
                ->order('a.sort asc')
                ->limit($offset, $pageSize)
                ->select();
    }
    
    public function getTaskListAll() {
        $where = array('a.status' => Lookup::isOpen);
        $task_list = $this->alias('a')
                          ->field('a.id,a.task_name')
                          ->where($where)
                          ->join('sp_integral_taskcate b', 'a.cate_id = b.id', 'INNER')
                          ->select();
        $task_arr = array();
        foreach ($task_list as $v) {
            $task_arr[$v['id']] = $v['task_name'];
        }
        $task_arr[11] = "连续签到奖励";
        $task_arr[12] = "普通签到奖励";
        $task_arr[13] = "积分兑换";
        $task_arr[14] = "后台操作";
        return $task_arr;
    }
    
    public function getTaskInfo($id) {
        return $this->where('id', $id)->find();
    }
    
    public function getCreateTimeAttr($time) {
        return $time;
    }
    
}
