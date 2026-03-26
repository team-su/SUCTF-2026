<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
if (!defined('APP_NAME')) exit();
class YdWxMsg{
	/**
	 * 构造文本回复消息
	 * @param string $toUser 目标用户
	 * @param string $fromUser 源用户
	 * @param string $content 文本消息内容
	 * @param int $createTime 取-1表示，表示取当前时间time()
	 */
	static function constructTextReplyMsg($toUser, $fromUser, $content, $createTime = -1){
		$msg = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[text]]></MsgType>
						<Content><![CDATA[%s]]></Content>
					  </xml>";
		if( $createTime === -1 ){
			$createTime = time();
		}
		$str = sprintf($msg, $toUser, $fromUser, $createTime, $content);
		return $str;
	}
	
	/**
	 * 构造音乐回复消息
	 * @param string $toUser
	 * @param string $fromUser
	 * @param string $title
	 * @param string $description
	 * @param string $musicUrl
	 * @param string $HQMusicUrl 为空则等于$musicUrl
	 * @param int $createTime  取-1表示，表示取当前时间time()
	 */
	static function constructMusicReplyMsg($toUser, $fromUser, $title, $description, $musicUrl, $HQMusicUrl = '', $createTime = -1){
		$msg = "<xml>
						  <ToUserName><![CDATA[%s]]></ToUserName>
						  <FromUserName><![CDATA[%s]]></FromUserName>
						  <CreateTime>%s</CreateTime>
						  <MsgType><![CDATA[music]]></MsgType>
						  <Music>
								<Title><![CDATA[%s]]></Title>
								<Description><![CDATA[%s]]></Description>
								<MusicUrl><![CDATA[%s]]></MusicUrl>
								<HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
						  </Music>
					  </xml>";
		if( $createTime === -1 ){
			$createTime = time();
		}
		$musicUrl=YdWxMsg::parseResourceUrl($musicUrl);
		if( $HQMusicUrl == '' ){
			$HQMusicUrl = $musicUrl;
		}else{
			$HQMusicUrl =YdWxMsg::parseResourceUrl($HQMusicUrl);
		}
		$str = sprintf($msg, $toUser, $fromUser, $createTime, $title, $description, $musicUrl, $HQMusicUrl);
		return $str;
	}
	
	/**
	 * 构造标准图文回复消息
	 * @param string $toUser
	 * @param string $fromUser
	 * @param array $article 标准图文二维数组：Title,Description,PicUrl,Url
	 * @param int $createTime   取-1表示，表示取当前时间time()
	 */
	static function constructPicTextReplyMsg($toUser, $fromUser, $article, $createTime = -1){
		if( !is_array($article) ) return false;
		if( count($article) <= 0 ) return false;
		$msg = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[news]]></MsgType>
		<ArticleCount>%s</ArticleCount>
		<Articles>";
		$articleCount = count($article);
		if( $articleCount > 10 ) $articleCount = 10;
        $description = '';
		for($i=0; $i < $articleCount; $i++){
			$title = $article[$i]['Title'];
			if($articleCount>1){
				$description = $article[$i]['Description'];
			}
			$picUrl=YdWxMsg::parseResourceUrl( $article[$i]['PicUrl'] );
			if(!empty($article['Url'])){
				$url = $article['Url'];
				$url .= (strpos($url, '?') === false ) ? '?' : '&';
				$url .= "fu=$toUser";
			}else{
				$url = '';
			}

			$msg .= "<item>
			<Title><![CDATA[$title]]></Title>
			<Description><![CDATA[$description]]></Description>
			<PicUrl><![CDATA[$picUrl]]></PicUrl>
			<Url><![CDATA[$url]]></Url>
			</item>";
		}
		$msg .="</Articles></xml>";
		if( $createTime === -1 ){
		$createTime = time();
		}
		$str = sprintf($msg, $toUser, $fromUser, $createTime, $articleCount);
		return $str;
	}
	
	/**
	 * 构造信息图文回复消息
	 * @param string $toUser
	 * @param string $fromUser
	 * @param array $info 数据库直接返回的图文二维数组
	 * @param int $createTime   取-1表示，表示取当前时间time()
	 */
	static function constructInfoReplyMsg($toUser, $fromUser, $info, $createTime = -1){
		if( !is_array($info) ) return false;
		if( count($info) <= 0 ) return false;
		$msg = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[news]]></MsgType>
		<ArticleCount>%s</ArticleCount>
		<Articles>";
		
		$articleCount = count($info);
		if( $articleCount > 10 ) $articleCount = 10;
        $description = '';
		for($i=0; $i < $articleCount; $i++){
			$title = $info[$i]['InfoTitle'];
			if($articleCount>1){
				$description = $info[$i]['InfoSContent'];
			}
			$picUrl = YdWxMsg::parseResourceUrl( $info[$i]['InfoPicture'] );
			$url = WxInfoUrl($info[$i]['InfoID'], $info[$i]['Html'], $info[$i]['LinkUrl'], false, $info[$i]['ChannelID']);
			$url .= (strpos($url, '?') === false ) ? '?' : '&';
			$url .= "fu=$toUser";
			$msg .= "<item>
				<Title><![CDATA[$title]]></Title>
				<Description><![CDATA[$description]]></Description>
				<PicUrl><![CDATA[$picUrl]]></PicUrl>
				<Url><![CDATA[$url]]></Url>
			</item>";
		}
		$msg .="</Articles></xml>";
		if( $createTime === -1 ){
			$createTime = time();
		}
		$str = sprintf($msg, $toUser, $fromUser, $createTime, $articleCount);
		return $str;
	}
	
	/**
	 * 构造频道信息图文回复消息
	 * @param string $toUser
	 * @param string $fromUser
	 * @param array $channel 数据库直接返回的频道列表二维数组
	 * @param int $createTime    取-1表示，表示取当前时间time()
	 */
	static function constructChannelReplyMsg($toUser, $fromUser, $channel, $n, $createTime = -1){
		if( !is_array($channel) ) return false;
		if( count($channel) <= 0 ) return false;
		$msg = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[news]]></MsgType>
		<ArticleCount>%s</ArticleCount>
		<Articles>";
		$ChannelCount = count($channel);
		if( $n > 10 ) $n = 10;
		if( $n > $ChannelCount ) $n = $ChannelCount;
        $description = '';
		for($i=0; $i < $n; $i++){
			$title = $channel[$i]['ChannelName'];
			//多图文消息时，description无效
			if($n>1){
				$description = $channel[$i]['ChannelSContent'];
			}
			if($i==0){ //第一个消息都是显示代表图片
				$picUrl = $channel[$i]['ChannelPicture'];
			}else{
				$picUrl = !empty($channel[$i]['ChannelIcon']) ? $channel[$i]['ChannelIcon'] : $channel[$i]['ChannelPicture'];
			}
			$picUrl = YdWxMsg::parseResourceUrl( $picUrl );
			
			$url = WxChannelUrl($channel[$i]['ChannelID'], $channel[$i]['Html'], $channel[$i]['LinkUrl']);
			$url .= (strpos($url, '?') === false ) ? '?' : '&';
			$url .= "fu=$toUser";
			
			$msg .= "<item>
			<Title><![CDATA[$title]]></Title>
			<Description><![CDATA[$description]]></Description>
			<PicUrl><![CDATA[$picUrl]]></PicUrl>
			<Url><![CDATA[$url]]></Url>
			</item>";
		}
		$msg .="</Articles></xml>";
		if( $createTime === -1 ){
			$createTime = time();
		}
		$str = sprintf($msg, $toUser, $fromUser, $createTime, $n);
		return $str;
	}
	
	/**
	 * 返回指定AppID抽奖应用的抽奖信息图文回复消息
	 * @param string $toUser
	 * @param string $fromUser
	 * @param array $appID 抽奖应用ID
	 * @param int $createTime    取-1表示，表示取当前时间time()
	 */
	static function constructLotteryAppReplyMsg($toUser, $fromUser, $appID, $createTime = -1){
		if( $appID == '' ) return false;
		$msg = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[news]]></MsgType>
		<ArticleCount>%s</ArticleCount>
		<Articles>";
		
		$m = D('Admin/WxApp');
		$data = $m->findLottery($appID);
		$title = $data['AppName'];
		//活动简介
		$description = empty($data['LotteryIntroduction']) ? $data['LotteryDescription'] : $data['LotteryIntroduction'];
		$picUrl = YdWxMsg::parseResourceUrl( $data['LotteryStartPicture'] );		
		$url = WxLotteryUrl($appID);
		$url .= (strpos($url, '?') === false ) ? '?' : '&';
		$url .= "fu=$toUser";
		
		$msg .= "<item>
		<Title><![CDATA[$title]]></Title>
		<Description><![CDATA[$description]]></Description>
		<PicUrl><![CDATA[$picUrl]]></PicUrl>
		<Url><![CDATA[$url]]></Url>
		</item>";

		$msg .="</Articles></xml>";
		if( $createTime === -1 ){
			$createTime = time();
		}
		$str = sprintf($msg, $toUser, $fromUser, $createTime, 1);
		return $str;
	}

	//解析资源地址
	static function parseResourceUrl($url){
		$resourceUrl = '';
		if(!empty($url)){
			if( strtolower( substr($url, 0, 7) ) == 'http://' || strtolower( substr($url, 0, 8) ) == 'https://'
					|| strtolower( substr($url, 0, 6) ) == 'ftp://' ){
				$resourceUrl = $url;
			}else{
				$resourceUrl = get_web_url(true, false).$url;  //必须是绝对地址
			}
		}
		return $resourceUrl;
	}
	
	/**
	 * 构造客服转发消息
	 * @param string $toUser 目标用户
	 * @param string $fromUser 源用户
	 * @param int $createTime 取-1表示，表示取当前时间time()
	 */
	static function constructcServiceReplyMsg($toUser, $fromUser, $createTime = -1){
		$msg = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[transfer_customer_service]]></MsgType>
		</xml>";
		if( $createTime === -1 ){
			$createTime = time();
		}
		$str = sprintf($msg, $toUser, $fromUser, $createTime);
		return $str;
	}
	
	/**
	 * 构造素材（图片、语音、视频）回复消息
	 * @param string $toUser 目标用户
	 * @param string $fromUser 源用户
	 * @param string $media_id 素材ID
	 * @param int $type 素材类型
	 */
	static function constructMaterialReplyMsg($toUser, $fromUser, $media_id, $type='image', $createTime = -1){
		if( $createTime === -1 ){
			$createTime = time();
		}
		$tagname = ucfirst($type);
		$msg = "<xml>
			<ToUserName><![CDATA[{$toUser}]]></ToUserName>
			<FromUserName><![CDATA[{$fromUser}]]></FromUserName>
			<CreateTime>{$createTime}</CreateTime>
			<MsgType><![CDATA[{$type}]]></MsgType>
			<{$tagname}><MediaId><![CDATA[{$media_id}]]></MediaId></{$tagname}>
		</xml>";
		return $msg;
	}
}
