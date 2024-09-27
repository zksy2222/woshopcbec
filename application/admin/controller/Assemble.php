<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use app\common\model\Goods as GoodsCommonModel;
use app\admin\model\GoodsOption as GoodsOptionModel;
use app\admin\model\Common as CommonModel;
use think\Db;

class Assemble extends Common{
    
    public function lst(){
        $shop_id = session('shop_id');
        
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3,4,5,10))){
            $filter = 10;
        }
        
        $where = array();
        $where['a.shop_id'] = $shop_id;
        $where['a.is_show'] = 1;
        $where['b.is_recycle'] = array('neq',1);
        $where['b.onsale'] = array('neq',0);
        switch($filter){
            //待审核
            case 1:
                $where['a.checked'] = 0;
                break;
            //即将开始
            case 2:
                $where['a.checked'] = 1;
                $where['a.start_time'] = array('gt',time());
                break;
            //拼团中
            case 3:
                $where['a.checked'] = 1;
                $where['a.start_time'] = array('elt',time());
                $where['a.end_time'] = array('gt',time());  
                break;
            //已结束
            case 4:
                $where['a.checked'] = 1;
                $where['a.end_time'] = array('elt',time());
                break;
            //平台关闭
            case 5:
                $where['a.checked'] = 2;
                break;
        }
        
        $list = Db::name('assemble')->alias('a')->field('a.*,b.goods_name')->join('sp_goods b','a.goods_id = b.id','LEFT')->where($where)->order('a.apply_time desc')->paginate(25);
        $page = $list->render();
        
        $listres = $list->toArray();
        $list = $listres['data'];
        
        if($list){
            foreach ($list as $k => $v){
                if($v['checked'] == 0){
                    //待审核
                    $list[$k]['zhuangtai'] = 1;
                }elseif($v['checked'] == 1 && $v['start_time'] > time()){
                    //即将开始
                    $list[$k]['zhuangtai'] = 2;
                }elseif($v['checked'] == 1 && $v['start_time'] <= time() && $v['end_time'] > time()){
                    //拼团中
                    $list[$k]['zhuangtai'] = 3;
                }elseif($v['checked'] == 1 && $v['end_time'] <= time()){
                    //已结束
                    $list[$k]['zhuangtai'] = 4;
                }elseif($v['checked'] == 2){
                    //平台关闭
                    $list[$k]['zhuangtai'] = 5;
                }
                
                if($v['goods_attr']){
                    if($v['goods_attr'] != '*'){
                        $list[$k]['goods_attr_str'] = '';
                        $gares = Db::name('goods_attr')->alias('a')->field('a.*,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$v['goods_attr'])->where('a.goods_id',$v['goods_id'])->where('b.attr_type',1)->select();
                        if($gares){
                            foreach ($gares as $key => $val){
                                if($key == 0){
                                    $list[$k]['goods_attr_str'] = $val['attr_name'].':'.$val['attr_value'];
                                }else{
                                    $list[$k]['goods_attr_str'] = $list[$k]['goods_attr_str'].' '.$val['attr_name'].':'.$val['attr_value'];
                                }
                            }
                        }
                    }else{
                        $gares = array();
                        $list[$k]['goods_attr_str'] = '';
                    }
                }else{
                    $gares = array();
                    $list[$k]['goods_attr_str'] = '';
                }
            }
        }
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $this->assign('pnum',$pnum);
        $this->assign('page',$page);
        $this->assign('filter',$filter);
        $this->assign('list',$list);// 赋值数据集
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }

    //获取商品信息
    public function getGoodsInfo(){
        if(request()->isPost()){
            $shop_id = session('shop_id');

            if(input('post.id')){
                $goods_id = input('post.id');
                $goods = Db::name('goods')->where('id',$goods_id)->where('onsale',1)->where('shop_id',$shop_id)->field('id,max_price,min_price,hasoption')->find();
                if(!$goods){
                    datamsg(0,'商品信息错误');
                }
                if($goods['hasoption'] == 1){
                    $goodsCommonModel = new GoodsCommonModel();
                    $goods['option'] = $goodsCommonModel->getAssembleGoodsOptionHtml($goods_id);
                }
                datamsg(1,'获取商品信息',$goods);
            }else{
                datamsg(0,'缺少商品id参数');
            }

        }
    }
    
    //根据商品单选属性计算价格调用对应库存
    public function get_goods_price(){
        if(request()->isPost()){
            $shop_id = session('shop_id');
            $data = input('post.');
            if(!empty($data['goods_id']) && !empty($data['goods_attr'])){
                $goods_id = $data['goods_id'];
                
                $goods = Db::name('goods')->where('id',$goods_id)->where('onsale',1)->where('shop_id',$shop_id)->field('id,shop_price,min_price,max_price')->find();
                if($goods){
                    $radiores = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.goods_id',$goods_id)->where('b.attr_type',1)->select();
                    if($radiores){
                        if($data['goods_attr'] != '*'){
                            $data['goods_attr'] = trim($data['goods_attr']);
                            $data['goods_attr'] = str_replace('，', ',', $data['goods_attr']);
                            $data['goods_attr'] = rtrim($data['goods_attr'],',');
                            $goods_attr = $data['goods_attr'];
                            
                            $gattr = explode(',', $goods_attr);
                            if($gattr && is_array($gattr)){
                                $radioattr = array();
                                foreach ($radiores as $va){
                                    $radioattr[$va['attr_id']][] = $va['id'];
                                }
                            
                                $gattres = array();
                                foreach ($gattr as $ga){
                                    if(!empty($ga)){
                                        $goodsxs = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id',$ga)->where('a.goods_id',$goods_id)->where('b.attr_type',1)->find();
                                        if($goodsxs){
                                            $gattres[$goodsxs['attr_id']] = $goodsxs['id'];
                                        }else{
                                            $value = array('status'=>0,'mess'=>'商品属性参数错误');
                                            return json($value);
                                        }
                                    }else{
                                        $value = array('status'=>0,'mess'=>'商品属性参数错误');
                                        return json($value);
                                    }
                                }
                            
                                foreach ($radioattr as $key => $val){
                                    if(empty($gattres[$key]) || !in_array($gattres[$key],$val)){
                                        $value = array('status'=>0,'mess'=>'请选择商品属性');
                                        return json($value);
                                    }
                                }
                            
                                foreach ($gattres as $key2 => $val2){
                                    if(empty($radioattr[$key2]) || !in_array($val2, $radioattr[$key2]) ){
                                        $value = array('status'=>0,'mess'=>'商品属性参数错误');
                                        return json($value);
                                    }
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'商品属性参数错误');
                                return json($value);
                            }
                            
                            $gares = Db::name('goods_attr')->where('id','in',$goods_attr)->where('goods_id',$goods_id)->field('id,attr_price')->select();
                            if($gares){
                                $shop_price = $goods['shop_price'];
                                foreach ($gares as $v){
                                    $shop_price+=$v['attr_price'];
                                }
                                $shop_price=sprintf("%.2f", $shop_price);
                                $prores = Db::name('product')->where('goods_attr',$goods_attr)->where('goods_id',$goods_id)->field('goods_number')->find();
                                if($prores){
                                    $goods_number = $prores['goods_number'];
                                }else{
                                    $goods_number = 0;
                                }
                                $value = array('status'=>1,'mess'=>'获取信息成功','shop_price'=>$shop_price,'goods_number'=>$goods_number);
                            }else{
                                $value = array('status'=>0,'mess'=>'参数错误');
                            }
                        }else{
                            $goods_attr = $data['goods_attr'];

                            if($goods['min_price'] != $goods['max_price']){
                                $value = array('status'=>0,'mess'=>'商品存在价格区间，不能选择全部规格');
                                return json($value);
                            }else{
                                $shop_price = $goods['min_price'];
                                $prores = Db::name('product')->where('goods_id',$goods_id)->field('goods_number')->select();
                                if($prores){
                                    $goods_number = 0;
                                    foreach ($prores as $v){
                                        $goods_number+=$v['goods_number'];
                                    }
                                }else{
                                    $goods_number = 0;
                                }
                                $value = array('status'=>1,'mess'=>'获取信息成功','shop_price'=>$shop_price,'goods_number'=>$goods_number);
                            }
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'参数错误');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'商品已下架或不存在');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数');
            }
        }
        return json($value);
    }
    
    public function add(){
        if(request()->isPost()){
            $shop_id = session('shop_id');
            $data = input('post.');
            $data['shop_id'] = $shop_id;
            $data['apply_time'] = time();
            

            $goods_id = $data['goods_id'];

            $goods = Db::name('goods')->where('id',$goods_id)->where('onsale',1)->where('shop_id',$shop_id)->field('id,shop_price,min_price,max_price,hasoption')->find();
            if(!$goods){
                datamsg(0,'商品已下架或不存在');
            }
            if($goods['hasoption'] == 1){
                $scene = 'Assemble.hasoption';
            }else{
                $scene = 'Assemble.nooption';
            }
            $result = $this->validate($data,$scene);
            if(true !== $result){
                datamsg(0,$result);
            }

            $start_time = strtotime($data['start_time']);
            $end_time = strtotime($data['end_time']);

            if($start_time >= $end_time){
                datamsg(0,'开始时间需小于结束时间');
            }

            //判断商品活动区间内是否参与活动
            $CommonModel = new CommonModel();
            $activity = $CommonModel->getActivityInfo($goods_id,$start_time,$end_time,$shop_id);
            switch ($activity) {
                case 1:
                    datamsg(0,'活动时间区间内该商品参与了秒杀活动，新增失败');
                    break;
                case 2:
                    datamsg(0,'活动时间区间内该商品参与了拼团活动，新增失败');
                    break;
                case 3:
                    datamsg(0,'活动时间区间内该商品参与了积分换购，新增失败');
                    break;
            }

            if($goods['hasoption']){
                $optionData = json_decode($data['optionArray'],true);
//                dump($optionData);die;
                $optionIdCheckedArr = $data['option_id_checked'];
                if(!isset($optionIdCheckedArr) || !is_array($optionIdCheckedArr)){
                    datamsg(0,'请选择商品规格');
                }

                $optionStock = $optionData['option_stock'];
                $optionId = $optionData['option_id'];
                $optionIds = $optionData['option_ids'];
                $optionPrice = $optionData['option_productprice'];
                $length = count($optionId);
                if(count($optionStock) != $length || count($optionPrice) != $length || count($optionIds) != $length){
                    datamsg(0,'规格数据有误');
                }

                foreach ($optionId as $k=>$v){
                    if(in_array($v,$optionIdCheckedArr)){
                        if(!is_numeric(trim($optionPrice[$k])) || trim($optionPrice[$k]) == 0){
                            datamsg(0,'规格价格必须为>0的数字');
                        }
                        if(!is_numeric($optionStock[$k]) || trim($optionStock[$k]) == 0){
                            datamsg(0,'规格库存必须为>0的数字');
                        }
                        Db::name('goods_option')->where('id',$v)->update([
                            'is_assemble' => 1,
                            'assemble_price' => trim($optionPrice[$k]),
                            'assemble_stock' => trim($optionStock[$k])
                        ]);
                    }
                }

                $goodsAttr = implode(',',$optionIdCheckedArr);

            }else{
                $goodsAttr = '';
            }

            $lastId = Db::name('assemble')->insert(array(
                'pin_name'=>$data['pin_name'],
                'goods_id'=>$data['goods_id'],
                'goods_attr'=>$goodsAttr,
                'price'=>$data['price'],
                'pin_num'=>$data['pin_num'],
                'stock'=>$data['num'],
                'start_time'=>$start_time,
                'end_time'=>$end_time,
                'remark'=>$data['remark'],
                'checked'=>1,
                'apply_time'=>$data['apply_time'],
                'checked_time'=>time(),
                'is_show'=>1,
                'shop_id'=>$data['shop_id']
            ));
            if($lastId){
                $value = array('status'=>1,'mess'=>'增加成功');
            }else{
                $value = array('status'=>0,'mess'=>'增加失败');
            }

            return json($value);
        }else{
            return $this->fetch();
        }
    }
    
    public function info(){
        if(input('id')){
            $shop_id = session('shop_id');
    
            $assembles = Db::name('assemble')->alias('a')->field('a.*,b.hasoption,b.goods_name,b.shop_price,b.total,b.min_price,b.max_price,b.thumb_url,c.shop_name')->join('sp_goods b','a.goods_id = b.id','LEFT')->join('sp_shops c','a.shop_id = c.id','LEFT')->where('a.id',input('id'))->where('a.shop_id',$shop_id)->find();
            if($assembles){
                if($assembles['hasoption']){
                    $goodsCommonModel = new GoodsCommonModel();
                    $optionHtml = $goodsCommonModel->getAssembleGoodsOptionHtml($assembles['goods_id']);
                    $this->assign('optionHtml',$optionHtml);
                }
    
    
                if($assembles['checked'] == 0){
                    //待审核
                    $assembles['zhuangtai'] = 1;
                }elseif($assembles['checked'] == 1 && $assembles['start_time'] > time()){
                    //即将开始
                    $assembles['zhuangtai'] = 2;
                }elseif($assembles['checked'] == 1 && $assembles['start_time'] <= time() && $assembles['end_time'] > time()){
                    //拼团中
                    $assembles['zhuangtai'] = 3;
                }elseif($assembles['checked'] == 1 && $assembles['end_time'] <= time()){
                    //已结束
                    $assembles['zhuangtai'] = 4;
                }elseif($assembles['checked'] == 2){
                    //平台关闭
                    $assembles['zhuangtai'] = 5;
                }
                $this->assign('assembles',$assembles);
                return $this->fetch();
            }else{
                $this->error('找不到相关信息');
            }
        }else{
            $this->error('缺少参数');
        }
    }
    
    
    public function delete(){
        $shop_id = session('shop_id');
        $id = input('id');
        if(!empty($id)){
            $assembles = Db::name('assemble')->where('id',$id)->where('shop_id',$shop_id)->where('is_show',1)->find();
            if($assembles){
                if($assembles['checked'] == 1 && $assembles['start_time'] <= time() && $assembles['end_time'] > time()){
                    //拼团活动进行中
                    $value = array('status'=>0,'mess'=>'拼团活动进行中，不允许删除');
                    return json($value);
                }
                
                // 启动事务
                Db::startTrans();
                try{
                    $goodsOptionModel = new GoodsOptionModel();
                    $goodsOptionModel->unSetGoodsOptionAssembleData($assembles['goods_id']);
                    Db::name('assemble')->update(array('is_show'=>0,'id'=>$id));
                    if($assembles['checked'] == 1 && $assembles['end_time'] <= time()){
                        $goods = Db::name('goods')->where('id',$assembles['goods_id'])->field('min_price,zs_price,is_activity')->find();
                        if($goods['min_price'] != $goods['zs_price']){
                            Db::name('goods')->update(array('id'=>$assembles['goods_id'],'zs_price'=>$goods['min_price']));
                        }
                        if($goods['is_activity'] == 1){
                            Db::name('goods')->update(array('id'=>$assembles['goods_id'],'is_activity'=>0));
                        }
                    }
                    // 提交事务
                    Db::commit();
                    $value = array('status'=>1,'mess'=>'删除成功');
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $value = array('status'=>0,'mess'=>'删除失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'找不到相关信息');
            }
        }else{
            $value = array('status'=>0,'mess'=>'未选中任何数据');
        }
        return json($value);
    }
    
    
    public function search(){
        $shop_id = session('shop_id');
        
        if(input('post.keyword') != ''){
            cookie('pin_keyword',input('post.keyword'),7200);
        }else{
            cookie('pin_keyword',null);
        }
        
        if(input('post.checked') != ''){
            cookie('pin_checked',input('post.checked'),7200);
        }
        
        if(input('post.recommend') != ''){
            cookie('pin_recommend',input('post.recommend'),7200);
        }
        
        if(input('post.starttime') != ''){
            $pinstarttime = strtotime(input('post.starttime'));
            cookie('pinstarttime',$pinstarttime,7200);
        }
        
        if(input('post.endtime') != ''){
            $pinendtime = strtotime(input('post.endtime'));
            cookie('pinendtime',$pinendtime,7200);
        }
        
        $where = array();
        
        $where['a.shop_id'] = $shop_id;
        $where['a.is_show'] = 1;
        
        if(cookie('pin_keyword')){
            $where['a.pin_name'] = cookie('pin_keyword');
        }
        
        if(cookie('pin_checked') != ''){
            $pin_checked = (int)cookie('pin_checked');
            if(!empty($pin_checked)){
                switch ($pin_checked){
                    //待审核
                    case 1:
                        $where['a.checked'] = 0;
                        break;
                        //即将开始
                    case 2:
                        $where['a.checked'] = 1;
                        $where['a.start_time'] = array('gt',time());
                        break;
                        //拼团中
                    case 3:
                        $where['a.checked'] = 1;
                        $where['a.start_time'] = array('elt',time());
                        $where['a.end_time'] = array('gt',time());
                        break;
                        //已结束
                    case 4:
                        $where['a.checked'] = 1;
                        $where['a.end_time'] = array('elt',time());
                        break;
                        //平台关闭
                    case 5:
                        $where['a.checked'] = 2;
                        break;
                }
            }
        }
        
        if(cookie('pin_recommend') != ''){
            $pin_recommend = (int)cookie('pin_recommend');
            if(!empty($pin_recommend)){
                switch ($pin_recommend){
                    //推荐
                    case 1:
                        $where['a.recommend'] = 1;
                        break;
                        //未推荐
                    case 2:
                        $where['a.recommend'] = 0;
                        break;
                }
            }
        }
        
        if(cookie('pinendtime') && cookie('pinstarttime')){
            $where['a.apply_time'] = array(array('egt',cookie('pinstarttime')), array('elt',cookie('pinendtime')));
        }
        
        if(cookie('pinstarttime') && !cookie('pinendtime')){
            $where['a.apply_time'] = array('egt',cookie('pinstarttime'));
        }
        
        if(cookie('pinendtime') && !cookie('pinstarttime')){
            $where['a.apply_time'] = array('elt',cookie('pinendtime'));
        }


        $list = Db::name('assemble')->alias('a')->field('a.*,b.goods_name')->join('sp_goods b','a.goods_id = b.id','LEFT')->where($where)->order('a.apply_time desc')->paginate(25);
        $page = $list->render();
        
        $listres = $list->toArray();
        $list = $listres['data'];
        
        if($list){
            foreach ($list as $k => $v){
                if($v['checked'] == 0){
                    //待审核
                    $list[$k]['zhuangtai'] = 1;
                }elseif($v['checked'] == 1 && $v['start_time'] > time()){
                    //即将开始
                    $list[$k]['zhuangtai'] = 2;
                }elseif($v['checked'] == 1 && $v['start_time'] <= time() && $v['end_time'] > time()){
                    //拼团中
                    $list[$k]['zhuangtai'] = 3;
                }elseif($v['checked'] == 1 && $v['end_time'] <= time()){
                    //已结束
                    $list[$k]['zhuangtai'] = 4;
                }elseif($v['checked'] == 2){
                    //平台关闭
                    $list[$k]['zhuangtai'] = 5;
                }
        
                if($v['goods_attr']){
                    $list[$k]['goods_attr_str'] = '';
                    $gares = Db::name('goods_attr')->alias('a')->field('a.*,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$v['goods_attr'])->where('a.goods_id',$v['goods_id'])->where('b.attr_type',1)->select();
                    if($gares){
                        foreach ($gares as $key => $val){
                            if($key == 0){
                                $list[$k]['goods_attr_str'] = $val['attr_name'].':'.$val['attr_value'];
                            }else{
                                $list[$k]['goods_attr_str'] = $list[$k]['goods_attr_str'].' '.$val['attr_name'].':'.$val['attr_value'];
                            }
                        }
                    }
                }else{
                    $gares = array();
                    $list[$k]['goods_attr_str'] = '';
                }
            }
        }
        
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $search = 1;
        
        if(cookie('pinstarttime')){
            $this->assign('starttime',cookie('pinstarttime'));
        }
        
        if(cookie('pinendtime')){
            $this->assign('endtime',cookie('pinendtime'));
        }
        
        if(cookie('pin_recommend')){
            $this->assign('recommend',cookie('pin_recommend'));
        }
        
        if(cookie('pin_checked')){
            $this->assign('checked',cookie('pin_checked'));
        }
        
        if(cookie('pin_keyword')){
            $this->assign('keyword',cookie('pin_keyword'));
        }
        
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        $this->assign('filter',10);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }        
    }
    
    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $shop_id = session('shop_id');
        $name = input('post.name');
        $value = input('post.value');
        $rushs = Db::name('assemble')->where('id',$id)->where('shop_id',$shop_id)->find();
        if($rushs){
            $data[$name] = $value;
            $data['id'] = $id;
            $count = Db::name('assemble')->update($data);
            if($count > 0){
                $result = 1;
            }else{
                $result = 0;
            }
        }else{
            $result = 0;
        }
        return $result;
    }

}