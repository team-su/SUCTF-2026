<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class TypeGroupModel extends Model{
	function getTypeGroup($p=array() ){
		$where['TypeID'] = intval($p['TypeID']);
		if( isset($p['IsEnable']) && $p['IsEnable'] != -1){
			$where['IsEnable'] = intval($p['IsEnable']);
		}
		$result = $this->where($where)->order('TypeGroupOrder asc, TypeGroupID desc')->select();
		if(!empty($result)){
			$n = is_array($result) ? count($result) : 0;
			$m = D('Admin/TypeAttribute');
			for($i=0; $i<$n; $i++){
				$AttributeCount = $m->where("TypeGroupID=".$result[$i]['TypeGroupID'])->count();
				$result[$i]['AttributeCount'] = $AttributeCount;
			}
		}
		return $result;
	}
	
	function getTypeGroupCount($p){
		$where['TypeID'] = intval($p['TypeID']);
		if( isset($p['IsEnable']) && $p['IsEnable'] != -1){
			$where['IsEnable'] = intval($p['IsEnable']);
		}
		$n= $this->where($where)->count();
		return $n;
	}
	
	function findTypeGroup($id, $options = array() ){
		if( !is_numeric($id) ) return false;
		$where['TypeGroupID'] = intval($id);
		if( isset($options['IsEnable']) && $options['IsEnable'] != -1 ) {
			$where['IsEnable'] = intval($options['IsEnable']);
		}
		$result = $this->where($where)->find();
		return $result;
	}
}
