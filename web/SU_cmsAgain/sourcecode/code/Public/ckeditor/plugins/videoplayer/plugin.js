CKEDITOR.plugins.add('videoplayer', {
    requires: ['dialog', 'fakeobjects'],
	onLoad: function() {
		var url = CKEDITOR.getUrl( this.path + 'images/placeholder.png' );
		CKEDITOR.addCss('img.cke_videoplayer{' +
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
        var b = editor.addCommand('videoplayer', new CKEDITOR.dialogCommand('videoplayer',{
			allowedContent: 'videoplayer[autostart,height,src,width]'
		}));
        editor.ui.addButton('videoplayer', {
            label: "插入视频",
            command: 'videoplayer',
            icon: this.path + 'images/videoplayer.png'
        });
        CKEDITOR.dialog.add('videoplayer', this.path + 'dialogs/videoplayer.js');
		
		//双击事件
		editor.on( 'doubleclick', function( evt ){
			var element = evt.data.element;
			if ( element.is( 'img' ) && element.data( 'cke-real-element-type' ) == 'videoplayer' ){
				evt.data.dialog = 'videoplayer';
			}
		});
		
		if ( editor.addMenuItems ){
				editor.addMenuItems({
					videoplayer :{label : "视频属性",  command : 'videoplayer',  group : 'image' }
				});
		}
		
		if ( editor.contextMenu ){
			editor.contextMenu.addListener( function( element, selection ){
				if ( element && element.is( 'img' ) && element.data( 'cke-real-element-type' ) == 'videoplayer' )
						return { videoplayer : CKEDITOR.TRISTATE_OFF };
			});
		}
    },
	
	afterInit : function( editor ){
		var dataProcessor = editor.dataProcessor;
		var dataFilter = dataProcessor && dataProcessor.dataFilter;
		if ( dataFilter ){
			dataFilter.addRules({
				elements :{
					videoplayer : function( element ){
						return editor.createFakeParserElement( element, 'cke_videoplayer', 'videoplayer', true );
					}
				}
			});
		}
	}
	
});