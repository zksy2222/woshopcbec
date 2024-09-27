<?php

namespace addons\country\controller;
use think\addons\Controller;
use think\addons;
use app\admin\controller\Common;
use addons\country\model\Country as CountryModel;
use app\common\Lookup;
use think\Db;

class Country extends Controller{
    
    public function lst() {
        $keyword = input('keyword');
        $langModel = new CountryModel();
        $list = $langModel ->getCountryList($keyword, Lookup::pageSize);
        $page = $list->render();
        $data = array('list' => $list, 'page' => $page, 'keyword' => $keyword);
        $this->assign($data);
        return request()->isAjax() ? $this->fetch('ajaxpage') : $this->fetch();
    }
    
    public function add()
    {
	    if (request()->isPost()) {
		    $admin_id = session('admin_id');
		    $data   = input('post.');
            $result = $this->validate($data, '\addons\country\validate\Country');
		    if (true !== $result) {
			    return json(array('status' => Lookup::isClose, 'mess' => $result));
		    }
            $countryModel = new CountryModel();
            Db::startTrans();
            try {
                $data['create_time']=date('Y-m-d H:i:s');
                $countryModel->save($data);
                Db::commit();
            } catch (\Exception $e) {
                return json(array('status' => Lookup::isClose, 'mess' => '添加失败'));
            }
            return json(array('status' => Lookup::isOpen, 'mess' => '添加成功'));
	    }
	    $langRes     = db('lang')->field('id,lang_name')->select();
	    $currencyRes = db('currency')->field('id,currency_name')->select();
	    $this->assign('langRes', $langRes);
	    $this->assign('currencyRes', $currencyRes);
	    return $this->fetch();
    }


    public function edit() {
        $countryModel = new CountryModel();
        if (request()->isPost()) {
	        $admin_id = session('admin_id');
        	$data = input('post.');
            $result = $this->validate($data, '\addons\country\validate\Country');
            if(true !== $result){
                return json(array('status' => Lookup::isClose, 'mess' => $result));
            }
            Db::startTrans();
            try{
            	$countryModel->update($data, $data['id']);
                Db::commit();
            } catch (\Exception $e) {
                return json(array('status' => Lookup::isClose, 'mess' => '编辑失败'));
            }
            return json(array('status' => Lookup::isOpen,'mess' => '编辑成功'));
        }
        $id = input('id');
        $info = $countryModel->getCountryInfoById($id);
        $data = array('info' => $info);
        $this->assign($data);
	    $langRes     = db('lang')->field('id,lang_name')->select();
	    $currencyRes = db('currency')->field('id,currency_name')->select();
	    $this->assign('langRes', $langRes);
	    $this->assign('currencyRes', $currencyRes);
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
        $countryModel = new CountryModel();
        $delResult = $countryModel->destroy($id);
        if (!$delResult) {
            return json(array('status' => Lookup::isClose, 'mess' => '删除失败'));
        }
        return json(array('status' => Lookup::isOpen, 'mess' => '删除成功'));
    }

	//处理上传图片
	public function uploadify()
	{
		$admin_id = session('admin_id');
		$file = request()->file('filedata');
		if ($file) {
			$info = $file->validate(['size' => 3145728, 'ext' => 'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'country');
			if ($info) {
				$zssjpics = Db::name('huamu_zspic')->where('admin_id', $admin_id)->find();
//				if ($zssjpics && $zssjpics['img_url']) {
//					Db::name('huamu_zspic')->where('id', $zssjpics['id'])->update(array('img_url' => ''));
//					if ($zssjpics['img_url'] && file_exists('./' . $zssjpics['img_url'])) {
//						@unlink('./' . $zssjpics['img_url']);
//					}
//				}
				$date = date('Ymd', time());
				$original = 'uploads/country/' . $info->getSaveName();
				$image = \think\Image::open('./' . $original);
				$image->thumb(640, 400)->save('./' . $original, null, 90);
				if ($zssjpics) {
					Db::name('huamu_zspic')->where('id', $zssjpics['id'])->update(array('img_url' => $original));
					$zspic_id = $zssjpics['id'];
				} else {
					$zspic_id = Db::name('huamu_zspic')->insertGetId(array('admin_id' => $admin_id, 'img_url' => $original));
				}
				$picarr = array('img_url' => $original, 'pic_id' => $zspic_id);
				$value = array('status' => 1, 'path' => $picarr);
			} else {
				$value = array('status' => 0, 'msg' => $file->getError());
			}
		} else {
			$value = array('status' => 0, 'msg' => '文件不存在');
		}
		return json($value);
	}
//手动删除未保存的上传图片手机
	public function delfile(){
		if(input('post.zspic_id')){
			$admin_id = session('admin_id');
			$zspic_id = input('post.zspic_id');
			$pics = Db::name('huamu_zspic')->where('id',$zspic_id)->where('admin_id',$admin_id)->find();
			if($pics && $pics['img_url']){
				$count = Db::name('huamu_zspic')->where('id',$pics['id'])->update(array('img_url'=>''));
				if($count > 0){
					if($pics['img_url'] && file_exists('./'.$pics['img_url'])){
						@unlink('./'.$pics['img_url']);
					}
					$value = 1;
				}else{
					$value = 0;
				}
			}else{
				$value = 0;
			}
		}else{
			$value = 0;
		}
		return json($value);
	}


	public function changeDefault() {
		if (!request()->isPost()) {
			return json(array('status' => Lookup::isClose, 'mess' => '请求方式错误'));
		}
		$id = input('post.id');
		if (!$id) {
			return json(array('status' => Lookup::isClose, 'mess' => '参数错误'));
		}
		$status = input('post.status');
		if ($status) {
			return json(array('status' => Lookup::isClose, 'mess' => '已是默认国家'));
		}
		$status = abs($status - 1);
		$data = array('status' => $status);
		$CountryModel = new CountryModel();
		$checked=$CountryModel->where('id',$id)->value('checked');
		if($status == 0){
			return json(array('status' => Lookup::isClose, 'mess' => '未开启国家，不能设置成默认国家'));
		}
		Db::startTrans();
		try{
			$country_id = $CountryModel->getCountryIsDefault($status);
			if ($country_id) {
				$CountryModel->update(array('status' => Lookup::isClose), array('id' => $country_id));
			}
			$CountryModel->update($data, array('id' => $id));
			Db::commit();
		} catch(\Exception $e) {
			return json(array('status' => Lookup::isClose, 'mess' => '设置默认国家失败'));
		}
		return json(array('status' => Lookup::isOpen, 'mess' => '设置默认国家成功'));
	}

	public function changeChecked() {
		if (!request()->isPost()) {
			return json(array('status' => Lookup::isClose, 'mess' => '请求方式错误'));
		}
		$id = input('post.id');
		if (!$id) {
			return json(array('status' => Lookup::isClose, 'mess' => '参数错误'));
		}
		$checked = input('post.checked');
		$checked = abs($checked - 1);
		$data = array('checked' => $checked);
		$CountryModel = new CountryModel();
		$status=$CountryModel->where('id',$id)->value('status');
		if($status == 1){
			return json(array('status' => Lookup::isClose, 'mess' => '默认国家不能关闭'));
		}
		Db::startTrans();
		try{
			$CountryModel->update($data, array('id' => $id));
			Db::commit();
		} catch(\Exception $e) {
			return json(array('status' => Lookup::isClose, 'mess' => '开启国家失败'));
		}
		return json(array('status' => Lookup::isOpen, 'mess' => '开启国家成功'));
	}
}
