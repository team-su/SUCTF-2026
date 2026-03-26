<?php
/**
 * 安全处理
 */
if (!defined('APP_NAME')) exit();
class YdSafe{
    static $asteriskCount = 10; //星号个数
    /**
     * 判断是否是敏感数据
     */
    static public function isSensitiveData($value) {
        if(empty($value) || is_array($value)){
            return false;
        }
        $c = str_repeat('*', self::$asteriskCount);
        if(false !==stripos($value, $c)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 用*替换敏感数据
     */
    static function hideSensitiveData($value){
        if(empty($value) || is_array($value)){
            return $value;
        }
        $c = str_repeat('*', self::$asteriskCount);
        $len = strlen($value);
        if($len > self::$asteriskCount) {
            $showLen = intval(0.2*$len);
            if($showLen<4) $showLen = 4;
            if($showLen>6) $showLen = 6;
        }else{
            $showLen = 3;
        }
        $value = substr($value, 0, $showLen).$c.substr($value, -$showLen);
        return $value;
    }

}