<?php
/*
 * @Descripttion: 总后台框架控制器
 * @Copyright: 武汉一一零七科技有限公司©版权所有
 * @Contact: QQ:2487937004
 * @Date: 2020-03-09 17:48:34
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2020-08-03 16:13:40
 */
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Lang;
use app\admin\model\Order;

class Index extends Common
{
    public function index(){
        $menu = session('menu');
        $shop_id = session('shop_id');
        $orderModel = new Order();
        //待发货订单数量
        $deliverOrderNum = $orderModel->getOrderCunt($shop_id,1);
        //待付款订单数量
        $paymentOrderNum = $orderModel->getOrderCunt($shop_id,4);
        //售后订单数量
        $thApplyNum= db(th_apply)->where('apply_status',0)->count();
        //总数量
        $countNum = $deliverOrderNum+$paymentOrderNum+$thApplyNum;
        $this->assign('deliverOrderNum',$deliverOrderNum);
        $this->assign('paymentOrderNum',$paymentOrderNum);
        $this->assign('thApplyNum',$thApplyNum);
        $this->assign('countNum',$countNum);
        $this->assign('menu',$menu);
        $this->assign('webconfig',$this->webconfig);
        return $this->fetch();
    }

    public function dashboard(){
        $wallets = Db::name('pt_wallet')->where('id',1)->find();

        $nowtime = time();
        $year = date('Y',time());
        $year2 = $year+1;
        $month = date('m',time());
        $month2 = $month+1;

        $day = date('d',time());
        // 当月
        $nowmonth = strtotime($year.'-'.$month.'-01 00:00:00');
        // 下一个月
        $lastmonth = strtotime($year.'-'.$month2.'-01 00:00:00');
        $nowyear   = strtotime($year.'-01-01 00:00:00');
        $lastyear  = strtotime($year2.'-01-01 00:00:00');

        // 全部已支付订单
        $order_num = Db::name('order')->where('state',1)->count();
        // 本月所有订单
        $month_order_num_all = Db::name('order')->whereTime('addtime','m')->count();
        // 本月已支付订单
        $month_order_num = Db::name('order')->where('state',1)->whereTime('addtime','m')->count();
        // 本月已成交订单
        $deal_num = Db::name('order')->whereTime('addtime','m')->where('order_status',1)->count();
        // 本月待支付订单
        $dai_num  = Db::name('order')->whereTime('addtime','m')->where('state',0)->count();
        // 本月售后订单
        $shou_num = Db::name('order')->whereTime('addtime','m')->where('shouhou',1)->count();

        if($month_order_num_all > 0){
            $deal_lv = sprintf("%.2f",$deal_num/$month_order_num_all)*100;
            $dai_lv = sprintf("%.2f",$dai_num/$month_order_num_all)*100;
            $shou_lv = sprintf("%.2f",$shou_num/$month_order_num_all)*100;
        }else{
            $deal_lv = 0;
            $dai_lv  = 0;
            $shou_lv = 0;
        }

        $month_salenum = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id=b.id','INNER')->where('b.state',1)->where('b.addtime','egt',$nowmonth)->where('b.addtime','lt',$lastyear)->count();
        $month_tuinum = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id = b.id','INNER')->where('a.th_status','in','1,2,3,4,9')->where('b.state',1)->where('b.addtime','egt',$nowmonth)->where('b.addtime','lt',$lastyear)->count();
        $month_huannum = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id = b.id','INNER')->where('a.th_status','in','5,6,7,8')->where('b.state',1)->where('b.addtime','egt',$nowmonth)->where('b.addtime','lt',$lastyear)->count();

        $monthSalenumStr = '';
        $monthTuinumStr = '';
        $monthHuannumStr = '';
        // 全年各月销售量、退款量、换货量
        for ($i=1; $i <= 12; $i++) {
            $monthSalenum = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id=b.id','INNER')->where('b.state',1)->where("FROM_UNIXTIME(b.addtime,'%m') = ".$i)->where('b.addtime','>',$nowyear)->count();
            $monthTuinum = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id = b.id','INNER')->where('a.th_status','in','1,2,3,4,9')->where('b.state',1)->where("FROM_UNIXTIME(b.addtime,'%m') = ".$i)->where('b.addtime','>',$nowyear)->count();
            $monthHuannum = Db::name('order_goods')->alias('a')->join('sp_order b','a.order_id = b.id','INNER')->where('a.th_status','in','5,6,7,8')->where('b.state',1)->where("FROM_UNIXTIME(b.addtime,'%m') = ".$i)->where('b.addtime','>',$nowyear)->count();
            $i < 12 ? $separator = ',' : $separator = '';
            $monthSalenumStr .= $monthSalenum.$separator;
            $monthTuinumStr .= $monthTuinum.$separator;
            $monthHuannumStr .= $monthHuannum.$separator;
        }

        // 总营业额
        $totalTurnover = Db::name('order')->where('state','1')->sum('goods_price');
        // 总会员数
        $memberNum = Db::name('member')->count();

        $this->assign('wallet_price',$wallets['price']);
        $this->assign('order_num',$order_num);
        $this->assign('month_order_num',$month_order_num);
        $this->assign('deal_lv',$deal_lv);
        $this->assign('dai_lv',$dai_lv);
        $this->assign('shou_lv',$shou_lv);
        $this->assign('month_salenum',$month_salenum);
        $this->assign('month_tuinum',$month_tuinum);
        $this->assign('month_huannum',$month_huannum);
        $this->assign('totalTurnover',$totalTurnover);
        $this->assign('memberNum',$memberNum);
        $this->assign('month',date('n',time()));
        $this->assign('year', $year);
        $this->assign('monthSalenumStr',$monthSalenumStr);
        $this->assign('monthTuinumStr',$monthTuinumStr);
        $this->assign('monthHuannumStr',$monthHuannumStr);
        $this->assign('webconfig',$this->webconfig);
        return $this->fetch();
    }

}
