<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class LanguageModel extends Model{
	function getLanguage($p=array()){
		$where = array();
        if( isset($p['IsEnable']) && -1 != $p['IsEnable'] ){
            $where['IsEnable'] = intval($p['IsEnable']);
        }
		$data = $this->where($where)->order('LanguageOrder ASC, LanguageID ASC')->select();
		return $data;
	}

    function findLanguage($id, $p = array()){
        if( !is_numeric($id) ) return false;
        $where['LanguageID'] = intval($id);
        if( isset($p['IsEnable']) ) {
            $where['IsEnable'] = intval($p['IsEnable']);
        }
        $result = $this->where($where)->find();
        return $result;
    }

    /**
     * 用户名称是否存在
     */
    function nameExist($name, $fieldName, $LanguageID = 0){
        $map = array('LanguageName'=>1, 'LanguageMark'=>1);
        if(!isset($map[$fieldName])) return false;
        $where[$fieldName] = $name;
        //对于修改必须排除本身
        if($LanguageID > 0){
            $where['LanguageID'] = array('neq', (int)$LanguageID);
        }
        $id = $this->where($where)->getField('LanguageID');
        if($id > 0){
            return true;
        }else{
            return false;
        }
    }

    function setDefault($LanguageID){
        $LanguageID = intval($LanguageID);
        $where = "LanguageID != {$LanguageID}";
        $this->where($where)->setField('IsDefault', 0);
    }

    function getLanguageConfig(){
        $config = array();
        $config['LANG_LIST'] = array();
        $config['DEFAULT_LANG'] = 'cn'; //默认语言
        $this->where("IsEnable=1")->order('LanguageOrder ASC,LanguageID ASC');
        $field = "LanguageID,LanguageName,LanguageMark,IsDefault,LanguageDomain";
        $data = $this->field($field)->select();
        if(!is_array($data)) $data = array();
        //是否启用多语言
        $config['LANG_AUTO_DETECT'] = count($data)>1 ? 1: 0;
        foreach($data as $v){
            $mark = $v['LanguageMark'];
            if($v['IsDefault'] == 1){ //何止默认语言
                $config['DEFAULT_LANG'] = $mark;
            }
            unset($v['IsDefault']);
            $config['LANG_LIST'][$mark] = $v;
        }
        return $config;
    }

    /**
     * 更新语言配置项
     */
    function updateLanguageConfig(){
        //标准语言项数据
        $m = D('Admin/Config');
        $field = "ConfigID,ConfigName,ConfigValue,ConfigFile,IsEnable";
        $data = $m->where("LanguageID=1")->field($field)->select();
        if(empty($data)) return;
        //获取所有语言（不含标准的1和2）
        $languages = $this->where("LanguageID>2")->field('LanguageID,LanguageName')->select();
        if(empty($languages)) return;
        $n = 0;
        foreach($data as $v){
            $key = $v['ConfigName'];
            foreach($languages as $x){
                $LanguageID = (int)$x['LanguageID'];
                $minConfigID = $LanguageID * 1000;  //仅更新新标准语言ID
                $maxConfigID = $minConfigID + 1000;
                $where = "LanguageID={$LanguageID} AND ConfigID>={$minConfigID} AND ConfigID<{$maxConfigID}";
                $map = $m->where($where)->getField('ConfigName,ConfigID');
                if(empty($map) || isset($map[$key])) continue;
                //增加项
                $v['ConfigID'] = $minConfigID + (int)$v['ConfigID'];
                $v['LanguageID'] = $LanguageID;
                $result = $m->add($v);
                if($result>0) $n++;
            }
        }
        return $n;
    }
}
