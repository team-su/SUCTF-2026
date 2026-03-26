CKEDITOR.editorConfig = function( config ) {
	//config.language = 'zh-cn';
	config.toolbar = [
  		{ name: 'document', items: [ 'Source', '-', 'Templates' ] },
  		{ name: 'clipboard', items: [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
  		{ name: 'editing', items: [ 'Find', 'Replace', '-', 'SelectAll', '-', 'Scayt' ] },
  		'/',
  		{ name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'CopyFormatting', 'RemoveFormat' ] },
  		{ name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl', 'Language' ] },
  		{ name: 'links', items: [ 'Link', 'Unlink', 'Anchor' ] },
  		{ name: 'insert', items: [ 'Image', 'video', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar'] },
  		'/',
  		{ name: 'styles', items: [ 'Styles', 'Format', 'Font', 'FontSize','lineheight' ] },
  		{ name: 'colors', items: [ 'TextColor', 'BGColor' ] },
  		{ name: 'tools', items: [ 'Maximize', 'ShowBlocks'] }
  	];
	
	config.allowedContent=true; 
	config.autoParagraph = false;
	config.enterMode = CKEDITOR.ENTER_BR;
	config.shiftEnterMode = CKEDITOR.ENTER_P; 
	config.font_names='正文/正文;宋体/宋体;黑体/黑体;仿宋/仿宋_GB2312;楷体/楷体_GB2312;隶书/隶书;幼圆/幼圆;微软雅黑/微软雅黑;'+config.font_names;
	
	//config.format_pre = { element : 'pre', attributes : { class : 'brush:xml' } };
	config.protectedSource.push(/<pre[\s\S]*?pre>/g); //缺点：会导致不可见，不使用会导致标签无效
	
	config.extraPlugins += (config.extraPlugins ? ',lineheight' : 'lineheight');
	config.extraPlugins += ',video';
};
