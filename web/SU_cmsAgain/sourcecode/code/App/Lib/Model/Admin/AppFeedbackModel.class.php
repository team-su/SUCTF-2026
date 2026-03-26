<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class AppFeedbackModel extends Model{

	function getAppFeedback($offset = -1, $length = -1, $p = array()){
		$this->field('b.MemberName, a.*');
		$this->table($this->tablePrefix.'app_feedback a');
		$this->join('Left Join '.$this->tablePrefix.'member b On a.MemberID = b.MemberID');		
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
        $where = array();
		if( !empty($p['AppFeedbackContent']) ){
            $p['AppFeedbackContent'] = YdInput::checkKeyword($p['AppFeedbackContent']);
			$where['AppFeedbackContent'] = array('like','%'.$p['AppFeedbackContent'].'%');			
		}
		if( !empty($p['MemberName']) ){
            $p['MemberName'] = YdInput::checkKeyword($p['MemberName']);
			$where['MemberName'] = $p["MemberName"];
		}
		
		$result = $this->where($where)->order('AppFeedbackID desc')->select();
		return $result;
	}
	
	function getAppFeedbackCount($p = array()){
		$this->table($this->tablePrefix.'app_feedback a');
		$this->join('Left Join '.$this->tablePrefix.'member b On a.MemberID = b.MemberID');
        $where = array();
		if( !empty($p['AppFeedbackContent']) ){
            $p['AppFeedbackContent'] = YdInput::checkKeyword($p['AppFeedbackContent']);
			$where['AppFeedbackContent'] = array('like','%'.$p['AppFeedbackContent'].'%');
		}
		if( !empty($p['MemberName']) ){
            $p['MemberName'] = YdInput::checkKeyword($p['MemberName']);
			$where['MemberName'] = $p["MemberName"];
		}
		
		$n = $this->where($where)->count();
		return $n;
	}
}
