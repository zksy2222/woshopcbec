<?php

namespace app\admin\controller;
use app\admin\controller\Common;
use app\admin\model\DistributionUser as distribUserModel;
use app\admin\model\DistributionCommissonDetail as distributionCommissonDetailModel;
use app\common\Lookup;
use app\common\model\DistributionConfig;
use app\admin\model\DistributionGrade;
use think\Db;
use think\Model;

class DistributionOrder extends Common{

    public function lst() {
        $distributionCommissonDetailModel = new distributionCommissonDetailModel();
        $distribUserId = input('user_id');
        $filter = input('filter');
        $keyword = input('keyword');
        if(!$filter || !in_array($filter, array(1,2,10))){
            $filter = 10;
        }
        if($distribUserId){
            $distribUserId = $distribUserId;
            $keyword = $distributionCommissonDetailModel->getRalName($distribUserId);
        }

        $orderList = $distributionCommissonDetailModel->getAmountLst($distribUserId,$filter,$keyword);
        $page = $orderList->render();

        $data = array(
            'page'      =>  $page,
            'orders'    =>  $orderList,
            'filter'    =>  $filter,
            'keyword'   =>  $keyword
        );
        $this->assign($data);
        return request()->isAjax() ? $this->fetch('ajaxpage') : $this->fetch();
    }

}
