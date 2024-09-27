<?php

namespace app\admin\controller;

use app\admin\controller\Common;
use app\admin\model\Dispatch as DispatchModel;
use app\admin\model\Logistics as LogisticsModel;
use think\Db;

class Dispatch extends Common
{
    public function lst()
    {
        $shopId = session('shop_id');
        $dispatchModel = new DispatchModel();
        $list = $dispatchModel->where('shop_id',$shopId)->order('sort DESC,id DESC')->paginate(5);
        $page = $list->render();
        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }
        $this->assign(array(
            'pnum' => $pnum,
            'page' => $page,
            'list' => $list
        ));
        if (request()->isAjax()) {
            return $this->fetch('ajaxpage');
        } else {
            return $this->fetch('lst');
        }
    }


    public function add()
    {
        $this->post();
        return $this->fetch('post');
    }

    public function edit()
    {
        $this->post();
        $random = get_random_string(16);
        $this->assign('random',$random);
        return $this->fetch('post');
    }

    public function post()
    {
        $id = input('param.id');
        $shopId = session('shop_id');
        $page = input('param.page');
        $search = input('param.s');
        $areas = get_areas();
        $dispatchModel = new DispatchModel();
        $dispatch = $dispatchModel->where('shop_id',$shopId)->where('id',$id)->find();
        if (!empty($dispatch)) {
            $dispatch_areas = unserialize($dispatch['areas']);
            $dispatch_carriers = unserialize($dispatch['carriers']);
            $dispatch_nodispatchareas = unserialize($dispatch['no_dispatch_areas']);
            $dispatch_nodispatchareas_code = unserialize($dispatch['no_dispatch_areas_code']);
        }
        $logisticsModel = new LogisticsModel();
        $express_list = $logisticsModel->select();

        $this->assign('areas', $areas);
        $this->assign('dispatch', $dispatch);
        $this->assign('dispatch_areas', $dispatch_areas);
        $this->assign('dispatch_carriers', $dispatch_carriers);
        $this->assign('dispatch_nodispatchareas', $dispatch_nodispatchareas);
        $this->assign('dispatch_nodispatchareas_code', $dispatch_nodispatchareas_code);
        $this->assign('pnum', $page);
        $this->assign('search', $search);
        $this->assign('express_list', $express_list);

        if (request()->isPost()) {
            $postData = input('post.');
            $areas = array();
            $randoms = $postData['random'];

            if (is_array($randoms)) {
                foreach ($randoms as $random) {
                    $citys = trim($postData['citys'][$random]);

                    if (empty($citys)) {
                        continue;
                    }

                    if ($postData['firstnum'][$random] < 1) {
                        $postData['firstnum'][$random] = 1;
                    }

                    if ($postData['secondnum'][$random] < 1) {
                        $postData['secondnum'][$random] = 1;
                    }

                    $areaData = array(
                        'citys'            => $postData['citys'][$random],
                        'citys_code'       => $postData['citys_code'][$random],
                        'first_price'      => $postData['firstprice'][$random],
                        'first_weight'     => $postData['firstweight'][$random],
                        'second_price'     => $postData['secondprice'][$random],
                        'second_weight'    => $postData['secondweight'][$random],
                        'first_num_price'  => $postData['firstnumprice'][$random],
                        'first_num'        => $postData['firstnum'][$random],
                        'second_num_price' => $postData['secondnumprice'][$random],
                        'second_num'       => $postData['secondnum'][$random],
                        'free_price'       => $postData['freeprice'][$random]
                    );
                    if ($postData['calculatetype'] == 0) {
                        $scene = 'Dispatch.check_weight_price';
                    } else {
                        $scene = 'Dispatch.check_num_price';
                    }
                    $validate = $this->validate($areaData, $scene);
                    if ($validate !== true) {
                        datamsg(0, $validate);
                    }

                    $areas[] = $areaData;
                }
            }

            $postData['default_firstnum'] = trim($postData['default_firstnum']);

            if ($postData['default_firstnum'] < 1) {
                $postData['default_firstnum'] = 1;
            }

            $postData['default_secondnum'] = trim($postData['default_secondnum']);

            if ($postData['default_secondnum'] < 1) {
                $postData['default_secondnum'] = 1;
            }

            $data = array(
                'shop_id'                => $shopId,
                'sort'                   => intval($postData['sort']),
                'dispatch_type'          => intval($postData['dispatchtype']),
                'is_default'             => intval($postData['isdefault']),
                'dispatch_name'          => trim($postData['dispatchname']),
                'express'                => trim($postData['express']),
                'calculate_type'         => trim($postData['calculatetype']),
                'first_price'            => trim($postData['default_firstprice']),
                'first_weight'           => trim($postData['default_firstweight']),
                'second_price'           => trim($postData['default_secondprice']),
                'second_weight'          => trim($postData['default_secondweight']),
                'first_num_price'        => trim($postData['default_firstnumprice']),
                'first_num'              => $postData['default_firstnum'],
                'second_num_price'       => trim($postData['default_secondnumprice']),
                'second_num'             => $postData['default_secondnum'],
                'free_price'             => $postData['default_freeprice'],
                'areas'                  => serialize($areas),
                'no_dispatch_areas'      => serialize($postData['nodispatchareas']),
                'no_dispatch_areas_code' => serialize($postData['nodispatchareas_code']),
                'is_dispatch_area'       => intval($postData['isdispatcharea']),
                'enabled'                => intval($postData['enabled'])
            );

            if ($data['calculate_type'] == 0) {
                $scene = 'Dispatch.check_weight_price_default';
            } else {
                $scene = 'Dispatch.check_num_price_default';
            }
            $validate = $this->validate($data, $scene);
            if ($validate !== true) {
                datamsg(0, $validate);
            }

            if ($data['is_default']){
                $dispatchModel->update(['is_default'=>0],['shop_id'=>$shopId]);
            }

            if (!empty($id)) {
                $data['id'] = $id;
                $save = $dispatchModel->update($data);
                if ($save) {
                    ys_admin_logs('编辑配送方式', 'dispatch/add', $id);
                    datamsg(1, '保存成功');
                } else {
                    datamsg(0, '保存失败');
                }
            } else {
                $getDispatchId = $dispatchModel->insertGetId($data);
                if ($getDispatchId) {
                    ys_admin_logs('新增配送方式', 'dispatch/add', $getDispatchId);
                    datamsg(1, '保存成功');
                } else {
                    datamsg(0, '保存失败');
                }
            }

        }
    }

    public function delete(){
        if(input('post.id')){
            $id = array_filter(explode(',', input('post.id')));
        }else{
            $id = input('id');
        }
        if(!empty($id)){
            $count = DispatchModel::destroy($id);
            if($count > 0){
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'编辑失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return json($value);
    }

    public function gaibian(){
        $shopId = session('shop_id');
        $id      = input('post.id');
        $name    = input('post.name');
        $value   = input('post.value');
        if(!$id || !$name){
            return 0;
        }
        $data[$name]     = $value;
        $where['id']      = $id;
        $where['shop_id'] = $shopId;
        $dispatchModel = new DispatchModel();
        $result = $dispatchModel->update($data,$where);

        if ($name == 'is_default' && $value == 1) {
            $defaultWhere['shop_id'] = $shopId;
            $defaultWhere['id'] = ['neq',$id];
            $defaultData['is_default'] = 0;
            $dispatchModel->update($defaultData,$defaultWhere);
        }
        return 1;
    }

    // 批量启用、禁用
    public function enable(){
        $shopId = session('shop_id');
        $id      = input('post.id');
        $enable  = input('post.enable');
        $msg = $enable == 1 ? '启用' : '禁用';

        if(!$id){
            return 0;
        }
        $where['id']      = ['in',$id];
        $where['shop_id'] = $shopId;
        $data['enabled'] = $enable;
        $dispatchModel = new DispatchModel();
        $result = $dispatchModel->update($data,$where);
        if($result){
            datamsg(1,$msg.'成功');
        }else{
            datamsg(0,$msg.'失败');
        }
    }



    //排序
    public function order(){
        $dispatchModel = new DispatchModel();
        $data = input('post.');
        if(!empty($data['sort'])){
            foreach ($data['sort'] as $key => $v){
                $data2['sort'] = $v;
                $data2['id'] = $key;
                $dispatchModel->save($data2,array('id'=>$data2['id']));
            }
            $value = array('status'=>1,'mess'=>'更新排序成功');
            ys_admin_logs('更新配送方式排序','logistics',1);
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return json($value);
    }

    public function tpl()
    {
        $random = get_random_string(16);
        ob_start();
        $this->assign('random', $random);
        $contents = $this->fetch();
        ob_clean();
        exit(json_encode(array('random' => $random, 'html' => $contents)));
        //        $this->assign('random',$random);
        //        return $this->fetch();
    }

    public function area()
    {
        $provinceCode = input('province_code');
        $areas = get_areas();
        $stc = '';
        $sta = '';
        foreach ($areas['province'] as $value) {
            if ($value['@attributes']['code'] == $provinceCode) {

                foreach ($value['city'] as $city) {
                    $stc .= '<div class=\'child c-group clist pcode_c' . $value['@attributes']['code'] . "' style='display: none;'>\r\n                        <label class='checkbox-inline ' style='cursor: default'>" . $city['@attributes']['name'] . "</label>\r\n                        <label class='checkbox-inline pull-right'>\r\n                        <input type='checkbox' id='ch_ccode" . $city['@attributes']['code'] . '\' class=\'cityall checkbox_pcode' . $value['@attributes']['code'] . '\' value=\'' . $city['@attributes']['name'] . '\' data-value=\'' . $city['@attributes']['code'] . '\' pcode=\'' . $value['@attributes']['code'] . '\' pname=\'' . $value['@attributes']['name'] . "' title='选择' />\r\n                        </label>\r\n                  </div>";

                    foreach ($city['county'] as $county) {
                        $sta .= ' <div class=\'child a-group alist pcode_a' . $value['@attributes']['code'] . ' ccode_a' . $city['@attributes']['code'] . "' style='display: none;'>\r\n                                  <label class='checkbox-inline ' style='cursor: default'>" . $county['@attributes']['name'] . "</label>\r\n                                  <label class='checkbox-inline pull-right'>\r\n                                  <input type='checkbox' id='ch_acode" . $county['@attributes']['code'] . '\' class=\'areaall checkbox_pcode' . $value['@attributes']['code'] . ' checkbox_ccode' . $city['@attributes']['code'] . '\' value=\'' . $county['@attributes']['name'] . '\' data-value=\'' . $county['@attributes']['code'] . '\' ccode=\'' . $city['@attributes']['code'] . '\' pcode=\'' . $value['@attributes']['code'] . '\' cname=\'' . $city['@attributes']['name'] . '\' pname=\'' . $value['@attributes']['name'] . "' title='选择' />\r\n                                  </label>\r\n                               </div>";
                    }
                }
            }
        }

        $data['stc'] = $stc;
        $data['sta'] = $sta;
        //        show_json(1, $data);
        $result = array('status' => 1, 'result' => $data);

        return json($result);
    }

    public function search()
    {
        $shopId = session('shop_id');

        if (input('post.keyword') != '') {
            cookie('dispatch_name', input('post.keyword'), 3600);
        }

        $where                 = array();
        $where['shop_id']    = $shopId;

        if (cookie('dispatch_name')) {
            $where['dispatch_name'] = array('like', '%' . cookie('dispatch_name') . '%');
        }

        $dispatchModel = new DispatchModel();
        $list = $dispatchModel->where($where)->order('sort DESC,id DESC')->paginate(5);
        $page = $list->render();

        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        if (cookie('dispatch_name')) {
            $this->assign('dispatch_name', cookie('dispatch_name'));
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pnum', $pnum);
        $this->assign('search', 1);

        if (request()->isAjax()) {
            return $this->fetch('ajaxpage');
        } else {
            return $this->fetch('lst');
        }
    }
}