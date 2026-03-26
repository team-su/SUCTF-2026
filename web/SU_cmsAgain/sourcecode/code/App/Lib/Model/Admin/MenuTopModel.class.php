<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class MenuTopModel extends Model{
	/*
	function getMenuTop1($MenuOwner = 1, $IsEnable = 1){
		$result = $this->where("MenuOwner=$MenuOwner and IsEnable=$IsEnable")->order('MenuTopOrder asc')->select();
		return $result;
	}
	*/
	function getMenuTop($p = array(), $field = true, $except = false){
		$defaults = array("MenuOwner"=>0, "IsEnable"=>1);
		$where = array_merge($defaults, $p);
		if( $where['IsEnable'] === -1 ) unset($where['IsEnable']);
		if($field){
			$field = YdInput::checkTableField($field);
		}
		$result = $this->field($field, $except)->where($where)->order('MenuTopOrder asc')->select();
		return $result;
	}
	
	//$MenuOwner: 0会员， 1：管理员
	function getMenuTopPurview($MenuOwner, $groupID){
        $MenuOwner = intval($MenuOwner);
		$groupID = intval($groupID);
		if( $MenuOwner == 1 ){ //管理员
			if( $groupID == 1 ){ //超级管理员
				$this->where("MenuOwner={$MenuOwner} and IsEnable=1");
				$result = $this->order('MenuTopOrder asc')->select();
				return $result;
			}
			$m = D('Admin/AdminGroup');
			$m->where("AdminGroupID={$groupID}");
		}else{
			$m = D('Admin/MemberGroup');
			$m->where("MemberGroupID={$groupID}");
		}
		$list = $m->getField('MenuTopPurview');  //获取id号
		if( empty($list) ) return false;
		$result = $this->where("MenuTopID in ({$list}) and IsEnable=1")->order('MenuTopOrder asc')->select();
		return $result;
	}
	
	
	function getAllMenu($p = array()){
		$where['MenuOwner'] = intval($p['MenuOwner']);
		if( $p['MenuOwner'] == 1){ //管理员端
			$where['MenuTopID'] = array('neq', 12);
			$result = $this->where($where)->order('MenuTopOrder asc')->select();
			if(!empty($result)){
				$n = is_array($result) ? count($result) : 0;
				$m = D('Admin/MenuGroup');
				$m1 = D('Admin/Menu');
				for($i=0; $i<$n; $i++){
					$result[$i]['MenuGroup'] = $m->getMenuGroup($result[$i]['MenuTopID'], -1);
					if(!empty($result[$i]['MenuGroup'])){
						$MenuGroup = &$result[$i]['MenuGroup'];
						$n1 = count($MenuGroup);
						for($j=0; $j<$n1; $j++){
							$MenuGroup[$j]['Menu'] = $m1->getMenu($MenuGroup[$j]['MenuGroupID'], -1);
						}
					}
				}
			}
		}else{ //会员端
			$where['MenuID'] = array('not in', '66');
			$this->field("c.*");
			$this->table($this->tablePrefix.'menu_top a');
			$this->join(" Inner Join ".$this->tablePrefix.'menu_group b On a.MenuTopID = b.MenuTopID');
			$this->join(" Inner Join ".$this->tablePrefix.'menu c On b.MenuGroupID = c.MenuGroupID');
			$result = $this->where($where)->order('c.MenuOrder asc, c.MenuID asc')->select();
		}
		return $result;
	}
	
	
}
