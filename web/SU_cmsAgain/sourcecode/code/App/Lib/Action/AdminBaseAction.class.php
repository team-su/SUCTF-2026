<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
if (!defined('APP_NAME')) exit();
class AdminBaseAction extends BaseAction {
    protected $pluginDir = './App/Tpl/Admin/Default/Plugin/';
	function _initialize(){
	    $this->_checkAdminWhiteIP();
	    $this->checkSafeQuestion();
		$mName = strtolower(ACTION_NAME);
		$NoCheckAction = array(
		    'login', 'verify','checklogin','showcode','logout', 'uc',
            'getloginqrcode', 'checkloginqrcode',
        ); //免登录验证模块


        if(!$this->isLogin() && !in_array($mName, $NoCheckAction)){ //没有登录，将返回登录页面
            $this->_checkAjaxRequest();
            redirect(__GROUP__."/Public/login");
        }
        if( !$this->checkPurview() ){
            redirect(__GROUP__."/Public/welcome");
        }

		$this->assign("AdminName", session("AdminName") );
		$this->assign("AdminGroupName", session("AdminGroupName") );

        $UploadDirType = (int)$GLOBALS['Config']['UPLOAD_DIR_TYPE'];
        $this->assign("UploadDirType", $UploadDirType);

		$this->AdminPageSize = $GLOBALS['Config']['ADMIN_PAGE_SIZE'] <= 0 ? 20 : $GLOBALS['Config']['ADMIN_PAGE_SIZE'];
		$this->AdminRollPage = $GLOBALS['Config']['ADMIN_ROLL_PAGE'] <= 0 ? 30 : $GLOBALS['Config']['ADMIN_ROLL_PAGE'];
		$this->assign("AdminPageSize", $this->AdminPageSize );
		
		parent::_initialize();
		$this->assign('LanguageID', session('AdminLangSet'));
	}

    /**
     * 后台IP白名单检查
     */
	private function _checkAdminWhiteIP(){
        $WhiteIPList = $GLOBALS['Config']['AdminWhiteIP'];
        if(empty($WhiteIPList) && strlen($WhiteIPList)<7 ) return;
        $isExist = false;
        $currentIP = get_client_ip();
        $WhiteIPList = str_replace(array("\r\n","\r"), "\n", $WhiteIPList);
        $WhiteIPList = explode ("\n", $WhiteIPList);
        foreach($WhiteIPList as $ip){
            if(false !== stripos($ip, '/')){ //192.168.1.3/10
                $ip1 = explode('.', $currentIP);
                $ip2 = explode('.', $ip);
                $t = explode('/', $ip2[3]);
                $lastItem = $ip1[3]; //当前IP最后一位
                if($ip1[0]===$ip2[0] && $ip1[1]===$ip2[1] && $ip1[2]===$ip2[2] &&
                    $lastItem>=$t[0] && $lastItem<=$t[1] ){
                    $isExist = true;
                    break;
                }
            }else{
                if($ip===$currentIP){
                    $isExist = true;
                    break;
                }
            }
        }

        if(!$isExist){
            $with = strtolower($_SERVER['HTTP_X_REQUESTED_WITH']);
            if($with=='xmlhttprequest'){
                $this->ajaxReturn(null, "禁止{$currentIP}访问！" , 0);
            }else{
                exit("禁止{$currentIP}访问！");
            }
        }
    }

	//检查后端是否是post请求，如果是就以ajax
	private function _checkAjaxRequest(){
        if(MODULE_NAME=='Multixcx'){
            $this->adminApiReturn(null, "登录超时，请重新登录！" , 0);
        }
        $with = strtolower($_SERVER['HTTP_X_REQUESTED_WITH']);
        if($with=='xmlhttprequest'){
            $this->ajaxReturn(null, "登录超时，请重新登录！" , 0);
        }
    }

	//权限检查 0:检查菜单，1：顶层菜单，2：树形频道
	function checkPurview(){
		$gid = intval(session('AdminGroupID'));
		if( $gid === 1 ) return true;  //超级管理员拥有所有权限
		
		$mName = strtolower( MODULE_NAME);
		$aName = strtolower( ACTION_NAME);
		$m = D('Admin/AdminGroup');
		if( $mName == 'channel') { //树形频道权限判断
			$list = $m->getChannelPurview( $gid );
			$id = $_REQUEST['ChannelID'];
		}else if( $mName == 'info' ){
			//已在info模块做了判断，这里无须判断
			return true;
			//$list = $m->getChannelPurview( $gid );
			//$id = $_REQUEST['ChannelID'];
		}else if( $mName == 'public' && $aName == 'memberleft') {//顶层菜单权限判断
			$list = $m->getMenuTopPurview( $gid );
			$id = $_REQUEST['MenuTopID'];
		}else{ //菜单权限判断
			$list = $m->getMenuPurview( $gid );
            $list = $this->processSpecialMenu($list);
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

	private function processSpecialMenu($list){
	    if(empty($list)) return '';
	    $tempList = explode(',', $list);
	    $idlist = '';
	    if(in_array('166', $tempList)){ //商城
	        $idlist .= '78,124,125,127,128,131,141,153,';
        }
	    if(in_array('151', $tempList)){ //邮件群发
            $idlist .= '24,25,26,151,';
        }

	    if(!empty($idlist)){
            $m = D('MenuOperation');
            $data = $m->where("MenuID IN('{$idlist}')")->field('MenuOperationID')->select();
            foreach($data as $v){
                $idlist .= ",{$v['MenuOperationID']}";
            }
            $list .= ','.trim($idlist, ',');
        }
	    return $list;
    }
	
	//是否登录
	function isLogin(){
		$b = session("?AdminID") && session("?AdminName") && session("AdminID")>0;
		return $b;
	}
	
	//模板上传[电脑模板和手机模板]
	function uploadTemplate() {
	    return; //存在安全问题，关闭上传模板功能
		set_time_limit(300);
		import("ORG.Net.UploadFile");
		$upload = new UploadFile();
		//设置上传文件大小
		$upload->maxSize  = $GLOBALS['Config']['MAX_UPLOAD_SIZE'] ; //最大上传大小
		//设置上传文件类型
		$upload->allowExts  = array('zip');
		//设置附件上传目录
		$upload->savePath =  RUNTIME_PATH; //模板上传到临时文件夹
		$upload->saveRule= time;
	
		if(!$upload->upload()) {
			$this->ajaxReturn(null, $upload->getErrorMsg() , 0);
		}else{
			$info =  $upload->getUploadFileInfo();
			//解压模板
			import('ORG.Util.PclZip');
			$tplDir = ($_POST['ishome'] == 1) ? TMPL_PATH.'Home/' : TMPL_PATH.'Wap/';
			$zipname = RUNTIME_PATH.$info[0]['savename'];
			$archive = new PclZip($zipname);
			if (($list = $archive->listContent()) == 0) {
				$this->ajaxReturn(null, '安装模板失败!' , 0);
			}else{
				//判断模板目录是否存在
				$currentDir = $tplDir.$list[0]['filename'];  //获取模板文件名
				if( is_dir($currentDir)){
					$this->ajaxReturn(null, '模板目录已经存在!请打开zip压缩包重命名根目录名,再重新安装!' , 0);
				}

				//判断是否是有效模板=====================================
				$count = count($list);
				$IsValid = false;
				for($n = 0; $n < $count; $n++){
				    $filename = strtolower($list[$n]['filename']);
					if( $list[$n]['folder'] == true && stripos($filename, 'channel/')  ){
						$IsValid = true;
						break;
					}
				}
                if( !$IsValid ){
                    $this->ajaxReturn(null, '无效模板压缩包!' , 0);
                }
                //================================================

                //模板里的文件名不能包含php、jsp、asp、aspx等危险文件============
                $map = array('php'=>true, 'jsp'=>true, 'asp'=>true, 'aspx'=>true);
                for($n = 0; $n < $count; $n++){
                    if( $list[$n]['folder']) continue;
                    $filename = strtolower($list[$n]['filename']);
                    if(stripos($filename, '/common_en.php') || stripos($filename, '/common_cn.php') ){

                    }else{
                        $ext = trim(strtolower(yd_file_ext($filename))); //必须去除2边的空格
                        if(isset($map[$ext])){
                            $this->ajaxReturn(null, "模板不能包含{$ext}文件" , 0);
                        }
                    }
                }
                //==============================================
			}
				
			//解压模板压缩包到模板目录
			if ($archive->extract(PCLZIP_OPT_PATH, $tplDir) == 0) {
				@unlink($zipname);
				$this->ajaxReturn(null, '安装模板失败!' , 0);
			}else{
				@unlink($zipname);
                $this->checkTemplateLangFile($currentDir);
				$this->ajaxReturn(null, '安装模板成功!' , 1);
			}
		}
	}

    /**
     * 检查模板语言包php文件是否有效
     * 如果是无效的则直接删除
     */
	private function checkTemplateLangFile($currentDir){
        $langs = array('cn', 'en');
        foreach($langs as $v){
            $langFile = "{$currentDir}Lang/common_{$v}.php";
            if(file_exists($langFile)){
                $content = file_get_contents($langFile);
                $content = str_ireplace('<?php', '', $content);
                $content = trim($content);
                if('return array' != substr($content,0,12)){
                    @unlink($langFile);
                }
            }
        }
    }
	
	//判断是否是一个有效的模板文件 $fileFullName文件全路径
	//防止非法保存文件
	function isValidTplFile($fileFullName, $type='Home'){
	    //去掉xml，容易产生xml注入漏洞
		$allowedExt = array('html','htm','shtml','js','css');
		$ext = strtolower(yd_file_ext($fileFullName));
		if( !in_array($ext, $allowedExt) || !file_exists($fileFullName) ){
			return false;
		}
		return true;
	}
	
	//编辑模板文件前预处理
	function preModifyTplFile($fileFullName){
		$IsWritable = yd_is_writable($fileFullName) ? 1 : 0;
		$BgColorText = ($IsWritable) ? '' : ' background:#eee;';
		$ReadOnlyText = ($IsWritable) ? '' : ' readonly="readonly" ';
		$DisableText = ($IsWritable) ? '' : ' disabled="disabled" ';
		$this->assign('IsWritable', $IsWritable);
		$this->assign('BgColorText', $BgColorText);
		$this->assign('ReadOnlyText', $ReadOnlyText);
		$this->assign('DisableText', $DisableText);
	}
	
	//切换数据状态（主要是2种状态）
	function toggleStatus(){
		$id = intval($_GET['id']);
		$fieldValue = intval( $_GET['FieldValue']);
		$fieldName = $_GET['FieldName'];
		$tableName = strtolower($_GET['TableName']);
		if( $id <= 0 || empty($fieldName) || empty($tableName)){
			$this->ajaxReturn(null, '参数错误' , 0);
		}else{
			switch($tableName){
				case 'link':
					if($fieldName == 'IsEnable') D('Admin/Link')->where("LinkID={$id}")->setField('IsEnable',$fieldValue); break;
				case 'mail_class':
					if( $fieldName == 'IsEnable') D('Admin/MailClass')->where("MailClassID={$id}")->setField('IsEnable',$fieldValue); break;
				case 'mail':
					if( $fieldName == 'IsEnable') D('Admin/Mail')->where("MailID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'ad_group':
					if($fieldName == 'IsEnable') D('Admin/AdGroup')->where("AdGroupID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'ad':
					if( $fieldName == 'IsEnable') D('Admin/Ad')->where("AdID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'banner':
					if( $fieldName == 'IsEnable') D('Admin/Banner')->where("BannerID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'member':
					if( $fieldName == 'IsLock') D('Admin/Member')->where("MemberID={$id}")->setField('IsLock',$fieldValue);
					if( $fieldName == 'IsCheck') D('Admin/Member')->where("MemberID={$id}")->setField('IsCheck',$fieldValue);break;
				case 'admin':
					if( $fieldName == 'IsLock') D('Admin/Admin')->where("AdminID={$id}")->setField('IsLock',$fieldValue);break;
				case 'support':
					if( $fieldName == 'IsEnable') D('Admin/Support')->where("SupportID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'shipping':
					if( $fieldName == 'IsEnable') D('Admin/Shipping')->where("ShippingID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'pay':
					if( $fieldName == 'IsEnable') D('Admin/Pay')->where("PayID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'job':
					if( $fieldName == 'IsEnable') D('Admin/Job')->where("JobID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'tag':
					if( $fieldName == 'IsEnable') D('Admin/Tag')->where("TagID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'type':
					if( $fieldName == 'IsEnable') D('Admin/Type')->where("TypeID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'special':
					if( $fieldName == 'IsEnable') D('Admin/Special')->where("SpecialID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'channel':
					if( $fieldName == 'IsShow') D('Admin/Channel')->where("ChannelID={$id}")->setField('IsShow',$fieldValue);
					if( $fieldName == 'IsLock') D('Admin/Channel')->where("ChannelID={$id}")->setField('IsLock',$fieldValue);
					if( $fieldName == 'IsEnable') D('Admin/Channel')->where("ChannelID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'info':
					if( $fieldName == 'IsEnable') D('Admin/Info')->where("InfoID={$id}")->setField('IsEnable',$fieldValue);
					if( $fieldName == 'IsCheck') D('Admin/Info')->where("InfoID={$id}")->setField('IsCheck',$fieldValue);break;
				case 'type_group':
					if( $fieldName == 'IsEnable') D('Admin/TypeGroup')->where("TypeGroupID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'type_attribute':
					if( $fieldName == 'IsEnable') D('Admin/TypeAttribute')->where("TypeAttributeID={$id}")->setField('IsEnable',$fieldValue);
					if( $fieldName == 'IsSearch') D('Admin/TypeAttribute')->where("TypeAttributeID={$id}")->setField('IsSearch',$fieldValue);break;
				case 'channelmodel':
					if( $fieldName == 'IsEnable') D('Admin/ChannelModel')->where("ChannelModelID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'guestbook':
					if( $fieldName == 'IsCheck') D('Admin/Guestbook')->where("MessageID={$id}")->setField('IsCheck',$fieldValue);break;
				case 'comment':
					if( $fieldName == 'IsCheck') D('Admin/Comment')->where("CommentID={$id}")->setField('IsCheck',$fieldValue);break;
				case 'wx_menu':
					if( $fieldName == 'IsEnable') D('Admin/WxMenu')->where("MenuID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'wx_reply':
					if( $fieldName == 'IsEnable') D('Admin/WxReply')->where("ReplyID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'wx_app':
					if( $fieldName == 'IsEnable') D('Admin/WxApp')->where("AppID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'wx_gift':
					if( $fieldName == 'IsEnable') D('Admin/WxGift')->where("GiftID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'wx_coupon':
					if( $fieldName == 'IsEnable') D('Admin/WxCoupon')->where("CouponID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'secret':
					if( $fieldName == 'IsEnable') D('Admin/Secret')->where("SecretID={$id}")->setField('IsEnable',$fieldValue);break;
				case 'menu_top':
					if($fieldName == 'IsEnable') D('Admin/MenuTop')->where("MenuTopID={$id}")->setField('IsEnable',$fieldValue); break;
				case 'menu_group':
					if($fieldName == 'IsEnable') D('Admin/MenuGroup')->where("MenuGroupID={$id}")->setField('IsEnable',$fieldValue); break;
				case 'menu':
					if($fieldName == 'IsEnable') {
						D('Admin/Menu')->where("MenuID={$id}")->setField('IsEnable',$fieldValue); 
						//禁止三级分销插件的同时，需要关闭分销功能
						if($fieldValue==0 && $id==148){
							$mc = D('Admin/Config');
							$mc->where("ConfigID=293")->setField('ConfigValue',0);
						}
					}
					break;
				case 'area':
					if($fieldName == 'IsEnable') D('Admin/Area')->where("AreaID={$id}")->setField('IsEnable',$fieldValue); break;
                case 'site':
                    if($fieldName == 'IsEnable') D('Admin/Site')->where("SiteID={$id}")->setField('IsEnable',$fieldValue); break;
                case 'language':
                    if($fieldName == 'IsEnable') D('Admin/Language')->where("LanguageID={$id}")->setField('IsEnable',$fieldValue); break;
			}
			$this->ajaxReturn(null, '设置成功' , 1);
		}
	}

    /**
     * 主要用户后端标准api返回（apiReturn是旧版）
     * 目前主要是多端小程序装修端调用
     */
    protected function adminApiReturn($data, $msg='', $status=1) {
        $data['Status'] = $status;
        $data['Message'] = $msg;
        if(APP_DEBUG){
            $ApiExecTime = microtime(TRUE) - $GLOBALS['ApiStartTime'];
            $data['Debug']['ApiExecTime'] = number_format($ApiExecTime, 3) . 's';
            if(MEMORY_LIMIT_ON) {
                $ApiUseMemory = (memory_get_usage() - $GLOBALS['ApiStartMemory'])/1024/1024;
                $data['Debug']['ApiUseMemory'] = number_format($ApiUseMemory,3).'M';
            }
            if( class_exists('Db',false) ) {
                $data['Debug']['ApiDbRead'] = N('db_query');
                $data['Debug']['ApiDbWrite'] = N('db_write');
            }
            $data['Debug']['ApiLoadFile'] = count(get_included_files());
            $fun  =  get_defined_functions();
            $data['Debug']['UserFunction'] = count($fun['user']);
            $data['Debug']['InternalFunction'] = count($fun['internal']);
        }
        $data['Timestamp'] = time(); //返回服务器时间戳给客户端

        header('Content-Type:text/html; charset=utf-8');
        header("Access-Control-Allow-Origin: *");
        $options = version_compare(PHP_VERSION, '5.4.0', '>=') ? JSON_UNESCAPED_UNICODE : 0;
        $data = json_encode($data, $options);
        exit($data);
    }

    protected function checkAdmin($ajaxReturn=true){
        $gid = intval(session('AdminGroupID'));
        $isSuperAdmin = ($gid == 1) ? 1 : 0;
        if( !$isSuperAdmin && $ajaxReturn) {
            $this->ajaxReturn(null, "只有超级管理员才能操作！", 0);
        }
        return $isSuperAdmin;
    }

    protected function checkSafeQuestion(){
        $answer = $GLOBALS['Config']['SafeAnswer'];
        if(empty($answer)) {
            $this->assign('SafeAnswerEnable', 0);
            return;
        }else{
            $this->assign('SafeAnswerEnable', 1);
        }
        $this->assign('SafeQuestion', $GLOBALS['Config']['SafeQuestion']);
        $IsSafeAnswer = (int)session('IsSafeAnswer'); //是否回答了安全问题
        if(1==$IsSafeAnswer){
            return;
        } ;
        $needCheck = false;
        $actionName = strtolower(ACTION_NAME);
        $moduleName = strtolower(MODULE_NAME);
        $whiteMap = array(
            'modifysafequestion'=>1,
            'add'=>1, 'modify'=>1, 'batchadd'=>1, 'modifyfield'=>1,
            'modifyclass'=>1, 'addclass'=>1, 'modifymenu'=>1,
            'modifykeywordreply'=>1,  'addkeywordreply'=>1,
            'modifyvote'=>1,'addvote'=>1, 'modifyquestion'=>1,'addquestion'=>1,
            'modifylottery'=>1,'addlottery'=>1,'modifyresearch'=>1,'addresearch'=>1,
            'modifygift'=>1,  'addgift'=>1,  'modifycoupon'=>1,  'addcoupon'=>1,
            'modifynotify'=>1,  'addnotify'=>1,
        );
        if(isset($whiteMap[$actionName])){
            return;
        }
        //上传设置
        if($moduleName=='config' && $actionName=='upload') return;
        if($moduleName=='plugin' && $actionName=='translate') return;
        //显示清除缓存界面直接返回
        if($actionName=='clearcache' && empty($_REQUEST['Action'])) return;

        $map = array(
            'sort'=>1, 'upload'=>1,
            'aliossmove'=>1,'aliossrenamedir'=>1,'aliossrename'=>1,'createdir'=>1,
            'uploadfile'=>1, 'makepinyin'=>1, 'transfer'=>1, 'createtemplate'=>1,
            'collectcontent'=>1, 'startsend'=>1,
            'backup'=>1, 'backupdata'=>1, 'dobackupall'=>1,'delzip'=>1,'getzipdownloadurl'=>1,
            'downloadall'=>1,'batchdelsqlfile'=>1,'delsqlfile'=>1,'downloadsqlfile'=>1,'recover'=>1,
            'ordercomponent'=>1,'backuptemplate'=>1,'answermessage'=>1,'batchcheckfeedback'=>1,
            'batchcheck'=>1,'translate'=>1,'batchlabel'=>1,'startimport'=>1,
            'batchlock'=>1,'batchmodifypwd'=>1,'setadmin'=>1,'take'=>1,''=>1,
            'createtemplate'=>1,'setdefaulttemplate'=>1,
            'installtemplate'=>1,'bindtester'=>1,'unbindtester'=>1,'submitaudit'=>1,'returnunauthorized'=>1,
            'setstatus'=>1,'upgradeplugin'=>1,'installplugin'=>1,'uninstallplugin'=>1,'enable'=>1,
            'setdistributor'=>1,'startimageprocess'=>1,
            'sendsmstest'=>1,'upgrade'=>1,'delfile'=>1,'renamedir'=>1,
            'createdir'=>1,'changefilename'=>1,'copyfile'=>1,
            'movefile'=>1,'setimagesize'=>1,'slimimage'=>1,'addwater'=>1,'cropimage'=>1,
            'getuploadtoken'=>1, 'startbaidupush'=>1,
            'backup'=>1,'enablemenu'=>1,'updatemenu'=>1,'clearmenu'=>1,'zerokeywordcount'=>1,
            'clearvote'=>1 ,'updatecardconfig'=>1, 'clearlottery'=>1, 'clearresearchdata'=>1,
            'changedirname'=>1, 'testemail'=>1, 'togglestatus'=>1, 'startcheckwords'=>1, 'inticheckwords'=>1,
            'clearcache'=>1,
            //uc相关操作
            'setucbindinfo'=>1, 'unbinduc'=>1,
            'phpinfo'=>1,
        );
        if(isset($map[$actionName])){
            $needCheck = true;
        }else{
            //所有编辑操作
            $list = array('save', 'del', 'modify', 'add', 'export', 'move', 'sort');
            foreach($list as $v){
                if(false !== strpos($actionName, $v)){
                    $needCheck = true;
                    break;
                }
            }
        }

        if($needCheck){
            //jQuery 发出 ajax 请求时，会在请求头部添加一个名为 X-Requested-With 的信息，信息内容为：XMLHttpRequest
            $with = strtolower($_SERVER['HTTP_X_REQUESTED_WITH']);
            if($with=='xmlhttprequest'){ //有bug，最好在多端小程序请求是，也提交HTTP_X_REQUESTED_WITH这个头
                $this->ajaxReturn(null, '二次安全验证失败！no-answer', 0);
            }else{
                $msgHtml = "<span style='color:red;'>抱歉，您未进行二次安全验证，无法操作！</span>";
                exit($msgHtml);
            }
        }
    }
}