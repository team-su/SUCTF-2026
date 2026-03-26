<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class WxApptypeModel extends Model{
	function getAppType($IsEnable=-1){
		$where = "1=1";
		if( $IsEnable != -1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable={$IsEnable}";
		}
		$data = $this->where($where)->order('AppTypeOrder asc,AppTypeID desc')->select();
		return $data;
	}
}
