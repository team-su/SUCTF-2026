<?php

class WxQuestionModel extends Model{
	function getQuestion($AppID, $IsEnable=-1){
		$AppID = intval($AppID);
		$where = "AppID=$AppID";
		if( $IsEnable != -1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable=$IsEnable";
		}
		$data = $this->where($where)->order('QuestionOrder asc, QuestionID desc')->select();
		return $data;
	}
	
	function getQuestionCount($AppID, $IsEnable=-1){
		$AppID = intval($AppID);
		$where = "AppID={$AppID}";
		if( $IsEnable != -1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable={$IsEnable}";
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	function getQuestionEx($offset = -1, $length = -1, $AppID=0, $IsEnable=-1){
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$AppID = intval($AppID);
		$where = "AppID=$AppID";
		if( $IsEnable != -1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable={$IsEnable}";
		}
		$data = $this->where($where)->order('QuestionOrder asc, QuestionID desc')->select();
		$n = is_array($data) ? count($data) : 0;
		for($i=0; $i < $n; $i++){
			$item = explode('@@@', $data[$i]['QuestionItem']);
			foreach ($item as $it){
				$tt = (array)explode('###', $it);
				$data[$i]['Item'][] = array('ItemID'=>$tt[0],'ItemName'=>$tt[1]);
			}
		}
		return $data;
	}
	
	function findQuestion($QuestionID, $IsEnable=-1){
		$QuestionID = intval($QuestionID);
		$where = "QuestionID=$QuestionID";
		if($IsEnable!=-1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable={$IsEnable}";
		}
		$p = $this->where($where)->find();
		//1###111@@@2###223@@@3###333
		$item = explode('@@@', $p['QuestionItem']);
		foreach ($item as $it){
			$tt = (array)explode('###', $it);
			$p['Item'][] = array(
					'ItemID'=>$tt[0],
					'ItemName'=>$tt[1]
			);
		}
		return $p;
	}
	
	function batchSortQuestion($id=array(), $order = array() ){
		$n = count($id);
		for($i = 0; $i < $n; $i++){
			if( is_numeric($order[$i]) ){
				$myid = intval($id[$i]);
				$this->where("QuestionID={$myid}")->setField('QuestionOrder', $order[$i]);
			}
		}
	}
	
	function batchDelQuestion( $id = array(), $del=false){
		$id = YdInput::filterCommaNum($id);
		$where = 'QuestionID in('.implode(',', $id).')';
		$result = $this->where($where)->delete();
		if($del){
			$m = D('Admin/WxResearch');
			$m->delResearch(false, $id);
		}
		return $result;
	}
	
	function delQuestion($id, $del=false){
		$id = YdInput::filterCommaNum($id);
		if( is_array($id)){
			$where = 'QuestionID in('.implode(',', $id).')';
		}else{
			$where = "QuestionID=$id";
		}
		$n = $this->where($where)->delete();
		if($del){
			$m = D('Admin/WxResearch');
			$m->delResearch(false, $id);
		}
		return $n;
	}
}
