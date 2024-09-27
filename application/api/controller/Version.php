<?php

namespace app\api\controller;

use think\Db;
use app\api\model\Version as VersionModel;
use app\api\model\ShopVersion as ShopVersionModel;

class Version extends Common

{

    public function getVersionInfo(){
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }

        $platform = input('post.platform');
        $currentVersionCode = input('post.version');

        $versionModel = new VersionModel();
        $newAppVersionInfo = $versionModel->getNewAppVersion();
        $newVersionCode = 1402;
        $newVersionName = 'V1.4.0.3';
        if($currentVersionCode < $newAppVersionInfo['version_code']){
            $data['versionCode'] = $newAppVersionInfo['version_code'];
            $data['versionName'] = $newAppVersionInfo['version_name'];
            $data['versionInfo'] = $newAppVersionInfo['version_info'];
            $data['updateType'] = $newAppVersionInfo['update_type']; // forcibly = 强制更新, solicit = 弹窗确认更新, silent = 静默更新
            if($platform == 'android'){
                $data['downloadUrl'] = $newAppVersionInfo['android_url'];
            }else{
                $data['downloadUrl'] = $newAppVersionInfo['ios_url'];
            }
            datamsg(200,'存在新版本',$data);
        }else{
            datamsg(200,'暂无新版本');
        }
    }



    public function getShopVersionInfo(){
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }

        $platform = input('post.platform');
        $currentVersionCode = input('post.version');

        $versionModel = new ShopVersionModel();
        $newAppVersionInfo = $versionModel->getNewAppVersion();
        $newVersionCode = 1402;
        $newVersionName = 'V1.4.0.3';
        if($currentVersionCode < $newAppVersionInfo['version_code']){
            $data['versionCode'] = $newAppVersionInfo['version_code'];
            $data['versionName'] = $newAppVersionInfo['version_name'];
            $data['versionInfo'] = $newAppVersionInfo['version_info'];
            $data['updateType'] = $newAppVersionInfo['update_type']; // forcibly = 强制更新, solicit = 弹窗确认更新, silent = 静默更新
            if($platform == 'android'){
                $data['downloadUrl'] = $newAppVersionInfo['android_url'];
            }else{
                $data['downloadUrl'] = $newAppVersionInfo['ios_url'];
            }
            datamsg(200,'存在新版本',$data);
        }else{
            datamsg(200,'暂无新版本');
        }
    }

}