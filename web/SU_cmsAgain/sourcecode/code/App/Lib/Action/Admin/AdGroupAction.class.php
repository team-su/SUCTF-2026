<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class AdGroupAction extends AdminBaseAction{
	function index(){
		$options['Parameter'] = array('AdCount'=> true);
		$this->opIndex( $options );
	}
	
	function add(){
		$options = array();
		$this->opAdd( false, $options );
	}
	
	function saveAdd(){
		$options = array();
		$this->opSaveAdd( $options );
	}
	
	function modify(){
		$options = array();
		$this->opModify(false, $options);
	}
	
	function saveModify(){
		$this->opSaveModify();
	}
	
	//删除、批量删除
	function del(){
		$options['DelFunctionName'] = 'delAdGroup';
		$this->opDel( $options );
	}
}