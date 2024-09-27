<?php
namespace app\mobile\controller;
use think\Controller;
use think\Db;

class Download extends Common
{
    public function index()
    {
        return $this->fetch();
        
    }
	
	public function downset(){
		
		$android = $this->getConfigInfo(84);
		$androiddownload = $android['value'];
		
		$ios = $this->getConfigInfo(85);
		$iosdownload = $ios['value'];
		
		if(empty($android)){
		    $value = array('code'=>0,'msg'=>'安卓下载地址不存在');
		}else if(empty($ios)){
		    $value = array('code'=>0,'msg'=>'安卓下载地址不存在');
		}else{
			$value = array('code'=>1,'msg'=>'查询成功','data'=>array('androidurl'=>$androiddownload,'iosurl'=>$iosdownload));
		}
		
		return json($value);
	}

}