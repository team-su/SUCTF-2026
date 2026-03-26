<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class MemberGroupModel extends Model{
	protected $_validate = array(
			array('MemberGroupName', 'require', '会员分组名称不能为空!'),
			array('MemberGroupName', '', '会员分组名称已经存在!', '0', 'unique'),
	);
	
	function getMemberGroup($p=false){
		$result = $this->order('MemberGroupID asc')->select();
		if( isset($p['Count']) && $p['Count'] == 1 && !empty($result)){ //统计数量
			$m = D('Admin/Member');
			$n = count($result);
			for($i = 0; $i < $n; $i++){
				$result[$i]['MemberCount'] = $m->where("MemberGroupID={$result[$i]['MemberGroupID']}")->count();
			}
		}
		return $result;
	}
	
	function getMenuTopPurview($memberGroupID){
		$memberGroupID = intval($memberGroupID);
		$result = $this->where("MemberGroupID=$memberGroupID")->getField('MenuTopPurview');
		return $result;
	}
	
	function getMenuGroupPurview($memberGroupID){
		$memberGroupID = intval($memberGroupID);
		$result = $this->where("MemberGroupID=$memberGroupID")->getField('MenuGroupPurview');
		return $result;
	}
	
	function getMenuPurview($memberGroupID){
		$memberGroupID = intval($memberGroupID);
		$result = $this->where("MemberGroupID=$memberGroupID")->getField('MenuPurview');
		return $result;
	}
	
	function getChannelPurview($memberGroupID){
		$memberGroupID = intval($memberGroupID);
		$result = $this->where("MemberGroupID=$memberGroupID")->getField('ChannelPurview'.LANG_SET);
		return $result;
	}
	
	function hasData($MemberGroupID){
		$m = D('Admin/Member');
        $MemberGroupID = intval($MemberGroupID);
		$n = $m->where("MemberGroupID={$MemberGroupID}")->count();
		if( $n > 0){
			return true;
		}else{
			return false;
		}
	}
}
