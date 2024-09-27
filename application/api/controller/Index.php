<?php
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use app\common\model\Upload;
use think\Db;
use app\api\model\Seckill;
use app\api\model\Assemble;
use app\api\model\Goods as GoodsModel;
use app\api\model\Live as LiveModel;
use app\api\model\Shops;

/**
 * @title 首页
 * @description 首页相关接口
 */
class Index extends Common{
    /**
     * @首页信息
     */
    public function indexInfo(){
        // 验证api_token
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){ // 400返回错误描述
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }

        $webconfig = $this->webconfig;

        $time = time();
        $dctime = date('Y-m-d',time());
        $tomtime = date('Y-m-d',time()+3600*24);
        $hdtime = '';
        $end_time = '';

        $sale_times = Db::name('sale_time')->order('time asc')->field('time')->select();
        $last_sale_time_index = count($sale_times) -1; // 最后一个时间段对应的索引值，从0开始
        if($sale_times){
            $rushtime = array();

            foreach ($sale_times as $k2 => $v2){
                if($v2['time'] < 10){
                    $dcthetime = strtotime($dctime.' 0'.$v2['time'].':00:00'); // 时间<10，前面加0修饰
                }else{
                    $dcthetime = strtotime($dctime.' '.$v2['time'].':00:00');
                }

                if(!empty($sale_times[$k2+1])){
                    if($sale_times[$k2+1]['time'] < 10){
                        $end_dcthetime = strtotime($dctime.' 0'.$sale_times[$k2+1]['time'].':00:00');
                    }else{
                        $end_dcthetime = strtotime($dctime.' '.$sale_times[$k2+1]['time'].':00:00');
                    }
                }else{
                    // 当为最后一个时
                    if($sale_times[0]['time'] < 10){
                        $end_dcthetime = strtotime($tomtime.' 0'.$sale_times[0]['time'].':00:00');
                    }else{
                        $end_dcthetime = strtotime($tomtime.' '.$sale_times[0]['time'].':00:00');
                    }
                }

                if($time >= $dcthetime){
                    $cuxiao = 1;
                }else{
                    $cuxiao = 0;
                }
                $rushtime[] = array('time'=>$dcthetime,'end_time'=>$end_dcthetime,'cuxiao'=>$cuxiao,'show'=>0);
            }

            if($rushtime){
                foreach ($rushtime as $key => $val){
                    if($time >= $val['time'] && $time < $val['end_time']){
                        $hdtime = $val['time'];
                        $end_time = $val['end_time'];
                        break;
                    }
                }
            }


            // 当不在秒杀时间段时，默认选在第一个时间段
            if(empty($hdtime) && empty($end_time)){
                $hdtime = $rushtime[0]['time'];
                $end_time = $rushtime[0]['end_time'];
            }
        }

        if(!empty($hdtime) && !empty($end_time)){
            $hdinfos = array('hdtime'=>$hdtime,'end_time'=>$end_time,'dqtime'=>time());
        }else{
            $hdinfos = array();
        }

        $indexinfos = array('hdinfos'=>$hdinfos);

	    datamsg(200,'获取信息成功',$indexinfos);
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
        $perpage = $webconfig['app_goodlst_num'];
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

    // 平台客服热线
    public function getServiceHotline(){
	    $tokenRes = $this->checkToken(0);
	    if($tokenRes['status'] == 400){ // 400返回错误描述
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }
        $serviceHotline = $this->webconfig['web_telephone'];
	    datamsg(200,'获取成功',array('serviceHotline'=>$serviceHotline));
    }

    // 平台客服热线
    public function getServiceUserToken(){
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){ // 400返回错误描述
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }
        $serviceUserId = Db::name('member')->where('shop_id',1)->value('id');
        $serviceUserToken = Db::name('member_token')->where('user_id',$serviceUserId)->value('token');
        datamsg(200,'获取成功',array('service_user_token'=>$serviceUserToken));
    }

    // 获取首页橱窗信息
    public function getIndexShowcase(){
        // 验证api_token
	    $tokenRes = $this->checkToken(0);
	    if($tokenRes['status'] == 400){ // 400返回错误描述
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }

        $goodsModel = new GoodsModel();
        $webconfig = $this->webconfig;

        // 秒杀
        $data['seckill']['title'] = '限时秒杀';
        $data['seckill']['slogan'] = '享惊喜折扣';
        $data['seckill']['tag'] = '秒杀Tag';
        $data['seckill']['tag_bg_color'] = '#ff4444';
        $data['seckill']['list'] = Seckill::getRecommendSeckill(4);
        foreach ($data['seckill']['list'] as $k=>$v){
            $data['seckill']['list'][$k]['thumb_url'] = url_format($v['thumb_url'],$webconfig['weburl']);
            $sekillPrice = $goodsModel->getGoodsShowPrice($v['goods_id'],'seckill','list');
            $data['seckill']['list'][$k]['price'] = $sekillPrice['seckill_price'];
            $data['seckill']['list'][$k]['shop_price'] = $sekillPrice['shop_price'];
        }

        // 拼团
        $data['assemble']['title'] = '人人拼团';
        $data['assemble']['slogan'] = '省钱省心';
        $data['assemble']['tag'] = '拼团Tag';
        $data['assemble']['tag_bg_color'] = '#ff5d7b';
        $data['assemble']['list'] = Assemble::getRecommendAssemble(10);
        foreach ($data['assemble']['list'] as $k=>$v){
            $data['assemble']['list'][$k]['thumb_url'] = url_format($v['thumb_url'],$webconfig['weburl']);
        }

        // 新品首发
        $data['new_goods']['title'] = '新品首发';
        $data['new_goods']['slogan'] = '美好新生活';
        $data['new_goods']['tag'] = '新品Tag';
        $data['new_goods']['tag_bg_color'] = '#ff703a';
        $data['new_goods']['list'] = $goodsModel->getTagGoods('is_new',10);
        foreach ($data['new_goods']['list'] as $k=>$v){
            $data['new_goods']['list'][$k]['thumb_url'] = url_format($v['thumb_url'],$webconfig['weburl']);
        }

        $data['live']['title'] = '热门直播';
        $data['live']['slogan'] = '享优惠福利';
        $data['live']['tag'] = '热门直播Tag';
        $data['live']['tag_bg_color'] = '#ff4444';
        $data['live']['is_open'] = (int)$webconfig['open_or_not'];
        $liveModel = new LiveModel();
        $data['live']['list'] = $liveModel->getRecommendLiveRoom(8);
        foreach ($data['live']['list'] as $k=>$v){
            $data['live']['list'][$k]['cover'] = url_format($v['cover'],$webconfig['weburl']);
        }
        datamsg(200, '获取成功', set_lang($data));
    }


    /**
     * 获取首页顶部导航列表
     */
    public function getIndexNavList(){
        cookie('think_var', 'en-us');
        $tokenRes = $this->checkToken(0);
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        }
        $list = db('category')->where(['pid'=>0,'is_show'=>1])->field('id,cate_name')->order('sort DESC')->select();
        $tuian[0] = ['id' => -1, 'cate_name' => '首页'];
        $list = array_merge($tuian, $list);

        datamsg(200, '首页顶部导航列表', set_lang($list));

    }

}
