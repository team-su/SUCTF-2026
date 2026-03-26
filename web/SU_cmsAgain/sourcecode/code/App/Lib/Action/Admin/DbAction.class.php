<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class DbAction extends AdminBaseAction{
    protected $db = '';
    protected $datadir;
    protected $zipdir;
    function _initialize(){
		parent::_initialize();
		//$db=D('');
		$this->db =   Db::getInstance();
		$this->sqldir = APP_DATA_PATH.'sql/'; //sql备份存储目录
		$this->zipdir = APP_DATA_PATH.'zip/'; //sql备份存储目录
    }

    //数据库管理首页
    function index(){
        $data = $this->db->query("SHOW TABLE STATUS LIKE '".C('DB_PREFIX')."%'");
        $DbSize = 0;
        $n = is_array($data) ? count($data) : 0;
        for($i = 0; $i < $n; $i++){
        	$check = $this->db->query('CHECK TABLE ' . $data[$i]['Name']);
        	$DbSize += $data[$i]['Data_length'];
        	$data[$i]['Status'] = isset($check[0]['Msg_text']) ? $check[0]['Msg_text'] : '';
        }
        $this->assign('DbSize', $DbSize);
        $this->assign('TableCount', $n);
        $this->assign("Db", $data);
        unset($data);
        $this->display();
    }

    /**
     * 判断当前是否是mysql数据库
     */
    private function _checkDbType($type=1){
        $dbType = strtolower(C('DB_TYPE'));
        $map = array(1=>"抱歉，不支持备份数据库{$dbType}", 2=>"");
        if ('mysqli' != $dbType){
            $msg = isset($map[$type]) ? $map[$type] : '';
            $this->ajaxReturn(null,  $msg, 0);
        }
    }
    
    //备份数据库(支持所有表备份和指定表备份)
    function backup(){
        $this->_checkDbType();
    	if( !is_dir($this->sqldir)){
    		$b = @mkdir($this->sqldir,0755,true); //循环创建目录
    		if($b == false){
    			$this->ajaxReturn(null, "创建目录{$this->sqldir}失败" , 0);
    		}
    	}
    	
    	if( !yd_is_writable( $this->sqldir ) ){
    		$this->ajaxReturn(null, "备份数据库失败，目录 {$this->sqldir} 没有写入权限" , 0);
    	}
    	$filename = date('Y-m-d_H_i_s').'_'.rand_string(6,10).'.sql';
    	$fileFullName = $this->sqldir . $filename;
    	$this->_backup( $fileFullName );
    }

    //一键备份全站时调用
    //仅备份数据，将数据备份成insert into语句，不备份表表结构
    function backupData(){
    	//先必须检查是否有写入权限
    	if( !yd_is_writable("./Data/") ){
    		$this->ajaxReturn(null, "备份失败，目录 ./Data/ 不存在或没有写入权限" , 0);
    	}
    	
    	//先删除上一次备份的数据文件
    	foreach (glob("./Data/db*.sql") as $filename) {
    		@unlink($filename);
    	}
    	
    	$fileFullName = "./Data/db".date("Y-m-d_H_i_s", time()).'_'.rand_string(6,10).'.sql';
    	$this->_backup( $fileFullName );
    }
    
    //备份数据， $fileFullName：采用相对路径
    private function _backup($fileFullName){
        yd_set_time_limit(600);
    	@ini_set('memory_limit', -1);
        $tables = array();
    	if(empty($_POST['tables'])) {
    		$dataList = $this->db->query("SHOW TABLE STATUS LIKE '".C('DB_PREFIX')."%'");
    		foreach ($dataList as $row){
    			$tables[]= $row['Name'];
    		}
    	}else{
            $tables = $this->_checkTable();
    	}
    	//$sql = "--SQL Backup Time:".yd_to_date(time())."\n";
    	$sql=""; //加上上句，直接在navicate执行导出的sql会报语法错误
    	//导出表结构 start===============================
    	$filter = array(
    	    'USING BTREE','ROW_FORMAT=DYNAMIC','ENGINE=InnoDB ','ENGINE=MyISAM ',
            'DEFAULT CHARSET=utf8mb4'); //utf8mb4只有mysql5.5才支持，5.1安装时会报错
    	foreach($tables as $table) {
    		$sql .= "\nDROP TABLE IF EXISTS `$table`;\n";
    		$info = $this->db->query("SHOW CREATE TABLE  $table");
    		$sql .= str_ireplace($filter,'',$info[0]['Create Table']).";\n";
    	}
        file_put_contents($fileFullName, $sql, FILE_APPEND);
    	//导出表结构 end================================
    	 
    	//导出数据 start================================
    	//mysql的max_allowed_packet默认值为1M 设置过小导致记录写入失败
    	//$maxRecord = 30; //默认值为：30，一个insert最多插入的记录数
        $r = true;
    	$maxPacketSize = 256 * 1024; //每次insert不能大于256k
    	$sql = "\n\n";
    	foreach($tables as $table) {
    		$row = 0;
    		$insertSize = 0;
    		$result = $this->db->query("SELECT * FROM $table ");
    		if( !empty( $result ) ){
    			$total = count($result);
    			foreach($result as $key=>$val) {
    				if( $insertSize == 0 ){
    					$sql .= "INSERT INTO `$table` VALUES\n";
    				}
    				
    				//所有字段
    				foreach ($val as $k=>$field){
    					if(is_string($field)) {
    						$val[$k] = '\''. $this->db->escapeString($field).'\'';
    					}elseif(empty($field)){
    						$val[$k] = 'NULL';
    					}
    				}
    				$sqlValues = "(".implode(',', $val).")";
    				$insertSize += strlen($sqlValues);
    				if( $insertSize > $maxPacketSize){
    					$insertSize = 0;
    				}
    				$sql .= $sqlValues;
    				
    				$row++;
    				if( $insertSize == 0 || $row == $total){
    					$sql .= ";\n\n";
    					$r = file_put_contents($fileFullName, $sql, FILE_APPEND);
    					$sql = '';
    				}else{
    					$sql .= ",\n";
    				}
    			} //foreach
    		} //end emtpy($result)
    	}
    	//导出数据 end================================
    	 
    	$filename = basename($fileFullName);
    	if($r){
            $filename = $this->_getSafeName($filename);
    		WriteLog($filename);
    		$result['NoUpload'] = intval($_GET['NoUpload']);
    		$this->ajaxReturn($result, "数据备份完成!" , 1);
    	}else{
    		$this->ajaxReturn(null, '备份失败!' , 0);
    	}
    }

    /**
     * 检查表名的有效性
     */
    private function _checkTable(){
        $tables = array();
        if(!empty($_POST['tables'])){
            foreach($_POST['tables'] as $v){
                if(preg_match('/^[A-Za-z0-9_]+$/',  $v)){
                    $tables[] = $v;
                }
            }
        }
        return $tables;
    }
    
    //一键备份全站查看
    function backupAll(){
    	header("Content-Type:text/html; charset=utf-8");
    	$filelist = yd_dir_list($this->zipdir, 'zip');
    	$TotalSize = 0;
    	$files = array();
    	foreach ((array)$filelist as $r){
    		$filesize = filesize($r);
    		$time = filemtime($r);
            $name = $this->_getSafeName($r);
    		$files[] = array('path'=> $r,'Name' => $name, 'Size' => $filesize, 'Time' => $time );
    		$TotalSize += $filesize;
    	}
    	krsort($files, SORT_NUMERIC);
    	$this->assign('SqlFileCount', count($files));
    	$this->assign('SqlFileTotalSize', $TotalSize);
    	$this->assign('SqlFile',$files);
    	$this->display();
    }
    
    //执行一键备份全站
    function doBackupAll(){
    		header("Content-Type:text/html; charset=utf-8");
            yd_set_time_limit(900);
    		@ini_set('memory_limit', -1);

    		$this->checkAdmin();
    		//如果zip目录不存在则创建
    		if( !is_dir($this->zipdir)){
    			$b = @mkdir($this->zipdir,0755,true); //循环创建目录
    			if($b == false){
    				$this->ajaxReturn(null, "创建目录{$this->zipdir}失败" , 0);
    			}
    		}
    
    		import('ORG.Util.PclZip');
    		$dir = realpath('./');
    		$webUrl = $_SERVER['HTTP_HOST'];
    		//过滤非法文件字符
    		$invalidChars = array('www.','https://', 'http://', '\\', '/', ':', '*', '?', '"', '<', '>', '|');
    		$webUrl = str_ireplace($invalidChars, '', $webUrl);
    		if(empty($webUrl)){  //后面增加4个随机字符，提高系统安全性
    			$zipname = 'all'.date("Y-m-d_H_i_s", time()).'_'.rand_string(6,10).'.zip';
    		}else{
    			$zipname = $webUrl.date("Y-m-d_H_i_s", time()).'_'.rand_string(6,10).'.zip';
    		}
    
    		$zipfilepath = $this->zipdir.$zipname;
    		$archive = new PclZip($zipfilepath);
    		//压缩时，通过回调函数ZipPreAddCallBack排除intall.lock文件
    		$v_list = $archive->create($dir, PCLZIP_OPT_REMOVE_PATH, $dir, PCLZIP_CB_PRE_ADD, 'ZipPreAddCallBack' );
    		if ($v_list == 0) {
    		    $errInfo = $archive->errorInfo(true);
                $code = mb_detect_encoding ($errInfo, array ("ASCII",'UTF-8',"GB2312","GBK",'BIG5'));
                if('GB2312'==$code || 'EUC-CN'==$code){ //返回EUC-CN就是GB2312
                    $errInfo = mb_convert_encoding($errInfo, "UTF-8", "GB2312");
                    $errMsg = "全站打包压缩失败！不能包含中文文件名！\n{$errInfo}";
                }else{
                    $errMsg = "全站打包压缩失败!\n{$errInfo}";
                }
    			$this->ajaxReturn(null,  $errMsg, 0);
    		}else{
    			$data['ZipName'] = $zipname;
    			$data['ZipSize'] = byte_format( filesize($zipfilepath) );
    			$data['ZipTime'] = yd_friend_date( filemtime($zipfilepath) );
    			$filelist = yd_dir_list($this->zipdir, 'zip');
    			$data['TotalSize'] = 0;
    			$data['FileCount'] = 0;
    			if( !empty($filelist) ){
    				$data['FileCount'] = count($filelist);
	    			foreach ((array)$filelist as $r){
	    				$data['TotalSize'] += filesize($r);
	    			}
	    			$data['TotalSize'] = byte_format( $data['TotalSize'] );
    			}
    			//保存日志不能保存原始文件名称
    			$safeZipName = $this->_getSafeName($zipname);
                WriteLog($safeZipName);
    			$this->ajaxReturn($data, '全站打包压缩成功!' , 1);
    		}
    }
    
    //删除数据文件
    function delZip(){
    	$files = $_REQUEST['file']; //(array)不放在这里避免误报病毒
        foreach ( (array)$files as $f){
            $filename = $this->_getSafeFile($f);
        	if( file_exists($filename)){
    			@unlink($filename);
        	}
    	}
    	WriteLog(implode(',', (array)$files));
    	redirect(__URL__.'/backupAll');
    }

    /**
     * 获取zip下载包的真实地址（需要做二次判断）
     */
    function getZipDownloadUrl(){
        $name = trim($_POST['ZipName']);
        $ext = strtolower(yd_file_ext($name));
        if($ext !== 'zip'){
            $this->ajaxReturn(null, '参数异常！' , 0);
        }
        $options['UserAction'] = '一键备份全站->下载';
        $options['LogType'] = 1; //其他操作
        WriteLog("下载备份文件{$name}", $options);
        $name = $this->_getSafeFile($name);
        if(!empty($name)){
            $name = basename($name);
            $this->ajaxReturn($name, '' , 1);
        }else{
            $this->ajaxReturn(null, '获取下载地址失败！' , 0);
        }
    }
    
    
   function downloadAll(){
    	$zipname = YdInput::checkFileName(trim($_GET['zipname']));
    	$zipfilepath = RUNTIME_PATH.$zipname;
    	if( file_exists($zipfilepath) ){
    		$downfile = @fopen($zipfilepath,"r");
    		$downsize = @filesize($zipfilepath);
    		@Header("Content-type: application/octet-stream");
    		@Header("Accept-Ranges: bytes");
    		@Header("Accept-Length: ".$downsize);
    		@Header("Content-Disposition: attachment; filename=".$zipname);
    		echo @fread($downfile, $downsize);
    		@fclose($downfile);
    		//@unlink($zipfilepath); //下载完毕,删除压缩文件， 加上此指令会导致无法下载
    	}
    }
    
    //显示列信息
    function columns(){
    	$table = YdInput::checkLetterNumber($_GET['TableName']);
    	if(!empty($table)){
    		$data = $this->excuteQuery("SHOW COLUMNS FROM {$table}");
    		$this->ajaxReturn($data['result'], $table , 3);
    		unset($data);
    	}
    }
    
    //检查
    function check(){
		$this->_command('check');
    }
    
    //分析表
    function analyze(){
		$this->_command('analyze');
    }
    
    //优化
    function optimize(){
    	$this->_command('optimize');
    }
    
    //修复
    function repair(){
    	$this->_command('repair');
    }
    
    //数据库维护命令
    private function _command($cmd){
    	$tables = $this->_checkTable();
		if (empty ( $tables )) {
			$this->ajaxReturn ( null, '参数错误!', 0 );
		}
		$tables = implode ( ',', $tables );
		$r = $this->excuteQuery ( $cmd . ' TABLE ' . $tables );
		if (false != $r) {
			$result = $r ['result'];
			$n = count ( $result );
			$status = '';
			foreach ( $result as $value ) {
				$status .= '表' . $value ['Table'] . '状态：' . $value ['Msg_text'] . "\n";
			}
			$map = array('check'=>'检查', 'analyze'=>'分析', 'optimize'=>'优化', 'repair'=>'修复',);
			$cmdName = $map[$cmd];
            WriteLog("{$cmdName} {$n}个表成功！");
			$this->ajaxReturn ( null, "{$cmdName} {$n}个表成功！", 1 );
		} else {
			$this->ajaxReturn ( null, $r ['dbError'], 0 );
		}
	}
	
	private function excuteQuery($sql = '') {
		if (empty ( $sql )) {
			$this->error ( L ( 'do_empty' ) );
		}
		$queryType = 'INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|LOAD DATA|SELECT .* INTO|COPY|ALTER|GRANT|TRUNCATE|REVOKE|LOCK|UNLOCK';
		try{
            if (preg_match('/^\s*"?(' . $queryType . ')\s+/i', $sql)) {
                $data['type'] = 'execute';
                $data['result'] = $this->db->execute($sql);
            }else {
                $data['type'] = 'query';
                $data['result'] = $this->db->query($sql);
            }
            $data['dberror'] = $this->db->error();
        }catch (Exception $e) {
            $data['dberror'] = $e->getMessage();
            $data['result'] = false;
        }
        return $data;
    }

    //数据还原首页
    function restore(){
    	$filelist = yd_dir_list($this->sqldir, 'sql');
    	$TotalSize = 0;
        $files = array();
    	foreach ((array)$filelist as $r){
    		$filesize = filesize($r);
    		$time = filemtime($r);
            $name = $this->_getSafeName($r);
    		$files[$time] = array('path'=> $r,'Name' => $name, 'Size' => $filesize, 'Time' => $time );
    		$TotalSize += $filesize;
    	}
    	if(is_array($files)) {
    	    krsort($files, SORT_NUMERIC);
    	    $n = count($files);
        }else{
    	    $n = 0;
        }
    	$this->assign('SqlFileCount', $n);
    	$this->assign('SqlFileTotalSize', $TotalSize);
    	$this->assign('SqlFile',$files);
    	$this->display();
    }
    
    //批量删除备份文件
    function batchDelSqlFile(){
        $files = $_POST['files'];
    	foreach ((array)$files as $r){
            $file = $this->_getSafeFile($r);
    		@unlink($file);
    	}
    	WriteLog(implode(',', (array)$files));
    	redirect(__URL__.'/restore');
    }
    
    //删除数据文件
    function delSqlFile(){
    	$file = $this->_getSafeFile($_GET['file']);
    	if(!empty($file) ){
    		@unlink($file);
    	}
    	WriteLog($file);
    	redirect(__URL__.'/restore');
    }

    /**
     * $file可以是文件名也可以是全路径
     */
    private function _getSafeName($file){
        $name = basename($file);
        $pos = strrpos($name,'_'); //找到最后一个_
        if($pos > 0){
            $ext = yd_file_ext($name);
            $name = substr($name, 0, $pos).".{$ext}";
        }
        return $name;
    }

    private function _getSafeFile($file){
        $file = YdInput::checkFileName( $file );
        $ext = strtolower(yd_file_ext($file));
        $pos = strrpos($file, '.');
        $file = substr($file, 0, $pos);
        if($ext==='sql'){
            $filelist = yd_dir_list($this->sqldir, 'sql');
        }else{
            $filelist = yd_dir_list($this->zipdir, 'zip');
        }
        $fileName = '';
        foreach($filelist as $v){
            if(false!==stripos($v, $file)){
                $fileName = $v;
                break;
            }
        }
        return $fileName;
    }
    
    //查看sql文件内容
    function viewSqlFile(){
        return; //不安全
    	$_REQUEST['file'] = YdInput::checkFileName( $_REQUEST['file'] );
    	if(!empty($_REQUEST['file']) ){
    		$content= file_get_contents($this->sqldir.$_REQUEST['file']);
    		if($content){
    			//如果$content存在乱码，会返回null
    			$this->ajaxReturn($content, $_REQUEST['file'], 1);
    		}else{
    			$this->ajaxReturn(null, '文件过大，无法查看，请下载至本地查看！' , 0);
    		}
    	}else{
    		$this->ajaxReturn(null, '操作失败!' , 0);
    	}
    }
    
    //下载备份
    function downloadSqlFile(){
            return;  //不安全
	    	$name = YdInput::checkFileName($_GET['file']);
	    	$file_dir = $this->sqldir;	    
	    	if (file_exists($file_dir.$name)){
		    	Header("Content-type: application/octet-stream");
		    	Header("Accept-Ranges: bytes");
		    	Header("Accept-Length: ".filesize($file_dir . $name));
		    	Header("Content-Disposition: attachment; filename=".$name);
		    	
		    	/* 无法下载大于300M的文件时
		    	ob_end_flush();  //必须加上此句，否则有可能提示内存错误
		    	readfile($file_dir.$name);
		    	*/
		    	
		    	//若压缩文件过大会报内存错误
		    	//解决方案：fread每次读取读取一段文件下载
		    	ob_clean();
		    	$buffer = 4096; //单位：字节
		    	$buffer_count = 0;
		    	$file = fopen($file_dir.$name,"r");
		    	$filesize = filesize($file_dir.$name);
		    	while(!feof($file) && $filesize-$buffer_count>0){
		    		$data = fread($file, $buffer);
		    		$buffer_count += $buffer;
		    		echo $data;
		    	}
		    	fclose($file);
	    	}

    }

    //数据恢复
	function recover(){
			header('Content-Type: text/html; charset=UTF-8');
            yd_set_time_limit(900);
			@ini_set('memory_limit', -1);
            $file = $this->_getSafeFile($_GET['file']);
			//读取数据文件
			$sqldata = file_get_contents($file);
			$dbPrefix = C('DB_PREFIX');
			$sqlFormat = sql_splitEx($sqldata, $dbPrefix);
			$oldDbPrefix = get_table_prefix( $sqlFormat[0] );
			foreach ((array)$sqlFormat as $sql){
					$sql = str_replace_once($oldDbPrefix, $dbPrefix, $sql); //替换表前缀
					if (strstr($sql, 'CREATE TABLE')){ //创建表
						preg_match('/CREATE TABLE `([^ ]*)`/', $sql, $matches);
						$ret = $this->excuteQuery($sql);
						if($ret){
							//echo   L('CREATE_TABLE_OK').$matches[0].' <br />';
						}else{
							$this->ajaxReturn(null, '数据还原失败！', 0);
						}
					}else{
						$ret =$this->excuteQuery($sql);
					}
			}
            WriteLog("数据还原成功！{$_GET['file']}");
			YdCache::writeAll(); //重新写入所有缓存
			$this->ajaxReturn(null, '数据还原成功！', 1);
	}
	
	/**
	 * 批量执行SQL语句
	 */
	function sql(){
	    return;
		header('Content-Type: text/html; charset=UTF-8');
		$code = rand(1000, 9999);
		session("SafeCode", $code);
		$this->assign("SafeCode", md5($code));
		$this->assign('Action', __URL__.'/executeSql' );
		$this->display();
	}

    /**
     * 太危险，不在提供此功能
     */
	function executeSql(){
	    return;
		header('Content-Type: text/html; charset=UTF-8');
		//防止攻击
		$code = md5(session("SafeCode"));
		if( empty($_POST['SafeCode']) || $_POST['SafeCode'] != $code){
			$this->ajaxReturn(null, '非法执行', 0);
		}
		
		if( empty($_POST['sql']) ){
			$this->ajaxReturn(null, 'sql语句不能为空', 0);
		}
		if (get_magic_quotes_gpc()) {
			$_POST['sql'] = stripslashes($_POST['sql']);
		}
		@set_time_limit(0);
		@ini_set('memory_limit', -1);
		$dbPrefix = C('DB_PREFIX');
		$sqls = sql_splitEx($_POST['sql'], $dbPrefix);
		$count = count($sqls);
		if( $count > 0 ){
			$oldDbPrefix = get_table_prefix( $sqls[0] );
			$log = array();
			$maxLen = 55; //显示sql的最大长度
			foreach ($sqls as $sql){
				if( !empty($oldDbPrefix) && $dbPrefix != $oldDbPrefix){
					$sql = str_replace_once($oldDbPrefix, $dbPrefix, $sql); //替换表前缀
				}
				$ret =$this->excuteQuery($sql);
				$n = mb_strlen( $sql , 'utf-8');  //获取实际内容的长度
				$showSql = ( $n > $maxLen ) ? msubstr( $sql, 0, $maxLen, 'utf-8', '...') : $sql;
				if( false === $ret['result']){ //执行报错
					$error = explode("\n", $ret['dberror']);
					$log[] = array('sql'=>$showSql, 'error'=>$error[0], 'n'=>0);
				}else if( is_numeric($ret['result']) ){
					$log[] = array('sql'=>$showSql, 'error'=>'', 'n'=>$ret['result']); //n:影响行数
				}else{
					$log[] = array('sql'=>$showSql, 'error'=>'', 'n'=>'-1');
				}
			}
			WriteLog("共执行{$count}条SQL语句");
			$this->ajaxReturn($log, '', 1);
		}else{
			$this->ajaxReturn(null, '没有sql语句', 0);
		}
	}
	
}

/**
 * 压缩时，排除install.lock文件
 */
function ZipPreAddCallBack($p_event, &$p_header){
    $list = array('Data/install.lock', 'Data/zip', 'Data/runtime', 'Data/html', 'Data/app.debug', 'App/Conf/db.php');
    $NoUpload = intval($_POST['NoUpload']);
    if(1 == $NoUpload){  //不备份Upload目录
        $list[] = 'Upload/';  //加/表示会创建一个Upload空目录
    }

	$file = $p_header['stored_filename']; //存储全路径，如：App/Conf/copy.php
	//只能修改$p_header中filename属性，其它都是只读
	//db.php带数据库账号信息，不适合添加到压缩包
	//不备份zip和runtime目录
    foreach($list as $v){
        if (0 === stripos($file, $v)){
            return 0;
        }
    }
    return 1;
	/*
	 $info = pathinfo($p_header['stored_filename']);
    // ----- bak files are skipped
    if ($info['extension'] == 'bak') {
      return 0;
    }
    // ----- jpg files are add with an images folder
    else if ($info['extension'] == 'jpg') {
      $p_header['stored_filename'] = 'images/'.$info['basename'];
      return 1;
    }
    // ----- all other files are simply added
    else {
      return 1;
    }
	 */
}

function  sql_splitEx($sql,$tablepre) {
	//升级包中的sql脚本表前缀统一替换成了[@DbPrefix]
	 $sql = str_ireplace('[@DbPrefix]', $tablepre, $sql);
	//if($tablepre != "youdian_") $sql = str_replace("youdian_", $tablepre, $sql);
	//sql语句预处理================
	$sql = str_replace("\r", "\n", $sql);
	$sql = trim($sql);
	$sql = trim($sql, ';');
	//=========================
	
	$ret = array(); //存储返回的sql数组
	$num = 0;
	$queriesarray = explode(";\n", $sql);  //sql中用";\n"分割每个执行语句
	unset($sql);
	foreach($queriesarray as $query){
		$query = trim($query);
		if( !empty($query) && substr($query, 0, 1) != '#'){
			$ret[$num] = '';
			$queries = explode("\n", $query);
			$queries = array_filter($queries); //去除空行
			foreach($queries as $v){ //去除以#或-开头的注释
				$str1 = substr($v, 0, 1);
				if($str1 != '#' && $str1 != '-') $ret[$num] .= $v;
			}
			$num++;
		}
	}
	return $ret;
}