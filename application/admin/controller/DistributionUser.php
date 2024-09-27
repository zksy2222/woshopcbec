<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use app\admin\model\DistributionUser as distribUserModel;
use app\common\Lookup;
use app\common\model\DistributionConfig;
use app\admin\model\DistributionGrade;
use app\admin\model\Member;

class DistributionUser extends Common{
    
    public function lst() {
        $distribConfigModel = new DistributionConfig();
        $config = $distribConfigModel->getDistributionConfig();
        $distribUserModel = new distribUserModel();
        $list = $distribUserModel->getDistribUserList();
        $list->each(function($item, $key) use($config, $distribUserModel) {
            $userid_arr[] = $item['user_id'];
	        $item['headimgurl'] = url_format($item['headimgurl'],$this->webconfig['weburl']);
            $item['total_user'] = $distribUserModel->getTotalUser($userid_arr, $config['level']);
            return $item;
        });
        $page = $list->render();
        $data = array(
            'list' => $list,
            'page' => $page
        );
        $this->assign($data);
        return request()->isAjax() ? $this->fetch('ajaxpage') : $this->fetch();
    }
    
    public function edit() {
        $distribUserModel = new distribUserModel();
        if (request()->isPost()) {
            $data = input('post.');
            $result = $this->validate($data, 'DistributionUser');
            if(true !== $result){
                return json(array('status' => 0,'mess' => $result));
            }
            $update_data['grade_id'] = input('post.grade_id');
            $update_data['status'] = input('post.status');
            $update_data['is_distribution']=1;
            $updateResult = $distribUserModel->update($update_data, array('id' => $data['id']));
            if (!$updateResult) {
                return json(array('status' => 0,'mess' => '编辑失败'));
            }
            return json(array('status' => 1,'mess' => '编辑成功'));
        }
        $userId = input('user_id');
        $info = $distribUserModel->getDistribUserByUserId($userId);
        $gradeModel = new DistributionGrade();
        $grade_list = $gradeModel->getGradeSelect();
        $this->assign('info', $info);
        $this->assign('grade_list', $grade_list);
        return $this->fetch();
    }
    
    public function info() {
        $userid_arr[] = input('user_id');
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3,10))){
            $filter = 10;
        }

        switch ($filter){
            //待发货
            case 1:
                $level = Lookup::levelOne;
                break;
            //已发货
            case 2:
                $level = Lookup::levelTwo;
                break;
            case 3:
                $level = Lookup::levelThree;
                break;
            //全部
            case 10:
                $level = 10;
                break;
        }
        $distribUserModel = new distribUserModel();
        $distribConfigModel = new DistributionConfig();
        $config = $distribConfigModel->getDistributionConfig();
        $diffLevel = $distribUserModel->getDiffLevelUser($userid_arr, $config['level']);
        $total_user = $distribUserModel->getTotalUser($userid_arr, $config['level']);
        $user_str = '总人数：' . $total_user . '人；';
        if (isset($diffLevel['levelOne'])) {
            $user_str .= '一级：' . $diffLevel['levelOne'] . '人；';
        }
        if (isset($diffLevel['levelTwo'])) {
            $user_str .= '二级：' . $diffLevel['levelTwo'] . '人；';
        }
        if (isset($diffLevel['levelThr'])) {
            $user_str .= '三级：' . $diffLevel['levelThr'] . '人';
        }
        $list = $distribUserModel->getLowerUserListByUserId($userid_arr, $level);
        $data = array(
            'user_str' => $user_str,
            'list' => $list,
        );
        $this->assign($data);
        $this->assign('diff_level',$diffLevel);
        $this->assign('userid',$userid_arr[0]);
        $this->assign('filter',$filter);
        return $this->fetch();
    }
    
}