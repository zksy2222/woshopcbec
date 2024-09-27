<?php

namespace app\api\controller;

use app\api\controller\Common;
use app\api\libs\paypal\Paypal;
use app\api\model\Common as CommonModel;
use app\api\model\GoodsSpec;
use app\api\model\Member as MemberModel;
use app\api\model\Goods as GoodsModel;
use app\api\model\GoodsOption as GoodsOptionModel;
use app\api\model\GoodsSpecItem as GoodsSpecItemModel;
use app\api\model\OrderGoods as OrderGoodsModel;
use app\api\model\Seckill as SeckillModel;
use app\api\model\IntegralShop as IntegralShopModel;
use app\api\model\Assemble as AssembleModel;
use app\api\model\Dispatch as DispatchModel;
use app\common\model\SmsCode as SmsCodeModel;
use EasyWeChat\Factory;
use think\Db;

class Order extends Common
{
    //购物车购买确认订单接口
    public function cartbuy()
    {
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }

        if (empty(input('post.cart_idres')) || is_array(input('post.cart_idres'))) {
            datamsg(400, '缺少购物车信息参数');
        }
        $cartIdRes = trim(input('post.cart_idres'));
        $cartIdRes = str_replace('，', ',', $cartIdRes);
        $cartIdRes = rtrim($cartIdRes, ',');

        if (!$cartIdRes) {
            datamsg(400, '购物车信息参数错误');
        }
        $cartIdRes = explode(',', $cartIdRes);
        $cartIdRes = array_unique($cartIdRes);

        if (!isset($cartIdRes) && !is_array($cartIdRes)) {
            datamsg(400, '购物车信息参数错误');
        }

        foreach ($cartIdRes as $v) {
            if (empty($v)) {
                datamsg(400, '购物车信息参数错误');
            }
            $carts = Db::name('cart')
                ->alias('a')
                ->field('a.*,b.goods_name,b.shop_price,b.thumb_url,c.shop_name')
                ->join('sp_goods b', 'a.goods_id = b.id', 'INNER')
                ->join('sp_shops c', 'a.shop_id = c.id', 'INNER')
                ->where('a.id', $v)
                ->where('a.user_id', $userId)
                ->where('b.onsale', 1)
                ->where('c.open_status', 1)
                ->find();
            if (!$carts) {
                datamsg(400, '购物车信息参数错误');
            }
            $ruinfo = array('id' => $carts['goods_id'], 'shop_id' => $carts['shop_id']);
            $ru_attr = $carts['goods_attr'];
            $commonModel = new CommonModel();
            $activity = $commonModel->getActivityInfo($ruinfo, $ru_attr);
            $goodsModel = new GoodsModel();
            if ($activity) {
                if ($activity['ac_type'] == 1) {
                    // 秒杀商品限购判断
                    $orderGoodsModel = new OrderGoodsModel();
                    $hasBuy = $orderGoodsModel->getUserOrderGoodsCount($userId, $carts['goods_id'], 'seckill');
                    $hasBuyStr = $hasBuy > 0 ? '，'.lang('您已经购买过') . $hasBuy . lang('件') : '';
                    if ($carts['num'] + $hasBuy > $activity['xznum']) {
                        datamsg(400, $carts['goods_name'] . '-'.lang('限购') . $activity['xznum'] . lang('件') . $hasBuyStr);
                    }

                    $stock = $goodsModel->getGoodsOptionStock($carts['goods_id'], $carts['goods_attr'], 'seckill');
                }
                if ($activity['ac_type'] == 2) {
                    datamsg(400, '积分换购商品不支持购物车提交订单');
                }
                if ($activity['ac_type'] == 3) {
                    datamsg(400, '存在拼团商品，确认订单失败');
                }
            } else {
                $stock = $goodsModel->getGoodsOptionStock($carts['goods_id'], $carts['goods_attr']);
            }

            if ($carts['num'] <= 0 || $carts['num'] > $stock) {
                if($activity['ac_type'] == 1){
                    $msg = lang('秒杀已抢完');
                }else{
                    $msg = '库存不足';
                }
                datamsg(400, $carts['goods_name'] . $msg);
            }
            unset($stock);
            unset($hasBuy);

        }

        $cartInfos = implode(',', $cartIdRes);
        datamsg(200, '操作成功', array('cart_idres' => $cartInfos));

    }

    //购物车购买确认订单详情接口
    public function cartsure()
    {

        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }

        $cartIdRes = trim(input('post.cart_idres'));
        if (empty($cartIdRes) || is_array($cartIdRes)) {
            datamsg(400, '缺少购物车信息参数');
        }

        $wallets = Db::name('wallet')->where('user_id', $userId)->find();

        $cartIdRes = str_replace('，', ',', $cartIdRes);
        $cartIdRes = rtrim($cartIdRes, ',');

        if (!$cartIdRes) {
            datamsg(400, '购物车信息参数错误');
        }
        $cartIdRes = explode(',', $cartIdRes);
        $cartIdRes = array_unique($cartIdRes);

        if (!$cartIdRes || !is_array($cartIdRes)) {
            datamsg(400, '购物车信息参数错误');
        }
        $zong_num = 0;
        $zsprice = 0;
        $goodsInfoRes = array();

        $webconfig = $this->webconfig;

        foreach ($cartIdRes as $v) {
            if (empty($v)) {
                datamsg(400, '购物车存在信息参数错误');
            }
            $carts = Db::name('cart')
                ->alias('a')
                ->field('a.*,b.goods_name,b.shop_price,b.thumb_url,b.is_send_free,c.shop_name')
                ->join('sp_goods b', 'a.goods_id = b.id', 'INNER')
                ->join('sp_shops c', 'a.shop_id = c.id', 'INNER')
                ->where('a.id', $v)
                ->where('a.user_id', $userId)
                ->where('b.onsale', 1)
                ->where('c.open_status', 1)
                ->find();
            if (!$carts) {
                datamsg(400, '购物车存在信息参数错误');
            }
            $carts['thumb_url'] = url_format($carts['thumb_url'], $webconfig['weburl']);

            $ruinfo = array('id' => $carts['goods_id'], 'shop_id' => $carts['shop_id']);
            $commonModel = new CommonModel();
            $activity = $commonModel->getActivityInfo($ruinfo);
            $goodsModel = new GoodsModel();
            if ($activity) {
                if ($activity['ac_type'] == 1) {
                    // 秒杀商品限购判断
                    $orderGoodsModel = new OrderGoodsModel();
                    $hasBuy = $orderGoodsModel->getUserOrderGoodsCount($userId, $carts['goods_id'], 'seckill');
                    $hasBuyStr = $hasBuy > 0 ? '，'.lang('您已经购买过') . $hasBuy . lang('件') : '';
                    if ($carts['num'] + $hasBuy > $activity['xznum']) {
                        datamsg(400, $carts['goods_name'] . '-'.lang('限购') . $activity['xznum'] . lang('件') . $hasBuyStr);
                    }
                    $stock = $goodsModel->getGoodsOptionStock($carts['goods_id'], $carts['goods_attr'], 'seckill');
                    $carts['shop_price'] = $goodsModel->getGoodsOptionPrice($carts['goods_id'], $carts['goods_attr'], 'seckill');
                    $carts['weight'] = $goodsModel->getGoodsOptionWeight($carts['goods_id'], $carts['goods_attr'], 'seckill');
                }
                if ($activity['ac_type'] == 2) {
                    datamsg(400, '积分换购商品不支持购物车提交订单');
                }
                if ($activity['ac_type'] == 3) {
                    datamsg(400, '拼团商品不支持购物车提交订单');
                }
            } else {
                $stock = $goodsModel->getGoodsOptionStock($carts['goods_id'], $carts['goods_attr']);
                $carts['shop_price'] = $goodsModel->getGoodsOptionPrice($carts['goods_id'], $carts['goods_attr']);
                $carts['weight'] = $goodsModel->getGoodsOptionWeight($carts['goods_id'], $carts['goods_attr'], 'normal');
            }

            if ($carts['num'] > 0 && $carts['num'] <= $stock) {
                if ($carts['goods_attr']) {
                    $goodsSpecItemModel = new GoodsSpecItemModel();
                    $goodsSpecItemIdArr = explode('_', $carts['goods_attr']);
                    $specItemInfo = $goodsSpecItemModel->getGoodsSpecAndSpecItemInfo($goodsSpecItemIdArr);
                    unset($goodsSpecItemIdArr);
                    $goods_attr_str = '';
                    foreach ($specItemInfo as $key => $val) {
                        $str = $key == count($specItemInfo) - 1 ? '' : ';';
                        $goods_attr_str .= $val->goodsSpec->title . ':' . $val->title . $str;
                    }
                } else {
                    $goods_attr_str = '';
                }

                $goodsInfoRes[] = array(
                    'id'             => $carts['goods_id'],
                    'goods_name'     => $carts['goods_name'],
                    'thumb_url'      => $carts['thumb_url'],
                    'goods_attr_str' => $goods_attr_str,
                    'shop_price'     => $carts['shop_price'],
                    'goods_num'      => $carts['num'],
                    'is_send_free'   => $carts['is_send_free'],
                    'shop_id'        => $carts['shop_id'],
                    'shop_name'      => $carts['shop_name'],
                    'weight'         => $carts['weight'],
                );
            } else {
                if($activity['ac_type'] == 1){
                    $msg = lang('秒杀已抢完');
                }else{
                    $msg = lang('库存不足');
                }
                datamsg(400, $carts['goods_name'] . $msg);
            }
        }

        $addressId = input('post.address_id');
        if (!empty($addressId)) {
            $addressWhere['id'] = $addressId;
        } else {
            $addressWhere['is_default'] = 1;
        }
        $address = Db::name('address')
            ->alias('a')
            ->field('id,contacts,phone,address,province,city,area,datavalue')
            ->where('user_id', $userId)
            ->where($addressWhere)
            ->find();
        if (!$address) {
            $address = '';
        }

        $dispatchModel = new DispatchModel();
        $dispatchPriceData = $dispatchModel->getOrderDispatchPrice($goodsInfoRes, $address);

        if (!$goodsInfoRes) {
            datamsg(400, '商品信息参数错误');
        }

        $hqGoodsInfos = array();
        foreach ($goodsInfoRes as $kd => $vd) {
            $hqGoodsInfos[$vd['shop_id']]['goodres'][] = $vd;
        }

        if (!$hqGoodsInfos) {
            datamsg(400, '商品信息参数错误');
        }

        foreach ($hqGoodsInfos as $kc => $vc) {
            $hqGoodsInfos[$kc]['coupon_str'] = '';
            $hqGoodsInfos[$kc]['cxhuodong'] = array();
            $hqGoodsInfos[$kc]['youhui_price'] = 0;
            $hqGoodsInfos[$kc]['xiaoji_price'] = 0;

            $xiaoji = 0;
            $shopGoodsNum = 0;

            foreach ($vc['goodres'] as $vp) {
                $xiaoji += sprintf("%.2f", $vp['shop_price'] * $vp['goods_num']);
                $shopGoodsNum += $vp['goods_num'];
            }

            $coupons = Db::name('coupon')
                ->where('shop_id', $kc)
                ->where('start_time', 'elt', time())
                ->where('end_time', 'gt', time() - 3600 * 24)
                ->where('onsale', 1)
                ->field('id,man_price,dec_price')
                ->order('man_price asc')
                ->find();
            if ($coupons) {
                $couinfos = Db::name('member_coupon')
                    ->alias('a')
                    ->field('a.*,b.man_price,b.dec_price')
                    ->join('sp_coupon b', 'a.coupon_id = b.id', 'INNER')
                    ->where('a.user_id', $userId)
                    ->where('a.is_sy', 0)
                    ->where('a.shop_id', $kc)
                    ->where('b.start_time', 'elt', time())
                    ->where('b.end_time', 'gt', time() - 3600 * 24)
                    ->where('b.onsale', 1)
                    ->where('b.man_price', 'elt', $xiaoji)
                    ->order('b.man_price desc')
                    ->find();

                if ($couinfos) {
                    $hqGoodsInfos[$kc]['youhui_price'] += $couinfos['dec_price'];
                    $hqGoodsInfos[$kc]['coupon_str'] = lang('满') . $couinfos['man_price'] . lang('减') . $couinfos['dec_price'] . lang('已优惠') . $couinfos['dec_price'];
                }
            }

            $promotionRes = Db::name('promotion')
                ->where('shop_id', $kc)
                ->where('is_show', 1)
                ->where('start_time', 'elt', time())
                ->where('end_time', 'gt', time())
                ->field('id,start_time,end_time,info_id')
                ->select();

            if ($promotionRes) {
                foreach ($promotionRes as $prv) {
                    $promTypeRes = Db::name('prom_type')->where('prom_id', $prv['id'])->select();
                    if ($promTypeRes) {
                        $promNum = 0;
                        $cuxiaogoods = array();
                        $prohdsort = array();

                        foreach ($vc['goodres'] as $vp) {
                            if (strpos(',' . $prv['info_id'] . ',', ',' . $vp['id'] . ',') !== false) {
                                $promNum += $vp['goods_num'];
                                $cuxiaogoods[] = array('id' => $vp['id'], 'shop_price' => $vp['shop_price'], 'goods_num' => $vp['goods_num']);
                            }
                        }

                        if ($promNum) {
                            foreach ($promTypeRes as $krp => $vrp) {
                                if ($promNum && $promNum >= $vrp['man_num']) {
                                    $prohdsort[] = $vrp;
                                }
                            }

                            if ($prohdsort) {
                                $prohdsort = array_sort($prohdsort, 'man_num');
                                $promhdinfo = $prohdsort[0];
                                $cxcd_price = 0;

                                $zhekou = $promhdinfo['discount'] / 100;
                                foreach ($cuxiaogoods as $cx) {
                                    $zhekouprice = sprintf("%.2f", $cx['shop_price'] * $zhekou);
                                    $youhui_price = ($cx['shop_price'] - $zhekouprice) * $cx['goods_num'];
                                    $hqGoodsInfos[$kc]['youhui_price'] += sprintf("%.2f", $youhui_price);
                                    $cxcd_price += sprintf("%.2f", $youhui_price);
                                }

                                $cxcd_price = sprintf("%.2f", $cxcd_price);
                                $zhe = $promhdinfo['discount'] / 10;
                                $hqGoodsInfos[$kc]['cxhuodong'][] = lang('部分商品满') . $promhdinfo['man_num'] . lang('件') . $zhe . lang('折').lang('已优惠') . $cxcd_price;
                            }
                        }
                    }
                }
            }


            $hqGoodsInfos[$kc]['youhui_price'] = sprintf("%.2f", $hqGoodsInfos[$kc]['youhui_price']);

            $hqGoodsInfos[$kc]['shopgoods_num'] = $shopGoodsNum;

            $hqGoodsInfos[$kc]['xiaoji_price'] = sprintf("%.2f", $xiaoji - $hqGoodsInfos[$kc]['youhui_price']);

            $hqGoodsInfos[$kc]['freight'] = $dispatchPriceData['dispatch_shop'][$kc];
            $hqGoodsInfos[$kc]['xiaoji_price'] = sprintf("%.2f", $hqGoodsInfos[$kc]['xiaoji_price'] + $dispatchPriceData['dispatch_shop'][$kc]);
            $hqGoodsInfos[$kc]['freight_str'] = lang('普通配送');

            $zong_num += $hqGoodsInfos[$kc]['shopgoods_num'];

            $zsprice += $hqGoodsInfos[$kc]['xiaoji_price'];
        }

        $hqGoodsInfos = array_values($hqGoodsInfos);

        $zsprice = sprintf("%.2f", $zsprice);

        $cartInfos = implode(',', $cartIdRes);

        $returnData = array(
            'goodinfo'            => $hqGoodsInfos,
            'zong_num'            => $zong_num,
            'zsprice'             => $zsprice,
            'address'             => $address,
            'wallet_price'        => $wallets['price'],
            'cart_idres'          => $cartInfos,
            'dispatch_price_data' => $dispatchPriceData
        );
        datamsg(200, '获取商品信息成功', $returnData);

    }

    //立即购买确认订单接口
    public function purbuy()
    {

        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }

        if (!input('post.goods_id') || !input('post.num')) {
            datamsg(400, '缺少购买商品参数');
        }
        if (!input('post.fangshi') || !in_array(input('post.fangshi'), array(1, 2))) {
            datamsg(400, '缺少购买方式参数');
        }

        $goodsId = input('post.goods_id');
        $num = input('post.num');
        $fangshi = input('post.fangshi');
        $assem_number = '';

        if (!preg_match("/^\\+?[1-9][0-9]*$/", $num)) {
            datamsg(400, '商品数量参数格式错误');
        }

        $goods = Db::name('goods')
            ->alias('a')
            ->field('a.id,a.goods_name,a.hasoption,a.shop_id')
            ->join('sp_shops b', 'a.shop_id = b.id', 'INNER')
            ->where('a.id', $goodsId)
            ->where('a.onsale', 1)
            ->where('b.open_status', 1)
            ->find();
        if (!$goods) {
            datamsg(400, '商品已下架或不存在');
        }

        $goods_attr = input('post.goods_attr');
        $goodsSpecItemIdArr = explode('_', $goods_attr);
        if ($goods['hasoption']) {
//            $goodsSpecModel = new GoodsSpecModel();
            $goodsSpecModel = new GoodsSpec();
            $checkSpec = $goodsSpecModel->checkGoodsSpec($goodsId, $goodsSpecItemIdArr);
            if ($checkSpec['status'] == 400) {
                datamsg(400, $checkSpec['mess']);
            }
        }

        $ruinfo = array('id' => $goods['id'], 'shop_id' => $goods['shop_id']);
        $ru_attr = $goods_attr;

        $commonModel = new CommonModel();
        $goodsModel = new GoodsModel();
        $orderGoodsModel = new OrderGoodsModel();
        $activity = $commonModel->getActivityInfo($ruinfo, $ru_attr);

        if ((!$activity) || ($activity && $activity['ac_type'] == 3 && $fangshi == 1)) { // 非活动商品或拼团商品的单独购买
            $stock = $goodsModel->getGoodsOptionStock($goods['id'], $goods_attr);
        } else {
            if ($activity['ac_type'] == 1) {
                $hasBuy = $orderGoodsModel->getUserOrderGoodsCount($userId, $goods['id'], 'seckill');
                $hasBuyStr = $hasBuy > 0 ? '，'.lang('您已经购买过') . $hasBuy . lang('件') : '';
                if ($num + $hasBuy > $activity['xznum']) {
                    datamsg(400, lang('该商品限购') . $activity['xznum'] . lang('该商品限购') . $hasBuyStr);
                }

                $stock = $goodsModel->getGoodsOptionStock($goods['id'], $goods_attr, 'seckill');
            }

            if ($activity['ac_type'] == 2) {
                $hasBuy = $orderGoodsModel->getUserOrderGoodsCount($userId, $goods['id'], 'integral');
                $hasBuyStr = $hasBuy > 0 ? '，'.lang('您已经购买过') . $hasBuy . lang('件') : '';
                if ($num + $hasBuy > $activity['xznum']) {
                    datamsg(400, lang('该商品限购') . $activity['xznum'] . lang('该商品限购') . $hasBuyStr);
                }

                $stock = $goodsModel->getGoodsOptionStock($goods['id'], $goods_attr, 'integral');
            }

            if ($activity['ac_type'] == 3 && $fangshi == 2) {
                $stock = $goodsModel->getGoodsOptionStock($goods['id'], $goods_attr, 'assemble');
            }

            if ($num > 0 && $num <= $stock) {
                if ($activity['ac_type'] == 3) {
                    $assem_type = 1;
                    $zhuangtai = 0;

                    if (input('post.pin_number')) {
                        $assem_number = input('post.pin_number');
                        $pintuans = Db::name('pintuan')
                            ->where('assem_number', $assem_number)
                            ->where('state', 1)
                            ->where('pin_status', 'in', '0,1')
                            ->where('hd_id', $activity['id'])
                            ->find();
                        if ($pintuans) {
                            $order_assembles = Db::name('order_assemble')
                                ->where('pin_id', $pintuans['id'])
                                ->where('user_id', $userId)
                                ->where('state', 1)
                                ->where('tui_status', 0)
                                ->find();
                            if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                if ($order_assembles) {
                                    $assem_type = 3;
                                    $zhuangtai = 1;
                                } else {
                                    $assem_type = 2;
                                }
                            } elseif ($pintuans['pin_status'] == 1) {
                                if ($order_assembles) {
                                    $zhuangtai = 2;
                                }
                            }
                        } else {
                            $order_assembles = Db::name('order_assemble')
                                ->where('user_id', $userId)
                                ->where('goods_id', $goods['id'])
                                ->where('shop_id', $goods['shop_id'])
                                ->where('hd_id', $activity['id'])
                                ->where('state', 1)
                                ->where('tui_status', 0)
                                ->order('addtime desc')
                                ->find();
                            if ($order_assembles) {
                                $pintuans = Db::name('pintuan')
                                    ->where('id', $order_assembles['pin_id'])
                                    ->where('state', 1)
                                    ->where('pin_status', 'in', '0,1')
                                    ->where('hd_id', $activity['id'])
                                    ->find();
                                if ($pintuans) {
                                    if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                        $assem_type = 3;
                                        $zhuangtai = 1;
                                    } elseif ($pintuans['pin_status'] == 1) {
                                        $zhuangtai = 2;
                                    }
                                }
                            }
                        }
                    } else {
                        $order_assembles = Db::name('order_assemble')
                            ->where('user_id', $userId)
                            ->where('goods_id', $goods['id'])
                            ->where('shop_id', $goods['shop_id'])
                            ->where('hd_id', $activity['id'])
                            ->where('state', 1)
                            ->where('tui_status', 0)
                            ->order('addtime desc')
                            ->find();
                        if ($order_assembles) {
                            $pintuans = Db::name('pintuan')
                                ->where('id', $order_assembles['pin_id'])
                                ->where('state', 1)
                                ->where('pin_status', 'in', '0,1')
                                ->where('hd_id', $activity['id'])
                                ->find();
                            if ($pintuans) {
                                if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                    $assem_type = 3;
                                    $zhuangtai = 1;
                                } elseif ($pintuans['pin_status'] == 1) {
                                    $zhuangtai = 2;
                                }
                            }
                        }
                    }

                    if ($assem_type == 3) {
                        datamsg(400, '您已参与商品拼团，下单失败');
                    }
                }
            }
        }

        if ($num <= 0 || $num > $stock) {
            if($activity['ac_type'] == 1){
                $msg = lang('秒杀已抢完');
            }else{
                $msg = lang('库存不足');
            }
            datamsg(400,  $msg);
        }

        $purchs = Db::name('purch')->where('user_id', $userId)->find();
        if ($purchs) {
            $count = Db::name('purch')
                ->where('id', $purchs['id'])
                ->where('user_id', $userId)
                ->update(array(
                    'goods_id'   => $goods['id'],
                    'goods_attr' => $goods_attr,
                    'num'        => $num,
                    'shop_id'    => $goods['shop_id']
                ));
            if ($count !== false) {
                datamsg(200, '操作成功', array('pur_id' => $purchs['id'], 'fangshi' => $fangshi, 'pin_number' => $assem_number));
            } else {
                datamsg(400, '操作失败，请重试');
            }
        } else {
            $pur_id = Db::name('purch')->insertGetId(array(
                'goods_id'   => $goods['id'],
                'goods_attr' => $goods_attr,
                'num'        => $num,
                'user_id'    => $userId,
                'shop_id'    => $goods['shop_id']
            ));
            if ($pur_id) {
                datamsg(200, '操作成功', array('pur_id' => $pur_id, 'fangshi' => $fangshi, 'pin_number' => $assem_number));
            } else {
                datamsg(400, '操作失败，请重试');
            }
        }
    }

    //立即购买确认订单详情接口
    public function pursure()
    {
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }

        if (!input('post.pur_id')) {
            datamsg(400, '缺少购买商品参数');
        }
        if (!input('post.fangshi') || !in_array(input('post.fangshi'), array(1, 2))) {
            datamsg(400, '缺少购买方式参数');
        }
        $webconfig = $this->webconfig;

        $wallets = Db::name('wallet')->where('user_id', $userId)->find();
        $pur_id = input('post.pur_id');
        $fangshi = input('post.fangshi');
        $assem_number = '';

        $purchs = Db::name('purch')
            ->alias('a')
            ->field('a.*,b.goods_name,b.thumb_url,b.shop_price,b.is_send_free,c.shop_name')
            ->join('sp_goods b', 'a.goods_id = b.id', 'INNER')
            ->join('sp_shops c', 'a.shop_id = c.id', 'INNER')
            ->where('a.id', $pur_id)
            ->where('a.user_id', $userId)
            ->where('b.onsale', 1)
            ->where('c.open_status', 1)
            ->find();
        if (!$purchs) {
            datamsg(400, '找不到相关商品信息');
        }

        $goodinfos = array();

        $purchs['thumb_url'] = url_format($purchs['thumb_url'], $webconfig['weburl']);

        $ruinfo = array('id' => $purchs['goods_id'], 'shop_id' => $purchs['shop_id']);
        $ru_attr = $purchs['goods_attr'];

        $commonModel = new CommonModel();
        $activity = $commonModel->getActivityInfo($ruinfo, $ru_attr);
        $goodsModel = new GoodsModel();
        $goodsSpecItemModel = new GoodsSpecItemModel();

        if ((!$activity) || ($activity && $activity['ac_type'] == 3 && $fangshi == 1)) { // 非活动商品或拼团活动的单独购买
            $stock = $goodsModel->getGoodsOptionStock($purchs['goods_id'], $purchs['goods_attr']);
            $weight = $goodsModel->getGoodsOptionWeight($purchs['goods_id'], $purchs['goods_attr']);

            if ($purchs['num'] > 0 && $purchs['num'] <= $stock) {

                if (!empty($purchs['goods_attr'])) {
                    $goods_attr_str = '';
                    $specItemIdArr = explode('_', $purchs['goods_attr']);
                    $specItemInfo = $goodsSpecItemModel->getGoodsSpecAndSpecItemInfo($specItemIdArr);
                    foreach ($specItemInfo as $k => $v) {
                        $str = $k == count($specItemInfo) - 1 ? '' : ';';
                        $goods_attr_str .= $v->goodsSpec->title . ':' . $v->title . $str;
                    }
                    $purchs['shop_price'] = $goodsModel->getGoodsOptionPrice($purchs['goods_id'], $purchs['goods_attr']);
                } else {
                    $goods_attr_str = '';
                }


                $goodinfos = array(
                    'id'             => $purchs['goods_id'],
                    'goods_name'     => $purchs['goods_name'],
                    'thumb_url'      => $purchs['thumb_url'],
                    'goods_attr_str' => $goods_attr_str,
                    'shop_price'     => $purchs['shop_price'],
                    'goods_num'      => $purchs['num'],
                    'is_send_free'   => $purchs['is_send_free'],
                    'shop_id'        => $purchs['shop_id'],
                    'shop_name'      => $purchs['shop_name'],
                    'weight'         => $weight
                );

            } else {
                datamsg(400, '库存不足');
            }
        } else {
            if ($activity['ac_type'] == 1) {
                $stock = $goodsModel->getGoodsOptionStock($purchs['goods_id'], $purchs['goods_attr'], 'seckill');
                $weight = $goodsModel->getGoodsOptionWeight($purchs['goods_id'], $purchs['goods_attr'], 'seckill');
                $purchs['shop_price'] = $goodsModel->getGoodsOptionPrice($purchs['goods_id'], $purchs['goods_attr'], 'seckill');
                // 秒杀商品限购判断
                $orderGoodsModel = new OrderGoodsModel();
                $hasBuy = $orderGoodsModel->getUserOrderGoodsCount($userId, $purchs['goods_id'], 'seckill');
                $hasBuyStr = $hasBuy > 0 ? '，'.lang('您已经购买过') . $hasBuy . lang('件') : '';
                if ($purchs['num'] + $hasBuy > $activity['xznum']) {
                    datamsg(400, lang('该商品限购') . $activity['xznum'] . lang('件') . $hasBuyStr);
                }
            }

            if ($activity['ac_type'] == 2) {
                $stock = $goodsModel->getGoodsOptionStock($purchs['goods_id'], $purchs['goods_attr'], 'integral');
                $weight = $goodsModel->getGoodsOptionWeight($purchs['goods_id'], $purchs['goods_attr'], 'integral');
                $purchs['shop_price'] = $goodsModel->getGoodsOptionPrice($purchs['goods_id'], $purchs['goods_attr'], 'integral');
                // 积分商品限购判断

                $orderGoodsModel = new OrderGoodsModel();
                $hasBuy = $orderGoodsModel->getUserOrderGoodsCount($userId, $purchs['goods_id'], 'integral');
                $hasBuyStr = $hasBuy > 0 ?  '，'.lang('您已经购买过') . $hasBuy . lang('件') : '';
                if ($purchs['num'] + $hasBuy > $activity['xznum']) {
                    datamsg(400, lang('该商品限购') . $activity['xznum'] . lang('件') . $hasBuyStr);
                }
            }

            if ($activity['ac_type'] == 3 && $fangshi == 2) {
                $stock = $goodsModel->getGoodsOptionStock($purchs['goods_id'], $purchs['goods_attr'], 'assemble');
                $weight = $goodsModel->getGoodsOptionWeight($purchs['goods_id'], $purchs['goods_attr'], 'assemble');
                $purchs['shop_price'] = $goodsModel->getGoodsOptionPrice($purchs['goods_id'], $purchs['goods_attr'], 'assemble');
            }

            if ($purchs['num'] > 0 && $purchs['num'] <= $stock) {
                if (!empty($purchs['goods_attr'])) {
                    $goods_attr_str = '';
                    $specItemIdArr = explode('_', $purchs['goods_attr']);
                    $specItemInfo = $goodsSpecItemModel->getGoodsSpecAndSpecItemInfo($specItemIdArr);
                    foreach ($specItemInfo as $k => $v) {
                        $str = $k == count($specItemInfo) - 1 ? '' : ';';
                        $goods_attr_str .= $v->goodsSpec->title . ':' . $v->title . $str;
                    }
                } else {
                    $goods_attr_str = '';
                }


                if ($activity['ac_type'] == 3) {
                    $assem_type = 1;
                    $zhuangtai = 0;

                    if (input('post.pin_number')) {
                        $assem_number = input('post.pin_number');
                        $pintuans = Db::name('pintuan')
                            ->where('assem_number', $assem_number)
                            ->where('state', 1)
                            ->where('pin_status', 'in', '0,1')
                            ->where('hd_id', $activity['id'])
                            ->find();
                        if ($pintuans) {
                            $order_assembles = Db::name('order_assemble')
                                ->where('pin_id', $pintuans['id'])
                                ->where('user_id', $userId)
                                ->where('state', 1)
                                ->where('tui_status', 0)
                                ->find();
                            if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                if ($order_assembles) {
                                    $assem_type = 3;
                                    $zhuangtai = 1;
                                } else {
                                    $assem_type = 2;
                                }
                            } elseif ($pintuans['pin_status'] == 1) {
                                if ($order_assembles) {
                                    $zhuangtai = 2;
                                }
                            }
                        } else {
                            $order_assembles = Db::name('order_assemble')
                                ->where('user_id', $userId)
                                ->where('goods_id', $purchs['goods_id'])
                                ->where('shop_id', $purchs['shop_id'])
                                ->where('hd_id', $activity['id'])
                                ->where('state', 1)
                                ->where('tui_status', 0)
                                ->order('addtime desc')
                                ->find();
                            if ($order_assembles) {
                                $pintuans = Db::name('pintuan')
                                    ->where('id', $order_assembles['pin_id'])
                                    ->where('state', 1)
                                    ->where('pin_status', 'in', '0,1')
                                    ->where('hd_id', $activity['id'])
                                    ->find();
                                if ($pintuans) {
                                    if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                        $assem_type = 3;
                                        $zhuangtai = 1;
                                    } elseif ($pintuans['pin_status'] == 1) {
                                        $zhuangtai = 2;
                                    }
                                }
                            }
                        }
                    } else {
                        $order_assembles = Db::name('order_assemble')
                            ->where('user_id', $userId)
                            ->where('goods_id', $purchs['goods_id'])
                            ->where('shop_id', $purchs['shop_id'])
                            ->where('hd_id', $activity['id'])
                            ->where('state', 1)
                            ->where('tui_status', 0)
                            ->order('addtime desc')
                            ->find();
                        if ($order_assembles) {
                            $pintuans = Db::name('pintuan')
                                ->where('id', $order_assembles['pin_id'])
                                ->where('state', 1)
                                ->where('pin_status', 'in', '0,1')
                                ->where('hd_id', $activity['id'])
                                ->find();
                            if ($pintuans) {
                                if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                    $assem_type = 3;
                                    $zhuangtai = 1;
                                } elseif ($pintuans['pin_status'] == 1) {
                                    $zhuangtai = 2;
                                }
                            }
                        }
                    }

                    if ($assem_type == 3) {
                        datamsg(400, '您已参与商品拼团，下单失败');
                    }
                }

                $goodinfos = array(
                    'id'             => $purchs['goods_id'],
                    'goods_name'     => $purchs['goods_name'],
                    'thumb_url'      => $purchs['thumb_url'],
                    'goods_attr_str' => $goods_attr_str,
                    'shop_price'     => $purchs['shop_price'],
                    'goods_num'      => $purchs['num'],
                    'is_send_free'   => $purchs['is_send_free'],
                    'shop_id'        => $purchs['shop_id'],
                    'shop_name'      => $purchs['shop_name'],
                    'weight'         => $weight
                );
            } else {
                if($activity['ac_type'] == 1){
                    $msg = lang('秒杀已抢完');
                }else{
                    $msg = lang('库存不足');
                }
                datamsg(400, $purchs['goods_name'] . $msg);
            }
        }

        $ordouts = Db::name('order_timeout')->where('id', 1)->find();

        if ($activity && $activity['ac_type'] == 3 && $fangshi == 2) {
            $assem_zt = array('is_show' => 1, 'time_out' => $ordouts['assem_timeout']);
        } else {
            $assem_zt = array('is_show' => 0, 'time_out' => '');
        }

        if ($goodinfos) {
            $goodinfos['coupon_str'] = '';
            $goodinfos['cxhuodong'] = array();
            $goodinfos['youhui_price'] = 0;
//            $goodinfos['freight'] = 0;
            $goodinfos['xiaoji_price'] = 0;

            $integral = "";
            if($activity['ac_type'] == 2){
                $xiaoji = sprintf("%.2f", $goodinfos['shop_price']['price'] * $goodinfos['goods_num']);
                $integral = $goodinfos['shop_price']['integral'] * $goodinfos['goods_num'];
            }else{
                $xiaoji = sprintf("%.2f", $goodinfos['shop_price'] * $goodinfos['goods_num']);
            }


            if ((!$activity) || (in_array($activity['ac_type'], array(1, 2))) || ($activity['ac_type'] == 3 && $fangshi == 1)) {
                $coupons = Db::name('coupon')
                    ->where('shop_id', $goodinfos['shop_id'])
                    ->where('start_time', 'elt', time())
                    ->where('end_time', 'gt', time() - 3600 * 24)
                    ->where('onsale', 1)
                    ->field('id,man_price,dec_price')
                    ->order('man_price asc')
                    ->find();
                if ($coupons) {
                    $couinfos = Db::name('member_coupon')
                        ->alias('a')
                        ->field('a.*,b.man_price,b.dec_price')
                        ->join('sp_coupon b', 'a.coupon_id = b.id', 'INNER')
                        ->where('a.user_id', $userId)
                        ->where('a.is_sy', 0)
                        ->where('a.shop_id', $goodinfos['shop_id'])
                        ->where('b.start_time', 'elt', time())
                        ->where('b.end_time', 'gt', time() - 3600 * 24)
                        ->where('b.onsale', 1)
                        ->where('b.man_price', 'elt', $xiaoji)
                        ->order('b.man_price desc')
                        ->find();

                    if ($couinfos) {
                        $goodinfos['youhui_price'] += $couinfos['dec_price'];
                        $goodinfos['coupon_str'] = '满' . $couinfos['man_price'] . '减' . $couinfos['dec_price'] . '  已优惠' . $couinfos['dec_price'];
                    }
                }

                $promotionRes = Db::name('promotion')
                    ->where('shop_id', $goodinfos['shop_id'])
                    ->where('is_show', 1)
                    ->where('start_time', 'elt', time())
                    ->where('end_time', 'gt', time())
                    ->field('id,start_time,end_time,info_id')
                    ->select();
                if ($promotionRes) {
                    foreach ($promotionRes as $prv) {
                        $promTypeRes = Db::name('prom_type')->where('prom_id', $prv['id'])->select();
                        if ($promTypeRes) {
                            $prohdsort = array();

                            if (strpos(',' . $prv['info_id'] . ',', ',' . $goodinfos['id'] . ',') !== false) {
                                foreach ($promTypeRes as $krp => $vrp) {
                                    if ($goodinfos['goods_num'] && $goodinfos['goods_num'] >= $vrp['man_num']) {
                                        $prohdsort[] = $vrp;
                                    }
                                }

                                if ($prohdsort) {
                                    $prohdsort = array_sort($prohdsort, 'man_num');
                                    $promhdinfo = $prohdsort[0];

                                    $zhekou = $promhdinfo['discount'] / 100;
                                    $zhekouprice = sprintf("%.2f", $goodinfos['shop_price'] * $zhekou);
                                    $youhui_price = ($goodinfos['shop_price'] - $zhekouprice) * $goodinfos['goods_num'];
                                    $youhui_price = sprintf("%.2f", $youhui_price);
                                    $goodinfos['youhui_price'] += $youhui_price;

                                    $zhe = $promhdinfo['discount'] / 10;
                                    $goodinfos['cxhuodong'][] = lang('部分商品满') . $promhdinfo['man_num'] . lang('件') . $zhe . lang('折').lang('已优惠') . $youhui_price;
                                }
                                break;
                            }
                        }
                    }
                }
            }

            $goodinfos['youhui_price'] = sprintf("%.2f", $goodinfos['youhui_price']);

            $goodinfos['xiaoji_price'] = sprintf("%.2f", $xiaoji - $goodinfos['youhui_price']);

            $goodinfos['freight_str'] = lang('普通配送');

            $zong_num = $goodinfos['goods_num'];

            $zsprice = $goodinfos['xiaoji_price'];

            $goodsInfoRes = array();
            $hqGoodsInfos = array();

            $goodsInfoRes[] = $goodinfos;

            $addressId = input('post.address_id');
            if (!empty($addressId)) {
                $addressWhere['id'] = $addressId;
            } else {
                $addressWhere['is_default'] = 1;
            }
            $address = Db::name('address')
                ->alias('a')
                ->field('id,contacts,phone,address,province,city,area,datavalue')
                ->where('user_id', $userId)
                ->where($addressWhere)
                ->find();
            if (!$address) {
                $address = '';
            }

            $dispatchModel = new DispatchModel();
            $dispatchPriceData = $dispatchModel->getOrderDispatchPrice($goodsInfoRes, $address);

            $zsprice = sprintf("%.2f", $goodinfos['xiaoji_price'] + $dispatchPriceData['dispatch_shop'][$goodinfos['shop_id']]);

            foreach ($goodsInfoRes as $kd => $vd) {
                $hqGoodsInfos[$vd['shop_id']]['goodres'][] = array('id' => $vd['id'], 'goods_name' => $vd['goods_name'], 'thumb_url' => $vd['thumb_url'], 'goods_attr_str' => $vd['goods_attr_str'], 'shop_price' => $vd['shop_price'], 'goods_num' => $vd['goods_num'], 'is_send_free' => $vd['is_send_free'], 'shop_id' => $vd['shop_id'], 'shop_name' => $vd['shop_name']);
                $hqGoodsInfos[$vd['shop_id']]['coupon_str'] = $vd['coupon_str'];
                $hqGoodsInfos[$vd['shop_id']]['cxhuodong'] = $vd['cxhuodong'];
                $hqGoodsInfos[$vd['shop_id']]['youhui_price'] = $vd['youhui_price'];
                $hqGoodsInfos[$vd['shop_id']]['freight'] = $dispatchPriceData['dispatch_shop'][$vd['shop_id']];
                $hqGoodsInfos[$vd['shop_id']]['shopgoods_num'] = $vd['goods_num'];
                $hqGoodsInfos[$vd['shop_id']]['xiaoji_price'] = sprintf("%.2f", $vd['xiaoji_price'] + $dispatchPriceData['dispatch_shop'][$vd['shop_id']]);
            }

            $hqGoodsInfos = array_values($hqGoodsInfos);

            $returnData = array(
                'goodinfo'            => $hqGoodsInfos,
                'zong_num'            => $zong_num,
                'zsprice'             => $zsprice,
                'address'             => $address,
                'wallet_price'        => $wallets['price'],
                'pur_id'              => $pur_id,
                'assem_zt'            => $assem_zt,
                'fangshi'             => $fangshi,
                'pin_number'          => $assem_number,
                'dispatch_price_data' => $dispatchPriceData,
                'ac_type'             => $activity['ac_type'],
                'integral'            => $integral
            );
            datamsg(200, '获取商品信息成功', $returnData);
        } else {
            datamsg(400, '找不到相关商品信息');
        }
    }

    //判断支付密码设置与否
    public function pdpaypwd()
    {
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }

        $zhifupwd = Db::name('member')->where('id', $userId)->value('paypwd');
        if ($zhifupwd) {
            $zhifupwd = 1;
        } else {
            $zhifupwd = 0;
        }
        datamsg(200, '获取支付密码设置与否状态信息成功', array('zhifupwd' => $zhifupwd));
    }

    //购物车购买创建订单接口
    public function addorder()
    {
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }

        if (!input('post.cart_idres') || is_array(input('post.cart_idres'))) {
            datamsg(400, '缺少购物车信息参数');
        }
        if (!input('post.dz_id')) {
            datamsg(400, '缺少地址信息');
        }

        $zf_type = input('post.zf_type');

        $address = Db::name('address')
            ->alias('a')
            ->field('id,contacts,phone,address,province,city,area,datavalue,pro_id,city_id,area_id')
            ->where('user_id', $userId)
            ->where('id',input('post.dz_id'))
            ->find();
        if (!$address) {
            datamsg(400, '地址信息错误');
        }
        $cartIdRes = trim(input('post.cart_idres'));
        $cartIdRes = str_replace('，', ',', $cartIdRes);
        $cartIdRes = rtrim($cartIdRes, ',');

        if (!$cartIdRes) {
            datamsg(400, '购物车信息参数错误');
        }
        $cartIdRes = explode(',', $cartIdRes);
        $cartIdRes = array_unique($cartIdRes);

        if (!$cartIdRes || !is_array($cartIdRes)) {
            datamsg(400, '购物车信息参数错误');
        }
        $total_price = 0;

        $goodsInfoRes = array();
        $goodsModel = new GoodsModel();
        foreach ($cartIdRes as $v) {
            if (empty($v)) {
                datamsg(400, '购物车存在信息参数错误');
            }
            $carts = Db::name('cart')->alias('a')->field('a.*,b.goods_name,b.thumb_url,b.shop_price,b.is_send_free,c.shop_name')->join('sp_goods b', 'a.goods_id = b.id', 'INNER')->join('sp_shops c', 'a.shop_id = c.id', 'INNER')->where('a.id', $v)->where('a.user_id', $userId)->where('b.onsale', 1)->where('c.open_status', 1)->find();
            if (!$carts) {
                datamsg(400, '购物车存在信息参数错误');
            }
            $ruinfo = array('id' => $carts['goods_id'], 'shop_id' => $carts['shop_id']);
            $ru_attr = $carts['goods_attr'];

            $commonModel = new CommonModel();
            $activity = $commonModel->getActivityInfo($ruinfo, $ru_attr);

            if ($activity) {
                $carts['hd_type'] = $activity['ac_type'];
                $carts['hd_id'] = $activity['id'];

                if ($activity['ac_type'] == 1) {
                    $stock = $goodsModel->getGoodsOptionStock($carts['goods_id'], $carts['goods_attr'], 'seckill');
                }
                if ($activity['ac_type'] == 2) {
                    datamsg(400, '积分换购商品不支持购物车提交订单');
                }
                if ($activity['ac_type'] == 3) {
                    datamsg(400, '拼团商品不支持购物车提交订单');
                }

                if ($carts['num'] > 0 && $carts['num'] <= $stock) {
                    if ($carts['goods_attr']) {
                        $goodsSpecItemModel = new GoodsSpecItemModel();
                        $goodsSpecItemIdArr = explode('_', $carts['goods_attr']);
                        $specItemInfo = $goodsSpecItemModel->getGoodsSpecAndSpecItemInfo($goodsSpecItemIdArr);
                        unset($goodsSpecItemIdArr);
                        $goods_attr_str = '';
                        foreach ($specItemInfo as $key => $val) {
                            $str = $key == count($specItemInfo) - 1 ? '' : ';';
                            $goods_attr_str .= $val->goodsSpec->title . ':' . $val->title . $str;
                        }
                    } else {
                        $goods_attr_str = '';
                    }

                    $carts['shop_price'] = $goodsModel->getGoodsOptionPrice($carts['goods_id'], $carts['goods_attr'], 'seckill');
                    $goodsInfoRes[] = array(
                        'id'             => $carts['goods_id'],
                        'goods_name'     => $carts['goods_name'],
                        'thumb_url'      => $carts['thumb_url'],
                        'goods_attr_id'  => $carts['goods_attr'],
                        'goods_attr_str' => $goods_attr_str,
                        'shop_price'     => $carts['shop_price'],
                        'goods_num'      => $carts['num'],
                        'is_send_free'   => $carts['is_send_free'],
                        'hd_type'        => $carts['hd_type'],
                        'hd_id'          => $carts['hd_id'],
                        'shop_id'        => $carts['shop_id']
                    );
                } else {
                    if($activity['ac_type'] == 1){
                        $msg = lang('秒杀已抢完');
                    }else{
                        $msg = lang('库存不足');
                    }
                    datamsg(400, $carts['goods_name'] . $msg);
                }
            } else {
                $carts['hd_type'] = 0;
                $carts['hd_id'] = 0;

                $stock = $goodsModel->getGoodsOptionStock($carts['goods_id'], $carts['goods_attr']);

                if ($carts['num'] > 0 && $carts['num'] <= $stock) {
                    if ($carts['goods_attr']) {
                        $goodsSpecItemModel = new GoodsSpecItemModel();
                        $goodsSpecItemIdArr = explode('_', $carts['goods_attr']);
                        $specItemInfo = $goodsSpecItemModel->getGoodsSpecAndSpecItemInfo($goodsSpecItemIdArr);
                        unset($goodsSpecItemIdArr);
                        $goods_attr_str = '';
                        foreach ($specItemInfo as $key => $val) {
                            $str = $key == count($specItemInfo) - 1 ? '' : ';';
                            $goods_attr_str .= $val->goodsSpec->title . ':' . $val->title . $str;
                        }
                    } else {
                        $goods_attr_str = '';
                    }

                    $carts['shop_price'] = $goodsModel->getGoodsOptionPrice($carts['goods_id'], $carts['goods_attr']);

                    $goodsInfoRes[] = array(
                        'id'             => $carts['goods_id'],
                        'goods_name'     => $carts['goods_name'],
                        'thumb_url'      => $carts['thumb_url'],
                        'goods_attr_id'  => $carts['goods_attr'],
                        'goods_attr_str' => $goods_attr_str,
                        'shop_price'     => $carts['shop_price'],
                        'goods_num'      => $carts['num'],
                        'is_send_free'   => $carts['is_send_free'],
                        'hd_type'        => $carts['hd_type'],
                        'hd_id'          => $carts['hd_id'],
                        'shop_id'        => $carts['shop_id']
                    );
                } else {
                    datamsg(400, $carts['goods_name'] . lang('库存不足'));
                }
            }


        }

        $ordouts = Db::name('order_timeout')->where('id', 1)->find();
        if (!$ordouts) {
            datamsg(400, '创建订单失败');
        }
        if (!$goodsInfoRes) {
            datamsg(400, '商品信息参数错误');
        }

        $dispatchModel = new DispatchModel();
        $dispatchPriceData = $dispatchModel->getOrderDispatchPrice($goodsInfoRes, $address);

        $hqGoodsInfos = array();

        foreach ($goodsInfoRes as $kd => $vd) {
            $hqGoodsInfos[$vd['shop_id']]['goodres'][] = $vd;
        }

        if (!$hqGoodsInfos) {
            datamsg(400, '商品信息参数错误');
        }
        foreach ($hqGoodsInfos as $kc => $vc) {
            $hqGoodsInfos[$kc]['coupon_id'] = 0;
            $hqGoodsInfos[$kc]['coupon_price'] = 0;
            $hqGoodsInfos[$kc]['coupon_str'] = '';
            $hqGoodsInfos[$kc]['youhui_price'] = 0;
            $hqGoodsInfos[$kc]['xiaoji_price'] = 0;

            $xiaoji = 0;
            foreach ($vc['goodres'] as $vp) {
                $xiaoji += sprintf("%.2f", $vp['shop_price'] * $vp['goods_num']);
            }

            $coupons = Db::name('coupon')->where('shop_id', $kc)->where('start_time', 'elt', time())->where('end_time', 'gt', time() - 3600 * 24)->where('onsale', 1)->field('id,man_price,dec_price')->order('man_price asc')->find();
            if ($coupons) {
                $couinfos = Db::name('member_coupon')->alias('a')->field('a.*,b.man_price,b.dec_price')->join('sp_coupon b', 'a.coupon_id = b.id', 'INNER')->where('a.user_id', $userId)->where('a.is_sy', 0)->where('a.shop_id', $kc)->where('b.start_time', 'elt', time())->where('b.end_time', 'gt', time() - 3600 * 24)->where('b.onsale', 1)->where('b.man_price', 'elt', $xiaoji)->order('b.man_price desc')->find();

                if ($couinfos) {
                    $hqGoodsInfos[$kc]['coupon_id'] = $couinfos['coupon_id'];
                    $hqGoodsInfos[$kc]['coupon_price'] = $couinfos['dec_price'];
                    $hqGoodsInfos[$kc]['coupon_str'] = '满' . $couinfos['man_price'] . '减' . $couinfos['dec_price'];
                    $hqGoodsInfos[$kc]['youhui_price'] += $couinfos['dec_price'];
                }
            }

            $promotionRes = Db::name('promotion')->where('shop_id', $kc)->where('is_show', 1)->where('start_time', 'elt', time())->where('end_time', 'gt', time())->field('id,start_time,end_time,info_id')->select();
            $cxgoodres = array();

            if ($promotionRes) {
                foreach ($promotionRes as $prv) {
                    $promTypeRes = Db::name('prom_type')->where('prom_id', $prv['id'])->select();
                    if ($promTypeRes) {
                        $promNum = 0;
                        $cuxiaogoods = array();
                        $prohdsort = array();
                        $cxgds = array();

                        foreach ($vc['goodres'] as $vp) {
                            if (strpos(',' . $prv['info_id'] . ',', ',' . $vp['id'] . ',') !== false) {
                                $promNum += $vp['goods_num'];
                                $cuxiaogoods[] = array('id' => $vp['id'], 'shop_price' => $vp['shop_price'], 'goods_num' => $vp['goods_num']);
                                $cxgds[] = $vp['id'];
                            }
                        }

                        if ($promNum) {
                            foreach ($promTypeRes as $krp => $vrp) {
                                if ($promNum && $promNum >= $vrp['man_num']) {
                                    $prohdsort[] = $vrp;
                                }
                            }

                            if ($prohdsort) {
                                $prohdsort = array_sort($prohdsort, 'man_num');
                                $promhdinfo = $prohdsort[0];

                                $zhekou = $promhdinfo['discount'] / 100;
                                foreach ($cuxiaogoods as $cx) {
                                    $zhekouprice = sprintf("%.2f", $cx['shop_price'] * $zhekou);
                                    $youhui_price = ($cx['shop_price'] - $zhekouprice) * $cx['goods_num'];
                                    $hqGoodsInfos[$kc]['youhui_price'] += sprintf("%.2f", $youhui_price);
                                }

                                $cxgoodres[] = array('promo_id' => $prv['id'], 'man_num' => $promhdinfo['man_num'], 'discount' => $promhdinfo['discount'], 'cxgds' => $cxgds);
                            }
                        }
                    }
                }
            }

            $hqGoodsInfos[$kc]['goods_price'] = $xiaoji;
            $hqGoodsInfos[$kc]['youhui_price'] = sprintf("%.2f", $hqGoodsInfos[$kc]['youhui_price']);
            $hqGoodsInfos[$kc]['xiaoji_price'] = sprintf("%.2f", $xiaoji - $hqGoodsInfos[$kc]['youhui_price']);

            $hqGoodsInfos[$kc]['freight'] = $dispatchPriceData['dispatch_shop'][$kc];
            $hqGoodsInfos[$kc]['xiaoji_price'] = sprintf("%.2f", $hqGoodsInfos[$kc]['xiaoji_price'] + $dispatchPriceData['dispatch_shop'][$kc]);
            $total_price += $hqGoodsInfos[$kc]['xiaoji_price'];
        }

        $total_price = sprintf("%.2f", $total_price);

        $orderNumber = 'Z' . date('YmdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $dingdan = Db::name('order_zong')->where('order_number', $orderNumber)->find();
        if ($dingdan) {
            datamsg(400, '创建订单失败');
        }
        $datainfo = array();
        $datainfo['order_number'] = $orderNumber;
        $datainfo['total_price'] = $total_price;
        $datainfo['state'] = 0;
        $datainfo['zf_type'] = 0;
        $datainfo['user_id'] = $userId;
        $datainfo['addtime'] = time();
        $datainfo['time_out'] = 0;

        // 启动事务
        Db::startTrans();
        try {
            $zong_id = Db::name('order_zong')->insertGetId($datainfo);
            if ($zong_id) {
                $outarr = array();
                $goodsOptionModel = new GoodsOptionModel();
                $seckillModel = new SeckillModel();
                foreach ($hqGoodsInfos as $qkey => $qval) {
                    $time_out = time() + $ordouts['normal_out_order'] * 3600;

                    foreach ($qval['goodres'] as $cvp) {
                        if ($cvp['hd_type'] == 1) {
                            $time_out = time() + $ordouts['rushactivity_out_order'] * 60;
                            break;
                        } elseif ($cvp['hd_type'] == 2) {
                            $time_out = time() + $ordouts['group_out_order'] * 60;
                        }
                    }

                    $outarr[] = $time_out;

                    $shop_ordernum = 'D' . date('YmdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);

                    $order_id = Db::name('order')->insertGetId(array(
                        'ordernumber'  => $shop_ordernum,
                        'contacts'     => $address['contacts'],
                        'telephone'    => $address['phone'],
                        'pro_id'       => $address['pro_id'],
                        'city_id'      => $address['city_id'],
                        'area_id'      => $address['area_id'],
                        'province'     => $address['province'],
                        'city'         => $address['city'],
                        'area'         => $address['area'],
                        'address'      => $address['address'],
                        'dz_id'        => $address['id'],
                        'goods_price'  => $qval['goods_price'],
                        'freight'      => $qval['freight'],
                        'coupon_id'    => $qval['coupon_id'],
                        'coupon_price' => $qval['coupon_price'],
                        'coupon_str'   => $qval['coupon_str'],
                        'youhui_price' => $qval['youhui_price'],
                        'total_price'  => $qval['xiaoji_price'],
                        'state'        => 0,
                        'zf_type'      => 0,
                        'fh_status'    => 0,
                        'order_status' => 0,
                        'user_id'      => $userId,
                        'zong_id'      => $zong_id,
                        'order_type'   => 1,
                        'pin_type'     => 0,
                        'pin_id'       => 0,
                        'shop_id'      => $qkey,
                        'addtime'      => time(),
                        'time_out'     => $time_out
                    ));

                    if ($qval['coupon_id']) {
                        Db::name('member_coupon')->where('user_id', $userId)->where('coupon_id', $qval['coupon_id'])->where('is_sy', 0)->where('shop_id', $qkey)->update(array('is_sy' => 1));
                        $goodyh_price = sprintf("%.2f", $qval['goods_price'] - $qval['coupon_price']);
                    }

                    foreach ($qval['goodres'] as $rkey => $rval) {
                        $goodzs_price = $rval['shop_price'];
                        $jian_price = 0;
                        $prom_id = 0;
                        $prom_str = '';

                        if ($qval['coupon_id']) {
                            $dan_price = sprintf("%.2f", ($goodyh_price / $qval['goods_price']) * $rval['shop_price']);
                            $goodzs_price = $dan_price;
                            $jian_price = sprintf("%.2f", $rval['shop_price'] - $dan_price);
                        }

                        if (!empty($cxgoodres)) {
                            foreach ($cxgoodres as $cxval) {
                                if (in_array($rval['id'], $cxval['cxgds'])) {
                                    $zklv = $cxval['discount'] / 100;
                                    $zkprice = sprintf("%.2f", $rval['shop_price'] * $zklv);
                                    $goodzs_price = sprintf("%.2f", $zkprice - $jian_price);
                                    $prom_id = $cxval['promo_id'];
                                    $zhenum = $cxval['discount'] / 10;
                                    $prom_str = lang('满') . $cxval['man_num'] . lang('件') . $zhenum . lang('折');
                                    break;
                                }
                            }
                        }

                        $orgoods_id = Db::name('order_goods')->insertGetId(array(
                            'goods_id'       => $rval['id'],
                            'goods_name'     => $rval['goods_name'],
                            'thumb_url'      => $rval['thumb_url'],
                            'goods_attr_id'  => $rval['goods_attr_id'],
                            'goods_attr_str' => $rval['goods_attr_str'],
                            'real_price'     => $rval['shop_price'],
                            'price'          => $goodzs_price,
                            'goods_num'      => $rval['goods_num'],
                            'hd_type'        => $rval['hd_type'],
                            'hd_id'          => $rval['hd_id'],
                            'prom_id'        => $prom_id,
                            'prom_str'       => $prom_str,
                            'is_send_free'   => $rval['is_send_free'],
                            'shop_id'        => $qkey,
                            'order_id'       => $order_id
                        ));

                        // 库存处理  $rval['hd_type'] 0-非活动商品，1-秒杀
                        if ($rval['hd_type'] == 0) {
                            if (!empty($rval['goods_attr_id'])) {
                                $goodsOptionModel->where(['goods_id' => $rval['id'], 'specs' => $rval['goods_attr_id']])->setDec('stock', $rval['goods_num']);
                            }
                            $goodsModel->where('id', $rval['id'])->setDec('total', $rval['goods_num']);
                        }

                        if ($rval['hd_type'] == 1) {
                            if (!empty($rval['goods_attr_id'])) {
                                $goodsOptionModel->where(['goods_id' => $rval['id'], 'specs' => $rval['goods_attr_id'], 'is_seckill' => 1])->setDec('seckill_stock', $rval['goods_num']);
                            } else {
                                $seckillModel->where('id', $rval['hd_id'])->setDec('stock', $rval['goods_num']);
                            }
                            $seckillModel->where('id', $rval['hd_id'])->setInc('sold', $rval['goods_num']);
                        }
                    }
                    //分销处理
                    $distrib = new DistributionCommon();
                    $distrib->commissionCalculation($userId, $qval['xiaoji_price'], $order_id);
                }

                $order_time_out = min($outarr);
                Db::name('order_zong')->update(array('id' => $zong_id, 'time_out' => $order_time_out));
                Db::name('cart')->where('id', 'in', $cartIdRes)->where('user_id', $userId)->delete();
            }

            // 提交事务
            Db::commit();
            $orderinfos = array('order_number' => $orderNumber, 'zf_type' => $zf_type);
            datamsg(200, '创建订单成功', $orderinfos);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            datamsg(400, '创建订单失败' . $e->getMessage());
        }

    }


    //立即购买创建订单接口
    public function puraddorder()
    {
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }
        if (!input('post.pur_id')) {
            datamsg(400, '缺少立即购买商品参数', array('status' => 400));
        }
        if (!in_array(input('post.fangshi'), array(1, 2))) {
            datamsg(400, '缺少购买方式参数', array('status' => 400));
        }
        if (!input('post.dz_id')) {
            datamsg(400, '缺少地址信息', array('status' => 400));
        }

        $zf_type = input('post.zf_type');
        $fangshi = input('post.fangshi');

        $address = Db::name('address')
            ->where('id', input('post.dz_id'))
            ->where('user_id', $userId)
            ->find();
        if (!$address) {
            datamsg(400, '地址信息错误', array('status' => 400));
        }
        $pur_id = input('post.pur_id');
        $purchs = Db::name('purch')
            ->alias('a')
            ->field('a.*,b.goods_name,b.thumb_url,b.shop_price,b.is_send_free,c.shop_name')
            ->join('sp_goods b', 'a.goods_id = b.id', 'INNER')
            ->join('sp_shops c', 'a.shop_id = c.id', 'INNER')
            ->where('a.id', $pur_id)
            ->where('a.user_id', $userId)
            ->where('b.onsale', 1)
            ->where('c.open_status', 1)
            ->find();
        if (!$purchs) {
            datamsg(400, '找不到相关商品信息', array('status' => 400));
        }
        $total_price = 0;
        $order_type = 1;
        $pin_type = 0;
        $goodinfos = array();

        $ruinfo = array('id' => $purchs['goods_id'], 'shop_id' => $purchs['shop_id']);
        $ru_attr = $purchs['goods_attr'];

        $commonModel = new CommonModel();
        $activity = $commonModel->getActivityInfo($ruinfo, $ru_attr);

        $goodsModel = new GoodsModel();
        $goodsSpecItemModel = new GoodsSpecItemModel();
        if (!$activity || ($activity && $activity['ac_type'] == 3 && $fangshi == 1)) { // 非活动商品或拼团活动单独购买
            $purchs['hd_type'] = 0;
            $purchs['hd_id'] = 0;

            $stock = $goodsModel->getGoodsOptionStock($purchs['goods_id'], $purchs['goods_attr']);
            $weight = $goodsModel->getGoodsOptionWeight($purchs['goods_id'], $purchs['goods_attr']);

            if ($purchs['num'] > $stock) {
                datamsg(400, '库存不足');
            }
            if (!empty($purchs['goods_attr'])) {
                $goods_attr_str = '';
                $specItemIdArr = explode('_', $purchs['goods_attr']);
                $specItemInfo = $goodsSpecItemModel->getGoodsSpecAndSpecItemInfo($specItemIdArr);
                foreach ($specItemInfo as $k => $v) {
                    $str = $k == count($specItemInfo) - 1 ? '' : ';';
                    $goods_attr_str .= $v->goodsSpec->title . ':' . $v->title . $str;
                }
                $purchs['shop_price'] = $goodsModel->getGoodsOptionPrice($purchs['goods_id'], $purchs['goods_attr']);
            } else {
                $goods_attr_str = '';
            }

            $goodinfos = array(
                'id'             => $purchs['goods_id'],
                'goods_name'     => $purchs['goods_name'],
                'thumb_url'      => $purchs['thumb_url'],
                'goods_attr_id'  => $purchs['goods_attr'],
                'goods_attr_str' => $goods_attr_str,
                'shop_price'     => $purchs['shop_price'],
                'goods_num'      => $purchs['num'],
                'is_send_free'   => $purchs['is_send_free'],
                'hd_type'        => $purchs['hd_type'],
                'hd_id'          => $purchs['hd_id'],
                'shop_id'        => $purchs['shop_id'],
                'weight'         => $weight
            );
        } else {
            $purchs['hd_type'] = $activity['ac_type'];
            $purchs['hd_id'] = $activity['id'];

            if ($activity['ac_type'] == 1) {
                $stock = $goodsModel->getGoodsOptionStock($purchs['goods_id'], $purchs['goods_attr'], 'seckill');
                $weight = $goodsModel->getGoodsOptionWeight($purchs['goods_id'], $purchs['goods_attr'], 'seckill');
                $purchs['shop_price'] = $goodsModel->getGoodsOptionPrice($purchs['goods_id'], $purchs['goods_attr'], 'seckill');
            }

            if ($activity['ac_type'] == 2) {
                $stock = $goodsModel->getGoodsOptionStock($purchs['goods_id'], $purchs['goods_attr'], 'integral');
                $weight = $goodsModel->getGoodsOptionWeight($purchs['goods_id'], $purchs['goods_attr'], 'integral');
                $price= $goodsModel->getGoodsOptionPrice($purchs['goods_id'], $purchs['goods_attr'], 'integral');
                $purchs['shop_price'] = $price['price'];
                $purchs['integral'] = $price['integral'];

            }

            if ($activity['ac_type'] == 3 && $fangshi == 2) {
                $stock = $goodsModel->getGoodsOptionStock($purchs['goods_id'], $purchs['goods_attr'], 'assemble');
                $weight = $goodsModel->getGoodsOptionWeight($purchs['goods_id'], $purchs['goods_attr'], 'assemble');
                $purchs['shop_price'] = $goodsModel->getGoodsOptionPrice($purchs['goods_id'], $purchs['goods_attr'], 'assemble');
            }

            if ($purchs['num'] > $stock) {
                if($activity['ac_type'] == 1){
                    $msg = lang('秒杀已抢完');
                }else{
                    $msg = lang('库存不足');
                }
                datamsg(400,  $msg);
            }
            if (!empty($purchs['goods_attr'])) {
                $goods_attr_str = '';
                $specItemIdArr = explode('_', $purchs['goods_attr']);
                $specItemInfo = $goodsSpecItemModel->getGoodsSpecAndSpecItemInfo($specItemIdArr);
                foreach ($specItemInfo as $k => $v) {
                    $str = $k == count($specItemInfo) - 1 ? '' : ';';
                    $goods_attr_str .= $v->goodsSpec->title . ':' . $v->title . $str;
                }
            } else {
                $goods_attr_str = '';
            }

            if ($activity['ac_type'] == 3) {
                $assem_type = 1;
                $zhuangtai = 0;

                if (input('post.pin_number')) {
                    $assem_number = input('post.pin_number');
                    $pintuans = Db::name('pintuan')
                        ->where('assem_number', $assem_number)
                        ->where('state', 1)
                        ->where('pin_status', 'in', '0,1')
                        ->where('hd_id', $activity['id'])
                        ->find();
                    if ($pintuans) {
                        $order_assembles = Db::name('order_assemble')
                            ->where('pin_id', $pintuans['id'])
                            ->where('user_id', $userId)
                            ->where('state', 1)
                            ->where('tui_status', 0)
                            ->find();
                        if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                            if ($order_assembles) {
                                $assem_type = 3;
                                $zhuangtai = 1;
                            } else {
                                $assem_type = 2;
                            }
                        } elseif ($pintuans['pin_status'] == 1) {
                            if ($order_assembles) {
                                $zhuangtai = 2;
                            }
                        }
                    } else {
                        if (!empty($activity['goods_attr'])) {
                            $order_assembles = Db::name('order_assemble')
                                ->where('user_id', $userId)
                                ->where('goods_id', $purchs['goods_id'])
                                ->where('goods_attr', $purchs['goods_attr'])
                                ->where('shop_id', $purchs['shop_id'])
                                ->where('hd_id', $activity['id'])
                                ->where('state', 1)
                                ->where('tui_status', 0)
                                ->order('addtime desc')
                                ->find();
                        } else {
                            $order_assembles = Db::name('order_assemble')
                                ->where('user_id', $userId)
                                ->where('goods_id', $purchs['goods_id'])
                                ->where('shop_id', $purchs['shop_id'])
                                ->where('hd_id', $activity['id'])
                                ->where('state', 1)
                                ->where('tui_status', 0)
                                ->order('addtime desc')
                                ->find();
                        }
                        if ($order_assembles) {
                            $pintuans = Db::name('pintuan')
                                ->where('id', $order_assembles['pin_id'])
                                ->where('state', 1)
                                ->where('pin_status', 'in', '0,1')
                                ->where('hd_id', $activity['id'])
                                ->find();
                            if ($pintuans) {
                                if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                    $assem_type = 3;
                                    $zhuangtai = 1;
                                } elseif ($pintuans['pin_status'] == 1) {
                                    $zhuangtai = 2;
                                }
                            }
                        }
                    }
                } else {
                    if (!empty($activity['goods_attr'])) {
                        $order_assembles = Db::name('order_assemble')
                            ->where('user_id', $userId)
                            ->where('goods_id', $purchs['goods_id'])
                            ->where('goods_attr', $purchs['goods_attr'])
                            ->where('shop_id', $purchs['shop_id'])
                            ->where('hd_id', $activity['id'])
                            ->where('state', 1)
                            ->where('tui_status', 0)
                            ->order('addtime desc')
                            ->find();
                    } else {
                        $order_assembles = Db::name('order_assemble')
                            ->where('user_id', $userId)
                            ->where('goods_id', $purchs['goods_id'])
                            ->where('shop_id', $purchs['shop_id'])
                            ->where('hd_id', $activity['id'])
                            ->where('state', 1)
                            ->where('tui_status', 0)
                            ->order('addtime desc')
                            ->find();
                    }
                    if ($order_assembles) {
                        $pintuans = Db::name('pintuan')
                            ->where('id', $order_assembles['pin_id'])
                            ->where('state', 1)
                            ->where('pin_status', 'in', '0,1')
                            ->where('hd_id', $activity['id'])
                            ->find();
                        if ($pintuans) {
                            if ($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()) {
                                $assem_type = 3;
                                $zhuangtai = 1;
                            } elseif ($pintuans['pin_status'] == 1) {
                                $zhuangtai = 2;
                            }
                        }
                    }
                }

                if ($assem_type == 3) {
                    datamsg(400, '您已参与商品拼团，下单失败');
                }
            }

            $goodinfos = array(
                'id'             => $purchs['goods_id'],
                'goods_name'     => $purchs['goods_name'],
                'thumb_url'      => $purchs['thumb_url'],
                'goods_attr_id'  => $purchs['goods_attr'],
                'goods_attr_str' => $goods_attr_str,
                'shop_price'     => $purchs['shop_price'],
                'goods_num'      => $purchs['num'],
                'is_send_free'   => $purchs['is_send_free'],
                'hd_type'        => $purchs['hd_type'],
                'hd_id'          => $purchs['hd_id'],
                'shop_id'        => $purchs['shop_id'],
                'weight'         => $weight,
                'integral'       => $purchs['integral']
            );

        }

        $ordouts = Db::name('order_timeout')->where('id', 1)->find();
        if (!$ordouts) {
            datamsg(400, '创建订单失败', array('status' => 400));
        }
        if (!$goodinfos) {
            datamsg(400, '商品信息参数错误', array('status' => 400));
        }
        if ($goodinfos['hd_type'] == 3 && $fangshi == 2) {
            if ($assem_type == 1) {
                $order_type = 2;
                $pin_type = 1;
            } elseif ($assem_type == 2) {
                $order_type = 2;
                $pin_type = 2;
            }
        }

        $goodinfos['coupon_id'] = 0;
        $goodinfos['coupon_price'] = 0;
        $goodinfos['coupon_str'] = '';
        $goodinfos['youhui_price'] = 0;
//        $goodinfos['freight'] = 0;
        $goodinfos['xiaoji_price'] = 0;
        $cxgoods = array();

        $xiaoji = sprintf("%.2f", $goodinfos['shop_price'] * $goodinfos['goods_num']);
        $integral= sprintf("%.2f", $goodinfos['integral'] * $goodinfos['goods_num']);

        if ((!$activity) || (in_array($activity['ac_type'], array(1, 2))) || ($activity['ac_type'] == 3 && $fangshi == 1)) { // 非活动商品、秒杀、拼团活动单独购买
            $coupons = Db::name('coupon')
                ->where('shop_id', $goodinfos['shop_id'])
                ->where('start_time', 'elt', time())
                ->where('end_time', 'gt', time() - 3600 * 24)
                ->where('onsale', 1)
                ->field('id,man_price,dec_price')
                ->order('man_price asc')
                ->find();
            if ($coupons) {
                $couinfos = Db::name('member_coupon')
                    ->alias('a')
                    ->field('a.*,b.man_price,b.dec_price')
                    ->join('sp_coupon b', 'a.coupon_id = b.id', 'INNER')
                    ->where('a.user_id', $userId)
                    ->where('a.is_sy', 0)
                    ->where('a.shop_id', $goodinfos['shop_id'])
                    ->where('b.start_time', 'elt', time())
                    ->where('b.end_time', 'gt', time() - 3600 * 24)
                    ->where('b.onsale', 1)
                    ->where('b.man_price', 'elt', $xiaoji)
                    ->order('b.man_price desc')
                    ->find();

                if ($couinfos) {
                    $goodinfos['coupon_id'] = $couinfos['coupon_id'];
                    $goodinfos['coupon_price'] = $couinfos['dec_price'];
                    $goodinfos['coupon_str'] = lang('满') . $couinfos['man_price'] . lang('减') . $couinfos['dec_price'];
                    $goodinfos['youhui_price'] += $couinfos['dec_price'];
                }
            }

            $promotionRes = Db::name('promotion')
                ->where('shop_id', $goodinfos['shop_id'])
                ->where('is_show', 1)
                ->where('start_time', 'elt', time())
                ->where('end_time', 'gt', time())
                ->field('id,start_time,end_time,info_id')
                ->select();

            if ($promotionRes) {
                foreach ($promotionRes as $prv) {
                    $promTypeRes = Db::name('prom_type')->where('prom_id', $prv['id'])->select();
                    if ($promTypeRes) {
                        $prohdsort = array();

                        if (strpos(',' . $prv['info_id'] . ',', ',' . $goodinfos['id'] . ',') !== false) {
                            foreach ($promTypeRes as $krp => $vrp) {
                                if ($goodinfos['goods_num'] && $goodinfos['goods_num'] >= $vrp['man_num']) {
                                    $prohdsort[] = $vrp;
                                }
                            }

                            if ($prohdsort) {
                                $prohdsort = array_sort($prohdsort, 'man_num');
                                $promhdinfo = $prohdsort[0];

                                $zhekou = $promhdinfo['discount'] / 100;
                                $zhekouprice = sprintf("%.2f", $goodinfos['shop_price'] * $zhekou);
                                $youhui_price = ($goodinfos['shop_price'] - $zhekouprice) * $goodinfos['goods_num'];
                                $youhui_price = sprintf("%.2f", $youhui_price);
                                $goodinfos['youhui_price'] += $youhui_price;

                                $cxgoods = array('promo_id' => $prv['id'], 'man_num' => $promhdinfo['man_num'], 'discount' => $promhdinfo['discount'], 'cxgds' => $goodinfos['id']);
                            }
                            break;
                        }
                    }
                }
            }
        }

        $goodinfos['goods_price'] = $xiaoji;
        $goodinfos['youhui_price'] = sprintf("%.2f", $goodinfos['youhui_price']);
        $goodinfos['xiaoji_price'] = sprintf("%.2f", $xiaoji - $goodinfos['youhui_price']);

        // 运费
        $goodsInfoRes[] = $goodinfos;
        $dispatchModel = new DispatchModel();
        $dispatchPriceData = $dispatchModel->getOrderDispatchPrice($goodsInfoRes, $address);

        $goodinfos['xiaoji_price'] = sprintf("%.2f", $goodinfos['xiaoji_price'] + $dispatchPriceData['dispatch_shop'][$goodinfos['shop_id']]);

        $total_price = sprintf("%.2f", $goodinfos['xiaoji_price']);

        $orderNumber = 'Z' . date('YmdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $dingdan = Db::name('order_zong')->where('order_number', $orderNumber)->find();
        if ($dingdan) {
            datamsg(400, '创建订单失败');
        }
        $datainfo = array();
        $datainfo['order_number'] = $orderNumber;
        $datainfo['total_price'] = $total_price;
        $datainfo['state'] = 0;
        $datainfo['zf_type'] = 0;
        $datainfo['user_id'] = $userId;
        $datainfo['addtime'] = time();
        $datainfo['time_out'] = 0;

        // 启动事务
        Db::startTrans();
        try {
            $zong_id = Db::name('order_zong')->insertGetId($datainfo);
            if ($zong_id) {
                $time_out = time() + $ordouts['normal_out_order'] * 3600;

                if ($goodinfos['hd_type'] == 1) {
                    $time_out = time() + $ordouts['rushactivity_out_order'] * 60;
                } elseif ($goodinfos['hd_type'] == 2) {
                    $time_out = time() + $ordouts['group_out_order'] * 60;
                } elseif ($goodinfos['hd_type'] == 3) {
                    $time_out = time() + $ordouts['assemorder_timeout'] * 60;
                }

                $shop_ordernum = 'D' . date('YmdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);

                $order_id = Db::name('order')->insertGetId(array(
                    'ordernumber'  => $shop_ordernum,
                    'contacts'     => $address['contacts'],
                    'telephone'    => $address['phone'],
                    'pro_id'       => $address['pro_id'],
                    'city_id'      => $address['city_id'],
                    'area_id'      => $address['area_id'],
                    'province'     => $address['province'],
                    'city'         => $address['city'],
                    'area'         => $address['area'],
                    'address'      => $address['address'],
                    'dz_id'        => $address['id'],
                    'goods_price'  => $goodinfos['goods_price'],
                    'freight'      => $dispatchPriceData['dispatch_shop'][$goodinfos['shop_id']],
                    'coupon_id'    => $goodinfos['coupon_id'],
                    'coupon_price' => $goodinfos['coupon_price'],
                    'coupon_str'   => $goodinfos['coupon_str'],
                    'youhui_price' => $goodinfos['youhui_price'],
                    'total_price'  => $goodinfos['xiaoji_price'],
                    'integral'     => $integral,
                    'state'        => 0,
                    'zf_type'      => 0,
                    'fh_status'    => 0,
                    'order_status' => 0,
                    'user_id'      => $userId,
                    'zong_id'      => $zong_id,
                    'order_type'   => $order_type,
                    'pin_type'     => $pin_type,
                    'pin_id'       => 0,
                    'shop_id'      => $goodinfos['shop_id'],
                    'addtime'      => time(),
                    'time_out'     => $time_out
                ));

                if ($goodinfos['coupon_id']) {
                    Db::name('member_coupon')
                        ->where('user_id', $userId)
                        ->where('coupon_id', $goodinfos['coupon_id'])
                        ->where('is_sy', 0)
                        ->where('shop_id', $goodinfos['shop_id'])
                        ->update(array('is_sy' => 1));
                    $goodyh_price = sprintf("%.2f", $goodinfos['goods_price'] - $goodinfos['coupon_price']);
                }

                $goodzs_price = $goodinfos['shop_price'];
                $jian_price = 0;
                $prom_id = 0;
                $prom_str = '';

                if ($goodinfos['coupon_id']) {
                    $dan_price = sprintf("%.2f", ($goodyh_price / $goodinfos['goods_price']) * $goodinfos['shop_price']);
                    $goodzs_price = $dan_price;
                    $jian_price = sprintf("%.2f", $goodinfos['shop_price'] - $dan_price);
                }

                if (!empty($cxgoods)) {
                    if ($goodinfos['id'] == $cxgoods['cxgds']) {
                        $zklv = $cxgoods['discount'] / 100;
                        $zkprice = sprintf("%.2f", $goodinfos['shop_price'] * $zklv);
                        $goodzs_price = sprintf("%.2f", $zkprice - $jian_price);
                        $prom_id = $cxgoods['promo_id'];
                        $zhenum = $cxgoods['discount'] / 10;
                        $prom_str = lang('满') . $cxgoods['man_num'] . lang('件') . $zhenum . lang('折');
                    }
                }

                $orgoods_id = Db::name('order_goods')->insertGetId(array(
                    'goods_id'       => $goodinfos['id'],
                    'goods_name'     => $goodinfos['goods_name'],
                    'thumb_url'      => $goodinfos['thumb_url'],
                    'goods_attr_id'  => $goodinfos['goods_attr_id'],
                    'goods_attr_str' => $goodinfos['goods_attr_str'],
                    'real_price'     => $goodinfos['shop_price'],
                    'price'          => $goodzs_price,
                    'integral'       => $integral,
                    'goods_num'      => $goodinfos['goods_num'],
                    'hd_type'        => $goodinfos['hd_type'],
                    'hd_id'          => $goodinfos['hd_id'],
                    'prom_id'        => $prom_id,
                    'prom_str'       => $prom_str,
                    'is_send_free'   => $goodinfos['is_send_free'],
                    'shop_id'        => $goodinfos['shop_id'],
                    'order_id'       => $order_id
                ));
                $goodsOptionModel = new GoodsOptionModel();
                $seckillModel = new SeckillModel();
                $IntegralShopModel = new IntegralShopModel();
                $assembleModel = new AssembleModel();
                // 库存处理  $gooosinfo['hd_type'] 0-非活动商品、拼团活动单独购买，1-秒杀，3-拼团
                if ($goodinfos['hd_type'] == 0) {
                    if (!empty($goodinfos['goods_attr_id'])) {
                        $goodsOptionModel->where(['goods_id' => $goodinfos['id'], 'specs' => $goodinfos['goods_attr_id']])->setDec('stock', $goodinfos['goods_num']);
                    }
                    $goodsModel->where('id', $goodinfos['id'])->setDec('total', $goodinfos['goods_num']);
                }

                if ($goodinfos['hd_type'] == 1) {
                    if (!empty($goodinfos['goods_attr_id'])) {
                        $goodsOptionModel->where(['goods_id' => $goodinfos['id'], 'specs' => $goodinfos['goods_attr_id'], 'is_seckill' => 1])->setDec('seckill_stock', $goodinfos['goods_num']);
                    } else {
                        $seckillModel->where('id', $goodinfos['hd_id'])->setDec('stock', $goodinfos['goods_num']);
                    }
                    $seckillModel->where('id', $goodinfos['hd_id'])->setInc('sold', $goodinfos['goods_num']);
                }

                if ($goodinfos['hd_type'] == 2) {
                    if (!empty($goodinfos['goods_attr_id'])) {
                        $goodsOptionModel->where(['goods_id' => $goodinfos['id'], 'specs' => $goodinfos['goods_attr_id'], 'is_integral' => 1])->setDec('integral_stock', $goodinfos['goods_num']);
                    } else {
                        $IntegralShopModel->where('id', $goodinfos['hd_id'])->setDec('stock', $goodinfos['goods_num']);
                    }
                    $IntegralShopModel->where('id', $goodinfos['hd_id'])->setInc('sold', $goodinfos['goods_num']);
                }

                if ($goodinfos['hd_type'] == 3) {
                    if (!empty($goodinfos['goods_attr_id'])) {
                        $goodsOptionModel->where(['goods_id' => $goodinfos['id'], 'specs' => $goodinfos['goods_attr_id'], 'is_assemble' => 1])->setDec('assemble_stock', $goodinfos['goods_num']);
                    } else {
                        $assembleModel->where('id', $goodinfos['hd_id'])->setDec('stock', $goodinfos['goods_num']);
                    }
                    $assembleModel->where('id', $goodinfos['hd_id'])->setInc('sold', $goodinfos['goods_num']);
                }


                Db::name('order_zong')->update(array('id' => $zong_id, 'time_out' => $time_out));
                Db::name('purch')->where('id', $pur_id)->where('user_id', $userId)->delete();

                if ($goodinfos['hd_type'] == 3 && $fangshi == 2) {

                    if ($assem_type == 1 || $assem_type == 2) {
                        if ($assem_type == 1) {
                            $assem_number = 'P' . date('YmdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                            $assem_timeout = time() + $ordouts['assem_timeout'] * 3600;
                            $pin_id = Db::name('pintuan')->insertGetId(array(
                                'assem_number' => $assem_number,
                                'state'        => 0,
                                'pin_num'      => $activity['pin_num'],
                                'tuan_num'     => 0,
                                'goods_id'     => $goodinfos['id'],
                                'pin_status'   => 0,
                                'tz_id'        => $userId,
                                'hd_id'        => $goodinfos['hd_id'],
                                'shop_id'      => $goodinfos['shop_id'],
                                'time'         => time(),
                                'timeout'      => $assem_timeout
                            ));

                            if ($pin_id) {
                                Db::name('order_assemble')->insert(array(
                                    'pin_type'   => 1,
                                    'goods_id'   => $goodinfos['id'],
                                    'goods_attr' => $goodinfos['goods_attr_id'],
                                    'shop_id'    => $goodinfos['shop_id'],
                                    'user_id'    => $userId,
                                    'hd_id'      => $goodinfos['hd_id'],
                                    'pin_id'     => $pin_id,
                                    'order_id'   => $order_id,
                                    'state'      => 0,
                                    'tui_status' => 0,
                                    'addtime'    => time()
                                ));

                                Db::name('order')->update(array('id' => $order_id, 'pin_id' => $pin_id));
                            }
                        } elseif ($assem_type == 2) {
                            Db::name('order_assemble')->insert(array(
                                'pin_type'   => 2,
                                'goods_id'   => $goodinfos['id'],
                                'goods_attr' => $goodinfos['goods_attr_id'],
                                'shop_id'    => $goodinfos['shop_id'],
                                'user_id'    => $userId,
                                'hd_id'      => $goodinfos['hd_id'],
                                'pin_id'     => $pintuans['id'],
                                'order_id'   => $order_id,
                                'state'      => 0,
                                'tui_status' => 0,
                                'addtime'    => time()
                            ));

                            Db::name('order')->update(array('id' => $order_id, 'pin_id' => $pintuans['id']));
                        }
                    }
                }
            }
            //直接通过商品id 去判断，如果存在购物车里的信息，则删除
            $tdata = Db::name('cart')->where('goods_id', $purchs['goods_id'])->where('user_id', $userId)->find();
            if ($tdata) {
                Db::name('cart')->where('goods_id', $purchs['goods_id'])->where('user_id', $userId)->delete();
            }

            //分销处理
            if($goodinfos['hd_type'] != 2){
                $distrib = new DistributionCommon();
                $distrib->commissionCalculation($userId, $goodinfos['xiaoji_price'], $order_id);
            }

            // 提交事务
            Db::commit();
            $orderinfos = array('order_number' => $orderNumber, 'zf_type' => $zf_type);
            datamsg(200, '创建订单成功', $orderinfos);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            datamsg(400, '创建订单失败' . $e->getMessage());
        }
    }


    //提交支付
    public function zhifu()
    {
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }

        if (!input('post.order_number')) {
            datamsg(400, '缺少订单号');
        }
        if (!in_array(input('post.zf_type'), array(1,2,3,4,5,6,7,8))) {
            datamsg(400, '支付方式参数错误');
        }

        $scene = input('post.scene', 'goods');  // 支付场景：goods-商品订单（默认），recharge-充值订单
        $orderNumber = input('post.order_number');
        $zf_type = input('post.zf_type');
        $webconfig = $this->webconfig;

        if (input('post.zf_type') == 5) {
            if (input('post.card_name') && input('post.card_number')) {
                $card_name = input('post.card_name');
                $card_number = input('post.card_number');
            } else {
                $value = array('status' => 400, 'mess' => '银行卡信息错误，支付失败', 'data' => array('status' => 400));
                return json($value);
            }
        }

        if (input('post.zf_type') == 6 || input('post.zf_type') == 7) {
            if (input('post.usdt_img')) {
                $usdtImg = input('post.usdt_img');
            } else {
                datamsg(400, '请上传usdt支付截图');
            }
        }

        if ($scene == 'goods') { // 商品订单
            $orderinfos = Db::name('order_zong')->where('order_number', $orderNumber)->where('state', 0)->where('user_id', $userId)->field('id,order_number,total_price,time_out')->find();
            if (!$orderinfos) {
                datamsg(400, '找不到相关类型订单');
            }
            $orderes = Db::name('order')->where('zong_id', $orderinfos['id'])->field('id,ordernumber,telephone,state,fh_status,order_status,order_type,pin_type,pin_id,time_out,shop_id')->select();
            $telephone = db('shops')->where('id',$orderes[0]['shop_id'])->value('telephone');
            $phone = db('member')->where('id',$userId)->value('phone');
            if (!$orderes) {
                datamsg(400, '找不到相关订单信息');
            }
            foreach ($orderes as $val2) {
                if ($val2['state'] != 0 || $val2['fh_status'] != 0 || $val2['order_status'] != 0) {
                    datamsg(400, '订单类型信息错误，支付失败');
                }
            }

            $orderIntegral = Db::name('order')->where('zong_id', $orderinfos['id'])->sum('integral');
            if($orderIntegral > 0){ // 如果订单中的积分>0，则判断用户的积分是否充足
                $userModel = new MemberModel();
                $userIntegral = $userModel->getUserIntegral($userId);
                if ($userIntegral == 0 || $userIntegral < $orderIntegral) {
                    datamsg(400, '积分不足，支付失败');
                }
            }

            $leixing = 0;
            $zforder_num = '';

            if (count($orderes) == 1) {
                if ($orderes[0]['order_type'] == 1) {
                    $leixing = 1; // 普通订单
                } elseif ($orderes[0]['order_type'] == 2) {
                    $leixing = 2; // 拼团订单（拼团订单只能单品拼团下单，子订单只存在一个，此处通过第一个子订单判断是否为拼团订单）
                    $pinorder_id = $orderes[0]['id'];
                    $pin_type = $orderes[0]['pin_type'];
                    $pin_id = $orderes[0]['pin_id'];
                }
                $zforder_num = $orderes[0]['ordernumber'];
            }

            if ($leixing == 2) {
                if ($pin_type == 1) {
                    $pintuans = Db::name('pintuan')->where('id', $pin_id)->where('tz_id', $userId)->where('state', 0)->find();
                    if (!$pintuans) {
                        datamsg(400, '参数错误，支付失败');
                    }
                    $order_assembles = Db::name('order_assemble')->where('pin_id', $pintuans['id'])->where('order_id', $pinorder_id)->where('pin_type', 1)->where('user_id', $userId)->where('state', 0)->where('tui_status', 0)->find();
                    if (!$order_assembles) {
                        datamsg(400, '参数错误，支付失败');
                    }
                    if ($pintuans['pin_status'] == 1 || $pintuans['pin_num'] == $pintuans['tuan_num']) {
                        datamsg(400, '参数错误，支付失败');
                    } elseif (($pintuans['pin_status'] == 2) || ($pintuans['pin_status'] == 0 && $pintuans['timeout'] <= time())) {
                        datamsg(400, '参数错误，支付失败');
                    }
                } elseif ($pin_type == 2) {
                    $pintuans = Db::name('pintuan')->where('id', $pin_id)->where('tz_id', 'neq', $userId)->where('state', 1)->find();
                    if (!$pintuans) {
                        datamsg(400, '参数错误，支付失败');
                    }
                    $order_assembles = Db::name('order_assemble')->where('pin_id', $pintuans['id'])->where('order_id', $pinorder_id)->where('pin_type', 2)->where('user_id', $userId)->where('state', 0)->where('tui_status', 0)->find();
                    if (!$order_assembles) {
                        datamsg(400, '参数错误，支付失败');
                    }
                    if ($pintuans['pin_status'] == 1 || $pintuans['pin_num'] == $pintuans['tuan_num']) {
                        datamsg(400, '该团已拼团成功，参团并支付失败');
                    } elseif (($pintuans['pin_status'] == 2) || ($pintuans['pin_status'] == 0 && $pintuans['timeout'] <= time())) {
                        datamsg(400, '该团已拼团失败，参团并支付失败');
                    }
                }
            }
            $nowtime = time();
            if ($nowtime > $orderinfos['time_out']) {
                datamsg(400, '订单已过期，支付失败');
            }
            $orderPrice = $orderinfos['total_price'];

            $timeOut = $orderinfos['time_out'];
            $return_url = $webconfig['weburl'] . '/h5/#/pagesC/order/allOrder?index=0';
            $payTitle = lang("商品订单");
            $orderSn = $orderinfos['order_number'];
        } elseif ($scene == 'recharge') {  // 充值订单
            $orderinfos = Db::name('recharge_order')->where('order_number', $orderNumber)->field('id,order_number,order_price,create_time')->find();
            if (!$orderinfos) {
                datamsg(400, '找不到相关订单信息');
            }
            $orderPrice = $orderinfos['order_price'];
            $timeOut = time() + 5 * 60; // 充值订单默认5分钟内支付
            $return_url = $webconfig['weburl'] . '/h5/#/pagesB/wallet/recharge';
            $payTitle = "充值订单";
            $orderSn = $orderinfos['order_number'];
        }elseif ($scene == 'apply') { // 商家入驻订单
            $orderinfos = Db::name('rz_order')->where('ordernumber', $orderNumber)->field('*')->find();
            if (!$orderinfos) {
                datamsg(400, '找不到相关订单信息');
            }
            $orderPrice = $orderinfos['total_price'];
            $timeOut = time() + 5 * 60; // 订单默认5分钟内支付
            $return_url = $webconfig['weburl'] . '/h5/#/pagesC/applyshop/applyStatus';
            $payTitle = "商家入驻订单";
            $orderSn = $orderinfos['ordernumber'];
        }

        $smsCodeModel = new SmsCodeModel();
        switch ($zf_type) {
            case 1: // 支付宝支付
                //获取支付宝支付配置信息返回
                //获取支付金额
                $money = $orderPrice;
                $notify_url = $webconfig['weburl'] . "/api/AliPay/aliNotify";
                $AliPayHelper = new AliPay();
                $body = $payTitle;
                if (input('post.h5') == 1) {
                    $data = $AliPayHelper->getWapPayInfo($orderSn, $body, $money, $notify_url, $return_url);
                    datamsg(200, '创建订单成功', array('order_number' => $orderSn, 'infos' => $data));
                }elseif (input('post.h5') == 2){    //2、pc支付
                    $data = $AliPayHelper->getPcPayInfo($orderSn, $body, $money, $notify_url, $return_url);
                    datamsg(200, '创建订单成功', array('order_number' => $orderSn, 'infos' => $data));
                } else {
                    $data = $AliPayHelper->getPrePayOrder($body, $money, $orderSn, $notify_url);
                    datamsg(200, '创建订单成功', array('order_number' => $orderSn, 'infos' => $data));
                }
                break;
            case 2: // 微信支付
                $quxiao_time = $timeOut - $nowtime;
                if ($quxiao_time > 60) {
                    //获取支付金额
                    $money = $orderPrice;
                    $body = $payTitle;//支付说明
                    $out_trade_no = $orderSn;//订单号
                    $total_fee = $money * 100;//支付金额(乘以100)
                    $time_start = $nowtime;
                    $time_expire = $timeOut;
                    $notify_url = $webconfig['weburl'] . '/api/WxPay/wxNotify';;//回调地址
                    if (input('post.wechat_miniprogram') == 1) {
                        // 小程序微信支付
                        $openId = Db::name('member')->where('id', $userId)->value('openid');
                        if (!$openId) {
                            $openId = input('post.xcx_openid');
                        }
                        if (!$openId) {
                            datamsg(400, '没有openid，支付失败');
                        }
                        $wechatePay = Factory::payment($this->wechatPayConfig);
                        $jssdk = $wechatePay->jssdk;

                        $order = $wechatePay->order->unify([
                            'body'         => $body,
                            'out_trade_no' => $out_trade_no,
                            'total_fee'    => $total_fee,
                            'notify_url'   => $notify_url, // 支付结果通知网址，如果不设置则会使用配置里的默认地址
                            'trade_type'   => 'JSAPI', // 请对应换成你的支付方式对应的值类型
                            'openid'       => $openId
                        ]);
                        $wechatePayRes = $jssdk->bridgeConfig($order['prepay_id'], false); // 返回数组
                        datamsg(200, '创建订单成功', array('order_number' => $orderSn, 'infos' => $wechatePayRes, 'order' => $order));
                    }elseif(input('post.wechat_miniprogram') == 2){
                        // APP微信支付
                        $wx = new WxPay();
                        $order = $wx->getPrePcPayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url);//调用微信支付的方法
                        if(isset($order['code_url']) && !empty($order['code_url'])){


                            Vendor('phpqrcode.phpqrcode');
                            $QRcode = new \QRcode();
                            ob_start(); // 在服务器打开一个缓冲区来保存所有的输出
                            $QRcode->png($order['code_url'],false,'L',10,5);
                            $imageString = base64_encode(ob_get_contents());
                            ob_end_clean(); //清除缓冲区的内容，并将缓冲区关闭，但不会输出内容
                            $imgInfo =  "data:image/jpg;base64,".$imageString;
                            return json(['status'=>200,'msg'=>'创建订单成功','data'=>['order_number'=>$orderSn,'infos'=>$imgInfo]]);
                            // datamsg(200, '创建订单成功', array('order_number' => $orderSn, 'infos' => $imgInfo));
                        } else {
                            datamsg(400, $order['err_code_des']);
                        }
                    }  else {
                        // APP微信支付
                        $wx = new WxPay();
                        $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url);//调用微信支付的方法
                        if ($order['prepay_id']) {
                            //判断返回参数中是否有prepay_id
                            $order1 = $wx->getOrder($order['prepay_id']);//执行二次签名返回参数
                            datamsg(200, '创建订单成功', array('order_number' => $orderSn, 'infos' => $order1));
                        } else {
                            datamsg(400, $order['err_code_des']);
                        }
                    }
                } else {
                    datamsg(400, '订单唤起支付超时，支付失败');
                }
                break;
            case 3: // 余额支付
                $userModel = new MemberModel();
                $payPwd = input('post.pay_password');
                $checkPayPwd = $userModel->checkPayPwd($userId, $payPwd);
                if ($checkPayPwd['status'] == 400) {
                    datamsg(400, $checkPayPwd['mess']);
                }

                $wallets = Db::name('wallet')->where('user_id', $userId)->find();
                if ($wallets['price'] < $orderPrice) {
                    datamsg(400, '钱包余额不足，支付失败');
                }
                $sheng_price = $wallets['price'] - $orderPrice;
                // 启动事务
                Db::startTrans();
                try {
                    Db::name('wallet')->update(array('price' => $sheng_price, 'id' => $wallets['id']));
                    Db::name('detail')->insert(array('de_type' => 2, 'zc_type' => 2, 'price' => $orderPrice, 'order_type' => 1, 'order_id' => $orderinfos['id'], 'user_id' => $userId, 'wat_id' => $wallets['id'], 'time' => time()));

                    $pay = new Pay();
                    if($scene == 'goods'){
                        $pay->doGooodsOrder($orderSn, 3);
                    }

                    if($scene == 'apply'){
                        $pay->doRzOrder($orderSn, 3);
                    }


                    // 提交事务
                    Db::commit();

                    $zfinfos = array('leixing' => $leixing, 'order_num' => $zforder_num);
                    datamsg(200, '支付成功', $zfinfos);
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    datamsg(400, '钱包余额支付失败');
                }
                break;
            case 4: // 积分支付
                $userModel = new MemberModel();
                $payPwd = input('post.pay_password');
                $checkPayPwd = $userModel->checkPayPwd($userId, $payPwd);
                if ($checkPayPwd['status'] == 400) {
                    datamsg(400, $checkPayPwd['mess']);
                }

                // 启动事务
                Db::startTrans();
                try {
                    $pay = new Pay();
                    $pay->doGooodsOrder($orderinfos['order_number'], 3);
                    // 提交事务
                    Db::commit();
                    $zfinfos = array('leixing' => $leixing, 'order_num' => $zforder_num);
                    datamsg(200, '支付成功', $zfinfos);
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    datamsg(400, '积分支付失败');
                }
                break;
            case 5: // 银行卡转账

                // 启动事务
                Db::startTrans();
                try {
                    if ($scene == 'recharge') { // 充值订单
                        $cards = db('order_card')->where(['order_number'=>$orderNumber])->find();
                        Db::name('recharge_order')->where('order_number', $orderNumber)->update(['pay_way' => 5]);
                        if ($cards) {
                            $data = [];
                            $data['id'] = $cards['id'];
                            $data['order_number'] = $orderNumber;
                            $data['card_name'] = $card_name;
                            $data['card_number'] = $card_number;
                            db('order_card')->update($data);
                        }else{
                            $data = [];
                            $data['order_number'] = $orderNumber;
                            $data['card_name'] = $card_name;
                            $data['card_number'] = $card_number;
                            db('order_card')->insert($data);
                        }
                    }elseif ($scene == 'goods') { // 商品订单
                        $cards = db('order_card')->where(['order_number'=>$orderNumber])->find();
                        $zongData['zf_type'] = $zf_type;
                        db('order_zong')->where('order_number', $orderNumber)->update($zongData);
                        $orderZongId = db('order_zong')->where('order_number', $orderNumber)->value('id');
                        db('order')->where('zong_id', $orderZongId)->update($zongData);
                        if ($cards) {
                            $data = [];
                            $data['id'] = $cards['id'];
                            $data['order_number'] = $orderNumber;
                            $data['card_name'] = $card_name;
                            $data['card_number'] = $card_number;
                            db('order_card')->update($data);
                        }else{
                            $data = [];
                            $data['order_number'] = $orderNumber;
                            $data['card_name'] = $card_name;
                            $data['card_number'] = $card_number;
                            db('order_card')->insert($data);
                        }
                    }elseif ($scene == 'apply'){ // 商家入驻订单
                        $cards = db('order_card')->where(['order_number'=>$orderNumber])->find();
                        $rzData['zf_type'] = $zf_type;
                        db('rz_order')->where('ordernumber', $orderNumber)->update($rzData);
                        if ($cards) {
                            $data = [];
                            $data['id'] = $cards['id'];
                            $data['order_number'] = $orderNumber;
                            $data['card_name'] = $card_name;
                            $data['card_number'] = $card_number;
                            db('order_card')->update($data);
                        }else {
                            $data = [];
                            $data['order_number'] = $orderNumber;
                            $data['card_name'] = $card_name;
                            $data['card_number'] = $card_number;
                            db('order_card')->insert($data);
                        }
                    }

                    // 提交事务
                    Db::commit();
                    if($scene == 'goods'){
                        $smsCodeModel->send($telephone,9,'',$phone.",".$orderNumber.",".$orderinfos['total_price']);
                    }
                    if($scene == 'apply'){
                        $smsCodeModel->send(get_config_value('web_telephone'),8,'',$orderes[0]['telephone'].",".$orderNumber.",".$orderinfos['total_price']);
                    }
                    $orderNum = array('order_num' => $orderNumber);
                    datamsg(200, '订单信息已提交', $orderNum);
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    datamsg(400, '支付失败');
                }
                break;
            case 6: // USDT-TRC20
            case 7: // USDT-ERC20
                // 启动事务
                Db::startTrans();
                try {
                    if ($scene == 'recharge') { // 充值订单
                        $usdts = db('order_usdt')->where(['order_number'=>$orderNumber])->find();
                        Db::name('recharge_order')->where('order_number',$orderNumber)->update(['pay_way'=>$zf_type]);
                        if($usdts){
                            $data = [];
                            $data['id']= $usdts['id'];
                            $data['order_number'] = $orderNumber;
                            $data['usdt_img'] = $usdtImg;
                            $data['create_time'] = time();
                            db('order_usdt')->update($data);
                        }else{
                            $data = [];
                            $data['order_number'] = $orderNumber;
                            $data['usdt_img'] = $usdtImg;
                            $data['create_time'] = time();
                            db('order_usdt')->insert($data);
                        }
                    }elseif ($scene == 'goods'){ // 商品订单
                        $usdts = db('order_usdt')->where(['order_number'=>$orderNumber])->find();
                        $zongData['zf_type'] = $zf_type;
                        db('order_zong')->where('order_number', $orderNumber)->update($zongData);
                        $orderZongId = db('order_zong')->where('order_number', $orderNumber)->value('id');
                        db('order')->where('zong_id', $orderZongId)->update($zongData);
                        if($usdts){
                            $data = [];
                            $data['id']= $usdts['id'];
                            $data['order_number'] = $orderNumber;
                            $data['usdt_img'] = $usdtImg;
                            $data['create_time'] = time();
                            db('order_usdt')->update($data);
                        }else {
                            $data = [];
                            $data['order_number'] = $orderNumber;
                            $data['usdt_img'] = $usdtImg;
                            $data['create_time'] = time();
                            db('order_usdt')->insert($data);
                        }
                    }elseif ($scene == 'apply'){ // 商家入驻订单
                        $usdts = db('order_usdt')->where(['order_number'=>$orderNumber])->find();
                        $rzData['zf_type'] = $zf_type;
                        db('rz_order')->where('ordernumber', $orderNumber)->update($rzData);
                        if($usdts){
                            $data = [];
                            $data['id']= $usdts['id'];
                            $data['order_number'] = $orderNumber;
                            $data['usdt_img'] = $usdtImg;
                            $data['create_time'] = time();
                            db('order_usdt')->update($data);
                        }else {
                            $data = [];
                            $data['order_number'] = $orderNumber;
                            $data['usdt_img'] = $usdtImg;
                            $data['create_time'] = time();
                            db('order_usdt')->insert($data);
                        }
                    }
                    // 提交事务
                    Db::commit();
                    if($scene == 'goods'){
                        $smsCodeModel->send($telephone,9,'',$phone.",".$orderNumber.",".$orderinfos['total_price']);
                    }
                    if($scene == 'apply'){
                        $smsCodeModel->send(get_config_value('web_telephone'),8,'',$orderes[0]['telephone'].",".$orderNumber.",".$orderinfos['total_price']);
                    }
                    $zfinfos = array('order_num' => $zforder_num);
                    datamsg(200, '支付成功', $zfinfos);
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    datamsg(400, '支付失败');
                }
                break;
            case 8: // PayPal支付
                $callback_url = db('paypal_config')->where(['id'=>1])->find();
                if($scene == "goods"){
                    $callback_url['h5_success_url'] = "{$callback_url['web_url']}pagesC/order/allOrder?index=1&order_number={$orderSn}";
                    $callback_url['h5_cancel_url'] = "{$callback_url['web_url']}pagesC/order/allOrder?index=1&order_number={$orderSn}";
                }elseif ($scene == "recharge"){
                    $callback_url['h5_success_url'] = "{$callback_url['web_url']}pagesC/wallet/wallet?index=1&order_number={$orderSn}";
                    $callback_url['h5_cancel_url'] = "{$callback_url['web_url']}pagesC/wallet/wallet?index=1&order_number={$orderSn}";
                }elseif ($scene == "apply"){
                    $callback_url['h5_success_url'] = "{$callback_url['web_url']}pages/tabBar/my?index=1&order_number={$orderSn}";
                    $callback_url['h5_cancel_url'] = "{$callback_url['web_url']}pages/tabBar/my?index=1&order_number={$orderSn}";
                }

                $h5 = input('post.h5');
                //获取支付宝支付配置信息返回
                //获取支付金额
                $money = $orderPrice;
                $Paypal = new Paypal();
                $body = $payTitle;
                $data = $Paypal->createPayPal($h5,$body,$money,$callback_url['h5_success_url'],$callback_url['h5_cancel_url']);
                if($data['status']){
                    $data['data']['clientId'] = $callback_url['client_id'];
                    $data['data']['online'] = $callback_url['online']==1 ? 'sandbox' : 'live';
                    datamsg(200, '创建订单成功', array('order_number' => $orderSn, 'infos' => $data['data']));
                }else{
                    datamsg(400, $data['msg']);
                }
                break;
        }
    }
}