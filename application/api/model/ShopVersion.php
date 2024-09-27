<?php

namespace app\api\model;

use think\Model;

class ShopVersion extends Model
{
    public function getNewAppVersion(){
        return $this->order('id DESC')->find();
    }
}
