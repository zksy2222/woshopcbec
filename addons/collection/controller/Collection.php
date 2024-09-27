<?php

namespace addons\collection\controller;
use app\shop\model\Type;
use GuzzleHttp\Client;
use QL\QueryList;
use think\addons\Controller;
use think\addons;
use think\Db;
use think\Config;
use think\Request;
use QL\Ext\Chrome;
use think\File;

class Collection extends Controller{

    public function info(){

        if(request()->isAjax()){
            $url = input('post.shop_url');
            $key1 = input('post.key');
            $num = input('post.num');
            $file =ROOT_PATH.'addons/collection/config.php';

            $key = ['key'];// 更改的键值名称
            $value = [$key1];// 对应的值
            for ($i = 0; $i < count($key); $i++) {
                $keys[$i] = '/\'' . $key[$i] . '\'(.*?),/';
                $values[$i] = "'". $key[$i]. "'". "=>" . "'".$value[$i] ."',";
            }
            $fileurl = $file;// 我的文件位置
            $string = file_get_contents($fileurl); //加载配置文件
            $string = preg_replace($keys, $values, $string); // 正则查找替换
            file_put_contents($fileurl, $string); // 写入配置文件


            if(!$url){
                $value = array('status'=>0, 'mess'=>'商品地址不能为空');
                return json($value);
            }

            if(!$url){
                $value = array('status'=>0, 'mess'=>'商品地址不能为空');
                return json($value);
            }

            //获取全站
            $tempu=parse_url($url);
            $wzurl=$tempu['host'];
            if($wzurl['host'] == 'shopee.tw'){
                unset($wzurl['path']);
            }
            $zd = substr($wzurl,strripos($wzurl,".")+1);
            $wz1 = mb_strpos($url,'i.');
            $shopId = substr($url,$wz1+2,9);

            $shopsItem = $this->getOneItems($shopId,$wzurl,$zd,$key1,1,$num);
            if($shopsItem['status'] == 1){
                $goodsNum = $this->getOneGoods($shopId,$wzurl,$zd,$key1,$shopsItem['data'],$num);
            }elseif($shopsItem['status'] == 0){
                return json($shopsItem);
            }


            if($goodsNum['status'] == 1){
                return json($goodsNum);
            }

            if($goodsNum['status'] == 0){
                return json($goodsNum);
            }



        }else{

            $file =ROOT_PATH.'addons/collection/config.php';
            $config = [];
            if (is_file($file)) {
                $temp_arr = include $file;
                foreach ($temp_arr as $key => $value) {
                    if ($value['type'] == 'group') {
                        foreach ($value['options'] as $gkey => $gvalue) {
                            foreach ($gvalue['options'] as $ikey => $ivalue) {
                                $config[$ikey] = $ivalue['value'];
                            }
                        }
                    } else {
                        $config[$key] = $temp_arr[$key]['value'];
                    }
                }
                unset($temp_arr);
            }
            $this->assign('key', $config['display']['key']);
            return $this->fetch('info');
        }
    }


    public function addGoods($data,$wzurl,$price){
        $goodsInfo = $data;
        $data = [];
        $data['goods_name'] = filterEmoji($goodsInfo['name']);
        $data['search_keywords'] = filterEmoji($goodsInfo['name']);
        $data['market_price'] = intval($price['price_before_discount']/100000);
        $data['shop_price'] = intval($price['price']/100000);
        $data['min_market_price'] = intval($price['price_min_before_discount']/100000);
        $data['max_market_price'] = intval($price['price_max_before_discount']/100000);
        $data['min_price'] = intval($price['price_min']/100000);
        $data['max_price'] = intval($price['price_max']/100000);
        $data['zs_price'] = intval($price['price']/100000);
        $data['onsale'] = 0;
        $data['goods_desc'] = '<p class="hrQhmh">'.nl2br(filterEmoji($goodsInfo['description'])).'</p>';
        $data['keywords'] = filterEmoji($goodsInfo['name']);
        $data['goods_brief'] =filterEmoji($goodsInfo['name']);
        $data['addtime'] = time();
        $data['is_new'] = 1;
        $data['shop_id'] = 1;
        $data['total'] = 9999;
        $data['thumb_url'] = 'https://cf.'.$wzurl.'/file/'.$goodsInfo['image'];


        //判断商品是否有规格
        if(empty($goodsInfo['tierVariations'][0]['name'])){
            $data['hasoption']  = 0;
        }else{
            $data['hasoption']  = 1;
        }
        $goodsId = Db::name('goods')->insertGetId($data);

        if($goodsId && $data['hasoption'] == 1){
            //添加规格tier_variations
            $goodsSpec = db('goods_spec');


            foreach ($goodsInfo['tierVariations'] as $k => $v){

                $a           = array('goods_id' => $goodsId, 'sort' => $k, 'title' => $v['name']);

                $spec_id = $goodsSpec->insertGetId($a);
                $itemids = array();
                foreach ($v['options'] as $k1 => $v1){
                    if(!empty($v['images'])){
                        $thumb = 'https://cf.'.$wzurl.'/file/'.$v['images'][$k1];
                    }
                    $d           = array('spec_id' => $spec_id, 'sort' => $k, 'title' => $v1, 'show' => 1, 'thumb' => $thumb);

                    $item_id = Db::name('goods_spec_item')->insertGetId($d);
                    $itemids[]    = $item_id;
                }

                Db::name('goods_spec')->where('id', $spec_id)->update(array('content' => serialize($itemids)));
            }
            $u = ',';
            $y = '/';

            if(strpos($goodsInfo['tierVariations'][0]['name'],$u) == false && strpos($goodsInfo['tierVariations'][0]['name'],$y) == false) {
                foreach ($goodsInfo['skus'] as $k => $v){
                    $newids = [];
                    $spec = explode(',', $v['name']);
                    foreach ($spec as $k1=>$v1){
                        $newids[] = $this->getSpecItemId($v1,$goodsId);
                    }
                    $newids = implode('_',$newids);
                    $a      = array('title' => str_ireplace('+', ',', $v['name']), 'shop_price' => intval($v['price']), 'market_price' => intval($v['priceBeforeDiscount']/100000), 'stock' => 1000, 'weight' => 1, 'goods_sn' => '', 'product_sn' => '', 'goods_id' => $goodsId, 'specs' => $newids);
                    Db::name('goods_option')->insertGetId($a);
                }
            }else{
                foreach ($goodsInfo['skus'] as $k => $v){
                    $goodsSpecId = $this->getSpecItemId($v['name'],$goodsId);
                    if($v['priceBeforeDiscount'] < 1){
                        $market_price = $data['market_price'];
                    }else{
                        $market_price = intval($v['priceBeforeDiscount']/100000);
                    }
                    $a      = array('title' => str_ireplace('+', ',', $v['name']), 'shop_price' => intval($v['price']), 'market_price' => $market_price, 'stock' => 1000, 'weight' => 1, 'goods_sn' => '', 'product_sn' => '', 'goods_id' => $goodsId, 'specs' => $goodsSpecId);
                    Db::name('goods_option')->insertGetId($a);
                }
            }


            foreach ($goodsInfo['images'] as $k=>$v){
                db('goods_pic')->insertGetId(['goods_id'=>$goodsId,'img_url'=>'https://cf.'.$wzurl.'/file/'.$v]);
                if($k == 5){
                    break;
                }
            }
        }
    }



//通过商品id和商品规格项获取规格项id
    public function getSpecItemId($title,$goodsId){
        $goodsSpec = db('goods_spec')->where(['goods_id'=>$goodsId])->select();
        $ids = [];
        foreach ($goodsSpec as $k=>$v){
            $ids[] = unserialize($v['content']);
        }
        $itemIds = [];
        foreach ($ids as $k => $v){
            foreach ($v as $k1 => $v1){
                $itemIds[] = $v1;
            }
        }
        $specItems = db('goods_spec_item')->whereIn('id',$itemIds)->select();
        foreach ($specItems as $k=>$v){
            if($v['title'] == $title){
                return $v['id'];
            }
        }


    }

    /**
     * 获取单个店铺所以商品列表信息
     * @param $shops
     * @return mixed
     */
    public function getOneItems($shopsId,$wzurl,$zd,$key,$page=1,$num){
        $res = [];
        $ql =  QueryList::get('https://api09.99api.com/shopee/shopGoods?apikey='.$key.'&shopId='.$shopsId.'&market='.$zd.'&page='.$page);
        usleep(1000000);
        $data = $ql->getHtml();
        $ql->destruct();
        $data = json_decode($data,true);

        if($data['retcode'] == 4005){
            $value = array('status'=>0, 'mess'=>'请检查key是否正确');
            return $value;
        }

        if($data['retcode'] == 5000){
            $value = array('status'=>0, 'mess'=>'99api接口访问失败，请检查！');
            return $value;
        }


        if($data['retcode'] == 2000){
            $value = array('status'=>0, 'mess'=>'请检查商品链接是否正确');
            return $value;
        }
        $res = [];
        $res= $data['data']['items'];
        if($data['hasNext'] == true){
            $info = $this->getOneItems($shopsId,$wzurl,$zd,$key,$data['page']+1,$num);
            foreach ($info as $k => $v){
                array_push($res,$v);
            }
            if(count($res) == $num){
                $value = array('status'=>1, 'mess'=>'获取成功','data'=>$res);
                return $value;
            }
        }

        $value = array('status'=>1, 'mess'=>'获取成功','data'=>$res);
        return $value;
    }

    public function getOneGoods($shopsId,$wzurl,$zd,$key,$items,$num){
        foreach ($items as $k => $v) {
            if($k == $num){
                $value = array('status'=>1, 'mess'=>'共获取'.($num).'条商品');
                return $value;
            }
            usleep(1000000);
            $ql1 = QueryList::get('https://api09.99api.com/shopee/goodsDetail?apikey=' . $key . '&itemId=' . $v['itemId'] . '&shopId=' . $shopsId . '&market='.$zd);
//            $ql1 = QueryList::get('https://api09.99api.com/shopee/goodsDetail?apikey=0D7B063CC04241BE3E01DA2466CAA549&itemId=9060234508&shopId=266774931&market=sg');
            $data1 = $ql1->getHtml();
            $ql1->destruct();
            $data1 = json_decode($data1, true);
            $price = [
                'price' => $data1['data']['price'],
                'price_min' => $data1['data']['priceMin'],
                'price_max' => $data1['data']['priceMax'],
                'price_min_before_discount' => $data1['data']['priceMinBeforeDiscount'],
                'price_max_before_discount' => $data1['data']['priceMaxBeforeDiscount'],
                'price_before_discount' => $data1['data']['priceBeforeDiscount'],
            ];

            if($data1['data']['priceMinBeforeDiscount']<1){
                $price['price_min_before_discount'] = $data1['data']['priceMin'];
            }
            if($data1['data']['priceMaxBeforeDiscount']<1){
                $price['price_max_before_discount'] = $data1['data']['priceMax'];
            }
            if($data1['data']['priceBeforeDiscount']<1){
                $price['price_before_discount'] = $data1['data']['priceMax'];
            }
            $this->addGoods($data1['data'],$wzurl,$price);
        }

        $value = array('status'=>1, 'mess'=>'共获取'.count($items).'条商品');
        return $value;
    }




    /**
     * 获取单个店铺所以商品列表信息
     * @param $shops
     * @return mixed
     */
    public function getOneItems12($shopsId,$wzurl,$key,$page,$num,$num1=1){
        $ql =  QueryList::get('https://api09.99api.com/shopee/shopGoods?apikey='.$key.'&shopId='.$shopsId.'&market=my&page='.$page);
        $data = $ql->getHtml();
        $ql->destruct();
        $data = json_decode($data,true);
        if($data['status'] == 4005){
            $value = array('status'=>0, 'mess'=>'请检查key是否正确');
            return $value;
        }

        if($data['retcode'] == 2000){
            $value = array('status'=>0, 'mess'=>'请检查商品链接是否正确');
            return $value;
        }


        foreach ($data['data']['items'] as $k => $v) {
            if($k == $num){
                $value = array('status'=>1, 'mess'=>'共获取'.($num).'条商品');
                return $value;
            }
            usleep(1000000);
            $ql1 = QueryList::get('https://api09.99api.com/shopee/goodsDetail?apikey=' . $key . '&itemId=' . $v['itemId'] . '&shopId=' . $shopsId . '&market=my');
            $data1 = $ql1->getHtml();
            $ql1->destruct();
            $data1 = json_decode($data1, true);
            $price = [
                'price' => $data1['data']['price'],
                'price_min' => $data1['data']['priceMin'],
                'price_max' => $data1['data']['priceMax'],
                'price_min_before_discount' => $data1['data']['priceMinBeforeDiscount'],
                'price_max_before_discount' => $data1['data']['priceMaxBeforeDiscount'],
                'price_before_discount' => $data1['data']['priceBeforeDiscount'],
            ];
            $this->addGoods($data1['data'],$wzurl,$price);
            $num1 = $num1+1;
        }

        if($data['hasNext'] == true){
            $this->getOneItems($shopsId,$wzurl,$key,$page+1,$num-$num1,$num1);
        }
        $value = array('status'=>1, 'mess'=>'共获取'.$num1.'条商品');
        return $value;
    }

}
