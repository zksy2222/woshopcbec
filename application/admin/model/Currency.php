<?php

namespace app\admin\model;
use think\Model;

class Currency extends Model{
    
    public function getCurrencyList($keyword, $pageSize) {
        $where = array();
        if ($keyword) {
            $where['currency_name|currency_code'] = array('like', "%{$keyword}%");
        }
        return Currency::where($where)->order('id desc')->paginate($pageSize);
    }
    
    public function getCurrencyInfoById($id) {
        return Currency::where('id', $id)->find();
    }
    
    public function getCreateTimeAttr($time) {
        return $time;
    }
}
