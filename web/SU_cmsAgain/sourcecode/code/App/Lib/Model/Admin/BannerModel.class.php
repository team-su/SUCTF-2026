<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class BannerModel extends Model{
	protected $_validate = array(
			array('BannerOrder', '/^[-]?\d+$/', '排序必须为数字!', '2','regex'),
	);
	
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getBanner($IsEnable = -1, $groupid=-1){
		$where = get_language_where('a');
		$this->field('b.BannerGroupName, a.*');
		$this->table($this->tablePrefix.'banner a');
		$this->join($this->tablePrefix.'banner_group b On a.BannerGroupID = b.BannerGroupID');
		if( $IsEnable != -1 ){
			$IsEnable = intval($IsEnable);
			$where .= " and a.IsEnable = $IsEnable ";
		}
		if( $groupid != -1 ){
			$groupid = intval($groupid);
			$where .= " and a.BannerGroupID = $groupid ";
		}
		$where .= get_site_where(); //站点条件
		$result = $this->where($where)->order('b.BannerGroupOrder asc,b.BannerGroupID desc,a.BannerOrder asc, a.BannerID desc')->select();
		return $result;
	}
	
	function batchDelBanner( $id = array() ){
		$id = YdInput::filterCommaNum($id);
		$where['BannerID']  = array('in', implode(',', $id));
		$result = $this->where($where)->delete();
		return $result;
	}
	
	//批量排序
	function batchSortBanner($BannerID=array(), $BannerOrder = array() ){
		$n = count($BannerID);
		for($i = 0; $i < $n; $i++){
			if( is_numeric($BannerOrder[$i]) ){
			    $id = intval($BannerID[$i]);
				$this->where("BannerID={$id}")->setField('BannerOrder', $BannerOrder[$i]);
			}
		}
	}
	
}
