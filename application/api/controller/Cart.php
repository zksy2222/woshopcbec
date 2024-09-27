<?php
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use app\api\model\Goods as GoodsModel;
use app\api\model\GoodsSpec as GoodsSpecModel;
use app\api\model\GoodsOption as GoodsOptionModel;
use app\api\model\GoodsSpecItem as GoodsSpecItemModel;
use think\Db;

class Cart extends Common{

    //加入购物车接口
    public function addcart(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
	    $data = input('post.');
	    if(empty($data['goods_id']) || empty($data['num'])){
		    datamsg(400,'缺少参数，加入购物车失败',array('status'=>400));
	    }
        $goodsId = $data['goods_id'];
	    if(!$goodsId){
		    datamsg(400,'缺少商品id',array('status'=>400));
	    }
        $num = $data['num'];
	    if(!preg_match("/^\\+?[1-9][0-9]*$/", $num)){
		    datamsg(400,'商品数量参数格式错误，加入购物车失败',array('status'=>400));
	    }
        $goods = Db::name('goods')->alias('a')->field('a.id,a.shop_price,a.shop_id,a.hasoption')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$goodsId)->where('a.onsale',1)->where('b.open_status',1)->find();
	    if(!$goods){
		    datamsg(400,'商品已下架或不存在',array('status'=>400));
	    }

        $goodsSpecItemIdArr = explode('_', $data['goods_attr']);

        if($goods['hasoption']){
            $goodsSpecModel = new GoodsSpecModel();
            $checkSpec = $goodsSpecModel->checkGoodsSpec($goodsId,$goodsSpecItemIdArr);
            if($checkSpec['status'] == 400){
                datamsg(400,$checkSpec['mess']);
            }
        }

        $ruinfo = array('id'=>$goodsId,'shop_id'=>$goods['shop_id']);
        $ru_attr = implode(',',$goodsSpecItemIdArr);

        $commonModel = new CommonModel();
        $activitys = $commonModel->getActivityInfo($ruinfo,$ru_attr);
        $goodsModel = new GoodsModel();

        $goodsSpecItemIdStr = implode('_',$goodsSpecItemIdArr);
        if($activitys){
            if($activitys['ac_type'] == 1){
                $goods_number = $goodsModel->getGoodsOptionStock($goodsId,$goodsSpecItemIdStr,'seckill');
            }
            if($activitys['ac_type'] == 2){
                datamsg(400,'积分换购商品不允许加入购物车',array('status'=>400));
            }
            if($activitys['ac_type'] == 3){
                datamsg(400,'拼团活动商品不允许加入购物车',array('status'=>400));
            }

        }else{
            $goods_number = $goodsModel->getGoodsOptionStock($goodsId,$goodsSpecItemIdStr);
        }

        if($goods_number < 0){
	        datamsg(400,'商品库存不足',array('status'=>400));
        }
	    if($num < 0 && $num > $goods_number){
		    datamsg(400,'商品库存不足',array('status'=>400));
	    }

        $cgoods = Db::name('cart')->where('user_id',$userId)->where('goods_id',$goodsId)->where('goods_attr',$goodsSpecItemIdStr)->where('shop_id',$goods['shop_id'])->find();
        $datainfo = array();

        if(!$cgoods){
            if($activitys && $activitys['ac_type'] == 1){
                if($num > $activitys['xznum']){
                    datamsg(400,lang('该秒杀商品限购').$activitys['xznum'].lang('件'),array('status'=>400));
                }
            }

            $datainfo['goods_id'] = $goodsId;
            $datainfo['goods_attr'] = $goodsSpecItemIdStr;
            $datainfo['num'] = $num;
            $datainfo['shop_id'] = $goods['shop_id'];
            $datainfo['user_id'] = $userId;
            $datainfo['add_time'] = time();
            $lastId = Db::name('cart')->insert($datainfo);
            if($lastId){
	            datamsg(200,'加入购物车成功',array('status'=>200));
            }else{
	            datamsg(400,'操作失败，请重试',array('status'=>400));
            }
        }else{
	        if($cgoods['num']+$num > $goods_number){
		        datamsg(400,'商品库存不足',array('status'=>400));
	        }

            if($activitys && $activitys['ac_type'] == 1){
                if($cgoods['num']+$num > $activitys['xznum']){
	                datamsg(400,lang('该秒杀商品限购').$activitys['xznum'].lang('件'),array('status'=>400));
                }
            }
            $datainfo['num'] = $cgoods['num']+$num;
            $datainfo['id'] = $cgoods['id'];
            $count = Db::name('cart')->update($datainfo);
            if($count>0){
	            datamsg(200,'加入购物车成功',array('status'=>200));
            }else{
	            datamsg(400,'操作失败，请重试',array('status'=>400));
            }

        }


    }


    //获取购物车商品列表接口
    public function index(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
	    if(!input('post.page') && !preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
		    datamsg(400,'缺少参数，加入购物车失败',array('status'=>400));
	    }

        $webconfig = $this->webconfig;
        $perpage = 20;
        $offset = (input('post.page')-1)*$perpage;

        $cartres = Db::name('cart')->alias('a')->field('a.id,a.goods_id,a.goods_attr,a.num,a.shop_id,b.goods_name,b.shop_price,b.thumb_url,c.shop_name')->join('sp_goods b','a.goods_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where('a.user_id',$userId)->where('b.onsale',1)->where('c.open_status',1)->order('a.add_time desc')->limit($offset,$perpage)->select();

        $cartinfores = array();
        $goodsModel = new GoodsModel();
        if($cartres){
            foreach ($cartres as $k => $v){
                $cartres[$k]['icon'] = 0;
                $cartres[$k]['thumb_url'] = url_format($v['thumb_url'],$webconfig['weburl']);

                $ruinfo = array('id'=>$v['goods_id'],'shop_id'=>$v['shop_id']);
                $ru_attr = str_replace('_',',',$v['goods_attr']);
                $commonModel = new CommonModel();
                $activitys = $commonModel->getActivityInfo($ruinfo,$ru_attr);

                $goodsOptionModel = new GoodsOptionModel();

                if($activitys){
                    if($activitys['ac_type'] == 3){
                        unset($cartres[$k]);
                        continue;
                    }

                    $cartres[$k]['is_activity'] = $activitys['ac_type'];

                    if($activitys['ac_type'] == 1){
                        $cartres[$k]['xznum'] = $activitys['xznum'];
                        $cartres[$k]['shop_price'] = $goodsModel->getGoodsOptionPrice($v['goods_id'],$v['goods_attr'],'seckill');
                    }

                    $cartres[$k]['sytime'] = time2string($activitys['end_time']-time());

                    if($v['goods_attr']){
                        $cartres[$k]['goods_attr_str'] = '';
                        $goodsSpecItemModel = new GoodsSpecItemModel();
                        $goodsSpecItemIdArr = explode('_',$v['goods_attr']);
                        $specItemInfo = $goodsSpecItemModel->getGoodsSpecAndSpecItemInfo($goodsSpecItemIdArr);
                        unset($goodsSpecItemIdArr);
                        foreach ($specItemInfo as $key => $val){
                            $str = $key == count($specItemInfo) - 1 ? '' : ';';
                            $cartres[$k]['goods_attr_str'] .= $val['goods_spec']['title'].':'.$val['title'].$str;
                        }
                    }else{
                        $specItemInfo = array();
                        $cartres[$k]['goods_attr_str'] = '';
                    }
                }else{
                    $cartres[$k]['is_activity'] = 0;

                    if($v['goods_attr']){
                        $cartres[$k]['goods_attr_str'] = '';
                        $goodsSpecItemModel = new GoodsSpecItemModel();
                        $goodsSpecItemIdArr = explode('_',$v['goods_attr']);
                        $specItemInfo = $goodsSpecItemModel->getGoodsSpecAndSpecItemInfo($goodsSpecItemIdArr);
                        unset($goodsSpecItemIdArr);
                        foreach ($specItemInfo as $key => $val){
                            $str = $key == count($specItemInfo) - 1 ? '' : ';';
                            $cartres[$k]['goods_attr_str'] .= $val['goods_spec']['title'].':'.$val['title'].$str;
                        }
                        $cartres[$k]['shop_price'] = $goodsModel->getGoodsOptionPrice($v['goods_id'],$v['goods_attr']);
                    }else{
                        $specItemInfo = array();
                        $cartres[$k]['goods_attr_str'] = '';
                    }
                }
            }

            foreach ($cartres as $cr){
                $cartinfores[$cr['shop_id']]['goodres'][] = $cr;
            }

            foreach ($cartinfores as $kc => $vc){
                $cartinfores[$kc]['couponinfos'] = array('is_show'=>0,'infos'=>'');
                $cartinfores[$kc]['promotions'] = array('is_show'=>0,'infos'=>'');
                $cartinfores[$kc]['icon'] = 0;

                $coupons = Db::name('coupon')->where('shop_id',$kc)->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->field('id,man_price,dec_price')->order('man_price asc')->find();
                if($coupons){
                    $cartinfores[$kc]['couponinfos'] = array('is_show'=>1,'infos'=>lang('用优惠券可享满').$coupons['man_price'].lang('减').$coupons['dec_price']);
                }

                $proarr = array();

                foreach ($vc['goodres'] as $vp){
                    $promotions = Db::name('promotion')->where("find_in_set('".$vp['goods_id']."',info_id)")->where('shop_id',$vp['shop_id'])->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,start_time,end_time')->find();
                    if($promotions){
                        $prom_typeres = Db::name('prom_type')->where('prom_id',$promotions['id'])->select();
                        if($prom_typeres){
                            foreach ($prom_typeres as $kcp => $vcp){
                                $zhekou = $vcp['discount']/10;
                                if($kcp == 0){
                                    $proarr[$promotions['id']] = lang('部分商品满').$vcp['man_num'].lang('件 享').$zhekou.lang('折');
                                }else{
                                    $proarr[$promotions['id']] = $proarr[$promotions['id']].lang('  满 ').$vcp['man_num'].lang('件 享').$zhekou.lang('折');
                                }
                            }
                        }
                    }
                }

                if($proarr){
                    $proarr = array_values($proarr);
                    $cartinfores[$kc]['promotions'] = array('is_show'=>1,'infos'=>$proarr);
                }
            }

            $cartinfores = array_values($cartinfores);

        }
	    datamsg(200,'获取购物车信息成功',$cartinfores);
    }

     //直播间获取购物车商品列表接口
     public function roomCartGoods(){
	     $tokenRes = $this->checkToken();
	     if($tokenRes['status'] == 400){
		     datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	     }else{
		     $userId = $tokenRes['user_id'];
	     }
        $shop_id = input('post.shop_id');
        if(empty($shop_id)){
            datamsg(400,'缺少店铺id参数');
        }
	     if(!input('post.page') && !preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
		     datamsg(400,'缺少参数，加入购物车失败',array('status'=>400));
	     }
        $webconfig = $this->webconfig;
        $perpage = 20;
        $offset = (input('post.page')-1)*$perpage;

        $cartres = Db::name('cart')->alias('a')->field('a.id,a.goods_id,a.goods_attr,a.num,a.shop_id,b.goods_name,b.shop_price,b.thumb_url,c.shop_name')->join('sp_goods b','a.goods_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where('a.user_id',$userId)->where('b.onsale',1)->where('c.open_status',1)->where('a.shop_id',$shop_id)->order('a.add_time desc')->limit($offset,$perpage)->select();

        $cartinfores = array();

        if($cartres){
            foreach ($cartres as $k => $v){
                $cartres[$k]['icon'] = 0;

                $cartres[$k]['thumb_url'] = url_format($v['thumb_url'],$webconfig['weburl']);

                $ruinfo = array('id'=>$v['goods_id'],'shop_id'=>$v['shop_id']);
                $ru_attr = $v['goods_attr'];

                $commonModel = new CommonModel();
                $activitys = $commonModel->getActivityInfo($ruinfo,$ru_attr);

                if($activitys){
                    if($activitys['ac_type'] == 3){
                        unset($cartres[$k]);
                        continue;
                    }

                    $cartres[$k]['is_activity'] = $activitys['ac_type'];

                    if($activitys['ac_type'] == 1){
                        $cartres[$k]['xznum'] = $activitys['xznum'];
                    }

                    $cartres[$k]['sytime'] = time2string($activitys['end_time']-time());

                    if($v['goods_attr']){
                        $cartres[$k]['goods_attr_str'] = '';
                        $specItemInfo = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$v['goods_attr'])->where('a.goods_id',$v['goods_id'])->where('b.attr_type',1)->select();
                        if($specItemInfo){
                            foreach ($specItemInfo as $key => $val){
                                if($key == 0){
                                    $cartres[$k]['goods_attr_str'] = $val['attr_name'].':'.$val['attr_value'];
                                }else{
                                    $cartres[$k]['goods_attr_str'] = $cartres[$k]['goods_attr_str'].' '.$val['attr_name'].':'.$val['attr_value'];
                                }
                            }
                        }
                    }else{
                        $specItemInfo = array();
                        $cartres[$k]['goods_attr_str'] = '';
                    }

                    $cartres[$k]['shop_price'] = $activitys['price'];
                }else{
                    $cartres[$k]['is_activity'] = 0;

                    if($v['goods_attr']){
                        $cartres[$k]['goods_attr_str'] = '';
                        $specItemInfo = Db::name('goods_attr')->alias('a')->field('a.*,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$v['goods_attr'])->where('a.goods_id',$v['goods_id'])->where('b.attr_type',1)->select();
                        if($specItemInfo){
                            foreach ($specItemInfo as $key => $val){
                                $cartres[$k]['shop_price']+=$val['attr_price'];
                                if($key == 0){
                                    $cartres[$k]['goods_attr_str'] = $val['attr_name'].':'.$val['attr_value'];
                                }else{
                                    $cartres[$k]['goods_attr_str'] = $cartres[$k]['goods_attr_str'].' '.$val['attr_name'].':'.$val['attr_value'];
                                }
                            }
                            $cartres[$k]['shop_price']=sprintf("%.2f", $cartres[$k]['shop_price']);
                        }
                    }else{
                        $specItemInfo = array();
                        $cartres[$k]['goods_attr_str'] = '';
                    }
                }
            }

            foreach ($cartres as $cr){
                $cartinfores[$cr['shop_id']]['goodres'][] = $cr;
            }

            foreach ($cartinfores as $kc => $vc){
                $cartinfores[$kc]['couponinfos'] = array('is_show'=>0,'infos'=>'');
                $cartinfores[$kc]['promotions'] = array('is_show'=>0,'infos'=>'');
                $cartinfores[$kc]['icon'] = 0;

                $coupons = Db::name('coupon')->where('shop_id',$kc)->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->field('id,man_price,dec_price')->order('man_price asc')->find();
                if($coupons){
                    $cartinfores[$kc]['couponinfos'] = array('is_show'=>1,'infos'=>'用优惠券可享满'.$coupons['man_price'].'减'.$coupons['dec_price']);
                }

                $proarr = array();

                foreach ($vc['goodres'] as $vp){
                    $promotions = Db::name('promotion')->where("find_in_set('".$vp['goods_id']."',info_id)")->where('shop_id',$vp['shop_id'])->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,start_time,end_time')->find();
                    if($promotions){
                        $prom_typeres = Db::name('prom_type')->where('prom_id',$promotions['id'])->select();
                        if($prom_typeres){
                            foreach ($prom_typeres as $kcp => $vcp){
                                $zhekou = $vcp['discount']/10;
                                if($kcp == 0){
                                    $proarr[$promotions['id']] = '部分商品满 '.$vcp['man_num'].'件 享'.$zhekou.'折';
                                }else{
                                    $proarr[$promotions['id']] = $proarr[$promotions['id']].'  满 '.$vcp['man_num'].'件 享'.$zhekou.'折';
                                }
                            }
                        }
                    }
                }

                if($proarr){
                    $proarr = array_values($proarr);
                    $cartinfores[$kc]['promotions'] = array('is_show'=>1,'infos'=>$proarr);
                }
            }

            $cartinfores = array_values($cartinfores);

            /*foreach ($cartinfores as $kc => $vc){
                $coupons = Db::name('coupon')->where('shop_id',$kc)->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->field('id,man_price,dec_price')->order('man_price asc')->find();
                if($coupons){
                    $shprice = 0;
                    foreach ($vc as $vp){
                        $shprice+=$vp['shop_price'];
                    }

                    $couinfos = Db::name('coupon')->where('shop_id',$kc)->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->where('man_price','elt',$shprice)->field('id,man_price,dec_price')->order('man_price desc')->find();
                    if($couinfos){
                        $cartinfores[$kc]['couponinfos'] = array('show'=>1,'infos'=>$couinfos['dec_price'].'元店铺优惠券 （满'.$couinfos['man_price'].'元）','dec_price'=>$couinfos['dec_price']);
                    }else{
                        $cartinfores[$kc]['couponinfos'] = array('show'=>1,'infos'=>'','dec_price'=>0);
                    }
                }else{
                    $cartinfores[$kc]['couponinfos'] = array('show'=>0,'infos'=>'','dec_price'=>0);
                }
            }*/
        }
        datamsg(200,'获取购物车信息成功',$cartinfores);
    }

    //修改购物车商品信息
    public function editcart(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
	    if(!input('post.cart_id')) {
		    datamsg(400, '缺少购物车参数', array('status' => 400));
	    }
	    if(!input('post.num')) {
		    datamsg(400, '商品数量参数错误', array('status' => 400));
	    }

        $cart_id = input('post.cart_id');
        $num = input('post.num');

	    if(!preg_match("/^\\+?[1-9][0-9]*$/", $num)) {
		    datamsg(400, '商品数量参数格式错误', array('status' => 400));
	    }

        $carts = Db::name('cart')
                   ->alias('a')
                   ->field('a.*')
                   ->join('sp_goods b','a.goods_id = b.id','INNER')
                   ->join('sp_shops c','a.shop_id = c.id','INNER')
                   ->where('a.id',$cart_id)
                   ->where('a.user_id',$userId)
                   ->where('b.onsale',1)
                   ->where('c.open_status',1)
                   ->find();

	    if(!$carts) {
		    datamsg(400, '找不到相关购物车信息', array('status' => 400));
	    }

        $ruinfo = array('id'=>$carts['goods_id'],'shop_id'=>$carts['shop_id']);
        $ru_attr = $carts['goods_attr'];

        $commonModel = new CommonModel();
        $activitys = $commonModel->getActivityInfo($ruinfo,$ru_attr);

        $goodsModel = new GoodsModel();
        if($activitys){
            if($activitys['ac_type'] == 1){
                $goods_number = $goodsModel->getGoodsOptionStock($carts['goods_id'],$carts['goods_attr'],'seckill');
            }
            if ($activitys['ac_type'] == 2) {
                datamsg(400, '积分换购商品不允许加入购物车',array('status'=>400));
            }
            if($activitys['ac_type'] == 3){
                datamsg(400,'拼团活动商品不允许加入购物车',array('status'=>400));
            }

        }else{
            $goods_number = $goodsModel->getGoodsOptionStock($carts['goods_id'],$carts['goods_attr']);
        }

        if($num < $carts['num']){
            $count = Db::name('cart')->where('id',$cart_id)->where('user_id',$userId)->update(array('num'=>$num));
            if($count > 0){
	            datamsg(200, '操作成功', array('status' => 200));
            }else{
	            datamsg(400, '操作失败', array('status' => 400));
            }
        }elseif($num == $carts['num']){
            // 前端提交的数量与数据库一致，不做处理
            // datamsg(400, '操作失败2', array('status' => 400));
        }elseif($num > $carts['num']){
	        if($num > $goods_number){
		        datamsg(400, '商品库存不足', array('status' => 400));
	        }

            if($activitys && $activitys['ac_type'] == 1){
                if($num > $activitys['xznum']){
	                datamsg(400, '该秒杀商品限购'.$activitys['xznum'].'件', array('status' => 400));
                }
            }

            $count = Db::name('cart')->where('id',$cart_id)->where('user_id',$userId)->update(array('num'=>$num));
            if($count > 0){
	            datamsg(200, '操作成功', array('status' => 200));
            }else{
	            datamsg(400, '操作失败', array('status' => 400));
            }

        }
    }

    //删除购物车信息
    public function delcart(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }else{
            $userId = $tokenRes['user_id'];
        }

        if(input('post.cart_id') && !is_array(input('post.cart_id'))){
            $cart_id = input('post.cart_id');
            $cart_id = trim($cart_id);
            $cart_id = str_replace('，', ',', $cart_id);
            $cart_id = rtrim($cart_id,',');

            if($cart_id){
                if(strpos($cart_id, ',') !== false){
                    $cartres = explode(',', $cart_id);
                    $cartres = array_unique($cartres);

                    if($cartres && is_array($cartres)){
                        foreach ($cartres as $v){
                            if(!empty($v)){
                                $carts = Db::name('cart')->where('id',$v)->where('user_id',$userId)->find();
                                if(!$carts){
                                    datamsg(400,'购物车参数错误',array('status'=>400));
                                }
                            }else{
                                datamsg(400,'购物车参数错误',array('status'=>400));
                            }
                        }

                        $cartstr = implode(',', $cartres);
                        $count = Db::name('cart')->where('id','in',$cartstr)->delete();
                    }else{
                        datamsg(400,'购物车参数错误',array('status'=>400));
                    }
                }else{
                    $carts = Db::name('cart')->where('id',$cart_id)->where('user_id',$userId)->find();
                    if($carts){
                        $count = Db::name('cart')->where('id',$cart_id)->delete();
                    }else{
                        datamsg(400,'购物车参数错误',array('status'=>400));
                    }
                }

                if($count > 0){
                    datamsg(200,'删除成功',array('status'=>200));
                }else{
                    datamsg(400,'删除失败',array('status'=>400));
                }
            }else{
                datamsg(400,'购物车参数错误',array('status'=>400));
            }
        }else{
            datamsg(400,'缺少购物车参数',array('status'=>400));
        }

    }



    //获取购物车商品数量
    public function getnum(){
        $needUserToken = input('post.token') ? 1 : 0;
	    $tokenRes = $this->checkToken($needUserToken);
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        $countnum = Db::name('cart')->alias('a')->field('a.*')->join('sp_goods b','a.goods_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where('a.user_id',$userId)->where('b.onsale',1)->where('c.open_status',1)->group('a.goods_id,a.goods_attr')->count();

	    datamsg(200, '获取购物车数量信息成功', array('countnum'=>$countnum));
    }

    // 客服加入购物车
    public function addCartByService(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
	    $data = input('post.');
	    if(empty($data['goods_id']) || empty($data['num'])){
		    datamsg(400,'缺少参数，加入购物车失败',array('status'=>400));
	    }
        $goodsId = $data['goods_id'];
        $num = $data['num'];

        if(!preg_match("/^\\+?[1-9][0-9]*$/", $num)) {
	        datamsg(400,'商品数量参数格式错误，加入购物车失败',array('status'=>400));
        }
        $goods = Db::name('goods')->alias('a')->field('a.id,a.shop_price,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$goodsId)->where('a.onsale',1)->where('b.open_status',1)->find();
	    if(!$goods){
		    datamsg(400,'商品已下架或不存在',array('status'=>400));
	    }

        $radiores = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.goods_id',$goodsId)->where('b.attr_type',1)->select();

        if($radiores){
	        if(empty($data['goods_attr']) && is_array($data['goods_attr'])){
		        datamsg(400,'请选择商品属性',array('status'=>400));
	        }

            $data['goods_attr'] = trim($data['goods_attr']);
            $data['goods_attr'] = str_replace('，', ',', $data['goods_attr']);
            $data['goods_attr'] = rtrim($data['goods_attr'],',');

            if(!$data['goods_attr']){
	            datamsg(400,'商品属性参数错误',array('status'=>400));
            }

            $gattr = explode(',', $data['goods_attr']);
            $gattr = array_unique($gattr);

            if(!$gattr && !is_array($gattr)){
	            datamsg(400,'商品属性参数错误',array('status'=>400));
            }

            $radioattr = array();
            foreach ($radiores as $va){
                $radioattr[$va['attr_id']][] = $va['id'];
            }

            $gattres = array();

            foreach ($gattr as $ga){
	            if(empty($ga)){
		            datamsg(400,'商品属性参数错误',array('status'=>400));
	            }
                $goodsxs = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id',$ga)->where('a.goods_id',$goodsId)->where('b.attr_type',1)->find();
                if($goodsxs){
                    $gattres[$goodsxs['attr_id']] = $goodsxs['id'];
                }else{
	                datamsg(400,'商品属性参数错误',array('status'=>400));
                }

            }

            foreach ($radioattr as $key => $val){
                if(empty($gattres[$key]) || !in_array($gattres[$key],$val)){
	                datamsg(400,'请选择商品属性',array('status'=>400));
                }
            }

            foreach ($gattres as $key2 => $val2){
                if(empty($radioattr[$key2]) || !in_array($val2, $radioattr[$key2]) ){
	                datamsg(400,'商品属性参数错误',array('status'=>400));
                }
            }
            $goods_attr = implode(',', $gattr);
        }else{
            if(empty($data['goods_attr'])){
                $goods_attr = '';
            }else{
                datamsg(400,'参数错误',array('status'=>400));
            }
        }

        $ruinfo = array('id'=>$goodsId,'shop_id'=>$goods['shop_id']);
        $ru_attr = $goods_attr;

        $commonModel = new CommonModel();
        $activitys = $commonModel->getActivityInfo($ruinfo,$ru_attr);

        if($activitys){
            if($activitys['ac_type'] == 1){
                $goods_number = $activitys['stock'];
            }else{
                if ($activitys['ac_type'] == 2) {
                    datamsg(400, '积分换购商品不允许加入购物车',array('status'=>400));
                }
                if($activitys['ac_type'] == 3){
	                datamsg(400,'拼团活动商品不允许加入购物车',array('status'=>400));
                }

                $prores = Db::name('product')->where('goods_id',$goodsId)->where('goods_attr',$goods_attr)->field('goods_number')->find();

                if($prores){
                    $goods_number = $prores['goods_number'];
                }else{
                    $goods_number = 0;
                }
            }
        }else{
            $prores = Db::name('product')->where('goods_id',$goodsId)->where('goods_attr',$goods_attr)->field('goods_number')->find();

            if($prores){
                $goods_number = $prores['goods_number'];
            }else{
                $goods_number = 0;
            }
        }
	    if($goods_number <= 0){
		    datamsg(400,'商品库存不足',array('status'=>400));
	    }

        if($num > 0 && $num > $goods_number) {
            datamsg(400,'商品库存不足',array('status'=>400));
        }

        $cgoods = Db::name('cart')->where('user_id',$userId)->where('goods_id',$goodsId)->where('goods_attr',$goods_attr)->where('shop_id',$goods['shop_id'])->find();
        $datainfo = array();

        if(!$cgoods){
            if($activitys && $activitys['ac_type'] == 1){
                if($num > $activitys['xznum']){
                    datamsg(400,'该秒杀商品限购',array('status'=>400));
                }
            }

            $datainfo['goods_id'] = $goodsId;
            $datainfo['goods_attr'] = $goods_attr;
            $datainfo['num'] = $num;
            $datainfo['shop_id'] = $goods['shop_id'];
            $datainfo['user_id'] = $userId;
            $datainfo['add_time'] = time();
            $lastId = Db::name('cart')->insert($datainfo);
            if($lastId){
                datamsg(200,'加入购物车成功',array('status'=>200));
            }else{
                datamsg(400,'操作失败，请重试',array('status'=>400));
            }
        }else{
	        if($cgoods['num']+$num > $goods_number){
		        datamsg(400,'商品库存不足',array('status'=>400));
	        }

            if($activitys && $activitys['ac_type'] == 1){
                if($cgoods['num']+$num > $activitys['xznum']){
                    datamsg(400,lang('该秒杀商品限购').$activitys['xznum'].lang('件'),array('status'=>400));
                }
            }


            $datainfo['num'] = $cgoods['num']+$num;
            $datainfo['id'] = $cgoods['id'];
            $count = Db::name('cart')->update($datainfo);
            if($count>0){
                datamsg(200,'加入购物车成功',array('status'=>200));
            }else{
	            datamsg(400,'操作失败，请重试',array('status'=>400));
            }
        }
    }

}