<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
if (!defined('APP_NAME')) exit();
class CheckLangBehavior extends Behavior {
    // 行为参数定义（默认值） 可在项目配置中覆盖
    protected $options   =  array(
            'LANG_SWITCH_ON'        => true,   // 默认关闭语言包功能
            'LANG_AUTO_DETECT'      => true,   // 自动侦测语言 开启多语言功能后有效
            'VAR_LANGUAGE'          => 'l',		// 默认语言切换变量
        );

    // 行为扩展的执行入口必须是run
    public function run(&$params){
        // 开启静态缓存
        $this->checkLanguage();
    }

    /**
     +----------------------------------------------------------
     * 语言检查
     * 检查浏览器支持语言，并自动加载语言包
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    private function checkLanguage() {
        // 不开启语言包功能，仅仅加载框架语言文件直接返回
        if (!C('LANG_SWITCH_ON')){
            return;
        }
        $var = C('VAR_LANGUAGE'); //语言变量
        $langSet = $this->_checkLanguageDomain();
        $groupName = strtolower( GROUP_NAME );
        $langAutoDetect = C('LANG_AUTO_DETECT');
        // 启用了语言包功能
        // 根据是否启用自动侦测设置获取语言选择
        if ($langAutoDetect) {
        	if($groupName == 'admin' || $groupName == 'member'){
        		//网站后台，需要自动记忆当前语言
        		$k = ucfirst($groupName.'LangSet');
        		if( isset($_REQUEST[$var]) ){ //设置语言
                    $langSet = YdInput::checkLetterNumber($_REQUEST[$var]);
        			cookie($k, $langSet);
        		}else if( cookie($k) ){ //若没有设置语言，则使用默认语言
        			$langSet = cookie($k);
        		}elseif(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){// 自动侦测浏览器语言
        			//Chinese (zh)  Chinese/China (zh-cn)   Chinese/Taiwan (zh-tw)  
        			//Chinese/Hong Kong (zh-hk)  Chinese/singapore (zh-sg)
	               // preg_match('/^([a-z\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
	                $langSet = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);  //获取当前语言前2位
	                if($langSet == 'zh' ) $langSet = 'cn';
	                cookie($k, $langSet, 3600);
            	}
        	}else{
        		//网站前台每次请求都包含当前的语言变量
        		if(isset($_REQUEST[$var]) && !empty($_REQUEST[$var]) && array_key_exists($langSet, C('LANG_LIST')) ){
        			$langSet = rtrim($_REQUEST[$var], '/');// url中设置了语言变量
        		}
        	}
        }

        //判断是否非法 /l/_adminer.php，防止漏洞检测报漏洞
        if(false!==stripos($langSet, 'admin') || !preg_match("/^[a-zA-Z0-9]{1,4}$/i", $langSet)){
            send_http_status(404);
            exit();
        }

        $defaultLang = C('DEFAULT_LANG');
        $mapLang = C('LANG_LIST');
        if( !array_key_exists($langSet, $mapLang) ) { // 非法语言参数
        	$langSet = $defaultLang;
        }
        
        $langSet = strtolower($langSet);  //语言变量值转换为小写
        
        //用于区分多语言模板================================
        if($groupName == 'home' || $groupName == 'wap'){
        	define('LANG_CACHE_EXT', $langSet);
        }else{
        	define('LANG_CACHE_EXT', '');
        }
        //===========================================
        
        // 定义当前语言常量===========================================
        $langID = get_language_id($langSet);
        define('LANG_ID', $langID);
        define('LANG_SET', $langSet);
        $prefix = '';
        if(LANG_SET  != $defaultLang ){
            $langDomain = get_language_domain($langSet);
            if(empty($langDomain) || isDecorationMode() ){  //没有定义域名才定义
                $prefix = '/'.LANG_SET;
            }
        }
        define('LANG_PREFIX',  $prefix);
        //======================================================

        //加载全局用户配置变量
        $GLOBALS['Config'] = YdCache::readConfig();

        if($groupName == 'admin'){
            //读取项目公共语言包
            $adminLangFile = LANG_PATH.'cn/common.php'; //管理后台永远是中文
            if (is_file($adminLangFile)){
                L(include $adminLangFile);
            }
        }else{
            //读取项目公共语言包
            if (is_file(LANG_PATH.LANG_SET.'/common.php')){
                L(include LANG_PATH.LANG_SET.'/common.php');
            }
        }

        $group = '';
        //读取当前分组公共语言包
        if (defined('GROUP_NAME')){
            if (is_file(LANG_PATH.LANG_SET.'/'.GROUP_NAME.'.php'))
                L(include LANG_PATH.LANG_SET.'/'.GROUP_NAME.'.php');
            $group = GROUP_NAME.C('TMPL_FILE_DEPR');
        }
        //读取当前模块语言包
        if (is_file(LANG_PATH.LANG_SET.'/'.$group.strtolower(MODULE_NAME).'.php')){
            L(include LANG_PATH.LANG_SET.'/'.$group.strtolower(MODULE_NAME).'.php');
        }
        
         //读取Home模块语言包 主题目录/Lang/common_语言标识
         if( strtolower(GROUP_NAME) == 'home'){
         	$HomeLang = THEME_PATH.'Lang/common_'.LANG_SET.'.php';
         	if (is_file($HomeLang) ){
         		L(include $HomeLang );
         	}
         }
         
         //读取Wap模块语言包 主题目录/Lang/common_语言标识
         if( strtolower(GROUP_NAME) == 'wap'){
         	$HomeLang = THEME_PATH.'Lang/common_'.LANG_SET.'.php';
         	if (is_file($HomeLang) ){
         		L(include $HomeLang );
         	}
         }

    }

    /**
     * 检查域名绑定，返回当前默认语言标识
     */
    private function _checkLanguageDomain(){
        $defaultLang = C('DEFAULT_LANG');
        $langAutoDetect = C('LANG_AUTO_DETECT');
        $groupName = strtolower( GROUP_NAME );
        if(empty($langAutoDetect)) return $defaultLang;
        if('admin' == $groupName  || 'member' == $groupName){
            return $defaultLang;
        }
        $mapLang = C('LANG_LIST');
        $host = strtolower($_SERVER['HTTP_HOST']);
        $temp = explode(':', $host);
        $domain = $temp[0];
        foreach($mapLang as $k=>$v){
            if($domain == strtolower($v['LanguageDomain'])){
                $var = C('VAR_LANGUAGE');
                $_GET[$var] = $k;
                $defaultLang = $k;
                break;
            }
        }
        return $defaultLang;
    }
}