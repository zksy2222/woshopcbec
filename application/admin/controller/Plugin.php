<?php
namespace app\admin\controller;
use think\Addons as addons;
use app\admin\controller\Common;
use app\common\Lookup;
use think\Db;
use think\Config;

class Plugin extends Common{

    public function lst() {
        $keyword = input('keyword');
        $where = array();
			if ($keyword) {
				$where['name'] = array('like', "%{$keyword}%");
			}
        $plugins=db('plugin')->order('category_id,id desc')->paginate($keyword, Lookup::pageSize);
        $page = $plugins->render();
	    $addonCategory=Config::get('addon_category');
        $data = array('plugins' => $plugins,'page' => $page, 'keyword' => $keyword,'category'=>$addonCategory);
        $this->assign($data);
        return request()->isAjax() ? $this->fetch('ajaxpage') : $this->fetch();
    }

	//是否开启
	public function changeIsClose() {
		if (!request()->isPost()) {
			return json(array('status' => 0, 'mess' => '请求方式错误'));
		}
		$id = input('post.id');

		if (!$id) {
			return json(array('status' => 0, 'mess' => '参数错误'));
		}
		$status = db('plugin')->where(['id'=>$id])->value('status');
		if($status == 1){
			$isclose = input('post.isclose');
//			if($isclose ==0 ){
//				$category_id=db('plugin')->where(['id'=>$id])->value('category_id');
//				if($category_id == 1){
//					$pluginIsClose=db('plugin')->where(array('category_id'=>$category_id,'isclose'=>1))->select();
//					if($pluginIsClose){
//						return json(array('status' => 0, 'mess' => '该内插件只能同时开启一个'));
//					}
//				}
//			}
			$data = array('isclose' => abs($isclose- 1));
			$updateResult = db('plugin')->where(array('id' => $id))->update($data);
			if (!$updateResult) {
				return json(array('status' => 0, 'mess' => '修改失败'));
			}
			return json(array('status' => 1, 'mess' => '修改成功'));
		}else{
			return json(array('status' => 0, 'mess' => '插件未安装'));
		}

	}

    //安装插件
    public function install(){
        //添加数据表
        $id=input('id');
        $name=db('plugin')->where('id',$id)->value('name');
	    $class=get_addon_class($name,'',$name);
	    $addon = new $class();
	    $data= $addon->install();
	    if($data['status'] == 1 ){
	    	$data=db('plugin')->where('id',$id)->update(['status'=>1]);
	    	if($data){
	    		return json(['status'=>1,'mess'=>'插件安装成功']);
		    }else{
			    return json(['status'=>0,'mess'=>'插件安装失败']);
		    }
	    }else{
		    return json(['status'=>0,'mess'=>$data['mess']]);
	    }

    }

    //卸载插件
    public function uninstall(){
	    $id=input('id');
	    $name=db('plugin')->where('id',$id)->value('name');
	    $class=get_addon_class($name,'',$name);
	    $addon = new $class();
	    $data= $addon->uninstall();
	    if($data['status'] == 1 ){
		    $data=db('plugin')->where('id',$id)->update(['status'=>0,'isclose'=>0]);
		    if($data){
			    return json(['status'=>1,'mess'=>'插件卸载成功']);
		    }else{
			    return json(['status'=>0,'mess'=>'插件卸载失败']);
		    }
	    }else{
		    return json(['status'=>0,'mess'=>$data['mess']]);
	    }
    }
    //上传本地插件
    public function upload(){

        $file = request()->file('filedata');
        if (!$file) {
            return json(array('status' => 0, 'mess' => '插件不存在'));
        }
        $fileName=$file->getInfo()['name'];
        $fileName=substr($fileName,0,-4);
        $flugin=db('plugin')->where('name',$fileName)->find();
        if($flugin){
            return json(array('status' => 0, 'mess' => '上传的插件已存在'));
        }
        if($fileName == 'index'){
            return $fileName."模块为基础插件功能模块，请尝试使用其它名称做为插件名";
        }
        $info = $file->validate(['size'=>15671238,'ext'=>'zip'])->move(ROOT_PATH . 'addons',$fileName);

        if($info){
             //拼接上传后的文件绝对路径
            $uploadPath=ROOT_PATH.'/addons/'.$fileName.'.zip';

            //定义解压路径
            $unzipPath=ROOT_PATH.'/addons/';
            //实例化对象
            $zip =new \ZipArchive();
            //打开zip文档，如果打开失败返回提示信息
            if($zip->open($uploadPath,\ZipArchive::CREATE)!==TRUE){
                return json(array('status' => 0, 'mess' => '上传失败'));
            }else{
                //将压缩文件解压到指定的目录下
                $zip->extractTo($unzipPath);
                //关闭zip文档
                $zip->close();
                if(is_dir($unzipPath.$fileName.'/'.'static')){
                    $toPath = ROOT_PATH.'public/static/addons/'.$fileName.'/static';
                    $this->delDirAndFile($toPath,true);
                    if(!is_dir($toPath)){
                        mkdir($toPath,0777,true);
                    }
                    $this->recurse_copy($unzipPath.$fileName.'/'.'static',$toPath);
                }

                //写入插件基本信息
                $class ='\addons\\'.$fileName.'\\'.ucfirst($fileName);
                $class = new $class();
	            $list=$class->getConfig($fileName);
	            $flugin=db('plugin')->where('name',$list['display']['name'])->find();
	            if(!$flugin){
	            	$pluginInfo['name']=$list['display']['name'];
		            $pluginInfo['title']=$list['display']['title'];
		            $pluginInfo['intro']=$list['display']['intro'];
		            $pluginInfo['author']=$list['display']['author'];
		            $pluginInfo['version']=$list['display']['version'];
		            $pluginInfo['status']=$list['display']['status'];
		            $pluginInfo['category_id']=$list['display']['category_id'];
	            	$data=db('plugin')->insert($pluginInfo);
	            	if($data){
			            return json(array('status' => 1, 'mess' => '上传成功'));
		            }
	            }else{
		            return json(array('status' => 0, 'mess' => '上传失败,请检查配置文件'));
	            }
            }
        }else{
            // 上传失败获取错误信息
           return json(array('status' => 0, 'mess' => '上传失败'));
        }


       
    }

//	删除插件
	public function delete() {
		if (!request()->isAjax()) {
			return json(array('status' => Lookup::isClose, 'mess' => lang('请求方式错误')));
		}
		$id = input('id');
		if (!is_numeric($id)) {
			return json(array('status' => Lookup::isClose, 'mess' => lang('参数错误')));
		}
		$del=db('plugin')->delete($id);
		if($del){
			return json(array('status' => Lookup::isOpen, 'mess' => lang('删除成功')));
		}else{
			return json(array('status' => Lookup::isClose, 'mess' => lang('删除失败')));
		}

	}


    //对象转换数组
   public function object_array($array){
        if(is_object($array)){
        $array = (array)$array;
        }
        if(is_array($array)){
        foreach($array as $key=>$value){
            $array[$key] = $this->object_array($value);
        }
        }
        return $array;
    }

    //查询所有插件
    public function getPlugsInfo(){
        $plugsPath=ROOT_PATH."addons/";
        $plugsPath=scandir($plugsPath);
        $plugsInfo=[];
        foreach ($plugsPath as $k => $v) {
            $plugInfo=$this->getPlugInfo($v);
//             判断插件配置文件是否存在
            if($plugInfo !== null){
                $plugInfo=json_decode($plugInfo);
                array_push($plugsInfo,$plugInfo);
            }
        }
        return $plugsInfo;
    }
    

    //获取插件的配置文件
    public function getPlugInfo($plug,$type="/info.json"){

        $plugPath=ROOT_PATH."addons/".$plug.$type;
        if(is_file($plugPath)){
            $plugInfo=file_get_contents($plugPath);
//            dump($plugInfo);die;
            return $plugInfo;
        }

    }

    //删除目录及目录下的所以文件
    public function delDirAndFile($path, $delDir = FALSE) {
        if (is_array($path)) {
            foreach ($path as $subPath)
                $this->delDirAndFile($subPath, $delDir);
        }
        if (is_dir($path)) {
            $handle = opendir($path);
            if ($handle) {
                while (false !== ( $item = readdir($handle) )) {
                    if ($item != "." && $item != "..")
                        is_dir("$path/$item") ? $this->delDirAndFile("$path/$item", $delDir) : unlink("$path/$item");
                }
                closedir($handle);
                if ($delDir)
                    return rmdir($path);
            }
        } else {
            if (file_exists($path)) {
                return unlink($path);
            } else {
                return FALSE;
            }
        }
        clearstatcache();
    }

    //复制目录
    public function recurse_copy($src,$dst) {  // 原目录，复制到的目录
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->recurse_copy($src . '/' . $file,$dst . '/' . $file);
                }else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

}