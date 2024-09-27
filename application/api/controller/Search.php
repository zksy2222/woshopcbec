<?php
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use app\api\model\Goods as GoodsModel;
use think\Db;

class Search extends Common{

    //搜索分类和商品
    public function searchGoods(){
//        $tokenRes = $this->checkToken(0);
//        if($tokenRes['status'] == 400){
//            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
//        }

        if(!input('post.keyword_name')){
            datamsg(400,'搜索内容不能为空',array('status'=>400));
        }

        if(mb_strlen(input('post.keyword_name'),'UTF8') <= 50){

            if(!input('post.page') && !preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                datamsg(400,'缺少页面参数',array('status'=>400));
            }
            $keyword_name = input('post.keyword_name');
            $pagenum = input('post.page');

            $where1 = '';

            $webconfig = $this->webconfig;
            $perpage = $webconfig['app_goodlst_num'];
            $offset = ($pagenum-1)*$perpage;

            // 根据商品分类中的关键词关联搜索
            $cates = Db::name('category')->where('is_show',1)->where("find_in_set('".$keyword_name."',search_keywords)")->field('id')->find();
            if($cates){
                $cate_id = $cates['id'];
                $categoryres = Db::name('category')->where('is_show',1)->field('id,pid')->order('sort asc')->select();
                $cateIds = array();
                $cateIds = get_all_child($categoryres, $cate_id);
                $cateIds[] = $cate_id;
                $cateIds = implode(',', $cateIds);

                $where1 = "a.cate_id in (".$cateIds.")";
            }

            // 品牌名称关联
            $brands = Db::name('brand')->where('is_show',1)->where('brand_name',$keyword_name)->field('id')->find();
            if($brands){
                $where1 = "a.brand_id = ".$brands['id']." OR ".$where1 ;
            }

            if(empty($where1)){
                $where1 = "find_in_set('".$keyword_name."',a.search_keywords) OR a.goods_name like '%".$keyword_name."%'";
            }else{
                $where1 = $where1." OR find_in_set('".$keyword_name."',a.search_keywords) OR a.goods_name like '%".$keyword_name."%'";
            }




            $where2 = "a.onsale = 1";
            $where3 = '';
            $where4 = '';
            $where5 = '';
            $where6 = '';

            if(input('post.goods_type') && input('post.goods_type') != 'all'){
                $goods_type = input('post.goods_type');
                switch($goods_type){
                    case 1:
                        $where3 = "a.leixing = 1"; // leixing:1自营，2商家
                        break;
                    case 2:
                        $where3 = "a.is_activity = 1";
                        break;
                }
            }

            if(input('post.low_price') && input('post.height_price')){
                $low_price = input('post.low_price');
                $height_price = input('post.height_price');

                if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $low_price)){
                    datamsg(400,'最低价格格式错误',array('status'=>400));
                }

                if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $height_price)){
                    datamsg(400,'最高价格格式错误',array('status'=>400));
                }

                if($low_price >= $height_price){
                    datamsg(400,'最低价格需小于最大价格',array('status'=>400));
                }

                $where4 = "a.zs_price >= '".$low_price."' AND a.zs_price <= '".$height_price."'";
            }elseif(input('post.low_price') && !input('post.height_price')){
                $low_price = input('post.low_price');

                if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $low_price)){
                    datamsg(400,'最低价格格式错误',array('status'=>400));
                }

                $where4 = "a.zs_price >= '".$low_price."'";
            }elseif(!input('post.low_price') && input('post.height_price')){
                $height_price = input('post.height_price');

                if(!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $height_price)){
                    datamsg(400,'最高价格格式错误',array('status'=>400));
                }

                $where4 = "a.zs_price <= '".$height_price."'";
            }

            if(!empty($cates)){
                if(input('post.brand_id') && input('post.brand_id') != 'all'){
                    $brand_id = input('post.brand_id');
                    $where5 = "a.brand_id = ".$brand_id."";
                }

                if(input('post.goods_attr')){
                    $goods_attr = input('post.goods_attr');
                    $goods_attr = trim($goods_attr);
                    $goods_attr = str_replace('，', ',', $goods_attr);
                    $goods_attr = rtrim($goods_attr,',');
                    $goods_attr = explode(',', $goods_attr);

                    if(!$goods_attr || !is_array($goods_attr)){
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

            $goodres = Db::name('goods')->alias('a')
                         ->field('a.id,a.goods_name,a.thumb_url,a.min_price,a.zs_price,a.leixing,a.shop_id,a.is_live,a.sale_num+a.sale_virtual as sale_num')
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

            $webconfig = $this->webconfig;

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
                            $integralPrice = $goodsModel->getGoodsShowPrice($v['id'],'integral','list');
                            $goodres[$k]['zs_price'] = $integralPrice['integral_price'];
                            $goodres[$k]['integral'] = $integralPrice['integral'];
                        }
                        if($activity['ac_type'] == 3){
                            $assemblePrice = $goodsModel->getGoodsShowPrice($v['id'],'assemble','list');
                            $goodres[$k]['zs_price'] = $assemblePrice['assemble_price'];
                        }
                        unset($seckillPrice);
                        unset($integralPrice);
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

            if(!empty($cates)){
                if($pagenum == 1){
                    $brandres = Db::name('brand')->where('find_in_set('.$cate_id.',cate_id_list)')->where('is_show',1)->field('id,brand_name')->select();

                    $keyname = $keyword_name;
                }
            }elseif(!empty($brands)){
                if($pagenum == 1){
                    $brandres = array();
                    $shaixuan = array();
                    $keyname = $keyword_name;
                }
            }else{
                if($pagenum == 1){
                    $brandres = array();
                    $shaixuan = array();
                    $keyname = $keyword_name;
                }
            }

            if($pagenum == 1){
                $goodlstinfo = array('keyword_name'=>$keyname,'goodres'=>$goodres,'brandres'=>$brandres,'shaixuan'=>$shaixuan);
            }else{
                $goodlstinfo = array('goodres'=>$goodres);
            }
            datamsg(200,'获取商品信息成功',$goodlstinfo);
        }else{
            datamsg(400,'搜索内容最多50个字符',array('status'=>400));
        }
    }


    //搜索商家
    public function searchshops(){
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }
        if(!input('post.keyword_name')){
            datamsg(400,'搜索内容不能为空',array('status'=>400));
        }

        if(mb_strlen(input('post.keyword_name'),'UTF8') <= 50){
            if(!input('post.page') && !preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                datamsg(400,'缺少页面参数',array('status'=>400));
            }

            $keyword_name = input('post.keyword_name');
            $pagenum = input('post.page');

            $where1 = '';

            $webconfig = $this->webconfig;
            $perpage = $webconfig['app_goodlst_num'];
            $offset = ($pagenum-1)*$perpage;
            // 根据商品分类中的关键词关联搜索
            $cates = Db::name('category')->where('is_show',1)->where("find_in_set('".$keyword_name."',search_keywords)")->field('id')->find();
            if($cates) {
                $cate_id = $cates['id'];
                $categoryres = Db::name('category')->where('is_show', 1)->field('id,pid')->order('sort asc')->select();
                $cateIds = array();
                $cateIds = get_all_child($categoryres, $cate_id);
                $cateIds[] = $cate_id;
                $cateIds = implode(',', $cateIds);
                $shopidarr = Db::name('shop_management')->where('cate_id', 'in', $cateIds)->distinct(true)->field('shop_id')->limit($offset, $perpage)->select();
                if ($shopidarr) {
                    $shopidres = array();
                    foreach ($shopidarr as $v) {
                        $shopidres[] = $v['shop_id'];
                    }
                    $shopidres = implode(',', $shopidres);
                    $where1 = "id in (" . $shopidres . ")";
                }
            }
            // 品牌名称关联
            $brands = Db::name('brand')->where('is_show',1)->where('brand_name',$keyword_name)->field('id')->find();
            if($brands){
                $shopidarr = Db::name('shop_managebrand')->where('brand_id',1)->field('shop_id')->limit($offset,$perpage)->select();
                if($shopidarr){
                    $shopidres = array();
                    foreach($shopidarr as $v){
                        $shopidres[] = $v['shop_id'];
                    }
                    $shopidres = implode(',',$shopidres);
                    $where1 = "id in (".$shopidres.") OR ".$where1;
                }
            }

            if(empty($where1)){
                $where1 = "find_in_set('".$keyword_name."',search_keywords) OR shop_name like '%".$keyword_name."%'";
            }else{
                $where1 =$where1."find_in_set('".$keyword_name."',search_keywords) OR shop_name like '%".$keyword_name."%'";
            }

            $where2=['open_status'=>1,'normal'=>1];
            $where3='';
            if(input('post.shop_leixing') && input('post.shop_leixing') != 'all'){
                $shop_type = input('post.shop_leixing');
                switch($shop_type){
                    case 1:
                        $where3 = "shop_leixing = 1"; // shop_leixing:1自营，2商家
                        break;
                    case 2:
                        $where3 = "shop_leixing = 2";
                        break;
                }
            }

            if(input('post.sort')){
                $sort = input('post.sort');
                switch($sort){
                    case 'zonghe':
                        $sortarr = array('shop_leixing'=>'asc','zonghe_fen'=>'desc','id'=>'asc');
                        break;
                    case 'deal_num':
                        $sortarr = array('deal_num '=>'desc','id'=>'asc');
                        break;
                    case 'praise_lv':
                        $sortarr = array('praise_lv '=>'desc','id'=>'asc');
                        break;
                    default:
                        $sortarr = array('shop_leixing'=>'asc','zonghe_fen'=>'desc','id'=>'asc');
                }
            }else{
                $sortarr = array('shop_leixing'=>'asc','zonghe_fen'=>'desc','id'=>'asc');
            }

            $shopres = Db::name('shops')->where($where1)->where($where2)->where($where3)->field('id,shop_name,logo,praise_lv,deal_num')->order($sortarr)->select();



            $webconfig = $this->webconfig;

            if($shopres){
                foreach ($shopres as $key => $val){
                    $shopres[$key]['logo'] = $webconfig['weburl'].'/'.$val['logo'];
                    $shopres[$key]['goodres'] = Db::name('goods')->where('shop_id',$val['id'])->where('onsale',1)->field('id,goods_name,thumb_url,min_price,zs_price,shop_id')->order('zonghe_lv desc,id asc')->limit(3)->select();
                    if($shopres[$key]['goodres']){
                        foreach ($shopres[$key]['goodres'] as $key2 => $val2){
                            $ruinfo = array('id'=>$val2['id'],'shop_id'=>$val2['shop_id']);
                            $commonModel = new CommonModel();
                            $activity = $commonModel->getActivityInfo($ruinfo);
                            if($activity){
                                $shopres[$key]['goodres'][$key2]['zs_price'] = $activity['price'];
                            }else{
                                $shopres[$key]['goodres'][$key2]['zs_price'] = $val2['min_price'];
                            }
                            $shopres[$key]['goodres'][$key2]['thumb_url'] = url_format($val2['thumb_url'],$webconfig['weburl'],'?imageMogr2/thumbnail/350x350');
                        }
                    }
                }
            }
            datamsg(200,'获取商家信息成功',$shopres);
        }else{
            datamsg(400,'搜索内容最多50个字符',array('status'=>400));
        }
    }

    public function getHotSearch(){
        $hotKeywords = get_config_value('hot_search');
        if(!empty($hotKeywords)){
            $hotKeywords = explode(',',$hotKeywords);
        }else{
            $hotKeywords = array(lang('热门'));
        }
        datamsg(200,'获取热门搜索',$hotKeywords);
    }
}
