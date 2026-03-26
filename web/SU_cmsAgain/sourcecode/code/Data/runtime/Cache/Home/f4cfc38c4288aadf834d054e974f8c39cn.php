<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>
<head>
    <title><?php echo ($Title); ?>-<?php echo ($WebName); ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="renderer" content="webkit">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="author" content="<?php echo ($WebName); ?>">
<meta name="keywords" content="<?php echo ($Keywords); ?>">
<meta name="description" content="<?php echo ($Description); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=0,minimal-ui">
<meta name="format-detection" content="telephone=no">
<meta name="HomeLanguageMark" content="<?php echo ($HomeLanguageMark); ?>">
<link href="<?php echo ($WebIcon); ?>" type="image/x-icon" rel="icon">
<link href="<?php echo ($WebIcon); ?>" type="image/x-icon" rel="shortcut icon">
<link href="//res.youdiancms.com/common.css<?php echo ($NoCache); ?>" rel="stylesheet" type="text/css" />
<link href="<?php echo ($Css); ?>style.css<?php echo ($NoCache); ?>" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="<?php echo ($WebPublic); ?>jquery/jquery.min.js"></script>
<script type="text/javascript" src="//res.youdiancms.com/common.js<?php echo ($NoCache); ?>"></script>
<?php echo ($AsyncStat); ?>
<!-- 全局主题样式控制 -->
<style type="text/css">
	/*=====主题色 开始=====*/
	body{ background-color: <?php echo ($TBgColor); ?>; }
	.btn{ background-color: <?php echo ($TThemeColor); ?>; }
	/*父级触发子级*/
	.ThemeColorParent:hover .ThemeColorChild{ color: <?php echo ($TThemeColor); ?> !important; }
	.ThemeColorParent:hover .ThemeColorBgChild,.ThemeColorParent:hover .ThemeColorBgChildAfter:after{ background-color: <?php echo ($TThemeColor); ?> !important; color: #FFF !important; }
	.ThemeColorParent:hover .ThemeColorBgColorChild { color: #FFF !important; }
	/*自身触发*/
	.ThemeColor,.ThemeColorHover:hover{ color: <?php echo ($TThemeColor); ?> !important; }
	.ThemeColorBg,.ThemeColorBgHover:hover{ background-color: <?php echo ($TThemeColor); ?> !important; color: #FFF !important; }
	.ThemeBeforeColorBg:before,.ThemeAfterColorBg:after { background-color: <?php echo ($TThemeColor); ?> !important; }
	.ThemeColorBorder,.ThemeColorBorderHover:hover,.ThemeColorBorderAfter:after{ border-color: <?php echo ($TThemeColor); ?> !important; }
	.ThemeColorBorderBottom,.ThemeColorBorderBottomHover:hover{ border-bottom-color: <?php echo ($TThemeColor); ?>; }
	.ThemeColorBtnHover:hover { border-color:<?php echo ($TThemeColor); ?> !important; color:<?php echo ($TThemeColor); ?> !important; }
	/*=====主题色 结束=====*/

	/*=====其他不可内联主题及变量 开始=====*/
	/*语言切换*/
	#language a{ background-color: <?php echo ($TThemeColor); ?>; }
	/*导航*/
	.logo_main_shade2{ background-color: <?php echo ($TNavigationBgColor); ?>; opacity: <?php echo ($TNavigationBgColorOpacity); ?>; }
	#logo_main.istop,#logo_main.navigation-style1,#logo_main.navigation-style3{ background-color: <?php echo ($TNavigationBgColor); ?>; }
	#navigation ul.navigationlist>li>a{ padding: 0 <?php echo ($TNavigationSpace); ?>px; }
	#navigation ul.navigationlist li a{ color: <?php echo ($TNavigationColor); ?>; font-size: <?php echo ($TNavigationSize); ?>px; }
	/*幻灯片*/
	.bannerlist li .bannertext{ top: <?php echo ($TBannerTextTop); ?>%; text-align: <?php echo ($TBannerTextAlign); ?>; }
	.bannerlist li .bannertext .BannerName{ color: <?php echo ($TBannerNameColor); ?>; font-size: <?php echo ($TBannerNameSize); ?>px; }
	.bannerlist li .bannertext .BannerDescription{ color: <?php echo ($TBannerDescriptionColor); ?>; font-size: <?php echo ($TBannerDescriptionSize); ?>px; }
	/*侧边栏*/
	.sidebar_title h2 { border-color: <?php echo ($TThemeColor); ?>; }
	.sidelist li a:hover .InfoTitle{ color: <?php echo ($TThemeColor); ?>; }
	/*翻页*/
	.page .current{ background-color: <?php echo ($TThemeColor); ?>; border-color: <?php echo ($TThemeColor); ?>; }
	/*联系我们图标*/
	#Map .ContactInfo i{ color: <?php echo ($TThemeColor); ?>; }
</style>

<script>
	$(document).ready(function(e) {
		pageInit();
		function pageInit(){
			if( $("#member").length > 0 ){
				if( "<?php echo ($EnableHtml); ?>" == 1 ){
					$.get("<?php echo JsonUrl();?>", null, function(data){ UpdateLoginStatus(data['MemberID'], data['MemberName'], data['EnableMember']); },"json");
				}else{
					UpdateLoginStatus("<?php echo ($MemberID); ?>", "<?php echo ($MemberName); ?>", "<?php echo ($EnableMember); ?>");
				}
			}
		}
		function UpdateLoginStatus(id, name, flag){
			if(flag==0){
				$("#member").remove();
				return;
			}
			var html = "";
			if( id ){
				html += '<span class="MemberName">'+name+'&nbsp;</span>';
				html += '<a href="<?php echo MemberUrl();?>" target="_blank"><?php echo (L("MemberCenter")); ?>&nbsp;&nbsp;&nbsp;</a>';
				html += '<a href="<?php echo MemberLogoutUrl();?>" target="_self" style="color: red;"><?php echo (L("MemberQuit")); ?></a>';
			}else{
				html += '<a href="<?php echo MemberLoginUrl();?>" target="_self"><?php echo (L("Login")); ?>&nbsp;&nbsp;&nbsp;</a>';
				html += '<a href="<?php echo MemberRegUrl();?>" target="_self"><?php echo (L("Reg")); ?>&nbsp;</a>';
			}
			$("#member").html( html );
		}
	});
</script>

</head>
<body class="body_index">
	<!-- wap Logo 开始-->
<div id="wap_logo_main">
  <div id="wap_logo">
     <?php if(!empty($WebLogo)): ?><div id="menu"></div>
       <div class="WebLogo">
       		<a class="WebLogo" href="<?php echo HomeUrl();?>"><img src="<?php echo ($WapLogo); ?>" /></a>
       		<?php if(!empty($Language)): if(($HomeLanguageMark) == "en"): ?><a class="languagebtn ThemeColorBg" href="<?php echo LanguageUrl('cn');?>" target="_self">中</a><?php endif; ?>
			<?php if(($HomeLanguageMark) == "cn"): ?><a class="languagebtn ThemeColorBg" href="<?php echo LanguageUrl('en');?>" target="_self">EN</a><?php endif; endif; ?>
       		<a class="shownavbtn" href="javascript:;"></a>
       	</div><?php endif; ?>
  </div>
</div>
<!--wap Logo 结束-->

<!--wap 导航 开始-->
<div id="wap_navigation">
    <i id="wap_navigationshade"></i>
    <ul class="wap_navigationlist">
        <div class="seachwrap">
            <form name="frmInfoSearch" method="post" action="<?php echo InfoSearchAction();?>">
                <input class="Keywords" name="Keywords" value="<?php echo ($SearchWord); ?>" type="text" placeholder="<?php echo (L("InputKeyword")); ?>"/>
                <input class="btnSearch" name="btnSearch" class="btn" type="submit" value=""  />
            </form>
        </div>
        <?php $_result=get_navigation(0,1,"-1",1,-1,-1,"");if(is_array($_result)): $i = 0; $__LIST__ = $_result;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$n1): $mod = ($i % 2 );++$i;?><li class="depth0">
          		<a href="<?php echo ($n1["ChannelUrl"]); ?>" class='<?php if(($n1["ChannelID"]) == $TopChannelID): ?>ThemeColor<?php endif; ?> minBorderBottom'><?php echo ($n1["ChannelName"]); ?></a>
	              <?php if(($n1["HasChild"]) == "1"): ?><i class="showmore"></i>
	                  <ul class="wap_subnavigationlist">
	                      <?php $_result=get_navigation($n1["ChannelID"],2,"-1",1,-1,-1,"");if(is_array($_result)): $i = 0; $__LIST__ = $_result;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$n2): $mod = ($i % 2 );++$i;?><li class="depth<?php echo ($n2["ChannelDepth"]); ?>"><a class="minBorderBottom" href="<?php echo ($n2["ChannelUrl"]); ?>"><?php echo ($n2["ChannelName"]); ?></a></li><?php endforeach; endif; else: echo "" ;endif; ?>
	                  </ul><?php endif; ?>
            </li><?php endforeach; endif; else: echo "" ;endif; ?>
    </ul>
</div>
<!--wap 导航 结束-->

<!--区块5041 开始-->
<div id="n5041" class="component floor_head0_main">
	<style type="text/css">
		#n5041{
			<?php echo (parsebg($THead0Bg5041)); ?>
		}
		#n5041 .floor_head0_shade2{
			<?php echo (parsebg($THead0Bg5041)); ?>
			opacity: <?php echo ($THead0BgColorOpacity5041); ?>;
			height: <?php echo ($THead0LogoHeight5041); ?>px;
			padding: <?php echo ($THead0Padding5041); ?>px 0;
		}
		#n5041 .floor_head0.navigation-style1{
			<?php echo (parsebg($THead0Bg5041)); ?>
		}
		#n5041.notTop .floor_head0.navigation-style2{
			<?php echo (parsebg($THead0Bg5041)); ?>
		}
		#n5041 .floor_head0{
			padding: <?php echo ($THead0Padding5041); ?>px 0;
		}
		#n5041 .logo img{
			height: <?php echo ($THead0LogoHeight5041); ?>px;
		}
		#n5041 .navigation{
			text-align: <?php echo ($THead0NavigationAlign5041); ?>;
		}
		#n5041 .navigation ul.navigationlist li{
			line-height: <?php echo ($THead0LogoHeight5041); ?>px;
		}
		#n5041 .navigation ul.navigationlist>li>a{
			<?php echo (parsefont($THead0NavigationFont5041)); ?>
			padding-left: <?php echo ($THead0NavigationSpace5041); ?>px;
			padding-right: <?php echo ($THead0NavigationSpace5041); ?>px;
		}
		#n5041 .subnavigationlist{
			<?php echo (parsebg($THead0SubNavigationBg5041)); ?>
		}
		#n5041 .subnavigationlist a{
			<?php echo (parsefont($THead0SubNavigationFont5041)); ?>
		}
		#n5041 .user{
			line-height: <?php echo ($THead0LogoHeight5041); ?>px;
		}
		#n5041 #member a.login_btn{
			<?php echo (parsefont($THead0MemberLoginFont5041)); ?>
			<?php echo (parsebutton($THead0LoginBtn5041)); ?>
			background: <?php echo ($THead0LoginBtnBgColor5041); ?>;
		}
		#n5041 #member a.login_btn:hover{
			<?php echo (parsebuttonhover($THead0LoginBtn5041)); ?>
		}
		#n5041 #member a.reg_btn{
			<?php echo (parsefont($THead0MemberRegFont5041)); ?>
			<?php echo (parsebutton($THead0RegBtn5041)); ?>
			background: <?php echo ($THead0RegBtnBgColor5041); ?>;
			margin-left: <?php echo ($THead0MemberBtnMargin5041); ?>px;
		}
		#n5041 #member a.reg_btn:hover{
			<?php echo (parsebuttonhover($THead0RegBtn5041)); ?>
		}
		#n5041 .navigation ul.navigationlist>li>a.current,
		#n5041 .navigation ul.navigationlist>li:hover>a{
			<?php echo (parsefont($THead0NavigationHoverFont5041)); ?>;
			<?php echo (parsebg($THead0NavigationHoverBg5041)); ?>;
		}
		#n5041 .navigation ul.navigationlist>li.separator{
			width: <?php echo ($THead0NavigationSeparatorWidth5041); ?>px;
			height: <?php echo ($THead0LogoHeight5041); ?>px;
			<?php echo (parsebg($THead0NavigationSeparatorBg5041)); ?>
		}
		#n5041 .navigation ul.navigationlist>li.separator span{
			color: <?php echo ($THead0NavigationSeparatorColor5041); ?>;
		}
		#n5041 .subnavigationlist a:hover{
			<?php echo (parsebg($THead0SubNavigationHoverBg5041)); ?>
			<?php echo (parsefont($THead0SubNavigationHoverFont5041)); ?>
		}
		#n5041 .nav_active{
			background: <?php echo ($THead0NavigationActiveLineColor5041); ?>;
		}
	</style>
	<i class="floor_head0_shade<?php echo ($THead0Style5041); ?>"></i>
	<div class="floor_head0 navigation-style<?php echo ($THead0Style5041); ?>" yd-add="1" yd-delete="1" yd-order="1" yd-group="5041" yd-content="channel">
	    <div class="head0 full-width<?php echo ($THead0FullWidth5041); ?>">
			<div class="logo" yd-content="basic" yd-group="5041" yd-tab="基本设置">
				<a href="<?php echo HomeUrl();?>" target="_self">
					<img src="<?php echo ($WebLogo); ?>" title="<?php echo ($WebName); ?>" alt="<?php echo ($WebName); ?>" />
				 </a>
			</div>
			<div class="user">
				<?php if(!empty($THead0ShowMember5041)): ?><div id="member" class="member" yd-content="reg" yd-group="5041" yd-tab="登录注册设置"></div><?php endif; ?>
				<?php if(!empty($THead0ShowLanguage5041)): if(!empty($Language)): ?><div class="language" yd-content="language">
							<?php if(($HomeLanguageMark) == "en"): ?><a class="ThemeColorBg" href="<?php echo LanguageUrl('cn');?>" target="_self">中</a><?php endif; ?>
							<?php if(($HomeLanguageMark) == "cn"): ?><a class="ThemeColorBg" href="<?php echo LanguageUrl('en');?>" target="_self">EN</a><?php endif; ?>
						</div><?php endif; endif; ?>
			</div>
			<div class="navigation">
				<ul class="navigationlist">
					<i class="nav_active"></i>
					<?php $_result=get_navigation(0,1,"-1",1,-1,-1,"");if(is_array($_result)): $ci = 0; $__LIST__ = $_result;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$c1): $mod = ($ci % 2 );++$ci; if(($ci) != "1"): ?><li class="separator"><?php if(!empty($THead0NavigationSeparatorImg5041)): ?><img src="<?php echo ($THead0NavigationSeparatorImg5041); ?>" /><?php else: ?><span><?php echo ($THead0NavigationSeparator5041); ?></span><?php endif; ?></li><?php endif; ?>
						<li class="list-item" data-index="<?php echo ($ci); ?>">
							<a href="<?php echo ($c1["ChannelUrl"]); ?>" target="<?php echo ($c1["ChannelTarget"]); ?>" class="<?php if(($c1["ChannelID"]) == $TopChannelID): ?>current<?php endif; ?>"><?php echo ($c1["ChannelName"]); ?></a>
							<?php if(!empty($THead0SubNavigationStyle5041)): if(($c1["HasChild"]) == "1"): ?><ul class="subnavigationlist style<?php echo ($THead0SubNavigationStyle5041); ?>">
										<?php $_result=get_navigation($c1["ChannelID"],1,"-1",1,-1,-1,"");if(is_array($_result)): $i = 0; $__LIST__ = $_result;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$c2): $mod = ($i % 2 );++$i;?><li><a href="<?php echo ($c2["ChannelUrl"]); ?>" target="<?php echo ($c2["ChannelTarget"]); ?>"><?php echo ($c2["ChannelName"]); ?></a></li><?php endforeach; endif; else: echo "" ;endif; ?>
									</ul><?php endif; endif; ?>
						</li><?php endforeach; endif; else: echo "" ;endif; ?>
				</ul>
			</div>
	    </div>
	</div>
	<script>
		$(function(){
			var width = 0;
			var left = 0;
			//用户
			pageInit();
			function pageInit(){
				if( $("#n5041 #member").length > 0 ){
					if( "<?php echo ($EnableHtml); ?>" == 1 ){
						$.get("<?php echo JsonUrl();?>", null, function(data){ UpdateLoginStatus(data['MemberID'], data['MemberName'], data['EnableMember']); },"json");
					}else{
						UpdateLoginStatus("<?php echo ($MemberID); ?>", "<?php echo ($MemberName); ?>", "<?php echo ($EnableMember); ?>");
					}
				}
			}
			function UpdateLoginStatus(id, name, flag){
				if(flag==0){
					$("#n5041 #member").remove();
					return;
				}
				var html = "";
				if( id ){
					html += '<a href="<?php echo MemberUrl();?>" target="_blank" class="MemberName">'+name+'</a>';
					html += '<a href="<?php echo MemberLogoutUrl();?>" target="_self" style="color: red;"><?php echo (L("MemberQuit")); ?></a>';
				}else{
					html += '<a href="<?php echo MemberLoginUrl();?>" class="login_btn" target="_self"><?php echo (L("Login")); ?></a>';
					html += '<a href="<?php echo MemberRegUrl();?>" class="reg_btn" target="_self"><?php echo (L("Reg")); ?></a>';
				}
				$("#n5041 #member").html( html );
			}

			// pc导航栏js
			$('#n5041 ul.navigationlist li').mousemove(function(){
				if($(this).find('ul').is(":animated")) return;
				$(this).find('ul').slideDown(280);
				if($(this).find('.style2').length > 0){
					var length = $('#n5041 ul.navigationlist>li.list-item').length;
					console.log(length - $(this).data('index'), length);
					if(length - $(this).data('index') <= 3){
						var child = $(this).find('.subnavigationlist');
						child.css({
							marginLeft: - (child.width() - $(this).width()) + 'px',
						})
					}
				}
			});
			$('#n5041 ul.navigationlist li').mouseleave(function(){
				$(this).find('ul').slideUp(280);
			});
			
			$('#n5041 .nav_active').css({
				width: $('#n5041 .navigationlist li a.current').parent().width(),
				left: $('#n5041 .navigationlist li a.current').parent()[0] && $('#n5041 .navigationlist li a.current').parent()[0].offsetLeft,
			});
			$('#n5041 .navigationlist li').mousemove(function(){
				$('#n5041 .nav_active').css({
					width: $(this).width(),
					left: $(this)[0].offsetLeft
				});
			})
			$('#n5041 .navigationlist li').mouseleave(function(){
				$('#n5041 .nav_active').css({
					width: $('#n5041 .navigationlist li a.current').parent().width(),
					left: $('#n5041 .navigationlist li a.current').parent()[0] && $('#n5041 .navigationlist li a.current').parent()[0].offsetLeft,
				});
			})

			$('#n5041 .subnavigationlist').mousemove(function(){
				$('#n5041 .nav_active').css('left', $(this).parent()[0].offsetLeft);
			})

			if($('#n5041').find('.floor_head0').hasClass('navigation-style2')){
				$('#n5041').height('0')
			}else{
				$('#n5041').height($('#n5041 .floor_head0')[0].clientHeight)
			}
			// 头部固定
			function logoMainChange(){
				if($('#n5041').offset().top < $(window).scrollTop()){
					$('#n5041').addClass('notTop');
				}else{
					$('#n5041').removeClass('notTop');
				}
			}
			// 滚动事件
			$(window).scroll(function(){
				logoMainChange();
			});
		})
	</script>
</div>
<!--区块5041 结束--><!--区块5150 开始-->
<?php if(($Html) != "index"): ?><div id="n5150" class="component floor_channel15_main" yd-add="1" yd-delete="1" yd-order="1" yd-group="5150">
    <style type="text/css">
        #n5150{
            <?php echo (parsebg($TChannel15Bg5150)); ?>
            padding-top:<?php echo ($TChannel15PaddingTop5150); ?>px;
            padding-bottom:<?php echo ($TChannel15PaddingBottom5150); ?>px;
        }
        #n2314 .TheChannelContent{
            animation-duration: <?php echo ($TChannel15AnimationTime5150); ?>s;
            animation-fill-mode: both;
        }
    </style>
    <div class="floor_channel15 full-width<?php echo ($TChannel15FullWidth5150); ?>">
        <div class="component_body">
            <div class="banner_img">
                <?php if(!empty($ChannelPicture)): ?><div yd-content="_channel,<?php echo ($ChannelID); ?>,3"><img pc-src="<?php echo ($ChannelPicture); ?>" wap-src="<?php echo ($f2); ?>" title="<?php echo ($ChannelName); ?>" alt="<?php echo ($ChannelName); ?>" /></div>
                <?php else: ?>
                    <?php if((channelpicture($TopChannelID)) != ""): ?><div yd-content="_channel,<?php echo ($TopChannelID); ?>,3"><img pc-src="<?php echo (channelpicture($TopChannelID)); ?>" wap-src="<?php echo (channelf2($TopChannelID)); ?>" title="<?php echo (channelname($TopChannelID)); ?>" alt="<?php echo (channelname($TopChannelID)); ?>" /></div>
                    <?php else: ?>
                        <div class="TheChannel">
                            <img pc-src="<?php echo ($TChannel15PcPicture5150); ?>" wap-src="<?php echo ($TChannel15WapPicture5150); ?>" title="<?php echo ($TChannel15PictureAlt5150); ?>" alt="<?php echo ($TChannel15PictureAlt5150); ?>" />
                            <div class="TheChannelContent" yd-animation="<?php echo ($TChannel15AnimationType5150); ?>"><?php echo ($TChannel15Content5150); ?></div>
                        </div><?php endif; endif; ?>
            </div>
        </div>
    </div>
</div><?php endif; ?>
<!--区块5150 结束--><!--区块5133 开始-->
<?php if(($Html) != "index"): ?><div id="n5133" class="component floor_channel1_main" yd-add="1" yd-delete="1" yd-order="1" yd-group="5133" yd-content="channel">
    	<?php if(!empty($TChannel1Show5133)): if(($TopHasChild) == "1"): ?><style type="text/css">
                    #n5133{
                        <?php echo (parsebg($TChannel1Bg5133)); ?>
                        padding-top: <?php echo ($TChannel1PaddingTop5133); ?>px;
                        padding-bottom: <?php echo ($TChannel1PaddingBottom5133); ?>px;
                    }
                    #n5133 .separator{
                        padding: 0 <?php echo ($TChannel1Space5133); ?>px;
                    }
                    #n5133 li a{
                        width:<?php echo ($TChannel1LiWidth5133); ?>px;
                        <?php echo (parsefont($TChannel1Font5133)); ?>
                       <?php echo (parsebg($TChannel1LiBg5133)); ?>
                       <?php echo (parseborder($TChannel1LiBorder5133)); ?>
                    }
                    #n5133 li a.current, #n5133 li a:hover{
                        <?php echo (parsefont($TChannel1HoverFont5133)); ?>
                        <?php echo (parsebg($TChannel1LiHoverBg5133)); ?>
                        <?php echo (parseborder($TChannel1LiHoverBorder5133)); ?>
                    }
                </style>
    			<div class="floor_channel1 full-width<?php echo ($TChannel1FullWidth5133); ?>">
    				<ul yd-animation>
    					<?php $_result=get_navigation($TopChannelID,1,"-1",1,-1,-1,"");if(is_array($_result)): $i = 0; $__LIST__ = $_result;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$c): $mod = ($i % 2 );++$i; if(($i) != "1"): ?><li class="separator"><?php echo ($TChannel1Separator5133); ?></li><?php endif; ?>
    						<li class="n<?php echo ($i); ?>">
    							<a class="depth2 <?php if(($c["ChannelID"]) == $ChannelID): ?>current<?php endif; ?>" href="<?php echo ($c["ChannelUrl"]); ?>"><?php echo ($c["ChannelName"]); if(($c["HasChild"]) == "1"): ?><i class="ydicon-xsj2"></i><?php endif; ?></a>
    							<?php if(($c["HasChild"]) == "1"): ?><div class="depth3list">
    									<?php $_result=get_navigation($c["ChannelID"],1,"-1",1,-1,-1,"");if(is_array($_result)): $i = 0; $__LIST__ = $_result;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$c1): $mod = ($i % 2 );++$i;?><a class="depth3" href="<?php echo ($c1["ChannelUrl"]); ?>"><?php echo ($c1["ChannelName"]); ?></a><?php endforeach; endif; else: echo "" ;endif; ?>
    								</div><?php endif; ?>
    						</li><?php endforeach; endif; else: echo "" ;endif; ?>
    				</ul>
    			</div><?php endif; endif; ?>
    </div><?php endif; ?>
<!--区块5133 结束-->


<!--区块5109 开始-->
<div id="n5109" class="component floor_banner0_main" yd-add="1" yd-delete="1" yd-order="1" yd-group="5109" yd-content="banner,<?php echo ($TBanner0Group5109); ?>">
	<style type="text/css">
		#n5109 .bannertext{
			top: <?php echo ($TBanner0TextTop5109); ?>%;
			text-align: <?php echo ($TBanner0TextAlign5109); ?>;
		}
		#n5109 .bannertext .BannerName{ 
			<?php echo (parsefont($TBanner0NameFont5109)); ?>
		}
		#n5109 .bannertext .BannerDescription{
			<?php echo (parsefont($TBanner0DescriptionFont5109)); ?>
		}
		#n5109 .swiper-pagination{
			yd-previewable-class: banner_dot banner_dot<?php echo ($TBanner0Dot5109); ?>;
		}
		@media screen and (min-width: 1200px) {
			.floor_banner0{
				height: 520px;
			}
		}
		@media screen and (max-width: 1199px) {
			.floor_banner0{
				height: 160px;
			}
		}
	</style>
	<div class="floor_banner0">
		<div class="swiper-container">
			<ul class="banner0 swiper-wrapper">
				<?php $_result=get_banner_list($TBanner0Group5109);if(is_array($_result)): $i = 0; $__LIST__ = $_result;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$b): $mod = ($i % 2 );++$i;?><li class="swiper-slide">
						<?php if(!empty($b["BannerUrl"])): ?><a href="<?php echo ($b["BannerUrl"]); ?>" target="_blank" ><img src="" pc-src="<?php echo ($b["BannerImage"]); ?>" wap-src="<?php echo ($b["BannerThumbnail"]); ?>" title="<?php echo ($b["BannerName"]); ?>" alt="<?php echo ($b["BannerName"]); ?>" /></a>
							<?php else: ?>
							<img src="" pc-src="<?php echo ($b["BannerImage"]); ?>" wap-src="<?php echo ($b["BannerThumbnail"]); ?>" title="<?php echo ($b["BannerName"]); ?>" alt="<?php echo ($b["BannerName"]); ?>" /><?php endif; ?>
						<div class="bannertext">
							<?php if(!empty($b["BannerName"])): ?><div class="BannerName" yd-text="banner,<?php echo ($b["BannerID"]); ?>,BannerName"><?php echo ($b["BannerName"]); ?></div><?php endif; ?>
							<?php if(!empty($b["BannerDescription"])): ?><div class="BannerDescription" yd-text="banner,<?php echo ($b["BannerID"]); ?>,BannerDescription"><?php echo (nl2br($b["BannerDescription"])); ?></div><?php endif; ?>
						</div>
					</li><?php endforeach; endif; else: echo "" ;endif; ?>
			</ul>
		</div>
		<div class="swiper-pagination banner_dot<?php echo ($TBanner0Dot5109); ?>"></div>
	</div>
	<script type="text/javascript">
		$(function(){
			var isLoad = false;
			$('#n5109 li img').load(function(){
				if(isLoad) return;
				isLoad = true;
				var mySwiper = new Swiper('#n5109 .swiper-container',{
					autoplay : 5000,//可选选项，自动滑动
					autoplayDisableOnInteraction : false, //操作后是否禁止自动滑动
					loop : true,//可选选项，开启循环
					pagination : '#n5109 .swiper-pagination', //分页器容器
					paginationClickable :true, //分页器是否可以点击
					speed: 1500, //滑动速度
					mode : '<?php echo ($TBanner0Effect5109); ?>',
	                calculateHeight : true, //自动计算高度
				})
				$('#n5109 .floor_banner0').height('auto');
			})
		})
	</script>
</div>
<!--区块5109 结束--><!--区块5137 开始-->
<div id="n5137" class="component floor_article13_main" yd-add="1" yd-delete="1" yd-order="1" yd-group="5137" yd-content="info,<?php echo ($TArticle13Channel5137); ?>">
	<?php if(!empty($TArticle13Show5137)): ?><style type="text/css">
			#n5137{
				<?php echo (parsebg($TArticle13Bg5137)); ?>
				padding:<?php echo ($TArticle13Padding5137); ?>px 0;
			}
			#n5137 .component_title a{ 
				<?php echo (parsefont($TArticle13TitleFont5137)); ?>
			}
			#n5137 .component_bottom a,
			#n5137 li a{ 
				<?php echo (parsefont($TArticle13InfoTitleFont5137)); ?>
			}
			#n5137 li a{
				yd-previewable-class: ydicon- ydicon-<?php echo ($TArticle13TitleIcon5137); ?>;
			}
			#n5137 li a:before{
				color: <?php echo ($TArticle13TitleIconColor5137); ?>;
			}
			#n5137 li span{ 
				<?php echo (parsefont($TArticle13InfoTimeFont5137)); ?>
			}
			#n5137 li{
				animation-duration: <?php echo ($TArticle13AnimationTime5137); ?>s;
				animation-fill-mode: both;
			}
		</style>
		<div class="floor_article13 display<?php echo ($TArticle13Show5137); ?> full-width<?php echo ($TArticle13FullWidth5137); ?>">
			<div class="component_title" yd-group="5137" yd-tab="标题设置">
				<img src="<?php echo ($TArticle13Icon5137); ?>" />
				<a href="<?php echo (channelurl($TArticle13Channel5137)); ?>" target="_blank" yd-text="channel,<?php echo ($TArticle13Channel5137); ?>,ChannelName"><?php echo (channelname($TArticle13Channel5137)); ?></a>
			</div>
			<div class="component_body">
				<ul class="article13">
					<?php $_labelid=$$TArticle13Label5137; $_timeformat="Y-m-d"; $_keywords=""; $_orderby=""; $_field="";$_pagesize=0; $_provinceid=-1;$_cityid=-1;$_districtid=-1;$_townid=-1; $_suffix="..."; $_specialid=0; $_minprice=-1;$_maxprice=-1;$_attr_info=""; $_result=get_info($TArticle13Channel5137, $_specialid, $TArticle13Count5137, $_timeformat, 0, $_suffix, $_labelid, 0, $_keywords, $_orderby, $_minprice, $_maxprice, $_attr_info, -1, $_field, $_pagesize, $_provinceid, $_cityid, $_districtid, $_townid);if(is_array($_result)): $i = 0; $__LIST__ = $_result;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$f): $mod = ($i % 2 );++$i;?><li yd-animation="<?php echo ($TArticle13AnimationType5137); ?>"><a href="<?php echo ($f["InfoUrl"]); ?>" target="_blank" class="ydicon-<?php echo ($TArticle13TitleIcon5137); ?>"><?php echo ($f["InfoTitle"]); ?></a><span><?php echo ($f["InfoTime"]); ?></span></li><?php endforeach; endif; else: echo "" ;endif; ?>
				</ul>
			</div>
			<div class="component_bottom"><a href="<?php echo (channelurl($TArticle13Channel5137)); ?>" target="_blank"><?php echo (L("more")); ?><i class="ydicon-right"></i></a></div>
		</div><?php endif; ?>
</div>
<!--区块5137 结束--><!--区块5110 开始-->
<div id="n5110" class="component floor_other1_main"  yd-add="1" yd-delete="1" yd-order="1" yd-group="5110" yd-content="_channel,<?php echo ($TOther1Channel5110); ?>">
	<?php if(!empty($TOther1Show5110)): ?><style type="text/css">
			#n5110{
				padding:<?php echo ($TOther1Padding5110); ?>px 0;
				<?php echo (parsebg($TOther1Bg5110)); ?>
			}
			#n5110 .component_title h2 a{
				<?php echo (parsefont($TOther1TitleFont5110)); ?>
			}
			#n5110 .component_title p{
				<?php echo (parsefont($TOther1SubtitleFont5110)); ?>
			}
			#n5110 .AlbumTitle{
				<?php echo (parsefont($TOther1InfoTitleFont5110)); ?>
			}
			#n5110 .AlbumDescription{
				<?php echo (parsefont($TOther1InfoSContentFont5110)); ?>
			}
			#n5110 li{
				animation-duration: <?php echo ($TOther1AnimationTime5110); ?>s;
				animation-fill-mode: both;
				yd-previewable-class: column column<?php echo ($TOther1ColumnCount5110); ?>;
			}
		</style>
		<div class="floor_other1 display<?php echo ($TOther1Show5110); ?> full-width<?php echo ($TOther1FullWidth5110); ?>">
			<?php if(!empty($TOther1TitleStyle5110)): ?><div class="component_title">
		            <h2 class="h2_<?php echo ($TOther1TitleStyle5110); ?> ThemeBeforeColorBg"><a href="<?php echo (channelurl($TOther1Channel5110)); ?>" yd-text="channel,<?php echo ($TOther1Channel5110); ?>,ChannelName"><?php echo (channelname($TOther1Channel5110)); ?></a></h2>
		            <p yd-text="channel,<?php echo ($TOther1Channel5110); ?>,ChannelSContent"><?php echo (channelscontent($TOther1Channel5110)); ?></p>
		        </div><?php endif; ?>
			<div class="component_body">
				<ul class="other1" yd-content="_channel,<?php echo ($TOther1Channel5110); ?>,4" yd-group="5110" yd-tab="列表设置">
					<?php $_result=get_channelalbum($TOther1Channel5110,"ChannelAlbum");if(is_array($_result)): $i = 0; $__LIST__ = array_slice($_result,0,$TOther1Count5110,true);if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$ca): $mod = ($i % 2 );++$i;?><li class="n<?php echo ($mod); ?> column<?php echo ($TOther1ColumnCount5110); ?>" yd-animation="<?php echo ($TOther1AnimationType5110); ?>">
							<a class="<?php echo ($TRoundCorner); ?>-round-corner ThemeColorParent">
								<div class="AlbumPicture"><img src="<?php echo (defaultpicture($ca["AlbumPicture"])); ?>" title="<?php echo ($ca["AlbumTitle"]); ?>" alt="<?php echo ($ca["AlbumTitle"]); ?>" /></div>
								<div class="AlbumWrap">
									<div class="AlbumTitle ThemeColorChild"><?php echo ($ca["AlbumTitle"]); ?></div>
									<div class="AlbumDescription"><?php echo ($ca["AlbumDescription"]); ?></div>
								</div>
							</a>
						</li><?php endforeach; endif; else: echo "" ;endif; ?>
				</ul>
			</div>
			<div class="component_bottom"></div>
		</div><?php endif; ?>
</div>
<!--区块5110 结束--><!--区块5148 开始-->
<div id="n5148" class="component floor_product0_main" yd-add="1" yd-delete="1" yd-order="1" yd-group="5148">
    <?php if(!empty($TProduct0Show5148)): ?><style type="text/css">
            #n5148{
                padding:<?php echo ($TProduct0Padding5148); ?>px 0;
                <?php echo (parsebg($TProduct0Bg5148)); ?>
            }
            #n5148 .component_title h2 a{ 
                <?php echo (parsefont($TProduct0TitleFont5148)); ?>
            }
            #n5148 .component_title p{ 
                <?php echo (parsefont($TProduct0SubtitleFont5148)); ?>
            }
            #n5148 .tabbar a{
                <?php echo (parsefont($TProduct0ChannelListFont5148)); ?>
            }
            #n5148 .InfoTitle{ 
                <?php echo (parsefont($TProduct0InfoTitleFont5148)); ?>
            }
            #n5148 .InfoSContent{ 
                <?php echo (parsefont($TProduct0InfoSContentFont5148)); ?>
            }
            #n5148 .InfoPrice{ 
                <?php echo (parsefont($TProduct0InfoPriceFont5148)); ?>
            }
            #n5148 .component_body ul{
                yd-previewable-class: -product0 <?php echo ($TProduct0Style5148); ?>-product0;
            }
            #n5148 .component_body li{
                yd-previewable-class: column column<?php echo ($TProduct0ColumnCount5148); ?>;
            }
            #n5148 li{
                animation-duration: <?php echo ($TProduct0AnimationTime5148); ?>s;
                animation-fill-mode: both;
            }
            #n5148 .thumb-product0 li .InfoPicture{
                width: <?php echo ($TProduct0InfoPictureSize5148); ?>%;
            }
            #n5148 .product0 li a{
                <?php echo (parsebg($TProduct0InfoListBg5148)); ?>
                <?php echo (parseborder($TProduct0InfoListBorder5148)); ?>
                padding: <?php echo ($TProduct0InfoListPadding5148); ?>;
                margin: <?php echo ($TProduct0InfoListMargin5148); ?>;
            }
        </style>
        <div class="floor_product0 display<?php echo ($TProduct0Show5148); ?> full-width<?php echo ($TProduct0FullWidth5148); ?>">
            <div class="component_title">
                <?php if(!empty($TProduct0TitleStyle5148)): ?><h2 class="h2_<?php echo ($TProduct0TitleStyle5148); ?> ThemeBeforeColorBg"><a href="<?php echo (channelurl($TProduct0Channel5148)); ?>" yd-text="channel,<?php echo ($TProduct0Channel5148); ?>,ChannelName"><?php echo (channelname($TProduct0Channel5148)); ?></a></h2>
                    <p yd-text="channel,<?php echo ($TProduct0Channel5148); ?>,ChannelSContent"><?php echo (channelscontent($TProduct0Channel5148)); ?></p><?php endif; ?>
                <ul class="tabbar" yd-content="channel,<?php echo ($TProduct0Channel5148); ?>">
                    <?php $_result=get_navigation($TProduct0Channel5148,1,"-1",1,-1,-1,"");if(is_array($_result)): $i = 0; $__LIST__ = $_result;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$c): $mod = ($i % 2 );++$i;?><li><a class="<?php if(($i) == "1"): ?>ThemeColorBg<?php endif; ?> n<?php echo ($i); ?>" href="javascript:;" data-index="<?php echo ($i); ?>"><?php echo ($c["ChannelName"]); ?></a></li><?php endforeach; endif; else: echo "" ;endif; ?>
                </ul>
            </div>
            <div class="component_body swiper-container" yd-content="info,<?php echo ($TProduct0Channel5148); ?>" yd-group="5148" yd-tab="列表设置">
                <div class="swiper-wrapper">
                    <?php $_result=get_navigation($TProduct0Channel5148,1,"-1",1,-1,-1,"");if(is_array($_result)): $i = 0; $__LIST__ = $_result;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$c): $mod = ($i % 2 );++$i;?><ul class="n<?php echo ($i); ?> swiper-slide product0 <?php echo ($TProduct0Style5148); ?>-product0">
                            <?php $_labelid=$TProduct0Label5148; $_timeformat="Y-m-d"; $_keywords=""; $_orderby=""; $_field="";$_pagesize=0; $_provinceid=-1;$_cityid=-1;$_districtid=-1;$_townid=-1; $_suffix="..."; $_specialid=0; $_minprice=-1;$_maxprice=-1;$_attr_info=""; $_result=get_info($c["ChannelID"], $_specialid, $TProduct0Count5148, $_timeformat, 0, $_suffix, $_labelid, 0, $_keywords, $_orderby, $_minprice, $_maxprice, $_attr_info, -1, $_field, $_pagesize, $_provinceid, $_cityid, $_districtid, $_townid);if(is_array($_result)): $i = 0; $__LIST__ = $_result;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$f): $mod = ($i % 2 );++$i;?><li class="n<?php echo ($mod); ?> column<?php echo ($TProduct0ColumnCount5148); ?>" yd-animation="<?php echo ($TProduct0AnimationType5148); ?>">
                                    <a href="<?php echo ($f["InfoUrl"]); ?>" target="_blank" class="<?php echo ($TRoundCorner); ?>-round-corner" yd-content="_info,<?php echo ($f["InfoID"]); ?>">
                                        <div class="InfoPicture <?php echo ($TRoundCorner); ?>-round-corner"><img src="<?php echo (defaultpicture($f["InfoPicture"])); ?>" title="<?php echo ($f["InfoTitle"]); ?>" alt="<?php echo ($f["InfoTitle"]); ?>"/></div>
                                        <div class="InfoWrap">
                                            <div class="InfoTitle"><?php echo ($f["InfoTitle"]); ?></div>
                                            <div class="InfoSContent">
                                                <?php if(!empty($f["InfoSContent"])): echo ($f["InfoSContent"]); else: echo (left(strip_tags($f["InfoContent"]),50)); endif; ?>
                                            </div>
                                        </div>
                                        <?php if(!empty($f["InfoPrice"])): ?><div class="InfoPrice">
                                                <span><?php echo ($CurrencySymbol); ?></span><?php echo ($f["InfoPrice"]); ?>
                                                <i class="ydicon-right3"></i>
                                            </div><?php endif; ?>
                                    </a>
                                </li><?php endforeach; endif; else: echo "" ;endif; ?>
                        </ul><?php endforeach; endif; else: echo "" ;endif; ?>
                </div>
            </div>
            <div class="component_bottom"></div>
        </div>
        <script type="text/javascript">
            $(function(){
                var swiper = new Swiper('#n5148 .swiper-container',{
                    speed: 500, //滑动速度
                    calculateHeight : true, //自动计算高度
                    onTouchEnd: function(item){
                        console.log(item);
                        $('#n5148 .tabbar a').removeClass('ThemeColorBg');
                        $('#n5148 .tabbar a.n' + (item.activeIndex + 1)).addClass('ThemeColorBg');
                    }
                });
                $('#n5148 .tabbar a').click(function(){
                    $('#n5148 .tabbar a').removeClass('ThemeColorBg');
                    $(this).addClass('ThemeColorBg');
                    swiper.swipeTo($(this).attr('data-index') - 1, 2000);
                })
            })
        </script><?php endif; ?>
</div>
<!--区块5148 结束--><!--区块5112 开始-->
<div id="n5112" class="component floor_video2_main" yd-add="1" yd-delete="1" yd-order="1" yd-group="5112" yd-content="info,<?php echo ($TVideo2Channel5112); ?>">
    <?php if(!empty($TVideo2Show5112)): ?><style type="text/css">
            #n5112{
                <?php echo (parsebg($TVideo2Bg5112)); ?>
            }
            #n5112 .InfoTitle{ 
                <?php echo (parsefont($TVideo2InfoTitleFont5112)); ?>
            }
            #n5112 .InfoSContent,#n5112 .InfoContent{
                <?php echo (parsefont($TVideo2InfoSContentFont5112)); ?>
            }
            #n5112 .swiper-active-switch{
                background-color: <?php echo ($TVideo2PaginationBgColor5112); ?>;
            }
            #n5112 li{
                animation-duration: <?php echo ($TVideo2AnimationTime5112); ?>s;
                animation-fill-mode: both;
            }
        </style>
        <div class="floor_video2 display<?php echo ($TVideo2Show5112); ?>">
            <div class="component_title"></div>
            <div class="component_body swiper-container">
                <ul class="video2 swiper-wrapper" yd-animation="<?php echo ($TVideo2AnimationType5112); ?>">
                    <!-- 循环 开始 -->
                    <?php $_labelid=$TVideo2Label5112; $_timeformat="Y-m-d"; $_keywords=""; $_orderby=""; $_field="";$_pagesize=0; $_provinceid=-1;$_cityid=-1;$_districtid=-1;$_townid=-1; $_suffix="..."; $_specialid=0; $_minprice=-1;$_maxprice=-1;$_attr_info=""; $_result=get_info($TVideo2Channel5112, $_specialid, $TVideo2Count5112, $_timeformat, 0, $_suffix, $_labelid, 0, $_keywords, $_orderby, $_minprice, $_maxprice, $_attr_info, -1, $_field, $_pagesize, $_provinceid, $_cityid, $_districtid, $_townid);if(is_array($_result)): $i = 0; $__LIST__ = $_result;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$f): $mod = ($i % 2 );++$i;?><li class="swiper-slide">
                            <div class="InfoPicture" yd-image="info,<?php echo ($f["InfoID"]); ?>,f1">
                                <img src="<?php echo ($f["f1"]); ?>" title="<?php echo ($f["InfoTitle"]); ?>" alt="<?php echo ($f["InfoTitle"]); ?>"/>
                                <i class="ydicon-play" onclick="videoPlay('<?php echo ($f["InfoAttachment"]); ?>')"></i>
                            </div>
                            <a class="ThemeColorParent" href="<?php echo ($f["InfoUrl"]); ?>" title="<?php echo ($f["InfoTitle"]); ?>" target="_blank" yd-content="_info,<?php echo ($f["InfoID"]); ?>">
                                <div class="InfoTitle ThemeColorChild"><?php echo ($f["InfoTitle"]); ?></div>
                                <div class="InfoSContent"><?php if(!empty($f["InfoSContent"])): echo ($f["InfoSContent"]); else: echo (left(strip_tags($f["InfoContent"]),100)); endif; ?></div>
                            </a>
                        </li><?php endforeach; endif; else: echo "" ;endif; ?>
                </ul>
                <div class="swiper-pagination"></div>
            </div>
            <div class="component_bottom"></div>
        </div>
        <script type="text/javascript">
            $(function(){
                var swiper = new Swiper('#n5112 .swiper-container',{
                    pagination : '#n5112 .swiper-pagination', //分页器容器
                    paginationClickable :true, //分页器是否可以点击
                    speed: 1000, //滑动速度
                    calculateHeight : true, //自动计算高度
                })
                if($('#n5112 .video2 a').height() <= 0) return;
                $('#n5112 .swiper-pagination').css('bottom', $('#n5112 .video2 a').height() + 30);
                $(window).resize(function(){
                    $('#n5112 .swiper-pagination').css('bottom', $('#n5112 .video2 a').height() + 30);
                })
            })
        </script><?php endif; ?>
</div>
<!--区块5112 结束--><!--区块5155 开始-->
<div id="n5155" class="component floor_picture0_main" yd-add="1" yd-delete="1" yd-order="1" yd-group="5155">
	<?php if(!empty($TPicture0Show5155)): ?><style type="text/css">
			#n5155{
				padding:<?php echo ($TPicture0Padding5155); ?>px 0;
				<?php echo (parsebg($TPicture0Bg5155)); ?>
			}
			#n5155 .component_title h2 a{
				<?php echo (parsefont($TPicture0TitleFont5155)); ?>
			}
			#n5155 .component_title p{
				<?php echo (parsefont($TPicture0SubtitleFont5155)); ?>
			}
			#n5155 .InfoTitle{
				<?php echo (parsefont($TPicture0InfoTitleFont5155)); ?>
			}
			#n5155 .InfoSContent{
				<?php echo (parsefont($TPicture0InfoSContentFont5155)); ?>
			}
			#n5155 ul{
				yd-previewable-class: -picture0 <?php echo ($TPicture0Style5155); ?>-picture0;
			}
			#n5155 li{
				animation-duration: <?php echo ($TPicture0AnimationTime5155); ?>s;
				animation-fill-mode: both;
				yd-previewable-class: column column<?php echo ($TPicture0ColumnCount5155); ?>;
			}
			#n5155 .thumb-picture0 li .InfoPicture{
				width: <?php echo ($TPicture0InfoPictureSize5155); ?>%;
			}
	        #n5155 .picture0 li .InfoWrap:after{
	            <?php if((parseitem($TPicture0InfoTitleFont5155,1)) == "0"): ?>display: none;<?php endif; ?>
	        }
	        #n5155 .picture0 li a{
	            <?php echo (parsebg($TPicture0InfoListBg5155)); ?>
	            <?php echo (parseborder($TPicture0InfoListBorder5155)); ?>
	            padding: <?php echo ($TPicture0InfoListPadding5155); ?>;
	            margin: <?php echo ($TPicture0InfoListMargin5155); ?>;
	        }
		</style>
	    <div class="floor_picture0 display<?php echo ($TPicture0Show5155); ?> full-width<?php echo ($TPicture0FullWidth5155); ?>">
	    	<?php if(!empty($TPicture0TitleStyle5155)): ?><div class="component_title">
		            <h2 class="h2_<?php echo ($TPicture0TitleStyle5155); ?> ThemeBeforeColorBg"><a href="<?php echo (channelurl($TPicture0Channel5155)); ?>" yd-text="channel,<?php echo ($TPicture0Channel5155); ?>,ChannelName"><?php echo (channelname($TPicture0Channel5155)); ?></a></h2>
		            <p yd-text="channel,<?php echo ($TPicture0Channel5155); ?>,ChannelSContent"><?php echo (channelscontent($TPicture0Channel5155)); ?></p>
		        </div><?php endif; ?>
	        <div class="component_body">
	            <ul class="picture0 <?php echo ($TPicture0Style5155); ?>-picture0" yd-group="5155" yd-tab="列表设置" yd-content="info,<?php echo ($TPicture0Channel5155); ?>">
	                <?php $_labelid=$TPicture0Label5155; $_timeformat="Y-m-d"; $_keywords=""; $_orderby=""; $_field="";$_pagesize=0; $_provinceid=-1;$_cityid=-1;$_districtid=-1;$_townid=-1; $_suffix="..."; $_specialid=0; $_minprice=-1;$_maxprice=-1;$_attr_info=""; $_result=get_info($TPicture0Channel5155, $_specialid, $TPicture0Count5155, $_timeformat, 0, $_suffix, $_labelid, 0, $_keywords, $_orderby, $_minprice, $_maxprice, $_attr_info, -1, $_field, $_pagesize, $_provinceid, $_cityid, $_districtid, $_townid);if(is_array($_result)): $i = 0; $__LIST__ = $_result;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$f): $mod = ($i % 2 );++$i;?><li class="n<?php echo ($i); ?> column<?php echo ($TPicture0ColumnCount5155); ?>" yd-animation="<?php echo ($TPicture0AnimationType5155); ?>">
	                        <a class="ThemeColorParent <?php echo ($TRoundCorner); ?>-round-corner" href="<?php echo ($f["InfoUrl"]); ?>" target="_blank" yd-content="_info,<?php echo ($f["InfoID"]); ?>">
	                            <div class="InfoPicture"><img src="<?php echo (defaultpicture($f["InfoPicture"])); ?>" title="<?php echo ($f["InfoTitle"]); ?>" alt="<?php echo ($f["InfoTitle"]); ?>" /></div>
	                            <div class="InfoWrap">
	                                <div class="InfoTitle ThemeColorChild"><?php echo ($f["InfoTitle"]); ?></div>
	                                <div class="InfoSContent">
	                                    <?php if(!empty($f["InfoSContent"])): echo ($f["InfoSContent"]); else: echo (left(strip_tags($f["InfoContent"]),50)); endif; ?>
	                                </div>
	                            </div>
	                        </a>
	                    </li><?php endforeach; endif; else: echo "" ;endif; ?>
	            </ul>
	        </div>
	        <div class="component_bottom"></div>
	    </div><?php endif; ?>
</div>
<!--区块5155 结束--><!--区块5114 开始-->
<div id="n5114" class="component floor_page0_main" yd-add="1" yd-delete="1" yd-order="1" yd-group="5114" yd-content="_channel,<?php echo ($TPage0Channel5114); ?>,3">
	<?php if(!empty($TPage0Show5114)): ?><style type="text/css">
			#n5114 .ChannelWrap{
				animation-duration: <?php echo ($TPage0AnimationTime5114); ?>s;
				animation-fill-mode: both;
			}
			#n5114 .ChannelContent{
				max-width: <?php echo ($TPage0SubContentWidth5114); ?>px;
			}
			#n5114 .ChannelSContent{ 
				<?php echo (parsefont($TPage0TitleFont5114)); ?>
				max-width: <?php echo ($TPage0SubContentWidth5114); ?>px;
			}
			#n5114 .ChannelContent p{ 
				<?php echo (parsefont($TPage0SubContentFont5114)); ?>
			}
			#n5114 .ChannelContent i{
				background-color: <?php echo ($TPage0SubContentBgColor5114); ?>;
			}
			#n5114 .ChannelWrap{
				animation-duration: <?php echo ($TPage0AnimationTime5114); ?>s;
				animation-fill-mode: both;
			}
		</style>
		<div class="floor_page0 display<?php echo ($TPage0Show5114); ?>">
			<div class="component_body">
				<img class="BgPicture" src="<?php echo ($TPage0Picture5114); ?>" title="<?php echo (channelname($TPage0Channel5114)); ?>" alt="<?php echo (channelname($TPage0Channel5114)); ?>" />
				<div class="ChannelWrap" yd-animation="<?php echo ($TPage0AnimationType5114); ?>">
					<div class="ChannelSContent" yd-text="channel,<?php echo ($TPage0Channel5114); ?>,ChannelSContent"><?php echo (channelscontent($TPage0Channel5114)); ?></div>
					<div class="ChannelContent BgBackParent" yd-content="_channel,<?php echo ($TPage0Channel5114); ?>,3" yd-group="5114" yd-tab="标题设置"><i></i><p><?php echo (left(strip_tags(channelcontent($TPage0Channel5114)),$TPage0SubContentLeft5114)); ?></p></div>
					<div yd-slot="5114A"></div>
				</div>
			</div>
		</div><?php endif; ?>
</div>
<!--区块5114 结束--><!--区块5149 开始-->
<div id="n5149" class="component floor_article0_main" yd-add="1" yd-delete="1" yd-order="1" yd-group="5149">
	<?php if(!empty($TArticle0Show5149)): ?><style type="text/css">
			#n5149{
				<?php echo (parsebg($TArticle0Bg5149)); ?>
				padding:<?php echo ($TArticle0Padding5149); ?>px 0;
			}
			#n5149 .component_title h2 a{
				<?php echo (parsefont($TArticle0TitleFont5149)); ?>
			}
			#n5149 .component_title p{
				<?php echo (parsefont($TArticle0SubtitleFont5149)); ?>
			}
			#n5149 .InfoTitle{
				<?php echo (parsefont($TArticle0InfoTitleFont5149)); ?>
			}
			#n5149 .InfoSContent{
				<?php echo (parsefont($TArticle0InfoSContentFont5149)); ?>
			}
			#n5149 .InfoTime{
				<?php echo (parsefont($TArticle0InfoTimeFont5149)); ?>
			}
			#n5149 .TitleIcon{
				width: <?php echo ($TArticle0TitleIconSize5149); ?>px;
			}
			#n5149 li{
				animation-duration: <?php echo ($TArticle0AnimationTime5149); ?>s;
				animation-fill-mode: both;
				yd-previewable-class: column column<?php echo ($TArticle0ColumnCount5149); ?>;
			}
			#n5149 ul{
				yd-previewable-class: -article0 <?php echo ($TArticle0Style5149); ?>-article0;
			}
			#n5149 .thumb-article0 li .InfoPicture,#n5149 .thumbright-article0 li .InfoPicture{
				width: <?php echo ($TArticle0InfoPictureSize5149); ?>%;
			}
	        #n5149 .article0 li a{
	            <?php echo (parsebg($TArticle0InfoListBg5149)); ?>
	            <?php echo (parseborder($TArticle0InfoListBorder5149)); ?>
	            padding: <?php echo ($TArticle0InfoListPadding5149); ?>;
	            margin: <?php echo ($TArticle0InfoListMargin5149); ?>;
	        }
		</style>
		<div class="floor_article0 display<?php echo ($TArticle0Show5149); ?> full-width<?php echo ($TArticle0FullWidth5149); ?>">
			<?php if(!empty($TArticle0TitleStyle5149)): ?><div class="component_title">
					<h2 class="h2_<?php echo ($TArticle0TitleStyle5149); ?> ThemeBeforeColorBg"><a href="<?php echo (channelurl($TArticle0Channel5149)); ?>" yd-text="channel,<?php echo ($TArticle0Channel5149); ?>,ChannelName"><?php echo (channelname($TArticle0Channel5149)); ?></a></h2>
					<p yd-text="channel,<?php echo ($TArticle0Channel5149); ?>,ChannelSContent"><?php echo (channelscontent($TArticle0Channel5149)); ?></p>
				</div><?php endif; ?>
			<div class="component_body">
				<ul class="article0 <?php echo ($TArticle0Style5149); ?>-article0" yd-group="5149" yd-tab="列表设置" yd-content="info,<?php echo ($TArticle0Channel5149); ?>">
					<?php $_labelid=$TArticle0Label5149; $_timeformat="Y-m-d"; $_keywords=""; $_orderby=""; $_field="";$_pagesize=0; $_provinceid=-1;$_cityid=-1;$_districtid=-1;$_townid=-1; $_suffix="..."; $_specialid=0; $_minprice=-1;$_maxprice=-1;$_attr_info=""; $_result=get_info($TArticle0Channel5149, $_specialid, $TArticle0Count5149, $_timeformat, 0, $_suffix, $_labelid, 0, $_keywords, $_orderby, $_minprice, $_maxprice, $_attr_info, -1, $_field, $_pagesize, $_provinceid, $_cityid, $_districtid, $_townid);if(is_array($_result)): $i = 0; $__LIST__ = $_result;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$f): $mod = ($i % 2 );++$i;?><li class="n<?php echo ($mod); ?> column<?php echo ($TArticle0ColumnCount5149); ?>" yd-animation="<?php echo ($TArticle0AnimationType5149); ?>">
							<a href="<?php echo ($f["InfoUrl"]); ?>" target="_blank" title="<?php echo ($f["InfoTitle"]); ?>" class="<?php echo ($TRoundCorner); ?>-round-corner ThemeColorParent" yd-content="_info,<?php echo ($f["InfoID"]); ?>">
								<div class="InfoPicture">
									<img src="<?php echo (defaultpicture($f["InfoPicture"])); ?>" title="<?php echo ($f["InfoTitle"]); ?>" alt="<?php echo ($f["InfoTitle"]); ?>" />
								</div>
								<div class="InfoWrap">
									<div class="InfoTitle ThemeColorChild" yd-var="TArticle0InfoTitleFont5149">
										<?php if(($TArticle0Style5149) == "text"): ?><img class="TitleIcon" src="<?php echo ($TArticle0TitleIcon5149); ?>"/><?php endif; ?>
										<?php echo ($f["InfoTitle"]); ?>
									</div>
									<div class="InfoSContent" yd-var="TArticle0InfoSContentFont5149"><?php if(!empty($f["InfoSContent"])): echo ($f["InfoSContent"]); else: echo (left(strip_tags($f["InfoContent"]),80)); endif; ?></div>
									<div class="InfoTime ThemeColorChild ThemeColorBgChildAfter" yd-var="TArticle0InfoTimeFont5149"><?php echo ($f["InfoTime"]); ?></div>
								</div>
							</a>
						</li><?php endforeach; endif; else: echo "" ;endif; ?>
				</ul>
			</div>
			<div class="component_bottom"></div>
		</div><?php endif; ?>
</div>
<!--区块5149 结束--><!--区块5117 开始-->
<div id="n5117" class="component floor_other2_main" yd-add="1" yd-delete="1" yd-order="1" yd-group="5117" yd-content="_channel,<?php echo ($TOther2Channel5117); ?>">
	<?php if(!empty($TOther2Show5117)): ?><style type="text/css">
			#n5117{
				padding:<?php echo ($TOther2Padding5117); ?>px 0;
				<?php echo (parsebg($TOther2Bg5117)); ?>
			}
			#n5117 .component_title h2 {
				<?php echo (parsefont($TOther2TitleFont5117)); ?>
			}
			#n5117 .component_title p{
				<?php echo (parsefont($TOther2SubtitleFont5117)); ?>
			}
			#n5117 .AlbumTitle{
				<?php echo (parsefont($TOther2InfoTitleFont5117)); ?>
			}
			#n5117 .AlbumDescription{
				<?php echo (parsefont($TOther2InfoSContentFont5117)); ?>
			}
			#n5117 li{
				animation-duration: <?php echo ($TOther2AnimationTime5117); ?>s;
				animation-fill-mode: both;
			}
			#n5117 li .AlbumWrap{
				border-width:<?php echo ($TOther2BorderWidth5117); ?>px;
				border-style:solid;
				border-color:<?php echo ($TOther2BorderColor5117); ?>;
				padding:<?php echo ($TOther2ListPadding5117); ?>px;
			}
		</style>
		<div class="floor_other2 display<?php echo ($TOther2Show5117); ?> full-width<?php echo ($TOther2FullWidth5117); ?>">
			<?php if(!empty($TOther2TitleStyle5117)): ?><div class="component_title">
					<h2 class="h2_<?php echo ($TOther2TitleStyle5117); ?> ThemeBeforeColorBg" yd-text="channel,<?php echo ($TOther2Channel5117); ?>,ChannelName"><?php echo (channelname($TOther2Channel5117)); ?></h2>
					<p yd-text="channel,<?php echo ($TOther2Channel5117); ?>,ChannelSContent"><?php echo (channelscontent($TOther2Channel5117)); ?></p>
				</div><?php endif; ?>
			<div class="component_body <?php echo ($TOther2Style5117); ?>-container" yd-content="_channel,<?php echo ($TOther2Channel5117); ?>,4" yd-group="5117" yd-tab="列表设置">
				<ul class="other2 swiper-wrapper <?php echo ($TOther2Style5117); ?>-other2">
					<?php $_result=get_channelalbum($TOther2Channel5117,"ChannelAlbum");if(is_array($_result)): $i = 0; $__LIST__ = array_slice($_result,0,$TOther2Count5117,true);if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$ca): $mod = ($i % 2 );++$i;?><li class="swiper-slide <?php if(($TOther2Style5117) != "swiper"): ?>column<?php echo ($TOther2ColumnCount5117); endif; ?>" yd-animation="<?php echo ($TOther2AnimationType5117); ?>">
							<div class="AlbumWrap <?php echo ($TRoundCorner); ?>-round-corner">
								<img src="<?php echo ($ca["AlbumPicture"]); ?>"  alt="<?php echo ($ca["AlbumTitle"]); ?>" title="<?php echo ($ca["AlbumTitle"]); ?>" />
								<div class="AlbumTitle"><?php echo ($ca["AlbumTitle"]); ?></div>
								<?php if(!empty($ca["AlbumDescription"])): ?><div class="AlbumDescription"><?php echo ($ca["AlbumDescription"]); ?></div><?php endif; ?>
							</div>
						</li><?php endforeach; endif; else: echo "" ;endif; ?>
				</ul>
			</div>
			<div class="component_bottom"></div>
		</div>
		<script type="text/javascript">
			$(function(){
				var swiper = new Swiper('#n5117 .swiper-container',{
					autoplay: 2000,
					autoplayDisableOnInteraction: false,
					slidesPerView: 'auto',
					calculateHeight : true,
                    loop: true,
                    loopedSlides: <?php echo ($TOther2Count5117); ?>
				})
			})
		</script><?php endif; ?>
</div>
<!--区块5117 结束--><!--区块5119 开始-->
<div id="n5119" class="component floor_link4_main" yd-add="1" yd-delete="1" yd-content="link" yd-order="1" yd-group="5119">
	<?php if(!empty($TLink4Show5119)): ?><style type="text/css">
			#n5119{ 
				padding:<?php echo ($TLink4Padding5119); ?>px 0;
				<?php echo (parsebg($TLink4Bg5119)); ?>
			}
			#n5119 .linklist .link_name{ 
				<?php echo (parsefont($TLink4TitleFont5119)); ?>;
			}
			#n5119 .linklist li{
				animation-duration: <?php echo ($TLink4AnimationTime5119); ?>s;
				animation-fill-mode: both;
				margin-right:<?php echo ($TLink4Margin5119); ?>px;
				<?php echo (parsefont($TLink4TextLinkFont5119)); ?>
			}
			#n5119 .linklist li a{ 
				<?php echo (parsefont($TLink4TextLinkFont5119)); ?>
			}
			#n5119 .linklist li img{ 
				width:<?php echo ($TLink4Width5119); ?>px;
				height:<?php echo ($TLink4Height5119); ?>px;
			}
		</style>
		<div class="floor_link4 display<?php echo ($TLink4Show5119); ?> full-width<?php echo ($TLink4FullWidth5119); ?>">
			<div class="component_title"></div>
			<div class="component_body">
				<ul class="linklist">
					<?php if(!empty($TLink4TitleStyle5119)): ?><li class="link_name " yd-animation="<?php echo ($TLink4AnimationType5119); ?>"><?php echo ($TLink4Name5119); ?></li>
						<?php if(empty($TLink4Style5119)): if(!empty($TLink4Separator5119)): ?><li yd-animation="<?php echo ($TLink4AnimationType5119); ?>"><?php echo ($TLink4Separator5119); ?></li><?php endif; endif; endif; ?>
					<?php $_result=get_link(-1, -1);if(is_array($_result)): $lk = 0; $__LIST__ = $_result;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$l): $mod = ($lk % 2 );++$lk; if(!empty($TLink4Style5119)): if(!empty($l["LinkLogo"])): ?><li yd-animation="<?php echo ($TLink4AnimationType5119); ?>">
									<a href="<?php echo ($l["LinkUrl"]); ?>" target="_blank">
										<img src="<?php echo ($l["LinkLogo"]); ?>" title="<?php echo ($l["LinkName"]); ?>" alt="<?php echo ($l["LinkName"]); ?>" />
									</a>
								</li><?php endif; ?>
							<?php else: ?>
							<?php if(($lk) != "1"): if(!empty($TLink4Separator5119)): ?><li yd-animation="<?php echo ($TLink4AnimationType5119); ?>"><?php echo ($TLink4Separator5119); ?></li><?php endif; endif; ?>
							<li yd-animation="<?php echo ($TLink4AnimationType5119); ?>">
								<a class="ThemeColorHover" href="<?php echo ($l["LinkUrl"]); ?>" target="_blank"><?php echo ($l["LinkName"]); ?></a>
							</li><?php endif; endforeach; endif; else: echo "" ;endif; ?>
				</ul>
			</div>
			<div class="component_bottom"></div>
		</div><?php endif; ?>
</div>
<!--区块5119 结束-->

    <!--区块5078 开始-->
<div id="n5078" class="component floor_foot2_main" yd-add="1" yd-delete="1" yd-order="1" yd-group="5078">
	<style type="text/css">
		#n5078{ 
			<?php echo (parsebg($TFoot2Bg5078)); ?>
		}
		#n5078 .component_title,#n5078 .component_body,#n5078 .component_bottom{
			padding-top:<?php echo ($TFoot2Padding5078); ?>px;
		}
		#n5078 .component_body{
			padding-bottom:<?php echo ($TFoot2Padding5078); ?>px;
		}
		#n5078 .component_body:before,#n5078 .component_body:after{
			background-color:<?php echo ($TFoot2BorderColor5078); ?>;
		}
		#n5078 .floor_foot2{
			animation-duration: <?php echo ($TFoot2AnimationTime5078); ?>s;
			animation-fill-mode: both;
		}
		#n5078 .foot2_title { 
			<?php echo (parsefont($TFoot2TitleFont5078)); ?>
		}
		#n5078 .foot2_WeChat .LogoText{ 
			<?php echo (parsefont($TFoot2QrcodeTextFont5078)); ?>
		}
		#n5078 .foot2_contact_wrap p{ 
			<?php echo (parsefont($TFoot2ContactFont5078)); ?>
		}
		#n5078 .foot2_contact_wrap .Telephone{
			<?php echo (parsefont($TFoot2TelephoneFont5078)); ?>
		}
		#n5078 .foot2_navigation .channel1{ 
			<?php echo (parsefont($TFoot2Channel1Font5078)); ?>
		}
		#n5078 .foot2_Content img {
			width:<?php echo ($TFoot2LogoWidth5078); ?>px;
		}
		#n5078 .foot2_Content .foot2_right_text {
			<?php echo (parsefont($TFoot2LogoTextFont5078)); ?>
		}
		@media screen and (max-width: 768px) {
			#n5078 .component_bottom {
				padding-bottom:<?php echo ($TFoot2Padding5078); ?>px;
			}
		}
	</style>
	<div class="floor_foot2 full-width<?php echo ($TFoot2FullWidth5078); ?>" yd-animation="<?php echo ($TFoot2AnimationType5078); ?>">
		<div class="component_title" yd-group="5078" yd-tab="左侧栏设置">
			<?php if(!empty($TFoot2LeftTitle5078)): ?><div class="foot2_title"><?php echo ($TFoot2LeftTitle5078); ?></div><?php endif; ?>
			<div class="foot2_WeChat">
				<?php if(!empty($TFoot2Qrcode5078)): ?><img src="<?php echo ($TFoot2Qrcode5078); ?>" title="<?php echo ($TFoot2QrcodeText5078); ?>" alt="<?php echo ($TFoot2QrcodeText5078); ?>" /><span class="LogoText"><?php echo ($TFoot2QrcodeText5078); ?></span><?php endif; ?>
			</div>
			<div yd-slot="5078Afoot"></div>
		</div>
		<div class="component_body" yd-group="5078" yd-tab="中间栏设置">
			<?php if(!empty($TFoot2CenterTitle5078)): ?><div class="foot2_title"><?php echo ($TFoot2CenterTitle5078); ?></div><?php endif; ?>
			<div class="foot2_contact_wrap" yd-content="contact">
				<div class="Telephone"><?php echo ($Telephone); ?></div>
				<?php if(!empty($TFoot2ContactText5078)): ?><p><?php echo ($TFoot2ContactText5078); ?></p><?php endif; ?>
				<?php if(!empty($Contact)): ?><p><?php echo (L("Contact")); ?>：<?php echo ($Contact); ?></p><?php endif; ?>
				<?php if(!empty($Mobile)): ?><p><?php echo (L("Mobile")); ?>：<?php echo ($Mobile); ?></p><?php endif; ?>
				<?php if(!empty($Email)): ?><p><?php echo (L("Email")); ?>：<?php echo ($Email); ?></p><?php endif; ?>
				<?php if(!empty($Address)): ?><p><?php echo (L("Address")); ?>：<?php echo ($Address); ?></p><?php endif; ?>
			</div>
			<div yd-slot="5078Bfoot"><!--区块5079 开始-->
<div id="n5079" class="component floor_child5_main" yd-group="5079" yd-delete="1" yd-animation="<?php echo ($TChild5AnimationType5079); ?>">
	<style type="text/css">
		#n5079{
			animation-duration: <?php echo ($TChild5AnimationTime5079); ?>s;
			animation-fill-mode: both;
		}
		#n5079 .floor_child5{
			padding: <?php echo ($TChild5Padding5079); ?>;
		}
		#n5079 .Picture{
			width: <?php echo ($TChild5PictureSize5079); ?>px;
			height: <?php echo ($TChild5PictureSize5079); ?>px;
			padding: <?php echo ($TChild5PicturePadding5079); ?>px;
			<?php echo (parseborder($TChild5PictureBorder5079)); ?>
		}
		#n5079 .Picture:hover{
			background: <?php echo ($TChild5HoverBgColor5079); ?> !important;
		}
		#n5079 .SubPicture img{
			width: <?php echo ($TChild5SubPictureSize5079); ?>px;
		}
		#n5079 ul{
			text-align: <?php echo ($TChild5FloorAlign5079); ?>;
		}
		#n5079 li{
			margin: 0 <?php echo ($TChild5PictureMargin5079); ?>px;
		}
		#n5079 li .Title{
			<?php echo (parsefont($TChild5TitleFont5079)); ?>
			yd-previewable-class: align align<?php echo ($TChild5TitleAlign5079); ?>;
		}
	</style>
	<ul class="floor_child5">
		<?php $_myvalue=$TChild5DataList5079; $_myfield=1; $_result=get_mydata($_myvalue,$_myfield,0,"","");if(is_array($_result)): $i = 0; $__LIST__ = $_result;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$d): $mod = ($i % 2 );++$i;?><li yd-animation="<?php echo ($TChild5AnimationType5079); ?>">
				<a href="<?php echo ($d["LinkUrl"]); ?>" target="<?php echo ($d["LinkTarget"]); ?>">
					<img class="Picture" src="<?php echo ($d["Picture"]); ?>" alt="<?php echo ($d["Title"]); ?>" style="background-color: <?php echo ($d["SubTitle"]); ?>" title="<?php echo ($d["Title"]); ?>" alt="<?php echo ($d["Title"]); ?>" />
					<div class="Title align<?php echo ($TChild5TitleAlign5079); ?>"><?php echo ($d["Title"]); ?></div>
				</a>
				<?php if(!empty($d["SubPicture"])): ?><div class="SubPicture"><img src="<?php echo ($d["SubPicture"]); ?>" title="<?php echo ($d["Title"]); ?>" alt="<?php echo ($d["Title"]); ?>" /></div><?php endif; ?>
			</li><?php endforeach; endif; else: echo "" ;endif; ?>
	</ul>
</div>
<!--区块5079 结束--></div>
		</div>
		<div class="component_bottom" yd-group="5078" yd-tab="右侧栏设置">
			<?php if(!empty($TFoot2RightTitle5078)): ?><div class="foot2_title"><?php echo ($TFoot2RightTitle5078); ?></div><?php endif; ?>
			<ul class="foot2_navigation" yd-content="channel">
				<?php $_result=get_navigation(0,1,"$TFoot2Channel5078",1,-1,-1,"");if(is_array($_result)): $i = 0; $__LIST__ = $_result;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$c1): $mod = ($i % 2 );++$i;?><li>
						<a class="channel1 ThemeColorHover" href="<?php echo ($c1["ChannelUrl"]); ?>" target="<?php echo ($c1["ChannelTarget"]); ?>" ><?php echo ($c1["ChannelName"]); ?></a>
					</li><?php endforeach; endif; else: echo "" ;endif; ?>
			</ul>
			<div class="foot2_Content">
				<?php if(!empty($TFoot2Logo5078)): ?><img src="<?php echo ($TFoot2Logo5078); ?>" title="<?php echo ($c1["TFoot2LogoText5078"]); ?>" alt="<?php echo ($c1["TFoot2LogoText5078"]); ?>"/><?php endif; ?><div class="foot2_right_text"><?php echo (nl2br($TFoot2LogoText5078)); ?></div>
			</div>
		</div>
	</div>
</div>
<!--区块5078 结束--><!--区块5015 开始-->
<div id="n5015" class="component floor_support1_main" yd-add="1" yd-delete="1" yd-order="1" yd-group="5015">
	<?php if(!empty($TSupport1Show5015)): ?><style type="text/css">
			#n5015{ 
				<?php echo (parsebg($TSupport1Bg5015)); ?>
			}
			#n5015 .component_body {
				padding:<?php echo ($TSupport1Padding5015); ?>px 0;border-top:<?php echo ($TSupport1BorderHeight5015); ?>px solid <?php echo ($TSupport1BorderColor5015); ?>;
			}
			#n5015 .component_body .footer_text{ 
				text-align:<?php echo ($TSupport1ExtraTextPosition5015); ?>;
				<?php echo (parsefont($TSupport1ExtraTextFont5015)); ?>
			}
			#n5015 .WebInfo,#n5015 .WebInfo a{ 
				text-align:<?php echo ($TSupport1WebInfoStyle5015); ?>;
				<?php echo (parsefont($TSupport1WebInfoFont5015)); ?>
			}
			#n5015 .support1_text{
				<?php echo (parsefont($TSupport1ExtraTextFont5015)); ?>
				<?php echo ($TSupport1ExtraTextPosition5015); ?>
			}
		</style>
		<div class="floor_support1 full-width<?php echo ($TSupport1FullWidth5015); ?>">
			<div class="component_title"></div>
			<div class="component_body">
				<?php if(!empty($TSupport1ExtraText5015)): ?><div class="footer_text"><?php echo (nl2br($TSupport1ExtraText5015)); ?></div><?php endif; ?>
				<div class="WebInfo support1_<?php echo ($TSupport1WebInfoStyle5015); ?>" yd-content="basic">
					<span class="support1_left"><a  href="<?php echo ($WebUrl); ?>" target="_self"><?php echo ($Company); ?></a>&nbsp;<?php echo (L("Copyright")); ?>&nbsp;<?php echo ($WebICP); ?>&nbsp;<?php echo ($Stat); ?></span>
			        <?php if((stripos($_SERVER['HTTP_HOST'],'31')) == "0"): ?><span class="support1_right"><?php echo (L("TechnicalSupport")); ?>：<a href="<?php echo ($CompanyUrl); ?>" target="_blank"><?php echo (L("YouDianSoftware")); ?></a></span><?php endif; ?>
				</div>
			</div>
			<div class="component_bottom"></div>
		</div><?php endif; ?>
</div>
<!--区块5015 结束-->
<?php if(!empty($TShowToolbar)): ?><div class="tool_back"></div>
	<div id="tool" yd-group="2">
		<ul class="toollist" style="background-color: <?php echo ($TToolbarBgColor); ?>;">
            <?php if(!empty($TToolbarName1)): ?><li><a  href="<?php echo ChannelUrl($TToolbarChannel1);?>" ><img src="<?php echo ($TToolbarIcon1); ?>"><p><?php echo ($TToolbarName1); ?></p></a></li><?php endif; ?>
            <?php if(!empty($TToolbarName2)): ?><li><a  href="<?php echo ($TToolbarChannel2); ?>" ><img src="<?php echo ($TToolbarIcon2); ?>"><p><?php echo ($TToolbarName2); ?></p></a></li><?php endif; ?>
            <?php if(!empty($TToolbarName3)): ?><li><a  href="<?php echo ChannelUrl($TToolbarChannel3);?>" ><img src="<?php echo ($TToolbarIcon3); ?>"><p><?php echo ($TToolbarName3); ?></p></a></li><?php endif; ?>
			<?php if(!empty($TToolbarName4)): ?><li><a  href="<?php echo ChannelUrl($TToolbarChannel4);?>" ><img src="<?php echo ($TToolbarIcon4); ?>"><p><?php echo ($TToolbarName4); ?></p></a></li><?php endif; ?>
		</ul>
	</div><?php endif; ?>

<style>
/*----------------------通用样式----------------------*/ 

/*----------------------大屏幕 大桌面显示器 (≥1200px)----------------------*/
@media screen and (min-width: 1200px) {

}

/*----------------------小屏幕 平板 (≥700px并且≤1199px)----------------------*/
@media screen and (min-width: 700px) and (max-width: 1199px) {

}

/*----------------------超小屏幕 手机 (≤699px)----------------------*/
@media screen and (max-width: 699px) {

}
</style>

<!----------------------自定义脚本代码---------------------->
<script  type="text/javascript">
$(document).ready(function(){

});
</script><!--gotop start-->
    <style>
            #topcontrol .yd-gotop{
                 transition-duration: .2s;  text-align: center; cursor: pointer; background: #FFFFFF; 
                 width: 50px;  height: 60px;line-height: 60px;
                border-radius:3px; box-shadow: 0 2px 18px rgba(0,0,0,.1);
            }
            #topcontrol .yd-gotop:hover{ background: #F9F9F9; }
            #topcontrol .yd-gotop i{ font-size:30px; color:#0066CC; }
            #topcontrol .yd-gotop:hover i{ color:#0066CC; }
    </style>
    <script>
        scrolltotop.controlattrs={offsetx:12, offsety:120 };
        scrolltotop.controlHTML = '<div yd-content="gotop" class="yd-gotop"><i class="ydicon-gotop18"></i></div>';
        scrolltotop.anchorkeyword = '#yd-gotop';
        scrolltotop.title = "回顶部";
        scrolltotop.init();
    </script>
    <!--gotop end-->

		<!--在线客服start-->
		<link rel='stylesheet' type='text/css' href='<?php echo ($WebPublic); ?>online/style/common.css'/>
            <script type='text/javascript' src='<?php echo ($WebPublic); ?>online/jquery.online.js'></script>
		<style>
			.SonlineBox .openTrigger, .SonlineBox .titleBox{ background-color:#0066CC; }
			.SonlineBox .contentBox{ border:2px solid #0066CC;  }
		</style>
		<script type='text/javascript'>
		$(window).load(function(){
			$().Sonline({
				Position:'right', Top:320, Width:55, Style:3, Effect:false, 
				DefaultsOpen:true, Tel:'1', Title:'在线客服',
				FooterText:"<img alt='微信扫一扫 关注我们' src='/Upload/public/kefu_qrcode.jpg' style='width: 130px; height: 130px;margin-top:8px;' title='微信扫一扫 关注微信公众号' /><br /><b style='color: rgb(255, 0, 0);'>wangzhan</b><br /><strong>扫一扫，关注微信公众号</strong>", Website:'__ROOT__',
				IconColor: '#FFFFFF', ThemeColor: '#0066CC',
				Qqlist:'/Upload/public/kefu_qrcode.jpg|微信客服|9,010-12340000|联系电话|8,|测试|10,123456|业务咨询|1'
			});
		});
		</script>
		<!--在线客服end-->
		


</body>
</html>