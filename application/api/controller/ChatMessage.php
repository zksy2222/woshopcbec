<?php
namespace app\api\controller;
use app\api\controller\Common;
use think\Db;

class ChatMessage extends Common{

    //获取聊天分页数
    public function chatlist(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }else{
            $userId = $tokenRes['user_id'];
        }

        $pages = 15;
        if(input('post.page')){
            $start_page = input('post.page') - 1;
            $start = $start_page * $pages;
        }else{
            $start_page = 1;
            $start = 0;
        }

        $data = input('post.');
        $touserdata = Db::name("member_token")->where("token = '".$data['toid']."'")->find();
        $chatList = Db::name("chat_message")
            ->where('fromid','IN',[$data['token'],$data['toid']])
            ->where('toid','IN',[$data['token'],$data['toid']])
            ->order('createtime DESC')
            ->limit($start,$pages)
            ->select();

        $weburl = Db::name('config')->where("ename = 'weburl'")->find();
        if($chatList){
            foreach ($chatList as $key => &$value) {
                $from_token = Db::name('member_token')->where("token = '".$value['fromid']."'")->find();
                $from_user = Db::name('member')->find($from_token['user_id']);
                $to_user = Db::name('member')->find($value['toid']);

                $value['from_username'] = empty($from_user['user_name']) ? lang('匿名') : $from_user['user_name'];
                $value['from_headimgurl'] = url_format($from_user['headimgurl'],$this->webconfig['weburl']);
                $value['to_username'] = empty($to_user['user_name']) ? lang('匿名') : $to_user['user_name'];
                $value['to_headimgurl'] =  url_format($to_user['headimgurl'],$this->webconfig['weburl']);
                $value['message_type'] = $value['messagetype'];
                $value['userType'] = $value['usertype'];
                $value['times'] = date('Y-m-d H:i:s',$value['createtime']);
                unset($value['messagetype']);
                unset($value['usertype']);
            }
        }
        datamsg(200,'聊天记录',$chatList);
    }
}