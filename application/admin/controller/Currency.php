<?php

namespace app\admin\controller;
use app\admin\controller\Common;
use app\admin\model\Currency as CurrencyModel;
use app\common\Lookup;
use think\Db;

class Currency extends Common{
    
    public function lst() {
        $keyword = input('keyword');
        $langModel = new CurrencyModel();
        $list = $langModel ->getCurrencyList($keyword, Lookup::pageSize);
        $page = $list->render();
        $data = array('list' => $list, 'page' => $page, 'keyword' => $keyword);
        $this->assign($data);
        return request()->isAjax() ? $this->fetch('ajaxpage') : $this->fetch();
    }
    
    public function add() {
        if (request()->isPost()) {
            $data = input('post.');
            $result = $this->validate($data, 'Currency');
            if(true !== $result){
                return json(array('status' => Lookup::isClose, 'mess' => $result));
            }
            $currencyModel = new CurrencyModel();
            Db::startTrans();
            try{
	            $currencyModel->save($data);
                Db::commit();
            } catch (\Exception $e) {
                return json(array('status' => Lookup::isClose, 'mess' => '添加失败'));
            }
            return json(array('status' => Lookup::isOpen,'mess' => '添加成功'));
        }
        return $this->fetch();
    }
    
    public function edit() {
        $currencyModel = new CurrencyModel();
        if (request()->isPost()) {
            $data = input('post.');
            $result = $this->validate($data, 'Currency');
            if(true !== $result){
                return json(array('status' => Lookup::isClose, 'mess' => $result));
            }
            Db::startTrans();
            try{
                $currencyModel->update($data, $data['id']);
                Db::commit();
            } catch (\Exception $e) {
                return json(array('status' => Lookup::isClose, 'mess' => '编辑失败'));
            }
            return json(array('status' => Lookup::isOpen,'mess' => '编辑成功'));
        }
        $id = input('id');
        $info = $currencyModel->getCurrencyInfoById($id);
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
        $currencyModel = new CurrencyModel();
        $delResult = $currencyModel->destroy($id);
        if (!$delResult) {
            return json(array('status' => Lookup::isClose, 'mess' => '删除失败'));
        }
        return json(array('status' => Lookup::isOpen, 'mess' => '删除成功'));
    }
}
