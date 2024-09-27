<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use http\Message\Body;
use think\Db;

class ShopRecharge extends Common{

    public function lst(){
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3))){
            $filter = 1;
        }
        
        $where = array();
    
        switch ($filter){
            //已支付
            case 3:
                $where = array('checked'=>1);
                break;
            //待支付
            case 2:
                $where = array('checked'=>0);
                break;
            case 3:

                break;
        }
        $list = Db::name('shop_recharge a')->join('shops b','a.shop_id = b.id')->where($where)->field('a.*,b.shop_name')->order('a.create_time desc')->paginate(25);

        $page = $list->render();
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $this->assign('filter',$filter);
        $this->assign('pnum',$pnum);
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('list',$list);// 赋值数据集
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    public function delete(){
        if(input('id') && !is_array(input('id'))){
            $id = input('id');
            $orders = Db::name('shop_recharge')->where('id',$id)->where('checked',0)->field('id')->find();
            if($orders){
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('shop_recharge')->where('id',$id)->delete();
                    // 提交事务
                    Db::commit();
                    ys_admin_logs('删除商家充值订单','shop_recharge',$id);
                    $value = array('status'=>1,'mess'=>'删除成功');
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $value = array('status'=>0,'mess'=>'删除失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'已支付订单不可删除');
            }
        }else{
            $value = array('status'=>0,'mess'=>'删除失败');
        }
        return json($value);
    }

    public function search(){
        if(input('post.keyword') != ''){
            cookie('recharge_keyword',input('post.keyword'),7200);
        }else{
            cookie('recharge_keyword',null);
        }

        if(input('post.cz_zt') != ''){
            cookie("recharge_cz_zt", input('post.cz_zt'), 7200);
        }

        if(input('post.starttime') != ''){
            $rechargestarttime = strtotime(input('post.starttime'));
            cookie('rechargestarttime',$rechargestarttime,7200);
        }

        if(input('post.endtime') != ''){
            $rechargeendtime = strtotime(input('post.endtime'));
            cookie('rechargeendtime',$rechargeendtime,7200);
        }

        $where = array();

        if(cookie('recharge_keyword')){
            $where['b.shop_name'] = array('like','%'.cookie('recharge_keyword').'%');
        }

        $nowtime = time();

        if(cookie('recharge_cz_zt') != ''){
            $order_zt = (int)cookie('recharge_cz_zt');

            if($order_zt != 0){
                switch($order_zt){
                    //已支付
                    case 1:
                        $where['a.checked'] = 1;
                        break;
                    //待支付
                    case 2:
                        $where['a.checked'] = 0;
                        break;
                }
            }
        }

        if(cookie('rechargeendtime') && cookie('rechargestarttime')){
            $where['a.create_time'] = array(array('egt',cookie('rechargestarttime')), array('lt',cookie('rechargeendtime')));
        }

        if(cookie('rechargestarttime') && !cookie('rechargeendtime')){
            $where['a.create_time'] = array('egt',cookie('rechargestarttime'));
        }

        if(cookie('rechargeendtime') && !cookie('rechargestarttime')){
            $where['a.create_time'] = array('lt',cookie('rechargeendtime'));
        }

        $list = Db::name('shop_recharge a')->join('shops b','a.shop_id = b.id')->where($where)->field('a.*,b.shop_name')->order('a.create_time desc')->paginate(25);

        $page = $list->render();

        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }

        $search = 1;

        if(cookie('rechargestarttime')){
            $this->assign('starttime',cookie('rechargestarttime'));
        }

        if(cookie('rechargeendtime')){
            $this->assign('endtime',cookie('rechargeendtime'));
        }

        if(cookie('recharge_keyword')){
            $this->assign('keyword',cookie('recharge_keyword'));
        }

        if(cookie('recharge_cz_zt') != ''){
            $this->assign('cz_zt',cookie('recharge_cz_zt'));
        }


        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        $this->assign('filter',1);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }

    public function checked(){
        if(request()->isPost()){
            if(input('post.id')){
                $shopRechargeId = input('post.id');
                $shopRechargeInfos = Db::name('shop_recharge')->where('id',$shopRechargeId)->find();
                if($shopRechargeInfos){
                    if(in_array(input('post.checked'), array(0,1))){
                        $shopWalletId = db('shop_wallet')->where('shop_id',$shopRechargeInfos['shop_id'])->value('id');
                        $data = [];
                        $data['de_type'] = 1;
                        $data['sr_type'] = 2;
                        $data['price'] = $shopRechargeInfos['price'];
                        $data['order_type'] = 5;
                        $data['shop_id'] = $shopRechargeInfos['shop_id'];
                        $data['wat_id'] = $shopWalletId;
                        $data['time'] = time();

                        // 启动事务
                        Db::startTrans();
                        try{
                            Db::name('shop_recharge')->update(array('checked'=>input('post.checked'),'id'=>$shopRechargeId));
                            //处理商家钱包逻辑
                            Db::name('shop_wallet')->where('shop_id',$shopRechargeInfos['shop_id'])->setInc('price',$shopRechargeInfos['price']);

                            $detailId = Db::name('shop_detail')->insertGetId($data);

                            // 提交事务
                            Db::commit();
                            ys_admin_logs('商家充值钱包金额','shop_wallet',$shopWalletId);
                            ys_admin_logs('商家充值明细','shop_detail',$detailId);
                            $value = array('status'=>1, 'mess'=>'操作成功');
                        } catch (\Exception $e) {
                            // 回滚事务
                            dump($e->getMessage());die;
                            Db::rollback();
                            $value = array('status'=>0, 'mess'=>'操作失败');
                        }
                    }else{
                        $value = array('status'=>0, 'mess'=>'参数错误');
                    }
                }else{
                    $value = array('status'=>0, 'mess'=>'找不到相关信息');
                }
            }else{
                $value = array('status'=>0, 'mess'=>'缺少参数');
            }
            return $value;
        }else{
            $id = input('id');
            $shopRecharge = Db::name('shop_recharge a')->join('shops b','a.shop_id = b.id')->where('a.id',$id)->field('a.*,b.shop_name')->find();
            $this->assign('pnum',input('page'));
            $this->assign('filter',input('filter'));
            $this->assign('shopRecharge',$shopRecharge);
            return $this->fetch();
        }
    }

}
