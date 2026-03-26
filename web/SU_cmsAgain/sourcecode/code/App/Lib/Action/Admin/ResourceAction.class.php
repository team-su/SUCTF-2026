<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
//上传资源管理
class ResourceAction extends AdminBaseAction{
    /**
     * 获取上传参数
     */
    public function getUploadParams(){
        $obj = $this->getInstance();
        $result = $obj->getUploadParams();
        if(false !== $result){
            $this->ajaxReturn($result, '', 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "获取上传参数失败！{$msg}", 0);
        }
    }

    /**
     * 获取上传Token，可以用于控制是否覆盖上传
     */
    public function getUploadToken(){
        $obj = $this->getInstance();
        $params = array();
        $params['key'] = $_POST['FileKey']; //文件全全路径
        $insertOnly = (1 == intval($_POST['IsOverWrite'])) ? 0 : 1; //实现覆盖或不覆盖上传
        $params['insertOnly'] = $insertOnly;
        $token = $obj->getUploadToken($params);
        if(false !== $token){
            $this->ajaxReturn($token, '', 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "获取上传Token失败！{$msg}", 0);
        }
    }

    /**
     * 获取对象实例
     */
    private function getInstance(){
        import("@.Common.YdResource");
        $source = intval($_POST['DataSource']);
        $obj = YdResource::getInstance($source);
        if(empty($obj)){
            $this->ajaxReturn(null, "创建对象失败！", 0);
        }
        $CurrentDir = $_POST['CurrentDir'];
        if(!empty($CurrentDir)){
            //当前目录名，必须以/结束
            if('/' !== substr($CurrentDir, -1)){
                $this->ajaxReturn(null, "当前目录必须以/结束！", 0);
            }
            //本地存储目录判断
            if(1 == $source){
                if(0 !== stripos($CurrentDir, './Upload/') || false !== stripos($CurrentDir, '..') || !is_dir($CurrentDir)){
                    $this->ajaxReturn(null, "当前目录无效！", 0);
                }
            }
        }
        //设置当前目录参数必传
        $obj->setCurrentDir($CurrentDir);
        return $obj;
    }

    //====================目录操作函数  开始====================

    /**
     * 获取目录列表
     */
    function getDir(){
        $this->_checkDataSource();
        $this->_autoCreateUploadDir();
        $obj = $this->getInstance();
        //这里不能传目录名称，必须是全路径，否则无法根据名称准确获取全路径
        //查看目录、创建目录和删除目录与当前目录无关，所以必须传全路径
        //而查看文件、删除文件、复制文件、移动文件与当前目录有关，只需要传名称即可
        $dir = !empty($_POST['Dir']) ? $_POST['Dir'] : $obj->getRootDir();
        $this->_checkDir($dir, false);
        $result = $obj->getDir($dir);
        if(false !== $result){
            $this->ajaxReturn($result, '', 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "获取目录失败！{$msg}", 0);
        }
    }

    private function _autoCreateUploadDir(){
        $source = intval($_POST['DataSource']);
        if(1 !== $source) return;
        $type = (int)$GLOBALS['Config']['UPLOAD_DIR_TYPE'];
        if(1 !== $type) return;
        $dir = './Upload/'.date("Ym").'/';
        if(!is_dir($dir)) {
            @mk_dir($dir);
        }
    }

    /**
     * 获取默认目录的所有子目录，用于显示当前项
     * 仅在打开对话框的时候调用一次
     */
    function getDefaultDir(){
        $this->_checkDataSource();
        $obj = $this->getInstance();
        $data = $obj->getDefaultDir();
        $this->ajaxReturn($data, '', 1);
    }

    /**
     * 创建目录
     */
    function createDir(){
        $obj = $this->getInstance();
        $dir = $_POST['Dir']; //要创建的目录全名称
        $dirName = $_POST['DirName']; //要创建目录名称，2个参数必传
        $this->_checkDir($dir, false);
        $this->_checkDirName($dirName);
        $result = $obj->createDir($dir);
        if(false !== $result){
            $data['RootDir'] = $obj->getRootDir();
            $this->ajaxReturn($data, '创建目录成功！', 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "创建目录失败！{$msg}", 0);
        }
    }

    /**
     * 删除目录（一次只能删除一个目录）
     */
    function deleteDir(){
        $obj = $this->getInstance();
        $dir = stripslashes($_POST['Dir']); //必须stripslashes，否则无法删除带有单引号的目录
        $this->_checkDir($dir);
        $result = $obj->deleteDir($dir);
        if(false !== $result){
            $this->ajaxReturn($result, '删除目录成功！', 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "删除目录失败！{$msg}", 0);
        }
    }

    /**
     * 统计目录(大小或其他属性）
     */
    function statDir(){
        $obj = $this->getInstance();
        $dir = !empty($_POST['Dir']) ? $_POST['Dir'] : $obj->getRootDir(); //为全路径
        $this->_checkDir($dir);
        $result = $obj->statDir($dir);
        if(false !== $result){
            $this->ajaxReturn($result, '', 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "获取目录大小失败！{$msg}", 0);
        }
    }

    /**
     * 重命名目录
     */
    function changeDirName(){
        $obj = $this->getInstance();
        $newOld = $_POST['OldDir']; //旧目录
        $this->_checkDir($newOld);

        $newDir = $_POST['NewDir']; //新目录
        $this->_checkDir($newDir, false);
        $newDirName = $_POST['NewDirName']; //新目录名
        $this->_checkDirName($newDirName);

        $result = $obj->changeDirName($newOld, $newDir);
        if(false !== $result){
            $this->ajaxReturn(null, '重命名成功！', 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "重命名失败！{$msg}", 0);
        }
    }
    //====================目录操作函数  结束====================


    //====================文件操作函数  开始====================
    /**
     * 获取文件列表
     */
    function getFile(){
        $obj = $this->getInstance();
        $dir = $_POST['Dir'] ? $_POST['Dir'] : $obj->getRootDir();
        $this->_checkDir($dir, false);
        $sortField = $_POST['SortField'] ? intval($_POST['SortField']) : 1;
        $sortOrder = $_POST['SortOrder'] ? intval($_POST['SortOrder']) : 3;
        $hasChildren = 0;
        $result = $obj->getFile($dir, $sortField, $sortOrder, $hasChildren);
        if(false !== $result){
            if(YdResource::needIconv()){
                foreach($result as $k => $v){
                    if(!preg_match("/^[\.a-zA-Z0-9_-]+$/i", $v['FileName'])){
                        $result[$k]['FileUrl'] = iconv('gbk', 'utf-8', $v['FileUrl']);
                        $result[$k]['FullFileName'] = iconv('gbk', 'utf-8', $v['FullFileName']);
                        $result[$k]['FileName'] = iconv('gbk', 'utf-8', $v['FileName']);
                    }
                }
            }
            //返回$hasChildren，用于优化前端界面
            $this->ajaxReturn($result, $hasChildren, 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "获取文件失败！{$msg}", 0);
        }
    }

    private function _convCharSet(&$p){
        //在php7.1以前的版本，因为此前将文件名从gbk转到utf-8，现在需要转回去。
        //保证在文件所有操作（删除、重命名、创建副本、移动、修改图片尺寸、图片裁剪、图片瘦身）都能正常
        import("@.Common.YdResource");
        if(YdResource::needIconv()){
            foreach($p as $k => $v){
                if(!preg_match("/^[\.a-zA-Z0-9_-]+$/i", $v)){
                    $p[$k] = iconv('utf-8', 'gbk', $v);
                }
            }
        }
    }

    private function _checkDataSource(){
        $source = intval($_POST['DataSource']);
        if(empty($source)){
            $this->ajaxReturn(null, "DataSource参数不能为空", 0);
        }
        if(1 == $source) return;
        $map = array(
            2 => array('id' => 149, 'name' => '七牛云存储插件', 'item' => 'QiniuEnable'),
            3 => array('id' => 173, 'name' => '阿里云存储插件', 'item' => 'AliEnable')
        );
        $data = $map[$source];
        $name = $data['name'];
        $isInstall = appIsInstall($data['id']);
        if(!$isInstall){
            $this->ajaxReturn(null, "{$name}未安装，请现在【应用】安装此插件！", 0);
        }
        $m = D('Admin/Config');
        $isEnable = $m->isEnable($data['item']);
        if(empty($isEnable)){
            $this->ajaxReturn(null, "未启用，请现在【应用】-【{$name}】启用此功能！", 0);
        }
    }

    /**
     * 删除文件（可以删除多个文件）
     */
    function deleteFile(){
        $this->_convCharSet($_POST);
        if(!isset($_POST['CurrentDir'])){
            $this->ajaxReturn(null, '当前目录不能为空！', 0);
        }
        $obj = $this->getInstance();
        $FileNameList = $_POST['FileNameList'];
        $this->_checkFileNameList($FileNameList);
        $result = $obj->deleteFile($FileNameList);
        if(false !== $result){
            $this->ajaxReturn(null, '删除文件成功！', 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "删除文件失败！{$msg}", 0);
        }
    }

    /**
     * 文件重命名
     */
    function changeFileName(){
        $this->_convCharSet($_POST);
        if(!isset($_POST['CurrentDir'])){
            $this->ajaxReturn(null, '当前目录不能为空！', 0);
        }
        $obj = $this->getInstance();
        $newName = trim($_POST['NewFileName']); //新文件名
        $newName = trim($newName, '.'); //防止漏洞攻击
        $this->_checkNewFileName($newName);
        $result = $obj->changeFileName($_POST['OldFileName'], $newName);
        if(false !== $result){
            $this->ajaxReturn(null, '重命名文件成功！', 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "重命名文件失败！{$msg}", 0);
        }
    }

    /**
     * 检查新文件名有效性
     */
    private function _checkNewFileName($newName){
        //文件名有效性检查
        /*
        $str = '\ / : * ? " < > |';
        $unsafeChar = explode(' ', $str);
        foreach($unsafeChar as $c){
            if(false !== stripos($newName, $c)){
                $this->ajaxReturn(null, "文件名不能包含符：{$str}" , 0);
            }
        }
        */
        $source = intval($_POST['DataSource']);
        if(1 == $source){
            //本地创建的目录不能是中文，会导致乱码、无法上传文件
            //云端存储不限制
            if(!preg_match("/^[\.a-zA-Z0-9_-]+$/i", $newName)){
                $this->ajaxReturn(null, "文件名称只能是字母、数字、下划线、中划线！", 0);
            }
            if(false !== stripos($newName, '..')){
                $this->ajaxReturn(null, "文件名不能包含..", 0);
            }
        }

        //不能是危险的扩展名
        $ext = pathinfo($newName, PATHINFO_EXTENSION);
        if(!empty($ext)){
            $deniedExt = array(
                'asa', 'asp', 'aspx', 'cdx', 'ascx', 'vbs', 'ascx', 'jsp', 'ashx', 'js', 'reg', 'cgi',
                'html', 'htm', 'shtml', 'xml', 'xhtml', 'config', 'htaccess', 'ini',
                'cfm', 'cfc', 'pl', 'bat', 'exe', 'com', 'dll', 'htaccess', 'cer',
                'php5', 'php4', 'php3', 'php2', 'php', 'pht', 'phtm',
            );
            foreach($deniedExt as $v){
                if(false !== stripos($ext, $v)){
                    $this->ajaxReturn(null, "无效的扩展名{$ext}", 0);
                }
            }
        }
        if('web.config' == strtolower($newName)){
            $this->ajaxReturn(null, "新文件名不能是web.config", 0);
        }

        if(false !== stripos($newName, '.php')
            || false !== stripos($newName, '.asp')
            || false !== stripos($newName, '.ini')){
            $this->ajaxReturn(null, "文件名包含非法字符！", 0);
        }
    }

    /**
     * 复制文件在当前目录（支持批量）
     */
    function copyFile(){
        $this->_convCharSet($_POST);
        $obj = $this->getInstance();
        $FileNameList = trim($_POST['FileNameList'], ',');
        $this->_checkFileNameList($FileNameList);
        $result = $obj->copyFile($FileNameList);
        if(false !== $result){
            $this->ajaxReturn(null, '创建副本成功！', 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "创建副本失败！{$msg}", 0);
        }
    }

    /**
     * 移动文件（可以移动多个文件）
     */
    function moveFile(){
        $this->_convCharSet($_POST);
        $obj = $this->getInstance();
        $FileNameList = trim($_POST['FileNameList'], ',');
        $IsOverWrite = intval($_POST['IsOverWrite']);
        $currentDir = trim($_POST['CurrentDir']);
        $dstDir = trim($_POST['DstDir']);
        if($currentDir == $dstDir){
            $this->ajaxReturn(null, "【目标目录】不能和【当前目录】相同！", 0);
        }

        if('' === $dstDir){
            $dstDir = $obj->getRootDir();
        }
        $this->_checkDir($dstDir);  //移动到根目录是为空，所以不判断
        $this->_checkFileNameList($FileNameList);
        $result = $obj->moveFile($FileNameList, $dstDir, $IsOverWrite);
        if(false !== $result){
            $this->ajaxReturn(null, '移动文件成功！', 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "移动文件失败！{$msg}", 0);
        }
    }

    /**
     * 检查目录有效性
     * $dir：目录全路径
     */
    private function _checkDir(&$dir, $checkExist = true){
        $source = intval($_POST['DataSource']);
        if(1 !== $source) return;
        if(empty($dir)){
            $this->ajaxReturn(null, "目录不能为空！", 0);
        }

        if(0 !== stripos($dir, './Upload/')){
            $dir = "./Upload/{$dir}";  //在跟目录下创建xx，传的不是./Upload/xx，而是xx，其他的地方都是全路径
            //$this->ajaxReturn(null, "无效的目录！" , 0);
        }

        //目录不能包含，如：./Upload/..Data/1.bak
        if(false !== stripos($dir, '..')){
            $this->ajaxReturn(null, "无效的目录！", 0);
        }

        if($checkExist && !empty($dir)){
            if(!is_dir($dir)){
                $this->ajaxReturn(null, "目录{$dir}不存在！", 0);
            }
        }
    }

    /**
     * 检查目录名称
     */
    private function _checkDirName($dirName, $checkEmpty = true, $checkExist = false){
        //非法字符检测
        $str = "\ / : * ? \" ' < > |";
        $unsafeChar = explode(' ', $str);
        foreach($unsafeChar as $c){
            if(false !== stripos($dirName, $c)){
                $this->ajaxReturn(null, "目录名称不能包含符：{$str}", 0);
            }
        }

        $source = intval($_POST['DataSource']);
        if(1 !== $source) return;
        //本地创建的目录不能是中文，会导致乱码、无法上传文件
        //云端存储不限制
        if(!preg_match("/^[\.a-zA-Z0-9_-]+$/i", $dirName)){
            $this->ajaxReturn(null, "目录名称只能是字母、数字、下划线、中划线！", 0);
        }

        //检查是否为空
        if($checkEmpty){
            if(empty($dirName)){
                $this->ajaxReturn(null, "目录不能为空！", 0);
            }
        }

        //检查目录是否存在
        if($checkExist && !empty($dirName)){
            $obj = $this->getInstance();
            $dir = $obj->getFullDirName($dirName);
            if(!is_dir($dir)){
                $this->ajaxReturn(null, "目录{$dirName}已经不可用！", 0);
            }
        }
    }

    /**
     * 检查文件名列表有效性
     * $checkImage：检查是否是一个图片
     */
    private function _checkFileNameList($FileNameList, $checkImage = false){
        if(empty($FileNameList)){
            $this->ajaxReturn(null, "请选择文件！", 0);
        }
        $files = explode(',', $FileNameList);
        $obj = $this->getInstance();
        foreach($files as $name){
            if(!preg_match("/^[\.a-zA-Z0-9_-]+$/i", $name)){
                $this->ajaxReturn(null, "文件名称只能是字母、数字、下划线、中划线！{$name}", 0);
            }
            if(false !== stripos($name, '..')){
                $this->ajaxReturn(null, "无效文件名，{$name}", 0);
            }
            if($checkImage){ //检查图片
                $isImage = $obj->isImageFile($name);
                if(!$isImage){
                    $this->ajaxReturn(null, "不能处理非图片文件，{$name}", 0);
                }
            }
        }
    }
    //====================文件操作函数  结束====================

    //====================图片处理函数  开始====================
    /**
     * 修改图片大小
     */
    function setImageSize(){
        $this->_convCharSet($_POST);
        $source = intval($_POST['DataSource']);
        if(1 !== $source){
            $this->ajaxReturn(null, "不支持修改大小！", 0);
        }

        $FileNameList = trim($_POST['FileNameList'], ',');
        $IsOverWrite = intval($_POST['IsOverWrite']);
        $width = intval($_POST['Width']);
        $height = intval($_POST['Height']);
        if(empty($width) && empty($height)){
            $this->ajaxReturn(null, "图片宽度和高度不能为空！", 0);
        }
        $this->_checkFileNameList($FileNameList, true);
        $obj = $this->getInstance();
        $result = $obj->setImageSize($FileNameList, $width, $height, $IsOverWrite);
        if(false !== $result){
            $this->ajaxReturn(null, '调整图片尺寸成功！', 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "调整图片尺寸失败！{$msg}", 0);
        }
    }

    /**
     * 图片瘦身
     */
    public function slimImage(){
        $this->_convCharSet($_POST);
        $source = intval($_POST['DataSource']);
        if(1 !== $source){
            $this->ajaxReturn(null, "不支持此操作", 0);
        }
        /*
         都为0表示不修改尺寸（操作多个图片，前台页面宽高默认为0）
         $width= intval($_POST['Width']);
        $height = intval($_POST['Height']);
        if(empty($width) && empty($height)){
            $this->ajaxReturn(null, "图片宽度和高度不能为空！" , 0);
        }
        */
        $IsPreview = intval($_POST['IsPreview']);
        $FileNameList = trim($_POST['FileNameList'], ',');
        $this->_checkFileNameList($FileNameList, true);
        $obj = $this->getInstance();
        $result = $obj->slimImage($FileNameList, $_POST);
        if(false !== $result){
            if($IsPreview){
                $newSize = $result['NewSize'];
                $oldSize = $result['OldSize'];
                $result['DeltaReadable'] = byte_format(abs($newSize - $oldSize));
                $result['NewSizeReadable'] = byte_format($newSize);
                $result['OldSizeReadable'] = byte_format($oldSize);
                $msg = '';
            }else{
                $msg = '图片瘦身成功！';
            }
            $this->ajaxReturn($result, $msg, 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "图片瘦身失败！{$msg}", 0);
        }
    }

    /**
     * 添加水印
     */
    public function addWater(){
        $this->_convCharSet($_POST);
        $source = intval($_POST['DataSource']);
        if(1 !== $source){
            $this->ajaxReturn(null, "不支持此操作", 0);
        }
        $FileNameList = trim($_POST['FileNameList'], ',');
        $this->_checkFileNameList($FileNameList, true);
        $waterConfig = $this->getWaterConfig(2);
        $params = array_merge($waterConfig, $_POST);
        $obj = $this->getInstance();
        $result = $obj->addWater($FileNameList, $params);
        if(false !== $result){
            $this->ajaxReturn($result, "添加水印成功！", 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "添加水印失败！{$msg}", 0);
        }
    }

    /**
     * 获取水印配置参数
     */
    public function getWaterConfig($type = 1){
        $m = D('Admin/Config');
        $data = $m->getConfig('water'); //配置数据不从缓存中提取
        $params['WaterEnable'] = $data['WATER_ENABLE'];
        $params['WaterType'] = $data['WATER_TYPE'];  //水印类型
        //图片水印
        $params['WaterPic'] = $data['WATER_PIC'];
        //文字水印参数
        $params['WaterText'] = $data['WATER_TEXT'];
        $params['WaterTextSize'] = $data['WATER_TEXT_SIZE'];
        $params['WaterTextColor'] = $data['WATER_TEXT_COLOR'];
        $params['WaterPosition'] = $data['WATER_POSITION'];
        if($type == 1){ //用于添加水印的界面显示
            $params['WaterTypeText'] = ($params['WaterType'] == 1) ? '图片水印' : '文字水印';
            $map = array(
                1 => '左上角', 2 => '上居中', 3 => '右上角',
                4 => '左居中', 5 => '正中间', 6 => '右居中',
                7 => '左下角', 8 => '下居中', 9 => '右下角',
            );
            $params['WaterPosition'] = $map[$params['WaterPosition']];
            $this->ajaxReturn($params, '', 1);
        }else{ //用于加水印
            //以下这些参数不需要在界面上显示
            $params['WaterOffsetX'] = $data['WATER_OFFSET_X'];
            $params['WaterOffsetY'] = $data['WATER_OFFSET_Y'];
            $params['WaterTrans'] = $data['WATER_TRANS'];
            $params['WaterFont'] = $data['WATER_FONT'];
            $params['WaterTextAngle'] = $data['WATER_TEXT_ANGLE'];
            return $params;
        }
    }

    /**
     * 图片裁剪
     */
    function cropImage(){
        $this->_convCharSet($_POST);
        $source = intval($_POST['DataSource']);
        if(1 !== $source){
            $this->ajaxReturn(null, "不支持裁剪图片！", 0);
        }
        $FileName = trim($_POST['FileName']);
        $this->_checkFileNameList($FileName, true);

        $x = intval($_POST['X']);
        $y = intval($_POST['Y']);
        $width = intval($_POST['Width']);
        $height = intval($_POST['Height']);
        if(empty($width) || empty($height)){
            $this->ajaxReturn(null, "图片宽度和高度必须大于0！", 0);
        }
        $IsOverWrite = intval($_POST['IsOverWrite']);

        $obj = $this->getInstance();
        $result = $obj->cropImage($FileName, $x, $y, $width, $height, $IsOverWrite);
        if(false !== $result){
            $this->ajaxReturn(null, '裁剪图片成功！', 1);
        }else{
            $msg = $obj->getLastError();
            $this->ajaxReturn(null, "裁剪图片失败！{$msg}", 0);
        }
    }
    //====================图片处理函数  结束====================
}