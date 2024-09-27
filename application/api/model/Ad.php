<?php

namespace app\api\model;
use think\Model;

class Ad extends Model{

    public function adCate(){
       return  $this->belongsTo('AdCate','cate_id');
    }

}
