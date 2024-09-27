<?php
namespace app\api\controller;
use app\api\model\Common as CommonModel;
use app\api\model\Comment as CommentModel;
use app\api\model\CommentPic;
use think\Db;

class Comment extends Common {
    // 添加商品评价
    public function addGoodsComment(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        $content = input('param.content');
//                $tags = input('param.tags');
        $goodsId = input('param.goods_id');
        $orderId = input('param.order_id');
        $goodsStar = input('param.goods_star');
        $logisticsStar = input('param.logistics_star');
        $serviceStar = input('param.service_star');
        $fpic = input('param.pic');
        if (empty($orderId)) {
            datamsg(400, '缺少订单参数');
        }
        if (empty($content)) {
            datamsg(400, '请填写评价内容');
        }
        if (empty($goodsStar)) {
            datamsg(400, '请给商品打分');
        }
        if (empty($serviceStar)) {
            datamsg(400, '请给服务打分');
        }
        if (empty($logisticsStar)) {
            datamsg(400, '请给物流打分');
        }

        $member = db('member')->where(['id'=>$userId])->find();
        $orderGoodsInfo = db('order_goods')->where('order_id',$orderId)->where('goods_id',$goodsId)->find();
        if(!$orderGoodsInfo){
            datamsg(400,'商品信息有误，暂时无法评价');
        }else{
            $shopId = $orderGoodsInfo['shop_id'];
            $orderGoodsId = $orderGoodsInfo['id'];
        }

        $data['user_id'] = $userId;
        $data['content'] = $content;
        $data['order_id'] = $orderId;
        $data['shop_id'] = $shopId;
        $data['goods_star'] = $goodsStar;
        $data['logistics_star'] = $logisticsStar;
        $data['service_star'] = $serviceStar;
//                    $data['tags'] = $tags;
        $data['goods_id'] = $goodsId;
        $data['orgoods_id'] = $orderGoodsId;
        $data['time'] = time();
        Db::startTrans();
        $commentResult = Db::name('comment')->insertGetId($data);
        $datapic = explode(',', $fpic);
        $picarr = [];
        foreach ($datapic as $key => $value) {
            $picarr[$key]['img_url'] = $value;
            $picarr[$key]['com_id'] = $commentResult;
        }
        $resultpic = Db::name('comment_pic')->insertAll($picarr);
        $updateCommentStatus = Db::name('order_goods')->where('goods_id',$goodsId)->where('order_id',$orderId)->update(['ping'=>1]);
        // 查找未评价的商品
        $findNoCommentGoods = Db::name('order_goods')->where('order_id',$orderId)->where('ping',0)->find();
        if(!$findNoCommentGoods){
            Db::name('order')->where('id',$orderId)->update(['ping'=>1]);
        }

        if ($commentResult && $resultpic && $updateCommentStatus) {
            //9订单评价（次）(会员积分)
            $num0 = $this->getIntegralValue(9);//获取积分
            $this->addIntegral($userId,$num0,9,$orderId);
            Db::commit();
            datamsg(200, '发布成功');
        }else{
            Db::rollback();
            datamsg(400, '发布失败');
        }
    }

    // 我的商品评价列表
    public function myGoodsCommentList(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }

        $size = input('param.size') ?  input('param.size') : 5;
        if(!is_numeric($size)){
            datamsg(400,'长度类型错误');
        }
        $type = input('param.type');
        $where=[];
        $list = Db::name('comment')
            ->alias('c')
            ->join('sp_order o', 'c.order_id = o.id', 'LEFT')
            ->join('sp_order_goods og','c.orgoods_id = og.id','LEFT')
            ->where('c.user_id',$userId)
            ->field('c.*,o.id as oid,o.ordernumber as ordernuber,og.goods_name,og.thumb_url')
            ->order('time desc')
            ->paginate($size)
            ->each(function ($item, $key) {
                $item['thumb_url'] = url_format($item['thumb_url'],$this->webconfig['weburl'],'?imageMogr2/thumbnail/300x300');
                $item['createtime'] = date('Y-m-d H:i:s', $item['time']);
                $imgurl_arr = db('comment_pic')->where(['com_id' => $item['id']])->column('img_url');
                foreach($imgurl_arr as $key1=>$value){
                    if(empty($value)){
                        continue;
                    }
                    $item['imgurl'][$key1] = url_format($value,$this->webconfig['weburl'],'?imageMogr2/thumbnail/200x200');
                }
                return $item;
            });
        $list_copy = $list->toArray();
        datamsg(200,'获取数据成功',$list_copy);


    }

    // 商品评价列表
    public function goodsCommentList()
    {
	    $tokenRes = $this->checkToken(0);
	    if ($tokenRes['status'] == 400) {
		    datamsg(400, $tokenRes['mess'], $tokenRes['data']);
	    }

	    $size = input('param.size') ? input('param.size') : 5;
	    if (!is_numeric($size)) {
		    datamsg(400, '长度类型错误');
	    }
	    $goodsId = input('post.goods_id');
	    if (empty($goodsId)) {
		    datamsg(400, '缺少商品ID参数');
	    }
	    $type     = input('param.type');
	    $where    = [];


	    $list      = Db::name('comment')
	                   ->alias('c')
	                   ->join('sp_goods g', 'c.goods_id = g.id', 'LEFT')
	                   ->join('sp_member m', 'c.user_id = m.id', 'LEFT')
	                   ->where('c.checked', 1)
	                   ->where('c.goods_id', $goodsId)
	                   ->field('c.*,g.thumb_url,m.user_name,m.headimgurl,m.oauth')
	                   ->order('time desc')
	                   ->paginate($size)
	                   ->each(function ($item, $key) {
		                   $domain            = $this->webconfig['weburl'];
		                   $item['thumb_url'] = url_format($item['thumb_url'],$this->webconfig['weburl'],'?imageMogr2/thumbnail/350x350');
		                   if ($item['oauth'] == 0) {
			                   $item['headimgurl'] = url_format($item['headimgurl'],$this->webconfig['weburl'],'?imageMogr2/thumbnail/150x150');
		                   }
		                   $item['createtime'] = date('Y-m-d H:i:s', $item['time']);
		                   $imgurl_arr         = db('comment_pic')->where(['com_id' => $item['id']])
		                                                          ->column('img_url');
		                   foreach ($imgurl_arr as $key1 => $value) {
			                   $item['imgurl'][$key1] = url_format($value,$this->webconfig['weburl'],'?imageMogr2/thumbnail/350x350');
		                   }
		                   return $item;
	                   });
	    $list_copy = $list->toArray();
	    datamsg(200, '获取数据成功', $list_copy);

    }

    //删除商品评价
    public function deleteGoodsComment(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }

        $id = input('post.id');
        if(empty($id)){
            datamsg(400, '缺少id参数');
        }else{
            Db::startTrans();
            $commentInfo = db('comment')->where('id',$id)->find();
            $res = db('comment')->where(['user_id'=>$userId,'checked'=>0])->delete($id);
            // 删除评价后，将对应的订单商品和订单的评价状态设置为0
            $updateOrderGoodsCommentStatus = db('order_goods')->where('id',$commentInfo['orgoods_id'])->update(['ping'=>0]);
            $updateOrderCommentStatus = db('order')->where('id',$commentInfo['order_id'])->update(['ping'=>0]);

            if($res && $updateOrderGoodsCommentStatus){
                Db::commit();
                datamsg(200, '删除成功');
            }else{
                Db::rollback();
                datamsg(400, '删除失败');
            }
        }


    }
    
    //商家端查看所有的评价
    public function getShopCommentList() {
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        $shop_id = input('shop_id');
        if (!is_numeric($shop_id)) {
            datamsg(400, 'shop_id参数错误');
        }
        $page = input('post.page', 1);
        if (!preg_match("/^\\+?[1-9][0-9]*$/", $page)) {
            datamsg(400, 'page参数错误');
        }
        $webconfig = $this->webconfig;
        $pageSize = $webconfig['app_goodlst_num'];
        $offset = ($page - 1) * $pageSize;
        $commentModel = new CommentModel();
        $picModel = new CommentPic();
        $comment_list = $commentModel->getShopCommentList($shop_id, $offset, $pageSize);

        foreach ($comment_list as $key => $v) {
            $comment_list[$key]['time'] = date('Y-m-d H:i:s', $v['time']);
            $comment_list[$key]['goods_img'] = url_format($v['thumb_url'],$webconfig['weburl']);
            $pic_list = $picModel->getCommentPicList($v['id']);
            $img_list = array();
            foreach ($pic_list as $item) {
                $img_list[] = url_format($item['img_url'],$webconfig['weburl'],'?imageMogr2/thumbnail/200x200');
            }
            $comment_list[$key]['img_list'] = $img_list;
        }
        $data = array('comment_list' => $comment_list);
        datamsg(200, '获取数据成功', $data);
    }
    
}