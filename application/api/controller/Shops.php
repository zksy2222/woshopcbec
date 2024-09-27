<?php
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use app\api\model\Goods as GoodsModel;
use app\api\model\Shops as ShopModel;
use think\Db;

class Shops extends Common{
    
    //获取店铺街列表
    public function getShopList(){
	    $tokenRes = $this->checkToken(0);
	    if ($tokenRes['status'] == 400) {
		    datamsg(400, $tokenRes['mess'], $tokenRes['data']);
	    }

        if(!input('post.page') && !preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
            datamsg(400, '缺少页面参数', array('status'=>400));
        }

	    $pagenum = input('post.page');

	    $where = '';

	    $webconfig = $this->webconfig;
	    $perpage = $webconfig['app_goodlst_num'];
	    $offset = ($pagenum-1)*$perpage;

	    if(input('post.sort')){
	        $sort = input('post.sort');
	        switch($sort){
	            case 'zonghe':
	                $sortarr = array('shop_leixing'=>'desc','zonghe_fen'=>'desc','id'=>'desc');
	                break;
	            case 'deal_num':
	                $sortarr = array('deal_num '=>'desc','id'=>'desc');
	                break;
	            case 'praise_lv':
	                $sortarr = array('praise_lv '=>'desc','id'=>'desc');
	                break;
	            default:
	                $sortarr = array('shop_leixing'=>'desc','zonghe_fen'=>'desc','id'=>'desc');
	        }
	    }else{
	        $sortarr = array('shop_leixing'=>'asc','zonghe_fen'=>'desc','id'=>'asc');
	    }

	    $shopres = Db::name('shops')->where($where)->where('open_status',1)->field('id,shop_name,logo,praise_lv,deal_num')->order($sortarr)->limit($offset,$perpage)->select();
        $total = Db::name('shops')->where($where)->where('open_status',1)->field('id')->order($sortarr)->count();

	    $webconfig = $this->webconfig;

	    if($shopres){
	        foreach ($shopres as $key => $val){
	            $shopres[$key]['logo'] = url_format($val['logo'],$webconfig['weburl'],'?imageMogr2/thumbnail/150x150');

	            $shopres[$key]['goodres'] = Db::name('goods')->where('shop_id',$val['id'])->where('onsale',1)->field('id,goods_name,thumb_url,min_price,zs_price,shop_id')->order('zonghe_lv desc,id asc')->limit(3)->select();

	            if($shopres[$key]['goodres']){
	                foreach ($shopres[$key]['goodres'] as $key2 => $val2){
	                    $ruinfo = array('id'=>$val2['id'],'shop_id'=>$val2['shop_id']);
	                    $commonModel = new CommonModel();
	                    $activitys = $commonModel->getActivityInfo($ruinfo);

	                    if($activitys){
	                        $shopres[$key]['goodres'][$key2]['zs_price'] = $activitys['price'];
	                    }else{
	                        $shopres[$key]['goodres'][$key2]['zs_price'] = $val2['min_price'];
	                    }
	                    $shopres[$key]['goodres'][$key2]['thumb_url'] = url_format($val2['thumb_url'],$webconfig['weburl']);;
	                }
	            }
	        }
	    }
	    datamsg(200, '获取商家信息成功', ['data'=>$shopres,'total'=>$total,'per_page'=>(int)$perpage]);
    }

    //获取商家详情接口
    public function getShopInfo(){
	    $tokenRes = $this->checkToken(0);
	    if ($tokenRes['status'] == 400) {
		    datamsg(400, $tokenRes['mess'], $tokenRes['data']);
	    }

                
        $token = input('post.token');
        $userId = Db::name('member_token')->where('token', $token)->value('user_id');
	    if(!input('post.shop_id')){
		    datamsg(400, '缺少商家参数',array('status'=>400));
	    }

        $shop_id = input('post.shop_id');

        $shops = Db::name('shops')->where('id',$shop_id)->where('open_status',1)->field('id,shop_name,logo,praise_lv,deal_num,settlement_date,shop_desc')->find();
        if(!$shops){
	        datamsg(400, '找不到相关商家信息',array('status'=>400));
        }

        $shops['shop_token'] = '';
        $member_shops = Db::name('member')->where('shop_id',$shop_id)->field('id')->find();
        if($member_shops){
            $shoptoken_infos = Db::name('member_token')->where('user_id',$member_shops['id'])->field('token')->find();
            if($shoptoken_infos){
                $shops['shop_token'] = $shoptoken_infos['token'];
            }
        }

        $webconfig = $this->webconfig;

        $shops['logo'] = url_format($shops['logo'],$webconfig['weburl'],'?imageMogr2/thumbnail/300x300');    //店铺logo 还是沿用以前的上传图片的logo

        if($userId){
            $colls = Db::name('coll_shops')->where('user_id',$userId)->where('shop_id',$shop_id)->find();
            if($colls){
                $shops['coll_shops'] = 1;
            }else{
                $shops['coll_shops'] = 0;
            }
        }else{
            $shops['coll_shops'] = 0;
        }

        //优惠券
        $couponres = Db::name('coupon')->where('shop_id',$shop_id)->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->field('id,man_price,dec_price,start_time,end_time,shop_id')->order('man_price asc')->select();
        foreach($couponres as $kpu => $vpu){
            $couponres[$kpu]['start_time'] = date('m-d',$vpu['start_time']);
            $couponres[$kpu]['end_time'] = date('m-d',$vpu['end_time']);
            $member_coupons = Db::name('member_coupon')->where('user_id',$userId)->where('coupon_id',$vpu['id'])->where('shop_id',$vpu['shop_id'])->find();
            if($member_coupons){
                $couponres[$kpu]['have'] = 1;
            }else{
                $couponres[$kpu]['have'] = 0;
            }
        }

        //商品活动信息
        $promotionres = Db::name('promotion')->where('shop_id',$shop_id)->where('is_show',1)->where('recommend',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,pic_url,info_id,shop_id')->select();
        if($promotionres){
            foreach ($promotionres as $k3 => $v3){
                $promotionres[$k3]['pic_url'] = url_format($v3['pic_url'],$webconfig['weburl']);
                $info_id = explode(',', $v3['info_id']);
                $promotionres[$k3]['goods_id'] = $info_id[0];
            }
        }
        //今日促销
        $rushres = Db::name('seckill')->where('shop_id',$shop_id)->where('checked',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->distinct(true)->field('goods_id')->order('apply_time desc')->select();
        $groupres = Db::name('group_buy')->where('shop_id',$shop_id)->where('checked',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->distinct(true)->field('goods_id')->order('apply_time asc')->select();
        $assembleres = Db::name('assemble')->where('shop_id',$shop_id)->where('checked',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->distinct(true)->field('goods_id')->order('apply_time asc')->select();

        $rusharr = array();
        if($rushres){
            foreach ($rushres as $v){
                $rusharr[] = $v['goods_id'];
            }
        }
        if($rusharr){
            $rusharr = array_unique($rusharr);
        }

        $grouparr = array();
        if($groupres){
            foreach ($groupres as $v2){
                $grouparr[] = $v2['goods_id'];
            }
        }
        if($grouparr){
            $grouparr = array_unique($grouparr);
        }

        $assemarr = array();
        if($assembleres){
            foreach ($assembleres as $v2){
                $assemarr[] = $v2['goods_id'];
            }
        }
        if($assemarr){
            $assemarr = array_unique($assemarr);
        }

        $cuxiaohd = array_merge($rusharr,$grouparr,$assemarr);

        $shopcomwz = array();

        $shop_customs = Db::name('shop_custom')->where('shop_id',$shop_id)->where('type',1)->field('info_id')->find();
        if($shop_customs){
            $shopcomwz = explode(',', $shop_customs['info_id']);
            $cuxiaoarr = array_merge($cuxiaohd,$shopcomwz);
        }else{
            $cuxiaoarr = $cuxiaohd;
        }

        $cxgoodres = array();

        if($cuxiaoarr){
            $cuxiaoarr = array_unique($cuxiaoarr);
            $cuxiaoarr = implode(',', $cuxiaoarr);
            $cxgoodres = Db::name('goods')->where('id','in',$cuxiaoarr)->where('shop_id',$shop_id)->where('onsale',1)->field('id,goods_name,thumb_url,min_price,zs_price,leixing,shop_id')->order('zonghe_lv desc,id asc')->select();

            if($cxgoodres){
                foreach ($cxgoodres as $k =>$v){
                    $cxgoodres[$k]['thumb_url'] = $webconfig['weburl'].'/'.$v['thumb_url'];
                    $cxgoodres[$k]['coupon'] = 0;

                    //优惠券
                    $coupons = Db::name('coupon')->where('shop_id',$v['shop_id'])->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->find();
                    if($coupons){
                        $cxgoodres[$k]['coupon'] = 1;
                    }

                    $ruinfo = array('id'=>$v['id'],'shop_id'=>$v['shop_id']);
                    $commonModel = new CommonModel();
                    $activitys = $commonModel->getActivityInfo($ruinfo);

                    if($activitys){
                        $cxgoodres[$k]['is_activity'] = $activitys['ac_type'];

                        if(!empty($activitys['goods_attr'])){
                            $goods_attr_str = '';
                            $gares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$activitys['goods_attr'])->where('a.goods_id',$v['id'])->where('b.attr_type',1)->select();
                            if($gares){
                                foreach ($gares as $key => $val){
                                    if($key == 0){
                                        $goods_attr_str = $val['attr_name'].':'.$val['attr_value'];
                                    }else{
                                        $goods_attr_str = $goods_attr_str.' '.$val['attr_name'].':'.$val['attr_value'];
                                    }
                                }
                                $cxgoodres[$k]['goods_name'] = $v['goods_name'].' '.$goods_attr_str;
                            }
                        }

                        $cxgoodres[$k]['zs_price'] = $activitys['price'];
                    }else{
                        $cxgoodres[$k]['is_activity'] = 0;
                        $cxgoodres[$k]['zs_price'] = $v['min_price'];
                    }
                }
            }
        }

		//结算日期
		$jsday = $shops['settlement_date'];

		//结算率
		$service_rate = $shops['service_rate'];

		//起始时间
		//当前年月
		$data = date("Y-m");
		//上个月
		$last_month = date('Y-m',strtotime('last month'));

		$starttime = strtotime($last_month.'-'.$jsday);
		$endtime = strtotime($data.'-'.$jsday);

		if($endtime < time()){//结束日期小于当前日期,改变开始日期未结束日期,结束日期加下个月
			$starttime = $endtime;
			$last_month = date('Y-m',strtotime('+1 month'));
			$endtime = strtotime($last_month.'-'.$jsday);
		}

		$wheretime = array('a.shop_id'=>$shop_id,'a.is_show'=>1);
		$sorttime = array('a.addtime'=>'desc','a.id'=>'desc');

		$wheretime0 = array('shop_id'=>$shop_id,'apply_status'=>3);
		$wheretime1['apply_time'] = array('egt',$starttime);// >=
		$wheretime1['apply_time'] = array('elt',$endtime);// <=
		$sorttime1 = array('apply_time'=>'desc');

		//收益金额
		$profits = Db::name('wallet')->where('user_id',$userId)->find();

		//已结算
		$tixian = Db::name('detail')->where(['user_id'=>$userId,'zc_type'=>1])->sum('price');

		//订单金额
		$ordermoneytotal = Db::name('order')->alias('a')->field('a.id')->join('sp_shops b','a.shop_id = b.id','INNER')->where($wheretime)->where('a.state', 1)->order($sorttime)->sum('a.total_price');

		//本月订单金额
		$ordermoney = Db::name('order')->alias('a')->field('a.id')->join('sp_shops b','a.shop_id = b.id','INNER')->where($wheretime)->where("a.addtime >= '".$starttime."' and a.addtime <= '".$endtime."'")->order($sorttime)->sum('a.total_price');

		$tuicount = Db::name('th_apply')->field('id')->where($wheretime0)->where('thfw_id','in','1,2')->where("apply_time >= '".$starttime."' and apply_time <= '".$endtime."'")->order($sorttime1)->count();
		//dump( Db::getLastSql());
        $billing['accountDays'] = $jsday;
        $billing['money'] = $ordermoneytotal * (100-$service_rate) / 100 + $profits['price'];
        $billing['monthDealCount'] = Db::name('order')->alias('a')->field('a.id')->join('sp_shops b','a.shop_id = b.id','INNER')->where($wheretime)->where("a.addtime >= '".$starttime."' and a.addtime <= '".$endtime."'")->order($sorttime)->count();
        $billing['monthDealMoney'] = $ordermoney;
        $billing['monthReturnCount'] = Db::name('th_apply')->field('id')->where($wheretime0)->where('thfw_id','in','1,2')->where("apply_time >= '".$starttime."' and apply_time <= '".$endtime."'")->order($sorttime1)->count();
        $billing['monthReturnMoney'] = Db::name('th_apply')->field('tui_price')->where($wheretime0)->where('thfw_id','in','1,2')->where("apply_time >= '".$starttime."' and apply_time <= '".$endtime."'")->order($sorttime1)->sum('tui_price');

		//->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)

		//待发货
		$where1 = array('a.shop_id'=>$shop_id,'a.state'=>1,'a.fh_status'=>0,'a.order_status'=>0,'a.is_show'=>1);
		$sort1 = array('a.pay_time'=>'desc','a.id'=>'desc');


		$send_num = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.coupon_id,a.total_price,a.state,a.fh_status,a.order_status,a.shouhou,a.ping,a.is_show,a.ping,a.order_type,a.pin_type,a.pin_id,a.shop_id,a.zdsh_time,a.time_out,b.shop_name')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where1)->order($sort1)->count();
		if($send_num == 0){
            $send_num = "";
        }
		//待收货
		$where2 = array('a.shop_id'=>$shop_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>0,'a.is_show'=>1);
		$sort2 = array('a.fh_time'=>'desc','a.id'=>'desc');
		$shou_num = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.coupon_id,a.total_price,a.state,a.fh_status,a.order_status,a.shouhou,a.ping,a.is_show,a.ping,a.order_type,a.pin_type,a.pin_id,a.shop_id,a.zdsh_time,a.time_out,b.shop_name')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where2)->order($sort2)->count();
        if($shou_num == 0){
            $shou_num = "";
        }
		//待评价
		$where3 = array('a.shop_id'=>$shop_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>1,'a.ping'=>0,'a.is_show'=>1);
		$sort3 = array('a.coll_time'=>'desc','a.id'=>'desc');
		$ping_num = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.coupon_id,a.total_price,a.state,a.fh_status,a.order_status,a.shouhou,a.ping,a.is_show,a.ping,a.order_type,a.pin_type,a.pin_id,a.shop_id,a.zdsh_time,a.time_out,b.shop_name')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where3)->order($sort3)->count();
		if($ping_num == 0){
            $ping_num = "";
        }
		//待付款
		$where4 = array('a.shop_id'=>$shop_id,'a.state'=>0,'a.fh_status'=>0,'a.order_status'=>0,'a.is_show'=>1);
		$sort4 = array('a.addtime'=>'desc','a.id'=>'desc');
		$pay_num = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.coupon_id,a.total_price,a.state,a.fh_status,a.order_status,a.shouhou,a.ping,a.is_show,a.ping,a.order_type,a.pin_type,a.pin_id,a.shop_id,a.zdsh_time,a.time_out,b.shop_name')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where4)->order($sort4)->count();
        if($pay_num == 0){
            $pay_num = "";
        }
		//退换货
		$huan_num=Db::name('th_apply')->field('id')->where(array('shop_id'=>$shop_id))->where('thfw_id','in','1,2,3')->order($sorttime1)->count();
		if($huan_num == 0){
            $huan_num = "";
        }
		$shopinfores = array('shops'=>$shops,'couponres'=>$couponres,'promotionres'=>$promotionres,'cxgoodres'=>$cxgoodres,'billing'=>$billing,'pay_num'=>$pay_num,'send_num'=>$send_num,'shou_num'=>$shou_num,'ping_num'=>$ping_num,'huan_num'=>$huan_num);


        //$shopinfores = array('shops'=>$shops,'couponres'=>$couponres,'promotionres'=>$promotionres,'cxgoodres'=>$cxgoodres,'billing'=>$billing);
        datamsg(200,'获取商家信息成功',$shopinfores);
    }
    
    //获取商家全部商品
    public function allgoods(){
	    $tokenRes = $this->checkToken(0);
	    if ($tokenRes['status'] == 400) {
		    datamsg(400, $tokenRes['mess'], $tokenRes['data']);
	    }

        if(!input('post.shop_id')){
            datamsg(400, '缺少商家参数', array('status'=>400));
        }

        if(!input('post.page') && !preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
            datamsg(400, '缺少页面参数', array('status'=>400));
        }

        $shop_id = input('post.shop_id');
        $pagenum = input('post.page');

        $shops = Db::name('shops')->where('id',$shop_id)->where('open_status',1)->field('id')->find();
        if(!$shops){
	        datamsg(400, '找不到相关商家信息', array('status'=>400));
        }

        $where = array();
        $where['shop_id'] = $shop_id;
        $where['onsale'] = 1;

        $webconfig = $this->webconfig;
        $perpage = $webconfig['app_goodlst_num'];
        $offset = ($pagenum-1)*$perpage;

        if(input('post.sort')){
            $sort = input('post.sort');
            switch($sort){
                case 'zonghe':
                    $sortarr = array('zonghe_lv'=>'desc','id'=>'asc');
                    break;
                case 'new':
                    $sortarr = array('addtime'=>'desc','id'=>'asc');
                    break;
                case 'deal_num':
                    $sortarr = array('deal_num '=>'desc','id'=>'asc');
                    break;
                case 'low_height':
                    $sortarr = array('zs_price'=>'asc','id'=>'asc');
                    break;
                case 'height_low':
                    $sortarr = array('zs_price'=>'desc','id'=>'asc');
                    break;
                default:
                    $sortarr = array('zonghe_lv'=>'desc','id'=>'asc');
            }
        }else{
            $sortarr = array('zonghe_lv'=>'desc','id'=>'asc');
        }

        $goodres = Db::name('goods')->where($where)->field('id,goods_name,thumb_url,min_price,zs_price,leixing,shop_id')->order($sortarr)->limit($offset,$perpage)->select();
        if($goodres){
            foreach ($goodres as $k =>$v){
                $goodres[$k]['thumb_url'] = $webconfig['weburl'].'/'.$v['thumb_url'];
                $goodres[$k]['coupon'] = 0;

                //优惠券
                $coupons = Db::name('coupon')->where('shop_id',$v['shop_id'])->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->find();
                if($coupons){
                    $goodres[$k]['coupon'] = 1;
                }

                $ruinfo = array('id'=>$v['id'],'shop_id'=>$v['shop_id']);
                $commonModel = new CommonModel();
                $activitys = $commonModel->getActivityInfo($ruinfo);

                if($activitys){
                    $goodres[$k]['is_activity'] = $activitys['ac_type'];

                    if(!empty($activitys['goods_attr'])){
                        $goods_attr_str = '';
                        $gares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$activitys['goods_attr'])->where('a.goods_id',$v['id'])->where('b.attr_type',1)->select();
                        if($gares){
                            foreach ($gares as $key => $val){
                                if($key == 0){
                                    $goods_attr_str = $val['attr_name'].':'.$val['attr_value'];
                                }else{
                                    $goods_attr_str = $goods_attr_str.' '.$val['attr_name'].':'.$val['attr_value'];
                                }
                            }
                            $goodres[$k]['goods_name'] = $v['goods_name'].' '.$goods_attr_str;
                        }
                    }

                    $goodres[$k]['zs_price'] = $activitys['price'];
                }else{
                    $goodres[$k]['is_activity'] = 0;
                    $goodres[$k]['zs_price'] = $v['min_price'];
                }
            }
        }
	    datamsg(200, '获取商家商品信息成功', $goodres);
    }
    
    //商家商品列表信息
    public function getShopGoodsList(){
	    $tokenRes = $this->checkToken(0);
	    if ($tokenRes['status'] == 400) {
		    datamsg(400, $tokenRes['mess'], $tokenRes['data']);
	    }

	    if(!input('post.shop_id')){
		    datamsg(400, '缺少商家参数', array('status'=>400));
	    }

	    if(!input('post.page') && !preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
		    datamsg(400, '缺少页面参数', array('status'=>400));
	    }

        $shop_id = input('post.shop_id');
        $pagenum = input('post.page');

        $shops = Db::name('shops')->where('id',$shop_id)->where('open_status',1)->field('id')->find();
        if(!$shops){
            datamsg(400, '找不到相关商家信息', array('status'=>400));
        }

        $where = array();
        $where['shop_id'] = $shop_id;
        $where['onsale'] = 1;

        $webconfig = $this->webconfig;
        $perpage = $webconfig['app_goodlst_num'];
        $offset = ($pagenum-1)*$perpage;

        if(input('post.shcate_id')){
            $shcate_id = input('post.shcate_id');
            $shcates = Db::name('shop_cate')->where('id',$shcate_id)->where('shop_id',$shop_id)->where('is_show',1)->field('id,cate_name')->find();
            if($shcates){
                $shcateres = Db::name('shop_cate')->where('shop_id',$shop_id)->where('is_show',1)->field('id,pid')->order('sort asc')->select();
                $cateIds = array();
                $cateIds = get_all_child($shcateres, $shcate_id);
                $cateIds[] = $shcate_id;
                $cateIds = implode(',', $cateIds);
                $where['shcate_id'] = array('in',$cateIds);
            }else{
                $value = array('status'=>400,'mess'=>'找不到相关分类信息','data'=>array('status'=>400));
                return json($value);
            }
        }

        if(input('post.sort')){
            $sort = input('post.sort');
            switch($sort){
                case 'zonghe':
                    $sortarr = array('zonghe_lv'=>'desc','id'=>'asc');
                    break;
                case 'new':
                    $sortarr = array('addtime'=>'desc','id'=>'asc');
                    break;
                case 'deal_num':
                    $sortarr = array('deal_num '=>'desc','id'=>'asc');
                    break;
                case 'low_height':
                    $sortarr = array('zs_price'=>'asc','id'=>'asc');
                    break;
                case 'height_low':
                    $sortarr = array('zs_price'=>'desc','id'=>'asc');
                    break;
                default:
                    $sortarr = array('zonghe_lv'=>'desc','id'=>'asc');
            }
        }else{
            $sortarr = array('zonghe_lv'=>'desc','id'=>'asc');
        }

        $goodres = Db::name('goods')->where($where)->field('id,goods_name,thumb_url,min_price,zs_price,leixing,shop_id,is_live')->order($sortarr)->limit($offset,$perpage)->select();
        $goodsModel = new GoodsModel();
        if($goodres){
            foreach ($goodres as $k =>$v){
                $goodres[$k]['goods_name'] = $this->getGoodsLangName($v['id'],$this->langCode);
                $goodres[$k]['thumb_url'] = url_format($v['thumb_url'],$webconfig['weburl']);
                $goodres[$k]['coupon'] = 0;

                //优惠券
                $coupons = Db::name('coupon')->where('shop_id',$v['shop_id'])->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->find();
                if($coupons){
                    $goodres[$k]['coupon'] = 1;
                }

                $ruinfo = array('id'=>$v['id'],'shop_id'=>$v['shop_id']);
                $commonModel = new CommonModel();
                $activitys = $commonModel->getActivityInfo($ruinfo);

                if($activitys){
                    $goodres[$k]['is_activity'] = $activitys['ac_type'];
                    if($activitys['ac_type'] == 1){
                        $seckillPriceData = $goodsModel->getGoodsShowPrice($v['id'],'seckill','list');
                        $goodres[$k]['zs_price'] = $seckillPriceData['seckill_price'];
                    }
                    if($activitys['ac_type'] == 3){
                        $assemblePriceDate = $goodsModel->getGoodsShowPrice($v['id'],'assemble','list');
                        $goodres[$k]['zs_price'] = $assemblePriceDate['assemble_price'];
                    }

                }else{
                    $goodres[$k]['is_activity'] = 0;
                    $goodres[$k]['zs_price'] = $v['min_price'];
                }
            }
        }
	    datamsg(200, '获取商家商品信息成功', $goodres);
    }
    
    //获取店铺详细信息
    public function getShopBaseInfo(){
	    $tokenRes = $this->checkToken(0);
	    if ($tokenRes['status'] == 400) {
		    datamsg(400, $tokenRes['mess'], $tokenRes['data']);
	    }

	    if(!input('post.shop_id')){
		    datamsg(400, '缺少商家参数', array('status'=>400));
	    }

        $shop_id = input('post.shop_id');
        $shops = Db::name('shops')
                   ->where('id',$shop_id)
                   ->where('open_status',1)
                   ->field('id,shop_name,logo,shop_desc,praise_lv,goods_fen,fw_fen,wuliu_fen')
                   ->find();
        if(!$shops){
	        datamsg(400, '找不到相关商家信息', array('status'=>400));
        }
        $shopApplyInfo = Db::name('apply_info')
                           ->where('shop_id',$shop_id)
                           ->where('state',1)
                           ->where('complete',1)
                           ->find();

        $shops['logo'] = url_format($shops['logo'],$this->webconfig,'?imageMogr2/thumbnail/300x300');
        $shops['zhizhao'] = url_format($shopApplyInfo['zhizhao'],$this->webconfig,'?imageMogr2/thumbnail/750');
	    datamsg(200, '获取商家信息成功', $shops);
    }
    
    //获取商家分类信息
    public function shopcates(){
	    $tokenRes = $this->checkToken(0);
	    if ($tokenRes['status'] == 400) {
		    datamsg(400, $tokenRes['mess'], $tokenRes['data']);
	    }

	    if(!input('post.shop_id')){
		    datamsg(400, '缺少商家参数', array('status'=>400));
	    }

        $shop_id = input('post.shop_id');
        $shops = Db::name('shops')->where('id',$shop_id)->where('open_status',1)->field('id')->find();
	    if(!$shops){
		    datamsg(400, '找不到相关商家信息', array('status'=>400));
	    }

        $cateres = Db::name('shop_cate')->where('shop_id',$shop_id)->where('pid',0)->where('is_show',1)->field('id,cate_name,pid')->order('sort asc')->select();
        foreach ($cateres as $k =>$v){
            $cateres[$k]['twocate'] = Db::name('shop_cate')->where('shop_id',$shop_id)->where('pid',$v['id'])->where('is_show',1)->field('id,cate_name,pid')->order('sort asc')->select();
        }
        $cateinfos = array('shop_id'=>$shop_id,'cateres'=>$cateres);
	    datamsg(200, '获取商家信息成功', $cateinfos);
    }
    
    
    //获取商家促销活动信息
    public function getPromotionInfo(){
	    $tokenRes = $this->checkToken(0);
	    if ($tokenRes['status'] == 400) {
		    datamsg(400, $tokenRes['mess'], $tokenRes['data']);
	    }

	    if(!input('post.prom_id')){
		    datamsg(400, '缺少活动参数', array('status'=>400));
	    }

        $prom_id = input('post.prom_id');
        $promotions = Db::name('promotion')->alias('a')->field('a.id,a.start_time,a.end_time,a.info_id,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$prom_id)->where('a.is_show',1)->where('a.recommend',1)->where('a.start_time','elt',time())->where('a.end_time','gt',time())->where('b.open_status',1)->find();

        if(!$promotions){
	        datamsg(400, '找不到相关活动信息', array('status'=>400));
        }

	    $promoinfos = array();
	    $start_time = date('Y年m月d日 H时',$promotions['start_time']);
	    $end_time = date('Y年m月d日 H时',$promotions['end_time']);
	    $prom_typeres = Db::name('prom_type')->where('prom_id',$promotions['id'])->select();
	    if(!$prom_typeres){
		    datamsg(400, '找不到相关活动信息或活动已过期', array('status'=>400));
	    }

        $promotion_infos = '';

        foreach ($prom_typeres as $kcp => $vcp){
            $zhekou = $vcp['discount']/10;
            if($kcp == 0){
                $promotion_infos = lang('商品满').$vcp['man_num'].lang('件享').$zhekou.lang('折');
            }else{
                $promotion_infos = $promotion_infos.lang('满').$vcp['man_num'].lang('件享').$zhekou.lang('折');
            }
        }

        $promoinfos['id'] = $promotions['id'];
        $promoinfos['hd_name'] = $promotion_infos;
        $promoinfos['time'] = lang('有效期：').$start_time.lang('至').$end_time.lang('截止');
        $promoinfos['start_time'] = $promotions['start_time'];
        $promoinfos['end_time'] = $promotions['end_time'];
        $promoinfos['dqtime'] = time();
	    datamsg(200, '获取促销活动信息成功', $promoinfos);
    }

	//获取商家促销活动商品信息
    public function getPromotionGoodsList(){
	    $tokenRes = $this->checkToken(0);
	    if ($tokenRes['status'] == 400) {
		    datamsg(400, $tokenRes['mess'], $tokenRes['data']);
	    }

	    if(!input('post.prom_id')){
		    datamsg(400, '缺少活动参数', array('status'=>400));
	    }

        if(!input('post.page') && !preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
            datamsg(400, '缺少页面参数', array('status'=>400));
        }

        $prom_id = input('post.prom_id');
        $pagenum = input('post.page');

        $webconfig = $this->webconfig;
        $perpage = $webconfig['app_goodlst_num'];
        $offset = ($pagenum-1)*$perpage;

        $promotions = Db::name('promotion')->alias('a')->field('a.id,a.start_time,a.end_time,a.info_id,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$prom_id)->where('a.is_show',1)->where('a.recommend',1)->where('a.start_time','elt',time())->where('a.end_time','gt',time())->where('b.open_status',1)->find();

        if(!$promotions){
	        datamsg(400, '找不到相关活动信息', array('status'=>400));
        }

        $goodres = Db::name('goods')->where('id','in',$promotions['info_id'])->where('shop_id',$promotions['shop_id'])->where('onsale',1)->field('id,goods_name,thumb_url,min_price,zs_price,leixing,shop_id')->order('zonghe_lv desc,id asc')->limit($offset,$perpage)->select();


        if($goodres){
            foreach ($goodres as $k =>$v){
                $goodres[$k]['thumb_url'] = url_format($v['thumb_url'],$webconfig['weburl']);
                $goodres[$k]['coupon'] = 0;

                //优惠券
                $coupons = Db::name('coupon')->where('shop_id',$v['shop_id'])->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->find();
                if($coupons){
                    $goodres[$k]['coupon'] = 1;
                }

                $ruinfo = array('id'=>$v['id'],'shop_id'=>$v['shop_id']);
                $commonModel = new CommonModel();
                $activitys = $commonModel->getActivityInfo($ruinfo);

                if($activitys){
                    $goodres[$k]['is_activity'] = $activitys['ac_type'];

                    if(!empty($activitys['goods_attr'])){
                        $goods_attr_str = '';
                        $gares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$activitys['goods_attr'])->where('a.goods_id',$v['id'])->where('b.attr_type',1)->select();
                        if($gares){
                            foreach ($gares as $key => $val){
                                if($key == 0){
                                    $goods_attr_str = $val['attr_name'].':'.$val['attr_value'];
                                }else{
                                    $goods_attr_str = $goods_attr_str.' '.$val['attr_name'].':'.$val['attr_value'];
                                }
                            }
                            $goodres[$k]['goods_name'] = $v['goods_name'].' '.$goods_attr_str;
                        }
                    }

                    $goodres[$k]['zs_price'] = $activitys['price'];
                }else{
                    $goodres[$k]['is_activity'] = 0;
                    $goodres[$k]['zs_price'] = $v['min_price'];
                }
            }
        }
	    datamsg(200, '获取商品信息成功', $goodres);
    }

    // 获取关注的店铺列表
    public function getFollowShops(){
        // 验证token
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
	        datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        }else{
            $userId = $tokenRes['user_id'];
        }


        $followShop = db('coll_shops')->where(['user_id'=>$userId])->column('shop_id');
        $size = input('param.size') ? input('param.size') : 10;
        $where = ['id'=>['IN',$followShop]];
        $list = Db::name('shops')->where($where)->field("id,shop_name,shop_desc,logo")
            ->order("id DESC")
            ->paginate($size)
            ->each(function ($item, $key) {
                $item['logo'] = $this->webconfig['weburl'].$item['logo'];
                return $item;
            });
        datamsg(200, '获取成功', $list);

    }

    //获取当前用户关注的店铺
    public function getFollowShopList(){
        // 验证token
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        }else{
            $userId = $tokenRes['user_id'];
        }


        if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
            $pagenum = input('post.page');

            $webconfig = $this->webconfig;
            $perpage = $webconfig['app_goodlst_num'];
            $offset = ($pagenum-1)*$perpage;

            if(input('post.sort')){
                $sort = input('post.sort');
                switch($sort){
                    case 'zonghe':
                        $sortarr = array('a.shop_leixing'=>'desc','a.zonghe_fen'=>'desc','a.id'=>'desc');
                        break;
                    case 'deal_num':
                        $sortarr = array('a.deal_num '=>'desc','a.id'=>'desc');
                        break;
                    case 'praise_lv':
                        $sortarr = array('a.praise_lv '=>'desc','a.id'=>'desc');
                        break;
                    default:
                        $sortarr = array('a.shop_leixing'=>'desc','a.zonghe_fen'=>'desc','a.id'=>'desc');
                }
            }else{
                $sortarr = array('a.shop_leixing'=>'desc','a.zonghe_fen'=>'desc','a.id'=>'desc');
            }

            $followShop = db('coll_shops')->where(['user_id' => $userId])->column('shop_id');

            $shopres = Db::name('shops')->alias('a')->where('a.id', 'in', $followShop)->where('a.open_status',1)
                ->field('a.id,a.shop_name,a.logo,a.praise_lv,a.deal_num,b.status')
                ->join('sp_live b', 'a.id = b.shop_id', 'left')
                ->order($sortarr)
                ->limit($offset,$perpage)
                ->select();

            $webconfig = $this->webconfig;

            if($shopres){
                foreach ($shopres as $key => $val){
                    $shopres[$key]['logo'] = url_format($val['logo'],$webconfig['weburl'],'?imageMogr2/thumbnail/350x350');

                    $shopres[$key]['goodres'] = Db::name('goods')->where('shop_id',$val['id'])->where('onsale',1)->field('id,goods_name,thumb_url,min_price,zs_price,shop_id,leixing')->order('zonghe_lv desc,id asc')->limit(3)->select();

                    if($shopres[$key]['goodres']){
                        foreach ($shopres[$key]['goodres'] as $key2 => $val2){
                            $ruinfo = array('id'=>$val2['id'],'shop_id'=>$val2['shop_id']);
                            $commonModel = new CommonModel();
                            $activitys = $commonModel->getActivityInfo($ruinfo);

                            if($activitys){
                                $shopres[$key]['goodres'][$key2]['zs_price'] = $activitys['price'];
                            }else{
                                $shopres[$key]['goodres'][$key2]['zs_price'] = $val2['min_price'];
                            }
                            $shopres[$key]['goodres'][$key2]['thumb_url'] = url_format($val2['thumb_url'],$webconfig['weburl']);
                        }
                    }
                }
            }
            datamsg(200, '获取商家信息成功', $shopres);
        }else{
            datamsg(400, '缺少页面参数', array('status'=>400));
        }
    }

    // 首页店铺街
    public function getRecommendShops(){
        // 验证api_token
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){ // 400返回错误描述
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }

        $webconfig = $this->webconfig;
        $shopModel = new ShopModel();
        $shopList = $shopModel->getRecommendShops(40);

        $goodsModel = new GoodsModel();
        foreach ($shopList as $k=>$v){
            $shopList[$k]['logo'] = url_format($v['logo'],$webconfig['weburl']);
            $goodsList = $goodsModel->getShopGoods($v['id'],3);
            $shopList[$k]['goods_list'] = $goodsList;
        }

        datamsg(200, '获取成功', $shopList);
    }
}