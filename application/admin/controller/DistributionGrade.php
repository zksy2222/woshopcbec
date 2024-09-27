<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use app\admin\model\DistributionGrade as distribGradeModel;
use app\common\Lookup;
use app\admin\model\Goods;

class DistributionGrade extends Common{
    
    public function lst() {
        $distribGradeModel = new distribGradeModel();
        $goodsModel = new Goods();
        $list = $distribGradeModel->getGradeList(Lookup::pageSize);
        foreach ($list as $key => $v) {
            if ($v['goods_id']) {
                $goods = $goodsModel->getGoodsInfoByGoodsId($v['goods_id']);
                $list[$key]['goods_name'] = $goods['goods_name'];
                $list[$key]['thumb_url'] = $this->webconfig['weburl'] . $goods['thumb_url'];
                $list[$key]['shop_price'] = $goods['shop_price'];
            }
        }
        $page = $list->render();
        $data = array(
            'list' => $list,
            'page' => $page
        );
        $this->assign($data);
        return request()->isAjax() ? $this->fetch('ajaxpage') : $this->fetch();
    }
    
    public function add() {
        if (request()->isPost()) {
            $data = input('post.');
            $result = $this->validate($data, 'DistributionGrade');
            if(true !== $result){
                return json(array('status' => 0, 'mess' => $result));
            }
            $regex = '/^\+?[1-9][0-9]*$/';
            if ($data['upgrade'] == Lookup::upgradeByUserCount) {
                if (!preg_match($regex, $data['user_count'])) {
                    return json(array('status' => 0, 'mess' => '人数请填写正整数'));
                } 
                $data['consume_amount'] = NULL;
                $data['goods_id'] = NULL;
            } elseif ($data['upgrade'] == Lookup::upgradeByConsumeAmount) {
                if (!preg_match($regex, $data['consume_amount'])) {
                    return json(array('status' => 0, 'mess' => '消费金额请填写正整数'));
                }
                $data['user_count'] = NULL;
                $data['goods_id'] = NULL;
            } elseif ($data['upgrade'] == Lookup::upgradeByGoodsId) {
                if (!$data['goods_id']) {
                    return json(array('status' => 0, 'mess' => '请选择商品'));
                }
                $data['user_count'] = NULL;
                $data['consume_amount'] = NULL;
            } else {
                $data['user_count'] = NULL;
                $data['consume_amount'] = NULL;
                $data['goods_id'] = NULL;
            }
            
            $distribGradeModel = new distribGradeModel();
            $addResult = $distribGradeModel->save($data);
            if (!$addResult) {
                return json(array('status' => 0, 'mess' => '添加失败'));
            }
            return json(array('status' => 1, 'mess' => '添加成功'));
        }
        return $this->fetch();
    }
    
    public function edit() {
        $distribGradeModel = new distribGradeModel();
        if (request()->isPost()) {
            $data = input('post.');
            $result = $this->validate($data, 'DistributionGrade');
            if(true !== $result){
                return json(array('status' => 0,'mess' => $result));
            }
            
            $regex = '/^\+?[1-9][0-9]*$/';
            if ($data['upgrade'] == Lookup::upgradeByUserCount) {
                if (!preg_match($regex, $data['user_count'])) {
                    return json(array('status' => 0, 'mess' => '人数请填写正整数'));
                } 
                $data['consume_amount'] = NULL;
                $data['goods_id'] = NULL;
            } elseif ($data['upgrade'] == Lookup::upgradeByConsumeAmount) {
                if (!preg_match($regex, $data['consume_amount'])) {
                    return json(array('status' => 0, 'mess' => '消费金额请填写正整数'));
                }
                $data['user_count'] = NULL;
                $data['goods_id'] = NULL;
            } elseif ($data['upgrade'] == Lookup::upgradeByGoodsId) {
                if (!$data['goods_id']) {
                    return json(array('status' => 0, 'mess' => '请选择商品'));
                }
                $data['user_count'] = NULL;
                $data['consume_amount'] = NULL;
            } else {
                $data['user_count'] = NULL;
                $data['consume_amount'] = NULL;
                $data['goods_id'] = NULL;
            }
            
            $updateResult = $distribGradeModel->update($data, array('id' => $data['id']));
            if (!$updateResult) {
                return json(array('status' => 0,'mess' => '编辑失败'));
            }
            return json(array('status' => 1,'mess' => '编辑成功'));
        }
        $id = input('id');
        $info = $distribGradeModel->getGradeInfoById($id);
        $goodsModel = new Goods();
        $goods_info = $goodsModel->getGoodsInfoByGoodsId($info['goods_id']);
        $this->assign('info', $info);
        $this->assign('goods_info', $goods_info);
        return $this->fetch();
    }
    
    public function delete() {
        if (!request()->isAjax()) {
            return json(array('status' => 0, 'mess' => '请求方式错误'));
        }
        $id = input('id');
        if (!$id) {
            return json(array('status' => 0, 'mess' => '参数错误'));
        }
        $distribGradeModel = new distribGradeModel();
        $delResult = $distribGradeModel->destroy($id);
        if (!$delResult) {
            return json(array('status' => 0, 'mess' => '删除失败'));
        }
        return json(array('status' => 1, 'mess' => '删除成功'));
    }
    
    public function changeStatus() {
        if (!request()->isPost()) {
            return json(array('status' => 0, 'mess' => '请求方式错误'));
        }
        $id = input('post.id');
        if (!$id) {
            return json(array('status' => 0, 'mess' => '参数错误'));
        }
        $status = abs(input('post.status') - 1);
        $distribGradeModel = new distribGradeModel();
        $data = array('status' => $status);
        $updateResult = $distribGradeModel->update($data, array('id' => $id));
        if (!$updateResult) {
            return json(array('status' => 0, 'mess' => '修改失败'));
        }
        return json(array('status' => 1, 'mess' => '修改成功'));
    }
}