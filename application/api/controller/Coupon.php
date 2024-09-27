<?php
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use think\Db;

class Coupon extends Common{
    
    //获取优惠券列表信息接口
    public function couponlst(){
        $needUserToken = input('post.token') ? 1 : 0;
	    $tokenRes = $this->checkToken($needUserToken);
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }

        $perpage = $this->webconfig['app_goodlst_num'];

        $type = input('post.type','shop');
	    // shop-店铺优惠券，all-所有优惠券
	    if($type == 'shop'){
            if(!input('post.shop_id')){
                datamsg(400,'缺少商家参数',array('status'=>400));
            }
            $shop_id = input('post.shop_id');
            $shops = Db::name('shops')->where('id',$shop_id)->where('open_status',1)->field('id')->find();
            if(!$shops){
                datamsg(400,'找不到相关商家信息',array('status'=>400));
            }
            $where['shop_id'] = $shop_id;
        }

        $couponres = Db::name('coupon')
                       ->alias('a')
                       ->join('shops b','a.shop_id = b.id')
                       ->where($where)
                       ->where('b.open_status',1)
                       ->where('start_time','elt',time())
                       ->where('end_time','gt',time()-3600*24)
                       ->where('a.onsale',1)
                       ->field('a.id,a.man_price,a.dec_price,a.start_time,a.end_time,a.shop_id')
                       ->order('a.man_price asc')
                       ->paginate($perpage)
                       ->each(function($item) use($userId){
                           $item['shop_name'] = Db::name('shops')->where('id',$item['shop_id'])->value('shop_name');
                           $item['start_time'] = date('Y-m-d',$item['start_time']);
                           $item['end_time'] = date('Y-m-d',$item['end_time']);
                           $shopGoodsIds = Db::name('goods')->where("shop_id",$item['shop_id'])->where('is_recycle',0)->where('onsale',1)->column("id");
                           $id = $shopGoodsIds ? array_rand($shopGoodsIds) : '-1';

                           $item['thumb_url'] = Db::name('goods')->where("id",$id)->value('thumb_url');
                           $item['thumb_url'] = url_format($item['thumb_url'], $this->webconfig['weburl']);
                           if(!empty($userId)){
                               $member_coupons = Db::name('member_coupon')->where('user_id',$userId)->where('coupon_id',$item['id'])->where('is_sy',0)->where('shop_id',$item['shop_id'])->find();
                               if($member_coupons){
                                   $item['have'] = 1;
                               }else{
                                   $item['have'] = 0;
                               }
                           }else{
                               $item['have'] = 0;
                           }
                           return $item;
                       });

	    datamsg(200,'获取优惠券信息成功',$couponres);
    }
  
      /**
     * 查询用户优惠券列表
     */
    public function memberCouponList() {
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }
        $userId = $tokenRes['user_id'];
        $conpous = db('member_coupon m')
                    ->field('m.is_sy,m.shop_id,m.user_id,c.id,c.man_price,c.dec_price,c.start_time,c.end_time')
                    ->join('coupon c','m.coupon_id = c.id')
                    ->where('m.user_id',$userId)
                    ->order('m.id DESC')
                    ->select();
        datamsg(200,'会员优惠券列表',$conpous);
    }
  
    
    //领取优惠券接口
    public function getcoupons(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
	    if(!input('post.coupon_id') || !input('post.shop_id')){
		    datamsg(400,'缺少参数',array('status'=>400));
	    }

	    $coupon_id = input('post.coupon_id');
	    $shop_id = input('post.shop_id');
	    $coupons = Db::name('coupon')->where('id',$coupon_id)->where('shop_id',$shop_id)->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->field('id')->find();
	    if(!$coupons){
		    datamsg(400,'找不到相关优惠券信息或已过期',array('status'=>400));
	    }

        $member_coupons = Db::name('member_coupon')->where('user_id',$userId)->where('coupon_id',$coupons['id'])->where('is_sy',0)->where('shop_id',$shop_id)->find();

        if(!$member_coupons){
            $lastId = Db::name('member_coupon')->insert(array('coupon_id'=>$coupons['id'],'is_sy'=>0,'shop_id'=>$shop_id,'user_id'=>$userId));
            if($lastId){
	            datamsg(200,'领券成功',array('status'=>400));
            }else{
	            datamsg(400,'领券失败',array('status'=>400));
            }
        }else{
	        datamsg(400,'该优惠券已领取，请勿重复领取',array('status'=>400));
        }
    }
    
}