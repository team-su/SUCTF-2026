<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class BannergroupAction extends AdminBaseAction{
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/BannerGroup');
		$p = array('Count'=>1);
		$this->assign('BannerGroup', $m->getBannerGroup($p));
		$this->display();
	}
	
	function add(){
		header("Content-Type:text/html; charset=utf-8");
	
		//模型属性信息==============================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(22);
		$Group = $m->getGroup(22);
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/SaveAdd');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/BannerGroup');
		if( $m->create() ){
			if($m->add()){
				WriteLog("ID:".$m->getLastInsID() );
				$this->ajaxReturn(null, '添加成功!' , 1);
			}else{
				$this->ajaxReturn(null, '添加失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function del(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/BannerGroup');
		$id = $_GET["BannerGroupID"];
		$data = "#tr$id";
		if( !is_numeric($id) ){
			$this->ajaxReturn($data, '参数非法!' , 3);
		}
		
		if( $m->hasData($id) ){
			$this->ajaxReturn($data, '当前分组包含幻灯片数据，请先删除!' , 2);
		}
	
		//删除操作
		if( $m->delBannerGroup($id) ){
			WriteLog("ID:$id");
			$this->ajaxReturn($data, '删除成功!' , 1);
		}else{
			$this->ajaxReturn($data, '删除失败!' , 0);
		}
	}
	
	function batchDel(){
		$id = $_POST['BannerGroupID'];
		$m = D('Admin/BannerGroup');		
		foreach($id as $k=>$v){
			if( $m->hasData($v) ){
				unset( $id[$k] );
			}
		}
		if( count($id) > 0 ){			
			$m->batchDelBannerGroup($id);
			WriteLog("ID:".implode(',', $id));
		}
		redirect(__URL__."/index");
	}
	
	
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$BannerGroupID = $_GET['BannerGroupID'];
		if( !is_numeric($BannerGroupID)){
			alert("非法参数", __URL__.'/index');
		}
		//====================================
	
		//模型属性信息=================================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(22);
		$Group = $m->getGroup(22);
	
		//获取专题数据======================================================
		$m = D('Admin/BannerGroup');
		$Info = $m->find( $BannerGroupID );
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				$Attribute[$n]['SelectedValue'] = $Info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
			}else{
				$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//==============================================================
	
		$this->assign('HiddenName', 'BannerGroupID');
		$this->assign('HiddenValue', $BannerGroupID);
		$this->assign('Action', __URL__.'/saveModify');
	
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/BannerGroup');
		if( $m->create() ){
			if($m->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				WriteLog("ID:".$_POST['BannerGroupID']);
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}

	//批量排序
	function batchSort(){
		$BannerGroupOrder = $_POST['BannerGroupOrder']; //排序
		$BannerGroupID = $_POST['BannerGroupOrderID']; //排序
		if( is_array($BannerGroupID) && is_array($BannerGroupOrder) && count($BannerGroupID) > 0 && count($BannerGroupOrder) > 0 ){
			D('Admin/BannerGroup')->batchSort($BannerGroupID, $BannerGroupOrder);
			WriteLog();
		}
		redirect(__URL__."/index");
	}
}