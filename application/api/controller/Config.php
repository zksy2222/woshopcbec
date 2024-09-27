<?php
namespace app\api\controller;
use app\api\model\Config as ConfigModel;


class Config extends Common
{
    /*
     * 获取过审配置信息
     */
    public function getShowConfig(){
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess']);
        }
        $configModel = new ConfigModel();
        $examineInfo = $configModel->getExamine();
        datamsg(200,'获取配置信息',$examineInfo);
    }

}