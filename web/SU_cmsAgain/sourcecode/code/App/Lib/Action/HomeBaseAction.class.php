<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
if (!defined('APP_NAME')) exit();
class HomeBaseAction extends BaseAction {
	protected $_fromUser = '';  //微信内部号
	protected $_isWx=0;  //是否是微信浏览器访问
	function _initialize(){
		parent::_initialize();
		$this->_assignPublicVar();
		$this->_assignConfigVar();
		$this->getTemplateConfig();
		$this->_fromUser();
	}

	private function _fromUser(){
        if( isset( $_GET['fu'])  && !empty( $_GET['fu']) ){
            $this->_fromUser = YdInput::checkLetterNumber($_GET['fu']);
            cookie('fu', $this->_fromUser, 31536000); //31536000秒＝1年，有效期为1年
        }else if( cookie('fu') ) {
            $this->_fromUser = cookie('fu');
        }
        //如：oM3xZty-jrBxm1J9bIRAo_rScgcgU
        $this->_fromUser = YdInput::checkLetterNumber($this->_fromUser);
    }
	
	public function _empty($method) {
		//加上header，在linux下，无法显示，2013-12-01后来在西数linux主机测试没问题
		header("HTTP/1.0 404 Not Found"); //使HTTP返回404状态码
		$fileName= THEME_PATH.'Channel/404.html';
		if( !file_exists($fileName)){
			$fileName = "./Public/tpl/".C('TMPL_404');
		}
		$this->display( $fileName );
	}
	
	
	//系统配置模板赋值
	private function _assignConfigVar(){
		//基本信息，手机网站在BaseAction中_assignPublicVar初始化
		$GLOBALS['DiscountRate'] = session("?DiscountRate") ? (double)(session("DiscountRate")) : 1;
		$this->assign('DiscountRate', $GLOBALS['DiscountRate'] );
		
		$data = &$GLOBALS['Config'];
		//联系方式
		$this->assign('Company', $data['COMPANY'] );
		$this->assign('Contact', $data['CONTACT'] );
		$this->assign('Address', $data['ADDRESS'] );
		$this->assign('Telephone', $data['TELEPHONE'] );
		$this->assign('Mobile', $data['MOBILE'] );
		$this->assign('Fax', $data['FAX'] );
		$this->assign('Email', $data['EMAIL'] );
		$this->assign('QQ', $data['QQ'] );
		$this->assign('PostCode', $data['POSTCODE'] );
		$this->assign('Longitude', $data['Longitude'] );
		$this->assign('Latitude', $data['Latitude'] );
		$this->assign('SearchPageSize', $data['SearchPageSize'] );
		
		//小程序设置
		$this->assign('XcxQrcode', $data['XCX_QRCODE'] );
		$this->assign('XcxName', $data['XCX_NAME'] );

		//分站
        $SiteID = defined('SITE_ID') ? SITE_ID : 0;
        if($SiteID>0){
            $this->assign('SiteName', SITE_NAME);
            $this->assign('SiteID', SITE_ID);
        }
		
		//注册设置
		$this->assign('MemberEnable', $data['MEMBER_ENABLE'] );
		$this->assign('EnableMember', $data['MEMBER_ENABLE'] );
		$this->assign('MemberRegEnable', $data['MEMBER_REG_ENABLE'] );
		$this->assign('MemberRegCheck', $data['MEMBER_REG_CHECK'] );
		$this->assign('MemberRegVerifyCode', $data['MEMBER_REG_VERIFYCODE'] );
		$this->assign('MemberLoginVerifyCode', $data['MEMBER_LOGIN_VERIFYCODE'] );
		
		//评论设置
		$this->assign('CommentEnable', $data['COMMENT_ENABLE'] );
		$this->assign('CommentTip', $data['COMMENT_TIP'] );
		$this->assign('CommentCheck', $data['COMMENT_CHECK'] );
		$this->assign('CommentVerifycode', $data['COMMENT_VERIFYCODE'] );
		$this->assign('CommentPageSize', $data['COMMENT_PAGE_SIZE'] );
		
		//留言设置
		$this->assign('GuestBookEnable', $data['GUEST_BOOK_ALLOW'] );
		$this->assign('GuestBookAllow', $data['GUEST_BOOK_ALLOW'] );
		$this->assign('GuestBookCheck', $data['GUEST_BOOK_CHECK'] );
		$this->assign('GuestBookVerifycode', $data['GUEST_BOOK_VERIFYCODE'] );
		$this->assign('GuestBookPageSize', $data['GUEST_BOOK_PAGESIZE'] );
		$this->assign('FeedbackVerifycode', $data['FEEDBACK_VERIFYCODE'] );
		
		//订单设置
		$this->assign('OrderEnable', $data['ORDER_ALLOW'] );
		$this->assign('OrderAllow', $data['ORDER_ALLOW'] );
		$this->assign('OrderPageSize', $data['ORDER_PAGESIZE'] );
		$this->assign('OrderVerifyCode', $data['ORDER_VERIFYCODE'] );
		
		//	站长统计
		if( $data['STAT_ENABLE'] ){
			$this->assign('Stat', $data['STAT_CODE'] );
			$this->assign('AsyncStat', $data['ASYNC_STAT_CODE'] );
		}
		
		//是否存在手机网站模板,是否启用手机网站==========================
		$wapStatus = 0;
		if( $data['WAP_STATUS'] == 1){
			$wapfile = TMPL_PATH.'Wap/'.C('WAP_DEFAULT_THEME').'/template.xml';
			if( file_exists($wapfile)){
				$wapStatus = 1;
			}
		}
		$this->assign('WapQrcode', $data['WAP_QRCODE'] );
		$this->assign('EnableWap', $wapStatus );
		$this->assign('WapEnable', $wapStatus );
		$SiteEnable = isset($data['SiteEnable']) ? $data['SiteEnable'] : 0;
        $this->assign('SiteEnable', $SiteEnable);  //多城市站点是否启用
		//===================================================
		
		//APP配置
		$this->assign('AppApkSize', $data['APP_APK_SIZE'] );
		$this->assign('AppApkUrl', $data['APP_APK_URL'] );
		$this->assign('AppApkQrcode', $data['APP_APK_QRCODE'] );
		$this->assign('AppIpaUrl', $data['APP_IPA_URL'] );
		$this->assign('AppIpaQrcode', $data['APP_IPA_QRCODE'] );
		
		//微信配置===========================================
		$this->assign('WxLogo', $data['WX_LOGO'] );
		$this->assign('WxName', $data['WX_NAME'] );
		$this->assign('WxOriginalID', $data['WX_ORIGINAL_ID'] );
		$this->assign('WxID', $data['WX_ID'] );
		$this->assign('WxDescription', $data['WX_DESCRIPTION'] );
		$this->assign('WxQrcode', $data['WX_QRCODE'] );
		//是否是微信浏览器
		$this->_isWx = stripos($_SERVER['HTTP_USER_AGENT'],"MicroMessenger") ? 1 : 0;
		$this->assign('IsWx', $this->_isWx );
		//=================================================
		
		//自定义标签, 优先级最高，会覆盖同名系统标签
		if( !empty($data['TAG_LIST']) ){
			$this->assign( $data['TAG_LIST'] );
		}
	}
	
	
	private function _assignPublicVar(){
		$MemberIsLogin = ( $this->MemberIsLogin() ) ? 1 : 0;
		if( $MemberIsLogin == 1){
			$this->assign('IsAdmin', (int)session('IsAdmin'));
			$this->assign('MemberIsLogin', $MemberIsLogin);
			$this->assign('MemberName', session('MemberName') );
			$this->assign('MemberID', (int)session('MemberID') );
			$this->assign('MemberGroupName', session('MemberGroupName') );
		}
	}
	
	function MemberIsLogin(){
		$b = session("?MemberID");
		return $b;
	}
	
	//获取当前频道的站点路径, $type(0:频道，1：查看信息详情)
	 function _getSitePath($ChannelID, $type=0, $ParentID=false, $ChannelName=false, $ChannelUrl=false){
	 	$m = D('Admin/Channel');
	 	//第一个===================================	 	
         $indexData = IndexChannelData();  //首页频道数据
         $IndexChannelID = $indexData['ChannelID']; //首页频道ID
         $first['PathName'] = $indexData['ChannelName'];
         $first['PathUrl'] = ChannelUrl($IndexChannelID);

		if( $ChannelID == $IndexChannelID){ //当前为首页
			$first['HasNext'] = 0;
			$SitePath[] = $first;
			return $SitePath;
		}else{
			$first['HasNext'] = 1;
		}
		//=======================================
		
		//在线订购，投递简历=========================================
		if( 6 == $ChannelID || 7 == $ChannelID || 10 == $ChannelID || 11 == $ChannelID){
			$SitePath[] = $first;
			$item["PathName"] = $ChannelName;
			$item["PathUrl"] = '#';
			$item["HasNext"] = 0;
			$SitePath[] = $item;
			return $SitePath;
		}
		//======================================================
		
		//公共模块，search:站内搜索
		$list = array('search'=>'SearchResult', 'userreg'=>'UserReg','userlogin'=>'UserLogin','forgetpassword' => 'ForgetPassword'
		,'cart'=>'MyShoppingCart','checkout'=>'FillAndCheckOrder','pay'=>'SuccessSubmitOrder','oauthlogin'=>'OauthLogin');
		if( key_exists($ChannelID, $list)){
			$SitePath[] = $first;
			$item["PathName"] = L( $list[$ChannelID] );
			$item["PathUrl"] = '#';
			$item["HasNext"] = 0;
			$SitePath[] = $item;
			return $SitePath;
		}
		 
		//最后一个===============================
		if( $type == 1 ){
			$SitePath[] = array('PathName' => L('ViewDetail'), 'PathUrl' => '#', 'HasNext' => 0);
		}
		$last["PathName"] = ($ChannelName===false) ? ChannelName( $ChannelID ) : $ChannelName;
		$last["PathUrl"] = ($ChannelUrl===false) ? ChannelUrl( $ChannelID ) : $ChannelUrl;
		$last["HasNext"] = ($type == 1) ? 1 : 0;
		$SitePath[] = $last;
		//=====================================
		$pid = ($ParentID === false ) ? $m->getFieldByChannelID( $ChannelID, 'Parent' ) : $ParentID;
		while ( $pid > 0 ) {
			$data = $m->findField( $pid, 'ChannelName,Parent,LinkUrl,Html');
			$item["PathName"] = $data['ChannelName'];
			$item["PathUrl"] = ChannelUrl( $pid, $data['Html'], $data['LinkUrl']);
			$item["HasNext"] = 1;
			$SitePath[] = $item;
			$pid = $data['Parent'];
		}
		 
		$temp[] = $first;
		$n = count( $SitePath ) - 1;
		for($i = $n; $i >= 0; $i--){
			$temp[] = $SitePath[$i];
		}
		unset( $SitePath );
		return $temp;
	}
	
	//测试伪静态使用
	function testModel(){
		header("Content-Type:text/html; charset=utf-8");
		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');  
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');  
		header('Cache-Control: no-store, no-cache, must-revalidate');  
		header('Cache-Control: post-check=0, pre-check=0', false );  
		header('Pragma: no-cache');
		echo 'success';
	}
}