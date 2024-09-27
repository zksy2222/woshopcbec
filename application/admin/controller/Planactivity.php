<?php
namespace app\admin\controller;
use app\admin\controller\Basic;
use QL\QueryList;
use GuzzleHttp\Client;
use think\Db;

class Planactivity extends Basic
{

    //秒杀、团购、拼团活动结束和开始后自动更新参与商品展示价格
    public function planhd()
    {
        $nowtime = time();

        //过期秒杀信息
        $end_rushres = Db::name('seckill')->where('checked', 1)->where('is_show', 1)->where('end_time', 'elt', $nowtime)->where('finish_status', 0)->field('id,goods_id,end_time')->select();
        if ($end_rushres) {
            foreach ($end_rushres as $vr) {
                $rumin_price = Db::name('goods')->where('id', $vr['goods_id'])->value('min_price');
                // 启动事务
                Db::startTrans();
                try {
                    Db::name('seckill')->update(array('hd_bs' => 2, 'id' => $vr['id'], 'finish_status' => 1, 'finish_time' => $vr['end_time']));
                    Db::name('goods')->update(array('id' => $vr['goods_id'], 'zs_price' => $rumin_price, 'is_activity' => 0));
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
        }

//        //过期团购信息
//        $end_groupres = Db::name('group_buy')->where('checked',1)->where('is_show',1)->where('end_time','elt',$nowtime)->where('finish_status',0)->field('id,goods_id')->select();
//        if($end_groupres){
//            foreach ($end_groupres as $vp){
//                $acmin_price = Db::name('goods')->where('id',$vp['goods_id'])->value('min_price');
//                // 启动事务
//                Db::startTrans();
//                try{
//                    Db::name('group_buy')->update(array('hd_bs'=>2,'id'=>$vp['id']));
//                    Db::name('goods')->update(array('id'=>$vp['goods_id'],'zs_price'=>$acmin_price,'is_activity'=>0));
//                    // 提交事务
//                    Db::commit();
//                } catch (\Exception $e) {
//                    // 回滚事务
//                    Db::rollback();
//                }
//            }
//        }

        //过期拼团信息
        $end_pinres = Db::name('assemble')->where('checked', 1)->where('hd_bs', 1)->where('is_show', 1)->where('end_time', 'elt', $nowtime)->field('id,goods_id,end_time')->select();
        if ($end_pinres) {
            foreach ($end_pinres as $va) {
                $asmin_price = Db::name('goods')->where('id', $va['goods_id'])->value('min_price');
                // 启动事务
                Db::startTrans();
                try {
                    Db::name('assemble')->update(array('hd_bs' => 2, 'id' => $va['id'], 'finish_status' => 1, 'finish_time' => $va['end_time']));
                    Db::name('goods')->update(array('id' => $va['goods_id'], 'zs_price' => $asmin_price, 'is_activity' => 0));
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
        }


        //秒杀中信息
        $rushres = Db::name('seckill')->where('checked', 1)->where('hd_bs', 0)->where('is_show', 1)->where('start_time', 'elt', $nowtime)->where('end_time', 'gt', $nowtime)->field('id,goods_id,goods_attr,price')->select();
        if ($rushres) {
            foreach ($rushres as $v) {
                if ($v['goods_attr']) {
                    // 启动事务
                    Db::startTrans();
                    try {
                        Db::name('seckill')->update(array('hd_bs' => 1, 'id' => $v['id']));
                        Db::name('goods')->update(array('id' => $v['goods_id'], 'zs_price' => $v['price'], 'is_activity' => 1));
                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                    }
                } else {
                    // 启动事务
                    Db::startTrans();
                    try {
                        Db::name('seckill')->update(array('hd_bs' => 1, 'id' => $v['id']));
                        Db::name('goods')->update(array('id' => $v['goods_id'], 'zs_price' => $v['price'], 'is_activity' => 1));
                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                    }
                }
            }
        }

//        //团购中信息
//        $groupres = Db::name('group_buy')->where('checked',1)->where('hd_bs',0)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,price')->select();
//        if($groupres){
//            foreach ($groupres as $val){
//                // 启动事务
//                Db::startTrans();
//                try{
//                    Db::name('group_buy')->update(array('hd_bs'=>1,'id'=>$val['id']));
//                    Db::name('goods')->update(array('id'=>$val['goods_id'],'zs_price'=>$val['price'],'is_activity'=>2));
//                    // 提交事务
//                    Db::commit();
//                } catch (\Exception $e) {
//                    // 回滚事务
//                    Db::rollback();
//                }
//            }
//        }
//
        //拼团中信息
        $pinres = Db::name('assemble')->where('checked', 1)->where('hd_bs', 0)->where('is_show', 1)->where('start_time', 'elt', time())->where('end_time', 'gt', time())->field('id,goods_id,price')->select();
        if ($pinres) {
            foreach ($pinres as $val2) {
                // 启动事务
                Db::startTrans();
                try {
                    Db::name('assemble')->update(array('hd_bs' => 1, 'id' => $val2['id']));
                    Db::name('goods')->update(array('id' => $val2['goods_id'], 'zs_price' => $val2['price'], 'is_activity' => 3));
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
        }

    }

    //商家访客人数每6小时增加一次
    public function planShopsVisitor()
    {

        $shopsVisitorRandom = $this->webconfig['shop_visitor_random'];
        $shopsVisitorRandom = explode(',', $shopsVisitorRandom);;
        $shopsDb = db('shops');
        $shopss = $shopsDb->select();
        foreach ($shopss as $k => $v) {
            // 启动事务
            Db::startTrans();
            try {
                $shopVisitor = $v['shop_visitor'] + rand($shopsVisitorRandom[0], $shopsVisitorRandom[1]);
                $shopsDb->update(['id' => $v['id'], 'shop_visitor' => $shopVisitor]);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
            }
        }
    }

    //json商品数据存入数据库
    public function jsonDbSql()
    {
        $json_string = file_get_contents('C:\Users\38087\Desktop\1.json');
        $data = json_decode($json_string, true);

        $id = input('id');
        $wzurl = 'shopee.sg';
        $cateId = $id;
        foreach ($data['items'] as $k => $v){
            $this->addGoods($v, $wzurl, $cateId);
        }

//        $msg = $this->addGoods($data['data'], $wzurl, $cateId);
        return 1;
    }

    public function addGoods($data, $wzurl, $cateId)
    {
        $goodsInfo = $data['item_basic'];
        $data = [];
        $data['cate_id'] = $cateId;
        $data['leixing'] = 0;
        $data['goods_name'] = filterEmoji($goodsInfo['name']);
        $data['search_keywords'] = filterEmoji($goodsInfo['name']);
        $data['market_price'] = intval($goodsInfo['price_before_discount'] / 100000);
        $data['shop_price'] = intval($goodsInfo['price'] / 100000);
        $data['min_market_price'] = intval($goodsInfo['price_min_before_discount'] / 100000);
        $data['max_market_price'] = intval($goodsInfo['price_max_before_discount'] / 100000);
        $data['min_price'] = intval($goodsInfo['price_min'] / 100000);
        $data['max_price'] = intval($goodsInfo['price_max'] / 100000);
        $data['zs_price'] = intval($goodsInfo['price'] / 100000);
        $data['onsale'] = 0;
        $data['goods_desc'] = '<p class="hrQhmh">' . nl2br(filterEmoji($goodsInfo['description'])) . '</p>';
        $data['keywords'] = filterEmoji($goodsInfo['name']);
        $data['goods_brief'] = filterEmoji($goodsInfo['name']);
        $data['addtime'] = time();
        $data['is_new'] = 1;
        $data['shop_id'] = 1;
        $data['total'] = 9999;
        $data['thumb_url'] = 'https://cf.' . $wzurl . '/file/' . $goodsInfo['image'];
//        $goodsName = str_replace( ' ', '-',$goodsInfo['name']);
//        $goodsName = str_replace( ']', '-',$goodsName);
//        $goodsName = str_replace('[', '-',$goodsName);
//        $goodsName = str_replace(',', '-',$goodsName);
//        $data['goods_url'] = "https://shopee.sg/".$goodsName.'-i.'.$goodsInfo['shopid'].'.'.$goodsInfo['itemid'];
        $data['goods_url'] = '-i.'.$goodsInfo['shopid'].'.'.$goodsInfo['itemid'];

        //判断商品是否有规格
        if (empty($goodsInfo['tier_variations'][0]['name'])) {
            $data['hasoption'] = 0;
        } else {
            $data['hasoption'] = 1;
        }
        $goodsId = Db::name('goods')->insertGetId($data);

        //添加图片
        foreach ($goodsInfo['images'] as $k => $v) {
            db('goods_pic')->insertGetId(['goods_id' => $goodsId, 'img_url' => 'https://cf.' . $wzurl . '/file/' . $v]);
            if ($k == 5) {
                break;
            }
        }



        $lang = db('lang')->select();
        foreach ($lang as $k => $v) {
            $g = 'en';
            switch ($v['remark']) {
                case '简体中文':
                    $to = 'zh';
                    break;
                case '法语':
                    $to = 'fra';
                    break;
                case '韩语':
                    $to = 'kor';
                    break;
                case '日语':
                    $to = 'jp';
                    break;
                case '繁体':
                    $to = 'cht';
                    break;
                case '德语':
                    $to = 'de';
                    break;
                case '西班牙语':
                    $to = 'spa';
                    break;
                case '俄语':
                    $to = 'ru';
                    break;
                case '泰语':
                    $to = 'th';
                    break;
                case '葡萄牙语':
                    $to = 'pt';
                    break;
                case '越南语':
                    $to = 'vie';
                    break;
                case '马来语':
                    $to = 'may';
                    break;
            }

            $goodsLang = [];
            $goodsLang['goods_id'] = $goodsId;
            $goodsLang['lang_id'] = $v['id'];
            $goodsLang['goods_name'] = $data['goods_name'];
            $goodsLang['goods_desc'] = '<p class="hrQhmh">' . nl2br(filterEmoji($goodsInfo['description'])) . '</p>';
            db('goods_lang')->insertGetId($goodsLang);
//            $goodsLang = [];
//            $goodsLang['goods_id'] = $goodsId;
//            $goodsLang['lang_id'] = $v['id'];
//
//            $goodsLang['goods_name'] = $this->language($data['goods_name'], $g,$to);
//            $goodsLang['goods_desc'] = '<p class="hrQhmh">' . $this->language(nl2br(filterEmoji($goodsInfo['description'])), $g,$to) . '</p>';
//            if($v['remark'] != 'English'){
//                db('goods_lang')->insertGetId($goodsLang);
//            }else{
//                $goodsLang = [];
//                $goodsLang['goods_id'] = $goodsId;
//                $goodsLang['lang_id'] = $v['id'];
//                $goodsLang['goods_name'] = $data['goods_name'];
//                $goodsLang['goods_desc'] = '<p class="hrQhmh">' . nl2br(filterEmoji($goodsInfo['description'])) . '</p>';
//                db('goods_lang')->insertGetId($goodsLang);
//            }

        }

        if ($goodsId && $data['hasoption'] == 1) {
            //添加规格tier_variations
            $goodsSpec = db('goods_spec');
            foreach ($goodsInfo['tier_variations'] as $k => $v) {
                $a = array('goods_id' => $goodsId, 'sort' => $k, 'title' => $v['name']);
                $spec_id = $goodsSpec->insertGetId($a);
                $itemids = array();
                foreach ($v['options'] as $k1 => $v1) {
                    if (!empty($v['images'])) {
                        $thumb = 'https://cf.' . $wzurl . '/file/' . $v['images'][$k1];
                    }
                    $d = array('spec_id' => $spec_id, 'sort' => $k, 'title' => $v1, 'show' => 1, 'thumb' => $thumb);
                    $item_id = Db::name('goods_spec_item')->insertGetId($d);
                    $itemids[] = $item_id;
                }

                Db::name('goods_spec')->where('id', $spec_id)->update(array('content' => serialize($itemids)));
            }
//dump($goodsInfo);die;
            foreach ($goodsInfo['models'] as $k => $v) {
                $newids = [];
                $spec = explode(',', $v['name']);

                foreach ($spec as $k1 => $v1) {
                    $newids[] = $this->getSpecItemId($v1, $goodsId);
                }
                $newids = implode('_', $newids);

                $a = array('title' => str_ireplace('+', ',', $v['name']), 'shop_price' => intval($v['price'] / 100000), 'market_price' => intval($v['price'] / 100000), 'stock' => 1000, 'weight' => 1, 'goods_sn' => '', 'product_sn' => '', 'goods_id' => $goodsId, 'specs' => $newids);

                Db::name('goods_option')->insertGetId($a);
            }
        }

        return 1;
    }


//通过商品id和商品规格项获取规格项id
    public function getSpecItemId($title, $goodsId)
    {
        $goodsSpec = db('goods_spec')->where(['goods_id' => $goodsId])->select();
        $ids = [];
        foreach ($goodsSpec as $k => $v) {
            $ids[] = unserialize($v['content']);
        }
        $itemIds = [];
        foreach ($ids as $k => $v) {
            foreach ($v as $k1 => $v1) {
                $itemIds[] = $v1;
            }
        }
        $specItems = db('goods_spec_item')->whereIn('id', $itemIds)->select();
        foreach ($specItems as $k => $v) {
            if ($v['title'] == $title) {
                return $v['id'];
            }
        }


    }


    public function language($value, $from = "en", $to = "zh")
    {
        $value_code = $value;
        $appid = "20221008001378512"; //您注册的API Key
        $key = "rbevfSm5wZbMiEKC0dRh"; //密钥
        $salt = rand(1000000000, 9999999999); //随机数
        $sign = md5($appid . $value_code . $salt . $key); //签名
        $value_code = urlencode($value_code);
        //生成翻译API的URL
        $languageurl = "http://api.fanyi.baidu.com/api/trans/vip/translate?q=$value_code&appid=$appid&salt=$salt&from=$from&to=$to&sign=$sign";
        $text = json_decode($this->language_text($languageurl));
        $lan = $text->trans_result;
        $result = '';
        foreach ($lan as $k => $v) {
            $result .= ucwords($v->dst) . "\n";
        }
        return $result;
    }

    public function language_text($reqURL)
    {
        $ch = curl_init($reqURL);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        if ($result) {
            curl_close($ch);
            return $result;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            return ("curl出错，错误码:$error");
        }
    }


    public function guge(){

        $url =  ("https://fanyi.baidu.com/v2transapi");

        $ql = QueryList::post($url,[
            'from'=>'en',
            'to'=>'zh',
            'query'=>'Decimal',
//            'simple_means_flag'=> 3,
//            'sign'=>'783471.989022',
//            'token'=>'541bc6f7e2751253f502285def5b09c0',
//            'domain'=>'common',
        ],$this->getHeaderss());
        $data = $ql->getHtml();
        $ql->destruct();
        $data = json_decode($data,true);
        dump($data);die;
    }

    public function getHeaderss(){
        $client = new Client();
        $response = $client->get('http://httpbin.org/get');
        $headers = $response->getHeaders();
        return $headers;
    }

    public function getItemShopId(){
        $id = input('id');
        $id = 113;
        $goodsList = db('goods')->where('cate_id',$id)->select();
        $goodsOptionDb = db('goods_option');
        foreach ($goodsList as $k => $v){
            $leng = strlen($v['goods_desc']);
            $option = $goodsOptionDb->where('goods_id',$v['id'])->find();
            if($leng >22 && !empty($option)){
                unset($v);
            }
        }
        return json($goodsList);
    }

    public function getItemShopId1(){
        $id = input('id');
        $goodsList = db('goods')->order('id','desc')->select();
        return json($goodsList);
    }

    public function setDesc(){
        $wzurl = 'shopee.sg';
        $data = input('data');
        file_put_contents(ROOT_PATH .'public/1.json',$data);
        $content = file_get_contents(ROOT_PATH .'public/1.json');
        $data = json_decode($content,true);
//        return $data['data']['description'];
        $id = input('id');
        $desc = '<p class="hrQhmh">' . nl2br(filterEmoji($data['data']['description'])) . '</p>';
        db('goods')->where('id',$id)->update(['goods_desc'=>$desc]);

        $goodsLangs = db('goods_lang')->where('goods_id',$id)->select();
        foreach ($goodsLangs as $k => $v){
            $v['goods_desc'] = $desc;
            db('goods_lang')->update($v);
        }

        $goodsInfo = db('goods')->where('id',$id)->find();
        $goodsOption = db('goods_option')->where('goods_id',$id)->select();

        if($goodsInfo['hasoption'] == 1 && empty($goodsOption)) {

            $specIds = db('goods_spec')->where('goods_id', $id)->column('id');
            db('goods_spec_item')->where('id', 'in', $specIds)->delete();
            db('goods_spec')->where('goods_id', $id)->delete();
            //添加规格tier_variations
            $goodsSpec = db('goods_spec');
            foreach ($data['data']['tier_variations'] as $k => $v) {
                $a = array('goods_id' => $id, 'sort' => $k, 'title' => $v['name']);
                $spec_id = $goodsSpec->insertGetId($a);
                $itemids = array();
                foreach ($v['options'] as $k1 => $v1) {
                    if (!empty($v['images'])) {
                        $thumb = 'https://cf.' . $wzurl . '/file/' . $v['images'][$k1];
                    }
                    $d = array('spec_id' => $spec_id, 'sort' => $k, 'title' => $v1, 'show' => 1, 'thumb' => $thumb);
                    $item_id = Db::name('goods_spec_item')->insertGetId($d);
                    $itemids[] = $item_id;
                }

                Db::name('goods_spec')->where('id', $spec_id)->update(array('content' => serialize($itemids)));
            }

            foreach ($data['data']['models'] as $k => $v) {
                $newids = [];
                $spec = explode(',', $v['name']);

                foreach ($spec as $k1 => $v1) {
                    $newids[] = $this->getSpecItemId($v1, $id);
                }
                $newids = implode('_', $newids);

                $a = array('title' => str_ireplace('+', ',', $v['name']), 'shop_price' => intval($v['price'] / 100000), 'market_price' => intval($v['price'] / 100000), 'stock' => 1000, 'weight' => 1, 'goods_sn' => '', 'product_sn' => '', 'goods_id' => $id, 'specs' => $newids);

                Db::name('goods_option')->insertGetId($a);
            }
        }

        return 1;
    }

    public function fy(){

        $keyword = '中国';
        $from = 'zh';
        $to = 'en';

        exec("python D:\项目\CPM-main//fy.py $keyword $from $to",$c);
        dump($c);
    }
}