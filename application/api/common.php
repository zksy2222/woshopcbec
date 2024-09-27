<?php
/*
 * @Descripttion: 
 * @Copyright: 武汉一一零七科技有限公司©版权所有
 * @Link: www.s1107.com
 * @Contact: QQ:2487937004
 * @LastEditors: cbing
 * @LastEditTime: 2020-04-14 11:16:55
 */

    /**
 * 模拟 http 请求
 * @param  String $url  请求网址
 * @param  Array  $data 数据
 */
function https_request($url, $data = null){
    // curl 初始化
    $curl = curl_init();

    // curl 设置
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

    // 判断 $data get  or post
    if ( !empty($data) ) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    // 执行
    $res = curl_exec($curl);
    curl_close($curl);
    return $res;
}

function time_ago($posttime){
    //当前时间的时间戳
    $nowtimes = strtotime(date('Y-m-d H:i:s'),time());
    //之前时间参数的时间戳
    $posttimes = strtotime($posttime);
    //相差时间戳
    $counttime = $nowtimes - $posttimes;
    //进行时间转换
    if($counttime<=10){
        return '刚刚';
    }else if($counttime>10 && $counttime<=30){
        return '刚才';
    }else if($counttime>30 && $counttime<=60){
        return '刚一会';
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
        return $posttime;
    }
}


/*
Utf-8、gb2312都支持的汉字截取函数
cut_str(字符串, 截取长度, 开始长度, 编码);
编码默认为 utf-8
开始长度默认为 0
*/
function cut_str($str,$len,$suffix="..."){
    if(function_exists('mb_substr')){
        if(strlen($str) > $len){
            $str= mb_substr($str,0,$len,'utf-8').$suffix;
        }
        return $str;
    }else{
        if(strlen($str) > $len){
            $str= substr($str,0,$len,'utf-8').$suffix;
        }
        return $str;
    }
}


/**
 * @func 获取当前域名地址和https
 */
function domain($domianname){
//    $type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
//    if(empty($type)){
//        $type = "https://";
//    }
//    $domain = $type.$_SERVER['SERVER_NAME'].'/';
    return $domianname;
}


/**
 * 获取唯一房间号
 */
function getRefereeId(){
    $code = rand(10000, 99999999);
    $userinfor = db('live')->where(['room'=>$code])->find();
    if(!empty($userinfor)){
        return getRefereeId(); //存在  就再运行
    }else{
        return $code;
    }
}