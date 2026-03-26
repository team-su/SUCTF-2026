<?php
class AppActiveModel extends Model{
	function getActiveStat($offset = -1, $length = -1, $p = array()){
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
        $where = array();
        $p['StartTime'] = YdInput::checkDatetime($p['StartTime']);
        $p['EndTime'] = YdInput::checkDatetime($p['EndTime']);
		if( !empty($p['StartTime']) && !empty($p['EndTime']) ){			 
			 $where['Time'] = array( array('egt', $p['StartTime']),array('elt', $p['EndTime']) );
		}
		
		$result = $this->where($where)->order('Time desc')->select();
		return $result;
	}
	
	function getAppActiveCount($p = array()){
        $where = array();
        $p['StartTime'] = YdInput::checkDatetime($p['StartTime']);
        $p['EndTime'] = YdInput::checkDatetime($p['EndTime']);
		if( !empty($p['StartTime']) && !empty($p['EndTime']) ){
			 $where['Time'] = array( array('egt', $p['StartTime']),array('elt', $p['EndTime']) );
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	function statActiveByDay($Year, $Month){
		$where = " YEAR(StartTime)=".intval($Year)." and  MONTH(StartTime)=".intval($Month);
		$this->where($where)->group("DAY(StartTime)");
		$data = $this->getField('DAY(StartTime) as Day,COUNT(AppActiveID) as Count');
		if(date('Y') == $Year && date('m') == $Month){
			$days = date('j');
		}else{
			if($Month<10){
				$days = date('t',strtotime($Year.'0'.$Month.'01'));
			}else{
				$days = date('t',strtotime($Year.$Month.'01'));
			}
		}
		if(!empty($data)){
			for($i = 1; $i <= $days; $i++){
				if( !array_key_exists($i, $data) ){
					$data[$i] = 0;
				}
			}
			ksort($data, SORT_NUMERIC);
		}else{
			for($i = 1; $i <= $days; $i++){
				$data[$i] = 0;
			}
		}
		return $data;
	}
	
	function  statActiveByMonth( $Year ){
		$where = " YEAR(StartTime)=".intval($Year);
		$this->table($this->tablePrefix.'app_active');
		$this->group("MONTH(StartTime)");
		$data = $this->where($where)->getField('MONTH(StartTime) as Month,COUNT(AppActiveID) as Count');
		return $data;
	}
	
	function statActiveByYearMonth(){
		$this->field(' YEAR(StartTime)  as  Year,COUNT(AppActiveID) as Count');
		$this->table($this->tablePrefix.'app_active');
		$this->group(" YEAR(StartTime) ");
		$data = $this->select();
	
		if( empty($data) ) return false;
		$result = array();
		$n = is_array($data) ? count($data) : 0;
		for($i = 0; $i < $n; $i++){
			$result[$i]["seriesname"] = "{$data[$i]['Year']}年 总计：{$data[$i]['Count']}";
			$month = $this->statActiveByMonth( $data[$i]['Year'] );
			for($j = 1; $j<=12 ; $j++){
				$result[$i]['data'][] = array('value'=>isset( $month[$j] ) ? $month[$j] : 0);
			}
		}
		unset($data);
		return $result;
	}
}