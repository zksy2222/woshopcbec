<?php

namespace app\admin\controller;

use app\admin\controller\Common;
use app\admin\model\Video as VideoModel;
use app\common\model\CosFileUpload;
use app\common\Lookup;
use app\admin\model\Shops;
use app\admin\model\Goods;

class Video extends Common
{

    public function lst()
    {
        $videlModel = new VideoModel();
        $list = $videlModel->getVideoList();
        $page = $list->render();
        $data = array(
            'list' => $list,
            'page' => $page
        );
        $this->assign($data);
        return request()->isAjax() ? $this->fetch('ajaxpage') : $this->fetch();
    }

    public function add()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $result = $this->validate($data, 'Video');
            if (true !== $result) {
                return json(array('status' => Lookup::isClose, 'mess' => $result));
            }
            $videoModel = new VideoModel();
            $data['create_time'] = time();
            $addResult = $videoModel->save($data);
            if (!$addResult) {
                return json(array('status' => Lookup::isClose, 'mess' => '添加失败'));
            }
            return json(array('status' => Lookup::isOpen, 'mess' => '添加成功'));
        }
        $shopsModel = new Shops();
        $shop_list = $shopsModel->getShopList();
        $data = array('shop_list' => $shop_list);
        $this->assign($data);
        return $this->fetch();
    }

    public function edit()
    {
        $videoModel = new VideoModel();
        if (request()->isPost()) {
            $data = input('post.');
            $result = $this->validate($data, 'Video');
            if (true !== $result) {
                return json(array('status' => Lookup::isClose, 'mess' => $result));
            }
            $videoModel = new VideoModel();
            $updateResult = $videoModel->update($data);
            if (!$updateResult) {
                return json(array('status' => Lookup::isClose, 'mess' => '更新失败'));
            }
            return json(array('status' => Lookup::isOpen, 'mess' => '更新成功'));
        }
        $id = input('id');
        $info = $videoModel->getVideoInfoById($id);
        $shopsModel = new Shops();
        $shop_list = $shopsModel->getShopList();
        $goodsModel = new Goods();
        $goods_info = $goodsModel->getGoodsInfoByGoodsId($info['goods_id']);
        $data = array(
            'info'       => $info,
            'shop_list'  => $shop_list,
            'goods_info' => $goods_info
        );
        $this->assign($data);
        return $this->fetch();
    }

    public function delete()
    {
        if (!request()->isAjax()) {
            return json(array('status' => Lookup::isClose, 'mess' => '请求方式错误'));
        }
        $id = input('id');
        if (!$id) {
            return json(array('status' => Lookup::isClose, 'mess' => '参数错误'));
        }
        $videoModel = new VideoModel();
        $delResult = $videoModel->destroy($id);
        if (!$delResult) {
            return json(array('status' => Lookup::isClose, 'mess' => '删除失败'));
        }
        return json(array('status' => Lookup::isOpen, 'mess' => '删除成功'));
    }

    public function uploadImage()
    {
        $image = !empty($_FILES['cover_image']) ? $_FILES['cover_image'] : '';
        if (!$image) {
            return json(array('status' => Lookup::isClose, 'mess' => '没有图片文件', 'data' => ''));
        }
        $uploadCos = new CosFileUpload();
        $verify = $uploadCos->imageVerify($image);
        if (!$verify['status']) {
            return json(array('status' => Lookup::isClose, 'mess' => $verify['mess'], 'data' => ''));
        }
        $dir = Lookup::videoCoverImage;
        $key = $uploadCos->getFileKey($verify['data'], $dir);
        $upload = $uploadCos->uploadFileCos($image['tmp_name'], $key);
        if (!$upload['status']) {
            return json(array('status' => Lookup::isClose, 'mess' => $upload['mess'], 'data' => ''));
        }
        $image_path = $upload['data']['path'];
        $image_url = $upload['data']['cos_domain'] . $image_path;
        $data = array('image_path' => $image_path, 'image_url' => $image_url);
        return json(array('status' => Lookup::isOpen, 'mess' => '上传成功', 'data' => $data));
    }

    public function uploadVideo()
    {
        $video = !empty($_FILES['videofile']) ? $_FILES['videofile'] : '';
        if (!$video) {
            return json(array('status' => Lookup::isClose, 'mess' => '没有视频文件', 'data' => ''));
        }
        $uploadCos = new CosFileUpload();
        $verify = $uploadCos->videoVerify($video);
        if (!$verify['status']) {
            return json(array('status' => Lookup::isClose, 'mess' => $verify['mess'], 'data' => ''));
        }
        $key = $uploadCos->getFileKey($verify['data']);
        $upload = $uploadCos->uploadFileCos($video['tmp_name'], $key);
        if (!$upload['status']) {
            return json(array('status' => Lookup::isClose, 'mess' => $upload['mess'], 'data' => ''));
        }
        $video_path = $upload['data']['path'];
        $video_url = $upload['data']['cos_domain'] . $video_path;
        $data = array('video_path' => $video_path, 'video_url' => $video_url);
        return json(array('status' => Lookup::isOpen, 'mess' => '上传成功', 'data' => $data));
    }

    public function getVideoList()
    {
        $shop_id = input('shop_id');
        $video_id = input('video_id');
        $keyword = input('keyword');
        $videlModel = new VideoModel();
        $list = $videlModel->getVideoListByShopId($shop_id, $video_id, $keyword);
        $page = $list->render();
        $data = array(
            'list'       => $list,
            'page'       => $page,
            'video_id'   => $video_id,
            'keyword'    => $keyword,
            'shop_id'    => $shop_id,
            'cos_domain' => $this->webconfig['cos_domain']
        );
        $this->assign($data);
        return request()->isAjax() ? $this->fetch('videopage') : $this->fetch('videolst');
    }
}
