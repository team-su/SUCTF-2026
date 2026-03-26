<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class LabelModel extends Model{
	protected $_validate = array(
			array('LabelName', 'require', '标记属性名称不能为空!'),
			array('LabelOrder', '/^[-]?\d+$/', '排序必须为数字!', '2','regex'),
	);
	
	function getLabel($ChannelModelID=-1, $IsSystem = -1, $IsEnable = -1, $offset = -1, $length = -1){
		$where = "1=1 ";
	    if( $ChannelModelID != -1 ){
	    	$ChannelModelID = intval($ChannelModelID);
			$where .= " and a.ChannelModelID=$ChannelModelID ";
		}
		
		if( $IsEnable != -1 ){
			$IsEnable = intval($IsEnable);
			$where .= " and a.IsEnable=$IsEnable";
		}
		
		if( $IsSystem != -1 ){
			$IsSystem = intval($IsSystem);
			$where .= " and a.IsSystem=$IsSystem";
		}
		
		//分页
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		
		$this->field('a.LabelID, a.LabelName, a.ChannelModelID,a.LabelOrder,a.IsSystem,a.IsEnable,b.ChannelModelName');
		$this->table($this->tablePrefix.'label a');
		$this->join($this->tablePrefix.'channel_model b On a.ChannelModelID = b.ChannelModelID');
		$result = $this->where($where)->order('a.LabelOrder asc, a.LabelID asc')->select();
		return $result;
	}
	
	function batchDelLabel( $id = array() ){
		$id = YdInput::filterCommaNum($id);
		$where = 'IsSystem = 0 and LabelID in('.implode(',', $id).')';
		$result = $this->where($where)->delete();
		return $result;
	}
	
	function safeDel( $LabelID ){
		$LabelID = intval($LabelID);
		$where = "IsSystem = 0 and LabelID = $LabelID";
		return $this->where($where)->delete();
	}
	
	//批量排序
	function batchSortLabel($LabelID=array(), $LabelOrder = array() ){
		$n = count($LabelID);
		for($i = 0; $i < $n; $i++){
			if( is_numeric($LabelOrder[$i]) ){
				$id = intval($LabelID[$i]);
				$this->where("LabelID={$id}")->setField('LabelOrder', $LabelOrder[$i]);
			}
		}
	}
	
	function getCount($ChannelModelID=-1, $IsSystem = -1, $IsEnable = -1){
		$where = "1=1 ";
	    if( $ChannelModelID != -1 ){
	    	$ChannelModelID = intval($ChannelModelID);
			$where .= " and a.ChannelModelID=$ChannelModelID ";
		}
		
		if( $IsEnable != -1 ){
			$IsEnable = intval($IsEnable);
			$where .= " and a.IsEnable=$IsEnable";
		}
		
		if( $IsSystem != -1 ){
			$IsSystem = intval($IsSystem);
			$where .= " and a.IsSystem=$IsSystem";
		}
		$n = $this->where($where)->count();
		return $n;
	}
}
