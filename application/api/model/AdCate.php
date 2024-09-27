<?php

namespace app\api\model;
use think\Model;

class AdCate extends Model{
    public function ad(){
        return $this->hasMany('Ad','cate_id');
    }

    public function getAdByTag($tag){
        $ad = $this->with(['ad'=>function($query){
            $query->where('is_on',1)->order('sort DESC');
        }])->where('tag',$tag)->find();
        return $ad->ad;
    }

    public function getAdCate($tag){
        $adCate = $this->where('tag',$tag)->find();
        return $adCate;
    }
}
