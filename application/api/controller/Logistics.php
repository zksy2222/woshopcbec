<?php
/*
 * @Author: your name
 * @Date: 2020-08-05 00:20:45
 * @LastEditTime: 2020-08-05 02:34:36
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: /daohang/Users/wutong/Library/Caches/com.binarynights.ForkLift-3/FileCache/FE8614C2-3AE1-4FC9-9E5B-131EEAEE9180/Logistics.php
 */
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use think\Db;

class Logistics extends Common{
    //快递鸟
    public function kdNiao(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            return json($tokenRes);
        }else{
            $userId = $tokenRes['user_id'];
        }
        $data = input('post.');
        if($data['kdniao_code'] == 'SF'){
            $telephone = substr($data['telephone'],-4);;
        }else{
            $telephone = '';
        }
        $appKey = $this->webconfig['kuaidiniao_appkey'];
        $eBusinessId =$this->webconfig['kuaidiniao_ebusinessid'];
        $requestData= "{".
            "'ShipperCode':'".$data['kdniao_code']."',".
            "'LogisticCode':'".$data['psnum']."',".
            "'CustomerName':'".$telephone."',".
            "}";
        $datas = array(
            'EBusinessID' => $eBusinessId,
            'RequestType' => $this->webconfig['kuaidiniao_request_type'],
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );

        $datas['DataSign'] = urlencode(base64_encode(md5($requestData.$appKey)));

        $url = 'http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx';
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);

        if(empty($url_info['port']))
        {
            $url_info['port']=80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }
        fclose($fd);
        $logisticsInfo = object_to_array(json_decode($gets));
        if($logisticsInfo['Success'] == true){
            datamsg(200,'获取成功',array_reverse($logisticsInfo['Traces']));
        }else{
            datamsg(400,'暂未查询到物流信息');
        }

    }


}
