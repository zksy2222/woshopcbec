<?php

namespace app\common\model;
use think\Model;

class DistributionConfig extends Model{
    
    public function getDistributionConfig() {
        return DistributionConfig::find();
    }
}
