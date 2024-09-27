<?php

namespace app\admin\controller;

use app\admin\controller\Common;
use think\Db;
use app\admin\model\Ad as AdModel;

class SignRecords extends Common
{
    public function lst(){

        $list = Db::name('sign_records a')
            ->join('member b','a.user_id = b.id')
            ->field('a.*,b.user_name')
            ->order('a.time desc')
            ->paginate(25);
        $page = $list->render();

        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }

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
            $orders = Db::name('order_records')->where('id',$id)->where('state',0)->field('id')->find();
            if($orders){
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('order_records')->where('id',$id)->delete();
                    // 提交事务
                    Db::commit();
                    ys_admin_logs('删除总订单','order_records',$id);
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
            cookie('user_name',input('post.keyword'),7200);
        }else{
            cookie('user_name',null);
        }
        

        if(input('post.starttime') != ''){
            $recordsstarttime = strtotime(input('post.starttime'));
            cookie('recordsstarttime',$recordsstarttime,7200);
        }

        if(input('post.endtime') != ''){
            $recordsendtime = strtotime(input('post.endtime'));
            cookie('recordsendtime',$recordsendtime,7200);
        }

        $where = array();

        if(cookie('user_name')){
            $userName = cookie('user_name');
            $where['b.user_name'] = ['like', "%{$userName}%"]; ;
        }

        if(cookie('recordsendtime') && cookie('recordsstarttime')){
            $where['time'] = array(array('egt',cookie('recordsstarttime')), array('lt',cookie('recordsendtime')));
        }

        if(cookie('recordsstarttime') && !cookie('recordsendtime')){
            $where['time'] = array('egt',cookie('recordsstarttime'));
        }

        if(cookie('recordsendtime') && !cookie('recordsstarttime')){
            $where['time'] = array('lt',cookie('recordsendtime'));
        }

        $list = Db::name('sign_records a')
            ->join('member b','a.user_id = b.id')
            ->where($where)
            ->field('a.*,b.user_name')
            ->order('a.time desc')
            ->paginate(25);
        $page = $list->render();


        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }

        $search = 1;

        if(cookie('recordsstarttime')){
            $this->assign('starttime',cookie('recordsstarttime'));
        }

        if(cookie('recordsendtime')){
            $this->assign('endtime',cookie('recordsendtime'));
        }

        if(cookie('user_name')){
            $this->assign('keyword',cookie('user_name'));
        }


        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }

}