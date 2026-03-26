<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class SupportModel extends Model{
	protected $_validate = array(
			array('SupportName', 'require', '客服名称不能为空!'),
			array('SupportName', '', '客服名称已经存在!', '0', 'lang_unique'),
			array('SupportOrder', '/^[-]?\d+$/', '排序必须为数字!', '2','regex'),
	);
	
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getSupport($IsEnable = -1){
		$this->field('a.SupportID, a.SupportName, a.SupportNumber,a.SupportOrder,a.IsEnable, b.SupportTypeName,b.SupportTypeID');
		$this->table($this->tablePrefix.'support a');
		$this->join('Inner Join '.$this->tablePrefix.'support_type b On a.SupportTypeID = b.SupportTypeID');
		$where = get_language_where('a');
		if( $IsEnable != -1 ){
			$IsEnable = intval($IsEnable);
			$where .= " and a.IsEnable=$IsEnable";
		}
		$result = $this->where($where)->order('a.SupportOrder asc')->select();
		return $result;
	}
	
	function batchDelSupport( $id = array() ){
		$id = YdInput::filterCommaNum($id);
		$where = 'SupportId in('.implode(',', $id).')';
		$result = $this->where($where)->delete();
		return $result;
	}
	
	//批量排序
	function batchSortSupport($SupportID=array(), $SupportOrder = array() ){
		$n = count($SupportID);
		for($i = 0; $i < $n; $i++){
			if( is_numeric($SupportOrder[$i]) ){
				$id = intval($SupportID[$i]);
				$this->where("SupportID={$id}")->setField('SupportOrder', $SupportOrder[$i]);
			}
		}
	}
	
}
