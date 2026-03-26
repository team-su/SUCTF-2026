<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class MenuOperationModel extends Model{
	/**
	 * 返回后台当前路径信息
	 * @param string $actionName
	 * @param string $moduleName
	 * @param string $groupName
	 * @return array
	 */
	function getMenuOperationPath($actionName, $moduleName, $groupName){
	    //参数过滤
	    $actionName = YdInput::checkLetterNumber($actionName);
        $moduleName = YdInput::checkLetterNumber($moduleName);
        $groupName = YdInput::checkLetterNumber($groupName);
        $data = array();
		//Info模块的路径单独处理
		if($moduleName=='Info'){
			$this->field('a.MenuGroupName, b.MenuTopName');
			$this->table($this->tablePrefix.'menu_group a');
			$this->join($this->tablePrefix.'menu_top b On a.MenuTopID = b.MenuTopID and b.IsEnable=1');
			$where['a.MenuGroupID'] = ($groupName=='Admin') ? 3 : 17;
			$result = $this->where($where)->find();
			if( empty($result) ) return false;
			$list = array( 'add'=>'添加', 'modify'=>'修改' );
			if( !empty($result['MenuTopName'])){
				$data[] = array('Name'=>$result['MenuTopName'] );
			}
			if( !empty($result['MenuGroupName'])){
				$data[] = array('Name'=>$result['MenuGroupName'] );
			}
			$actionName = strtolower($actionName);
			if( array_key_exists($actionName, $list) ){
				$data[] = array('Name'=>$list[ $actionName ] );
			}
			return $data;
		}
		
		//对于会员后台的频道
		if($groupName=='Member' && $moduleName=='Channel'){
			$this->field('a.MenuGroupName, b.MenuTopName');
			$this->table($this->tablePrefix.'menu_group a');
			$this->join($this->tablePrefix.'menu_top b On a.MenuTopID = b.MenuTopID and b.IsEnable=1');
			$where['a.MenuGroupID'] = 17;
			$result = $this->where($where)->find();
			if( empty($result) ) return false;
			if( !empty($result['MenuTopName'])){
				$data[] = array('Name'=>$result['MenuTopName'] );
			}
			if( !empty($result['MenuGroupName'])){
				$data[] = array('Name'=>$result['MenuGroupName'] );
			}
			return $data;
		}
		
		$this->field('a.MenuOperationName, b.MenuName, c.MenuGroupName, d.MenuTopName,a.LogType');
		$this->table($this->tablePrefix.'menu_operation a');
		$this->join($this->tablePrefix.'menu b On a.MenuID = b.MenuID and b.IsEnable=1');
		$this->join($this->tablePrefix.'menu_group c On b.MenuGroupID = c.MenuGroupID and c.IsEnable=1');
		$this->join($this->tablePrefix.'menu_top d On c.MenuTopID = d.MenuTopID and d.IsEnable=1');
		
		$where['d.MenuOwner'] = ($groupName=='Admin') ? 1 : 0;
		$where['a.ModuleName'] = $moduleName;
		$where['a.ActionName'] = strtolower($actionName);
		$where['a.IsEnable'] = 1;
		$result = $this->where($where)->find();
		if( !empty($result) ){
			if( !empty($result['MenuTopName'])){
				$data[] = array('Name'=>$result['MenuTopName'] );
			}
			
			if( !empty($result['MenuGroupName'])){
				$data[] = array('Name'=>$result['MenuGroupName'] );
			}
			
			if( !empty($result['MenuName'])){
				$data[] = array('Name'=>$result['MenuName'] );
			}
			
			//查看类型的不显示为路径
			if( !empty($result['MenuOperationName']) && $result['LogType'] != 9){
				$data[] = array('Name'=>$result['MenuOperationName']);
			}
			return $data;
		}
		return $result;
	}
	
	/**
	 * 返回日志操作的菜单信息（仅在WriteLog调用）
	 * @param string $actionName
	 * @param string $moduleName
	 * @param string $groupName
	 * @return array 
	 */
	function getLog($actionName, $moduleName, $groupName){
        $actionName = YdInput::checkLetterNumber($actionName);
        $moduleName = YdInput::checkLetterNumber($moduleName);
        $groupName = YdInput::checkLetterNumber($groupName);

        $actionName = strtolower($actionName);
	    $data = $this->getLogData($actionName, $moduleName, $groupName);
	    if(empty($data)){
            $this->field('a.MenuOperationName,a.LogType, a.ActionName, a.ModuleName, b.MenuName');
            $this->table($this->tablePrefix.'menu_operation a');
            $this->join($this->tablePrefix.'menu b On a.MenuID = b.MenuID and b.IsEnable=1');
            $where['a.ActionName'] = $actionName;
            $where['a.ModuleName'] = $moduleName;
            $where['a.GroupName'] = $groupName;
            $data = $this->where($where)->find();
        }
		return $data;
	}

    /**
     * 这些数据暂不写入数据库，稳定后再写入数据库
     */
	private function getLogData($actionName, $moduleName, $groupName){
        $actionName = YdInput::checkLetterNumber($actionName);
        $moduleName = YdInput::checkLetterNumber($moduleName);
        $groupName = YdInput::checkLetterNumber($groupName);

	    $key = "{$groupName}_{$moduleName}_{$actionName}";
	    $map = array();
        $map["Admin_Template_deletetemplatefile"] = array('MenuOperationName'=>'删除模板文件', 'LogType'=>'3', 'MenuName'=>'模板管理');
        $map["Admin_Template_addtemplatefile"] = array('MenuOperationName'=>'创建模板文件', 'LogType'=>'2', 'MenuName'=>'模板管理');
        $map["Admin_WapTemplate_deletetemplatefile"] = array('MenuOperationName'=>'删除模板文件', 'LogType'=>'3', 'MenuName'=>'模板管理');
        $map["Admin_WapTemplate_addtemplatefile"] = array('MenuOperationName'=>'创建模板文件', 'LogType'=>'2', 'MenuName'=>'模板管理');
        $map["Admin_Channel_createtemplate"] = array('MenuOperationName'=>'创建模板文件', 'LogType'=>'2', 'MenuName'=>'频道管理');
        //插件相关
        $map["Admin_Plugin_savetranslate"] = array('MenuOperationName'=>'保存配置', 'LogType'=>'4', 'MenuName'=>'百度翻译');
        $map["Admin_Plugin_savebancopy"] = array('MenuOperationName'=>'保存配置', 'LogType'=>'4', 'MenuName'=>'屏蔽鼠标右键');
        $map["Admin_Plugin_savesmsconfig"] = array('MenuOperationName'=>'保存配置', 'LogType'=>'4', 'MenuName'=>'短信功能');
        $map["Admin_Plugin_savebadip"] = array('MenuOperationName'=>'保存配置', 'LogType'=>'4', 'MenuName'=>'IP黑名单');
        $map["Admin_Qiniu_saveconfig"] = array('MenuOperationName'=>'保存配置', 'LogType'=>'4', 'MenuName'=>'七牛云');

        $map["Admin_Plugin_savebadreplace"] = array('MenuOperationName'=>'保存配置', 'LogType'=>'4', 'MenuName'=>'敏感词过滤');
        $map["Admin_Plugin_saveenablegray"] = array('MenuOperationName'=>'保存配置', 'LogType'=>'4', 'MenuName'=>'网站一键变灰');
        $map["Admin_Plugin_savecheckwords"] = array('MenuOperationName'=>'保存配置', 'LogType'=>'4', 'MenuName'=>'敏感词检测');
        $map["Admin_Plugin_savedistributionconfig"] = array('MenuOperationName'=>'保存配置', 'LogType'=>'4', 'MenuName'=>'三级分销');

        $map["Admin_Area_batchdel"] = array('MenuOperationName'=>'删除区域', 'LogType'=>'3', 'MenuName'=>'区域管理');
        $map["Admin_Config_savetheme"] = array('MenuOperationName'=>'保存配置', 'LogType'=>'4', 'MenuName'=>'主题颜色设置');

        $map["Admin_Config_savejob"] = array('MenuOperationName'=>'保存配置', 'LogType'=>'4', 'MenuName'=>'简历设置');
        if(isset($map[$key])){
            return $map[$key];
        }else{
            return false;
        }
    }
	
	/**
	 * 获取当前操作所属的菜单ID
	 * @param string $actionName
	 * @param string $moduleName
	 * @param string $groupName
	 * @return 菜单ID
	 */
	function getMenuID($actionName, $moduleName, $groupName){
        $actionName = YdInput::checkLetterNumber($actionName);
        $moduleName = YdInput::checkLetterNumber($moduleName);
        $groupName = YdInput::checkLetterNumber($groupName);

		$where['ActionName'] = strtolower($actionName); //统一使用小写
		$where['ModuleName'] = $moduleName;
		$where['GroupName'] = $groupName;
		$id = $this->where($where)->getField('MenuID');
		return $id;
	}
}
