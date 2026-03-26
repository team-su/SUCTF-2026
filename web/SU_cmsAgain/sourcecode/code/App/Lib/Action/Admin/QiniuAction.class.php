<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class QiniuAction extends AdminBaseAction{
    /**
     * 七牛首页
     */
    function index(){
        header("Content-Type:text/html; charset=utf-8");
        $this->display("{$this->pluginDir}qiniu.html");
    }

    /**
     * 创建七牛云对象实例
     */
    private function getQiniuInstance(){
        $c = &$GLOBALS['Config'];
        if(empty($c['QiniuEnable'])){
            $this->ajaxReturn(null, '功能未启用，请在【基本设置】启用！' , 0);
        }
        import("@.Common.YdQiniu");
        $config['secretKey'] = $c['QiniuSecretKey'];
        $config['accessKey'] = $c['QiniuAccessKey'];
        $config['domain'] = $c['QiniuUrl'];
        $config['bucket'] = $c['QiniuBucketName'];
        $obj = new YdQiniu($config);
        return $obj;
    }

    /**
     * 七牛云存储设置
     */
    function config(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('other'); //配置数据不从缓存中提取

        //搜索引擎优化设置
        $QiniuEnable = empty($data['QiniuEnable']) ? 0 : $data['QiniuEnable'];
        $data['QiniuSecretKey'] = HideSensitiveData($data['QiniuSecretKey']);
        $this->assign('QiniuEnable', $QiniuEnable );
        $this->assign('QiniuAccessKey', $data['QiniuAccessKey']);
        $this->assign('QiniuSecretKey', $data['QiniuSecretKey'] );
        $this->assign('QiniuBucketName', $data['QiniuBucketName'] );
        $this->assign('QiniuUrl', $data['QiniuUrl'] );
        $this->assign('QiniuMirrorUrl', $data['QiniuMirrorUrl'] );
        $this->assign('QiniuInterlaceEnable', $data['QiniuInterlaceEnable'] );
        $this->assign('QiniuFileType', $data['QiniuFileType'] );
        $this->assign('Action', __URL__.'/saveConfig' );
        $this->display("{$this->pluginDir}qiniuconfig.html");
    }

    /**
     * 保存七牛云存储
     */
    function saveConfig(){
        if($_POST['QiniuEnable']==1){
            //AccessKey
            $_POST['QiniuAccessKey'] = trim($_POST['QiniuAccessKey']);
            if(empty($_POST['QiniuAccessKey'])){
                $this->ajaxReturn(null, 'AccessKey不能为空' , 0);
            }
            //SecretKey
            $_POST['QiniuSecretKey'] = trim($_POST['QiniuSecretKey']);
            if(empty($_POST['QiniuSecretKey'])){
                $this->ajaxReturn(null, 'SecretKey不能为空' , 0);
            }
            //存储空间名
            $_POST['QiniuBucketName'] = trim($_POST['QiniuBucketName']);
            if(empty($_POST['QiniuBucketName'])){
                $this->ajaxReturn(null, '存储空间名不能为空' , 0);
            }

            //访问域名结尾不能为/
            $_POST['QiniuUrl'] = strtolower(trim($_POST['QiniuUrl']));
            $_POST['QiniuUrl'] = trim($_POST['QiniuUrl'],'/');
            if(empty($_POST['QiniuUrl'])){
                $this->ajaxReturn(null, '访问域名不能为空' , 0);
            }
            if('http' != substr($_POST['QiniuUrl'], 0, 4)){
                $this->ajaxReturn(null, '访问域名必须以http开头' , 0);
            }

            //镜像源（镜像不是必须的）
            $_POST['QiniuMirrorUrl'] = strtolower(trim($_POST['QiniuMirrorUrl']));
            $_POST['QiniuMirrorUrl'] = trim($_POST['QiniuMirrorUrl'],'/');
            if(!empty($_POST['QiniuMirrorUrl'])){
                /*
                if(empty($_POST['QiniuMirrorUrl'])){
                    $this->ajaxReturn(null, '镜像源不能为空' , 0);
                }
                */
                if('http' != substr($_POST['QiniuMirrorUrl'], 0, 4)){
                    $this->ajaxReturn(null, '镜像源域名必须以http开头' , 0);
                }
                //扩展名有效性检查
                $QiniuFileType = str_replace(' ', '', trim($_POST['QiniuFileType']));
                $QiniuFileType = trim($QiniuFileType, '|');
                if(empty($_POST['QiniuFileType'])){
                    $this->ajaxReturn(null, '扩展名不能为空' , 0);
                }
                if(!preg_match('/^[a-zA-Z0-9|]+$/', $QiniuFileType) ){
                    $this->ajaxReturn(null, '扩展名格式包含无效字符' , 0);
                }
                $_POST['QiniuFileType'] = $QiniuFileType;
                //连接七牛云，设置镜像源地址
                import("@.Common.YdQiniu");
                $config['secretKey'] = $_POST['QiniuSecretKey'];
                $config['accessKey'] = $_POST['QiniuAccessKey'];
                $config['domain'] = $_POST['QiniuUrl'];
                $config['bucket'] = $_POST['QiniuBucketName'];
                $obj = new YdQiniu($config);
                $response = $obj->setMirrorUrl($_POST['QiniuMirrorUrl']);
                if( false===$response){
                    $msg = $obj->getLastError();
                    $this->ajaxReturn(null, "设置镜像域名失败！{$msg}" , 0);
                }
            }
        }
        $data = GetConfigDataToSave('', 'Qiniu');
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            unset($data['QiniuSecretKey']);
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    function file(){
        header("Content-Type:text/html; charset=utf-8");
        $result = array('items'=>array(), 'commonPrefixes'=>array());
        $dir = !empty($_REQUEST['Dir']) ? trim($_REQUEST['Dir']) : '';  // 当前目录：结尾带/
        $QiniuEnable = $GLOBALS['Config']['QiniuEnable'];
        $domain = $GLOBALS['Config']['QiniuUrl'];
        if($QiniuEnable == 1){
            $obj = $this->getQiniuInstance();
            $query = array();
            $query['prefix'] = $dir;
            $result = $obj->getAllList($query);
            $obj->formatData($result);
            //文件上传===================================================
            $UploadToken = $obj->UploadToken();
            $MaxUploadSize = "5120"; //最大上传大小
            $this->assign('MaxUploadSize', $MaxUploadSize);
            $this->assign('UploadToken', $UploadToken);
            $this->assign('Domain', $domain);
            //==========================================================
        }
        $hasData = (empty($result['commonPrefixes']) && empty($result['items'])) ? false : true;
        $NavList = array();
        $NavList[] = array('Name'=>"资源根", 'Dir'=>'');
        if(!empty($dir)){
            $temp = explode('/', trim($dir, '/'));
            $str = '';
            foreach($temp as $v){
                $str .= "{$v}/";
                $NavList[] = array('Name'=>$v, 'Dir'=>$str);
            }
        }
        $this->assign('NavList', $NavList);

        $this->assign('QiniuEnable', $QiniuEnable);
        $this->assign('Dir', $dir);
        $this->assign('DirData', $result['commonPrefixes']); //目录数据
        $this->assign('Data', $result['items']);
        $this->assign('HasData', $hasData);
        $this->display("{$this->pluginDir}qiniufile.html");
    }

    /**
     * 获取七牛储存空间对应的域名
     */
    function getBucketDomain(){
        header("Content-Type:text/html; charset=utf-8");
        import("@.Common.YdQiniu");
        $config['secretKey'] = $_POST['sk'];
        $config['accessKey'] = $_POST['ak'];
        $config['bucket'] = $_POST['bucket'];
        $obj = new YdQiniu($config);
        $res = $obj->getBucketDomain();
        if( !empty($res) ){
            $domain = implode('&nbsp;&nbsp;&nbsp;&nbsp;', $res);
            $msg = "当前储存空间域名：{$domain}";
            //获取推荐域名，一般赠送的域名是4级域名，如：ozrvg3g67.bkt.clouddn.com
            $suggest = $res[0];
            foreach ($res as $v){
                $n = substr_count($v,'.');
                if($n<=2){
                    $suggest = $v;
                    break;
                }
            }
            $suggest = get_current_protocal().$suggest;
            $this->ajaxReturn($suggest, $msg , 1);
        }else{
            $this->ajaxReturn(null, '获取域名失败，请检查AccessKey、SecretKey、存储空间名是否正确！' , 0);
        }
    }

    /**
     * 七牛文件重命名
     */
    function rename(){
        $OldFileName = trim($_POST['OldFileName']);
        $NewFileName = trim($_POST['NewFileName']);
        $Dir = trim($_POST['Dir']); //当前目录
        if(empty($NewFileName)){
            $this->ajaxReturn(null, '文件名不能为空' , 0);
        }
        if(false!==strpos($NewFileName, '/')){
            $this->ajaxReturn(null, '无效文件名，不能包含 / ' , 0);
        }
        if($OldFileName==$NewFileName){
            $this->ajaxReturn(null, '新文件名和旧文件名不能相同' , 0);
        }
        $obj = $this->getQiniuInstance();
        $result= $obj->rename($Dir.$OldFileName, $Dir.$NewFileName);
        if( false !== $result){
            $domain = $GLOBALS['Config']['QiniuUrl'];
            $data['Name'] = $NewFileName;
            $data['Key'] = $Dir.$NewFileName;
            $data['FileUrl'] = $domain.'/'.ltrim($Dir.$NewFileName,'/');
            $this->ajaxReturn($data, '重命名成功！' , 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "重命名失败！{$msg}", 0);
        }
    }

    /**
     * 获取指定目录的子目录
     */
    function getDir(){
        $dir = trim($_POST['Dir']); //当前目录
        $obj = $this->getQiniuInstance();
        $result = $obj->getDir($dir);
        if(false !== $result){
            $result = $obj->formatDirData($result);
            $this->ajaxReturn($result, '' , 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "获取目录失败！{$msg}", 0);
        }
    }

    /**
     * 创建空目录
     */
    function createDir(){
        $DirName = trim($_POST['DirName']);
        $Dir = trim($_POST['Dir']); //当前目录
        if(empty($DirName)){
            $this->ajaxReturn(null, '目录名称不能为空' , 0);
        }
        $obj = $this->getQiniuInstance();
        $myDir = trim($Dir.$DirName, '/');
        //结尾必须附加/，表示创建目录
        $obj->autoSetUpHost();
        $result= $obj->createDir($myDir.'/');
        if( false !== $result){
            $this->ajaxReturn(null, "创建成功！", 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "创建失败！{$msg}" , 0);
        }
    }

    /**
     * 重命名目录
     */
    function renameDir(){
        $OldDirName = trim($_POST['OldDirName']);
        $NewDirName = trim($_POST['NewDirName']);
        $Dir = trim($_POST['Dir']); //当前目录
        if(empty($NewDirName)){
            $this->ajaxReturn(null, '目录名不能为空' , 0);
        }
        if(false!==strpos($NewDirName, '/')){
            $this->ajaxReturn(null, '无效目录名，不能包含 / ' , 0);
        }
        if($OldDirName==$NewDirName){
            $this->ajaxReturn(null, '新目录名和旧目录名不能相同' , 0);
        }
        $obj = $this->getQiniuInstance();
        $result= $obj->renameDir($Dir.$OldDirName, $Dir.$NewDirName);
        if( false !== $result){
            $data['Name'] = $NewDirName;
            $data['Key'] = $Dir.$NewDirName;
            $this->ajaxReturn($data, '重命名成功！' , 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "重命名失败！{$msg}", 0);
        }
    }

    /**
     * 七牛删除文件
     */
    function delFile(){
        if(!is_array($_POST['Key']) && !is_array($_POST['DirKey'])){
            $this->ajaxReturn(null, '请选择文件！' , 0);
        }
        $obj = $this->getQiniuInstance();
        //删除目录里的文件
        $allDataToDel = $_POST['Key'];
        foreach($_POST['DirKey'] as $dir){
            $query['prefix'] = $dir;
            $result = $obj->getAllList($query, false);
            foreach($result['items'] as $v){
                $allDataToDel[] = $v['key'];
            }
        }
        //删除文件列表
        $result= $obj->delBatch($allDataToDel);
        if( false !== $result){
            $this->ajaxReturn(null, '删除成功！' , 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "删除失败！{$msg}" , 0);
        }
    }

    /**
     * 移动（暂不实现移动目录）
     */
    function move(){
        $filesToMove = $_POST['Key'];
        if(!is_array($filesToMove) || count($filesToMove)==0){
            $this->ajaxReturn(null, '请先选择要移动的文件！' , 0);
        }
        $DstDir = trim($_REQUEST['DstDir']);
        $isOverWrite = intval($_REQUEST['IsOverWrite']);
        import("@.Common.YdResource");
        $obj = new YdResourceQiniu();
        $result= $obj->moveFile($filesToMove, $DstDir, $isOverWrite);
        if( false !== $result){
            $this->ajaxReturn(null, '移动成功！' , 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "移动失败！{$msg}" , 0);
        }
    }
}