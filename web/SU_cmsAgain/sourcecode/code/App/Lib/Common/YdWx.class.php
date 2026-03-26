<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
if (!defined('APP_NAME')) exit();
class YdWx{
	public $Token = null;
	public $AppID = null;
	public $AppSecret = null;
	public $AccessToken=null;
	public $ErrorMessage = '';
	
	//凭证获取接口
	public $CredentialApiUrl = "https://api.weixin.qq.com/cgi-bin/token";
	public $CreateMenuApiUrl = "https://api.weixin.qq.com/cgi-bin/menu/create";
	public $DeleteMenuApiUrl = "https://api.weixin.qq.com/cgi-bin/menu/delete";
	public $Code = null; 
	
	function __construct(){
		//获取配置信息
		$this->Token = $GLOBALS['Config']['WX_TOKEN'];
		$this->AppID = $GLOBALS['Config']['WX_APP_ID'];
		$this->AppSecret = $GLOBALS['Config']['WX_APP_SECRET'];
		$this->Code = array('-1'=>'系统繁忙',
				'0'=>'请求成功',     '40001'=>'验证失败',     '40002'=>'不合法的凭证类型',   '40003'=>'不合法的OpenID',
				'40004'=>'不合法的媒体文件类型',    '40005'=>'不合法的文件类型',    '40006'=>'不合法的文件大小',    '40007'=>'不合法的媒体文件id',
				'40008'=>'不合法的消息类型',  '40009'=>'不合法的图片文件大小',  '40010'=>'不合法的语音文件大小',  '40011'=>'不合法的视频文件大小',
				'40012'=>'不合法的缩略图文件大小',  '40013'=>'不合法的APPID', '40014'=>'不合法的access_token',
				'40015'=>'不合法的菜单类型',  '40016'=>'不合法的按钮个数',  '40017'=>'不合法的按钮个数', '40018'=>'不合法的按钮名字长度',
				'40019'=>'不合法的按钮KEY长度',  '40020'=>'不合法的按钮URL长度',  '40021'=>'不合法的菜单版本号',  '40022'=>'不合法的子菜单级数',
				'40023'=>'不合法的子菜单按钮个数',  '40024'=>'不合法的子菜单按钮类型',  '40025'=>'不合法的子菜单按钮名字长度','40026'=>'不合法的子菜单按钮KEY长度',
				'40027'=>'不合法的子菜单按钮URL长度', '40028'=>'不合法的自定义菜单使用用户','41001'=>'缺少access_token参数','41002'=>'缺少appid参数',
				'41003'=>'缺少refresh_token参数','41004'=>'缺少secret参数','41005'=>'缺少多媒体文件数据','41006'=>'缺少media_id参数',
				'41007'=>'缺少子菜单数据','42001'=>'access_token超时','43001'=>'需要GET请求','43002'=>'需要POST请求',
				'43003'=>'需要HTTPS请求','44001'=>'多媒体文件为空','44002'=>'POST的数据包为空','44003'=>'图文消息内容为空',
				'45001'=>'多媒体文件大小超过限制','45002'=>'消息内容超过限制','45003'=>'标题字段超过限制','45004'=>'描述字段超过限制',
				'45005'=>'链接字段超过限制','45006'=>'图片链接字段超过限制','45007'=>'语音播放时间超过限制','45008'=>'图文消息超过限制',
				'45009'=>'接口调用超过限制','45010'=>'创建菜单个数超过限制','46001'=>'不存在媒体数据','46002'=>'不存在的菜单版本',
				'46003'=>'不存在的菜单数据', '47001'=>'解析JSON/XML内容错误');
	}
	
	/**
	 * 有效性验证
	 */
	public function valid(){
		$echoStr = $_GET["echostr"];	
		//valid signature , option
		if($this->checkSignature()){
			echo $echoStr;
		}else{
		    exit(); //如果校验签名错误，就直接返回 2024-05
        }
	}
	
	/**
	 * 是否能获取凭证（启用自定义菜单后，有效）
	 */
	public function hasCredential(){
		//全局微信token缓存文件
		$filename = RUNTIME_PATH."token{$this->AppID}.php";
		if(file_exists($filename)){
			$result = include $filename;
		}else{
			$result['expires'] = -1;
			$result['access_token'] = '';
		}
		if ( empty($result['access_token']) || time() > intval($result['expires']) ){ //已经过期
			$para = array(
					"grant_type"=>"client_credential",
					"appid" => $this->AppID,
					"secret" => $this->AppSecret,
			);
			$res = yd_curl_get($this->CredentialApiUrl, $para);
			if( $res === false ) return false;
			$res = json_decode($res, true);
			//报错：{"errcode":40164,"errmsg":"invalid ip 115.29.106.192 ipv6 ::ffff:115.29.106.192, not in whitelist rid: 662f689d-713b293b-5a176be2"}
			if( !empty( $res['access_token'] ) ){
				$this->AccessToken = $res['access_token'];
				//保存全局access_token====================
				$data = array();
				$data['access_token'] = $res['access_token'];
				//expires_in:	凭证有效时间，单位：秒
				$data['expires']= time() + $res['expires_in'] - 300; 
				cache_array($data, $filename, false);
				//==================================
				return true;
			}else{
				$this->AccessToken = null;
                $this->ErrorMessage = $res['errmsg'];
				return false;
			}
		}else{ //没有过期，则直接从缓存获取token
			$this->AccessToken = $result['access_token'];
			return true;
		}
	}
	

	/**
	 * 新增永久素材
	 * @param string $filename 本地文件名，如：/cms7/Upload/1.jpg
	 */
	public function addMaterial($filename){
		if( empty($this->AccessToken) ) return false;
		$token = $this->AccessToken;
		$type = $this->getMaterialType($filename);
		if(empty($type)) return false;
		if($type == 'video'){
			$title = basename($filename);
			$introduction = date('YmdHis');
			$data['description'] = "{\"title\":\"{$title}\", \"introduction\":\"{$introduction}\"}";
		}
		//filename是网络路径，/cms7/Upload/1.jpg， DOCUMENT_ROOT：E:/WWW
		$filename = $_SERVER['DOCUMENT_ROOT'].$filename;
		if(class_exists('\CURLFile')){ //适合php版本>=5.6，需要测试是否可用
			$data['media'] = new CURLFile($filename);
		}else{ //php5.6版不支持@
			$data['media']  = "@{$filename}";
		}
		$ApiUrl = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token={$token}&type={$type}";
		$maxtime = intval(get_cfg_var('max_execution_time'));
		if($maxtime<=0){
			$maxtime = 1200;
		}
		$res = yd_curl_post($ApiUrl, $data, $maxtime);
		//返回说明:成功{"media_id":MEDIA_ID,"url":URL}视频不返回url，失败{"errcode":40007,"errmsg":"invalid media_id"}
		if( $res === false) {
			return false;
		}
		$res = json_decode($res, true);
		if( empty($res['media_id']) ) {
			$this->ErrorMessage = $res['errmsg'];
			if(APP_DEBUG){
				WriteErrLog("addMaterial失败，{$res['errcode']} {$res['errmsg']}");
			}
			return false;
		}
		$res['type'] = $type; //将素材类型返回
		return $res;
	}
	
	/**
	 * 根据文件名获取素材类型
	 */
	private function getMaterialType($filename){
		$ext = strtolower(yd_file_ext($filename));
		/*
		 素材类型（图片image、语音voice、视频video、缩略图thumb）
		图片（image）: 2M，支持bmp/png/jpeg/jpg/gif格式
		语音（voice）：2M，播放长度不超过60s，mp3/wma/wav/amr格式
		视频（video）：10MB，支持MP4格式
		*/
		$type = '';
		if( in_array($ext, array('bmp','png','jpeg','jpg','gif')) ){
			$type = 'image';
		}elseif(in_array($ext, array('mp3','wma','wav','amr')) ){
			$type = 'voice';
		}elseif(in_array($ext, array('mp4')) ){
			$type = 'video';
		}
		return $type;
	}
	
	/**
	 * 创建微信菜单
	 * @param string $json
	 */
	public function createMenu($json){
		if( empty($this->AccessToken) ) return false;
		$postUrl = $this->CreateMenuApiUrl."?access_token=".$this->AccessToken;
		$res = yd_curl_post($postUrl, $json);
		if( $res === false ) return false;
		$res = json_decode($res, true);
		if( $res['errcode'] == 0 ){
			return true;
		}else{
			return isset($res['errmsg']) ? $res['errmsg'] : false;
		}
	}
	
	public function clearMenu(){
		if( empty($this->AccessToken) ) return false;
		$para = array("access_token"=>$this->AccessToken);
		$res = yd_curl_get($this->DeleteMenuApiUrl, $para);
		if( $res === false ) return false;
		$res = json_decode($res, true);
		if( $res['errcode'] == 0 ){
			return true;
		}else{
			$errcode = $res['errcode'];
			return isset($this->Code[$errcode]) ? $this->Code[$errcode] : false;
		}
	}
	
	private function checkSignature(){
        $token = $this->Token;
	    if(empty($token) || strlen($token)<=2){
	        return false;
        }
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];
	
		$tmpArr = array($token, $timestamp, $nonce);
		//2014.3.4, 添加参数SORT_STRING，解决有时无法接收用户消息的问题
		sort($tmpArr, SORT_STRING); 
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
	
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
	
	public function responseMsg(){
	    /*
	     <xml>
            <ToUserName><![CDATA[gh_29********21]]></ToUserName>
            <FromUserName><![CDATA[o*****4-7Z**************s]]></FromUserName>
            <CreateTime>1481769005</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[李建华测试]]></Content>
            <MsgId>6364149417119100008</MsgId>
        </xml>
	     */
		//$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];	//get post data, May be due to the different environments
        $postStr = file_get_contents("php://input");
        if(APP_DEBUG) WriteErrLog("微信回调responseMsg：postStr={$postStr}");
		if (empty($postStr) ) return;
		$HasCustomerService = $GLOBALS['Config']['WX_CUSTOMER_SERVICE']; //是否启用多客服
		//用户发送消息－> 公众帐号
		$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
		$fromUsername = (string)$postObj->FromUserName;  //用户帐号：格式：oM3xZt2T5m-pPMXwEQB9snrUseSM
		$toUsername = (string)$postObj->ToUserName;          //公众帐号
		$msgType = (string)$postObj->MsgType;   //消息类型
		$createTime = (string)$postObj->CreateTime;
		$msgID = (string)$postObj->MsgId;
		$p1=$p2=$p3=$p4='';
		$replyMsg = '';
		//session('fromuser', $fromUsername); //记录当前用户微信ID，记录无效无法传递
        //过滤参数（防止注入）
        $fromUsername = YdInput::checkLetterNumber($fromUsername);
        $toUsername = YdInput::checkLetterNumber($toUsername);
        $msgType = YdInput::checkLetterNumber($msgType);
        $createTime = YdInput::checkLetterNumber($createTime);
        $msgID = YdInput::checkLetterNumber($msgID);

        if(function_exists('GzhNotify')){
            GzhNotify($postObj);
        }
		//插入微信用户信息=====================
		$member = D('Admin/Member');
		$member->updateWxUser($fromUsername);
		//================================
		import('@.Common.YdWxMsg');
		switch ($msgType){
			case 'text':  //文本消息
				$p1 = trim((string)$postObj->Content);  //文本消息内容
				$m = D('Admin/WxReply');
				$data = $m->findReply( $p1 ); //[1]用户自定义匹配
				if( empty($data) ){ //匹配优先级：[1]用户自定义匹配 [2]第三方应用匹配  [3]系统默认匹配
					$replyMsg = $this->runKeyword($fromUsername, $toUsername, $p1);
					if(false === $replyMsg){ //没有匹配到应用表关键词
						$data['keyword'] = $p1;
						$replyMsg = $this->runApp($fromUsername, $toUsername, $data);
						if( false === $replyMsg ){   //如果没有第三方应用匹配，则返回默认消息
							if( $HasCustomerService == 1){ //如果启用了多客服，则转发到多客服
								$replyMsg = YdWxMsg::constructcServiceReplyMsg($fromUsername, $toUsername);
							}else{
								$data = $m->findDefaultReply(1);   //[3]系统默认匹配
								$replyMsg = $this->getMsg($fromUsername, $toUsername, $data);
							}
						}
					}
				}else{
					$m->incKeyword($p1); //使用计数
					$data['keyword'] = $p1;
					$replyMsg = $this->getMsg($fromUsername, $toUsername, $data);
				}
				break;
			case 'image':  //图片消息
				$p1 = (string)$postObj->PicUrl;  //图片链接
				if( $HasCustomerService == 1){ //如果启用了多客服，则转发到多客服
					$replyMsg = YdWxMsg::constructcServiceReplyMsg($fromUsername, $toUsername);
				}
				break;
			case 'location': //地理位置消息
				$p1 = (string)$postObj->Location_X;  //纬度
				$p2 = (string)$postObj->Location_Y;  //经度
				$p3 = (string)$postObj->Scale;   //缩放大小
				$p4 = (string)$postObj->Label;   //位置信息
				//更新当前用户位置数据
				D('Admin/Member')->wxUpdatePosition($fromUsername, $p2, $p1);
				$m = D('Admin/WxReply');
				$data = $m->findLbsReply(1);
				if(!empty($data)){
					$data['longitude'] = $p2;
					$data['latitude'] = $p1;
					$data['keyword'] = empty($data['p4']) ? $data['p1'] : $data['p4'];
					$replyMsg = $this->runApp($fromUsername, $toUsername, $data);
				}else{
					if( $HasCustomerService == 1){ //如果启用了多客服，则转发到多客服
						$replyMsg = YdWxMsg::constructcServiceReplyMsg($fromUsername, $toUsername);
					}
				}
				break;
			case 'link': //链接消息
				$p1 = (string)$postObj->Title;
				$p2 = (string)$postObj->Description; 
				$p3 = (string)$postObj->Url;
				break;
			case 'event': //事件推送
				$p1 = (string)$postObj->Event;
				$p2 = (string)$postObj->EventKey;
				$event = strtolower($postObj->Event);
				switch ($event){
					case 'subscribe':  //订阅
						$m = D('Admin/WxReply');
						$data = $m->findSubscribeReply(1);
						$replyMsg = $this->getMsg($fromUsername, $toUsername, $data);
						//插入微信用户信息=====================
						$member = D('Admin/Member');
						$member->updateWxUser($fromUsername);
						//================================
						unset($m,$data,$member);
						break;
					case 'unsubscribe':  //取消订阅
						$member = D('Admin/Member');
						$member->updateWxUser($fromUsername, false);
						break;
					case 'click':  //自定义菜单点击事件
						$mn = D('Admin/WxMenu');
						$MenuID = (int)$postObj->EventKey; //必须强制转换，否则findMenu，返回false
						$data = $mn->findMenu( $MenuID );
						$replyMsg = $this->getMsg($fromUsername, $toUsername, $data);
						unset($mn,$data);
						break;
				}
				break;
		}
		//判断是否保存用户消息
		if( $GLOBALS['Config']['WX_SAVE_MSG'] == 1){
			$mm = D('Admin/WxMessage');
			$b = $mm->InsertMessage($msgID, $msgType, $fromUsername, $toUsername, $p1, $p2, $p3, $p4, $createTime);
		}
		
		//仅当消息不为空时，返回信息
		if( !empty($replyMsg) ){
			echo $replyMsg;
		}
	}
	
	//运行应用表中设置的关键词
	public function runKeyword($fromUsername, $toUsername, $keyword){
		$replyMsg = false;
		$m = D('Admin/WxApp');
		$where['AppKeyword'] = $keyword;
		$m->where($where)->field('AppID,AppTypeID,AppParameter');
		$data = $m->order('AppOrder asc,AppID desc')->limit(1)->select();
		if(empty($data)) return $replyMsg;
		import('@.Common.YdWxApp');
		//应用参数初始化 开始========================
		$params['fromUser'] = $fromUsername;
		$params['toUser'] = $toUsername;
		$params['parameter']['appid'] = $data[0]['AppID'];
		//应用参数初始化 结束========================
		switch($data[0]['AppTypeID']){
			case 1: //微活动 (3：微工具，4：地理位置服务：无需考虑)
				$t= explode('@@@', $data[0]['AppParameter']);
				$replyMsg = _lottery($params, $t[0]); //$t[0] : 抽奖类型
				break;
			case 2: //微投票
				$replyMsg = toupiao($params);
				break;
			case 5: //微调查
				$replyMsg = diaocha($params);
				break;
			case 6: //微会员卡
				$replyMsg = huiyuanka($params);
				break;
			default:
				$replyMsg = false;
		}
		return $replyMsg;
	}
	
	public function runApp($fromUsername, $toUsername, $data){
		$keyword = $data['keyword'];
		$appid = $data['p1'];
		$applist = include CONF_PATH.'wxapp.php';  //加载微信配置文件
		if( array_key_exists($keyword, $applist) ){
			$t = $applist[$keyword];
			$t['matches'][0]=$keyword;
		}else{
			foreach ($applist as $k=>$v){
				//$matches[0] 将包含与整个模式匹配的文本，$matches[1] 将包含与第一个捕获的括号中的子模式所匹配的文本，以此类推。
				if(substr($k, 0, 1) == '/' && preg_match($k, $keyword, $matches)){
					$t = $v;
					$t['matches'] = $matches; //保存匹配结果
					break;
				}
			}
		}
		
		if(empty($t)){
			return false;
		}else{
			$t['fromUser'] = $fromUsername;
			$t['toUser'] = $toUsername;
			//只有地理位置消息才有的参数=================
			if(!empty($data['longitude'])){
				$t['parameter']['longitude'] = $data['longitude'];
				$t['parameter']['latitude'] = $data['latitude'];
			}
			//===================================
			//对于固定关键词：$appid可能是：刮刮卡，不是数字
			$t['parameter']['appid'] = is_numeric($appid) ? $appid : -1;
			import('@.Common.YdWxApp');
			return call_user_func( $t['function'], $t);
		}
	}
	/**
	 * 返回指定回复消息的xml字符串
	 * @param array $data
	 */
	public function getMsg($fromUsername, $toUsername, $data){
		if( empty( $data) ) return false;
		import('@.Common.YdWxMsg');
		$replyMsg = false;
		switch( $data['TypeID'] ){
			case 1:  //1:文本消息
				//\r\n在微信中回显示小方框
				$data['p1']=str_replace(array("\r\n","\r"), "\n", $data['p1']);
				$replyMsg = YdWxMsg::constructTextReplyMsg($fromUsername, $toUsername, $data['p1']);
				break;
			case 2: //2:图文消息  p1表示频道ID, p2表示消息条数
				$m2 = D('Admin/Channel');
				if( $data['p1'] == 1 || $data['p1'] == 2){ //首页特殊处理，返回整个网站导航
					$channelInfo = $m2->getNavigation(0,1); //返回首页一级栏目导航
					if($data['p2'] > 1 && !empty($channelInfo[0]['ChannelSContent'])){
						$channelInfo[0]['ChannelName'] = $channelInfo[0]['ChannelSContent'];
					}
					$replyMsg = YdWxMsg::constructChannelReplyMsg($fromUsername, $toUsername, $channelInfo, $data['p2']);
					unset($m2, $channelInfo);
				}else{
					$ChannelModelID = $m2->getChannelModelID( $data['p1'] );
					if($ChannelModelID == 32 || $ChannelModelID == 33 ||  $ChannelModelID == 37){ //单页模型 和 链接模型
						if( $data['p2'] == 1 ){ //显示当前频道图文消息
							$channelInfo = $m2->where( "ChannelID=".intval($data['p1']) )->select();
						}else{//显示当前单页频道子频道图文消息
							$channelInfo = $m2->getNavigation($data['p1'], 1);
						}
						$replyMsg = YdWxMsg::constructChannelReplyMsg($fromUsername, $toUsername, $channelInfo, $data['p2']);
						unset($m2, $channelInfo);
					}else{
						$m1 = D('Admin/Info');
						$info = $m1->getInfo(0, $data['p2'], $data['p1'], 1, 1);
						$replyMsg = YdWxMsg::constructInfoReplyMsg($fromUsername, $toUsername, $info);
						unset($m1, $info);
					}
				}
				break;
			case 3: //3:音乐消息   p1表示音乐标题，p2表示音乐描述，  p3表示音乐URL
				$replyMsg = YdWxMsg::constructMusicReplyMsg($fromUsername, $toUsername, $data['p1'], $data['p2'], $data['p3']);
				break;
			case 7: //素材 p1: media_id, p2:素材本地路径
				$replyMsg = YdWxMsg::constructMaterialReplyMsg($fromUsername, $toUsername, $data['p1'], $data['p4']);
				break;
			default:  //5:第三方应用，$data['p1']： AppID, $data['p2']:Keyword，$data['p4']表应用绑定的指令
				if( substr($data['p1'], 0, 1) == '/' ){
					$data['keyword'] = $data['p4'];
				}else{
					$data['keyword'] = $data['p2'];
				}
				$replyMsg = $this->runApp($fromUsername, $toUsername, $data);
				break;
		}
		return $replyMsg;
	}	
}
