<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class IndexAction extends AdminBaseAction {
	function __construct(){
		parent::__construct(); //调用父类的构造函数
	}
	
	//后台管理首页
    public function index(){
        redirect(__GROUP__."/Public/adminIndex");
    }
}