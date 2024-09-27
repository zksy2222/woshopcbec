<?php
namespace app\api\controller;
use app\api\model\Common as CommonModel;
use think\Db;
use app\api\model\FindCate;

class Find extends Common {


    public function findcate() {
        $res = $this->checkToken(0);
        if($res['status'] == 400){
            return json($res);
        }
        
        $findCate = new FindCate();
        $cate_list = $findCate->getFindCateList();
        $data = array(
            'cate_list' => $cate_list
        );
        
        datamsg(200, '获取成功', set_lang($data));
    }




    /**
     * @func 图片上传
     */
    public function uploadspic(){
        if(request()->isPost()) {
            $file = request()->file('file');
            $common = new Commonfun();
            $picarr = $common->uploadspic($file, 'find_pic', 9);
            
            $data['code']=200;
            $data['data']['src']=$picarr;
            $data['msg']='获取成功';
            echo json_encode($data);die();
        }else{
            $data['code']=400;
            $data['msg']='获取失败';
            echo json_encode($data);die();
        }
    }


    /**
     * @func 后台聊天接口
     */
    public function huploadspic(){
        if(request()->isPost()) {
            $file = request()->file('file');
            $common = new Commonfun();
            $picarr = $common->uploadspic($file, 'find_pic', 9);

            $srcs = $picarr['wz'];
            $data['code']=0;
            $data['data']['src']=$srcs;
            $data['msg']='获取成功';
            echo json_encode($data);die();
        }else{
            $data['code']=400;
            $data['msg']='获取失败';
            echo json_encode($data);die();
        }
    }

}