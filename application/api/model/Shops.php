<?php
/**
 * @Description: 开发场景配置文件
 * @Author : 梧桐 <2487937004@qq.com>
 * @Copyright : 武汉一一零七科技有限公司 All rights reserved.
 */
namespace app\api\model;
use think\Model;
use think\Db;

class Shops extends Model
{
    public function getRecommendShops($num){
        return Db::name('shops')
            ->where(['open_status'=>1,'normal'=>1])
            ->limit($num)
            ->select();
    }
}