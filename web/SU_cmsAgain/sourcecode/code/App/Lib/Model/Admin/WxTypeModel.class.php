<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class WxTypeModel extends Model{
	function getType($IsReply=-1, $IsEnable=-1){
		$where = "1=1";
		if( $IsReply != -1){
			$IsReply = intval($IsReply);
			$where .= " and IsReply={$IsReply}";
		}
		if( $IsEnable != -1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable={$IsEnable}";
		}
		$data = $this->where($where)->order('TypeOrder asc,TypeID desc')->select();
		return $data;
	}
}
