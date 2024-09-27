<?php
namespace app\api\controller;
use think\Controller;
use Qcloud\Cos\Client;

class Commonfun extends Common
{

    /**
     * @func 图片上传类
     * @param $file图片对象
     * @param $mkdirname 图片存放的目录
     * @param $numpic最多上传图片的数量
     * @return array|string 返回上传图片的路径
     */
    public function uploadspic($file,$mkdirname,$numpic){
        // dump($file);
        if(empty($file)){
            datamsg(400,'请上传图片');
        }
        if(is_array($file)){
            if(count($file) >= $numpic){
                datamsg(400,lang('最多上传').$numpic.lang('张图片'));
            }
            $picarr=[];
            foreach($file as $key=>$value){
                $info = $file[$key]->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $mkdirname);
                if($info){
                    $original['dz'] = '/uploads/'.$mkdirname.'/'.$info->getSaveName();
                    $original['wz'] = $this->webconfig['weburl'].'/uploads/'.$mkdirname.'/'.$info->getSaveName();
                    $picarr[]=$original;
                }else{
                    $picarr[]=0;
                }
            }
            return $picarr;
        }else{
            $info = $file->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $mkdirname);
            // dump($info);die;
            if($info){
                $original['dz'] = 'uploads/'.$mkdirname.'/'.$info->getSaveName();
                $original['wz'] = $this->webconfig['weburl'].'/uploads/'.$mkdirname.'/'.$info->getSaveName();
                $picarr[]=$original;
            }else{
                datamsg(400,'图片上传失败');
            }
            return $original;
        }
    }

}

