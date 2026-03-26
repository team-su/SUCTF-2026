<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class ImportwxAction extends AdminBaseAction {
	function index(){
		header("Content-Type:text/html; charset=utf-8");
        $TextEditor = $GLOBALS['Config']['TextEditor'];
        if(empty($TextEditor)) $TextEditor = 1; //默认为CK编辑器
        $Channel = $this->_getChannel();

        $this->assign('TextEditor', $TextEditor);
        $this->assign('Channel', $Channel);
        $this->assign('Action', __URL__.'/save' );
		$this->display();
	}

    /**
     * 获取频道数据
     */
	private function _getChannel(){
        //获取频道数据
        $m = D('Admin/Channel');
        $gid = intval(session('AdminGroupID'));
        $Channel = ($gid==1) ? $m->getChannelList(0,-1) : $m->getChannelPurview(1, $gid);
        $data = array();
        $map = array('32'=>1, '33'=>1); //排除单页和链接频道
        foreach($Channel as $k=>$v){
            $hasChild = (int)$v['HasChild'];
            $modelID = (int)$v['ChannelModelID'];
            if(0==$hasChild && isset($map[$modelID])) continue;
            $data[] = array(
                'ChannelID'=>$v['ChannelID'],
                'ChannelName'=>$v['ChannelName'],
                'ChannelModelID'=>$modelID
            );
        }
        return $data;
    }

    /**
     * 导入内容
     */
    function import(){
        $url = trim($_POST["WxUrl"]);
        if(0!==stripos($url, 'https://')){
            $this->ajaxReturn(null, "微信文章链接无效！" , 0);
        }
        //之前不加可以正常获取内容，2024-04-29 加上才行，否则会301转向
        $suffix = '?nwr_flag=1#wechat_redirect';
        if(false===stripos($url, $suffix)){
            $url .= $suffix;
        }
        $result = $this->getWxContent($url);
        if(is_array($result)){
            $this->ajaxReturn($result, '导入微信文章成功!' , 1);
        }else{
            $this->ajaxReturn(null, "导入微信文章失败！{$result}" , 0);
        }
    }

    private function getWxContent($url){
        try{
            //模拟微信浏览器
            $options['CURLOPT_USERAGENT'] = "Mozilla/5.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/5.0";
            $html = yd_curl_get($url, false, 15, $options);
            if(empty($html)) return '无法获取页面内容';
            import("@.Common.PhpQuery");
            phpQuery::newDocument($html);
            $title = trim(pq("h1")->text());  //获取标题
            if(empty($title)) return '无法获取标题';
            $content = trim(pq("#js_content")->html()); //获取文章内容
            if(empty($content)) return '无法获取内容';
            $data = array();
            $data['InfoTitle'] = $title;
            $data['InfoContent'] = $this->parseContent($content);
            return $data;
        }catch(Exception $e){
            return $e->getMessage();
        }
    }

    /**
     * 解析内容
     */
    private function parseContent($content){
        $doc = phpQuery::newDocument($content);
        //微信文章中的远程资源做了防盗链处理，必须要下载到本地
        //1、解析图片资源 <img class="wxw-img" data-ratio="1.7273" data-src="https://mmbiz.qpic.cn/g/640?wx_fmt=gif" data-type="gif" data-w="55" data-width="100%" style="" />
        $list = array('data-src', 'data-ratio', 'data-type', 'data-w', 'data-width', 'data-cropselx1','data-cropselx2', 'data-cropsely1', 'data-cropsely2', 'data-galleryid', 'data-s');
        foreach(pq("img") as $img){ //$img是纯DOM节点, 将它变为phpQuery对象： pq($img);
            $imgSelector = pq($img);
            $src = $imgSelector->attr("data-src"); //需要下载图片资源到本地
            if(!empty($src)){
                $type = $imgSelector->attr("data-type"); //图片类型
            }else{
                $src = $imgSelector->attr("src");
                $type = ''; //自动获取扩展名
            }
            $src = $this->downloadResource($src, $type); //下载资源到本地
            $imgSelector->attr("src", $src);
            //删除不需要的属性
            foreach($list as $attrName){
                $imgSelector->removeAttr($attrName);
            }
        }

        //2、解析音频、视频资源
        // <video src=”http://www.abc.com/test.mp4″ controls></video>
        //<audio  src=”http://www.abc.com/test.mp3″ controls></audio>
        foreach(pq("video,audio") as $v){
            $selector = pq($v);
            $src = $selector->attr("src");
            if(!empty($src)){
                $src = $this->downloadResource($src); //下载资源到本地
                $selector->attr("src", $src);
            }
        }

        //3、解析其他资源
        //删除：<section data-id="88571" data-tools="13325编辑器" label="Powered by 1xx.com">
        //<p data-label="Power by：chajian.XX.com" data-tools="XX插件粉丝阅读数" style="display:none;">&nbsp;</p>
        foreach(pq("section,p") as $v){
            $selector = pq($v);
            $selector->removeAttr("data-tools");
            $selector->removeAttr("data-label");
            $selector->removeAttr("label");
        }

        //<iframe class="video_iframe rich_pages" data-vidtype="2" data-mpvid="wxv_3155937738593140739"
        // data-cover="http%3A%2F%2Fmmbiz.qpic.cn%2Fmmbiz_jpg%2FUC9dmwx_fmt%3Djpeg"  style="border-radius: 4px;" data-src="https://mp.weixin.qq.com/mp/readtemplate?t=pages/video_player_tmpl&amp;action=mpvideo&amp;auto=0&amp;vid=wxv_3155937738593140739"></iframe>

        //4、内联样式图片提取：
        //场景1：<svg style="background-image: url(&quot;https://mmbiz.qpic.cn/mmbiz_jpg/UC9dmuS7dCzcDATeQ/640?wx_fmt=jpeg&quot;);background-attachment: scroll;">
        //场景2：<svg style='background-image: url("https://mmbiz.qpic.cn/mmbiz_jpg/UC9dmuS7dCzcDATeQ/640?wx_fmt=jpeg");background-attachment: scroll;'>
        $content = $doc->markup();
        $search = array('powered-by="xiumi.us"'); //删除其他特殊字符
        $content = str_ireplace($search, '', $content);
        $that = $this;
        //替换所有通用地址
        $content = preg_replace_callback('/"(https:\/\/[\w\W]+?)"/i', function($matches) use ($that){
            // 通常: $matches[0]是完成的匹配；$matches[1]是第一个捕获子组的匹配
            $myurl = $matches[1];
            $type = $that->getResourceExt($myurl);
            $src = $that->downloadResource($myurl, $type);
            return "\"{$src}\"";
        }, $content);
        return $content;
    }

    /**
     * 获取资源扩展名
     */
    public function getResourceExt($url){
        $key = 'wx_fmt=';
        $pos = stripos($url, $key);
        if(false !== $pos){ //举例：https://mmbiz.qpic.cn/mmbiz_jpg/UC9dmuS7dCzcDATeQ/640?wx_fmt=jpeg
            $query = parse_url($url, PHP_URL_QUERY);
            $data = array();
            parse_str($query, $data);
            $type = isset($data['wx_fmt']) ? $data['wx_fmt'] : '';
        }else{
            $pos = stripos($url, '?');
            if(false !== $pos){
                $newUrl = substr($url, 0, $pos);
                $type = yd_file_ext($newUrl);
            }else{
                $type = yd_file_ext($url);
            }
        }
        if(empty($type)) $type = 'jpg';  //或无法自动获取图片，必须使用一个默认值，否则会导致图片无法下载
        return $type;
    }

    /**
     * 下载图片资源
     */
    public function downloadResource($url, $type=''){
        //举例：https://mmbiz.qpic.cn/mmbiz_png/Uia5LRxdXwtTZBl3TUpAGNfAv9rT4h0RicJNpyHSibUMrtLQPdvxwHzuibyMYZ5bjI9yCSgoPVvH2l9ia3iaYUJ2lfwg/640?wx_fmt=png
        if($url == "") return '';
        if('/'==substr($url, 0, 1)) return $url; //本地地址直接返回
        if(empty($type)){ //自动获取扩展名
            $type = $this->getResourceExt($url);
        }
        $type = strtolower($type);
        $map = array('gif'=>1, 'jpg'=>1, 'jpeg'=>1, 'jpe'=>1, 'bmp'=>1, 'png'=>1, 'tiff'=>1, 'tif'=>1, 'ico'=>1, 'mp4'=>1, 'mp3'=>1);
        if( !isset($map[$type])) return '';
        $content = @file_get_contents($url);
        if( empty($content) ) return '';
        $uploadDir = GetUploadDir();
        $fileName = $uploadDir.date("YmdHis").rand_string(4).'.'.$type;;
        @file_put_contents($fileName,  $content );
        $fileUrl = $this->WebInstallDir.substr($fileName, 2);
        return $fileUrl;
    }

    /**
     * 保存内容
     */
    function save(){
        //Infocontent必须为utf8mb4_unicode_ci编码，如：不加此行指令: abc💫，会保存为abc???，加上就能正常保存
        //MySQL从版本5.5.3开始支持UTF-8字符集中的全范围Unicode，即UTF-8mb4字符集。这个字符集可以存储四个字节的Unicode字符，
        //包括来自Unicode 6.0的一些新增的字符。UTF-8mb4字符集不仅可以存储日常的文本数据，还可以存储包括表情符号在内的各种符号表。
        C('DB_CHARSET', 'utf8mb4');
        $data = $this->_checkData();
        $m = D('Admin/Info');
        $id = intval($_POST['InfoID']);
        if($id > 0){ //保存
            $m->where("InfoID={$id}")->setField($data);
        }else{ //添加
            $id = $m->add($data);
        }
        if($id > 0){
            WriteLog("保存微信导入文章成功！{$data['InfoTitle']}，ID:{$id}");
            $this->ajaxReturn($id, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    private function _checkData(){
        $m = D('Admin/Channel');
        $ChannelID = (int)$_POST["ChannelID"];

        if(empty($_POST['InfoTitle'])){
            $this->ajaxReturn(null, '文章标题不能为空！' , 0);
        }
        if( $m->hasChildChannel($ChannelID) ){
            $this->ajaxReturn(null, '只能选择子频道！' , 0);
        }
        if(empty($_POST['InfoContent'])){
            $this->ajaxReturn(null, '文章内容不能为空！' , 0);
        }

        $data = array();
        $data['InfoTitle'] = $_POST['InfoTitle'];
        $data['InfoContent'] = $_POST['InfoContent'];
        //$data['InfoContent'] = "133a\"bc💫先来自查💫2223";  //用于测试乱码

        $data['ChannelID'] = $ChannelID;
        $data['InfoTime'] = date("Y-m-d H:i:s");
        $data['IsCheck'] = 1;
        //设置以下参数默认值
        $data['ReadLevel'] = '';
        $data['LabelID'] = '';
        $data['SpecialID'] = '';
        $data['ChannelIDEx'] = '';
        $data['f1'] = '';
        $data['f2'] = '';
        $data['f3'] = '';
        $data['f4'] = '';
        $data['f5'] = '';
        return $data;
    }
}