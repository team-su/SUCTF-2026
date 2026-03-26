$(document).ready(function(){	
   $(".boxtable tr").hover(function() {
		$(this).find(".Caution").css("color","#F00");
	}, function() {
		$(this).find(".Caution").css("color","#666");
	});
	
	$(document).keydown(function(e) {
		keyHandler(e);
	});

	$(".datatable .checkrow").click(function(){
		var currentTr = $(this).parent().parent();
		if( $(this).is(':checked') ){
			currentTr.addClass("checked");
		}else{
			currentTr.removeClass("checked");
		}
	});
});

//解决升级到jquery1.12兼容性问题，$.browser被废弃
if(!$.browser){
	var myAgent = navigator.userAgent.toLowerCase();
	$.browser={};
	$.browser.mozilla = /firefox/.test(myAgent);
	$.browser.webkit = /webkit/.test(myAgent);
	$.browser.opera = /opera/.test(myAgent);
	$.browser.msie = /msie/.test(myAgent);
	$.browser.safari  = /safari/.test(myAgent);
}

//全选
function CheckAll(){
	var title = $("#selectall").attr('title');
	if(title == "全选"){
		$("#selectall").attr('title','取消');
		$("#selectall").html('取消');
		$(".datatable tr .checkrow").prop('checked', true);
		$(".datatable tr .checkrow").addClass("checked");
	}else{
		$("#selectall").attr('title','全选');
		$("#selectall").html('全选');
		$(".datatable tr .checkrow").prop('checked', false);
		$(".datatable tr .checkrow").removeClass("checked");
	}
}

//刷新左侧框架
function RefreshLeftFrame(){
	try{
		window.parent.frames["menu"].location.reload();
	}catch(e){
		;
	}
}

//参数顺序，msg,title
function ShowMessage(){
	var msg = arguments[0] ? arguments[0] : '';  //对话框内容
	var title = arguments[1] ? arguments[1] : '友情提示'; //标题
	var icon = arguments[2] ? arguments[2] : 'succeed'; //图标
	var time = arguments[3] ? arguments[3] : 1400; //自动关闭时间
	iconid = 'icon_'+icon;
	msg = "<div id='"+iconid+"' class='mybox'>"+msg+"</div>";
	var padding= 8;
	var skin = icon+"-box";
	var d;
	switch(icon){
		case 'succeed':
			d = dialog({padding:padding, skin:skin, content:msg, id:'tipid'});
			break;
		case 'error':
			d = dialog({content:msg,skin:skin, title:"友情提示", quickClose: true, okValue:"确定",ok:function(){}, id:'tipid', padding:padding});
			break;
		case 'warning':
			d = dialog({title:title, skin:skin,padding:padding, content:msg, id:'tipid'});
			break;
	}
	d.show();
	if(icon != "error") setTimeout(function () { d.close().remove(); }, time);
	return d;
}
//参数顺序：内容msg，标题title
function SucceedBox(){ return ShowMessage(arguments[0], arguments[1], 'succeed',1500);}
function ErrorBox(){ 
	var msg = arguments[0];
	if(msg && msg.indexOf("no-answer")>0 ) {
		showSafeQuestion();
		return true;
	}
	return ShowMessage(arguments[0], arguments[1], 'error', 2000);
}
function WarnBox(){ return  ShowMessage(arguments[0], arguments[1], 'warning', 2500);}
function LoadBox(){
	var msg = arguments[0] ? arguments[0] : '处理中，请稍后...';
	var html = '<div class="loading-container">';
    html += '<div class="loading">';
    html += '<div class="loading-dot"></div><div class="loading-dot"></div>';
    html += '<div class="loading-dot"></div><div class="loading-dot"></div><div class="loading-dot"></div>';
    html += '</div>';
    html += '<div class="loading-text">'+msg+'</div>';
	html += '</div>';
	$("body").append(html);
}
function CloseLoadBox(){ 
	$(".loading-container").remove();
}
//确认对话框
function ConfirmBox(content, successCallBack, failCallBack, params){
	if(!params) params={};
	var icon = params['Icon'] ? params['Icon'] : 'common';  //delete、common、sort
	if( content.indexOf('</div>') == -1 ){ //默认为common
		content = "<div id='icon_"+icon+"'>"+content+"</div>";
	}
	var Title= params["Title"] ? params["Title"] : "友情提示";
	var OkText = params["OkText"] ? params["OkText"] : "确定";
	var OkCancel = params["OkCancel"] ? params["OkCancel"] : "取消";
	var StatusBar = params["StatusBar"] ? params["StatusBar"] : "";
	//默认为true
	var QuickClose = (typeof(params["QuickClose"]) == "undefined") ? true : params["QuickClose"];
	if(!params.id) params.id="dlgConfirmBox";
	var d = dialog({
		title:Title, 
		skin: 'confirm-box', 
		padding:8, 
		fixed:true, 
		quickClose:QuickClose,
		id:params.id, content:content, ok: function () {
			if(successCallBack) successCallBack(this);
		}, okValue:OkText,
		cancel:function(){
			if(failCallBack) failCallBack(this);
		},
		statusbar: StatusBar,
		cancelValue:OkCancel
	});
	d.show();
	return d;
}

//通过ID，在光标处插入字符串
function insertAtCursorByID(id, value){
		insertAtCursor( document.getElementById(id), value);
}
//关闭颜色对话框，需要配合click事件使用
function closeColorPickerDlg(e){
	var obj = $("#colorpanel");
	if(obj.length==0) return;
	var offset = obj.offset();
	var x1 = offset.left;
	var x2 = offset.left + obj.width();
	var y1 = offset.top - 30;  //弹框永远向下，所以只需要在上面增加即可
	var y2 = offset.top + obj.height();
	if(e.pageX < x1 || e.pageX > x2 || e.pageY < y1 || e.pageY > y2){
		obj.hide();
	}
}
// 在光标处插入字符串
// myField, 文本框对象 document.getElementByID('')获取
// 要插入的值
function insertAtCursor(myField, myValue) {
	//IE support
	if (document.selection) {
		myField.focus();
		sel            = document.selection.createRange();
		sel.text    = myValue;
		sel.select();
	}
	//MOZILLA/NETSCAPE support
	else if (myField.selectionStart || myField.selectionStart == '0') {
		var startPos    = myField.selectionStart;
		var endPos        = myField.selectionEnd;
		// save scrollTop before insert
		var restoreTop    = myField.scrollTop;
		myField.value    = myField.value.substring(0, startPos) + myValue + myField.value.substring(endPos, myField.value.length);
		if (restoreTop > 0){
			// restore previous scrollTop
			myField.scrollTop = restoreTop;
		}
		myField.focus();
		myField.selectionStart    = startPos + myValue.length;
		myField.selectionEnd    = startPos + myValue.length;
	} else {
		myField.value += myValue;
		myField.focus();
	}
}



jQuery.fn.extend({
	/**  
	 * 选中内容  
	 */  
	selectContents: function(){   
		$(this).each(function(i){   
			var node = this;   
			var selection, range, doc, win;   
			if ((doc = node.ownerDocument) &&   
				(win = doc.defaultView) &&   
				typeof win.getSelection != 'undefined' &&   
				typeof doc.createRange != 'undefined' &&   
				(selection = window.getSelection()) &&   
				typeof selection.removeAllRanges != 'undefined')   
			{   
				range = doc.createRange();   
				range.selectNode(node);   
				if(i == 0){   
					selection.removeAllRanges();   
				}   
				selection.addRange(range);   
			}   
			else if (document.body &&   
					 typeof document.body.createTextRange != 'undefined' &&   
					 (range = document.body.createTextRange()))   
			{   
				range.moveToElementText(node);   
				range.select();   
			}   
		});   
	},   
	/**  
	 * 初始化对象以支持光标处插入内容  
	 */  
	setCaret: function(){   
		if(!$.browser.msie) return;   
		var initSetCaret = function(){   
			var textObj = $(this).get(0);   
			textObj.caretPos = document.selection.createRange().duplicate();   
		};   
		$(this)   
		.click(initSetCaret)   
		.select(initSetCaret)   
		.keyup(initSetCaret);   
	},   
	/**  
	 * 在当前对象光标处插入指定的内容  
	 */  
	insertAtCaret: function(textFeildValue){   
	   var textObj = $(this).get(0);   
	   if(document.all && textObj.createTextRange && textObj.caretPos){   
		   var caretPos=textObj.caretPos;   
		   caretPos.text = caretPos.text.charAt(caretPos.text.length-1) == '' ?   
							   textFeildValue+'' : textFeildValue;   
	   }   
	   else if(textObj.setSelectionRange){   
		   var rangeStart=textObj.selectionStart;   
		   var rangeEnd=textObj.selectionEnd;   
		   var tempStr1=textObj.value.substring(0,rangeStart);   
		   var tempStr2=textObj.value.substring(rangeEnd);   
		   textObj.value=tempStr1+textFeildValue+tempStr2;   
		   textObj.focus();   
		   var len=textFeildValue.length;   
		   textObj.setSelectionRange(rangeStart+len,rangeStart+len);   
		   textObj.blur();   
	   }   
	   else {   
		   textObj.value+=textFeildValue;   
	   }   
	}   
}); 

function ClearSystemCache(url){
	LoadBox("清除系统缓存中...");
	$.get(url, {}, function(data){
	   CloseLoadBox();
	   if (data.status==1){
			SucceedBox(data.info);
		}else{
			ErrorBox(data.info);
		}
	}, "json");
}

function ClearAllHtmlCache(url){
	LoadBox("清除全部Html静态缓存中...");
	$.get(url, {}, function(data){
	   CloseLoadBox();
	   if (data.status==1){
			SucceedBox(data.info);
		}else{
			ErrorBox(data.info);
		}
	}, "json");
}


//更新百度编辑数据
function UpdateUEditorData(){
	$(".ueditor").each(function(){
		var name = $(this).attr('id');
		var myContent =UE.getEditor(name+'container').getContent();
		$("#"+name).val(myContent);
	});
}

//重置百度编辑数据
function ResetUEditorData(){
	$(".ueditor").each(function(){
		var name = $(this).attr('id');
		UE.getEditor(name+'container').setContent("");
		$("#"+name).val("");
	});
}
  
/*====jQuery Form Plugin开始====*/
/*!
 * jQuery Form Plugin
 * version: 3.51.0-2014.06.20  Requires jQuery v1.5 or later  Copyright (c) 2014 M. Alsup
 */
!function(e){"use strict";"function"==typeof define&&define.amd?define(["jquery"],e):e("undefined"!=typeof jQuery?jQuery:window.Zepto)}(function(e){"use strict";function t(t){var r=t.data;t.isDefaultPrevented()||(t.preventDefault(),e(t.target).ajaxSubmit(r))}function r(t){var r=t.target,a=e(r);if(!a.is("[type=submit],[type=image]")){var n=a.closest("[type=submit]");if(0===n.length)return;r=n[0]}var i=this;if(i.clk=r,"image"==r.type)if(void 0!==t.offsetX)i.clk_x=t.offsetX,i.clk_y=t.offsetY;else if("function"==typeof e.fn.offset){var o=a.offset();i.clk_x=t.pageX-o.left,i.clk_y=t.pageY-o.top}else i.clk_x=t.pageX-r.offsetLeft,i.clk_y=t.pageY-r.offsetTop;setTimeout(function(){i.clk=i.clk_x=i.clk_y=null},100)}function a(){if(e.fn.ajaxSubmit.debug){var t="[jquery.form] "+Array.prototype.join.call(arguments,"");window.console&&window.console.log?window.console.log(t):window.opera&&window.opera.postError&&window.opera.postError(t)}}var n={};n.fileapi=void 0!==e("<input type='file'/>").get(0).files,n.formdata=void 0!==window.FormData;var i=!!e.fn.prop;e.fn.attr2=function(){if(!i)return this.attr.apply(this,arguments);var e=this.prop.apply(this,arguments);return e&&e.jquery||"string"==typeof e?e:this.attr.apply(this,arguments)},e.fn.ajaxSubmit=function(t){function r(r){var a,n,i=e.param(r,t.traditional).split("&"),o=i.length,s=[];for(a=0;o>a;a++)i[a]=i[a].replace(/\+/g," "),n=i[a].split("="),s.push([decodeURIComponent(n[0]),decodeURIComponent(n[1])]);return s}function o(a){for(var n=new FormData,i=0;i<a.length;i++)n.append(a[i].name,a[i].value);if(t.extraData){var o=r(t.extraData);for(i=0;i<o.length;i++)o[i]&&n.append(o[i][0],o[i][1])}t.data=null;var s=e.extend(!0,{},e.ajaxSettings,t,{contentType:!1,processData:!1,cache:!1,type:u||"POST"});t.uploadProgress&&(s.xhr=function(){var r=e.ajaxSettings.xhr();return r.upload&&r.upload.addEventListener("progress",function(e){var r=0,a=e.loaded||e.position,n=e.total;e.lengthComputable&&(r=Math.ceil(a/n*100)),t.uploadProgress(e,a,n,r)},!1),r}),s.data=null;var c=s.beforeSend;return s.beforeSend=function(e,r){r.data=t.formData?t.formData:n,c&&c.call(this,e,r)},e.ajax(s)}function s(r){function n(e){var t=null;try{e.contentWindow&&(t=e.contentWindow.document)}catch(r){a("cannot get iframe.contentWindow document: "+r)}if(t)return t;try{t=e.contentDocument?e.contentDocument:e.document}catch(r){a("cannot get iframe.contentDocument: "+r),t=e.document}return t}function o(){function t(){try{var e=n(g).readyState;a("state = "+e),e&&"uninitialized"==e.toLowerCase()&&setTimeout(t,50)}catch(r){a("Server abort: ",r," (",r.name,")"),s(k),j&&clearTimeout(j),j=void 0}}var r=f.attr2("target"),i=f.attr2("action"),o="multipart/form-data",c=f.attr("enctype")||f.attr("encoding")||o;w.setAttribute("target",p),(!u||/post/i.test(u))&&w.setAttribute("method","POST"),i!=m.url&&w.setAttribute("action",m.url),m.skipEncodingOverride||u&&!/post/i.test(u)||f.attr({encoding:"multipart/form-data",enctype:"multipart/form-data"}),m.timeout&&(j=setTimeout(function(){T=!0,s(D)},m.timeout));var l=[];try{if(m.extraData)for(var d in m.extraData)m.extraData.hasOwnProperty(d)&&l.push(e.isPlainObject(m.extraData[d])&&m.extraData[d].hasOwnProperty("name")&&m.extraData[d].hasOwnProperty("value")?e('<input type="hidden" name="'+m.extraData[d].name+'">').val(m.extraData[d].value).appendTo(w)[0]:e('<input type="hidden" name="'+d+'">').val(m.extraData[d]).appendTo(w)[0]);m.iframeTarget||v.appendTo("body"),g.attachEvent?g.attachEvent("onload",s):g.addEventListener("load",s,!1),setTimeout(t,15);try{w.submit()}catch(h){var x=document.createElement("form").submit;x.apply(w)}}finally{w.setAttribute("action",i),w.setAttribute("enctype",c),r?w.setAttribute("target",r):f.removeAttr("target"),e(l).remove()}}function s(t){if(!x.aborted&&!F){if(M=n(g),M||(a("cannot access response document"),t=k),t===D&&x)return x.abort("timeout"),void S.reject(x,"timeout");if(t==k&&x)return x.abort("server abort"),void S.reject(x,"error","server abort");if(M&&M.location.href!=m.iframeSrc||T){g.detachEvent?g.detachEvent("onload",s):g.removeEventListener("load",s,!1);var r,i="success";try{if(T)throw"timeout";var o="xml"==m.dataType||M.XMLDocument||e.isXMLDoc(M);if(a("isXml="+o),!o&&window.opera&&(null===M.body||!M.body.innerHTML)&&--O)return a("requeing onLoad callback, DOM not available"),void setTimeout(s,250);var u=M.body?M.body:M.documentElement;x.responseText=u?u.innerHTML:null,x.responseXML=M.XMLDocument?M.XMLDocument:M,o&&(m.dataType="xml"),x.getResponseHeader=function(e){var t={"content-type":m.dataType};return t[e.toLowerCase()]},u&&(x.status=Number(u.getAttribute("status"))||x.status,x.statusText=u.getAttribute("statusText")||x.statusText);var c=(m.dataType||"").toLowerCase(),l=/(json|script|text)/.test(c);if(l||m.textarea){var f=M.getElementsByTagName("textarea")[0];if(f)x.responseText=f.value,x.status=Number(f.getAttribute("status"))||x.status,x.statusText=f.getAttribute("statusText")||x.statusText;else if(l){var p=M.getElementsByTagName("pre")[0],h=M.getElementsByTagName("body")[0];p?x.responseText=p.textContent?p.textContent:p.innerText:h&&(x.responseText=h.textContent?h.textContent:h.innerText)}}else"xml"==c&&!x.responseXML&&x.responseText&&(x.responseXML=X(x.responseText));try{E=_(x,c,m)}catch(y){i="parsererror",x.error=r=y||i}}catch(y){a("error caught: ",y),i="error",x.error=r=y||i}x.aborted&&(a("upload aborted"),i=null),x.status&&(i=x.status>=200&&x.status<300||304===x.status?"success":"error"),"success"===i?(m.success&&m.success.call(m.context,E,"success",x),S.resolve(x.responseText,"success",x),d&&e.event.trigger("ajaxSuccess",[x,m])):i&&(void 0===r&&(r=x.statusText),m.error&&m.error.call(m.context,x,i,r),S.reject(x,"error",r),d&&e.event.trigger("ajaxError",[x,m,r])),d&&e.event.trigger("ajaxComplete",[x,m]),d&&!--e.active&&e.event.trigger("ajaxStop"),m.complete&&m.complete.call(m.context,x,i),F=!0,m.timeout&&clearTimeout(j),setTimeout(function(){m.iframeTarget?v.attr("src",m.iframeSrc):v.remove(),x.responseXML=null},100)}}}var c,l,m,d,p,v,g,x,y,b,T,j,w=f[0],S=e.Deferred();if(S.abort=function(e){x.abort(e)},r)for(l=0;l<h.length;l++)c=e(h[l]),i?c.prop("disabled",!1):c.removeAttr("disabled");if(m=e.extend(!0,{},e.ajaxSettings,t),m.context=m.context||m,p="jqFormIO"+(new Date).getTime(),m.iframeTarget?(v=e(m.iframeTarget),b=v.attr2("name"),b?p=b:v.attr2("name",p)):(v=e('<iframe name="'+p+'" src="'+m.iframeSrc+'" />'),v.css({position:"absolute",top:"-1000px",left:"-1000px"})),g=v[0],x={aborted:0,responseText:null,responseXML:null,status:0,statusText:"n/a",getAllResponseHeaders:function(){},getResponseHeader:function(){},setRequestHeader:function(){},abort:function(t){var r="timeout"===t?"timeout":"aborted";a("aborting upload... "+r),this.aborted=1;try{g.contentWindow.document.execCommand&&g.contentWindow.document.execCommand("Stop")}catch(n){}v.attr("src",m.iframeSrc),x.error=r,m.error&&m.error.call(m.context,x,r,t),d&&e.event.trigger("ajaxError",[x,m,r]),m.complete&&m.complete.call(m.context,x,r)}},d=m.global,d&&0===e.active++&&e.event.trigger("ajaxStart"),d&&e.event.trigger("ajaxSend",[x,m]),m.beforeSend&&m.beforeSend.call(m.context,x,m)===!1)return m.global&&e.active--,S.reject(),S;if(x.aborted)return S.reject(),S;y=w.clk,y&&(b=y.name,b&&!y.disabled&&(m.extraData=m.extraData||{},m.extraData[b]=y.value,"image"==y.type&&(m.extraData[b+".x"]=w.clk_x,m.extraData[b+".y"]=w.clk_y)));var D=1,k=2,A=e("meta[name=csrf-token]").attr("content"),L=e("meta[name=csrf-param]").attr("content");L&&A&&(m.extraData=m.extraData||{},m.extraData[L]=A),m.forceSync?o():setTimeout(o,10);var E,M,F,O=50,X=e.parseXML||function(e,t){return window.ActiveXObject?(t=new ActiveXObject("Microsoft.XMLDOM"),t.async="false",t.loadXML(e)):t=(new DOMParser).parseFromString(e,"text/xml"),t&&t.documentElement&&"parsererror"!=t.documentElement.nodeName?t:null},C=e.parseJSON||function(e){return window.eval("("+e+")")},_=function(t,r,a){var n=t.getResponseHeader("content-type")||"",i="xml"===r||!r&&n.indexOf("xml")>=0,o=i?t.responseXML:t.responseText;return i&&"parsererror"===o.documentElement.nodeName&&e.error&&e.error("parsererror"),a&&a.dataFilter&&(o=a.dataFilter(o,r)),"string"==typeof o&&("json"===r||!r&&n.indexOf("json")>=0?o=C(o):("script"===r||!r&&n.indexOf("javascript")>=0)&&e.globalEval(o)),o};return S}if(!this.length)return a("ajaxSubmit: skipping submit process - no element selected"),this;var u,c,l,f=this;"function"==typeof t?t={success:t}:void 0===t&&(t={}),u=t.type||this.attr2("method"),c=t.url||this.attr2("action"),l="string"==typeof c?e.trim(c):"",l=l||window.location.href||"",l&&(l=(l.match(/^([^#]+)/)||[])[1]),t=e.extend(!0,{url:l,success:e.ajaxSettings.success,type:u||e.ajaxSettings.type,iframeSrc:/^https/i.test(window.location.href||"")?"javascript:false":"about:blank"},t);var m={};if(this.trigger("form-pre-serialize",[this,t,m]),m.veto)return a("ajaxSubmit: submit vetoed via form-pre-serialize trigger"),this;if(t.beforeSerialize&&t.beforeSerialize(this,t)===!1)return a("ajaxSubmit: submit aborted via beforeSerialize callback"),this;var d=t.traditional;void 0===d&&(d=e.ajaxSettings.traditional);var p,h=[],v=this.formToArray(t.semantic,h);if(t.data&&(t.extraData=t.data,p=e.param(t.data,d)),t.beforeSubmit&&t.beforeSubmit(v,this,t)===!1)return a("ajaxSubmit: submit aborted via beforeSubmit callback"),this;if(this.trigger("form-submit-validate",[v,this,t,m]),m.veto)return a("ajaxSubmit: submit vetoed via form-submit-validate trigger"),this;var g=e.param(v,d);p&&(g=g?g+"&"+p:p),"GET"==t.type.toUpperCase()?(t.url+=(t.url.indexOf("?")>=0?"&":"?")+g,t.data=null):t.data=g;var x=[];if(t.resetForm&&x.push(function(){f.resetForm()}),t.clearForm&&x.push(function(){f.clearForm(t.includeHidden)}),!t.dataType&&t.target){var y=t.success||function(){};x.push(function(r){var a=t.replaceTarget?"replaceWith":"html";e(t.target)[a](r).each(y,arguments)})}else t.success&&x.push(t.success);if(t.success=function(e,r,a){for(var n=t.context||this,i=0,o=x.length;o>i;i++)x[i].apply(n,[e,r,a||f,f])},t.error){var b=t.error;t.error=function(e,r,a){var n=t.context||this;b.apply(n,[e,r,a,f])}}if(t.complete){var T=t.complete;t.complete=function(e,r){var a=t.context||this;T.apply(a,[e,r,f])}}var j=e("input[type=file]:enabled",this).filter(function(){return""!==e(this).val()}),w=j.length>0,S="multipart/form-data",D=f.attr("enctype")==S||f.attr("encoding")==S,k=n.fileapi&&n.formdata;a("fileAPI :"+k);var A,L=(w||D)&&!k;t.iframe!==!1&&(t.iframe||L)?t.closeKeepAlive?e.get(t.closeKeepAlive,function(){A=s(v)}):A=s(v):A=(w||D)&&k?o(v):e.ajax(t),f.removeData("jqxhr").data("jqxhr",A);for(var E=0;E<h.length;E++)h[E]=null;return this.trigger("form-submit-notify",[this,t]),this},e.fn.ajaxForm=function(n){if(n=n||{},n.delegation=n.delegation&&e.isFunction(e.fn.on),!n.delegation&&0===this.length){var i={s:this.selector,c:this.context};return!e.isReady&&i.s?(a("DOM not ready, queuing ajaxForm"),e(function(){e(i.s,i.c).ajaxForm(n)}),this):(a("terminating; zero elements found by selector"+(e.isReady?"":" (DOM not ready)")),this)}return n.delegation?(e(document).off("submit.form-plugin",this.selector,t).off("click.form-plugin",this.selector,r).on("submit.form-plugin",this.selector,n,t).on("click.form-plugin",this.selector,n,r),this):this.ajaxFormUnbind().bind("submit.form-plugin",n,t).bind("click.form-plugin",n,r)},e.fn.ajaxFormUnbind=function(){return this.unbind("submit.form-plugin click.form-plugin")},e.fn.formToArray=function(t,r){var a=[];if(0===this.length)return a;var i,o=this[0],s=this.attr("id"),u=t?o.getElementsByTagName("*"):o.elements;if(u&&!/MSIE [678]/.test(navigator.userAgent)&&(u=e(u).get()),s&&(i=e(':input[form="'+s+'"]').get(),i.length&&(u=(u||[]).concat(i))),!u||!u.length)return a;var c,l,f,m,d,p,h;for(c=0,p=u.length;p>c;c++)if(d=u[c],f=d.name,f&&!d.disabled)if(t&&o.clk&&"image"==d.type)o.clk==d&&(a.push({name:f,value:e(d).val(),type:d.type}),a.push({name:f+".x",value:o.clk_x},{name:f+".y",value:o.clk_y}));else if(m=e.fieldValue(d,!0),m&&m.constructor==Array)for(r&&r.push(d),l=0,h=m.length;h>l;l++)a.push({name:f,value:m[l]});else if(n.fileapi&&"file"==d.type){r&&r.push(d);var v=d.files;if(v.length)for(l=0;l<v.length;l++)a.push({name:f,value:v[l],type:d.type});else a.push({name:f,value:"",type:d.type})}else null!==m&&"undefined"!=typeof m&&(r&&r.push(d),a.push({name:f,value:m,type:d.type,required:d.required}));if(!t&&o.clk){var g=e(o.clk),x=g[0];f=x.name,f&&!x.disabled&&"image"==x.type&&(a.push({name:f,value:g.val()}),a.push({name:f+".x",value:o.clk_x},{name:f+".y",value:o.clk_y}))}return a},e.fn.formSerialize=function(t){return e.param(this.formToArray(t))},e.fn.fieldSerialize=function(t){var r=[];return this.each(function(){var a=this.name;if(a){var n=e.fieldValue(this,t);if(n&&n.constructor==Array)for(var i=0,o=n.length;o>i;i++)r.push({name:a,value:n[i]});else null!==n&&"undefined"!=typeof n&&r.push({name:this.name,value:n})}}),e.param(r)},e.fn.fieldValue=function(t){for(var r=[],a=0,n=this.length;n>a;a++){var i=this[a],o=e.fieldValue(i,t);null===o||"undefined"==typeof o||o.constructor==Array&&!o.length||(o.constructor==Array?e.merge(r,o):r.push(o))}return r},e.fieldValue=function(t,r){var a=t.name,n=t.type,i=t.tagName.toLowerCase();if(void 0===r&&(r=!0),r&&(!a||t.disabled||"reset"==n||"button"==n||("checkbox"==n||"radio"==n)&&!t.checked||("submit"==n||"image"==n)&&t.form&&t.form.clk!=t||"select"==i&&-1==t.selectedIndex))return null;if("select"==i){var o=t.selectedIndex;if(0>o)return null;for(var s=[],u=t.options,c="select-one"==n,l=c?o+1:u.length,f=c?o:0;l>f;f++){var m=u[f];if(m.selected){var d=m.value;if(d||(d=m.attributes&&m.attributes.value&&!m.attributes.value.specified?m.text:m.value),c)return d;s.push(d)}}return s}return e(t).val()},e.fn.clearForm=function(t){return this.each(function(){e("input,select,textarea",this).clearFields(t)})},e.fn.clearFields=e.fn.clearInputs=function(t){var r=/^(?:color|date|datetime|email|month|number|password|range|search|tel|text|time|url|week)$/i;return this.each(function(){var a=this.type,n=this.tagName.toLowerCase();r.test(a)||"textarea"==n?this.value="":"checkbox"==a||"radio"==a?this.checked=!1:"select"==n?this.selectedIndex=-1:"file"==a?/MSIE/.test(navigator.userAgent)?e(this).replaceWith(e(this).clone(!0)):e(this).val(""):t&&(t===!0&&/hidden/.test(a)||"string"==typeof t&&e(this).is(t))&&(this.value="")})},e.fn.resetForm=function(){return this.each(function(){("function"==typeof this.reset||"object"==typeof this.reset&&!this.reset.nodeType)&&this.reset()})},e.fn.enable=function(e){return void 0===e&&(e=!0),this.each(function(){this.disabled=!e})},e.fn.selected=function(t){return void 0===t&&(t=!0),this.each(function(){var r=this.type;if("checkbox"==r||"radio"==r)this.checked=t;else if("option"==this.tagName.toLowerCase()){var a=e(this).parent("select");t&&a[0]&&"select-one"==a[0].type&&a.find("option").selected(!1),this.selected=t}})},e.fn.ajaxSubmit.debug=!1});
/*====jQuery Form Plugin 结束====*/

/*====powerFloat  开始====*/
/*! 
2012-01-28 v1.6.1	
target参数支持funtion类型，以实现类似动态Ajax地址功能
作者：张鑫旭 by zhangxinxu
*/
(function($) {
	$.fn.powerFloat = function(options) {
		return $(this).each(function() {
			var s = $.extend({}, defaults, options || {});
			var init = function(pms, trigger) {
				if (o.target && o.target.css("display") !== "none") {
					o.targetHide();
				}		
				o.s = pms;
				o.trigger = trigger;				
			}, hoverTimer;

			switch (s.eventType) {
				case "hover": {
					$(this).hover(function() {					
						if (o.timerHold) {
							o.flagDisplay = true;
						}
												
						var numShowDelay = parseInt(s.showDelay, 10);
						
						init(s, $(this));
						//鼠标hover延时
						if (numShowDelay) {
							if (hoverTimer) {
								clearTimeout(hoverTimer);
							}
							hoverTimer = setTimeout(function() {
								o.targetGet.call(o);
							}, numShowDelay);	
						} else {
							o.targetGet();	
						}
					}, function() {
						if (hoverTimer) {
							clearTimeout(hoverTimer);
						}
						if (o.timerHold) {
							clearTimeout(o.timerHold);	
						}

						o.flagDisplay = false;
						o.targetHold();
					});
					if (s.hoverFollow) {
						//鼠标跟随	
						$(this).mousemove(function(e) {
							o.cacheData.left = e.pageX;
							o.cacheData.top = e.pageY;
							o.targetGet.call(o);
							return false;
						});
					}
					break;	
				}
				case "click": {
					$(this).click(function(e) {
						if (o.display && o.trigger && e.target === o.trigger.get(0)) {
							o.flagDisplay = false;	
							o.displayDetect();
						} else {
							init(s, $(this));		
							o.targetGet();
								
							if (!$(document).data("mouseupBind")) {
								$(document).bind("mouseup", function(e) {
									var flag = false;
									if (o.trigger) {
										var idTarget = o.target.attr("id");
										if (!idTarget) {
											idTarget = "R_" + Math.random();
											o.target.attr("id", idTarget);
										}
										$(e.target).parents().each(function() {
											if ($(this).attr("id") === idTarget) {
												flag = true;
											}
										});
										if (s.eventType === "click" && o.display && e.target != o.trigger.get(0) && !flag) {
											o.flagDisplay = false;	
											o.displayDetect();
										}
									}
									return false;
								}).data("mouseupBind", true);
							}
						}						
					});
					break;
				}
				case "focus": {
					$(this).focus(function() {
						var self = $(this);
						setTimeout(function() {
							init(s, self);
							o.targetGet();	
						}, 200);
					}).blur(function() {
						o.flagDisplay = false;
						setTimeout(function() {
							o.displayDetect();
						}, 190);
					});	
					break;
				}
				default: {
					init(s, $(this));
					o.targetGet();// 放置页面点击后显示的浮动内容隐掉
					$(document).unbind("mouseup").data("mouseupBind", false);
				}
			}
		});
	};
	
	var o = {
		targetGet: function() {
			//一切显示的触发来源
			if (!this.trigger) { return this; }
			//动态改变属性值后，attr无法获取最新的值
			//var attr = this.trigger.attr(this.s.targetAttr);
			var attr = this.trigger.prop(this.s.targetAttr); //如果prop属性不是固有属性则会返回undefined，所以必须再次通过attr获取
			if('undefined'==typeof(attr)){
				attr = this.trigger.attr(this.s.targetAttr);
			}
			var target = typeof this.s.target == "function"? this.s.target.call(this.trigger): this.s.target;

			switch (this.s.targetMode) {
				case "common": {
					if (target) {
						var type = typeof(target);
						if (type === "object") {
							if (target.size()) {
								o.target = target.eq(0);
							}
						} else if (type === "string") {
							if ($(target).size()) {
								o.target = $(target).eq(0);
							}	
						}
					} else {
						if (attr && $("#" + attr).size()) {
							o.target = $("#" + attr);
						}
					}
					if (o.target) {
						o.targetShow();
					} else {
						return this;	
					}
					
					break;
				}
				case "ajax": {
					//ajax元素，如图片，页面地址
					var url = target || attr;
					this.targetProtect = false;
					
					if (!url) { return; }
					//by wang 不是图片，不预览
					if (!/(\.jpg|\.png|\.gif|\.bmp|\.jpeg|\.tiff|\.tif)$/i.test(url)) {
						return;
					}
					
					if (!o.cacheData[url]) {
						o.loading();
					}
					//优先认定为图片加载
					var tempImage = new Image();
					tempImage.onload = function() {
						var w = tempImage.width, h = tempImage.height;
						var winw = $(window).width(), winh = $(window).height();
						var imgScale = w / h, winScale = winw / winh;
						if (imgScale > winScale) {
							//图片的宽高比大于显示屏幕
							if (w > winw / 2) {
								w = winw / 2;
								h = w / imgScale;	
							}
						} else {
							//图片高度较高
							if (h > winh / 2) {
								h = winh / 2;
								w = h * imgScale;
							}
						}
						var imgHtml = '<img class="float_ajax_image" src="' + url + '" width="' + w + '" height = "' + h + '" />';
						o.cacheData[url] = true;
						o.target = $(imgHtml);
						o.targetShow();
					};
					tempImage.onerror = function() {
						//如果图片加载失败，两种可能，一是100%图片，则提示；否则作为页面加载
						if (/(\.jpg|\.png|\.gif|\.bmp|\.jpeg)$/i.test(url)) {
							o.target = $('<div class="float_ajax_error">图片加载失败。</div>');
							o.targetShow();
						} else {
							$.ajax({
								url: url,
								success: function(data) {
									if (typeof(data) === "string") {
										o.cacheData[url] = true;
										o.target = $('<div class="float_ajax_data">' + data + '</div>');
										o.targetShow();
									}
								},
								error: function() {
									o.target = $('<div class="float_ajax_error">数据没有加载成功。</div>');
									o.targetShow();
								}
							});
						}
					};
					tempImage.src = url;
					
					break;
				}
				case "list": {
					//下拉列表
					var targetHtml = '<ul class="float_list_ul">',  arrLength;
					if ($.isArray(target) && (arrLength = target.length)) {
						$.each(target, function(i, obj) {
							var list = "", strClass = "", text, href;
							if (i === 0) {
								strClass = ' class="float_list_li_first"';
							}
							if (i === arrLength - 1) {
								strClass = ' class="float_list_li_last"';	
							}
							if (typeof(obj) === "object" && (text = obj.text.toString())) {
								if (href = (obj.href || "javascript:")) {
									list = '<a href="' + href + '" class="float_list_a">' + text + '</a>';	
								} else {
									list = text;	
								}
							} else if (typeof(obj) === "string" && obj) {
								list = obj;
							}
							if (list) {
								targetHtml += '<li' + strClass + '>' + list + '</li>';	
							}
						});
					} else {
						targetHtml += '<li class="float_list_null">列表无数据。</li>';	
					}
					targetHtml += '</ul>';
					o.target = $(targetHtml);
					this.targetProtect = false;	
					o.targetShow();	
					break;	
				}
				case "remind": {
					//内容均是字符串
					var strRemind = target || attr;
					this.targetProtect = false;	
					if (typeof(strRemind) === "string") {
						o.target = $('<span>' + strRemind + '</span>');
						o.targetShow();	
					}
					break;
				}
				default: {
					var objOther = target || attr, type = typeof(objOther);
					if (objOther) {
						if (type === "string") {
							//选择器
							if (/^.[^:#\[\.,]*$/.test(objOther)) {
								if ($(objOther).size()) {
									o.target = $(objOther).eq(0);
									this.targetProtect = true;	
								} else if ($("#" + objOther).size()) {
									o.target = $("#" + objOther).eq(0);
									this.targetProtect = true;
								} else {
									o.target = $('<div>' + objOther + '</div>');
									this.targetProtect = false;		
								}
							} else {
								o.target = $('<div>' + objOther + '</div>');
								this.targetProtect = false;
							}
							
							o.targetShow();	
						} else if (type === "object") {
							if (!$.isArray(objOther) && objOther.size()) {
								o.target = objOther.eq(0);
								this.targetProtect = true;
								o.targetShow();	
							}
						}
					}
				}
			}
			return this;
		},
		container: function() {
			//容器(如果有)重装target
			var cont = this.s.container, mode = this.s.targetMode || "mode";
			if (mode === "ajax" || mode === "remind") {
				//显示三角
				this.s.sharpAngle = true;	
			} else {
				this.s.sharpAngle = false;
			}
			//是否反向
			if (this.s.reverseSharp) {
				this.s.sharpAngle = !this.s.sharpAngle;	
			}
			
			if (mode !== "common") {
				//common模式无新容器装载
				if (cont === null) {
					cont = "plugin";	
				} 
				if ( cont === "plugin" ) {
					if (!$("#floatBox_" + mode).size()) {
						$('<div id="floatBox_' + mode + '" class="float_' + mode + '_box"></div>').appendTo($("body")).hide();
					}
					cont = $("#floatBox_" + mode);	
				} 
				
				if (cont && typeof(cont) !== "string" && cont.size()) {
					if (this.targetProtect) {
						o.target.show().css("position", "static");
					}
					o.target = cont.empty().append(o.target);
				}
			}
			return this;
		},
		setWidth: function() {
			var w = this.s.width;
			if (w === "auto") {
				if (this.target.get(0).style.width) {
					this.target.css("width", "auto");	
				}
			} else if (w === "inherit") {
				this.target.width(this.trigger.width());
			} else {
				this.target.css("width", w);	
			}
			return this;
		},
		position: function() {
			if (!this.trigger || !this.target) {
				return this;
			}
			var pos, tri_h = 0, tri_w = 0, cor_w = 0, cor_h = 0, tri_l, tri_t, tar_l, tar_t, cor_l, cor_t,
				tar_h = this.target.data("height"), tar_w = this.target.data("width"),
				st = $(window).scrollTop(),
				
				off_x = parseInt(this.s.offsets.x, 10) || 0, off_y = parseInt(this.s.offsets.y, 10) || 0,
				mousePos = this.cacheData;

			//缓存目标对象高度，宽度，提高鼠标跟随时显示性能，元素隐藏时缓存清除
			if (!tar_h) {
				tar_h = this.target.outerHeight();
				if (this.s.hoverFollow) {
					this.target.data("height", tar_h);
				}
			}
			if (!tar_w) {
				tar_w = this.target.outerWidth();
				if (this.s.hoverFollow) {
					this.target.data("width", tar_w);
				}
			}
			
			pos = this.trigger.offset();
			tri_h = this.trigger.outerHeight();
			tri_w = this.trigger.outerWidth();
			tri_l = pos.left;
			tri_t = pos.top;
			
			var funMouseL = function() {
				if (tri_l < 0) {
					tri_l = 0;
				} else if (tri_l + tri_h > $(window).width()) {
					tri_l = $(window).width() - tri_w;	
				}
			}, funMouseT = function() {
				if (tri_t < 0) {
					tri_t = 0;
				} else if (tri_t + tri_h > $(document).height()) {
					tri_t = $(document).height() - tri_h;
				}
			};
			//如果是鼠标跟随
			if (this.s.hoverFollow && mousePos.left && mousePos.top) {
				if (this.s.hoverFollow === "x") {
					//水平方向移动，说明纵坐标固定
					tri_l = mousePos.left
					funMouseL();
				} else if (this.s.hoverFollow === "y") {
					//垂直方向移动，说明横坐标固定，纵坐标跟随鼠标移动
					tri_t = mousePos.top;
					funMouseT();
				} else {
					tri_l = mousePos.left;
					tri_t = mousePos.top;
					funMouseL();
					funMouseT();
				}	
			}	
			
			
			var arrLegalPos = ["4-1", "1-4", "5-7", "2-3", "2-1", "6-8", "3-4", "4-3", "8-6", "1-2", "7-5", "3-2"],
				align = this.s.position, alignMatch = false, strDirect;
			$.each(arrLegalPos, function(i, n) {
				if (n === align) {
					alignMatch = true;	
					return;
				}
			});
			if (!alignMatch) {
				align = "4-1";
			}
			
			var funDirect = function(a) {
				var dir = "bottom";
				//确定方向
				switch (a) {
					case "1-4": case "5-7": case "2-3": {
						dir = "top";
						break;
					}
					case "2-1": case "6-8": case "3-4": {
						dir = "right";
						break;
					}
					case "1-2": case "8-6": case "4-3": {
						dir = "left";
						break;
					}
					case "4-1": case "7-5": case "3-2": {
						dir = "bottom";
						break;
					}
				}
				return dir;
			};
			
			//居中判断
			var funCenterJudge = function(a) {
				if (a === "5-7" || a === "6-8" || a === "8-6" || a === "7-5") {
					return true;
				}
				return false;
			};
			
			var funJudge = function(dir) {
				var totalHeight = 0, totalWidth = 0, flagCorner = (o.s.sharpAngle && o.corner) ? true: false;
				if (dir === "right") {
					totalWidth = tri_l + tri_w + tar_w + off_x;
					if (flagCorner) {
						totalWidth += o.corner.width();
					}	
					if (totalWidth > $(window).width()) {
						return false;	
					}
				} else if (dir === "bottom") {
					totalHeight = tri_t + tri_h + tar_h + off_y;
					if (flagCorner) {
						totalHeight += 	o.corner.height();
					}
					if (totalHeight > st + $(window).height()) {
						return false;	
					}
				} else if (dir === "top") {
					totalHeight = tar_h + off_y;
					if (flagCorner) {
						totalHeight += 	o.corner.height();
					}
					if (totalHeight > tri_t - st) {
						return false;	
					} 
				} else if (dir === "left") {
					totalWidth = tar_w + off_x;
					if (flagCorner) {
						totalWidth += o.corner.width();
					}
					if (totalWidth > tri_l) {
						return false;	
					}
				}
				return true;
			};
			//此时的方向
			strDirect = funDirect(align);

			if (this.s.sharpAngle) {
				//创建尖角
				this.createSharp(strDirect);
			}
			
			//边缘过界判断
			if (this.s.edgeAdjust) {
				//根据位置是否溢出显示界面重新判定定位
				if (funJudge(strDirect)) {
					//该方向不溢出
					(function() {
						if (funCenterJudge(align)) { return; }
						var obj = {
							top: {
								right: "2-3",
								left: "1-4"	
							},
							right: {
								top: "2-1",
								bottom: "3-4"
							},
							bottom: {
								right: "3-2",
								left: "4-1"	
							},
							left: {
								top: "1-2",
								bottom: "4-3"	
							}
						};
						var o = obj[strDirect], name;
						if (o) {
							for (var name in o) {
								if (!funJudge(name)) {
									align = o[name];
								}
							}
						}
					})();
				} else {
					//该方向溢出
					(function() {
						if (funCenterJudge(align)) { 
							var center = {
								"5-7": "7-5",
								"7-5": "5-7",
								"6-8": "8-6",
								"8-6": "6-8"
							};
							align = center[align];
						} else {
							var obj = {
								top: {
									left: "3-2",
									right: "4-1"	
								},
								right: {
									bottom: "1-2",
									top: "4-3"
								},
								bottom: {
									left: "2-3",
									right: "1-4"
								},
								left: {
									bottom: "2-1",
									top: "3-4"
								}
							};
							var o = obj[strDirect], arr = [];
							for (var name in o) {
								arr.push(name);
							}
							if (funJudge(arr[0]) || !funJudge(arr[1])) {
								align = o[arr[0]];
							} else {
								align = o[arr[1]];	
							}
						}
					})();
				}
			}
			
			//已确定的尖角
			var strNewDirect = funDirect(align), strFirst = align.split("-")[0];
			if (this.s.sharpAngle) {
				//创建尖角
				this.createSharp(strNewDirect);
				cor_w = this.corner.width(), cor_h = this.corner.height();
			}

			//确定left, top值
			if (this.s.hoverFollow) {
				//如果鼠标跟随
				if (this.s.hoverFollow === "x") {
					//仅水平方向跟随
					tar_l = tri_l + off_x;
					if (strFirst === "1" || strFirst === "8" || strFirst === "4" ) {
						//最左
						tar_l = tri_l - (tar_w - tri_w) / 2 + off_x;
					} else {
						//右侧
						tar_l = tri_l - (tar_w - tri_w) + off_x;
					}
					
					//这是垂直位置，固定不动
					if (strFirst === "1" || strFirst === "5" || strFirst === "2" ) {
						tar_t = tri_t - off_y - tar_h - cor_h;
						//尖角
						cor_t = tri_t - cor_h - off_y - 1;
						
					} else {
						//下方
						tar_t = tri_t + tri_h + off_y + cor_h;
						cor_t = tri_t + tri_h + off_y + 1;
					}
					cor_l = pos.left - (cor_w - tri_w) / 2;
				} else if (this.s.hoverFollow === "y") {
					//仅垂直方向跟随
					if (strFirst === "1" || strFirst === "5" || strFirst === "2" ) {
						//顶部
						tar_t = tri_t - (tar_h - tri_h) / 2 + off_y;
					} else {
						//底部
						tar_t = tri_t - (tar_h - tri_h) + off_y;
					}
							
					if (strFirst === "1" || strFirst === "8" || strFirst === "4" ) {
						//左侧
						tar_l = tri_l - tar_w - off_x - cor_w;
						cor_l = tri_l - cor_w - off_x - 1;
					} else {
						//右侧
						tar_l = tri_l + tri_w - off_x + cor_w;
						cor_l = tri_l + tri_w + off_x + 1;
					}
					cor_t = pos.top - (cor_h - tri_h) / 2;
				} else {
					tar_l = tri_l + off_x;
					tar_t = tri_t + off_y;	
				}
				
			} else {
				switch (strNewDirect) {
					case "top": {
						tar_t = tri_t - off_y - tar_h - cor_h;
						if (strFirst == "1") {
							tar_l = tri_l - off_x;	
						} else if (strFirst === "5") {
							tar_l = tri_l - (tar_w - tri_w) / 2 - off_x;
						} else {
							tar_l = tri_l - (tar_w - tri_w) - off_x;
						}
						cor_t = tri_t - cor_h - off_y - 1;
						cor_l = tri_l - (cor_w - tri_w) / 2;
						break;
					}
					case "right": {
						tar_l = tri_l + tri_w + off_x + cor_w;
						if (strFirst == "2") {
							tar_t = tri_t + off_y;	
						} else if (strFirst === "6") {
							tar_t = tri_t - (tar_h - tri_h) / 2 + off_y;
						} else {
							tar_t = tri_t - (tar_h - tri_h) + off_y;
						}
						cor_l = tri_l + tri_w + off_x + 1;
						cor_t = tri_t - (cor_h - tri_h) / 2;
						break;
					}
					case "bottom": {
						tar_t = tri_t + tri_h + off_y + cor_h;
						if (strFirst == "4") {
							tar_l = tri_l + off_x;	
						} else if (strFirst === "7") {
							tar_l = tri_l - (tar_w - tri_w) / 2 + off_x;
						} else {
							tar_l = tri_l - (tar_w - tri_w) + off_x;
						}
						cor_t = tri_t + tri_h + off_y + 1;
						cor_l = tri_l - (cor_w - tri_w) / 2;
						break;
					}
					case "left": {
						tar_l = tri_l - tar_w - off_x - cor_w;
						if (strFirst == "2") {
							tar_t = tri_t - off_y;	
						} else if (strFirst === "6") {
							tar_t = tri_t - (tar_w - tri_w) / 2 - off_y;
						} else {
							tar_t = tri_t - (tar_h - tri_h) - off_y;
						}
						cor_l = tar_l + cor_w;
						cor_t = tri_t - (tar_w - cor_w) / 2;
						break;
					}
				}
			}
			//尖角的显示
			if (cor_h && cor_w && this.corner) {
				this.corner.css({
					left: cor_l,
					top: cor_t,
					zIndex: this.s.zIndex + 1	
				});
			}
			//浮动框显示
			this.target.css({
				position: "absolute",
				left: tar_l,
				top: tar_t,
				zIndex: this.s.zIndex
			});
			return this;
		},
		createSharp: function(dir) {
			var bgColor, bdColor, color1 = "", color2 = "";
			var objReverse = {
				left: "right",
				right: "left",
				bottom: "top",
				top: "bottom"	
			}, dirReverse = objReverse[dir] || "top";
			
			if (this.target) {
				bgColor = this.target.css("background-color");
				if (parseInt(this.target.css("border-" + dirReverse + "-width")) > 0) {
					bdColor = this.target.css("border-" + dirReverse + "-color");
				} 
				
				if (bdColor &&  bdColor !== "transparent") {
					color1 = 'style="color:' + bdColor + ';"';
				} else {
					color1 = 'style="display:none;"';
				}
				if (bgColor && bgColor !== "transparent") {
					color2 = 'style="color:' + bgColor + ';"';
				}else {
					color2 = 'style="display:none;"';
				}
			}
			
			var html = '<div id="floatCorner_' + dir + '" class="float_corner float_corner_' + dir + '">' +
					'<span class="corner corner_1" ' + color1 + '>◆</span>' +
					'<span class="corner corner_2" ' + color2 + '>◆</span>' +
				'</div>';
			if (!$("#floatCorner_" + dir).size()) {
				$("body").append($(html));	
			}
			this.corner = $("#floatCorner_" + dir);
			return this;
		},
		targetHold: function() {
			if (this.s.hoverHold) {	
				var delay = parseInt(this.s.hideDelay, 10) || 200;
				if (this.target) {
					this.target.hover(function() {
						o.flagDisplay = true;
					}, function() {
						if (o.timerHold) {
							clearTimeout(o.timerHold);	
						}
						o.flagDisplay = false;
						o.targetHold();	
					});
				}
				
				o.timerHold = setTimeout(function() {
					o.displayDetect.call(o);	
				}, delay);
			} else {
				this.displayDetect();	
			}
			return this;
		},
		loading: function() {
			this.target = $('<div class="float_loading"></div>');
			this.targetShow();
			this.target.removeData("width").removeData("height");
			return this;
		},
		displayDetect: function() {
			//显示与否检测与触发
			if (!this.flagDisplay && this.display) {
				this.targetHide();
				this.timerHold = null;
			}
			return this;
		},
		targetShow: function() {
			o.cornerClear();
			this.display = true;
			this.container().setWidth().position();
			this.target.show();
			if ($.isFunction(this.s.showCall)) {
				this.s.showCall.call(this.trigger, this.target);	
			}
			return this;
		},
		targetHide: function() {
			this.display = false;
			this.targetClear();
			this.cornerClear();
			if ($.isFunction(this.s.hideCall)) {
				this.s.hideCall.call(this.trigger);	
			}
			this.target = null;
			this.trigger = null;
			this.s = {};
			this.targetProtect = false;
			return this;
		},
		targetClear: function() {
			if (this.target) {
				if (this.target.data("width")) {
					this.target.removeData("width").removeData("height");	
				}
				if (this.targetProtect) {
					//保护孩子
					this.target.children().hide().appendTo($("body"));
				} 
				this.target.unbind().hide();
			}
		},
		cornerClear: function() {
			if (this.corner) {
				//使用remove避免潜在的尖角颜色冲突问题
				this.corner.remove();
			}
		},
		target: null,
		trigger: null,
		s: {},
		cacheData: {},
		targetProtect: false
	};
	
	$.powerFloat = {};
	$.powerFloat.hide = function() {
		o.targetHide();	
	};
	
	var defaults  = {
		width: "auto", //可选参数：inherit，数值(px)
		offsets: {x: 0,y: 0	},
		zIndex: 99999,
		
		eventType: "hover", //事件类型，其他可选参数有：click, focus
		
		showDelay: 0, //鼠标hover显示延迟
		hideDelay: 0, //鼠标移出隐藏延时
		
		hoverHold: true,
		hoverFollow: false, //true或是关键字x, y
		
		targetMode: "common", //浮动层的类型，其他可选参数有：ajax, list, remind
		target: null, //target对象获取来源，优先获取，如果为null，则从targetAttr中获取。
		targetAttr: "rel", //target对象获取来源，当targetMode为list时无效
		
		container: null, //转载target的容器，可以使用"plugin"关键字，则表示使用插件自带容器类型
		reverseSharp: false, //是否反向小三角的显示，默认ajax, remind是显示三角的，其他如list和自定义形式是不显示的
		
		position: "4-1", //trigger-target
		edgeAdjust: true, //边缘位置自动调整
		
		showCall: $.noop,
		hideCall: $.noop
	};
})(jQuery);
/*====powerFloat  结束====*/


/*! jquery.cookie v1.4.1 开始 作者：https://github.com/jquery/jquery/ MIT协议 */
!function(a){"function"==typeof define&&define.amd?define(["jquery"],a):"object"==typeof exports?a(require("jquery")):a(jQuery)}(function(a){function b(a){return h.raw?a:encodeURIComponent(a)}function c(a){return h.raw?a:decodeURIComponent(a)}function d(a){return b(h.json?JSON.stringify(a):String(a))}function e(a){0===a.indexOf('"')&&(a=a.slice(1,-1).replace(/\\"/g,'"').replace(/\\\\/g,"\\"));try{return a=decodeURIComponent(a.replace(g," ")),h.json?JSON.parse(a):a}catch(b){}}function f(b,c){var d=h.raw?b:e(b);return a.isFunction(c)?c(d):d}var g=/\+/g,h=a.cookie=function(e,g,i){if(void 0!==g&&!a.isFunction(g)){if(i=a.extend({},h.defaults,i),"number"==typeof i.expires){var j=i.expires,k=i.expires=new Date;k.setTime(+k+864e5*j)}return document.cookie=[b(e),"=",d(g),i.expires?"; expires="+i.expires.toUTCString():"",i.path?"; path="+i.path:"",i.domain?"; domain="+i.domain:"",i.secure?"; secure":""].join("")}for(var l=e?void 0:{},m=document.cookie?document.cookie.split("; "):[],n=0,o=m.length;o>n;n++){var p=m[n].split("="),q=c(p.shift()),r=p.join("=");if(e&&e===q){l=f(r,g);break}e||void 0===(r=f(r))||(l[q]=r)}return l};h.defaults={},a.removeCookie=function(b,c){return void 0===a.cookie(b)?!1:(a.cookie(b,"",a.extend({},c,{expires:-1})),!a.cookie(b))}});
/*! jquery.cookie v1.4.1 结束*/


/*! jquery.colorpicker.js 开始*/
(function($) {
    var ColorHex=new Array('00','33','66','99','CC','FF');
    var SpColorHex=new Array('FF0000','00FF00','0000FF','FFFF00','00FFFF','FF00FF');
    $.fn.colorpicker = function(options) {
        var opts = jQuery.extend({}, jQuery.fn.colorpicker.defaults, options);
        initColor();
        return this.each(function(){
            var obj = $(this);
            obj.bind(opts.event,function(){
                //定位
                var ttop  = $(this).offset().top;     //控件的定位点高
                var thei  = $(this).height();  //控件本身的高
                var tleft = $(this).offset().left;    //控件的定位点宽
                $("#colorpanel").css({
                    top:ttop+thei+5,
                    left:tleft
                }).show();
                var target = opts.target ? $(opts.target) : obj;
                if(target.data("color") == null){
                    target.data("color",target.css("color"));
                }
                if(target.data("value") == null){
                    target.data("value",target.val());
                }
          
                $("#_creset").bind("click",function(){
                    target.css("color", target.data("color")).val(target.data("value"));
                    $("#colorpanel").hide();
                    opts.reset(obj);
                });
          
                $("#CT tr td").unbind("click").mouseover(function(){
                    var color=$(this).css("background-color");
                    $("#DisColor").css("background",color);
                    $("#HexColor").val($(this).attr("rel"));
                }).click(function(){
                    var color=$(this).attr("rel");
                    color = opts.ishex ? color : getRGBColor(color);
                    if(opts.fillcolor) target.val(color);
                    target.css("color",color);
                    $("#colorpanel").hide();
                    $("#_creset").unbind("click");
                    opts.success(obj,color);
                });
          
            });
        });
    
        function initColor(){
            $("body").append('<div id="colorpanel" style="position: absolute; display: none;z-index:99999"></div>');
            var colorTable = '';
            var colorValue = '';
            for(i=0;i<2;i++){
                for(j=0;j<6;j++){
                    colorTable=colorTable+'<tr height=12>'
                    colorTable=colorTable+'<td width=11 rel="#000000" style="background-color:#000000">'
                    colorValue = i==0 ? ColorHex[j]+ColorHex[j]+ColorHex[j] : SpColorHex[j];
                    colorTable=colorTable+'<td width=11 rel="#'+colorValue+'" style="background-color:#'+colorValue+'">'
                    colorTable=colorTable+'<td width=11 rel="#000000" style="background-color:#000000">'
                    for (k=0;k<3;k++){
                        for (l=0;l<6;l++){
                            colorValue = ColorHex[k+i*3]+ColorHex[l]+ColorHex[j];
                            colorTable=colorTable+'<td width=11 rel="#'+colorValue+'" style="background-color:#'+colorValue+'">'
                        }
                    }
                }
            }
            colorTable='<table width=253 border="0" cellspacing="0" cellpadding="0" style="border:1px solid #000;">'
            +'<tr height=30><td colspan=21 bgcolor=#cccccc>'
            +'<table cellpadding="0" cellspacing="1" border="0" style="border-collapse: collapse">'
            +'<tr><td width="3"><td><input type="text" id="DisColor" size="6" disabled style="border:solid 1px #000000;background-color:#ffff00"></td>'
            +'<td width="3"><td><input type="text" id="HexColor" size="7" style="border:inset 1px;font-family:Arial;" value="#000000"><a href="javascript:void(0);" id="_cclose">关闭</a> | <a href="javascript:void(0);" id="_creset">清除</a></td></tr></table></td></table>'
            +'<table id="CT" border="1" cellspacing="0" cellpadding="0" style="border-collapse: collapse" bordercolor="000000"  style="cursor:pointer;">'
            +colorTable+'</table>';
            $("#colorpanel").html(colorTable);
            $("#_cclose").on('click',function(){
                $("#colorpanel").hide();
                return false;
            }).css({ "font-size":"12px","padding-left":"20px" });
			
			//点击其他地方，关闭颜色框==============================
			/* 会引起Forced reflow while executing JavaScript took 34ms问题
			$(document).bind("mousedown", function (e) {
				var obj = $("#colorpanel");
				var offset = obj.offset();
				var x1 = offset.left;
				var x2 = offset.left + obj.width();
				var y1 = offset.top - 30;  //弹框永远向下，所以只需要在上面增加即可
				var y2 = offset.top + obj.height();
				//$(".WaterPreview").css( {position:"absolute", left:x1+"px", top:y1+"px", width: (x2-x1)+"px", height:(y2-y1)+"px"} );
				if(e.pageX < x1 || e.pageX > x2 || e.pageY < y1 || e.pageY > y2){
					obj.hide();
				}
			});
			*/
			//==============================================
        }
        
        function getRGBColor(color) {
            var result;
            if ( color && color.constructor == Array && color.length == 3 )
                color = color;
            if (result = /rgb\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*\)/.exec(color))
                color = [parseInt(result[1]), parseInt(result[2]), parseInt(result[3])];
            if (result = /rgb\(\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*\)/.exec(color))
                color =[parseFloat(result[1])*2.55, parseFloat(result[2])*2.55, parseFloat(result[3])*2.55];
            if (result = /#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})/.exec(color))
                color =[parseInt(result[1],16), parseInt(result[2],16), parseInt(result[3],16)];
            if (result = /#([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])/.exec(color))
                color =[parseInt(result[1]+result[1],16), parseInt(result[2]+result[2],16), parseInt(result[3]+result[3],16)];
            return "rgb("+color[0]+","+color[1]+","+color[2]+")";
        }
    };
    jQuery.fn.colorpicker.defaults = {
        ishex : true, //是否使用16进制颜色值
        fillcolor:false,  //是否将颜色值填充至对象的val中
        target: null, //目标对象
        event: 'click', //颜色框显示的事件
        success:function(){}, //回调函数
        reset:function(){}
    };
})(jQuery);
/*! jquery.colorpicker.js 结束*/


/*====artdialog开始====*/
/*! artDialog v6.0.4 TangBin | https://github.com/aui/artDialog LGPL协议*/
!function(){function a(b){var d=c[b],e="exports";return"object"==typeof d?d:(d[e]||(d[e]={},d[e]=d.call(d[e],a,d[e],d)||d[e]),d[e])}function b(a,b){c[a]=b}var c={};b("jquery",function(){return jQuery}),b("popup",function(a){function b(){this.destroyed=!1,this.__popup=c("<div />").css({display:"none",position:"absolute",outline:0}).attr("tabindex","-1").html(this.innerHTML).appendTo("body"),this.__backdrop=this.__mask=c("<div />").css({opacity:.7,background:"#000"}),this.node=this.__popup[0],this.backdrop=this.__backdrop[0],d++}var c=a("jquery"),d=0,e=!("minWidth"in c("html")[0].style),f=!e;return c.extend(b.prototype,{node:null,backdrop:null,fixed:!1,destroyed:!0,open:!1,returnValue:"",autofocus:!0,align:"bottom left",innerHTML:"",className:"ui-popup",show:function(a){if(this.destroyed)return this;var d=this.__popup,g=this.__backdrop;if(this.__activeElement=this.__getActive(),this.open=!0,this.follow=a||this.follow,!this.__ready){if(d.addClass(this.className).attr("role",this.modal?"alertdialog":"dialog").css("position",this.fixed?"fixed":"absolute"),e||c(window).on("resize",c.proxy(this.reset,this)),this.modal){var h={position:"fixed",left:0,top:0,width:"100%",height:"100%",overflow:"hidden",userSelect:"none",zIndex:this.zIndex||b.zIndex};d.addClass(this.className+"-modal"),f||c.extend(h,{position:"absolute",width:c(window).width()+"px",height:c(document).height()+"px"}),g.css(h).attr({tabindex:"0"}).on("focus",c.proxy(this.focus,this)),this.__mask=g.clone(!0).attr("style","").insertAfter(d),g.addClass(this.className+"-backdrop").insertBefore(d),this.__ready=!0}d.html()||d.html(this.innerHTML)}return d.addClass(this.className+"-show").show(),g.show(),this.reset().focus(),this.__dispatchEvent("show"),this},showModal:function(){return this.modal=!0,this.show.apply(this,arguments)},close:function(a){return!this.destroyed&&this.open&&(void 0!==a&&(this.returnValue=a),this.__popup.hide().removeClass(this.className+"-show"),this.__backdrop.hide(),this.open=!1,this.blur(),this.__dispatchEvent("close")),this},remove:function(){if(this.destroyed)return this;this.__dispatchEvent("beforeremove"),b.current===this&&(b.current=null),this.__popup.remove(),this.__backdrop.remove(),this.__mask.remove(),e||c(window).off("resize",this.reset),this.__dispatchEvent("remove");for(var a in this)delete this[a];return this},reset:function(){var a=this.follow;return a?this.__follow(a):this.__center(),this.__dispatchEvent("reset"),this},focus:function(){var a=this.node,d=this.__popup,e=b.current,f=this.zIndex=b.zIndex++;if(e&&e!==this&&e.blur(!1),!c.contains(a,this.__getActive())){var g=d.find("[autofocus]")[0];!this._autofocus&&g?this._autofocus=!0:g=a,this.__focus(g)}return d.css("zIndex",f),b.current=this,d.addClass(this.className+"-focus"),this.__dispatchEvent("focus"),this},blur:function(){var a=this.__activeElement,b=arguments[0];return b!==!1&&this.__focus(a),this._autofocus=!1,this.__popup.removeClass(this.className+"-focus"),this.__dispatchEvent("blur"),this},addEventListener:function(a,b){return this.__getEventListener(a).push(b),this},removeEventListener:function(a,b){for(var c=this.__getEventListener(a),d=0;d<c.length;d++)b===c[d]&&c.splice(d--,1);return this},__getEventListener:function(a){var b=this.__listener;return b||(b=this.__listener={}),b[a]||(b[a]=[]),b[a]},__dispatchEvent:function(a){var b=this.__getEventListener(a);this["on"+a]&&this["on"+a]();for(var c=0;c<b.length;c++)b[c].call(this)},__focus:function(a){try{this.autofocus&&!/^iframe$/i.test(a.nodeName)&&a.focus()}catch(b){}},__getActive:function(){try{var a=document.activeElement,b=a.contentDocument,c=b&&b.activeElement||a;return c}catch(d){}},__center:function(){var a=this.__popup,b=c(window),d=c(document),e=this.fixed,f=e?0:d.scrollLeft(),g=e?0:d.scrollTop(),h=b.width(),i=b.height(),j=a.width(),k=a.height(),l=(h-j)/2+f,m=382*(i-k)/1e3+g,n=a[0].style;n.left=Math.max(parseInt(l),f)+"px",n.top=Math.max(parseInt(m),g)+"px"},__follow:function(a){var b=a.parentNode&&c(a),d=this.__popup;if(this.__followSkin&&d.removeClass(this.__followSkin),b){var e=b.offset();if(e.left*e.top<0)return this.__center()}var f=this,g=this.fixed,h=c(window),i=c(document),j=h.width(),k=h.height(),l=i.scrollLeft(),m=i.scrollTop(),n=d.width(),o=d.height(),p=b?b.outerWidth():0,q=b?b.outerHeight():0,r=this.__offset(a),s=r.left,t=r.top,u=g?s-l:s,v=g?t-m:t,w=g?0:l,x=g?0:m,y=w+j-n,z=x+k-o,A={},B=this.align.split(" "),C=this.className+"-",D={top:"bottom",bottom:"top",left:"right",right:"left"},E={top:"top",bottom:"top",left:"left",right:"left"},F=[{top:v-o,bottom:v+q,left:u-n,right:u+p},{top:v,bottom:v-o+q,left:u,right:u-n+p}],G={left:u+p/2-n/2,top:v+q/2-o/2},H={left:[w,y],top:[x,z]};c.each(B,function(a,b){F[a][b]>H[E[b]][1]&&(b=B[a]=D[b]),F[a][b]<H[E[b]][0]&&(B[a]=D[b])}),B[1]||(E[B[1]]="left"===E[B[0]]?"top":"left",F[1][B[1]]=G[E[B[1]]]),C+=B.join("-")+" "+this.className+"-follow",f.__followSkin=C,b&&d.addClass(C),A[E[B[0]]]=parseInt(F[0][B[0]]),A[E[B[1]]]=parseInt(F[1][B[1]]),d.css(A)},__offset:function(a){var b=a.parentNode,d=b?c(a).offset():{left:a.pageX,top:a.pageY};a=b?a:a.target;var e=a.ownerDocument,f=e.defaultView||e.parentWindow;if(f==window)return d;var g=f.frameElement,h=c(e),i=h.scrollLeft(),j=h.scrollTop(),k=c(g).offset(),l=k.left,m=k.top;return{left:d.left+l-i,top:d.top+m-j}}}),b.zIndex=1024,b.current=null,b}),b("dialog-config",{backdropBackground:"#000",backdropOpacity:.7,content:'<span class="ui-dialog-loading">Loading..</span>',title:"",statusbar:"",button:null,ok:null,cancel:null,okValue:"ok",cancelValue:"cancel",cancelDisplay:!0,width:"",height:"",padding:"",skin:"",quickClose:!1,cssUri:"../css/ui-dialog.css",innerHTML:'<div i="dialog" class="ui-dialog"><div class="ui-dialog-arrow-a"></div><div class="ui-dialog-arrow-b"></div><table class="ui-dialog-grid"><tr><td i="header" class="ui-dialog-header"><button i="close" class="ui-dialog-close">&#215;</button><div i="title" class="ui-dialog-title"></div></td></tr><tr><td i="body" class="ui-dialog-body"><div i="content" class="ui-dialog-content"></div></td></tr><tr><td i="footer" class="ui-dialog-footer"><div i="statusbar" class="ui-dialog-statusbar"></div><div i="button" class="ui-dialog-button"></div></td></tr></table></div>'}),b("dialog",function(a){var b=a("jquery"),c=a("popup"),d=a("dialog-config"),e=d.cssUri;if(e){var f=a[a.toUrl?"toUrl":"resolve"];f&&(e=f(e),e='<link rel="stylesheet" href="'+e+'" />',b("base")[0]?b("base").before(e):b("head").append(e))}var g=0,h=new Date-0,i=!("minWidth"in b("html")[0].style),j="createTouch"in document&&!("onmousemove"in document)||/(iPhone|iPad|iPod)/i.test(navigator.userAgent),k=!i&&!j,l=function(a,c,d){var e=a=a||{};("string"==typeof a||1===a.nodeType)&&(a={content:a,fixed:!j}),a=b.extend(!0,{},l.defaults,a),a.original=e;var f=a.id=a.id||h+g,i=l.get(f);return i?i.focus():(k||(a.fixed=!1),a.quickClose&&(a.modal=!0,a.backdropOpacity=0),b.isArray(a.button)||(a.button=[]),void 0!==d&&(a.cancel=d),a.cancel&&a.button.push({id:"cancel",value:a.cancelValue,callback:a.cancel,display:a.cancelDisplay}),void 0!==c&&(a.ok=c),a.ok&&a.button.push({id:"ok",value:a.okValue,callback:a.ok,autofocus:!0}),l.list[f]=new l.create(a))},m=function(){};m.prototype=c.prototype;var n=l.prototype=new m;return l.create=function(a){var d=this;b.extend(this,new c);var e=(a.original,b(this.node).html(a.innerHTML)),f=b(this.backdrop);return this.options=a,this._popup=e,b.each(a,function(a,b){"function"==typeof d[a]?d[a](b):d[a]=b}),a.zIndex&&(c.zIndex=a.zIndex),e.attr({"aria-labelledby":this._$("title").attr("id","title:"+this.id).attr("id"),"aria-describedby":this._$("content").attr("id","content:"+this.id).attr("id")}),this._$("close").css("display",this.cancel===!1?"none":"").attr("title",this.cancelValue).on("click",function(a){d._trigger("cancel"),a.preventDefault()}),this._$("dialog").addClass(this.skin),this._$("body").css("padding",this.padding),a.quickClose&&f.on("onmousedown"in document?"mousedown":"click",function(){return d._trigger("cancel"),!1}),this.addEventListener("show",function(){f.css({opacity:0,background:a.backdropBackground}).animate({opacity:a.backdropOpacity},150)}),this._esc=function(a){var b=a.target,e=b.nodeName,f=/^input|textarea$/i,g=c.current===d,h=a.keyCode;!g||f.test(e)&&"button"!==b.type||27===h&&d._trigger("cancel")},b(document).on("keydown",this._esc),this.addEventListener("remove",function(){b(document).off("keydown",this._esc),delete l.list[this.id]}),g++,l.oncreate(this),this},l.create.prototype=n,b.extend(n,{content:function(a){var c=this._$("content");return"object"==typeof a?(a=b(a),c.empty("").append(a.show()),this.addEventListener("remove",function(){b("body").append(a.hide())})):c.html(a),this.reset()},title:function(a){return this._$("title").text(a),this._$("header")[a?"show":"hide"](),this},width:function(a){return this._$("content").css("width",a),this.reset()},height:function(a){return this._$("content").css("height",a),this.reset()},button:function(a){a=a||[];var c=this,d="",e=0;return this.callbacks={},"string"==typeof a?(d=a,e++):b.each(a,function(a,f){var g=f.id=f.id||f.value,h="";c.callbacks[g]=f.callback,f.display===!1?h=' style="display:none"':e++,d+='<button type="button" i-id="'+g+'"'+h+(f.disabled?" disabled":"")+(f.autofocus?' autofocus class="ui-dialog-autofocus"':"")+">"+f.value+"</button>",c._$("button").on("click","[i-id="+g+"]",function(a){var d=b(this);d.attr("disabled")||c._trigger(g),a.preventDefault()})}),this._$("button").html(d),this._$("footer")[e?"show":"hide"](),this},statusbar:function(a){return this._$("statusbar").html(a)[a?"show":"hide"](),this},_$:function(a){return this._popup.find("[i="+a+"]")},_trigger:function(a){var b=this.callbacks[a];return"function"!=typeof b||b.call(this)!==!1?this.close().remove():this}}),l.oncreate=b.noop,l.getCurrent=function(){return c.current},l.get=function(a){return void 0===a?l.list:l.list[a]},l.list={},l.defaults=d,l}),b("drag",function(a){var b=a("jquery"),c=b(window),d=b(document),e="createTouch"in document,f=document.documentElement,g=!("minWidth"in f.style),h=!g&&"onlosecapture"in f,i="setCapture"in f,j={start:e?"touchstart":"mousedown",over:e?"touchmove":"mousemove",end:e?"touchend":"mouseup"},k=e?function(a){return a.touches||(a=a.originalEvent.touches.item(0)),a}:function(a){return a},l=function(){this.start=b.proxy(this.start,this),this.over=b.proxy(this.over,this),this.end=b.proxy(this.end,this),this.onstart=this.onover=this.onend=b.noop};return l.types=j,l.prototype={start:function(a){return a=this.startFix(a),d.on(j.over,this.over).on(j.end,this.end),this.onstart(a),!1},over:function(a){return a=this.overFix(a),this.onover(a),!1},end:function(a){return a=this.endFix(a),d.off(j.over,this.over).off(j.end,this.end),this.onend(a),!1},startFix:function(a){return a=k(a),this.target=b(a.target),this.selectstart=function(){return!1},d.on("selectstart",this.selectstart).on("dblclick",this.end),h?this.target.on("losecapture",this.end):c.on("blur",this.end),i&&this.target[0].setCapture(),a},overFix:function(a){return a=k(a)},endFix:function(a){return a=k(a),d.off("selectstart",this.selectstart).off("dblclick",this.end),h?this.target.off("losecapture",this.end):c.off("blur",this.end),i&&this.target[0].releaseCapture(),a}},l.create=function(a,e){var f,g,h,i,j=b(a),k=new l,m=l.types.start,n=function(){},o=a.className.replace(/^\s|\s.*/g,"")+"-drag-start",p={onstart:n,onover:n,onend:n,off:function(){j.off(m,k.start)}};return k.onstart=function(b){var e="fixed"===j.css("position"),k=d.scrollLeft(),l=d.scrollTop(),m=j.width(),n=j.height();f=0,g=0,h=e?c.width()-m+f:d.width()-m,i=e?c.height()-n+g:d.height()-n;var q=j.offset(),r=this.startLeft=e?q.left-k:q.left,s=this.startTop=e?q.top-l:q.top;this.clientX=b.clientX,this.clientY=b.clientY,j.addClass(o),p.onstart.call(a,b,r,s)},k.onover=function(b){var c=b.clientX-this.clientX+this.startLeft,d=b.clientY-this.clientY+this.startTop,e=j[0].style;c=Math.max(f,Math.min(h,c)),d=Math.max(g,Math.min(i,d)),e.left=c+"px",e.top=d+"px",p.onover.call(a,b,c,d)},k.onend=function(b){var c=j.position(),d=c.left,e=c.top;j.removeClass(o),p.onend.call(a,b,d,e)},k.off=function(){j.off(m,k.start)},e?k.start(e):j.on(m,k.start),p},l}),b("dialog-plus",function(a){var b=a("jquery"),c=a("dialog"),d=a("drag");return c.oncreate=function(a){var c,e=a.options,f=e.original,g=e.url,h=e.oniframeload;if(g&&(this.padding=e.padding=0,c=b("<iframe />"),c.attr({src:g,name:a.id,width:"100%",height:"100%",allowtransparency:"yes",frameborder:"no",scrolling:"no"}).on("load",function(){var b;try{b=c[0].contentWindow.frameElement}catch(d){}b&&(e.width||a.width(c.contents().width()),e.height||a.height(c.contents().height())),h&&h.call(a)}),a.addEventListener("beforeremove",function(){c.attr("src","about:blank").remove()},!1),a.content(c[0]),a.iframeNode=c[0]),!(f instanceof Object))for(var i=function(){a.close().remove()},j=0;j<frames.length;j++)try{if(f instanceof frames[j].Object){b(frames[j]).one("unload",i);break}}catch(k){}b(a.node).on(d.types.start,"[i=title]",function(b){a.follow||(a.focus(),d.create(a.node,b))})},c.get=function(a){if(a&&a.frameElement){var b,d=a.frameElement,e=c.list;for(var f in e)if(b=e[f],b.node.getElementsByTagName("iframe")[0]===d)return b}else if(a)return c.list[a]},c}),window.dialog=a("dialog-plus")}();
/*====artdialog结束====*/

/*====jquery-tabs开始====*/
// jQuery Tabs Plugins 1.0 http://stylechen.com/jquery-tabs.html 4th Dec 2010
;(function($){
	$.fn.extend({
		Tabs:function(options){
			options = $.extend({event : 'mouseover',timeout : 0,auto : 0,callback : null}, options);
			var self = $(this),
				tabBox = self.children( 'div.tab_box' ).children( 'div' ),
				menu = self.children( 'ul.tab_menu' ),
				items = menu.find( 'li' ),
				timer;
			var tabHandle = function( elem ){
					elem.siblings( 'li' ).removeClass( 'current' ).end().addClass( 'current' );
					tabBox.siblings( 'div' ).addClass( 'hide' ).end().eq( elem.index() ).removeClass( 'hide' );
				},
				delay = function( elem, time ){
					time ? setTimeout(function(){ tabHandle( elem ); }, time) : tabHandle( elem );
				},
				start = function(){
					if( !options.auto ) return;
					timer = setInterval( autoRun, options.auto );
				},
				autoRun = function(){
					var current = menu.find( 'li.current' ),
						firstItem = items.eq(0),
						len = items.length,
						index = current.index() + 1,
						item = index === len ? firstItem : current.next( 'li' ),
						i = index === len ? 0 : index;
					current.removeClass( 'current' );
					item.addClass( 'current' );
					tabBox.siblings( 'div' ).addClass( 'hide' ).end().eq(i).removeClass( 'hide' );
				};
							
			items.bind( options.event, function(){
				delay( $(this), options.timeout );
				if( options.callback ){
					options.callback( self );
				}
			});
			
			if( options.auto ){
				start();
				self.hover(function(){
					clearInterval( timer );
					timer = undefined;
				},function(){
					start();
				});
			}
			return this;
		}
	});
})(jQuery);
/*====jquery-tabs结束====*/

/*====jquery-jrange开始====*/
/* Written by  Nitin Hayaran (nitinhayaran@gmail.com) Licensed under the MIT (MIT-LICENSE.txt) */
!function($,t,i,s){"use strict";var o=function(){return this.init.apply(this,arguments)};o.prototype={defaults:{onstatechange:function(){},ondragend:function(){},onbarclicked:function(){},isRange:!1,showLabels:!0,showScale:!0,step:1,format:"%s",theme:"theme-green",width:300,disable:!1,snap:!1},template:'<div class="slider-container">			<div class="back-bar">                <div class="selected-bar"></div>                <div class="pointer low"></div><div class="pointer-label low">123456</div>                <div class="pointer high"></div><div class="pointer-label high">456789</div>                <div class="clickable-dummy"></div>            </div>            <div class="scale"></div>		</div>',init:function(t,i){this.options=$.extend({},this.defaults,i),this.inputNode=$(t),this.options.value=this.inputNode.val()||(this.options.isRange?this.options.from+","+this.options.from:""+this.options.from),this.domNode=$(this.template),this.domNode.addClass(this.options.theme),this.inputNode.after(this.domNode),this.domNode.on("change",this.onChange),this.pointers=$(".pointer",this.domNode),this.lowPointer=this.pointers.first(),this.highPointer=this.pointers.last(),this.labels=$(".pointer-label",this.domNode),this.lowLabel=this.labels.first(),this.highLabel=this.labels.last(),this.scale=$(".scale",this.domNode),this.bar=$(".selected-bar",this.domNode),this.clickableBar=this.domNode.find(".clickable-dummy"),this.interval=this.options.to-this.options.from,this.render()},render:function(){return 0!==this.inputNode.width()||this.options.width?(this.options.width=this.options.width||this.inputNode.width(),this.domNode.width(this.options.width),this.inputNode.hide(),this.isSingle()&&(this.lowPointer.hide(),this.lowLabel.hide()),this.options.showLabels||this.labels.hide(),this.attachEvents(),this.options.showScale&&this.renderScale(),void this.setValue(this.options.value)):void console.log("jRange : no width found, returning")},isSingle:function(){return"number"==typeof this.options.value?!0:-1===this.options.value.indexOf(",")&&!this.options.isRange},attachEvents:function(){this.clickableBar.click($.proxy(this.barClicked,this)),this.pointers.on("mousedown touchstart",$.proxy(this.onDragStart,this)),this.pointers.bind("dragstart",function(t){t.preventDefault()})},onDragStart:function(t){if(!(this.options.disable||"mousedown"===t.type&&1!==t.which)){t.stopPropagation(),t.preventDefault();var s=$(t.target);this.pointers.removeClass("last-active"),s.addClass("focused last-active"),this[(s.hasClass("low")?"low":"high")+"Label"].addClass("focused"),$(i).on("mousemove.slider touchmove.slider",$.proxy(this.onDrag,this,s)),$(i).on("mouseup.slider touchend.slider touchcancel.slider",$.proxy(this.onDragEnd,this))}},onDrag:function(t,i){i.stopPropagation(),i.preventDefault(),i.originalEvent.touches&&i.originalEvent.touches.length?i=i.originalEvent.touches[0]:i.originalEvent.changedTouches&&i.originalEvent.changedTouches.length&&(i=i.originalEvent.changedTouches[0]);var s=i.clientX-this.domNode.offset().left;this.domNode.trigger("change",[this,t,s])},onDragEnd:function(t){this.pointers.removeClass("focused").trigger("rangeslideend"),this.labels.removeClass("focused"),$(i).off(".slider"),this.options.ondragend.call(this,this.options.value)},barClicked:function(t){if(!this.options.disable){var i=t.pageX-this.clickableBar.offset().left;if(this.isSingle())this.setPosition(this.pointers.last(),i,!0,!0);else{var s=Math.abs(parseFloat(this.pointers.first().css("left"),10)),o=this.pointers.first().width()/2,e=Math.abs(parseFloat(this.pointers.last().css("left"),10)),n=this.pointers.first().width()/2,a=Math.abs(s-i+o),h=Math.abs(e-i+n),l;l=a==h?s>i?this.pointers.first():this.pointers.last():h>a?this.pointers.first():this.pointers.last(),this.setPosition(l,i,!0,!0)}this.options.onbarclicked.call(this,this.options.value)}},onChange:function(t,i,s,o){var e,n;e=0,n=i.domNode.width(),i.isSingle()||(e=s.hasClass("high")?parseFloat(i.lowPointer.css("left"))+i.lowPointer.width()/2:0,n=s.hasClass("low")?parseFloat(i.highPointer.css("left"))+i.highPointer.width()/2:i.domNode.width());var a=Math.min(Math.max(o,e),n);i.setPosition(s,a,!0)},setPosition:function(t,i,s,o){var e,n,a=parseFloat(this.lowPointer.css("left")),h=parseFloat(this.highPointer.css("left"))||0,l=this.highPointer.width()/2;if(s||(i=this.prcToPx(i)),this.options.snap){var r=this.correctPositionForSnap(i);if(-1===r)return;i=r}t[0]===this.highPointer[0]?h=Math.round(i-l):a=Math.round(i-l),t[o?"animate":"css"]({left:Math.round(i-l)}),this.isSingle()?e=0:(e=a+l,n=h+l);var d=Math.round(h+l-e);this.bar[o?"animate":"css"]({width:Math.abs(d),left:d>0?e:e+d}),this.showPointerValue(t,i,o),this.isReadonly()},correctPositionForSnap:function(t){var i=this.positionToValue(t)-this.options.from,s=this.options.width/(this.interval/this.options.step),o=i/this.options.step*s;return o+s/2>=t&&t>=o-s/2?o:-1},setValue:function(t){var i=t.toString().split(",");i[0]=Math.min(Math.max(i[0],this.options.from),this.options.to)+"",i.length>1&&(i[1]=Math.min(Math.max(i[1],this.options.from),this.options.to)+""),this.options.value=t;var s=this.valuesToPrc(2===i.length?i:[0,i[0]]);this.isSingle()?this.setPosition(this.highPointer,s[1]):(this.setPosition(this.lowPointer,s[0]),this.setPosition(this.highPointer,s[1]))},renderScale:function(){for(var t=this.options.scale||[this.options.from,this.options.to],i=Math.round(100/(t.length-1)*10)/10,s="",o=0;o<t.length;o++)s+='<span style="left: '+o*i+'%">'+("|"!=t[o]?"<ins>"+t[o]+"</ins>":"")+"</span>";this.scale.html(s),$("ins",this.scale).each(function(){$(this).css({marginLeft:-$(this).outerWidth()/2})})},getBarWidth:function(){var t=this.options.value.split(",");return t.length>1?parseFloat(t[1])-parseFloat(t[0]):parseFloat(t[0])},showPointerValue:function(t,i,o){var e=$(".pointer-label",this.domNode)[t.hasClass("low")?"first":"last"](),n,a=this.positionToValue(i);if($.isFunction(this.options.format)){var h=this.isSingle()?s:t.hasClass("low")?"low":"high";n=this.options.format(a,h)}else n=this.options.format.replace("%s",a);var l=e.html(n).width(),r=i-l/2;r=Math.min(Math.max(r,0),this.options.width-l),e[o?"animate":"css"]({left:r}),this.setInputValue(t,a)},valuesToPrc:function(t){var i=100*(parseFloat(t[0])-parseFloat(this.options.from))/this.interval,s=100*(parseFloat(t[1])-parseFloat(this.options.from))/this.interval;return[i,s]},prcToPx:function(t){return this.domNode.width()*t/100},isDecimal:function(){return-1!==(this.options.value+this.options.from+this.options.to).indexOf(".")},positionToValue:function(t){var i=t/this.domNode.width()*this.interval;if(i=parseFloat(i,10)+parseFloat(this.options.from,10),this.isDecimal()){var s=Math.round(Math.round(i/this.options.step)*this.options.step*100)/100;if(0!==s)for(s=""+s,-1===s.indexOf(".")&&(s+=".");s.length-s.indexOf(".")<3;)s+="0";else s="0.00";return s}return Math.round(i/this.options.step)*this.options.step},setInputValue:function(t,i){if(this.isSingle())this.options.value=i.toString();else{var s=this.options.value.split(",");t.hasClass("low")?this.options.value=i+","+s[1]:this.options.value=s[0]+","+i}this.inputNode.val()!==this.options.value&&(this.inputNode.val(this.options.value).trigger("change"),this.options.onstatechange.call(this,this.options.value))},getValue:function(){return this.options.value},getOptions:function(){return this.options},getRange:function(){return this.options.from+","+this.options.to},isReadonly:function(){this.domNode.toggleClass("slider-readonly",this.options.disable)},disable:function(){this.options.disable=!0,this.isReadonly()},enable:function(){this.options.disable=!1,this.isReadonly()},toggleDisable:function(){this.options.disable=!this.options.disable,this.isReadonly()},updateRange:function(t,i){var s=t.toString().split(",");this.interval=parseInt(s[1])-parseInt(s[0]),i?this.setValue(i):this.setValue(this.getValue())}};var e="jRange";$.fn[e]=function(i){var s=arguments,n;return this.each(function(){var a=$(this),h=$.data(this,"plugin_"+e),l="object"==typeof i&&i;h||(a.data("plugin_"+e,h=new o(this,l)),$(t).resize(function(){h.setValue(h.getValue())})),"string"==typeof i&&(n=h[i].apply(h,Array.prototype.slice.call(s,1)))}),n||this}}(jQuery,window,document);
/*====jquery-jrange结束====*/

/*====plupload 开始====*/
/**
 * mOxie v1.5.7 Released under GPL License Date: 2017-11-03
 Copyright 2013, Moxiecode Systems AB License: http://www.plupload.com/license
 */
!function(e,t){var i=function(){var e={};return t.apply(e,arguments),e.moxie};"function"==typeof define&&define.amd?define("moxie",[],i):"object"==typeof module&&module.exports?module.exports=i():e.moxie=i()}(this||window,function(){!function(e,t){"use strict";function i(e,t){for(var i,n=[],r=0;r<e.length;++r){if(i=s[e[r]]||o(e[r]),!i)throw"module definition dependecy not found: "+e[r];n.push(i)}t.apply(null,n)}function n(e,n,r){if("string"!=typeof e)throw"invalid module definition, module id must be defined and be a string";if(n===t)throw"invalid module definition, dependencies must be specified";if(r===t)throw"invalid module definition, definition function must be specified";i(n,function(){s[e]=r.apply(null,arguments)})}function r(e){return!!s[e]}function o(t){for(var i=e,n=t.split(/[.\/]/),r=0;r<n.length;++r){if(!i[n[r]])return;i=i[n[r]]}return i}function a(i){for(var n=0;n<i.length;n++){for(var r=e,o=i[n],a=o.split(/[.\/]/),u=0;u<a.length-1;++u)r[a[u]]===t&&(r[a[u]]={}),r=r[a[u]];r[a[a.length-1]]=s[o]}}var s={};n("moxie/core/utils/Basic",[],function(){function e(e){var t;return e===t?"undefined":null===e?"null":e.nodeType?"node":{}.toString.call(e).match(/\s([a-z|A-Z]+)/)[1].toLowerCase()}function t(){return s(!1,!1,arguments)}function i(){return s(!0,!1,arguments)}function n(){return s(!1,!0,arguments)}function r(){return s(!0,!0,arguments)}function o(t){switch(e(t)){case"array":return s(!1,!0,[[],t]);case"object":return s(!1,!0,[{},t]);default:return t}}function a(i){switch(e(i)){case"array":return Array.prototype.slice.call(i);case"object":return t({},i)}return i}function s(t,i,n){var r,o=n[0];return c(n,function(n,u){u>0&&c(n,function(n,u){var c=-1!==h(e(n),["array","object"]);return n===r||t&&o[u]===r?!0:(c&&i&&(n=a(n)),e(o[u])===e(n)&&c?s(t,i,[o[u],n]):o[u]=n,void 0)})}),o}function u(e,t){function i(){this.constructor=e}for(var n in t)({}).hasOwnProperty.call(t,n)&&(e[n]=t[n]);return i.prototype=t.prototype,e.prototype=new i,e.parent=t.prototype,e}function c(e,t){var i,n,r,o;if(e){try{i=e.length}catch(a){i=o}if(i===o||"number"!=typeof i){for(n in e)if(e.hasOwnProperty(n)&&t(e[n],n)===!1)return}else for(r=0;i>r;r++)if(t(e[r],r)===!1)return}}function l(t){var i;if(!t||"object"!==e(t))return!0;for(i in t)return!1;return!0}function d(t,i){function n(r){"function"===e(t[r])&&t[r](function(e){++r<o&&!e?n(r):i(e)})}var r=0,o=t.length;"function"!==e(i)&&(i=function(){}),t&&t.length||i(),n(r)}function m(e,t){var i=0,n=e.length,r=new Array(n);c(e,function(e,o){e(function(e){if(e)return t(e);var a=[].slice.call(arguments);a.shift(),r[o]=a,i++,i===n&&(r.unshift(null),t.apply(this,r))})})}function h(e,t){if(t){if(Array.prototype.indexOf)return Array.prototype.indexOf.call(t,e);for(var i=0,n=t.length;n>i;i++)if(t[i]===e)return i}return-1}function f(t,i){var n=[];"array"!==e(t)&&(t=[t]),"array"!==e(i)&&(i=[i]);for(var r in t)-1===h(t[r],i)&&n.push(t[r]);return n.length?n:!1}function p(e,t){var i=[];return c(e,function(e){-1!==h(e,t)&&i.push(e)}),i.length?i:null}function g(e){var t,i=[];for(t=0;t<e.length;t++)i[t]=e[t];return i}function x(e){return e?String.prototype.trim?String.prototype.trim.call(e):e.toString().replace(/^\s*/,"").replace(/\s*$/,""):e}function v(e){if("string"!=typeof e)return e;var t,i={t:1099511627776,g:1073741824,m:1048576,k:1024};return e=/^([0-9\.]+)([tmgk]?)$/.exec(e.toLowerCase().replace(/[^0-9\.tmkg]/g,"")),t=e[2],e=+e[1],i.hasOwnProperty(t)&&(e*=i[t]),Math.floor(e)}function w(e){var t=[].slice.call(arguments,1);return e.replace(/%([a-z])/g,function(e,i){var n=t.shift();switch(i){case"s":return n+"";case"d":return parseInt(n,10);case"f":return parseFloat(n);case"c":return"";default:return n}})}function y(e,t){var i=this;setTimeout(function(){e.call(i)},t||1)}var E=function(){var e=0;return function(t){var i,n=(new Date).getTime().toString(32);for(i=0;5>i;i++)n+=Math.floor(65535*Math.random()).toString(32);return(t||"o_")+n+(e++).toString(32)}}();return{guid:E,typeOf:e,extend:t,extendIf:i,extendImmutable:n,extendImmutableIf:r,clone:o,inherit:u,each:c,isEmptyObj:l,inSeries:d,inParallel:m,inArray:h,arrayDiff:f,arrayIntersect:p,toArray:g,trim:x,sprintf:w,parseSizeStr:v,delay:y}}),n("moxie/core/utils/Encode",[],function(){var e=function(e){return unescape(encodeURIComponent(e))},t=function(e){return decodeURIComponent(escape(e))},i=function(e,i){if("function"==typeof window.atob)return i?t(window.atob(e)):window.atob(e);var n,r,o,a,s,u,c,l,d="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",m=0,h=0,f="",p=[];if(!e)return e;e+="";do a=d.indexOf(e.charAt(m++)),s=d.indexOf(e.charAt(m++)),u=d.indexOf(e.charAt(m++)),c=d.indexOf(e.charAt(m++)),l=a<<18|s<<12|u<<6|c,n=255&l>>16,r=255&l>>8,o=255&l,p[h++]=64==u?String.fromCharCode(n):64==c?String.fromCharCode(n,r):String.fromCharCode(n,r,o);while(m<e.length);return f=p.join(""),i?t(f):f},n=function(t,i){if(i&&(t=e(t)),"function"==typeof window.btoa)return window.btoa(t);var n,r,o,a,s,u,c,l,d="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",m=0,h=0,f="",p=[];if(!t)return t;do n=t.charCodeAt(m++),r=t.charCodeAt(m++),o=t.charCodeAt(m++),l=n<<16|r<<8|o,a=63&l>>18,s=63&l>>12,u=63&l>>6,c=63&l,p[h++]=d.charAt(a)+d.charAt(s)+d.charAt(u)+d.charAt(c);while(m<t.length);f=p.join("");var g=t.length%3;return(g?f.slice(0,g-3):f)+"===".slice(g||3)};return{utf8_encode:e,utf8_decode:t,atob:i,btoa:n}}),n("moxie/core/utils/Env",["moxie/core/utils/Basic"],function(e){function i(e,t,i){var n=0,r=0,o=0,a={dev:-6,alpha:-5,a:-5,beta:-4,b:-4,RC:-3,rc:-3,"#":-2,p:1,pl:1},s=function(e){return e=(""+e).replace(/[_\-+]/g,"."),e=e.replace(/([^.\d]+)/g,".$1.").replace(/\.{2,}/g,"."),e.length?e.split("."):[-8]},u=function(e){return e?isNaN(e)?a[e]||-7:parseInt(e,10):0};for(e=s(e),t=s(t),r=Math.max(e.length,t.length),n=0;r>n;n++)if(e[n]!=t[n]){if(e[n]=u(e[n]),t[n]=u(t[n]),e[n]<t[n]){o=-1;break}if(e[n]>t[n]){o=1;break}}if(!i)return o;switch(i){case">":case"gt":return o>0;case">=":case"ge":return o>=0;case"<=":case"le":return 0>=o;case"==":case"=":case"eq":return 0===o;case"<>":case"!=":case"ne":return 0!==o;case"":case"<":case"lt":return 0>o;default:return null}}var n=function(e){var t="",i="?",n="function",r="undefined",o="object",a="name",s="version",u={has:function(e,t){return-1!==t.toLowerCase().indexOf(e.toLowerCase())},lowerize:function(e){return e.toLowerCase()}},c={rgx:function(){for(var t,i,a,s,u,c,l,d=0,m=arguments;d<m.length;d+=2){var h=m[d],f=m[d+1];if(typeof t===r){t={};for(s in f)u=f[s],typeof u===o?t[u[0]]=e:t[u]=e}for(i=a=0;i<h.length;i++)if(c=h[i].exec(this.getUA())){for(s=0;s<f.length;s++)l=c[++a],u=f[s],typeof u===o&&u.length>0?2==u.length?t[u[0]]=typeof u[1]==n?u[1].call(this,l):u[1]:3==u.length?t[u[0]]=typeof u[1]!==n||u[1].exec&&u[1].test?l?l.replace(u[1],u[2]):e:l?u[1].call(this,l,u[2]):e:4==u.length&&(t[u[0]]=l?u[3].call(this,l.replace(u[1],u[2])):e):t[u]=l?l:e;break}if(c)break}return t},str:function(t,n){for(var r in n)if(typeof n[r]===o&&n[r].length>0){for(var a=0;a<n[r].length;a++)if(u.has(n[r][a],t))return r===i?e:r}else if(u.has(n[r],t))return r===i?e:r;return t}},l={browser:{oldsafari:{major:{1:["/8","/1","/3"],2:"/4","?":"/"},version:{"1.0":"/8",1.2:"/1",1.3:"/3","2.0":"/412","2.0.2":"/416","2.0.3":"/417","2.0.4":"/419","?":"/"}}},device:{sprint:{model:{"Evo Shift 4G":"7373KT"},vendor:{HTC:"APA",Sprint:"Sprint"}}},os:{windows:{version:{ME:"4.90","NT 3.11":"NT3.51","NT 4.0":"NT4.0",2000:"NT 5.0",XP:["NT 5.1","NT 5.2"],Vista:"NT 6.0",7:"NT 6.1",8:"NT 6.2",8.1:"NT 6.3",RT:"ARM"}}}},d={browser:[[/(opera\smini)\/([\w\.-]+)/i,/(opera\s[mobiletab]+).+version\/([\w\.-]+)/i,/(opera).+version\/([\w\.]+)/i,/(opera)[\/\s]+([\w\.]+)/i],[a,s],[/\s(opr)\/([\w\.]+)/i],[[a,"Opera"],s],[/(kindle)\/([\w\.]+)/i,/(lunascape|maxthon|netfront|jasmine|blazer)[\/\s]?([\w\.]+)*/i,/(avant\s|iemobile|slim|baidu)(?:browser)?[\/\s]?([\w\.]*)/i,/(?:ms|\()(ie)\s([\w\.]+)/i,/(rekonq)\/([\w\.]+)*/i,/(chromium|flock|rockmelt|midori|epiphany|silk|skyfire|ovibrowser|bolt|iron|vivaldi)\/([\w\.-]+)/i],[a,s],[/(trident).+rv[:\s]([\w\.]+).+like\sgecko/i],[[a,"IE"],s],[/(edge)\/((\d+)?[\w\.]+)/i],[a,s],[/(yabrowser)\/([\w\.]+)/i],[[a,"Yandex"],s],[/(comodo_dragon)\/([\w\.]+)/i],[[a,/_/g," "],s],[/(chrome|omniweb|arora|[tizenoka]{5}\s?browser)\/v?([\w\.]+)/i,/(uc\s?browser|qqbrowser)[\/\s]?([\w\.]+)/i],[a,s],[/(dolfin)\/([\w\.]+)/i],[[a,"Dolphin"],s],[/((?:android.+)crmo|crios)\/([\w\.]+)/i],[[a,"Chrome"],s],[/XiaoMi\/MiuiBrowser\/([\w\.]+)/i],[s,[a,"MIUI Browser"]],[/android.+version\/([\w\.]+)\s+(?:mobile\s?safari|safari)/i],[s,[a,"Android Browser"]],[/FBAV\/([\w\.]+);/i],[s,[a,"Facebook"]],[/version\/([\w\.]+).+?mobile\/\w+\s(safari)/i],[s,[a,"Mobile Safari"]],[/version\/([\w\.]+).+?(mobile\s?safari|safari)/i],[s,a],[/webkit.+?(mobile\s?safari|safari)(\/[\w\.]+)/i],[a,[s,c.str,l.browser.oldsafari.version]],[/(konqueror)\/([\w\.]+)/i,/(webkit|khtml)\/([\w\.]+)/i],[a,s],[/(navigator|netscape)\/([\w\.-]+)/i],[[a,"Netscape"],s],[/(swiftfox)/i,/(icedragon|iceweasel|camino|chimera|fennec|maemo\sbrowser|minimo|conkeror)[\/\s]?([\w\.\+]+)/i,/(firefox|seamonkey|k-meleon|icecat|iceape|firebird|phoenix)\/([\w\.-]+)/i,/(mozilla)\/([\w\.]+).+rv\:.+gecko\/\d+/i,/(polaris|lynx|dillo|icab|doris|amaya|w3m|netsurf)[\/\s]?([\w\.]+)/i,/(links)\s\(([\w\.]+)/i,/(gobrowser)\/?([\w\.]+)*/i,/(ice\s?browser)\/v?([\w\._]+)/i,/(mosaic)[\/\s]([\w\.]+)/i],[a,s]],engine:[[/windows.+\sedge\/([\w\.]+)/i],[s,[a,"EdgeHTML"]],[/(presto)\/([\w\.]+)/i,/(webkit|trident|netfront|netsurf|amaya|lynx|w3m)\/([\w\.]+)/i,/(khtml|tasman|links)[\/\s]\(?([\w\.]+)/i,/(icab)[\/\s]([23]\.[\d\.]+)/i],[a,s],[/rv\:([\w\.]+).*(gecko)/i],[s,a]],os:[[/microsoft\s(windows)\s(vista|xp)/i],[a,s],[/(windows)\snt\s6\.2;\s(arm)/i,/(windows\sphone(?:\sos)*|windows\smobile|windows)[\s\/]?([ntce\d\.\s]+\w)/i],[a,[s,c.str,l.os.windows.version]],[/(win(?=3|9|n)|win\s9x\s)([nt\d\.]+)/i],[[a,"Windows"],[s,c.str,l.os.windows.version]],[/\((bb)(10);/i],[[a,"BlackBerry"],s],[/(blackberry)\w*\/?([\w\.]+)*/i,/(tizen)[\/\s]([\w\.]+)/i,/(android|webos|palm\os|qnx|bada|rim\stablet\sos|meego|contiki)[\/\s-]?([\w\.]+)*/i,/linux;.+(sailfish);/i],[a,s],[/(symbian\s?os|symbos|s60(?=;))[\/\s-]?([\w\.]+)*/i],[[a,"Symbian"],s],[/\((series40);/i],[a],[/mozilla.+\(mobile;.+gecko.+firefox/i],[[a,"Firefox OS"],s],[/(nintendo|playstation)\s([wids3portablevu]+)/i,/(mint)[\/\s\(]?(\w+)*/i,/(mageia|vectorlinux)[;\s]/i,/(joli|[kxln]?ubuntu|debian|[open]*suse|gentoo|arch|slackware|fedora|mandriva|centos|pclinuxos|redhat|zenwalk|linpus)[\/\s-]?([\w\.-]+)*/i,/(hurd|linux)\s?([\w\.]+)*/i,/(gnu)\s?([\w\.]+)*/i],[a,s],[/(cros)\s[\w]+\s([\w\.]+\w)/i],[[a,"Chromium OS"],s],[/(sunos)\s?([\w\.]+\d)*/i],[[a,"Solaris"],s],[/\s([frentopc-]{0,4}bsd|dragonfly)\s?([\w\.]+)*/i],[a,s],[/(ip[honead]+)(?:.*os\s*([\w]+)*\slike\smac|;\sopera)/i],[[a,"iOS"],[s,/_/g,"."]],[/(mac\sos\sx)\s?([\w\s\.]+\w)*/i,/(macintosh|mac(?=_powerpc)\s)/i],[[a,"Mac OS"],[s,/_/g,"."]],[/((?:open)?solaris)[\/\s-]?([\w\.]+)*/i,/(haiku)\s(\w+)/i,/(aix)\s((\d)(?=\.|\)|\s)[\w\.]*)*/i,/(plan\s9|minix|beos|os\/2|amigaos|morphos|risc\sos|openvms)/i,/(unix)\s?([\w\.]+)*/i],[a,s]]},m=function(e){var i=e||(window&&window.navigator&&window.navigator.userAgent?window.navigator.userAgent:t);this.getBrowser=function(){return c.rgx.apply(this,d.browser)},this.getEngine=function(){return c.rgx.apply(this,d.engine)},this.getOS=function(){return c.rgx.apply(this,d.os)},this.getResult=function(){return{ua:this.getUA(),browser:this.getBrowser(),engine:this.getEngine(),os:this.getOS()}},this.getUA=function(){return i},this.setUA=function(e){return i=e,this},this.setUA(i)};return m}(),r=function(){var i={access_global_ns:function(){return!!window.moxie},define_property:function(){return!1}(),create_canvas:function(){var e=document.createElement("canvas"),t=!(!e.getContext||!e.getContext("2d"));return i.create_canvas=t,t},return_response_type:function(t){try{if(-1!==e.inArray(t,["","text","document"]))return!0;if(window.XMLHttpRequest){var i=new XMLHttpRequest;if(i.open("get","/"),"responseType"in i)return i.responseType=t,i.responseType!==t?!1:!0}}catch(n){}return!1},use_blob_uri:function(){var e=window.URL;return i.use_blob_uri=e&&"createObjectURL"in e&&"revokeObjectURL"in e&&("IE"!==a.browser||a.verComp(a.version,"11.0.46",">=")),i.use_blob_uri},use_data_uri:function(){var e=new Image;return e.onload=function(){i.use_data_uri=1===e.width&&1===e.height},setTimeout(function(){e.src="data:image/gif;base64,R0lGODlhAQABAIAAAP8AAAAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw=="},1),!1}(),use_data_uri_over32kb:function(){return i.use_data_uri&&("IE"!==a.browser||a.version>=9)},use_data_uri_of:function(e){return i.use_data_uri&&33e3>e||i.use_data_uri_over32kb()},use_fileinput:function(){if(navigator.userAgent.match(/(Android (1.0|1.1|1.5|1.6|2.0|2.1))|(Windows Phone (OS 7|8.0))|(XBLWP)|(ZuneWP)|(w(eb)?OSBrowser)|(webOS)|(Kindle\/(1.0|2.0|2.5|3.0))/))return!1;var e=document.createElement("input");return e.setAttribute("type","file"),i.use_fileinput=!e.disabled},use_webgl:function(){var e,n=document.createElement("canvas"),r=null;try{r=n.getContext("webgl")||n.getContext("experimental-webgl")}catch(o){}return r||(r=null),e=!!r,i.use_webgl=e,n=t,e}};return function(t){var n=[].slice.call(arguments);return n.shift(),"function"===e.typeOf(i[t])?i[t].apply(this,n):!!i[t]}}(),o=(new n).getResult(),a={can:r,uaParser:n,browser:o.browser.name,version:o.browser.version,os:o.os.name,osVersion:o.os.version,verComp:i,swf_url:"../flash/Moxie.swf",xap_url:"../silverlight/Moxie.xap",global_event_dispatcher:"moxie.core.EventTarget.instance.dispatchEvent"};return a.OS=a.os,a}),n("moxie/core/Exceptions",["moxie/core/utils/Basic"],function(e){function t(e,t){var i;for(i in e)if(e[i]===t)return i;return null}return{RuntimeError:function(){function i(e,i){this.code=e,this.name=t(n,e),this.message=this.name+(i||": RuntimeError "+this.code)}var n={NOT_INIT_ERR:1,EXCEPTION_ERR:3,NOT_SUPPORTED_ERR:9,JS_ERR:4};return e.extend(i,n),i.prototype=Error.prototype,i}(),OperationNotAllowedException:function(){function t(e){this.code=e,this.name="OperationNotAllowedException"}return e.extend(t,{NOT_ALLOWED_ERR:1}),t.prototype=Error.prototype,t}(),ImageError:function(){function i(e){this.code=e,this.name=t(n,e),this.message=this.name+": ImageError "+this.code}var n={WRONG_FORMAT:1,MAX_RESOLUTION_ERR:2,INVALID_META_ERR:3};return e.extend(i,n),i.prototype=Error.prototype,i}(),FileException:function(){function i(e){this.code=e,this.name=t(n,e),this.message=this.name+": FileException "+this.code}var n={NOT_FOUND_ERR:1,SECURITY_ERR:2,ABORT_ERR:3,NOT_READABLE_ERR:4,ENCODING_ERR:5,NO_MODIFICATION_ALLOWED_ERR:6,INVALID_STATE_ERR:7,SYNTAX_ERR:8};return e.extend(i,n),i.prototype=Error.prototype,i}(),DOMException:function(){function i(e){this.code=e,this.name=t(n,e),this.message=this.name+": DOMException "+this.code}var n={INDEX_SIZE_ERR:1,DOMSTRING_SIZE_ERR:2,HIERARCHY_REQUEST_ERR:3,WRONG_DOCUMENT_ERR:4,INVALID_CHARACTER_ERR:5,NO_DATA_ALLOWED_ERR:6,NO_MODIFICATION_ALLOWED_ERR:7,NOT_FOUND_ERR:8,NOT_SUPPORTED_ERR:9,INUSE_ATTRIBUTE_ERR:10,INVALID_STATE_ERR:11,SYNTAX_ERR:12,INVALID_MODIFICATION_ERR:13,NAMESPACE_ERR:14,INVALID_ACCESS_ERR:15,VALIDATION_ERR:16,TYPE_MISMATCH_ERR:17,SECURITY_ERR:18,NETWORK_ERR:19,ABORT_ERR:20,URL_MISMATCH_ERR:21,QUOTA_EXCEEDED_ERR:22,TIMEOUT_ERR:23,INVALID_NODE_TYPE_ERR:24,DATA_CLONE_ERR:25};return e.extend(i,n),i.prototype=Error.prototype,i}(),EventException:function(){function t(e){this.code=e,this.name="EventException"}return e.extend(t,{UNSPECIFIED_EVENT_TYPE_ERR:0}),t.prototype=Error.prototype,t}()}}),n("moxie/core/utils/Dom",["moxie/core/utils/Env"],function(e){var t=function(e){return"string"!=typeof e?e:document.getElementById(e)},i=function(e,t){if(!e.className)return!1;var i=new RegExp("(^|\\s+)"+t+"(\\s+|$)");return i.test(e.className)},n=function(e,t){i(e,t)||(e.className=e.className?e.className.replace(/\s+$/,"")+" "+t:t)},r=function(e,t){if(e.className){var i=new RegExp("(^|\\s+)"+t+"(\\s+|$)");e.className=e.className.replace(i,function(e,t,i){return" "===t&&" "===i?" ":""})}},o=function(e,t){return e.currentStyle?e.currentStyle[t]:window.getComputedStyle?window.getComputedStyle(e,null)[t]:void 0},a=function(t,i){function n(e){var t,i,n=0,r=0;return e&&(i=e.getBoundingClientRect(),t="CSS1Compat"===c.compatMode?c.documentElement:c.body,n=i.left+t.scrollLeft,r=i.top+t.scrollTop),{x:n,y:r}}var r,o,a,s=0,u=0,c=document;if(t=t,i=i||c.body,t&&t.getBoundingClientRect&&"IE"===e.browser&&(!c.documentMode||c.documentMode<8))return o=n(t),a=n(i),{x:o.x-a.x,y:o.y-a.y};for(r=t;r&&r!=i&&r.nodeType;)s+=r.offsetLeft||0,u+=r.offsetTop||0,r=r.offsetParent;for(r=t.parentNode;r&&r!=i&&r.nodeType;)s-=r.scrollLeft||0,u-=r.scrollTop||0,r=r.parentNode;return{x:s,y:u}},s=function(e){return{w:e.offsetWidth||e.clientWidth,h:e.offsetHeight||e.clientHeight}};return{get:t,hasClass:i,addClass:n,removeClass:r,getStyle:o,getPos:a,getSize:s}}),n("moxie/core/EventTarget",["moxie/core/utils/Env","moxie/core/Exceptions","moxie/core/utils/Basic"],function(e,t,i){function n(){this.uid=i.guid()}var r={};return i.extend(n.prototype,{init:function(){this.uid||(this.uid=i.guid("uid_"))},addEventListener:function(e,t,n,o){var a,s=this;return this.hasOwnProperty("uid")||(this.uid=i.guid("uid_")),e=i.trim(e),/\s/.test(e)?(i.each(e.split(/\s+/),function(e){s.addEventListener(e,t,n,o)}),void 0):(e=e.toLowerCase(),n=parseInt(n,10)||0,a=r[this.uid]&&r[this.uid][e]||[],a.push({fn:t,priority:n,scope:o||this}),r[this.uid]||(r[this.uid]={}),r[this.uid][e]=a,void 0)},hasEventListener:function(e){var t;return e?(e=e.toLowerCase(),t=r[this.uid]&&r[this.uid][e]):t=r[this.uid],t?t:!1},removeEventListener:function(e,t){var n,o,a=this;if(e=e.toLowerCase(),/\s/.test(e))return i.each(e.split(/\s+/),function(e){a.removeEventListener(e,t)}),void 0;if(n=r[this.uid]&&r[this.uid][e]){if(t){for(o=n.length-1;o>=0;o--)if(n[o].fn===t){n.splice(o,1);break}}else n=[];n.length||(delete r[this.uid][e],i.isEmptyObj(r[this.uid])&&delete r[this.uid])}},removeAllEventListeners:function(){r[this.uid]&&delete r[this.uid]},dispatchEvent:function(e){var n,o,a,s,u,c={},l=!0;if("string"!==i.typeOf(e)){if(s=e,"string"!==i.typeOf(s.type))throw new t.EventException(t.EventException.UNSPECIFIED_EVENT_TYPE_ERR);e=s.type,s.total!==u&&s.loaded!==u&&(c.total=s.total,c.loaded=s.loaded),c.async=s.async||!1}if(-1!==e.indexOf("::")?function(t){n=t[0],e=t[1]}(e.split("::")):n=this.uid,e=e.toLowerCase(),o=r[n]&&r[n][e]){o.sort(function(e,t){return t.priority-e.priority}),a=[].slice.call(arguments),a.shift(),c.type=e,a.unshift(c);var d=[];i.each(o,function(e){a[0].target=e.scope,c.async?d.push(function(t){setTimeout(function(){t(e.fn.apply(e.scope,a)===!1)},1)}):d.push(function(t){t(e.fn.apply(e.scope,a)===!1)})}),d.length&&i.inSeries(d,function(e){l=!e})}return l},bindOnce:function(e,t,i,n){var r=this;r.bind.call(this,e,function o(){return r.unbind(e,o),t.apply(this,arguments)},i,n)},bind:function(){this.addEventListener.apply(this,arguments)},unbind:function(){this.removeEventListener.apply(this,arguments)},unbindAll:function(){this.removeAllEventListeners.apply(this,arguments)},trigger:function(){return this.dispatchEvent.apply(this,arguments)},handleEventProps:function(e){var t=this;this.bind(e.join(" "),function(e){var t="on"+e.type.toLowerCase();"function"===i.typeOf(this[t])&&this[t].apply(this,arguments)}),i.each(e,function(e){e="on"+e.toLowerCase(e),"undefined"===i.typeOf(t[e])&&(t[e]=null)})}}),n.instance=new n,n}),n("moxie/runtime/Runtime",["moxie/core/utils/Env","moxie/core/utils/Basic","moxie/core/utils/Dom","moxie/core/EventTarget"],function(e,t,i,n){function r(e,n,o,s,u){var c,l=this,d=t.guid(n+"_"),m=u||"browser";e=e||{},a[d]=this,o=t.extend({access_binary:!1,access_image_binary:!1,display_media:!1,do_cors:!1,drag_and_drop:!1,filter_by_extension:!0,resize_image:!1,report_upload_progress:!1,return_response_headers:!1,return_response_type:!1,return_status_code:!0,send_custom_headers:!1,select_file:!1,select_folder:!1,select_multiple:!0,send_binary_string:!1,send_browser_cookies:!0,send_multipart:!0,slice_blob:!1,stream_upload:!1,summon_file_dialog:!1,upload_filesize:!0,use_http_method:!0},o),e.preferred_caps&&(m=r.getMode(s,e.preferred_caps,m)),c=function(){var e={};return{exec:function(t,i,n,r){return c[i]&&(e[t]||(e[t]={context:this,instance:new c[i]}),e[t].instance[n])?e[t].instance[n].apply(this,r):void 0},removeInstance:function(t){delete e[t]},removeAllInstances:function(){var i=this;t.each(e,function(e,n){"function"===t.typeOf(e.instance.destroy)&&e.instance.destroy.call(e.context),i.removeInstance(n)})}}}(),t.extend(this,{initialized:!1,uid:d,type:n,mode:r.getMode(s,e.required_caps,m),shimid:d+"_container",clients:0,options:e,can:function(e,i){var n=arguments[2]||o;if("string"===t.typeOf(e)&&"undefined"===t.typeOf(i)&&(e=r.parseCaps(e)),"object"===t.typeOf(e)){for(var a in e)if(!this.can(a,e[a],n))return!1;return!0}return"function"===t.typeOf(n[e])?n[e].call(this,i):i===n[e]},getShimContainer:function(){var e,n=i.get(this.shimid);return n||(e=i.get(this.options.container)||document.body,n=document.createElement("div"),n.id=this.shimid,n.className="moxie-shim moxie-shim-"+this.type,t.extend(n.style,{position:"absolute",top:"0px",left:"0px",width:"1px",height:"1px",overflow:"hidden"}),e.appendChild(n),e=null),n},getShim:function(){return c},shimExec:function(e,t){var i=[].slice.call(arguments,2);return l.getShim().exec.call(this,this.uid,e,t,i)},exec:function(e,t){var i=[].slice.call(arguments,2);return l[e]&&l[e][t]?l[e][t].apply(this,i):l.shimExec.apply(this,arguments)},destroy:function(){if(l){var e=i.get(this.shimid);e&&e.parentNode.removeChild(e),c&&c.removeAllInstances(),this.unbindAll(),delete a[this.uid],this.uid=null,d=l=c=e=null}}}),this.mode&&e.required_caps&&!this.can(e.required_caps)&&(this.mode=!1)}var o={},a={};return r.order="html5,flash,silverlight,html4",r.getRuntime=function(e){return a[e]?a[e]:!1},r.addConstructor=function(e,t){t.prototype=n.instance,o[e]=t},r.getConstructor=function(e){return o[e]||null},r.getInfo=function(e){var t=r.getRuntime(e);return t?{uid:t.uid,type:t.type,mode:t.mode,can:function(){return t.can.apply(t,arguments)}}:null},r.parseCaps=function(e){var i={};return"string"!==t.typeOf(e)?e||{}:(t.each(e.split(","),function(e){i[e]=!0}),i)},r.can=function(e,t){var i,n,o=r.getConstructor(e);return o?(i=new o({required_caps:t}),n=i.mode,i.destroy(),!!n):!1},r.thatCan=function(e,t){var i=(t||r.order).split(/\s*,\s*/);for(var n in i)if(r.can(i[n],e))return i[n];return null},r.getMode=function(e,i,n){var r=null;if("undefined"===t.typeOf(n)&&(n="browser"),i&&!t.isEmptyObj(e)){if(t.each(i,function(i,n){if(e.hasOwnProperty(n)){var o=e[n](i);if("string"==typeof o&&(o=[o]),r){if(!(r=t.arrayIntersect(r,o)))return r=!1}else r=o}}),r)return-1!==t.inArray(n,r)?n:r[0];if(r===!1)return!1}return n},r.getGlobalEventTarget=function(){if(/^moxie\./.test(e.global_event_dispatcher)&&!e.can("access_global_ns")){var i=t.guid("moxie_event_target_");window[i]=function(e,t){n.instance.dispatchEvent(e,t)},e.global_event_dispatcher=i}return e.global_event_dispatcher},r.capTrue=function(){return!0},r.capFalse=function(){return!1},r.capTest=function(e){return function(){return!!e}},r}),n("moxie/runtime/RuntimeClient",["moxie/core/utils/Env","moxie/core/Exceptions","moxie/core/utils/Basic","moxie/runtime/Runtime"],function(e,t,i,n){return function(){var e;i.extend(this,{connectRuntime:function(r){function o(i){var a,u;return i.length?(a=i.shift().toLowerCase(),(u=n.getConstructor(a))?(e=new u(r),e.bind("Init",function(){e.initialized=!0,setTimeout(function(){e.clients++,s.ruid=e.uid,s.trigger("RuntimeInit",e)},1)}),e.bind("Error",function(){e.destroy(),o(i)}),e.bind("Exception",function(e,i){var n=i.name+"(#"+i.code+")"+(i.message?", from: "+i.message:"");s.trigger("RuntimeError",new t.RuntimeError(t.RuntimeError.EXCEPTION_ERR,n))}),e.mode?(e.init(),void 0):(e.trigger("Error"),void 0)):(o(i),void 0)):(s.trigger("RuntimeError",new t.RuntimeError(t.RuntimeError.NOT_INIT_ERR)),e=null,void 0)}var a,s=this;if("string"===i.typeOf(r)?a=r:"string"===i.typeOf(r.ruid)&&(a=r.ruid),a){if(e=n.getRuntime(a))return s.ruid=a,e.clients++,e;throw new t.RuntimeError(t.RuntimeError.NOT_INIT_ERR)}o((r.runtime_order||n.order).split(/\s*,\s*/))},disconnectRuntime:function(){e&&--e.clients<=0&&e.destroy(),e=null},getRuntime:function(){return e&&e.uid?e:e=null},exec:function(){return e?e.exec.apply(this,arguments):null},can:function(t){return e?e.can(t):!1}})}}),n("moxie/file/Blob",["moxie/core/utils/Basic","moxie/core/utils/Encode","moxie/runtime/RuntimeClient"],function(e,t,i){function n(o,a){function s(t,i,o){var a,s=r[this.uid];return"string"===e.typeOf(s)&&s.length?(a=new n(null,{type:o,size:i-t}),a.detach(s.substr(t,a.size)),a):null}i.call(this),o&&this.connectRuntime(o),a?"string"===e.typeOf(a)&&(a={data:a}):a={},e.extend(this,{uid:a.uid||e.guid("uid_"),ruid:o,size:a.size||0,type:a.type||"",slice:function(e,t,i){return this.isDetached()?s.apply(this,arguments):this.getRuntime().exec.call(this,"Blob","slice",this.getSource(),e,t,i)},getSource:function(){return r[this.uid]?r[this.uid]:null},detach:function(e){if(this.ruid&&(this.getRuntime().exec.call(this,"Blob","destroy"),this.disconnectRuntime(),this.ruid=null),e=e||"","data:"==e.substr(0,5)){var i=e.indexOf(";base64,");this.type=e.substring(5,i),e=t.atob(e.substring(i+8))}this.size=e.length,r[this.uid]=e},isDetached:function(){return!this.ruid&&"string"===e.typeOf(r[this.uid])},destroy:function(){this.detach(),delete r[this.uid]}}),a.data?this.detach(a.data):r[this.uid]=a}var r={};return n}),n("moxie/core/I18n",["moxie/core/utils/Basic"],function(e){var t={};return{addI18n:function(i){return e.extend(t,i)},translate:function(e){return t[e]||e},_:function(e){return this.translate(e)},sprintf:function(t){var i=[].slice.call(arguments,1);return t.replace(/%[a-z]/g,function(){var t=i.shift();return"undefined"!==e.typeOf(t)?t:""})}}}),n("moxie/core/utils/Mime",["moxie/core/utils/Basic","moxie/core/I18n"],function(e,t){var i="application/msword,doc dot,application/pdf,pdf,application/pgp-signature,pgp,application/postscript,ps ai eps,application/rtf,rtf,application/vnd.ms-excel,xls xlb xlt xla,application/vnd.ms-powerpoint,ppt pps pot ppa,application/zip,zip,application/x-shockwave-flash,swf swfl,application/vnd.openxmlformats-officedocument.wordprocessingml.document,docx,application/vnd.openxmlformats-officedocument.wordprocessingml.template,dotx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,xlsx,application/vnd.openxmlformats-officedocument.presentationml.presentation,pptx,application/vnd.openxmlformats-officedocument.presentationml.template,potx,application/vnd.openxmlformats-officedocument.presentationml.slideshow,ppsx,application/x-javascript,js,application/json,json,audio/mpeg,mp3 mpga mpega mp2,audio/x-wav,wav,audio/x-m4a,m4a,audio/ogg,oga ogg,audio/aiff,aiff aif,audio/flac,flac,audio/aac,aac,audio/ac3,ac3,audio/x-ms-wma,wma,image/bmp,bmp,image/gif,gif,image/jpeg,jpg jpeg jpe,image/photoshop,psd,image/png,png,image/svg+xml,svg svgz,image/tiff,tiff tif,text/plain,asc txt text diff log,text/html,htm html xhtml,text/css,css,text/csv,csv,text/rtf,rtf,video/mpeg,mpeg mpg mpe m2v,video/quicktime,qt mov,video/mp4,mp4,video/x-m4v,m4v,video/x-flv,flv,video/x-ms-wmv,wmv,video/avi,avi,video/webm,webm,video/3gpp,3gpp 3gp,video/3gpp2,3g2,video/vnd.rn-realvideo,rv,video/ogg,ogv,video/x-matroska,mkv,application/vnd.oasis.opendocument.formula-template,otf,application/octet-stream,exe",n={},r={},o=function(e){var t,i,o,a=e.split(/,/);for(t=0;t<a.length;t+=2){for(o=a[t+1].split(/ /),i=0;i<o.length;i++)n[o[i]]=a[t];r[a[t]]=o}},a=function(t,i){var n,r,o,a,s=[];for(r=0;r<t.length;r++)for(n=t[r].extensions.toLowerCase().split(/\s*,\s*/),o=0;o<n.length;o++){if("*"===n[o])return[];if(a=s[n[o]],i&&/^\w+$/.test(n[o]))s.push("."+n[o]);else if(a&&-1===e.inArray(a,s))s.push(a);else if(!a)return[]}return s},s=function(t){var i=[];return e.each(t,function(t){if(t=t.toLowerCase(),"*"===t)return i=[],!1;var n=t.match(/^(\w+)\/(\*|\w+)$/);n&&("*"===n[2]?e.each(r,function(e,t){new RegExp("^"+n[1]+"/").test(t)&&[].push.apply(i,r[t])}):r[t]&&[].push.apply(i,r[t]))}),i},u=function(i){var n=[],r=[];return"string"===e.typeOf(i)&&(i=e.trim(i).split(/\s*,\s*/)),r=s(i),n.push({title:t.translate("Files"),extensions:r.length?r.join(","):"*"}),n},c=function(e){var t=e&&e.match(/\.([^.]+)$/);return t?t[1].toLowerCase():""},l=function(e){return n[c(e)]||""};return o(i),{mimes:n,extensions:r,addMimeType:o,extList2mimes:a,mimes2exts:s,mimes2extList:u,getFileExtension:c,getFileMime:l}}),n("moxie/file/FileInput",["moxie/core/utils/Basic","moxie/core/utils/Env","moxie/core/utils/Mime","moxie/core/utils/Dom","moxie/core/Exceptions","moxie/core/EventTarget","moxie/core/I18n","moxie/runtime/Runtime","moxie/runtime/RuntimeClient"],function(e,t,i,n,r,o,a,s,u){function c(t){var o,c,d;if(-1!==e.inArray(e.typeOf(t),["string","node"])&&(t={browse_button:t}),c=n.get(t.browse_button),!c)throw new r.DOMException(r.DOMException.NOT_FOUND_ERR);d={accept:[{title:a.translate("All Files"),extensions:"*"}],multiple:!1,required_caps:!1,container:c.parentNode||document.body},t=e.extend({},d,t),"string"==typeof t.required_caps&&(t.required_caps=s.parseCaps(t.required_caps)),"string"==typeof t.accept&&(t.accept=i.mimes2extList(t.accept)),o=n.get(t.container),o||(o=document.body),"static"===n.getStyle(o,"position")&&(o.style.position="relative"),o=c=null,u.call(this),e.extend(this,{uid:e.guid("uid_"),ruid:null,shimid:null,files:null,init:function(){var i=this;i.bind("RuntimeInit",function(r,o){i.ruid=o.uid,i.shimid=o.shimid,i.bind("Ready",function(){i.trigger("Refresh")},999),i.bind("Refresh",function(){var i,r,a,s,u;a=n.get(t.browse_button),s=n.get(o.shimid),a&&(i=n.getPos(a,n.get(t.container)),r=n.getSize(a),u=parseInt(n.getStyle(a,"z-index"),10)||0,s&&e.extend(s.style,{top:i.y+"px",left:i.x+"px",width:r.w+"px",height:r.h+"px",zIndex:u+1})),s=a=null}),o.exec.call(i,"FileInput","init",t)}),i.connectRuntime(e.extend({},t,{required_caps:{select_file:!0}}))},getOption:function(e){return t[e]},setOption:function(e,n){if(t.hasOwnProperty(e)){var o=t[e];switch(e){case"accept":"string"==typeof n&&(n=i.mimes2extList(n));break;case"container":case"required_caps":throw new r.FileException(r.FileException.NO_MODIFICATION_ALLOWED_ERR)}t[e]=n,this.exec("FileInput","setOption",e,n),this.trigger("OptionChanged",e,n,o)}},disable:function(t){var i=this.getRuntime();i&&this.exec("FileInput","disable","undefined"===e.typeOf(t)?!0:t)},refresh:function(){this.trigger("Refresh")},destroy:function(){var t=this.getRuntime();t&&(t.exec.call(this,"FileInput","destroy"),this.disconnectRuntime()),"array"===e.typeOf(this.files)&&e.each(this.files,function(e){e.destroy()}),this.files=null,this.unbindAll()}}),this.handleEventProps(l)}var l=["ready","change","cancel","mouseenter","mouseleave","mousedown","mouseup"];return c.prototype=o.instance,c}),n("moxie/file/File",["moxie/core/utils/Basic","moxie/core/utils/Mime","moxie/file/Blob"],function(e,t,i){function n(n,r){r||(r={}),i.apply(this,arguments),this.type||(this.type=t.getFileMime(r.name));var o;if(r.name)o=r.name.replace(/\\/g,"/"),o=o.substr(o.lastIndexOf("/")+1);else if(this.type){var a=this.type.split("/")[0];o=e.guid((""!==a?a:"file")+"_"),t.extensions[this.type]&&(o+="."+t.extensions[this.type][0])}e.extend(this,{name:o||e.guid("file_"),relativePath:"",lastModifiedDate:r.lastModifiedDate||(new Date).toLocaleString()})}return n.prototype=i.prototype,n}),n("moxie/file/FileDrop",["moxie/core/I18n","moxie/core/utils/Dom","moxie/core/Exceptions","moxie/core/utils/Basic","moxie/core/utils/Env","moxie/file/File","moxie/runtime/RuntimeClient","moxie/core/EventTarget","moxie/core/utils/Mime"],function(e,t,i,n,r,o,a,s,u){function c(i){var r,o=this;"string"==typeof i&&(i={drop_zone:i}),r={accept:[{title:e.translate("All Files"),extensions:"*"}],required_caps:{drag_and_drop:!0}},i="object"==typeof i?n.extend({},r,i):r,i.container=t.get(i.drop_zone)||document.body,"static"===t.getStyle(i.container,"position")&&(i.container.style.position="relative"),"string"==typeof i.accept&&(i.accept=u.mimes2extList(i.accept)),a.call(o),n.extend(o,{uid:n.guid("uid_"),ruid:null,files:null,init:function(){o.bind("RuntimeInit",function(e,t){o.ruid=t.uid,t.exec.call(o,"FileDrop","init",i),o.dispatchEvent("ready")
}),o.connectRuntime(i)},destroy:function(){var e=this.getRuntime();e&&(e.exec.call(this,"FileDrop","destroy"),this.disconnectRuntime()),this.files=null,this.unbindAll()}}),this.handleEventProps(l)}var l=["ready","dragenter","dragleave","drop","error"];return c.prototype=s.instance,c}),n("moxie/file/FileReader",["moxie/core/utils/Basic","moxie/core/utils/Encode","moxie/core/Exceptions","moxie/core/EventTarget","moxie/file/Blob","moxie/runtime/RuntimeClient"],function(e,t,i,n,r,o){function a(){function n(e,n){if(this.trigger("loadstart"),this.readyState===a.LOADING)return this.trigger("error",new i.DOMException(i.DOMException.INVALID_STATE_ERR)),this.trigger("loadend"),void 0;if(!(n instanceof r))return this.trigger("error",new i.DOMException(i.DOMException.NOT_FOUND_ERR)),this.trigger("loadend"),void 0;if(this.result=null,this.readyState=a.LOADING,n.isDetached()){var o=n.getSource();switch(e){case"readAsText":case"readAsBinaryString":this.result=o;break;case"readAsDataURL":this.result="data:"+n.type+";base64,"+t.btoa(o)}this.readyState=a.DONE,this.trigger("load"),this.trigger("loadend")}else this.connectRuntime(n.ruid),this.exec("FileReader","read",e,n)}o.call(this),e.extend(this,{uid:e.guid("uid_"),readyState:a.EMPTY,result:null,error:null,readAsBinaryString:function(e){n.call(this,"readAsBinaryString",e)},readAsDataURL:function(e){n.call(this,"readAsDataURL",e)},readAsText:function(e){n.call(this,"readAsText",e)},abort:function(){this.result=null,-1===e.inArray(this.readyState,[a.EMPTY,a.DONE])&&(this.readyState===a.LOADING&&(this.readyState=a.DONE),this.exec("FileReader","abort"),this.trigger("abort"),this.trigger("loadend"))},destroy:function(){this.abort(),this.exec("FileReader","destroy"),this.disconnectRuntime(),this.unbindAll()}}),this.handleEventProps(s),this.bind("Error",function(e,t){this.readyState=a.DONE,this.error=t},999),this.bind("Load",function(){this.readyState=a.DONE},999)}var s=["loadstart","progress","load","abort","error","loadend"];return a.EMPTY=0,a.LOADING=1,a.DONE=2,a.prototype=n.instance,a}),n("moxie/core/utils/Url",["moxie/core/utils/Basic"],function(e){var t=function(i,n){var r,o=["source","scheme","authority","userInfo","user","pass","host","port","relative","path","directory","file","query","fragment"],a=o.length,s={http:80,https:443},u={},c=/^(?:([^:\/?#]+):)?(?:\/\/()(?:(?:()(?:([^:@\/]*):?([^:@\/]*))?@)?(\[[\da-fA-F:]+\]|[^:\/?#]*)(?::(\d*))?))?()(?:(()(?:(?:[^?#\/]*\/)*)()(?:[^?#]*))(?:\\?([^#]*))?(?:#(.*))?)/,l=c.exec(i||""),d=/^\/\/\w/.test(i);switch(e.typeOf(n)){case"undefined":n=t(document.location.href,!1);break;case"string":n=t(n,!1)}for(;a--;)l[a]&&(u[o[a]]=l[a]);if(r=!d&&!u.scheme,(d||r)&&(u.scheme=n.scheme),r){u.host=n.host,u.port=n.port;var m="";/^[^\/]/.test(u.path)&&(m=n.path,m=/\/[^\/]*\.[^\/]*$/.test(m)?m.replace(/\/[^\/]+$/,"/"):m.replace(/\/?$/,"/")),u.path=m+(u.path||"")}return u.port||(u.port=s[u.scheme]||80),u.port=parseInt(u.port,10),u.path||(u.path="/"),delete u.source,u},i=function(e){var i={http:80,https:443},n="object"==typeof e?e:t(e);return n.scheme+"://"+n.host+(n.port!==i[n.scheme]?":"+n.port:"")+n.path+(n.query?n.query:"")},n=function(e){function i(e){return[e.scheme,e.host,e.port].join("/")}return"string"==typeof e&&(e=t(e)),i(t())===i(e)};return{parseUrl:t,resolveUrl:i,hasSameOrigin:n}}),n("moxie/runtime/RuntimeTarget",["moxie/core/utils/Basic","moxie/runtime/RuntimeClient","moxie/core/EventTarget"],function(e,t,i){function n(){this.uid=e.guid("uid_"),t.call(this),this.destroy=function(){this.disconnectRuntime(),this.unbindAll()}}return n.prototype=i.instance,n}),n("moxie/file/FileReaderSync",["moxie/core/utils/Basic","moxie/runtime/RuntimeClient","moxie/core/utils/Encode"],function(e,t,i){return function(){function n(e,t){if(!t.isDetached()){var n=this.connectRuntime(t.ruid).exec.call(this,"FileReaderSync","read",e,t);return this.disconnectRuntime(),n}var r=t.getSource();switch(e){case"readAsBinaryString":return r;case"readAsDataURL":return"data:"+t.type+";base64,"+i.btoa(r);case"readAsText":for(var o="",a=0,s=r.length;s>a;a++)o+=String.fromCharCode(r[a]);return o}}t.call(this),e.extend(this,{uid:e.guid("uid_"),readAsBinaryString:function(e){return n.call(this,"readAsBinaryString",e)},readAsDataURL:function(e){return n.call(this,"readAsDataURL",e)},readAsText:function(e){return n.call(this,"readAsText",e)}})}}),n("moxie/xhr/FormData",["moxie/core/Exceptions","moxie/core/utils/Basic","moxie/file/Blob"],function(e,t,i){function n(){var e,n=[];t.extend(this,{append:function(r,o){var a=this,s=t.typeOf(o);o instanceof i?e={name:r,value:o}:"array"===s?(r+="[]",t.each(o,function(e){a.append(r,e)})):"object"===s?t.each(o,function(e,t){a.append(r+"["+t+"]",e)}):"null"===s||"undefined"===s||"number"===s&&isNaN(o)?a.append(r,"false"):n.push({name:r,value:o.toString()})},hasBlob:function(){return!!this.getBlob()},getBlob:function(){return e&&e.value||null},getBlobName:function(){return e&&e.name||null},each:function(i){t.each(n,function(e){i(e.value,e.name)}),e&&i(e.value,e.name)},destroy:function(){e=null,n=[]}})}return n}),n("moxie/xhr/XMLHttpRequest",["moxie/core/utils/Basic","moxie/core/Exceptions","moxie/core/EventTarget","moxie/core/utils/Encode","moxie/core/utils/Url","moxie/runtime/Runtime","moxie/runtime/RuntimeTarget","moxie/file/Blob","moxie/file/FileReaderSync","moxie/xhr/FormData","moxie/core/utils/Env","moxie/core/utils/Mime"],function(e,t,i,n,r,o,a,s,u,c,l,d){function m(){this.uid=e.guid("uid_")}function h(){function i(e,t){return I.hasOwnProperty(e)?1===arguments.length?l.can("define_property")?I[e]:A[e]:(l.can("define_property")?I[e]=t:A[e]=t,void 0):void 0}function u(t){function n(){_&&(_.destroy(),_=null),s.dispatchEvent("loadend"),s=null}function r(r){_.bind("LoadStart",function(e){i("readyState",h.LOADING),s.dispatchEvent("readystatechange"),s.dispatchEvent(e),L&&s.upload.dispatchEvent(e)}),_.bind("Progress",function(e){i("readyState")!==h.LOADING&&(i("readyState",h.LOADING),s.dispatchEvent("readystatechange")),s.dispatchEvent(e)}),_.bind("UploadProgress",function(e){L&&s.upload.dispatchEvent({type:"progress",lengthComputable:!1,total:e.total,loaded:e.loaded})}),_.bind("Load",function(t){i("readyState",h.DONE),i("status",Number(r.exec.call(_,"XMLHttpRequest","getStatus")||0)),i("statusText",f[i("status")]||""),i("response",r.exec.call(_,"XMLHttpRequest","getResponse",i("responseType"))),~e.inArray(i("responseType"),["text",""])?i("responseText",i("response")):"document"===i("responseType")&&i("responseXML",i("response")),U=r.exec.call(_,"XMLHttpRequest","getAllResponseHeaders"),s.dispatchEvent("readystatechange"),i("status")>0?(L&&s.upload.dispatchEvent(t),s.dispatchEvent(t)):(F=!0,s.dispatchEvent("error")),n()}),_.bind("Abort",function(e){s.dispatchEvent(e),n()}),_.bind("Error",function(e){F=!0,i("readyState",h.DONE),s.dispatchEvent("readystatechange"),M=!0,s.dispatchEvent(e),n()}),r.exec.call(_,"XMLHttpRequest","send",{url:x,method:v,async:T,user:w,password:y,headers:S,mimeType:D,encoding:O,responseType:s.responseType,withCredentials:s.withCredentials,options:k},t)}var s=this;E=(new Date).getTime(),_=new a,"string"==typeof k.required_caps&&(k.required_caps=o.parseCaps(k.required_caps)),k.required_caps=e.extend({},k.required_caps,{return_response_type:s.responseType}),t instanceof c&&(k.required_caps.send_multipart=!0),e.isEmptyObj(S)||(k.required_caps.send_custom_headers=!0),B||(k.required_caps.do_cors=!0),k.ruid?r(_.connectRuntime(k)):(_.bind("RuntimeInit",function(e,t){r(t)}),_.bind("RuntimeError",function(e,t){s.dispatchEvent("RuntimeError",t)}),_.connectRuntime(k))}function g(){i("responseText",""),i("responseXML",null),i("response",null),i("status",0),i("statusText",""),E=b=null}var x,v,w,y,E,b,_,R,A=this,I={timeout:0,readyState:h.UNSENT,withCredentials:!1,status:0,statusText:"",responseType:"",responseXML:null,responseText:null,response:null},T=!0,S={},O=null,D=null,N=!1,C=!1,L=!1,M=!1,F=!1,B=!1,P=null,H=null,k={},U="";e.extend(this,I,{uid:e.guid("uid_"),upload:new m,open:function(o,a,s,u,c){var l;if(!o||!a)throw new t.DOMException(t.DOMException.SYNTAX_ERR);if(/[\u0100-\uffff]/.test(o)||n.utf8_encode(o)!==o)throw new t.DOMException(t.DOMException.SYNTAX_ERR);if(~e.inArray(o.toUpperCase(),["CONNECT","DELETE","GET","HEAD","OPTIONS","POST","PUT","TRACE","TRACK"])&&(v=o.toUpperCase()),~e.inArray(v,["CONNECT","TRACE","TRACK"]))throw new t.DOMException(t.DOMException.SECURITY_ERR);if(a=n.utf8_encode(a),l=r.parseUrl(a),B=r.hasSameOrigin(l),x=r.resolveUrl(a),(u||c)&&!B)throw new t.DOMException(t.DOMException.INVALID_ACCESS_ERR);if(w=u||l.user,y=c||l.pass,T=s||!0,T===!1&&(i("timeout")||i("withCredentials")||""!==i("responseType")))throw new t.DOMException(t.DOMException.INVALID_ACCESS_ERR);N=!T,C=!1,S={},g.call(this),i("readyState",h.OPENED),this.dispatchEvent("readystatechange")},setRequestHeader:function(r,o){var a=["accept-charset","accept-encoding","access-control-request-headers","access-control-request-method","connection","content-length","cookie","cookie2","content-transfer-encoding","date","expect","host","keep-alive","origin","referer","te","trailer","transfer-encoding","upgrade","user-agent","via"];if(i("readyState")!==h.OPENED||C)throw new t.DOMException(t.DOMException.INVALID_STATE_ERR);if(/[\u0100-\uffff]/.test(r)||n.utf8_encode(r)!==r)throw new t.DOMException(t.DOMException.SYNTAX_ERR);return r=e.trim(r).toLowerCase(),~e.inArray(r,a)||/^(proxy\-|sec\-)/.test(r)?!1:(S[r]?S[r]+=", "+o:S[r]=o,!0)},hasRequestHeader:function(e){return e&&S[e.toLowerCase()]||!1},getAllResponseHeaders:function(){return U||""},getResponseHeader:function(t){return t=t.toLowerCase(),F||~e.inArray(t,["set-cookie","set-cookie2"])?null:U&&""!==U&&(R||(R={},e.each(U.split(/\r\n/),function(t){var i=t.split(/:\s+/);2===i.length&&(i[0]=e.trim(i[0]),R[i[0].toLowerCase()]={header:i[0],value:e.trim(i[1])})})),R.hasOwnProperty(t))?R[t].header+": "+R[t].value:null},overrideMimeType:function(n){var r,o;if(~e.inArray(i("readyState"),[h.LOADING,h.DONE]))throw new t.DOMException(t.DOMException.INVALID_STATE_ERR);if(n=e.trim(n.toLowerCase()),/;/.test(n)&&(r=n.match(/^([^;]+)(?:;\scharset\=)?(.*)$/))&&(n=r[1],r[2]&&(o=r[2])),!d.mimes[n])throw new t.DOMException(t.DOMException.SYNTAX_ERR);P=n,H=o},send:function(i,r){if(k="string"===e.typeOf(r)?{ruid:r}:r?r:{},this.readyState!==h.OPENED||C)throw new t.DOMException(t.DOMException.INVALID_STATE_ERR);if(i instanceof s)k.ruid=i.ruid,D=i.type||"application/octet-stream";else if(i instanceof c){if(i.hasBlob()){var o=i.getBlob();k.ruid=o.ruid,D=o.type||"application/octet-stream"}}else"string"==typeof i&&(O="UTF-8",D="text/plain;charset=UTF-8",i=n.utf8_encode(i));this.withCredentials||(this.withCredentials=k.required_caps&&k.required_caps.send_browser_cookies&&!B),L=!N&&this.upload.hasEventListener(),F=!1,M=!i,N||(C=!0),u.call(this,i)},abort:function(){if(F=!0,N=!1,~e.inArray(i("readyState"),[h.UNSENT,h.OPENED,h.DONE]))i("readyState",h.UNSENT);else{if(i("readyState",h.DONE),C=!1,!_)throw new t.DOMException(t.DOMException.INVALID_STATE_ERR);_.getRuntime().exec.call(_,"XMLHttpRequest","abort",M),M=!0}},destroy:function(){_&&("function"===e.typeOf(_.destroy)&&_.destroy(),_=null),this.unbindAll(),this.upload&&(this.upload.unbindAll(),this.upload=null)}}),this.handleEventProps(p.concat(["readystatechange"])),this.upload.handleEventProps(p)}var f={100:"Continue",101:"Switching Protocols",102:"Processing",200:"OK",201:"Created",202:"Accepted",203:"Non-Authoritative Information",204:"No Content",205:"Reset Content",206:"Partial Content",207:"Multi-Status",226:"IM Used",300:"Multiple Choices",301:"Moved Permanently",302:"Found",303:"See Other",304:"Not Modified",305:"Use Proxy",306:"Reserved",307:"Temporary Redirect",400:"Bad Request",401:"Unauthorized",402:"Payment Required",403:"Forbidden",404:"Not Found",405:"Method Not Allowed",406:"Not Acceptable",407:"Proxy Authentication Required",408:"Request Timeout",409:"Conflict",410:"Gone",411:"Length Required",412:"Precondition Failed",413:"Request Entity Too Large",414:"Request-URI Too Long",415:"Unsupported Media Type",416:"Requested Range Not Satisfiable",417:"Expectation Failed",422:"Unprocessable Entity",423:"Locked",424:"Failed Dependency",426:"Upgrade Required",500:"Internal Server Error",501:"Not Implemented",502:"Bad Gateway",503:"Service Unavailable",504:"Gateway Timeout",505:"HTTP Version Not Supported",506:"Variant Also Negotiates",507:"Insufficient Storage",510:"Not Extended"};m.prototype=i.instance;var p=["loadstart","progress","abort","error","load","timeout","loadend"];return h.UNSENT=0,h.OPENED=1,h.HEADERS_RECEIVED=2,h.LOADING=3,h.DONE=4,h.prototype=i.instance,h}),n("moxie/runtime/Transporter",["moxie/core/utils/Basic","moxie/core/utils/Encode","moxie/runtime/RuntimeClient","moxie/core/EventTarget"],function(e,t,i,n){function r(){function n(){l=d=0,c=this.result=null}function o(t,i){var n=this;u=i,n.bind("TransportingProgress",function(t){d=t.loaded,l>d&&-1===e.inArray(n.state,[r.IDLE,r.DONE])&&a.call(n)},999),n.bind("TransportingComplete",function(){d=l,n.state=r.DONE,c=null,n.result=u.exec.call(n,"Transporter","getAsBlob",t||"")},999),n.state=r.BUSY,n.trigger("TransportingStarted"),a.call(n)}function a(){var e,i=this,n=l-d;m>n&&(m=n),e=t.btoa(c.substr(d,m)),u.exec.call(i,"Transporter","receive",e,l)}var s,u,c,l,d,m;i.call(this),e.extend(this,{uid:e.guid("uid_"),state:r.IDLE,result:null,transport:function(t,i,r){var a=this;if(r=e.extend({chunk_size:204798},r),(s=r.chunk_size%3)&&(r.chunk_size+=3-s),m=r.chunk_size,n.call(this),c=t,l=t.length,"string"===e.typeOf(r)||r.ruid)o.call(a,i,this.connectRuntime(r));else{var u=function(e,t){a.unbind("RuntimeInit",u),o.call(a,i,t)};this.bind("RuntimeInit",u),this.connectRuntime(r)}},abort:function(){var e=this;e.state=r.IDLE,u&&(u.exec.call(e,"Transporter","clear"),e.trigger("TransportingAborted")),n.call(e)},destroy:function(){this.unbindAll(),u=null,this.disconnectRuntime(),n.call(this)}})}return r.IDLE=0,r.BUSY=1,r.DONE=2,r.prototype=n.instance,r}),n("moxie/image/Image",["moxie/core/utils/Basic","moxie/core/utils/Dom","moxie/core/Exceptions","moxie/file/FileReaderSync","moxie/xhr/XMLHttpRequest","moxie/runtime/Runtime","moxie/runtime/RuntimeClient","moxie/runtime/Transporter","moxie/core/utils/Env","moxie/core/EventTarget","moxie/file/Blob","moxie/file/File","moxie/core/utils/Encode"],function(e,t,i,n,r,o,a,s,u,c,l,d,m){function h(){function n(e){try{return e||(e=this.exec("Image","getInfo")),this.size=e.size,this.width=e.width,this.height=e.height,this.type=e.type,this.meta=e.meta,""===this.name&&(this.name=e.name),!0}catch(t){return this.trigger("error",t.code),!1}}function c(t){var n=e.typeOf(t);try{if(t instanceof h){if(!t.size)throw new i.DOMException(i.DOMException.INVALID_STATE_ERR);p.apply(this,arguments)}else if(t instanceof l){if(!~e.inArray(t.type,["image/jpeg","image/png"]))throw new i.ImageError(i.ImageError.WRONG_FORMAT);g.apply(this,arguments)}else if(-1!==e.inArray(n,["blob","file"]))c.call(this,new d(null,t),arguments[1]);else if("string"===n)"data:"===t.substr(0,5)?c.call(this,new l(null,{data:t}),arguments[1]):x.apply(this,arguments);else{if("node"!==n||"img"!==t.nodeName.toLowerCase())throw new i.DOMException(i.DOMException.TYPE_MISMATCH_ERR);c.call(this,t.src,arguments[1])}}catch(r){this.trigger("error",r.code)}}function p(t,i){var n=this.connectRuntime(t.ruid);this.ruid=n.uid,n.exec.call(this,"Image","loadFromImage",t,"undefined"===e.typeOf(i)?!0:i)}function g(t,i){function n(e){r.ruid=e.uid,e.exec.call(r,"Image","loadFromBlob",t)}var r=this;r.name=t.name||"",t.isDetached()?(this.bind("RuntimeInit",function(e,t){n(t)}),i&&"string"==typeof i.required_caps&&(i.required_caps=o.parseCaps(i.required_caps)),this.connectRuntime(e.extend({required_caps:{access_image_binary:!0,resize_image:!0}},i))):n(this.connectRuntime(t.ruid))}function x(e,t){var i,n=this;i=new r,i.open("get",e),i.responseType="blob",i.onprogress=function(e){n.trigger(e)},i.onload=function(){g.call(n,i.response,!0)},i.onerror=function(e){n.trigger(e)},i.onloadend=function(){i.destroy()},i.bind("RuntimeError",function(e,t){n.trigger("RuntimeError",t)}),i.send(null,t)}a.call(this),e.extend(this,{uid:e.guid("uid_"),ruid:null,name:"",size:0,width:0,height:0,type:"",meta:{},clone:function(){this.load.apply(this,arguments)},load:function(){c.apply(this,arguments)},resize:function(t){var n,r,o=this,a={x:0,y:0,width:o.width,height:o.height},s=e.extendIf({width:o.width,height:o.height,type:o.type||"image/jpeg",quality:90,crop:!1,fit:!0,preserveHeaders:!0,resample:"default",multipass:!0},t);try{if(!o.size)throw new i.DOMException(i.DOMException.INVALID_STATE_ERR);if(o.width>h.MAX_RESIZE_WIDTH||o.height>h.MAX_RESIZE_HEIGHT)throw new i.ImageError(i.ImageError.MAX_RESOLUTION_ERR);if(n=o.meta&&o.meta.tiff&&o.meta.tiff.Orientation||1,-1!==e.inArray(n,[5,6,7,8])){var u=s.width;s.width=s.height,s.height=u}if(s.crop){switch(r=Math.max(s.width/o.width,s.height/o.height),t.fit?(a.width=Math.min(Math.ceil(s.width/r),o.width),a.height=Math.min(Math.ceil(s.height/r),o.height),r=s.width/a.width):(a.width=Math.min(s.width,o.width),a.height=Math.min(s.height,o.height),r=1),"boolean"==typeof s.crop&&(s.crop="cc"),s.crop.toLowerCase().replace(/_/,"-")){case"rb":case"right-bottom":a.x=o.width-a.width,a.y=o.height-a.height;break;case"cb":case"center-bottom":a.x=Math.floor((o.width-a.width)/2),a.y=o.height-a.height;break;case"lb":case"left-bottom":a.x=0,a.y=o.height-a.height;break;case"lt":case"left-top":a.x=0,a.y=0;break;case"ct":case"center-top":a.x=Math.floor((o.width-a.width)/2),a.y=0;break;case"rt":case"right-top":a.x=o.width-a.width,a.y=0;break;case"rc":case"right-center":case"right-middle":a.x=o.width-a.width,a.y=Math.floor((o.height-a.height)/2);break;case"lc":case"left-center":case"left-middle":a.x=0,a.y=Math.floor((o.height-a.height)/2);break;case"cc":case"center-center":case"center-middle":default:a.x=Math.floor((o.width-a.width)/2),a.y=Math.floor((o.height-a.height)/2)}a.x=Math.max(a.x,0),a.y=Math.max(a.y,0)}else r=Math.min(s.width/o.width,s.height/o.height),r>1&&!s.fit&&(r=1);this.exec("Image","resize",a,r,s)}catch(c){o.trigger("error",c.code)}},downsize:function(t){var i,n={width:this.width,height:this.height,type:this.type||"image/jpeg",quality:90,crop:!1,fit:!1,preserveHeaders:!0,resample:"default"};i="object"==typeof t?e.extend(n,t):e.extend(n,{width:arguments[0],height:arguments[1],crop:arguments[2],preserveHeaders:arguments[3]}),this.resize(i)},crop:function(e,t,i){this.downsize(e,t,!0,i)},getAsCanvas:function(){if(!u.can("create_canvas"))throw new i.RuntimeError(i.RuntimeError.NOT_SUPPORTED_ERR);return this.exec("Image","getAsCanvas")},getAsBlob:function(e,t){if(!this.size)throw new i.DOMException(i.DOMException.INVALID_STATE_ERR);return this.exec("Image","getAsBlob",e||"image/jpeg",t||90)},getAsDataURL:function(e,t){if(!this.size)throw new i.DOMException(i.DOMException.INVALID_STATE_ERR);return this.exec("Image","getAsDataURL",e||"image/jpeg",t||90)},getAsBinaryString:function(e,t){var i=this.getAsDataURL(e,t);return m.atob(i.substring(i.indexOf("base64,")+7))},embed:function(n,r){function o(t,r){var o=this;if(u.can("create_canvas")){var l=o.getAsCanvas();if(l)return n.appendChild(l),l=null,o.destroy(),c.trigger("embedded"),void 0}var d=o.getAsDataURL(t,r);if(!d)throw new i.ImageError(i.ImageError.WRONG_FORMAT);if(u.can("use_data_uri_of",d.length))n.innerHTML='<img src="'+d+'" width="'+o.width+'" height="'+o.height+'" alt="" />',o.destroy(),c.trigger("embedded");else{var h=new s;h.bind("TransportingComplete",function(){a=c.connectRuntime(this.result.ruid),c.bind("Embedded",function(){e.extend(a.getShimContainer().style,{top:"0px",left:"0px",width:o.width+"px",height:o.height+"px"}),a=null},999),a.exec.call(c,"ImageView","display",this.result.uid,width,height),o.destroy()}),h.transport(m.atob(d.substring(d.indexOf("base64,")+7)),t,{required_caps:{display_media:!0},runtime_order:"flash,silverlight",container:n})}}var a,c=this,l=e.extend({width:this.width,height:this.height,type:this.type||"image/jpeg",quality:90,fit:!0,resample:"nearest"},r);try{if(!(n=t.get(n)))throw new i.DOMException(i.DOMException.INVALID_NODE_TYPE_ERR);if(!this.size)throw new i.DOMException(i.DOMException.INVALID_STATE_ERR);this.width>h.MAX_RESIZE_WIDTH||this.height>h.MAX_RESIZE_HEIGHT;var d=new h;return d.bind("Resize",function(){o.call(this,l.type,l.quality)}),d.bind("Load",function(){this.downsize(l)}),this.meta.thumb&&this.meta.thumb.width>=l.width&&this.meta.thumb.height>=l.height?d.load(this.meta.thumb.data):d.clone(this,!1),d}catch(f){this.trigger("error",f.code)}},destroy:function(){this.ruid&&(this.getRuntime().exec.call(this,"Image","destroy"),this.disconnectRuntime()),this.meta&&this.meta.thumb&&this.meta.thumb.data.destroy(),this.unbindAll()}}),this.handleEventProps(f),this.bind("Load Resize",function(){return n.call(this)},999)}var f=["progress","load","error","resize","embedded"];return h.MAX_RESIZE_WIDTH=8192,h.MAX_RESIZE_HEIGHT=8192,h.prototype=c.instance,h}),n("moxie/runtime/html5/Runtime",["moxie/core/utils/Basic","moxie/core/Exceptions","moxie/runtime/Runtime","moxie/core/utils/Env"],function(e,t,i,n){function o(t){var o=this,u=i.capTest,c=i.capTrue,l=e.extend({access_binary:u(window.FileReader||window.File&&window.File.getAsDataURL),access_image_binary:function(){return o.can("access_binary")&&!!s.Image},display_media:u((n.can("create_canvas")||n.can("use_data_uri_over32kb"))&&r("moxie/image/Image")),do_cors:u(window.XMLHttpRequest&&"withCredentials"in new XMLHttpRequest),drag_and_drop:u(function(){var e=document.createElement("div");return("draggable"in e||"ondragstart"in e&&"ondrop"in e)&&("IE"!==n.browser||n.verComp(n.version,9,">"))}()),filter_by_extension:u(function(){return!("Chrome"===n.browser&&n.verComp(n.version,28,"<")||"IE"===n.browser&&n.verComp(n.version,10,"<")||"Safari"===n.browser&&n.verComp(n.version,7,"<")||"Firefox"===n.browser&&n.verComp(n.version,37,"<"))}()),return_response_headers:c,return_response_type:function(e){return"json"===e&&window.JSON?!0:n.can("return_response_type",e)},return_status_code:c,report_upload_progress:u(window.XMLHttpRequest&&(new XMLHttpRequest).upload),resize_image:function(){return o.can("access_binary")&&n.can("create_canvas")},select_file:function(){return n.can("use_fileinput")&&window.File},select_folder:function(){return o.can("select_file")&&("Chrome"===n.browser&&n.verComp(n.version,21,">=")||"Firefox"===n.browser&&n.verComp(n.version,42,">="))},select_multiple:function(){return!(!o.can("select_file")||"Safari"===n.browser&&"Windows"===n.os||"iOS"===n.os&&n.verComp(n.osVersion,"7.0.0",">")&&n.verComp(n.osVersion,"8.0.0","<"))},send_binary_string:u(window.XMLHttpRequest&&((new XMLHttpRequest).sendAsBinary||window.Uint8Array&&window.ArrayBuffer)),send_custom_headers:u(window.XMLHttpRequest),send_multipart:function(){return!!(window.XMLHttpRequest&&(new XMLHttpRequest).upload&&window.FormData)||o.can("send_binary_string")},slice_blob:u(window.File&&(File.prototype.mozSlice||File.prototype.webkitSlice||File.prototype.slice)),stream_upload:function(){return o.can("slice_blob")&&o.can("send_multipart")},summon_file_dialog:function(){return o.can("select_file")&&!("Firefox"===n.browser&&n.verComp(n.version,4,"<")||"Opera"===n.browser&&n.verComp(n.version,12,"<")||"IE"===n.browser&&n.verComp(n.version,10,"<"))},upload_filesize:c,use_http_method:c},arguments[2]);i.call(this,t,arguments[1]||a,l),e.extend(this,{init:function(){this.trigger("Init")},destroy:function(e){return function(){e.call(o),e=o=null}}(this.destroy)}),e.extend(this.getShim(),s)}var a="html5",s={};return i.addConstructor(a,o),s}),n("moxie/runtime/html5/file/Blob",["moxie/runtime/html5/Runtime","moxie/file/Blob"],function(e,t){function i(){function e(e,t,i){var n;if(!window.File.prototype.slice)return(n=window.File.prototype.webkitSlice||window.File.prototype.mozSlice)?n.call(e,t,i):null;try{return e.slice(),e.slice(t,i)}catch(r){return e.slice(t,i-t)}}this.slice=function(){return new t(this.getRuntime().uid,e.apply(this,arguments))},this.destroy=function(){this.getRuntime().getShim().removeInstance(this.uid)}}return e.Blob=i}),n("moxie/core/utils/Events",["moxie/core/utils/Basic"],function(e){function t(){this.returnValue=!1}function i(){this.cancelBubble=!0}var n={},r="moxie_"+e.guid(),o=function(o,a,s,u){var c,l;a=a.toLowerCase(),o.addEventListener?(c=s,o.addEventListener(a,c,!1)):o.attachEvent&&(c=function(){var e=window.event;e.target||(e.target=e.srcElement),e.preventDefault=t,e.stopPropagation=i,s(e)},o.attachEvent("on"+a,c)),o[r]||(o[r]=e.guid()),n.hasOwnProperty(o[r])||(n[o[r]]={}),l=n[o[r]],l.hasOwnProperty(a)||(l[a]=[]),l[a].push({func:c,orig:s,key:u})},a=function(t,i,o){var a,s;if(i=i.toLowerCase(),t[r]&&n[t[r]]&&n[t[r]][i]){a=n[t[r]][i];for(var u=a.length-1;u>=0&&(a[u].orig!==o&&a[u].key!==o||(t.removeEventListener?t.removeEventListener(i,a[u].func,!1):t.detachEvent&&t.detachEvent("on"+i,a[u].func),a[u].orig=null,a[u].func=null,a.splice(u,1),o===s));u--);if(a.length||delete n[t[r]][i],e.isEmptyObj(n[t[r]])){delete n[t[r]];try{delete t[r]}catch(c){t[r]=s}}}},s=function(t,i){t&&t[r]&&e.each(n[t[r]],function(e,n){a(t,n,i)})};return{addEvent:o,removeEvent:a,removeAllEvents:s}}),n("moxie/runtime/html5/file/FileInput",["moxie/runtime/html5/Runtime","moxie/file/File","moxie/core/utils/Basic","moxie/core/utils/Dom","moxie/core/utils/Events","moxie/core/utils/Mime","moxie/core/utils/Env"],function(e,t,i,n,r,o,a){function s(){var e,s;i.extend(this,{init:function(u){var c,l,d,m,h,f,p=this,g=p.getRuntime();e=u,d=o.extList2mimes(e.accept,g.can("filter_by_extension")),l=g.getShimContainer(),l.innerHTML='<input id="'+g.uid+'" type="file" style="font-size:999px;opacity:0;"'+(e.multiple&&g.can("select_multiple")?"multiple":"")+(e.directory&&g.can("select_folder")?"webkitdirectory directory":"")+(d?' accept="'+d.join(",")+'"':"")+" />",c=n.get(g.uid),i.extend(c.style,{position:"absolute",top:0,left:0,width:"100%",height:"100%"}),m=n.get(e.browse_button),s=n.getStyle(m,"z-index")||"auto",g.can("summon_file_dialog")&&("static"===n.getStyle(m,"position")&&(m.style.position="relative"),r.addEvent(m,"click",function(e){var t=n.get(g.uid);t&&!t.disabled&&t.click(),e.preventDefault()},p.uid),p.bind("Refresh",function(){h=parseInt(s,10)||1,n.get(e.browse_button).style.zIndex=h,this.getRuntime().getShimContainer().style.zIndex=h-1})),f=g.can("summon_file_dialog")?m:l,r.addEvent(f,"mouseover",function(){p.trigger("mouseenter")},p.uid),r.addEvent(f,"mouseout",function(){p.trigger("mouseleave")},p.uid),r.addEvent(f,"mousedown",function(){p.trigger("mousedown")},p.uid),r.addEvent(n.get(e.container),"mouseup",function(){p.trigger("mouseup")},p.uid),(g.can("summon_file_dialog")?c:m).setAttribute("tabindex",-1),c.onchange=function x(){if(p.files=[],i.each(this.files,function(i){var n="";return e.directory&&"."==i.name?!0:(i.webkitRelativePath&&(n="/"+i.webkitRelativePath.replace(/^\//,"")),i=new t(g.uid,i),i.relativePath=n,p.files.push(i),void 0)}),"IE"!==a.browser&&"IEMobile"!==a.browser)this.value="";else{var n=this.cloneNode(!0);this.parentNode.replaceChild(n,this),n.onchange=x}p.files.length&&p.trigger("change")},p.trigger({type:"ready",async:!0}),l=null},setOption:function(e,t){var i=this.getRuntime(),r=n.get(i.uid);switch(e){case"accept":if(t){var a=t.mimes||o.extList2mimes(t,i.can("filter_by_extension"));r.setAttribute("accept",a.join(","))}else r.removeAttribute("accept");break;case"directory":t&&i.can("select_folder")?(r.setAttribute("directory",""),r.setAttribute("webkitdirectory","")):(r.removeAttribute("directory"),r.removeAttribute("webkitdirectory"));break;case"multiple":t&&i.can("select_multiple")?r.setAttribute("multiple",""):r.removeAttribute("multiple")}},disable:function(e){var t,i=this.getRuntime();(t=n.get(i.uid))&&(t.disabled=!!e)},destroy:function(){var t=this.getRuntime(),i=t.getShim(),o=t.getShimContainer(),a=e&&n.get(e.container),u=e&&n.get(e.browse_button);a&&r.removeAllEvents(a,this.uid),u&&(r.removeAllEvents(u,this.uid),u.style.zIndex=s),o&&(r.removeAllEvents(o,this.uid),o.innerHTML=""),i.removeInstance(this.uid),e=o=a=u=i=null}})}return e.FileInput=s}),n("moxie/runtime/html5/file/FileDrop",["moxie/runtime/html5/Runtime","moxie/file/File","moxie/core/utils/Basic","moxie/core/utils/Dom","moxie/core/utils/Events","moxie/core/utils/Mime"],function(e,t,i,n,r,o){function a(){function e(e){if(!e.dataTransfer||!e.dataTransfer.types)return!1;var t=i.toArray(e.dataTransfer.types||[]);return-1!==i.inArray("Files",t)||-1!==i.inArray("public.file-url",t)||-1!==i.inArray("application/x-moz-file",t)}function a(e,i){if(u(e)){var n=new t(f,e);n.relativePath=i||"",p.push(n)}}function s(e){for(var t=[],n=0;n<e.length;n++)[].push.apply(t,e[n].extensions.split(/\s*,\s*/));return-1===i.inArray("*",t)?t:[]}function u(e){if(!g.length)return!0;var t=o.getFileExtension(e.name);return!t||-1!==i.inArray(t,g)}function c(e,t){var n=[];i.each(e,function(e){var t=e.webkitGetAsEntry();t&&(t.isFile?a(e.getAsFile(),t.fullPath):n.push(t))}),n.length?l(n,t):t()}function l(e,t){var n=[];i.each(e,function(e){n.push(function(t){d(e,t)})}),i.inSeries(n,function(){t()})}function d(e,t){e.isFile?e.file(function(i){a(i,e.fullPath),t()},function(){t()}):e.isDirectory?m(e,t):t()}function m(e,t){function i(e){r.readEntries(function(t){t.length?([].push.apply(n,t),i(e)):e()},e)}var n=[],r=e.createReader();i(function(){l(n,t)})}var h,f,p=[],g=[];i.extend(this,{init:function(t){var n,o=this;h=t,f=o.ruid,g=s(h.accept),n=h.container,r.addEvent(n,"dragover",function(t){e(t)&&(t.preventDefault(),t.dataTransfer.dropEffect="copy")},o.uid),r.addEvent(n,"drop",function(t){e(t)&&(t.preventDefault(),p=[],t.dataTransfer.items&&t.dataTransfer.items[0].webkitGetAsEntry?c(t.dataTransfer.items,function(){o.files=p,o.trigger("drop")}):(i.each(t.dataTransfer.files,function(e){a(e)}),o.files=p,o.trigger("drop")))},o.uid),r.addEvent(n,"dragenter",function(){o.trigger("dragenter")},o.uid),r.addEvent(n,"dragleave",function(){o.trigger("dragleave")},o.uid)},destroy:function(){r.removeAllEvents(h&&n.get(h.container),this.uid),f=p=g=h=null,this.getRuntime().getShim().removeInstance(this.uid)}})}return e.FileDrop=a}),n("moxie/runtime/html5/file/FileReader",["moxie/runtime/html5/Runtime","moxie/core/utils/Encode","moxie/core/utils/Basic"],function(e,t,i){function n(){function e(e){return t.atob(e.substring(e.indexOf("base64,")+7))}var n,r=!1;i.extend(this,{read:function(t,o){var a=this;a.result="",n=new window.FileReader,n.addEventListener("progress",function(e){a.trigger(e)}),n.addEventListener("load",function(t){a.result=r?e(n.result):n.result,a.trigger(t)}),n.addEventListener("error",function(e){a.trigger(e,n.error)}),n.addEventListener("loadend",function(e){n=null,a.trigger(e)}),"function"===i.typeOf(n[t])?(r=!1,n[t](o.getSource())):"readAsBinaryString"===t&&(r=!0,n.readAsDataURL(o.getSource()))},abort:function(){n&&n.abort()},destroy:function(){n=null,this.getRuntime().getShim().removeInstance(this.uid)}})}return e.FileReader=n}),n("moxie/runtime/html5/xhr/XMLHttpRequest",["moxie/runtime/html5/Runtime","moxie/core/utils/Basic","moxie/core/utils/Mime","moxie/core/utils/Url","moxie/file/File","moxie/file/Blob","moxie/xhr/FormData","moxie/core/Exceptions","moxie/core/utils/Env"],function(e,t,i,n,r,o,a,s,u){function c(){function e(e,t){var i,n,r=this;i=t.getBlob().getSource(),n=new window.FileReader,n.onload=function(){t.append(t.getBlobName(),new o(null,{type:i.type,data:n.result})),f.send.call(r,e,t)},n.readAsBinaryString(i)}function c(){return!window.XMLHttpRequest||"IE"===u.browser&&u.verComp(u.version,8,"<")?function(){for(var e=["Msxml2.XMLHTTP.6.0","Microsoft.XMLHTTP"],t=0;t<e.length;t++)try{return new ActiveXObject(e[t])}catch(i){}}():new window.XMLHttpRequest}function l(e){var t=e.responseXML,i=e.responseText;return"IE"===u.browser&&i&&t&&!t.documentElement&&/[^\/]+\/[^\+]+\+xml/.test(e.getResponseHeader("Content-Type"))&&(t=new window.ActiveXObject("Microsoft.XMLDOM"),t.async=!1,t.validateOnParse=!1,t.loadXML(i)),t&&("IE"===u.browser&&0!==t.parseError||!t.documentElement||"parsererror"===t.documentElement.tagName)?null:t
}function d(e){var t="----moxieboundary"+(new Date).getTime(),i="--",n="\r\n",r="",a=this.getRuntime();if(!a.can("send_binary_string"))throw new s.RuntimeError(s.RuntimeError.NOT_SUPPORTED_ERR);return m.setRequestHeader("Content-Type","multipart/form-data; boundary="+t),e.each(function(e,a){r+=e instanceof o?i+t+n+'Content-Disposition: form-data; name="'+a+'"; filename="'+unescape(encodeURIComponent(e.name||"blob"))+'"'+n+"Content-Type: "+(e.type||"application/octet-stream")+n+n+e.getSource()+n:i+t+n+'Content-Disposition: form-data; name="'+a+'"'+n+n+unescape(encodeURIComponent(e))+n}),r+=i+t+i+n}var m,h,f=this;t.extend(this,{send:function(i,r){var s=this,l="Mozilla"===u.browser&&u.verComp(u.version,4,">=")&&u.verComp(u.version,7,"<"),f="Android Browser"===u.browser,p=!1;if(h=i.url.replace(/^.+?\/([\w\-\.]+)$/,"$1").toLowerCase(),m=c(),m.open(i.method,i.url,i.async,i.user,i.password),r instanceof o)r.isDetached()&&(p=!0),r=r.getSource();else if(r instanceof a){if(r.hasBlob())if(r.getBlob().isDetached())r=d.call(s,r),p=!0;else if((l||f)&&"blob"===t.typeOf(r.getBlob().getSource())&&window.FileReader)return e.call(s,i,r),void 0;if(r instanceof a){var g=new window.FormData;r.each(function(e,t){e instanceof o?g.append(t,e.getSource()):g.append(t,e)}),r=g}}m.upload?(i.withCredentials&&(m.withCredentials=!0),m.addEventListener("load",function(e){s.trigger(e)}),m.addEventListener("error",function(e){s.trigger(e)}),m.addEventListener("progress",function(e){s.trigger(e)}),m.upload.addEventListener("progress",function(e){s.trigger({type:"UploadProgress",loaded:e.loaded,total:e.total})})):m.onreadystatechange=function(){switch(m.readyState){case 1:break;case 2:break;case 3:var e,t;try{n.hasSameOrigin(i.url)&&(e=m.getResponseHeader("Content-Length")||0),m.responseText&&(t=m.responseText.length)}catch(r){e=t=0}s.trigger({type:"progress",lengthComputable:!!e,total:parseInt(e,10),loaded:t});break;case 4:m.onreadystatechange=function(){};try{if(m.status>=200&&m.status<400){s.trigger("load");break}}catch(r){}s.trigger("error")}},t.isEmptyObj(i.headers)||t.each(i.headers,function(e,t){m.setRequestHeader(t,e)}),""!==i.responseType&&"responseType"in m&&(m.responseType="json"!==i.responseType||u.can("return_response_type","json")?i.responseType:"text"),p?m.sendAsBinary?m.sendAsBinary(r):function(){for(var e=new Uint8Array(r.length),t=0;t<r.length;t++)e[t]=255&r.charCodeAt(t);m.send(e.buffer)}():m.send(r),s.trigger("loadstart")},getStatus:function(){try{if(m)return m.status}catch(e){}return 0},getResponse:function(e){var t=this.getRuntime();try{switch(e){case"blob":var n=new r(t.uid,m.response),o=m.getResponseHeader("Content-Disposition");if(o){var a=o.match(/filename=([\'\"'])([^\1]+)\1/);a&&(h=a[2])}return n.name=h,n.type||(n.type=i.getFileMime(h)),n;case"json":return u.can("return_response_type","json")?m.response:200===m.status&&window.JSON?JSON.parse(m.responseText):null;case"document":return l(m);default:return""!==m.responseText?m.responseText:null}}catch(s){return null}},getAllResponseHeaders:function(){try{return m.getAllResponseHeaders()}catch(e){}return""},abort:function(){m&&m.abort()},destroy:function(){f=h=null,this.getRuntime().getShim().removeInstance(this.uid)}})}return e.XMLHttpRequest=c}),n("moxie/runtime/html5/utils/BinaryReader",["moxie/core/utils/Basic"],function(e){function t(e){e instanceof ArrayBuffer?i.apply(this,arguments):n.apply(this,arguments)}function i(t){var i=new DataView(t);e.extend(this,{readByteAt:function(e){return i.getUint8(e)},writeByteAt:function(e,t){i.setUint8(e,t)},SEGMENT:function(e,n,r){switch(arguments.length){case 2:return t.slice(e,e+n);case 1:return t.slice(e);case 3:if(null===r&&(r=new ArrayBuffer),r instanceof ArrayBuffer){var o=new Uint8Array(this.length()-n+r.byteLength);e>0&&o.set(new Uint8Array(t.slice(0,e)),0),o.set(new Uint8Array(r),e),o.set(new Uint8Array(t.slice(e+n)),e+r.byteLength),this.clear(),t=o.buffer,i=new DataView(t);break}default:return t}},length:function(){return t?t.byteLength:0},clear:function(){i=t=null}})}function n(t){function i(e,i,n){n=3===arguments.length?n:t.length-i-1,t=t.substr(0,i)+e+t.substr(n+i)}e.extend(this,{readByteAt:function(e){return t.charCodeAt(e)},writeByteAt:function(e,t){i(String.fromCharCode(t),e,1)},SEGMENT:function(e,n,r){switch(arguments.length){case 1:return t.substr(e);case 2:return t.substr(e,n);case 3:i(null!==r?r:"",e,n);break;default:return t}},length:function(){return t?t.length:0},clear:function(){t=null}})}return e.extend(t.prototype,{littleEndian:!1,read:function(e,t){var i,n,r;if(e+t>this.length())throw new Error("You are trying to read outside the source boundaries.");for(n=this.littleEndian?0:-8*(t-1),r=0,i=0;t>r;r++)i|=this.readByteAt(e+r)<<Math.abs(n+8*r);return i},write:function(e,t,i){var n,r;if(e>this.length())throw new Error("You are trying to write outside the source boundaries.");for(n=this.littleEndian?0:-8*(i-1),r=0;i>r;r++)this.writeByteAt(e+r,255&t>>Math.abs(n+8*r))},BYTE:function(e){return this.read(e,1)},SHORT:function(e){return this.read(e,2)},LONG:function(e){return this.read(e,4)},SLONG:function(e){var t=this.read(e,4);return t>2147483647?t-4294967296:t},CHAR:function(e){return String.fromCharCode(this.read(e,1))},STRING:function(e,t){return this.asArray("CHAR",e,t).join("")},asArray:function(e,t,i){for(var n=[],r=0;i>r;r++)n[r]=this[e](t+r);return n}}),t}),n("moxie/runtime/html5/image/JPEGHeaders",["moxie/runtime/html5/utils/BinaryReader","moxie/core/Exceptions"],function(e,t){return function i(n){var r,o,a,s=[],u=0;if(r=new e(n),65496!==r.SHORT(0))throw r.clear(),new t.ImageError(t.ImageError.WRONG_FORMAT);for(o=2;o<=r.length();)if(a=r.SHORT(o),a>=65488&&65495>=a)o+=2;else{if(65498===a||65497===a)break;u=r.SHORT(o+2)+2,a>=65505&&65519>=a&&s.push({hex:a,name:"APP"+(15&a),start:o,length:u,segment:r.SEGMENT(o,u)}),o+=u}return r.clear(),{headers:s,restore:function(t){var i,n,r;for(r=new e(t),o=65504==r.SHORT(2)?4+r.SHORT(4):2,n=0,i=s.length;i>n;n++)r.SEGMENT(o,0,s[n].segment),o+=s[n].length;return t=r.SEGMENT(),r.clear(),t},strip:function(t){var n,r,o,a;for(o=new i(t),r=o.headers,o.purge(),n=new e(t),a=r.length;a--;)n.SEGMENT(r[a].start,r[a].length,"");return t=n.SEGMENT(),n.clear(),t},get:function(e){for(var t=[],i=0,n=s.length;n>i;i++)s[i].name===e.toUpperCase()&&t.push(s[i].segment);return t},set:function(e,t){var i,n,r,o=[];for("string"==typeof t?o.push(t):o=t,i=n=0,r=s.length;r>i&&(s[i].name===e.toUpperCase()&&(s[i].segment=o[n],s[i].length=o[n].length,n++),!(n>=o.length));i++);},purge:function(){this.headers=s=[]}}}}),n("moxie/runtime/html5/image/ExifParser",["moxie/core/utils/Basic","moxie/runtime/html5/utils/BinaryReader","moxie/core/Exceptions"],function(e,i,n){function r(o){function a(i,r){var o,a,s,u,c,m,h,f,p=this,g=[],x={},v={1:"BYTE",7:"UNDEFINED",2:"ASCII",3:"SHORT",4:"LONG",5:"RATIONAL",9:"SLONG",10:"SRATIONAL"},w={BYTE:1,UNDEFINED:1,ASCII:1,SHORT:2,LONG:4,RATIONAL:8,SLONG:4,SRATIONAL:8};for(o=p.SHORT(i),a=0;o>a;a++)if(g=[],h=i+2+12*a,s=r[p.SHORT(h)],s!==t){if(u=v[p.SHORT(h+=2)],c=p.LONG(h+=2),m=w[u],!m)throw new n.ImageError(n.ImageError.INVALID_META_ERR);if(h+=4,m*c>4&&(h=p.LONG(h)+d.tiffHeader),h+m*c>=this.length())throw new n.ImageError(n.ImageError.INVALID_META_ERR);"ASCII"!==u?(g=p.asArray(u,h,c),f=1==c?g[0]:g,x[s]=l.hasOwnProperty(s)&&"object"!=typeof f?l[s][f]:f):x[s]=e.trim(p.STRING(h,c).replace(/\0$/,""))}return x}function s(e,t,i){var n,r,o,a=0;if("string"==typeof t){var s=c[e.toLowerCase()];for(var u in s)if(s[u]===t){t=u;break}}n=d[e.toLowerCase()+"IFD"],r=this.SHORT(n);for(var l=0;r>l;l++)if(o=n+12*l+2,this.SHORT(o)==t){a=o+8;break}if(!a)return!1;try{this.write(a,i,4)}catch(m){return!1}return!0}var u,c,l,d,m,h;if(i.call(this,o),c={tiff:{274:"Orientation",270:"ImageDescription",271:"Make",272:"Model",305:"Software",34665:"ExifIFDPointer",34853:"GPSInfoIFDPointer"},exif:{36864:"ExifVersion",40961:"ColorSpace",40962:"PixelXDimension",40963:"PixelYDimension",36867:"DateTimeOriginal",33434:"ExposureTime",33437:"FNumber",34855:"ISOSpeedRatings",37377:"ShutterSpeedValue",37378:"ApertureValue",37383:"MeteringMode",37384:"LightSource",37385:"Flash",37386:"FocalLength",41986:"ExposureMode",41987:"WhiteBalance",41990:"SceneCaptureType",41988:"DigitalZoomRatio",41992:"Contrast",41993:"Saturation",41994:"Sharpness"},gps:{0:"GPSVersionID",1:"GPSLatitudeRef",2:"GPSLatitude",3:"GPSLongitudeRef",4:"GPSLongitude"},thumb:{513:"JPEGInterchangeFormat",514:"JPEGInterchangeFormatLength"}},l={ColorSpace:{1:"sRGB",0:"Uncalibrated"},MeteringMode:{0:"Unknown",1:"Average",2:"CenterWeightedAverage",3:"Spot",4:"MultiSpot",5:"Pattern",6:"Partial",255:"Other"},LightSource:{1:"Daylight",2:"Fliorescent",3:"Tungsten",4:"Flash",9:"Fine weather",10:"Cloudy weather",11:"Shade",12:"Daylight fluorescent (D 5700 - 7100K)",13:"Day white fluorescent (N 4600 -5400K)",14:"Cool white fluorescent (W 3900 - 4500K)",15:"White fluorescent (WW 3200 - 3700K)",17:"Standard light A",18:"Standard light B",19:"Standard light C",20:"D55",21:"D65",22:"D75",23:"D50",24:"ISO studio tungsten",255:"Other"},Flash:{0:"Flash did not fire",1:"Flash fired",5:"Strobe return light not detected",7:"Strobe return light detected",9:"Flash fired, compulsory flash mode",13:"Flash fired, compulsory flash mode, return light not detected",15:"Flash fired, compulsory flash mode, return light detected",16:"Flash did not fire, compulsory flash mode",24:"Flash did not fire, auto mode",25:"Flash fired, auto mode",29:"Flash fired, auto mode, return light not detected",31:"Flash fired, auto mode, return light detected",32:"No flash function",65:"Flash fired, red-eye reduction mode",69:"Flash fired, red-eye reduction mode, return light not detected",71:"Flash fired, red-eye reduction mode, return light detected",73:"Flash fired, compulsory flash mode, red-eye reduction mode",77:"Flash fired, compulsory flash mode, red-eye reduction mode, return light not detected",79:"Flash fired, compulsory flash mode, red-eye reduction mode, return light detected",89:"Flash fired, auto mode, red-eye reduction mode",93:"Flash fired, auto mode, return light not detected, red-eye reduction mode",95:"Flash fired, auto mode, return light detected, red-eye reduction mode"},ExposureMode:{0:"Auto exposure",1:"Manual exposure",2:"Auto bracket"},WhiteBalance:{0:"Auto white balance",1:"Manual white balance"},SceneCaptureType:{0:"Standard",1:"Landscape",2:"Portrait",3:"Night scene"},Contrast:{0:"Normal",1:"Soft",2:"Hard"},Saturation:{0:"Normal",1:"Low saturation",2:"High saturation"},Sharpness:{0:"Normal",1:"Soft",2:"Hard"},GPSLatitudeRef:{N:"North latitude",S:"South latitude"},GPSLongitudeRef:{E:"East longitude",W:"West longitude"}},d={tiffHeader:10},m=d.tiffHeader,u={clear:this.clear},e.extend(this,{read:function(){try{return r.prototype.read.apply(this,arguments)}catch(e){throw new n.ImageError(n.ImageError.INVALID_META_ERR)}},write:function(){try{return r.prototype.write.apply(this,arguments)}catch(e){throw new n.ImageError(n.ImageError.INVALID_META_ERR)}},UNDEFINED:function(){return this.BYTE.apply(this,arguments)},RATIONAL:function(e){return this.LONG(e)/this.LONG(e+4)},SRATIONAL:function(e){return this.SLONG(e)/this.SLONG(e+4)},ASCII:function(e){return this.CHAR(e)},TIFF:function(){return h||null},EXIF:function(){var t=null;if(d.exifIFD){try{t=a.call(this,d.exifIFD,c.exif)}catch(i){return null}if(t.ExifVersion&&"array"===e.typeOf(t.ExifVersion)){for(var n=0,r="";n<t.ExifVersion.length;n++)r+=String.fromCharCode(t.ExifVersion[n]);t.ExifVersion=r}}return t},GPS:function(){var t=null;if(d.gpsIFD){try{t=a.call(this,d.gpsIFD,c.gps)}catch(i){return null}t.GPSVersionID&&"array"===e.typeOf(t.GPSVersionID)&&(t.GPSVersionID=t.GPSVersionID.join("."))}return t},thumb:function(){if(d.IFD1)try{var e=a.call(this,d.IFD1,c.thumb);if("JPEGInterchangeFormat"in e)return this.SEGMENT(d.tiffHeader+e.JPEGInterchangeFormat,e.JPEGInterchangeFormatLength)}catch(t){}return null},setExif:function(e,t){return"PixelXDimension"!==e&&"PixelYDimension"!==e?!1:s.call(this,"exif",e,t)},clear:function(){u.clear(),o=c=l=h=d=u=null}}),65505!==this.SHORT(0)||"EXIF\0"!==this.STRING(4,5).toUpperCase())throw new n.ImageError(n.ImageError.INVALID_META_ERR);if(this.littleEndian=18761==this.SHORT(m),42!==this.SHORT(m+=2))throw new n.ImageError(n.ImageError.INVALID_META_ERR);d.IFD0=d.tiffHeader+this.LONG(m+=2),h=a.call(this,d.IFD0,c.tiff),"ExifIFDPointer"in h&&(d.exifIFD=d.tiffHeader+h.ExifIFDPointer,delete h.ExifIFDPointer),"GPSInfoIFDPointer"in h&&(d.gpsIFD=d.tiffHeader+h.GPSInfoIFDPointer,delete h.GPSInfoIFDPointer),e.isEmptyObj(h)&&(h=null);var f=this.LONG(d.IFD0+12*this.SHORT(d.IFD0)+2);f&&(d.IFD1=d.tiffHeader+f)}return r.prototype=i.prototype,r}),n("moxie/runtime/html5/image/JPEG",["moxie/core/utils/Basic","moxie/core/Exceptions","moxie/runtime/html5/image/JPEGHeaders","moxie/runtime/html5/utils/BinaryReader","moxie/runtime/html5/image/ExifParser"],function(e,t,i,n,r){function o(o){function a(e){var t,i,n=0;for(e||(e=c);n<=e.length();){if(t=e.SHORT(n+=2),t>=65472&&65475>=t)return n+=5,{height:e.SHORT(n),width:e.SHORT(n+=2)};i=e.SHORT(n+=2),n+=i-2}return null}function s(){var e,t,i=d.thumb();return i&&(e=new n(i),t=a(e),e.clear(),t)?(t.data=i,t):null}function u(){d&&l&&c&&(d.clear(),l.purge(),c.clear(),m=l=d=c=null)}var c,l,d,m;if(c=new n(o),65496!==c.SHORT(0))throw new t.ImageError(t.ImageError.WRONG_FORMAT);l=new i(o);try{d=new r(l.get("app1")[0])}catch(h){}m=a.call(this),e.extend(this,{type:"image/jpeg",size:c.length(),width:m&&m.width||0,height:m&&m.height||0,setExif:function(t,i){return d?("object"===e.typeOf(t)?e.each(t,function(e,t){d.setExif(t,e)}):d.setExif(t,i),l.set("app1",d.SEGMENT()),void 0):!1},writeHeaders:function(){return arguments.length?l.restore(arguments[0]):l.restore(o)},stripHeaders:function(e){return l.strip(e)},purge:function(){u.call(this)}}),d&&(this.meta={tiff:d.TIFF(),exif:d.EXIF(),gps:d.GPS(),thumb:s()})}return o}),n("moxie/runtime/html5/image/PNG",["moxie/core/Exceptions","moxie/core/utils/Basic","moxie/runtime/html5/utils/BinaryReader"],function(e,t,i){function n(n){function r(){var e,t;return e=a.call(this,8),"IHDR"==e.type?(t=e.start,{width:s.LONG(t),height:s.LONG(t+=4)}):null}function o(){s&&(s.clear(),n=l=u=c=s=null)}function a(e){var t,i,n,r;return t=s.LONG(e),i=s.STRING(e+=4,4),n=e+=4,r=s.LONG(e+t),{length:t,type:i,start:n,CRC:r}}var s,u,c,l;s=new i(n),function(){var t=0,i=0,n=[35152,20039,3338,6666];for(i=0;i<n.length;i++,t+=2)if(n[i]!=s.SHORT(t))throw new e.ImageError(e.ImageError.WRONG_FORMAT)}(),l=r.call(this),t.extend(this,{type:"image/png",size:s.length(),width:l.width,height:l.height,purge:function(){o.call(this)}}),o.call(this)}return n}),n("moxie/runtime/html5/image/ImageInfo",["moxie/core/utils/Basic","moxie/core/Exceptions","moxie/runtime/html5/image/JPEG","moxie/runtime/html5/image/PNG"],function(e,t,i,n){return function(r){var o,a=[i,n];o=function(){for(var e=0;e<a.length;e++)try{return new a[e](r)}catch(i){}throw new t.ImageError(t.ImageError.WRONG_FORMAT)}(),e.extend(this,{type:"",size:0,width:0,height:0,setExif:function(){},writeHeaders:function(e){return e},stripHeaders:function(e){return e},purge:function(){r=null}}),e.extend(this,o),this.purge=function(){o.purge(),o=null}}}),n("moxie/runtime/html5/image/ResizerCanvas",[],function(){function e(i,n,r){var o=i.width>i.height?"width":"height",a=Math.round(i[o]*n),s=!1;"nearest"!==r&&(.5>n||n>2)&&(n=.5>n?.5:2,s=!0);var u=t(i,n);return s?e(u,a/u[o],r):u}function t(e,t){var i=e.width,n=e.height,r=Math.round(i*t),o=Math.round(n*t),a=document.createElement("canvas");return a.width=r,a.height=o,a.getContext("2d").drawImage(e,0,0,i,n,0,0,r,o),e=null,a}return{scale:e}}),n("moxie/runtime/html5/image/Image",["moxie/runtime/html5/Runtime","moxie/core/utils/Basic","moxie/core/Exceptions","moxie/core/utils/Encode","moxie/file/Blob","moxie/file/File","moxie/runtime/html5/image/ImageInfo","moxie/runtime/html5/image/ResizerCanvas","moxie/core/utils/Mime","moxie/core/utils/Env"],function(e,t,i,n,r,o,a,s,u){function c(){function e(){if(!v&&!g)throw new i.ImageError(i.DOMException.INVALID_STATE_ERR);return v||g}function c(){var t=e();return"canvas"==t.nodeName.toLowerCase()?t:(v=document.createElement("canvas"),v.width=t.width,v.height=t.height,v.getContext("2d").drawImage(t,0,0),v)}function l(e){return n.atob(e.substring(e.indexOf("base64,")+7))}function d(e,t){return"data:"+(t||"")+";base64,"+n.btoa(e)}function m(e){var t=this;g=new Image,g.onerror=function(){p.call(this),t.trigger("error",i.ImageError.WRONG_FORMAT)},g.onload=function(){t.trigger("load")},g.src="data:"==e.substr(0,5)?e:d(e,y.type)}function h(e,t){var n,r=this;return window.FileReader?(n=new FileReader,n.onload=function(){t.call(r,this.result)},n.onerror=function(){r.trigger("error",i.ImageError.WRONG_FORMAT)},n.readAsDataURL(e),void 0):t.call(this,e.getAsDataURL())}function f(e,i){var n=Math.PI/180,r=document.createElement("canvas"),o=r.getContext("2d"),a=e.width,s=e.height;switch(t.inArray(i,[5,6,7,8])>-1?(r.width=s,r.height=a):(r.width=a,r.height=s),i){case 2:o.translate(a,0),o.scale(-1,1);break;case 3:o.translate(a,s),o.rotate(180*n);break;case 4:o.translate(0,s),o.scale(1,-1);break;case 5:o.rotate(90*n),o.scale(1,-1);break;case 6:o.rotate(90*n),o.translate(0,-s);break;case 7:o.rotate(90*n),o.translate(a,-s),o.scale(-1,1);break;case 8:o.rotate(-90*n),o.translate(-a,0)}return o.drawImage(e,0,0,a,s),r}function p(){x&&(x.purge(),x=null),w=g=v=y=null,b=!1}var g,x,v,w,y,E=this,b=!1,_=!0;t.extend(this,{loadFromBlob:function(e){var t=this.getRuntime(),n=arguments.length>1?arguments[1]:!0;if(!t.can("access_binary"))throw new i.RuntimeError(i.RuntimeError.NOT_SUPPORTED_ERR);return y=e,e.isDetached()?(w=e.getSource(),m.call(this,w),void 0):(h.call(this,e.getSource(),function(e){n&&(w=l(e)),m.call(this,e)}),void 0)},loadFromImage:function(e,t){this.meta=e.meta,y=new o(null,{name:e.name,size:e.size,type:e.type}),m.call(this,t?w=e.getAsBinaryString():e.getAsDataURL())},getInfo:function(){var t,i=this.getRuntime();return!x&&w&&i.can("access_image_binary")&&(x=new a(w)),t={width:e().width||0,height:e().height||0,type:y.type||u.getFileMime(y.name),size:w&&w.length||y.size||0,name:y.name||"",meta:null},_&&(t.meta=x&&x.meta||this.meta||{},!t.meta||!t.meta.thumb||t.meta.thumb.data instanceof r||(t.meta.thumb.data=new r(null,{type:"image/jpeg",data:t.meta.thumb.data}))),t},resize:function(t,i,n){var r=document.createElement("canvas");if(r.width=t.width,r.height=t.height,r.getContext("2d").drawImage(e(),t.x,t.y,t.width,t.height,0,0,r.width,r.height),v=s.scale(r,i),_=n.preserveHeaders,!_){var o=this.meta&&this.meta.tiff&&this.meta.tiff.Orientation||1;v=f(v,o)}this.width=v.width,this.height=v.height,b=!0,this.trigger("Resize")},getAsCanvas:function(){return v||(v=c()),v.id=this.uid+"_canvas",v},getAsBlob:function(e,t){return e!==this.type?(b=!0,new o(null,{name:y.name||"",type:e,data:E.getAsDataURL(e,t)})):new o(null,{name:y.name||"",type:e,data:E.getAsBinaryString(e,t)})},getAsDataURL:function(e){var t=arguments[1]||90;if(!b)return g.src;if(c(),"image/jpeg"!==e)return v.toDataURL("image/png");try{return v.toDataURL("image/jpeg",t/100)}catch(i){return v.toDataURL("image/jpeg")}},getAsBinaryString:function(e,t){if(!b)return w||(w=l(E.getAsDataURL(e,t))),w;if("image/jpeg"!==e)w=l(E.getAsDataURL(e,t));else{var i;t||(t=90),c();try{i=v.toDataURL("image/jpeg",t/100)}catch(n){i=v.toDataURL("image/jpeg")}w=l(i),x&&(w=x.stripHeaders(w),_&&(x.meta&&x.meta.exif&&x.setExif({PixelXDimension:this.width,PixelYDimension:this.height}),w=x.writeHeaders(w)),x.purge(),x=null)}return b=!1,w},destroy:function(){E=null,p.call(this),this.getRuntime().getShim().removeInstance(this.uid)}})}return e.Image=c}),n("moxie/runtime/flash/Runtime",["moxie/core/utils/Basic","moxie/core/utils/Env","moxie/core/utils/Dom","moxie/core/Exceptions","moxie/runtime/Runtime"],function(e,t,i,n,o){function a(){var e;try{e=navigator.plugins["Shockwave Flash"],e=e.description}catch(t){try{e=new ActiveXObject("ShockwaveFlash.ShockwaveFlash").GetVariable("$version")}catch(i){e="0.0"}}return e=e.match(/\d+/g),parseFloat(e[0]+"."+e[1])}function s(e){var n=i.get(e);n&&"OBJECT"==n.nodeName&&("IE"===t.browser?(n.style.display="none",function r(){4==n.readyState?u(e):setTimeout(r,10)}()):n.parentNode.removeChild(n))}function u(e){var t=i.get(e);if(t){for(var n in t)"function"==typeof t[n]&&(t[n]=null);t.parentNode.removeChild(t)}}function c(u){var c,m=this;u=e.extend({swf_url:t.swf_url},u),o.call(this,u,l,{access_binary:function(e){return e&&"browser"===m.mode},access_image_binary:function(e){return e&&"browser"===m.mode},display_media:o.capTest(r("moxie/image/Image")),do_cors:o.capTrue,drag_and_drop:!1,report_upload_progress:function(){return"client"===m.mode},resize_image:o.capTrue,return_response_headers:!1,return_response_type:function(t){return"json"===t&&window.JSON?!0:!e.arrayDiff(t,["","text","document"])||"browser"===m.mode},return_status_code:function(t){return"browser"===m.mode||!e.arrayDiff(t,[200,404])},select_file:o.capTrue,select_multiple:o.capTrue,send_binary_string:function(e){return e&&"browser"===m.mode},send_browser_cookies:function(e){return e&&"browser"===m.mode},send_custom_headers:function(e){return e&&"browser"===m.mode},send_multipart:o.capTrue,slice_blob:function(e){return e&&"browser"===m.mode},stream_upload:function(e){return e&&"browser"===m.mode},summon_file_dialog:!1,upload_filesize:function(t){return e.parseSizeStr(t)<=2097152||"client"===m.mode},use_http_method:function(t){return!e.arrayDiff(t,["GET","POST"])}},{access_binary:function(e){return e?"browser":"client"},access_image_binary:function(e){return e?"browser":"client"},report_upload_progress:function(e){return e?"browser":"client"},return_response_type:function(t){return e.arrayDiff(t,["","text","json","document"])?"browser":["client","browser"]},return_status_code:function(t){return e.arrayDiff(t,[200,404])?"browser":["client","browser"]},send_binary_string:function(e){return e?"browser":"client"},send_browser_cookies:function(e){return e?"browser":"client"},send_custom_headers:function(e){return e?"browser":"client"},slice_blob:function(e){return e?"browser":"client"},stream_upload:function(e){return e?"client":"browser"},upload_filesize:function(t){return e.parseSizeStr(t)>=2097152?"client":"browser"}},"client"),a()<11.3&&(this.mode=!1),e.extend(this,{getShim:function(){return i.get(this.uid)},shimExec:function(e,t){var i=[].slice.call(arguments,2);return m.getShim().exec(this.uid,e,t,i)},init:function(){var i,r,a;a=this.getShimContainer(),e.extend(a.style,{position:"absolute",top:"-8px",left:"-8px",width:"9px",height:"9px",overflow:"hidden"}),i='<object id="'+this.uid+'" type="application/x-shockwave-flash" data="'+u.swf_url+'" ',"IE"===t.browser&&(i+='classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" '),i+='width="100%" height="100%" style="outline:0"><param name="movie" value="'+u.swf_url+'" />'+'<param name="flashvars" value="uid='+escape(this.uid)+"&target="+o.getGlobalEventTarget()+'" />'+'<param name="wmode" value="transparent" />'+'<param name="allowscriptaccess" value="always" />'+"</object>","IE"===t.browser?(r=document.createElement("div"),a.appendChild(r),r.outerHTML=i,r=a=null):a.innerHTML=i,c=setTimeout(function(){m&&!m.initialized&&m.trigger("Error",new n.RuntimeError(n.RuntimeError.NOT_INIT_ERR))},5e3)},destroy:function(e){return function(){s(m.uid),e.call(m),clearTimeout(c),u=c=e=m=null}}(this.destroy)},d)}var l="flash",d={};return o.addConstructor(l,c),d}),n("moxie/runtime/flash/file/Blob",["moxie/runtime/flash/Runtime","moxie/file/Blob"],function(e,t){var i={slice:function(e,i,n,r){var o=this.getRuntime();return 0>i?i=Math.max(e.size+i,0):i>0&&(i=Math.min(i,e.size)),0>n?n=Math.max(e.size+n,0):n>0&&(n=Math.min(n,e.size)),e=o.shimExec.call(this,"Blob","slice",i,n,r||""),e&&(e=new t(o.uid,e)),e}};return e.Blob=i}),n("moxie/runtime/flash/file/FileInput",["moxie/runtime/flash/Runtime","moxie/file/File","moxie/core/utils/Dom","moxie/core/utils/Basic"],function(e,t,i,n){var r={init:function(e){var r=this,o=this.getRuntime(),a=i.get(e.browse_button);a&&(a.setAttribute("tabindex",-1),a=null),this.bind("Change",function(){var e=o.shimExec.call(r,"FileInput","getFiles");r.files=[],n.each(e,function(e){r.files.push(new t(o.uid,e))})},999),this.getRuntime().shimExec.call(this,"FileInput","init",{accept:e.accept,multiple:e.multiple}),this.trigger("ready")}};return e.FileInput=r}),n("moxie/runtime/flash/file/FileReader",["moxie/runtime/flash/Runtime","moxie/core/utils/Encode"],function(e,t){function i(e,i){switch(i){case"readAsText":return t.atob(e,"utf8");case"readAsBinaryString":return t.atob(e);case"readAsDataURL":return e}return null}var n={read:function(e,t){var n=this;return n.result="","readAsDataURL"===e&&(n.result="data:"+(t.type||"")+";base64,"),n.bind("Progress",function(t,r){r&&(n.result+=i(r,e))},999),n.getRuntime().shimExec.call(this,"FileReader","readAsBase64",t.uid)}};return e.FileReader=n}),n("moxie/runtime/flash/file/FileReaderSync",["moxie/runtime/flash/Runtime","moxie/core/utils/Encode"],function(e,t){function i(e,i){switch(i){case"readAsText":return t.atob(e,"utf8");case"readAsBinaryString":return t.atob(e);case"readAsDataURL":return e}return null}var n={read:function(e,t){var n,r=this.getRuntime();return(n=r.shimExec.call(this,"FileReaderSync","readAsBase64",t.uid))?("readAsDataURL"===e&&(n="data:"+(t.type||"")+";base64,"+n),i(n,e,t.type)):null}};return e.FileReaderSync=n}),n("moxie/runtime/flash/runtime/Transporter",["moxie/runtime/flash/Runtime","moxie/file/Blob"],function(e,t){var i={getAsBlob:function(e){var i=this.getRuntime(),n=i.shimExec.call(this,"Transporter","getAsBlob",e);return n?new t(i.uid,n):null}};return e.Transporter=i}),n("moxie/runtime/flash/xhr/XMLHttpRequest",["moxie/runtime/flash/Runtime","moxie/core/utils/Basic","moxie/file/Blob","moxie/file/File","moxie/file/FileReaderSync","moxie/runtime/flash/file/FileReaderSync","moxie/xhr/FormData","moxie/runtime/Transporter","moxie/runtime/flash/runtime/Transporter"],function(e,t,i,n,r,o,a,s){var u={send:function(e,n){function r(){e.transport=l.mode,l.shimExec.call(c,"XMLHttpRequest","send",e,n)}function o(e,t){l.shimExec.call(c,"XMLHttpRequest","appendBlob",e,t.uid),n=null,r()}function u(e,t){var i=new s;i.bind("TransportingComplete",function(){t(this.result)}),i.transport(e.getSource(),e.type,{ruid:l.uid})}var c=this,l=c.getRuntime();if(t.isEmptyObj(e.headers)||t.each(e.headers,function(e,t){l.shimExec.call(c,"XMLHttpRequest","setRequestHeader",t,e.toString())}),n instanceof a){var d;if(n.each(function(e,t){e instanceof i?d=t:l.shimExec.call(c,"XMLHttpRequest","append",t,e)}),n.hasBlob()){var m=n.getBlob();m.isDetached()?u(m,function(e){m.destroy(),o(d,e)}):o(d,m)}else n=null,r()}else n instanceof i?n.isDetached()?u(n,function(e){n.destroy(),n=e.uid,r()}):(n=n.uid,r()):r()},getResponse:function(e){var i,o,a=this.getRuntime();if(o=a.shimExec.call(this,"XMLHttpRequest","getResponseAsBlob")){if(o=new n(a.uid,o),"blob"===e)return o;try{if(i=new r,~t.inArray(e,["","text"]))return i.readAsText(o);if("json"===e&&window.JSON)return JSON.parse(i.readAsText(o))}finally{o.destroy()}}return null},abort:function(){var e=this.getRuntime();e.shimExec.call(this,"XMLHttpRequest","abort"),this.dispatchEvent("readystatechange"),this.dispatchEvent("abort")}};return e.XMLHttpRequest=u}),n("moxie/runtime/flash/image/Image",["moxie/runtime/flash/Runtime","moxie/core/utils/Basic","moxie/runtime/Transporter","moxie/file/Blob","moxie/file/FileReaderSync"],function(e,t,i,n,r){var o={loadFromBlob:function(e){function t(e){r.shimExec.call(n,"Image","loadFromBlob",e.uid),n=r=null}var n=this,r=n.getRuntime();if(e.isDetached()){var o=new i;o.bind("TransportingComplete",function(){t(o.result.getSource())}),o.transport(e.getSource(),e.type,{ruid:r.uid})}else t(e.getSource())},loadFromImage:function(e){var t=this.getRuntime();return t.shimExec.call(this,"Image","loadFromImage",e.uid)},getInfo:function(){var e=this.getRuntime(),t=e.shimExec.call(this,"Image","getInfo");return t.meta&&t.meta.thumb&&t.meta.thumb.data&&!(e.meta.thumb.data instanceof n)&&(t.meta.thumb.data=new n(e.uid,t.meta.thumb.data)),t},getAsBlob:function(e,t){var i=this.getRuntime(),r=i.shimExec.call(this,"Image","getAsBlob",e,t);return r?new n(i.uid,r):null},getAsDataURL:function(){var e,t=this.getRuntime(),i=t.Image.getAsBlob.apply(this,arguments);return i?(e=new r,e.readAsDataURL(i)):null}};return e.Image=o}),n("moxie/runtime/silverlight/Runtime",["moxie/core/utils/Basic","moxie/core/utils/Env","moxie/core/utils/Dom","moxie/core/Exceptions","moxie/runtime/Runtime"],function(e,t,i,n,o){function a(e){var t,i,n,r,o,a=!1,s=null,u=0;try{try{s=new ActiveXObject("AgControl.AgControl"),s.IsVersionSupported(e)&&(a=!0),s=null}catch(c){var l=navigator.plugins["Silverlight Plug-In"];if(l){for(t=l.description,"1.0.30226.2"===t&&(t="2.0.30226.2"),i=t.split(".");i.length>3;)i.pop();for(;i.length<4;)i.push(0);for(n=e.split(".");n.length>4;)n.pop();do r=parseInt(n[u],10),o=parseInt(i[u],10),u++;while(u<n.length&&r===o);o>=r&&!isNaN(r)&&(a=!0)}}}catch(d){a=!1}return a}function s(s){var l,d=this;s=e.extend({xap_url:t.xap_url},s),o.call(this,s,u,{access_binary:o.capTrue,access_image_binary:o.capTrue,display_media:o.capTest(r("moxie/image/Image")),do_cors:o.capTrue,drag_and_drop:!1,report_upload_progress:o.capTrue,resize_image:o.capTrue,return_response_headers:function(e){return e&&"client"===d.mode},return_response_type:function(e){return"json"!==e?!0:!!window.JSON},return_status_code:function(t){return"client"===d.mode||!e.arrayDiff(t,[200,404])},select_file:o.capTrue,select_multiple:o.capTrue,send_binary_string:o.capTrue,send_browser_cookies:function(e){return e&&"browser"===d.mode},send_custom_headers:function(e){return e&&"client"===d.mode},send_multipart:o.capTrue,slice_blob:o.capTrue,stream_upload:!0,summon_file_dialog:!1,upload_filesize:o.capTrue,use_http_method:function(t){return"client"===d.mode||!e.arrayDiff(t,["GET","POST"])}},{return_response_headers:function(e){return e?"client":"browser"},return_status_code:function(t){return e.arrayDiff(t,[200,404])?"client":["client","browser"]},send_browser_cookies:function(e){return e?"browser":"client"},send_custom_headers:function(e){return e?"client":"browser"},use_http_method:function(t){return e.arrayDiff(t,["GET","POST"])?"client":["client","browser"]}}),a("2.0.31005.0")&&"Opera"!==t.browser||(this.mode=!1),e.extend(this,{getShim:function(){return i.get(this.uid).content.Moxie},shimExec:function(e,t){var i=[].slice.call(arguments,2);return d.getShim().exec(this.uid,e,t,i)},init:function(){var e;e=this.getShimContainer(),e.innerHTML='<object id="'+this.uid+'" data="data:application/x-silverlight," type="application/x-silverlight-2" width="100%" height="100%" style="outline:none;">'+'<param name="source" value="'+s.xap_url+'"/>'+'<param name="background" value="Transparent"/>'+'<param name="windowless" value="true"/>'+'<param name="enablehtmlaccess" value="true"/>'+'<param name="initParams" value="uid='+this.uid+",target="+o.getGlobalEventTarget()+'"/>'+"</object>",l=setTimeout(function(){d&&!d.initialized&&d.trigger("Error",new n.RuntimeError(n.RuntimeError.NOT_INIT_ERR))},"Windows"!==t.OS?1e4:5e3)},destroy:function(e){return function(){e.call(d),clearTimeout(l),s=l=e=d=null}}(this.destroy)},c)}var u="silverlight",c={};return o.addConstructor(u,s),c}),n("moxie/runtime/silverlight/file/Blob",["moxie/runtime/silverlight/Runtime","moxie/core/utils/Basic","moxie/runtime/flash/file/Blob"],function(e,t,i){return e.Blob=t.extend({},i)}),n("moxie/runtime/silverlight/file/FileInput",["moxie/runtime/silverlight/Runtime","moxie/file/File","moxie/core/utils/Dom","moxie/core/utils/Basic"],function(e,t,i,n){function r(e){for(var t="",i=0;i<e.length;i++)t+=(""!==t?"|":"")+e[i].title+" | *."+e[i].extensions.replace(/,/g,";*.");return t}var o={init:function(e){var o=this,a=this.getRuntime(),s=i.get(e.browse_button);s&&(s.setAttribute("tabindex",-1),s=null),this.bind("Change",function(){var e=a.shimExec.call(o,"FileInput","getFiles");
o.files=[],n.each(e,function(e){o.files.push(new t(a.uid,e))})},999),a.shimExec.call(this,"FileInput","init",r(e.accept),e.multiple),this.trigger("ready")},setOption:function(e,t){"accept"==e&&(t=r(t)),this.getRuntime().shimExec.call(this,"FileInput","setOption",e,t)}};return e.FileInput=o}),n("moxie/runtime/silverlight/file/FileDrop",["moxie/runtime/silverlight/Runtime","moxie/core/utils/Dom","moxie/core/utils/Events"],function(e,t,i){var n={init:function(){var e,n=this,r=n.getRuntime();return e=r.getShimContainer(),i.addEvent(e,"dragover",function(e){e.preventDefault(),e.stopPropagation(),e.dataTransfer.dropEffect="copy"},n.uid),i.addEvent(e,"dragenter",function(e){e.preventDefault();var i=t.get(r.uid).dragEnter(e);i&&e.stopPropagation()},n.uid),i.addEvent(e,"drop",function(e){e.preventDefault();var i=t.get(r.uid).dragDrop(e);i&&e.stopPropagation()},n.uid),r.shimExec.call(this,"FileDrop","init")}};return e.FileDrop=n}),n("moxie/runtime/silverlight/file/FileReader",["moxie/runtime/silverlight/Runtime","moxie/core/utils/Basic","moxie/runtime/flash/file/FileReader"],function(e,t,i){return e.FileReader=t.extend({},i)}),n("moxie/runtime/silverlight/file/FileReaderSync",["moxie/runtime/silverlight/Runtime","moxie/core/utils/Basic","moxie/runtime/flash/file/FileReaderSync"],function(e,t,i){return e.FileReaderSync=t.extend({},i)}),n("moxie/runtime/silverlight/runtime/Transporter",["moxie/runtime/silverlight/Runtime","moxie/core/utils/Basic","moxie/runtime/flash/runtime/Transporter"],function(e,t,i){return e.Transporter=t.extend({},i)}),n("moxie/runtime/silverlight/xhr/XMLHttpRequest",["moxie/runtime/silverlight/Runtime","moxie/core/utils/Basic","moxie/runtime/flash/xhr/XMLHttpRequest","moxie/runtime/silverlight/file/FileReaderSync","moxie/runtime/silverlight/runtime/Transporter"],function(e,t,i){return e.XMLHttpRequest=t.extend({},i)}),n("moxie/runtime/silverlight/image/Image",["moxie/runtime/silverlight/Runtime","moxie/core/utils/Basic","moxie/file/Blob","moxie/runtime/flash/image/Image"],function(e,t,i,n){return e.Image=t.extend({},n,{getInfo:function(){var e=this.getRuntime(),n=["tiff","exif","gps","thumb"],r={meta:{}},o=e.shimExec.call(this,"Image","getInfo");return o.meta&&(t.each(n,function(e){var t,i,n,a,s=o.meta[e];if(s&&s.keys)for(r.meta[e]={},i=0,n=s.keys.length;n>i;i++)t=s.keys[i],a=s[t],a&&(/^(\d|[1-9]\d+)$/.test(a)?a=parseInt(a,10):/^\d*\.\d+$/.test(a)&&(a=parseFloat(a)),r.meta[e][t]=a)}),r.meta&&r.meta.thumb&&r.meta.thumb.data&&!(e.meta.thumb.data instanceof i)&&(r.meta.thumb.data=new i(e.uid,r.meta.thumb.data))),r.width=parseInt(o.width,10),r.height=parseInt(o.height,10),r.size=parseInt(o.size,10),r.type=o.type,r.name=o.name,r},resize:function(e,t,i){this.getRuntime().shimExec.call(this,"Image","resize",e.x,e.y,e.width,e.height,t,i.preserveHeaders,i.resample)}})}),n("moxie/runtime/html4/Runtime",["moxie/core/utils/Basic","moxie/core/Exceptions","moxie/runtime/Runtime","moxie/core/utils/Env"],function(e,t,i,n){function o(t){var o=this,u=i.capTest,c=i.capTrue;i.call(this,t,a,{access_binary:u(window.FileReader||window.File&&File.getAsDataURL),access_image_binary:!1,display_media:u((n.can("create_canvas")||n.can("use_data_uri_over32kb"))&&r("moxie/image/Image")),do_cors:!1,drag_and_drop:!1,filter_by_extension:u(function(){return!("Chrome"===n.browser&&n.verComp(n.version,28,"<")||"IE"===n.browser&&n.verComp(n.version,10,"<")||"Safari"===n.browser&&n.verComp(n.version,7,"<")||"Firefox"===n.browser&&n.verComp(n.version,37,"<"))}()),resize_image:function(){return s.Image&&o.can("access_binary")&&n.can("create_canvas")},report_upload_progress:!1,return_response_headers:!1,return_response_type:function(t){return"json"===t&&window.JSON?!0:!!~e.inArray(t,["text","document",""])},return_status_code:function(t){return!e.arrayDiff(t,[200,404])},select_file:function(){return n.can("use_fileinput")},select_multiple:!1,send_binary_string:!1,send_custom_headers:!1,send_multipart:!0,slice_blob:!1,stream_upload:function(){return o.can("select_file")},summon_file_dialog:function(){return o.can("select_file")&&!("Firefox"===n.browser&&n.verComp(n.version,4,"<")||"Opera"===n.browser&&n.verComp(n.version,12,"<")||"IE"===n.browser&&n.verComp(n.version,10,"<"))},upload_filesize:c,use_http_method:function(t){return!e.arrayDiff(t,["GET","POST"])}}),e.extend(this,{init:function(){this.trigger("Init")},destroy:function(e){return function(){e.call(o),e=o=null}}(this.destroy)}),e.extend(this.getShim(),s)}var a="html4",s={};return i.addConstructor(a,o),s}),n("moxie/runtime/html4/file/FileInput",["moxie/runtime/html4/Runtime","moxie/file/File","moxie/core/utils/Basic","moxie/core/utils/Dom","moxie/core/utils/Events","moxie/core/utils/Mime","moxie/core/utils/Env"],function(e,t,i,n,r,o,a){function s(){function e(){var o,c,d,m,h,f,p=this,g=p.getRuntime();f=i.guid("uid_"),o=g.getShimContainer(),s&&(d=n.get(s+"_form"),d&&(i.extend(d.style,{top:"100%"}),d.firstChild.setAttribute("tabindex",-1))),m=document.createElement("form"),m.setAttribute("id",f+"_form"),m.setAttribute("method","post"),m.setAttribute("enctype","multipart/form-data"),m.setAttribute("encoding","multipart/form-data"),i.extend(m.style,{overflow:"hidden",position:"absolute",top:0,left:0,width:"100%",height:"100%"}),h=document.createElement("input"),h.setAttribute("id",f),h.setAttribute("type","file"),h.setAttribute("accept",l.join(",")),g.can("summon_file_dialog")&&h.setAttribute("tabindex",-1),i.extend(h.style,{fontSize:"999px",opacity:0}),m.appendChild(h),o.appendChild(m),i.extend(h.style,{position:"absolute",top:0,left:0,width:"100%",height:"100%"}),"IE"===a.browser&&a.verComp(a.version,10,"<")&&i.extend(h.style,{filter:"progid:DXImageTransform.Microsoft.Alpha(opacity=0)"}),h.onchange=function(){var i;this.value&&(i=this.files?this.files[0]:{name:this.value},i=new t(g.uid,i),this.onchange=function(){},e.call(p),p.files=[i],h.setAttribute("id",i.uid),m.setAttribute("id",i.uid+"_form"),p.trigger("change"),h=m=null)},g.can("summon_file_dialog")&&(c=n.get(u.browse_button),r.removeEvent(c,"click",p.uid),r.addEvent(c,"click",function(e){h&&!h.disabled&&h.click(),e.preventDefault()},p.uid)),s=f,o=d=c=null}var s,u,c,l=[];i.extend(this,{init:function(t){var i,a=this,s=a.getRuntime();u=t,l=o.extList2mimes(t.accept,s.can("filter_by_extension")),i=s.getShimContainer(),function(){var e,o,l;e=n.get(t.browse_button),c=n.getStyle(e,"z-index")||"auto",s.can("summon_file_dialog")?("static"===n.getStyle(e,"position")&&(e.style.position="relative"),a.bind("Refresh",function(){o=parseInt(c,10)||1,n.get(u.browse_button).style.zIndex=o,this.getRuntime().getShimContainer().style.zIndex=o-1})):e.setAttribute("tabindex",-1),l=s.can("summon_file_dialog")?e:i,r.addEvent(l,"mouseover",function(){a.trigger("mouseenter")},a.uid),r.addEvent(l,"mouseout",function(){a.trigger("mouseleave")},a.uid),r.addEvent(l,"mousedown",function(){a.trigger("mousedown")},a.uid),r.addEvent(n.get(t.container),"mouseup",function(){a.trigger("mouseup")},a.uid),e=null}(),e.call(this),i=null,a.trigger({type:"ready",async:!0})},setOption:function(e,t){var i,r=this.getRuntime();"accept"==e&&(l=t.mimes||o.extList2mimes(t,r.can("filter_by_extension"))),i=n.get(s),i&&i.setAttribute("accept",l.join(","))},disable:function(e){var t;(t=n.get(s))&&(t.disabled=!!e)},destroy:function(){var e=this.getRuntime(),t=e.getShim(),i=e.getShimContainer(),o=u&&n.get(u.container),a=u&&n.get(u.browse_button);o&&r.removeAllEvents(o,this.uid),a&&(r.removeAllEvents(a,this.uid),a.style.zIndex=c),i&&(r.removeAllEvents(i,this.uid),i.innerHTML=""),t.removeInstance(this.uid),s=l=u=i=o=a=t=null}})}return e.FileInput=s}),n("moxie/runtime/html4/file/FileReader",["moxie/runtime/html4/Runtime","moxie/runtime/html5/file/FileReader"],function(e,t){return e.FileReader=t}),n("moxie/runtime/html4/xhr/XMLHttpRequest",["moxie/runtime/html4/Runtime","moxie/core/utils/Basic","moxie/core/utils/Dom","moxie/core/utils/Url","moxie/core/Exceptions","moxie/core/utils/Events","moxie/file/Blob","moxie/xhr/FormData"],function(e,t,i,n,r,o,a,s){function u(){function e(e){var t,n,r,a,s=this,u=!1;if(l){if(t=l.id.replace(/_iframe$/,""),n=i.get(t+"_form")){for(r=n.getElementsByTagName("input"),a=r.length;a--;)switch(r[a].getAttribute("type")){case"hidden":r[a].parentNode.removeChild(r[a]);break;case"file":u=!0}r=[],u||n.parentNode.removeChild(n),n=null}setTimeout(function(){o.removeEvent(l,"load",s.uid),l.parentNode&&l.parentNode.removeChild(l);var t=s.getRuntime().getShimContainer();t.children.length||t.parentNode.removeChild(t),t=l=null,e()},1)}}var u,c,l;t.extend(this,{send:function(d,m){function h(){var i=w.getShimContainer()||document.body,r=document.createElement("div");r.innerHTML='<iframe id="'+f+'_iframe" name="'+f+'_iframe" src="javascript:&quot;&quot;" style="display:none"></iframe>',l=r.firstChild,i.appendChild(l),o.addEvent(l,"load",function(){var i;try{i=l.contentWindow.document||l.contentDocument||window.frames[l.id].document,/^4(0[0-9]|1[0-7]|2[2346])\s/.test(i.title)?u=i.title.replace(/^(\d+).*$/,"$1"):(u=200,c=t.trim(i.body.innerHTML),v.trigger({type:"progress",loaded:c.length,total:c.length}),x&&v.trigger({type:"uploadprogress",loaded:x.size||1025,total:x.size||1025}))}catch(r){if(!n.hasSameOrigin(d.url))return e.call(v,function(){v.trigger("error")}),void 0;u=404}e.call(v,function(){v.trigger("load")})},v.uid)}var f,p,g,x,v=this,w=v.getRuntime();if(u=c=null,m instanceof s&&m.hasBlob()){if(x=m.getBlob(),f=x.uid,g=i.get(f),p=i.get(f+"_form"),!p)throw new r.DOMException(r.DOMException.NOT_FOUND_ERR)}else f=t.guid("uid_"),p=document.createElement("form"),p.setAttribute("id",f+"_form"),p.setAttribute("method",d.method),p.setAttribute("enctype","multipart/form-data"),p.setAttribute("encoding","multipart/form-data"),w.getShimContainer().appendChild(p);p.setAttribute("target",f+"_iframe"),m instanceof s&&m.each(function(e,i){if(e instanceof a)g&&g.setAttribute("name",i);else{var n=document.createElement("input");t.extend(n,{type:"hidden",name:i,value:e}),g?p.insertBefore(n,g):p.appendChild(n)}}),p.setAttribute("action",d.url),h(),p.submit(),v.trigger("loadstart")},getStatus:function(){return u},getResponse:function(e){if("json"===e&&"string"===t.typeOf(c)&&window.JSON)try{return JSON.parse(c.replace(/^\s*<pre[^>]*>/,"").replace(/<\/pre>\s*$/,""))}catch(i){return null}return c},abort:function(){var t=this;l&&l.contentWindow&&(l.contentWindow.stop?l.contentWindow.stop():l.contentWindow.document.execCommand?l.contentWindow.document.execCommand("Stop"):l.src="about:blank"),e.call(this,function(){t.dispatchEvent("abort")})},destroy:function(){this.getRuntime().getShim().removeInstance(this.uid)}})}return e.XMLHttpRequest=u}),n("moxie/runtime/html4/image/Image",["moxie/runtime/html4/Runtime","moxie/runtime/html5/image/Image"],function(e,t){return e.Image=t}),a(["moxie/core/utils/Basic","moxie/core/utils/Encode","moxie/core/utils/Env","moxie/core/Exceptions","moxie/core/utils/Dom","moxie/core/EventTarget","moxie/runtime/Runtime","moxie/runtime/RuntimeClient","moxie/file/Blob","moxie/core/I18n","moxie/core/utils/Mime","moxie/file/FileInput","moxie/file/File","moxie/file/FileDrop","moxie/file/FileReader","moxie/core/utils/Url","moxie/runtime/RuntimeTarget","moxie/xhr/FormData","moxie/xhr/XMLHttpRequest","moxie/image/Image","moxie/core/utils/Events","moxie/runtime/html5/image/ResizerCanvas"])}(this)});
/**
 * Plupload  v2.3.6 Released under GPL License Date: 2017-11-03
   Copyright 2013, Moxiecode Systems AB
   License: http://www.plupload.com/license
 */
!function(e,t){var i=function(){var e={};return t.apply(e,arguments),e.plupload};"function"==typeof define&&define.amd?define("plupload",["./moxie"],i):"object"==typeof module&&module.exports?module.exports=i(require("./moxie")):e.plupload=i(e.moxie)}(this||window,function(e){!function(e,t,i){function n(e){function t(e,t,i){var r={chunks:"slice_blob",jpgresize:"send_binary_string",pngresize:"send_binary_string",progress:"report_upload_progress",multi_selection:"select_multiple",dragdrop:"drag_and_drop",drop_element:"drag_and_drop",headers:"send_custom_headers",urlstream_upload:"send_binary_string",canSendBinary:"send_binary",triggerDialog:"summon_file_dialog"};r[e]?n[r[e]]=t:i||(n[e]=t)}var i=e.required_features,n={};return"string"==typeof i?l.each(i.split(/\s*,\s*/),function(e){t(e,!0)}):"object"==typeof i?l.each(i,function(e,i){t(i,e)}):i===!0&&(e.chunk_size&&e.chunk_size>0&&(n.slice_blob=!0),l.isEmptyObj(e.resize)&&e.multipart!==!1||(n.send_binary_string=!0),e.http_method&&(n.use_http_method=e.http_method),l.each(e,function(e,i){t(i,!!e,!0)})),n}var r=window.setTimeout,s={},a=t.core.utils,o=t.runtime.Runtime,l={VERSION:"2.3.6",STOPPED:1,STARTED:2,QUEUED:1,UPLOADING:2,FAILED:4,DONE:5,GENERIC_ERROR:-100,HTTP_ERROR:-200,IO_ERROR:-300,SECURITY_ERROR:-400,INIT_ERROR:-500,FILE_SIZE_ERROR:-600,FILE_EXTENSION_ERROR:-601,FILE_DUPLICATE_ERROR:-602,IMAGE_FORMAT_ERROR:-700,MEMORY_ERROR:-701,IMAGE_DIMENSIONS_ERROR:-702,moxie:t,mimeTypes:a.Mime.mimes,ua:a.Env,typeOf:a.Basic.typeOf,extend:a.Basic.extend,guid:a.Basic.guid,getAll:function(e){var t,i=[];"array"!==l.typeOf(e)&&(e=[e]);for(var n=e.length;n--;)t=l.get(e[n]),t&&i.push(t);return i.length?i:null},get:a.Dom.get,each:a.Basic.each,getPos:a.Dom.getPos,getSize:a.Dom.getSize,xmlEncode:function(e){var t={"<":"lt",">":"gt","&":"amp",'"':"quot","'":"#39"},i=/[<>&\"\']/g;return e?(""+e).replace(i,function(e){return t[e]?"&"+t[e]+";":e}):e},toArray:a.Basic.toArray,inArray:a.Basic.inArray,inSeries:a.Basic.inSeries,addI18n:t.core.I18n.addI18n,translate:t.core.I18n.translate,sprintf:a.Basic.sprintf,isEmptyObj:a.Basic.isEmptyObj,hasClass:a.Dom.hasClass,addClass:a.Dom.addClass,removeClass:a.Dom.removeClass,getStyle:a.Dom.getStyle,addEvent:a.Events.addEvent,removeEvent:a.Events.removeEvent,removeAllEvents:a.Events.removeAllEvents,cleanName:function(e){var t,i;for(i=[/[\300-\306]/g,"A",/[\340-\346]/g,"a",/\307/g,"C",/\347/g,"c",/[\310-\313]/g,"E",/[\350-\353]/g,"e",/[\314-\317]/g,"I",/[\354-\357]/g,"i",/\321/g,"N",/\361/g,"n",/[\322-\330]/g,"O",/[\362-\370]/g,"o",/[\331-\334]/g,"U",/[\371-\374]/g,"u"],t=0;t<i.length;t+=2)e=e.replace(i[t],i[t+1]);return e=e.replace(/\s+/g,"_"),e=e.replace(/[^a-z0-9_\-\.]+/gi,"")},buildUrl:function(e,t){var i="";return l.each(t,function(e,t){i+=(i?"&":"")+encodeURIComponent(t)+"="+encodeURIComponent(e)}),i&&(e+=(e.indexOf("?")>0?"&":"?")+i),e},formatSize:function(e){function t(e,t){return Math.round(e*Math.pow(10,t))/Math.pow(10,t)}if(e===i||/\D/.test(e))return l.translate("N/A");var n=Math.pow(1024,4);return e>n?t(e/n,1)+" "+l.translate("tb"):e>(n/=1024)?t(e/n,1)+" "+l.translate("gb"):e>(n/=1024)?t(e/n,1)+" "+l.translate("mb"):e>1024?Math.round(e/1024)+" "+l.translate("kb"):e+" "+l.translate("b")},parseSize:a.Basic.parseSizeStr,predictRuntime:function(e,t){var i,n;return i=new l.Uploader(e),n=o.thatCan(i.getOption().required_features,t||e.runtimes),i.destroy(),n},addFileFilter:function(e,t){s[e]=t}};l.addFileFilter("mime_types",function(e,t,i){e.length&&!e.regexp.test(t.name)?(this.trigger("Error",{code:l.FILE_EXTENSION_ERROR,message:l.translate("File extension error."),file:t}),i(!1)):i(!0)}),l.addFileFilter("max_file_size",function(e,t,i){var n;e=l.parseSize(e),t.size!==n&&e&&t.size>e?(this.trigger("Error",{code:l.FILE_SIZE_ERROR,message:l.translate("File size error."),file:t}),i(!1)):i(!0)}),l.addFileFilter("prevent_duplicates",function(e,t,i){if(e)for(var n=this.files.length;n--;)if(t.name===this.files[n].name&&t.size===this.files[n].size)return this.trigger("Error",{code:l.FILE_DUPLICATE_ERROR,message:l.translate("Duplicate file error."),file:t}),i(!1),void 0;i(!0)}),l.addFileFilter("prevent_empty",function(e,t,n){e&&!t.size&&t.size!==i?(this.trigger("Error",{code:l.FILE_SIZE_ERROR,message:l.translate("File size error."),file:t}),n(!1)):n(!0)}),l.Uploader=function(e){function a(){var e,t,i=0;if(this.state==l.STARTED){for(t=0;t<D.length;t++)e||D[t].status!=l.QUEUED?i++:(e=D[t],this.trigger("BeforeUpload",e)&&(e.status=l.UPLOADING,this.trigger("UploadFile",e)));i==D.length&&(this.state!==l.STOPPED&&(this.state=l.STOPPED,this.trigger("StateChanged")),this.trigger("UploadComplete",D))}}function u(e){e.percent=e.size>0?Math.ceil(100*(e.loaded/e.size)):100,d()}function d(){var e,t,n,r=0;for(I.reset(),e=0;e<D.length;e++)t=D[e],t.size!==i?(I.size+=t.origSize,n=t.loaded*t.origSize/t.size,(!t.completeTimestamp||t.completeTimestamp>S)&&(r+=n),I.loaded+=n):I.size=i,t.status==l.DONE?I.uploaded++:t.status==l.FAILED?I.failed++:I.queued++;I.size===i?I.percent=D.length>0?Math.ceil(100*(I.uploaded/D.length)):0:(I.bytesPerSec=Math.ceil(r/((+new Date-S||1)/1e3)),I.percent=I.size>0?Math.ceil(100*(I.loaded/I.size)):0)}function c(){var e=F[0]||P[0];return e?e.getRuntime().uid:!1}function f(){this.bind("FilesAdded FilesRemoved",function(e){e.trigger("QueueChanged"),e.refresh()}),this.bind("CancelUpload",b),this.bind("BeforeUpload",m),this.bind("UploadFile",_),this.bind("UploadProgress",E),this.bind("StateChanged",v),this.bind("QueueChanged",d),this.bind("Error",R),this.bind("FileUploaded",y),this.bind("Destroy",z)}function p(e,i){var n=this,r=0,s=[],a={runtime_order:e.runtimes,required_caps:e.required_features,preferred_caps:x,swf_url:e.flash_swf_url,xap_url:e.silverlight_xap_url};l.each(e.runtimes.split(/\s*,\s*/),function(t){e[t]&&(a[t]=e[t])}),e.browse_button&&l.each(e.browse_button,function(i){s.push(function(s){var u=new t.file.FileInput(l.extend({},a,{accept:e.filters.mime_types,name:e.file_data_name,multiple:e.multi_selection,container:e.container,browse_button:i}));u.onready=function(){var e=o.getInfo(this.ruid);l.extend(n.features,{chunks:e.can("slice_blob"),multipart:e.can("send_multipart"),multi_selection:e.can("select_multiple")}),r++,F.push(this),s()},u.onchange=function(){n.addFile(this.files)},u.bind("mouseenter mouseleave mousedown mouseup",function(t){U||(e.browse_button_hover&&("mouseenter"===t.type?l.addClass(i,e.browse_button_hover):"mouseleave"===t.type&&l.removeClass(i,e.browse_button_hover)),e.browse_button_active&&("mousedown"===t.type?l.addClass(i,e.browse_button_active):"mouseup"===t.type&&l.removeClass(i,e.browse_button_active)))}),u.bind("mousedown",function(){n.trigger("Browse")}),u.bind("error runtimeerror",function(){u=null,s()}),u.init()})}),e.drop_element&&l.each(e.drop_element,function(e){s.push(function(i){var s=new t.file.FileDrop(l.extend({},a,{drop_zone:e}));s.onready=function(){var e=o.getInfo(this.ruid);l.extend(n.features,{chunks:e.can("slice_blob"),multipart:e.can("send_multipart"),dragdrop:e.can("drag_and_drop")}),r++,P.push(this),i()},s.ondrop=function(){n.addFile(this.files)},s.bind("error runtimeerror",function(){s=null,i()}),s.init()})}),l.inSeries(s,function(){"function"==typeof i&&i(r)})}function g(e,n,r,s){var a=new t.image.Image;try{a.onload=function(){n.width>this.width&&n.height>this.height&&n.quality===i&&n.preserve_headers&&!n.crop?(this.destroy(),s(e)):a.downsize(n.width,n.height,n.crop,n.preserve_headers)},a.onresize=function(){var t=this.getAsBlob(e.type,n.quality);this.destroy(),s(t)},a.bind("error runtimeerror",function(){this.destroy(),s(e)}),a.load(e,r)}catch(o){s(e)}}function h(e,i,r){function s(e,i,n){var r=O[e];switch(e){case"max_file_size":"max_file_size"===e&&(O.max_file_size=O.filters.max_file_size=i);break;case"chunk_size":(i=l.parseSize(i))&&(O[e]=i,O.send_file_name=!0);break;case"multipart":O[e]=i,i||(O.send_file_name=!0);break;case"http_method":O[e]="PUT"===i.toUpperCase()?"PUT":"POST";break;case"unique_names":O[e]=i,i&&(O.send_file_name=!0);break;case"filters":"array"===l.typeOf(i)&&(i={mime_types:i}),n?l.extend(O.filters,i):O.filters=i,i.mime_types&&("string"===l.typeOf(i.mime_types)&&(i.mime_types=t.core.utils.Mime.mimes2extList(i.mime_types)),i.mime_types.regexp=function(e){var t=[];return l.each(e,function(e){l.each(e.extensions.split(/,/),function(e){/^\s*\*\s*$/.test(e)?t.push("\\.*"):t.push("\\."+e.replace(new RegExp("["+"/^$.*+?|()[]{}\\".replace(/./g,"\\$&")+"]","g"),"\\$&"))})}),new RegExp("("+t.join("|")+")$","i")}(i.mime_types),O.filters.mime_types=i.mime_types);break;case"resize":O.resize=i?l.extend({preserve_headers:!0,crop:!1},i):!1;break;case"prevent_duplicates":O.prevent_duplicates=O.filters.prevent_duplicates=!!i;break;case"container":case"browse_button":case"drop_element":i="container"===e?l.get(i):l.getAll(i);case"runtimes":case"multi_selection":case"flash_swf_url":case"silverlight_xap_url":O[e]=i,n||(u=!0);break;default:O[e]=i}n||a.trigger("OptionChanged",e,i,r)}var a=this,u=!1;"object"==typeof e?l.each(e,function(e,t){s(t,e,r)}):s(e,i,r),r?(O.required_features=n(l.extend({},O)),x=n(l.extend({},O,{required_features:!0}))):u&&(a.trigger("Destroy"),p.call(a,O,function(e){e?(a.runtime=o.getInfo(c()).type,a.trigger("Init",{runtime:a.runtime}),a.trigger("PostInit")):a.trigger("Error",{code:l.INIT_ERROR,message:l.translate("Init error.")})}))}function m(e,t){if(e.settings.unique_names){var i=t.name.match(/\.([^.]+)$/),n="part";i&&(n=i[1]),t.target_name=t.id+"."+n}}function _(e,i){function n(){c-->0?r(s,1e3):(i.loaded=p,e.trigger("Error",{code:l.HTTP_ERROR,message:l.translate("HTTP Error."),file:i,response:T.responseText,status:T.status,responseHeaders:T.getAllResponseHeaders()}))}function s(){var t,n,r={};i.status===l.UPLOADING&&e.state!==l.STOPPED&&(e.settings.send_file_name&&(r.name=i.target_name||i.name),d&&f.chunks&&o.size>d?(n=Math.min(d,o.size-p),t=o.slice(p,p+n)):(n=o.size,t=o),d&&f.chunks&&(e.settings.send_chunk_number?(r.chunk=Math.ceil(p/d),r.chunks=Math.ceil(o.size/d)):(r.offset=p,r.total=o.size)),e.trigger("BeforeChunkUpload",i,r,t,p)&&a(r,t,n))}function a(a,d,g){var m;T=new t.xhr.XMLHttpRequest,T.upload&&(T.upload.onprogress=function(t){i.loaded=Math.min(i.size,p+t.loaded),e.trigger("UploadProgress",i)}),T.onload=function(){return T.status<200||T.status>=400?(n(),void 0):(c=e.settings.max_retries,g<o.size?(d.destroy(),p+=g,i.loaded=Math.min(p,o.size),e.trigger("ChunkUploaded",i,{offset:i.loaded,total:o.size,response:T.responseText,status:T.status,responseHeaders:T.getAllResponseHeaders()}),"Android Browser"===l.ua.browser&&e.trigger("UploadProgress",i)):i.loaded=i.size,d=m=null,!p||p>=o.size?(i.size!=i.origSize&&(o.destroy(),o=null),e.trigger("UploadProgress",i),i.status=l.DONE,i.completeTimestamp=+new Date,e.trigger("FileUploaded",i,{response:T.responseText,status:T.status,responseHeaders:T.getAllResponseHeaders()})):r(s,1),void 0)},T.onerror=function(){n()},T.onloadend=function(){this.destroy()},e.settings.multipart&&f.multipart?(T.open(e.settings.http_method,u,!0),l.each(e.settings.headers,function(e,t){T.setRequestHeader(t,e)}),m=new t.xhr.FormData,l.each(l.extend(a,e.settings.multipart_params),function(e,t){m.append(t,e)}),m.append(e.settings.file_data_name,d),T.send(m,h)):(u=l.buildUrl(e.settings.url,l.extend(a,e.settings.multipart_params)),T.open(e.settings.http_method,u,!0),l.each(e.settings.headers,function(e,t){T.setRequestHeader(t,e)}),T.hasRequestHeader("Content-Type")||T.setRequestHeader("Content-Type","application/octet-stream"),T.send(d,h))}var o,u=e.settings.url,d=e.settings.chunk_size,c=e.settings.max_retries,f=e.features,p=0,h={runtime_order:e.settings.runtimes,required_caps:e.settings.required_features,preferred_caps:x,swf_url:e.settings.flash_swf_url,xap_url:e.settings.silverlight_xap_url};i.loaded&&(p=i.loaded=d?d*Math.floor(i.loaded/d):0),o=i.getSource(),l.isEmptyObj(e.settings.resize)||-1===l.inArray(o.type,["image/jpeg","image/png"])?s():g(o,e.settings.resize,h,function(e){o=e,i.size=e.size,s()})}function E(e,t){u(t)}function v(e){if(e.state==l.STARTED)S=+new Date;else if(e.state==l.STOPPED)for(var t=e.files.length-1;t>=0;t--)e.files[t].status==l.UPLOADING&&(e.files[t].status=l.QUEUED,d())}function b(){T&&T.abort()}function y(e){d(),r(function(){a.call(e)},1)}function R(e,t){t.code===l.INIT_ERROR?e.destroy():t.code===l.HTTP_ERROR&&(t.file.status=l.FAILED,t.file.completeTimestamp=+new Date,u(t.file),e.state==l.STARTED&&(e.trigger("CancelUpload"),r(function(){a.call(e)},1)))}function z(e){e.stop(),l.each(D,function(e){e.destroy()}),D=[],F.length&&(l.each(F,function(e){e.destroy()}),F=[]),P.length&&(l.each(P,function(e){e.destroy()}),P=[]),x={},U=!1,S=T=null,I.reset()}var O,S,I,T,w=l.guid(),D=[],x={},F=[],P=[],U=!1;O={chunk_size:0,file_data_name:"file",filters:{mime_types:[],max_file_size:0,prevent_duplicates:!1,prevent_empty:!0},flash_swf_url:"js/Moxie.swf",http_method:"POST",max_retries:0,multipart:!0,multi_selection:!0,resize:!1,runtimes:o.order,send_file_name:!0,send_chunk_number:!0,silverlight_xap_url:"js/Moxie.xap"},h.call(this,e,null,!0),I=new l.QueueProgress,l.extend(this,{id:w,uid:w,state:l.STOPPED,features:{},runtime:null,files:D,settings:O,total:I,init:function(){var e,t,i=this;return e=i.getOption("preinit"),"function"==typeof e?e(i):l.each(e,function(e,t){i.bind(t,e)}),f.call(i),l.each(["container","browse_button","drop_element"],function(e){return null===i.getOption(e)?(t={code:l.INIT_ERROR,message:l.sprintf(l.translate("%s specified, but cannot be found."),e)},!1):void 0}),t?i.trigger("Error",t):O.browse_button||O.drop_element?(p.call(i,O,function(e){var t=i.getOption("init");"function"==typeof t?t(i):l.each(t,function(e,t){i.bind(t,e)}),e?(i.runtime=o.getInfo(c()).type,i.trigger("Init",{runtime:i.runtime}),i.trigger("PostInit")):i.trigger("Error",{code:l.INIT_ERROR,message:l.translate("Init error.")})}),void 0):i.trigger("Error",{code:l.INIT_ERROR,message:l.translate("You must specify either browse_button or drop_element.")})},setOption:function(e,t){h.call(this,e,t,!this.runtime)},getOption:function(e){return e?O[e]:O},refresh:function(){F.length&&l.each(F,function(e){e.trigger("Refresh")}),this.trigger("Refresh")},start:function(){this.state!=l.STARTED&&(this.state=l.STARTED,this.trigger("StateChanged"),a.call(this))},stop:function(){this.state!=l.STOPPED&&(this.state=l.STOPPED,this.trigger("StateChanged"),this.trigger("CancelUpload"))},disableBrowse:function(){U=arguments[0]!==i?arguments[0]:!0,F.length&&l.each(F,function(e){e.disable(U)}),this.trigger("DisableBrowse",U)},getFile:function(e){var t;for(t=D.length-1;t>=0;t--)if(D[t].id===e)return D[t]},addFile:function(e,i){function n(e,t){var i=[];l.each(u.settings.filters,function(t,n){s[n]&&i.push(function(i){s[n].call(u,t,e,function(e){i(!e)})})}),l.inSeries(i,t)}function a(e){var s=l.typeOf(e);if(e instanceof t.file.File){if(!e.ruid&&!e.isDetached()){if(!o)return!1;e.ruid=o,e.connectRuntime(o)}a(new l.File(e))}else e instanceof t.file.Blob?(a(e.getSource()),e.destroy()):e instanceof l.File?(i&&(e.name=i),d.push(function(t){n(e,function(i){i||(D.push(e),f.push(e),u.trigger("FileFiltered",e)),r(t,1)})})):-1!==l.inArray(s,["file","blob"])?a(new t.file.File(null,e)):"node"===s&&"filelist"===l.typeOf(e.files)?l.each(e.files,a):"array"===s&&(i=null,l.each(e,a))}var o,u=this,d=[],f=[];o=c(),a(e),d.length&&l.inSeries(d,function(){f.length&&u.trigger("FilesAdded",f)})},removeFile:function(e){for(var t="string"==typeof e?e:e.id,i=D.length-1;i>=0;i--)if(D[i].id===t)return this.splice(i,1)[0]},splice:function(e,t){var n=D.splice(e===i?0:e,t===i?D.length:t),r=!1;return this.state==l.STARTED&&(l.each(n,function(e){return e.status===l.UPLOADING?(r=!0,!1):void 0}),r&&this.stop()),this.trigger("FilesRemoved",n),l.each(n,function(e){e.destroy()}),r&&this.start(),n},dispatchEvent:function(e){var t,i;if(e=e.toLowerCase(),t=this.hasEventListener(e)){t.sort(function(e,t){return t.priority-e.priority}),i=[].slice.call(arguments),i.shift(),i.unshift(this);for(var n=0;n<t.length;n++)if(t[n].fn.apply(t[n].scope,i)===!1)return!1}return!0},bind:function(e,t,i,n){l.Uploader.prototype.bind.call(this,e,t,n,i)},destroy:function(){this.trigger("Destroy"),O=I=null,this.unbindAll()}})},l.Uploader.prototype=t.core.EventTarget.instance,l.File=function(){function e(e){l.extend(this,{id:l.guid(),name:e.name||e.fileName,type:e.type||"",relativePath:e.relativePath||"",size:e.fileSize||e.size,origSize:e.fileSize||e.size,loaded:0,percent:0,status:l.QUEUED,lastModifiedDate:e.lastModifiedDate||(new Date).toLocaleString(),completeTimestamp:0,getNative:function(){var e=this.getSource().getSource();return-1!==l.inArray(l.typeOf(e),["blob","file"])?e:null},getSource:function(){return t[this.id]?t[this.id]:null},destroy:function(){var e=this.getSource();e&&(e.destroy(),delete t[this.id])}}),t[this.id]=e}var t={};return e}(),l.QueueProgress=function(){var e=this;e.size=0,e.loaded=0,e.uploaded=0,e.failed=0,e.queued=0,e.percent=0,e.bytesPerSec=0,e.reset=function(){e.size=e.loaded=e.uploaded=e.failed=e.queued=e.percent=e.bytesPerSec=0}},e.plupload=l}(this,e)});
/*====plupload 结束====*/

/*====公共库 结束====*/
;(function(global){
	var options = { Debug : 0 };  //默认配置参数
	
	//设置默认参数
	function setOptions(config){
		config.Debug  = config.Debug ? parseInt(config.Debug) : 0;
		options = $.extend(options, config);
	}
	
	//输出日志
	function log(){
		if(options.Debug==0) return;
		var args = arguments;
		var len = args.length;
		if(len==1){
			console.log(args[0]);
		}else if(len==2){
			console.log(args[0], args[1]);
		}else if(len==3){
			console.log(args[0], args[1], args[2]);
		}else{
			console.log(args);
		}
	}
	
	//替换所有字符串，search可以为字符串和数组(批量替换所有)
	//举例：1、replaceAll(str, 'a', '1')  
	//2、replaceAll(str, ['a', 'b'], ['1', '2']); 
	//3、replaceAll(str, ['a', 'b'], 'x');
	function replaceAll(str, search, replace){
		if(Array.isArray(search)){
			var regSearch = '';
			if(Array.isArray(replace)){
				var len = Math.min(search.length, replace.length);
				for(var i = 0; i < len; i++){
					regSearch = new RegExp(search[i],'g');
					str = str.replace(regSearch, replace[i]);
				}
			}else{
				for(var i = 0; i < search.length; i++){
					regSearch = new RegExp(search[i],'g');
					str = str.replace(regSearch, replace);
				}
			}
		}else{
			var regSearch = new RegExp(search,'g');
			str = str.replace(regSearch, replace);
		}
		return str;
	}
	
	//模板字符串替换
	//调用举例：
	//1、format("来自[0]，今年[1]岁", city, age)
	//2、format("来自[city]，今年[age]岁", obj对象);
	function format(){
		var args = arguments;
		var content = args[0]; //第一个 参数为内容
		if( content.indexOf("[0]") > -1 ){
			var regSearch = new RegExp("\\[(\\d+)\\]",'g');
			content = content.replace(regSearch, function (match, number) {
				var n = parseInt(number) + 1;
				value = typeof args[n] != 'undefined' ? args[n] : "";
				return value;
			});
		}else{
			if(!content) return content;
			var obj = args[1] || {};
			var search = '';
			for(var key in obj){
				search = '\\['+key+'\\]';
				content = replaceAll(content, search, obj[key]);
			}
		}
		return content;
	}
	
	//返回当前时间：type取值：1：年-月-日 时:分:秒，2：时:分:秒
	function getCurrentTime(type){
		var myDate = new Date(); //实例一个时间对象；
		var hour = myDate.getHours(); 
		var min = myDate.getMinutes(); 
		var sec = myDate.getSeconds(); 
		if(hour.toString().length === 1) hour = '0' + hour;
		if(min.toString().length === 1) min = '0' + min;
		if(sec.toString().length === 1) sec = '0' + sec;
		
		if(type == 2){
			dateTime = hour +":"+ min +":"+ sec;
		}else{
			var year = new Date().getFullYear();
			var month = new Date().getMonth() + 1;
			var day = new Date().getDate();
			
			if(month.toString().length === 1) month = '0' + month;
			if(day.toString().length === 1) day = '0' + day;
			dateTime = year +"-"+ month +"-"+ day + " " + hour +":"+ min +":"+ sec;
		}
		return dateTime
	}
	
	//返回当前时间戳
	function getTimeStamp(){
		var ts = (new Date()).getTime();
		return ts;
	}
	
	//生成随机字符串
	function makeRandomString(length, type){
		//字母删除了lIoO
		var lowerCase = 'abcdefghijkmnpqrstuvwxyz';
		var upperCase = 'ABCDEFGHJKMNPQRSTUVWXYZ';
		var numbers = '0123456789';
		var specialChars = '!@#$^*_|<>';
		if(!type) type = 1;  //默认为1
		var charSet = "";
		switch(type){
			case 1: //字母+数字
				charSet = lowerCase + upperCase + numbers;
				break;
			case 2: //字母+数字+特殊字符
				charSet = lowerCase + upperCase + numbers + specialChars;
				break;
			case 3: //字母
				charSet = lowerCase+upperCase
				break;
			case 4: //数字
				charSet = numbers
				break;
		}
		charSet = charSet+charSet+charSet; //重复3次
		var str = "";
		var totalLength = charSet.length;
		for (let i = 0; i < length; i++) {
			var randomIndex = Math.floor(Math.random() * totalLength);
			str += charSet[randomIndex];
		}
		return str;
	}
	
	  /**
	   * 判断异步js是否加载完成
	   */
	function loadJs(script, completecallback){
		var objScript = document.createElement("script");
		objScript.src =script;
		objScript.async='async';
		var scriptArr = script.split('/');
		id = scriptArr[scriptArr.length-1];
		objScript.id = id.replace(/\./g, '-');
		document.body.appendChild(objScript);
		if(objScript.readyState){//IE
		  objScript.onreadystatechange=function(){
			if(objScript.readyState=='complete'||objScript.readyState=='loaded'){
			  objScript.onreadystatechange=null;
			  if(completecallback) completecallback();
			}
		  }
		}else{//非IE
		  objScript.onload=function(){
			 if(completecallback) completecallback();
		  }
		}
	  }
	
	//导出函数
	global.yd = {
		setOptions:setOptions,
		log: log,
		format:format,
		getCurrentTime:getCurrentTime,
		getTimeStamp:getTimeStamp,
		loadJs:loadJs,
		replaceAll:replaceAll,
		makeRandomString:makeRandomString
	};
})(window);
/*====公共库 结束====*/


/*====color开始====*/
(function(global,factory){"use strict";if(typeof module==="object"&&typeof module.exports==="object"){module.exports=global.document?factory(global):function(win){if(!win.document){throw new Error("jscolor needs a window with document")}return factory(win)};return}factory(global)})(typeof window!=="undefined"?window:this,function(window){"use strict";var jscolor=function(){var jsc={initialized:false,instances:[],readyQueue:[],register:function(){if(typeof window!=="undefined"&&window.document){window.document.addEventListener("DOMContentLoaded",jsc.pub.init,false)}},installBySelector:function(selector,rootNode){rootNode=rootNode?jsc.node(rootNode):window.document;if(!rootNode){throw new Error("Missing root node")}var elms=rootNode.querySelectorAll(selector);var matchClass=new RegExp("(^|\\s)("+jsc.pub.lookupClass+")(\\s*(\\{[^}]*\\})|\\s|$)","i");for(var i=0;i<elms.length;i+=1){if(elms[i].jscolor&&elms[i].jscolor instanceof jsc.pub){continue}if(elms[i].type!==undefined&&elms[i].type.toLowerCase()=="color"&&jsc.isColorAttrSupported){continue}var dataOpts,m;if((dataOpts=jsc.getDataAttr(elms[i],"jscolor"))!==null||elms[i].className&&(m=elms[i].className.match(matchClass))){var targetElm=elms[i];var optsStr="";if(dataOpts!==null){optsStr=dataOpts}else if(m){console.warn('Installation using class name is DEPRECATED. Use data-jscolor="" attribute instead.'+jsc.docsRef);if(m[4]){optsStr=m[4]}}var opts=null;if(optsStr.trim()){try{opts=jsc.parseOptionsStr(optsStr)}catch(e){console.warn(e+"\n"+optsStr)}}try{new jsc.pub(targetElm,opts)}catch(e){console.warn(e)}}}},parseOptionsStr:function(str){var opts=null;try{opts=JSON.parse(str)}catch(eParse){if(!jsc.pub.looseJSON){throw new Error("Could not parse jscolor options as JSON: "+eParse)}else{try{opts=new Function("var opts = ("+str+'); return typeof opts === "object" ? opts : {};')()}catch(eEval){throw new Error("Could not evaluate jscolor options: "+eEval)}}}return opts},getInstances:function(){var inst=[];for(var i=0;i<jsc.instances.length;i+=1){if(jsc.instances[i]&&jsc.instances[i].targetElement){inst.push(jsc.instances[i])}}return inst},createEl:function(tagName){var el=window.document.createElement(tagName);jsc.setData(el,"gui",true);return el},node:function(nodeOrSelector){if(!nodeOrSelector){return null}if(typeof nodeOrSelector==="string"){var sel=nodeOrSelector;var el=null;try{el=window.document.querySelector(sel)}catch(e){console.warn(e);return null}if(!el){console.warn("No element matches the selector: %s",sel)}return el}if(jsc.isNode(nodeOrSelector)){return nodeOrSelector}console.warn("Invalid node of type %s: %s",typeof nodeOrSelector,nodeOrSelector);return null},isNode:function(val){if(typeof Node==="object"){return val instanceof Node}return val&&typeof val==="object"&&typeof val.nodeType==="number"&&typeof val.nodeName==="string"},nodeName:function(node){if(node&&node.nodeName){return node.nodeName.toLowerCase()}return false},removeChildren:function(node){while(node.firstChild){node.removeChild(node.firstChild)}},isTextInput:function(el){return el&&jsc.nodeName(el)==="input"&&el.type.toLowerCase()==="text"},isButton:function(el){if(!el){return false}var n=jsc.nodeName(el);return n==="button"||n==="input"&&["button","submit","reset"].indexOf(el.type.toLowerCase())>-1},isButtonEmpty:function(el){switch(jsc.nodeName(el)){case"input":return!el.value||el.value.trim()==="";case"button":return el.textContent.trim()===""}return null},isPassiveEventSupported:function(){var supported=false;try{var opts=Object.defineProperty({},"passive",{get:function(){supported=true}});window.addEventListener("testPassive",null,opts);window.removeEventListener("testPassive",null,opts)}catch(e){}return supported}(),isColorAttrSupported:function(){var elm=window.document.createElement("input");if(elm.setAttribute){elm.setAttribute("type","color");if(elm.type.toLowerCase()=="color"){return true}}return false}(),dataProp:"_data_jscolor",setData:function(){var obj=arguments[0];if(arguments.length===3){var data=obj.hasOwnProperty(jsc.dataProp)?obj[jsc.dataProp]:obj[jsc.dataProp]={};var prop=arguments[1];var value=arguments[2];data[prop]=value;return true}else if(arguments.length===2&&typeof arguments[1]==="object"){var data=obj.hasOwnProperty(jsc.dataProp)?obj[jsc.dataProp]:obj[jsc.dataProp]={};var map=arguments[1];for(var prop in map){if(map.hasOwnProperty(prop)){data[prop]=map[prop]}}return true}throw new Error("Invalid arguments")},removeData:function(){var obj=arguments[0];if(!obj.hasOwnProperty(jsc.dataProp)){return true}for(var i=1;i<arguments.length;i+=1){var prop=arguments[i];delete obj[jsc.dataProp][prop]}return true},getData:function(obj,prop,setDefault){if(!obj.hasOwnProperty(jsc.dataProp)){if(setDefault!==undefined){obj[jsc.dataProp]={}}else{return undefined}}var data=obj[jsc.dataProp];if(!data.hasOwnProperty(prop)&&setDefault!==undefined){data[prop]=setDefault}return data[prop]},getDataAttr:function(el,name){var attrName="data-"+name;var attrValue=el.getAttribute(attrName);return attrValue},setDataAttr:function(el,name,value){var attrName="data-"+name;el.setAttribute(attrName,value)},_attachedGroupEvents:{},attachGroupEvent:function(groupName,el,evnt,func){if(!jsc._attachedGroupEvents.hasOwnProperty(groupName)){jsc._attachedGroupEvents[groupName]=[]}jsc._attachedGroupEvents[groupName].push([el,evnt,func]);el.addEventListener(evnt,func,false)},detachGroupEvents:function(groupName){if(jsc._attachedGroupEvents.hasOwnProperty(groupName)){for(var i=0;i<jsc._attachedGroupEvents[groupName].length;i+=1){var evt=jsc._attachedGroupEvents[groupName][i];evt[0].removeEventListener(evt[1],evt[2],false)}delete jsc._attachedGroupEvents[groupName]}},preventDefault:function(e){if(e.preventDefault){e.preventDefault()}e.returnValue=false},captureTarget:function(target){if(target.setCapture){jsc._capturedTarget=target;jsc._capturedTarget.setCapture()}},releaseTarget:function(){if(jsc._capturedTarget){jsc._capturedTarget.releaseCapture();jsc._capturedTarget=null}},triggerEvent:function(el,eventName,bubbles,cancelable){if(!el){return}var ev=null;if(typeof Event==="function"){ev=new Event(eventName,{bubbles:bubbles,cancelable:cancelable})}else{ev=window.document.createEvent("Event");ev.initEvent(eventName,bubbles,cancelable)}if(!ev){return false}jsc.setData(ev,"internal",true);el.dispatchEvent(ev);return true},triggerInputEvent:function(el,eventName,bubbles,cancelable){if(!el){return}if(jsc.isTextInput(el)){jsc.triggerEvent(el,eventName,bubbles,cancelable)}},eventKey:function(ev){var keys={9:"Tab",13:"Enter",27:"Escape"};if(typeof ev.code==="string"){return ev.code}else if(ev.keyCode!==undefined&&keys.hasOwnProperty(ev.keyCode)){return keys[ev.keyCode]}return null},strList:function(str){if(!str){return[]}return str.replace(/^\s+|\s+$/g,"").split(/\s+/)},hasClass:function(elm,className){if(!className){return false}if(elm.classList!==undefined){return elm.classList.contains(className)}return-1!=(" "+elm.className.replace(/\s+/g," ")+" ").indexOf(" "+className+" ")},addClass:function(elm,className){var classNames=jsc.strList(className);if(elm.classList!==undefined){for(var i=0;i<classNames.length;i+=1){elm.classList.add(classNames[i])}return}for(var i=0;i<classNames.length;i+=1){if(!jsc.hasClass(elm,classNames[i])){elm.className+=(elm.className?" ":"")+classNames[i]}}},removeClass:function(elm,className){var classNames=jsc.strList(className);if(elm.classList!==undefined){for(var i=0;i<classNames.length;i+=1){elm.classList.remove(classNames[i])}return}for(var i=0;i<classNames.length;i+=1){var repl=new RegExp("^\\s*"+classNames[i]+"\\s*|"+"\\s*"+classNames[i]+"\\s*$|"+"\\s+"+classNames[i]+"(\\s+)","g");elm.className=elm.className.replace(repl,"$1")}},getCompStyle:function(elm){var compStyle=window.getComputedStyle?window.getComputedStyle(elm):elm.currentStyle;if(!compStyle){return{}}return compStyle},setStyle:function(elm,styles,important,reversible){var priority=important?"important":"";var origStyle=null;for(var prop in styles){if(styles.hasOwnProperty(prop)){var setVal=null;if(styles[prop]===null){if(!origStyle){origStyle=jsc.getData(elm,"origStyle")}if(origStyle&&origStyle.hasOwnProperty(prop)){setVal=origStyle[prop]}}else{if(reversible){if(!origStyle){origStyle=jsc.getData(elm,"origStyle",{})}if(!origStyle.hasOwnProperty(prop)){origStyle[prop]=elm.style[prop]}}setVal=styles[prop]}if(setVal!==null){elm.style.setProperty(prop,setVal,priority)}}}},hexColor:function(r,g,b){return"#"+(("0"+Math.round(r).toString(16)).substr(-2)+("0"+Math.round(g).toString(16)).substr(-2)+("0"+Math.round(b).toString(16)).substr(-2)).toUpperCase()},hexaColor:function(r,g,b,a){return"#"+(("0"+Math.round(r).toString(16)).substr(-2)+("0"+Math.round(g).toString(16)).substr(-2)+("0"+Math.round(b).toString(16)).substr(-2)+("0"+Math.round(a*255).toString(16)).substr(-2)).toUpperCase()},rgbColor:function(r,g,b){return"rgb("+Math.round(r)+","+Math.round(g)+","+Math.round(b)+")"},rgbaColor:function(r,g,b,a){return"rgba("+Math.round(r)+","+Math.round(g)+","+Math.round(b)+","+Math.round((a===undefined||a===null?1:a)*100)/100+")"},linearGradient:function(){function getFuncName(){var stdName="linear-gradient";var prefixes=["","-webkit-","-moz-","-o-","-ms-"];var helper=window.document.createElement("div");for(var i=0;i<prefixes.length;i+=1){var tryFunc=prefixes[i]+stdName;var tryVal=tryFunc+"(to right, rgba(0,0,0,0), rgba(0,0,0,0))";helper.style.background=tryVal;if(helper.style.background){return tryFunc}}return stdName}var funcName=getFuncName();return function(){return funcName+"("+Array.prototype.join.call(arguments,", ")+")"}}(),setBorderRadius:function(elm,value){jsc.setStyle(elm,{"border-radius":value||"0"})},setBoxShadow:function(elm,value){jsc.setStyle(elm,{"box-shadow":value||"none"})},getElementPos:function(e,relativeToViewport){var x=0,y=0;var rect=e.getBoundingClientRect();x=rect.left;y=rect.top;if(!relativeToViewport){var viewPos=jsc.getViewPos();x+=viewPos[0];y+=viewPos[1]}return[x,y]},getElementSize:function(e){return[e.offsetWidth,e.offsetHeight]},getAbsPointerPos:function(e){var x=0,y=0;if(typeof e.changedTouches!=="undefined"&&e.changedTouches.length){x=e.changedTouches[0].clientX;y=e.changedTouches[0].clientY}else if(typeof e.clientX==="number"){x=e.clientX;y=e.clientY}return{x:x,y:y}},getRelPointerPos:function(e){var target=e.target||e.srcElement;var targetRect=target.getBoundingClientRect();var x=0,y=0;var clientX=0,clientY=0;if(typeof e.changedTouches!=="undefined"&&e.changedTouches.length){clientX=e.changedTouches[0].clientX;clientY=e.changedTouches[0].clientY}else if(typeof e.clientX==="number"){clientX=e.clientX;clientY=e.clientY}x=clientX-targetRect.left;y=clientY-targetRect.top;return{x:x,y:y}},getViewPos:function(){var doc=window.document.documentElement;return[(window.pageXOffset||doc.scrollLeft)-(doc.clientLeft||0),(window.pageYOffset||doc.scrollTop)-(doc.clientTop||0)]},getViewSize:function(){var doc=window.document.documentElement;return[window.innerWidth||doc.clientWidth,window.innerHeight||doc.clientHeight]},RGB_HSV:function(r,g,b){r/=255;g/=255;b/=255;var n=Math.min(Math.min(r,g),b);var v=Math.max(Math.max(r,g),b);var m=v-n;if(m===0){return[null,0,100*v]}var h=r===n?3+(b-g)/m:g===n?5+(r-b)/m:1+(g-r)/m;return[60*(h===6?0:h),100*(m/v),100*v]},HSV_RGB:function(h,s,v){var u=255*(v/100);if(h===null){return[u,u,u]}h/=60;s/=100;var i=Math.floor(h);var f=i%2?h-i:1-(h-i);var m=u*(1-s);var n=u*(1-s*f);switch(i){case 6:case 0:return[u,n,m];case 1:return[n,u,m];case 2:return[m,u,n];case 3:return[m,n,u];case 4:return[n,m,u];case 5:return[u,m,n]}},parseColorString:function(str){var ret={rgba:null,format:null};var m;if(m=str.match(/^\W*([0-9A-F]{3,8})\W*$/i)){if(m[1].length===8){ret.format="hexa";ret.rgba=[parseInt(m[1].substr(0,2),16),parseInt(m[1].substr(2,2),16),parseInt(m[1].substr(4,2),16),parseInt(m[1].substr(6,2),16)/255]}else if(m[1].length===6){ret.format="hex";ret.rgba=[parseInt(m[1].substr(0,2),16),parseInt(m[1].substr(2,2),16),parseInt(m[1].substr(4,2),16),null]}else if(m[1].length===3){ret.format="hex";ret.rgba=[parseInt(m[1].charAt(0)+m[1].charAt(0),16),parseInt(m[1].charAt(1)+m[1].charAt(1),16),parseInt(m[1].charAt(2)+m[1].charAt(2),16),null]}else{return false}return ret}if(m=str.match(/^\W*rgba?\(([^)]*)\)\W*$/i)){var par=m[1].split(",");var re=/^\s*(\d+|\d*\.\d+|\d+\.\d*)\s*$/;var mR,mG,mB,mA;if(par.length>=3&&(mR=par[0].match(re))&&(mG=par[1].match(re))&&(mB=par[2].match(re))){ret.format="rgb";ret.rgba=[parseFloat(mR[1])||0,parseFloat(mG[1])||0,parseFloat(mB[1])||0,null];if(par.length>=4&&(mA=par[3].match(re))){ret.format="rgba";ret.rgba[3]=parseFloat(mA[1])||0}return ret}}return false},parsePaletteValue:function(mixed){var vals=[];if(typeof mixed==="string"){mixed.replace(/#[0-9A-F]{3}([0-9A-F]{3})?|rgba?\(([^)]*)\)/gi,function(val){vals.push(val)})}else if(Array.isArray(mixed)){vals=mixed}var colors=[];for(var i=0;i<vals.length;i++){var color=jsc.parseColorString(vals[i]);if(color){colors.push(color)}}return colors},containsTranparentColor:function(colors){for(var i=0;i<colors.length;i++){var a=colors[i].rgba[3];if(a!==null&&a<1){return true}}return false},isAlphaFormat:function(format){switch(format.toLowerCase()){case"hexa":case"rgba":return true}return false},scaleCanvasForHighDPR:function(canvas){var dpr=window.devicePixelRatio||1;canvas.width*=dpr;canvas.height*=dpr;var ctx=canvas.getContext("2d");ctx.scale(dpr,dpr)},genColorPreviewCanvas:function(color,separatorPos,specWidth,scaleForHighDPR){var sepW=Math.round(jsc.pub.previewSeparator.length);var sqSize=jsc.pub.chessboardSize;var sqColor1=jsc.pub.chessboardColor1;var sqColor2=jsc.pub.chessboardColor2;var cWidth=specWidth?specWidth:sqSize*2;var cHeight=sqSize*2;var canvas=jsc.createEl("canvas");var ctx=canvas.getContext("2d");canvas.width=cWidth;canvas.height=cHeight;if(scaleForHighDPR){jsc.scaleCanvasForHighDPR(canvas)}ctx.fillStyle=sqColor1;ctx.fillRect(0,0,cWidth,cHeight);ctx.fillStyle=sqColor2;for(var x=0;x<cWidth;x+=sqSize*2){ctx.fillRect(x,0,sqSize,sqSize);ctx.fillRect(x+sqSize,sqSize,sqSize,sqSize)}if(color){ctx.fillStyle=color;ctx.fillRect(0,0,cWidth,cHeight)}var start=null;switch(separatorPos){case"left":start=0;ctx.clearRect(0,0,sepW/2,cHeight);break;case"right":start=cWidth-sepW;ctx.clearRect(cWidth-sepW/2,0,sepW/2,cHeight);break}if(start!==null){ctx.lineWidth=1;for(var i=0;i<jsc.pub.previewSeparator.length;i+=1){ctx.beginPath();ctx.strokeStyle=jsc.pub.previewSeparator[i];ctx.moveTo(.5+start+i,0);ctx.lineTo(.5+start+i,cHeight);ctx.stroke()}}return{canvas:canvas,width:cWidth,height:cHeight}},genColorPreviewGradient:function(color,position,width){var params=[];if(position&&width){params=["to "+{left:"right",right:"left"}[position],color+" 0%",color+" "+width+"px","rgba(0,0,0,0) "+(width+1)+"px","rgba(0,0,0,0) 100%"]}else{params=["to right",color+" 0%",color+" 100%"]}return jsc.linearGradient.apply(this,params)},redrawPosition:function(){if(!jsc.picker||!jsc.picker.owner){return}var thisObj=jsc.picker.owner;var tp,vp;if(thisObj.fixed){tp=jsc.getElementPos(thisObj.targetElement,true);vp=[0,0]}else{tp=jsc.getElementPos(thisObj.targetElement);vp=jsc.getViewPos()}var ts=jsc.getElementSize(thisObj.targetElement);var vs=jsc.getViewSize();var pd=jsc.getPickerDims(thisObj);var ps=[pd.borderW,pd.borderH];var a,b,c;switch(thisObj.position.toLowerCase()){case"left":a=1;b=0;c=-1;break;case"right":a=1;b=0;c=1;break;case"top":a=0;b=1;c=-1;break;default:a=0;b=1;c=1;break}var l=(ts[b]+ps[b])/2;if(!thisObj.smartPosition){var pp=[tp[a],tp[b]+ts[b]-l+l*c]}else{var pp=[-vp[a]+tp[a]+ps[a]>vs[a]?-vp[a]+tp[a]+ts[a]/2>vs[a]/2&&tp[a]+ts[a]-ps[a]>=0?tp[a]+ts[a]-ps[a]:tp[a]:tp[a],-vp[b]+tp[b]+ts[b]+ps[b]-l+l*c>vs[b]?-vp[b]+tp[b]+ts[b]/2>vs[b]/2&&tp[b]+ts[b]-l-l*c>=0?tp[b]+ts[b]-l-l*c:tp[b]+ts[b]-l+l*c:tp[b]+ts[b]-l+l*c>=0?tp[b]+ts[b]-l+l*c:tp[b]+ts[b]-l-l*c]}var x=pp[a];var y=pp[b];var positionValue=thisObj.fixed?"fixed":"absolute";var contractShadow=(pp[0]+ps[0]>tp[0]||pp[0]<tp[0]+ts[0])&&pp[1]+ps[1]<tp[1]+ts[1];jsc._drawPosition(thisObj,x,y,positionValue,contractShadow)},_drawPosition:function(thisObj,x,y,positionValue,contractShadow){var vShadow=contractShadow?0:thisObj.shadowBlur;jsc.picker.wrap.style.position=positionValue;jsc.picker.wrap.style.left=x+"px";jsc.picker.wrap.style.top=y+"px";jsc.setBoxShadow(jsc.picker.boxS,thisObj.shadow?new jsc.BoxShadow(0,vShadow,thisObj.shadowBlur,0,thisObj.shadowColor):null)},getPickerDims:function(thisObj){var w=2*thisObj.controlBorderWidth+thisObj.width;var h=2*thisObj.controlBorderWidth+thisObj.height;var sliderSpace=2*thisObj.controlBorderWidth+2*jsc.getControlPadding(thisObj)+thisObj.sliderSize;if(jsc.getSliderChannel(thisObj)){w+=sliderSpace}if(thisObj.hasAlphaChannel()){w+=sliderSpace}var pal=jsc.getPaletteDims(thisObj,w);if(pal.height){h+=pal.height+thisObj.padding}if(thisObj.closeButton){h+=2*thisObj.controlBorderWidth+thisObj.padding+thisObj.buttonHeight}var pW=w+2*thisObj.padding;var pH=h+2*thisObj.padding;return{contentW:w,contentH:h,paddedW:pW,paddedH:pH,borderW:pW+2*thisObj.borderWidth,borderH:pH+2*thisObj.borderWidth,palette:pal}},getPaletteDims:function(thisObj,width){var cols=0,rows=0,cellW=0,cellH=0,height=0;var sampleCount=thisObj._palette?thisObj._palette.length:0;if(sampleCount){cols=thisObj.paletteCols;rows=cols>0?Math.ceil(sampleCount/cols):0;cellW=Math.max(1,Math.floor((width-(cols-1)*thisObj.paletteSpacing)/cols));cellH=thisObj.paletteHeight?Math.min(thisObj.paletteHeight,cellW):cellW}if(rows){height=rows*cellH+(rows-1)*thisObj.paletteSpacing}return{cols:cols,rows:rows,cellW:cellW,cellH:cellH,width:width,height:height}},getControlPadding:function(thisObj){return Math.max(thisObj.padding/2,2*thisObj.pointerBorderWidth+thisObj.pointerThickness-thisObj.controlBorderWidth)},getPadYChannel:function(thisObj){switch(thisObj.mode.charAt(1).toLowerCase()){case"v":return"v";break}return"s"},getSliderChannel:function(thisObj){if(thisObj.mode.length>2){switch(thisObj.mode.charAt(2).toLowerCase()){case"s":return"s";break;case"v":return"v";break}}return null},triggerCallback:function(thisObj,prop){if(!thisObj[prop]){return}var callback=null;if(typeof thisObj[prop]==="string"){try{callback=new Function(thisObj[prop])}catch(e){console.error(e)}}else{callback=thisObj[prop]}if(callback){callback.call(thisObj)}},triggerGlobal:function(eventNames){var inst=jsc.getInstances();for(var i=0;i<inst.length;i+=1){inst[i].trigger(eventNames)}},_pointerMoveEvent:{mouse:"mousemove",touch:"touchmove"},_pointerEndEvent:{mouse:"mouseup",touch:"touchend"},_pointerOrigin:null,_capturedTarget:null,onDocumentKeyUp:function(e){if(["Tab","Escape"].indexOf(jsc.eventKey(e))!==-1){if(jsc.picker&&jsc.picker.owner){jsc.picker.owner.tryHide()}}},onWindowResize:function(e){jsc.redrawPosition()},onWindowScroll:function(e){jsc.redrawPosition()},onParentScroll:function(e){if(jsc.picker&&jsc.picker.owner){jsc.picker.owner.tryHide()}},onDocumentMouseDown:function(e){var target=e.target||e.srcElement;if(target.jscolor&&target.jscolor instanceof jsc.pub){if(target.jscolor.showOnClick&&!target.disabled){target.jscolor.show()}}else if(jsc.getData(target,"gui")){var control=jsc.getData(target,"control");if(control){jsc.onControlPointerStart(e,target,jsc.getData(target,"control"),"mouse")}}else{if(jsc.picker&&jsc.picker.owner){jsc.picker.owner.tryHide()}}},onPickerTouchStart:function(e){var target=e.target||e.srcElement;if(jsc.getData(target,"control")){jsc.onControlPointerStart(e,target,jsc.getData(target,"control"),"touch")}},onControlPointerStart:function(e,target,controlName,pointerType){var thisObj=jsc.getData(target,"instance");jsc.preventDefault(e);jsc.captureTarget(target);var registerDragEvents=function(doc,offset){jsc.attachGroupEvent("drag",doc,jsc._pointerMoveEvent[pointerType],jsc.onDocumentPointerMove(e,target,controlName,pointerType,offset));jsc.attachGroupEvent("drag",doc,jsc._pointerEndEvent[pointerType],jsc.onDocumentPointerEnd(e,target,controlName,pointerType))};registerDragEvents(window.document,[0,0]);if(window.parent&&window.frameElement){var rect=window.frameElement.getBoundingClientRect();var ofs=[-rect.left,-rect.top];registerDragEvents(window.parent.window.document,ofs)}var abs=jsc.getAbsPointerPos(e);var rel=jsc.getRelPointerPos(e);jsc._pointerOrigin={x:abs.x-rel.x,y:abs.y-rel.y};switch(controlName){case"pad":if(jsc.getSliderChannel(thisObj)==="v"&&thisObj.channels.v===0){thisObj.fromHSVA(null,null,100,null)}jsc.setPad(thisObj,e,0,0);break;case"sld":jsc.setSld(thisObj,e,0);break;case"asld":jsc.setASld(thisObj,e,0);break}thisObj.trigger("input")},onDocumentPointerMove:function(e,target,controlName,pointerType,offset){return function(e){var thisObj=jsc.getData(target,"instance");switch(controlName){case"pad":jsc.setPad(thisObj,e,offset[0],offset[1]);break;case"sld":jsc.setSld(thisObj,e,offset[1]);break;case"asld":jsc.setASld(thisObj,e,offset[1]);break}thisObj.trigger("input")}},onDocumentPointerEnd:function(e,target,controlName,pointerType){return function(e){var thisObj=jsc.getData(target,"instance");jsc.detachGroupEvents("drag");jsc.releaseTarget();thisObj.trigger("input");thisObj.trigger("change")}},onPaletteSampleClick:function(e){var target=e.currentTarget;var thisObj=jsc.getData(target,"instance");var color=jsc.getData(target,"color");if(thisObj.format.toLowerCase()==="any"){thisObj._setFormat(color.format);if(!jsc.isAlphaFormat(thisObj.getFormat())){color.rgba[3]=1}}if(color.rgba[3]===null){if(thisObj.paletteSetsAlpha===true||thisObj.paletteSetsAlpha==="auto"&&thisObj._paletteHasTransparency){color.rgba[3]=1}}thisObj.fromRGBA.apply(thisObj,color.rgba);thisObj.trigger("input");thisObj.trigger("change");if(thisObj.hideOnPaletteClick){thisObj.hide()}},setPad:function(thisObj,e,ofsX,ofsY){var pointerAbs=jsc.getAbsPointerPos(e);var x=ofsX+pointerAbs.x-jsc._pointerOrigin.x-thisObj.padding-thisObj.controlBorderWidth;var y=ofsY+pointerAbs.y-jsc._pointerOrigin.y-thisObj.padding-thisObj.controlBorderWidth;var xVal=x*(360/(thisObj.width-1));var yVal=100-y*(100/(thisObj.height-1));switch(jsc.getPadYChannel(thisObj)){case"s":thisObj.fromHSVA(xVal,yVal,null,null);break;case"v":thisObj.fromHSVA(xVal,null,yVal,null);break}},setSld:function(thisObj,e,ofsY){var pointerAbs=jsc.getAbsPointerPos(e);var y=ofsY+pointerAbs.y-jsc._pointerOrigin.y-thisObj.padding-thisObj.controlBorderWidth;var yVal=100-y*(100/(thisObj.height-1));switch(jsc.getSliderChannel(thisObj)){case"s":thisObj.fromHSVA(null,yVal,null,null);break;case"v":thisObj.fromHSVA(null,null,yVal,null);break}},setASld:function(thisObj,e,ofsY){var pointerAbs=jsc.getAbsPointerPos(e);var y=ofsY+pointerAbs.y-jsc._pointerOrigin.y-thisObj.padding-thisObj.controlBorderWidth;var yVal=1-y*(1/(thisObj.height-1));if(yVal<1){var fmt=thisObj.getFormat();if(thisObj.format.toLowerCase()==="any"&&!jsc.isAlphaFormat(fmt)){thisObj._setFormat(fmt==="hex"?"hexa":"rgba")}}thisObj.fromHSVA(null,null,null,yVal)},createPadCanvas:function(){var ret={elm:null,draw:null};var canvas=jsc.createEl("canvas");var ctx=canvas.getContext("2d");var drawFunc=function(width,height,type){canvas.width=width;canvas.height=height;ctx.clearRect(0,0,canvas.width,canvas.height);var hGrad=ctx.createLinearGradient(0,0,canvas.width,0);hGrad.addColorStop(0/6,"#F00");hGrad.addColorStop(1/6,"#FF0");hGrad.addColorStop(2/6,"#0F0");hGrad.addColorStop(3/6,"#0FF");hGrad.addColorStop(4/6,"#00F");hGrad.addColorStop(5/6,"#F0F");hGrad.addColorStop(6/6,"#F00");ctx.fillStyle=hGrad;ctx.fillRect(0,0,canvas.width,canvas.height);var vGrad=ctx.createLinearGradient(0,0,0,canvas.height);switch(type.toLowerCase()){case"s":vGrad.addColorStop(0,"rgba(255,255,255,0)");vGrad.addColorStop(1,"rgba(255,255,255,1)");break;case"v":vGrad.addColorStop(0,"rgba(0,0,0,0)");vGrad.addColorStop(1,"rgba(0,0,0,1)");break}ctx.fillStyle=vGrad;ctx.fillRect(0,0,canvas.width,canvas.height)};ret.elm=canvas;ret.draw=drawFunc;return ret},createSliderGradient:function(){var ret={elm:null,draw:null};var canvas=jsc.createEl("canvas");var ctx=canvas.getContext("2d");var drawFunc=function(width,height,color1,color2){canvas.width=width;canvas.height=height;ctx.clearRect(0,0,canvas.width,canvas.height);var grad=ctx.createLinearGradient(0,0,0,canvas.height);grad.addColorStop(0,color1);grad.addColorStop(1,color2);ctx.fillStyle=grad;ctx.fillRect(0,0,canvas.width,canvas.height)};ret.elm=canvas;ret.draw=drawFunc;return ret},createASliderGradient:function(){var ret={elm:null,draw:null};var canvas=jsc.createEl("canvas");var ctx=canvas.getContext("2d");var drawFunc=function(width,height,color){canvas.width=width;canvas.height=height;ctx.clearRect(0,0,canvas.width,canvas.height);var sqSize=canvas.width/2;var sqColor1=jsc.pub.chessboardColor1;var sqColor2=jsc.pub.chessboardColor2;ctx.fillStyle=sqColor1;ctx.fillRect(0,0,canvas.width,canvas.height);if(sqSize>0){for(var y=0;y<canvas.height;y+=sqSize*2){ctx.fillStyle=sqColor2;ctx.fillRect(0,y,sqSize,sqSize);ctx.fillRect(sqSize,y+sqSize,sqSize,sqSize)}}var grad=ctx.createLinearGradient(0,0,0,canvas.height);grad.addColorStop(0,color);grad.addColorStop(1,"rgba(0,0,0,0)");ctx.fillStyle=grad;ctx.fillRect(0,0,canvas.width,canvas.height)};ret.elm=canvas;ret.draw=drawFunc;return ret},BoxShadow:function(){var BoxShadow=function(hShadow,vShadow,blur,spread,color,inset){this.hShadow=hShadow;this.vShadow=vShadow;this.blur=blur;this.spread=spread;this.color=color;this.inset=!!inset};BoxShadow.prototype.toString=function(){var vals=[Math.round(this.hShadow)+"px",Math.round(this.vShadow)+"px",Math.round(this.blur)+"px",Math.round(this.spread)+"px",this.color];if(this.inset){vals.push("inset")}return vals.join(" ")};return BoxShadow}(),flags:{leaveValue:1<<0,leaveAlpha:1<<1,leavePreview:1<<2},enumOpts:{format:["auto","any","hex","hexa","rgb","rgba"],previewPosition:["left","right"],mode:["hsv","hvs","hs","hv"],position:["left","right","top","bottom"],alphaChannel:["auto",true,false],paletteSetsAlpha:["auto",true,false]},deprecatedOpts:{styleElement:"previewElement",onFineChange:"onInput",overwriteImportant:"forceStyle",closable:"closeButton",insetWidth:"controlBorderWidth",insetColor:"controlBorderColor",refine:null},docsRef:" "+"See https://jscolor.com/docs/",pub:function(targetElement,opts){var THIS=this;if(!opts){opts={}}this.channels={r:255,g:255,b:255,h:0,s:0,v:100,a:1};this.format="auto";this.value=undefined;this.alpha=undefined;this.onChange=undefined;this.onInput=undefined;this.valueElement=undefined;this.alphaElement=undefined;this.previewElement=undefined;this.previewPosition="left";this.previewSize=32;this.previewPadding=8;this.required=true;this.hash=true;this.uppercase=true;this.forceStyle=true;this.width=181;this.height=101;this.mode="HSV";this.alphaChannel="auto";this.position="bottom";this.smartPosition=true;this.showOnClick=true;this.hideOnLeave=true;this.palette=[];this.paletteCols=10;this.paletteSetsAlpha="auto";this.paletteHeight=16;this.paletteSpacing=4;this.hideOnPaletteClick=false;this.sliderSize=16;this.crossSize=8;this.closeButton=false;this.closeText="Close";this.buttonColor="rgba(0,0,0,1)";this.buttonHeight=18;this.padding=12;this.backgroundColor="rgba(255,255,255,1)";this.borderWidth=1;this.borderColor="rgba(187,187,187,1)";this.borderRadius=8;this.controlBorderWidth=1;this.controlBorderColor="rgba(187,187,187,1)";this.shadow=true;this.shadowBlur=15;this.shadowColor="rgba(0,0,0,0.2)";this.pointerColor="rgba(76,76,76,1)";this.pointerBorderWidth=1;this.pointerBorderColor="rgba(255,255,255,1)";this.pointerThickness=2;this.zIndex=5e3;this.container=undefined;this.minS=0;this.maxS=100;this.minV=0;this.maxV=100;this.minA=0;this.maxA=1;this.option=function(){if(!arguments.length){throw new Error("No option specified")}if(arguments.length===1&&typeof arguments[0]==="string"){try{return getOption(arguments[0])}catch(e){console.warn(e)}return false}else if(arguments.length>=2&&typeof arguments[0]==="string"){try{if(!setOption(arguments[0],arguments[1])){return false}}catch(e){console.warn(e);return false}this.redraw();this.exposeColor();return true}else if(arguments.length===1&&typeof arguments[0]==="object"){var opts=arguments[0];var success=true;for(var opt in opts){if(opts.hasOwnProperty(opt)){try{if(!setOption(opt,opts[opt])){success=false}}catch(e){console.warn(e);success=false}}}this.redraw();this.exposeColor();return success}throw new Error("Invalid arguments")};this.channel=function(name,value){if(typeof name!=="string"){throw new Error("Invalid value for channel name: "+name)}if(value===undefined){if(!this.channels.hasOwnProperty(name.toLowerCase())){console.warn("Getting unknown channel: "+name);return false}return this.channels[name.toLowerCase()]}else{var res=false;switch(name.toLowerCase()){case"r":res=this.fromRGBA(value,null,null,null);break;case"g":res=this.fromRGBA(null,value,null,null);break;case"b":res=this.fromRGBA(null,null,value,null);break;case"h":res=this.fromHSVA(value,null,null,null);break;case"s":res=this.fromHSVA(null,value,null,null);break;case"v":res=this.fromHSVA(null,null,value,null);break;case"a":res=this.fromHSVA(null,null,null,value);break;default:console.warn("Setting unknown channel: "+name);return false}if(res){this.redraw();return true}}return false};this.trigger=function(eventNames){var evs=jsc.strList(eventNames);for(var i=0;i<evs.length;i+=1){var ev=evs[i].toLowerCase();var callbackProp=null;switch(ev){case"input":callbackProp="onInput";break;case"change":callbackProp="onChange";break}if(callbackProp){jsc.triggerCallback(this,callbackProp)}jsc.triggerInputEvent(this.valueElement,ev,true,true)}};this.fromHSVA=function(h,s,v,a,flags){if(h===undefined){h=null}if(s===undefined){s=null}if(v===undefined){v=null}if(a===undefined){a=null}if(h!==null){if(isNaN(h)){return false}this.channels.h=Math.max(0,Math.min(360,h))}if(s!==null){if(isNaN(s)){return false}this.channels.s=Math.max(0,Math.min(100,this.maxS,s),this.minS)}if(v!==null){if(isNaN(v)){return false}this.channels.v=Math.max(0,Math.min(100,this.maxV,v),this.minV)}if(a!==null){if(isNaN(a)){return false}this.channels.a=this.hasAlphaChannel()?Math.max(0,Math.min(1,this.maxA,a),this.minA):1}var rgb=jsc.HSV_RGB(this.channels.h,this.channels.s,this.channels.v);this.channels.r=rgb[0];this.channels.g=rgb[1];this.channels.b=rgb[2];this.exposeColor(flags);return true};this.fromRGBA=function(r,g,b,a,flags){if(r===undefined){r=null}if(g===undefined){g=null}if(b===undefined){b=null}if(a===undefined){a=null}if(r!==null){if(isNaN(r)){return false}r=Math.max(0,Math.min(255,r))}if(g!==null){if(isNaN(g)){return false}g=Math.max(0,Math.min(255,g))}if(b!==null){if(isNaN(b)){return false}b=Math.max(0,Math.min(255,b))}if(a!==null){if(isNaN(a)){return false}this.channels.a=this.hasAlphaChannel()?Math.max(0,Math.min(1,this.maxA,a),this.minA):1}var hsv=jsc.RGB_HSV(r===null?this.channels.r:r,g===null?this.channels.g:g,b===null?this.channels.b:b);if(hsv[0]!==null){this.channels.h=Math.max(0,Math.min(360,hsv[0]))}if(hsv[2]!==0){this.channels.s=Math.max(0,this.minS,Math.min(100,this.maxS,hsv[1]))}this.channels.v=Math.max(0,this.minV,Math.min(100,this.maxV,hsv[2]));var rgb=jsc.HSV_RGB(this.channels.h,this.channels.s,this.channels.v);this.channels.r=rgb[0];this.channels.g=rgb[1];this.channels.b=rgb[2];this.exposeColor(flags);return true};this.fromHSV=function(h,s,v,flags){console.warn("fromHSV() method is DEPRECATED. Using fromHSVA() instead."+jsc.docsRef);return this.fromHSVA(h,s,v,null,flags)};this.fromRGB=function(r,g,b,flags){console.warn("fromRGB() method is DEPRECATED. Using fromRGBA() instead."+jsc.docsRef);return this.fromRGBA(r,g,b,null,flags)};this.fromString=function(str,flags){if(!this.required&&str.trim()===""){this.setPreviewElementBg(null);this.setValueElementValue("");return true}var color=jsc.parseColorString(str);if(!color){return false}if(this.format.toLowerCase()==="any"){this._setFormat(color.format);if(!jsc.isAlphaFormat(this.getFormat())){color.rgba[3]=1}}this.fromRGBA(color.rgba[0],color.rgba[1],color.rgba[2],color.rgba[3],flags);return true};this.toString=function(format){if(format===undefined){format=this.getFormat()}switch(format.toLowerCase()){case"hex":return this.toHEXString();break;case"hexa":return this.toHEXAString();break;case"rgb":return this.toRGBString();break;case"rgba":return this.toRGBAString();break}return false};this.toHEXString=function(){return jsc.hexColor(this.channels.r,this.channels.g,this.channels.b)};this.toHEXAString=function(){return jsc.hexaColor(this.channels.r,this.channels.g,this.channels.b,this.channels.a)};this.toRGBString=function(){return jsc.rgbColor(this.channels.r,this.channels.g,this.channels.b)};this.toRGBAString=function(){return jsc.rgbaColor(this.channels.r,this.channels.g,this.channels.b,this.channels.a)};this.toGrayscale=function(){return.213*this.channels.r+.715*this.channels.g+.072*this.channels.b};this.toCanvas=function(){return jsc.genColorPreviewCanvas(this.toRGBAString()).canvas};this.toDataURL=function(){return this.toCanvas().toDataURL()};this.toBackground=function(){return jsc.pub.background(this.toRGBAString())};this.isLight=function(){return this.toGrayscale()>255/2};this.hide=function(){if(isPickerOwner()){detachPicker()}};this.show=function(){drawPicker()};this.redraw=function(){if(isPickerOwner()){drawPicker()}};this.getFormat=function(){return this._currentFormat};this._setFormat=function(format){this._currentFormat=format.toLowerCase()};this.hasAlphaChannel=function(){if(this.alphaChannel==="auto"){return this.format.toLowerCase()==="any"||jsc.isAlphaFormat(this.getFormat())||this.alpha!==undefined||this.alphaElement!==undefined}return this.alphaChannel};this.processValueInput=function(str){if(!this.fromString(str)){this.exposeColor()}};this.processAlphaInput=function(str){if(!this.fromHSVA(null,null,null,parseFloat(str))){this.exposeColor()}};this.exposeColor=function(flags){var colorStr=this.toString();var fmt=this.getFormat();jsc.setDataAttr(this.targetElement,"current-color",colorStr);if(!(flags&jsc.flags.leaveValue)&&this.valueElement){if(fmt==="hex"||fmt==="hexa"){if(!this.uppercase){colorStr=colorStr.toLowerCase()}if(!this.hash){colorStr=colorStr.replace(/^#/,"")}}this.setValueElementValue(colorStr)}if(!(flags&jsc.flags.leaveAlpha)&&this.alphaElement){var alphaVal=Math.round(this.channels.a*100)/100;this.setAlphaElementValue(alphaVal)}if(!(flags&jsc.flags.leavePreview)&&this.previewElement){var previewPos=null;if(jsc.isTextInput(this.previewElement)||jsc.isButton(this.previewElement)&&!jsc.isButtonEmpty(this.previewElement)){previewPos=this.previewPosition}this.setPreviewElementBg(this.toRGBAString())}if(isPickerOwner()){redrawPad();redrawSld();redrawASld()}};this.setPreviewElementBg=function(color){if(!this.previewElement){return}var position=null;var width=null;if(jsc.isTextInput(this.previewElement)||jsc.isButton(this.previewElement)&&!jsc.isButtonEmpty(this.previewElement)){position=this.previewPosition;width=this.previewSize}var backgrounds=[];if(!color){backgrounds.push({image:"none",position:"left top",size:"auto",repeat:"no-repeat",origin:"padding-box"})}else{backgrounds.push({image:jsc.genColorPreviewGradient(color,position,width?width-jsc.pub.previewSeparator.length:null),position:"left top",size:"auto",repeat:position?"repeat-y":"repeat",origin:"padding-box"});var preview=jsc.genColorPreviewCanvas("rgba(0,0,0,0)",position?{left:"right",right:"left"}[position]:null,width,true);backgrounds.push({image:"url('"+preview.canvas.toDataURL()+"')",position:(position||"left")+" top",size:preview.width+"px "+preview.height+"px",repeat:position?"repeat-y":"repeat",origin:"padding-box"})}var bg={image:[],position:[],size:[],repeat:[],origin:[]};for(var i=0;i<backgrounds.length;i+=1){bg.image.push(backgrounds[i].image);bg.position.push(backgrounds[i].position);bg.size.push(backgrounds[i].size);bg.repeat.push(backgrounds[i].repeat);bg.origin.push(backgrounds[i].origin)}var sty={"background-image":bg.image.join(", "),"background-position":bg.position.join(", "),"background-size":bg.size.join(", "),"background-repeat":bg.repeat.join(", "),"background-origin":bg.origin.join(", ")};jsc.setStyle(this.previewElement,sty,this.forceStyle);var padding={left:null,right:null};if(position){padding[position]=this.previewSize+this.previewPadding+"px"}var sty={"padding-left":padding.left,"padding-right":padding.right};jsc.setStyle(this.previewElement,sty,this.forceStyle,true)};this.setValueElementValue=function(str){if(this.valueElement){if(jsc.nodeName(this.valueElement)==="input"){this.valueElement.value=str}else{this.valueElement.innerHTML=str}}};this.setAlphaElementValue=function(str){if(this.alphaElement){if(jsc.nodeName(this.alphaElement)==="input"){this.alphaElement.value=str}else{this.alphaElement.innerHTML=str}}};this._processParentElementsInDOM=function(){if(this._parentElementsProcessed){return}this._parentElementsProcessed=true;var elm=this.targetElement;do{var compStyle=jsc.getCompStyle(elm);if(compStyle.position&&compStyle.position.toLowerCase()==="fixed"){this.fixed=true}if(elm!==this.targetElement){if(!jsc.getData(elm,"hasScrollListener")){elm.addEventListener("scroll",jsc.onParentScroll,false);jsc.setData(elm,"hasScrollListener",true)}}}while((elm=elm.parentNode)&&jsc.nodeName(elm)!=="body")};this.tryHide=function(){if(this.hideOnLeave){this.hide()}};this.set__palette=function(val){this.palette=val;this._palette=jsc.parsePaletteValue(val);this._paletteHasTransparency=jsc.containsTranparentColor(this._palette)};function setOption(option,value){if(typeof option!=="string"){throw new Error("Invalid value for option name: "+option)}if(jsc.enumOpts.hasOwnProperty(option)){if(typeof value==="string"){value=value.toLowerCase()}if(jsc.enumOpts[option].indexOf(value)===-1){throw new Error("Option '"+option+"' has invalid value: "+value)}}if(jsc.deprecatedOpts.hasOwnProperty(option)){var oldOpt=option;var newOpt=jsc.deprecatedOpts[option];if(newOpt){console.warn("Option '%s' is DEPRECATED, using '%s' instead."+jsc.docsRef,oldOpt,newOpt);option=newOpt}else{throw new Error("Option '"+option+"' is DEPRECATED")}}var setter="set__"+option;if(typeof THIS[setter]==="function"){THIS[setter](value);return true}else if(option in THIS){THIS[option]=value;return true}throw new Error("Unrecognized configuration option: "+option)}function getOption(option){if(typeof option!=="string"){throw new Error("Invalid value for option name: "+option)}if(jsc.deprecatedOpts.hasOwnProperty(option)){var oldOpt=option;var newOpt=jsc.deprecatedOpts[option];if(newOpt){console.warn("Option '%s' is DEPRECATED, using '%s' instead."+jsc.docsRef,oldOpt,newOpt);option=newOpt}else{throw new Error("Option '"+option+"' is DEPRECATED")}}var getter="get__"+option;if(typeof THIS[getter]==="function"){return THIS[getter](value)}else if(option in THIS){return THIS[option]}throw new Error("Unrecognized configuration option: "+option)}function detachPicker(){jsc.removeClass(THIS.targetElement,jsc.pub.activeClassName);jsc.picker.wrap.parentNode.removeChild(jsc.picker.wrap);delete jsc.picker.owner}function drawPicker(){THIS._processParentElementsInDOM();if(!jsc.picker){jsc.picker={owner:null,wrap:jsc.createEl("div"),box:jsc.createEl("div"),boxS:jsc.createEl("div"),boxB:jsc.createEl("div"),pad:jsc.createEl("div"),padB:jsc.createEl("div"),padM:jsc.createEl("div"),padCanvas:jsc.createPadCanvas(),cross:jsc.createEl("div"),crossBY:jsc.createEl("div"),crossBX:jsc.createEl("div"),crossLY:jsc.createEl("div"),crossLX:jsc.createEl("div"),sld:jsc.createEl("div"),sldB:jsc.createEl("div"),sldM:jsc.createEl("div"),sldGrad:jsc.createSliderGradient(),sldPtrS:jsc.createEl("div"),sldPtrIB:jsc.createEl("div"),sldPtrMB:jsc.createEl("div"),sldPtrOB:jsc.createEl("div"),asld:jsc.createEl("div"),asldB:jsc.createEl("div"),asldM:jsc.createEl("div"),asldGrad:jsc.createASliderGradient(),asldPtrS:jsc.createEl("div"),asldPtrIB:jsc.createEl("div"),asldPtrMB:jsc.createEl("div"),asldPtrOB:jsc.createEl("div"),pal:jsc.createEl("div"),btn:jsc.createEl("div"),btnT:jsc.createEl("span")};jsc.picker.pad.appendChild(jsc.picker.padCanvas.elm);jsc.picker.padB.appendChild(jsc.picker.pad);jsc.picker.cross.appendChild(jsc.picker.crossBY);jsc.picker.cross.appendChild(jsc.picker.crossBX);jsc.picker.cross.appendChild(jsc.picker.crossLY);jsc.picker.cross.appendChild(jsc.picker.crossLX);jsc.picker.padB.appendChild(jsc.picker.cross);jsc.picker.box.appendChild(jsc.picker.padB);jsc.picker.box.appendChild(jsc.picker.padM);jsc.picker.sld.appendChild(jsc.picker.sldGrad.elm);jsc.picker.sldB.appendChild(jsc.picker.sld);jsc.picker.sldB.appendChild(jsc.picker.sldPtrOB);jsc.picker.sldPtrOB.appendChild(jsc.picker.sldPtrMB);jsc.picker.sldPtrMB.appendChild(jsc.picker.sldPtrIB);jsc.picker.sldPtrIB.appendChild(jsc.picker.sldPtrS);jsc.picker.box.appendChild(jsc.picker.sldB);jsc.picker.box.appendChild(jsc.picker.sldM);jsc.picker.asld.appendChild(jsc.picker.asldGrad.elm);jsc.picker.asldB.appendChild(jsc.picker.asld);jsc.picker.asldB.appendChild(jsc.picker.asldPtrOB);jsc.picker.asldPtrOB.appendChild(jsc.picker.asldPtrMB);jsc.picker.asldPtrMB.appendChild(jsc.picker.asldPtrIB);jsc.picker.asldPtrIB.appendChild(jsc.picker.asldPtrS);jsc.picker.box.appendChild(jsc.picker.asldB);jsc.picker.box.appendChild(jsc.picker.asldM);jsc.picker.box.appendChild(jsc.picker.pal);jsc.picker.btn.appendChild(jsc.picker.btnT);jsc.picker.box.appendChild(jsc.picker.btn);jsc.picker.boxB.appendChild(jsc.picker.box);jsc.picker.wrap.appendChild(jsc.picker.boxS);jsc.picker.wrap.appendChild(jsc.picker.boxB);jsc.picker.wrap.addEventListener("touchstart",jsc.onPickerTouchStart,jsc.isPassiveEventSupported?{passive:false}:false)}var p=jsc.picker;var displaySlider=!!jsc.getSliderChannel(THIS);var displayAlphaSlider=THIS.hasAlphaChannel();var pickerDims=jsc.getPickerDims(THIS);var crossOuterSize=2*THIS.pointerBorderWidth+THIS.pointerThickness+2*THIS.crossSize;var controlPadding=jsc.getControlPadding(THIS);var borderRadius=Math.min(THIS.borderRadius,Math.round(THIS.padding*Math.PI));var padCursor="crosshair";p.wrap.className="jscolor-picker-wrap";p.wrap.style.clear="both";p.wrap.style.width=pickerDims.borderW+"px";p.wrap.style.height=pickerDims.borderH+"px";p.wrap.style.zIndex=THIS.zIndex;p.box.className="jscolor-picker";p.box.style.width=pickerDims.paddedW+"px";p.box.style.height=pickerDims.paddedH+"px";p.box.style.position="relative";p.boxS.className="jscolor-picker-shadow";p.boxS.style.position="absolute";p.boxS.style.left="0";p.boxS.style.top="0";p.boxS.style.width="100%";p.boxS.style.height="100%";jsc.setBorderRadius(p.boxS,borderRadius+"px");p.boxB.className="jscolor-picker-border";p.boxB.style.position="relative";p.boxB.style.border=THIS.borderWidth+"px solid";p.boxB.style.borderColor=THIS.borderColor;p.boxB.style.background=THIS.backgroundColor;jsc.setBorderRadius(p.boxB,borderRadius+"px");p.padM.style.background="rgba(255,0,0,.2)";p.sldM.style.background="rgba(0,255,0,.2)";p.asldM.style.background="rgba(0,0,255,.2)";p.padM.style.opacity=p.sldM.style.opacity=p.asldM.style.opacity="0";p.pad.style.position="relative";p.pad.style.width=THIS.width+"px";p.pad.style.height=THIS.height+"px";p.padCanvas.draw(THIS.width,THIS.height,jsc.getPadYChannel(THIS));p.padB.style.position="absolute";p.padB.style.left=THIS.padding+"px";p.padB.style.top=THIS.padding+"px";p.padB.style.border=THIS.controlBorderWidth+"px solid";p.padB.style.borderColor=THIS.controlBorderColor;p.padM.style.position="absolute";p.padM.style.left=0+"px";p.padM.style.top=0+"px";p.padM.style.width=THIS.padding+2*THIS.controlBorderWidth+THIS.width+controlPadding+"px";p.padM.style.height=2*THIS.controlBorderWidth+2*THIS.padding+THIS.height+"px";p.padM.style.cursor=padCursor;jsc.setData(p.padM,{instance:THIS,control:"pad"});p.cross.style.position="absolute";p.cross.style.left=p.cross.style.top="0";p.cross.style.width=p.cross.style.height=crossOuterSize+"px";p.crossBY.style.position=p.crossBX.style.position="absolute";p.crossBY.style.background=p.crossBX.style.background=THIS.pointerBorderColor;p.crossBY.style.width=p.crossBX.style.height=2*THIS.pointerBorderWidth+THIS.pointerThickness+"px";p.crossBY.style.height=p.crossBX.style.width=crossOuterSize+"px";p.crossBY.style.left=p.crossBX.style.top=Math.floor(crossOuterSize/2)-Math.floor(THIS.pointerThickness/2)-THIS.pointerBorderWidth+"px";p.crossBY.style.top=p.crossBX.style.left="0";p.crossLY.style.position=p.crossLX.style.position="absolute";p.crossLY.style.background=p.crossLX.style.background=THIS.pointerColor;p.crossLY.style.height=p.crossLX.style.width=crossOuterSize-2*THIS.pointerBorderWidth+"px";p.crossLY.style.width=p.crossLX.style.height=THIS.pointerThickness+"px";p.crossLY.style.left=p.crossLX.style.top=Math.floor(crossOuterSize/2)-Math.floor(THIS.pointerThickness/2)+"px";p.crossLY.style.top=p.crossLX.style.left=THIS.pointerBorderWidth+"px";p.sld.style.overflow="hidden";p.sld.style.width=THIS.sliderSize+"px";p.sld.style.height=THIS.height+"px";p.sldGrad.draw(THIS.sliderSize,THIS.height,"#000","#000");p.sldB.style.display=displaySlider?"block":"none";p.sldB.style.position="absolute";p.sldB.style.left=THIS.padding+THIS.width+2*THIS.controlBorderWidth+2*controlPadding+"px";p.sldB.style.top=THIS.padding+"px";p.sldB.style.border=THIS.controlBorderWidth+"px solid";p.sldB.style.borderColor=THIS.controlBorderColor;p.sldM.style.display=displaySlider?"block":"none";p.sldM.style.position="absolute";p.sldM.style.left=THIS.padding+THIS.width+2*THIS.controlBorderWidth+controlPadding+"px";p.sldM.style.top=0+"px";p.sldM.style.width=THIS.sliderSize+2*controlPadding+2*THIS.controlBorderWidth+(displayAlphaSlider?0:Math.max(0,THIS.padding-controlPadding))+"px";p.sldM.style.height=2*THIS.controlBorderWidth+2*THIS.padding+THIS.height+"px";p.sldM.style.cursor="default";jsc.setData(p.sldM,{instance:THIS,control:"sld"});p.sldPtrIB.style.border=p.sldPtrOB.style.border=THIS.pointerBorderWidth+"px solid "+THIS.pointerBorderColor;p.sldPtrOB.style.position="absolute";p.sldPtrOB.style.left=-(2*THIS.pointerBorderWidth+THIS.pointerThickness)+"px";p.sldPtrOB.style.top="0";p.sldPtrMB.style.border=THIS.pointerThickness+"px solid "+THIS.pointerColor;p.sldPtrS.style.width=THIS.sliderSize+"px";p.sldPtrS.style.height=jsc.pub.sliderInnerSpace+"px";p.asld.style.overflow="hidden";p.asld.style.width=THIS.sliderSize+"px";p.asld.style.height=THIS.height+"px";p.asldGrad.draw(THIS.sliderSize,THIS.height,"#000");p.asldB.style.display=displayAlphaSlider?"block":"none";p.asldB.style.position="absolute";p.asldB.style.left=THIS.padding+THIS.width+2*THIS.controlBorderWidth+controlPadding+(displaySlider?THIS.sliderSize+3*controlPadding+2*THIS.controlBorderWidth:0)+"px";p.asldB.style.top=THIS.padding+"px";p.asldB.style.border=THIS.controlBorderWidth+"px solid";p.asldB.style.borderColor=THIS.controlBorderColor;p.asldM.style.display=displayAlphaSlider?"block":"none";p.asldM.style.position="absolute";p.asldM.style.left=THIS.padding+THIS.width+2*THIS.controlBorderWidth+controlPadding+(displaySlider?THIS.sliderSize+2*controlPadding+2*THIS.controlBorderWidth:0)+"px";p.asldM.style.top=0+"px";p.asldM.style.width=THIS.sliderSize+2*controlPadding+2*THIS.controlBorderWidth+Math.max(0,THIS.padding-controlPadding)+"px";p.asldM.style.height=2*THIS.controlBorderWidth+2*THIS.padding+THIS.height+"px";p.asldM.style.cursor="default";jsc.setData(p.asldM,{instance:THIS,control:"asld"});p.asldPtrIB.style.border=p.asldPtrOB.style.border=THIS.pointerBorderWidth+"px solid "+THIS.pointerBorderColor;p.asldPtrOB.style.position="absolute";p.asldPtrOB.style.left=-(2*THIS.pointerBorderWidth+THIS.pointerThickness)+"px";p.asldPtrOB.style.top="0";p.asldPtrMB.style.border=THIS.pointerThickness+"px solid "+THIS.pointerColor;p.asldPtrS.style.width=THIS.sliderSize+"px";p.asldPtrS.style.height=jsc.pub.sliderInnerSpace+"px";p.pal.className="jscolor-palette";p.pal.style.display=pickerDims.palette.rows?"block":"none";p.pal.style.position="absolute";p.pal.style.left=THIS.padding+"px";p.pal.style.top=2*THIS.controlBorderWidth+2*THIS.padding+THIS.height+"px";p.pal.innerHTML="";var chessboard=jsc.genColorPreviewCanvas("rgba(0,0,0,0)");var si=0;for(var r=0;r<pickerDims.palette.rows;r++){for(var c=0;c<pickerDims.palette.cols&&si<THIS._palette.length;c++,si++){var sampleColor=THIS._palette[si];var sampleCssColor=jsc.rgbaColor.apply(null,sampleColor.rgba);var sc=jsc.createEl("div");sc.style.width=pickerDims.palette.cellW-2*THIS.controlBorderWidth+"px";sc.style.height=pickerDims.palette.cellH-2*THIS.controlBorderWidth+"px";sc.style.backgroundColor=sampleCssColor;var sw=jsc.createEl("div");sw.className="jscolor-palette-sample";sw.style.display="block";sw.style.position="absolute";sw.style.left=(pickerDims.palette.cols<=1?0:Math.round(10*(c*((pickerDims.contentW-pickerDims.palette.cellW)/(pickerDims.palette.cols-1))))/10)+"px";sw.style.top=r*(pickerDims.palette.cellH+THIS.paletteSpacing)+"px";sw.style.border=THIS.controlBorderWidth+"px solid";sw.style.borderColor=THIS.controlBorderColor;sw.style.cursor="pointer";if(sampleColor.rgba[3]!==null&&sampleColor.rgba[3]<1){sw.style.backgroundImage="url('"+chessboard.canvas.toDataURL()+"')";sw.style.backgroundRepeat="repeat";sw.style.backgroundPosition="center center"}jsc.setData(sw,{instance:THIS,control:"palette-sample",color:sampleColor});sw.addEventListener("click",jsc.onPaletteSampleClick,false);sw.appendChild(sc);p.pal.appendChild(sw)}}function setBtnBorder(){var insetColors=THIS.controlBorderColor.split(/\s+/);var outsetColor=insetColors.length<2?insetColors[0]:insetColors[1]+" "+insetColors[0]+" "+insetColors[0]+" "+insetColors[1];p.btn.style.borderColor=outsetColor}var btnPadding=15;p.btn.className="jscolor-btn-close";p.btn.style.display=THIS.closeButton?"block":"none";p.btn.style.position="absolute";p.btn.style.left=THIS.padding+"px";p.btn.style.bottom=THIS.padding+"px";p.btn.style.padding="0 "+btnPadding+"px";p.btn.style.maxWidth=pickerDims.contentW-2*THIS.controlBorderWidth-2*btnPadding+"px";p.btn.style.overflow="hidden";p.btn.style.height=THIS.buttonHeight+"px";p.btn.style.whiteSpace="nowrap";p.btn.style.border=THIS.controlBorderWidth+"px solid";setBtnBorder();p.btn.style.color=THIS.buttonColor;p.btn.style.font="12px sans-serif";p.btn.style.textAlign="center";p.btn.style.cursor="pointer";p.btn.onmousedown=function(){THIS.hide()};p.btnT.style.lineHeight=THIS.buttonHeight+"px";p.btnT.innerHTML="";p.btnT.appendChild(window.document.createTextNode(THIS.closeText));redrawPad();redrawSld();redrawASld();if(jsc.picker.owner&&jsc.picker.owner!==THIS){jsc.removeClass(jsc.picker.owner.targetElement,jsc.pub.activeClassName)}jsc.picker.owner=THIS;if(THIS.container===window.document.body){jsc.redrawPosition()}else{jsc._drawPosition(THIS,0,0,"relative",false)}if(p.wrap.parentNode!==THIS.container){THIS.container.appendChild(p.wrap)}jsc.addClass(THIS.targetElement,jsc.pub.activeClassName)}function redrawPad(){var yChannel=jsc.getPadYChannel(THIS);var x=Math.round(THIS.channels.h/360*(THIS.width-1));var y=Math.round((1-THIS.channels[yChannel]/100)*(THIS.height-1));var crossOuterSize=2*THIS.pointerBorderWidth+THIS.pointerThickness+2*THIS.crossSize;var ofs=-Math.floor(crossOuterSize/2);jsc.picker.cross.style.left=x+ofs+"px";jsc.picker.cross.style.top=y+ofs+"px";switch(jsc.getSliderChannel(THIS)){case"s":var rgb1=jsc.HSV_RGB(THIS.channels.h,100,THIS.channels.v);var rgb2=jsc.HSV_RGB(THIS.channels.h,0,THIS.channels.v);var color1="rgb("+Math.round(rgb1[0])+","+Math.round(rgb1[1])+","+Math.round(rgb1[2])+")";var color2="rgb("+Math.round(rgb2[0])+","+Math.round(rgb2[1])+","+Math.round(rgb2[2])+")";jsc.picker.sldGrad.draw(THIS.sliderSize,THIS.height,color1,color2);break;case"v":var rgb=jsc.HSV_RGB(THIS.channels.h,THIS.channels.s,100);var color1="rgb("+Math.round(rgb[0])+","+Math.round(rgb[1])+","+Math.round(rgb[2])+")";var color2="#000";jsc.picker.sldGrad.draw(THIS.sliderSize,THIS.height,color1,color2);break}jsc.picker.asldGrad.draw(THIS.sliderSize,THIS.height,THIS.toHEXString())}function redrawSld(){var sldChannel=jsc.getSliderChannel(THIS);if(sldChannel){var y=Math.round((1-THIS.channels[sldChannel]/100)*(THIS.height-1));jsc.picker.sldPtrOB.style.top=y-(2*THIS.pointerBorderWidth+THIS.pointerThickness)-Math.floor(jsc.pub.sliderInnerSpace/2)+"px"}jsc.picker.asldGrad.draw(THIS.sliderSize,THIS.height,THIS.toHEXString())}function redrawASld(){var y=Math.round((1-THIS.channels.a)*(THIS.height-1));jsc.picker.asldPtrOB.style.top=y-(2*THIS.pointerBorderWidth+THIS.pointerThickness)-Math.floor(jsc.pub.sliderInnerSpace/2)+"px"}function isPickerOwner(){return jsc.picker&&jsc.picker.owner===THIS}function onValueKeyDown(ev){if(jsc.eventKey(ev)==="Enter"){if(THIS.valueElement){THIS.processValueInput(THIS.valueElement.value)}THIS.tryHide()}}function onAlphaKeyDown(ev){if(jsc.eventKey(ev)==="Enter"){if(THIS.alphaElement){THIS.processAlphaInput(THIS.alphaElement.value)}THIS.tryHide()}}function onValueChange(ev){if(jsc.getData(ev,"internal")){return}var oldVal=THIS.valueElement.value;THIS.processValueInput(THIS.valueElement.value);jsc.triggerCallback(THIS,"onChange");if(THIS.valueElement.value!==oldVal){jsc.triggerInputEvent(THIS.valueElement,"change",true,true)}}function onAlphaChange(ev){if(jsc.getData(ev,"internal")){return}var oldVal=THIS.alphaElement.value;THIS.processAlphaInput(THIS.alphaElement.value);jsc.triggerCallback(THIS,"onChange");jsc.triggerInputEvent(THIS.valueElement,"change",true,true);if(THIS.alphaElement.value!==oldVal){jsc.triggerInputEvent(THIS.alphaElement,"change",true,true)}}function onValueInput(ev){if(jsc.getData(ev,"internal")){return}if(THIS.valueElement){THIS.fromString(THIS.valueElement.value,jsc.flags.leaveValue)}jsc.triggerCallback(THIS,"onInput")}function onAlphaInput(ev){if(jsc.getData(ev,"internal")){return}if(THIS.alphaElement){THIS.fromHSVA(null,null,null,parseFloat(THIS.alphaElement.value),jsc.flags.leaveAlpha)}jsc.triggerCallback(THIS,"onInput");jsc.triggerInputEvent(THIS.valueElement,"input",true,true)}if(jsc.pub.options){for(var opt in jsc.pub.options){if(jsc.pub.options.hasOwnProperty(opt)){try{setOption(opt,jsc.pub.options[opt])}catch(e){console.warn(e)}}}}var presetsArr=[];if(opts.preset){if(typeof opts.preset==="string"){presetsArr=opts.preset.split(/\s+/)}else if(Array.isArray(opts.preset)){presetsArr=opts.preset.slice()}else{console.warn("Unrecognized preset value")}}if(presetsArr.indexOf("default")===-1){presetsArr.push("default")}for(var i=presetsArr.length-1;i>=0;i-=1){var pres=presetsArr[i];if(!pres){continue}if(!jsc.pub.presets.hasOwnProperty(pres)){console.warn("Unknown preset: %s",pres);continue}for(var opt in jsc.pub.presets[pres]){if(jsc.pub.presets[pres].hasOwnProperty(opt)){try{setOption(opt,jsc.pub.presets[pres][opt])}catch(e){console.warn(e)}}}}var nonProperties=["preset"];for(var opt in opts){if(opts.hasOwnProperty(opt)){if(nonProperties.indexOf(opt)===-1){try{setOption(opt,opts[opt])}catch(e){console.warn(e)}}}}if(this.container===undefined){this.container=window.document.body}else{this.container=jsc.node(this.container)}if(!this.container){throw new Error("Cannot instantiate color picker without a container element")}this.targetElement=jsc.node(targetElement);if(!this.targetElement){if(typeof targetElement==="string"&&/^[a-zA-Z][\w:.-]*$/.test(targetElement)){var possiblyId=targetElement;throw new Error("If '"+possiblyId+"' is supposed to be an ID, please use '#"+possiblyId+"' or any valid CSS selector.")}throw new Error("Cannot instantiate color picker without a target element")}if(this.targetElement.jscolor&&this.targetElement.jscolor instanceof jsc.pub){throw new Error("Color picker already installed on this element")}this.targetElement.jscolor=this;jsc.addClass(this.targetElement,jsc.pub.className);jsc.instances.push(this);if(jsc.isButton(this.targetElement)){if(this.targetElement.type.toLowerCase()!=="button"){this.targetElement.type="button"}if(jsc.isButtonEmpty(this.targetElement)){jsc.removeChildren(this.targetElement);this.targetElement.appendChild(window.document.createTextNode(" "));var compStyle=jsc.getCompStyle(this.targetElement);var currMinWidth=parseFloat(compStyle["min-width"])||0;if(currMinWidth<this.previewSize){jsc.setStyle(this.targetElement,{"min-width":this.previewSize+"px"},this.forceStyle)}}}if(this.valueElement===undefined){if(jsc.isTextInput(this.targetElement)){this.valueElement=this.targetElement}else{}}else if(this.valueElement===null){}else{this.valueElement=jsc.node(this.valueElement)}if(this.alphaElement){this.alphaElement=jsc.node(this.alphaElement)}if(this.previewElement===undefined){this.previewElement=this.targetElement}else if(this.previewElement===null){}else{this.previewElement=jsc.node(this.previewElement)}if(this.valueElement&&jsc.isTextInput(this.valueElement)){var valueElementOrigEvents={onInput:this.valueElement.oninput};this.valueElement.oninput=null;this.valueElement.addEventListener("keydown",onValueKeyDown,false);this.valueElement.addEventListener("change",onValueChange,false);this.valueElement.addEventListener("input",onValueInput,false);if(valueElementOrigEvents.onInput){this.valueElement.addEventListener("input",valueElementOrigEvents.onInput,false)}this.valueElement.setAttribute("autocomplete","off");this.valueElement.setAttribute("autocorrect","off");this.valueElement.setAttribute("autocapitalize","off");this.valueElement.setAttribute("spellcheck",false)}if(this.alphaElement&&jsc.isTextInput(this.alphaElement)){this.alphaElement.addEventListener("keydown",onAlphaKeyDown,false);this.alphaElement.addEventListener("change",onAlphaChange,false);this.alphaElement.addEventListener("input",onAlphaInput,false);this.alphaElement.setAttribute("autocomplete","off");this.alphaElement.setAttribute("autocorrect","off");this.alphaElement.setAttribute("autocapitalize","off");this.alphaElement.setAttribute("spellcheck",false)}var initValue="FFFFFF";if(this.value!==undefined){initValue=this.value}else if(this.valueElement&&this.valueElement.value!==undefined){initValue=this.valueElement.value}var initAlpha=undefined;if(this.alpha!==undefined){initAlpha=""+this.alpha}else if(this.alphaElement&&this.alphaElement.value!==undefined){initAlpha=this.alphaElement.value}this._currentFormat=null;if(["auto","any"].indexOf(this.format.toLowerCase())>-1){var color=jsc.parseColorString(initValue);this._currentFormat=color?color.format:"hex"}else{this._currentFormat=this.format.toLowerCase()}this.processValueInput(initValue);if(initAlpha!==undefined){this.processAlphaInput(initAlpha)}}};jsc.pub.className="jscolor";jsc.pub.activeClassName="jscolor-active";jsc.pub.looseJSON=true;jsc.pub.presets={};jsc.pub.presets["default"]={};jsc.pub.presets["light"]={backgroundColor:"rgba(255,255,255,1)",controlBorderColor:"rgba(187,187,187,1)",buttonColor:"rgba(0,0,0,1)"};jsc.pub.presets["dark"]={backgroundColor:"rgba(51,51,51,1)",controlBorderColor:"rgba(153,153,153,1)",buttonColor:"rgba(240,240,240,1)"};jsc.pub.presets["small"]={width:101,height:101,padding:10,sliderSize:14,paletteCols:8};jsc.pub.presets["medium"]={width:181,height:101,padding:12,sliderSize:16,paletteCols:10};jsc.pub.presets["large"]={width:271,height:151,padding:12,sliderSize:24,paletteCols:15};jsc.pub.presets["thin"]={borderWidth:1,controlBorderWidth:1,pointerBorderWidth:1};jsc.pub.presets["thick"]={borderWidth:2,controlBorderWidth:2,pointerBorderWidth:2};jsc.pub.sliderInnerSpace=3;jsc.pub.chessboardSize=8;jsc.pub.chessboardColor1="#666666";jsc.pub.chessboardColor2="#999999";jsc.pub.previewSeparator=["rgba(255,255,255,.65)","rgba(128,128,128,.65)"];jsc.pub.init=function(){if(jsc.initialized){return}window.document.addEventListener("mousedown",jsc.onDocumentMouseDown,false);window.document.addEventListener("keyup",jsc.onDocumentKeyUp,false);window.addEventListener("resize",jsc.onWindowResize,false);window.addEventListener("scroll",jsc.onWindowScroll,false);jsc.pub.install();jsc.initialized=true;while(jsc.readyQueue.length){var func=jsc.readyQueue.shift();func()}};jsc.pub.install=function(rootNode){var success=true;try{jsc.installBySelector("[data-jscolor]",rootNode)}catch(e){success=false;console.warn(e)}if(jsc.pub.lookupClass){try{jsc.installBySelector("input."+jsc.pub.lookupClass+", "+"button."+jsc.pub.lookupClass,rootNode)}catch(e){}}return success};jsc.pub.ready=function(func){if(typeof func!=="function"){console.warn("Passed value is not a function");return false}if(jsc.initialized){func()}else{jsc.readyQueue.push(func)}return true};jsc.pub.trigger=function(eventNames){var triggerNow=function(){jsc.triggerGlobal(eventNames)};if(jsc.initialized){triggerNow()}else{jsc.pub.ready(triggerNow)}};jsc.pub.hide=function(){if(jsc.picker&&jsc.picker.owner){jsc.picker.owner.hide()}};jsc.pub.chessboard=function(color){if(!color){color="rgba(0,0,0,0)"}var preview=jsc.genColorPreviewCanvas(color);return preview.canvas.toDataURL()};jsc.pub.background=function(color){var backgrounds=[];backgrounds.push(jsc.genColorPreviewGradient(color));var preview=jsc.genColorPreviewCanvas();backgrounds.push(["url('"+preview.canvas.toDataURL()+"')","left top","repeat"].join(" "));return backgrounds.join(", ")};jsc.pub.options={};jsc.pub.lookupClass="jscolor";jsc.pub.installByClassName=function(){console.error('jscolor.installByClassName() is DEPRECATED. Use data-jscolor="" attribute instead of a class name.'+jsc.docsRef);return false};jsc.register();return jsc.pub}();if(typeof window.jscolor==="undefined"){window.jscolor=window.JSColor=jscolor}return jscolor});
/*====color结束====*/
