<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class TagAction extends AdminBaseAction {
	/**
	 * 显示自定义标签管理首页
	 */
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Tag');
		import("ORG.Util.Page");
		$TotalPage = $m->getCount(); //获取留言总数
		$PageSize = $this->AdminPageSize;
		
		$Page = new Page($TotalPage, $PageSize);
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		
		$data= $m->getTag($Page->firstRow, $Page->listRows);
		
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Tag', $data);
		$this->assign('Page', $ShowPage); //分页条
		$this->display();
	}
	
	/**
	 * 添加自定义标签
	 */
	function add(){
		header("Content-Type:text/html; charset=utf-8");
		//模型属性信息==============================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(17);
		$Group = $m->getGroup(17);
	
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/saveAdd');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	/**
	 * 保存自定义标签
	 */
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
		unset($_POST['__hash__']);
		$m = D('Admin/Tag');
		if( $m->create() ){
			if($m->add()){
				$id = $m->getLastInsID();
				$des = var_export($_POST, true);
				WriteLog("ID:{$id} {$des}");
                YdCache::writeConfig();
				$this->ajaxReturn(null, '添加成功!' , 1);
			}else{
				$this->ajaxReturn(null, '添加失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	/**
	 * 修改自定义标签
	 */
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查
		$TagID = $_GET['TagID'];
		if( !is_numeric($TagID)){
			alert("非法参数", __URL__.'/index');
		}
	
		//模型属性信息
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(17);
		$Group = $m->getGroup(17);
	
		//获取专题数据
		$m = D('Admin/Tag');
		$Info = $m->find( $TagID );
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				$Attribute[$n]['SelectedValue'] = $Info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
			}else{
				$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息

		$this->assign('HiddenName', 'TagID');
		$this->assign('HiddenValue', $TagID);
		$this->assign('Action', __URL__.'/saveModify');
	
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	/**
	 * 更新自定义标签
	 */
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
        unset($_POST['__hash__']);
		$m = D('Admin/Tag');
		if( $m->create() ){
			if($m->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				YdCache::writeConfig();
                $des = var_export($_POST, true);
				WriteLog("ID:{$_POST['TagID']} {$des}");
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	/**
	 * 删除自定义标签
	 */
	function del(){
		header("Content-Type:text/html; charset=utf-8");
		$TagID = $_GET["TagID"];
		$p = $_GET["p"];
	
		if( is_numeric($TagID) && is_numeric($p)){
			$m = D('Admin/Tag');
			if( $m->delete($TagID) ){
				YdCache::writeConfig();
				WriteLog("ID:$TagID");
			}
		}
		redirect(__URL__."/index/p/$p");
	}
	
	function batchDel(){
		$id = $_POST['TagID'];
		$NowPage = (int)$_POST["NowPage"];
		if( count($id) > 0 ){
			$m = D('Admin/Tag');
			if( $m->batchDelTag($id) ){
				YdCache::writeConfig();
				WriteLog("ID:".implode(',', $id));
			}
		}
		redirect(__URL__."/index/p/$NowPage");
	}
}