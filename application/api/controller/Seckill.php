<?php
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use app\api\model\SaleTime as SaleTimeModel;
use think\Db;

class Seckill extends Common{
    
    //获取秒杀时间段
    public function getSeckillTime(){
	    $tokenRes = $this->checkToken(0);
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }

        $saleTimeModel = new SaleTimeModel();
        $seckillTime = $saleTimeModel->getSaleTime();
        if($seckillTime['status'] == 400){
            datamsg(400,'获取秒杀时间段失败');
        }else{
            $seckillTimeArr = array();
            foreach ($seckillTime['data'] as $v) {
                if(($v['cuxiao'] == 1 && $v['show'] ==1) || $v['cuxiao'] == 0){
                    $seckillTimeArr[] = $v;
                }
            }
            datamsg(200,'获取秒杀时间段成功',$seckillTimeArr);
        }


    }
    
    public function index(){
	    $tokenRes = $this->checkToken(0);
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }

        if(!input('post.nowtime')){
            datamsg(400,'缺少时间段参数',array('status'=>400));
        }

        if(!input('post.page') && !preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
            datamsg(400,'缺少页面参数',array('status'=>400));
        }

        $nowtime = input('post.nowtime');
        $pagenum = input('post.page');

        $webconfig = $this->webconfig;
        $perpage = $webconfig['app_goodlst_num'];
        $offset = ($pagenum-1)*$perpage;

        $time = time();

        $sale_times = Db::name('sale_time')->order('time asc')->field('time')->select();
        if(!$sale_times){
	        datamsg(400,'找不到秒杀时间段信息',array('status'=>400));
        }

        $saleTimeModel = new SaleTimeModel();
        $seckillTimeRes = $saleTimeModel->getSaleTime();
        if($seckillTimeRes['status'] == 400){
            datamsg(400,'获取秒杀时间段失败');
        }else{
            $rushtime = $seckillTimeRes['data'];
        }

        $activity = 0;

        foreach ($rushtime as $key => $val){
            if($time >= $val['time'] && $time < $val['end_time']){
                $rushtime[$key]['show'] = 1;
                break;
            }
        }

        foreach ($rushtime as $ku => $vu){
            if($vu['time'] == $nowtime){
                $activity = 1;
                $cuxiao = $vu['cuxiao'];
                $show = $vu['show'];
                $hdtime = $nowtime;
                $end_time = $vu['end_time'];
                break;
            }
        }

        if($activity == 1){
            $seckillRes = Db::name('seckill')->alias('a')
                         ->field('a.id,a.goods_id,a.goods_attr,a.price,a.sold,b.goods_name,b.thumb_url,b.shop_price,b.min_price,b.max_price,b.zs_price,b.leixing,b.shop_id,b.hasoption')
                         ->join('sp_goods b','a.goods_id = b.id','INNER')
                         ->join('sp_shops c','a.shop_id = c.id','INNER')
                         ->where('a.checked',1)
                         ->where('a.recommend',1)
                         ->where('a.is_show',1)
                         ->where('a.start_time','elt',$nowtime)
                         ->where('a.end_time','egt',$nowtime+3600)
                         ->where('a.finish_status',0)
                         ->where('b.onsale',1)
                         ->where('c.open_status',1)
                         ->group('a.goods_id')->order('a.sort esc')->limit($offset,$perpage)->select();

            if($seckillRes){
                foreach ($seckillRes as $kc => $vc){

                    $seckillRes[$kc]['thumb_url'] = url_format($vc['thumb_url'],$webconfig['weburl']);

                    if($vc['hasoption']){
                        $minPriceOption = Db::name('goods_option')->where(['goods_id'=>$vc['goods_id'],'is_seckill'=>1])->order('seckill_price ASC')->find();
                        $seckillRes[$kc]['seckill_price'] = $minPriceOption['seckill_price'];
                        $seckillRes[$kc]['shop_price'] = $minPriceOption['shop_price'];
                        unset($minPriceOption);
                        $totalStock = Db::name('goods_option')->where(['goods_id'=>$vc['goods_id'],'is_seckill'=>1])->sum('seckill_stock');
                        $seckillRes[$kc]['sales_ratio'] = round($vc['sold']/($vc['sold']+$totalStock) * 100,2);
                        unset($totalStock);
                    }else{
                        $seckillRes[$kc]['seckill_price'] = $vc['price'];
                        $seckillRes[$kc]['shop_price'] = $vc['min_price'];
                        $seckillRes[$kc]['sales_ratio'] = round($vc['sold']/($vc['sold']+$vc['stock']) * 100,2);
                    }

                }
            }

            if($pagenum == 1){
                $goodsinfo = array('cuxiao'=>$cuxiao,'show'=>$show,'hdtime'=>$hdtime,'end_time'=>$end_time,'dqtime'=>$time,'goodres'=>$seckillRes);
            }else{
                $goodsinfo = array('cuxiao'=>$cuxiao,'show'=>$show,'goodres'=>$seckillRes);
            }
            datamsg(200,'获取秒杀商品信息成功',$goodsinfo);
        }else{
            datamsg(400,'时间段参数错误');
        }
    }

    //获取秒杀即将开始商品详情
    public function rushgoodinfo(){
	    $tokenRes = $this->checkToken(0);
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }
        if(!empty($tokenRes['user_id'])){
            $userId = $tokenRes['user_id'];
        }else{
            $userId = 0;
        }
        if(!input('post.goods_id')){
	        datamsg(400,'缺少商品参数',array('status'=>400));
        }
	    if(!input('post.rush_id')){
		    datamsg(400,'缺少秒杀活动参数',array('status'=>400));
	    }

        $goodsId = input('post.goods_id');
        $rush_id = input('post.rush_id');

        $goods = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.thumb_url,a.shop_price,a.goods_desc,a.fuwu,a.is_send_free,a.leixing,a.shop_id')->join('sp_shops b','a.shop_id = b.id','INNER')->where('a.id',$goodsId)->where('a.onsale',1)->where('b.open_status',1)->find();
	    if(!$goods){
		    datamsg(400,'商品已下架或不存在',array('status'=>400));
	    }

            if($userId){
                $colls = Db::name('coll_goods')->where('user_id',$userId)->where('goods_id',$goodsId)->find();
                if($colls){
                    $goods['coll_goods'] = 1;
                }else{
                    $goods['coll_goods'] = 0;
                }
            }else{
                $goods['coll_goods'] = 0;
            }

            $rushs = Db::name('seckill')->where('id',$rush_id)->where('goods_id',$goods['id'])->where('shop_id',$goods['shop_id'])->where('checked',1)->where('recommend',1)->where('is_show',1)->where('start_time','gt',time())->field('id,goods_id,goods_attr,price,xznum,stock,sold,start_time,end_time')->find();
	    if(!$rushs){
		    datamsg(400,'找不到相关活动信息',array('status'=>400));
	    }

        $sale_times = Db::name('sale_time')->order('time asc')->field('time')->select();
	    if(!$sale_times){
		    datamsg(400,'找不到秒杀时间段信息',array('status'=>400));
	    }

        $rushtime = array();

        $dctime = date('Y-m-d',time());
        $tomtime = date('Y-m-d',time()+3600);

        foreach ($sale_times as $k2 => $v2){
            if($v2['time'] < 10){
                $dcthetime = strtotime($dctime.' 0'.$v2['time'].':00:00');
            }else{
                $dcthetime = strtotime($dctime.' '.$v2['time'].':00:00');
            }

            $rushtime[] = $dcthetime;
        }

        foreach ($sale_times as $k3 => $v3){
            if($v3['time'] < 10){
                $thetime = strtotime($tomtime.' 0'.$v3['time'].':00:00');
            }else{
                $thetime = strtotime($tomtime.' '.$v3['time'].':00:00');
            }

            $rushtime[] = $thetime;
        }
	    if(!$rushtime){
		    datamsg(400,'找不到秒杀时间段信息',array('status'=>400));
	    }
        if(in_array($rushs['start_time'], $rushtime)){
            $goods['price'] = $rushs['price'];
            $webconfig = $this->webconfig;
            $goods['thumb_url'] = $webconfig['weburl'].'/'.$goods['thumb_url'];
            $goods['goods_desc'] = str_replace("/public/",$webconfig['weburl']."/public/",$goods['goods_desc']);
            $goods['goods_desc'] = str_replace("<img","<img style='width:100%;max-height:1000px;'",$goods['goods_desc']);

            if($rushs['goods_attr']){
                $goods_attr_str = '';
                $gares = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_value,a.attr_price,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$rushs['goods_attr'])->where('a.goods_id',$goods['id'])->where('b.attr_type',1)->select();
                if($gares){
                    foreach ($gares as $kr => $vr){
                        if($kr == 0){
                            $goods_attr_str = $vr['attr_name'].':'.$vr['attr_value'];
                        }else{
                            $goods_attr_str = $goods_attr_str.' '.$vr['attr_name'].':'.$vr['attr_value'];
                        }
                        $goods['shop_price']+=$vr['attr_price'];
                    }
                    $goods['goods_name']=$goods['goods_name'].' '.$goods_attr_str;
                    $goods['shop_price']=sprintf("%.2f", $goods['shop_price']);
                }else{
                    $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
                    return json($value);
                }
            }


            $pronum = $rushs['num'];

            $activity_info = array(
                'num'=>$rushs['num'],
                'xznum'=>$rushs['xznum'],
                'start_time'=>$rushs['start_time'],
                'end_time'=>$rushs['end_time'],
                'dqtime' => time()
            );

            $gpres = Db::name('goods_pic')->where('goods_id',$goodsId)->field('id,img_url,sort')->order('sort asc')->select();
            foreach ($gpres as $kp => $vp){
                $gpres[$kp]['img_url'] = $webconfig['weburl'].'/'.$vp['img_url'];
            }

            $uniattr = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id,a.attr_value,b.attr_name,b.attr_type')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.goods_id',$goodsId)->where('b.attr_type',0)->select();

            $goods_attr = '';

            $ruinfo = array('id'=>$goods['id'],'shop_id'=>$goods['shop_id']);
            $commonModel = new CommonModel();
            $activitys = $commonModel->getActivityInfo($ruinfo);

            //邮费
            if($goods['is_send_free'] == 0){
                $shopinfos = Db::name('shops')->where('id',$goods['shop_id'])->field('freight,reduce')->find();
                $freight = lang('运费').$shopinfos['freight'].lang('订单满').$shopinfos['reduce'].lang('免运费');
            }else{
                $freight = lang('包邮');
            }

            //优惠券
            $couponinfos = array('is_show'=>0,'infos'=>'');
            $couponres = Db::name('coupon')->where('shop_id',$goods['shop_id'])->where('start_time','elt',time())->where('end_time','gt',time()-3600*24)->where('onsale',1)->field('man_price,dec_price')->order('man_price asc')->limit(3)->select();
            if($couponres){
                $couponinfos = array('is_show'=>1,'infos'=>$couponres);
            }

            //商品活动信息
            $huodong = array('is_show'=>0,'infos'=>'','prom_id'=>0);
            $promotions = Db::name('promotion')->where("find_in_set('".$goods['id']."',info_id)")->where('shop_id',$goods['shop_id'])->where('is_show',1)->where('recommend',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,start_time,end_time')->find();
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

            $goodsinfo = array(
                'id'=>$goods['id'],
                'goods_name'=>$goods['goods_name'],
                'thumb_url'=>$goods['thumb_url'],
                'goods_desc'=>$goods['goods_desc'],
                'freight'=>$freight,
                'leixing'=>$goods['leixing'],
                'shop_id'=>$goods['shop_id'],
                'price'=>$goods['price'],
                'shop_price'=>$goods['shop_price'],
                'coll_goods'=>$goods['coll_goods']
            );

            $shopinfos = Db::name('shops')->where('id',$goods['shop_id'])->where('open_status',1)->field('id,shop_name,shop_desc,logo,goods_fen,fw_fen,wuliu_fen')->find();
            $shopinfos['logo'] = $webconfig['weburl'].'/'.$shopinfos['logo'];

            $shop_customs = Db::name('shop_custom')->where('shop_id',$goods['shop_id'])->where('type',1)->field('info_id')->find();
            $remgoodres = array();

            if($shop_customs){
                $remgoodres = Db::name('goods')->where('id','in',$shop_customs['info_id'])->where('shop_id',$goods['shop_id'])->where('onsale',1)->field('id,goods_name,thumb_url,min_price,zs_price,leixing,shop_id')->order('zonghe_lv desc,id asc')->select();

                if($remgoodres){
                    foreach ($remgoodres as $k2 => $v2){
                        $remgoodres[$k2]['thumb_url'] = $webconfig['weburl'].'/'.$v2['thumb_url'];

                        $reruinfo = array('id'=>$v2['id'],'shop_id'=>$v2['shop_id']);
                        $regongyong = new CommonModel();
                        $reactivitys = $regongyong->getActivityInfo($reruinfo);

                        if($reactivitys){
                            if(!empty($reactivitys['goods_attr'])){
                                $regoods_attr_str = '';
                                $regares = Db::name('goods_attr')->alias('a')->field('a.attr_value,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$reactivitys['goods_attr'])->where('a.goods_id',$v2['id'])->where('b.attr_type',1)->select();
                                if($regares){
                                    foreach ($regares as $key2 => $val2){
                                        if($key2 == 0){
                                            $regoods_attr_str = $val2['attr_name'].':'.$val2['attr_value'];
                                        }else{
                                            $regoods_attr_str = $regoods_attr_str.' '.$val2['attr_name'].':'.$val2['attr_value'];
                                        }
                                    }
                                    $remgoodres[$k2]['goods_name'] = $v2['goods_name'].' '.$regoods_attr_str;
                                }
                            }

                            $remgoodres[$k2]['zs_price'] = $reactivitys['price'];
                        }else{
                            $remgoodres[$k2]['zs_price'] = $v2['min_price'];
                        }
                    }
                }
            }

            $goodinfores = array(
                'goodsinfo'=>$goodsinfo,
                'goods_attr'=>$goods_attr,
                'pronum'=>$pronum,
                'activity_info'=>$activity_info,
                'gpres'=>$gpres,
                'uniattr'=>$uniattr,
                'couponinfos'=>$couponinfos,
                'huodong'=>$huodong,
                'sertions'=>$sertions,
                'shopinfos'=>$shopinfos,
                'remgoodres'=>$remgoodres
            );
	        datamsg(200,'获取商品详情信息成功',$goodinfores);
        }else{
            datamsg(400,'参数错误',array('status'=>400));
        }
    }
}