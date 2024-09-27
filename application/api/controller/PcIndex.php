<?php

namespace app\api\controller;

use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use app\api\model\Goods as GoodsModel;
use app\api\model\Seckill;
use think\Db;

class PcIndex extends Common
{


    // 获取首页橱窗信息
    public function getIndexShowcase()
    {
        // 验证api_token
//	    $tokenRes = $this->checkToken(0);
//	    if($tokenRes['status'] == 400){ // 400返回错误描述
//		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
//	    }

        $goodsModel = new GoodsModel();
        $webconfig = $this->webconfig;

        // 秒杀
        $data['seckill']['title'] = '限时秒杀';
        $data['seckill']['slogan'] = '享惊喜折扣';
        $data['seckill']['tag'] = '秒杀Tag';
        $data['seckill']['tag_bg_color'] = '#ff4444';
        $data['seckill']['list'] = Seckill::getRecommendSeckill(4);
        foreach ($data['seckill']['list'] as $k => $v) {
            $data['seckill']['list'][$k]['thumb_url'] = url_format($v['thumb_url'], $webconfig['weburl']);
            $sekillPrice = $goodsModel->getGoodsShowPrice($v['goods_id'], 'seckill', 'list');
            $data['seckill']['list'][$k]['price'] = $sekillPrice['seckill_price'];
            $data['seckill']['list'][$k]['shop_price'] = $sekillPrice['shop_price'];
        }


        // 新品首发
        $data['new_goods']['title'] = '新品首发';
        $data['new_goods']['slogan'] = '美好新生活';
        $data['new_goods']['tag'] = '新品Tag';
        $data['new_goods']['tag_bg_color'] = '#ff703a';
        $data['new_goods']['list'] = $goodsModel->getTagGoods('is_new', 3);
        foreach ($data['new_goods']['list'] as $k => $v) {
            $data['new_goods']['list'][$k]['thumb_url'] = url_format($v['thumb_url'], $webconfig['weburl']);
        }

        datamsg(200, '获取成功', set_lang($data));
    }


    public function couponList()
    {
        $needUserToken = input('post.token') ? 1 : 0;
        $tokenRes = $this->checkToken($needUserToken);
        if($tokenRes['status'] != 400){
            $userId = $tokenRes['user_id'];
        }else{
            $userId = 0;
        }

        $couponres = Db::name('coupon')
            ->alias('a')
            ->join('shops b','a.shop_id = b.id')
            ->where('b.open_status',1)
            ->where('start_time','elt',time())
            ->where('end_time','gt',time()-3600*24)
            ->where('a.onsale',1)
            ->field('a.id,a.man_price,a.dec_price,a.start_time,a.end_time,a.shop_id')
            ->order('a.man_price asc')
            ->limit(3)
            ->select();

        foreach($couponres as $key=>$value){
            $couponres[$key]['shop_name'] = Db::name('shops')->where('id',$value['shop_id'])->value('shop_name');
            $couponres[$key]['start_time'] = date('Y-m-d',$value['start_time']);
            $couponres[$key]['end_time'] = date('Y-m-d',$value['end_time']);

            $shopGoodsIds = Db::name('goods')->where("shop_id",$value['shop_id'])->where('is_recycle',0)->where('onsale',1)->column("id");
            $id = $shopGoodsIds ? array_rand($shopGoodsIds) : '-1';
            $couponres[$key]['thumb_url'] = Db::name('goods')->where("id",$id)->value('thumb_url');
//            $couponres[$key]['thumb_url'] = Db::name('goods')->where('is_recycle',0)->where('onsale',1)->order('id DESC')->value('thumb_url');
            $couponres[$key]['thumb_url'] = url_format($couponres[$key]['thumb_url'], $this->webconfig['weburl']);
            if(!empty($userId)){
                $member_coupons = Db::name('member_coupon')->where('user_id',$userId)->where('coupon_id',$value['id'])->where('is_sy',0)->where('shop_id',$value['shop_id'])->find();
                if($member_coupons){
                    $couponres[$key]['have'] = 1;
                }else{
                    $couponres[$key]['have'] = 0;
                }
            }else{
                $couponres[$key]['have'] = 0;
            }
        }
        datamsg(200, '获取优惠券信息成功', set_lang($couponres));
    }

    public function goodsHome()
    {
        $tags = ['is_recommend', 'is_hot', 'is_new', 'is_special'];
        $where = "is_recycle = 0 AND onsale = 1";
        $tag = input('tag', '');
        if (in_array($tag, $tags)) {
            $where .= " and {$tag} = 1";
        }
        $num = input('num', 3);

        $goods = Db::name('goods')->whereRaw($where)->field('id,goods_name,thumb_url,zs_price')->order('id asc')->limit($num)->select();

        $webconfig = $this->webconfig;
        foreach ($goods as $key => $row) {
            $goods[$key]['thumb_url'] = url_format($row['thumb_url'], $webconfig['weburl']);
        }
        datamsg(200, '商品列表', set_lang($goods));

    }

    //分类列表
    public function categoryList()
    {
        cookie('think_var', 'en-us');
//        $tokenRes = $this->checkToken(0);
//        if ($tokenRes['status'] == 400) {
//            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
//        }
        $list = db('category')->where(['pid' => 0, 'is_show' => 1])->field('id,cate_name')->order('sort DESC')->select();
        $webconfig = $this->webconfig;

        foreach ($list as $keys => $value) {
            $cate_id = $value['id'];
            $child_cate = Db::name('category')->where('pid', $cate_id)->where('is_show', 1)->field('id,cate_name,cate_pic')->order('sort asc')->select();

            if ($child_cate) {
                foreach ($child_cate as $key => $val) {
                    $child_cate[$key]['cate_pic'] = url_format($val['cate_pic'], $webconfig['weburl'], '?imageMogr2/thumbnail/80x');
                    $child_cate[$key]['three'] = Db::name('category')->where('pid', $val['id'])->where('is_show', 1)->field('id,cate_name,cate_pic')->order('sort asc')->select();
                    if (!$child_cate[$key]['three']) {
                        $child_cate[$key]['three'][] = $val;
                    }
                    foreach ($child_cate[$key]['three'] as $key2 => $val2) {
                        $child_cate[$key]['three'][$key2]['cate_pic'] = url_format($val2['cate_pic'], $webconfig['weburl'], '?imageMogr2/thumbnail/80x');
                    }
                }
            } else {
                $child_cate = [];
            }
            $list[$keys]['three'] = set_lang($child_cate);
        }
        datamsg(200, '首页顶部导航列表', set_lang($list));
    }

    public function tagsInfo()
    {
        $result['tagName'] = '品牌';
        $result['type'] = 'brand';
        $brandList = Db::name('brand')->column('id, brand_name as name')->where('is_show', 1)->select();
        $result['list'] = $brandList;

        datamsg(200, '首页顶部导航列表', set_lang($result));

    }

    public function searchGoods()
    {
        if (!mb_strlen(input('post.keyword_name'), 'UTF8') > 50) {
            datamsg(400, '搜索内容最多50个字符', array('status' => 400));
        }
        if (!input('post.page') && !preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))) {
            datamsg(400, '缺少页面参数', array('status' => 400));
        }
        $keyword_name = input('post.keyword_name');
        $pagenum = input('post.page');

        $where1 = '';

        $webconfig = $this->webconfig;
        $perpage = $webconfig['app_goodlst_num'];
        $offset = ($pagenum - 1) * $perpage;

        // 根据商品分类中的关键词关联搜索
        $cates = Db::name('category')->where('is_show', 1)->where("find_in_set('" . $keyword_name . "',search_keywords)")->field('id')->find();
        $cate_id = 0;
        if ($cates) {
            $cate_id = $cates['id'];
            $categoryres = Db::name('category')->where('is_show', 1)->field('id,pid')->order('sort asc')->select();

            $cateIds = array();
            $cateIds = get_all_child($categoryres, $cate_id);


            $cateIds[] = $cate_id;
            $cateIds = implode(',', $cateIds);

            $where1 = "a.cate_id in (" . $cateIds . ")";
        }

        // 品牌名称关联
        $brands = Db::name('brand')->where('is_show', 1)->where('brand_name', $keyword_name)->field('id')->find();
        if ($brands) {
            $where1 = "a.brand_id = " . $brands['id'] . " OR " . $where1;
        }

        if (empty($where1)) {
            $where1 = "find_in_set('" . $keyword_name . "',a.search_keywords) OR a.goods_name like '%" . $keyword_name . "%'";
        } else {
            $where1 = $where1 . " OR find_in_set('" . $keyword_name . "',a.search_keywords) OR a.goods_name like '%" . $keyword_name . "%'";
        }


        $where2 = "a.onsale = 1";
        $where3 = '';
        $where4 = '';
        $where5 = '';
        $where6 = '';


        if (input('post.low_price') && input('post.height_price')) {
            $low_price = input('post.low_price');
            $height_price = input('post.height_price');

            if (!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $low_price)) {
                datamsg(400, '最低价格格式错误', array('status' => 400));
            }

            if (!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $height_price)) {
                datamsg(400, '最高价格格式错误', array('status' => 400));
            }

            if ($low_price >= $height_price) {
                datamsg(400, '最低价格需小于最大价格', array('status' => 400));
            }

            $where4 = "a.zs_price >= '" . $low_price . "' AND a.zs_price <= '" . $height_price . "'";
        } elseif (input('post.low_price') && !input('post.height_price')) {
            $low_price = input('post.low_price');

            if (!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $low_price)) {
                datamsg(400, '最低价格格式错误', array('status' => 400));
            }

            $where4 = "a.zs_price >= '" . $low_price . "'";
        } elseif (!input('post.low_price') && input('post.height_price')) {
            $height_price = input('post.height_price');

            if (!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $height_price)) {
                datamsg(400, '最高价格格式错误', array('status' => 400));
            }

            $where4 = "a.zs_price <= '" . $height_price . "'";
        }

        if (!empty($cates)) {
            if (input('post.brand_id') && input('post.brand_id') != 'all') {
                $brand_id = input('post.brand_id');
                $where5 = "a.brand_id = " . $brand_id . "";
            }

            if (input('post.goods_attr')) {
                $goods_attr = input('post.goods_attr');
                $goods_attr = trim($goods_attr);
                $goods_attr = str_replace('，', ',', $goods_attr);
                $goods_attr = rtrim($goods_attr, ',');
                $goods_attr = explode(',', $goods_attr);

                if (!$goods_attr || !is_array($goods_attr)) {
                    datamsg(400, '商品属性筛选条件参数错误', array('status' => 400));
                }

                foreach ($goods_attr as $kca => $va) {
                    if (!empty($va)) {
                        if ($kca == 0) {
                            $where6 = "find_in_set('" . $va . "',a.shuxings)";
                        } else {
                            $where6 = $where6 . " AND find_in_set('" . $va . "',a.shuxings)";
                        }
                    } else {
                        datamsg(400, '商品属性筛选条件参数错误', array('status' => 400));
                    }
                }
            }
        }

        if (input('post.sort')) {
            $sort = input('post.sort');
            switch ($sort) {
                case 'zonghe':
                    $sortarr = array('a.leixing' => 'desc', 'a.zonghe_lv' => 'desc', 'a.id' => 'desc');
                    break;
                case 'deal_num':
                    $sortarr = array('a.deal_num ' => 'desc', 'a.id' => 'desc');
                    break;
                case 'low_height':
                    $sortarr = array('a.zs_price' => 'asc', 'a.id' => 'desc');
                    break;
                case 'height_low':
                    $sortarr = array('a.zs_price' => 'desc', 'a.id' => 'desc');
                    break;
                default:
                    $sortarr = array('a.leixing' => 'desc', 'a.zonghe_lv' => 'desc', 'a.id' => 'desc');
            }
        } else {
            $sortarr = array('a.leixing' => 'desc', 'a.zonghe_lv' => 'desc', 'a.id' => 'desc');
        }

        $where7 = '';
        if (input('post.cate_id')) {
            $where7 = 'a.cate_id = ' . input('post.cate_id');
        }
        $where8 = '';
        if (input('post.brand_id')) {
            $where8 = 'a.brand_id = ' . input('post.brand_id');;
        }
        $goodres = Db::name('goods')->alias('a')
            ->field('a.id,a.goods_name,a.thumb_url,a.min_price,a.zs_price,a.leixing,a.shop_id,a.is_live')
            ->join('sp_shops b', 'a.shop_id = b.id', 'INNER')
            ->where($where1)
            ->where($where2)
            ->where($where3)
            ->where($where4)
            ->where($where5)
            ->where($where6)
            ->where($where7)
            ->where($where8)
            ->where("b.open_status = 1")
            ->order($sortarr)
            ->limit($offset, $perpage)
            ->select();

        $webconfig = $this->webconfig;

        if ($goodres) {
            foreach ($goodres as $k => $v) {
                $goodres[$k]['goods_name'] = $this->getGoodsLangName($v['id'], $this->langCode);
                $goodres[$k]['thumb_url'] = url_format($v['thumb_url'], $webconfig['weburl'], '?imageMogr2/thumbnail/350x350');
                $goodres[$k]['coupon'] = 0;

                $ruinfo = array('id' => $v['id'], 'shop_id' => $v['shop_id']);
                $commonModel = new CommonModel();
                $activity = $commonModel->getActivityInfo($ruinfo);

                if ($activity) {
                    $goodsModel = new GoodsModel();
                    $goodres[$k]['is_activity'] = $activity['ac_type'];
                    if ($activity['ac_type'] == 1) {
                        $seckillPrice = $goodsModel->getGoodsShowPrice($v['id'], 'seckill', 'list');
                        $goodres[$k]['zs_price'] = $seckillPrice['seckill_price'];
                    }
                    if ($activity['ac_type'] == 2) {
                        $integralPrice = $goodsModel->getGoodsShowPrice($v['id'], 'integral', 'list');
                        $goodres[$k]['zs_price'] = $integralPrice['integral_price'];
                        $goodres[$k]['integral'] = $integralPrice['integral'];
                    }
                    if ($activity['ac_type'] == 3) {
                        $assemblePrice = $goodsModel->getGoodsShowPrice($v['id'], 'assemble', 'list');
                        $goodres[$k]['zs_price'] = $assemblePrice['assemble_price'];
                    }
                    unset($seckillPrice);
                    unset($integralPrice);
                    unset($assemblePrice);
                } else {
                    $goodres[$k]['is_activity'] = 0;
                    $goodres[$k]['zs_price'] = $v['min_price'];
                }

                if (!$activity || in_array($activity['ac_type'], array(1, 2))) {
                    //优惠券
                    $coupons = Db::name('coupon')->where('shop_id', $v['shop_id'])->where('start_time', 'elt', time())->where('end_time', 'gt', time() - 3600 * 24)->where('onsale', 1)->find();
                    if ($coupons) {
                        $goodres[$k]['coupon'] = 1;
                    }
                }
            }
        }
        $brandData = [];
        if ($cates) {
            $cates = Db::name('category')->where('is_show', 1)->where("pid", '=', $cates['id'])->field('cate_name as name,id')->select();
            if ($cates) {
                $brand = Db::name('brand')->where('is_show', 1)->field('brand_name as name,id,cate_id_list')->select();
                $brandDataTemp = [];
                foreach ($brand as $row) {
                    $cate_id_list = explode(',', $row['cate_id_list']);
                    foreach ($cates as $cRow) {
                        if (in_array($cRow['id'], $cate_id_list)) {
                            $brandDataTemp[$row['id']] = $row;
                        }
                    }
                }
                foreach ($brandDataTemp as $row) {
                    $brandData[] = $row;
                }
            }
        } else {
            $cates = [];
        }
        $tag['tagName'] = '分类';
        $tag['type'] = 'cate_id';
        $tag['list'] = $cates;
        $tags[] = $tag;

        $tag['tagName'] = '品牌';
        $tag['type'] = 'brand_id';
        $tag['list'] = $brandData;
        $tags[] = $tag;


//        $brands = Db::name('brand')->where('is_show', 1)->field('cate_name as name,id')->field('cate_name as name,id')->find();

        if ($pagenum == 1) {
            $goodlstinfo = array('tags' => $tags, 'goodres' => $goodres);
        } else {
            $goodlstinfo = array('tags' => $tags, 'goodres' => $goodres);
        }
        datamsg(200, '获取商品信息成功', $goodlstinfo);

    }

    //首页商品信息
    public function getGoodsList(){
        // 验证api_token
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){ // 400返回错误描述
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }
        if(!input('post.page') && !preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
            datamsg(400,'缺少页面参数',array('status'=>400));
        }

        $goodsModel = new GoodsModel();
        $pagenum = input('post.page');

        $webconfig = $this->webconfig;
        $perpage = 50;
        $offset = ($pagenum-1)*$perpage;

        $goodres = Db::name('goods')
                     ->alias('a')
                     ->field('a.id,a.goods_name,a.thumb_url,a.min_price,a.zs_price,a.leixing,a.shop_id,a.is_live')
                     ->join('sp_shops b','a.shop_id = b.id','INNER')
                     ->where('a.is_recycle',0)
                     ->where('a.onsale',1)
                     ->where('b.open_status',1)
                     ->order(array('a.zonghe_lv'=>'desc','a.id'=>'desc'))
                     ->limit($offset,$perpage)->select();

        if($goodres){
            foreach ($goodres as $k =>$v){
                $goodres[$k]['goods_name'] = $this->getGoodsLangName($v['id'],$this->langCode);
                $goodres[$k]['thumb_url'] = url_format($v['thumb_url'],$webconfig['weburl']);
                $goodres[$k]['coupon'] = 0;

                $ruinfo = array('id'=>$v['id'],'shop_id'=>$v['shop_id']);
                $commonModel = new CommonModel();
                $activity = $commonModel->getActivityInfo($ruinfo);

                if($activity){
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
        datamsg(200,'获取商品信息成功',$goodres);
    }


    /**
     * 获取网站配置信息
     */
    public function pcWebConfig(){
        datamsg(200,'获取成功',$this->webconfig);
    }


    /**
     * 订单轮询
     */
    public function searchGetOrder(){
        $params = input("post.");
        $order_zong = Db("order_zong")->where("order_number",$params['order_number'])->find();
        if($order_zong && $order_zong['state'] == 1){
            datamsg(200,'支付成功');
        }
        datamsg(400,'等待支付中...');
    }
}
