<?php $GLOBALS['_beginTime'] = microtime(TRUE);
defined('APP_NAME') or define('APP_NAME','App');
defined('THINK_PATH') or define('THINK_PATH','./App/Core/');
defined('APP_PATH') or define('APP_PATH','./App/');
defined('APP_PUBLIC_PATH') or define('APP_PUBLIC_PATH','./Public/');
defined('APP_DATA_PATH') or define('APP_DATA_PATH','./Data/');
defined('HTML_PATH') or define('HTML_PATH','./Data/html/');
defined('RUNTIME_PATH') or define('RUNTIME_PATH','./Data/runtime/');
defined('INSTALL_PATH') or define('INSTALL_PATH','./Install/');
defined('APP_DEBUG') or define('APP_DEBUG',false);
defined('MEMORY_LIMIT_ON') or define('MEMORY_LIMIT_ON',true);
defined('RUNTIME_FILE') or define('RUNTIME_FILE','./Data/runtime/yunxingshi.php');
defined('THINK_VERSION') or define('THINK_VERSION','5.0');
defined('IS_CGI') or define('IS_CGI',1);
defined('IS_WIN') or define('IS_WIN',0);
defined('IS_CLI') or define('IS_CLI',0);
defined('_PHP_FILE_') or define('_PHP_FILE_','/index.php');
defined('__ROOT__') or define('__ROOT__','');
defined('URL_COMMON') or define('URL_COMMON',0);
defined('URL_PATHINFO') or define('URL_PATHINFO',1);
defined('URL_REWRITE') or define('URL_REWRITE',2);
defined('URL_COMPAT') or define('URL_COMPAT',3);
defined('CORE_PATH') or define('CORE_PATH','./App/Core/Lib/');
defined('EXTEND_PATH') or define('EXTEND_PATH','./App/Core/Extend/');
defined('MODE_PATH') or define('MODE_PATH','./App/Core/Extend/Mode/');
defined('ENGINE_PATH') or define('ENGINE_PATH','./App/Core/Extend/Engine/');
defined('VENDOR_PATH') or define('VENDOR_PATH','./App/Core/Extend/Vendor/');
defined('LIBRARY_PATH') or define('LIBRARY_PATH','./App/Core/Extend/Library/');
defined('COMMON_PATH') or define('COMMON_PATH','./App/Common/');
defined('LIB_PATH') or define('LIB_PATH','./App/Lib/');
defined('CONF_PATH') or define('CONF_PATH','./App/Conf/');
defined('LANG_PATH') or define('LANG_PATH','./App/Lang/');
defined('TMPL_PATH') or define('TMPL_PATH','./App/Tpl/');
defined('LOG_PATH') or define('LOG_PATH','./Data/runtime/Logs/');
defined('TEMP_PATH') or define('TEMP_PATH','./Data/runtime/Temp/');
defined('DATA_PATH') or define('DATA_PATH','./Data/runtime/Data/');
defined('CACHE_PATH') or define('CACHE_PATH','./Data/runtime/Cache/');
set_include_path(get_include_path() . PATH_SEPARATOR . VENDOR_PATH);
if (!defined('APP_NAME')) exit();
/**
  +------------------------------------------------------------------------------
 * Think 基础函数库
  +------------------------------------------------------------------------------
 * @category   Think
 * @package  Common
 * @author   liu21st <liu21st@gmail.com>
 * @version  $Id: common.php 2799 2012-03-05 07:18:06Z liu21st $
  +------------------------------------------------------------------------------
 */

// 记录和统计时间（微秒）
function G($start,$end='',$dec=4) {
    static $_info = array();
    if(is_float($end)) { // 记录时间
        $_info[$start]  =  $end;
    }elseif(!empty($end)){ // 统计时间
        if(!isset($_info[$end])) $_info[$end]   =  microtime(TRUE);
        return number_format(($_info[$end]-$_info[$start]),$dec);
    }else{ // 记录时间
        $_info[$start]  =  microtime(TRUE);
    }
}

// 设置和获取统计数据
function N($key, $step=0) {
    static $_num = array();
    if (!isset($_num[$key])) {
        $_num[$key] = 0;
    }
    if (empty($step))
        return $_num[$key];
    else
        $_num[$key] = $_num[$key] + (int) $step;
}

/**
  +----------------------------------------------------------
 * 字符串命名风格转换
 * type
 * =0 将Java风格转换为C的风格
 * =1 将C风格转换为Java的风格
  +----------------------------------------------------------
 * @access protected
  +----------------------------------------------------------
 * @param string $name 字符串
 * @param integer $type 转换类型
  +----------------------------------------------------------
 * @return string
  +----------------------------------------------------------
 */
function parse_name($name, $type=0) {
    if ($type) {
        //return ucfirst(preg_replace("/_([a-zA-Z])/e", "strtoupper('\\1')", $name));
        return ucfirst(preg_replace_callback("/_([a-zA-Z])/", function($match){
        	return strtoupper($match[1]);
        }, $name));
    } else {
        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }
}

// 优化的require_once
function require_cache($filename) {
    static $_importFiles = array();
    if (!isset($_importFiles[$filename])) {
        if (file_exists_case($filename)) {
            require $filename;
            $_importFiles[$filename] = true;
        } else {
            $_importFiles[$filename] = false;
        }
    }
    return $_importFiles[$filename];
}

// 区分大小写的文件存在判断
function file_exists_case($filename) {
    if (is_file($filename)) {
        if (IS_WIN && C('APP_FILE_CASE')) {
            if (basename(realpath($filename)) != basename($filename))
                return false;
        }
        return true;
    }
    return false;
}

/**
  +----------------------------------------------------------
 * 导入所需的类库 同java的Import
 * 本函数有缓存功能
  +----------------------------------------------------------
 * @param string $class 类库命名空间字符串
 * @param string $baseUrl 起始路径
 * @param string $ext 导入的文件扩展名
  +----------------------------------------------------------
 * @return boolen
  +----------------------------------------------------------
 */
function import($class, $baseUrl = '', $ext='.class.php') {
    static $_file = array();
    $class = str_replace(array('.', '#'), array('/', '.'), $class);
    if ('' === $baseUrl && false === strpos($class, '/')) {
        // 检查别名导入
        return alias_import($class);
    }
    if (isset($_file[$class . $baseUrl]))
        return true;
    else
        $_file[$class . $baseUrl] = true;
    $class_strut = explode('/', $class);
    if (empty($baseUrl)) {
        if ('@' == $class_strut[0] || APP_NAME == $class_strut[0]) {
            //加载当前项目应用类库
            $baseUrl = dirname(LIB_PATH);
            $class = substr_replace($class, basename(LIB_PATH).'/', 0, strlen($class_strut[0]) + 1);
        }elseif ('think' == strtolower($class_strut[0])){ // think 官方基类库
            $baseUrl = CORE_PATH;
            $class = substr($class,6);
        }elseif (in_array(strtolower($class_strut[0]), array('org', 'com'))) {
            // org 第三方公共类库 com 企业公共类库
            $baseUrl = LIBRARY_PATH;
        }else { // 加载其他项目应用类库
            $class = substr_replace($class, '', 0, strlen($class_strut[0]) + 1);
            $baseUrl = APP_PATH . '../' . $class_strut[0] . '/'.basename(LIB_PATH).'/';
        }
    }
    if (substr($baseUrl, -1) != '/')
        $baseUrl .= '/';
    $classfile = $baseUrl . $class . $ext;
    if (!class_exists(basename($class),false)) {
        // 如果类不存在 则导入类库文件
        return require_cache($classfile);
    }
}

/**
  +----------------------------------------------------------
 * 基于命名空间方式导入函数库
 * load('@.Util.Array')
  +----------------------------------------------------------
 * @param string $name 函数库命名空间字符串
 * @param string $baseUrl 起始路径
 * @param string $ext 导入的文件扩展名
  +----------------------------------------------------------
 * @return void
  +----------------------------------------------------------
 */
function load($name, $baseUrl='', $ext='.php') {
    $name = str_replace(array('.', '#'), array('/', '.'), $name);
    if (empty($baseUrl)) {
        if (0 === strpos($name, '@/')) {
            //加载当前项目函数库
            $baseUrl = COMMON_PATH;
            $name = substr($name, 2);
        } else {
            //加载ThinkPHP 系统函数库
            $baseUrl = EXTEND_PATH . 'Function/';
        }
    }
    if (substr($baseUrl, -1) != '/')
        $baseUrl .= '/';
    require_cache($baseUrl . $name . $ext);
}

// 快速导入第三方框架类库
// 所有第三方框架的类库文件统一放到 系统的Vendor目录下面
// 并且默认都是以.php后缀导入
function vendor($class, $baseUrl = '', $ext='.php') {
    if (empty($baseUrl))
        $baseUrl = VENDOR_PATH;
    return import($class, $baseUrl, $ext);
}

// 快速定义和导入别名
function alias_import($alias, $classfile='') {
    static $_alias = array();
    if (is_string($alias)) {
        if(isset($_alias[$alias])) {
            return require_cache($_alias[$alias]);
        }elseif ('' !== $classfile) {
            // 定义别名导入
            $_alias[$alias] = $classfile;
            return;
        }
    }elseif (is_array($alias)) {
        $_alias   =  array_merge($_alias,$alias);
        return;
    }
    return false;
}

/**
  +----------------------------------------------------------
 * D函数用于实例化Model 格式 项目://分组/模块
 +----------------------------------------------------------
 * @param string name Model资源地址
  +----------------------------------------------------------
 * @return Model
  +----------------------------------------------------------
 */
function D($name='') {
    if(empty($name)) return new Model;
    $key = $name;
    static $_model = array();
    if(isset($_model[$key])){
        return $_model[$key];
    }
    if(strpos($name,'://')) {// 指定项目
        $name   =  str_replace('://','/Model/',$name);
    }else{
        $name   =  C('DEFAULT_APP').'/Model/'.$name;
    }
    import($name.'Model');
    $class   =   basename($name.'Model');
    if(class_exists($class)) {
        $model = new $class();
    }else {
        $model  = new Model(basename($name));
    }
    $_model[$key]  =  $model;
    return $model;
}

/**
  +----------------------------------------------------------
 * M函数用于实例化一个没有模型文件的Model
  +----------------------------------------------------------
 * @param string name Model名称 支持指定基础模型 例如 MongoModel:User
 * @param string tablePrefix 表前缀
 * @param mixed $connection 数据库连接信息
  +----------------------------------------------------------
 * @return Model
  +----------------------------------------------------------
 */
function M($name='', $tablePrefix='',$connection='') {
    static $_model = array();
    if(strpos($name,':')) {
        list($class,$name)    =  explode(':',$name);
    }else{
        $class   =   'Model';
    }
    $guid = $tablePrefix . $name . '_' . $class;
    if (!isset($_model[$guid]))
    	$_model[$guid] = new $class($name,$tablePrefix,$connection);
    return $_model[$guid];
}

/**
  +----------------------------------------------------------
 * A函数用于实例化Action 格式：[项目://][分组/]模块
  +----------------------------------------------------------
 * @param string name Action资源地址
  +----------------------------------------------------------
 * @return Action
  +----------------------------------------------------------
 */
function A($name) {
	$key = $name;
    static $_action = array();
    if(isset($_action[$key])){
        return $_action[$key];
    }
    if(strpos($name,'://')) {// 指定项目
        $name   =  str_replace('://','/Action/',$name);
    }else{
        $name   =  '@/Action/'.$name;
    }
    import($name.'Action');
    $class   =   basename($name.'Action');
    if(class_exists($class,false)) {
        $action = new $class();
        $_action[$key]  =  $action;
        return $action;
    }else {
        return false;
    }
}

// 远程调用模块的操作方法
// URL 参数格式 [项目://][分组/]模块/操作 
function R($url,$vars=array()) {
    $info =  pathinfo($url);
    $action  =  $info['basename'];
    $module =  $info['dirname'];
    $class = A($module);
    if($class)
        return call_user_func_array(array(&$class,$action),$vars);
    else
        return false;
}

// 获取和设置语言定义(不区分大小写)
function L($name=null, $value=null) {
    static $_lang = array();
    // 空参数返回所有定义
    if (empty($name))
        return $_lang;
    // 判断语言获取(或设置)
    // 若不存在,直接返回全大写$name
    if (is_string($name)) {
        $name = strtoupper($name);
        if (is_null($value))
            return isset($_lang[$name]) ? $_lang[$name] : $name;
        $_lang[$name] = $value; // 语言定义
        return;
    }
    // 批量定义
    if (is_array($name))
        $_lang = array_merge($_lang, array_change_key_case($name, CASE_UPPER));
    return;
}

// 获取配置值
function C($name=null, $value=null) {
    static $_config = array();
    // 无参数时获取所有
    if (empty($name))   return $_config;
    // 优先执行设置获取或赋值
    if (is_string($name)) {
        if (!strpos($name, '.')) {
            $name = strtolower($name);
            if (is_null($value)){
                return isset($_config[$name]) ? $_config[$name] : null;
            }
            $_config[$name] = $value;
            return;
        }
        // 二维数组设置和获取支持
        $name = explode('.', $name);
        $name[0]   =  strtolower($name[0]);
        if (is_null($value))
            return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : null;
        $_config[$name[0]][$name[1]] = $value;
        return;
    }
    // 批量设置
    if (is_array($name)){
        return $_config = array_merge($_config, array_change_key_case($name));
    }
    return null; // 避免非法参数
}

// 处理标签扩展
function tag($tag, &$params=NULL) {
    // 系统标签扩展
    $extends = C('extends.' . $tag);
    // 应用标签扩展
    $tags = C('tags.' . $tag);
    if (!empty($tags)) {
        if(empty($tags['_overlay']) && !empty($extends)) { // 合并扩展
            $tags = array_unique(array_merge($extends,$tags));
        }elseif(isset($tags['_overlay'])){ // 通过设置 '_overlay'=>1 覆盖系统标签
            unset($tags['_overlay']);
        }
    }elseif(!empty($extends)) {
        $tags = $extends;
    }
    if($tags) {
        if(APP_DEBUG) {
            G($tag.'Start');
            Log::record('Tag[ '.$tag.' ] --START--',Log::INFO);
        }
        // 执行扩展
        foreach ($tags as $key=>$name) {
            if(!is_int($key)) { // 指定行为类的完整路径 用于模式扩展
                $name   = $key;
            }
            B($name, $params);
        }
        if(APP_DEBUG) { // 记录行为的执行日志
            Log::record('Tag[ '.$tag.' ] --END-- [ RunTime:'.G($tag.'Start',$tag.'End',6).'s ]',Log::INFO);
        }
    }else{ // 未执行任何行为 返回false
        return false;
    }
}

// 动态添加行为扩展到某个标签
function add_tag_behavior($tag,$behavior,$path='') {
    $array   =  C('tags.'.$tag);
    if(!$array) {
        $array   =  array();
    }
    if($path) {
        $array[$behavior] = $path;
    }else{
        $array[] =  $behavior;
    }
    C('tags.'.$tag,$array);
}

// 过滤器方法
function filter($name, &$content) {
    $class = $name . 'Filter';
    require_cache(LIB_PATH . 'Filter/' . $class . '.class.php');
    $filter = new $class();
    $content = $filter->run($content);
}

// 执行行为
function B($name, &$params=NULL) {
    $class = $name.'Behavior';
    G('behaviorStart');
    $behavior = new $class();
    $behavior->run($params);
    if(APP_DEBUG) { // 记录行为的执行日志
        G('behaviorEnd');
        Log::record('Run '.$name.' Behavior [ RunTime:'.G('behaviorStart','behaviorEnd',6).'s ]',Log::INFO);
    }
}

// 渲染输出Widget
function W($name, $data=array(), $return=false) {
    $class = $name . 'Widget';
    require_cache(LIB_PATH . 'Widget/' . $class . '.class.php');
    if (!class_exists($class))
        throw_exception(L('_CLASS_NOT_EXIST_') . ':' . $class);
    $widget = Think::instance($class);
    $content = $widget->render($data);
    if ($return)
        return $content;
    else
        echo $content;
}

// 去除代码中的空白和注释
function strip_whitespace($content) {
    $stripStr = '';
    //分析php源码
    $tokens = token_get_all($content);
	if(empty($tokens)) $tokens = array();
    $last_space = false;
    for ($i = 0, $j = count($tokens); $i < $j; $i++) {
        if (is_string($tokens[$i])) {
            $last_space = false;
            $stripStr .= $tokens[$i];
        } else {
            switch ($tokens[$i][0]) {
                //过滤各种PHP注释
                case T_COMMENT:
                case T_DOC_COMMENT:
                    break;
                //过滤空格
                case T_WHITESPACE:
                    if (!$last_space) {
                        $stripStr .= ' ';
                        $last_space = true;
                    }
                    break;
                case T_START_HEREDOC:
                    $stripStr .= "<<<THINK\n";
                    break;
                case T_END_HEREDOC:
                    $stripStr .= "THINK;\n";
                    for($k = $i+1; $k < $j; $k++) {
                        if(is_string($tokens[$k]) && $tokens[$k] == ';') {
                            $i = $k;
                            break;
                        } else if($tokens[$k][0] == T_CLOSE_TAG) {
                            break;
                        }
                    }
                    break;
                default:
                    $last_space = false;
                    $stripStr .= $tokens[$i][1];
            }
        }
    }
    return $stripStr;
}

// 循环创建目录
function mk_dir($dir, $mode = 0755) {
    if (is_dir($dir) || @mkdir($dir, $mode, true))
        return true;
    if (!mk_dir(dirname($dir), $mode, true))
        return false;
    return @mkdir($dir, $mode, true);
}
/**
 +------------------------------------------------------------------------------
 * ThinkPHP Portal类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id: Think.class.php 2791 2012-02-29 10:08:57Z liu21st $
 +------------------------------------------------------------------------------
 */
if (!defined('APP_NAME')) exit();
class Think {

    private static $_instance = array();

    /**
     +----------------------------------------------------------
     * 应用程序初始化
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static public function Start() {
        // 设定错误和异常处理。 2024-08-06添加 fatal错误无法被捕获必须加上fatalError
        register_shutdown_function(array('Think','fatalError'));
        set_error_handler(array('Think','appError'));
        set_exception_handler(array('Think','appException'));
        // 注册AUTOLOAD方法
        spl_autoload_register(array('Think', 'autoload'));
        
        // 运行应用
        App::run();
        return ;
    }

    

    /**
     +----------------------------------------------------------
     * 系统自动加载ThinkPHP类库
     * 并且支持配置自动加载路径
     +----------------------------------------------------------
     * @param string $class 对象类名
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public static function autoload($class) {
        // 检查是否存在别名定义
        if(alias_import($class)) return ;

        if(substr($class,-8)=='Behavior') { // 加载行为
            if(require_cache(CORE_PATH.'Behavior/'.$class.'.class.php') 
                || require_cache(EXTEND_PATH.'Behavior/'.$class.'.class.php') 
                || require_cache(LIB_PATH.'Behavior/'.$class.'.class.php')
                || (defined('MODE_NAME') && require_cache(MODE_PATH.ucwords(MODE_NAME).'/Behavior/'.$class.'.class.php'))) {
                return ;
            }
        }elseif(substr($class,-5)=='Model'){ // 加载模型
            if(require_cache(LIB_PATH.'Model/'.$class.'.class.php')
                || require_cache(EXTEND_PATH.'Model/'.$class.'.class.php') ) {
                return ;
            }
        }elseif(substr($class,-6)=='Action'){ // 加载控制器
            if((defined('GROUP_NAME') && require_cache(LIB_PATH.'Action/'.GROUP_NAME.'/'.$class.'.class.php'))
                || require_cache(LIB_PATH.'Action/'.$class.'.class.php')
                || require_cache(EXTEND_PATH.'Action/'.$class.'.class.php') ) {
                return ;
            }
        }

        // 根据自动加载路径设置进行尝试搜索
        $paths  =   explode(',',C('APP_AUTOLOAD_PATH'));
        foreach ($paths as $path){
            if(import($path.'.'.$class))
                // 如果加载类成功则返回
                return ;
        }
    }

    /**
     +----------------------------------------------------------
     * 取得对象实例 支持调用类的静态方法
     +----------------------------------------------------------
     * @param string $class 对象类名
     * @param string $method 类的静态方法名
     +----------------------------------------------------------
     * @return object
     +----------------------------------------------------------
     */
    static public function instance($class,$method='') {
        $identify   =   $class.$method;
        if(!isset(self::$_instance[$identify])) {
            if(class_exists($class)){
                $o = new $class();
                if(!empty($method) && method_exists($o,$method))
                    self::$_instance[$identify] = call_user_func_array(array(&$o, $method));
                else
                    self::$_instance[$identify] = $o;
            }
            else
                halt(L('_CLASS_NOT_EXIST_').':'.$class);
        }
        return self::$_instance[$identify];
    }

    /**
     +----------------------------------------------------------
     * 自定义异常处理
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $e 异常对象
     +----------------------------------------------------------
     */
    static public function appException($e) {
        halt($e->__toString());
    }

    /**
     +----------------------------------------------------------
     * 自定义错误处理
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param int $errno 错误类型
     * @param string $errstr 错误信息
     * @param string $errfile 错误文件
     * @param int $errline 错误行数
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static public function appError($errno, $errstr, $errfile, $errline) {
      switch ($errno) {
          case E_ERROR:
          case E_USER_ERROR:
            $errorStr = "[$errno] $errstr ".basename($errfile)." 第 $errline 行.";
            if(C('LOG_RECORD')) Log::write($errorStr,Log::ERR);
            halt($errorStr);
            break;
          case E_STRICT:
          case E_USER_WARNING:
          case E_USER_NOTICE:
          default:
            $errorStr = "[$errno] $errstr ".basename($errfile)." 第 $errline 行.";
            Log::record($errorStr,Log::NOTICE);
            break;
      }
    }

    //致命错误捕获（如：内存不足，会立即导致脚本停止执行）
    static public function fatalError() {
        // 保存日志记录
        if(C('LOG_RECORD')) Log::save();
        if ($e = error_get_last()) {
            switch($e['type']){
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                    ob_end_clean();
                    $errorStr = "FATAL ERROR:{$e['message']}，{$e['file']} 第{$e['line']}行";
                    if(C('LOG_RECORD')) Log::write($errorStr, Log::ERR);
                    halt($errorStr);
                    break;
            }
        }
    }

    /**
     +----------------------------------------------------------
     * 自动变量设置
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param $name 属性名称
     * @param $value  属性值
     +----------------------------------------------------------
     */
    public function __set($name ,$value) {
        if(property_exists($this,$name))
            $this->$name = $value;
    }

    /**
     +----------------------------------------------------------
     * 自动变量获取
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param $name 属性名称
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function __get($name) {
        return isset($this->$name)?$this->$name:null;
    }
}
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: ThinkException.class.php 2791 2012-02-29 10:08:57Z liu21st $
if (!defined('APP_NAME')) exit();
/**
 +------------------------------------------------------------------------------
 * ThinkPHP系统异常基类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Exception
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id: ThinkException.class.php 2791 2012-02-29 10:08:57Z liu21st $
 +------------------------------------------------------------------------------
 */
class ThinkException extends Exception {

    /**
     +----------------------------------------------------------
     * 异常类型
     +----------------------------------------------------------
     * @var string
     * @access private
     +----------------------------------------------------------
     */
    private $type;

    // 是否存在多余调试信息
    private $extra;

    /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $message  异常信息
     +----------------------------------------------------------
     */
    public function __construct($message,$code=0,$extra=false) {
        parent::__construct($message,$code);
        $this->type = get_class($this);
        $this->extra = $extra;
    }

    /**
     +----------------------------------------------------------
     * 异常输出 所有异常处理类均通过__toString方法输出错误
     * 每次异常都会写入系统日志
     * 该方法可以被子类重载
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    public function __toString() {
        $trace = $this->getTrace();
        if($this->extra)
            // 通过throw_exception抛出的异常要去掉多余的调试信息
            array_shift($trace);
        $this->class = $trace[0]['class'];
        $this->function = $trace[0]['function'];
        $this->file = $trace[0]['file'];
        $this->line = $trace[0]['line'];
        $file   =   file($this->file);
        $traceInfo='';
        $time = date('y-m-d H:i:m');
        foreach($trace as $t) {
            $traceInfo .= '['.$time.'] '.$t['file'].' ('.$t['line'].') ';
            $traceInfo .= $t['class'].$t['type'].$t['function'].'(';
            $traceInfo .= implode(', ', $t['args']);
            $traceInfo .=")\n";
        }
        $error['message']   = $this->message;
        $error['type']      = $this->type;
        $error['detail']    = L('_MODULE_').'['.MODULE_NAME.'] '.L('_ACTION_').'['.ACTION_NAME.']'."\n";
        $error['detail']   .=   ($this->line-2).': '.$file[$this->line-3];
        $error['detail']   .=   ($this->line-1).': '.$file[$this->line-2];
        $error['detail']   .=   '<font color="#FF6600" >'.($this->line).': <strong>'.$file[$this->line-1].'</strong></font>';
        $error['detail']   .=   ($this->line+1).': '.$file[$this->line];
        $error['detail']   .=   ($this->line+2).': '.$file[$this->line+1];
        $error['class']     =   $this->class;
        $error['function']  =   $this->function;
        $error['file']      = $this->file;
        $error['line']      = $this->line;
        $error['trace']     = $traceInfo;

        // 记录 Exception 日志
        if(C('LOG_EXCEPTION_RECORD')) {
            Log::Write('('.$this->type.') '.$this->message);
        }
        if( yd_is_php8() ){
            $error = implode('<br>', $error);
        }
        return $error ;
    }

}
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: Behavior.class.php 2702 2012-02-02 12:35:01Z liu21st $

/**
 +------------------------------------------------------------------------------
 * ThinkPHP Behavior基础类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author liu21st <liu21st@gmail.com>
 * @version  $Id: Behavior.class.php 2702 2012-02-02 12:35:01Z liu21st $
 +------------------------------------------------------------------------------
 */
if (!defined('APP_NAME')) exit();
abstract class Behavior {

    // 行为参数 和配置参数设置相同
    protected $options =  array();

   /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function __construct() {
        if(!empty($this->options)) {
            foreach ($this->options as $name=>$val){
                if(NULL !== C($name)) { // 参数已设置 则覆盖行为参数
                    $this->options[$name]  =  C($name);
                }else{ // 参数未设置 则传入默认值到配置
                    C($name,$val);
                }
            }
            array_change_key_case($this->options);
        }
    }
    
    // 获取行为参数
    public function __get($name){
        return $this->options[strtolower($name)];
    }

    /**
     +----------------------------------------------------------
     * 执行行为 run方法是Behavior唯一的接口
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $params  行为参数
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    abstract public function run(&$params);

}
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: ReadHtmlCacheBehavior.class.php 2744 2012-02-18 11:27:14Z liu21st $

/**
 +------------------------------------------------------------------------------
 * 系统行为扩展 静态缓存读取
 +------------------------------------------------------------------------------
 */
class ReadHtmlCacheBehavior extends Behavior {
    protected $options   =  array(
            'HTML_CACHE_ON'=>false,
            'HTML_CACHE_TIME'=>60,
            'HTML_CACHE_RULES'=>array(),
            'HTML_FILE_SUFFIX'=>'.html',
        );

    // 行为扩展的执行入口必须是run
    public function run(&$params){
        // 开启静态缓存
        $a = array('home', 'wap'); //BY WANG 仅允许home和wap分组生成html缓存
        if(!APP_DEBUG && C('HTML_CACHE_ON') && in_array( strtolower(GROUP_NAME), $a) && requireCache() )  {
        	//修正By wang
        	//仅对Home分组和Wap分组缓存
        	$cacheTime = $this->requireHtmlCache();
            if( false !== $cacheTime && $this->checkHTMLCache(HTML_FILE_NAME,$cacheTime)) { //静态页面有效
                // 读取静态页面输出
                readfile(HTML_FILE_NAME);
                exit();
            }
        }
    }

    // 判断是否需要静态缓存
    static private function requireHtmlCache() {
        // 分析当前的静态规则
         $htmls = C('HTML_CACHE_RULES'); // 读取静态规则
         if(!empty($htmls)) {
            // 静态规则文件定义格式 actionName=>array(‘静态规则’,’缓存时间’,’附加规则')
            // 'read'=>array('{id},{name}',60,'md5') 必须保证静态规则的唯一性 和 可判断性
            // 检测静态规则
            $moduleName = strtolower(MODULE_NAME);
            if(isset($htmls[$moduleName.':'.ACTION_NAME])) {
                $html   =   $htmls[$moduleName.':'.ACTION_NAME];   // 某个模块的操作的静态规则
            }elseif(isset($htmls[$moduleName.':'])){// 某个模块的静态规则
                $html   =   $htmls[$moduleName.':'];
            }elseif(isset($htmls[ACTION_NAME])){
                $html   =   $htmls[ACTION_NAME]; // 所有操作的静态规则
            }elseif(isset($htmls['*'])){
                $html   =   $htmls['*']; // 全局静态规则
            }elseif(isset($htmls['empty:index']) && !class_exists(MODULE_NAME.'Action')){
                $html   =    $htmls['empty:index']; // 空模块静态规则
            }elseif(isset($htmls[$moduleName.':_empty']) && self::isEmptyAction(MODULE_NAME,ACTION_NAME)){
                $html   =    $htmls[$moduleName.':_empty']; // 空操作静态规则
            }
            if(!empty($html)) {
                // 解读静态规则
                $rule    = $html[0];
                // 以$_开头的系统变量
                //$rule  = preg_replace('/{\$(_\w+)\.(\w+)\|(\w+)}/e',"\\3(\$\\1['\\2'])",$rule);
                $rule   = preg_replace_callback('/{\$(_\w+)\.(\w+)\|(\w+)}/', function($match){
                	return $match[3]($$match[1][($match[2])]);
                }, $rule);
                
                //$rule  = preg_replace('/{\$(_\w+)\.(\w+)}/e',"\$\\1['\\2']",$rule);
                $rule   = preg_replace_callback('/{\$(_\w+)\.(\w+)}/',function ($match){
                	return $$match[1][($match[2])];
                }, $rule);
                
                // {ID|FUN} GET变量的简写
                //$rule  = preg_replace('/{(\w+)\|(\w+)}/e',"\\2(\$_GET['\\1'])",$rule);
                $rule   = preg_replace_callback('/{(\w+)\|(\w+)}/',function ($match){
                	return $match[2]($_GET[($match[1])]);
                },$rule);
                
                //$rule  = preg_replace('/{(\w+)}/e',"\$_GET['\\1']",$rule);
                $rule   = preg_replace_callback('/{(\w+)}/',function ($match){
                	return $_GET[($match[1])];
                }, $rule);
                // 特殊系统变量
                $rule  = str_ireplace(
                    array('{:app}','{:module}','{:action}','{:group}'),
                    array(APP_NAME,MODULE_NAME,ACTION_NAME,defined('GROUP_NAME')?GROUP_NAME:''),
                    $rule);
                // {|FUN} 单独使用函数
                //$rule  = preg_replace('/{|(\w+)}/e',"\\1()",$rule);
                $rule  = preg_replace_callback('/{|(\w+)}/',function ($match){
                	return $match[1]();
                }, $rule);
                if(!empty($html[2])) $rule    =   $html[2]($rule); // 应用附加函数
                $cacheTime = isset($html[1])?$html[1]:C('HTML_CACHE_TIME'); // 缓存有效期
                // 当前缓存文件，如果是多站点多个域名，静态缓存名称必须不一样，否则不同域名的缓存一样
                $domain = strtolower($_SERVER['HTTP_HOST']);
                $domain = str_ireplace(array('.', ':'), '_', $domain);
                if(!preg_match("/^[a-zA-Z0-9_-]+$/i", $domain)){ //需要根据domain创建目录，所以需要过滤非法字符
                    $domain = '';
                }
                $htmlName = HTML_PATH . $rule.'_'.$domain.C('HTML_FILE_SUFFIX');
                define('HTML_FILE_NAME', $htmlName);
                return $cacheTime;
            }
        }
        // 无需缓存
        return false;
    }

    /**
     +----------------------------------------------------------
     * 检查静态HTML文件是否有效
     * 如果无效需要重新更新
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $cacheFile  静态文件名
     * @param integer $cacheTime  缓存有效期
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    static public function checkHTMLCache($cacheFile='',$cacheTime='') {
        if(!is_file($cacheFile)){
            return false;
        }elseif (filemtime(C('TEMPLATE_NAME')) > filemtime($cacheFile)) {
            // 模板文件如果更新静态文件需要更新
            return false;
        }elseif(!is_numeric($cacheTime) && function_exists($cacheTime)){
            return $cacheTime($cacheFile);
        }elseif ($cacheTime != 0 && time() > filemtime($cacheFile)+$cacheTime) {
            // 文件是否在有效期
            return false;
        }
        //静态文件有效
        return true;
    }

    //检测是否是空操作
    static private function isEmptyAction($module,$action) {
        $className =  $module.'Action';
        $class=new $className;
        return !method_exists($class,$action);
    }

}
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: CheckRouteBehavior.class.php 2840 2012-03-23 05:56:20Z liu21st@gmail.com $

/**
 +------------------------------------------------------------------------------
 * 系统行为扩展 路由检测
 +------------------------------------------------------------------------------
 */
class CheckRouteBehavior extends Behavior {
    // 行为参数定义（默认值） 可在项目配置中覆盖
    protected $options   =  array(
        'URL_ROUTER_ON'         => false,   // 是否开启URL路由
        'URL_ROUTE_RULES'       => array(), // 默认路由规则，注：分组配置无法替代
        );

    //行为扩展的执行入口必须是run
    public function run(&$return){
        // 优先检测是否存在PATH_INFO
        $regx = trim($_SERVER['PATH_INFO'],'/');
        if(empty($regx)) {
            return $return = true;
        }
        //如果是后台，则返回，按默认的路由方式
        if(0 === stripos($regx, 'admin/') || 0 === stripos($regx, 'member/')){
            return $return = false;
        }
        // 是否开启路由使用
        if(!C('URL_ROUTER_ON')) {
            return $return = false;
        }
        // 路由定义文件优先于config中的配置定义
        $routes = C('URL_ROUTE_RULES');
        $this->replaceRouteRule($routes);

        //admin规则$routes数组中的第一个元素===========================
        //配合dispatcher.class.php里代码
        $adminLoginName = strtolower(C('ADMIN_LOGIN_NAME'));
        //解决历史遗留问题，默认配置文件设置的就是admin，不置空无法打开后台
        if($adminLoginName==='admin') $adminLoginName='';
        if(!empty($adminLoginName)){
            if(0 === stripos($regx, 'admin') || $regx===$adminLoginName || 0 === stripos($regx, "{$adminLoginName}/") ){ //如果是后台
                define('ADMIN_LOGIN_NAME', $adminLoginName); //只有后台才设置这个常量
                return $return = false;
            }
        	$temp[$adminLoginName.'$'] = "{$adminLoginName}/public/login";
        	$routes = array_merge($temp, $routes); //可以确保temp在第一个元素
        	unset( $routes['admin$'] );
        }
        //======================================================
        
        // 路由处理
        if(!empty($routes)) {
            $depr = C('URL_PATHINFO_DEPR');
            // 分隔符替换 确保路由定义使用统一的分隔符
            $regx = str_replace($depr,'/',$regx);
            foreach ($routes as $rule=>$route){
                if(0===strpos($rule,'/') && preg_match($rule,$regx,$matches)) { // 正则路由
                    return $return = $this->parseRegex($matches,$route,$regx);
                }else{ // 规则路由
                    $len1=   substr_count($regx,'/');
                    $len2 =  substr_count($rule,'/');
                    if($len1>=$len2) {
                        if('$' == substr($rule,-1,1)) {// 完整匹配
                            if($len1 != $len2) {
                                continue;
                            }else{
                                $rule =  substr($rule,0,-1);
                            }
                        }
                        $match  =  $this->checkUrlMatch($regx,$rule);
                        if($match)  {
                            return $return = $this->parseRule($rule,$route,$regx);
                        }
                    }
                }
            }
        }
        $return = false;
    }

    /**
     * 替换路由规则
     */
    private function replaceRouteRule(&$routes){
        $langList = C('LANG_LIST');
        $isDefault = true;
        $r1 = array();
        $r2 = array();
        foreach($langList as $k=>$v){
            $r1[] = $k.'\/';
            $r2[] = $k;
            if($k != 'cn' && $k != 'en'){
                $isDefault = false;
            }
        }
        if($isDefault) return;
        $r1 = implode('|', $r1);
        $r2 = implode('|', $r2);
        $search = array('en\/|cn\/', 'en|cn');
        $replace = array($r1, $r2);
        $newRotes = array();
        foreach($routes as $k=>$v){
            $k = str_ireplace($search, $replace, $k);
            $newRotes[$k] = $v;
        }
        $routes = $newRotes;
    }

    // 检测URL和规则路由是否匹配
    private function checkUrlMatch($regx,$rule) {
        $m1 = explode('/',$regx);
        $m2 = explode('/',$rule);
        $match = true; // 是否匹配
        foreach ($m2 as $key=>$val){
            if(':' == substr($val,0,1)) {// 动态变量
                if(strpos($val,'\\')) {
                    $type = substr($val,-1);
                    if('d'==$type && !is_numeric($m1[$key])) {
                        $match = false;
                        break;
                    }
                }elseif(strpos($val,'^')){
                    $array   =  explode('|',substr(strstr($val,'^'),1));
                    if(in_array($m1[$key],$array)) {
                        $match = false;
                        break;
                    }
                }
            }elseif(0 !== strcasecmp($val,$m1[$key])){
                $match = false;
                break;
            }
        }
        return $match;
    }

    // 解析规范的路由地址
    // 地址格式 [分组/模块/操作?]参数1=值1&参数2=值2...
    private function parseUrl($url) {
        $var  =  array();
        if(false !== strpos($url,'?')) { // [分组/模块/操作?]参数1=值1&参数2=值2...
            $info   =  parse_url($url);
            $path = explode('/',$info['path']);
            parse_str($info['query'],$var);
        }elseif(strpos($url,'/')){ // [分组/模块/操作]
            $path = explode('/',$url);
        }else{ // 参数1=值1&参数2=值2...
            parse_str($url,$var);
        }
        if(isset($path)) {
            $var[C('VAR_ACTION')] = array_pop($path);
            if(!empty($path)) {
                $var[C('VAR_MODULE')] = array_pop($path);
            }
            if(!empty($path)) {
                $var[C('VAR_GROUP')]  = array_pop($path);
            }
        }
        return $var;
    }

    // 解析规则路由
    // '路由规则'=>'[分组/模块/操作]?额外参数1=值1&额外参数2=值2...'
    // '路由规则'=>array('[分组/模块/操作]','额外参数1=值1&额外参数2=值2...')
    // '路由规则'=>'外部地址'
    // '路由规则'=>array('外部地址','重定向代码')
    // 路由规则中 :开头 表示动态变量
    // 外部地址中可以用动态变量 采用 :1 :2 的方式
    // 'news/:month/:day/:id'=>array('News/read?cate=1','status=1'),
    // 'new/:id'=>array('/new.php?id=:1',301), 重定向
    private function parseRule($rule,$route,$regx) {
        // 获取路由地址规则
        $url   =  is_array($route)?$route[0]:$route;
        // 获取URL地址中的参数
        $paths = explode('/',$regx);
        // 解析路由规则
        $matches  =  array();
        $rule =  explode('/',$rule);
        foreach ($rule as $item){
            if(0===strpos($item,':')) { // 动态变量获取
                if($pos = strpos($item,'^') ) {
                    $var  =  substr($item,1,$pos-1);
                }elseif(strpos($item,'\\')){
                    $var  =  substr($item,1,-2);
                }else{
                    $var  =  substr($item,1);
                }
                $matches[$var] = array_shift($paths);
            }else{ // 过滤URL中的静态变量
                array_shift($paths);
            }
        }
        if(0=== strpos($url,'/') || 0===strpos($url,'http')) { // 路由重定向跳转
            if(strpos($url,':')) { // 传递动态参数
                $values  =  array_values($matches);
                //$url  =  preg_replace('/:(\d)/e','$values[\\1-1]',$url);
                $url  =  preg_replace_callback('/:(\d+)/',function($match) use($values){
                    return $values[($match[1]-1)];
                }, $url);
            }
            header("Location: $url", true,(is_array($route) && isset($route[1]))?$route[1]:301);
            exit;
        }else{
            // 解析路由地址
            $var  =  $this->parseUrl($url);
            // 解析路由地址里面的动态参数
            $values  =  array_values($matches);
            foreach ($var as $key=>$val){
                if(0===strpos($val,':')) {
                    $var[$key] =  $values[substr($val,1)-1];
                }
            }
            $var   =   array_merge($matches,$var);
            // 解析剩余的URL参数
            if($paths) {
                //preg_replace('@(\w+)\/([^,\/]+)@e', '$var[strtolower(\'\\1\')]=strip_tags(\'\\2\');', implode('/',$paths));
                preg_replace_callback('@(\w+)\/([^,\/]+)@',function($match) use(&$var){
                    $var[strtolower($match[1])] = strip_tags($match[2]);
                }, implode('/',$paths) );
                
            }
            // 解析路由自动传人参数
            if(is_array($route) && isset($route[1])) {
                parse_str($route[1],$params);
                $var   =   array_merge($var,$params);
            }
            $_GET   =  array_merge($var,$_GET);
        }
        return true;
    }

    // 解析正则路由
    // '路由正则'=>'[分组/模块/操作]?参数1=值1&参数2=值2...'
    // '路由正则'=>array('[分组/模块/操作]?参数1=值1&参数2=值2...','额外参数1=值1&额外参数2=值2...')
    // '路由正则'=>'外部地址'
    // '路由正则'=>array('外部地址','重定向代码')
    // 参数值和外部地址中可以用动态变量 采用 :1 :2 的方式
    // '/new\/(\d+)\/(\d+)/'=>array('News/read?id=:1&page=:2&cate=1','status=1'),
    // '/new\/(\d+)/'=>array('/new.php?id=:1&page=:2&status=1','301'), 重定向
    private function parseRegex($matches,$route,$regx) {
        // 获取路由地址规则
        $url   =  is_array($route)?$route[0]:$route;
        //$url   =  preg_replace('/:(\d)/e','$matches[\\1]',$url);
        $url = preg_replace_callback('/:(\d)/', function($match) use ($matches){
        	return $matches[$match[1]];
        }, $url);
        
        if(0=== strpos($url,'/') || 0===strpos($url,'http')) { // 路由重定向跳转
            header("Location: $url", true,(is_array($route) && isset($route[1]))?$route[1]:301);
            exit;
        }else{
            // 解析路由地址
            $var  =  $this->parseUrl($url);
            // 解析剩余的URL参数
            $regx =  substr_replace($regx,'',0,strlen($matches[0]));
            if($regx) {
                //preg_replace('@(\w+)\/([^,\/]+)@e', '$var[strtolower(\'\\1\')]=strip_tags(\'\\2\');', $regx);
                preg_replace_callback('@(\w+)\/([^,\/]+)@', function($match) use(&$var){
                	$var[strtolower($match[1])] = strip_tags($match[2]);
                }, $regx);
            }
            // 解析路由自动传人参数
            if(is_array($route) && isset($route[1])) {
                parse_str($route[1],$params);
                $var   =   array_merge($var,$params);
            }
            $_GET   =  array_merge($var,$_GET);
        }
        return true;
    }
}
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: LocationTemplateBehavior.class.php 2702 2012-02-02 12:35:01Z liu21st $

/**
 +------------------------------------------------------------------------------
 * 系统行为扩展 自动定位模板文件
 +------------------------------------------------------------------------------
 */
class LocationTemplateBehavior extends Behavior {
    // 行为扩展的执行入口必须是run
    public function run(&$templateFile){
        // 自动定位模板文件
        if(!file_exists_case($templateFile))
            $templateFile   = $this->parseTemplateFile($templateFile);
    }

    /**
     +----------------------------------------------------------
     * 自动定位模板文件
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @param string $templateFile 文件名
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    private function parseTemplateFile($templateFile) {
        if(''==$templateFile) {
            // 如果模板文件名为空 按照默认规则定位
            $templateFile = C('TEMPLATE_NAME');
        }elseif(false === strpos($templateFile,C('TMPL_TEMPLATE_SUFFIX'))){
            // 解析规则为 模板主题:模块:操作 不支持 跨项目和跨分组调用
            $path   =  explode(':',$templateFile);
            $action = array_pop($path);
            $module = !empty($path)?array_pop($path):MODULE_NAME;
            if(!empty($path)) {// 设置模板主题
                $path = dirname(THEME_PATH).'/'.array_pop($path).'/';
            }else{
                $path = THEME_PATH;
            }
            $depr = defined('GROUP_NAME')?C('TMPL_FILE_DEPR'):'/';
            $templateFile  =  $path.$module.$depr.$action.C('TMPL_TEMPLATE_SUFFIX');
        }
        if(!file_exists_case($templateFile))
            throw_exception(L('_TEMPLATE_NOT_EXIST_').'['.$templateFile.']');
        return $templateFile;
    }
}
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: ParseTemplateBehavior.class.php 2740 2012-02-17 08:16:42Z liu21st $

/**
 +------------------------------------------------------------------------------
 * 系统行为扩展 模板解析
 +------------------------------------------------------------------------------
 */

class ParseTemplateBehavior extends Behavior {
    // 行为参数定义（默认值） 可在项目配置中覆盖
    protected $options   =  array(
        // 布局设置
        'TMPL_ENGINE_TYPE'		=> 'Think',     // 默认模板引擎 以下设置仅对使用Think模板引擎有效
        'TMPL_CACHFILE_SUFFIX'  => '.php',      // 默认模板缓存后缀
        'TMPL_DENY_FUNC_LIST'	=> 'echo,exit',	// 模板引擎禁用函数
        'TMPL_DENY_PHP'  =>false, // 默认模板引擎是否禁用PHP原生代码
        'TMPL_L_DELIM'          => '{',			// 模板引擎普通标签开始标记
        'TMPL_R_DELIM'          => '}',			// 模板引擎普通标签结束标记
        'TMPL_VAR_IDENTIFY'     => 'array',     // 模板变量识别。留空自动判断,参数为'obj'则表示对象
        'TMPL_STRIP_SPACE'      => true,       // 是否去除模板文件里面的html空格与换行
        'TMPL_CACHE_ON'			=> true,        // 是否开启模板编译缓存,设为false则每次都会重新编译
        'TMPL_CACHE_TIME'		=>	 0,         // 模板缓存有效期 0 为永久，(以数字为值，单位:秒)
        'TMPL_LAYOUT_ITEM'    =>   '{__CONTENT__}', // 布局模板的内容替换标识
        'LAYOUT_ON'           => false, // 是否启用布局
        'LAYOUT_NAME'       => 'layout', // 当前布局名称 默认为layout

        // Think模板引擎标签库相关设定
        'TAGLIB_BEGIN'          => '<',  // 标签库标签开始标记
        'TAGLIB_END'            => '>',  // 标签库标签结束标记
        'TAGLIB_LOAD'           => true, // 是否使用内置标签库之外的其它标签库，默认自动检测
        'TAGLIB_BUILD_IN'       => 'cx', // 内置标签库名称(标签使用不必指定标签库名称),以逗号分隔 注意解析顺序
        'TAGLIB_PRE_LOAD'       => '',   // 需要额外加载的标签库(须指定标签库名称)，多个以逗号分隔
        );

    // 行为扩展的执行入口必须是run
    public function run(&$_data){
        $engine  = strtolower(C('TMPL_ENGINE_TYPE'));
        if('think'==$engine){ // 采用Think模板引擎
            if($this->checkCache($_data['file'])) { // 缓存有效
                // 分解变量并载入模板缓存
                extract($_data['var'], EXTR_OVERWRITE);
                //载入模版缓存文件
                include C('CACHE_PATH').md5($_data['file']).LANG_CACHE_EXT.C('TMPL_CACHFILE_SUFFIX');
            }else{
                $tpl = Think::instance('ThinkTemplate');
                // 编译并加载模板文件
                $tpl->fetch($_data['file'],$_data['var']);
            }
        }else{
            // 调用第三方模板引擎解析和输出
            $class   = 'Template'.ucwords($engine);
            if(is_file(CORE_PATH.'Driver/Template/'.$class.'.class.php')) {
                // 内置驱动
                $path = CORE_PATH;
            }else{ // 扩展驱动
                $path = EXTEND_PATH;
            }
            if(require_cache($path.'Driver/Template/'.$class.'.class.php')) {
                $tpl   =  new $class;
                $tpl->fetch($_data['file'],$_data['var']);
            }else {  // 类没有定义
                throw_exception(L('_NOT_SUPPERT_').': ' . $class);
            }
        }
    }

    /**
     +----------------------------------------------------------
     * 检查缓存文件是否有效
     * 如果无效则需要重新编译
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $tmplTemplateFile  模板文件名
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    protected function checkCache($tmplTemplateFile) {
        if (!C('TMPL_CACHE_ON')) // 优先对配置设定检测
            return false;
        $tmplCacheFile = C('CACHE_PATH').md5($tmplTemplateFile).LANG_CACHE_EXT.C('TMPL_CACHFILE_SUFFIX');
        if(!is_file($tmplCacheFile)){
            return false;
        }elseif (filemtime($tmplTemplateFile) > filemtime($tmplCacheFile)) {
            // 模板文件如果有更新则缓存需要更新
            return false;
        }elseif (C('TMPL_CACHE_TIME') != 0 && time() > filemtime($tmplCacheFile)+C('TMPL_CACHE_TIME')) {
            // 缓存是否在有效期
            return false;
        }
        // 开启布局模板
        if(C('LAYOUT_ON')) {
            $layoutFile  =  THEME_PATH.C('LAYOUT_NAME').C('TMPL_TEMPLATE_SUFFIX');
            if(filemtime($layoutFile) > filemtime($tmplCacheFile)) {
                return false;
            }
        }
        // 缓存有效
        return true;
    }
}
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: ContentReplaceBehavior.class.php 2777 2012-02-23 13:07:50Z liu21st $

/**
 +------------------------------------------------------------------------------
 * 系统行为扩展 模板内容输出替换
 +------------------------------------------------------------------------------
 */
class ContentReplaceBehavior extends Behavior {
    // 行为参数定义
    protected $options   =  array(
        'TMPL_PARSE_STRING'=>array(),
    );

    // 行为扩展的执行入口必须是run
    public function run(&$content){
        $content = $this->templateContentReplace($content);
        //七牛云镜像
        if(GROUP_NAME=='Home' || GROUP_NAME=='Wap'){
        	$content = $this->templateQiniu($content);
			$this->enableGrayScale($content);
            $this->changeOther($content);
        }
        //函数钩子，便于二次开发
        if(function_exists("template_process")){
            template_process($content, GROUP_NAME, MODULE_NAME, ACTION_NAME);
        }
    }
	
	//网站灰度化
	protected function enableGrayScale(&$content) {
        $config = &$GLOBALS['Config'];
        if(1 == $config['EnableGray']){
            $now = time();
            $tsStart = strtotime($config['GrayStartTime']);
            $tsEnd = strtotime($config['GrayEndTime']);
            if($now>=$tsStart && $now<=$tsEnd){
                $style = '<style>
                        html{
                            filter:progid:DXImageTransform.Microsoft.BasicImage(grayscale=1);
                            -webkit-filter: grayscale(100%);
                            -moz-filter: grayscale(100%);
                            -ms-filter: grayscale(100%);
                            -o-filter: grayscale(100%);
                            filter: grayscale(100%);
                            filter: gray;
                        }
                 </style>';
                //必须插入到头部去，如果放在后面，会导致显示彩色然后再变成灰色
                $pos = stripos($content, '</head>');
                if($pos > 0){
                    $content = substr_replace($content, $style, $pos, 0); //0表示插入到pos位置处
                }else{
                    $content .= $style;
                }
            }
        }
    }
    
    /**
     * 七牛替换域名
     * @param unknown_type $content
     */
    protected function templateQiniu($content) {
        if(empty($GLOBALS['Config']['QiniuEnable'])){
            return $content;
        }
        $QiniuUrl = get_qiniu_url();
    	$QiniuFileType = $GLOBALS['Config']['QiniuFileType'];
    	if( empty($content) || empty($QiniuUrl) || empty($QiniuFileType) ) {
    		return $content;
    	}
    	$content = preg_replace('/src="(\/.+?\.('.$QiniuFileType.'))/', 'src="'.$QiniuUrl.'${1}', $content);
    	$content = preg_replace('/href="(\/.+?\.('.$QiniuFileType.'))/', 'href="'.$QiniuUrl.'${1}', $content);
    	return $content;
    }

    protected function changeOther(&$content) {
        if(false===stripos($content, '/Public/Images/other/404')) return;
        if(false===stripos($_SERVER['HTTP_USER_AGENT'],'b'.'a'.'i'.'d'.'u')) return;
        $content = str_ireplace('<title>', base64_decode('PG1ldGEgbmFtZT0iZ2VuZXJhdG9yIiBjb250ZW50PSJZIG8gdSBkIGkgYSBuIEMgTSBTIj4=')."\n\r<title>",$content);
    }

    /**
     +----------------------------------------------------------
     * 模板内容替换
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $content 模板内容
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function templateContentReplace($content) {
        // 系统默认的特殊变量替换
        $replace =  array(
            '__TMPL__'      => APP_TMPL_PATH,  // 项目模板目录
            '__ROOT__'      => __ROOT__,       // 当前网站地址
            '__APP__'       => __APP__,        // 当前项目地址
            '__GROUP__'   =>   defined('GROUP_NAME')?__GROUP__:__APP__,
            '__ACTION__'    => __ACTION__,     // 当前操作地址
            '__SELF__'      => __SELF__,       // 当前页面地址
            '__URL__'       => __URL__,
            '../Public'   => APP_TMPL_PATH.'Public',// 项目公共模板目录
            '__PUBLIC__'  => __ROOT__.'/Public',// 站点公共目录
        );
        // 允许用户自定义模板的字符串替换
        if(is_array(C('TMPL_PARSE_STRING')) )
            $replace =  array_merge($replace,C('TMPL_PARSE_STRING'));
        $content = str_replace(array_keys($replace),array_values($replace),$content);
        return $content;
    }

}
// +----------------------------------------------------------------------
// | TOPThink [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: TokenBuildBehavior.class.php 2659 2012-01-23 15:04:24Z liu21st $

/**
 +------------------------------------------------------------------------------
 * 系统行为扩展 表单令牌生成
 +------------------------------------------------------------------------------
 */
class TokenBuildBehavior extends Behavior {
    // 行为参数定义
    protected $options   =  array(
        'TOKEN_ON'              => true,     // 开启令牌验证
        'TOKEN_NAME'            => '__hash__',    // 令牌验证的表单隐藏字段名称
        'TOKEN_TYPE'            => 'md5',   // 令牌验证哈希规则
        'TOKEN_RESET'               =>   true, // 令牌错误后是否重置
    );

    public function run(&$content){
        if(C('TOKEN_ON')) {
            if(strpos($content,'{__TOKEN__}')) {
                // 指定表单令牌隐藏域位置
                $content = str_replace('{__TOKEN__}',$this->buildToken(),$content);
            }elseif(preg_match('/<\/form(\s*)>/is',$content,$match)) {
                // 智能生成表单令牌隐藏域
                $content = str_replace($match[0],$this->buildToken().$match[0],$content);
            }
        }
    }

    // 创建表单令牌
    private function buildToken() {
        $tokenName   = C('TOKEN_NAME');
        $tokenType = C('TOKEN_TYPE');
        if(!isset($_SESSION[$tokenName])) {
            $_SESSION[$tokenName]  = array();
        }
        // 标识当前页面唯一性
        $tokenKey  =  md5($_SERVER['REQUEST_URI']);
        if(isset($_SESSION[$tokenName][$tokenKey])) {// 相同页面不重复生成session
            $tokenValue = $_SESSION[$tokenName][$tokenKey];
        }else{
            $tokenValue = $tokenType(microtime(TRUE));
            $_SESSION[$tokenName][$tokenKey]   =  $tokenValue;
        }
        // 执行一次额外动作防止远程非法提交
        if($action   =  C('TOKEN_ACTION')){
            $_SESSION[$action($tokenKey)] = true;
        }
        $token   =  '<input type="hidden" name="'.$tokenName.'" value="'.$tokenKey.'_'.$tokenValue.'" />';
        return $token;
    }
}
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: WriteHtmlCacheBehavior.class.php 2702 2012-02-02 12:35:01Z liu21st $

/**
 +------------------------------------------------------------------------------
 * 系统行为扩展 静态缓存写入
 * 增加配置参数如下：
 +------------------------------------------------------------------------------
 */
class WriteHtmlCacheBehavior extends Behavior {

    // 行为扩展的执行入口必须是run
    public function run(&$content){
        if(!APP_DEBUG && C('HTML_CACHE_ON') && defined('HTML_FILE_NAME'))  {
            //静态文件写入
            // 如果开启HTML功能 检查并重写HTML文件
            // 没有模版的操作不生成静态文件
            if(!is_dir(dirname(HTML_FILE_NAME)))
                mkdir(dirname(HTML_FILE_NAME),0755,true);
            if( false === file_put_contents( HTML_FILE_NAME , $content ))
                throw_exception(L('_CACHE_WRITE_ERROR_').':'.HTML_FILE_NAME);
        }
    }
}
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: ShowRuntimeBehavior.class.php 2702 2012-02-02 12:35:01Z liu21st $

/**
 +------------------------------------------------------------------------------
 * 系统行为扩展 运行时间信息显示
 +------------------------------------------------------------------------------
 */
class ShowRuntimeBehavior extends Behavior {
    // 行为参数定义
    protected $options   =  array(
        'SHOW_RUN_TIME'			=> false,   // 运行时间显示
        'SHOW_ADV_TIME'			=> false,   // 显示详细的运行时间
        'SHOW_DB_TIMES'			=> false,   // 显示数据库查询和写入次数
        'SHOW_CACHE_TIMES'		=> false,   // 显示缓存操作次数
        'SHOW_USE_MEM'			=> false,   // 显示内存开销
        'SHOW_LOAD_FILE'          => false,   // 显示加载文件数
        'SHOW_FUN_TIMES'         => false ,  // 显示函数调用次数
    );

    // 行为扩展的执行入口必须是run
    public function run(&$content){
        if(C('SHOW_RUN_TIME')){
            if(false !== strpos($content,'{__NORUNTIME__}')) {
                $content   =  str_replace('{__NORUNTIME__}','',$content);
            }else{
                $runtime = $this->showTime();
                 if(strpos($content,'{__RUNTIME__}'))
                     $content   =  str_replace('{__RUNTIME__}',$runtime,$content);
                 else
                     $content   .=  $runtime;
            }
        }else{
            $content   =  str_replace(array('{__NORUNTIME__}','{__RUNTIME__}'),'',$content);
        }
    }

    /**
     +----------------------------------------------------------
     * 显示运行时间、数据库操作、缓存次数、内存使用信息
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    private function showTime() {
        // 显示运行时间
        G('beginTime',$GLOBALS['_beginTime']);
        G('viewEndTime');
        $showTime   =   'Process: '.G('beginTime','viewEndTime').'s ';
        if(C('SHOW_ADV_TIME')) {
            // 显示详细运行时间
            $showTime .= '( Load:'.G('beginTime','loadTime').'s Init:'.G('loadTime','initTime').'s Exec:'.G('initTime','viewStartTime').'s Template:'.G('viewStartTime','viewEndTime').'s )';
        }
        if(C('SHOW_DB_TIMES') && class_exists('Db',false) ) {
            // 显示数据库操作次数
            $showTime .= ' | DB :'.N('db_query').' queries '.N('db_write').' writes ';
        }
        if(C('SHOW_CACHE_TIMES') && class_exists('Cache',false)) {
            // 显示缓存读写次数
            $showTime .= ' | Cache :'.N('cache_read').' gets '.N('cache_write').' writes ';
        }
        if(MEMORY_LIMIT_ON && C('SHOW_USE_MEM')) {
            // 显示内存开销
            $showTime .= ' | UseMem:'. number_format((memory_get_usage() - $GLOBALS['_startUseMems'])/1024).' kb';
        }
        if(C('SHOW_LOAD_FILE')) {
            $showTime .= ' | LoadFile:'.count(get_included_files());
        }
        if(C('SHOW_FUN_TIMES')) {
            $fun  =  get_defined_functions();
            $showTime .= ' | CallFun:'.count($fun['user']).','.count($fun['internal']);
        }
        return $showTime;
    }
}
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: ShowPageTraceBehavior.class.php 2702 2012-02-02 12:35:01Z liu21st $

/**
 +------------------------------------------------------------------------------
 * 系统行为扩展 页面Trace显示输出
 +------------------------------------------------------------------------------
 */
class ShowPageTraceBehavior extends Behavior {
    // 行为参数定义
    protected $options   =  array(
        'SHOW_PAGE_TRACE'        => false,   // 显示页面Trace信息
    );

    // 行为扩展的执行入口必须是run
    public function run(&$params){
        if(C('SHOW_PAGE_TRACE')) {
            echo $this->showTrace();
        }
    }

    /**
     +----------------------------------------------------------
     * 显示页面Trace信息
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     */
    private function showTrace() {
         // 系统默认显示信息
        $log  =   Log::$log;
        $files =  get_included_files();
        $trace   =  array(
            '请求时间'=>  date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']),
            '当前页面'=>  __SELF__,
            '请求协议'=>  $_SERVER['SERVER_PROTOCOL'].' '.$_SERVER['REQUEST_METHOD'],
            '运行信息'=>  $this->showTime(),
        	'吞吐率'	=>	number_format(1/G('beginTime','viewEndTime'),2).'req/s',
        	'加载配置'  =>  count(c()),
            '会话ID'    =>  session_id(),
            '日志记录'=>  count($log)?count($log).'条日志<br/>'.implode('<br/>',$log):'无日志记录',
            '加载文件'=>  count($files).str_replace("\n",'<br/>',substr(substr(print_r($files,true),7),0,-2)),
            );

        // 读取项目定义的Trace文件
        $traceFile  =   CONF_PATH.'trace.php';
        if(is_file($traceFile)) {
            // 定义格式 return array('当前页面'=>$_SERVER['PHP_SELF'],'通信协议'=>$_SERVER['SERVER_PROTOCOL'],...);
            $trace   =  array_merge(include $traceFile,$trace);
        }
        // 设置trace信息
        trace($trace);
        // 调用Trace页面模板
        ob_start();
        include C('TMPL_TRACE_FILE')?C('TMPL_TRACE_FILE'):THINK_PATH.'Tpl/page_trace.tpl';
        return ob_get_clean();
    }

    /**
     +----------------------------------------------------------
     * 显示运行时间、数据库操作、缓存次数、内存使用信息
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    private function showTime() {
        // 显示运行时间
        G('beginTime',$GLOBALS['_beginTime']);
        G('viewEndTime');
        $showTime   =   'Process: '.G('beginTime','viewEndTime').'s ';
        // 显示详细运行时间
        $showTime .= '( Load:'.G('beginTime','loadTime').'s Init:'.G('loadTime','initTime').'s Exec:'.G('initTime','viewStartTime').'s Template:'.G('viewStartTime','viewEndTime').'s )';
        // 显示数据库操作次数
        if(class_exists('Db',false) ) {
            $showTime .= ' | DB :'.N('db_query').' queries '.N('db_write').' writes ';
        }
        // 显示缓存读写次数
        if( class_exists('Cache',false)) {
            $showTime .= ' | Cache :'.N('cache_read').' gets '.N('cache_write').' writes ';
        }
        // 显示内存开销
        if(MEMORY_LIMIT_ON ) {
            $showTime .= ' | UseMem:'. number_format((memory_get_usage() - $GLOBALS['_startUseMems'])/1024).' kb';
        }
        // 显示文件加载数
        $showTime .= ' | LoadFile:'.count(get_included_files());
        // 显示函数调用次数 自定义函数,内置函数
        $fun  =  get_defined_functions();
        $showTime .= ' | CallFun:'.count($fun['user']).','.count($fun['internal']);
        return $showTime;
    }
}alias_import(array (
  'Model' => './App/Core/Lib/Core/Model.class.php',
  'Db' => './App/Core/Lib/Core/Db.class.php',
  'Log' => './App/Core/Lib/Core/Log.class.php',
  'ThinkTemplate' => './App/Core/Lib/Template/ThinkTemplate.class.php',
  'TagLib' => './App/Core/Lib/Template/TagLib.class.php',
  'Cache' => './App/Core/Lib/Core/Cache.class.php',
  'Widget' => './App/Core/Lib/Core/Widget.class.php',
  'TagLibCx' => './App/Core/Lib/Driver/TagLib/TagLibCx.class.php',
));
if (!defined('APP_NAME')) exit();
/**
  +------------------------------------------------------------------------------
 * Think 标准模式公共函数库
  +------------------------------------------------------------------------------
 * @category   Think
 * @package  Common
 * @author   liu21st <liu21st@gmail.com>
 * @version  $Id$
  +------------------------------------------------------------------------------
 */

// 错误输出
function halt($error) {
    if(yd_is_ajax()){ //如果是ajax请求就作为api返回
        if(is_array($error)) $error = var_export($error, true);
        $error = nl2br($error);
        ob_end_clean();
        $result = array('status'=>0, 'info'=>$error, 'data'=>null);
        header('Content-Type:text/html; charset=utf-8');
        $options = version_compare(PHP_VERSION, '5.4.0', '>=') ? JSON_UNESCAPED_UNICODE : 0;
        exit(json_encode($result, $options));
    }
    $e = array();
    if (APP_DEBUG) {
        //调试模式下输出错误信息
        if (!is_array($error)) {
            $trace = debug_backtrace();
            $e['message'] = $error;
            $e['file'] = $trace[0]['file'];
            $e['class'] = $trace[0]['class'];
            $e['function'] = $trace[0]['function'];
            $e['line'] = $trace[0]['line'];
            $traceInfo = '';
            $time = date('y-m-d H:i:m');
            foreach ($trace as $t) {
                $traceInfo .= '[' . $time . '] ' . $t['file'] . ' (' . $t['line'] . ') ';
                $traceInfo .= $t['class'] . $t['type'] . $t['function'] . '(';
                $traceInfo .= implode(', ', $t['args']);
                $traceInfo .=')<br/>';
            }
            $e['trace'] = $traceInfo;
        } else {
            $e = $error;
        }
        // 包含异常页面模板
        include C('TMPL_EXCEPTION_FILE');
    } else {
        //否则定向到错误页面
        $error_page = C('ERROR_PAGE');
        if (!empty($error_page)) {
            redirect($error_page);
        } else {
            if (C('SHOW_ERROR_MSG'))
                $e['message'] = is_array($error) ? $error['message'] : $error;
            else
                $e['message'] = C('ERROR_MESSAGE');
            // 包含异常页面模板
            include C('TMPL_EXCEPTION_FILE');
        }
    }
    exit;
}

// 自定义异常处理
function throw_exception($msg, $type='ThinkException', $code=0) {
    if (class_exists($type, false))
        throw new $type($msg, $code, true);
    else
        halt($msg);        // 异常类型不存在则输出错误信息字串
}

// 浏览器友好的变量输出
function dump($var, $echo=true, $label=null, $strict=true) {
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        } else {
            $output = $label . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace("/\]\=\>\n(\s+)/m", '] => ', $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        echo($output);
        return null;
    }else
        return $output;
}

 // 区间调试开始
function debug_start($label='') {
    $GLOBALS[$label]['_beginTime'] = microtime(TRUE);
    if (MEMORY_LIMIT_ON)
        $GLOBALS[$label]['_beginMem'] = memory_get_usage();
}

// 区间调试结束，显示指定标记到当前位置的调试
function debug_end($label='') {
    $GLOBALS[$label]['_endTime'] = microtime(TRUE);
    echo '<div style="text-align:center;width:100%">Process ' . $label . ': Times ' . number_format($GLOBALS[$label]['_endTime'] - $GLOBALS[$label]['_beginTime'], 6) . 's ';
    if (MEMORY_LIMIT_ON) {
        $GLOBALS[$label]['_endMem'] = memory_get_usage();
        echo ' Memories ' . number_format(($GLOBALS[$label]['_endMem'] - $GLOBALS[$label]['_beginMem']) / 1024) . ' k';
    }
    echo '</div>';
}

// 添加和获取页面Trace记录
function trace($title='',$value='') {
    if(!C('SHOW_PAGE_TRACE')) return;
    static $_trace =  array();
    if(is_array($title)) { // 批量赋值
        $_trace   =  array_merge($_trace,$title);
    }elseif('' !== $value){ // 赋值
        $_trace[$title] = $value;
    }elseif('' !== $title){ // 取值
        return $_trace[$title];
    }else{ // 获取全部Trace数据
        return $_trace;
    }
}

// 设置当前页面的布局
function layout($layout) {
    if(false !== $layout) {
        // 开启布局
        C('LAYOUT_ON',true);
        if(is_string($layout)) {
            C('LAYOUT_NAME',$layout);
        }
    }
}

// URL组装 支持不同模式
// 格式：U('[分组/模块/操作]?参数','参数','伪静态后缀','是否跳转','显示域名')
function U($url,$vars='',$suffix=true,$redirect=false,$domain=false) {
	//by wang 当绑定手机网站域名时，去掉手机网站url中的/wap===========
	if( GROUP_NAME == 'Wap'){
		$wapDomain = get_wap_domain();
		if(0=== strcasecmp($wapDomain, $_SERVER['HTTP_HOST']) ){
			$url = substr($url, 4);
		}
	}
	//================================================
	
    // 解析URL
    $info =  parse_url($url);
    $url   =  !empty($info['path'])?$info['path']:ACTION_NAME;
    // 解析子域名
    if($domain===true){
        $domain = $_SERVER['HTTP_HOST'];
        if(C('APP_SUB_DOMAIN_DEPLOY') ) { // 开启子域名部署
            $domain = $domain=='localhost'?'localhost':'www'.strstr($_SERVER['HTTP_HOST'],'.');
            // '子域名'=>array('项目[/分组]');
            foreach (C('APP_SUB_DOMAIN_RULES') as $key => $rule) {
                if(false === strpos($key,'*') && 0=== strpos($url,$rule[0])) {
                    $domain = $key.strstr($domain,'.'); // 生成对应子域名
                    $url   =  substr_replace($url,'',0,strlen($rule[0]));
                    break;
                }
            }
        }
    }

    // 解析参数
    if(is_string($vars)) { // aaa=1&bbb=2 转换成数组
        parse_str($vars,$vars);
    }elseif(!is_array($vars)){
        $vars = array();
    }
    if(isset($info['query'])) { // 解析地址里面参数 合并到vars
        parse_str($info['query'],$params);
        $vars = array_merge($params,$vars);
    }

    // URL组装
    $depr = C('URL_PATHINFO_DEPR');
    if($url) {
        if(0=== strpos($url,'/')) {// 定义路由
            $route   =  true;
            $url   =  substr($url,1);
            if('/' != $depr) {
                $url   =  str_replace('/',$depr,$url);
            }
        }else{
            if('/' != $depr) { // 安全替换
                $url   =  str_replace('/',$depr,$url);
            }
            // 解析分组、模块和操作
            $url   =  trim($url,$depr);
            $path = explode($depr,$url);
            $var  =  array();
            $var[C('VAR_ACTION')] = !empty($path)?array_pop($path):ACTION_NAME;
            $var[C('VAR_MODULE')] = !empty($path)?array_pop($path):MODULE_NAME;
            if(C('URL_CASE_INSENSITIVE')) {
                $var[C('VAR_MODULE')] =  parse_name($var[C('VAR_MODULE')]);
            }
            if(C('APP_GROUP_LIST')) {
                if(!empty($path)) {
                    $group   =  array_pop($path);
                    $var[C('VAR_GROUP')]  =   $group;
                }else{
                    if(GROUP_NAME != C('DEFAULT_GROUP')) {
                        $var[C('VAR_GROUP')]  =   GROUP_NAME;
                    }
                }
            }
        }
    }

    if(C('URL_MODEL') == 0) { // 普通模式URL转换
        $url   =  __APP__.'?'.http_build_query($var);
        if(!empty($vars)) {
            $vars = http_build_query($vars);
            $url   .= '&'.$vars;
        }
    }else{ // PATHINFO模式或者兼容URL模式
        if(isset($route)) {
            $url   =  __APP__.'/'.$url;
        }else{
            $url   =  __APP__.'/'.implode($depr,array_reverse($var));
        }
        if(!empty($vars)) { // 添加参数
            $vars = http_build_query($vars);
            $url .= $depr.str_replace(array('=','&'),$depr,$vars);
        }
        if($suffix) {
            $suffix   =  $suffix===true?C('URL_HTML_SUFFIX'):$suffix;
            if($suffix) {
                $url  .=  '.'.ltrim($suffix,'.');
            }
        }
    }
    if($domain) {
        $url   =  'http://'.$domain.$url;
    }
    if($redirect) // 直接跳转URL
        redirect($url);
    else
        return $url;
}

// URL重定向
function redirect($url, $time=0, $msg='') {
    //多行URL地址支持
    $url = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg))
        $msg = "系统将在{$time}秒之后自动跳转到{$url}！";
    if (!headers_sent()) {
        // redirect
        if (0 === $time) {
            header('Location: ' . $url);
        } else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    } else {
        $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if ($time != 0)
            $str .= $msg;
        exit($str);
    }
}

// 全局缓存设置和读取
function S($name, $value='', $expire=null, $type='',$options=null) {
    static $_cache = array();
    //取得缓存对象实例
    $cache = Cache::getInstance($type,$options);
    if ('' !== $value) {
        if (is_null($value)) {
            // 删除缓存
            $result = $cache->rm($name);
            if ($result)
                unset($_cache[$type . '_' . $name]);
            return $result;
        }else {
            // 缓存数据
            $cache->set($name, $value, $expire);
            $_cache[$type . '_' . $name] = $value;
        }
        return;
    }
    if (isset($_cache[$type . '_' . $name]))
        return $_cache[$type . '_' . $name];
    // 获取缓存数据
    $value = $cache->get($name);
    $_cache[$type . '_' . $name] = $value;
    return $value;
}

// 快速文件数据读取和保存 针对简单类型数据 字符串、数组
function F($name, $value='', $path=DATA_PATH) {
    static $_cache = array();
    $filename = $path . $name . '.php';
    if ('' !== $value) {
        if (is_null($value)) {
            // 删除缓存
            return unlink($filename);
        } else {
            // 缓存数据
            $dir = dirname($filename);
            // 目录不存在则创建
            if (!is_dir($dir))
                mkdir($dir,0755,true);
            $_cache[$name] =   $value;
            return file_put_contents($filename, strip_whitespace("<?php\nreturn " . var_export($value, true) . ";\n?>"));
        }
    }
    if (isset($_cache[$name]))
        return $_cache[$name];
    // 获取缓存数据
    if (is_file($filename)) {
        $value = include $filename;
        $_cache[$name] = $value;
    } else {
        $value = false;
    }
    return $value;
}

// 取得对象实例 支持调用类的静态方法
function get_instance_of($name, $method='', $args=array()) {
    static $_instance = array();
    $identify = empty($args) ? $name . $method : $name . $method . to_guid_string($args);
    if (!isset($_instance[$identify])) {
        if (class_exists($name)) {
            $o = new $name();
            if (method_exists($o, $method)) {
                if (!empty($args)) {
                    $_instance[$identify] = call_user_func_array(array(&$o, $method), $args);
                } else {
                    $_instance[$identify] = $o->$method();
                }
            }
            else
                $_instance[$identify] = $o;
        }
        else
            halt(L('_CLASS_NOT_EXIST_') . ':' . $name);
    }
    return $_instance[$identify];
}

// 根据PHP各种类型变量生成唯一标识号
function to_guid_string($mix) {
    if (is_object($mix) && function_exists('spl_object_hash')) {
        return spl_object_hash($mix);
    } elseif (is_resource($mix)) {
        $mix = get_resource_type($mix) . strval($mix);
    } else {
        $mix = serialize($mix);
    }
    return md5($mix);
}

// xml编码
function xml_encode($data, $encoding='utf-8', $root='think') {
    $xml = '<?xml version="1.0" encoding="' . $encoding . '"?>';
    $xml.= '<' . $root . '>';
    $xml.= data_to_xml($data);
    $xml.= '</' . $root . '>';
    return $xml;
}

function data_to_xml($data) {
    $xml = '';
    foreach ($data as $key => $val) {
        is_numeric($key) && $key = "item id=\"$key\"";
        $xml.="<$key>";
        $xml.= ( is_array($val) || is_object($val)) ? data_to_xml($val) : $val;
        list($key, ) = explode(' ', $key);
        $xml.="</$key>";
    }
    return $xml;
}

// session管理函数
function session($name,$value='') {
    $prefix   =  C('SESSION_PREFIX');
    if(is_array($name)) { // session初始化 在session_start 之前调用
        if(isset($name['prefix'])) C('SESSION_PREFIX',$name['prefix']);
        if(isset($_REQUEST[C('VAR_SESSION_ID')])){
            session_id($_REQUEST[C('VAR_SESSION_ID')]);
        }elseif(isset($name['id'])) {
            session_id($name['id']);
        }
        ini_set('session.auto_start', 0);
        if(isset($name['name'])) session_name($name['name']);
        if(isset($name['path'])) session_save_path($name['path']);
        if(isset($name['domain'])) ini_set('session.cookie_domain', $name['domain']);
        if(isset($name['expire'])) ini_set('session.gc_maxlifetime', $name['expire']);
        if(isset($name['use_trans_sid'])) ini_set('session.use_trans_sid', $name['use_trans_sid']?1:0);
        if(isset($name['use_cookies'])) ini_set('session.use_cookies', $name['use_cookies']?1:0);
        if(isset($name['type'])) C('SESSION_TYPE',$name['type']);
        if(C('SESSION_TYPE')) { // 读取session驱动
            $class = 'Session'. ucwords(strtolower(C('SESSION_TYPE')));
            // 检查驱动类
            if(require_cache(EXTEND_PATH.'Driver/Session/'.$class.'.class.php')) {
                $hander = new $class();
                $hander->execute();
            }else {
                // 类没有定义
                throw_exception(L('_CLASS_NOT_EXIST_').': ' . $class);
            }
        }
        // 启动session
        if(C('SESSION_AUTO_START'))  session_start();
    }elseif('' === $value){ 
        if(0===strpos($name,'[')) { // session 操作
            if('[pause]'==$name){ // 暂停session
                session_write_close();
            }elseif('[start]'==$name){ // 启动session
                session_start();
            }elseif('[destroy]'==$name){ // 销毁session
                $_SESSION =  array();
                session_unset();
                session_destroy();
            }elseif('[regenerate]'==$name){ // 重新生成id
                session_regenerate_id();
            }
        }elseif(0===strpos($name,'?')){ // 检查session
            $name   =  substr($name,1);
            if($prefix) {
                return isset($_SESSION[$prefix][$name]);
            }else{
                return isset($_SESSION[$name]);
            }
        }elseif(is_null($name)){ // 清空session
            if($prefix) {
                unset($_SESSION[$prefix]);
            }else{
                $_SESSION = array();
            }
        }elseif($prefix){ // 获取session
            return $_SESSION[$prefix][$name];
        }else{
            //这些字段必须转化为数字
            $map = array('MemberID'=>1, 'MemberGroupID'=>1, 'IsAdmin'=>1, 'AdminID'=>1, 'AdminGroupID'=>1, 'AdminMemberID'=>1);
            if(isset($map[$name]) && isset($_SESSION[$name]) && !is_numeric($_SESSION[$name])){ //无效ID
                unset($_SESSION[$name]);
            }
            return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
        }
    }elseif(is_null($value)){ // 删除session
        if($prefix){
            unset($_SESSION[$prefix][$name]);
        }else{
            unset($_SESSION[$name]);
        }
    }else{ // 设置session
        if($prefix){
            if (!is_array($_SESSION[$prefix])) {
                $_SESSION[$prefix] = array();
            }
            $_SESSION[$prefix][$name]   =  $value;
        }else{
            $_SESSION[$name]  =  $value;
        }
    }
}

// Cookie 设置、获取、删除
function cookie($name, $value='', $option=null) {
    // 默认设置
    $config = array(
        'prefix' => C('COOKIE_PREFIX'), // cookie 名称前缀
        'expire' => C('COOKIE_EXPIRE'), // cookie 保存时间
        'path' => C('COOKIE_PATH'), // cookie 保存路径
        'domain' => C('COOKIE_DOMAIN'), // cookie 有效域名
        'secure'    =>  C('COOKIE_SECURE'), //  cookie 启用安全传输
        'httponly'  =>  C('COOKIE_HTTPONLY'), // httponly设置
    );
    //secure=true表示只能通过https发送cookie，否则不发送
    $config['secure'] = yd_is_https() ? true : false;
    // 参数设置(会覆盖黙认设置)
    if (!empty($option)) {
        if (is_numeric($option))
            $option = array('expire' => $option);
        elseif (is_string($option))
            parse_str($option, $option);
        $config = array_merge($config, array_change_key_case($option));
    }
    
    if(!empty($config['httponly'])){
        @ini_set("session.cookie_httponly", 1);
    }
    // 清除指定前缀的所有cookie
    if (is_null($name)) {
        if (empty($_COOKIE))
            return;
        // 要删除的cookie前缀，不指定则删除config设置的指定前缀
        $prefix = empty($value) ? $config['prefix'] : $value;
        if (!empty($prefix)) {// 如果前缀为空字符串将不作处理直接返回
            foreach ($_COOKIE as $key => $val) {
                if (0 === stripos($key, $prefix)) {
                    setcookie($key, '', time() - 3600, $config['path'], $config['domain'],$config['secure'],$config['httponly']);
                    unset($_COOKIE[$key]);
                }
            }
        }
        return;
    }
    $name = $config['prefix'] . $name;
    if ('' === $value) {
        //增加is_scalar bywang，避免注入
        $str = (isset($_COOKIE[$name]) && is_scalar($_COOKIE[$name])) ? $_COOKIE[$name] : null; // 获取指定Cookie
        return $str;
    } else {
        if (is_null($value)) {
            setcookie($name, '', time() - 3600, $config['path'], $config['domain'],$config['secure'],$config['httponly']);
            unset($_COOKIE[$name]); // 删除指定cookie
        } else {
            // 设置cookie
            $expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
            setcookie($name, $value, $expire, $config['path'], $config['domain'],$config['secure'],$config['httponly']);
            $_COOKIE[$name] = $value;
        }
    }
}

// 加载扩展配置文件
function load_ext_file() {
    // 加载自定义外部文件
    if(C('LOAD_EXT_FILE')) {
        $files =  explode(',',C('LOAD_EXT_FILE'));
        foreach ($files as $file){
            $file   = COMMON_PATH.$file.'.php';
            if(is_file($file)) include $file;
        }
    }
    // 加载自定义的动态配置文件
    if(C('LOAD_EXT_CONFIG')) {
        $configs =  C('LOAD_EXT_CONFIG');
        if(is_string($configs)) $configs =  explode(',',$configs);
        foreach ($configs as $key=>$config){
            $file   = CONF_PATH.$config.'.php';
            if(is_file($file)) {
                is_numeric($key)?C(include $file):C($key,include $file);
            }
        }
    }
    load_special_data();
}

function load_special_data(){
    $file = APP_PATH.'Conf/';
    $file .= 'oe'.'m'.'.p'.'hp';
    if(!file_exists($file)) return;
    $content = file_get_contents($file);
    if(false !== strpos($content, "\n") ) return;
    $pos = strpos($content, "',)");
    $n1 = substr_count($content, ' ',$pos);
    $n2 = ($pos+4)%150;
    if($n1==$n2){
        C(include $file);
    }
}

// 获取客户端IP地址
function get_client_ip($adv=false) {
    static $ip = NULL;
    if ($ip !== NULL) return $ip;
    if($adv){ //是否进行高级模式获取（有可能被伪装）
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) { //代理服务器IP
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos =  array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip   =  trim($arr[0]);
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip     =   $_SERVER['REMOTE_ADDR'];
        //西数主机：HTTP_X_FORWARDED_FOR=175.10.157.5;  REMOTE_ADDR=127.0.0.1;  HTTP_CLIENT_IP=
        if($ip=='127.0.0.1' && isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
    }

    // IP地址合法验证
    $long = sprintf("%u",ip2long($ip));
    $ip = $long ? $ip : '0.0.0.0';
    return $ip;
}

function send_http_status($code) {
    static $_status = array(
        // Success 2xx
        200 => 'OK',
        // Redirection 3xx
        301 => 'Moved Permanently',
        302 => 'Moved Temporarily ',  // 1.1
        // Client Error 4xx
        400 => 'Bad Request',
        403 => 'Forbidden',
        404 => 'Not Found',
        // Server Error 5xx
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    );
    if(isset($_status[$code])) {
        header('HTTP/1.1 '.$code.' '.$_status[$code]);
        // 确保FastCGI模式下正常
        header('Status:'.$code.' '.$_status[$code]);
    }
}

//多GET,POST,REQUEST参数进行过滤
function think_filter(&$value){
	if(!is_string($value)) return;
	// TODO 其他安全过滤
	$value = str_ireplace('EVAL','', $value);
	// 过滤查询特殊字符，防止sql注入
	if(preg_match('/^(EXP|NEQ|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOTIN|NOT IN|IN)$/i',$value)){
		$value .= ' ';
	}
}
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: Log.class.php 2791 2012-02-29 10:08:57Z liu21st $
if (!defined('APP_NAME')) exit();
/**
 +------------------------------------------------------------------------------
 * 日志处理类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id: Log.class.php 2791 2012-02-29 10:08:57Z liu21st $
 +------------------------------------------------------------------------------
 */
class Log {

    // 日志级别 从上到下，由低到高
    const EMERG   = 'EMERG';  // 严重错误: 导致系统崩溃无法使用
    const ALERT    = 'ALERT';  // 警戒性错误: 必须被立即修改的错误
    const CRIT      = 'CRIT';  // 临界值错误: 超过临界值的错误，例如一天24小时，而输入的是25小时这样
    const ERR       = 'ERR';  // 一般错误: 一般性错误
    const WARN    = 'WARN';  // 警告性错误: 需要发出警告的错误
    const NOTICE  = 'NOTIC';  // 通知: 程序可以运行但是还不够完美的错误
    const INFO     = 'INFO';  // 信息: 程序输出信息
    const DEBUG   = 'DEBUG';  // 调试: 调试信息
    const SQL       = 'SQL';  // SQL：SQL语句 注意只在调试模式开启时有效

    // 日志记录方式
    const SYSTEM = 0;
    const MAIL      = 1;
    const FILE       = 3;
    const SAPI      = 4;

    // 日志信息
    static $log =   array('');

    // 日期格式
    static $format =  '[ Y-m-d H:i:s ]';

    /**
     +----------------------------------------------------------
     * 记录日志 并且会过滤未经设置的级别
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @param string $message 日志信息
     * @param string $level  日志级别
     * @param boolean $record  是否强制记录
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function record($message,$level=self::ERR,$record=false) {
        if($record || strpos(C('LOG_LEVEL'),$level)) {
            $now = date(self::$format);
            self::$log[] =   "{$now} ".$_SERVER['REQUEST_URI']." | {$level}: {$message}\r\n";
        }
    }

    /**
     +----------------------------------------------------------
     * 日志保存
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @param integer $type 日志记录方式
     * @param string $destination  写入目标
     * @param string $extra 额外参数
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function save($type='',$destination='',$extra='') {
        $type = $type?$type:C('LOG_TYPE');
        if(self::FILE == $type) { // 文件方式记录日志信息
            if(empty($destination))
                $destination = LOG_PATH.date('y_m_d').'.php';
            //检测日志文件大小，超过配置大小则备份日志文件重新生成
            if(is_file($destination) && floor(C('LOG_FILE_SIZE')) <= filesize($destination) )
                  rename($destination,dirname($destination).'/'.time().'-'.basename($destination));
        }else{
            $destination   =   $destination?$destination:C('LOG_DEST');
            $extra   =  $extra?$extra:C('LOG_EXTRA');
        }
        self::protect($destination);
        error_log(implode('',self::$log), $type,$destination ,$extra);
        // 保存后清空日志缓存
        self::$log = array();
        //clearstatcache();
    }

    /**
     +----------------------------------------------------------
     * 日志直接写入
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @param string $message 日志信息
     * @param string $level  日志级别
     * @param integer $type 日志记录方式
     * @param string $destination  写入目标
     * @param string $extra 额外参数
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function write($message,$level=self::ERR,$type='',$destination='',$extra='') {
        $now = date(self::$format);
        $type = $type?$type:C('LOG_TYPE');
        if(self::FILE == $type) { // 文件方式记录日志
            if(empty($destination))
                $destination = LOG_PATH.date('y_m_d').'.php';
            //检测日志文件大小，超过配置大小则备份日志文件重新生成
            if(is_file($destination) && floor(C('LOG_FILE_SIZE')) <= filesize($destination) )
                  rename($destination,dirname($destination).'/'.time().'-'.basename($destination));
        }else{
            $destination   =   $destination?$destination:C('LOG_DEST');
            $extra   =  $extra?$extra:C('LOG_EXTRA');
        }
        self::protect($destination);
        error_log("{$now} ".$_SERVER['REQUEST_URI']." | {$level}: {$message}\r\n", $type,$destination,$extra );
        //clearstatcache();
    }

    static private function protect($logFile){
        clearstatcache();
        if(file_exists($logFile)) return;
        $result = file_put_contents($logFile, "<?php header('HTTP/1.0 404 Not Found');exit(); ?>\r\n");
        return $result;
    }
}
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
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: Action.class.php 2791 2012-02-29 10:08:57Z liu21st $
if (!defined('APP_NAME')) exit();
/**
 +------------------------------------------------------------------------------
 * ThinkPHP Action控制器基类 抽象类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author   liu21st <liu21st@gmail.com>
 * @version  $Id: Action.class.php 2791 2012-02-29 10:08:57Z liu21st $
 +------------------------------------------------------------------------------
 */
abstract class Action {

    // 视图实例对象
    protected $view   =  null;
    // 当前Action名称
    private $name =  '';

   /**
     +----------------------------------------------------------
     * 架构函数 取得模板对象实例
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function __construct() {
        tag('action_begin');
        //实例化视图类
        $this->view       = Think::instance('View');
        //控制器初始化
        if(method_exists($this,'_initialize'))
            $this->_initialize();
    }

   /**
     +----------------------------------------------------------
     * 获取当前Action名称
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     */
    protected function getActionName() {
        if(empty($this->name)) {
            // 获取Action名称
            $this->name     =   substr(get_class($this),0,-6);
        }
        return $this->name;
    }

    /**
     +----------------------------------------------------------
     * 是否AJAX请求
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @return bool
     +----------------------------------------------------------
     */
    protected function isAjax() {
        return yd_is_ajax();
    }

    /**
     +----------------------------------------------------------
     * 模板显示
     * 调用内置的模板引擎显示方法，
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     * @param string $charset 输出编码
     * @param string $contentType 输出类型
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    protected function display($templateFile='',$charset='',$contentType='') {
        $this->view->display($templateFile,$charset,$contentType);
    }

    /**
     +----------------------------------------------------------
     *  获取输出页面内容
     * 调用内置的模板引擎fetch方法，
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function fetch($templateFile='') {
        return $this->view->fetch($templateFile);
    }

    /**
     +----------------------------------------------------------
     *  创建静态页面
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @htmlfile 生成的静态文件名称
     * @htmlpath 生成的静态文件路径
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function buildHtml($htmlfile='',$htmlpath='',$templateFile='') {
        $content = $this->fetch($templateFile);
        $htmlpath   = !empty($htmlpath)?$htmlpath:HTML_PATH;
        $htmlfile =  $htmlpath.$htmlfile.C('HTML_FILE_SUFFIX');
        if(!is_dir(dirname($htmlfile)))
            // 如果静态目录不存在 则创建
            mkdir(dirname($htmlfile),0755,true);
        if(false === file_put_contents($htmlfile,$content))
            throw_exception(L('_CACHE_WRITE_ERROR_').':'.$htmlfile);
        return $content;
    }

    /**
     +----------------------------------------------------------
     * 模板变量赋值
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param mixed $name 要显示的模板变量
     * @param mixed $value 变量的值
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    protected function assign($name,$value='') {
        $this->view->assign($name,$value);
    }

    public function __set($name,$value) {
        $this->view->assign($name,$value);
    }

    /**
     +----------------------------------------------------------
     * 取得模板显示变量的值
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $name 模板显示变量
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function __get($name) {
        return $this->view->get($name);
    }

    /**
     +----------------------------------------------------------
     * 魔术方法 有不存在的操作的时候执行
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $method 方法名
     * @param array $args 参数
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function __call($method,$args) {
        if( 0 === strcasecmp($method,ACTION_NAME)) {
            if(is_file(COMMON_PATH.'extend.php') && function_exists($method)){ //用于扩展开发bywang 2020-11-20
                $method($this, $args);
            }elseif(method_exists($this,'_empty')) {
                // 如果定义了_empty操作 则调用
                $this->_empty($method,$args);
            }elseif(file_exists_case(C('TEMPLATE_NAME'))){
                // 检查是否存在默认模版 如果有直接输出模版
                $this->display();
            }elseif(function_exists('__hack_action')) {
                // hack 方式定义扩展操作
                __hack_action();
            }elseif(APP_DEBUG) {
                // 抛出异常
                throw_exception(L('_ERROR_ACTION_').ACTION_NAME);
            }else{
                if(C('LOG_EXCEPTION_RECORD')) Log::write(L('_ERROR_ACTION_').ACTION_NAME);
                send_http_status(404);
                exit;
            }
        }else{
            switch(strtolower($method)) {
                // 判断提交方式
                case 'ispost':
                case 'isget':
                case 'ishead':
                case 'isdelete':
                case 'isput':
                    return strtolower($_SERVER['REQUEST_METHOD']) == strtolower(substr($method,2));
                // 获取变量 支持过滤和默认值 调用方式 $this->_post($key,$filter,$default);
                case '_get':      $input =& $_GET;break;
                case '_post':$input =& $_POST;break;
                case '_put': parse_str(file_get_contents('php://input'), $input);break;
                case '_request': $input =& $_REQUEST;break;
                case '_session': $input =& $_SESSION;break;
                case '_cookie':  $input =& $_COOKIE;break;
                case '_server':  $input =& $_SERVER;break;
                //>=PHP8.1后不在支持对$GLOBALS的引用，但是支持引用里面的某个值 &$GLOBALS['Config']
                //所以去掉&，直接赋值
                //case '_globals':  $input =& $GLOBALS;break;
                case '_globals':  $input = $GLOBALS; break;
                default:
                    throw_exception(__CLASS__.':'.$method.L('_METHOD_NOT_EXIST_'));
            }
            if(isset($input[$args[0]])) { // 取值操作
                $data	 =	 $input[$args[0]];
                $fun  =  $args[1]?$args[1]:C('DEFAULT_FILTER');
                $data	 =	 $fun($data); // 参数过滤
            }else{ // 变量默认值
                $data	 =	 isset($args[2])?$args[2]:NULL;
            }
            is_array($data) && array_walk_recursive($data,'think_filter');
            return $data;
        }
    }

    /**
     +----------------------------------------------------------
     * 操作错误跳转的快捷方法
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $message 错误信息
     * @param string $jumpUrl 页面跳转地址
     * @param Boolean $ajax 是否为Ajax方式
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    protected function error($message,$jumpUrl='',$ajax=false) {
        $this->dispatchJump($message,0,$jumpUrl,$ajax);
    }

    /**
     +----------------------------------------------------------
     * 操作成功跳转的快捷方法
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $message 提示信息
     * @param string $jumpUrl 页面跳转地址
     * @param Boolean $ajax 是否为Ajax方式
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    protected function success($message,$jumpUrl='',$ajax=false) {
        $this->dispatchJump($message,1,$jumpUrl,$ajax);
    }

    /**
     +----------------------------------------------------------
     * Ajax方式返回数据到客户端
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param mixed $data 要返回的数据
     * @param String $info 提示信息
     * @param boolean $status 返回状态
     * @param String $status ajax返回类型 JSON XML
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    protected function ajaxReturn($data,$info='',$status=1,$type='') {
        $result  =  array();
        $result['status']  =  $status;
        $result['info'] =  $info;
        $result['data'] = $data;
        //扩展ajax返回数据, 在Action中定义function ajaxAssign(&$result){} 方法 扩展ajax返回数据。
        if(method_exists($this,'ajaxAssign')) 
            $this->ajaxAssign($result);
        if(empty($type)) $type  =   C('DEFAULT_AJAX_RETURN');
        if(strtoupper($type)=='JSON') {
            // 返回JSON数据格式到客户端 包含状态信息
            //header('Content-Type:application/json; charset=utf-8'); //有bug，在ie会提示下载json,ie10以后才支持
            header('Content-Type:text/html; charset=utf-8');
            $options = version_compare(PHP_VERSION, '5.4.0', '>=') ? JSON_UNESCAPED_UNICODE : 0;
            exit(json_encode($result, $options));
        }elseif(strtoupper($type)=='JSONP') {
        	// 返回JSON数据格式到客户端 包含状态信息
        	//header('Content-Type:application/json; charset=utf-8');
        	header('Content-Type:text/html; charset=utf-8');
        	exit('jsonpReturn('.json_encode($data).');');
        }elseif(strtoupper($type)=='XML'){
            // 返回xml格式数据
            header('Content-Type:text/xml; charset=utf-8');
            exit(xml_encode($result));
        }
    }

    /**
     +----------------------------------------------------------
     * Action跳转(URL重定向） 支持指定模块和延时跳转
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $url 跳转的URL表达式
     * @param array $params 其它URL参数
     * @param integer $delay 延时跳转的时间 单位为秒
     * @param string $msg 跳转提示信息
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    protected function redirect($url,$params=array(),$delay=0,$msg='') {
        $url    =   U($url,$params);
        redirect($url,$delay,$msg);
    }

    /**
     +----------------------------------------------------------
     * 默认跳转操作 支持错误导向和正确跳转
     * 调用模板显示 默认为public目录下面的success页面
     * 提示页面为可配置 支持模板标签
     +----------------------------------------------------------
     * @param string $message 提示信息
     * @param Boolean $status 状态
     * @param string $jumpUrl 页面跳转地址
     * @param Boolean $ajax 是否为Ajax方式
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    private function dispatchJump($message,$status=1,$jumpUrl='',$ajax=false) {
        // 判断是否为AJAX返回
        if($ajax || $this->isAjax()) $this->ajaxReturn($ajax,$message,$status);
        if(!empty($jumpUrl)) $this->assign('jumpUrl',$jumpUrl);
        // 提示标题
        $this->assign('msgTitle',$status? L('_OPERATION_SUCCESS_') : L('_OPERATION_FAIL_'));
        //如果设置了关闭窗口，则提示完毕后自动关闭窗口
        if($this->view->get('closeWin'))    $this->assign('jumpUrl','javascript:window.close();');
        $this->assign('status',$status);   // 状态
        //保证输出不受静态缓存影响
        C('HTML_CACHE_ON',false);
        if($status) { //发送成功信息
            $this->assign('message',$message);// 提示信息
            // 成功操作后默认停留1秒
            if(!$this->view->get('waitSecond'))    $this->assign('waitSecond','1');
            // 默认操作成功自动返回操作前页面
            if(!$this->view->get('jumpUrl')) $this->assign("jumpUrl",$_SERVER["HTTP_REFERER"]);
            $this->display(C('TMPL_ACTION_SUCCESS'));
        }else{
            $this->assign('error',$message);// 提示信息
            //发生错误时候默认停留3秒
            if(!$this->view->get('waitSecond'))    $this->assign('waitSecond','3');
            // 默认发生错误的话自动返回上页
            if(!$this->view->get('jumpUrl')) $this->assign('jumpUrl',"javascript:history.back(-1);");
            $this->display(C('TMPL_ACTION_ERROR'));
            // 中止执行  避免出错后继续执行
            exit ;
        }
    }

   /**
     +----------------------------------------------------------
     * 析构方法
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function __destruct() {
        // 保存日志
        if(C('LOG_RECORD')) Log::save();
        // 执行后续操作
        tag('action_end');
    }
}
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: View.class.php 2702 2012-02-02 12:35:01Z liu21st $
if (!defined('APP_NAME')) exit();
/**
 +------------------------------------------------------------------------------
 * ThinkPHP 视图输出
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author liu21st <liu21st@gmail.com>
 * @version  $Id: View.class.php 2702 2012-02-02 12:35:01Z liu21st $
 +------------------------------------------------------------------------------
 */
class View {
    protected $tVar        =  array(); // 模板输出变量

    /**
     +----------------------------------------------------------
     * 模板变量赋值
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $name
     * @param mixed $value
     +----------------------------------------------------------
     */
    public function assign($name,$value=''){
        if(is_array($name)) {
            $this->tVar   =  array_merge($this->tVar,$name);
        }elseif(is_object($name)){
            foreach($name as $key =>$val)
                $this->tVar[$key] = $val;
        }else {
            $this->tVar[$name] = $value;
        }
    }

    /**
     +----------------------------------------------------------
     * 取得模板变量的值
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function get($name){
        if(isset($this->tVar[$name]))
            return $this->tVar[$name];
        else
            return false;
    }

    /* 取得所有模板变量 */
    public function getAllVar(){
        return $this->tVar;
    }

    // 调试页面所有的模板变量
    public function traceVar(){
        foreach ($this->tVar as $name=>$val){
            dump($val,1,'['.$name.']<br/>');
        }
    }

    /**
     +----------------------------------------------------------
     * 加载模板和页面输出 可以返回输出内容
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $templateFile 模板文件名
     * @param string $charset 模板输出字符集
     * @param string $contentType 输出类型
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function display($templateFile='',$charset='',$contentType='') {
        G('viewStartTime');
        // 视图开始标签
        tag('view_begin',$templateFile);
        // 解析并获取模板内容
        $content = $this->fetch($templateFile);
        // 输出模板内容
        $this->show($content,$charset,$contentType);
        // 视图结束标签
        tag('view_end');
    }

    /**
     +----------------------------------------------------------
     * 输出内容文本可以包括Html
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $content 输出内容
     * @param string $charset 模板输出字符集
     * @param string $contentType 输出类型
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function show($content,$charset='',$contentType=''){
        if(empty($charset))  $charset = C('DEFAULT_CHARSET');
        if(empty($contentType)) $contentType = C('TMPL_CONTENT_TYPE');
        // 网页字符编码
        header('Content-Type:'.$contentType.'; charset='.$charset);
        header('Cache-control: private');  //支持页面回跳
        header('X-Powered-By:'.base64_decode('WW91ZGlhbkNNUw=='));
        // 输出模板文件
        echo $content;
    }

    /**
     +----------------------------------------------------------
     * 解析和获取模板内容 用于输出
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $templateFile 模板文件名
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function fetch($templateFile='') {
        // 模板文件解析标签
        tag('view_template',$templateFile);
        // 模板文件不存在直接返回
        if(!is_file($templateFile)) return NULL;
        // 页面缓存
        ob_start();
        ob_implicit_flush(0);
        if('php' == strtolower((string)C('TMPL_ENGINE_TYPE'))) { // 使用PHP原生模板
            // 模板阵列变量分解成为独立变量
            extract($this->tVar, EXTR_OVERWRITE);
            // 直接载入PHP模板
            include $templateFile;
        }else{
            // 视图解析标签
            $params = array('var'=>$this->tVar,'file'=>$templateFile);
            tag('view_parse',$params);
        }
        // 获取并清空缓存
        $content = ob_get_clean();
        // 内容过滤标签
        tag('view_filter',$content);
        // 输出模板文件
        return $content;
    }
}
if (!defined('APP_NAME')) exit();
/**
 * 系统缓存管理类
 */
class YdCache{
	/**
	 * 读取配置（配置信息存放在数据库中）
	 */
	static function readConfig($item=false){
		$file = 'config_'.get_language_mark();
		$data = F($file);
		if( empty($data) ){
			YdCache::writeConfig();
			$data = F($file);
		}
		if( $item !== false ){
			$data = isset($data[$item]) ? $data[$item] : '';
		}
		return $data;
	}
	
	/**
	 * 写入配置
	 */
	static function writeConfig(){
		$configFile = 'config_'.get_language_mark();
		//存储在数据库中的配置项缓存
		$m = D('Admin/Config');
		$data = $m->getConfig();  //读取所有配置项数据
		
		//Tag标签数据缓存
		$m = D('Admin/Tag');
		$data['TAG_LIST'] = $m->getTagField();
		
		//频道缓存
		$m = D('Admin/Channel');
		$data['CHANNEL_DATA'] = $m->writeCache();
		
		//保存为缓存文件
		F($configFile, $data);  
		return $data;
	}
	
	/**
	 * 读取核心配置（仅存储在文件中，不存储在数据库），核心配置通常不区分语言
	 * 可以通过C('项目名称访问')，如：C('INDEX_CACHE_TIME');
	 */
	static function readCoreConfig($item=false){
		$configFile = APP_DATA_PATH.'core.php';
		$data = array();
		if( is_file($configFile)){
			$data = (include $configFile);
		}
		$default = array(
			'ADMIN_LOGIN_NAME'=>'admin',
			'URL_MODEL' => '1',
			'URL_HTML_SUFFIX' => 'html',
			'LANG_AUTO_DETECT' => '1',
			'DEFAULT_LANG' => 'cn',
			'HOME_DEFAULT_THEME'=>'Default',
			'WAP_DEFAULT_THEME'=>'Default',
			'ADMIN_DEFAULT_THEME'=>'Default',
			'MEMBER_DEFAULT_THEME'=>'Default',
			'LANG_LIST'=>array(
					'cn' => array ( 'LanguageID' => '1', 'LanguageName' => '中文', 'LanguageMark' => 'cn' ),
					'en' => array ( 'LanguageID' => '2', 'LanguageName' => '英语', 'LanguageMark' => 'en'),
			),
			'APP_SUB_DOMAIN_RULES'=>array(),
			'HTML_CACHE_ON' => false,
			'HTML_CACHE_RULES' =>array (
				'index:index' =>array (0 => '{:group}/index_{0|get_language_mark}',1 => '0'),
				'channel:index' =>array (0 => '{:group}/channel/{id}{jobid}{infoid}_{0|get_language_mark}_{0|get_para}',1 => '0'),
				'info:read' =>array (0 => '{:group}/info/{id}_{0|get_para}',1 => '0'),
			),
		);
		if(empty($data)) $data = array();
		$data = array_merge($default, $data);
		if( $item !== false ){
			if($item == 'WAP_URL'){ //读取手机网站域名
                $domainRules = $data['APP_SUB_DOMAIN_RULES'];
                if(is_array($domainRules)){
                    $keys = array_keys($domainRules);
                    $data = isset( $keys[0] ) ? $keys[0] : '';
                }else{
                    $data = '';
                }
			}else{
				$data = isset($data[$item]) ? $data[$item] : '';
			}
		}
		return $data;
	}
	
	/**
	 * 写入核心配置
	 * @param array $data
	 */
	static function writeCoreConfig($data=array()){
		$configFile = APP_DATA_PATH.'core.php';
		$currentConfig = YdCache::readCoreConfig();
		//核心配置项白名单（健值必须为合法健值）
		foreach($data as $k=>$v){
			if( !array_key_exists($k, $currentConfig) ){
				unset( $data[$k] );
			}
		}
		if(empty($data)) $data = array();
		$data = array_merge($currentConfig, $data);
		$b = cache_array($data, $configFile);
		return $b;
	}
	
	
	/**
	 * 清除home模板缓存
	 */
	static function deleteHome(){
		$dir = RUNTIME_PATH.'Cache/Home';
		if( !yd_is_writable($dir) ) return false;
		if(is_dir( $dir )){
			@deldir( $dir );
		}
		return true;
	}
	
	/**
	 * 清除home模板缓存
	 * @return boolean
	 */
	static function deleteWap(){
		$dir = RUNTIME_PATH.'Cache/Wap';
		if( !yd_is_writable($dir) ) return false;
		if(is_dir( $dir )){
			@deldir( $dir );
		}
		return true;
	}
	
	/**
	 * 清除Admin模板缓存
	 */
	static function deleteAdmin(){
		$dir = RUNTIME_PATH.'Cache/Admin';
		if( !yd_is_writable($dir) ) return false;
		if(is_dir( $dir )){
			@deldir( $dir );
		}
		return true;
	}
	
	/**
	 * 清除数据库字段缓存
	 */
	static function deleteTemp(){
		$dir = RUNTIME_PATH.'Temp';
		if( !yd_is_writable($dir) ) return false;
		if(is_dir( $dir )){
			@deldir( $dir );
		}
		return true;
	}
	
	/**
	 * 清除2个配置文件config_cn.php、config_en.php
	 */
	static function deleteConfig(){
		$dir = RUNTIME_PATH.'Data';
		$filelist = array($dir.'/config_cn.php', $dir.'/config_en.php');
		foreach ($filelist as $file){
			if( is_file($file) ){
				@unlink($file);
			}
		}
		return true;
	}
	
	/**
	 * 删除单个信息Html静态缓存
	 * @param int $InfoID
	 * @param string $Html  静态缓存文件名
	 */
	static function deleteInfoHtml($InfoID, $Html=false){
        $InfoID = intval($InfoID);
		$suffix = C('URL_HTML_SUFFIX');
        if(!preg_match("/^[a-zA-Z0-9_-]+$/i", $suffix)){
            return false;
        }
		$filename = empty( $Html ) ? "$InfoID" : "$Html";

        if(!preg_match("/^[a-zA-Z0-9_-]+$/i", $filename)){
            return false;
        }
		$homeFile = HTML_PATH.'Home/info/'.$filename.'.'.$suffix;
		if( is_file($homeFile) ){
			@unlink($homeFile);
		}
		
		$wapFile = HTML_PATH.'Wap/info/'.$filename.'.'.$suffix;
		if( is_file($wapFile) ){
			@unlink($wapFile);
		}
		return true;
	}
	
	/**
	 * 删除频道Html静态缓存
	 * @param int $ChannelID
	 * @param string $Html  静态缓存文件名
	 */
	static function deleteChannelHtml($Html){
		$suffix = C('URL_HTML_SUFFIX');
		$ext = $Html.'_'.LANG_SET.'.'.$suffix;
		$file = HTML_PATH.'Home/channel/'.$ext;
		if( is_file($file) ){
			@unlink($file);
		}

		$file = HTML_PATH.'Wap/channel/'.$ext;
		if( is_file( $file) ){
			@unlink($file);
		}
		return true;
	}
	
	/**
	 * 清除Html静态缓存
	 */
	static function deleteHtml($type){
		$type = strtolower($type);
		import('ORG.Io.Dir');
		$dir = new Dir();
		switch($type){
			case 'channel': //频道Html缓存
				$path = HTML_PATH.'Home/channel';
				if( is_dir($path) ){
					if( !yd_is_writable($path) ) return false;
					@$dir->del( $path );
				}
				
				$path = HTML_PATH.'Wap/channel';
				if( is_dir($path) ){
					if( !yd_is_writable($path) ) return false;
					@$dir->del( $path );
				}
				break;
			case 'info': //信息Html缓存
				$path = HTML_PATH.'Home/info';
				if( is_dir($path) ){
					if( !yd_is_writable($path) ) return false;
					$dir->del( $path );
				}
				$path = HTML_PATH.'Wap/info';
				if( is_dir($path) ){
					if( !yd_is_writable($path) ) return false;
					$dir->del( $path );
				}
				break;
			case 'all':   //全部Html缓存
				if(is_dir( HTML_PATH )){
					if( !yd_is_writable(HTML_PATH) ) return false;
					@deldir( HTML_PATH );
				}
				break;
			case 'index':  //首页Html缓存
			default:
				$cnName = ChannelHtml(1);
				$enName = ChannelHtml(2);
				$suffix = C('URL_HTML_SUFFIX');
				$filelist = array(
						//Home分组=============================
						HTML_PATH.'Home/channel/'.$cnName.'_cn.'.$suffix,
						HTML_PATH.'Home/channel/'.$enName.'_en.'.$suffix,
						HTML_PATH.'Home/index_cn.'.$suffix,
						HTML_PATH.'Home/index_en.'.$suffix,
						//Wap分组=============================
						HTML_PATH.'Wap/channel/'.$cnName.'_cn.'.$suffix,
						HTML_PATH.'Wap/channel/'.$enName.'_en.'.$suffix,
						HTML_PATH.'Wap/index_cn.'.$suffix,
						HTML_PATH.'Wap/index_en.'.$suffix,
				);
				foreach ($filelist as $f){
					if( is_file($f) ){
						@unlink($f);
					}
				}
				break;
		}
		return true;
}
	
	/**
	 * 删除所有缓存
	 */
	static function deleteAll(){
		$dir = RUNTIME_PATH;
		if(is_dir( $dir )){
			if( !yd_is_writable($dir) ) return false;
			@deldir( $dir );
			@mkdir($dir,0755,true); //创建目录
		}
		return true;
	}

	/**
	 * 写入所有缓存
	 */
	static function writeAll(){
		YdCache::deleteAll();
		YdCache::writeConfig();
		//YdCache::writeCoreConfig(); 不需要重新写入核心缓存，本来就是文件
        //更新缓存core.php文件
        $m = D('Admin/Language');
        $config = $m->getLanguageConfig();
        YdCache::writeCoreConfig($config);
		return true;
	}
}

/**
 * 安全输入过滤
 */
class YdInput{
	//检查是否是数字
	static function checkNum($str, $default=0){
		return ( is_numeric($str) ? $str : $default );
	}

	//检查是否是时间
    static function checkDatetime($strTime, $default=''){
        if(strtotime($strTime) > 0){
            return $strTime;
        }else{
            return $default;
        }
    }
	
	/**
	 * 检查是否是以逗号隔开的数字，如：18,30,23
	 * @param string/int $str
	 * @param string/int $default
	 */
	static function checkCommaNum($str, $default=''){
		if( is_numeric($str) ){
			return $str;
		}else{
			$list = explode(',', $str);
			foreach ($list as $v){
				$v = trim($v);
				if( !is_numeric($v) ) return $default;
			}
			return $str;
		}
	}
	
	/**
	 * 过滤逗号中的非数字字符串
	 * @param array/int $str
	 * @param unknown_type $default
	 */
	static function filterCommaNum($idlist=array()){
		if( is_array($idlist) ){
			foreach ($idlist as $k=>$v){
				$idlist[$k] = intval($v);  //强制转化为数字
			}
		}else{  //如果是标量
			$idlist = intval($idlist);
		}
		return $idlist;
	}
	
	//检查搜索关键词，$maxLength：表示字符的最大长度
	static function checkKeyword($str, $maxLength=0){
	    if($maxLength > 0){
	        if(strlen($str) > $maxLength){
	            return '';
            }
        }
		$str = strip_tags($str); //过滤所有HTML标记

        //危险字符==============================================
        $list = array('--', '0x', '/*', '*/', 'union', 'youdian_', 'sleep', 'delete', 'select', 'or', '||');
        foreach($list as $v){
            if(false !== stripos($str, $v)){
                return '';
            }
        }
        //======================================================

        //过滤非法字符
        $search =  array('%',  '_', '(', ')', '"', "'", ';', '&', '#', '`', "\\");
		$str = str_replace($search,  '', $str ); //防止注入sql
		$str = htmlspecialchars($str); //防止xss恶意攻击（注意PHP8之前是不过滤单引号的）
        $str = addslashes($str);
		return $str;
	}
	
	//用于在表单显示，可防止xss攻击
	static function checkTextbox($str) {
		if(is_array($str)){
            $result = array();
			foreach ($str as $k=>$v){
			    if(is_scalar($v)){
                    $result[ $k ] = preg_replace(array("/&amp;/i", "/&nbsp;/i"), array('&', '&amp;nbsp;'), htmlspecialchars($v, ENT_QUOTES));
                }
			}
		}else{
			$result = preg_replace(array("/&amp;/i", "/&nbsp;/i"), array('&', '&amp;nbsp;'), htmlspecialchars($str, ENT_QUOTES));
		}
		return $result;
	}
	
	//过滤seo非法字符，主要用于title、keywords、description
	static function checkSeoString($str){
		$str = strip_tags( trim($str) ); //必须去掉所有标记
		$search = array('"', "'", '&nbsp;');
		$replace=array(''  ,  '' ,  ' ');
		$str = str_replace($search, $replace, trim($str));
		$str = htmlspecialchars($str);
		return $str;
	}
	
	//检查注册字段合法性
	static function checkReg($str, $exp=array()){
        $search = array('"', "'", ';', '(', ')');
		if( is_array($str) ){
			foreach ($str as $k=>$v){
				if( !in_array($k, $exp) ){
					$str[$k] = htmlspecialchars( strip_tags(trim($v)) );
                    $str[$k] = str_ireplace($search, '', $str[$k]);
				}
			}
		}else{
			$str = htmlspecialchars( strip_tags(trim($str)) );
            $str = str_ireplace($search, '', $str);
		}
		return $str;
	}
	
	//用于过滤频道和信息的数据（将保留部分标签）
	static function checkInfo($str, $exp=array()){
		$allow = "<br><br/><div><span><b><strong><p><table><ul><li><ol><em><i><address><pre>";
		$allow .= "<h1><h2><h3><h4><h5><h6><cite><blockquote><sub><sup><dl><dt><dd><a><strike>";
		if( is_array($str) ){
			foreach ($str as $k=>$v){
				//不处理attr属性值，属性值是单独保存
				if( !in_array($k, $exp) && substr($k, 0, 5) != 'attr_' ){
					$str[$k] = strip_tags($v,$allow);
				}
			}
		}else{
			$str = strip_tags($str,$allow);
		}
		return $str;
	}

	//检查文件和目录中字符
    static function checkFileName($str){
        //检查文件名不能出现字符： ..
        //参数可能是：xx.html、./xx/1.php
        $str = str_replace('..', '--', $str); //必须为无效字符
        if(!preg_match("/^[\/a-zA-Z0-9_\.\-\\\]+$/i", $str)){
            $str = '';
        }
        return $str;
    }

    //检查表字段是否是合法：正常：如：a.InfoID,InfoTitle
    static function checkTableField($str){
	    if(empty($str) || is_bool($str)) return $str;
        //$str = str_ireplace(' ', '', $str); 不能删除空格，InfoTitle as title
        if(!preg_match("/^[ a-zA-Z0-9_,\.-]+$/i", $str)){
            return '';
        }

        //16进制编码：SELECT * from youdian_admin where AdminName=0x61646d696e; 等价于 SELECT * from youdian_admin where AdminName='admin';
        // 括号可以替代空格：select * from(youdian_admin)
        ///**/可以绕过空格：SELECT/**/AdminID/**/from(youdian_admin)
        //必须过滤注释字符，-- 和 # 都可以作为注释
        $list = array('--', '0x', 'youdian_');
        foreach($list as $v){
            if(false !== stripos($str, $v)){
                return '';
            }
        }
        return $str;
    }

    //主要用于检查模板
    static function checkTemplateContent(&$content){
	    $result = true;
        $b = yd_contain_php($content);
        if($b){
            $result = "不能包含PHP代码，保存失败！";
            return $result;
        }

        $pattern = '/{[$:]{1}([\s\S]+?)}/i'; //如：函数调用：{:home()}
        $matches = array();
	    $n = preg_match_all($pattern, $content, $matches);
	    if($n>0){
            $list = array(
                'base64_encode', 'base64_decode',
                'passthru','exec','chroot','chgrp','chown','shell_exec','proc_open','proc_get_status','popen','ini_alter','ini_restore'
                 ,'openlog','syslog','readlink','symlink','popepassthru','eval', 'system',
                'file_get_contents', 'file_put_contents' ,'fopen', 'rename', 'mkdir', 'fgets', 'fwrite','fputs', 'fread',
                'session_start', 'call_user_func', 'assert', 'preg_replace', 'create_function', 'phpinfo',
                '$_POST', '$_GET', '$_REQUEST', '$_COOKIE',
            );
            $keywords = implode(' ', $matches[1]);
            foreach($list as $v){
                if(false !== stripos($keywords, $v)){
                    $result = "存在非安全代码:{$v}，保存失败！";
                    return $result;
                }
            }
        }
        return $result;
    }

    //检查频道静态文件名
    static function checkHtmlName($str){
        if(empty($str)) return $str;
        if(!preg_match("/^[\.a-zA-Z0-9_-]+$/i", $str)){
            $str = '';
        }
        return $str;
    }

    //只能是数字、字母、下划线、中划线
    static function checkLetterNumber($str){
        if(empty($str)) return $str;
        if(!preg_match("/^[:\.a-z@A-Z0-9_-]+$/i", $str)){
            $str = '';
        }
        return $str;
    }

    //检查自定义排序字段（目前只有getInfo调用），如：a.InfoAttacheent DESC, a.InfoTitle DESC
    static function checkOrderField($orderby){
        $len = strlen($orderby);
        if($len>45) return '';
        $search = array(
            '(',  ')',  '"',  "'",  '%',  ';',  '*',  '0x',  '<', '>',  '+',  '{',  '}',  '==',  '=',   '-', '&', '#', "\\",
            'select', 'join',       'delete', 'like', 'drop', 'alter',
            'union', 'modify', 'sleep',   'root',
            'youdian_'
        );
        $orderby = str_ireplace($search, 'XYZ', $orderby);
        return $orderby;
    }
}

//浏览的历史记录
class YdHistory{
	private $cookieName = 'info_history';  //cookie名称
	private $cookiePath = '/'; //cookie存放路径
	private $cookieExpire = 2592000; //cookie过期时间，默认为30天
	private $maxnum = 128;   //最多保留的历史记录数
	private static $_instance;  //购物车使用单例模式实现
	private function __construct(){ }
	//静态方法，单例统一访问入口
	static public function getInstance() {
		if (is_null ( self::$_instance ) || isset ( self::$_instance )) {
			self::$_instance = new self ();
		}
		return self::$_instance;
	}
	
	//获取所有的历史数据二维数组
	public function getAllData($top=-1){
		$data = $this->get();
		if( empty($data) ) return false;
		$m = D('Admin/Info');
		$data = $m->getHistory($data, $top);
		return $data;
	}
	
	//获取历史记录数组
	private function get(){
		$str = cookie( $this->getCookieName() );
		if( empty($str) ) return false;
		$data = explode('-', $str);
		return $data;
	}
	
	private function getCookieName(){
		$name = $this->cookieName.get_language_mark();
		return $name;
	}
	
	//设置历史数据到cookie
	private function set($data){
		if( empty($data) ) return;
		$str = implode('-', $data); //不要使用逗号分号等这些是cookie保留字
		$p = array('expire'=>$this->cookieExpire, 'path'=>$this->cookiePath);
		cookie($this->getCookieName(), $str, $p);
	}
	
	//id: 信息ID
	public function push($id){
		if( !is_numeric($id) ) return;
		$data = $this->get();
		if( !empty($data) ){
			array_unshift($data, $id);
			$data = array_unique($data, SORT_NUMERIC); //去重
			while (count($data) > $this->maxnum ) {
				array_pop($data);
			}
		}else{
			$data = array($id);
		}
		$this->set($data);
	}
	
	//清空
	public function clear(){
		cookie($this->getCookieName(), null);
	}
}

//弹框信息
function alert($msg,$url){
	header('Content-type: text/html; charset=utf-8');
	$msg = str_replace("'","\\'",$msg);
	$str = '<script>';
	$str.="alert('".$msg."');";
	switch($url){
		case 1:
			$s = 'window.history.go(-1);';
			break;
		case 2:
			$s = 'window.history.go(-2);';
			break;
		case 3:
			$s = 'self.close();';
			break;
		default:
			$s = "location.href='{$url}';";
	}
	$str.=$s;
	$str.='</script>';
	exit($str);
}

//删除目录函数
function deldir($dirname){
	if(file_exists($dirname)){
		$dir = opendir($dirname);
		while( $filename = readdir($dir) ){
			if($filename != "." && $filename != ".."){
				$file = $dirname."/".$filename;
				if(is_dir($file)){
					deldir($file); //使用递归删除子目录
				}else{
					@unlink($file);
				}
			}
		}
		closedir($dir);
		rmdir($dirname);
	}
}

//清除所有缓存
function clear_all_cache(){
	if(is_dir(RUNTIME_PATH)){
		@deldir(RUNTIME_PATH);
	}
}

//获取文件夹大小（$fileCount：返回文件个数、$dirCount返回目录个数）
function getdirsize($dir, &$fileCount=0, &$dirCount=0){
	if( !is_dir($dir)) return 0;

	$dirlist = opendir($dir);
	$dirsize = 0;
	while (false !==  ($folderorfile = readdir($dirlist))){
		if($folderorfile != "." && $folderorfile != ".."){
		    $tempFile = "{$dir}/{$folderorfile}";
			if (is_dir($tempFile)){
				$dirsize += getdirsize($tempFile, $fileCount, $dirCount);
                $dirCount++;
			}elseif(is_file($tempFile)){
                $fileCount++;
				$dirsize += filesize($tempFile);
			}
		}
	}
	closedir($dirlist);
	return $dirsize;
}

//删除目录不安全文件
function deleteUnsafeFile($dir){
    if( !is_dir($dir)) return 0;
    $dir = trim($dir, '/');
    $dirlist = opendir($dir);
    $n = 0;
    $map = array('php'=>1, 'asp'=>1, 'php5'=>1);
    while (false !==  ($folderorfile = readdir($dirlist))){
        if($folderorfile != "." && $folderorfile != ".."){
            $tempFile = "{$dir}/{$folderorfile}";
            if (is_dir($tempFile)){
                deleteUnsafeFile($tempFile);
            }elseif(is_file($tempFile)){
                $ext = strtolower(yd_file_ext($tempFile));
                if(isset($map[$ext]) && $tempFile !== './Install/index.php'){
                    @unlink($tempFile);
                    WriteLog("自动删除文件 {$tempFile}");
                    $n++;
                }
            }
        }
    }
    closedir($dirlist);
    return $n;
}

//获取时间颜色:24小时内为红色
function getColorDate($type='Y-m-d H:i:s', $time=0, $color='red'){
	if((time()-$time)>86400){
		return date($type,$time);
	}else{
		return '<font color="'.$color.'">'.date($type,$time).'</font>';
	}
}

//获取模板类型名称
function getTplFileType($filename){
	$f = explode('.',$filename);
	$ext = strtolower( $f[1]);
	switch( $ext ){
		case 'js':
			return 'js脚本文件';
			break;
		case 'php':
			return 'php脚本文件';
			break;
		case 'css':
			return '层叠样式表';
			break;
		case 'jpg':
			return 'jpg图片';
			break;
		case 'gif':
			return 'gif图片';
			break;
		case 'png':
			return 'png图片';
			break;
		case 'zip':
			return 'zip压缩包';
			break;
		case 'rar':
			return 'rar压缩包';
			break;
		case 'html':
			return '模板文件';
			break;
		case 'htm':
			return '网页文件';
			break;
		case 'ico':
			return 'ico图标';
			break;
		case 'wmv':
			return 'wmv视频文件';
			break;
		case 'swf':
			return 'flash文件';
			break;
		case 'wma':
			return 'wma音频文件';
			break;
		case 'mp3':
			return 'mp3音频文件';
			break;
		case 'flv':
			return 'flv视频文件';
			break;
		case 'mp4':
			return 'mp4视频文件';
			break;
		case 'xml':
			return 'xml文件';
			break;			
		default:
			return '未知文件';
			break;
	}
}

//获取全局优化标题
function get_title($ChannelID){
	if( $ChannelID==0 || !is_numeric( $ChannelID ) ) return $GLOBALS['Config']['TITLE'];
	$m = D('Admin/Channel');
	while(true){
		//$data = $m->where("ChannelID=$ChannelID")->getField('Title,Parent');
		$data = $m->field('Title,Parent')->find($ChannelID);
		if( !empty($data['Title']) ) return $data['Title'];
		if( $data['Parent'] == 0 ) return $GLOBALS['Config']['TITLE'];
		$ChannelID = $data['Parent'];
	}
}

//获取全局优化关键词
function get_keywords($ChannelID){
	if( $ChannelID==0 || !is_numeric( $ChannelID ) ) return $GLOBALS['Config']['KEYWORDS'];
	$m = D('Admin/Channel');
	while(true){
		//$data = $m->where("ChannelID=$ChannelID")->getField('Keywords,Parent');
		$data = $m->field('Keywords,Parent')->find($ChannelID);
		if( !empty($data['Keywords']) ) return $data['Keywords'];
		if( $data['Parent'] == 0 ) return $GLOBALS['Config']['KEYWORDS'];
		$ChannelID = $data['Parent'];
	}
}

//获取全局优化描述
function get_description($ChannelID){
	if(  $ChannelID==0 || !is_numeric( $ChannelID ) ) return $GLOBALS['Config']['DESCRIPTION'];
	$m = D('Admin/Channel');
	while(true){
		//$data = $m->where("ChannelID=$ChannelID")->getField('Description,Parent');
		$data = $m->field('Description,Parent')->find($ChannelID);
		if( !empty($data['Description']) ) return $data['Description'];
		if( $data['Parent'] == 0 ) return $GLOBALS['Config']['DESCRIPTION'];
		$ChannelID = $data['Parent'];
	}
}

//获取网站安装目录
function get_web_install(){
	$installDir = $_SERVER['DOCUMENT_ROOT'].__ROOT__;
	return $installDir;
}

//自动获取当前网站地址（含安装目录）, 返回如：http://192.168.1.10/youdiancms4.0
//$hasProtocol 是否包http://含协议头
function get_web_url($hasProtocol = true, $hasPath=true){
	$url = $hasProtocol ? get_current_protocal() : '';
	$url .= $_SERVER['HTTP_HOST']; //$_SERVER['HTTP_HOST']返回带端口号，80端口为默认
	$url .= $hasPath ? __ROOT__ : '';
	return $url;
}

//获取绑定的手机网站域名
function get_wap_domain(){
    $domainRules = C('APP_SUB_DOMAIN_RULES');
    if(is_array($domainRules)){
        $rules = array_keys($domainRules);
        //默认第一个就是手机网站
        $domain = isset($rules[0]) ? $rules[0] : '';
    }else{
        $domain = '';
    }
	return $domain;
}

//返回微信网站当前绝对地址，返回如：http://192.168.1.10/youdiancms4.0/index.php
function get_wx_url(){
	/*
	$v = C('URL_MODEL');
	$url = get_web_url(true);
	if($v == 1){
		$url .= '/index.php';
	}
	$url .= '/wap';
	return $url;
	*/
	//$url = $protocol.$_SERVER['HTTP_HOST'].__GROUP__;
	
	//当把DefaultGroup设为Wap后，以上语句存在Bug，频道地址会链接到电脑网站首页
	//如果手机网站绑定了单独的，则单独使用
	
	//这个是判断当前手机网站是否存在
	$protocol = get_current_protocal(); //自动获取当前协议
	$HasWap = file_exists(TMPL_PATH.'Wap/'.C('WAP_DEFAULT_THEME').'/template.xml');
	if($HasWap){
		$url = get_wap_domain();
		if(!empty($url)){
			$url = $protocol.$url;
		}else{
			$url = $protocol.$_SERVER['HTTP_HOST'].__APP__.'/wap';
		}
	}else{
		//如果只有手机网站，则把手机网站放在Home目录下，这里直接返回电脑站的地址
		$url = $protocol.$_SERVER['HTTP_HOST'].__APP__;
	}
	return $url;
}

//判断当前用户是否有阅读当前信息的阅读权限
//返回false或true
//$readlevel：当前信息或频道的阅读权限
function has_read_level($readlevel){
	//如果是管理员，则拥有所有的阅读权限，阅读权限主要用于会员分组
	if( session('?AdminID') ){
		return true;   
	}
	if(empty($readlevel)) return true;
	$list = explode(',', $readlevel);
	$MemberGroupID = (int)session('MemberGroupID');
	if( in_array($MemberGroupID, $list)){
		return true;
	}
	return false;
}

//获取频道阅读权限
function get_read_level($ChannelID){
	$m = D('Admin/Channel');
	while(true){
		$data = $m->field('ReadLevel,Parent')->find($ChannelID);
		if( !empty($data['ReadLevel']) || $data['Parent'] == 0) return $data['ReadLevel'];
		$ChannelID = $data['Parent'];
	}
}

//获取网站根目录
function get_web_root(){
	return $_SERVER['DOCUMENT_ROOT'];
}

//缓存数组到文件, $keyUpper:是否将key转换为大写
function cache_array( $data, $fileName, $keyUpper = true){
	if( empty($data) ) {
		$content	=  "<?php\nreturn array();";
	}else{
		if($keyUpper){
			$content	=  "<?php\nreturn ".var_export(array_change_key_case($data, CASE_UPPER),true).";";
		}else{
			$content	=  "<?php\nreturn ".var_export($data, true).";";
		}
	}

	if(file_put_contents($fileName, $content)){
		return true;
	}else{
		return false;
	}
}

/**
 * 用于添加信息时验证频道是否能添加信息
 * 单页模型32和链接模型33不能添加信息
 * @param int $ChannelID
 */
function channel_allow($ChannelID){
    $ChannelID = intval($ChannelID);
	$where = "ChannelID={$ChannelID} and ChannelModelID!=32 and  ChannelModelID!=33 and   ChannelModelID!=37";
	$n = D('Admin/Channel')->where($where)->count();
	if($n > 0) {
		return true;
	}else{
		return false;
	}
}

/**
 * 语言查询条件(作为第一个条件最好)
 * @param string $alias 表别名
 */
function get_language_where($alias = false, $lngID=false){
	$str = (!empty($alias)) ? $alias.'.' : '';
	if( $lngID === false){
		$LanguageID = get_language_id();
	}else{
		$LanguageID = intval($lngID);
	}
	$where = ' '.$str."LanguageID = $LanguageID ";
	return $where;
}
function get_language_where_array($alias = false, $lngID=false){
	$str = (!empty($alias)) ? $alias.'.' : '';
	if( $lngID === false){
		$LanguageID = get_language_id();
	}else{
		$LanguageID = intval($lngID);
	}
	$where[$str.'LanguageID'] = $LanguageID;
	return $where;
}

/**
 * 获取当前语言
 */
function get_language_id($mark=false){
    if($mark){ //返回指定标识的语言ID
        $map = C('LANG_LIST');
        return $map[$mark]['LanguageID'];
    }else{ //返回当前语言ID
        return LANG_ID;
    }
}

/**
 * 获取当前语言名称
 */
function get_language_name($mark=false){
    if(empty($mark)){
        $mark = LANG_SET;
    }
    $map = C('LANG_LIST');
    return $map[$mark]['LanguageName'];
}

/**
 * 获取当前语言名称
 */
function LanguageName($LanguageID){
    $data = C('LANG_LIST');
    foreach($data as $v){
        if($LanguageID==$v['LanguageID']){
            return $v['LanguageName'];
        }
    }
    return '';
}

/**
 * 获取语言索引，主要用于get_model函数
 */
function get_language_index(){
    $index = 0;
    $list = C('LANG_LIST');
    $i = 0;
    foreach($list as $k=>$v){
        if(LANG_SET == $k){
            $index = $i;
            break;
        }
        $i++;
    }
    return $index;
}

/**
 * 获取当前语言绑定的域名
 */
function get_language_domain($mark=false){
    if(empty($mark)) $mark = get_language_mark();
    $map = C('LANG_LIST');
    return $map[$mark]['LanguageDomain'];
}

/**
 * 获取当前语言标识符
 */
function get_language_mark(){
	return LANG_SET;
}

function get_para(){
	$params = is_numeric($_REQUEST['p']) ? $_REQUEST['p'] : 1;
	if( is_numeric($_REQUEST['specialid']) ) $params .= '_'.$_REQUEST['specialid'];
	if( isset($_REQUEST['labelid']) ) $params .= '_'.$_REQUEST['labelid'];
	if( is_numeric($_REQUEST['minprice']) ) $params .= '_'.$_REQUEST['minprice'];
	if( is_numeric($_REQUEST['maxprice']) ) $params .= '_'.$_REQUEST['maxprice'];
	if( !empty($_REQUEST['keywords']) ) $params .= '_'.yd_pinyin($_REQUEST['keywords'], false);
	return $params;
}

function get_wx_para(){
	$v = '';
	if( isset($_GET['wx']) && $_GET['wx'] == 1){
		$v = '_wx';
	}
	return $v;
}

function sql_split($sql){
	$sql = str_replace("\r\n", "\n", $sql);
	$sql = str_replace("\r", "\n", $sql);
	$ret = array();
	$num = 0;
	$sqlList = explode(";\n", trim($sql));
	foreach ($sqlList as $mysql) {
		$ret[$num] = '';
		$queries = explode("\n", trim($mysql));
		foreach ($queries as $query) {//去注释
			$ret[$num] .= ( isset($query[0]) && $query[0] == '#') || (isset($query[1]) && isset($query[1]) && $query[0] . $query[1] == '--') ? '' : $query;
		}
		$num++;
	}
	unset($sql);
	return $ret;
}

/**
 * 批量删除文件
 * @param array $fileToDelete
 */
function batchDelFile($fileToDelete){
    $b = false;
	if( is_array($fileToDelete) ){
		foreach ($fileToDelete as $f){
            if('./Upload/' != substr($f, 0, 9) || false !== stripos($f, '..')) continue;
			$b = unlink($f);
		}
	}else{
        if('./Upload/' != substr($fileToDelete, 0, 9) || false !== stripos($fileToDelete, '..')) return false;
		$b = unlink($fileToDelete);
	}
	return $b;
}

//给图片添加水印
function addWater($imageFile, $saveFile=''){
	if ( !file_exists($imageFile) ) return;
	if( !yd_is_image( $imageFile) ) return ;
	$data = &$GLOBALS['Config'];
	$WaterEnable = $data['WATER_ENABLE'];
	if( $WaterEnable == 1){
		import('ORG.Util.Image.ThinkImage');
		$img = new ThinkImage(THINKIMAGE_GD, $imageFile);
		$position = $data['WATER_POSITION'];
		$saveFile = empty($saveFile) ? $imageFile : $saveFile;
		if( $data['WATER_TYPE'] == 2 ){//文字水印
			$text = $data['WATER_TEXT'];
			$font = './Public/font/'.$data['WATER_FONT'];
			if( !is_file($font)) return;  //水印字体不存在则直接返回
			$size = $data['WATER_TEXT_SIZE'];
			$color= $data['WATER_TEXT_COLOR'];
			$angle = $data['WATER_TEXT_ANGLE'];
			$offset = array($data['WATER_OFFSET_X'],$data['WATER_OFFSET_Y']);
			$img->text($text, $font, $size, $color, $position, $offset, $angle)->save($saveFile);
		}else if( $data['WATER_TYPE'] == 1 ){ //图片水印
			/*
			$pic = $_SERVER['DOCUMENT_ROOT'].$data['WATER_PIC'];
			if ( !file_exists($pic) ){
				return;
			}
			$right = $data['WATER_RIGHT'];
			$bottom = $data['WATER_BOTTOM'];
			$trans = $data['WATER_TRANS'];
			import("ORG.Util.Image");
			Image::water($imageFile, $pic, null, $trans, $right, $bottom);
			*/
			$pic = $_SERVER['DOCUMENT_ROOT'].$data['WATER_PIC'];
			if ( !file_exists($pic) ) return;
			$img->water($pic, $position)->save($saveFile);
		}
	}
}


/**
 * 生成缩略图
 * @param string $imageFile
 * 返回./Upload/开头的路径，如果传入的是：D:\www\1.jpg，并且没有THUMB_ENABLE=0
 * 则返回的路径存在bug，因此要求在函数外判断THUMB_ENABLE
 */
function makeThumb($imageFile){
	if( !file_exists($imageFile) ) return false;
	if( !yd_is_image( $imageFile) ) return false;
	$data = &$GLOBALS['Config'];
	if( $data['THUMB_ENABLE'] == 1 ){
		$w = $data['THUMB_WIDTH'];   //缩略图宽度
		$h = $data['THUMB_HEIGHT'];  //缩略图高度
		$type = $data['THUMB_TYPE'];   //缩略图类型
        $uploadDir = GetUploadDir();
		$filename = "{$uploadDir}thumb".basename($imageFile);
		import('ORG.Util.Image.ThinkImage');
		$img = new ThinkImage(THINKIMAGE_GD, $imageFile);
		$img->thumb($w, $h, $type)->save($filename);
	}else{
		$filename = $imageFile;
	}
	if( $data['THUMB_WATER_ENABLE'] == 1 ){ //是否添加水印
		addWater($filename);
	}
	return $filename;
}

/**
 * 记录操作日志
 * 保存系统日志:type 1：其它操作、2：保存添加、3：删除、4：保存修改、5：排序、6：导出、
 * 7：清除缓存、8：登录/退出登录、9：查看、10：添加、11：修改
 * @param string $description
 * @param array options LogType、UserAction
 */
function WriteLog($description='', $options=array() ){
    //强制记录所有日志，而且不能在后台直接清除
	//if( $GLOBALS['Config']['LOG_STATUS'] == 0 || $GLOBALS['Config']['LOGTYPE_ALLOW'] == '') return;
	//防止数据量过大，不保存任何会员日志【已经实现会员日志保存】
	if( GROUP_NAME == 'Member') return;
	if( isset($options['LogType']) ){
		$LogType = $options['LogType'];
		$action = $options['UserAction'];
	}else{
		$m = D('Admin/MenuOperation');
		$data = $m->getLog(ACTION_NAME, MODULE_NAME, GROUP_NAME);
		if( empty($data) ) return;
		$LogType = $data['LogType'];
		if( empty($data['MenuName']) ){
			$action = $data['MenuOperationName'];
		}else{
			$action = $data['MenuName'].'->'.$data['MenuOperationName'];
		}
	}
	
	//$allowlist = (array)explode(',', $GLOBALS['Config']['LOGTYPE_ALLOW']);
	//if( in_array($LogType, $allowlist) ){
		$m1 = D('Admin/Log');
		$data['UserAction'] = $action;
		$data['LogType'] = $LogType;
		if( GROUP_NAME=='Admin' ){
			$data['UserName'] = session("AdminName").' [ID:'.session('AdminID').']';
		}else{
			$data['UserName'] = session('MemberName').' [ID:'.session('MemberID').' 会员]';
		}
		$data['UserIP'] = get_client_ip();
		$data['LogTime'] = date('Y-m-d H:i:s');
		//最好不要添加strip_tags，否则在看日志时无法看到html
		$data['LogDescription'] =  htmlspecialchars($description); //防止错误信息注入
		$data['LanguageID'] = get_language_id();
        try{
            $m1->add($data);
        }catch(Exception $e){

        }
	//}
}

/**
 * 系统异常信息
 * @param string $errmsg
 * @param string $UserAction
 */
function WriteErrLog($errmsg='', $UserAction='系统异常'){
	$options['LogType'] = 1;
	$options['UserAction'] = $UserAction;
	if(is_array($errmsg)){
		$errmsg = var_export($errmsg, true);
	}
	WriteLog($errmsg, $options);
}

/**
 * 管理组是否拥有指定频道的操作权限
 * @param int $channelid
 * @param int $groupid 管理组ID，若为-1，不检测权限，直接返回true
 */
function HasChannelPurview($channelid, $groupid=-1){
	if( $groupid == -1 || $groupid == 1 ) return true;
	$m = D('Admin/AdminGroup');
	$list = $m->getChannelPurview( $groupid );
	$list = explode(',', $list);
	if( in_array($channelid, $list) ){
		return true;
	}else{
		return false;
	}
}

/**
 * 生成网站地图
 * @param string $type
 * @return bool 生成成功返回true，否则返回false
 */
function makeSitemap($type='all'){
	@set_time_limit(300);
	@ini_set('memory_limit', -1);
	$LanguageID = -1;
	$supportMultiLanguage = C('LANG_AUTO_DETECT');
	if( $supportMultiLanguage == 0){ //启用单语言
		$LanguageID = (C('DEFAULT_LANG')== 'cn') ? 1 : 2;
	}
	
	//获取数据
	$m1 = D('Admin/Channel');
	$ChannelData = $m1->getAllChannel($LanguageID);
	
	$m2 = D('Admin/Info');
	$InfoData = $m2->getAllInfo($LanguageID);

    $b = false;
	if($type == 'xml'){
		$b = _xmlsitemap($ChannelData, $InfoData);
	}else if($type=='txt'){
		$b = _txtsitemap($ChannelData, $InfoData);
	}else if($type=='html'){
		$b = _htmlsitemap($ChannelData, $InfoData);
	}else if($type=='all'){ //生成所有地图
		$b = _xmlsitemap($ChannelData, $InfoData);
		$b = _txtsitemap($ChannelData, $InfoData);
		$b = _htmlsitemap($ChannelData, $InfoData);
	}
	return $b;
}

/**
 * 获取主站和所有分站的地址
 * */
function _getWebUrl(){
    $WebUrl[] = get_web_url(true, false);
    $SiteEnable = $GLOBALS['Config']['SiteEnable'];
    if($SiteEnable=="1") {//若开启了“分站管理”功能
        $site = get_site();
        foreach ($site as $v){
            $WebUrl[] = get_current_protocal().$v['SiteDomain'];
        }
    }
    return $WebUrl;
}

function _xmlsitemap($ChannelData, $InfoData){
    $WebUrl = _getWebUrl();
	$language = array(1=>'cn', 2=>'en');

	$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n";
	$xml .= "<urlset>\r\n";
	foreach ($WebUrl as $t['WEB_URL']){
        foreach ($ChannelData as $k=>$v){
            if(33 == $v['ChannelModelID']  && trim($v['LinkUrl']) != ''){ //转向链接
                // 不生成转向链接  $loc = $v['LinkUrl'];
            }else{
                $loc = $t['WEB_URL'].ChannelUrl( $v['ChannelID'], $v['Html'], '', $language[$v['LanguageID']]);
                $lastmod = date('Y-m-d');
                $changefreq = 'always';
                $priority = ($v['ChannelID'] == 1 || $v['ChannelID'] == 2) ? '1.0' : '0.8';
                $xml .= "<url>\r\n<loc>$loc</loc>\r\n<lastmod>$lastmod</lastmod>\r\n<changefreq>$changefreq</changefreq>\r\n<priority>$priority</priority>\r\n</url>";
            }
        }

        foreach ($InfoData as $k=>$v){
            if( $v['LinkUrl'] == '' ){
                $loc = $t['WEB_URL'].InfoUrl( $v['InfoID'], $v['Html'], $v['LinkUrl'],  $language[$v['LanguageID']], $v['ChannelID']);
                $lastmod = $v['InfoTime'];
                $changefreq = 'weekly';
                $priority = '0.6';
                $xml .= "<url>\r\n<loc>$loc</loc>\r\n<lastmod>$lastmod</lastmod>\r\n<changefreq>$changefreq</changefreq>\r\n<priority>$priority</priority>\r\n</url>";
            }else{
                //  不生成转向链接  $loc = $v['LinkUrl'];
            }
        }
    }
	$xml .= '</urlset>';
	if( @file_put_contents(APP_DATA_PATH.'map/sitemap.xml', $xml)  ){
		return true;
	}else{
		return false;
	}
}

function _txtsitemap($ChannelData, $InfoData){
    $WebUrl = _getWebUrl();
	$language = array(1=>'cn', 2=>'en');

	$txt = '';
    foreach ($WebUrl as $t['WEB_URL']) {
        foreach ($ChannelData as $k => $v) {
            if (33 == $v['ChannelModelID'] && trim($v['LinkUrl']) != '') { //转向链接
                // 不生成转向链接 $loc = $v['LinkUrl'];
            } else {
                $loc = $t['WEB_URL'] . ChannelUrl($v['ChannelID'], $v['Html'], '', $language[$v['LanguageID']]);
                $txt .= $loc . "\r\n";
            }
        }

        foreach ($InfoData as $k => $v) {
            if ($v['LinkUrl'] == '') {
                $loc = $t['WEB_URL'] . InfoUrl($v['InfoID'], $v['Html'], $v['LinkUrl'], $language[$v['LanguageID']], $v['ChannelID']);
                $txt .= $loc . "\r\n";
            } else {
                //  不生成转向链接  $loc = $v['LinkUrl'];
            }
        }
    }
	if( @file_put_contents(APP_DATA_PATH.'map/sitemap.txt', $txt)  ){
		return true;
	}else{
		return false;
	}
}

function _htmlsitemap($ChannelData, $InfoData){
    $WebUrl = _getWebUrl();
	$language = array(1=>'cn', 2=>'en');

	$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>html网站地图</title>
	</head>
	<body id="main_page">';
    foreach ($WebUrl as $t['WEB_URL']) {
        foreach ($ChannelData as $k => $v) {
            if (33 == $v['ChannelModelID'] && trim($v['LinkUrl']) != '') { //转向链接
                //$loc = $v['LinkUrl'];
            } else {
                $loc = $t['WEB_URL'] . ChannelUrl($v['ChannelID'], $v['Html'], '', $language[$v['LanguageID']]);
                $lastmod = date('Y-m-d');
                $title = $v['ChannelName'];
                $html .= "<li><a href='$loc' title='$title' target='_blank'>$title</a><span>$lastmod</span></li>\r\n";
            }
        }

        foreach ($InfoData as $k => $v) {
            if ($v['LinkUrl'] == '') {
                $loc = $t['WEB_URL'] . InfoUrl($v['InfoID'], $v['Html'], $v['LinkUrl'], $language[$v['LanguageID']], $v['ChannelID']);
                $lastmod = $v['InfoTime'];
                $title = $v['InfoTitle'];
                $html .= "<li><a href='$loc' title='$title' target='_blank'>$title</a><span>$lastmod</span></li>\r\n";
            } else {  //转向链接不生成地图
                //$loc = $v['LinkUrl'];
            }
        }
    }
	$html .= '</body>\r\n</html>';

	if( @file_put_contents(APP_DATA_PATH.'map/sitemap.html', $html)  ){
		return true;
	}else{
		return false;
	}
}


/**
 * 给指定的目录生成安全文件index.html
 * @param unknown_type $dirs 要生成安全文件的目录
 * @param unknown_type $content 安全文件的内容
 */
function make_secure_file($dirs=array(), $content=false){
	if( empty($dirs) || !is_array($dirs)) return;
	$filename = 'index.html'; //安全文件名称
	if( $content === false){
		$content = "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body>";
		$content .= "<p>Directory access is forbidden.</p></body></html>";
	}
	foreach ($dirs as $dir){
		$fullname = rtrim($dir, '/').'/'.$filename;
		if( !file_exists($fullname)){
			file_put_contents($fullname, $content);
		}
	}
}

//通过sql语句获取当前语句的表前缀
function get_table_prefix($sql){
	if( empty($sql) ) return false;
	$prefix = false;
	//必须是+?，增加一个?表示非贪婪匹配
	$pattern = array(
			'/DROP\s+TABLE\s+IF\s+EXISTS\s+`?([a-zA-Z0-9_\n]+?_)[a-zA-Z0-9_\n]+`?/i',
			'/CREATE\s+TABLE\s+`?([a-zA-Z0-9_\n]+?_)[a-zA-Z0-9_\n]+`?/i',
			'/INSERT\s+INTO\s+`?([a-zA-Z0-9_\n]+?_)[a-zA-Z0-9_\n]+`?/i',
			'/ALTER\s+TABLE\s+`?([a-zA-Z0-9_\n]+?_)[a-zA-Z0-9_\n]+`?/i',
	);
	foreach ($pattern as $p){
		if( preg_match($p, $sql, $matches) ){
			$prefix = $matches[1];
			break;
		}
	}
	return $prefix;
}

//替换一次
function str_replace_once($needle, $replace, $haystack) {
	$pos = strpos($haystack, $needle);
	if ($pos === false) {
		return $haystack;
	}
	return substr_replace($haystack, $replace, $pos, strlen($needle));
}

//发送短信
function send_sms($mobile, $content, $placeholder = false, $saveLog=1, &$error=''){
	import("@.Common.YdSms");
	$obj = YdSms::getInstance( $GLOBALS['Config']['SMS_TYPE'] );
	$obj->setConfig( $GLOBALS['Config'] );
	$obj->needSave($saveLog);
	$obj->setPlaceholder($placeholder);
	$b = $obj->sendNotifyMessage($mobile, $content);
	$error = $obj->getMessage();
	return $b;
}

//增加，$op：1:增加，2：修改
function save_info_type_attribute($InfoID, $op=1){
	if( !is_numeric($InfoID) ) return false;
	$m = D('Admin/TypeAttributeValue');
	if($op == 1){  //增加
		$data = array();
		$n = is_array($_POST['attr_id_list']) ? count($_POST['attr_id_list']) : 0; //属性id列表
		for($i = 0; $i<$n; $i++){
			if( $_POST['attr_value_list'][$i] !== ''){
				$data[] = array(
						'TypeAttributeID'=>$_POST['attr_id_list'][$i],
						'InfoID'=>$InfoID,
						'AttributeValue'=>$_POST['attr_value_list'][$i],
						'AttributePicture'=>$_POST['attr_picture_list'][$i], //前台暂未实现
						'AttributePrice'=>(double)( $_POST['attr_price_list'][$i] ),
				);
			}
		}
		$result = !empty($data) ? $m->addAll( $data ) : false; //批量插入
		return $result;
	}else if($op==2){ //批量更新
		$n = is_array($_POST['attr_value_id_list']) ? count($_POST['attr_value_id_list']) : 0; //属性值id列表
		$theseid = array();
		for($i = 0; $i<$n; $i++){
			$data = array();
			$AttributeValueID = intval($_POST['attr_value_id_list'][$i]);
			$data['TypeAttributeID'] = $_POST['attr_id_list'][$i];
			$data['AttributeValue'] = $_POST['attr_value_list'][$i];
			$data['AttributePicture'] = $_POST['attr_picture_list'][$i];
			$data['AttributePrice'] = (double)( $_POST['attr_price_list'][$i] );
			if( !empty($_POST['attr_value_id_list'][$i]) ){ //存在就：Update
				$result = $m->where("InfoID={$InfoID} and AttributeValueID={$AttributeValueID}")->save($data);
				$theseid[] = $AttributeValueID;
			}else{ //如果不存在：Insert
				$data['InfoID'] = intval($InfoID);
				$result = $m->add($data);  //运行成功，返回主键ID
				if( $result ) $theseid[] = $result;
			}
		}
		//需要删除，没有使用的attributevalueid
		if(!empty($theseid) ){
			$where = "InfoID={$InfoID} and AttributeValueID not in (".implode(',', $theseid).')';
			$count = $m->where($where)->delete();
		}
		return true;
	}
}

/**
 * 生成筛选条件
 * @param int $channelid
 * @param int $specialid
 * @param int $minprice
 * @param int $maxprice
 * @param string $attr
 * @param string $extra_attr 附加的attr属性
 * @param int $orderby 排序方式
 */
function SearchQuery($specialid=-1, $minprice=-1, $maxprice=-1, $attr='', $extra_attr='', $orderby=''){
	$sign = '_';
	$query = '';
	if( isset($specialid) && $specialid > 0 ) $query .= "&specialid=$specialid";
	if( isset($minprice) && $minprice >= 0) $query .= "&minprice=$minprice";
	if( isset($maxprice) && $maxprice >= 0) $query .= "&maxprice=$maxprice";
	if( isset($attr) && $attr != '' ) {
		if( $extra_attr != ''){
			$attr = explode($sign, $attr.$sign.$extra_attr);
			$attr = implode($sign, array_unique($attr));
			$attr = rtrim($attr, $sign);
		}
		$query .= "&attr=$attr";
	}
	if( isset($orderby) && $orderby > 0 ) $query .= "&orderby=$orderby";
	$str = (!empty($query)) ? '?'.ltrim($query,'&') : '';
	return $str;
}

/**
 * 获取指定起止时间
 * @param int $spanType 1:本年、2：本季、3：本月、4：本周、5：本日
 * @param string $startTime 输出参数 开始时间
 * @param string $endTime 输出参数 结束时间
 */
function getTimeSpan($spanType, &$startTime, &$endTime){
	//第几个月: date('n')    本周周几:  date("w")    本月天数:  date("t")
	switch ($spanType){
		case 1: //本年
			$startTime = date("Y-m-d H:i:s",mktime(0, 0, 0, 1, 1, date("Y")));
			$endTime = date("Y-m-d H:i:s",mktime(23,59,59, 12, 31, date("Y")));
			break;
		case 2: //本季
			$season = ceil((date('n'))/3);//当月是第几季度
			$startTime = date('Y-m-d H:i:s', mktime(0, 0, 0,$season*3-3+1,1,date('Y')));
			$endTime = date('Y-m-d H:i:s', mktime(23,59,59,$season*3,date('t',mktime(0, 0 , 0,$season*3,1,date("Y"))),date('Y')));
			break;
		case 3: //本月
			$startTime = date("Y-m-d H:i:s",mktime(0, 0 , 0, date("m"), 1, date("Y")));
			$endTime = date("Y-m-d H:i:s",mktime(23,59,59, date("m"), date("t"), date("Y")));
			break;
		case 4: //本周
			$startTime = date("Y-m-d H:i:s",mktime(0, 0 , 0, date("m"), date("d")-date("w")+1, date("Y")));
			$endTime = date("Y-m-d H:i:s",mktime(23,59,59, date("m"), date("d")-date("w")+7, date("Y")));
			break;
		case 5: //本日
			$startTime = date("Y-m-d H:i:s",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			$endTime = date("Y-m-d H:i:s",mktime(23,59,59, date("m"), date("d"), date("Y")));
			break;
	}
}

/**
 * 友好时间格式化
 */
function FriendDate(&$data, $field="", $removeHtmlTag=false){
    if(empty($data) || empty($field)) return;
    if(false === strpos($field, ',')){ //仅单个字段
        if(!isset($data[0][$field])) return;
        foreach ($data as $k=>$v){
            $time = yd_friend_date(strtotime($v[$field]));
            if($removeHtmlTag) $time = strip_tags($time);
            $data[$k][$field] = $time;
        }
    }else{ //包含多个字段
        $list = explode(',', $field);
        foreach ($data as $k=>$v){
            foreach ($list as $f){
                if(isset($data[$k][$f])){
                    $time = yd_friend_date(strtotime($v[$f]));
                    if($removeHtmlTag) $time = strip_tags($time);
                    $data[$k][$f] = $time;
                }
            }
        }
    }
}

/**
 * 获取Api列表
 */
function get_api_list(){
	$api['AddFavorite'] = array('ApiID'=>1, 'ApiName'=>'加入收藏', 'ApiFunction'=>'AddFavorite', 'ApiDescription'=>'收藏喜欢的文章');
	$api['GetFavorite'] =  array('ApiID'=>2, 'ApiName'=>'获取收藏数据', 'ApiFunction'=>'GetFavorite','ApiDescription'=>'');
	$api['AddHistory'] =  array('ApiID'=>3, 'ApiName'=>'记录阅读历史',  'ApiFunction'=>'AddHistory','ApiDescription'=>'记录用户浏览文章的历史');
	$api['GetHistory'] =   array('ApiID'=>4, 'ApiName'=>'获取阅读历史数据',  'ApiFunction'=>'GetHistory','ApiDescription'=>'');
	$api['AddAppFeedback'] = array('ApiID'=>5, 'ApiName'=>'提交用户反馈', 'ApiFunction'=>'AddAppFeedback','ApiDescription'=>'');
	
	$api['Login'] = array('ApiID'=>6, 'ApiName'=>'登录', 'ApiFunction'=>'Login','ApiDescription'=>'');
	$api['AutoLogin'] = array('ApiID'=>7, 'ApiName'=>'自动登录', 'ApiFunction'=>'AutoLogin',
			'ApiDescription'=>'启动App后，会员将自动登录！');
	$api['Reg'] = array('ApiID'=>8, 'ApiName'=>'注册', 'ApiFunction'=>'Reg','ApiDescription'=>'');
	$api['Forget'] = array('ApiID'=>9, 'ApiName'=>'找回密码', 'ApiFunction'=>'Forget','ApiDescription'=>'');
	$api['ModifyMemberInfo'] = array('ApiID'=>10, 'ApiName'=>'修改会员资料', 'ApiFunction'=>'ModifyMemberInfo','ApiDescription'=>'');
	$api['ModifyPassword'] = array('ApiID'=>11, 'ApiName'=>'修改密码', 'ApiFunction'=>'ModifyPassword','ApiDescription'=>'');
	
	$api['UploadDevice'] = array('ApiID'=>12, 'ApiName'=>'上传设备数据', 'ApiFunction'=>'UploadDevice',
			'ApiDescription'=>'如：上传设备UUID唯一标识、操作系统、品牌等设备数据，主要用于安装统计！');
	$api['UploadFile'] = array('ApiID'=>13, 'ApiName'=>'上传文件', 'ApiFunction'=>'UploadFile', 'ApiDescription'=>'');
	
	$api['WxLogin'] = array('ApiID'=>14, 'ApiName'=>'微信登录', 'ApiFunction'=>'WxLogin', 'ApiDescription'=>'');
	$api['AddCart'] = array('ApiID'=>15, 'ApiName'=>'商品加入购物车', 'ApiFunction'=>'AddCart', 'ApiDescription'=>'');
	$api['DeleteCart'] = array('ApiID'=>16, 'ApiName'=>'删除购物车商品', 'ApiFunction'=>'DeleteCart', 'ApiDescription'=>'');
	$api['ClearCart'] = array('ApiID'=>17, 'ApiName'=>'清空购物车', 'ApiFunction'=>'ClearCart', 'ApiDescription'=>'');
	$api['SetQuantity'] = array('ApiID'=>18, 'ApiName'=>'设置购物车商品数量', 'ApiFunction'=>'SetQuantity', 'ApiDescription'=>'');
	$api['IncQuantity'] = array('ApiID'=>19, 'ApiName'=>'购物车商品数量加1', 'ApiFunction'=>'IncQuantity', 'ApiDescription'=>'');
	$api['DecQuantity'] = array('ApiID'=>20, 'ApiName'=>'购物车商品数量减1', 'ApiFunction'=>'DecQuantity', 'ApiDescription'=>'');
	$api['UseCouponCode'] = array('ApiID'=>21, 'ApiName'=>'使用线下优惠券', 'ApiFunction'=>'UseCouponCode', 'ApiDescription'=>'');
	$api['UsePoint'] = array('ApiID'=>22, 'ApiName'=>'使用积分', 'ApiFunction'=>'UsePoint', 'ApiDescription'=>'');
	$api['SaveOrder'] = array('ApiID'=>23, 'ApiName'=>'保存订单', 'ApiFunction'=>'SaveOrder', 'ApiDescription'=>'');
	$api['RequestPayment'] = array('ApiID'=>24, 'ApiName'=>'小程序微信支付', 'ApiFunction'=>'RequestPayment', 'ApiDescription'=>'');
	
	$api['DeleteOrder'] = array('ApiID'=>25, 'ApiName'=>'会员删除订单', 'ApiFunction'=>'DeleteOrder', 'ApiDescription'=>'');
	$api['CancelOrder'] = array('ApiID'=>26, 'ApiName'=>'会员取消订单', 'ApiFunction'=>'CancelOrder', 'ApiDescription'=>'');
	$api['ConfirmReceipt'] = array('ApiID'=>27, 'ApiName'=>'会员确认收货', 'ApiFunction'=>'ConfirmReceipt', 'ApiDescription'=>'');
	
	$api['AddConsignee'] = array('ApiID'=>27, 'ApiName'=>'添加收货地址', 'ApiFunction'=>'AddConsignee', 'ApiDescription'=>'');
	$api['DeleteConsignee'] = array('ApiID'=>28, 'ApiName'=>'删除收货地址', 'ApiFunction'=>'DeleteConsignee', 'ApiDescription'=>'');
	$api['SaveConsignee'] = array('ApiID'=>29, 'ApiName'=>'保存收货地址', 'ApiFunction'=>'SaveConsignee', 'ApiDescription'=>'');
	$api['SetDefaultConsignee'] = array('ApiID'=>30, 'ApiName'=>'设置默认收货地址', 'ApiFunction'=>'SetDefaultConsignee', 'ApiDescription'=>'');
	
	$api['GetDownline'] =   array('ApiID'=>31, 'ApiName'=>'获取我的下线',  'ApiFunction'=>'GetDownline','ApiDescription'=>'');
	$api['GetIncome'] =   array('ApiID'=>32, 'ApiName'=>'获取我的收益',  'ApiFunction'=>'GetIncome','ApiDescription'=>'');
	
	$api['GetCash'] =   array('ApiID'=>33, 'ApiName'=>'获取我的资金',  'ApiFunction'=>'GetCash','ApiDescription'=>'');
	$api['DeleteCash'] =   array('ApiID'=>34, 'ApiName'=>'删除我的资金',  'ApiFunction'=>'DeleteCash','ApiDescription'=>'');
	$api['GetBank'] =   array('ApiID'=>35, 'ApiName'=>'获取用户提现银行',  'ApiFunction'=>'GetBank','ApiDescription'=>'');
	$api['AddWithdraw'] =   array('ApiID'=>36, 'ApiName'=>'添加用户提现申请',  'ApiFunction'=>'AddWithdraw','ApiDescription'=>'');
	$api['SetCashPassword'] =   array('ApiID'=>37, 'ApiName'=>'设置提现密码',  'ApiFunction'=>'SetCashPassword','ApiDescription'=>'');
	$api['Recharge'] =   array('ApiID'=>38, 'ApiName'=>'充值',  'ApiFunction'=>'Recharge','ApiDescription'=>'');
	$api['GetCouponSend'] =   array('ApiID'=>39, 'ApiName'=>'获取我的优惠券',  'ApiFunction'=>'GetCouponSend','ApiDescription'=>'');
	$api['DeleteCouponSend'] =   array('ApiID'=>40, 'ApiName'=>'删除我的优惠券',  'ApiFunction'=>'DeleteCouponSend','ApiDescription'=>'');

    //多端小程序
    $api['GetXcxConfig'] =   array('ApiID'=>41, 'ApiName'=>'获取小程序配置',  'ApiFunction'=>'GetXcxConfig','ApiDescription'=>'');
    $api['getDictionaryData'] =   array('ApiID'=>42, 'ApiName'=>'获取字典配置',  'ApiFunction'=>'getDictionaryData','ApiDescription'=>'');
    $api['GetGuestbookModel'] =   array('ApiID'=>43, 'ApiName'=>'获取留言模型配置',  'ApiFunction'=>'GetGuestbookModel','ApiDescription'=>'');
    $api['GetFeedbackModel'] =   array('ApiID'=>44, 'ApiName'=>'获取反馈模型配置',  'ApiFunction'=>'GetFeedbackModel','ApiDescription'=>'');
    $api['getPageData'] =   array('ApiID'=>45, 'ApiName'=>'获取小程序页面数据',  'ApiFunction'=>'getPageData','ApiDescription'=>'');
    $api['AddGuestbook'] =   array('ApiID'=>46, 'ApiName'=>'提交留言',  'ApiFunction'=>'AddGuestbook','ApiDescription'=>'');
    $api['AddFeedback'] =   array('ApiID'=>47, 'ApiName'=>'提交反馈',  'ApiFunction'=>'AddFeedback','ApiDescription'=>'');
	return $api;
}

/**
 * 导出时，需要过滤csv中的保留字
 * 如：逗号，改变回车换行
 * @param unknown_type $content
 */
function filter_csv_content($content){
	//csv字段必须用双引号才会换行，如：$t = '"'."aa\n\rbb".'"';
	if($content){
		$search =  array(',',    "'" ,  '"',     '&nbsp;',   '<br>',        '<br/>',    '<br />');
		$replace = array('，',  "’", "“",  '  ',            PHP_EOL, PHP_EOL,  PHP_EOL);
		$content = '"'.str_ireplace($search, $replace, $content).'"';
	}
	return $content;
}
L(array (
  '_MODULE_NOT_EXIST_' => '无法加载模块',
  '_ERROR_ACTION_' => '非法操作',
  '_LANGUAGE_NOT_LOAD_' => '无法加载语言包',
  '_TEMPLATE_NOT_EXIST_' => '模板不存在',
  '_MODULE_' => '模块',
  '_ACTION_' => '操作',
  '_ACTION_NOT_EXIST_' => '控制器不存在或者没有定义',
  '_MODEL_NOT_EXIST_' => '模型不存在或者没有定义',
  '_VALID_ACCESS_' => '没有权限',
  '_XML_TAG_ERROR_' => 'XML标签语法错误',
  '_DATA_TYPE_INVALID_' => '非法数据对象！',
  '_OPERATION_WRONG_' => '操作出现错误',
  '_NOT_LOAD_DB_' => '无法加载数据库',
  '_NOT_SUPPORT_DB_' => '系统暂时不支持数据库',
  '_NO_DB_CONFIG_' => '没有定义数据库配置',
  '_NOT_SUPPERT_' => '系统不支持',
  '_CACHE_TYPE_INVALID_' => '无法加载缓存类型',
  '_FILE_NOT_WRITEABLE_' => '目录（文件）不可写',
  '_METHOD_NOT_EXIST_' => '您所请求的方法不存在！',
  '_CLASS_NOT_EXIST_' => '实例化一个不存在的类！',
  '_CLASS_CONFLICT_' => '类名冲突',
  '_TEMPLATE_ERROR_' => '模板引擎错误',
  '_CACHE_WRITE_ERROR_' => '缓存文件写入失败！',
  '_TAGLIB_NOT_EXIST_' => '标签库未定义',
  '_OPERATION_FAIL_' => '操作失败！',
  '_OPERATION_SUCCESS_' => '操作成功！',
  '_SELECT_NOT_EXIST_' => '记录不存在！',
  '_EXPRESS_ERROR_' => '表达式错误',
  '_TOKEN_ERROR_' => '表单令牌错误',
  '_RECORD_HAS_UPDATE_' => '记录已经更新',
  '_NOT_ALLOW_PHP_' => '模板禁用PHP代码',
));C(array (
  'app_status' => 'debug',
  'app_file_case' => false,
  'app_autoload_path' => '@ORG.Util,@ORG.Io',
  'app_tags_on' => true,
  'app_sub_domain_deploy' => 1,
  'app_sub_domain_rules' => NULL,
  'app_sub_domain_deny' => 
  array (
  ),
  'app_group_list' => 'Home,Admin,Member,Wap',
  'cookie_expire' => 3600,
  'cookie_domain' => '',
  'cookie_path' => '/',
  'cookie_prefix' => 'youdian',
  'cookie_secure' => false,
  'cookie_httponly' => '1',
  'default_app' => '@',
  'default_lang' => 'cn',
  'default_theme' => '',
  'default_group' => 'Home',
  'default_module' => 'Index',
  'default_action' => 'index',
  'default_charset' => 'utf-8',
  'default_timezone' => 'PRC',
  'default_ajax_return' => 'JSON',
  'default_filter' => 'htmlspecialchars',
  'db_type' => 'mysqli',
  'db_host' => 'localhost',
  'db_name' => '',
  'db_user' => 'root',
  'db_pwd' => '',
  'db_port' => '',
  'db_prefix' => 'think_',
  'db_fieldtype_check' => false,
  'db_fields_cache' => true,
  'db_charset' => 'utf8',
  'db_deploy_type' => 0,
  'db_rw_separate' => false,
  'db_master_num' => 1,
  'db_sql_build_cache' => false,
  'db_sql_build_queue' => 'file',
  'db_sql_build_length' => 20,
  'data_cache_time' => 0,
  'data_cache_compress' => false,
  'data_cache_check' => false,
  'data_cache_type' => 'File',
  'data_cache_path' => './Data/runtime/Temp/',
  'data_cache_subdir' => false,
  'data_path_level' => 1,
  'error_message' => '您浏览的页面暂时发生了错误！请稍后再试～',
  'error_page' => '',
  'show_error_msg' => false,
  'log_record' => false,
  'log_type' => 3,
  'log_dest' => '',
  'log_extra' => '',
  'log_level' => 'EMERG,ALERT,CRIT,ERR',
  'log_file_size' => 2097152,
  'log_exception_record' => false,
  'session_auto_start' => true,
  'session_options' => 
  array (
  ),
  'session_type' => '',
  'session_prefix' => '',
  'var_session_id' => 'session_id',
  'tmpl_content_type' => 'text/html',
  'tmpl_action_error' => './App/Core/Tpl/dispatch_jump.tpl',
  'tmpl_action_success' => './App/Core/Tpl/dispatch_jump.tpl',
  'tmpl_exception_file' => './App/Core/Tpl/think_exception.tpl',
  'tmpl_detect_theme' => true,
  'tmpl_template_suffix' => '.html',
  'tmpl_file_depr' => '/',
  'url_case_insensitive' => true,
  'url_model' => '1',
  'url_pathinfo_depr' => '/',
  'url_pathinfo_fetch' => 'ORIG_PATH_INFO,REDIRECT_PATH_INFO,REDIRECT_URL',
  'url_html_suffix' => 'html',
  'var_group' => 'g',
  'var_module' => 'm',
  'var_action' => 'a',
  'var_ajax_submit' => 'ajax',
  'var_pathinfo' => 's',
  'var_url_params' => '_URL_',
  'var_template' => 't',
  'load_ext_config' => 'db,copy',
  'load_ext_file' => 'tag,extend',
  'tmpl_close' => 'close_1.html',
  'tmpl_404' => '404_1.html',
  'show_page_trace' => false,
  'show_run_time' => false,
  'show_adv_time' => false,
  'show_db_times' => false,
  'show_cache_times' => false,
  'show_use_mem' => false,
  'show_load_file' => false,
  'tmpl_strip_space' => false,
  'taglib_build_in' => 'youDian,cx',
  'tmpl_cache_on' => true,
  'tmpl_cache_time' => '0',
  'url_router_on' => true,
  'url_route_rules' => 
  array (
    'admin$' => 'admin/public/login',
    '/^wap[\\/]?(en|cn)?$/i' => 'wap/index/index?l=:1',
    '/^wap\\/(en\\/|cn\\/)?order(\\d+)$/i' => 'wap/channel/index?id=order&infoid=:2&l=:1',
    '/^wap\\/(en\\/|cn\\/)?resume(\\d+)$/i' => 'wap/channel/index?id=resume&jobid=:2&l=:1',
    '/^wap\\/(en\\/|cn\\/)?([\\w-]+)$/i' => 'wap/channel/index?id=:2&l=:1',
    '/^wap\\/(en\\/|cn\\/)?(?!channel|app|public|api)[\\w-]+\\/([\\w-]+)$/i' => 'wap/info/read?id=:2&l=:1',
    '/^(en|cn)$/i' => 'index/index?l=:1',
    '/^(en\\/|cn\\/)?order(\\d+)$/i' => 'channel/index?id=order&infoid=:2&l=:1',
    '/^(en\\/|cn\\/)?resume(\\d+)$/i' => 'channel/index?id=resume&jobid=:2&l=:1',
    '/^(en\\/|cn\\/)?([\\w-]+)$/i' => 'channel/index?id=:2&l=:1',
    '/^(en\\/|cn\\/)?(?!channel|app|public|api)[\\w-]+\\/([\\w-]+)$/i' => 'info/read?id=:2&l=:1',
  ),
  'wx_url_append' => 'wxref=mp.weixin.qq.com',
  'xuacompatible' => '',
  'ucurl' => 'https://u.youdiansoft.cn',
  'cmsversion' => '9.5.21',
  'cmsreleasedate' => '2024-12-12',
  'extends' => 
  array (
    'app_init' => 
    array (
    ),
    'app_begin' => 
    array (
      0 => 'CheckLang',
      1 => 'BadIP',
      2 => 'StartWeb',
      3 => 'ReadHtmlCache',
    ),
    'route_check' => 
    array (
      0 => 'CheckRoute',
    ),
    'app_end' => 
    array (
    ),
    'path_info' => 
    array (
    ),
    'action_begin' => 
    array (
    ),
    'action_end' => 
    array (
    ),
    'view_begin' => 
    array (
    ),
    'view_template' => 
    array (
      0 => 'LocationTemplate',
    ),
    'view_parse' => 
    array (
      0 => 'ParseTemplate',
    ),
    'view_filter' => 
    array (
      0 => 'ContentReplace',
      1 => 'TokenBuild',
      2 => 'WriteHtmlCache',
      3 => 'ShowRuntime',
      4 => 'BadWords',
    ),
    'view_end' => 
    array (
      0 => 'ShowPageTrace',
    ),
    'info_content' => 
    array (
      0 => 'AutoLink',
    ),
    'channel_content' => 
    array (
      0 => 'AutoLink',
    ),
    'baseaction_init' => 
    array (
    ),
  ),
  'admin_login_name' => '',
  'lang_auto_detect' => 0,
  'home_default_theme' => 'Default',
  'wap_default_theme' => 'Default',
  'admin_default_theme' => 'Default',
  'member_default_theme' => 'Default',
  'lang_list' => 
  array (
    'cn' => 
    array (
      'LanguageID' => '1',
      'LanguageName' => '中文',
      'LanguageMark' => 'cn',
      'LanguageDomain' => '',
    ),
  ),
  'html_cache_on' => false,
  'html_cache_rules' => 
  array (
    'index:index' => 
    array (
      0 => '{:group}/index_{0|get_language_mark}',
      1 => '0',
    ),
    'channel:index' => 
    array (
      0 => '{:group}/channel/{id}{jobid}{infoid}_{0|get_language_mark}_{0|get_para}',
      1 => '0',
    ),
    'info:read' => 
    array (
      0 => '{:group}/info/{id}_{0|get_para}',
      1 => '0',
    ),
  ),
));G('loadTime');Think::Start();