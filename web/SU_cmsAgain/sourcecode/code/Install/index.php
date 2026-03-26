<?php 
@set_time_limit(300);
error_reporting(E_ALL & ~E_NOTICE);

if(!defined('APP_NAME')){
    header('HTTP/1.1 403 Forbidden');
    header('Status: 403 Forbidden');
    exit();
}
define('ROOT', dirname(__FILE__));
define('HomeUrl', substr($_SERVER['PHP_SELF'], 0, stripos($_SERVER['PHP_SELF'], '/install')) );

$installPath = INSTALL_PATH; //安装目录
//常量定义
$HomeUrl = substr($_SERVER['PHP_SELF'], 0, stripos($_SERVER['PHP_SELF'], '/install'));
$Css = "{$installPath}Tpl/Public/css";
$Images = "{$installPath}Tpl/Public/images";
$InstallLock = APP_DATA_PATH.'install.lock';
$DbDataSql = ""; //数据库文件
$ErrMsg = "";
$runtimeDir = APP_DATA_PATH."runtime";
$dbFile = APP_PATH."Conf/db.php";
$copyFile = APP_PATH."Conf/copy.php";
$oemFile = APP_PATH."Conf/oem.php";
$Step = isset($_GET['InstallStep']) ? (int)$_GET['InstallStep'] : 0;  //当前安装步骤

//检查是否已经安装=========================================
if($Step != 4){  //第四步已经安装完成
	if (file_exists($InstallLock)){
		$ErrMsg = "系统已成功安装！<br/>如果您要重新安装，请手动删除Data/install.lock文件！";
		include_once "{$installPath}Tpl/error.html";
		exit;
	}
}
//====================================================

if(!file_exists($copyFile) ){
	$ErrMsg = "缺少必要的安装文件!";
	include_once "{$installPath}Tpl/error.html";
	exit;
}

if( file_exists( $dbFile) && false == dir_writeable( $dbFile ) ){
	$ErrMsg = "数据库配置文件 {$dbFile} 没有写入权限!";
	include_once "{$installPath}Tpl/error.html";
	exit;
}

if( file_exists($oemFile) ){
    $IsOem = true;
    $CMSName = '';
    $CompanyFullName = '我们的';
    $CompanyUrl = '';
    $Logo = "{$Images}/logo1.png";
}else{
    $IsOem = false;
    $cmsinfo = include_once($copyFile);
    $CMSName = $cmsinfo['CMSName'];
    $CompanyFullName = $cmsinfo['CompanyFullName'];
    $CompanyUrl = $cmsinfo['CompanyUrl'];
    $Logo = "{$Images}/logo.png";
}

//获取数据库文件
foreach (glob(APP_DATA_PATH."db*.sql") as $filename) {
	$DbDataSql = $filename;
	break;
}
if(!file_exists($DbDataSql) ){
	$ErrMsg = "数据库文件不存在!";
	include_once "{$installPath}Tpl/error.html";
	exit;
}

switch($Step){
	case 1: //第一步：检查系统配置
		//name项目, r系统所需配置, b最佳配置, current当前配置
		$cp_items = array(
				array('name' => '操作系统', 'list' => 'os', 'c' => 'PHP_OS', 'r' => '不限', 'b' => 'Linux'),
				array('name' => 'PHP', 'list' => 'php', 'c' => 'PHP_VERSION', 'r' => '5.3+', 'b' => '7.4'),
				array('name' => 'GD库', 'list' => 'gdversion', 'r' => '2.0', 'b' => '2.0'),
				array('name' => '磁盘空间', 'list' => 'disk', 'r' => '30M', 'b' => '不限'),
		);
		 
		$dir_items = array(
				array('type' => 'dir', 'path' => APP_DATA_PATH),
				array('type' => 'dir', 'path' => APP_PATH.'Conf/'),
				array('type' => 'dir', 'path' => './Upload/' ),
		);
		 
		$func_items = array(
				array('name' => 'mysqli_connect'),
				array('name' => 'mb_strlen'),
				array('name' => 'iconv'),  //必须支持字符编码转换函数，汉字转拼音时会用到
				//array('name' => 'fsockopen'),iconv
				//array('name' => 'xml_parser_create'),
				array('name' => 'simplexml_load_file'),
		);
		$SystemInfo = syscheck($cp_items);
		$DirInfo = dircheck($dir_items);
		$FunctionInfo=function_check($func_items);
		include_once ("{$installPath}Tpl/step1.html");
		ob_flush();
		flush();
		
		foreach($DirInfo as $d){
			if($d['status'] != 1){
				echo '<script type="text/javascript">DisableNext();</script>';
				ob_flush();
				flush();
				break;
			}
		}
		
		//函数有效性检查
		foreach($FunctionInfo as $s){
			if($s['status'] != true){
				echo '<script type="text/javascript">DisableNext();</script>';
				ob_flush();
				flush();
				break;
			}
		}
		
		exit ();
	case 2: //第二步：录入数据库配置参数
		include_once ("{$installPath}Tpl/step2.html");
		ob_flush();
		flush();
		exit ();
	case 3: //第三步
		include_once (APP_PATH."Lib/Common/YdDbMysql.class.php");
		$dbHost = filter_params($_POST['dbhost']);
		$dbName = filter_params($_POST['dbname']);
		$dbUser = filter_params($_POST['dbuser']);
		$dbPwd = filter_params($_POST['dbpw']);
		$dbPort = filter_params($_POST['dbport']);
		$dbPrefix = filter_params($_POST['dbprefix']);

		if( $dbHost == "" ){
			alert('数据库服务器不能为空！', 'index.php?InstallStep=2');
		}
		if( $dbName == "" ){
			alert('数据库名不能为空', 'index.php?InstallStep=2');
		}
		if( $dbUser == "" ){
			alert('数据库用户名不能为空', 'index.php?InstallStep=2');
		}
		if( $dbPrefix == "" ){
			alert('数据库表前缀不能为空', 'index.php?InstallStep=2');
		}
		 
		$AdminName = filter_params($_POST['AdminName']);
		$pwd1 = filter_params($_POST['pwd1']);
		$pwd2 = filter_params($_POST['pwd2']);
		if( $AdminName == "" ){
			alert('管理员用户名不能为空' ,  'index.php?InstallStep=2');
		}
		if( $pwd1 == "" ){
			alert('管理密码不能为空' , 'index.php?InstallStep=2');
		}
		if( $pwd2 == "" ){
			alert('重复密码不能为空' ,  'index.php?InstallStep=2');
		}
		if( $pwd1 != $pwd2 ){
			alert('两次输入的密码不相同，请重新输入' ,  'index.php?InstallStep=2');
		}
        if(!preg_match("/^(?![^a-zA-Z]+$)(?!\D+$).{8,}$/", $pwd1)){
            alert('密码必须包含字母和数字，并且长度不低于8位！' ,  'index.php?InstallStep=2');
        }
		$pwd1 = yd_password_hash($pwd1);
		 
		//检查数据库＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝
		$link = @mysqli_connect($dbHost, $dbUser, $dbPwd);
		if (!$link) {
			alert("无法连接数据库！请检查数据库用户名或者密码是否正确！".mysqli_connect_error(), 'index.php?InstallStep=2');
		}
		
		if(!@mysqli_select_db($link, $dbName)){  //如果不存在则创建数据库
			if( !@mysqli_query($link, "CREATE DATABASE `$dbName` DEFAULT CHARACTER SET utf8mb4") ){
				alert("创建数据库失败！".mysqli_error($link), 'index.php?InstallStep=2');
			}
		}
		
		if (mysqli_errno($link)) {
			alert("无法创建新的数据库或无法连接现有数据库！\n请检查用户权限或数据库名称填写是否正确", 'index.php?InstallStep=2');
		}
		mysqli_close($link);
		//=====================================================================
		include_once ("{$installPath}Tpl/step3.html");
		ob_flush();
		flush();
		 
		showmessage("开始安装数据库...");
		
		$db = new YdDbMysql;
		$db->connect($dbHost, $dbUser, $dbPwd, $dbName, 'utf8');
		
		//创建表结构和初始化系统数据
		$dbSql = file_get_contents($DbDataSql);
		$sqlList = sql_split( $dbSql );
		$oldDbPrefix = get_table_prefix( $sqlList[0] );
		foreach ($sqlList as $query) {
			$query = str_replace_once($oldDbPrefix, $dbPrefix, $query); //替换表前缀
			if ($query) {
				$b = @$db->query($query); //DROP TABLE 不提示
				if (preg_match('/CREATE\s*TABLE\s* `([a-zA-Z0-9_\n]+)`/', $query, $matches)) {
					showmessage($matches[1]."表创建", $b);
				} else if (preg_match('/INSERT\s*INTO\s* `([a-zA-Z0-9_\n]+)`/', $query, $matches)) {
					showmessage("初始化".$matches[1]."表数据", $b);
				}
			}
		}
		showmessage("安装数据完成！");
		
		//写入管理员数据======================================================================
		$sql = "Update {$dbPrefix}admin Set AdminName='$AdminName', AdminPassword='$pwd1' Where AdminID=1";
		$b = @$db->query($sql);
		$sql = "Update {$dbPrefix}member Set MemberName='$AdminName', MemberPassword='$pwd1' Where MemberID=1";
		$b = @$db->query($sql);
		$sql = "UPDATE {$dbPrefix}config SET ConfigValue='1' WHERE ConfigID IN(337,338)";
        $b = @$db->query($sql);
		showmessage("创建管理员", $b);
		//==================================================================================
		
		//将数据库文件写入配置
		$dbInfo = array (
				'DB_TYPE' => 'mysqli',
				'DB_HOST' => "$dbHost",
				'DB_NAME' => "$dbName",
				'DB_USER' => "$dbUser",
				'DB_PWD' => "$dbPwd",
				'DB_PORT' => "$dbPort",
				'DB_PREFIX' => "$dbPrefix",
		);
		$b = cache_array($dbInfo, $dbFile);
		showmessage("写入数据库配置文件", $b);
		echo '<script type="text/javascript">Finish();</script>';
		ob_flush();//修改部分
		flush();
		showmessage("数据库安装完成！");

		//创建安装锁定文件
		if (!file_exists( $InstallLock ) ) {
			@touch( $InstallLock );
		}
		
		//清除系统缓存
		if(is_dir( $runtimeDir )){
			@deldir( $runtimeDir );
		}
		exit ();
	case 4: //第四步
		include_once ("{$installPath}Tpl/step4.html");
		ob_flush();
		flush();
		exit ();
	default:  //安装协议
        $LicenseContent = file_exists('./license.txt') ? file_get_contents('./license.txt') : '';
        $LicenseContent = nl2br($LicenseContent);
		include_once ("{$installPath}Tpl/index.html");
		ob_flush();
		flush();
		exit();
}


//系统函数=======================================================
//过滤参数
function filter_params($str){
    $str = trim($str);
    //get_magic_quotes_gpc函数已自 PHP 7.4.0 起弃用，自 PHP 8.0.0 起移除。强烈建议不要依赖本函数。
    //在PHP7.4安装时会提示，Deprecated：function get_magic_quotes_gpc is deprecated。所以这里加上@
    //PHP7.4：function_exists('get_magic_quotes_gpc')会返回true
    if(function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc()){
        return $str;
    }
    $str = addslashes($str);
    return $str;
}

//系统环境检查
function syscheck($items) {
	foreach ($items as $key => $item) {
		if ($item['list'] == 'php') { //PHP版本， current:当前版本
			$items[$key]['current'] = PHP_VERSION; //PHP_VERSION 存储当前PHP的版本号，也可以通过PHPVERSION()函数获取。
		} elseif ($item['list'] == 'upload') {  //文件上传参数
			$items[$key]['current'] = @ini_get('file_uploads') ? ini_get('upload_max_filesize') : 'unknow';
		} elseif ($item['list'] == 'gdversion') { //gd库版本
			$tmp = function_exists('gd_info') ? gd_info() : array();  //gd_info():返回一个关联数组描述了安装的 GD 库的版本和性能。
			$items[$key]['current'] = empty($tmp['GD Version']) ? 'noext' : $tmp['GD Version'];
			unset($tmp); //释放数组
		} elseif ($item['list'] == 'disk') { //可用磁盘空间
			if (function_exists('disk_free_space')) {
				//disk_free_space -- 返回目录中的可用空间(不能用于远程文件)
				$items[$key]['current'] = floor(disk_free_space(ROOT) / (1024 * 1024)) . 'M';
			} else {
				$items[$key]['current'] = 'unknow';
			}
		} elseif (isset($item['c'])) {
			$items[$key]['current'] = constant($item['c']);
		}
		$items[$key]['status'] = 1;
		if ($item['r'] != 'notset' && strcmp($items[$key]['current'], $item['r']) < 0) {
			$items[$key]['status'] = 0;
		}
	}
	return $items;
}

function dircheck($diritems) {
	foreach ($diritems as $key => $item) {
		$item_path = $item['path'];
		if (!dir_writeable($item_path)) {
			$diritems[$key]['status'] = 0;
			$diritems[$key]['current'] = 0;
		} else {
			$diritems[$key]['status'] = 1;
			$diritems[$key]['current'] = 1;
		}
	}
	return $diritems;
}

function filemode($file, $checktype='w') {
	if (!file_exists($file)) {
		return false;
	}
	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
		$testfile = $file . 'writetest.txt';
		if (is_dir($file)) {
			$dir = @opendir($file);
			if ($dir === false) {
				return false;
			}

			if ($checktype == 'r') {
				$mode = (@readdir($dir) != false) ? true : false;
				@closedir($dir);
				return $mode;
			}

			if ($checktype == 'w') {
				$fp = @fopen($testfile, 'wb');
				if ($fp != false) {
					$wp = @fwrite($fp, 'demo');
					$mode = ($wp != false) ? true : false;
					@fclose($fp);
					@unlink($testfile);
					return $mode;
				} else {
					return false;
				}
			}
		} elseif (is_file($file)) {
			if ($checktype == 'r') {
				$fp = @fopen($file, 'rb');
				@fclose($fp);
				$mode = ($fp != false) ? true : false;
				return $mode;
			}

			if ($checktype == 'w') {
				$fp = @fopen($file, 'ab+');
				if ($fp != false) {
					$wp = @fwrite($fp, '');
					$mode = ($wp != false) ? true : false;
					@fclose($fp);
					return $mode;
				} else {
					return false;
				}
			}
		}
	} else {
		if ($checktype == 'r') {
			$fp = @is_readable($file);
			$mode = ($fp) ? true : false;
			return $mode;
		}
		if ($checktype == 'w') {
			$fp = @is_writable($file);
			$mode = ($fp) ? true : false;
			return $mode;
		}
	}
}

function dir_writeable($file){
	if (DIRECTORY_SEPARATOR === '/' && ( version_compare(PHP_VERSION, '5.4', '>=') || ! ini_get('safe_mode'))){
		return is_writable($file);
	}
	if (is_dir($file)){
		$file = rtrim($file, '/').'/'.md5(mt_rand());
		if (($fp = @fopen($file, 'ab')) === FALSE){
			return FALSE;
		}
		fclose($fp);
		//@chmod($file, 0777);  //加上chmod偶尔会造成unlink提示 permission denied
		@unlink($file);
		return TRUE;
	}elseif ( ! is_file($file) || ($fp = @fopen($file, 'ab')) === FALSE){
		return FALSE;
	}
	fclose($fp);
	return TRUE;
}

function function_check($funcitems) {
    $funcitemslist = array();
	foreach ($funcitems as $key => $item) {
		$funcitemslist[$key]['name'] = $item['name'];
		$funcitemslist[$key]['status'] = function_exists($item['name']);
	}
	return $funcitemslist;
}

function showmessage($msg, $tip='notip'){
	if( $tip !== 'notip'){
		$msg .= $tip ? '成功' : '失败';
	}
	echo '<script type="text/javascript">showmessage(\'' . addslashes($msg) . ' \');</script>' . "\r\n";
	ob_flush(); //修改部分
	flush();
}

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

//通过sql语句获取当前语句的表前缀
function get_table_prefix($sql){
	if( empty($sql) ) return false;
	$prefix = false;
	$pattern = array(
			'/DROP\s+TABLE\s+IF\s+EXISTS\s+`([a-zA-Z0-9_\n]+_)[a-zA-Z0-9_\n]+`/i',
			'/CREATE\s+TABLE\s+`([a-zA-Z0-9_\n]+_)[a-zA-Z0-9_\n]+`/i',
			'/INSERT\s+INTO\s+`([a-zA-Z0-9_\n]+_)[a-zA-Z0-9_\n]+`/i',
	);
	foreach ($pattern as $p){
		if( preg_match($p, $sql, $matches) ){
			$prefix = $matches[1];
			break;
		}
	}
	return $prefix;
}

function str_replace_once($needle, $replace, $haystack) {
	$pos = strpos($haystack, $needle);
	if ($pos === false) {
		return $haystack;
	}
	return substr_replace($haystack, $replace, $pos, strlen($needle));
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

//缓存数组到文件, $keyUpper:是否将key转换为大写
function cache_array( $data, $fileName, $keyUpper = true){
	if( empty($data) ) {
		$content	=  "<?php\nreturn array();\n?>";
	}else{
		if($keyUpper){
			$content	=  "<?php\nreturn ".var_export(array_change_key_case($data, CASE_UPPER),true).";\n?>";
		}else{
			$content	=  "<?php\nreturn ".var_export($data, true).";\n?>";
		}
	}

	if(file_put_contents($fileName, $content)){
		return true;
	}else{
		return false;
	}
}

//删除目录函数及其所有子目录和文件
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

/**
 *  友点密码加密
 * @param $pwd 原始密码
 */
function yd_password_hash($pwd){
    if(defined('CRYPT_BLOWFISH' ) && CRYPT_BLOWFISH ==1){
        $randStr = md5(uniqid(rand(), true));
        $randStr = substr($randStr, 0, 22);
        $salt = '$2y$11$'.$randStr;  //29位盐
        $hash = crypt($pwd, $salt);
    }else{
        $hash = md5($pwd);
    }
    return $hash;
}
//==============================================================
?>