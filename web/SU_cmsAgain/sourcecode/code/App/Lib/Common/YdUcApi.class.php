<?php
/**
 * 用户中心接口
 */
if (!defined('APP_NAME')) exit();
class YdUcApi{
    private $ApiUrl = '';
    private $LastError = '';
    private $AdminID = 0;
    private $MemberID = 0;
    function __construct(){
        $this->ApiUrl = C('UcUrl');
        $this->LastError = '';
        $this->AdminID = (int)session('AdminID');
        $this->MemberID = (int)session("AdminMemberID");
    }

    /**
     * 返回上一次错误
     */
    public function getLastError(){
        return $this->LastError;
    }

    /**
     * 获取api地址
     */
    private function getUrl($action, $module='Uc'){
        $url = "{$this->ApiUrl}/api/index.php/public/{$module}/{$action}";
        return $url;
    }

    /**
     * 获取微信绑定相关信息
     */
    function getUcBindInfo(){
        $m = D("Admin/Admin");
        $where['AdminID'] = $this->AdminID;
        $field = "UcOpenID,MemberID";
        $result = $m->where($where)->field($field)->find();
        if(!is_array($result)) {
            $result = array();
        }else{
            if(!empty($result['UcOpenID'])){
                $m = D('Admin/Member');
                $where = "MemberID={$result['MemberID']}";
                $m->field('MemberAvatar,WxName');
                $info = $m->where($where)->find();
                if(!empty($info)){
                    $result = array_merge($result, $info);
                }
            }
        }
        $result['CurrentDomain'] = $_SERVER['HTTP_HOST'];
        $result['WebName'] = str_ireplace('"', '', $GLOBALS['Config']['WEB_NAME']);
        return $result;
    }

    /**
     * 在绑定成功后，设置绑定信息
     * 参数：WxName、UcOpenID、UserAvatar
     */
    function setUcBindInfo($params){
        $UcOpenID = YdInput::checkLetterNumber($params['UcOpenID']);
        if(empty($UcOpenID)){
            $this->LastError = "UcOpenID参数不能为空";
            return false;
        }
        $m = D("Admin/Admin");
        $where = "AdminID={$this->AdminID}";
        $result = $m->where($where)->setField('UcOpenID', $UcOpenID);
        if(false !== $result){
            $Avatar = $params['UserAvatar'];
            $WxName = $params['WxName'];
            $m = D('Admin/Member');
            $where = "MemberID={$this->MemberID}";
            $dataToUpdate = array();
            $dataToUpdate['MemberAvatar'] = $Avatar;
            $dataToUpdate['WxName'] = $WxName;
            $result = $m->where($where)->setField($dataToUpdate);
        }
        return $result;
    }

    /**
     * 获取当前管理员的UcOpenID
     */
    public function getUcOpenID(){
        $AdminID = $this->AdminID;
        $m = D("Admin/Admin");
        $where['AdminID'] = intval($AdminID);
        $openid = $m->where($where)->getField('UcOpenID');
        return $openid;
    }

    /**
     * 判断网站有没有绑定UC(任何账号)
     */
    public function isBindUc(){
        $m = D("Admin/Admin");
        $where = "UcOpenID!=''";
        $id = $m->where($where)->getField('AdminID');
        if($id>0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 清除OpenID
     */
    private function clearUcOpenID(){
        $m = D("Admin/Admin");
        $where['AdminID'] = $this->AdminID;
        $result = $m->where($where)->setField('UcOpenID', '');
        return $result;
    }

    /**
     * 在管理后台解绑
     */
    public function unBindUc(){
        $openid = $this->getUcOpenID();
        if(empty($openid)){
            return true;
        }
        $url = $this->getUrl('unBindUc');
        $params['UcOpenID'] = $openid;
        $result = yd_curl_post($url, $params);
        if(empty($result)){
            $this->LastError = '解绑微信失败！API返回空';
            return false;
        }
        $result = json_decode($result, true);
        if(empty($result['Status'])){
            $this->LastError = $result['Message'];
            return false;
        }
        //同步更新数据库
        $result = $this->clearUcOpenID();
        return $result;
    }

    /**
     * 检查解绑状态
     * 返回true：已绑定、false：未绑定
     */
    public function checkBindUcStatus(){
        $openid = $this->getUcOpenID();
        if(empty($openid)){
            return true;
        }
        $url = $this->getUrl('checkBindUcStatus');
        $params['UcOpenID'] = $openid;
        $result = yd_curl_post($url, $params);
        if($result==='unbind'){
            //如果未绑定，就直接清空OpenID
            $this->clearUcOpenID();
            return false;
        }
        return true;
    }

    /**
     * 获取登录二维码
     */
    public function getLoginUcQrcode(){
        $url = $this->getUrl('getLoginUcQrcode');
        $params = array('Domain'=>$_SERVER['HTTP_HOST']);
        $result = yd_curl_post($url, $params);
        exit($result);
    }

    /**
     * 检查二维码登录是否成功
     */
    public function checkLoginUcQrcode($SceneStr){
        $url = $this->getUrl('checkLoginUcQrcode');
        $params = array(
            'SceneStr'=>$SceneStr,
            'Domain'=>$_SERVER['HTTP_HOST']
        );
        $result = yd_curl_post($url, $params);
        if(empty($result)){
            $this->LastError = '获取二维码失败！API返回空';
            return false;
        }
        $result = json_decode($result, true);
        if(empty($result['Status'])){
            $this->LastError = $result['Message'];
            return false;
        }
        return $result['Data']; //对应UcOpenID
    }
}