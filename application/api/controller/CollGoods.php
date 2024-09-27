<?php
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use think\Db;
use app\api\model\CollGoods as CollGoodsModel;

class CollGoods extends Common{
    
    //收藏商品
    public function coll(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
	    if(!input('post.goods_id')){
	    	datamsg(400,'缺少商品参数',array('status'=>400));
	    }
        $goodsId = input('post.goods_id');
        $goods = Db::name('goods')->alias('a')->field('a.id,a.cate_id,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$goodsId)->where('a.onsale',1)->where('b.open_status',1)->find();
        if(!$goods){
	        datamsg(400,'商品不存在或已下架',array('status'=>400));
        }

        $coll_goods = Db::name('coll_goods')->where('user_id',$userId)->where('goods_id',$goodsId)->find();
	    if($coll_goods){
		    datamsg(400,'已收藏该商品，请勿重复收藏',array('status'=>400));
	    }

        $pid = Db::name('category')->where('id',$goods['cate_id'])->value('pid');
        if($pid == 0){
            $cid = $goods['cate_id'];
        }else{
            $categoryres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
            $cateIds = array();
            $cateIds = get_all_parent($categoryres, $goods['cate_id']);
            $cid = end($cateIds);

            // 启动事务
            Db::startTrans();
            try{
                Db::name('coll_goods')->insert(array('goods_id'=>$goodsId,'user_id'=>$userId,'cate_id'=>$cid,'addtime'=>time()));
                Db::name('goods')->where('id',$goodsId)->setInc('coll_num',1);
                // 提交事务
                Db::commit();
	            datamsg(200,'收藏成功',array('status'=>200));
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
	            datamsg(400,'收藏失败',array('status'=>400));
            }
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
	    if(!input('post.goods_id')){
		    datamsg(400,'缺少商品参数',array('status'=>400));
	    }
        $goodsId = input('post.goods_id');
        $coll_goods = Db::name('coll_goods')->where('user_id',$userId)->where('goods_id',$goodsId)->find();
        if(!$coll_goods){
	        datamsg(400,'该商品暂未收藏，取消失败',array('status'=>400));
        }

        $coll_num = Db::name('goods')->where('id',$goodsId)->value('coll_num');
        // 启动事务
        Db::startTrans();
        try{
            Db::name('coll_goods')->delete($coll_goods['id']);
            if($coll_num > 0){
                Db::name('goods')->where('id',$goodsId)->setDec('coll_num',1);
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

    //获取收藏的商品列表
    public function getCollGoodsList() {
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }

        $page = input('post.page', 1);
        if (!preg_match("/^\\+?[1-9][0-9]*$/", $page)) {
            datamsg(400, 'page参数错误');
        }
        
        $webconfig = $this->webconfig;
        $pageSize = $webconfig['app_goodlst_num'];
        $offset = ($page - 1) * $pageSize;
        
        $collModel = new CollGoodsModel();
        $coll_list = $collModel->getCollGoodsList($userId, $offset, $pageSize);
        foreach ($coll_list as $key => $v) {
            $coll_list[$key]['thumb_url'] = $this->webconfig['weburl'] . $v['thumb_url'];
        }
        $data = array('coll_list' => $coll_list);
        datamsg(200, 'success', $data);
    }
}