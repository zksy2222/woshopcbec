<?php
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use think\Db;

class Report extends Common
{
    // 投诉建议反馈列表
	public function  listReport(){
		$tokenRes = $this->checkToken();
		if($tokenRes['status'] == 400){
			datamsg(400,$tokenRes['mess'],$tokenRes['data']);
		}else{
			$userId = $tokenRes['user_id'];
		}
		$reports = db('feedback_help a')
            ->field('a.*,b.content reply_content,b.replytime,b.admin_id')
            ->join('reply b','a.id = b.fid','left')
            ->where('user_id',$userId)
            ->select();

		datamsg(200,'获取举报信息成功',$reports);


	}
    // 发布投诉建议
    public function addReport()
    {
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }

        $title = input('post.title');
        $content = input('post.content');
        if (empty($title)) {
            datamsg(400, '请选择举报内容');
        }
        if (empty($content)) {
            datamsg(400, '请输入举报或建议内容');
        }
        $data['user_id'] = $userId;
        $data['title'] = $title;
        $data['content'] = $content;
        $data['time'] = time();
        $data['reply'] = 0;

        $res = db('feedback_help')->insertGetId($data);

        $pic = input('param.pic');
        $datapic = explode(',', $pic);
        $picarr = [];
        foreach ($datapic as $key => $value) {
            $picarr[$key]['pathurl'] = $value;
            $picarr[$key]['fid'] = $res;
        }
        $resultPic = Db::name('feedback_pic')->insertAll($picarr);
        if ($res && $resultPic) {
            Db::commit();
            datamsg(200, '提交成功');
        } else {
            Db::rollback();
            datamsg(400, '提交失败');
        }

        if ($res) {
            datamsg(200, '提交成功');
        } else {
            datamsg(400, '提交失败');
        }


    }
	
	// 口碑投诉
	public function findReport()
	{
		$tokenRes = $this->checkToken();
		if($tokenRes['status'] == 400){
			datamsg(400,$tokenRes['mess'],$tokenRes['data']);
		}else{
			$userId = $tokenRes['user_id'];
		}

		$k_id = input('post.k_id');
        $title = input('post.title');
        $content = input('post.content');
        if (empty($title)) {
            datamsg(400, '请选择举报内容');
        }
        if (empty($content)) {
            datamsg(400, '请输入举报或建议内容');
        }
        $data['user_id'] = $userId;
        $data['k_id'] = $k_id;
        $data['title'] = $title;
        $data['content'] = $content;
        $data['time'] = time();
        $data['reply'] = 0;

        $res = db('feedback_order')->insertGetId($data);

        $pic = input('param.pic');
        $datapic = explode(',', $pic);
        $picarr = [];
        foreach ($datapic as $key => $value) {
            $picarr[$key]['pathurl'] = $value;
            $picarr[$key]['fid'] = $res;
        }
        $resultPic = Db::name('feedback_order_pic')->insertAll($picarr);
        if ($res && $resultPic) {
            Db::commit();
            datamsg(200, '提交成功');
        } else {
            Db::rollback();
            datamsg(400, '提交失败');
        }

        if ($res) {
            datamsg(200, '提交成功');
        } else {
            datamsg(400, '提交失败');
        }
	}
	
	
}