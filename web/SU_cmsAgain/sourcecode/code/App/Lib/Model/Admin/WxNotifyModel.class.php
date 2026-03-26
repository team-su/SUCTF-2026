<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class WxNotifyModel extends Model {
		protected $_validate = array(
			array('NotifyName', 'require', '通知名称不能为空!'),
	);
	//获取调查建议
	function getNotify($offset = -1, $length = -1, $keywords=''){
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$where = "1=1";
		if( $keywords != ''){
			$keywords = addslashes(stripslashes($keywords));
            $keywords = YdInput::checkKeyword( $keywords );
			$where .= " and ( NotifyName like '%{$keywords}%' )";
		}
		$result = $this->where($where)->order('NotifyTime desc, NotifyID desc')->select();
		return $result;
	}
	
	//获取调查数量
	function getCount($keywords=''){
		$where = "1=1";
		if( $keywords != ''){
			$keywords = addslashes(stripslashes($keywords));
            $keywords = YdInput::checkKeyword( $keywords );
			$where .= " and ( NotifyName like '%{$keywords}%' )";
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	//支持删除和批量删除
	function delNotify($id){
		$id = YdInput::filterCommaNum($id);
		$where = is_array($id) ? 'NotifyID in('.implode(',', $id).')' : "NotifyID=$id";
		$result = $this->where($where)->delete();
		return $result;
	}
}