<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class ChannelAction extends MemberBaseAction {
	function GetSpecial(){
		$ChannelID = $_GET['ChannelID'];
		if( is_numeric($ChannelID) ){
			$s = D('Admin/Special');
			$SpecialInfo = $s->getSpecial( array('IsEnable'=>1) );
			$option = "<optgroup label='请选择所属专题（按Ctrl+左键可进行多选）'>";
			foreach($SpecialInfo as $key=>$value){
				$v = $value['SpecialID'];
				$t = $value['SpecialName'];
				$option .= "<option value='$v'>$t</option>";
			}
			$option .= '</optgroup>';
			$this->ajaxReturn(null, $option , 1);
		}
	}
	
	/**
	 * 单页频道
	 */
	function single(){
		header("Content-Type:text/html; charset=utf-8");
		
		$ChannelID = $_GET['ChannelID'];
		if( !is_numeric($ChannelID) ){
			alert("非法参数", __URL__.'/single');
		}

		//模型属性信息==============================================
		$c = D('Admin/Channel');
		$ChannelInfo = $c->find( $ChannelID );
		
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute($ChannelInfo['ChannelModelID']);
		$Group = $m->getGroup($ChannelInfo['ChannelModelID']);
		
		for($n = 0; $n < count($Attribute); $n++){
		   if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				if ( $Attribute[$n]['DisplayType'] == 'channelselect'){
					$Attribute[$n]['SelectedValue'] = $ChannelInfo[ 'ChannelID' ]; //获取频道设置值
					$Attribute[$n]['FirstValue'] = "0"; //FirstValue
					$Attribute[$n]['FirstText'] = "作为主频道"; //FirstText
				}else{
					$Attribute[$n]['SelectedValue'] = $ChannelInfo[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
				}
			}else{
				$Attribute[$n]['DisplayValue'] = $ChannelInfo[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/saveModify');
		$this->assign('ChannelID', $ChannelID);
		$this->assign('ChannelName', $ChannelInfo['ChannelName']);
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	/**
	 * 链接频道
	 */
	function link(){
		header("Content-Type:text/html; charset=utf-8");
	
		$ChannelID = $_GET['ChannelID'];
		if( !is_numeric($ChannelID) ){
			alert("非法参数", __URL__.'/Save');
		}
	
		//模型属性信息==============================================
		$c = D('Admin/Channel');
		$ChannelInfo = $c->find( $ChannelID );
	
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute($ChannelInfo['ChannelModelID']);
		$Group = $m->getGroup($ChannelInfo['ChannelModelID']);
	
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
					$Attribute[$n]['SelectedValue'] = $ChannelInfo[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
			}else{
				$Attribute[$n]['DisplayValue'] = $ChannelInfo[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/saveModify');
		$this->assign('ChannelID', $ChannelID);
		$this->assign('ChannelName', $ChannelInfo['ChannelName']);
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}

	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
		return; //会员不允许修改频道
		$n = count($_POST);
		foreach ($_POST as $k=>$v){
			if( is_array($v) ){
				$_POST[$k] = implode(',', $v);
			}
		}
		
		//todo:这里必须加上频道权限控制
	
		//Html存在，并且ChannelName不为空在自动生成拼音文件名（单页频道不生成）
		if( isset($_POST['Html']) && empty($_POST['Html'])  && !empty($_POST['ChannelName']) ){
			$_POST['Html'] = yd_pinyin( $_POST['ChannelName'] );
		}
		if( !isset($_POST['ReadLevel']) ) $_POST['ReadLevel'] = '';
		
		//必须进行XSS过滤防止攻击=======================================
		//yd_remove_xss过滤会有bug，经测试22,33，过滤后变为：2233
		//$_POST = yd_remove_xss( $_POST );
		$_POST = YdInput::checkInfo( $_POST );
		//========================================================
		
		$c = D('Admin/Channel');
		if( $c->create() ){
			if($GLOBALS['Config']['AUTO_UPLOAD_ENABLE']==1 && stripos($c->ChannelContent , '<img') !== false ){
				//上传远程图片有，需要同步更新编辑器内容
				$temp = yd_upload_content($c->ChannelContent);
				$c->ChannelContent = $temp[2];
				if($c->save() === false){
					$this->ajaxReturn(null, '修改失败!' , 0);
				}else{
					//需要做是否启用自动上传远程图片判断 0:远程地址，1：本地地址
					$this->ajaxReturn($temp[0], $temp[1] , 1);
				}
			}else{
				if($c->save() === false){
					$this->ajaxReturn(null, '修改频道失败!' , 0);
				}else{
					WriteLog("ID:".$_POST['ChannelID'], array('LogType'=>4,'UserAction'=>'保存频道修改'));
					$this->ajaxReturn(null, '修改频道成功!' , 1);
				}
			}
		}else{
			$this->ajaxReturn(null, $c->getError() , 0);
		}
	}
}