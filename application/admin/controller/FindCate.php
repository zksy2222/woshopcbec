<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use app\admin\model\FindCate as findCateModel;

class FindCate extends Common{
    
    public function lst(){
        $findCateModel = new findCateModel();
        $list = $findCateModel->getFindCateList();
        $this->assign('list', $list);
        return $this->fetch();
    }
    
    public function add() {
        if(request()->isPost()) {
            $data = input('post.');
            $result = $this->validate($data, 'FindCate');
            if(true !== $result){
                return json(array('status' => 0,'mess' => $result));
            }
            $findCateModel = new findCateModel();
            $addResult = $findCateModel->save($data);
            if (!$addResult) {
                return json(array('status' => 0,'mess' => '添加失败'));
            }
            return json(array('status' => 1,'mess' => '添加成功'));
        }
        return $this->fetch();
    }
    
    public function edit() {
        $findCateModel = new findCateModel();
        if(request()->isPost()) {
            $data = input('post.');
            $result = $this->validate($data, 'FindCate');
            if(true !== $result){
                return json(array('status' => 0,'mess' => $result));
            }
            $updateResult = $findCateModel->update($data, array('id' => $data['id']));
            if (!$updateResult) {
                return json(array('status' => 0,'mess' => '编辑失败'));
            }
            return json(array('status' => 1,'mess' => '编辑成功'));
        }
        $id = input('id');
        $info = $findCateModel->getFindCateById($id);
        $this->assign('info', $info);
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
        $findCateModel = new findCateModel();
        $delResult = $findCateModel->destroy($id);
        if (!$delResult) {
            return json(array('status' => 0, 'mess' => '删除失败'));
        }
        return json(array('status' => 1, 'mess' => '删除成功'));
    }
    
    public function changeShow() {
        if (!request()->isPost()) {
            return json(array('status' => 0, 'mess' => '请求方式错误'));
        }
        $id = input('post.id');
        if (!$id) {
            return json(array('status' => 0, 'mess' => '参数错误'));
        }
        $is_show = abs(input('post.is_show') - 1);
        $findCateModel = new findCateModel();
        $data = array('is_show' => $is_show);
        $updateResult = $findCateModel->update($data, array('id' => $id));
        if (!$updateResult) {
            return json(array('status' => 0, 'mess' => '修改失败'));
        }
        return json(array('status' => 1, 'mess' => '修改成功'));
    }


}
?>