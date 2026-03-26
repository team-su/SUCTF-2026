<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class SupportTypeModel extends Model{
	function getSupportType(){
		$result = $this->field('SupportTypeID, SupportTypeName')->order('SupportTypeOrder asc')->select();
		return $result;
	}
}
