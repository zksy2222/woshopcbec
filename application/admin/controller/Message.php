<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use app\admin\controller\UniPush;

use think\Db;



class Message extends Common{
    //消息列表
    public function lst(){
        $list = Db::name('notification')->alias('a')->field('a.*,b.user_name')->join('member b','a.user_id=b.id','LEFT')->order('edit_time desc')->paginate(25);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign(array(
            'pnum'=>$pnum,
            'list'=>$list,
            'page'=>$page
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch();
        }
    }

    //推送消息
    public function send(){

        if(request()->isAjax()){
            $data = input('post.');
            $admin_id = session('admin_id');
            $result = $this->validate($data,'Message');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{

                $data['cover'] = $data['pic_id'];
                unset($data['pic_id']);

                if(!empty($data['create_time'])){
                    $data['create_time'] = strtotime($data['addtime']);
                }else{
                    $data['create_time'] = time();
                }
                $data['introduce'] = $data['introduce'] ? $data['introduce'] : "您有一条新的消息，请注意查收！";
                $data['edit_time'] = time();
                if($data['type'] != 0){
                    if(!empty($data['user_id'])){
                        $userId=Db::name('member')->find($data['user_id']);
                        if($userId){
                            $lastId = Db::name('notification')->insertGetId($data);
                        }else{
                            $value = array('status'=>0,'mess'=>'该会员不存在');
                            return json($value);
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'会员ID不能为空');
                        return json($value);
                    }

                }else{
                    $lastId = Db::name('notification')->insertGetId($data);
                }
                if($lastId){
                    //推送所有人
                    if($data['type'] != 1){

                        $data1 = array('title'=>$data['title'],'content'=>$data['introduce']);
                        $this->push($data1);
                        $value = array('status'=>1,'mess'=>'发送成功');
                    }else {
                        $data1 = array('cid'=>$data['user_id'],'title'=>$data['title'],'content'=>$data['introduce']);
                        $this->push($data1);
                        $value = array('status'=>1,'mess'=>'发送成功');
                    }

                }else{
                    $value = array('status'=>0,'mess'=>'发送失败');
                }
            }
            return json($value);
        }else{
            return $this->fetch();
        }
    }

    public function edit(){
        if(request()->isPost()){
            if(input('post.id')){
                $data = input('post.');
                $admin_id = session('admin_id');
                $result = $this->validate($data,'Message');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $msginfos = Db::name('notification')->where('id',$data['id'])->find();
                    if($msginfos){
                        $data['edit_time'] = time();

                        if(!empty($data['pic_id'])){
                            $data['cover'] = $data['pic_id'];
                        }else{
                            $data['cover'] = $msginfos['cover'];
                        }
                        unset($data['pic_id']);
                        //print_r($data);die();
                        //$count = $msg->allowField(true)->save($data,array('id'=>$data['id']));
                        $count = db('notification')->where(['id'=>$data['id']])->update($data);

                        if($count !== false){
                            ys_admin_logs('消息编辑成功','message',$data['id']);
                            $value = array('status'=>1,'mess'=>'编辑成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'编辑失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关信息，编辑失败');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{

            $admin_id = session('admin_id');

            if(input('id')){
                $id = input('id');
                $info = Db::name('notification')->find($id);
//                 dump($info);die();
                if($info){
                    $this->assign('ars', $info);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }

        return json($value);
    }

    public function delete(){
        if(input('post.id')){
            $id= array_filter(explode(',', input('post.id')));
        }else{
            $id = input('id');
        }
        if(!empty($id)){
            $count = db('notification')->delete($id);
            if($count > 0){
                if(is_array($id)){
                    foreach ($id as $v2){
                        ys_admin_logs('删除消息','message',$v2);
                    }
                }else{
                    ys_admin_logs('删除消息','message',$id);
                }
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'编辑失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return $value;
    }

    /***
     * 直接进行推送任务
     */
    private function push($data){
        if(empty($data['cid'])){
            $data['payload'] = '{"title":"'.$data['title'].'","content":"'.$data['content'].'","sound":"default","payload":"test"}';
            $model = new UniPush();
            $model->pushAll($data);
        }else {
            $data['payload'] ='{"cid":"'.$data['cid'].'" "title":"'.$data['title'].'","content":"'.$data['content'].'","sound":"default","payload":"test"}';
            $model = new UniPush();
            $model->pushOne($data);
        }
    }

}