CKEDITOR.plugins.add('video', {
    requires: ['dialog', 'fakeobjects'],
	onLoad: function() {
		var url = CKEDITOR.getUrl( this.path + 'images/placeholder.png' );
		CKEDITOR.addCss('img.cke_video{' +
					'background-image: url(' + url + ');' +
					'background-color: #000;' +
					'background-position: center center;' +
					'background-repeat: no-repeat;' +
					'border: 1px solid #a9a9a9;' +
					'width: 300px;' +
					'height: 225px;' +
				'}'
		);
    },
    init: function(editor){
        var b = editor.addCommand('video', new CKEDITOR.dialogCommand('video',{
			allowedContent: 'video[autoplay,height,src,width,controls,class]'
		}));
        editor.ui.addButton('video', {
            label: "插入视频",
            command: 'video',
            icon: this.path + 'images/video.png'
        });
        CKEDITOR.dialog.add('video', this.path + 'dialogs/video.js');
		
		//双击事件
		editor.on( 'doubleclick', function( evt ){
			var element = evt.data.element;
			if ( element.is( 'img' ) && element.data( 'cke-real-element-type' ) == 'video' ){
				evt.data.dialog = 'video';
			}
		});
		
		if ( editor.addMenuItems ){
				editor.addMenuItems({
					video :{label : "视频属性",  command : 'video',  group : 'image' }
				});
		}
		
		if ( editor.contextMenu ){
			editor.contextMenu.addListener( function( element, selection ){
				if ( element && element.is( 'img' ) && element.data( 'cke-real-element-type' ) == 'video' )
						return { video : CKEDITOR.TRISTATE_OFF };
			});
		}
    },
	
	afterInit : function( editor ){
		var dataProcessor = editor.dataProcessor;
		var dataFilter = dataProcessor && dataProcessor.dataFilter;
		if ( dataFilter ){
			dataFilter.addRules({
				elements :{
					video : function( element ){
						return editor.createFakeParserElement( element, 'cke_video', 'video', true );
					}
				}
			});
		}
	}
	
});