<?php

namespace app\admin\controller;
use app\admin\controller\Common;
use app\admin\model\LangTranslate as LangTranslateModel;
use app\common\Lookup;
use think\Db;

class LangTranslate extends Common{
    
    public function lst() {
        $keyword = input('keyword');
        $translateModel = new LangTranslateModel();
	    $langList=$translateModel->getLangList();
        $list = $translateModel->getLangKeyList($keyword, Lookup::pageSize)
                               ->each(function($item) use($translateModel,$langList){

	    	foreach ($langList as $k=>$v){
			    $item['values'][$v['id']] = $translateModel->getLangValueByKeyId($item['id'],$v['id']);
		    }
		    return $item;
	    });
        $page = $list->render();

        $data = array('list' => $list, 'lang_list' => $langList, 'page' => $page, 'keyword' => $keyword);
        $this->assign($data);
        return request()->isAjax() ? $this->fetch('ajaxpage') : $this->fetch();
    }
    
    public function add() {
        if (request()->isPost()) {
            $data = input('post.');
            $result = $this->validate($data, 'LangTranslate');
            if(true !== $result){
                return json(array('status' => Lookup::isClose, 'mess' => $result));
            }
            if (empty(array_filter($data['value_name']))) {
                return json(array('status' => Lookup::isClose, 'mess' => lang('至少填写一种语言')));
            }
            $translateModel = new LangTranslateModel();
            Db::startTrans();
            try{
            	$key_data = array(
            		'key_name' => $data['key_name'],
		            'remark' => $data['remark'],
		            'create_time' => date('Y-m-d H:i:s')
	            );
            	$key_id=$translateModel->insertKeyGetId($key_data);
                foreach ($data['lang_id'] as $key => $v) {
                    $value_data = array('lang_id' => $v, 'value_name' => $data['value_name'][$key],'lang_key_id'=>$key_id);
                    $translateModel->insertValueData($value_data);
                    //更新lang语言包
                    $this->writeLang($data['key_name'], $data['value_name'][$key], $data['lang_code'][$key]);
                }
                Db::commit();
            } catch (\Exception $e) {
                return json(array('status' => Lookup::isClose, 'mess' => lang('添加失败')));
            }
            return json(array('status' => Lookup::isOpen,'mess' => lang('添加成功')));
        }
        $translateModel = new LangTranslateModel();
        $lang_list = $translateModel->getLangList();
        $data = array('lang_list' => $lang_list);
        $this->assign($data);
        return $this->fetch();
    }
    
    public function edit() {
        $translateModel = new LangTranslateModel();
        if (request()->isPost()) {
            $data = input('post.');
            $result = $this->validate($data, 'LangTranslate');
            if(true !== $result){
                return json(array('status' => Lookup::isClose, 'mess' => $result));
            }
            if (empty(array_filter($data['value_name']))) {
                return json(array('status' => Lookup::isClose, 'mess' => lang('至少填写一种语言')));
            }

            Db::startTrans();
            try{
                $translateModel->updateLangKey($data['id'],['key_name'=>$data['key_name']]);
                foreach ($data['value_id'] as $k => $v) {
                    if(!empty($v)){
                    	$translateModel->updateLangValue($v,['value_name'=>$data['value_name'][$k]]);
                    }else{
	                    $value_data = array('lang_id' => $data['lang_id'][$k], 'value_name' => $data['value_name'][$k],'lang_key_id'=>$data['id']);
	                    $translateModel->insertValueData($value_data);
                    }
                    //更新lang语言包
//                    $this->writeLang($data['key_name'], $data['value_name'][$key], $data['lang_code'][$key]);
                }
                Db::commit();
            } catch (\Exception $e) {
                return json(array('status' => Lookup::isClose, 'mess' => lang('编辑失败')));
            }
            return json(array('status' => Lookup::isOpen,'mess' => lang('编辑成功')));
        }
        $id = input('id');
        $data = $translateModel->getLangTranslateInfo($id);
        $this->assign($data);
        return $this->fetch();
    }
    
    public function delete() {
        if (!request()->isAjax()) {
            return json(array('status' => Lookup::isClose, 'mess' => lang('请求方式错误')));
        }
        $id = input('id');
        if (!is_numeric($id)) {
            return json(array('status' => Lookup::isClose, 'mess' => lang('参数错误')));
        }
        $translateModel = new LangTranslateModel();
        Db::startTrans();
        try{
	        $translateModel->destroyLangValue($id);
	        $translateModel->destroyLangKey($id);
            Db::commit();
        } catch (\Exception $e) {
            return json(array('status' => Lookup::isClose, 'mess' => lang('删除失败')));
        }
        return json(array('status' => Lookup::isOpen, 'mess' => lang('删除成功')));
    }
    
    public function createLangPackage() {
        $translateModel = new LangTranslateModel();
        $key_list = $translateModel->getLangKeyData();
        if (!$key_list) {
            return json(array('status' => Lookup::isClose, 'mess' => lang('暂无翻译语言')));
        }
        $lang = array();
        foreach ($key_list as $item) {
            $value_list = $translateModel->getLangValueData($item['id']);
            foreach ($value_list as $v) {
                $lang[$v['lang_code']][$item['key_name']] = $v['value_name'];
            }
        }
        $langList = $translateModel->getLangList();
        foreach ($langList as $v) {
            $lang_file = ROOT_PATH . "thinkphp/lang/{$v['lang_code']}.php";
            $code = "<?php \r\nreturn " . var_export($lang[$v['lang_code']], true) . ";";
            $handle = fopen($lang_file, 'w');
            fwrite($handle, $code);
            fclose($handle);
        }
        return json(array('status' => Lookup::isOpen, 'mess' => lang('语言包生成成功')));
    }
    
    public function import() {
        $lang_id = input('post.lang_id');
        $lang_code = input('post.lang_code');
        if (!$lang_id) {
            return json(array('status' => Lookup::isClose, 'mess' => lang('请选择导入的语言')));
        }
        if (!$lang_code) {
            return json(array('status' => Lookup::isClose, 'mess' => lang('参数错误')));
        }
        $file = request()->file('filedata');
        if (!$file) {
            return json(array('status' => 0, 'mess' => '文件不存在'));
        }
        $info = $file->validate(['size'=>3145728,'ext'=>'xls,xlsx'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'lang_excel');
        if (!$info) {
            return json(array('status' => 0, 'mess' => '导入失败'));
        }
        $getSaveName = str_replace("\\","/",$info->getSaveName());
        $filepath = './uploads/lang_excel/' . $getSaveName;
        $path = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'lang_excel/' . $info->getSaveName();
        vendor("phpexcel.PHPExcel");
        if ($info->getExtension() =='xlsx') {
            $objReader = new \PHPExcel_Reader_Excel2007();
        } else if ($info->getExtension() =='xls') {
            $objReader = new \PHPExcel_Reader_Excel5();
        }
        $objPHPExcel = $objReader->load($path, $encode='utf-8');
        @unlink($filepath);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $k = 0;
        $fail_num = $succ_num = $update_num = 0;
//	    $langIds=db('lang')->select();
        $lang_data = array();
        $translateModel = new LangTranslateModel();
        Db::startTrans();
        try{
            for($i = 2; $i <= $highestRow; $i++) {
                $lang_key = $objPHPExcel->getActiveSheet()->getCell("A" . $i)->getValue();
	            $langKeyRemark = $objPHPExcel->getActiveSheet()->getCell("C" . $i)->getValue();
                $lang_value = $objPHPExcel->getActiveSheet()->getCell("B" . $i)->getValue();
                if (!$lang_key || !$lang_value) {
                    continue;
                }
                $keyInfo = $translateModel->getLangKeyByName($lang_key);
                if (!$keyInfo) {
	                $key_id=$translateModel->insertKeyGetId(['key_name'=>$lang_key,'create_time'=> date('Y-m-d H:i:s'),'remark'=>$langKeyRemark]);
//	                if(is_array($langIds)){
//		                foreach ($langIds as $k=>$v){
//		                	if($v['id'] != $lang_id){
//				                $translateModel->insertValueData(array('lang_id'=>$v['id'],'value_name'=>'','lang_key_id'=>$key_id));
//			                }else{
//				                $translateModel->insertValueData(array('lang_id'=>$lang_id,'value_name'=>$lang_value,'lang_key_id'=>$key_id));
//			                }
//
//	                    }
//	                }else{
//		                $translateModel->insertValueData(array('lang_id'=>$lang_id,'value_name'=>$lang_value,'lang_key_id'=>$key_id));
//	                }
	                $translateModel->insertValueData(array('lang_id'=>$lang_id,'value_name'=>$lang_value,'lang_key_id'=>$key_id));

                } else {
                    $valueInfo = $translateModel->getLangValueInfo($keyInfo['id'], $lang_id);
                    if (!$valueInfo) {
                        $translateModel->insertValueData(array('lang_id' => $lang_id, 'value_name' => $lang_value,'lang_key_id'=>$keyInfo['id']));
                    } else {
                        $fail_num++;
                        if($translateModel->updateLangValue($valueInfo['id'], array('value_name' => $lang_value))){
                            $update_num++;
                        }
                        continue;
                    }
                }
                $lang_data[$lang_key] = $lang_value;
                $succ_num++;
                $k++;
            }
            if (!empty($lang_data)) {
                $lang_file = ROOT_PATH . "thinkphp/lang/{$lang_code}.php";
                $code = "<?php \r\nreturn " . var_export($lang_data, true) . ";";
                $handle = fopen($lang_file, 'w');
                fwrite($handle, $code);
                fclose($handle);
            }

            Db::commit();
        } catch (\Exception $e) {
            return json(array('status' => Lookup::isClose, 'mess' => lang('导入失败')));
        }
        $lang_mess = lang('语言翻译导入成功', ['total_row' => $highestRow - 1, 'succ_num' => $succ_num, 'fail_num' => $fail_num, 'update_num' => $update_num]);
        return json(array('status' => Lookup::isOpen, 'mess' => $lang_mess));
    }
    
    private function writeLang($key_name, $value_name, $lang_code) {
        if (!$value_name) {
            return false;
        }
        $lang_file = ROOT_PATH . "thinkphp/lang/{$lang_code}.php";
        $lang = file_exists($lang_file) ? require($lang_file) : [];
        $lang[$key_name] = $value_name;
        $code = "<?php \r\nreturn " . var_export($lang, true) . ";";
        $handle = fopen($lang_file, 'w');
        fwrite($handle, $code);
        fclose($handle);
    }
    
}
