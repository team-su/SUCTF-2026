<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class AliossAction extends AdminBaseAction{
    protected $_aliOssErrorMsg = '';
    /**
     * 阿里OSS首页
     */
    function index(){
        header("Content-Type:text/html; charset=utf-8");
        $this->display("{$this->pluginDir}alioss.html");
    }

    /**
     * 阿里OSS配置
     */
    function aliossConfig() {
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('other'); //配置数据不从缓存中提取
        $AliEnable = empty($data['AliEnable']) ? 0 : $data['AliEnable'];
        $data['AliAccessKeySecret'] = HideSensitiveData($data['AliAccessKeySecret']);
        $this->assign('AliEnable', $AliEnable);
        $this->assign('AliAccessKeyID', $data['AliAccessKeyID']);
        $this->assign('AliAccessKeySecret', $data['AliAccessKeySecret']);
        $this->assign('AliBucketName', $data['AliBucketName']);
        $this->assign('AliEndpoint', $data['AliEndpoint']);
        $this->assign('Action', __URL__ . '/saveAliConfig');
        $this->display("{$this->pluginDir}aliossconfig.html");
    }

    /**
     * 保存阿里OSS配置
     */
    function saveAliConfig() {
        if ($_POST['AliEnable'] == 1) {
            //AccessKey
            $_POST['AliAccessKeyID'] = trim($_POST['AliAccessKeyID']);
            if (empty($_POST['AliAccessKeyID'])) {
                $this->ajaxReturn(null, 'AccessKey不能为空', 0);
            }
            //SecretKey
            $_POST['AliAccessKeySecret'] = trim($_POST['AliAccessKeySecret']);
            if (empty($_POST['AliAccessKeySecret'])) {
                $this->ajaxReturn(null, 'SecretKey不能为空', 0);
            }
            //存储空间名
            $_POST['AliBucketName'] = trim($_POST['AliBucketName']);
            if (empty($_POST['AliBucketName'])) {
                $this->ajaxReturn(null, '存储空间名不能为空', 0);
            }

            //访问域名结尾不能为/
            $_POST['AliEndpoint'] = strtolower(trim($_POST['AliEndpoint']));
            $_POST['AliEndpoint'] = trim($_POST['AliEndpoint'], '/');
            if (empty($_POST['AliEndpoint'])) {
                $this->ajaxReturn(null, '访问域名不能为空', 0);
            }
            if ('http' != substr($_POST['AliEndpoint'], 0, 4)) {
                $this->ajaxReturn(null, '访问域名必须以http开头', 0);
            }
        }
        $data = GetConfigDataToSave('', 'Ali');
        $m = D("Admin/Config");
        if ($m->saveConfig($data)) {
            unset($data['AliAccessKeySecret']);
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!', 1);
        } else {
            $this->ajaxReturn(null, '保存失败!', 0);
        }

    }

    /*
     * 阿里OSS 获取实例
     * */
    private function getAliOssInstance() {
        //引入阿里sdk的方式，有三种，三选一即可
        require_once('App/Lib/Common/AliOss/autoload.php');      //1、源码方式
        //2、composer方式
        //   3、phar方式

        $AliAccessKeyID = $GLOBALS['Config']['AliAccessKeyID'];
        $AliAccessKeySecret = $GLOBALS['Config']['AliAccessKeySecret'];
        $AliBucketName = $GLOBALS['Config']['AliBucketName'];
        $AliEndpoint = $GLOBALS['Config']['AliEndpoint'];
        try {
            $ossClient = new \OSS\OssClient($AliAccessKeyID, $AliAccessKeySecret, $AliEndpoint);
            return $ossClient;
        } catch (Exception $e) {
            $this->_aliOssErrorMsg = $e->getMessage();
            return false;
        }
    }

    /*
     * 阿里OSS 获取指定目录的子目录
     * */
    function getAliOssDir(){
        $dir = trim($_POST['Dir']); //当前目录
        $ossClient = $this->getAliOssInstance();

        $options = array(
            'prefix' =>$dir,
        );
        $AliBucketName = $GLOBALS['Config']['AliBucketName'];
        $listObjectInfo = $ossClient->listObjects($AliBucketName, $options);
        $listPrefix = $listObjectInfo->getPrefixList(); // directory list
        if (!empty($listPrefix)) {
            foreach ($listPrefix as $v) {
                $temp = explode('/', trim($v->getPrefix(), '/')  );
                $name = end($temp);
                $depth = count($temp);
                $result[] = array('DirName'=>$name, 'DirKey'=>$v->getPrefix(), 'DirDepth'=>$depth);
            }
        }
        if(false !== $result){
            $this->ajaxReturn($result, '' , 1);
        }else{
            $this->ajaxReturn(null, "获取目录失败！", 0);
        }
    }

    /**
     * 阿里OSS 选定的文件，移动到指定目录
     * */
    function aliOssMove() {
        $filesToMove = $_POST['Key'];
        if (!is_array($filesToMove) || count($filesToMove) == 0) {
            $this->ajaxReturn(null, '请先选择要移动的文件！', 0);
        }
        $DstDir = trim($_REQUEST['DstDir']);
        try {
            $ossClient = $this->getAliOssInstance();
            $AliBucketName = $GLOBALS['Config']['AliBucketName'];
            foreach ($filesToMove as $v) {
                $temp = explode('/', trim($v, '/'));
                $name = end($temp);
                $fromFile = $v;
                $toFile = $DstDir . $name;
                if ($fromFile == $toFile) {
                    $this->ajaxReturn(null, '移动失败！file exists', 0);
                }
                $ossClient->copyObject($AliBucketName, $fromFile, $AliBucketName, $toFile);
                $ossClient->deleteObject($AliBucketName, $v);
            }
            $this->ajaxReturn(null, '移动成功！', 1);
        } catch (Exception $e) {
            $this->ajaxReturn(null, "移动失败！{$e->getMessage()}", 0);
        }
    }

    /**
     * 阿里OSS目录重命名
     * */
    function aliossRenameDir() {
        $OldDirName = trim($_POST['OldDirName']);
        $NewDirName = trim($_POST['NewDirName']);
        $Prefix = trim($_POST['Prefix']); //当前目录
        if (empty($NewDirName)) {
            $this->ajaxReturn(null, '目录名不能为空', 0);
        }
        if(false!==strpos($NewDirName, '/')){
            $this->ajaxReturn(null, '无效目录名，不能包含 / ' , 0);
        }
        if ($OldDirName == $NewDirName) {
            $this->ajaxReturn(null, '新目录名和旧目录名不能相同', 0);
        }

        $AliEnable = $GLOBALS['Config']['AliEnable'];
        if ($AliEnable == 1) {
            try {
                $ossClient = $this->getAliOssInstance();
                $AliBucketName = $GLOBALS['Config']['AliBucketName'];
                $this->getRenameDir($Prefix, $OldDirName, $NewDirName, $ossClient);
                $data['Name'] = $NewDirName;
                $data['Key'] = $Prefix . $NewDirName . '/';

                $this->ajaxReturn($data, '重命名成功！', 1);
            } catch (Exception $e) {
                $this->ajaxReturn(null, "重命名失败！{$e->getMessage()}", 0);
            }
        }
    }

    /**
     * 阿里OSS，判断指定文件，是否存在
     */
    function getObjectExist() {
        echo 0;//暂时不考虑是否存在，以后再考虑。2021年9月7日15:20:58
       /* $NewFileName = $_REQUEST['FileName'];
        $ossClient = $this->getAliOssInstance();
        $AliBucketName = $GLOBALS['Config']['AliBucketName'];

        $exist = $ossClient->doesObjectExist($AliBucketName, $NewFileName);
        if ($exist) {//存在，返回1
            echo 1;
        } else {//不存在，返回0
            echo 0;
        }*/
    }

    /**
     * 阿里OSS，用递归方式，获取当前目录下的，所有级别的子目录和文件
     * */
    private function getRenameDir($Prefix, $OldDirName, $NewDirName, $ossClient) {
        $renameObjectList = array();
        $options = array(
            'prefix' => $Prefix . $OldDirName . '/',
        );
        $AliBucketName = $GLOBALS['Config']['AliBucketName'];
        $listObjectInfo = $ossClient->listObjects($AliBucketName, $options);
        $listObject = $listObjectInfo->getObjectList(); // file list
        if (!empty($listObject)) {
            foreach ($listObject as $vo) {
                if ($vo->getKey() == $Prefix . $OldDirName . '/') {//末级目录
                    $ossClient->copyObject($AliBucketName, $Prefix . $OldDirName . '/', $AliBucketName, $Prefix . $NewDirName . '/');
                    $ossClient->deleteObject($AliBucketName, $Prefix . $OldDirName . '/');
                } else {//文件
                    $tempFileName = str_replace($Prefix . $OldDirName . '/', '', $vo->getKey());
                    $ossClient->copyObject($AliBucketName, $vo->getKey(), $AliBucketName, $Prefix . $NewDirName . '/' . $tempFileName);
                    $ossClient->deleteObject($AliBucketName, $vo->getKey());
                }
            }
        }

        $listPrefix = $listObjectInfo->getPrefixList(); // directory list
        if (!empty($listPrefix)) {    //若当前目录有子目录，则求出各个子目录
            foreach ($listPrefix as $vo) {
                $NewDirName2 = rtrim(str_replace($Prefix, '', str_replace($OldDirName, $NewDirName, $vo->getPrefix())), '/');
                $OldDirName2 = rtrim(str_replace($Prefix, '', $vo->getPrefix()), '/');

                $renameObjectList = $this->getRenameDir($Prefix, $OldDirName2, $NewDirName2, $ossClient);

            }
        } else {  //若当前目录没有子目录了

        }
        return $renameObjectList;  //返回待删列表
    }

    /**
     * 阿里OSS文件重命名
     * */
    function aliossRename() {
        $OldFileName = trim($_POST['OldFileName']);
        $NewFileName = trim($_POST['NewFileName']);
        $Prefix = trim($_POST['Prefix']); //当前目录
        if (empty($NewFileName)) {
            $this->ajaxReturn(null, '文件名不能为空', 0);
        }
        if(false!==strpos($NewFileName, '/')){
            $this->ajaxReturn(null, '无效文件名，不能包含 / ' , 0);
        }
        if ($OldFileName == $NewFileName) {
            $this->ajaxReturn(null, '新文件名和旧文件名不能相同', 0);
        }

        $AliEnable = $GLOBALS['Config']['AliEnable'];
        if ($AliEnable == 1) {
            try {
                $ossClient = $this->getAliOssInstance();
                $AliBucketName = $GLOBALS['Config']['AliBucketName'];

                $exist = $ossClient->doesObjectExist($AliBucketName, $Prefix.$NewFileName);
                if($exist){
                    $this->ajaxReturn(null, "文件 {$NewFileName} 已经存在！", 0);
                }

                $ossClient->copyObject($AliBucketName, $Prefix.$OldFileName, $AliBucketName, $Prefix.$NewFileName);
                $ossClient->deleteObject($AliBucketName, $Prefix.$OldFileName);

                $data['FileName'] = $NewFileName;
                $data['NewFileName'] = $Prefix.$NewFileName;
                $AliEndpoint=end(explode('//', $GLOBALS['Config']['AliEndpoint']));
                $data['FileUrl'] = "http://{$GLOBALS['Config']['AliBucketName']}.{$AliEndpoint}" . '/' . ltrim($NewFileName, '/');
                $this->ajaxReturn($data, '重命名成功！', 1);
            } catch (Exception $e) {
                $this->ajaxReturn(null, "重命名失败！{$e->getMessage()}", 0);
            }
        }
    }

    /**
     * 阿里OSS文件列表
     */
    function aliossFile() {
        header("Content-Type:text/html; charset=utf-8");
        $ObjectList = array();
        $AliEnable = $GLOBALS['Config']['AliEnable'];
        if ($AliEnable == 1) {
            try {
                $AliBucketName = $GLOBALS['Config']['AliBucketName'];
                $ossClient = $this->getAliOssInstance();
                if(empty($ossClient)){
                    exit($this->_aliOssErrorMsg);
                }

                $nextMarker = !empty($_REQUEST['Marker']) ? $_REQUEST['Marker'] : "";
                $Prefix = !empty($_REQUEST['Prefix']) ? $_REQUEST['Prefix'] : "";
                $KeyWords = !empty($_REQUEST['KeyWords']) ? $_REQUEST['KeyWords'] : "";//文件名前缀 ，也就是文件名左边包含了这个字符串

                if (!empty($Prefix)) {//求上一级目录的路径
                    $PrefixArr = explode('/', $Prefix);
                    $rightnStr = $PrefixArr[count($PrefixArr) - 2] . '/';
                    $PreviousPrefix = str_replace($rightnStr, '', $Prefix);
                }

                $PageSize = 1000;
                $options = array(
                    'delimiter' => '/',
                    'prefix' => $Prefix . $KeyWords,
                    'max-keys' => $PageSize,
                    'marker' => $nextMarker,
                );
                $listObjectInfo = $ossClient->listObjects($AliBucketName, $options);
                $nextMarker = $listObjectInfo->getNextMarker();// 得到nextMarker，从上一次listObjects读到的最后一个文件的下一个文件开始继续获取文件列表。
                $listObject = $listObjectInfo->getObjectList(); // file list
                $listPrefix = $listObjectInfo->getPrefixList(); // directory list

                $k = 0;
                if (!empty($listPrefix)) {
                    foreach ($listPrefix as $v) {
                        $str = str_replace($Prefix, '', $v->getPrefix());
                        $str = substr($str, 0, strlen($str) - 1);
                        $ObjectList[$k]['filename'] = $str;
                        $ObjectList[$k]['key'] = $v->getPrefix();

                        $ObjectList[$k]['filetype'] = "目录";
                        $ObjectList[$k]['fsize'] = "";
                        $ObjectList[$k]['ico'] = $this->WebPublic . 'Images/FileICO/dir.gif';
                        $k++;
                    }
                }

                if (!empty($listObject)) {
                    $AliEndpoint = end(explode('//', $GLOBALS['Config']['AliEndpoint']));
                    $domain = "http://{$GLOBALS['Config']['AliBucketName']}.{$AliEndpoint}";;
                    foreach ($listObject as $v) {
                        if ($v->getKey() == $Prefix) {
                            continue;
                        }
                        $str = str_replace($Prefix, '', $v->getKey());
                        $ObjectList[$k]['filename'] = $str;
                        $ObjectList[$k]['key'] = $v->getKey();
                        $ext = strtolower(yd_file_ext($v->getKey()));
                        $ObjectList[$k]['filetype'] = getTplFileType(".{$ext}");
                        $ObjectList[$k]['fsize'] = byte_format($v->getSize());
                        $ts = intval($v->getLastModified() / 10000000);
                        $ObjectList[$k]['putTime'] = date('Y-m-d H:i:s', strtotime($v->getLastModified()));
                        $extFile = './Public/Images/FileICO/' . $ext . '.gif';
                        if (is_file($extFile)) {
                            $ObjectList[$k]['ico'] = $this->WebPublic . 'Images/FileICO/' . $ext . '.gif';
                        } else {
                            $ObjectList[$k]['ico'] = $this->WebPublic . 'Images/FileICO/unknown.gif';
                        }
                        $ObjectList[$k]['fileurl'] = $domain . '/' . ltrim($v->getKey(), '/');

                        $k++;
                    }
                }
            } catch (Exception $e) {
                exit("页面加载失败！{$e->getMessage()}");
            }
        }

        $dir = $Prefix;
        $NavList = array();
        $NavList[] = array('Name' => "资源根", 'Dir' => '');
        if (!empty($dir)) {
            $temp = explode('/', trim($dir, '/'));
            $str = '';
            foreach ($temp as $v) {
                $str .= "{$v}/";
                $NavList[] = array('Name' => $v, 'Dir' => $str);
            }
        }
        $this->assign('NavList', $NavList);

        $this->assign('NextMarker', $nextMarker);
        $this->assign('PageSize', $PageSize);
        $this->assign('Prefix', $Prefix);
        $this->assign('PreviousPrefix', $PreviousPrefix);
        $this->assign('KeyWords', $KeyWords);
        $this->assign('Data', $ObjectList);
        $this->assign('GetAliUploadFile', __URL__ . '/getAliUploadFile');
        $this->display("{$this->pluginDir}aliossfile.html");
    }

    /**
     * 阿里OSS文件上传
     */
    function getAliUploadFile() {
        $id = $GLOBALS['Config']['AliAccessKeyID'];          // 请填写您的AccessKeyId。
        $key = $GLOBALS['Config']['AliAccessKeySecret'];     // 请填写您的AccessKeySecret。
// $host的格式为 bucketname.endpoint，请替换为您的真实信息。
        $AliEndpoint=end(explode('//', $GLOBALS['Config']['AliEndpoint']));
        $host = "http://{$GLOBALS['Config']['AliBucketName']}.{$AliEndpoint}";
// $callbackUrl为上传回调服务器的URL，请将下面的IP和Port配置为您自己的真实URL信息。
        $callbackUrl = "";
        $dir = $_REQUEST['Prefix'];          // 用户上传文件时指定的前缀。

        $callback_param = array(
            'callbackUrl' => $callbackUrl,
            'callbackBody' => 'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
            'callbackBodyType' => "application/x-www-form-urlencoded"
        );
        $callback_string = json_encode($callback_param);

        $base64_callback_body = base64_encode($callback_string);
        $now = time();
        $expire = 30;  //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问。
        $end = $now + $expire;
        $expiration = str_replace('+00:00', '.000Z', gmdate('c', $end));

//最大文件大小.用户可以自己设置
        $condition = array(0 => 'content-length-range', 1 => 0, 2 => 1048576000);
        $conditions[] = $condition;

// 表示用户上传的数据，必须是以$dir开始，不然上传会失败，这一步不是必须项，只是为了安全起见，防止用户通过policy上传到别人的目录。
        $start = array(0 => 'starts-with', 1 => '$key', 2 => $dir);
        $conditions[] = $start;


        $arr = array('expiration' => $expiration, 'conditions' => $conditions);
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $key, true));

        $response = array();
        $response['accessid'] = $id;
        $response['host'] = $host;
        $response['policy'] = $base64_policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        $response['callback'] = $base64_callback_body;
        $response['dir'] = $dir;  // 这个参数是设置用户上传文件时指定的前缀。
        echo json_encode($response);

    }

    /**
     * 阿里OSS文件删除
     */
    function aliossDelFile() {
        $Key = $_POST['Key'];
        $DirKey = $_POST['DirKey'];
        if(!is_array($Key) && !is_array($DirKey)){
            $this->ajaxReturn(null, '请选择文件！' , 0);
        }
        $DelKey = array_merge((array)$Key,(array)$DirKey);
        $AliEnable = $GLOBALS['Config']['AliEnable'];
        if ($AliEnable == 1) {
            $AliBucketName = $GLOBALS['Config']['AliBucketName'];
            try {
                $ossClient = $this->getAliOssInstance();
                $delObjectList = array();
                foreach ($DelKey as $k => $v) {
                    $options = array(
                        'prefix' => $v,
                    );
                    $listObjectInfo = $ossClient->listObjects($AliBucketName, $options);
                    $listObject = $listObjectInfo->getObjectList(); // object list
                    foreach ($listObject as $vo) {
                        $delObjectList[] = $vo->getKey();
                    }
                    $listPrefix = $listObjectInfo->getPrefixList(); // directory list
                    foreach ($listPrefix as $vo) {
                        $delObjectList = array_merge($delObjectList, $this->getDelObjectList($vo->getPrefix(), $ossClient));
                    }
                }
                $result = $ossClient->deleteObjects($AliBucketName, $delObjectList);
                $this->ajaxReturn(null, '删除成功！', 1);
            } catch (Exception $e) {
                $this->ajaxReturn(null, "删除失败！{$e->getMessage()}", 0);
            }
        }
    }

    /**
     * 阿里OSS，用递归方式，获取当前目录下的，所有级别的子目录和文件
     * */
    private function getDelObjectList($dirName, $ossClient) {
        $delObjectList = array();
        $options = array(
            'prefix' => $dirName,
        );
        $AliBucketName = $GLOBALS['Config']['AliBucketName'];
        $listObjectInfo = $ossClient->listObjects($AliBucketName, $options);
        $listObject = $listObjectInfo->getObjectList(); // object list
        if (!empty($listObject)) {
            foreach ($listObject as $vo) {
                $delObjectList[] = $vo->getKey();
            }
        }

        $listPrefix = $listObjectInfo->getPrefixList(); // directory list
        if (!empty($listPrefix)) {    //若当前目录有子目录，则求出各个子目录的待删目录
            foreach ($listPrefix as $vo) {
                $delObjectList = array_merge($delObjectList, $this->getDelObjectList($vo->getPrefix(), $ossClient));
            }
        } else {  //若当前目录没有子目录了，则将当前目录收集到待删列表中
            $delObjectList[] = $dirName;
        }

        return $delObjectList;  //返回待删列表

    }

    /**
     * 阿里OSS新建目录
     */
    function aliossCreateDir() {
        $DirName = trim($_REQUEST['DirName']);
        $Prefix = trim($_REQUEST['Prefix']);

        if (empty($DirName)) {
            $this->ajaxReturn(null, '目录名称不能为空！', 0);
        }
        $DirNameArr=explode('/',$DirName);
        foreach ($DirNameArr as $v){
            if(empty($v)){
                $this->ajaxReturn(null, '目录名称不能为空！', 0);
            }
        }

        $AliEnable = $GLOBALS['Config']['AliEnable'];
        if ($AliEnable == 1) {
            $AliBucketName = $GLOBALS['Config']['AliBucketName'];
            try {
                $ossClient = $this->getAliOssInstance();
                $NewDirName= $Prefix . $DirName . '/';
                $exist = $ossClient->doesObjectExist($AliBucketName, $NewDirName);
                if($exist){
                    $this->ajaxReturn(null, "目录 {$DirName} 已经存在！", 0);
                }
                $result = $ossClient->PutObject($AliBucketName,$NewDirName , "");

                $this->ajaxReturn(null, '创建成功！', 1);
            } catch (Exception $e) {
                $this->ajaxReturn(null, "创建失败！{$e->getMessage()}", 0);
            }
        }
    }

    /**
     * 阿里OSS文件上传2
     */
    function aliossUploadFile() {
        $Prefix = !empty($_REQUEST['Prefix']) ? $_REQUEST['Prefix'] : "";
        $AliEnable = $GLOBALS['Config']['AliEnable'];
        if ($AliEnable == 1) {
            $AliBucketName = $GLOBALS['Config']['AliBucketName'];
            try {
                $ossClient = $this->getAliOssInstance();
                $result = $ossClient->uploadFile($AliBucketName, $Prefix . $_FILES['uploadFile']['name'], $_FILES['uploadFile']['tmp_name']);

                $this->ajaxReturn(null, '上传成功！', 1);
            } catch (Exception $e) {
                $this->ajaxReturn(null, "上传失败！{$e->getMessage()}", 0);
            }
        }
    }

}