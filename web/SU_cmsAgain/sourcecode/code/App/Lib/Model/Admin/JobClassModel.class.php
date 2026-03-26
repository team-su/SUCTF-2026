<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class JobClassModel extends Model{
	protected $_validate = array(
			array('JobClassName', 'require', '分类名称不能为空!'),
			array('JobClassName', '', '分类名称已经存在!', '0', 'lang_unique'),
			array('JobClassOrder', '/^[-]?\d+$/', '排序必须为数字!', '2','regex'),
	);
	
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getJobClass($p=false){
		$where = get_language_where();
		$result = $this->where($where)->order('JobClassOrder ASC, JobClassID ASC')->select();
		if( isset($p['Count']) && $p['Count'] == 1 && !empty($result)){ //统计分组链接数量
			$m = D('Admin/Job');
			$n = count($result);
			for($i = 0; $i < $n; $i++){
				$result[$i]['JobCount'] = $m->where("JobClassID={$result[$i]['JobClassID']}")->count();
			}
		}
		return $result;
	}
	
	function batchDelJobClass( $id = array() ){
		$id = YdInput::filterCommaNum($id);
		$where = 'JobClassID in('.implode(',', $id).')';
		$result = $this->where($where)->delete();
		return $result;
	}
	
	//幻灯分组是否包含数据
	function hasData($id){
		$m = D('Admin/Job');
		$id = intval($id);
		$c = $m->where("JobClassID={$id}")->count();
		if( $c > 0){
			return true;
		}else{
			return false;
		}
	}
	
	//批量排序
	function batchSortJobClass($JobClassID=array(), $JobClassOrder = array() ){
		$n = count($JobClassID);
		for($i = 0; $i < $n; $i++){
			if( is_numeric($JobClassOrder[$i]) ){
				$id = intval($JobClassID[$i]);
				$this->where("JobClassID={$id}")->setField('JobClassOrder', $JobClassOrder[$i]);
			}
		}
	}
	
}
