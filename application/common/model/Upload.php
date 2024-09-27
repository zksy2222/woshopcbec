<?php

namespace app\common\model;

use think\Model;
use Qcloud\Cos\Client;


class Upload extends Model
{
    private $configCateId = 17; // 第三方存储 参数分类ID
    private $videoMaxSize = 31457280; //30M
    private $videoType    = array('mp4', 'avi', 'rmvb', 'mkv');
    private $imageMaxSize = 10485760;  //10M  10485760
    private $imageType    = array('jpg','jpeg','gif','png');
    public  $uploadConfig;
    public  $cosConfig;
    public  $webUrl;

    protected function initialize()
    {
        $configModel        = new Config();
        $this->uploadConfig = $configModel->getConfigValueByCateId($this->configCateId);
        if ($this->uploadConfig['storage_mode'] == 'tengxunyun') {
            $this->cosConfig = array(
                'region'      => $this->uploadConfig['cos_region'],
                'schema'      => 'https',
                'credentials' => array(
                    'secretId'  => $this->uploadConfig['cos_secretid'],
                    'secretKey' => $this->uploadConfig['cos_secretkey']
                ),
                'bucket_id'   => $this->uploadConfig['bucket_id'],
                'cos_domain'  => $this->uploadConfig['cos_domain'],
            );
        }
        $this->webUrl       = $configModel->getConfigByName('weburl');
    }

    /**
     * @func 图片上传
     * @param $file 图片对象
     * @param $mkdirname 图片存放的目录
     * @param $maxNum最多上传图片的数量
     * @return array|string 返回上传图片的路径
     */
    public function uploadPic($file,$mkdirname='')
    {

        if (empty($file)) {
            return array('status' => 400, 'mess' => '请上传图片');
        }

        $storageMode = $this->uploadConfig['storage_mode'];
        if ($storageMode == 'tengxunyun') {
            $cosClient = new Client($this->cosConfig);
        }
        if(empty($mkdirname)){
            $mkdirname =  input('post.name', 'pic');
        }
        $uploadFolder = '/uploads/' . $mkdirname . '/'; // 上传文件路径
        $rule         = ['size' => $this->imageMaxSize, 'ext' => $this->imageType];

        $maxNum = 9;  //最大上传图片数量

        if (is_array($file)) { // 多图片上传
            if (count($file) >= $maxNum) {
                datamsg(400, '最多上传' . $maxNum . '张图片');
            }
            $picArr = [];
            foreach ($file as $k => $v) {
                $checkFile = $file[$k]->check($rule);
                if ($checkFile !== true) {
                    return array('status' => 400, 'mess' => $file[$k]->getError());
                }
                if ($storageMode == 'tengxunyun') {
                    try {
                        if ($file[$k]) {
                            $extension = strtolower(pathinfo($file[$k]->getInfo('name'), PATHINFO_EXTENSION));
                            $key       = $uploadFolder . date('Ymd') . '/' . md5(uniqid(microtime(true), true)) . '.' . $extension;
                            $fileInfo  = array('Bucket' => $this->cosConfig['bucket_id'], 'Key' => $key, 'Body' => $file[$k]);
                            $result    = $cosClient->putObject($fileInfo);
                            if (empty($result['Key'])) {
                                return array('status' => 400, 'mess' => '上传失败', 'data' => '');
                            }
                            $data[] = array(
                                'path'      => $this->cosConfig['cos_domain'] . $result['Key'],
                                'full_path' => $this->cosConfig['cos_domain'] . $result['Key']
                            );
                            unset($key);unset($fileInfo);unset($result);
                        } else {
                            return array('status' => 400, 'mess' => '读取不到上传文件，上传失败', 'data' => '');
                        }
                    } catch (\Exception $e) {
                        return array('status' => 400, 'mess' => '上传失败，' . $e->getMessage());
                    }
                } else {
                    $info = $file[$k]->move(ROOT_PATH . 'public' . $uploadFolder);
                    if ($info) {
                        $data[] = array(
                            'path'      => $uploadFolder . $info->getSaveName(),
                            'full_path' => $this->webUrl . $uploadFolder . $info->getSaveName()
                        );
                    } else {
                        return array('status' => 400, 'mess' => '上传失败：' . $file[$k]->getError());
                    }
                }
            }
            return array('status' => 200, 'mess' => '上传成功', 'data' => $data);
        } else { // 单图片上传
            $checkFile = $file->check($rule);
            if ($checkFile !== true) {
                return array('status' => 400, 'mess' => $file->getError());
            }
            if ($storageMode == 'tengxunyun') {

                try {
                    if ($file) {
                        $extension = strtolower(pathinfo($file->getInfo('name'), PATHINFO_EXTENSION));
                        $key       = $uploadFolder . date('Ymd') . '/' . md5(uniqid(microtime(true), true)) . '.' . $extension;
                        $fileInfo  = array('Bucket' => $this->cosConfig['bucket_id'], 'Key' => $key, 'Body' => $file);
                        $result    = $cosClient->putObject($fileInfo);
                        if (empty($result['Key'])) {
                            return array('status' => 400, 'mess' => '上传失败', 'data' => '');
                        }
                        $data = array(
                            'path'      => $this->cosConfig['cos_domain'] . $result['Key'],
                            'full_path' => $this->cosConfig['cos_domain'] . $result['Key']
                        );
                        return array('status' => 200, 'mess' => '上传成功', 'data' => $data);
                    } else {
                        return array('status' => 400, 'mess' => '读取不到上传文件，上传失败');
                    }
                } catch (\Exception $e) {
                    return array('status' => 400, 'mess' => '上传失败，' . $e->getMessage());
                }
            } else {

                $info = $file->move(ROOT_PATH . 'public' . $uploadFolder);
                if ($info) {
                    $data = array(
                        'path'      => $uploadFolder . $info->getSaveName(),
                        'full_path' => $this->webUrl . $uploadFolder . $info->getSaveName()
                    );
                    return array('status' => 200, 'mess' => '上传成功', 'data' => $data);
                } else {
                    return array('status' => 400, 'mess' => '上传失败：' . $file->getError());
                }
            }


        }
    }


    public function uploadVideo($file)
    {

        
        $uploadFolder = '/uploads/' . input('post.name', 'video') . '/'; // 上传文件路径
        $rule         = ['size' => $this->videoMaxSize, 'ext' => $this->videoType];

        $checkFile = $file->check($rule);
        if ($checkFile !== true) {
            return array('status' => 400, 'mess' => $file->getError());
        }

        $storageMode = $this->uploadConfig['storage_mode'];
        if ($storageMode == 'tengxunyun') {
            $cosClient = new Client($this->cosConfig);
            try {
                if ($file) {
                    $extension = strtolower(pathinfo($file->getInfo('name'), PATHINFO_EXTENSION));
                    $key       = $uploadFolder . date('Ymd') . '/' . md5(uniqid(microtime(true), true)) . '.' . $extension;
                    $fileInfo  = array('Bucket' => $this->cosConfig['bucket_id'], 'Key' => $key, 'Body' => $file);
                    $result    = $cosClient->putObject($fileInfo);
                    if (empty($result['Key'])) {
                        return array('status' => 400, 'mess' => '上传失败', 'data' => '');
                    }
                    $data = array(
                        'path'      => $this->cosConfig['cos_domain'] . $result['Key'],
                        'full_path' => $this->cosConfig['cos_domain'] . $result['Key']
                    );
                    return array('status' => 200, 'mess' => '上传成功', 'data' => $data);
                } else {
                    return array('status' => 400, 'mess' => '读取不到上传文件，上传失败');
                }
            } catch (\Exception $e) {
                return array('status' => 400, 'mess' => '上传失败，' . $e->getMessage());
            }
        }else{
            $info = $file->move(ROOT_PATH . 'public' . $uploadFolder);
            if ($info) {
                $data = array(
                    'path'      => $uploadFolder . $info->getSaveName(),
                    'full_path' => $this->webUrl . $uploadFolder . $info->getSaveName()
                );
                return array('status' => 200, 'mess' => '上传成功', 'data' => $data);
            } else {
                return array('status' => 400, 'mess' => '上传失败：' . $file->getError());
            }
        }


    }


}