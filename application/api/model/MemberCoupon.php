<?php

namespace app\api\model;

use think\Model;

class MemberCoupon extends Model
{
     public function coupon(){
         return $this->hasOne('coupon');
     }

     public function getUnusedCouponsCount($userId){
         return $this->with(['coupon'=>function($query){
             $query->where('end_time','<=',time());
         }])->where('user_id',$userId)->count();
     }

}
