<?php
if (!defined('APP_NAME')) exit();
class YdApi{
    private $AppID = '';
    private $AppKey = '';
    private $ApiUrl = 'http://api.youdiancms.com/index.php/';
    private $LastError = '';
    private $Code=0; //api返回的Code
    private $Token = ''; //用户中心登录Token
    function __construct($AppID='', $AppKey=''){
        $this->AppID = $AppID;
        $this->AppKey = $AppKey;
    }

    /**
     * 设置Token
     */
    function setToken($token){
        $token = YdInput::checkLetterNumber($token);
        $this->Token = $token;
    }

    /**
     * 获取最新版本号
     */
    function getLatestVersion(){
        $url = "{$this->ApiUrl}Public/getLatestVersion";
        $params = array();
        $this->_getPublicParams($params);
        $result = yd_curl_post($url, $params);
        $result = json_decode($result, true);
        if(1 == $result['Status']){
            return $result['Data'];
        }else{
            $this->LastError = $result['Message'];
            $this->Code = $result['Code'];
            return false;
        }
    }

    function saveUpgradeLog(){
        $url = "{$this->ApiUrl}Public/saveUpgradeLog";
        $params = array();
        $this->_getPublicParams($params);
        $result = yd_curl_post($url, $params);
        $result = json_decode($result, true);
        if(1 == $result['Status']){
            return $result['Data'];
        }else{
            $this->LastError = $result['Message'];
            $this->Code = $result['Code'];
            return false;
        }
    }

    function getUpgradeExtraInfo(){
        $url = "{$this->ApiUrl}Public/getUpgradeExtraInfo";
        $params = array();
        $this->_getPublicParams($params);
        $result = yd_curl_post($url, $params);
        $result = json_decode($result, true);
        if(1 == $result['Status']){
            return $result['Data'];
        }else{
            $this->LastError = $result['Message'];
            $this->Code = $result['Code'];
            return false;
        }
    }

    /**
     * 检查装修组件是否有效
     */
    function checkDecorationKey(){
        $url = "{$this->ApiUrl}Component/checkDecorationKey";
        $params = array();
        $params['DecorationID'] = $this->AppID;
        $params['DecorationKey'] = $this->AppKey;
        $this->_getPublicParams($params);
        $result = yd_curl_post($url, $params);
        $result = json_decode($result, true);
        if(1 == $result['Status']){
            return true;
        }else{
            $this->LastError = $result['Message'];
            $this->Code = $result['Code'];
            return false;
        }
    }

    /**
     * 检查是否购买组件
     */
    function checkBuyDecoration(){
        $url = "{$this->ApiUrl}Component/checkBuyDecoration";
        $params = array();
        $this->_getPublicParams($params);
        $result = yd_curl_post($url, $params);
        $result = json_decode($result, true);
        if(1 == $result['Status']){
            return true;
        }else{
            $this->LastError = $result['Message'];
            $this->Code = $result['Code'];
            return false;
        }
    }

    /**
     * 获取组件
     */
    function getComponent(){
        $url = "{$this->ApiUrl}Component/getComponent";
        $params = array();
        $this->_getPublicParams($params);
        $result = yd_curl_post($url, $params);
        $result = json_decode($result, true);
        if(1 == $result['Status']){
            return $result['Data'];
        }else{
            $this->LastError = $result['Message'];
            $this->Code = $result['Code'];
            return false;
        }
    }

    /**
     * 查看组件
     */
    function findComponent($ComponentClassKey, $GroupID){
        $url = "{$this->ApiUrl}Component/findComponent";
        $params = array();
        $params['ComponentClassKey'] = $ComponentClassKey;
        $params['GroupID'] = $GroupID;
        $this->_getPublicParams($params);
        $result = yd_curl_post($url, $params);
        $result = json_decode($result, true);
        if(1 == $result['Status']){
            return $result['Data'];
        }else{
            $this->LastError = $result['Message'];
            $this->Code = $result['Code'];
            return false;
        }
    }

    function getSensitiveWords($WordsType=1){
        $url = "{$this->ApiUrl}Words/getSensitiveWords";
        $params = array();
        $params['WordsType'] = intval($WordsType);
        $this->_getPublicParams($params);
        $result = yd_curl_post($url, $params);
        $result = json_decode($result, true);
        if(1 == $result['Status']){
            return $result['Data'];
        }else{
            $this->LastError = $result['Message'];
            $this->Code = $result['Code'];
            return false;
        }
    }

    /**
     * 获取最新版本号
     */
    function getIpLocation($ip){
        $url = "{$this->ApiUrl}Utility/getIpLocation";
        $params = array();
        $params['Ip'] = $ip;
        $this->_getPublicParams($params);
        $result = yd_curl_post($url, $params);
        $result = json_decode($result, true);
        if(1 == $result['Status']){
            return $result['Data'];
        }else{
            $this->LastError = $result['Message'];
            $this->Code = $result['Code'];
            return false;
        }
    }

    /**
     * 获取公共参数
     */
    private function _getPublicParams(&$params){
        $params['AppID'] = $this->AppID;
        $params['Timestamp'] = time();
        $params['NonceStr'] = rand_string(32); //随机字符串，不长于32位
        $params['Host'] = $_SERVER['HTTP_HOST'];
        if(!empty($this->Token)){
            $params['Token'] = $this->Token;
        }
        $params['OS'] = PHP_OS;
        $params['Source'] = $this->_getSource();
        $params['Sign'] = $this->_makeSign($params);
        return $params;
    }

    /**
     * 生成秘钥
     */
    private function _makeSign($params){
        $params = app_para_filter($params);
        ksort($params);
        reset($params);
        $sign = app_build_sign($params, $this->AppKey);
        return $sign;
    }

    private function _getSource(){
        $fileName = APP_DATA_PATH.'sou'.'rce.lo'.'ck';
        $str = file_exists($fileName) ? file_get_contents($fileName) : 1;
        return $str;
    }

    function getLastError(){
        return $this->LastError;
    }

    function getCode(){
        return (int)$this->Code;
    }

    /**
     * 获取应用插件附加信息
     */
    function getPluginExtraInfo(){
        $url = "{$this->ApiUrl}Plugin/getPluginExtraInfo";
        $params = array();
        $this->_getPublicParams($params);
        $result = yd_curl_post($url, $params);
        $result = json_decode($result, true);
        if(1 == $result['Status']){
            return $result['Data'];
        }else{
            $this->LastError = $result['Message'];
            $this->Code = $result['Code'];
            return false;
        }
    }

    /**
     * 获取应用插件信息
     */
    function getPlugin(){
        $url = "{$this->ApiUrl}Plugin/getPlugin";
        $params = array();
        $this->_getPublicParams($params);
        $result = yd_curl_post($url, $params);
        $result = json_decode($result, true);
        if(1 == $result['Status']){
            return $result['Data'];
        }else{
            $this->LastError = $result['Message'];
            $this->Code = $result['Code'];
            return false;
        }
    }

    /**
     * $SqlType：获取插件脚本（1：安装，2：卸载，3：升级）
     * $PluginVersion：当前插件版本（仅对Type=3有效）
     */
    function getPluginSql($SqlType=3, $PluginID=0, $PluginVersion=99){
        $url = "{$this->ApiUrl}Plugin/getPluginSql";
        $params = array();
        $this->_getPublicParams($params);
        $params['SqlType'] = intval($SqlType);
        $params['PluginID'] = intval($PluginID);
        if($SqlType == 3){
            $params['PluginVersion'] = $PluginVersion;
        }
        $result = yd_curl_post($url, $params);
        $result = json_decode($result, true);
        if(1 == $result['Status']){
            return $result['Data'];
        }else{
            $this->LastError = $result['Message'];
            $this->Code = $result['Code'];
            return false;
        }
    }

    /**
     * 获取小程序模板数据
     */
    function findXcxTemplate($TemplateNo){
        $url = "{$this->ApiUrl}XcxTemplate/findXcxTemplate";
        $params = array();
        $params['TemplateNo'] = $TemplateNo;
        $this->_getPublicParams($params);
        $result = yd_curl_post($url, $params);
        $result = json_decode($result, true);
        if(1 == $result['Status']){
            return $result['Data'];
        }else{
            $this->LastError = $result['Message'];
            $this->Code = $result['Code'];
            return false;
        }
    }

    /**
     * 获取特征关键词
     */
    function getHorseCharacteristic(){
        $url = "{$this->ApiUrl}Plugin/getHorseCharacteristic";
        $params = array();
        $this->_getPublicParams($params);
        $result = yd_curl_post($url, $params);
        $result = json_decode($result, true);
        if(1 == $result['Status']){
            return $result['Data'];
        }else{
            $this->LastError = $result['Message'];
            $this->Code = $result['Code'];
            return false;
        }
    }
}


//==================API签名认证函数 开始======================
/**
 * 参数过滤
 * @param array $para 参数
 */
function app_para_filter($para) {
    $para_filter = array();
    foreach ($para as $key=>$val) {
        //去除调试参数：&XDEBUG_SESSION_START=ECLIPSE_DBGP&KEY=14868670618872
        if(!is_array($val) && $val !== '' && $key != 'Sign' && $key !='XDEBUG_SESSION_START' && $key !='KEY') {
            $para_filter[$key] = $para[$key];
        }
    }
    return $para_filter;
}

/**
 * 生成签名
 * @param array $para
 * @param array $AppSecret 秘钥
 */
function app_build_sign($para,$AppSecret) {
    //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
    //由于js客户端在计算参数时用了encodeURIComponent，所以服务器端参数也必须urlencode编码
    //原因：同一中文字符使用 js md5.createHash()和php md5函数的结果不一样，所以必须编码
    //Upload(1).jpg encodeURIComponent: Upload%2F1(1).jpg，
    //                                 urlencode结果:   Upload%2F1%281%29.jpg，2者结果不一样
    $str = app_link_string($para,false);
    $sign = md5($str.$AppSecret);
    return $sign;
}

/**
 * 连接参数
 * @param array $para
 * @param boo $urlEncode 是否编码
 * @return string
 */
function app_link_string($para, $urlEncode=true) {
    $arg  = '';
    foreach ($para as $key=>$val ) {
        if(is_array($val)) continue;
        if($urlEncode) $val = urlencode($val);
        $arg .= "{$key}={$val}&";
    }
    //去掉最后一个&字符
    $arg = substr($arg, 0, -1);
    //如果存在转义字符，那么去掉转义
    if(get_magic_quotes_gpc()){
        $arg = stripslashes($arg);
    }
    return $arg;
}
//==================API签名认证函数 结束======================