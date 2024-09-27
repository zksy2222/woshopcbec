<?php

namespace app\api\controller;

use app\api\model\Common as CommonModel;
use think\Db;
use app\api\model\Live as LiveModel;

class Share extends Common
{
    /**
     * 分销分享数据
     */
    public function shareData()
    {
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }
        //生成二维码
        Vendor('phpqrcode.phpqrcode');
        //生成二维码图片
        $object = new \QRcode();
        $imgrq  = date('Ymd', time());
        if (!is_dir("./uploads/share/" . $userId)) {
            mkdir("./uploads/share/" . $userId, 0777, true);
        }

        $url         = get_config_value('linkurl'); // 推广链接
        $url         = $url . "/index/inviter/" . $userId;//绑定当前用户id
        $imgfilepath = "./uploads/share/" . $userId . "/qrcode_" . $userId . ".jpg";
        $object->png($url, $imgfilepath, 'H', 15, 2);
        $imgurlfile = "/uploads/share/" . $userId . "/qrcode_" . $userId . ".jpg";

        //二维码图片生成成功
        $data['qrcodeurl'] = $this->webconfig['weburl'] . $imgurlfile;

        //合并图片

        //获取推广背景图
        $bg         = get_config_value('posters_background'); // 推广海报背景图
        $QR         = url_format($bg,$this->webconfig['weburl']);
        // $a = $this->http_request($QR);
        // halt($a);
        // halt($QR);
        $QR         = imagecreatefromstring(ihttp_request($QR));

        $imgurlfile = imagecreatefromstring(ihttp_request($data['qrcodeurl']));
        $QR_width   = imagesx($QR);//背景图片宽度

        $QR_height         = imagesy($QR) - 200;//背景图片高度
        $imgurlfile_width  = imagesx($imgurlfile);//二维码图片宽度
        $imgurlfile_height = imagesy($imgurlfile);//二维码图片高度

        $imgurlfile_qr_width  = 360;
        $imgurlfile_qr_height = 360;

        $from_width  = ($QR_width - $imgurlfile_qr_width) / 2;
        $from_width1 = ($QR_height - $imgurlfile_qr_height) / 1.15;
        //重新组合图片并调整大小
        $a = imagecopyresampled($QR, $imgurlfile, $from_width, $from_width1, 0, 0, $imgurlfile_qr_width, $imgurlfile_qr_height, $imgurlfile_width, $imgurlfile_height);

        $img_path = "./uploads/share/" . $userId . "/bgqrcode_" . $userId . ".jpg";
        //存放拼接后的图片到本地
        $r = imagejpeg($QR, $img_path);


        $data['tgimg'] = $this->webconfig['weburl'] . "/uploads/share/" . $userId . "/bgqrcode_" . $userId . ".jpg";

        //分享信息
        $sharedata = db('config')->where(['ca_id' => 12])->field('id,ename,value,values')->order("id desc")->select();
        $j         = 0;
        foreach ($sharedata as $k => $v) {
            if (in_array($v['id'], array(152, 153, 154, 166))) {
                $arr                     = array("152" => "端庄版", "153" => "硬核版", "154" => "正常版", "166" => "");
                $data['wx'][$j]['value'] = $arr[$v['id']];
                $data['wx'][$j]['name']  = $v['value'];
                $j++;
            } else {
                $data[$v['ename']] = $v['value'];
            }
        }
        $data['posters_background'] = url_format($bg,$this->webconfig['weburl']);
        datamsg(200, '获取成功', $data);

    }
}