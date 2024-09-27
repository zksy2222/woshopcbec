<?php

namespace app\api\controller;
use app\api\controller\Common;
use app\common\Lookup;
use app\api\model\NavMenu as NavMenuModel;

class NavMenu extends Common{
    
    public function getMenuList() {
        $res = $this->checkToken(0);
        if ($res['status'] == 400) {
            return json($res);
        }
        $clientType = input('post.client_type','','trim');
        if($clientType ==  'pc'){
            $navId = Lookup::pcHomePageNavId;
        }else{
            $navId = Lookup::mobileHomePageNavId;
        }

        $menuModel = new NavMenuModel();
        $menuList = $menuModel->getMenuList($navId, Lookup::isShow);
        foreach ($menuList as $k => $v) {
            $menuList[$k]['menu_name'] = lang($menuList[$k]['menu_name']);
            $menuList[$k]['image_path'] = url_format($v['image_path'],$this->webconfig['weburl'],'?imageMogr2/thumbnail/120x');
            $menuList[$k]['menu_url_type'] = 'navigateTo'; // 常规页面链接
            if(!empty($v['menu_url'])){
                if(strpos($v['menu_url'],'tabBar') > 0){
                    $menuList[$k]['menu_url_type'] = 'tab'; // tabbar页面链接
                }
            }
        }
        $data = array('menu_list' => $menuList);
        datamsg(200, 'success', $data);
    }

}
