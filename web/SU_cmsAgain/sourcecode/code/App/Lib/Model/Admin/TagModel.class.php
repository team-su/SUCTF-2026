<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class TagModel extends Model{
	protected $_validate = array(
			array('TagName', 'require', '自定义标签名称不能为空!'),
			array('TagName', '/^[a-zA-Z]+[\d]*$/', '自定义标签名包含无效字符!', 0, 'regex'),
			array('TagName', '', '自定义标签名称已经存在!', '0', 'lang_unique')
	);
	
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getTag($offset = -1, $length = -1, $IsEnable = -1, $field=false){
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$where = get_language_where();
		if( $IsEnable != -1 ){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable = $IsEnable";
		}
		if(!empty($field)){
		    $field = YdInput::checkTableField($field);
			$this->field($field);
		}
		$result = $this->where($where)->order('TagID desc')->select();
		return $result;
	}
	
	
	function batchDelTag( $id = array() ){
		$id = YdInput::filterCommaNum($id);
		$where = 'TagId in('.implode(',', $id).')';
		$result = $this->where($where)->delete();
		return $result;
	}
	
	function getTagField(){
		$where = get_language_where()." and IsEnable = 1";
		$data = $this->where($where)->getField('TagName,TagContent');
		return $data;
	}
}
