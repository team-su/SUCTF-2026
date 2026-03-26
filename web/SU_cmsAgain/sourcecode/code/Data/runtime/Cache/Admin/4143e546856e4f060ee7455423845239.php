<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <?php echo ($XUaCompatible); ?>
    <title></title>
	<link href="<?php echo ($Css); ?>font.css" rel="stylesheet" type="text/css" />
    <style type="text/css">
        body{ 
			font-family: "Microsoft Yahei"; height:100%; overflow:hidden; margin: 0; 
			background: <?php echo ($AdminLeftMenuBgColor); ?>; 
		}
        [class*=ydicon]:before{ font-family: 'ydicon'; }
    	ul{ margin: 0; padding: 0; }
		li {list-style:none;}
		a { color:#4D5D2C; text-decoration:none; cursor:pointer;}
		#menu a.active, #menu a:hover{ 
			color: <?php echo ($AdminLeftMenuSelectedColor); ?>;
		}
		
		#menu ul li.ydicon-left{ position: relative; }
		#menu ul li.ydicon-left:before{ position: absolute; right: -11px; top: 50%; margin-top: -15px; font-size: 25px; color: #fafafa; }

		#top{ height: 100%; width: 100%; overflow:hidden;}
		#top .con { position:relative;width:100%; height: 100%;}
		#logo img{ display: block; margin: 10px auto 20px; width: 50px; border-radius: 50%; padding: 0px; }
		#logo a { display:block; }
		#menu { font-size:14px; font-weight:normal; }
		#menu a { 
			display:block; text-align:center; margin-bottom: 12px; 
			color: <?php echo ($AdminLeftMenuTextColor); ?>; 
		}
		#menu a.active{ text-decoration:none; }
		#menu a:before{ display: block; width: 1em; margin: 0 auto 2px; font-size: 23px; font-weight: normal; }
		#cms{ position: fixed; bottom: 2px; left: 0; right: 0; margin: auto; }
		#cms a{ display: block; width: 75px; margin: 0 auto; }
		#cms img{ width: 100%; border: 0; }
	</style>
</head>
<body>
<div id="top">
    <div class="con">
        <div id="logo"><a href="<?php echo ($Url); ?>welcome" title="<?php echo ($CMSName); ?>" target="main">
			<?php if(empty($IsOem)): ?><img src="<?php echo ($Images); ?>logo.png"></a>
			<?php else: ?>
				<img src="<?php echo ($CmsLogo); ?>"></a><?php endif; ?>
		</a></div>
        <?php if(!empty($Language1)): ?><div id="language">
              <div>
                    <ul>
                        <?php if(is_array($Language)): $i = 0; $__LIST__ = $Language;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$l): $mod = ($i % 2 );++$i;?><li>
                                <a <?php if(($l["LanguageMark"]) == $AdminLanguageMark): ?>class="active"<?php endif; ?>  onclick="ChangeLng(this,'<?php echo ($l["LanguageMark"]); ?>','<?php echo ($Url); ?>','lng<?php echo ($l["LanguageID"]); ?>')" target="main"><?php echo ($l["LanguageName"]); ?></a>
                            </li><?php endforeach; endif; else: echo "" ;endif; ?>
                    </ul>
              </div>
            </div><?php endif; ?>
        <!--menu start-->
        <div id="menu">
            <div class="item">
                <ul>
                    <?php if(is_array($MenuTop)): $i = 0; $__LIST__ = $MenuTop;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$m): $mod = ($i % 2 );++$i; if($m["IsActive"] == '1'): ?><li class="index">
								<a href="<?php echo ($m["MenuLink"]); ?>" id="item<?php echo ($m["MenuTopID"]); ?>" target="<?php echo ($m["MenuTopTarget"]); ?>" mid="<?php echo ($m["MenuTopID"]); ?>" class="active ydicon-menu<?php echo ($m["MenuTopID"]); ?>" onclick="Tabmenu(this,<?php echo ($m["MenuTopID"]); ?>);"><?php echo ($m["MenuTopName"]); ?></a>
								</li>
                        <?php else: ?>
                            <li>
								<a href="<?php echo ($m["MenuLink"]); ?>" id="item<?php echo ($m["MenuTopID"]); ?>" class="ydicon-menu<?php echo ($m["MenuTopID"]); ?>" target="<?php echo ($m["MenuTopTarget"]); ?>"  mid="<?php echo ($m["MenuTopID"]); ?>" onclick="Tabmenu(this,<?php echo ($m["MenuTopID"]); ?>);"><?php echo ($m["MenuTopName"]); ?>
								</a>
							</li><?php endif; endforeach; endif; else: echo "" ;endif; ?>
                </ul>
            </div>
        </div>
        <!--menu end-->
        <div id="cms">
			<?php if(!empty($CompanyUrl1)): ?><a href="<?php echo ($CompanyUrl1); ?>" title="<?php echo ($CMSName); ?>" target="_blank"><?php endif; ?>
			<?php if(empty($IsOem)): ?><img src="<?php echo ($Images); ?>cms.png">
			<?php else: ?>
				<?php if(!empty($CmsTextLogo)): ?><img src="<?php echo ($CmsTextLogo); ?>"><?php endif; endif; ?>
			<?php if(!empty($CompanyUrl1)): ?></a><?php endif; ?>
		</div>
    </div>
</div>
</body>
</html>
<script type="text/javascript" src="<?php echo ($WebPublic); ?>jquery/jquery.min.js"></script>
<script>
$(document).ready(function() {
	$(document).keydown(function(e) {
		window.parent.frames['main'].keyHandler(e);
	});
	
	var n = "<?php echo ($MenuTopID); ?>";
	var curitem = document.getElementById("item"+n);
	Tabmenu(curitem, n);
});

function Tabmenu(obj,n){
	$("#menu a").removeClass("active").parent().removeClass('ydicon-left');
	$(obj).addClass("active").parent().addClass('ydicon-left');
	location.hash = n;
	if(n==17){
		setTimeout(function(){
			$("#menu-wrap", window.parent.document).hide();
			$("#main-wrap", window.parent.document).css('left', '80px')
		}, 200);
	}else{
		var menuWidth = 165; //默认宽度
		var mainLeft = 245; //默认左边距
		var treeWidth = "<?php echo ($ChannelTreeWidth); ?>";
		//150这个宽度没有必要修改adminindex里面的宽度
		if(n == 3 && treeWidth>150){ 
			 var delta = treeWidth - 150;
			 menuWidth += delta;
             mainLeft += delta;
			 console.log("treeWidth="+treeWidth+" menuWidth="+menuWidth+" mainLeft="+mainLeft);
		}
		$("#menu-wrap",  window.parent.document).show();
		$("#menu-wrap", window.parent.document).css('width', menuWidth+'px');
		$("#main-wrap", window.parent.document).css('left', mainLeft+'px');
	}
	return true;
};
</script>