<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
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
		.container{ padding: 12px 10px;}
		#upgradetip{color:green;font-weight:bold;	}
		.ImageYes{	font-size: 14px;	color: #339900;	font-weight:bold;	font-family:Verdana;} 
		.ImageNo{	font-size:14px;	color:red;	font-weight:bold;	font-family:Verdana;}
		div.table{ border-radius: 5px; }
		div.table .th { 
			border-radius: 6px 6px 0 0;
			color:#2a333c;
			background-color:#f0f0f0; 
			border:1px solid #ddd; 
			height:30px; line-height:30px; padding:5px 10px; 
			position:relative; margin-left:0px; font-size:14px; 
		}
		div.table  td{ padding:8px 8px;}
		/*网站概况*/
		.block-container{ overflow:hidden; float:left; width:12.5%; }
		.block{ background:#e2e2e2; width:90%; padding:18px 0; margin:10px; border-radius:5px;}
		.block h2{ font-size:20px; text-align:center; margin-top:5px; color: #999;}
		.block p{ font-size:24px; text-align:center; color:#1fbeec;}
		.block:hover{ opacity:0.8; transition: all 0.5s !important;}
		
		.tip-wrapper{color:red;font-size:16px; width:380px; padding:5px 8px;}
		.tip-wrapper .tip-item{ border-bottom:1px solid #eee; padding: 3px 0;margin: 5px 0;}
		.tip-wrapper .tip-foot{color:blue;font-size:14px}
		.tip-wrapper #cbSafeDlg{margin-right:5px;}
    </style>
</head>
<body id="main_page">
<div class="container">
       <div class="table" style="display:none;">
            <h2 class="th" id="c5">登录成功</h2>                
            <table>
                <tr>
                    <td width="25%">您好，&nbsp;<span class="redTip"><?php echo (htmlspecialchars($AdminName)); ?></span>&nbsp;您已成功登录系统！</td>
                     <td width="25%">登录次数：<span class="redTip"><?php echo ($LoginCount); ?></span>&nbsp;&nbsp;</td>
                    <td width="25%">登录IP：<span class="redTip"><?php echo ($LastLoginIP); ?></span>&nbsp;&nbsp;</td>
                    <td  width="25%">登录时间：<span class="redTip"><?php echo ($LastLoginTime); ?></span> </td>
                </tr>
            </table>
        </div>
		
		<div class="table">
            <h2 class="th" id="c5">网站概况
				<span style="float:right;padding-right:10px;">系统版本：v<?php echo ($CMSVersion); ?></span>
			</h2>                
            <table>
                <tr>
                    <td>
						<?php if(is_array($StatList)): $i = 0; $__LIST__ = $StatList;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$s): $mod = ($i % 2 );++$i;?><div class="block-container"><div class="block"><p><?php echo ($s["Count"]); ?></p><h2><?php echo ($s["Name"]); ?></h2></div></div><?php endforeach; endif; else: echo "" ;endif; ?>
					</td>
                </tr>
            </table>
        </div>

        <div class="table">
            <h2 class="th" id="c3">服务器基本信息
				<span class="head" style="float:right; padding-right:10px;">
					当前服务器时间：<?php echo ($Server["Time"]); ?>
					&nbsp;&nbsp;时区：<?php echo date_default_timezone_get();?>
				</span>
			</h2>                
            <table>
                <tr>
                  <td width="50%" colspan="2">
						操作系统：<?php echo (htmlspecialchars($Server["OS"])); ?> 编码：<?php echo (htmlspecialchars($Server["AcceptLanguage"])); ?>
				 </td>
                  <td width="25%">服务器IP：<span class="redTip"><?php echo (htmlspecialchars($Server["IP"])); ?></span></td>
                  <td width="25%">WEB服务器：<span class="redTip"><?php echo ((htmlspecialchars($Server["WebServerName"]))?(htmlspecialchars($Server["WebServerName"])):'未知'); ?></span></td>
                </tr>
                <tr>
                <td width="25%">PHP运行模式：<b><?php echo (htmlspecialchars($Server["PHPSAPI"])); ?></b></td>
                <td width="25%">PHP版本：<span class="redTip"><?php echo (htmlspecialchars($Server["PHPVersion"])); ?></span>
				PHP安全模式：<?php echo ($Server["PHPSafe"]); ?>
				</td>
                <td width="25%">最大INPUT数&nbsp;max_input_vars: <span class="redTip"><?php echo ($Server["MaxInputVars"]); ?></span></td>
                <td width="25%">POST最大字节数&nbsp;post_max_size: <span class="redTip"><?php echo ($Server["MaxPostSize"]); ?></span></td>
              </tr>
              <tr>
                <td>Session：<?php echo ($Server["Session"]); ?></td>
                <td>Cookie：<?php echo ($Server["Cookie"]); ?></td>
                <td>脚本最长执行时间：<span class="redTip"><?php echo ($Server["MaxExecutionTime"]); ?></span>秒</td>
                <td>最大上传大小：<span class="redTip"><?php echo ($Server["UploadMaxFileSize"]); ?></span></td>
              </tr>
            </table>
        </div>     
        
        <div class="table">
            <h2 class="th" id="c4">组件支持情况&nbsp;&nbsp;
            <a onclick="return checkPhpinfo()" href="<?php echo ($Group); ?>/Public/phpinfo" target="_blank" style="float:right;padding-right:10px;">查看更多...</a></h2>    
            <table>
                <tr>
                  <td width="25%">Curl：<?php echo ($Server["CurlInit"]); ?></td>
                  <td width="25%">iconv编码：<?php echo ($Server["Iconv"]); ?></td>
                  <td width="25%">图形处理 GD Library：<?php echo ($Server["GD"]); ?></td>
                  <td width="25%">ZendOptmizer：<?php echo ($Server["ZendOptimizer"]); ?></td>
                </tr>
                <tr>                          
                  <td>自定义全局变量&nbsp;register_globals：<?php echo ($Server["RegisterGlobals"]); ?></td>
                  <td>最多允许使用内存&nbsp;memory_limit：<span class="redTip"><?php echo ($Server["MemoryLimit"]); ?></span></td>
                  <td>Socket：<?php echo ($Server["Socket"]); ?></td>
                  <td>高精度数学运算 BCMath：<?php echo ($Server["BCMath"]); ?></td>
                </tr>
                
                <tr>
                  <td>历法运算 Calendar：<?php echo ($Server["Calendar"]); ?></td>
                  <td>magic_quotes_gpc：<?php echo ($Server["MagicQuotes"]); ?></td>
                  <td>magic_quotes_runtime：<?php echo ($Server["MagicQuotesRuntime"]); ?></td>
                  <td>MCrypt加密：<?php echo ($Server["MCrypt"]); ?></td>
                </tr>
                
                <tr>
                  <td>哈稀计算 MHash：<?php echo ($Server["MHash"]); ?></td>
                  <td>OpenSSL：<?php echo ($Server["OpenSSL"]); ?></td>
                  <td>流媒体支持：<?php echo ($Server["StreamMedia"]); ?></td>
                  <td>Tokenizer：<?php echo ($Server["Tokenizer"]); ?></td>
                </tr>
                
                <tr>
                  <td>文件压缩 Zlib：<?php echo ($Server["Zlib"]); ?></td>
                  <td>XML解析：<?php echo ($Server["XML"]); ?></td>
                  <td>目录存取协议 LDAP：<?php echo ($Server["LDAP"]); ?></td>
                  <td>多字节字符串MbString：<?php echo ($Server["MbString"]); ?></td>
                </tr>
            </table>
        </div>    
    
        <div class="table">
            <h2 class="th" id="c5">网站空间和数据库
				<?php if(!empty($ShowMysqlTime)): ?><span class="head" style="float:right; padding-right:10px;">
						当前数据库服务器时间：<?php echo ($Server["MySqlTime"]); ?>
					</span><?php endif; ?>
			</h2>           
            <table>
                <tr>
                    <td colspan="5">网站总大小：<b id="WebTotalSize" style="color:red"></b>&nbsp;&nbsp;<a onclick="GetTotalSize()">点击查询</a></td>
                </tr>
                <tr>
                    <td width="20%">数据库版本：<b><?php echo ($Server["MySqlVersion"]); ?></b></td>
                    <td width="20%">数据库服务器：<b><?php echo (C("DB_HOST")); ?></b></td>
                    <td width="20%">数据库名称：<b><?php echo (C("DB_NAME")); ?></b>
					</td>
                    <td width="20%">数据库编码：<b><?php echo ($Server["CharacterSetDatabase"]); ?></b></td>
                    <td width="20%">数据库大小：<span class="redTip"><?php echo ($Server["DbSize"]); ?></span></td>
                </tr>
            </table>
        </div>
</div>
</body>
</html>
<script>
function checkPhpinfo(){
	var b = checkSafeQuestion();
	return !b;
}

function GetTotalSize(){
    var url = "<?php echo ($Url); ?>getWebTotalSize";
	sizetip("计算中，请稍后...", true); 
	$.get(url, {}, TotalComplete, "json");
	return true;
}

function TotalComplete(data, textStatus){
	if (data.status == 1){
		$("#WebTotalSize").html(data.data);
	}else{
		sizetip("系统超时，请重试！", false);	
	}
}

function sizetip(str, isanimation){
	html = "";
	if( isanimation ){
		html = "<img src='<?php echo ($WebPublic); ?>Images/loading/21.gif' border='0' align='absmiddle'/>";
	}
	html += str;
	$("#WebTotalSize").html(html);		
}

//显示重要提示信息
function showTip(data){
	var showSafeAnswerTip = data.ShowSafeAnswerTip;
	var showDirTip = data.ShowDirTip;
	if(showSafeAnswerTip==0 && showDirTip==0) return;

	var content = "<div class='tip-wrapper'>";
	if(showSafeAnswerTip == 1){
		content += "<div class='tip-item'>▪ 您的网站没有设置【二次安全验证问题】</div>";
	}
	if(showDirTip == 1){
		content += "<div class='tip-item'>▪ 您的网站【目录权限】设置不正确</div>";
	}
	okText = "查看安全设置";
	content += "<div  class='tip-foot'>请务必按要求设置，您的网站将更加安全，可有效抵御病毒木马攻击！<div>";
	content += "</div>";
	showTipDlg(content, okText);
}

function showTipDlg(content, okText){
	var NoSafeTip = window.localStorage.getItem("NoSafeTip") || 0;
	if(NoSafeTip==1) return;
	var d = dialog({
		title: '安全隐患提醒',
		content: content,
		statusbar: '<label><input type="checkbox" id="cbSafeDlg" onclick="noremind()">不再提醒</label>',
		okValue: okText,
		padding: 5,
		ok: function () {
			d.close().remove();
			location.href = "__GROUP__/Config/safe";
		},
		cancelValue: '取消',
		cancel: function () {
			d.close().remove();
		}
	});
	d.show();
}

//不再提醒
function noremind(){
	var isCheck = $("#cbSafeDlg").is(':checked');
	if(isCheck){
		window.localStorage.setItem("NoSafeTip", 1);
	}else{
		window.localStorage.setItem("NoSafeTip", 0);
	}
}

$(document).ready(function(){   
	//alert($.fn.jquery); 当前版本1.7.2
	//$("#AuthorizeText").css("visibility","hidden"); 
	//$("#AuthorizeText").css("display","none"); 
	setTimeout(function(){
		var url = "<?php echo ($Url); ?>getTipInfo";
		$.post(url, null, function(res){
			if(res.status==1){
				showTip(res.data);
			}else{
				ErrorBox(data.info);
			}
		}, "json");
	}, 2000);
});
</script>