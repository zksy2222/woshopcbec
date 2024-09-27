<?php

namespace app\api\model;
use think\Model;

class Dispatch extends Model
{
    /**
     * 计算运费
     * @param type $param 重量或者是数量
     * @param type $d
     * @param type $calculatetype -1默认读取$d中的calculatetype值 1按数量计算运费 0按重量计算运费
     */
    public function getDispatchPrice($param, $d, $calculatetype = -1)
    {

        if (empty($d)) {
            return 0;
        }

        $price = 0;

        if ($calculatetype == -1) {
            $calculatetype = $d['calculate_type'];
        }

        if ($calculatetype == 1) { //按件计算
            if ($param <= $d['first_num']) {
                $price = floatval($d['first_num_price']);
            }
            else {
                $price = floatval($d['first_num_price']);
                $secondweight = $param - floatval($d['first_num']);
                $dsecondweight = (floatval($d['second_num']) <= 0 ? 1 : floatval($d['second_num']));
                $secondprice = 0;

                if (($secondweight % $dsecondweight) == 0) {
                    $secondprice = ($secondweight / $dsecondweight) * floatval($d['second_num_price']);
                }
                else {
                    $secondprice = ((int) ($secondweight / $dsecondweight) + 1) * floatval($d['second_num_price']);
                }

                $price += $secondprice;
            }
        }
        else if ($param <= $d['first_weight']) {
            if (0 <= $param) {
                $price = floatval($d['first_price']);
            }
            else {
                $price = 0;
            }
        }
        else {
            $price = floatval($d['first_price']);
            $secondweight = $param - floatval($d['first_weight']);
            $dsecondweight = (floatval($d['second_weight']) <= 0 ? 1 : floatval($d['second_weight']));
            $secondprice = 0;

            if (($secondweight % $dsecondweight) == 0) {
                $secondprice = ($secondweight / $dsecondweight) * floatval($d['second_price']);
            }
            else {
                $secondprice = ((int) ($secondweight / $dsecondweight) + 1) * floatval($d['second_price']);
            }

            $price += $secondprice;
        }

        return $price;
    }

    public function getCityDispatchPrice($areas, $address, $param, $d)
    {
        $address_datavalue = trim($address['datavalue']);
        if (is_array($areas) && (0 < count($areas))) {
            foreach ($areas as $area) {
                $citys_code = explode(';', $area['citys_code']);
                if (in_array($address_datavalue, $citys_code) && !empty($citys_code)) {
                    return $this->getDispatchPrice($param, $area, $d['calculate_type']);
                }
            }
        }

        return $this->getDispatchPrice($param, $d);
    }

    /**
     * 获取默认快递信息
     */
    public function getDefaultDispatch($shopId = 0)
    {
        $data = $this->where('is_default',1)->where('shop_id',$shopId)->where('enabled',1)->find();
        return $data;
    }

    /**
     * 获取最新的一条快递信息
     */
    public function getNewDispatch($shopId = 0)
    {
        $data = $this->where('shop_id',$shopId)->where('enabled',1)->order('id DESC')->find();
        return $data;
    }

    /**
     * 获取一条快递信息
     */
    public function getOneDispatch($id)
    {

        if ($id == 0) {
            $data = $this->where('is_default',1)->where('enabled',1)->find();
        }
        else {
            $data = $this->where('id',$id)->where('enabled',1)->find();
        }

        return $data;
    }

    public function getAllNoDispatchAreas($areas = array())
    {
        $dispatch_citys = array();
        if (!empty($areas)) {
            $areas = iunserializer($areas);
            if (!empty($areas)) {
                $dispatch_citys = explode(';', trim($areas, ';'));
            }
        }

        $citys = array();
        if (!empty($dispatch_citys)) {
            $citys = array_unique($dispatch_citys);
        }

        return $citys;
    }

    public function checkOnlyDispatchAreas($user_city_code, $dispatch_data)
    {
        $areas = $dispatch_data['no_dispatch_areas_code'];

        $isnoarea = 1;
        if (!empty($user_city_code) && !empty($areas)) {
            $areas = iunserializer($areas);
            $citys = explode(';', trim($areas, ';'));

            if (in_array($user_city_code, $citys)) {
                $isnoarea = 0;
            }
        }

        return $isnoarea;
    }

    public function getNoDispatchAreas($goods)
    {
        if (($goods['type'] == 2) || ($goods['type'] == 3)) {
            return '';
        }

        if ($goods['dispatch_type'] == 1) {
            $dispatchareas = $this->getAllNoDispatchAreas();
        }
        else {
            if (empty($goods['dispatch_id'])) {
                $dispatch = $this->getDefaultDispatch($goods['shop_id']);
            }
            else {
                $dispatch = $this->getOneDispatch($goods['dispatch_id']);
            }

            if (empty($dispatch)) {
                $dispatch = $this->getNewDispatch($goods['shop_id']);
            }

            if (empty($dispatch['isdispatcharea'])) {
                $onlysent = 0;
                $citys = $this->getAllNoDispatchAreas($dispatch['nodispatchareas']);
            }
            else {
                $onlysent = 1;
                $dispatchareas = iunserializer($dispatch['nodispatchareas']);
                $citys = explode(';', trim($dispatchareas, ';'));
            }
        }

        return array('onlysent' => $onlysent, 'citys' => $citys);
    }

    public function getOrderDispatchPrice($goods, $address)
    {
        $dispatch_price        = 0;

        $dispatch_array        = array();
        $dispatch_shop         = array();

        $total_array           = array();
        $totalprice_array      = array();
        $nodispatch_array      = array();

        $user_city             = '';
        $user_city_code        = '';

        if (!empty($address)) {
            $user_city = $address['city'] . $address['area'];
            $user_city_code = $address['datavalue'];
        }

        foreach ($goods as $g) {
            $dispatch_shop[$g['shop_id']] = 0;
            $total_array[$g['id']] += $g['goods_num'];
            $totalprice_array[$g['id']] += $g['shop_price'];
        }

        $goodsModel = new Goods();
        foreach ($goods as $g) {
            $goodsInfo = array();
            $goodsInfo = $goodsModel->get($g['id']);
            $isnodispatch = 0;
            $sendfree = false;
            $shopId = $g['shop_id'];
            $g['ednum'] = $goodsInfo->ednum;
            $g['edareas_code'] = $goodsInfo->edareas_code;
            $g['edmoney'] = $goodsInfo->edmoney;
            $g['dispatch_type'] = $goodsInfo->dispatch_type;
            $g['dispatch_id'] = $goodsInfo->dispatch_id;
            $g['dispatch_price'] = $goodsInfo->dispatch_price;

            if (!empty($g['is_send_free'])) { // 包邮
                $sendfree = true;
            }
            else {

                // 满件包邮
                if (($g['ednum'] <= $total_array[$g['id']]) && (0 < $g['ednum'])) {
                    $gareas = explode(';', $g['edareas_code']);

                    if (empty($gareas)) {
                        $sendfree = true;
                    }
                    else if (!empty($address)) {
                        if (!in_array($user_city_code, $gareas)) {
                            $sendfree = true;
                        }
                    }
                    else {
                        $sendfree = true;
                    }
                }

                // 满额包邮
                if ((floatval($g['edmoney']) <= $totalprice_array[$g['id']]) && (0 < floatval($g['edmoney']))) {
                    $gareas = explode(';', $g['edareas_code']);

                    if (empty($gareas)) {
                        $sendfree = true;
                    }
                    else if (!empty($address)) {
                        if (!in_array($user_city_code, $gareas)) {
                            $sendfree = true;
                        }
                    }
                    else {
                        $sendfree = true;
                    }
                }
            }

            if ($g['dispatch_type'] == 1) { // 统一邮费

                // 统一邮费
                if ((0 < $g['dispatch_price']) && !$sendfree && ($isnodispatch == 0)) {
                    $dispatch_shop[$shopId] += $g['dispatch_price'];
                    $dispatch_price += $g['dispatch_price'];
                }
            }
            else { // 运费模板
                if ($g['dispatch_type'] == 0) {
                    if (empty($g['dispatch_id'])) {
                        $dispatch_data = $this->getDefaultDispatch($shopId);
                    }
                    else {
                        $dispatch_data = $this->getOneDispatch($g['dispatch_id']);
                    }

                    if (empty($dispatch_data)) {
                        $dispatch_data = $this->getNewDispatch($shopId);
                    }

                    if (!empty($dispatch_data)) {
                        $isnoarea = 0;
                        $dkey = $dispatch_data['id'];
                        $isdispatcharea = intval($dispatch_data['is_dispatch_area']);

                        if (!empty($user_city)) {
                            if (empty($isdispatcharea)) { // 不配送区域
                                $citys = $this->getAllNoDispatchAreas($dispatch_data['no_dispatch_areas_code']);

                                if (!empty($citys)) {
                                    if (in_array($user_city_code, $citys)) {
                                        $isnoarea = 1;
                                    }
                                }
                            }
                            else {

                                if (empty($isnoarea)) {
                                    $isnoarea = $this->checkOnlyDispatchAreas($user_city_code, $dispatch_data);
                                }
                            }

                            if (!empty($isnoarea)) {
                                $isnodispatch = 1;
                                $has_goodsid = 0;

                                if (!empty($nodispatch_array['goods_id'])) {
                                    if (in_array($g['id'], $nodispatch_array['goods_id'])) {
                                        $has_goodsid = 1;
                                    }
                                }

                                if ($has_goodsid == 0) {
                                    $nodispatch_array['goods_id'][] = $g['id'];
                                    $nodispatch_array['goods_name'][] = $g['goods_name'];
                                    $nodispatch_array['city'] = $user_city;
                                }
                            }
                        }

                        if (!$sendfree && ($isnodispatch == 0)) {
                            $areas = unserialize($dispatch_data['areas']);

                            if ($dispatch_data['calculate_type'] == 1) { // 按件计算
                                $param = $g['goods_num'];
                            }
                            else {
                                $param = $g['weight'] * $g['goods_num'];
                            }

                            if (array_key_exists($dkey, $dispatch_array)) {
                                $dispatch_array[$dkey]['param'] += $param;
                            }
                            else {
                                $dispatch_array[$dkey]['data'] = $dispatch_data;
                                $dispatch_array[$dkey]['param'] = $param;
                            }

                        }
                    }
                }
            }
        }

        if (!empty($dispatch_array)) {
            $dispatch_info = array();

            foreach ($dispatch_array as $k => $v) {
                $dispatch_data = $dispatch_array[$k]['data'];
                $param = $dispatch_array[$k]['param'];
                $areas = unserialize($dispatch_data['areas']);

                if (!empty($address)) {
                    $dprice = $this->getCityDispatchPrice($areas, $address, $param, $dispatch_data);
                }
                else {
                    $dprice = $this->getDispatchPrice($param, $dispatch_data);
                }

                $shopId = $dispatch_data['shop_id'];
                $dispatch_shop[$shopId] += $dprice;

                $dispatch_price += $dprice;

                $dispatch_info[$dispatch_data['id']]['price'] += $dprice;
                $dispatch_info[$dispatch_data['id']]['free_price'] = intval($dispatch_data['free_price']);
            }

            if (!empty($dispatch_info)) {
                foreach ($dispatch_info as $k => $v) {
                    if ((0 < $v['free_price']) && ($v['free_price'] <= $v['price'])) {
                        $dispatch_price -= $v['price'];
                    }
                }

                if ($dispatch_price < 0) {
                    $dispatch_price = 0;
                }
            }
        }

        if ($dispatch_price == 0) {
            foreach ($dispatch_shop as &$dm) {
                $dm = 0;
            }
            unset($dm);
        }

        if (!empty($nodispatch_array)) {
            $nodispatch = lang('商品');

            foreach ($nodispatch_array['goods_name'] as $k => $v) {
                $nodispatch .= '"'.$v . '",';
            }

            $nodispatch = trim($nodispatch, ',');
            $nodispatch .= lang('不支持配送到') . $nodispatch_array['city'];
            $nodispatch_array['nodispatch'] = $nodispatch;
            $nodispatch_array['isnodispatch'] = 1;
        }

        $data = array();
        $data['dispatch_price'] = $dispatch_price;
        $data['dispatch_shop'] = $dispatch_shop;
        $data['nodispatch_array'] = $nodispatch_array;
        return $data;
    }

    public function getGoodsDispatchPrice($goods)
    {
        if (!empty($goods['is_send_free'])) {
            return 0;
        }

        if ($goods['dispatch_type'] == 1) {
            return $goods['dispatch_type'];
        }

        if (empty($goods['dispatch_id'])) {
            $dispatch =$this->getDefaultDispatch($goods['shop_id']);
        }
        else {
            $dispatch =$this->getOneDispatch($goods['dispatch_id']);
        }

        if (empty($dispatch)) {
            $dispatch =$this->getNewDispatch($goods['shop_id']);
        }

        $areas = iunserializer($dispatch['areas']);
        if (!empty($areas) && is_array($areas)) {
            $firstprice = array();

            foreach ($areas as $val) {
                $firstprice[] = $val['first_price'];
            }

            array_push($firstprice,$this->getDispatchPrice(1, $dispatch));
//            $goodsDispatchPrice = array('min' => round(min($firstprice), 2), 'max' => round(max($firstprice), 2));
            $goodsDispatchPrice = round(min($firstprice), 2).'~'.round(max($firstprice), 2);
        }
        else {
            $goodsDispatchPrice =$this->getDispatchPrice(1, $dispatch);
        }

        return $goodsDispatchPrice;
    }

}

?>
