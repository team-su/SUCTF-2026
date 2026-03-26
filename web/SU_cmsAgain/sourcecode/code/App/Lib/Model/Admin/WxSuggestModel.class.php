<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class WxSuggestModel extends Model {
	protected $_validate = array();
	//获取调查建议
	function getSuggest($offset = -1, $length = -1, $AppID = false, $keywords=''){
		$this->field('b.MemberRealName,b.MemberMobile,b.MemberGender, a.*');
		$this->table($this->tablePrefix.'wx_suggest a');
		$this->join(' left join '.$this->tablePrefix.'member b On a.FromUser = b.FromUser');
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$where = "1=1";
		if( $AppID !== false ){
			$AppID = intval($AppID);
			$where .= " and a.AppID = {$AppID}";
		}
		if( $keywords != ''){
			$keywords = addslashes(stripslashes($keywords));
            $keywords = YdInput::checkKeyword( $keywords );
			$where .= " and ( b.MemberRealName like '%{$keywords}%' or b.MemberMobile like  '%{$keywords}%' )";
		}
		$result = $this->where($where)->order('a.SuggestID desc')->select();
		return $result;
	}
	
	//获取调查数量
	function getCount($AppID = false, $keywords=''){
		$this->table($this->tablePrefix.'wx_suggest a');
		$this->join(' left join '.$this->tablePrefix.'member b On a.FromUser = b.FromUser');
		$where = "1=1";
		if( $AppID !== false ){
			$AppID = intval($AppID);
			$where .= " and a.AppID = $AppID";
		}
		if( $keywords != ''){
			$keywords = addslashes(stripslashes($keywords));
            $keywords = YdInput::checkKeyword( $keywords );
			$where .= " and ( b.MemberRealName like '%{$keywords}%' or b.MemberMobile like  '%{$keywords}%' )";
		}
		$n = $this->where($where)->count();
		return $n;
	}

	//保存用户建议
	function addSuggest($AppID, $fromUser, $suggest){
        $fromUser = YdInput::checkLetterNumber($fromUser);
		if(empty($suggest)) return false;
		$AppID = intval($AppID);
		$where['AppID'] = $AppID;
		$where['FromUser'] = $fromUser;
		$n = $this->where($where)->count();
		if($n>0) return false;  //不能重复插入
		$data = array(
				'AppID'=>$AppID,
				'FromUser'=>$fromUser,
				'SuggestContent'=>$suggest,
				'SuggestTime'=>date('Y-m-d H:i:s'),
		);
		$result = $this->add($data);
		return $result;
	}
	
	//删除指定调查所有建议
	function delSuggest($appID){
		$appID = YdInput::filterCommaNum($appID);
		if( is_array($appID)){
			$where = 'AppID in('.implode(',', $appID).')';
		}else{
			$where = "AppID={$appID}";
		}
		return $this->where($where)->delete();
	}
}