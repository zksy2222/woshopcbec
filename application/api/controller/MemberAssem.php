<?php
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use think\Db;

class MemberAssem extends Common{
    //获取拼团详情状态接口
    public function info(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }else{
            $userId = $tokenRes['user_id'];
        }
        if(!input('post.order_num')){
            datamsg(400,'缺少订单号',array('status'=>400));
        }
        $order_num = input('post.order_num');
        $orders = Db::name('order')
                    ->where('ordernumber',$order_num)
                    ->where('user_id',$userId)
                    ->where('order_type',2)
                    ->where('state',1)
                    ->where('is_show',1)
                    ->field('id,ordernumber,order_type,pin_type,pin_id')
                    ->find();
        if(!$orders){
            datamsg(400,'找不到相关拼团信息',array('status'=>400));
        }

        if($orders['pin_type'] == 1){
            $pintuans = Db::name('pintuan')->where('id',$orders['pin_id'])->where('tz_id',$userId)->where('state',1)->field('id,pin_num,tuan_num,pin_status,timeout')->find();
        }elseif($orders['pin_type'] == 2){
            $pintuans = Db::name('pintuan')->where('id',$orders['pin_id'])->where('tz_id','neq',$userId)->where('state',1)->field('id,pin_num,tuan_num,pin_status,timeout')->find();
        }else{
            datamsg(400,'找不到相关拼团信息',array('status'=>400));
        }

        if(!$pintuans){
            datamsg(400,'找不到相关拼团信息',array('status'=>400));
        }

        $order_assembles = Db::name('order_assemble')->where('pin_id',$pintuans['id'])->where('order_id',$orders['id'])->where('pin_type',$orders['pin_type'])->where('user_id',$userId)->where('state',1)->where('tui_status',0)->find();
        if(!$order_assembles){
            datamsg(400,'找不到相关拼团信息',array('status'=>400));
        }

        $webconfig = $this->webconfig;
        $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();

        foreach ($member_assem as $key => $val){
            $member_assem[$key]['headimgurl'] = url_format($val['headimgurl'],$webconfig['weburl'],'?imageMogr2/thumbnail/350x350');
        }

        if($pintuans['pin_status'] == 0 && $pintuans['timeout'] > time()){
            if($order_assembles['pin_type'] == 1){
                $pininfo = lang('发起拼团成功');
                $zhuangtai = 1;
                $tuan_name = lang('快快邀请神秘的TA来参团,距结束仅剩');
            }elseif($order_assembles['pin_type'] == 2){
                $pininfo = lang('参与拼团成功');
                $zhuangtai = 1;
                $tuan_name = lang('快快邀请神秘的TA来参团,距结束仅剩');
            }
        }elseif($pintuans['pin_status'] == 1){
            $pininfo = lang('拼团成功');
            $zhuangtai = 2;
            $tuan_name = '';
            foreach ($member_assem as $k => $v){
                if($k == 0){
                    $tuan_name = $v['user_name'];
                }else{
                    $tuan_name = $tuan_name.'、'.$v['user_name'];
                }
            }
            $tuan_name = $tuan_name.lang('也算一起拼过得人了');
        }elseif(($pintuans['pin_status'] == 2) || ($pintuans['pin_status'] == 0 && $pintuans['timeout'] <= time())){
            $pininfo = lang('拼团失败');
            $zhuangtai = 3;
            $tuan_name = '';
        }else{
            datamsg(400,'找不到相关拼团信息',array('status'=>400));
        }

        $order_num = $orders['ordernumber'];
        $pin_id = $pintuans['id'];
        $tuan_id = $order_assembles['id'];
        $nowtime = time();
        $timeout = $pintuans['timeout'];

        $goodsinfo = Db::name('order_goods')->where('order_id',$orders['id'])->field('id,goods_id,goods_name,thumb_url,goods_attr_str,price,goods_num,th_status,order_id')->find();
        $goodsinfo['thumb_url'] = url_format($goodsinfo['thumb_url'],$webconfig['weburl']);
        $goodsinfo['pin_num'] = $pintuans['pin_num'];
        datamsg(200,'获取拼团状态信息成功',array('goodsinfo'=>$goodsinfo,'pininfo'=>$pininfo,'zhuangtai'=>$zhuangtai,'order_num'=>$order_num,'pin_id'=>$pin_id,'tuan_id'=>$tuan_id,'nowtime'=>$nowtime,'timeout'=>$timeout,'member_assem'=>$member_assem,'tuan_name'=>$tuan_name));
    }

    public function yaoqing(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }else{
            $userId = $tokenRes['user_id'];
        }
        if(!input('post.pin_id') || !input('post.tuan_id')){
            datamsg(400,'缺少参数',array('status'=>400));
        }

        $pin_id = input('post.pin_id');
        $tuan_id = input('post.tuan_id');

        $order_assembles = Db::name('order_assemble')->where('id',$tuan_id)->where('pin_id',$pin_id)->where('user_id',$userId)->where('state',1)->where('tui_status',0)->find();
        if(!$order_assembles){
            datamsg(400,'找不到相关拼团信息',array('status'=>400));
        }

        if($order_assembles['pin_type'] == 1){
            $pintuans = Db::name('pintuan')->where('id',$order_assembles['pin_id'])->where('tz_id',$userId)->where('state',1)->where('pin_status',0)->where('timeout','gt',time())->field('id,assem_number,goods_id,pin_num,tuan_num,pin_status,timeout')->find();
        }elseif($order_assembles['pin_type'] == 2){
            $pintuans = Db::name('pintuan')->where('id',$order_assembles['pin_id'])->where('tz_id','neq',$userId)->where('state',1)->where('pin_status',0)->where('timeout','gt',time())->field('id,assem_number,goods_id,pin_num,tuan_num,pin_status,timeout')->find();
        }else{
            datamsg(400,'找不到相关拼团信息',array('status'=>400));
        }
        if(!$pintuans){
            datamsg(400,'找不到相关拼团信息',array('status'=>400));
        }
        $orders = Db::name('order')->where('id',$order_assembles['order_id'])->where('user_id',$userId)->where('order_type',2)->where('pin_type',$order_assembles['pin_type'])->where('state',1)->where('is_show',1)->field('id,ordernumber,order_type,pin_type,pin_id')->find();
        if(!$orders){
            datamsg(400,'找不到相关拼团信息',array('status'=>400));
        }

        $goodsinfo = Db::name('order_goods')->where('order_id',$orders['id'])->field('goods_id,goods_name,goods_attr_str')->find();
        if(!$goodsinfo){
            datamsg(400,'找不到相关拼团信息',array('status'=>400));
        }

        $webconfig = $this->webconfig;
        $member_assem = Db::name('order_assemble')->alias('a')->field('a.pin_type,b.user_name,b.headimgurl')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pin_id',$pintuans['id'])->where('a.state',1)->where('a.tui_status',0)->order('a.addtime asc')->select();

        foreach ($member_assem as $key => $val){
            $member_assem[$key]['headimgurl'] = $webconfig['weburl'].'/'.$val['headimgurl'];
        }

        $goods_name = $goodsinfo['goods_name'].$goodsinfo['goods_attr_str'];

        $num = $pintuans['pin_num']-$pintuans['tuan_num'];
        $goodsId = $order_assembles['goods_id'];
        $pin_number = $pintuans['assem_number'];
        $weburl = $webconfig['weburl'];

        $value = array('data'=>array('member_assem'=>$member_assem,'num'=>$num,'goods_name'=>$goods_name,'goods_id'=>$goodsId,'pin_number'=>$pin_number,'weburl'=>$weburl));
        datamsg(200,'获取拼团邀请信息成功',$value);
    }
}