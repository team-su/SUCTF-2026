<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
if (!defined('APP_NAME')) exit();
class EmptyAction extends BaseAction {
	public function _empty($method) {
        header("HTTP/1.0 404 Not Found"); //使HTTP返回404状态码
		$this->display("./Public/tpl/".C('TMPL_404') );
	}
}