<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Detail extends Common{
    public function lst(){
        $userId = input('user_id');
        if(empty($userId)){
            $this->error('缺少用户信息');
        }

        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3))){
            $filter = 3;
        }

        $members = Db::name('member')->where('id',$userId)->field('user_name')->find();
        if($members){
            $where = array();

            switch ($filter){
                case 1:
                    //收入
                    $where = array('a.user_id'=>$userId,'a.de_type'=>1);
                    break;
                case 2:
                    //支出
                    $where = array('a.user_id'=>$userId,'a.de_type'=>2);
                    break;
                case 3:
                    //全部
                    $where = array('a.user_id'=>$userId);
                    break;
            }

            $list = Db::name('detail')
                ->alias('a')
                ->field('a.*,b.user_name')
                ->join('sp_member b','a.user_id = b.id','LEFT')
                ->where($where)->order('a.time desc')
                ->paginate(25)
                ->each(function ($item){
                        $item['withdraw'] = db('withdraw')->where('id',$item['tx_id'])->field('create_time,wtime')->find();
                    return $item;
                });;

            $page = $list->render();
            if(input('page')){
                $pnum = input('page');
            }else{
                $pnum = 1;
            }

            $wallet = Db::name('wallet')->where('user_id',$userId)->find();
            $totalprice = $wallet['price'];
            $this->assign(array(
                'list'=>$list,
                'page'=>$page,
                'pnum'=>$pnum,
                'filter'=>$filter,
                'user_name'=>$members['user_name'],
                'totalprice'=>$totalprice,
                'user_id'=>$userId
            ));
            if(request()->isAjax()){
                return $this->fetch('ajaxpage');
            }else{
                return $this->fetch('lst');
            }
        }else{
            $this->error('用户不存在');
        }

    }
    
    public function info(){
        if(input('de_id') && input('user_id')){
            $de_id = input('de_id');
            $userId = input('user_id');
            $details = Db::name('detail')->alias('a')->field('a.*,b.user_name')->join('sp_member b','a.user_id = b.id','LEFT')->where('a.id',$de_id)->where('a.user_id',$userId)->find();
            if($details){
                if($details['de_type'] == 1){
                    //收入
                    switch ($details['sr_type']){
                        //订单分成
                        case 1:
                            $details['az_number'] = Db::name('anzhuang')->where('id',$details['order_id'])->value('az_number');
                            if(!$details['az_number']){
                                $this->error('获取失败');
                            }
                            break;
                        //订单退款
                        case 2:
                            $details['th_number'] = Db::name('th_apply')->where('order_id',$details['order_id'])->value('th_number');
                            if(!$details['th_number']){
                                $this->error('获取失败');
                            }
                            break;
                    }
                }elseif($details['de_type'] == 2){
                    //支出
                    switch ($details['zc_type']){
                        //提现
                        case 1:
                            $details['tx_number'] = Db::name('withdraw')->where('id',$details['tx_id'])->value('tx_number');
                            if(!$details['tx_number']){
                                $this->error('获取失败');
                            }
                            break;
                    }
                }
                $this->assign('details',$details);
                return $this->fetch();
            }else{
                $this->error('明细信息错误');
            }
        }else{
            $this->error('明细信息错误');
        }
    }
    
    public function search(){
        if(input('user_id')){
            $where = array();
            $userId = input('user_id');
            $members = Db::name('member')->where('id',$userId)->field('user_name')->find();
            if($members){
                $wallet = Db::name('wallet')->where('user_id',$userId)->find();
                $totalprice = $wallet['price'];
                
                $where['a.user_id'] = $userId;
                
                if(input('post.de_zt') != ''){
                    cookie("de_zt", input('post.de_zt'), 7200);
                }
                
                if(input('post.starttime') != ''){
                    $destarttime = strtotime(input('post.starttime'));
                    cookie('destarttime',$destarttime,3600);
                }
                
                if(input('post.endtime') != ''){
                    $deendtime = strtotime(input('post.endtime'));
                    cookie('deendtime',$deendtime,3600);
                }
                
                if(cookie('de_zt') != ''){
                    $de_zt = (int)cookie('de_zt');
                    if($de_zt != 0){
                        switch($de_zt){
                            //收入
                            case 1:
                                $where['a.de_type'] = 1;
                                break;
                                //支出
                            case 2:
                                $where['a.de_type'] = 2;
                                break;
                        }
                    }
                }
                 
                
                if(cookie('deendtime') && cookie('destarttime')){
                    $where['a.time'] = array(array('egt',cookie('destarttime')), array('lt',cookie('deendtime')));
                }
                
                if(cookie('destarttime') && !cookie('deendtime')){
                    $where['a.time'] = array('egt',cookie('destarttime'));
                }
                
                if(cookie('deendtime') && !cookie('destarttime')){
                    $where['a.time'] = array('lt',cookie('deendtime'));
                }
                
                $list = Db::name('detail')->alias('a')->field('a.*,b.user_name')->join('sp_member b','a.user_id = b.id','LEFT')->where($where)->order('a.time desc')->paginate(50);
                $page = $list->render();
                
                if(input('page')){
                    $pnum = input('page');
                }else{
                    $pnum = 1;
                }
                $search = 1;
                
                if(cookie('destarttime')){
                    $this->assign('starttime',cookie('destarttime'));
                }
                
                if(cookie('deendtime')){
                    $this->assign('endtime',cookie('deendtime'));
                }
                
                if(cookie('de_zt') != ''){
                    $this->assign('de_zt',cookie('de_zt'));
                }
                
                if(input('post.filter')){
                    $filter = input('post.filter');
                }else{
                    $filter = 3;
                }
                
                $this->assign('search',$search);
                $this->assign('pnum', $pnum);
                $this->assign('filter',$filter);
                $this->assign('user_id',$userId);
                $this->assign('user_name',$members['user_name']);
                $this->assign('totalprice',$totalprice);
                $this->assign('list', $list);// 赋值数据集
                $this->assign('page', $page);// 赋值分页输出
                if(request()->isAjax()){
                    return $this->fetch('ajaxpage');
                }else{
                    return $this->fetch('lst');
                }
            }else{
                $this->error('找不到相关用户');
            }
        }else{
            $this->error('缺少用户id');
        }    
    }

    //余额充值
    public function addBalance(){
        if(request()->isAjax()){
            $admin_id = session('admin_id');
            $data = input('post.');
            $password = db('admin')->where('id',$admin_id)->value('password');
            if(empty($data['password']) || $password != md5($data['password'])){
                return json(array('status' => 0, 'mess' => '密码错误'));
            }
            $walletInfo = db('wallet')->where('user_id',$data['user_id'])->find();
            $detailData = [];
            switch ($data['type']) {
                //增加
                case 0:
                    $newPrice = $walletInfo['price'] + $data['price'];
                    $detailData['de_type'] = 1;
                    $detailData['sr_type'] = 6;
                    $detailData['user_id'] = $data['user_id'];
                    $detailData['wat_id'] = $walletInfo['id'];
                    $detailData['price'] = $data['price'];
                    $detailData['time'] = time();
                    break;
                //减少
                case 1:
                    if($data['price'] > $walletInfo['price']){
                        return json(array('status' => 0, 'mess' => '账号余额不足'));
                    }
                    $newPrice = $walletInfo['price'] - $data['price'];
                    $detailData['de_type'] = 2;
                    $detailData['zc_type'] = 3;
                    $detailData['user_id'] = $data['user_id'];
                    $detailData['wat_id'] = $walletInfo['id'];
                    $detailData['price'] = $data['price'];
                    $detailData['time'] = time();
                    break;
                //最终余额
                case 2:
                    $newPrice = $data['price'];
                    if($data['price'] >= $walletInfo['price']){
                        $detailData['de_type'] = 1;
                        $detailData['sr_type'] = 6;
                        $detailData['price'] = $data['price'] - $walletInfo['price'];
                    }else{
                        $detailData['de_type'] = 2;
                        $detailData['zc_type'] = 3;
                        $detailData['price'] = $walletInfo['price'] - $data['price'] ;
                    }

                    $detailData['user_id'] = $data['user_id'];
                    $detailData['wat_id'] = $walletInfo['id'];
                    $detailData['time'] = time();
                    break;

            }
            // 启动事务
            Db::startTrans();
            try{
                db('wallet')->where('user_id',$data['user_id'])->update(['price'=>$newPrice]);
                $detailId = db('detail')->insertGetId($detailData);

                // 提交事务
                Db::commit();
                ys_admin_logs('后台操作钱包金额','wallet',$walletInfo['id']);
                ys_admin_logs('后台操作钱包明细','detail',$detailId);
                $value = array('status'=>1, 'mess'=>'操作成功');
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $value = array('status'=>0, 'mess'=>'操作失败');
            }
            return json($value);

        }else{
            $userId = input('user_id');
            if(empty($userId)){
                $this->error('缺少用户信息');
            }
            $memberInfo = db('member a')
                ->join('wallet b','a.id = b.user_id')
                ->where('a.id',$userId)
                ->field('a.user_name,b.price,b.user_id')
                ->find();

            $this->assign('memberInfo',$memberInfo);
            return $this->fetch();
        }

    }

    //积分充值
    public function addIntegral(){
        if(request()->isAjax()){
            $admin_id = session('admin_id');
            $data = input('post.');
            $password = db('admin')->where('id',$admin_id)->value('password');

            if(empty($data['password']) || $password != md5($data['password'])){
                return json(array('status' => 0, 'mess' => '密码错误'));
            }
            $integralInfo = db('member')->where('id',$data['user_id'])->field('id,integral')->find();
            $integralData = [];
            switch ($data['type']) {
                //增加
                case 0:
                    $newIntegral= $integralInfo['integral'] + $data['integral'];
                    $integralData['type'] = 14;
                    $integralData['class'] = 0;
                    $integralData['user_id'] = $data['user_id'];
                    $integralData['integral'] = $data['integral'];
                    $integralData['addtime'] = time();
                    break;
                //减少
                case 1:
                    if($data['integral'] > $integralInfo['integral']){
                        return json(array('status' => 0, 'mess' => '账号余额不足'));
                    }
                    $newIntegral = $integralInfo['integral'] - $data['integral'];
                    $integralData['type'] = 14;
                    $integralData['class'] = 1;
                    $integralData['user_id'] = $data['user_id'];
                    $integralData['integral'] = $data['integral'];
                    $integralData['addtime'] = time();
                    break;
                //最终积分
                case 2:
                    $newIntegral = $data['integral'];
                    if($data['integral'] >= $integralInfo['integral']){
                        $integralData['type'] = 14;
                        $integralData['class'] = 0;
                        $integralData['integral'] = $data['integral'] - $integralInfo['integral'];
                    }else{
                        $integralData['type'] = 14;
                        $integralData['class'] = 1;
                        $integralData['integral'] = $integralInfo['integral'] - $data['integral'] ;
                    }

                    $integralData['user_id'] = $data['user_id'];
                    $integralData['time'] = time();
                    break;

            }
            // 启动事务
            Db::startTrans();
            try{
                db('member')->where('id',$data['user_id'])->update(['integral'=>$newIntegral]);
                $memberIntegralId = db('member_integral')->insertGetId($integralData);

                // 提交事务
                Db::commit();
                ys_admin_logs('后台操作会员积分','member',$integralInfo['id']);
                ys_admin_logs('后台操作会员积分记录','member_integral',$memberIntegralId);
                $value = array('status'=>1, 'mess'=>'操作成功');
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $value = array('status'=>0, 'mess'=>'操作失败');
            }
            return json($value);

        }else{
            $userId = input('user_id');
            if(empty($userId)){
                $this->error('缺少用户信息');
            }
            $memberInfo = db('member')
                ->where('id',$userId)
                ->field('user_name,integral,id')
                ->find();

            $this->assign('memberInfo',$memberInfo);
            return $this->fetch();
        }

    }

}
