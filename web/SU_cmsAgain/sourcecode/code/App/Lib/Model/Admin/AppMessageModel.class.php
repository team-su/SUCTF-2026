<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class AppMessageModel extends Model{
	function getAppMessage($offset = -1, $length = -1, $p=array()){
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
        $where = array();
		if( isset($p['AppMessageType']) &&  $p['AppMessageType'] != -1){
			$where['AppMessageType'] = (int)$p['AppMessageType'];
		}
		$result = $this->where($where)->order('AppMessageID desc')->select();
		return $result;
	}
	
	function getAppMessageCount($p=array()){
        $where = array();
		if( isset($p['AppMessageType']) &&  $p['AppMessageType'] != -1){
			$where['AppMessageType'] = intval($p['AppMessageType']);
		}
		$n= $this->where($where)->count();
		return $n;
	}
	
	function findAppMessage($id, $p = array() ){
		$where['AppMessageID'] = intval($id);
		if( isset($p['AppMessageType']) &&  $p['AppMessageType'] != -1){
			$where['AppMessageType'] = intval($p['AppMessageType']);
		}
		$result = $this->where($where)->find();
		return $result;
	}
}