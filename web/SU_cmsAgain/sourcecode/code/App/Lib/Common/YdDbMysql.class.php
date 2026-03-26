<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
if (!defined('APP_NAME')) exit();
class YdDbMysql {
	var $querynum = 0;
	var $link;
	var $histories;
	var $time;
	var $tablepre;

	function connect($dbhost, $dbuser, $dbpw, $dbname = '', $dbcharset='utf8', $pconnect = 0, $tablepre = '', $time = 0) {
		$this->time = $time;
		$this->tablepre = $tablepre;
		
		if (!$this->link = mysqli_connect($dbhost, $dbuser, $dbpw)) {
			$this->halt('Can not connect to MySQL server！'.mysqli_connect_error());
		}

		if ($this->version() > '4.1') {
			if ($dbcharset) {
				mysqli_query($this->link, "SET character_set_connection=" . $dbcharset . ", character_set_results=" . $dbcharset . ", character_set_client=binary");
			}

			if ($this->version() > '5.0.1') {
				mysqli_query($this->link, "SET sql_mode=''");
			}
		}

		if ($dbname) {
			mysqli_select_db($this->link, $dbname);
		}
	}

	function fetch_array($query, $result_type = MYSQLI_ASSOC) {
		return mysqli_fetch_array($query, $result_type);
	}

	function result_first($sql, &$data) {
		$query = $this->query($sql);
		$data = $this->result($query, 0);
	}

	function fetch_first($sql, &$arr) {
		$query = $this->query($sql);
		$arr = $this->fetch_array($query);
	}

	function fetch_all($sql, &$arr) {
		$query = $this->query($sql);
		while ($data = $this->fetch_array($query)) {
			$arr[] = $data;
		}
	}

	function cache_gc() {
		$this->query("DELETE FROM {$this->tablepre}sqlcaches WHERE expiry<$this->time");
	}

	function query($sql, $type = '', $cachetime = FALSE) {
		if (!($query = mysqli_query($this->link, $sql)) && $type != 'SILENT') {
			$this->halt('MySQL Query Error', $sql);
		}
		$this->querynum++;
		$this->histories[] = $sql;
		return $query;
	}

	function affected_rows() {
		return mysqli_affected_rows($this->link);
	}

	function error() {
		return (($this->link) ? mysqli_error($this->link) : mysqli_error());
	}

	function errno() {
		return intval(($this->link) ? mysqli_errno($this->link) : mysqli_errno());
	}

	function result($query, $row, $field=0) {
		//在PHP没有mysql_result这个函数了
		//$query = @mysql_result($query, $row);
		//return $query;
		
		mysqli_data_seek($query, $row);
		$type = is_numeric($field) ? MYSQLI_NUM : MYSQLI_ASSOC;
		$row = mysqli_fetch_array($query, $type);
		return isset($row[$field]) ? $row[$field] : '';
	}

	function num_rows($query) {
		$query = mysqli_num_rows($query);
		return $query;
	}

	function num_fields($query) {
		return mysqli_num_fields($query);
	}

	function free_result($query) {
		return mysqli_free_result($query);
	}

	function insert_id() {
		return ($id = mysqli_insert_id($this->link)) >= 0 ? $id : $this->result($this->query("SELECT last_insert_id()"), 0);
	}

	function fetch_row($query) {
		$query = mysqli_fetch_row($this->link, $query);
		return $query;
	}

	function fetch_fields($query) {
		return mysqli_fetch_field($query);
	}

	function version() {
		return mysqli_get_server_info($this->link);
	}

	function close() {
		return mysqli_close($this->link);
	}

	function halt($message = '', $sql = '') {
		exit('<br/>提示：数据库错误<br/>SQL语句：' . $sql . '<br/>错误关键字：' . mysqli_error($this->link));
	}

}