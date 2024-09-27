<?php
/**
 * @Description: 直播Model
 * @Author : 梧桐 <2487937004@qq.com>
 * @Copyright : 武汉一一零七科技有限公司 All rights reserved.
 */

namespace app\api\model;

use think\Db;
use think\Model;

class Live extends Model
{
    public function getLiveByShopId($shop_id)
    {
        $where = array('shop_id' => $shop_id);
        return $this->where($where)->find();
    }

    /*
     * 获取推荐直播间
     * @param $num int 直播间数量
     * */
    public function getRecommendLiveRoom($num)
    {
        return $this->field('id,cover,status,shop_id')
                    ->where('isrecommend', 1)
                    ->where('is_recycle',0)
                    ->where('status','<>', 2)
                    ->where('isclose','<>', 1)
                    ->limit($num)
                    ->select();
    }
}