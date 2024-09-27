<?php

namespace app\common\model;

use think\Db;
use think\Model;


class Goods extends Model
{
    public function getSeckillGoodsOptionHtml($goodsId){

        $specs    = array();
        $allspecs = Db::name('goods_spec')->where('goods_id', $goodsId)->order('sort ASC')->select();

        foreach ($allspecs as &$s) {
            $s['items'] = Db::name('goods_spec_item')->where('spec_id', $s['id'])->select();
        }
        unset($s);
        $html    = '';
        $options = Db::name('goods_option')->where('goods_id', $goodsId)->order('id asc')->select();

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
            $html     .= '<table class="table table-bordered table-condensed content-option">';
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
            $html .= '<th style="width:80px;text-align: center;">售卖价</th>';
            $html .= '<th style="width:80px;text-align: center;">总库存</th>';
            $html .= '<th style="width:80px;text-align: center;">参与秒杀 <input class="select_all" type="checkbox" value="1" /></th>';
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">秒杀库存</div><div class="input-group"><input type="text" class="form-control input-sm option_stock_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_stock\');"></a></span></div></div></th>';
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">秒杀价格</div><div class="input-group"><input type="text" class="form-control input-sm option_productprice_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_productprice\');"></a></span></div></div></th>';



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
                $hh  .= '<tr class="option-item">';
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
                        $val = array('id' => $o['id'], 'title' => $o['title'], 'stock' => $o['stock'], 'productprice' => $o['shop_price'], 'marketprice' => $o['market_price'], 'goodssn' => $o['goods_sn'], 'productsn' => $o['product_sn'], 'weight' => $o['weight'], 'is_seckill' => $o['is_seckill'], 'seckill_price' => $o['seckill_price'], 'seckill_stock'=>$o['seckill_stock']);
                        unset($temp);
                        break;
                    }
                }

                if($val['is_seckill']){
                    $checkStr = 'checked';
                }else{
                    $checkStr = '';
                }
                $hh .= '<td style="text-align: center;">'.$val['productprice'].'元</td>';
                $hh .= '<td style="text-align: center;">'.$val['stock'].'</td>';
                $hh .= '<td style="text-align: center;"><input type="checkbox" name="option_id_checked[]" '.$checkStr.' value="'.$val['id'].'"/></td>';
                $hh .= '<td>';
                $hh .= '<input data-name="option_stock_' . $ids . '"  type="text" class="form-control option_stock option_stock_' . $ids . '" value="' . $val['seckill_stock'] . '"/>';
                $hh .= '</td>';
                $hh .= '<input data-name="option_id_' . $ids . '"  type="hidden" class="form-control option_id option_id_' . $ids . '" value="' . $val['id'] . '"/>';
                $hh .= '<input data-name="option_ids"  type="hidden" class="form-control option_ids option_ids_' . $ids . '" value="' . $ids . '"/>';
                $hh .= '<input data-name="option_title_' . $ids . '"  type="hidden" class="form-control option_title option_title_' . $ids . '" value="' . $val['title'] . '"/>';

                $hh .= '<td><input data-name="option_productprice_' . $ids . '" type="text" class="form-control option_productprice option_productprice_' . $ids . '" " value="' . $val['seckill_price'] . '"/></td>';



                $hh .= '</tr>';

                ++$i;
            }

            $html .= $hh;
            $html .= '</table>';

        }
        return $html;
    }

    public function getAssembleGoodsOptionHtml($goodsId){

        $specs    = array();
        $allspecs = Db::name('goods_spec')->where('goods_id', $goodsId)->order('sort ASC')->select();

        foreach ($allspecs as &$s) {
            $s['items'] = Db::name('goods_spec_item')->where('spec_id', $s['id'])->select();
        }
        unset($s);
        $html    = '';
        $options = Db::name('goods_option')->where('goods_id', $goodsId)->order('id asc')->select();

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
            $html     .= '<table class="table table-bordered table-condensed content-option">';
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
            $html .= '<th style="width:80px;text-align: center;">售卖价</th>';
            $html .= '<th style="width:80px;text-align: center;">总库存</th>';
            $html .= '<th style="width:80px;text-align: center;">参与拼团 <input class="select_all" type="checkbox" value="1" /></th>';
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">拼团库存</div><div class="input-group"><input type="text" class="form-control input-sm option_stock_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_stock\');"></a></span></div></div></th>';
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">拼团价格</div><div class="input-group"><input type="text" class="form-control input-sm option_productprice_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_productprice\');"></a></span></div></div></th>';



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
                $hh  .= '<tr class="option-item">';
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
                        $val = array('id' => $o['id'], 'title' => $o['title'], 'stock' => $o['stock'], 'productprice' => $o['shop_price'], 'marketprice' => $o['market_price'], 'goodssn' => $o['goods_sn'], 'productsn' => $o['product_sn'], 'weight' => $o['weight'], 'is_assemble' => $o['is_assemble'], 'assemble_price' => $o['assemble_price'], 'assemble_stock'=>$o['assemble_stock']);
                        unset($temp);
                        break;
                    }
                }

                if($val['is_assemble']){
                    $checkStr = 'checked';
                }else{
                    $checkStr = '';
                }
                $hh .= '<td style="text-align: center;">'.$val['productprice'].'元</td>';
                $hh .= '<td style="text-align: center;">'.$val['stock'].'</td>';
                $hh .= '<td style="text-align: center;"><input type="checkbox" name="option_id_checked[]" '.$checkStr.' value="'.$val['id'].'"/></td>';
                $hh .= '<td>';
                $hh .= '<input data-name="option_stock_' . $ids . '"  type="text" class="form-control option_stock option_stock_' . $ids . '" value="' . $val['assemble_stock'] . '"/>';
                $hh .= '</td>';
                $hh .= '<input data-name="option_id_' . $ids . '"  type="hidden" class="form-control option_id option_id_' . $ids . '" value="' . $val['id'] . '"/>';
                $hh .= '<input data-name="option_ids"  type="hidden" class="form-control option_ids option_ids_' . $ids . '" value="' . $ids . '"/>';
                $hh .= '<input data-name="option_title_' . $ids . '"  type="hidden" class="form-control option_title option_title_' . $ids . '" value="' . $val['title'] . '"/>';

                $hh .= '<td><input data-name="option_productprice_' . $ids . '" type="text" class="form-control option_productprice option_productprice_' . $ids . '" " value="' . $val['assemble_price'] . '"/></td>';



                $hh .= '</tr>';

                ++$i;
            }

            $html .= $hh;
            $html .= '</table>';

        }
        return $html;
    }

    public function getIntegralGoodsOptionHtml($goodsId){

        $specs    = array();
        $allspecs = Db::name('goods_spec')->where('goods_id', $goodsId)->order('sort ASC')->select();

        foreach ($allspecs as &$s) {
            $s['items'] = Db::name('goods_spec_item')->where('spec_id', $s['id'])->select();
        }
        unset($s);
        $html    = '';
        $options = Db::name('goods_option')->where('goods_id', $goodsId)->order('id asc')->select();

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
            $html     .= '<table class="table table-bordered table-condensed content-option">';
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
            $html .= '<th style="width:80px;text-align: center;">售卖价</th>';
            $html .= '<th style="width:80px;text-align: center;">总库存</th>';
            $html .= '<th style="width:80px;text-align: center;">参与换购 <input class="select_all" type="checkbox" value="1" /></th>';
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">换购库存</div><div class="input-group"><input type="text" class="form-control input-sm option_stock_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_stock\');"></a></span></div></div></th>';
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">消耗积分</div><div class="input-group"><input type="text" class="form-control input-sm option_integral_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_integral\');"></a></span></div></div></th>';
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">+</div></div></th>';
            $html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">消耗金额</div><div class="input-group"><input type="text" class="form-control input-sm option_productprice_all"  VALUE=""/><span class="input-group-addon"><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_productprice\');"></a></span></div></div></th>';



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
                $hh  .= '<tr class="option-item">';
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
                        $val = array('id' => $o['id'], 'title' => $o['title'], 'stock' => $o['stock'], 'productprice' => $o['shop_price'], 'marketprice' => $o['market_price'], 'goodssn' => $o['goods_sn'], 'productsn' => $o['product_sn'], 'weight' => $o['weight'], 'is_integral' => $o['is_integral'], 'integral_price' => $o['integral_price'], 'integral_stock'=>$o['integral_stock'],'integral'=>$o['integral']);
                        unset($temp);
                        break;
                    }
                }

                if($val['is_integral']){
                    $checkStr = 'checked';
                }else{
                    $checkStr = '';
                }
                $hh .= '<td style="text-align: center;">'.$val['productprice'].'元</td>';
                $hh .= '<td style="text-align: center;">'.$val['stock'].'</td>';
                $hh .= '<td style="text-align: center;"><input type="checkbox" name="option_id_checked[]" '.$checkStr.' value="'.$val['id'].'"/></td>';
                $hh .= '<td>';
                $hh .= '<input data-name="option_stock_' . $ids . '"  type="text" class="form-control option_stock option_stock_' . $ids . '" value="' . $val['integral_stock'] . '"/>';
                $hh .= '</td>';
                $hh .= '<input data-name="option_id_' . $ids . '"  type="hidden" class="form-control option_id option_id_' . $ids . '" value="' . $val['id'] . '"/>';
                $hh .= '<input data-name="option_ids"  type="hidden" class="form-control option_ids option_ids_' . $ids . '" value="' . $ids . '"/>';
                $hh .= '<input data-name="option_title_' . $ids . '"  type="hidden" class="form-control option_title option_title_' . $ids . '" value="' . $val['title'] . '"/>';

                $hh .= '<td><input data-name="option_integral_' . $ids . '" type="text" class="form-control option_integral option_integral_' . $ids . '" " value="' . $val['integral'] . '"/></td>';
                $hh .= '<td style="text-align: center;">+</td>';
                $hh .= '<td><input data-name="option_productprice_' . $ids . '" type="text" class="form-control option_productprice option_productprice_' . $ids . '" " value="' . $val['integral_price'] . '"/></td>';



                $hh .= '</tr>';

                ++$i;
            }

            $html .= $hh;
            $html .= '</table>';

        }
        return $html;
    }
}