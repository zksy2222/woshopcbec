<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
require_once dirname(__FILE__).'/../../../im/vendor/workerman/gatewayclient/Gateway.php';
use GatewayClient\Gateway;

class SocketNotice extends Common{
    public function send($data){
        ini_set('default_socket_timeout', -1);  //不超时
        Gateway::$registerAddress = '127.0.0.1:1240';
        $result['type']='live_notice';
        $result['content']=$data['notice_content'];
        $jsonmsg = json_encode($result);
        Gateway::sendToClient($data['clientid'],$jsonmsg);
    }
}