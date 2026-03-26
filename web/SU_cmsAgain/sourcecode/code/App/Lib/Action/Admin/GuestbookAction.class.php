<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class GuestbookAction extends AdminBaseAction {
	function index(){
		header("Content-Type:text/html; charset=utf-8");
        $SearchWords = !empty($_REQUEST['SearchWords']) ? YdInput::checkKeyword( $_REQUEST['SearchWords'] ) : '';
        $IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck'])  ? (int)$_REQUEST['IsCheck'] : -1;

		$m = D('Admin/Guestbook');
		import("ORG.Util.Page");
		$TotalPage = $m->getMessageCount($IsCheck, -1, $SearchWords);
		$PageSize = $this->AdminPageSize;
		
		$Page = new Page($TotalPage, $PageSize);
		$Page->rollPage = $this->AdminRollPage;
        $Page->parameter = "&IsCheck={$IsCheck}&SearchWords={$SearchWords}";
		$ShowPage = $Page->show();
		
		$Message= $m->getMessage($Page->firstRow, $Page->listRows, $IsCheck, -1, $SearchWords);
		getAllInfo($Message, 6); //合成数据

        $this->assign('SearchWords', $SearchWords);
        $this->assign('IsCheck', $IsCheck);
		$this->assign('NowPage', $Page->getNowPage()); 
		$this->assign('Message', $Message);
		$this->assign('AdminPageSize', $PageSize);
		$this->assign('Page', $ShowPage); //分页条
		$this->display();
	}
	
	function delMessage(){
		header("Content-Type:text/html; charset=utf-8");
		$MessageID = (int)$_GET["MessageID"];
		$p = (int)$_GET["p"];
        $SearchWords = !empty($_REQUEST['SearchWords']) ? YdInput::checkKeyword( $_REQUEST['SearchWords'] ) : '';
        $IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck'])  ? (int)$_REQUEST['IsCheck'] : -1;  //不能使用empty判断，0也是空
		if( is_numeric($MessageID) && is_numeric($p)){
			D('Admin/Guestbook')->delete($MessageID);
			WriteLog("ID:$MessageID");
		}
		redirect(__URL__."/index/p/$p/IsCheck/$IsCheck/SearchWords/$SearchWords");
	}
	
	function batchDelMessage(){
		$id = $_POST['MessageID'];
        $SearchWords = !empty($_REQUEST['SearchWords']) ? YdInput::checkKeyword( $_REQUEST['SearchWords'] ) : '';
        $IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck'])  ? (int)$_REQUEST['IsCheck'] : -1;  //不能使用empty判断，0也是空
		$NowPage = (int)$_POST["NowPage"];
		if( count($id) > 0 ){
			D('Admin/Guestbook')->batchDelMessage($id);
			WriteLog("ID:".implode(',', $id));
		}
		redirect(__URL__."/index/p/$NowPage/IsCheck/$IsCheck/SearchWords/$SearchWords");
	}
	
	function batchCheckMessage(){
		$id = $_POST['MessageID'];
        $SearchWords = !empty($_REQUEST['SearchWords']) ? YdInput::checkKeyword( $_REQUEST['SearchWords'] ) : '';
        $IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck'])  ? (int)$_REQUEST['IsCheck'] : -1;  //不能使用empty判断，0也是空
		$NowPage = (int)$_POST["NowPage"];
		$Check = (int)$_GET['Check'];  //审核值
		if( count($id) > 0 ){
		    $m = D('Admin/Guestbook');
            $m->batchCheckMessage( $id , $Check);
			WriteLog("ID:".implode(',', $id));
		}
		redirect(__URL__."/index/p/$NowPage/IsCheck/$IsCheck/SearchWords/$SearchWords");
	}
	
	function exportMessage(){
		$csvName = date('Y-m-d_H_i').'.csv';  //导出文件名称

		//获取字段 start===================================
		$ChannelModelID = 6;
		$a = D('Admin/Attribute');
		$field = $a->getAttribute($ChannelModelID);
		foreach ($field as $f){
			$name = explode(',', $f['DisplayName']);
			$colName[ $f['FieldName'] ] = $name[0];
		}
		$colName[ 'MessageTime' ] = '时间';
		//获取字段 start===================================
        $str = "";
		foreach ($colName as $k=>$v){
			$v = filter_csv_content($v);
			$str.= "$v,";
		}
		$str = substr($str, 0, strlen($str)-1);
		$str= "$str".PHP_EOL;
		
		$m = D('Admin/Guestbook');
		//只导出满足条件的
        $SearchWords = !empty($_REQUEST['SearchWords']) ? YdInput::checkKeyword( $_REQUEST['SearchWords'] ) : '';
        $IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck'])  ? (int)$_REQUEST['IsCheck'] : -1;  //不能使用empty判断，0也是空
		$data= $m->getMessage(-1, -1, $IsCheck, -1, $SearchWords);
		//注意导入和导出的过程中，因为我们使用的是统一UTF-8编码，
		//遇到中文字符一定要记得转码，否则可能会出现中文乱码的情况。
		foreach($data as $d){
			$temp = "";
			foreach ($colName as $k=>$v){
				$d[$k] = filter_csv_content($d[$k]);
				$temp.= $d[$k].',';
			}
			$temp = substr($temp, 0, strlen($temp)-1);
			$str .= $temp.PHP_EOL;
		}
		$str= iconv('utf-8', 'gb2312//IGNORE', $str);
		WriteLog();
		yd_download_csv($csvName, $str); //下载csv
		
	}

	function answerMessage(){
		$id = $_POST['dlgMessageID'];
		$dlgAnswerContent = strip_tags($_POST['dlgAnswerContent']);
		$NowPage = (int)$_POST["NowPage"];
        $SearchWords = !empty($_REQUEST['SearchWords']) ? YdInput::checkKeyword( $_REQUEST['SearchWords'] ) : '';
        $IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck'])  ? (int)$_REQUEST['IsCheck'] : -1;  //不能使用empty判断，0也是空
		if(  is_numeric($id) ){
			D('Admin/Guestbook')->answerMessage( $id , $dlgAnswerContent);
			WriteLog("ID:$id");
		}
		redirect(__URL__."/index/p/$NowPage/IsCheck/$IsCheck/SearchWords/$SearchWords");
	}
}