<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class WxCouponModel extends Model {
		protected $_validate = array(
			array('CouponName', 'require', '名称不能为空!'),
			array('CouponOrder', '/^[-]?\d+$/', '排序必须为数字!', '2','regex'),
	);
	//获取调查建议
	function getCoupon($offset = -1, $length = -1, $keywords='', $IsEnable = -1, $time=-1){
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$where = "1=1";
		if( $keywords != ''){
			$keywords = addslashes(stripslashes($keywords));
            $keywords = YdInput::checkKeyword( $keywords );
			$where .= " and ( CouponName like '%$keywords%' )";
		}
		if( $IsEnable != -1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable={$IsEnable}";
		}
		if( $time != -1){
			$now = date('Y-m-d H:i:s');
			$where .= " and StartTime<='{$now}' and EndTime>='{$now}'";
		}
		$result = $this->where($where)->order('CouponOrder asc, CouponID desc')->select();
		return $result;
	}
	
	//获取调查数量
	function getCount($keywords=''){
		$where = "1=1";
		if( $keywords != ''){
			$keywords = addslashes(stripslashes($keywords));
            $keywords = YdInput::checkKeyword( $keywords );
			$where .= " and ( CouponName like '%{$keywords}%' )";
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	//支持删除和批量删除
	function delCoupon($id){
		$id = YdInput::filterCommaNum($id);
		$where = is_array($id) ? 'CouponID in('.implode(',', $id).')' : "CouponID=$id";
		$result = $this->where($where)->delete();
		return $result;
	}
	
	//批量排序
	function batchSortCoupon($CouponID=array(), $CouponOrder = array() ){
		$n = count($CouponID);
		for($i = 0; $i < $n; $i++){
			if( is_numeric($CouponOrder[$i]) ){
				$id = intval($CouponID[$i]);
				$this->where("CouponID={$id}")->setField('CouponOrder', $CouponOrder[$i]);
			}
		}
	}
}