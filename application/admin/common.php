<?php
    /**
     * 添加后台日志
     * @param $log 操作类型
     * @param $tables 操作数据表
     * @param $opid 操作主键ID
     */
    function ys_admin_logs($log,$tables,$opid)
    {
        return \app\admin\controller\AdminLog::add($log,$tables,$opid);
    }

	/**
	 * @func 返回用户的姓名和电话
	 * @param $uid用户id
	 */
	function getusernumber($uid){
		$users = db('member')->where(['id'=>$uid])->find();
		$name = '';
		if($users['user_name']){
			$name=$users['user_name'].'-';
		}
		return $name.mix_phone($users['phone']);
	}

	/**
	 * 俩个时间戳相差多少
	 * @param $begin_time
	 * @param $end_time
	 * @return array
	 */
	function timediff($begin_time,$end_time){
		if($begin_time < $end_time){
			$starttime = $begin_time;
			$endtime = $end_time;
		}else{
			$starttime = $end_time;
			$endtime = $begin_time;
		}
		//计算天数
		$timediff = $endtime-$starttime;
		$days = intval($timediff/86400);
		//计算小时数
		$remain = $timediff%86400;
		$hours = intval($remain/3600);
		//计算分钟数
		$remain = $remain%3600;
		$mins = intval($remain/60);
		//计算秒数
		$secs = $remain%60;
		$res = array("day" => $days,"hour" => $hours,"min" => $mins,"sec" => $secs);
		return $res;
	}


	/**
     * 生成六位春数字邀请码
     */
	function agentInviteCode(){
        $unique_no = substr(base_convert(md5(uniqid(md5(microtime(true)),true)), 16, 10), 0, 6);
        $agentUserInviteCode = db("agent")->where("invite_code",$unique_no)->count();
        if($agentUserInviteCode){
            return agentInviteCode();
        }
        return $unique_no;
    }