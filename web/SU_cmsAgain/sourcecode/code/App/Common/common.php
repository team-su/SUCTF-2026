<?php
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