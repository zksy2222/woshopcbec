<?php

namespace app\api\model;

use think\Model;

class Version extends Model
{
    public function getNewAppVersion(){
        return $this->order('id DESC')->find();
    }
}
