<?php
namespace addons\collection;	// 注意命名空间规范

use think\Db;
use think\Addons;
/**
 * 插件测试
 * @author byron sampson
 */
class Collection extends Addons	// 需继承think\addons\Addons类
{
	// 该插件的基础信息
    public $info = [

    ];

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
	    $id=input('id');
	    $name=db('plugin')->where('id',$id)->value('name');
	    $configRes=$this->getConfig();
	    try{
		    if($configRes['display'][$name]){
		    	$pri_name=($configRes['display'][$name]['pri_name']);
		    	$find=db('privilege')->where('pri_name',$pri_name)->find();
		    	if(empty($find)){
				    $privilege=db('privilege')->insertGetId($configRes['display'][$name]);
				    if($privilege){
				    	$one=$configRes['display']['one'];
				    	if($one){
						    $one['pid']=$privilege;
						    db('privilege')->insert($one);
					    }
					    $two=$configRes['display']['two'];
				    	if($two){
						    $two['pid']=$privilege;
						    db('privilege')->insert($two);
					    }
					    return ['status'=>1,'mess'=>'插件已存在'];
				    }
			    }else{
				    return ['status'=>0];
			    }
		    }else{
			    return ['status'=>0];
		    }
	    }catch (\Exception $e) {
            return ['status'=>0,'mess'=>$e->getMessage()];
	    }
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
	    $id=input('id');
	    $name=db('plugin')->where('id',$id)->value('name');

	    try {
			db('privilege')->where(['mname'=>$name,'type'=>1])->delete();
		    return ['status'=>1];
	    }catch (\Exception $e) {
            return ['status'=>0,'mess'=>$e->getMessage()];
	    }

    }

    /**
     * 实现的langhook钩子方法
     * @return mixed
     */
    public function langhook($param)
    {
//        dump(1);die;
		// 调用钩子时候的参数信息
        print_r($param);
		// 当前插件的配置信息，配置信息存在当前目录的config.php文件中，见下方
        print_r($this->getConfig());
		// 可以返回模板，模板文件默认读取的为插件目录中的文件。模板名不能为空！
        return $this->fetch('info');
    }


	//获取插件的配置文件
	public function getPlugInfo($plug,$type="/info.json"){

		$plugPath=ROOT_PATH."addons/".$plug.$type;
		// dump($plugPath);die;
		if(is_file($plugPath)){
			$plugInfo=file_get_contents($plugPath);
			return $plugInfo;
		}
	}
}