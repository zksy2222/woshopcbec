<?php

namespace app\admin\controller;

use app\admin\controller\Common;
use app\admin\model\distributionCommissonDetail;
use think\Db;

class Goods extends Common
{
    public function lst()
    {
        $shopId = session('shop_id');

        $filter = input('filter');
        if (!$filter || !in_array($filter, array(1, 2, 3))) {
            $filter = 3;
        }

        $where              = array();
        $where['a.shop_id'] = $shopId;

        $list     = Db::name('goods')
                      ->alias('a')
                      ->field('a.id,a.goods_name,a.thumb_url,a.market_price,a.shop_price,a.onsale,a.is_live,b.cate_name')
                      ->join('sp_category b', 'a.cate_id = b.id', 'LEFT')
                      ->where($where)
                      ->where('a.is_recycle', 0)
                      ->order('a.id desc')
                      ->paginate(25);
        $page     = $list->render();
        $cateres  = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
        $brandres = Db::name('brand')->field('id,brand_name')->select();

        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pnum', $pnum);
        $this->assign('filter', $filter);
        $this->assign('cateres', recursive($cateres));
        $this->assign('brandres', $brandres);
        if (request()->isAjax()) {
            return $this->fetch('ajaxpage');
        } else {
            return $this->fetch('lst');
        }
    }

    public function catelist()
    {
        if (input('cate_id')) {
            $cid    = input('cate_id');
            $filter = input('filter');
            if (!$filter || !in_array($filter, array(1, 2, 3))) {
                $filter = 3;
            }

            $shopId = session('shop_id');

            $where                 = array();
            $where['a.shop_id']    = $shopId;
            $where['a.is_recycle'] = 0;



            $cate_name          = Db::name('category')->where('id', $cid)->value('cate_name');
            $cateres            = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
            $cateId             = array();
            $cateId             = get_all_child($cateres, $cid);
            $cateId[]           = $cid;
            $cateId             = implode(',', $cateId);
            $where['a.cate_id'] = array('in', $cateId);
            $list               = Db::name('goods')
                                    ->alias('a')
                                    ->field('a.id,a.goods_name,a.thumb_url,a.market_price,a.shop_price,a.onsale,b.cate_name,c.brand_name')
                                    ->join('sp_category b', 'a.cate_id = b.id', 'LEFT')
                                    ->join('sp_brand c', 'a.brand_id = c.id', 'LEFT')
                                    ->where($where)
                                    ->order('a.addtime desc')
                                    ->paginate(25);
            $page               = $list->render();

            $brandres = Db::name('brand')->field('id,brand_name')->select();

            if (input('page')) {
                $pnum = input('page');
            } else {
                $pnum = 1;
            }

            $this->assign('cate_id', $cid);
            $this->assign('cate_name', $cate_name);
            $this->assign('list', $list);
            $this->assign('page', $page);
            $this->assign('pnum', $pnum);
            $this->assign('filter', $filter);
            $this->assign('cateres', recursive($cateres));
            $this->assign('brandres', $brandres);
            if (request()->isAjax()) {
                return $this->fetch('ajaxpage');
            } else {
                return $this->fetch('lst');
            }
        } else {
            $this->error('缺少参数');
        }
    }

    public function shopCateList(){
        if(input('cate_id')){
            $shopId = session('shop_id');
            $cid = input('cate_id');

            $cates = Db::name('shop_cate')->where('id',$cid)->where('shop_id',$shopId)->find();
            if($cates){
                $filter = input('filter');
                if(!$filter || !in_array($filter, array(1,2,3))){
                    $filter = 3;
                }

                $where = array();
                $where['a.shop_id'] = $shopId;
                $where['a.is_recycle'] = 0;


                $cate_name = $cates['cate_name'];
                $cateres = Db::name('shop_cate')->where('shop_id',$shopId)->field('id,cate_name,pid')->order('sort asc')->select();
                $cateId = array();
                $cateId = get_all_child($cateres, $cid);
                $cateId[] = $cid;
                $cateId = implode(',', $cateId);
                $where['a.shcate_id'] = array('in',$cateId);
                $list = Db::name('goods')
                          ->alias('a')
                          ->field('a.id,a.goods_name,a.thumb_url,a.market_price,a.shop_price,a.onsale,a.checked,b.cate_name')
                          ->join('sp_shop_cate b','a.shcate_id = b.id','LEFT')
                          ->where($where)
                          ->order('a.addtime desc')
                          ->paginate(25);
                $page = $list->render();

                if(input('page')){
                    $pnum = input('page');
                }else{
                    $pnum = 1;
                }

                $this->assign('cate_id',$cid);
                $this->assign('cate_name',$cate_name);
                $this->assign('list',$list);
                $this->assign('page',$page);
                $this->assign('pnum',$pnum);
                $this->assign('filter',$filter);
                $this->assign('cateres',recursive($cateres));
                if(request()->isAjax()){
                    return $this->fetch('ajaxpage');
                }else{
                    return $this->fetch('lst');
                }
            }else{
                $this->error('参数错误');
            }
        }else{
            $this->error('缺少参数');
        }
    }

    //商品回收站
    public function hslst()
    {
        $where                 = array();
        $where['a.shop_id']    = session('shop_id');
        $where['a.is_recycle'] = 1;
        $where['a.onsale']     = 0;
        $list                  = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.thumb_url,a.market_price,a.shop_price,a.onsale,b.cate_name')->join('sp_category b', 'a.cate_id = b.id', 'LEFT')->where($where)->order('a.addtime desc')->paginate(25);
        $page                  = $list->render();
        $cateres               = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
        $brandres              = Db::name('brand')->field('id,brand_name')->select();

        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pnum', $pnum);
        $this->assign('cateres', recursive($cateres));
        $this->assign('brandres', $brandres);
        if (request()->isAjax()) {
            return $this->fetch('hsajaxpage');
        } else {
            return $this->fetch('hslst');
        }
    }

    //修改特价、新品、热销、推荐
    public function gaibian()
    {
        $shopId = session('shop_id');
        $id      = input('post.id');
        $name    = input('post.name');
        $value   = input('post.value');
        if ($name && $name == 'onsale') {
            if (isset($value) && in_array($value, array(0, 1))) {
                $goods = Db::name('goods')->where('id', $id)->where('shop_id', $shopId)->where('is_recycle', 0)->where('checked', 1)->field('id,cate_id,brand_id')->find();
                if ($goods) {
                    $data[$name]     = $value;
                    $data['shop_id'] = $shopId;
                    $data['id']      = $id;

                    // 启动事务
                    Db::startTrans();
                    try {
                        Db::name('goods')->update($data);

                        if ($name == "onsale" && $value == 1) {
                            $ymanages = Db::name('shop_management')->where('shop_id', $shopId)->where('cate_id', $goods['cate_id'])->find();
                            if (!$ymanages) {
                                Db::name('shop_management')->insert(array('shop_id' => $shopId, 'cate_id' => $goods['cate_id']));
                            }

                            $yrbrands = Db::name('shop_managebrand')->where('shop_id', $shopId)->where('brand_id', $goods['brand_id'])->find();
                            if (!$yrbrands) {
                                Db::name('shop_managebrand')->insert(array('shop_id' => $shopId, 'brand_id' => $goods['brand_id']));
                            }

                            ys_admin_logs('设为上架', 'goods', $id);
                        } elseif ($name == "onsale" && $value == 0) {
                            $ymanages = Db::name('shop_management')->where('shop_id', $shopId)->where('cate_id', $goods['cate_id'])->find();
                            if ($ymanages) {
                                $good_manages = Db::name('goods')->where('shop_id', $shopId)->where('cate_id', $goods['cate_id'])->where('onsale', 1)->where('is_recycle', 0)->field('id')->find();
                                if (!$good_manages) {
                                    Db::name('shop_management')->where('id', $ymanages['id'])->delete();
                                }
                            }

                            $yrbrands = Db::name('shop_managebrand')->where('shop_id', $shopId)->where('brand_id', $goods['brand_id'])->find();
                            if ($yrbrands) {
                                $good_brands = Db::name('goods')->where('shop_id', $shopId)->where('brand_id', $goods['brand_id'])->where('onsale', 1)->where('is_recycle', 0)->field('id')->find();
                                if (!$good_brands) {
                                    Db::name('shop_managebrand')->where('id', $yrbrands['id'])->delete();
                                }
                            }
                            ys_admin_logs('设为下架', 'goods', $id);
                        }
                        // 提交事务
                        Db::commit();
                        $result = 1;
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $result = 0;
                    }
                } else {
                    $result = 0;
                }
            } else {
                $result = 0;
            }
        } elseif ($name && $name == 'is_live') {
            $data[$name]     = $value;
            $data['shop_id'] = $shopId;
            $data['id']      = $id;
            $result          = Db::name('goods')->update($data);
        } else {
            $result = 0;
        }
        return json($result);
    }

    //设置默认商品属性
    public function progaibian()
    {
        $shopId  = session('shop_id');
        $id       = input('post.id');
        $name     = input('post.name');
        $value    = input('post.value');
        $goodsId = input('post.goods_id');
        $goods    = Db::name('goods')->where('id', $goodsId)->where('shop_id', $shopId)->where('is_recycle', 0)->field('id')->find();
        if ($goods) {
            $products = Db::name('product')->where('id', $id)->where('goods_id', $goodsId)->find();
            if ($products) {
                if ($value == 1) {
                    // 启动事务
                    Db::startTrans();
                    try {
                        Db::name('product')->where('goods_id', $goodsId)->where('def', 1)->update(array('def' => 0));
                        Db::name('product')->where('id', $id)->where('goods_id', $goodsId)->update(array('def' => 1));
                        // 提交事务
                        Db::commit();
                        ys_admin_logs('设为默认商品库存', 'product', $id);
                        $result = 1;
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $result = 0;
                    }
                } elseif ($value == 0) {
                    $count = Db::name('product')->where('id', $id)->where('goods_id', $goodsId)->update(array('def' => 0));
                    if ($count > 0) {
                        ys_admin_logs('撤销默认商品库存', 'product', $id);
                        $result = 1;
                    } else {
                        $result = 0;
                    }
                }
            } else {
                $result = 0;
            }
        } else {
            $result = 0;
        }
        return json($result);
    }

    public function deleteone()
    {
        $shopId = session('shop_id');

        if (input('post.ypic_id') && input('post.goods_id')) {
            $goods = Db::name('goods')->where('id', input('post.goods_id'))->where('shop_id', $shopId)->field('id')->find();
            if ($goods) {
                $pics = Db::name('goods_pic')->where('id', input('post.ypic_id'))->where('goods_id', input('post.goods_id'))->field('id,img_url')->find();
                if ($pics) {
                    $count = Db::name('goods_pic')->delete(input('post.ypic_id'));
                    if ($count > 0) {
                        if (!empty($pics['img_url']) && file_exists('./' . $pics['img_url'])) {
                            @unlink('./' . $pics['img_url']);
                        }
                        $value = 1;
                    } else {
                        $value = 0;
                    }
                } else {
                    $value = 0;
                }
            } else {
                $value = 0;
            }
        } else {
            $value = 0;
        }
        return json($value);
    }

    public function add()
    {
        $shopId  = session('shop_id');
        $admin_id = session('admin_id');

        $cateres    = Db::name('category')->field('id,cate_name,tjgd,pid')->order('sort asc')->select();
        $langs      = Db::name('lang')->order('id asc')->select();
        $shcateres  = Db::name('shop_cate')->where('shop_id', $shopId)->field('id,cate_name,pid')->order('sort asc')->select();
        $levres     = Db::name('member_level')->field('id,level_name')->order('id asc')->select();
        $sertionres = Db::name('sertion')->where('shop_id',$shopId)->where('is_show', 1)->field('id,ser_name')->order('sort asc')->select();
        if (input('cate_id')) {
            $this->assign('cate_id', input('cate_id'));
        }
        $dispatchData = Db::name('dispatch')->where('shop_id',$shopId)->order('sort DESC,id DESC')->select();
        $areas = get_areas();

        $this->assign('cateres', recursive($cateres));
        $this->assign('langs', $langs);
        $this->assign('shcateres', recursive($shcateres));
        $this->assign('levres', $levres);
        $this->assign('sertionres', $sertionres);
        $this->assign('dispatchData', $dispatchData);
        $this->assign('areas', $areas);
        return $this->fetch('post');
    }

    public function getshuxingLst()
    {
        if (request()->isPost()) {
            if (input('post.typeid') && input('post.cate_id')) {
                $typeId  = input('post.typeid');
                $cate_id = input('post.cate_id');

                $cates = Db::name('category')->where('id', $cate_id)->find();
                if ($cates) {
                    $gdtypes = Db::name('type')->where('id', $typeId)->find();
                    if ($gdtypes && $typeId == $cates['type_id']) {
                        $attrres = Db::name('attr')->where('type_id', $typeId)->order('sort asc')->select();
                    } else {
                        $attrres = '';
                    }
                } else {
                    $attrres = '';
                }
            } else {
                $attrres = '';
            }
            return json($attrres);
        }
    }

    public function getAttrLst()
    {
        if (input('post.type_id') && input('post.id')) {
            if (input('post.cate_id')) {
                $shopId = session('shop_id');
                $type_id = input('post.type_id');
                $id      = input('post.id');
                $cate_id = input('post.cate_id');

                $cates = Db::name('category')->where('id', $cate_id)->find();
                if ($cates) {
                    $gdtypes = Db::name('type')->where('id', $type_id)->find();

                    if ($gdtypes && $type_id == $cates['type_id']) {
                        $goods = Db::name('goods')->where('id', $id)->where('shop_id', $shopId)->where('is_recycle', 0)->field('id')->find();
                        if ($goods) {
                            $attrres = Db::name('attr')->where('type_id', $type_id)->order('sort asc')->select();
                            $arr     = Db::name('goods_attr')->where('goods_id', $id)->select();
                            $gares   = array();
                            if ($arr) {
                                foreach ($arr as $k => $v) {
                                    $gares[$v['attr_id']][] = $v;
                                }
                            }
                            $value = array('attrres' => $attrres, 'gares' => $gares);
                        } else {
                            $value = '';
                        }
                    } else {
                        $value = '';
                    }
                } else {
                    $value = '';
                }
            } else {
                $value = '';
            }
        } else {
            $value = '';
        }
        return json($value);
    }

    public function edit()
    {
        if (input('id')) {
            $shopId  = session('shop_id');
            $admin_id = session('admin_id');
            $goodss   = Db::name('goods')->where('id', input('id'))->where('shop_id', $shopId)->where('is_recycle', 0)->find();
            if ($goodss) {
                $langs      = Db::name('lang')->order('id asc')->select();
                $goodsLangs = Db::name('goods_lang')->where('goods_id',$goodss['id'])->select();
                $cateres    = Db::name('category')->field('id,cate_name,tjgd,pid')->order('sort asc')->select();
                $shcateres  = Db::name('shop_cate')->where('shop_id', $shopId)->field('id,cate_name,pid')->order('sort asc')->select();
                $brandres   = Db::name('brand')->where('find_in_set(' . $goodss['cate_id'] . ',cate_id_list)')->field('id,brand_name')->select();
                $levres     = Db::name('member_level')->field('id,level_name')->order('id asc')->select();
                $sertionres = Db::name('sertion')->where('shop_id',$shopId)->where('is_show', 1)->field('id,ser_name')->order('sort asc')->select();
                $params = Db::name('goods_param')->where('goods_id', input('id'))->order('sort ASC')->select();

                $types = Db::name('type')->where('id', $goodss['type_id'])->field('id,type_name')->find();

                $goodpicres = Db::name('goods_pic')->where('goods_id', input('id'))->order('sort asc')->select();
                $mpres      = Db::name('member_price')->where('goods_id', input('id'))->select();


                $specs    = array();
                $allspecs = Db::name('goods_spec')->where('goods_id', input('id'))->order('sort ASC')->select();

                foreach ($allspecs as &$s) {
                    $s['items'] = Db::name('goods_spec_item')->where('spec_id', $s['id'])->select();
                }
                unset($s);

                $html    = '';
                $options = Db::name('goods_option')->where('goods_id', input('id'))->order('id asc')->select();

                if (0 < count($options)) {
                    $specitemids = explode('_', $options[0]['specs']);

                    foreach ($specitemids as $itemid) {
                        foreach ($allspecs as $ss) {
                            $items = $ss['items'];

                            foreach ($items as $it) {
                                if ($it['id'] == $itemid) {
                                    $specs[] = $ss;
                                    break;
                                }
                            }
                        }
                    }

                    $html     = '';
                    $html     .= '<table class="table table-bordered table-condensed">';
                    $html     .= '<thead>';
                    $html     .= '<tr class="active">';
                    $len      = count($specs);
                    $newlen   = 1;
                    $h        = array();
                    $rowspans = array();
                    $i        = 0;
//dump($specs);die;
                    while ($i < $len) {
                        $html    .= '<th>' . $specs[$i]['title'] . '</th>';
                        $itemlen = count($specs[$i]['items']);

                        if ($itemlen <= 0) {
                            $itemlen = 1;
                        }

                        $newlen *= $itemlen;
                        $h      = array();
                        $j      = 0;

                        while ($j < $newlen) {
                            $h[$i][$j] = array();
                            ++$j;
                        }

                        $l            = count($specs[$i]['items']);
                        $rowspans[$i] = 1;
                        $j            = $i + 1;

                        while ($j < $len) {
                            $rowspans[$i] *= count($specs[$j]['items']);
                            ++$j;
                        }

                        ++$i;
                    }

                    $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">库存</div><div class="input-group"><input type="text" class="form-control input-sm option_stock_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_stock\');"></a></span></div></div></th>';

                    $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">市场价格</div><div class="input-group"><input type="text" class="form-control  input-sm option_marketprice_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_marketprice\');"></a></span></div></div></th>';
                    $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">销售价格</div><div class="input-group"><input type="text" class="form-control input-sm option_productprice_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_productprice\');"></a></span></div></div></th>';

                    $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">编码</div><div class="input-group"><input type="text" class="form-control input-sm option_goodssn_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_goodssn\');"></a></span></div></div></th>';
                    $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">条码</div><div class="input-group"><input type="text" class="form-control input-sm option_productsn_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_productsn\');"></a></span></div></div></th>';
                    $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">重量（克）</div><div class="input-group"><input type="text" class="form-control input-sm option_weight_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_weight\');"></a></span></div></div></th>';


                    $html .= '</tr></thead>';
                    $m    = 0;

                    while ($m < $len) {
                        $k   = 0;
                        $kid = 0;
                        $n   = 0;
                        $j   = 0;

                        while ($j < $newlen) {
                            $rowspan = $rowspans[$m];

                            if (($j % $rowspan) == 0) {
                                $h[$m][$j] = array('html' => '<td class=\'full\' rowspan=\'' . $rowspan . '\'>' . $specs[$m]['items'][$kid]['title'] . '</td>', 'id' => $specs[$m]['items'][$kid]['id']);
                            } else {
                                $h[$m][$j] = array('html' => '', 'id' => $specs[$m]['items'][$kid]['id']);
                            }

                            ++$n;

                            if ($n == $rowspan) {
                                ++$kid;

                                if ((count($specs[$m]['items']) - 1) < $kid) {
                                    $kid = 0;
                                }

                                $n = 0;
                            }

                            ++$j;
                        }

                        ++$m;
                    }

                    $hh = '';
                    $i  = 0;

                    while ($i < $newlen) {
                        $hh  .= '<tr>';
                        $ids = array();
                        $j   = 0;

                        while ($j < $len) {
                            $hh    .= $h[$j][$i]['html'];
                            $ids[] = $h[$j][$i]['id'];
                            ++$j;
                        }

                        $ids = implode('_', $ids);
                        $val = array('id' => '', 'title' => '', 'stock' => '', 'productprice' => '', 'marketprice' => '', 'weight' => '', 'virtual' => '');

                        foreach ($options as $o) {
                            if ($ids === $o['specs']) {
                                $val = array('id' => $o['id'], 'title' => $o['title'], 'stock' => $o['stock'], 'productprice' => $o['shop_price'], 'marketprice' => $o['market_price'], 'goodssn' => $o['goods_sn'], 'productsn' => $o['product_sn'], 'weight' => $o['weight']);
                                unset($temp);
                                break;
                            }
                        }

                        $hh .= '<td>';
                        $hh .= '<input data-name="option_stock_' . $ids . '"  type="text" class="form-control option_stock option_stock_' . $ids . '" value="' . $val['stock'] . '"/>';
                        $hh .= '</td>';
                        $hh .= '<input data-name="option_id_' . $ids . '"  type="hidden" class="form-control option_id option_id_' . $ids . '" value="' . $val['id'] . '"/>';
                        $hh .= '<input data-name="option_ids"  type="hidden" class="form-control option_ids option_ids_' . $ids . '" value="' . $ids . '"/>';
                        $hh .= '<input data-name="option_title_' . $ids . '"  type="hidden" class="form-control option_title option_title_' . $ids . '" value="' . $val['title'] . '"/>';
                        $hh .= '<td><input data-name="option_marketprice_' . $ids . '" type="text" class="form-control option_marketprice option_marketprice_' . $ids . '" value="' . $val['marketprice'] . '"/></td>';
                        $hh .= '<td><input data-name="option_productprice_' . $ids . '" type="text" class="form-control option_productprice option_productprice_' . $ids . '" " value="' . $val['productprice'] . '"/></td>';

                        $hh .= '<td><input data-name="option_goodssn_' . $ids . '" type="text" class="form-control option_goodssn option_goodssn_' . $ids . '" " value="' . $val['goodssn'] . '"/></td>';
                        $hh .= '<td><input data-name="option_productsn_' . $ids . '" type="text" class="form-control option_productsn option_productsn_' . $ids . '" " value="' . $val['productsn'] . '"/></td>';
                        $hh .= '<td><input data-name="option_weight_' . $ids . '" type="text" class="form-control option_weight option_weight_' . $ids . '" " value="' . $val['weight'] . '"/></td>';
                        $hh .= '</tr>';

                        ++$i;
                    }

                    $html .= $hh;
                    $html .= '</table>';

                }

                $dispatchData = Db::name('dispatch')->where('shop_id',$shopId)->order('sort DESC,id DESC')->select();
                $areas = get_areas();
                $this->assign('dispatchData', $dispatchData);
                $this->assign('areas', $areas);
                $this->assign('html', $html);
                $this->assign('allspecs', $allspecs);

                if (input('s')) {
                    $this->assign('search', input('s'));
                }

                $this->assign('pnum', input('page'));
                $this->assign('filter', input('filter'));

                if (input('cate_id')) {
                    $this->assign('cate_id', input('cate_id'));
                }
                $this->assign('langs', $langs);
                $this->assign('goodsLangs', $goodsLangs);
                $this->assign('cateres', recursive($cateres));
                $this->assign('shcateres', recursive($shcateres));
                $this->assign('brandres', $brandres);
                $this->assign('levres', $levres);
                $this->assign('sertionres', $sertionres);
                $this->assign('params', $params);
                $this->assign('goodpicres', $goodpicres);
                $this->assign('mpres', $mpres);
                $this->assign('goodss', $goodss);
                $this->assign('item', $goodss);
                return $this->fetch('post');
            } else {
                $this->error('找不到相关信息');
            }
        } else {
            $this->error('缺少参数');
        }
    }

    //根据分类获取品牌和类型
    public function getbrandtype()
    {
        if (request()->isPost()) {
            $cate_id = input('post.cate_id');
            if ($cate_id) {
                $cates = Db::name('category')->where('id', $cate_id)->field('id,type_id,pid')->find();
                if ($cates) {
                    $brandres = Db::name('brand')->where('find_in_set(' . $cate_id . ',cate_id_list)')->field('id,brand_name')->select();
                    $types    = Db::name('type')->where('id', $cates['type_id'])->field('id,type_name')->find();
                } else {
                    $brandres = '';
                    $types    = '';
                }
            } else {
                $brandres = '';
                $types    = '';
            }
            if (empty($brandres) && empty($types)) {
                $value = array();
            } else {
                $value = array('brandres' => $brandres, 'types' => $types);
            }
            return json($value);
        }
    }


    //商品单选属性库存列表
    public function product()
    {
        if (request()->isPost()) {
            $data    = input('post.');
            $shopId = session('shop_id');
            if (!empty($data['goods_number']) && !empty($data['goods_id'])) {
                $goods_number = $data['goods_number'];
                $goodsId     = $data['goods_id'];
                $goodsinfo    = Db::name('goods')->where('id', $goodsId)->where('shop_id', $shopId)->where('is_recycle', 0)->find();
                if ($goodsinfo) {
                    if (!empty($data['product_id'])) {
                        $product_id = $data['product_id'];
                    }

                    if (!empty($data['goods_attr'])) {
                        $goods_attr = $data['goods_attr'];
                        $zyzarr     = array();
                        foreach ($goods_number as $yk => $yv) {
                            if (isset($yv) && preg_match("/^[0-9]+$/", $yv)) {
                                $yzarr = array();
                                foreach ($goods_attr as $key => $val) {
                                    if (empty($val[$yk])) {
                                        $value = array('status' => 0, 'mess' => '有商品属性为空，保存失败');
                                        return json($value);
                                    } else {
                                        $yzshuxings = Db::name('goods_attr')->alias('a')->field('a.id')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.id', $val[$yk])->where('a.goods_id', $goodsId)->where('b.attr_type', 1)->find();
                                        if ($yzshuxings) {
                                            $yzarr[] = $val[$yk];
                                        } else {
                                            $value = array('status' => 0, 'mess' => '有商品属性参数错误，保存失败');
                                            return json($value);
                                        }
                                    }
                                }
                                if (!empty($yzarr)) {
                                    $yzarr    = implode(',', $yzarr);
                                    $zyzarr[] = $yzarr;
                                }
                            } else {
                                $value = array('status' => 0, 'mess' => '有库存为空或不为数字，保存失败');
                                return json($value);
                            }
                        }

                        if (count($zyzarr) != count(array_unique($zyzarr))) {
                            $value = array('status' => 0, 'mess' => '存在相同的商品属性组合库存，保存失败');
                            return json($value);
                        }

                        foreach ($goods_number as $k => $v) {
                            if (empty($v) || !preg_match("/^[0-9]+$/", $v)) {
                                $v = 0;
                            }

                            $goodsAttr = array();
                            foreach ($goods_attr as $key => $val) {
                                if (empty($val[$k])) {
                                    continue 2;
                                }
                                $goodshuxings = Db::name('goods_attr')->alias('a')->field('a.id')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.id', $val[$k])->where('a.goods_id', $goodsId)->where('b.attr_type', 1)->find();
                                if ($goodshuxings) {
                                    $goodsAttr[] = $val[$k];
                                } else {
                                    continue 2;
                                }
                            }
                            if (!empty($goodsAttr)) {
                                $goodsAttr = implode(',', $goodsAttr);
                                // 启动事务
                                Db::startTrans();
                                try {
                                    if (!empty($product_id[$k])) {
                                        $product1 = Db::name('product')->where('id', $product_id[$k])->where('goods_id', $goodsId)->find();
                                        $product2 = Db::name('product')->where('id', 'neq', $product_id[$k])->where('goods_attr', $goodsAttr)->where('goods_id', $goodsId)->find();
                                        if ($product1 && !$product2) {
                                            Db::name('product')->where('id', $product_id[$k])->where('goods_id', $goodsId)->update(array('goods_attr' => $goodsAttr, 'goods_number' => $v));
                                            ys_admin_logs('保存商品库存', 'product', $product_id[$k]);
                                        }
                                    } else {
                                        $products = Db::name('product')->where('goods_attr', $goodsAttr)->where('goods_id', $goodsId)->find();
                                        if (!$products) {
                                            $kc_id = Db::name('product')->insertGetId(array('goods_attr' => $goodsAttr, 'goods_number' => $v, 'goods_id' => $goodsId, 'shop_id' => $shopId));
                                            ys_admin_logs('保存商品库存', 'product', $kc_id);
                                        }
                                    }
                                    // 提交事务
                                    Db::commit();
                                    $value = array('status' => 1, 'mess' => '保存成功');
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status' => 0, 'mess' => '保存失败');
                                }
                            }
                        }
                    } else {
                        foreach ($goods_number as $k => $v) {
                            if (empty($v) || !preg_match("/^[0-9]+$/", $v)) {
                                $v = 0;
                            }

                            // 启动事务
                            Db::startTrans();
                            try {
                                if (!empty($product_id[$k])) {
                                    $products = Db::name('product')->where('id', $product_id[$k])->where('goods_id', $goodsId)->find();
                                    if ($products) {
                                        Db::name('product')->where('id', $product_id[$k])->where('goods_id', $goodsId)->update(array('goods_number' => $v));
                                        ys_admin_logs('保存商品库存', 'product', $product_id[$k]);
                                    }
                                } else {
                                    $products = Db::name('product')->where('goods_id', $goodsId)->find();
                                    if (!$products) {
                                        $kc_id = Db::name('product')->insertGetId(array('goods_attr' => '', 'goods_number' => $v, 'goods_id' => $goodsId, 'shop_id' => $shopId));
                                        ys_admin_logs('保存商品库存', 'product', $kc_id);
                                    }
                                }
                                // 提交事务
                                Db::commit();
                                $value = array('status' => 1, 'mess' => '保存成功');
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                $value = array('status' => 0, 'mess' => '保存失败');
                            }
                        }
                    }
                } else {
                    $value = array('status' => 0, 'mess' => '找不到相关商品信息，保存失败');
                }
            } else {
                $value = array('status' => 0, 'mess' => '保存失败');
            }
            return json($value);
        } else {
            $id        = input('id');
            $shopId   = session('shop_id');
            $goodsinfo = Db::name('goods')->where('id', input('id'))->where('shop_id', $shopId)->where('is_recycle', 0)->find();
            if ($goodsinfo) {
                $_radioAttrRes = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id,a.attr_value,b.attr_name,b.attr_type')->join('sp_attr b', 'a.attr_id = b.id', 'INNER')->where('a.goods_id', $id)->where('b.attr_type', 1)->select();

                $radioAttrRes = array();
                if ($_radioAttrRes) {
                    foreach ($_radioAttrRes as $v) {
                        $radioAttrRes[$v['attr_id']][] = $v;
                    }
                }

                $goods_name = Db::name('goods')->where('id', input('id'))->value('goods_name');
                $prores     = Db::name('product')->where('goods_id', input('id'))->select();
                if (input('pnum')) {
                    $this->assign('pnum', input('pnum'));
                }
                $this->assign('prores', $prores);
                $this->assign('goods_name', $goods_name);
                $this->assign('goods_id', input('id'));
                $this->assign('radioAttrRes', $radioAttrRes);
                return $this->fetch();
            } else {
                $this->error('找不到相关信息');
            }
        }
    }


    //删除商品属性
    public function deletega()
    {
        if (request()->isPost()) {
            if (input('post.id') && input('post.goods_id')) {
                $shopId   = session('shop_id');
                $id        = input('post.id');
                $goodsId  = input('post.goods_id');
                $goodsinfo = Db::name('goods')->where('id', $goodsId)->where('shop_id', $shopId)->where('is_recycle', 0)->field('id')->find();
                if ($goodsinfo) {
                    //活动信息
                    $huodong   = 0;
                    $activitys = Db::name('seckill')->where('goods_id', $goodsId)->where('checked', 'neq', 2)->where('is_show', 1)->where('end_time', 'gt', time())->field('id,goods_attr,price')->order('price asc')->find();
                    if (!$activitys) {
                        $activitys = Db::name('group_buy')->where('goods_id', $goodsId)->where('checked', 'neq', 2)->where('is_show', 1)->where('end_time', 'gt', time())->field('id,goods_attr,price')->order('price asc')->find();
                        if (!$activitys) {
                            $activitys = Db::name('assemble')->where('goods_id', $goodsId)->where('checked', 'neq', 2)->where('is_show', 1)->where('end_time', 'gt', time())->field('id,goods_attr,price')->order('price asc')->find();
                            if ($activitys) {
                                $huodong = 3;
                            }
                        } else {
                            $huodong = 2;
                        }
                    } else {
                        $huodong = 1;
                    }

                    if ($huodong) {
                        switch ($huodong) {
                            case 1:
                                $value = array('status' => 0, 'mess' => '商品已参与秒杀活动，活动期间不允许删除商品规格属性');
                                break;
                            case 2:
                                $value = array('status' => 0, 'mess' => '商品已参与团购活动，活动期间不允许删除商品规格属性');
                                break;
                            case 3:
                                $value = array('status' => 0, 'mess' => '商品已参与拼团活动，活动期间不允许删除商品规格属性');
                                break;
                        }
                        return json($value);
                    }

                    $pro = Db::name('product')->where('goods_id', $goodsId)->where('find_in_set(' . $id . ',goods_attr)')->field('id')->limit(1)->find();
                    if ($pro) {
                        $value = array('status' => 0, 'mess' => '该商品库存中正在使用此商品属性，删除失败');
                    } else {
                        // 启动事务
                        Db::startTrans();
                        try {
                            Db::name('goods_attr')->where('id', $id)->where('goods_id', $goodsId)->delete();
                            //更新商品属性集合
                            $goods_shuxing = Db::name('goods_attr')->where('goods_id', $goodsId)->field('attr_id,attr_value')->select();
                            $shuxings      = '';
                            foreach ($goods_shuxing as $kcv => $gcv) {
                                if ($kcv == 0) {
                                    $shuxings = $gcv['attr_id'] . ':' . $gcv['attr_value'];
                                } else {
                                    $shuxings = $shuxings . ',' . $gcv['attr_id'] . ':' . $gcv['attr_value'];
                                }
                            }
                            Db::name('goods')->update(array('id' => $goodsId, 'shuxings' => $shuxings));
                            // 提交事务
                            Db::commit();
                            $value = array('status' => 1, 'mess' => '删除成功');
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                            $value = array('status' => 0, 'mess' => '删除失败');
                        }
                    }
                } else {
                    $value = array('status' => 0, 'mess' => '商品信息有误，删除失败');
                }
            } else {
                $value = array('status' => 0, 'mess' => '缺少参数，删除失败');
            }
            return json($value);
        }
    }

    //删除库存信息
    public function delproduct()
    {
        if (request()->isPost()) {
            if (input('post.id') && input('post.goods_id')) {
                $shopId   = session('shop_id');
                $id        = input('post.id');
                $goodsId  = input('post.goods_id');
                $goodsinfo = Db::name('goods')->where('id', $goodsId)->where('shop_id', $shopId)->where('is_recycle', 0)->field('id')->find();
                if ($goodsinfo) {
                    $products = Db::name('product')->where('id', $id)->where('goods_id', $goodsId)->find();
                    if ($products) {
                        $count = Db::name('product')->where('id', $id)->where('goods_id', $goodsId)->delete();
                        if ($count > 0) {
                            ys_admin_logs('删除商品库存', 'product', $id);
                            $value = array('status' => 1, 'mess' => '删除成功');
                        } else {
                            $value = array('status' => 0, 'mess' => '删除失败');
                        }
                    } else {
                        $value = array('status' => 0, 'mess' => '删除失败');
                    }
                } else {
                    $value = array('status' => 0, 'mess' => '商品信息有误，删除失败');
                }
            } else {
                $value = array('status' => 0, 'mess' => '缺少参数，删除失败');
            }
            return json($value);
        }
    }

    //放入回收站
    public function recycle()
    {
        $id      = input('id');
        $shopId = session('shop_id');
        if (!empty($id) && !is_array($id)) {
            $goods = Db::name('goods')->where('id', $id)->where('shop_id', $shopId)->where('is_recycle', 0)->find();
            if ($goods) {
                // 启动事务
                Db::startTrans();
                try {
                    Db::name('goods')->where('id', $id)->update(array('is_recycle' => 1, 'onsale' => 0));
                    $ymanages = Db::name('shop_management')->where('shop_id', $shopId)->where('cate_id', $goods['cate_id'])->find();
                    if ($ymanages) {
                        $good_manages = Db::name('goods')->where('shop_id', $shopId)->where('cate_id', $goods['cate_id'])->where('onsale', 1)->where('is_recycle', 1)->field('id')->find();
                        if (!$good_manages) {
                            Db::name('shop_management')->where('id', $ymanages['id'])->delete();
                        }
                    }

                    $yrbrands = Db::name('shop_managebrand')->where('shop_id', $shopId)->where('brand_id', $goods['brand_id'])->find();
                    if ($yrbrands) {
                        $good_brands = Db::name('goods')->where('shop_id', $shopId)->where('brand_id', $goods['brand_id'])->where('onsale', 1)->field('id')->find();
                        if (!$good_brands) {
                            Db::name('shop_managebrand')->where('id', $yrbrands['id'])->delete();
                        }
                    }

//                    Db::name('shops')->where('id', $shopId)->setDec('goods_num', 1);
                    // 提交事务
                    Db::commit();
                    ys_admin_logs('商品加入回收站', 'goods', $id);
                    $value = array('status' => 1, 'mess' => '加入回收站成功');
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    dump($e->getMessage());
                    die;
                    $value = array('status' => 0, 'mess' => '加入回收站失败');
                }

            } else {
                $value = array('status' => 0, 'mess' => '找不到相关信息');
            }
        } else {
            $value = array('status' => 0, 'mess' => '未选中任何数据');
        }
        return json($value);
    }

    //批量放入回收站
    public function batchRecycle()
    {
        $ids  = input('get.');
        $shopId = session('shop_id');
        if (!empty($ids) && is_array($ids['ids'])) {
            $goods = Db::name('goods')->where('id','in', $ids['ids'])->where('shop_id', $shopId)->where('is_recycle', 0)->select();
            if (count($ids['ids']) == count($goods)) {
                // 启动事务
                Db::startTrans();
                try {
                    foreach ($ids['ids'] as $k => $v){
                        Db::name('goods')->where('id', $v)->update(array('is_recycle' => 1, 'onsale' => 0));
                        $ymanages = Db::name('shop_management')->where('shop_id', $shopId)->where('cate_id', $v['cate_id'])->find();
                        if ($ymanages) {
                            $good_manages = Db::name('goods')->where('shop_id', $shopId)->where('cate_id', $v['cate_id'])->where('onsale', 1)->where('is_recycle', 1)->field('id')->find();
                            if (!$good_manages) {
                                Db::name('shop_management')->where('id', $ymanages['id'])->delete();
                            }
                        }

                        $yrbrands = Db::name('shop_managebrand')->where('shop_id', $shopId)->where('brand_id', $v['brand_id'])->find();
                        if ($yrbrands) {
                            $good_brands = Db::name('goods')->where('shop_id', $shopId)->where('brand_id', $v['brand_id'])->where('onsale', 1)->field('id')->find();
                            if (!$good_brands) {
                                Db::name('shop_managebrand')->where('id', $yrbrands['id'])->delete();
                            }
                        }
                    }


//                    Db::name('shops')->where('id', $shopId)->setDec('goods_num', 1);
                    // 提交事务
                    Db::commit();
                    ys_admin_logs('商品批量加入回收站', 'goods', $ids['ids']);
                    $value = array('status' => 1, 'mess' => '批量加入回收站成功');
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    dump($e->getMessage());
                    die;
                    $value = array('status' => 0, 'mess' => '加入回收站失败');
                }

            } else {
                $value = array('status' => 0, 'mess' => '找不到相关信息');
            }
        } else {
            $value = array('status' => 0, 'mess' => '未选中任何数据');
        }
        return json($value);
    }

    //取出回收站商品
    public function recovery()
    {
        $id      = input('id');
        $shopId = session('shop_id');
        if (!empty($id) && !is_array($id)) {
            $goods = Db::name('goods')->where('id', $id)->where('shop_id', $shopId)->where('is_recycle', 1)->where('onsale', 0)->find();
            if ($goods) {
                if ($goods['checked'] == 1) {
                    // 启动事务
                    Db::startTrans();
                    try {
                        Db::name('goods')->where('id', $id)->update(array('is_recycle' => 0));

                        $ymanages = Db::name('shop_management')->where('shop_id', $shopId)->where('cate_id', $goods['cate_id'])->find();
                        if (!$ymanages) {
                            Db::name('shop_management')->insert(array('shop_id' => $shopId, 'cate_id' => $goods['cate_id']));
                        }

                        $yrbrands = Db::name('shop_managebrand')->where('shop_id', $shopId)->where('brand_id', $goods['brand_id'])->find();
                        if (!$yrbrands) {
                            Db::name('shop_managebrand')->insert(array('shop_id' => $shopId, 'brand_id' => $goods['brand_id']));
                        }

                        Db::name('shops')->where('id', $shopId)->setInc('goods_num', 1);
                        // 提交事务
                        Db::commit();
                        ys_admin_logs('商品从回收站恢复', 'goods', $id);
                        $value = array('status' => 1, 'mess' => '恢复商品成功');
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $value = array('status' => 0, 'mess' => '恢复商品失败');
                    }
                } else {
                    $value = array('status' => 0, 'mess' => '违规商品不可恢复');
                }
            } else {
                $value = array('status' => 0, 'mess' => '找不到相关信息');
            }
        } else {
            $value = array('status' => 0, 'mess' => '未选中任何数据');
        }
        return json($value);
    }

    //删除商品
    public function delete()
    {
        if (input('post.id')) {
            $id = array_filter(explode(',', input('post.id')));
        } else {
            $id = input('id');
        }

        $shopId = session('shop_id');
        if (!empty($id)) {
            if (!is_array($id)) {
                $goods = Db::name('goods')->where('id', $id)->where('shop_id', $shopId)->where('is_recycle', 1)->where('onsale', 0)->find();
                if ($goods) {
                    $good_picres = Db::name('goods_pic')->where('goods_id', $id)->field('id,img_url')->select();
                    $attr_picres = Db::name('goods_attr')->where('goods_id', $id)->field('id,attr_pic')->select();

                    // 启动事务
                    Db::startTrans();
                    try {
                        Db::name('goods')->where('id', $id)->delete();
                        Db::name('goods_attr')->where('goods_id', $id)->delete();
                        Db::name('product')->where('goods_id', $id)->delete();

                        if ($goods['thumb_url'] && file_exists('./' . $goods['thumb_url'])) {
                            @unlink('./' . $goods['thumb_url']);
                        }

                        if ($good_picres) {
                            foreach ($good_picres as $v) {
                                if ($v['img_url'] && file_exists('./' . $v['img_url'])) {
                                    @unlink('./' . $v['img_url']);
                                }
                            }
                        }

                        if ($attr_picres) {
                            foreach ($attr_picres as $val) {
                                if ($val['attr_pic'] && file_exists('./' . $val['attr_pic'])) {
                                    @unlink('./' . $val['attr_pic']);
                                }
                            }
                        }

                        // 提交事务
                        Db::commit();
                        ys_admin_logs('删除商品成功', 'goods', $id);
                        $value = array('status' => 1, 'mess' => '删除商品成功');
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $value = array('status' => 0, 'mess' => '删除商品失败');
                    }
                } else {
                    $value = array('status' => 0, 'mess' => '找不到相关信息');
                }
            } else {
                $idarr = $id;
                foreach ($idarr as $vd) {
                    $goods = Db::name('goods')->where('id', $vd)->where('shop_id', $shopId)->where('is_recycle', 1)->where('onsale', 0)->find();
                    if ($goods) {
                        $good_picres = Db::name('goods_pic')->where('goods_id', $vd)->field('id,img_url')->select();
                        $attr_picres = Db::name('goods_attr')->where('goods_id', $vd)->field('id,attr_pic')->select();

                        // 启动事务
                        Db::startTrans();
                        try {
                            Db::name('goods')->where('id', $vd)->delete();
                            Db::name('goods_attr')->where('goods_id', $vd)->delete();
                            Db::name('product')->where('goods_id', $vd)->delete();

                            if ($goods['thumb_url'] && file_exists('./' . $goods['thumb_url'])) {
                                @unlink('./' . $goods['thumb_url']);
                            }

                            if ($good_picres) {
                                foreach ($good_picres as $v) {
                                    if ($v['img_url'] && file_exists('./' . $v['img_url'])) {
                                        @unlink('./' . $v['img_url']);
                                    }
                                }
                            }

                            if ($attr_picres) {
                                foreach ($attr_picres as $val) {
                                    if ($val['attr_pic'] && file_exists('./' . $val['attr_pic'])) {
                                        @unlink('./' . $val['attr_pic']);
                                    }
                                }
                            }

                            // 提交事务
                            Db::commit();
                            ys_admin_logs('删除商品成功', 'goods', $vd);
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                        }
                    }
                }
                $value = array('status' => 1, 'mess' => '删除商品成功');
            }
        } else {
            $value = array('status' => 0, 'mess' => '未选中任何数据');
        }
        return json($value);
    }


    //搜索商品
    public function search()
    {
        $shopId = session('shop_id');

        if (input('post.keyword') != '') {
            cookie('goods_name', input('post.keyword'), 3600);
        }

        if (input('post.cate_id') != '') {
            cookie('goods_cate_id', input('post.cate_id'), 3600);
        }

        if (input('post.brand_id') != '') {
            cookie('goods_brand_id', input('post.brand_id'), 3600);
        }

        if (input('post.attr') != '') {
            cookie('goods_attr', input('post.attr'), 3600);
        }

        if (input('post.onsale') != '') {
            cookie('goods_onsale', input('post.onsale'), 3600);
        }


        $cateres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();

        $where                 = array();
        $where['a.shop_id']    = $shopId;
        $where['a.is_recycle'] = 0;

        if (cookie('goods_name')) {
            $where['a.goods_name'] = array('like', '%' . cookie('goods_name') . '%');
        }

        if (cookie('goods_cate_id') != '') {
            //(int)将cookie字符串强制转换成整型
            $cid = (int)cookie('goods_cate_id');
            if ($cid != 0) {
                $cateId             = array();
                $cateId             = get_all_child($cateres, $cid);
                $cateId[]           = $cid;
                $cateId             = implode(',', $cateId);
                $where['a.cate_id'] = array('in', $cateId);
            }
        }

        if (cookie('goods_brand_id') != '') {
            //(int)将cookie字符串强制转换成整型
            $bid = (int)cookie('goods_brand_id');
            if ($bid != 0) {
                $where['a.brand_id'] = $bid;
            }
        }

        if (cookie('goods_attr') != '') {
            if (cookie('goods_attr') != '0') {
                $where['a.' . cookie('goods_attr')] = 1;
            }
        }

        if (cookie('goods_onsale') != '') {
            //(int)将cookie字符串强制转换成整型
            $sale = (int)cookie('goods_onsale');
            if ($sale != 0) {
                if ($sale == 1) {
                    $where['a.onsale'] = 1;
                } elseif ($sale == 2) {
                    $where['a.onsale'] = 0;
                }
            }
        }

        $list = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.thumb_url,a.market_price,a.shop_price,a.onsale,b.cate_name')->join('sp_category b', 'a.cate_id = b.id', 'LEFT')->where($where)->order('a.id desc')->paginate(25);
        $page = $list->render();

        $brandres = Db::name('brand')->field('id,brand_name')->select();

        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $search = 1;

        $filter = 3;

        if (cookie('goods_name')) {
            $this->assign('goods_name', cookie('goods_name'));
        }
        $this->assign('cate_id', $cid);
        $this->assign('brand_id', $bid);
        $this->assign('attr', cookie('goods_attr'));
        $this->assign('onsale', $sale);
        $this->assign('cateres', recursive($cateres));
        $this->assign('brandres', $brandres);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pnum', $pnum);
        $this->assign('search', $search);
        $this->assign('filter', $filter);
        if (request()->isAjax()) {
            return $this->fetch('ajaxpage');
        } else {
            return $this->fetch('lst');
        }
    }


    //搜索商品
    public function hssearch()
    {
        $shopId = session('shop_id');

        if (input('post.keyword') != '') {
            cookie('hsgoods_name', input('post.keyword'), 3600);
        } else {
            cookie('hsgoods_name', null);
        }

        if (input('post.cate_id') != '') {
            cookie('hsgoods_cate_id', input('post.cate_id'), 3600);
        }

        if (input('post.brand_id') != '') {
            cookie('hsgoods_brand_id', input('post.brand_id'), 3600);
        }

        $cateres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();

        $where                 = array();
        $where['a.shop_id']    = $shopId;
        $where['a.is_recycle'] = 1;
        $where['a.onsale']     = 0;

        if (cookie('hsgoods_name')) {
            $where['a.goods_name'] = array('like', '%' . cookie('hsgoods_name') . '%');
        }

        if (cookie('hsgoods_cate_id') != '') {
            //(int)将cookie字符串强制转换成整型
            $cid = (int)cookie('hsgoods_cate_id');
            if ($cid != 0) {
                $cateId             = array();
                $cateId             = get_all_child($cateres, $cid);
                $cateId[]           = $cid;
                $cateId             = implode(',', $cateId);
                $where['a.cate_id'] = array('in', $cateId);
            }
        }

        if (cookie('hsgoods_brand_id') != '') {
            //(int)将cookie字符串强制转换成整型
            $bid = (int)cookie('hsgoods_brand_id');
            if ($bid != 0) {
                $where['a.brand_id'] = $bid;
            }
        }

        $list = Db::name('goods')->alias('a')->field('a.id,a.goods_name,a.thumb_url,a.market_price,a.shop_price,a.onsale,b.cate_name')->join('sp_category b', 'a.cate_id = b.id', 'LEFT')->where($where)->order('a.addtime desc')->paginate(25);
        $page = $list->render();

        $brandres = Db::name('brand')->field('id,brand_name')->select();

        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $search = 1;

        if (cookie('hsgoods_name')) {
            $this->assign('goods_name', cookie('hsgoods_name'));
        }
        $this->assign('cate_id', $cid);
        $this->assign('brand_id', $bid);
        $this->assign('cateres', recursive($cateres));
        $this->assign('brandres', $brandres);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pnum', $pnum);
        $this->assign('search', $search);
        if (request()->isAjax()) {
            return $this->fetch('hsajaxpage');
        } else {
            return $this->fetch('hslst');
        }
    }

//批量修改分类
    public function setcate()
    {
        if (request()->isAjax()) {
            $data  = input('post.');
            $ids   = explode(',', $data['ids']);
            $goods = db('goods');
            // 启动事务
            Db::startTrans();
            try{
                if (!empty($data['cate_id'])) {
                    $goods->where('id','in',$ids)->update(['cate_id' => $data['cate_id']]);
                }
                if (!empty($data['shcate_id'])) {
                    $goods->where('id','in',$ids)->update(['shcate_id' => $data['shcate_id']]);
                }
                // 提交事务
                Db::commit();
                datamsg(1,'批量修改成功');
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                datamsg(0,'批量修改失败');
            }

        }
        $cateres   = Db::name('category')->field('id,cate_name,tjgd,pid')->order('sort asc')->select();
        $shcateres = Db::name('shop_cate')->where('shop_id', 1)->field('id,cate_name,pid')->order('sort asc')->select();
        $this->assign('cateres', recursive($cateres));
        $this->assign('shcateres', recursive($shcateres));
        return $this->fetch();
    }

    public function tpl()
    {
        $tpl    = input('param.tpl');
        $specid = input('param.specid');
        $title  = input('param.title');

        if ($tpl == 'option') {
            $tag = get_random_string(32);
            return $this->fetch('goods/tpl/option');
        } else if ($tpl == 'spec') {
            $spec = array('id' => get_random_string(32), 'title' => $title);
            $this->assign('spec', $spec);
            return $this->fetch('goods/tpl/spec');
        } else if ($tpl == 'specitem') {
            $spec     = array('id' => $specid);
            $specItem = array('id' => get_random_string(32), 'title' => $title, 'show' => 1);
            $this->assign('spec', $spec);
            $this->assign('specitem', $specItem);
            return $this->fetch('goods/tpl/spec_item');
        } else {
            if ($tpl == 'param') {
                $tag = get_random_string(32);
                return $this->fetch('goods/tpl/param');
            }
        }
    }

    public function post()
    {
        if (request()->isPost()) {
            $shopId           = session('shop_id');
            $data              = input('post.');
            $lang = db('lang')->select();
            foreach ($lang as $k => $v){
                if(!empty($data['goods_name_'.$v['lang_code']])){
                    $data['goods_name'] = $data['goods_name_'.$v['lang_code']];
                    break;
                }

            }
            foreach ($lang as $k => $v){
                if(!empty($data['goods_desc_'.$v['lang_code']])){
                    $data['goods_desc'] = $data['goods_desc_'.$v['lang_code']];
                    break;
                }
            }

            if (isset($data['id']) && $data['id'] > 0) {
                $goodsId = $data['id'];
                $isEdit = 1;
            }else{
                $isEdit = 0;
            }
            $data['shop_id']   = $shopId;
            $data['hasoption'] = input('post.hasoption', 0);
            if(input('post.id')){
                $result            = $this->validate($data, 'Goods.edit');
            }else{
                $result            = $this->validate($data, 'Goods');
            }
            if (true !== $result) {
                $value = array('status' => 0, 'mess' => $result);
                return json($value);
            }


            if($isEdit){
                $goodss = Db::name('goods')->where('id', input('post.id'))->where('shop_id', $shopId)->where('is_recycle', 0)->find();
                if (!$goodss) {
                    $value = array('status' => 0, 'mess' => '找不到相关信息，编辑失败');
                    return json($value);
                }
                if ($goodss['checked'] == 2 && $data['onsale'] == 1) {
                    $value = array('status' => 0, 'mess' => '违规商品不可上架');
                    return json($value);
                }

                //活动信息
                $huodong   = 0;
                $activitys = Db::name('seckill')->where('goods_id', $goodss['id'])->where('checked', 'neq', 2)->where('is_show', 1)->where('end_time', 'gt', time())->field('id,goods_attr,price')->order('price asc')->find();
                if (!$activitys) {
                    $activitys = Db::name('group_buy')->where('goods_id', $goodss['id'])->where('checked', 'neq', 2)->where('is_show', 1)->where('end_time', 'gt', time())->field('id,goods_attr,price')->order('price asc')->find();
                    if (!$activitys) {
                        $activitys = Db::name('assemble')->where('goods_id', $goodss['id'])->where('checked', 'neq', 2)->where('is_show', 1)->where('end_time', 'gt', time())->field('id,goods_attr,price')->order('price asc')->find();
                        if ($activitys) {
                            $huodong = 3;
                        }
                    } else {
                        $huodong = 2;
                    }
                } else {
                    $huodong = 1;
                }
            }


            $categorys = Db::name('category')->where('id', $data['cate_id'])->find();
            if (!$categorys) {
                $value = array('status' => 0, 'mess' => '参数错误');
                return json($value);
            }
            $child_cates = Db::name('category')->where('pid', $data['cate_id'])->find();
            if ($child_cates) {
                $value = array('status' => 0, 'mess' => '商品分类存在下级分类，编辑失败');
            }

            $shcates = Db::name('shop_cate')->where('id', $data['shcate_id'])->where('shop_id', $shopId)->find();
            if (!$shcates) {
                $value = array('status' => 0, 'mess' => '店铺分类参数错误');
            }
            $child_shcates = Db::name('shop_cate')->where('pid', $data['shcate_id'])->find();
            if ($child_shcates) {
                $value = array('status' => 0, 'mess' => '店铺分类存在下级分类，编辑失败');
            }
            if (!empty($data['brand_id'])) {
                $brands = Db::name('brand')->where('id', $data['brand_id'])->where('find_in_set(' . $data['cate_id'] . ',cate_id_list)')->field('id')->find();
                if (!$brands) {
                    $value = array('status' => 0, 'mess' => '品牌参数错误，编辑失败');
                    return json($value);
                }
            }


            if ($goodss['shop_price'] != $data['shop_price']) {
                if ($huodong) {
                    switch ($huodong) {
                        case 1:
                            $value = array('status' => 0, 'mess' => '商品已参与秒杀活动，活动期间不允许修改商品价格');
                            break;
                        case 2:
                            $value = array('status' => 0, 'mess' => '商品已参与团购活动，活动期间不允许修改商品价格');
                            break;
                        case 3:
                            $value = array('status' => 0, 'mess' => '商品已参与拼团活动，活动期间不允许修改商品价格');
                            break;
                    }
                    return json($value);
                }
            }

            if (!empty($data['goods_thumb'])) {
                $data['thumb_url'] = $data['goods_thumb'];
            } else {
                $data['thumb_url'] = $goodss['thumb_url'];
            }


            if (!empty($data['ypic_id'])) {
                $count1 = Db::name('goods_pic')->where('goods_id', $goodsId)->where('id', 'in', $data['ypic_id'])->count();
            } else {
                $count1 = 0;
            }

            if (!empty($data['goods_pics'])) {
                $count2 = count($data['goods_pics']);
            } else {
                $count2 = 0;
            }

            $countnum = $count1 + $count2;

            $webconfig = $this->webconfig;

            if ($countnum > $webconfig['goodsimg_maxnum']) {
                $value = array('status' => 0, 'mess' => '商品图片最多上传' . $webconfig['goodsimg_maxnum'] . '张');
                return json($value);
            }

            if (!empty($data['fuwu'])) {
                $fuwures = $data['fuwu'];
                if (is_array($fuwures)) {
                    foreach ($fuwures as $vur) {
                        $sertions = Db::name('sertion')->where('shop_id',$shopId)->where('id', $vur)->where('is_show', 1)->find();
                        if (!$sertions) {
                            $value = array('status' => 0, 'mess' => '所选服务项信息错误');
                            return json($value);
                        }
                    }
                    $data['fuwu'] = implode(',', $fuwures);
                } else {
                    $value = array('status' => 0, 'mess' => '所选服务项参数错误');
                    return json($value);
                }
            } else {
                $data['fuwu'] = '';
            }


            $data['search_keywords'] = str_replace('，', ',', $data['search_keywords']);

            // 启动事务
            Db::startTrans();
            try {
                if (input('post.id')) {
                    Db::name('goods')->update(array(
                        'id'              => $goodsId,
                        'goods_name'      => $data['goods_name'],
                        'thumb_url'       => $data['thumb_url'],
                        'market_price'    => $data['market_price'],
                        'shop_price'      => $data['shop_price'],
                        'onsale'          => 0,
                        'cate_id'         => $data['cate_id'],
                        'brand_id'        => $data['brand_id'],
                        'goods_desc'      => $data['goods_desc'],
                        'search_keywords' => $data['search_keywords'],
                        'keywords'        => $data['keywords'],
                        'goods_brief'     => $data['goods_brief'],
                        'fuwu'            => $data['fuwu'],
                        'is_recycle'      => $data['is_recycle'],
                        'is_special'      => 0,
                        'is_new'          => 0,
                        'is_hot'          => 0,
                        'is_recommend'    => 0,
                        'is_live'         => 0,
                        'shcate_id'       => 0,
                        'hasoption'       => $data['hasoption'],
                        'goods_sn'        => $data['goods_sn'],
                        'product_sn'      => $data['product_sn'],
                        'weight'          => $data['weight'],
                        'total'           => $data['total'],
                        'is_send_free'    => $data['is_send_free'],
                        'dispatch_type'   => $data['dispatch_type'],
                        'dispatch_id'     => $data['dispatch_id'],
                        'dispatch_price'  => $data['dispatch_price'],
                        'ednum'           => $data['ednum'],
                        'edmoney'         => $data['edmoney'],
                        'edareas'         => $data['edareas'],
                        'edareas_code'    => $data['edareas_code'],
                    ));
                    $goodsLangDb = db('goods_lang');
                    $langs = db('lang')->select();
                    foreach ($langs as $k => $v) {
                        $goodsLangData = [];
                        $goodsLangData['goods_id'] = $goodsId;
                        $goodsLangData['lang_id']  = $v['id'];
                        $goodsLangData['goods_name'] = $data['goods_name_'. $v['lang_code']];
                        $goodsLangData['goods_desc'] = $data['goods_desc_'. $v['lang_code']];
                        // 判断商品多语言设置是否存在，没有就新增，存在就更新
                        $hasGoodsLang = $goodsLangDb->where(['goods_id'=>$goodsId,'lang_id'=>$v['id']])->find();
                        if($hasGoodsLang){
                            $goodsLangDb->where(['goods_id'=>$goodsId,'lang_id'=>$v['id']])->update($goodsLangData);
                        }else{
                            $goodsLangDb->insertGetId($goodsLangData);
                        }
                    }

                }else{
                    if($shopId == 1){
                        $leixing = 1;
                    }else{
                        $leixing = 2;
                    }

                    $goodsId = Db::name('goods')->insertGetId(array(
                        'goods_name'      => $data['goods_name'],
                        'thumb_url'       => $data['thumb_url'],
                        'market_price'    => $data['market_price'],
                        'shop_price'      => $data['shop_price'],
                        'onsale'          => 0,
                        'cate_id'         => $data['cate_id'],
                        'brand_id'        => intval($data['brand_id']),
                        'type_id'         => $data['type_id'],
                        'search_keywords' => $data['search_keywords'],
                        'goods_desc'      => $data['goods_desc'],
                        'keywords'        => $data['keywords'],
                        'goods_brief'     => $data['goods_brief'],
                        'fuwu'            => $data['fuwu'],
                        'addtime'         => time(),
                        'is_recycle'      => $data['is_recycle'],
                        'is_special'      => 0,
                        'is_new'          => 0,
                        'is_hot'          => 0,
                        'is_recommend'    => 0,
                        'is_live'         => 0,
                        'checked'         => 1,
                        'shcate_id'       => 0,
                        'hasoption'       => $data['hasoption'],
                        'goods_sn'        => $data['goods_sn'],
                        'product_sn'      => $data['product_sn'],
                        'weight'          => $data['weight'],
                        'total'           => $data['total'],
                        'leixing'         => $leixing,
                        'shop_id'         => $shopId,
                        'is_send_free'    => $data['is_send_free'],
                        'dispatch_type'   => $data['dispatch_type'],
                        'dispatch_id'     => $data['dispatch_id'],
                        'dispatch_price'  => $data['dispatch_price'],
                        'ednum'           => $data['ednum'],
                        'edmoney'         => $data['edmoney'],
                        'edareas'         => $data['edareas'],
                        'edareas_code'    => $data['edareas_code'],
                    ));

                    //添加多语言商品名称和商品详情
                    $goodsLangDb = db('goods_lang');
                    $langs = db('lang')->select();

                    foreach ($langs as $k => $v){
                        $goodsLangData = [];
                        $goodsLangData['goods_id'] = $goodsId;
                        $goodsLangData['lang_id']  = $v['id'];
                        $goodsLangData['goods_name'] = $data['goods_name_'. $v['lang_code']];
                        if(!empty($data['goods_desc_'. $v['lang_code']])){
                            $goodsLangData['goods_desc'] = $data['goods_desc_'. $v['lang_code']];
                        }
                        $goodsLangDb->insertGetId($goodsLangData);
                    }
                }

            //编辑商品图片
            if (!empty($data['ypic_id'])) {
                $sort2 = $data['sort2'];
                foreach ($data['ypic_id'] as $keypic => $valpic) {
                    if (empty($sort2[$keypic])) {
                        $sort2[$keypic] = 0;
                    }
                    $goodspics = Db::name('goods_pic')->where('id', $valpic)->where('goods_id', $goodsId)->find();
                    if ($goodspics) {
                        Db::name('goods_pic')->where('id', $valpic)->where('goods_id', $goodsId)->update(array('sort' => $sort2[$keypic]));
                    }
                }
            }

            if (!empty($data['goods_pics'])) {
                $sort3 = $data['sort3'];
                foreach ($data['goods_pics'] as $key => $val) {
                    if (empty($sort3[$key])) {
                        $sort3[$key] = 0;
                    }
                    $img_url = $val;
                    Db::name('goods_pic')->insert(array('img_url' => $img_url, 'sort' => $sort3[$key], 'goods_id' => $goodsId));
                }
            }

            $min_shop_price   = $data['shop_price'];
            $max_shop_price   = $data['shop_price'];
            $min_market_price = $data['market_price'];
            $max_market_price = $data['market_price'];

            $zs_price    = $min_shop_price;
            $is_activity = 0;

            //秒杀信息
            $rushs = Db::name('seckill')->where('goods_id', $goodsId)->where('checked', 1)->where('is_show', 1)->where('start_time', 'elt', time())->where('end_time', 'gt', time())->field('id,price')->order('price asc')->find();
            if ($rushs) {
                $zs_price    = $rushs['price'];
                $is_activity = 1;
            } else {
                //团购信息
                $groups = Db::name('group_buy')->where('goods_id', $goodsId)->where('checked', 1)->where('is_show', 1)->where('start_time', 'elt', time())->where('end_time', 'gt', time())->field('id,price')->order('price asc')->find();
                if ($groups) {
                    $zs_price    = $groups['price'];
                    $is_activity = 2;
                } else {
                    //拼团信息
                    $assembles = Db::name('assemble')->where('goods_id', $goodsId)->where('checked', 1)->where('is_show', 1)->where('start_time', 'elt', time())->where('end_time', 'gt', time())->field('id,price')->order('price asc')->find();
                    if ($assembles) {
                        $zs_price    = $assembles['price'];
                        $is_activity = 3;
                    }
                }
            }

            Db::name('goods')->update(array('min_market_price' => $min_market_price, 'max_market_price' => $max_market_price, 'min_price' => $min_shop_price, 'max_price' => $max_shop_price, 'zs_price' => $zs_price, 'is_activity' => $is_activity, 'id' => $goodsId));

            if ($data['cate_id'] != $goodss['cate_id']) {
                $managements = Db::name('shop_management')->where('shop_id', $shopId)->where('cate_id', $data['cate_id'])->find();
                if (!$managements) {
                    Db::name('shop_management')->insert(array('shop_id' => $shopId, 'cate_id' => $data['cate_id']));
                }

                $ymanages = Db::name('shop_management')->where('shop_id', $shopId)->where('cate_id', $goodss['cate_id'])->find();
                if ($ymanages) {
                    $good_manages = Db::name('goods')->where('shop_id', $shopId)->where('cate_id', $goodss['cate_id'])->where('onsale', 1)->where('is_recycle', 0)->field('id')->find();
                    if (!$good_manages) {
                        Db::name('shop_management')->where('id', $ymanages['id'])->delete();
                    }
                }
            } else {
                $ymanages = Db::name('shop_management')->where('shop_id', $shopId)->where('cate_id', $goodss['cate_id'])->find();
                if (!$ymanages) {
                    Db::name('shop_management')->insert(array('shop_id' => $shopId, 'cate_id' => $goodss['cate_id']));
                }
            }

            if($isEdit){
                if ($data['brand_id'] != $goodss['brand_id']) {
                    $managebrands = Db::name('shop_managebrand')->where('shop_id', $shopId)->where('brand_id', $data['brand_id'])->find();
                    if (!$managebrands) {
                        Db::name('shop_managebrand')->insert(array('shop_id' => $shopId, 'brand_id' => $data['brand_id']));
                    }

                    $yrbrands = Db::name('shop_managebrand')->where('shop_id', $shopId)->where('brand_id', $goodss['brand_id'])->find();
                    if ($yrbrands) {
                        $good_brands = Db::name('goods')->where('shop_id', $shopId)->where('brand_id', $goodss['brand_id'])->where('onsale', 1)->where('is_recycle', 0)->field('id')->find();
                        if (!$good_brands) {
                            Db::name('shop_managebrand')->where('id', $yrbrands['id'])->delete();
                        }
                    }
                } else {
                    $yrbrands = Db::name('shop_managebrand')->where('shop_id', $shopId)->where('brand_id', $goodss['brand_id'])->find();
                    if (!$yrbrands) {
                        Db::name('shop_managebrand')->insert(array('shop_id' => $shopId, 'brand_id' => $goodss['brand_id']));
                    }
                }
            }

            // 商品参数处理 START
            $paramIds = $_POST['param_id'];
            $paramTitles = $_POST['param_title'];
            $paramValues = $_POST['param_value'];
            $len = count($paramIds);
            $paramids = array();
            $k = 0;

            while ($k < $len) {
                $paramId = '';
                $getParamId = $paramIds[$k];
                $a = array( 'title' => $paramTitles[$k], 'value' => $paramValues[$k], 'sort' => $k, 'goods_id' => $goodsId);

                if (!is_numeric($getParamId)) {
                    $paramId = Db::name('goods_param')->insertGetId($a);
                }
                else {
                    Db::name('goods_param')->where('id', $getParamId)->update($a);
                    $paramId = $getParamId;
                }

                $paramids[] = $paramId;
                ++$k;
            }

            if (0 < count($paramids)) {
                $sql = 'delete from '  . config('database.prefix') . 'goods_param where goods_id=' . $goodsId . ' and id not in ( ' . implode(',', $paramids) . ')';
                Db::execute($sql);
            }
            else {
                $sql = 'delete from '  . config('database.prefix') . 'goods_param where goods_id=' . $goodsId;
                Db::execute($sql);
            }
            // 商品参数处理 END

            // 商品规格 处理 START
            $totalstocks = 0;
            $spec_ids    = $_POST['spec_id'];
            $spec_titles = $_POST['spec_title'];
            $specids     = array();
            $len         = count($spec_ids);
            $spec_items  = array();
            $k           = 0;

            while ($k < $len) {
                $spec_id     = '';
                $get_spec_id = $spec_ids[$k];
                $a           = array('goods_id' => $goodsId, 'sort' => $k, 'title' => $spec_titles[$get_spec_id]);

                if (is_numeric($get_spec_id)) {
                    Db::name('goods_spec')->where('id', $get_spec_id)->update($a);
                    $spec_id = $get_spec_id;
                } else {
                    $spec_id = Db::name('goods_spec')->insertGetId($a);
                }

                $spec_item_ids    = $_POST['spec_item_id_' . $get_spec_id];
                $spec_item_titles = $_POST['spec_item_title_' . $get_spec_id];
                $spec_item_shows  = $_POST['spec_item_show_' . $get_spec_id];
                $spec_item_thumbs = $_POST['spec_item_thumb_' . $get_spec_id];

                $itemlen = count($spec_item_ids);
                $itemids = array();
                $n       = 0;

                while ($n < $itemlen) {
                    $item_id     = '';
                    $get_item_id = $spec_item_ids[$n];
                    $d           = array('spec_id' => $spec_id, 'sort' => $n, 'title' => $spec_item_titles[$n], 'show' => $spec_item_shows[$n], 'thumb' => $spec_item_thumbs[$n]);
                    $f           = 'spec_item_thumb_' . $get_item_id;

                    if (is_numeric($get_item_id)) {
                        Db::name('goods_spec_item')->where('id', $get_item_id)->update($d);
                        $item_id = $get_item_id;
                    } else {
                        $item_id = Db::name('goods_spec_item')->insertGetId($d);
                    }

                    $itemids[]    = $item_id;
                    $d['get_id']  = $get_item_id;
                    $d['id']      = $item_id;
                    $spec_items[] = $d;
                    ++$n;
                }

                if (0 < count($itemids)) {
                    Db::name('goods_spec_item')->where(['spec_id' => $spec_id, 'id' => ['not in', $itemids]])->delete();
                } else {
                    Db::name('goods_spec_item')->where(['spec_id' => $spec_id])->delete();
                }
                Db::name('goods_spec')->where('id', $spec_id)->update(array('content' => serialize($itemids)));
                $specids[] = $spec_id;
                ++$k;
            }

            if (0 < count($specids)) {
                Db::name('goods_spec')->where(['goods_id' => $goodsId, 'id' => ['not in', $specids]])->delete();
            } else {
                Db::name('goods_spec')->where(['goods_id' => $goodsId])->delete();
            }

            $optionArray = json_decode($_POST['optionArray'], true);

            $option_idss = $optionArray['option_ids'];
            $len         = count($option_idss);
            $optionids   = array();

            $k = 0;

            while ($k < $len) {
                $option_id     = '';
                $ids           = $option_idss[$k];
                $get_option_id = $optionArray['option_id'][$k];
                $idsarr        = explode('_', $ids);
                $newids        = array();

                foreach ($idsarr as $key => $ida) {
                    foreach ($spec_items as $it) {
                        if ($it['get_id'] == $ida) {
                            $newids[] = $it['id'];
                            break;
                        }
                    }
                }

                $newids = implode('_', $newids);
                $a      = array('title' => $optionArray['option_title'][$k], 'shop_price' => $optionArray['option_productprice'][$k], 'market_price' => $optionArray['option_marketprice'][$k], 'stock' => $optionArray['option_stock'][$k], 'weight' => $optionArray['option_weight'][$k], 'goods_sn' => $optionArray['option_goodssn'][$k], 'product_sn' => $optionArray['option_productsn'][$k], 'goods_id' => $goodsId, 'specs' => $newids);

                $totalstocks += $a['stock'];

                if (empty($get_option_id)) {
                    $option_id = Db::name('goods_option')->insertGetId($a);
                } else {
                    Db::name('goods_option')->where('id', $get_option_id)->update($a);
                    $option_id = $get_option_id;
                }

                $optionids[] = $option_id;

                ++$k;
            }

            if ((0 < count($optionids)) && ($data['hasoption'] !== 0)) {
                Db::name('goods_option')->where(['goods_id' => $goodsId, 'id' => ['not in', $optionids]])->delete();
                $minShopPrice   = Db::name('goods_option')->where('goods_id', $goodsId)->min('shop_price');
                $minMarketPrice = Db::name('goods_option')->where('goods_id', $goodsId)->min('market_price');
                $maxShopPrice   = Db::name('goods_option')->where('goods_id', $goodsId)->max('shop_price');
                $maxMarketPrice = Db::name('goods_option')->where('goods_id', $goodsId)->max('market_price');

                Db::name('goods')->where('id', $goodsId)->update([
                     'total'            => $totalstocks,
                     'min_price'        => $minShopPrice,
                     'min_market_price' => $minMarketPrice,
                     'max_price'        => $maxShopPrice,
                     'max_market_price' => $maxMarketPrice,
                    ]);

            } else {

                Db::name('goods_option')->where('goods_id', $goodsId)->delete();
                $sql = 'update ' . config('database.prefix') . 'goods set min_price = shop_price,min_market_price =  market_price,max_price = shop_price, max_market_price = market_price where id = ' . $goodsId . ' and hasoption=0;';
                Db::execute($sql);
            }

            // 商品规格处理 END

            //提交事务
            Db::commit();

            ys_admin_logs('编辑商品', 'goods', $goodsId);
            if($isEdit){
                $mess = '编辑成功';
            }else{
                $mess = '新增成功';
            }
            $value = array('status' => 1, 'mess' => $mess);
        } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                    if($isEdit){
                        $mess = '编辑失败';
                    }else{
                        $mess = '新增失败';
                }
                $value = array('status' => 0, 'mess' => $mess.$e->getMessage());
            }


            return json($value);
        }
    }

}

?>