<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class JobModel extends Model{
	protected $_validate = array(
			array('JobName', 'require', '职位名称不能为空!'),
			array('JobName', '', '职位名称已经存在!', '0', 'lang_unique'),
			array('JobOrder', '/^[-]?\d+$/', '排序必须为数字!', '2','regex'),
	);
	
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getJob($offset = -1, $length = -1, $IsEnable = -1, $JobClassID=-1){
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$where = get_language_where();
		if( $IsEnable != -1){
			$where .= " and IsEnable=".intval($IsEnable);
		}
        if( $JobClassID > 0){
            $where .= " and JobClassID=".intval($JobClassID);
        }
		$result = $this->where($where)->order('JobOrder asc, JobID desc')->select();
		$this->_getJobClass($result);
		return $result;
	}

	private function _getJobClass(&$data){
	    $m = D('Admin/JobClass');
	    $map = $m->getField('JobClassID,JobClassName');
	    if(!is_array($map)) $map=array();
	    foreach($data as $k=>$v){
	        $key = $v['JobClassID'];
	        $data[$k]['JobClassName'] = isset($map[$key]) ? $map[$key] : '';
	    }
    }

    function getJobCount($JobClassID=-1){
        $JobClassID = intval($JobClassID);
        $where = get_language_where();
        if($JobClassID > 0){
            $where .= " and JobClassID={$JobClassID}";
        }
        $n = $this->where($where)->count();
        return $n;
    }

	function batchDelJob( $id = array() ){
		$id = YdInput::filterCommaNum($id);
		$where['JobID']  = array('in', implode(',', $id));
		$result = $this->where($where)->delete();
		return $result;
	}
	
	//批量排序
	function batchSortJob($JobID=array(), $JobOrder = array() ){
		$n = count($JobID);
		for($i = 0; $i < $n; $i++){
			if( is_numeric($JobOrder[$i]) ){
				$id = intval($JobID[$i]);
				$this->where("JobID={$id}")->setField('JobOrder', $JobOrder[$i]);
			}
		}
	}
	
	function hasData($JobID){
		$m = D('Admin/Resume');
		$c = $m->getResumeCount( $JobID );
		if( $c > 0){
			return true;
		}else{
			return false;
		}
	}

}
