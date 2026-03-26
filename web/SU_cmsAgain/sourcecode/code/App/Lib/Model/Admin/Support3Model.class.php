<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class Support3Model extends Model{
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function findSupport3(){
		$where = get_language_where();
		$data = $this->where($where)->find();
		return $data;
	}
	
}

