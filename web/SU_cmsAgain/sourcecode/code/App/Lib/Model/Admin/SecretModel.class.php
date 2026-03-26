<?php
//通信秘钥管理
class SecretModel extends Model{
	function getSecret($options = array()){
		if( isset($options['IsEnable']) && $options['IsEnable'] != -1){
			$IsEnable = intval($options['IsEnable']);
			$this->where("IsEnable=$IsEnable");
		}
		$result = $this->order('SecretID desc')->select();
		return $result;
	}
	
	function findSecret($id, $options = array() ){
		if( !is_numeric($id) ) return false;
		$where['SecretID'] = intval($id);
		if( isset($options['IsEnable']) && $options['IsEnable'] != -1 ) {
			$where['IsEnable'] = intval($options['IsEnable']);
		}
		$result = $this->where($where)->find();
		return $result;
	}
	
	/**
	 * 检查是否调用指定api的权限
	 * @param string $AppID
	 * @param string $ApiFunction Api函数名称
	 */
	function checkSecret($AppID, $ApiFunction){
		$AppID = YdInput::checkLetterNumber($AppID);
        $ApiFunction = YdInput::checkLetterNumber($ApiFunction);
		$Api = get_api_list();
		if( !isset($Api[$ApiFunction]) ) return false;
		$ApiID = $Api[$ApiFunction]['ApiID'];
		$where = "IsEnable=1 and AppID='{$AppID}' and (ApiList='all' or FIND_IN_SET({$ApiID}, ApiList))";
		$AppSecret = $this->where($where)->getField('AppSecret');
		return $AppSecret;
	}
	
	function getAppSecret($appID){
		$where['AppID'] = intval($appID);
		$where['IsEnable'] = 1;
		$AppSecret = $this->where($where)->getField('AppSecret');
		return $AppSecret;
	}

	function findMultiXcxSecret(){
        $where = "SecretRemark='multixcx' AND IsEnable=1";
        $data = $this->where($where)->field('AppID,AppSecret')->find();
        if(empty($data)){ //如果为空，则自动生成
            $data = array();
            $data['AppID'] = rand_string(8, 1);
            $data['AppSecret'] = rand_string(32, 999);
            $data['ApiList'] = 'all';
            $data['SecretTime'] = date("Y-m-d H:i:s");
            $data['IsEnable'] = 1;
            $data['SecretRemark'] = 'multixcx'; //表示多端小程序
            $id = $this->add($data);
            if(empty($id)) $data = array();
        }
        $result = array();
        $result['CmsAppID'] = $data['AppID'];
        $result['CmsAppSecret'] = $data['AppSecret'];
        return $result;
    }
}