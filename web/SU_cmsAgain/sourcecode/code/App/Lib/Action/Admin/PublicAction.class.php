<?php
class PublicAction extends AdminBaseAction{
	//--登录界面
	function index(){
		redirect(__URL__.'/Login');	
	}
	
	//登录界面
	 function login(){
		header("Content-Type:text/html; charset=utf-8");
		if( $this->isLogin() ){
			redirect( __URL__.'/adminIndex' );
		}
        //是否绑定UC
         import("@.Common.YdUcApi");
         $uc = new YdUcApi();
         $isBindUc = $uc->isBindUc();
         $this->assign('IsBindUc', $isBindUc?1:0);

        $this->showLicense();
		$this->deleteOldFile();
		$this->display();
		$this->showLoginAuthorize();
	}

	function uc(){
        header("Content-Type:text/html; charset=utf-8");
        $this->display();
    }
	
	function showCode(){
		$AdminName = trim($_GET['username']);
		$m = D('Admin/Admin');
		$showCode = $m->hasCode($AdminName);
		$this->ajaxReturn(null, '' , $showCode);
	}
	
	//退出系统
	function logOut(){
		header("Content-Type:text/html; charset=utf-8");
		$options['LogType'] = 8;
		$options['UserAction'] = '退出管理后台';
		WriteLog(session("AdminName"),$options);
        afterLogout();
		redirect(__URL__.'/Login');
	}
	
	 function checkLogin(){
	 	header("Content-Type:text/html; charset=utf-8");
         $admin = D('Admin/Admin');
	 	//用户名
		$AdminName = trim($_POST['username']);
         if(empty($AdminName)){
             session('verify', rand(1000, 9999) );
             $this->ajaxReturn(null, '用户名不能为空!' , 0);
         }

         $AdminName = $admin->getRealAdminName($AdminName);
         $showCode = $admin->hasCode($AdminName);
         if(empty($AdminName)){
             session('verify', rand(1000, 9999) );
             $this->ajaxReturn($showCode, '用户名或密码错误!' , 0);
         }

         //密码
         $AdminPassword = trim($_POST['password']);
         $AdminPassword = yd_safe_decode($AdminPassword);
         if(empty($AdminPassword)){
			session('verify', rand(1000, 9999) );
			$this->ajaxReturn(null, '密码不能为空!' , 0);
		}

        $verifyCode = trim($_POST['verifycode']);
		if( $showCode == 1 ){ //只有等于1时才需要显示验证码
			if(empty($verifyCode)){
				session('verify', rand(1000, 9999) );
				$this->ajaxReturn(1, '请输入验证码!' , 0);
			}
			
			$verifyCode2 = session('verify');
			if(md5($verifyCode) !== $verifyCode2){
				session('verify', rand(1000, 9999) );
				$this->ajaxReturn(1, '验证码错误!' , 0);
			}
		}
		
		$options['LogType'] = 8;
		$options['UserAction'] = '管理员登录';
		//0: 用户名或密码错误，1：用户被锁定，2:用户组不存在，数组：认证成功
		$result = $admin->checkLogin($AdminName, $AdminPassword );
		session('verify', rand(1000, 9999) );
		if( $result == 0 ){
			WriteLog("管理员{$AdminName}登录失败，用户名或密码",$options);
			$showCode = $admin->hasCode($AdminName);
			$this->ajaxReturn($showCode, '用户名或密码错误!' , 0);
		}else if($result == 1){
			WriteLog("管理员登录失败，{$AdminName}被锁定30分钟",$options);
			$this->ajaxReturn(null, "账户已被锁定30分钟！" , 1);
		}else if($result == 2){
			WriteLog("管理员登录失败，管理组不存在",$options);
			$this->ajaxReturn(null, '管理组不存在' , 2);
		}else if( is_array($result) ){ //认证成功
            $this->afterLoginSuccess($result);
			$this->ajaxReturn(rand(1000, 9999), '登录成功' , 3);
		}
	}

    /**
     * 登录成功
     */
	private function afterLoginSuccess($result, $isScanLogin=false){
        $AdminName = $result['AdminName'];
        $AdminID = (int)$result['AdminID'];
        $m = D('Admin/Admin');
        $m->UpdateLogin($AdminID);
        if($isScanLogin){ //扫码登录
            $logContent = "管理员【{$AdminName}】扫码登录成功";
        }else{
            $logContent = "管理员【{$AdminName}】登录成功";
        }
        $this->sendLoginNotify($AdminName, $isScanLogin);
        $this->saveAgreeLicenseData();
        $options = array();
        $options['LogType'] = 8;
        $options['UserAction'] = '管理员登录';
        WriteLog($logContent, $options);
        session('AdminID', $AdminID);
        session('AdminMemberID', $result['MemberID']);
        session('AdminName', $result['AdminName']);
        session('AdminGroupID', $result['AdminGroupID']);
        session('AdminGroupName', $result['AdminGroupName']);
        session('verify', rand(1000, 9999));
    }

    /**
     * 发送管理员登录通知
     */
    private function sendLoginNotify($AdminName, $isScanLogin){
        $c = &$GLOBALS['Config'];
        if(empty($c['ADMIN_LOGIN_SENDEMAIL'])) return true;
        $to = empty($c['ADMIN_LOGIN_EMAIL']) ? $c['EMAIL'] : $c['ADMIN_LOGIN_EMAIL'];
        $title = $c['ADMIN_LOGIN_EMAIL_TITLE'];
        if(empty($to) || empty($title)) return false;
        //邮件标题
        $title = str_ireplace('{$Name}', $AdminName, $title);
        $title = str_ireplace('{$WebName}', $c['WEB_NAME'], $title);
        //邮件内容
        $now = date("Y-m-d H:i:s");
        $ip = get_client_ip();
        $url = get_current_url(false);
        $body = "管理员【{$AdminName}】IP:{$ip}于{$now}";
        if($isScanLogin){ //扫码登录
            $body .= "扫码登录网站{$url}后台成功！";
        }else{
            $body .= "登录网站后台{$url}成功！";
        }
        //发送邮件
        $b = sendwebmail($to, $title, $body);
        if(false===$b){
            $errMsg = PHP_MAILER_ERROR;
        }
        return $b;
    }

    private function showLicense(){
        //读取协议内容
        $LicenseContent = file_exists('./license.txt') ? file_get_contents('./license.txt') : '';
        $LicenseContent = nl2br($LicenseContent);
        $this->assign('LicenseContent', $LicenseContent);
        $data = $this->getAgreeInfo();
        $isAgree = isset($data['IsAgree']) ? $data['IsAgree'] : 0;
        $this->assign('IsAgree', $isAgree);
    }

    private function saveAgreeLicenseData(){
        $data = $this->getAgreeInfo();
        if(isset($data['IsAgree'])) return;  //存在就不写入
        $fileName = APP_DATA_PATH.'install.lock';
        $info['IsAgree'] = 1;
        $info['AgreeTime'] = date("Y-m-d H:i:s");
        file_put_contents($fileName, json_encode($info));
    }

    private function getAgreeInfo(){
        $fileName = APP_DATA_PATH.'install.lock';
        $content = file_get_contents($fileName);
        if(empty($content)) return array();
        $data = json_decode($content, true);
        if(empty($data)) $data = array();
        return $data;
    }
	
	//获取当前MenuTopID
	private function getCurrentMenuTopID(){
		if( isset( $_GET['MenuTopID']  ) ){
			$id = intval($_GET['MenuTopID']);
			cookie("MenuTopID", $id);
		}else if( cookie("MenuTopID") ){
			$id = cookie("MenuTopID");
		}else{
			$id = 3;  //默认为3（内容管理）
		}
		return $id;
	}

	private function deleteOldFile(){
	    //删除文件
	    $fileList = array(
	        './Public/font/simkai.ttf',
            './Public/font/arial.ttf',
            './Public/font/verdana.ttf',
            './Public/ueditor/third-party/snapscreen/UEditorSnapscreen.exe',
        );
	    foreach($fileList as $file){
            if(file_exists($file)){
                unlink($file);
            }
        }

	    //删除目录
        $folderList = array('./Public/ckfinder');
        foreach($folderList as $dir){
            if(is_dir($dir)){
                @deldir($dir);
            }
        }

    }

	function adminTop(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/MenuTop');
		$gid = intval(session('AdminGroupID'));
		if( $gid == 1){ //超级管理员拥有所有权限
			$MenuTop = $m->getMenuTop( array("MenuOwner"=>1) );
		}else{
			$MenuTop = $m->getMenuTopPurview(1,  $gid);
		}
		$Group = __GROUP__;
		foreach($MenuTop as $k=>$v){
		    $MenuTopID = $v['MenuTopID'];
		    if(17 == $MenuTopID){ //应用中心
		        $MenuTop[$k]['MenuTopTarget'] = 'main';
                $MenuTop[$k]['MenuLink'] = "{$Group}/Plugin/index";
            }else{
                $MenuTop[$k]['MenuLink'] = "{$Group}/{$v['MenuTopUrl']}/MenuTopID/{$MenuTopID}";
            }
        }
		$MenuTopID = $this->getCurrentMenuTopID();
        //用户可以自行控制宽度
        $m = D('Admin/Config');
        $ChannelTreeWidth = $m->getConfigItem('ChannelTreeWidth', 'ConfigValue');
        if(empty($ChannelTreeWidth)) $ChannelTreeWidth = 0;
        $this->assign('ChannelTreeWidth', $ChannelTreeWidth);

		$this->assign('MenuTopID',$MenuTopID);
		$this->assign("MenuTop", $MenuTop );
		$this->display();
	}
	
	function adminLeft(){
		header("Content-Type:text/html; charset=utf-8");
		$MenuTopID = $this->getCurrentMenuTopID();
		$mg = D('Admin/MenuGroup');
		$m = D('Admin/Menu');
		$gid = intval(session('AdminGroupID'));
		if( $gid == 1){ //超级管理员
			$MenuGroup = $mg->getMenuGroup($MenuTopID);
			$Menu = $m->getMenu();
		}else{
			$MenuGroup = $mg->getMenuGroupPurview(1, $gid, $MenuTopID);
			$Menu = $m->getMenuPurview(1, $gid);
		}

		//避免升级改数据库，直接修改
		if($MenuTopID==1){
		    $n = count($Menu);
		    for($i =$n-1; $i>=0; $i--){
		        if($Menu[$i]['MenuID'] == 101){
		            $Menu[$i]['MenuContent'] = 'Language/index';
		            break;
                }
            }
        }
		
		//内容管理需要加载树形频道========================================
		if( $MenuTopID == 3 ){
			$c = D('Admin/Channel');
			$Channel = ($gid==1) ? $c->getChannelList(0,-1,'') : $c->getChannelPurview(1, $gid, '');
			$n = count($Channel);
			
			//找出ChannelDepth最大值===========================
			$maxDepth = -9999;
			for($j = 0; $j < $n; $j++){
				if( $Channel[$j]['ChannelDepth'] > $maxDepth ) {
					$maxDepth = $Channel[$j]['ChannelDepth'];
				}
			}
			//===========================================
			
			for($i = 0; $i < $n; $i++){
				$Channel[$i]['HasChild'] = $c->hasChildChannel($Channel[$i]['ChannelID']);
				$Channel[$i]['ChannelDepth'] = ($maxDepth - $Channel[$i]['ChannelDepth'] + 1);
			}

			//获取树形宽度
            $m = D('Admin/Config');
			$ChannelTreeWidth = $m->getConfigItem('ChannelTreeWidth', 'ConfigValue');
            $this->assign('ChannelTreeWidth', $ChannelTreeWidth);

			$this->assign('Channel', $Channel);
		}
		//========================================================
		$this->assign('MenuTopID',$MenuTopID);
		
		$this->assign('Menu', $Menu);
		$this->assign('MenuGroup',$MenuGroup);
		
		$this->assign("AdminName", session("AdminName") );
		$this->assign("AdminGroupName", session("AdminGroupName") );
		
		$this->display();
	}
	
	//页脚
	function adminBottom(){
		header("Content-Type:text/html; charset=utf-8");
		$this->display();
	}
	
	//管理首页
	function adminIndex(){
		header("Content-Type:text/html; charset=utf-8");
		//获取当前管理员登录的头像
        $m = D('Admin/Member');
        $MemberID = (int)session('AdminMemberID');
        $MemberAvatar = $m->getMemberAvatar($MemberID);
        $this->assign('MemberAvatar', $MemberAvatar);
        //当前顶级菜单ID
        $MenuTopID = $this->getCurrentMenuTopID();
        $this->assign('MenuTopID', $MenuTopID);
        //基本配置
        $m = D('Admin/Config');
        $data = $m->getConfig('basic'); //配置数据不从缓存中提取
        $this->assign('CheckUpdate', $data['CheckUpdate'] );

        //是否支持模板装修
        //$SupportDecoration = $this->_supportDecoration();
        //$this->assign('SupportDecoration', $SupportDecoration);
		$this->display();
	}

	//是否支持模板装修
	private function _supportDecoration(){
        $ThemeName = C('HOME_DEFAULT_THEME');
        $indexFile = TMPL_PATH.'Home/'.$ThemeName.'/Channel/index.html';
        $content = file_get_contents($indexFile);
        if(false!==stripos($content, 'yd-group')){
            return true;
        }else{
            return false;
        }
    }
	
	/**
	 * 设置语言
	 */
	function setLanguage(){
		redirect(__URL__.'/AdminIndex');
	}

    /**
     * 获取提示信息，在客户端显示弹框
     */
	function getTipInfo(){
	    $data = array();
        $answer = $GLOBALS['Config']['SafeAnswer'];
        $data['ShowSafeAnswerTip'] = empty($answer) ? 1 : 0;
        //目录权限检测
        $status = 1;
        DirDetection($status);
        $data['ShowDirTip'] = empty($status) ? 1 : 0;
        $this->ajaxReturn($data, '' , 1);
    }

	//管理首页
	function welcome(){
		header("Content-Type:text/html; charset=utf-8");
        //$x = crypt('mypassword', '$2a$07$usesomesillystringforsalt$');
		//获取服务器信息================
		import('@.Common.YdServerInfo');
		$s = new YdServerInfo();
		$info = $s->getServerInfo();
        $isDbReadonly = $s->isDbReadonly();
        $this->assign('IsDbReadonly', $isDbReadonly);
		$this->assign('Server', $info);
		//==========================

		
		//检测是否需要升级==================================
		/*
		import('@.Common.YdUpgrade');
		$u = new YdUpgrade($this->YouDianCMSVersion);
		$LastestVersion = $u->getLatestVersion();
		$LastestDate = $u->getLatestDate();
		$needUpgrade = $u->needUpgrade() ? 1 : 0;  //是否需要升级
		$this->assign('LastestDate', $LastestDate);
		$this->assign('LastestVersion', $LastestVersion);
		$this->assign('NeedUpgrade', $needUpgrade);
		*/
		//=============================================
		
		//获取管理员信息==============================
        /*
		$admin = D('Admin/Admin');
		$adminInfo = $admin->find( session('AdminID') );
		$this->assign('LastLoginTime', $adminInfo['LastLoginTime']);
		$this->assign('LastLoginIP', $adminInfo['LastLoginIP']);
		$this->assign('LoginCount', $adminInfo['LoginCount']);
        */
		//========================================

        //网站概况
        $m = D('Admin/Info');
        $Stat = $m->statInfo();
        $this->assign('StatList', $Stat);

        //删除半年前的日志（防止日志太大导致数据库大小超标）
        try{
            $m = D('Admin/Log');
            $maxDays = 30 * 6;  //6个月
            $time = date("Y-m-d H:i:s", strtotime("-{$maxDays} day"));
            $where = "LogTime<'{$time}'";
            $result = $m->where($where)->delete();
        }catch(Exception $e){

        }


        //获取mysql服务器的时间
        $ShowMysqlTime = 0;
        $host = C('DB_HOST');
        if('localhost'!=$host && '127.0.0.1' != $host){
            $ShowMysqlTime = 1;
        }
        $this->assign('ShowMysqlTime', $ShowMysqlTime);



		$this->display();
		//7.1版，将放在登陆界面显示
		//$this->showAuthorize();
	}
	
	//网站目录权限检测
	public function dirDetection(){
		$config = $this->getWapTpl();
		$wapConfigFile = $config['pWapConfig'];
		$config = $this->getHomeTpl();
		$homeConfigFile = $config['pHomeConfig'];
		//flag=0表示不检测可执行权限
		$list = array(
				//array('Name'=>'网站根目录',        'Dir'=>'./',                 'Flag'=>0, 'Suggest'=>'只读', 'Remark'=>'网站放在wwwroot目录下，将wwwroot设为只读，如果要生成网站地图，需要文件sitemap.html、sitemap.xml、sitemap.txt设为可读写'),
				array('Name'=>'数据目录',  'Dir'=>APP_DATA_PATH,    'Flag'=>1, 'Suggest'=>"可读写、关闭执行权限",  'Remark'=>'数据目录，存放数据库备份sql、全站备份zip、静态缓存html、系统缓存runtime等'),
				array('Name'=>'上传目录',           'Dir'=>'./Upload/',     'Flag'=>1, 'Suggest'=>"可读写、关闭执行权限",  'Remark'=>'上传的文件都存在此目录！'),
				array('Name'=>'系统配置目录',     'Dir'=>CONF_PATH,  'Flag'=>1, 'Suggest'=>"可读写、关闭执行权限",  'Remark'=>'系统配置文件，如：数据库配置、伪静态配置等'),
				array('Name'=>'电脑网站模板目录',  'Dir'=>TMPL_PATH.'Home/',  'Flag'=>1, 'Suggest'=>'只读',  'Remark'=>'如果需要在后台修改模板文件，才开启写入权限，建议设置为只读'),
				array('Name'=>'手机网站模板目录',  'Dir'=>TMPL_PATH.'Wap/',  'Flag'=>1, 'Suggest'=>'只读',  'Remark'=>'如果需要在后台修改模板文件，才开启写入权限，建议设置为只读'),
					
				//array('Name'=>'电脑网站模板配置',  'Dir'=>$homeConfigFile,  'Flag'=>0, 'Suggest'=>'可读写',  'Remark'=>'如要在后台使用【模板管理】-【电脑网站管理】-【模板设置】，请开启写入权限'),
				//array('Name'=>'手机网站模板配置',  'Dir'=>$wapConfigFile,  'Flag'=>0, 'Suggest'=>'可读写',  'Remark'=>'如要在后台使用【模板管理】-【手机网站管理】-【模板设置】，请开启写入权限'),
					
				//array('Name'=>'xml地图',  'Dir'=>'./sitemap.xml',  'Flag'=>0, 'Suggest'=>'可读写',  'Remark'=>''),
				//array('Name'=>'txt地图',  'Dir'=>'./sitemap.txt',  'Flag'=>0, 'Suggest'=>'可读写',  'Remark'=>''),
				//array('Name'=>'html地图',  'Dir'=>'./sitemap.html',  'Flag'=>0, 'Suggest'=>'可读写',  'Remark'=>''),
		);
		
		$n = count($list);
		for($i=0; $i < $n; $i++){
			if( file_exists($list[$i]['Dir']) ){
				$list[$i]['IsWritable'] = @yd_is_writable( $list[$i]['Dir'] );
				if( $list[$i]['Flag'] == 1 ){
					$list[$i]['IsExecutable'] = @yd_is_executable( $list[$i]['Dir'] );
				}
				$list[$i]['FileExist'] = 1;
			}else{
				$list[$i]['FileExist'] = 0;
			}
		}
		$this->assign('DirList', $list);
		$this->display();
	}
	
	private function showLoginAuthorize(){
		$hasChanged = $this->loginFileHasChanged();
		if($hasChanged){
			$AuthorizeScript="<script>
			$(document).ready(function(){
				$('#username').attr('disabled',true);
			 	$('#password').attr('disabled',true);
			 	$('.buttonface').attr('disabled',true);
			 	alert('非法篡改版权文件！');
			});
			</script>";
			echo $AuthorizeScript;  //直接输出，防止删除
		}

		//$_SERVER['HTTP_HOST']; //若端口号为非80端口，输出回包含端口号
		$para = '?version='.C('CMSVersion').'&host='.$_SERVER['HTTP_HOST'].'&os='.PHP_OS;
		$para .= '&cen='.C('CMSEnName').'&v=4&source='.$this->getSource();

		foreach (glob("./Data/*.id") as $filename) {
			$mid = basename($filename, '.id');
			if( is_numeric($mid)){
				$para .= '&mid='.$mid;
				break;
			}
		}
		$para .= '&time='.time(); //防止缓存
		$authUrl = "https://auth.youdiancms.com/authorize.php$para";
		$AuthorizeScript="<script>
			$(document).ready(function(){
				setTimeout(function(){
					$.getJSON('{$authUrl}&callback=?', function(data){
						$('#AuthorizeImage').html(data['AuthorizeImage']);
					});
				}, 350);
			});
		</script>";
		echo $AuthorizeScript;  //直接输出，防止删除
	}
	
	//检查登陆文件是否被篡改
	private function loginFileHasChanged(){
		return false;
	}

    private function getSource(){
        $source = 1;
        $fileName = APP_DATA_PATH.'source.lock';
        if(file_exists($fileName)){
            $source = file_get_contents($fileName);
        }
        return $source;
    }


	//检查是否有新版本
    function checkUpgrade(){
	    $lockFile = APP_DATA_PATH.'upgrade.lock';
        if(file_exists($lockFile)){
            $lockFile = substr($lockFile, 2);
            $this->ajaxReturn(null, "已锁定，不能升级！删除{$lockFile}解锁", 0);
        }

        import('@.Common.YdUpgrade');
        $u = new YdUpgrade();
        $data = $u->canUpgrade();
        if(is_array($data)){
            $this->ajaxReturn($data, '', 1);
        }else{
            $temp['version'] = $u->getLatestVersion();
            $this->ajaxReturn($temp, $data, 0);
        }
    }

	//在线升级
	function upgrade(){
        yd_set_time_limit(300);
	    $Step = intval($_POST['Step']); //升级步骤
        //强制升级，重新删除
        if(!empty($_POST['IsReUpgrade'])){
            C('CMSVersion', '9.0.0');
        }
        $this->_checkUpgradeParams();
		import('@.Common.YdUpgrade');
		$u = new YdUpgrade();
        $result = false;
        $msg = '';
        switch($Step){
            case 1: //第1步
                YdCache::deleteAll();  //升级前清除缓存
                $result = $u->downloadFile($_POST['Version']);
                $msg = '下载升级包';
                break;
            case 2: //第2步
                $result = $u->unzipFile($_POST['ZipFile']);
                $msg = '解压升级包';
                break;
            case 3: //第3步
                $result = $u->upgradeDb($_POST['Version'], $_POST['ReleaseDate']);
                $msg = '升级数据库';
                break;
        }

        if(is_array($result)){
            if(3 == $Step) {
                $options = array('LogType'=>1, 'UserAction'=>'在线升级');
                WriteLog("在线升级到版本v{$_POST['Version']}成功！", $options);
            }
            $this->ajaxReturn($result, "{$msg}完成！{$result['Time']}s", 1);
        }else{
            $lastError = $u->getLastError();
            $this->ajaxReturn(null, "{$msg}失败！{$lastError}", 0);
        }
	}

    /**
     * 检查升级参数
     */
	private function _checkUpgradeParams(){
	    //版本号
	    if(isset($_POST['Version']) && !empty($_POST['Version'])){
            $version = YdInput::checkLetterNumber($_POST['Version']);
            if(empty($version)){
                $this->ajaxReturn(null, "版本号格式错误！{$version}", 0);
            }
        }

	    //发布日期
        if(isset($_POST['ReleaseDate']) && !empty($_POST['ReleaseDate'])){
            $date = YdInput::checkLetterNumber($_POST['ReleaseDate']);
            if(empty($date)){
                $this->ajaxReturn(null, "发布日期错误！{$date}", 0);
            }
        }
    }

	//修改密码
	function pwd(){
		header("Content-Type:text/html; charset=utf-8");
		$this->assign('Action', __URL__.'/savePwd');
		$this->display();
	}

    //修改密码
    function savePwd(){
        header("Content-Type:text/html; charset=utf-8");
        $admin = D('Admin/Admin');
        $pwd1 = trim($_POST['pwd1']);  //原始密码
        $pwd2 = $_POST['pwd2'];
        $pwd3 = $_POST['pwd3'];
        if( empty($pwd1) ){
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

        if( $pwd1 == $pwd3 ){
            $this->ajaxReturn(null, '新密码不能和原始密码相同!' , 0);
        }
        $options['LogType'] = 8;
        $b = $admin->exist(session('AdminName'), $pwd1); //检查原始密码是否正确
        if(!$b){
            $options['UserAction'] = '修改密码';
            WriteLog(session('AdminName').'修改密码失败，原密码错误', $options);
            $this->ajaxReturn(null, '原密码错误!' , 0);
        }

        $adminID = (int)session('AdminID');
        $pwd2 = yd_password_hash($pwd2);
        $r = $admin->where("AdminID={$adminID}")->setField('AdminPassword', $pwd2);
        if($r){
            $options['UserAction'] = '修改密码';
            WriteLog(session('AdminName').'修改密码成功', $options);
            $this->ajaxReturn(null, '修改密码成功!' , 1);
        }else{
            $this->ajaxReturn(null, '修改密码失败!' , 0);
        }
    }
	
	function browser(){
		header("Content-Type:text/html; charset=utf-8");
		$this->display();
	}
	
	//清除系统缓存
	function clearCache(){
		header("Content-Type:text/html; charset=utf-8");
		$action = strtolower( $_REQUEST['Action'] );
		$p['LogType']=7;
		cookie("MenuTopID", null); //当顶部菜单为清除缓存时，不记录为模板选中
		switch ($action){
			case 'systemcache':  //清除系统缓存
                $m = D('Admin/Language');
                $m->updateLanguageConfig();
                $m = D('Admin/Log');
                $m->deleteExpiredLog();
				if( YdCache::writeAll() ){
					$p['UserAction'] = '清除系统缓存';
					WriteLog('', $p);
					$this->ajaxReturn(null, '清除系统缓存成功！', 1);
				}else{
					$this->ajaxReturn(null, '清除系统缓存失败！没有写入权限', 0);
				}
				break;
			case 'modelcache':  //清除频道模型缓存
				if(YdCache::deleteTemp()){
					$p['UserAction'] = '清除频道模型缓存';
					WriteLog('', $p);
					$this->ajaxReturn(null, '清除频道模型缓存成功！', 1);
				}else{
					$this->ajaxReturn(null, '清除频道模型缓存失败！没有写入权限', 0);
				}
				break;
			case 'homecache': 
				if(YdCache::deleteHome()){
					$p['UserAction'] = '清除模板缓存';
					WriteLog('', $p);
					$this->ajaxReturn(null, '清除模板缓存成功！', 1);
				}else{
					$this->ajaxReturn(null, '清除模板缓存失败！没有写入权限', 0);
				}
				break;
			case 'wapcache':
				if(YdCache::deleteWap()){
					$p['UserAction'] = '清除模板缓存';
					WriteLog('', $p);
					$this->ajaxReturn(null, '清除模板缓存成功！', 1);
				}else{
					$this->ajaxReturn(null, '清除模板缓存失败！没有写入权限', 0);
				}
				break;
			case 'indexhtmlcache': //清除网站首页Html静态缓存
				if( YdCache::deleteHtml('index')){
					$p['UserAction'] = '清除首页Html缓存';
					WriteLog('', $p);
					$this->ajaxReturn(null, '清除首页Html缓存成功！', 1);
				}else{
					$this->ajaxReturn(null, '清除首页Html缓存失败！没有写入权限', 0);
				}
				break;
			case 'channelhtmlcache': //频道首页Html静态缓存
				if( YdCache::deleteHtml('channel') ){
					$p['UserAction'] = '清除频道首页Html缓存';
					WriteLog('', $p);
					$this->ajaxReturn(null, '清除频道首页Html缓存成功！', 1);
				}else{
					$this->ajaxReturn(null, '清除频道首页Html缓存失败！没有写入权限', 0);
				}
				break;
			case 'infohtmlcache': //信息页面Html静态缓存
				if(YdCache::deleteHtml('info')){
					$p['UserAction'] = '清除内容页面Html缓存';
					WriteLog('', $p);
					$this->ajaxReturn(null, '清除内容页面Html缓存成功！', 1);
				}else{
					$this->ajaxReturn(null, '清除内容页面Html缓存失败！没有写入权限', 0);
				}
				break;
			case 'allhtmlcache': //所有Html静态缓存
				if(YdCache::deleteHtml('all')){
					$p['UserAction'] = '清除全部Html缓存';
					WriteLog('', $p);
					$this->ajaxReturn(null, '清除全部Html缓存成功！', 1);
				}else{
					$this->ajaxReturn(null, '清除全部Html缓存失败！没有写入权限', 0);
				}
				break;
			case 'saveconfig': //保存缓存配置
				if( isset($_POST) ){  //保存配置到数据库
					if( !is_numeric( $_POST['INDEX_CACHE_TIME']) || 
							!is_numeric( $_POST['CHANNEL_CACHE_TIME']) ||
							!is_numeric( $_POST['INFO_CACHE_TIME'])
					){
						$this->ajaxReturn(null, '缓存时间必须为数字！', 0);
					}
					
					$htmlEnable = ($_POST['HTML_ENABLE'] == 1) ? true : false;
					$IndexCacheTime = $_POST['INDEX_CACHE_TIME'];
					$ChannelCacheTime = $_POST['CHANNEL_CACHE_TIME'];
					$InfoCacheTime = $_POST['INFO_CACHE_TIME'];
					$html = array (
							'HTML_CACHE_ON' => $htmlEnable,
							'HTML_CACHE_RULES'=> array(
									'index:index'=>array('{:group}/index_{0|get_language_mark}', $IndexCacheTime),
									'channel:index'=>array('{:group}/channel/{id}{jobid}{infoid}_{0|get_language_mark}_{0|get_para}', $ChannelCacheTime),
									'info:read'=>array('{:group}/info/{id}_{0|get_para}', $InfoCacheTime),
							)
					);					
					if( YdCache::writeCoreConfig($html) ){
						YdCache::deleteAll(); //必须清除缓存，否则无法重新读取core.php
						$p['UserAction'] = '保存缓存配置';
						$p['LogType'] = 4;
						WriteLog('', $p);
						$this->ajaxReturn(null, '保存配置成功!' , 1);
					}else{
						$this->ajaxReturn(null, '保存配置失败!' , 0);
					}
				}
				break;
		}
		$core = C('HTML_CACHE_RULES');
		$HtmlEnable = C('HTML_CACHE_ON') ? 1 : 0;
		$IndexCacheTime = intval($core['index:index'][1]);
		$ChannelCacheTime = intval($core['channel:index'][1]);
		$InfoCacheTime = intval($core['info:read'][1]);
		
		$this->assign('HtmlEnable', $HtmlEnable );
		$this->assign('IndexCacheTime', $IndexCacheTime );
		$this->assign('ChannelCacheTime', $ChannelCacheTime);
		$this->assign('InfoCacheTime', $InfoCacheTime );
		$this->assign('Action', __URL__.'/clearCache' );

		$this->display();
	}
	
	function phpinfo(){
		header("Content-Type:text/html; charset=utf-8");
		echo phpinfo();
	}
	
	//获取网站总大小
	function getWebTotalSize(){
		header("Content-Type:text/html; charset=utf-8");
		$size = byte_format(getdirsize('./'));
		$uploadSize = byte_format(getdirsize('./Upload'));
		if( $size > 0 ){ //返回获取值
			$str = $size.'&nbsp;&nbsp;其中上传目录大小为：'.$uploadSize;
			$this->ajaxReturn($str, '' , 1);
		}else{ //获取失败
			$this->ajaxReturn(null, '' , 0);
		}
	}

	//回答安全问题
    function answerSafeQuestion(){
        $answer = trim($_POST['SafeAnswer']);
        if(empty($answer)){
            $this->ajaxReturn(null, '问题答案不能为空！' , 0);
        }
        $b = IsDbReadonly();
        if($b){
            $this->ajaxReturn(null, '验证失败，当前数据库为只读！' , 0);
        }

        $m = D('Admin/Config');
        $errorCount = 0;
        $errorText = '';
        $maxCount = 0; //最大次数
        $b = $m->checkSafeErrorCount($errorCount, $errorText, $maxCount);
        if(!$b){
            $this->ajaxReturn(null, $errorText , 0);
        }
        $isCorrect = $m->isSafeAnswerCorrect($answer);
        if($isCorrect){
            session('IsSafeAnswer', 1);
            $m->incSafeErrorCount(-1); //验证成功需要清0操作
            $this->ajaxReturn(null, '验证成功！' , 1);
        }else{
            $m->incSafeErrorCount($errorCount);
            $this->ajaxReturn(null, "问题答案不正确！(错误不能超过{$maxCount}次)" , 0);
        }
    }

    /**
     * 获取登录二维码
     */
    function getLoginQrcode(){
        import("@.Common.YdUcApi");
        $uc = new YdUcApi();
        $uc->getLoginUcQrcode();
    }

    /**
     * 检查二维码登录
     */
    function checkLoginQrcode(){
        import("@.Common.YdUcApi");
        $uc = new YdUcApi();
        $UcOpenID = $uc->checkLoginUcQrcode($_POST['SceneStr']);
        if(false !== $UcOpenID){
            $m = D('Admin/Admin');
            $result = $m->checkLoginUcOpenID($UcOpenID);
            if( is_array($result) ){ //认证成功
                $this->afterLoginSuccess($result, true);
                $this->ajaxReturn(null, '登录成功' , 3);
            }else{
                $this->ajaxReturn(null, '登录失败' , 0);
            }
        }else{
            $this->ajaxReturn(null, '' , 0);
        }
    }
}