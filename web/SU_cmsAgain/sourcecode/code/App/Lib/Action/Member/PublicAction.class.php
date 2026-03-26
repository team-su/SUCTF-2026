<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class PublicAction extends MemberBaseAction {
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$gid = (int)session('MemberGroupID');
		//获取顶级菜单
		$mt = D('Admin/MenuTop');
		$MenuTop = $mt->getMenuTopPurview(0,  $gid);
		//获取分组
		$mg = D('Admin/MenuGroup');
		$MenuGroup = $mg->getMenuGroupPurview(0, $gid);
		//获取菜单
		$m = D('Admin/Menu');
		$Menu = $m->getMenuPurview(0, $gid);
		
		$Data = array();
		foreach ($MenuTop as $v1){
			if( $v1['MenuTopTarget'] == '_top' || $v1['MenuTopTarget'] == 'main'){
				$Data[] = array( 'MenuID'=>$v1['MenuTopID'], 'MenuName'=>$v1['MenuTopName'], 'MenuAction'=>$v1['MenuTopUrl']
						,'MenuTarget'=>$v1['MenuTopTarget']);
			}else{
				foreach ($MenuGroup as $v2){
					if( $v1['MenuTopID'] == $v2['MenuTopID']){
						foreach ($Menu as $v3){
							if( $v3['MenuGroupID'] == $v2['MenuGroupID']){
								$Data[] = array( 'MenuID'=>$v3['MenuID'], 'MenuName'=>$v3['MenuName'], 'MenuAction'=>$v3['MenuContent']
										,'MenuTarget'=>'main' );
							}
						}
					}
				}
			}
		}
		$this->assign('Menu', $Data);
		$this->assign("MemberName", session("MemberName") );
		$this->assign("MemberGroupName", session("MemberGroupName") );
		$this->assign("MemberAvatar", session("MemberAvatar") );
		$this->display();
	}
	
	//退出系统
	function logOut(){
		$options['LogType'] = 8;
		$options['UserAction'] = '退出会员后台';
		WriteLog(session("MemberName"),$options);
		session("MemberID", null);
		session("MemberName", null);
		session("MemberGroupID", null);
		session("MemberGroupName", null);
		session('DiscountRate',null);
		session('IsAdmin', null);
		$url = trim($_GET['url']);
		if( empty($url) ){
			$url = $_SERVER['HTTP_REFERER'];
			//当为商城是，退出以后，避免重新跳转到登录
			if( empty($url) || stripos($url, 'public/checkout') || stripos($url, 'public/pay') ){
				$url = HomeUrl();
			}
		}
		redirect( $url );
	}

	
	//用户后台首页
	function welcome(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Member');
		$mid = (int)session('MemberID');
		$data = $m->find( $mid );
		$CommentCount = D('Admin/Comment')->getCount("GuestID=$mid");
		$MessageCount = D('Admin/Guestbook')->getCount("GuestID=$mid");
		$InfoCount = D('Admin/Info')->getMemberInfoCount($mid);
		$OrderCount = D('Admin/Order')->getOrderCount(array('MemberID'=>$mid));
		$ResumeCount = D('Admin/Resume')->getCount("GuestID=$mid");
		
		$this->assign('CommentCount', $CommentCount);
		$this->assign('MessageCount', $MessageCount);
		$this->assign('InfoCount', $InfoCount);
		$this->assign('OrderCount', $OrderCount);
		$this->assign('ResumeCount', $ResumeCount);
		$this->assign('LastLoginTime', $data['LastLoginTime']);
		$this->assign('LastLoginIP', $data['LastLoginIP']);
		$this->assign('LoginCount', $data['LoginCount']);
		$this->display();
	}
	
	//修改密码
	function pwd(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = intval(session('MemberID'));
		$m = D('Admin/Member');
		//如果是第三方注册，没有原始密码
		$oldPassword = $m->where("MemberID=$MemberID")->getField('MemberPassword');
		$HasOldPassword = empty($oldPassword) ? 0 : 1;
		if( $_POST['Action'] == 'save'){ //保存
			$pwd1 = trim($_POST['pwd1']);  //原始密码
			$pwd2 = trim($_POST['pwd2']);
			$pwd3 = trim($_POST['pwd3']);
			
			if( $HasOldPassword && empty($pwd1) ){
				$this->ajaxReturn(null, '原始密码不能为空!' , 0);
			}
				
			if( empty($pwd2) ){
				$this->ajaxReturn(null, '新密码不能为空!' , 0);
			}
				
			if( empty($pwd3) ){
				$this->ajaxReturn(null, '重复密码不能为空!' , 0);
			}
				
			if( $pwd2 != $pwd3 ){
				$this->ajaxReturn(null, '二次输入的密码不一致!' , 0);
			}
				
			if( $HasOldPassword && $pwd1 == $pwd3 ){
				$this->ajaxReturn(null, '新密码不能和原始密码相同!' , 0);
			}
			$options['LogType'] = 8;
			
			if($HasOldPassword){
				$isCorrect = $m->isOldPasswordCorrect($MemberID, $pwd1);
				if(!$isCorrect){
					$options['UserAction'] = '修改密码';
					WriteLog(session('MemberName').'修改密码失败，原密码错误', $options);
					$this->ajaxReturn(null, '原密码错误!' , 0);
				}
			}

            $pwd2 = yd_password_hash($pwd2);
			$r = $m->where("MemberID=$MemberID")->setField('MemberPassword', $pwd2);
			if($r){
				$options['UserAction'] = '修改密码';
				WriteLog(session('MemberName').'修改密码成功', $options);
				$this->ajaxReturn(null, '修改密码成功!' , 1);
			}else{
				$this->ajaxReturn(null, '修改密码失败!' , 0);
			}
		}
		$this->assign('HasOldPassword', $HasOldPassword);
		$this->assign('Action', __URL__.'/pwd');
		$this->display();
	}
}