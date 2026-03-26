<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class AdModel extends Model{
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getAd($offset = -1, $length = -1, $p=array() ){
		$this->field('a.*,b.AdTypeName,c.AdGroupName');
		$this->table($this->tablePrefix.'ad a');
		$this->join('Left Join '.$this->tablePrefix.'ad_type b On a.AdTypeID = b.AdTypeID');
		$this->join('Left Join '.$this->tablePrefix.'ad_group c On a.AdGroupID = c.AdGroupID');
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$where = get_language_where_array('a');
		if( isset($p['IsEnable']) ){
			$where['a.IsEnable'] = intval($p['IsEnable']);
		}
		if( isset($p['AdGroupID']) ){ //根据广告位筛选
			$where['a.AdGroupID'] = intval($p['AdGroupID']);
		}
		$result = $this->where($where)->order('a.AdGroupID, a.AdOrder asc , a.AdTime desc')->select();
		return $result;
	}
	
	function findAd($id, $p = array() ){
		$where['AdID'] = intval($id);
		$result = $this->where($where)->find();
		return $result;
	}
	
	function getAdCount( $options=array() ){
		$where = get_language_where_array();
		if( isset($options['AdGroupID']) ){ //根据广告位筛选
			$where['AdGroupID'] = intval($options['AdGroupID']);
		}
		if( isset($options['IsEnable']) ){
			$where['IsEnable'] = intval($options['IsEnable']);
		}
		$n = $this->where($where)->count();
		return $n;
	}
}
