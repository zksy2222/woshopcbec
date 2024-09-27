<?php

namespace app\admin\model;
use think\Model;
use app\common\Lookup;

class GoodsImportRecord extends Model{
    
    public function getImportRecordList() {
        return GoodsImportRecord::order('id desc')->paginate(Lookup::pageSize);
    }
    
    public static function getImportRecordInfo($id) {
        return GoodsImportRecord::where('id', $id)->find();
    }
    
    public function getCreateTimeAttr($time) {
        return $time;
    }
    
}
