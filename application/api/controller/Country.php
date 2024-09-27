<?php
namespace app\api\controller;
use app\api\controller\Common;
use think\Db;
use app\api\model\Country as CountryModel;
use app\api\controller\Location as location;
class Country extends Common
{
	//获取默认国家列表信息及对应的语言和货币
	public function getCountryList(){
		$tokenRes = $this->checkToken(0);
		if($tokenRes['status'] == 400){
			datamsg(400,$tokenRes['mess'],$tokenRes['data']);
		}
		$plugins=db('plugin')->where('name','country')->find();
		if(!$plugins){
			datamsg(400,'未安装插件',array('status'=>400));
		}
		$webconfig = $this->webconfig;
		$countryModel = new CountryModel();
		$countryLst=$countryModel->getCoutryLst();
		if($countryLst){
			foreach ($countryLst as $k =>$v){
				$countryLst[$k]['country_img'] = url_format($v['country_img'],$webconfig['weburl']);
			}
			$countryLst=$this->array_val_chunk($countryLst);
			datamsg(200,'获取默认国家列表成功',$countryLst);
		}else{
			datamsg(400,'获取默认国家列表失败',array('status'=>400));
		}
	}
	//通过经纬度获取国家信息
    public function getCountryDefault(){
	    $tokenRes = $this->checkToken(0);
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }
        $plugins=db('plugin')->where('name','country')->find();
        if(!$plugins){
	        datamsg(400,'未安装插件',array('status'=>400));
        }

        $location=new location();
	    $webconfig = $this->webconfig;
        $ak=$webconfig['map_key'];
	    $data=input('post.');
	    $longitude=$data['longitude'];
		$latitude =$data['latitude'];
        $country_code_iso=$location->getAddressComponent($ak,$longitude,$latitude);
        $country_code=$country_code_iso['result']['addressComponent']['country_code_iso'];
        if(empty($country_code)){
            $country_code="CHN";
        }
	    $countryModel = new CountryModel();
        $countryList=$countryModel->getCountryDefault($country_code);
        if(empty($countryList)){
            $countryList=$countryModel->getCountryStatus();
        }
        $countryRes['id']= $countryList['id'];
        $countryRes['country_cname']= $countryList['country_cname'];
        $countryRes['country_ename']= $countryList['country_ename'];
        $countryRes['country_bname']= $countryList['country_bname'];
        $countryRes['country_code']= $countryList['country_code'];
        $countryRes['country_img']= $countryList['country_img'];
	    $countryRes['lang_id']= $countryList['lang_id'];
	    $countryRes['currency']['currency_id']= $countryList['currency_id'];
	    $countryRes['currency']['currency_name']= $countryList['currency_name'];
	    $countryRes['currency']['currency_symbol']= $countryList['currency_symbol'];
	    $countryRes['currency']['currency_code']= $countryList['currency_code'];
        $countryRes['currency']['currency_exchange']= $countryList['currency_exchange'];
	    if($countryRes){
	        $countryRes['country_img'] = $webconfig['weburl'].'/'.$countryRes['country_img'];
		    datamsg(200,'获取默认国家列表成功',$countryRes);
        }else{
		    datamsg(400,'未安装插件',array('status'=>400));
        }
    }

    //国家列表改写数组
    public function array_val_chunk($array)
    {
        $result = array();
        $ar2    = [];
        foreach ($array as $key => $value) {
            foreach ($array as $k => $val) {
                if ($value['country_initials'] == $val['country_initials']) {
                    $ar2['letter']=$val['country_initials'];
                    $ar2['list'][] = $val;
                }
            }
            $result[$value['country_initials']]= $ar2;
            $ar2                                = [];
        }
        return $result;
    }
}
