<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class LinkAction extends AdminBaseAction{
	//友情连接分类
	function classIndex(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/LinkClass');
		$p = array('Count'=>1);
		$this->assign('LinkClass', $m->getLinkClass($p));
		$this->display();
	}
	
	/**
	 * 添加友情链接分类
	 */
	function addClass(){
		header("Content-Type:text/html; charset=utf-8");
	
		//模型属性信息==============================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(9);
		$Group = $m->getGroup(9);
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/saveAddClass');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveAddClass(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/LinkClass');
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
	
	function delClass(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/LinkClass');
		$id = $_GET["LinkClassID"];
		$data = "#tr$id";
		if( !is_numeric($id) ){
			$this->ajaxReturn($data, '参数非法!' , 3);
		}
		
		if( $m->hasData($id) ){
			$this->ajaxReturn($data, '当前分类包含友情链接数据，请先删除!' , 2);
		}
	
		//删除操作
		if( $m->delete($id) ){
			WriteLog( "ID:$id");
			$this->ajaxReturn($data, '删除成功!' , 1);
		}else{
			$this->ajaxReturn($data, '删除失败!' , 0);
		}
	}
	
	function batchDelClass(){
		$id = $_POST['LinkClassID'];
		//若分类存在数据，则不删除
		$m = D('Admin/LinkClass');
		foreach($id as $k=>$v){
			if( $m->hasData($v) ){
				unset( $id[$k] );
			}
		}
		
		if( count($id) > 0 ){
			$m->batchDelLinkClass($id);
			WriteLog("ID:".implode(',', $id));
		}
		redirect(__URL__."/classIndex");
	}
	
	function batchSortClass(){
		$LinkClassOrder = $_POST['LinkClassOrder']; //排序
		$LinkClassID = $_POST['LinkClassOrderID']; //排序
		if( is_array($LinkClassID) && is_array($LinkClassOrder) && count($LinkClassID) > 0 && count($LinkClassOrder) > 0 ){
			D('Admin/LinkClass')->batchSortLinkClass($LinkClassID, $LinkClassOrder);
			WriteLog();
		}
		redirect(__URL__."/classIndex");
	}
	
	function modifyClass(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$LinkClassID = $_GET['LinkClassID'];
		if( !is_numeric($LinkClassID)){
			alert("非法参数", __URL__.'/classIndex');
		}
		//====================================
	
		//模型属性信息=================================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(9);
		$Group = $m->getGroup(9);
	
		//获取专题数据======================================================
		$m = D('Admin/LinkClass');
		$Info = $m->find( $LinkClassID );
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				$Attribute[$n]['SelectedValue'] = $Info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
			}else{
				$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//==============================================================
	
		$this->assign('HiddenName', 'LinkClassID');
		$this->assign('HiddenValue', $LinkClassID);
		$this->assign('Action', __URL__.'/saveModifyClass');
	
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveModifyClass(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/LinkClass');
		if( $m->create() ){
			if($m->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				WriteLog("ID:".$_POST['LinkClassID'] );
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	//友情链接
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$LinkClassID = isset($_REQUEST['LinkClassID']) ? $_REQUEST['LinkClassID'] : -1;
		
		$m = D('Admin/Link');
		import("ORG.Util.Page");
		$TotalPage = $m->getLinkCount($LinkClassID); //获取留言总数
		$PageSize = $this->AdminPageSize;
		
		$Page = new Page($TotalPage, $PageSize);
		$Page->rollPage = $this->AdminRollPage;
		if( $LinkClassID != -1){
			$Page->parameter = "&LinkClassID=$LinkClassID";
		}
		$ShowPage = $Page->show();
		
		$data= $m->getLink($Page->firstRow, $Page->listRows, $LinkClassID);
		
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Link', $data);
		$this->assign('LinkClassID', $LinkClassID);
		$this->assign('Page', $ShowPage); //分页条
		$this->display();
	}

    //预处理POST变量
    private function prePost(&$p){
        //处理复选框显示
        foreach ($p as $k=>$v){
            if( is_array($v) ){
                $p[$k] = implode(',', $v);
            }
        }
    }
	
	function add(){
		header("Content-Type:text/html; charset=utf-8");
	
		//模型属性信息==============================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(10);
		$Group = $m->getGroup(10);
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/saveAdd');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
        $this->prePost($_POST);
		$m = D('Admin/Link');
		if( $m->create() ){
			if($m->add()){
                $des = $_POST['LinkName'];
				WriteLog("ID:".$m->getLastInsID()." {$des}" );
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
		$LinkClassID = isset($_REQUEST['LinkClassID']) && is_numeric($_REQUEST['LinkClassID']) ? (int)$_REQUEST['LinkClassID'] : -1;
		$LinkID = $_GET["LinkID"];
		$NowPage = (int)$_GET["p"];
		
		if( is_numeric($LinkID) && is_numeric($NowPage)){
			$m = D('Admin/Link');
			$fileToDel = $m->getAttachment($LinkID);
			if( $m->delete($LinkID) ){
				batchDelFile($fileToDel);
				WriteLog("ID:$LinkID");
				redirect(__URL__."/index?LinkClassID=$LinkClassID&p=$NowPage");
			}
		}
	}
	
	function batchDel(){
		$LinkClassID = isset($_REQUEST['LinkClassID']) ? $_REQUEST['LinkClassID'] : -1;
		$id = $_POST['LinkID'];
		$NowPage = (int)$_POST["NowPage"];
		if( count($id) > 0 ){
			$m=D('Admin/Link');
			$fileToDel = $m->getAttachment($id);
			$m->batchDelLink($id);
			batchDelFile($fileToDel);
			WriteLog("ID:".implode(',', $id));
		}
		redirect(__URL__."/index?LinkClassID=$LinkClassID&p=$NowPage");
	}
	
	function batchSort(){
		$LinkClassID = isset($_REQUEST['LinkClassID']) ? $_REQUEST['LinkClassID'] : -1;
		$LinkOrder = $_POST['LinkOrder']; //排序
		$LinkID = $_POST['LinkOrderID']; //排序
		$NowPage = (int)$_POST["NowPage"];
		if( is_array($LinkID) && is_array($LinkOrder) && count($LinkID) > 0 && count($LinkOrder) > 0 ){
			$m = D('Admin/Link');
			$m->batchSortLink($LinkID, $LinkOrder);
			WriteLog();
		}
		redirect(__URL__."/index?LinkClassID=$LinkClassID&p=$NowPage");
	}
	
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$LinkID = $_GET['LinkID'];
		if( !is_numeric($LinkID)){
			alert("非法参数", __URL__.'/index');
		}
		//====================================
	
		//模型属性信息=================================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(10);
		$Group = $m->getGroup(10);
	
		//获取专题数据======================================================
		$m = D('Admin/Link');
		$Info = $m->find( $LinkID );
		$total = count($Attribute);
		for($n = 0; $n < $total; $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				$Attribute[$n]['SelectedValue'] = $Info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
			}else{
				$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//==============================================================
	
		$this->assign('HiddenName', 'LinkID');
		$this->assign('HiddenValue', $LinkID);
		$this->assign('Action', __URL__.'/saveModify');
	
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
        $this->prePost($_POST);
		$m = D('Admin/Link');
		if( $m->create() ){
			if($m->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
                $des = $_POST['LinkName'];
				WriteLog("ID:".$_POST['LinkID']." {$des}" );
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
}