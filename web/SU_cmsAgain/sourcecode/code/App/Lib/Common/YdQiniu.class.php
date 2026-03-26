<?php
if (!defined('APP_NAME')) exit();
	class YdQiniu {
		public $QINIU_API_HOST = 	'http://api.qiniu.com';
		public $QINIU_RSF_HOST = 	'http://rsf.qiniu.com';
		public $QINIU_RS_HOST 	= 	'http://rs.qiniu.com';
		public $QINIU_UP_HOST 	= 	'http://up.qiniu.com';
		public $QINIU_PU_HOST 	= 	'http://pu.qbox.me';
		public $timeout 		= 	'';
		private $_lastError = '';

		public function __construct($config){
			$this->sk 		= 	$config['secretKey'];
			$this->ak 		= 	$config['accessKey'];
			$this->domain 	= 	$config['domain'];
			$this->bucket 	= 	$config['bucket'];
			$this->timeout 	= 	isset($config['timeout'])? $config['timeout'] : 3600;
		}

		static function sign($sk, $ak, $data){
			$sign = hash_hmac('sha1', $data, $sk, true);
			return $ak . ':' . self::Qiniu_Encode($sign);
		}

		static function signWithData($sk, $ak, $data){
			$data = self::Qiniu_Encode($data);
			return self::sign($sk, $ak, $data) . ':' . $data;
		}

        /**
         * @return 获取当前配置项的域名
         */
		public function getDomain(){
		    $domain = trim($this->domain, '/');
		    return $domain;
        }

		public function accessToken($url, $body=''){
			$parsed_url = 	parse_url($url);
		    $path 		= 	$parsed_url['path'];
		    $access 	= 	$path;
		    if (isset($parsed_url['query'])) {
		        $access .= "?" . $parsed_url['query'];
		    }
		    $access    .= "\n";

		    if($body){
		        $access .= $body;
		    }
		    return self::sign($this->sk, $this->ak, $access);
		}

		public function UploadToken($sk=false,$ak=false,$param=array()){
		    if(false===$sk) $sk = $this->sk;
            if(false===$ak) $ak = $this->ak;
			$param['deadline'] = $param['Expires'] == 0? 3600: $param['Expires'];
			$param['deadline'] += time();
			//$this->bucket:key 可以实现覆盖上传
			$data = array('deadline'=>$param['deadline']);
            if (!empty($param['key'])) {  //用于实现覆盖上传
                $data['scope'] = "{$this->bucket}:{$param['key']}";
                //配合scope实现覆盖上传，还是提示错误
                $data['insertOnly'] = $param['insertOnly'];
            }else{
                $data['scope'] =  $this->bucket;
            }

			if (!empty($param['CallbackUrl'])) {
				$data['callbackUrl'] = $param['CallbackUrl'];
			}
			if (!empty($param['CallbackBody'])) {
				$data['callbackBody'] = $param['CallbackBody'];
			}
			if (!empty($param['ReturnUrl'])) {
				$data['returnUrl'] = $param['ReturnUrl'];
			}
			if (!empty($param['ReturnBody'])) {
				$data['returnBody'] = $param['ReturnBody'];
			}
			if (!empty($param['AsyncOps'])) {
				$data['asyncOps'] = $param['AsyncOps'];
			}
			if (!empty($param['EndUser'])) {
				$data['endUser'] = $param['EndUser'];
			}
			$data = json_encode($data);
			return self::SignWithData($sk, $ak, $data);
		}

		public function upload($config, $file){
			$uploadToken = $this->UploadToken($this->sk, $this->ak, $config);

			$url 	= 	"{$this->QINIU_UP_HOST}";
			$mimeBoundary = md5(microtime());
			$header = 	array('Content-Type'=>'multipart/form-data;boundary='.$mimeBoundary);
			$data 	= 	array();

			$fields = array(
				'token'	=>	$uploadToken,
				'key'	=>	$config['saveName']? $config['saveName']  : $file['fileName'],
			);

			if(is_array($config['custom_fields']) && $config['custom_fields'] !== array()){
				$fields = array_merge($fields, $config['custom_fields']);
			}

			foreach ($fields as $name => $val) {
				array_push($data, '--' . $mimeBoundary);
				array_push($data, "Content-Disposition: form-data; name=\"$name\"");
				array_push($data, '');
				array_push($data, $val);
			}

			//文件
			array_push($data, '--' . $mimeBoundary);
			$name 		= 	$file['name'];
			$fileName 	= 	$file['fileName'];
			$fileBody 	= 	$file['fileBody'];
			$fileName 	= 	self::Qiniu_escapeQuotes($fileName);
			array_push($data, "Content-Disposition: form-data; name=\"$name\"; filename=\"$fileName\"");
			array_push($data, 'Content-Type: application/octet-stream');
			array_push($data, '');
			array_push($data, $fileBody);

			array_push($data, '--' . $mimeBoundary . '--');
			array_push($data, '');

			$body 		= 	implode("\r\n", $data);
			$response 	= 	$this->request($url, 'POST', $header, $body);
			return $response;
		}

        /**
         * 创建目录（调用前，必须调用autoSetUpHost方法）
         */
        public function createDir($dir){
            $dir = trim($dir, '/').'/';
            $config = array('saveName'=>$dir);
            $file = array('name'=>'file', 'fileName'=>$dir, 'fileBody'=>'');
            $result = $this->upload($config, $file);
            return $result;
        }

        /**
         * 删除一个目录
         */
        public function deleteDir($dir){
            if(strlen($dir)==0){
                $this->_lastError = "不能删除空目录";
                return false;
            }
            $allDataToDel = array();
            $query['prefix'] = $dir;
            $result = $this->getAllList($query, false);
            foreach($result['items'] as $v){
                $allDataToDel[] = $v['key'];
            }
            $result= $this->delBatch($allDataToDel);
            return $result;
        }

		public function dealWithType($key, $type){
			$param 		= 	$this->buildUrlParam();
			$url 		= 	'';

			switch($type){
				case 'img':
					$url = $this->downLink($key);
					if($param['imageInfo']){
						$url .= '?imageInfo';
					}else if($param['exif']){
						$url .= '?exif';
					}else if($param['imageView']){
						$url .= '?imageView/'.$param['mode'];
						if($param['w'])
							$url .= "/w/{$param['w']}";
						if($param['h'])
							$url .= "/h/{$param['h']}";
						if($param['q'])
							$url .= "/q/{$param['q']}";
						if($param['format'])
							$url .= "/format/{$param['format']}";
					}
					break;
				case 'video': //TODO 视频处理
				case 'doc':
					$url = $this->downLink($key);
					$url .= '?md2html';
					if(isset($param['mode']))
						$url .= '/'.(int)$param['mode'];
					if($param['cssurl'])
						$url .= '/'. self::Qiniu_Encode($param['cssurl']);
					break;

			}
			return $url;
		}

		public function buildUrlParam(){
			return $_REQUEST;
		}

		//获取某个路径下的文件列表
		public function getList($query = array(), $path = ''){
			$query 			= 	array_merge(array('bucket'=>$this->bucket,), $query);
			$url 			= 	"{$this->QINIU_RSF_HOST}/list?".http_build_query($query);
			$accessToken 	= 	$this->accessToken($url);
			$response 		= 	$this->request($url, 'POST', array('Authorization'=>"QBox $accessToken"));
			return $response;
		}

        /**
         * 一次获取获取所有数据
         */
		public function getAllList($query, $delimiter='/'){
            $query['limit'] = 1000;
            if(!empty($delimiter)){
                $query['delimiter'] =$delimiter;  //指定后返回目录分隔符
            }
            $result = $this->getList($query);
            if(!empty($delimiter) && !isset($result['commonPrefixes'])) {
                $result['commonPrefixes'] = array();
            }
            $marker = $result['marker'];
            while(!empty($marker)){
                $query['marker'] = $marker;
                $temp = $this->getList($query);
                if(!empty($temp['items'])){
                    $result['items'] = array_merge($result['items'], $temp['items']);
                }
                if(!empty($temp['commonPrefixes'])){
                    $result['commonPrefixes'] = array_merge($result['commonPrefixes'], $temp['commonPrefixes']);
                }
                $marker = $temp['marker'] ? $temp['marker'] : '';
            }
            return $result;
        }

        public function getDir($dir=''){
            $query = array();
            $query['limit'] = 1000;
            $query['delimiter'] ='/';  //指定后返回目录分隔符
            if(!empty($dir)) $query['prefix'] = $dir;
            $result = $this->getList($query);
            $data = $result['commonPrefixes'];
            $marker = $result['marker'];
            while(!empty($marker)){
                $query['marker'] = $marker;
                $temp = $this->getList($query);
                if(!empty($temp['commonPrefixes'])){
                    $data= array_merge($data, $temp['commonPrefixes']);
                }
                $marker = $temp['marker'] ? $temp['marker'] : '';
            }
            if(empty($data)) $data = array();
            return $data;
        }

        public function formatDirData(&$data){
            $result = array();
            foreach($data as $v){
                $temp = explode('/', trim($v, '/'));
                $name = end($temp);
                $depth = count($temp);
                $result[] = array('DirName'=>$name, 'DirKey'=>$v, 'DirDepth'=>$depth);
            }
            return $result;
        }

        /**
         * 格式化数据
         *  $sortField：1时间（默认），2名称，3大小
         * $sortOrder：3：降序，4：升序
         */
        public function formatData(&$result, $sortField=1, $sortOrder=3){
            $Images = __ROOT__."/Public/Images/";
            $domain = $GLOBALS['Config']['QiniuUrl'];
            if(!empty($result['items'])){
                $map = array(1=>'putTime', 2=>'name', 3=>'fsize');
                $sortkeys = array();
                $datalist = array();
                foreach ($result['items'] as $v){
                    $key = $v['key'];
                    //创建一个目录时：a3/，a3/1.jpg，当打开a3/目录时，需要排除a3/
                    if('/' == substr($key, -1) ) continue;
                    $sortkeys[] = $v[ $map[$sortField] ];
                    $temp = explode('/', $key);
                    $name = end($temp);
                    $v['name'] = $name;
                    $v['fsize'] = byte_format( $v['fsize'] );
                    $ext = strtolower(yd_file_ext($key));
                    $v['filetype'] = getTplFileType( ".{$ext}");
                    //上传时间，单位：100纳秒，其值去掉低七位即为Unix时间戳。
                    $ts = intval($v['putTime']/10000000);
                    $v['putTime'] = yd_friend_date($ts);
                    $extFile = './Public/Images/FileICO/'.$ext.'.gif';
                    if( is_file( $extFile ) ){
                        $v['ico'] = "{$Images}FileICO/{$ext}.gif";
                    }else{
                        $v['ico'] = "{$Images}/FileICO/unknown.gif";
                    }
                    $v['fileurl'] = $domain.'/'.ltrim($key,'/');
                    $datalist[] = $v;
                }
                //对插件进行排序
                $sortFlag = ($sortField==2) ? SORT_REGULAR : SORT_NUMERIC;
                array_multisort($sortkeys, $sortOrder, $sortFlag, $datalist);
                $result['items'] = $datalist;
            }

            //遍历目录
            if(!empty($result['commonPrefixes'])){
                $datalist = array();
                foreach($result['commonPrefixes'] as $v){
                    $t = explode('/', $v);
                    $name = $t[ count($t)-2 ];
                    $datalist[] = array('name'=>$name, 'key'=>$v, 'ico'=>"{$Images}/FileICO/folder.gif");
                }
                $result['commonPrefixes'] = $datalist;
            }
        }

        /**
         * 获取某个文件的信息
         */
		public function info($key){
			$key = 	trim($key);
			$url = "{$this->QINIU_RS_HOST}/stat/" . self::Qiniu_Encode("{$this->bucket}:{$key}");
			$accessToken = 	$this->accessToken($url);
			$response = $this->request($url, 'POST', array('Authorization'=>"QBox $accessToken"));
			return $response;
		}

        /**
         * 判断文件是否存在
         */
		public function exist($key){
            $result = $this->info($key);
            if($result===false){
                return false;
            }else{
                return true;
            }
        }
		
		//设置镜像源地址
		public function setMirrorUrl($mirrorUrl){
			$url 	= 	"{$this->QINIU_PU_HOST}/image/{$this->bucket}/from/".self::Qiniu_Encode($mirrorUrl);
			$accessToken = $this->accessToken($url);
			$response = $this->request($url, 'POST', array('Authorization' 	=>"QBox {$accessToken}"));
			return $response;
		}
		
		//获取储存空间对应的域名
		public function getBucketDomain(){
			$url 	= 	"{$this->QINIU_API_HOST}/v6/domain/list?tbl={$this->bucket}";
			$accessToken = $this->accessToken($url);
			//返回：["oupgm4mi4.bkt.clouddn.com", "sarah.qiniudemo.com"]
			$response = $this->request($url, 'GET', array('Authorization' 	=>"QBox {$accessToken}"));
			if(!empty($response)){
				$response = str_replace(array('"','[', ']',' '), '', $response);
				$response = explode(',', $response);
			}
			return $response;
		}

        /**
         * 获取当前Bucket信息
         */
		public function bucketInfo(){
            $url 	= 	"{$this->QINIU_API_HOST}/v2/query?ak={$this->ak}&bucket={$this->bucket}";
            $result = yd_curl_get($url);
            $result = json_decode($result, true);
            return $result;
        }

        /**
         * 自动设置上传地址（每个区域的上传地址都是不同的）
         */
        public function autoSetUpHost(){
		    $info = $this->bucketInfo();
		    $host = $info['up']['src']['main'][0];
		    if(!empty($host)){
                $this->QINIU_UP_HOST = $host;
            }
        }

		//获取空间文件总数，存在bug，不使用
		public function getTotalFileCount(){
			//GET /v6/count?bucket=test01&begin=<BeginTime>&end=<EndTime>&g=day HTTP/1.1
			$timeStamp =time();
			$start = date("YmdHis", $timeStamp-10);
			$end = date("YmdHis", $timeStamp);
			$url = "{$this->QINIU_API_HOST}/v6/count?bucket={$this->bucket}";
			$url .= "&begin={$start}&end={$end}&g=day";
			$accessToken = 	$this->accessToken($url);
			$res = $this->request($url, 'POST', array('Authorization'=>"QBox $accessToken"));
			$n = 0;
			if( isset($res['datas'][0]) ){
				$n = $res['datas'][0];
			}
			return $n;
		}
		
		//获取文件下载资源链接
		public function downLink($key){
			$key = urlencode($key);
			$key = self::Qiniu_escapeQuotes($key);
			$url = "http://{$this->domain}/{$key}";
			return $url;
		}

		//重命名单个文件
		public function rename($file, $new_file){
			$key = trim($file);
			$url = "{$this->QINIU_RS_HOST}/move/" . self::Qiniu_Encode("{$this->bucket}:{$key}") .'/'. self::Qiniu_Encode("{$this->bucket}:{$new_file}");
			trace($url);
			$accessToken = $this->accessToken($url);
			$response = $this->request($url, 'POST', array('Authorization'=>"QBox $accessToken"));
			return $response;
		}



		//重命名目录
        public function renameDir($oldDir, $newDir){
            $oldDir = trim($oldDir, '/').'/';
            $newDir = trim($newDir, '/').'/';
            $query['prefix'] = $oldDir;
            $data = $this->getAllList($query, false);
            $response = true;
            if(!empty($data['items'])){
                $maxCount = 800; //每次批量删除最大文件个数，建议为800
                $url = $this->QINIU_RS_HOST . '/batch';
                $ops = array();
                $lastIndex = count($data['items'])-1;
                foreach ($data['items'] as $k=>$v) {
                    $key = $v['key'];
                    $newKey = str_replace_once($oldDir, $newDir, $key);
                    $oldFile = self::Qiniu_Encode("{$this->bucket}:{$key}");
                    $newFile = self::Qiniu_Encode("{$this->bucket}:{$newKey}");
                    $ops[] = "/move/{$oldFile}/{$newFile}";
                    if($maxCount == count($ops) || $k==$lastIndex){
                        $params = 'op=' . implode('&op=', $ops);
                        $apiUrl = "{$url}?{$params}";
                        $accessToken = $this->accessToken($apiUrl);
                        $response = $this->request($apiUrl, 'POST', array('Authorization'=>"QBox $accessToken"));
                        if(false===$response){
                            break;
                        }
                        $ops = array(); //重置
                    }
                }
            }
            return $response;
        }


		//删除单个文件
		public function del($file){
			$key = trim($file);
			$url = "{$this->QINIU_RS_HOST}/delete/" . self::Qiniu_Encode("{$this->bucket}:{$key}");
			$accessToken = $this->accessToken($url);
			$response = $this->request($url, 'POST', array('Authorization'=>"QBox $accessToken"));
			return $response;
		}

		//批量删除文件（最大限制1000次，超过1000个需要分包）
		public function delBatch($files){
            $maxCount = 800; //每次批量删除最大文件个数，建议为800
			$url = $this->QINIU_RS_HOST . '/batch';
            $ops = array();
            $lastIndex = count($files)-1;
            $response = true;
            foreach ($files as $k=>$file) {
                $ops[] = "/delete/". self::Qiniu_Encode("{$this->bucket}:{$file}");
                if($maxCount == count($ops) || $k==$lastIndex){
                    $params = 'op=' . implode('&op=', $ops);
                    $apiUrl = "{$url}?{$params}";
                    $accessToken = $this->accessToken($apiUrl);
                    $response = $this->request($apiUrl, 'POST', array('Authorization'=>"QBox $accessToken"));
                    if(false===$response){
                        break;
                    }
                    $ops = array(); //重置
                }
            }
            return $response;

            /*
			$ops = array();
			foreach ($files as $file) {
				$ops[] = "/delete/". self::Qiniu_Encode("{$this->bucket}:{$file}");
			}
			$params = 'op=' . implode('&op=', $ops);
			$url .= '?'.$params;
			trace($url);
			$accessToken = $this->accessToken($url);
			$response = $this->request($url, 'POST', array('Authorization'=>"QBox $accessToken"));
			return $response;
            */
		}

        /**
         * 批量复制
         * $oldFileList：数组
         */
        public function copyBatch($oldFileList, $newFileList){
            $maxCount = 800; //每次批量移动最大文件个数，建议为800
            $url = $this->QINIU_RS_HOST . '/batch';
            $ops = array();
            $lastIndex = count($newFileList)-1;
            $response = true;
            foreach ($newFileList as $k=>$file) {
                $oldFile = "{$this->bucket}:{$oldFileList[$k]}";
                $newFile = "{$this->bucket}:{$file}";
                $oldFile = self::Qiniu_Encode($oldFile);
                $newFile = self::Qiniu_Encode($newFile);
                $ops[] = "/copy/{$oldFile}/{$newFile}";
                if($maxCount == count($ops) || $k==$lastIndex){
                    $params = 'op=' . implode('&op=', $ops);
                    $apiUrl = "{$url}?{$params}";
                    $accessToken = $this->accessToken($apiUrl);
                    $response = $this->request($apiUrl, 'POST', array('Authorization'=>"QBox $accessToken"));
                    if(false===$response){
                        break;
                    }
                    $ops = array(); //重置
                }
            }
            return $response;
        }

        /**
         * 批量移动
         * $isOverWrite：当目标文件存在，是否覆盖
         */
        public function moveBatch($srcFiles, $dstFiles, $isOverWrite=false){
            $maxCount = 800; //每次批量移动最大文件个数，建议为800
            $url = $this->QINIU_RS_HOST . '/batch';
            $ops = array();
            $lastIndex = count($srcFiles)-1;
            $response = true;
            foreach ($srcFiles as $k=>$file) {
                $oldFile = "{$this->bucket}:{$file}";
                $newFile = "{$this->bucket}:".$dstFiles[$k];
                $oldFile = self::Qiniu_Encode($oldFile);
                $newFile = self::Qiniu_Encode($newFile);
                $ops[] = "/move/{$oldFile}/{$newFile}";
                if($maxCount == count($ops) || $k==$lastIndex){
                    $params = 'op=' . implode('&op=', $ops);
                    $apiUrl = "{$url}?{$params}";
                    //强制覆盖
                    if($isOverWrite) $apiUrl.= '/force/true';
                    $accessToken = $this->accessToken($apiUrl);
                    $response = $this->request($apiUrl, 'POST', array('Authorization'=>"QBox $accessToken"));
                    if(false===$response){
                        break;
                    }
                    $ops = array(); //重置
                }
            }
            return $response;
        }

		static function Qiniu_Encode($str) {// URLSafeBase64Encode
			$find = array('+', '/');
			$replace = array('-', '_');
			return str_replace($find, $replace, base64_encode($str));
		}

		static function Qiniu_escapeQuotes($str){
			$find = array("\\", "\"");
			$replace = array("\\\\", "\\\"");
			return str_replace($find, $replace, $str);
		}

	    /**
	     * 请求云服务器
	     * @param  string   $path    请求的PATH
	     * @param  string   $method  请求方法
	     * @param  array    $headers 请求header
	     * @param  resource $body    上传文件资源
	     * @return boolean
	     */
	    private function request($path, $method, $headers = null, $body = null){
	        $ch  = curl_init($path);
	        $_headers = array('Expect:');
	        if (!is_null($headers) && is_array($headers)){
	            foreach($headers as $k => $v) {
	                array_push($_headers, "{$k}: {$v}");
	            }
	        }

	        $length = 0;
			$date   = gmdate('D, d M Y H:i:s \G\M\T');

	        if (!is_null($body)) {
	            if(is_resource($body)){
	                fseek($body, 0, SEEK_END);
	                $length = ftell($body);
	                fseek($body, 0);

	                array_push($_headers, "Content-Length: {$length}");
	                curl_setopt($ch, CURLOPT_INFILE, $body);
	                curl_setopt($ch, CURLOPT_INFILESIZE, $length);
	            } else {
	                $length = @strlen($body);
	                array_push($_headers, "Content-Length: {$length}");
	                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
	            }
	        } else {
	            array_push($_headers, "Content-Length: {$length}");
	        }

	        // array_push($_headers, 'Authorization: ' . $this->sign($method, $uri, $date, $length));
	        array_push($_headers, "Date: {$date}");

	        curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);
	        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
	        curl_setopt($ch, CURLOPT_HEADER, 1);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

	        if ($method == 'PUT' || $method == 'POST') {
				curl_setopt($ch, CURLOPT_POST, 1);
	        } else {
				curl_setopt($ch, CURLOPT_POST, 0);
	        }

	        if ($method == 'HEAD') {
	            curl_setopt($ch, CURLOPT_NOBODY, true);
	        }

	        $response = curl_exec($ch);
	        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	        curl_close($ch);
	        list($header, $body) = explode("\r\n\r\n", $response, 2);
	        if ($status == 200) {
	            if ($method == 'GET') {
	                return $body;
	            } else {
	                return $this->response($response);
	            }
	        } else {
	            $this->error($header , $body);
	            return false;
	        }
	    }

        /**
	     * 获取响应数据
	     * @param  string $text 响应头字符串
	     * @return array        响应数据列表
	     */
	    private function response($text){
	        $headers = explode(PHP_EOL, $text);
	        $items = array();
	        foreach($headers as $header) {
	            $header = trim($header);
	            if(strpos($header, '{') !== False){
	                $items = json_decode($header, 1);
	                break;
	            }
	        }
	        return $items;
	    }

        /**
	     * 获取请求错误信息
	     * @param  string $header 请求返回头信息
	     */
		private function error($header, $body) {
	        list($status, $stash) = explode("\r\n", $header, 2);
	        list($v, $code, $message) = explode(" ", $status, 3);
	        $message = is_null($message) ? 'File Not Found' : "[{$status}]:{$message}]";
	        $obj= json_decode($body ,true);
	        if(isset($obj['error'])){
                $errMsg = $obj['error'];
            }elseif(isset($obj[0]['data']['error'])){
                $errMsg = $obj[0]['data']['error'];
            }else{
                $errMsg = $message;
            }
	        $this->_lastError = $errMsg;
	    }

        /**
         * 获取上次错误信息
         */
	    public function getLastError(){
		    return $this->_lastError;
        }
	}
