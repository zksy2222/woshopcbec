<?php

namespace app\admin\controller;

use app\admin\controller\Common;
use app\admin\model\Member as MemberModel;
use think\Db;
use think\File;

class Im extends Common
{

    //上传证书
    public function setSocket()
    {
        if (request()->isAjax()) {

            $file = request()->file('file');

            if($file){
                $info = $file->move(ROOT_PATH . 'im/ssl/','');
                if($info){
                    if($info->getExtension() == 'key'){
                        // 成功上传后 获取上传信息
                        $data=[];
                        $data['file_name'] = $info->getFilename();
                        $data['path'] = ROOT_PATH . 'im/ssl/'.$info->getFilename();

                        $content = file_get_contents(ROOT_PATH .'im/Applications/Front/start_gateway.php');
                        $start ='local_pk'."'".'    => ';
                        $end = ',';
                        $local_pk = cut($start,$end,$content);
                        $content = str_replace($local_pk,"'".$data['path']."'",$content);
                        file_put_contents(ROOT_PATH .'im/Applications/Front/start_gateway.php',$content);


                        datamsg(200, '密钥上传成功', $data);
                    }elseif ($info->getExtension() == 'pem'){
                        $data=[];
                        $data['file_name'] = $info->getFilename();
                        $data['path'] = ROOT_PATH . 'im/ssl/'.$info->getFilename();

                        $content = file_get_contents(ROOT_PATH .'im/Applications/Front/start_gateway.php');
                        $start ='local_cert'."'".'  => ';
                        $end = ',';
                        $local_cert = cut($start,$end,$content);
                        $content = str_replace($local_cert,"'".$data['path']."'",$content);
                        file_put_contents(ROOT_PATH .'im/Applications/Front/start_gateway.php',$content);

                        datamsg(200, '证书上传成功', $data);
                    }
                }else{
                    // 上传失败获取错误信息
                    datamsg(400, '上传失败');
                }
            }
        } else {
            $fileKey = file_exists(ROOT_PATH . 'im/ssl/private.key');
            if($fileKey){
                $this->assign('file_key','private.key');
            }
            $filePem = file_exists(ROOT_PATH . 'im/ssl/full_chain.pem');
            if($filePem){
                $this->assign('file_pem','full_chain.pem');
            }
            return $this->fetch();
        }
    }

    //运行配置
    public function runSetup(){
        if (request()->isAjax()) {
            $data = input('post.');
            $start_gateway = file_get_contents(ROOT_PATH .'im/Applications/Front/start_gateway.php');
            $start_businessworker = file_get_contents(ROOT_PATH .'im/Applications/Front/start_businessworker.php');
            $shopIndex = file_get_contents(ROOT_PATH .'application/shop/view/index/index.html');
            $adminIndex = file_get_contents(ROOT_PATH .'application/admin/view/index/index.html');

            $start ='['."'".'port'."'".'] = ';
            $start1 ='$gateway->count = ';
            $start2 ='$worker->count = ';
            $start3 ='//$gateway->transport';
            $start4 ='WebSocket';


            $s = mb_strpos($start_gateway,$start3);
            if($s == false){
                $ssl = 1;
            }else{
                $ssl = 0;
            }
            $end = ';';
            $port = cut($start,$end,$start_gateway);
            $gatewayCount = cut($start1,$end,$start_gateway);
            $bussinessWorkerCount = cut($start2,$end,$start_businessworker);
            $shopWebSocket = cut($start4,$end,$shopIndex);
            $shopWebSocket = cut("'","'",$shopWebSocket);
            $adminWebSocket = cut($start4,$end,$adminIndex);
            $adminWebSocket = cut("'","'",$adminWebSocket);

            //修改文件
            if($data['ssl'] == 1 && $ssl == 0){
                $start_gateway = str_replace($start3,'$gateway->transport',$start_gateway);
            }

            if($data['ssl'] == 0 && $ssl ==1){
                $start_gateway = str_replace('$gateway->transport','//$gateway->transport',$start_gateway);
            }

            $start_gateway = str_replace($start.$port,$start.$data['port'],$start_gateway);
            $start_gateway = str_replace($start1.$gatewayCount,$start1.$data['gateway_count'],$start_gateway);
            file_put_contents(ROOT_PATH .'im/Applications/Front/start_gateway.php',$start_gateway);
            $start_businessworker = str_replace($start2.$bussinessWorkerCount,$start2.$data['bussiness_worker_count'],$start_businessworker);
            file_put_contents(ROOT_PATH .'im/Applications/Front/start_businessworker.php',$start_businessworker);
            $shop_index = str_replace($shopWebSocket,$data['web_socket'],$shopIndex);
            file_put_contents(ROOT_PATH .'application/shop/view/index/index.html',$shop_index);
            $admin_index = str_replace($adminWebSocket,$data['web_socket'],$adminIndex);
            file_put_contents(ROOT_PATH .'application/admin/view/index/index.html',$admin_index);

            //数据库配置写入im数据

            $eventsDefFile = ROOT_PATH . 'im/Applications/Front/Events.php';

            $content = file_get_contents($eventsDefFile);

            $start1 ='$database = ';
            $start2 ='$username = ';
            $start3 ='$password = ';
            $end = ';';
            $database = cut($start1,$end,$content);
            $username = cut($start2,$end,$content);
            $password = cut($start3,$end,$content);

            $content = str_replace($database,"'".config('database.database')."'",$content);
            $content = str_replace($username,"'".config('database.username')."'",$content);
            $content = str_replace($password,"'".config('database.password')."'",$content);

            file_put_contents($eventsDefFile,$content);
            datamsg(1, '修改配置成功！');
        } else {
            $start_gateway = file_get_contents(ROOT_PATH .'im/Applications/Front/start_gateway.php');
            $start_businessworker = file_get_contents(ROOT_PATH .'im/Applications/Front/start_businessworker.php');
            $index = file_get_contents(ROOT_PATH .'application/shop/view/index/index.html');

            $start ='['."'".'port'."'".'] = ';
            $start1 ='$gateway->count = ';
            $start2 ='$worker->count = ';
            $start3 ='//$gateway->transport';
            $start4 ='WebSocket';

            $s = mb_strpos($start_gateway,$start3);
            if($s == false){
                $ssl = 1;
            }else{
                $ssl = 0;
            }
            $end = ';';
            $port = cut($start,$end,$start_gateway);
            $gatewayCount = cut($start1,$end,$start_gateway);
            $bussinessWorkerCount = cut($start2,$end,$start_businessworker);
            $webSocket = cut($start4,$end,$index);
            $webSocket = cut("'","'",$webSocket);



            $this->assign([
                'port'  =>  $port,
                'gateway_count'  =>  $gatewayCount,
                'bussiness_worker_count'  =>  $bussinessWorkerCount,
                'ssl'  =>  $ssl,
                'web_socket'  =>  $webSocket,

            ]);

            return $this->fetch();
        }
    }

    //设置平台客服账号
    public function serviceMember(){
        $memberModel = new MemberModel();
        if (request()->isAjax()) {
            $userId= input('post.user_id');
            Db::startTrans();
            try{
                $memberModel->where('shop_id',1)->update(['shop_id'=>0]);
                $member = $memberModel->where('shop_id',1)->find();
                if(!$member){
                    $memberModel->where('id',$userId)->update(['shop_id'=>1]);
                }
                Db::commit();
                return json(array('status' => 1, 'mess' => '设置成功'));
            } catch (\Exception $e) {
                return json(array('status' => 0, 'mess' => '设置失败'));
            }
        }else{
            $member = $memberModel->where('shop_id',1)->find();
            $this->assign('member', $member);
            return $this->fetch();
        }
    }
    //聊天列表
    public function lst(){
        $list = Db::name('chat_message')->order('id asc')->select();
        foreach($list as $key=>&$value){
            $value['headimgurl'] = $this->webconfig['weburl'].'/'.$value['headimgurl'];
        }
        $this->assign('list', $list);
        return $this->fetch();
    }


    /**
     * 聊天记录
     */
    public function chatlist(){
        $id = input('param.id');
        $mess_token = db('chat_listmessage')->where(['cid'=>$id])->group('token')->column('token');
        if(empty($mess_token)){
            $mess_token = [-1];
        }
        $ids = db('member_token')->where(['token'=>['in',$mess_token]])->column('user_id');
        if(empty($ids)){
            $ids = [-1];
        }
        $member = db('member')->where(['id'=>['in',$ids]])->field('id,user_name,headimgurl,summary')->select();
        foreach($member as $key=>&$value){
            $value['headimgurl'] ? $value['headimgurl'] = $this->webconfig['weburl'].'/'.$value['headimgurl'] : $value['headimgurl'] = $this->webconfig['weburl'].'/uploads/default';
            $member[$key]['token'] = db('member_token')->where(['user_id'=>$value['id']])->value('token');
            $value['summary'] ? $value['summary'] : $value['summary']='这个人很懒，什么都没有写';
        }

        $this->assign([
            'cid'=>$id,
            'member'=>$member,
        ]);
        return $this->fetch();
    }


    /**
     * 获取个人的聊天记录
     */
    public function userChatLog(){
        if(request()->isAjax()){
            $chatToken = input('id');
            $shopToken = session('shopadmin_token');

            $chatLog = Db::name('chat_message')->where("((fromid='{$shopToken}' and toid='{$chatToken}') or (fromid='{$chatToken}' and toid='{$shopToken}'))")
                         ->order('createtime desc')
                         ->select();

            foreach ($chatLog as $k => $v){
                $memberId = Db::name('member_token')->where('token',$v['fromid'])->value('user_id');
                $chatLog[$k]['from_user'] = Db::name('member')->where('id',$memberId)->find();
                $chatLog[$k]['from_user']['avatar'] = url_format($chatLog[$k]['from_user']['headimgurl'],$this->webconfig['weburl']);
            }

            if( empty($chatLog) ){
                return json( ['code' => 0, 'data' => '', 'msg' => '没有记录'] );
            }

            return json( ['code' => 1, 'data' => $chatLog, 'msg' => 'success'] );

        }else{
            $chatToken = input('id');
            $this->assign('chatToken',$chatToken);
            return $this->fetch();
        }

    }

    //获取列表
    public function getList()
    {
        $shopMember = Db::name('member')->where('shop_id',session('shop_id'))->field('id,user_name,headimgurl')->find();

        $return = [
            'code' => 0,
            'msg'=> '',
            'data' => [
                'mine' => [
                    'username' => $shopMember['user_name'],
                    'id' => session('shopadmin_token'),
                    'status' => 'online',
                    'sign' => '平台客服',
                    'avatar' => url_format($shopMember['headimgurl'],$this->webconfig['weburl'])
                ],
                'friend' => [
                    [
                        'groupname' => '顾客',
                        'id' => 1,
                        'online' => 0,
                        'list' => []
                    ]
                ],
                'group' => 2
            ],
        ];



        $shopToken = session('shopadmin_token');
        $sql = "SELECT * FROM(
                                        SELECT id,message,is_read,usertype,messagetype,createtime,fromid AS F,toid AS T FROM sp_chat_message WHERE fromid='".$shopToken."'
                                        UNION
                                        SELECT id,message,is_read,usertype,messagetype,createtime,toid AS F,fromid AS T FROM sp_chat_message WHERE toid='" .$shopToken."' ORDER BY createtime DESC
                                    ) sp GROUP BY T ORDER BY createtime DESC";
        $chatData = Db::query($sql);
        if($chatData) {

            foreach ($chatData as $key => &$value) {

                $value['fromid'] = $value['F'];
                $value['id'] = $value['toid'] = $value['T'];

                $fromUserId = Db::name('member_token')->where("token",$value['fromid'])->value('user_id');
                $fromUser = Db::name('member')->where("id",$fromUserId)->find();
                $toUserId = Db::name('member_token')->where("token",$value['toid'])->value('user_id');
                $toUser = Db::name('member')->where("id",$toUserId)->find();

                $value['from_username'] = empty($fromUser['user_name']) ? '匿名' : $fromUser['user_name'];
                $value['from_headimgurl'] = url_format($fromUser['headimgurl'],$this->webconfig['weburl']);
                $value['username'] = $value['to_username'] = empty($toUser['user_name']) ? '匿名' : $toUser['user_name'];
                $value['avatar'] = $value['to_headimgurl'] = url_format($toUser['headimgurl'],$this->webconfig['weburl']);
                $value['message_type'] = $value['messagetype'];
                $value['userType'] = $value['usertype'];
                $value['msg_time'] = $value['createtime'];
                $value['dates'] = date('Y-m-d H:i:s',$value['createtime']);
                unset($value['usertype']);

                $value['msg'] = $value['message'];
                $value['msgtype'] = $value['messagetype'];
            }

        }else{
            $chatData = [];
        }

        $return['data']['friend'][0]['list'] = $chatData;

        return json( $return );

    }

}