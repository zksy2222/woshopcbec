<?php
namespace app\api\controller;
use app\api\controller\Common;

class CeshiApi extends Common{
    public function ceshi(){
        // echo 1;die;
        if(request()->isPost()){
            $secretstr = input('post.url');
            if($secretstr){
                $clientSecret = 'yiling6670238160ravntyoneapp7926';
                $apiTokenServer = md5($secretstr.date('Y-m-d', time()).$clientSecret);
                datamsg(200,'获取成功',array('api_token_server'=>$apiTokenServer));
            }else{
                $value = array('status'=>400,'mess'=>'缺失url参数，url格式为：api/CeshiApi/ceshi','data'=>array('status'=>400));
            }
            
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
            
        }
        return json($value); 
    }
    

}