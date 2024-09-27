<?php

namespace addons\lang\model;
use think\Model;

class Lang extends Model{
    
    public function getLangList($keyword, $pageSize) {
        $where = array();
        if ($keyword) {
            $where['lang_name|lang_code'] = array('like', "%{$keyword}%");
        }
        return Lang::where($where)->order('id desc')->paginate($pageSize);
    }
    
    public function getLangInfoById($id) {
        return Lang::where('id', $id)->find();
    }
    
    public function getLangIsDefault($is_default) {
        return Lang::where('is_default', $is_default)->value('id');
    }
    
    public static function getLangSwitchList() {
        return Lang::field('id,lang_name,lang_code')->select();
    }
    
    public function getCreateTimeAttr($time) {
        return $time;
    }
}
