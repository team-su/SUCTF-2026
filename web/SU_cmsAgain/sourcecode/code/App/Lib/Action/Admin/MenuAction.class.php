<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class MenuAction extends AdminBaseAction{
	function index(){
		$p = array();
		$p['ModuleName'] = "MenuTop";
		$p['GetFunctionName'] = "getAllMenu";
		if( isset($_REQUEST['MenuOwner']) ){
			$p['Parameter']['MenuOwner'] = $_REQUEST['MenuOwner'];
		}else{
			$p['Parameter']['MenuOwner'] = 1;//默认为1
		}
		$this->opIndex($p);
	}
	
	/**
	 * 保存所有修改
	 */
	function saveAll(){
		header("Content-Type:text/html; charset=utf-8");
		//保存顶级菜单
		$n = count( $_POST['MenuTopID'] );
		if($n>0){
			$m = D('Admin/MenuTop');
			for($i = 0; $i < $n; $i++){
				$MenuTopID = intval($_POST['MenuTopID'][$i]);
				$data = array(
					'MenuTopName' => $_POST['MenuTopName'][$i],
					'MenuTopOrder' => $_POST['MenuTopOrder'][$i],
				);
				$m->where("MenuTopID=$MenuTopID")->setField( $data );
			}
		}
		
		//保存菜单分组
		$n = count( $_POST['MenuGroupID'] );
		if($n>0){
			$m = D('Admin/MenuGroup');
			for($i = 0; $i < $n; $i++){
				$MenuGroupID = intval($_POST['MenuGroupID'][$i]);
				$data = array(
						'MenuGroupName' => $_POST['MenuGroupName'][$i],
						'MenuGroupOrder' =>  $_POST['MenuGroupOrder'][$i],
				);
				$m->where("MenuGroupID=$MenuGroupID")->setField( $data );
			}
		}
		
		//保存菜单
		$n = count( $_POST['MenuID'] );
		if($n>0){
			$m = D('Admin/Menu');
			for($i = 0; $i < $n; $i++){
				$MenuID = intval($_POST['MenuID'][$i]);
				$data = array(
						'MenuName' => $_POST['MenuName'][$i],
						'MenuOrder' => $_POST['MenuOrder'][$i],
				);
				$m->where("MenuID=$MenuID")->setField( $data );
			}
		}
		WriteLog();
		$MenuOwner = intval($_REQUEST['MenuOwner']);
		redirect(__URL__."/index/MenuOwner/$MenuOwner");
	}
}