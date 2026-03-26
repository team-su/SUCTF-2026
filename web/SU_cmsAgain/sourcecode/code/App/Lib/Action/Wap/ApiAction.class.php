<?php
/**
 * 系统API
 * 当前版本：v1.1.4
 * 最后修改时间：2018-05-10
 */

/**
 * 判断信息是否存在
 * @param int $InfoID
 * @return int
 */
function app_info_exist($InfoID){
	$m = D('Admin/Info');
	$where['InfoID'] = intval($InfoID);
	$where['IsEnable'] = 1;
	$id = $m->where($where)->getField('InfoID');
	return $id;
}

/**
 * 判断会员是否存在
 * @param int $MemberID
 */
function app_member_exist($MemberID){
	$m = D('Admin/Member');
	$where['MemberID'] = intval($MemberID);
	$where['IsCheck'] = 1;
	$where['IsLock'] = 0;
	$id = $m->where($where)->getField('MemberID');
	return $id;
}

/**
 * 判断会员是否可以收藏
 * @param int $MemberID
 */
function app_can_favorite($MemberID){
	$MaxFavoritePerDay = 50; //定义每个会员每天最大收藏数
	$m = D('Admin/Favorite');
	$MemberID = intval($MemberID);
	$where = "to_days(FavoriteTime) = to_days(now()) and MemberID=$MemberID";
	$n = $m->where($where)->count();
	if( $n > $MaxFavoritePerDay){
		return false;
	}else{
		return true;
	}
}

/**
 * 判断指定ip是否能添加反馈
 * @param string $ip
 */
function app_can_feedback($ip){
	$MaxFeedbackPerIp = 10; //定义每个ID每天最大反馈数
	$m = D('Admin/AppFeedback');
	$ip = addslashes(stripslashes($ip)); //过滤危险字符
	$where = "to_days(AppFeedbackTime) = to_days(now()) and AppFeedbackIp='{$ip}'";
	$n = $m->where($where)->count();
	if( $n > $MaxFeedbackPerIp){
		return false;
	}else{
		return true;
	}
}

/**
 * 判断指定ip是否能加入设备统计
 * @param string $ip
 */
function app_can_stat($ip){
	$MaxDevicePerIp = 10; //定义每个IP每天最大提交的设备数
	$m = D('Admin/AppStat');
	$ip = addslashes(stripslashes($ip)); //过滤危险字符
	$where = "to_days(Time) = to_days(now()) and Ip='{$ip}'";
	$n = $m->where($where)->count();
	if( $n > $MaxDevicePerIp){
		return false;
	}else{
		return true;
	}
}

/**
 * 判断指定ip是否可以注册
 * @param string $ip
 */
function app_can_reg($ip){
	$MaxRegPerIp = 5; //定义每个IP每天最大注册数量
	$m = D('Admin/Member');
	$ip = addslashes(stripslashes($ip)); //过滤危险字符
	$where = "to_days(RegisterTime) = to_days(now()) and RegisterIP='{$ip}'";
	$n = $m->where($where)->count();
	if( $n > $MaxRegPerIp){
		return false;
	}else{
		return true;
	}
}

/**
 * 参数过滤
 * @param array $para 参数
 */
function app_para_filter($para) {
	$para_filter = array();
	foreach ($para as $key=>$val) {
		//去除调试参数：&XDEBUG_SESSION_START=ECLIPSE_DBGP&KEY=14868670618872
		if(!is_array($val) && $val !== '' && $key != 'Sign' && $key !='XDEBUG_SESSION_START' && $key !='KEY') {
			$para_filter[$key] = $para[$key];
		}
	}
	return $para_filter;
}

/**
 * 生成签名
 * @param array $para
 * @param array $AppSecret 秘钥
 */
function app_build_sign($para,$AppSecret) {
	//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
	//由于js客户端在计算参数时用了encodeURIComponent，所以服务器端参数也必须urlencode编码
	//原因：同一中文字符使用 js md5.createHash()和php md5函数的结果不一样，所以必须编码
	//Upload(1).jpg encodeURIComponent: Upload%2F1(1).jpg，
	//                                 urlencode结果:   Upload%2F1%281%29.jpg，2者结果不一样
	$str = app_link_string($para,false);
	$sign = md5($str.$AppSecret);
	return $sign;
}

/**
 * 连接参数
 * @param array $para
 * @param boo $urlEncode 是否编码
 * @return string
 */
function app_link_string($para, $urlEncode=true) {
	$arg  = '';
	foreach ($para as $key=>$val ) {
		if($urlEncode) $val = urlencode($val);
		$arg .= "{$key}={$val}&";
	}
	//去掉最后一个&字符
	$arg = substr($arg, 0, -1);
	//如果存在转义字符，那么去掉转义
	if(get_magic_quotes_gpc()){
		$arg = stripslashes($arg);
	}
	return $arg;
}

/**
 * 将内容里的路径转化为绝对路径
 * @param array $data 二维数组
 * @param array/string $fieldName 字段列表
 */
function app_relative_to_absolute(&$data, $fieldName=array()){
	if(CLIENT_TYPE != 3 && !empty($data) && !empty($fieldName) ){
		$domain = get_current_url();
		$n = is_array($data) ? count($data) : 0;
		$list = explode(',', $fieldName);
		for($i=0; $i<$n; $i++){
			foreach ($list as $v){
				$v = trim($v);
				if( isset($data[$i][$v]) ){
					$data[$i][$v] = yd_relative_to_absolute( $data[$i][$v] ,$domain);
				}
			}
		}
	}
}

/**
 * 针对不同的客户端（1：APP，2：小程序，3：站内同域调用）删除相应的标签
 * @param str $content
 */
function app_strip_tags($content){
	if(CLIENT_TYPE==2){
		//在小程序端必须过滤script标记
		$content = preg_replace('/<script.*?>.*?<\/script.*?>/si', "", $content);
		//标签有style属性会导致打不开
		$content = str_ireplace('style="', 'style1="', $content);
	}
	return $content;
}

class ApiAction extends HomeBaseAction {
	//Api初始化
	function _initialize(){
		parent::_initialize();
		error_reporting(0); //API调用关闭所有错误
		define('API_SHOW_TIME', APP_DEBUG);
		if(API_SHOW_TIME){
			$GLOBALS['ApiStartTime'] = microtime(TRUE);
			if(MEMORY_LIMIT_ON) {
				$GLOBALS['ApiStartMemory'] = memory_get_usage();
			}
		}
		
		//API通用参数
		$Format = isset($_REQUEST['Format']) ? trim($_REQUEST['Format']) : 'json';
		$Version = isset($_REQUEST['Version']) ? trim($_REQUEST['Version']) : '1.0';
		$LanguageID = get_language_id();
		$LanguageMark = get_language_mark();
		$JsonpCallback = isset($_REQUEST['JsonpCallback']) ? trim($_REQUEST['JsonpCallback']) : 'jsonpReturn';
		$HasConfig = isset($_REQUEST['HasConfig']) ? intval($_REQUEST['HasConfig']) : 0;
		$Sign = isset($_REQUEST['Sign']) ? trim($_REQUEST['Sign']) : ''; //签名字符串
		//客户端类型（1：APP，2：小程序，3：站内同域调用，无需验证签名，5：多端小程序）
		$ClientType = isset($_REQUEST['ClientType']) ? intval($_REQUEST['ClientType']) : 1;
		
		//定义常量
		define('API_FORMAT', $Format);
		define('API_VERSION', $Version);
		define('API_LANGUAGE_ID', $LanguageID);
		define('API_LANGUAGE_MARK', $LanguageMark);
		define('API_JSONP_CALLBACK', $JsonpCallback);
		define('API_HAS_CONFIG', $HasConfig);
		define('API_SIGN', $Sign);
		define('CLIENT_TYPE', $ClientType);
		
		//其他常量参数
		define('MAX_LOGIN_FAIL_COUNT', 10);  //最大登陆失败次数
	}
	
	/**
	 * 获取系统配置（不进行签名验证）
	 */
	public function GetConfig(){
		$data['Data'] = $this->GetConfigData();
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}
	
	private function GetConfigData(){
		$config = &$GLOBALS['Config'];
		if(CLIENT_TYPE==3){
			$AppLogo = $config['APP_LOGO'];
			$AppApkUrl = $config['APP_APK_URL'];
			$AppIpaUrl = $config['APP_IPA_URL'];
			$AppApkQrcode = $config['APP_APK_QRCODE'];
			$AppIpaQrcode = $config['APP_IPA_QRCODE'];
			$AppAbout = $config['APP_ABOUT'];
		}else{
			$domain = get_current_url();
			$AppLogo = app_to_fullurl($config['APP_LOGO'], $domain);
			$AppApkUrl = app_to_fullurl($config['APP_APK_URL'], $domain);
			$AppIpaUrl = app_to_fullurl($config['APP_IPA_URL'], $domain);
			$AppApkQrcode = app_to_fullurl($config['APP_APK_QRCODE'], $domain);
			$AppIpaQrcode = app_to_fullurl($config['APP_IPA_QRCODE'], $domain);
			$AppAbout = yd_relative_to_absolute($config['APP_ABOUT'], $domain);
		}
		$LanguageID = API_LANGUAGE_ID;
		$LanguageMark = API_LANGUAGE_MARK;
		$EnableMultiLauguage = intval(C('LANG_AUTO_DETECT')); //是否启用多语言
		$gcj = yd_bd2gcj( array('x'=>$config['Latitude'], 'y'=>$config['Longitude']) );
		$PointExchangeRate = intval($GLOBALS['Config']['POINT_EXCHANGE_RATE']);
		$MoneyExchangeRate = ($PointExchangeRate!=0) ? 1/$PointExchangeRate : 0;
		$data = array(
				'WebName'=>$config['WEB_NAME'],  //网站名称
				'WebIcp'=>$config['WEB_ICP'],
				
				'Company'=>$config['COMPANY'],
				'Telephone'=>$config['TELEPHONE'],
				'Mobile'=>$config['MOBILE'],
				'Fax'=>$config['FAX'],
				'Email'=>$config['EMAIL'],
				'Address'=>$config['ADDRESS'],
				'Contact'=>$config['CONTACT'],
				
				'BaiduLongitude'=>$config['Longitude'], //百度坐标经度
				'BaiduLatitude'=>$config['Latitude'], //百度坐标纬度
				'GcjLongitude'=>$gcj['y'], //火星坐标经度
				'GcjLatitude'=>$gcj['x'],    //火星坐标纬度
				
				'EnableMultiLauguage'=>$EnableMultiLauguage,
				'LanguageMark'=>$LanguageMark,
				'LanguageID'=>$LanguageID,
				'MemberLoginVerifyCode'=>$config['MEMBER_LOGIN_VERIFYCODE'],
				'MemberRegVerifyCode'=>$config['MEMBER_REG_VERIFYCODE'],
				'MemberRegCheck'=>$config['MEMBER_REG_CHECK'],
				'CurrencySymbol'=>$config['CURRENCY_SYMBOL'],
				'FreeShippingThreshold'=>intval($config['FREE_SHIPPING_THRESHOLD']),
				
				'PointExchangeRate'=>$PointExchangeRate,
				'MoneyExchangeRate'=>$MoneyExchangeRate,
				'ShowTechnicalSupport'=>1,
		);
		if(CLIENT_TYPE==1 || CLIENT_TYPE==3){ //APP和本地应用
			$data['AppAbout']=$AppAbout;
			$data['AppLogo']=$AppLogo;
			$data['AppThemeColor']=$config['APP_THEME_COLOR'];
			$data['AppVersion']=$config['APP_VERSION'];
			$data['AppVersionDescription']=nl2br($config['APP_VERSION_DESCRIPTION']);
			
			$data['AppShareTitle']=$config['APP_SHARE_TITLE'];
			$data['AppShareDescription']=$config['APP_SHARE_DESCRIPTION'];
			$data['AppApkShareUrl']=$config['APP_APK_SHARE_URL'];
			
			$data['AppApkSize']=$config['APP_APK_SIZE'];
			$data['AppApkUrl']=$AppApkUrl;
			$data['AppApkQrcode']=$AppApkQrcode;
			$data['AppIpaUrl']=$AppIpaUrl;
			$data['AppIpaQrcode']=$AppIpaQrcode;
			
			$data['AppTab2ChannelID']=$config['APP_TAB2_CHANNELID'];
			$data['AppTab2Icon']=$config['APP_TAB2_ICON'];
			$data['AppTab2Title']=$config['APP_TAB2_TITLE'];
			$data['AppTab2IconActive']=$config['APP_TAB2_ICON_ACTIVE'];
			
			$data['AppTab3ChannelID']=$config['APP_TAB3_CHANNELID'];
			$data['AppTab3Icon']=$config['APP_TAB3_ICON'];
			$data['AppTab3Title']=$config['APP_TAB3_TITLE'];
			$data['AppTab3IconActive']=$config['APP_TAB3_ICON_ACTIVE'];
		}else if(CLIENT_TYPE==2){ //小程序
			$data['AppAbout']=$AppAbout;
			$data['WxCustomerService'] = $config['WX_CUSTOMER_SERVICE'];
			
			$data['XcxName']=$config['XCX_NAME'];
			$data['XcxThemeColor']=$config['XCX_THEME_COLOR'];
			
			$data['XcxTabColor']=$config['XCX_TAB_COLOR'];
			$data['XcxTabSelectedColor']=$config['XCX_TAB_SELECTED_COLOR'];
			$data['XcxTabBackgroundColor']=$config['XCX_TAB_BACKGROUND_COLOR'];
			$data['XcxTabBorderStyle']=$config['XCX_TAB_BORDER_STYLE'];
			
			$data['XcxTab1ChannelID']=$config['XCX_TAB1_CHANNELID'];
			$data['XcxTab1Icon']=$config['XCX_TAB1_ICON'];
			$data['XcxTab1Title']=$config['XCX_TAB1_TITLE'];
			$data['XcxTab1IconActive']=$config['XCX_TAB1_ICON_ACTIVE'];
			
			$data['XcxTab2ChannelID']=$config['XCX_TAB2_CHANNELID'];
			$data['XcxTab2Icon']=$config['XCX_TAB2_ICON'];
			$data['XcxTab2Title']=$config['XCX_TAB2_TITLE'];
			$data['XcxTab2IconActive']=$config['XCX_TAB2_ICON_ACTIVE'];
			
			$data['XcxTab3ChannelID']=$config['XCX_TAB3_CHANNELID'];
			$data['XcxTab3Icon']=$config['XCX_TAB3_ICON'];
			$data['XcxTab3Title']=$config['XCX_TAB3_TITLE'];
			$data['XcxTab3IconActive']=$config['XCX_TAB3_ICON_ACTIVE'];
			
			$data['XcxTab4ChannelID']=$config['XCX_TAB4_CHANNELID'];
			$data['XcxTab4Icon']=$config['XCX_TAB4_ICON'];
			$data['XcxTab4Title']=$config['XCX_TAB4_TITLE'];
			$data['XcxTab4IconActive']=$config['XCX_TAB4_ICON_ACTIVE'];
			
			//获取小程序自定义配置数据
			$filename= APP_DATA_PATH."xcx.php";
			if( file_exists($filename) ){
				$obj = include $filename;
				$lang = get_language_mark();
				$obj = isset($obj[$LanguageMark]) ? $obj[$LanguageMark] : false;
				foreach($obj as $v){
					$data[ $v['name'] ] = $v['value'];
				}
			}
		}
		return $data;
	}
	
	/**
	 * 获取频道数据
	 */
	public function GetChannel(){
		$ChannelID = intval($_REQUEST['ChannelID']);
		$Depth = isset($_REQUEST['Depth']) ? intval($_REQUEST['Depth']) : 1;
		$ShowHidden = isset($_REQUEST['ShowHidden']) ? intval($_REQUEST['ShowHidden']) : 0;
		$IsShow = ($ShowHidden == 0) ? 1 : -1;
		$IDList= isset($_REQUEST['IDList']) ? $_REQUEST['IDList'] : '';
		$ChannelModelID = $_REQUEST['ChannelModelID'];
        $Field = $this->_filterTableField($_REQUEST['Field']);
		$data['Data'] = get_navigation($ChannelID, $Depth, $IDList, $IsShow, $ChannelModelID, API_LANGUAGE_ID, $Field);
		$this->_getChannelAlbum($data['Data']);  //获取相册数据
		app_relative_to_absolute($data['Data'], 'ChannelPicture,ChannelContent,ChannelIcon,f1,f2,f3');
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}
	
	public function _getChannelAlbum(&$data){
		//必须判断是否存在InfoAlbum字段
		if(empty($data) || !isset($data[0]['ChannelAlbum'])) return false;
		$n = is_array($data) ? count($data) : 0;
		for($i=0; $i<$n; $i++){
			$InfoAlbum = $data[$i]["ChannelAlbum"];
			if( !empty($InfoAlbum) ){
				$result = yd_split($InfoAlbum, array('AlbumTitle','AlbumPicture','AlbumDescription'));
				if(is_array($result)){
					app_relative_to_absolute($result, 'AlbumPicture');
					$data[$i]['ChannelAlbum'] = $result;
				}
			}
		}
	}
	
	/**
	 * 查找频道
	 */
	public function FindChannel(){
		$ChannelID = $_REQUEST['ChannelID'];
		$m = D('Admin/Channel');
		if(is_numeric($ChannelID)){
			$where['ChannelID'] = $ChannelID;
		}else{
            $ChannelID = YdInput::checkHtmlName($ChannelID);
			$where['Html'] = $ChannelID;
		}
		$where['IsEnable'] = 1;
		$data = $m->where($where)->find();
		if( empty( $data ) ) { //若频道不存在，则转向404页面
			$this->ApiReturn($data, '', 1, API_FORMAT);
		}
		$Parent = $data['Parent'];
		 
		//计算特殊字段值start==================================================
		//是否有阅读权限
		$ReadLevel = $data['ReadLevel'];
		$ReadLevel= ( !empty($ReadLevel) || $Parent==0 ) ? $ReadLevel : get_read_level( $Parent );
		$data['HasReadLevel'] = has_read_level( $ReadLevel ) ? 1 : 0;
		 
		//详细内容特殊标签解析
		$data['ChannelContent'] = ParseTag( $data['ChannelContent'] );
		$data['ChannelContent'] = app_strip_tags($data['ChannelContent']);
		tag('channel_content', $data['ChannelContent']);
		 
		//搜索引擎优化字段
		if(CLIENT_TYPE == 3){ //只有同域才调用搜索引擎优化
			if( empty($data['Title']) ) {
				$data['Title'] = ($Parent==0) ? $GLOBALS['Config']['TITLE'] : get_title( $Parent );
			}
			$data['Title'] = YdInput::checkSeoString( $data['Title'] );
			 
			if( empty($data['Keywords']) ){
				$data['Keywords'] = ($Parent==0) ? $GLOBALS['Config']['KEYWORDS'] : get_keywords( $Parent );
			}
			$data['Keywords'] = YdInput::checkSeoString( $data['Keywords'] );
			 
			if( empty($data['Description']) ){
				$data['Description'] = ($Parent==0) ? $GLOBALS['Config']['DESCRIPTION'] : get_description( $Parent );
			}
			$data['Description'] = YdInput::checkSeoString( $data['Description'] );
		}
		 
		//其它
		$data['HasParent'] = ($Parent > 0 ) ? 1 : 0 ;  //是否有父频道
		$data['TopChannelID'] = ($Parent == 0) ? $ChannelID : $m->getTopChannel( $ChannelID ); //顶级频道ID
		$data['TopHasChild'] = ( $data['HasChild'] == 1 ||  $Parent != 0 ) ? 1 : 0;
		$data['ChannelUrl'] = ChannelUrl($ChannelID, $data['Html'], $data['LinkUrl']);
		//计算特殊字段值end==================================================
		
		//内容相对路径转化为绝对路径
		$domain = get_current_url();
		if(CLIENT_TYPE != 3){
			$data['ChannelContent'] = yd_relative_to_absolute( $data['ChannelContent'] ,$domain);
			$data['f1'] = yd_relative_to_absolute( $data['f1'] ,$domain);
			$data['f2'] = yd_relative_to_absolute( $data['f2'] ,$domain);
			$data['f3'] = yd_relative_to_absolute( $data['f3'] ,$domain);

			//地址转换
			$data['ChannelUrl'] = app_to_fullurl($data['ChannelUrl'], $domain);
			$data['ChannelIcon'] = app_to_fullurl($data['ChannelIcon'], $domain);
			$data['ChannelPicture'] = app_to_fullurl($data['ChannelPicture'], $domain);
		}
		$temp[0] = &$data;
		$this->_getChannelAlbum($temp);  //获取相册数据
		
		$search = array('<pre>','</pre>','<pre class');
		$replace = array('<textarea class="brush:xml">','</textarea>','<textarea class');
		$data['ChannelContent'] = str_ireplace($search, $replace, $data['ChannelContent']);
		$this->ApiReturn(array('Data'=>$data), '', 1, API_FORMAT);
	}

    /**
     * 通过频道ID获取频道模型ID
     */
    public function FindChannelModelID(){
        $ChannelID = intval($_REQUEST['ChannelID']);
        $id = ChannelModelID($ChannelID);
        $this->ApiReturn(array('Data'=>$id), '', 1, API_FORMAT);
    }
	
	/**
	 * 检索频道
	 */
	public function SearchChannel(){
		$Keyword=trim($_REQUEST['Keyword']);  //查找关键词
        if( empty($Keyword) ){
            $this->ApiReturn(false, '', 1, API_FORMAT);
        }
		$Keyword = YdInput::checkKeyword($Keyword);
        $Field = $this->_filterTableField($_REQUEST['Field']);
        if(!empty($Field)){
            $Field = YdInput::checkTableField($Field);
        }else{
            $Field = 'ChannelName,ChannelID,Html,ChannelModelID';
        }

		$m = D('Admin/Channel');
		$where = "(ChannelName like '%{$Keyword}%' or ChannelContent like '%{$Keyword}%')";
		$where .= " and IsEnable = 1 and LanguageID=".API_LANGUAGE_ID;
		$data['Data'] = $m->where($where)->field($Field)->select();
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}
	
	/**
	 * 获取幻灯片
	 */
	public function GetBanner(){
		$BannerGroupID = isset($_REQUEST['BannerGroupID']) && is_numeric($_REQUEST['BannerGroupID'])  ? (int)$_REQUEST['BannerGroupID'] : -1;
		$data['Data'] = get_banner_list($BannerGroupID);
		app_relative_to_absolute($data['Data'], 'BannerImage,BannerThumbnail');
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}
	
	/**
	 * 获取幻灯片分组
	 */
	public function GetBannerGroup(){
		$data['Data'] = get_bannergroup();
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}
	
	/**
	 * 获取频道属性标记
	 */
	public function GetLabel(){
		$ChannelModelID = isset($_REQUEST['ChannelModelID']) && is_numeric($_REQUEST['ChannelModelID']) ? (int)$_REQUEST['ChannelModelID'] : -1;
		$data['Data'] = get_label($ChannelModelID);
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}
	
	/**
	 * 获取专题
	 */
	public function GetSpecial(){
		$ChannelID = isset($_REQUEST['ChannelID']) ? $_REQUEST['ChannelID'] : -1;
		$IdList = isset($_REQUEST['IdList']) ? $_REQUEST['IdList'] : -1;
		$data['Data'] = get_special($ChannelID, $IdList);
		app_relative_to_absolute($data['Data'], 'SpecialPicture');
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}
	
	/**
	 * 获取信息相册
	 */
	public function GetInfoAlbum(){
		$InfoID = isset($_REQUEST['InfoID']) ? $_REQUEST['InfoID'] : -1;
		$FieldName = isset($_REQUEST['FieldName']) ? $_REQUEST['FieldName'] : 'InfoAlbum';
        $FieldName = $this->_filterTableField($FieldName);
		$data['Data'] = get_infoalbum($InfoID, $FieldName);
		app_relative_to_absolute($data['Data'], 'AlbumPicture');
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}
	
	/**
	 * 获取频道相册
	 */
	public function GetChannelAlbum(){
		$ChannelID = isset($_REQUEST['ChannelID']) ? $_REQUEST['ChannelID'] : -1;
		$FieldName = isset($_REQUEST['FieldName']) ? $_REQUEST['FieldName'] : 'ChannelAlbum';
        $FieldName = $this->_filterTableField($FieldName);
		$data['Data'] = get_channelalbum($ChannelID, $FieldName);
		app_relative_to_absolute($data['Data'], 'AlbumPicture');
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}
	
	/**
	 * 获取相关文章
	 */
	public function GetInfoRelation(){
		$InfoID = isset($_REQUEST['InfoID']) ? $_REQUEST['InfoID'] : -1;
		$FieldName = isset($_REQUEST['FieldName']) ? $_REQUEST['FieldName'] : 'InfoRelation';
        $FieldName = $this->_filterTableField($FieldName);
		$data['Data'] = get_inforelation($InfoID, $FieldName);
		app_relative_to_absolute($data['Data'], 'InfoAttachment,InfoPicture,InfoContent,f1,f2,f3,f4,f5,InfoUrl');
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}
	
	/**
	 * 获取频道相关信息
	 */
	public function GetChannelRelation(){
		$ChannelID = isset($_REQUEST['ChannelID']) ? $_REQUEST['ChannelID'] : -1;
		$FieldName = isset($_REQUEST['FieldName']) ? $_REQUEST['FieldName'] : 'ChannelRelation';
        $FieldName = $this->_filterTableField($FieldName);
		$data['Data'] = get_channelrelation($ChannelID, $FieldName);
		app_relative_to_absolute($data['Data'], 'InfoAttachment,InfoPicture,InfoContent,f1,f2,f3,f4,f5');
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}
	
	/**
	 * 获取友情链接
	 */
	public function GetLink(){
		$LinkClassID = isset($_REQUEST['LinkClassID']) ? intval($_REQUEST['LinkClassID']) : -1;
		$Top = isset($_REQUEST['Top']) ? intval($_REQUEST['Top']) : -1;
		$data['Data'] = get_link($LinkClassID, $Top);
		app_relative_to_absolute($data['Data'], 'LinkLogo');
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}
	
	/**
	 * 获取友情链接分类
	 */
	public function GetLinkClass(){
		$data['Data'] = get_link_class();
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}
	
	/**
	 * 获取广告
	 */
	public function GetAd(){
		$AdGroupID = isset($_REQUEST['AdGroupID']) ? $_REQUEST['AdGroupID'] : -1;
		$data['Data'] = get_ad_list($AdGroupID);
		app_relative_to_absolute($data['Data'], 'AdContent');
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}
	
	/**
	 * 获取指定区域的下级区域，AreaID为0表示获取顶级区域
	 */
	public function GetArea(){
		$AreaID = isset($_REQUEST['AreaID']) ? $_REQUEST['AreaID'] : 0;
		$data['Data'] = get_area($AreaID);
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}
	
	/**
	 * 获取广告分组
	 */
	public function GetAdGroup(){
		$data['Data'] = get_adgroup();
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}
	
	/**
	 * 获取在线客服列表
	 */
	public function GetSupport(){
		$QqStyle = isset($_REQUEST['QqStyle']) ? $_REQUEST['QqStyle'] : '41';
		$data['Data'] = get_support($QqStyle);
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}
	
	/**
	 * 人才招聘
	 */
	public function GetJob(){
		$Top = isset($_REQUEST['Top']) ? intval($_REQUEST['Top']) : -1;
		$m = D('Admin/Job');
		if($Top > 0){ //返回前Top条
			$data['Data'] = $m->getJob(0, $Top, 1);
		}else{ //返回所有
			$data['Data'] = $m->getJob(-1, -1, 1);
		}
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}
	
	/**
	 * 获取模型
	 */
	public function GetModel(){
		$ChannelModelID = isset($_REQUEST['ChannelModelID']) ? $_REQUEST['ChannelModelID'] : '41';
		$data['Data'] = get_model($ChannelModelID);
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}
	
	/**
	 * 获取信息数据
	 */
	public function GetInfo(){
		$ChannelID = isset($_REQUEST['ChannelID']) ? $_REQUEST['ChannelID'] : 0;
		if($ChannelID==-1) $ChannelID=0;
		$SpecialID = isset($_REQUEST['SpecialID']) ? $_REQUEST['SpecialID'] : 0;
		$Top = isset($_REQUEST['Top']) ? $_REQUEST['Top'] : -1;
		$TimeFormat = isset($_REQUEST['TimeFormat']) ? $_REQUEST['TimeFormat'] : 'Y-m-d';
		$TitleLen = isset($_REQUEST['TitleLen']) ? $_REQUEST['TitleLen'] : 0;
		
		$Suffix = isset($_REQUEST['Suffix']) ? $_REQUEST['Suffix'] : '...';
		$LabelID = isset($_REQUEST['LabelID']) ? $_REQUEST['LabelID'] : '';
		$NowPage = isset($_REQUEST['NowPage']) ? intval($_REQUEST['NowPage']) : 0;
		$PageSize = isset($_REQUEST['PageSize']) ? intval($_REQUEST['PageSize']) : 0;
		$Keywords = isset($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		$OrderBy = isset($_REQUEST['OrderBy']) ? $_REQUEST['OrderBy'] : '';
		
		$MinPrice = isset($_REQUEST['MinPrice']) ? $_REQUEST['MinPrice'] : -1;
		$MaxPrice = isset($_REQUEST['MaxPrice']) ? $_REQUEST['MaxPrice'] : -1;
		$Attr = isset($_REQUEST['Attr']) ? $_REQUEST['Attr'] : '';
		$Field = $this->_filterTableField($_REQUEST['Field']);
		
		//省市区
		$ProvinceID = isset($_REQUEST['ProvinceID']) ? YdInput::checkNum( $_REQUEST['ProvinceID'] ) : -1;
		$CityID = isset($_REQUEST['CityID']) ? YdInput::checkNum( $_REQUEST['CityID'] ) : -1;
		$DistrictID = isset($_REQUEST['DistrictID']) ? YdInput::checkNum( $_REQUEST['DistrictID'] ) : -1;

		$data['Data'] = get_info($ChannelID, $SpecialID, $Top, $TimeFormat, $TitleLen, $Suffix, $LabelID, $NowPage, $Keywords, 
				$OrderBy, $MinPrice, $MaxPrice, $Attr, API_LANGUAGE_ID, $Field, $PageSize,$ProvinceID,$CityID,$DistrictID);
		$this->_getInfoAlbum($data['Data']);
		app_relative_to_absolute($data['Data'], 'InfoAttachment,InfoPicture,InfoContent,f1,f2,f3,f4,f5,InfoUrl');

		//自动提取前100个字，为InfoSContent
		
		//分页才运行
		if( !empty($data['Data']) && is_array($data['Data']) && $NowPage > 0){
			$data['Total'] = $data['Data'][0]['Count'];  //信息总条数
			$data['PageSize'] = $PageSize;  //分页大小
			$data['PageCount'] = ceil($data['Total']/$PageSize); //总页数
			$data['NowPage'] = $NowPage;
			$data['HasNextPage'] = ( $NowPage >= $data['PageCount']) ? 0 : 1;
		}else{
			$data['HasNextPage'] = 0;
		}
		//获取模板数据
		if(!empty($_REQUEST['HasPageData'])){
		    $data['PageData'] = $this->getPageData(false);
        }
		$this->ApiReturn($data, '', 1, API_FORMAT);
	}

	private function _filterTableField($field){
	    if(empty($field)) return '';
	    //不允许有空格和-
        if(!preg_match("/^[a-zA-Z0-9_,\.]+$/i", $field)){
            return '';
        }
        $list = array('0x');
        foreach($list as $v){
            if(false !== stripos($field, $v)){
                return '';
            }
        }
        return $field;
    }
	
	public function _getInfoAlbum(&$data){
		//必须判断是否存在InfoAlbum字段
		if(empty($data) || !isset($data[0]['InfoAlbum'])) return false;
		$n = is_array($data) ? count($data) : 0;
		for($i=0; $i<$n; $i++){
			$InfoAlbum = $data[$i]["InfoAlbum"];
			if( !empty($InfoAlbum) ){
				$result = yd_split($InfoAlbum, array('AlbumTitle','AlbumPicture','AlbumDescription'));
				if(is_array($result)){
					app_relative_to_absolute($result, 'AlbumPicture');
					$data[$i]['InfoAlbum'] = $result;
				}
			}
		}
	}
    
	/**
	 * 查找信息
	 */
	public function FindInfo(){
		$m = D('Admin/Info');
		$data = $m->findinfo($_REQUEST['id']); //可以是id或文件名
		if(empty($data)){
			$this->ApiReturn(false, '', 0, API_FORMAT);
		}
		$id = $data['InfoID'];
		//判断频道是否禁用，频道禁用后，不能查看频道的信息
		$ChannelID = $data['ChannelID'];
		$mc = D('Admin/Channel');
		$channel = $mc->findField($ChannelID,'Parent,HasChild,ReadLevel,ChannelModelID,ChannelName,IsEnable,ChannelSName,Html,LinkUrl,ReadTemplate,Title,Keywords,Description');
		if( $channel['IsEnable'] == 0 ){
			$this->ApiReturn(false, '', 0, API_FORMAT);
		}
		
		//计算特殊字段值start=================================================
		//是否有阅读权限
		if( !empty($data['ReadLevel']) ){
			$ReadLevel = $data['ReadLevel'];
		}else{
			$ReadLevel = ( !empty($channel['ReadLevel']) || $channel['Parent'] == 0) ? $channel['ReadLevel'] : get_read_level( $channel['Parent'] );
		}
		$data['HasReadLevel'] = has_read_level( $ReadLevel ) ? 1 : 0;
		 
		//搜索引擎优化
		if(CLIENT_TYPE == 3){ //只有同域才有搜索引擎优化
			if( empty($data['Title']) ) {
				$data['Title'] = !empty($channel['Title']) ? $channel['Title'] : get_title( $channel['Parent'] );
			}
			$data['Title'] = YdInput::checkSeoString($data['Title'] );
			 
			if( empty($data['Keywords']) ) {
				$data['Keywords'] = !empty($channel['Keywords']) ? $channel['Keywords'] : get_keywords( $channel['Parent'] );
			}
			$data['Keywords'] = YdInput::checkSeoString($data['Keywords'] );
			
			if( !empty($data['Description']) ){
				$data['Description'] = YdInput::checkSeoString($data['Description'] );
			}else if( !empty($data['InfoSContent'])  ){
				$data['Description'] = YdInput::checkSeoString($data['InfoSContent']);
			}else if( !empty($data['InfoContent'])  ){
				$data['Description'] = YdInput::checkSeoString($data['InfoContent'] );
				$data['Description'] = Left($data['Description'], 120);
			}
		}
		$m->IncHit($id); //文章点击次数加1
		$data['InfoHit'] = $data['InfoHit']+1;
		
		$data['ChannelModelID'] = $channel['ChannelModelID'];
		$data['ChannelName'] = $channel['ChannelName'];
		$data['ChannelUrl'] = ChannelUrl($ChannelID, $channel['Html'], $channel['LinkUrl']);
		$data['ChannelSName'] = $channel['ChannelSName'];
		
		if($data['HasReadLevel'] == 1){
			$data['InfoContent'] = ParseTag( $data['InfoContent'] );
		}else{ //如果没有权限就提示
			$data['InfoContent'] = L('ReadLevelTip');
		}
		$data['InfoContent'] = app_strip_tags($data['InfoContent']);
		
		//信息
		$data['InfoUrl'] = InfoUrl($id, $data['Html'], $data['LinkUrl'], false, $data['ChannelID']);
		$data['Parent'] = $channel['Parent'];
		$data['HasChild'] = $channel['HasChild'];
		$data['TopChannelID'] = ($channel['Parent']==0) ? $ChannelID : $mc->getTopChannel( $ChannelID );
		$data['TopHasChild'] = ( $channel['HasChild'] == 1 ||  $channel['Parent'] != 0 ) ? 1 : 0;
		$data['DiscountPrice'] = $data['InfoPrice'] * $GLOBALS['DiscountRate'];
		$data['InfoFriendTime'] = yd_friend_date(strtotime( $data['InfoTime'] ));
		$data['InfoPrice'] = yd_to_money($data['InfoPrice']);
		$data['ExchangePrice'] = ExchangePrice($id, $data['ExchangePoint'], $data['DiscountPrice']);
		
		//内容相对路径转化为绝对路径
		$domain = get_current_url();
		$data['VideoPlayer'] = app_video_player($data['InfoAttachment'], $domain);
		if(CLIENT_TYPE != 3){
			$wapDomain = get_wx_url();
			$data['InfoUrl'] = $wapDomain.$data['InfoUrl'];
			$data['InfoContent'] = yd_relative_to_absolute( $data['InfoContent'] ,$domain);
			$data['f1'] = yd_relative_to_absolute( $data['f1'] ,$domain);
			$data['f2'] = yd_relative_to_absolute( $data['f2'] ,$domain);
			$data['f3'] = yd_relative_to_absolute( $data['f3'] ,$domain);
			$data['f4'] = yd_relative_to_absolute( $data['f4'] ,$domain);
			$data['f5'] = yd_relative_to_absolute( $data['f5'] ,$domain);
			//地址转换
			$data['ChannelUrl'] = app_to_fullurl($data['ChannelUrl'], $domain);
			$data['InfoUrl'] = app_to_fullurl($data['InfoUrl'], $domain);
			$data['InfoPicture'] = app_to_fullurl($data['InfoPicture'], $domain);
			$data['InfoAttachment'] = app_to_fullurl($data['InfoAttachment'], $domain);
		}
		$temp[0] = &$data;
		$this->_getInfoAlbum($temp); //获取信息的相册数据
		
		//获取是否被收藏，必须出入参数true，否则没有登陆会提示登陆超时
		$MemberID = $this->checkToken(true); 
		if( $MemberID > 0 ){  //如果是登陆状态
			$mf = D('Admin/Favorite');
			$data['IsFavorite'] = $mf->isAdd($id, $MemberID) ? 1 : 0;
			$mc = D('Admin/Cart');
			$data['TotalItemCount'] = $mc->getItemCount($MemberID);
		}else{
			$data['IsFavorite'] = 0;
			$data['TotalItemCount'] = 0;
		}
		unset($data['MemberID']); //禁止使用MemberID
        $result = array();
        $result['Data'] = $data;
        //获取模板数据
        if(!empty($_REQUEST['HasPageData'])){
            $result['PageData'] = $this->getPageData(false);
        }
		$this->ApiReturn($result, '', 1, API_FORMAT);
	}
	
	/**
	 * 微信登陆，需要微信端传入code凭据（调用wx.login方法）
	 */
	public function WxLogin(){
		//只有小程序才能使用此接口
		if( CLIENT_TYPE !== 2 ) exit();
		$this->checkSign();
		$Code = trim($_POST['code']);  //凭证
		if($Code == ''){
			$this->ApiReturn(null, L('CodeRequired'), 0, API_FORMAT);
		}
		
		//小程序秘钥设置
		$XcsAppID = $GLOBALS['Config']['XCX_APP_ID'];
		$XcxAppSecret = $GLOBALS['Config']['XCX_APP_SECRET'];
		if( empty($XcsAppID) || empty($XcxAppSecret) ){
			$this->ApiReturn(null, L('AppIDAppSecretNotConfig'), 0, API_FORMAT);
		}
		
		//通过code获取openid
		$apiUrl = 'https://api.weixin.qq.com/sns/jscode2session';
		$params = array(
				'appid'     => $XcsAppID,
				'secret'  => $XcxAppSecret,
				'js_code' => $Code,
				'grant_type'=>'authorization_code',
		);
		$result = yd_curl_get($apiUrl, $params, 30);
		$result = json_decode($result, true);
		//$result = array('openid'=>'openid123456789');
		//正常返回的JSON数据包{"openid": "OPENID","session_key": "SESSIONKEY","unionid": "UNIONID"}
		//错误时返回JSON数据包(示例为Code无效){"errcode": 40029,"errmsg": "invalid code"}
		if( isset($result["errcode"]) ){
			$error = $result["errmsg"].$result["errcode"];
			$this->ApiReturn(null, $error, 0, API_FORMAT);
		}else{
			//偶尔发现客户的小程序获取的openid为空，所以加个判断
			if(empty($result["openid"])){
				$this->ApiReturn(null, 'Openid is empty', 0, API_FORMAT);
			}
			$m = D('Admin/Member');
			$data = $m->findMemberByOpenID( $result["openid"] );
			if( !empty($data) ){  //表示已经绑定，直接登录即可
				if( $data['IsLock'] == 1 || $data['IsCheck'] == 0 ){
					$this->ApiReturn(null, L('AccountUncheckLock'), 0, API_FORMAT);
				}else{
					$m->UpdateLogin($data['MemberID']);
					$res['Data'] = $this->getMemberData( $data );  //同时生成令牌
					$this->ApiReturn($res, L('LoginSuccess'), 1, API_FORMAT);
				}
			}else{ //表示没有绑定，需要进行绑定，微信账号会直接传入
				$wx['MemberName'] = $_POST['nickName'];
				//微信：性别 0：未知、1：男、2：女，我们系统（1：女，0：男）
				$wx['MemberGender'] = ($_POST['gender']==2) ? 1 : 0;  
				$wx['MemberAvatar'] = $_POST['avatarUrl'];
				$wx['RegisterTime'] = date('Y-m-d H:i:s');
				$wx['IsCheck'] = 1;
				$wx['OpenID'] = $result["openid"];
				//自动成为分销商=======================================
				$wx['InviterID'] = GetInviterID();
				$DistributeEnable = $GLOBALS['Config']['DistributeEnable'];
				$DistributeRequirement = $GLOBALS['Config']['DistributeRequirement'];
				if(1==$DistributeEnable && 1==$DistributeRequirement){
					$md = D('Admin/DistributorLevel');
					$DistributorLevelID = $md->getLowestDistributorLevelID();
					$wx['IsDistributor'] = 1;
					$wx['DistributorLevelID'] = $DistributorLevelID;
					$wx['DistributorTime'] = $wx['RegisterTime'];
					$wx['InviteCode'] = MakeInviteCode();
				}else{
					$wx['IsDistributor'] = 0;
					$wx['DistributorLevelID'] = 0;
					$wx['InviteCode'] = '';
				}
				//===============================================
				
				$MemberID = $m->add($wx);
				if($MemberID>0){
					$data = $m->findMember($MemberID);
					$res['Data'] = $this->getMemberData( $data ); //同时生成令牌
					$this->ApiReturn($res, L('LoginSuccess'), 1, API_FORMAT);
				}else{
					$this->ApiReturn(null, L('AccountBindFail'), 0, API_FORMAT);
				}
			}
		}
	}
	
	/**
	 * 登陆
	 */
	public function Login(){
		$this->checkSign();
		$MemberName = trim($_POST['MemberName']);
		$MemberPassword = trim($_POST['MemberPassword']);
		if($MemberName == ''){
			$this->ApiReturn(null, L('UserNameRequired'), 0, API_FORMAT);
		}
		if($MemberPassword == ''){
			$this->ApiReturn(null, L('PasswordRequired'), 0, API_FORMAT);
		}
		
		if(CLIENT_TYPE == 3 && $GLOBALS['Config']['MEMBER_LOGIN_VERIFYCODE'] == 1){
			$MemberCode = trim($_POST['MemberCode']);
			if($MemberCode == ''){
				$this->ApiReturn(null, L('VerifyCodeRequired'), 0, API_FORMAT);
			}
			
			$MemberCode2 = session('membercode');
			if(md5($MemberCode) != $MemberCode2){
				session('membercode', rand(1000, 9999) );
				$this->ApiReturn(null, L('VerifyCodeError'), 0, API_FORMAT);
			}
		}
		
		$m = D('Admin/Member');
		$LoginFailCount = $m->getLoginFailCount($MemberName);
		if($LoginFailCount > MAX_LOGIN_FAIL_COUNT){
			$this->ApiReturn(null, L('PasswordErrorMaxCount'), 0, API_FORMAT);
		}
		
		//0: 用户名或密码错误，1：用户被锁定，2:用户组不存在，数组：认证成功
		$result = $m->checkLogin($MemberName, $MemberPassword );
		if( $result == 0 ){
			$this->ApiReturn(null, L('UserNamePasswordError'), 0, API_FORMAT);
		}else if($result == 1){
			$this->ApiReturn(null, L('AccountLock'), 0, API_FORMAT);
		}else if($result == 2){
			$this->ApiReturn(null, L('AdminGroupNotExist'), 0, API_FORMAT);
		}else if($result == 3){
			$this->ApiReturn(null, L('AccountUnchecked'), 0, API_FORMAT);
		}else if( is_array($result) ){ //认证成功
			$m->UpdateLogin($result['MemberID']);
			if(CLIENT_TYPE == 3){
				session('MemberID', $result['MemberID']);
				session('MemberName', $MemberName);
				session('MemberGroupID', $result['MemberGroupID']);
				session('MemberGroupName', $result['MemberGroupName']);
				session('DiscountRate', is_numeric($result['DiscountRate']) ? $result['DiscountRate'] : 1);
			}
			$data['Data'] = $this->getMemberData( $result );
			$this->ApiReturn($data, L('LoginSuccess'), 1, API_FORMAT);
		}
	}
	
	/**
	 * 自动登录
	 */
	public function AutoLogin(){
		$this->checkSign();
		$MemberName = trim($_POST['MemberName']);
		$MemberPassword = trim($_POST['MemberPassword']);
		if($MemberName == '' || $MemberPassword == ''){
			$this->ApiReturn(null, '', 0, API_FORMAT);
		}
		$m = D('Admin/Member');
		$LoginFailCount = $m->getLoginFailCount($MemberName);
		if($LoginFailCount > MAX_LOGIN_FAIL_COUNT){
			$this->ApiReturn(null, L('PasswordErrorMaxCount'), 0, API_FORMAT);
		}
		
		//0: 用户名或密码错误，1：用户被锁定，2:用户组不存在，数组：认证成功
		$result = $m->checkLogin($MemberName, $MemberPassword);
		if( is_array($result) ){ //认证成功
			$m->UpdateLogin($result['MemberID']);
			$data['Data'] = $this->getMemberData( $result );
			$this->ApiReturn($data, '', 1, API_FORMAT);
		}
		$this->ApiReturn(null, '', 0, API_FORMAT);
	}
	
	/**
	 * 退出登陆
	 */
	public function LoginOut(){
		if(CLIENT_TYPE == 3){
			session("MemberID", null);
			session("MemberName", null);
			session("MemberGroupID", null);
			session("MemberGroupName", null);
			session('DiscountRate',null);
			session('IsAdmin', null);
		}else{
			$this->checkSign();
			$m = D('Admin/Token');
			$m->deleteToken($_REQUEST['Token']);
		}
		$this->ApiReturn(null, '', 1, API_FORMAT);
	}
	
	/**
	 * 登陆成功，生成登陆令牌，返回指定会员数据
	 * @param array $result 会员数据
	 */
	private function getMemberData($result){
		//设置默认值，否则在小程序或app中会显示null
		$DiscountRate = !empty($result['DiscountRate']) ? $result['DiscountRate'] : 1;
		$MemberAvatar = ($result['MemberAvatar'] != 'null') ? app_to_fullurl($result['MemberAvatar']) : '';
		$MemberMobile = ($result['MemberMobile'] != 'null') ? $result['MemberMobile'] : '';
		$MemberEmail = ($result['MemberEmail'] != 'null') ? $result['MemberEmail'] : '';
		$MemberQQ = ($result['MemberQQ'] != 'null') ? $result['MemberQQ'] : '';
		$MemberTelephone = ($result['MemberTelephone'] != 'null') ? $result['MemberTelephone'] : '';
		$MemberName = ($result['MemberName'] != 'null') ? $result['MemberName'] : '';
		$MemberID = intval($result['MemberID']);
		//会员是否开启了分销功能
		$distribute_enable = plugin_distribute_enable();
		if($distribute_enable==1 && 1==$result['IsDistributor']){
			$DistributeEnable=1;
			$InviteCode = $result['InviteCode'];
		}else{
			$DistributeEnable = 0;
			$InviteCode = '';
		}
		$member =array(
			'MemberID'=>$MemberID,
			'MemberName'=>$MemberName,
			'MemberGender'=>$result['MemberGender'],
			'MemberTelephone'=>$MemberTelephone,
			'MemberEmail'=>$MemberEmail,
			'MemberQQ'=>$MemberQQ,
			
			'RegisterTime'=>$result['RegisterTime'],
			'RegisterIP'=>$result['RegisterIP'],
			
			'MemberMobile'=>$MemberMobile,
			'MemberGroupID'=>$result['MemberGroupID'],
			'MemberGroupName'=>$result['MemberGroupName'],
			'MemberAvatar'=>$MemberAvatar,
			'DiscountRate'=>$DiscountRate,
			'DistributeEnable'=>$DistributeEnable,
			'InviteCode'=>$InviteCode,
		);
		if(CLIENT_TYPE != 3){ //本地调用不生成Token
			$m = D('Admin/Token');
			$m->where("MemberID={$MemberID}" )->delete(); //先删除之前的Token
			$member['Token'] = $m->createToken($MemberID);
		}
		return $member;
	}
	
	/**
	 * 注册
	 */
	public function Reg(){
		$this->checkSign();
		$_REQUEST = YdInput::checkReg($_POST, array('MemberPassword','MemberPassword1') ); //防止xss注入
		
		$ip = get_client_ip();
		if(!app_can_reg($ip)){
			$this->ApiReturn(null, L('RegFail'), 0, API_FORMAT);
		}
		
		//手机号码
		$MemberMobile = trim($_POST['MemberMobile']);
		if( $MemberMobile == '' ){
			$this->ApiReturn(null, L('MobileRequired'), 0, API_FORMAT);
		}
		
		//检查手机号码是否已经被注册
		$m = D('Admin/Member');
		if( $m->hasMobile($MemberMobile) ){
			$this->ApiReturn(null, L('MemberMobileExist'), 0, API_FORMAT);
		}
		
		//验证密码=====================================
		$MemberPassword = trim($_POST['MemberPassword']);
		if( $MemberPassword == '' ){
			$this->ApiReturn(null, L('PasswordRequired'), 0, API_FORMAT);
		}
		
		$MemberPassword1 = trim($_POST['MemberPassword1']);
		if( $MemberPassword1 == '' ){
			$this->ApiReturn(null, L('ConfirmPasswordRequired'), 0, API_FORMAT);
		}
		
		if( $MemberPassword != $MemberPassword1 ){
			$this->ApiReturn(null, L('PasswordUnmatch'), 0, API_FORMAT);
		}
		
		if(CLIENT_TYPE == 3 && $GLOBALS['Config']['MEMBER_REG_VERIFYCODE'] == 1){
			$MemberCode = trim($_POST['MemberCode']);
			if($MemberCode == ''){
				$this->ApiReturn(null, L('VerifyCodeRequired'), 0, API_FORMAT);
			}
				
			$MemberCode2 = session('membercode');
			if(md5($MemberCode) != $MemberCode2){
				session('membercode', rand(1000, 9999) );
				$this->ApiReturn(null, L('VerifyCodeError'), 0, API_FORMAT);
			}
		}
		//============================================
		
		$data['MemberMobile'] = $MemberMobile;
		$data['MemberPassword'] = yd_password_hash($MemberPassword);
		$data['RegisterTime'] = date('Y-m-d H:i:s');
		$data['RegisterIP'] = $ip;
		$data['IsCheck'] = 1;
		//自动成为分销商========================================
		$data['InviterID'] = GetInviterID();
		$DistributeEnable = $GLOBALS['Config']['DistributeEnable'];
		$DistributeRequirement = $GLOBALS['Config']['DistributeRequirement'];
		if(1==$DistributeEnable && 1==$DistributeRequirement){
			$md = D('Admin/DistributorLevel');
			$DistributorLevelID = $md->getLowestDistributorLevelID();
			$data['IsDistributor'] = 1;
			$data['DistributorLevelID'] = $DistributorLevelID;
			$data['DistributorTime'] = $data['RegisterTime'];
			$data['InviteCode'] = MakeInviteCode();
		}else{
			$data['IsDistributor'] = 0;
			$data['DistributorLevelID'] = 0;
			$data['InviteCode'] = '';
		}
		//==================================================
		$MemberID = $m->add($data);
		if( $MemberID ){
			$result = $m->field("MemberID,MemberName,MemberMobile,MemberGroupID,MemberAvatar")->find($MemberID);
			$mg = D('Admin/MemberGroup');
			$group = $mg->field("MemberGroupName,DiscountRate")->find($result['MemberGroupID']);
			$reg['Data']=array(
					'MemberID'=>$result['MemberID'],
					'MemberName'=>$result['MemberName'],
					'MemberMobile'=>$result['MemberMobile'],
					'MemberGroupID'=>$result['MemberGroupID'],
					'MemberGroupName'=>$group['MemberGroupName'],
					'MemberAvatar'=>app_to_fullurl($result['MemberAvatar']),
					'DiscountRate'=>$group['DiscountRate'],
				);
				$this->ApiReturn($reg, L('RegSuccessNoCheck'), 1, API_FORMAT);
		}else{
			$this->ApiReturn(null, L('RegFail'), 0, API_FORMAT);
		}
	}
	
	/**
	 * 找回密码
	 */
	public function Forget(){
		$this->checkSign();
		header("Content-Type:text/html; charset=utf-8");
		$Step = trim( $_POST['Step'] );  //当前步骤
		if( $Step == 1 ){   //第一步：验证用户是否存在
			$MemberName = trim( $_POST['MemberName'] ); //可以是用户名、电子邮件、手机号码
			if( $MemberName == ''){
				$this->ApiReturn(null, L('UserNameRequired'), 0, API_FORMAT);
			}
			
			//==验证码 开始
			if(CLIENT_TYPE == 3 && $GLOBALS['Config']['MEMBER_LOGIN_VERIFYCODE'] == 1){
				$MemberCode = trim($_POST['MemberCode']);
				if($MemberCode == ''){
					$this->ApiReturn(null, L('VerifyCodeRequired'), 0, API_FORMAT);
				}
				
				$MemberCode2 = session('membercode');
				if(md5($MemberCode) != $MemberCode2){
					session('membercode', rand(1000, 9999) );
					$this->ApiReturn(null, L('VerifyCodeError'), 0, API_FORMAT);
				}
			}
			//==验证码 结束
		
			$m = D('Admin/Member');
			$data = $m->getFindPwdData($MemberName);
			if( empty($data)){
				$this->ApiReturn(null, L('UserNotExist'), 0, API_FORMAT);
			}else{
				$result['Data']['SmsEnable'] = $GLOBALS['Config']['SMS_ACCOUNT'] ? 1 : 0;
				$result['Data'] = $data;
				$this->ApiReturn($result, 'success', 1, API_FORMAT);
			}
		}else if( $Step == 2 ){   //第二部：密码重置
			//先检查密码
			$MemberPassword = trim($_POST['MemberPassword']);
			if( $MemberPassword == '' ){
				$this->ApiReturn(null, L('PasswordRequired'), 0, API_FORMAT);
			}
			$MemberPassword1 = trim($_POST['MemberPassword1']);
			if( $MemberPassword1 == '' ){
				$this->ApiReturn(null, L('ConfirmPasswordRequired'), 0, API_FORMAT);
			}
			if( $MemberPassword != $MemberPassword1 ){
				$this->ApiReturn(null, L('PasswordUnmatch'), 0, API_FORMAT);
			}
			
			$m = D('Admin/Member');
			//需要回答密保问题，这里可以直接使用$_POST['MemberID']
			$where['MemberID'] = intval( $_POST['MemberID'] );
			if( $_POST['FindPwdWay'] == 2 ){ //1:密码问题，2：手机
				//手机找回密码，暂未实现
				//检查短信验证码是否有效
				/*
				$SmsCode = trim($_REQUEST['SmsCode']);
				if( $SmsCode == '' ){
					$this->ApiReturn(null, L('SmsCodeRequired'), 0, API_FORMAT);
				}
				$SmsCode1 = session('SmsCode');
				if( $SmsCode != $SmsCode1){
					$this->ApiReturn(null, L('SmsCodeError'), 0, API_FORMAT);
				}
				*/
                return;
			}else{
				//检查密保答案
				$MemberAnswer = trim( $_POST['MemberAnswer'] );
				if( $MemberAnswer == ''){
					$this->ApiReturn(null, L('AnswerRequired'), 0, API_FORMAT);
				}
				$isCorrect = $m->isAnswerCorrect($where['MemberID'], $MemberAnswer);
				if(!$isCorrect){
					$this->ApiReturn(null, L('AnswerError'), 0, API_FORMAT);
				}
			}
            $MemberPassword = yd_password_hash($MemberPassword);
			$result =  $m->where($where)->setField('MemberPassword', $MemberPassword);
			if( $result === false ){
				$this->ApiReturn(null, L('ResetPwdFail'), 0, API_FORMAT);
			}else{
				$this->ApiReturn(null, L('ResetPwdSuccess'), 1, API_FORMAT);
			}
		}
	}
	
    /**
     * 获取评论数据
     */
    public function GetComment(){
    	//接口参数
    	$params['InfoID'] = intval( $_REQUEST['InfoID'] );  //评论InfoID
    	$params['CommentRank'] = isset($_REQUEST['CommentRank']) ? intval($_REQUEST['CommentRank']) : -1; //-1:表示返回所有评论
    	$params['LanguageID'] = API_LANGUAGE_ID;
    	$params['IsCheck'] = 1;
    	$params['Parent'] = 0; //不获取回复的信息
    	$params['ReplyComments'] = 1; //获取回复数据
    
    	//分页
    	$p = isset($_REQUEST['p']) ? intval($_REQUEST['p']) : 1;  //当前页
    	if( isset($_REQUEST['PageSize']) ){
    		$PageSize = intval($_REQUEST['PageSize']);
    	}else{
    		$PageSize = intval( $GLOBALS['Config']['COMMENT_PAGE_SIZE'] );
    	}
    	if( $PageSize <= 0 ) $PageSize = 20;
    
    	$m = D('Admin/Comment');
    	$TotalItemCount = $m->getCommentCount($params);
    	$data['CurrentPage'] = $p; //当前页码
    	$data['PageSize'] = $PageSize; //分页大小
    	$data['TotalItemCount'] = $TotalItemCount; //单项总条数
    	if($TotalItemCount <= 0){
    		$data['TotalPage'] = 0;
    	}else{
    		$data['TotalPage'] = ceil($TotalItemCount/$PageSize); //总页数
    		$Offset = $PageSize*($p-1);
    		$data['Data'] = $m->getComment($Offset, $PageSize, $params);
    	}
    	$this->ApiReturn($data, '', 1, API_FORMAT);
    }
    
    /**
     * 统计评论数据
     */
    public function StatComment(){
    	//接口参数
    	$InfoID = intval( $_REQUEST['InfoID'] );  //评论ID
    	$m = D('Admin/Comment');
    	//获取评论统计数
    	$stat = $m->statCommenRank($InfoID, -1, API_LANGUAGE_ID);
    	$data['PositiveCount'] = isset($stat[3]) ? $stat[3] : 0;
    	$data['NeutralCount'] = isset($stat[2]) ? $stat[2] : 0;
    	$data['NegativeCount'] = isset($stat[1]) ? $stat[1] : 0;
    	$TotalCount = $data['PositiveCount'] + $data['NeutralCount'] + $data['NegativeCount'];
    	$data['PositiveRate'] = ($TotalCount > 0) ? round($data['PositiveCount']*100/$TotalCount) : 0;
    	$data['NeutralRate'] = ($TotalCount > 0) ? round($data['NeutralCount']*100/$TotalCount) : 0;
    	$data['NegativeRate'] = ($TotalCount > 0) ? round($data['NegativeCount']*100/$TotalCount) : 0;
    	$data['TotalCount'] = $TotalCount;  //评论总数
    	$this->ApiReturn($data, '', 1, API_FORMAT);
    }
    
    /**
     * 浏览历史
     */
    public function GetHistory(){
    	$this->checkSign();
    	$MemberID = $this->checkToken();
    	//接口参数
    	$params['MemberID'] = $MemberID;
    	$params['LanguageID'] = API_LANGUAGE_ID;
    	$NowPage = isset($_POST['NowPage']) ? (int)$_POST['NowPage'] : 1;
    	$PageSize = isset($_POST['PageSize']) ? (int)$_POST['PageSize'] : 20;
    	 
    	$m = D('Admin/History');
    	$Total = $m->getHistoryCount($params);
    	//初始化结果
    	$result = array('Data' => false, 'PageSize' => $PageSize, 'PageCount' => 0, 'NowPage' => $NowPage, 'HasNextPage' => 0 );
    	if( $Total > 0 ) {
    		$offset = ($NowPage - 1 > 0) ? ($NowPage - 1)*$PageSize : 0;
    		$data = $m->getHistory($offset, $PageSize, $params);
    		if( !empty($data)){
    			$domain = get_current_url();
    			$n = is_array($data) ? count($data) : 0;
    			for($i=0; $i<$n; $i++){
    				$timestamp = strtotime( $data[$i]['InfoTime'] );
    				$data[$i]['InfoFriendTime'] = yd_friend_date($timestamp);
    				if( CLIENT_TYPE != 3 ){
    					$data[$i]['InfoAttachment'] = app_to_fullurl($data[$i]['InfoAttachment'], $domain);
    					$data[$i]['InfoPicture'] = app_to_fullurl($data[$i]['InfoPicture'], $domain);
    				}
    			}
    			$result['Data'] = $data;  //数据
    			$result['Total'] = $Total;  //信息总条数
    			$result['PageSize'] = $PageSize;  //分页大小
    			$result['PageCount'] = ceil($Total/$PageSize); //总页数
    			$result['NowPage'] = $NowPage;
    			$result['HasNextPage'] = ( $NowPage >= $result['PageCount']) ? 0 : 1;
    		}
    	}
    	$this->ApiReturn($result, '', 1, API_FORMAT);
    }
    
    /**
     * 保存浏览器历史
     */
    public function AddHistory(){
    	$this->checkSign();
    	//判断InfoID是否存在
    	$InfoID = intval($_POST['InfoID']);
    	$MemberID = $this->checkToken();
    	if( !app_info_exist($InfoID)){
    		$this->ApiReturn(null, '', 0, API_FORMAT);
    	}
    	$m = D('Admin/History');
    	$result = $m->addHistory($InfoID, $MemberID); //做了不能重复添加的控制
    	$this->ApiReturn(null, '', 1, API_FORMAT);
    }
    
    /**
     * 我的收藏
     */
    public function GetFavorite(){
    	$this->checkSign();
    	$MemberID = $this->checkToken();
    	//接口参数
    	$params['MemberID'] = $MemberID;
    	$params['LanguageID'] = API_LANGUAGE_ID;
    	$NowPage = isset($_POST['NowPage']) ? (int)$_POST['NowPage'] : 1;
    	$PageSize = isset($_POST['PageSize']) ? (int)$_POST['PageSize'] : 20;
    	
    	$m = D('Admin/Favorite');
    	$Total = $m->getFavoriteCount($params);
    	//初始化结果
    	$result = array('Data' => false, 'PageSize' => $PageSize, 'PageCount' => 0, 'NowPage' => $NowPage, 'HasNextPage' => 0 );
    	if( $Total > 0 ) {
    		$offset = ($NowPage - 1 > 0) ? ($NowPage - 1)*$PageSize : 0;
    		$data = $m->getFavorite($offset, $PageSize, $params);
    		if( !empty($data)){
    			$domain = get_current_url();
    			$n = is_array($data) ? count($data) : 0;
    			for($i=0; $i<$n; $i++){
    				$timestamp = strtotime( $data[$i]['InfoTime'] );
    				$data[$i]['InfoFriendTime'] = yd_friend_date($timestamp);
    				if( CLIENT_TYPE != 3 ){
	    				$data[$i]['InfoAttachment'] = app_to_fullurl($data[$i]['InfoAttachment'], $domain);
	    				$data[$i]['InfoPicture'] = app_to_fullurl($data[$i]['InfoPicture'], $domain);
    				}
    			}
    			
    			$result['Data'] = $data;  //数据
    			$result['Total'] = $Total;  //信息总条数
    			$result['PageSize'] = $PageSize;  //分页大小
    			$result['PageCount'] = ceil($Total/$PageSize); //总页数
    			$result['NowPage'] = $NowPage;
    			$result['HasNextPage'] = ( $NowPage >= $result['PageCount']) ? 0 : 1;
    		}
    	}
    	$this->ApiReturn($result, '', 1, API_FORMAT);
    }
    
    /**
     * 我的下线/推广
     */
    function GetDownline(){
    	$this->checkSign();
    	$MemberID = $this->checkToken();
    	$m = D('Admin/Member');
    	$result = $m->getDownline($MemberID);
    	$Level = isset($_REQUEST['Level']) ? intval($_REQUEST['Level']) : -1;
    	$list = array(1=>'一级下线', 2=>'二级下线', 3=>'三级下线');
    	$AllLevel = ($Level == -1) ? array(1,2,3) : array($Level);
    	$data = array();
    	$m = D('Admin/DistributorLevel');
    	foreach ($result as $k=>$v){
    		if(in_array($k, $AllLevel)){
    			if(!empty($v)){
    				$n = count($v);
    				$DownLineLevel = $list[$k];
    				for($i=0; $i<$n; $i++){
    					$v[$i]['DownLineLevelName'] = $DownLineLevel;
    					$v[$i]['DownLineLevelID'] = $k;
    					$where = 'DistributorLevelID = '.intval($v[$i]['DistributorLevelID']);
    					$DistributorLevelName = $m->where($where)->getField('DistributorLevelName');
    					$v[$i]['DistributorLevelName'] = $DistributorLevelName;
    					$data[$k][] = $v[$i];
    				}
    			}
    		}
    	}
    	$Downline1Count = count($result[1]);
    	$Downline2Count = count($result[2]);
    	$Downline3Count = count($result[3]);
    	$DownlineCount = $Downline1Count + $Downline2Count + $Downline3Count;
    	$temp['Data'] = array(
    		'DownlineCount'=>$DownlineCount,
    		'Downline1Count'=>$Downline1Count,
    		'Downline2Count'=>$Downline2Count,
    		'Downline3Count'=>$Downline3Count,
    		//数据
    		'Downline1'=>$data[1],
    		'Downline2'=>$data[2],
    		'Downline3'=>$data[3],
    	);
    	$this->ApiReturn($temp, '', 1, API_FORMAT);
    }
    
    /**
     * 我的收益
     */
    function GetIncome(){
    	$this->checkSign();
    	$MemberID = $this->checkToken();
    	$CashType = 5; //表示分佣金额

    	$m = D('Admin/Cash');
    	$p['CashType'] = $CashType;
    	$p['MemberID'] = $MemberID;
    	$result['Data'] = $m->getCash(-1, -1, $p);
    	$result['TotalQuantity'] = $m->getQuantity($CashType, $MemberID);
    	$this->ApiReturn($result, '', 1, API_FORMAT);
    }
    
    /**
     * 我的资金
     */
    function GetCash(){
    	$this->checkSign();
    	$MemberID = $this->checkToken();
    	$m = D('Admin/Cash');
    	$p = array('MemberID'=>$MemberID);
    	$result['Data'] = $m->getCash(-1, -1, $p);
    	//我的充值总金额
    	$result['TotalQuantity'] = $m->getQuantity(1, $MemberID);
    	//我的可用资金
    	$result['AvailableQuantity'] = $m->getAvailableQuantity($MemberID);
    	
		//是否设置提现密码
    	$mm = D('Admin/Member');
    	$CashPassword = $mm->getCashPassword($MemberID); //获取提现密码
    	$result['HasCashPassword'] = $CashPassword ? 1 : 0;
    	
    	$WithdrawThreshold = $GLOBALS['Config']['WithdrawThreshold'];
    	$CanWithdraw = 0; //是否可以提现
    	if($result['AvailableQuantity'] >= $WithdrawThreshold){
    		$CanWithdraw = 1;
    	}
    	$result['CanWithdraw'] = $CanWithdraw;
    	$result['WithdrawThreshold'] = $WithdrawThreshold;
    	$result['MinWithdraw'] = $GLOBALS['Config']['MinWithdraw'];
    	$myresult['Data'] = $result;
    	$this->ApiReturn($myresult, '', 1, API_FORMAT);
    }
    
    /**
     * 删除资金记录
     */
    function DeleteCash(){
    	$this->checkSign();
    	$MemberID = $this->checkToken();
    	$m = D('Admin/Cash');
    	$p['MemberID'] = $MemberID;
    	$b = $m->delCash($_POST['CashID'], $p);
    	if($b){
    		$this->ApiReturn(null, L('DelSuccess'), 1, API_FORMAT);
    	}else{
    		$this->ApiReturn(null, L('DelFail'), 0, API_FORMAT);
    	}
    }
    
    /**
     * 获取用户提现银行
     */
    public function GetBank(){
    	$this->checkSign();
    	$MemberID = $this->checkToken();
    	$m = D('Admin/Cash');
    	$result['Data'] = $m->getBank($MemberID);
    	$this->ApiReturn($result, '', 1, API_FORMAT);
    }

    public function AddWithdraw2(){
        $this->ApiReturn("", L('WithdrawMoneyNumeral') , 0, API_FORMAT);
    }
    /**
     * 保存用户提现申请
     */
    public function AddWithdraw(){
    	$this->checkSign();
    	$MemberID = $this->checkToken();
    	if( !is_numeric($_POST['CashQuantity']) ){
			$this->ApiReturn("", L('WithdrawMoneyNumeral') , 0, API_FORMAT);
		}
		$CashQuantity = (double)($_POST['CashQuantity']);
		$MinWithdraw = $GLOBALS['Config']['MinWithdraw']; //最低提现额度
		if($CashQuantity < $MinWithdraw){
			$GreaterThan = str_replace('[n]', $MinWithdraw, L('WithdrawMoneyGreaterThan'));
			$this->ApiReturn("", $GreaterThan , 0, API_FORMAT);
		}
		$m = D('Admin/Cash');
		$AvailableQuantity = $m->getAvailableQuantity($MemberID);
		if($CashQuantity>$AvailableQuantity){
			$this->ApiReturn("", L('InsufficientAccount') , 0, API_FORMAT);
		}
	
		if( empty($_POST['BankName']) ){
			$this->ApiReturn("", L('BankNameRequired') , 0, API_FORMAT);
		}
		if( empty($_POST['BankAccount']) ){
			$this->ApiReturn("", L('BankAccountRequired') , 0, API_FORMAT);
		}
		if( empty($_POST['OwnerName']) ){
			$this->ApiReturn("", L('OwnerNameRequired') , 0, API_FORMAT);
		}
	
		//验证密码
		$mm = D('Admin/Member');
        $CashPassword = $_POST['CashPassword'];
        $isCorrect = $mm->isCashPasswordCorrect($MemberID, $CashPassword);
		if(!$isCorrect){
			$this->ApiReturn("", L('CashPasswordError') , 0, API_FORMAT);
		}
		$_POST['CashType'] = 4;
		$_POST['CashStatus'] = 2; //未转账状态
		$_POST['MemberID'] = $MemberID;
		$_POST['CashQuantity'] = 0 - $CashQuantity;
		$_POST['CashTime'] = date('Y-m-d H:i:s');
		
		if( $m->create() ){
			$result = $m->add();
			if($result){
				$LogDescription = 'ID:'.$m->getLastInsID();
				WriteLog( $LogDescription );
				$this->ApiReturn(null, L('WithdrawSuccess') , 1, API_FORMAT);
			}else{
				$this->ApiReturn(null, L('WithdrawFail') , 0, API_FORMAT);
			}
		}else{
			$this->ApiReturn(null, L('WithdrawFail') , 0, API_FORMAT);
		}
    }
    
    /**
     * 设置提现密码
     */
    public function SetCashPassword(){
    	$this->checkSign();
    	$MemberID = $this->checkToken();
    	$m = D('Admin/Member');
    	//原始提现密码密码
    	$oldPassword = $m->where("MemberID=$MemberID")->getField('CashPassword');
    	$HasOldPassword = empty($oldPassword) ? 0 : 1;
    	
    	$pwd1 = trim($_POST['Pwd1']);  //原始密码
    	$pwd2 = trim($_POST['Pwd2']);  //新密码
    	$pwd3 = trim($_POST['Pwd3']);  //新密码确认
    		
    	if( $HasOldPassword && empty($pwd1) ){
    		$this->ApiReturn(null, L('OldPasswordRequired') , 0, API_FORMAT);
    	}
    	
    	if( empty($pwd2) ){
    		$this->ApiReturn(null, L('NewPasswordRequired'), 0, API_FORMAT);
    	}
    	
    	if( empty($pwd3) ){
    		$this->ApiReturn(null, L('ConfirmPasswordRequired'), 0, API_FORMAT);
    	}
    	
    	if( $pwd2 != $pwd3 ){
    		$this->ApiReturn(null, L('PasswordUnmatch'), 0, API_FORMAT);
    	}
    	
    	if( $HasOldPassword && $pwd1 == $pwd3 ){
    		$this->ApiReturn(null, L('NewOldPasswordCanNotSame'), 0, API_FORMAT);
    	}
    	
    	$options['LogType'] = 8;
    	if($HasOldPassword){
    		$isCorrect = $m->isCashPasswordCorrect($MemberID, $pwd1);
    		if(!$isCorrect){
    			$options['UserAction'] = '修改提现密码';
    			WriteLog("{$MemberID}修改提现密码失败，原密码错误", $options);
    			$this->ApiReturn(null, L('OldPasswordIncorrect'), 0, API_FORMAT);
    		}
    	}

    	$pwd2 = yd_password_hash($pwd2);
    	$r = $m->where("MemberID={$MemberID}")->setField('CashPassword', $pwd2);
    	if($r){
    		$options['UserAction'] = '修改提现密码';
    		WriteLog("{$MemberID}修改提现密码成功", $options);
    		$this->ApiReturn(null, L('ModifyPwdSuccess'), 1, API_FORMAT);
    	}else{
    		$this->ApiReturn(null, L('ModifyPwdFail'), 0, API_FORMAT);
    	}
    }
    
    /**
     * 加入收藏
     */
    public function AddFavorite(){
    	if( !app_info_exist($_REQUEST['InfoID']) ){
    		$this->ApiReturn(null, L('FavoriteFail'), 0, API_FORMAT);
    	}
    	$m = D('Admin/Favorite');
    	//有写入数据的请求必须是POST提交
    	if( isset( $_POST['IsAdd']) ){ //非本地调用
    		$this->checkSign();
    		$InfoID = intval( $_POST['InfoID'] );
    		$MemberID = $this->checkToken();
    		if( !app_can_favorite($MemberID)){
    			$this->ApiReturn(null, L('LoginFirst'), 0, API_FORMAT);
    		}
    		$where['InfoID'] = $InfoID;
    		$where['MemberID'] = $MemberID;
	    	if( $_POST['IsAdd'] == 1){
	    		//先判断用户是否已经收藏当前文章
		    	$n = $m->where($where)->count();
		    	if( $n > 0 ){
		    		$this->ApiReturn(null, L('FavoriteSuccess'), 1, API_FORMAT);
		    	}else{
		    		$data['InfoID'] = $InfoID;
		    		$data['MemberID'] = $MemberID;
		    		$data['FavoriteTime'] = date('Y-m-d H:i:s');
		    		if( $m->add($data) ){
		    			$this->ApiReturn(null, L('FavoriteSuccess'), 1, API_FORMAT);
		    		}else{
		    			$this->ApiReturn(null, L('FavoriteFail'), 0, API_FORMAT);
		    		}
		    	}
    		}else{  //取消收藏
    			$m->delFavoriteByInfoID($InfoID, array('MemberID'=>$MemberID));
    			$this->ApiReturn(null, L('CancelFavorite'), 1, API_FORMAT);
    		}
    	}else{ //==主要在本地调用==
	    	$MemberID = intval( session('MemberID') );
	    	if( empty($MemberID) ){
	    		$this->ApiReturn(null, L('LoginFirst'), 2, API_FORMAT);
	    	}
	    	if( !app_can_favorite($MemberID) ){
	    		$this->ApiReturn(null, L('FavoriteFail'), 0, API_FORMAT);
	    	}
	    	$where['InfoID'] = intval( $_REQUEST['InfoID'] );
	    	$where['MemberID'] = $MemberID;
	    	$n = $m->where($where)->count();
	    	if( $n > 0 ){
	    		$this->ApiReturn(null, L('FavoriteSuccess'), 1, API_FORMAT);
	    	}else{
	    		$data['InfoID'] = intval( $_REQUEST['InfoID'] );
	    		$data['MemberID'] = $MemberID;
	    		$data['FavoriteTime'] = date('Y-m-d H:i:s');
	    		if( $m->add($data) ){
	    			$this->ApiReturn(null, L('FavoriteSuccess'), 1, API_FORMAT);
	    		}else{
	    			$this->ApiReturn(null, L('FavoriteFail'), 0, API_FORMAT);
	    		}
	    	}
    	}
    }
    
    /**
     * 验证签名是否正确
     */
    private function checkSign(){
    	//return true; //调试模式，不校验签名
    	if(CLIENT_TYPE == 3){ //如果是本地调用，则不进行签名校验
    		return true;
    	}
    	//H5端不校验签名，因为秘钥不能存放在h5
    	if(isset($_POST['XcxType']) && intval($_POST['XcxType'])==5){
            return true;
        }
    	if(API_SIGN){ //只有签名存在的时候才进行服务器端认证
    		//先检查是否接口权限
    		$m = D('Admin/Secret');
    		//无需设置权限的接口
    		$NoCheckList = array('LoginOut','FindOrder','GetOrder','StatOrder'); 
    		if( in_array(P_ACTION_NAME, $NoCheckList) ){
    			$AppSecret = $m->getAppSecret($_POST['AppID']);
    		}else{
	    		$AppSecret = $m->checkSecret($_POST['AppID'], P_ACTION_NAME);
	    		if(empty($AppSecret)){
	    			$this->ApiReturn(null, L('ApiNoPermission'), 0, API_FORMAT);
	    		}
    		}
    		
    		//签名认证
	    	$temp = app_para_filter($_POST);
	    	ksort($temp);
	    	reset($temp);
	    	$sign = app_build_sign($temp, $AppSecret);
	    	//判断签名是否正确
	    	if(API_SIGN != $sign){
	    		$this->ApiReturn(null, L('AppSignError'), 0, API_FORMAT);
	    	}
	    	
	    	//判断时间戳是否正确
	    	$MaxTimeout = 0; //超时时间，单位：秒，0表示不判断超时
	    	if($MaxTimeout > 0){
		    	$timestamp = intval($_POST['Timestamp']);
		    	$diff = abs(time() - $timestamp);
		    	if($diff > $MaxTimeout){
		    		$this->ApiReturn(null, L('AppTimestampError'), 0, API_FORMAT);
		    	}
	    	}
    	}else{
    		//如果是同域认证（服务器端语言不受同域限制），就没有必要进行签名认证
    		//签名不能为空，否则其他语言可用调用
    		$this->ApiReturn(null, L('AppSignError'), 0, API_FORMAT);
    	}
    }
    
    function AddAppFeedback(){
    	$this->checkSign();
    	$MemberID = $this->checkToken(true); //传入true表示，非会员也可以提交反馈
    	$ip = get_client_ip();
    	if( !app_can_feedback($ip) ){
    		$this->ApiReturn(null, L('FeedbackFail') , 0, API_FORMAT);
    	}
    	
    	$m = D('Admin/AppFeedback');
    	if( $_POST['AppFeedbackContent'] == '' ){
    		$this->ApiReturn(null, L('AppFeedbackContentRequired'), 0, API_FORMAT);
    	}
    	$data['AppFeedbackContent'] = $_POST['AppFeedbackContent'];
    	$data['AppFeedbackContact'] = $_POST['AppFeedbackContact'];
    	$data['MemberID'] = $MemberID;
    	$data['AppFeedbackTime'] = date('Y-m-d H:i:s');
    	if(CLIENT_TYPE != 3){
    		$data['Uuid'] = $_POST['Uuid'];
    		$data['AppFeedbackImage'] = app_remove_domain($_POST['AppFeedbackImage']);
    	}
    	$data['AppFeedbackIp'] = $ip;
    	if($m->add($data)){
    		$this->ApiReturn(null, L('FeedbackSuccess'), 1, API_FORMAT);
    	}else{
    		$this->ApiReturn(null, L('FeedbackFail') , 0, API_FORMAT);
    	}
    }
    
    /**
     * 上传设备数据
     */
    function UploadDevice(){
    	if(CLIENT_TYPE == 3) return; //本地不使用此接口
    	$this->checkSign();
    	$Uuid = trim($_POST['Uuid']);
        $Uuid = YdInput::checkLetterNumber($Uuid);
    	$ip = get_client_ip();
    	$time = date('Y-m-d H:i:s');
    	if(empty($Uuid) || !app_can_stat($ip)){
    		$this->ApiReturn(null, null , 0, API_FORMAT);
    	}
    	
    	$m = D('Admin/AppStat');
    	$where['Uuid'] = $Uuid;
    	$result = $m->where($where)->getField('Uuid');
    	if(!empty($result)){ //如果已经登记，则记录到活动表中
    		$ma = D('Admin/AppActive');
    		$active['Uuid'] = $Uuid;
    		$active['Ip'] = $ip;
    		$active['StartTime'] = $time;
    		$result = $ma->add($active);
    		$this->ApiReturn(null, null, 1, API_FORMAT);
    	}
    	
    	$data['Uuid'] = $Uuid;
    	$data['Platform'] = $_POST['Platform'];
    	$data['Model'] = $_POST['Model'];
    	$data['Manufacturer'] = $_POST['Manufacturer'];
    	$data['Ip'] = $ip;
    	$data['Time'] = $time;
    	//保存反馈
    	if($m->add($data)){
    		$this->ApiReturn(null, null, 1, API_FORMAT);
    	}else{
    		$this->ApiReturn(null, null, 0, API_FORMAT);
    	}
    }
    
    /**
     * 修改个人密码
     */
    function ModifyPassword(){
    	$this->checkSign();
    	$MemberID = $this->checkToken();
    	$OldPassword = trim($_POST['OldPassword']);
    	$NewPassword = trim($_POST['NewPassword']);
    	$ConfirmPassword = trim($_POST['ConfirmPassword']);
    	//会员ID
    	if( empty($MemberID) ){
    		$this->ApiReturn(null, L('ModifyPwdFail'), 0, API_FORMAT);
    	}
    	//旧密码
    	if( empty($OldPassword) ){
    		$this->ApiReturn(null, L('OldPasswordRequired'), 0, API_FORMAT);
    	}
    	//新密码
    	if( empty($NewPassword) ){
    		$this->ApiReturn(null, L('NewPasswordRequired'), 0, API_FORMAT);
    	}
    	//确认密码
    	if( empty($ConfirmPassword) ){
    		$this->ApiReturn(null, L('ConfirmPasswordRequired'), 0, API_FORMAT);
    	}
    	if( $NewPassword != $ConfirmPassword ){
    		$this->ApiReturn(null, L('PasswordUnmatch'), 0, API_FORMAT);
    	}
    	if( $NewPassword == $OldPassword ){
    		$this->ApiReturn(null, L('NewOldPasswordCanNotSame'), 0, API_FORMAT);
    	}
    	
    	//判断原始密码是否正确
    	$m = D('Admin/Member');
    	$isCorrect = $m->isOldPasswordCorrect($MemberID, $OldPassword);
    	if(!$isCorrect){
    		$this->ApiReturn(null, L('OldPasswordIncorrect'), 0, API_FORMAT);
    	}

    	//修改密码
        $NewPassword = yd_password_hash($NewPassword);
    	$result = $m->where("MemberID=$MemberID")->setField('MemberPassword', $NewPassword );
    	if($result){
    		$this->ApiReturn(null, L('ModifyPwdSuccess'), 1, API_FORMAT);
    	}else{
    		$this->ApiReturn(null, L('ModifyPwdFail'), 0, API_FORMAT);
    	}
    }
    
    /**
     * 修改个人资料
     */
    function ModifyMemberInfo(){
    	$this->checkSign();
    	$MemberID = $this->checkToken();
    	$MemberAvatar = $_POST['MemberAvatar'];
        $data = array();
        if(!empty($_POST['MemberAvatar'])) {  //保存的时候必须移除前缀
            $data['MemberAvatar'] = app_remove_domain($MemberAvatar);
        }
        if(isset($_POST['MemberGender'])){
            $data['MemberGender'] = intval($_POST['MemberGender']);
        }

        if(!empty($_POST['MemberMobile']))$data['MemberMobile'] = $_POST['MemberMobile'];
        if(!empty($_POST['MemberTelephone']))$data['MemberTelephone'] = $_POST['MemberTelephone'];
        if(!empty($_POST['MemberEmail'])) $data['MemberEmail'] = $_POST['MemberEmail'];
        if(!empty($_POST['MemberQQ']))$data['MemberQQ'] = $_POST['MemberQQ'];
    	if(!empty($_POST['MemberName'])) $data['MemberName'] = $_POST['MemberName'];
    	
    	$m = D('Admin/Member');
    	//增加RegisterTime和RegisterIP作为查询条件，增强安全性
    	$where['MemberID'] = $MemberID;
    	$where['IsCheck'] = 1;
    	$where['IsLock'] = 0;
    	$result = $m->where($where)->setField($data);
    	if($result===false){ //修改失败
    		$this->ApiReturn(null, L('ModifyFail'), 0, API_FORMAT);
    	}else{
    		$data['MemberAvatar'] = $MemberAvatar; //返回的地址必须包含域名前缀
    		$this->ApiReturn(array('Data'=>$data), L('ModifySuccess'), 1, API_FORMAT);
    	}
    }
    
    /**
     * 检查Token，并返回MemberID
     * $returnMemberID: 如果MemberID不存在 true:返回0，false:返回ajax json对象
     * 如果MemberID存在，就直接返回MemberID
     */
    private function checkToken($returnMemberID=false){
    	if(CLIENT_TYPE == 3){
    		$memberID = intval(session('MemberID'));
    	}else{
	    	$MaxTimeout = 0; //超时时间，单位：秒，0：表示永不超时
	    	$m = D('Admin/Token');
	    	$token = YdInput::checkLetterNumber($_REQUEST['Token']);
	    	$where['Token'] = $token;
	    	$data = $m->where($where)->field('MemberID,Timestamp')->find();
	    	$memberID = 0;
	    	if($data){
	    		$memberID = intval($data['MemberID']);
	    		if($MaxTimeout > 0 ){ //判断是否超时
	    			$TokenTimestamp = intval($data['Timestamp']);
	    			$timeout = time() - $TokenTimestamp;
	    			if( $timeout > $MaxTimeout){ //已经超时
	    				if($returnMemberID){
	    					$memberID = 0;
	    				}else{
	    					$this->ApiReturn(null, L('LoginTimeout'), 0, API_FORMAT);
	    				}
	    			}
	    		}
	    	}
	    	
	    	if(empty($memberID) && !$returnMemberID ){
	    		$this->ApiReturn(null, L('LoginFirst'), 0, API_FORMAT);
	    	}
    	}
    	return $memberID;
    }
    
    /**
     * 上传文件
     */
    function UploadFile(){
    	if(CLIENT_TYPE == 3) {
    	    $this->checkAdminLogin();
        }else{
            $this->checkSign();
            $this->checkToken();  //只有登录后才能上传文件
        }
    	$d = &$GLOBALS['Config'];
    	import("ORG.Net.UploadFile");
    	$upload = new UploadFile();
    	//设置上传文件大小
    	$upload->maxSize  = $d['MAX_UPLOAD_SIZE'] ; //最大上传大小
    	//设置上传文件类型，禁止上传asp,aspx,jsp,php,ashx,js,html,htm，增强安全性
        $deniedExt = array(
            'asa','asp', 'aspx', 'cdx','ascx', 'vbs', 'ascx', 'jsp', 'ashx', 'js',  'reg',  'cgi',
            'html', 'htm','shtml', 'xml', 'xhtml', 'config', 'htaccess', 'ini',
            'cfm', 'cfc', 'pl', 'bat', 'exe',  'com',  'dll',  'htaccess', 'cer',
            'php5', 'php4', 'php3', 'php2', 'php', 'pht', 'phtm'
        );
    	$allowExts = str_ireplace($deniedExt, 'xxx', $d['UPLOAD_FILE_TYPE']);
    	$upload->allowExts  = explode('|', $allowExts);
    	$dirName = isset($_POST['UploadDirName']) ? $_POST['UploadDirName'] : 'app';
        if(!preg_match("/^[a-zA-Z0-9_-]+$/i", $dirName)){
            $dirName = '';
        }
    	//设置附件上传目录
    	$UploadDir = "{$d['UPLOAD']}{$dirName}/";
    	if( !is_dir($UploadDir) ){
    		mkdir($UploadDir,0755,true);
    	}
    	$upload->savePath =  $UploadDir;
		if(yd_is_php8()){ //php8不能直接复制函数名
			$upload->saveRule= 'time';
		}else{
			$upload->saveRule= time;
		}

    	//多端小程序装修传过来的是file，不是appfile
    	//IMG_20170129_114000.jpg?1487235976450，插入的图片文件包含?，必须去掉
    	$offset = strpos($_FILES['appfile']['name'], '?');
    	if( $offset !== false){
    		$_FILES['appfile']['name'] = substr($_FILES['appfile']['name'], 0, $offset);
    	}
    	
    	if($upload->upload()) {
    		$info =  $upload->getUploadFileInfo();
    		//上传后文件的路径
    		$path = __ROOT__.'/'.substr($UploadDir.$info[0]['savename'], 2);
    		$data['Data'] = app_to_fullurl($path); //加上域名前缀
    		$this->ApiReturn($data, '', 1, API_FORMAT);
    	}else{
    		$data['Data'] = '';
    		$this->ApiReturn($data, '', 0, API_FORMAT);
    	}
    }
    
    /**
     * 获取推送消息
     */
    function GetAppMessage(){
    	//接口参数
    	$params['AppMessageType'] = $_REQUEST['AppMessageType'];
    	$NowPage = isset($_REQUEST['NowPage']) ? (int)$_REQUEST['NowPage'] : 1;
    	$PageSize = isset($_REQUEST['PageSize']) ? (int)$_REQUEST['PageSize'] : 20;
    	
    	$m = D('Admin/AppMessage');
    	$Total = $m->getAppMessageCount($params);
    	//初始化结果
    	$result = array('Data' => false, 'PageSize' => $PageSize, 'PageCount' => 0, 'NowPage' => $NowPage, 'HasNextPage' => 0 );
    	if( $Total > 0 ) {
    		$offset = ($NowPage - 1 > 0) ? ($NowPage - 1)*$PageSize : 0;
    		$data = $m->getAppMessage($offset, $PageSize, $params);
    		if( !empty($data)){
    			$domain = get_current_url();
    			$n = is_array($data) ? count($data) : 0;
    			for($i=0; $i<$n; $i++){
    				$timestamp = strtotime( $data[$i]['AppMessageTime'] );
    				$data[$i]['AppMessageTime'] = yd_friend_date($timestamp);
    				if( CLIENT_TYPE != 3 ){
    					$data[$i]['AppMessageContent'] = yd_relative_to_absolute( ParseTag( $data[$i]['AppMessageContent'] ) );
    				}else{
    					$data[$i]['AppMessageContent'] = ParseTag( $data[$i]['AppMessageContent'] );
    				}
    			}
    			 
    			$result['Data'] = $data;  //数据
    			$result['Total'] = $Total;  //信息总条数
    			$result['PageSize'] = $PageSize;  //分页大小
    			$result['PageCount'] = ceil($Total/$PageSize); //总页数
    			$result['NowPage'] = $NowPage;
    			$result['HasNextPage'] = ( $NowPage >= $result['PageCount']) ? 0 : 1;
    		}
    	}
    	$this->ApiReturn($result, '', 1, API_FORMAT);
    }
    
    /**
     * 查找推送消息
     */
    public function FindAppMessage(){
    	$m = D('Admin/AppMessage');
    	$data = $m->findAppMessage($_REQUEST['id']); //可以是id或文件名
    	if(empty($data)){
    		$this->ApiReturn(false, '', 0, API_FORMAT);
    	}
    	if( CLIENT_TYPE != 3 ){
    		$data['AppMessageContent'] = yd_relative_to_absolute(ParseTag( $data['AppMessageContent'] ));
    	}else{
    		$data['AppMessageContent'] = ParseTag( $data['AppMessageContent'] );
    	}
    	$data['AppMessageTime'] = yd_friend_date(strtotime( $data['AppMessageTime'] ));
    	$this->ApiReturn(array('Data'=>$data), '', 1, API_FORMAT);
    }
    
    /**
     * 添加商品到购物车
     */
    function AddCart(){
    	$InfoID = intval($_REQUEST['id']);
    	$Quantity = empty($_REQUEST['quantity']) ? 1 : intval($_REQUEST['quantity']);
    	$valueid = empty($_REQUEST['valueid'] ) ? '' : $_REQUEST['valueid'];
    	if( !is_numeric($InfoID) ){
    		$this->ApiReturn(false, L('AddCartFail'), 0, API_FORMAT);
    	}
    	
    	$MemberID = $this->checkToken();
    	$m = D('Admin/Cart');
    	$b = $m->has($InfoID, $valueid, $MemberID);
    	if($b){ //商品在购物车中已经存在
    		$this->ApiReturn(false, L('AddCartRepeat'), 0, API_FORMAT);
    	}
    	
    	$data = array('ProductID' => $InfoID, 'ProductQuantity' => $Quantity, 'AttributeValueID'=>$valueid);
    	$m->addCart( $data, $MemberID);
    	$p['TotalItemCount'] = $m->getItemCount($MemberID);
    	$p['TotalItemPrice'] = $m->getTotalPrice($InfoID, $valueid, $MemberID);
    	$p['TotalPrice'] = $m->getTotalPrice(false, $MemberID);
    	$this->ApiReturn(array('Data'=>$p), L('AddCartSuccess'), 1, API_FORMAT);
    }
    
    /**
     * 删除购物车中的商品
     */
    function DeleteCart(){
    	$id = intval($_REQUEST['id']); //组合的数比较大，不能用intval转换    	
    	$MemberID = $this->checkToken();
    	$m = D('Admin/Cart');
    	$m->deleteCart($id, $MemberID);
    	
    	$p['TotalItemCount'] = $m->getItemCount($MemberID);
    	$p['TotalItemPrice'] = $m->getTotalPrice($id,$MemberID);
    	$p['TotalPrice'] = $m->getTotalPrice(false, $MemberID);
    	$this->ApiReturn(array('Data'=>$p), '', 1, API_FORMAT);
    }
    
    /**
     * 清空购物车
     */
    function ClearCart(){
    	$MemberID = $this->checkToken();
    	$m = D('Admin/Cart');
    	$m->clearCart($MemberID);
    	$this->ApiReturn(null, '', 1, API_FORMAT);
    }
    
    /**
     * 设置购物车中的商品数量
     * @param int $id 商品ID
     * @param int $n 商品数量
     * @param int $type 1: 设置，2：加1，3：减1
     */
    private function _setQuantity($id, $n, $type=1){
    	$MemberID = $this->checkToken();
    	$m = D('Admin/Cart');
    	$b = $m->setQuantity( $id, $n, $type, $MemberID);
    	if($b){
    		$p['TotalItemCount'] = $m->getItemCount($MemberID);
    		$p['TotalItemPrice'] = $m->getTotalPrice($id,$MemberID);
    		$p['TotalPrice'] = $m->getTotalPrice(false, $MemberID);
    		$p['ProductQuantity'] = $b;
    		$this->ApiReturn(array('Data'=>$p), '', 1, API_FORMAT);
    	}else{
    		$this->ApiReturn(null, '', 0, API_FORMAT);
    	}
    }
    
    /**
     * 设置商品数量
     */
    function SetQuantity(){
    	$this->_setQuantity($_REQUEST['CartID'], $_REQUEST['Quantity'], 1);
    }
    
    /**
     * 增加数量
     */
    function IncQuantity(){
    	$this->_setQuantity($_REQUEST['CartID'], 0, 2);
    }
    
    /**
     * 减少数量
     */
    function DecQuantity(){
    	$this->_setQuantity($_REQUEST['CartID'], 0, 3);
    }
    
    /**
     * 使用线下优惠券代码
     */
    public function UseCouponCode(){
        $this->checkSign();
        $MemberID = $this->checkToken();
    	$CouponCode = trim($_REQUEST['CouponCode']);
    	$TotalPrice = floatval($_REQUEST['TotalPrice']);
    	if(empty($CouponCode)){
    		$this->ApiReturn(null, L('InputCouponCode'), 0, API_FORMAT);
    	}
    	$m = D('Admin/CouponSend');
    	$result = $m->checkCouponCode($CouponCode);
    	if(is_array($result)){
    		if( $TotalPrice < $result['ConsumeMoney'] ){
    			$Tip = str_ireplace('[n]', $result['ConsumeMoney'], L('CanNotUseCoupon'));
    			$this->ApiReturn(null, $Tip, 0, API_FORMAT);
    		}else{
    			//优惠价格，直接返回负数
    			$result['CouponMoney'] = number_format(-$result['CouponMoney'], 2);
    			$this->ApiReturn(array('Data'=>$result), '', 1, API_FORMAT);
    		}
    	}elseif($result==1){ //优惠券已过期
    		$this->ApiReturn(null, L('CouponExpired'), 0, API_FORMAT);
    	}elseif($result==2){ //优惠券不存在
    		$this->ApiReturn(null, L('CouponNotExist'), 0, API_FORMAT);
    	}elseif($result==3){ //优惠券已经用过了
    		$this->ApiReturn(null, L('CouponUsed'), 0, API_FORMAT);
    	}
    }
    
    /**
     * 获取可用优惠券数据（主要用户订单结算）
     */
    function GetCoupon(){
    	//$this->checkSign(); 不验证签名
    	$MemberID = $this->checkToken();
    	$m = D('Admin/CouponSend');
    	$data['Data'] = $m->getAvailableCoupon($MemberID);
    	$this->ApiReturn($data, '', 1, API_FORMAT);
    }
    
    /**
     * 获取我的优惠券
     */
    function GetCouponSend(){
    	$this->checkSign();
    	$p['MemberID'] = $this->checkToken();
    	$m = D('Admin/CouponSend');
    	$data['Data'] = $m->getCouponSend(-1, -1, $p);
    	$this->ApiReturn($data, '', 1, API_FORMAT);
    }
    
    /**
     * 删除我的优惠券
     */
    function DeleteCouponSend(){
    	$this->checkSign();
    	$MemberID = $this->checkToken();
    	$m = D('Admin/CouponSend');
    	$p['MemberID'] = $MemberID;
    	$b = $m->delCouponSend($_POST['CouponSendID'], $p);
    	if($b){
    		$this->ApiReturn(null, L('DelSuccess'), 1, API_FORMAT);
    	}else{
    		$this->ApiReturn(null, L('DelFail'), 0, API_FORMAT);
    	}
    }
    
    /**
     * 获取我的积分
     */
    function GetPoint(){
    	$MemberID = $this->checkToken();
    	$m = D('Admin/Point');
    	$data['Data'] = array('TotalPoint'=>0, 'MaxUsePoint'=>0);
    	if($MemberID>0){
    		//会员总积分
    		$data['Data']['TotalPoint'] = $m->getTotalPoint($MemberID);
    		$p = array('MemberID'=>$MemberID);
    		$data['Data']['Data'] = $m->getPoint(-1, -1, $p);
    		//获取当前订单的最大可使用积分
    		$mc = D('Admin/Cart');
    		$data['Data']['MaxUsePoint'] = $mc->getCartExchangePoint($MemberID);
    	}
    	$this->ApiReturn($data, '', 1, API_FORMAT);
    }
    
    /**
     * 使用积分
     */
    function UsePoint(){
        $this->checkSign();
    	$Point = intval($_REQUEST['Point']);
    	if($Point<0){
    		$this->ApiReturn(null, L('InvalidPoint'), 0, API_FORMAT);
    	}
    	$m = D('Admin/Point');
    	$MemberID = $this->checkToken();
    	$TotalPoint = $m->getTotalPoint($MemberID); //获取总积分
    	//输入的积分不能大于总积分
    	if($Point > $TotalPoint){
    		$this->ApiReturn(null, L('GtTotalPoint'), 0, API_FORMAT);
    	}
    	 
    	//判断不能大于最大可以使用的积分
    	$mc = D('Admin/Cart');
    	$MaxUsePoint = $mc->getCartExchangePoint($MemberID);
    	if($Point > $MaxUsePoint){
    		$tip = str_ireplace('[n]', $MaxUsePoint, L('MaxUsePointTip'));
    		$this->ApiReturn(null, $tip, 0, API_FORMAT);
    	}
    	 
    	$PointExchangeRate = intval($GLOBALS['Config']['POINT_EXCHANGE_RATE']);
    	if($PointExchangeRate <= 0){
    		$this->ApiReturn(null, L('PointExchangeRateInvalid'), 0, API_FORMAT);
    	}
    	$data['PointPrice'] = number_format(0-$Point/$PointExchangeRate, 2);
    	$this->ApiReturn(array('Data'=>$data), L('UsePointSuccess'), 1, API_FORMAT);
    }
    
    /**
     * 获取购物车数据
     */
    function GetCart(){
    	$MemberID = $this->checkToken();
    	$m = D('Admin/Cart');
    	$data = $m->getCart(false, $MemberID);
    	app_relative_to_absolute($data, 'ProductPicture');
    	
    	if(!empty($data)){
	    	//计算扩展字段
	    	$m1 = D('Admin/TypeAttributeValue');
	    	foreach ($data as $k=>$v){
	    		$result = $m1->getAttributeByAttributeValueID( $v['AttributeValueID'] );
	    		$ProductPrice = ($v['ProductPrice'] + $result['TotalPrice']) * $GLOBALS['DiscountRate'];
	    		//计算扩展字段，保留2位小数
	    		$data[$k]['ProductPrice'] = sprintf("%.2f", $ProductPrice);
	    		$data[$k]['TotalItemPrice'] = sprintf("%.2f", $v['ProductQuantity'] * $ProductPrice );
	    		$data[$k]['ProductAttributes'] = $result['Attributes'];
	    		$data[$k]['ProductUrl'] = InfoUrl($v['ProductID'], $v['Html'], $v['LinkUrl'],  false, $v['ChannelID']);
	    	}
    	}
    	$this->ApiReturn(array('Data'=>$data), '', 1, API_FORMAT);
    }
    
    /**
     * 获取最后一次订单的收货人信息
     */
    function GetLatestConsignee(){
    	$MemberID = $this->checkToken();
    	$m = D('Admin/Order');
    	$data['Data'] = $m->getLatestConsignee($MemberID);
    	$this->ApiReturn($data, '', 1, API_FORMAT);
    }
    
    /**
     * 设置默认收货地址
     */
    function SetDefaultConsignee(){
    	$this->checkSign();
    	$p['MemberID'] = $this->checkToken();
    	$m = D('Admin/Consignee');
    	$b = $m->setDefaultConsignee($_POST['ConsigneeID'], $p);
    	if($b){
    		$this->ApiReturn(null, '', 1, API_FORMAT);
    	}else{
    		$this->ApiReturn(null, '', 0, API_FORMAT);
    	}
    	
    }
    
    /**
     * 获取默认收货人信息
     */
    function GetDefaultConsignee(){
    	$MemberID = $this->checkToken();
    	$m = D('Admin/Consignee');
    	$data['Data'] = $m->getDefaultConsignee($MemberID);
    	$this->ApiReturn($data, '', 1, API_FORMAT);
    }
    
    /**
     * 获取收货人信息
     */
    function GetConsignee(){
    	$p['MemberID'] = $this->checkToken();
    	$m = D('Admin/Consignee');
    	$p['IsEnable'] = 1;
    	$data['Data'] = $m->getConsignee($p);
    	$this->ApiReturn($data, '', 1, API_FORMAT);
    }
    
    /**
     * 查找收货地址
     */
    public function FindConsignee(){
    	$p['MemberID'] = $this->checkToken();
    	$m = D('Admin/Consignee');
    	$data = $m->findConsignee($_REQUEST['ConsigneeID'], $p); //可以是id或文件名
    	$this->ApiReturn(array('Data'=>$data), '', 1, API_FORMAT);
    }
    
    /**
     * 添加收货人
     */
    public function AddConsignee(){
    	$this->checkSign();
    	$_POST['MemberID'] = $this->checkToken();
    	$_POST['LanguageID'] = API_LANGUAGE_ID;
    	$m = D('Admin/Consignee');
    	$b = $m->add($_POST);
    	if($b){
    		$data['Data'] = $b;
    		$this->ApiReturn($data, L('SaveSuccess'), 1, API_FORMAT);
    	}else{
    		$this->ApiReturn(null, L('SaveFail'), 0, API_FORMAT);
    	}
    }
    
    /**
     * 删除收货人
     */
    public function DeleteConsignee(){
    	$this->checkSign();
    	$p['MemberID'] = $this->checkToken();
    	$m = D('Admin/Consignee');
    	//$_POST['ConsigneeID']为数组，则支持批量删除
    	$b = $m->delConsignee($_POST['ConsigneeID'], $p);
    	if($b){
    		$this->ApiReturn(null, L('DelSuccess'), 1, API_FORMAT);
    	}else{
    		$this->ApiReturn(null, L('DelFail'), 0, API_FORMAT);
    	}
    }
    
    /**
     * 保存收货人
     */
    public function SaveConsignee(){
        $this->checkSign();
    	$_POST['MemberID'] = $this->checkToken();
    	$m = D('Admin/Consignee');
    	if( $m->create() ){
    		if(false === $m->save()){
    			$this->ApiReturn(null, L('SaveFail'), 0, API_FORMAT);
    		}else{
    			$this->ApiReturn(null, L('SaveSuccess'), 1, API_FORMAT);
    		}
    	}else{
    		$this->ApiReturn(null, L('SaveFail'), 0, API_FORMAT);
    	}
    }
    
    /**
     * 获取收货时间列表
     */
    function GetDeliveryTime(){
    	$data['Data'] = get_deliverytime();
    	$this->ApiReturn($data, '', 1, API_FORMAT);
    }
    
    
    /**
     * 获取配送信息
     */
    function GetShipping(){
    	$data['Data'] = get_shipping();
    	$this->ApiReturn($data, '', 1, API_FORMAT);
    }
    
    /**
     * 获取支付信息
     */
    function GetPay(){
    	$data = array();
    	if(CLIENT_TYPE==2){ //获取小程序端的支付方式信息
    		
    	}elseif(CLIENT_TYPE==1){ //获取APP端支付方式信息
    		
    	}else{
    		$data['Data'] = get_pay();
    	}
    	$this->ApiReturn($data, '', 1, API_FORMAT);
    }
    
    /**
     * 获取价格范围
     */
    function GetPriceRange(){
    	$ChannelID = isset($_REQUEST['ChannelID']) ? $_REQUEST['ChannelID'] : -1;
    	$Count = isset($_REQUEST['Count']) ? $_REQUEST['Count'] : 5;
    	$data['Data'] = get_price_range($ChannelID, $Count);
    	$this->ApiReturn($data, '', 1, API_FORMAT);
    }
    
    /**
     * 获取类型属性
     */
    function GetTypeAttribute(){
    	//类别，1：所有属性、2：规格属性、3：检索条件属性
    	$Type = isset($_REQUEST['Type']) ? $_REQUEST['Type'] : 1;
    	$ChannelID = isset($_REQUEST['ChannelID']) ? $_REQUEST['ChannelID'] : -1;
    	$SpecialID = isset($_REQUEST['SpecialID']) ? $_REQUEST['SpecialID'] : -1;
    	$MinPrice = isset($_REQUEST['MinPrice']) ? $_REQUEST['MinPrice'] : -1;
    	$MaxPrice = isset($_REQUEST['MaxPrice']) ? $_REQUEST['MaxPrice'] : -1;
    	$InfoID = isset($_REQUEST['InfoID']) ? $_REQUEST['InfoID'] : -1;
    	
    	$data = get_type_attribute($Type, $InfoID, $ChannelID, $SpecialID, $MinPrice, $MaxPrice);
    	//这里返回数组的下标是属性分组的ID，如：1,10,8，在转化为json对象后，排序会重新改变为：1,8,10
    	//为了保持顺序，必须转化
    	$result['Data'] = array();
    	foreach ($data as $v){
    		$result['Data'][] = $v;
    	}
    	//==============================================================
    	$this->ApiReturn($result, '', 1, API_FORMAT);
    }
    
    /**
     * 获取已选择的属性
     */
    function GetSelectedAttribute(){
    	//$Attr属性以下划线分开，如:12_33
    	$Attr = isset($_REQUEST['Attr']) ? $_REQUEST['Attr'] : '';
    	$SpecialID = isset($_REQUEST['SpecialID']) ? $_REQUEST['SpecialID'] : -1;
    	$MinPrice = isset($_REQUEST['MinPrice']) ? $_REQUEST['MinPrice'] : -1;
    	$MaxPrice = isset($_REQUEST['MaxPrice']) ? $_REQUEST['MaxPrice'] : -1;
    	$data['Data'] = get_selected_attribute($Attr, $SpecialID, $MinPrice, $MaxPrice);
    	$this->ApiReturn($data, '', 1, API_FORMAT);
    }
    
    /**
     * 获取排行榜
     */
    function GetTop(){
    	$Type = isset($_REQUEST['Type']) ? $_REQUEST['Type'] : 'sales';  //默认按销量排序
    	$ChannelID = isset($_REQUEST['ChannelID']) ? $_REQUEST['ChannelID'] : -1;
    	$Top = isset($_REQUEST['Top']) ? intval($_REQUEST['Top']) : -1;
    	$Order = (isset($_REQUEST['Order']) && $_REQUEST['Order']=='asc') ? 'asc' : 'desc';
    	$data['Data'] = get_top($ChannelID, $Type, $Top, $Order);
    	$this->ApiReturn($data, '', 1, API_FORMAT);
    }
    
    /**
     * 保存订单
     */
    public function SaveOrder(){
        $this->checkSign();
    	$MemberID = $this->checkToken();
    	$ShippingID = $_POST['ShippingID'];  //配送方式
    	//如果是小程序，则默认为"微信支付"
    	$PayID = (CLIENT_TYPE==2) ? 10 : $_POST['PayID'];
    	$c = &$GLOBALS['Config'];
    	 
    	//验证码显示 开始================================
    	//小程序、APP端没有验证码
    	//验证码显示 结束================================
    	 
    	//提交订单前，判断购物车是否为空
    	$mc = D('Admin/Cart');
    	if( $mc->isEmpty($MemberID) ){
    		$this->ApiReturn(null, L('OrderFail'), 0, API_FORMAT);
    	}
    	$TotalPrice = $mc->getTotalPrice(false, $MemberID); //订单中商品总金额
    	 
    	//判断优惠券的有效性========================================
    	$CouponType = intval($_REQUEST['CouponType']);
    	$CouponPrice = 0; //优惠券抵扣金额
        $result = false;
        $mcs = D('Admin/CouponSend');
        $CouponSendID = 0;
    	switch($CouponType){
    		case 1: //会员优惠券
    			$CouponSendID = intval($_REQUEST['CouponSendID']);
    			if($CouponSendID > 0){
    				$result = $mcs->checkCoupon($CouponSendID);
    			}
    			break;
    		case 2:  //线下优惠券
    			$CouponCode = trim($_REQUEST['CouponCode']);
    			if(!empty($CouponCode)){
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
    	$sumPoint = $mc->sumPoint($MemberID,3);
    	$Point = intval($_REQUEST['Point']);
    	$PointExchangeRate = intval($GLOBALS['Config']['POINT_EXCHANGE_RATE']);
    	$PointPrice = 0;
    	if($Point>0 && $PointExchangeRate>0){
    		$TotalPoint = $mp->getTotalPoint($MemberID); //获取总积分
    		//输入的积分不能大于总积分
    		if($Point > $TotalPoint){
    			$this->ApiReturn(null, L('GtTotalPoint'), 0, API_FORMAT);
    		}
    		 
    		//判断不能大于最大可以使用的积分
    		$MaxUsePoint = $sumPoint['ExchangePoint'];
    		if($Point > $MaxUsePoint){
    			$tip = str_ireplace('[n]', $MaxUsePoint, L('MaxUsePointTip'));
    			$this->ApiReturn(null, $tip, 0, API_FORMAT);
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
    			$b = $mc->saveOrderProduct($OrderID, $MemberID); //保存订购商品
    			if($b){
    				$mc->clearCart($MemberID);  //清空购物车
    			}else{
    				$this->ApiReturn(null, L('OrderFail'), 0, API_FORMAT);
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
    			 
    			$msg['MemberID'] = $MemberID;
    			$msg['OrderID'] = $OrderID;
    			$msg['OrderNumber'] = $OrderNumber;
    			$msg['TotalPrice'] = $TotalPrice;
    			$msg['PayPrice'] = $PayPrice;
    			$msg['ShippingPrice'] = $ShippingPrice;
    			$msg['CouponPrice'] = $CouponPrice; //优惠券抵扣
    			$msg['TotalOrderPrice'] = $TotalOrderPrice;
    			//$msg['PayUrl'] = PayUrl($OrderID);
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
    			//$this->makeXcxPayParams($msg);
    			
    			$this->ApiReturn(array('Data'=>$msg), L('OrderSuccess'), 1, API_FORMAT);
    		}else{
    			$this->ApiReturn(null, L('OrderFail'), 0, API_FORMAT);
    		}
    	}
    }
    
    /**
     * 小程序微信支付
     * @param array $data
     */
    public function RequestPayment(){
    	if(CLIENT_TYPE == 2 ){
    		$MemberID = $this->checkToken();
    		$m = D('Admin/Order');
    		$OrderID = intval($_REQUEST['OrderID']);
    		$data = $m->findOrder($OrderID, array('MemberID'=>$MemberID));
    		//PayStatus：2：未支付、1：已支付
    		if($data['PayStatus'] == '2'){
    			import("@.Common.YdPay");
    			$config['AccountID'] = $GLOBALS['Config']['XCX_APP_ID']; //公众账号ID
    			$config['AccountName'] = $GLOBALS['Config']['XCX_ACCOUNT_NAME']; //微信支付商户号
    			$config['AccountKey'] = $GLOBALS['Config']['XCX_ACCOUNT_KEY']; //微信支付
    			$config['OrderNumber'] = $data['OrderNumber'];       //订单编号
    			$config['TotalOrderPrice'] = $data['TotalOrderPrice'];  //订单总金额
    			$config['OpenID'] = D('Admin/Member')->where("MemberID={$MemberID}")->getField('OpenID');
    			$obj = new YdXcxPay($config);
    			$payParams = $obj->getPayUrl();
    			if(!empty($payParams)){
    				$this->ApiReturn(array('Data'=>$payParams), '', 1, API_FORMAT);
    			}else{
    				$this->ApiReturn(false, L('GetPayParameterFail'), 0, API_FORMAT);
    			}
    		}else{
    			$this->ApiReturn(false, L('OrderPayed'), 0, API_FORMAT);
    		}
    	}
    }
    
    /**
     * 充值
     */
    public function Recharge(){
    	$this->checkSign();
    	$MemberID = $this->checkToken();
    	$CashQuantity = (double)($_REQUEST['CashQuantity']);
    	if($CashQuantity <= 0 ){
    		$this->ApiReturn(null, L('MoneyGreaterThanZero'), 0, API_FORMAT);
    	}
    	$PayTypeID = 10;
    	$mp = D('Admin/Pay');
    	$PayID = $mp->where("PayTypeID={$PayTypeID}")->getField('PayID');
    	if(empty($PayID)) $PayID = 0;
    	//插入充值记录========================
    	$m = D('Admin/Cash');
    	$cash['MemberID'] = $MemberID;
    	$cash['CashQuantity'] = $CashQuantity;
    	$cash['CashType'] = 1;
    	$cash['CashStatus'] = 2;
    	$cash['CashTime'] = date('Y-m-d H:i:s');
    	$cash['PayID'] = $PayID; //10：微信支付
    	$cash['CashRemark'] = $_REQUEST['CashRemark'];
    	$CashID = $m->add($cash);
    	//=================================
    	if($CashID>0){
	    	$PayRate = 0; //支付手续费，1：表示没有手续费
	    	//当前充值总费用
	    	$TotalOrderPrice = sprintf("%.2f", $CashQuantity + $CashQuantity * $PayRate);
	    	//构造一个唯一的订单号
	    	$OrderNumber = 'ZXCZ'.date('YmdHis').'_'.$CashID;
	    	$mm = D('Admin/Member');
	    	$openid = $mm->where("MemberID={$MemberID}")->getField('OpenID');

    		import("@.Common.YdPay");
    		$config['AccountID'] = $GLOBALS['Config']['XCX_APP_ID']; //公众账号ID
    		$config['AccountName'] = $GLOBALS['Config']['XCX_ACCOUNT_NAME']; //微信支付商户号
    		$config['AccountKey'] = $GLOBALS['Config']['XCX_ACCOUNT_KEY']; //微信支付
    		$config['OrderNumber'] = $OrderNumber;       //订单编号
    		$config['TotalOrderPrice'] = $TotalOrderPrice;  //订单总金额
    		$config['OpenID'] = $openid;
    		$obj = new YdXcxPay($config);
    		$payParams = $obj->getPayUrl();
    		if(!empty($payParams)){
    			$this->ApiReturn(array('Data'=>$payParams), '', 1, API_FORMAT);
    		}else{
    			$this->ApiReturn(false, L('GetPayParameterFail'), 0, API_FORMAT);
    		}
    	}else{
    		$this->ApiReturn(false, L('RechargeFail'), 0, API_FORMAT);
    	}
    }
    
    /**
     * 会员获取订单
     */
    public function GetOrder(){
    	$this->checkSign();
    	$MemberID = $this->checkToken();
    	/*  订单状态  1：等待付款    2：待发货   3：待收货   4：已退款   5：已退货   6：已完成   7：已作废   8：已取消    */
    	$params['OrderStatus'] = $_REQUEST['OrderStatus'];
    	$params['MemberID'] = $MemberID;
    	$params['LanguageID'] = API_LANGUAGE_ID;
    	$NowPage = isset($_POST['NowPage']) ? (int)$_POST['NowPage'] : 1;
    	$PageSize = isset($_POST['PageSize']) ? (int)$_POST['PageSize'] : 20;
    	if(empty($MemberID)){
    		$result['Data'] = false;
    		$result['Total'] = 0;
    		$result['PageSize'] = $PageSize;  //分页大小
    		$result['PageCount'] =0;
    		$result['NowPage'] = 1;
    		$result['HasNextPage'] = 0;
    	}else{
	    	$m = D('Admin/Order');
	    	$Total = $m->getOrderCount($params);
	    	//初始化结果
	    	$result = array('Data' => false, 'PageSize' => $PageSize, 'PageCount' => 0, 'NowPage' => $NowPage, 'HasNextPage' => 0 );
	    	if( $Total > 0 ) {
	    		$offset = ($NowPage - 1 > 0) ? ($NowPage - 1)*$PageSize : 0;
	    		$data = $m->getOrder($offset, $PageSize, $params);
	    		if( !empty($data)){
	    			$this->GetOrderProduct($data); //获取订单关联的商品信息
	    			$result['Data'] = $data;  //数据
	    			$result['Total'] = $Total;  //信息总条数
	    			$result['PageSize'] = $PageSize;  //分页大小
	    			$result['PageCount'] = ceil($Total/$PageSize); //总页数
	    			$result['NowPage'] = $NowPage;
	    			$result['HasNextPage'] = ( $NowPage >= $result['PageCount']) ? 0 : 1;
	    		}
	    	}
    	}
    	$this->ApiReturn($result, '', 1, API_FORMAT);
    }
    
    /**
     * 订单统计
     */
    public function StatOrder(){
    	$m = D('Admin/Order');
    	$this->checkSign();
    	$MemberID = $this->checkToken();
    	$result['Data'] = $m->statOrder($MemberID);
    	//$result['Data']的健就是订单状态ID，健值就是订单统计值
    	// 1：新订单=等待付款、2：已付款=待发货、3：已发货=待收货、4：退款=已退款、
		// 5：退货=已退货、6：结单=已完成、7：作废=已作废、8：已取消=已取消
    	$this->ApiReturn($result, '', 1, API_FORMAT);
    }
    
    /**
     * 查找订单
     */
    public function FindOrder(){
    	$this->checkSign();
    	$MemberID = $this->checkToken();
    	$OrderID = $_REQUEST['OrderID'];
	    $params['MemberID'] = $MemberID;
	    $m = D('Admin/Order');
	    $result['Data'] = $m->findOrder($OrderID, $params);
	    if(!empty( $result['Data'] )){
	    	$md = D('Admin/DeliveryTime');
	    	$where="DeliveryTimeID=".$result['Data']['DeliveryTimeID'];
	    	$DeliveryTimeName = $md->where($where)->getField('DeliveryTimeName');
	    	$result['Data']['DeliveryTimeName'] = $DeliveryTimeName;
	    }
	    $this->GetOrderProduct($result['Data']); //获取订单关联的商品信息
    	$this->ApiReturn($result, '', 1, API_FORMAT);
    }
    
    /**
     * 获取订单的产品信息
     * @param array $data 订单二维或一维数据
     */
    private function GetOrderProduct(&$data){
    	if(empty($data)) return;
    	$m = D('Admin/OrderProduct');
    	if( isset($data['OrderID']) ){ //如果是一维订单数据
    		$Product = $m->getOrderProduct($data['OrderID']);
    		if( !empty($Product)){
    			$n1 = count($Product);
    			for($i=0; $i < $n1; $i++){
    				$Product[$i]['ProductPicture'] = InfoPicture($Product[$i]['ProductID']);
    			}
    			app_relative_to_absolute($Product, 'ProductPicture');
    		}
    		$data['Products'] = $Product;
    	}else{
	    	$n = is_array($data) ? count($data) : 0;
	    	for($i=0; $i < $n; $i++){
	    		if( $data[$i]['PayStatus'] == 2 ){ //获取支付链接
	    			//$data[$i]['PayUrl'] = PayUrl($data[$i]['OrderID']);
	    		}
	    		
	    		//获取订单产品相关信息
	    		$Product = $m->getOrderProduct($data[$i]['OrderID']);
	    		if( !empty($Product)){
	    			$n1 = count($Product);
	    			for($j=0; $j < $n1; $j++){
	    				$Product[$j]['ProductPicture'] = InfoPicture($Product[$j]['ProductID']);
	    			}
	    			app_relative_to_absolute($Product, 'ProductPicture');
	    		}
	    		$data[$i]['Products'] = $Product;
	    		$data[$i]['ProductCount'] = count($data[$i]['Product']);
	    	}
    	}
    }
    
    /**
     * 会员删除订单
     */
    public function DeleteOrder(){
    	$this->checkSign();
    	$MemberID = $this->checkToken();
    	$m = D('Admin/Order');
    	$p['MemberID'] = $MemberID;
    	$b = $m->delOrder($_REQUEST['OrderID'], $p);
    	if($b!==false){
    		$this->ApiReturn(null, L('DelOrderSuccess'), 1, API_FORMAT);
    	}else{
    		$this->ApiReturn(null, L('DelOrderFail'), 0, API_FORMAT);
    	}
    }
    
    /**
     * 会员取消订单
     */
    public function CancelOrder(){
    	$this->checkSign();
    	$MemberID = $this->checkToken();

	    $m = D('Admin/Order');
	    $p['MemberID'] = $MemberID;
	    $p['MemberName'] = D('Admin/Member')->where("MemberID=".$p['MemberID'])->getField('MemberName');
	    $b = $m->cancelOrder($_REQUEST['OrderID'], $p);
	    $this->ApiReturn(null, L('CancelOrderSuccess'), 1, API_FORMAT);
    }
    
    /**
     * 会员确认收货
     */
    public function ConfirmReceipt(){
    	$this->checkSign();
    	$MemberID = $this->checkToken();

	    $m = D('Admin/Order');
	    $p['MemberID'] = $MemberID;
	    $p['MemberName'] = D('Admin/Member')->where("MemberID=".$p['MemberID'])->getField('MemberName');
	    $b = $m->confirmReceipt($_REQUEST['OrderID'], $p);
	    $this->ApiReturn(null, L('ConfirmReceiptSuccess'), 1, API_FORMAT);
    }
    
    /**
     * 会员支付成功以后，置为已支付状态
     */
    /*
    public function SetPayStatus(){
    	$this->checkSign();
    	$MemberID = $this->checkToken();
    	$m = D('Admin/Order');
    	$p['MemberID'] = $MemberID;
    	$b = $m->setPayStatus($_REQUEST['OrderID'], 1);
    	$this->ApiReturn(null, '已支付状态设置成功', 1, API_FORMAT);
    }
    */
    
    /**
     * Ajax数据返回
     * @param mix $data 返回的数据
     * @param string $info 状态文本信息
     * @param int $status 状态 1：成功，0：失败
     * @param string $format 返回的格式，如：json，xml
     */
    private function ApiReturn($data, $msg='', $status=1, $format='json') {
    	$data['Status'] = $status;
    	$data['Message'] = $msg;
    	if(empty($format)) $format = 'json';
    	if(API_SHOW_TIME){
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
    	//表示获取配置数据
    	if(API_HAS_CONFIG){
    		$data['Config']= $this->GetConfigData();
    	}
    	$data['Timestamp'] = time(); //返回服务器时间戳给客户端
    	switch(strtoupper($format)){
    		case 'JSON':
    			header('Content-Type:text/html; charset=utf-8');
    			$this->OutuptAllOrginHeader();
                $options = version_compare(PHP_VERSION, '5.4.0', '>=') ? JSON_UNESCAPED_UNICODE : 0;
    			$data = json_encode($data, $options);
    			exit($data);
    		//case 'JSONP':  //有很大的风险
    		//	header('Content-Type:text/html; charset=utf-8');
    		//	$data = json_encode($data);
    		//	exit(API_JSONP_CALLBACK."({$data});");
    		case 'XML':
    			header('Content-Type:text/xml; charset=utf-8');
    			$this->OutuptAllOrginHeader();
    			$data = xml_encode($data);
    			exit($data);
    		default:
    			exit();
    	}
    }
    
    /**
     * 是否允许输出Access-Control-Allow-Origin头
     */
    private function OutuptAllOrginHeader(){
    	$bOutput = false;
    	if(CLIENT_TYPE==3){ //如果是本地调用，则不进行跨域处理
    		$bOutput = true;
    	}else if(API_SIGN) {
    		$bOutput = true;
    	}else{
    		//下面这些函数任何人都可以调用，不需要控制权限，需要输出跨域头
    		$list = array('findappmessage'=>'', 'findinfo'=>'', 'getappmessage'=>'','getchannel'=>'',
    		'getcomment'=>'','getconfig'=>'','getinfo'=>'','statcomment'=>'','findchannel'=>'', 'getspecial'=>'', 'getlabel'=>1);
    		$name = strtolower(ACTION_NAME);
			if(key_exists($name, $list)){
				$bOutput = true;
			}
    	}
    	if($bOutput){
    		header("Access-Control-Allow-Origin: *");
    	}
    }
    
    /**
     * 通过单号查询快递
     */
    public function QueryExpress(){
    	$nu = trim( $_REQUEST['Number'] ); //快递单号
    	$url = "https://www.kuaidi100.com/autonumber/autoComNum?text={$nu}";
    	//正确：{"comCode":"","num":"603938693","auto":[{"comCode":"jd","id":"","noCount":909,"noPre":"60393","startTime":""}]}
    	//没有找到：{"comCode":"","num":"343","auto":[]}
    	$result = yd_curl_get($url);
    	$result = json_decode($result, true);
    	if( isset($result['auto'][0]['comCode']) && !empty($result['auto'][0]['comCode']) ){
    		$comCode = $result['auto'][0]['comCode'];
    		$api = "https://www.kuaidi100.com/query?type={$comCode}&postid={$nu}";
    	    //$api="https://www.kuaidi100.com/query?type=jd&postid=603938693961&id=1&valicode=&temp=0.3486660116116519";
    		$result = yd_curl_get($api);
    		/*
    		 * {"message":"ok","nu":"60393869396","ischeck":"1","condition":"F00","com":"jd","status":"200","state":"3",
    		 * "data":[
    		 * {"time":"2017-08-23 14:39:13","ftime":"2017-08-23 14:39:13","context":"货物已完成配送，感谢您选择京东配送","location":""},
    		 * {"time":"2017-08-23 08:42:29","ftime":"2017-08-23 08:42:29","context":"配送员开始配送，配送员，李云耀，手机号，13467598245","location":""},
    		 * {"time":"2017-08-23 08:00:42","ftime":"2017-08-23 08:00:42","context":"货物已分配，等待配送","location":""},
    		 * {"time":"2017-08-23 08:00:41","ftime":"2017-08-23 08:00:41","context":"货物已到达【星沙站】","location":""},
    		 * {"time":"2017-08-22 23:38:39","ftime":"2017-08-22 23:38:39","context":"货物已完成分拣，离开【长沙分拨中心】","location":""},
    		 * {"time":"2017-08-22 23:36:43","ftime":"2017-08-22 23:36:43","context":"货物已到达【长沙分拨中心】","location":""},
    		 * {"time":"2017-08-22 23:36:22","ftime":"2017-08-22 23:36:22","context":"货物已到达【长沙分拨中心】","location":""},
    		 * {"time":"2017-08-22 15:51:34","ftime":"2017-08-22 15:51:34","context":"货物已完成分拣，离开【武汉亚一分拣中心】","location":""},
    		 * {"time":"2017-08-22 15:51:04","ftime":"2017-08-22 15:51:04","context":"货物已交付京东快递","location":""}
    		 * ]
    		 * }
    		 */
    		$data['Data'] = json_decode($result, true);
    		$this->ApiReturn($data, '', 1, API_FORMAT);
    	}else{
    		$this->ApiReturn(null, '', 0, API_FORMAT);
    	}
    }

    /**
     * 检查管理员是否登录
     */
    private function checkAdminLogin(){
        $AdminID = intval(session("AdminID"));
        if(empty($AdminID)){
            $this->ApiReturn(null, '登录超时，请重新登录！', 0, API_FORMAT);
        }
        return $AdminID;
    }

    //=======================多端小程序前端 开始=======================//
    /**
     *  获取留言模型
     */
    public function GetGuestbookModel(){
        $data['Data'] = get_model(6);
        $this->ApiReturn($data, '', 1, API_FORMAT);
    }

    /**
     *  获取模型模型
     */
    public function GetFeedbackModel(){
        $data['Data'] = get_model(37);
        $this->ApiReturn($data, '', 1, API_FORMAT);
    }

    /**
     * 小程序配置（最先被调用）
     */
    public function GetXcxConfig(){
        $config = array();
        $config['Debug'] = APP_DEBUG ? 1 : 0; //控制APP客户端是否启用调试模式
        $config['WebInstallDir'] = $this->WebInstallDir;

        $IsAdminLogin = intval(session("AdminID"))>0 ? true : false;
        if($IsAdminLogin){
            $AdminLoginName = C('ADMIN_LOGIN_NAME');
            if(empty($AdminLoginName)) $AdminLoginName = 'Admin';
            $FileManagerUrl = __APP__."/{$AdminLoginName}/Config/fileManager";
            if($config['Debug']){
                $domain = get_current_url();
                $FileManagerUrl = "{$domain}{$FileManagerUrl}";
            }
            $config['FileManagerUrl'] = $FileManagerUrl;
        }else{
            $config['FileManagerUrl'] = '';
        }

        $config['IsOem'] = ('YouDianCMS'==C('CMSEnName')) ? 0 : 1;
        if(isset($_POST['HasTabbar']) && $_POST['HasTabbar']==1){
            $m = D('Admin/TemplatePage');
            $TemplateID = isset($_POST['TemplateID']) ? $_POST['TemplateID'] : 0;
            $XcxType = intval($_REQUEST['XcxType']);
            $IsPreview = ($TemplateID>0) ? 1 : 0; //是否是预览，如果是则调用草稿内容
            $config['Tabbar'] = $m->getTabbar($TemplateID, false, $IsPreview, $XcxType);
            $m = D('Admin/Template');
            $config['ThemeColor'] = $m->getThemeColor($TemplateID,$XcxType);
            //处理Tabbar===============================================
            if(!empty($config['Tabbar'])){
                import('@.Common.YdTemplate');
                $t = new YdTemplate();
                $t->addDomain(true);  //终端使用带域名的全路径，否则h5终端无法显示图像
                $t->parseTabbar($config['Tabbar']);
            }
            //=======================================================
        }
        //获取多端小程序配置
        if(!empty($_POST['HasMultiXcxConfig'])){
            $m = D('Admin/Config');
            $xcxConfig = $m->getMultiXcxConfig();
            if($IsAdminLogin){
                $m = D('Admin/Secret');
                $cmsConfig = $m->findMultiXcxSecret();
            }else{
                $cmsConfig = array();
            }
            $config = array_merge($config, $xcxConfig, $cmsConfig);
            $config['Domain'] = get_current_url(false);
        }
        $data['Data'] = $config;
        $this->ApiReturn($data, '', 1, API_FORMAT);
    }

    /**
     * 【小程序端和H5端】获取模板页面数据
     */
    function getPageData($isAjax=true){
        $m = D('Admin/TemplatePage');
        $XcxType = intval($_REQUEST['XcxType']);
        //如果参数TemplateID存在，就是预览模式
        $TemplateID = isset($_POST['TemplateID']) ? (int)$_POST['TemplateID'] : 0;
        $IsPreview = ($TemplateID > 0) ? 1 : 0; //是否是预览，如果是则调用草稿内容
        $TemplatePageID = isset($_POST['TemplatePageID']) ? (int)$_POST['TemplatePageID'] : 0;
        if(empty($TemplatePageID)){
            //TemplateID不为空表示，调用指定模板的数据，而不是默认模板
            $type = intval($_POST['TemplatePageType']);
            $TemplatePageID = $m->findDefaultTemplatePageID($type, $TemplateID, $XcxType);
        }
        $pageData = $m->findTemplatePageContent($TemplatePageID, $IsPreview);
        if(!empty($pageData)){
            import('@.Common.YdTemplate');
            $t = new YdTemplate();
            $t->addDomain(true);  //终端使用带域名的全路径，否则h5终端无法显示图像
            $result['PageData'] = $t->parsePageData($pageData);
        }else{
            $result = array();
        }
        //记录统计数据========================================
        if($XcxType>0){
            $m = D('Admin/Visitor');
            $params = array('XcxType'=>$XcxType);
            $m->addVisitor($params);
        }
        //================================================
        if($isAjax){
            $this->ApiReturn(array('Data'=>$result), '', 1, API_FORMAT);
        }else{ //主要在GetInfo中调用
            return $result['PageData'];
        }
    }

    /**
     * 在线留言
     */
    function AddGuestbook(){
        $this->checkSign();
        $MemberID = $this->checkToken(true);
        $c = &$GLOBALS['Config'];
        $ma = D('Admin/Attribute');
        $Attributes = $ma->getAttribute(6);
        $this->_checkGuestbook($Attributes);
        $m = D('Admin/Guestbook');
        if( $m->create() ){
            if( $MemberID>0 ){
                $m->GuestID = $MemberID;
                $m->GuestName = MemberName($MemberID);
                $_POST['GuestName'] = $m->GuestName;
            }else{
                $m->GuestID = 0;
            }
            $m->IsCheck = ($c['GUEST_BOOK_CHECK'] == 1) ? 0 : 1;
            $MessageTime = date('Y-m-d H:i:s');
            $m->MessageTime = $MessageTime;
            $m->GuestIP = get_client_ip();

            if($m->add()){
                $emailbody = '';
                $success = ($c['GUEST_BOOK_CHECK'] == 1) ? L('GuestbookSuccess') : L('GuestbookSuccessNoCheck');
                //留言邮件 开始=============================================================
                if( $c['GUEST_BOOK_SENDEMAIL'] == 1){
                    //获取留言内容====
                    foreach ($Attributes as $a){
                        $name = explode(',', $a['DisplayName']);
                        $value = $_POST[ $a['FieldName'] ];
                        if(  $value != '' ){
                            $emailbody .= $name[0].':'.$value.'<br/>';
                        }
                    }
                    $emailbody .= '时间:'.$MessageTime;
                    $emailtitle = $c['GUEST_BOOK_EMAIL_TITLE'];
                    $emailto = empty($c['GUEST_BOOK_EMAIL']) ? $c['EMAIL'] : $c['GUEST_BOOK_EMAIL'];
                    //====
                    //提交留言时自动发送电子邮件
                    $b = sendwebmail($emailto, $emailtitle, $emailbody);
                }
                //留言邮件 结束=============================================================

                //短信通知 开始=====================================
                if( $c['GUEST_BOOK_SMS'] == 1){
                    //获取留言内容
                    $GuestBookSmsTemplate = $c['GUEST_BOOK_SMS_TEMPLATE'];
                    if( empty( $emailbody) && stripos($GuestBookSmsTemplate, '{$Content}')){
                        foreach ($Attributes as $a){
                            $name = explode(',', $a['DisplayName']);
                            $value = $_POST[ $a['FieldName'] ];
                            if(  $value != '' ){
                                $emailbody .= $name[0].':'.$value.'<br/>';
                            }
                        }
                    }
                    $placeholder = array('{$Time}'=>$MessageTime, '{$Content}'=>$emailbody);
                    send_sms($c['GUEST_BOOK_SMS_TO'], $GuestBookSmsTemplate, $placeholder);
                    //短信通知 结束============================================
                }
                //==========================================
                $this->ApiReturn(null, $success , 1, API_FORMAT);
            }else{
                $this->ApiReturn(null, L('GuestbookFail') , 0, API_FORMAT);
            }
        }else{
            $this->ApiReturn(null, $m->getError() , 0, API_FORMAT);
        }
    }

    private function _checkGuestbook($Attributes){
        foreach ($_POST as $k=>$v){
            if( is_array($v) ){
                $_POST[$k] = implode(',', $v);
            }
        }
        $_POST = YdInput::checkTextbox($_POST);
        $isAllow = intval($GLOBALS['Config']['GUEST_BOOK_ALLOW']);
        //留言权限
        switch($isAllow){
            case 0:  //禁止留言
                $this->ApiReturn(null, L('GuestbookForbidden') , 0, API_FORMAT);
                break;
            case 1: //允许匿名留言
                break;
            case 2: //允许会员留言
                if(empty($MemberID)){
                    $this->ApiReturn(null, L('GuestbookLevel') , 0, API_FORMAT);
                }
                break;
        }

        //有效性检查
        foreach($Attributes as $v){
            if(empty($v['IsRequire'])) continue;
            $name = $v['FieldName'];
            if(empty($_POST[$name])){
                $DisplayName = explode(',', $v['DisplayName']);
                $this->ApiReturn(null, "{$DisplayName[0]}不能为空！" , 0, API_FORMAT);
            }
        }

        //定义每个ID每天最大留言数
        $MaxPerIp = 50;
        $m = D('Admin/Guestbook');
        $ip = get_client_ip();
        $where = "to_days(MessageTime) = to_days(now()) and GuestIP='{$ip}'";
        $n = $m->where($where)->count();
        if( $n > $MaxPerIp){
            $this->ApiReturn(null, L('GuestbookFail') , 0, API_FORMAT);
        }
    }

    /**
     * 反馈提交
     */
    public function AddFeedback(){
        $this->checkSign();
        $MemberID = $this->checkToken(true);
        $c = &$GLOBALS['Config'];
        $ma = D('Admin/Attribute');
        $Attributes = $ma->getAttribute(37);
        $ChannelID = $this->_checkFeedback($Attributes);
        $m = D('Admin/Info');
        if( $m->create() ){
            $m->ChannelID =$ChannelID;
            $m->MemberID =$MemberID;
            $m->IsCheck = ($c['FEEDBACK_CHECK'] == 1) ? 0 : 1;
            $m->IsEnable = 1;
            $InfoTime = date('Y-m-d H:i:s');
            $m->InfoTime = $InfoTime;
            $m->InfoIP = get_client_ip();
            if($m->add()){
                $emailbody = '';
                if( $c['FEEDBACK_SENDEMAIL'] == 1){
                    //获取留言内容===============================================================
                    $Group = $ma->getGroup(37);
                    foreach($Group as $g){
                        foreach ($Attributes as $a){
                            if( $g['AttributeID'] == $a['GroupID']){
                                $name = explode(',', $a['DisplayName']);
                                $value = $_POST[ $a['FieldName'] ];
                                if(  $value != '' ){
                                    $emailbody .= $name[0].':'.$value.'<br/>';
                                }
                            }
                        }
                    }
                    $emailbody .= '时间:'.$InfoTime;
                    $emailtitle = $c['FEEDBACK_EMAIL_TITLE'];
                    $emailto = empty($c['FEEDBACK_EMAIL']) ? $c['EMAIL'] : $c['FEEDBACK_EMAIL'];
                    //=======================================================＝===============
                    //提交留言时自动发送电子邮件
                    $b = sendwebmail($emailto, $emailtitle, $emailbody);
                }

                //短信通知 开始=====================================
                if( $c['FEEDBACK_SMS'] == 1){
                    //获取留言内容
                    $SmsTemplate = $c['FEEDBACK_SMS_TEMPLATE'];
                    if( empty( $emailbody) && stripos($SmsTemplate, '{$Content}')){
                        foreach ($Attributes as $a){
                            $name = explode(',', $a['DisplayName']);
                            $value = $_POST[ $a['FieldName'] ];
                            if(  $value != '' ){
                                $emailbody .= $name[0].':'.$value.'<br/>';
                            }
                        }
                    }
                    $placeholder = array('{$Time}'=>$InfoTime, '{$Content}'=>$emailbody);
                    send_sms($c['FEEDBACK_SMS_TO'], $SmsTemplate, $placeholder);
                    //短信通知 结束============================================
                }
                //==========================================
                $this->ApiReturn(null, L('FeedbackSuccess') , 1, API_FORMAT);
            }else{
                $this->ApiReturn(null, L('FeedbackFail') , 0, API_FORMAT);
            }
        }else{
            $this->ApiReturn(null, $m->getError() , 0, API_FORMAT);
        }
    }

    private function _checkFeedback(&$Attributes){
        //处理checkbox的情况
        foreach ($_POST as $k=>$v){
            if( is_array($v) ){
                $_POST[$k] = implode(',', $v);
            }
        }
        $_POST = YdInput::checkTextbox( $_POST ); //xss过滤

        //有效性检查
        foreach($Attributes as $v){
            if(empty($v['IsRequire'])) continue;
            $name = $v['FieldName'];
            if(empty($_POST[$name])){
                $DisplayName = explode(',', $v['DisplayName']);
                $this->ApiReturn(null, "{$DisplayName[0]}不能为空！" , 0, API_FORMAT);
            }
        }

        //获取反馈频道的频道ID（目前仅支持一个反馈频道）
        $m = D('Admin/Channel');
        $where = "ChannelModelID=37 AND IsEnable=1";
        $ChannelID = $m->where($where)->getField('ChannelID');
        if(empty($ChannelID)){
            $this->ApiReturn(null, "反馈频道不存在！" , 0, API_FORMAT);
        }

        //定义每个ID每天最大发布数
        $MaxPerIp = 50;
        $m = D('Admin/Info');
        $ip = get_client_ip();
        $ip = addslashes(stripslashes($ip)); //过滤危险字符
        $where = "to_days(InfoTime) = to_days(now()) AND InfoIP='{$ip}' AND ChannelID={$ChannelID}";
        $n = $m->where($where)->count();
        if( $n > $MaxPerIp){
            $this->ApiReturn(null, L('FeedbackFail') , 0, API_FORMAT);
        }
        return $ChannelID;
    }
    //=======================多端小程序 结束=======================//
}