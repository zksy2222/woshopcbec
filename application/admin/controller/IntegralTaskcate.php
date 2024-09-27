<?php

namespace app\admin\controller;
use app\admin\controller\Common;
use app\admin\model\IntegralTaskcate as IntegralTaskcateModel;
use app\common\Lookup;

class IntegralTaskcate extends Common{
    
    public function lst() {
        $keyword = input('keyword');
        $taskCateModel = new IntegralTaskcateModel();
        $list = $taskCateModel ->getTaskCateList($keyword, Lookup::pageSize);
        $page = $list->render();
        $data = array('list' => $list, 'page' => $page, 'keyword' => $keyword);
        $this->assign($data);
        return request()->isAjax() ? $this->fetch('ajaxpage') : $this->fetch();
    }
    
    public function add() {
        if (request()->isPost()) {
            $data = input('post.');
            $result = $this->validate($data, 'IntegralTaskcate');
            if(true !== $result){
                return json(array('status' => Lookup::isClose, 'mess' => $result));
            }
            $taskCateModel = new IntegralTaskcateModel();
            $addResult = $taskCateModel->save($data);
            if (!$addResult) {
                return json(array('status' => Lookup::isClose, 'mess' => '添加失败'));
            }
            return json(array('status' => Lookup::isOpen,'mess' => '添加成功'));
        }
        return $this->fetch();
    }
    
    public function edit() {
        if (request()->isPost()) {
            $data = input('post.');
            $result = $this->validate($data, 'IntegralTaskcate');
            if(true !== $result){
                return json(array('status' => Lookup::isClose, 'mess' => $result));
            }
            $taskCateModel = new IntegralTaskcateModel();
            $updateResult = $taskCateModel->update($data, $data['id']);
            if (!$updateResult) {
                return json(array('status' => Lookup::isClose, 'mess' => '编辑失败'));
            }
            return json(array('status' => Lookup::isOpen,'mess' => '编辑成功'));
        }
        $id = input('id');
        $taskCateModel = new IntegralTaskcateModel();
        $info = $taskCateModel->getTaskCateInfo($id);
        $data = array('info' => $info);
        $this->assign($data);
        return $this->fetch();
    }
    
    public function delete() {
        if (!request()->isAjax()) {
            return json(array('status' => Lookup::isClose, 'mess' => '请求方式错误'));
        }
        $id = input('id');
        if (!is_numeric($id)) {
            return json(array('status' => Lookup::isClose, 'mess' => '参数错误'));
        }
        $taskCateModel = new IntegralTaskcateModel();
        $delResult = $taskCateModel->destroy($id);
        if (!$delResult) {
            return json(array('status' => Lookup::isClose, 'mess' => '删除失败'));
        }
        return json(array('status' => Lookup::isOpen, 'mess' => '删除成功'));
    }
    
}
