<?php
namespace app\api\controller;

class Location
{

// 百度Geocoding API
//	const API = 'http://api.map.baidu.com/geocoder/v3/';

	// 不显示周边数据
	const NO_POIS = 0;

	// 显示周边数据
	const POIS = 1;

	/**
	 * 根据地址获取国家、省份、城市及周边数据
	 * @param  String  $ak        百度ak(密钥)
	 * @param  Decimal $longitude 经度
	 * @param  Decimal $latitude  纬度
	 * @param  Int     $pois      是否显示周边数据
	 * @return Array
	 */
	public  function getAddressComponent($ak, $longitude, $latitude, $pois=self::NO_POIS){

$url = "http://api.map.baidu.com/reverse_geocoding/v3/?ak=".$ak."&output=json&coordtype=wgs84ll&location=".$latitude.",".$longitude;
		// 请求百度api
		$response = self::toCurl($url);
//		dump($response);die;
		$result = array();

		if($response){
			$result = json_decode($response, true);
		}

		return $result;

	}

	/**
	 * 使用curl调用百度Geocoding API
	 * @param  String $url    请求的地址
	 * @param  Array  $param  请求的参数
	 * @return JSON
	 */
	private static function toCurl($url, $param=array()){
		$ch = curl_init();

		if(substr($url,0,5)=='https'){
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		$response = curl_exec($ch);
		if($error=curl_error($ch)){
			return false;
		}

		curl_close($ch);

		return $response;

	}
}
