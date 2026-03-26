<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class ConfigAction extends AdminBaseAction {
    //系统设置编辑
    function basic(){
        $m = D('Admin/Config');
        $data = $m->getConfig('basic'); //配置数据不从缓存中提取
        $this->assign('WebName', $data['WEB_NAME'] );
        $this->assign('WebUrl', $data['WEB_URL'] );
        $this->assign('WebIcon', $data['WEB_ICON'] );
        $this->assign('WebURL', $data['WEB_URL'] );
        $this->assign('WebIcp', $data['WEB_ICP'] );
        $this->assign('WebICP', $data['WEB_ICP'] );
        $this->assign('WebStatus', $data['WEB_STATUS'] );
        $this->assign('WebCloseReason', $data['WEB_CLOSE_REASON'] );
        $this->assign('WebLogo', $data['WEB_LOGO'] );
        $this->assign('CheckUpdate', $data['CheckUpdate'] );

        $this->assign('Action', __URL__.'/saveBasic' );
        $this->display();
    }

    function email(){
        $m = D('Admin/Config');
        $data = $m->getConfig('basic'); //配置数据不从缓存中提取
        $data['EMAIL_PASSWORD'] = HideSensitiveData($data['EMAIL_PASSWORD']);
        $this->assign('EmailSender', $data['EMAIL_SENDER'] );
        $this->assign('EmailAccount', $data['EMAIL_ACCOUNT'] );
        $this->assign('EmailSmtp', $data['EMAIL_SMTP'] );
        $this->assign('EmailPassword', $data['EMAIL_PASSWORD'] );
        $this->assign('EmailWay', $data['EMAIL_WAY'] );
        $this->assign('EmailPort', $data['EMAIL_PORT'] );
        $this->assign('Action', __URL__.'/saveEmail' );
        $this->display();
    }

    function testEmail(){
        //sendmail(  '发件人账号','发件人姓名','收件人帐号','邮件标题','内容','邮箱账号','邮箱密码','smtp服务器,false,false,端口,协议);
        $username = $_POST['EmailAccount'];
        $pwd = $_POST['EmailPassword'];
        $smtp = $_POST['EmailSmtp'];
        $fromname = $_POST['EmailSender'];
        $port = $_POST['EmailPort'];
        $way = $_POST['EmailWay'];
        if($way=='ssl' && !function_exists('openssl_open') ){
            $this->ajaxReturn(null, '系统不支持OpenSSL组件，请开启，或选择TLS方式发送邮件！' , 3);
        }

        if( !function_exists('fsockopen') ){
            $this->ajaxReturn(null, '系统不支持fsockopen，无法发送邮件' , 3);
        }

        import("@.Common.YdSafe");
        if(YdSafe::isSensitiveData($pwd)){ //未修改则从数据库获取
            $m = D('Admin/Config');
            $pwd = $m->getConfigItem('EMAIL_PASSWORD');
        }
        $b = sendmail($username, $fromname, $username, L('EmailTestTitle'), L('EmailTestContent'), $username, $pwd, $smtp, false, false, $port, $way);
        WriteLog("发送测试邮件！", array('LogType'=>1, 'UserAction'=>'设置 -> 邮箱设置'));
        if( $b ){
            $this->ajaxReturn(null, '邮箱设置正确!' , 1);
        }else{
            $this->ajaxReturn(null, '邮箱设置错误！'.PHP_MAILER_ERROR , 0);
        }
    }

    function saveEmail(){
        $fieldMap = array(
            'EMAIL_SENDER'=>2, 'EMAIL_ACCOUNT'=>2, 'EMAIL_SMTP'=>2, 'EMAIL_PASSWORD'=>2, 'EMAIL_WAY'=>2,
            'EMAIL_PORT'=>1
        );
        $data = GetConfigDataToSave($fieldMap);
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            unset($data['EMAIL_PASSWORD']);
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    //保存配置
    function saveBasic(){
        //保存调试配置[无需缓存到runtime]=======================
        $debugFile = APP_DATA_PATH.'app.debug';
        if( isset($_POST['APP_DEBUG']) && $_POST['APP_DEBUG'] == 1 ){
            @touch( $debugFile );
        }else{
            @unlink( $debugFile );
        }
        //===========================================
        $fieldMap = array(
            'WEB_NAME'=>2,              'WEB_ICON'=>2, 'WEB_URL'=>2, 'WEB_ICP'=>2,
            'WEB_STATUS'=>1, 'WEB_CLOSE_REASON'=>2, 'WEB_LOGO'=>2,    'CheckUpdate'=>1,
        );
        $data = GetConfigDataToSave($fieldMap);

        $data['WEB_ICP'] = strip_tags($data['WEB_ICP'], "<span><a>"); //备案信息里可能会加入a标签
        $data['WEB_CLOSE_REASON'] = strip_tags($data['WEB_CLOSE_REASON'], "<div><span><br><p>");
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    function seo(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('seo'); //配置数据不从缓存中提取

        //搜索引擎优化设置
        $this->assign('Title', $data['TITLE'] );
        $this->assign('Keywords', $data['KEYWORDS'] );
        $this->assign('Description', $data['DESCRIPTION'] );

        $this->assign('Action', __URL__.'/saveSeo' );
        $this->display();
    }

    function saveSeo(){
        //将中文逗号替换成英文逗号
        $_POST['KEYWORDS'] = str_replace('，', ',', $_POST['KEYWORDS']);
        $fieldMap = array('TITLE'=>2,   'KEYWORDS'=>2,  'DESCRIPTION'=>2);
        $data = GetConfigDataToSave($fieldMap);
        //必须替换回车换行，描述内容不支持换行
        $data['DESCRIPTION'] = str_ireplace(array("\n", "\r"), '', trim($data['DESCRIPTION']));
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    /**
     * 站长统计设置
     */
    function stat(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('stat');

        $this->assign('StatUserName', $data['STAT_USERNAME'] );
        $this->assign('StatUserPwd', $data['STAT_USERPWD'] );
        $this->assign('StatCode', $data['STAT_CODE'] );
        $this->assign('AsyncStatCode', $data['ASYNC_STAT_CODE'] );
        $this->assign('StatEnable', $data['STAT_ENABLE'] );

        $this->assign("Action", __URL__.'/SaveStat');
        $this->display();
    }

    /**
     * 保存站长统计设置
     */
    function saveStat(){
        if (get_magic_quotes_gpc()) {
            $_POST['STAT_CODE'] = stripslashes($_POST['STAT_CODE']);
            $_POST['ASYNC_STAT_CODE'] = stripslashes($_POST['ASYNC_STAT_CODE']);
        }
        if(yd_contain_php($_POST['STAT_CODE'])){
            $this->ajaxReturn(null, "同步统计脚本代码不能包含php代码" , 0);
        }
        if(yd_contain_php($_POST['ASYNC_STAT_CODE'])){
            $this->ajaxReturn(null, "异步统计脚本代码不能包含php代码" , 0);
        }

        $fieldMap = array('STAT_USERNAME'=>2,  'STAT_USERPWD'=>2, 'STAT_CODE'=>2, 'ASYNC_STAT_CODE'=>2, 'STAT_ENABLE'=>1);
        $data = GetConfigDataToSave($fieldMap);
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    /**
     * 百度分享设置
     */
    function baidushare(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('baidushare');

        $this->assign('ShareStyle', $data['SHARE_STYLE'] );
        $this->assign('ShareTop', $data['SHARE_TOP'] );
        $this->assign('SharePos', $data['SHARE_POS'] );
        $this->assign('ShareSize', $data['SHARE_SIZE'] );
        $this->assign('ShareEnable', $data['SHARE_ENABLE'] );

        $this->assign("Action", __URL__.'/SaveBaidushare');
        $this->display();
    }

    function saveBaidushare(){
        $fieldMap = array('SHARE_STYLE'=>1,  'SHARE_TOP'=>1, 'SHARE_POS'=>2, 'SHARE_SIZE'=>1, 'SHARE_ENABLE'=>1);
        $data = GetConfigDataToSave($fieldMap);
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            YdCache::deleteHome();
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    function contact(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('contact'); //配置数据不从缓存中提取

        //联系方式
        $this->assign('Company', $data['COMPANY'] );
        $this->assign('Contact', $data['CONTACT'] );
        $this->assign('Address', $data['ADDRESS'] );
        $this->assign('Telephone', $data['TELEPHONE'] );
        $this->assign('Mobile', $data['MOBILE'] );
        $this->assign('Fax', $data['FAX'] );
        $this->assign('Email', $data['EMAIL'] );
        $this->assign('QQ', $data['QQ'] );
        $this->assign('PostCode', $data['POSTCODE'] );

        $this->assign('Longitude', $data['Longitude'] );
        $this->assign('Latitude', $data['Latitude'] );

        $this->assign('Action', __URL__.'/saveContact' );
        $this->display();
    }

    function saveContact(){
        $fieldMap = array(
            'COMPANY'=>2,  'CONTACT'=>2, 'ADDRESS'=>2, 'TELEPHONE'=>2, 'MOBILE'=>2,
            'FAX'=>2,             'EMAIL'=>2,        'QQ'=>2,           'POSTCODE'=>2,   'Longitude'=>2,
            'Latitude'=>2,
        );
        $data = GetConfigDataToSave($fieldMap);
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    function upload(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('upload'); //配置数据不从缓存中提取

        $IsExecutable = yd_is_executable('./Upload');

        $this->assign('Upload', $data['UPLOAD'] );
        $this->assign('UploadFileType', $data['UPLOAD_FILE_TYPE'] );
        $maxSize = round((double)$data['MAX_UPLOAD_SIZE']/1024/1024, 1);
        $this->assign('MaxUploadSize', $maxSize );
        $this->assign('UploadDirType', $data['UPLOAD_DIR_TYPE'] );
        $this->assign('IsExecutable', $IsExecutable );

        $this->assign('Action', __URL__.'/saveUpload' );
        $this->display();
    }

    function saveUpload(){
        if( !is_numeric($_POST['MAX_UPLOAD_SIZE']) ){
            $this->ajaxReturn(null, '最大上传文件大小必须为数字!' , 0);
        }
        if($_POST['MAX_UPLOAD_SIZE']<0){
            $this->ajaxReturn(null, '最大上传文件大小必须大于等于0' , 0);
        }
        $_POST['MAX_UPLOAD_SIZE'] = intval($_POST['MAX_UPLOAD_SIZE']*1024*1024);  //保存时转化为字节

        $types = explode('|', $_POST['UPLOAD_FILE_TYPE']);
        $blackList = array('asp', 'php', 'asa', 'jsp', 'bat', 'exe', 'ascx', 'cgi', 'ini', 'cer', 'pht', 'dll', 'xml', 'htaccess', 'config');
        foreach($types as $v){
            $type = trim(strtolower($v));
            if(strlen($type) > 5){
                $this->ajaxReturn(null, '扩展名长度不能大于5位' , 0);
            }
            foreach($blackList as $b){
                if(false !== stripos($type, $b)){
                    $this->ajaxReturn(null, "包含非法扩展名{$b}" , 0);
                }
            }
        }
        //没有上传目录
        $fieldMap = array('UPLOAD_FILE_TYPE'=>2, 'MAX_UPLOAD_SIZE'=>1, 'UPLOAD_DIR_TYPE'=>1);
        $data = GetConfigDataToSave($fieldMap);
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    function guestbook(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('guestbook'); //配置数据不从缓存中提取

        $this->assign('GuestBookAllow', $data['GUEST_BOOK_ALLOW'] );
        $this->assign('GuestBookCheck', $data['GUEST_BOOK_CHECK'] );
        $this->assign('GuestBookVerifycode', $data['GUEST_BOOK_VERIFYCODE'] );
        $this->assign('GuestBookPageSize', $data['GUEST_BOOK_PAGESIZE'] );

        //邮件通知
        $this->assign('GuestBookSendEmail', $data['GUEST_BOOK_SENDEMAIL'] );
        $this->assign('GuestBookEmail', $data['GUEST_BOOK_EMAIL'] );  //接收邮件帐号
        $this->assign('GuestBookEmailTitle', $data['GUEST_BOOK_EMAIL_TITLE'] );  //邮件标题
        //$this->assign('GuestBookEmailBody', $data['GUEST_BOOK_EMAIL_BODY'] );  //邮件内容

        $this->assign('GuestBookSms', $data['GUEST_BOOK_SMS'] );
        $this->assign('GuestBookSmsTo', $data['GUEST_BOOK_SMS_TO'] );  //接收邮件帐号
        $this->assign('GuestBookSmsTemplate', $data['GUEST_BOOK_SMS_TEMPLATE'] );  //邮件标题

        //$this->assign('Label', $this->getLabel(6) );
        $this->assign('Action', __URL__.'/saveGuestbook' );
        $this->display();
    }

    function saveGuestbook(){
        $data = GetConfigDataToSave('', 'GUEST_BOOK_');
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    function feedback(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('guestbook'); //配置数据不从缓存中提取
        $this->assign('c', $data );
        $this->assign('Action', __URL__.'/saveFeedback' );
        $this->display();
    }

    function saveFeedback(){
        $data = GetConfigDataToSave('', 'FEEDBACK_');
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    //返回模型对应的标签数组（不含分组名称）
    private function getLabel($channelModelID){
        $label[] = array('LabelName'=>'{$WebName}', 'DisplayName'=>'网站名称');
        $label[] = array('LabelName'=>'{$WebUrl}', 'DisplayName'=>'网站域名');
        $m = D('Admin/Attribute');
        $Attribute = $m->getAttribute($channelModelID);
        foreach ($Attribute as $a){
            $name = explode(',', $a['DisplayName']);
            $label[] = array('LabelName'=>'{$'.$a['FieldName'].'}', 'DisplayName'=>$name[0]);
        }
        return $label;
    }


    function comment(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('comment'); //配置数据不从缓存中提取

        $this->assign('CommentEnable', $data['COMMENT_ENABLE'] );
        $this->assign('CommentCheck', $data['COMMENT_CHECK'] );
        $this->assign('CommentVerifycode', $data['COMMENT_VERIFYCODE'] );
        $this->assign('CommentPageSize', $data['COMMENT_PAGE_SIZE'] );
        $this->assign('CommentBuy', $data['COMMENT_BUY'] );
        $this->assign('CommentTip', $data['COMMENT_TIP'] );

        $this->assign('Action', __URL__.'/saveComment' );
        $this->display();
    }

    function saveComment(){
        $data = GetConfigDataToSave('', 'COMMENT_');
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    function water(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('water'); //配置数据不从缓存中提取
        $this->assign('WaterEnable', $data['WATER_ENABLE'] );
        $this->assign('WaterPic', $data['WATER_PIC'] );
        $this->assign('WaterOffsetX', $data['WATER_OFFSET_X'] );
        $this->assign('WaterOffsetY', $data['WATER_OFFSET_Y'] );
        $this->assign('WaterTrans', $data['WATER_TRANS'] );

        $this->assign('WaterType', $data['WATER_TYPE'] );
        $this->assign('WaterText', $data['WATER_TEXT'] );
        $this->assign('WaterFont', $data['WATER_FONT'] );
        $this->assign('WaterTextSize', $data['WATER_TEXT_SIZE'] );
        $this->assign('WaterTextColor', $data['WATER_TEXT_COLOR'] );
        $this->assign('WaterTextAngle', $data['WATER_TEXT_ANGLE'] );
        $this->assign('WaterPosition', $data['WATER_POSITION'] );

        $MyFont = GetWaterFonts();
        $this->assign('MyFont',$MyFont);
        $this->assign('Action', __URL__.'/saveWater' );
        $this->display();
    }

    function saveWater(){
        $_POST['WATER_TYPE'] = (int)$_POST['WATER_TYPE'];
        if( $_POST['WATER_TYPE'] == 2 ){ //文字水印
            if( empty($_POST['WATER_TEXT']) ){
                $this->ajaxReturn(null, '水印文字不能为空!' , 0);
            }

            $font = './Public/font/'.$_POST['WATER_FONT'];
            if( !is_file($font)){
                $this->ajaxReturn(null, '水印字体文件不存在!' , 0);
            }

            if( !is_numeric($_POST['WATER_TEXT_SIZE']) ){
                $this->ajaxReturn(null, '水印文字大小必须为数字!' , 0);
            }

            if( !is_numeric($_POST['WATER_TEXT_ANGLE']) ){
                $this->ajaxReturn(null, '水印文字角度必须为数字!' , 0);
            }

            if( !is_numeric($_POST['WATER_OFFSET_X']) ){
                $this->ajaxReturn(null, '水印文字水平X偏移量必须为数字!' , 0);
            }

            if( !is_numeric($_POST['WATER_OFFSET_Y']) ){
                $this->ajaxReturn(null, '水印文字垂直Y偏移量必须为数字!' , 0);
            }
        }else{  //图片水印
            if( empty($_POST['WATER_PIC']) ){
                $this->ajaxReturn(null, '水印图片不能为空!' , 0);
            }
            $map = array('png'=>1, 'jpg'=>1, 'jpeg'=>1, 'bmp'=>1);
            $ext = yd_file_ext($_POST['WATER_PIC']);
            $ext = strtolower($ext);
            if(!isset($map[$ext])){
                $this->ajaxReturn(null, '水印图片必须是png、jpg、bmp格式' , 0);
            }
        }

        $data = GetConfigDataToSave('', 'WATER_');
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    //水印预览
    function waterPreview(){
        header("Content-type: image/jpg");
        $src = './Public/Images/standard/1.jpg';
        $dst = RUNTIME_PATH.'waterpreview.jpg';
        if( is_file($dst) ){
            @unlink($dst);
        }

        if( 1 == $GLOBALS['Config']['WATER_ENABLE']){
            addWater($src, $dst);
        }else{ //未启用水印
            $dst = $src;
        }

        //$dst = __ROOT__.substr($dst, 1);
        //$this->ajaxReturn($dst,'',1);
        if( file_exists($dst) ){
            $g = imagecreatefromjpeg($dst);
            imagejpeg($g, NULL, 80);
            imagedestroy($g);
        }
    }

    /**
     * 第三方登录设置
     */
    function oauth(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Oauth');
        $data = $m->getOauth();
        if(!empty($data)){
            import("@.Common.YdOauth");
            $n = is_array($data) ? count($data) : 0;
            for($i = 0; $i < $n; $i++){
                $obj = YdOauth::getInstance($data[$i]['OauthMark']);
                $obj->setAppID( $data[$i]['OauthAppID'] );
                $obj->setAppKey( $data[$i]['OauthAppKey'] );
                $data[$i]['OauthRequestUrl'] = $obj->getRequestUrl();
                $data[$i]['OauthAppKey'] = HideSensitiveData($data[$i]['OauthAppKey']);
            }
        }
        $this->assign("Data", $data);
        $this->assign('Action', __URL__.'/saveOauth' );
        $this->display();
    }

    function saveOauth(){
        import("@.Common.YdSafe");
        $n = is_array($_POST['OauthID']) ? count( $_POST['OauthID'] ) : 0;
        $result = false;
        $data = array();
        $m = D("Admin/Oauth");
        for($i=0; $i<$n; $i++){
            $appKey = $_POST['OauthAppKey'][$i];
            $data = array(
                'OauthID'=>$_POST['OauthID'][$i],
                'OauthAppID'=>$_POST['OauthAppID'][$i],
                'OauthAppKey'=>$appKey,
                'OauthOrder'=>$_POST['OauthOrder'][$i],
                'IsEnable'=>$_POST['IsEnable'][$i]
            );
            if(YdSafe::isSensitiveData($appKey)){
                $result = true;
            }else{
                $result = $m->save($data);
            }
        }

        if($result === false){
            $this->ajaxReturn(null, '保存失败!' , 0);
        }else{
            unset($data['OauthAppKey']);
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }
    }

    function reg(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('reg'); //配置数据不从缓存中提取

        $this->assign('MemberEnable', $data['MEMBER_ENABLE'] );
        $this->assign('MemberRegEnable', $data['MEMBER_REG_ENABLE'] );
        $this->assign('MemberRegCheck', $data['MEMBER_REG_CHECK'] );
        $this->assign('MemberRegVerifyCode', $data['MEMBER_REG_VERIFYCODE'] );
        $this->assign('MemberLoginVerifyCode', $data['MEMBER_LOGIN_VERIFYCODE'] );
        $this->assign('MemberAddCheck', $data['MEMBER_ADD_CHECK'] );
        $this->assign('EmailBody', $data['EMAIL_BODY'] );
        $this->assign('MobileRegTemplate', $data['MOBILE_REG_TEMPLATE'] );

        $this->assign('Action', __URL__.'/saveReg' );
        $this->display();
    }

    function saveReg(){
        $fieldMap = array('MEMBER_ENABLE'=>1, 'MEMBER_LOGIN_VERIFYCODE'=>1, 'MEMBER_ADD_CHECK'=>1, 'EMAIL_BODY'=>2, 'MOBILE_REG_TEMPLATE'=>2);
        $data = GetConfigDataToSave($fieldMap, 'MEMBER_REG_');
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    function db(){
        return;
        header("Content-Type:text/html; charset=utf-8");
        $this->assign('DbHost', C('DB_HOST') );
        $this->assign('DbName', C('DB_NAME') );
        $this->assign('DbUser', C('DB_USER') );
        $this->assign('DbPort', C('DB_PORT') );
        $this->assign('DbPrefix', C('DB_PREFIX') );
        $this->assign('Action', __URL__.'/saveDb' );
        $this->display();
    }

    function saveDb(){
        return;
        if( isset($_POST) ){  //保存配置到数据库
            //检查数据库连接================================================
            $dbHost = trim($_POST['DB_HOST']);
            $dbUser= trim($_POST['DB_USER']);
            $dbName = trim($_POST['DB_NAME']);
            $dbPwd= trim($_POST['DB_PWD']);
            $dbPort = trim($_POST['DB_PORT']);
            $dbPrefix = trim($_POST['DB_PREFIX']);

            $conn = @mysql_connect($dbHost, $dbUser, $dbPwd);
            if( !$conn ){
                $this->ajaxReturn(null, '连接数据库失败!' , 0);
            }
            if( !mysql_select_db($dbName, $conn) ){
                $this->ajaxReturn(null, '数据库'.$dbName.'不存在！' , 0);
            }
            if( isset($_POST['testdb']) ) { //数据库测试
                $this->ajaxReturn(null, '连接数据库成功!' , 1);
            }
            //==========================================================

            //保存数据库配置文件==============================================
            $file = CONF_PATH.'db.php';
            $db = array ('DB_TYPE' => 'mysqli',
                'DB_HOST' => $dbHost,
                'DB_NAME' => $dbName,
                'DB_USER' => $dbUser,
                'DB_PWD' => $dbPwd,
                'DB_PORT' => $dbPort,
                'DB_PREFIX' => $dbPrefix,
            );
            $b = cache_array($db, $file);
            if( !$b ) $this->ajaxReturn(null, '保存数据库配置失败!' , 0);

            //缓存数据库成功后必须更新数据库配置缓存======================
            //C('DB_HOST', $_POST['DB_HOST']);
            //C('DB_NAME', $_POST['DB_NAME']);
            //C('DB_USER', $_POST['DB_USER']);
            //C('DB_PWD', $_POST['DB_PWD']);
            //C('DB_PORT', $_POST['DB_PORT']);
            //$m = M();
            //$m->db(0, NULL);
            //$m->db(0, "mysql://$dbUser:$dbPwd@$dbHost:$dbPort/$dbName");
            //存在问题：当前配置写入db.php后，并没有改变当前正在运行的数据库对象，下次运行才生效
            //是否有办法解决
            //==================================================

            //写配置文件
            YdCache::writeAll(); //更新所有缓存,内部会调用YdCache::deleteAll();
            $description = var_export($_POST, true);
            WriteLog($description);
            $this->ajaxReturn(null, '配置数据库成功!' , 1);
        }
    }

    //在线客服设置
    function online(){
        header("Content-Type:text/html; charset=utf-8");

        $m = D('Admin/Config');
        $data = $m->getConfig('online'); //配置数据不从缓存中提取

        $this->assign('OnlineEnable', $data['ONLINE_ENABLE'] );
        $this->assign('OnlineStyle', $data['ONLINE_STYLE'] );
        $this->assign('OnlinePosition', $data['ONLINE_POSITION'] );
        $this->assign('OnlineTop', $data['ONLINE_TOP'] );
        $this->assign('OnlineEffect', $data['ONLINE_EFFECT'] );
        $this->assign('OnlineWidth', $data['ONLINE_WIDTH'] );

        $this->assign('OnlineOpen', $data['ONLINE_OPEN'] );
        $this->assign('OnlineTelephone', $data['ONLINE_TELEPHONE'] );

        $this->assign('OnlineFooterText', $data['ONLINE_FOOTER_TEXT'] );
        $this->assign('OnlineTitle', $data['ONLINE_TITLE'] );
        $this->assign('OnlineColor', $data['ONLINE_COLOR'] ); //主题颜色
        $this->assign('OnlineIconColor', $data['ONLINE_ICON_COLOR'] ); //图标颜色

        $this->assign('Action', __URL__.'/saveOnline' );
        $this->display();
    }

    //保存在线客服
    function saveOnline(){
        $data = GetConfigDataToSave('', 'ONLINE_');
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            if(!APP_DEBUG){
                YdCache::deleteHome();
            }
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    /**
     * 语言设置
     */
    function language(){
        header("Content-Type:text/html; charset=utf-8");
        $this->assign('LangAutoDetect', C('LANG_AUTO_DETECT') );
        $this->assign('DefaultLang', C('DEFAULT_LANG') );
        $this->assign('Action', __URL__.'/saveLanguage' );
        $this->display();
    }

    function saveLanguage(){
        $fieldMap = array('LANG_AUTO_DETECT'=>2, 'DEFAULT_LANG'=>2);
        $data = GetConfigDataToSave($fieldMap);
        if( YdCache::writeCoreConfig($data) ){
            YdCache::deleteAll();
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    /**
     * 内核设置
     */
    function core(){
        header("Content-Type:text/html; charset=utf-8");
        //1: 不启用伪静态(PathInfo模式), 2:启用伪静态 Rewrite模式
        $this->assign('UrlModel', C('URL_MODEL') );
        $this->assign('UrlHtmlSuffix', C('URL_HTML_SUFFIX') );
        $this->assign('Action', __URL__.'/saveCore' );
        $this->display();
    }

    function saveCore(){
        if( $_POST['URL_MODEL'] == 2 ){
            //若启用伪静态需要判断服务器是否支持伪静态, 加t参数防止缓存
            $url = get_web_url().'/public/testmodel/t/'.time();
            $url = str_ireplace('index.php/', '', $url);
            //$r = get_headers($url);  若使用get_headers，则url不存在时，也会返回200 OK
            $txt = file_get_contents($url);
            if( $txt != 'success'){
                $this->ajaxReturn(null, '您的服务器不支持伪静态!' , 0);
            }
        }
        $data['URL_MODEL'] = (int)$_POST['URL_MODEL'];
        $data['URL_HTML_SUFFIX'] = $_POST['URL_HTML_SUFFIX'];
        if(!preg_match('/^[A-Za-z0-9]+$/',  $data['URL_HTML_SUFFIX'] )){
            $this->ajaxReturn(null, '后缀只能是字母、数字！' , 0);
        }
        //长度判断
        $max = 10;
        if(strlen($data['URL_HTML_SUFFIX']) > $max){
            $this->ajaxReturn(null, "后缀长度不能大于{$max}个字符！" , 0);
        }
        $b = YdCache::writeCoreConfig($data);
        if( $b ){
            YdCache::deleteAll();
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    function order(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('order'); //配置数据不从缓存中提取

        $this->assign('OrderAllow', $data['ORDER_ALLOW'] );
        $this->assign('OrderPageSize', $data['ORDER_PAGESIZE'] );
        $this->assign('OrderVerifyCode', $data['ORDER_VERIFYCODE'] );
        $this->assign('OrderEmail', $data['ORDER_EMAIL'] );
        $this->assign('OrderEmailBody', $data['ORDER_EMAIL_BODY'] );
        $this->assign('OrderEmailTo', $data['ORDER_EMAIL_TO'] );
        $this->assign('OrderEmailTitle', $data['ORDER_EMAIL_TITLE'] );
        $this->assign('OrderPrefix', $data['ORDER_PREFIX'] );

        $this->assign('CurrencySymbol', $data['CURRENCY_SYMBOL'] );

        $this->assign('OrderSms', $data['ORDER_SMS'] );
        $this->assign('OrderSmsTo', $data['ORDER_SMS_TO'] );
        $this->assign('OrderSmsTemplate', $data['ORDER_SMS_TEMPLATE'] );

        //商城设置
        $this->assign('FreeShippingThreshold', $data['FREE_SHIPPING_THRESHOLD'] );
        $this->assign('PointExchangeRate', $data['POINT_EXCHANGE_RATE'] );
        $this->assign('AutoReceiveDays', $data['AUTO_RECEIVE_DAYS'] );

        $this->assign('Action', __URL__.'/saveOrder' );
        $this->display();
    }

    function saveOrder(){
        $fieldMap = array(
            'CURRENCY_SYMBOL'=>2, 'ORDER_PREFIX'=>2,  'ORDER_ALLOW'=>2, 'ORDER_PAGESIZE'=>2, 'ORDER_VERIFYCODE'=>2,
            'FREE_SHIPPING_THRESHOLD'=>1, 'POINT_EXCHANGE_RATE'=>1,  'AUTO_RECEIVE_DAYS'=>1, 'ORDER_EMAIL'=>1, 'ORDER_EMAIL_TO'=>2,
            'ORDER_EMAIL_TITLE'=>2, 'ORDER_EMAIL_BODY'=>2,  'ORDER_SMS'=>1, 'ORDER_SMS_TO'=>2, 'ORDER_SMS_TEMPLATE'=>2,
        );
        $data = GetConfigDataToSave($fieldMap);
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    //自动生成关键词内链
    function autoLink(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('link'); //配置数据不从缓存中提取

        $this->assign('LinkEnable', $data['LINK_ENABLE'] );
        $this->assign('LinkKeyword', $data['LINK_KEYWORD'] );

        $this->assign('Action', __URL__.'/saveAutoLink' );
        $this->display();
    }

    function saveAutoLink(){
        //将中文等号替换成英文等号
        $_POST['LINK_KEYWORD'] = str_replace('＝', '=', $_POST['LINK_KEYWORD']);
        $_POST['LINK_KEYWORD'] = trim($_POST['LINK_KEYWORD']);
        $fieldMap = array('LINK_ENABLE'=>1, 'LINK_KEYWORD'=>2);
        $data = GetConfigDataToSave($fieldMap);
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    function thumb(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('thumb'); //配置数据不从缓存中提取
        $this->assign('ThumbEnable', $data['THUMB_ENABLE'] );
        $this->assign('ThumbWaterEnable', $data['THUMB_WATER_ENABLE'] );
        $this->assign('ThumbType', $data['THUMB_TYPE'] );
        $this->assign('ThumbWidth', $data['THUMB_WIDTH'] );
        $this->assign('ThumbHeight', $data['THUMB_HEIGHT'] );
        $this->assign('ThumbFirst', $data['THUMB_FIRST'] );

        $this->assign('Action', __URL__.'/saveThumb' );
        $this->display();
    }

    function saveThumb(){
        if( !is_numeric($_POST['THUMB_WIDTH']) ){
            $this->ajaxReturn(null, '缩略图宽度必须为数字!' , 0);
        }
        if( !is_numeric($_POST['THUMB_HEIGHT']) ){
            $this->ajaxReturn(null, '缩略图高度必须为数字!' , 0);
        }
        $fieldMap = array(
            'THUMB_FIRST'=>1, 'THUMB_ENABLE'=>1,    'THUMB_TYPE'=>1, 'THUMB_WIDTH'=>1, 'THUMB_HEIGHT'=>1,
            'THUMB_WATER_ENABLE'=>1
        );
        $data = GetConfigDataToSave($fieldMap);
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    function wap(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('wap'); //配置数据不从缓存中提取
        $this->assign('WapAutoRedirect', $data['WAP_AUTO_REDIRECT'] );
        $this->assign('WapPcAccess', $data['WAP_PC_ACCESS'] );
        $this->assign('WapLogo', $data['WAP_LOGO'] );
        $this->assign('WapQrcode', $data['WAP_QRCODE'] );
        $this->assign('WapStatus', $data['WAP_STATUS'] );

        //读取domain数据，直接从核心配置文件里读取========
        $wapUrl = YdCache::readCoreConfig('WAP_URL');
        $this->assign('WapURL', $wapUrl );
        $this->assign('WapUrl', $wapUrl );
        //====================================

        $this->assign('Action', __URL__.'/saveWap' );
        $this->display();
    }

    function saveWap(){
        //保存手机网站域名配置到config/domain.php[无需缓存到runtime]============
        $temp = array();
        if( isset($_POST['WAP_URL']) ){
            $domain = array('APP_SUB_DOMAIN_RULES'=>array());
            $dList = explode(',', trim($_POST['WAP_URL']));
            foreach( (array)$dList as $d){
                $d = YdInput::checkLetterNumber($d);
                if(!empty($d)){
                    $d = trim($d);
                    $d = str_ireplace('http://', '', rtrim($d,'\\/') ); //去掉http://
                    $temp[$d] = array('wap/');
                }
            }
            $domain['APP_SUB_DOMAIN_RULES'] = $temp;
            YdCache::writeCoreConfig($domain);
            YdCache::deleteAll();
        }
        //====================================================
        $fieldMap = array(
            'WAP_STATUS'=>1, 'WAP_LOGO'=>2,    'WAP_URL'=>2, 'WAP_QRCODE'=>2, 'WAP_AUTO_REDIRECT'=>1,
            'WAP_PC_ACCESS'=>1
        );
        $data = GetConfigDataToSave($fieldMap);
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    function other(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('other'); //配置数据不从缓存中提取
        $this->assign('AdminPageSize', $data['ADMIN_PAGE_SIZE'] );
        $this->assign('AdminRollPage', $data['ADMIN_ROLL_PAGE'] );
        $this->assign('AutoDelEnable', $data['AUTO_DEL_ENABLE'] );
        $this->assign('AutoUploadEnable', $data['AUTO_UPLOAD_ENABLE'] );
        $this->assign('DelLinkEnable', $data['DEL_LINK_ENABLE'] );

        $this->assign('TextEditor', $data['TextEditor'] );
        $this->assign('AllowLink', $data['ALLOW_LINK'] );
        $this->assign('SearchPageSize', $data['SearchPageSize'] );
        $this->assign('HomeRollPage', $data['HomeRollPage'] );
        $this->assign('ChannelTreeWidth', $data['ChannelTreeWidth'] );

        $this->assign('ADMIN_LOGIN_SENDEMAIL', $data['ADMIN_LOGIN_SENDEMAIL'] );
        $this->assign('ADMIN_LOGIN_EMAIL', $data['ADMIN_LOGIN_EMAIL'] );
        $this->assign('ADMIN_LOGIN_EMAIL_TITLE', $data['ADMIN_LOGIN_EMAIL_TITLE'] );

        $this->assign('Action', __URL__.'/saveOther' );
        $this->display();
    }

    function saveOther(){
        if( isset($_POST) ){  //保存配置到数据库
            unset( $_POST['__hash__'] );
            $fieldMap = array(
                'SearchPageSize'=>1,         'HomeRollPage'=>1,                'ADMIN_PAGE_SIZE'=>1, 'ADMIN_ROLL_PAGE'=>1, 'TextEditor'=>1,
                'AUTO_DEL_ENABLE'=>1,  'AUTO_UPLOAD_ENABLE'=>1, 'DEL_LINK_ENABLE'=>1, 'ChannelTreeWidth'=>1, 'ALLOW_LINK'=>2,
                'ADMIN_LOGIN_SENDEMAIL'=>1,  'ADMIN_LOGIN_EMAIL'=>2, 'ADMIN_LOGIN_EMAIL_TITLE'=>2,
            );
            $data = GetConfigDataToSave($fieldMap);
            if(1== $data['ADMIN_LOGIN_SENDEMAIL']){
                if(empty($data['ADMIN_LOGIN_EMAIL_TITLE'])){
                    $this->ajaxReturn(null, '通知邮件标题不能为空!' , 0);
                }
            }
            $m = D("Admin/Config");
            if( $m->saveConfig($data) ){
                YdCache::deleteAll();
                $description = var_export($data, true);
                WriteLog($description);
                $this->ajaxReturn(null, '保存成功！' , 1);
            }else{
                $this->ajaxReturn(null, '保存失败!' , 0);
            }
        }
    }

    /**
     * 安全设置
     */
    function safe(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('other'); //配置数据不从缓存中提取
        $this->assign('AdminWhiteIP', $data['AdminWhiteIP'] );
        $this->assign('AdminLoginName', C('ADMIN_LOGIN_NAME') );
        $ip = get_client_ip();
        $this->assign('CurrentIP', $ip);

        //如何设置了答案就启用
        $SafeEnable = empty($data['SafeAnswer']) ? 0 : 1;
        $this->assign('SafeEnable', $SafeEnable);
        $this->assign('SafeQuestion', $data['SafeQuestion'] );
        $question = $this->_getQuestion();
        $this->assign('Question', $question);
        $this->assign('Tip', "备注：问题的答案应该很难被穷举。<br/>不能是生日、学号等相关问题");

        //目录安全监测
        $list = DirDetection();
        $this->assign('DirList', $list);

        //危险函数检测
        $DisableFunction = '';
        $functions = array(
            array("name"=>"eval", "des"=>"功能描述：把字符串作为php代码执行，常用于木马\n危险等级：高"),
            array("name"=>"assert", "des"=>"功能描述：检查一个断言是否为false，将字符串作为php代码执行，常用于木马\n危险等级：高"),

            array("name"=>"chmod", "des"=>"功能描述：修改文件权限\n危险等级：高"),
            array("name"=>"chgrp", "des"=>"功能描述：改变文件或目录所属的用户组。\n危险等级：高"),
            array("name"=>"chown", "des"=>"功能描述：改变文件或目录的所有者\n危险等级：高"),

            array("name"=>"exec", "des"=>"功能描述：允许执行一个外部程序（如 UNIX Shell 或 CMD 命令等）\n危险等级：高"),
            array("name"=>"shell_exec", "des"=>"功能描述：通过 Shell 执行命令，并将执行结果作为字符串返回\n危险等级：高"),
            array("name"=>"passthru", "des"=>"功能描述：允许执行一个外部程序并回显输出，类似于 exec()\n危险等级：高"),
            array("name"=>"system", "des"=>"功能描述：允许执行一个外部程序并回显输出，类似于 passthru()\n危险等级：高"),
            array("name"=>"putenv", "des"=>"功能描述：用于在 PHP 运行时改变系统字符集环境。用该函数修改系统字符集环境后，利用sendmail指令发送特殊参数执行系统 SHELL 命令\n危险等级：高"),
            array("name"=>"chroot", "des"=>"功能描述：可改变当前 PHP 进程的工作根目录，仅当系统支持CLI模式PHP 时才能工作，且该函数不适用于 Windows 系统\n危险等级：高"),

            array("name"=>"popen", "des"=>"功能描述：可通过 popen() 的参数传递一条命令，并对 popen() 所打开的文件进行执行\n危险等级：高"),
            array("name"=>"proc_open", "des"=>"功能描述：执行一个命令并打开文件指针用于读取以及写入\n危险等级：高"),
            array("name"=>"pcntl_exec", "des"=>"功能描述：在当前进程空间执行指定程序\n危险等级：高"),
            array("name"=>"ini_alter", "des"=>"功能描述：是 ini_set() 函数的一个别名函数，功能与 ini_set() 相同。具体参见 ini_set()\n危险等级：高"),
            array("name"=>"ini_restore", "des"=>"功能描述：可用于恢复 PHP 环境配置参数到其初始值\n危险等级：高"),
            array("name"=>"dl", "des"=>"功能描述：在 PHP 进行运行过程当中（而非启动时）加载一个 PHP 外部模块\n危险等级：高"),
            array("name"=>"openlog", "des"=>"功能描述：为程序打开与系统记录器的连接\n危险等级：高"),
            array("name"=>"syslog", "des"=>"功能描述：可调用 UNIX 系统的系统层 syslog() 函数\n危险等级：中"),

            array("name"=>"popepassthru", "des"=>""),
            array("name"=>"pcntl_fork", "des"=>"功能描述：在当前进程当前位置创建子进程\n危险等级：高"),
            array("name"=>"pcntl_waitpid", "des"=>"功能描述：等待或返回fork的子进程状态\n危险等级：高"),
            array("name"=>"imap_open", "des"=>"功能描述：打开邮箱的IMAP流\n危险等级：高"),
            array("name"=>"apache_setenv", "des"=>"功能描述：设置 Apache 子进程环境变量\n危险等级：高")
        );

        //以下2个函数，只有linux才支持，在windows里放在disablefunciton会导致服务无法启动
        if(0===IS_WIN){
            $functions[] = array("name"=>"readlink", "des"=>"功能描述：返回符号连接指向的目标文件内容\n危险等级：中");
            $functions[] = array("name"=>"symlink", "des"=>"功能描述：在 UNIX 系统中建立一个符号链接\n危险等级：高");
        }
        foreach($functions as $v){
            $name = trim($v['name']);
            $title = !empty($v['des']) ? "title='{$v['des']}'" : '';
            if(function_exists($name)){
                $DisableFunction .= "<span class='functions f1' {$title}>{$name}</span>";
            }else{
                $DisableFunction .= "<span class='functions f0' {$title}>{$name}</span>";
            }
        }
        $this->assign('DisableFunction', $DisableFunction);

        //微信绑定登录
        import("@.Common.YdUcApi");
        $uc = new YdUcApi();
        $uc->checkBindUcStatus();
        $info = $uc->getUcBindInfo();
        $this->assign($info);

        $this->assign('Action', __URL__.'/saveSafe' );
        $this->display();
    }

    /**
     * 解绑uc
     */
    function unbindUc(){
        import("@.Common.YdUcApi");
        $uc = new YdUcApi();
        $result = $uc->unbindUc();
        if(false !== $result){
            $this->ajaxReturn(null, '解绑微信成功！' , 1);
        }else{
            $errMsg = $uc->getLastError();
            $this->ajaxReturn(null, "解绑微信失败！{$errMsg}" , 0);
        }
    }

    /**
     * 设置绑定信息
     */
    function setUcBindInfo(){
        import("@.Common.YdUcApi");
        $uc = new YdUcApi();
        $result = $uc->setUcBindInfo($_POST);
        if(false !== $result){
            $this->ajaxReturn(null, '绑定成功！' , 1);
        }else{
            $this->ajaxReturn(null, '绑定失败！' , 0);
        }
    }

    private function _getQuestion(){
        $data = array(
            '我母亲的姓名？',
            '我父亲的姓名？',

            '我初中班主任的姓名？',
            '我高中主任的姓名？',

            '我的真实姓名?',
            '我的身份证号码后10位?',
            '我配偶的姓名？',
            '我配偶的电话号码？',
        );
        return $data;
    }


    /**
     * 保存安全设置
     */
    function saveSafe(){
        $this->_isAdminWhiteIPValid();
        if( isset($_POST) ){  //保存配置到数据库
            unset( $_POST['__hash__'] );
            $oldName = C('ADMIN_LOGIN_NAME');
            $loginName = strtolower($_POST['AdminLoginName']);
            $loginNameChanged = ($oldName===$loginName) ? false : true;
            if(!empty($loginName)){
                $map = array('home'=>1, 'wap'=>1, 'app'=>1, 'admin'=>1, 'member'=>1, 'html'=>1,'channel'=>1);
                if(isset($map[$loginName])){
                    $this->ajaxReturn(null, "【后台登录目录名称】不能是{$loginName}" , 0);
                }
                if(!preg_match("/^[a-zA-Z0-9_-]+$/i", $loginName)){
                    $this->ajaxReturn(null, "【后台登录目录名称】必须是：数字、字母、下划线、中划线" , 0);
                }
                $len = strlen($loginName);
                if($len < 6 || $len > 20){
                    $this->ajaxReturn(null, "【后台登录目录名称】必须是6-20个字符" , 0);
                }
            }
            //保存后台登陆名称到core.php文件，就算为空也必须要保存
            $config['ADMIN_LOGIN_NAME'] = $loginName;
            YdCache::writeCoreConfig($config);

            $fieldMap = array('AdminLoginName'=>2,   'AdminWhiteIP'=>2);
            $data = GetConfigDataToSave($fieldMap);
            $m = D("Admin/Config");
            if( $m->saveConfig($data) ){
                YdCache::deleteAll();
                unset($data['AdminLoginName']);
                $description = var_export($data, true);
                WriteLog($description);
                if($loginNameChanged){
                    $msg = "保存成功，您修改了后台地址，必须退出并重新登录！";
                    if(empty($loginName)) $loginName='admin';
                    if(empty($oldName)) $oldName='admin';
                    $url = str_ireplace($oldName, $loginName, __GROUP__);
                    //在nginx上不加debug=1就会打不开，apache正常
                    $url = "{$url}/public/logout?debug=1";
                    afterLogout(); //必须清除session
                    $this->ajaxReturn($url, $msg, 2);
                }else{
                    $this->ajaxReturn(null, '保存成功！' , 1);
                }
            }else{
                $this->ajaxReturn(null, '保存失败!' , 0);
            }
        }
    }

    /**
     * 修改二次验证安全问题
     */
    function modifySafeQuestion(){
        $m = D("Admin/Config");
        $oldAnswer = trim($_POST['OldSafeAnswer']);
        if(empty($oldAnswer)){
            $this->ajaxReturn(null, '问题答案不能为空！' , 0);
        }

        //输出参数
        $errorCount = 0;  //返回当前错误次数
        $errorText = '';  //返回当前错误信息
        $maxCount = 0; //返回允许最大次数
        $b = $m->checkSafeErrorCount($errorCount, $errorText, $maxCount);
        if(!$b){
            $this->ajaxReturn(null, $errorText , 0);
        }

        //这里必须判断错误次数，防止利用此api进行暴力破解
        $isCorrect = $m->isSafeAnswerCorrect($oldAnswer);
        if(!$isCorrect){
            $m->incSafeErrorCount($errorCount); //增加错误次数
            $this->ajaxReturn(null, '问题答案不正确！' , 0);
        }else{
            $m->incSafeErrorCount(-1); //回答正确，清0
        }

        $data = array();
        $SafeEnable = intval($_POST['SafeEnable']);
        if(1 == $SafeEnable){
            $answer = trim($_POST['NewSafeAnswer']);
            $question = trim($_POST['NewSafeQuestion']);
            if(empty($question)){
                $this->ajaxReturn(null, '新问题名称不能为空！' , 0);
            }
            if(empty($answer)){
                $this->ajaxReturn(null, '新问题答案不能为空！' , 0);
            }
            if(strlen($answer) < 6){
                $this->ajaxReturn(null, '新问题答案长度必须大于6（1个汉字的长度等于3）' , 0);
            }
            $data['SafeQuestion'] = $question;
            $data['SafeAnswer'] = yd_password_hash($answer);
        }else{
            $question = '';
            $data['SafeAnswer'] = ''; //留空表示停用
        }
        $result = $this->_saveSafeConfigData($data);
        //$result = $m->saveConfig($data);
        if(false!==$result){
            if($SafeEnable==1){
                $description = $question;
            }else{
                $description = "禁止二次验证安全问题";
            }
            WriteLog($description, array('LogType'=>4, 'UserAction'=>'修改二次验证安全问题'));
            YdCache::deleteAll();
            $this->ajaxReturn(null, '修改成功' , 1);
        }else{
            $this->ajaxReturn(null, '修改失败' , 0);
        }
    }

    /**
     * 设置二次验证安全问题
     */
    function setSafeQuestion(){
        $answer = trim($_POST['SafeAnswer']);
        $question = trim($_POST['SafeQuestion']);
        if(empty($question)){
            $this->ajaxReturn(null, '问题名称不能为空！' , 0);
        }
        if(empty($answer)){
            $this->ajaxReturn(null, '问题答案不能为空！' , 0);
        }
        if(strlen($answer) < 6){
            $this->ajaxReturn(null, '问题答案长度必须大于6（1个汉字的长度等于3）' , 0);
        }
        //必须判断是否设置了问题
        $m = D('Admin/Config');
        $oldSafeAnswer = $m->getConfigItem('SafeAnswer');
        if(!empty($oldSafeAnswer)){
            $this->ajaxReturn(null, '已经设置了安全验证问题！不需要重复设置' , 0);
        }

        $data = array();
        $data['SafeQuestion'] = $question;
        $data['SafeAnswer'] = yd_password_hash($answer); //答案加密
        $result = $this->_saveSafeConfigData($data);
        //这里不能调用saveConfig，会过滤掉敏感数据
        //$result = $m->saveConfig($data);
        if(false!==$result){
            $description = $question;
            WriteLog($description, array('LogType'=>4, 'UserAction'=>'设置二次验证安全问题'));
            YdCache::deleteAll();
            $this->ajaxReturn(null, '设置成功' , 1);
        }else{
            $this->ajaxReturn(null, '设置失败' , 0);
        }
    }

    /**
     * 保存安全配置数据
     * 用于替代saveConfig
     */
    private function _saveSafeConfigData($data){
        $m = D('Admin/Config');
        $result = false;
        foreach($data as $k=>$v){
            $where['ConfigName'] = $k;
            $result = $m->where($where)->setField('ConfigValue', $v);
        }
        return $result;
    }

    private function _isAdminWhiteIPValid(){
        $ip = trim($_POST['AdminWhiteIP']);
        $ip = str_ireplace(' ', '', $ip);
        if(empty($ip)) return;
        $tempIPList = str_replace(array("\r\n","\r"), "\n", $ip);
        $tempIPList = explode ("\n", $tempIPList);
        $currentIP = get_client_ip();
        $arrIP = explode('.', $currentIP);
        $isExist = false;
        foreach($tempIPList as $v){
            if(empty($v)) continue;
            $temp = explode('.', $v);
            $n = count($temp);
            if(4 !== $n) $this->ajaxReturn(null, 'IP地址无效' , 0);
            foreach($temp as $item){
                $items = explode('/', $item);
                foreach($items as $it){
                    if(!is_numeric($it) || $it<0 || $it>255){
                        $this->ajaxReturn(null, "IP地址 {$v} 无效" , 0);
                    }
                }
            }
            //判断当前ip是否在里面 192.168.1.5/35
            if(false!==stripos($v, '/')){
                $t = explode('/', $temp[3]);
                $lastItem = $arrIP[3];
                if($temp[0]==$arrIP[0] && $temp[1]==$arrIP[1] && $temp[2]==$arrIP[2] &&
                    $lastItem>=$t[0] && $lastItem<=$t[1] ){
                    $isExist = true;
                }
            }else{
                if($v===$currentIP){
                    $isExist = true;
                }
            }
        }
        if(!$isExist){
            $this->ajaxReturn(null, "当前IP必须在 IP白名单 列表！" , 0);
        }
        $_POST['AdminWhiteIP'] = $ip;
    }

    /**
     * 批量替换图片地址，不对外公开
     */
    function batch(){
        header("Content-Type:text/html; charset=utf-8");
        $action = strtolower($_POST['Action']);
        if($action=='replaceimage'){ //批量替换图片
            $search = trim($_POST['Search']);
            $replace = trim($_POST['Replace']);
            if( empty($search) ){
                $this->ajaxReturn(null, '搜索图像路径字符串不能为空!' , 0);
            }
            if( empty($replace) ){
                $this->ajaxReturn(null, '替换字符串不能为空!' , 0);
            }
            if( $search==$replace ){
                $this->ajaxReturn(null, '检索串和替换串不能相同' , 0);
            }
            $n = 0;
            //1。替换配置 开始==
            $m = D('Admin/Config');
            $where = "ConfigName in('WEB_LOGO','WAP_LOGO','WX_LOGO','WX_QRCODE','APP_LOGO','WAP_QRCODE'";
            $where .= ",'XCX_QRCODE','APP_ABOUT')";
            $data = $m->where($where)->field('ConfigID,ConfigValue')->select();
            foreach ($data as $v){
                $wh = "ConfigID={$v['ConfigID']}";
                $ConfigValue = str_replace($search, $replace, $v['ConfigValue']);
                $result = $m->where($wh)->setField('ConfigValue', $ConfigValue);
                if($result){
                    $n++;
                }
            }
            //1.替换配置 结束==

            //2.替换信息 开始==
            $m = D('Admin/Info');
            $where = "IsEnable=1 and (InfoPicture!='' or InfoContent!='' )";
            $data = $m->where($where)->field('InfoID,InfoPicture,InfoContent')->select();
            foreach ($data as $v){
                $r = array();
                $picture = $v['InfoPicture'];
                $content = $v['InfoContent'];
                if(!empty($picture) || !empty($content)){
                    $wh = "InfoID={$v['InfoID']}";
                    if( 0 === strpos($picture, $search) ){
                        $r['InfoPicture'] = str_replace($search, $replace, $picture);
                    }
                    if( false === strpos($content, $replace)){
                        $r['InfoContent'] = str_replace($search, $replace, $content);
                    }
                    $result = $m->where($wh)->setField($r);
                    if($result){
                        $n++;
                    }
                }
            }
            //2.替换信息 结束==

            //3.替换频道 开始==
            $m = D('Admin/Channel');
            $where = "IsEnable=1 and (ChannelPicture!='' or ChannelContent!='' )";
            $data = $m->where($where)->field('ChannelID,ChannelPicture,ChannelContent')->select();
            foreach ($data as $v){
                $r = array();
                $picture = $v['ChannelPicture'];
                $content = $v['ChannelContent'];
                if(!empty($picture) || !empty($content)){
                    $wh = "ChannelID={$v['ChannelID']}";
                    if( 0 === strpos($picture, $search) ){
                        $r['ChannelPicture'] = str_replace($search, $replace, $picture);
                    }
                    if( false === strpos($content, $replace)){
                        $r['ChannelContent'] = str_replace($search, $replace, $content);
                    }
                    $result = $m->where($wh)->setField($r);
                    if($result){
                        $n++;
                    }
                }
            }
            //3.替换频道  结束==

            //4.替换幻灯片开始==
            $m = D('Admin/Banner');
            $where = "BannerImage!=''";
            $data = $m->where($where)->field('BannerID,BannerImage')->select();
            foreach ($data as $v){
                $r = array();
                $picture = $v['BannerImage'];
                if(!empty($picture)){
                    $wh = "BannerID={$v['BannerID']}";
                    if( 0 === strpos($picture, $search) ){
                        $r['BannerImage'] = str_replace($search, $replace, $picture);
                        $result = $m->where($wh)->setField($r);
                        if($result){
                            $n++;
                        }
                    }
                }
            }
            //4.替换幻灯片  结束==

            //5.替换专题 开始==
            $m = D('Admin/Special');
            $where = "SpecialPicture!=''";
            $data = $m->where($where)->field('SpecialID,SpecialPicture')->select();
            foreach ($data as $v){
                $r = array();
                $picture = $v['SpecialPicture'];
                if(!empty($picture)){
                    $wh = "SpecialID={$v['SpecialID']}";
                    if( 0 === strpos($picture, $search) ){
                        $r['SpecialPicture'] = str_replace($search, $replace, $picture);
                        $result = $m->where($wh)->setField($r);
                        if($result){
                            $n++;
                        }
                    }
                }
            }
            //5.替换专题  结束==

            //6.替换广告 开始==
            $m = D('Admin/Ad');
            $where = "AdContent!=''";
            $data = $m->where($where)->field('AdID,AdContent')->select();
            foreach ($data as $v){
                $r = array();
                $picture = $v['AdContent'];
                if(!empty($picture)){
                    $wh = "AdID={$v['AdID']}";
                    if( 0 === strpos($picture, $search) ){
                        $r['AdContent'] = str_replace($search, $replace, $picture);
                        $result = $m->where($wh)->setField($r);
                        if($result){
                            $n++;
                        }
                    }
                }
            }
            //6.替换广告  结束==

            //7.替换友情链接 开始==
            $m = D('Admin/Link');
            $where = "LinkLogo!=''";
            $data = $m->where($where)->field('LinkID,LinkLogo')->select();
            foreach ($data as $v){
                $r = array();
                $picture = $v['LinkLogo'];
                if(!empty($picture)){
                    $wh = "LinkID={$v['LinkID']}";
                    if( 0 === strpos($picture, $search) ){
                        $r['LinkLogo'] = str_replace($search, $replace, $picture);
                        $result = $m->where($wh)->setField($r);
                        if($result){
                            $n++;
                        }
                    }
                }
            }
            //7.替换友情链接  结束==

            if( $n>0 ){
                $this->ajaxReturn(null, "替换图像地址成功!共替换了{$n}个!", 1);
            }else{
                $this->ajaxReturn(null, '没有替换任何图像地址!' , 0);
            }
        }
        $this->assign("WebInstallDir", $this->WebInstallDir);
        $this->display();
    }

    /**
     * 后台主题颜色设置
     */
    function theme(){
        $m = D('Admin/Config');
        $data = $m->getConfig('basic'); //配置数据不从缓存中提取
        $this->assign('AdminThemeColor', $data['AdminThemeColor'] );
        $this->assign('AdminLeftMenuBgColor', $data['AdminLeftMenuBgColor'] );
        $this->assign('AdminLeftMenuTextColor', $data['AdminLeftMenuTextColor'] );
        $this->assign('AdminLeftMenuSelectedColor', $data['AdminLeftMenuSelectedColor'] );
        $this->assign('Action', __URL__.'/saveTheme' );
        $this->display();
    }

    /**
     * 保存主题颜色
     */
    function saveTheme(){
        if( empty($_POST['AdminThemeColor']) ){
            $this->ajaxReturn(null, '主题颜色不能为空!' , 0);
        }
        if( empty($_POST['AdminLeftMenuBgColor']) ){
            $this->ajaxReturn(null, '左侧菜单背景颜色不能为空!' , 0);
        }
        if( empty($_POST['AdminLeftMenuTextColor']) ){
            $this->ajaxReturn(null, '左侧菜单文本颜色不能为空!' , 0);
        }
        if( empty($_POST['AdminLeftMenuSelectedColor']) ){
            $this->ajaxReturn(null, '左侧菜单文本选中颜色不能为空!' , 0);
        }
        $fieldMap = array('AdminThemeColor'=>2, 'AdminLeftMenuBgColor'=>2, 'AdminLeftMenuTextColor'=>2, 'AdminLeftMenuSelectedColor'=>2);
        $data = GetConfigDataToSave($fieldMap);
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    /**
     * 回顶部
     */
    function goTop(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('other'); //配置数据不从缓存中提取
        $this->assign('GoTopStyle', $data['GoTopStyle']); //样式
        $this->assign('GoTopBgColor', $data['GoTopBgColor']); //背景颜色
        $this->assign('GoTopCornerSize', $data['GoTopCornerSize']);  //圆角大小
        $this->assign('GoTopWidth', $data['GoTopWidth']); //宽度
        $this->assign('GoTopHeight', $data['GoTopHeight']); //高度

        $this->assign('GoTopIconSize', $data['GoTopIconSize']); //图标大小
        $this->assign('GoTopIconColor', $data['GoTopIconColor']); //图标颜色
        $this->assign('GoTopEnable', $data['GoTopEnable']); //是否启用

        $this->assign('GoTopShadow', $data['GoTopShadow']); //是否启用
        $this->assign('GoTopBgColorHover', $data['GoTopBgColorHover']); //是否启用
        $this->assign('GoTopIconColorHover', $data['GoTopIconColorHover']); //是否启用
        //位置
        $this->assign('GoTopRight', $data['GoTopRight']);
        $this->assign('GoTopBottom', $data['GoTopBottom']);

        //图标列表
        $icons = array();
        for($i=1; $i<=23;$i++){
            $icons[] = "ydicon-gotop{$i}";
        }
        $this->assign('Icons', $icons );
        $this->assign('Action', __URL__.'/saveGoTop' );
        $this->display();
    }

    /**
     * 保存回顶部配置
     */
    function saveGoTop(){
        if(empty($_POST['GoTopStyle'])){
            $this->ajaxReturn(null, '请选择图标样式!' , 0);
        }
        $data = GetConfigDataToSave('', 'GoTop');
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            YdCache::deleteAll(); //清楚所有缓存
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    /**
     * 编辑器（主要用于在多端小程序装修以iframe形式调用）
     */
    function fileManager(){
        header("Content-Type:text/html; charset=utf-8");
        $this->display();
    }

    function job(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('job'); //配置数据不从缓存中提取
        $this->assign('c', $data );
        $this->assign('Action', __URL__.'/saveJob' );
        $this->display();
    }

    function saveJob(){
        $data = GetConfigDataToSave('', 'JOB_');
        if(1==$data['JOB_SENDEMAIL']){
            if(empty($data['JOB_EMAIL_TITLE'])){
                $this->ajaxReturn(null, '通知邮件标题不能为空!' , 0);
            }
        }
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    /**
     * 仅用于测试
     */
    function test(){
        header("Content-Type:text/html; charset=utf-8");
        $this->assign('Action', __URL__.'/saveTest' );
        $this->display();
    }
}