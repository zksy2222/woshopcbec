<?php

namespace app\admin\model;
use think\Model;

class Shops extends Model{
    
    public function getShopList() {
        $where = array('open_status' => 1, 'normal' => 1);
        return Shops::field('id,shop_name')->where($where)->select();
    }
}
