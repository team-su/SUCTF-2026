<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class WxAction extends AdminBaseAction {
	function index(){
		header('Location:https://mp.weixin.qq.com/');
	}
	
	function message(){
		header("Content-Type:text/html; charset=utf-8");
		$Keywords = !empty($_REQUEST['Keywords']) ?  YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		$MsgType = isset($_REQUEST['MsgType']) ? YdInput::checkKeyword( $_REQUEST['MsgType'] ) : '';
	
		import("ORG.Util.Page");
		$m = D('Admin/WxMessage');
		$TotalPage = $m->getCount($MsgType, $Keywords); //总页数
		$PageSize = $this->AdminPageSize;
		$Page = new Page($TotalPage, $PageSize);
	
		$Page->parameter = "&Keywords=$Keywords&MsgType=$MsgType";
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		$data = $m->getMessage($Page->firstRow, $Page->listRows, $MsgType,  $Keywords);
		$this->assign('NowPage', $Page->getNowPage());
		$this->assign('Page', $ShowPage); //分页条
		$this->assign('Keywords', $Keywords); //当前频道
		$this->assign('MsgType', $MsgType); //当前频道
		$this->assign('Message', $data);
		$this->display();
	}
	
	function delAllMessage(){
		$m = D('Admin/WxMessage');
		$m->delAllMessage();
		WriteLog();
		redirect(__URL__."/message/");
	}
	
	function exportMessage(){
		$csvName = date('Y-m-d_H_i').'.csv';  //导出文件名称
		$colName = array(
				'MsgContent'=>'消息内容',
				'MemberRealName'=>'姓名',
				'MemberMobile'=>'手机',
				'FromUserName'=>'发送方微信ID',
				'CreateTime'=>'时间',
		);
        $str = '';
		foreach ($colName as $k=>$v){
			$str.= "$v,";
		}
		$str = substr($str, 0, strlen($str)-1)."\n";		
		$m = D('Admin/WxMessage');
		$data= $m->getMessage(-1, -1, 'text');
		//注意导入和导出的过程中，因为我们使用的是统一UTF-8编码，
		//遇到中文字符一定要记得转码，否则可能会出现中文乱码的情况。
		foreach($data as $d){ //仅导出文本消息
			$str .= "{$d['p1']},{$d['MemberRealName']},{$d['MemberMobile']},{$d['FromUserName']},{$d['CreateTime']}\n";
		}
		WriteLog();
		$str = iconv('utf-8', 'gb2312//IGNORE', $str);
		yd_download_csv($csvName, $str); //下载csv
	}
	
	function batchDelMessage(){
		$id = $_POST['MessageID'];
		$NowPage = intval($_POST["NowPage"]);
	
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] )  : '';
		$MsgType = isset($_REQUEST['MsgType']) ? YdInput::checkKeyword( $_REQUEST['MsgType'] ) : '';
		$parameter = "?Keywords=$Keywords&MsgType=$MsgType&p=$NowPage";
	
		if( count($id) > 0 ){
			$m = D('Admin/WxMessage');
			$m->batchDelMessage($id);
			WriteLog("ID:".implode(',', $id));
		}
		redirect(__URL__."/Message/".$parameter);
	}
	
	/*
	 * 基本信息设置
	*/
	function basic(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Config');
		$data = $m->getConfig('wx'); //配置数据不从缓存中提取
		$this->assign('WxLogo', $data['WX_LOGO'] );
		$this->assign('WxName', $data['WX_NAME'] );
		$this->assign('WxOriginalID', $data['WX_ORIGINAL_ID'] );
		$this->assign('WxID', $data['WX_ID'] );
		$this->assign('WxType', $data['WX_TYPE'] );
		
		$this->assign('WxDescription', $data['WX_DESCRIPTION'] );
		$this->assign('WxQrcode', $data['WX_QRCODE'] );
		$this->assign('WxSaveMsg', $data['WX_SAVE_MSG'] );
		$this->assign('WxCustomerService', $data['WX_CUSTOMER_SERVICE'] );
		$this->assign('Action', __URL__.'/saveBasic' );
		unset($data);
		$this->display();
	}
	
	//微信功能配置
	function config(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Menu');
		$data = $m->getWxAppMenu();
		$this->assign('Menu', $data);
		$this->display();
	}
	
	//微信小程序设置
	function xcxConfig(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Config');
		$data = $m->getConfig('wx'); //配置数据不从缓存中提取
        $data['XCX_APP_SECRET'] = HideSensitiveData($data['XCX_APP_SECRET']);
        $data['XCX_ACCOUNT_KEY'] = HideSensitiveData($data['XCX_ACCOUNT_KEY']);

		//频道信息========================================================
		$m = D('Admin/Channel');
		$gid = intval(session('AdminGroupID'));
		$Channel = $m->getChannel(0,true,false, -1, -1, '&nbsp;&nbsp;&nbsp;&nbsp;├─ ', $gid);
		$this->assign('Channel', $Channel);
		//=============================================================
		$this->assign('XcxName', $data['XCX_NAME'] );
		$this->assign('XcxThemeColor', $data['XCX_THEME_COLOR'] );
		$this->assign('XcxQrcode', $data['XCX_QRCODE'] );
		
		$this->assign('XcxAppID', $data['XCX_APP_ID'] );
		$this->assign('XcxAppSecret', $data['XCX_APP_SECRET'] );
		
		$this->assign('XcxPayRate', $data['XCX_PAY_RATE'] );
		$this->assign('XcxAccountName', $data['XCX_ACCOUNT_NAME'] );
		$this->assign('XcxAccountKey', $data['XCX_ACCOUNT_KEY'] );

		//工具条基本设置
		$this->assign('XcxTabColor', $data['XCX_TAB_COLOR'] );
		$this->assign('XcxTabSelectedColor', $data['XCX_TAB_SELECTED_COLOR'] );
		$this->assign('XcxTabBackgroundColor', $data['XCX_TAB_BACKGROUND_COLOR'] );
		$this->assign('XcxTabBorderStyle', $data['XCX_TAB_BORDER_STYLE'] );
		
		//工具条选项设置
		$this->assign('XcxTab1ChannelID', $data['XCX_TAB1_CHANNELID'] );
		$this->assign('XcxTab1Title', $data['XCX_TAB1_TITLE'] );
		$this->assign('XcxTab1Icon', $data['XCX_TAB1_ICON'] );
		$this->assign('XcxTab1IconActive', $data['XCX_TAB1_ICON_ACTIVE'] );
		
		$this->assign('XcxTab2ChannelID', $data['XCX_TAB2_CHANNELID'] );
		$this->assign('XcxTab2Title', $data['XCX_TAB2_TITLE'] );
		$this->assign('XcxTab2Icon', $data['XCX_TAB2_ICON'] );
		$this->assign('XcxTab2IconActive', $data['XCX_TAB2_ICON_ACTIVE'] );
		
		$this->assign('XcxTab3ChannelID', $data['XCX_TAB3_CHANNELID'] );
		$this->assign('XcxTab3Title', $data['XCX_TAB3_TITLE'] );
		$this->assign('XcxTab3Icon', $data['XCX_TAB3_ICON'] );
		$this->assign('XcxTab3IconActive', $data['XCX_TAB3_ICON_ACTIVE'] );
		
		$this->assign('XcxTab4ChannelID', $data['XCX_TAB4_CHANNELID'] );
		$this->assign('XcxTab4Title', $data['XCX_TAB4_TITLE'] );
		$this->assign('XcxTab4Icon', $data['XCX_TAB4_ICON'] );
		$this->assign('XcxTab4IconActive', $data['XCX_TAB4_ICON_ACTIVE'] );
		
		//自定义参数设置
		$Attribute = array();
		$filename= "./Data/xcx.php";
		$HasXcxConfig = 0;
		if( file_exists($filename)){
			$HasXcxConfig = 1;
			$obj = include $filename;
			$lang = get_language_mark();
			$obj = isset($obj[$lang]) ? $obj[$lang] : false;
			if(!empty($obj)){
				$n = is_array($obj) ? count($obj) : 0;
				for($i=0; $i < $n; $i++){
					$DisplayValue = $obj[$i]['value'];
					$type = isset($obj[$i]['type']) ? $obj[$i]['type'] : '';
                    $width = isset($obj[$i]['width']) ? $obj[$i]['width'] : '';
                    $height = isset($obj[$i]['height']) ? $obj[$i]['height'] : '';
                    $class = isset($obj[$i]['class']) ? $obj[$i]['class'] : '';

					$DisplayType = $this->_getDefault('type', '', $type);
					$DisplayWidth = $this->_getDefault('width', $DisplayType, $width);
					$DisplayHeight = $this->_getDefault('height', $DisplayType, $height);
					$DisplayClass = $this->_getDefault('class', $DisplayType, $class);
				
					$Attribute[$i] = array(
							'FieldName'=>$obj[$i]['name'],
							'DisplayName'=>$obj[$i]['title'],
							'DisplayHelpText'=>$obj[$i]['help'].' 配置变量名称：'.$obj[$i]['name'].'',
							'DisplayType'=>$DisplayType,
							'DisplayWidth'=>$DisplayWidth,
							'DisplayHeight'=>$DisplayHeight,
							'DisplayClass'=>$DisplayClass,
					);
					$Attribute[$i]['AdminGroupID'] = 1; //模板配置允许获取所有频道
					if ( $DisplayType == 'specialselect' || $DisplayType=='specialselectno'){
						$Attribute[$i]['SelectedValue'] = explode(',' , $DisplayValue); //获取频道设置值
					}else if($DisplayType == 'channelexselect' || $DisplayType == 'channelexselectno'){ //支持多选
						$Attribute[$i]['SelectedValue'] = explode(',' ,$DisplayValue); //获取频道设置值
					}else if( $this->isSelected( $DisplayType ) ){
						$Attribute[$i]['SelectedValue'] = $DisplayValue;
						$parameter = isset($obj[$i]['parameter']) ? $obj[$i]['parameter'] : '';
						$Attribute[$i]['DisplayValue'] = str_replace('@@@', "\n", $parameter);
					}else{
						$Attribute[$i]['DisplayValue'] = $DisplayValue;
					}
				}
				import("@.Common.YdParseModel");
				$Attribute = parsemodel($Attribute);  //解析属性信息
			}
		}
		
		//获取小程序图标
		$Icons = array();
		foreach (glob("./Public/Images/xcx/*.png") as $filename) {
			$IconPath = $this->WebInstallDir.substr($filename, 2);
			$IconName = basename($filename,'.png');
			$Icons[] = array(
				'IconName'=>$IconName,
				'IconPath'=>$IconPath,		
			);
		}
		$IconPath = $this->WebInstallDir.'Public/Images/xcx/';
		$this->assign('IconPath', $IconPath);
		$this->assign('Icons', $Icons);
		
		$this->assign('HasXcxConfig', $HasXcxConfig);
		$this->assign('Attribute', $Attribute );
		$this->assign('Action', __URL__.'/saveXcxConfig' );
		$this->display();
	}
	
	//获取默认值
	private function _getDefault($key, $type, $value){
		if( !empty($value) ) {
			$data = $value;
		}else{
			$key = strtolower($key);
			switch ($key){
				case 'width': //宽度
					$dafault = array('text'=>'270px','textarea'=>'100%', 'image'=>'100%');
					$data = key_exists($type, $dafault) ? $dafault[$type] : false;
					break;
				case 'height': //高度
					$dafault = array('textarea'=>'100px');
					$data = key_exists($type, $dafault) ? $dafault[$type] : false;
					break;
				case 'class': //样式类
					$dafault = array('text'=>'textinput','textarea'=>'textinput', 'image'=>'textinput');
					$data = key_exists($type, $dafault) ? $dafault[$type] : false;
					break;
				case 'type': //类型
					$data = 'channelselect';
					break;
				default:
					$data = false;
			}
		}
		return $data;
	}
	
	//判断属性类型是否是可选
	function isSelected($type){
		if( stripos($type, 'checkbox') === false &&
				stripos($type, 'radio' ) === false &&
				stripos($type, 'select' )  === false  ){
			return false;
		}else{
			return true;
		}
	}
	
	//保存配置
	function saveXcxConfig(){
        $this->_saveXcx($_POST);
        $data = GetConfigDataToSave('', 'XCX_');
        $m = D("Admin/Config");
        if( $m->saveConfig($data, 'wx') ){
            unset($data['XCX_APP_SECRET'], $data['XCX_ACCOUNT_KEY']);
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
	}
	
	//保存小程序配置文件
	private function _saveXcx($data){
		$filename= "./Data/xcx.php";
		if( !file_exists($filename)) return false;
		$lang = get_language_mark();
		$config = include $filename;
		if(empty($config) || empty($config[$lang])) return false;
		$current = &$config[$lang];
		$n = is_array($current) ? count($current) : 0;
		
		//预处理，对checkbox转化为字符串
		foreach ($data as $k=>$v){
			for($i=0; $i<$n;$i++){
				if( $current[$i]['name'] == $k){ //健值是配置变量
				    unset($_POST[$k]); //避免保存到数据库里去
					$current[$i]['value'] = is_array($v) ? implode(',', $v) : $v;
				}
			}
		}
		cache_array($config, $filename, false);
	}
	
	function enableMenu(){
		$MenuID = $_REQUEST['MenuID'];
		$IsEnable = $_REQUEST['IsEnable'];
		$m = D('Admin/Menu');
		$m->enable($MenuID, $IsEnable);
		redirect(__URL__."/config");
	}
	
	function batchSortMenu(){
		$MenuOrder = $_POST['MenuOrder']; //排序
		$MenuID = $_POST['MenuOrderID']; //排序
		if( is_array($MenuOrder) && is_array($MenuID) && count($MenuOrder) > 0 && count($MenuID) > 0 ){
			D('Admin/Menu')->batchSortMenu($MenuID, $MenuOrder);
			WriteLog();
		}
		redirect(__URL__."/config");
	}
	
	/**
	 * 保存基本设置信息
	 */
	function saveBasic(){
        $data = GetConfigDataToSave('', 'WX_');
        $m = D("Admin/Config");
        if( $m->saveConfig($data, 'wx') ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存配置成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存配置失败!' , 0);
        }
	}

	/*
	 * 消息接口配置
	 */
	function messageAPI(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Config');
		$data = $m->getConfig('wx'); //配置数据不从缓存中提取
        $data['WX_APP_SECRET'] = HideSensitiveData($data['WX_APP_SECRET']);
		$this->assign('WxToken', $data['WX_TOKEN'] );
		$this->assign('WxAppid', $data['WX_APP_ID'] );
		$this->assign('WxAppSecret', $data['WX_APP_SECRET'] );
		$this->assign('Action', __URL__.'/saveMessageAPI' );
		unset($data);
		$this->display();
	}

    /*
     * 保存并验证消息接口配置
    */
    function saveMessageAPI(){
        //保存前去掉里面的空格，如果有空格会导致微信无法对接或appsecret变为无效
        foreach ($_POST as $k=>$v){
            $_POST[$k] = trim($v);
        }
        $fieldMap = array('WX_TOKEN'=>2,     'WX_APP_ID'=>2, 'WX_APP_SECRET'=>2);
        $data = GetConfigDataToSave($fieldMap);
        $m = D("Admin/Config");
        if( $m->saveConfig($data, 'wx') ){
            unset($data['WX_APP_SECRET']);
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }
	
	/*
	 * 凭证接口配置
	*/
	function credentialAPI(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Config');
		$data = $m->getConfig('wx'); //配置数据不从缓存中提取
		$this->assign('WxToken', $data['WX_TOKEN'] );
		$this->assign('WxAppid', $data['WX_APP_ID'] );
		$this->assign('WxAppSecret', $data['WX_APP_SECRET'] );
		$this->assign('Action', __URL__.'/saveCredentialAPI' );
		unset($data);
		$this->display();
	}
	
	function saveCredentialAPI(){
        $fieldMap = array('WX_TOKEN'=>2,     'WX_APP_ID'=>2, 'WX_APP_SECRET'=>2);
        $data = GetConfigDataToSave($fieldMap);
        $m = D("Admin/Config");
        if( $m->saveConfig($data, 'wx') ){
            import("@.Common.YdWx");
            $w = new YdWx();
            $b = $w->hasCredential();
            if($b){
                $description = var_export($data, true);
                WriteLog($description);
                $this->ajaxReturn(null, '保存配置成功!' , 1);
            }else{
                $this->ajaxReturn(null, "获取凭证失败！{$w->ErrorMessage}" , 0);
            }
        }else{
            $this->ajaxReturn(null, '保存配置失败!' , 0);
        }
	}
	


	//===================自定义菜单 start=====================================
	/*
	 * 同步微信菜单
	*/
	function updateMenu(){
		import("@.Common.YdWx");
		$wx = new YdWx();
		$b = $wx->hasCredential();
		if($b === false){
			$this->ajaxReturn(null, "获取凭证失败！{$wx->ErrorMessage}" , 0);
		}
		$m = D('Admin/WxMenu');
		$menu = $m->getWxMenu();
		$b = $wx->createMenu($menu);
		if($b===true){
			WriteLog();
			$this->ajaxReturn(null, '创建自定义菜单成功！' , 1);
		}else{
			$this->ajaxReturn(null, '创建自定义菜单失败！'.$b , 0);
		}
	}
	
	function clearMenu(){
		import("@.Common.YdWx");
		$wx = new YdWx();
		$b = $wx->hasCredential();
		if($b === false){
            $this->ajaxReturn(null, "获取凭证失败！{$wx->ErrorMessage}" , 0);
		}
		$b=$wx->clearMenu();
		if($b===true){
			WriteLog();
			$this->ajaxReturn(null, '清除微信菜单成功！' , 1);
		}else{
			$this->ajaxReturn(null, '清除微信菜单失败！'.$b , 0);
		}
	}
	
	/**
	 * 上传素材到微信服务器
	 */
	function uploadMaterial(){
		header("Content-Type:text/html; charset=utf-8");
		import("@.Common.YdWx");
		$wx = new YdWx();
		$b = $wx->hasCredential();
		if($b === false){
            $this->ajaxReturn(null, "获取凭证失败！{$wx->ErrorMessage}" , 0);
		}
		$filename = $_POST['filename'];
		if(empty($filename)){
			$this->ajaxReturn(null, '请选择文件！' , 0);
		}
		$result = $wx->addMaterial($filename);
		if($result){
			$this->ajaxReturn($result, '' , 1);
		}else{
			$this->ajaxReturn(null, $wx->ErrorMessage, 0);
		}
	}
	
	//微信自定义菜单
	function menu(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/WxMenu');
		$data = $m->getMenu();
		
		$EnableOpenSSL = function_exists('openssl_open') ? 1 : 0;  //OpenSSL支持
		$this->assign('EnableOpenSSL', $EnableOpenSSL);
		$this->assign('Menu', $data);
		$this->display();
	}
	
	function addMenu(){
		header("Content-Type:text/html; charset=utf-8");
		//获取一级菜单
		$m1 = D('Admin/WxMenu');
		$topMenu = $m1->getSubMenu(0);
	
		//获取菜单类型
		$m2 = D('Admin/WxType');
		$Type = $m2->getType(-1,1);
	
		//获取频道信息
		$m3 = D('Admin/Channel');
		$gid = intval(session('AdminGroupID'));
		$Channel = $m3->getChannel(0,true,true, -1, -1, '&nbsp;&nbsp;&nbsp;&nbsp;├─ ', $gid); //不显示链接频道
	
		$this->assign('Channel', $Channel);
		$this->assign('Type', $Type);
		$this->assign('TopMenu', $topMenu);
		$this->assign('Action', __URL__.'/saveAddMenu');
		$this->display();
	}
	

	
	//保存自定义菜单
	function saveAddMenu(){
		header("Content-Type:text/html; charset=utf-8");
		$this->parseTypePost();
		$m = D('Admin/WxMenu');
		
		if( $_POST['Parent'] != 0){ //仅判断修改子菜单
			$subMenuCount = $m->getSubMenuCount( $_POST['Parent'] );
			if( $subMenuCount > 4 ){
				$this->ajaxReturn(null, '所属一级菜单的子菜单数不能超过5个' , 0);
			}
		}
		
		if( $m->create() ){
			if($m->add()){
				WriteLog("ID:".$m->getLastInsID() );
				$this->ajaxReturn(null, '添加自定义菜单成功!' , 1);
			}else{
				$this->ajaxReturn(null, '添加自定义菜单失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	//修改自定义菜单
	function modifyMenu(){
		header("Content-Type:text/html; charset=utf-8");
		$MenuID = intval($_GET['MenuID']);
		
		//当前菜单
		$m1 = D('Admin/WxMenu');
		$data = $m1->findMenu($MenuID);
		$data['HasChild'] = $m1->hasChild($MenuID)== true ? 1 : 0; //是否有子菜单
	
		//获取一级菜单
		$topMenu = $m1->getSubMenu(0);
	
		//获取菜单类型
		$m2 = D('Admin/WxType');
		$Type = $m2->getType(-1,1);
	
		//获取频道信息
		$m3 = D('Admin/Channel');
		$gid = intval(session('AdminGroupID'));
		$Channel = $m3->getChannel(0,true,true, -1, -1, '&nbsp;&nbsp;&nbsp;&nbsp;├─ ', $gid); //不显示链接频道
	
		$this->assign('Channel', $Channel);
		$this->assign('Type', $Type);
		$this->assign('TopMenu', $topMenu);
		$this->assign('Reply', $data);  //命名为Reply，为了兼容tool.html文件
		$this->assign('MenuID', $MenuID);
		$this->assign('Parent', $data['Parent']);
		$this->assign('Action', __URL__.'/saveModifyMenu');
		$this->display();
	}
	
	//保存自定义菜单修改
	function saveModifyMenu(){
		header("Content-Type:text/html; charset=utf-8");
		$this->parseTypePost();
		$c = D('Admin/WxMenu');
		if( $_POST['Parent'] != 0){ //仅判断修改子菜单
			$subMenuCount = $c->getSubMenuCount( $_POST['Parent'] );
			$max = ( $_POST['Parent'] == $_POST['ParentOld'] ) ? 5 : 4;
			if( $subMenuCount > $max ){
				$this->ajaxReturn(null, '所属一级菜单的子菜单数不能超过5个' , 0);
			}
		}
	
		if( $c->create() ){
			if($c->save() === false){
				$this->ajaxReturn(null, '修改自定义菜单失败!' , 0);
			}else{
				WriteLog("ID:".$_POST['MenuID'] );
				$this->ajaxReturn(null, '修改自定义菜单成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $c->getError() , 0);
		}
	}
	
	//删除自定义菜单
	function delMenu(){
		header("Content-Type:text/html; charset=utf-8");
		$MenuID = $_GET["MenuID"];
		if( is_numeric($MenuID) ){
			$m = D('Admin/WxMenu');
			$m->delete( $MenuID );
			WriteLog("ID:$MenuID");
		}
		redirect(__URL__."/menu/");
	}
	
	function saveAllMenu(){
		$data = array(
				"MenuID" => $_POST['MenuID'],
				"MenuName" => $_POST['MenuName'],
				"MenuOrder" => $_POST['MenuOrder'],
		);
	
		if( is_array($data['MenuID']) && count($data['MenuID']) > 0 ){
			$m = D('Admin/WxMenu');
			$m->saveAllMenu( $data );
			WriteLog();
		}
		redirect(__URL__."/menu");
	}
	//===================自定义菜单 end======================================

	
	
	//===================自动回复 start======================================
	//默认自动回复
	function defaultReply(){
		header("Content-Type:text/html; charset=utf-8");
		$m1 = D('Admin/WxReply');
		$data = $m1->findDefaultReply();
	
		//获取菜单类型
		$m2 = D('Admin/WxType');
		$Type = $m2->getType(1,1);
	
		//获取频道信息
		$m3 = D('Admin/Channel');
		$gid = intval(session('AdminGroupID'));
		$Channel = $m3->getChannel(0,true,true, -1, -1, '&nbsp;&nbsp;&nbsp;&nbsp;├─ ', $gid);
		
		$this->assign('Reply', $data);
		$this->assign('Channel', $Channel);
		$this->assign('Type', $Type);
		$this->assign('Action', __URL__.'/saveDefaultReply');
		$this->display();
	}
	
	function saveDefaultReply(){
		header("Content-Type:text/html; charset=utf-8");
		$this->parseTypePost();
		$m = D('Admin/WxReply');
		if( $m->create() ){
			if($m->save() === false){
				$this->ajaxReturn(null, '保存失败!' , 0);
			}else{
				WriteLog();
				$this->ajaxReturn(null, '保存成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function subscribeReply(){
		header("Content-Type:text/html; charset=utf-8");
		$m1 = D('Admin/WxReply');
		$data = $m1->findSubscribeReply();
	
		//获取菜单类型
		$m2 = D('Admin/WxType');
		$Type = $m2->getType(1,1);
	
		//获取频道信息
		$m3 = D('Admin/Channel');
		$gid = intval(session('AdminGroupID'));
		$Channel = $m3->getChannel(0,true,true, -1, -1, '&nbsp;&nbsp;&nbsp;&nbsp;├─ ', $gid);
	
		$this->assign('Reply', $data);
		$this->assign('Channel', $Channel);
		$this->assign('Type', $Type);
		$this->assign('Action', __URL__.'/saveSubscribeReply');
		$this->display();
	}
	
	/**
	 * 保存默认回复消息
	 */
	function saveSubscribeReply(){
		header("Content-Type:text/html; charset=utf-8");
		$this->parseTypePost();
		$m = D('Admin/WxReply');
		if( $m->create() ){
			if($m->save() === false){
				WriteLog();
				$this->ajaxReturn(null, '保存失败!' , 0);
			}else{
				$this->ajaxReturn(null, '保存成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
		
	//地理位置自动回复
	function lbsReply(){
		header("Content-Type:text/html; charset=utf-8");
		$m1 = D('Admin/WxReply');
		$data = $m1->findLbsReply();
		
		$all = include CONF_PATH.'wxapp.php';  //加载微信配置文件
        $AppList = array();
		foreach ($all as $k=>$v){
			if($v['type'] == 4){
				$AppList[] = array("AppID"=>$k, 'AppName'=>$v['name'], 'Keyword'=>$k, 'Description'=>$v['description']);
			}
		}
		$this->assign('AppList', $AppList);
		$this->assign('Reply', $data);
		$this->assign('Action', __URL__.'/saveLbsReply');
		$this->display();
	}
	
	//保存地理位置回复
	function saveLbsReply(){
		header("Content-Type:text/html; charset=utf-8");
		$this->parseTypePost();
		$m = D('Admin/WxReply');
		if( $m->create() ){
			if($m->save() === false){
				WriteLog();
				$this->ajaxReturn(null, '保存失败!' , 0);
			}else{
				$this->ajaxReturn(null, '保存成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
		
	//关键词自动回复主页
	function keywordReply(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/WxReply');
		import("ORG.Util.Page");
		$TotalPage = $m->getKeywordReplyCount();
		$PageSize = $this->AdminPageSize;
		
		$Page = new Page($TotalPage, $PageSize);
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		$data = $m->getKeywordReply($Page->firstRow, $Page->listRows);
		
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('KeywordReply',  $data );
		$this->assign('Page', $ShowPage); //分页条
		$this->display();
	}
	
	function delKeywordReply(){
		header("Content-Type:text/html; charset=utf-8");
		$ReplyID = $_GET["ReplyID"];
		$p = $_GET["p"];
		$m = D('Admin/WxReply');
		if( is_numeric($ReplyID) && is_numeric($p)  ){
			$m->delete( $ReplyID );
			WriteLog("ID:$ReplyID");
		}
		redirect(__URL__."/keywordReply/p/$p");
	}
	
	function batchDelKeywordReply(){
		$id = $_POST['ReplyID'];
		$NowPage = intval($_POST["NowPage"]);
		$m = D('Admin/WxReply');
		if( count($id) > 0 ){
			$m->batchDelKeywordReply( $id );
			WriteLog("ID:".implode(',', $id));
		}
		redirect(__URL__."/keywordReply/p/$NowPage");
	}
	
	function batchSortKeywordReply(){
		$Priority = $_POST['Priority']; //排序
		$ReplyID = $_POST['PriorityID']; //排序
		$NowPage = intval($_POST["NowPage"]);
		if( is_array($ReplyID) && is_array($Priority) && count($ReplyID) > 0 && count($Priority) > 0 ){
			D('Admin/WxReply')->batchSortKeywordReply($ReplyID, $Priority);
			WriteLog();
		}
		redirect(__URL__."/keywordReply?p=$NowPage");
	}
	
	function modifyKeywordReply(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$ReplyID = $_GET['ReplyID'];
		if( !is_numeric($ReplyID)){
			alert("非法参数", __URL__.'/keywordReply');
		}
		//====================================
		$m1 = D('Admin/WxReply');
		$Reply = $m1->findKeywordReply($ReplyID);
		
		$m2 = D('Admin/WxType');
		$Type = $m2->getType(1,1);//获取类型列表
		
		$m3 = D('Admin/Channel');
		$gid = intval(session('AdminGroupID'));
		$Channel = $m3->getChannel(0, true, true, -1, -1, '&nbsp;&nbsp;&nbsp;&nbsp;├─ ', $gid); //不显示链接频道
		unset($m1, $m2, $m3);
		
		$this->assign('Reply', $Reply);
		$this->assign('Type', $Type);
		$this->assign('Channel', $Channel);
		
		$this->assign('HiddenName', 'ReplyID');
		$this->assign('HiddenValue', $Reply['ReplyID']);
		$this->assign('Action', __URL__.'/saveModifyKeywordReply');
		$this->display();
	}
	
	function saveModifyKeywordReply(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/WxReply');
		$this->parseTypePost();
		if( $m->create() ){
			if($m->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				WriteLog("ID:".$_POST['ReplyID'] );
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function addKeywordReply(){
		header("Content-Type:text/html; charset=utf-8");
		//获取菜单类型
		$m1 = D('Admin/WxType');
		$Type = $m1->getType(1,1);
	
		//获取频道信息
		$m2 = D('Admin/Channel');
		$gid = intval(session('AdminGroupID'));
		$Channel = $m2->getChannel(0,true,true, -1, -1, '&nbsp;&nbsp;&nbsp;&nbsp;├─ ', $gid); //不显示链接频道
	
		$this->assign('Channel', $Channel);
		$this->assign('Type', $Type);
		$this->assign('Action', __URL__.'/saveAddKeywordReply');
		$this->display();
	}
	
	//对关键词清0
	function zeroKeywordCount(){
		header("Content-Type:text/html; charset=utf-8");
		$replyID = $_GET['ReplyID'];
		$m = D('Admin/WxReply');
		if($m->zeroKeywordCount($replyID)===false){
			$this->ajaxReturn(null, '清零失败!' , 0);
		}else{
			WriteLog();
			$this->ajaxReturn($replyID, '清零成功!' , 1);
		}
	}
	
	function saveAddKeywordReply(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/WxReply');
		$this->parseTypePost();
		if( $m->create() ){
			if($m->add()){
				WriteLog("ID:".$m->getLastInsID() );
				$this->ajaxReturn(null, '添加成功!' , 1);
			}else{
				$this->ajaxReturn(null, '添加失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	//===================自动回复 end======================================
	
	//解析消息类型参数
	private function parseTypePost(){
		$type = $_POST['TypeID'];
		switch($type){
			case "1": //微信文本消息
				$_POST['p1'] = $_POST['a1'];
				break;
			case "2": //微信图文消息
				$_POST['p1'] = $_POST['a2'];
				if( !is_numeric($_POST['a3']) ){
					$_POST['a3'] = 10;
				}
				$_POST['p2'] = $_POST['a3'];
				break;
			case "3": //微信音乐消息
				$_POST['p1'] = $_POST['a4'];
				$_POST['p2'] = $_POST['a5'];
				$_POST['p3'] = $_POST['a6'];
				break;
			case "4": //打开频道主页
				$_POST['p1'] = $_POST['a7'];
				break;
			case "5": //第三方应用
				$t = explode(',', $_POST['a8']);
				$_POST['p1'] = addslashes($t[0]);  //存放appid
				$_POST['p2'] = addslashes($t[1]);  //存放keyword
				
				if( substr($t[0], 0, 1) == '/' ){
					$reg = get_magic_quotes_gpc() ? stripslashes($t[0]) : $t[0];
					$_POST['p4'] = $_POST['a10'];  //存放应用参数
					if(  preg_match($reg, $_POST['p4'])===0){
						$this->ajaxReturn(null, '应用指令格式错误！' , 0);
					}
				}else{
					$_POST['p4'] = ''; //表示无参指令
				}
				break;
			case "6": //外部链接
				$prefix = strtolower( substr($_POST['a9'], 0, 8) );
				//包含:就认为是以协议开头
				if( stripos($prefix, ':') === false){
					$this->ajaxReturn(null, '外部链接必须以http、ftp等协议名开头！' , 0);
				}
				$_POST['p1'] = $_POST['a9'];
				break;
			case "7": //直接返回素材（图片、视频、语音）
				$_POST['p1'] = $_POST['a11']; //media_id
				$_POST['p2'] = $_POST['a12']; //url
				$_POST['p3'] = $_POST['a13']; //本地图片地址
				$_POST['p4'] = $_POST['a14']; //type
				break;
			case "8": //打开小程序
				$_POST['p1'] = $_POST['a15']; //appid
				$_POST['p2'] = $_POST['a16']; //小程序路径
				break;
		}
		unset( $_POST['a1'], $_POST['a2'], $_POST['a3'], $_POST['a4'], $_POST['a5'], $_POST['a6'], $_POST['a7'], $_POST['a8'], $_POST['a9'] );
	}
	
	
	//===================微信应用 开始=====================
	//抽奖应用（大转盘、刮刮卡、砸金蛋）奖
	function lottery(){
		header("Content-Type:text/html; charset=utf-8");
		//$AppTypeID = isset($_REQUEST['AppTypeID']) ? $_REQUEST['AppTypeID'] : -1;
		$AppTypeID = 1; //仅显示抽奖应用
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
	
		import("ORG.Util.Page");
		$m = D('Admin/WxApp');
		$TotalPage = $m->getCount($AppTypeID, $Keywords); //总页数
		$PageSize = $this->AdminPageSize;
		$Page = new Page($TotalPage, $PageSize);
		
		$Page->parameter = "&AppTypeID=$AppTypeID&Keywords=$Keywords";
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
	
		$m1 = D('Admin/WxAward');
		$data = $m->getApp($Page->firstRow, $Page->listRows, $AppTypeID, $Keywords);
		$count = is_array($data) ? count($data) : 0;
		for($i = 0; $i < $count; $i++){
			$p = explode('@@@', $data[$i]['AppParameter']);
			$data[$i]['LotteryType'] = $p[0];
			$data[$i]['LotteryStartTime'] = $p[3];
			$data[$i]['LotteryEndTime'] = $p[4];
			
			$data[$i]['WinNumber'] = $m1->getWinPeopleNumber( $data[$i]['AppID'] );
			$data[$i]['ViewNumber'] = $m1->getPeopleNumber( $data[$i]['AppID'], 0);
			$data[$i]['JoinNumber'] = $m1->getPeopleNumber( $data[$i]['AppID'], 1);
		}
		//$m1 = D('Admin/WxApptype');
		//$AppType = $m1->getAppType(1);
		//$this->assign('AppType', $AppType);
	
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Page', $ShowPage); //分页条

		$this->assign('AppTypeID', $AppTypeID); 
		$this->assign('Keywords', $Keywords);
		$this->assign('App', $data);
		
		$this->display();
	}
	
	//中奖用户管理
	function award(){
		header("Content-Type:text/html; charset=utf-8");
		$AppID = isset($_REQUEST['AppID']) ? intval($_REQUEST['AppID']) : -1;
		$Mobile = empty($_REQUEST['Mobile']) ? '' : $_REQUEST['Mobile'];
		
		import("ORG.Util.Page");
		$m = D('Admin/WxAward');
		$TotalPage = $m->getAwardCount($AppID,$Mobile); //总页数
		
		$PageSize = $this->AdminPageSize;
		$Page = new Page($TotalPage, $PageSize);
		$Page->parameter = "&AppID=$AppID";
		if( $Mobile != '' ){
			$Page->parameter .= "&Mobile=$Mobile";
		}
		
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		
		//活动应用列表
		$m1 = D("Admin/WxApp");
		$App = $m1->getApp(-1,-1, 1, '', 1);
	
		$data = $m->getAward($Page->firstRow, $Page->listRows, $AppID, $Mobile);
		$count = is_array($data) ? count($data) : 0;
		$list = array(1=>"一等奖", 2=>"二等奖", 3=>"三等奖");
		for($i = 0; $i < $count; $i++){
			$p = $m1->findLottery( $data[$i]['AppID'] );
			$key = 'LotteryAward'.$data[$i]['AwardNumber'];
			$data[$i]['AwardName'] = $p[ $key ];
			$data[$i]['AwardNumberText'] = $list[ $data[$i]['AwardNumber'] ];
		}
		
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Page', $ShowPage); //分页条

		$this->assign('AppID', $AppID);
		$this->assign('Mobile', $Mobile);
		$this->assign('App', $App);
		$this->assign('Award', $data);
		$this->display();
	}
	
	//确认领奖
	function checkAward(){
		$AwardID = $_GET['AwardID'];
		$AppID = isset($_REQUEST['AppID']) ? intval($_REQUEST['AppID']) : -1;
		$Mobile = empty($_REQUEST['Mobile']) ? '' : $_REQUEST['Mobile'];
		$p = $_GET["p"];
		$parameter = "?AppID={$AppID}&Mobile=$Mobile&p=$p";
		
		$m = D('Admin/WxAward');
		$m->checkAward($AwardID);
		redirect(__URL__."/award".$parameter);
	}
	
	function delLottery(){
		header("Content-Type:text/html; charset=utf-8");
		$AppID = intval($_REQUEST["AppID"]);
		$Keywords = YdInput::checkKeyword( $_REQUEST['Keywords'] );
		$p = $_GET["p"];
		$parameter = "?Keywords=$Keywords&p=$p";
		$m = D('Admin/WxApp');
		if( $m->delete($AppID ) ){ //删除应用的同时，删除相关中奖数据
			$m1 = D('Admin/WxAward');
			$m1->deleteAward($AppID);
			WriteLog("ID:$AppID");
		}
		redirect(__URL__."/lottery/".$parameter);
	}
	
	//清除用户抽奖数据
	function clearLottery(){
		header("Content-Type:text/html; charset=utf-8");
		$appid = $_GET['appid'];
		$m = D('Admin/WxAward');
		if($m->deleteAward($appid)===false){
			$this->ajaxReturn(null, '清空失败!' , 0);
		}else{
			WriteLog("ID:$appid");
			$this->ajaxReturn($appid, '清空成功!' , 1);
		}
	}
	
	//批量删除
	function batchDelLottery(){
		$AppID = intval($_POST['AppID']);
		$Keywords = YdInput::checkKeyword( $_REQUEST['Keywords'] );
		$NowPage = intval($_REQUEST["NowPage"]);
		$parameter = "?Keywords=$Keywords&p=$NowPage";
	
		if( count($AppID) > 0 ){
			$m = D('Admin/WxApp');
			$m->batchDelApp( $AppID );
			WriteLog("ID:".implode(',', $AppID));
		}
		redirect(__URL__."/lottery".$parameter);
	}
	
	//添加抽奖活动
	function addLottery(){
		header("Content-Type:text/html; charset=utf-8");
		$StartTime = date('Y-m-d H:i:s'); //活动开始时间
		$EndTime = date('Y-m-d H:i:s', time()+24*60*60);  //活动结束时间＝活动开始时间+1天时间
		
		$this->assign('StartTime', $StartTime);
		$this->assign('EndTime',  $EndTime);
		$this->assign('Action', __URL__.'/saveLotteryAdd');
		$this->display();
	}
	
	function modifyLottery(){
		header("Content-Type:text/html; charset=utf-8");
		$AppID = $_GET['AppID'];
		$m = D('Admin/WxApp');
		$data = $m->FindLottery($AppID);
		
		$this->assign('Action', __URL__.'/saveModifyLottery');
		$this->assign('HiddenName', 'AppID');
		$this->assign('HiddenValue', $AppID);
		$this->assign('AppID',  $AppID);
		$this->assign('l',  $data);
		$this->display();
	}
	
	function saveModifyLottery(){
		header("Content-Type:text/html; charset=utf-8");
		$data = $this->checkLotteryParameter();
		$m = D('Admin/WxApp');
		if( $m->save( $data ) === false ){
			$this->ajaxReturn(null, '修改失败!' , 0);
		}else{
			WriteLog("ID:".$_POST['AppID'] );
			$this->ajaxReturn(null, '修改成功!' , 1);
		}
	}
	
	function saveLotteryAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$data = $this->checkLotteryParameter();
		$m = D('Admin/WxApp');
		if( $m->add( $data ) === false ){
			WriteLog("ID:".$m->getLastInsID() );
			$this->ajaxReturn(null, '添加成功!' , 0);
		}else{
			$this->ajaxReturn(null, '添加成功!' , 1);
		}
	}
	
	function checkLotteryParameter(){
		$LotteryName = trim($_POST['LotteryName']);
		if( $LotteryName == ''){
			$this->ajaxReturn(null, '活动名称不能为空!' , 0);
		}
		if( strpos($LotteryName, '@@@') !== false ){
			$this->ajaxReturn(null, '活动名称包含非法字符!' , 0);
		}
		
		$LotteryStartTime = strtotime(trim($_POST['LotteryStartTime']));
		if( empty($LotteryStartTime) ){
			$this->ajaxReturn(null, '开始日期无效!' , 0);
		}
		
		$LotteryEndTime = strtotime(trim($_POST['LotteryEndTime']));
		if( empty($LotteryEndTime) ){
			$this->ajaxReturn(null, '结束日期无效!' , 0);
		}
		
		if( $LotteryStartTime > $LotteryEndTime){
			$this->ajaxReturn(null, '结束日期必须大于开始日期!' , 0);
		}
		
		$LotteryAward1 = trim($_POST['LotteryAward1']);
		$LotteryAward2 = trim($_POST['LotteryAward2']);
		$LotteryAward3 = trim($_POST['LotteryAward3']);
		if( strpos($LotteryAward1, '@@@') !== false || strpos($LotteryAward2, '@@@') !== false 
				|| strpos($LotteryAward3, '@@@') !== false ){
			$this->ajaxReturn(null, '奖品名称包含非法字符!' , 0);
		}
		
		$LotteryAward1Num = trim($_POST['LotteryAward1Num']);
		$LotteryAward2Num = trim($_POST['LotteryAward2Num']);
		$LotteryAward3Num = trim($_POST['LotteryAward3Num']);
		if( !is_numeric($LotteryAward1Num) || !is_numeric($LotteryAward2Num) || !is_numeric($LotteryAward3Num) ){
			$this->ajaxReturn(null, '奖品数量必须为整数!' , 0);
		}
		 
		if( $LotteryAward1Num < 1 || $LotteryAward2Num < 1 || $LotteryAward3Num < 1 ){
			$this->ajaxReturn(null, '奖品数量必须大于1!' , 0);
		}
		
		$LotteryAward1Probability = trim($_POST['LotteryAward1Probability']);
		$LotteryAward2Probability = trim($_POST['LotteryAward2Probability']);
		$LotteryAward3Probability = trim($_POST['LotteryAward3Probability']);
		if( !is_numeric($LotteryAward1Probability) || !is_numeric($LotteryAward2Probability) || !is_numeric($LotteryAward3Probability) ){
			$this->ajaxReturn(null, '中奖概率必须为整数!' , 0);
		}
		if( $LotteryAward1Probability < 0 || $LotteryAward1Probability >10000 ){
			$this->ajaxReturn(null, '中奖概率必须介于0-10000之间' , 0);
		}
		
		$LotteryMax = trim($_POST['LotteryMax']);
		if( !is_numeric($LotteryMax) || $LotteryMax < 1 ){
			$this->ajaxReturn(null, '每人最多抽奖总次数无效' , 0);
		}
		
		$LotteryDayMax = trim($_POST['LotteryDayMax']);
		if( !is_numeric($LotteryDayMax) || $LotteryDayMax < 1 ){
			$this->ajaxReturn(null, '每天最多抽奖次数无效' , 0);
		}
		if( $LotteryDayMax > $LotteryMax ){
			$this->ajaxReturn(null, '每天最多抽奖次数不能超过抽奖总次数' , 0);
		}
		
		$LotteryPassword = trim($_POST['LotteryPassword']);
		if( strpos($LotteryPassword, '@@@') !== false ){
			$this->ajaxReturn(null, '商家兑奖密码包含非法字符!' , 0);
		}

        $data = array();
		//用于修改
		if(!empty($_POST['AppID'])){
			$data['AppID'] = $_POST['AppID'];
		}
		$data['AppName'] = $_POST['LotteryName'];
		$data['AppKeyword'] = $_POST['AppKeyword'];
		$data['AppTypeID'] = 1;
		$data['AppDescription'] = $_POST['AppDescription'];
		$data['IsEnable'] = $_POST['IsEnable'];
		
		$data['AppParameter']="{$_POST['LotteryType']}@@@{$_POST['LotteryIntroduction']}@@@{$_POST['LotteryDescription']}@@@{$_POST['LotteryStartTime']}";
		$data['AppParameter'].="@@@{$_POST['LotteryEndTime']}@@@{$_POST['LotteryRepeatTip']}@@@{$_POST['LotteryStartPicture']}@@@{$_POST['LotteryEndPicture']}";
		$data['AppParameter'].="@@@{$_POST['LotteryEndTitle']}@@@{$_POST['LotteryEndDescription']}";

		$data['AppParameter'].="@@@{$_POST['LotteryAward1']}@@@{$_POST['LotteryAward1Num']}@@@{$_POST['LotteryAward1Probability']}";
		$data['AppParameter'].="@@@{$_POST['LotteryAward2']}@@@{$_POST['LotteryAward2Num']}@@@{$_POST['LotteryAward2Probability']}";
		$data['AppParameter'].="@@@{$_POST['LotteryAward3']}@@@{$_POST['LotteryAward3Num']}@@@{$_POST['LotteryAward3Probability']}";
		
		$data['AppParameter'].="@@@{$_POST['LotteryMax']}@@@{$_POST['LotteryPassword']}";
		$data['AppParameter'].="@@@{$_POST['LotteryDayMax']}@@@{$_POST['LotteryTip']}";
		return $data;
	}
	
	function batchSortLottery(){
		$AppOrder = $_POST['AppOrder']; //排序
		$AppID = $_POST['AppOrderID']; //排序
		$Keywords = $_REQUEST["Keywords"];
		$NowPage = $_REQUEST["NowPage"];
		$parameter = "?Keywords=$Keywords&p=$NowPage";
		if( is_array($AppID) && is_array($AppOrder) && count($AppID) > 0 && count($AppOrder) > 0 ){
			D('Admin/WxApp')->batchSortApp($AppID, $AppOrder);
			WriteLog();
		}
		redirect(__URL__."/lottery".$parameter);
	}
	//===================微信应用 结束=====================

	//微查询开始========================================
	function query(){
		header("Content-Type:text/html; charset=utf-8");
		$applist = include CONF_PATH.'wxapp.php';  //加载微信配置文件
        $App = array();
		foreach ($applist as $k=>$v){
			if( $v['type'] == 3 ){
				$temp = explode("\n", $v['description']);
				$App[] = array(
						'AppName'=>$v['name'],
						'Format'=>$temp[0],
						'Example'=>isset($temp[1])?$temp[1]:''
                );
			}
		}
		$this->assign('App', $App);
		$this->display();
	}
	//微查询结束========================================
	
	//微投票开始========================================
	function vote(){
		header("Content-Type:text/html; charset=utf-8");
		$AppTypeID = 2; //仅显示投票调查应用
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
	
		import("ORG.Util.Page");
		$m = D('Admin/WxApp');
		$TotalPage = $m->getCount($AppTypeID, $Keywords); //总页数
		$PageSize = $this->AdminPageSize;
		$Page = new Page($TotalPage, $PageSize);
	
		$Page->parameter = "&AppTypeID=$AppTypeID&Keywords=$Keywords";
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
	
		$v = D('Admin/WxVote');
		$m1 = D('Admin/WxAward');
		$data = $m->getApp($Page->firstRow, $Page->listRows, $AppTypeID, $Keywords);
		$count = is_array($data) ? count($data) : 0;
		for($i = 0; $i < $count; $i++){
			//是否多选@@@开始时间@@@结束时间@@@选项@@@投票图片@@@显示结果
			$p = explode('@@@', $data[$i]['AppParameter']);
			$data[$i]['IsMultiple'] = $p[0];
			$data[$i]['StartTime'] = $p[1];
			$data[$i]['EndTime'] = $p[2];
			$data[$i]['VotePicture'] = $p[4];
			$data[$i]['ShowResult'] = $p[5];
			$data[$i]['Number'] = $v->getTotalCount( $data[$i]['AppID'] ); //总票数
			$data[$i]['PeopleNumber'] = $v->getPeopleNumber( $data[$i]['AppID'] ); //总人数
			$items= (array)explode('$$$', $p[3]);
			$n = is_array($items) ? count($items) : 0;
			for($x=0; $x<$n; $x++){
				//获取票数
				$it = (array)explode('###', $items[$x]);
				$ItemID = $it[0];
				$ItemName = $it[1];
				$VoteCount = $v->GetVoteCount($data[$i]['AppID'], $ItemID);
				if($data[$i]['Number']!=0){
                    $VotePercent = ($VoteCount/$data[$i]['Number'])*100;
                }else{
                    $VotePercent = 0;
                }
				$data[$i]['Item'][$x] = array(
						'ItemName'=>$ItemName,
						'ItemID'=>$ItemID,
						'VoteCount'=>$VoteCount,
						'VotePercent'=>round($VotePercent,2),
				);
			}
		}
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Page', $ShowPage); //分页条
	
		$this->assign('AppTypeID', $AppTypeID);
		$this->assign('Keywords', $Keywords);
		$this->assign('App', $data);
	
		$this->display();
	}
	function addVote(){
		header("Content-Type:text/html; charset=utf-8");
		$StartTime = date('Y-m-d H:i:s'); //活动开始时间
		$EndTime = date('Y-m-d H:i:s', time()+365*24*60*60);  //活动结束时间＝活动开始时间+1天时间
	
		$this->assign('StartTime', $StartTime);
		$this->assign('EndTime',  $EndTime);
		$this->assign('Action', __URL__.'/saveVoteAdd');
		$this->display();
	}
	
	function saveVoteAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$data = $this->checkVoteParameter();
		$m = D('Admin/WxApp');
		if( $m->Add($data) === false ){
			$this->ajaxReturn(null, '添加失败!' , 0);
		}else{
			WriteLog("ID:".$m->getLastInsID() );
			$this->ajaxReturn(null, '添加成功!' , 1);
		}
	}
	
	function modifyVote(){
		header("Content-Type:text/html; charset=utf-8");
		$AppID = intval($_GET['AppID']);
		$m = D('Admin/WxApp');
		$data = $m->findVote($AppID);
	
		$this->assign('Action', __URL__.'/saveModifyVote');
		$this->assign('HiddenName', 'AppID');
		$this->assign('HiddenValue', $AppID);
		$this->assign('AppID',  $AppID);
		$this->assign('Data',  $data);
		$this->display();
	}
	
	function saveModifyVote(){
		header("Content-Type:text/html; charset=utf-8");
		$data = $this->checkVoteParameter();
		$m = D('Admin/WxApp');
		if( $m->save( $data ) === false ){
			$this->ajaxReturn(null, '修改失败!' , 0);
		}else{
			WriteLog("ID:".$_POST['AppID'] );
			$this->ajaxReturn(null, '修改成功!' , 1);
		}
	}
	
	function delVote(){
		header("Content-Type:text/html; charset=utf-8");
		$AppID = intval($_REQUEST["AppID"]);
		$Keywords = YdInput::checkKeyword( $_REQUEST['Keywords'] ) ;
		$p = intval($_GET["p"]);
		$parameter = "?Keywords=$Keywords&p=$p";
		$m = D('Admin/WxApp');
		if( $m->delete($AppID ) ){
			//删除应用的同时，删除相关中投票记录信息
			$m1 = D('Admin/WxVote');
			$m1->delVote($AppID);
			WriteLog("ID:$AppID");
		}
		redirect(__URL__."/vote/".$parameter);
	}
	
	//批量删除信息
	function batchDelVote(){
		$AppID = intval($_POST['AppID']);
		$Keywords = YdInput::checkKeyword($_REQUEST['Keywords']);
		$NowPage = intval($_REQUEST["NowPage"]);
		$parameter = "?Keywords=$Keywords&p=$NowPage";
	
		if( count($AppID) > 0 ){
			$m = D('Admin/WxApp');
			if($m->batchDelApp( $AppID )){
				//删除应用的同时，删除相关中投票记录信息
				$m1 = D('Admin/WxVote');
				$m1->delVote($AppID);
				WriteLog("ID:".implode(',', $AppID));
			}
		}
		redirect(__URL__."/vote".$parameter);
	}
	//批量排序Banner
	function batchSortVote(){
		$AppOrder = $_POST['AppOrder']; //排序
		$AppID = $_POST['AppOrderID']; //排序
		$Keywords = YdInput::checkKeyword($_REQUEST['Keywords']);
		$NowPage = intval($_REQUEST["NowPage"]);
		$parameter = "?Keywords=$Keywords&p=$NowPage";
		if( is_array($AppID) && is_array($AppOrder) && count($AppID) > 0 && count($AppOrder) > 0 ){
			D('Admin/WxApp')->batchSortApp($AppID, $AppOrder);
			WriteLog();
		}
		redirect(__URL__."/vote".$parameter);
	}
	
	function clearVote(){
		header("Content-Type:text/html; charset=utf-8");
		$appid = $_GET['appid'];
		$m = D('Admin/WxVote');
		if($m->delVote($appid)===false){
			$this->ajaxReturn(null, '清空失败!' , 0);
		}else{
			WriteLog( "ID:$appid");
			$this->ajaxReturn($appid, '清空成功!' , 1);
		}
	}
	
	function checkVoteParameter(){
		$VoteName = trim($_POST['VoteName']);
		if( $VoteName == ''){
			$this->ajaxReturn(null, '投票名称不能为空!' , 0);
		}
		
		if( strpos($VoteName, '@@@') !== false ){
			$this->ajaxReturn(null, '投票名称包含非法字符!' , 0);
		}
	
		$StartTime = strtotime(trim($_POST['StartTime']));
		if( empty($StartTime) ){
			$this->ajaxReturn(null, '开始日期无效!' , 0);
		}
	
		$EndTime = strtotime(trim($_POST['EndTime']));
		if( empty($EndTime) ){
			$this->ajaxReturn(null, '结束日期无效!' , 0);
		}
	
		if( $StartTime > $EndTime){
			$this->ajaxReturn(null, '结束日期必须大于开始日期!' , 0);
		}
		
		//投票项目start==========================================
		$n = is_array($_POST['ItemName']) ? count($_POST['ItemName']) : 0;
		if($n<2){
			$this->ajaxReturn(null, '至少需要2个投票项目!' , 0);
		}
		//投票项目ID的值取最大值
		$maxItemID = is_array($_POST['ItemID']) ? max($_POST['ItemID']) : -1;
		$maxItemID = ($maxItemID == -1) ? 1 : ($maxItemID+1);
        $items = array();
		for($i=0; $i < $n; $i++){
			if( empty($_POST['ItemName'][$i]) ){
				$this->ajaxReturn(null, '投票项目'.($i+1).'不能为空!' , 0);
			}
			
			if( empty($_POST['ItemID'][$i]) ){
				$_POST['ItemID'][$i] = $maxItemID;
				$maxItemID++;
			}
			$items[] = "{$_POST['ItemID'][$i]}###{$_POST['ItemName'][$i]}";
		}
		$VoteItem = implode('$$$', $items);
		//投票项目end==========================================
		
		//用于修改
		if(!empty($_POST['AppID'])){
			$data['AppID'] = $_POST['AppID'];
		}
		$data['AppName'] = $VoteName;
		$data['AppKeyword'] = $_POST['AppKeyword'];
		$data['AppTypeID'] = 2;
		//是否多选@@@开始时间@@@结束时间@@@选项
		$data['AppParameter']="{$_POST['IsMultiple']}@@@{$_POST['StartTime']}@@@{$_POST['EndTime']}@@@{$VoteItem}@@@{$_POST['VotePicture']}@@@{$_POST['ShowResult']}";
		$data['AppDescription'] = $_POST['Description'];
		$data['IsEnable'] = $_POST['IsEnable'];
		return $data;
	}
	
	//微投票结束========================================
	
	
	//微调查开始========================================
	function research(){
		header("Content-Type:text/html; charset=utf-8");
		$AppTypeID = 5; //仅显示调查应用
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
	
		import("ORG.Util.Page");
		$m = D('Admin/WxApp');
		$TotalPage = $m->getCount($AppTypeID, $Keywords); //总页数
		$PageSize = $this->AdminPageSize;
		$Page = new Page($TotalPage, $PageSize);

		$Page->parameter = "&AppTypeID=$AppTypeID";
		if($Keywords!=''){
			$Page->parameter .= "&Keywords=$Keywords";
		}
		
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();

		$data = $m->getApp($Page->firstRow, $Page->listRows, $AppTypeID, $Keywords);
		$count = is_array($data) ? count($data) : 0;
		$v = D('Admin/WxResearch');
		for($i = 0; $i < $count; $i++){
			parseAppParameter($data[$i]['AppParameter'], $AppTypeID, $data[$i]);
			$data[$i]['PeopleNumber'] = $v->getPeopleNumber( $data[$i]['AppID'] ); //总人数
		}
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Page', $ShowPage); //分页条
	
		$this->assign('AppTypeID', $AppTypeID);
		$this->assign('Keywords', $Keywords);
		$this->assign('App', $data);
	
		$this->display();
	}
	
	function addResearch(){
		header("Content-Type:text/html; charset=utf-8");
		$StartTime = date('Y-m-d H:i:s'); //活动开始时间
		$EndTime = date('Y-m-d H:i:s', time()+90*24*60*60);  //活动结束时间＝活动开始时间+1天时间
	
		$this->assign('StartTime', $StartTime);
		$this->assign('EndTime',  $EndTime);
		$this->assign('Action', __URL__.'/saveResearchAdd');
		$this->display();
	}
	
	function saveResearchAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$data = $this->checkResearchParameter();
		$m = D('Admin/WxApp');
		if( $m->Add($data) === false ){
			$this->ajaxReturn(null, '添加失败!' , 0);
		}else{
			WriteLog("ID:".$m->getLastInsID() );
			$this->ajaxReturn(null, '添加成功!' , 1);
		}
	}
	
	function modifyResearch(){
		header("Content-Type:text/html; charset=utf-8");
		$AppID = intval($_GET['AppID']);
		$m = D('Admin/WxApp');
		$data = $m->findApp($AppID);
	
		$this->assign('Action', __URL__.'/saveModifyResearch');
		$this->assign('HiddenName', 'AppID');
		$this->assign('HiddenValue', $AppID);
		$this->assign('AppID',  $AppID);
		$this->assign('r',  $data);
		$this->display();
	}
	
	function saveModifyResearch(){
		header("Content-Type:text/html; charset=utf-8");
		$data = $this->checkResearchParameter();
		$m = D('Admin/WxApp');
		if( $m->save( $data ) === false ){
			$this->ajaxReturn(null, '修改失败!' , 0);
		}else{
			WriteLog("ID:".$_POST['AppID'] );
			$this->ajaxReturn(null, '修改成功!' , 1);
		}
	}
	
	function checkResearchParameter(){
		$ResearchName = trim($_POST['ResearchName']);
		if( $ResearchName == ''){
			$this->ajaxReturn(null, '调查名称不能为空!' , 0);
		}
	
		if( strpos($ResearchName, '@@@') !== false ){
			$this->ajaxReturn(null, '调查名称包含非法字符!' , 0);
		}
	
		$StartTime = strtotime(trim($_POST['StartTime']));
		if( empty($StartTime) ){
			$this->ajaxReturn(null, '开始日期无效!' , 0);
		}
	
		$EndTime = strtotime(trim($_POST['EndTime']));
		if( empty($EndTime) ){
			$this->ajaxReturn(null, '结束日期无效!' , 0);
		}
	
		if( $StartTime > $EndTime){
			$this->ajaxReturn(null, '结束日期必须大于开始日期!' , 0);
		}

		//用于修改
		if(!empty($_POST['AppID'])){
			$data['AppID'] = $_POST['AppID'];
		}
		$data['AppName'] = $ResearchName;
		$data['AppKeyword'] = $_POST['AppKeyword'];
		$data['AppTypeID'] = 5;
		//图片封面@@@开始说明@@@开始说明@@@开始时间@@@结束时间@@@转向链接@@@图文描述@@@是否匿名
		$data['AppParameter']="{$_POST['ResearchPicture']}@@@{$_POST['StartDescription']}@@@{$_POST['EndDescription']}@@@{$_POST['StartTime']}@@@{$_POST['EndTime']}";
		$data['AppParameter'].="@@@{$_POST['LinkUrl']}@@@{$_POST['ResearchDescription']}@@@{$_POST['IsAnonymous']}";
		$data['AppDescription'] = $_POST['Description'];
		$data['IsEnable'] = $_POST['IsEnable'];
		return $data;
	}
	
	function delResearch(){
		header("Content-Type:text/html; charset=utf-8");
		$AppID = intval($_REQUEST["AppID"]);
		$Keywords = YdInput::checkKeyword($_REQUEST['Keywords']);
		$p = intval($_GET["p"]);
		$parameter = "?Keywords=$Keywords&p=$p";
		$m = D('Admin/WxApp');
		$m->delResearch($AppID );
		WriteLog("ID:$AppID");
		redirect(__URL__."/research/".$parameter);
	}
	
	//ajax方式清除用户调查数据
	function clearResearch(){
		header("Content-Type:text/html; charset=utf-8");
		$AppID = intval($_REQUEST["AppID"]);
		//删除投票记录
		$m2 = D('Admin/WxResearch');
		$m2->delResearch($AppID);
		//删除提交的建议
		$m3 = D('Admin/WxSuggest');
		$result = $m3->delSuggest($AppID);
		
		if( $result === false ){
			$this->ajaxReturn(null, '清除失败!' , 0);
		}else{
			WriteLog("ID:$AppID");
			$this->ajaxReturn(null, '清除成功!' , 1);
		}
	}
	
	//清除用户调查数据
	function clearResearchData(){
		header("Content-Type:text/html; charset=utf-8");
		$AppID = intval($_REQUEST["AppID"]);
		//删除投票记录
		$m2 = D('Admin/WxResearch');
		$m2->delResearch($AppID);
		//删除提交的建议
		$m3 = D('Admin/WxSuggest');
		$m3->delSuggest($AppID);
		WriteLog("ID:$AppID");
		redirect(__URL__."/suggest/AppID/$AppID");
	}
	
	//批量删除信息
	function batchDelResearch(){
		$AppID = intval($_POST['AppID']);
		$Keywords = YdInput::checkKeyword($_REQUEST['Keywords']);
		$NowPage = intval($_REQUEST["NowPage"]);
		$parameter = "?Keywords=$Keywords&p=$NowPage";
	
		if( count($AppID) > 0 ){
			$m = D('Admin/WxApp');
			$m->delResearch( $AppID );
			WriteLog("ID:".implode(',', $AppID));
		}
		redirect(__URL__."/research".$parameter);
	}
	
	function question(){
		header("Content-Type:text/html; charset=utf-8");
		$AppID = intval($_REQUEST['AppID']);
		$m = D('Admin/WxQuestion');
		$v = D('Admin/WxResearch');
		$data = $m->getQuestion($AppID);
		$count = is_array($data) ? count($data) : 0;
		for($i = 0; $i < $count; $i++){
			//1###aa@@@2###bb@@@3###cc
			$items = explode('@@@', $data[$i]['QuestionItem']);
			$n = is_array($items) ? count($items) : 0;
			for($x=0; $x<$n; $x++){
				//获取票数
				$it = (array)explode('###', $items[$x]);
				$ItemID = $it[0];
				$ItemName = $it[1];
				$VoteCount = $v->GetResearchCount($data[$i]['AppID'], $data[$i]['QuestionID'], $ItemID);
				$TotalNumber = $v->getTotalCount( $data[$i]['AppID'], $data[$i]['QuestionID']); //获取当前题目总票数
				$VotePercent = ($TotalNumber>0) ? ($VoteCount/$TotalNumber)*100 : 0;
				$data[$i]['Item'][$x] = array(
						'ItemName'=>$ItemName,
						'ItemID'=>$ItemID,
						'VoteCount'=>$VoteCount,
						'VotePercent'=>round($VotePercent,2),
				);
			}
		}
		$PeopleNumber = $v->getPeopleNumber( $AppID ); //总人数
		$AppName = D('Admin/WxApp')->where("AppID=$AppID")->getField('AppName');
		
		$this->assign('PeopleNumber', $PeopleNumber);
		$this->assign('AppName', $AppName);
		
		$this->assign('AppID', $AppID);
		$this->assign('Question', $data);
		$this->display();
	}
	
	//导出调查结果
	function exportQuestion(){
		$csvName = 'research'.date('Y-m-d_H_i_s').'.txt';
		$AppID = intval($_REQUEST['AppID']);
		$m = D('Admin/WxQuestion');
		$data = $m->getQuestion($AppID);
		$count = is_array($data) ? count($data) : 0;
		
		$v = D('Admin/WxResearch');
		$PeopleNumber = $v->getPeopleNumber( $AppID ); //总人数
		$AppName = D('Admin/WxApp')->where("AppID={$AppID}")->getField('AppName');
		$str = "{$AppName}  参与调查人数:{$PeopleNumber}人\r\n\r\n";
		
		for($i = 0; $i < $count; $i++){
			$n1 = $i + 1;
			$str .= "{$n1}.{$data[$i]['QuestionName']}\r\n";
			//1###aa@@@2###bb@@@3###cc
			$items = explode('@@@', $data[$i]['QuestionItem']);
			$n = is_array($items) ? count($items) : 0;
			for($x=0; $x<$n; $x++){
				//获取票数
				$it = (array)explode('###', $items[$x]);
				$ItemID = $it[0];
				$ItemName = $it[1];
				$VoteCount = $v->GetResearchCount($data[$i]['AppID'], $data[$i]['QuestionID'], $ItemID);
				$TotalNumber = $v->getTotalCount( $data[$i]['AppID'], $data[$i]['QuestionID']); //获取当前题目总票数
                if($TotalNumber>0){
                    $VotePercent = round(($VoteCount/$TotalNumber)*100, 2);
                }else{
                    $VotePercent = 0;
                }
				$n2 = $x+1;
				$str .= "[{$n2}]{$ItemName}  {$VoteCount}票  占{$VotePercent}%\r\n";
			}
			$str .= "\r\n";
		}
		$str= iconv('utf-8', 'gb2312//IGNORE', $str);
		WriteLog();
		yd_download_csv($csvName, $str); //下载csv
	}
	
	function addQuestion(){
		header("Content-Type:text/html; charset=utf-8");
		$AppID = intval($_REQUEST['AppID']);
		$this->assign('AppID', $AppID);
		$this->assign('Action', __URL__.'/saveQuestionAdd');
		$this->display();
	}
	
	function saveQuestionAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$data = $this->checkQuestionParameter();
		$m = D('Admin/WxQuestion');
		if( $m->add($data) === false ){
			WriteLog("ID:".$m->getLastInsID() );
			$this->ajaxReturn(null, '添加失败!' , 0);
		}else{
			$this->ajaxReturn(null, '添加成功!' , 1);
		}
	}
	
	function modifyQuestion(){
		header("Content-Type:text/html; charset=utf-8");
		$AppID = intval($_REQUEST['AppID']);
		$QuestionID = intval($_REQUEST['QuestionID']);
		$m = D('Admin/WxQuestion');
		$data = $m->findQuestion($QuestionID);
	
		$this->assign('Action', __URL__.'/saveModifyQuestion');
		$this->assign('HiddenName', 'QuestionID');
		$this->assign('HiddenValue', $QuestionID);
		$this->assign('QuestionID',  $QuestionID);
		$this->assign('AppID',  $AppID);
		$this->assign('q',  $data);
		$this->display();
	}
	
	function saveModifyQuestion(){
		header("Content-Type:text/html; charset=utf-8");
		$data = $this->checkQuestionParameter();
		$m = D('Admin/WxQuestion');
		if( $m->save( $data ) === false ){
			$this->ajaxReturn(null, '修改失败!' , 0);
		}else{
			WriteLog("ID:".$_POST['QuestionID'] );
			$this->ajaxReturn(null, '修改成功!' , 1);
		}
	}
	
	function checkQuestionParameter(){
		$QuestionName = trim($_POST['QuestionName']);
		if( $QuestionName == ''){
			$this->ajaxReturn(null, '题目名称不能为空!' , 0);
		}
	
		if( strpos($QuestionName, '@@@') !== false ){
			$this->ajaxReturn(null, '题目名称包含非法字符!' , 0);
		}
	
		//投票项目start==========================================
		$n = is_array($_POST['ItemName']) ? count($_POST['ItemName']) : 0;
		if($n<2){
			$this->ajaxReturn(null, '至少需要2个投票项目!' , 0);
		}
		//投票项目ID的值取最大值
		$maxItemID = is_array($_POST['ItemID']) ? max($_POST['ItemID']) : -1;
		$maxItemID = ($maxItemID == -1) ? 1 : ($maxItemID+1);
        $items = array();
		for($i=0; $i < $n; $i++){
			if( empty($_POST['ItemName'][$i]) ){
				$this->ajaxReturn(null, '项目'.($i+1).'不能为空!' , 0);
			}
				
			if( empty($_POST['ItemID'][$i]) ){
				$_POST['ItemID'][$i] = $maxItemID;
				$maxItemID++;
			}
			$items[] = "{$_POST['ItemID'][$i]}###{$_POST['ItemName'][$i]}";
		}
		$QuestionItem = implode('@@@', $items);
		//投票项目end==========================================
	
		//用于修改
		if(!empty($_POST['QuestionID'])){
			$data['QuestionID'] = $_POST['QuestionID'];
		}
		
		$data['QuestionName'] = $QuestionName;
		$data['AppID'] = $_REQUEST['AppID'];
		$data['IsMultiple'] = $_REQUEST['IsMultiple'];
		$data['QuestionOrder'] = $_REQUEST['QuestionOrder'];
		$data['QuestionItem']=$QuestionItem;
		$data['IsEnable'] = $_POST['IsEnable'];
		return $data;
	}
	
	function delQuestion(){
		header("Content-Type:text/html; charset=utf-8");
		$QuestionID = intval($_REQUEST["QuestionID"]);
		$AppID = intval($_REQUEST["AppID"]);
		$m = D('Admin/WxQuestion');
		$m->delQuestion($QuestionID, true);
		WriteLog("ID:$QuestionID");
		redirect(__URL__."/question/AppID/$AppID");
	}
	
	//批量删除
	function batchDelQuestion(){
		$QuestionID = $_REQUEST["QuestionID"];
		$AppID = intval($_REQUEST["AppID"]);
		if( count($QuestionID) > 0 ){
			$m = D('Admin/WxQuestion');
			$m->batchDelQuestion( $QuestionID, true);
			WriteLog("ID:".implode(',', $QuestionID));
		}
		redirect(__URL__."/question/AppID/$AppID");
	}
	//批量排序Banner
	function batchSortQuestion(){
		$QuestionOrder = $_POST['QuestionOrder']; //排序
		$QuestionID = $_POST['QuestionOrderID']; //排序
		$AppID = intval($_REQUEST["AppID"]);
		if( is_array($QuestionID) && is_array($QuestionOrder) && count($QuestionID) > 0 && count($QuestionOrder) > 0 ){
			$m = D('Admin/WxQuestion');
			$m->batchSortQuestion($QuestionID, $QuestionOrder);
			WriteLog();
		}
		redirect(__URL__."/question/AppID/$AppID");
	}
	//调查建议
	function suggest(){
		header("Content-Type:text/html; charset=utf-8");
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword($_REQUEST['Keywords']) : '';
		$AppID = !empty($_REQUEST['AppID']) ? intval($_REQUEST['AppID']) : false;
	
		import("ORG.Util.Page");
		$m = D('Admin/WxSuggest');
		$TotalPage = $m->getCount($AppID, $Keywords); //总页数
		$PageSize = $this->AdminPageSize;
		$Page = new Page($TotalPage, $PageSize);
		$Page->parameter = "&AppID=$AppID";
		if($Keywords!=''){
			$Page->parameter .= "&Keywords=$Keywords";
		}
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		$data = $m->getSuggest($Page->firstRow, $Page->listRows, $AppID, $Keywords);

		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Page', $ShowPage); //分页条
	
		$app = D('Admin/WxApp');
		$AppName = $app->where("AppID=$AppID")->getField('AppName');
		$this->assign('AppID', $AppID);
		$this->assign('AppName', $AppName);
		$this->assign('Keywords', $Keywords);
		$this->assign('Suggest', $data);
		$this->display();
	}
	
	//导出用户数据
	function exportSuggest(){
		$m = D('Admin/WxSuggest');
		$csvName = 'suggest'.date('Y-m-d_H_i_s').'.csv';  //导出文件名称
		$AppID = intval($_REQUEST['AppID']);
		$data= $m->getSuggest(-1,-1, $AppID);
		$str= "姓名,手机号码,建议内容,时间\n";
		foreach($data as $v){
			$MemberRealName = $v['MemberRealName'];
			$MemberMobile = $v['MemberMobile'];
			$SuggestContent = $v['SuggestContent'];
			$SuggestTime = $v['SuggestTime'];
			$str .= "$MemberRealName,$MemberMobile,$SuggestContent,$SuggestTime\n";
		}
		WriteLog();
		$str= iconv('utf-8', 'gb2312//IGNORE', $str);
		yd_download_csv($csvName, $str); //下载csv
	}
	//微调查结束========================================
	
	
	//微会员卡开始========================================
	function card(){
		header("Content-Type:text/html; charset=utf-8");
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword($_REQUEST['Keywords']) : '';
		
		$m = D('Admin/Member');
		import("ORG.Util.Page");
		$TotalPage = $m->getCardCount($Keywords);
		$PageSize = $this->AdminPageSize;
		
		$Page = new Page($TotalPage, $PageSize);
		if( $Keywords != ''){
			$Page->parameter = "&Keywords=$Keywords";
		}
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		
		$Member= $m->getMemberCard($Page->firstRow, $Page->listRows, $Keywords);
		$n = is_array($Member) ? count($Member) : 0;
		$c = D('Admin/WxConsume');
		$s = D('Admin/WxScore');
		for($i=0; $i<$n;$i++){
			$MemberID = $Member[$i]['MemberID'];
			$totalMoney = $c->getTotal($MemberID);
			$usedMoney = $c->getUsed($MemberID);
			$totalScore = $s->getTotal($MemberID);
			$usedScore = $s->getUsed($MemberID);
			
			$Member[$i]['TotalMoney'] = $totalMoney;
			$Member[$i]['UnUsedMoney'] = $totalMoney - $usedMoney;
			$Member[$i]['TotalScore'] = $totalScore;
			$Member[$i]['UnUsedScore'] = $totalScore - $usedScore;
		}
		
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Member', $Member);
		$this->assign('Keywords', $Keywords); //当前频道
		$this->assign('Page', $ShowPage); //分页条
		$this->display();
	}
	
	//会员卡设置
	function cardConfig(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/WxApp');
		$data = $m->findCardConfig();
		if(empty($data['CardNumberColor']) ){
			$data['CardNumberColor'] = '#000000';
		}
		if(empty($data['CardNameColor']) ){
			$data['CardNameColor'] = '#000000';
		}
		if(empty($data['CardTip']) ){
			$data['CardTip'] = '微时代会员卡，方便携带收藏，永不挂失';
		}
		if(!is_numeric($data['SignAward']) ){
			$data['SignAward'] = 1;
		}
		if(!is_numeric($data['ConsumeAward']) ){
			$data['ConsumeAward'] = 1;
		}
		
		//获取频道信息
		$m3 = D('Admin/Channel');
		$gid = intval(session('AdminGroupID'));
		$Channel = $m3->getChannel(0,true,true, -1, -1, '&nbsp;&nbsp;&nbsp;&nbsp;├─ ', $gid); //不显示链接频道
		$this->assign('Channel', $Channel);
		//链接应用信息
		$UrlApp = $m->getUrlApp();
		$this->assign('UrlApp', $UrlApp);
		
		$this->assign('c', $data); //会员卡配置数据
		$this->assign('Action', __URL__.'/updateCardConfig');
		$this->display();
	}
	
	function updateCardConfig(){
		header("Content-Type:text/html; charset=utf-8");
		$data = $this->checkCardParameter();
		$m = D('Admin/WxApp');
		if( $m->updateCardConfig($data) === false ){
			WriteLog();
			$this->ajaxReturn(null, '保存失败!' , 0);
		}else{
			$this->ajaxReturn(null, '保存成功!' , 1);
		}
	}
	function checkCardParameter(){
		if( strpos($_POST['CardName'], '@@@') !== false ){
			$this->ajaxReturn(null, '会员卡名称包含非法字符!' , 0);
		}
		
		if( strpos($_POST['CardTip'], '@@@') !== false ){
			$this->ajaxReturn(null, '首页提示文字包含非法字符!' , 0);
		}
		
		if( strpos($_POST['CardDescription'], '@@@') !== false ){
			$this->ajaxReturn(null, '首页提示文字包含非法字符!' , 0);
		}
		
		if( strpos($_POST['ScoreDescription'], '@@@') !== false ){
			$this->ajaxReturn(null, '积分规则说明包含非法字符!' , 0);
		}
		
		if( !is_numeric($_POST['SignAward']) ){
			$this->ajaxReturn(null, '签到奖励必须为数字!' , 0);
		}
		
		if( !is_numeric($_POST['ConsumeAward']) ){
			$this->ajaxReturn(null, '消费奖励必须为数字!' , 0);
		}
		
		if( strpos($_POST['MerchantDescription'], '@@@') !== false ){
			$this->ajaxReturn(null, '商家包含非法字符!' , 0);
		}
		
		//分店管理start==========================================
		$n = is_array($_POST['StoreName']) ? count($_POST['StoreName']) : 0;
		//投票项目ID的值取最大值
		$maxStoreID = is_array($_POST['StoreID']) ? max($_POST['StoreID']) : -1;
		$maxStoreID = ($maxStoreID == -1) ? 1 : ($maxStoreID+1);
        $items = array();
		for($i=0; $i < $n; $i++){
			if( empty($_POST['StoreName'][$i]) ){
				$this->ajaxReturn(null, '分店'.($i+1).'名称不能为空!' , 0);
			}
		
			if( empty($_POST['StoreID'][$i]) ){
				$_POST['StoreID'][$i] = $maxStoreID;
				$maxStoreID++;
			}
			$items[] = "{$_POST['StoreID'][$i]}###{$_POST['StoreName'][$i]}###{$_POST['StoreTelephone'][$i]}###{$_POST['StoreAddress'][$i]}";
		}
		$StoreItem = implode('$$$', $items);
		//分店管理end==========================================
		
		//业务关联 start=========================================
		$n = is_array($_POST['LinkName']) ? count($_POST['LinkName']) : 0;
		$LinkItem = "";
		for($i=0; $i < $n; $i++){
			if($i != 0 ) $LinkItem .= '$$$';
			$LinkItem .= "{$_POST['LinkName'][$i]}###{$_POST['LinkType'][$i]}###{$_POST['LinkUrl'][$i]}";
		}
		//业务关联 end==========================================
	
		//用于修改
		//$data['AppID'] = $_POST['AppID'];  //等于－1表示添加，否则修改
		$data['AppName'] = $_POST['CardName'];
		$data['AppKeyword'] = $_POST['AppKeyword'];
		$data['AppTypeID'] = 6;
		//0名称@@@1图标@@@2背景@@@3封面图片@@@4封面消息@@@5卡号文字颜色@@@6名称文字颜色
		//@@@7使用说明@@@8积分规则说明@@@9签到奖励@@@10消费奖励
		//@@@11商家名称@@@12商家简介@@@13联系方式@@@14商家地址@@@15经度@@@16纬度@@@17商家确认消费密码
		//@@@分店列表(StoreID###StoreName###StoreTelephone###StoreAddress$$$)
		$data['AppParameter']="{$_POST['CardName']}@@@{$_POST['CardIcon']}@@@{$_POST['CardBackground']}";
		$data['AppParameter'].="@@@{$_POST['CardPicture']}@@@{$_POST['CardTip']}";
		$data['AppParameter'].="@@@{$_POST['CardNumberColor']}@@@{$_POST['CardNameColor']}";
		$data['AppParameter'].="@@@{$_POST['CardDescription']}@@@{$_POST['ScoreDescription']}@@@{$_POST['SignAward']}@@@{$_POST['ConsumeAward']}";
		$data['AppParameter'].="@@@{$_POST['MerchantName']}@@@{$_POST['MerchantDescription']}@@@{$_POST['MerchantTelephone']}";
		$data['AppParameter'].="@@@{$_POST['MerchantAddress']}@@@{$_POST['Longitude']}@@@{$_POST['Latitude']}@@@{$_POST['CardPassword']}";
		$data['AppParameter'].="@@@{$StoreItem}@@@{$LinkItem}";
		$data['IsEnable'] = $_POST['IsEnable'];
		return $data;
	}
	
	//会员充值
	function pay(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = intval($_REQUEST["MemberID"]);
		$ConsumeMoney = $_REQUEST["ConsumeMoney"];
		if(!is_numeric($ConsumeMoney)){
			$this->ajaxReturn(null, '充值金额必须为数字!' , 0);
		}
		$Remark = $_REQUEST["Remark"];
		$m = D('Admin/WxConsume');
		if( $m->pay($MemberID,$ConsumeMoney,$Remark) === false ){
			$this->ajaxReturn(null, '充值失败!' , 0);
		}else{
			WriteLog("充值金额：".$ConsumeMoney);
			$this->ajaxReturn(null, '充值成功!' , 1);
		}
	}
	
	//赠送积分
	function giveScore(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = intval($_REQUEST["MemberID"]);
		$ScoreNumber = $_REQUEST["ScoreNumber"];
		if(!is_numeric($ScoreNumber)){
			$this->ajaxReturn(null, '积分必须为数字!' , 0);
		}
		$Remark = $_REQUEST["Remark"];
		$m = D('Admin/WxScore');
		if( $m->give($MemberID,$ScoreNumber,$Remark) === false ){
			$this->ajaxReturn(null, '赠送积分失败!' , 0);
		}else{
			WriteLog("赠送积分：".$ScoreNumber);
			$this->ajaxReturn(null, '赠送积分成功!' , 1);
		}
	}
	
	//批量冻结
	function batchLock(){
		$id = $_POST['MemberID'];
		$NowPage = intval($_POST["NowPage"]);
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword($_REQUEST['Keywords']) : '';
		$Lock = $_GET['Lock'];  //审核值
		$url = __URL__."/card/p/$NowPage";
		if($Keywords!='')$url.="/Keywords/$Keywords";
		if( count($id) > 0 ){
			$m = D('Admin/Member');
			$m->batchLockMember( $id , $Lock);
			WriteLog("ID:".implode(',', $id));
		}
		redirect($url);
	}
	
	//导出，不能使用ajax提交
	function export(){
		$m = D('Admin/Member');
		$c = D('Admin/WxConsume');
		$s = D('Admin/WxScore');
		$csvName = 'member'.date('Y-m-d_H_i').'.csv';  //导出文件名称
		$data= $m->getMember();
		$str= "会员卡号,真实姓名,性别,所属分组,移动电话,电话,QQ,E-mail,余额,总金额,剩余积分,总积分,领卡时间,联系地址\n";
		$g = array(0=>'男',1=>'女');
		//注意导入和导出的过程中，因为我们使用的是统一UTF-8编码，
		//遇到中文字符一定要记得转码，否则可能会出现中文乱码的情况。
		foreach($data as $v){
			$MemberID = $v['MemberID'];
			$totalMoney = $c->getTotal($MemberID);
			$usedMoney = $c->getUsed($MemberID);
			$unusedMoney = $totalMoney-$usedMoney;
			$totalScore = $s->getTotal($MemberID);
			$usedScore = $s->getUsed($MemberID);
			$unusedScore = $totalScore-$usedScore;
			$MemberGender = $g[$v['MemberGender']];
			
			$str .= "{$v['CardNumber']},{$v['MemberRealName']},$MemberGender,{$v['MemberGroupName']},";
			$str .= "{$v['MemberMobile']},{$v['MemberTelphone']},{$v['MemberQQ']},{$v['MemberEmail']},";
			$str .= "$unusedMoney,$totalMoney,$unusedScore,$totalScore,";
			$str .= "{$v['CardTime']},{$v['MemberAddress']}\n";
		}
		WriteLog();
		$str= iconv('utf-8', 'gb2312//IGNORE', $str);
		yd_download_csv($csvName, $str); //下载csv
	}
	
	//消费记录
	function consume(){
		header("Content-Type:text/html; charset=utf-8");
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword($_REQUEST['Keywords']) : '';
		
		$m = D('Admin/WxConsume');
		import("ORG.Util.Page");
		$TotalPage = $m->getCount($Keywords);
		$PageSize = $this->AdminPageSize;
		
		$Page = new Page($TotalPage, $PageSize);
		if( $Keywords != ''){
			$Page->parameter = "&Keywords=$Keywords";
		}
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		$Data= $m->getConsume($Page->firstRow, $Page->listRows, $Keywords);
		
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Consume', $Data);
		$this->assign('Keywords', $Keywords); //当前频道
		$this->assign('Page', $ShowPage); //分页条
		$this->display();
	}
	
	//消费金额
	function expense(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = intval($_REQUEST['MemberID']); //会员ID
		$ConsumeMoney = $_REQUEST['ConsumeMoney']; //消费金额
		$ConsumeType = $_REQUEST['ConsumeType']; //支付方式
		$Remark = $_REQUEST['Remark'];  //备注
		
		if(empty($MemberID) || !is_numeric($ConsumeMoney)){
			$this->ajaxReturn(null, '提交失败!' , 0);
		}
		
		$wc = D('Admin/WxConsume');
		//余额消费需要判断余额是否不足
		if( $ConsumeType == 2){
			$UnUsedMoney = $wc->getUnUsed($MemberID);
			if($UnUsedMoney < $ConsumeMoney){
				$this->ajaxReturn(null, $UnUsedMoney , 3); //返回余额不足的消息
			}
		}
		
		//保存消费信息 start==========
		$data = array(
				'MemberID'=>$MemberID,
				'ConsumeMoney'=>$ConsumeMoney,
				'ConsumeTime'=>date('Y-m-d H:i:s'),
				'ConsumeType'=>$ConsumeType,  //1:充值，2：余额消费，3：现金消费
				'Remark'=>$Remark,
		);
		$result = $wc->add($data);
		//保存消费信息 end==========
		
		//消费金额赠送积分
		if($result !== false){
			$wa = D('Admin/WxApp');
			$config = $wa->findCardConfig();
			$award = $config['ConsumeAward'];
			if( $award > 0){
				$score = (int)($award*$ConsumeMoney);
				$ws = D('Admin/WxScore');
				$result = $ws->expend($MemberID, $score);
			}
		}
		
		if($result !== false){
			WriteLog( 'type：'.$ConsumeType);
			$this->ajaxReturn(null, '提交成功!' , 1);
		}else{
			$this->ajaxReturn(null, '提交失败!' , 0);
		}
	}
	
	//积分记录
	function score(){
		header("Content-Type:text/html; charset=utf-8");
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword($_REQUEST['Keywords']) : '';
	
		$m = D('Admin/WxScore');
		import("ORG.Util.Page");
		$TotalPage = $m->getCount($Keywords);
		$PageSize = $this->AdminPageSize;
	
		$Page = new Page($TotalPage, $PageSize);
		if( $Keywords != ''){
			$Page->parameter = "&Keywords=$Keywords";
		}
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		$Data= $m->getScore($Page->firstRow, $Page->listRows, $Keywords);
	
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Score', $Data);
		$this->assign('Keywords', $Keywords); //当前频道
		$this->assign('Page', $ShowPage); //分页条
		$this->display();
	}
	
	//礼品券
	function gift(){
		header("Content-Type:text/html; charset=utf-8");
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword($_REQUEST['Keywords']) : '';
	
		import("ORG.Util.Page");
		$m = D('Admin/WxGift');
		$TotalPage = $m->getCount($Keywords); //总页数
		$PageSize = 10;
		$Page = new Page($TotalPage, $PageSize);
	
		if($Keywords != ''){
			$Page->parameter = "&Keywords=$Keywords";
		}
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		$data = $m->getGift($Page->firstRow, $Page->listRows, $Keywords);
		$n = is_array($data) ? count($data) : 0;
		$ws = D('Admin/WxScore');
		for($i=0; $i<$n; $i++){
			$data[$i]['GiftUsed'] = $ws->GetGiftUsed( $data[$i]['GiftID'] );
			$data[$i]['Flag'] = isTimeRange($data[$i]['StartTime'], $data[$i]['EndTime']);
		}
		
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Page', $ShowPage); //分页条
		$this->assign('Keywords', $Keywords);
		$this->assign('Gift', $data);
		$this->display();
	}
	
	//查看队员礼品会员
	function giftMember(){
		header("Content-Type:text/html; charset=utf-8");
		$GiftID = !empty($_REQUEST['GiftID']) ? $_REQUEST['GiftID'] : '';
		
		import("ORG.Util.Page");
		$m = D('Admin/WxScore');
		$TotalPage = $m->getGiftMemberCount($GiftID); //总页数
		$PageSize = $this->AdminPageSize;
		$Page = new Page($TotalPage, $PageSize);
		
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		$data = $m->getGiftMember($Page->firstRow, $Page->listRows, $GiftID);
		
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Page', $ShowPage); //分页条
		$this->assign('GiftMember', $data);
		$this->display();
	}
	
	function addGift(){
		header("Content-Type:text/html; charset=utf-8");
		$StartTime = date('Y-m-d H:i:s'); //活动开始时间
		$EndTime = date('Y-m-d H:i:s', time()+90*24*60*60);  //活动结束时间＝活动开始时间+1天时间
	
		$this->assign('StartTime', $StartTime);
		$this->assign('EndTime',  $EndTime);
		$this->assign('Action', __URL__.'/saveGiftAdd');
		$this->display();
	}
	
	function saveGiftAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/WxGift');
		if( $m->create() ){
			if($m->add()){
				WriteLog("ID:".$m->getLastInsID() );
				$this->ajaxReturn(null, '添加成功!' , 1);
			}else{
				$this->ajaxReturn(null, '添加失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function modifyGift(){
		header("Content-Type:text/html; charset=utf-8");
		$GiftID = intval($_GET['GiftID']);
		$m = D('Admin/WxGift');
		$data = $m->find($GiftID);
	
		$this->assign('Action', __URL__.'/saveModifyGift');
		$this->assign('HiddenName', 'GiftID');
		$this->assign('HiddenValue', $GiftID);
		$this->assign('GiftID',  $GiftID);
		$this->assign('g',  $data);
		$this->display();
	}
	
	function saveModifyGift(){
			header("Content-Type:text/html; charset=utf-8");
			$m = D('Admin/WxGift');
			if( $m->create() ){
				if($m->save() === false){
					WriteLog("ID:".$_POST['GiftID'] );
					$this->ajaxReturn(null, '修改失败!' , 0);
				}else{
					$this->ajaxReturn(null, '修改成功!' , 1);
				}
			}else{
				$this->ajaxReturn(null, $m->getError() , 0);
			}
	}
	
	//删除和批量删除合二为一
	function delGift(){
		header("Content-Type:text/html; charset=utf-8");
		$GiftID = $_REQUEST["GiftID"];
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword($_REQUEST['Keywords']) : '';
		$parameter = '?p='.intval($_REQUEST["NowPage"]);
		if($Keywords != ''){
			$parameter .= "&Keywords=$Keywords";
		}
		$m = D('Admin/WxGift');
		$m->delGift($GiftID);
		WriteLog("ID:".implode(',', $GiftID));
		redirect(__URL__."/gift/".$parameter);
	}
	
	function batchSortGift(){
		$GiftID = $_POST['GiftOrderID']; //排序
		$GiftOrder = $_POST['GiftOrder']; //排序
		$NowPage = intval($_POST["NowPage"]);
		if( is_array($GiftID) && is_array($GiftOrder) && count($GiftID) > 0 && count($GiftOrder) > 0 ){
			D('Admin/WxGift')->batchSortGift($GiftID, $GiftOrder);
			WriteLog();
		}
		redirect(__URL__."/gift?p=$NowPage");
	}
	
	//会员通知
	function notify(){
		header("Content-Type:text/html; charset=utf-8");
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword($_REQUEST['Keywords']): '';
	
		import("ORG.Util.Page");
		$m = D('Admin/WxNotify');
		$TotalPage = $m->getCount($Keywords); //总页数
		$PageSize = $this->AdminPageSize;;
		$Page = new Page($TotalPage, $PageSize);
	
		if($Keywords != ''){
			//Keywords前必须加&
			$Page->parameter = "&Keywords=$Keywords";
		}
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		$data = $m->getNotify($Page->firstRow, $Page->listRows, $Keywords);
		
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Page', $ShowPage); //分页条
		$this->assign('Keywords', $Keywords);
		$this->assign('Notify', $data);
		$this->display();
	}
	
	//添加会员通知
	function addNotify(){
		header("Content-Type:text/html; charset=utf-8");
		$NotifyTime = date('Y-m-d H:i:s'); //活动开始时间
		$this->assign('NotifyTime', $NotifyTime);
		$this->assign('Action', __URL__.'/saveNotifyAdd');
		$this->display();
	}
	
	//保存会员通知
	function saveNotifyAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/WxNotify');
		if( $m->create() ){
			if($m->add()){
				WriteLog("ID:".$m->getLastInsID() );
				$this->ajaxReturn(null, '添加成功!' , 1);
			}else{
				$this->ajaxReturn(null, '添加失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	//修改通知
	function modifyNotify(){
		header("Content-Type:text/html; charset=utf-8");
		$NotifyID = $_GET['NotifyID'];
		$m = D('Admin/WxNotify');
		$data = $m->find($NotifyID);
	
		$this->assign('Action', __URL__.'/saveModifyNotify');
		$this->assign('HiddenName', 'NotifyID');
		$this->assign('HiddenValue', $NotifyID);
		$this->assign('NotifyID',  $NotifyID);
		$this->assign('n',  $data);
		$this->display();
	}
	
	//保存通知修改
	function saveModifyNotify(){
			header("Content-Type:text/html; charset=utf-8");
			$m = D('Admin/WxNotify');
			if( $m->create() ){
				if($m->save() === false){
					$this->ajaxReturn(null, '修改失败!' , 0);
				}else{
					WriteLog("ID:".$_POST['NotifyID'] );
					$this->ajaxReturn(null, '修改成功!' , 1);
				}
			}else{
				$this->ajaxReturn(null, $m->getError() , 0);
			}
	}
	
	//删除/批量删除通知
	function delNotify(){
		header("Content-Type:text/html; charset=utf-8");
		$NotifyID = $_REQUEST["NotifyID"];
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword($_REQUEST['Keywords']) : '';
		$parameter = '?p='.intval($_REQUEST["NowPage"]);
		if($Keywords != ''){
			$parameter .= "&Keywords=$Keywords";
		}
		$m = D('Admin/WxNotify');
		$m->delNotify($NotifyID);
		WriteLog("ID:".implode(',', $NotifyID));
		redirect(__URL__."/notify".$parameter);
	}
	
	
	//优惠卷管理
	//优惠卷管理首页
	function coupon(){
		header("Content-Type:text/html; charset=utf-8");
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword($_REQUEST['Keywords']) : '';
	
		import("ORG.Util.Page");
		$m = D('Admin/WxCoupon');
		$TotalPage = $m->getCount($Keywords); //总页数
		$PageSize = $this->AdminPageSize;
		$Page = new Page($TotalPage, $PageSize);
	
		if($Keywords != ''){
			$Page->parameter = "&Keywords=$Keywords";
		}
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		$data = $m->getCoupon($Page->firstRow, $Page->listRows, $Keywords);
		$n = is_array($data) ? count($data) : 0;
		$wc = D('Admin/WxConsume');
		for($i=0; $i<$n; $i++){
			$data[$i]['CouponUsed'] = $wc->GetCouponUsed( $data[$i]['CouponID'] );
			$data[$i]['Flag'] = isTimeRange($data[$i]['StartTime'], $data[$i]['EndTime']);
		}
	
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Page', $ShowPage); //分页条
		$this->assign('Keywords', $Keywords);
		$this->assign('Coupon', $data);
		$this->display();
	}
	
	//查看队员礼品会员
	function couponMember(){
		header("Content-Type:text/html; charset=utf-8");
		$CouponID = !empty($_REQUEST['CouponID']) ? $_REQUEST['CouponID'] : '';
	
		import("ORG.Util.Page");
		$m = D('Admin/WxConsume');
		$TotalPage = $m->getCouponMemberCount($CouponID); //总页数
		$PageSize = $this->AdminPageSize;
		$Page = new Page($TotalPage, $PageSize);
	
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		$data = $m->getCouponMember($Page->firstRow, $Page->listRows, $CouponID);
	
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Page', $ShowPage); //分页条
		$this->assign('CouponMember', $data);
		$this->display();
	}
	
	function addCoupon(){
		header("Content-Type:text/html; charset=utf-8");
		$StartTime = date('Y-m-d H:i:s'); //活动开始时间
		$EndTime = date('Y-m-d H:i:s', time()+90*24*60*60);  //活动结束时间＝活动开始时间+1天时间
	
		$this->assign('StartTime', $StartTime);
		$this->assign('EndTime',  $EndTime);
		$this->assign('Action', __URL__.'/saveCouponAdd');
		$this->display();
	}
	
	function saveCouponAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/WxCoupon');
		if( $m->create() ){
			if($m->add()){
				WriteLog("ID:".$m->getLastInsID() );
				$this->ajaxReturn(null, '添加成功!' , 1);
			}else{
				$this->ajaxReturn(null, '添加失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function modifyCoupon(){
		header("Content-Type:text/html; charset=utf-8");
		$CouponID = intval($_GET['CouponID']);
		$m = D('Admin/WxCoupon');
		$data = $m->find($CouponID);
	
		$this->assign('Action', __URL__.'/saveModifyCoupon');
		$this->assign('HiddenName', 'CouponID');
		$this->assign('HiddenValue', $CouponID);
		$this->assign('CouponID',  $CouponID);
		$this->assign('c',  $data);
		$this->display();
	}
	
	function saveModifyCoupon(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/WxCoupon');
		if( $m->create() ){
			if($m->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				WriteLog("ID:".$_POST['CouponID'] );
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	//删除和批量删除合二为一
	function delCoupon(){
		header("Content-Type:text/html; charset=utf-8");
		$CouponID = $_REQUEST["CouponID"];
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword($_REQUEST['Keywords']) : '';
		$parameter = '?p='.intval($_REQUEST["NowPage"]);
		if($Keywords != ''){
			$parameter .= "&Keywords=$Keywords";
		}
		$m = D('Admin/WxCoupon');
		$m->delCoupon($CouponID);
		WriteLog("ID:".implode(',', $CouponID));
		redirect(__URL__."/coupon/".$parameter);
	}
	
	function batchSortCoupon(){
		$CouponID = $_POST['CouponOrderID']; //排序
		$CouponOrder = $_POST['CouponOrder']; //排序
		$NowPage = intval($_POST["NowPage"]);
		if( is_array($CouponID) && is_array($CouponOrder) && count($CouponID) > 0 && count($CouponOrder) > 0 ){
			D('Admin/WxCoupon')->batchSortCoupon($CouponID, $CouponOrder);
			WriteLog();
		}
		redirect(__URL__."/coupon?p=$NowPage");
	}
	
	//微会员卡结束========================================
}