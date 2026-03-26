<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class CouponAction extends MemberBaseAction {
	function index(){
		$MemberID = (int)session('MemberID');
		$p = array(
				'HasPage' => true,
				'Parameter' => array('MemberID' =>$MemberID),
				'ModuleName' => 'CouponSend',
		);
		$this->opIndex($p);
	}
	
	function del(){
		$MemberID = (int)session('MemberID');
		$p['Parameter']['MemberID'] = $MemberID;
		$p['ModuleName'] = 'CouponSend';
		if( is_numeric($_REQUEST['p']) ){
			$p['Parameter']['p'] = $_REQUEST['p'];
		}
		$this->opDel( $p );
	}
}