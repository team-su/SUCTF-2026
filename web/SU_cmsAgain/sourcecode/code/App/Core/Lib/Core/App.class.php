<?php
/**
 +------------------------------------------------------------------------------
 * ThinkPHP 应用程序类 执行应用过程管理
 * 可以在模式扩展中重新定义 但是必须具有Run方法接口
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id: App.class.php 2792 2012-03-02 03:36:36Z liu21st $
 +------------------------------------------------------------------------------
 */
if (!defined('APP_NAME')) exit();
class App {

    /**
     +----------------------------------------------------------
     * 应用程序初始化
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static public function init() {
        // 设置系统时区
        date_default_timezone_set(C('DEFAULT_TIMEZONE'));
        //必须设置，否则basename，pathinfo等无法解析中文路径，
        //如：banner/中国的/1.jpg，basename会返回"中国的/1.jpg"（PHP5.3没有问题，7.0或以上存在问题）
        setlocale(LC_ALL, 'zh_CN.UTF8');
        // 加载动态项目公共文件和配置
        load_ext_file(); 
        // URL调度
        Dispatcher::dispatch();

        //===============================================
        /*
        if(defined('GROUP_NAME')) {
            // 加载分组配置文件
            if(is_file(CONF_PATH.GROUP_NAME.'/config.php')){
                C(include CONF_PATH.GROUP_NAME.'/config.php');
            }
            // 加载分组函数文件
            if(is_file(COMMON_PATH.GROUP_NAME.'/function.php')){
                include COMMON_PATH.GROUP_NAME.'/function.php';
            }
        }
        $templateSet =  C('DEFAULT_THEME');
        */
        $templateSet = C(strtoupper(GROUP_NAME).'_DEFAULT_THEME');
        C('DEFAULT_THEME', $templateSet);
        //================================================
        
        //系统变量安全过滤，主要防止SQL注入
        if(isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            $_SERVER['HTTP_REFERER'] = htmlspecialchars($_SERVER['HTTP_REFERER']);
        }
        array_walk_recursive($_GET,         'think_filter');
        array_walk_recursive($_POST,       'think_filter');
        array_walk_recursive($_REQUEST, 'think_filter');
       
        /* 模板相关目录常量 */
        define('THEME_NAME',   $templateSet); // 当前模板主题名称
        $group   =  defined('GROUP_NAME')?GROUP_NAME.'/':'';
        define('THEME_PATH',   TMPL_PATH.$group.(THEME_NAME?THEME_NAME.'/':''));
        define('APP_TMPL_PATH',__ROOT__.'/'.APP_NAME.(APP_NAME?'/':'').basename(TMPL_PATH).'/'.$group.(THEME_NAME?THEME_NAME.'/':''));
        C('TEMPLATE_NAME',THEME_PATH.MODULE_NAME.(defined('GROUP_NAME')?C('TMPL_FILE_DEPR'):'/').ACTION_NAME.C('TMPL_TEMPLATE_SUFFIX'));
        C('CACHE_PATH',CACHE_PATH.$group);
        return ;
    }

    /**
     +----------------------------------------------------------
     * 执行应用程序
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    static public function exec() {
        // 安全检测 \w表示：[A-Za-z0-9_]
        if(!preg_match('/^[A-Za-z](\w)*$/',MODULE_NAME)){
            $module =  false;
        }else{
            //创建Action控制器实例
            $group =  defined('GROUP_NAME') ? GROUP_NAME.'/' : '';
            $module  =  A($group.MODULE_NAME);
        }

        if(!$module) {  //这里删除了hack模块
            // 是否定义Empty模块
            $module = A('Empty');
            if(!$module){
                $msg =  L('_MODULE_NOT_EXIST_').MODULE_NAME;
                if(APP_DEBUG) {
                    // 模块不存在 抛出异常
                    throw_exception($msg);
                }else{
                    if(C('LOG_EXCEPTION_RECORD')) Log::write($msg);
                    send_http_status(404);
                    exit;
                }
            }
        }
        //获取当前操作名
        $action = ACTION_NAME;
        if(!preg_match('/^[A-Za-z](\w)*$/',$action)){
        	send_http_status(404);
            exit;
        }
        // 获取操作方法名标签
        tag('action_name',$action);
        //if (method_exists($module,'_before_'.$action)) {
            // 执行前置操作
        //    call_user_func(array(&$module,'_before_'.$action));
        //}
        //执行当前操作，不加存在判断，否则会无法响应不存在的方法
        //if (method_exists($module, $action)){
            call_user_func(array(&$module, $action));
        //}
        //if (method_exists($module,'_after_'.$action)) {
            //  执行后缀操作
       //     call_user_func(array(&$module,'_after_'.$action));
       // }
        return ;
    }

    /**
     +----------------------------------------------------------
     * 运行应用实例 入口文件使用的快捷方法
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static public function run() {    	
        // 项目初始化标签
        tag('app_init');
        App::init();
        // 项目开始标签
        tag('app_begin');
        // Session初始化
        session(C('SESSION_OPTIONS'));
        // 记录应用初始化时间
        G('initTime');
        App::exec();
        // 项目结束标签
        tag('app_end');
        // 保存日志记录
        if(C('LOG_RECORD')) Log::save();
        return ;
    }

}