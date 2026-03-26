<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class BannerAction extends AdminBaseAction {
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Banner');
        $IsEnable = isset($_POST['IsEnable']) ? intval($_POST['IsEnable']) : -1;
		$data = $m->getBanner($IsEnable);
        $this->assign('IsEnable',  $IsEnable);
		$this->assign('Banner',  $data);
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
		$Attribute = $m->getAttribute(3);
		$Group = $m->getGroup(3);
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/SaveAdd');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
        $this->prePost($_POST);
		$m = D('Admin/Banner');
		if( $m->create() ){
			if($m->add()){
				YdCache::deleteHome();
				WriteLog("ID:".$m->getLastInsID() );
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
		$BannerID = $_GET['BannerID'];
		if( !is_numeric($BannerID)){
			alert("非法参数", __URL__.'/banner');
		}
		//====================================
	
		//模型属性信息=================================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(3);
		$Group = $m->getGroup(3);
	
		//获取专题数据======================================================
		$s = D('Admin/Banner');
		$BannerInfo = $s->find( $BannerID );
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				$Attribute[$n]['SelectedValue'] = $BannerInfo[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
			}else{
				$Attribute[$n]['DisplayValue'] = $BannerInfo[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//==============================================================
	
		$this->assign('HiddenName', 'BannerID');
		$this->assign('HiddenValue', $BannerID);

		$this->assign('Action', __URL__.'/saveModify');
		
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
        $this->prePost($_POST);
		$b = D('Admin/Banner');
		if( $b->create() ){
			if($b->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				YdCache::deleteHome();
				WriteLog("ID:".$_POST['BannerID'] );
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $b->getError() , 0);
		}
	}
	
	function del(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Banner');
		$BannerID = $_GET["BannerID"];
		$data = "#tr$BannerID";
		if( !is_numeric($BannerID) ){
			$this->ajaxReturn($data, '参数非法!' , 3);
		}
		//删除操作
		$fileToDel = $m->getAttachment($BannerID);
		if( $m->delete($BannerID) ){
			YdCache::deleteHome();
			batchDelFile($fileToDel);
			WriteLog("ID:$BannerID");
			$this->ajaxReturn($data, '删除成功!' , 1);
		}else{
			$this->ajaxReturn($data, '删除失败!' , 0);
		}
	}

	function batchDel(){
		$id = $_POST['BannerID'];
		if( count($id) > 0 ){
			$m = D('Admin/Banner');
			$fileToDel = $m->getAttachment($id);
			$m->batchDelBanner($id);
			batchDelFile($fileToDel); //删除图片文件
			YdCache::deleteHome();
			WriteLog("ID:".implode(',', $id));
		}
		redirect(__URL__."/index");
	}
	
	function batchSort(){
		$BannerOrder = $_POST['BannerOrder']; //排序
		$BannerID = $_POST['BannerOrderID']; //排序
		if( is_array($BannerID) && is_array($BannerOrder) && count($BannerID) > 0 && count($BannerOrder) > 0 ){
			D('Admin/Banner')->batchSortBanner($BannerID, $BannerOrder);
			YdCache::deleteHome();
			WriteLog();
		}
		redirect(__URL__."/index");
	}

}