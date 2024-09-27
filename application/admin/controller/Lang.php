<?php

namespace app\admin\controller;
use app\admin\controller\Common;
use app\admin\model\Lang as LangModel;
use app\admin\model\LangTranslate;
use app\common\Lookup;
use think\Db;

class Lang extends Common{
    
    public function lst() {
        $keyword = input('keyword');
        $langModel = new LangModel();
        $list = $langModel ->getLangList($keyword, Lookup::pageSize);
        $page = $list->render();
        $data = array('list' => $list, 'page' => $page, 'keyword' => $keyword);
        $this->assign($data);
        return request()->isAjax() ? $this->fetch('ajaxpage') : $this->fetch();
    }
    
    public function add() {
        if (request()->isPost()) {
            $data = input('post.');
            $result = $this->validate($data, 'Lang');
            if(true !== $result){
                return json(array('status' => Lookup::isClose, 'mess' => $result));
            }
            $langModel = new LangModel();
            Db::startTrans();
            try{
                if ($data['is_default']) {
                    $lang_id = $langModel->getLangIsDefault($data['is_default']);
                    if ($lang_id) {
                        $langModel->update(array('is_default' => Lookup::isClose), array('id' => $lang_id));
                    }
                }
                $langModel->save($data);
                Db::commit();
            } catch (\Exception $e) {
                return json(array('status' => Lookup::isClose, 'mess' => '添加失败'));
            }
            return json(array('status' => Lookup::isOpen,'mess' => '添加成功'));
        }
        return $this->fetch();
    }
    
    public function edit() {
        $langModel = new LangModel();
        if (request()->isPost()) {
            $data = input('post.');
            $result = $this->validate($data, 'Lang');
            if(true !== $result){
                return json(array('status' => Lookup::isClose, 'mess' => $result));
            }
            Db::startTrans();
            try{
                if ($data['is_default']) {
                    $lang_id = $langModel->getLangIsDefault($data['is_default']);
                    if ($lang_id) {
                        $langModel->update(array('is_default' => Lookup::isClose), array('id' => $lang_id));
                    }
                }
                $langModel->update($data, $data['id']);
                Db::commit();
            } catch (\Exception $e) {
                return json(array('status' => Lookup::isClose, 'mess' => '编辑失败'));
            }
            return json(array('status' => Lookup::isOpen,'mess' => '编辑成功'));
        }
        $id = input('id');
        $info = $langModel->getLangInfoById($id);
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
        if (LangTranslate::getLangValueByLangId($id)) {
            return json(array('status' => Lookup::isClose, 'mess' => '当前语言存在翻译字段'));
        }
        $langModel = new LangModel();
        $delResult = $langModel->destroy($id);
        if (!$delResult) {
            return json(array('status' => Lookup::isClose, 'mess' => '删除失败'));
        }
        return json(array('status' => Lookup::isOpen, 'mess' => '删除成功'));
    }
    
    public function changeDefault() {
        if (!request()->isPost()) {
            return json(array('status' => Lookup::isClose, 'mess' => '请求方式错误'));
        }
        $id = input('post.id');
        if (!$id) {
            return json(array('status' => Lookup::isClose, 'mess' => '参数错误'));
        }
        $isdefault = input('post.is_default');
        if ($isdefault) {
            return json(array('status' => Lookup::isClose, 'mess' => '已是默认语言'));
        }
        $is_default = abs($isdefault - 1);
        $data = array('is_default' => $is_default);
        $langModel = new LangModel();
        Db::startTrans();
        try{
            $lang_id = $langModel->getLangIsDefault($is_default);
            if ($lang_id) {
                $langModel->update(array('is_default' => Lookup::isClose), array('id' => $lang_id));
            }
            $langModel->update($data, array('id' => $id));
            Db::commit();
        } catch(\Exception $e) {
            return json(array('status' => Lookup::isClose, 'mess' => '设置默认语言失败'));
        }
        return json(array('status' => Lookup::isOpen, 'mess' => '设置默认语言成功'));
    }
    
//    public function changeLang() {
//        $lang = input('lang');
//        cookie('think_var', $lang);
//        return json(array('status' => Lookup::isOpen, 'mess' => '语言切换成功'));
//    }
}
