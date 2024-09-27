<?php
/**
 * @Description: 直播Model
 * @Author : 梧桐 <2487937004@qq.com>
 * @Copyright : 武汉一一零七科技有限公司 All rights reserved.
 */
namespace app\api\model;
use think\Db;
use think\Model;

class Lang extends Model
{
    /*
     * 获取语言
     * @param $num int 记录条数
     * */
    public function getLangList(){
	    $langData=db('lang')->select();
	    $langList = [];
	    foreach ($langData as $k => $v){
	        $langList[$k]['code'] = $v['lang_code_front'];
	        $langList[$k]['language'] = $v['remark'];
        }
    	return $langList;
    }

    public function  getValues($langId){
    	$values=db('lang_value')->where('lang_id',$langId)->select();
    	return $values;
    }

    public function getKeyName($langKeyId){
    	$keyName=db('lang_key')->where('id',$langKeyId)->value('key_name');
    	return $keyName;
    }

    public  function  getTabbarKeyName($where){
    	$tabbarKeyId=db('lang_key')->where('key_name','in',$where)->select();
    	return $tabbarKeyId;
    }

    public function  getValueName($id,$lang_id){
    	$tabbarValueName=db('lang_value')->where(['lang_key_id'=>$id,'lang_id'=>$lang_id])->value('value_name');
    	return $tabbarValueName;
    }
}