<?php
namespace app\api\controller;

use app\api\model\GoodsParam;
use think\Db;
use app\api\model\Common as CommonModel;
use app\api\model\Goods as GoodsModel;
use app\api\model\GoodsSpec as GoodsSpecModel;
use app\api\model\GoodsOption as GoodsOptionModel;
use app\api\model\GoodsParam as GoodsParamModel;
use app\api\model\Dispatch as DispatchModel;

class Goods extends Common{
    //根据分类获取商品列表
    public function getCategoryGoodsList(){
	    $tokenRes = $this->checkToken(0);
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }
	    if(!input('post.cate_id')){
	        datamsg(400,'缺少分类参数',array('status'=>400));
	    }
	    if(!input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", !input('post.page'))){
		    datamsg(400,'缺少页面参数',array('status'=>400));
	    }
        $cate_id = input('post.cate_id');
        $pagenum = input('post.page');
        $cates = Db::name('category')->where('id',$cate_id)->where('is_show',1)->field('id,cate_name')->find();
        if(!$cates){
            datamsg(400,'分类信息参数错误',array('status'=>400));
        }
        $categoryres = Db::name('category')->where('is_show',1)->field('id,pid')->order('sort asc')->select();
        $cateIds = array();
        $cateIds = get_all_child($categoryres, $cate_id);
        $cateIds[] = $cate_id;
        $cateIds = implode(',', $cateIds);

        $webconfig = $this->webconfig;
        $perpage = $webconfig['app_goodlst_num'];
        $offset = ($pagenum-1)*$perpage;

        $where1 = "a.cate_id in (".$cateIds.")";
        $where2 = "a.onsale = 1";
        $where3 = '';
        $where4 = '';
        $where5 = '';
        $where6 = '';

        if(input('post.goods_type') && input('post.goods_type') != 'all'){
            $goods_type = input('post.goods_type');
            switch($goods_type){
                case 1:
                    $where3 = "a.leixing = 1";
                    break;
                case 2:
                    $where3 = "a.is_activity = 1";
                    break;
            }
        }

        if(input('post.low_price') && input('post.max_price')){
            $low_price = input('post.low_price');
            $max_price = input('post.max_price');

            if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $low_price)){
	            datamsg(400,'最低价格格式错误',array('status'=>400));
            }

            if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $max_price)){
	            datamsg(400,'最高价格格式错误',array('status'=>400));
            }

            if($low_price >= $max_price){
	            datamsg(400,'最低价格需小于最大价格',array('status'=>400));
            }

            $where4 = "a.zs_price >= '".$low_price."' AND a.zs_price <= '".$max_price."'";
        }elseif(input('post.low_price') && !input('post.max_price')){
            $low_price = input('post.low_price');

            if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $low_price)){
	            datamsg(400,'最低价格格式错误',array('status'=>400));
            }

            $where4 = "a.zs_price >= '".$low_price."'";
        }elseif(!input('post.low_price') && input('post.max_price')){
            $max_price = input('post.max_price');

            if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $max_price)){
	            datamsg(400,'最高价格格式错误',array('status'=>400));
            }

            $where4 = "a.zs_price <= '".$max_price."'";
        }

        if(input('post.brand_id') && input('post.brand_id') != 'all'){
            $brand_id = input('post.brand_id');
            $where5 = "a.brand_id = ".$brand_id."";
        }

        if(input('post.goods_attr') && !is_array(input('post.goods_attr'))){
            $goods_attr = input('post.goods_attr');
            $goods_attr = trim($goods_attr);
            $goods_attr = str_replace('，', ',', $goods_attr);
            $goods_attr = rtrim($goods_attr,',');

            if($goods_attr){
                $goods_attr = explode(',', $goods_attr);
                $goods_attr = array_unique($goods_attr);

                if(!$goods_attr || !is_array($goods_attr)){
	                datamsg(400,'商品属性筛选条件参数错误',array('status'=>400));
                }
            }else{
	            datamsg(400,'商品属性筛选条件参数错误',array('status'=>400));
            }


            foreach ($goods_attr as $kca => $va){
                if(!empty($va)){
                    if($kca == 0){
                        $where6 = "find_in_set('".$va."',a.shuxings)";
                    }else{
                        $where6 = $where6." AND find_in_set('".$va."',a.shuxings)";
                    }
                }else{
	                datamsg(400,'商品属性筛选条件参数错误',array('status'=>400));
                }
            }
        }

        if(input('post.sort')){
            $sort = input('post.sort');
            switch($sort){
                case 'zonghe':
                    $sortarr = array('a.leixing'=>'desc','a.zonghe_lv'=>'desc','a.id'=>'desc');
                    break;
                case 'deal_num':
                    $sortarr = array('a.deal_num '=>'desc','a.id'=>'desc');
                    break;
                case 'low_height':
                    $sortarr = array('a.zs_price'=>'asc','a.id'=>'desc');
                    break;
                case 'height_low':
                    $sortarr = array('a.zs_price'=>'desc','a.id'=>'desc');
                    break;
                default:
                    $sortarr = array('a.leixing'=>'desc','a.zonghe_lv'=>'desc','a.id'=>'desc');
            }
        }else{
            $sortarr = array('a.leixing'=>'desc','a.zonghe_lv'=>'desc','a.id'=>'desc');
        }

        $goodres = Db::name('goods')
                     ->alias('a')
                     ->field('a.id,a.goods_name,a.thumb_url,a.min_price,a.zs_price,a.leixing,a.shop_id,a.is_live')
                     ->join('sp_shops b','a.shop_id = b.id','INNER')
                     ->where($where1)
                     ->where($where2)
                     ->where($where3)
                     ->where($where4)
                     ->where($where5)
                     ->where($where6)
                     ->where("b.open_status = 1")
                     ->order($sortarr)
                     ->limit($offset,$perpage)
                     ->select();

        if($goodres){
            foreach ($goodres as $k =>$v){
                $goodres[$k]['goods_name'] = $this->getGoodsLangName($v['id'],$this->langCode);
                $goodres[$k]['thumb_url'] = url_format($v['thumb_url'],$webconfig['weburl']);
                $goodres[$k]['coupon'] = 0;

                $ruinfo = array('id'=>$v['id'],'shop_id'=>$v['shop_id']);
                $commonModel = new CommonModel();
                $activity = $commonModel->getActivityInfo($ruinfo);

                if($activity){
                    $goodsModel = new GoodsModel();
                    $goodres[$k]['is_activity'] = $activity['ac_type'];
                    if($activity['ac_type'] == 1){
                        $seckillPrice = $goodsModel->getGoodsShowPrice($v['id'],'seckill','list');
                        $goodres[$k]['zs_price'] = $seckillPrice['seckill_price'];
                    }

                    if($activity['ac_type'] == 2){
                        $seckillPrice = $goodsModel->getGoodsShowPrice($v['id'],'integral','list');
                        $goodres[$k]['zs_price'] = $seckillPrice['integral_price'];
                        $goodres[$k]['integral'] = $seckillPrice['integral'];
                    }

                    if($activity['ac_type'] == 3){
                        $assemblePrice = $goodsModel->getGoodsShowPrice($v['id'],'assemble','list');
                        $goodres[$k]['zs_price'] = $assemblePrice['assemble_price'];
                    }
                    unset($seckillPrice);
                    unset($assemblePrice);
                }else{
                    $goodres[$k]['is_activity'] = 0;
                    $goodres[$k]['zs_price'] = $v['min_price'];
                }

                if(!$activity || in_array($activity['ac_type'], array(1,2))){
                    //优惠券
                    $coupons = Db::name('coupon')->where('shop_id',$v['shop_id'])->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->find();
                    if($coupons){
                        $goodres[$k]['coupon'] = 1;
                    }
                }
            }
        }

        if($pagenum == 1){
            $brandres = Db::name('brand')->where('find_in_set('.$cate_id.',cate_id_list)')->where('is_show',1)->field('id,brand_name')->select();
            $shaixuan = Db::name('attr')->where('type_id',3)->where('is_sear',1)->field('id,attr_name,attr_values')->select();
            if($shaixuan){
                foreach ($shaixuan as $key2 => $val2){
                    $shaixuan[$key2]['attr_values'] = explode(',',  $val2['attr_values']);
                }
            }

            $cateinfos = array('id'=>$cates['id'],'cate_name'=>$cates['cate_name']);

            $goodlstinfo = array('cates'=>$cateinfos,'goodres'=>$goodres,'brandres'=>$brandres,'shaixuan'=>$shaixuan);
        }else{
            $goodlstinfo = array('goodres'=>$goodres);
        }
	    datamsg(200,'获取商品信息成功',$goodlstinfo);
    }

    // 获取标签（新品、热销）商品列表
    public function getTagGoodsList(){
	    $tokenRes = $this->checkToken(0);
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }
	    if(!input('post.tag')){
		    datamsg(400,'缺少标签参数',array('status'=>400));
	    }
	    if(!input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", !input('post.page'))){
		    datamsg(400,'缺少页面参数',array('status'=>400));
	    }

        $tag = input('post.tag');
        $pagenum = input('post.page');

        if($tag == 'new') {
            $where1 = "a.is_new = 1";
        }
        if ($tag == 'recommend'){
            $where1 = "a.is_recommend = 1";
        }
        if ($tag == 'hot'){
            $where1 = "a.is_hot = 1";
        }
        $webconfig = $this->webconfig;
        $perpage = $webconfig['app_goodlst_num'];
        $offset = ($pagenum - 1) * $perpage;
        $where2 = "a.onsale = 1";

        $goodres = Db::name('goods')
                     ->alias('a')
                     ->field('a.id,a.goods_name,a.thumb_url,a.min_price,a.zs_price,a.leixing,a.shop_id,a.addtime')
                     ->join('sp_shops b','a.shop_id = b.id','INNER')
                     ->where($where1)
                     ->where($where2)
                     ->where("b.open_status = 1")
                     ->order('id DESC')
                     ->limit($offset,$perpage)
                     ->select();

        $total = Db::name('goods')
            ->alias('a')
            ->field('a.id')
            ->join('sp_shops b','a.shop_id = b.id','INNER')
            ->where($where1)
            ->where($where2)
            ->where("b.open_status = 1")
            ->count();
        if($goodres){
            foreach ($goodres as $k =>$v){
                $goodres[$k]['thumb_url'] = url_format($v['thumb_url'],$webconfig['weburl']);
                $goodres[$k]['coupon'] = 0;

                $ruinfo = array('id'=>$v['id'],'shop_id'=>$v['shop_id']);
                $commonModel = new CommonModel();
                $activity = $commonModel->getActivityInfo($ruinfo);

                if($activity){
                    $goodsModel = new GoodsModel();
                    $goodres[$k]['is_activity'] = $activity['ac_type'];
                    if($activity['ac_type'] == 1){
                        $seckillPriceData = $goodsModel->getGoodsShowPrice($v['id'],'seckill','list');
                        $goodres[$k]['zs_price'] = $seckillPriceData['seckill_price'];
                    }
                    if($activity['ac_type'] == 3){
                        $assemblePriceData = $goodsModel->getGoodsShowPrice($v['id'],'assemble','list');
                        $goodres[$k]['zs_price'] = $assemblePriceData['assemble_price'];
                    }
                    unset($seckillPriceData);
                    unset($assemblePriceData);
                }else{
                    $goodres[$k]['is_activity'] = 0;
                    $goodres[$k]['zs_price'] = $v['min_price'];
                }

                if(!$activity || in_array($activity['ac_type'], array(1,2))){
                    //优惠券
                    $coupons = Db::name('coupon')->where('shop_id',$v['shop_id'])->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->find();
                    if($coupons){
                        $goodres[$k]['coupon'] = 1;
                    }
                }
            }
        }

        if($pagenum == 1){
            $brandres = Db::name('brand')->where('is_show',1)->field('id,brand_name')->select();

            $goodlstinfo = array('goodres'=>$goodres,'total'=>$total,'per_page'=>$perpage);
        }else{
            $goodlstinfo = array('goodres'=>$goodres,'total'=>$total,'per_page'=>$perpage);
        }
		datamsg(200,'获取商品信息成功',$goodlstinfo);
    }
    
    //商品详情
    public function goodsInfo(){
	    $tokenRes = $this->checkToken(0);
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        $pin_id = '';
        $tuan_id = '';
        $memberpinres = array();
	    if(!input('post.goods_id')){
		    datamsg(400,'请输入商品id',array('status'=>400));
	    }

        $goodsId = input('post.goods_id');
        $goods = Db::name('goods')
                   ->alias('a')
                   ->field('a.id,a.goods_name,a.thumb_url,a.shop_price,a.min_market_price,a.max_market_price,a.min_price,a.max_price,a.zs_price,a.goods_desc,a.fuwu,a.is_send_free,a.leixing,a.is_activity,a.shop_id,a.sale_num,a.hasoption,a.total')
                   ->join('sp_shops b','a.shop_id = b.id','INNER')
                   ->where('a.id',$goodsId)
                   ->where('a.onsale',1)
                   ->where('b.open_status',1)
                   ->find();
        if(!$goods){
	        datamsg(400,'商品已下架或不存在',array('status'=>400));
        }
        $goodsModel = new GoodsModel();
        $webconfig = $this->webconfig;
        $goods['thumb_url'] = url_format($goods['thumb_url'],$webconfig['weburl']);
        $goods['goods_name'] = $this->getGoodsLangName($goodsId,$this->langCode);
        $goods['goods_desc'] = $this->getGoodsLangDescription($goodsId,$this->langCode);
        $goods['goods_desc'] = str_replace("\"/uploads/","\"".$webconfig['weburl']."/uploads/",$goods['goods_desc']);
        $goods['goods_desc'] = str_replace("<img","<img style='max-width:100%;'",$goods['goods_desc']);

        if($userId){
            $colls = Db::name('coll_goods')->where('user_id',$userId)->where('goods_id',$goods['id'])->find();
            if($colls){
                $goods['coll_goods'] = 1;
            }else{
                $goods['coll_goods'] = 0;
            }
        }else{
            $goods['coll_goods'] = 0;
        }

        $goods['shop_token'] = '';
        $member_shops = Db::name('member')->where('shop_id',$goods['shop_id'])->field('id')->find();
        if($member_shops){
            $shoptoken_infos = Db::name('member_token')->where('user_id',$member_shops['id'])->field('token')->find();
            if($shoptoken_infos){
                $goods['shop_token'] = $shoptoken_infos['token'];
            }
        }

        // 实际销量
        $onetime = date('Y-m-d',time()-3600*24*30);
        $oneriqi = strtotime($onetime);
        $goods['sale_number'] = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id = b.id','INNER')->where('a.goods_id',$goods['id'])->where('b.state',1)->where('b.addtime','egt',$oneriqi)->sum('a.goods_num');

        $gpres = Db::name('goods_pic')->where('goods_id',$goods['id'])->field('id,img_url,sort')->order('sort asc')->select();
        foreach ($gpres as $kp => $vp){
            $gpres[$kp]['img_url'] = url_format($vp['img_url'],$webconfig['weburl']);
        }

        $guige = array();
        $activity = array();
        if(input('post.rush_id') && !input('post.group_id') && !input('post.assem_id')){
            //秒杀
            $rush_id = input('post.rush_id');
            $activity = Db::name('seckill')
                          ->where('id',$rush_id)
                          ->where('goods_id',$goods['id'])
                          ->where('shop_id',$goods['shop_id'])
                          ->where('checked',1)
                          ->where('recommend',1)
                          ->where('is_show',1)
                          ->where('start_time','elt',time())
                          ->where('end_time','gt',time())
                          ->field('id,goods_id,goods_attr,price,xznum,stock,sold,start_time,end_time')
                          ->find();
            if($activity){
                $activity['ac_type'] = 1;
            }
        }elseif(input('post.group_id') && !input('post.rush_id') && !input('post.assem_id')){
            //秒杀
            $rush_id = input('post.group_id');
            $activity = Db::name('integral_shop')
                ->where('id',$rush_id)
                ->where('goods_id',$goods['id'])
                ->where('shop_id',$goods['shop_id'])
                ->where('checked',1)
                ->where('recommend',1)
                ->where('is_show',1)
                ->field('id,goods_id,goods_attr,price,xznum,stock,sold,integral')
                ->find();
            if($activity){
                $activity['ac_type'] = 1;
            }
        }elseif(input('post.assem_id') && !input('post.rush_id') && !input('post.group_id')){
            //拼团
            $assem_id = input('post.assem_id');
            $activity = Db::name('assemble')
                          ->where('id',$assem_id)
                          ->where('goods_id',$goods['id'])
                          ->where('shop_id',$goods['shop_id'])
                          ->where('checked',1)
                          ->where('is_show',1)
                          ->where('start_time','elt',time())
                          ->where('end_time','gt',time())
                          ->field('id,goods_id,goods_attr,price,pin_num,start_time,end_time')
                          ->find();
            if($activity){
                $activity['ac_type'] = 3;
            }
        }

        $commonModel = new CommonModel();
        if(empty($activity)){
            $ruinfo = array('id'=>$goods['id'],'shop_id'=>$goods['shop_id']);
            $activity = $commonModel->getActivityInfo($ruinfo);
        }

        $activitySpecItemIds = array();
        if($activity && $activity['goods_attr']){
            $seckillOptionIdArr = explode(',',$activity['goods_attr']);
            foreach ($seckillOptionIdArr as $v){
                $activitySpecItemIdStr = Db::name('goods_option')->where('id',$v)->value('specs');
                $activitySpecItemIdArr = explode('_',$activitySpecItemIdStr);
                $activitySpecItemIds = array_merge($activitySpecItemIds,$activitySpecItemIdArr);
                unset($activitySpecItemIdStr);
                unset($activitySpecItemIdArr);
            }
        }

        $specs = false;
        $formatSpecList = array();
        if (!empty($goods) && $goods['hasoption']) {
            $specs = Db::name('goods_spec')->where('goods_id',$goods['id'])->order('sort ASC')->select();

            foreach ($specs as &$spec) {
                $spec['list'] = Db::name('goods_spec_item')->where(['spec_id'=>$spec['id'],'show'=>1])->order('sort ASC')->select();
                foreach ($spec['list'] as &$specItem){
                    $specItem['thumb'] = !empty($specItem['thumb']) ? url_format($specItem['thumb'],$webconfig['weburl']) : '';
                }
                unset($specItem);
            }
            unset($spec);

        }

        $formatSpecList = $specs;

        $activityInfo = array();
        $formatSkuList = array();
        if($activity){
            $goods['is_activity'] = $activity['ac_type'];
            // 秒杀
            if($activity['ac_type'] == 1){ // 1-秒杀，2-积分，3-拼团
                $priceData = $goodsModel->getGoodsShowPrice($goods['id'],'seckill');
                $goods['zs_shop_price'] = $priceData['seckill_price'];
                $goods['zs_market_price'] = $priceData['shop_price'];

                $pronum = $goodsModel->getGoodsStock($goods['id'],'seckill');

                $sales_ratio = $goodsModel->getSalesRatio($activity['id'],'seckill');
                $activityInfo = array(
                    'sales_ratio' => $sales_ratio,
                    'xznum'       => $activity['xznum'],
                    'start_time'  => $activity['start_time'],
                    'end_time'    => $activity['end_time'],
                    'dqtime'      => time()
                );

                if($activity['goods_attr']){
                    $formatSkuList = $goodsModel->getFormatSkuList($goods['id'],'seckill');
                }

            }
            // 积分
            if($activity['ac_type'] == 2){ // 1-秒杀，2-积分，3-拼团
                $priceData = $goodsModel->getGoodsShowPrice($goods['id'],'integral');
                $goods['zs_shop_price'] = $priceData['integral_price'];
                $goods['zs_market_price'] = $priceData['shop_price'];
                $goods['integral'] = $priceData['integral'];
                $pronum = $goodsModel->getGoodsStock($goods['id'],'integral');

                $sales_ratio = $goodsModel->getSalesRatio($activity['id'],'integral');
                $activityInfo = array(
                    'sales_ratio' => $sales_ratio,
                    'xznum'       => $activity['xznum'],
                    'start_time'  => $activity['start_time'],
                    'end_time'    => $activity['end_time'],
                    'dqtime'      => time()
                );

                if($activity['goods_attr']){
                    $formatSkuList = $goodsModel->getFormatSkuList($goods['id'],'integral');
                }

            }
            // 拼团
            if($activity['ac_type'] == 3){

                $priceData = $goodsModel->getGoodsShowPrice($goods['id'],'assemble');
                $goods['zs_shop_price'] = $priceData['assemble_price'];
                $goods['zs_market_price'] = $priceData['shop_price'];
                $priceData = $goodsModel->getGoodsShowPrice($goods['id'],'assemble','button');
                $pintuanButtonPrice = $priceData['assemble_price'];

                $pronum = $goodsModel->getGoodsStock($goods['id'],'assemble');

                if($activity['goods_attr']){
                    $formatSkuList = $goodsModel->getFormatSkuList($goods['id'],'assemble');
                }

                $assem_type = 1;
                $zhuangtai = 0;
                $member_assem = array();

                if(input('post.pin_number')){
                    if($userId){
                        $assem_number = input('post.pin_number');
                        $pintuans = Db::name('pintuan')
                                      ->where('assem_number',$assem_number)
                                      ->where('state',1)
                                      ->where('pin_status','in','0,1')
                                      ->find();
                        if($pintuans){
                            $pthdinfos = Db::name('assemble')
                                           ->where('id',$pintuans['hd_id'])
                                           ->where('goods_id',$goods['id'])
                                           ->where('shop_id',$goods['shop_id'])
                                           ->where('checked',1)
                                           ->where('is_show',1)
                                           ->where('start_time','elt',time())
                                           ->where('end_time','gt',time())
                                           ->field('id,goods_id,goods_attr,price,pin_num,start_time,end_time')
                                           ->find();
                            if($pthdinfos){
                                $assembleOrder = Db::name('order_assemble')
                                                     ->where('pin_id',$pintuans['id'])
                                                     ->where('user_id',$userId)
                                                     ->where('state',1)
                                                     ->where('tui_status',0)
                                                     ->find();
                                if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                    if($assembleOrder){
                                        $assem_type = 3;
                                        $zhuangtai = 1;
                                        $member_assem = Db::name('order_assemble')
                                                          ->alias('a')
                                                          ->field('a.pin_type,b.user_name,b.headimgurl')
                                                          ->join('sp_member b','a.user_id = b.id','INNER')
                                                          ->where('a.pin_id',$pintuans['id'])
                                                          ->where('a.state',1)
                                                          ->where('a.tui_status',0)
                                                          ->order('a.addtime asc')
                                                          ->select();
                                    }else{
                                        $assem_type = 2;
                                        $member_assem = Db::name('order_assemble')
                                                          ->alias('a')
                                                          ->field('a.pin_type,b.user_name,b.headimgurl')
                                                          ->join('sp_member b','a.user_id = b.id','INNER')
                                                          ->where('a.pin_id',$pintuans['id'])
                                                          ->where('a.state',1)
                                                          ->where('a.tui_status',0)
                                                          ->order('a.addtime asc')
                                                          ->select();
                                    }
                                }elseif($pintuans['pin_status'] == 1){
                                    if($assembleOrder){
                                        $zhuangtai = 2;
                                        $member_assem = Db::name('order_assemble')
                                                          ->alias('a')
                                                          ->field('a.pin_type,b.user_name,b.headimgurl')
                                                          ->join('sp_member b','a.user_id = b.id','INNER')
                                                          ->where('a.pin_id',$pintuans['id'])
                                                          ->where('a.state',1)
                                                          ->where('a.tui_status',0)
                                                          ->order('a.addtime asc')
                                                          ->select();
                                    }
                                }
                            }
                        }else{
                            $assembleOrder = Db::name('order_assemble')
                                               ->where('user_id',$userId)
                                               ->where('goods_id',$goods['id'])
                                               ->where('shop_id',$goods['shop_id'])
                                               ->where('hd_id',$activity['id'])
                                               ->where('state',1)
                                               ->where('tui_status',0)
                                               ->order('addtime desc')
                                               ->find();
                            if($assembleOrder){
                                $pintuans = Db::name('pintuan')
                                              ->where('id',$assembleOrder['pin_id'])
                                              ->where('state',1)
                                              ->where('pin_status','in','0,1')
                                              ->where('hd_id',$activity['id'])
                                              ->find();
                                if($pintuans){
                                    if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                        $assem_type = 3;
                                        $zhuangtai = 1;
                                        $member_assem = Db::name('order_assemble')
                                                          ->alias('a')
                                                          ->field('a.pin_type,b.user_name,b.headimgurl')
                                                          ->join('sp_member b','a.user_id = b.id','INNER')
                                                          ->where('a.pin_id',$pintuans['id'])
                                                          ->where('a.state',1)
                                                          ->where('a.tui_status',0)
                                                          ->order('a.addtime asc')
                                                          ->select();
                                    }elseif($pintuans['pin_status'] == 1){
                                        $zhuangtai = 2;
                                        $member_assem = Db::name('order_assemble')
                                                          ->alias('a')
                                                          ->field('a.pin_type,b.user_name,b.headimgurl')
                                                          ->join('sp_member b','a.user_id = b.id','INNER')
                                                          ->where('a.pin_id',$pintuans['id'])
                                                          ->where('a.state',1)
                                                          ->where('a.tui_status',0)
                                                          ->order('a.addtime asc')
                                                          ->select();
                                    }
                                }
                            }
                        }
                    }
                }else{
                    if($userId){
                        $assembleOrder = Db::name('order_assemble')
                                           ->where('user_id',$userId)
                                           ->where('goods_id',$goods['id'])
                                           ->where('shop_id',$goods['shop_id'])
                                           ->where('hd_id',$activity['id'])
                                           ->where('state',1)
                                           ->where('tui_status',0)
                                           ->order('addtime desc')
                                           ->find();
                        if($assembleOrder){
                            $pintuans = Db::name('pintuan')
                                          ->where('id',$assembleOrder['pin_id'])
                                          ->where('state',1)
                                          ->where('pin_status','in','0,1')
                                          ->where('hd_id',$activity['id'])
                                          ->find();
                            if($pintuans){
                                if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){ // 拼团中
                                    $assem_type = 3;
                                    $zhuangtai = 1;
                                }elseif($pintuans['pin_status'] == 1){ // 拼团完成
                                    $zhuangtai = 2;
                                }
                                $member_assem = Db::name('order_assemble')
                                                  ->alias('a')
                                                  ->field('a.pin_type,b.user_name,b.headimgurl')
                                                  ->join('sp_member b','a.user_id = b.id','INNER')
                                                  ->where('a.pin_id',$pintuans['id'])
                                                  ->where('a.state',1)
                                                  ->where('a.tui_status',0)
                                                  ->order('a.addtime asc')
                                                  ->select();
                            }
                        }
                    }
                }

                if($assem_type == 3){
                    $pin_id = $pintuans['id'];
                    $tuan_id = $assembleOrder['id'];
                }

                if(!empty($pthdinfos) && $pthdinfos['id'] != $activity['id']){
                    $ptactivitys = $pthdinfos;
                    $goods['zs_shop_price'] = $ptactivitys['price'];
                }else{
                    $ptactivitys = $activity;
                }

                $danduPriceData = $goodsModel->getGoodsShowPrice($goods['id'],'normal','button');
                $danduButtonPrice = $danduPriceData['shop_price'];

                if(in_array($assem_type,array(1,3))){
                    $userAssembleOrder = Db::name('order_assemble')
                                           ->alias('a')
                                           ->field('a.pin_id')
                                           ->join('sp_pintuan b','a.pin_id = b.id','INNER')
                                           ->where('a.user_id',$userId)
                                           ->where('a.goods_id',$goods['id'])
                                           ->where('a.shop_id',$goods['shop_id'])
                                           ->where('a.hd_id',$ptactivitys['id'])
                                           ->where('a.state',1)
                                           ->where('a.tui_status',0)
                                           ->where('b.state',1)
                                           ->where('b.hd_id',$ptactivitys['id'])
                                           ->where('b.pin_status',0)
                                           ->where('b.timeout','gt',time())
                                           ->group('a.pin_id')
                                           ->select();
                    if($userAssembleOrder){
                        $userpinid = array();
                        foreach ($userAssembleOrder as $vur){
                            $userpinid[] = $vur['pin_id'];
                        }
                        $userpinid = array_unique($userpinid);
                        $userpinid = implode(',', $userpinid);
                        $memberpinres =  Db::name('pintuan')
                                           ->alias('a')
                                           ->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')
                                           ->join('sp_member b','a.tz_id = b.id','INNER')
                                           ->where('a.id','not in',$userpinid)
                                           ->where('a.hd_id',$ptactivitys['id'])
                                           ->where('a.state',1)
                                           ->where('a.pin_status',0)
                                           ->where('a.timeout','gt',time())
                                           ->order('a.tuan_num desc')
                                           ->limit(3)
                                           ->select();
                    }else{
                        $memberpinres =  Db::name('pintuan')
                                           ->alias('a')
                                           ->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')
                                           ->join('sp_member b','a.tz_id = b.id','INNER')
                                           ->where('a.hd_id',$ptactivitys['id'])
                                           ->where('a.state',1)
                                           ->where('a.pin_status',0)
                                           ->where('a.timeout','gt',time())
                                           ->order('a.tuan_num desc')
                                           ->limit(3)
                                           ->select();
                    }

                    if($memberpinres){
                        foreach ($memberpinres as $kpc => $vpc){
                            $memberpinres[$kpc]['headimgurl'] = url_format($vpc['headimgurl'],$webconfig['weburl'],'?imageMogr2/thumbnail/200x200');
                            $memberpinres[$kpc]['pin_time_out'] = time2string($vpc['timeout']-time());
                            $memberpinres[$kpc]['goods_id'] = $goods['id'];
                        }
                    }
                }

                if($assem_type == 1 && $zhuangtai == 0){
                    if($userId){
                        $member_picinfos  = Db::name('member')->where('id',$userId)->field('user_name,headimgurl')->find();
                        $member_pic = url_format($member_picinfos['headimgurl'],$webconfig['weburl'],'?imageMogr2/thumbnail/200x200');
                        $member_assem[] = array('pin_type'=>2,'user_name'=>$member_picinfos['user_name'],'headimgurl'=>$member_pic);
                    }else{
                        $member_pic = $webconfig['weburl'].'/static/admin/img/nopic.jpg';
                        $member_assem[] = array('pin_type'=>2,'user_name'=>'','headimgurl'=>$member_pic);
                    }
                }else{
                    if(!empty($member_assem)){
                        foreach ($member_assem as $kas => $vas){
                            $member_assem[$kas]['headimgurl'] = url_format($vas['headimgurl'],$webconfig['weburl'],'?imageMogr2/thumbnail/200x200');
                        }
                    }
                }

                $activityInfo = array(
                    'assem_type'=>$assem_type,
                    'zhuangtai'=>$zhuangtai,
                    'pin_num'=>$ptactivitys['pin_num'],
                    'dandu_button_price'=>$danduButtonPrice,
                    'pintuan_button_price'=>$pintuanButtonPrice,
                    'member_assem'=>$member_assem,
                    'start_time'=>$ptactivitys['start_time'],
                    'end_time'=>$ptactivitys['end_time'],
                    'dqtime' => time()
                );
            }

        }else{
            $goods['is_activity'] = 0;
            $priceData = $goodsModel->getGoodsShowPrice($goods['id']);
            $goods['zs_shop_price'] = $priceData['shop_price'];
            $goods['zs_market_price'] = $priceData['market_price'];
            $pronum = $goods['total'];

            if($goods['hasoption']){
                $formatSkuList = $goodsModel->getFormatSkuList($goods['id']);
            }
        }

        //邮费
        if($goods['is_send_free'] == 0){
            $dispatchModel = new DispatchModel();
            $price = $dispatchModel->getGoodsDispatchPrice($goods);
            $freight = lang('运费').$price.lang('元');
        }else{
            $freight = lang('包邮');
        }

        //优惠券
        $couponinfos = array('is_show'=>0,'infos'=>'');
        //商品活动信息
        $huodong = array('is_show'=>0,'infos'=>'','prom_id'=>0);

        $couponres = Db::name('coupon')
                       ->where('shop_id',$goods['shop_id'])
                       ->where('start_time','elt',time())
                       ->where('end_time','gt',time()-3600*24)
                       ->where('onsale',1)
                       ->field('man_price,dec_price')
                       ->order('man_price asc')
                       ->limit(3)
                       ->select();
        if($couponres){
            $couponinfos = array('is_show'=>1,'infos'=>$couponres);
        }

        $promotions = Db::name('promotion')
                        ->where("find_in_set('".$goods['id']."',info_id)")
                        ->where('shop_id',$goods['shop_id'])
                        ->where('is_show',1)
                        ->where('start_time','elt',time())
                        ->where('end_time','gt',time())
                        ->field('id,start_time,end_time')
                        ->find();
        if($promotions){
            $prom_typeres = Db::name('prom_type')->where('prom_id',$promotions['id'])->select();
        }else{
            $prom_typeres = array();
        }

        $goods_promotion = '';

        if(!empty($promotions) && !empty($prom_typeres)){
            $start_time = date('Y年m月d日 H时',$promotions['start_time']);
            $end_time = date('Y年m月d日 H时',$promotions['end_time']);
            foreach ($prom_typeres as $kcp => $vcp){
                $zhekou = $vcp['discount']/10;
                if($kcp == 0){
                    $goods_promotion = lang('商品满').$vcp['man_num'].lang('件享').$zhekou.lang('折');
                }else{
                    $goods_promotion = $goods_promotion.lang('满').$vcp['man_num'].lang('件享').$zhekou.lang('折');
                }
            }
            $huodong = array('is_show'=>1,'infos'=>$goods_promotion,'prom_id'=>$promotions['id']);
        }

        //服务项
        $sertions = array('is_show'=>0,'infos'=>'');

        if(!empty($goods['fuwu'])){
            $sertionres = Db::name('sertion')->where('id','in',$goods['fuwu'])->where('is_show',1)->field('ser_name')->order('sort asc')->limit(2)->select();
            if($sertionres){
                $sertions = array('is_show'=>1,'infos'=>$sertionres);
            }
        }

        //当前商品所属店铺是否正在直播中
        $is_live = 0;
        if (Db::name('live')->where(['shop_id' => $goods['shop_id'], 'status' => 1])->find()) {
            $is_live = 1;
        }

        $goodsinfo = array(
            'id'=>$goods['id'],
            'goods_name'=>$goods['goods_name'],
            'thumb_url'=>$goods['thumb_url'],
            'goods_desc'=>$goods['goods_desc'],
            'freight'=>$freight,
            'leixing'=>$goods['leixing'],
            'shop_id'=>$goods['shop_id'],
            'zs_market_price'=>$goods['zs_market_price'],
            'zs_shop_price'=>$goods['zs_shop_price'],
            'integral'=>$goods['integral'],
            'is_activity'=>$goods['is_activity'],
            'coll_goods'=>$goods['coll_goods'],
            'sale_number'=>$goods['sale_num'],
            'shop_token'=>$goods['shop_token'],
            'is_live' => $is_live,
            'hasoption' => $goods['hasoption']
        );

        $shopinfos = Db::name('shops')
                       ->where('id',$goods['shop_id'])
                       ->where('open_status',1)
                       ->field('id,shop_name,shop_desc,logo,goods_fen,fw_fen,wuliu_fen')
                       ->find();
        $shopinfos['logo'] = url_format($shopinfos['logo'],$webconfig['weburl']);
        $shopinfos['goods_list'] = $goodsModel->getShopGoods($goods['shop_id'],4);


        $goodsParamModel = new GoodsParamModel();
        $goodsParam = $goodsParamModel->getGoodsParam($goods['id']);
        $goodinfores = array(
            'goodsinfo'=>$goodsinfo,
            'activity_info'=>$activityInfo,
            'pronum'=>$pronum,
            'gpres'=>$gpres,
            'specs'=>$specs,
            'formatSpecList' => $formatSpecList,
            'formatSkuList' => $formatSkuList,
            'goods_param'=>$goodsParam,
            'guige'=>$guige,
            'couponinfos'=>$couponinfos,
            'huodong'=>$huodong,
            'sertions'=>$sertions,
            'shopinfos'=>$shopinfos,
            'pin_id'=>$pin_id,
            'tuan_id'=>$tuan_id,
            'memberpinres'=>$memberpinres,
            'share_url'=> $webconfig['weburl'].'/h5/#/pagesC/goods/goodsDetails?id='.$goodsId
        );
	    //商品分享首次进入，存入session用于绑定分销商上下级关系
	    if (!empty(input('post.user_pid'))) {
		    session('user_pid', input('post.user_pid'));
	    }
	    datamsg(200, '获取商品详情信息成功', $goodinfores);
    }

    //根据商品属性获取商品详情
    public function getGoodsPrice(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        $pin_id = '';
        $tuan_id = '';
        $memberpinres = array();

        $data = input('post.');
        if(empty($data['goods_id']) && empty($data['goods_attr'])){
	        datamsg(400,'缺少参数',array('status'=>400));
        }
        if(!input('post.fangshi') && !input('post.fangshi') == 1){
	        datamsg(400,'缺少购买方式参数',array('status'=>400));
        }

        if(is_array($data['goods_attr'])){
	        datamsg(400,'缺少商品单选属性参数',array('status'=>400));
        }

        $goodsId = $data['goods_id'];
        $fangshi = $data['fangshi'];  // 1-普通商品、秒杀商品、拼团商品单独购买，2-拼团商品拼团购买

        $goods_attr = $data['goods_attr'] = trim($data['goods_attr']);

        if(!$data['goods_attr']){
	        datamsg(400,'商品属性参数错误');
        }

        $goods = Db::name('goods')
                   ->alias('a')
                   ->field('a.id,a.goods_name,a.shop_price,a.min_price,a.max_price,a.shop_id,a.hasoption,a.total')
                   ->join('sp_shops b','a.shop_id = b.id','INNER')
                   ->where('a.id',$goodsId)
                   ->where('a.onsale',1)
                   ->where('b.open_status',1)
                   ->find();
		if(!$goods){
			datamsg(400,'商品已下架或不存在');
		}
        $goodsSpecItemIdArr = explode('_', $data['goods_attr']);
        $goodsSpecModel = new GoodsSpecModel();
        $checkSpec = $goodsSpecModel->checkGoodsSpec($goodsId,$goodsSpecItemIdArr);
        if($checkSpec['status'] == 400){
            datamsg(400,$checkSpec['mess']);
        }

        $goods_name = $goods['goods_name'];
        $webconfig = $this->webconfig;

        $ruinfo = array('id'=>$goods['id'],'shop_id'=>$goods['shop_id']);
        $ru_attr = implode('_',$goodsSpecItemIdArr);
        $commonModel = new CommonModel();
        $activity = $commonModel->getActivityInfo($ruinfo);
        $activityInfo = array();
        $goodsModel = new GoodsModel();
        if($activity){
            $is_activity = $activity['ac_type'];
            if($activity['ac_type'] == 1){
                if($activity['goods_attr']){
                    $activityGoodsOption = Db::name('goods_option')
                               ->where('goods_id',$goods['id'])
                               ->where('specs',$goods_attr)
                               ->where('is_seckill',1)
                               ->find();
                    $zs_shop_price = $activityGoodsOption['seckill_price'];
                    $stock = $activityGoodsOption['seckill_stock'];
                }else{
                    $zs_shop_price = $activity['price'];
                    $stock = $activity['stock'];
                }

            }

            if($activity['ac_type'] == 2){
                if($activity['goods_attr']){
                    $activityGoodsOption = Db::name('goods_option')
                        ->where('goods_id',$goods['id'])
                        ->where('specs',$goods_attr)
                        ->where('is_integral',1)
                        ->find();
                    $zs_shop_price = $activityGoodsOption['integral_price'];
                    $stock = $activityGoodsOption['integral_stock'];
                    $integral = $activityGoodsOption['integral'];

                }else{
                    $zs_shop_price = $activity['price'];
                    $stock = $activity['stock'];
                    $integral = $activity['integral'];
                }

            }

            if($activity['ac_type'] == 3){
                if($fangshi == 1){ // 单独购买
                    $is_activity = 0;
                    $goodsOptionModel = new GoodsOptionModel();
                    $goodsSpecItemIdStr = implode('_',$goodsSpecItemIdArr);
                    $goodsOption = $goodsOptionModel->where(['goods_id'=>$goodsId,'specs'=>$goodsSpecItemIdStr])->find();
                    $zs_shop_price = sprintf("%.2f", $goodsOption['shop_price']);
                    $stock = $goodsOption['stock'];
                }else{ // 拼团购买
                    $assem_type = 1;
                    $zhuangtai = 0;  // 0-未参与，1-参与中，2-完成
                    $member_assem = array();

                    if(input('post.pin_number')){
                        if($userId){
                            $assem_number = input('post.pin_number');
                            $pintuans = Db::name('pintuan')
                                          ->where('assem_number',$assem_number)
                                          ->where('state',1)
                                          ->where('pin_status','in','0,1')
                                          ->where('hd_id',$activity['id'])
                                          ->find();
                            if($pintuans){
                                $assembleOrder = Db::name('order_assemble')
                                                   ->where('pin_id',$pintuans['id'])
                                                   ->where('user_id',$userId)
                                                   ->where('state',1)
                                                   ->where('tui_status',0)
                                                   ->find();
                                if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                    if($assembleOrder){
                                        $assem_type = 3;
                                        $zhuangtai = 1;
                                        $member_assem = Db::name('order_assemble')
                                                          ->alias('a')
                                                          ->field('a.pin_type,b.user_name,b.headimgurl')
                                                          ->join('sp_member b','a.user_id = b.id','INNER')
                                                          ->where('a.pin_id',$pintuans['id'])
                                                          ->where('a.state',1)
                                                          ->where('a.tui_status',0)
                                                          ->order('a.addtime asc')
                                                          ->select();
                                    }else{
                                        $assem_type = 2;
                                        $member_assem = Db::name('order_assemble')
                                                          ->alias('a')
                                                          ->field('a.pin_type,b.user_name,b.headimgurl')
                                                          ->join('sp_member b','a.user_id = b.id','INNER')
                                                          ->where('a.pin_id',$pintuans['id'])
                                                          ->where('a.state',1)
                                                          ->where('a.tui_status',0)
                                                          ->order('a.addtime asc')
                                                          ->select();
                                    }
                                }elseif($pintuans['pin_status'] == 1){
                                    if($assembleOrder){
                                        $zhuangtai = 2;
                                        $member_assem = Db::name('order_assemble')
                                                          ->alias('a')
                                                          ->field('a.pin_type,b.user_name,b.headimgurl')
                                                          ->join('sp_member b','a.user_id = b.id','INNER')
                                                          ->where('a.pin_id',$pintuans['id'])
                                                          ->where('a.state',1)
                                                          ->where('a.tui_status',0)
                                                          ->order('a.addtime asc')
                                                          ->select();
                                    }
                                }
                            }else{
                                $assembleOrder = Db::name('order_assemble')
                                                   ->where('user_id',$userId)
                                                   ->where('goods_id',$goods['id'])
                                                   ->where('shop_id',$goods['shop_id'])
                                                   ->where('hd_id',$activity['id'])
                                                   ->where('state',1)
                                                   ->where('tui_status',0)
                                                   ->order('addtime desc')
                                                   ->find();

                                if($assembleOrder){
                                    $pintuans = Db::name('pintuan')
                                                  ->where('id',$assembleOrder['pin_id'])
                                                  ->where('state',1)
                                                  ->where('pin_status','in','0,1')
                                                  ->where('hd_id',$activity['id'])
                                                  ->find();
                                    if($pintuans){
                                        if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                            $assem_type = 3;
                                            $zhuangtai = 1;
                                            $member_assem = Db::name('order_assemble')
                                                              ->alias('a')
                                                              ->field('a.pin_type,b.user_name,b.headimgurl')
                                                              ->join('sp_member b','a.user_id = b.id','INNER')
                                                              ->where('a.pin_id',$pintuans['id'])
                                                              ->where('a.state',1)
                                                              ->where('a.tui_status',0)
                                                              ->order('a.addtime asc')
                                                              ->select();
                                        }elseif($pintuans['pin_status'] == 1){
                                            $zhuangtai = 2;
                                            $member_assem = Db::name('order_assemble')
                                                              ->alias('a')
                                                              ->field('a.pin_type,b.user_name,b.headimgurl')
                                                              ->join('sp_member b','a.user_id = b.id','INNER')
                                                              ->where('a.pin_id',$pintuans['id'])
                                                              ->where('a.state',1)
                                                              ->where('a.tui_status',0)
                                                              ->order('a.addtime asc')
                                                              ->select();
                                        }
                                    }
                                }
                            }
                        }
                    }else{
                        if($userId){
                            $assembleOrder = Db::name('order_assemble')
                                               ->where('user_id',$userId)
                                               ->where('goods_id',$goods['id'])
                                               ->where('shop_id',$goods['shop_id'])
                                               ->where('hd_id',$activity['id'])
                                               ->where('state',1)
                                               ->where('tui_status',0)
                                               ->order('addtime desc')
                                               ->find();
                            if($assembleOrder){
                                $pintuans = Db::name('pintuan')
                                              ->where('id',$assembleOrder['pin_id'])
                                              ->where('state',1)
                                              ->where('pin_status','in','0,1')
                                              ->where('hd_id',$activity['id'])
                                              ->find();
                                if($pintuans){
                                    if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
                                        $assem_type = 3;
                                        $zhuangtai = 1;
                                    }elseif($pintuans['pin_status'] == 1){
                                        $zhuangtai = 2;
                                    }
                                    $member_assem = Db::name('order_assemble')
                                                      ->alias('a')
                                                      ->field('a.pin_type,b.user_name,b.headimgurl')
                                                      ->join('sp_member b','a.user_id = b.id','INNER')
                                                      ->where('a.pin_id',$pintuans['id'])
                                                      ->where('a.state',1)
                                                      ->where('a.tui_status',0)
                                                      ->order('a.addtime asc')
                                                      ->select();
                                }
                            }
                        }
                    }

                    if($assem_type == 3){
                        $pin_id = $pintuans['id'];
                        $tuan_id = $assembleOrder['id'];
                    }


                    if($assem_type == 1 && $zhuangtai == 0){
                        if($userId){
                            $member_pic = Db::name('member')->where('id',$userId)->value('headimgurl');
                            $member_pic = url_format($member_pic,$webconfig['weburl'],'?imageMogr2/thumbnail/200x200');
                            $member_assem[] = array('pin_type'=>2,'headimgurl'=>$member_pic);
                        }else{
                            $member_pic = $webconfig['weburl'].'/static/admin/img/nopic.jpg';
                            $member_assem[] = array('pin_type'=>2,'user_name'=>'','headimgurl'=>$member_pic);
                        }
                    }else{
                        if(!empty($member_assem)){
                            foreach ($member_assem as $kas => $vas){
                                $member_assem[$kas]['headimgurl'] = url_format($vas['headimgurl'],$webconfig['weburl'],'?imageMogr2/thumbnail/200x200');
                            }
                        }
                    }

                    if(in_array($assem_type,array(1,3))){
                        $userAssembleOrder = Db::name('order_assemble')
                                               ->alias('a')
                                               ->field('a.pin_id')
                                               ->join('sp_pintuan b','a.pin_id = b.id','INNER')
                                               ->where('a.user_id',$userId)
                                               ->where('a.goods_id',$goods['id'])
                                               ->where('a.shop_id',$goods['shop_id'])
                                               ->where('a.hd_id',$activity['id'])
                                               ->where('a.state',1)
                                               ->where('a.tui_status',0)
                                               ->where('b.state',1)
                                               ->where('b.hd_id',$activity['id'])
                                               ->where('b.pin_status',0)
                                               ->where('b.timeout','gt',time())
                                               ->group('a.pin_id')
                                               ->select();
                        if($userAssembleOrder){
                            $userpinid = array();
                            foreach ($userAssembleOrder as $vur){
                                $userpinid[] = $vur['pin_id'];
                            }
                            $userpinid = array_unique($userpinid);
                            $userpinid = implode(',', $userpinid);
                            $memberpinres =  Db::name('pintuan')
                                               ->alias('a')
                                               ->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')
                                               ->join('sp_member b','a.tz_id = b.id','INNER')
                                               ->where('a.id','not in',$userpinid)
                                               ->where('a.hd_id',$activity['id'])
                                               ->where('a.state',1)
                                               ->where('a.pin_status',0)
                                               ->where('a.timeout','gt',time())
                                               ->order('a.tuan_num desc')
                                               ->limit(3)
                                               ->select();
                        }else{
                            $memberpinres =  Db::name('pintuan')
                                               ->alias('a')
                                               ->field('a.id,a.assem_number as pin_number,a.pin_num,a.tuan_num,a.time,a.timeout,b.user_name,b.headimgurl')
                                               ->join('sp_member b','a.tz_id = b.id','INNER')
                                               ->where('a.hd_id',$activity['id'])
                                               ->where('a.state',1)
                                               ->where('a.pin_status',0)
                                               ->where('a.timeout','gt',time())
                                               ->order('a.tuan_num desc')
                                               ->limit(3)
                                               ->select();
                        }

                        if($memberpinres){
                            foreach ($memberpinres as $kpc => $vpc){
                                $memberpinres[$kpc]['headimgurl'] = url_format($vpc['headimgurl'],$webconfig['weburl'],'?imageMogr2/thumbnail/200x200');
                                $memberpinres[$kpc]['pin_time_out'] = time2string($vpc['timeout']-time());
                                $memberpinres[$kpc]['goods_id'] = $goods['id'];
                            }
                        }
                    }

                    if($activity['goods_attr']){
                        $goodsSpecItemIdStr = implode('_',$goodsSpecItemIdArr);
                        $zs_shop_price = $goodsModel->getGoodsOptionPrice($goods['id'],$goodsSpecItemIdStr,'assemble');
                        $stock = $goodsModel->getGoodsOptionStock($goods['id'],$goodsSpecItemIdStr,'assemble');
                    }else{
                        $zs_shop_price = $activity['price'];
                        $stock = $activity['stock'];
                    }

                    $danduPriceData = $goodsModel->getGoodsShowPrice($goods['id'],'normal','button');
                    $danduButtonPrice = $danduPriceData['shop_price'];

                    $priceData = $goodsModel->getGoodsShowPrice($goods['id'],'assemble','button');
                    $pintuanButtonPrice = $priceData['assemble_price'];

                    $activityInfo = array(
                        'assem_type'=>$assem_type,
                        'zhuangtai'=>$zhuangtai,
                        'pin_num'=>$activity['pin_num'],
                        'dandu_button_price'=>$danduButtonPrice,
                        'pintuan_button_price'=>$pintuanButtonPrice,
                        'member_assem'=>$member_assem,
                        'start_time'=>$activity['start_time'],
                        'end_time'=>$activity['end_time'],
                        'dqtime' => time()
                    );
                }

            }

        }else{
            $is_activity = 0;
            $goodsOptionModel = new GoodsOptionModel();
            $goodsSpecItemIdStr = implode('_',$goodsSpecItemIdArr);
            $goodsOption = $goodsOptionModel->where(['goods_id'=>$goodsId,'specs'=>$goodsSpecItemIdStr])->find();
            $zs_shop_price = sprintf("%.2f", $goodsOption['shop_price']);
            $stock = $goodsOption['stock'];
        }

        $attrinfos = array(
            'is_activity'=>$is_activity,
            'goods_name'=>$goods_name,
            'attr_pic'=>'',
            'zs_shop_price'=>$zs_shop_price,
            'stock'=>$stock,
            'integral'=>$integral
        );

        $goodsinfo = array(
            'attrinfos'=>$attrinfos,
            'activity_info'=>$activityInfo,
            'fangshi'=>$fangshi,
            'pin_id'=>$pin_id,
            'tuan_id'=>$tuan_id,
            'memberpinres'=>$memberpinres
        );

        datamsg(200,'获取信息成功',$goodsinfo);
    }

    /**
     * @description 获取商品规格库存
     * @param int $goodsId 商品ID
     * @param string $type 商品类型：seckill-秒杀，assemble-拼团，normal-普通商品
     */
    public function getGoodsSkuList(){
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }
        $goodsId = input('post.goods_id');
        $type = input('post.type');
        if(empty($goodsId) || empty($type)){
            datamsg(400,'缺少商品参数');
        }
        $goodsModel = new GoodsModel();
        $skuList = $goodsModel->getFormatSkuList($goodsId,$type);
        datamsg(200,'获取商品规格库存',$skuList);
    }


    // 获取指定店铺的商品 
    public function getShopGoods(){
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }

        $shop_id = input('post.shop_id');
        if(empty($shop_id)){
            datamsg(400,'缺少店铺id参数');
        }else{
            $goods = db('goods')->where(['onsale'=>1,'shop_id'=>$shop_id])->select();
            datamsg(200, '获取成功',$goods);
        }
    }
    
}