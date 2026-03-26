<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>管理中心-<?php echo (htmlspecialchars($WebName)); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="renderer" content="webkit">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
<meta name="generator" content="YoudianCMS" data-variable="http://www.youdiancms.com" />
<meta name="referrer" content="no-referrer">
<?php echo ($XUaCompatible); ?>
<title></title>
<link href="<?php echo ($Css); ?>style.css" rel="stylesheet" type="text/css" />
<link href="<?php echo ($Css); ?>font.css" rel="stylesheet" type="text/css" />
<style>
	[class*='ydicon']:before{ font-family: 'ydicon'; }

	#btnSubmit{ 
		background: <?php echo ($AdminThemeColor); ?>; 
	}
	.toolbar a,.toolbar #btnSeek{
		line-height: 1.42857143em; padding: 6px 12px; border-radius: 4px; color:#FFF; display: inline-block; border: 1px solid #ccc; background: #FFF; color: #212529;
		vertical-align: top; outline: none;
	}
	.toolbar a:hover{ background: #eee; }
	.toolbar a:active,.toolbar #btnSeek:active{ box-shadow: inset 0 3px 5px rgba(0,0,0,.125); }
	.toolbar a:before{ padding-right: 3px; font-weight: normal; font-family: "ydicon"; }
	.toolbar #btnSaveAll,.toolbar #btnSeek{
		color: #FFF;
		border: 1px solid <?php echo ($AdminThemeColor); ?>;
		background: <?php echo ($AdminThemeColor); ?> !important;
	}
	.toolbar #btnSaveAll:hover,.toolbar #btnSeek:hover{ opacity: .9; }
	.toolbar a#del:hover,.toolbar a#btnCache:hover{ background: #ec0000 !important; }
	.toolbar a#del,.toolbar a#btnCache{ background: red !important; color: #FFF; border-color: red; }
	.toolbar a#btnBack:before{ content: "\e655"; }
	.toolbar a#btnSave:before{ content: "\e678"; }
	.toolbar a#btnSaveAll:before{ content: "\e612"; }
	.toolbar a#btnAdd:before{ content: "\e612"; }
	.toolbar a#del:before { content: "\e651"; }
	.toolbar a#sortall:before{ content: "\e681"; }
	.toolbar a#btnConfig:before{ content: "\e67c"; }
	.toolbar a#btnCheck:before{ content: "\e625"; }
	.toolbar a#btnUnCheck:before{ content: "\e624"; }
	.toolbar a#btnCache:before{ content: "\e613"; }
	.toolbar a#btnLock:before{ content: "\e6a3"; }
	.toolbar a#btnUnlock:before{ content: "\e6a4"; }
	.toolbar a#btnPwd:before{ content: "\e63e"; }
	.toolbar a#btnParent:before{ content: "\e611"; }
	.toolbar a#refresh:before{ content: "\e682"; }
	.toolbar a#btnSeek:before{ content: "\e6bc"; }
	.toolbar a#btnMove:before{ content: "\e774"; }
	.toolbar a#btnCopy:before{ content: "\e601"; }

	.tab_menu{ 
		border-bottom:2px solid <?php echo ($AdminThemeColor); ?>; 
	}
	.tab_menu li.current{ 
		background: <?php echo ($AdminThemeColor); ?>; 
	}
	.submenulist li a:hover, .submenulist li a.current{ 
		background:<?php echo ($AdminThemeColor); ?>; 
	}
	.ui-dialog-footer button.ui-dialog-autofocus{
	    background-color: <?php echo ($AdminThemeColor); ?>;
		border-color: <?php echo ($AdminThemeColor); ?>;
	}

	/*文件管理器主题响应*/
	.yd-fm-upload-btn{
		background: <?php echo ($AdminThemeColor); ?>;
	}
	.yd-fm-set-btn.on{
		background: <?php echo ($AdminThemeColor); ?>;
	}
	.yd-fm-set-btn.on:hover{
		background: <?php echo ($AdminThemeColor); ?> !important;
	}
	.yd-fm-dir-container .diritem .item.on:after{
		border: 2px solid <?php echo ($AdminThemeColor); ?>;
	}
	.yd-fm-dir-container .diritem .item:before{
		color: <?php echo ($AdminThemeColor); ?>;
	}
	.yd-fm-fileitem.on{
		border-color: <?php echo ($AdminThemeColor); ?> !important;
	}
	.yd-fm-fileitem.on:before{
		color: <?php echo ($AdminThemeColor); ?>;
	}
	.yd-fm-filter-head i:active{
		border: 2px solid <?php echo ($AdminThemeColor); ?>;
	}
	.yd-switch.on{
		background: <?php echo ($AdminThemeColor); ?>;
	}
	.yd-radio.on:before{
		background: <?php echo ($AdminThemeColor); ?>;
	}
</style>
<script type="text/javascript" src="<?php echo ($WebPublic); ?>jquery/jquery.min.js"></script>

<script type="text/javascript" src="<?php echo ($Js); ?>common.js"></script>
<script type="text/javascript" src="<?php echo ($Js); ?>manager.js<?php echo ($NoCache); ?>"></script>
<script>
	yd.setOptions({
		Debug: "<?php echo ($AppDebug); ?>"
	});
	localStorage.setItem("UploadDirType", "<?php echo ($UploadDirType); ?>");
	FileManager.setConfig({
		Group: "__GROUP__",
		Js: "<?php echo ($Js); ?>"
	})
	
	function GoBack(url){
		if( url == undefined ) url = "<?php echo ($Url); ?>index";
		window.location.href = url;
	}
	
	function keyHandler(e){
		//Ctrl+Del清除系统缓存
		if(e.ctrlKey && e.keyCode == 46){
			ClearSystemCache( "__GROUP__/public/clearCache/Action/systemcache" );
		}else if(e.shiftKey && e.keyCode == 46){  //Shift+Del一键清除全部Html静态缓存
			ClearAllHtmlCache("__GROUP__/public/clearCache/Action/allhtmlcache");
		}else if(e.ctrlKey && e.keyCode == 13){ //Ctrl+Enter提交
			console.log("Ctrl+Enter提交");
			if( $("#btnSubmit").length > 0 && $("#NoCtrlEnter").val() != 1 ){
				$("#myaction").val("SaveConfig"); //兼容缓存管理
				$("form:first").attr('action','<?php echo ($Action); ?>');
				$("form:first").submit();
			}
		}else if(e.ctrlKey && e.keyCode == 37){ //ctrl+<-- 搜狗不支持esc
			if( $(".GoBack").length > 0 ){  //存在返回按钮才运行
				GoBack();  //firefox 无效
			}
		}
	}
	
	//obj对象
	function toggleStatus(obj, id, tableName, status0Name, status0Value, status0Color, status1Name, status1Value, status1Color, fieldName){
		if(checkSafeQuestion()) return;
		if( status0Name == undefined) status0Name ="禁用";
		if( status0Value == undefined) status0Value =0;
		if( status0Color == undefined) status0Color ="#F00";
		
		if( status1Name == undefined) status1Name ="启用";
		if( status1Value == undefined) status1Value =1;
		if( status1Color == undefined) status1Color ="#000";
		if( fieldName == undefined) fieldName ="IsEnable";
		
		if( $(obj).text() == status1Name ){
			statusValue = status0Value;  statusName = status0Name;  color = status0Color;
		}else{
			statusValue = status1Value;  statusName = status1Name;  color = status1Color;
		}
		$(obj).text( statusName );
		$(obj).css("color", color);
		var p = {id:id, FieldValue:statusValue, TableName:tableName, FieldName: fieldName};
		$.get("<?php echo ($Url); ?>toggleStatus", p, null, 'json');
	}
	
	function DeleteTip(key){
		if(key){
			key = '<b>【'+key+'】</b>';
		}else{
			key = "";
		}
		var des = '<div id="icon_delete">确定删除'+key+'吗？删除后无法恢复！</div>';
		return des;
	}
	
	function checkSafeQuestion(){
		var enable = "<?php echo ($SafeAnswerEnable); ?>";
		if(enable ==1){
			//是否正确回答过问题
			var IsSafeAnswer = window.localStorage.getItem("IsSafeAnswer") || 0;
			if(IsSafeAnswer == 0) {
				showSafeQuestion();
				return true;
			}
		}
		return false;
	}
	
	function showSafeQuestion(){
		var content = '<div style="overflow:hidden; width:270px; padding: 10px 5px;">';
		content += '<div class="red" style="padding-bottom:8px; text-align:left;font-weight:bold;"><?php echo ($SafeQuestion); ?></div>';
		content += '<div><input  class="textinput" autocomplete="off" style="width:96%;padding: 8px 6px;" type="text" id="MySafeAnswer" placeholder="请输入问题答案" value="" />';
		content += '</div></div>';
		var d = dialog({
			content: content,
			title:"二次安全验证", 
			quickClose: false, 
			okValue:"确定",
			ok:function(){
				//不用用$Group，系统某些地方会覆盖全局Group
				var url = "__GROUP__/public/answerSafeQuestion";
				var params = {
					"SafeAnswer": $("#MySafeAnswer").val()
				};
				$.post(url, params, function(res){
					if(res.status==1){
						window.localStorage.setItem("IsSafeAnswer", 1);
						SucceedBox(res.info);
						d.close().remove();
					}else{
						window.localStorage.setItem("IsSafeAnswer", 0);
						ErrorBox(res.info);
					}
				}, "json");
				return false;
			}, 
			cancelValue:"取消",
			cancel:function(){
				d.close().remove();
			},
			id:'dlgSafeAnswer', 
			padding:5
		}).show();
	}
</script>
    <style>
        html{ height: 100%; }
		body{ height: 100%; padding:0; margin:0; background: #fafafa; font-family: Microsoft Yahei; }
        table,td,th, body,dt,dd,dl{ margin:0; padding:0; border:none;}
		iframe{ width:100%; height:100%; }
        i{ font-style: normal; }

        #header{ height: 50px; line-height: 50px; padding: 0 15px; margin-bottom: 10px; background: #f8f9fa; box-shadow: 0 2px 10px 0 rgba(0,0,0,.1); }
        #header .left{ font-size:18px; font-weight:bold; color:#666; }
        #header .left .debug{ background: #f30; color: #FFF; border-radius: 3px; padding: 3px 8px; font-size: 12px; text-decoration:none;}
        #header .left .debug:hover{ opacity:0.8;}
		#header .left .WebName a{ color: #444; text-decoration:none; }
		#header .left .WebName a:hover{ color: #f30; }
        #header .right{ position: absolute; right: 0; top: 0; }
        .headerlist{ margin: 0; padding: 6px 0 0; }
        .headerlist li{ position: relative; list-style: none; float: left; width: 105px; vertical-align: middle; font-size: 14px; }
        .headerlist li a{ text-decoration:none; cursor:pointer; color: #212529; }
        .headerlist .title{ display: block; height: 38px; line-height: 38px; padding: 0 12px; cursor: pointer; border-radius: 6px; transition-duration: .4s; }
        .headerlist .title i{
            float: left; width: 20px; height: 20px; margin-top: 8px; margin-right: 2px; background-image: url(<?php echo ($Images); ?>/headericon.gif);
        }
        .headerlist .ydimg-home{ background-position: center -45px; }
        .headerlist .ydimg-cache{ background-position: center -93px; }
        .headerlist .ydimg-support{ background-position: center -21px; }
        .headerlist .ydimg-lang{ background-position: center -67px; }
        .headerlist .ydimg-theme{ background-position: center 1px; }
        .headerlist .ydicon-update{ background-position: center -138px; }
        
		.headerlist .ydicon-zhuangxiu{ background-position: center -164px; } 
		.headerlist .ydicon-zhuangxiu:before { content: ""; }

        .headerlist .title i.ydimg-bottom{ position: absolute; right: 5px; top: 0; background-position: center -116px;  }
        .headerlist .title .MemberAvatar{ position: absolute; top: 0; left: 10px; width: 35px; height: 35px; line-height: 40px; border-radius: 50%; font-size: 35px; color: #666; }
        .headerlist .title:hover{ background: #e2e6ea; }
        .headerlist li.admin .title{ line-height: 18px; }
        .headerlist li.admin .avatar{ height: 35px; float: left; margin-top: 1px; border-radius:35px; margin-right: 5px;}
        .headerlist li.admin .AdminGroupName{ font-size: 12px; color: #888; padding-right: 10px; }
        .headerlist .list{ 
			display: none; position: absolute; width: 150px; top: 100%; right: 0; 
			background: #FFF; border: 1px solid #ddd; border-radius: 6px; 
		}
        .headerlist .list a{ display: block; line-height: 1; padding: 15px 20px; white-space: nowrap; 
		transition-duration: .4s; border-bottom:1px solid #ddd;}
        .headerlist .list a:hover{ background: #e2e6ea; }
		.add-language{ 
			background: <?php echo ($AdminThemeColor); ?>;
			color:fff; border-radius: 5px; padding: 6px 22px;
		}
		.add-language:hover{ opacity:.8;}

		#language{ width: 160px; }
        #language a{ background-position: 120px center; padding: 0 8px;height: 43px; line-height: 43px;}
		 #language a.active{
            background: url(<?php echo ($Images); ?>lactive.gif) 130px center no-repeat;
			color: red; font-weight:bold;
		}
		#language a img{ width:32px; height: 20px; vertical-align:middle; border-radius:3px;}

        /*底部区域*/
        #myfooter{text-align:right;padding:8px 15px;color:#888;font-family:Verdana;font-size:12px;}
        #myfooter .left{ width:auto;float:left;text-align:left; }
        #myfooter .right{ width:auto; float:right;text-align:right;}
        #myfooter a { color:inherit; text-decoration:none; cursor:pointer;}
        #myfooter a:hover{ text-decoration:none; color:#f30;}

        #top-wrap{position: fixed; width: 80px; left: 0; top: 0; bottom: 0;}
        #header{ position: fixed; top: 0; left: 80px; right: 0; z-index: 9; }
        #menu-wrap{ position: fixed; width: 165px; top: 60px; left: 80px; bottom: 0; }
        #main-wrap{ position: fixed; left: 245px; top: 60px; bottom: 32px; right: 0; }
        #myfooter{ position: fixed; bottom: 0; right: 0; left: 245px; }
    </style>
</head>
<body>
	<div id="top-wrap"><iframe src="<?php echo ($Url); ?>AdminTop" name="header" target="menu" scrolling="no" frameborder="0"></iframe></div>
    <div id="header">
        <div class="con">
            <div class="left">
                欢迎登录<span class="WebName"><a href="<?php echo ($Group); ?>/Config/basic" target="main"><?php echo (htmlspecialchars($WebName)); ?></a></span>管理后台！
                <?php if(($AppDebug) == "1"): ?><a class="debug" title="网站正式发布后，请关闭调试模式！" href="<?php echo ($Group); ?>/Config/basic" target="main">调试模式</a><?php endif; ?>
            </div>
            <div class="right">
                <ul class="headerlist">
                    <li><a class="title" href="<?php echo ($WebInstallDir); ?>" target="_blank"><i class="ydimg-home"></i>网站首页</a></li>
					<li><a class="title" href="<?php echo ($Group); ?>/Decoration/index" target="_blank"><i class="ydicon-zhuangxiu"></i>模板装修</a></li>
					
                    <li><a class="title" onclick="checkUpgrade(false)" target="_self"><i class="ydicon-update"></i>检查更新</a></li>
					
                    <li><a class="title" href="<?php echo ($Group); ?>/Public/ClearCache" target="main"><i class="ydimg-cache"></i>缓存管理</a></li>
                    <?php if(empty($IsOem)): ?><li style="width: 120px;">
                        <a class="title"><i class="ydimg-support"></i>技术支持<i class="ydimg-bottom"></i></a>
                        <div class="list">
                            <a href="http://use.youdiancms.com" target="_blank">帮助手册</a>
                            <a href="http://tag.youdiancms.com" target="_blank">二次开发手册</a>
							<a href="http://zx.youdiancms.com" target="_blank">高级装修手册</a>
							<a href="http://dd.youdiancms.com" target="_blank">多端小程序手册</a>
                            <a href="http://www.youdiancms.com" target="_blank">官方网站</a>
                        </div>
                    </li><?php endif; ?>
					<?php if(!empty($Language)): ?><li style="width: 120px;">
                        <a class="title" href="<?php echo ($Group); ?>/Config/language" target="main">
							<i class="ydimg-lang"></i>语言切换<i class="ydimg-bottom"></i>
						</a>
                        <div id="language" class="list">
                            <?php if(is_array($Language)): $i = 0; $__LIST__ = $Language;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$l): $mod = ($i % 2 );++$i;?><a <?php if(($l["LanguageMark"]) == $AdminLanguageMark): ?>class="active"<?php endif; ?>  onclick="ChangeLng(this,'<?php echo ($l["LanguageMark"]); ?>','<?php echo ($Url); ?>','lng<?php echo ($l["LanguageID"]); ?>')" target="main">
								<img src="<?php echo ($WebPublic); ?>Images/mark/<?php echo ($l["LanguageMark"]); ?>.png" />
								<?php echo ($l["LanguageName"]); ?></a><?php endforeach; endif; else: echo "" ;endif; ?>
							<a href="<?php echo ($Group); ?>/Language/index" target="main"><span class="add-language">+ 添加网站语言<span></a>
                        </div>
                    </li><?php endif; ?>
                    <li><a class="title" href="<?php echo ($Group); ?>/Config/theme" target="main"><i class="ydimg-theme"></i>主题颜色</a></li>
                    <li class="admin" style="width: 190px;">
                        <a class="title">
                            <?php if(!empty($MemberAvatar)): ?><img class="avatar" src="<?php echo (htmlspecialchars($MemberAvatar)); ?>" />
                           <?php else: ?>
                                <img class="avatar" src="<?php echo ($Images); ?>defaultavatar.png"><?php endif; ?>
                            <span class="AdminName"><?php echo (htmlspecialchars($AdminName)); ?></span><br/>
							<span class="AdminGroupName"><?php echo (htmlspecialchars($AdminGroupName)); ?></span>
                            <i class="ydimg-bottom"></i>
                        </a>
                        <div class="list">
                            <a href="<?php echo ($Group); ?>/member/Modify/MemberID/<?php echo ($_SESSION['AdminMemberID']); ?>" target="main">个人信息</a>
                            <a href="<?php echo ($Url); ?>pwd" target='main'>修改密码</a>
                            <a href="<?php echo ($Url); ?>logout" target="_top">安全退出</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div id="menu-wrap"><iframe src="<?php echo ($Url); ?>AdminLeft/MenuTopID/<?php echo ($MenuTopID); ?>" id="menu" name="menu" target="main" scrolling="no" frameborder="0"></iframe></div>
    <div id="main-wrap"><iframe src="<?php echo ($Url); ?>Welcome" id="main" name="main" frameborder="0" scrolling="yes"></iframe></div>
	<?php if(empty($IsOem)): ?><div id="myfooter">
	        <a href="<?php echo ($CompanyUrl1); ?>" target="_blank"><?php echo ($CMSName); echo ($CMSEnName); ?></a>&nbsp;<?php echo ($CMSVersion); ?>&nbsp;
	        <a href="<?php echo ($CompanyUrl); ?>" target="_blank"><?php echo (htmlspecialchars($CompanyFullName)); ?></a>&nbsp;版权所有&nbsp;侵权必究
	    </div><?php endif; ?>
</body>
</html>
<script type="text/javascript">
function ChangeLng(obj,mark,url,id){
	var Items = document.getElementById("language").getElementsByTagName("a");
	for(var i= 0,len = Items.length;i<len;++i){
		if(Items[i].clssName !==""){
			Items[i].className = "";
		}
	}
	obj.className = "active";
	
	var currentUrl = window.frames["menu"].location.href;
	if(currentUrl.indexOf("MenuTopID/3") > 0 ){
		RefreshChannel(obj,mark);
	}else{
		RefreshMain(obj,mark);
	}
}

function RefreshChannel(obj,mark){
	var t = new Date().getTime();
	var url = window.frames["menu"].location.href;
	var p = url.indexOf("/l/");
	if( p > 0){
	  url = url.substring(0, p);
	}
	obj.target = "menu";
	obj.href = url+"/l/"+mark+"/random/"+t;
	var mainUrl = window.frames["main"].location.href;
	var objUrl = "<?php echo ($Url); ?>welcome";
	var index = mainUrl.toLowerCase().indexOf( objUrl.toLowerCase()  );
	if( index == -1  ){
		window.frames["main"].location.href = "<?php echo ($Url); ?>welcome";
	}
	return true;
}

function RefreshMain(obj,mark){
	var t = new Date().getTime();
	var url = window.frames["main"].location.href;
	var p = url.indexOf("/l/");
	if( p > 0){
	  url = url.substring(0, p);
	}
	obj.target = "main";
	obj.href = url+"/l/"+mark+"/random/"+t;
	return true;
}
</script>
<script type="text/javascript">
var gUpgradeUrl = "<?php echo ($Url); ?>upgrade?debug=1";  //升级接口地址
var gTimeout = 180*1000; //超时时间

var gNewVersion = ""; //当前新版本号
var gReleaseDate = ""; //发布日期
var gIsUpgrading = false; //是否正在升级
var gIsReUpgrade = 0;  //是否强制升级

//检测升级
function checkUpgrade(isAutoCheck){
	if(checkSafeQuestion()) return;
	gIsUpgrading = false;
	gNewVersion = "";
	gReleaseDate = "";
	clearTip();
	var url = "<?php echo ($Url); ?>checkUpgrade?debug=1";
	$.get(url, null, function(data){
		var ver = (data.data && data.data.version) || "";
		gNewVersion = ver;
		if (data.status == 1){
			gReleaseDate = data.data.date;
			openUpgradeDlg("发现新版本 v"+ver, data.data.description, 1);
		}else{
			if(isAutoCheck){
				console.log("升级检测失败："+data.info);
			}else{
				var des = "<span class='des' style='color:red;'>";
				des += "<img src='<?php echo ($Images); ?>artdialog/warning.png' />"+data.info+"</span>";
				openUpgradeDlg("检查新版本", des, 2);
			}
		}
	}, "json");
}

//打开升级对话框 mode=1 显示升级和取消，2：显示我知道了
function openUpgradeDlg(title, des, mode){
	$(".upgrade_title").text(title);
	$(".upgrade_body").html(des);
	var dlgWidth = 450;
	var  winWidth = parseInt( $(window).width() );
	var offsetX = (winWidth-dlgWidth)/2;
	$(".dlg_upgrade").css({
		"top": "25%",
		"left": offsetX+"px",
		"width": dlgWidth+"px"
	});
	if(mode == 1){
		$(".btnKnow").hide();
		$(".btnUpgrade").show();
		$(".btnReUpgrade").hide();
		$(".btnCancel").show();
	}else{ //仅提示
		$(".btnKnow").show();
		$(".btnUpgrade").hide();
		$(".btnCancel").hide();
		
		$(".btnReUpgrade").show();
		$(".btnReUpgrade").removeClass("disabled");
		$(".btnReUpgrade").attr('disabled', false);
		$(".btnReUpgrade").val("重新升级");
	
	}
	$(".dlg_upgrade").show();
}

//关闭升级对话框
function closeUpgradeDlg(){
	gIsUpgrading = false;
	$(".dlg_upgrade").hide();
}

//第1步：下载升级文件
function downloadFile(){
	if(!gIsUpgrading) return;
	clearTip();
	showTip("开始升级");
	var params = {
		"Step":1,
		"Version":gNewVersion,
		"random":new Date().getTime(),
		"IsReUpgrade":gIsReUpgrade
	};
	$.ajax({url: gUpgradeUrl,  type: "POST", timeout: gTimeout, data: params, dataType:"json",
		success: function(data){
			if (data.status == 1){
				showTip(data.info);
				unzipFile(data.data);
			}else{
				showError(data.info);
			}
		},
		error:function errorCallback(obj, textStatus, errorThrown){
			var errmsg = (textStatus == "timeout") ? "下载升级包超时" :"发生错误 "+obj.statusText;
			showError(errmsg);
		}
	});
}

//第2步：解压升级包
function unzipFile(data){
	if(!gIsUpgrading) return;
	var params = {
		"Step":2, 
		"ZipFile":data['ZipFile'], 
		"random":new Date().getTime(),
		"Version":gNewVersion,
		"IsReUpgrade":gIsReUpgrade 
	};
	$.ajax({url: gUpgradeUrl,  type: "POST", timeout: gTimeout, data: params, dataType:"json",
		success: function(data){
			if (data.status == 1){
				showTip(data.info);
				upgradeDb(data.data);
			}else{
				showError(data.info);
			}
		},
		error:function errorCallback(obj, textStatus, errorThrown){
			var errmsg = (textStatus == "timeout") ? "解压升级包超时" :"发生错误 "+obj.statusText;
			showError(errmsg);
		}
	});
}

//第3步：升级数据库
function upgradeDb(data){
	if(!gIsUpgrading) return;
	var params = {
		"Step":3, 
		"Version":gNewVersion, 
		"ReleaseDate":gReleaseDate,
		"IsReUpgrade":gIsReUpgrade,
		"random":new Date().getTime() 
	};
	$.ajax({url: gUpgradeUrl,  type: "POST", timeout: gTimeout, data: params, dataType:"json",
		success: function(data){
			if (data.status == 1){
				showTip(data.info);
				showTip("恭喜，升级完成！");
				$(".upgrade-loading").hide();
				$(".btnKnow").show();
				$(".btnUpgrade").hide();
				$(".btnReUpgrade").hide();
				$(".btnCancel").hide();
			}else{
				showError(data.info);
			}
		},
		error:function errorCallback(obj, textStatus, errorThrown){
			var errmsg = (textStatus == "timeout") ? "升级数据库超时" :"发生错误 "+obj.statusText;
			showError(errmsg);
		}
	});
}

function getTipPrefix(){
	var myDate = new Date(); 
	var hour = myDate.getHours(); //获取时，
	var min = myDate.getMinutes(); //分
	var second = myDate.getSeconds(); //秒
	if(second<10) second = "0"+second;
	var prefix = "· "+hour+":"+min+":"+second+" ";
	return prefix;
}

//停止升级
function stopUpgrade(){
	gIsUpgrading = false;
	$(".upgrade-loading").hide();
	$(".btnUpgrade").removeClass("disabled");
	$(".btnUpgrade").attr('disabled', false);
	$(".btnUpgrade").val("一键升级");

	$(".btnReUpgrade").removeClass("disabled");
	$(".btnReUpgrade").attr('disabled', false);
	$(".btnReUpgrade").val("重新升级");
}

//升级提示
function showTip(html){
	if(!html) return;
	var prefix = getTipPrefix();
	html = "<div class='item'>"+prefix+html+"</div>";
	$(".upgrade_tip").append(html);
}

//清空所有升级提示
function clearTip(){
	$(".upgrade_tip").html("");
}

//显示升级错误
function showError(message){
	var prefix = getTipPrefix();
	html = "<div class='item'>"+prefix+"<span style='color:red;'>"+message+"</span></div>";
	$(".upgrade_tip").append(html);
	stopUpgrade();
}

$(document).ready(function(){
	$('.headerlist li').mouseenter(function(){
		$(this).find('.list').show();
	})
	$('.headerlist li').mouseleave(function(){
		$(this).find('.list').hide();
	})
	
	//取消升级
	$(".btnCancel").click(function(){
		closeUpgradeDlg();
	});
	
	//我知道了
	$(".btnKnow").click(function(){
		closeUpgradeDlg();
	});
	
	//立即升级
	$(".btnUpgrade").click(function(){
		$(".upgrade-loading").show();
		$(".btnUpgrade").attr('disabled',true);
		$(".btnUpgrade").addClass("disabled");
		$(".btnUpgrade").val("升级中，请稍后...");
		gIsUpgrading = true;
		gIsReUpgrade = 0;
		downloadFile(); //下载升级文件
	});
	
	//强制升级
	$(".btnReUpgrade").click(function(){
		$(".upgrade-loading").show();
		$(".btnReUpgrade").attr('disabled',true);
		$(".btnReUpgrade").addClass("disabled");
		$(".btnReUpgrade").val("升级中，请稍后...");
		gIsUpgrading = true;
		gIsReUpgrade = 1;
		downloadFile(); //下载升级文件
	});
	
	//是否自动检测更新
	var checkUpdate = "<?php echo ($CheckUpdate); ?>";
	if(1==checkUpdate) checkUpgrade(true);
});

</script>
<style>
.dlg_upgrade{ 
	overflow:hidden; width:420px; margin:0 auto; 
	border: 1px solid rgba(0,0,0,.2); border-radius: 6px;
	-webkit-box-shadow: 0 5px 15px rgba(0,0,0,.5); background:#fff;
    box-shadow: 0 5px 15px rgba(0,0,0,.5); display:none; position:absolute;
}
.dlg_upgrade .upgrade_title{ background:#fff; padding:15px; font-size: 16px;font-weight: bold; text-align: center; }
.dlg_upgrade .upgrade_body{ border-bottom: 1px solid #e5e5e5; border-top: 1px solid #e5e5e5; padding:10px; 
font-size:15px; line-height:1.6em;  overflow:hidden; margin-bottom:8px;}
.dlg_upgrade .upgrade_tip{   overflow:hidden; }
.dlg_upgrade .upgrade_tip .item{ padding: 2px 15px; font-size:15px; color:#002bff; }
.dlg_upgrade .des{ font-size:16px; }
.dlg_upgrade .des img{ vertical-align:middle; margin-left:8px;}
.dlg_upgrade .upgrade_bottom{ padding:12px; overflow:hidden;}

.dlg_upgrade .btnCancel, .dlg_upgrade .btnUpgrade, .dlg_upgrade .btnKnow, .dlg_upgrade .btnReUpgrade{ 
	float:right; margin-left:15px;  text-align:center; 
	border-radius: 4px; CURSOR: pointer; padding: 8px 15px;font-size:14px; 
 }
.dlg_upgrade .btnReUpgrade{ background:red; border: 1px solid red; color: #fff; }
.dlg_upgrade .btnReUpgrade:hover{ opacity:0.7; background:red; color:#fff; }
.dlg_upgrade .btnCancel{ background:#fff; border: 1px solid #ccc; color: #212529; }
.dlg_upgrade .btnCancel:hover{ background: #eee; }
.dlg_upgrade .btnUpgrade, .dlg_upgrade .btnKnow{ background:#5cb85c; border: 1px solid #4cae4c;color: #fff; }
.dlg_upgrade .btnUpgrade:hover, .dlg_upgrade .btnKnow:hover{ opacity:0.8; color:#fff; background:#5cb85c; }
.dlg_upgrade .disabled{ opacity:0.8; color:#f0f0f0; cursor:not-allowed; }
.upgrade-loading{ border:0; vertical-align:middle; width:25px; margin-top:6px; display:none;}
</style>
<div class="dlg_upgrade">
	<div class="upgrade_title"></div>
	<div class="upgrade_body"></div>
	<div class="upgrade_tip"></div>
	<div class="upgrade_bottom">
		<img class="upgrade-loading" src="<?php echo ($WebPublic); ?>Images/loading/20.gif" />
		<input type="button" class="btnKnow" value="我知道了" />
		<input type="button" class="btnReUpgrade" value="重新升级" />
		<input type="button" class="btnUpgrade" value="一键升级" />
		<input type="button" class="btnCancel" value="取消" />
	</div>
</div>