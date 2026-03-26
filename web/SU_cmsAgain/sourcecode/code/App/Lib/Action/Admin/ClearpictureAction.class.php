<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class ClearpictureAction extends AdminBaseAction {
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$this->display();
	}

    function ScanImage() {
        $imageFileMap = $this->_getImageFile();
        $tplFile = $this->_getTplFile();

        $pattern ='#/Upload/.+?\.(jpg|jpeg|jpe|gif|bmp|png)#i';
        foreach ($tplFile as $file){
            $content = file_get_contents($file);
            $num = preg_match_all($pattern, $content, $matches);
            foreach ($matches[0] as $temp) {
                if ($imageFileMap[$temp]) {
                    unset($imageFileMap[$temp]);
                }
            }
        }

        $m = D('Admin/Info');
        if (false !== $imageFileMap) {
            $tables = $m->query("SHOW TABLE STATUS LIKE '" . C('DB_PREFIX') . "%'");
            $n = is_array($tables) ? count($tables) : 0;
            for ($i = 0; $i < $n; $i++) {
                $tableData = $m->query("select *  from {$tables[$i]['Name']}");
                foreach ($tableData as $row) {
                    $rowContent = '';
                    foreach ($row as $fieldValue) {
                        if(false!==stripos($fieldValue, "/Upload/")){
                            $rowContent .= $fieldValue;
                        }
                    }
                    $num = preg_match_all($pattern, $rowContent, $matches);
                    foreach ($matches[0] as $temp) {
                        if ($imageFileMap[$temp]) {
                            unset($imageFileMap[$temp]);
                        }
                    }
                }
            }

            //在php7.1以前的版本，$imageFileMap会返回中文乱码，所以需要用iconv函数处理。以便ajaxReturn能正确返回给前端
            if (version_compare(PHP_VERSION, '7.1.0', '<=')) {
                foreach ($imageFileMap as $k => $v) {
                    if (!preg_match("/^[\.a-zA-Z0-9_-]+$/i", $v['FileName'])) {
                        $imageFileMap[$k]['FileUrl'] = iconv('gbk', 'utf-8', $v['FileUrl']);
                        $imageFileMap[$k]['FullFileName'] = iconv('gbk', 'utf-8', $v['FullFileName']);
                        $imageFileMap[$k]['FileName'] = iconv('gbk', 'utf-8', $v['FileName']);
                    }
                }
            }
            $this->ajaxReturn($imageFileMap, "扫描完成！", 1);
        } else {
            $this->ajaxReturn(null, "扫描失败！", 0);
        }
    }

    private function _getImageFile($dir = false) {
        if (empty($dir)) $dir = './Upload/';
        $webSiteRoot = __ROOT__;
        $IconPath = "{$webSiteRoot}/Public/Images/FileICO/";
        $data = array();
        $imageMap = array('jpg' => 1, 'jpeg' => 1, 'png' => 1, 'gif' => 1, 'bmp' => 1);
        $list = scandir($dir); // 得到该文件下的所有文件和文件夹
        foreach ($list as $file) {//遍历
            //web.config为系统文件，不显示
            if ($file == "." || $file == ".." || $file == 'web.config') continue;
            $fullFileName = $dir . $file;
            if (is_file($fullFileName)) {
                $ext = yd_file_ext($fullFileName);
                $ext = strtolower($ext); //文件名小写
                if (isset($imageMap[$ext])) {

                    $extFile = "./Public/Images/FileICO/{$ext}.gif";
                    if( is_file($extFile)){
                        $FileIcon =  "{$IconPath}{$ext}.gif";
                    }else{
                        $FileIcon = "{$IconPath}unknown.gif";
                    }

                    $size = filesize($fullFileName);
                    $timeStamp = filemtime($fullFileName);
                    $time = date("Y年m月d日 H:i:s", $timeStamp);
                    $fileUrl = substr($fullFileName, 1);
                    $imgsize = getimagesize($fullFileName);
                    $temp = array(
                        'FileUrl' => $fileUrl,
                        'FullFileName' => $fullFileName,  //文件全路径
                        'FileName' => $file, //不含路径
                        'FileExt' => $ext,
                        'FileSize' => $size,
                        'FriendFileSize' => byte_format($size, 1),
                        'FileTime' => $time,
                        'FileTimeStamp'=>$timeStamp,
                        'Width' => $imgsize[0],
                        'Height' => $imgsize[1],
                        'FileIcon'=>$FileIcon,
                    );

                    $data[$fileUrl] = $temp;
                }

            } elseif (is_dir($fullFileName)) {
                $dataTemp = $this->_getImageFile($fullFileName . '/');
                $data = array_merge($data, $dataTemp);
            }
        }

        return $data;

    }

    private function _getTplFile($dir = false){
        if (empty($dir)) $dir = './App/Tpl/';
        $data = array();
        $extMap = array('php' => 1, 'js' => 1, 'css' => 1, 'html' => 1);
        $list = scandir($dir); // 得到该文件下的所有文件和文件夹
        foreach ($list as $file) {//遍历
            //web.config为系统文件，不显示
            if ($file == "." || $file == ".." || $file == 'web.config') continue;
            $fullFileName = $dir . $file;
            if (is_file($fullFileName)) {
                $ext = yd_file_ext($fullFileName);
                $ext = strtolower($ext); //文件名小写
                if (isset($extMap[$ext])) {
                    $data[] = $fullFileName;
                }
            }elseif (is_dir($fullFileName)) {
                $dataTemp = $this->_getTplFile($fullFileName . '/');
                $data = array_merge($data, $dataTemp);
            }
        }

        return $data;
    }

    function batchDelImage() {
        $FullFileName = $_POST['FullFileName'];
        if (count($FullFileName) <= 0) {
            $this->ajaxReturn(null, "请选择要删除的文件！", 0);
        }

        batchDelFile($FullFileName); //删除图片文件
        WriteLog("FullFileName:" . implode(',', $FullFileName));
        $this->ajaxReturn(null, "删除文件成功！", 1);
    }

}