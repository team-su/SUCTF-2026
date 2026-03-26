<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class CollectAction extends AdminBaseAction{
	private $_userAgent = array(
		//1:IE浏览器、2:Firefox浏览器、3:Chrome浏览器、4:Opera浏览器、5:Safari浏览器、
		1=>"Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)"	, 
		2=>"Mozilla/5.0 (Windows NT 6.1; rv:37.0) Gecko/20100101 Firefox/37.0",
		3=>"Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36",
		4=>"User-Agent:Opera/9.80 （Macintosh; Intel Mac OS X 10.6.8; U; en） Presto/2.8.131 Version/11.11",
		5=>"Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN) AppleWebKit/533.21.1 (KHTML, like Gecko) Version/5.0.5 Safari/533.21.1",
		//手机浏览器：	6:Android手机浏览器、7:iPhone手机浏览器、8:微信浏览器、9:QQ手机浏览器
		6=>"Mozilla/5.0 (Linux; U; Android 2.2; en-us; Nexus One Build/FRF91) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1",
		7=>"Mozilla/5.0 (iPad; U; CPU OS 3_2_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B500 Safari/531.21.10",
		8=>"Mozilla/5.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/5.0",
		9=>"MQQBrowser/2801 Mozilla/5.0 (iPhone:U;CPU iPhone OS 4_1 like Mac OS X;zh-cn) AppleWebKit/532.9(KHTML,like Gecko)Version/4.0.5 Mobile/8B117 Safari/6531.22.7",
		//搜索蜘蛛
		10=>"Baiduspider+(+http://www.baidu.com/search/spider.htm)",
		11=>"Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)",
		12=>"Sogou web spider/3.0(+http://www.sogou.com/docs/help/webmasters.htm#07″)",
		
	);

	function index(){
		$p['Parameter'] = array();
		$p['HasPage'] = false; //表示有分页
		$this->opIndex( $p );
	}

	//开始采集数据
	function start(){
		header("Content-Type:text/html; charset=utf-8");
		C('TOKEN_ON',false);
		$id = $_REQUEST['id'];
		$m = D('Admin/Collect');
		$data = $m->findCollect( $id );
		$ListUrlOther = trim($data['ListUrlOther']);
		$data['ListUrlOther'] = str_replace( array("\r\n","\r","\n"), '@@@', $ListUrlOther );
		$this->assign('ThumbWidth', $GLOBALS['Config']['THUMB_WIDTH']);
		$this->assign('ThumbHeight', $GLOBALS['Config']['THUMB_HEIGHT']);
		$this->assign('ThumbEnable', $GLOBALS['Config']['THUMB_ENABLE']);
		
		$this->assign('CollectID', $id);
		$this->assign('Data', $data);
		$this->display();
	}
	
	function add(){
		C('TOKEN_ON',false);
		$realChannel = $this->getRealChannel();
		$this->assign('Channel', $realChannel);
		$this->assign('ThumbWidth', $GLOBALS['Config']['THUMB_WIDTH']);
		$this->assign('ThumbHeight', $GLOBALS['Config']['THUMB_HEIGHT']);
		$this->assign('ThumbEnable', $GLOBALS['Config']['THUMB_ENABLE']);
		$this->opAdd();
	}
	
	function modify(){
		C('TOKEN_ON',false);
		$options = array();
		$realChannel = $this->getRealChannel();
		$this->assign('Channel', $realChannel);
		$this->assign('ThumbWidth', $GLOBALS['Config']['THUMB_WIDTH']);
		$this->assign('ThumbHeight', $GLOBALS['Config']['THUMB_HEIGHT']);
		$this->assign('ThumbEnable', $GLOBALS['Config']['THUMB_ENABLE']);
		$this->opModify(false, $options);
	}
	
	private function getRealChannel(){
		//获取频道数据
		$m = D('Admin/Channel');
		$gid = intval(session('AdminGroupID'));
		$channel = ($gid==1) ? $m->getChannelList(0,-1) : $m->getChannelPurview(1, $gid);
		$m1 = D('Admin/Attribute');
		$realChannel = array(); //不显示单页频道和链接频道
		foreach($channel as $c){
			$hasChild = $c['HasChild'];
			$channelModelID = $c['ChannelModelID'];
			if( $hasChild == 0 && ($channelModelID==32 || $channelModelID==33) ) continue;
			$attr = $m1->getCollectAttribte( $channelModelID, $hasChild );
			$realChannel[] = array(
					'ChannelID' => $c['ChannelID'],
					'ChannelName' => $c['ChannelName'],
					'Html'=>$c['Html'],
					'ChannelModelID' => $channelModelID,
					'HasChild' => $hasChild,
					'FieldName'=>$attr['FieldName'],
					'DisplayName'=>$attr['DisplayName'],
					'AttributeID'=>$attr['AttributeID'],
			);
		}
		return $realChannel;
	}
	
	//删除、批量删除
	function del(){
		$this->opDel();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
		$p['Data'] = $this->checkParameter();
		$this->opSaveModify( $p );
	}
	
	function saveAdd(){
		$p['Data'] = $this->checkParameter();
		$this->opSaveAdd( $p );
	}
	
	private function checkParameter(){
		//用于修改
		if(!empty($_POST['CollectID'])){
			$data['CollectID'] = $_POST['CollectID'];
		}
		
		$data['ChannelID'] = $_POST['ChannelID'];
		$data['CollectDescription'] = trim($_POST['CollectDescription']);
		
		$data['CollectName'] = trim($_POST['CollectName']);
		if( $data['CollectName'] == ''){
			$this->ajaxReturn(null, '规则名称不能为空!' , 0);
		}
		
		//列表参数 start====================================
		$ListUrlStart = trim($_POST['ListUrlStart']);
		if( !is_numeric($ListUrlStart) ){
			$this->ajaxReturn(null, '通配符起始值必须为整数!' , 0);
		}
	
		$ListUrlEnd= trim($_POST['ListUrlEnd']);
		if( !is_numeric($ListUrlEnd) ){
			$this->ajaxReturn(null, '通配符结束值必须为整数!' , 0);
		}
		
		$ListUrlStep= trim($_POST['ListUrlStep']);
		if( !is_numeric($ListUrlStep) ){
			$this->ajaxReturn(null, '通配符步长必须为整数!' , 0);
		}
		
		$ListUrlLength= trim($_POST['ListUrlLength']);
		if( !is_numeric($ListUrlLength) ){
			$this->ajaxReturn(null, '通配符长度必须为整数!' , 0);
		}
		//列表页地址0、开始1、结束2、步长3、长度4、附加5、区域6
		$data['ListUrlPara'] = "{$_POST['ListUrl']}@@@$ListUrlStart@@@$ListUrlEnd@@@$ListUrlStep";
		$data['ListUrlPara'] .= "@@@$ListUrlLength@@@{$_POST['ListUrlOther']}@@@{$_POST['ListUrlRegionRegex']}";
		//列表参数 end====================================
		
		//详细页 start===================================
		$data['DetailUrlPara'] = $_POST['DetailUrlRegex'];
		//详细页 end====================================
		
		//字段规则start==========================================
		$items = array();
		$ai = $_POST['AttributeID'];
		$ar = $_POST['AttributeRegex'];
		$n = is_array($ai) ? count($ai) : 0;
		for($i=0; $i < $n; $i++){
			$items[] = "{$ai[$i]}###{$ar[$i]}";
		}
		$data['FieldPara'] = implode('@@@', $items);
		//字段规则end=========================================
		
		//替换规则start=========================================
		$items = array();
		$st = $_POST['SearchText'];
		$rt = $_POST['ReplaceText'];
		$n = is_array($st) ? count($st) : 0;
		for($i=0; $i < $n; $i++){
			if( !empty($st[$i]) ){
				$items[] = "{$st[$i]}###{$rt[$i]}";
			}
		}
		$data['ReplacePara'] = implode('@@@', $items);
		//替换规则end==========================================
		
		//其它参数 start=====
		if( !is_numeric( $_POST['MaxCount']  ) ){
			$this->ajaxReturn(null, '最大采集数必须为数字!' , 0);
		}
		
		if( !is_numeric( $_POST['TimeTnterval']  ) ){
			$this->ajaxReturn(null, '时间间隔必须为数字!' , 0);
		}
		//字符编码0、时间间隔1、采集顺序2、自动上传图片3、自动上传flash4、是否审核5、浏览器标识6、最大采集数量7
		//自动提取缩略图8、测试采集Url9、是否保存重复标题10
		//内容分页类型11、内容分页区域规则12、内容分页URL地址规则13
		$data['OtherPara'] = "{$_POST['Charset']}@@@{$_POST['TimeTnterval']}@@@{$_POST['CollectOrder']}";
		$data['OtherPara'] .= "@@@{$_POST['AutoUploadImage']}@@@{$_POST['AutoUploadFlash']}@@@{$_POST['EnableCheck']}";
		$data['OtherPara'] .= "@@@{$_POST['UserAgent']}@@@{$_POST['MaxCount']}@@@{$_POST['AutoThumbFirst']}";
		$data['OtherPara'] .= "@@@{$_POST['TestDetailUrl']}@@@{$_POST['EnableTitle']}";
		$data['OtherPara'] .= "@@@{$_POST['PageType']}@@@{$_POST['PageRegionRegex']}@@@{$_POST['AllPageUrlRegex']}@@@{$_POST['NextPageUrlRegex']}";
		//其它参数 end=====
		
		$data['CreateTime'] = date('Y-m-d H:i:s');
		$data['LanguageID'] = get_language_id();
		return $data;
	}

	/**
	 * 详细页采集测试
	 */
	function collectList(){
		header("Content-Type:text/html; charset=utf-8");
		$result = $this->_collectList( $_POST );
		if( is_array($result) ){
			$this->ajaxReturn($result, '采集成功' , 1);
		}else{
			$this->ajaxReturn(null, $result , 0);
		}
	}
	
	/**
	 * 字段测试采集
	 */
	function testField(){
		header("Content-Type:text/html; charset=utf-8");
        $url = trim($_POST['TestDetailUrl']);
		if( empty($url) ){ //随机采集
			if( empty($_POST['DetailUrlRegex']) ){  //直接从列表页采集
				$url = $_POST['ListUrl'];  //传递过来的本身就是一个随机列表页
			}else{
				$result = $this->_collectList( $_POST );
				if( is_array($result) ){
					//随机获取一个列表页地址采集
					$index = rand(0, count($result)-1 );
					$url = $result[ $index ];
				}else{
					$this->ajaxReturn($result, '' , 0);
				}
			}
		}
		$data = $this->_collectContent($url, $_POST['FieldInfo'], $_POST['ReplacePara'], $_POST);
		if( is_array($data) ){
			$this->ajaxReturn($data, $url , 1);  //返回一个关联数组
		}else{
			$this->ajaxReturn($data, $url , 0);
		}
	}
	
	/**
	 * 采集内容
	 */
	function collectContent(){
		header("Content-Type:text/html; charset=utf-8");
		$url = $_POST['DetailUrl'];
		$fieldInfo = $_POST['FieldInfo'];
		$ReplacePara = $_POST['ReplacePara'];
		$result = $this->_collectContent($url, $fieldInfo, $ReplacePara, $_POST);
		if( is_array($result) ){
			//初始化默认值============================
			$result['InfoTime'] = isset($result['InfoTime']) ? $result['InfoTime'] : date('Y-m-d H:i:s');
			$result['ChannelID'] = $_POST['ChannelID'];
			$result['MemberID'] = (int)session('AdminMemberID');
			$result['ReadLevel'] = '';
			$result['LabelID'] = '';
			$result['IsCheck'] = $_POST['EnableCheck']==1 ? 0 : 1;
			//===================================
			
			$m = D('Admin/Info');
			if( $result['InfoTitle'] != "" && $_POST['EnableTitle']==0 ){
				$where['InfoTitle'] = $result['InfoTitle'];
				$where['ChannelID'] = $result['ChannelID'];
				$bExist = $m->where($where)->count();
				if($bExist > 0){ //重复判断
					$this->ajaxReturn('', "标题在目标频道已经存在，跳过" , 2);
				}
			}
			
			$nolist = array('ChannelID', 'SpecialID', 'MemberID', 'ReadLevel', 'InfoOrder', 'InfoTime', 'InfoHit', 'LabelID',
					'IsEnable', 'IsCheck', 'LanguageID', 'IsHtml', 'Html');
			$t = parse_url( $url );
			$prefix = $t['port'] > 0 ? "{$t['scheme']}://{$t['host']}:{$t['port']}/" : "{$t['scheme']}://{$t['host']}/";
			//自动上传图片 start====================================
			if( $_POST['AutoUploadImage'] == 1){
				foreach ($result as $k=>$v){
					if( !in_array($k, $nolist) && stripos($v , '<img') !== false ){
						$result[$k] = $this->_autoUploadImage( $v , $prefix); //自动上传图片
					}
				}
			}
			//自动上传图片 end=====================================
			
			//自动获取第一个图片作为缩略图 start====================================
			if( $_POST['AutoThumbFirst'] == 1 && empty($result['InfoPicture']) ){
			    $uploadDir = GetUploadDir();
				foreach ($result as $k=>$v){
					if( !in_array($k, $nolist) && stripos($v , '<img') !== false ){
						$imageList = yd_extract_image($v, 2);
						if( $imageList && !empty( $imageList[0] ) ){
							$imageUrl = $imageList[0];
							if( $_POST['AutoUploadImage'] == 1){
								if($GLOBALS['Config']['THUMB_ENABLE'] == 1){
									//远程图片已经上传，DocumentRoot：如：D:\www,   WebInstallDir:/YoudianCMS/
									$thumbFile = makeThumb($this->DocumentRoot.$imageUrl);
									$result['InfoPicture'] = $this->WebInstallDir.substr($thumbFile, 2);
								}else{
									$result['InfoPicture'] = $imageUrl;
								}
							}else{
								//远程图片没有上传
								if( strtolower( substr($imageUrl, 0, 7) ) != 'http://' && strtolower( substr($imageUrl, 0, 6)) != 'ftp://'
										&& strtolower( substr($imageUrl, 0, 8) ) != 'https://' ){ //获取的图片地址不是绝对地址
									$imageUrl = $prefix.ltrim($imageUrl, '/');
								}
								$grabFile = yd_grab_image($imageUrl); //保存图片到本地，仅返回文件名
								$thumbFile = makeThumb( $uploadDir.$grabFile ); //自动生成自定大小的缩略图，和加水印
								$result['InfoPicture'] = $this->WebInstallDir.substr($thumbFile, 2);
							}
							break;
						}
					}
				}
			}
			//自动获取第一个图片作为缩略图 end=====================================
			$result['LanguageID'] = get_language_id();
			$b = $m->add( $result );
			if($b){
				$this->ajaxReturn('', '采集数据成功，并成功保存到数据库' , 1);
			}else{
				$this->ajaxReturn('', "采集数据成功，但入库失败！" , 0);
			}
		}else{
			$this->ajaxReturn(null, $result , 0);
		}
	}
	
	/**
	 * 自动上传内容里的图片
	 * @param string $content
	 * @param string $prefix 网址，后面带/，如：http://www.xx.com:88/
	 */
	private function _autoUploadImage($content, $prefix){
		$imageList = yd_extract_image($content, 2);
		if( $imageList === false ) return $content;
		$fileList = array();
		$protocal = yd_is_https() ? 'https:' : 'http:';
        $uploadDir = GetUploadDir();
        $uploadDir1 = substr($uploadDir, 1);
		foreach ($imageList as $v){
            if( '//'==substr($v, 0, 2) ){
                $v = "{$protocal}{$v}";
            }elseif( strtolower( substr($v, 0, 7) ) != 'http://' && strtolower( substr($v, 0, 6)) != 'ftp://'
					&& strtolower( substr($v, 0, 8) ) != 'https://'){ //获取的图片地址不是绝对地址
				$v = $prefix.ltrim($v,'/');
			}
			$grabFile = yd_grab_image($v);
			$fileList[] = __ROOT__.$uploadDir1.$grabFile; //上传图片
			addWater($uploadDir.$grabFile); //添加水印
		}
		$content = str_ireplace($imageList, $fileList, $content);
		return $content;
	}
	
	/**
	 * 采集详细页的内容
	 * @param string $url 详细页
	 * @param string $regex 匹配规则字符串信息，格式：id###fieldname###regex@@@id###fieldname###regex
	 * @param string $replace 替换规则字符串，格式：search###replace@@@search###replace
	 * @param unknown_type $r
	 */
	private function _collectContent($url, $regex, $replace = false, $options = array() ){
		if( empty($url) || strlen($url) < 10 ){
			$errmsg = 'URL地址无效';
			return 	$errmsg;
		}

        $fieldRule = explode('###', $regex);

        if(false !== stripos($fieldRule[2], 'function')){
            $errmsg = '自定义函数存在安全隐患，不再支持！';
            return $errmsg;
        }


		$this->_sleep( $options['TimeTnterval'] );
		@set_time_limit(200); //非安全模式下也不一定生效，在linux下生效
		
		$options['Url'] = $url;
		$httpHeader = $this->_getOptions( $options );
		//step1:获取页面内容
		$content = yd_curl_get($url, false, 30, $httpHeader); //超时时间：30s
		$content = $this->ToUtf8($content, $options['Charset']);
		if( empty($content) ) {
			$errmsg = '无法获取页面内容';
			return 	$errmsg;
		}
		
		//必须放在替换内容前，否则分页条有可能被替换掉了
		if($options['PageType'] == 1){
			$page = $this->_getPage($content, $options);
		}else{
			if( !empty($options['NextPageUrlRegex']) ){
				$nextPageUrl = $this->_match($options['NextPageUrlRegex'], $content);
			}else{
				$nextPageUrl = '';
			}
		}
		
		//step2:对原始内容进行替换
		if( !empty($replace) ){
			$replaceArray = (array)explode('@@@', $replace);
			foreach ($replaceArray as $it){
				$tt = (array)explode('###', $it);
				$pattern = $this->_prePattern( $tt[0] );
				$replacement = get_magic_quotes_gpc() ? stripcslashes($tt[1]) : $tt[1];
				$content = preg_replace($pattern, $replacement, $content);
			}
		}
		
		//step3:根据规则获取字段内容
		$result = array();
		$regex= (array)explode('@@@', $regex);
		foreach ($regex as $it){
			$tt = (array)explode('###', $it);
			$FieldName = $tt[1];
			$FieldPattern = $tt[2];
			$match = $this->_match($FieldPattern, $content);
			//没有匹配则不做任何处理
			if( $match !== false ){
				$result[ $FieldName ] = $match;
			}
		}
		
		//获取分页数据=============================================
		if($options['PageType'] == 1){ //全部列出的分页列表
			if(!empty($page)){
				foreach ($page as $pageUrl){
					$content = $this->_getPageContent($pageUrl, $replace, $options);
					if(!empty($content)){
						foreach ($regex as $it){
							$tt = (array)explode('###', $it);
							$FieldName = $tt[1];
							//目前只有InfoContent支持多页
							if( $FieldName == 'InfoContent'){
								$FieldPattern = $tt[2];
								$match = $this->_match($FieldPattern, $content);
								//没有匹配则不做任何处理
								if( $match !== false ){
									$result[ $FieldName ] .= $match;
								}
							}
						}
					}
					$this->_sleep( $options['TimeTnterval'] );
				}
			}
		}else{  //上下页形式或不完整的分页列表
			while(!empty( $nextPageUrl )){
				$content = $this->_getPageContent($nextPageUrl, $replace, $options);
				if(!empty($content)){
					foreach ($regex as $it){
						$tt = (array)explode('###', $it);
						$FieldName = $tt[1];
						//目前只有InfoContent支持多页
						if( $FieldName == 'InfoContent'){
							$FieldPattern = $tt[2];
							$match = $this->_match($FieldPattern, $content);
							//没有匹配则不做任何处理
							if( $match !== false ){
								$result[ $FieldName ] .= $match;
							}
						}
					}
				}
				$nextPageUrl = $this->_match($options['NextPageUrlRegex'], $content);
				$this->_sleep( $options['TimeTnterval'] );
			}
		}
		//======================================================
		
		if( empty($result) ) {
			$errmsg = '没有匹配任何内容';
			return 	$errmsg;
		}
		return $result;
	}
	
	/**
	 * 获取分页内容并替换
	 * @param string $url 分页url
	 * @param array $options 参数
	 */
	private function _getPageContent($url, $replace, $options){
		if( empty($url) || strlen($url) < 10 ) return '';
		$options['Url'] = $url;
		$httpHeader = $this->_getOptions( $options );
		//step1:获取页面内容
		$content = yd_curl_get($url, false, 30, $httpHeader); //超时时间：30s
		$content = $this->ToUtf8($content, $options['Charset']);
		if( empty($content) ) return '';
		
		//step2:对原始内容进行替换
		if( !empty($replace) ){
			$replace = (array)explode('@@@', $replace);
			foreach ($replace as $it){
				$tt = (array)explode('###', $it);
				$pattern = $this->_prePattern( $tt[0] );
				$replacement = get_magic_quotes_gpc() ? stripcslashes($tt[1]) : $tt[1];
				$content = preg_replace($pattern, $replacement, $content);
			}
		}
		return $content;
	}
	
	private function _getPage($content, $options){
		if( empty( $options['AllPageUrlRegex']) || empty($content) ) return false;
		$allPage = false;
		//获取链接区域范围
		if( !empty( $options['PageRegionRegex']) ){
			$pageRegion = $this->_match($options['PageRegionRegex'], $content);
		}else{
			$pageRegion = $content;
		}
		
		$pattern = $options['AllPageUrlRegex'];
		//为了匹配更加准确，将{*}替换为{a}(表示没有引号的字符串，更符合url)，url不可能有引号
		$pattern = str_replace('{*}', '{a}', $pattern);
		$pattern = $this->_prePattern($pattern);
		if( preg_match_all($pattern,$pageRegion, $match) ){
			$match = array_unique($match[0]);
			//去重处理以后索引不是连续的，必须变成连续的，返回json才是数组，否则是对象
			$t = parse_url( $options['ListUrl'] );
			if( $t['port'] > 0 ){
				$prefix = "{$t['scheme']}://{$t['host']}:{$t['port']}/";
			}else{
				$prefix = "{$t['scheme']}://{$t['host']}/";
			}

			$base_url = $options['ListUrl'];
			foreach ($match as $v){
				if( strtolower( substr($v, 0, 7) ) != 'http://' && strtolower( substr($v, 0, 8) ) != 'https://'){
					if( substr($v, 0, 1) == '/' ){
						$allPage[] = $prefix.ltrim($v, '/');
					}else{  //如果是相对路径，需要转换为绝对路径
						$allPage[] = yd_rel2abs($base_url, $v);
					}
				}else{
					$allPage[] = $v; //如果采集的是绝对地址，则不加网址前缀
				}
			}
		}
		return $allPage;
	}
	
	/**
	 * 智能匹配
	 * @param string $pattern
	 * @param string $content
	 */
	private function _match($pattern, $content){
		if( get_magic_quotes_gpc() ){
			$pattern = stripcslashes($pattern);
		}
		//调用自定义扩展函数
        if(substr($pattern, 0, 6) == 'caiji_'){
            if(function_exists($pattern)){
                $result = @$pattern($content);
                return $result;
            }
        }

        //（1）判断是否是专家模式，以function
		if( strtolower(substr($pattern, 0, 8)) == 'function'){
		    return false; //存在安全隐患，不再支持
		    if(false === stripos($pattern, 'function getfield($content)')){
		        return false;
		    }
		    $codeLength = strlen($pattern);
		    if($codeLength>120) return false; //代码长度不超过120字符，防止恶意攻击
			$pos1 = stripos($pattern, '{');
			$pos2= strrpos($pattern, '}');
			if( $pos1 === false || $pos2 === false ) return false;
			$code = substr($pattern, $pos1+1, $pos2-$pos1-1);
			if( strlen($code) < 7 ) return false;
			//过滤$code中的恶意代码
			$bad = array(  //不能有点号，防止字符串，实现动态调用函数
                '.',
			    'delete ','copy', 'select ', 'D(\'', 'D("', 'M(\'', 'M("', 'include', 'require','unlink', 'rmdir','mkdir',
                'fopen' ,'fwrite','fputs','link','filesize', 'mysql_connect', 'mysqli', 'file_put_contents', '$_GET', '$_POST', '$_REQUEST',
                'eval', 'system', 'exec', 'shell_exec', 'passthru', 'popen', '`', 'assert', 'preg_replace', 'call_user_func',
                'echo ', 'file_get_contents', 'phpinfo', 'proc_', 'create_function', 'array_map', 'base64_', 'md5',

            );
			$code = str_ireplace($bad, '-', $code); //不能替换为空，有漏洞
			
			//创建自定义函数，并执行，必须添加@，否则当$code中存在语法错误时，会抛出异常
			$myfunction = @create_function('$content', $code);
			if( $myfunction === false ){
				$result = false; //说明函数存在语法错误
			}else{
				$result = @$myfunction( $content );
			}
			$result = trim($result);
			return $result;
		}
		
		//(2)Jquery表达式模式，以$( 开头
		if( substr($pattern, 0, 2) == '$(' && substr(rtrim($pattern,';'), -1) == ')' ){
			//支持的方法有：html()、val()、text()、attr('xx')
			$pos = strrpos($pattern, '.');
			if( $pos === false ) return false;
			$pqMethodList = array('html', 'val', 'text', 'attr');
			$firstBracketPos = strpos($pattern, '(', $pos);
			if( $firstBracketPos === false ) return false;
			$pqMethodName = strtolower( substr($pattern, $pos+1, $firstBracketPos-$pos-1) );
			if( !in_array($pqMethodName, $pqMethodList) ){
				return false;
			}
			$pqExpress = substr($pattern, 3, $pos - 5);
			if( strlen($pqExpress) < 1 ) return false;
			import("@.Common.PhpQuery");
			//必须改变编码，否则phpQuery自动获取$content的charset（当前已经是utf-8）
			$content = preg_replace('/<meta.+?charset=[^\w]?([-\w]+)/i', 'utf-8', $content);
			phpQuery::newDocument($content);
			if( $pqMethodName == 'attr'){ //只有attr带参数
				$lastBracketPos = strrpos($pattern, ')');
				if( $lastBracketPos === false ) return false;
				$attrStart = $firstBracketPos+2;
				$attrLen = $lastBracketPos-$firstBracketPos-3;
				$attrName = substr($pattern, $attrStart, $attrLen);
				$result = pq($pqExpress)->attr( $attrName );
			}else{
				$result = pq($pqExpress)->$pqMethodName();
			}
			$result = trim($result);
			return $result;
		}
		
		//（3）常量模式：没有通配符，直接返回正则表达式本身
		if( strpos($pattern, '{*}') === false && strpos($pattern, '{n}') === false ){
			return $pattern;
		}
		
		//（4）智能匹配	、正则表达式匹配
		$result = false;
		$delimiter = (strpos($pattern, '{*}') !== false) ? '{*}' : '{n}';
		$tag = explode($delimiter, $pattern);
		$right_tag = str_replace(array('/','>'), '', $tag[1]);
		if( substr($tag[0], 0, 1) == '<' && substr($tag[0],-1) == '>' && substr($tag[1], 0, 1) == '<' && substr($tag[1],-1) == '>' 
				&& 0 === stripos($pattern, $right_tag) ){ //标签对方式匹配判断
			$result = yd_tagpos($content, $tag[0]);
		}else{ //正则表达式匹配（即前后截取字符串）
			$pattern = $this->_prePattern( $pattern );
			if( preg_match($pattern, $content, $match) ){
				$result = $match[1];
			}
		}
		$result = trim($result);
		return $result;
	}
	
	/**
	 * 获取采集网页是http参数
	 * @param array $p  Url:表示当前采集的地址
	 */
	private function _getOptions($p = array()){
		$options = array();
        $options['AutoGzip'] = 1; //自动解压缩gzip网页内容
		//设置UserAgent
		if( isset($p['UserAgent']) ){
			$userAgent = $this->_userAgent[ $p['UserAgent'] ];
			if( !empty($userAgent) ){
				$options['CURLOPT_USERAGENT'] = $userAgent;
			}
		}
		//设置来源地址
		if( isset($p['Url']) ){
			$t = parse_url( $p['Url'] );
			$options['CURLOPT_REFERER'] = "{$t['scheme']}://{$t['host']}";
		}
		return $options;
	}
	
	/**
	 * 采集详细页数组，返回数组表示采集成功，否则返回错误消息
	 * @param array $p
	 */
	private function _collectList( $p = array() ){
		$p['Url'] = $p['ListUrl'];
		$httpHeader = $this->_getOptions( $p );
		$content = yd_curl_get($p['ListUrl'], false, 30, $httpHeader ); //超时时间：30s
        //$content = $this->ToUtf8($content, $p['Charset']);
        //内容为空直接返回false
		if( empty($content) ) {
			$errmsg = '无法获取页面内容';
			return 	$errmsg;
		}
		
		//链接区域范围指定
		if( !empty( $p['ListUrlRegionRegex'] ) ){
			$content = $this->_match($p['ListUrlRegionRegex'], $content);
			if($content === false){
				$errmsg = '链接区域规则无效或不匹配';
				return $errmsg;
			}
			if(strlen($content) <= 0){
				$errmsg = '链接区域为空';
				return $errmsg;
			}
			/* 这种方式存在bug
			$posStart = strpos($content, $rr[0]);
			$posEnd = strpos($content, $rr[1], $posStart);
			if( $posStart === false || $posEnd === false ){
				$errmsg = '链接区域规则无效或不匹配';
				return 	$errmsg;
			}
			$content = substr($content, $posStart, $posEnd - $posStart + 1);
			*/
		}
		
		$this->_sleep( $p['TimeTnterval'] );

		$pattern = $p['DetailUrlRegex'];
		//if(strpos($pattern,'{*}') === false && strpos($pattern,'{(\d)+}') === false){
		//	return array($pattern);
		//}
		//为了匹配更加准确，将{*}替换为{a}(表示没有引号的字符串，更符合url)，url不可能有引号
		$pattern = str_replace('{*}', '{a}', $pattern);
		$pattern = $this->_prePattern($pattern);
		if( preg_match_all($pattern,$content, $match) ){
			$match = array_unique($match[0]);
		}else{
			$errmsg = '没有匹配任何详细页URL地址';
			return 	$errmsg;
		}
		
		//去重处理以后索引不是连续的，必须变成连续的，返回json才是数组，否则是对象
		$t = parse_url( $p['ListUrl'] );
		if( $t['port'] > 0 ){
			$prefix = "{$t['scheme']}://{$t['host']}:{$t['port']}/";
		}else{
			$prefix = "{$t['scheme']}://{$t['host']}/";
		}

        $result = array();
		$base_url = $p['ListUrl'];
		foreach ($match as $v){
			if( strtolower( substr($v, 0, 7) ) != 'http://' && strtolower( substr($v, 0, 8) ) != 'https://'){
				if( substr($v, 0, 1) == '/' ){
					$result[] = $prefix.ltrim($v, '/');
				}else{  //如果是相对路径，需要转换为绝对路径
					$result[] = yd_rel2abs($base_url, $v);
				}
			}else{
				$result[] = $v; //如果采集的是绝对地址，则不加网址前缀
			}
		}
		return $result;
	}
	
	//随机延时，time:表示延时时间：单位：毫秒
	private function _sleep( $time ){
		if( $time > 0 ){ //传入的是毫秒
			//随机增加一个毫秒数[1, $time*50%]，50%可以保证更加随机
			$rand_time = rand(1, $time * 0.5);
			$time = ( $time + $rand_time ) * 1000; //转化为微秒
			usleep( $time ); //1毫秒=1000微秒
		}
	}
	
	/**
	 * 将网页代码转化为utf-8编码
	 * @param string $content 网页内容
	 * @param string $charset 网页编码
	 */
	private function ToUtf8($content, $charset='utf-8'){
		$charset = strtolower($charset);
		if( $charset == 'utf-8'){
			return $content;
		}
		
		if( $charset == 'auto' ){  //自动检测网页编码meta标签
			//<meta http-equiv="Content-Type" content="text/html; charset=big5" />
			//<meta charset="big5">
			$charset = preg_match('/<meta.+?charset=[^\w]?([-\w]+)/i',$content,$t) ? strtolower($t[1]) : '';
			if( $charset == '' ){
				//获取编码失败，有2中方法再检测编码，即：
				//（1）mb_detect_encoding
				//（2）从http响应头Content-type获取编码
			}else if( $charset != 'utf-8'){
				$content = iconv($charset, 'utf-8//IGNORE', $content);
			}
		}else{
			$content = iconv($charset, 'utf-8//IGNORE', $content);
		}
		return $content;
	} 
	
	//预处理匹配规则
	private function _prePattern($pattern){
		if( get_magic_quotes_gpc() ){
			$pattern = stripcslashes($pattern);
		}
		$pattern = preg_quote($pattern);
		$pattern = str_replace('\{\*\}', '(.*?)', $pattern);
		$pattern = str_replace('\{n\}', '(\d+)', $pattern);
		$pattern = str_replace('\{a\}', '([^\'\"]*?)', $pattern);
		
		//s如果设定了这个修正符，那么，被匹配的字符串将视为一行来看，包括换行符，换行符将被视为普通字符串。
		$pattern = "~".$pattern."~is";
		return $pattern;
	}
	
	/**
	 * 测试采集分页
	 */
	function testPage(){
		header("Content-Type:text/html; charset=utf-8");
		$url = trim($_POST['TestDetailUrl']);
		if( empty($url) ){ //随机采集
			if( empty($_POST['DetailUrlRegex']) ){  //直接从列表页采集
				$url = $_POST['ListUrl'];  //传递过来的本身就是一个随机列表页
			}else{
				$result = $this->_collectList( $_POST );
				if( is_array($result) ){
					//随机获取一个列表页地址采集
					$index = rand(0, count($result)-1 );
					$url = $result[ $index ];
				}else{
					$this->ajaxReturn($result, '' , 0);
				}
			}
		}
		$data = false; //存储分页url地址
		$content = $this->_getPageContent($url, $_POST['ReplacePara'], $_POST);
		if($_POST['PageType'] == 1){
			$data = $this->_getPage($content, $_POST);
		}else{
			if( !empty($_POST['NextPageUrlRegex']) ){
				$pageUrl = $this->_match( $_POST['NextPageUrlRegex'], $content );
				while( !empty($pageUrl) ){
					$data[] = $pageUrl;
					$content = $this->_getPageContent($pageUrl, $_POST['ReplacePara'], $_POST);
					$pageUrl = $this->_match( $_POST['NextPageUrlRegex'], $content );
				}
			}
		}
		
		if( is_array($data) ){
			$this->ajaxReturn($data, $url , 1);
		}else{
			$this->ajaxReturn($data, $url , 0);
		}
	}
}
?>