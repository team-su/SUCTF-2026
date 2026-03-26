<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class AdminGroupModel extends Model{
	protected $_validate = array(
			array('AdminGroupName', 'require', '分组名称不能为空!'),
			array('AdminGroupName', '', '分组名称已经存在!', '0', 'unique'),
	);

	function getAdminGroup($adminID = -1, $p=false){
		if( $adminID != -1){
			$adminID = intval($adminID);
			$this->where("Admin={$adminID}");
		}
		$result = $this->order('AdminGroupID asc')->select();
		if( isset($p['Count']) && $p['Count'] == 1 && !empty($result)){ //统计数量
			$m = D('Admin/Admin');
			$n = is_array($result) ? count($result) : 0;
			for($i = 0; $i < $n; $i++){
				$result[$i]['AdminCount'] = $m->where("AdminGroupID={$result[$i]['AdminGroupID']}")->count();
			}
		}
		return $result;
	}
	
	function findAdminGroup($adminGroupID){
		$adminGroupID = intval($adminGroupID);
		$result = $this->find($adminGroupID);
		return $result;
	}
	
	function getGroupName($adminGroupID){
		$where['AdminGroupID'] = intval($adminGroupID);
		$result = $this->where($where)->getField('AdminGroupName');
		return $result;
	}
	
	function getMenuTopPurview($AdminGroupID){
		$where['AdminGroupID'] = intval($AdminGroupID);
		$result = $this->where($where)->getField('MenuTopPurview');
		return $result;
	}
	
	function getMenuGroupPurview($AdminGroupID){
		$where['AdminGroupID'] = intval($AdminGroupID);
		$result = $this->where($where)->getField('MenuGroupPurview');
		return $result;
	}
	
	function getMenuPurview($AdminGroupID){
		$where['AdminGroupID'] = intval($AdminGroupID);
		$result = $this->where($where)->getField('MenuPurview');
		return $result;
	}
	
	function getChannelPurview($AdminGroupID){
		//多个地方可能用到，使用缓存
		static $_cache = array();
		if (isset($_cache[$AdminGroupID])){
			return $_cache[$AdminGroupID];
		}
		$where['AdminGroupID'] = intval($AdminGroupID);
		$result = $this->where($where)->getField('ChannelPurview'.LANG_SET);
		$_cache[$AdminGroupID] = $result;
		return $result;
	}
	
	function hasData($AdminGroupID){
		$m = D('Admin/Admin');
		$where['AdminGroupID'] = intval($AdminGroupID);
		$n = $m->where($where)->count();
		if( $n > 0){
			return true;
		}else{
			return false;
		}
	}
	
}
