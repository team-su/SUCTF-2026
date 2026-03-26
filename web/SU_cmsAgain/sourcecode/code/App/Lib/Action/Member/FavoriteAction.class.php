<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class FavoriteAction extends MemberBaseAction {
	function index(){
		$MemberID = (int)session('MemberID');
		$p = array(
				'HasPage' => true,
				'PageSize'=>8,
				'Parameter' => array('MemberID' =>$MemberID),
		);
		$this->opIndex($p);
	}
	
	function del(){
		$p = array(
			'Parameter' => array('p' =>intval( $_REQUEST['p']) ),
		);
		$this->opDel( $p );
	}
}