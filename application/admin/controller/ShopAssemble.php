<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use app\common\model\Goods as GoodsCommonModel;
use think\Db;

class ShopAssemble extends Common{
    
    public function lst(){
        $shop_id = session('shop_id');
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3,4,5,10))){
            $filter = 10;
        }
    
        $where = array();
        $where['a.is_show'] = 1;
        $where['a.shop_id'] = array('neq',$shop_id);
        
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
                //抢购中
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
                    //抢购中
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

    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $shop_id = session('shop_id');
        $name = input('post.name');
        $value = input('post.value');
        $assembles = Db::name('assemble')->where('id',$id)->where('checked',1)->where('is_show',1)->where('shop_id','neq',$shop_id)->find();
        if($assembles){
            $data[$name] = $value;
            $data['id'] = $id;
            $count = Db::name('assemble')->update($data);
            if($count > 0){
                ys_admin_logs('修改商家拼团活动推荐状态','assemble',$id);
                $result = 1;
            }else{
                $result = 0;
            }
        }else{
            $result = 0;
        }
        return $result;
    }
    
    public function checked(){
        if(request()->isPost()){
            $shop_id = session('shop_id');
            $data = input('post.');
            if(!empty($data['id'])){
                if(!empty($data['checked']) && in_array($data['checked'], array(1,2))){
                    $assembles = Db::name('assemble')->where('id',$data['id'])->where('checked',0)->where('is_show',1)->where('shop_id','neq',$shop_id)->find();
                    if($assembles){
                        if($data['checked'] == 1){
                            // 启动事务
                            Db::startTrans();
                            try{
                                if($assembles['start_time'] <= time() && $assembles['end_time'] > time()){
                                    //活动进行中更新活动标识
                                    Db::name('assemble')->where('id',$data['id'])->update(array('checked'=>$data['checked'],'hd_bs'=>1,'checked_time'=>time()));
                                    //活动进行中更新商品展示价格
                                    Db::name('goods')->update(array('id'=>$assembles['goods_id'],'zs_price'=>$assembles['price'],'is_activity'=>1));
                                }else{
                                    Db::name('assemble')->where('id',$data['id'])->update(array('checked'=>$data['checked'],'checked_time'=>time()));
                                }
                                // 提交事务
                                Db::commit();
                                ys_admin_logs('审核通过商家拼团活动','assemble',$data['id']);
                                $value = array('status'=>1, 'mess'=>'设置成功');
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                $value = array('status'=>0, 'mess'=>'设置失败');
                            }
                        }elseif($data['checked'] == 2){
                            if(input('post.refuse_remarks')){
                                $refuse_remarks = input('post.refuse_remarks');
                                $count = Db::name('assemble')->where('id',$data['id'])->update(array('checked'=>$data['checked'],'checked_time'=>time(),'refuse_remarks'=>$refuse_remarks));
                            }else{
                                $value = array('status'=>0, 'mess'=>'请填写拒绝原因');
                                return json($value);
                            }
                            
                            if($count !== false){
                                ys_admin_logs('审核不通过商家拼团活动','assemble',$data['id']);
                                $value = array('status'=>1,'mess'=>'设置成功');
                            }else{
                                $value = array('status'=>0,'mess'=>'设置失败');
                            }
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关信息');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'参数错误');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数');
            }
            return json($value);
        }else{
            if(input('id')){
                $shop_id = session('shop_id');
                
                $assembles = Db::name('assemble')->alias('a')->field('a.*,b.goods_name,b.shop_price,b.min_price,b.max_price,b.thumb_url,c.shop_name')->join('sp_goods b','a.goods_id = b.id','LEFT')->join('sp_shops c','a.shop_id = c.id','LEFT')->where('a.id',input('id'))->where('a.checked',0)->where('a.is_show',1)->where('a.shop_id','neq',$shop_id)->find();
                if($assembles){
                    if($assembles['goods_attr']){
                        if($assembles['goods_attr'] != '*'){
                            $assembles['goods_attr_str'] = '';
                            $gares = Db::name('goods_attr')->alias('a')->field('a.*,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$assembles['goods_attr'])->where('a.goods_id',$assembles['goods_id'])->where('b.attr_type',1)->select();
                            if($gares){
                                foreach ($gares as $key => $val){
                                    if($key == 0){
                                        $assembles['goods_attr_str'] = $val['attr_name'].':'.$val['attr_value'];
                                    }else{
                                        $assembles['goods_attr_str'] = $assembles['goods_attr_str'].' '.$val['attr_name'].':'.$val['attr_value'];
                                    }
                                }
                            
                                $shop_price = $assembles['shop_price'];
                                foreach ($gares as $v){
                                    $shop_price+=$v['attr_price'];
                                }
                                $shop_price=sprintf("%.2f", $shop_price);
                                $assembles['shangpin_price'] = $shop_price;
                                
                                $prores = Db::name('product')->where('goods_attr',$assembles['goods_attr'])->where('goods_id',$assembles['goods_id'])->field('goods_number')->find();
                                if($prores){
                                    $goods_number = $prores['goods_number'];
                                }else{
                                    $goods_number = 0;
                                }
                                $assembles['goods_number'] = $goods_number;
                            }
                        }else{
                            $gares = array();
                            $assembles['goods_attr_str'] = '';
                            $assembles['shangpin_price'] = $assembles['min_price'];
                            
                            $prores = Db::name('product')->where('goods_id',$assembles['goods_id'])->field('goods_number')->select();
                            if($prores){
                                $goods_number = 0;
                                foreach ($prores as $v){
                                    $goods_number+=$v['goods_number'];
                                }
                            }else{
                                $goods_number = 0;
                            }
                            $assembles['goods_number'] = $goods_number;
                        }
                    }else{
                        $gares = array();
                        $assembles['goods_attr_str'] = '';
                        $assembles['shangpin_price'] = $assembles['min_price'];
                        
                        $prores = Db::name('product')->where('goods_id',$assembles['goods_id'])->field('goods_number')->select();
                        if($prores){
                            $goods_number = 0;
                            foreach ($prores as $v){
                                $goods_number+=$v['goods_number'];
                            }
                        }else{
                            $goods_number = 0;
                        }
                        $assembles['goods_number'] = $goods_number;
                    }
                    
                    if(input('s')){
                        $this->assign('search', input('s'));
                    }
                    $this->assign('pnum', input('page'));
                    $this->assign('filter',input('filter'));
                    $this->assign('assembles',$assembles);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }

    public function info(){
        if(input('id')){
            $shop_id = session('shop_id');

            $assembles = Db::name('assemble')
                           ->alias('a')
                           ->field('a.*,b.hasoption,b.goods_name,b.shop_price,b.min_price,b.max_price,b.thumb_url,c.shop_name')
                           ->join('sp_goods b','a.goods_id = b.id','LEFT')
                           ->join('sp_shops c','a.shop_id = c.id','LEFT')
                           ->where('a.id',input('id'))
                           ->where('a.is_show',1)
                           ->where('a.shop_id','neq',$shop_id)
                           ->find();
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
                    //抢购中
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
    
    public function search(){
        $shop_id = session('shop_id');
        
        if(input('post.keyword') != ''){
            cookie('cppin_keyword',input('post.keyword'),7200);
        }else{
            cookie('cppin_keyword',null);
        }
        
        if(input('post.shop_name') != ''){
            cookie('cppin_shop_name',input('post.shop_name'),7200);
        }else{
            cookie('cppin_shop_name',null);
        }
        
        if(input('post.checked') != ''){
            cookie('cppin_checked',input('post.checked'),7200);
        }
        
        if(input('post.starttime') != ''){
            $cppinstarttime = strtotime(input('post.starttime'));
            cookie('cppinstarttime',$cppinstarttime,7200);
        }
        
        if(input('post.endtime') != ''){
            $cppinendtime = strtotime(input('post.endtime'));
            cookie('cppinendtime',$cppinendtime,7200);
        }
        
        $where = array();
        
        $where['a.is_show'] = 1;
        $where['a.shop_id'] = array('neq',$shop_id);

        if(cookie('cppin_keyword')){
            $where['a.pin_name'] = cookie('cppin_keyword');
        }
        
        if(cookie('cppin_shop_name')){
            $shops = Db::name('shops')->where('shop_name',cookie('cppin_shop_name'))->where('id','neq',$shop_id)->field('id')->find();
            if($shops){
                $where['a.shop_id'] = $shops['id'];
            }else{
                $where['a.shop_id'] = 'aa';
            }
        }
        
        if(cookie('cppin_checked') != ''){
            $cppin_checked = (int)cookie('cppin_checked');
            if(!empty($cppin_checked)){
                switch ($cppin_checked){
                    //待审核
                    case 1:
                        $where['a.checked'] = 0;
                        break;
                        //即将开始
                    case 2:
                        $where['a.checked'] = 1;
                        $where['a.start_time'] = array('gt',time());
                        break;
                        //抢购中
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
        
        if(cookie('cppinendtime') && cookie('cppinstarttime')){
            $where['a.apply_time'] = array(array('egt',cookie('cppinstarttime')), array('elt',cookie('cppinendtime')));
        }
        
        if(cookie('cppinstarttime') && !cookie('cppinendtime')){
            $where['a.apply_time'] = array('egt',cookie('cppinstarttime'));
        }
        
        if(cookie('cppinendtime') && !cookie('cppinstarttime')){
            $where['a.apply_time'] = array('elt',cookie('cppinendtime'));
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
                    //抢购中
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
        
        if(cookie('cppinstarttime')){
            $this->assign('starttime',cookie('cppinstarttime'));
        }
        
        if(cookie('cppinendtime')){
            $this->assign('endtime',cookie('cppinendtime'));
        }
        
        if(cookie('cppin_checked')){
            $this->assign('checked',cookie('cppin_checked'));
        }
        
        if(cookie('cppin_shop_name')){
            $this->assign('shop_name',cookie('cppin_shop_name'));
        }
        
        if(cookie('cppin_keyword')){
            $this->assign('keyword',cookie('cppin_keyword'));
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
    
}