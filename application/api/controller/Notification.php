<?php
namespace app\api\controller;
use app\api\model\Common as CommonModel;
use think\Db;
class Notification extends Common {
  
    /**
     * @func 获取消息列表
     */
    public function notificationList(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }else{
            $userId = $tokenRes['user_id'];
        }

        $type = input('param.type');
        $page = input('param.page') ? input('param.page') : 1;
        $size = input('param.size') ? input('param.size') : 10;
        $size = 4;
        $isnewperson = input('param.isnewperson');
        $userAddTime=db('member')->where('id',$userId)->value('regtime');
        if($type == 0){
            $where= ['type'=>$type,'status'=>1,'create_time'=>['>=',$userAddTime]];
        }else{
            $where = ['type'=>$type,'status'=>1,'user_id'=>$userId];
        }

        $list = db('notification')->where($where)->field('id,title,introduce,cover,create_time,type, user_id as uid')->order('id DESC')
            ->paginate($size)
            ->each(function ($item, $key) use($userId) {
                $item['create_time'] = time_ago(date('Y-m-d H:i:s', $item['create_time']));
                $item['cover'] = url_format($item['cover'],$this->webconfig['weburl']);
                //是否已读
                $reads = Db::name('notification_read')->field('id')->where('user_id',$userId)->where('notification_id',$item['id'])->find();
                if($reads){
                    $item['is_read'] = 1;
                }else{
                    $item['is_read'] = 0;
                }
                return $item;
            });
        datamsg(200, '获取成功', $list);
    }
	
	//消息详情
	public function notificationInfo(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess']);
        }else{
            $userId = $tokenRes['user_id'];
        }

        if(!input('post.id')){
            datamsg(400,'缺少ID');
        }
        $id = input('post.id');
        $info = Db::name('notification')->field('id,title,cover,introduce,content,type,status,edit_time,create_time')->where('id',$id)->find();
        if(!$info){
            datamsg(400,'获取消息详情失败');
        }
        //消息设为已读
        $isread = Db::name('notification_read')->field('id')->where('notification_id',$info['id'])->where('user_id',$userId)->find();
        if(empty($isread)){
            $data['user_id'] = $userId;
            $data['notification_id'] = $info['id'];
            $data['create_time'] = time();
            Db::name('notification_read')->insert($data);
        }

        $info['cover'] = $this->webconfig['weburl']."/".$info['cover'];
        $info['edit_time'] = time_ago(date('Y-m-d H:i:s', $info['edit_time']));
        $info['create_time'] = time_ago(date('Y-m-d H:i:s', $info['create_time']));
        $info['content'] = str_replace("/public/",$this->webconfig['weburl']."/public/",$info['content']);

        datamsg(200,'获取消息详情成功',$info);
	}

   
}