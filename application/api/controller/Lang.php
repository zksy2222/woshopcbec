<?php
namespace app\api\controller;
use app\api\controller\Common;
use think\Db;
use app\api\model\Lang as LangModel;
class Lang extends Common
{
    //获取语言种类
    public function getLangList(){
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }
        // 验证token
        $res = $this->checkToken(0);
        if($res['status'] == 400){  return json($res);  }

        $plugins=db('plugin')->where('name','lang')->find();
        if(!$plugins){
            datamsg(400,'未安装插件',array('status'=>400));
        }

       hook('getLangListHook');
    }

    //获取语言翻译类容
    public function getLangKeyValue(){
        // 验证token
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }

        $plugins=db('plugin')->where('name','lang')->find();
        if(!$plugins){
            datamsg(400,'未安装插件',array('status'=>400));
        }

        $langModel = new LangModel();
        $lang_id=(input('post.lang_id'));
        if(!$lang_id){
            datamsg(400,'语言参数错误',array('status'=>400));
        }
        $langValue= $langModel->getValues($lang_id);
        foreach ($langValue as $k => $v) {
            $langKey= $langModel->getKeyName($v['lang_key_id']);
            $langKeyValue[$langKey]=$v['value_name'];
        }
        if (!$langKeyValue) {
            datamsg(400,'获取语言信息失败',array('status'=>400));
        }
        datamsg(200,'获取语言信息成功',$langKeyValue);
    }
    //获取语言tabbar
    public function getLangTabbar(){
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }

        $plugins=db('plugin')->where('name','lang')->find();
        if(!$plugins){
            datamsg(400,'未安装插件',array('status'=>400));
        }

        $langModel = new LangModel();
        $lang_id=(input('post.lang_id'));
        $tabbarKeys=['首页','分类','发现','购物车','个人中心'];
        $langTabbarKeyId=$langModel->getTabbarKeyName($tabbarKeys);
        foreach ($langTabbarKeyId as $k => $v) {
            $langTabbar[$v['key_name']]= $langModel->getValueName($v['id'],$lang_id);
        }
        if (!$langTabbar) {
            datamsg(400,'获取语言tabbar失败',array('status'=>400));
        }
        datamsg(200,'获取语言tabbar成功',$langTabbar);
    }
}
