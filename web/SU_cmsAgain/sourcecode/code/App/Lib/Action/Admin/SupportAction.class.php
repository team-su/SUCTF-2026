<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class SupportAction extends AdminBaseAction {
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Support');
		$this->assign('Support', $m->getSupport());
		$this->display();
	}
	
	function add(){
		header("Content-Type:text/html; charset=utf-8");
	
		//模型属性信息==============================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(4);
		$Group = $m->getGroup(4);
        $this->_setAttribute($Attribute);
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================

		$this->assign('Action', __URL__.'/saveAdd');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}

	private function _setAttribute(&$Attribute){
        foreach($Attribute as $k=>$v){
            if($v['FieldName']=='SupportNumber'){
                $Attribute[$k]['DisplayType'] = 'image';
                break;
            }
        }
    }
	
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
        unset($_POST['__hash__']);
		$m = D('Admin/Support');
		if( $m->create() ){
			if($m->add()){
				YdCache::deleteAll();
				$des = var_export($_POST, true);
				WriteLog("ID:".$m->getLastInsID()." {$des}" );
				$this->ajaxReturn(null, '添加成功!' , 1);
			}else{
				$this->ajaxReturn(null, '添加失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$SupportID = $_GET['SupportID'];
		if( !is_numeric($SupportID)){
			alert("非法参数", __URL__.'/support');
		}
		//====================================
	
		//模型属性信息=================================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(4);
        $this->_setAttribute($Attribute);
		$Group = $m->getGroup(4);
	
		//获取专题数据======================================================
		$m = D('Admin/Support');
		$Info = $m->find( $SupportID );
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				$Attribute[$n]['SelectedValue'] = $Info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
			}else{
				$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//==============================================================
	
		$this->assign('HiddenName', 'SupportID');
		$this->assign('HiddenValue', $SupportID);
		$this->assign('SupportTypeID', $Info['SupportTypeID']);
		$this->assign('Action', __URL__.'/saveModify');
	
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
		unset($_POST['__hash__']);
		$m = D('Admin/Support');
		if( $m->create() ){
			if($m->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				YdCache::deleteAll();
                $des = var_export($_POST, true);
				WriteLog("ID:".$_POST['SupportID']." {$des}" );
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function del(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Support');
		$id = $_GET["SupportID"];
		$data = "#tr$id";
		if( !is_numeric($id) ){
			$this->ajaxReturn($data, '参数非法!' , 3);
		}
	
		//删除操作
		if( $m->delete($id) ){
			YdCache::deleteAll();
			WriteLog( "ID:$id");
			$this->ajaxReturn($data, '删除成功!' , 1);
		}else{
			$this->ajaxReturn($data, '删除失败!' , 0);
		}
	}
	
	function batchDel(){
		$id = $_POST['SupportID'];
		if( count($id) > 0 ){
			D('Admin/Support')->batchDelSupport($id);
			YdCache::deleteAll();
			WriteLog("ID:".implode(',', $id));
		}
		redirect(__URL__.'/index');
	}
	
	function batchSort(){
		$SupportOrder = $_POST['SupportOrder']; //排序
		$SupportID = $_POST['SupportOrderID']; //排序
		if( is_array($SupportID) && is_array($SupportOrder) && count($SupportID) > 0 && count($SupportOrder) > 0 ){
			D('Admin/Support')->batchSortSupport($SupportID, $SupportOrder);
			YdCache::deleteAll();
			WriteLog();
		}
		redirect(__URL__.'/index');
	}
	
	function third(){
		header("Content-Type:text/html; charset=utf-8");
	
		//模型属性信息==============================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(5);
		$Group = $m->getGroup(5);
	
		$m = D('Admin/Support3');
		$info = $m->findSupport3();
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				$Attribute[$n]['SelectedValue'] = $info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
			}else{
				$Attribute[$n]['DisplayValue'] = $info[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		
		//$info为空时，则增加一条信息
		$Action = !empty($info) ? __URL__.'/SaveThird' : __URL__.'/AddThird';
		$IsSave = !empty($info) ? 1 : 0;
		$this->assign('IsSave', $IsSave);
		$this->assign('Action', $Action);
		$this->assign('Group', $Group);
		$this->assign('Support3ID', $info['Support3ID']);
		$this->assign('Support3Js', $info['Support3Js']);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveThird(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Support3');
		if( $m->create() ){
			if($m->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				YdCache::deleteAll();
				$des = $_POST['Support3Js'];
				//加上此代码，防止注入php代码到模板
				if(yd_contain_php($des)){
                    $this->ajaxReturn(null, "不能包含php代码" , 0);
                }
				WriteLog($des);
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function addThird(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Support3');
		if( $m->create() ){
			if($m->add() === false){
				$this->ajaxReturn(null, '增加失败!' , 0);
			}else{
				YdCache::deleteAll();
				$this->ajaxReturn(null, '增加成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
}