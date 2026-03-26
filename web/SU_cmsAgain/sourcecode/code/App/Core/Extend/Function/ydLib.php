<?php
if (!defined('APP_NAME')) exit();
/**
 * 友好的时间显示
 *
 * @param int    $sTime 待显示的时间
 * @param string $type  类型. normal | mohu | full | ymd | other
 * @return string
 */
function yd_friend_date($sTime, $type = 'normal') {
	if (!$sTime) return '';
	//sTime=源时间，cTime=当前时间，dTime=时间差
	$cTime        =    time();
	$dTime        =    $cTime - $sTime;
	if( $dTime < 0 ){
		return "<span style='color:#60F;font-weight:bold;'>".date("Y-m-d H:i",$sTime)."</span>";
	}
	$dDay        =    intval(date("z",$cTime)) - intval(date("z",$sTime));
	//$dDay        =    intval($dTime/3600/24);
	$dYear        =    intval(date("Y",$cTime)) - intval(date("Y",$sTime));
	//normal：n秒前，n分钟前，n小时前，日期
	if($type=='normal'){
		if( $dTime == 0 ){
			return "<span class='now-color'>刚刚</span>";
		}elseif( $dTime < 60 ){
			return "<span class='second-color'>".$dTime.'秒前</span>';
		}elseif( $dTime < 3600 ){
			return "<span class='minute-color'>".intval($dTime/60).'分钟前</span>';
			//今天的数据.年份相同.日期相同.
		}elseif( $dYear==0 && $dDay == 0  ){
			//return intval($dTime/3600)."小时前";
			return "<span class='day-color'>今天".date('H:i',$sTime).'</span>';
		}elseif($dYear==0){
			return "<span class='month-day-color'>".date("m月d日 H:i",$sTime).'</span>';
		}else{
			return "<span class='year-month-day-color'>".date("Y-m-d H:i",$sTime).'</span>';
		}
	}elseif($type=='mohu'){
		if( $dTime < 60 ){
			return $dTime."秒前";
		}elseif( $dTime < 3600 ){
			return intval($dTime/60)."分钟前";
		}elseif( $dTime >= 3600 && $dDay == 0  ){
			return intval($dTime/3600)."小时前";
		}elseif( $dDay > 0 && $dDay<=7 ){
			return intval($dDay)."天前";
		}elseif( $dDay > 7 &&  $dDay <= 30 ){
			return intval($dDay/7) . '周前';
		}elseif( $dDay > 30 ){
			return intval($dDay/30) . '个月前';
		}
	//full: Y-m-d , H:i:s
	}elseif($type=='full'){
		return date("Y-m-d , H:i:s",$sTime);
	}elseif($type=='ymd'){
		return date("Y-m-d",$sTime);
	}else{
		if( $dTime < 60 ){
			return $dTime."秒前";
		}elseif( $dTime < 3600 ){
			return intval($dTime/60)."分钟前";
		}elseif( $dTime >= 3600 && $dDay == 0  ){
			return intval($dTime/3600)."小时前";
		}elseif($dYear==0){
			return date("Y-m-d H:i:s",$sTime);
		}else{
			return date("Y-m-d H:i:s",$sTime);
		}
	}
}

/**
 *
 * Description 友好显示时间
 * @param int $time 要格式化的时间戳 默认为当前时间
 * @return string $text 格式化后的时间戳
 */
/*
function yd_friend_date($time = NULL) {
	$text = '';
	$time = ($time === NULL || $time > time() ) ? time() : intval($time);
	$t = time() - $time; //时间差 （秒）
	if ($t == 0)
		$text = '刚刚';
	elseif ($t < 60)
		$text = $t . '秒前'; // 一分钟内
	elseif ($t < 60 * 60)
		$text = floor($t / 60) . '分钟前'; //一小时内
	elseif ($t < 60 * 60 * 24)
		$text = floor($t / (60 * 60)) . '小时前'; // 一天内
	elseif ($t < 60 * 60 * 24 * 2)
		$text = '昨天 ' . date('H:i', $time); //两天内  存在bug
	elseif ($t < 60 * 60 * 24 * 3)
		$text = '前天 ' . date('H:i', $time); // 三天内
	elseif ($t < 60 * 60 * 24 * 30)
		$text = date('m月d日 H:i', $time); //一个月内
	elseif ($t < 60 * 60 * 24 * 365)
		$text = date('m月d日', $time); //一年内
	else
		$text = date('Y年m月d日', $time); //一年以前
	return $text;
}
*/

/**
 * 根据文件后缀获取mime类型
 * @param  string $ext 文件后缀
 * @return string      mime类型
 */
function yd_mime_type($ext){
	static $mime_types = array (
			'apk'     => 'application/vnd.android.package-archive',
			'3gp'     => 'video/3gpp',
			'ai'      => 'application/postscript',
			'aif'     => 'audio/x-aiff',
			'aifc'    => 'audio/x-aiff',
			'aiff'    => 'audio/x-aiff',
			'asc'     => 'text/plain',
			'atom'    => 'application/atom+xml',
			'au'      => 'audio/basic',
			'avi'     => 'video/x-msvideo',
			'bcpio'   => 'application/x-bcpio',
			'bin'     => 'application/octet-stream',
			'bmp'     => 'image/bmp',
			'cdf'     => 'application/x-netcdf',
			'cgm'     => 'image/cgm',
			'class'   => 'application/octet-stream',
			'cpio'    => 'application/x-cpio',
			'cpt'     => 'application/mac-compactpro',
			'csh'     => 'application/x-csh',
			'css'     => 'text/css',
			'dcr'     => 'application/x-director',
			'dif'     => 'video/x-dv',
			'dir'     => 'application/x-director',
			'djv'     => 'image/vnd.djvu',
			'djvu'    => 'image/vnd.djvu',
			'dll'     => 'application/octet-stream',
			'dmg'     => 'application/octet-stream',
			'dms'     => 'application/octet-stream',
			'doc'     => 'application/msword',
			'dtd'     => 'application/xml-dtd',
			'dv'      => 'video/x-dv',
			'dvi'     => 'application/x-dvi',
			'dxr'     => 'application/x-director',
			'eps'     => 'application/postscript',
			'etx'     => 'text/x-setext',
			'exe'     => 'application/octet-stream',
			'ez'      => 'application/andrew-inset',
			'flv'     => 'video/x-flv',
			'gif'     => 'image/gif',
			'gram'    => 'application/srgs',
			'grxml'   => 'application/srgs+xml',
			'gtar'    => 'application/x-gtar',
			'gz'      => 'application/x-gzip',
			'hdf'     => 'application/x-hdf',
			'hqx'     => 'application/mac-binhex40',
			'htm'     => 'text/html',
			'html'    => 'text/html',
			'ice'     => 'x-conference/x-cooltalk',
			'ico'     => 'image/x-icon',
			'ics'     => 'text/calendar',
			'ief'     => 'image/ief',
			'ifb'     => 'text/calendar',
			'iges'    => 'model/iges',
			'igs'     => 'model/iges',
			'jnlp'    => 'application/x-java-jnlp-file',
			'jp2'     => 'image/jp2',
			'jpe'     => 'image/jpeg',
			'jpeg'    => 'image/jpeg',
			'jpg'     => 'image/jpeg',
			'js'      => 'application/x-javascript',
			'kar'     => 'audio/midi',
			'latex'   => 'application/x-latex',
			'lha'     => 'application/octet-stream',
			'lzh'     => 'application/octet-stream',
			'm3u'     => 'audio/x-mpegurl',
			'm4a'     => 'audio/mp4a-latm',
			'm4p'     => 'audio/mp4a-latm',
			'm4u'     => 'video/vnd.mpegurl',
			'm4v'     => 'video/x-m4v',
			'mac'     => 'image/x-macpaint',
			'man'     => 'application/x-troff-man',
			'mathml'  => 'application/mathml+xml',
			'me'      => 'application/x-troff-me',
			'mesh'    => 'model/mesh',
			'mid'     => 'audio/midi',
			'midi'    => 'audio/midi',
			'mif'     => 'application/vnd.mif',
			'mov'     => 'video/quicktime',
			'movie'   => 'video/x-sgi-movie',
			'mp2'     => 'audio/mpeg',
			'mp3'     => 'audio/mpeg',
			'mp4'     => 'video/mp4',
			'mpe'     => 'video/mpeg',
			'mpeg'    => 'video/mpeg',
			'mpg'     => 'video/mpeg',
			'mpga'    => 'audio/mpeg',
			'ms'      => 'application/x-troff-ms',
			'msh'     => 'model/mesh',
			'mxu'     => 'video/vnd.mpegurl',
			'nc'      => 'application/x-netcdf',
			'oda'     => 'application/oda',
			'ogg'     => 'application/ogg',
			'ogv'     => 'video/ogv',
			'pbm'     => 'image/x-portable-bitmap',
			'pct'     => 'image/pict',
			'pdb'     => 'chemical/x-pdb',
			'pdf'     => 'application/pdf',
			'pgm'     => 'image/x-portable-graymap',
			'pgn'     => 'application/x-chess-pgn',
			'pic'     => 'image/pict',
			'pict'    => 'image/pict',
			'png'     => 'image/png',
			'pnm'     => 'image/x-portable-anymap',
			'pnt'     => 'image/x-macpaint',
			'pntg'    => 'image/x-macpaint',
			'ppm'     => 'image/x-portable-pixmap',
			'ppt'     => 'application/vnd.ms-powerpoint',
			'ps'      => 'application/postscript',
			'qt'      => 'video/quicktime',
			'qti'     => 'image/x-quicktime',
			'qtif'    => 'image/x-quicktime',
			'ra'      => 'audio/x-pn-realaudio',
			'ram'     => 'audio/x-pn-realaudio',
			'ras'     => 'image/x-cmu-raster',
			'rdf'     => 'application/rdf+xml',
			'rgb'     => 'image/x-rgb',
			'rm'      => 'application/vnd.rn-realmedia',
			'roff'    => 'application/x-troff',
			'rtf'     => 'text/rtf',
			'rtx'     => 'text/richtext',
			'sgm'     => 'text/sgml',
			'sgml'    => 'text/sgml',
			'sh'      => 'application/x-sh',
			'shar'    => 'application/x-shar',
			'silo'    => 'model/mesh',
			'sit'     => 'application/x-stuffit',
			'skd'     => 'application/x-koan',
			'skm'     => 'application/x-koan',
			'skp'     => 'application/x-koan',
			'skt'     => 'application/x-koan',
			'smi'     => 'application/smil',
			'smil'    => 'application/smil',
			'snd'     => 'audio/basic',
			'so'      => 'application/octet-stream',
			'spl'     => 'application/x-futuresplash',
			'src'     => 'application/x-wais-source',
			'sv4cpio' => 'application/x-sv4cpio',
			'sv4crc'  => 'application/x-sv4crc',
			'svg'     => 'image/svg+xml',
			'swf'     => 'application/x-shockwave-flash',
			't'       => 'application/x-troff',
			'tar'     => 'application/x-tar',
			'tcl'     => 'application/x-tcl',
			'tex'     => 'application/x-tex',
			'texi'    => 'application/x-texinfo',
			'texinfo' => 'application/x-texinfo',
			'tif'     => 'image/tiff',
			'tiff'    => 'image/tiff',
			'tr'      => 'application/x-troff',
			'tsv'     => 'text/tab-separated-values',
			'txt'     => 'text/plain',
			'ustar'   => 'application/x-ustar',
			'vcd'     => 'application/x-cdlink',
			'vrml'    => 'model/vrml',
			'vxml'    => 'application/voicexml+xml',
			'wav'     => 'audio/x-wav',
			'wbmp'    => 'image/vnd.wap.wbmp',
			'wbxml'   => 'application/vnd.wap.wbxml',
			'webm'    => 'video/webm',
			'wml'     => 'text/vnd.wap.wml',
			'wmlc'    => 'application/vnd.wap.wmlc',
			'wmls'    => 'text/vnd.wap.wmlscript',
			'wmlsc'   => 'application/vnd.wap.wmlscriptc',
			'wmv'     => 'video/x-ms-wmv',
			'wrl'     => 'model/vrml',
			'xbm'     => 'image/x-xbitmap',
			'xht'     => 'application/xhtml+xml',
			'xhtml'   => 'application/xhtml+xml',
			'xls'     => 'application/vnd.ms-excel',
			'xml'     => 'application/xml',
			'xpm'     => 'image/x-xpixmap',
			'xsl'     => 'application/xml',
			'xslt'    => 'application/xslt+xml',
			'xul'     => 'application/vnd.mozilla.xul+xml',
			'xwd'     => 'image/x-xwindowdump',
			'xyz'     => 'chemical/x-xyz',
			'zip'     => 'application/zip'
	);
	return isset($mime_types[$ext]) ? $mime_types[$ext] : 'application/octet-stream';
}

/**
 * 彩虹字符效果
 * @param string $str
 */
function yd_color_text($str){
	$len        = mb_strlen($str);
	$colorTxt   = '';
	for($i=0; $i<$len; $i++) {
		$colorTxt .=  '<span style="color:'.yd_rand_color().'">'.mb_substr($str,$i,1,'utf-8').'</span>';
	}
	return $colorTxt;
}

/**
 * 获取随机颜色，格式：#A33534;
 * @return string  随机颜色字符串
 */
function yd_rand_color(){
	return '#'.sprintf("%02X",mt_rand(0,255)).sprintf("%02X",mt_rand(0,255)).sprintf("%02X",mt_rand(0,255));
}

/**
 * Unix 时间戳转化为日期，例如：调用：toDate(time())  
 * @param int $time Unix 时间戳(即自从 Unix 纪元（格林威治时间 1970 年 1 月 1 日 00:00:00）到当前时间的秒数。 
 * @param string $format
 * @return string
 */
function yd_to_date($time, $format = 'Y-m-d H:i:s') {
	if (empty ( $time )) {
		return '';
	}
	$format = str_replace ( '#', ':', $format );
	return date ($format, $time );
}

/**
 * 返回文件扩展名（不带点号）
 * 能处理以下三种特殊情况:
 (1)没有文件扩展名 
 (2)路径中包含了字符.，如/home/test.d/test.txt 
 (3)路径中包含了字符.，但文件没有扩展名。如/home/test.d/test 
 * @param string $file
 */
function yd_file_ext($file) {
	//return strtolower(trim(substr(strrchr($filename, '.'), 1, 10)));
	return pathinfo($file, PATHINFO_EXTENSION);
}

/**
 * 获取规范化路径
 * @param string $path
 * @return string
 */
function yd_dir_path($path) {
	$path = str_replace('\\', '/', $path);
	if(substr($path, -1) != '/') $path = $path.'/';
	return $path;
}

/**
 * 获取目录下所有文件列表，支持子目录
 * @param unknown_type $path
 * @param unknown_type $exts
 * @param unknown_type $list
 */
function yd_dir_list($path, $exts = '', $list= array()) {
	$path = yd_dir_path($path);
	$files = glob($path.'*');
	foreach($files as $v) {
		$fileext = yd_file_ext($v);
		if (!$exts || preg_match("/\.($exts)/i", $v)) {
			$list[] = $v;
			if (is_dir($v)) {
				$list = yd_dir_list($v, $exts, $list);
			}
		}
	}
	return $list;
}

/**
 * 关键词生成链接
 * @param txt $string 原字符串
 * @param $links array 关键词链接数组，格式：array(0=>array('关键词'=>'http://www.csyoudian.com'));
 * @param replacenum $int 替换次数 -1表示替换所有
 * @return string 返回字符串
 */
function yd_key_links($txt, $links, $replacenum = -1) {
	if ($links) {
		$pattern = $replace = array();
		foreach ($links as $v) {
			//wordpress关键链接插件：$regEx = '\'(?!((<.*?)|(<a.*?)))('. $keyword . ')(?!(([^<>]*?)>)|([^>]*?</a>))\'s' . $case;
			//$pattern[] = '/(?!(<a.*?))' . preg_quote($v[0], '/') . '(?!([^>]*?<\/a>))/s';
			//会替换<scirpt>回车</script>
			//$pattern[] = '/(?!((<.*?)|(<a.*?)))' . preg_quote($v[0], '/') . '(?!(([^<>]*?)>)|([^>]*?<\/a>))/s'; 
			$pattern[] = '/(?!((<.*?)|(<a.*?)|<script.*?))' . preg_quote($v[0], '/') . '(?!(([^<>]*?)>)|([^>]*?<\/a>)|([^>]*?<\/script>))/s';
			
			//$pattern[] = '/(?!(<a.*?))' . preg_quote($v[0], '/') . '(?!.*<\/a>)/s';  //原始
			//$replace[] = '<a href="' . $v[1] . '" target="_blank"><b>' . $v[0] . '</b></a>';
			$replace[] = "<a href=\"{$v[1]}\" target=\"_blank\" class=\"autolink\">{$v[0]}</a>";
		}
		$txt = preg_replace($pattern, $replace, $txt, $replacenum);
	}
	return $txt;
}

/**
 * 自动将文本链接转化为超链接
 * @param unknown_type $foo
 */
function yd_auto_link($str){
	$str = preg_replace('/(((f|ht){1}tp://)[-a-zA-Z0-9@:%_\+.~#?&//=]+)/i', '<a href="\1" target="_blank">\1</a>', $str);
	if( stripos($str, "http") === FALSE ){
		$str = preg_replace('/(www.[-a-zA-Z0-9@:%_\+.~#?&//=]+)/i', '<a href="http://\1" target="_blank">\1</a>', $str);
	}else{
		$str = preg_replace('/([[:space:]()[{}])(www.[-a-zA-Z0-9@:%_\+.~#?&//=]+)/i', '\1<a href="http://\2" target="_blank">\2</a>', $str);
	}
	return $str;
}

/**
 * 转化为QQ图标
 * @param string $qq
 * @param string $style qq样式, 取值范围[41,50]
 * @param string $tip 提示信息
 */
function yd_qq_face($qq, $style = 41, $tip = ''){
	$qqText = "<a target=\"_blank\" href=\"http://wpa.qq.com/msgrd?v=3&uin=$qq&site=qq&menu=yes\"><img border=\"0\" src=\"http://wpa.qq.com/pa?p=2:$qq:$style\" alt=\"$tip\" title=\"$tip\"></a>";
	return $qqText;
}

/**
 * 转化为淘宝旺旺图标
 * @param string $number
 * @param string $tip 提示信息
 */
function yd_taobao_face($number, $tip = ''){
		$str = "<a target='_blank' href='http://amos1.taobao.com/msg.ww?v=2&uid=$number&s=1' >
		<img border='0' src='http://amos1.taobao.com/online.ww?v=2&uid=$number&s=1' alt='$tip' title='$tip' />
		</a>";
		return $str;
}

/**
 * 转化为阿里旺旺图标
 * @param string $number
 * @param string $tip 提示信息
 */
function yd_ali_face($number, $tip = ''){
	$str = "<a target='_blank' href='http://amos.im.alisoft.com/msg.aw?v=2&amp;uid=$number&amp;site=cnalichn&amp;s=4'>
	           <img alt='$tip' title='$tip' border='0' src='http://amos.im.alisoft.com/online.aw?v=2&amp;uid=$number&amp;site=cnalichn&amp;s=4' />
	           </a>";
	return $str;
}

/**
 * 转化为国际版阿里旺旺图标
 * @param string $number
 * @param string $tip 提示信息
 */
function yd_interali_face($number, $tip = ''){
	$str = "<a target='_blank' href='http://amos.alicdn.com/msg.aw?v=2&amp;uid={$number}&amp;site=enaliint&amp;s=24&amp;charset=UTF-8' ";
	$str .= "  style='text-align:center;' data-uid='{$number}'>";
	$str .= "<img style='border:none;vertical-align:middle;margin-right:5px;' ";
	$str .= " src='http://amos.alicdn.com/online.aw?v=2&amp;uid={$number}&amp;site=enaliint&amp;s=22&amp;charset=UTF-8'>";
	$str .= "{$number}</a>";
	return $str;
}

/**
 * 转化为MSN图标
 * @param string $number
 * @param string $tip 提示信息
 */
function yd_msn_face($number, $tip = ''){
	$msn = __ROOT__."/Public/Images/online/msn.gif";
	$str = "<a target=blank href='msnim:chat?contact=$number&Site=$number'>
	<img src='$msn' alt='$tip' title='$tip'/>
	</a>";
	return $str;
}

/**
 * 转化为Skype图标
 * @param string $number
 * @param string $tip 提示信息
 */
function yd_skype_face($number, $tip = ''){
	$str = "<a target='blank' href='callto://$number'>
	<img border='0' src='http://mystatus.skype.com/smallclassic/$number' alt='$tip' title='$tip'/>
	</a>";
	return $str;
}

/**
 * 根据ip地址查询区域位置
 * @param string $ip
 * @return array 返回区域位置数组
 * 如：http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=115.156.238.114
 * 将返回数组，country:中国,province:湖北,city:武汉,district:,isp:教育网,type:学校,desc:华中科技大学东校区
 */
function yd_ip2location($ip, $type=0){
	static $_loc = array();
	$k = md5($ip);
	if( isset($_loc[$k]) ){
		return $_loc[$k];
	}
    $area = null;
	switch($type){
		case 1:  //使用本地库
			break;
		case 0:  //使用新浪接口(默认)
		default: 
			$api = "http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=$ip";
			$content = file_get_contents($api);
			$data = json_decode($content, true);
			if( !isset($data['ret'] ) || $data['ret'] < 0 ) return false;
			$area = array(
					'Country'=>$data['country'],
					'Province'=>$data['province'],
					'City'=>$data['city'],
					'District'=>$data['district'],
					'ISP'=>$data['isp'],
					'Type'=>$data['type'],
					'Description'=>$data['desc']
			);
	}
	$_loc[$k] = $area;
	return $area;
}

//拼音获取函数===========================================================
/**
 * 返回指定中文的拼音
 * @param string $str
 * @param bool $first 当为true时返回中文拼音首字母
 * @param string $code 编码
 * @param int $flag 1：小写，2：大写，3：首字母大写
 * @return array 返回汉字对应的拼音
 */
function yd_pinyin($str, $first =true, $code='UTF8', $flag=1){
	if( is_numeric($str) ) return $str;
	$_Data = array ( 'zuo' => '-10254', 'zun' => '-10256', 'zui' => '-10260', 'zuan' => '-10262', 'zu' => '-10270', 'zou' => '-10274', 'zong' => '-10281', 'zi' => '-10296', 'zhuo' => '-10307', 'zhun' => '-10309', 'zhui' => '-10315', 'zhuang' => '-10322', 'zhuan' => '-10328', 'zhuai' => '-10329', 'zhua' => '-10331', 'zhu' => '-10519', 'zhou' => '-10533', 'zhong' => '-10544', 'zhi' => '-10587', 'zheng' => '-10764', 'zhen' => '-10780', 'zhe' => '-10790', 'zhao' => '-10800', 'zhang' => '-10815', 'zhan' => '-10832', 'zhai' => '-10838', 'zha' => '-11014', 'zeng' => '-11018', 'zen' => '-11019', 'zei' => '-11020', 'ze' => '-11024', 'zao' => '-11038', 'zang' => '-11041', 'zan' => '-11045', 'zai' => '-11052', 'za' => '-11055', 'yun' => '-11067', 'yue' => '-11077', 'yuan' => '-11097', 'yu' => '-11303', 'you' => '-11324', 'yong' => '-11339', 'yo' => '-11340', 'ying' => '-11358', 'yin' => '-11536', 'yi' => '-11589', 'ye' => '-11604', 'yao' => '-11781', 'yang' => '-11798', 'yan' => '-11831', 'ya' => '-11847', 'xun' => '-11861', 'xue' => '-11867', 'xuan' => '-12039', 'xu' => '-12058', 'xiu' => '-12067', 'xiong' => '-12074', 'xing' => '-12089', 'xin' => '-12099', 'xie' => '-12120', 'xiao' => '-12300', 'xiang' => '-12320', 'xian' => '-12346', 'xia' => '-12359', 'xi' => '-12556', 'wu' => '-12585', 'wo' => '-12594', 'weng' => '-12597', 'wen' => '-12607', 'wei' => '-12802', 'wang' => '-12812', 'wan' => '-12829', 'wai' => '-12831', 'wa' => '-12838', 'tuo' => '-12849', 'tun' => '-12852', 'tui' => '-12858', 'tuan' => '-12860', 'tu' => '-12871', 'tou' => '-12875', 'tong' => '-12888', 'ting' => '-13060', 'tie' => '-13063', 'tiao' => '-13068', 'tian' => '-13076', 'ti' => '-13091', 'teng' => '-13095', 'te' => '-13096', 'tao' => '-13107', 'tang' => '-13120', 'tan' => '-13138', 'tai' => '-13147', 'ta' => '-13318', 'suo' => '-13326', 'sun' => '-13329', 'sui' => '-13340', 'suan' => '-13343', 'su' => '-13356', 'sou' => '-13359', 'song' => '-13367', 'si' => '-13383', 'shuo' => '-13387', 'shun' => '-13391', 'shui' => '-13395', 'shuang' => '-13398', 'shuan' => '-13400', 'shuai' => '-13404', 'shua' => '-13406', 'shu' => '-13601', 'shou' => '-13611', 'shi' => '-13658', 'sheng' => '-13831', 'shen' => '-13847', 'she' => '-13859', 'shao' => '-13870', 'shang' => '-13878', 'shan' => '-13894', 'shai' => '-13896', 'sha' => '-13905', 'seng' => '-13906', 'sen' => '-13907', 'se' => '-13910', 'sao' => '-13914', 'sang' => '-13917', 'san' => '-14083', 'sai' => '-14087', 'sa' => '-14090', 'ruo' => '-14092', 'run' => '-14094', 'rui' => '-14097', 'ruan' => '-14099', 'ru' => '-14109', 'rou' => '-14112', 'rong' => '-14122', 'ri' => '-14123', 'reng' => '-14125', 'ren' => '-14135', 're' => '-14137', 'rao' => '-14140', 'rang' => '-14145', 'ran' => '-14149', 'qun' => '-14151', 'que' => '-14159', 'quan' => '-14170', 'qu' => '-14345', 'qiu' => '-14353', 'qiong' => '-14355', 'qing' => '-14368', 'qin' => '-14379', 'qie' => '-14384', 'qiao' => '-14399', 'qiang' => '-14407', 'qian' => '-14429', 'qia' => '-14594', 'qi' => '-14630', 'pu' => '-14645', 'po' => '-14654', 'ping' => '-14663', 'pin' => '-14668', 'pie' => '-14670', 'piao' => '-14674', 'pian' => '-14678', 'pi' => '-14857', 'peng' => '-14871', 'pen' => '-14873', 'pei' => '-14882', 'pao' => '-14889', 'pang' => '-14894', 'pan' => '-14902', 'pai' => '-14908', 'pa' => '-14914', 'ou' => '-14921', 'o' => '-14922', 'nuo' => '-14926', 'nue' => '-14928', 'nuan' => '-14929', 'nv' => '-14930', 'nu' => '-14933', 'nong' => '-14937', 'niu' => '-14941', 'ning' => '-15109', 'nin' => '-15110', 'nie' => '-15117', 'niao' => '-15119', 'niang' => '-15121', 'nian' => '-15128', 'ni' => '-15139', 'neng' => '-15140', 'nen' => '-15141', 'nei' => '-15143', 'ne' => '-15144', 'nao' => '-15149', 'nang' => '-15150', 'nan' => '-15153', 'nai' => '-15158', 'na' => '-15165', 'mu' => '-15180', 'mou' => '-15183', 'mo' => '-15362', 'miu' => '-15363', 'ming' => '-15369', 'min' => '-15375', 'mie' => '-15377', 'miao' => '-15385', 'mian' => '-15394', 'mi' => '-15408', 'meng' => '-15416', 'men' => '-15419', 'mei' => '-15435', 'me' => '-15436', 'mao' => '-15448', 'mang' => '-15454', 'man' => '-15625', 'mai' => '-15631', 'ma' => '-15640', 'luo' => '-15652', 'lun' => '-15659', 'lue' => '-15661', 'luan' => '-15667', 'lv' => '-15681', 'lu' => '-15701', 'lou' => '-15707', 'long' => '-15878', 'liu' => '-15889', 'ling' => '-15903', 'lin' => '-15915', 'lie' => '-15920', 'liao' => '-15933', 'liang' => '-15944', 'lian' => '-15958', 'lia' => '-15959', 'li' => '-16155', 'leng' => '-16158', 'lei' => '-16169', 'le' => '-16171', 'lao' => '-16180', 'lang' => '-16187', 'lan' => '-16202', 'lai' => '-16205', 'la' => '-16212', 'kuo' => '-16216', 'kun' => '-16220', 'kui' => '-16393', 'kuang' => '-16401', 'kuan' => '-16403', 'kuai' => '-16407', 'kua' => '-16412', 'ku' => '-16419', 'kou' => '-16423', 'kong' => '-16427', 'keng' => '-16429', 'ken' => '-16433', 'ke' => '-16448', 'kao' => '-16452', 'kang' => '-16459', 'kan' => '-16465', 'kai' => '-16470', 'ka' => '-16474', 'jun' => '-16647', 'jue' => '-16657', 'juan' => '-16664', 'ju' => '-16689', 'jiu' => '-16706', 'jiong' => '-16708', 'jing' => '-16733', 'jin' => '-16915', 'jie' => '-16942', 'jiao' => '-16970', 'jiang' => '-16983', 'jian' => '-17185', 'jia' => '-17202', 'ji' => '-17417', 'huo' => '-17427', 'hun' => '-17433', 'hui' => '-17454', 'huang' => '-17468', 'huan' => '-17482', 'huai' => '-17487', 'hua' => '-17496', 'hu' => '-17676', 'hou' => '-17683', 'hong' => '-17692', 'heng' => '-17697', 'hen' => '-17701', 'hei' => '-17703', 'he' => '-17721', 'hao' => '-17730', 'hang' => '-17733', 'han' => '-17752', 'hai' => '-17759', 'ha' => '-17922', 'guo' => '-17928', 'gun' => '-17931', 'gui' => '-17947', 'guang' => '-17950', 'guan' => '-17961', 'guai' => '-17964', 'gua' => '-17970', 'gu' => '-17988', 'gou' => '-17997', 'gong' => '-18012', 'geng' => '-18181', 'gen' => '-18183', 'gei' => '-18184', 'ge' => '-18201', 'gao' => '-18211', 'gang' => '-18220', 'gan' => '-18231', 'gai' => '-18237', 'ga' => '-18239', 'fu' => '-18446', 'fou' => '-18447', 'fo' => '-18448', 'feng' => '-18463', 'fen' => '-18478', 'fei' => '-18490', 'fang' => '-18501', 'fan' => '-18518', 'fa' => '-18526', 'er' => '-18696', 'en' => '-18697', 'e' => '-18710', 'duo' => '-18722', 'dun' => '-18731', 'dui' => '-18735', 'duan' => '-18741', 'du' => '-18756', 'dou' => '-18763', 'dong' => '-18773', 'diu' => '-18774', 'ding' => '-18783', 'die' => '-18952', 'diao' => '-18961', 'dian' => '-18977', 'di' => '-18996', 'deng' => '-19003', 'de' => '-19006', 'dao' => '-19018', 'dang' => '-19023', 'dan' => '-19038', 'dai' => '-19212', 'da' => '-19218', 'cuo' => '-19224', 'cun' => '-19227', 'cui' => '-19235', 'cuan' => '-19238', 'cu' => '-19242', 'cou' => '-19243', 'cong' => '-19249', 'ci' => '-19261', 'chuo' => '-19263', 'chun' => '-19270', 'chui' => '-19275', 'chuang' => '-19281', 'chuan' => '-19288', 'chuai' => '-19289', 'chu' => '-19467', 'chou' => '-19479', 'chong' => '-19484', 'chi' => '-19500', 'cheng' => '-19515', 'chen' => '-19525', 'che' => '-19531', 'chao' => '-19540', 'chang' => '-19715', 'chan' => '-19725', 'chai' => '-19728', 'cha' => '-19739', 'ceng' => '-19741', 'ce' => '-19746', 'cao' => '-19751', 'cang' => '-19756', 'can' => '-19763', 'cai' => '-19774', 'ca' => '-19775', 'bu' => '-19784', 'bo' => '-19805', 'bing' => '-19976', 'bin' => '-19982', 'bie' => '-19986', 'biao' => '-19990', 'bian' => '-20002', 'bi' => '-20026', 'beng' => '-20032', 'ben' => '-20036', 'bei' => '-20051', 'bao' => '-20230', 'bang' => '-20242', 'ban' => '-20257', 'bai' => '-20265', 'ba' => '-20283', 'ao' => '-20292', 'ang' => '-20295', 'an' => '-20304', 'ai' => '-20317', 'a' => '-20319', );
	if( strtolower($code) != 'gb2312') {
	    $str = _U2_Utf8_Gb($str);
    }
	$_Res = '';
	for($i=0; $i<strlen($str); $i++) {
		$_P = ord(substr($str, $i, 1));
		if($_P>160) {
			$_Q = ord(substr($str, ++$i, 1)); $_P = $_P*256 + $_Q - 65536;
		}
		$py = _Pinyin($_P, $_Data);
		switch($flag){
			case 2:
				$py = strtoupper($py);
				break;
			case 3:
				$py = ucfirst($py);
				break;
			case 1:
			default:
				$py = strtolower($py);
		}
		if(!empty($py)){
			$_Res .= $first ? $py[0] : $py;
		}
	}
	$py = preg_replace("/[^a-zA-Z0-9]*/", '', $_Res);
	return $py;
}

function _Pinyin($_Num, $_Data){
	if($_Num>0 && $_Num<160 ){
		return chr($_Num);
	}elseif($_Num<-20319 || $_Num>-10247){
		return '';
	}else{
		foreach($_Data as $k=>$v){
			if($v<=$_Num) break;
		}
		return $k;
	}
}
function _U2_Utf8_Gb($_C){
	$_String = '';
	//php8之前的版本，在比较数字和字符串时，会把字符串转化为数字
    //php8比较数字字符串时，会按数字进行比较。不是数字字符串时，将数字转为字符串，按字符串比较。
	$newC = yd_is_php8() ? (int)$_C : $_C;  //2023-07-04
	if($newC < 0x80){
		$_String .= $_C;
	}elseif($newC < 0x800) {
		$_String .= chr(0xC0 | $_C>>6);
		$_String .= chr(0x80 | $_C & 0x3F);
	}elseif($newC < 0x10000){
		$_String .= chr(0xE0 | $_C>>12);
		$_String .= chr(0x80 | $_C>>6 & 0x3F);
		$_String .= chr(0x80 | $_C & 0x3F);
	}elseif($newC < 0x200000) {
		$_String .= chr(0xF0 | $_C>>18);
		$_String .= chr(0x80 | $_C>>12 & 0x3F);
		$_String .= chr(0x80 | $_C>>6 & 0x3F);
		$_String .= chr(0x80 | $_C & 0x3F);
	}
	//加上"//ignore"使有不能转换的字符出现时不报错
	return iconv('UTF-8', 'GB2312//ignore', $_String);
}
//================================================================

/**
 * 关闭标签
 * @param string $html
 */
function yd_close_tags($html){
	// strip fraction of open or close tag from end (e.g. if we take first x characters, we might cut off a tag at the end!)
	$html = preg_replace('/<[^>]*$/','',$html); // ending with fraction of open tag
	// put open tags into an array
	preg_match_all('#<([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
	$opentags = $result[1];
	// put all closed tags into an array
	preg_match_all('#</([a-z]+)>#iU', $html, $result);
	$closetags = $result[1];
	$len_opened = count($opentags);
	// if all tags are closed, we can return
	if (count($closetags) == $len_opened) {
		return $html;
	}
	// close tags in reverse order that they were opened
	$opentags = array_reverse($opentags);
	// self closing tags
	$sc = array('br','input','img','hr','meta','link');
	// ,'frame','iframe','param','area','base','basefont','col'
	// should not skip tags that can have content inside!
	for ($i=0; $i < $len_opened; $i++){
		$ot = strtolower($opentags[$i]);
		if (!in_array($opentags[$i], $closetags) && !in_array($ot,$sc)){
			$html .= '</'.$opentags[$i].'>';
		}else{
			unset($closetags[array_search($opentags[$i], $closetags)]);
		}
	}
	return $html;
}

/**
 * 获取图片
 * @param string $content
 * @param int $type 0:本地图片，1：远程图片，2：本地和远程图片
 */
function yd_extract_image($content,$type=0){
	if( empty($content) || strlen($content) < 10 ) return false;
	//本地图片,<img\s+?[^>] 无法匹配以下图像，因为包含>498
	//<img  onload='javascript:if(this.width>498)this.width=498;'  src="http://x.com/1.jpg"  />
	$pattern = array(
	'/<img\s+?[^>]*?src=[\'\"]?([^>]*?\.(jpg|jpeg|jpe|gif|bmp|png|tiff|tif|ico))[\'\"]?[^>]*?[\/]?>/i', //本地图片
	'/<img\s+?[^>]*?src=[\'\"]?((http|https|ftp):\/\/[^>]*?\.(jpg|jpeg|jpe|gif|bmp|png|tiff|tif|ico))[\'\"]?[^>]*?[\/]?>/i', //远程图片
	'/<img\s+?[^>]*?src=[\'\"]?([^>]*?\.(jpg|jpeg|jpe|gif|bmp|png|tiff|tif|ico))[\'\"]?[^>]*?[\/]?>/i', //以上两者
	);
	if( $type < 0 || $type > 2 ) $type = 0;
	$content = stripslashes($content);
	$num = preg_match_all($pattern[$type], $content, $matchs);
	
	if( $num > 0 ){
		if($type==0){
			$m = array();
			foreach ($matchs[1] as $v){
				if( stripos($v, 'http://') === false && stripos($v, 'https://') === false 
						&& stripos($v, 'ftp://') === false ){
					$m[] = $v;  //获取本地图片
				}
			}
			return count($m) == 0 ? false : $m;
		}
		return $matchs[1];
	}else{
		return false;
	}
}

/**
 * 获取远程图片并把它保存到本地, 确定您有把文件写入本地服务器的权限
 * @param string $url 是远程图片的完整URL地址
 * @param string $filename 是可选变量: 如果为空，本地文件名将基于时间和日期
 * @return boolean|string
 */
function yd_grab_image($url, $filename=''){
	if($url=="") return false;
	if($filename=="") {
		$ext=strrchr($url,".");
		$ext = strtolower($ext);
		$list = array('.gif', '.jpg', '.jpeg', '.jpe', '.bmp', '.png', '.tiff', '.tif', '.ico');
		if( !in_array($ext, $list)){
			return false;
		}
		$filename = date("YmdHis").rand_string(4).$ext;
	}
	//对中文文件名进行url编码
	$pos = strrpos($url,'/');
	if( $pos !== false ){
		$zh = urlencode( substr($url, $pos+1) );
		$url = substr_replace( $url, $zh, $pos+1);
	}
	$content = @file_get_contents($url);
	if( $content ){
        $uploadDir = GetUploadDir();
		$n = @file_put_contents($uploadDir.$filename,  $content );
	}
	return $filename;
	/*
	ob_start();
	readfile($url);
	$img = ob_get_contents();
	ob_end_clean();
	$size = strlen($img);
	$fp2=@fopen($filename, "a");
	fwrite($fp2,$img);
	fclose($fp2);
	return $filename;
	*/
}

/**
 * 上传信息中的图片
 * @param string $content
 */
function yd_upload_content($content, $type=1){
	$imageList = yd_extract_image($content, $type);
	if( $imageList === false ) return array(null, null, $content);
	$fileList = array();
	$uploadDir = GetUploadDir();
	$uploadDir1 = substr($uploadDir, 1);
	foreach ($imageList as $v){
		$grabFile = yd_grab_image($v);
		$fileList[] = __ROOT__.$uploadDir1.$grabFile; //上传图片
		addWater($uploadDir.$grabFile); //添加水印
	}
	$res = array($imageList, $fileList, str_ireplace($imageList, $fileList, $content) );
	return $res;
}

/**   
 * 删除非站内链接
 * @access    public
 * @param     string  $body  内容
 * @param     array  $allow_urls  允许的超链接
 * @return    string
 */
function yd_replace_link( $body, $allow_urls=array()  ){
	$host_rule = join('|', $allow_urls);
	$host_rule = preg_replace("#[\n\r]#", '', $host_rule);
	$host_rule = str_replace('.', "\\.", $host_rule);
	$host_rule = str_replace('/', "\\/", $host_rule);
	$arr = '';
	//preg_match_all("#<a([^>]*)>(.*)<\/a>#iU", $body, $arr);
	//仅匹配站外链接
	preg_match_all('#<a.+?href=["\']((?:http|https|ftp)://.+?)["\'].*?>(.+?)</a>#iU', $body, $arr);
	if( is_array($arr[0]) ){
		$rparr = array();
		$tgarr = array();
		foreach($arr[0] as $i=>$v){
			if( $host_rule != '' && preg_match('#'.$host_rule.'#i', $arr[1][$i]) ) {
				continue;
			} else {
				$rparr[] = $v;
				$tgarr[] = $arr[2][$i];
			}
		}
		if( !empty($rparr) ) {
			$body = str_replace($rparr, $tgarr, $body);
		}
	}
	$arr = $rparr = $tgarr = '';
	return $body;
}

function yd_download_csv($filename,$data) {
	header("Content-type:text/csv");
	header("Content-Disposition:attachment;filename=".$filename);
	header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
	header('Expires:0');
	header('Pragma:public');
	echo $data;
}

/**
 * 是否是合法的email地址
 */
function yd_is_email($email){
	$pattern = '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/i';
	if( preg_match($pattern,$email,$r) ){
		return true;
	}else{
		return false;
	}
}

/**
 * 是否是合法的图像格式
 */
function yd_is_image($filename){
	$size = getimagesize($filename);
	if( $size ){
		return true;
	}else{
		return false;
	}
}




/**
 * 发送get请求，并返回结果
 * @param string $url 请求url
 * @param array $data 请求关联数组
 * @param int $timeout 超时时间
 * @return boolean|mixed
 */
function yd_curl_get($url, $data=false, $timeout = 5, $options=array() ){
	//http_build_query(array('foo'=>'bar','baz'=>'boom')); 输出：foo=bar&baz=boom
    $hasHeader = !empty($options['AutoGzip']) ? true : false;
	if(!empty($data)){
		$url .= '?'.http_build_query($data);
	}
	if( function_exists('curl_init') ){
		$ch = curl_init( $url );
		//症状：php curl调用https出错 排查方法：在命令行中使用curl调用试试。
		//原因：服务器所在机房无法验证SSL证书。解决办法：跳过SSL证书检查。
		//不加上CURLOPT_SSL_VERIFYPEER，curl_exec总是返回false
		if( FALSE !== stripos($url,"https://") ){
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}

        //返回 response_header, 该选项非常重要,如果不为 true, 只会获得响应的正文
		curl_setopt($ch, CURLOPT_HEADER, $hasHeader);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		//curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:220.181.136.242', 'CLIENT-IP:220.181.136.242'));
		//CURLOPT_REFERER、CURLOPT_USERAGENT
		foreach ($options as $k=>$v){
			$k = is_numeric($k) ? $k : constant($k);
			curl_setopt($ch, $k, $v);
		}
		$res = curl_exec($ch);
		if(APP_DEBUG && empty($res)){
			$errorText = curl_error($ch);
			WriteErrLog("{$errorText},yd_curl_get curl_exec失败");
		}
        if($hasHeader){
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headerText = substr($res, 0, $headerSize);
            $res = substr($res, $headerSize); //正文内容
            //如果启用了gzip压缩就解压
            if(false!==stripos($headerText, 'gzip')){
                $res = gzdecode($res);
            }
        }
		curl_close($ch);
	}else{
		//利用了stream_context_create()设置超时时间:
		//当读取https协议时，需要服务器支持open_ssl模块
		$opts = array( 'http' => array('timeout' => $timeout, 'method'=>"GET", 'header'=>'') );
		if( $options['CURLOPT_REFERER']){
			$opts['header'] .= "Referer:".$options['CURLOPT_REFERER']."\r\n";
		}
		if( $options['CURLOPT_USERAGENT']){
			$opts['header'] .= "User-Agent:".$options['CURLOPT_USERAGENT']."\r\n";
		}
		$context = stream_context_create( $opts );
		$res = @file_get_contents( $url, false, $context );
	}
	return $res;
}

/**
 * 发送POST请求，并返回结果
 * @param string $url 请求url
 * @param mix $requestString
 * @param int $timeout  超时时间
 * @return boolean|mixed
 */
function yd_curl_post($url, $requestString, $timeout = 5, $options=array() ){
	if( $url == "" || $timeout <= 0 ){
		return false;
	}
	
	/* 无需转换，可以直接传递数组
	if( is_array($requestString) ){
		$requestString = http_build_query($requestString);
	}
	*/
	
	if( function_exists('curl_init') ){
		$ch = curl_init( $url );
		//症状：php curl调用https出错 排查方法：在命令行中使用curl调用试试。
		//原因：服务器所在机房无法验证SSL证书。解决办法：跳过SSL证书检查。
		//不加上CURLOPT_SSL_VERIFYPEER，curl_exec总是返回false
		if( FALSE !== stripos($url,"https://") ){
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString); //$requestString可以为数组
		foreach ($options as $k=>$v){
			$k = is_numeric($k) ? $k : constant($k);
			curl_setopt($ch, $k, $v);
		}
		$res = curl_exec($ch);
		if(APP_DEBUG && empty($res)){
			$errorText = curl_error($ch);
			WriteErrLog("{$errorText},yd_curl_post curl_exec失败");
		}
		curl_close($ch);
	}else{
		//利用了stream_context_create()设置超时时间:
		$opts = array( 'http' => array(
				'timeout' => $timeout, 
				'method'=>"POST",
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => $requestString
		) );
		$context = stream_context_create( $opts );
		$res = @file_get_contents( $url, false, $context );
	}
	return $res;
}

//获取 某个月的最大天数（最后一天）
function yd_month_lastday($month, $year) {
	switch ($month) {
		case 4 :
		case 6 :
		case 9 :
		case 11 :
			$days = 30;
			break;
		case 2 :
			if ($year % 4 == 0) {
				if ($year % 100 == 0) {
					$days = $year % 400 == 0 ? 29 : 28;
				} else {
					$days = 29;
				}
			} else {
				$days = 28;
			}
			break;
		default :
			$days = 31;
			break;
	}
	return $days;
}

function yd_addslashes($str){
	if (!get_magic_quotes_gpc()) {
		$result = is_array($str) ? array_map('addslashes', $str) : addslashes($str);
	}else{
		$result = $str;
	}
	return $result;
}

//移除xss代码
function yd_remove_xss($str){
	$result = is_array($str) ? array_map('remove_xss', $str) : remove_xss($str);
	return $result;
}

//防止sql注入攻击，$value：注入字段的值
function yd_filter_sql($value){
	return $value;
}

/**
 * 增强型字符串截取函数，本函数修改字uchome的getstr
 * 截取中文字符无乱码，避免截取半个中文的情况
 */
function yd_getstr($string, $length, $encoding  = 'utf-8') {
	$string = trim($string);
	if($length && strlen($string) > $length) {
		//截断字符
		$wordscut = '';
		if(strtolower($encoding) == 'utf-8') {
			//utf8编码
			$n = 0;
			$tn = 0;
			$noc = 0;
			while ($n < strlen($string)) {
				$t = ord($string[$n]);
				if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
					$tn = 1;
					$n++;
					$noc++;
				} elseif(194 <= $t && $t <= 223) {
					$tn = 2;
					$n += 2;
					$noc += 2;
				} elseif(224 <= $t && $t < 239) {
					$tn = 3;
					$n += 3;
					$noc += 2;
				} elseif(240 <= $t && $t <= 247) {
					$tn = 4;
					$n += 4;
					$noc += 2;
				} elseif(248 <= $t && $t <= 251) {
					$tn = 5;
					$n += 5;
					$noc += 2;
				} elseif($t == 252 || $t == 253) {
					$tn = 6;
					$n += 6;
					$noc += 2;
				} else {
					$n++;
				}
				if ($noc >= $length) {
					break;
				}
			}
			if ($noc > $length) {
				$n -= $tn;
			}
			$wordscut = substr($string, 0, $n);
		} else {
			for($i = 0; $i < $length - 1; $i++) {
				if(ord($string[$i]) > 127) {
					$wordscut .= $string[$i].$string[$i + 1];
					$i++;
				} else {
					$wordscut .= $string[$i];
				}
			}
		}
		$string = $wordscut;
	}
	return trim($string);
}

/**
 * 检查指定文件或目录是否可写
 * @param	string 文件全路径或目录
 * @return	bool
 */
function yd_is_writable($file){
	//is_writable在windows系统中不能准确判断文件是否可写, 如果文件只是可读 is_writable() 也会返回 true
	//Unix server开启了安全模式，则is_writable不可靠
	//php从5.4版本开始，取消安全模式
	if (DIRECTORY_SEPARATOR === '/' && ( version_compare(PHP_VERSION, '5.4', '>=') || ! ini_get('safe_mode'))){
		return is_writable($file);
	}
	if (is_dir($file)){
		$file = rtrim($file, '/').'/check'.md5(mt_rand());  //增加check作为权限检测的标识
		if (($fp = @fopen($file, 'ab')) === FALSE){
			return FALSE;
		}
		@fclose($fp);
		//@chmod($file, 0755);  加上chmod偶尔会造成unlink提示 permission denied
		@unlink($file);
		return TRUE;
	}elseif ( ! is_file($file) || ($fp = @fopen($file, 'ab')) === FALSE){
		return FALSE;
	}
	@fclose($fp);
	return TRUE;
}

/**
 * BMP 创建函数,GD库里没有imagecreatefrombmp函数
 * @author simon
 * @param string $filename path of bmp file
 * @example who use,who knows
 * @return resource of GD
 */
function yd_imagecreatefrombmp( $filename ){
	if ( !$f1 = fopen( $filename, "rb" ) )
		return FALSE;
	 
	$FILE = unpack( "vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread( $f1, 14 ) );
	if ( $FILE['file_type'] != 19778 )
		return FALSE;
	 
	$BMP = unpack( 'Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel' . '/Vcompression/Vsize_bitmap/Vhoriz_resolution' . '/Vvert_resolution/Vcolors_used/Vcolors_important', fread( $f1, 40 ) );
	$BMP['colors'] = pow( 2, $BMP['bits_per_pixel'] );
	if ( $BMP['size_bitmap'] == 0 )
		$BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
	$BMP['bytes_per_pixel'] = $BMP['bits_per_pixel'] / 8;
	$BMP['bytes_per_pixel2'] = ceil( $BMP['bytes_per_pixel'] );
	$BMP['decal'] = ($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
	$BMP['decal'] -= floor( $BMP['width'] * $BMP['bytes_per_pixel'] / 4 );
	$BMP['decal'] = 4 - (4 * $BMP['decal']);
	if ( $BMP['decal'] == 4 )
		$BMP['decal'] = 0;
	 
	$PALETTE = array();
	if ( $BMP['colors'] < 16777216 ){
		$PALETTE = unpack( 'V' . $BMP['colors'], fread( $f1, $BMP['colors'] * 4 ) );
	}
	 
	$IMG = fread( $f1, $BMP['size_bitmap'] );
	$VIDE = chr( 0 );
	 
	$res = imagecreatetruecolor( $BMP['width'], $BMP['height'] );
	$P = 0;
	$Y = $BMP['height'] - 1;
	while( $Y >= 0 ){
		$X = 0;
		while( $X < $BMP['width'] ){
			if ( $BMP['bits_per_pixel'] == 32 ){
				$COLOR = unpack( "V", substr( $IMG, $P, 3 ) );
				$B = ord(substr($IMG, $P,1));
				$G = ord(substr($IMG, $P+1,1));
				$R = ord(substr($IMG, $P+2,1));
				$color = imagecolorexact( $res, $R, $G, $B );
				if ( $color == -1 )
					$color = imagecolorallocate( $res, $R, $G, $B );
				$COLOR[0] = $R*256*256+$G*256+$B;
				$COLOR[1] = $color;
			}elseif ( $BMP['bits_per_pixel'] == 24 )
			$COLOR = unpack( "V", substr( $IMG, $P, 3 ) . $VIDE );
			elseif ( $BMP['bits_per_pixel'] == 16 ){
				$COLOR = unpack( "n", substr( $IMG, $P, 2 ) );
				$COLOR[1] = $PALETTE[$COLOR[1] + 1];
			}elseif ( $BMP['bits_per_pixel'] == 8 ){
				$COLOR = unpack( "n", $VIDE . substr( $IMG, $P, 1 ) );
				$COLOR[1] = $PALETTE[$COLOR[1] + 1];
			}elseif ( $BMP['bits_per_pixel'] == 4 ){
				$COLOR = unpack( "n", $VIDE . substr( $IMG, floor( $P ), 1 ) );
				if ( ($P * 2) % 2 == 0 )
					$COLOR[1] = ($COLOR[1] >> 4);
				else
					$COLOR[1] = ($COLOR[1] & 0x0F);
				$COLOR[1] = $PALETTE[$COLOR[1] + 1];
			}elseif ( $BMP['bits_per_pixel'] == 1 ){
				$COLOR = unpack( "n", $VIDE . substr( $IMG, floor( $P ), 1 ) );
				if ( ($P * 8) % 8 == 0 )
					$COLOR[1] = $COLOR[1] >> 7;
				elseif ( ($P * 8) % 8 == 1 )
				$COLOR[1] = ($COLOR[1] & 0x40) >> 6;
				elseif ( ($P * 8) % 8 == 2 )
				$COLOR[1] = ($COLOR[1] & 0x20) >> 5;
				elseif ( ($P * 8) % 8 == 3 )
				$COLOR[1] = ($COLOR[1] & 0x10) >> 4;
				elseif ( ($P * 8) % 8 == 4 )
				$COLOR[1] = ($COLOR[1] & 0x8) >> 3;
				elseif ( ($P * 8) % 8 == 5 )
				$COLOR[1] = ($COLOR[1] & 0x4) >> 2;
				elseif ( ($P * 8) % 8 == 6 )
				$COLOR[1] = ($COLOR[1] & 0x2) >> 1;
				elseif ( ($P * 8) % 8 == 7 )
				$COLOR[1] = ($COLOR[1] & 0x1);
				$COLOR[1] = $PALETTE[$COLOR[1] + 1];
			}else
				return FALSE;
			imagesetpixel( $res, $X, $Y, $COLOR[1] );
			$X++;
			$P += $BMP['bytes_per_pixel'];
		}
		$Y--;
		$P += $BMP['decal'];
	}
	fclose( $f1 );
	return $res;
}

/**
 * 创建bmp格式图片
 *
 * @author: legend(legendsky@hotmail.com)
 * @link: http://www.ugia.cn/?p=96
 * @description: create Bitmap-File with GD library
 * @version: 0.1
 *
 * @param resource $im          图像资源
 * @param string   $filename    如果要另存为文件，请指定文件名，为空则直接在浏览器输出
 * @param integer  $bit         图像质量(1、4、8、16、24、32位)
 * @param integer  $compression 压缩方式，0为不压缩，1使用RLE8压缩算法进行压缩
 *
 * @return integer
 */
function yd_imagebmp(&$im, $filename = '', $bit = 8, $compression = 0){
	if (!in_array($bit, array(1, 4, 8, 16, 24, 32))){
		$bit = 8;
	}
	else if ($bit == 32) { // todo:32 bit
		$bit = 24;
	}
	$bits = pow(2, $bit);

	// 调整调色板
	imagetruecolortopalette($im, true, $bits);
	$width  = imagesx($im);
	$height = imagesy($im);
	$colors_num = imagecolorstotal($im);

	if ($bit <= 8){
		// 颜色索引
		$rgb_quad = '';
		for ($i = 0; $i < $colors_num; $i ++){
			$colors = imagecolorsforindex($im, $i);
			$rgb_quad .= chr($colors['blue']) . chr($colors['green']) . chr($colors['red']) . "\0";
		}

		// 位图数据
		$bmp_data = '';
		// 非压缩
		if ($compression == 0 || $bit < 8){
			if (!in_array($bit, array(1, 4, 8))){
				$bit = 8;
			}
			$compression = 0;

			// 每行字节数必须为4的倍数，补齐。
			$extra = '';
			$padding = 4 - ceil($width / (8 / $bit)) % 4;
			if ($padding % 4 != 0){
				$extra = str_repeat("\0", $padding);
			}

		for ($j = $height - 1; $j >= 0; $j --){
		$i = 0;
		while ($i < $width){
		$bin = 0;
		$limit = $width - $i < 8 / $bit ? (8 / $bit - $width + $i) * $bit : 0;
		for ($k = 8 - $bit; $k >= $limit; $k -= $bit){
		$index = imagecolorat($im, $i, $j);
		$bin |= $index << $k;
		$i ++;
		}
		$bmp_data .= chr($bin);
		}

			$bmp_data .= $extra;
		}
		}
		// RLE8 压缩
		else if ($compression == 1 && $bit == 8){
		for ($j = $height - 1; $j >= 0; $j --){
		$last_index = "\0";
		$same_num   = 0;
		for ($i = 0; $i <= $width; $i ++){
		$index = imagecolorat($im, $i, $j);
		if ($index !== $last_index || $same_num > 255){
		if ($same_num != 0){
			$bmp_data .= chr($same_num) . chr($last_index);
		}
		$last_index = $index;
		$same_num = 1;
		}else{
		$same_num ++;
	}
	}
	$bmp_data .= "\0\0";
	}

	$bmp_data .= "\0\1";
	}
	$size_quad = strlen($rgb_quad);
	$size_data = strlen($bmp_data);
	}else{
		// 每行字节数必须为4的倍数，补齐。
		$extra = '';
		$padding = 4 - ($width * ($bit / 8)) % 4;
		if ($padding % 4 != 0){
		$extra = str_repeat("\0", $padding);
		}
			// 位图数据
			$bmp_data = '';
			for ($j = $height - 1; $j >= 0; $j --){
			for ($i = 0; $i < $width; $i ++){
				$index  = imagecolorat($im, $i, $j);
				$colors = imagecolorsforindex($im, $index);

					if ($bit == 16){
					$bin = 0 << $bit;
					$bin |= ($colors['red'] >> 3) << 10;
					$bin |= ($colors['green'] >> 3) << 5;
					$bin |= $colors['blue'] >> 3;
					$bmp_data .= pack("v", $bin);
			}else{
					$bmp_data .= pack("c*", $colors['blue'], $colors['green'], $colors['red']);
			}
			// todo: 32bit;
			}
			$bmp_data .= $extra;
		}
		$size_quad = 0;
		$size_data = strlen($bmp_data);
		$colors_num = 0;
	}
	// 位图文件头
	$file_header = "BM" . pack("V3", 54 + $size_quad + $size_data, 0, 54 + $size_quad);
		// 位图信息头
		$info_header = pack("V3v2V*", 0x28, $width, $height, 1, $bit, $compression, $size_data, 0, 0, $colors_num, 0);

		// 写入文件
		if ($filename != ''){
			$fp = fopen($filename, "wb");
			fwrite($fp, $file_header);
			fwrite($fp, $info_header);
			fwrite($fp, $rgb_quad);
			fwrite($fp, $bmp_data);
			fclose($fp);
			return 1;
		}

		// 浏览器输出
		header("Content-Type: image/bmp");
		echo $file_header . $info_header;
		echo $rgb_quad;
		echo $bmp_data;
		return 1;
}

/**
 * 获取开始标签对应的结束标签的位置
 * @param string $content
 * @param string $start_tag 开始标签
 * @return boolean 没有找到返回 false，1：返回标签对之间的字符串，2：返回结束标记位置
 */
function yd_tagpos($content, $start_tag, $return_type='1'){
	$start_tag = trim($start_tag);
	if( empty($content) || empty($start_tag) ) return false;
	$start_pos = stripos($content, $start_tag);
	if( $start_pos === false ) return false;

	$pos = strpos($start_tag, ' ');
	if( $pos === false ){  //开始标签没有属性，如：<ul>、<div>
		$left = rtrim($start_tag, '>');
		$right = str_replace('<', '</', $start_tag);
	}else{ //<div id='xx' >
		$left = substr($start_tag, 0, $pos);
		$right = str_replace('<', '</', $left.'>');
	}
	//开始标记入站
	$stack[] = array( 'tag'=>$left, 'pos'=> $start_pos);
	$offset = $start_pos + 1;
	$find_pos = false;
	while( ( $result=_next_tag($content, $left, $right, $offset) ) !== false ){
		$n = count($stack);
		if( $stack[$n-1]['tag'] == $result['tag'] ){ //相同则进站
			array_push($stack, $result);
		}else{
			array_pop($stack);
			if( count($stack) == 0 ){ //出站以后，堆栈为空，表示匹配完成
				$find_pos = $result['pos'];
				break;
			}
		}
		$offset = $result['pos'] + 1;
	}
	$result = false;
	if( $find_pos === false ){
		$result = false;
	}else{
		if( $return_type == 1){ //返回字符串
			//$start_index = $start_pos + strlen($start_tag); 存在bug相当于前后截取
			//返回标签对之间的内容，标签里属性不能有>否则会有问题
			$start_index = strpos($content, '>', $start_pos) + 1;
			$length = $find_pos - $start_index;
			$result = substr($content, $start_index, $length);
		}else{ //返回位置
			$result = $find_pos;
		}
	}
	return $result;
}

function _next_tag($content, $left, $right, $offset = 0){
	$leftpos = stripos($content, $left, $offset);  //当内容中有<div 存在bug
	$rightpos = stripos($content, $right, $offset);
	if( false === $leftpos && false === $rightpos  ){
		$result = false;
	}else if( false === $leftpos ){
		$result = array('tag'=>$right, 'pos'=>$rightpos);
	}else if( false === $rightpos ){
		$result = array('tag'=>$left, 'pos'=>$leftpos);
	}else if( $leftpos < $rightpos ){
		$result = array('tag'=>$left, 'pos'=>$leftpos);
	}else{
		$result = array('tag'=>$right, 'pos'=>$rightpos);
	}
	return $result;
}

/**
 * 字符串转2维数组
 * @param string $string
 * @param array $key
 * @param string $rowDelimiter
 * @param string $colDelimiter
 */
function yd_split($string, $key=false, $rowDelimiter='@@@', $colDelimiter='###'){
	if(empty($string) ) return false;
	$row = explode($rowDelimiter, $string);
	$limit = is_array($key) ? count($key) : 0;
    $result = array();
	foreach ($row as $r){
	    $col = explode($colDelimiter, $r, $limit);
		$n = count( $col );
		$temp = array();
		for($i = 0; $i < $n; $i++){
			$k = ( !empty($key) && isset( $key[$i] ) ) ? $key[$i] : $i;
			$temp[$k] = $col[ $i ];
		}
		$result[] = $temp;
	}
	return $result;
}

/**
 * 判断目录是否有执行权限
 * @param string $dir
 * @return boolean  0：无、1：有、-1：未知
 */
function yd_is_executable($dir){
	$result = -1;
	if (is_dir($dir) && yd_is_writable($dir) ){
		$file = rtrim($dir, '/').'/x.php';
		@file_put_contents($file, '<?php echo 1; ?>');
		$url = get_web_url().'/'.ltrim($file, './');
		//访问外部网址，必须设置超时时间，否则会在某些linux服务器提示：访问超时的错误
		$context = @stream_context_create( array('http' => array('timeout' => 30) ));
		$result = @file_get_contents($url);
		if( $result != 1 ) $result = 0;
		@unlink($file);
	}
	return $result;
}

function yd_is_mobile()   {
	//先检查是否为wap代理，准确度高
    $via = isset($_SERVER['HTTP_VIA']) ? $_SERVER['HTTP_VIA'] : '';
	if(stristr($via,"wap") || 1==session('IsMobile')){
		return true;
	}
	$_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';    
	$mobile_browser = 0;
	if(preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))     
		$mobile_browser++;    
	if((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') !== false))     
		$mobile_browser++;
	if(isset($_SERVER['HTTP_X_WAP_PROFILE']))
		$mobile_browser++;
	if(isset($_SERVER['HTTP_PROFILE']))
		$mobile_browser++;
	$mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));
	$mobile_agents = array('w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',       
			'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',       
			'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',       
			'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',       
			'newt','noki','oper','palm','pana','pant','phil','play','port','prox',       
			'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',       
			'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',       
			'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',       
			'wapr','webc','winw','winw','xda','xda-');
	if(in_array($mobile_ua, $mobile_agents))
		$mobile_browser++;
	if(strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)
		$mobile_browser++;    // Pre-final check to reset everything if the user is on Windows    
	if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)
		$mobile_browser=0;    // But WP7 is also Windows, with a slightly different characteristic    
	if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)     
		$mobile_browser++;
	if($mobile_browser>0)  
		return true;    
	else   
		return false;
}

/**
 * 相对url转绝对url
 * 举例：$base_url$src_url
 * 	http://x.cn/news/xx.html  ./1.html             http://x.cn/news/1.html
	http://x.cn/news/             ../1.html,            http://x.cn/1.html  
	http://x.cn/news/e/          ../../1.html,         http://x.cn/1.html  
	http://x.cn/news/             ../news/1.html,   http://x.cn/news/1.html 
	http://x.cn/news/             1.html,               http://x.cn/news/1.html 
	http://x.cn/news/             /1.html,              http://x.cn/1.html 
	http://x.cn/news/             ../../1.html,          http://x.cn/../1.html 
	如果基准url是文件：http://x.cn/news/1.html 会自动提取news作为当前目录
	如果基准url是目录（必须以/开头）：http://x.cn/news/ 当前目录为：news，没有后面的/则当前目录为：/
 * @param string $base_url  基准url
 * @param string $src_url     相对url
 * @return string  返回转换以后的绝对路径
 */
function yd_rel2abs($base_url, $src_url) {
	if (!$src_url) {
		return '';
	}
	$src_info = parse_url($src_url);
	if (isset($src_info['scheme'])) {
		return $src_url;
	}
	$base_info = parse_url($base_url);
	$url = $base_info['scheme'] . '://' . $base_info['host'];
	if (!isset($src_info['path'])) {
		$src_info['path'] = '';
	}
	if (substr($src_info['path'], 0, 1) == '/') {
		$path = $src_info['path'];
	} else {
		//fixed only ?
		if (empty($src_info['path'])) {
			$path = ($base_info['path']);
		} else {
			// fix dirname
			if (substr($base_info['path'], -1) == '/') {
				$path = $base_info['path'] . $src_info['path'];
			} else {
				$path = (dirname($base_info['path']) . '/') . $src_info['path'];
			}
		}
	}
	$rst = array();
	$path_array = explode('/', $path);
	if (!$path_array[0]) {
		$rst[] = '';
	}
	foreach ($path_array as $key => $dir) {
		if ($dir == '..') {
			if (end($rst) == '..') {
				$rst[] = '..';
			} elseif (!array_pop($rst)) {
				$rst[] = '..';
			}
		} elseif ($dir && $dir != '.') {
			$rst[] = $dir;
		}
	}
	if (!end($path_array)) {
		$rst[] = '';
	}
	$url .= implode('/', $rst);
	$url = str_replace('\\', '/', $url);
	$url = str_ireplace('&amp;', '&', $url);
	return $url . ($src_info['query'] ? '?' . $src_info['query'] : '');
}

/**
 +----------------------------------------------------------
 * 字符串截取，支持中文和其他编码
 +----------------------------------------------------------
 * @static
 * @access public
 +----------------------------------------------------------
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function msubstr($str, $start=0, $length=0, $charset="utf-8", $suffix='...') {
	if(function_exists("mb_substr"))
		$slice = mb_substr($str, $start, $length, $charset);
	elseif(function_exists('iconv_substr')) {
		$slice = iconv_substr($str,$start,$length,$charset);
		if(false === $slice) {
			$slice = '';
		}
	}else{
		$re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
		$re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
		$re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
		$re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
		preg_match_all($re[$charset], $str, $match);
		$slice = join("",array_slice($match[0], $start, $length));
	}
	if( $suffix === true ) $suffix = '...';
	return $suffix ? $slice.$suffix : $slice;
}

/**
 +----------------------------------------------------------
 * 产生随机字串，可用来自动生成密码 默认长度6位 字母和数字混合
 +----------------------------------------------------------
 * @param string $len 长度
 * @param string $type 字串类型
 * 0 字母 1 数字 其它 混合
 * @param string $addChars 额外字符
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function rand_string($len=6,$type='',$addChars='') {
	$str ='';
	switch($type) {
		case 0:
			$chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.$addChars;
			break;
		case 1:
			$chars= str_repeat('0123456789',3);
			break;
		case 2:
			$chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ'.$addChars;
			break;
		case 3:
			$chars='abcdefghijklmnopqrstuvwxyz'.$addChars;
			break;
		case 4:
			$chars = "们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩康遵牧遭幅园腔订香肉弟屋敏恢忘编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航衣孙龄岭骗休借".$addChars;
			break;
		case 5:
			$chars='abcdefghijkmnpqrstuvwxyz23456789'.$addChars;
			break;
		default :
			// 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
			$chars='ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'.$addChars;
			break;
	}
	if($len>10 ) {//位数过长重复字符串一定次数
		$chars= $type==1? str_repeat($chars,$len) : str_repeat($chars,5);
	}
	if($type!=4) {
		$chars   =   str_shuffle($chars);
		$str     =   substr($chars,0,$len);
	}else{
		// 中文随机字
		for($i=0;$i<$len;$i++){
			$str.= msubstr($chars, floor(mt_rand(0,mb_strlen($chars,'utf-8')-1)),1);
		}
	}
	return $str;
}

/**
 * 生成32位唯一token字符串
 * @return string
 */
function yd_make_token(){
	$token = md5( uniqid(mt_rand(), true) );
	return $token;
}

/**
 +----------------------------------------------------------
 * 获取登录验证码 默认为4位数字
 +----------------------------------------------------------
 * @param string $fmode 文件名
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function build_verify ($length=4,$mode=1) {
	return rand_string($length,$mode);
}

/**
 +----------------------------------------------------------
 * 字节格式化 把字节数格式为 B K M G T 描述的大小
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function byte_format($size, $dec=2) {
	$a = array("B", "KB", "MB", "GB", "TB", "PB");
	$pos = 0;
	while ($size >= 1024) {
		$size /= 1024;
		$pos++;
	}
	return round($size,$dec)." ".$a[$pos];
}

/**
 +----------------------------------------------------------
 * 检查字符串是否是UTF8编码
 +----------------------------------------------------------
 * @param string $string 字符串
 +----------------------------------------------------------
 * @return Boolean
 +----------------------------------------------------------
 */
function is_utf8($string) {
	return preg_match('%^(?:
			[\x09\x0A\x0D\x20-\x7E]            # ASCII
			| [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
			|  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
			| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
			|  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
			|  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
			| [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
			|  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
	)*$%xs', $string);
}
/**
 +----------------------------------------------------------
 * 代码加亮
 +----------------------------------------------------------
 * @param String  $str 要高亮显示的字符串 或者 文件名
 * @param Boolean $show 是否输出
 +----------------------------------------------------------
 * @return String
 +----------------------------------------------------------
 */
function highlight_code($str,$show=false) {
	if(file_exists($str)) {
		$str    =   file_get_contents($str);
	}
	$str  =  stripslashes(trim($str));
	// The highlight string function encodes and highlights
	// brackets so we need them to start raw
	$str = str_replace(array('&lt;', '&gt;'), array('<', '>'), $str);

	// Replace any existing PHP tags to temporary markers so they don't accidentally
	// break the string out of PHP, and thus, thwart the highlighting.

	$str = str_replace(array('&lt;?php', '?&gt;',  '\\'), array('phptagopen', 'phptagclose', 'backslashtmp'), $str);

	// The highlight_string function requires that the text be surrounded
	// by PHP tags.  Since we don't know if A) the submitted text has PHP tags,
	// or B) whether the PHP tags enclose the entire string, we will add our
	// own PHP tags around the string along with some markers to make replacement easier later

	$str = '<?php //tempstart'."\n".$str.'//tempend ?>'; // <?

	// All the magic happens here, baby!
	$str = highlight_string($str, TRUE);

	// Prior to PHP 5, the highlight function used icky font tags
	// so we'll replace them with span tags.
	if (abs(phpversion()) < 5) {
		$str = str_replace(array('<font ', '</font>'), array('<span ', '</span>'), $str);
		$str = preg_replace('#color="(.*?)"#', 'style="color: \\1"', $str);
	}

	// Remove our artificially added PHP
	$str = preg_replace("#\<code\>.+?//tempstart\<br />\</span\>#is", "<code>\n", $str);
	$str = preg_replace("#\<code\>.+?//tempstart\<br />#is", "<code>\n", $str);
	$str = preg_replace("#//tempend.+#is", "</span>\n</code>", $str);

	// Replace our markers back to PHP tags.
	$str = str_replace(array('phptagopen', 'phptagclose', 'backslashtmp'), array('&lt;?php', '?&gt;', '\\'), $str); //<?
	$line   =   explode("<br />", rtrim(ltrim($str,'<code>'),'</code>'));
	$result =   '<div class="code"><ol>';
	foreach($line as $key=>$val) {
		$result .=  '<li>'.$val.'</li>';
	}
	$result .=  '</ol></div>';
	$result = str_replace("\n", "", $result);
	if( $show!== false) {
		echo($result);
	}else {
		return $result;
	}
}

//输出安全的html
function h($text, $tags = null) {
	$text	=	trim($text);
	//完全过滤注释
	$text	=	preg_replace('/<!--?.*-->/','',$text);
	//完全过滤动态代码
	$text	=	preg_replace('/<\?|\?'.'>/','',$text);
	//完全过滤js
	$text	=	preg_replace('/<script?.*\/script>/','',$text);

	$text	=	str_replace('[','&#091;',$text);
	$text	=	str_replace(']','&#093;',$text);
	$text	=	str_replace('|','&#124;',$text);
	//过滤换行符
	$text	=	preg_replace('/\r?\n/','',$text);
	//br
	$text	=	preg_replace('/<br(\s\/)?'.'>/i','[br]',$text);
	$text	=	preg_replace('/(\[br\]\s*){10,}/i','[br]',$text);
	//过滤危险的属性，如：过滤on事件lang js
	while(preg_match('/(<[^><]+)( lang|on|action|background|codebase|dynsrc|lowsrc)[^><]+/i',$text,$mat)){
		$text=str_replace($mat[0],$mat[1],$text);
	}
	while(preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i',$text,$mat)){
		$text=str_replace($mat[0],$mat[1].$mat[3],$text);
	}
	if(empty($tags)) {
		$tags = 'table|td|th|tr|i|b|u|strong|img|p|br|div|strong|em|ul|ol|li|dl|dd|dt|a';
	}
	//允许的HTML标签
	$text	=	preg_replace('/<('.$tags.')( [^><\[\]]*)>/i','[\1\2]',$text);
	//过滤多余html
	$text	=	preg_replace('/<\/?(html|head|meta|link|base|basefont|body|bgsound|title|style|script|form|iframe|frame|frameset|applet|id|ilayer|layer|name|script|style|xml)[^><]*>/i','',$text);
	//过滤合法的html标签
	while(preg_match('/<([a-z]+)[^><\[\]]*>[^><]*<\/\1>/i',$text,$mat)){
		$text=str_replace($mat[0],str_replace('>',']',str_replace('<','[',$mat[0])),$text);
	}
	//转换引号
	while(preg_match('/(\[[^\[\]]*=\s*)(\"|\')([^\2=\[\]]+)\2([^\[\]]*\])/i',$text,$mat)){
		$text=str_replace($mat[0],$mat[1].'|'.$mat[3].'|'.$mat[4],$text);
	}
	//过滤错误的单个引号
	while(preg_match('/\[[^\[\]]*(\"|\')[^\[\]]*\]/i',$text,$mat)){
		$text=str_replace($mat[0],str_replace($mat[1],'',$mat[0]),$text);
	}
	//转换其它所有不合法的 < >
	$text	=	str_replace('<','&lt;',$text);
	$text	=	str_replace('>','&gt;',$text);
	$text	=	str_replace('"','&quot;',$text);
	//反转换
	$text	=	str_replace('[','<',$text);
	$text	=	str_replace(']','>',$text);
	$text	=	str_replace('|','"',$text);
	//过滤多余空格
	$text	=	str_replace('  ',' ',$text);
	return $text;
}

// 随机生成一组字符串
function build_count_rand ($number,$length=4,$mode=1) {
	if($mode==1 && $length<strlen($number) ) {
		//不足以生成一定数量的不重复数字
		return false;
	}
	$rand   =  array();
	for($i=0; $i<$number; $i++) {
		$rand[] =   rand_string($length,$mode);
	}
	$unqiue = array_unique($rand);
	if(count($unqiue)==count($rand)) {
		return $rand;
	}
	$count   = count($rand)-count($unqiue);
	for($i=0; $i<$count*3; $i++) {
		$rand[] =   rand_string($length,$mode);
	}
	$rand = array_slice(array_unique ($rand),0,$number);
	return $rand;
}

function remove_xss($val) {
	// remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
	// this prevents some character re-spacing such as <java\0script>
	// note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
	$val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);

	// straight replacements, the user should never need these since they're normal characters
	// this prevents like <IMG SRC=@avascript:alert('XSS')>
	$search = 'abcdefghijklmnopqrstuvwxyz';
	$search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$search .= '1234567890!@#$%^&*()';
	$search .= '~`";:?+/={}[]-_|\'\\';
	for ($i = 0; $i < strlen($search); $i++) {
		// ;? matches the ;, which is optional
		// 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

		// @ @ search for the hex values
		$val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
		// @ @ 0{0,7} matches '0' zero to seven times
		$val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
	}

	// now the only remaining whitespace attacks are \t, \n, and \r
	$ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
	$ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
	$ra = array_merge($ra1, $ra2);

	$found = true; // keep replacing as long as the previous round replaced something
	while ($found == true) {
		$val_before = $val;
		for ($i = 0; $i < sizeof($ra); $i++) {
			$pattern = '/';
			for ($j = 0; $j < strlen($ra[$i]); $j++) {
				if ($j > 0) {
					$pattern .= '(';
					$pattern .= '(&#[xX]0{0,8}([9ab]);)';
					$pattern .= '|';
					$pattern .= '|(&#0{0,8}([9|10|13]);)';
					$pattern .= ')*';
				}
				$pattern .= $ra[$i][$j];
			}
			$pattern .= '/i';
			$replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
			$val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
			if ($val_before == $val) {
				// no replacements were made, so exit the loop
				$found = false;
			}
		}
	}
	return $val;
}

/**
 +----------------------------------------------------------
 * 把返回的数据集转换成Tree
 +----------------------------------------------------------
 * @access public
 +----------------------------------------------------------
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 +----------------------------------------------------------
 * @return array
 +----------------------------------------------------------
 */
function list_to_tree($list, $pk='id',$pid = 'pid',$child = '_child',$root=0) {
	// 创建Tree
	$tree = array();
	if(is_array($list)) {
		// 创建基于主键的数组引用
		$refer = array();
		foreach ($list as $key => $data) {
			$refer[$data[$pk]] =& $list[$key];
		}
		foreach ($list as $key => $data) {
			// 判断是否存在parent
			$parentId = $data[$pid];
			if ($root == $parentId) {
				$tree[] =& $list[$key];
			}else{
				if (isset($refer[$parentId])) {
					$parent =& $refer[$parentId];
					$parent[$child][] =& $list[$key];
				}
			}
		}
	}
	return $tree;
}

/**
 +----------------------------------------------------------
 * 对查询结果集进行排序
 +----------------------------------------------------------
 * @access public
 +----------------------------------------------------------
 * @param array $list 查询结果
 * @param string $field 排序的字段名
 * @param array $sortby 排序类型
 * asc正向排序 desc逆向排序 nat自然排序
 +----------------------------------------------------------
 * @return array
 +----------------------------------------------------------
 */
function list_sort_by($list,$field, $sortby='asc') {
	if(is_array($list)){
		$refer = $resultSet = array();
		foreach ($list as $i => $data)
			$refer[$i] = &$data[$field];
		switch ($sortby) {
			case 'asc': // 正向排序
				asort($refer);
				break;
			case 'desc':// 逆向排序
				arsort($refer);
				break;
			case 'nat': // 自然排序
				natcasesort($refer);
				break;
		}
		foreach ( $refer as $key=> $val)
			$resultSet[] = &$list[$key];
		return $resultSet;
	}
	return false;
}

/**
 +----------------------------------------------------------
 * 在数据列表中搜索
 +----------------------------------------------------------
 * @access public
 +----------------------------------------------------------
 * @param array $list 数据列表
 * @param mixed $condition 查询条件
 * 支持 array('name'=>$value) 或者 name=$value
 +----------------------------------------------------------
 * @return array
 +----------------------------------------------------------
 */
function list_search($list,$condition) {
	if(is_string($condition))
		parse_str($condition,$condition);
	// 返回的结果集合
	$resultSet = array();
	foreach ($list as $key=>$data){
		$find   =   false;
		foreach ($condition as $field=>$value){
			if(isset($data[$field])) {
				if(0 === strpos($value,'/')) {
					$find   =   preg_match($value,$data[$field]);
				}elseif($data[$field]==$value){
					$find = true;
				}
			}
		}
		if($find)
			$resultSet[]     =   &$list[$key];
	}
	return $resultSet;
}

// 自动转换字符集 支持数组转换
function auto_charset($fContents, $from='gbk', $to='utf-8') {
	$from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
	$to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
	if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
		//如果编码相同或者非字符串标量则不转换
		return $fContents;
	}
	if (is_string($fContents)) {
		if (function_exists('mb_convert_encoding')) {
			return mb_convert_encoding($fContents, $to, $from);
		} elseif (function_exists('iconv')) {
			return iconv($from, $to, $fContents);
		} else {
			return $fContents;
		}
	} elseif (is_array($fContents)) {
		foreach ($fContents as $key => $val) {
			$_key = auto_charset($key, $from, $to);
			$fContents[$_key] = auto_charset($val, $from, $to);
			if ($key != $_key)
				unset($fContents[$key]);
		}
		return $fContents;
	}
	else {
		return $fContents;
	}
}

//将内容中的图片相对地址和链接相对地址转化为绝对路径
function yd_relative_to_absolute($content, $feed_url) {
	//当$content仅仅是一个图片地址时，如：/Upload/1.jpg
	if( substr($content, 0, 1) == '/' ){
		if( substr($content, -4, 1) == '.' || substr($content, -5, 1) == '.' ){
			return app_to_fullurl($content,$feed_url);
		}
	}
	
	preg_match('/(https|http|ftp):\/\//', $feed_url, $protocol);
	$server_url = preg_replace("/(https|http|ftp|news):\/\//", "", $feed_url);
	$server_url = preg_replace("/\/.*/", "", $server_url);

	if ($server_url == '') {
		return $content;
	}
	// $protocol[0]存储："http://"，$protocol[1]存储http
	if (isset($protocol[0])) {
		$new_content = preg_replace('/href="\//', 'href="'.$protocol[0].$server_url.'/', $content);
		$new_content = preg_replace('/src="\//', 'src="'.$protocol[0].$server_url.'/', $new_content);
	} else {
		$new_content = $content;
	}
	return $new_content;
}

/**
 * 转化为货币类型，保留2位小数：如：12345转为：12345.00，1.235转为1.24
 * @param double $val
 */
function yd_to_money($val){
	return number_format($val, 2, '.', '');
}

/**
 * 判断当前协议是否是https
 */
function yd_is_https(){
	if ( !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off'){
		return true;
	}elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'){
		return true;
	}elseif ( !empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off'){
		return true;
	}elseif ( !empty($_SERVER['HTTP_FROM_HTTPS']) && strtolower($_SERVER['HTTP_FROM_HTTPS']) !== 'off'){
		//西数虚拟主机部署ssl会启用HTTP_FROM_HTTPS，没有设置HTTPS
		return true;
	}
	return false;
}

/**
 * 是否是本地开发
 */
function yd_is_local($userAgent=false){
    $addr = $_SERVER['SERVER_ADDR'];
    $isLocalIp = (false !== strpos($addr, '192.168.1') || false !== strpos($addr, '127.0.0.1') ) && file_exists('D:/local.lock');
    $isAgent = $userAgent ? stripos($_SERVER['HTTP_USER_AGENT'], $userAgent) : true;
    if(false !== $isLocalIp && false !== $isAgent){
        return true;
    }else{
        return false;
    }
}

/**
 * 是不是本地POSTMAN调试
 */
function yd_is_postman(){
    return yd_is_local('Postman');
}

/**
 * 计算时间差
 * @param string $startTime 开始时间字符串
 * @param string $endTime 结束时间字符串
 * @param string $elaps 类型（d天、y年、M月、w星期、h小时、m分钟、s秒）
 * @return number
 */
function yd_date_diff($startTime, $endTime, $elaps = "d") {
    $__DAYS_PER_WEEK__       = (7);
    $__DAYS_PER_MONTH__       = (30);
    $__DAYS_PER_YEAR__       = (365);
    $__HOURS_IN_A_DAY__      = (24);
    $__MINUTES_IN_A_DAY__    = (1440);
    $__SECONDS_IN_A_DAY__    = (86400);
    //计算天数差
    $tsStart = is_numeric($startTime) ? $startTime : strtotime($startTime);
    $tsEnd = is_numeric($endTime) ? $endTime : strtotime($endTime);
    $__DAYSELAPS = ($tsEnd- $tsStart) / $__SECONDS_IN_A_DAY__ ;
    switch ($elaps) {
        case "y"://转换成年
            $__DAYSELAPS =  $__DAYSELAPS / $__DAYS_PER_YEAR__;
            break;
        case "M"://转换成月
            $__DAYSELAPS =  $__DAYSELAPS / $__DAYS_PER_MONTH__;
            break;
        case "w"://转换成星期
            $__DAYSELAPS =  $__DAYSELAPS / $__DAYS_PER_WEEK__;
            break;
        case "h"://转换成小时
            $__DAYSELAPS =  $__DAYSELAPS * $__HOURS_IN_A_DAY__;
            break;
        case "m"://转换成分钟
            $__DAYSELAPS =  $__DAYSELAPS * $__MINUTES_IN_A_DAY__;
            break;
        case "s"://转换成秒
            $__DAYSELAPS =  $__DAYSELAPS * $__SECONDS_IN_A_DAY__;
            break;
    }
    return $__DAYSELAPS;
}

/**
 * 百度坐标转火星坐标yd（国测局）
 *  'x'=>atitude', 'y'=>Longitude
 * @param array $from
 * @return array
 */
function yd_bd2gcj($from){
    $x_pi = 3.14159265358979324*3000.0/180.0;

    $x = $from['y'] - 0.0065;
    $y = $from['x'] - 0.006;
    $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);

    $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
    $to['y'] = $z * cos($theta);  //long
    $to['x'] = $z * sin($theta);  //lat
    return $to;
}

/**
 * 设置超时时间
 * $time：超时时间，单位：秒，默认为1小时
 */
function yd_set_time_limit($time=3600){
    @set_time_limit($time); //在windows下不生效
    @ini_set("max_execution_time", "{$time}");
}

/**
 *  友点密码加密
 * @param $pwd 原始密码
 */
function yd_password_hash($pwd){
    // if(defined('CRYPT_BLOWFISH' ) && CRYPT_BLOWFISH ==1){
    //     $randStr = md5(uniqid(rand(), true));
    //     $randStr = substr($randStr, 0, 22);
    //     $salt = '$2y$11$'.$randStr;  //29位盐
    //     $hash = crypt($pwd, $salt);
    // }else{
    //     $hash = md5($pwd);
    // }
    return $pwd;
}

/**
 * 校验密码是否正确
 */
function yd_password_verify($pwd, $hash){
	return $pwd === $hash;
    // $hash = (string)$hash;
    // $len = strlen($hash);
    // if(32 == $len){ //表示32位普通md5加密（为了兼容旧版）
    //     $b = (md5($pwd)===$hash);
    // }else{
    //     $salt = substr($hash, 0, 29); //提取盐
    //     $b = (crypt($pwd, $salt) === $hash);
    // }
    // return $b;
}

function yd_safe_decode($str){
    $strLen = strlen($str);
    if($strLen<13) return '';
    $start = 6;
    $end = $strLen - 2 * $start;
    $str = substr($str, $start, $end);
    $str = urldecode(base64_decode($str));
    return $str;
}

/**
 * 是否包含php代码
 */
function yd_contain_php($str){
    if(empty($str)) return false;
    //通过<script language="php">phpinfo();</script>也可以插入php脚本
    if(preg_match('/language\s*=\s*["\']*php["\']*/i', $str)){
        return true;
    }
    //<?php
    if(preg_match('/<\?php\s*|<\?=\s*|<\?\s/i', $str)){
        return true;
    }
    return false;
}

/**
 * 判断是否是ajax请求
 */
function yd_is_ajax(){
    if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) ) {
        if('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])){
            return true;
        }
    }
    // 判断Ajax方式提交
    if(!empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')])){
        return true;
    }
    return false;
}