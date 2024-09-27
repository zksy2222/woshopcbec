<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use http\Message\Body;
use think\Db;

class RechargeOrder extends Common{

    public function lst(){
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3))){
            $filter = 1;
        }
        
        $where = array();
    
        switch ($filter){
            //已支付
            case 3:
                $where = array('pay_status'=>1);
                break;
            //待支付
            case 2:
                $where = array('pay_status'=>0);
                break;
            case 3:

                break;
        }
        $list = Db::name('recharge_order a')->join('member b','a.uid = b.id')->where($where)->field('a.*,b.user_name')->order('create_time desc')->paginate(25);

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
            $orders = Db::name('recharge_order')->where('id',$id)->where('pay_status',0)->field('id')->find();
            if($orders){
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('recharge_order')->where('id',$id)->delete();
                    // 提交事务
                    Db::commit();
                    ys_admin_logs('删除充值订单','order_zong',$id);
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

        if(input('post.order_zt') != ''){
            cookie("recharge_order_zt", input('post.order_zt'), 7200);
        }

        if(input('post.zf_type') != ''){
            cookie("recharge_zf_type", input('post.zf_type'), 7200);
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
            $where['order_number'] = cookie('recharge_keyword');
        }

        $nowtime = time();

        if(cookie('recharge_order_zt') != ''){
            $order_zt = (int)cookie('recharge_order_zt');

            if($order_zt != 0){
                switch($order_zt){
                    //已支付
                    case 1:
                        $where['pay_status'] = 1;
                        break;
                    //待支付
                    case 2:
                        $where['pay_status'] = 0;
                        break;
                }
            }
        }

        if(cookie('recharge_zf_type') != ''){
            $zf_type = (int)cookie('recharge_zf_type');
            if($zf_type != 0){
                switch($zf_type){
                    //微信支付
                    case 1:
                        $where['pay_way'] = 1;
                        break;
                    //支付宝支付
                    case 2:
                        $where['pay_way'] = 2;
                        break;
                    //银行卡支付
                    case 5:
                        $where['pay_way'] = 5;
                        break;
                    //USDTTRC20支付
                    case 6:
                        $where['pay_way'] = 6;
                        break;
                    //USDTERC20支付
                    case 7:
                        $where['pay_way'] = 7;
                        break;
                }
            }
        }

        if(cookie('rechargeendtime') && cookie('rechargestarttime')){
            $where['create_time'] = array(array('egt',cookie('rechargestarttime')), array('lt',cookie('rechargeendtime')));
        }

        if(cookie('rechargestarttime') && !cookie('rechargeendtime')){
            $where['create_time'] = array('egt',cookie('rechargestarttime'));
        }

        if(cookie('rechargeendtime') && !cookie('rechargestarttime')){
            $where['create_time'] = array('lt',cookie('rechargeendtime'));
        }

        $list = Db::name('recharge_order a')->join('member b','a.uid = b.id')->where($where)->field('a.*,b.user_name')->order('create_time desc')->paginate(25);

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

        if(cookie('recharge_order_zt') != ''){
            $this->assign('order_zt',cookie('recharge_order_zt'));
        }

        if(cookie('recharge_zf_type') != ''){
            $this->assign('zf_type',cookie('recharge_zf_type'));
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
                $rechargeOrderId = input('post.id');
                $rechargeOrderInfos = Db::name('recharge_order')->where('id',$rechargeOrderId)->find();
                if($rechargeOrderInfos){
                    if(in_array(input('post.checked'), array(0,1))){

                        //处理钱包逻辑
                        $walletInfo = db('wallet')->where('user_id',$rechargeOrderInfos['uid'])->find();
                        $newPrice = $walletInfo['price'] + $rechargeOrderInfos['order_price'];
                        $detailData = [];
                        $detailData['de_type'] = 1;
                        $detailData['sr_type'] = 7;
                        $detailData['user_id'] = $rechargeOrderInfos['uid'];
                        $detailData['wat_id'] = $walletInfo['id'];
                        $detailData['price'] = $rechargeOrderInfos['order_price'];
                        $detailData['time'] = time();

                        // 启动事务
                        Db::startTrans();
                        try{
                            Db::name('recharge_order')->update(array('pay_status'=>input('post.checked'),'id'=>$rechargeOrderId));
                            db('wallet')->where('user_id',$rechargeOrderInfos['uid'])->update(['price'=>$newPrice]);
                            $detailId = db('detail')->insertGetId($detailData);

                            // 提交事务
                            Db::commit();
                            ys_admin_logs('后台操作钱包金额','wallet',$walletInfo['id']);
                            ys_admin_logs('后台操作钱包明细','detail',$detailId);
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
            $rechargeOrderInfo = Db::name('recharge_order a')->join('member b','a.uid = b.id')->where('a.id',$id)->field('a.*,b.user_name')->find();
            if($rechargeOrderInfo['pay_way'] == 5){
                $rechargeOrderInfo['order_card'] = Db::name('order_card')->where('order_number',$rechargeOrderInfo['order_number'])->find();
            }elseif ($rechargeOrderInfo['pay_way'] == 6 || $rechargeOrderInfo['pay_way'] == 7){
                $rechargeOrderInfo['order_usdt'] = Db::name('order_usdt')->where('order_number',$rechargeOrderInfo['order_number'])->find();
            }
            $this->assign('pnum',input('page'));
            $this->assign('filter',input('filter'));
            $this->assign('rechargeOrderInfo',$rechargeOrderInfo);
            return $this->fetch();
        }
    }

}
