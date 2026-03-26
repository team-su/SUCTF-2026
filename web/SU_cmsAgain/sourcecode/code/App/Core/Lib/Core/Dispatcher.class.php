<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: Dispatcher.class.php 2840 2012-03-23 05:56:20Z liu21st@gmail.com $
if (!defined('APP_NAME')) exit();
/**
 +------------------------------------------------------------------------------
 * ThinkPHP内置的Dispatcher类
 * 完成URL解析、路由和调度
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id: Dispatcher.class.php 2840 2012-03-23 05:56:20Z liu21st@gmail.com $
 +------------------------------------------------------------------------------
 */
class Dispatcher {

    /**
     +----------------------------------------------------------
     * URL映射到控制器
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static public function dispatch() {
        $urlMode  =  C('URL_MODEL');
        if(!empty($_GET[C('VAR_PATHINFO')])) { // 判断URL里面是否有兼容模式参数
            $_SERVER['PATH_INFO']   = $_GET[C('VAR_PATHINFO')];
            unset($_GET[C('VAR_PATHINFO')]);
        }
        if($urlMode == URL_COMPAT ){
            // 兼容模式判断
            define('PHP_FILE',_PHP_FILE_.'?'.C('VAR_PATHINFO').'=');
        }elseif($urlMode == URL_REWRITE ) {
            //当前项目地址
            $url    =   dirname(_PHP_FILE_);
            if($url == '/' || $url == '\\')
                $url    =   '';
            define('PHP_FILE',$url);
        }else {
            //当前项目地址
            define('PHP_FILE',_PHP_FILE_);
        }
        
        // 分析PATHINFO信息
        if(empty($_SERVER['PATH_INFO'])) {
        	$types   =  explode(',',C('URL_PATHINFO_FETCH'));
        	foreach ($types as $type){
        		if(0===strpos($type,':')) {// 支持函数判断
        			$_SERVER['PATH_INFO'] =   call_user_func(substr($type,1));
        			break;
        		}elseif(!empty($_SERVER[$type])) {
        			$_SERVER['PATH_INFO'] = (0 === strpos($_SERVER[$type],$_SERVER['SCRIPT_NAME']))?
        			substr($_SERVER[$type], strlen($_SERVER['SCRIPT_NAME']))   :  $_SERVER[$type];
        			break;
        		}
        	}
        }

        // 开启子域名部署（主要用于手机网站）
        //使用手机域名/admin 也可以访问管理后台，用于只有手机网站的情况, 
        //在使用$_SERVER['PATH_INFO']之前必须判断是否为空，因为有些linux服务器不支持PATH_INFO，见上面的判断：分析PATHINFO信息
        //$LoginAdmin = isset($_SERVER['PATH_INFO']) ? stripos($_SERVER['PATH_INFO'], '/admin') : false;
        $LoginAdmin = false;
        if( isset($_SERVER['PATH_INFO']) ){
        	if( 0 === stripos($_SERVER['PATH_INFO'], '/admin') || 0 === stripos($_SERVER['PATH_INFO'], '/member') ){
        		$LoginAdmin = 0;
        	}
        }
        if(C('APP_SUB_DOMAIN_DEPLOY') && 0 !== $LoginAdmin) { //如果不是登陆后台
        	/*
            $rules = C('APP_SUB_DOMAIN_RULES');
            $subDomain    = strtolower(substr($_SERVER['HTTP_HOST'],0,strpos($_SERVER['HTTP_HOST'],'.')));
            define('SUB_DOMAIN',$subDomain); // 二级域名定义
            if($subDomain && isset($rules[$subDomain])) {
                $rule =  $rules[$subDomain];
            }elseif(isset($rules['*'])){ //泛域名支持
                if('www' != $subDomain && !in_array($subDomain,C('APP_SUB_DOMAIN_DENY'))) {
                    $rule =  $rules['*'];
                }
            }
            */
        	
        	//修改成能绑定一级域名==============================================
        	//判断手机网站是否存在，若不存在或默认的已经是手机网站，则取消手机网站域名绑定
        	$HasWap = file_exists(TMPL_PATH.'Wap/'.C('WAP_DEFAULT_THEME').'/template.xml');
        	if( $HasWap && C('DEFAULT_GROUP') != 'Wap'){
	        	//修改后不再支持泛域名
        		$domainRules = C('APP_SUB_DOMAIN_RULES');
        		if( !empty($domainRules) ){
		        	$rules = array_change_key_case( $domainRules );
		        	$httpHost    = strtolower($_SERVER['HTTP_HOST']);
		        	if($httpHost && isset($rules[$httpHost])) {
		        		$rule =  $rules[$httpHost];
		        	}
        		}
        	 }
        	//===========================================================
            if(!empty($rule)) {
                // 子域名部署规则 '子域名'=>array('分组名/[模块名]','var1=a&var2=b');
                $array   =  explode('/',$rule[0]);
                $module = array_pop($array);
                if(!empty($module)) {
                    $_GET[C('VAR_MODULE')] = $module;
                    $domainModule   =  true;
                }
                if(!empty($array)) {
                    $_GET[C('VAR_GROUP')]  = array_pop($array);
                    $domainGroup =  true;
                }
                if(isset($rule[1])) { // 传入参数
                    parse_str($rule[1],$parms);
                    $_GET   =  array_merge($_GET,$parms);
                }
            }
        }

        $depr = C('URL_PATHINFO_DEPR');
        if(!empty($_SERVER['PATH_INFO'])) {
            tag('path_info');
            if(C('URL_HTML_SUFFIX')) {
                $_SERVER['PATH_INFO'] = preg_replace('/\.'.trim(C('URL_HTML_SUFFIX'),'.').'$/i', '', $_SERVER['PATH_INFO']);
            }
            if(!self::routerCheck()){   //检测路由规则 如果没有则按默认规则调度URL
                $paths = explode($depr,trim($_SERVER['PATH_INFO'],'/'));
                if(C('VAR_URL_PARAMS')) {
                    // 直接通过$_GET['_URL_'][1] $_GET['_URL_'][2] 获取URL参数 方便不用路由时参数获取
                    $_GET[C('VAR_URL_PARAMS')]   =  $paths;
                }
                $var  =  array();
                if (C('APP_GROUP_LIST') && !isset($_GET[C('VAR_GROUP')])){
                    $adminLoginName = strtolower(C('ADMIN_LOGIN_NAME'));
                    //解决历史遗留问题，默认配置文件设置的就是admin，不置空无法打开后台
                    if($adminLoginName==='admin') $adminLoginName = '';
                    $tempAppGroupList = strtolower(C('APP_GROUP_LIST'));
                    $FirstPath = strtolower($paths[0]);
                    if(!empty($adminLoginName) && $adminLoginName===$FirstPath){
                        array_shift($paths);
                        $var[C('VAR_GROUP')] = 'Admin';
                        if (!defined('ADMIN_LOGIN_NAME')) { //没有定义
                            define('ADMIN_LOGIN_NAME', $adminLoginName);
                        }
                    }else{
                        $var[C('VAR_GROUP')] = in_array(strtolower($paths[0]),explode(',',$tempAppGroupList))? array_shift($paths) : '';
                    }
                    //定义后台名称后，不能使用admin访问
                    if(!empty($adminLoginName) && $FirstPath==='admin'){
                        send_http_status('404');
                        exit();
                    }
                    if(C('APP_GROUP_DENY') && in_array(strtolower($var[C('VAR_GROUP')]),explode(',',strtolower(C('APP_GROUP_DENY'))))) {
                        // 禁止直接访问分组
                        exit;
                    }
                }
                if(!isset($_GET[C('VAR_MODULE')])) {// 还没有定义模块名称
                    $var[C('VAR_MODULE')]  =   array_shift($paths);
                }
                $var[C('VAR_ACTION')]  =   array_shift($paths);
                // 解析剩余的URL参数
                if(count($paths)>0){
                	//$res = preg_replace('@(\w+)'.$depr.'([^'.$depr.'\/]+)@e', '$var[\'\\1\']=strip_tags(\'\\2\');', implode($depr,$paths));
                	$res = preg_replace_callback('@(\w+)'.$depr.'([^'.$depr.'\/]+)@', function($match) use(&$var){
                		$var[$match[1]] = strip_tags($match[2]);
                	}, implode($depr,$paths));
                }
                $_GET   =  array_merge($var,$_GET);
            }else{
                //===================================
                //配合CheckRouteBehavior.class.php
                if(defined('ADMIN_LOGIN_NAME')){
                    $_GET[C('VAR_GROUP')] = 'admin';
                }
                //====================================
            }
            define('__INFO__',$_SERVER['PATH_INFO']);
        }

        // 获取分组 模块和操作名称
        if (C('APP_GROUP_LIST')) {
            define('GROUP_NAME', self::getGroup(C('VAR_GROUP')));
        }
        define('MODULE_NAME',self::getModule(C('VAR_MODULE')));
        define('ACTION_NAME',self::getAction(C('VAR_ACTION')));
        // URL常量
        define('__SELF__',strip_tags($_SERVER['REQUEST_URI']));
        // 当前项目地址
        define('__APP__',strip_tags(PHP_FILE));
        // 当前模块和分组地址
        $module = defined('P_MODULE_NAME')?P_MODULE_NAME:MODULE_NAME;
        if(defined('GROUP_NAME')) {
            if(defined('ADMIN_LOGIN_NAME')){
                define('__GROUP__', __APP__.'/'.ADMIN_LOGIN_NAME);
            }else{
                define('__GROUP__',(!empty($domainGroup) || strtolower(GROUP_NAME) == strtolower(C('DEFAULT_GROUP')) )?__APP__ : __APP__.'/'.GROUP_NAME);
            }
            define('__URL__',!empty($domainModule)?__GROUP__.$depr : __GROUP__.$depr.$module);
        }else{
            define('__URL__',!empty($domainModule)?__APP__.'/' : __APP__.'/'.$module);
        }
        // 当前操作地址
        define('__ACTION__',__URL__.$depr.ACTION_NAME);

        //保证$_REQUEST正常取值
        $_REQUEST = array_merge($_POST,$_GET);
    }

    /**
     +----------------------------------------------------------
     * 路由检测
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static public function routerCheck() {
        $return   =  false;
        // 路由检测标签
        tag('route_check',$return);
        return $return;
    }

    /**
     +----------------------------------------------------------
     * 获得实际的模块名称
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static private function getModule($var) {
        $module = (!empty($_GET[$var])? $_GET[$var]:C('DEFAULT_MODULE'));
        unset($_GET[$var]);
        if(C('URL_CASE_INSENSITIVE')) {
            // URL地址不区分大小写
            $mdl = strtolower($module);
            define('P_MODULE_NAME',$mdl);
            // 智能识别方式 index.php/user_type/index/ 识别到 UserTypeAction 模块
            $map = array('adgroup'=>'AdGroup', 'horsescan'=>'HorseScan'); //bywang 解决2个单词首字母大写的bug
            if( !isset( $map[ $mdl ] )){
            	$module = ucfirst(parse_name(P_MODULE_NAME,1));
            }else{
            	$module =$map[ $mdl ];
            }
        }
        return strip_tags($module);
    }

    /**
     +----------------------------------------------------------
     * 获得实际的操作名称
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static private function getAction($var) {
        $action   = !empty($_POST[$var]) ?
            $_POST[$var] :
            (!empty($_GET[$var])?$_GET[$var]:C('DEFAULT_ACTION'));
        unset($_POST[$var],$_GET[$var]);
        define('P_ACTION_NAME',$action);
        return strip_tags(C('URL_CASE_INSENSITIVE')?strtolower($action):$action);
    }

    /**
     +----------------------------------------------------------
     * 获得实际的分组名称
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static private function getGroup($var) {
        $group   = (!empty($_GET[$var])?$_GET[$var]:C('DEFAULT_GROUP'));
        unset($_GET[$var]);
        return strip_tags(C('URL_CASE_INSENSITIVE') ?ucfirst(strtolower($group)):$group);
    }

}