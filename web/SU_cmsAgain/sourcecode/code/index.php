<?php
define('APP_NAME', 'App');                       //应用程序名

define('THINK_PATH', './App/Core/');        //框架路径
define('APP_PATH', './App/');                     //应用程序路径
define('APP_PUBLIC_PATH', './Public/');    //应用程序公共路径

define('APP_DATA_PATH', './Data/');     //数据路径
define('HTML_PATH', APP_DATA_PATH.'html/');            //数据路径
define('RUNTIME_PATH', APP_DATA_PATH.'runtime/');  //系统缓存目录
define('INSTALL_PATH', './Install/');

//通过GET参数来开临时启调试模式
if( isset($_GET['debug']) ){
	define('APP_DEBUG', ($_GET['debug']==1) ? true : false);
}else{
	define('APP_DEBUG', file_exists(APP_DATA_PATH.'app.debug') );
}
require(THINK_PATH.'/ThinkPHP.php');    //加载核心框架