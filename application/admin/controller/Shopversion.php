<?php

namespace app\admin\controller;

use app\admin\controller\Common;

use think\Db;




class Shopversion extends Common{

    

    public function lst(){

        $list = Db::name('shop_version')->order('create_time DESC')->select();
        foreach($list as $k=>$v){
            switch ($v['update_type']){
                case 'forcibly':
                    $list[$k]['update_type'] = '强制更新';
                    break;
                case 'solicit':
                    $list[$k]['update_type'] = '弹窗确认更新';
                    break;
                case 'silent':
                    $list[$k]['update_type'] = '静默更新';
                    break;
            }
        }

        $this->assign('list',$list);// 赋值数据集

        return $this->fetch();

    }



    public function add(){

        if(request()->isPost()) {

            $data = input('post.');

            if(empty($data['version_code'])){

                $versions = array('status'=>0,'mess'=>'请上传版本号');

                return json($versions);

            }
            if(empty($data['version_name'])){

                $versions = array('status'=>0,'mess'=>'请填写版本描述');

                return json($versions);

            }

//            if(empty($data['urls'])){
//
//                $urls = array('status'=>0,'mess'=>'请上传安装包');
//
//                return json($urls);
//
//            }

            unset($data['fileselect']);

            $data['create_time']=time();

            $result = db('shop_version')->insert($data);

            if($result){

                $value = array('status'=>1,'mess'=>'增加成功');

            }else{

                $value = array('status'=>0,'mess'=>'增加失败');

            }

            return json($value);

        }else{

            return $this->fetch();

        }

    }

    //编辑
    public function edit(){
        if(request()->isPost()) {
            $data = input('post.');
            if(!$data['id']){
                datamsg(0,'缺少参数，编辑失败');
            }
            $versionInfos = Db::name('shop_version')->where('id', $data['id'])->field('id')->find();
            if (!$versionInfos) {
                datamsg(0, '编辑失败');
            }
            $count = Db::name('shop_version')->update($data, array('id' => $data['id']));
            if ($count === false) {
                datamsg(0, '编辑失败');
            }
            ys_admin_logs('编辑app版本控制', 'shop_version', $data['id']);
            datamsg(1, '编辑成功');
        }else {
            $id = input('id');
            if(!$id){
                datamsg(0,'缺少参数，编辑失败');
            }
            $version = Db::name('shop_version')->find($id);
            if(!$version){
                datamsg(0,'找不到相关信息');
            }
            $this->assign('version', $version);
            return $this->fetch();
        }

    }




    public function delete(){

        $id = input('id');

        if(!empty($id)){

            $result = db('shop_version')->delete($id);

            if($result){

                $value = array('status'=>1,'mess'=>'删除成功');

            }else{

                $value = array('status'=>0,'mess'=>'删除失败');

            }

        }

        return $value;

    }











    //上传apk的包

//处理上传图片

    public function uploadify(){

        $admin_id = session('admin_id');

        $file = request()->file('filedata');

        if($file){

            $info = $file->validate(['size'=>52428800,'ext'=>'apk,png'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apkversion');

            if($info){

                $getSaveName = str_replace("\\","/",$info->getSaveName());

                $original = 'uploads/apkversion/'.$getSaveName;

                $value = array('status'=>1,'path'=>$this->webconfig['weburl'].'/'.'uploads/android.jpg','filepath'=>$original);

            }else{

                $value = array('status'=>0,'msg'=>$file->getError());

            }

        }else{

            $value = array('status'=>0,'msg'=>'文件不存在');

        }

        return json($value);

    }

    // 删除文件
    public function delFile(){
        $path = input('post.urls');
        if(!$path){
            return array('status'=>0,'msg'=>'文件路径不存在，删除失败！');
        }
        $filePath = ROOT_PATH ."/public/". $path;

        if (file_exists($filePath)) {
            $res = unlink($filePath);//删除文件
            if($res){
                return array('status'=>1,'msg'=>'删除成功！');
            }else{
                return array('status'=>1,'msg'=>'删除失败！');
            }
        }else{
            return array('status'=>0,'msg'=>'文件路径错误，删除失败！');
        }

    }







}