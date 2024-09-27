<?php

namespace app\common\model;
use think\Model;
class Config extends Model{
    public function getConfigByCateId($cateId) {
        return $this->where('ca_id', $cateId)->field('ename,value')->select();
    }

    public function getConfigValueByCateId($cateId) {
        $configList = $this->where('ca_id', $cateId)->field('ename,value')->select();
        $config = [];
        foreach ($configList as $k=>$v){
            $config[$v['ename']] = $v['value'];
        }
        return $config;
    }

    public function getConfigByName($name){
        return $this->where('ename',$name)->value('value');
    }
}
