<?php

namespace addons\country\model;
use think\Model;

class Country extends Model{
    
    public function getCountryList($keyword, $pageSize) {
        $where = array();
        if ($keyword) {
            $where['country_cname|country_code'] = array('like', "%{$keyword}%");
        }
        return Country::alias('a')->field('a.*,b.lang_name,c.currency_name')->join('lang b','a.lang_id = b.id','LEFT')->join('currency c','a.currency_id = c.id','LEFT')->where($where)->order('id desc')->paginate($pageSize);
    }
    
    public function getCountryInfoById($id) {
        return country::where('id', $id)->find();
    }

	public function getCountryIsDefault($status) {
		return country::where('status', $status)->value('id');
	}

    public function getCreateTimeAttr($time) {
        return $time;
    }
}
