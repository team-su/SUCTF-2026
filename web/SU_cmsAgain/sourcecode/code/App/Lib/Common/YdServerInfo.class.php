<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
if (!defined('APP_NAME')) exit();
class YdServerInfo{
	private $_NoYes = array(
			'no'=>array('image'=>"<span class='ImageNo'>×</span>", 'cn'=>"<span class='CnNo'>不支持</span>", 'en'=>"<span class='EnNo'>No</span>"),
			'yes'=>array('image'=>"<span class='ImageYes'>√</span>", 'cn'=>"<span class='CnYes'>支持</span>", 'en'=>"<span class='EnYes'>Yes</span>")
	);
	private $_format = 'image';

	function __construct($format = 'image'){
		$this->setFormat($format);
	}

	/**
	 * 获取格式数据
	 * @return string
	 */
	function getFormat(){
		return $this->_format;
	}

	/**
	 * 设置格式数据
	 * @param string $format
	 */
	function setFormat($format = 'image'){
		if( array_key_exists($format, $this->_NoYes) ){
			$this->_format = $format;
		}else{
			$this->_format = 'image';
		}
	}

	/**
	 * 获取PHP配置信息
	 * @param string $varName
	 * @return string
	 */
	function getVar($varName){
		switch ($res = get_cfg_var($varName)){
			case 0:
				return $this->_NoYes['no'][$this->_format];
				break;
			case 1:
				return $this->_NoYes['yes'][$this->_format];
				break;
			default:
				return $res;
				break;
		}
	}

	function functionExists($name){
		return function_exists($name) ? $this->_NoYes['yes'][$this->_format] :  $this->_NoYes['no'][$this->_format];
	}

	/**
	 *  返回服务器基本信息
	 * @param int $format 输出格式: image:图像; cn:中文; en:英文
	 * @return array 服务器基本信息
	 */
	function getServerInfo(){
		//服务器
		$ServerInfo = array();

		$ServerInfo['OS'] = php_uname();  //操作系统版本号
		$ServerInfo['PHPVersion'] = phpversion();  //PHP版本号
        //如果直接使用：$_SERVER['SERVER_ADDR'] 部分主机可能返回127.0.0.1
		$ServerInfo['IP']   = @gethostbyname($_SERVER['SERVER_NAME']);  //服务器IP
		$ServerInfo['PHPSAPI'] = strtoupper(php_sapi_name()); // php运行方式(PHP run mode：apache2handler)
		$ServerInfo['CPUNumber'] = isset($_ENV['NUMBER_OF_PROCESSORS']) ? $_ENV['NUMBER_OF_PROCESSORS'] : null;  //CPU个数
        //Apache/2.4.39 (Win64) OpenSSL/1.1.1b mod_fcgid/2.3.9a mod_log_rotate/1.02
        $ServerSoftware = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '';
        $pos = strpos($ServerSoftware, ' ');  //可能没有空格
        if (false !== $pos) $ServerSoftware = substr($ServerSoftware, 0, $pos);
        $ServerInfo['WebServerName'] = $ServerSoftware;

		$ServerInfo['AcceptLanguage'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];  //服务器操作系统文字编码

		$ServerInfo['PHPSafe'] = $this->getVar('safe_mode');  //PHP安全模式
		$ServerInfo['MaxPostSize'] = $this->getVar('post_max_size');  //最大post大小
        //最大input个数，默认为1000，批量保存频道可能会超过此数量
        $ServerInfo['MaxInputVars'] = $this->getVar('max_input_vars');

		$ServerInfo['UploadMaxFileSize']  = $this->getVar('upload_max_filesize');  //最大上传大小
		$ServerInfo['MaxExecutionTime']  = $this->getVar('max_execution_time') ; //脚本最长执行时间

		$ServerInfo['RegisterGlobals'] = $this->getVar('register_globals');  //自动定义全局变量
		$ServerInfo['MemoryLimit'] = $this->getVar('memory_limit'); //程序最多允许使用内存量 memory_limit
		$ServerInfo['Time'] = gmdate("Y年n月j日 H:i:s", time() + 8 * 3600);  //服务器时间
		$ServerInfo['Session'] = $this->functionExists('session_start');
		$ServerInfo['Cookie'] = isset($_COOKIE) ? $this->_NoYes['yes'][$this->_format] : $this->_NoYes['no'][$this->_format];


		//PHP组件支持
		$ServerInfo['ZendOptimizer'] = (get_cfg_var('zend_optimizer.optimization_level') || get_cfg_var('zend_extension_manager.optimizer_ts') || get_cfg_var('zend_extension_ts')) ? $this->_NoYes['yes'][$this->_format] : $this->_NoYes['no'][$this->_format];
		$ServerInfo['Iconv'] = $this->functionExists('iconv');
		$ServerInfo['CurlInit'] = $this->functionExists('curl_init');
		$ServerInfo['EacceleratorInfo'] = $this->functionExists('eaccelerator_info');
		$ServerInfo['XCache'] = extension_loaded('XCache') ? $this->_NoYes['yes'][$this->_format] : $this->_NoYes['no'][$this->_format];

		$ServerInfo['ASpell'] = $this->functionExists('aspell_new');  //拼写检查 ASpell Library
		$ServerInfo['BCMath'] = $this->functionExists('bcadd');  //高精度数学运算 BCMath
		$ServerInfo['Calendar'] = $this->functionExists('JDToFrench');  //历法运算 Calendar
		$ServerInfo['GD'] = $this->functionExists('imageline');  //图形处理 GD Library
		$ServerInfo['MbString'] = $this->functionExists('mb_strlen');  //图形处理 GD Library

		$ServerInfo['Class'] = $this->functionExists('class_exists');  //类/对象支持
		$ServerInfo['CType'] = $this->functionExists('ctype_upper');  //字串类型检测支持
		$ServerInfo['Iconv'] = $this->functionExists('iconv');  //iconv编码支持
		$ServerInfo['MCrypt'] = $this->functionExists('mcrypt_cbc');  //MCrypt加密处理支持
		$ServerInfo['MHash'] = $this->functionExists('mhash');  //哈稀计算 MHash

		$ServerInfo['OpenSSL'] = $this->functionExists('openssl_open');  //OpenSSL支持
		$ServerInfo['Socket'] = $this->functionExists('fsockopen');  //Socket支持
		$ServerInfo['StreamMedia'] = $this->functionExists('stream_context_create');  //流媒体支持
		$ServerInfo['Tokenizer'] = $this->functionExists('token_name');  //Tokenizer支持
		$ServerInfo['Zlib'] = $this->functionExists('gzclose');  //压缩文件支持(Zlib)

		$ServerInfo['XML'] = $this->functionExists('xml_set_object');  //XML解析
		$ServerInfo['LDAP'] = $this->functionExists('ldap_close');  //目录存取协议(LDAP)支持
		$ServerInfo['YellowPage'] = $this->functionExists('yp_match');  //Yellow Page系统支持
		$ServerInfo['PDF'] = $this->functionExists('pdf_close');  //PDF文档支持

		$ServerInfo['MagicQuotes'] = get_magic_quotes_gpc() ? $this->_NoYes['yes'][$this->_format] : $this->_NoYes['no'][$this->_format];

        $ServerInfo['MagicQuotesRuntime'] = get_magic_quotes_runtime() ? $this->_NoYes['yes'][$this->_format] : $this->_NoYes['no'][$this->_format];

        //获取数据库信息
        $dbInfo = $this->_getDbInfo();
        $ServerInfo = array_merge($ServerInfo, $dbInfo);

		//网站统计信息
		//$WebDir = realpath('../'.__ROOT__);
		//$ServerInfo['WebSize'] = byte_format( getdirsize( $WebDir ) );

		return $ServerInfo;
	}

    /**
     * 获取数据库信息
     */
	private function _getDbInfo(){
        $info = array();
        //获取mysql版本号
        $m = M('Config');
        $dbName = C('DB_NAME');
        $dbType = C('DB_TYPE');
        if('kingbase'==$dbType){  //人大金仓数据库(MYSQL兼容模式)
            $data = $m->query('Select version() as version,now() as now');
            $info['MySqlTime'] = isset($data[0]['now']) ? $data[0]['now'] : '未知';  //获取MySql时间
            //人大金仓返回：KingbaseES V009R001C002B0014 on x64, compiled by Visual C++ build 1800, 64-bit
            $info['MySqlVersion'] = isset($data[0]['version']) ? $data[0]['version'] : ''; //获取MySql版本号
            $info['MySqlTime'] = substr($info['MySqlTime'],0, 19);  //2024-11-15 10:40:49.961069
            $temp = explode(' ', $info['MySqlVersion']);
            $info['MySqlVersion'] = "{$temp[0]} {$temp[1]}";
            //编码
            $temp = $m->query("SELECT sys_encoding_to_char(ENCODING) as encoding FROM sys_database WHERE DATNAME = '{$dbName}'");
            $info['CharacterSetDatabase'] = isset($temp[0]['encoding']) ? $temp[0]['encoding'] : ''; //获取MySql字符集

            //获取大小
            $temp = $m->query("SELECT pg_size_pretty(pg_database_size(datname)) AS db_size FROM pg_database WHERE datname='{$dbName}'");
            $info['DbSize'] = isset($temp[0]['db_size']) ? $temp[0]['db_size'] : '未知';
        }elseif('dm' == $dbType){ //达梦数据库(MYSQL兼容模式)
            //不能使用now()会返回时区
            $data = $m->query('Select SYSDATE AS now,  SF_GET_UNICODE_FLAG() as encode');
            $info['MySqlTime'] = isset($data[0]['now']) ? $data[0]['now'] : '未知';
            $map = array('0'=>'GBK18030', '1'=>'UTF-8', '2'=>'EUC-KR');
            $encode = isset($data[0]['encode']) ? $data[0]['encode'] : '';
            $info['CharacterSetDatabase'] = isset($map[$encode]) ? $map[$encode] : '未知';

            $data = $m->query('select *  from v$version limit 1');
            $info['MySqlVersion'] = isset($data[0]['BANNER']) ? $data[0]['BANNER'] : '';
        }else{ //mysql
            $data = $m->query('Select version() as version,now() as now');
            $info['MySqlTime'] = isset($data[0]['now']) ? $data[0]['now'] : '未知';  //获取MySql时间
            //人大金仓返回：KingbaseES V009R001C002B0014 on x64, compiled by Visual C++ build 1800, 64-bit
            $info['MySqlVersion'] = isset($data[0]['version']) ? $data[0]['version'] : ''; //获取MySql版本号

            $data = $m->query("show variables like 'character_set_database'");
            $info['CharacterSetDatabase'] = isset($data[0]['Value']) ? $data[0]['Value'] : ''; //获取MySql字符集

            //获取数据库大小
            $data = $m->query('SHOW TABLE STATUS FROM '.C('DB_NAME') );
            $DbSize = 0;
            foreach($data as $value) {
                $DbSize += ($value["Data_length"] + $value["Index_length"]);
            }
            $info['DbSize'] = byte_format($DbSize);
            unset($data);
        }
        return $info;
    }

    /**
     * 判断数据库是否是只读
     */
	function isDbReadonly(){
	    //如果是只读，凡是写数据库都会报错
	    try{
	        //如果是只读，设置会报错
            $m = D('Admin/Config');
            $result = $m->where('ConfigID=1')->setField('ConfigDescription', time());
            return $result>0 ? 0 : 1;
        }catch(Exception $e){
	        return 1;
        }
    }
}