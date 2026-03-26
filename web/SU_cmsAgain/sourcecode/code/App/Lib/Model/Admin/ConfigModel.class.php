<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class ConfigModel extends Model{
	/**
	 * 保存配置到数据库
	 * @param array $data 配置数据
	 * @param string $configFile 配置文件名(省略扩展名.php)
	 * @param bool $cache 是否缓存到配置文件
	 */
	function saveConfig($data, $cache = true){
        try{
            $this->_save($data); //保存到数据库
            if($cache){ //缓存数据
                //必须删除中英配置，下面的writeConfig只重写当前语言的
                YdCache::deleteConfig();
                YdCache::writeConfig();
            }
            return true;
        }catch(Exception $e){
            return false;
        }
	}
	
	/**
	 * 获取配置数据
	 */
	function getConfig($ConfigFile=false){
		$where = '('.get_language_where().' or LanguageID=0)';
		$where .= " and IsEnable = 1 and ConfigFile not in('config','html','home/config','wap/config') ";
		if($ConfigFile){
            $ConfigFile = addslashes(stripslashes($ConfigFile));
            $ConfigFile = YdInput::checkHtmlName($ConfigFile);
			$where .= " and ConfigFile = '{$ConfigFile}'";
		}
		$data = $this->where($where)->getField('ConfigName,ConfigValue');
		return $data;
	}
	
	//保存数据库配置信息
	private function _save($data){
        //需要删除敏感变量
        $keysToDel = array('UPLOAD', 'SafeErrorCount', 'SafeAnswer', 'SafeQuestion');
	    foreach($keysToDel as $key){
	        unset($data[$key]);
	    }

		foreach($data as $key=>$val) {
		    $key = YdInput::checkTableField($key);
			$config    = array();
			$config['ConfigValue']  =  $val;
			$where = '('.get_language_where().' or LanguageID=0)';
			$where .=  " and ConfigName='".$key."'";
			$this->where($where)->save($config);
		}
	}
	
	function getDomain(){
		$where = '('.get_language_where().' or LanguageID=0)';
		$where .= " and IsEnable = 1 and ConfigFile='domain'";
		$data = $this->where($where)->getField('ConfigName,ConfigValue');
		return $data;
	}

    /**
     * 获取多端小程序配置数据
     */
	function getMultiXcxConfig(){
        $where = 'LanguageID=0 AND IsEnable = 1';
        $where .= " AND ConfigID IN(358,360,362,364)"; //仅返回小程序APPID
        $data = $this->where($where)->getField('ConfigName,ConfigValue');
        return $data;
    }

    function setXcxAppID($type, $appID){
	   $map = array('bd'=>358, 'tt'=>360,  'wx'=>362,  'zfb'=>364, 'qh'=>366);
	   if( isset($map[$type]) ){
           $appID = YdInput::checkLetterNumber($appID);
           $where = "ConfigID={$map[$type]}";
           $result = $this->where($where)->setField('ConfigValue', $appID);
       }else{
           $result = false;
       }
        return $result;
    }

    /**
     * 获取配置项
     */
    function getConfigItem($ConfigName, $FieldName='ConfigValue'){
        $ConfigName = YdInput::checkLetterNumber($ConfigName);
        $FieldName = YdInput::checkLetterNumber($FieldName);
        $where = '('.get_language_where().' or LanguageID=0)';
        $where .= " AND ConfigName='{$ConfigName}'";
        $result = $this->where($where)->getField($FieldName);
        return $result;
    }

    /**
     * 配置项是否启用
     */
    function isEnable($ConfigName){
        $ConfigName = YdInput::checkLetterNumber($ConfigName);
        $result = $this->getConfigItem($ConfigName, 'ConfigValue');
        return $result;
    }

    /**
     * 判断用户旧密码是否存在
     * $answer：答案明文
     */
    function isSafeAnswerCorrect($answer){
        $where = "ConfigID=381";
        $hash = $this->where($where)->getField('ConfigValue');
        $b = yd_password_verify($answer, $hash);
        if($b){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 检查录入答案错误次数
     */
    function checkSafeErrorCount(&$errorCount=0, &$errorText='', &$maxCount=0){
        $maxCount = 3; //最大错误次数
        $maxTimeout = 15*60; //单位：秒，15分钟=900秒
        $where = "ConfigID = 382";
        $this->field('ConfigValue AS ErrorCount,ConfigDescription AS ErrorTime');
        $data = $this->where($where)->find();
        $timeSpan = time() - (int)$data['ErrorTime'];
        if($timeSpan > $maxTimeout){ //如果超时，则重置次数
            $errorCount = 0;
            $this->incSafeErrorCount(-1); //超时后置0
        }else{
            $errorCount = empty($data) ? 0 : (int)$data['ErrorCount'];
        }
        if($errorCount >= $maxCount){ //这里必须加=，错误次数增加在后面代码
            $min = $maxTimeout/60;
            $errorText = "输入错误次数过多，请{$min}分钟后再试！";
            return false;
        }
        return true;
    }

    /**
     * 增加安全错误次数
     */
    function incSafeErrorCount($ErrorCount=0){
        $dataToUpdate = array();
        $dataToUpdate['ConfigValue'] = $ErrorCount+1;
        $dataToUpdate['ConfigDescription'] = time();
        $where = "ConfigID=382";
        $result = $this->where($where)->setField($dataToUpdate);
        return $result;
    }
}