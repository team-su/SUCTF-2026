<?php
/**
 * 上传资源管理
 */
if (!defined('APP_NAME')) exit();
abstract class YdResource{
	protected $rootDir = '';  //根目录
    protected $currentDir = ''; //当前目录
    protected $lastError = ''; //上一次错误信息
    protected $imageMap = array();
    protected $extMap = array();
    protected $dataSource = 1; //1本地、2七牛、3阿里
	public function __construct($dataSource){
	    $this->dataSource = $dataSource;
        if($dataSource==1) $this->rootDir = './Upload/';
	    $this->lastError = '';
	    $this->currentDir = '';
	    $this->imageMap = array('jpg'=>1, 'jpeg'=>1, 'png'=>1, 'gif'=>1, 'bmp'=>1);
	    //主要用于判断扩展名图片是否存在，以下扩展名图片一定是存在的
	    $this->extMap = array(
	        'jpeg'=>1, 'jpg'=>1, 'png'=>1, 'bmp'=>1, 'zip'=>1, 'rar'=>1, 'mp3'=>1, 'mp4'=>1,
            'html'=>1, 'htm'=>1, 'pdf'=>1, 'psd'=>1, 'ppt'=>1, 'pptx'=>1, 'doc'=>1, 'docx'=>1,
            'xls'=>1, 'xlsx'=>1,
        );
	}

	/**
	 * 创建实例
	 */
	public static function getInstance($type) {
	    $map = array(1=>'Local', 2=>'Qiniu', 3=>'Alioss');
	    if(!isset($map[$type])) return false;
		$className = "YdResource{$map[$type]}";
		if (class_exists($className)) {
			$obj = new $className($type);
			return $obj;
		} else {
			return false;
		}
	}

	public static function needIconv(){
	    //在php7.1以前的版本，$result会返回中文乱码，所以需要用iconv函数处理。以便ajaxReturn能正确返回给前端
        if(version_compare(PHP_VERSION, '7.1.0', '<=')){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 判断文件名是否是图片
     */
	public function isImageFile($fileName){
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $ext = strtolower($ext); //文件名小写
        if(isset($this->imageMap[$ext])){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 设置当前目录
     */
	public function setCurrentDir($dir){
	    if(empty($dir)){
	        $this->currentDir = $this->rootDir;
        }else{
            $this->currentDir = $dir;
        }
    }

    /**
     * 获取全目录名称
     */
    public function getFullDirName($dirName){
        if(!empty($dirName)){
            $dir = "{$this->currentDir}{$dirName}/";
        }else{
            $dir = $this->currentDir;
        }
        return $dir;
    }

    /**
     * 返回上一次错误
     */
	public function getLastError(){
	    return $this->lastError;
    }

    public function getRootDir(){
	    return $this->rootDir;
    }

    //1、目录操作
    //获取当前目录下的所有子目录，如果为空$dir就是根目录，参数为目录名称不含路径
    abstract protected function getDir($dir);
    abstract protected function createDir($dir);
    abstract protected function deleteDir($dir);
    abstract protected function changeDirName($oldDir, $newDir);
    abstract protected  function statDir($dir);

    //2、文件操作
	//获取当前目录的文件列表，参数为目录名称不含路径
	abstract protected function getFile($dir, $sortField=1, $sortOrder=3);
    //删除文件，参数仅是文件名，不含路径（一次可以删除多个文件）
    abstract protected function deleteFile($fileNameList);
    //重命名，参数仅是文件名，不含路径
    abstract protected function changeFileName($oldFileName, $newFileName);
    //在当前目录复制文件
    abstract protected function copyFile($fileNameList);
    //移动文件到指定目录，$dstDir：目标全路径（一次可以移动多个文件）
    abstract protected function moveFile($fileNameList, $dstDir, $isOverWrite=true);

    //3、图像处理
    //改变大小
    abstract protected function setImageSize($fileNameList, $width, $height, $isOverWrite=true);
    //图片瘦身（修改大小，转化格式）,参数：width、height、quality、isoverWrite，type
    abstract protected function slimImage($fileNameList, $params);


    //4：其他
    //获取文件上传参数
    abstract protected function getUploadParams();
    //获取默认目录数据
    abstract protected function getDefaultDir();
}

/**
 * 本地资源文件
 */
class YdResourceLocal extends YdResource {
	public function __construct($dataSource){
		parent::__construct($dataSource);
	}

    /**
     * 获取文件上传参数
     */
	public function getUploadParams(){
	    return true;
    }

    /**
     * 获取指定目录的所有子目录
     */
    public function getDir($dir){
        $data = array();
        $list = scandir($dir); // 得到该文件下的所有文件和文件夹
        foreach($list as $file){//遍历
            if($file=="."  || $file=="..") continue;
            $fullFileName=$dir.$file;//生成路径
            if(is_dir($fullFileName)){ //判断是不是文件夹
                $dirName = $fullFileName.'/';
                $hasChildren = $this->_hasSubdirectory($dirName);
                $data[] = array('FullDirName'=>$dirName, 'DirName'=>$file, 'HasChildren'=>$hasChildren);
            }
        }
        return $data;
    }

    /**
     * 获取默认目录所有子目录
     */
    public function getDefaultDir(){
        $data = array();
        //如：./Upload/banner/mobile/news/t1/
        $defaultDir = rtrim($this->currentDir, '/');  //默认目录就是当前目录
        //默认目录不存在，也返回空
        if(!is_dir($defaultDir)) return array();
        $list = explode('/', $defaultDir);
        $n = count($list);
        //当默认目录是1级目录时，不需要获取子级目录，直接返回即可
        if($n<=3) return array();
        $prefix = '';
        foreach($list as $k=>$v){
            $prefix .= "{$v}/";
            if($k<=1 || $k==$n-1) continue; //跳过根目录和1级目录
            $temp = $this->getDir($prefix);
            foreach($temp as $k1=>$v1){
                $temp[$k1]['Depth'] = $k+1;
                $temp[$k1]['Parent'] = $prefix;
            }
            $data = array_merge($data, $temp);
        }
        return $data;
    }

    /**
     * 创建一个目录
     */
    public function createDir($dir){
        if(is_dir($dir)){
            $name = basename( trim($dir, '/') );
            $this->lastError = "目录 {$name} 已经存在";
            return false;
        }
        $b = @mkdir($dir, 0755, true);
        return $b;
    }

    /**
     * 删除目录（会递归删除里面所有的文件和目录）
     */
    public function deleteDir($dir){
        if(empty($dir)) return false;
        @deldir($dir);
        return true;
    }

    /**
     * 目录改名
     */
    public function changeDirName($oldDir, $newDir){
        if(!is_dir($oldDir)){
            $this->lastError = '目录不存在';
            return false;
        }

        if(is_dir($newDir)){
            $this->lastError = '目录已经存在';
            return false;
        }
        $b = rename($oldDir, $newDir);
        return $b;
    }

    /**
     * 判断当前目录是否有子目录
     */
    private function _hasSubdirectory($dir){
        $b = false;
        $list = scandir($dir); // 得到该文件下的所有文件和文件夹
        foreach($list as $file){//遍历
            if($file=="."  || $file=="..") continue;
            $fullFileName=$dir.$file;
            if(is_dir($fullFileName)){
                $b = true;
                break;
            }
        }
        return $b;
    }


    /**
     * 统计目录信息
     */
    public function statDir($dir){
        yd_set_time_limit(3600);
        @ini_set('memory_limit', -1);
        $data = array();
        $n = 0; //文件个数
        $totalSize = getdirsize($dir, $n);
        $data['TotalSize'] = $totalSize;
        $data['TotalSizeReadable'] = byte_format($totalSize, 2);
        //创建时间
        $timeStamp = filectime($dir);
        $data['CreateTime'] = date("Y年m月d日 H:i:s", $timeStamp);
        //最后一次修改事件
        $timeStamp = filemtime($dir);
        $data['ModifyTime'] = date("Y年m月d日 H:i:s", $timeStamp);
        $data['FileCount'] = $n;
        return $data;
    }

    /**
     * 获取目录下所有文件
     * $sortField：1时间（默认），2名称，3大小
     * $sortOrder：3：降序，4：升序
     * $hasChildren：返回当前目录是否有子目录，对本地存储没用，对云端存储才有用
     * 这里为了保持返回数据统一，都加了这个参数
     */
	public function getFile($dir=false, $sortField=1, $sortOrder=3, &$hasChildren=0){
	    if(empty($dir)) $dir = $this->rootDir;
        $data = array();
        $list = scandir($dir); // 得到该文件下的所有文件和文件夹
        $map = $this->imageMap;
        $webSiteRoot = __ROOT__;
        $IconPath = "{$webSiteRoot}/Public/Images/FileICO/";
        //这些扩展名一定存在图标
        $extMap = $this->extMap;
        $sortkeys = array();
        foreach($list as $file){//遍历
            //web.config为系统文件，不显示
            if($file=="."  || $file==".." || $file=='web.config') continue;
            $fullFileName=$dir.$file;
            if(is_file($fullFileName)){
                $ext = pathinfo($fullFileName, PATHINFO_EXTENSION);
                $ext = strtolower($ext); //文件名小写
                $size = filesize($fullFileName);
                $timeStamp = filemtime($fullFileName);
                $time = date("Y年m月d日 H:i:s", $timeStamp);
                $fileUrl = substr($fullFileName, 1);
                if(!empty($webSiteRoot)){
                    $fileUrl = "{$webSiteRoot}{$fileUrl}";
                }
                if($sortField==1){ //按时间
                    $sortkeys[] = $timeStamp;
                }elseif($sortField==2){ //按名称
                    $sortkeys[] = $file;
                }else{ //按大小
                    $sortkeys[] = $size;
                }

                $extFile = "./Public/Images/FileICO/{$ext}.gif";
                if( isset($extMap[$ext]) || is_file($extFile)){
                    $FileIcon =  "{$IconPath}{$ext}.gif";
                }else{
                    $FileIcon = "{$IconPath}unknown.gif";
                }
                $temp = array(
                    'FileUrl'=>$fileUrl,
                    'FullFileName'=>$fullFileName,  //文件全路径
                    'FileName'=>$file, //不含路径
                    'FileExt'=>$ext,
                    'FileSize'=>$size,
                    'FriendFileSize'=>byte_format($size, 1),
                    'FileTime'=>$time,
                    'FileIcon'=>$FileIcon,
                );
                if(isset($map[$ext])){
                    $size = getimagesize($fullFileName);
                    $temp['IsImage'] = 1;
                    $temp['Width'] = $size[0];
                    $temp['Height'] = $size[1];
                }else{
                    $temp['IsImage'] = 0;
                }
                $data[] = $temp;
            }elseif(is_dir($fullFileName)){
                $hasChildren = $fullFileName;
            }
        }
        //对插件进行排序
        $sortFlag = ($sortField==2) ? SORT_REGULAR : SORT_NUMERIC;
        array_multisort($sortkeys, $sortOrder, $sortFlag, $data);
        return $data;
	}

    /**
     * 删除文件，$fileName 文件名称（不含目录）
     */
	public function deleteFile($fileNameList){
	    $FileList = explode(',', $fileNameList);
	    foreach($FileList as $name){
	        $fileToDel = $this->currentDir.$name;
            if(is_file($fileToDel)){
                unlink($fileToDel);
            }
        }
	    return true;
    }

    /**
     * 文件重命名
     */
    public function changeFileName($oldFileName, $newFileName){
        $fullOldFileName = $this->currentDir.$oldFileName;
        if(!is_file($fullOldFileName)){
            $this->lastError = '文件不存在';
            return false;
        }
        $fullNewFileName = $this->currentDir.$newFileName;
        if(file_exists($fullNewFileName)){
            $this->lastError = "{$newFileName} 已经存在！";
            return false;
        }
        $b = rename($fullOldFileName, $fullNewFileName);
        return $b;
    }

    /**
     * 拷贝文件在当前目录
     */
    public function copyFile($fileNameList){
        $FileNameList = explode(',', trim($fileNameList, ','));
        //用于重命名
        $map = array();
        foreach($FileNameList as $v){
            $map[$v] = true;
        }
        $b = false;
        foreach($FileNameList as $fileName){
            $srcFileName = "{$this->currentDir}{$fileName}";
            if(!is_file($srcFileName)){
                $this->lastError = '文件不存在';
                return false;
            }
            $dstFileName = $this->_getNextFile($fileName, $this->currentDir);
            $b = copy($srcFileName, $dstFileName);
        }
        return $b;
    }

    /**
     * 移动文件
     * $dstDir：目标目录全路径
     */
    public function moveFile($fileNameList, $dstDir, $isOverWrite=true){
        $srcFiles = explode(',', $fileNameList);
        $map = array();
        foreach($srcFiles as $v){
            $map[$v] = true;
        }
        foreach($srcFiles as $fileName){
            $srcFileName = "{$this->currentDir}{$fileName}";
            if(!is_file($srcFileName)) continue;
            $dstFileName = "{$dstDir}{$fileName}";
            if(!$isOverWrite && file_exists($dstFileName)){ //如果存在，就重命名
                $dstFileName = $this->_getNextFile($fileName, $dstDir, $map);
            }
            $b = rename($srcFileName, $dstFileName);
        }
        return true;
    }

    /**
     * 通过加后缀的形式，获取新文件名称
     */
    private function _getNextFile($fileName, $dstDir, &$map=array()){
        $i=1;
        $newFullFileName = '';
        while(true){
            $info = pathinfo($fileName);
            $newFileName = "{$info['filename']}_{$i}.{$info['extension']}";
            if(!isset($map[$newFileName])){
                $newFullFileName = "{$dstDir}{$newFileName}";
                if(!file_exists($newFullFileName)){
                    $map[$newFileName] = true;
                    break;
                }
            }
            $i++;
        }
        return $newFullFileName;
    }

    /**
     * 设置图片大小
     */
    public function setImageSize($fileNameList, $width, $height, $isOverWrite=true){
        yd_set_time_limit(3600);
        @ini_set('memory_limit', -1);
        import('ORG.Util.Image.ThinkImage');
        $fileNameList = explode(',', $fileNameList);
        foreach($fileNameList as $fileName){
            $srcFileName = "{$this->currentDir}{$fileName}";
            if(!is_file($srcFileName)) continue;
            $dstWidth = $width;
            $dstHeight = $height;

            $img = new ThinkImage(THINKIMAGE_GD, $srcFileName);
            $srcWidth = $img->width(); //原始图像宽度
            $srcHeight = $img->height();
            if($width==0 && $height==0){
                $dstWidth = $srcWidth;
                $dstHeight = $srcHeight;
            }else{
                if($width==0){ //设置为0，就自动计算自适应
                    $dstWidth = $height * $srcWidth/$srcHeight;
                }
                if($height==0){ //设置为0，就自动计算自适应
                    $dstHeight = $width*$srcHeight/$srcWidth;
                }
            }
            $dstFileName = $isOverWrite ? $srcFileName : $this->_getNextFile($fileName, $this->currentDir);
            //2：表示自动留白
            $img->thumb($dstWidth, $dstHeight, 2);
            $b = $img->save($dstFileName);
        }
        return true;
    }

    /**
     * 图像瘦身
     * $fileNameList：文件名列表，不含路径
     * 支持预览
     */
    public function slimImage($fileNameList, $params){
        yd_set_time_limit(3600);
        @ini_set('memory_limit', -1);
        $result = true;
        //参数
        $FileNameList = explode(',', trim($fileNameList, ','));
        $IsOverWrite = intval($params['IsOverWrite']);
        $IsPreview = intval($params['IsPreview']);
        if($IsPreview == 1){ //如果是预览，仅处理第一个
            $FileNameList = array_slice($FileNameList, 0, 1);
        }
        $width= intval($params['Width']);
        $height = intval($params['Height']);
        $toExt =  trim($params['Format']); //图片格式
        $quality =  intval($params['Quality']); //品质因素，仅对jpg有效

        //用于重命名
        $map = array();
        foreach($FileNameList as $v){
            $map[$v] = true;
        }
        $dstDir = $this->currentDir;
        import('ORG.Util.Image.ThinkImage');
        foreach($FileNameList as $fileName){
            $srcFileName = "{$dstDir}{$fileName}";
            if($IsPreview){
                $destFileName = RUNTIME_PATH.$fileName;
            }else{
                $destFileName = $srcFileName;
                if(!$IsOverWrite){ //如果不是覆盖，就获取新的文件名
                    $destFileName = $this->_getNextFile($fileName, $this->currentDir, $map);
                }
            }
            //保存原始大小
            $dstWidth = $width;
            $dstHeight = $height;

            $img = new ThinkImage(THINKIMAGE_GD, $srcFileName);
            $srcWidth = $img->width(); //原始图像宽度
            $srcHeight = $img->height();
            if($width==0 && $height==0){
                $dstWidth = $srcWidth;
                $dstHeight = $srcHeight;
            }else{
                if($width==0){ //设置为0，就自动计算自适应
                    $dstWidth = $height*$srcWidth/$srcHeight;
                }
                if($height == 0){ //设置为0，就自动计算自适应
                    $dstHeight = $width*$srcHeight/$srcWidth;
                }
            }

            //1、调整尺寸
            if($srcWidth == $width && $srcHeight==$height){ //如果尺寸一样就不裁剪
                if(!$IsOverWrite){ //如果是覆盖，就不需要保存（不是覆盖就一定有新名称）
                    //$result = $img->save($destFileName);
                    copy($srcFileName, $destFileName); //使用拷贝更快，并且防止转换改变质量
                }
            }else{
                $ThumbType = 6; //固定尺寸缩放，2：自动留白
                $result = $img->thumb($dstWidth, $dstHeight, $ThumbType)->save($destFileName);
            }

            //2、格式转换
            $fromExt = strtolower(yd_file_ext($destFileName));
            $fromExt1 = $fromExt;
            if($fromExt=="jpeg" || $fromExt=="jpg") $fromExt1 = "jpg";
            $isNotSameExt = ($toExt!==$fromExt1) ? true : false;
            //如果扩展名相同就无需转换，jpeg除外（因为可以设置单独的品质因数）
            if($toExt=="jpg" || $isNotSameExt){
                $img = new ThinkImage(THINKIMAGE_GD, $destFileName);
                $other['JpegQuality'] = $quality;
                if($isNotSameExt){ //扩展名不同
                    $tempDestFileName = $destFileName;
                    $info = pathinfo($destFileName);
                    $destFileName = "{$info['dirname']}/{$info['filename']}.{$toExt}";
                    //判断是否存在===========================================
                    if(!$IsPreview){
                        $tempFileName = basename($destFileName);
                        $destFileName = $this->_getNextFile($tempFileName, $this->currentDir, $map);
                    }
                    //==================================================
                    $result = $img->saveAs($destFileName, $other);
                    unlink($tempDestFileName); //删除中间转换的文件
                }else{ //扩展名相同，就无需重复判断文件名
                    $result = $img->saveAs($destFileName, $other);
                }
            }

            //如果是预览就返回大小
            if($IsPreview == 1 && false!==$result){
                $oldSize = filesize($srcFileName);
                $newSize = filesize($destFileName);
                $url = __ROOT__.substr($destFileName, 1).'?'.time(); //防止缓存
                $result = array(
                    'NewSize'=>$newSize,'NewFile'=>$destFileName, 'NewFileUrl'=>$url,
                    'NewWidth'=>$width, 'NewHeight'=>$height,
                    'OldSize'=>$oldSize,
                );
            }
        }
        return $result;
    }

    /**
     * 添加水印
     * $fileNameList：文件名列表，不含路径
     * 支持预览
     */
    public function addWater($fileNameList, $params){
        yd_set_time_limit(3600);
        @ini_set('memory_limit', -1);
        $result = true;
        //参数
        $FileNameList = explode(',', trim($fileNameList, ','));
        $IsOverWrite = intval($params['IsOverWrite']);
        $IsPreview = intval($params['IsPreview']);
        if($IsPreview == 1){ //如果是预览，仅处理第一个
            $FileNameList = array_slice($FileNameList, 0, 1);
        }
        //用于重命名
        $dstDir = $this->currentDir;
        $map = array();
        foreach($FileNameList as $v){
            $map[$v] = true;
        }
        //水印参数=====================================
        $c = &$params; //水印设置
        $WaterType = intval($c['WaterType']);
        $offset = array($c['WaterOffsetX'], $c['WaterOffsetY']);
        $font = "";
        if(!empty($c['WaterFont'])){
            $font = './Public/font/' . $c['WaterFont'];
            if(!file_exists($font)) $font = '';
        }
        $pic = $_SERVER['DOCUMENT_ROOT'] . $c['WaterPic'];
        //==========================================
        import('ORG.Util.Image.ThinkImage');
        foreach($FileNameList as $fileName){
            $srcFileName = "{$dstDir}{$fileName}";
            if($IsPreview){
                $destFileName = RUNTIME_PATH.$fileName;
            }else{
                $destFileName = $srcFileName;
                if(!$IsOverWrite){ //如果不是覆盖，就获取新的文件名
                    $destFileName = $this->_getNextFile($fileName, $dstDir, $map);
                }
            }
            $img = new ThinkImage(THINKIMAGE_GD, $srcFileName);
            if($WaterType == 2){ //文字水印
                $img->text($c['WaterText'], $font, $c['WaterTextSize'], $c['WaterTextColor'], $c['WaterPosition'], $offset, $c['WaterTextAngle']);
                $result = $img->save($destFileName);
            }else if($WaterType== 1){ //图片水印
                $img->water($pic, $c['WaterPosition']);
                $result = $img->save($destFileName);
            }
        }
        if($IsPreview == 1 && false!==$result){
            $result = array();
            $result['FileUrl'] = __ROOT__.substr($destFileName, 1).'?'.time(); //防止缓存
        }
        return $result;
    }

    /**
     * 设置图片大小
     */
    public function cropImage($fileName, $x, $y, $width, $height, $isOverWrite=true){
        yd_set_time_limit(3600);
        @ini_set('memory_limit', -1);
        $srcFileName = $this->currentDir.$fileName;
        if(!is_file($srcFileName)) {
            $this->lastError = '文件不存在！';
            return false;
        }
        import('ORG.Util.Image.ThinkImage');
        $img = new ThinkImage(THINKIMAGE_GD, $srcFileName);
        $dstFileName = $isOverWrite ? $srcFileName : $this->_getNextFile($srcFileName, $this->currentDir);
        $img->crop($width, $height, $x, $y);
        $b = $img->save($dstFileName);
        return true;
    }
}

/**
 * 七牛云存储管理
 */
class YdResourceQiniu extends YdResource{
    private $obj = null;  //七牛云对象
    public function __construct($dataSource=2){
        parent::__construct($dataSource);
        import("@.Common.YdQiniu");
        $config['secretKey'] = $GLOBALS['Config']['QiniuSecretKey'];
        $config['accessKey'] = $GLOBALS['Config']['QiniuAccessKey'];
        $config['domain'] = $GLOBALS['Config']['QiniuUrl'];
        $config['bucket'] = $GLOBALS['Config']['QiniuBucketName'];
        $this->obj = new YdQiniu($config);
    }

    /**
     * 获取文件上传参数
     */
    public function getUploadParams(){
        $data = array();
        $data['MaxUploadSize'] = 5120; //最大上传大小，单位：MB
        $data['UploadToken'] = $this->obj->UploadToken();
        $data['Domain'] = $this->obj->getDomain();
        return $data;
    }

    /**
     * WEB直传时，每次都获取Token
     * 默认情况下，如果存在重复文件，就会提示文件已经存在
     */
    public function getUploadToken($params=array()){
        $token = $this->obj->UploadToken(false , false, $params);
        return $token;
    }

    /**
     * 获取默认目录所有子目录
     */
    public function getDefaultDir(){
        $data = array();
        //默认目录不存在，也返回空
        if(false===$this->obj->exist($this->currentDir)) {
            return array();
        }
        //如：t1/t2/t3
        $defaultDir = rtrim($this->currentDir, '/');  //默认目录就是当前目录
        $list = explode('/', $defaultDir);
        $n = count($list);
        //当默认目录是1级目录时，不需要获取子级目录，直接返回即可
        if($n<=1) return array();
        $prefix = '';
        foreach($list as $k=>$v){
            $prefix .= "{$v}/";
            if($k==$n-1) continue; //跳过根目录和1级目录
            $temp = $this->getDir($prefix);
            foreach($temp as $k1=>$v1){
                $temp[$k1]['Depth'] = $v1['DirDepth']+1;
                $temp[$k1]['Parent'] = $prefix;
            }
            $data = array_merge($data, $temp);
        }
        return $data;
    }

    /**
     * 目录操作
     * 获取当前目录下的所有子目录，如果为空$dir就是根目录，参数为目录名称不含路径
     */
    public function getDir($dir){
        $data = array();
        $list = $this->obj->getDir($dir);
        foreach($list as $v){ //遍历
            $temp = explode('/', trim($v, '/'));
            $name = end($temp);
            $depth = count($temp);
            $hasChildren = true;
            $data[] = array('FullDirName'=>$v, 'DirName'=>$name, 'HasChildren'=>$hasChildren, 'DirDepth'=>$depth);
        }
        return $data;

    }

    /**
     * 创建目录
     */
    public function createDir($dir){
        $dir = trim($dir, '/').'/'; //目录名称必须以/结尾
        $exist = $this->obj->exist($dir);
        if($exist){
            $name = basename( trim($dir, '/') );
            $this->lastError = "目录 {$name} 已经存在";
            return false;
        }
        $this->obj->autoSetUpHost();
        $b = $this->obj->createDir($dir);
        if(false===$b){
            $this->lastError = $this->obj->getLastError();
        }
        return $b;
    }

    /**
     * 删除目录
     */
    public function deleteDir($dir){
        $b = $this->obj->deleteDir($dir);
        if(false===$b){
            $this->lastError = $this->obj->getLastError();
        }
        return $b;
    }

    /**
     * 目录重命名
     */
    public function changeDirName($oldDir, $newDir){
        $b = $this->obj->renameDir($oldDir, $newDir);
        if(false===$b){
            $this->lastError = $this->obj->getLastError();
        }
        return $b;
    }

    /**
     * 统计目录信息
     */
    public function statDir($dir){
        $totalSize =0;
        $query = array('prefix'=>$dir);
        $result =  $this->obj->getAllList($query, false);
        foreach($result['items'] as $v){
            $totalSize += $v['fsize'];
        }
        $data['TotalSize'] = $totalSize;
        $data['TotalSizeReadable'] = byte_format($totalSize, 2);
        //创建时间（没有修改时间）
        $info = $this->obj->info($dir);
        $timeStamp =  intval($info['putTime']/10000000);
        $data['CreateTime'] = date("Y年m月d日 H:i:s", $timeStamp);
        $data['ModifyTime'] = '';
        $data['FileCount'] = is_array($result) ? count($result) : 0;
        return $data;
    }

    /**
     * 文件操作
     * 获取当前目录的文件列表，参数为目录名称不含路径
     * $hasChildren：输出参数，如果当前$dir存在子目录，就返回1
     */
    public function getFile($dir, $sortField=1, $sortOrder=3, &$hasChildren=0){
        if(empty($dir)) $dir = $this->rootDir;
        $data = array();
        $query = array('prefix'=>$dir);
        $list = $this->obj->getAllList($query); // 得到该文件下的所有文件和文件夹
        if(false===$list){
            $this->lastError = $this->obj->getLastError();
            return false;
        }
        //判断当前目录是否有子目录
        if(is_array($list['commonPrefixes']) && count($list['commonPrefixes'])>0){
            $hasChildren = 1;
        }else{
            $hasChildren = 0;
        }
        $map = $this->imageMap;
        $webSiteRoot = __ROOT__;
        $IconPath = "{$webSiteRoot}/Public/Images/FileICO/";
        //这些扩展名一定存在图标
        $extMap = $this->extMap;
        $sortkeys = array();
        $domain = $this->obj->getDomain();
        foreach($list['items'] as $v){//遍历
            $key = $v['key']; //文件全路径
            if('/' == substr($key, -1) ) continue; //如果是目录跳过
            $fileName = basename($key);
            $ext = pathinfo($key, PATHINFO_EXTENSION);
            $ext = strtolower($ext); //文件名小写
            $size = $v['fsize'];
            $timeStamp =  intval($v['putTime']/10000000);
            $time = date("Y年m月d日 H:i:s", $timeStamp);
            $fileUrl = "{$domain}/{$key}";

            if($sortField==1){ //按时间
                $sortkeys[] = $timeStamp;
            }elseif($sortField==2){ //按名称
                $sortkeys[] = $fileName;
            }else{ //按大小
                $sortkeys[] = $size;
            }

            $extFile = "./Public/Images/FileICO/{$ext}.gif";
            if( isset($extMap[$ext]) || is_file($extFile)){
                $FileIcon =  "{$IconPath}{$ext}.gif";
            }else{
                $FileIcon = "{$IconPath}unknown.gif";
            }
            $temp = array(
                'FileUrl'=>$fileUrl,
                'FullFileName'=>$fileUrl,  //文件全路径
                'FileName'=>$fileName, //不含路径
                'FileExt'=>$ext,
                'FileSize'=>$size,
                'FriendFileSize'=>byte_format($size, 1),
                'FileTime'=>$time,
                'FileIcon'=>$FileIcon,
            );
            if(isset($map[$ext])){
                $temp['IsImage'] = 1;
                $temp['Width'] = 0;
                $temp['Height'] = 0;
            }else{
                $temp['IsImage'] = 0;
            }
            $data[] = $temp;
        }
        //对插件进行排序
        $sortFlag = ($sortField==2) ? SORT_REGULAR : SORT_NUMERIC;
        array_multisort($sortkeys, $sortOrder, $sortFlag, $data);
        return $data;
    }

    /**
     * 删除文件，参数仅是文件名，不含路径（一次可以删除多个文件）
     */
    public function deleteFile($fileNameList){
        if(empty($fileNameList)){
            $this->_lastError = "文件不能为空！";
            return false;
        }
        //文件名转全路径
        $FileList = explode(',', $fileNameList);
        foreach($FileList as $k=>$v){
            $FileList[$k] = $this->currentDir.$v;
        }
        $b = $this->obj->delBatch($FileList);
        if(false===$b){
            $this->lastError = $this->obj->getLastError();
        }
        return $b;
    }

    /**
     * 重命名，参数仅是文件名，不含路径
     */
    public function changeFileName($oldFileName, $newFileName){
        $oldFile = $this->currentDir.$oldFileName;
        $newFile = $this->currentDir.$newFileName;
        //如果新文件名存在，则会自动提示名称已经存在
        $b = $this->obj->rename($oldFile, $newFile);
        if(false===$b){
            $this->lastError = $this->obj->getLastError();
        }
        return $b;
    }

    /**
     * 在当前目录复制文件（支持批量）
     */
    public function copyFile($fileNameList){
        $oldFileList = explode(',', $fileNameList);
        $newFileList = array();
        foreach($oldFileList as $k=>$v){
            $newFileList[] = $this->_getNextFile($v, $this->currentDir);
            $oldFileList[$k] = "{$this->currentDir}{$v}";
        }
        $b = $this->obj->copyBatch($oldFileList, $newFileList);
        if(false===$b){
            $this->lastError = $this->obj->getLastError();
        }
        return $b;
    }

    /**
     * 通过加后缀的形式，获取新文件名称
     * $map：仅对批量操作有效，当批量移动文件时，map为源文件散列表
     */
    private function _getNextFile($fileName, $dstDir, &$map=array()){
        $i=1;
        $newFullFileName = '';
        while(true){
            $info = pathinfo($fileName);
            $newFileName = "{$info['filename']}_{$i}.{$info['extension']}";
            if(!isset($map[$newFileName])){
                $newFullFileName = "{$dstDir}{$newFileName}";
                $isExist = $this->obj->exist($newFullFileName);
                if(!$isExist){
                    $map[$newFileName] = true;
                    break;
                }
            }
            $i++;
        }
        return $newFullFileName;
    }

    /**
     * 移动文件到指定目录，$dstDir：目标全路径（一次可以移动多个文件）
     */
    public function moveFile($fileNameList, $dstDir, $isOverWrite=true){
        $srcFiles = is_array($fileNameList) ? $fileNameList : explode(',', $fileNameList);
        $map = array();
        foreach($srcFiles as $v){
            $map[$v] = true;
        }
        $dstFiles = array();
        foreach($srcFiles as $k=>$srcFileName){
            $dstFileName = "{$dstDir}{$srcFileName}";
            if(!$isOverWrite && $this->obj->exist($dstFileName)){ //如果存在，就重命名
                $dstFiles[] = $this->_getNextFile($srcFileName, $dstDir, $map);
            }else{
                $dstFiles[] = $dstFileName;
            }
            $srcFiles[$k] = "{$this->currentDir}{$srcFileName}";
        }
        $b = $this->obj->moveBatch($srcFiles, $dstFiles, $isOverWrite);
        if(false===$b){
            $this->lastError = $this->obj->getLastError();
        }
        return true;
    }

    /**
     * 图像处理（七牛云不实现）
     */
    public function setImageSize($fileNameList, $width, $height, $isOverWrite=true){
        return true;
    }

    /**
     * 图像瘦身（七牛云不实现）
     */
    public function slimImage($fileNameList, $params){
        return true;
    }
}

/**
 *阿里OSS 存储管理
 */
class YdResourceAlioss extends YdResource{
    private $obj = null;  //阿里OSS对象
    public function __construct($dataSource=3){
        parent::__construct($dataSource);

        require_once('App/Lib/Common/AliOss/autoload.php');
        $AliAccessKeyID = $GLOBALS['Config']['AliAccessKeyID'];
        $AliAccessKeySecret = $GLOBALS['Config']['AliAccessKeySecret'];
        $AliBucketName = $GLOBALS['Config']['AliBucketName'];
        $AliEndpoint = $GLOBALS['Config']['AliEndpoint'];
        $this->obj = new \OSS\OssClient($AliAccessKeyID, $AliAccessKeySecret, $AliEndpoint);
    }

    /**
     * 获取文件上传参数
     */
    public function getUploadParams(){
        $data = array();
        $data['MaxUploadSize'] = 5120; //最大上传大小，单位：MB
        $data['UploadToken'] = "";
        $data['Domain'] = end(explode('//', $GLOBALS['Config']['AliEndpoint']));
        return $data;
    }

    /**
     * 目录操作
     * 获取当前目录下的所有子目录，如果为空$dir就是根目录，参数为目录名称不含路径
     */
    public function getDir($dir){
        $data = array();
        $options = array(
            'prefix' =>$dir,
        );
        $AliBucketName = $GLOBALS['Config']['AliBucketName'];
        $listObjectInfo = $this->obj->listObjects($AliBucketName, $options);
        $list = $listObjectInfo->getPrefixList(); // directory list

        foreach($list as $value){ //遍历
            $v = $value->getPrefix();
            $temp = explode('/', trim($v, '/'));
            $name = end($temp);
            $depth = count($temp);
            $hasChildren = true;
            $data[] = array('FullDirName'=>$v, 'DirName'=>$name, 'HasChildren'=>$hasChildren, 'DirDepth'=>$depth);
        }
        return $data;
    }

    /**
     * 获取默认目录所有子目录
     */
    public function getDefaultDir(){
        $data = array();
        //默认目录不存在，也返回空
        $AliBucketName = $GLOBALS['Config']['AliBucketName'];
    /*    if(false===$this->obj->doesObjectExist($AliBucketName,$this->currentDir)) {
            return array();
        }*/
        //如：t1/t2/t3
        $defaultDir = rtrim($this->currentDir, '/');  //默认目录就是当前目录
        $list = explode('/', $defaultDir);
        $n = count($list);
        //当默认目录是1级目录时，不需要获取子级目录，直接返回即可
        if($n<=1) return array();
        $prefix = '';
        foreach($list as $k=>$v){
            $prefix .= "{$v}/";
            if($k==$n-1) continue; //跳过根目录和1级目录
            $temp = $this->getDir($prefix);
            foreach($temp as $k1=>$v1){
                $temp[$k1]['Depth'] = $v1['DirDepth']+1;
                $temp[$k1]['Parent'] = $prefix;
            }
            $data = array_merge($data, $temp);
        }
        return $data;
    }

    /**
     * 创建目录
     */
    public function createDir($dir){
        $dir = trim($dir, '/').'/'; //目录名称必须以/结尾
        $AliBucketName = $GLOBALS['Config']['AliBucketName'];
        $exist = $this->obj->doesObjectExist($AliBucketName, $dir);
        if($exist){
            $name = basename( trim($dir, '/') );
            $this->lastError = "目录 {$name} 已经存在";
            return false;
        }
        try {
            $b = $this->obj->PutObject($AliBucketName,$dir , "");
        } catch (Exception $e) {
            $b = false;
            $this->lastError = "创建失败！{$e->getMessage()}";
        }
        return $b;
    }

    /**
     * 删除目录
     */
    public function deleteDir($dir){
        $delObjectList= $this->_getDelObjectList($dir,$this->obj);
        $AliBucketName = $GLOBALS['Config']['AliBucketName'];
        try {
            $b = $this->obj->deleteObjects($AliBucketName, $delObjectList);
        } catch (Exception $e) {
            $b = false;
            $this->lastError = "删除失败！{$e->getMessage()}";
        }
        return $b;
    }

    /**
     * 阿里OSS，用递归方式，获取当前目录下的，所有级别的子目录和文件
     * */
    private function _getDelObjectList($dirName, $ossClient) {
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
                $delObjectList = array_merge($delObjectList, $this->_getDelObjectList($vo->getPrefix(), $ossClient));
            }
        } else {  //若当前目录没有子目录了，则将当前目录收集到待删列表中
            $delObjectList[] = $dirName;
        }

        return $delObjectList;  //返回待删列表

    }

    /**
     * 目录重命名
     */
    public function changeDirName($oldDir, $newDir){
        $temp = explode('/', trim($oldDir, '/')  );
        $OldDirName = end($temp);

        $temp = explode('/', trim($newDir, '/')  );
        $NewDirName = end($temp);

        $Prefix = str_replace($OldDirName.'/',"",$oldDir);

        if (empty($NewDirName)) {
            $this->lastError ='目录名不能为空';
            return false;
        }
        if(false!==strpos($NewDirName, '/')){
            $this->lastError = '无效目录名，不能包含 / ';
            return false;
        }
        if ($OldDirName == $NewDirName) {
            $this->lastError = '新目录名和旧目录名不能相同';
            return false;
        }
        $b =   $this->getRenameDir($Prefix, $OldDirName, $NewDirName, $this->obj);
        return b;
    }

    /**
     * 统计目录大小（包含里面所有子目录）
     * $returnFriendSize：返回友好大小（带单位）
     */
    public function statDir($dir){
        $totalSize =0;
        $query = array('delimiter' => '','prefix'=>$dir);
        $AliBucketName = $GLOBALS['Config']['AliBucketName'];
        $listObjectInfo =  $this->obj->listObjects($AliBucketName,$query);
        $result = $listObjectInfo->getObjectList(); // file list

        $FileCount = 0;
        foreach($result as $v){
            $totalSize += $v->getSize();
            if($v->getKey()==$dir){
                $DirCreateTime=$v->getLastModified();
            }
            if( substr($v->getKey(),-1)!="/" ){
                $FileCount++;
            }
        }
        $data['TotalSize'] = $totalSize;
        $data['TotalSizeReadable'] = byte_format($totalSize, 2);
        //创建时间（没有修改时间）
        $data['CreateTime'] = date("Y年m月d日 H:i:s", strtotime($DirCreateTime));
        $data['ModifyTime'] = '';
        $data['FileCount'] = $FileCount;
        return $data;
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
    //2、文件操作
    //获取当前目录的文件列表，参数为目录名称不含路径
    public function getFile($dir, $sortField=1, $sortOrder=3, &$hasChildren=0){
        if(empty($dir)) $dir = $this->rootDir;
        $data = array();
        $query = array('prefix'=>$dir);
        $AliBucketName = $GLOBALS['Config']['AliBucketName'];
        $listObjectInfo =  $this->obj->listObjects($AliBucketName, $query);
        $list = $listObjectInfo->getObjectList(); // file list
        $listPrefix = $listObjectInfo->getPrefixList(); // directory list

        if(false===$list){
            $this->lastError = $this->obj->getLastError();
            return false;
        }
        //判断当前目录是否有子目录
        if(is_array($listPrefix) && count($listPrefix)>0){
            $hasChildren = 1;
        }else{
            $hasChildren = 0;
        }
        $map = $this->imageMap;
        $webSiteRoot = __ROOT__;
        $IconPath = "{$webSiteRoot}/Public/Images/FileICO/";
        //这些扩展名一定存在图标
        $extMap = $this->extMap;
        $sortkeys = array();

        $AliEndpoint=end(explode('//', $GLOBALS['Config']['AliEndpoint']));
        $domain = "http://{$GLOBALS['Config']['AliBucketName']}.{$AliEndpoint}";


        foreach($list as $v){//遍历
            $key = $v->getKey(); //文件全路径
            if('/' == substr($key, -1) ) continue; //如果是目录跳过
            $fileName = basename($key);
            $ext = pathinfo($key, PATHINFO_EXTENSION);
            $ext = strtolower($ext); //文件名小写
            $size = $v->getSize();
            $timeStamp =  intval(strtotime($v->getLastModified()));
            $time = date("Y年m月d日 H:i",  strtotime($v->getLastModified()));
            $fileUrl = "{$domain}/{$key}";

            if($sortField==1){ //按时间
                $sortkeys[] = $timeStamp;
            }elseif($sortField==2){ //按名称
                $sortkeys[] = $fileName;
            }else{ //按大小
                $sortkeys[] = $size;
            }

            $extFile = "./Public/Images/FileICO/{$ext}.gif";
            if( isset($extMap[$ext]) || is_file($extFile)){
                $FileIcon =  "{$IconPath}{$ext}.gif";
            }else{
                $FileIcon = "{$IconPath}unknown.gif";
            }
            $temp = array(
                'FileUrl'=>$fileUrl,
                'FullFileName'=>$fileUrl,  //文件全路径
                'FileName'=>$fileName, //不含路径
                'FileExt'=>$ext,
                'FileSize'=>$size,
                'FriendFileSize'=>byte_format($size, 1),
                'FileTime'=>$time,
                'FileIcon'=>$FileIcon,
            );
            if(isset($map[$ext])){
                $temp['IsImage'] = 1;
                $temp['Width'] = 0;
                $temp['Height'] = 0;
            }else{
                $temp['IsImage'] = 0;
            }
            $data[] = $temp;
        }
        //对插件进行排序
        $sortFlag = ($sortField==2) ? SORT_REGULAR : SORT_NUMERIC;
        array_multisort($sortkeys, $sortOrder, $sortFlag, $data);
        return $data;
    }

    //删除文件，参数仅是文件名，不含路径（一次可以删除多个文件）
    public function deleteFile($fileNameList){
        if(empty($fileNameList)){
            $this->lastError = "文件不能为空！";
            return false;
        }
        //文件名转全路径
        $FileList = explode(',', $fileNameList);
        foreach($FileList as $k=>$v){
            $FileList[$k] = $this->currentDir.$v;
        }
      //  $b = $this->obj->delBatch($FileList);
        try {
            $AliBucketName = $GLOBALS['Config']['AliBucketName'];
            $b = $this->obj->deleteObjects($AliBucketName, $FileList);
            return $b;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    //重命名，参数仅是文件名，不含路径
    public function changeFileName($oldFileName, $newFileName) {
        if (empty($newFileName)) {
            $this->lastError = '文件名不能为空';
            return false;
        }
        if (false !== strpos($newFileName, '/')) {
            $this->lastError = '无效文件名，不能包含 / ';
            return false;
        }
        if ($oldFileName == $newFileName) {
            $this->lastError = '新文件名和旧文件名不能相同';
            return false;
        }

        $oldFile = $this->currentDir . $oldFileName;
        $newFile = $this->currentDir . $newFileName;
        try {
            //如果新文件名存在，则会自动提示名称已经存在
            $AliBucketName = $GLOBALS['Config']['AliBucketName'];

            $exist = $this->obj->doesObjectExist($AliBucketName, $newFile);
            if($exist){
                $this->lastError = "文件 {$newFileName} 已经存在！";
                return false;
            }
            $b = $this->obj->copyObject($AliBucketName, $oldFile, $AliBucketName, $newFile);
            $b = $this->obj->deleteObject($AliBucketName, $oldFile);
            return $b;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    //在当前目录复制文件（支持批量）
    public function copyFile($fileNameList){
        $oldFileList = explode(',', $fileNameList);
        $newFileList = array();
        foreach($oldFileList as $k=>$v){
            $newFileList[] = $this->_getNextFile($v, $this->currentDir);
            $oldFileList[$k] = "{$this->currentDir}{$v}";
        }
        try{
            $b = false;
            $AliBucketName = $GLOBALS['Config']['AliBucketName'];
            foreach ($oldFileList as $k=>$v){
                $b = $this->obj->copyObject($AliBucketName, $oldFileList[$k], $AliBucketName, $newFileList[$k]);
            }
            return $b;
        }  catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    /**
     * 通过加后缀的形式，获取新文件名称
     * $map：仅对批量操作有效，当批量移动文件时，map为源文件散列表
     */
    private function _getNextFile($fileName, $dstDir, &$map=array()){
        $i=1;
        $newFullFileName = '';
        while(true){
            $info = pathinfo($fileName);
            $newFileName = "{$info['filename']}_{$i}.{$info['extension']}";
            if(!isset($map[$newFileName])){
                $newFullFileName = "{$dstDir}{$newFileName}";
                $AliBucketName = $GLOBALS['Config']['AliBucketName'];
                $isExist = $this->obj->doesObjectExist($AliBucketName,$newFullFileName);
                if(!$isExist){
                    $map[$newFileName] = true;
                    break;
                }
            }
            $i++;
        }
        return $newFullFileName;
    }


    //移动文件到指定目录，$dstDir：目标全路径（一次可以移动多个文件）
    public function moveFile($fileNameList, $dstDir, $isOverWrite = true) {
        $srcFiles = is_array($fileNameList) ? $fileNameList : explode(',', $fileNameList);
        $map = array();
        foreach ($srcFiles as $v) {
            $map[$v] = true;
        }
        $dstFiles = array();

        $AliBucketName = $GLOBALS['Config']['AliBucketName'];
        foreach ($srcFiles as $k => $srcFileName) {
            $dstFileName = "{$dstDir}{$srcFileName}";
            $exist = $this->obj->doesObjectExist($AliBucketName, $dstFileName);

            if (!$isOverWrite && $exist) { //如果存在，就重命名
                $dstFiles[] = $this->_getNextFile($srcFileName, $dstDir, $map);
            } else {
                $dstFiles[] = $dstFileName;
            }
            $srcFiles[$k] = "{$this->currentDir}{$srcFileName}";
        }
        try {

            foreach ($srcFiles as $k => $v) {
                $b = $this->obj->copyObject($AliBucketName, $srcFiles[$k], $AliBucketName, $dstFiles[$k]);
                $b = $this->obj->deleteObject($AliBucketName, $srcFiles[$k]);
            }
            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    //3、图像处理
    public function setImageSize($fileNameList, $width, $height, $isOverWrite=true){
        return true;
    }

    /**
     * 图像瘦身（阿里OSS不实现）
     */
    public function slimImage($fileNameList, $params){
        return true;
    }
}