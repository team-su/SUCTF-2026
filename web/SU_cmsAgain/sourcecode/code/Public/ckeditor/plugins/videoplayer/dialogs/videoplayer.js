(function(){
	function loadValue( iNode ){
		var isCheckbox = this instanceof CKEDITOR.ui.dialog.checkbox;
		if ( iNode.hasAttribute( this.id ) ){
			var value = iNode.getAttribute( this.id );
			if ( isCheckbox )
				this.setValue( checkboxValues[ this.id ][ 'true' ] == value.toLowerCase() );
			else
				this.setValue( value );
		}
	}
	
	function commitValue( iNode ){
		var isRemove = this.getValue() === '',
			isCheckbox = this instanceof CKEDITOR.ui.dialog.checkbox,
			value = this.getValue();
		if ( isRemove ){
			iNode.removeAttribute( this.att || this.id );
		}else if ( isCheckbox ) {
			iNode.setAttribute( this.id, checkboxValues[ this.id ][ value ] );
		}else{
			iNode.setAttribute( this.att || this.id, value );
		}
	}
	
	CKEDITOR.dialog.add('videoplayer', function(editor){ 
		var tip1 = '<style>';
		tip1 += '.cke_dialog_contents_body .cke_dialog_ui_hbox_last>a.cke_dialog_ui_button{ margin-top:0;  }';
		tip1 += '.video_title{ border-bottom:1px solid #ddd; margin:0px 0px 10px; padding:8px 0px; font-size:14px;font-weight:bold; }';
		tip1 += '</style><div class="video_title">说明</div>';
		tip1 += '<p style="color:#000;margin-bottom:10px; line-height:22px;">';
		tip1 += '1. 推荐使用<b style="color:red;">H.264编码的MP4格式视频</b>，能在电脑、平板、手机观看，兼容性好';
		tip1 += '<a href="http://jingyan.baidu.com/article/c1465413b5ebc40bfdfc4c7a.html" target="_blank" style="color:red;">如何转化为H.264编码的mp4格式？</a><br/>';
		tip1 += '2. 大视频请在【插件管理】-【七牛云存储】上传到七牛云（免费、无广告、播放速度快），然后引用地址即可</p>';
		return {
			title: '插入视频', 
			resizable: CKEDITOR.DIALOG_RESIZE_NONE,  
			minWidth: 600,  
			minHeight: 330, 
			contents: [
				{  
					id: 'Upload',  
					hidden: true, 
					label: '上传视频',   
					filebrowser: 'uploadButton',				
					elements: [
						{
							type: 'text',
							id: 'src',
							label: '<div class="video_title">视频地址</div>',
							setup : loadValue,
							commit : commitValue
						},
						{
							type: 'hbox',
							widths: ['', '18%', '18%'],
							children: [
							   {
									type: 'file',
									id: 'upload',
									size: 38
								}, 
							   {
									type: 'fileButton',
									id: 'uploadButton',
									filebrowser: 'Upload:src',
									label: '上传到服务器上',
									'for': ['Upload', 'upload']
								},
								{
									type : 'button',
									id : 'browse',
									filebrowser :{
										action : 'Browse',
										target: 'Upload:src',
										url: editor.config.filebrowserImageBrowseLinkUrl
									},
									hidden : true,
									label : editor.lang.common.browseServer
								}
							]
						},
						{
							type:'html',
							html:'<div class="video_title">参数设置</div>'
						},
						{
							type: 'hbox',
							widths: ['30%', '30%','30%'],
							children: [
								{
									type: 'text',
									width: '70px',
									id: 'width',
									'default':'450',
									label: '宽度',
									setup : loadValue,
									commit : commitValue
								},
								{
									type: 'text',
									width: '70px',
									id: 'height',
									'default':'320',
									label: '高度',
									setup : loadValue,
									commit : commitValue
								},
								{
									type: 'radio',
									id: 'autostart',
									items : [ [ '开启', '1' ], [ '关闭', '0' ] ] ,
									'default':'0',
									label: '<p style="margin-bottom:10px;">是否自动播放视频</p>',
									setup : loadValue,
									commit : commitValue
								}
							]
						},
						{
							type: 'html',
							html: tip1,
							id: 'help'
						}
					]  
				}
			], 
			onShow : function(){
					this.fakeImage = this.iPlayerNode = null;
					var fakeImage = this.getSelectedElement();
					if ( fakeImage && fakeImage.data( 'cke-real-element-type' ) && fakeImage.data( 'cke-real-element-type' ) == 'videoplayer' ){
						this.fakeImage = fakeImage;
						var iPlayerNode = editor.restoreRealElement( fakeImage );
						this.iPlayerNode = iPlayerNode;
						this.setupContent( iPlayerNode );
					}
			},
			onOk: function(){
					var iPlayerNode;
					if ( !this.fakeImage ){
						iPlayerNode = new CKEDITOR.dom.element( 'videoplayer' );
					}else{
						iPlayerNode = this.iPlayerNode;
					}
	
					var extraStyles = {}, extraAttributes = {};
					this.commitContent( iPlayerNode, extraStyles, extraAttributes );
	
					// Refresh the fake image.
					var newFakeImage = editor.createFakeElement( iPlayerNode, 'cke_videoplayer', 'videoplayer', true );
					newFakeImage.setAttributes( extraAttributes );
					newFakeImage.setStyles( extraStyles );
					if ( this.fakeImage ){
						newFakeImage.replace( this.fakeImage );
						editor.getSelection().selectElement( newFakeImage );
					}else{
						editor.insertElement( newFakeImage );
					}	
			},  
			onLoad: function(){   
			          
			}  
		};  
	});
})();