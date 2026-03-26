<?php
class AppStatModel extends Model{
	function getAppStat($offset = -1, $length = -1, $p = array()){
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}

        $where = array();
		if( !empty($p['Platform']) ){
			$where['Platform'] = YdInput::checkLetterNumber($p["Platform"]);
		}

        $p['StartTime'] = YdInput::checkDatetime($p['StartTime']);
        $p['EndTime'] = YdInput::checkDatetime($p['EndTime']);
		if( !empty($p['StartTime']) && !empty($p['EndTime']) ){			 
			 $where['Time'] = array( array('egt', $p['StartTime']),array('elt', $p['EndTime']) );
		}
		
		$result = $this->where($where)->order('Time desc')->select();
		return $result;
	}
	
	function getAppStatCount($p = array()){
        $where = array();
		if( !empty($p['Platform']) ){
			$where['Platform'] = YdInput::checkLetterNumber($p["Platform"]);
		}

        $p['StartTime'] = YdInput::checkDatetime($p['StartTime']);
        $p['EndTime'] = YdInput::checkDatetime($p['EndTime']);
		if( !empty($p['StartTime']) && !empty($p['EndTime']) ){			 
			 $where['Time'] = array( array('egt', $p['StartTime']),array('elt', $p['EndTime']) );
		}
		
		$n = $this->where($where)->count();
		return $n;
	}
}