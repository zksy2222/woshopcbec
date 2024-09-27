<?php
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use think\Db;

class CollShops extends Common{
    
    //收藏店铺
    public function coll(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }else{
            $userId = $tokenRes['user_id'];
        }
        if(!input('post.shop_id')){
            datamsg(400,'缺少商家参数',array('status'=>400));
        }

        $shop_id = input('post.shop_id');
        $shops = Db::name('shops')->where('id',$shop_id)->field('id')->find();
        if(!$shops){
            datamsg(400,'店铺不存在',array('status'=>400));
        }

        $coll_shops = Db::name('coll_shops')->where('user_id',$userId)->where('shop_id',$shop_id)->find();
        if($coll_shops){
            datamsg(400,'已关注该店铺，请勿重复关注',array('status'=>400));
        }

        // 启动事务
        Db::startTrans();
        try{
            Db::name('coll_shops')->insert(array('shop_id'=>$shop_id,'user_id'=>$userId,'addtime'=>time()));
            Db::name('shops')->where('id',$shop_id)->setInc('coll_num',1);

            //关注直播间
            //7关注主播（仅限一次）

            Db::name('live_fans')->where('user_id',$userId)->update(array('isfollow'=>1));

            $live = db('live')->where(['shop_id'=>$shop_id])->find();
            $room = $live['room'];
            $num = $this->getLiveIntegralRules(7);
            $this->addLiveIntegral($userId,$shop_id,$room,$num,7);

            // 提交事务
            Db::commit();
            datamsg(200,'关注成功',array('status'=>200));
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            datamsg(400,'关注失败',array('status'=>400));
        }
    }
    
    //取消收藏
    public function cancelcoll(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
	    if(!input('post.shop_id')){
		    datamsg(400,'缺少商家参数',array('status'=>400));
	    }
        $shop_id = input('post.shop_id');
        $coll_shops = Db::name('coll_shops')->where('user_id',$userId)->where('shop_id',$shop_id)->find();
	    if(!$coll_shops){
		    datamsg(400,'该店铺暂未关注，取消失败',array('status'=>400));
	    }
        $coll_num = Db::name('shops')->where('id',$shop_id)->value('coll_num');
        // 启动事务
        Db::startTrans();
        try{
            Db::name('coll_shops')->delete($coll_shops['id']);
			Db::name('live_fans')->where('user_id',$userId)->update(array('isfollow'=>0));
            if($coll_num > 0){
                Db::name('shops')->where('id',$shop_id)->setDec('coll_num',1);
            }
            // 提交事务
            Db::commit();
	        datamsg(200,'取消成功',array('status'=>200));
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
	        datamsg(400,'取消失败',array('status'=>400));
        }

    }
}
