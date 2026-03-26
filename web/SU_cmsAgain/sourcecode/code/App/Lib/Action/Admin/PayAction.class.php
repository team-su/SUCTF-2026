<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class PayAction extends AdminBaseAction{
	function index(){
		$p = array();
		$this->opIndex($p);
	}
	
	function add(){
		$Data = array('PayOrder'=>0, 'PayRate'=>0 ,'PayTypeID'=>1);
		$this->assign('Data', $Data);
		$p = array();
		$this->opAdd( false, $p );
	}
	
	function saveAdd(){
		$p = array();
		$this->opSaveAdd( $p );
	}
	
	function modify(){
		$p = array();
		$this->opModify(false, $p);
	}
	
	function saveModify(){
		$this->opSaveModify();
	}

	function del(){
		$p = array();
		$this->opDel( $p );
	}
	
	function sort(){
		$this->opSort();
	}
}