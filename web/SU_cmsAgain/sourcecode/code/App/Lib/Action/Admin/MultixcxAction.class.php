<?php
/**
 *  多端小程序
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 * 注意：模板最终放在App/Plugin/Multixcx目录下
 * 1）由于设置了伪静态规则，多端小程序不能放在tpl目录下
 * 2）不能作为php端模板使用，因为懒加载会无法找到文件
 */
class MultixcxAction extends AdminBaseAction{
    private $_cmsUrl = 'http://www.youdiancms.com';
    private $_apiUrl = 'http://api.youdiancms.com';
    /**
     * 字典数据
     */
    public function getDictionaryData(){
        //字典数据类型
        $AllTypes = explode(',', strtolower(trim($_POST['Type'])));
        $map = array();
        foreach ($AllTypes as $v){
            $map[$v] = 1;
        }
        $result['Data'] = array();
        import('@.Common.YdTemplate');
        //获取店铺装修所有字典数据
        if(isset($map['template'])){
            $allData = YdTemplate::getAllDictionary();
            $result['Data'] = array_merge($result['Data'], $allData);
        }

        //频道来源
        if(isset($map['channel']) || isset($map['channel1'])){
            $result['Data']['Channel'] = YdTemplate::getChannelSource($map);
        }

        //模板页面
        if(isset($map['templatepage'])){
            $m = D('Admin/TemplatePage');
            $params = array();
            $params['TemplateID'] = $_POST['TemplateID'];
            $params['Field'] = 'TemplatePageID,TemplatePageName';
            $data = $m->getTemplatePage($params);
            $result['Data']['TemplatePage'] = $data;
        }
        $this->adminApiReturn($result, '', 1);
    }

    /**
     * 获取小程序模板
     */
    public function GetTemplate(){
        $m = D('Admin/Template');
        $result['Data'] = $m->getTemplate($_POST);
        $this->adminApiReturn($result, '', 1);
    }

    /**
     * 查看小程序模板
     */
    public function FindTemplate(){
        $m = D('Admin/Template');
        $result['Data'] = $m->findTemplate($_POST['TemplateID'], $_POST);
        $this->adminApiReturn($result, '', 1);
    }

    /**
     * 创建一个空模板
     */
    public function CreateTemplate(){
        $m = D('Admin/Template');
        if(empty($_POST['TemplateName'])){
            $this->adminApiReturn(null, '模板名称不能为空！', 0);
        }
        $totalCount = $m->getTotalTemplateCount();
        $max = 20;
        if($totalCount > $max){
            $this->adminApiReturn(null, "创建模板失败，最多只能创建{$max}个模板！", 0);
        }
        $result = $m->createTemplate($_POST);
        if(false !== $result){
            //创建空模板后，将当前模板的数据直接返回
            $data['Data'] = $result;
            $this->adminApiReturn($data, L('OperateSuccess'), 1);
        }else{
            $this->adminApiReturn(null, L('OperateFail'), 0);
        }
    }

    /**
     * 删除小程序模板
     */
    public function DeleteTemplate(){
        $m = D('Admin/Template');
        $result = $m->deleteTemplate($_POST['TemplateID']);
        if(false !== $result){
            $this->adminApiReturn(null, L('DelSuccess'), 1);
        }else{
            $this->adminApiReturn(null, L('DelFail'), 0);
        }
    }

    /**
     * 设置默认小程序模板
     */
    public function SetDefaultTemplate(){
        $m = D('Admin/Template');
        $result = $m->setDefaultTemplate($_POST['TemplateID']);
        if(false !== $result){
            $this->adminApiReturn(null, L('OperateSuccess'), 1);
        }else{
            $this->adminApiReturn(null, L('OperateFail'), 0);
        }
    }

    /**
     * 设置修改模板
     */
    public function modifyTemplate(){
        if(empty($_POST['TemplateName'])){
            $this->adminApiReturn(null, '模板名称不能为空！', 0);
        }
        if(empty($_POST['ThemeColor'])){
            $this->adminApiReturn(null, '主题颜色不能为空！', 0);
        }
        $ThemeColor = strtolower($_POST['ThemeColor']);
        if('#ffffff'==$ThemeColor){
            $this->adminApiReturn(null, '主题颜色不能为白色！', 0);
        }
        $m = D('Admin/Template');
        $result = $m->modifyTemplate($_POST);
        if(false !== $result){
            $this->adminApiReturn(null, L('SaveSuccess'), 1);
        }else{
            $this->adminApiReturn(null, L('SaveFail'), 0);
        }
    }

    /**
     * 备份模板
     */
    function BackupTemplate(){
        $m = D('Admin/Template');
        $result = $m->backupTemplate($_POST['TemplateID']);
        if(false !== $result){
            $this->adminApiReturn(null, L('OperateSuccess'), 1);
        }else{
            $this->adminApiReturn(null, L('OperateFail'), 0);
        }
    }

    /**
     * 查看模板页面数据（仅装修端调用）
     */
    public function FindTemplatePage(){
        $m = D('Admin/TemplatePage');
        $type = intval($_POST['TemplatePageType']);
        if($type > 0){
            $TemplatePageID = $m->findDefaultTemplatePageID($type);
        }else{
            $TemplatePageID = $_POST['TemplatePageID'];
        }
        $_POST['HasTabbar'] = 1; //表示获取Tabbar
        $result['Data'] = $m->findTemplatePage($TemplatePageID, $_POST);
        $this->adminApiReturn($result, '', 1);
    }

    /**
     * 保存模板页
     */
    public function SaveTemplatePage(){
        if(empty($_POST['TemplateID'])){
            $this->adminApiReturn(null, 'TemplateID参数不能为空！', 0);
        }
        $TemplateID = $_POST['TemplateID'];
        //客户端会编码+，所以这里必须urldecode
        if(isset($_POST['TemplatePageContent'])){ //必须判断，否则在保存页面名称是会出问题
            decodeSpecialChars($_POST['TemplatePageContent']);
        }
        $IsDraft = intval($_POST['IsDraft']);
        $m = D('Admin/TemplatePage');
        $result = $m->saveTemplatePage($_POST['TemplatePageID'], $_POST);
        if(false !== $result){
            if(0 == $IsDraft){ //发布模板
                $result = $m->publishAllTemplatePage($TemplateID);
                $msg = (false === $result) ? '发布模板失败！' : '发布模板成功！';
            }else{
                $msg = L('SaveSuccess');
            }
            $this->adminApiReturn(null, $msg, 1);
        }else{
            $msg = (0==$IsDraft) ? '发布模板失败！' : L('SaveFail');
            $this->adminApiReturn(null, $msg, 0);
        }
    }

    /**
     * 删除模板页
     */
    public function DeleteTemplatePage(){
        $m = D('Admin/TemplatePage');
        $result = $m->deleteTemplatePage($_POST['TemplatePageID']);
        if(false !== $result){
            $this->adminApiReturn(null, L('DelSuccess'), 1);
        }else{
            $this->adminApiReturn(null, L('DelFail'), 0);
        }
    }

    /**
     * 添加自定义页面
     */
    public function AddTemplatePage(){
        if(empty($_POST['TemplateID'])){
            $this->adminApiReturn(null, '模板ID不存在！', 0);
        }
        if(empty($_POST['TemplatePageName'])){
            $this->adminApiReturn(null, '页面名称不能为空！', 0);
        }
        $m = D('Admin/TemplatePage');
        $result = $m->addTemplatePage($_POST);
        if($result > 0){
            $data = array();
            $data['TemplatePageID'] = $result;
            $data['TemplatePageType'] = 99;
            $this->adminApiReturn(array('Data'=>$data), L('AddSuccess'), 1);
        }else{
            $this->adminApiReturn(null, L('AddFail'), 0);
        }
    }

    /**
     * 获取模板页面数据（仅装修端使用）
     */
    public function getTemplateData(){
        $type = $_POST['Type'];
        import('@.Common.YdTemplate');
        $t = new YdTemplate();
        $data = array();
        switch($type){
            case 'goods':
                $count = isset($_POST['Count']) ? $_POST['Count'] : 20;
                $data = $t->getGoodsData($count, $_POST['Source']);
                break;
            case 'content':
                $data = $t->getInfoData($_POST);
                break;
            case 'channel': //分类页
                $params = array();
                $params['ParentID'] = $_POST['Source'];
                $data = YdTemplate::getChannelSource($params);
                break;
            case 'info': //通过频道模型获取信息
                $m = D('Admin/Template');
                $map = array('Article'=>30, 'Picture'=>31, 'Video'=>34, 'Product'=>36);
                $key = $_POST['ChannelModel'];
                if(isset($map[$key])){
                    $ChannelModelID = $map[$key];
                    $data = $m->getInfoByChannelModelID($ChannelModelID);
                }else{
                    $data = false;
                }
                break;
        }
        $result['Data'] = $data;
        //分类页装修，并选择列表样式，还需要将列表页的装修样式返回
        if($type=='content' && $_POST['Style']==3 && !empty($_POST['TemplateID'])){
            $m = D('Admin/TemplatePage');
            $style = $m->findListPageStyle($_POST['TemplateID']);
            $map = array(30=>'Article', 31=>'Picture', 34=>'Video', 36=>'Product');
            $ChannelModelID = ChannelModelID($_POST['Source']);
            //装修端必须知道所属频道模型ID
            $style['ChannelModel'] = isset($map[$ChannelModelID]) ? $map[$ChannelModelID] : 'Article';
            $result['Style'] = $style;
        }
        $this->adminApiReturn($result, '', 1);
    }

    /**
     * 设置小程序配置信息（授权时调用）
     */
    function setXcxInfo(){
        $m = D('Admin/Config');
        $result = $m->setXcxAppID($_POST['XcxType'], $_POST['XcxAppID']);
        if(false !== $result){
            $this->adminApiReturn(null, "设置成功", 1);
        }else{
            $this->adminApiReturn(null, "设置失败", 0);
        }
    }
    /**
     * 清除小程序配置信息（手工调用，其实就是清除本地微信小程序app配置项XcxAppID）
     */
    function clearXcxInfo(){
        $m = D('Admin/Config');
        $result = $m->setXcxAppID($_POST['XcxType'], '');
        if(false !== $result){
            $this->adminApiReturn(null, "设置成功", 1);
        }else{
            $this->adminApiReturn(null, "设置失败", 0);
        }
    }
    /**
     * 预览模板
     */
    function previewTemplate(){
        $result = array();
        $TemplateID = isset($_POST['TemplateID']) ? intval($_POST['TemplateID']) : 0;
        if(empty($TemplateID)){
            $this->adminApiReturn(null, '预览模板ID不能为空！', 0);
        }
        //tid为预览参数
        $url = get_current_url().'/App/Plugin/Multixcx/h5/index.html#/?tid='.$TemplateID;
        $fileName = Qrcode($url, 7, RUNTIME_PATH, "prevew.png");
        if(file_exists($fileName)){
            //直接返回一个图像BASE64字符串
            $base64 = base64_encode(file_get_contents($fileName));
            $result['Data']['Image'] = "data:image/png;base64,{$base64}";
            $result['Data']['Title'] = "手机扫码预览";
            $result['Data']['Url'] = $url;
            $result['Data']['ButtonText'] = '在PC端预览';
            $this->adminApiReturn($result, "", 1);
        }else{
            $this->adminApiReturn(null, "预览失败", 0);
        }
    }

    /**
     * 安装小程序模板
     */
    function installTemplate(){
        $TemplateNo = YdInput::checkLetterNumber($_POST['TemplateNo']);
        if(empty($TemplateNo)){
            $this->adminApiReturn(null, '模板编号不能为空！', 0);
        }
        import("@.Common.YdApi");
        $api = new YdApi();
        $data = $api->findXcxTemplate($TemplateNo);
        if(empty($data)){
            $errMsg = $api->getLastError();
            $this->adminApiReturn(null, "安装模板失失败 {$errMsg}", 0);
        }
        $m = D('Admin/Template');
        $isExist = $m->templateNameExist($data['TemplateName']);
        if($isExist){
            $this->adminApiReturn(null, "安装模板失失败，模板【{$data['TemplateName']}】已经存在！", 0);
        }
        $result = $m->installTemplate($data, $TemplateNo);
        if(false !== $result){
            $this->adminApiReturn(null, "安装模板成功", 1);
        }else{
            $this->adminApiReturn(null, "安装模板失败", 0);
        }
    }

    /**
     * 获取小程序模板分类
     */
    function getXcxTemplateClass(){
        $url = "{$this->_cmsUrl}/index.php/Api/GetSpecial";
        $params = array();
        $json = yd_curl_get($url, $params);
        exit($json);
    }

    /**
     * 获取小程序模板
     */
    function getXcxTemlate(){
        $url = "{$this->_cmsUrl}/index.php/Api/GetInfo";
        $params = array();
        $params['ChannelID'] = 129;  //129为小程序模板频道ID
        $params['SpecialID'] = intval($_REQUEST['SpecialID']);
        $params['Keywords'] = $_REQUEST['Keywords']; //按关键词查询
        $json = yd_curl_get($url, $params);
        if(!empty($json)){
            $data = json_decode($json, true);
            $newData = array();
            //如果源站是https，这里是http也没有关系（但是必须部署了https，估计是浏览器自动转换）
            //$dir = 'http://www.youdiancms.com/Upload/';
            //youdiancms.com由于使用iframe不能启用https，所以必须换个域名
            $dir = 'https://template.wangzhan31.com/Upload/';
            foreach($data['Data'] as $v){
                $newData[] = array(
                    'InfoID'=>$v['InfoID'],
                    'TemplateTitle'=>$v['InfoTitle'],
                    'TemplatePicture'=>"{$dir}template/{$v['f1']}.jpg",
                    'TemplateQr'=>"{$dir}qr/{$v['f1']}.png",
                    'TemplateNo'=>$v['f1'],  //模板编号
                    'ChannelID'=>$v['ChannelID'],
                );
            }
            $data['Data'] = $newData;
            $json = json_encode($data);
        }
        exit($json);
    }

    /*====================小程序发布API接口 开始====================*/
    private function _getApiUrl($action, $module=false){
        if(empty($module)){
            $map = array('wx'=>'WxAuth', 'bd'=>'BdAuth', 'zj'=>'ZjAuth', 'zfb'=>'ZfbAuth', 'qh'=>'QhAuth');
            $key = $_POST['XcxType'];
            $module = $map[$key];
        }
        $url = "{$this->_apiUrl}/index.php/{$module}/{$action}";
        return $url;
    }

    private function _getAuthParams(){
        $params = $_POST;
        $params['Host'] = $_SERVER['HTTP_HOST'];
        return $params;
    }

    /**
     * 获取服务器域名
     */
    function getServerDomain(){
        $apiUrl = $this->_getApiUrl('getServerDomain');
        $params = $this->_getAuthParams();
        $result = yd_curl_post($apiUrl, $params);
        exit($result);
    }

    /*
  *为授权方设置服务器域名
  * */
    function setServerDomain(){
        $apiUrl = $this->_getApiUrl('setServerDomain');
        $params = $this->_getAuthParams();
        $result = yd_curl_post($apiUrl, $params);
        exit($result);
    }

    /**
     * 获取服务器域名
     */
    function getBusinessDomain(){
        $apiUrl = $this->_getApiUrl('getBusinessDomain');
        $params = $this->_getAuthParams();
        $result = yd_curl_post($apiUrl, $params);
        exit($result);
    }

    /*
  *为授权方设置服务器域名
  * */
    function setBusinessDomain(){
        $apiUrl = $this->_getApiUrl('setBusinessDomain');
        $params = $this->_getAuthParams();
        $result = yd_curl_post($apiUrl, $params);
        exit($result);
    }

    /**
     * 是否授权
     */
    function findAuthorizer(){
        $apiUrl = $this->_getApiUrl('findAuthorizer');
        $params = $this->_getAuthParams();
        $result = yd_curl_post($apiUrl, $params);
        exit($result);
    }
    /**
     * 获取体验码
     */
    function getExperienceQR(){
        $apiUrl = $this->_getApiUrl('getExperienceQR');
        $params = $this->_getAuthParams();
        $result = yd_curl_post($apiUrl, $params);
        exit($result);
    }
    /**
     * 获取体验者列表
     * 参数，id，WeChatID（微信号）
     */
    function getTester(){
        $apiUrl = $this->_getApiUrl('getTester');
        $params = $this->_getAuthParams();
        $result = yd_curl_post($apiUrl, $params);
        exit($result);
    }

    /**
     * 绑定微信用户为体验者
     * 参数，id，WeChatID（微信号）
     */
    function bindTester(){
        $apiUrl = $this->_getApiUrl('bindTester');
        $params = $this->_getAuthParams();
        $result = yd_curl_post($apiUrl, $params);
        exit($result);
    }

    /**
     * 删除微信用户为体验者
     * 参数，id，WeChatID（微信号）
     */
    function unbindTester(){
        $apiUrl = $this->_getApiUrl('unbindTester');
        $params = $this->_getAuthParams();
        $result = yd_curl_post($apiUrl, $params);
        exit($result);
    }

    /**
     * 提交审核 id
     */
    function submitAudit(){
        $apiUrl = $this->_getApiUrl('submitAudit');
        $params = $this->_getAuthParams();
        $result = yd_curl_post($apiUrl, $params);
        exit($result);
    }

    /**
     * 撤销授权者 id
     * 回到未授权的状态 第0步
     */
    function returnUnAuthorized(){
        $apiUrl = $this->_getApiUrl('returnUnAuthorized');
        $params = $this->_getAuthParams();
        $result = yd_curl_post($apiUrl, $params);
        exit($result);
    }

    /**
     * 撤销审核步骤
     * 回到刚授权的状态 第1步
     */
    function returnAuthorized(){
        $apiUrl = $this->_getApiUrl('returnAuthorized');
        $params = $this->_getAuthParams();
        $result = yd_curl_post($apiUrl, $params);
        exit($result);
    }


    /**
     * 查询小程序用户隐私保护指引
     */
    function getPrivacySetting(){
        $apiUrl = $this->_getApiUrl('getPrivacySetting');
        $params = $this->_getAuthParams();
        $result = yd_curl_post($apiUrl, $params);
        exit($result);
    }
    /**
     * 配置小程序用户隐私保护指引
     */
    function setPrivacySetting(){
        $apiUrl = $this->_getApiUrl('setPrivacySetting');
        $params = $this->_getAuthParams();
        $result = yd_curl_post($apiUrl, $params);
        exit($result);
    }
    /*====================小程序发布API接口 结束====================*/

    /**
     * 统计访客
     */
    function statVisitor(){
        $m = D('Admin/Visitor');
        $XcxType = intval($_POST['XcxType']);
        $type = intval($_POST['Type']);
        $data = array();
        switch ($type){
            case 1: //按天统计
                $data = $m->statVisitorByDay($_POST);
                break;
            case 2: //按分钟统计
                $data = $m->statVisitorByHourMinute($_POST);
                break;
            case 3://按终端类型统计
                $data = $m->statVisitorByXcxType($_POST);
                break;
        }
        $result = array();
        $result['Data'] = $data;
        $result['StatData']['TotalVisitorPVCount'] = $m->getVisitorPVCount('', '', $XcxType);
        $result['StatData']['TotalVisitorIPCount'] = $m->getVisitorIPCount('', '', $XcxType);
        $this->adminApiReturn($result, "", 1);
    }
}