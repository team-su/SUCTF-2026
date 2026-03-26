<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class AppAction extends AdminBaseAction {	
	function stat(){
		$p['HasPage'] = true;
		$p['ModuleName'] = 'AppStat';		
		
		if( !empty($_REQUEST['Platform']) ){
			$p['Parameter']['Platform'] = $_REQUEST['Platform'];
		}				
		
		if( !empty($_REQUEST['TimeSpan']) ){	//起止时间段
			$TimeSpan = intval( $_REQUEST['TimeSpan'] ) ;
		}else{
			$TimeSpan = 1;	//若没有选择，默认为全部时间段
		}
		$p['Parameter']['TimeSpan'] = $TimeSpan;
		
		switch($TimeSpan){
			case 1:	//全部时间段
				$p['Parameter']['StartTime'] = null;
				$p['Parameter']['EndTime'] = null;
				break;
			case 2:	//今天
				$p['Parameter']['StartTime'] = date('Y-m-d 00:00:00');
				$p['Parameter']['EndTime'] = date('Y-m-d 23:59:59');
				break;
			case 3:	//昨天
				$p['Parameter']['StartTime'] = date('Y-m-d 00:00:00',strtotime("-1 day"));
				$p['Parameter']['EndTime'] = date('Y-m-d 23:59:59',strtotime("-1 day"));
				break;
			case 4:	//最近7天
				$p['Parameter']['StartTime'] = date('Y-m-d 00:00:00',strtotime("-6 day"));
				$p['Parameter']['EndTime'] = date('Y-m-d 23:59:59');
				break;
			case 5:	//最近1年
				$p['Parameter']['StartTime'] = date('Y-m-d 00:00:00',strtotime("-1 year 1 day"));
				$p['Parameter']['EndTime'] = date('Y-m-d 23:59:59');
				break;
			case 9:	//自定义时间段				
				$p['Parameter']['StartTime'] = !empty($_REQUEST['StartTime']) ? $_REQUEST['StartTime'] : date('Y-m-d 00:00:00');
				$p['Parameter']['EndTime'] = !empty($_REQUEST['EndTime']) ? $_REQUEST['EndTime'] : date('Y-m-d 23:59:59');
				break;
		}
		$m = D('Admin/AppStat');
		
		$p1['Platform'] = 'Android';
		$AndroidCount = $m -> getAppStatCount($p1);
		$p1['Platform'] = 'iOS';
		$iOSCount = $m -> getAppStatCount($p1);
		$TotalCount = $AndroidCount + $iOSCount;
		$this->assign('AndroidCount',$AndroidCount);
		$this->assign('iOSCount',$iOSCount);
		$this->assign('TotalCount',$TotalCount);
		
		$p1['StartTime'] = $p['Parameter']['StartTime'];
		$p1['EndTime'] = $p['Parameter']['EndTime'];
		if($p['Parameter']['Platform'] == "iOS"){
			$AndroidQueryCount = 0;
		}else{
			$p1['Platform'] = 'Android';
			$AndroidQueryCount = $m -> getAppStatCount($p1);
		}
		if($p['Parameter']['Platform'] == "Android"){
			$iOSQueryCount = 0;
		}else{
			$p1['Platform'] = 'iOS';
			$iOSQueryCount = $m -> getAppStatCount($p1);
		}
		$this->assign("AndroidQueryCount",$AndroidQueryCount);
		$this->assign("iOSQueryCount",$iOSQueryCount);
		$this->assign("TotalQueryCount",$AndroidQueryCount+$iOSQueryCount);		
		$this->assign('TimeSpan',$TimeSpan);
		
		$this->opIndex($p);
	}
	
	function feedback(){
		$p['HasPage'] = true;
		$p['ModuleName'] = 'AppFeedback';
		$p['PageSize'] = 10;
		
		$p['Parameter']['AppFeedbackContent'] = !empty($_REQUEST['AppFeedbackContent']) ? $_REQUEST['AppFeedbackContent'] : '';
		$p['Parameter']['MemberName'] = !empty($_REQUEST['MemberName']) ? $_REQUEST['MemberName'] : '';
		
		$this->opIndex($p);
	}
	
	function delFeedback(){		
		$p['ModuleName'] = 'AppFeedback';
		
		if( !empty($_REQUEST['AppFeedbackContent']) ){
			$p['Parameter']['AppFeedbackContent'] = $_REQUEST['AppFeedbackContent'];
		}
		if( !empty($_REQUEST['MemberName']) ){
			$p['Parameter']['MemberName'] = $_REQUEST['MemberName'];
		}				
		if( is_numeric($_REQUEST['p']) ){
			$p['Parameter']['p'] = $_REQUEST['p'];
		}
		$p['Url'] = __URL__."/feedback";
		
		$this->opDel( $p );
	}
	
	function makeAppSecret(){
		header("Content-Type:text/html; charset=utf-8");
		$secret = rand_string(32, 999);
		$this->ajaxReturn($secret, null , 1);
	}
	
	function makeAppID(){
		header("Content-Type:text/html; charset=utf-8");
		$id = rand_string(8, 1);
		$this->ajaxReturn($id, null , 1);
	}
	
	function config(){
		$m = D('Admin/Config');
		$data = $m->getConfig('other'); //配置数据不从缓存中提取
	
		//频道信息========================================================
		$m = D('Admin/Channel');
		$gid = intval(session('AdminGroupID'));
		$Channel = $m->getChannel(0,false,false, -1, -1, '&nbsp;&nbsp;&nbsp;&nbsp;├─ ', $gid);
		$this->assign('Channel', $Channel);
		//=============================================================
	
		$this->assign('AppLogo', $data['APP_LOGO'] );
		$this->assign('AppThemeColor', $data['APP_THEME_COLOR'] );
		$this->assign('AppSecretJiguang', $data['APP_KEY_JIGUANG'] );
		$this->assign('AppMasterSecretJiguang', $data['APP_MASTER_SECRET_JIGUANG'] );
		
		$this->assign('AppVersion', $data['APP_VERSION'] );
		$this->assign('AppVersionDescription', $data['APP_VERSION_DESCRIPTION'] );
		
		$this->assign('AppShareTitle', $data['APP_SHARE_TITLE'] );
		$this->assign('AppShareDescription', $data['APP_SHARE_DESCRIPTION'] );
		$this->assign('AppApkShareUrl', $data['APP_APK_SHARE_URL'] );
	
		$this->assign('AppApkSize', $data['APP_APK_SIZE'] );
		$this->assign('AppApkUrl', $data['APP_APK_URL'] );
		$this->assign('AppApkQrcode', $data['APP_APK_QRCODE'] );
		$this->assign('AppIpaUrl', $data['APP_IPA_URL'] );
		$this->assign('AppIpaQrcode', $data['APP_IPA_QRCODE'] );
	
		$this->assign('AppTab2ChannelID', $data['APP_TAB2_CHANNELID'] );
		$this->assign('AppTab2Icon', $data['APP_TAB2_ICON'] );
		$this->assign('AppTab2Title', $data['APP_TAB2_TITLE'] );
		$this->assign('AppTab2IconActive', $data['APP_TAB2_ICON_ACTIVE'] );
		
		$this->assign('AppTab3ChannelID', $data['APP_TAB3_CHANNELID'] );
		$this->assign('AppTab3Icon', $data['APP_TAB3_ICON'] );
		$this->assign('AppTab3Title', $data['APP_TAB3_TITLE'] );
		$this->assign('AppTab3IconActive', $data['APP_TAB3_ICON_ACTIVE'] );
	
		$this->assign('Action', __URL__.'/saveConfig' );
		$this->display();
	}
	
	//保存配置
	function saveConfig(){
        $data = GetConfigDataToSave('', 'APP_');
        $m = D("Admin/Config");
        if( $m->saveConfig($data, 'other') ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
	}
	
	/**
	 * 关于我们
	 */
	function about(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Config');
		$data = $m->getConfig('other'); //配置数据不从缓存中提取
		$this->assign('AppAbout', $data['APP_ABOUT'] );
		$this->assign('Action', __URL__.'/saveAbout' );
		$this->display();
	}
	
	/**
	 * 保存关于我们
	 */
	function saveAbout(){
        $fieldMap = array('APP_ABOUT'=>2);
        $data = GetConfigDataToSave($fieldMap);
        $m = D("Admin/Config");
        if( $m->saveConfig($data, 'other') ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
	}
	
	function message(){
		$p['HasPage'] = true;
		$p['ModuleName'] = 'AppMessage';
		$this->opIndex($p);
	}
	
	function delMessage(){
		$p['ModuleName'] = 'AppMessage';
		$p['Url'] = __URL__.'/message';
		if( is_numeric($_REQUEST['p']) ){
			$p['Parameter']['p'] = $_REQUEST['p'];
		}
		$this->opDel( $p );
	}
	
	function addMessage(){
		header("Content-Type:text/html; charset=utf-8");
		$this->assign('Action', __URL__.'/pushMessage' );
		$this->display();
	}
	
	/**
	 * 开始推送消息
	 */
	function pushMessage(){
		$type = intval($_POST['AppMessageType']);
		if($type==1){ //通知消息
			$title = $_POST['AppMessageTitle'];
			$content = $_POST['AppMessageContent'];
            $id = 0;
			//在客户端通过AppMessageID获取内容，直接传内容
			//$options['content'] = yd_relative_to_absolute( ParseTag($content) );
		}else{  //文章链接
			$id = intval( $_POST['InfoID'] );
			$mi = D('Admin/Info');
			$info = $mi->field('InfoTitle,InfoSContent,InfoContent')->find($id);
			if( empty($info) ) $this->ajaxReturn('', '文章不存在!' , 0);
			$title = strip_tags( $info['InfoTitle'] );
			$content = !empty($info['InfoSContent']) ? $info['InfoSContent'] : $info['InfoContent'];
			$options['InfoID'] = $id;
		}
		
		if(empty($title)){
			$this->ajaxReturn('AppMessageTitle', '消息标题不能为空!' , 0);
		}
		
		//在手机通知栏必须去除HTML，否则会直接显示HTML代码，实际内容通过附加参数传递
		$content = str_ireplace(array('"',"'",'&quot;'), '', strip_tags($content));
		$content = trim(Left($content, 60));
		if(empty($content)){
			$this->ajaxReturn('AppMessageContent', '消息内容不能为空!' , 0);
		}
		
		//保存推送历史记录===================================================
		$am = D('Admin/AppMessage');
		$data['AppMessageType'] = $type;
		$data['AppMessageTitle'] = $title;
		$data['AppMessageContent'] = ($type==1) ? $_POST['AppMessageContent'] : ''; //推送文章不保存内容
		if($type==2){
			$data['AppMessageParameter'] = $id;
		}
		$data['AppMessageTime'] = date('Y-m-d H:i:s');
		$lastID = $am->add($data);
		if($lastID > 0 ) $options['AppMessageID'] = $lastID;
		//==============================================================
		
		import("@.Common.YdJiguangPush");
		$j = new YdJiguangPush();
		$options['AppMessageType'] = $type;
		$options['platform'] = 'all';  //"android", "ios", "winphone"
		$result = $j->pushNotification($title, $content, $options);
		if($result){
			$LogDescription = 'ID:'.$lastID;
			WriteLog( $LogDescription );
			$this->ajaxReturn(null, '推送消息成功!' , 1);
		}else{
			$error = $j->getLastError();
			$this->ajaxReturn(null, "推送消息失败!\n\r{$error}" , 0);
		}
	}
	
	/**
	 * 秘钥管理
	 */
	function secret(){
		$Api = get_api_list();
		$this->assign('Api', $Api);
		$p['HasPage'] = false;
		$p['ModuleName'] = 'Secret';
		$this->opIndex($p);
	}
	
	function addSecret(){
		$Data = array('SecretTime'=>date('Y-m-d H:i:s'));
		$this->assign('Data', $Data);
		$Api = get_api_list();
		$this->assign('Api', $Api);
		$p['Action'] = __URL__.'/saveAddSecret';
		$this->opAdd( false, $p );
	}
	
	function saveAddSecret(){
		$this->_prePostSecret();
		$p['ModuleName'] = 'Secret';
		$this->opSaveAdd( $p );
	}
	
	function modifySecret(){
		$Api = get_api_list();
		$this->assign('Api', $Api);
		$p['ModuleName'] = 'Secret';
		$p['Action'] = __URL__.'/saveModifySecret';
		$this->opModify(false, $p);
	}
	
	function saveModifySecret(){
		$this->_prePostSecret();
		$p['ModuleName'] = 'Secret';
		$this->opSaveModify($p);
	}
	
	private function _prePostSecret(){
        if( strlen($_POST['AppID']) != 8 ){
            $this->ajaxReturn(null, 'AppID的长度必须为8个字符' , 0);
        }

        import("@.Common.YdSafe");
        $b = YdSafe::isSensitiveData($_POST['AppSecret']);
        if($b){
            unset($_POST['AppSecret']);
        }else{
            if( strlen($_POST['AppSecret']) != 32 ){
                $this->ajaxReturn(null, 'AppSecret秘钥必须为32个字符' , 0);
            }
        }

		//全部权限
		if('all'==$_POST['PurviewType']){
            $_POST['ApiList'] = 'all';
        }else{
            if( is_array($_POST['ApiList']) ){
                $_POST['ApiList'] = implode(',', $_POST['ApiList']);
            }
            if(empty($_POST['ApiList'])){
                $this->ajaxReturn(null, '请选择至少一个接口权限！' , 0);
            }
        }
	}
	
	function delSecret(){
		$p['ModuleName'] = 'Secret';
		$p['Url'] = __URL__.'/secret';
		$this->opDel( $p );
	}
	
	function active(){
		header("Content-Type:text/html; charset=utf-8");
		
		$StatType = !empty($_REQUEST['StatType']) ?  $_REQUEST['StatType']  : 1; //统计项目，默认为类型1
		$Year = !empty($_REQUEST['Year']) ?  $_REQUEST['Year']  :date('Y');
		$Month = !empty($_REQUEST['Month']) ?  $_REQUEST['Month']  :date('n');
        $XData = '';
        $YData = '';
		$m = D('Admin/AppActive');
		if($StatType == 1){ //按天统计
			$data = $m->statActiveByDay($Year, $Month);
			if(!empty($data)){
                $XData = '';
                $YData = '';
				foreach ($data as $k=>$v){
					$XData .= "{\"label\": \"{$Month}-{$k}\"},";
					$YData .= "{\"value\": \"{$v}\"},";
				}
				$XData = trim($XData, ',');
				$YData = trim($YData, ',');
			}else{
				$XData = '';
				$YData = '';
			}			
		}else if($StatType == 2){ //分年按月统计
			$data = $m->statActiveByYearMonth();
			if(!empty($data)){
				$YData = json_encode($data);
				$XData = '{"label": "1月"},{"label": "2月"},{"label": "3月"},{"label": "4月"},{"label": "5月"},{"label": "6月"},';
				$XData .= '{"label": "7月"},{"label": "8月"},{"label": "9月"},{"label": "10月"},{"label": "11月"},{"label": "12月"}';
			}else{
				$XData = $YData = false;
			}
		}
		
		$AllYear = array();
		for($y = 2017; $y <= date('Y'); $y++){
			if($y == date('Y')){
				for($z = 1; $z <= date('m'); $z++){					
					$AllYear[$y][] = $z;					
				}
			}else{
				$AllYear[$y] = array('1','2','3','4','5','6','7','8','9','10','11','12');
			}
		}
		$this->assign('AllYear',$AllYear);
		
		$this->assign('Year', $Year);
		$this->assign('Month', $Month);
		$this->assign("XData", $XData);
		$this->assign("YData", $YData);
		$this->assign('StatType', $StatType);
		
		$this->display();
	}
}