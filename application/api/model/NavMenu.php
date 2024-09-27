<?php

namespace app\api\model;
use think\Model;

class NavMenu extends Model{
    
    public function getMenuList($nav_id, $is_show) {
        $where = array('nav_id' => $nav_id, 'is_show' => $is_show);
        return NavMenu::field('nav_id, is_show', true)->where($where)->order('sort DESC')->select();
    }
    
    public function getBannerImage($id) {
        return NavMenu::where('id', $id)->value('banner_path');
    }
}
