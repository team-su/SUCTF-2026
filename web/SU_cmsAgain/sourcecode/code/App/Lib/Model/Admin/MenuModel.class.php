<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class MenuModel extends Model{
	
	/**
	 * 获取指定分组的菜单
	 * @param int $MenuGroupID
	 * @return array 菜单
	 */
	function getMenu($MenuGroupID = 0, $IsEnable = 1){
        $where = array();
		if($MenuGroupID>0){
			$where['MenuGroupID'] = intval($MenuGroupID);
		}
		if($IsEnable != -1){
			$where['IsEnable'] = intval($IsEnable);
		}
		$result = $this->where($where)->order('MenuOrder asc, MenuID asc')->select();
		return $result;
	}
	
	//禁用/启用菜单
	function enable($MenuID, $IsEnable){
		$MenuID = intval($MenuID);
        $IsEnable = intval($IsEnable);
		$result = $this->where("MenuID=$MenuID")->setField('IsEnable', $IsEnable);
		return $result;
	}
	
	function getWxAppMenu($IsEnable = -1){
		$where = "MenuGroupID=25";
		if( $IsEnable != -1 ){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable=$IsEnable";
		}
		$result = $this->where($where)->order('MenuOrder asc, MenuID asc')->select();
		return $result;
	}
	
	//批量排序
	function batchSortMenu($ID=array(), $Order = array() ){
		$n = count($ID);
		for($i = 0; $i < $n; $i++){
			if( is_numeric($Order[$i]) ){
				$myid = intval($ID[$i]);
				$this->where("MenuID={$myid}")->setField('MenuOrder', $Order[$i]);
			}
		}
	}
	
	//$MenuOwner: 0会员， 1：管理员, $groupID: 组ID
	function getMenuPurview($MenuOwner, $groupID){
        $MenuOwner = intval($MenuOwner);
		$groupID = intval($groupID);
		if( $MenuOwner == 1 ){
			if( $groupID == 1 ){ //超级管理员
				$this->where("IsEnable=1");
				$result = $this->order('MenuOrder asc')->select();
				return $result;
			}
			$m = D('Admin/AdminGroup');
			$m->where("AdminGroupID=$groupID");
		}else{
			$m = D('Admin/MemberGroup');
			$m->where("MemberGroupID=$groupID");
		}
		$list = $m->getField('MenuPurview');  //获取id号
		if( empty($list) ) return false;
		//菜单类型（0：菜单 ，1：标签，2：分隔符，3:栏目树形菜单）
		$this->where("IsEnable=1 and (MenuType = 3 or MenuID in ($list) )");
		$result = $this->order('MenuOrder asc, MenuID asc')->select();
		return $result;
	}

}
