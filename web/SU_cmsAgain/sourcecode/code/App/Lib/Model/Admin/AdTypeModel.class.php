<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class AdTypeModel extends Model{
	function getAdType(){
		$result = $this->field('AdTypeID, AdTypeName')->order('AdTypeID asc')->select();
		return $result;
	}
}
