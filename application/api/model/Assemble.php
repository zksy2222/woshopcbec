<?php
/**
 * @Description: 拼团
 * @Author : 梧桐 <2487937004@qq.com>
 * @Copyright : 武汉一一零七科技有限公司 All rights reserved.
 */
namespace  app\api\model;
use think\Db;
use think\Model;

class Assemble extends Model
{
    public function getRecommendAssemble($num){
//        $sql = "SELECT
//                        `a`.goods_id as id, `g`.`thumb_url`,`g`.`shop_price`
//                FROM
//                        sp_assemble a
//                INNER JOIN `sp_goods` `g` ON `a`.`goods_id` = `g`.`id`
//                INNER JOIN `sp_shops` `c` ON `c`.`id` = `g`.`shop_id`
//                WHERE
//                `a`.`checked` = 1
//                AND `a`.`recommend` = 1
//                AND `a`.`is_show` = 1
//                AND `g`.`onsale` = 1
//                AND `c`.`open_status` = 1
//                AND (`a`.`start_time` > ".time()." OR (`a`.`start_time` <= ".time()." AND `a`.`end_time` > ".time()."))
//                ORDER BY
//                        `id` DESC
//                LIMIT {$num}";
//        return Db::query($sql);

        $where['a.checked'] = 1;
        $where['a.is_show'] = 1;
        $where['a.start_time'] = array('elt',time());
        $where['a.end_time'] = array('gt',time());
        $where['a.finish_status'] = 0;
        $where['b.onsale'] = 1;
        $where['c.open_status'] = 1;
        $assembleres = Db::name('assemble')
            ->alias('a')
            ->field('a.id,a.goods_id,a.goods_attr,a.price,a.pin_num,a.sold,b.goods_name,b.thumb_url,b.shop_price,b.min_price,b.max_price,b.zs_price,b.leixing,b.shop_id')
            ->join('sp_goods b','a.goods_id = b.id','INNER')
            ->join('sp_shops c','a.shop_id = c.id','INNER')
            ->where($where)
            ->group('a.goods_id')
            ->order('a.sort esc')
            ->limit($num)
            ->select();
        return $assembleres;
    }
}