<?php

namespace app\api\controller;

use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use think\Db;

class Category extends Common
{

    //获取分类信息
    public function index()
    {
        $tokenRes = $this->checkToken(0);
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        }

        $cateres = Db::name('category')->where('pid', 0)->where('is_show', 1)->field('id,cate_name')->order('sort asc')->select();
        $recom_cate = Db::name('category')->where('show_in_recommend', 1)->where('is_show', 1)->field('id,cate_name,cate_pic')->order('sort asc')->select();
        $webconfig = $this->webconfig;
        foreach ($recom_cate as $key => $val) {
            $recom_cate[$key]['cate_pic'] = url_format($val['cate_pic'], $webconfig['weburl'], '?imageMogr2/thumbnail/80x');
        }
        $cateinfos = array('cateres' => $cateres, 'recom_cate' => $recom_cate);
        datamsg(200, '获取商品分类成功', set_lang($cateinfos));
    }

    //通过顶级分类id获取子类
    public function getchild()
    {
//	    $tokenRes = $this->checkToken(0);
//	    if($tokenRes['status'] == 400){
//		    datamsg(400, $tokenRes['mess'], $tokenRes['data']);
//	    }

        $webconfig = $this->webconfig;
        if (input('post.cate_id')) {
            $cate_id = input('post.cate_id');
            $categorys = Db::name('category')->where('id', $cate_id)->where('pid', 0)->where('is_show', 1)->field('id,cate_name,cate_pic')->find();
            if (!$categorys) {
                datamsg(400, '分类id参数错误', array('status' => 400));
            }
            $child_cate = Db::name('category')->where('pid', $cate_id)->where('is_show', 1)->field('id,cate_name,cate_pic')->order('sort asc')->select();
            if ($child_cate) {
                foreach ($child_cate as $key => $val) {
                    $child_cate[$key]['cate_pic'] = url_format($val['cate_pic'], $webconfig['weburl'], '?imageMogr2/thumbnail/80x');
                    $child_cate[$key]['three'] = Db::name('category')->where('pid', $val['id'])->where('is_show', 1)->field('id,cate_name,cate_pic')->order('sort asc')->select();
                    if (!$child_cate[$key]['three']) {
                        $child_cate[$key]['three'][] = $val;
                    }
                    foreach ($child_cate[$key]['three'] as $key2 => $val2) {
                        $child_cate[$key]['three'][$key2]['cate_pic'] = url_format($val2['cate_pic'], $webconfig['weburl'], '?imageMogr2/thumbnail/80x');
                    }
                }
            } else {
                $child_cate[] = $categorys;
            }
            datamsg(200, '获取子类成功', set_lang($child_cate));

        } elseif (input('post.cate_id') == 0) {
            $recom_cate = Db::name('category')->where('show_in_recommend', 1)->where('is_show', 1)->field('id,cate_name,cate_pic')->order('sort asc')->select();
            foreach ($recom_cate as $kr => $vr) {
                $recom_cate[$kr]['cate_pic'] = url_format($vr['cate_pic'], $webconfig['weburl'], '?imageMogr2/thumbnail/80x');
            }
            datamsg(200, '获取推荐分类信息成功', set_lang($recom_cate));
        } else {
            datamsg(400, '缺少分类id参数');
        }
    }


}