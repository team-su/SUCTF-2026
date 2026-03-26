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
	
	CKEDITOR.dialog.add('video', function(editor){
		var tip1 = '<style>';
		tip1 += '.cke_dialog_contents_body .cke_dialog_ui_hbox_last>a.cke_dialog_ui_button{ margin-top:0;  }';
		tip1 += '.video_title{ border-bottom:1px solid #ddd; margin:0; padding:8px 0px; font-size:14px;font-weight:bold; }';
		tip1 += '</style><div class="video_title">说明</div>';
		tip1 += '<p style="color:#000;margin-top:10px; line-height:22px;">';
		tip1 += '1. 推荐使用<b style="color:red;">H.264编码的MP4格式视频</b>，兼容性好<br/>';
		tip1 += '2. 视频建议上传到七牛云（在【应用】-【七牛云存储】插件），无广告、节省流量，播放速度快</p>';
		return {
			title: '插入视频', 
			resizable: CKEDITOR.DIALOG_RESIZE_NONE,  
			minWidth: 600,  
			minHeight: 320, 
			contents: [{  
					id: 'Upload',  
					elements: [
						{ type:'html',html:'<div class="video_title">视频地址</div>' },
						{ type: 'text',width: '100%', id:'src',setup : loadValue,commit : commitValue},
						{
							type : 'button', id:'browse',label :"选择视频文件", onClick:function(that){
								if("undefined" !== typeof FileManager){
									FileManager.selectActionFunction = function(url){
										var obj = that.data.dialog.getContentElement("Upload", 'src');
										obj.setValue(url);
									};
									FileManager.popup();
								}else{
									alert("没有权限查看！");
								}
							} 
						 },
						{ type:'html',html:'<div class="video_title">参数设置</div>' },
						{
							type: 'hbox', widths: ['30%', '30%','30%'],
							children: [
								{ type: 'text',width: '70px', id: 'width','default':'450',label: '宽度',setup : loadValue,commit : commitValue },
								{ type: 'text',width: '70px',id: 'height','default':'320',label: '高度',setup : loadValue,commit : commitValue},
								{ type: 'radio', id: 'autoplay', items : [ [ '开启', 'autoplay' ], [ '关闭', '' ] ] , 'default':'', 
									label: '<p style="margin-bottom:10px;">是否自动播放视频</p>',
									setup : loadValue, commit : commitValue
								}
							]
						},
						{ type: 'html', html: tip1, id: 'help' }
					]  
				}
			], 
			onShow : function(){
					this.fakeImage = this.iPlayerNode = null;
					var fakeImage = this.getSelectedElement();
					if ( fakeImage && fakeImage.data( 'cke-real-element-type' ) && fakeImage.data( 'cke-real-element-type' ) == 'video' ){
						this.fakeImage = fakeImage;
						var iPlayerNode = editor.restoreRealElement( fakeImage );
						this.iPlayerNode = iPlayerNode;
						this.setupContent( iPlayerNode );
					}
			},
			onOk: function(){
					var iPlayerNode;
					if ( !this.fakeImage ){
						iPlayerNode = new CKEDITOR.dom.element( 'video' );
					}else{
						iPlayerNode = this.iPlayerNode;
					}
	
					var extraStyles = {}, extraAttributes = {};
					this.commitContent( iPlayerNode, extraStyles, extraAttributes );
	
					iPlayerNode.setAttribute("controls","controls");
					iPlayerNode.setAttribute("class","edui-faked-video video-js"); //百度编辑器class，为了兼容加上
					// Refresh the fake image.
					var newFakeImage = editor.createFakeElement( iPlayerNode, 'cke_video', 'video', true );
					

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