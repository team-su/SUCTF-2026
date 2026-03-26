<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
if (!defined('APP_NAME')) exit();
class YdVideoPlayer{	
	//属性默认值
	private $_attr = array('width'=>'100%','height'=>'450px', 'type'=>'auto',
			'allowfullscreen'=>1,'autostart'=>0);
	function __construct($attr=false){
		if( is_array($attr) ){
			$this->_attr = array_merge($this->_attr, $attr);
		}
	}

	/**
	 * 设置播放器属性
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name,$value) {
		$this->_attr[$name]  =   $value;
	}
	
	/**
	 * 获取播放器属性
	 * @param string $name
	 */
	public function __get($name) {
		return isset($this->_attr[$name])?$this->_attr[$name]:null;
	}
	
	public function attr($data){
		if( is_array($data)){
			$this->_attr = $data;
		}
		return $this;
	}
	
	/**
	 * 输出播放代码
	 */
	function render(){
		//优先判断第三方视频网站播放器，使用iframe嵌入
		$src = $this->_attr['src'];
		$ext = $this->getExt($src);
		$type = strtolower( $this->_attr['type'] );
		$player = 'html5';
		if( $this->isPlatform($src) ){ //如果没有扩展名则使用iframe通用代码，主要用于优酷和土豆的通用播放代码
			$player = ($type == 'auto') ? 'iframe' : $type;
		}else{
			if( GROUP_NAME == 'Wap'){ //手机端只能用HTML5播放
				$player = 'html5';
			}else{
				if($type == 'auto'){ //自动匹配播放器
					$list = array('mp4'=>'mp4', 'flv'=>'flv', 'swf'=>'swf','mp3'=>'mp3',
							'gif'=>'img','png'=>'img','jpg'=>'img','bmp'=>'img','tif'=>'img','tiff'=>'img',
					);
					if($ext=='mp4' || $ext=='mp3'){
                        $player = 'html5';
                    }else{
                        $player = key_exists($ext, $list) ? $list[$ext] : 'embed';
                    }
				}
			}
		}
		$html = method_exists($this, $player) ? $this->$player() : '';
		return $html;
	}
	
	/**
	 * 获取视频扩展名
	 * @param string $src 视频路径（可能是url、本地文件）
	 */
	function getExt($src){
		$ext = '';
		$pos = strrpos($src, '.');
		if( $pos !== false ){
			$ext = substr($src, $pos+1);
			$ext = strtolower($ext);
		}
		return $ext;
	}
	
	//判断是否是第三方视频平台
	function isPlatform($src){
		//常见的视频格式：avi、wmv、mpeg、mp4、mov、mkv、flv、f4v、m4v、rmvb、rm、3gp、dat、ts、mts、vob
		//sohu:   http://tv.sohu.com/upload/static/share/share_play.html#80270673_6445627_0_9001_0   总是自动播放
		//youku: http://player.youku.com/embed/XMTI1NzMxOTYzNg==
		//tudou: http://www.tudou.com/programs/view/html5embed.action?type=2&code=J9x8ZO1x-wI&lcode=-PS_B5_EeG8&resourceId=0_06_05_99
		$ext = $this->getExt($src);
		if( empty($ext) || strlen($ext) >=5 ){
			return true;
		}else{
			return false;
		}
	}
	
	function mp4(){
		if( empty( $this->_attr['src'] ) ) return false;
		$width = $this->_attr['width'];
		$height = $this->_attr['height'];
		$allowfullscreen = $this->_attr['allowfullscreen'];
		$autostart01 = $this->_attr['autostart'];
		$autostart = ($autostart01 == 1) ? 'true' : 'false';
		$src = $this->_attr['src'];
		$root  =  __ROOT__;
		
		$html = "<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-4445535411111'
			codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0'
			width='{$width}' height='{$height}'>
				<param name='movie' value='{$root}/Public/effect/flvplayer.swf' />
				<param name='quality' value='high' />
				<param name='wmode' value='transparent'>
				<param name='Play' value='{$autostart}'>
				<param name='allowFullScreen' value='{$allowfullscreen}' />
				<param name='FlashVars' value='vcastr_file={$src}&IsAutoPlay={$autostart01}' /> 
				<embed src='{$root}/Public/effect/flvplayer.swf' allowfullscreen='true'  quality='high'  wmode='transparent'  
				 flashvars='vcastr_file={$src}&IsAutoPlay={$autostart01}'
				 pluginspage='http://www.macromedia.com/go/getflashplayer'  autostart='{$autostart}' 
				 type='application/x-shockwave-flash' width='{$width}' height='{$height}' />
		</object>";
		return $html;
	}
	
	function flv(){
		if( empty( $this->_attr['src'] ) ) return false;
		$width = $this->_attr['width'];
		$height = $this->_attr['height'];
		$allowfullscreen = $this->_attr['allowfullscreen'];
		$autostart01 = $this->_attr['autostart'];
		$autostart = ($autostart01 == 1) ? 'true' : 'false';
		$src = $this->_attr['src'];
		$root  =  __ROOT__;
		
		$html = "<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-4445535411111'
			codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0'
			width='{$width}' height='{$height}'>
				<param name='movie' value='{$root}/Public/effect/flvplayer.swf' />
				<param name='quality' value='high' />
				<param name='wmode' value='transparent'>
				<param name='Play' value='{$autostart}'>
				<param name='allowFullScreen' value='{$allowfullscreen}' />
				<param name='FlashVars' value='vcastr_file={$src}&IsAutoPlay={$autostart01}' /> 
				<embed src='{$root}/Public/effect/flvplayer.swf' allowfullscreen='true'  quality='high' wmode='transparent'  
				 flashvars='vcastr_file={$src}&IsAutoPlay={$autostart01}'
				 pluginspage='http://www.macromedia.com/go/getflashplayer'  autostart='{$autostart}' 
				 type='application/x-shockwave-flash' width='{$width}' height='{$height}' />
		</object>";
		return $html;
	}
	
	function swf(){
		if( empty( $this->_attr['src'] ) ) return false;
		$width = $this->_attr['width'];
		$height = $this->_attr['height'];
		$allowfullscreen = $this->_attr['allowfullscreen'];
		//swf总会自动播放，不支持打开时暂停
		$autostart01 = $this->_attr['autostart'];
		$autostart = ($autostart01 == 1) ? 'true' : 'false';
		$src = $this->_attr['src'];
		
		$html = "<object classid=\"clsid:27CDB6E-AE6D-11cf-96B8-444553540000\"
				codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0\"
				width=\"{$width}\"  height=\"{$height}\" align=\"center\">
				<param name=\"movie\" value=\"{$src}\">
				<param name=\"quality\" value=\"high\">
				<param name=\"wmode\" value=\"transparent\">
				<param name=\"Play\" value=\"{$autostart}\">
				<param name=\"wmode\" value=\"transparent\">
				<embed src=\"{$src}\" width=\"{$width}\" height=\"{$height}\" wmode=\"transparent\" 
					align=\"center\" quality=\"high\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\"
					type=\"application/x-shockwave-flash\" autostart=\"{$autostart}\" >
				</embed>
			</object>";
		return $html;
	}
	
	function html5(){
		if( empty( $this->_attr['src'] ) ) return false;
		$width = $this->_attr['width'];
		$height = $this->_attr['height'];
		$allowfullscreen = $this->_attr['allowfullscreen'];
		$autostart = ($this->_attr['autostart'] == 1) ? 'autoplay="autoplay"' : '';
		$src = $this->_attr['src'];

		$html = "<video width=\"{$width}\" height=\"{$height}\" ";
		$html .=" src=\"{$src}\" controls=\"controls\" $autostart >";
		$html .=" </video>";
		return $html;
	}
	
	function iframe(){
		if( empty( $this->_attr['src'] ) ) return false;
		$html  = "<iframe height=\"{$this->_attr['height']}\" width=\"{$this->_attr['width']}\" ";
		$html .= " src=\"{$this->_attr['src']}\" frameborder=\"0\" ";
		if( $this->_attr['allowfullscreen'] == 1 ){
			$html .= ' allowfullscreen';
		}
		$html .= " ></iframe>";
		return $html;
	}
	
	function img(){
		if( empty( $this->_attr['src'] ) ) return false;
		$html = "<img src=\"{$this->_attr['src']}\" height=\"{$this->_attr['height']}\" width=\"{$this->_attr['width']}\" />";
		return $html;
	}
	
	function mp3(){
		if( empty( $this->_attr['src'] ) ) return false;
		$width = '445px'; //$this->_attr['width'];
		$title = urlencode($this->_attr['title']);
		$height = '70px'; //$this->_attr['height'];
		$allowfullscreen = $this->_attr['allowfullscreen'];
		//swf总会自动播放，不支持打开时暂停
		$autostart01 = $this->_attr['autostart'];
		$autostart = ($autostart01 == 1) ? 'true' : 'false';
		$src = $this->_attr['src'];
		
		$url = __ROOT__.'/Public/effect/mp3player.swf';
		//酷播SingleMP3Player音乐播放器V1.0
		$html = "<object classid=\"clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\" codebase=\"http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0\" id=\"CuPlayer\" width=\"{$width}\" height=\"{$height}\" align=\"middle\">
			<param name=\"allowScriptAccess\" value=\"sameDomain\" />
			<param name=\"movie\" value=\"{$url}?musicfile={$src}&musictitle={$title}\" />
			<param name=\"quality\" value=\"high\" />
			<param name=\"wmode\" value=\"transparent\">
			<param name=\"allowfullscreen\" value=\"{$allowfullscreen}\" />
			<embed src=\"{$url}?musicfile={$src}&musictitle={$title}&is_play=false\" autostart=\"{$autostart}\" width=\"{$width}\" height=\"{$height}\" quality=\"high\" wmode=\"transparent\" swLiveConnect=true name=\"CuPlayer\" align=\"middle\" allowScriptAccess=\"sameDomain\" type=\"application/x-shockwave-flash\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\" allowfullscreen=\"{$allowfullscreen}\" />
		</object>";
		return $html;
		
	}
	
	//通用播放
	function embed(){
		if( empty( $this->_attr['src'] ) ) return false;
		$width = $this->_attr['width'];
		$height = $this->_attr['height'];
		$allowfullscreen = $this->_attr['allowfullscreen'];
		$autostart01 = $this->_attr['autostart'];
		$autostart = ($autostart01 == 1) ? 'true' : 'false';
		$src = $this->_attr['src'];
		$html = "<embed width=\"{$width}\" height=\"{$height}\" border=\"0\" showdisplay=\"0\" showcontrols=\"1\" ";
		$html .= " autostart=\"{$autostart}\" autorewind=\"0\" playcount=\"0\" wmode=\"transparent\" ";
		$html .= " moviewindowheight=\"{$height}\" moviewindowwidth=\"{$width}\" filename=\"{$src}\" src=\"{$src}\">";
		$html .= " </embed>";
		return $html;
	}
	

}