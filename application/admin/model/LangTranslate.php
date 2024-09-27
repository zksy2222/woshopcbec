<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class LangTranslate extends Model{
    
    public function getLangList() {
        return Db::name('lang')->field('id,lang_name,lang_code')->select();
    }
    
//    public function getLangTranslateList($keyword, $pageSize) {
//        $where = array();
//        if ($keyword) {
//            $where['key_name'] = array('like', "%{$keyword}%");
//        }
//        $list = Db::name('lang_key')->where($where)->order('id desc')->paginate($pageSize, false, ['query' => request()->param()]);
//        return $list;
//    }
	public function getLangKeyList($keyword,$pageSize) {
		$where = array();
			if ($keyword) {
				$where['key_name'] = array('like', "%{$keyword}%");
			}
			$list = Db::name('lang_key')->where($where)->order('id desc')->paginate($pageSize, false, ['query' => request()->param()]);


		return $list;
	}

//	publice function  getLangTranslateList($keyword, $pageSize){
//    	$where=arrary();
//    	if($keyword){
//		    $where['key_name'] = array('like', "%{$keyword}%");
//	    }
//    	$list=db('lang_value')->where($where)->order('id DESC')->paginate($pageSize, false, ['query' => request()->param()]);
//        return $list;
//}

    public function getLangTranslateInfo($id) {
    	$langList=db('lang')->select();
        $keyInfo = Db::name('lang_key')->where('id', $id)->find();
        foreach ($langList as $k=>$v){
				$valuelist[$k]=db('lang_value')->alias('a')->field('a.id,a.lang_key_id,a.value_name,b.lang_name,b.id lang_id')->join('lang b','a.lang_id = b.id','LEFT')->where(['lang_key_id'=>$id,'lang_id'=>$v['id']])->find();
				if(empty($valuelist[$k])){
					$valuelist[$k]['lang_name']=$v['lang_name'];
					$valuelist[$k]['lang_id']=$v['id'];
					$valuelist[$k]['lang_key_id']=$keyInfo['id'];
				}
        }
        return array(
            'key_info' => $keyInfo,
            'value_list' => $valuelist,
        );
    }
    
    public function getLangValueById($id) {
        return Db::name('lang_value')
                ->alias('a')
                ->field('b.id,a.value_name')
                ->join('sp_lang b', 'a.lang_id = b.id', 'left')
                ->where('lang_key_id', $id)->select();
    }

	public function getLangValueByKeyId($id,$langId) {
		return Db::name('lang_value')
		         ->field('id,value_name')
		         ->where(['lang_key_id'=> $id,'lang_id'=>$langId])->find();
	}

    
    public static function getLangValueByLangId($lang_id) {
        return Db::name('lang_value')->where('lang_id', $lang_id)->value('id');
    }
    
    public function getLangKeyData() {
        return Db::name('lang_key')->select();
    }
    
    public function getLangKeyByName($key_name) {
        return Db::name('lang_key')->where('key_name', $key_name)->find();
    }
    
    public function getLangValueInfo($lang_key_id, $lang_id) {
        return Db::name('lang_value')->where('lang_key_id', $lang_key_id)->where('lang_id', $lang_id)->find();
    }
    
    public function getLangValueData($id) {
        return Db::name('lang_value')
                ->alias('a')
                ->field('a.id,a.lang_id,a.value_name,b.lang_code')
                ->join('sp_lang b', 'a.lang_id = b.id', 'left')
                ->where('a.lang_key_id', 'in', $id)->select();
    }

	public function insertKeyGetId($data) {
		return Db::name('lang_key')->insertGetId($data);
	}

    public function insertValueData($data) {
        return Db::name('lang_value')->insert($data);
    }
    

    
    public function updateLangValue($id, $data) {
        return Db::name('lang_value')->where('id',$id)->update($data);
    }
    
    public function updateLangKey($id, $data) {
        return Db::name('lang_key')->where('id', $id)->update($data);
    }
    
    public function destroyLangKey($id) {
        return Db::name('lang_key')->delete($id);
    }
    
    public function destroyLangValue($id) {
        return Db::name('lang_value')->where('lang_key_id', $id)->delete();
    }
    
    public function getCreateTimeAttr($time) {
        return $time;
    }
}
