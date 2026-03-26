<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class TypeModel extends Model{
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getType($offset = -1, $length = -1, $p=array() ){
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$where = get_language_where_array();
		if( isset($p['IsEnable']) && $p['IsEnable'] != -1){
			$where['IsEnable'] = intval($p['IsEnable']);
		}
		$result = $this->where($where)->order('TypeOrder asc, TypeID desc')->select();
		//统计分组个数和属性数量
		if( isset($p['IsCount']) && $p['IsCount'] == 1){
			if(!empty($result)){
				$n = is_array($result) ? count($result) : 0;
				$ma = D('Admin/TypeAttribute');
				$mg = D('Admin/TypeGroup');
				for($i=0; $i<$n; $i++){
					$where = "TypeID=".$result[$i]['TypeID'];
					$AttributeCount = $ma->where($where)->count();
					$result[$i]['AttributeCount'] = $AttributeCount;
					
					$GroupCount = $mg->where($where)->count();
					$result[$i]['GroupCount'] = $GroupCount;
					
					$result[$i]['Count'] = $AttributeCount + $GroupCount;
				}
			}
		}
		return $result;
	}
	
	function getTypeCount($p){
		$where = get_language_where_array();
		if( isset($p['IsEnable']) && $p['IsEnable'] != -1){
			$where['IsEnable'] = intval($p['IsEnable']);
		}
		$n= $this->where($where)->count();
		return $n;
	}
	
	function findType($id, $options = array() ){
		if( !is_numeric($id) ) return false;
		$where = get_language_where_array();
		$where['TypeID'] = $id;
		if( isset($options['IsEnable']) && $options['IsEnable'] != -1 ) {
			$where['IsEnable'] = (int)$options['IsEnable'];
		}
		$result = $this->where($where)->find();
		return $result;
	}
}
