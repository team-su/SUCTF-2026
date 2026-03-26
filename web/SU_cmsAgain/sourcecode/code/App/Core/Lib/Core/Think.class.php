<?php
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
        //[RUNTIME]
        Think::buildApp();         // 预编译项目
        //[/RUNTIME]
        // 运行应用
        App::run();
        return ;
    }

    //[RUNTIME]
    /**
     +----------------------------------------------------------
     * 读取配置信息 编译项目
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    static private function buildApp() {
        // 加载底层惯例配置文件
        C(include THINK_PATH.'Conf/convention.php');
        // 加载项目配置文件
        C(include CONF_PATH.'config.php');
        // 加载框架底层语言包
        L(include THINK_PATH.'Lang/'.strtolower(C('DEFAULT_LANG')).'.php');
        //加载版本文件
        if(file_exists(CONF_PATH.'version.php')){
            C(include CONF_PATH.'version.php');
        }
        
        // 加载系统行为扩展定义
        if(C('APP_TAGS_ON')){
            C('extends', include THINK_PATH.'Conf/tags.php');
        }

        // 加载项目配置目录的tags文件定义
        //if(is_file(CONF_PATH.'tags.php')){
        //    C('tags', include CONF_PATH.'tags.php');
        //}

        $compile   = '';
        // 读取核心编译文件列表
        $list  =  array(
                THINK_PATH.'Common/functions.php', // 标准模式函数库
                CORE_PATH.'Core/Log.class.php',    // 日志处理类
                CORE_PATH.'Core/Dispatcher.class.php', // URL调度类
                CORE_PATH.'Core/App.class.php',   // 应用程序类
                CORE_PATH.'Core/Action.class.php', // 控制器类
                CORE_PATH.'Core/View.class.php',  // 视图类
        );
       
        foreach ($list as $file){
            if(is_file($file))  {
                require_cache($file);
                if(!APP_DEBUG)   $compile .= compile($file);
            }
        }

        // 加载项目公共文件
        if(is_file(COMMON_PATH.'common.php')) {
            include COMMON_PATH.'common.php';
            // 编译文件
            if(!APP_DEBUG)  $compile   .= compile(COMMON_PATH.'common.php');
        }
        
        //项目核心配置
        if(is_file(APP_DATA_PATH.'core.php')) {
        	C(include APP_DATA_PATH.'core.php');
        }else{
        	YdCache::writeCoreConfig();
        	C(include APP_DATA_PATH.'core.php');
        }

        if(APP_DEBUG) {
            // 调试模式加载系统默认的配置文件
            C(include THINK_PATH.'Conf/debug.php');
            // 读取调试模式的应用状态
            //$status  =  C('APP_STATUS');
            // 加载对应的项目配置文件
            //if(is_file(CONF_PATH.$status.'.php')){
            //    C(include CONF_PATH.$status.'.php'); // 允许项目增加开发模式配置定义
           // }
        }else{
            // 部署模式下面生成编译文件
            build_runtime_cache($compile);
        }
        return ;
    }
    //[/RUNTIME]

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