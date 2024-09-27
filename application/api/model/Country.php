<?php
/**
 * @Description: 直播Model
 * @Author : 梧桐 <2487937004@qq.com>
 * @Copyright : 武汉一一零七科技有限公司 All rights reserved.
 */
namespace app\api\model;
use think\Db;
use think\Model;

class Country extends Model
{
    /*
     * 获取国家
     * @param $num int 记录条数
     * */

	public  function getCoutryLst(){
		$countryRes=db('country')->alias('a')->field('a.id,a.country_cname,a.country_ename,a.country_initials,a.country_bname,a.country_code,a.country_img,a.lang_id,a.currency_id,b.currency_name,b.currency_symbol,b.currency_code,b.currency_exchange,c.lang_code')->join('currency b','a.currency_id = b.id','LEFT')->join('lang c','a.lang_id = c.id','LEFT')->where('a.checked','1')->order('a.country_initials')->select();
		return $countryRes;
	}


    public function getCountryDefault($country_code){
	    $countrys=db('country')->alias('a')->field('a.id,a.country_cname,a.country_ename,a.country_bname,a.country_code,a.country_img,a.lang_id,a.currency_id,b.currency_name,b.currency_symbol,b.currency_code,b.currency_exchange')->join('currency b','a.currency_id = b.id','LEFT')->where('a.country_code',$country_code)->where('a.checked','1')->find();
    	return $countrys;
    }

    public function getCountryStatus(){
	    $countrys=db('country')->alias('a')->field('a.id,a.country_cname,a.country_ename,a.country_bname,a.country_code,a.country_img,a.lang_id,a.currency_id,b.currency_name,b.currency_symbol,b.currency_code,b.currency_exchange')->join('currency b','a.currency_id = b.id','LEFT')->where('a.status','1')->find();
	    return $countrys;
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