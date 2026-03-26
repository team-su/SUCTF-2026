<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <?php echo ($XUaCompatible); ?>
    <title></title>
    <link href="<?php echo ($Css); ?>style.css" rel="stylesheet" type="text/css" />
	<link href="<?php echo ($Css); ?>font.css" rel="stylesheet" type="text/css" />
	<style>
		body{ font-family: Microsoft Yahei; }
		[class*='ydicon']{  }
		.ydicon-right{ position: relative; }
		.ydicon-right:before{ 
			font-family: 'ydicon';
			position: absolute; width: 1em; height: 1em; line-height: 1; 
			right: 0; top: 0; bottom: 0; right: 3px; margin: auto; font-size: 17px; 
		}
        #topmenu{ text-align:left;padding:2px;}
        #topmenu a.current{
        	color:#fff; background:<?php echo ($AdminThemeColor); ?>;
        }
        #topmenu a { color:#000; text-decoration: none; padding:2px 3px; border-radius: 3px; }
        #topmenu a:hover {
        	text-decoration: none; color:#fff; background: <?php echo ($AdminThemeColor); ?>;
        }
        #topmenu a.addinfo{ background: none; text-decoration: none; padding: 1px 0 1px 3px;}
        #topmenu a.addinfo:hover{ background: none; text-decoration: none;  }
        #d1{ font-weight:bold }
        #topmenu .single{color:#F30;}
        #topmenu .link{color:#60F;}
        #topmenu .feedback{
        	color:<?php echo ($AdminThemeColor); ?>;
        }
        .cc{ background-color:#9BB055; color:#FFFFFF;}
        .tree{ cursor:pointer;}

		#sidebar li a:hover,#sidebar li a.active{ 
			color: #FFF; background: <?php echo ($AdminThemeColor); ?>;
		}
		
		<?php if(!empty($ChannelTreeWidth)): ?>#sidebar.sidebar3{
				width:<?php echo ($ChannelTreeWidth); ?>px;
			}<?php endif; ?>
    </style>
</head>
<body id="sidebar_page">
<div class="wrap">
    <div class="cotainer">
        <div id="sidebar" class="sidebar<?php echo ($_GET['MenuTopID']); ?>">
        <div class="con">
            <?php if(is_array($MenuGroup)): $i = 0; $__LIST__ = $MenuGroup;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$mg): $mod = ($i % 2 );++$i;?><div class="item">
                <h2><?php echo ($mg["MenuGroupName"]); ?><span class='close' style="display: none;">收起</span></h2>
                <ul  <?php if(($mg["MenuGroupID"]) == "25"): ?>id="ScrollWx"<?php endif; ?>>
                <?php if(($mg["MenuGroupID"]) == "3"): ?><div id="ScrollChannel" style="text-align:left;overflow:hidden;">
                    <?php if(is_array($Channel)): $i = 0; $__LIST__ = $Channel;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$c): $mod = ($i % 2 );++$i; if(($c["IsEnable"]) == "1"): if(!in_array(($c["ChannelID"]), explode(',',"6,7,10,11"))): ?><div id="topmenu" style="text-indent:<?php echo ($c['ChannelDepth']*12-12); ?>px; " class="parent<?php echo ($c["Parent"]); ?>"  flag="menu"  cid="<?php echo ($c["ChannelID"]); ?>" haschild="<?php echo ($c["HasChild"]); ?>">
                                <?php if(($c["HasChild"]) == "1"): ?><img src="<?php echo ($Images); ?>c<?php echo ($c["HasChild"]); ?>_open.gif" align="absmiddle" status="open" class="tree" onclick="ToggleTree(this, <?php echo ($c["ChannelID"]); ?>)" title="展开" />
                                <?php else: ?>
                                    <img src="<?php echo ($Images); ?>c_line.gif" align="absmiddle" /><?php endif; ?>
                                <?php switch($c["ChannelModelID"]): case "32": ?><a href='<?php echo ($Group); ?>/Channel/modify/ChannelID/<?php echo ($c["ChannelID"]); ?>' cid="c<?php echo ($c["ChannelID"]); ?>"  id="d<?php echo ($c["ChannelDepth"]); ?>" target='main' class="single" ><?php echo ($c["ChannelName"]); ?></a><?php break;?>
                                    <?php case "37": ?><a href='<?php echo ($Group); ?>/Info/Feedback/ChannelID/<?php echo ($c["ChannelID"]); ?>' cid="c<?php echo ($c["ChannelID"]); ?>"  id="d<?php echo ($c["ChannelDepth"]); ?>" target='main' class="feedback"><?php echo ($c["ChannelName"]); ?></a><?php break;?>
                                    <?php case "33": if(($c["ChannelID"] == 5) or ($c["ChannelID"] == 9)): ?><a href='<?php echo ($Group); ?>/GuestBook/index//ChannelID/<?php echo ($c["ChannelID"]); ?>' cid="c<?php echo ($c["ChannelID"]); ?>"  id="d<?php echo ($c["ChannelDepth"]); ?>" target='main' class="link"><?php echo ($c["ChannelName"]); ?></a>
                                        <?php elseif(($c["ChannelID"] == 4) or ($c["ChannelID"] == 8)): ?>
                                            <a href='<?php echo ($Group); ?>/job/index/ChannelID/<?php echo ($c["ChannelID"]); ?>' cid="c<?php echo ($c["ChannelID"]); ?>"  id="d<?php echo ($c["ChannelDepth"]); ?>" target='main' class="link"><?php echo ($c["ChannelName"]); ?></a>
                                        <?php else: ?>
                                            <a href='<?php echo ($Group); ?>/Channel/modify/ChannelID/<?php echo ($c["ChannelID"]); ?>' cid="c<?php echo ($c["ChannelID"]); ?>"  id="d<?php echo ($c["ChannelDepth"]); ?>" target='main' class="link"><?php echo ($c["ChannelName"]); ?></a><?php endif; break;?>
                                    <?php default: ?><a href='<?php echo ($Group); ?>/Info/Index/ChannelID/<?php echo ($c["ChannelID"]); ?>'  cid="c<?php echo ($c["ChannelID"]); ?>" id="d<?php echo ($c["ChannelDepth"]); ?>" target='main'><?php echo ($c["ChannelName"]); ?></a>
                                    <?php if(($c["HasChild"]) == "0"): ?><a href='<?php echo ($Group); ?>/Info/Add/ChannelID/<?php echo ($c["ChannelID"]); ?>' cid="c<?php echo ($c["ChannelID"]); ?>" target='main' class="addinfo"><img src="<?php echo ($Images); ?>addinfo.png" align="absmiddle" title="添加内容" alt="添加内容" /></a><?php endif; endswitch;?>
                            </div><?php endif; endif; endforeach; endif; else: echo "" ;endif; ?>
                    </div><?php endif; ?>
                
                <?php if(is_array($Menu)): $i = 0; $__LIST__ = $Menu;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$m): $mod = ($i % 2 );++$i; if(($m["MenuGroupID"]) == $mg["MenuGroupID"]): switch($m["MenuType"]): case "1": break;?>
                                <?php case "2": break;?>
                                <?php case "3": break;?>
								<?php case "4": ?><li menuid="<?php echo ($m["MenuID"]); ?>"><a href="<?php echo ($WebInstallDir); echo ($m["MenuContent"]); ?>"  target="_blank"><?php echo ($m["MenuName"]); ?></a></li><?php break;?>
                                <?php case "0": ?>
                                <?php default: ?>
									<li menuid="<?php echo ($m["MenuID"]); ?>"><a href="<?php echo ($Group); ?>/<?php echo ($m["MenuContent"]); ?>"  target="main"><?php echo ($m["MenuName"]); ?></a></li><?php endswitch; endif; endforeach; endif; else: echo "" ;endif; ?>
                </ul>
                </div><?php endforeach; endif; else: echo "" ;endif; ?>
        </div><!--/ .con-->
        </div><!--/ sidebar-->
    </div>
</div>
</body>
</html>
<script type="text/javascript" src="<?php echo ($WebPublic); ?>jquery/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo ($Js); ?>common.js"></script>
<script type="text/javascript" src="<?php echo ($WebPublic); ?>jquery/jscroll.js"></script>
<script type='text/javascript'>
$(document).ready(function(){
	$(document).keydown(function(e) {
		window.parent.frames['main'].keyHandler(e);
	});

	$(window).resize(function() {
		var h = $(window).height();
		ScrollChannel(h);
		ScrollWx(h);
	});

	$('#sidebar .con h2 span').bind("click",function(){
		if(this.className == "close"){
			this.className = "open";
			$(this.parentNode.parentNode).find("ul").hide()
		}else{
			this.className = "close";
			$(this.parentNode.parentNode).find("ul").show()
		}
	});
	
	//左侧菜单点击
	$('ul li a').bind("click",function(){
		$('ul  li  a').each(function(i){
			$(this).removeClass("active ydicon-right");
		});
		//去除树形菜单选中
		$("a[id^=d]").removeClass("current");
		$(this).blur();
		$(this).addClass("active ydicon-right");
	});
	
	$(".addinfo").click(function(){
			var cid = $(this).attr("cid");
			$("a[id^=d]").removeClass("current");
			$("a[cid="+cid+"]").addClass("current");
	});
	
	$("a[id^=d]").click(function() {
		$("a[id^=d]").removeClass("current");
		$('ul li a').removeClass("active");
		$(this).addClass("current");
	});
	
	//页面初始化
	pageInit();
	function pageInit(){
		updateStatus();
		var h = $(window).height();
		ScrollChannel(h);
		ScrollWx(h);
	}
});

 function ScrollChannel(h){
			$('#ScrollChannel').css({height:h - 140});
			$('#ScrollChannel').jscroll({
				W:"3px",
				BgUrl:"",//设置滚动条背景图片地址
				Bg:"#e5e5e5",//设置滚动条背景图片position,颜色等
				Bar:{  Pos:"up",//设置滚动条初始化位置在底部
					   Bd:{Out:"#ccc",Hover:"#ccc"},//设置滚动滚轴边框颜色：鼠标离开(默认)，经过
					   Bg:{Out:"#ccc",Hover:"#aaa",Focus:"#aaa"}
				}, //设置滚动条滚轴背景：鼠标离开(默认)，经过，点击
				Btn:{  btn: false, //是否显示上下按钮 false为不显示
					   uBg:{Out:"#ccc",Hover:"#ccc",Focus:"#ccc"},//设置上按钮背景：鼠标离开(默认)，经过，点击
					   dBg:{Out:"#ccc",Hover:"#ccc",Focus:"#ccc"}
				}  //设置下按钮背景：鼠标离开(默认)，经过，点击
			});
	}
	
	function ScrollWx(h){
		$("#ScrollWx").css({height:h - 326});
		$("#ScrollWx").jscroll({
				W:"3px",
				BgUrl:"",//设置滚动条背景图片地址
				Bg:"#e5e5e5",//设置滚动条背景图片position,颜色等
				Bar:{  Pos:"up",//设置滚动条初始化位置在底部
					   Bd:{Out:"#ccc",Hover:"#ccc"},//设置滚动滚轴边框颜色：鼠标离开(默认)，经过
					   Bg:{Out:"#ccc",Hover:"#aaa",Focus:"#aaa"}
				}, //设置滚动条滚轴背景：鼠标离开(默认)，经过，点击
				Btn:{  btn: false, //是否显示上下按钮 false为不显示
					   uBg:{Out:"#ccc",Hover:"#ccc",Focus:"#ccc"},//设置上按钮背景：鼠标离开(默认)，经过，点击
					   dBg:{Out:"#ccc",Hover:"#ccc",Focus:"#ccc"}
				}  //设置下按钮背景：鼠标离开(默认)，经过，点击
		});
  }

//展开/收缩子频道
function ToggleTree(obj, cid){
	var status = $(obj).attr("status");
	if(status == "open"){
		$(obj).attr("src", "<?php echo ($Images); ?>c1_close.gif");
		$(obj).attr("status", "close");
		$(obj).attr("title", "展开");
		closeChannel(cid);
	}else{
		$(obj).attr("src", "<?php echo ($Images); ?>c1_open.gif");
		$(obj).attr("status", "open");
		$(obj).attr("title", "收缩");
		openChannel(cid);
	}
	saveStatus();
	ScrollChannel( $(window).height() );
}

//收缩
function closeChannel(cid){
	$(".parent"+cid).each(function() {
		$(this).hide();
		if( $(this).attr("haschild") == 1 && $(this).find(".tree").attr("status") == "open"  ){
			closeChannel( $(this).attr("cid") );
		}
	});
}

//展开
function openChannel(cid){
	$(".parent"+cid).each(function() {
		$(this).show();
		if( $(this).attr("haschild") == 1 && $(this).find(".tree").attr("status") == "open" ){
			openChannel( $(this).attr("cid") );
		}
	});
}

//保存树形菜单状态
function saveStatus(){
	var list = [];
	$("div[flag='menu']").each(function() {
		if( $(this).attr("haschild") == 1 && $(this).find(".tree").attr("status") == "close"  ){
			list.push( $(this).attr("cid") );
		}
	});
	if( list.length > 0 ){
		var all = list.join(",");
		$.cookie("<?php echo (C("COOKIE_PREFIX")); ?>TreeStatus", all, {path: '/', expiress:30} );　//expiress 有效日期，单位：天
	}
}

//同步更新状态
function updateStatus(){
	var status = $.cookie("<?php echo (C("COOKIE_PREFIX")); ?>TreeStatus");
	if( status ){
		status = status.split(",");
		if( status.length > 0 ){
			for(var i = 0; i < status.length; i++){
				var obj = $("div[cid="+status[i]+"]").find(".tree");
				if(obj.length > 0){
					obj.attr("src", "<?php echo ($Images); ?>c1_close.gif");
					obj.attr("status", "close");
					obj.attr("title", "展开");
					closeChannel( status[i] );
				}
			}
		}
	}
}
</script>