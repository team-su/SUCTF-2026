<?php
if (!defined('APP_NAME')) exit();
/**
 * 定义模板相关函数
 */
load('ydLib');
function get_slide($selector, $autoPage, $titCell, $mainCell, $autoPlay, $interTime, $delayTime, $defaultIndex, 
		$trigger, $scroll, $vis, $prevCell, $nextCell, $titOnClassName, $effect){
	$defaultIndex =   !empty($defaultIndex) ?  "defaultIndex:".$defaultIndex."," : '';
	$data = "<script type='text/javascript'>
		$(document).ready(function(){
			$('$selector').slide({
				$autoPage $titCell $mainCell $autoPlay $interTime $delayTime $defaultIndex $trigger $scroll $vis $prevCell $nextCell $titOnClassName $effect
			});
		})
	</script>";
	return $data;
}

//获取顶层菜单
//$menuOwner:  0：会员，1:管理员
//$groupid: 0:表示不受分组权限的限制
function get_menu_top($menuOwner = 0){
	$m = D('Admin/MenuTop');
	$data = $m->getMenuTop( array('MenuOwner'=>$menuOwner) );
	return $data;
}

function get_menu_group($MenuTopID = 0){
	$m = D('Admin/MenuGroup');
	$data = $m->getMenuGroup( $MenuTopID, 1);
	return $data;
}

function get_menu($menuGroupID = 0){
	$m = D('Admin/Menu');
	$data = $m->getMenu( $menuGroupID, 1);
	return $data;
}

function get_menu_operation(){
	$m = D('Admin/MenuOperation');
	$data = $m->getMenuOperationPath( ACTION_NAME, MODULE_NAME, GROUP_NAME);
	return $data;
}

//频道信息
function get_channel($parentID = 0, $depth=1, $prefix='', $idlist=false){
	$channel = D('Admin/Channel');
	$data = $channel->getChannelList($parentID, $depth, $prefix, $idlist);
	$count = is_array($data) ? count($data) : 0;
	if( empty($data) ) return false;  //当$data为false时，count($data)返回1，因此必选先判断$data是否为空
	//找出ChannelDepth最大值
	$maxDepth = -9999;
	for($j = 0; $j < $count; $j++){
		if( $data[$j]['ChannelDepth'] > $maxDepth ) {
			$maxDepth = $data[$j]['ChannelDepth'];
		}
	}
    $siteName = defined('SITE_NAME') && !empty($GLOBALS['Config']['SiteChannelShow']) ? SITE_NAME : '';
	for($i = 0; $i < $count; $i++){
        $data[$i]['ChannelName'] = $siteName.$data[$i]['ChannelName'];
		if( 33 == $data[$i]['ChannelModelID'] && trim($data[$i]['LinkUrl']) != '' ){ //33:转向链接
			if( GROUP_NAME == 'Wap' && 'http' != strtolower( substr($data[$i]['LinkUrl'], 0, 4))){
				//站内链接手机站，如：1.html ,自动加上全路径
				$data[$i]['ChannelUrl'] = WapHomeUrl().'/'.ltrim($data[$i]['LinkUrl'],'/');
			}else{
				$data[$i]['ChannelUrl'] = $data[$i]['LinkUrl'];
			}
		}else{
			$data[$i]['ChannelUrl'] = ChannelUrl($data[$i]['ChannelID'], $data[$i]['Html'], '');
		}
		$data[$i]['ChannelDepth'] = ($maxDepth - $data[$i]['ChannelDepth'] + 1);
		$data[$i]['Count'] = $count;
		//$data[$i]['HasChild'] = $channel->hasChildChannel($data[$i]['ChannelID']) ? 1 : 0;
	}
	return $data;
}

//专题信息
function get_special($ChannelID = 0, $idlist=-1){
	$m = D('Admin/Special');
	$p = array('IsEnable'=>1, 'idlist'=>$idlist, 'ChannelID'=>$ChannelID);
	$data = $m->getSpecial($p);
	if( empty($data) ) return false; 
	return $data;
}

//频道信息
function get_navigation($parentID = 0, $depth=1, $idlist=-1, $isshow=1, $channelmodelid=-1, $LanguageID=-1, $Field=''){
	if( !is_numeric($parentID) ) return false;
	if(empty($idlist)) $idlist = -1; //$idlist为空，可能传入false或''
	if(empty($channelmodelid)) $channelmodelid = -1;
	if(empty($Field)) $Field = '';
	$key = md5($parentID.$depth.$idlist.$isshow.$channelmodelid.$LanguageID.$Field);
	static $_cache = array();
	if (isset($_cache[$key])){
		return $_cache[$key];
	}
	$channel = D('Admin/Channel');
	$data = $channel->getNavigation($parentID, $depth, $idlist, $isshow,$channelmodelid, $LanguageID, $Field);
	if( empty($data) ) return false;  //当$data为false时，count($data)返回1，因此必选先判断$data是否为空
	$count = is_array($data) ? count($data) : 0;
	
	//找出ChannelDepth最大值，归一化ChannelDepth，从0开始
	$maxDepth = -9999;
	for($j = 0; $j < $count; $j++){
		if(isset($data[$j]['ChannelDepth']) && $data[$j]['ChannelDepth'] > $maxDepth ) {
			$maxDepth = $data[$j]['ChannelDepth'];
		}
	}
    $siteName = defined('SITE_NAME') && !empty($GLOBALS['Config']['SiteChannelShow']) ? SITE_NAME : '';
	for($i = 0; $i < $count; $i++){
        $data[$i]['ChannelName'] = $siteName.$data[$i]['ChannelName'];
		if( 33 == $data[$i]['ChannelModelID'] && trim($data[$i]['LinkUrl']) != ''){ //33:转向链接
			if( GROUP_NAME == 'Wap' && 'http' != strtolower( substr($data[$i]['LinkUrl'], 0, 4))){
				//站内链接手机站，如：1.html ,自动加上全路径
				$data[$i]['ChannelUrl'] = WapHomeUrl().'/'.ltrim($data[$i]['LinkUrl'],'/');
			}else{
				$data[$i]['ChannelUrl'] = $data[$i]['LinkUrl'];
			}
		}else{
			$data[$i]['ChannelUrl'] = ChannelUrl($data[$i]['ChannelID'], $data[$i]['Html'], '');
		}
		if(!isset($data[$i]['ChannelDepth'])) $data[$i]['ChannelDepth'] = 0;
		$data[$i]['ChannelDepth'] = ($maxDepth - $data[$i]['ChannelDepth'] + 1);
		$data[$i]['Count'] = $count;
		ChannelName($data[$i]['ChannelID'], $data[$i]['ChannelName']); //缓存频道名称
	}
	$_cache[$key] = $data;
	return $data;
}

//微信应用列表
function get_wx_app(){
	$m = D('Admin/WxApp');
	$App = $m->getApp(-1, -1, -1, '', 1);
	$n = is_array($App) ? count($App) : 0;
	
	for($i=0; $i < $n; $i++){
		switch($App[$i]['AppTypeID']){
			case 1: //微活动
				$type = substr($App[$i]['AppParameter'], 0, 1);
				$App[$i]['Keyword'] = ($type==0) ? '大转盘' : '刮刮卡';
				break;
			case 2:  //微投票
				$App[$i]['Keyword'] = '投票';
				break;
			case 5:  //微调查
				$App[$i]['Keyword'] = '调查';
				break;
			default:
				break;
		}
	}
	
	$wa= D('Admin/WxApptype');
	$type = $wa->getAppType(1);
    $t = array();
	foreach($type as $k=>$v){
		$t[ $v['AppTypeID'] ] = $v['AppTypeName'];
	}
	
	$applist = include CONF_PATH.'wxapp.php';  //加载微信配置文件
	foreach ($applist as $k=>$v){
		$App[] = array(
				"AppID"=>$k, 
				'AppName'=>$v['name'], 
				'Keyword'=>$k, 
				'Description'=>$v['description'],
				'AppTypeName'=>$t[ $v['type'] ],
		);
	}
	return $App;
}

//网站地图
function get_sitemap($ChannelID = 0, $depth=-1, $prefix='&nbsp;&nbsp;'){
	return get_navigation($ChannelID, $depth);
}

//友情链接
function get_link($linkClassID = 0, $top = 10){
	$m = D('Admin/Link');
	$data = $m->getLink(0, $top, $linkClassID, 1);
	return $data;
}

//有利于自定义表单
function get_banner_list($BannerGroupID = -1){
	$m = D('Admin/Banner');
	$data = $m->getBanner(1, $BannerGroupID);
	return $data;
}

function get_bannergroup(){
	$m = D('Admin/BannerGroup');
	$data = $m->getBannerGroup();
	return $data;
}

//人才招聘
function get_job($top = 10, $nowPage = 0, $JobClassID=0){
    $top = intval($top);
    $nowPage = intval($nowPage);
	$m = D('Admin/Job');
	if( $nowPage != 0 ){//分页, $labelID和$top无效, 获取指定分页数据
		$t = array('cn'=>8, 'en'=>4);
		$PageSize = PageSize( $t[LANG_SET] ); //页面大小
		$PageSize = ($PageSize > 0) ? $PageSize : 20;
		$nowPage = ( $nowPage - 1 > 0 ) ? ( $nowPage - 1 ) : 0;
		$offset = $nowPage * $PageSize;
		$data = $m->getJob($offset, $PageSize, 1, $JobClassID);
	}else{ //不分页
		$data = $m->getJob(0, $top, 1, $JobClassID);
	}
	return $data;
}

function get_online($id){
    $config = &$GLOBALS['Config'];
    $enable = $config['ONLINE_ENABLE'] ;
    if( empty( $enable ) ) return '';

    $style = $config['ONLINE_STYLE'];
    $color = $config['ONLINE_COLOR'];
    $position = $config['ONLINE_POSITION'] ;
    $top = $config['ONLINE_TOP'] ;
    $width = $config['ONLINE_WIDTH'] ;
    $title = $config['ONLINE_TITLE'] ;
    $effect = $config['ONLINE_EFFECT'] ;
    $open = $config['ONLINE_OPEN'] ;
    $telephone = $config['ONLINE_TELEPHONE'];
    $footertext = $config['ONLINE_FOOTER_TEXT'];
    $iconColor = $config['ONLINE_ICON_COLOR'];

    $style        = !empty($style) ? $style : '1';  //默认为传统样式
    if(!is_numeric($style)) $style= 1; //传统样式的值是blue，red

    $top        = isset($top) ? $top : 200;
    $width         = isset($width) ? $width : 200;
    $title        = !empty($title) ? $title : L('OnlineTitle');
    $footertext        = !empty($footertext) ? $footertext : '';
    $footertext = str_replace('"', "'", $footertext );
    $footertext = str_replace('$', "\$", $footertext );
    $footertext = str_replace(array("\r\n", "\r", "\n"), "", $footertext);
    //$footertext = preg_replace("'([\r\n])[\s]+'", "<br/>", $footertext); //去除回车换行符
    //$footertext = nl2br($footertext);
    $telephone = !empty($telephone) ? $telephone : 1;
    $position  = ($position  == 0) ? 'left' : 'right';
    $effect      = ($effect  == 0) ? 'false' : 'true';
    $open       = ($open == 0) ? 'false' : 'true';

    if(1 == $style){ //传统样式
        if($width < 110) $width = 160;
    }else{ //简约
        if($width >110) $width = 50; //块大小
    }

    $m = D('Admin/Support');
    $data = $m->getSupport( 1 );
    $qqlist = '';
    if(!empty($data)){
        $count = is_array($data) ? count($data) : 0;
        for($n = 0; $n < $count-1; $n++){
            $qqlist .= $data[$n]['SupportNumber'].'|'.$data[$n]['SupportName'].'|'.$data[$n]['SupportTypeID'].',';
        }
        $qqlist .= $data[$count-1]['SupportNumber'].'|'.$data[$count-1]['SupportName'].'|'.$data[$count-1]['SupportTypeID'];
    }
    /*
     多个QQ用','隔开，QQ和客服名用'|'隔开
    Position:'left',//left或right
    Top:100,//顶部距离，默认200px
    Width:300,//顶部距离，默认200px
    Style:5,//图标的显示风格共6种风格，默认显示第一种：1
    Effect:true, //滚动或者固定两种方式，布尔值：true或false
    DefaultsOpen:false, //默认展开：true,默认收缩：false
    Tel:'0731-84037726',//其它信息图片等
    Qqlist:'402719549|售前咨询,402719549|售前咨询,402719549|售后咨询,402719549|技术支持,402719549|建议/投诉'
    */
    //使用window.load的原因是模板装修时，在线客服加载过慢，导致不能装修
    $js = '';
    if($id=='online'){ //只有旧版才包含脚本，新版在组件common.js已经包含
        $js = "<link rel='stylesheet' type='text/css' href='{\$WebPublic}online/style/common.css'/>
            <script type='text/javascript' src='{\$WebPublic}online/jquery.online.js'></script>";
    }
    $str = "
		<!--在线客服start-->
		{$js}
		<style>
			.SonlineBox .openTrigger, .SonlineBox .titleBox{ background-color:{$color}; }
			.SonlineBox .contentBox{ border:2px solid {$color};  }
		</style>
		<script type='text/javascript'>
		$(window).load(function(){
			$().Sonline({
				Position:'{$position}', Top:{$top}, Width:{$width}, Style:{$style}, Effect:{$effect}, 
				DefaultsOpen:{$open}, Tel:'{$telephone}', Title:'{$title}',
				FooterText:\"{$footertext}\", Website:'__ROOT__',
				IconColor: '{$iconColor}', ThemeColor: '{$color}',
				Qqlist:'{$qqlist}'
			});
		});
		</script>
		<!--在线客服end-->
		";
    return $str;
}

//gotop标签
function get_gotop($id, $right, $bottom, $style='', $title=''){
    if($id=='gotop'){ //等于gotop一定是旧版
        return get_gotop_old($id, $right, $bottom, $style, $title);
    }
    $config = &$GLOBALS['Config'];
    if(empty($config['GoTopEnable'])) return '';
    $bgColor = !empty($config['GoTopBgColor']) ? $config['GoTopBgColor'] : '#FF0000';
    $height = !empty($config['GoTopHeight']) ? $config['GoTopHeight'] : 50;
    $width = !empty($config['GoTopWidth']) ? $config['GoTopWidth'] : 50;
    $cornerSize = !empty($config['GoTopCornerSize']) ? $config['GoTopCornerSize'] : 0;
    $shadow = !empty($config['GoTopShadow']) ? $config['GoTopShadow'] : '0';

    $bgColorHover = !empty($config['GoTopBgColorHover']) ? $config['GoTopBgColorHover'] : '#FF0000';
    $iconSize = !empty($config['GoTopIconSize']) ? $config['GoTopIconSize'] : 30;
    $iconColor = !empty($config['GoTopIconColor']) ? $config['GoTopIconColor'] : '#FFFFFF';
    $iconColorHover = !empty($config['GoTopIconColorHover']) ? $config['GoTopIconColorHover'] : '#FFFFFF';
    //位置
    $right = !empty($config['GoTopRight']) ? $config['GoTopRight'] : 20;
    $bottom = !empty($config['GoTopBottom']) ? $config['GoTopBottom'] : 150;
    $str = "<!--gotop start-->
    <style>
            #topcontrol .yd-gotop{
                 transition-duration: .2s;  text-align: center; cursor: pointer; background: {$bgColor}; 
                 width: {$width}px;  height: {$height}px;line-height: {$height}px;
                border-radius:{$cornerSize}px; box-shadow: 0 2px {$shadow}px rgba(0,0,0,.1);
            }
            #topcontrol .yd-gotop:hover{ background: {$bgColorHover}; }
            #topcontrol .yd-gotop i{ font-size:{$iconSize}px; color:{$iconColor}; }
            #topcontrol .yd-gotop:hover i{ color:{$iconColorHover}; }
    </style>
    <script>
        scrolltotop.controlattrs={offsetx:$right, offsety:$bottom };
        scrolltotop.controlHTML = '<div yd-content=\"gotop\" class=\"yd-gotop\"><i class=\"{$config['GoTopStyle']}\"></i></div>';
        scrolltotop.anchorkeyword = '#{$id}';
        scrolltotop.title = \"{$title}\";
        scrolltotop.init();
    </script>
    <!--gotop end-->";
    return $str;
}

//主要为了兼容旧版
function get_gotop_old($id, $right, $bottom, $style='', $title='') {
    $str = "
		<!--gotop start-->
		<script type='text/javascript' src='{\$WebPublic}jquery/common.js'></script>
		<script>
			scrolltotop.controlattrs={offsetx:$right, offsety:$bottom};
			scrolltotop.controlHTML = '<img src=\"{\$WebPublic}Images/gotop/$style.gif\" />';
			scrolltotop.anchorkeyword = '#$id';
			scrolltotop.title = '$title';
			scrolltotop.init();
		</script>
		<!--gotop end-->
		";
    return $str;
}

//广告
function get_ad($adid, $id, $width, $height, $delay=10, $step=1, $left='8px', $right='8px', $top='260px'){
    $adid = intval($adid);
	$m = D('Admin/Ad');
	$data = $m->where("IsEnable=1 and AdID=$adid")->find();
	
	$AdTypeID = $data['AdTypeID'];
	$des = $data['AdName']."[".$data['AdTime']."]&#13".$data['AdDescription'];
	$adUrl = $data['AdUrl'];
	$adContent = $data['AdContent'];
	$adName = $data['AdName'];
	
	$parseStr = "";
	switch($AdTypeID){
		case 1:   //图片广告
			$parseStr = "<!--图片广告start-->";
			if(empty($adUrl)){
				$parseStr .= "<img $width $height  src='$adContent' border='0' alt='$des' title='$des' />";
			}else{
				$parseStr .= "<a id='$id' href='$adUrl' target='_blank'><img $width $height  src='$adContent' border='0' alt='$des' title='$des' /></a>";
			}
			$parseStr .= "<!--图片广告end-->";
			break;
		case 2:   //Flash广告
			$parseStr .=  "<!--Flash广告start-->\n";
			$parseStr .= "<embed $width $height src=\"".$data['AdContent']."\" ";
			$parseStr .= " quality=\"high\" type=\"application/x-shockwave-flash\" wmode=\"transparent\"  ";
			$parseStr .= " pluginspage=\"http://www.macromedia.com/go/getflashplayer\" allowScriptAccess=\"always\">";
			$parseStr .= "</embed>";
			$parseStr .=  "\n<!--Flash广告start-->";
			break;
		case 3:   //漂浮广告
			//代码被废弃
			break;
		case 4:   //代码广告
			$parseStr = "<!--代码广告start-->\n".$data['AdContent']."\n<!--代码广告end-->";
			break;
		case 5: //对联广告
			$top1 = str_ireplace('px', '', $top);
			
			$duilianImg = $data['AdContent'];
			$duilianImg = str_replace(array("\r\n","\r"), "\n", $duilianImg);
			$duilianImg = (array)explode ("\n", $duilianImg);
			
			$duilianImg1 = $duilianImg[0];
			$duilianImg2 = !empty($duilianImg[1]) ? $duilianImg[1] : $duilianImg[0];

			$duilianUrl = (array)explode(',', $data['AdUrl']);
			$duilianUrl1 = $duilianUrl[0];
			$duilianUrl2 = !empty($duilianUrl[1]) ? $duilianUrl[1] : $duilianUrl[0];

			$adclose = __ROOT__."/Public/Images/adclose.gif";
			$parseStr = "<!--对联广告start-->
			<style>
			.duilian$id{top:$top; position:absolute; display:none;}
			.duilianclose$id{ cursor:pointer; }
			</style>
			<div class='duilian$id' style='left:$left;'>
			<div><a href='$duilianUrl1' target='_blank'><img src='$duilianImg1' $width $height  alt='$des' title='$des'></a></div>
			<img  class='duilianclose$id' src='$adclose' $width  height='14px'>
			</div>
			<div class='duilian$id'  style='right:$right;'>
			<div><a  href='$duilianUrl2'  target='_blank'><img src='$duilianImg2' $width $height  alt='$des' title='$des'></a></div>
			<img class='duilianclose$id' src='$adclose' $width  height='14px'>
			</div>
			<script type='text/javascript'>
			$(document).ready(function(){
				var duilian = $('div.duilian$id');
				var window_w = $(window).width();
				if( window_w > 1000 ){ duilian.show(); }
				$(window).scroll(function(){
				var scrollTop = $(window).scrollTop();
				duilian.stop().animate({top:scrollTop+$top1}, 800);
			});
			$('img.duilianclose$id').click(function(){
				$(this).parent().hide();
				return false;
			});
			});
			</script>
			<!--对联广告end-->";
			break;
		}
		return $parseStr;
}

function AdCount($adGroupID){
	$m = D('Admin/Ad');
	$n = $m->getAdCount($adGroupID);
	return $n;
}

function get_ad_list($groupID = -1){
	$m = D('Admin/Ad');
	$p['IsEnable'] = 1;
	if( $groupID != -1){
		$p['AdGroupID'] = $groupID;
	}
	$data = $m->getAd(-1, -1, $p);
	return $data;
}

function get_area($parent = -1){
	$m = D('Admin/Area');
	$data = $m->getArea($parent,1);
	return $data;
}

function get_site(){
    $m = D('Admin/Site');
    $data = $m->getSite(1);
    return $data;
}

function AreaName($AreaID){
    $m = D('Admin/Area');
    $where['AreaID'] = intval($AreaID);
    $name = $m->where($where)->getField('AreaName');
    return $name;
}

function get_adgroup(){
	$m = D('Admin/AdGroup');
	$p['IsEnable'] = 1;
	$data = $m->getAdGroup($p);
	return $data;
}

//在线客服列表
function get_support($qqstyle='41'){
	$m = D('Admin/Support');
	$data = $m->getSupport(1);
	$n = is_array($data) ? count($data) : 0;
	for($i = 0; $i < $n; $i++){
		switch( $data[$i]['SupportTypeID']){
			case 2://淘宝旺旺
				$data[$i]['SupportFace'] = yd_taobao_face($data[$i]['SupportNumber']);
				break;
			case 3://阿里旺旺
				$data[$i]['SupportFace'] = yd_ali_face($data[$i] ['SupportNumber'] );
				break;
			case 4 : // 微软MSN
				$data [$i] ['SupportFace'] = yd_msn_face ( $data [$i] ['SupportNumber'] );
				break;
			case 5 : // Skype
				$data [$i] ['SupportFace'] = yd_skype_face ( $data [$i] ['SupportNumber'] );
				break;
			case 6://国际版阿里旺旺
				$data[$i]['SupportFace'] = yd_interali_face($data[$i] ['SupportNumber'] );
				break;
			case 7: //自定义
				$data[$i]['SupportFace'] = $data[$i]['SupportNumber'];
				break;
			case 1 : // qq
			default :
				$data [$i] ['SupportFace'] = yd_qq_face ( $data [$i] ['SupportNumber'], $qqstyle );
				break;
		}
	}
	return $data;
}

//获取标签数据
function get_label($channelmodelid){
	$m = D('Admin/Label');
	$data = $m->getLabel($channelmodelid,-1,1);
	return $data;
}

//获取表单字段列表
function get_form($channelmodelid){
	$m = D('Admin/Attribute');
	$group = $m->getGroup($channelmodelid);
	$Attribute = $m->getAttribute($channelmodelid);
    $data = array();
	foreach($group as $g){
		foreach ($Attribute as $a){
			if( $a['GroupID'] == $g['AttributeID'] ){
				$data[] = $a;
			}
		}
	}
	unset($group, $Attribute);
	return $data;
}

//主要用于前台模型调用，获取模型字段列表
function get_model($channelmodelid, $idlist=-1){
	$id = get_language_index();
	$m = D('Admin/Attribute');
	$group = $m->getGroup($channelmodelid);
	$Attribute = $m->getAttribute($channelmodelid, false, 1, -1, $idlist);
	$n = 0;
    $Selected = '';
    $data = array();
	foreach($group as $g){
		foreach ($Attribute as $a){
			if( $a['GroupID'] == $g['AttributeID'] ){
				$DisplayName = explode(',', $a['DisplayName']);
				$a['DisplayName'] = isset($DisplayName[$id]) ? $DisplayName[$id] : $DisplayName[0];
				$data[$n] = $a;
				
				if( stripos($a['DisplayType'], 'radio') !== false ){
					$Selected = "checked='checked'";
				}else if(stripos($a['DisplayType'], 'checkbox') !== false ){
					$Selected = "checked='checked'";
				}else if(stripos($a['DisplayType'], 'select') !== false ){
					$Selected = "selected='selected'";
				}
				//获取具体项目
				if(stripos($a['DisplayType'], 'radio') !== false 
						|| stripos($a['DisplayType'], 'checkbox') !== false 
						||  stripos($a['DisplayType'], 'select') !== false){
					$defaultValue = str_replace(array("\r\n","\r"), "\n", $a['DisplayValue']);
					$item = explode("\n", $defaultValue);
					for($j = 0; $j < count($item); $j++){
					    //格式：女|女|1,Female|Female|1  【回车】 男|男,Male|Female
						$itemString = explode(',', $item[$j]);
						$itemString = isset($itemString[$id]) ? $itemString[$id] : $itemString[0];
						$t = explode ('|', $itemString ); //value|item|是否是默认
						$sel=empty($t[2]) ? '' : $Selected;
						$data[$n]['Item'][$j] = array('Value'=> $t[0], 'Text'=>$t[1], 'Selected'=>$sel);
					}
				}
				$n++;
			}
		}
	}
	unset($group, $Attribute);
	return $data;
}

//获取订阅邮件分类
function get_mail_class(){
	$m = D('Admin/MailClass');
	$data = $m->getMailClass(1);
	return $data;
}

//获取友情链接分类
function get_link_class(){
	$m = D('Admin/LinkClass');
	$data = $m->getLinkClass();
	return $data;
}

function get_job_class(){
    $m = D('Admin/JobClass');
    $data = $m->getJobClass();
    return $data;
}

function get_videopalyer($data){
	import("@.Common.YdVideoPlayer");
	$v = new YdVideoPlayer( $data );
	$html = $v->render();
	return $html;
}

//banner
function get_banner($width, $height, $time, $showtext, $textcolor, $textbgcolor,$textbgalpha,$bartextcolor,$barovercolor,$baroutcolor,$channelid,$groupid=-1,$top=-1,$labelid=-1){
	$files = '';  $links = ''; $texts = '';
	if(empty($channelid) ){
		$m = D('Admin/Banner');
		$data = $m->getBanner(1 , $groupid);
		$count = is_array($data) ? count($data) : 0;
		if( $count <= 0 ) return '';
		if( $count == 1 ) return get_one_banner($data [0]['BannerImage'], $data [0]['BannerUrl'] ,$data [0]['BannerName'], $width, $height);
		for($i = 0; $i < $count; $i++){
			$files .= '|'.$data[$i]['BannerImage'];
			$links .= '|'.$data[$i]['BannerUrl'];
			$texts .= '|'.$data[$i]['BannerName'];
		}
	}else{
		$m = D('Admin/Info');
		$data = $m->getInfoImage($channelid, $top, $labelid);
		$count = is_array($data) ? count($data) : 0;
		if( $count <= 0 ) return '';
		if( $count == 1 ) return get_one_banner($data[0]['InfoPicture'], InfoUrl( $data [0]['InfoID'] ),$data [0]['InfoTitle'], $width, $height);
		for($i = 0; $i < $count; $i++){
			$files .= '|'.$data[$i]['InfoPicture'];
			$links .= '|'.InfoUrl($data[$i]['InfoID']);
			$texts .= '|'. $data[$i]['InfoTitle'];
		}
	}
	
	$files = substr($files, 1);
	$links = substr($links, 1);
	$texts = ($showtext==1) ? substr($texts, 1) : ''; //控制是否显示标题
	
	$effectFile = __ROOT__.'/Public/effect/normal.swf';
	
	//-- config:参数 自动播放时间(秒)|文字颜色|文字背景色|文字背景透明度|按键数字颜色|当前按键颜色|普
	$parseStr = "<script type=\"text/javascript\">
	var swf_width = '$width';
	var swf_height = '$height';
	var config = '$time|$textcolor|$textbgcolor|$textbgalpha|$bartextcolor|$barovercolor|$baroutcolor';
	var files = '$files';
	var links = '$links';
	var texts = '$texts';
	
	document.write('<object classid=\"clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\" codebase=\"http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0\" width=\"'+ swf_width +'\" height=\"'+ swf_height +'\">');
	document.write('<param name=\"movie\" value=\"$effectFile\" />');
	document.write('<param name=\"quality\" value=\"high\" />');
	document.write('<param name=\"menu\" value=\"false\" />');
	document.write('<param name=wmode value=\"opaque\" />');
	document.write('<param name=\"FlashVars\" value=\"config='+config+'&bcastr_flie='+files+'&bcastr_link='+links+'&bcastr_title='+texts+'\" />');
	document.write('<embed src=\"$effectFile\" wmode=\"opaque\" FlashVars=\"config='+config+'&bcastr_flie='+files+'&bcastr_link='+links+'&bcastr_title='+texts+'& menu=\"false\" quality=\"high\" width=\"'+ swf_width +'\" height=\"'+ swf_height +'\" type=\"application/x-shockwave-flash\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\" />');
	document.write('</object>');
	</script>";
	return $parseStr;
}

//输出banner1，存在Bug，如仅支持jpg，偶尔图片显示空白
function get_banner1($width, $height, $time, $showtext, $channelid,$groupid=-1,$top=-1,$labelid=-1) {
	$files = $links = $texts = '';
	if (empty ( $channelid )) {
		$m = D ( 'Admin/Banner' );
		$data = $m->getBanner ( 1 , $groupid);
		$count = count ( $data );
		if( $count <= 0 ) return '';
		if( $count == 1 ) return get_one_banner($data [0]['BannerImage'], $data [0]['BannerUrl'] ,$data [0]['BannerName'],$width, $height);
		for($i = 0; $i < $count; $i ++) {
			$files .= '|' . $data [$i]['BannerImage'];
			$links .= '|' . $data [$i]['BannerUrl'];
			$texts .= '|' . $data [$i]['BannerName'];
		}
	} else { // 取频道图片
		$m = D ( 'Admin/Info' );
		$data = $m->getInfoImage ( $channelid , $top, $labelid);
		$count = count ( $data );
		if( $count <= 0 ) return '';
		if( $count == 1 ) return get_one_banner($data[0]['InfoPicture'], InfoUrl( $data [0]['InfoID'] ),$data [0]['InfoTitle'],$width, $height);
		for($i = 0; $i < $count; $i ++) {
			$files .= '|' . $data [$i] ['InfoPicture'];
			$links .= '|' . InfoUrl ( $data [$i] ['InfoID'] );
			$texts .= '|' . $data [$i] ['InfoTitle'];
		}
	}
	
	$files = substr ( $files, 1 );
	$links = substr ( $links, 1 );
	$texts = substr ( $texts, 1 );
	$effectFile = __ROOT__ . '/Public/effect/slide.swf';
	
	// -- config:参数 自动播放时间(秒)|文字颜色|文字背景色|文字背景透明度|按键数字颜色|当前按键颜色|普
	$parseStr = "<script type='text/javascript'>
	var interval_time=$time;
	var focus_width='$width';
	var focus_height='$height';
	var text_height = $showtext;
	var text_align= 'center';
	var swf_height = parseInt(focus_height)+parseInt(text_height);
	
	var pics= '$files'
	var links='$links'
	var texts='$texts'
		
	document.write('<object classid=\"clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\" codebase=\"http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0\" width=\"'+ focus_width +'\" height=\"'+ swf_height +'\">');
	document.write('<param name=\"movie\" value=\"$effectFile\"><param name=\"quality\" value=\"high\"><param name=\"bgcolor\" value=\"#ffffff\">');
	document.write('<param name=\"menu\" value=\"false\"><param name=wmode value=\"transparent\">');
	document.write('<param name=\"FlashVars\" value=\"pics='+pics+'&links='+links+'&texts='+texts+'&borderwidth='+focus_width+'&borderheight='+focus_height+'&textheight='+text_height+'&text_align='+text_align+'&interval_time='+interval_time+'\">');
	document.write('<embed src=\"$effectFile\" wmode=\"opaque\" FlashVars=\"pics='+pics+'&links='+links+'&texts='+texts+'&borderwidth='+focus_width+'&borderheight='+focus_height+'&textheight='+text_height+'&text_align='+text_align+'&interval_time='+interval_time+'\" menu=\"false\" bgcolor=\"#ffffff\"  quality=\"high\" width=\"'+ focus_width +'\" height=\"'+ swf_height +'\" allowScriptAccess=\"sameDomain\" type=\"application/x-shockwave-flash\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\" />');
	document.write('</object>');
	</script>";
	return $parseStr;
}
// banner2
function get_banner2($width, $height, $time, $showtext, $channelid,$groupid=-1,$top=-1,$labelid=-1) {
	return get_banner1 ( $width, $height, $time, $showtext, $channelid  , $groupid, $top, $labelid);
}
// banner3
function get_banner3($width, $height, $time, $showtext, $channelid,$groupid=-1,$top=-1,$labelid=-1) {
	// symbol: linear（直线）和circular（圆形，译注：类似倒计时，顺时针）。
	$xml = "<?xml version='1.0' encoding='utf-8' ?>
	<cu3er>
	<settings>
	<auto_play>
	<defaults symbol='circular' time='$time' /><!--倒计时按钮的位置-->
	<tweenIn width='30' height='30' tint='0xFF0000' alpha='0.5'/>
	<tweenOver alpha='1'/>
	</auto_play>
	<prev_button>
	<defaults round_corners='5,5,5,5'/>
	<tweenOver tint='0xFFFFFF' scaleX='1.1' scaleY='1.1'/>
	<tweenOut tint='0x000000' />
	</prev_button>
	<prev_symbol>
	<tweenOver tint='0x000000' />
	</prev_symbol>
	<next_button>
	<defaults round_corners='5,5,5,5'/>
	<tweenOver tint='0xFFFFFF'  scaleX='1.1' scaleY='1.1'/>
	<tweenOut tint='0x000000' />
	</next_button>
	<next_symbol>
	<tweenOver tint='0x000000' />
	</next_symbol>
	</settings>
	<slides>";
	
	//num – 每次变换包含的切片数 slicing – 立方体切片方向：水平horizontal或垂直vertical
	// direction - 变换方向 / 立方体旋转方向：上up、下、左、右
	//shader – transition shading type – none, flat, phong
	//<transition num="4" direction="right" shader="flat" />
	//$transition[] = "<transition num='4' slicing='vertical' direction='down' />";
	$transition = array(0=>'<transition num="3" slicing="vertical" direction="down"/>',
	    1=>'<transition num="4" direction="right" shader="flat" />',
	    2=>'<transition num="6" slicing="vertical" direction="up" shader="phong" delay="0.05" z_multiplier="4" />',
	    3=>'',
	);
	$n = count($transition)-1;
	if(empty($channelid) ){
		$m = D('Admin/Banner');
		$data = $m->getBanner(1 , $groupid);
		$count = is_array($data) ? count($data) : 0;
		if( $count <= 0 ) return '';
		if( $count == 1 ) return get_one_banner($data [0]['BannerImage'], $data [0]['BannerUrl'] ,$data [0]['BannerName'], $width, $height);
		for($i = 0; $i < $count; $i++){
			$title = trim($data[$i]['BannerName']);
			$img = $data[$i]['BannerImage'];
			$url = $data[$i]['BannerUrl'];
			$xml .= "\n<slide><url>$img</url>";
			if( $showtext && $title !='' ){
			    $xml .= "\n<description>\n<link target='_blank'>$url</link>\n<heading>$title</heading>\n<paragraph></paragraph>\n</description>";
			}else{
			    $xml .= "\n<link target='_blank'>$url</link>";
			}
			$tr = ($i != $count - 1) ? $transition[$i % $n] : '';
			$xml .= "\n</slide>\n$tr";
		}
	}else{ //取频道图片
			$m = D('Admin/Info');
			$data = $m->getInfoImage($channelid, $top, $labelid);
			$count = is_array($data) ? count($data) : 0;
			if( $count <= 0 ) return '';
			if( $count == 1 ) return get_one_banner($data[0]['InfoPicture'], InfoUrl( $data [0]['InfoID'] ),$data [0]['InfoTitle'], $width, $height);
			for($i = 0; $i < $count; $i++){
				$title = $data[$i]['InfoTitle'];
				$para = $data[$i]['InfoSContent'];
				$img = $data[$i]['InfoPicture'];
				$url = InfoUrl($data[$i]['InfoID']);
				$xml .= "\n<slide><url>$img</url>";
				if( $showtext && $title !='' ){
				$xml .= "\n<description>\n<link target='_blank'>$url</link>\n<heading>$title</heading>\n<paragraph>$para</paragraph>\n</description>";
				}else{
				$xml .= "\n<link target='_blank'>$url</link>";
				}
				$tr = ($i != $count - 1) ? $transition[$i % $n] : '';
				$xml .= "\n</slide>\n$tr";
		  }
	}
	$xml .= "\n</slides>\n</cu3er>";
	$fileName = RUNTIME_PATH.'data/banner3.xml';
	$b = file_put_contents($fileName, $xml);
	if( !$b ) return false;
	$effectFile = __ROOT__.'/Public/effect/cu3er.swf';
	
	$parseStr = "<script type='text/javascript'>
	var swf_width='$width';
	var swf_height = '$height';
	var xml = '$fileName';
	document.write('<object classid=\"clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\" codebase=\"http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0\" width=\"'+ swf_width +'\" height=\"'+ swf_height +'\">');
	document.write('<param name=\"movie\" value=\"$effectFile\"><param name=\"quality\" value=\"high\"><param name=\"bgcolor\" value=\"#ffffff\">');
	document.write('<param name=\"menu\" value=\"false\"><param name=wmode value=\"transparent\">');
	document.write('<param name=\"FlashVars\" value=\"xml='+xml+'\">');
	document.write('<embed src=\"$effectFile\" wmode=\"opaque\" FlashVars=\"xml='+xml+'\" menu=\"false\" bgcolor=\"#ffffff\"  quality=\"high\" width=\"'+ swf_width +'\" height=\"'+ swf_height +'\" allowScriptAccess=\"sameDomain\" type=\"application/x-shockwave-flash\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\" />');
	document.write('</object>');
	</script>";
	return $parseStr;
}
//输出单个媒体文件支持：图像，swf
//$url仅对图像格式文件有效
function get_one_banner($image, $url, $title, $width, $height){
	$ext = strtolower( yd_file_ext($image) );
	$str = '';
	if( empty($ext) ) return $str;
	if($ext == 'swf'){ //flash动画
		$str = "
		<object classid=\"clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\" codebase=\"http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0\" width=\"$width\" height=\"$height\">
		<param name=\"movie\" value=\"$image\">
		<param name=\"quality\" value=\"high\">
		<param name=\"bgcolor\" value=\"#ffffff\">
		<param name=\"menu\" value=\"false\">
		<param name=wmode value=\"transparent\">
		<embed src=\"$image\" wmode=\"opaque\"  menu=\"false\" bgcolor=\"#ffffff\"  quality=\"high\" width=\"$width\" height=\"$height\" allowScriptAccess=\"sameDomain\" type=\"application/x-shockwave-flash\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\" />
		</object>";
	}else{ //图像
		//$width .= 'px'; $height .= 'px';  //必须加上px image的宽度或高度才生效
		//经测试不加px，正常
		if( is_numeric($width) ) $width .= 'px';
		if( is_numeric($height) ) $height .= 'px';
		if( empty($url) ){
			$str = "<img src='$image' alt='$title'  style=\"width:$width;height:$height\"  />";
		}else {
			$str = "<a href='$url' target='_blank'><img src='$image' alt='$title'    style='width:$width;height:$height;border:0px'  /></a>";
		}
	}
	return $str;
}

//幻灯片高度
function BannerImageHeight($BannerGroupID=0){
    return _bannerHeight($BannerGroupID, 1);
}

//幻灯片缩略图高度
function BannerThumbnailHeight($BannerGroupID=0){
    return _bannerHeight($BannerGroupID, 2);
}

function _bannerHeight($BannerGroupID=0, $type=1){
    $FieldName = ($type==1) ? 'BannerImage' : 'BannerThumbnail';
    $BannerGroupID = intval($BannerGroupID);
    $m = D('Admin/Banner');
    $where = "{$FieldName} !='' AND IsEnable=1";
    if($BannerGroupID>0){
        $where .= " AND BannerGroupID={$BannerGroupID}";
    }
    $img = $m->where($where)->getField($FieldName);
    if(!empty($img)){
        //getimagesize函数支持远程图片
        $isLocal = ('/'==substr($img, 0, 1)) ? true : false;
        if($isLocal){ //本地图片
            $imgFile = ".{$img}";
            if(file_exists($imgFile)){
                $data = getimagesize($imgFile);
                $height = $data[1];
            }
        }else{ //远程图片
            $data = getimagesize($img);
            $height = $data[1];
        }
    }
    if(empty($height)) $height = 0;
    return $height;
}

//客户留言
function get_guestbook($top = 10, $nowPage = 0){
	$m = D('Admin/Guestbook');
	if( $nowPage != 0 ){//分页, $labelID和$top无效, 获取指定分页数据
		$t = array('cn'=>9, 'en'=>5);
		$PageSize = PageSize( $t[LANG_SET] ); //页面大小
		$PageSize = ($PageSize > 0) ? $PageSize : 20;
		$nowPage = ( $nowPage - 1 > 0 ) ? ( $nowPage - 1 ) : 0;
		$offset = $nowPage * $PageSize;
		$data = $m->getMessage($offset, $PageSize, 1);
	}else{ //不分页
		$data = $m->getMessage(0, $top, 1);
	}
	return $data;
}

//获取评论信息
function get_comment($infoid, $nowPage = 0){
	$m = D('Admin/Comment');
    $params['InfoID'] =$infoid;
	if( $nowPage != 0 ){
		$PageSize = $GLOBALS['Config']['COMMENT_PAGE_SIZE']; //页面大小
		$PageSize = ($PageSize > 0) ? $PageSize : 20;
		$nowPage = ( $nowPage - 1 > 0 ) ? ( $nowPage - 1 ) : 0;
		$offset = $nowPage * $PageSize;
		$data = $m->getComment($offset, $PageSize, $params);  //-1:所有人的评论，1: 审核通过的
	}else{ //不分页
		$data = $m->getComment(-1, -1, $params);
	}
	return $data;
}

//投票调查
function get_vote_list($id){
	$m = D('Admin/WxApp');
	$data = $m->findTagVote($id);
	return $data;
}

function get_infoalbum($infoid, $fieldname='InfoAlbum'){
	$m = D('Admin/Info');
	$data = $m->getInfoAlbum( $infoid, $fieldname);
	return $data;
}

function get_inforelation($infoid, $fieldname='InfoRelation'){
	$m = D('Admin/Info');
	$data = $m->getInfoRelation($infoid,$fieldname);
	if( empty($data) ) return false;
	$Total = is_array($data) ? count($data) : 0;
	for($i = 0; $i < $Total; $i++){
		$data[$i]['DiscountPrice'] = DiscountPrice($data[$i]['InfoID'], $data[$i]['InfoPrice']);
		$data[$i]['InfoPrice'] = yd_to_money($data[$i]['InfoPrice']);
		$data[$i]['InfoSTitle'] = $data[$i]['InfoTitle'];
		$data[$i]['InfoUrl'] = InfoUrl($data[$i]['InfoID'], $data[$i]['Html'], $data[$i]['LinkUrl'], false, $data[$i]['ChannelID']);
		$data[$i]['Count'] = $Total;
	}
	return $data;
}

function get_channelalbum($channelid, $fieldname='ChannelAlbum'){
	$m = D('Admin/Channel');
	$data = $m->getChannelAlbum( $channelid, $fieldname);
	return $data;
}

function get_channelrelation($channelid, $fieldname='ChannelRelation'){
	$m = D('Admin/Channel');
	$data = $m->getChannelRelation($channelid,$fieldname);
	if( empty($data) ) return false;
	$Total = is_array($data) ? count($data) : 0;
	for($i = 0; $i < $Total; $i++){
		$data[$i]['InfoSTitle'] = $data[$i]['InfoTitle'];
		$data[$i]['InfoUrl'] = InfoUrl($data[$i]['InfoID'], $data[$i]['Html'], $data[$i]['LinkUrl'], false, $data[$i]['ChannelID']);
		$data[$i]['Count'] = $Total;
	}
	return $data;
}

function get_history($top=-1){
	$h = YdHistory::getInstance();
	$data = $h->getAllData($top);
	return $data;
}

//sales 按销量排序
function get_top($channelid='-1', $type='sales', $top=-1, $order='desc'){
	$m = D('Admin/Info');
	$data = $m->getTopOrderbySales($channelid, $top, $order);
	return $data;
}

function get_tag($infoid){
	$m = D('Admin/Info');
	$str = $m->getTag($infoid);
	if( empty($str) ) return false;
	$data = explode(',', $str);
	$n = is_array($data) ? count($data) : 0;
	$tag = false;
	for($i = 0; $i < $n; $i++){
		$name = trim($data[$i]);
		$url = InfoSearchAction();
		$url .= (strpos($url, '?') === false ) ? '?' : '&';
		$url .= 'Keywords='.urlencode($name);
		$tag[$i]['TagName'] = $name;
		$tag[$i]['TagUrl'] = $url;
	}
	return $tag;
}

function get_oauth(){
	$m = D('Admin/Oauth');
	$options['IsEnable'] = 1;
	$options['RemoveEmpty'] = 1;
	$data = $m->getOauth($options);
	if(empty($data)) return false;
	import("@.Common.YdOauth");
	$n = is_array($data) ? count($data) : 0;
	for($i = 0; $i < $n; $i++){
		$obj = YdOauth::getInstance($data[$i]['OauthMark']);
		$obj->setAppID( $data[$i]['OauthAppID'] );
		$obj->setAppKey( $data[$i]['OauthAppKey'] );
		$data[$i]['OauthRequestUrl'] = $obj->getRequestUrl();
	}
	return $data;
}

//获取信息（$channelID：多个频道ID以逗号隔开）
function get_info($channelID = 0, $specialID = 0, $top = 10, $timeFormat='Y-m-d', $titleLen = 0, $suffix='...', $labelID='', $nowPage=0, 
		$keywords='', $orderby=false, $minprice=-1, $maxprice=-1, $attr='', $LanguageID=-1, $Field='', $PageSize=0,
		$ProvinceID=-1, $CityID=-1, $DistrictID=-1, $TownID=-1){
	$m = D('Admin/Info');
	$options = array('Time'=>1, 'MinPrice'=>$minprice, 'MaxPrice'=>$maxprice,'Attr'=>$attr, 'LanguageID'=>$LanguageID, 
			'Field'=>$Field, 'Flag'=>1, 'ProvinceID'=>$ProvinceID, 'CityID'=>$CityID, 'DistrictID'=>$DistrictID, 'TownID'=>$TownID);
    $nowPage = intval($nowPage);
	if( $nowPage != 0 ){ //分页, $labelID和$top无效, 获取指定分页数据
        $channelID = intval($channelID); //分页的情况下，一定是数字
		$Total = $m->getCount($channelID, 1, 1, $keywords, -1, $specialID, $labelID, 1, -1, $options); //获取总记录数$page
		if( empty($PageSize) ){
		    if(MODULE_NAME==='Channel' && ACTION_NAME==='search'){ //检索页可以单独设置分页大小
                $PageSize = $GLOBALS['Config']['SearchPageSize'];
            }else{
		        $mc = D('Admin/Channel');
                $PageSize = $mc->where("ChannelID=$channelID")->getField('PageSize'); //页面大小
            }
			$PageSize = ($PageSize > 0) ? $PageSize : 20;
		}
        $PageSize = intval($PageSize);
		$nowPage = ( $nowPage - 1 > 0 ) ? ( $nowPage - 1 ) : 0;
		$offset = $nowPage * $PageSize;
		$data = $m->getInfo($offset, $PageSize, $channelID, 1, 1, $labelID, $keywords, -1, $specialID, 1, $orderby, -1, $options);
	}else{ //不分页
        $top = intval($top);
		$data = $m->getInfo(0, $top, $channelID, 1, 1, $labelID, $keywords, -1, $specialID, 1, $orderby, -1, $options);
		$Total = is_array($data) ? count($data) : 0;
	}
	if( empty($data) ) return false;  //当$data为false时，count($data)返回1，因此必选先判断$data是否为空
	$mt = D('Admin/TypeAttribute');
	$count = is_array($data) ? count($data) : 0;
	//$html = C('HTML_CACHE_ON');
    $siteName = defined('SITE_NAME') && !empty($GLOBALS['Config']['SiteInfoShow']) ? SITE_NAME : '';
	for($i = 0; $i < $count; $i++){
        $data[$i]['InfoTitle'] = $siteName.$data[$i]['InfoTitle'];
		//当$titleLen＝0时这样写会浪费CPU资源。$len = mb_strlen( $data[$i]['InfoTitle'] , 'utf-8'); //1个汉字算1个长度
		if( $titleLen > 0 &&  mb_strlen( $data[$i]['InfoTitle'] , 'utf-8') > $titleLen ){ //截取字符串
			$data[$i]['InfoSTitle'] = msubstr( $data[$i]['InfoTitle'], 0, $titleLen, 'utf-8', $suffix);
		}else{
			$data[$i]['InfoSTitle'] = $data[$i]['InfoTitle'];
		}
		//if($html){
		//	$data[$i]['InfoHit'] = "<script src='".__GROUP__."/public/getInfoHit?infoid={$data[$i]['InfoID']}'></script>";
		//}	
		$data[$i]['DiscountPrice'] = DiscountPrice($data[$i]['InfoID'], $data[$i]['InfoPrice']);
		$data[$i]['ExchangePrice'] = ExchangePrice($data[$i]['InfoID'], $data[$i]['ExchangePoint'],$data[$i]['DiscountPrice']);
		
		$data[$i]['InfoPrice'] = yd_to_money($data[$i]['InfoPrice']);
		$timestamp = strtotime( $data[$i]['InfoTime'] );
		if(defined('CLIENT_TYPE') && 5==CLIENT_TYPE){ //多端小程序特殊处理
            $data[$i]['InfoTime'] = strip_tags(yd_friend_date($timestamp)); //去除标记
        }else{
            $data[$i]['InfoTime'] = date($timeFormat, $timestamp);  //格式化时间
        }
		$data[$i]['InfoFriendTime'] = yd_friend_date($timestamp);
		$data[$i]['InfoUrl'] = InfoUrl($data[$i]['InfoID'], $data[$i]['Html'], $data[$i]['LinkUrl'],  false, $data[$i]['ChannelID']);
		if( empty($Field) || false !== stripos($Field, 'ChannelUrl') ){
			$data[$i]['ChannelUrl'] = ChannelUrl($data[$i]['ChannelID'], $data[$i]['ChannelHtml'], $data[$i]['ChannelLinkUrl']);
		}
		$data[$i]['Count'] = $Total;
		if($data[$i]['TypeID']>0){
			$data[$i]['HasPriceAttribute'] = $mt->hasPriceAttribute($data[$i]['TypeID']);
		}else{
			$data[$i]['HasPriceAttribute'] = 0;
		}
	}
	return $data;
}

/**
 *  获取实际的LabelID
 * @param $labelID，可能是hot，recommend，或者是：1,2,3
 */
function ChannelLabelID($LabelID, $ChannelID){
    if(is_numeric($ChannelID) && $ChannelID>0 && ($LabelID=='hot' || $LabelID=='recommend')){
        $ChannelModelID = ChannelModelID($ChannelID);
        $map = array();
        $map[30] = array('hot'=>1, 'recommend'=>3); //文章
        $map[31] = array('hot'=>4, 'recommend'=>6); //图片
        $map[34] = array('hot'=>7, 'recommend'=>9); //视频
        $map[35] = array('hot'=>10, 'recommend'=>12); //下载
        $map[36] = array('hot'=>13, 'recommend'=>15); //产品
        if(isset($map[$ChannelModelID][$LabelID])){
            $LabelID = $map[$ChannelModelID][$LabelID];
        }
    }
    return $LabelID;
}

/**
 * 获取类型属性
 * @param int $type 1：所有属性、2：规格属性、3：检索条件属性
 * @param int $infoid 信息ID，仅type=2、3时有效
 * @param int $channelid 频道ID，仅type=1时有效
 * @param int $specialid 专题ID，仅type=1时有效
 * @param int $minprice 最小价格，仅type=1时有效
 * @param int $maxprice 最大价格，仅type=1时有效
 */
function get_type_attribute($type=1, $infoid=-1, $channelid=-1, $specialid=-1, $minprice=-1, $maxprice=-1){
	if( !is_numeric($infoid)) $infoid = -1;
	if( !is_numeric($channelid)) $channelid = -1;
	if( !is_numeric($specialid)) $specialid = -1;
	if( !is_numeric($minprice)) $minprice = -1;
	if( !is_numeric($maxprice)) $maxprice = -1;
	$m = D('Admin/TypeAttribute');
    $data = array();
	switch($type){
		case 1:
			$data = $m->getAllAttribute($infoid); //显示所有属性和分组
			break;
		case 2:
			$data = $m->getSpecAttribute($infoid); //仅显示规格
			break;
		case 3:
			$data = $m->getConditionAttribute($channelid, $specialid, $minprice, $maxprice); //显示筛选条件属性
			break;
	}
	return $data;
}

/**
 * 获取选择属性数据
 * @param string $attr 属性值，多个属性值以下划线隔开
 */
function get_selected_attribute($attr='', $specialid=-1, $minprice=-1, $maxprice=-1){
	if( empty($attr) ) return false;
	if( !preg_match('/^([0-9]+_?)+$/',$attr) ) return false;
	$m = D('Admin/TypeAttributeValue');
	$data = $m->getSelectedAttribute($attr, $specialid, $minprice, $maxprice);
	return $data;
}

/**
 * 执行sql返回数据
 * @param string $sql   //sql不能包含< > 引号等特殊字符,<用&lt;替代，>用&gt;替代, 单引号用^替代
 * @param int $page 小于等于0表示不分页
 */
function get_data($sql){
	//sql合法性检验
	$prefix = strtolower(substr($sql, 0, 6));
	if( $prefix != 'select' ) return false;
	/*  建议使用<php>$sql="";</php>定义变量，可以使用任何字符
	$chars = array(
	    '&lt;'=>'<', '&gt;'=>'>', '^^'=>'"', '^'=>"'",
        ' nheq '=>' !== ',
        ' heq '=>' === ',' neq '=>' != ',
        ' eq '=>' == ',' egt '=>' >= ',
        ' gt '=>' > ',' elt '=>' <= ',' lt '=>' < '
	);
	$sql = str_replace(array_keys($chars), array_values($chars), $sql);
	*/
	$banTable = array('admin','admin_group','member','member_group','config', 'order', 'log', 'pay', 'point',
     'secret', 'template', 'token', 'wx_', 'oauth', 'coupon', 'cash', 'app_', 'cart');
	$tablePrefix = C('DB_PREFIX');
	foreach($banTable as $v){
		if( stripos($sql, $tablePrefix.$v) ){
			return false;
		}
	}
	$m = new Model();
	$data = $m->query($sql);
	return $data;
}

/**
 * datalist标签使用
 */
function get_mydata($value, $field, $limit, $rowdelimiter, $columndelimiter){
    if(empty($value)) return false;
    //设置默认的分隔符
    if(empty($rowdelimiter)) {
        $rowdelimiter=($field==1) ? '{[r]}' : "\n";
    }
    if(empty($columndelimiter)) {
        $columndelimiter=($field==1) ? '{[c]}': ",";
    }
    $type = '';
    if(empty($field)) {
        $field = 'Name,Value,Content,Field4,Field5,Field6,Field7,Field8,Field9';
    }elseif($field==1){ //针对list类型特殊处理，定义默认的分隔符
        $field = 'Title,SubTitle,Picture,SubPicture,Description,Link,LinkText';
        $type = 'list';
    }
    $field = explode(',', $field);
    if(empty($limit)) {
        $limit = is_array($field) ? count($field) : 0;
    }
    if($rowdelimiter=="\n"){
        $value = str_replace(array("\r\n","\r"), "\n", $value);
    }
    if($columndelimiter=="\r\n"){
        $value = str_replace(array("\r\n","\r"), "\n", $value);
    }
    $items = explode ($rowdelimiter, $value);
    $datalist = array();
    foreach($items as $v){
        $temp = explode($columndelimiter, $v, $limit);
        $value = array();
        $n = count($temp);
        for($i=0; $i<$n; $i++){
            $name = $field[$i];
            if($type=='list'){
                if($name == 'Link' || $name == 'Description'){
                    $temp[$i] = nl2br($temp[$i]);
                }
                if($name == 'Link' && is_numeric($temp[$i])){
                    $ChannelID = $temp[$i];
                    if($ChannelID == -2){ //表示无连接
                        $value['LinkUrl'] = 'javascript:void(0);';
                        $value['LinkTarget'] = '_self';
                    }elseif($ChannelID == -1){ //自定义链接
                        $value['LinkUrl'] = $temp[$i+1];
                        $value['LinkTarget'] = '_blank';
                    }elseif($ChannelID>0){ //频道链接
                        $value['LinkUrl'] = ChannelUrl($ChannelID);
                        $value['LinkTarget'] = '_blank';
                    }
                }
            }
            $value[$name] = $temp[$i];
        }
        unset($value['LinkText']);
        $datalist[] = $value;
    }
    return $datalist;
}

/**
 * 获取list的个数
 */
function ListCount($str){
    $n = 0;
    $data = explode ('{[r]}', $str);
    if(!empty($data)){
        $n = is_array($data) ? count($data) : 0;
    }
    return $n;
}

/**
 * 返回list个数占比
 */
function ListPercent($str){
    $per = 0;
    $n = ListCount($str);
    if($n > 0) $per = round(100.0/$n, 4);
    return $per;
}

/**
 * 返回json数组
 * @param string $url
 */
function get_json($url, $method, $datakey){
    $params = $_REQUEST;
    if('get' == strtolower($method)){
        $result = yd_curl_get($url, $params, 30);
    }else{
        $result = yd_curl_post($url, $params, 30);
    }
    $result = json_decode($result, true); //返回二维数组，便于voilist遍历
    if(!empty($datakey)){
        $result = $result[$datakey];
    }
	return $result;
}

//根据主键获取表中字段值, $tableName:不带前缀
function get_single_value($id, $tableName, $FieldName){
	if(!is_numeric($id)) return false;
	$key = $tableName.$FieldName.$id;
	static $_cache = array();
	if (isset($_cache[$key])){
		return $_cache[$key];
	}
	switch ( strtolower($tableName) ){
		case 'info': //信息
			$m = D('Admin/Info');
			$result = $m->where("InfoID=$id and IsEnable=1 and IsCheck=1")->getField($FieldName);
			break;
		case 'channel': //频道
			$m = D('Admin/Channel');
			$where = "ChannelID=$id and IsEnable=1 ";
			if( $FieldName == 'LinkUrl' ){ //必须为链接模型才返回LinkUrl
					$where .= ' and ChannelModelID = 33';
			}
			$result = $m->where($where)->getField($FieldName);
			break;
		case 'channel_model':
			$m = D('Admin/ChannelModel');
			$result = $m->where("ChannelModelID=$id")->getField($FieldName);
			break;
		case 'banner': //幻灯
			$m = D('Admin/Banner');
			$result = $m->where("BannerID=$id and IsEnable=1")->getField($FieldName);
			break;
		case 'bannergroup': //幻灯片分组
			$m = D('Admin/BannerGroup');
			$result = $m->where("BannerGroupID=$id")->getField($FieldName);
			break;
		case 'link': //友情链接
			$m = D('Admin/Link');
			$result = $m->where("LinkID=$id and IsEnable=1")->getField($FieldName);
			break;
		case 'linkclass': //友情链接分类
			$m = D('Admin/LinkClass');
			$result = $m->where("LinkClassID=$id")->getField($FieldName);
			break;
		case 'ad': //广告
			$m = D('Admin/Ad');
			$result = $m->where("AdID=$id and IsEnable=1")->getField($FieldName);
			break;
		case 'adgroup': //广告分组
			$m = D('Admin/AdGroup');
			$result = $m->where("AdGroupID=$id and IsEnable=1")->getField($FieldName);
			break;
		case 'job': //职位
			$m = D('Admin/Job');
			$result = $m->where("JobID=$id and IsEnable=1")->getField($FieldName);
			break;
		case 'tag': //自定义标签
			$m = D('Admin/Tag');
			$result = $m->where("TagID=$id and IsEnable=1")->getField($FieldName);
			break;
		case 'special': //专题
			$m = D('Admin/Special');
			$result = $m->where("SpecialID=$id and IsEnable=1")->getField($FieldName);
			break;
		case 'guestbook': //留言
			$m = D('Admin/Guestbook');
			$result = $m->where("MessageID=$id")->getField($FieldName);
			break;
		case 'label': //频道属性标记
			$m = D('Admin/Label');
			$result = $m->where("LabelID=$id")->getField($FieldName);
			break;
		case 'member': //会员信息
			$m = D('Admin/Member');
			$result = $m->where("MemberID=$id and IsLock=0 and IsCheck=1")->getField($FieldName);
			break;
		default:
			$result = false;
	}
	$_cache[$key] = $result;
	return $result;
}

//频道单字段 Start===============================================
function HasChild($id){
	$HasChild = get_single_value($id, 'Channel', 'HasChild');
	return $HasChild;
}

//当前频道顶层频道是否有子频道
function TopHasChild($id){
	$result = HasChild($id);
	if($result==1) return 1;
	
	$result = HasParent($id);
	if($result==1) return 1;
	
	return 0;
}

function HasParent($id){
	$result = get_single_value($id, 'Channel', 'Parent');
	return $result > 0 ? 1: 0;
}
//指定频道的最顶层频道ID
function TopChannelID($id){
	$m = D('Admin/Channel');
	$topChannelID = $m->getTopChannel($id);
	return $topChannelID;
}

//$value不为false时表示赋值缓存频道名称，可以极大的提升ChannelName函数性能
function ChannelName($id, $name=false){
	static $_cache = array();
	if(false === $name){
		if (isset($_cache[$id])){
			return $_cache[$id];
		}
		$m = D('Admin/Channel');
		$where['ChannelID'] = intval($id);
		$where['IsEnable'] = 1;
		$result = $m->where($where)->getField('ChannelName');
		$_cache[$id] = $result;
		return $result;
	}else{ //缓存频道名称
		$_cache[$id] = $name;
	}
}
function ChannelModelName($id){	return  get_single_value($id, 'Channel_Model', 'ChannelModelName');}
function ChannelModelID($id){
    return  get_single_value($id, 'Channel', 'ChannelModelID');
}
function ChannelTarget($id){	return  get_single_value($id, 'Channel', 'ChannelTarget');}
function ChannelOrder($id){	return  get_single_value($id, 'Channel', 'ChannelOrder');}

function ChannelUrl($id, $html=false, $linkurl=false, $langmark = false){
	static $_cache = array();
	if (isset($_cache[$id])){
		return $_cache[$id];
	}
	
	if($langmark === false){
		$prefix = LANG_PREFIX;
	}else{ //显式传入$langmark
		$prefix = $langmark != C('DEFAULT_LANG') ? '/'.$langmark : '';
	}
	
	//如果存在欢迎页=================================================
	if($html=='index') { //旧版：$id == 1 || $id== 2， 通过静态页面名称是否等于index来判断是否为首页
        $url =  (strtolower(GROUP_NAME) != 'wap') ? __APP__ : __GROUP__;
        //存在bug，没有电脑站并默认为Wap时，英文版首页地址为：/index.php/en,会调到home分组
        //解决方案：将配置文件config路由默认home删除即可
        $url .= $prefix;
        if( $url == '' ) $url = '/';  //当在public/login时，若__APP__为空，则首页为/public地址
        $url = AppendWxUrlPara($url);
        $_cache[$id] = $url;
        return $url;
	}
	//===========================================================
	
	//转向链接======================================================
	if( false === $linkurl ){ //无显式参数传入
		$linkurl = ChannelLinkUrl($id);
		if( !empty( $linkurl ) ) {
			$url = AppendWxUrlPara($linkurl);
			$_cache[$id] = $url;
			return $url;
		}
	}else if( !empty($linkurl) ){ //有参数插入，并且不为空
		$url = AppendWxUrlPara($linkurl);
		$_cache[$id] = $url;
		return $url;
	}
	//============================================================
	
	//非转向链接的情况==============================
	if( false === $html){  //无显式参数传入
		$html = ChannelHtml($id);
	}
	//=========================================
	
	if(C('URL_ROUTER_ON')){//支持路由
		$url = (strtolower(GROUP_NAME) != 'wap') ? U($prefix."/$html") : U('/wap'.$prefix.'/'.$html);
	}else{
		$url = (strtolower(GROUP_NAME) != 'wap') ? U('channel/index', "id=$html") : U('wap/channel/index', "id=$html");
	}
	$url = AppendWxUrlPara($url);
	$_cache[$id] = $url;
	return $url;
}

function ChannelLinkUrl($id){
	$linkurl = get_single_value($id, 'Channel', 'LinkUrl');
	return $linkurl;
}
function ChannelSName($id){
	return get_single_value($id, 'Channel', 'ChannelSName'); 
}
function ChannelContent($id, $count = 0, $suffix='...'){
	$content = get_single_value($id, 'Channel', 'ChannelContent');
	if( $count > 0 ){
		$len = mb_strlen( $content , 'utf-8');  //获取实际内容的长度
		if( $len > $count ){ //截取字符串
			$content = msubstr( $content, 0, $count, 'utf-8', $suffix);
		}
	}
	$content = ParseTag($content);
	return $content;
}
function ChannelSContent($id, $count = 0, $suffix='...'){
	$content = get_single_value($id, 'Channel', 'ChannelSContent');
	if( $count <= 0 ) return $content;
	$len = mb_strlen( $content , 'utf-8');  //获取实际内容的长度
	if( $len > $count ){ //截取字符串
		$content = msubstr( $content, 0, $count, 'utf-8', $suffix);
	}
	return $content;
}
function ChannelPicture($id){
	return get_single_value($id, 'Channel', 'ChannelPicture');
}
function ChannelReadTemplate($id){
	return get_single_value($id, 'Channel', 'ReadTemplate');
}
function ChannelIndexTemplate($id){
	return get_single_value($id, 'Channel', 'IndexTemplate');
}
function ChannelReadLevel($id){	return get_single_value($id, 'Channel', 'ReadLevel');}
function PageSize($id){	return get_single_value($id, 'Channel', 'PageSize');}
function ChannelParent($id){
	return get_single_value($id, 'Channel', 'Parent');
}
function ChannelIcon($id){
	return get_single_value($id, 'Channel', 'ChannelIcon');
}
function ChannelStyle($id){return get_single_value($id, 'Channel', 'ChannelStyle');}
function ChannelF1($id){
	return get_single_value($id, 'Channel', 'f1');
}
function ChannelF2($id){
	return get_single_value($id, 'Channel', 'f2');
}
function ChannelF3($id){
	return get_single_value($id, 'Channel', 'f3');
}

function ChannelHtml($id){	return get_single_value($id, 'Channel', 'Html');}
function ChannelTitle($id){	return  get_single_value($id, 'Channel', 'Title');}
function ChannelKeywords($id){	return  get_single_value($id, 'Channel', 'Keywords');}
function ChannelDescription($id){	return  get_single_value($id, 'Channel', 'Description');}
function ChannelIsShow($id){	return  get_single_value($id, 'Channel', 'IsShow');}
//频道单字段 End================================================

//频道属性标记 Start==============================================
function LabelName($id){
	$name = get_single_value($id, 'Label', 'LabelName');
	return $name;
}
//频道属性标记 End===============================================

//友情链接 start===============================================
//友情链接 Start==============================================
function LinkClassName($id){
	return get_single_value($id, 'LinkClass', 'LinkClassName');
}
function LinkName($id){
	return get_single_value($id, 'Link', 'LinkName');
}
function LinkClassID($id){
	return get_single_value($id, 'Link', 'LinkClassID');
}
function LinkType($id){
	return get_single_value($id, 'Link', 'LinkType');
}
function LinkLogo($id){
	return get_single_value($id, 'Link', 'LinkLogo');
}
function LinkUrl($id){
	return get_single_value($id, 'Link', 'LinkUrl');
}
function LinkDescription($id){
	return get_single_value($id, 'Link', 'LinkDescription');
}
//友情链接 End===============================================

//会员 start================================================
function MemberMobile($id){
	return get_single_value($id, 'Member', 'MemberMobile');
}

function MemberEmail($id){
	return get_single_value($id, 'Member', 'MemberEmail');
}

/**
 * 获取当前会员总积分
 */
function TotalPoint(){
	$TotalPoint = 0;
	$MemberID = session('MemberID');
	if($MemberID>0){
		$m = D('Admin/Point');
		$TotalPoint = $m->getTotalPoint($MemberID);
		if(empty($TotalPoint)) $TotalPoint=0;
	}
	return $TotalPoint;
}

/**
 * 获取当前订单最大可用积分
 */
function MaxUsePoint(){
	$MemberID = session('MemberID');
	$m = D('Admin/Cart');
	$point = $m->getCartExchangePoint($MemberID);
	return $point;
}
//会员 end=================================================

//幻灯片 start===============================================
function BannerGroupName($id){
	return get_single_value($id, 'BannerGroup', 'BannerGroupName');
}
function BannerName($id){
	return get_single_value($id, 'Banner', 'BannerName');
}
function BannerGroupID($id){
	return get_single_value($id, 'Banner', 'BannerGroupID');
}
function BannerImage($id){
	return get_single_value($id, 'Banner', 'BannerImage');
}
function BannerThumbnail($id){
	return get_single_value($id, 'Banner', 'BannerThumbnail');
}
function BannerUrl($id){
	return get_single_value($id, 'Banner', 'BannerUrl');
}
function BannerDescription($id){
	return get_single_value($id, 'Banner', 'BannerDescription');
}
//幻灯片 End===============================================

//专题 start===============================================
function SpecialName($id){
	return get_single_value($id, 'Special', 'SpecialName');
}
function SpecialDescription($id){
	return get_single_value($id, 'Special', 'SpecialDescription');
}
//专题 End===============================================

//自定义标签 start===============================================
function TagName($id){
	return get_single_value($id, 'Tag', 'TagName');
}
function TagContent($id){
	return get_single_value($id, 'Tag', 'TagContent');
}
function TagDescription($id){
	return get_single_value($id, 'Tag', 'TagDescription');
}
//自定义标签 End===============================================

//广告标签 start===============================================
function AdName($id){
	return get_single_value($id, 'Ad', 'AdName');
}
function AdContent($id){
	return get_single_value($id, 'Ad', 'AdContent');
}
function AdUrl($id){
	return get_single_value($id, 'Ad', 'AdUrl');
}
function AdDescription($id){
	return get_single_value($id, 'Ad', 'AdDescription');
}
function AdTime($id){
	return get_single_value($id, 'Ad', 'AdTime');
}
function AdGroupID($id){
	return get_single_value($id, 'Ad', 'AdGroupID');
}
function AdGroupName($id){
	return get_single_value($id, 'AdGroup', 'AdGroupName');
}
function AdGroupDescription($id){
	return get_single_value($id, 'AdGroup', 'AdGroupDescription');
}
//广告标签 End===============================================

//信息单字段 Start===============================================
function ChannelID($id){return get_single_value($id, 'Info', 'ChannelID');}
function InfoTitle($id){return get_single_value($id, 'Info', 'InfoTitle');}
function InfoTime($id){return get_single_value($id, 'Info', 'InfoTime');}
function InfoF1($id){ return get_single_value($id, 'Info', 'f1');}
function InfoF2($id){return get_single_value($id, 'Info', 'f2');}
function InfoF3($id){return get_single_value($id, 'Info', 'f3');}
function InfoF4($id){return get_single_value($id, 'Info', 'f4');}
function InfoF5($id){return get_single_value($id, 'Info', 'f5');}
function InfoPrice($id){return yd_to_money(get_single_value($id, 'Info', 'InfoPrice'));}
//获取信息所属频道模型
function InfoChannelModelID($InfoID, $ChannelID=0){
    if(empty($ChannelID)){
        $ChannelID = ChannelID($InfoID);
    }
    $m = D('Admin/Channel');
    $ChannelModelID = $m->getChannelModelID($ChannelID);
    return $ChannelModelID;
}

function DiscountPrice($id, $infoprice){
	$price = is_numeric($infoprice) ? $infoprice : get_single_value($id, 'Info', 'InfoPrice');
	$price = yd_to_money($price*$GLOBALS['DiscountRate']); //4舍5入保留2位小数
	return $price;
}

/**
 * 计算积分兑换价格=价格-积分x(1/POINT_EXCHANGE_RATE)
 * @param int $id
 * @param int $point
 * @param doube $discountprice
 * @return Ambigous <string, unknown>
 */
function ExchangePrice($id, $point, $discountprice=false){
	$price = is_numeric($discountprice) ? $discountprice : DiscountPrice($id);
	if($point>0){
		$rate = $GLOBALS['Config']['POINT_EXCHANGE_RATE'];
		if($rate>0){
			$price = $price-$point/$rate;
			if($price<0) $price = 0;
			$price = yd_to_money($price); //4舍5入保留2位小数
		}
	}
	return $price;
}

function InfoSContent($id, $count = 0, $suffix='...'){
	$content = get_single_value($id, 'Info', 'InfoSContent');
	if( $count <= 0 ) return $content;
	$len = mb_strlen( $content , 'utf-8');  //获取实际内容的长度
	if( $len > $count ){ //截取字符串
		$content = msubstr( $content, 0, $count, 'utf-8', $suffix);
	}
	return $content;
}
function InfoContent($id, $count = 0, $suffix='...'){
	$content = get_single_value($id, 'Info', 'InfoContent');
	if( $count > 0 ){
		$len = mb_strlen( $content , 'utf-8');  //获取实际内容的长度
		if( $len > $count ){ //截取字符串
			$content = msubstr( $content, 0, $count, 'utf-8', $suffix);
		}
	}
	$content = ParseTag($content);
	return $content;
}
function InfoPicture($id){
	return get_single_value($id, 'Info', 'InfoPicture');
}
function InfoAttachment($id){
	return get_single_value($id, 'Info', 'InfoAttachment');
}
function InfoHtml($id){
	return get_single_value($id, 'Info', 'Html');
}
function InfoLinkUrl($id){
	return get_single_value($id, 'Info', 'LinkUrl');
}

function InfoUrl($id, $html=false, $linkurl=false, $langmark = false, $channelid=false){
	//转向链接======================================================
	if( false === $linkurl ){ //无显式参数传入
		$linkurl = InfoLinkUrl($id);
		if( !empty( $linkurl ) ) {
			$url = AppendWxUrlPara($linkurl);
			return $url;
		}
	}else if( !empty($linkurl) ){ //有参数插入，并且不为空
		$url = AppendWxUrlPara($linkurl);
		return $url;
	}
	//============================================================
	
	if($langmark === false){
		$prefix = LANG_PREFIX;
	}else{ //显式传入$langmark
		$prefix = $langmark != C('DEFAULT_LANG') ? '/'.$langmark : '';
	}
	
	if($channelid !== false){
		$dir = $GLOBALS['Config']['CHANNEL_DATA'][$channelid];
	}else{
		$channelid = ChannelID($id);
		$dir = $GLOBALS['Config']['CHANNEL_DATA'][$channelid];
	}
	if( empty($dir) ) $dir = 'info';
	
	//非转向链接的情况
	if( false === $html){  //无显式参数传入
		$html = InfoHtml($id);
		if( !empty($html) ) $id = $html;
	}else if( !empty($html) ){ //显式传参
		$id = $html;
	}
	
	if(C('URL_ROUTER_ON')){
		$url =  (strtolower(GROUP_NAME) != 'wap') ? U("{$prefix}/{$dir}/{$id}") : U("/wap{$prefix}/{$dir}/{$id}");  //支持路由
	}else{
		$url =  (strtolower(GROUP_NAME) != 'wap') ? U("{$dir}/read", "id={$id}") : U("wap/{$dir}/read", "id={$id}");
	}
	$url = AppendWxUrlPara($url);
	return $url;
}
//获取指定频道信息数
function InfoCount($id){
	$m = D('Admin/Info');
	$n = $m->getInfoCount($id);
	return $n;
}

function InfoOrder($id){return get_single_value($id, 'Info', 'InfoOrder');}
function InfoHit($id){return get_single_value($id, 'Info', 'InfoHit');}
function InfoFrom($id){return get_single_value($id, 'Info', 'InfoFrom');}

function LabelID($id){
	return get_single_value($id, 'Info', 'LabelID');
}
function InfoAlbum($id){
	return get_single_value($id, 'Info', 'InfoAlbum');
}
function InfoRelation($id){
	return get_single_value($id, 'Info', 'InfoRelation');
}

function InfoReadLevel($id){return get_single_value($id, 'Info', 'ReadLevel');}
function SpecialID($id){return get_single_value($id, 'Info', 'SpecialID'); }
function InfoKeywords($id){	return get_single_value($id, 'Info', 'Keywords'); }
function InfoDescription($id){ return get_single_value($id, 'Info', 'Description'); }
//信息单字段 End===============================================

//常见页面Url=================================================
//信息检索
function InfoSearchAction( $ChannelID = 0 ){
	//$url = __APP__.'/'.GROUP_NAME.'/channel/search';
    $ChannelID = intval($ChannelID);
	$url = __GROUP__.'/channel/search';
	if( $ChannelID != 0 ){
		$url .= '/id/'.$ChannelID;
	}
	$url .= '/l/'.LANG_SET;
	return AppendWxUrlPara($url);
}

function InfoSearchUrl( $ChannelID = 0 ){
	return InfoSearchAction( $ChannelID );
}

//邮件订阅Url
function SubscibeUrl($MailClassID = false){
    $MailClassID = intval($MailClassID);
	$url = __GROUP__.'/channel/subscibe';
	if( $MailClassID ){
		$url .= '/id/'.$MailClassID;
	}
	$url .= '/l/'.LANG_SET;
	return $url;
}

//用户注册
function MemberRegAction(){
	$url = __GROUP__.'/public/savereg/l/'.LANG_SET;
	return $url;
}

function MemberSaveRegUrl(){
	return MemberRegAction();
}

function MemberSaveOauth(){
	$url = __GROUP__.'/public/saveOauth/l/'.LANG_SET;
	return $url;
}

//留言板地址
function GuestbookAddAction(){
	$url = __GROUP__.'/channel/guestbookadd/l/'.LANG_SET;
	return $url;
}

function GuestbookAddUrl(){
	return GuestbookAddAction();
}

function CommentAddAction(){
	$url = __GROUP__.'/channel/commentadd/l/'.LANG_SET;
	return $url;
}

function OrderAddAction(){
	$url = __GROUP__.'/channel/orderAdd/l/'.LANG_SET;
	return $url;
}

function ShopUrl($InfoID){
	$url = __APP__.'/wap/public/addCart/l/'.LANG_SET.'?infoid='.$InfoID;
	return $url;
}

//网站首页地址
function HomeUrl(){
	if(stripos($_SERVER['HTTP_USER_AGENT'],"MicroMessenger") === false) {
		return ChannelUrl(0, 'index');
	}else{
		return WxChannelUrl(0, 'index');
	}
}

//手机站首页网址
function WapHomeUrl($langmark=false){
	if($langmark === false){
		$prefix = LANG_PREFIX;
	}else{ //显式传入$langmark
		$prefix = $langmark != C('DEFAULT_LANG') ? '/'.$langmark : '';
	}
	$url =  __APP__.'/wap'.$prefix;
	$domainRules = C('APP_SUB_DOMAIN_RULES');
	if(!empty($domainRules)){ //如果设置了域名
		$data = array_keys($domainRules);
		if( isset($data[0]) ){
			$protocol = get_current_protocal();
			if( 2 == C('URL_MODEL') ){
				$url =  $protocol.$data[0].$prefix;
			}else{
				$url =  $protocol.$data[0].'/index.php'.$prefix;
			}
		}
	}
	$url = AppendWxUrlPara($url);
	return $url;
}

//后台设置的手机版域名
function WapUrl(){
    $url = '';
    $domainRules = C('APP_SUB_DOMAIN_RULES');
    if(is_array($domainRules)){ //如果设置了域名
        $data = array_keys($domainRules);
        $url = isset($data[0]) ? $data[0] : '';
    }
    return $url;
}

//人才招聘地址
function JobUrl(){
	$t = array('en'=>3, 'cn'=>8);
	$id = $t[ get_language_mark() ];
	return ChannelUrl($id);
}

//语言切换地址
function LanguageUrl($mark = 'cn'){
        $langList = C('LANG_LIST');
        if(!empty($langList[$mark]['LanguageDomain'])){
            $url = $langList[$mark]['LanguageDomain'];
        }else{
            $url =  (strtolower(GROUP_NAME) != 'wap') ? __APP__ : __GROUP__;
            if( $url == '' ) $url = '/';
            $url .= '?l='.$mark;
            $url = AppendWxUrlPara($url);
        }
		return $url;
}

/**
 * 是否是装修模式
 */
function isDecorationMode(){
    //在检查语言行为里，session还不可用，所以不能使用session
    //$adminID = intval(session("AdminID"));
    //if(empty($adminID)) return false;
    $fileName = APP_DATA_PATH.'decoration.lock';
    if(APP_DEBUG && file_exists($fileName)){
        return true;
    }else{
        return false;
    }
}

/**
 * 获取多语言列表
 */
function get_language(){
    $enableMultiLanguage = C('LANG_AUTO_DETECT');
    if(empty($enableMultiLanguage)){
        return false;
    }
    $langList = C('LANG_LIST');
    $protocal = get_current_protocal(); //缺陷：不能混合使用http和https
    $tempUrl =  (strtolower(GROUP_NAME) != 'wap') ? __APP__ : __GROUP__;
    if($tempUrl == '') $tempUrl = '/';
    $tempUrl .= '?l=';
    $WebPublic = __ROOT__.'/Public/';
    $port = $_SERVER["SERVER_PORT"];
    $port = ( empty($port) || $port == 80 ) ? '' : ":{$port}";
    $IsDecoration = isDecorationMode();
    $data = array();
    foreach($langList as $v){
        $domain = $v['LanguageDomain'];
        $mark = $v['LanguageMark'];
        //装修模式下，绑定域名失效，否则无法装修（存在跨域iframe问题）
        if($IsDecoration || empty($domain)){
            $url = $tempUrl.$mark;
        }else{
            $url = $protocal.$domain.$port;
        }
        $v['LanguagePicture'] =  "{$WebPublic}Images/mark/{$mark}.png";
        $v['LanguageUrl'] = $url;
        $data[] = $v;
    }
    return $data;
}

//网站地图地址
function SitemapUrl(){
	$t = array('en'=>14, 'cn'=>3);
	$id = $t[ get_language_mark() ];
	$url = ChannelUrl($id);
	return $url;
}

//在线订购地址
function OrderUrl($InfoID=false){
	$html = 'order';
	if(C('URL_ROUTER_ON')){
		$url = (strtolower(GROUP_NAME) != 'wap') ? U(LANG_PREFIX."/$html$InfoID") : U("/wap".LANG_PREFIX."/$html$InfoID");  //支持路由
	}else{
		$url =  (strtolower(GROUP_NAME) != 'wap') ? U('channel/index', array('id'=>$html,'infoid'=>$InfoID)) : U('/wap/channel/index', array('id'=>$html,'infoid'=>$InfoID));
	}
	return AppendWxUrlPara($url);
}


//投递简历地址
function ResumeUrl($JobID=false){
	//$t = array('en'=>11, 'cn'=>10);
	//$id = $t[ get_language_mark() ];
	//$html = ChannelHtml($id);
	$html = 'resume';
	if(C('URL_ROUTER_ON')){
		$url = (strtolower(GROUP_NAME) != 'wap') ? U(LANG_PREFIX."/$html$JobID") : U("/wap".LANG_PREFIX."/$html$JobID");  //支持路由
	}else{
		$url =  (strtolower(GROUP_NAME) != 'wap') ? U('channel/index', array('id'=>$html,'jobid'=>$JobID)) : U('/wap/channel/index', array('id'=>$html,'jobid'=>$JobID));
	}
	return AppendWxUrlPara($url);
}

//API接口网址
function ApiUrl($type){
	if(GROUP_NAME=='Home' || GROUP_NAME=='Wap'){
		$url = __GROUP__.'/api/'.$type.'/l/'.LANG_SET;
	}else{
		$url = __APP__.'/api/'.$type.'/l/'.LANG_SET;
	}
	return $url;
}

//用户登录
function MemberLoginUrl(){
	$url = __GROUP__.'/public/login/l/'.LANG_SET;
	return AppendWxUrlPara($url);
}

//用户注册
function MemberRegUrl(){
	$url = __GROUP__.'/public/reg/l/'.LANG_SET;
	return AppendWxUrlPara($url);
}

//手机注册验证码链接
function SmsCodeUrl(){
	$url = __GROUP__.'/public/sendSmsCode/l/'.LANG_SET;
	return $url;
}

//忘记密码
function MemberForgetUrl(){
	$url = __GROUP__.'/public/forget/l/'.LANG_SET;
	return AppendWxUrlPara($url);
}

//登陆验证
function CheckMemberUrl(){
	$url = __GROUP__.'/public/checkLogin';
	return $url;
}

//用户后台首页
function MemberUrl(){
	$url = __APP__.'/member/public/index';
	return $url;
}

function WapMemberUrl(){
	$url = __APP__.'/member/mobile/index';
	return $url;
}

function MemberLogoutUrl(){
	$url = __APP__.'/member/public/logout';
	return $url;
}

//反馈表单提交
function FeedbackUrl(){
	$url =  __GROUP__.'/Channel/feedbackAdd/l/'.LANG_SET;
	return $url;
}

//邮件发送
function MailUrl($isverify = 1){
	$url =  __GROUP__.'/public/sendMail?isverify='.$isverify;
	return $url;
}

/**
 * 生成验证码
 * @param string $verifyName 验证码session名称
 * @param int $width 宽度
 * @param int $height 高度
 * @param int $length 验证码个数
 * @param int $mode 模式 0:字母, 1:数字, 2:大写字母, 3:小写字母, 4:中文, 5:混合
 * @param string $type 验证码图片格式
 */
function CodeUrl($verifyName='verify',$width = 22, $height = 22, $length = 4, $mode = 1, $type = 'png'){
	$get = "?verify=$verifyName&width=$width&height=$height&length=$length&mode=$mode&type=$type";
	return __GROUP__."/public/verifyCode".$get;
}
//表单Action end==================================================


//微信实用函数start==============================================
//微信API接口地址
function WxApiUrl(){
	$v = C('URL_MODEL');
	$url = get_web_url();
	if($v == 1){
		$url .= '/index.php';
	}
	$url .= '/public/wxapi/';
	return $url;
}

//2013年附加微信参数，修复不能拨号的bug，$iswx＝true表示确定是微信
function AppendWxUrlPara($url, $iswx=false){
	return $url;  //2017-8-31 最近发现不附加这串字符也可以拨号，所以直接返回
    /*
	if( $iswx || stripos($_SERVER['HTTP_USER_AGENT'],"MicroMessenger") ) {
		//判断url中是否存在?参数
		$append = (strpos($url, '?') === false ) ? '?' : '&';
		$url .= $append.C('WX_URL_APPEND');
	}
	return $url;
    */
}

function WxLotteryUrl($appID){
    $appID = intval($appID);
	$url = get_wx_url()."/app/lottery?appid=$appID";
	$url = AppendWxUrlPara($url, true);
	return $url;
}

//投票Url
function WxVoteUrl($appID){
    $appID = intval($appID);
	$url = get_wx_url()."/app/vote?appid=$appID";
	$url = AppendWxUrlPara($url, true);
	return $url;
}

//调查Url
function WxResearchUrl($appID){
    $appID = intval($appID);
	$url = get_wx_url()."/app/research?appid=$appID";
	$url = AppendWxUrlPara($url, true);
	return $url;
}

//提交投票
function WxVoteAddUrl($appID){
    $appID = intval($appID);
	$url =  __GROUP__.'/channel/voteAdd?appid='.$appID;
	return $url;
}

//会员卡首页地址
function WxCardUrl(){
	$url = get_wx_url()."/app/card";
	$url = AppendWxUrlPara($url, true);
	return $url;
}

function WxCardStoreUrl(){
	$url = get_wx_url()."/app/cardStore";
	$url = AppendWxUrlPara($url, true);
	return $url;
}

//会员卡说明地址
function WxCardInfoUrl(){
	$url = get_wx_url()."/app/cardInfo";
	$url = AppendWxUrlPara($url, true);
	return $url;
}

//会员卡通知管理
function WxCardNotifyUrl(){
	$url = get_wx_url()."/app/cardNotify";
	$url = AppendWxUrlPara($url, true);
	return $url;
}

//我的消费记录
function WxCardMyConsumeUrl(){
	$url = get_wx_url()."/app/cardMyConsume";
	$url = AppendWxUrlPara($url, true);
	return $url;
}

//我的积分记录
function WxCardMyScoreUrl(){
	$url = get_wx_url()."/app/cardMyScore";
	$url = AppendWxUrlPara($url, true);
	return $url;
}

//我的兑换记录
function WxCardMyExchangeUrl(){
	$url = get_wx_url()."/app/cardMyExchange";
	$url = AppendWxUrlPara($url, true);
	return $url;
}

//会员资料
function WxCardMemberUrl(){
	$url = get_wx_url()."/app/cardMember";
	$url = AppendWxUrlPara($url, true);
	return $url;
}

//微信绑定会员
function WxBindMemberUrl(){
	$url = get_wx_url()."/app/bindMember";
	$url = AppendWxUrlPara($url, true);
	return $url;
}


//会员特权地址
function WxCardPrivilegeUrl(){
	$url = get_wx_url()."/app/cardprivilege";
	$url = AppendWxUrlPara($url, true);
	return $url;
}

//会员卡优惠卷地址
function WxCouponUrl(){
	$url = get_wx_url()."/app/cardCoupon";
	$url = AppendWxUrlPara($url, true);
	return $url;
}

//会员卡兑换
function WxExchangeUrl(){
	$url = get_wx_url()."/app/cardExchange";
	$url = AppendWxUrlPara($url, true);
	return $url;
}

//会员卡签到
function WxCardScoreUrl(){
	$url = get_wx_url()."/app/cardScore";
	$url = AppendWxUrlPara($url, true);
	return $url;
}

//微应用Url地址
function WxAppUrl($AppID, $AppTypeID=false){
	$AppTypeID = (false === $AppTypeID) ? WxAppTypeID($AppID) : $AppTypeID;
	switch($AppTypeID){
		case 1: //微活动
			$url = WxLotteryUrl($AppID);
			break;
		case 2: //微投票
			$url = WxVoteUrl($AppID);
			break;
		case 5: //微调查
			$url = WxResearchUrl($AppID);
			break;
		case 6: //微会员卡
			$url = WxCardUrl();
			break;
		default:
			$url = "";
	}
	return $url;
}

//生成微信Token唯一字符串
function WxToken(){
	$name = explode('.', get_web_url());
	if( !empty( $name[1] ) ){
		$prefix = strtoupper( trim( $name[1] ) ) ;
		$prefix = str_replace('-', '', $prefix);
	}else{
		$prefix = 'YOUDIANCMS';
	}
	$token = $prefix.date('YmdHis');
	//$token必须为英文或数字，长度为3－32字符
	if( strlen($token) > 32 ){
		$token = substr($token, 0, 32);
	}
	return $token;
}

function WxAppName($appID){
    $appID = intval($appID);
	$m = D('Admin/WxApp');
	$AppName = $m->where("AppID={$appID}")->getField('AppName');
	return $AppName;
}

function WxAppTypeID($appID){
    $appID = intval($appID);
	$m = D('Admin/WxApp');
	$AppTypeID = $m->where("AppID=$appID")->getField('AppTypeID');
	return $AppTypeID;
}

//微信频道Url，带wx=1后缀
function WxChannelUrl($id, $html=false, $linkurl=false, $langmark = false){
	static $_cache = array();
	if (isset($_cache[$id])){
		return $_cache[$id];
	}

	if($langmark === false){
		$prefix = LANG_PREFIX;
	}else{ //显式传入$langmark
		$prefix = $langmark != C('DEFAULT_LANG') ? '/'.$langmark : '';
	}

	$url = get_wx_url();  //返回微信绝对地址
	//如果存在欢迎页=================================================
	if($html == 'index') { //$id == 1 || $id== 2
		$HasWelcome = file_exists( THEME_PATH.'channel/welcome.html' );
		if( $HasWelcome == false){
			$url .= $prefix;
			$url = AppendWxUrlPara($url, true);
			$_cache[$id] = $url;
			return $url;
		}
	}
	//===========================================================

	//转向链接======================================================
	if( false === $linkurl ){ //无显式参数传入
		$linkurl = ChannelLinkUrl($id);
		if( !empty( $linkurl ) ) {
			$linkurl = WxAbsoulteUrl( $linkurl );
			return AppendWxUrlPara($linkurl, true);
		}
	}else if( !empty($linkurl) ){ //有参数插入，并且不为空
		$linkurl = WxAbsoulteUrl( $linkurl );
		return AppendWxUrlPara($linkurl, true);
	}
	//============================================================

	//非转向链接的情况==============================
	if( false === $html){  //无显式参数传入
		$html = ChannelHtml($id);
	}
	//=========================================
	$url .= $prefix.'/'.$html;
	$url = AppendWxUrlPara($url, true);
	$_cache[$id] = $url;
	return $url;
}

//转化为微信绝对地址
function WxAbsoulteUrl($url){
	$resourceUrl = '';
	if(!empty($url)){
		if( strtolower( substr($url, 0, 7) ) == 'http://' || strtolower( substr($url, 0, 8) ) == 'https://'
				|| strtolower( substr($url, 0, 6) ) == 'ftp://' ){
			$resourceUrl = $url;
		}else{
			$resourceUrl = get_wx_url().$url; //必须是绝对地址
		}
	}
	return $resourceUrl;
}

//微信信息Url地址
function WxInfoUrl($id, $html=false, $linkurl=false, $langmark = false, $channelid=false){
	//转向链接======================================================
	if( false === $linkurl ){ //无显式参数传入
		$linkurl = InfoLinkUrl($id);
		if( !empty( $linkurl ) ) {
			$linkurl = WxAbsoulteUrl( $linkurl );
			return AppendWxUrlPara($linkurl, true);
		}
	}else if( !empty($linkurl) ){ //有参数插入，并且不为空
		$linkurl = WxAbsoulteUrl( $linkurl );
		return AppendWxUrlPara($linkurl, true);
	}
	//============================================================
	if($channelid !== false){
		$dir = $GLOBALS['Config']['CHANNEL_DATA'][$channelid];
	}else{
		$channelid = ChannelID($id);
		$dir = $GLOBALS['Config']['CHANNEL_DATA'][$channelid];
	}
	if( empty($dir) ) $dir = 'info';
	
	if($langmark === false){
		$prefix = LANG_PREFIX;
	}else{ //显式传入$langmark
		$prefix = $langmark != C('DEFAULT_LANG') ? '/'.$langmark : '';
	}
	$url = get_wx_url();  //返回微信绝对地址
	//非转向链接的情况
	if( false === $html){  //无显式参数传入
		$html = InfoHtml($id);
		if( !empty($html) ) $id = $html;
	}else if( !empty($html) ){ //显式传参
		$id = $html;
	}
	$url .= "{$prefix}/{$dir}/{$id}";
	$url = AppendWxUrlPara($url, true);
	return $url;
}

//菜单颜色
function WxMenuTypeColor($MenuID){
	$color = array(
			1=>'#000000',  2=>'#90F',  3=>'blue',  4=>'green',  5=>'#630',
			6=>'red',  7=>'#960', 8=>'#F90',
	);
	if( isset($color[$MenuID]) ){
		return $color[$MenuID];
	}else{
		return '#000000'; //默认为黑色
	}
}

//判断微信用户的ID是否有效
function wxUserExist($fromUser){
	$m = D('Admin/Member');
	$b = $m->wxUserExist($fromUser);
	return $b;
}

//微信实用函数end==============================================

//模板实用函数start==============================================
function Left($str, $len = 0, $suffix='...'){
	if( $len <= 0 ) return $str;
	if( LANG_SET == 'en'){
		$len *= 2;  //英文长度自动加倍
	}
	$n = mb_strlen( $str , 'utf-8');  //获取实际内容的长度
	if( $n > $len ){ //截取字符串
		$str = msubstr( $str, 0, $len, 'utf-8', $suffix);
	}
	$str = yd_close_tags($str);//补齐Html标签
	return $str;
}

//返回默认图片url
function DefaultPicture($url, $default='1'){
	if( strlen(trim($url)) <= 0 ){
		$k = get_language_mark();
		$nopic = APP_TMPL_PATH."Public/Images/nopic$k.jpg";
		$nopic_p = get_web_root().$nopic;
		if( !file_exists( $nopic_p ) ){
			$nopic = __ROOT__.'/Public/Images/nopic/'.$default.$k.'.jpg';
		}
		return $nopic;
	}
	return $url;
}

//解析模板设置的字体字符串
function ParseFont($style){
    if(empty($style)) return '';
    //$style = ",18,#FFFFFF,1.2,,,,,center,10";
    ///格式：0字体, 1大小, 2颜色, 3行高，4加粗，5斜体，6下划线，7删除线，8对齐方式，9手机端字体，10左右内边距
    $data = explode(',', $style);
    $css = '';
    if(!empty($data[0])){
        $css .= "font-family:{$data[0]};";
    }

    //如果手机端字体大小为空，表示（空：跟随电脑，0：不显示）=======================
    $fontSize = false; //默认为无效值
    $mobileFontSize = isset($data[9]) ? $data[9] : null;
    if(0 != strlen($mobileFontSize) && yd_is_mobile()){
        if($mobileFontSize>0 && $mobileFontSize < 5){ //放大倍数
            $fontSize = is_numeric($data[1]) ? round($mobileFontSize*$data[1]) : false;
        }else{
            $fontSize = $mobileFontSize;
        }
    }else{
        if(isset($data[1]) && is_numeric($data[1])){
            $fontSize = $data[1];
        }
    }
    if(false!==$fontSize){
        if(0==$fontSize){ //等于0直接隐藏
            $css .= "display:none;";
        }elseif(!empty($fontSize)){
            $css .= "font-size:{$fontSize}px;";
        }
    }
    //===============================================================

    if(!empty($data[2])){
        $css .= "color:{$data[2]};";
    }
    if(!empty($data[3])){
        $css .= "line-height:{$data[3]}em;";
    }
    //左右内边距
    if(isset($data[10]) && strlen($data[10])>0){
        $css .= "padding-left:{$data[10]}px;padding-right:{$data[10]}px;";
    }
    $css .= (isset($data[4]) && $data[4]==1) ? 'font-weight: bold;' :'font-weight: normal;'; //加粗
    $css .= (isset($data[5]) && $data[5]==1) ? 'font-style: italic;' :'font-style: normal;'; //斜体
    $css .= (isset($data[6]) && $data[6]==1) ? 'text-decoration: underline;' :''; //下划线
    $css .= (isset($data[7]) && $data[7]==1) ? 'text-decoration: line-through;' :''; //删除线
    if(isset($data[8])){
        $css .= "text-align:{$data[8]};"; //对齐方式
    }
    return $css;
}

function ParseLinkTarget($value){
    //格式：链接类型,链接目标,链接值
    $data = explode(',', $value);
    if($data[0]==4){
        $target = '_self';
    }else{
        $target = isset($data[1]) ? $data[1] : '_self';
    }
    return $target;
}

function ParseLinkUrl($value){
    $url = '';
    //格式：0链接类型, 1链接目标, 2链接值
    $data = explode(',', $value);
    $type = intval($data[0]);
    switch($type){
        case 1:  //频道链接
            $url = ChannelUrl($data[2]);
            break;
        case 2: //QQ
            $url = "http://wpa.qq.com/msgrd?v=3&uin={$data[2]}&site=qq&menu=yes";
            break;
        case 3: //电话号码
            $url = "tel:{$data[2]}";
            break;
        case 4: //无链接
            $url= 'javascript:;';
            break;
        case 9: //自定义链接
            $url = $data[2];
            break;
    }
    return $url;
}

/**
 * 解析背景 格式：0背景类型, 1背景颜色(开始),2背景颜色(结束)，3背景图片，4平铺, 5背景大小自适应，6背景位置，7锁定背景位置,8:角度
 */
function ParseBg($style){
    if(empty($style)) return '';
    $css = '';
    $data = explode(',', $style);
    $way = intval($data[0]);
    switch($way){
        case 1: //纯色填充
            $css = "background-color:{$data[1]};";
            break;
        case 2: //渐变
            $angle = empty($data[8]) ? 0 : intval($data[8])%360;
            $css = "background-image: linear-gradient({$angle}deg, {$data[1]}, {$data[2]});";
            break;
        case 3: //图片填充
            if(!empty($data[1])) $css .= "background-color:{$data[1]};";
            if(!empty($data[3])) $css .= "background-image:url({$data[3]});";
            if(!empty($data[4])) $css .= "background-repeat:{$data[4]};";
            if(!empty($data[5])) $css .= "background-size:{$data[5]};";
            if(!empty($data[6])) $css .= "background-position:{$data[6]};";
            if(!empty($data[7])) $css .= "background-attachment:{$data[7]};";
            break;
        case 0: //没有背景
        default:
    }
    return $css;
}

function ParseItem($style, $index){
    $data = explode(',', $style);
    $str = (strlen($data[$index])>0) ? $data[$index] : '';
    return $str;
}

function ParseTable($style){
    if(empty($style)) return '';
    //0宽度，1行数，2列数，3宽度单位，4上下内边距, 5左右内边距，6数据二维列表
    $data = explode(',', $style);
    $tableWidth = !empty($data[0]) ? $data[0].'px' : '100%';
    $unit = !empty($data[3]) ? $data[3] : 'px'; //单位
    $paddingX = !empty($data[4]) ? $data[4] : 3; //默认为3
    $paddingY = !empty($data[5]) ? $data[5] : 5; //默认为5
    $search = array('√', '×', "\n");
    $replace = array('<i class="ydicon-yes"></i>', '<i class="ydicon-no"></i>', '<br/>');
    $content = str_ireplace($search, $replace, $data[6]);
    $rows = explode('{r}', $content);
    $rowsCount = count($rows);
    $map = array(1=>'left', 2=>'center', 3=>'right');
    $tempWidth = explode('{c}', $rows[0]);  //宽度
    $tempAlign = explode('{c}', $rows[1]);  //对齐方式
    $html = "
    <style>.yd-table td .td-item{ padding:{$paddingY}px {$paddingX}px; } </style>
    <table class='yd-table' cellpadding='0' cellspacing='0' style='width:{$tableWidth};'>";
    for($r = 2; $r < $rowsCount; $r++){
        $rowNo = $r - 1;
        $html .= "<tr class='r{$rowNo}'>";
        $cols = explode('{c}', $rows[$r]);
        foreach($cols as $j=>$c){
            $colsNo = $j + 1;
            $style = (2 == $r && $tempWidth[$j]) ? "width:{$tempWidth[$j]}{$unit};" : ''; //只有第一样才有宽度
            $key = $tempAlign[$j];
            $align = isset($map[$key]) ? $map[$key] : 'left';
            $style .= "text-align:{$align}";
            $html .= "<td class='c{$colsNo}' style='{$style}'><div class='td-item'>{$c}</div></td>";
        }
        $html .= "</tr>";
    }
    $html .= '</table>';
    return $html;
}

function ParseButton($style){
    if(empty($style)) return '';
    //格式：0名称、1保留使用、2宽度、3圆角、4边框、5边框颜色、6悬浮背景颜色，7：悬浮文字颜色，8：是否显示
    $data = explode(',', $style);
    $css = '';
    $width = isset($data[2]) ? $data[2] : null;
    $isShow = isset($data[8]) ? $data[8] : null;
    if(is_numeric($isShow) && 0 == $isShow) { //是否显示
        $css .= "display:none;";
    }
    if(is_numeric($width) && 0 == $width) { //隐藏
        $css .= "display:none;";
    }elseif(!empty($width)){
        $css .= "width:{$width}px;";
    }
    if(!empty($data[3])) $css .= "border-radius:{$data[3]}px;";
     if(!empty($data[4]) && !empty($data[5])){
         $css .= "border:{$data[4]}px solid {$data[5]};";
     }
    return $css;
}

function ParseBorder($style){
    if(empty($style)) return '';
    //格式：0边框大小、1边框样式、2边框颜色、3圆角大小、4阴影大小、边框位置
    $data = explode(',', $style);
    $css = '';
    $size = trim($data[0]);
    if(strlen($size)>0){
        $pos = empty($data[5]) ? '' : "-{$data[5]}";
        $css .= "border{$pos}:{$size}px {$data[1]} {$data[2]};";
    }

    //圆角
    $radius = trim($data[3]);
    if(strlen($radius) > 0){
        $css .= "border-radius:{$radius}px;";
    }
    //阴影
    $shadow = trim($data[4]);
    if(is_numeric($shadow)){
        //修改这里，还需要修改模板装修页面previewBorder函数
        $x = $shadow/2;
        $css .= "box-shadow: 0 {$x}px {$shadow}px rgba(0,0,0,.1);";
    }
    return $css;
}

function ParseButtonName($style){
    $data = explode(',', $style);
    return $data[0];
}

//悬浮背景颜色
function ParseButtonHoverColor($style){
    $data = explode(',', $style);
    return $data[6];
}

function ParseButtonShow($style){
    $data = explode(',', $style);
    return $data[8];
}

//悬浮按钮文本颜色
function ParseButtonHoverTextColor($style){
    $data = explode(',', $style);
    return $data[7];
}

//按钮浮动样式
function ParseButtonHover($style){
    if(empty($style)) return '';
    $data = explode(',', $style);
    $css = '';
    if(!empty($data[6])){
        $css .= "background:{$data[6]}; ";
    }else{
        $css .= "opacity: .8;";
    }
    if(!empty($data[7])){
        $css .= "color:{$data[7]};";
    }
    return $css;
}

function ParseXY($value){
    $data = explode(',', $value);
    $style="left:{$data[0]}px; top:{$data[1]}px;";
    return $style;
}

function ParseX($value){
    $data = explode(',', $value);
    return $data[0];
}
function ParseY($value){
    $data = explode(',', $value);
    return $data[1];
}
function ParseZ($value){
    $data = explode(',', $value);
    return $data[2];
}

/**
 * 返回默认头像
 */
function DefaultAvatar($url){
	if( strlen(trim($url)) <= 0 ){
		$nopic = APP_TMPL_PATH."Public/Images/noavatar.png";
		$nopic_p = get_web_root().$nopic;
		if( !file_exists( $nopic_p ) ){
			$nopic = __ROOT__.'/Public/Images/nopic/noavatar.png';
		}
		return $nopic;
	}
	return $url;
}

/**
 * 加载JS
 */
function LoadJsOnce($jsUrl){
    $key = md5($jsUrl);
    static $_cache = array();
    if (isset($_cache[$key])){
        return ''; //如果已经加载，则返回空不在加载
    }
    $jsUrl = "<script type=\"text/javascript\" src=\"{$jsUrl}\"></script>";
    $_cache[$key] = $jsUrl;
    return $jsUrl;
}

/**
 * 加载样式文件
 */
function LoadStyleOnce($cssUrl){
    $key = md5($cssUrl);
    static $_cache = array();
    if (isset($_cache[$key])){
        return ''; //如果已经加载，则返回空不在加载
    }
    $cssUrl = "<link href=\"{$cssUrl}\" rel=\"stylesheet\" type=\"text/css\" />";
    $_cache[$key] = $cssUrl;
    return $cssUrl;
}


//获取手机网站频道图标
function WapChannelIcon($ChannelID, $default='default.jpg'){
	$icon = APP_TMPL_PATH."Public/images/icon/$ChannelID.jpg";
	$icon_p = get_web_root().$icon;
	if( !file_exists( $icon_p ) ){
		$icon = APP_TMPL_PATH."Public/images/icon/$default";
	}
	return $icon;
}

function WapChannelIconPng($ChannelID, $default='default.png'){
	$icon = APP_TMPL_PATH."Public/images/icon/$ChannelID.png";
	$icon_p = get_web_root().$icon;
	if( !file_exists( $icon_p ) ){
		$icon = APP_TMPL_PATH."Public/images/icon/$default";
	}
	return $icon;
}

//返回对应频道模型ID对应的颜色
function ChannelModelColor($ChannelModelID){
	//30文章,31图片,32单页,33链接,34视频,35下载,36产品
	$color = array(
			30=>'#000000',  31=>'#90F',  32=>'blue',  33=>'red',  34=>'#630',
			35=>'#666',  36=>'green', 37=>'#960', 38=>'#000000',    39=>'#000000',
	);
	if( isset($color[$ChannelModelID]) ){
		return $color[$ChannelModelID];
	}else if($ChannelModelID < 30){
		return '#999';
	}else{
		return '#000000'; //默认为黑色
	}
}

/**
 * 替换图片中的src属性，主要用于辅助实现按需加载图片
 * @param string $content
 * @param string $replacement src被替换为这个字符串
 */
function ImageSrcReplace($content, $replacement='data-original='){
	if( stripos($content, '<img') > 0){
		$content = preg_replace('/(<img.+)src=/iU', '$1 '.$replacement, $content);
	}
	return $content;
}
//实用函数End==================================================

//判断是否需要缓存，仅用于ReadHtmlCacheBehavior
function requireCache(){
	$m = strtolower( MODULE_NAME );
	$a = strtolower( ACTION_NAME );
	//首页========================================
	if( $m == 'index' && $a == 'index'){
		$ChannelID = LANG_ID;
		$IsHtml = get_single_value($ChannelID, 'Channel', 'IsHtml');
		return ($IsHtml == 1) ? true : false;
	}
	//===========================================
	
	//频道主页======================================
	if( $m == 'channel' && $a == 'index'){
		$m = D('Admin/Channel');
		$IsHtml = $m->IsHtmlByHtml( $_GET['id'] );
		return ($IsHtml == 1) ? true : false;
	}
	//===========================================
	
	//信息主页======================================
	if( $m == 'info' && $a == 'read'){
		$m = D('Admin/Info');
		$IsHtml = $m->getIsHtml( $_GET['id']  );
		return ($IsHtml == 1) ? true : false;
	}
	//============================================
}

//百度翻译
function baiduTranslate($content, $from='zh', $to = 'en'){
	if( empty($content) || is_numeric($content)){
		$data["Status"] = 1;
		$data["Content"] = $content;
		return $data;
	}
	//为了翻译正常，对待翻译的数据做预处理===================================
	//翻译：<img src='1.jpg' title='my img' />计算 签名";
	//结果：<img src='1.jpg' title='my img' />signature calculation
	//翻译html标记存在的问题：在翻译img，属性值用单引号包含，百度翻译标签可能会删除里面的空格
	//翻译图片会存在问题，因此翻译前做替换
	$content = strip_tags($content, "<img><br><br/>");
	$pattern = array(
			'/<img\s+?[^>]*?[\/]?>/i'
	);
	$search = array();
	$replace = array();
	foreach($pattern as $k=>$p){
		$n = preg_match_all($p, $content, $matchs);
		if( $n > 0 ){
			$i = 0;
			foreach ($matchs[0] as $v){
				$search[] = $v;
				//$replace[]= '{['.$k.$i.']}';
                //不能使用]} 符号，会被其他语言翻译，数字支持绝大多数语言，单不支持泰语
                $replace[]= "9{$k}{$i}";  //最好以9开头，01会被泰语翻译，是901不会被翻译
				$i++;
			}
			$content = str_ireplace($search, $replace, $content);
		}
	}
	//==============================================================

	//翻译常量定义
	$apiurl = "https://api.fanyi.baidu.com/api/trans/vip/translate";
	$appid  = $GLOBALS['Config']['BAIDU_TRANSLATE_APPID'];
	$apikey = $GLOBALS['Config']['BAIDU_TRANSLATE_APIKEY'];
	$timeout = 30; //超时时间，单位：秒

	$text = baiduSplit($content, 5000); //$maxBytes = 5800; //单次请求不能超过6000字节
	$nSize = count($text);
	$data = array();
	for($j=0; $j < $nSize; $j++){
		$myText = trim($text[$j]);
		if( strlen($myText) == 0 ) continue;
		$salt = rand(10000,99999);
		$sign = md5($appid . $myText . $salt . $apikey); //计算签名（appid+q+salt+密钥）
		$p = array('appid'=>$appid,'q'=>$myText, 'salt' => $salt , 'from'=>$from, 'to'=>$to, 'sign'=>$sign);
		$result = yd_curl_post($apiurl, $p, $timeout);
		$result = json_decode($result, true);
		if(empty($result)){
			$data["Status"] = 0;
			$data["ErrorMessage"] = "翻译结果为空，可能是内容有乱码";
		}else if( !isset($result['error_code']) ){ //如果翻译出错，返回：{"error_code":"52003","error_msg":"UNAUTHORIZED USER"}
			$data["Status"] = 1;
			$n = count($result['trans_result']);
			for($i=0;$i<$n;$i++){
				$data['Content'] .= $result['trans_result'][$i]['dst'];
				if($i != $n-1 ){  //段落之间必须加上回车
					$data['Content'] .= "<br/>";
				}
			}
			//反向替换图片
            if(!empty($replace)){
                $data['Content'] = str_ireplace($replace, $search, $data['Content']);
            }
		}else{
			$code = array(
				'52000'=>'成功',
				'52001'=>'请求超时, 请重试',
				'52002'=>'系统错误, 请重试',
				'52003'=>'未授权用户, 请检查您的appid是否正确',
				'54000'=>'必填参数为空, 请检查是否少传参数',
				'58000'=>'客户端IP非法	检查您填写的IP地址是否正确, 请可修改您填写的服务器IP地址',
				'54001'=>'签名错误, 请请检查您的签名生成方法',
				'54003'=>'访问频率受限，请降低您的调用频率',
				'58001'=>'译文语言方向不支持, 请检查译文语言是否在语言列表里',
				'54004'=>'账户余额不足, 请前往管理控制台为账户充值',
				'54005'=>'长query请求频繁, 请降低长query的发送频率，3s后再试',
			);
			$error_code = $result['error_code'];
			$data["Status"] = 0;
			$data["ErrorMessage"] = isset( $code[$error_code] ) ? $code[$error_code] : '翻译异常';
		}
        sleep(1); //百度限制翻译频率，必须延时一秒
	} //for end
	return $data;
}

//单次翻译有最大值限制，必须将长文本分块
function baiduSplit($content, $max = 5000){
	$totalBytes = strlen($content);
    $pos = false;
	if( $totalBytes > $max){
		$delimiter = array(',', '.', '，', '。', "\r\n","\n"); //在标点符号处截断，不会影响翻译
		$prePos = 0;         //上一次位置
		$curPos = $max;  //当前位置
		$maxOffset = $totalBytes + $max;
        $text = array();
		while($curPos <= $maxOffset){
			$offset = $curPos - $totalBytes;
			foreach ($delimiter as $d){
				$pos = strrpos($content, $d, $offset);
				if($pos !== false || $pos > $prePos ) break; //一旦发现，跳出
			}
			if( $pos !== false && $pos > $prePos ) $curPos = $pos;
			$len = $curPos - $prePos;
			$text[] = substr($content, $prePos, $len);
			$prePos = $curPos; //记住上一次的位置
			$curPos += $max;
		}
	}else{
		$text[] = $content;
	}
	return $text;
}

//to 收件人帐号,多个帐号以逗号,分开
function sendwebmail($to, $title, $body){
	if( empty($to) || empty($title) || empty($body)) return false;
	$from = $GLOBALS['Config']['EMAIL_ACCOUNT'];  //发件人账号
	$fromname = $GLOBALS['Config']['EMAIL_SENDER'];  //发件人姓名
	$smtp = $GLOBALS['Config']['EMAIL_SMTP'];
	$pwd = $GLOBALS['Config']['EMAIL_PASSWORD'];
	$port = $GLOBALS['Config']['EMAIL_PORT'];
	$way = $GLOBALS['Config']['EMAIL_WAY'];
	$b = sendmail($from, $fromname, $to, $title, $body, $from, $pwd, $smtp, false, false, $port, $way);
	return $b;
}

/**
 * 
 * @param string 发件人账号
 * @param string 发件人姓名
 * @param mix to 收件人帐号,多个帐号以逗号,分开
 * @param string $title 邮件标题
 * @param string $body 内容
 * @param string $usename  邮箱账号
 * @param string $usepassword  邮箱密码
 * @param string $smtp  smtp服务器
 * @param string $repto
 * @param string $repname
 * @return boolean
 */
function sendmail($from, $fromname, $to, $title, $body, $usename, $usepassword, $smtp, $repto=false, $repname=false, $port=25, $way='tls'){
    yd_set_time_limit(30000);
	include_once('./App/Lib/Common/class.phpmailer.php');
	$mail   = new PHPMailer();
	//$mail->SMTPDebug  = true;
	$mail->CharSet    = "UTF-8"; // charset
	$mail->Encoding   = "base64";

	$mail->IsSMTP(); // telling the class to use SMTP

	//system
	$mail->Port       = $port;
	if(stripos($smtp,'.gmail.com')===false){
		$mail->SMTPSecure = ($way=='tls') ? '' : "ssl";
		$mail->Host       = $smtp; // SMTP server
	}else{
		$mail->Host       = $smtp; // SMTP server
		$mail->SMTPSecure = "ssl";
		//$mail->Host       = 'ssl://'.$smtp; // SMTP server
	}

	$mail->SMTPAuth   = true;
	$mail->Username   = $usename; // SMTP account username
	$mail->Password   = $usepassword;        // SMTP account password

	$mail->From       = $from;//send email
	$mail->FromName   = $fromname; //name of send

	//repet
	if($repto!=""){
		$name = isset($repname)?$repname:$repto;
		$mail->AddReplyTo($repto, $name);
	}
	$mail->WordWrap   = 50; // line

	//title
	$mail->Subject		= (isset($title)) ? $title : '';//title


	//$mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; //

	//body
	//$body  = eregi_replace("[\]",'',$body);
	//已经被PHP7 废弃eregi_replace
	$body  = preg_replace("/\\\/",'',$body); //Remove backslashes
	$mail->MsgHTML($body);

	//to
	if($to){
		$address = explode(",",$to);
		foreach($address AS $key => $val){
			if( yd_is_email($val) ){  //邮件有效才添加
				$mail->AddAddress($val, "");
			}
		}
	}
	//send attech
	//if(isset($data['attach']))
	//{
	//$attach = explode("|",$data['attach']);
	//foreach($attach AS $key => $val)
	//{
	//$mail->AddAttachment($val,"");             // attech
	//}
	//}
	if(!@$mail->Send()) {
		$mail->SmtpClose();
		//return "Mailer Error: " . $mail->ErrorInfo;
		define('PHP_MAILER_ERROR', $mail->ErrorInfo);
		return false;
	} else {
		$mail->SmtpClose();
		//return "Message sent!";
		define('PHP_MAILER_ERROR', '');
		return true;
	}
}

//判断now是否位于[start,end]
//$start:开始时间，$end:结束时间, $now:未指定则为当前时间
function isTimeRange($start, $end, $now=false){
	$flag = 0;
	$s = strtotime($start);
	$e = strtotime($end);
	$n = ($now === false) ? time() : strtotime($now);
	if($n < $s){
		$flag = -1;
	}else if($n>$e){
		$flag = 1;
	}else{
		$flag = 0; //位于范围内
	}
	return $flag;
}

//根据模型，获取所有数据
function getAllInfo(&$data, $ChannelModelID=6){
	$m = D('Admin/Attribute');
	$Attribute = $m->getAttribute( $ChannelModelID );
	$f = array();
	foreach($Attribute as $a){
		if( $a['FieldName'] != 'GuestName'){
			$name = explode(',', $a['DisplayName']);
			$f[] = array( 'FieldName'=>$a['FieldName'], 'DisplayName'=>$name[0] );
		}
	}
	unset($Attribute);
	$n = is_array($data) ? count($data) : 0;
	for($i = 0; $i<$n; $i++){
        $data[$i]['AllInfo'] = '';
		foreach($f as $v){
			if(!empty($data[$i][$v['FieldName']])){
				$value = htmlspecialchars($data[$i][$v['FieldName']]);
				$suffix = strtolower( substr($value, -4) );
				if( $suffix == '.jpg' || $suffix == '.png' || $suffix == '.gif'){
					$value = "<a href='{$value}' target='_blank'><img src='{$value}' class='autoimg' /></a>";
				}
				$data[$i]['AllInfo'] .= "<b class='displayname'>{$v['DisplayName']}：</b>{$value}<br/>";
			}
		}
	}
}

//解析微信应用参数开始========
//$out:传入数组，用于输出
function parseAppParameter($parameter, $type, &$out){
	$p = explode('@@@', $parameter);
	switch($type){
		case 1:  //微活动
			$out['LotteryType'] = $p[0];
			$out['LotteryIntroduction'] = $p[1];
			$out['LotteryDescription'] = $p[2];
			$out['LotteryStartTime'] = $p[3];
			$out['LotteryEndTime'] = $p[4];
			
			$out['LotteryRepeatTip'] = $p[5];
			$out['LotteryStartPicture'] = $p[6];
			$out['LotteryEndPicture'] = $p[7];
			$out['LotteryEndTitle'] = $p[8];
			$out['LotteryEndDescription'] = $p[9];
				
			$out['LotteryAward1'] = $p[10];
			$out['LotteryAward1Num'] = $p[11];
			$out['LotteryAward1Probability'] = $p[12];
			
			$out['LotteryAward2'] = $p[13];
			$out['LotteryAward2Num'] = $p[14];
			$out['LotteryAward2Probability'] = $p[15];
			
			$out['LotteryAward3'] = $p[16];
			$out['LotteryAward3Num'] = $p[17];
			$out['LotteryAward3Probability'] = $p[18];
			
			$out['LotteryMax'] = $p[19];
			$out['LotteryPassword'] = $p[20];
			
			$out['LotteryDayMax'] = $p[21];
			$out['LotteryTip'] = $p[22];
            break;
		case 5:  //微调查
			//0图片封面@@@1开始说明@@@2开始说明@@@3开始时间@@@4结束时间@@@5转向链接@@@6图文描述@@@7是否匿名
			$out['ResearchPicture'] = $p[0];
			$out['StartDescription'] = $p[1];
			$out['EndDescription'] = $p[2];
			$out['StartTime'] = $p[3];
			$out['EndTime'] = $p[4];
			$out['LinkUrl'] = $p[5];
			$out['ResearchDescription'] = $p[6];
			$out['IsAnonymous'] = $p[7];
			break;
		case 6: //微会员卡
			//0名称@@@1图标@@@2背景@@@3封面图片@@@4封面消息@@@5卡号文字颜色@@@6名称文字颜色
			//@@@7使用说明@@@8积分规则说明@@@9签到奖励@@@10消费奖励
			//@@@11商家名称@@@12商家简介@@@13联系方式@@@14商家地址@@@15经度@@@16纬度@@@17商家确认消费密码
			//@@@分店列表(StoreID###StoreName###StoreTelephone###StoreAddress$$$)
			//@@@业务关联LinkName###LinkType###LinkUrl$$$
			$out['CardName'] = $p[0];
			$out['CardIcon'] = $p[1];
			$out['CardBackground'] = $p[2];
			$out['CardPicture'] = $p[3];
			$out['CardTip'] = $p[4];
			
			$out['CardNumberColor'] = $p[5];
			$out['CardNameColor'] = $p[6];
			$out['CardDescription'] = $p[7];
			$out['ScoreDescription'] = $p[8];
			$out['SignAward'] = $p[9];
			
			$out['ConsumeAward'] = $p[10];
			$out['MerchantName'] = $p[11];
			$out['MerchantDescription'] = $p[12];
			$out['MerchantTelephone'] = $p[13];
			$out['MerchantAddress'] = $p[14];
			
			$out['Longitude'] = $p[15];
			$out['Latitude'] = $p[16];
			$out['CardPassword'] = $p[17];
			
			if( !empty($p[18]) ){
				$store = (array)explode('$$$', $p[18]);
				foreach($store as $it){
					$t = (array)explode('###', $it);
					$out['Store'][]=array(
							'StoreID'=>$t[0], 
							'StoreName'=>$t[1], 
							'StoreTelephone'=>$t[2], 
							'StoreAddress'=>$t[3]
					);
				}
			}
			
			if( !empty($p[19]) ){
				$link = (array)explode('$$$', $p[19]);
				foreach($link as $it){
					$t = (array)explode('###', $it);
					$out['Link'][]=array(
							'LinkName'=>$t[0],
							'LinkType'=>$t[1],
							'LinkUrl'=>$t[2]
					);
				}
			}
            break;
		case 2:  //微调查
			//是否多选@@@开始时间@@@结束时间@@@选项@@@图片@@@结果显示
			$out['IsMultiple'] = $p[0];
			$out['StartTime'] = $p[1];
			$out['EndTime'] = $p[2];
			$out['VotePicture'] = $p[4];
			$out['ShowResult'] = $p[5];
			$temp = (array)explode('$$$', $p[3]);
			foreach ($temp as $it){
				$tt = (array)explode('###', $it);
				$out['Item'][] = array('ItemID'=>$tt[0],'ItemName'=>$tt[1]);
			}
			break;
	}
}
//解析微信应用参数结束========

/**
 * 用于解析内容中的标签，仅支持及少数标签
 * 目前支持的标签右：videoplayer
 * @param string $content
 */
function ParseTag($content){
	$start = stripos($content, '<videoplayer');
	while ( $start !== false ){
		$posEnd = stripos($content, '/>', $start);
		$length = $posEnd - $start + 2;
		$tag = substr($content, $start, $length);
		$xml = simplexml_load_string($tag);
		if($xml) {
			$attr = (array)($xml->attributes());
			$attr= array_change_key_case($attr['@attributes']);
			import("@.Common.YdVideoPlayer");
			$v = new YdVideoPlayer( $attr );
			$html = $v->render();
			$content = substr_replace($content, $html, $start, $length);
		}
		$start = stripos($content, '<videoplayer', $posEnd);
	}
	return $content;
}

//购物车模块===================================
//获取购物车数据
function get_cart(){
	import("@.Common.YdCart");
	$cart = YdCart::getInstance();
	$data = $cart->getAll();
	return $data;
}
//收货时间时间列表
function get_deliverytime(){
	$m = D('Admin/DeliveryTime');
	$data = $m->getDeliveryTime(1);
	return $data;
}
//配送方式
function get_shipping(){
	$m = D('Admin/Shipping');
	$p['IsEnable'] = 1;
	$data = $m->getShipping($p);
	return $data;
}

//收货人信息
function get_consignee(){
	$m = D('Admin/Consignee');
	$p['MemberID'] = session('MemberID');
	$p['IsEnable'] = 1;
	$data = $m->getConsignee($p);
	return $data;
}

//分销商等级
function get_distributorlevel(){
	$m = D('Admin/DistributorLevel');
	$data = $m->getDistributorLevel();
	return $data;
}

//获取现金类型
function get_cashtype(){
	$m = D('Admin/Cash');
	$data = $m->getCashType();
	return $data;
}

//支付方式 $sitetype 1:都可以，2：电脑，3：手机
function get_pay($sitetype, $isOnline=-1){
	$m = D('Admin/Pay');
	$p['IsEnable'] = 1;
	$p['SiteType'] = $sitetype;
	$p['IsOnline'] = $isOnline;
	$data = $m->getPay($p);
	$pay = array();
	$n = is_array($data) ? count($data) : 0;
	
	//微信客户端不能用支付宝，手机端模板代码里控制不能使用微信支付
	if( stripos($_SERVER['HTTP_USER_AGENT'],"MicroMessenger") ){
		$blacklist = array(1); //微信端支付黑名单，1:支付宝
		for($i = 0; $i < $n; $i++){
			if( !in_array($data[$i]['PayTypeID'], $blacklist)){
				$pay[] = $data[$i];
			}
		}
		return $pay;
	}else if( yd_is_mobile() ){ //如果是移动端
		$blacklist = array(10); //微信端支付黑名单，10:微支付
		for($i = 0; $i < $n; $i++){
			if( !in_array($data[$i]['PayTypeID'], $blacklist)){
				$pay[] = $data[$i];
			}
		}
		return $pay;
	}else{
		return $data;
	}
}

//当前会员可用优惠券列表
function get_coupon(){
	$MemberID = session("MemberID");
	if( empty($MemberID) ) return false;
	$m = D('Admin/CouponSend');
	$data = $m->getAvailableCoupon($MemberID);
	return $data;
}

//支付类别
function get_pay_type(){
	$m = D('Admin/PayType');
	$data = $m->getPayType(1);
	return $data;
}

//获取价格区间
function get_price_range($channelid=-1, $count=5){
	$m = D('Admin/Info');
	$row = $m->getMinMaxPrice($channelid);
	$min = $row['MinPrice'];
	$max = $row['MaxPrice'];
    $data = array();
	if( $count == 1){
		$data[] = array('MinPrice'=>$min, 'MaxPrice'=>$max);
		return $data;
	}
	
	$price_grade = 0.0001;
	$n = log10( $max );
	for($i = -2; $i <= $n; $i++){
		$price_grade *= 10;
	}
	
	//计算价格跨度：取整( (最大值-最小值) / 分级数 / 数量级 ) * 数量级
	$span = ceil( ($max - $min) / $count / $price_grade ) * $price_grade;
	if($span == 0) $span = $price_grade;
	
	//计算新的最小最大值
	for($i = 1; $min > $span * $i; $i ++);
	for($j = 1; $min > $span * ($i-1) + $price_grade * $j; $j++);
	$new_min = $span * ($i-1) + $price_grade * ($j - 1);
	for(; $max >= $span * $i; $i ++);
	$new_max = $span * ($i) + $price_grade * ($j - 1);
	
	$new_grade = round( ($new_max-$new_min)/$count );
	if($new_grade <= 0 ) $new_grade = 1; //必须添加，否则$new_grade=0时，会死循环
	for($start = $new_min; $start < $new_max; $start += $new_grade){
		if( $start >= $max ) break; //解决"当价格=最大值时，分级会多出来"的bug
		$data[] = array('MinPrice'=>$start, 'MaxPrice'=>$start+$new_grade);
	}
	return $data;
}

//获取订单状态
function get_order_status(){
	$data = array(
			1=>array('OrderStatusID'=>1, 'OrderStatusName'=>'新订单', 'MemberOrderStatusName'=>'等待付款'),
			2=>array('OrderStatusID'=>2, 'OrderStatusName'=>'已付款', 'MemberOrderStatusName'=>'待发货'),
			3=>array('OrderStatusID'=>3, 'OrderStatusName'=>'已发货', 'MemberOrderStatusName'=>'待收货'),
			
			4=>array('OrderStatusID'=>4, 'OrderStatusName'=>'退款', 'MemberOrderStatusName'=>'已退款'),
			5=>array('OrderStatusID'=>5, 'OrderStatusName'=>'退货', 'MemberOrderStatusName'=>'已退货'),
			
			6=>array('OrderStatusID'=>6, 'OrderStatusName'=>'结单', 'MemberOrderStatusName'=>'已完成'),
			7=>array('OrderStatusID'=>7, 'OrderStatusName'=>'作废', 'MemberOrderStatusName'=>'已作废'),
			8=>array('OrderStatusID'=>8, 'OrderStatusName'=>'已取消', 'MemberOrderStatusName'=>'已取消'),
	);
	return $data;
}

//通过js获取json数据
function JsonUrl(){
	$url = __GROUP__.'/public/getJson/l/'.LANG_SET;
	return AppendWxUrlPara($url);
}
//购物车Url
function CartUrl(){
	$url = __GROUP__.'/public/cart/l/'.LANG_SET;
	return AppendWxUrlPara($url);
}
//结算地址
function CheckoutUrl(){
	$url = __GROUP__.'/public/checkout/l/'.LANG_SET;
	return AppendWxUrlPara($url);
}

//支付页面地址，在Wap、Member分组也可能会调用到
function PayUrl($orderid=''){
	if(GROUP_NAME == 'Member'){
		if( MODULE_NAME == 'Mobile' ){ //手机会员端
			$protocol = get_current_protocal();
			$domain = strtolower(get_wap_domain());
			$url = !empty($domain) ? $protocol.$domain.__APP__ : __APP__.'/wap';
			$url .= '/public/pay?l='.LANG_SET;
		}else{
			$url = __APP__.'/public/pay?l='.LANG_SET;
		}
	}else{
		$url = __GROUP__.'/public/pay?l='.LANG_SET;
	}
	if( !empty($orderid) ) $url .="&orderid={$orderid}";
	return $url;
}

function getOrderStatusUrl($orderid=false){
	$url = __GROUP__.'/public/getOrderStatus/l/'.LANG_SET;
	if( !empty($orderid) ) $url .="/orderid/{$orderid}";
	return $url;
}

//立即支付
function PayNowUrl($orderid=false, $payid=false){
	$url = __GROUP__.'/public/payNow?l='.LANG_SET;
	if( !empty($orderid) ) $url .="&orderid={$orderid}";
	if( !empty($payid) ) $url .="&payid={$payid}";
	return $url;
}

//结算地址
function SaveCheckoutUrl(){
	$url = __GROUP__.'/public/saveCheckout/l/'.LANG_SET;
	return $url;
}

//检查线下优惠券代码
function CheckCouponCodeUrl(){
	$url = __GROUP__.'/public/checkCouponCode/l/'.LANG_SET;
	return $url;
}

function CheckPointUrl(){
	$url = __GROUP__.'/public/checkPoint/l/'.LANG_SET;
	return $url;
}

//添加到购物车Url
function AddCartUrl($InfoID=false){
	$url = __GROUP__.'/public/addCart/l/'.LANG_SET;
	if( is_numeric($InfoID)){
		$url .= '/id/'.$InfoID;
	}
	return AppendWxUrlPara($url);
}
//删除购物车商品
function DeleteCartUrl($InfoID=false){
	$url = __GROUP__.'/public/deleteCart/l/'.LANG_SET;
	if( is_numeric($InfoID)){
		$url .= '/id/'.$InfoID;
	}
	return AppendWxUrlPara($url);
}

function _quantityUrl($type=1, $InfoID=false, $n=false){
	switch($type){
		case 2: //+
			$url = __GROUP__.'/public/incQuantity/l/'.LANG_SET;
			break;
		case 3: //-
			$url = __GROUP__.'/public/decQuantity/l/'.LANG_SET;
			break;
		default: //set
			$url = __GROUP__.'/public/setQuantity/l/'.LANG_SET;
			if( is_numeric($n)){
				$url .= '/quantity/'.$n;
			}
	}
	if( is_numeric($InfoID)){
		$url .= '/id/'.$InfoID;
	}
	$url = AppendWxUrlPara($url);
	return $url;
}
//设置商品数量
function SetQuantityUrl($InfoID=false, $n=false){
	return _quantityUrl(1, $InfoID, $n);
}
//增加商品数量
function IncQuantityUrl($InfoID=false){
	return _quantityUrl(2, $InfoID);
}
//减少商品数量
function DecQuantityUrl($InfoID=false){
	return _quantityUrl(3, $InfoID);
}
//清空购物车
function ClearCartUrl(){
	$url = __GROUP__.'/public/clearCart/l/'.LANG_SET;
	return $url;
}
function ClearHistoryUrl(){
	$url = __GROUP__.'/public/clearHistory/l/'.LANG_SET;
	return $url;
}
//购物车总金额
function TotalPrice(){
	import("@.Common.YdCart");
	$cart = YdCart::getInstance();
	$total = $cart->getTotalPrice();
	return $total;
}
function TotalItemCount(){
	import("@.Common.YdCart");
	$cart = YdCart::getInstance();
	$total = $cart->getItemCount();
	return $total;
}
//生成支付二维码图片，url: weixin：//wxpay/s/An4baqw
function PayQrcode($url, $size=7){
	import("@.Common.phpqrcode");
	//容错率，也就是有被覆盖的区域还能识别，分别是 
	//L（QR_ECLEVEL_L，7%），M（QR_ECLEVEL_M，15%），Q（QR_ECLEVEL_Q，25%），H（QR_ECLEVEL_H，30%）； 
	$errorCorrectionLevel = 'H';
	$dir = RUNTIME_PATH.'qrcode/';
	if( !file_exists($dir)){
		@mkdir($dir,0755,true);
	}
	$fileName = $dir.md5($url).'.png';
	//生成二维码图片
	QRcode::png($url, $fileName, $errorCorrectionLevel, $size, 1);
	$fileName = __ROOT__.substr($fileName, 1);
	return $fileName;
}

/**
 * 生成二维码
 * @param string $content 二维码内容
 * @param int $size
 * @return string
 */
function Qrcode($content, $size=7, $savePath = false, $fileName=false, $params=array()){
    import("@.Common.phpqrcode");
    //容错率，也就是有被覆盖的区域还能识别，分别是
    //L（QR_ECLEVEL_L，7%），M（QR_ECLEVEL_M，15%），Q（QR_ECLEVEL_Q，25%），H（QR_ECLEVEL_H，30%）；
    $errorCorrectionLevel = 'H';
    $dir = empty($savePath) ? RUNTIME_PATH: $savePath;
    if( !file_exists($dir)){
        @mkdir($dir,0755,true);
    }
    if(empty($fileName)){
        $fileName = md5($content).'.png';
    }
    $fileFullName = $dir.$fileName;
    //生成二维码图片
    QRcode::png($content, $fileFullName, $errorCorrectionLevel, $size, 1);
    if(!empty($params['Base64'])){ //返回base64编码
        $base64 = '';
        if(file_exists($fileFullName)){
            $base64 = base64_encode(file_get_contents($fileFullName));
            $base64 = "data:image/png;base64,{$base64}";
        }
        return $base64;
    }else{
        return $fileFullName;
    }
}

/*
 * 微信公众号支付授权目录
 */
function WeixinPayDir(){
	$protocol = get_current_protocal();
	$domainWap = strtolower(get_wap_domain());
	if( !empty($domainWap) ){
		$url = $protocol.$domainWap.__APP__.'/public/';
	}else{
		$url = $protocol.$_SERVER['HTTP_HOST'].__APP__.'/wap/public/';
	}
	return $url;
}

/*
 * 微信公众号支付授权目录(用于手机后台的会员充值)
 */
function WeixinRechargeDir(){
	$protocol = get_current_protocal();
	$domainWap = strtolower(get_wap_domain());
	if( !empty($domainWap) ){
		$url = $protocol.$domainWap.__APP__.'/Member/Mobile/recharge/';
	}else{
		$url = $protocol.$_SERVER['HTTP_HOST'].__APP__.'/Member/Mobile/recharge/';
	}
	return $url;
}

/*
 * 微信公众号支付回调域名
 */
function WeixinPayOAuthDomain(){
	$domain = get_wap_domain();
	if( empty($domain) ){
		$domain = $_SERVER['HTTP_HOST'];
	}
	$domain = strtolower($domain);
	return $domain;
}

/**
 * 第三方登录回调地址
 * @param string $groupName 分组名称
 */
function OauthCallback($groupName='Home'){
	$url = get_current_protocal();
	if($groupName=='Home'){
		$domain = $_SERVER['HTTP_HOST'];
		$url .= $domain.__APP__;
	}else{
		$domain = strtolower(get_wap_domain());
		if( !empty($domain) ){
			$url .= $domain.__APP__;
		}else{
			$url .= $_SERVER['HTTP_HOST'].__APP__.'/wap';
		}
	}
	$url .='/public/oauth';
	return $url;
}

/**
 * 判断当前支付是否是跳转支付
 * @param int $type
 */
function IsRedirectPay($type){
	$list = array(1, 2, 3, 4, 8, 9);
	return in_array($type, $list);
}
//==========================================

/**
 * 获取七牛域名
 */
function get_qiniu_url(){
	$url = '';
	if(GROUP_NAME=='Home' || GROUP_NAME=='Wap'){
		$QiniuEnable = $GLOBALS['Config']['QiniuEnable'];
		//仅对Home和Wap分组有效
		if($QiniuEnable==1){
			$url = $GLOBALS['Config']['QiniuUrl'];
		}
	}
	return $url;
}

/**
 * 获取访问者的网址或域名(包含端口号)
 */
function get_current_url($hasProtocal=true){
	$url = '';
	if($hasProtocal){
		$url = get_current_protocal();
	}
	//当非80端口时：_SERVER["HTTP_HOST"] 会输出端口号，例如：xx.net:8080
	$url .= $_SERVER['HTTP_HOST'];
	//$port = $_SERVER["SERVER_PORT"];
	//if( !empty($port) && $port != 80 ){
	//	$url .= ':'.$port;
	//}
	return $url;
}

/**
 * 推送信息给百度
 * @param int $InfoID
 */
function baidu_push_info($InfoID){
	$result = "";
	if( isset($_POST['InfoTime']) && empty($_POST['LinkUrl']) ){
		//定时发布的文章不提交到百度
		$diff = time() - strtotime($_POST['InfoTime']);
		if( $_POST['IsEnable']==1 &&  $_POST['IsCheck']==1 && $diff>=0){
			$result = baidu_push_content($InfoID, 1);
		}
	}
	return $result;
}

/**
 * 推送信息给百度
 * @param int $InfoID
 */
function baidu_push_channel($ChannelID){
	$result = "";
	if( empty($_POST['LinkUrl']) && $_POST['IsEnable']==1 ){
		$result = baidu_push_content($ChannelID, 2);
	}
	return $result;
}

/**
 * 百度推送信息或文章
 * @param int $id
 * @param int $type
 */
function baidu_push_content($id, $type=1){
	$enable = &$GLOBALS['Config']['BaiduPushEnable'];
	$msg = "";
	if(!empty($enable) && $id>0){
		$domain = get_current_url();
		if($type==1){ //信息
			$url = $domain.InfoUrl($id);
		}else{ //频道
			$url = $domain.ChannelUrl($id);
		}
		$result = baidu_push($url);
		if( isset($result['error']) ){
			$msg = "<span style='color:red;'>百度推送失败，{$result['message']}<span>";
		}else{
			$msg = "<span style='color:green;'>百度推送成功！<span>";
		}
	}
	return $msg;
}

/**
 * 将网址推送到百度
 * @param string/array $url 支持单个网址推送、多个网址推送（已回车隔开）、数组
 * 没有启用返回false
 */
function baidu_push($url){
	/*
	 * 推送成功推送失败
		字段	                是否必选	参数类型	说明
		success	            是	             int	成功推送的url条数
		remain	            是          	 int	当天剩余的可推送url条数
		not_same_site	否	             array	由于不是本站url而未处理的url列表
		not_valid	        否	             array	不合法的url列表
		
		推送失败
		字段	    是否必传	类型	    说明
		error	        是	        int	    错误码，与状态码相同
		message	是	        string	错误描述
	 */
	//数组分离
	if( is_string($url)){
		$url = str_replace("\r\n", "\n", $url);
		$url = str_replace("\r", "\n", $url);
		$url = explode("\n", trim($url));
	}
	//去重
	$url = array_unique($url);
	//删除无效网址
	$data = array();
	foreach ($url as $v){
		if( 'http' == strtolower(substr($v, 0, 4)) ){
			$data[] = $v;
		}
	}
	$token = &$GLOBALS['Config']['BaiduPushToken'];
	if(empty($token)) {
		return array('error'=>'901', 'message'=>'token为空');
	}
	$domain = get_current_url(false); //域名先已经在站长平台进行过登记
	//$domain = "www.csyoudian.com";
	$api = "http://data.zz.baidu.com/urls?site={$domain}&token={$token}";
	//if(!is_array($url)) $url = array($url); //不是数组转换为数组
	$data = implode("\n", $data);  //2019-01-03必须添加此语句才正常，之前没有
	$result = yd_curl_post($api, $data);
	if(!empty($result)){
		$result = json_decode($result, true);
	}else{
		$result = array('error'=>'902', 'message'=>'curl_post返回空');
	}
	return $result;
}

//=============APP 相关函数 开始===================
/**
 * 添加图片资源域名前缀
 * @param array/string $data 仅支持二维数组和字符串
 * @param string $field 仅支持单个字段，仅对$data为数组有效
 * @return 返回字符串或数组
 */
function AddResDomain(&$data, $field='', $domain=false){
    $domain = get_current_url();
    if(is_array($data)){
        foreach ($data as $k=>$v){
            $data[$k][$field] = app_to_fullurl( $v[$field],$domain );
        }
    }elseif(is_string($data)){
        $result = app_to_fullurl($data, $domain);
        return $result;
    }
}

/*
 * 将内容中的资源地址（图片、视频、音频），替换为绝对路径
 */
function AddContentResDomain($content){
    //场景1：<img src="/upload/1.jpg" />
    $ResUrl = get_current_url();
    $content = str_ireplace('src="/', "src=\"{$ResUrl}/", $content);
    $content = str_ireplace('url("/', "url(\"{$ResUrl}/", $content);
    return $content;
}

/**
 * 将url转化为全路径
 * @param string $url
 * @param string $domain
 */
function app_to_fullurl($url, $domain=false){
	//表示为相对地址需要转换，为空时不需要转换
	if( !empty($url) && substr($url, 0, 1) == '/' ){ 
		if(empty($domain)) $domain = get_current_url();
		$url = $domain.$url;
	}
	return $url;
}

/**
 * 移除域名前缀 http://www.x.com/Upload/1.jgp 转化为：/Upload/1.jpg
 * @param string $url
 * @param string $domain
 * @return string
 */
function app_remove_domain($url, $domain=false){
	if( !empty($url) && strtolower(substr($url, 0, 4))  == 'http' ){
		if(empty($domain)) $domain = get_current_url();
		$url = str_ireplace($domain, '', $url);
		$url = trim($url);
	}
	return $url;
}

/**
 * 生成App播放器代码
 * @param string $url 本地路径或优酷或土豆的路径
 * @return string
 */
function app_video_player($url, $domain=false){
	if( substr($url, 0, 1) == '/' ){ //表示为相对地址，一定是本地视频文件
		if(empty($domain)) $domain = get_current_url();
		$url = $domain.$url;
		$player = "<video class='app_video_player' src='{$url}' controls='controls' autoplay='autoplay'></video>";
	}else{
		//第三方播放平台
		$player = "<iframe class='app_video_player' src='{$url}'  allowtransparency='true' allowfullscreen='true' ";
		$player .= "allowfullscreenInteractive='true' scrolling='no' border='0' frameborder='0'></iframe>";
	}
	return $player;
}

/**
 * 获取当前的协议
 */
function get_current_protocal(){
	$protocal = 'http://';
	$b = yd_is_https();
	if($b){
		$protocal = 'https://';
	}
	return $protocal;
}

/**
 * 根据邀请码，获取邀请人ID
 */
function GetInviterID(){
	$InviterID = 0;
	//小程序、APP不支持session则直接通过GET/POST传参
	if( !empty($_REQUEST['ic']) ){ 
		$code = trim($_REQUEST['ic']);
	}elseif( session("?ic") ){
		$code = trim(session("ic"));
	}else{
		$code = '';
	}
	$code = YdInput::checkLetterNumber($code);
	if(!empty($code)){
		$m = D('Admin/Member');
		$code = addslashes(stripslashes($code));
		$MemberID = $m->where("InviteCode='{$code}'")->getField('MemberID');
		if($MemberID>0){
			$InviterID = $MemberID;
		}
	}
	return $InviterID;
}

//获取水印字体文件信息
function GetWaterFonts(){
	$list['simkai.ttf']="楷体";
	$data = array();
	foreach (glob("./Public/font/*.*") as $filename) {
		$FontName = basename($filename);
		$FontAlias = isset($list[$FontName]) ? $list[$FontName] : '';
		$data[]=array(
			'FontFile'=>$filename,
			'FontName'=>$FontName,
			'FontAlias'=>$FontAlias
		);
	}
	return $data;
}
//=============APP 相关函数 结束===================

//=============插件相关函数 开始===================
/**
 * 插件命名规范：
 * （1）插件公共函数以plugin_开头，如：plugin_distribute_enable
 * （2）三级分销：相关函数以distribute_开头，如：distribute_rebate分销返利
 * （3）对模板调用的函数，采用大驼峰命名规则，如：TotalIncome
 */
/**
 * 是否启用了分销插件
 */
function plugin_distribute_enable(){
	$DistributeEnable = $GLOBALS['Config']['DistributeEnable'];
	return $DistributeEnable;
}

/**
 * 获取当前用户总收益
 * @return number
 */
function TotalIncome(){
	$CashType = 5; //表示分佣金额
	$MemberID = session('MemberID');
	$total= 0;
	if($MemberID>0){
		$m = D('Admin/Cash');
		$total = $m->getQuantity($CashType, $MemberID);
		$total = round($total, 2);
	}
	return $total;
}

/**
 * 生成6位数数字和字符的邀请码
 * @param int $len 邀请码的长度
 */
function MakeInviteCode($len=6){
	$code = strtolower(rand_string($len, 5));
	return $code;
}


function PcInviteUrl($MemberID=false){
	return InviteUrl($MemberID,1);
}

function WapInviteUrl($MemberID=false){
	return InviteUrl($MemberID,2);
}

/**
 * 我的推广链接
 * @param int $MemberID
 * @param int $type 1:pc,2:wap
 * @return string
 */
function InviteUrl($MemberID=false, $type=1){
	$url = '';
	if(empty($MemberID)){
		$MemberID = session('MemberID');
	}
	if(empty($MemberID)) {
		return $url;
	}
	
	$m = D('Admin/Member');
	$where['MemberID'] = intval($MemberID);
	$InviteCode = $m->where($where)->getField('InviteCode');
	if(!empty($InviteCode)){
		if($type==2){
			$url = get_wx_url();
		}else{
			$url = get_web_url();
			if( 2 != C('URL_MODEL') ){
				$url .= '/index.php';
			}
		}
		$url .= '/public/reg/l/'.LANG_SET."?ic={$InviteCode}";
	}
	return $url;
}

function InviteQrcode($MemberID=false){
	//L（QR_ECLEVEL_L，7%），M（QR_ECLEVEL_M，15%），Q（QR_ECLEVEL_Q，25%），H（QR_ECLEVEL_H，30%）；
	if(empty($MemberID)){
		$MemberID = session('MemberID');
	}
    $MemberID = intval($MemberID);
	$dir = APP_DATA_PATH.'ic/';
	$fileName = $dir.$MemberID.'.png';
	if(!file_exists($fileName)){
		if( !file_exists($dir)){
			@mkdir($dir, 0755, true);
		}
		//生成二维码图片
		$url = WapInviteUrl($MemberID);
		import("@.Common.phpqrcode");
		QRcode::png($url, $fileName, 'H', 7, 1);
	}
	$fileName = __ROOT__.substr($fileName, 1);
	return $fileName;
}

/**
 * 判断用户是否开启了分销功能
 * @param int $MemberID
 */
function can_distribute($MemberID){
	$b = false;
	$MemberID = intval($MemberID);
	$enable = plugin_distribute_enable();
	if($enable==1){
		//成为分销商的条件 1:无条件，2：购买商品，不管哪种情况IsDistributor一定等于1
		$m = D('Admin/Member');
		$IsDistributor = $m->where("MemberID=$MemberID")->getField('IsDistributor');
		$b = ($IsDistributor==1) ? true : false;
	}
	return $b;
}

/**
 * 购买商品满指定数量自动成为分销商
 */
function auto_set_distributor($OrderID){
	$Requirement = $GLOBALS['Config']['DistributeRequirement'];
	//2：购买商品自动成为分销商
	if($Requirement == 2){
        $OrderID = intval($OrderID);
		$mo = D('Admin/Order');
		$MemberID = $mo->where("OrderStatus=6 and OrderID={$OrderID}")->getField('MemberID');
		
		$mm = D('Admin/Member');
		$member = $mm->where("MemberID=$MemberID")->field('IsDistributor,IsCheck,IsLock,InviteCode')->find();
		$IsDistributor = $member['IsDistributor'];
		$IsCheck = $member['IsCheck'];
		$IsLock = $member['IsLock'];
		$InviteCode = $member['InviteCode'];
		if($IsCheck==1 && $IsLock==0 && $IsDistributor==0){ //只有不是分销商的情况才自动设置
			$MinMoney = intval($GLOBALS['Config']['MinMoney']);
			$TotalOrderPrice = $mo->getTotalOrderPrice($MemberID);
			if($TotalOrderPrice >= $MinMoney){
				$md = D('Admin/DistributorLevel');
				$DistributorLevelID = $md->getLowestDistributorLevelID();
				$update['IsDistributor'] = 1;
				$update['DistributorLevelID'] = $DistributorLevelID;
				$update['DistributorTime'] = date('Y-m-d H:i:s');
				if(empty($InviteCode)){
					$update['InviteCode'] = MakeInviteCode();
				}
				$b = $mm->where("MemberID=$MemberID")->setField($update);
			}
		}
	}
}

/**
 * 分销返利
 * @param int $OrderID
 */
function distribute_rebate($OrderID){
	$m = D('Admin/Order');
    $OrderID = intval($OrderID);
	$order = $m->where("OrderID=$OrderID")->field('MemberID,OrderNumber')->find();
	$MemberID = $order['MemberID'];
	$OrderNumber = $order['OrderNumber'];
	//判断当前用户是否有分销功能
	//$canDistribute = can_distribute($MemberID);
	//if(!$canDistribute) return false;
	
	//==计算总分成佣金==
	$Commission = 0; //总分成佣金
	$DistributeMode = $GLOBALS['Config']['DistributeMode'];
	if($DistributeMode==1){ //1:按商品设置的分成金额      
		$mi = D('Admin/Info');
		$Commission = $mi->getOrderCommission($OrderID);
	}else{ //2:按订单设置的分成比例
		//获取当前订单消费总额
		$TotalOrderPrice = $m->getTotalOrderPrice($MemberID, $OrderID);
		$OrderRate = (double)$GLOBALS['Config']['OrderRate']/100.0;
		$Commission = $TotalOrderPrice * $OrderRate;
	}
	if( $Commission<=0 ) return false;
	
	//==开始返利==
	$CashTime = date('Y-m-d H:i:s');
	$cash = array(); //返利数据
	//1.自己返佣
	$memberToUpgrade = array(); //记录可能会升级的分销商
	$BuyerRate = (double)$GLOBALS['Config']['BuyerRate']/100.0;
	if($BuyerRate>0){
		$money = round($Commission * $BuyerRate, 2);
		$cash[] = array(
			'MemberID'=>$MemberID,
			'CashQuantity'=>$money,
			'CashType'=>5, //5:表示分销佣金
			'CashStatus'=>1,
			'CashTime'=>$CashTime,
			'OrderID'=>$OrderID, //分佣时记录对应的订单ID
			'CashRemark'=>"购买者自返佣，订单号：{$OrderNumber}",
		);
		$memberToUpgrade[] = $MemberID;
	}
	//2.下线返佣
	$md = D('Admin/DistributorLevel');
	$mm = D('Admin/Member');
	$upline = $mm->getUpline($MemberID, $GLOBALS['Config']['ReturnGrade']);
	foreach ($upline as $level=>$v){
		$rate = $md->getCommissionRate($v['DistributorLevelID'], $level);
		//参与返利的上线一定正常状态，否则不返利
		if($v['IsCheck']==1 && $v['IsLock']==0 && $rate>0){
			$money = $Commission * $rate;
			$memberToUpgrade[] = $v['MemberID'];
			$cash[] = array(
				'MemberID'=>$v['MemberID'],
				'CashQuantity'=>$money,
				'CashType'=>5,  //5:表示分销佣金
				'CashStatus'=>1,
				'CashTime'=>$CashTime,
				'OrderID'=>$OrderID, //分佣时记录对应的订单ID
				'CashRemark'=>"订单号：{$OrderNumber}",
			);
			//模板变量
			$var['CashQuantity'] = $money;
			$var['OrderNumber'] = $OrderNumber;
			$var['CashTime'] =  $CashTime;
			$var['MemberName'] =  $v['MemberName'];
			$var['MemberEmail'] =  $v['MemberEmail'];
			$var['MemberMobile'] =  $v['MemberMobile'];
			distribute_notify($var);
		}
	}
	//3. 批量插入数据
	if(count($cash)>0){
		$mc = D('Admin/Cash');
		$result = $mc->addAll($cash); //批量插入返利数据
		if($result){
			//4.相关会员，升级分销商等级
			$md->upgradeDistributorLevel($memberToUpgrade);
		}
	}
}

/**
 * 分销返利通知
 * @param array $data 相关数据
 */
function distribute_notify($data){
	//会员姓名、返佣金额
	$search = array('{$Name}',  '{$Money}', '{$Time}', '{$OrderNumber}');
	$replace = array($data['MemberName'], $data['CashQuantity'], $data['CashTime'], $data['OrderNumber']);
	//邮件通知
	$IsEmailNotify = $GLOBALS['Config']['DistributeEmail'];
	if($IsEmailNotify==1 && !empty($data['MemberEmail'])){
		$emailTitle = str_ireplace($search, $replace, $GLOBALS['Config']['DistributeEmailTitle']);
		$emailBody = str_ireplace($search, $replace, $GLOBALS['Config']['DistributeEmailBody']);
		$emailBody = nl2br($emailBody);
		$b = sendwebmail($data['MemberEmail'], $emailTitle, $emailBody);
	}
	//短信通知
	$IsSmsNotify = $GLOBALS['Config']['DistributeSms'];
	if($IsSmsNotify==1 && !empty($data['MemberMobile'])){
		$content = str_ireplace($search, $replace, $GLOBALS['Config']['DistributeSmsBody']);
		$b = send_sms($data['MemberMobile'], $content);
	}
}
//=============插件相关函数 结束===================

/**
 * 获取频道模板模板内容
 */
function getEmptyTemplateContent($type, $modelID=30){
    $map = array(30=>'article', 31=>'picture', 32=>'single', 34=>'video', 35=>'download', 36=>'product', 37=>'feedback');
    $name = isset($map[$modelID]) ? $map[$modelID] : 'article';
    $class= "body_{$name}";
    if($type == 2){
        $class .= ' infodetail';
    }
    $content = "<!DOCTYPE html>
<html>
<head>
    <title>{\$ChannelName}|{\$Title}-{\$WebName}</title>
    <include file=\"Public:meta\" />
</head>
<body  class=\"{$class}\">
    <include file=\"Public:header\" />

    <include file=\"Public:footer\" />
</body>
</html>";
    return $content;
}

function AllPageUrl($LanguageID=1){
    $result = array();
    $domain = get_web_url(true, false);
    $language = array(1=>'cn', 2=>'en');
    $m = D('Admin/Channel');
    $data = $m->getAllChannel($LanguageID);
    foreach ($data as $k=>$v){
        if(33 == $v['ChannelModelID']  && trim($v['LinkUrl']) != ''){ //转向链接
            // 不生成转向链接 $loc = $v['LinkUrl'];
        }else{
            $url = $domain.ChannelUrl( $v['ChannelID'], $v['Html'], '', $language[$v['LanguageID']]);
            $result[] = array('Url'=>$url, 'ID'=>$v['ChannelID'], 'Type'=>1);
        }
    }

    $m = D('Admin/Info');
    $data = $m->getAllInfo($LanguageID);
    foreach ($data as $k=>$v){
        if( $v['LinkUrl'] == '' ){
            $url = $domain.InfoUrl( $v['InfoID'], $v['Html'], $v['LinkUrl'],  $language[$v['LanguageID']], $v['ChannelID']);
            $result[] = array('Url'=>$url, 'ID'=>$v['InfoID'], 'Type'=>2);
        }
    }
    return $result;
}

function get_labelcheckbox($modelid, $checked='', $id='lid', $name='lid[]', $style='', $separator='&nbsp;&nbsp;'){
    $checked = explode(',', $checked); //转化为数组
    $name = !empty($name) ? "name='$name'" : '';
    $id = !empty($id) ? "id='$id'" : '';
    $style = !empty($style) ? "style='$style'" : '';

    $m= D('Admin/Label');
    $data = $m->getLabel($modelid,-1,1);
    $parseStr   = '';
    foreach($data as $k=>$v) {
        if($checked == $v['LabelID']  || in_array($v['LabelID'], $checked) ) {
            $chk = "checked='checked'";
        }else{
            $chk = '';
        }
        $parseStr .= "<label><input type='checkbox' {$chk} {$id} {$name} {$style} value='{$v['LabelID']}'>{$v['LabelName']}{$separator}</label>";
    }
    return $parseStr;
}

/**
 * 解码客户端的encodeSpecialChars编码结果
 */
function decodeSpecialChars(&$content){
    $content = str_ireplace('%2B', '+', $content);
}

function get_table_sql($tables, $exportData=true){
    $m = D('Admin/Channel');
    $sql=""; //加上上句，直接在navicate执行导出的sql会报语法错误
    //导出表结构
    $filter = array('USING BTREE','ROW_FORMAT=DYNAMIC','ENGINE=InnoDB ','ENGINE=MyISAM ');
    foreach($tables as $table) {
        $sql .= "\nDROP TABLE IF EXISTS `$table`;\n";
        $info = $m->query("SHOW CREATE TABLE  $table");
        $sql .= str_ireplace($filter,'',$info[0]['Create Table']).";\n";
    }
    //导出数据
    if($exportData){
        $sql .= "\n\n";
        foreach($tables as $table){
            $row = 0;
            $result = $m->query("SELECT * FROM $table ");
            if(empty($result)) continue;
            foreach($result as $key => $val){
                $sql .= "INSERT INTO `$table` VALUES\n";
                //所有字段
                foreach($val as $k => $field){
                    if(is_string($field)){
                        $val[$k] = '\'' . addslashes($field) . '\'';
                    }elseif(empty($field)){
                        $val[$k] = 'NULL';
                    }
                }
                $sqlValues = "(" . implode(',', $val) . ")";
                $sql .= $sqlValues;
                $row++;
                $sql .= ";\n\n";
            } //foreach
        }
    }
    return $sql;
}

/**
 * 判断插件是否安装
 * @param $PluginID
 */
function appIsInstall($PluginID){
    static $_cache = array();
    if(isset($_cache[$PluginID])){
        return $_cache[$PluginID];
    }
    $m = D('Admin/Menu');
    $where["MenuID"] = intval($PluginID);
    $IsEnable = $m->where($where)->getField('IsEnable');
    $result = ($IsEnable == 1) ? true : false;
    $_cache[$PluginID] = $result;
    return $result;
}

/**
 * CkEditor上传地址
 */
function CkEditorUploadUrl(){
    //2中场合使用 1）图片-》上传 ；2）从word粘贴图片自动上传
    //后面的参数必须使用?，否则ckeditor会不正确
    $url  = __GROUP__.'/public/upload?UploadSource=1';  //UploadSource表示上传来源，1：CKEDTOR
    if(APP_DEBUG){
        $url .= '&XDEBUG_SESSION_START=ECLIPSE_DBGP';
    }
    return $url;
}

/**
 * UEditor上传地址
 */
function UEditorUploadUrl(){
    //2中场合使用 1）图片-》上传 ；2）从word粘贴图片自动上传
    $url  = __GROUP__.'/public/upload/UploadSource/2';  //UploadSource表示上传来源，2：UEDTOR
    if(APP_DEBUG){
        $url .= '/XDEBUG_SESSION_START/ECLIPSE_DBGP';
    }
    return $url;
}

/**
 * 简约CK编辑器
 */
function CkEditor($id, $height=200){
    $WebPublic = __ROOT__ . '/Public/';
    $filebrowserImageUploadUrl = '';
    if(!empty($UploadUrl)){
        $filebrowserImageUploadUrl = "'filebrowserImageUploadUrl':'{$UploadUrl}',";
    }
    $html = "
    <script type='text/javascript'>window.CKEDITOR_BASEPATH='{$WebPublic}ckeditor/';</script>
    <script type='text/javascript' src='{$WebPublic}ckeditor/ckeditor.js'></script>
    <script type='text/javascript'>
    CKEDITOR.replace('{$id}', {
        'uiColor': '#f0f0f0', 'width':'100%', 
        'height':'{$height}px', 
        {$filebrowserImageUploadUrl}
    });
    </script>";
    return $html;
}

/**
 * 获取站点条件
 */
function get_site_where(){
    if (!defined('SITE_ID') || (int)SITE_ID == 0) return '';
    $id = (int)SITE_ID;
    $where = " AND ( SiteID = '' OR  FIND_IN_SET('{$id}', SiteID) )";
    return $where;
}

/**
 * 安全目录权限检测
 * $status：状态输出参数，表示目录的状态是否符合要求
 */
function DirDetection(&$status=0){
    /*
    $config = $this->getWapTpl();
    $wapConfigFile = $config['pWapConfig'];
    $config = $this->getHomeTpl();
    $homeConfigFile = $config['pHomeConfig'];
    */
    //flag=0表示不检测可执行权限
    $list = array(
        array('Name'=>'网站根目录',        'Dir'=>'./',                 'Flag'=>1, 'SuggestFlag'=>'1', 'Suggest'=>'只读、<b style="color:green;">开启</b>脚本执行权限', 'Remark'=>'网站放在wwwroot目录下，将wwwroot设为只读'),

        array('Name'=>'数据目录',  'Dir'=>APP_DATA_PATH,    'Flag'=>1,'SuggestFlag'=>'2', 'Suggest'=>"读写、关闭脚本执行权限",  'Remark'=>'数据目录，存放数据库备份sql、全站备份zip、静态缓存html、系统缓存runtime等'),
        array('Name'=>'上传目录',           'Dir'=>'./Upload/',     'Flag'=>1, 'SuggestFlag'=>'2', 'Suggest'=>"读写、关闭脚本执行权限",  'Remark'=>'上传的文件都存在此目录！'),
        array('Name'=>'公共目录',     'Dir'=>'./Public/',  'Flag'=>1, 'SuggestFlag'=>'1', 'Suggest'=>"只读、关闭脚本执行权限",  'Remark'=>'公共静态文件、JS公共库'),
        array('Name'=>'安装目录',     'Dir'=>'./Install/',  'Flag'=>1, 'SuggestFlag'=>'1', 'Suggest'=>"只读、关闭脚本执行权限",  'Remark'=>'系统安装文件，安装时需要开启脚本执行权限'),

        array('Name'=>'应用目录',     'Dir'=>'./App/',  'Flag'=>1, 'SuggestFlag'=>'1', 'Suggest'=>"只读、关闭脚本执行权限",  'Remark'=>''),
        //array('Name'=>'系统配置目录',     'Dir'=>CONF_PATH,  'Flag'=>1, 'SuggestFlag'=>'1', 'Suggest'=>"只读",  'Remark'=>'系统配置文件，如：数据库配置、伪静态配置等'),
        array('Name'=>'电脑网站模板目录',  'Dir'=>TMPL_PATH.'Home/',  'Flag'=>1, 'SuggestFlag'=>'1', 'Suggest'=>'只读',  'Remark'=>'模板装修时，才开启写入权限，建议设置为只读'),
        array('Name'=>'手机网站模板目录',  'Dir'=>TMPL_PATH.'Wap/',  'Flag'=>1, 'SuggestFlag'=>'1', 'Suggest'=>'只读',  'Remark'=>'模板装修时，才开启写入权限，建议设置为只读'),

        //array('Name'=>'电脑网站模板配置',  'Dir'=>$homeConfigFile,  'Flag'=>0, 'Suggest'=>'可读写',  'Remark'=>'如要在后台使用【模板管理】-【电脑网站管理】-【模板设置】，请开启写入权限'),
        //array('Name'=>'手机网站模板配置',  'Dir'=>$wapConfigFile,  'Flag'=>0, 'Suggest'=>'可读写',  'Remark'=>'如要在后台使用【模板管理】-【手机网站管理】-【模板设置】，请开启写入权限'),
    );
    $status = 1;  //全局状态
    $n = count($list);
    for($i=0; $i < $n; $i++){
        if( file_exists($list[$i]['Dir']) ){
            $isWritable = @yd_is_writable($list[$i]['Dir']) ? 1 : 0;
            $list[$i]['IsWritable'] = $isWritable;
            if( $list[$i]['Flag'] == 1 ){
                $list[$i]['IsExecutable'] = @yd_is_executable( $list[$i]['Dir'] ) ? 1: 0;
            }

            if($list[$i]['SuggestFlag'] == 1){ //1表示只读，
                $DirStatus = ($isWritable==0) ? 1 : 0;
            }else{ //2：可读写，并关闭执行权限
                if($isWritable==1){
                    $DirStatus = !$list[$i]['IsExecutable'] ? 1 : 0;
                }else{
                    $DirStatus = 1;  //如果目录是只读的，状态一定通过
                }
            }
            if($DirStatus==0) $status = 0; //只要有一个等于0，就认为没有通过
            $list[$i]['DirStatus'] = $DirStatus;
            $list[$i]['FileExist'] = 1;
        }else{
            $list[$i]['DirStatus'] = '';
            $list[$i]['FileExist'] = 0;
        }
    }
    return $list;
}

function afterLogout(){
    @unlink( APP_DATA_PATH.'decoration.lock' );
    session("AdminID", null);
    session("AdminName", null);
    session("AdminGroupID", null);
    session("AdminGroupName", null);
    session("AdminMemberID", null);
    session('IsSafeAnswer', null);
}

/**
 * 返回所有语言
 */
function AllLanguage(){
    $data = array();
    //$data[] = array('name'=>'自定义', 'mark'=>'');  , 'lang'=>''

    //lang表示对应的百度翻译目标语言
    $data[] = array('name'=>'德语', 'mark'=>'de',       'lang'=>'de');
    $data[] = array('name'=>'日语', 'mark'=>'ja',        'lang'=>'jp');
    $data[] = array('name'=>'俄语', 'mark'=>'ru',        'lang'=>'ru');
    $data[] = array('name'=>'法语', 'mark'=>'fr',         'lang'=>'fra');
    $data[] = array('name'=>'阿拉伯语', 'mark'=>'ar', 'lang'=>'ara');

    $data[] = array('name'=>'西班牙语', 'mark'=>'es',    'lang'=>'spa');
    $data[] = array('name'=>'葡萄牙语', 'mark'=>'pt' ,   'lang'=>'pt');
    $data[] = array('name'=>'意大利语', 'mark'=>'it' ,    'lang'=>'it');
    $data[] = array('name'=>'泰语', 'mark'=>'th' , 'lang'=>'th');
    $data[] = array('name'=>'韩语', 'mark'=>'ko', 'lang'=>'kor');

    $data[] = array('name'=>'阿塞拜疆语', 'mark'=>'az',  'lang'=>'aze');
    $data[] = array('name'=>'爱尔兰语', 'mark'=>'ga',     'lang'=>'gle');
    $data[] = array('name'=>'爱沙尼亚语', 'mark'=>'et',  'lang'=>'est');
    $data[] = array('name'=>'白俄罗斯语', 'mark'=>'be', 'lang'=>'bel');
    $data[] = array('name'=>'保加利亚语', 'mark'=>'bg', 'lang'=>'bul');
    $data[] = array('name'=>'冰岛语', 'mark'=>'is',   'lang'=>'ice');
    $data[] = array('name'=>'波兰语', 'mark'=>'pl' , 'lang'=>'pl');

    //布尔语、海地克里奥尔语、在百度翻译中不存在
    //$data[] = array('name'=>'布尔语', 'mark'=>'af', 'lang'=>'');
    $data[] = array('name'=>'丹麦语', 'mark'=>'da',   'lang'=>'dan');
    $data[] = array('name'=>'菲律宾语', 'mark'=>'tl', 'lang'=>'fil');
    $data[] = array('name'=>'芬兰语', 'mark'=>'fi',    'lang'=>'fin');

    //$data[] = array('name'=>'海地克里奥尔语', 'mark'=>'ht', 'lang'=>'');
    $data[] = array('name'=>'荷兰语', 'mark'=>'nl' ,           'lang'=>'nl');
    $data[] = array('name'=>'加泰罗尼亚语', 'mark'=>'ca', 'lang'=>'cat');
    $data[] = array('name'=>'捷克语', 'mark'=>'cs' ,           'lang'=>'cs');
    $data[] = array('name'=>'克罗地亚语', 'mark'=>'hr',     'lang'=>'hrv');

    $data[] = array('name'=>'老挝语', 'mark'=>'lao',  'lang'=>'lao');
    $data[] = array('name'=>'拉丁语', 'mark'=>'la',        'lang'=>'lat');
    $data[] = array('name'=>'拉脱维亚语', 'mark'=>'lv', 'lang'=>'lav');
    $data[] = array('name'=>'立陶宛语', 'mark'=>'lt',     'lang'=>'lit');
    $data[] = array('name'=>'罗马尼亚语', 'mark'=>'ro', 'lang'=>'rom');
    $data[] = array('name'=>'马耳他语', 'mark'=>'mt',   'lang'=>'mlt');
    $data[] = array('name'=>'马来语', 'mark'=>'ms',      'lang'=>'may');
    $data[] = array('name'=>'马其顿语', 'mark'=>'mk',  'lang'=>'mac');
    $data[] = array('name'=>'挪威语', 'mark'=>'no',      'lang'=>'nor');

    $data[] = array('name'=>'瑞典语', 'mark'=>'sv',         'lang'=>'swe');
    $data[] = array('name'=>'塞尔维亚语', 'mark'=>'sr',   'lang'=>'srp');
    $data[] = array('name'=>'斯洛伐克语', 'mark'=>'sk' , 'lang'=>'sk');
    $data[] = array('name'=>'斯洛文尼亚语', 'mark'=>'sl', 'lang'=>'slo');
    $data[] = array('name'=>'斯瓦希里语', 'mark'=>'sw',  'lang'=>'swa');

    $data[] = array('name'=>'土耳其语', 'mark'=>'tr' , 'lang'=>'tr');
    $data[] = array('name'=>'威尔士语', 'mark'=>'cy', 'lang'=>'wel');
    $data[] = array('name'=>'乌克兰语', 'mark'=>'uk', 'lang'=>'ukr');
    $data[] = array('name'=>'希伯来语', 'mark'=>'iw', 'lang'=>'heb');
    $data[] = array('name'=>'希腊语', 'mark'=>'el' ,    'lang'=>'el');
    $data[] = array('name'=>'匈牙利语', 'mark'=>'hu' , 'lang'=>'hu');
    $data[] = array('name'=>'印尼语', 'mark'=>'id' , 'lang'=>'id');
    $data[] = array('name'=>'越南语', 'mark'=>'vi',  'lang'=>'vie');



    $data[] = array('name'=>'英语', 'mark'=>'en' ,       'lang'=>'en');
    $data[] = array('name'=>'繁体中文', 'mark'=>'zh', 'lang'=>'cht');
    $data[] = array('name'=>'简体中文', 'mark'=>'cn', 'lang'=>'zh');
    return $data;
}

/**
 * 当前语言标识转百度翻译的语言标识
 */
function TranslateLang($mark){
    $to = '';
    $data = AllLanguage();
    foreach($data as $k=>$v){
        if($v['mark']==$mark){
            $to = $v['lang'];
            break;
        }
    }
    return $to;
}

/**
 * 首页频道数据
 */
function IndexChannelData(){
    static $_cache = false;
    if(empty($_cache)){
        $LanguageID = get_language_id();
        $m = D('Admin/Channel');
        $where = "Html='index' AND LanguageID={$LanguageID}";
        $_cache = $m->where($where)->find();
    }
    return $_cache;
}

/**
 *判断当前频道是否是首页频道ID
 */
function IsIndexChannel($ChannelID){
    $data = IndexChannelData();
    if($ChannelID == $data['ChannelID']){
        return true;
    }else{
        return false;
    }
}

function getChannelSelect($params){
    $menuowner = $params['menuowner'];
    $groupid = $params['groupid'];
    $LanguageID = $params['LanguageID'];
    if(empty($LanguageID)) $LanguageID = false;

    $firstvalue = $params['firstvalue'];
    $firsttext = $params['firsttext'];
    $selectvalue = $params['selectvalue'];

    $name = $params['name'];
    $id = $params['id'];
    $style = $params['style'];

    $c = D('Admin/Channel');
    //$ChannelInfo = $c->getChannel($ChannelID, $hasSingleChannel, $hasLinkChannel);

    //仅显示有权限的频道=============================================
    $ChannelInfo = $c->getChannelPurview($menuowner, $groupid, '&nbsp;&nbsp;&nbsp;&nbsp;├─ ', $LanguageID);
    //$n = count($ChannelInfo);
    //for($i = 0; $i < $n; $i++){
    //	$ChannelInfo[$i]['HasChild'] = $c->hasChildChannel($ChannelInfo[$i]['ChannelID']);
    //}
    //=========================================================
    $id = !empty($id) ? "id='$id'" : '';
    $name  = !empty($name ) ? "name='$name'" : '';
    $style = !empty($style) ? "style='$style'" : '';
    $onchange = !empty($onchange) ? "onchange=$onchange" : '';

    $parseStr = "<select $id $name $style $onchange>";
    if(  !empty($firsttext) ){
        $parseStr .= "<option value='$firstvalue'>$firsttext</option>";
    }
    foreach($ChannelInfo as $k=>$v){
        $hasChild = $v['HasChild'];
        $cmid = $v['ChannelModelID'];
        $cid = $v['ChannelID'];
        if($cid == 1 || $cid == 2 ) continue;
        $cname = $v['ChannelName'];
        $sel = ($selectvalue == $cid ) ? "Selected='Selected'" : '';
        $parseStr .= "<option haschild='$hasChild' cmid='$cmid' value='$cid' $sel>$cname</option>";
    }
    $parseStr .= '</select>';
    return $parseStr;
}

/**
 * 获取要保存的字段数据
 *  $fieldPrefix：自动获取指定前缀的字段，并合并在$fieldMap
 */
function GetConfigDataToSave($fieldMap, $fieldPrefix=''){
    unset($_POST['SafeAnswer']);
    if(empty($fieldMap)) $fieldMap = array();
    if(!empty($fieldPrefix)){ //自动通过前缀获取fieldMap
        $prefixLen = strlen($fieldPrefix);
        foreach($_POST as $k=>$v){
            if($fieldPrefix ==substr($k, 0, $prefixLen)){
                $fieldMap[$k] = 2;  //自动获取的默认都是字符串
            }
        }
    }

    $data = array();
    if(empty($fieldMap)) return $data;
    //设置不过滤的配置变量
    $map = array(
        'WEB_CLOSE_REASON'=>1, 'WEB_ICP'=>1, 'STAT_CODE'=>1, 'ASYNC_STAT_CODE'=>1,
        'APP_ABOUT'=>1, 'ONLINE_FOOTER_TEXT'=>1, 'Support3Js'=>1, 'ImageProcess'=>1,
    );
    foreach($_POST as $k=>$v){
        if( !isset($fieldMap[$k]) ) continue;
        $type = $fieldMap[$k];
        if(1 == $type){ // 转数字
            $data[$k] = intval($_POST[$k]);
        }else{  //字符串
            $data[$k] = $_POST[$k];
            if(is_numeric($data[$k]) || empty($data[$k]) || isset($map[$k])) continue;
            //这里不建议使用htmlspecial 会转义单引号
            $data[$k] = strip_tags($data[$k]);
            $data[$k] = str_ireplace('"', '', $data[$k]); //替换双引号（不能替换单引号，因为英文版可能出现单引号）
        }
    }

    //无需修改的敏感数据
    $list = array(
        'EMAIL_PASSWORD', 'BaiduPushToken', 'WX_APP_SECRET', 'XCX_APP_SECRET', 'XCX_ACCOUNT_KEY',
        'SMS_PASSWORD', 'AliAccessKeySecret', 'QiniuSecretKey', 'BAIDU_TRANSLATE_APIKEY'
    );
    import("@.Common.YdSafe");
    foreach($list as $key){
        if(!isset($data[$key])) continue;
        $b = YdSafe::isSensitiveData($data[$key]);
        if($b){  //未修改的敏感数据，不用保存
            unset($data[$key]);
        }
    }
    return $data;
}

function HideSensitiveData($value){
    import("@.Common.YdSafe");
    $value = YdSafe::hideSensitiveData($value);
    return $value;
}

function IsDbReadonly(){
    import("@.Common.YdServerInfo");
    $s = new YdServerInfo();
    $b = $s->isDbReadonly();
    return (1==$b) ? true : false;
}

function GetUploadDir(){
    //$dir = $GLOBALS['Config']['UPLOAD'];  //如：./Upload/
    $dir = './Upload/';
    //IsOverWrite存在表示是从文件管理上传，如果选择的是根目录则savepath为空。以下代码让选择根目录上传有效
    if(isset($_POST['IsOverWrite']) && empty($_POST['savepath'])){
        return $dir;
    }

    $type = (int)$GLOBALS['Config']['UPLOAD_DIR_TYPE'];
    if(1 == $type){ //按年月生成目录
        $dir .= date("Ym").'/';
        if(!is_dir($dir)) @mk_dir($dir);
    }else{ //默认不创建目录

    }
    return $dir;
}