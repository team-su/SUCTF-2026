;(function(global){
	var fn = function(){
		//FileManager全局参数
		var fm; // FileManager对象本身，在调用popup或widget时赋值
		var mDlg;
		var mChildDlg;
		var mChildDialogID = 'yd-fm-child-dialog'; //二级子dialog的id 主要用于判断是否阻止自定义事件（键盘，鼠标）
		var mCurrentTarget = null; //当前鼠标或聚焦的文件 文件夹
		var mConfig = {}; //配置参数
		var mFiles = {}; //上传的文件列表
		var mUploader = {}; //上传对象
		var mHasUploadError = false; //上传是否出现错误， 并且用于判断上传状态的自动隐藏
		var mUploadIsOverWrite = getStorage('YDUploadIsOverWrite') || 0; //上传覆盖
		var mUploadCloseTimer; //关闭上传进度定时器
		var mDataSource = getStorage('YDDataSource') || 1; //数据来源 1：本地 2：七牛云 3：阿里云
		var mCurrentDir1 = getStorage('YDCurrentDir1') || ''; //当前打开的文件夹 本地
		var mCurrentDir2 = getStorage('YDCurrentDir2') || ''; //当前打开的文件夹 七牛
		var mCurrentDir3 = getStorage('YDCurrentDir3') || ''; //当前打开的文件夹 阿里
		var mGetCurrentDir = function(){ //当前打开的文件夹 用于获取数据 上面三个用于缓存
			//console.log("调用mGetCurrentDir函数");
			if(mDataSource == 1) {
				return getStorage('YDCurrentDir1') || '';
			}
			if(mDataSource == 2) {
				return getStorage('YDCurrentDir2') || '';
			}
			if(mDataSource == 3) {
				return getStorage('YDCurrentDir3') || '';
			}
		};
		var mCurrentDir = mGetCurrentDir();
		var mViewType = getStorage('YDCurrentViewType') || 1; //视图样式
		var mSortField = getStorage('YDCurrentSortField') || 1; //排序方式
		var mSortOrder = getStorage('YDCurrentSortOrder') || 3; //排序顺序
		var mImageShowThreshold = getStorage('YDImageShowThreshold') || 500; //显示小于这个值的图片
		var mIsCache = getStorage('YDImageIsCache') || 1; //是否缓存缩略图
		var mSizeTip = '高填0，表示根据宽度，按比例计算高度！宽填0也一样。用于实现等比例缩放多个图片';
		var mCreateRootDir = function(){
			return '<li class="diritem dir-name--Upload- parent-name-">'+
				'<div class="click-show-file item depth1 ydicon-folder ydicon-folder-open '+ (!mCurrentDir ? "on" : "") +'" data-depth="1" data-fullname="" data-name="根目录">'+
					'根目录<i class="click-show-subdir hasChild ydicon-bottom" data-fullname="" data-depth="1" data-parent="-Upload-"></i>'+
				'</div>'+
			'</li>'
		} // 静态根目录

		//更具容器等比例转换图片宽高
		var mImageSizeToAspectFit = function(w, h, padding, el){ //将图片的宽高 适应成使图片的长边能完全显示出来（不越出容器且不变形）
			if(!el) el = global;
			if(padding === undefined) padding = 50;
			var r = w / h;
			var winW = $(el).width() - padding;
			var winH = $(el).height() - padding;
			if(w > winW){
				w = winW;
				h = w / r;
			}else if(h > winH){
				h = winH;
				w = h * r;
			}
			return {height:h,width:w,windowHeight:winH,windowWidth:winW,ratio:r}
		}

		//删除url '?' 后面的参数
		var mFormatUrl = function(fileUrl){
            var pos = fileUrl.lastIndexOf("?");
            if(pos > 0){
                fileUrl = fileUrl.substr(0, pos);
            }
            return fileUrl;
		}

		//选中状态获取，返回值0代表：未选，1代表：多选-操作左键选中项，2代表：单选-操作左键选中项，3代表：单项-操作右键选中项
		var mGetFileSelectedStatus = function(){
			var rSelectedNum = $('#yd-fm-container .click-select-file.r-on').length || 0;
			var selectedNum = $('#yd-fm-container .click-select-file.on').length || 0;
			if(selectedNum == 1) return 2
			if(selectedNum > 1) return 1
			if(rSelectedNum > 0) return 3;
			return 0
		}

		//获取选中的文件数量
		var mGetSelectedFilesCount = function(){
			var st = mGetFileSelectedStatus();
			if(st == 0) return 0;
			if(st == 3) return 1;
			var selectedFile = $('#yd-fm-container .click-select-file.on');
			return selectedFile.length;
		}

		//获取选中的文件名列表
		var mGetSelectedFilesName = function(){
			if(mGetFileSelectedStatus() == 0) return null;
			var fileNameList = [];
			if(mGetFileSelectedStatus() == 3){
				fileNameList = mCurrentTarget.data('name')
			}else{
				var selectedFile = $('#yd-fm-container .click-select-file.on');
				selectedFile.each(function(){
					fileNameList.push($(this).data('name'));
				})
				fileNameList = fileNameList.join(',');
			}
			return fileNameList;
		}

		//获取选中的文件详情列表
		var mGetSelectedFilesDetail = function(){
			if(mGetFileSelectedStatus() == 0) return null;
			var tg = mCurrentTarget;
			var fileSelectedList = [];
			if(mGetFileSelectedStatus() == 3){
				fileSelectedList.push({
					fullname: tg.data('fullname'),
					name: tg.data('name'),
					ext: tg.data('extname'),
					isImage: tg.data('isimg'),
					url: mFormatUrl(tg.data('imgsrc')),
					width: tg.data('w'),
					height: tg.data('h'),
					size: tg.data('size'),
					friendSize: tg.data('fsize')
				})
			}else{
				var selectedFile = $('#yd-fm-container .click-select-file.on');
				selectedFile.each(function(){
					fileSelectedList.push({
						fullname: $(this).data('fullname'),
						name: $(this).data('name'),
						ext: $(this).data('extname'),
						isImage: $(this).data('isimg'),
						url: mFormatUrl($(this).data('imgsrc')),
						width: $(this).data('w'),
						height: $(this).data('h'),
						size: $(this).data('size'),
						n: $(this).attr('yd-n'), //用于排序的参数
						friendSize: $(this).data('fsize')
					})
				})
			}
			return fileSelectedList;
		}

		//获取选中的文件详情
		var mGetSelectedFileDetail = function(){
			if(mGetFileSelectedStatus() == 0) return null;
			var tg = mCurrentTarget;
			return fileDetail = {
				fullname: tg.data('fullname'),
				name: tg.data('name'),
				ext: tg.data('extname'),
				isImage: tg.data('isimg'),
				url: mFormatUrl(tg.data('imgsrc')),
				width: tg.data('w'),
				height: tg.data('h'),
				size: tg.data('size'),
				friendSize: tg.data('fsize')
			}
		}

		//获取基础html
		function getBasicHtml(){
			return '<div id="yd-fm-container">'+
				'<div class="yd-fm-header">'+
					'<div id="yd-fm-upload-btn" class="yd-fm-upload-btn ydicon-upload">上传文件</div>'+
					'<div class="yd-fm-upload-tip"><i class="ydicon-icon_unknown"></i>上传覆盖</div>'+
					'<div class="yd-fm-upload-switch yd-switch"><i></i><span>关闭</span></div>'+
					'<div class="yd-fm-tabbar">'+
						'<span class="s1" data-source="1">本地储存</span>'+
						'<span class="s2" data-source="2">七牛云储存</span>'+
						'<span class="s3" data-source="3">阿里云储存</span>'+
					'</div>'+
					'<div class="yd-fm-search"><input class="yd-fm-search-input" type="text" placeholder="搜索关键词" /></div>'+
					'<span class="yd-fm-set-btn ydicon-set"></span>'+
				'</div>'+
				'<div class="yd-fm-dir-container">'+
					'<div class="yd-fm-dirlist"></div>'+
				'</div>'+
				'<div class="yd-fm-file-container">'+
					'<div class="yd-fm-upload-container">'+
						'<div id="yd-fm-upload-container" style="overflow:hidden">'+
							'<ul class="yd-fm-uploadlist">'+
								'<li class="yd-fm-uploadlist-header">'+
									'<div class="yd-fm-uploadlist-name">文件名称</div><div class="yd-fm-uploadlist-operator">操作</div><div class="yd-fm-uploadlist-status">状态</div>'+
									'<div class="yd-fm-uploadlist-speed">上传速度</div><div class="yd-fm-uploadlist-per">上传进度</div><div class="yd-fm-uploadlist-size">大小</div>'+
								'</li>'+
							'</ul>'+
					 	'</div>'+
				 	'</div>'+
					'<div class="yd-fm-file-head">'+
						'<span class="s1">文件名</span>'+
						'<span>日期</span>'+
						'<span class="s3">大小</span>'+
					'</div>'+
					'<div class="yd-fm-filelist"></div>'+
				'</div>'+
				'<div class="yd-fm-filter-shade"></div>'+
				'<div class="yd-fm-filter">'+
					'<div class="yd-fm-filter-head"><i class="ydicon-icon_close-107 yd-fm-close-btn"></i></div>'+
					'<div class="yd-fm-filter-title">视图</div>'+
					'<div class="yd-fm-filter-view">'+
						'<div class="yd-fm-item s1 yd-radio" data-val="1">缩略图</div>'+
						'<div class="yd-fm-item s2 yd-radio" data-val="2">列表</div>'+
						'<div class="yd-fm-item s3 yd-radio" data-val="3">紧凑</div>'+
					'</div>'+
					'<div class="yd-fm-filter-title">排序</div>'+
					'<div class="yd-fm-filter-sort">'+
						'<div class="yd-fm-item s1 yd-radio" data-val="1">时间</div>'+
						'<div class="yd-fm-item s2 yd-radio" data-val="2">名称</div>'+
						'<div class="yd-fm-item s3 yd-radio" data-val="3">大小</div>'+
					'</div>'+
					'<div class="yd-fm-filter-title">排序顺序</div>'+
					'<div class="yd-fm-filter-sort-type">'+
						'<div class="yd-fm-item s3 yd-radio" data-val="3">降序</div>'+
						'<div class="yd-fm-item s4 yd-radio" data-val="4">升序</div>'+
					'</div>'+
					'<div class="yd-fm-filter-title">缩略图</div>'+
					'<div class="yd-fm-filter-image-threshold">'+
						'缩略图大于<span><input type="number" value="800" maxlength="4" min="0" max="9999" />KB</span>不显示'+
					'</div>'+
					'<div class="yd-fm-filter-image-cache">'+
						'缩略图缓存<div class="yd-fm-cache-switch yd-switch"><i></i><span>关闭</span></div>'+
					'</div>'+
					'<div class="yd-fm-filter-title">说明</div>'+
					'<div class="yd-fm-filter-help">'+
						'<div class="yd-fm-item">1、按住Ctrl，选择多个图片</div>'+
						'<div class="yd-fm-item">2、按住Shift，选择起始范围图片</div>'+
						'<div class="yd-fm-item">3、Enter 选择并返回</div>'+
						'<div class="yd-fm-item">4、Ctrl+A 选择所有文件</div>'+
					'</div>'+
					'<div class="yd-fm-filter-title"></div>'+
				'</div>'+
			'</div>'
		}

		//获取当前存在的dlg 最大zIndex的值
		function getMaxZIndex(){
			var dialogs = dialog.list;
			var zIndex = 0;
			var currentIndex = 0;
			for(var key in dialogs){
				currentIndex = dialogs[key].zIndex;
				if(currentIndex > zIndex){
					zIndex = currentIndex;
				}
			}
			if(zIndex < 20000) zIndex = 20000;
			return ++zIndex;
		}

		// 入口 显示
		function popup(isModal){
			$('input').blur(); //为了防止Enter选中文件后再次弹出dlg
			fm = this;
			if(mDlg && mDlg.open) return this;
			mDlg = dialog({
				title: '文件管理器',
				id: 'yd-fm-file-manager',
				padding: 0,
				zIndex: getMaxZIndex(),  //ckeditor的属性弹框为：10010
				content: getBasicHtml(),
				onshow:function(){
					init();
				},
				cancel: function(){
					destroyDialog();
				},
				cancelDisplay: false //不显示取消按钮
			})
			if(isModal){
				mDlg.showModal();
			}else{
				mDlg.show();
			}
			return this;
		}

		// 入口(非dlg) 页面中显示
		function widget(id){
			fm = this;
			$('#'+id).html(getBasicHtml());
			init();
			var windowHeight = $(document).height();
			$('#yd-fm-container').attr('ishtml', '').css({
				width: '100%',
				height: windowHeight+'px'
			})
			$('.yd-fm-dir-container,.yd-fm-file-container').css({
				height: windowHeight-47+'px'
			})
			$('.yd-fm-filelist li').css('width', '190px');
			return this;
		}

		//关闭 移除
		function destroyDialog(){
			if(!mDlg) return;
			yd.log('destroyDialog');
			$('.ui-dialog-title').unbind('mousedown', fmOptionRemove);
			$(document).unbind('mouseup', fmOptionRemove);
			$(document).unbind('keyup', fmHotKeyUp);
			$(document).unbind('keydown', fmHotKeyDown);
			fileRightOption.remove();
			dirRightOption.remove();
			mDlg.close().remove();
			mDlg = '';
			return this;
		}

		//初始化
		function init(){
			yd.log('init');
			//注释下行代码会造成七牛选项卡无法显示，注释也有问题。还是删除$('.yd-fm-tabbar').remove();
			if(!mConfig.dataSource) mConfig.dataSource = 1;
			setDefaultDir();
			
			//绑定基础事件
			$('.ui-dialog-title').mousedown(fmOptionRemove)
			$(document).mouseup(fmOptionRemove);
			$(document).keyup(fmHotKeyUp);
			$(document).keydown(fmHotKeyDown);
			//固定储存空间，隐藏tab
			if(mConfig.dataSource){
				mDataSource = mConfig.dataSource;
				mCurrentDir = mGetCurrentDir();
				if(mDataSource>1){ //只有本地文件管理器才显示存储类型选择
					$('.yd-fm-tabbar').remove();
				}
			}
			//阻止默认右键菜单
			$('#yd-fm-container').contextmenu(function(){
				if($(":focus").hasClass('yd-fm-search-input')) return true; //焦点在搜索框则允许右键
				return false;
			})
			//上传覆盖提示
			var uploadTip = $('.yd-fm-upload-tip');
			uploadTip.click(function(){
				mChildDlg = dialog({
					align: 'bottom',
					content: '开启上传覆盖后，允许上传同名文件，<br/>关闭上传覆盖无法上传同名文件。<br/>注：阿里云存储仅支持覆盖上传',
					quickClose: true,
				}).show($(this)[0]);
			})
			//初始化上传覆盖switch
			var uploadSwitch = $('.yd-fm-upload-switch');
			var setSwitch = function(isAli){
				uploadSwitch.removeClass('desable')
				if(isAli){
					uploadSwitch.addClass('on desable').find('span').text('开启');
				}else{
					if(mUploadIsOverWrite == 1){
						uploadSwitch.addClass('on').find('span').text('开启');
					}else{
						uploadSwitch.removeClass('on').find('span').text('关闭');
					}
				}
				uploadSwitch.unbind().click(function(){
					if($(this).hasClass('desable')) return;
					if($(this).hasClass('on')){
						$(this).removeClass('on').find('span').text('关闭');
						mUploadIsOverWrite = 0;
						setStorage('YDUploadIsOverWrite', '0');
					}else{
						$(this).addClass('on').find('span').text('开启');
						mUploadIsOverWrite = 1;
						setStorage('YDUploadIsOverWrite', '1');
					}
				})
			}
			//初始化缩略图缓存switch
			var cacheSwitch = $('.yd-fm-cache-switch')
			if(mIsCache == 1) cacheSwitch.addClass('on').find('span').text('开启');
			cacheSwitch.unbind().click(function(){
				if($(this).hasClass('on')){
					$(this).removeClass('on').find('span').text('关闭');
					mIsCache = 0;
					setStorage('YDImageIsCache', '0');
				}else{
					$(this).addClass('on').find('span').text('开启');
					mIsCache = 1;
					setStorage('YDImageIsCache', '1');
				}
			})
			//初始化上传工具
			var initUpload = function(ds){
				//初始化本地上传工具
				if(ds == 1){
					loadLocalUpload();
					setSwitch();
				}
				//初始化七牛云上传插件
				if(ds == 2){
					loadQiniuUpload();
					setSwitch();
				}
				//初始化阿里云上传插件
				if(ds == 3){
					loadAliUpload();
					setSwitch(1);
				}
			}
			initUpload(mDataSource);
			//切换栏 绑定切换栏事件
			$('.yd-fm-tabbar span.s'+mDataSource).addClass('on');
			$('.yd-fm-tabbar span').click(function(){
				var that = $(this);
				var source = $(this).data('source');
				if(source == mDataSource) return;
				$('.yd-fm-search-input').val('');
				LoadBox('加载中');
				getDir({DataSource: source}, null, null, function(res){
					CloseLoadBox();
					/*--去除上传按钮身上的事件--*/
					var btn = $('#yd-fm-upload-btn').clone()
					$('#yd-fm-upload-btn').remove()
					$('.yd-fm-header').prepend(btn)
					/*----*/
					setStorage('YDDataSource', source);
					mDataSource = source;
					mCurrentDir = mGetCurrentDir();
					initUpload(source);
					$('.yd-fm-tabbar span').removeClass('on');
					that.addClass('on');
					$('.yd-fm-dirlist').html(mCreateRootDir());
					showDir(res.data, 2, '-Upload-');
					getDefaultDir();
					getFile();
					return false;
				})
			})
			//搜索 绑定搜索事件
			var seachInput = $('.yd-fm-search input');
			seachInput.val("");
			var seachInputTimer;
			seachInput.unbind().bind('input propertychange',function(e){
				var value = $(this).val();
				var files = $('.yd-fm-fileitem');
				files.removeClass('on ydicon-gouxuan');
				clearTimeout(seachInputTimer);
				if(!value || value == ''){
					files.show();
					return;
				}
				seachInputTimer = setTimeout(function(){
					files.hide();
					files.each(function(){
						var itemName = $(this).data('name');
						if(itemName.indexOf(value) != -1){
							$(this).show();
						}
					})
					setImgSrc();
				}, 200)
			})
			//右侧栏 绑定右侧栏事件
			$('.yd-fm-set-btn').click(function(){
				$(this).addClass('on');
				$('.yd-fm-filter').animate({right: '0px'});
				$('.yd-fm-filter-shade').show();
			})
			$('.yd-fm-filter .yd-fm-close-btn,.yd-fm-filter-shade').mouseup(function(){
				$('.yd-fm-set-btn').removeClass('on');
				$('.yd-fm-filter').animate({right: '-310px'});
				$('.yd-fm-filter-shade').hide();
			})
			//视图样式切换
			$('.yd-fm-filter-view .yd-fm-item.s'+mViewType).addClass('on'); //初始化选中项
			$('.yd-fm-file-container').addClass('style'+mViewType); //初始化文件样式
			var viewChangeItem = $('.yd-fm-filter-view .yd-fm-item');
			viewChangeItem.click(function(){
				viewChangeItem.removeClass('on');
				$(this).addClass('on')
				$('.yd-fm-file-container').removeClass('style1 style2 style3');
				$('.yd-fm-file-container').addClass('style'+$(this).data('val'));
				setStorage('YDCurrentViewType', $(this).data('val'));
				mViewType = $(this).data('val');
				if($(this).hasClass('s1')){
					setImgSrc(); //视图为缩略图时触发懒加载
				}
			})
			//排序条件
			var sortParams = {
				SortField: mSortField,
				SortOrder: mSortOrder,
			}
			//排序方式
			$('.yd-fm-filter-sort .yd-fm-item.s'+mSortField).addClass('on'); //初始化选中项
			var sort = $('.yd-fm-filter-sort .yd-fm-item');
			sort.click(function(e){
				sort.removeClass('on');
				$(this).addClass('on');
				mSortField = sortParams.SortField = $(this).data('val');
				setStorage('YDCurrentSortField', $(this).data('val'));
				getFile(sortParams);
			})
			//升序 降序
			$('.yd-fm-filter-sort-type .yd-fm-item.s'+mSortOrder).addClass('on'); //初始化选中项
			var sortType = $('.yd-fm-filter-sort-type .yd-fm-item');
			sortType.click(function(){
				sortType.removeClass('on');
				$(this).addClass('on');
				mSortOrder = sortParams.SortOrder = $(this).data('val');
				setStorage('YDCurrentSortOrder', $(this).data('val'));
				getFile(sortParams);
			})
			//是否显示大图
			var showImg = $('.yd-fm-filter-image-threshold input');
			showImg.val(mImageShowThreshold);
			showImg.focus(function(){
				$(this).parent().addClass('hover')
			})
			showImg.blur(function(){
				var val = $(this).val();
				if(val === '') val = 0;
				$(this).parent().removeClass('hover')
				if(mImageShowThreshold != val) getFile();
				mImageShowThreshold = val;
				setStorage('YDImageShowThreshold', val);
			})
			//点击文件空白处所有文件取消选中， 点击文件列表任意位置关闭上传状态框
			$('#yd-fm-container').click(function(e){
				if(mHasUploadError) $('.yd-fm-upload-container').hide().find('.yd-fm-uploadlist-body').remove();
				if($(e.target).hasClass('yd-fm-file-container') || $(e.target).hasClass('yd-fm-filelist')){
					mCurrentTarget = null;
					$('.yd-fm-fileitem').removeClass('on ydicon-gouxuan');
				};
			})
			fileRightOption.init();//初始化文件右键菜单
			dirRightOption.init();//初始化文件夹右键菜单
		 	//初始化根目录
			$('.yd-fm-dirlist').html(mCreateRootDir());
			//开始请求数据
			getDir(null, 2, '-Upload-', null, function(){
				getDefaultDir();
			});
			getFile();
			return 'isOk';
		}

		//缓存操作
		function setStorage(key, value){
			localStorage.setItem(key, value);
		}
		function getStorage(key){
			return localStorage.getItem(key)
		}

		/* -------------------------------------文件夹操作 开始------------------------------------- */
		//获取API公共参数
		function getPublicParams(params){
			var token = mConfig.Token;
			if(token){
					params.Token = token;
					params.Timestamp = Math.round(new Date().getTime()/1000);
					params.NonceStr = getNonceStr();
			}
			return params;
		}
		
	function getNonceStr() {
			var len = 16;
			/****默认去掉了容易混淆的字符oOLl,9gq,Vv,Uu,I1****/
			var chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678';
			var maxPos = chars.length;
			var pwd = '';
			for (var i = 0; i < len; i++) {
				pwd += chars.charAt(Math.floor(Math.random() * maxPos));
			}
			var str = pwd + (new Date().getTime());
			return str;
		}
		
		function getUrl(action, module){
			if(!module) module = "Resource";
			var baseUrl = mConfig.Group;
			var url = baseUrl+"/"+module+"/"+action;
			return url;
		}
		
		//获取本地文件上传接口地址
		function getUploadUrl(){
			var url = getUrl("upload", "public");
			//可以自定义外部上传接口（便于移植到其他系统）
			if(mConfig.UploadUrl){ 
				url = mConfig.UploadUrl;
			}
			return url;
		}

		//默认文件夹 获取并选中默认文件夹
		function getDefaultDir(){
			var url = getUrl("getDefaultDir");
			var params = {CurrentDir: mCurrentDir, DataSource: mDataSource};
			params = getPublicParams(params);
			$.post(url, params, function(res){
				yd.log('getDefaultDir', res, {CurrentDir: mCurrentDir, DataSource: mDataSource});
				if(res.status == 1 && res.data){
					if(res.data.length >= 1){
						for(var i = 0; i < res.data.length; i++){
							var item = res.data[i];
							var parent = item.Parent.replace(/\//g,'-');
							parent = parent.substr(1);
							showDir([item], item.Depth, parent);
							if(item.HasChildren || i === 0) $('#yd-fm-container .dir-name-' + parent).children(":first").addClass('ydicon-folder-open').children(":first").removeClass('ydicon-right').addClass('ydicon-bottom hasChild')
						}
					}
					$('.yd-fm-dir-container .click-show-file').each(function(index){
						if($(this).hasClass('on')){
							$('.yd-fm-dir-container').scrollTop(44*index-176);
						 	return false;
						}
					})
				}else{
					ErrorBox(res.info);
				}
			}, 'JSON');
			return true;
		}

		//获取文件夹数据 params:参数 depth:层级 parentName:父级 cb:回调 onLoad:渲染完毕回调
		function getDir(params, depth, parentName, cb, onLoad){
			if(!depth) depth = 1;
			if(!parentName) parentName = '';
			if(!params){
				params = {DataSource: mDataSource}
			}else{
				if(!params.DataSource) params.DataSource = mDataSource;
			}
			var url = getUrl("getDir");
			params = getPublicParams(params);
			$.post(url, params, function(res){
				yd.log('getDir', res, params);
				if(res.status == 1){
					if(cb){
						if(!cb(res)) return; //如果回调返回的时false那么阻止接下来的数据渲染，后续交给回调
					}
					showDir(res.data, depth, parentName);
					if(onLoad) onLoad();
				}else{
					CloseLoadBox();
					ErrorBox(res.info);
				}
			}, 'JSON');
		}

		//渲染文件夹 子文件夹
		function showDir(dirs, depth, parentName){
			for (var i = 0; i < dirs.length; i++) {
				var item = dirs[i];
				var name = item.FullDirName.replace(/\//g,'-');
				if(name[0] == '.') name = name.substr(1);
				if(name[0] == '.') name = name.substr(1); //可能存在../upload，所以需要替换2次
				var html = '<li class="diritem dir-name-'+name+' parent-name-'+ parentName +'">';
				html += '<div class="click-show-file item '+ (mCurrentDir == item.FullDirName ? "on ydicon-folder-open" : "") +' ydicon-folder depth'+ depth +'" data-depth="'+ depth +'" data-fullname="'+ item.FullDirName +'" data-name="'+ item.DirName +'" data-parent="'+ name +'">';
				html += item.DirName;
				if(item.HasChildren){
					html += '<i class="click-show-subdir ydicon-right" data-fullname="'+ item.FullDirName +'" data-depth="'+ depth +'" data-parent="'+ name +'"></i>'
				}
				html += '</div></li>';
				if(!parentName){
					$('.yd-fm-dirlist').append(html);
				}else{
					$('#yd-fm-container .dir-name-' + parentName).append(html);
				}
			}
			bindDirClick();
		}

		//监听文件夹 和 展开文件夹箭头 点击文件夹
		var bindDirClick = (function(){
			var last = ''; //这个参数 是为了记录上一个打开的文件夹，点击当前文件夹时，调整它的图标为关闭图标.
			var fn = function(){
				var bindShowFileClick = function(){
					mCurrentTarget = $(this);
					var that = $(this);
					$('.yd-fm-dir-container .click-show-file').removeClass('on');
					$('.yd-fm-search-input').val('');
					last && last.removeClass('ydicon-folder-open');
					that.addClass('on');
					last = that;
					mCurrentDir = that.data('fullname');
					if(mDataSource == 1){
						mCurrentDir1 = mCurrentDir;
						setStorage('YDCurrentDir1', mCurrentDir);
					}
					if(mDataSource == 2){
						mCurrentDir2 = mCurrentDir;
						setStorage('YDCurrentDir2', mCurrentDir);
					}
					if(mDataSource == 3){
						mCurrentDir3 = mCurrentDir;
						setStorage('YDCurrentDir3', mCurrentDir);
					}
					checkOpenDir();
					getFile(null, function(res){
						if(res.info == 0){
							that.find('.click-show-subdir').remove(); //如果没有子文件夹则删除展开文件夹的箭头
						}
					});
				}
				var bindShowSubdirClick = function(event){
					event.stopPropagation();
					var that = $(this);
					var child = $('.yd-fm-dir-container .parent-name-' + that.data('parent'));
					if(that.hasClass('hasChild')) {
						if(that.hasClass('ydicon-right')){
							that.removeClass('ydicon-right').addClass('ydicon-bottom');
							child.show();
						}else{
							that.removeClass('ydicon-bottom').addClass('ydicon-right');
							child.hide();
						}
						checkOpenDir();
						return;
					};
					var depth = parseInt(that.data('depth'))+1;
					getDir({Dir: that.data('fullname')}, depth, that.data('parent'), function(res){
						if(that.hasClass('ydicon-right')){
							that.removeClass('ydicon-right').addClass('ydicon-bottom');
							child.show();
						}else{
							that.removeClass('ydicon-bottom').addClass('ydicon-right');
							child.hide();
						}
						that.addClass('hasChild');
						//如果没有子文件夹则删除展开文件夹的箭头
						if(res.data.length < 1){
							that.remove()
						}else{
							checkOpenDir();
						}
						return true;
					});
				}
				var checkOpenDir = function(){
					var dirs = $('.yd-fm-dir-container .click-show-file');
					dirs.each(function(){
						$(this).removeClass('ydicon-folder-open');
						if($(this).hasClass('on')){
							$(this).addClass('ydicon-folder-open');
						}
						if($(this).find('.click-show-subdir').hasClass('ydicon-bottom')){
							$(this).addClass('ydicon-folder-open');
						}
					})
				}
				$('.yd-fm-dir-container .click-show-file').unbind().click(bindShowFileClick);
				$('.yd-fm-dir-container .click-show-subdir').unbind().click(bindShowSubdirClick);
				//监听文件夹鼠标右键点击事件
				$('.yd-fm-dirlist').unbind().contextmenu(function(e){
					mCurrentTarget = $(e.target);
					if(!mCurrentTarget.data('name')) return true;
					//生成文件夹右键菜单
					$('.yd-fm-dir-container .item').removeClass('r-on');
					mCurrentTarget.addClass('r-on');
					dirRightOption.show(e.pageX, e.pageY);
					return false;
				})
			}
			return fn;
		})();

		//绑定文件夹右键选项点击事件
		var dirRightOption = (function(){
			var init = function(){
				var html = '<div class="yd-fm-right-option yd-fm-dir-right-option" style="z-index:'+(mDlg?mDlg.zIndex+50:20049)+'">';
				html += '<div class="yd-fm-right-option-item ydicon-newfolder" create-dir>创建子文件夹</div>';
				html += '<div class="yd-fm-right-option-item ydicon-rename" rename-dir>重命名</div>';
				html += '<div class="yd-fm-right-option-item ydicon-delete-full" delete-dir>删除</div>';
				html += '<div class="yd-fm-right-option-item ydicon-stat" detaile-dir>属性</div>';
				html += '</div>';
				$('body').append(html);
				$('.yd-fm-right-option-item[create-dir]').click(createDir);
				$('.yd-fm-right-option-item[rename-dir]').click(changeDirName);
				$('.yd-fm-right-option-item[delete-dir]').click(deleteDir);
				$('.yd-fm-right-option-item[detaile-dir]').click(statDir);
			}
			var show = function(x, y){
				var dir = $('.yd-fm-dir-right-option');
				var dirHeight = dir.height();
				//当鼠标点击位置小于菜单高度时则菜单生成位置在鼠标的右上角，否则在右下角
				var windowHeightHalf = $(document).height() - dirHeight - 40;
				if(y > windowHeightHalf) y -= dirHeight;
				dir.css({ left: x + 'px', top: y + 'px' }).show();
			}
			var remove = function(){
				$('.yd-fm-dir-right-option').remove();
			}
			return {
				init: init,
				show: show,
				remove: remove
			}
		})();

		//创建文件夹
		function createDir(){
			var tg = mCurrentTarget;
			var fullDirName = tg.data('fullname');
			var depth = parseInt(tg.data('depth'));
			var parent = tg.data('parent');
			mChildDlg = dialog({
				title: '创建子文件夹',
				id: mChildDialogID,
				padding: 0,
				content: '<input id="yd-fm-createDir" class="yd-fm-textinput" placeholder="请输入新的文件夹名" autofocus="autofocus"/>',
				onshow:function(){
				},
				ok: function () {
					var newDirName = $('#yd-fm-createDir').val();
					var url = getUrl("createDir");
					var params = {
						Dir: fullDirName+newDirName,
						DirName: newDirName,
						DataSource: mDataSource,
					};
					params = getPublicParams(params);
					$.post(url, params, function(res){
						yd.log('createDir', res, params);
						if(res.status == 1){
							//从根目录下创建文件夹，会存在问题，没有./Upload/前缀
							var baseDir = (''==fullDirName) ? res.data.RootDir : '';
							SucceedBox(res.info);
							mChildDlg.close().remove();
							showDir([{
								DirName: params.DirName,
								FullDirName: baseDir+params.Dir+'/',
								HasChildren: false,
							}], depth + 1, parent)
							if(tg.find('.click-show-subdir').length < 1){
								tg.append('<i class="click-show-subdir hasChild ydicon-bottom" data-fullname="'+ baseDir+fullDirName +'" data-depth="'+ depth +'" data-parent="'+ baseDir+parent +'"></i>')
								bindDirClick();
							}
						}else{
							ErrorBox(res.info);
						}
					}, 'JSON');
					return false;
				},
				cancel:function(){
					this.close().remove();
				},
				okValue: '确定',
				cancelValue: '关闭',
				cancel: true
			}).show();
		}

		//修改文件夹名 文件夹重命名
		function changeDirName(){
			var tg = mCurrentTarget;
			var oldDir = tg.data('fullname');
			var oldName = tg.data('name');
			mChildDlg = dialog({
				title: '修改文件夹名称',
				id: mChildDialogID,
				quickClose: true,
				padding: 0,
				content: '<input id="yd-fm-changeDirName" class="yd-fm-textinput" placeholder="请输入新的文件夹名" value="'+oldName+'" autofocus="autofocus"/>',
				onshow:function(){
				},
				ok: function () {
					var newDirName = $('#yd-fm-changeDirName').val();
					var url = getUrl("changeDirName");
					var reg = new RegExp('(.*)'+oldName); //构造规则，用于替换oldDir旧文件夹匹配到最后一级的文件夹名称
					var params = {
						OldDir: oldDir,
						NewDir: oldDir.replace(reg, '$1'+newDirName),
						NewDirName: newDirName,
						DataSource: mDataSource
					}
					LoadBox();
					params = getPublicParams(params);
					$.post(url, params, function(res){
						CloseLoadBox();
						yd.log('changeDirName', res, params);
						if(res.status == 1){
							mChildDlg.close().remove();
							SucceedBox(res.info);
							if(mCurrentDir.indexOf(oldDir) == 0){
								mCurrentDir = mCurrentDir.replace(oldDir, params.NewDir);
								setStorage('YDCurrentDir'+mDataSource, mCurrentDir);
							}
							$('.yd-fm-dirlist').html(mCreateRootDir());
							getDir(null, 2, '-Upload-', null, function(){
								getDefaultDir();
								getFile();
							});
						}else{
							ErrorBox(res.info)
						}
					}, 'JSON');
					return false;
				},
				cancel:function(){
					this.close().remove();
				},
				okValue: '确定',
				cancelValue: '关闭',
				cancel: true
			}).show();
		}

		//删除文件夹
		function deleteDir(){
			var tg = mCurrentTarget;
			var fullDirName = tg.data('fullname');
			var name = tg.data('name');
			mChildDlg = ConfirmBox('确定删除【'+name+'】文件夹？', function(){
				var url = getUrl("deleteDir");
				var params = { Dir: fullDirName, DataSource: mDataSource };
				params = getPublicParams(params);
				$.post(url, params, function(res){
					yd.log('deleteDir', res, params)
					if(res.status == 1){
						SucceedBox(res.info)
						tg.parent().remove();
						var top = tg.data('parent').replace(name + '-', '');
						if($('.dir-name-'+top).find('.diritem').length < 1){ //判断目录没有子文件夹则删除小箭头
							$('.dir-name-'+top).find('.click-show-subdir').remove();
						}
						if(mCurrentDir == fullDirName){ //如果删除的是当前目录，则重置当前目录的值
							mCurrentDir = '';
							setStorage('YDCurrentDir', "");
							getFile();
						}
					}else{
						ErrorBox(res.info)
					}
				}, 'JSON');
			}, null, {id: mChildDialogID})
		}

		//文件夹属性
		function statDir(){
			var tg = mCurrentTarget;
			var fullDirName = tg.data('fullname');
			var name = tg.data('name');
			var html = '<div class="yd-fm-dir-stat">';
			html += '<div><span class="yd-fm-dir-stat-label">文件个数：</span><span class="s4"></span></div>';
			html += '<div class="yd-fm-wrap"><span class="yd-fm-dir-stat-label">大小：</span><span class="s1"></span></div>';
			html += '<div><span class="yd-fm-dir-stat-label">创建时间：</span><span class="s2"></span></div>';
			html += '<div><span class="yd-fm-dir-stat-label">修改时间：</span><span class="s3"></span></div>';
			html += '</div>';
			LoadBox('加载中')
			var url = getUrl("statDir");
			var params = {Dir: fullDirName, DataSource: mDataSource};
			params = getPublicParams(params);
			$.post(url, params, function(res){
				CloseLoadBox();
				yd.log('statDir', res, {Dir: fullDirName, DataSource: mDataSource});
				if(res.status == 1){
					mChildDlg = dialog({
						title: name+' 属性',
						id: mChildDialogID,
						padding: 0,
						content: html,
						quickClose: true,
						onshow:function(){
							var d = res.data;
							var wrap = $('.yd-fm-dir-stat');
							var getTarget = function(e){
								return wrap.find(e);
							}
							var size = d.TotalSizeReadable + '（' + d.TotalSize + ' 字节）';
							getTarget('.s1').html(d.TotalSizeReadable + '（' + d.TotalSize + ' 字节）').attr('title', d.TotalSizeReadable + '（' + d.TotalSize + ' 字节）')
							getTarget('.s2').html(d.CreateTime)
							getTarget('.s4').html(d.FileCount)
							if(d.ModifyTime){
								getTarget('.s3').html(d.ModifyTime);
							}else{
								getTarget('.s3').parent().remove();
							}
						},
						cancel:function(){
							this.close().remove();
						},
						cancel: true,
						cancelDisplay: false //不显示取消按钮
					}).show();
				}else{
					ErrorBox(res.info)
				}
			}, 'JSON');
		}

		/* -------------------------------------文件夹操作 结束------------------------------------- */


		/* -------------------------------------文件操作 开始------------------------------------- */

		//获取文件数据
		function getFile(params, cb){
			if(!params){
				params = {Dir: mCurrentDir, DataSource: mDataSource, SortField: mSortField, SortOrder: mSortOrder}
			}else{
				params.Dir = mCurrentDir;
				params.DataSource = mDataSource;
			}
			var url = getUrl("getFile");
			params = getPublicParams(params);
			$.post(url, params, function(res){
				yd.log('getFile', res, params);
				if(res.status == 1){
					showFile(res.data);
					if(cb) cb(res);
				}else{
					ErrorBox(res.info)
				}
			}, 'JSON');
		}

		//渲染文件列表
		function showFile(files){
			$('.yd-fm-filelist').html('');
			if(!files || files.length < 1){
				var html = '<div class="yd-fm-dir-empty ydicon-folder"><span>文件夹是空的</span></div>';
				$('.yd-fm-filelist').append(html);
				return;
			}
			for (var i = 0; i < files.length; i++) {
				var item = files[i];
				// 关闭缓存
				if(mIsCache == 0) item.FileUrl += '?' + new Date().getTime();
				//筛选文件类型
				var filter = mConfig.filter;
				if(filter == 1){
					if(!item.IsImage) continue;
				}else if(filter){
					if(item.FileExt.indexOf(filter) == -1) continue;
				}
				var wh = (item.IsImage && item.Width > 0) ? item.Width + "×" + item.Height : '';
				var size = item.FriendFileSize.replace(' ', '');
				var html = '<li title='+ item.FileName + "&#10;" + size + "&nbsp;&nbsp;" + wh +'>';
				html += '<div class="yd-fm-fileitem click-select-file" data-fullname="'+item.FullFileName
				+'" data-name="'+ item.FileName
				+'" data-imgsrc="'+ item.FileUrl
				+'" data-extname="'+ item.FileExt
				+'" data-isimg="'+ item.IsImage
				+'" data-w="'+ item.Width
				+'" data-h="'+ item.Height
				+'" data-size="'+ item.FileSize
				+'" data-fsize="'+ item.FriendFileSize
				+'" data-index="'+ i
				+'">'
				if(item.IsImage == 1){
					html += '<div class="yd-fm-file-img"><img data-src="'+ item.FileUrl +'" data-fsize="'+item.FileSize+'"/></div>';
					if(item.FileSize / 1024 > mImageShowThreshold) html += '<div class="yd-fm-file-threshold">图片过大<br/>不自动显示缩略图<span>显示</span></div>'
				}else{
					html += '<div class="yd-fm-file-ext">'+ item.FileExt +'</div>';
				}
				html += '<div class="yd-fm-wrap">'
				html += '<div class="yd-fm-file-icon"><img src="'+ item.FileIcon +'" /></div>';
				html += '<div class="yd-fm-file-name">'+ item.FileName +'</div>';
				html += '<div class="yd-fm-file-time">'+ item.FileTime +'</div>';
				html += '<div class="yd-fm-file-size">'+ item.FriendFileSize +'</div>';
				html += '</div></div></li>';
				$('.yd-fm-filelist').append(html);
			}
			bindFileEvent();
		}

		//渲染后绑定文件事件
		function bindFileEvent(){
			var fileItems = $('#yd-fm-container .click-select-file');
			fileItems.click(fileItems, selectFile); 
			fileItems.dblclick(fileItems, function(e){
				if(e.ctrlKey){
					return false;
				}
				confirmedFile();
			});
			fileItems.contextmenu(fileItems, showFileOption);
			//设置七牛云文件鼠标移上去的title属性
			if(mDataSource == 2){
				var oldTitle = '';
				fileItems.mouseenter(function(){
					if($(this).find('.yd-fm-file-img img').length < 1 || !$(this).find('.yd-fm-file-img img')[0].naturalWidth) return;
					imgWidth = parseInt($(this).find('.yd-fm-file-img img')[0].naturalWidth);
					imgHeight = parseInt($(this).find('.yd-fm-file-img img')[0].naturalHeight);
					oldTitle = $(this).parent().attr('title');
					var newTitle = oldTitle.split('0×0')[0];
					var wh = imgWidth > 0 ? imgWidth + '×' + imgHeight : '';
					newTitle += wh;
					$(this).parent().attr('title', newTitle);
				});
				fileItems.mouseleave(function(){
					if($(this).find('.yd-fm-file-img img').length < 1 || !$(this).find('.yd-fm-file-img img')[0].naturalWidth) return;
					$(this).parent().attr('title', oldTitle);
				})
			}
			imgLazyLoad();
			setImgSrc();
			checkItemImg();
		}

		//查看过大图片
		function checkItemImg(){
			var checkBtns = $('#yd-fm-container .click-select-file .yd-fm-file-threshold span');
			checkBtns.click(function(){
				var img = $(this).parent().parent().find('.yd-fm-file-img img');
				img.attr('src',img.data('src'));
				$(this).parent().remove();
				return false;
			})
		}

		//图片懒加载 滚动触发
		function imgLazyLoad(){
			var filesWrap = $('.yd-fm-file-container.style1');
			var lazyTimer;
			filesWrap.unbind().scroll(function(){
				clearTimeout(lazyTimer);
				lazyTimer = setTimeout(function(){
					setImgSrc();
				},200)
			})
		}
		//图片懒加载 替换src
		function setImgSrc(){
			var wrapHeight = $('.yd-fm-file-container.style1').height();
			var files = $('.yd-fm-fileitem img');
			files.each(function(){
				var top = parseInt($(this).offset().top) - 200;
				var size = parseInt($(this).data('fsize')) / 1024;
				if(size < mImageShowThreshold && top < wrapHeight && !$(this).attr('src') && !$(this).parent().is(':hidden')){
					$(this).attr('src', $(this).data('src'));
				}
			})
		}

		//鼠标选中文件
		var selectFile = (function(){
			var selectFileIndex = 1; //选择排序
			var beforeSelected = false; //首选项
			function select(e){
				mCurrentTarget = $(this);
				if(e.shiftKey){
					//shift连续多选
					if($('.yd-fm-fileitem.on').length < 1){
						beforeSelected = $(this);
					}
					e.data.removeClass('on ydicon-gouxuan');
					var cindex = parseInt($(this).data('index'));
					var oindex = parseInt(beforeSelected.data('index'));
					if(cindex > oindex){
						for(var i = oindex; i <= cindex; i++){
							var item = $('.yd-fm-fileitem[data-index='+i+']');
							item.addClass('on ydicon-gouxuan');
							item.attr('yd-n', selectFileIndex);
						}
					}else{
						for(var i = cindex; i <= oindex; i++){
							var item = $('.yd-fm-fileitem[data-index='+i+']');
							item.addClass('on ydicon-gouxuan');
							item.attr('yd-n', selectFileIndex);
						}
					}
					beforeSelected = $(this);
					e.preventDefault();
					return false;
				}
				$(this).attr('yd-n', selectFileIndex++);
				beforeSelected = $(this);
				if(e.ctrlKey){
					// ctrl单个多选
					if($(this).hasClass('on')){
						$(this).removeClass('on ydicon-gouxuan');
						$(this).removeAttr('yd-n');
					}else{
						$(this).addClass('on ydicon-gouxuan');
					}
					e.preventDefault();
					return false;
				}else{
					//单选
					e.data.removeClass('on ydicon-gouxuan');
				}
				$(this).addClass('on ydicon-gouxuan');
			}
			return select;
		})();

		//文件右键菜单方法集
		var fileRightOption = (function(){
			// class说明 yd-fm-file-right-onlyone-option：代表只能单选出现 ； yd-fm-file-right-img-option：代表只能图片出现
			function init(){
				var html = '<div class="yd-fm-right-option yd-fm-file-right-option" style="z-index:'+(mDlg?mDlg.zIndex+50:20049)+'">';
				if(fm.selectActionFunction) html += '<div class="yd-fm-right-option-item ydicon-select-file" select-file>选择 Enter</div>';
				html += '<div class="yd-fm-right-option-item ydicon-look" view-file>查看</div>';
				html += '<div class="yd-fm-right-option-item yd-fm-file-right-onlyone-option ydicon-copy" copy-url>复制URL</div>';
				html += '<div class="yd-fm-file-right-img-option">';
					html += '<div class="yd-fm-right-option-item ydicon-edit" edit-file-size>改变尺寸</div>';
					html += '<div class="yd-fm-right-option-item yd-fm-file-right-onlyone-option ydicon-crop" image-crop>图片裁剪</div>';
					html += '<div class="yd-fm-right-option-item ydicon-slim" slim-image>图片瘦身</div>';
					html += '<div class="yd-fm-right-option-item ydicon-water" image-water>图片加水印</div>';
				html += '</div>';
				html += '<div class="yd-fm-right-option-item yd-fm-file-right-onlyone-option ydicon-rename" change-file-name>重命名</div>';
				html += '<div class="yd-fm-right-option-item ydicon-copy" copy-file>创建副本</div>';
				html += '<div class="yd-fm-right-option-item ydicon-move" move-file>移动</div>';
				html += '<div class="yd-fm-right-option-item ydicon-delete-full" delete-file>删除 Del</div>';
				html += '</div>';
				$('body').append(html);
				//为生成的文件右键菜单绑定点击事件
				$('.yd-fm-right-option-item[select-file]').click(confirmedFile);
				$('.yd-fm-right-option-item[view-file]').click(fileView);
				$('.yd-fm-right-option-item[copy-url]').click(copyUrl);
				$('.yd-fm-right-option-item[edit-file-size]').click(fileResize);
				$('.yd-fm-right-option-item[slim-image]').click(slimImage);
				$('.yd-fm-right-option-item[image-water]').click(imageWater);
				$('.yd-fm-right-option-item[image-crop]').click(imageCrop);
				$('.yd-fm-right-option-item[change-file-name]').click(changeFileName);
				$('.yd-fm-right-option-item[copy-file]').click(copyFile);
				$('.yd-fm-right-option-item[move-file]').click(moveFile);
				$('.yd-fm-right-option-item[delete-file]').click(deleteFile);
			}
			function show(x, y){
				var name = mCurrentTarget.data('name');
				var st = mGetFileSelectedStatus();
				var allIsImg = mCurrentTarget.data('isimg') == 1 ? true : false;
				var length = mGetSelectedFilesCount();
				if(length > 0 && allIsImg){
					var fileDetailList = mGetSelectedFilesDetail();
					for(var i = 0; i < length; i++){
						var item = fileDetailList[i];
						if(!item.isImage){
							allIsImg = false;
							break;
						}
					}
				}
				// 多选全是图片则显示图片相关操作项
				if(allIsImg && mDataSource == 1){
					$('.yd-fm-file-right-img-option').show();
				}
				// 单选显示重命名
				if(st != 1){
					$('.yd-fm-file-right-onlyone-option').show();
				}
				//当鼠标点击位置小于菜单高度时则菜单生成位置在鼠标的右上角，否则在右下角，y轴同理
				var file = $('.yd-fm-file-right-option');
				var fileOptionHeight = file.height();
				var fileOptionWidth = file.width();
				var windowHeightHalf = $(document).height() - fileOptionHeight - 40;
				var windowWidthHalf = $(document).width() - fileOptionHeight + 50;
				if(y > windowHeightHalf) y -= fileOptionHeight;
				if(x > windowWidthHalf) x -= fileOptionWidth;
				file.css({ left: x + 'px', top: y + 'px' });
				//不能直接写file.show(); 会导致在ie下弹出默认菜单
				setTimeout(function(){
                     file.show();
                },100);
			}
			function hide(){
				$('.yd-fm-file-right-img-option,.yd-fm-file-right-onlyone-option').hide();
			}
			function remove(){
				$('.yd-fm-file-right-option').remove();
			}
			return {
				init: init,
				show: show,
				hide: hide,
				remove: remove
			}
		})();

		//生成文件右键菜单
		function showFileOption(e){
			//console.log("clientX="+e.clientX+" clientY="+e.clientY + " pageX="+e.pageX+" pageY="+e.pageY);
			mCurrentTarget = $(this);
			//右键到未选中项时 取消所有选中项
			if(!$(this).hasClass('on')) $('.yd-fm-fileitem').removeClass('on ydicon-gouxuan');
			e.data.removeClass('r-on');
			$(this).addClass('r-on');
			fileRightOption.hide();
			fileRightOption.show(e.clientX, e.clientY);
			return false;
		}

		//选择文件到后台
		function confirmedFile(e){
			var fileDetailList = mGetSelectedFilesDetail()
			if(!fileDetailList){
				ErrorBox('请选择文件');
				return false;
			}
			if(fileDetailList.length > 1){ //排序
				fileDetailList.sort(function(a, b){
					return a.n - b.n;
				})
			}
			var fileDetail = mGetSelectedFileDetail()
			if(fm.selectActionFunction) {
				yd.log("selectActionFunction回调执行");
				fm.selectActionFunction(mFormatUrl(mCurrentTarget.data('imgsrc')), fileDetail, fileDetailList);
				destroyDialog();
			}
			return false;
		}

		//查看文件
		function fileView(){
			var root = $('#yd-fm-container');
			var html = createViewBox();
			if(html){
			 	$(root).append(html);
				$('.yd-fm-fileView-mask,.yd-fm-fileView-close').click(function(){
					$('.yd-fm-fileView-img,.yd-fm-fileView-mask,.yd-fm-fileView-close').remove();
				})
			}
			//创建简易图片查看器， 非图片直接弹出新窗口
			function createViewBox(){
				var tg = mCurrentTarget;
				var imgUrl = tg.data('imgsrc');
				if(tg.data('isimg') == 1){
					imgUrl += '?' + new Date().getTime();
					var html = '<img class="yd-fm-fileView-img" src="'+ imgUrl +'"/>';
					html += '<div class="yd-fm-fileView-close ydicon-icon_cancel"></div>';
					html += '<div class="yd-fm-fileView-mask"></div>';
					return html;
				}else{
					global.open(imgUrl,"_blank");
					return false;
				}
			}
		}

		//复制文件路径
		function copyUrl(){
			var value = mCurrentTarget.data('imgsrc').split('?')[0];
			var inputHtml = yd.format('<input id="yd-fm-copy-input" value="[0]" style="position: fixed;top:0;left:0;" />', value);
			$('body').append(inputHtml);
			var inputDom = $('#yd-fm-copy-input');
			inputDom.select(); // 选择对象
    		document.execCommand("Copy");
    		inputDom.remove();
    		SucceedBox('复制成功');
		}

		//改变尺寸
		function fileResize(){
			var tg = mCurrentTarget;
			var fileNameList = mGetSelectedFilesName();
			var imgUrl = tg.data('imgsrc');
			var imgWidth = tg.data('w');
			var imgHeight = tg.data('h');
			var n = mGetSelectedFilesCount();
			mChildDlg = dialog({
				title: '设置新的尺寸（已选中' + n + '个图片）',
				id: mChildDialogID,
				padding: 0,
				quickClose: true,
				content: createdImgResizeBox(),
				onshow: function(){
					var lockDom = $('.yd-fm-resize-lock');
					var isOverWriteDom = $('.yd-fm-resize-isOverWrite');
					var widthDom = $('.yd-fm-resize-width');
					var heightDom = $('.yd-fm-resize-height');
					var ratio = (imgWidth / imgHeight) || 1;
					var lockWh = function(){
						var width = widthDom.val();
						heightDom.val(parseInt(width/ratio));
						// widthDom.change(function(){
						// 	heightDom.val(parseInt($(this).val()/ratio));
						// })
						// heightDom.change(function(){
						// 	widthDom.val(parseInt($(this).val()*ratio));
						// })
						widthDom.unbind().bind('input propertychange', function(){
							heightDom.val(parseInt($(this).val()/ratio));
						})
						heightDom.unbind().bind('input propertychange', function(){
							widthDom.val(parseInt($(this).val()*ratio));
						})
					}
					if(lockDom.is(':checked')){
						lockWh();
					}
					lockDom.change(function(){
						if($(this).is(':checked')){
							lockWh();
						}else{
							widthDom.unbind();
							heightDom.unbind();
						}
					})
				},
				ok: function () {
					var url = getUrl("setImageSize");
					var isOverWrite = $('.yd-fm-resize-isOverWrite').is(':checked') ? 1 : 0;
					imgWidth = $('.yd-fm-resize-width').val();
					imgHeight = $('.yd-fm-resize-height').val();
					var params = { CurrentDir: mCurrentDir, FileNameList: fileNameList, IsOverWrite: isOverWrite, Width: imgWidth, Height: imgHeight, DataSource: mDataSource };
					params = getPublicParams(params);
					$.post(url, params, function(res){
						yd.log('setImageSize', res, params);
						if(res.status == 1){
							mChildDlg.close().remove();
							SucceedBox(res.info);
							getFile();
						}else{
							ErrorBox(res.info);
						}
					}, 'JSON');
					return false;
				},
				cancel:function(){
					this.close().remove();
				},
				okValue: '确定',
				cancelValue: '关闭',
				cancel: true
			}).show();
			//创建尺寸修改工具框
			function createdImgResizeBox(){
				var html = '<div class="yd-fm-resize">';
				html  += '<div class="yd-fm-resize-img">';
			 	html += '<img src="'+ imgUrl +'"/>';
				if(imgWidth&&imgHeight) html += '<div class="yd-fm-resize-wh">'+ imgWidth + "×" + imgHeight +'</div>';
				html += '</div><div class="yd-fm-resize-wrap">'
				html += '<span>宽度</span>&nbsp;&nbsp;<input class="yd-fm-resize-width" type="number" value="'+imgWidth+'" /><br/>'
				html += '<span>高度</span>&nbsp;&nbsp;<input class="yd-fm-resize-height" type="number" value="'+imgHeight+'" />'
				html += '<div class="yd-fm-resize-tip">'+mSizeTip+'</div><label>'
				if(n <= 1){
					html += '<input class="yd-fm-resize-lock" type="checkbox" checked="checked" />'
				}else{
					html += '<input class="yd-fm-resize-lock" type="checkbox" />'
				}
				html += '<span>锁定比例</span>&nbsp;&nbsp;</label><label>'
				html += '<input class="yd-fm-resize-isOverWrite" type="checkbox" />'
				html += '<span>覆盖文件</span></label>'
				html += '</div></div>'
				return html;
			}
		}

		//图片瘦身
		function slimImage(){
			var tg = mCurrentTarget;
			var fileNameList = mGetSelectedFilesName();
			var imgUrl = tg.data('imgsrc');
			var imgWidth = tg.data('w');
			var imgHeight = tg.data('h');
			var name = tg.data('name');
			var n = mGetSelectedFilesCount();
			var url = getUrl("slimImage");
			var timer;
			mChildDlg = dialog({
				title: '图片瘦身（已选中' + n + '个图片）',
				id: mChildDialogID,
				padding: 0,
				quickClose: true,
				content: createdSlimImageBox(imgUrl, imgWidth, imgHeight, n, name),
				statusbar: '<div class="yd-fm-slim-statusbar"></div>',
				onshow:function(){
					$('.yd-fm-slim-range input').jRange({
					    theme:'theme-blue',  snap: true, showLabels: true, 
						showScale:false,from: 0, to: 100, step: 1, width: 220,
						onstatechange:function () { //数字变化的时候的回调函数
							slimImageRequest()
						},
					});
					var lockDom = $('.yd-fm-slim-lock');
					var isOverWriteDom = $('.yd-fm-slim-isOverWrite');
					var widthDom = $('.yd-fm-slim-width');
					var heightDom = $('.yd-fm-slim-height');
					var typeDom = $('.yd-fm-slim input[name="slimImage"]');
					var ratio = (imgWidth / imgHeight) || 1;
					var bindInput = function(isLock){
						widthDom.unbind().bind('input propertychange', function(){
							if(isLock) heightDom.val(parseInt($(this).val()/ratio));
							slimImageRequest()
						})
						heightDom.unbind().bind('input propertychange', function(){
							if(isLock) widthDom.val(parseInt($(this).val()*ratio));
							slimImageRequest()
						})
					}
					var lockWh = function(){
						var width = widthDom.val();
						heightDom.val(parseInt(width/ratio));
					}
					if(lockDom.is(':checked')){
						lockWh();
						bindInput(1);
					}else{
						bindInput();
					}
					lockDom.change(function(){
						if($(this).is(':checked')){
							lockWh();
							bindInput(1);
						}else{
							bindInput();
						}
						slimImageRequest()
					})
					typeDom.change(function(){
						slimImageRequest();
						var type = $('.yd-fm-slim input[name="slimImage"]:checked').val();
						var wrap = $('.yd-fm-slim-range-wrap');
						if(type != 'jpg'){
							wrap.hide()
						}else{
							wrap.show()
						}
					})
				},
				ok: function () {
					slimImageRequest(1);
					return false;
				},
				cancel:function(){
					this.close().remove();
				},
				okValue: '确定',
				cancelValue: '关闭',
				cancel: true,
				cancelDisplay: false,
			}).show();
			function slimImageRequest(notPreview){
				var fn = function(){
					var params = {
						CurrentDir: mCurrentDir,
						DataSource: mDataSource,
						FileNameList: fileNameList,
						Width: $('.yd-fm-slim-width').val(),
						Height: $('.yd-fm-slim-height').val(),
						IsOverWrite: $(".yd-fm-slim-isOverWrite").is(':checked') ? 1 : 0,
						Format: $('.yd-fm-slim input[name="slimImage"]:checked').val(),
						Quality: $('.yd-fm-slim-range input').val(),
					};
					if(!notPreview){
						params.IsPreview = 1;
						params.FileNameList = name;
					}
					if(notPreview) LoadBox('瘦身中');
					params = getPublicParams(params);
					$.post(url, params, function(res){
						yd.log('slimImage', res, params);
						CloseLoadBox();
						if(res.status == 1){
							if(notPreview){
								SucceedBox(res.info);
								getFile();
								mChildDlg.close().remove();
								return;
							}
							var d = res.data
							var statusbar = '原图<span class="blue">'+d.OldSizeReadable+'</span> ';
			                statusbar += '瘦身后<span class="green">'+d.NewSizeReadable+'</span>';
			                statusbar += d.NewSize > d.OldSize ? ' 增加' : ' 减少';
			                statusbar += '了<span class="red" style="margin-right: 5px;">'+d.DeltaReadable+'</span>';
			                statusbar += '<a href="javascript:;" id="slim-preview">预览效果</a>';
							$('.yd-fm-slim-statusbar').html(statusbar);
							$('#slim-preview').click(function(){ openSlimPreview(d) });
						}else{
							ErrorBox(res.info);
						}
					}, 'JSON');
				}
				if(!notPreview){
					clearTimeout(timer);
					timer = setTimeout(function(){
						fn();
					},200)
				}else{
					fn();
				}
			}
			// 瘦身图片预览
			function openSlimPreview(d){
				var fitwh = mImageSizeToAspectFit(d.NewWidth, d.NewHeight)
				mChildDlg = dialog({
					title: '图片瘦身预览',
					quickClose: true,
					padding: 0,
					content: '<div style="width:'+fitwh.width+'px;height:'+fitwh.height+'px;max-height:'+(fitwh.windowHeight-100)+'px;overflow-y:auto;"><img src="'+d.NewFileUrl+'" width="100%"/></div>',
				}).show();
			}
			//创建图片瘦身工具框
			function createdSlimImageBox(imgUrl, width, height, n, name){
				var html = '<div class="yd-fm-slim">';
				html  += '<div class="yd-fm-slim-img">';
			 	html += '<img src="'+ imgUrl +'"/>';
				html += '<div class="yd-fm-slim-name">'+ name + '</div>';
				if(width&&height) html += '<div class="yd-fm-slim-wh">'+ width + "×" + height +'</div>';
				html += '</div><div class="yd-fm-slim-resize-wrap">'
				html += '<span>宽</span>&nbsp;&nbsp;<input class="yd-fm-slim-width" type="number" value="'+width+'"/>&nbsp;&nbsp;&nbsp;'
				html += '<span>高</span>&nbsp;&nbsp;<input class="yd-fm-slim-height" type="number" value="'+height+'"/>'
				html += '<div class="yd-fm-slim-tip">'+mSizeTip+'</div>'
				html += '<div class="yd-fm-slim-type-wrap"><span>图像格式&nbsp;&nbsp;&nbsp;</span>'
				html += '<label><input type="radio" name="slimImage" checked="checked" value="jpg" />jpg</label>'
				html += '<label><input type="radio" name="slimImage" value="png" />png</label>'
				html += '<label><input type="radio" name="slimImage" value="gif" />gif</label>'
				html += '<label><input type="radio" name="slimImage" value="bmp" />bmp</label></div>'
				html += '<div class="yd-fm-slim-range-wrap"><span>图像品质&nbsp;</span>'
				html += '<div class="yd-fm-slim-range"><input type="hidden" value="60" /></div></div>'
				html += '<div class="yd-fm-slim-line"></div>'
				html += '<label>'
				if(n <= 1){
					html += '<input class="yd-fm-slim-lock" type="checkbox" checked="checked" />'
				}else{
					html += '<input class="yd-fm-slim-lock" type="checkbox" />'
				}
				html += '<span>锁定比例</span></label>'
				html += '<label><input class="yd-fm-slim-isOverWrite" type="checkbox" />'
				html += '<span>覆盖文件</span></label>'
				html += '</div></div>'
				return html;
			}
		}

		//图片水印
		function imageWater(){
			var tg = mCurrentTarget;
			var fileDetail = {
				fileNameList: mGetSelectedFilesName(),
				imgUrl: tg.data('imgsrc'),
				w: tg.data('w'),
				h: tg.data('h'),
				name: tg.data('name'),
			}
			var addWaterRequest = function(isPreview) {
				var url = getUrl("addWater");
				var params = {
					CurrentDir: mCurrentDir,
					DataSource: mDataSource,
					FileNameList: isPreview ? fileDetail.name : fileDetail.fileNameList,
					IsOverWrite: $(".yd-fm-water-isOverWrite").is(':checked') ? 1 : 0,
					IsPreview: isPreview ? 1 : 0
				}
				var fitwh = mImageSizeToAspectFit(fileDetail.w, fileDetail.h);
				params = getPublicParams(params);
				$.post(url, params, function(res){
					yd.log('addWater', res, params)
					if(res.status == 1){
						if(isPreview){
							dialog({
								title: '水印效果预览',
								quickClose: true,
								padding: 0,
								content: '<img src="'+res.data.FileUrl+'" style="display:block;width:'+fitwh.width+'px;height:'+fitwh.height+'px;" />'
							}).show();
							return;
						}
						SucceedBox(res.info)
						mChildDlg.close().remove();
						getFile();
						return;
					}
					ErrorBox(res.info);
				},'JSON')
			}
			var getWaterConfig = function(){
				var url = getUrl("getWaterConfig");
				var params={};
				params = getPublicParams(params);
				$.post(url, params, function(res){
					yd.log('getWaterConfig', res);
					if(res.status == 1){
						createdImageWaterBox(res.data);
					}else{
						ErrorBox(res.info)
					}
				}, 'JSON');
			}
			mChildDlg = dialog({
				title: '图片加水印（已选中' + mGetSelectedFilesCount() + '个图片）',
				id: mChildDialogID,
				padding: 0,
				quickClose: true,
				statusbar: '<a href="javascript:;" class="yd-fm-water-preview-btn">水印效果预览<a>',
				content: '<div class="yd-fm-water-container"></div>',
				onshow:function(){
					$('.yd-fm-water-preview-btn').click(function(){
						addWaterRequest(1);
					})
					getWaterConfig();
				},
				ok: function () {
					addWaterRequest(0);
					return false;
				},
				button: [
					{
						value: '水印设置',
						callback: function () {
							dialog({
								title: '图片水印设置',
								padding: 0,
								quickClose: true,
								content: '<iframe class="yd-fm-water-preview-container" frameborder="0" scrolling="yes" src="'+mConfig.Group+'/Config/water"></iframe>',
								cancelDisplay: false,
								cancel: function(){
									getWaterConfig();
								}
							}).show();
							return false;
						}
					}
				],
				cancel: function(){
					
				},
				okValue: '确定',
				cancelDisplay: false,
			}).show();
			//创建图片水印工具框
			function createdImageWaterBox(data){
				var html = '<div id="yd-fm-water">';
				html  += '<div class="yd-fm-water-img">';
			 	html += '<img src="'+ fileDetail.imgUrl +'"/>';
				html += '<div class="yd-fm-water-name">'+ fileDetail.name + '</div>';
				if(fileDetail.w&&fileDetail.h) html += '<div class="yd-fm-water-wh">'+ fileDetail.w + "×" + fileDetail.h +'</div>';

				html += '</div><div class="yd-fm-water-wrap">'
				html += '<div class="w">'
				html += '<span>水印类型</span>'+data.WaterTypeText+'<br/>'
				if(data.WaterType == 1) html += '<span>水印图片</span><img class="yd-fm-water-pic" src="'+data.WaterPic+'" /><br/>'
				if(data.WaterType == 2){
					html += '<span>水印文字</span><span class="WaterText" style="line-height:1.3em;color:'+data.WaterTextColor+';">'+data.WaterText+'</span><br/>'
					html += '<span>水印文字大小</span>'+data.WaterTextSize+'px<br/>'
					html += '<span>水印文字颜色</span><span class="WaterTextColor" style="background:'+data.WaterTextColor+';"></span>'+data.WaterTextColor+'<br/>'
				}
				html += '<span>水印位置</span>'+data.WaterPosition+'<br/>'
				html += '</div>'
				html += '<label><input class="yd-fm-water-isOverWrite" type="checkbox" />'
				html += '<span style="color:#888;cursor:pointer;">覆盖文件</span></label>'
				html += '</div></div>'
				$('.yd-fm-water-container').html(html);
				return html;
			}
		}

		// 图片裁剪
		function imageCrop(){
			var cropperMousedown,cropperMousemove,cropperMouseup;
			var detail = mGetSelectedFileDetail();
			var wh = mImageSizeToAspectFit(detail.width, detail.height, 80);
			var isLock = false;
			var ratio = 1;
			var isOverWrite = 0;
			var cropper;
			var cropperLeft = parseInt(detail.width/4);
			var cropperTop = parseInt(detail.height/4);
			var cropperWdith = parseInt(detail.width/2);
			var cropperHeight = parseInt(detail.height/2);
			mChildDlg = dialog({
				title: yd.format('图片裁剪[0]（[1] × [2]）', detail.name, detail.width, detail.height),
				id: mChildDialogID,
				padding: 0,
				quickClose: true,
				content: getCropHtml(),
				statusbar: getCropStatusbarHtml(),
				onshow:function(){
					cropperMoveEventBind();
					cropperStatusbarEventBind();
					cropper = $('.yd-fm-cropper');
				},
				ok: function () {
					var url = getUrl("cropImage");
					var size = $('.yd-fm-cropper.s');
					var params = {
						CurrentDir: mCurrentDir,
						DataSource: mDataSource,
						FileName: detail.name,
						X: parseInt(size.css('left')),
						Y: parseInt(size.css('top')),
						Width: parseInt(size.css('width')) + 4,
						Height: parseInt(size.css('height')) + 4,
						IsOverWrite: isOverWrite,
					};
					LoadBox();
					params = getPublicParams(params);
					$.post(url, params, function(res){
						yd.log('cropImage', res, params);
						CloseLoadBox();
						if(res.status == 1){
							SucceedBox(res.info)
							mChildDlg.close().remove();
							getFile();
						}else{
							ErrorBox(res.info)
						}
					}, 'JSON');
					return false;
				},
				cancel: function(){
					$(global).unbind('mousedown', cropperMousedown);
					$(global).unbind('mousemove', cropperMousemove);
					$(global).unbind('mouseup', cropperMouseup);
				},
				okValue: '确定',
				cancelValue: '取消',
			}).show();
			function getCropHtml(){
				var html = yd.format('<div class="yd-fm-crop-container" style="width:[0]px;height:[1]px;max-height:[2]px;max-width:[3]px">', detail.width + 30, detail.height + 30, wh.windowHeight-100, wh.windowWidth);
				html += yd.format('<div class="yd-fm-cropimg" style="width:[0]px;height:[1]px">', detail.width, detail.height);
				html += yd.format('<div class="yd-fm-cropper s" style="left:[0]px;top:[1]px;width:[2]px;height:[3]px"><i class="yd-fm-cropper-size"></i></div>', cropperLeft, cropperTop, cropperWdith, cropperHeight);
				html += yd.format('<div class="yd-fm-cropper yd-fm-cropper-shad" style="left:[0]px;top:[1]px;width:[2]px;height:[3]px"></div>', cropperLeft, cropperTop, cropperWdith, cropperHeight);
				html += yd.format('<img src="[0]"/>', detail.url);
				html += '</div></div>'
				return html;
			}
			function getCropStatusbarHtml(){
				var html = '<div class="yd-fm-cropper-status">'
				html += yd.format('<span>裁剪区域</span><input class="yd-fm-cropper-width yd-fm-textinput" type="number" value="[0]" /> × <input class="yd-fm-cropper-height yd-fm-textinput" type="number" value="[1]" />', cropperWdith, cropperHeight);
				html += '<div class="yd-fm-wrap"><label><input type="checkbox" class="yd-fm-crop-lock" />锁定比例</label>&nbsp;&nbsp;&nbsp;<label><input type="checkbox" class="yd-fm-crop-isOverWrite" />覆盖文件</label></div>'
				html += '</div>'
				return html;
			}
			function cropperStatusbarEventBind(){
				var lockDom = $('.yd-fm-crop-lock');
				var isOverWriteDom = $('.yd-fm-crop-isOverWrite');
				var widthDom = $('.yd-fm-cropper-width');
				var heightDom = $('.yd-fm-cropper-height');
				var lockWh = function(){
					ratio = (widthDom.val() / heightDom.val()) || 1;
				}
				lockDom.change(function(){
					if($(this).is(':checked')){
						isLock = true;
						lockWh();
					}else{
						isLock = false;
					}
				})
				isOverWriteDom.change(function(){
					if($(this).is(':checked')){
						isOverWrite = 1;
					}else{
						isOverWrite = 0;
					}
				})
				widthDom.unbind().bind('input propertychange', function(){
					var width = parseInt($(this).val());
					if(width < 5){
						width = 5;
					}else if(width + cropperLeft > detail.width){
						width = detail.width - cropperLeft;
					}
					if(isLock){
						var height = parseInt(width/ratio);
						if(height < 5){
							height = 5;
							width = parseInt(height*ratio);
						}else if(height + cropperTop > detail.height){
							height = detail.height - cropperTop;
							width = parseInt(height*ratio);
						}
						cropper.height(height-4);
						heightDom.val(height);
					}
					$(this).val(width);
					cropper.width(width-4);
				})
				heightDom.unbind().bind('input propertychange', function(){
					var height = parseInt($(this).val());
					if(height < 5){
						height = 5;
					}else if(height + cropperTop > detail.height){
						height = detail.height - cropperTop;
					}
					if(isLock){
						var width = parseInt(height*ratio);
						if(width < 5){
							width = 5;
							height = parseInt(width/ratio);
						}else if(width + cropperLeft > detail.width){
							width = detail.width - cropperLeft;
							height = parseInt(width/ratio);
						}
						cropper.width(width-4);
						widthDom.val(width);
					}
					$(this).val(height);
					cropper.height(height-4);
				})
			}
			function cropperMoveEventBind(){
				var container = $(global);
				var firstX,firstY;
				var width,height;
				var maxRight,maxBottom;
				var canMove = false;
				var isSize = false;
				cropperMousedown = function(e){
					firstX = e.screenX;
					firstY = e.screenY;
					cropperLeft = parseInt(cropper.css('left'));
					cropperTop = parseInt(cropper.css('top'));
					if(e.target.className == 'yd-fm-cropper-size'){
						// 大小调整
						cropper.addClass('active');
						isSize = true;
						canMove = true;
						width = parseInt(cropper.width());
						height = parseInt(cropper.height());
						return false;
					}
					if($(e.target).hasClass('yd-fm-cropper')){
						// 移动
						cropper.addClass('active');
						console.log(e)
						canMove = true;
					}
					maxRight = detail.width - (cropper.width());
					maxBottom = detail.height - (cropper.height());
				}
				cropperMousemove = function(e){
					if(canMove){
						if(isSize){
							// 大小调整
							var w = parseInt(width - (firstX - e.screenX));
							var h = parseInt(height - (firstY - e.screenY));
							if(isLock) h = parseInt(w / ratio);
							if(w < 5){
								w = 5;
								if(isLock) h = parseInt(w / ratio);
							}else if(w + cropperLeft > detail.width){
								w = detail.width - (cropperLeft);
								if(isLock) h = parseInt(w / ratio);
							}
							if(h < 5){
								h = 5;
							 	if(isLock) w = parseInt(h * ratio);
							}else if(h + cropperTop > detail.height){
							 	h = detail.height - (cropperTop);
							 	if(isLock) w = parseInt(h * ratio);
							}
							cropper.css({ width: w - 4 + 'px', height: h - 4 + 'px' });
							$('.yd-fm-cropper-width').val(w);
							$('.yd-fm-cropper-height').val(h);
							return false;
						}
						var x = cropperLeft - (firstX - e.screenX);
						var y = cropperTop - (firstY - e.screenY);
						if(x < 0) x = 0;
						if(x > maxRight) x = maxRight - 4;
						if(y < 0) y = 0;
						if(y > maxBottom) y = maxBottom - 4;
						cropper.css({ 'left': x + 'px', 'top': y + 'px' });
						return false;
					}
				}
				cropperMouseup = function(){
					cropper.removeClass('active');
					canMove = false;
					isSize = false;
				}
				container.mousedown(cropperMousedown)
				container.mousemove(cropperMousemove)
				container.mouseup(cropperMouseup)
			}
		}

		//文件重命名
		function changeFileName(){
			var tg = mCurrentTarget;
			var oldName = tg.data('name');
			mChildDlg = dialog({
				title: '重命名',
				id: mChildDialogID,
				padding: 0,
				quickClose: true,
				content: '<input id="yd-fm-changeFileName" class="yd-fm-textinput" placeholder="请输入新的文件名" value="'+oldName+'" autofocus="autofocus" />',
				onshow:function(){
				},
				ok: function () {
					var newFileName = $('#yd-fm-changeFileName').val();
					var url = getUrl("changeFileName");
					var params = {
						OldFileName: oldName,
						NewFileName: newFileName,
						DataSource: mDataSource,
						CurrentDir: mCurrentDir
					};
					params = getPublicParams(params);
					$.post(url, params, function(res){
						yd.log('changeFileName', res, params);
						if(res.status == 1){
							SucceedBox(res.info)
							mChildDlg.close().remove();
							var img = tg.find('.yd-fm-file-img img');
							var newSrc = img.attr('src').replace(oldName, newFileName);
							img.attr('src', newSrc);
							tg.find('.yd-fm-file-name').text(newFileName);
							tg.data({
								'name': newFileName,
								'imgsrc': newSrc,
								'fullname': '.'+newSrc
							})
						}else{
							ErrorBox(res.info)
						}
					}, 'JSON');
					return false;
				},
				cancel:function(){
					this.close().remove();
				},
				okValue: '确定',
				cancelValue: '关闭',
				cancel: true
			}).show();
		}

		//创建副本文件
		function copyFile(){
			var fileNameList = mGetSelectedFilesName();
			var url = getUrl("copyFile");
			var params = {
				CurrentDir: mCurrentDir,
				FileNameList: fileNameList,
				DataSource: mDataSource
			};
			params = getPublicParams(params);
			$.post(url, params, function(res){
				yd.log('copyFile', res, params);
				if(res.status == 1){
					SucceedBox(res.info);
					getFile();
				}else{
					ErrorBox(res.info)
				}
			}, 'JSON');
		}

		//移动文件
		function moveFile(){
			cloneAndShowDir();
			bindFloatDirClick();
			function cloneAndShowDir(){
				var dirsClone = $('#yd-fm-container .yd-fm-dir-container').clone();
				var statusBar = "<label class='yd-fm-movedlg-status-bar' title='如果选择，移动到目标目录会覆盖同名文件，否则会重命名文件！'>";
		        statusBar+= "<input type='checkbox'>覆盖同名目标文件</label>";
				dirsClone.css({
					'height': '360px',
					'margin': '0',
					'width': '340px',
				})
				dirsClone.attr('id', 'yd-fm-float-dir');
				dirsClone.find('.click-show-file').removeClass('on');
				mChildDlg = dialog({
					title: '请选择目录（已选中'+mGetSelectedFilesCount()+'个文件）',
					id: mChildDialogID,
					padding: 0,
					content: dirsClone,
					statusbar: statusBar,
					quickClose: true,
					onshow:function(){
					},
					ok: function () {
						var selectedDir = $('#yd-fm-float-dir .click-show-file.on');
						if(selectedDir.length < 1){
							ErrorBox('请选择目录');
							return false;
						}
						var isOverWrite = $(".yd-fm-movedlg-status-bar input").is(':checked') ? 1 : 0;
						var dirFullName = selectedDir.data('fullname');
						var url = getUrl("moveFile");
						var fileNameList = mGetSelectedFilesName();
						var params = { 
							CurrentDir: mCurrentDir, 
							FileNameList: fileNameList, 
							IsOverWrite: isOverWrite, 
							DstDir: dirFullName, 
							DataSource: mDataSource 
						};
						params = getPublicParams(params);
						$.post(url, params, function(res){
							yd.log('moveFile', res, params);
							if(res.status == 1){
								SucceedBox(res.info);
								mChildDlg.close().remove();
								getFile();
							}else{
								ErrorBox(res.info);
							}
						}, 'JSON');
						return false;
					},
					cancel:function(){
						this.close().remove();
					},
					okValue: '移动',
					cancelValue: '关闭',
					cancel: true
				}).show();
			}
			function bindFloatDirClick(cb){
				var bindDirClick = function(){
					$('#yd-fm-float-dir .click-show-file').removeClass('on');
					$(this).addClass('on');
				}
				var bindShowSubdirClick = function(event){
					event.stopPropagation();
					var that = $(this);
					var child = $('#yd-fm-float-dir .parent-name-' + that.data('parent'));
					if(that.hasClass('ydicon-right')){
						that.removeClass('ydicon-right').addClass('ydicon-bottom');
						child.show();
					}else{
						that.addClass('ydicon-right').removeClass('ydicon-bottom');
						child.hide();
					}
					if(that.hasClass('hasChild')) return;
					that.addClass('hasChild');
					var depth = parseInt(that.data('depth'))+1;
					getFloatDir({Dir: that.data('fullname'), DataSource: mDataSource}, depth, that.data('parent'));
					//获取浮动子文件夹
					function getFloatDir(params, depth, parentName){
						if(!depth) depth = 1;
						if(!parentName) parentName = '';
						if(!params) params = {DataSource: mDataSource};
						var url = getUrl("getDir");
						params = getPublicParams(params);
						$.post(url, params, function(res){
							yd.log('getDir-移动', res, params);
							if(res.status == 1){
								showFloatDir(res.data, depth, parentName);
							}else{
								ErrorBox(res.info);
							}
							//如果没有子文件夹则删除展开文件夹的箭头
							if(res.data.length < 1){
								that.remove()
							}
							return true;
						}, 'JSON');
					}
					//渲染浮动文件夹 子文件夹
					function showFloatDir(dirs, depth, parentName){
						for (var i = 0; i < dirs.length; i++) {
							var item = dirs[i];
							var name = item.FullDirName.replace(/\//g,'-');
							if(name[0] == '.') name = name.substr(1);
							var html = '<li class="diritem dir-name-'+name+' parent-name-'+ parentName +'">';
							html += '<div class="click-show-file item ydicon-folder depth'+ depth +'" data-depth="'+ depth +'" data-fullname="'+ item.FullDirName +'" data-name="'+ item.DirName +'">';
							html += item.DirName;
							if(item.HasChildren){
								html += '<i class="click-show-subdir ydicon-right" data-fullname='+ item.FullDirName +' data-depth='+ depth +' data-parent='+ name +'></i>'
							}
							html += '</div></li>';
							if(!parentName){
								$('.yd-fm-dirlist').append(html);
							}else{
								$('#yd-fm-float-dir .dir-name-' + parentName).append(html);
							}
						}
						bindFloatDirClick();
					}
				}
				$('#yd-fm-float-dir .click-show-file').unbind().click(bindDirClick);
				$('#yd-fm-float-dir .click-show-subdir').unbind().click(bindShowSubdirClick);
			}
		}

		//删除文件
		function deleteFile(){
			var fileNameList = mGetSelectedFilesName();
			yd.log('文件删除', fileNameList);
			if(!fileNameList) return ErrorBox('请选择要删除的文件');
			var length = mGetSelectedFilesCount();
			var msg = '确定删除选中的<span style="color:red">'+ length +'</span>个文件吗？' 
			mChildDlg = ConfirmBox(msg, function(){
				var url = getUrl("deleteFile");
				var params = { CurrentDir: mCurrentDir, FileNameList: fileNameList, DataSource: mDataSource };
				params = getPublicParams(params);
				$.post(url, params, function(res){
					yd.log('deleteFile', res, params);
					if(res.status == 1){
						SucceedBox(res.info);
						if(length > 1){
							var selectedFile = $('#yd-fm-container .click-select-file.on');
							selectedFile.each(function(){
								$(this).remove();
							})
							if($('.yd-fm-fileitem').length < 1) getFile();
						}else{
							mCurrentTarget.parent().remove();
						}
					}else{
						ErrorBox(res.info);
					}
				}, 'JSON');
			}, null, {id: mChildDialogID})
		}
		/* -------------------------------------文件操作 结束------------------------------------- */

		/* -------------------------------------文件上传 开始------------------------------------- */
	    //取消上传 id: 就是file.id
	    function cancelUpload(id){
	        if(mFiles[id]){
	            var up = mFiles[id].up;
	            var file = mFiles[id].file;
	            $("#"+file.id).remove();
	            up.removeFile(file);
	        }
	        return this;
	    }
		/*---- 上传函数回调 开始 ----*/
		// 上传文件添加完成回调
		function filesAddedCallback(up, files){
			yd.log('filesAddedCallback', files);
			clearTimeout(mUploadCloseTimer)
			mHasUploadError = false;
			$(".yd-fm-upload-container").show();
		  	plupload.each(files, function(file) {
				// 文件添加进队列后，处理相关的事情
				var size = plupload.formatSize(file.size).toUpperCase();
				var id = file.id;
				var html = '<li class="yd-fm-uploadlist-body" id='+id+'>';
				html +='<div class="yd-fm-uploadlist-name">'+file.name+'</div>';
				html +='<div class="yd-fm-uploadlist-operator"><span class="yd-fm-uploadlist-cancel" onclick="FileManager.cancelUpload('+"'"+id+"'"+')">取消上传</span></div>';
				html +='<div class="yd-fm-uploadlist-status"><span class="yd-fm-uploadlist-wait">等待上传</span></div>';
				html +='<div class="yd-fm-uploadlist-speed">--</div>';
				html +='<div class="yd-fm-uploadlist-per">0%</div>';
				html +='<div class="yd-fm-uploadlist-size">'+size+'</div>';
				html +='<div class="yd-fm-uploadlist-perblock"></div>';
				html +='</li>';
				mFiles[id] = {"up": up, "file": file};
				$(".yd-fm-uploadlist").append(html);
		  	});
	        if(mDataSource == 1) mUploader.start();
		}
		function beforeUploadCallback(up, file) {
			yd.log('beforeUploadCallback', file)
	    	if(mDataSource == 1) mUploader.settings.multipart_params.savepath = mCurrentDir;
	    	mUploader.settings.multipart_params.IsOverWrite = mUploadIsOverWrite;
	    }
		function uploadProgressCallback(up, file) {
			yd.log('uploadProgressCallback', file)
			var percent = file.percent + "%";
			$("#"+file.id+" .yd-fm-uploadlist-per").text(percent);
			$("#"+file.id+" .yd-fm-uploadlist-perblock").css('width', percent);
			if(file.speed>0){
				var speed = plupload.formatSize(file.speed).toUpperCase()+'/s';
				$("#"+file.id+" .yd-fm-uploadlist-speed").text(speed);
			}
			$("#"+file.id+" .yd-fm-uploadlist-status").html('<span class="yd-fm-uploadlist-uploading">正在上传</span>');
		}
		function fileUploadedCallback(up, file, res) {
			yd.log('fileUploadedCallback',file,res)
			var isSuccess = true;
			var errorMsg = "";
			if(mDataSource == 1){ //本地上传
				res = res && res.response ? JSON.parse(res.response) : '';
				if(res && res.status != 1){
					isSuccess = false;
					errorMsg = res.info;
				}
			}else if(mDataSource == 2){

			}else if(mDataSource == 3){

			}

			if(isSuccess){
				$("#" + file.id + " .yd-fm-uploadlist-status").html('<span class="yd-fm-uploadlist-suc">上传成功</span>');
			}else{
				$("#" + file.id + " .yd-fm-uploadlist-status").html('<span class="yd-fm-uploadlist-err" title="'+errorMsg+'">'+errorMsg+'</span>');
				mHasUploadError = true;
			}
	        $("#" + file.id + " .yd-fm-uploadlist-operator").html("--");
	    }
	    function errorCallback(up, err, errTip) {
	        //经测试：同一个文件(复制多个也是同一个文件)上传多次不会触发Error，同一个文件名不同的2个文件上传才会触发Error事件
	        yd.log('errorCallback', err, errTip)
	        var id = err.file.id;
			$("#" + id + " .yd-fm-uploadlist-status").html('<span class="yd-fm-uploadlist-err" title="'+errTip+'">'+errTip+'</span>');
	        $("#" + id + " .yd-fm-uploadlist-operator").html("--");
			mHasUploadError = true;
	    }
	    function uploadCompleteCallback() {
			//队列文件处理完毕后，处理相关的事情
			mFiles = {};
			getFile();
			if(!mHasUploadError){
				mUploadCloseTimer = setTimeout(function(){
					$('.yd-fm-upload-container').hide().find('.yd-fm-uploadlist-body').remove();
				}, 5000)
			};
			mHasUploadError = true;
	    }
	    /*---- 上传函数回调 结束 ----*/

		//加载本地上传工具
		function loadLocalUpload(){
			var params = {
				IsOverWrite: mUploadIsOverWrite, 
				savepath: mCurrentDir, 
				isrename: 0, 
				addwater: 'yes',
				isthumb: 0, 
				flag:1
			};
			params = getPublicParams(params);
		    mUploader = new plupload.Uploader({
		        multipart_params : params ,
		        runtimes: 'html5,flash,silverlight,html4',
		        browse_button: 'yd-fm-upload-btn',
		        //multi_selection: false,
		        container: document.getElementById('yd-fm-upload-container'),
				chunk_size:0,
		        url: getUploadUrl(),
		        init: {
		            PostInit: function(){
						params = getPublicParams(params);
					},
		            FilesAdded: filesAddedCallback,
		            BeforeUpload: beforeUploadCallback,
		            UploadProgress: uploadProgressCallback,
		            FileUploaded: fileUploadedCallback,
		            Error: errorCallback,
		            UploadComplete: uploadCompleteCallback
		        }
		    });
		    mUploader.init();
		}
		//加载七牛云上传工具
		function loadQiniuUpload(){
			yd.loadJs(mConfig.Js+'qiniu.js', function(){
				var url = getUrl("getUploadParams");
				var params = {DataSource: mDataSource,CurrentDir:mCurrentDir};
				params = getPublicParams(params);
				$.post(url, params, function(res){
					yd.log('getUploadParams', res);
					if(res.status == 1){
						initUploader(res.data)
					}else{
						ErrorBox(res.info);
					}
				}, 'JSON');
			});
			function initUploader(params){
				mUploader = Qiniu.uploader({
		        	multipart_params: {IsOverWrite: mUploadIsOverWrite},
					disable_statistics_report: true,   // 禁止自动发送上传统计信息到七牛，默认允许发送
					runtimes: 'html5,flash,html4',      //上传模式，依次退化
					browse_button: 'yd-fm-upload-btn',         // 上传选择的点选按钮，必需
					//uptoken : params.UploadToken, // uptoken是上传凭证，由其他程序生成
					uptoken_func: function(file){    // 在需要获取 uptoken 时，该方法会被调用
						 var uptoken = "";
						 var url = getUrl("getUploadToken");
						 var tokenParams = {
							 FileKey: mCurrentDir+file.name,
							 DataSource: mDataSource,
							 IsOverWrite: mUploadIsOverWrite
						};
						tokenParams = getPublicParams(tokenParams);
						 $.ajax({type:"post", async:false, data:tokenParams, dataType:"json", url:url,
								success : function(res){
									if(res.status == 1){
										uptoken = res.data;
									}
								}
						 });
					   return uptoken;
					 },
					get_new_uptoken: true,             // 设置上传文件的时候是否每次都重新获取新的uptoken
					
					//downtoken_url: mConfig.Group+'/Resource/getUploadToken',
					// Ajax请求downToken的Url，私有空间时使用，JS-SDK将向该地址POST文件的key和domain，服务端返回的JSON必须包含url字段，url值为该文件的下载地址
					// unique_names: true,   // 默认false，key为文件名。若开启该选项，JS-SDK会为每个文件自动生成key（文件名）
					//save_key: true,             // 默认false。若在服务端生成uptoken的上传策略中指定了sava_key，则开启，SDK在前端将不对key进行任何处理
					domain: params.Domain,     // bucket域名，下载资源时用到，必需
					container: 'yd-fm-upload-container',             // 上传区域DOM ID，默认是browser_button的父元素
					max_file_size: params.MaxUploadSize+'mb',             // 最大文件体积限制
					flash_swf_url: 'path/of/plupload/Moxie.swf',  //引入flash，相对路径
					//max_retries: 3,                     // 上传失败最大重试次数
					dragdrop: true,                     // 开启可拖曳上传
					drop_element: 'yd-fm-upload-container',   // 拖曳上传区域元素的ID，拖曳文件或文件夹后可触发上传
					chunk_size: '4mb',                // 分块上传时，每块的体积
					auto_start: true,                    // 选择文件后自动上传，若关闭需要自己绑定事件触发上传
					init: {
					  	'FilesAdded': filesAddedCallback,
						'BeforeUpload': beforeUploadCallback,
						'UploadProgress': uploadProgressCallback,
						'FileUploaded': fileUploadedCallback,
						'Error': errorCallback,
						'Key': function(up, file) {
							// 若想在前端对每个文件的key进行个性化处理，可以配置该函数
							// 该配置必须要在 unique_names: false , save_key: false 时才生效
							var key = mCurrentDir;
							key += file.name;
							return key
						},
						UploadComplete: uploadCompleteCallback
					}
				});	
			}
		}
		//加载阿里云上传工具
		function loadAliUpload(){
		    var accessid = '',
		    accesskey = '',
		    host = '',
		    policyBase64 = '',
		    signature = '',
		    callbackbody = '',
		    filename = '',
		    key = '',
		    expire = 0,
		    g_object_name = '',
		    timestamp,
		    now = timestamp = Date.parse(new Date()) / 1000;
		    function send_request() {
		        var responseStr="";
		        $.ajax({
		            url: mConfig.Group+"/alioss/getAliUploadFile?Prefix="+mCurrentDir,
		            async: false,
		            type : "GET",
		            success: function(data){
		                responseStr =data;
		            }
		        });
		        return responseStr;
		    };

		    function get_signature() {
		        // 可以判断当前expire是否超过了当前时间， 如果超过了当前时间， 就重新取一下，3s 作为缓冲。
		        now = timestamp = Date.parse(new Date()) / 1000;
		        if (expire < now + 3) {
		            body = send_request();

		            var obj = eval("(" + body + ")");
		            host = obj['host']
		            policyBase64 = obj['policy']
		            accessid = obj['accessid']
		            signature = obj['signature']
		            expire = parseInt(obj['expire'])
		            callbackbody = obj['callback']
		            key = obj['dir']
		            return true;
		        }
		        return false;
		    };



		    function get_suffix(filename) {
		        pos = filename.lastIndexOf('.')
		        suffix = ''
		        if (pos != -1) {
		            suffix = filename.substring(pos)
		        }
		        return suffix;
		    }



		    function get_uploaded_object_name(filename) {
		        if (g_object_name_type == 'local_name') {
		            tmp_name = g_object_name
		            tmp_name = tmp_name.replace("${filename}", filename);
		            return tmp_name
		        } else if (g_object_name_type == 'random_name') {
		            return g_object_name
		        }
		    }
		    function set_upload_param(up, filename, ret) {
		        if (ret == false) {
		            ret = get_signature()
		        }
		        g_object_name = key;
		        if (filename != '') {
		            suffix = get_suffix(filename);
					g_object_name += "${filename}";
		        }
		        new_multipart_params = {
		            'key': g_object_name,
		            'policy': policyBase64,
		            'OSSAccessKeyId': accessid,
		            'success_action_status': '200', //让服务端返回200,不然，默认会返回204
		            'callback': callbackbody,
		            'signature': signature,
		        };
		        up.setOption({
		            'url': host,
		            'multipart_params': new_multipart_params
		        });
		        up.start();
		    }
		    mUploader = new plupload.Uploader({
		        multipart_params: {IsOverWrite: mUploadIsOverWrite},
		        runtimes: 'html5,flash,silverlight,html4',
		        browse_button: 'yd-fm-upload-btn',
		        //multi_selection: false,
		        container: document.getElementById('yd-fm-upload-container'),
		        url: 'http://oss.aliyuncs.com',
		        init: {
		            PostInit: function () {},
		            FilesAdded: function(up, file){
		            	filesAddedCallback(up, file)
	        			set_upload_param(mUploader, '', false); //选中后，就自动上传。2021年8月27日
		            },
		            BeforeUpload: function (up, file) {
		            	beforeUploadCallback(up, file);
		                set_upload_param(up, file.name, true);
		            },
		            UploadProgress: uploadProgressCallback,
		            FileUploaded: fileUploadedCallback,
		            Error: errorCallback,
		            UploadComplete: uploadCompleteCallback
		        }
		    });
		    mUploader.init();
		}
		/* -------------------------------------文件上传 结束------------------------------------- */
		//判断是否有悬浮的dlg，有则阻止自定义键盘或鼠标事件
		function fmHasSubDlg(){
			var childDlg = dialog.get(mChildDialogID);
			if(childDlg) return true;
			return false;
		}
		//移除一系列鼠标操作（右键菜单、右键选中、左键选中等）
		function fmOptionRemove(e){
			if($(e.target).hasClass('yd-fm-right-option-item')){
				setTimeout(function(){
					$('.yd-fm-right-option').hide();
				},20)
				return;
			};
			if(fmHasSubDlg()) return;
			$('.yd-fm-fileitem').removeClass('r-on');
			$('.yd-fm-dir-container .item').removeClass('r-on');
			$('.yd-fm-right-option').hide();
		}
		//热键操作
		// 上下左右键移动
		function fmDirectionKeyEvent(){
			if(!mDlg) return {
				left: function(){},
				right: function(){},
				top: function(){},
				down: function(){}
			}
			var selectedNum = mGetSelectedFilesCount();
			var first = $('.yd-fm-fileitem:first');
			var last = $('.yd-fm-fileitem:last');
			var on = $('.yd-fm-fileitem.on');
			var classStr = 'on ydicon-gouxuan';
			var itemStr = '.yd-fm-fileitem';
			var lineNum;
			if(mViewType == 1){
				lineNum = 5;
			}else if(mViewType == 2){
				lineNum = 1;
			}else if(mViewType == 3){
				lineNum = 4;
			}
			function left(){
				if(first.hasClass('on')) return;
				if(selectedNum < 1){
					first.addClass(classStr);
					mCurrentTarget = first;
				}else{
					mCurrentTarget = on.removeClass(classStr).parent().prev().find(itemStr).addClass(classStr);
				}
			}
			function right(){
				if(last.hasClass('on')) return;
				if(selectedNum < 1){
					first.addClass(classStr);
					mCurrentTarget = first;
				}else{
					mCurrentTarget = on.removeClass(classStr).parent().next().find(itemStr).addClass(classStr);
				}
			}
			function top(){
				var i = on.data('index');
				if(i < lineNum) return;
				if(selectedNum < 1){
					first.addClass(classStr);
					mCurrentTarget = first;
				}else{
					on.removeClass(classStr);
					var t = itemStr+'[data-index='+(i-lineNum)+']';
					mCurrentTarget = $(t).addClass(classStr);
				}
			}
			function down(){
				var length = $(itemStr).length;
				var i = parseInt(on.data('index'));
				if(i >= length - lineNum) return;
				if(selectedNum < 1){
					first.addClass(classStr);
					mCurrentTarget = first;
				}else{
					on.removeClass(classStr);
					var t = itemStr+'[data-index='+(i+lineNum)+']';
					mCurrentTarget = $(t).addClass(classStr);
				}
			}
			return {
				left: left,
				right: right,
				top: top,
				down: down,
			}
		};
		function fmHotKeyUp(e){
			$('body').removeClass('yd-fm-container-not-select'); //取消默认文本选择
		}
		function fmHotKeyDown(e){
			if($(":focus").hasClass('yd-fm-search-input')) return; //焦点在搜索框则阻止任何键盘操作
			if(fmHasSubDlg()) return;
			// yd.log(e.keyCode)
			$('.yd-fm-right-option').hide();
			switch(e.keyCode){
				case 13:
					//Enter
					if(!e.ctrlKey){
						confirmedFile();
						return false;
					}
					break;
				case 16:
					//Shift
					$('body').addClass('yd-fm-container-not-select'); //阻止默认文本选择
					break;
				case 17:
					//Control
					// $('body').addClass('yd-fm-container-not-select'); //阻止默认文本选择
					break;
				case 27:
					//Escape
					// mDlg.close().remove();
					break;
				case 46:
					//Delete
					deleteFile();
					break;
				case 65:
					//a
					if(e.ctrlKey){
						// Control + a
						e.preventDefault();
						$('.yd-fm-fileitem').addClass('on ydicon-gouxuan'); //选中所有文件
					}
					break;
				case 37:
					//←
					fmDirectionKeyEvent().left();
					break;
				case 38:
					//↑
					fmDirectionKeyEvent().top();
					break;
				case 39:
					//→
					fmDirectionKeyEvent().right();
					break;
				case 40:
					//↓
					fmDirectionKeyEvent().down();
					break;
			}
		}
		//配置参数设置
		function setConfig(params){
			mConfig = $.extend(mConfig, params);
			if(params.filter == 2){
				params.filter = 'mp4';
			}
			return this;
		}
		
		//设置默认目录
		function setDefaultDir(){
			dataSource = getStorage('YDDataSource') || 1; 
			if(1 != dataSource) return this;
			uploadDirType = getStorage('UploadDirType');
			if(1 != uploadDirType) return this;
			var value = mGetCurrentDir();
			if(value) return this;
			
			var now = new Date();  
			var year = now.getFullYear().toString();
			var month = now.getMonth() + 1;
			if(month.toString().length === 1) month = '0' + month;
			
			value = "./Upload/"+year+month.toString()+"/";
			setStorage('YDCurrentDir1', value)
			return this;
		}
		return {
			popup: popup,
			widget: widget,
			destroyDialog: destroyDialog,
			setConfig: setConfig,
			setDefaultDir:setDefaultDir,
			cancelUpload: cancelUpload,
			selectActionFunction: null,
		};
	}
	global.FileManager = fn();
})(window);
