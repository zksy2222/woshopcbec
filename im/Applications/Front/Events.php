<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;
use Workerman\Redis\Client;
require_once dirname(__FILE__).'/../../vendor/workerman/mysql/src/Connection.php';
/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    /**
     * 新建一个类的静态成员，用来保存数据库实例
     */
    public static $db = null;
    public static $redis = null;
    public static $redis2 = null;


    /**
     * 当进程启动时一些初始化工作
     *
     * @return void
     */
    public static function onWorkerStart($worker)
    {
        // 初始化redis连接
        self::$redis = new Client('redis://127.0.0.1:6379');
        self::$redis2 = new Client('redis://127.0.0.1:6379');
        // 初始化数据库连接
        $host = '127.0.0.1';
        $database = 'cbec_woshop_221224';
        $username = 'root';
        $password = '123456';
        self::$db = new \Workerman\MySQL\Connection($host, '3306', $username, $password, $database);
    }

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     *
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        //向当前client_id发送数据
        $data['type'] = 'init';
        $data['data'] = ['client_id' => $client_id, 'msg' => 'onConnect'];
        Gateway::sendToClient($client_id, json_encode($data));
    }

    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message)
    {

        $data = json_decode($message, true);
        switch ($data['type']){
            case 'init':
                $data['data']=['client_id'=>$client_id,'msg'=>'initmsg'];
                Gateway::sendToClient($client_id, json_encode($data));
                break;
            case 'ping':
                $mess['type']='pong';
                if(empty($data['id'])){
                    $data['id']=-1;
                }
                Gateway::sendToUid($data['id'],json_encode($mess));
                break;
            case "bindlive_id":
                $getAllClientIds = Gateway::getClientIdListByGroup($data['room']);
                $getAllClientIdsKey = array_keys($getAllClientIds);
                if(!in_array($data['client_id'],$getAllClientIdsKey)){
                    Gateway::joinGroup($data['client_id'],$data['room']);
                    $key = 'msg';//Channel 订阅这频道的订阅者，都能收收到消息
                    self::$redis->publish($key,$message); // 将信息发送到频道$key
                }
                break;
            case "livesay":
                $key = 'msg';//Channel 订阅这频道的订阅者，都能收收到消息
                self::$redis->publish($key,$message); // 将信息发送到频道$key
                break;
            case "blockUser":
                $key = 'msg';//Channel 订阅这频道的订阅者，都能收收到消息
                self::$redis->publish($key,$message); // 将信息发送到频道$key
                break;
            case "givegift":
                $key = 'msg';//Channel 订阅这频道的订阅者，都能收收到消息
                self::$redis->publish($key,$message); // 将信息发送到频道$key
                break;
            case 'getCount':
                $clients_list = Gateway::getClientSessionsByGroup($data['room']);
                $result = ['num'=>count($clients_list), 'time'=>date('Y-m-d H:i:s'), 'type'=>'count'];
                Gateway::sendToClient($client_id, json_encode($result));
                break;
            case 'dianzan':
                $clients_list = Gateway::getClientSessionsByGroup($data['room']);
                $result = ['count'=>1, 'time'=>date('Y-m-d H:i:s'), 'type'=>'dianzan'];
                Gateway::sendToGroup($data['room'], json_encode($result));
                break;
            case "addCart":
                $key = 'msg';//Channel 订阅这频道的订阅者，都能收收到消息
                self::$redis->publish($key,$message); // 将信息发送到频道$key
                break;
            // 以下是2021年01月08日合并客服Events.php文件
            case "bind_id":
                Gateway::bindUid($client_id,$data['id']);
                $mess=['type'=>'bind_id','data'=>['chat'=>'绑定成功']];
                Gateway::sendToUid($data['id'],json_encode($mess));
                break;
            case "say":
                $key = 'chat';//Channel 订阅这频道的订阅者，都能收收到消息
                self::$redis->publish($key,$message); // 将信息发送到频道$key
                break;
            case "history":
                $key = 'chat';//Channel 订阅这频道的订阅者，都能收收到消息
                self::$redis->publish($key,$message); // 将信息发送到频道$key
                break;
            case "chatlist":         //和谁聊过天的列表
                $key = 'chat';//Channel 订阅这频道的订阅者，都能收收到消息
                self::$redis->publish($key,$message); // 将信息发送到频道$key
                break;
            case "readmsg":
                $key = 'chat';//Channel 订阅这频道的订阅者，都能收收到消息
                self::$redis->publish($key,$message); // 将信息发送到频道$key
                break;
            case "livebind_id":
                Gateway::joinGroup($client_id, $data['room']);
                $key = 'chat';//Channel 订阅这频道的订阅者，都能收收到消息
                self::$redis->publish($key,$message); // 将信息发送到频道$key
                break;
            default:
                $key = 'chat';//Channel 订阅这频道的订阅者，都能收收到消息
                break;
        }

        // 订阅给定的多个频道['chat','msg']的信息
        self::$redis2->subscribe(['chat','msg'], function ($channel, $msg) {
            $data=json_decode($msg,true);
            switch ($data['type']){
                case 'say':
                    print_r('ssssssssssay');
                    $sayData['fromid']=$data['data']['fromid'];
                    $sayData['toid']=$data['data']['toid'];
                    $sayData['usertype']=$data['data']['userType'];
                    $sayData['message']=$data['data']['message'];
                    $sayData['messagetype']=$data['data']['message_type'];
                    if($sayData['messagetype'] == 'img'){
                        $sayData['width']=$data['data']['width'];
                        $sayData['height']=$data['data']['height'];
                    }
                    $sayData['createtime']=time();
                    $insertSayData = self::$db->insert('sp_chat_message')->cols($sayData)->query();

                    $fromUserId = self::$db->select('user_id')->from('sp_member_token')->where("token= '".$data['data']['fromid']."' ")->single();
                    $fromUser = self::$db->select('*')->from('sp_member')->where("id= ".$fromUserId)->row();

                    $toUserId = self::$db->select('user_id')->from('sp_member_token')->where("token= '".$data['data']['toid']."' ")->single();
                    $toUser = self::$db->select('*')->from('sp_member')->where("id= ".$toUserId)->row();

                    $weburl = self::$db->select('*')->from('sp_config')->where("ename= 'weburl' ")->row();
                    $data['data']['from_username'] = empty($fromUser['user_name']) ? '匿名' : $fromUser['user_name'];
                    $data['data']['from_headimgurl'] = self::url_format($fromUser['headimgurl'],$weburl['value']);
                    $data['data']['to_username'] = empty($toUser['user_name']) ? '匿名' : $toUser['user_name'];
                    $data['data']['to_headimgurl'] = self::url_format($toUser['headimgurl'],$weburl['value']);
                    Gateway::sendToUid($data['data']['toid'],json_encode($data));
                    break;
                case 'history':
                    print_r('history');
                    $where['fromid']=$data['data']['fromid'];
                    $where['toid']=$data['data']['toid'];
                    $sql="SELECT * FROM `sp_chat_message` WHERE fromid IN('".$where['fromid']."','".$where['toid']."') AND toid IN('".$where['toid']."','".$where['fromid']."') ORDER BY createtime DESC limit 15";
                    $result = self::$db->query($sql);

                    if(!empty($result)) {
                        $weburl = self::$db->select('*')->from('sp_config')->where("ename= 'weburl' ")->row();
                        foreach ($result as $key => &$value) {
                            $fromUserId = self::$db->select('user_id')->from('sp_member_token')->where("token= '".$value['fromid']."' ")->single();
                            $fromUser = self::$db->select('*')->from('sp_member')->where("id= ".$fromUserId)->row();
                            $toUserId = self::$db->select('user_id')->from('sp_member_token')->where("token= '".$value['toid']."' ")->single();
                            $toUser = self::$db->select('*')->from('sp_member')->where("id= ".$toUserId)->row();


                            $value['from_username'] = empty($fromUser['user_name']) ? '匿名' : $fromUser['user_name'];
                            $value['from_headimgurl'] = self::url_format($fromUser['headimgurl'],$weburl['value']);
                            $value['to_username'] = empty($toUser['user_name']) ? '匿名' : $toUser['user_name'];
                            $value['to_headimgurl'] = self::url_format($toUser['headimgurl'],$weburl['value']);
                            $value['message_type'] = $value['messagetype'];
                            $value['userType'] = $value['usertype'];
                            unset($value['messagetype']);
                            unset($value['usertype']);
                        }
                    }else{
                        $result = [];
                    }
                    $historyData['type']='history';
                    $historyData['data']=$result;
                    Gateway::sendToUid($data['data']['fromid'],json_encode($historyData));
                    break;
                case 'chatlist':
                    $where['fromid']=$data['data']['fromid'];
                    $sql = "SELECT * FROM(
                                        SELECT id,message,is_read,usertype,messagetype,createtime,fromid AS F,toid AS T FROM sp_chat_message WHERE fromid='".$where['fromid']."'
                                        UNION
                                        SELECT id,message,is_read,usertype,messagetype,createtime,toid AS F,fromid AS T FROM sp_chat_message WHERE toid='" .$where['fromid']."' ORDER BY createtime DESC
                                    ) sp GROUP BY T ORDER BY createtime DESC";
                    $result = self::$db->query($sql);

                    if(!empty($result)) {
                        $weburl = self::$db->select('*')->from('sp_config')->where("ename= 'weburl' ")->row();
                        foreach ($result as $key => &$value) {

                            $value['fromid'] = $value['F'];
                            $value['toid'] = $value['T'];

                            $fromUserId = self::$db->select('user_id')->from('sp_member_token')->where("token= '".$value['fromid']."' ")->single();
                            $fromUser = self::$db->select('*')->from('sp_member')->where("id= ".$fromUserId)->row();
                            $toUserId = self::$db->select('user_id')->from('sp_member_token')->where("token= '".$value['toid']."' ")->single();
                            $toUser = self::$db->select('*')->from('sp_member')->where("id= ".$toUserId)->row();

                            $value['from_username'] = empty($fromUser['user_name']) ? '匿名' : $fromUser['user_name'];
                            $value['from_headimgurl'] = self::url_format($fromUser['headimgurl'],$weburl['value']);
                            $value['to_username'] = empty($toUser['user_name']) ? '匿名' : $toUser['user_name'];
                            $value['to_headimgurl'] = self::url_format($toUser['headimgurl'],$weburl['value']);
                            $value['message_type'] = $value['messagetype'];
                            $value['userType'] = $value['usertype'];
                            $value['msg_time'] = self::time_ago($value['createtime']);
                            $value['dates'] = date('Y-m-d H:i:s',$value['createtime']);
                            unset($value['usertype']);

                            $value['msg'] = $value['message'];
                            $value['msgtype'] = $value['messagetype'];


                            $countsql = "SELECT COUNT(*) as msgcount FROM `sp_chat_message` WHERE fromid = '".$value['toid']."' and toid = '".$value['fromid']."' AND is_read=0";
                            $msgcount = self::$db->query($countsql);
                            $value['msgcount'] = (int)$msgcount[0]['msgcount'];
                        }

                    }else{
                        $result = [];
                    }
                    $chatListData['type']='chatlist';
                    $chatListData['data']=$result;

                    Gateway::sendToUid($data['data']['fromid'],json_encode($chatListData));
                    break;
                case 'readmsg':
                    $r = self::$db->update('sp_chat_message')->cols(array('is_read'=>1))->where("fromid='".$data['data']['toid']."' AND toid='".$data['data']['fromid']."'")->query();
                    $chatListData['type']='readmsg';
                    $chatListData['q']= $r;
                    $chatListData['data']= $data;
                    Gateway::sendToUid($data['data']['fromid'],json_encode($chatListData));
                    break;
                case 'bindlive_id':
                    $comein = self::$db->select('*')->from('sp_live_comein')->where("room='".$data['room']."' AND token='".$data['id']."'")->row();

                    if(empty($comein)){
                        $comeinData['room']=$data['room'];
                        $comeinData['token']=$data['id'];
                        $comeinData['createtime']=time();
                        self::$db->insert('sp_live_comein')->cols($comeinData)->query();
                    }

                    $fromUserId = self::$db->select('user_id')->from('sp_member_token')->where("token= '".$data['id']."' ")->single();

                    self::addIntegral(5,$fromUserId);

                    $fromUser = self::$db->select('*')->from('sp_member')->where("id= ".$fromUserId)->row();

                    //给此用户增加client_id绑定，每次绑定client_id 的时候记录 最新client_id 的值
                    $userClient = self::$db->select('*')->from('sp_member_clientid')->where("user_id= ".$fromUserId)->row();
                    if(empty($userClient)){  //创建记录
                        $cidarr = [
                            'user_id' => $fromUserId, 'client_id' => $data['client_id'] , 'created' => date('Y-m-d H:i:s',time())
                        ];
                        self::$db->insert('sp_member_clientid')->cols($cidarr)->query();
                    }else{

                        if($userClient['client_id'] != $data['client_id']){
                            //更改此用户的新连接client_id
                            self::$db->update('sp_member_clientid')->cols(array('client_id'=>$data['client_id'],'created'=>date('Y-m-d H:i:s',time())))->where('id='.$userClient['id'])->query();
                        }
                    }

                    if($fromUser['shop_id'] >0 ){
                        $result['role']='shop';
                    }
                    if($fromUser['pid'] >0 ){
                        $shop =  self::$db->select('*')->from('sp_member')->where("id= ".$fromUser['pid'])->row();
                        $result['role']='service';
                        $result['serviceShopId'] = $shop['shop_id'];
                    }
                    if($fromUser['shop_id'] ==0 && $fromUser['pid'] ==0 ){
                        $result['role']='user';
                    }
                    $weburl = self::$db->select('*')->from('sp_config')->where("ename= 'weburl' ")->row();

                    $map['username'] = empty($fromUser['user_name']) ? '匿名' : $fromUser['user_name'];
                    $map['headimgurl'] = self::url_format($fromUser['headimgurl'],$weburl['value']);
                    $result['type']='notice';
                    $result['data']=$map;
                    $result['msg']= $map['username'].'加入了直播间';
                    $jsonmsg = json_encode($result);
                    Gateway::sendToGroup($data['room'],$jsonmsg);
                    break;
                case 'livesay':
                    print_r('livesay Start');
                    $fromUserId = self::$db->select('user_id')->from('sp_member_token')->where("token= '".$data['id']."' ")->single();

                    self::addIntegral(6,$fromUserId);

                    $fromUser = self::$db->select('*')->from('sp_member')->where("id= ".$fromUserId)->row();
                    $weburl = self::$db->select('*')->from('sp_config')->where("ename= 'weburl' ")->row();

                    $liveFansData = self::$db->select('*')->from('sp_live_fans')->where("user_id= ".$fromUserId)->row();

                    $integral = isset($liveFansData['integral'])?(int)$liveFansData['integral']:0;
                    $mintegral = (int) $fromUser['integral'];

                    $rank = self::$db->select('*')->from('sp_fans_level')->where("points_min <='".$integral."' AND points_max >='".$integral."'")->row();
                    //查出会员的等级信息
                    $mrank = self::$db->select('*')->from('sp_member_level')->where("points_min <='".$mintegral."' AND points_max >='".$mintegral."'")->row();

                    //获取发言配置条数
                    $configData = self::$db->select('*')->from('sp_live_config')->where("id= 1 ")->row();
                    //统计今日已发言条数
                    $todayTime = strtotime(date('Y-m-d 00:00:00',time()));
                    $todayCountSql = "select count(*) as allcount from sp_live_chat_message where fromid = '".$data['id']."' and chat_room_id = ".$data['room'].' and createtime > '.$todayTime;
                    $todayTalkCount = self::$db->query($todayCountSql);
                    if($todayTalkCount[0]['allcount'] <= $configData['fans_msg_10min_max']*10){   //如果发言总条数小于配置中可参与积分增加的条数  则处理   否则不处理
                        if($todayTalkCount[0]['allcount'] % 10 == 0){   //如果总条数满足10的倍数  则加配置中配置的积分
                            $addPoints = $configData['fans_msg_10min'];
                            //会员发言增加积分
                            $idataarr = [
                                'user_id' => $fromUserId , 'room' => $data['room'] , 'integral' => $addPoints , 'type'=> 4, 'addtime'=> time()
                            ];
                            self::$db->insert('sp_fans_integral')->cols($idataarr)->query();
                        }
                    }

                    $map['username'] = empty($fromUser['user_name']) ? '匿名' : $fromUser['user_name'];
                    $map['headimgurl'] = self::url_format($fromUser['headimgurl'],$weburl['value']);
                    $map['user_id'] = $fromUser['id'];
                    $map['msg'] = $data['msg'];
                    $map['rank'] = $rank['level_name'];
                    $map['member_rank'] = $mrank['level_name'];
                    $map['integral'] = $integral;
                    $result['type']='livesay';
                    $map['block_type']= 0;
                    $result['msg']= $map['rank'].$map['username'].'：'.$data['msg'];
                    if($fromUser['shop_id'] >0 ){
                        $result['role']='shop';
                        $map['role']='shop';
                    }
                    if($fromUser['pid'] >0 ){
                        $shop = self::$db->select('*')->from('sp_member')->where("id= ".$fromUser['pid'])->row();
                        $roomShop = self::$db->select('shop_id')->from('sp_live')->where("room= '".$data['room']."' ")->single();
                        if($shop['shop_id'] == $roomShop){
                            $map['role']='service';
                            $map['serviceShopId'] = $shop['shop_id'];
                            $result['role']='service';
                            $result['serviceShopId'] = $shop['shop_id'];
                        }else{
                            $map['role']='user';
                            $result['role']='user';
                        }
                    }
                    if($fromUser['shop_id'] ==0 && $fromUser['pid'] ==0 ){
                        $map['role']='user';
                        $result['role']='user';
                    }
                    $result['data']=$map;

                    $chatMessageData['fromid']=$data['id'];
                    $chatMessageData['usertype']='home';
                    $chatMessageData['message']=$data['msg'];
                    $chatMessageData['messagetype']='';
                    $chatMessageData['chat_room_id']=$data['room'];
                    $chatMessageData['createtime']=time();
                    self::$db->insert('sp_live_chat_message')->cols($chatMessageData)->query();
                    // 正常聊天消息
                    $jsonmsg = json_encode($result);

                    $blockType = self::$db->select('*')->from('sp_live_room_block')->where("user_id= ".$fromUser['id']." AND room='".$data['room']."' ")->orderByASC(array('type'))->row();

                    if(empty($blockType)){
                        Gateway::sendToGroup($data['room'],$jsonmsg);
                    }elseif($blockType['type'] == 1){

                        // 拉黑消息
                        $data['msg'] = '你已被拉黑，无法发消息';
                        $map['msg'] = $data['msg'];
                        $result['msg']=$map['username'].'：'.$data['msg'];
                        $map['block_type']= 1;
                        $result['data']=$map;
                        $blockmsg = json_encode($result);

                        // Gateway::sendToUid($from_user['id'],$blockmsg);

                        Gateway::sendToClient($data['client_id'],$blockmsg);


                        // Gateway::sendToGroup($data['room'],$blockmsg);
                    }elseif($blockType['type'] == 2){
                        if(time()> ($blockType['add_time']+24*60*60))
                        {
                            self::$db->delete('sp_live_room_block')->where('id='.$blockType['id'])->query();
                            Gateway::sendToGroup($data['room'],$jsonmsg);
                        }else{
                            // 拉黑消息
                            $data['msg'] = '你已被禁言24小时，无法发消息';
                            $map['msg'] = $data['msg'];
                            $result['msg']=$map['username'].'：'.$data['msg'];
                            $map['block_type']= 2;
                            $result['data']=$map;
                            $blockmsg = json_encode($result);
                            Gateway::sendToClient($data['client_id'],$blockmsg);
                        }
                    }elseif($blockType['type'] == 3){
                        // 拉黑消息
                        $data['msg'] = '你已被永久禁言，无法发消息';
                        $map['msg'] = $data['msg'];
                        $result['msg']=$map['username'].'：'.$data['msg'];
                        $map['block_type']= 3;
                        $result['data']=$map;
                        $blockmsg = json_encode($result);
                        Gateway::sendToClient($data['client_id'],$blockmsg);
                    }
                    break;
                case 'blockUser':

                    $result['type']='blockUser';

                    $userInfo = self::$db->select('user_name')->from('sp_member')->where("id= ".$data['userid'])->row();
                    $blockData['room']=$data['room'];
                    $blockData['user_id']=$data['userid'];
                    $blockData['type']=$data['blockType'];
                    $blockData['add_time']=time();
                    $blockData['shop_id']=$data['shopid'];
                    self::$db->insert('sp_live_room_block')->cols($blockData)->query();

                    if($data['blockType'] == 1){
                        $blockType = '拉黑';
                    }
                    if($data['blockType'] == 2 ){
                        $blockType = '单场禁言';
                    }
                    if($data['blockType'] == 3){
                        $blockType = '永久禁言';
                    }
                    $result['msg'] = '用户['.$userInfo['user_name'].']违规，已被管理员'.$blockType;

                    $jsonmsg = json_encode($result);
                    Gateway::sendToGroup($data['room'],$jsonmsg);
                    break;
                case 'givegift':
                    $fromUserId = self::$db->select('user_id')->from('sp_member_token')->where("token= '".$data['id']."' ")->single();
                    $fromUser = self::$db->select('*')->from('sp_member')->where("id= ".$fromUserId)->row();
                    $weburl = self::$db->select('*')->from('sp_config')->where("ename= 'weburl' ")->row();

                    $liveFansData = self::$db->select('*')->from('sp_live_fans')->where("user_id= ".$fromUserId)->row();
                    $integral = isset($liveFansData['integral'])?(int)$liveFansData['integral']:0;
                    $mintegral = (int) $fromUser['integral'];
                    $rank = self::$db->select('*')->from('sp_fans_level')->where("points_min <='".$integral."' AND points_max >='".$integral."'")->row();
                    //查出会员的等级信息
                    $mrank = self::$db->select('*')->from('sp_member_level')->where("points_min <='".$mintegral."' AND points_max >='".$mintegral."'")->row();

                    $gifts = self::$db->select('*')->from('sp_live_gift')->where("id= ".$data['gift_id'])->row();
                    $map['username'] = empty($fromUser['user_name']) ? '匿名' : $fromUser['user_name'];
                    $map['headimgurl'] = self::url_format($fromUser['headimgurl'],$weburl['value']);
                    $map['user_id'] = $fromUser['id'];
                    $map['rank'] = $rank['level_name'];
                    $map['member_rank'] = $mrank['level_name'];
                    $map['integral'] = $integral;
                    $map['msg'] = '送给主播礼物"'.$gifts['name'].'"';

                    if($fromUser['shop_id'] >0 ){
                        $map['role']='shop';
                    }
                    if($fromUser['pid'] >0 ){
                        $shop =  self::$db->select('*')->from('sp_member')->where("id= ".$fromUser['pid'])->row();
                        $map['role']='service';
                        $map['serviceShopId'] = $shop['shop_id'];
                    }
                    if($fromUser['shop_id'] ==0 && $fromUser['pid'] ==0 ){
                        $map['role']='user';
                    }
                    //发送说话显示
                    $result['type']='livesay';
                    $result['data']=$map;
                    $result['msg']= "送给主播礼物{$gifts['name']}";

                    $giveResult = self::givegiftUserAmount($fromUser,$data,$gifts);
                    if($giveResult['status']){
                        $jsonmsg = json_encode($result);
                        Gateway::sendToGroup($data['room'],$jsonmsg);
                    }else{
                        $map['msg'] = "送礼【{$gifts['name']}】失败，{$giveResult['msg']}";
                        $result['data']=$map;
                        $result['msg']= "送礼【{$gifts['name']}】失败，{$giveResult['msg']}";
                        $jsonmsg = json_encode($result);
                        Gateway::sendToClient($data['client_id'],$jsonmsg);
                    }


                    //发送礼物显示
                    $map['pic']= self::url_format($gifts['pic'],$weburl['value']);
                    $map['picgif']= self::url_format($gifts['picgif'],$weburl['value']);
                    $map['gift_name'] = $gifts['name'];
                    $map['id']=$data['id'];
                    $giftsto['type']='givegiftlist';
                    $giftsto['data']=$map;
                    $giftsto['msg'] = '送出'.$gifts['name'];
                    if($giveResult['status']){
                        $giftstostr = json_encode($giftsto);
                        Gateway::sendToGroup($data['room'],$giftstostr);
                    }
                    break;
                case 'addCart' :
                    $fromUserId = self::$db->select('user_id')->from('sp_member_token')->where("token= '".$data['id']."' ")->single();
                    $toClientData = self::$db->select('*')->from('sp_member_clientid')->where("user_id= ".$data['userid'])->row();
                    $result = [];
                    $newresult['type']='addCart';
                    $newresult['data']=$data;
                    $newresult['msg']= "向此".$toClientData['client_id'].'用户发送了一条通知';
                    Gateway::sendToClient($toClientData['client_id'],json_encode($newresult));
                    break;

                default:
                    echo "default msg";
            }

        });
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {
        $data = array(
            'type' => 'login_out',
            'data' => array('client_id' => $client_id, 'msg' => 'socket连接断开')
        );
        Gateway::sendToClient($client_id, json_encode($data));
        //        GateWay::sendToAll(json_encode($data));
    }
    // 格式化url
    /**
     * 格式化url
     * @param string $url 需要格式化的url字符串
     * @param string $prefix url前缀
     */
    public static function url_format($url,$prefix=''){
        if(empty($url)){
            return $prefix.'/uploads/default.jpg';
        }
        if(substr($url,0,4) == 'http'){
            return $url;
        }else{
            return $prefix.$url;
        }
    }

    public static function time_ago($posttime){
        //相差时间戳
        $counttime = time() - $posttime;
        //进行时间转换
        if($counttime<=10){
            return '刚刚';
        }else if($counttime>10 && $counttime<=30){
            return '刚刚';
        }else if($counttime>30 && $counttime<=60){
            return '刚刚';
        }else if($counttime>60 && $counttime<=120){
            return '1分钟前';
        }else if($counttime>120 && $counttime<=180){
            return '2分钟前';
        }else if($counttime>180 && $counttime<3600){
            return intval(($counttime/60)).'分钟前';
        }else if($counttime>=3600 && $counttime<3600*24){
            return intval(($counttime/3600)).'小时前';
        }else if($counttime>=3600*24 && $counttime<3600*24*2){
            return '昨天';
        }else if($counttime>=3600*24*2 && $counttime<3600*24*3){
            return '前天';
        }else if($counttime>=3600*24*3 && $counttime<=3600*24*20){
            return intval(($counttime/(3600*24))).'天前';
        }else{
            return date('Y-m-d H:i',$posttime);
        }
    }

    //添加积分
    public static function addIntegral($type,$user_id){
        $fromUserIntegral = self::$db->select('*')->from('sp_integral_task')->where("id={$type}")->row();
        if($fromUserIntegral && $fromUserIntegral['integral']){
            $start = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
            $end = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
            $isExtexdIntegral = self::$db->select('integral')->from('sp_member_integral')->where("type={$type} AND user_id={$user_id} AND addtime>={$start} AND addtime<={$end}")->row();
            if(!$isExtexdIntegral){
                $data['user_id'] = $user_id;
                $data['integral'] = $fromUserIntegral['integral'];
                $data['type'] = $type;
                $data['order_id'] = 0;
                $data['addtime'] = time();
                self::$db->insert('sp_member_integral')->cols($data)->query();

                $fromUserMember = self::$db->select('*')->from('sp_member')->where("id={$user_id}")->row();
                $integral = $fromUserMember['integral'] + $fromUserIntegral['integral'];
                self::$db->update('sp_member')->where("id={$user_id}")->cols(array('integral'=>$integral))->query();
            }

        }
    }

    /**
     * 赠送礼物
     */
    public static function givegiftUserAmount($fromUser,$data,$gifts){
        //用户钱包
        $memberWallets= self::$db->select('*')->from('sp_wallet')->where("user_id={$fromUser['id']}")->row();
        //店铺钱包
        $shopWallets = self::$db->select('*')->from('sp_shop_wallet')->where("shop_id={$data['shop_id']}")->row();
        //主播用户信息
        $anchorMember = self::$db->select('*')->from('sp_member')->where("shop_id={$data['shop_id']}")->row();
        //主播钱包
        $anchorWallets = self::$db->select('*')->from('sp_wallet')->where("user_id={$anchorMember['id']}")->row();
        //分成比例
        $liveCommision = self::$db->select('*')->from('sp_live_commision_config')->where("shop_id={$data['shop_id']}")->row();
        $liveCommision['gift_commision_rate'] = isset($liveCommision['gift_commision_rate']) && !empty($liveCommision['gift_commision_rate']) ? $liveCommision['gift_commision_rate']/100 : "0.02";

        self::$db->beginTrans();
        try{
            if($memberWallets['gold_coin'] - $gifts['gift_coin'] < 0){
                throw new \Exception("用户金币不足");
            }
            $gift_commision_rate = $gifts['gift_coin'] * $liveCommision['gift_commision_rate'];
            $anchor_gold_coin = substr(sprintf("%.3f",$gift_commision_rate),0,-1);
            $shop_gold_coin= $gifts['gift_coin'] - $anchor_gold_coin;

            $live_give_gift=self::$db->insert('sp_live_gift_give')->cols([
                "uid"=>$memberWallets['user_id'],
                "shop_id"=>$shopWallets['shop_id'],
                "gid"=>$gifts['id'],
                "gift_coin"=>$gifts['gift_coin'],
                "createtime"=>time(),
                "anchor_user_id"=>$anchorWallets['user_id'],
                "anchor_gold_coin"=>$anchor_gold_coin,
                "shop_gold_coin"=>$shop_gold_coin
            ])->query();
            if(!$live_give_gift){
                throw new \Exception("赠送礼物失败");
            }

            $member_gold_coin_version = $memberWallets['gold_coin_version'];
            $member_update_gold_coin_version = $memberWallets['gold_coin_version'] + 1;
            //赠送礼物的人
            $wallet_gold_coin_id = self::$db->insert('sp_wallet_gold_coin_log')->cols(array(
                'gold_coin_version'=>$member_update_gold_coin_version,
                'user_id'=>$memberWallets['user_id'],
                'oil_num'=>$memberWallets['gold_coin'],
                'this_num'=>$memberWallets['gold_coin'] - $gifts['gift_coin'],
                'num'=>$gifts['gift_coin'],
                'status'=>2,
                'type'=>1,
                'remark'=>"赠送主播礼物【{$gifts['name']}】",
                'updated'=>time(),
                'created'=>time()
            ))->query();
            if(!$wallet_gold_coin_id){
                throw new \Exception("您赠送礼物失败了");
            }
            $update_wallet_gold = self::$db->update('sp_wallet')->where("user_id={$memberWallets['user_id']} AND gold_coin_version={$member_gold_coin_version}")
                                           ->cols([
                                               'gold_coin'=>$memberWallets['gold_coin'] - $gifts['gift_coin'],
                                               'gold_coin_version'=>$member_update_gold_coin_version
                                           ])
                                           ->query();
            if(!$update_wallet_gold){
                throw new \Exception("扣除金币余额失败");
            }


            $anchor_gold_coin_version = $anchorWallets['gold_coin_version'];
            $anchor_update_gold_coin_version = $anchorWallets['gold_coin_version'] + 1;
            //赠送礼物的人
            $anchor_wallet_gold_coin_id = self::$db->insert('sp_wallet_gold_coin_log')->cols(array(
                'gold_coin_version'=>$anchor_update_gold_coin_version,
                'user_id'=>$anchorWallets['user_id'],
                'oil_num'=>$anchorWallets['gold_coin'],
                'this_num'=>$anchorWallets['gold_coin'] + $anchor_gold_coin,
                'num'=>$anchor_gold_coin,
                'status'=>1,
                'type'=>1,
                'remark'=>"收到用户礼物【{$gifts['name']}】分成",
                'updated'=>time(),
                'created'=>time()
            ))->query();
            if(!$anchor_wallet_gold_coin_id){
                throw new \Exception("您赠送礼物失败了");
            }
            $anchor_update_wallet_gold = self::$db->update('sp_wallet')->where("user_id={$anchorWallets['user_id']} AND gold_coin_version={$anchor_gold_coin_version}")
                                                  ->cols([
                                                      'gold_coin'=>$anchorWallets['gold_coin'] + $anchor_gold_coin,
                                                      'gold_coin_version'=>$anchor_update_gold_coin_version
                                                  ])
                                                  ->query();
            if(!$anchor_update_wallet_gold){
                throw new \Exception("主播增加金币余额失败");
            }


            $shop_gold_coin_version = $shopWallets['gold_coin_version'];
            $shop_update_gold_coin_version = $shopWallets['gold_coin_version'] + 1;
            //店铺送接受礼物
            $shop_wallet_gold_coin_id = self::$db->insert('sp_wallet_shop_gold_coin_log')->cols(array(
                'gold_coin_version'=>$anchor_update_gold_coin_version,
                'shop_id'=>$data['shop_id'],
                'oil_num'=>$shopWallets['gold_coin'],
                'this_num'=>$shopWallets['gold_coin'] + $shop_gold_coin,
                'num'=>$shop_gold_coin,
                'status'=>1,
                'type'=>1,
                'remark'=>"收到用户礼物【{$gifts['name']}】分成",
                'updated'=>time(),
                'created'=>time()
            ))->query();
            if(!$shop_wallet_gold_coin_id){
                throw new \Exception("店铺礼物余额日志写入失败了");
            }
            $shop_update_wallet_gold = self::$db->update('sp_shop_wallet')->where("shop_id={$data['shop_id']} AND gold_coin_version={$shop_gold_coin_version}")
                                                ->cols([
                                                    'gold_coin'=>$shopWallets['gold_coin'] + $shop_gold_coin,
                                                    'gold_coin_version'=>$shop_update_gold_coin_version
                                                ])
                                                ->query();
            if(!$shop_update_wallet_gold){
                throw new \Exception("店铺增加金币余额失败");
            }
            self::$db->commitTrans();
            return ['status'=>true,'msg'=>"增加成功"];
        }catch (\Exception $exception){
            self::$db->rollBackTrans();
            return ['status'=>false,'msg'=>$exception->getMessage()];
        }
    }
}
