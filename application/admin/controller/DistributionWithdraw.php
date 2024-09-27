<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use app\admin\model\DistributionWithdraw as distributionWithdrawModel;
use app\admin\model\DistributionUser;
use app\admin\model\BankCard;
use app\common\Lookup;
use think\Db;

class DistributionWithdraw extends Common {
    public function lst(){
        $pnum = input('page', 1);
        $filter = input('filter');
        if (!in_array($filter, [1,2,3,4,5,10])) {
            $filter = 10;
        }
        $withdrawModel = new distributionWithdrawModel();
        $list = $withdrawModel->getWithdrawList($filter);
        $page = $list->render();
        $this->assign(array(
            'list'=>$list,
            'page'=>$page,
            'pnum'=>$pnum,
            'filter'=>$filter
        ));
        return request()->isAjax() ? $this->fetch('ajaxpage') : $this->fetch();
    }
    
    public function checked() {
        $withdrawModel = new distributionWithdrawModel();
        if(request()->isPost()){
            $id = input('post.id');
            $status = input('post.status');
            $amount = input('post.amount');
            $distrib_id = input('post.distrib_id');
            if (!$id) {
                return json(array('status' => 0, 'mess' => '参数错误，设置失败'));
            }
            if (!in_array($status, [1,2])) {
                return json(array('status' => 0, 'mess' => '参数错误，设置失败'));
            }
            if (!is_numeric($amount)) {
                return json(array('status' => 0, 'mess' => '参数错误，设置失败'));
            }
            if (!$distrib_id) {
                return json(array('status' => 0, 'mess' => '参数错误，设置失败'));
            }
            Db::startTrans();
            try{
                $data = array('status' => $status);
                $withdrawModel->update($data, array('id' => $id));
                $distribModel = new DistributionUser();
                if ($status == Lookup::passCheckWithdraw) { //审核通过，待打款金额自增
                    $distribModel->withdrawSetInc($distrib_id, $amount, 'waitpay_amount');
                } elseif ($status == Lookup::rfuseCheckWithdraw) {  //审核拒绝，可提现佣金恢复
                    $distribModel->withdrawSetInc($distrib_id, $amount, 'withdraw_amount');
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                return json(array('status' => 0, 'mess' => '设置失败'));
            }
            return json(array('status' => 1, 'mess' => '设置成功'));
        }
        
        $id = input('tx_id');
        $filter = input('filter');
        if (!$id || !$filter) {
            $this->error('缺少参数');
        }
        if(!in_array($filter, array(1,2,3,4,5,10))){
            $this->error('参数错误');
        }
        $where = array('id' => $id, 'status' => Lookup::waitCheckWithdraw, 'pay_status' => Lookup::waitPayStatusWithdraw);
        $withdraw_info = $withdrawModel->getWithdrawById($where);
        if (!$withdraw_info) {
            $this->error('提现信息错误');
        }
        $bank_id = $withdraw_info['bank_id'];
        $bankModel = new BankCard();
        $bank_info = $bankModel->getBankCardInfo($bank_id);
        if (input('s')) {
            $this->assign('search',input('s'));
        }
        $this->assign('pnum', input('page'));
        $this->assign('filter', input('filter'));
        $this->assign('withdraw_info', $withdraw_info);
        $this->assign('bank_info', $bank_info);
        return $this->fetch();
    }
    
    public function dakuan() {
        $withdrawModel = new distributionWithdrawModel();
        if (request()->isPost()) {
            $id = input('post.id');
            $pay_status = input('post.pay_status');
            $amount = input('post.amount');
            $distrib_id = input('post.distrib_id');
            if (!$id) {
                return json(array('status' => 0, 'mess' => '参数错误，设置失败'));
            }
            if (!in_array($pay_status, [1,2])) {
                return json(array('status' => 0, 'mess' => '参数错误，设置失败'));
            }
            if (!is_numeric($amount)) {
                return json(array('status' => 0, 'mess' => '参数错误，设置失败'));
            }
            if (!$distrib_id) {
                return json(array('status' => 0, 'mess' => '参数错误，设置失败'));
            }
            Db::startTrans();
            try{
                $data = array('pay_status' => $pay_status);
                $distribModel = new DistributionUser();
                $distribModel->withdrawSetDec($id, $amount, 'waitpay_amount');
                if ($pay_status == Lookup::failPayStatusWithdraw) { //打款失败，可提现金额恢复，待打款金额自减
                    $remarks = input('post.remarks');
                    if (!$remarks) {
                        return json(array('status' => 0, 'mess' => '请填写打款失败原因'));
                    }
                    $data['remarks'] = $remarks;
                    $distribModel->withdrawSetInc($distrib_id, $amount, 'withdraw_amount');
                    
                } elseif ($pay_status == Lookup::successPayStatusWithdraw) {
                    $distribModel->withdrawSetInc($distrib_id, $amount, 'success_amount');
                }
                $withdrawModel->update($data, array('id' => $id));
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                return json(array('status' => 0, 'mess' => '设置失败'));
            }
            return json(array('status' => 1, 'mess' => '设置成功'));
            
        }
        
        $id = input('tx_id');
        $filter = input('filter');
        if (!$id || !$filter) {
            $this->error('缺少参数');
        }
        if (!in_array($filter, [1,2,3,4,5,10])) {
            $this->error('参数错误');
        }
        $where = array('id' => $id, 'status' => Lookup::passCheckWithdraw, 'pay_status' => Lookup::waitPayStatusWithdraw);
        $withdraw_info = $withdrawModel->getWithdrawById($where);
        if (!$withdraw_info) {
            $this->error('提现信息错误');
        }
        $bank_id = $withdraw_info['bank_id'];
        $bankModel = new BankCard();
        $bank_info = $bankModel->getBankCardInfo($bank_id);
        if (input('s')) {
            $this->assign('search',input('s'));
        }
        $this->assign('pnum', input('page'));
        $this->assign('filter', input('filter'));
        $this->assign('withdraw_info', $withdraw_info);
        $this->assign('bank_info', $bank_info);
        return $this->fetch();
    }
    
    public function info() {
        $id = input('tx_id');
        if (!$id) {
            $this->error('缺少参数');
        }
        $withdrawModel = new distributionWithdrawModel();
        $where = array('id' => $id);
        $withdraw_info = $withdrawModel->getWithdrawById($where);
        if (!$withdraw_info) {
            $this->error('提现信息错误');
        }
        $bank_id = $withdraw_info['bank_id'];
        $bankModel = new BankCard();
        $bank_info = $bankModel->getBankCardInfo($bank_id);
        if (input('s')) {
            $this->assign('search',input('s'));
        }
        $this->assign('pnum', input('page'));
        $this->assign('filter', input('filter'));
        $this->assign('withdraw_info', $withdraw_info);
        $this->assign('bank_info', $bank_info);
        return $this->fetch();
    }
    
    public function search(){
        
        $where = array();
        $shtx_zt = input('post.tx_zt', 0);
        
        if(!empty(input('post.keyword'))){
            $where['a.tx_number'] = input('post.keyword');
            $this->assign('keyword', input('post.keyword'));
        }
        
        if(!empty(input('post.starttime')) && !empty(input('post.endtime'))){
            $where['a.create_time'] = array(array('egt',input('post.starttime')), array('lt',input('post.endtime')));
        }
        
        if(!empty(input('post.starttime')) && empty(input('post.endtime'))){
            $where['a.create_time'] = array('egt',input('post.starttime'));
        }
        
        if(empty(input('post.starttime')) && !empty(input('post.endtime'))){
            $where['a.create_time'] = array('lt',input('post.endtime'));
        }
        
        $withdrawModel = new distributionWithdrawModel();
        $list = $withdrawModel->getWithdrawList($shtx_zt, $where);
        $page = $list->render();
        
        if(!empty(input('post.starttime'))){
            $this->assign('starttime',input('post.starttime'));
        }
        
        if(!empty(input('post.endtime'))){
            $this->assign('endtime',input('post.endtime'));
        }
        
        $search = 1;
        $filter = 10;
        $pnum = input('page', 1);
        
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('filter',$filter);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('tx_zt',$shtx_zt);
        return request()->isAjax() ? $this->fetch('ajaxpage') : $this->fetch('lst');
    }

    /***
     * 获取商家待提现余额信息
     */
    public function shoplist(){
        $today = date('d',time());
        $wallet = Db::name('shop_wallet')->alias('a')->field('a.*,b.shop_name,b.settlement_date,b.service_rate')->join('sp_shops b','a.shop_id = b.id','INNER')->select();
        if($wallet){
            foreach($wallet as $key=>$value){
                //获取结算日期，如果当前日子大于结算日期，结算日期加30
                if($today > $value['settlement_date']){
                    $wallet[$key]['sort'] = $value['settlement_date'] + 30;
                    $wallet[$key]['year'] = date('Y-m',strtotime('+1 month'));
                }else{
                    $wallet[$key]['sort'] = $value['settlement_date'];
                    $wallet[$key]['year'] = date('Y-m',time());
                }
            }
        }
        $sort_arr = [];
        foreach ($wallet as $key => $value) {
            $sort_arr[] = $value['sort'];
        }

        array_multisort($sort_arr, SORT_ASC, $wallet);
        $this->assign(array(
            'list'=>$wallet,
        ));
        return $this->fetch();
    }

}
?>

