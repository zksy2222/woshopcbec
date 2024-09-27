<?php

namespace app\admin\controller;
use app\admin\controller\Common;
use app\admin\model\NewPublish as NewPublishModel;
use app\admin\model\Shops;
use app\admin\model\Goods;
use app\admin\model\Video;
use app\common\Lookup;

class NewPublish extends Common{
    
    public $newModel;
    
    public function _initialize() {
        parent::_initialize();
        $this->newModel = new NewPublishModel();
    }
    
    public function lst() {
        $keyword = input('keyword');
        $list = $this->newModel->getNewPublishList($keyword, Lookup::pageSize);
        $goodsModel = new Goods();
        foreach ($list as $key => $item) {
            $goods_list = $goodsModel->getGoodsInfo($item['goods_id']);
            $goods_name_str = '';
            foreach ($goods_list as $v) {
                 $goods_name_str .= '【' . $v['goods_name'] . '】<br>';
            }
            $list[$key]['goods_name'] = $goods_name_str;
        }
        $page = $list->render();
        $data = array(
            'list' => $list,
            'page' => $page,
            'keyword' => $keyword,
        );
        $this->assign($data);
        return request()->isAjax() ? $this->fetch('ajaxpage') : $this->fetch();
    }
    
    public function add() {
        if (request()->isPost()) {
            $data = input('post.');
            $result = $this->validate($data, 'NewPublish');
            if(true !== $result){
                return json(array('status' => Lookup::isClose, 'mess' => $result));
            }
            $count = count($data['goods_id']) + count($data['video_id']);
            if ($count > 9) {
                return json(array('status' => Lookup::isClose, 'mess' => '商品和视频共计不超过9个'));
            }
            $data['goods_id'] = implode(',', $data['goods_id']);
            $addResult = $this->newModel->save($data);
            if (!$addResult) {
                return json(array('status' => Lookup::isClose, 'mess' => '添加失败'));
            }
            return json(array('status' => Lookup::isOpen,'mess' => '添加成功'));
        }
        $shopsModel = new Shops();
        $shop_list = $shopsModel->getShopList();
        $data = array('shop_list' => $shop_list);
        $this->assign($data);
        return $this->fetch();
    }
    
    public function edit() {
        if (request()->isPost()) {
            $data = input('post.');
            $result = $this->validate($data, 'NewPublish');
            if(true !== $result){
                return json(array('status' => Lookup::isClose, 'mess' => $result));
            }
            $count = count($data['goods_id']) + count($data['video_id']);
            if ($count > 9) {
                return json(array('status' => Lookup::isClose, 'mess' => '商品和视频共计不超过9个'));
            }
            $data['goods_id'] = implode(',', $data['goods_id']);
            $data['video_id'] = implode(',', $data['video_id']);
            $addResult = $this->newModel->update($data, $data['id']);
            if (!$addResult) {
                return json(array('status' => Lookup::isClose, 'mess' => '编辑失败'));
            }
            return json(array('status' => Lookup::isOpen,'mess' => '编辑成功'));
        }
        $id = input('id');
        $info = $this->newModel->getNewPublishInfo($id);
        $shopsModel = new Shops();
        $shop_list = $shopsModel->getShopList();
        $goodsModel = new Goods();
        $goods_list = $goodsModel->getGoodsListByIds($info['goods_id']);

        $data = array(
            'info' => $info,
            'shop_list' => $shop_list,
            'goods_list' => $goods_list,
        );
        $this->assign($data);
        return $this->fetch();
    }
    
    public function delete() {
        if (!request()->isAjax()) {
            return json(array('status' => Lookup::isClose, 'mess' => '请求方式错误'));
        }
        $id = input('id');
        if (!$id) {
            return json(array('status' => Lookup::isClose, 'mess' => '参数错误'));
        }
        $delResult = $this->newModel->destroy($id);
        if (!$delResult) {
            return json(array('status' => Lookup::isClose, 'mess' => '删除失败'));
        }
        return json(array('status' => Lookup::isOpen, 'mess' => '删除成功'));
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
        $data = array('status' => $status);
        $updateResult = $this->newModel->update($data, array('id' => $id));
        if (!$updateResult) {
            return json(array('status' => 0, 'mess' => '修改失败'));
        }
        return json(array('status' => 1, 'mess' => '修改成功'));
    }
    
}
