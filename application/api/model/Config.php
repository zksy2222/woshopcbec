<?php

namespace app\api\model;

use think\Model;

class Config extends Model
{
    public function getExamine(){
        $where = [
            'open_many_shop',
            'copyright_open',
            'open_or_not',
            'is_email',
            'is_phone',
        ];
        $examineInfo = $this->where('ename','in',$where)->field('id,cname,ename,value')->select();

        return $examineInfo;
    }
}
