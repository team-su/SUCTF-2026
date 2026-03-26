<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class LogAction extends AdminBaseAction{
	//友情链接
	function index(){
	    //自动清除日志
	    if(!isset($_GET['p']) || 1==$_GET['p']){
            $m = D('Admin/Log');
            $m->deleteExpiredLog();
        }
        $p['DataCallBack'] = 'updateUserCity';
		$p['Parameter'] = array(
				'UserName' => isset($_REQUEST['UserName']) ? $_REQUEST['UserName'] : '',
				'LogType' => isset($_REQUEST['LogType']) ? $_REQUEST['LogType'] : '',
		);
		$p['HasPage'] = true; //表示有分页
		$this->opIndex( $p );
	}

    /**
     * 更新用户城市
     */
	protected function updateUserCity(&$data){
	    if(empty($data)) return;
        $ip = array();
        foreach($data as $v){
            if(empty($v['UserCity']) && !empty($v['UserIP'])){
                $ip[] = $v['UserIP'];
            }
        }
        if(empty($ip)) return;
        $ip = array_unique($ip); //去重
        $ip = implode(',', $ip);
        import("@.Common.YdApi");
        $api = new YdApi();
        $map = $api->getIpLocation($ip);
        $m = D('Admin/Log');
        try{
            foreach($data as $k=>$v){
                $key = $v['UserIP'];
                if(isset($map[$key])){
                    $data[$k]['UserCity'] = $map[$key];
                    $where = "LogID={$v['LogID']}";
                    $b = $m->where($where)->setField('UserCity', $map[$key]);
                }
            }
        }catch(Exception $e){

        }
    }
	
	//删除、批量删除
	function del(){
	    return;
	    /*
		if( !empty($_REQUEST['UserName']) ){
			$p['Parameter']['UserName'] = $_REQUEST['UserName'];
		}
		if( !empty($_REQUEST['LogType']) ){
			$p['Parameter']['LogType'] = $_REQUEST['LogType'];
		}
		if( is_numeric($_REQUEST['p']) ){
			$p['Parameter']['p'] = $_REQUEST['p'];
		}
		$this->opDel( $p );
	    */
	}

	//清除所有日志
	function delAll(){
        return;
		//$this->opDelAll( );
	}
	
	function config(){
		$m = D('Admin/Config');
		$data = $m->getConfig('basic'); //配置数据不从缓存中提取
		$this->assign('LogStatus', $data['LOG_STATUS'] );
		$this->assign('LogTypeAllow', $data['LOGTYPE_ALLOW'] );
		$this->assign('Action', __URL__.'/saveConfig' );
		$this->display();
	}
	
	//保存配置
	function saveConfig(){
        if( is_array($_POST['LOGTYPE_ALLOW']) ){
            $_POST['LOGTYPE_ALLOW'] = implode(',', $_POST['LOGTYPE_ALLOW']);
        }else{
            $_POST['LOGTYPE_ALLOW'] = '';
        }
        $fieldMap = array('LOG_STATUS'=>2, 'LOGTYPE_ALLOW'=>2);
        $data = GetConfigDataToSave($fieldMap);
        $m = D("Admin/Config");
        if( $m->saveConfig($data, 'basic') ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
	}
	
	//通过IP获取地理位置
	function getLocation(){
		//现对ip进行去重
		$ips = array_unique($_POST['UserIP']);
		$m = D('Admin/Log');
		foreach ($ips as $ip){
			$data = yd_ip2location($ip);
			if( $data !== false ){
				$city = implode(' ', $data);
				$m->setCity($ip, $city);
			}
		}
		$this->ajaxReturn(null, '保存成功!' , 1);
	}
}