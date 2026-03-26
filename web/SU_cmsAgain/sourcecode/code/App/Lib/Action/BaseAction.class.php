<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
if (!defined('APP_NAME')) exit();
class BaseAction extends Action {
	//系统变量
	public $DocumentRoot;   //网站物理根目录 如：D:/www
	public $WebInstallDir;     //网站安装目录，如：/YoudianCMS/
	public $WebPublic;          //网站公共目录, 如：/YoudianCMS/Public/
    public $IsOem=0;
	
	//模板变量
	public $Tpl;                 //当前系统模板目录(不包含主题名和分组名称) 如：/YouDianCMS/App/Tpl/
	public $GroupTpl;      // 当前分组模板目录 如：/YouDianCMS/App/Tpl/H__ROOT__ome
	public $Theme;           //当前主题目录 如：/YouDianCMS/App/Tpl/Home/t1/
	public $Public;            //模板公共目录 如：/YouDianCMS/App/Tpl/Home/t1/public/
	public $Css;                //模板Css目录 如：/YouDianCMS/App/Tpl/Home/t1/public/css
	public $Images;         //模板Images目录 如：/YouDianCMS/App/Tpl/Home/t1/public/images
	public $Js;                  //模板Js脚本目录 如：/YouDianCMS/App/Tpl/Home/t1/public/js
	public $Flash;            //模板Flash目录 如：/YouDianCMS/App/Tpl/Home/t1/public/flash
	
	//真实路径
	public $rTpl;
	public $rGroupTpl;
	public $rTheme;
	public $rCss;
	public $rConfig;
	
	//其它信息
	public $AdminPageSize = 20;  //后台分页大小, 默认为20
	public $AdminRollPage = 30;  //后台分页显示数, 默认为30
	
	function _initialize(){
		$this->_getPublicVar();      //获取公共变量
		$this->_assignPublicVar(); //公共变量模板赋值
		tag('baseaction_init', $this);
		//记录推荐码到session，不能放到StartWebBehavior里（session还没有初始化）,注意session对小程序无效
		if(!empty($_REQUEST['ic']) ){
		    $ic = YdInput::checkLetterNumber($_REQUEST['ic']);
			session('ic', $ic);
		}
	}
	
	public function assignValue($name, $value=''){
		$this->assign($name, $value);
	}
	
	public function getTemplateConfig(){
		$fileName = THEME_PATH.'config.xml';
		if(file_exists($fileName)){
			import("@.Common.YdTemplateConfig");
			$languageMark = get_language_mark();
			$tc = new YdTemplateConfig($fileName, $languageMark);
			$data = $tc->getData();
			//为了提高标签兼容性，使用单变量，而不是直接assign一个数组
			foreach ($data as $k=>$v){
				$this->assign($k, $v);
			}
		}
	}
	
	//输出当前cms版本号
	public function version(){
		header("Content-Type:text/html; charset=utf-8");
		$v = C('CMSVersion');
		echo $v;
	}
	
	//读取系统公共变量模板赋值
	private function _getPublicVar(){
		   //应用程序
		   $this->DocumentRoot = $_SERVER['DOCUMENT_ROOT'];
		   $this->WebInstallDir      =  __ROOT__.'/';
		   $this->WebPublic =  $this->WebInstallDir.'Public/';
		   
		   $Config = $this->WebInstallDir.substr(CONF_PATH, 2);
		   $WebUpload = $this->WebInstallDir.substr($GLOBALS['Config']['UPLOAD'], 2);
		   
		   $this->assign('Url', __URL__.'/');
		   $this->assign('WebUpload', $WebUpload );
		   $this->assign('Upload', $WebUpload );
		   
		   //模板
		   $this->Tpl = $this->WebInstallDir.substr(TMPL_PATH,2);
		   $this->ThemeName = THEME_NAME;
		   $this->GroupTpl = $this->WebInstallDir.substr(TMPL_PATH,2).GROUP_NAME.'/';
		   $this->Theme = APP_TMPL_PATH;
			
		   $this->Public = $this->Theme.'Public/';
		   $this->Css = $this->Public.'css/';
		   $this->Images = $this->Public.'images/';
		   $this->Js = $this->Public.'js/';
		   $this->Flash = $this->Public.'flash/';
		   
		   //真实路径
		   $this->rTpl = $this->DocumentRoot.$this->Tpl;
		   $this->rGroupTpl = $this->DocumentRoot.$this->GroupTpl;
		   $this->rTheme = $this->DocumentRoot.$this->Theme;
		   $this->rCss = $this->DocumentRoot.$this->Css;
		   $this->rConfig = $this->DocumentRoot.$Config;
		   
		   //系统信息
		   if( !file_exists(CONF_PATH.'copy.php' ) ) exit(); //如果版权信息不存在则终止运行
		   $CMSEdition = (C('LANG_AUTO_DETECT') == true ) ? '多语言版' : '企业版';
		   $CMSRN = '2013SR028772'; //登记号
		   $now = getdate();
		   $CMSCopyRight = sprintf("%d-%d", $now['year']-3, $now['year']+2);
            $this->assign('CMSEdition', $CMSEdition);
            $this->assign('CMSVersion', C('CMSVersion'));
            $this->assign('CMSRN', $CMSRN);
            $this->assign('CMSReleaseDate', C('CMSReleaseDate'));
            $this->assign('CMSCopyRight', $CMSCopyRight );
            $this->assign('CMSCopyright', $CMSCopyRight );
            $this->assign('XUaCompatible', C('XUaCompatible'));
            $this->assign('UcUrl', C('UcUrl'));

            //系统相关信息================================
            $isOem = ('YouDianCMS'==C('CMSEnName')) ? 0 : 1;
            if($isOem==1){
                $this->assign('CmsLogo', C('CmsLogo')); //后台左上图片
                $this->assign('CmsTextLogo', C('CmsTextLogo')); //后台左下图片
                $this->assign('CmsLoginLogo', C('CmsLoginLogo')); //登录页面图片
            }
            $this->IsOem = $isOem;
            $this->assign('IsOem', $isOem);
            $this->assign('CMSName', C('CMSName'));
            $this->assign('CMSEnName', C('CMSEnName'));
            $this->assign('CompanyName', C('CompanyName'));
            $this->assign('CompanyFullName', C('CompanyFullName'));
            $this->assign('CompanyUrl', C('CompanyUrl'));
            $this->assign('CompanyUrl1', C('CompanyUrl'));
            //========================================

            $this->assign('CompanyAddress', C('CompanyAddress'));
            $this->assign('CompanyTelephone', C('CompanyTelephone'));
            $this->assign('CompanyFax', C('CompanyFax'));
            $this->assign('CompanyEmail', C('CompanyEmail'));
            $this->assign('CompanyQQ', C('CompanyQQ'));
            $this->assign('CompanyPostCode', C('CompanyPostCode'));
	}
	
	//系统公共变量模板赋值
	private function _assignPublicVar(){
		//系统变量
		$this->assign('DocumentRoot', $this->DocumentRoot);
		$this->assign('WebInstallDir', $this->WebInstallDir);  
		$this->assign('WebPublic', $this->WebPublic);
		
		$this->assign('UploadAction', __URL__.'/Upload'); //文件上传Action
		$this->assign('UploadAttachmentAction', __URL__.'/UploadAttachment'); //附件上传Action
		$this->assign('App', __APP__); //入口文件地址：如/YoudianCMS/index.php
		$this->assign('Group', __GROUP__); //当前分组地址：如/YoudianCMS/index.php/admin
		$this->assign('AppDebug', (APP_DEBUG ? 1 : 0) );
		if(APP_DEBUG){
			$time = time();
			$this->assign('NoCache', "?nocache=".$time);
			$this->assign('ANoCache', "&nocache=".$time); //A: ampersand &
		}else{
			$this->assign('NoCache', '');
			$this->assign('ANoCache', '');
		}
		
		//模板变量
		$this->assign('Tpl', $this->Tpl);
		$this->assign('GroupTpl', $this->GroupTpl);
		$this->assign('Theme', $this->Theme);
		$this->assign('ThemeName', THEME_NAME);
		$this->assign('Public', $this->Public);  //模板公共目录
		$this->assign('Css', $this->Css);  //模板css目录
		$this->assign('Images', $this->Images);  //模板css目录
		$this->assign('Js', $this->Js);
		$this->assign('Flash', $this->Flash);
		
		//语言
		$this->assign('Language', C('LANG_AUTO_DETECT') ? C('LANG_LIST') : false);
		
		//多语言变量设置
		$groupName = strtolower( GROUP_NAME );
		$k = ucfirst($groupName.'LanguageMark');
		$this->assign($k, LANG_SET);
		$this->assign('LanguageMark', LANG_SET);
		$config = &$GLOBALS['Config'];;
		//搜索引擎优化设置
		$this->assign('Title', $config['TITLE'] );
		$this->assign('Keywords', $config['KEYWORDS'] );
		$this->assign('Description', $config['DESCRIPTION'] );
		
		//基本信息==================================================
        $this->assign('AdminThemeColor', $config['AdminThemeColor'] );
        $this->assign('AdminLeftMenuBgColor', $config['AdminLeftMenuBgColor']);
        $this->assign('AdminLeftMenuTextColor', $config['AdminLeftMenuTextColor']);
        $this->assign('AdminLeftMenuSelectedColor', $config['AdminLeftMenuSelectedColor']);

		$this->assign('WebName', $config['WEB_NAME'] );
		$this->assign('WebUrl', $config['WEB_URL'] );
		$this->assign('WebLogo', $config['WEB_LOGO'] );
		$this->assign('WebIcon', empty($config['WEB_ICON']) ? 'favicon.ico' : $config['WEB_ICON']);
		$this->assign('WebURL', $config['WEB_URL'] );

		$webICP = $this->getWebICP($config['WEB_ICP']);
		$this->assign('WebIcp', $webICP);
		$this->assign('WebICP', $webICP);

		$this->assign('WebStatus', $config['WEB_STATUS'] );
		$this->assign('WebCloseReason', $config['WEB_CLOSE_REASON'] );
		$this->assign('WebBadWords', $config['WEB_BAD_WORDS'] );
		$this->assign('EnableHtml', C('HTML_CACHE_ON') ? 1 : 0 );
		$this->assign('CurrencySymbol', $config['CURRENCY_SYMBOL'] );
		
		$PointExchangeRate = intval($config['POINT_EXCHANGE_RATE']);
		$MoneyExchangeRate = ($PointExchangeRate!=0) ? 1/$PointExchangeRate : 0;
		$this->assign('PointExchangeRate',  $PointExchangeRate);
		$this->assign('MoneyExchangeRate',  $MoneyExchangeRate);
		$FreeShippingThreshold = intval($config['FREE_SHIPPING_THRESHOLD']);
		$this->assign('FreeShippingThreshold',  $FreeShippingThreshold);
		
		if($FreeShippingThreshold>0){ //只有免运费的情况下才显示
			$FreeShippingTip = str_replace('[n]', $FreeShippingThreshold, L('FreeShippingTip'));
			$this->assign('FreeShippingTip',  $FreeShippingTip);
		}
		//=========================================================
		
		$wapLogo = empty($config['WAP_LOGO']) ? $config['WEB_LOGO'] : $config['WAP_LOGO'];
		$this->assign('WapLogo',  $wapLogo );
		//仅对Wap分组有效
		if( strtolower(GROUP_NAME) == 'wap'){
			$this->assign('WebLogo',  $wapLogo ); //在wap分组中还是使用WebLogo
		}

        $this->assign('TextEditor', $config['TextEditor'] );
	}

	private function getWebICP($icp){
	    //如果包含a标签，就表示自定义，原样输出
	    if(false!==stripos($icp, 'beian')){
	        return $icp;
        }
	    $temp = explode(' ', trim($icp), 2);
	    $n = count($temp);
	    $icp = "<a href=\"https://beian.miit.gov.cn\" rel=\"nofollow\" target=\"_blank\">{$temp[0]}</a>";
        if(2==$n && !empty($temp[1])){
            $matches = array();
            preg_match('/[0-9]{8,}/',$temp[1], $matches);
            $icp .= "  <a href=\"http://www.beian.gov.cn/portal/registerSystemInfo?recordcode={$matches[0]}\" target=\"_blank\" rel=\"nofollow\">";
            $icp.= "{$temp[1]}</a>";
        }
	    return $icp;
    }
	
	//获取校验码
	function verifyCode(){
		$length = $_GET['length'];        //长度
		$mode = $_GET['mode'];          //模式
		$type = $_GET['type'];              //图像类型
		$width = $_GET['width'];           //宽度
		$height = $_GET['height'];        //高度
		$verifyName = trim($_GET['verify']);  //验证码session名称
        $map = array('commentcode'=>1, 'verify'=>1);
        if(!isset($map[$verifyName])){
            $verifyName = 'verify';
        }
		import("ORG.Util.Image");
		Image::buildImageVerify($length, $mode, $type, $width, $height, $verifyName);
	}
	
	//$length:校验码个数, $mode: 0:字母, 1:数字, 2:大写字母, 3:小写字母, 4:中文, 5:混合
	function verify($length = 4, $mode = 1, $width = 22, $height = 26, $verifyName = 'verify', $type = 'png'){
		import("ORG.Util.Image");
		Image::buildImageVerify($length, $mode, $type, $width, $height, $verifyName);
	}
	
	function guestbookCode($length = 4, $mode = 1, $width = 22, $height = 26, $verifyName = 'guestbookcode', $type = 'png'){
		$this->verify($length, $mode, $width, $height, $verifyName, $type);
	}
	
	function feedbackCode($length = 4, $mode = 1, $width = 22, $height = 26, $verifyName = 'feedbackcode', $type = 'png'){
		$this->verify($length, $mode, $width, $height, $verifyName, $type);
	}
	
	function orderCode($length = 4, $mode = 1, $width = 22, $height = 26, $verifyName = 'ordercode', $type = 'png'){
		$this->verify($length, $mode, $width, $height, $verifyName, $type);
	}
	
	function resumeCode($length = 4, $mode = 1, $width = 22, $height = 26, $verifyName = 'resumecode', $type = 'png'){
		$this->verify($length, $mode, $width, $height, $verifyName, $type);
	}
	
	function memberCode($length = 4, $mode = 1, $width = 22, $height = 26, $verifyName = 'membercode', $type = 'png'){
		$this->verify($length, $mode, $width, $height, $verifyName, $type);
	}
	//=======================================================================================
	
	//404页面
	function _404($message = '', $jumpUrl = '', $waitSecond = 3) {
		$this->assign('message', '访问的页面不存在！');
		$this->display();
	}

	
	/**
	 * 解析模型参数属性
	 * @param array $attribute
	 */
	function parseAttribute($attribute){
		import("@.Common.YdParseModel");
		$result = parsemodel($attribute);
		return $result;
	}
	
	//判断属性类型是否是可选
	function IsSelectedAttribute($type){
		//$selectedType = array('checkbox'=>0, 'radio'=>1, 'channelselect'=>2, 'modelselect'=>3, 'select'=>4, 
		//		'specialselect'=>5, 'supporttypeselect'=>6, 'linkclassselect'=>7, 'adtyperadio'=>8, 'membergroupselect'=>9);
		//return array_key_exists( strtolower($type), $selectedType);
		if( stripos($type, 'checkbox') === false && 
				stripos($type, 'radio' ) === false &&
				stripos($type, 'select' )  === false  ){
			return false;
		}else{
			return true;
		}
	}
	
	function getHomeTpl(){
		$t = array();
		$t['HomeThemeName'] = C('HOME_DEFAULT_THEME');
		$t['HomeTpl'] = $this->Tpl.'Home/';
		$t['HomeTheme'] = $t['HomeTpl'].$t['HomeThemeName'].'/';
		$t['HomeXml'] = $t['HomeTheme'].'template.xml';
		$t['HomeConfig'] = $t['HomeTheme'].'config.xml';
		$t['HomePublic'] = $t['HomeTheme'].'Public/';
		$t['HomeCss'] = $t['HomePublic'].'css/';
		$t['HomeImages'] = $t['HomePublic'].'images/';
		$t['HomeJs'] = $t['HomePublic'].'js/';
		$t['HomeFlash'] = $t['HomePublic'].'flash/';
		
		$t['rHomeTpl'] = $this->DocumentRoot.$t['HomeTpl'];
		$t['rHomeCss'] = $this->DocumentRoot.$t['HomeCss'];
		$t['rHomeTheme'] = $this->DocumentRoot.$t['HomeTheme'];
		$t['rHomeXml'] = $this->DocumentRoot.$t['HomeXml'];
		$t['rHomePublic'] = $this->DocumentRoot.$t['HomePublic'];
		$t['rHomeConfig'] = $this->DocumentRoot.$t['HomeConfig'];
		
		//物理相对路径，如：./App/Tpl
		$t['pHomeTpl'] = TMPL_PATH.'Home/';
		$t['pHomeTheme'] = $t['pHomeTpl'].$t['HomeThemeName'].'/';
		$t['pHomeXml'] = $t['pHomeTheme'].'template.xml';
		$t['pHomeConfig'] = $t['pHomeTheme'].'config.xml';
		$t['pHomePublic'] = $t['pHomeTheme'].'Public/';
		$t['pHomeCss'] = $t['pHomePublic'].'css/';
		$t['pHomeImages'] = $t['pHomePublic'].'images/';
		$t['pHomeJs'] = $t['pHomePublic'].'js/';
		$t['pHomeFlash'] = $t['pHomePublic'].'flash/';

		$xml = @simplexml_load_file( $t['rHomeXml'] );
		$t['HomeName'] = (string)$xml->name;
		
		return $t;
	}
	
	function getWapTpl(){
		$t = array();
		$t['WapThemeName'] = C('WAP_DEFAULT_THEME');
		$t['WapTpl'] = $this->Tpl.'Wap/';
		$t['WapTheme'] = $t['WapTpl'].$t['WapThemeName'].'/';
		$t['WapXml'] = $t['WapTheme'].'template.xml';
		$t['WapConfig'] = $t['WapTheme'].'config.xml';
		$t['WapPublic'] = $t['WapTheme'].'Public/';
		$t['WapCss'] = $t['WapPublic'].'css/';
		$t['WapImages'] = $t['WapPublic'].'images/';
		$t['WapJs'] = $t['WapPublic'].'js/';
		$t['WapFlash'] = $t['WapPublic'].'flash/';
	
		$t['rWapTpl'] = $this->DocumentRoot.$t['WapTpl'];
		$t['rWapCss'] = $this->DocumentRoot.$t['WapCss'];
		$t['rWapTheme'] = $this->DocumentRoot.$t['WapTheme'];
		$t['rWapXml'] = $this->DocumentRoot.$t['WapXml'];
		$t['rWapPublic'] = $this->DocumentRoot.$t['WapPublic'];
		$t['rWapConfig'] = $this->DocumentRoot.$t['WapConfig'];
		
		//物理相对路径，如：./App/Tpl
		$t['pWapTpl'] = TMPL_PATH.'Wap/';
		$t['pWapTheme'] = $t['pWapTpl'].$t['WapThemeName'].'/';
		$t['pWapXml'] = $t['pWapTheme'].'template.xml';
		$t['pWapConfig'] = $t['pWapTheme'].'config.xml';
		$t['pWapPublic'] = $t['pWapTheme'].'Public/';
		$t['pWapCss'] = $t['pWapPublic'].'css/';
		$t['pWapImages'] = $t['pWapPublic'].'images/';
		$t['pWapJs'] = $t['pWapPublic'].'js/';
		$t['pWapFlash'] = $t['pWapPublic'].'flash/';
	
		$xml = @simplexml_load_file( $t['rWapXml'] );
		$t['WapName'] = (string)$xml->name;
	
		return $t;
	}
	
	//文件上传
	function upload() {
        if( !session('?MemberID') && !session("?AdminID") ){
            $this->uploadFail('没有权限上传');
        }
        //上传参数
        $savePath = isset($_REQUEST['savepath']) ? trim($_REQUEST['savepath']) : '';  //上传后存储的路径
        $addWater = isset($_REQUEST['addwater']) ? $_REQUEST['addwater'] : 'yes'; //是否添加水印
        $isThumb= isset($_REQUEST['isthumb']) ? intval($_REQUEST['isthumb']) : 1;  //是否生成缩略图
        $isRename= isset($_REQUEST['isrename']) ? intval($_REQUEST['isrename']) : 1;  //是否重命名（默认重命名）
        if($isRename==0){ //如果有中文字符，必须重命名，否则可能会出现乱码
            $fileName = $_REQUEST['name'];
            if(!preg_match("/^[\(\)\.a-zA-Z0-9_-]+$/i", $fileName)){
                $isRename = 1;
            }
        }
        $IsOverWrite= (isset($_REQUEST['IsOverWrite']) && $_REQUEST['IsOverWrite']==1) ? true: false; //是否覆盖上传

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
        $allowExts = str_ireplace($deniedExt, 'xxx', $d['UPLOAD_FILE_TYPE']); //不能替换为空有漏洞
        $upload->allowExts  = explode('|', $allowExts);
        //设置附件上传目录
        if(!empty($savePath)){
            if(substr($savePath, 0, 9) ===$d['UPLOAD'] && false===stripos($savePath, '..')){
                $upload->savePath = $savePath;
            }else{
                $this->uploadFail('上传目录无效');
            }
        }else{ //如果上传没有指定目录，那就自动生成
            //$savePath =  $d['UPLOAD'];
            $savePath = GetUploadDir();
        }
        $upload->savePath = $savePath;
        if($isRename){ //是否重命名
            //php8不能直接复制函数名
            $upload->saveRule= yd_is_php8() ? 'time' : time;
        }
        $upload->uploadReplace = $IsOverWrite; //是否覆盖上传
        //仅上传单文件
        if( !empty($_REQUEST['currentfile']) ){
            foreach ($_FILES as $k=>$v){
                if( $k != $_REQUEST['currentfile'] ){
                    unset( $_FILES[$k] );
                }
            }
        }
        $data['FileField'] = trim($_REQUEST['currentfile']);
        $data['ImageField'] = $data['FileField'].'Image';

        if(!$upload->upload()) {
            $errorMsg =$upload->getErrorMsg();
            $this->uploadFail($errorMsg);
        }else{
            $info =  $upload->getUploadFileInfo();
            $ext = trim(strtolower($info[0]['extension']));
            if(in_array($ext, $deniedExt)){
                $this->uploadFail('上传失败，非法文件');
            }
            $saveName = $info[0]['savename'];
            $fullSaveName = $savePath.$saveName; //本地文件路径，如：./Upload/1.jpg
            $data['Path'] = $this->WebInstallDir.substr($fullSaveName, 2); //上传文件url地址
            $data['FileName'] = $saveName; //上传文件的文件名
            if($isThumb===1){ //上传缩略图
                $thumbPath = makeThumb($fullSaveName);
                if( $thumbPath ){
                    $data['Path'] = $this->WebInstallDir.substr($thumbPath, 2);
                    $this->uploadSuccess($data);
                }else{
                    $this->uploadFail('上传失败');
                }
            }else{ //其它非缩略图上传
                if( $addWater == 'no' ){
                     //不添加水印
                }else{
                    //为空或等于1都要添加水印
                    addWater($fullSaveName); //添加水印
                }
                $this->uploadSuccess($data);
            }
        }
	}

    /**
     * 上传失败
     */
	private function uploadFail($errorMsg, $status=2){
        //上传来源
        $UploadSource= isset($_REQUEST['UploadSource']) ? intval($_REQUEST['UploadSource']) : 0;
        if($UploadSource==1){ //CKEditor
            //响应类型
            $responseType = !empty($_REQUEST['responseType']) ? $_REQUEST['responseType'] : '';
            if($responseType=='json'){
                //uploaded=0：表示上传失败
                $data = array('uploaded'=>0, 'error'=>array('message'=>$errorMsg) );
                $data = json_encode($data);
            }else{
                $cb = intval($_GET['CKEditorFuncNum']); //获得ck的回调id
                $data = "<script>window.parent.CKEDITOR.tools.callFunction({$cb}, '', '{$errorMsg}');</script>";
            }
            exit($data);
        }else if($UploadSource==2){ //UEditor
            $data = array('state'=>'ERROR', 'error'=>array('message'=>$errorMsg) );
            $data = json_encode($data);
            exit($data);
        }else{
            $this->ajaxReturn(null, $errorMsg , $status);
        }
    }

    /**
     * 上传成功
     */
    private function uploadSuccess($data, $msg=''){
        //上传来源
        $UploadSource= isset($_REQUEST['UploadSource']) ? intval($_REQUEST['UploadSource']) : 0;
        $des = "上传文件 {$data['Path']}";
        WriteLog($des, array('LogType'=>10, 'UserAction'=>''));
        if($UploadSource==1){ //CKEditor
            $url = $data['Path'];
            //响应类型
            $responseType = !empty($_REQUEST['responseType']) ? $_REQUEST['responseType'] : '';
            if($responseType=='json'){
                //uploaded=1：表示上传成功
                $data = array('uploaded'=>1, 'fileName'=>$data['FileName'], 'url'=>$url);
                $data = json_encode($data);
            }else{
                $cb = intval($_GET['CKEditorFuncNum']);
                $data = "<script>window.parent.CKEDITOR.tools.callFunction({$cb}, '{$url}', '');</script>";
            }
            exit($data);
        }else if($UploadSource==2){ //UEditor
            $url = $data['Path'];
            //响应类型
            $responseType = !empty($_REQUEST['responseType']) ? $_REQUEST['responseType'] : '';
            $data = array('state'=>'SUCCESS','url'=>$url,'title'=>$data['FileName'],'original'=>'图片.png','type'=>'png', 'size'=>'999' );
            $data = json_encode($data);

            exit($data);
        }else{
            $msg = empty($msg) ? '上传成功!' : $msg;
            $status = isset($_REQUEST['flag']) ? intval($_REQUEST['flag']) : 3; //上传成功返回的标志值（默认返回3）
            $this->ajaxReturn($data, $msg , $status);
        }
    }
	
	/**
	 * 发送邮件
	 */
	function sendMail(){
		header("Content-Type:text/html; charset=utf-8");
		$isverify = trim($_REQUEST['isverify']);
		$EmailTitle = trim($_REQUEST['EmailTitle']);              //标题
		$EmailContent = trim($_REQUEST['EmailContent']);  //内容

		if( empty($EmailTitle) ){
			$this->ajaxReturn(null, L('EmailTitleRequired') , 0);
		}
		
		if( empty($EmailContent) ){
			$this->ajaxReturn(null, L('EmailContentRequired') , 0);
		}
		
		if( $isverify == 1){
			$verifycode = $_POST['verifycode'];
			if( empty($verifycode) ){
				session('mailcode', rand(1000, 9999) );
				$this->ajaxReturn(null, L('VerifyCodeRequired') , 0);
			}
			
			if( md5($verifycode) != session('mailcode')  ){
				session('mailcode', rand(1000, 9999) );
				$this->ajaxReturn(null, L('VerifyCodeError')  , 0);
			}
		}
		
		$b = sendwebmail($GLOBALS['Config']['EMAIL'], $EmailTitle, $EmailContent);
		if( $b ){
			$this->ajaxReturn(null, L('EmailSendSuccess') , 0);
		}else{
			$this->ajaxReturn(null, L('EmailSendFail') , 0);
		}
		
	}
	
	/**
	 * 通用首页
	 * @param array $p
	 * (1)PageSize: 分页大小，HasPage：是否启用分页
	 * (2)Parameter: get参数，参数名=>参数值，如：$p['Parameter']=array('username'=>'zhangsan');
	 * (3)函数名称：GetFunctionName、GetCountFunctionName，支持DataCallBack回调函数
	 */
	protected function opIndex($p = array()){
		header("Content-Type:text/html; charset=utf-8");
		$moduleName = isset($p['ModuleName']) ? $p['ModuleName'] : MODULE_NAME;
		$getFunctionName = isset($p['GetFunctionName']) ? $p['GetFunctionName'] : 'get'.$moduleName;
		$Parameter = isset($p['Parameter']) ? $p['Parameter'] : false;
		$m = D('Admin/'.$moduleName);
		if( isset($p['HasPage']) && $p['HasPage'] ){  //支持分页
			if( isset($p['PageSize']) && $p['PageSize'] == -1 ){ //等于-1表示不分页（主要用于会员后台不分页）
                $data = $m->$getFunctionName(-1, -1, $Parameter );
			}else{
                $getCountFunctionName = isset($p['GetCountFunctionName']) ? $p['GetCountFunctionName'] : 'get'.$moduleName.'Count';
                $TotalPage = $m->$getCountFunctionName( $Parameter );
                $PageSize = isset($p['PageSize']) ? (int)$p['PageSize'] : $this->AdminPageSize;
                import("ORG.Util.Page");
                $Page = new Page($TotalPage, $PageSize);
                $Page->rollPage = $this->AdminRollPage;

                //获取页面参数
                if( isset($p['NotPageParameterKey']) && !empty($p['NotPageParameterKey']) ){
                    $PageParameter = array();
                    foreach ($Parameter as $k=>$v){
                        if( !in_array($k, $p['NotPageParameterKey']) ){
                            $PageParameter[$k]=$v;
                        }
                    }
                }else{
                    $PageParameter = $Parameter;
                }
                if( !empty( $PageParameter ) ){
                    $Page->parameter = http_build_query($PageParameter);
                }

                $data = $m->$getFunctionName($Page->firstRow, $Page->listRows, $Parameter );
                $ShowPage = $Page->show();
                $this->assign('AdminPageSize',$PageSize);
                $this->assign('NowPage', $Page->getNowPage()); //当前页码
                $this->assign('Page', $ShowPage); //分页条
			}
			$this->assign($Parameter);
		}else{
			$data = $m->$getFunctionName( $Parameter );
			$this->assign($Parameter);
		}
	
		//Data数据处理回调函数
		if( isset($p['DataCallBack']) && method_exists($this, $p['DataCallBack'])){
			$method = $p['DataCallBack'];
			$this->$method($data);
		}
	
		$this->assign('Data', $data);
		if( empty($p['TemplateFile']) ){
			$this->display();
		}else{
			$this->display( $p['TemplateFile'] );
		}
	}
	
	/**
	 * 通用删除\批量删除
	 * @param array $p 参数
	 * options参数key取值：
	 *   (1)IDVar：待删除记录ID变量名称，默认为id
	 *   (2)Parameter: get参数，参数名=>参数值，如：$p['Parameter']=array('username'=>'zhangsan');
	 *   (3)日志：LogDescription
	 *   (4)函数名称：DelFunctionName: 自定义删除函数名称(可以接受参数)
	 */
	protected function opDel( $p = array() ){
		header("Content-Type:text/html; charset=utf-8");
		$IDVar = isset( $p['IDVar'] ) ? $p['IDVar'] : 'id';
		$id = $_REQUEST[$IDVar];
		$Parameter = isset($p['Parameter']) ? $p['Parameter'] : false;
		$Url = isset($p['Url']) ? $p['Url'] : __URL__."/index";
		$moduleName = isset($p['ModuleName']) ? $p['ModuleName'] : MODULE_NAME;
		$delFunctionName = isset($p['DelFunctionName']) ? $p['DelFunctionName'] : 'del'.$moduleName;
		$m = D('Admin/'.$moduleName);
	
		//需要删除记录的同时，还需要删除对应的文件，必须在删除以前就获取其文件路径
		$list = array('Ad'); //to do 需要进一步完善其他模块
		$fileToDel = in_array($moduleName, $list) ? $m->getAttachment($id) : false;
	
		if( method_exists($m, $delFunctionName) ){ //调用自定义函数
			$result = $m->$delFunctionName( $id , $Parameter );
		}else{  //调用通用函数
			$result = $m->baseDel( $id );
		}
	
		//删除记录对应的文件
		if($result && $fileToDel){
			batchDelFile($fileToDel);
		}
	
		//删除回调函数
		if( isset($p['DelCallBack']) && method_exists($this, $p['DelCallBack'])){
			$method = $p['DelCallBack'];
			$this->$method($result, $id, $Parameter);
		}
	
		//Log日志信息==================================================
		$des = is_array( $id ) ? implode(',', $id) : $id;
		$LogDescription = isset($p['LogDescription']) ? $p['LogDescription'] : "ID:".$des;
		WriteLog($LogDescription);
		//==========================================================
	
		if( isset($p['NotPageParameterKey']) && !empty($p['NotPageParameterKey']) ){
			$PageParameter = array();
			foreach ($Parameter as $k=>$v){
				if( !in_array($k, $p['NotPageParameterKey']) ){
					$PageParameter[$k]=$v;
				}
			}
		}else{
			$PageParameter = $Parameter;
		}
		if( !empty($PageParameter) ){
			if( false !== strpos($Url, '?', 8) ){
				$Url .= '&'.http_build_query($PageParameter);
			}else{
				$Url = rtrim($Url, '/');
				foreach ($PageParameter as $k=>$v){
					$v = urlencode($v);
					$Url .= "/{$k}/{$v}";
				}
			}
		}
		redirect($Url);
	}
	
	/**
	 * 通用删除表所有数据，即清空表
	 * @param array $p 参数
	 * options参数
	 * (1)日志：LogDescription
	 */
	protected function opDelAll( $p = array() ){
		header("Content-Type:text/html; charset=utf-8");
		$moduleName = isset($p['ModuleName']) ? $p['ModuleName'] : MODULE_NAME;
		$m = D('Admin/'.$moduleName);
		$result = $m->baseDelAll( $p );
		//删除回调函数
		if( isset($p['DelCallBack']) && method_exists($this, $p['DelCallBack'])){
			$method = $p['DelCallBack'];
			$this->$method($result);
		}
	
		//Log日志信息==================================================
		$LogDescription = isset($p['LogDescription']) ? $p['LogDescription'] : $moduleName;
		WriteLog($LogDescription);
		//==========================================================
		$Url = isset($p['Url']) ? $p['Url'] : __URL__."/index";
		redirect($Url);
	}
	
	/**
	 * 通用排序
	 * @param array $p 参数
	 * options参数key取值：
	 *   (1)IDVar：排序ID变量名称，默认为OrderID
	 *   (2)NumberVar：排序值变量名称，默认为OrderNumber
	 *   (3)Parameter: get参数，参数名=>参数值，如：$p['Parameter']=array('username'=>'zhangsan');
	 *   (4)日志：LogDescription
	 */
	protected function opSort( $p = array() ){
		header("Content-Type:text/html; charset=utf-8");
		$moduleName = isset($p['ModuleName']) ? $p['ModuleName'] : MODULE_NAME;
		$m = D('Admin/'.$moduleName);
		$IDVar = isset( $p['IDVar'] ) ? $p['IDVar'] : 'OrderID';
		$NumberVar = isset( $p['NumberVar'] ) ? $p['NumberVar'] : 'OrderNumber';
		$Url = isset($p['Url']) ? $p['Url'] : __URL__."/index";
		$m->baseSort($_POST[ $IDVar ], $_POST[$NumberVar]);
	
		//Log日志信息==================================================
		$LogDescription = isset($p['LogDescription']) ? $p['LogDescription'] : '';
		WriteLog($LogDescription );
		//==========================================================
	
		if( isset($p['Parameter']) ){
			$para = '?'.http_build_query( $p['Parameter'] );
			redirect($Url.$para);
		}else{
			redirect($Url);
		}
	}
	
	/**
	 * 通用添加
	 * @param int $ChannelModelID 频道模型ID
	 * @param array $p
	 */
	protected function opAdd($ChannelModelID=false,  $p = array()){
		header("Content-Type:text/html; charset=utf-8");
		if( $ChannelModelID !== false){
			$m = D('Admin/Attribute');
			$Attribute = $m->getAttribute($ChannelModelID);
			$Group = $m->getGroup($ChannelModelID);
			$Attribute = $this->parseAttribute($Attribute);  //解析属性信息
			$this->assign('Group', $Group);
			$this->assign('Attribute', $Attribute);
		}
		$Action = isset($p['Action']) ? $p['Action'] : __URL__.'/saveAdd';
		$this->assign('Action', $Action);
		if( empty($p['TemplateFile']) ){
			$this->display();
		}else{
			$this->display( $p['TemplateFile'] );
		}
	}
	
	/**
	 * 通用保存添加
	 * @param array $p 参数
	 * options参数key取值：
	 *   SuccessMsg：操作成功提示文字
	 *   FailMsg：操作失败提示文字
	 *   Data：表示直接添加数据，不用create
	 *   日志：LogDescription
	 */
	protected function opSaveAdd( $p = array() ){
		header("Content-Type:text/html; charset=utf-8");
		$moduleName = isset($p['ModuleName']) ? $p['ModuleName'] : MODULE_NAME;
		$m = D('Admin/'.$moduleName);
		$SuccessMsg = isset( $p['SuccessMsg'] ) ? $p['SuccessMsg'] : '添加成功';
		$FailMsg = isset( $p['FailMsg'] ) ? $p['FailMsg'] : '添加失败';
		if( empty($p['Data']) ){
			if( $m->create() ){
				$result = $m->add();
				if( isset($p['AddCallBack']) && method_exists($this, $p['AddCallBack'])){
					$method = $p['AddCallBack'];
					$this->$method($result);
				}
				if($result){
					$LogDescription = isset($p['LogDescription']) ? $p['LogDescription'] : 'ID:'.$m->getLastInsID();
					if(isset($_POST["{$moduleName}Name"])){
                        $LogDescription .= ' '.$_POST["{$moduleName}Name"];
                    }
					WriteLog( $LogDescription );
					$this->ajaxReturn(null, $SuccessMsg , 1);
				}else{
					$this->ajaxReturn(null, $FailMsg , 0);
				}
			}else{
				if( isset($p['AddCallBack']) && method_exists($this, $p['AddCallBack'])){
					$method = $p['AddCallBack'];
					$this->$method(false);
				}
				$this->ajaxReturn(null, $m->getError() , 0);
			}
		}else{
			$result = $m->add( $p['Data'] );
			if( isset($p['AddCallBack']) && method_exists($this, $p['AddCallBack'])){
				$method = $p['AddCallBack'];
				$this->$method($result);
			}
			if( $result ){
				$LogDescription = isset($p['LogDescription']) ? $p['LogDescription'] : 'ID:'.$m->getLastInsID();
				WriteLog($LogDescription);
				$this->ajaxReturn(null, $SuccessMsg , 1);
			}else{
				$this->ajaxReturn(null, $FailMsg , 0);
			}
		}
	}
	
	/**
	 * 通用修改
	 * @param int $ChannelModelID 频道模型ID
	 * @param array $p
	 * FindFunctionName
	 */
	protected function opModify($ChannelModelID=false,  $p = array()){
		header("Content-Type:text/html; charset=utf-8");
		$IDVar = isset( $p['IDVar'] ) ? $p['IDVar'] : 'id';
		$id = $_GET[ $IDVar ];
		if( !is_numeric($id)){
			return false;
		}
		$Action = isset($p['Action']) ? $p['Action'] : __URL__.'/saveModify';
		//获取模型属性数据
		$moduleName = isset($p['ModuleName']) ? $p['ModuleName'] : MODULE_NAME;
		$m = D('Admin/'.$moduleName);
		$FunctionName = isset($p['FindFunctionName']) ? $p['FindFunctionName'] : 'find'.$moduleName;
		$Parameter = isset($p['Parameter']) ? $p['Parameter'] : false;
		$Info = $m->$FunctionName( $id, $Parameter);
	
		if( $ChannelModelID !== false){
			//模型属性信息
			$m = D('Admin/Attribute');
			$Attribute = $m->getAttribute($ChannelModelID);
			$Group = $m->getGroup($ChannelModelID);
			$totalCount = is_array($Attribute) ? count($Attribute) : 0;
			for($n = 0; $n <$totalCount ; $n++){
				if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
					$Attribute[$n]['SelectedValue'] = $Info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
				}else{
					$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
				}
			}
			$Attribute = $this->parseAttribute($Attribute);  //解析属性信息
			$this->assign('Group', $Group);
			$this->assign('Attribute', $Attribute);
		}else{
			//不自动生成表单
			//$this->assign('Data', $Info);
		}
		//Data数据处理回调函数
		if( isset($p['DataCallBack']) && method_exists($this, $p['DataCallBack'])){
			$method = $p['DataCallBack'];
			$this->$method($Info);
		}
		$this->assign('Data', $Info);
	
		$this->assign('HiddenName', $moduleName.'ID');
		$this->assign('HiddenValue', $id);
		$this->assign('Action', $Action);
		if( empty($p['TemplateFile']) ){
			$this->display();
		}else{
			$this->display( $p['TemplateFile'] );
		}
	}
	
	/**
	 * 通用保存修改
	 * @param array $p 参数
	 * options参数key取值：
	 *   SuccessMsg：操作成功提示文字
	 *   FailMsg：操作失败提示文字
	 *   Data：表示直接添加数据，不用create
	 *   日志：LogDescription
	 */
	protected function opSaveModify( $p = array() ){
		header("Content-Type:text/html; charset=utf-8");
		$moduleName = isset($p['ModuleName']) ? $p['ModuleName'] : MODULE_NAME;
		$m = D('Admin/'.$moduleName);
		$id = $_POST[$moduleName.'ID'];
		$SuccessMsg = isset( $p['SuccessMsg'] ) ? $p['SuccessMsg'] : '修改成功';
		$FailMsg = isset( $p['FailMsg'] ) ? $p['FailMsg'] : '修改失败';
		//Log日志信息
		$LogDescription = isset($p['LogDescription']) ? $p['LogDescription'] : 'ID:'.$id;
        if(isset($_POST["{$moduleName}Name"])){
            $LogDescription .= ' '.$_POST["{$moduleName}Name"];
        }
		if( empty( $p['Data'] ) ){
			if( $m->create() ){
			    if(isset($_POST['CurrentMemberID'])){
			        $m->where('MemberID='.intval($_POST['CurrentMemberID']));
                }
				$result = $m->save();
				if( isset($p['SaveCallBack']) && method_exists($this, $p['SaveCallBack'])){
					$method = $p['SaveCallBack'];
					$this->$method($result, $id);
				}
				if($result === false){
					$this->ajaxReturn(null, $FailMsg , 0);
				}else{
					WriteLog($LogDescription);
					$this->ajaxReturn(null, $SuccessMsg , 1);
				}
			}else{
				if( isset($p['SaveCallBack']) && method_exists($this, $p['SaveCallBack'])){
					$method = $p['SaveCallBack'];
					$this->$method(false, $id);
				}
				$this->ajaxReturn(null, $m->getError() , 0);
			}
		}else{
			$result = $m->save( $p['Data'] );
			if( isset($p['SaveCallBack']) && method_exists($this, $p['SaveCallBack'])){
				$method = $p['SaveCallBack'];
				$this->$method($result, $id);
			}
			if( $result === false ){
				$this->ajaxReturn(null, $FailMsg , 0);
			}else{
				WriteLog($LogDescription);
				$this->ajaxReturn(null, $SuccessMsg , 1);
			}
		}
	}
	
	//获取类型信息
	function getTypeAttribute(){
		header("Content-Type:text/html; charset=utf-8");
		if( $_POST['InfoID'] ){
			$m = D('Admin/TypeAttributeValue');
			$data = $m->getAllTypeAttributeValue( $_POST['InfoID'], $_POST['TypeID'] );
		}else{
			$m = D('Admin/TypeAttribute');
			$p['TypeID'] = $_POST['TypeID'];
			$p['IsEnable'] = 1;
			$data = $m->getTypeAttribute($p);
		}
		$num = 1;
		$LastTypeAttributeID = -1;
		if($data){
			$n = is_array($data) ? count($data) : 0;
			for($i=0; $i<$n; $i++){
				$TypeAttributeID = $data[$i]['TypeAttributeID'];
				$AttributeValueID = $data[$i]['AttributeValueID'];
				$AttributeValue = isset($data[$i]['AttributeValue']) ? $data[$i]['AttributeValue'] : ''; //属性值
				$AttributePrice = isset($data[$i]['AttributePrice']) ? $data[$i]['AttributePrice'] : '0';   //属性价格
				$AttributePicture = isset($data[$i]['AttributePicture']) ? $data[$i]['AttributePicture'] : '';   //属性图片
				$data[$i]['Html'] = "<input type='hidden' name='attr_id_list[]' value='{$TypeAttributeID}'>";
				$data[$i]['Html'] .= "<input type='hidden' name='attr_value_id_list[]' value='{$AttributeValueID}'>";
				switch( $data[$i]['InputType'] ){
					case 2: //从列表选择
						$data[$i]['Html'] .= "<select style='min-width:150px;' name='attr_value_list[]'>";
						//$data[$i]['Html'] .= '<option value="">请选择...</option>';
						if( !empty( $data[$i]['InputValue'] ) ){
							$value = str_replace(array("\r\n","\r"), "\n", $data[$i]['InputValue']);
							$items = explode("\n", $value);
							foreach ($items as $it){
								if( $it == $AttributeValue){
									$data[$i]['Html'] .= "<option value=\"{$it}\"  selected='selected'>{$it}</option>";
								}else{
									$data[$i]['Html'] .= "<option value=\"{$it}\">{$it}</option>";
								}
							}
						}
						$data[$i]['Html'] .= '</select>';
						break;
					case 3: //多行
						$data[$i]['Html'] .= "<textarea  name='attr_value_list[]' style='height:60px;width:100%;'>{$AttributeValue}</textarea>";
						break;
					case 1: //手工录入
					default:
						$data[$i]['Html'] .= "<input type='text' name='attr_value_list[]' class='textinput' style='width:33%;' value='{$AttributeValue}' />";
				}
				if( $data[$i]['ValueType'] == 1){ //唯一属性
					$data[$i]['Html'] .= "<input type='hidden' name='attr_price_list[]' value=''><input type='hidden' name='attr_picture_list[]' value=''>";
				}else{ //作为规格
					if($LastTypeAttributeID != -1 && $LastTypeAttributeID != $TypeAttributeID ) $num = 1;
					$data[$i]['Html'] .="&nbsp;&nbsp;&nbsp;<b>图片</b>&nbsp;
					<input type='text' placeholder='图片尺寸建议为：60x60' id='AttributePictureFile{$TypeAttributeID}_{$num}Image' onmouseover='attributePicturefloat(this)' style='width:300px;' class='textinput' name='attr_picture_list[]' value='{$AttributePicture}'>
					<span class='UploadWrapper'>
					    <input class='btnFileUpload' type='button' value='上传'>
					    <input class='InputFile' id='AttributePictureFile{$TypeAttributeID}_{$num}' name='AttributePictureFile{$TypeAttributeID}_{$num}' type='file' onchange='uploadAttributePicture(this)' accept='image/*'>
					</span>";
					
					//只有管理员组才显示从图片库选择
					if( GROUP_NAME == 'Admin'){
						$SelectPicture = L('SelectPicture');
						$data[$i]['Html'] .="<input onclick='BrowserAttributePictureServer(this)' id='btnAttributePictureServer' ";
						$data[$i]['Html'] .=" name='btnAttributePictureServer' type='button' value='{$SelectPicture}' class='btnUpload'>";
					}
					
					$data[$i]['Html'] .="&nbsp;&nbsp;&nbsp;<b>增加价格</b>&nbsp;
					<input type='text' name='attr_price_list[]' class='textinput' style='width:60px;' value='{$AttributePrice}'>
					&nbsp;<a class='btn_attr_del' onclick='delAttribute(this)'>[ 删除 ]</a>
					&nbsp;<a class='btn_attr_add' onclick='addAttribute(this)'>[ 添加 ]</a>";
					$num++;
					$LastTypeAttributeID = $TypeAttributeID;
				}
			}
			$this->ajaxReturn($data, '' , 1);
		}else{
			$this->ajaxReturn($data, '' , 0);
		}
	}

}