<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class ChannelmodelAction extends AdminBaseAction {
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/ChannelModel');
		$data = $m->getChannelModel(0,-1);
		
		$n = is_array($data) ? count($data) : 0;
		for($i = 0; $i < $n; $i++){
			$data[$i]['CanDelete'] = ($m->canDelete( $data[$i]['ChannelModelID'] ) ) ? 1 : 0;
		}
		
		$this->assign('ChannelModel', $data);
		$this->display();
	}
	
	function add(){
		header("Content-Type:text/html; charset=utf-8");
		//模型属性信息==============================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(19);
		$Group = $m->getGroup(19);
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/saveAdd');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/ChannelModel');
		if( $m->create() ){
			$m->startTrans();
			$ChannelModelID = $m->add();
			if($ChannelModelID && $m->InitModel( $ChannelModelID ) ){
				$m->commit();			
				YdCache::deleteTemp(); //清楚缓存
				WriteLog("ID:".$m->getLastInsID() );
				$this->ajaxReturn(null, '添加成功!' , 1);
			}else{
				$m->rollback();
				$this->ajaxReturn(null, '添加失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$ChannelModelID = $_GET['ChannelModelID'];
		if( !is_numeric($ChannelModelID)){
			alert("非法参数", __URL__.'/index');
		}
		//====================================
	
		//模型属性信息=================================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(19);
		$Group = $m->getGroup(19);
	
		//获取模型数据======================================================
		$s = D('Admin/ChannelModel');
		$ChannelModelInfo = $s->find( $ChannelModelID );
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){	
				if ( $Attribute[$n]['DisplayType'] == 'channelselect'){
					$Attribute[$n]['SelectedValue'] = $ChannelModelInfo[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
					$Attribute[$n]['FirstValue'] = "0"; //FirstValue
					$Attribute[$n]['FirstText'] = "所有频道"; //FirstText
				}else{
					$Attribute[$n]['SelectedValue'] = $ChannelModelInfo[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
				}
			}else{
				$Attribute[$n]['DisplayValue'] = $ChannelModelInfo[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//==============================================================
	
		$this->assign('ChannelModelID', $ChannelModelID);
		$this->assign('Action', __URL__.'/SaveModify');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
		$c = D('Admin/ChannelModel');
		if( $c->create() ){
			if($c->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				YdCache::deleteTemp(); //清楚缓存
				WriteLog("ID:".$_POST['ChannelModelID'] );
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $c->getError() , 0);
		}
	}
	
	function batchSort(){
		$Order = $_POST['ChannelModelOrder'];
		$ID = $_POST['ChannelModelID'];
		if( is_array($ID) && is_array($Order) && count($ID) > 0 && count($Order) > 0 ){
			D('Admin/ChannelModel')->batchSortChannelModel($ID, $Order);
			YdCache::deleteTemp(); //清楚缓存
			WriteLog();
		}
		redirect(__URL__."/index");
	}
	
	function del(){
		header("Content-Type:text/html; charset=utf-8");
		$ChannelModelID = $_GET["ChannelModelID"];
		if( is_numeric($ChannelModelID) ){
			$m = D('Admin/ChannelModel');
			if( $m->canDelete($ChannelModelID) ){
				$r1 = $m->delete($ChannelModelID);
				$r2 = $m->deleteAttribute($ChannelModelID);
				YdCache::deleteTemp(); //清楚缓存
				WriteLog("ID:$ChannelModelID");
			}
		}
		redirect(__URL__.'/index');
	}
	
	function viewField(){
		header("Content-Type:text/html; charset=utf-8");
		$ChannelModelID = (int)$_GET["ChannelModelID"];
        $ChannelID = 0;
		switch($ChannelModelID){
			case 6: //留言
				$ReturnUrl = __GROUP__."/GuestBook/index";
				break;
			case 37: //反馈
                $ChannelID = (int)$_GET["ChannelID"];
                //$ReturnUrl = __GROUP__."/Info/Feedback/ChannelID/{$ChannelID}";
                $ReturnUrl = "";  //不显示返回。由于传了参数频道ID，显示返回有问题
                break;
			case 26: //订单
				$ReturnUrl = __GROUP__."/Order/index";
				break;
			default:
				$ReturnUrl = __URL__."/index";
		}

		if( is_numeric($ChannelModelID) ){
			$m = D('Admin/Attribute');
			$data = $m->getAttribute($ChannelModelID, true, -1, -1);
			$type = $m->getDisplayType();
            $t = array();
			foreach ($type as $v){
				$t[ $v['DisplayTypeID'] ] = $v['DisplayTypeName'];
			}
			$n = is_array($data) ? count($data) : 0;
			for($i = 0; $i < $n; $i++){
			    $key = $data[$i]['DisplayType'];
				$data[$i]['DisplayTypeName'] = isset($t[$key]) ? $t[$key] : '';
			}
			$d = array();
			for($i = 0; $i < $n; $i++){
				if( $data[$i]['GroupID'] == 0 ){ //分组
					$d[] = $data[$i];
					$CurrentGroup = $data[$i]['AttributeID'];
					for($j = 0; $j < $n; $j++){
						if($data[$j]['GroupID'] == $CurrentGroup){
							$d[] = $data[$j];
						}
					}
				}
			}
			
			if( $ChannelModelID == 6 || $ChannelModelID == 26 || $ChannelModelID==37){
				//去掉分组显示
				$n = count($d);
				for($i=0; $i<$n; $i++){
					if( $d[$i]['GroupID'] == 0 ){
						unset($d[$i]);
						break;
					}
				}
			}

            $this->assign('ChannelID', $ChannelID);
			$this->assign('ChannelModelID', $ChannelModelID);
			$this->assign('Attribute', $d);
			$this->assign('ReturnUrl', $ReturnUrl);
			unset($data, $d);
			$this->display();
		}
	}
	
	function modifyField(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查==================================
		$ChannelModelID = (int)$_GET['ChannelModelID'];
		$AttributeID = (int)$_GET['AttributeID'];
		if( !is_numeric($ChannelModelID) || !is_numeric($AttributeID)){
			alert("非法参数", __URL__.'/index');
		}
		//===========================================
	
		//模型属性信息===================================================================
		$m = D('Admin/Attribute');
		$id = ( $m->IsGroup($AttributeID) ? 21 : 20 );
		$Attribute = $m->getAttribute($id);
		$Group = $m->getGroup($id);
				
		$ChannelModelName = D('Admin/ChannelModel')->where("ChannelModelID=$ChannelModelID")->getField('ChannelModelName');
			
		//获取模型数据===================================================================
		$m = D('Admin/Attribute');
		$info = $m->find( $AttributeID );
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				if ( $Attribute[$n]['DisplayType'] == 'displaytypeselect'){
					$Attribute[$n]['SelectedValue'] = $info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
				}else if($Attribute[$n]['DisplayType'] == 'attributegroupselect'){
					$Attribute[$n]['SelectedValue'] = $info[ $Attribute[$n]['FieldName'] ];
					$Attribute[$n]['ChannelModelID'] = $ChannelModelID;
				}else{
					$Attribute[$n]['SelectedValue'] = $info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
				}
			}else if ( strtolower($Attribute[$n]['DisplayType']) == 'label'){
				$Attribute[$n]['DisplayValue'] =  "<b style='color:blue'>".$info[ $Attribute[$n]['FieldName'] ]."</b>";
			}else{
				$Attribute[$n]['DisplayValue'] = $info[ $Attribute[$n]['FieldName'] ];
			}
			
			if ( strtolower($Attribute[$n]['FieldName']) == 'channelmodelid'){
				$Attribute[$n]['DisplayValue'] =  "<b style='color:blue'>$ChannelModelName</b>"; 
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//==============================================================
	
		$this->assign('ChannelModelID', $ChannelModelID);
		$this->assign('AttributeID', $AttributeID);
		$this->assign('Action', __URL__.'/saveModifyField');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveModifyField(){
		header("Content-Type:text/html; charset=utf-8");
		$c = D('Admin/Attribute');
		if( $c->create() ){
			if($c->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				YdCache::deleteTemp();
				WriteLog("ID:".$_POST['AttributeID'] );
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $c->getError() , 0);
		}
	}
	
	function batchSortField(){
		$ChannelModelID = $_GET['ChannelModelID'];
		$Order = $_POST['DisplayOrder'];
		$ID = $_POST['AttributeID'];
		if( is_array($ID) && is_array($Order) && count($ID) > 0 && count($Order) > 0 ){
			D('Admin/Attribute')->batchSortAttribute($ID, $Order);
			YdCache::deleteTemp(); //清除缓存
			WriteLog();
		}
        $url = __URL__."/viewField/ChannelModelID/$ChannelModelID";
        $ChannelID = isset($_GET['ChannelID']) ? (int)$_GET['ChannelID'] : 0;
		if($ChannelID > 0){
            $url .= "/ChannelID/{$ChannelID}";
        }
		redirect($url);
	}
	
	function clearCache(){
		YdCache::deleteTemp();
		$this->ajaxReturn(null, '清除频道模型缓存成功!' , 1);
	}
}
?>