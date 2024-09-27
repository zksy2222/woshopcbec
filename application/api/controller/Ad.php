<?php
namespace app\api\controller;

use app\api\model\AdCate as AdCateModel;
use app\api\model\Test as AdModel;

class Ad extends Common{

    public function getAdByTag(){
//        $tokenRes = $this->checkToken(0);
//        if($tokenRes['status'] == 400){
//            datamsg(400,$tokenRes['mess']);
//        }
        $tag = input('post.tag');
        if(empty($tag)){
           datamsg(400,'缺少广告位标识参数');
        }

        $adCateModel = new AdCateModel();
        $adCate = $adCateModel->getAdCate($tag);
        $ad = $adCateModel->getAdByTag($tag);
        $width =$adCate['width'];
        $height =$adCate['height'];
        if(!$width || !$height){
            $width = 700;
            $height = 280;
        }
        foreach ($ad as $k=>$v){
            $ad[$k]['ad_pic'] = url_format($v['ad_pic'],$this->webconfig['weburl'],'?imageMogr2/thumbnail/'.$width.'x'.$height);
            $ad[$k]['ad_url_type'] = 'navigateTo'; // 常规页面链接
            if(!empty($v['ad_url'])){
                if(strpos($v['ad_url'],'tabBar') > 0){
                    $ad[$k]['ad_url_type'] = 'tab'; // tabbar页面链接
                }
            }
        }
        datamsg(200,'获取广告信息成功',$ad);
    }
}