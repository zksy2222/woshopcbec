<?php
/**
 * @Description: 商品Model
 * @Author : 梧桐 <2487937004@qq.com>
 * @Copyright : 武汉一一零七科技有限公司 All rights reserved.
 */

namespace app\api\model;

use think\Db;
use think\Model;

class Goods extends Model
{
    /*
     * 获取标签（新品、热销）商品列表
     * @param $tag string 新品：is_new 热销：is_hot 包邮：is_send_free 推荐：is_recommend
     * @param $num int 记录条数
     * */
    public function getTagGoods($tag, $num)
    {
        $tags = ['is_new', 'is_hot', 'is_send_free', 'is_recommend'];
        if (!in_array($tag, $tags)) {
            $tag = 'is_new';
        }
        return Db::name('goods')
            ->field('id,id as goods_id,thumb_url,goods_name,shop_price,sale_num+sale_virtual as sale_num')
            ->where([$tag => 1, 'checked' => 1, 'is_recycle' => 0, 'onsale' => 1])
            ->order('id DESC')
            ->limit($num)
            ->select();
    }

    public function getGoodsListByIds($goods_ids)
    {
        $where = array('id' => array('in', $goods_ids), 'onsale' => 1);
        return $this->field('id goods_id,goods_name,shop_price,thumb_url goods_img')->where($where)->select();
    }

    public function getIntegralGoodsList($offset, $pageSize)
    {
        $where = array('a.checked' => 1, 'a.is_show' => 1, 'b.onsale' => 1, 'b.checked' => 1, 'b.is_recycle' => 0);
        return db('integral_shop')->field('a.goods_id,a.price shop_price,a.integral,a.goods_attr,b.goods_name,b.thumb_url goods_img')->alias('a')->join('goods b', 'a.goods_id = b.id')->where($where)->limit($offset, $pageSize)->select();
    }

    /**
     * @description 获取商品总库存
     * @param int $goodsId 商品ID
     * @param string $type 商品类型：seckill-秒杀，integral-积分，assemble-拼团，normal-普通商品
     */
    public function getGoodsStock($goodsId, $type = 'normal')
    {
        $goods = $this::get($goodsId);
        switch ($type) {
            case 'normal':
                $stock = $this->where('id', $goodsId)->value('total');
                break;
            case 'seckill':
                if ($goods['hasoption']) {
                    $goodsOptionModel = new GoodsOption();
                    $stock = $goodsOptionModel->where(['goods_id' => $goodsId, 'is_seckill' => 1])->sum('seckill_stock');
                } else {
                    $seckillModel = new Seckill();
                    $seckillStock = $seckillModel->where('goods_id', $goodsId)
                        ->where('checked', 1)
                        ->where('is_show', 1)
                        ->where('start_time', 'elt', time())
                        ->where('end_time', 'gt', time())
                        ->order('id asc')
                        ->value('stock');
                    $stock = intval($seckillStock);
                }
                break;
            case 'integral':
                if ($goods['hasoption']) {
                    $goodsOptionModel = new GoodsOption();
                    $stock = $goodsOptionModel->where(['goods_id' => $goodsId, 'is_integral' => 1])->sum('integral_stock');
                } else {
                    $integralShopModel = new IntegralShop();
                    $integralStock = $integralShopModel->where('goods_id', $goodsId)
                        ->where('checked', 1)
                        ->where('is_show', 1)
                        ->order('id asc')
                        ->value('stock');
                    $stock = intval($integralStock);
                }
                break;
            case 'assemble':
                if ($goods['hasoption']) {
                    $goodsOptionModel = new GoodsOption();
                    $stock = $goodsOptionModel->where(['goods_id' => $goodsId, 'is_assemble' => 1])->sum('assemble_stock');
                } else {
                    $assembleModel = new Assemble();
                    $assembleStock = $assembleModel->where('goods_id', $goodsId)
                        ->where('checked', 1)
                        ->where('is_show', 1)
                        ->where('start_time', 'elt', time())
                        ->where('end_time', 'gt', time())
                        ->order('id asc')
                        ->value('stock');
                    $stock = intval($assembleStock);
                }
                break;
            default:
                $stock = 0;
        }
        return $stock;
    }


    /**
     * @description 获取商品规格库存
     * @param int $goodsId 商品ID
     * @param string $type 商品类型：seckill-秒杀，integral-积分，assemble-拼团，normal-普通商品
     * @param string $specItemIdStr 商品规格组合
     */
    public function getGoodsOptionStock($goodsId, $specItemIdStr = '', $type = 'normal')
    {
        $goods = $this::get($goodsId);
        $goodsOptionModel = new GoodsOption();
        switch ($type) {
            case 'normal':
                if ($goods['hasoption']) {
                    $stock = $goodsOptionModel->where(['goods_id' => $goodsId, 'specs' => $specItemIdStr])->value('stock');
                } else {
                    $stock = $this->where('id', $goodsId)->value('total');
                }
                break;
            case 'seckill':
                if ($goods['hasoption']) {

                    $stock = $goodsOptionModel->where(['goods_id' => $goodsId, 'is_seckill' => 1, 'specs' => $specItemIdStr])->value('seckill_stock');
                } else {
                    $seckillModel = new Seckill();
                    $seckillStock = $seckillModel->where('goods_id', $goodsId)
                        ->where('checked', 1)
                        ->where('is_show', 1)
                        ->where('start_time', 'elt', time())
                        ->where('end_time', 'gt', time())
                        ->order('id asc')
                        ->value('stock');
                    $stock = intval($seckillStock);
                }
                break;
            case 'integral':
                if ($goods['hasoption']) {

                    $stock = $goodsOptionModel->where(['goods_id' => $goodsId, 'is_integral' => 1, 'specs' => $specItemIdStr])->value('integral_stock');
                } else {
                    $integralShopModel = new IntegralShop();
                    $integralStock = $integralShopModel->where('goods_id', $goodsId)
                        ->where('checked', 1)
                        ->where('is_show', 1)
                        ->order('id asc')
                        ->value('stock');
                    $stock = intval($integralStock);
                }
                break;
            case 'assemble':
                if ($goods['hasoption']) {
                    $stock = $goodsOptionModel->where(['goods_id' => $goodsId, 'is_assemble' => 1, 'specs' => $specItemIdStr])->value('assemble_stock');
                } else {
                    $assembleModel = new Assemble();
                    $assembleStock = $assembleModel->where('goods_id', $goodsId)
                        ->where('checked', 1)
                        ->where('is_show', 1)
                        ->where('start_time', 'elt', time())
                        ->where('end_time', 'gt', time())
                        ->order('id asc')
                        ->value('stock');
                    $stock = intval($assembleStock);
                }
                break;
            default:
                $stock = 0;
        }
        if (!$stock) {
            $stock = 0;
        }
        return $stock;
    }

    /**
     * @description 获取商品规格价格
     * @param int $goodsId 商品ID
     * @param string $type 商品类型：seckill-秒杀，integral-积分，assemble-拼团，normal-普通商品
     * @param string $specItemIdStr 商品规格组合
     */
    public function getGoodsOptionPrice($goodsId, $specItemIdStr = '', $type = 'normal')
    {
        $goods = $this::get($goodsId);
        $goodsOptionModel = new GoodsOption();
        switch ($type) {
            case 'normal':
                if ($goods['hasoption']) {
                    $price = $goodsOptionModel->where(['goods_id' => $goodsId, 'specs' => $specItemIdStr])->value('shop_price');
                } else {
                    $price = $this->where('id', $goodsId)->value('shop_price');
                }
                break;
            case 'seckill':
                if ($goods['hasoption']) {
                    $price = $goodsOptionModel->where(['goods_id' => $goodsId, 'is_seckill' => 1, 'specs' => $specItemIdStr])->value('seckill_price');
                } else {
                    $seckillModel = new Seckill();
                    $seckillPrice = $seckillModel->where('goods_id', $goodsId)
                        ->where('checked', 1)
                        ->where('is_show', 1)
                        ->where('start_time', 'elt', time())
                        ->where('end_time', 'gt', time())
                        ->order('id asc')
                        ->value('price');
                    $price = $seckillPrice;
                }
                break;
            case 'integral':
                if ($goods['hasoption']) {
                    $price = $goodsOptionModel->field('integral_price price,integral')->where(['goods_id' => $goodsId, 'is_integral' => 1, 'specs' => $specItemIdStr])->find();
                } else {
                    $integralShopModel = new IntegralShop();
                    $integralPrice = $integralShopModel->where('goods_id', $goodsId)
                        ->field('price,integral')
                        ->where('checked', 1)
                        ->where('is_show', 1)
                        ->order('id asc')
                        ->find();
                    $price = $integralPrice;
                }
                break;
            case 'assemble':
                if ($goods['hasoption']) {
                    $price = $goodsOptionModel->where(['goods_id' => $goodsId, 'is_assemble' => 1, 'specs' => $specItemIdStr])->value('assemble_price');
                } else {
                    $assembleModel = new Assemble();
                    $assemblePrice = $assembleModel->where('goods_id', $goodsId)
                        ->where('checked', 1)
                        ->where('is_show', 1)
                        ->where('start_time', 'elt', time())
                        ->where('end_time', 'gt', time())
                        ->order('id asc')
                        ->value('price');
                    $price = $assemblePrice;
                }
                break;
            default:
                $price = 0;
        }
        if (!$price) {
            $price = 0;
        }
        return $price;
    }

    /**
     * @description 获取商品规格重量
     * @param int $goodsId 商品ID
     * @param string $type 商品类型：seckill-秒杀，integral-积分，assemble-拼团，normal-普通商品
     * @param string $specItemIdStr 商品规格组合
     */
    public function getGoodsOptionWeight($goodsId, $specItemIdStr = '', $type = 'normal')
    {
        $goods = $this::get($goodsId);
        $goodsOptionModel = new GoodsOption();

        if ($goods['hasoption']) {
            switch ($type) {
                case 'normal':
                    $where = '';
                    break;
                case 'seckill':
                    $where['is_seckill'] = 1;
                    break;
                case 'integral':
                    $where['is_integral'] = 1;
                    break;
                case 'assemble':
                    $where['is_assemble'] = 1;
                    break;
                default:
                    $price = 0;
            }
            $weight = $goodsOptionModel->where(['goods_id' => $goodsId, 'specs' => $specItemIdStr])->where($where)->value('weight');
        } else {
            $weight = $this->where('id', $goodsId)->value('weight');
        }
        if (!$weight) {
            $weight = 0;
        }
        return $weight;
    }

    /**
     * @description 获取商品显示价格
     * @param int $goodsId 商品ID
     * @param string $type 商品类型：seckill-秒杀，integral-积分，assemble-拼团，normal-普通商品
     * @param string $position 位置：detail-详情页，list-列表页
     */
    public function getGoodsShowPrice($goodsId, $type = 'normal', $position = "detail")
    {
        $goods = $this::get($goodsId);
        switch ($type) {
            case 'normal':
                if ($goods->min_price != $goods->max_price && $position == 'detail') {
                    $priceData['shop_price'] = $goods->min_price . '~' . $goods->max_price;
                } else {
                    $priceData['shop_price'] = $goods->min_price;
                }
                if ($goods->min_market_price != $goods->max_market_price && $position == 'detail') {
                    $priceData['market_price'] = $goods->min_market_price . '~' . $goods->max_market_price;
                } else {
                    $priceData['market_price'] = $goods->min_market_price;
                }
                break;
            case 'seckill':
                if ($goods['hasoption']) {
                    $goodsOptionModel = new GoodsOption();
                    $minSeckillPrice = $goodsOptionModel->where(['goods_id' => $goodsId, 'is_seckill' => 1])->min('seckill_price');
                    $maxSeckillPrice = $goodsOptionModel->where(['goods_id' => $goodsId, 'is_seckill' => 1])->max('seckill_price');
                    $minSeckillShopPrice = $goodsOptionModel->where(['goods_id' => $goodsId, 'is_seckill' => 1])->min('shop_price');
                    $maxSeckillShopPrice = $goodsOptionModel->where(['goods_id' => $goodsId, 'is_seckill' => 1])->max('shop_price');

                    if ($minSeckillPrice != $maxSeckillPrice && $position == 'detail') {
                        $priceData['seckill_price'] = $minSeckillPrice . '~' . $maxSeckillPrice;
                    } else {
                        $priceData['seckill_price'] = $minSeckillPrice;
                    }
                    if ($minSeckillShopPrice != $maxSeckillShopPrice && $position == 'detail') {
                        $priceData['shop_price'] = $minSeckillShopPrice . '~' . $maxSeckillShopPrice;
                    } else {
                        $priceData['shop_price'] = $minSeckillShopPrice;
                    }
                } else {
                    $seckillModel = new Seckill();
                    $seckillPrice = $seckillModel->where('goods_id', $goodsId)
                        ->where('checked', 1)
                        ->where('is_show', 1)
                        ->where('start_time', 'elt', time())
                        ->where('end_time', 'gt', time())
                        ->order('id asc')
                        ->value('price');
                    $priceData['seckill_price'] = $seckillPrice;
                    $priceData['shop_price'] = $goods->shop_price;
                }
                break;
            case 'integral':
                if ($goods['hasoption']) {
                    $goodsOptionModel = new GoodsOption();
                    $minPriceOption = $goodsOptionModel->where(['goods_id' => $goodsId, 'is_integral' => 1])->order('integral_price ASC')->find();
                    $minShopPriceOption = $goodsOptionModel->where(['goods_id' => $goodsId, 'is_integral' => 1])->order('shop_price ASC')->find();
                    $minIntegralOption = $goodsOptionModel->where(['goods_id' => $goodsId, 'is_integral' => 1])->order('integral ASC')->find();
                    $priceData['integral_price'] = $minPriceOption['integral_price'];
                    $priceData['integral'] = $minIntegralOption['integral'];
                    $priceData['shop_price'] = $minShopPriceOption['shop_price'];

                } else {
                    $integralShopModel = new IntegralShop();
                    $integral = $integralShopModel->field('price,integral')->where('goods_id', $goodsId)
                        ->where('checked', 1)
                        ->where('is_show', 1)
                        ->order('id asc')
                        ->find();
                    $priceData['integral_price'] = $integral['price'];
                    $priceData['integral'] = $integral['integral'];
                    $priceData['shop_price'] = $goods->shop_price;
                }
                break;
            case 'assemble':
                if ($goods['hasoption']) {
                    $goodsOptionModel = new GoodsOption();
                    $minAseemblePrice = $goodsOptionModel->where(['goods_id' => $goodsId, 'is_assemble' => 1])->min('assemble_price');
                    $maxAseemblePrice = $goodsOptionModel->where(['goods_id' => $goodsId, 'is_assemble' => 1])->max('assemble_price');
                    $minAseembleShopPrice = $goodsOptionModel->where(['goods_id' => $goodsId, 'is_assemble' => 1])->min('shop_price');
                    $maxAseembleShopPrice = $goodsOptionModel->where(['goods_id' => $goodsId, 'is_assemble' => 1])->max('shop_price');

                    if ($minAseemblePrice != $maxAseemblePrice && $position == 'detail') {
                        $priceData['assemble_price'] = $minAseemblePrice . '~' . $maxAseemblePrice;
                    } else {
                        $priceData['assemble_price'] = $minAseemblePrice;
                    }
                    if ($minAseembleShopPrice != $maxAseembleShopPrice && $position == 'detail') {
                        $priceData['shop_price'] = $minAseembleShopPrice . '~' . $maxAseembleShopPrice;
                    } else {
                        $priceData['shop_price'] = $minAseembleShopPrice;
                    }
                } else {
                    $assembleModel = new Assemble();
                    $assemblePrice = $assembleModel->where('goods_id', $goodsId)
                        ->where('checked', 1)
                        ->where('is_show', 1)
                        ->where('start_time', 'elt', time())
                        ->where('end_time', 'gt', time())
                        ->order('id asc')
                        ->value('price');
                    $priceData['assemble_price'] = $assemblePrice;
                    $priceData['shop_price'] = $goods->shop_price;
                }
                break;
            default:
                $stock = 0;
        }
        return $priceData;
    }

    /**
     * @description 获取活动商品销售比率
     * @param int $activityId 活动ID
     * @param string $type 活动类型：seckill-秒杀，integral-积分，assemble-拼团
     */
    public function getSalesRatio($activityId, $type = 'seckill')
    {
        switch ($type) {
            case 'seckill':
                $activityModel = new Seckill();
                $whereField = 'is_seckill';
                $sumField = 'seckill_stock';
                break;
            case 'integral':
                $activityModel = new IntegralShop();
                $whereField = 'is_integral';
                $sumField = 'integral_stock';
                break;
            case 'assemble':
                $activityModel = new Assemble();
                $whereField = 'is_assemble';
                $sumField = 'assemble_stock';
                break;
        }

        $activity = $activityModel::get($activityId);
        $goodsModel = new Goods();
        $goods = $goodsModel::get($activity->goods_id);
        if (!empty($activity->goods_attr) && $goods->hasoption) {
            $optionModel = new GoodsOption();
            $totalStock = $optionModel->where('goods_id', $goods->id)->where($whereField, 1)->sum($sumField);
            $salesRatio = round($activity->sold / ($activity->sold + $totalStock), 2) * 100;
        } else {
            $salesRatio = round($activity->sold / ($activity->sold + $activity->stock), 2) * 100;
        }
        return $salesRatio;
    }

    /**
     * @description 获取商品的格式化库存数据
     * @param int $goodsId 商品ID
     * @param string $type 商品类型：seckill-秒杀，integral-积分，assemble-拼团，normal-普通商品
     */
    public function getFormatSkuList($goodsId, $type = 'normal')
    {
        $goodsOptionModel = new GoodsOption();
        $goodsSpecItemModel = new GoodsSpecItem();
        $skuList = array();
        switch ($type) {
            case 'normal':
                break;
            case 'seckill':
                $where['is_seckill'] = 1;
                break;
            case 'integral':
                $where['is_integral'] = 1;
                break;
            case 'assemble':
                $where['is_assemble'] = 1;
                break;
        }
        $goodsOptionList = $goodsOptionModel->where('goods_id', $goodsId)->where($where)->select();
        foreach ($goodsOptionList as $k => $v) {
            $skuList[$k]['id'] = $v['id'];
            $specItemArr = array();
            $specItemIdArr = explode('_', $v['specs']);
            foreach ($specItemIdArr as $v2) {
                $specItemTitle = $goodsSpecItemModel->where('id', $v2)->value('title');
                array_push($specItemArr, $specItemTitle);
                unset($specItemTitle);
            }
            $skuList[$k]['specs'] = $specItemArr;

        }
        return $skuList;
    }

    public function getShopGoods($shopId, $num)
    {
        $goodsList = $this->field('id,goods_name,thumb_url,shop_price,min_price')
            ->where('shop_id', $shopId)
            ->where('is_recommend', 1)
            ->where('checked', 1)
            ->where('onsale', 1)
            ->where('is_recycle', 0)
            ->order('is_hot DESC,id DESC')
            ->limit($num)
            ->select();
        $webUrl = get_config_value('weburl');
        foreach ($goodsList as $k => $v) {
            $goodsList[$k]['thumb_url'] = url_format($v['thumb_url'], $webUrl);
        }
        return $goodsList;
    }

}