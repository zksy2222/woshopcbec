<?php

namespace app\admin\controller;
use app\admin\controller\Common;
use app\admin\model\IntegralTask as IntegralTaskModel;
use app\admin\model\IntegralTaskcate;
use app\common\Lookup;

class IntegralTask extends Common{
    
    public function lst() {
        $page = input('page',1);
        $keyword = input('keyword');
        $taskModel = new IntegralTaskModel();
        $list = $taskModel ->getTaskList($keyword, 20,$page);
        $page = $list->render();
//        dump($list->currentPage());
        $data = array('list' => $list, 'page' => $page, 'keyword' => $keyword,'pnum'=>$list->currentPage());
        $this->assign($data);
        return request()->isAjax() ? $this->fetch('ajaxpage') : $this->fetch();
    }
    
    public function add() {
        if (request()->isPost()) {
            $data = input('post.');
            $result = $this->validate($data, 'IntegralTask');
            if(true !== $result){
                return json(array('status' => Lookup::isClose, 'mess' => $result));
            }
            $taskModel = new IntegralTaskModel();
            $addResult = $taskModel->save($data);
            if (!$addResult) {
                return json(array('status' => Lookup::isClose, 'mess' => '添加失败'));
            }
            return json(array('status' => Lookup::isOpen,'mess' => '添加成功'));
        }
        $taskcateModel = new IntegralTaskcate();
        $task_cate = $taskcateModel->getTaskcateSelect();
        $data = array('task_cate' => $task_cate);
        $this->assign($data);
        return $this->fetch();
    }
    
    public function edit() {
        if (request()->isPost()) {
            $data = input('post.');
            $result = $this->validate($data, 'IntegralTask');
            if(true !== $result){
                return json(array('status' => Lookup::isClose, 'mess' => $result));
            }
            $taskModel = new IntegralTaskModel();
            $updateResult = $taskModel->update($data, $data['id']);
            if (!$updateResult) {
                return json(array('status' => Lookup::isClose, 'mess' => '编辑失败'));
            }
            return json(array('status' => Lookup::isOpen,'mess' => '编辑成功'));
        }
        $id = input('id');
        $taskModel = new IntegralTaskModel();
        $info = $taskModel->getTaskInfo($id);
        $taskcateModel = new IntegralTaskcate();
        $task_cate = $taskcateModel->getTaskcateSelect();
        $data = array('info' => $info, 'task_cate' => $task_cate);
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
        $taskModel = new IntegralTaskModel();
        $delResult = $taskModel->destroy($id);
        if (!$delResult) {
            return json(array('status' => Lookup::isClose, 'mess' => '删除失败'));
        }
        return json(array('status' => Lookup::isOpen, 'mess' => '删除成功'));
    }
    
    public function updateSort(){
        $taskModel = new IntegralTaskModel();
        $data = input('post.');
        if(!empty($data['sort'])){
            foreach ($data['sort'] as $key => $v){
                $data2['sort'] = $v;
                $data2['id'] = $key;
                $taskModel->save($data2,array('id'=>$data2['id']));
            }
            $value = array('status' => Lookup::isOpen, 'mess' => '更新排序成功');
        }else{
            $value = array('status' => Lookup::isClose, 'mess' => '未修改任何排序');
        }
        return $value;
    }
    
    public function changeStatus() {
        if (!request()->isPost()) {
            return json(array('status' => Lookup::isClose, 'mess' => '请求方式错误'));
        }
        $id = input('post.id');
        if (!$id) {
            return json(array('status' => Lookup::isClose, 'mess' => '参数错误'));
        }
        $status = abs(input('post.status') - 1);
        $data = array('status' => $status);
        $taskModel = new IntegralTaskModel();
        $updateResult = $taskModel->update($data, array('id' => $id));
        if (!$updateResult) {
            return json(array('status' => Lookup::isClose, 'mess' => '修改失败'));
        }
        return json(array('status' => Lookup::isOpen, 'mess' => '修改成功'));
    }

}
