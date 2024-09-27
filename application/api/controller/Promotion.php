<?php
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use think\Db;

class Promotion extends Common{
    
    public function huodonginfo(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }
        if(!input('post.shop_id')){
	        datamsg(400,'缺少商家参数',array('status'=>400));
        }
        if(!input('post.goods_id')){
	        datamsg(400,'缺少商品参数',array('status'=>400));
        }
        if(!input('post.prom_id')){
	        datamsg(400,'缺少活动参数',array('status'=>400));
        }

        $shop_id = input('post.shop_id');
        $goodsId = input('post.goods_id');
        $prom_id = input('post.prom_id');
        $promotions = Db::name('promotion')->where('id',$prom_id)->where('shop_id',$shop_id)->where("find_in_set('".$goodsId."',info_id)")->where('is_show',1)->where('recommend',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,start_time,end_time,shop_id')->find();
        if(!$promotions){
	        datamsg(400,'找不到相关活动信息或活动已过期',array('status'=>400));
        }
        $prom_typeres = Db::name('prom_type')->where('prom_id',$promotions['id'])->select();
        if(!$prom_typeres){
	        datamsg(400,'找不到相关活动信息或活动已过期',array('status'=>400));
        }

        $start_time = date('Y年m月d日 H时',$promotions['start_time']);
        $end_time = date('Y年m月d日 H时',$promotions['end_time']);
        $promotion_infos = '';

        foreach ($prom_typeres as $kcp => $vcp){
            $zhekou = $vcp['discount']/10;
            if($kcp == 0){
                $promotion_infos = lang('商品满 ').$vcp['man_num'].lang('件享').$zhekou.lang('折');
            }else{
                $promotion_infos = $promotion_infos.lang('满').$vcp['man_num'].lang('件享').$zhekou.lang('折');
            }
        }
        $goods_promotion = array('prom_id'=>$promotions['id'],'shop_id'=>$shop_id,'goods_id'=>$goodsId,'promotion_name'=>$promotion_infos,'time'=>lang('有效期：').$start_time.lang('至').$end_time.lang('截止'));
	    datamsg(200,'获取活动信息成功',$goods_promotion);
    }
}