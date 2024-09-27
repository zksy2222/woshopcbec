<?php
namespace app\api\model;
use think\Db;
use think\Model;

class Seckill extends Model
{
    public function getRecommendSeckill($num){
//        $sql = "SELECT
//                        `r`.goods_id as id,`r`.goods_id, `g`.`thumb_url` ,
//                        `g`.`shop_price`,`g`.goods_name
//                FROM
//                        sp_seckill r
//                INNER JOIN `sp_goods` `g` ON `r`.`goods_id` = `g`.`id`
//                INNER JOIN `sp_shops` `c` ON `c`.`id` = `g`.`shop_id`
//                WHERE
//                        `r`.`checked` = 1
//                AND `r`.`recommend` = 1
//                AND `r`.`is_show` = 1
//                AND `g`.`onsale` = 1
//                AND `c`.`open_status` = 1
//                AND (`r`.`start_time` > ".time()." OR (`r`.`start_time` <= ".time()." AND `r`.`end_time` > ".time()."))
//                ORDER BY
//                        `id` DESC
//                LIMIT {$num}";
//        return Db::query($sql);


        $seckillRes = Db::name('seckill')->alias('a')
            ->field('a.id,a.goods_id,a.goods_attr,a.price,a.sold,b.goods_name,b.thumb_url,b.shop_price,b.min_price,b.max_price,b.zs_price,b.leixing,b.shop_id,b.hasoption')
            ->join('sp_goods b','a.goods_id = b.id','INNER')
            ->join('sp_shops c','a.shop_id = c.id','INNER')
            ->where('a.checked',1)
            ->where('a.recommend',1)
            ->where('a.is_show',1)
            ->where('a.start_time','elt',time())
            ->where('a.end_time','egt',time()+3600)
            ->where('a.finish_status',0)
            ->where('b.onsale',1)
            ->where('c.open_status',1)
            ->group('a.goods_id')->order('a.sort esc')->limit($num)->select();
            return $seckillRes;


    }

}