<?php
namespace app\api\controller;
use think\Controller;
use think\Db;

class WxPay extends Controller{


    //获取预支付订单
    public function getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url){

    	$wxpayRes=db('wxpay_config')->where('id',1)->find();
    	$appid=$wxpayRes['app_appid'];
	    $mch_id=$wxpayRes['mch_id'];

    	$url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $onoce_str = $this->getRandChar(32);
        $data["appid"] =$appid;
        $data["body"] = $body;
        $data["mch_id"] = $mch_id;
        $data["nonce_str"] = $onoce_str;
        $data["notify_url"] = $notify_url;
        $data["out_trade_no"] = $out_trade_no;
        $data["spbill_create_ip"] = $this->get_client_ip();
        $data["total_fee"] = $total_fee;
        $data["time_start"] = date('YmdHis', $time_start);
        $data["time_expire"] = date('YmdHis', $time_expire);
        $data["trade_type"] = "APP";
        $s = $this->getSign($data, false);
        $data["sign"] = $s;

        $xml = $this->arrayToXml($data);
        $response = $this->postXmlCurl($xml, $url);

        //将微信返回的结果xml转成数组
        return xml_to_array($response);
    }




    //获取预支付订单
    public function getPrePcPayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url){

        $wxpayRes=db('wxpay_config')->where('id',1)->find();
        $appid=$wxpayRes['app_appid'];
        $mch_id=$wxpayRes['mch_id'];

        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $onoce_str = $this->getRandChar(32);
        $data["appid"] =$appid;
        $data["body"] = $body;
        $data["mch_id"] = $mch_id;
        $data["nonce_str"] = $onoce_str;
        $data["notify_url"] = $notify_url;
        $data["out_trade_no"] = $out_trade_no;
        $data["spbill_create_ip"] = $this->get_client_ip();
        $data["total_fee"] = $total_fee;
        $data["time_start"] = date('YmdHis', $time_start);
        $data["time_expire"] = date('YmdHis', $time_expire);
        $data["trade_type"] = "NATIVE";
        $s = $this->getSign($data, false);
        $data["sign"] = $s;

        $xml = $this->arrayToXml($data);
        $response = $this->postXmlCurl($xml, $url);
        //将微信返回的结果xml转成数组
        return xml_to_array($response);
    }


    //执行第二次签名，才能返回给客户端使用
    public function getOrder($prepayId){
	    $wxpayRes=db('wxpay_config')->where('id',1)->find();
	    $appid=$wxpayRes['app_appid'];
	    $mch_id=$wxpayRes['mch_id'];

        $data["appid"] = $appid;
        $data["noncestr"] = $this->getRandChar(32);;
        $data["package"] = "Sign=WXPay";
        $data["partnerid"] = $mch_id;
        $data["prepayid"] = $prepayId;
        $data["timestamp"] = time();
        $s = $this->getSign($data, false);
        $data["sign"] = $s;

        return $data;
    }

    /*
        生成签名
    */
    function getSign($Obj)
    {
	    $wxpayRes=db('wxpay_config')->where('id',1)->find();
	    $api_key=$wxpayRes['api_key'];

        foreach ($Obj as $k => $v)
        {
            $Parameters[strtolower($k)] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //echo "【string】 =".$String."</br>";
        //签名步骤二：在string后加入KEY
        $String = $String."&key=".$api_key;
        //签名步骤三：MD5加密
        $result_ = strtoupper(md5($String));
        return $result_;
    }

    //获取指定长度的随机字符串
    function getRandChar($length){
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol)-1;

        for($i=0;$i<$length;$i++){
            $str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }

        return $str;
    }

    //数组转xml
    function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
            if (is_numeric($val))
            {
                $xml.="<".$key.">".$val."</".$key.">";

            }
            else
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
        }
        $xml.="</xml>";
        return $xml;
    }

    //post https请求，CURLOPT_POSTFIELDS xml格式
    function postXmlCurl($xml,$url,$second=30)
    {
        //初始化curl
        $ch = curl_init();
        //超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT,$second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data)
        {
            curl_close($ch);
            return $data;
        }
        else
        {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error"."<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);
            return false;
        }
    }

    /*
        获取当前服务器的IP
    */
    function get_client_ip()
    {
        if ($_SERVER['REMOTE_ADDR']) {
            $cip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv("REMOTE_ADDR")) {
            $cip = getenv("REMOTE_ADDR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $cip = getenv("HTTP_CLIENT_IP");
        } else {
            $cip = "unknown";
        }
        return $cip;
    }

    //将数组转成uri字符串
    function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v)
        {
            if($urlencode)
            {
                $v = urlencode($v);
            }
            $buff .= strtolower($k) . "=" . $v . "&";
        }
        $reqPar;
        if (strlen($buff) > 0)
        {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return $reqPar;
    }

    /**
    xml转成数组
     */
    function domnode_to_array($node) {
        $output = array();
        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;
            case XML_ELEMENT_NODE:
                for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) {
                    $child = $node->childNodes->item($i);
                    $v = $this->domnode_to_array($child);
                    if(isset($child->tagName)) {
                        $t = $child->tagName;
                        if(!isset($output[$t])) {
                            $output[$t] = array();
                        }
                        $output[$t][] = $v;
                    }
                    elseif($v) {
                        $output = (string) $v;
                    }
                }
                if(is_array($output)) {
                    if($node->attributes->length) {
                        $a = array();
                        foreach($node->attributes as $attrName => $attrNode) {
                            $a[$attrName] = (string) $attrNode->value;
                        }
                        $output['@attributes'] = $a;
                    }
                    foreach ($output as $t => $v) {
                        if(is_array($v) && count($v)==1 && $t!='@attributes') {
                            $output[$t] = $v[0];
                        }
                    }
                }
                break;
        }
        return $output;
    }

    public function wxNotify(){
        $xml = file_get_contents('php://input');
        // 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了
        //file_put_contents('./Api/wxpay/logs/log.txt',$xml,FILE_APPEND);
        //将服务器返回的XML数据转化为数组
        $data = xml_to_array($xml);
        // 保存微信服务器返回的签名sign
        $data_sign = $data['sign'];
        // sign不参与签名算法
        unset($data['sign']);
        $wx = new WxPay;
        $sign = $wx->getSign($data);

        // 判断签名是否正确  判断支付状态
        if ( ($sign===$data_sign) && ($data['return_code']=='SUCCESS') && ($data['result_code']=='SUCCESS') ) {
            //获取服务器返回的数据
            $order_sn = $data['out_trade_no'];  //订单单号
            $total_fee = $data['total_fee'];    //付款金额
            $pay = new Pay();
            $orderType = substr( $data['out_trade_no'],0,1);
            // $orderType Z-商品订单，C-充值订单，R-商家入驻保证金订单
            switch ($orderType){
                case "Z":
                    $pay->doGooodsOrder($order_sn,2);
                    break;
                case "C":
                    $pay->doRechargeOrder($order_sn,2);
                    break;
                case "R":
                    $pay->doRzOrder($order_sn,2);
                    break;
                default:
                    // 系统错误，未获取到订单类型
            }
        }else{
            $result = false;
        }
        // 返回状态给微信服务器
        if ($result) {
            $str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        }else{
            $str='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
        }
        echo $str;
    }
}