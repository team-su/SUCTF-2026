<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
if (!defined('APP_NAME')) exit();
class MemberBaseAction extends BaseAction {
	function _initialize(){
		if( !$this->isLogin() ){ //没有登录，将返回网站首页
		    $this->_checkAjaxRequest();
			redirect( HomeUrl() );
		}
		
		if( !$this->checkPurview() ){ //没有权限，将返回后页欢迎页
			redirect( HomeUrl() );
		}

		parent::_initialize();
		$this->_assignPublicVar();
	}

    //检查后端是否是post请求，如果是就以ajax
    private function _checkAjaxRequest(){
        $with = strtolower($_SERVER['HTTP_X_REQUESTED_WITH']);
        if($with=='xmlhttprequest'){
            $this->ajaxReturn(null, "登录超时，请重新登录！" , 0);
        }
    }
	
	private function _assignPublicVar(){
		$this->assign('IsAdmin', (int)session('IsAdmin'));
		$this->assign('MemberID', (int)session('MemberID') );
		$this->assign("MemberName", session("MemberName") );
		$this->assign("MemberGroupName", session("MemberGroupName") );
	}
	
	//是否登录
	function isLogin(){
		$b = session("?MemberID");
		return $b;
	}
	
	//权限检查 0:检查菜单，1：顶层菜单，2：树形频道
	function checkPurview(){
		$mName = strtolower( MODULE_NAME );
		$aName = strtolower( ACTION_NAME );
		$m = D('Admin/MemberGroup');
		$gid = (int)session('MemberGroupID');
		if( $mName == 'info' ) { //树形频道权限判断
			$list = $m->getChannelPurview( $gid );
			$id = $_REQUEST['ChannelID'];
		}else if( $mName == 'public' && $aName == 'memberleft') {//顶层菜单权限判断
			$list = $m->getMenuTopPurview( $gid );
			$id = $_REQUEST['MenuTopID'];
		}else{ //菜单权限判断
			$list = $m->getMenuPurview( $gid );
			$m1 = D('Admin/MenuOperation');
			$id = $m1->getMenuID(ACTION_NAME, MODULE_NAME, GROUP_NAME);
		}
		
		if( !is_numeric($id) ) return true; //不存在的菜单，不控制权限
		$list = explode(',', $list);
		if( in_array($id, $list) ){
			return true;
		}else{
			return false;
		}
	}
	

}