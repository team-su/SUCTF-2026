<?php 
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
if (!defined('APP_NAME')) exit();
class YdUpgrade{
	//次版本号只能是一位
    private $_lastError = ''; //上次错误
	private $_upgradeUrl = 'http://upgrade.youdiancms.com/';
	private $_cachePath = null;  //升级包临时解压位置
    private $_sqlPath = ''; //升级sql路径
    private $_debug = false; //升级调试模式主要用于调试升级，会下载升级包，但是不会覆盖本地文件
    private $_version = 0; //服务器最新版本
	
	function __construct(){
        $this->_cachePath = RUNTIME_PATH.'upgrade';
        $this->_sqlPath = APP_DATA_PATH . 'upgradesql/';
        $this->_debug =  file_exists("D:/local.lock") ? true : false;
	}

    /**
     * 检测是否可以升级
     * @param $versioin 升级前的版本号
     * 返回数组（最新升级信息）表示成功
     */
    function canUpgrade(){
        $b = file_exists(APP_DATA_PATH.'noupgrade.lock');
        if($b){
            return '系统已经锁定，不能在线升级！';
        }
        $api = create_api_Instance();
        $data = $api->getLatestVersion();
        if( empty($data) ) {
            $error = $api->getLastError();
            return "无法连接版本服务器！{$error}";
        }
        $this->_version = $data['version'];
        $version = C('CMSVersion');
        //默认情况下，在第一个版本低于第二个时，version_compare() 返回 -1；如果两者相等，返回 0；第二个版本更低时则返回 1。
        if(!empty($version) && version_compare($data['version'], $version) <= 0) {
            return '已经是最新版本';
        }
        //删除之前的目录，不放在downloadFile检测=================================
        //先删除，再创建
        @deldir($this->_cachePath);
        @mkdir($this->_cachePath);
        //删除之前的升级脚本，防止冲突
        if(is_dir($this->_sqlPath)){
            @deldir($this->_sqlPath);
        }
        //===========================================================
        return $data;
    }

    /**
     * 获取升级包最新版本
     */
    function getLatestVersion(){
        return $this->_version;
    }

    //获取最后错误数据
    function getLastError(){
        return $this->_lastError;
    }

	//更新数据库
	private function _executeSqlFile($sql){
		if( !file_exists($sql) ) return true;
		$content = @file_get_contents($sql);
		//升级脚本表前缀用[@DbPrefix]替代
		$dbPrefix = C('DB_PREFIX');
        $content = str_ireplace('[@DbPrefix]', $dbPrefix, $content);
		$db = M();
		$sqlList = sql_split($content);
		foreach ($sqlList as $query) {
			$query = trim($query);
			if ($query) {
			    //@ 符号（错误控制运算符）用于抑制函数调用的错误消息，但它不能用于屏蔽异常（Exception）
                //PHP8.1-8.3会抛出异常，会导致@无法屏蔽异常
                try{
                    $result = @$db->execute($query);
                }catch (Exception $e) {
                    $result = false;
                }
			}
		}
		return true;
	}

	//=====================最新版本的升级步骤 开始=====================
    /**
     * 检查文件权限
     */
    private function _checkUpgrade(){
        $dirlistToCheck = array(
            './App/',
            './App/Tpl/Admin/',
            './App/Tpl/Member/',
            './App/Common/',
            './Public/',
            CONF_PATH.'version.php',
        );
        foreach($dirlistToCheck as $dir){
            if(file_exists($dir) && !yd_is_writable($dir)){
                $this->_lastError = "{$dir} 没有写入权限";
                return false;
            }
        }
        return true;
    }

    /**
     * 第1步：下载升级文件
     * $version：当前最新版本
     */
    function downloadFile($version){
        $startTime = time();
        $b = $this->_checkUpgrade(); //检查升级基本条件
        if(false === $b) return false;
        //升级包名称：upgrade+版本号.zip
        $filename = "upgrade{$version}.zip";
        $url = "{$this->_upgradeUrl}{$filename}?t=".date('YmdHi');
        $savePath = "{$this->_cachePath}/{$filename}"; //保存路径
        //下载zip压缩包
        if(!is_dir($this->_cachePath)){
            @mkdir($this->_cachePath);
        }
        if(!is_dir($this->_sqlPath)){
            @mkdir($this->_sqlPath);
        }
        $b = @file_put_contents($savePath, @file_get_contents($url));
        if(false === $b) {
            return false;
        }
        session('UpgradeZipFile', $savePath); //保存为session防止前端注入
        $result = array();
        $result['ZipFile'] = $savePath;
        $result['Time'] = time() - $startTime;
        return $result;
    }

    /**
     *  第2步：解压升级包（这步花费时间最长，通常超过30秒，小于60秒）
     */
    function unzipFile($zipFile){
        $startTime = time();
        $zipFile =  session('UpgradeZipFile');
        session('UpgradeZipFile',null);  //立即删除
        $unzipDir = $this->_debug ? 'd:/临时/upgradetest/' : './';
        if(!file_exists($zipFile)){
            $this->_lastError = '升级包不存在！';
            return false;
        }
        $zipSize = filesize($zipFile)/(1024*1024);
        if( 0 !== stripos($zipFile, $this->_cachePath) ||
            false !==stripos($zipFile, '..') ||
            $zipSize < 8 ){
            $this->_lastError = '非法升级包！';
            return false;
        }
        import('ORG.Util.PclZip');
        $zip = new PclZip($zipFile);
        if($zip->extract(PCLZIP_OPT_PATH, $unzipDir, PCLZIP_OPT_REPLACE_NEWER) == 0){
            $errMsg = $zip->errorInfo();
            $this->_lastError = "解压失败！{$errMsg}";
            return false;
        }
        $result = array();
        $result['Time'] = time() - $startTime;
        return $result;
    }

    /**
     * 第3步：升级数据库
     * $versioin：升级前的版本号
     */
    function upgradeDb($newVersion, $releaseDate){
        $version = C('CMSVersion');
        $startTime = time();
        $filelist = glob("{$this->_sqlPath}upgrade*.sql");
        //对脚本文件升序排列，版本号必须是3位数，否则排序会出问题
        sort($filelist, SORT_STRING);
        foreach ($filelist as $fullFileName){
            $name = basename($fullFileName, '.sql');
            $temp = explode('_', $name);
            if(2 != count($temp)) continue;
            // 升级包名称：upgrade3位序号_版本号.zip 如：upgrade001_9.0.1.sql
            $sqlVersion = $temp[1];
            if(version_compare($sqlVersion, $version) > 0) {
                $this->_executeSqlFile($fullFileName);
            }
        }

        $this->_deleteUnsafeFile();

        //升级数据库成功，需要更新版本
        $version = array('CMSVersion'=>$newVersion, 'CMSReleaseDate'=>$releaseDate);
        $b = cache_array($version, CONF_PATH.'version.php', false);
        if(false === $b){
            $this->_lastError = '更新系统版本失败';
            return false;
        }

        //删除临时升级包
        if(is_dir($this->_cachePath)){
            @deldir($this->_cachePath);
        }
        if(is_dir($this->_sqlPath)){
            @deldir($this->_sqlPath);
        }

        YdCache::writeAll();
        //保存升级日志
        $api = create_api_Instance();
        //升级完成，删除文件和目录==========================
        $data = $api->getUpgradeExtraInfo();
        if(!empty($data)){
            foreach($data['DeleteFiles'] as $file){
                if(is_dir($file)){
                    @deldir($file);
                }else if(is_file($file)){
                    @unlink($file);
                }
            }
        }
        //==========================================

        //保存升级日志
        $api->saveUpgradeLog();
        $result = array();
        $result['Time'] = time() - $startTime;
        return $result;
    }

    /**
     * 删除目录下的不安全的文件
     */
    private function _deleteUnsafeFile(){
        deleteUnsafeFile('./Public');
        deleteUnsafeFile('./Install');
    }
    //=====================最新版本的升级步骤 结束=====================
}

/**
 * 插件升级
 */
class YdPluginUpgrade{
    //次版本号只能是一位
    private $_lastError = ''; //上次错误
    private $_code = 0; //api返回的code
    private $_upgradeUrl = 'http://upgrade.youdiancms.com/';
    private $_cachePath = '';

    function __construct(){
        $this->_cachePath = RUNTIME_PATH.'upgrade';
    }

    /**
     * 安装插件
     */
    function installPlugin($PluginID, $LatestVersion){
        $PluginID = intval($PluginID);
        $LatestVersion = YdInput::checkLetterNumber($LatestVersion);

        //如果插件目录不存在，就创建
        $pluginDir = './App/Plugin/';
        if(!is_dir($pluginDir)){
            @mkdir($pluginDir);
        }
        //第1步：检查目录权限
        $result = $this->checkPurview();
        if(false===$result)return false;

        //第2步：运行安装脚本
        $result = $this->executeSql(1, $PluginID);
        if(false===$result) return false;

        //第3步：下载并解压升级包
        $result = $this->downloadAndUnzip($PluginID, $LatestVersion);
        return $result;
    }

    /**
     * 卸载插件
     */
    function uninstallPlugin($PluginID, $InstallDir, $FileList){
        $PluginID = intval($PluginID);

        //第1步：检查目录权限，这里删除特定目录，不需要检查所有
        //$result = $this->checkPurview();
        //if(false===$result) return false;
        $this->backupData($PluginID);

        //第2步：运行卸载脚本
        $result = $this->executeSql(2, $PluginID);
        if(false===$result) return false;

        //第3步：删除安装目录
        if(!empty($InstallDir)){
            $b = $this->checkInstallDir($InstallDir);
            if(false===$b){
                $this->_lastError = "{$InstallDir}目录无效！";
                return false;
            }
            if(file_exists($InstallDir) && !yd_is_writable($InstallDir)){
                $this->_lastError = "{$InstallDir}目录没有写入权限，删除失败";
                return false;
            }
            @deldir($InstallDir);
        }

        //第4步：删除插件相关文件
        $FileList = ''; //暂不使用FileList
        if(!empty($FileList)){
            $FileList = explode(',', $FileList);
            foreach($FileList as $fileName){
                if(file_exists($fileName)){
                    unlink($fileName);
                }
            }
        }
        return true;
    }

    private function checkInstallDir($InstallDir){
        $key = strtolower($InstallDir);
        $map = array(
            './app/tpl/admin/default/site/'=>1,
            './app/tpl/admin/default/horsescan/'=>1,
            './app/plugin/multixcx/'=>1,
            './app/tpl/admin/default/importwx/'=>1,
        );
        if(isset($map[$key])){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 备份插件数据
     */
    private function backupData($PluginID){
        $PluginID = intval($PluginID);
        if(164 == $PluginID){
            $prefix = C('DB_PREFIX');
            $tables = array("{$prefix}template", "{$prefix}template_page");
            $sql = get_table_sql($tables);
            if(empty($sql)) return;
            $dataDir = APP_DATA_PATH.'sql/';
            if(!is_dir($dataDir)){
                @mkdir($dataDir, 0755, true);
            }
            $fileName = "{$dataDir}xcx".date("Y-m-d_H_i_s").rand_string(5,10).'.sql';
            file_put_contents($fileName, $sql);
        }
    }

    /**
     * 升级插件
     * $PluginVersion：当前插件的版本
     */
    function upgradePlugin($PluginID, $PluginVersion, $LatestVersion){
        $PluginID = intval($PluginID);
        $PluginVersion = YdInput::checkLetterNumber($PluginVersion);
        $LatestVersion = YdInput::checkLetterNumber($LatestVersion);

        //第1步：检查目录权限
        $result = $this->checkPurview();
        if(false===$result)return false;

        //第2步：运行升级脚本
        $result = $this->executeSql(3, $PluginID, $PluginVersion);
        if(false===$result) return false;

        //第3步：下载并解压升级包
        $result = $this->downloadAndUnzip($PluginID, $LatestVersion);
        return $result;
    }

    //获取最后错误数据
    function getLastError(){
        return $this->_lastError;
    }

    /**
     * 返回对应的错误代码
     */
    function getCode(){
        return $this->_code;
    }

    /**
     * 检查目录权限
     */
    private function checkPurview(){
        $dirlistToCheck = array('./App/Plugin/', './App/Tpl/Admin/Default/Plugin/', './App/Common/', );
        foreach($dirlistToCheck as $dir){
            if(file_exists($dir) && !yd_is_writable($dir)){
                $this->_lastError = "{$dir}目录没有写入权限";
                return false;
            }
        }
        //先删除下载缓存目录
        @deldir($this->_cachePath);
        @mkdir($this->_cachePath);
        return true;
    }

    /**
     * 下载并解压文件
     */
    private function downloadAndUnzip($PluginID, $LatestVersion){
        $ZipFile = "plugin{$PluginID}_{$LatestVersion}.zip";
        $url = "{$this->_upgradeUrl}{$ZipFile}?t=".date('YmdHi');
        $savedZipFile = "{$this->_cachePath}/{$ZipFile}"; //保存路径
        if(file_exists($savedZipFile)) @unlink($savedZipFile); //防止非法解压文件
        $b = @file_put_contents($savedZipFile, @file_get_contents($url)); //下载zip压缩包
        if(empty($b)) {
            $this->_lastError = "下载升级包失败";
            return false;
        }

        import('ORG.Util.PclZip');
        $zip = new PclZip($savedZipFile);
        if($zip->extract(PCLZIP_OPT_PATH, './', PCLZIP_OPT_REPLACE_NEWER) == 0){
            $this->_lastError = '解压失败！';
            return false;
        }
        //解压完成，删除插件zip包
        if(file_exists($savedZipFile)) @unlink($savedZipFile);
        return true;
    }

    /**
     * 执行SQL脚本（安装，卸载，升级）
     */
    private function executeSql($SqlType, $PluginID, $PluginVersion='99'){
        $PluginID = intval($PluginID);
        $SqlType = intval($SqlType);
        $sqlList = $this->getPluginSql($SqlType, $PluginID, $PluginVersion); //获取升级脚本
        if(false === $sqlList) {
            return false;
        }

        $dbPrefix = C('DB_PREFIX');
        $db = M();
        foreach ($sqlList as $sql) {
            $sql = str_ireplace('[@DbPrefix]', $dbPrefix, trim($sql));
            if (!empty($sql)) {
                //@ 符号（错误控制运算符）用于抑制函数调用的错误消息，但它不能用于屏蔽异常（Exception）
                //PHP8.1-8.3会抛出异常，会导致@无法屏蔽异常
                try{
                    $result = @$db->execute($sql);
                }catch (Exception $e) {
                    $result = false;
                }
            }
        }
        return true;
    }

    /**
     * 获取升级脚本
     * $PluginVersion：当前插件的版本号
     */
    private function getPluginSql($SqlType=3, $PluginID=0, $PluginVersion=99){
        $api = create_api_Instance();
        $data = $api->getPluginSql($SqlType, $PluginID, $PluginVersion);
        if(false === $data) {
            $this->_lastError = $api->getLastError();
            $this->_code = $api->getCode();
            return false;
        }
        return $data;
    }
}

function create_api_Instance(){
    import("@.Common.YdApi");
    $api = new YdApi();
    if(isset($_POST['Token'])){
        $api->setToken($_POST['Token']);
    }
    return $api;
}