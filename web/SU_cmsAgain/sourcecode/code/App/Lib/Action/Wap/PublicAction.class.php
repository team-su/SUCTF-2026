<?php
class PublicAction extends HomeBaseAction {
	function _initialize(){
		parent::_initialize();
		//未启用会员功能, 将导致public/testModel重定向到首页
		//if( $data['MEMBER_ENABLE'] == 0 ){
		//	redirect( HomeUrl() );
		//}
	}
	
	function index(){
		redirect(__URL__.'/login');
	}
	
	//用户登录
    function login(){
        $this->_checkMember();
        header("Content-Type:text/html; charset=utf-8");
        $SitePath = $this->_getSitePath( 'userlogin' );
        $this->assign('SitePath', $SitePath);
        $this->display();
    }

    /**
     * 检查是否启用会员功能
     */
    private function _checkMember(){
        $enable = (int)$GLOBALS['Config']['MEMBER_ENABLE'];
        if(empty($enable)){
            send_http_status(404);
            exit();
        }
    }
    
    function oauth(){
        $this->_checkMember();
    	header("Content-Type:text/html; charset=utf-8");
    	$data = explode('_', $_REQUEST['state']);
    	$state = $data[0];
    	$type = $data[1];
    	if( empty($state) || empty($type) ) redirect( HomeUrl() );
    	//防止CSRF攻击
    	if( session('oauth_state_'.$type) != $_REQUEST['state'] ) redirect( HomeUrl() );
    	
    	//调取配置数据
    	$m = D('Admin/Oauth');
    	$config = $m->findOauthByMark($type);
    	if( empty($config) ) redirect( HomeUrl() );
    	
    	import("@.Common.YdOauth");
    	$obj = YdOauth::getInstance($type);
    	$obj->setAppID( $config['OauthAppID'] );
    	$obj->setAppKey( $config['OauthAppKey'] );
    	$obj->setCode( $_REQUEST['code'] );
    	$obj->getAccessToken();           //获取Token
    	$openid = $obj->getOpenID();  //获取OpenID
    	if( empty($openid) ) redirect( HomeUrl() );
    	
    	$m1 = D('Admin/Member');
    	$data = $m1->findMemberByOpenID($openid);
    	if( !empty($data) ){  //表示已经存在，直接登录即可
    		if( $data['IsLock'] == 1 || $data['IsCheck'] == 0 ){
    			redirect( HomeUrl() ); //如果被锁定，返回首页
    		}else{
    			$this->setLogin($data);
    			$url = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : HomeUrl();
    			header("Location:{$url}");
				exit();
    		}
    	}else{ //openid不存在，是注册新账号，还是绑定现有账号
    		$user = $obj->getUserInfo();
    		session('openid', $openid); //不能讲openid assgn给模板，有安全隐患
    		$this->assign($user);
    	}
    	$SitePath = $this->_getSitePath( 'oauthlogin' );
    	$this->assign('SitePath', $SitePath);
    	$this->display();
    }
    
    //保存授权信息
    function saveOauth(){
        $this->_checkMember();
    	header("Content-Type:text/html; charset=utf-8");
    	$type = $_REQUEST['type'];
    	$url = !empty($_REQUEST['RefererUrl']) ? $_REQUEST['RefererUrl'] : HomeUrl();
    	$OpenID = session('openid');
    	if( empty($OpenID) ){
    		header("Location:{$url}"); exit();
    	}
    	if($type == 1){ //注册新账号
    		$this->checkReg();
    		unset($_POST['MemberPassword'], $_POST['MemberGroupID']);
    		$_POST = YdInput::checkReg($_POST, array('MemberPassword','MemberPassword1') ); //防止xss注入
    		$MemberName = trim($_POST['MemberName']);
    		if( $MemberName == '' ){
    			$this->ajaxReturn(null, L('UserNameRequired') , 0);
    		}
    		
    		if( $GLOBALS['Config']['MEMBER_REG_VERIFYCODE'] == 1){ //启用验证码
    			$MemberCode = trim($_POST['MemberCode']);
    			if($MemberCode==''){
    				session('MemberCode', rand(1000, 9999) );
    				$this->ajaxReturn(null, L('VerifyCodeRequired') , 0);
    			}
    			
    			$MemberCode2 = session('membercode');
    			if(md5($MemberCode) != $MemberCode2){
    				session('MemberCode', rand(1000, 9999) );
    				$this->ajaxReturn(null, L('VerifyCodeError') , 0);
    			}
    		}
    		
    		$m = D('Admin/Member');
    		if( $m->create() ){
    			$m->RegisterTime = date('Y-m-d H:i:s');
    			$m->IsCheck = 1; //第三方登录直接审核通过
    			$m->OpenID = $OpenID;
    			if($m->add()){
    				//注册成功以后，自动登录
    				$this->setLogin( $OpenID );
    				$this->ajaxReturn(null, $url, 1);
    			}else{
    				$this->ajaxReturn(null, L('RegFail') , 0);
    			}
    		}else{
    			$this->ajaxReturn(null, L('RegFail')."\n".$m->getError() , 0);
    		}
    	}else{ //绑定现有账号，并登录
    		//$MemberName可能是昵称、email、手机号码
    		$MemberName= trim($_POST['MemberName']);
    		if( $MemberName == '' ){
    			$this->ajaxReturn(null, L('UserNameRequired') , 0);
    		}
    		
    		$MemberPassword = trim($_POST['MemberPassword']);
    		if( $MemberPassword == '' ){
    			$this->ajaxReturn(null, L('PasswordRequired') , 0);
    		}
    		
    		if( $GLOBALS['Config']['MEMBER_REG_VERIFYCODE'] == 1){ //启用验证码
    			$MemberCode = trim($_POST['MemberCode']);
    			if($MemberCode==''){
    				session('MemberCode', rand(1000, 9999) );
    				$this->ajaxReturn(null, L('VerifyCodeRequired') , 0);
    			}
    			 
    			$MemberCode2 = session('membercode');
    			if(md5($MemberCode) != $MemberCode2){
    				session('MemberCode', rand(1000, 9999) );
    				$this->ajaxReturn(null, L('VerifyCodeError') , 0);
    			}
    		}
    		$m = D('Admin/Member');
    		$result = $m->bindMember($MemberName, $MemberPassword, $OpenID);
    		if($result){
				$this->setLogin( $OpenID );
				$this->ajaxReturn(null, $url, 1);
    		}else{
    			$this->ajaxReturn(null, L('UserNamePasswordError') , 0);
    		}
    	}
    }
    
    private function setLogin($param){
    	$m = D('Admin/Member');
    	if( is_array($param) ){ //直接传入会员数据
    		$data = $param;
    	}elseif (is_numeric($param)){ //表示data是MemberID
    		$data = $m->findMember( $param );
    	}else{ //表示data是OpenID
    		$data = $m->findMemberByOpenID( $param );
    	}

    	if(!empty($data)){ //自动登录
    		$m->UpdateLogin($data['MemberID']);
    		session('MemberID', $data['MemberID']);
    		session('MemberName', $data['MemberName']);
    		session('MemberGroupID', $data['MemberGroupID']);
    		session('MemberGroupName', $data['MemberGroupName']);
    		session('DiscountRate', is_numeric($data['DiscountRate']) ? $data['DiscountRate'] : 1);
    		$IsAdmin = ( $data['AdminID'] ) ? 1 : 0;
    		session('IsAdmin', $IsAdmin );
    	}
    }
    
    function checkLogin(){
        $this->_checkMember();
    	header("Content-Type:text/html; charset=utf-8");
    	$MemberName = trim($_POST['MemberName']);
    	$MemberPassword = trim($_POST['MemberPassword']);
    	$MemberCode = trim($_POST['MemberCode']);
    
    	if($MemberName == ''){
    		session('membercode', rand(1000, 9999) );
    		$this->ajaxReturn(null, L('UserNameRequired') , 0);
    	}
    
    	if($MemberPassword == ''){
    		session('membercode', rand(1000, 9999) );
    		$this->ajaxReturn(null, L('PasswordRequired') , 0);
    	}
    
    	if( $GLOBALS['Config']['MEMBER_LOGIN_VERIFYCODE'] == 1){ //启用验证码
	    	if($MemberCode == ''){
	    		session('membercode', rand(1000, 9999) );
	    		$this->ajaxReturn(null, L('VerifyCodeRequired') , 0);
	    	}
	    
	    	$MemberCode2 = session('membercode');
	    	if(md5($MemberCode) != $MemberCode2){
	    		session('membercode', rand(1000, 9999) );
	    		$this->ajaxReturn(null, L('VerifyCodeError') , 0);
	    	}
    	}
    
    	$m = D('Admin/Member');
    	//判断登录失败次数，不能超过10
        $LoginFailCount = $m->getLoginFailCount($MemberName);
        if($LoginFailCount > 10){
            session('membercode', rand(1000, 9999) );
            $this->ajaxReturn(null, L('PasswordErrorMaxCount') , 0);
        }

    	//0: 用户名或密码错误，1：用户被锁定，2:用户组不存在，数组：认证成功
    	$result = $m->checkLogin($MemberName, $MemberPassword );
        session('membercode', rand(1000, 9999) );
    	if( $result == 0 ){
    		$this->ajaxReturn(null, L('UserNamePasswordError') , 0);
    	}else if($result == 1){
    		$this->ajaxReturn(null, L('AccountLock') , 0);
    	}else if($result == 2){
    		$this->ajaxReturn(null, L('AdminGroupNotExist') , 0);
    	}else if($result == 3){
    		$this->ajaxReturn(null, L('AccountUnchecked') , 0);
    	}else if( is_array($result) ){ //认证成功
    		$m->UpdateLogin($result['MemberID']);
    		session('MemberID', $result['MemberID']);
    		session('MemberName', $MemberName);
    		session('MemberGroupID', $result['MemberGroupID']);
    		session('MemberGroupName', $result['MemberGroupName']);
    		session('MemberAvatar', $result['MemberAvatar']);
    		
    		session('DiscountRate', is_numeric($result['DiscountRate']) ? $result['DiscountRate'] : 1);
    		
    		$IsAdmin = D('Admin/Admin')->where("MemberID=".$result['MemberID'])->count();
    		$IsAdmin = ( $IsAdmin > 0) ? 1 : 0;
    		session('IsAdmin', $IsAdmin );
    		//登录成功，返回来源页
    		$this->ajaxReturn(null, L('LoginSuccess'), 1);
    	}
    }

    function checkReg(){
    	$Enable = (int)$GLOBALS['Config']['MEMBER_REG_ENABLE'];
    	if( 0==$Enable ){
    		header("Location:".HomeUrl());
    		exit();
    	}
    }
    
    function reg(){
        $this->_checkMember();
    	header("Content-Type:text/html; charset=utf-8");
		$this->checkReg();
		$SitePath = $this->_getSitePath( 'userreg' );
		$this->assign('SitePath', $SitePath);
    	$this->display();
    }
    
    //发送手机注册验证码
    function sendSmsCode(){
    	//如果不为空，表示专用于找回密码，找回密码不需要验证码
    	if( session("?ForgetMemberMobile") ){
    		$mobile = session("ForgetMemberMobile");
    	}else{
	    	$mobile = $_POST['mobile'];
	    	//必须传入验证码和手机号码
	    	if(empty($mobile)){
	    		$this->ajaxReturn('MemberMobile', L('MobileRequired'), 0);
	    	}
	    	//验证码 开始，发送短信必须要有验证码================
	    	$MemberCode = trim($_POST['MemberCode']);
	    	if($MemberCode==''){
	    		$this->ajaxReturn('MemberCode', L('VerifyCodeRequired') , 0);
	    	}
	    	
	    	$MemberCode2 = session('membercode');
	    	if(md5($MemberCode) != $MemberCode2){
	    		session('MemberCode', rand(1000, 9999) );
	    		$this->ajaxReturn('MemberCode', L('VerifyCodeError') , 0);
	    	}
	    	//=======================================
	    	
	    	//必须先检查手机号码是否存在，存在则返回错误，主要用于注册
	    	if( $_POST['check'] == 1){
	    		$m = D('Admin/Member');
	    		$where['MemberMobile'] = $mobile;
	    		$MemberID = $m->where($where)->getField('MemberID');
	    		if( $MemberID > 0 ){ //表示已经存在
	    			$this->ajaxReturn('MemberMobile', L('MemberMobileExist'), 0);
	    		}
	    	}
    	}
    	
    	$code = rand_string(4, 1); //产生4位数数字验证码
    	$content = $GLOBALS['Config']['MOBILE_REG_TEMPLATE']; //读取模板
    	$b = send_sms($mobile, $content, array('{$Code}'=>$code) );
    	if($b){
    		//session：应该设置有效期, 99秒超时时间
    		//session(array('name'=>'SmsCode','expire'=>99));
    		session('SmsCode',$code);
            session('SmsMobile',$mobile);
    		$this->ajaxReturn(null, '' , 1);
    	}else{
    		$this->ajaxReturn(null, L('SendSMSFail'), 0);
    	}
    }
    
    //忘记密码
    function forget(){
        $this->_checkMember();
    	header("Content-Type:text/html; charset=utf-8");
    	$Step = intval( $_POST['Step'] );  //当前步骤
    	if( $Step == 1 ){   //第一步：验证用户是否存在
	    	$MemberName = trim( $_POST['MemberName'] ); //可以是用户名、电子邮件、手机号码
	    	if( $MemberName == ''){
	    		$this->ajaxReturn('MemberName', L('UserNameRequired') , 0);
	    	}
	    	
	    	//验证码，在第二步就没有验证码了================================
	    	$MemberCode = trim($_POST['MemberCode']);
	    	if($MemberCode==''){
	    		$this->ajaxReturn('MemberCode', L('VerifyCodeRequired') , 0);
	    	}
	    	$MemberCode2 = session('membercode');
	    	if(md5($MemberCode) != $MemberCode2){
	    		$this->ajaxReturn('MemberCode', L('VerifyCodeError') , 0);
	    	}
	    	//==================================================
	    	
	    	$m = D('Admin/Member');
	    	$data = $m->getFindPwdData($MemberName);
	    	if( empty($data)){
	    		$this->ajaxReturn('MemberName', L('UserNotExist') , 0);
	    	}else{
	    		session("ForgetMemberID", $data['MemberID']);
	    		session("ForgetMemberMobile", $data['MemberMobile']);
	    		$data['SmsEnable'] = $GLOBALS['Config']['SMS_ACCOUNT'] ? 1 : 0;
	    		$this->ajaxReturn($data, 'success' , 1);
	    	}
    	}else if( $Step == 2 ){   //第二部：密码重置
    		//先检查密码
    		$MemberPassword = trim($_POST['MemberPassword']);
    		if( $MemberPassword == '' ){
    			$this->ajaxReturn('MemberPassword', L('PasswordRequired') , 0);
    		}
    		$MemberPassword1 = trim($_POST['MemberPassword1']);
    		if( $MemberPassword1 == '' ){
    			$this->ajaxReturn('MemberPassword1', L('ConfirmPasswordRequired') , 0);
    		}
    		if( $MemberPassword != $MemberPassword1 ){
    			$this->ajaxReturn('MemberPassword', L('PasswordUnmatch') , 0);
    		}
    		
    		$m = D('Admin/Member');
    		$where['MemberID'] = intval(session("ForgetMemberID"));
	    	if( $_POST['FindPwdWay'] == 2 ){ //1:密码问题，2：手机
	    	    //检查短信验证码是否有效
	    	    $SmsCode = trim($_POST['SmsCode']);
	    	    if( $SmsCode == '' ){
	    	    	$this->ajaxReturn('SmsCode', L('SmsCodeRequired') , 0);
	    	    }
	    	    $SmsCode1 = session('SmsCode');
	    	    $this->_checkSmsCode($SmsCode1, session('ForgetMemberMobile'));
		    	if( $SmsCode != $SmsCode1){
		    		$this->ajaxReturn('SmsCode', L('SmsCodeError') , 0);
		    	}
	    	}else{
	    		//检查密保答案
	    		$MemberAnswer = trim( $_POST['MemberAnswer'] );
	    		if( $MemberAnswer == ''){
	    			$this->ajaxReturn('MemberAnswer', L('AnswerRequired') , 0);
	    		}
                $isCorrect = $m->isAnswerCorrect($where['MemberID'], $MemberAnswer);
		    	if(!$isCorrect){
		    		$this->ajaxReturn('MemberAnswer', L('AnswerError') , 0);
		    	}
	    	}
            $MemberPassword = yd_password_hash($MemberPassword);
	    	$result =  $m->where($where)->setField('MemberPassword', $MemberPassword);
	    	if( $result === false ){
	    		$this->ajaxReturn(null, L('ResetPwdFail') , 0);
	    	}else{
	    		session('ForgetMemberID',null);
	    		session('ForgetMemberMobile',null);
	    		session('SmsCode',null);
	    		$this->ajaxReturn(null, L('ResetPwdSuccess') , 2);
	    	}
    	}else{
    		$SitePath = $this->_getSitePath( 'forgetpassword' );
    		$this->assign('SitePath', $SitePath);
    		$this->display();
    	}
    }

    /**
     * 检查验证码是否过期
     */
    private function _checkSmsCode($SmsCode, $SmsMobile){
        $m = D('Admin/Log');
        $isExpired = $m->isSmsCodeExpired($SmsCode, $SmsMobile);
        if($isExpired){
            $this->ajaxReturn('SmsCode', L('SmsCodeError') , 0);
        }
    }
    
    //注册提交
    function saveReg(){
        $this->_checkMember();
    	header("Content-Type:text/html; charset=utf-8");
    	$this->checkReg();
    	if( $GLOBALS['Config']['MEMBER_REG_CHECK'] == 3 ){ //手机注册
    		$this->saveMobileReg();
    	}else{ //正常注册（用户名注册、电子邮件注册）
    		$this->saveNormalReg();
    	}
    }
    
    //用户名注册和电子邮件注册
    private function saveNormalReg(){
    	$reg = &$GLOBALS['Config'];
    	$_POST = YdInput::checkReg($_POST, array('MemberPassword','MemberPassword1') ); //防止xss注入
    	if( $reg['MEMBER_REG_CHECK'] == '2' ){  //启用邮件验证，电子邮件不能为空
    		$MemberEmail = trim($_POST['MemberEmail']);
    		if( $MemberEmail == '' ){
                session('membercode', rand(1000, 9999) );
    			$this->ajaxReturn('MemberEmail', L('MemberEmailRequired') , 0);
    		}
    		if( !strpos($MemberEmail, '@') ){
                session('membercode', rand(1000, 9999) );
    			$this->ajaxReturn('MemberEmail', L('MemberEmailInvalid') , 0);
    		}
    	}else{ //0,1 不审核和管理员审核 统一使用用户名注册
    		$MemberName = trim($_POST['MemberName']);
    		if( $MemberName == '' ){
                session('membercode', rand(1000, 9999) );
    			$this->ajaxReturn('MemberName', L('UserNameRequired') , 0);
    		}
    	}
    	 
    	$MemberPassword = trim($_POST['MemberPassword']);
    	if( $MemberPassword == '' ){
            session('membercode', rand(1000, 9999) );
    		$this->ajaxReturn('MemberPassword', L('PasswordRequired') , 0);
    	}
    	
    	$MemberPassword1 = trim($_POST['MemberPassword1']);
    	if( $MemberPassword1 == '' ){
            session('membercode', rand(1000, 9999) );
    		$this->ajaxReturn('MemberPassword1', L('ConfirmPasswordRequired') , 0);
    	}
    	
    	if( $MemberPassword != $MemberPassword1 ){
            session('membercode', rand(1000, 9999) );
    		$this->ajaxReturn('MemberPassword', L('PasswordUnmatch') , 0);
    	}
    	
    	if( $reg['MEMBER_REG_VERIFYCODE'] == 1){ //启用验证码
    		$MemberCode = trim($_POST['MemberCode']);
    		if($MemberCode==''){
                session('membercode', rand(1000, 9999) );
    			$this->ajaxReturn('MemberCode', L('VerifyCodeRequired') , 0);
    		}
    	
    		$MemberCode2 = session('membercode');
    		if(md5($MemberCode) != $MemberCode2){
    			session('membercode', rand(1000, 9999) );
    			$this->ajaxReturn('MemberCode', L('VerifyCodeError') , 0);
    		}
    	}
    		
    	$m = D('Admin/Member');
        session('membercode', rand(1000, 9999) );
    	if( $m->create() ){
    		$m->RegisterTime = date('Y-m-d H:i:s');
    		$m->RegisterIP = get_client_ip();
    		$m->MemberPassword = yd_password_hash($MemberPassword);
    		
    		//自动成为分销商========================================
    		$m->InviterID = GetInviterID();
    		$DistributeEnable = $GLOBALS['Config']['DistributeEnable'];
    		$DistributeRequirement = $GLOBALS['Config']['DistributeRequirement'];
    		if(1==$DistributeEnable && 1==$DistributeRequirement){
    			$md = D('Admin/DistributorLevel');
    			$DistributorLevelID = $md->getLowestDistributorLevelID();
    			$m->IsDistributor = 1;
    			$m->DistributorLevelID = $DistributorLevelID;
    			$m->DistributorTime = $m->RegisterTime;
    			$m->InviteCode = MakeInviteCode();
    		}else{
    			$m->IsDistributor = 0;
    			$m->DistributorLevelID = 0;
    			$m->InviteCode ='';
    		}
    		//==================================================
    			
    		switch($reg['MEMBER_REG_CHECK']){
    			case '0':  //不审核
    				$success = L('RegSuccessNoCheck');
    				$m->IsCheck = 1;
    				break;
    			case '1':  //管理员验证
    				$success = L('RegSuccess');
    				$m->IsCheck = 0;
    				break;
    			case '2':  //邮件认证
    				$m->IsCheck = 0;
    				$success = L('RegSuccessEmailCheck');
    				$code = md5(strtotime($m->RegisterTime));
    				break;
    		}
    	
    		if($m->add()){
    			if( $reg['MEMBER_REG_CHECK'] == '2' ){ //邮件激活
    				$WebName = $reg['WEB_NAME'];
    				$WebUrl = get_web_url(); //自动获取当前地址
    				if(C('URL_MODEL') == 1){
    					$WebUrl .= '/index.php';
    				}
    	
    				$EmailTitle = $WebName.' '.L('EmailActivateTitle');
    				$ActivateUrl = "{$WebUrl}/public/activate?code={$code}&name={$MemberEmail}&l=".LANG_SET;
    				$Activate = "<a href='$ActivateUrl' target='_blank'>$ActivateUrl</a>";
    				//变量解析==============================================
    				$EmailBody = str_ireplace('{$WebName}', $WebName, $reg['EMAIL_BODY']);
    				$EmailBody = str_ireplace('{$WebUrl}', $WebUrl, $EmailBody);
    				$EmailBody = str_ireplace('{$MemberName}', $MemberEmail, $EmailBody);
    				$EmailBody = str_ireplace('{$Activate}', $Activate, $EmailBody);
    				//====================================================
    				$b = sendwebmail($MemberEmail, $EmailTitle, $EmailBody);
    			}
    			$this->ajaxReturn(null, $success, 1);
    		}else{
    			$this->ajaxReturn(null, L('RegFail') , 0);
    		}
    	}else{
    		$this->ajaxReturn(null, $m->getError() , 0);
    	}
    }
    
    //手机号码注册
    private function saveMobileReg(){
    	$_POST = YdInput::checkReg($_POST, array('MemberPassword','MemberPassword1') ); //防止xss注入
		//手机号码
    	$MemberMobile = trim($_POST['MemberMobile']);
    	if( $MemberMobile == '' ){
            session('membercode', rand(1000, 9999) );
    		$this->ajaxReturn('MemberMobile', L('MobileRequired') , 0);
    	}
        $SmsMobile = session('SmsMobile');
    	if($MemberMobile!==$SmsMobile){
            session('membercode', rand(1000, 9999) );
            $this->ajaxReturn('MemberMobile', L('MobileRequired')  , 0);
        }
    	
    	//验证手机校验码============================
    	$SmsCode = trim($_POST['SmsCode']);
    	if( $SmsCode == '' ){
            session('membercode', rand(1000, 9999) );
    		$this->ajaxReturn('SmsCode', L('SmsCodeRequired') , 0);
    	}
    	$SmsCode1 = session('SmsCode');
    	$this->_checkSmsCode($SmsCode1, $SmsMobile);
    	if( $SmsCode != $SmsCode1){
            session('membercode', rand(1000, 9999) );
    		$this->ajaxReturn('SmsCode', L('SmsCodeError') , 0);
    	}
    	//=======================================
    	
    	//验证密码=====================================
    	$MemberPassword = trim($_POST['MemberPassword']);
    	if( $MemberPassword == '' ){
            session('membercode', rand(1000, 9999) );
    		$this->ajaxReturn('MemberPassword', L('PasswordRequired') , 0);
    	}
    	 
    	$MemberPassword1 = trim($_POST['MemberPassword1']);
    	if( $MemberPassword1 == '' ){
            session('membercode', rand(1000, 9999) );
    		$this->ajaxReturn('MemberPassword1', L('ConfirmPasswordRequired') , 0);
    	}
    	
    	if( $MemberPassword != $MemberPassword1 ){
            session('membercode', rand(1000, 9999) );
    		$this->ajaxReturn('MemberPassword', L('PasswordUnmatch') , 0);
    	}
    	//============================================
    	
    	$m = D('Admin/Member');
        session('membercode', rand(1000, 9999) );
    	if( $m->create() ){
    		$m->RegisterTime = date('Y-m-d H:i:s');
    		$m->RegisterIP = get_client_ip();
    		$m->MemberPassword = yd_password_hash($MemberPassword);
    		$m->IsCheck = 1; //手机号码注册，无需审核
    		//自动成为分销商========================================
    		$m->InviterID = GetInviterID();
    		$DistributeEnable = $GLOBALS['Config']['DistributeEnable'];
    		$DistributeRequirement = $GLOBALS['Config']['DistributeRequirement'];
    		if(1==$DistributeEnable && 1==$DistributeRequirement){
    			$md = D('Admin/DistributorLevel');
    			$DistributorLevelID = $md->getLowestDistributorLevelID();
    			$m->IsDistributor = 1;
    			$m->DistributorLevelID = $DistributorLevelID;
    			$m->DistributorTime = $m->RegisterTime;
    			$m->InviteCode = MakeInviteCode();
    		}else{
    			$m->IsDistributor = 0;
    			$m->DistributorLevelID = 0;
    			$m->InviteCode = '';
    		}
    		//==================================================
    		if($m->add()){
    			session('SmsCode',null); //注册成功以后，将session变量置空
    			$this->ajaxReturn(null, L('RegSuccessNoCheck'), 1);
    		}else{
    			$this->ajaxReturn(null, L('RegFail') , 0);
    		}
    	}else{
    		$this->ajaxReturn(null, $m->getError() , 0);
    	}
    }
    
    //邮件激活帐号
    function activate(){
    	if( $GLOBALS['Config']['MEMBER_REG_CHECK'] == '2' ){
	    	$code = trim($_GET['code']);   //校验码
            $code = YdInput::checkLetterNumber($code);
	    	$name = trim($_GET['name']); //会员名称，是传过来的email
	    	$m = D('Admin/Member');
	    	$where['MemberEmail'] = $name;
	    	$RegisterTime = $m->where( $where )->getField('RegisterTime');
	    	$mycode = md5( strtotime($RegisterTime) );
	    	if( $mycode == $code ){ //校验成功
	    		$b = $m->where($where)->setField('IsCheck', 1);
	    		if($b){
	    			$redirect = HomeUrl();
	    			alert(L('ActivateSuccess'), $redirect );
	    		}
	    	}
    	}
    }
	
    //微信认证接口
    function wxapi(){
        import("@.Common.YdWx");
        $wx = new YdWx();
        $wx->valid();
        $wx->responseMsg();
    }
    
    //获取点击次数接口
    function getInfoHit(){
    	header("Content-Type:text/html; charset=utf-8");
    	$InfoID = $_REQUEST['infoid'];
    	if( !is_numeric($InfoID) ){
    		return false;
    	}
    	$m = D('Admin/Info');
    	$InfoHit = $m->where("InfoID={$InfoID}")->getField('InfoHit');
    	echo "document.write({$InfoHit})";
    }
    
    //增加点击次数
    function incInfoHit(){
        //必须为javascript 与_configSafe里的头设置有关
    	header("Content-Type:text/javascript; charset=utf-8");
    	$InfoID = $_REQUEST['infoid'];
    	if( !is_numeric($InfoID) ){
    		return false;
    	}
    	$m = D('Admin/Info');
    	$m->IncHit($InfoID);
    	$NewInfoHit = $m->where("InfoID={$InfoID}")->getField('InfoHit');
    	echo "document.write({$NewInfoHit})";
    }
    
    //在启用静态缓存的情况下获取，登录的成员信息，以json返回
    function getJson(){
    	header("Content-Type:text/html; charset=utf-8");
    	$result['MemberName'] = session('MemberName');
    	$result['MemberID'] = (int)session('MemberID');
    	$result['MemberGroupID'] = (int)session('MemberGroupID');
    	$result['MemberGroupName'] = session('MemberGroupName');
    	$result['EnableMember'] = (int)$GLOBALS['Config']['MEMBER_ENABLE'];
    	//购物车相关信息
    	if( isset($_REQUEST['type']) && $_REQUEST['type'] != 'nocart' ){
    		$result['TotalItemCount'] = TotalItemCount(); //商品数量
    	}
    	$json = json_encode($result);
		echo $json;
    }
    
    //购物车操作===========================================
    //购物车
    public function cart(){
    	header("Content-Type:text/html; charset=utf-8");
    	$SitePath = $this->_getSitePath( 'cart' );
    	$this->assign('SitePath', $SitePath);
    	$this->display();
    }
    
    //结算
    public function checkout(){
    	header("Content-Type:text/html; charset=utf-8");
    	$m = D('Admin/Order');
    	$data = false;
    	if( session('?MemberID') ){
    		$mid = (int)session('MemberID');
    		$data = $m->getLatestConsignee($mid);
    	}else{
    		$url = MemberLoginUrl();
    		redirect($url);
    	}
    	$SitePath = $this->_getSitePath( 'checkout' );
    	$this->assign('SitePath', $SitePath);
    	$this->assign($data);
    	$this->display();
    }
    
    //保存订单到数据库
    public function SaveCheckout(){
    	header("Content-Type:text/html; charset=utf-8");
    	//如果没有登录
    	$MemberID = session('?MemberID') ? (int)session('MemberID') : 0;
    	$ShippingID = (int)$_POST['ShippingID'];  //配送方式
    	$PayID = (int)$_POST['PayID'];  //支付方式
        if(empty($PayID)){
            session('ordercode', rand(1000, 9999) );
            $this->ajaxReturn(null, L('OrderFail') , 0);
        }
    	if(!is_numeric($MemberID)){
    		$url = MemberLoginUrl();
    		redirect($url);
    	}
    	
    	//验证码显示 开始================================
    	$c = &$GLOBALS['Config'];
    	if( $c['ORDER_VERIFYCODE'] == 1){
    		$verifycode = $_POST['verifycode'];
    		if( empty($verifycode) ){
    			session('ordercode', rand(1000, 9999) );
    			$this->ajaxReturn(null, L('VerifyCodeRequired') , 3);
    		}
    	
    		if( md5($verifycode) != session('ordercode')  ){
    			session('ordercode', rand(1000, 9999) );
    			$this->ajaxReturn(null, L('VerifyCodeError')  , 3);
    		}
    	}
    	//验证码显示 结束================================
    	
    	//提交订单前，判断购物车是否为空
    	import("@.Common.YdCart");
    	$cart = YdCart::getInstance();
    	if( $cart->isEmpty() ){
            session('ordercode', rand(1000, 9999) );
    		$this->ajaxReturn(null, L('OrderFail') , 0);
    	}
    	$TotalPrice = $cart->getTotalPrice(); //订单中商品总金额
    	
    	//判断优惠券的有效性========================================
    	$CouponType = intval($_REQUEST['CouponType']);
    	$CouponPrice = 0; //优惠券抵扣金额
    	switch($CouponType){
    		case 1: //会员优惠券
    			$CouponSendID = intval($_REQUEST['CouponSendID']);
    			if($CouponSendID > 0){
    			    $mcs = D('Admin/CouponSend');
    				$result = $mcs->checkCoupon($CouponSendID);
    			}
    			break;
    		case 2:  //线下优惠券
    			$CouponCode = trim($_REQUEST['CouponCode']);
    			if(!empty($CouponCode)){
    				$mcs = D('Admin/CouponSend');
    				$result = $mcs->checkCouponCode($CouponCode);
    			}
    			break;
    	}
    	if(is_array($result) && $TotalPrice >= $result['ConsumeMoney']){
    		$CouponPrice = (double)($result['CouponMoney']);
    		$CouponSendID = $result['CouponSendID'];
    	}
    	//=======================================================
    	
    	//验证积分的有效性========================================
    	$mp = D('Admin/Point');
    	$mc = D('Admin/Cart');
    	$sumPoint = $mc->sumPoint($MemberID,3);
    	$Point = intval($_REQUEST['Point']);
    	$PointExchangeRate = intval($GLOBALS['Config']['POINT_EXCHANGE_RATE']);
    	$PointPrice = 0;
    	if($Point>0 && $PointExchangeRate>0){
	    	$TotalPoint = $mp->getTotalPoint($MemberID); //获取总积分
	    	//输入的积分不能大于总积分
	    	if($Point > $TotalPoint){
                session('ordercode', rand(1000, 9999) );
	    		$this->ajaxReturn(null, L('GtTotalPoint'), 0);
	    	}
	    	 
	    	//判断不能大于最大可以使用的积分
	    	$MaxUsePoint = $sumPoint['ExchangePoint'];
	    	if($Point > $MaxUsePoint){
	    		$tip = str_ireplace('[n]', $MaxUsePoint, L('MaxUsePointTip'));
                session('ordercode', rand(1000, 9999) );
	    		$this->ajaxReturn(null, $tip, 0);
	    	}
	    	$PointPrice = number_format($Point/$PointExchangeRate, 2);
    	}
    	//验证积分的有效性========================================
    	
    	//先保存订单
    	$m = D('Admin/Order');
    	if( $m->create() ){
    		$m3 = D('Admin/Shipping');
    		$m->MemberID = $MemberID; //会员ID
    		$OrderNumber = $m->makeOrderNumber();//订单编号
    		$m->OrderNumber = $OrderNumber;
    		
    		$m->TotalPrice = $TotalPrice;
    		
    		$FreeShippingThreshold = intval($GLOBALS['Config']['FREE_SHIPPING_THRESHOLD']);
    		if( $TotalPrice >= $FreeShippingThreshold){
    			$ShippingPrice = 0; //免运费
    		}else{
    			$ShippingPrice = $m3->getShippingPrice( $ShippingID ); //配送费用
    		}
    		$m->ShippingPrice = $ShippingPrice; //配送费用
    		
    		if( $m3->isCod( $ShippingID) ){
    			$PayPrice = 0;
    		}else{
    			$m2 = D('Admin/Pay');
    			$PayRate = $m2->getPayRate($PayID); //支付手续费，单位：百分比，如果是百分比费率2%则填写0.02；
    			$PayPrice = sprintf("%.2f", ($TotalPrice+$ShippingPrice) * $PayRate);
    		}
    		$m->PayPrice = $PayPrice;
    		$m->CouponPrice = $CouponPrice;
    		$m->PointPrice = $PointPrice;
    		$TotalOrderPrice = $TotalPrice + $PayPrice + $ShippingPrice - $CouponPrice - $PointPrice;
    
    		$m->DiscountPrice = 0;  //折扣初始化为0
    		$m->OrderPoint = $sumPoint['GivePoint'];  //本次订单赠送的积分数
    		$OrderTime = date('Y-m-d H:i:s');
    		$m->OrderTime = $OrderTime;
    
    		$m->OrderStauts = 1; //1：待处理、2：已处理、3：退款、4：退货
    		$m->PayStauts = 2;    //1：已支付、2：未支付
    		$m->ShippingStauts = 2;  //1：已发货、2：未发货
    
    		$OrderID = $m->add(); //返回主键ID
    		if( $OrderID ){
    			$b = $cart->Save($OrderID); //保存订购商品
    			if($b){
    				$cart->clear();  //清空购物车
    			}else{
                    session('ordercode', rand(1000, 9999) );
    				$this->ajaxReturn(null, L('OrderFail') , 0);
    			}
    			
    			//记录优惠券已经使用
    			if($CouponPrice>0){ //大于0表示使用了优惠券
    				$mcs->SetOrderID($CouponSendID,$OrderID);
    			}
    			
    			//减去已用积分，在确认收货以后赠送积分=========
    			if($PointPrice>0){
    				$mp->orderUsePoint($OrderID,$MemberID,$Point);
    			}
    			//=======================
    			
    			//提交订单成功，保存收货地址======================
    			if( isset($_REQUEST['Consignee']) && 0 == $_REQUEST['Consignee'] ){
    				$consignee['MemberID'] = $MemberID;
    				$consignee['ConsigneeRealName'] = $_REQUEST['ConsigneeRealName'];
    				$consignee['ConsigneeAddress'] = $_REQUEST['ConsigneeAddress'];
    				$consignee['ConsigneeMobile'] = $_REQUEST['ConsigneeMobile'];
    				$consignee['ConsigneeTelephone'] = $_REQUEST['ConsigneeTelephone'];
    				$consignee['ConsigneePostcode'] = $_REQUEST['ConsigneePostcode'];
    				$consignee['ConsigneeEmail'] = $_REQUEST['ConsigneeEmail'];
    				$consignee['IsDefault'] = 0;
    				$consignee['IsEnable'] = 1;
    				$consignee['LanguageID'] = get_language_id();
    				$mc = D('Admin/Consignee');
    				$b = $mc->add($consignee);
    			}
    			//========================================
    			
    			$msg['OrderID'] = $OrderID;
    			$msg['TotalPrice'] = $TotalPrice;
    			$msg['PayPrice'] = $PayPrice;
    			$msg['ShippingPrice'] = $ShippingPrice;
    			$msg['CouponPrice'] = $CouponPrice; //优惠券抵扣
    			$msg['TotalOrderPrice'] = $TotalOrderPrice;
    			$msg['PayUrl'] = PayUrl($OrderID);
    			//发送邮件开始=========================
    			$search = array('{$OrderTime}', '{$TotalOrderPrice}', '{$OrderNumber}');
    			$replace = array($OrderTime,    $TotalOrderPrice,     $OrderNumber);
    			if( $c['ORDER_EMAIL'] == 1){  //订单通知邮件
    				//邮件支持变量
    				$body = str_ireplace($search, $replace, $c['ORDER_EMAIL_BODY']);
    				$title = str_ireplace($search, $replace, $c['ORDER_EMAIL_TITLE']);
    				$to = empty($c['ORDER_EMAIL_TO']) ? $c['EMAIL'] : $c['ORDER_EMAIL_TO'];
    				$b = sendwebmail($to, $title, $body);
    			}
    			//发送邮件 结束=========================
    			
    			//短信通知开始=========================
    			if( $c['ORDER_SMS'] == 1){
    				$placeholder = array('{$OrderTime}'=>$OrderTime, '{$TotalOrderPrice}'=>$TotalOrderPrice, 
    						'{$OrderNumber}'=>$OrderNumber);
    				send_sms($c['ORDER_SMS_TO'], $c['ORDER_SMS_TEMPLATE'], $placeholder);
    			}
    			//短信通知 结束=========================
                session('ordercode', rand(1000, 9999) );
    			$this->ajaxReturn($msg, L('OrderSuccess') , 1);
    		}else{
                session('ordercode', rand(1000, 9999) );
    			$this->ajaxReturn(null, L('OrderFail') , 0);
    		}
    	}
    	$this->display();
    }
    
    //检查线下优惠券代码
    public function checkCouponCode(){
    	header("Content-Type:text/html; charset=utf-8");
    	$CouponCode = trim($_REQUEST['CouponCode']);
    	$TotalPrice = floatval($_REQUEST['TotalPrice']);
    	if(empty($CouponCode)){
    		$this->ajaxReturn(null, L('InputCouponCode'), 0);
    	}
		$m = D('Admin/CouponSend');
		$result = $m->checkCouponCode($CouponCode);
		if(is_array($result)){
			if( $TotalPrice < $result['ConsumeMoney'] ){
				$Tip = str_ireplace('[n]', $result['ConsumeMoney'], L('CanNotUseCoupon'));
				$this->ajaxReturn(null, $Tip, 0);
			}else{
				//优惠价格，直接返回负数
				$result['CouponMoney'] = number_format(-$result['CouponMoney'], 2);
				$this->ajaxReturn($result, '', 1);
			}
		}elseif($result==1){ //优惠券已过期
			$this->ajaxReturn(null, L('CouponExpired'), 0);
		}elseif($result==2){ //优惠券不存在
			$this->ajaxReturn(null, L('CouponNotExist'), 0);
		}elseif($result==3){ //优惠券已经用过了
			$this->ajaxReturn(null, L('CouponUsed'), 0);
		}
    }
    
    /**
     * 检查积分
     */
    function checkPoint(){
    	header("Content-Type:text/html; charset=utf-8");
    	$Point = intval($_REQUEST['Point']);
    	if($Point<0){
    		$this->ajaxReturn(null, L('InvalidPoint'), 0);
    	}
    	$m = D('Admin/Point');
    	$MemberID = (int)session('MemberID');
    	$TotalPoint = $m->getTotalPoint($MemberID); //获取总积分
    	//输入的积分不能大于总积分
    	if($Point > $TotalPoint){
    		$this->ajaxReturn(null, L('GtTotalPoint'), 0);
    	}
    	
    	//判断不能大于最大可以使用的积分
    	$MaxUsePoint = MaxUsePoint();
    	if($Point > $MaxUsePoint){
    		$tip = str_ireplace('[n]', $MaxUsePoint, L('MaxUsePointTip'));
    		$this->ajaxReturn(null, $tip, 0);
    	}
    	
    	$PointExchangeRate = intval($GLOBALS['Config']['POINT_EXCHANGE_RATE']);
    	if($PointExchangeRate <= 0){
    		$this->ajaxReturn(null, L('PointExchangeRateInvalid'), 0);
    	}
    	$data['PointPrice'] = number_format(0-$Point/$PointExchangeRate, 2);
    	$this->ajaxReturn($data, '', 1);
    }
    
    public function getOrderStatus(){
    	$MemberID = (int)session('MemberID');
    	$OrderID = $_REQUEST['orderid'];
    	if( empty($MemberID) || !is_numeric($OrderID) ) {
    		$this->ajaxReturn(false, false, 0);
    	}
    	$m = D('Admin/Order');
    	$where = array('MemberID' => $MemberID, 'OrderID' => $OrderID);
    	$PayStatus = $m->where($where)->getfield('PayStatus'); //获取当前支付状态
    	$this->ajaxReturn('', $PayStatus, 1);
    }
   
    
    //立即支付
    public function payNow(){
    	$MemberID = session('MemberID');
    	if( empty($MemberID) || !is_numeric($_REQUEST['orderid']) || !is_numeric($_REQUEST['payid'])) {
    		redirect( HomeUrl() );
    	}
    	$m = D('Admin/Order');
    	$PayID = intval($_REQUEST['payid']);
    	$OrderID = intval($_REQUEST['orderid']);
    	$where = array('MemberID' => $MemberID, 'OrderID' => $OrderID);
    	$current = $m->where($where)->field('PayStatus,PayID,TotalPrice,ShippingPrice')->find(); //获取当前支付状态
    	//PayStatus：2：未支付、1：已支付
    	if($current['PayStatus'] == '2'){
    		//=={表示重新选择了支付方式，需要重新计算总金额，和设置PayID
    		if( $PayID != $current['PayID']){
    			$mp = D('Admin/Pay');
    			$payData = $mp->field('PayRate,PayTypeID')->find($PayID);
    			if( $payData['PayRate'] == 0 ){
    				$PayPrice = 0;
    			}else{
	    			$PayPrice = ($current['TotalPrice'] + $current['ShippingPrice']) * $payData['PayRate'];
	    			$PayPrice = sprintf("%.2f", $PayPrice);
    			}
    			$new = array('PayID'=>$PayID, 'PayPrice'=>$PayPrice);
    			$n = $m->where($where)->setField($new);
    		}
    		//==}
    		$data = $m->findOrder($_REQUEST['orderid'], array('MemberID'=>$MemberID));
    		$data['PayIcon'] = 1; //1: ok，-1：不显示图标，用于对话框图标显示
    		$PayTypeID = $data['PayTypeID'];
    		switch ($PayTypeID){
    			case 5: //5: 银行汇款/转账
    			case 6: //6: 货到付款
    				$data['PayTip'] = L('FriendTip');
    				$data['PayContent'] = L('OfflinePayTip');
    				break;
    			case 7: //余额支付
    				import("@.Common.YdPay");
    				$obj = pay_factory_create($PayTypeID);
    				$data['MemberID'] = $MemberID;
    				$data = $obj->setConfig( $data ); 
    				break;
    			case 8: //银联支付
    				import("@.Common.YdPay");
    				$obj = pay_factory_create($PayTypeID, $data['SiteType']);
    				$obj->setConfig( $data );   //设置参数
    				$PayUrl = $obj->getPayUrl();  //返回一个表单并自动提交post
    				echo $PayUrl;
    				exit();
    				break;
    			case 1: //支付宝支付
    				import("@.Common.YdPay");
    				$obj = pay_factory_create($PayTypeID, $data['SiteType']);
    				$obj->setConfig( $data );   //设置参数
    				$data['PayUrl'] = $obj->getPayUrl();  //获取付款链接
    				header("Location: ".$data['PayUrl']);
					exit();
    				break;
    			case 4: //Paypal标准支付
    				import("@.Common.YdPay");
    				$obj = pay_factory_create($PayTypeID);
    				$obj->setConfig( $data );   //设置参数
    				$data['PayUrl'] = $obj->getPayUrl();  //获取付款链接
    				header("Location: ".$data['PayUrl']);
    				exit();
    				break;
    			case 10: //微信支付
    				import("@.Common.YdPay");
    				$obj = pay_factory_create($PayTypeID, $data['SiteType']);
    				$obj->setConfig( $data );   //设置参数
    				if( $obj->getType() == 'NATIVE'){
    					$data['PayUrl'] = $obj->getPayUrl();
    					$data['PayTip'] = L('WeiXinPayScanTip');
    					$PayImageSrc = empty($data['PayUrl']) ? '' : PayQrcode( $data['PayUrl'] );
    					$data['PayIcon'] = -1;
    					$data['PayContent'] = '';
    					if( !empty($PayImageSrc) ){
    						$data['PayContent'] = "<img src='{$PayImageSrc}' style='width:180px;height:180px;display:block;margin:0 auto' />";
    					}
    				}else{
    					//必须提前获取
    				    if( !empty($_GET['openid']) ){
	    					$obj->openid = $_GET['openid'];
	    				    $data['PayJson'] = $obj->getPayUrl(); //微信公众号支付返回的是json数据
    					}else{
    						$data['PayJson'] = '';
    					}
    				}
    				break;
    		}
    		unset($data['AccountName'], $data['AccountPassword'], $data['AccountKey'], $data['AccountID']);
    		$this->ajaxReturn($data, false, 1);
    	}else{
    		//已经支付完成，不用重复支付
    		$data['PayIcon'] = 1;
    		$data['PayTip'] = L('FriendTip');
    		$data['PayContent'] = L('OrderOnlinePayFinish');
    		$this->ajaxReturn($data, false, 1);
    	}
    }
    
    //支付页面，订单提交成功后，进入支付页面
    public function pay(){
    	header("Content-Type:text/html; charset=utf-8");
    	C('TOKEN_ON',false);  //禁止表单令牌，防止影响支付签名
        $PayClass = YdInput::checkLetterNumber($_GET['classname']);
    	$type = strtolower($_REQUEST['type']);
    	switch ($type){
    		case 'returnurl':  //支付同步转向页面
    			import("@.Common.YdPay");
    			$obj = new $PayClass();
    			$data = $obj->returnurl();
    			break;
    		case 'notifyurl':  //支付端异步通知
    			import("@.Common.YdPay");
    			$obj = new $PayClass();
    			$obj->notifyurl();
    			exit();
    		default:  //开始支付页面
    			$MemberID = (int)session('MemberID');
    			if( empty($MemberID) ) redirect( HomeUrl() );
    			//如果是微信，必须先通过回调获取Code
    			if( stripos($_SERVER['HTTP_USER_AGENT'],"MicroMessenger") ){
    				import("@.Common.YdPay");
    				$wx = new YdWxPay();
    				$mp = D('Admin/Pay');
    				$result = $mp->where('PayTypeID=10 and IsEnable=1')->field('AccountID,AccountPassword')->find();
    				$appid = $result['AccountID'];
    				$appsecret = $result['AccountPassword'];
    				if( isset( $_GET['code']) ){
    					$openid = $wx->getOpenidByCode( $_GET['code'], $appid, $appsecret);
    					$this->assign('OpenID', $openid);
    				}else{
    					if(!empty($appid)){
    						$redirectUrl = $wx->getCodeUrl($appid);
    						header("Location:{$redirectUrl}"); //页面将跳转至 redirect_uri/?code=CODE&state=STATE
    						exit();
    					}
    				}
    			}
    			$m = D('Admin/Order');
	    		$data = $m->findOrder($_REQUEST['orderid'], array('MemberID'=>$MemberID));
    	}
    	unset($data['IsEnable'], $data['LanguageID']);
    	unset($data['AccountName'], $data['AccountPassword'], $data['AccountKey'], $data['AccountID']);
    	$SitePath = $this->_getSitePath( 'pay' );
    	$this->assign('SitePath', $SitePath);
    	$this->assign($data);
    	$this->display();
    }
    
    //添加到购物车
    function addCart(){
    	$InfoID = $_REQUEST['id'];
    	$Quantity = empty($_REQUEST['quantity']) ? 1 : intval($_REQUEST['quantity']);
    	$valueid = empty($_REQUEST['valueid'] ) ? '' : $_REQUEST['valueid'];
    	if( !is_numeric($InfoID) ){
    		$this->ajaxReturn(null, L('AddCartFail') , 0);
    	}
    	
    	import("@.Common.YdCart");
    	$cart = YdCart::getInstance();
    	$b = $cart->has($InfoID, $valueid);
    	if($b){
    		$this->ajaxReturn(null, L('AddCartRepeat') , 2);
    	}
    	
    	$cart->add($InfoID, $Quantity, $valueid);
    	$p['TotalItemCount'] = $cart->getItemCount();
    	$p['TotalItemPrice'] = $cart->getTotalPrice($InfoID);
    	$p['TotalPrice'] = $cart->getTotalPrice();
    	$this->ajaxReturn($p, L('AddCartSuccess') , 1);
    }
    
    //删除商品
    function deleteCart(){
    	$CartID = $_REQUEST['id']; //组合的数比较大，不能用intval转换
    	import("@.Common.YdCart");
    	$cart = YdCart::getInstance();
    	$cart->delete($CartID);
    	$p['TotalItemCount'] = $cart->getItemCount();
    	$p['TotalItemPrice'] = $cart->getTotalPrice($CartID);
    	$p['TotalPrice'] = $cart->getTotalPrice();
    	$this->ajaxReturn($p, '' , 1);
    }
    
    //清空购物车
    function clearCart(){
    	import("@.Common.YdCart");
    	$cart = YdCart::getInstance();
    	$cart->clear();
    	$this->ajaxReturn(null, '' , 1);
    }
    
    //设置商品数量
    function _setQuantity($id, $n, $type=1){
    	import("@.Common.YdCart");
    	$cart = YdCart::getInstance();
    	$b = $cart->setQuantity($id, $n, $type);
    	if($b){
    		$p['TotalItemCount'] = $cart->getItemCount();
    		$p['TotalItemPrice'] = $cart->getTotalPrice($id);
    		$p['TotalPrice'] = $cart->getTotalPrice();
    		$p['ProductQuantity'] = $b;
    		$this->ajaxReturn($p, '' , 1);
    	}else{
    		$this->ajaxReturn(null, '' , 0);
    	}
    }
    
    //设置商品数量
    function setQuantity(){
    	$this->_setQuantity($_REQUEST['id'], $_REQUEST['quantity'], 1);
    }
    
    //增加数量
    function incQuantity(){
    	$this->_setQuantity($_REQUEST['id'], 0, 2);
    }
    
    //减少数量
    function decQuantity(){
    	$this->_setQuantity($_REQUEST['id'], 0, 3);
    }
    
    //清空历史记录
    function clearHistory(){
    	$h = YdHistory::getInstance();
		$h->clear(); //清空历史记录
    	$this->ajaxReturn(null, '' , 1);
    }
    //==================================================
}