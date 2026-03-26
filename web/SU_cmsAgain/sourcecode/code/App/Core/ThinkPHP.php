<?php
// ThinkPHP 入口文件
if (!defined('APP_NAME')) exit();
$_SERVER['PHP_SELF'] = htmlentities($_SERVER['PHP_SELF']);
yd_install();
//记录开始运行时间
$GLOBALS['_beginTime'] = microtime(TRUE);
// 记录内存初始使用
define('MEMORY_LIMIT_ON',function_exists('memory_get_usage'));
if(MEMORY_LIMIT_ON) $GLOBALS['_startUseMems'] = memory_get_usage();
defined('APP_PATH') or define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']).'/');
defined('RUNTIME_PATH') or define('RUNTIME_PATH',APP_PATH.'Runtime/');
defined('APP_DEBUG') or define('APP_DEBUG',false);  //是否调试模式
//~runtime在某些linux的web服务器为保留字，有时无法删除，必须改名
$runtime = 'yunxingshi.php';
defined('RUNTIME_FILE') or define('RUNTIME_FILE',RUNTIME_PATH.$runtime);
if(yd_is_php8()){
    define('MAGIC_QUOTES_GPC', false);
    if(!function_exists("get_magic_quotes_gpc")){
        function get_magic_quotes_gpc(){
            return false;
        }
    }
    if (!function_exists("get_magic_quotes_runtime")) {
        function get_magic_quotes_runtime(){
            return false;
        }
    }
    if ( !function_exists("set_magic_quotes_runtime")) {
        function set_magic_quotes_runtime($quotes){
            return false;
        }
    }
}
//系统信息
if(version_compare(PHP_VERSION,'5.4.0','<') ) {
    @set_magic_quotes_runtime (0);
    define('MAGIC_QUOTES_GPC',get_magic_quotes_gpc()?True:False);
}
if(!APP_DEBUG && is_file(RUNTIME_FILE)) {
    // 部署模式直接载入运行缓存
    require RUNTIME_FILE;
}else{
    // 系统目录定义
    defined('THINK_PATH') or define('THINK_PATH', dirname(__FILE__).'/');
    // 加载运行时文件
    require THINK_PATH.'Common/runtime.php';
}
function yd_is_php8(){
    return version_compare(PHP_VERSION, '8.0.0', '>=');
}

function yd_install(){
    if (!file_exists(APP_DATA_PATH.'install.lock') || isset($_GET['InstallStep'])){
        header("Content-type: text/html;charset=utf-8");
        $installFile = INSTALL_PATH.'index.php';
        if(file_exists($installFile)){
            include_once $installFile;
        }else{
            echo "安装文件不存在，请上传安装文件。如果已经安装，请新建Data/install.lock文件";
        }
        exit();
    }
}