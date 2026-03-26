CKEDITOR.plugins.add('multiimage', {
    init: function(editor){
        var b = editor.addCommand('multiimage', {
			exec: function( editor ) {
				FileManager.selectActionFunction = function(url, currentfile, allfiles){
					var html = "";
					var n = 0;
					for(var i = 0; i < allfiles.length; i++){
						var fileUrl = allfiles[i].url;
						var ext = allfiles[i].ext;
						var name = allfiles[i].name;
						if(allfiles[i].isImage){
							html += '<img src="'+fileUrl+'" />';
						}else if(ext=="mp4"){
							html += '<video class="edui-faked-video video-js" controls="controls" height="320" src="'+fileUrl+'" width="450">&nbsp;</video>';
						}else{
							var target = (ext=="zip" || ext=="rar") ? "_self" : "_blank";
							html += '<a href="'+fileUrl+'" target="'+target+'" />'+name+'</a>';
						}
						html += '<br/>';
					}
					if(html.length>0){
						editor.insertHtml( html );
					}
				};
				FileManager.popup(); 
			}	
		});
        editor.ui.addButton('multiimage', {
            label: "多图插入和上传（按Ctrl可选择多个图片、右键选择）",
            command: 'multiimage',
            icon: this.path + '/icon.png'
        });
    }
});