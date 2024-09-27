<?php

$config = db('config');
$appId = $config->where('ename', 'unipush_appid')->value('value');
$packageName = $config->where('ename', 'unipush_package_name')->value('value');
$masterSecret = $config->where('ename', 'unipush_mastersecret')->value('value');
$appKey = $config->where('ename', 'unipush_appkey')->value('value');
$host = $config->where('ename', 'unipush_host')->value('value');

define('APPID', $appId); // 个推平台申请应用的AppID
define('APPKEY', $appKey); // 个推平台申请应用的AppKey
define('MASTERSECRET', $masterSecret); // 个推平台申请应用的MasterSecret
define('HOST', $host); // 个推推送平台服务器地址
define('PACKAGENAME', $packageName); //应用包名，修改为自己应用的包名


?>