<?php

namespace app\pc\controller;

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
class Index extends Common
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
        $data['seckill']['list'] = Seckill::getRecommendSeckill(5);
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
        $time = time();
        $where = "b.checked = 1 and b.is_recycle = 0 and a.start_time <= {$time} and a.end_time > {$time} and a.onsale = 1 and a.checked = 1 and a.is_recycle = 0";
        $couponres = Db::name('coupon')->alias('a')->join('sp_goods b', 'a.goods_id = b.id')->whereRaw($where)->field('a.id,a.man_price,a.dec_price,b.goods_name,b.thumb_url,a.start_time,a.end_time')->order('a.man_price asc')->limit(3)->select();
        $webconfig = $this->webconfig;

        foreach ($couponres as $k => $v) {
            $couponres[$k]['start_time'] = date('Y-m-d H:i:s', $v['start_time']);
            $couponres[$k]['end_time'] = date('Y-m-d H:i:s', $v['end_time']);
            $couponres[$k]['thumb_url'] = url_format($v['thumb_url'], $webconfig['weburl']);
        }
        datamsg(200, '获取优惠券信息成功', set_lang($couponres));
    }

    public function goodsHome()
    {
        $tags = ['is_recommend', 'is_hot', 'is_new', 'is_special'];
        $where = "is_recycle = 0";
        $tag = input('tag', '');
        if (in_array($tag, $tags)) {
            $where .= " and {$tag} = 1";
        }
        $num = input('num', 3);

        $goods = Db::name('goods')->whereRaw($where)->field('goods_name,thumb_url,zs_price')->order('id asc')->limit($num)->select();

        $webconfig = $this->webconfig;
        foreach ($goods as $key => $row) {
            $goods[$key]['thumb_url'] = url_format($row['thumb_url'], $webconfig['weburl']);

        }
        datamsg(200, '商品列表', set_lang($goods));

    }
}
