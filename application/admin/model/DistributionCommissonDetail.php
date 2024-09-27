<?php

namespace app\admin\model;
use think\Model;
use app\common\Lookup;
use app\admin\model\DistributionUser as distribUserModel;

class DistributionCommissonDetail extends Model{
    //获取分拥明细
    public function getAmountLst($distribUserId="",$filter=10,$keyword="") {
        if(!empty($distribUserId)){
            $where = [
                'distrib_user_id' => $distribUserId
            ];
        }else{
            $where = [];
        }
        switch ($filter){
            //已结算
            case 1:
                $where1 = 'a.complete_time<>0';
                break;
            //未结算
            case 2:
                $where1 = 'a.complete_time=0';
                break;
            //全部
            case 10:
                $where1 = array();
                break;
        }
        if(!empty($keyword)){
            $where2['c.real_name'] = array('like','%'.$keyword.'%');
        }
        return $this->alias('a')
                    ->field('a.create_time,a.amount,a.complete_time,a.level,a.user_id,d.fh_status,d.order_status,d.total_price,d.ordernumber,d.state,b.user_name,b.phone,c.real_name,c.phone dis_phone')
                    ->join('sp_member b','a.user_id = b.id','LEFT')
                    ->join('distribution_user c','a.distrib_user_id = c.user_id','LEFT')
                    ->join('order d','a.order_id = d.id','LEFT')
                    ->where($where)
                    ->where($where1)
                    ->where($where2)
                    ->order('a.create_time desc')
                    ->paginate(Lookup::pageSize);
    }


    public function getRalName($distribUserId){
        $distribUserModel = new distribUserModel();
        $where = [
            'user_id' => $distribUserId
        ];
        return $distribUserModel->where($where)->value('real_name');
    }
    

}
