<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class MenuGroupModel extends Model{
	/**
	 * 获取顶层菜单对应的菜单分组
	 * @param int $MenuTopID
	 * @param bool $IsEnable
	 */
	function getMenuGroup($MenuTopID = 0, $IsEnable = 1){
        $where = array();
		if($MenuTopID>0){
			$where['MenuTopID'] = intval($MenuTopID);
		}
		if($IsEnable != -1){
			$where['IsEnable'] = intval($IsEnable);
		}
		$result = $this->where($where)->order('MenuGroupOrder asc')->select();
		return $result;
	}
	
	//$MenuOwner: 0会员， 1：管理员
	function getMenuGroupPurview($MenuOwner, $groupID, $menuTopID=-1){
        $MenuOwner = intval($MenuOwner);
		$groupID = intval($groupID);
		$menuTopID = intval($menuTopID);
		if( $MenuOwner == 1 ){
			$m = D('Admin/AdminGroup');
			$m->where("AdminGroupID=$groupID");
		}else{
			$m = D('Admin/MemberGroup');
			$m->where("MemberGroupID=$groupID");
		}
		$list = $m->getField('MenuGroupPurview');  //获取id号
		if( empty($list) ) return false;
		$where = "MenuGroupID in ($list)";
		if($menuTopID != -1){
			$where .= " and MenuTopID = $menuTopID";
		}
		$result = $this->where($where)->order('MenuGroupOrder asc')->select();
		return $result;
	}
	

}
