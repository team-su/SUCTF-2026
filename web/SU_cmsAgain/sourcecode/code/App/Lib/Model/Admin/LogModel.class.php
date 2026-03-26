<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class LogModel extends Model{
	function getLog($offset = -1, $length = -1, $p=array()){
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
        $where = $this->getLogWhere($p);
		$result = $this->where($where)->order('LogID desc')->select();
		return $result;
	}

	function getLogWhere($p=array()){
        $where = array();
        if( !empty( $p['UserName'] ) ){
            $keywords =YdInput::checkKeyword($p['UserName']);
            $where['UserName|LogDescription|UserAction'] = array('like', "%{$keywords}%");
        }
        if( !empty( $p['LogType'] ) ){
            $where['LogType'] = intval($p['LogType']);
        }
        return $where;
    }
	
	//统计日志总数
	function getLogCount($p=array() ){
		$where = $this->getLogWhere($p);
		$n = $this->where($where)->count();
		return $n;
	}
	
	//将指定ip的城市
	function setCity($ip, $city){
		$ip = YdInput::checkHtmlName($ip);
		$where = "UserIP='{$ip}' and ISNULL(UserCity) ";
		$result = $this->where( $where )->setField('UserCity', $city);
		return $result;
	}
	
	/**
	 * 获取当天指定IP地址发送的短信数
	 * @param string $ip
	 */
	function getDaySmsCountByIp($ip){
        $ip = YdInput::checkHtmlName($ip);
		$where = "LogType=9 and date(LogTime) = curdate()";
		$where .= " and UserIP='{$ip}'";
		$n = $this->where( $where )->count();
		return $n;
	}
	
	/**
	 * 获取当天指定手机号码发送的短信数
	 * @param string $num 
	 */
	function getDaySmsCountByNum($num){
		$num = $ip = YdInput::checkHtmlName($num);
		$where = "LogType=9 and date(LogTime) = curdate()";
		$where .= " and UserAction='{$num}'";
		$n = $this->where( $where )->count();
		return $n;
	}

    /**
     * 删除过期日志
     */
	function deleteExpiredLog(){
        try{
            $maxDays = 30 * 6;  //6个月
            $time = date("Y-m-d H:i:s", strtotime("-{$maxDays} day"));
            $where = "LogTime<'{$time}'";
            $result = $this->where($where)->delete();
            return $result;
        }catch(Exception $e){
            return false;
        }
    }

    /**
 * 判断短信验证码是否过期，默认为99秒
 */
    function isSmsCodeExpired($code, $mobile, $timeout=99){
        $code = YdInput::checkNum($code);
        $mobile = YdInput::checkLetterNumber($mobile);
        $timeout = intval($timeout); //单位：秒

        $where = "LogType=9 AND UserAction='{$mobile}' AND LogDescription LIKE '%【{$code}】%'";
        $where .= " AND timestampdiff(SECOND,LogTime, now())<{$timeout}";
        $id = $this->where( $where )->order("LogID DESC")->getField('LogID');
        if($id > 0){
            return false;
        }else{
            return true;
        }
    }
}
