//由于里面包含一些反斜杠，会被php替换，所以必须独立放到js文件中
function getCssKey(content){
	//场景1：#xx.align1 img{ border:0; margin-right: {\$变量}px; }
	//场景2：#xx.align1 img{ margin-right: {\$变量}px; }
	//场景3：#xx.align1 img{ border:0;{\$变量|ParseFont} }
	//场景4：#xx.align1 img{ {\$变量|ParseFont} margin-right: {\$变量}px; }  //前面是个变量
	//场景5：#xx.align1 img{ top:3px; border: {\$变量}px dashed {\$变量};}
	//场景6：#xx.align1{} img{ margin-right: {\$变量}px; }
	console.log("CssKey="+content);
	if(content.length == 0) return false;
	//必须替换{\$变量}为空，才能适应场景5的情况，反斜杠会被替换为空
	content = content.replace(/{\$.+?}/g, "");
	var fromIndex = content.length - 3;
	if(fromIndex < 0) fromIndex = content.length - 1;
	var startIndex = content.lastIndexOf(";", fromIndex);
	if(startIndex== -1) startIndex = content.lastIndexOf("}", fromIndex);
	if(startIndex== -1) startIndex = content.lastIndexOf("{", fromIndex);
	if(startIndex== -1) startIndex = 0; //如果没有找到，说明全部是的
	var cssKey = content.substr(startIndex);
	cssKey = cssKey.split(":")[0];
	cssKey = cssKey.replace(/;|:| |\}|\{/g, ""); //替换分号；冒号，空格
	cssKey = cssKey.toLowerCase();
	return cssKey;
}