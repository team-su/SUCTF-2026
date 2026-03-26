/*
此插件基于Jquery
插件名：jquery.Sonline(在线客服插件)
作者 似懂非懂 版本 2.0 ,协议GPL
Blog：www.haw86.com
*/
(function($){
	$.fn.Sonline = function(options){
        var opts = $.extend({}, $.fn.Sonline.defualts, options); 
		$.fn.setList(opts); //调用列表设置
		$.fn.Sonline.styleType(opts);
		if(opts.DefaultsOpen == false){
			$.fn.Sonline.closes(opts.Position,0);
		}
		//展开
		//$("#SonlineBox > .openTrigger").live("click",function(){$.fn.Sonline.opens(opts);});
		//关闭
		//$("#SonlineBox > .contentBox > .closeTrigger").live("click",function(){$.fn.Sonline.closes(opts.Position,"fast");});
		
		//live() 方法在 jQuery 版本 1.7 中被废弃(1.7也支持on)，在版本 1.9 中被移除。请使用 on() 方法代替。
		$("#SonlineBox > .openTrigger").on("click", "", function(){
			$.fn.Sonline.opens(opts);
		});
		$("#SonlineBox > .contentBox > .closeTrigger").on("click",function(){
			$.fn.Sonline.closes(opts.Position,"fast");
		});
		// 手机和平板端默认收缩
		if($(window).width() <= 768){
			$.fn.Sonline.closes(opts.Position,0);
		}

		//Ie6兼容或滚动方式显示
		if ($.browser && ($.browser.version == "6.0") && !$.support.style||opts.Effect==true) {
			$.fn.Sonline.scrollType();
		}else if(opts.Effect==false){
			$("#SonlineBox").css({position:"fixed"});
		}
	}
	//plugin defaults
	$.fn.Sonline.defualts ={
		Position:"left",//left或right
		Top:200,//顶部距离，默认200px
		Effect:true, //滚动或者固定两种方式，布尔值：true或
		Width:170,//顶部距离，默认200px
		DefaultsOpen:true, //默认展开：true,默认收缩：false
		Style:1,//图标的显示风格，默认显示:1
		Tel:"",//服务热线
		Title:"在线客服",//服务热线
		FooterText:'',
		Website:'',
		IconColor: '#FFF',
		ThemeColor: '',
		Qqlist:"" //多个QQ用','隔开，QQ和客服名用'|'隔开
	}
	
	//展开
	$.fn.Sonline.opens = function(opts){
		var positionType = opts.Position;
		$("#SonlineBox").css({width:opts.Width+4});
		if(positionType=="left"){$("#SonlineBox > .contentBox").animate({left: 0},"fast");}
		else if(positionType=="right"){$("#SonlineBox > .contentBox").animate({right: 0},"fast");}
		$("#SonlineBox > .openTrigger").hide();
		$("#SonlineBox").css('overflow','visible');
	}

	//关闭
	$.fn.Sonline.closes = function(positionType,speed){
		$("#SonlineBox > .openTrigger").show();
		var widthValue =$("#SonlineBox > .openTrigger").width();
		var allWidth =(-($("#SonlineBox > .contentBox").width())-6);
		if(positionType=="left"){$("#SonlineBox > .contentBox").animate({left: allWidth},speed);}
		else if(positionType=="right"){$("#SonlineBox > .contentBox").animate({right: allWidth},speed);}
		$("#SonlineBox").css('overflow','hidden').animate({width:widthValue},speed);
		
	}
	
	//风格选择
	$.fn.Sonline.styleType = function(opts){
		var typeNum = 41;
		return typeNum;
	}

	//子插件：设置列表参数
	$.fn.setList = function(opts){
		$("body").append("<div yd-content='support' class='SonlineBox "+(opts.Style == 1 ? 'SonlineBox1' : 'SonlineBox2')+" position"+opts.Position+"' id='SonlineBox' style='top:-600px; position:absolute;'><div class='openTrigger' style='display:none' title=''></div><div class='contentBox'><div class='closeTrigger' title=''></div><div class='titleBox'><span>"+opts.Title+"</span></div><div class='listBox'></div><div class='tels'>"+opts.FooterText+"</div></div></div>");
		$("#SonlineBox > .contentBox").width(opts.Width)
		if(opts.Qqlist==""){ 
			$("#SonlineBox > .contentBox > .tels").css("border",0); 
		}else{
			var style = opts.Style;
			if(1==style){ //传统
				$.fn.Sonline.showStyle1(opts);
			}else if(2==style){ //图标
				$.fn.Sonline.showStyle2(opts);
			}else if(3==style){ //图标+标题
				$.fn.Sonline.showStyle3(opts);
			}
		}
		if(opts.Position=="left"){$("#SonlineBox").css({left:0});}
		else if(opts.Position=="right"){$("#SonlineBox").css({right:0})}
		$("#SonlineBox").css({top:opts.Top,width:opts.Width+4});
		setTimeout(function(){
			var allHeights=0;
			if($("#SonlineBox > .contentBox").height() < $("#SonlineBox > .openTrigger").height()){
				allHeights = $("#SonlineBox > .openTrigger").height()+4;
			} else{allHeights = $("#SonlineBox > .contentBox").height()+40;}
			$("#SonlineBox").height(allHeights);
		},50)
		if(opts.Position=="left"){$("#SonlineBox > .openTrigger").css({left:0});}
		else if(opts.Position=="right"){$("#SonlineBox > .openTrigger").css({right:0});}
	}
	
	//滑动式效果
	$.fn.Sonline.scrollType = function(){
		$("#SonlineBox").css({position:"absolute"});
		var topNum = parseInt($("#SonlineBox").css("top")+"");
		$(window).scroll(function(){
			var scrollTopNum = $(window).scrollTop();//获取网页被卷去的高
			$("#SonlineBox").stop(true,false).delay(200).animate({top:scrollTopNum+topNum},"slow");
		});
	}
	
	$.fn.Sonline.showStyle1 = function(opts){
		var qqListHtml = $.fn.Sonline.splitStr1(opts);
		$("#SonlineBox > .contentBox > .listBox").append(qqListHtml);	
	}
	
	$.fn.Sonline.showStyle2 = function(opts){
		var qqListHtml = $.fn.Sonline.splitStr2(opts);
		$("#SonlineBox > .contentBox > .listBox").append(qqListHtml);
		$(".SonlineBox2 .contentBox .listBox .item").css({width: opts.Width + 'px', 'height': opts.Width + 'px', 'line-height': opts.Width + 'px', 'color': opts.IconColor, 'background-color': opts.ThemeColor})
		$(".SonlineBox2 .contentBox .listBox .item i").css("font-size", opts.Width / 2 + 'px');
		$(".SonlineBox2 .contentBox .listBox .item .triangle").css({top: opts.Width / 2 - 7 + 'px'});
		$(".SonlineBox2 .contentBox .listBox .item.footer").css('line-height', opts.Width + 'px').hover(function(){
			$(".SonlineBox2 .contentBox .tels").addClass('show').css('bottom', opts.Width / 2 - 25 + 'px');
		},function(){
			$(".SonlineBox2 .contentBox .tels").removeClass('show');
		});
	}
	
	$.fn.Sonline.showStyle3 = function(opts){
		var qqListHtml = $.fn.Sonline.splitStr2(opts);
		$("#SonlineBox > .contentBox > .listBox").append(qqListHtml);
		$(".SonlineBox2 .contentBox .listBox .item span.title").css('display', 'block');
		$(".SonlineBox2 .contentBox .listBox .item").css({width: opts.Width + 'px', 'height': opts.Width + 'px', 'color': opts.IconColor, 'background-color': opts.ThemeColor})
		$(".SonlineBox2 .contentBox .listBox .item i").css({"font-size": opts.Width / 2 + 'px'});
		$(".SonlineBox2 .contentBox .listBox .item .triangle").css({top: opts.Width / 2 - 7 + 'px'});
		$(".SonlineBox2 .contentBox .listBox .item.footer").css('line-height', opts.Width + 'px').hover(function(){
			$(".SonlineBox2 .contentBox .tels").addClass('show').css('bottom', opts.Width / 2 - 25 + 'px');
		},function(){
			$(".SonlineBox2 .contentBox .tels").removeClass('show');
		});
	}
	
	//分割QQ
	$.fn.Sonline.splitStr1 = function(opts){
		var strs= new Array(); //定义一数组
		var QqlistText = opts.Qqlist;
		strs=QqlistText.split(","); //字符分割
		var alt = "";
		var QqHtml=""
		for (var i=0;i<strs.length;i++){	
			var subStrs= new Array(); //定义一数组
			var subQqlist = strs[i];
			subStrs = subQqlist.split("|"); //字符分割
			var type = parseInt(subStrs[2]);
			switch(type){
				case 2://淘宝旺旺
					QqHtml += "<div class='QQList'><span>"+subStrs[1]+"：</span><div class='ico'>";
					QqHtml += "<a target='_blank' href='http://amos1.taobao.com/msg.ww?v=2&uid="+subStrs[0]+"&s=1' >";
					QqHtml += "<img border='0' src='http://amos1.taobao.com/online.ww?v=2&uid="+subStrs[0]+"&s=1' alt='"+alt+"' title='"+alt+"' />";
					QqHtml += "</a>";
					break;
				case 3://阿里旺旺
					QqHtml += "<div class='QQList'><span>"+subStrs[1]+"：</span><div class='ico'>";
					QqHtml += "<a target='_blank' href='http://amos.im.alisoft.com/msg.aw?v=2&amp;uid="+subStrs[0]+"&amp;site=cnalichn&amp;s=4'>";
					QqHtml += "<img alt='"+alt+"' title='"+alt+"' border='0' src='http://amos.im.alisoft.com/online.aw?v=2&amp;uid="+subStrs[0]+"&amp;site=cnalichn&amp;s=4' />";
					QqHtml += "</a>";
					break;
				case 6://阿里旺旺国际版
					QqHtml += "<div class='QQList'><span>"+subStrs[1]+"：</span><div class='ico'>";
					QqHtml += "<a target='_blank' href='http://amos.alicdn.com/msg.aw?v=2&amp;uid="+subStrs[0]+"&amp;site=enaliint&amp;s=24&amp;charset=UTF-8' ";
					QqHtml += "  style='text-align:center;' data-uid='"+subStrs[0]+"'>";
					QqHtml += "<img style='border:none;vertical-align:middle;margin-right:5px;' ";
					QqHtml += " src='http://amos.alicdn.com/online.aw?v=2&amp;uid="+subStrs[0]+"&amp;site=enaliint&amp;s=22&amp;charset=UTF-8'>";
					QqHtml += ""+subStrs[0]+"</a>";
					break;
				case 4://微软MSN
					QqHtml += "<div class='QQList'><span>"+subStrs[1]+"：</span><div class='ico'>";
					var msn = opts.Website+"/Public/Images/online/msn.gif";
					QqHtml += "<a target='_blank' href='msnim:chat?contact="+subStrs[0]+"&Site="+subStrs[0]+"'>";
					QqHtml += "<img src='"+msn+"' alt='"+alt+"' title='"+alt+"'/>";
					QqHtml += "</a>";
					break;
				case 5://Skype
					QqHtml += "<div class='QQList'><span>"+subStrs[1]+"：</span><div class='ico'>";
					var skype = opts.Website+"/Public/Images/online/skype.gif";
					QqHtml += "<a target='_blank' href='callto://"+subStrs[0]+"'>";
					QqHtml += "<img border='0' src='"+skype+"' alt='"+alt+"' title='"+alt+"'/>";
					QqHtml += "</a>";
					break;
				case 7://自定义类型
					QqHtml += "<div class='QQList'><span>"+subStrs[1]+"：</span><div class='ico'>";
					QqHtml +=  subStrs[0];
					break;
				case 8: //电话
					QqHtml += "<div class='QQList TelList'><span>"+subStrs[1]+"：</span>";
					QqHtml += "<a target='_blank' href='tel:"+subStrs[0]+"'>"+subStrs[0];
					QqHtml += "</a><div>";
					break;
				case 9: //二维码
					QqHtml += "<div class='QrList'><div class='ico'>";
					QqHtml += "<img border='0' src='"+subStrs[0]+"'/>";
					QqHtml += "<span>"+subStrs[1]+"</span>";
					break;
				case 10: //外部链接
					QqHtml += "<div class='LinkList'>";
					QqHtml += "<a target='_blank' href='"+subStrs[0]+"'>"+subStrs[1];
					QqHtml += "</a><div>";
					break;
				case 11: //facebook
					QqHtml += "<div class='QQList TelList'><span>"+subStrs[1]+"：</span>";
					QqHtml += "<a target='_blank' href='https://www.facebook.com/"+subStrs[0]+"'>"+subStrs[0];
					QqHtml += "</a><div>";
					break;
				case 1: //QQ
				default:
					QqHtml += "<div class='QQList'><span>"+subStrs[1]+"：</span><div class='ico'>";
					QqHtml += "<a target='_blank' href='http://wpa.qq.com/msgrd?v=3&uin="+subStrs[0]+"&site=qq&menu=yes'>";
					QqHtml += "<img border='0' src='http://wpa.qq.com/pa?p=2:"+subStrs[0]+":"+$.fn.Sonline.styleType(opts)+" &amp;r=0.22914223582483828' alt='"+alt+"'  title='"+alt+"'>";
					QqHtml += "</a>";
			}
			QqHtml += "</div><div style='clear:both;'></div></div>";
		}
		return QqHtml;
	}

	//分割QQ
	$.fn.Sonline.splitStr2 = function(opts){
		var strs= new Array(); //定义一数组
		var QqlistText = opts.Qqlist;
		strs=QqlistText.split(","); //字符分割
		var alt = "";
		var QqHtml=""
		for (var i=0;i<strs.length;i++){	
			var subStrs= new Array(); //定义一数组
			var subQqlist = strs[i];
			subStrs = subQqlist.split("|"); //字符分割
			var type = parseInt(subStrs[2]);
			QqHtml += "<div class='item' title="+subStrs[1]+">";
			switch(type){
				case 2://淘宝旺旺
					QqHtml += "<a target='_blank' href='https://amos1.taobao.com/msg.ww?v=2&uid="+subStrs[0]+"&s=1' >";
					QqHtml += "<i class='ydicon-wangwang'></i>";
					QqHtml += "<span class='title'>"+subStrs[1]+"</span>";
					QqHtml += "</a>";
					break;
				case 3://阿里旺旺
					QqHtml += "<a target='_blank' href='https://amos.im.alisoft.com/msg.aw?v=2&amp;uid="+subStrs[0]+"&amp;site=cnalichn&amp;s=4'>";
					QqHtml += "<i class='ydicon-wangwang1'></i>";
					QqHtml += "<span class='title'>"+subStrs[1]+"</span>";
					QqHtml += "</a>";
					break;
				case 6://阿里旺旺国际版
					QqHtml += "<a target='_blank' href='https://amos.alicdn.com/msg.aw?v=2&amp;uid="+subStrs[0]+"&amp;site=enaliint&amp;s=24&amp;charset=UTF-8' data-uid='"+subStrs[0]+"'>";
					QqHtml += "<i class='ydicon-wangwang1'></i>";
					QqHtml += "<span class='title'>"+subStrs[1]+"</span>";
					QqHtml += "</a>";
					break;
				case 4://微软MSN
					QqHtml += "<a target='_blank' href='msnim:chat?contact="+subStrs[0]+"&Site="+subStrs[0]+"'>";
					QqHtml += "<i class='ydicon-MSN'></i>";
					QqHtml += "<span class='title'>"+subStrs[1]+"</span>";
					QqHtml += "</a>";
					break;
				case 5://Skype
					QqHtml += "<a target='_blank' href='callto://"+subStrs[0]+"'>";
					QqHtml += "<i class='ydicon-skype'></i>";
					QqHtml += "<span class='title'>"+subStrs[1]+"</span>";
					QqHtml += "</a>";
					break;
				case 7://自定义类型
					QqHtml +=  subStrs[0];
					break;
				case 8: //电话
					QqHtml += "<a target='_blank' href='tel:"+subStrs[0]+"'>";
					QqHtml += "<i class='ydicon-phone2'></i>";
					QqHtml += "<span class='title'>"+subStrs[1]+"</span>";
					QqHtml += "</a>";
					QqHtml += "<div class='float_box tel ydicon-phone2'><span class='triangle'></span>"+subStrs[0]+"</div>";
					break;
				case 9: //二维码
					QqHtml += "<i class='ydicon-WX'></i>";
					QqHtml += "<span class='title'>"+subStrs[1]+"</span>";
					QqHtml += "<div class='float_box qr'><span class='triangle'></span><img src='"+subStrs[0]+"' />"+subStrs[1]+"</div>";
					break;
				case 10: //外部链接
					QqHtml += "<a target='_blank' href='"+subStrs[0]+"'>";
					QqHtml += "<i class='ydicon-wangzhi'></i>";
					QqHtml += "<span class='title'>"+subStrs[1]+"</span>";
					QqHtml += "</a>";
					break;
				case 11: //facebook
					QqHtml += "<a target='_blank' href='https://www.facebook.com/"+subStrs[0]+"'>";
					QqHtml += "<i class='ydicon-fb1'></i>";
					QqHtml += "<span class='title'>"+subStrs[1]+"</span>";
					QqHtml += "</a>";
					break;
				case 1: //QQ
					QqHtml += "<a target='_blank' href='https://wpa.qq.com/msgrd?v=3&uin="+subStrs[0]+"&site=qq&menu=yes'>";
					QqHtml += "<i class='ydicon-qq2'></i>";
					QqHtml += "<span class='title'>"+subStrs[1]+"</span>";
					QqHtml += "</a>";
				default:
			}
			QqHtml += "<div style='clear:both;'></div></div>";
		}
		if(opts.FooterText){
			QqHtml += "<div class='item footer'>";
			QqHtml += "<div style='clear:both;'></div></div>";
		}
		return QqHtml;
	}
})(jQuery);    


 