<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
// 应用函数文件
error_reporting(E_ERROR);

use think\Db;

define('PAGE', 12);
define('SUCCESS', 'success');
define('UPDATE_ORDER', '修改订单');
define('WIN', 200);
define('LOSE', 400);
/**
 * 请求接口返回内容
 * @param string $url [请求的URL地址]
 * @param string $params [请求的参数]
 * @param int $ipost [是否采用POST形式]
 * @return  string
 */
function juhecurl($url, $params = false, $ispost = 0)
{
    $httpInfo = array();
    $ch       = curl_init();

    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'JuheData');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($ispost) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $url);
    } else {
        if ($params) {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
        }
    }
    $response = curl_exec($ch);
    if ($response === FALSE) {
        //echo "cURL Error: " . curl_error($ch);
        return false;
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
    curl_close($ch);
    return $response;
}

function is_serialized($data, $strict = true) {
    if (!is_string($data)) {
        return false;
    }
    $data = trim($data);
    if ('N;' == $data) {
        return true;
    }
    if (strlen($data) < 4) {
        return false;
    }
    if (':' !== $data[1]) {
        return false;
    }
    if ($strict) {
        $lastc = substr($data, -1);
        if (';' !== $lastc && '}' !== $lastc) {
            return false;
        }
    } else {
        $semicolon = strpos($data, ';');
        $brace = strpos($data, '}');
        if (false === $semicolon && false === $brace)
            return false;
        if (false !== $semicolon && $semicolon < 3)
            return false;
        if (false !== $brace && $brace < 4)
            return false;
    }
    $token = $data[0];
    switch ($token) {
        case 's' :
            if ($strict) {
                if ('"' !== substr($data, -2, 1)) {
                    return false;
                }
            } elseif (false === strpos($data, '"')) {
                return false;
            }
        case 'a' :
        case 'O' :
            return (bool)preg_match("/^{$token}:[0-9]+:/s", $data);
        case 'b' :
        case 'i' :
        case 'd' :
            $end = $strict ? '$' : '';
            return (bool)preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
    }
    return false;
}

function iunserializer($value)
{
    if (empty($value)) {
        return '';
    }
    if (!is_serialized($value)) {
        return $value;
    }
    $result = unserialize($value);
    if ($result === false) {
        $temp = preg_replace_callback('!s:(\d+):"(.*?)";!s', function ($matchs) {
            return 's:' . strlen($matchs[2]) . ':"' . $matchs[2] . '";';
        }, $value);
        return unserialize($temp);
    }
    return $result;
}

/**
 * 获取从当日开始的一个月时间段
 * @param $date
 * @return array
 */
function get_month()
{
    $firstday = date("Y-m-d", strtotime('now'));
    $lastday  = date("Y-m-d", strtotime("$firstday +1 month"));
    return array($firstday, $lastday);
}

/**
 * 数组分页函数  核心函数  array_slice
 * 用此函数之前要先将数据库里面的所有数据按一定的顺序查询出来存入数组中
 * $count   每页多少条数据
 * $page   当前第几页
 * $array   查询出来的所有数组
 * order 0 - 不变     1- 反序
 */

function page_array($count, $page, $array, $order)
{
    global $countpage; #定全局变量
    $page  = (empty($page)) ? '1' : $page; #判断当前页面是否为空 如果为空就表示为第一页面
    $start = ($page - 1) * $count; #计算每次分页的开始位置
    if ($order == 1) {
        $array = array_reverse($array);
    }
    $totals    = count($array);
    $countpage = ceil($totals / $count); #计算总页面数
    $pagedata  = array();

    $pagedata = array_slice($array, $start, $count);
    return $pagedata;  #返回查询数据
}

/**
 * base64图片上传
 * @param $base64
 * @param string $path
 * @return bool|string
 */

function get_base64_img($base64, $path = 'upload/cards/')
{
    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64, $result)) {
        mk_dirs($path . date('Ymd', time()));
        $path     = $path . date('Ymd', time()) . "/";
        $type     = $result[2];
        $co       = rand('1', '20');
        $new_file = $path . md5(time() . $co) . ".{$type}";
        if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64)))) {
            return "/" . $new_file;
        } else {
            return false;
        }
    }
}

/**
 * 图片转换
 * @return [type] [description]
 */
function cover_img($img = '')
{
    $http = "https://" . $_SERVER['SERVER_NAME'];
    $img != '' ? $data = $http . $img : $data = $http . '/upload/nopic.png';
    return $data;
}

function cover_img2($img = '')
{
    $http = "https://" . $_SERVER['SERVER_NAME'];
    $img != '' ? $data = $http . $img : $data = '';
    return $data;
}

/**
 * 获取json类型
 * @param  [type] $result [json状态]
 * @return [type]         [返回json类型]
 */

function result_type($result)
{
    $res = $result;
    switch ($result) {
        case 'arr':
            $res = array();
            break;
        case 'obj':
            $res = (object)array();
            break;
        case 'str':
            $res = "";
            break;
        case null:
            $res = (object)array();
            break;
    }
    return $res;
}

/**
 * 返回json数据
 */
if (!function_exists('datamsg')) {
    function datamsg($code, $msg, $result = '')
    {
        $data['status'] = $code;
        $data['mess']   = lang($msg);
        is_object($result) ? $result = $result->toArray() : '';

        if (!empty($result)) {
            $data['data'] = result_type($result);
        } else {
            $data['data'] = array();
        }

        echo json_encode(unserialize(str_replace(array('NAN;', 'INF;'), '0;', serialize($data))));
        die;
    }
}
/**
 * 多语言改变数组返回值，
 * @param $data
 * @return mixed
 */
function set_lang($data) {

    foreach ($data as $key => &$val) {
        if(is_object($val)){
            $val = $val->toArray();
        }
        if (is_array($val)) {
            if (!empty($val)) {
                $data[$key] = set_lang($val);
            }
        } else {
            if(!empty($data[$key])){
                $data[$key] = lang($data[$key]);
            }
        }
    }
    return $data;
}
/**
 * 获取请求头数据
 *
 * @return array
 */
function get_all_header()
{
    // 忽略获取的header数据。这个函数后面会用到。主要是起过滤作用
    $ignore  = array('host', 'accept', 'content-length', 'content-type');
    $headers = array();
    //这里大家有兴趣的话，可以打印一下。会出来很多的header头信息。咱们想要的部分，都是‘http_'开头的。所以下面会进行过滤输出。
    /*    var_dump($_SERVER);
        exit;*/

    foreach ($_SERVER as $key => $value) {
        if (substr($key, 0, 5) === 'HTTP_') {
            //这里取到的都是'http_'开头的数据。
            //前去开头的前5位
            $key = substr($key, 5);
            //把$key中的'_'下划线都替换为空字符串
            $key = str_replace('_', ' ', $key);
            //再把$key中的空字符串替换成‘-’
            $key = str_replace(' ', '-', $key);
            //把$key中的所有字符转换为小写
            $key = strtolower($key);

            //这里主要是过滤上面写的$ignore数组中的数据
            if (!in_array($key, $ignore)) {
                $headers[$key] = $value;
            }
        }
    }
    //输出获取到的header
    return $headers;

}


/**
 * 重组数组的结构(二维数组)
 *
 * @param $arr
 * @param null $find_index
 * @param null $value_index
 * @param null $operation
 * @return mixed|null|number
 */
function array_index_value($arr, $find_index = null, $value_index = null, $operation = null)
{
    if (empty($arr)) {
        return array();
    }
    $ret   = null;
    $names = @array_reduce($arr, create_function('$v,$w', '$v[' . ($find_index ? '$w[' . $find_index . ']' : '') . ']=' . ($value_index ? '$w[' . $value_index . ']' : '$w') . ';return $v;'));

    switch ($operation) {
        case 'sum':
            $ret = array_sum($names);
            break;
        default:
            $ret = $names;
            break;
    }
    return $ret;
}

/**
 * 生成目录结构
 * @param string $path 插件完整路径
 * @param array $list 目录列表
 */
function mk_dirs($a1, $mode = 0777)
{
    if (is_dir($a1) || @mkdir($a1, $mode)) return TRUE;
    if (!mkdir(dirname($a1), $mode)) return FALSE;
    return @mkdir($a1, $mode);
}

/*
 * 递归实现无限级分类
 *
 */
function recursive($array, $pid = 0, $level = 0)
{
    $arr = array();
    foreach ($array as $v) {
        if ($v['pid'] == $pid) {
            $v['level'] = $level;
            $v['html']  = str_repeat('--', $level);
            $arr[]      = $v;
            $arr        = array_merge($arr, recursive($array, $v['id'], $level + 1));
        }
    }
    return $arr;
}

/*
 * 传递一个id获取它的所有子类id
 *
 */
function get_all_child($array, $id)
{
    $arr = array();
    foreach ($array as $v) {
        if ($v['pid'] == $id) {
            $arr[] = $v['id'];
            $arr   = array_merge($arr, get_all_child($array, $v['id']));
        }
    }
    return $arr;
}


/*
 *
 * 传递一个id获取所有父类及它自己
 *
 */
function get_all_parent($array, $id)
{
    $arr = array();
    foreach ($array as $v) {
        if ($v['id'] == $id) {
            $arr[] = $v['id'];
            $arr   = array_merge($arr, get_all_parent($array, $v['pid']));
        }
    }
    return $arr;
}

//生成一个不会重复的字符串
function settoken()
{
    $str = md5(uniqid(md5(microtime(true)), true));
    $str = sha1($str); //加密
    return $str;
}

//随机生成数字与字母组合
function get_random_string($len, $chars = null)
{
    if (is_null($chars)) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    }
    mt_srand(10000000 * (double)microtime());
    for ($i = 0, $str = '', $lc = strlen($chars) - 1; $i < $len; $i++) {
        $str .= $chars[mt_rand(0, $lc)];
    }
    return $str;
}


//生成六位验证码
function create_sms_code($length = 6)
{
    $min = pow(10, ($length - 1));
    $max = pow(10, $length) - 1;
    return rand($min, $max);
}


//自定义函数：time2string($second) 输入秒数换算成多少天/多少小时/多少分/多少秒的字符串
function time2string($second)
{
    $day    = floor($second / (3600 * 24));
    $second = $second % (3600 * 24);//除去整天之后剩余的时间
    $hour   = floor($second / 3600);
    $second = $second % 3600;//除去整小时之后剩余的时间
    $minute = floor($second / 60);
    $second = $second % 60;//除去整分钟之后剩余的时间
    //返回字符串
    if ($day == 0) {
        return $hour . '小时' . $minute . '分';
    } else {
        return $day . '天' . $hour . '小时' . $minute . '分';
    }
}

//下载文件
function download_weixin_file($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_NOBODY, 0);    //只取body头
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $package  = curl_exec($ch);
    $httpinfo = curl_getinfo($ch);
    curl_close($ch);
    $imageAll = array_merge(array('body' => $package), array('header' => $httpinfo));
    return $imageAll;
}


/**
 * 对银行卡号进行掩码处理
 * @param string $bankCardNo 银行卡号
 * @return string             掩码后的银行卡号
 */
function format_bankcard_no($bankCardNo)
{
    //截取银行卡号后4位
    $suffix         = substr($bankCardNo, -4, 4);
    $maskBankCardNo = "**** **** **** **** " . $suffix;
    return $maskBankCardNo;
}

function xml_to_array($xml)
{
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $values;
}

/**
 *  发送短信验证码
 * @param
 * @return object
 * @author:Damow
 */
function send_sms($mobiles, $param,$templateId)
{
    import('Ucpaas', EXTEND_PATH);
    $options['accountsid'] = get_config_value('message_accountsid');
    //填写在开发者控制台首页上的Auth Token
    $options['token'] = get_config_value('message_token');
    $Ucpaas           = new \Ucpaas($options);
    //初始化 $options必填
    $appid      = get_config_value('message_appid');    //应用的ID，可在开发者控制台内的短信产品下查看

    $uid = "";
    //70字内（含70字）计一条，超过70字，按67字/条计费，超过长度短信平台将会自动分割为多条发送。分割后的多条短信将按照具体占用条数计费。
    $acsResponse = $Ucpaas->SendSms($appid, $templateId, $param, $mobiles, $uid);
    $acsResponse = json_decode($acsResponse);
    return $acsResponse;
}

/**
 *  发送邮箱验证码
 * @param
 * @return object
 * @author:Damow
 */
function send_email($email, $code)
{
    vendor('phpmailer.PHPMailerAutoload'); // 引入
    $toemail = $email;
    $mail = new PHPMailer\PHPMailer\PHPMailer(); // 新建
    $mail->isSMTP();  //  开启SMTP
    $mail->CharSet = 'utf8';  // 设置编码
    $mail->Host = get_config_value('email_host'); // SMTP服务器
    $mail->SMTPAuth = true;  // smtp需要鉴权 这个必须是true
    $mail->Username = get_config_value('email_name'); // 发信人的账号
    $mail->Password = get_config_value('email_smtp'); // 密码，非邮箱密码，是SMTP生成的密码
    $mail->From = get_config_value('email_name'); // 发信人的地址
    $mail->SMTPSecure = 'ssl';  // 采用ssl协议
    $mail->Port = get_config_value('email_port'); // 端口号
    $mail->FromName = get_config_value('webtitle'); // 发件人昵称
    $mail->addAddress($toemail); // 收信人地址
    $mail->addReplyTo(get_config_value('web_email'));//回复的时候回复的邮箱，建议和发信人一样
    $mail->Subject = "您有新的验证码"; // 邮件主题
    $mail->Body = "您的验证码是：  ".$code."   有效期为".get_config_value('mess_valid_time')."分钟，本邮件请勿回复！";  // 邮件内容
    $status = $mail->send();
    return json_decode($status);
}

/**
 * 对象转数组
 *
 * @param object $obj 对象
 * @return array
 */
function object_to_array($obj)
{
    $obj = (array)$obj;
    foreach ($obj as $k => $v) {
        if (gettype($v) == 'resource') {
            return;
        }
        if (gettype($v) == 'object' || gettype($v) == 'array') {
            $obj[$k] = (array)object_to_array($v);
        }
    }
    return $obj;
}


/**
 * 二维数组根据某个字段排序
 * @param array $proszarray 要排序的数组
 * @param string $prokeys 要排序的键字段
 * @param string $prosort 排序类型  SORT_ASC     SORT_DESC
 * @return array 排序后的数组
 */
function array_sort($proszarray, $prokeys, $prosort = SORT_DESC)
{
    $keysValue = array_column($proszarray, $prokeys);
    array_multisort($keysValue, $prosort, $proszarray);
    return $proszarray;
}

// 获取用户角色
function get_user_role($userId)
{
    $userInfo = db('member')->find($userId);
    if ($userInfo['shop_id'] > 0) {
        $role = "shop";
    }
    if ($userInfo['pid'] > 0) {
        $role = 'service';
    }
    if ($userInfo['shop_id'] == 0 && $userInfo['pid'] == 0) {
        $role = 'user';
    }
    return $role;
}

/**
 * url格式化
 * @param string $url 需要格式化的url字符串
 * @param string $prefix url前缀
 * @param string $rule 使用第三方存储时，可传入图片处理规则
 * @return string 返回最终的url字符串
 */
function url_format($url, $prefix = '', $rule = '')
{
    if (empty($url)) {
        return $prefix . '/uploads/default.jpg';
    }
    if (substr($url, 0, 4) == 'http') {
        return $url.$rule;
    } else {
        return $prefix .'/'. $url;
    }
}


function get_config_info($id)
{
    $res = Db::name('config')->where('id', $id)->field('ename,value,values')->cache(60)->find();
    return $res;
}

//获取配置信息
function get_config_value($ename)
{
    $res = Db::name('config')->where('ename', $ename)->cache(60)->value('value');
    return $res;
}

//手机号验证
function is_mobile($mobile)
{
    $rule   = '/^1(3|4|5|7|8)[0-9]\d{8}$/';
    $result = preg_match($rule, $mobile);
    if ($result) {
        return true;
    } else {
        return false;
    }
}

//支付方式
function get_pay_type($type)
{
    switch ($type) {
        case 1:
            $payType = '支付宝';
            break;
        case 2:
            $payType = '微信支付';
            break;
        case 3:
            $payType = '余额支付';
            break;
        case 4:
            $payType = '朋友代付';
            break;
        case 5:
            $payType = '银行卡支付';
            break;
        case 6:
            $payType = 'USDTTRC20支付';
            break;
        case 7:
            $payType = 'USDTERC20支付';
            break;
        default:
            $payType = '-';
            break;
    }
    return lang($payType);
}

function pwdEncrypt($pwd, $salt = '')
{
    if (!empty($salt)) {
        return md5($pwd . md5($salt));
    } else {
        return md5($pwd);
    }
}

function get_withdraw_type_name($type)
{
    switch ($type) {
        case 1:
            $typeNme = '余额提现';
            break;
        case 2:
            $typeNme = '佣金提现';
            break;
        default:
            $typeNme = '-';
            break;
    }
    return $typeNme;
}

function get_areas()
{

    $file = ROOT_PATH . '/public/static/admin/js/area/AreaNew.xml';
    $fileStr = file_get_contents($file);
    $areas = json_decode(json_encode(simplexml_load_string($fileStr)), true);
    if (!empty($areas['province'])) {
        foreach ($areas['province'] as $k => &$row) {
            if (0 < $k) {
                if (empty($row['city'][0])) {
                    $row['city'][0]['@attributes'] = $row['city']['@attributes'];
                    $row['city'][0]['county'] = $row['city']['county'];
                    unset($row['city']['@attributes']);
                    unset($row['city']['county']);
                }
            }
            else {
                unset($areas['province'][0]);
            }

            foreach ($row['city'] as $k1 => $v1) {
                if (empty($v1['county'][0])) {
                    $row['city'][$k1]['county'][0]['@attributes'] = $v1['county']['@attributes'];
                    unset($row['city'][$k1]['county']['@attributes']);
                }
            }
        }

        unset($row);
    }

    return $areas;
}

function ihttp_request($url, $data = null)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    if (!empty($data)) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $info = curl_exec($curl);
    curl_close($curl);
    return $info;
}

// 混淆手机号，中间5位用*代替
function mix_phone($phone){
    if(config('mix_phone')){
        if(empty($phone)){
            $phone = "*****";
        }else{
            $phone = substr($phone,0,3)."*****".substr($phone,8);
        }
        return $phone;
    }else{
        return $phone;
    }
}

// 过滤掉emoji表情
function filterEmoji($str)
{
    $str = preg_replace_callback(    //执行一个正则表达式搜索并且使用一个回调进行替换
        '/./u',
        function (array $match) {
            return strlen($match[0]) >= 4 ? '' : $match[0];
        },
        $str);
    return $str;
}


//查找字符串
function cut($begin,$end,$str){
    $b = mb_strpos($str,$begin) + mb_strlen($begin);
    $e = mb_strpos($str,$end,$b) - $b;
    return mb_substr($str,$b,$e);
}


//获取用户的ip
function getIP(){
    if(!empty($_SERVER["HTTP_CLIENT_IP"])){
        $cip = $_SERVER["HTTP_CLIENT_IP"];
    }
    elseif(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
        $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    }
    elseif(!empty($_SERVER["REMOTE_ADDR"])){
        $cip = $_SERVER["REMOTE_ADDR"];
    }
    else{
        $cip = "Unknown";
    }
    return $cip;
}