<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class InfoAction extends AdminBaseAction {
	/**
	 * 信息列表显示首页
	 */
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$ChannelID = (int)$_REQUEST['ChannelID'];
		if( !is_numeric($ChannelID)) return;
		$gid = intval(session('AdminGroupID'));
		
		$IsEnable = isset($_REQUEST['IsEnable']) && is_numeric($_REQUEST['IsEnable']) ? (int)$_REQUEST['IsEnable'] : -1;  //值存在0，所有不能使用empty判断
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		$MemberID = isset($_REQUEST['MemberID']) && is_numeric($_REQUEST['MemberID']) ? (int)$_REQUEST['MemberID'] : -1;
		$IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck']) ? (int)$_REQUEST['IsCheck'] : -1;
	
		import("ORG.Util.Page");
		$s = D('Admin/Info');
		//$ChannelID = 0, $IsContainChild = 1, $IsEnable = -1, $Keywords='', $MemberID = -1, 
		//$SpecialID = 0, $LabelID = '', $IsCheck=-1
		$TotalPage = $s->getCount($ChannelID, 1, $IsEnable, $Keywords, $MemberID, 0, '', $IsCheck, $gid); //总页数
		$PageSize = $this->AdminPageSize;
		$Page = new Page($TotalPage, $PageSize);
		
		$Page->parameter = "&IsEnable=$IsEnable&Keywords=$Keywords&MemberID=$MemberID&IsCheck=$IsCheck";
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();

        $cm = D('Admin/Channel');
        $ChannelModelID = $cm->getChannelModelID($ChannelID);

		//参数：$FirstRow, $ListRow, $ChannelID = 0, $IsContainChild = 1, $IsEnable = -1, $LabelID = '', 
		//$Keywords='', $MemberID = -1, $SpecialID = 0, $IsCheck=-1
		$Info = $s->getInfo($Page->firstRow, $Page->listRows, $ChannelID, 1, $IsEnable, '', $Keywords, $MemberID, 0, $IsCheck, false, $gid);
		if(!empty($Info)){
			$n = count($Info);
			$timeStamp = time();
			for($i=0;$i<$n;$i++){
				$InfoUrl = InfoUrl($Info[$i]['InfoID'], $Info[$i]['Html'], $Info[$i]['LinkUrl'], false, $Info[$i]['ChannelID']);
				$Info[$i]['InfoUrl'] = "{$InfoUrl}?preview=1";
				$timeDiff = $timeStamp - strtotime($Info[$i]['InfoTime']);
				$Info[$i]['IsTime'] = ($timeDiff<0) ? 1 : 0;
                $Info[$i]['LabelHtml'] =  $this->_getLabelHtml($Info[$i]['LabelID'], $ChannelModelID);
			}
		}

		//启用多语言的前提下才显示翻译
		$hasMultiLanguage = C('LANG_AUTO_DETECT');
		if($hasMultiLanguage){
            $IsInstall = appIsInstall(168);
        }else{
            $IsInstall = false;
        }
        $this->assign('IsInstall', $IsInstall?1:0); //当前频道

		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Page', $ShowPage); //分页条
		$this->assign('ChannelModelID', $ChannelModelID); //当前频道
		$this->assign('ChannelID', $ChannelID); //当前频道
		$this->assign('IsEnable', $IsEnable); //当前频道
		$this->assign('Keywords', $Keywords); //当前频道
		$this->assign('MemberID', $MemberID==-1 ? '' : $MemberID); //当前频道
		$this->assign('AdminGroupID', $gid); //当前频道
		$this->assign('IsCheck', $IsCheck); //当前频道
		$this->assign('Info', $Info);
		$this->display();
	}

    private function _getLabelHtml($LabelID, $ChannelModelID){
	    if(empty($LabelID)) return '';
        static $map = false;
        if(false === $map){
            $m = D('Label');
            $where['ChannelModelID'] = (int)$ChannelModelID;
            $data = $m->where($where)->field('LabelID,LabelName')->select();
            $colorMap = array('red', 'green', 'blue', 'purple');
            $map = array(); //防止8.0报notice
            foreach($data as $k=>$v){
                $key = $v['LabelID'];
                $color = $colorMap[$k%4];
                $map[$key] = " <b style='color:{$color};'> [{$v['LabelName']}] </b> ";
            }
        }
        if(empty($map)) return '';
        $data = explode(',', $LabelID);
        $html = '';
        foreach($data as $id){
            $html .= isset($map[$id]) ? $map[$id] : '';
        }
        return $html;
    }

	//反馈模型
	function feedback(){
		header("Content-Type:text/html; charset=utf-8");
		$IsEnable = -1;
		$MemberID = isset($_REQUEST['MemberID']) && is_numeric($_REQUEST['MemberID']) ? (int)$_REQUEST['MemberID'] : -1;
		$IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck']) ? (int)$_REQUEST['IsCheck'] : -1;
		$ChannelID = (int)$_REQUEST['ChannelID'];
		$Keywords = isset($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		
		import("ORG.Util.Page");
		$s = D('Admin/Info');
		//$ChannelID = 0, $IsContainChild = 1, $IsEnable = -1, $Keywords='', $MemberID = -1,
		//$SpecialID = 0, $LabelID = '', $IsCheck=-1
		$TotalPage = $s->getCount($ChannelID, 1, $IsEnable, $Keywords, $MemberID, 0, '', $IsCheck); //总页数
		$PageSize = $this->AdminPageSize;
		$Page = new Page($TotalPage, $PageSize);
		
		$Page->parameter = "&IsCheck=$IsCheck&Keywords=$Keywords&MemberID=$MemberID";
		
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		
		//参数：$FirstRow, $ListRow, $ChannelID = 0, $IsContainChild = 1, $IsEnable = -1, $LabelID = '',
		//$Keywords='', $MemberID = -1, $SpecialID = 0, $IsCheck=-1
		$Info = $s->getInfo($Page->firstRow, $Page->listRows, $ChannelID, 1, $IsEnable, '', $Keywords, $MemberID, 0, $IsCheck);
		
		$ChannelModelID = 37; //反馈模型ID为37
		//=====================================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute( $ChannelModelID );
		$Group = $m->getGroup( $ChannelModelID );
		//$n1 = count($Group);
		//$n2 = count($Attribute);
		$f = array();
		foreach($Group as $g){
			foreach($Attribute as $a){
				if( $a['GroupID'] == $g['AttributeID'] ){
					$f[] = array( 'FieldName'=>$a['FieldName'], 'DisplayName'=>$a['DisplayName'] );
				}
			}
		}
		unset($Attribute, $Group);
		//=====================================================
		$n3 = is_array($Info) ? count($Info) : 0;
		for($i = 0; $i<$n3; $i++){
            $Info[$i]['AllInfo'] = '';
			foreach($f as $v){
			    $value = isset($Info[$i][$v['FieldName']]) ? $Info[$i][$v['FieldName']] : '';
				if( strlen($value) > 0){  //不能使用empty，因为值可能为0
                    $value = htmlspecialchars($value);
					$Info[$i]['AllInfo'] .= "<b>{$v['DisplayName']}：</b>{$value}<br/>";
				}
			}
		}
		//2024-05-30
		$this->assign('Group', ''); //bug:会覆盖BaseAction下的{$Group}
		$this->assign('Attribute', '');

		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Page', $ShowPage); //分页条
		$this->assign('ChannelModelID', $ChannelModelID); //当前频道
		$this->assign('ChannelID', $ChannelID); //当前频道
		$this->assign('Keywords', $Keywords); 
		$this->assign('IsCheck', $IsCheck); //当前频道
		$this->assign('AdminGroupID', intval(session('AdminGroupID'))); //当前频道
		$this->assign('MemberID', $MemberID==-1 ? '' : $MemberID); //当前频道
		$this->assign('Info', $Info);
		$this->display();
	}
	
	/**
	 * 删除反馈
	 */
	function delFeedback(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Info');
		$InfoID = (int)$_GET["InfoID"];
		$ChannelID = (int)$_GET["ChannelID"];
		$p = $_GET["p"];
	
		//有问题==================================================================
		$Keywords = isset($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		$IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck']) ? (int)$_REQUEST['IsCheck'] : -1;
		$MemberID = isset($_REQUEST['MemberID']) && is_numeric($_REQUEST['MemberID']) ? (int)$_REQUEST['MemberID'] : -1;
		$parameter ="?IsCheck=$IsCheck";
		if( $Keywords != ''){
			$parameter .= "&Keywords=$Keywords";
		}
		if($MemberID != -1){
			$parameter .= "&MemberID=$MemberID";
		}
		$parameter .= "&p=$p";
		//======================================================================
	
		if( $this->hasInfoPurview($InfoID) && is_numeric($InfoID) && is_numeric($ChannelID) && is_numeric($p)){
			$m->delete($InfoID);
			WriteLog("ID:$InfoID", array('LogType'=>3,'UserAction'=>'删除信息') );
		}
		redirect(__URL__."/feedback/ChannelID/$ChannelID".$parameter);
	}
	
	//批量删除反馈
	function batchDelFeedback(){
		$id = $_POST['InfoID'];
		$id = $this->checkIDPurview( $id );
		$ChannelID = (int)$_POST["ChannelID"];
		$NowPage = (int)$_POST["NowPage"];
		$Keywords = isset($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		$IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck']) ? (int)$_REQUEST['IsCheck'] : -1;
		$MemberID = isset($_REQUEST['MemberID']) && is_numeric($_REQUEST['MemberID']) ? (int)$_REQUEST['MemberID'] : -1;
		$parameter = "?IsCheck=$IsCheck";
		if( $Keywords != ''){
			$parameter .= "&Keywords=$Keywords";
		}
		if($MemberID != -1){
			$parameter .= "&MemberID=$MemberID";
		}
		$parameter .= "&p=$NowPage";
		if( count($id) > 0 ){
			$m = D('Admin/Info');
			$m->batchDelInfo($id);
			WriteLog("ID:".implode(',', $id), array('LogType'=>3,'UserAction'=>'批量删除信息'));
		}
		redirect(__URL__."/feedback/ChannelID/$ChannelID".$parameter);
	}
	
	//批量审核
	function batchCheckFeedback(){
		$id = $_POST['InfoID'];
		$id = $this->checkIDPurview( $id );
		$ChannelID = (int)$_POST["ChannelID"];
		$NowPage = (int)$_POST["NowPage"];
		$Check = (int)$_GET['Check'];  //审核值

		$MemberID = isset($_REQUEST['MemberID']) && is_numeric($_REQUEST['MemberID']) ? (int)$_REQUEST['MemberID'] : -1;
		$IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck']) ? (int)$_REQUEST['IsCheck'] : -1;
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		$parameter = "?Keywords=$Keywords&p=$NowPage&MemberID=$MemberID&IsCheck=$IsCheck";
	
		if( count($id) > 0 ){
            $m = D('Admin/Info');
            $m->batchCheck( $id , $Check);
			$p['UserAction'] = $Check==1 ? '批量审核信息' : '批量取消审核信息';
			$p['LogType'] = 1;
			WriteLog("ID:".implode(',', $id), $p);
		}
	
		redirect(__URL__."/feedback/ChannelID/$ChannelID".$parameter);
	}
	
	/**
	 * 删除信息
	 */
	function del(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Info');
		$InfoID = (int)$_GET["InfoID"];
		$ChannelID = (int)$_GET["ChannelID"];
		$p = (int)$_GET["p"];
		
		//有问题==================================================================
		$IsEnable = isset($_REQUEST['IsEnable']) && is_numeric($_REQUEST['IsEnable']) ? (int)$_REQUEST['IsEnable'] : -1; //值存在0，所有不能使用empty判断
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		$MemberID = isset($_REQUEST['MemberID']) && is_numeric($_REQUEST['MemberID']) ? (int)$_REQUEST['MemberID'] : -1;
		$IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck']) ? (int)$_REQUEST['IsCheck'] : -1;
		$parameter = "?IsEnable=$IsEnable&Keywords=$Keywords&MemberID=$MemberID&IsCheck=$IsCheck&p=$p";
		//======================================================================
	
		if( $this->hasInfoPurview($InfoID) && is_numeric($InfoID) && is_numeric($ChannelID) && is_numeric($p)){
			$fileToDel = $m->getAttachment($InfoID);
			$m->delete($InfoID);
			batchDelFile($fileToDel);
            $this->_deleteInfoAttributeValue($InfoID);
			WriteLog( "ID:$InfoID", array('LogType'=>3,'UserAction'=>'删除信息'));
		}
		redirect(__URL__."/index/ChannelID/$ChannelID".$parameter);
	}
	
	//是否有操作当前信息的权限
	private function hasInfoPurview($infoid){
		$gid = intval(session('AdminGroupID'));
		if( $gid == 1 ) return true;
		//获取信息所属频道
		$m = D('Admin/Info');
		$where['InfoID'] = intval($infoid);
		$channelid = $m->where($where)->getField('ChannelID');
		$b = $this->hasChannelPurview( $channelid );
		return $b;
	}
	
	private function hasChannelPurview($channelid){
		$gid = intval(session('AdminGroupID'));
		if( $gid == 1 ) return true;
		$m = D('Admin/AdminGroup');
		$list = $m->getChannelPurview( $gid );
	
		$list = explode(',', $list);
		if( in_array($channelid, $list) ){
			return true;
		}else{
			return false;
		}
	}
	
	//批量检查id，并删除无权限的ID
	private function checkIDPurview( $id=array() ){
		$gid = intval(session('AdminGroupID'));
		if( $gid == 1 ) return $id;
		$goodid = array();
		$m1 = D('Admin/AdminGroup');
		$list = $m1->getChannelPurview( $gid );
		$list = explode(',', $list);
	
		//获取信息所属频道
		$m2 = D('Admin/Info');
		foreach ($id as $k=>$v){
			$channelid = $m2->where("InfoID=".intval($v))->getField('ChannelID');
			if( in_array($channelid, $list) ){
				$goodid[] = $v;
			}
		}
		return $goodid;
	}
	
	//批量删除信息
	function batchDel(){
		$id = $_POST['InfoID'];
		$id = $this->checkIDPurview( $id );
		$ChannelID = (int)$_POST["ChannelID"];
		$NowPage = (int)$_POST["NowPage"];
		
		$IsEnable = isset($_REQUEST['IsEnable']) && is_numeric($_REQUEST['IsEnable']) ? (int)$_REQUEST['IsEnable'] : -1; //值存在0，所有不能使用empty判断
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		$MemberID = isset($_REQUEST['MemberID']) && is_numeric($_REQUEST['MemberID']) ? (int)$_REQUEST['MemberID'] : -1;
		$IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck']) ? (int)$_REQUEST['IsCheck'] : -1;
		$parameter = "?IsEnable=$IsEnable&Keywords=$Keywords&MemberID=$MemberID&IsCheck=$IsCheck&p=$NowPage";
		
		if( count($id) > 0 ){
			$m = D('Admin/Info');
			$fileToDel = $m->getAttachment($id);
			$m->batchDelInfo($id);
			batchDelFile($fileToDel);
			$this->_deleteInfoAttributeValue($id);
			WriteLog("ID:".implode(',', $id), array('LogType'=>3,'UserAction'=>'批量删除信息'));
		}
		redirect(__URL__."/index/ChannelID/$ChannelID".$parameter);
	}

    /**
     * 删除信息的同时删除相关属性
     */
	private function _deleteInfoAttributeValue($id){
	    if(empty($id)) return false;
	    $m = D('TypeAttributeValue');
	    if(is_array($id)){
            $id = YdInput::filterCommaNum($id);
            if(empty($id)) return false;
            $where['InfoID'] = array('IN', $id);
        }else{
            $where['InfoID'] = (int)$id;
        }
        $result = $m->where($where)->delete();
	    return $result;
    }
	
	function batchMove(){
		$id = $_POST['InfoID'];  //支持多个
		$id = $this->checkIDPurview( $id );
		$NowChannelID = (int)$_POST["ChannelID"];
		$NowPage = (int)$_POST["NowPage"]; //当前页
	
		$ChannelID = (int)$_POST["cid"]; //目标频道
		$SpecialID = isset($_POST["sid"]) ? $_POST["sid"] : 0; //目标专题（支持多个）
		
		//查询参数=================================================================
		$IsEnable = isset($_REQUEST['IsEnable']) && is_numeric($_REQUEST['IsEnable']) ? (int)$_REQUEST['IsEnable'] : -1; //值存在0，所有不能使用empty判断
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		$MemberID = isset($_REQUEST['MemberID']) && is_numeric($_REQUEST['MemberID']) ? (int)$_REQUEST['MemberID'] : -1;
		$IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck']) ? (int)$_REQUEST['IsCheck'] : -1;
		$parameter = "?IsEnable=$IsEnable&Keywords=$Keywords&MemberID=$MemberID&IsCheck=$IsCheck&p=$NowPage";
		//=======================================================================
		
		$url = __URL__."/index/ChannelID/$NowChannelID".$parameter;
		if( count($id) > 0 ){
			$b = channel_allow($ChannelID);
			if($b){
                $m = D('Admin/Info');
                $m->batchMoveInfo($id, $ChannelID, $SpecialID);
				WriteLog("ID:".implode(',', $id), array('LogType'=>1,'UserAction'=>'移动信息'));
			}else{
				alert('不能移动到指定目标频道!', $url);
			}
		}
		redirect( $url );
	}
	
	//批量排序信息
	function batchSort(){
		$ChannelID = (int)$_POST["ChannelID"];
		$NowPage = (int)$_POST["NowPage"];
	
		$InfoOrder = $_POST['InfoOrder']; //排序（数组）
		$InfoID = $_POST['InfoOrderID']; //排序（数组）
		
		$IsEnable = isset($_REQUEST['IsEnable']) && is_numeric($_REQUEST['IsEnable']) ? (int)$_REQUEST['IsEnable'] : -1; //值存在0，所有不能使用empty判断
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		$MemberID = isset($_REQUEST['MemberID']) && is_numeric($_REQUEST['MemberID']) ? (int)$_REQUEST['MemberID'] : -1;
		$IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck']) ? (int)$_REQUEST['IsCheck'] : -1;
		$parameter = "?IsEnable=$IsEnable&Keywords=$Keywords&MemberID=$MemberID&IsCheck=$IsCheck&p=$NowPage";

		if(is_array($InfoID) && is_array($InfoOrder) && count($InfoID) > 0 && count($InfoOrder) > 0 ){
		    $m = D('Admin/Info');
            $m->batchSortInfo($InfoID, $InfoOrder);
			WriteLog('', array('LogType'=>5,'UserAction'=>'信息排序'));
		}
		redirect(__URL__."/index/ChannelID/$ChannelID".$parameter);
	}
	
	//批量审核
	function batchCheck(){
		$id = $_POST['InfoID'];  //支持多个
		$id = $this->checkIDPurview( $id );
		$ChannelID = (int)$_POST["ChannelID"];
		$NowPage = (int)$_POST["NowPage"];
		$Check = (int)$_GET['Check'];  //审核值
		
		$IsEnable = isset($_REQUEST['IsEnable']) && is_numeric($_REQUEST['IsEnable']) ? (int)$_REQUEST['IsEnable'] : -1; //值存在0，所有不能使用empty判断
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		$MemberID = isset($_REQUEST['MemberID']) && is_numeric($_REQUEST['MemberID']) ? (int)$_REQUEST['MemberID'] : -1;
		$IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck']) ? (int)$_REQUEST['IsCheck'] : -1;
		$parameter = "?IsEnable=$IsEnable&Keywords=$Keywords&MemberID=$MemberID&IsCheck=$IsCheck&p=$NowPage";
		
		if( count($id) > 0 ){
			$m = D('Admin/Info');
			$m->batchCheck( $id , $Check);
			$options['UserAction'] = $Check==1 ? '批量审核信息' : '批量取消审核信息';
			$options['LogType'] = 1;
			WriteLog("ID:".implode(',', $id), $options);
		}		
		redirect(__URL__."/index/ChannelID/$ChannelID".$parameter);
	}
	
	//中英互译
	function translate(){
		$ChannelID = $_POST["ChannelID"];  //译文存入指定频道
		$InfoID = $_POST["InfoID"];  //待翻译的信息ID
		$TargetLanguage = $_POST["TargetLanguage"]; //目标语言
		$data['Number']=$_POST["Number"]; //当前翻译序号
		//参数检查
		if( !$this->hasInfoPurview($InfoID) || !is_numeric($ChannelID) || !is_numeric($InfoID) || !is_numeric($data['Number']) ){
			$this->ajaxReturn($data, '无效参数！' , 0);
		}
		
		//判断是否是有效频道
		$ChannelModelID = ChannelModelID($ChannelID);
		if( $ChannelModelID == 32 || $ChannelModelID == 33){
			$this->ajaxReturn($data, '目标频道不能为单页或链接频道！' , 0);
		}
		
		//获取原始数据
		$m = D('Admin/Info');
		$info = $m->find($InfoID);
        $info['LanguageID'] = get_language_id($TargetLanguage); //目标语言ID

		$SourceLanguage = get_language_mark();
		$from = TranslateLang($SourceLanguage);  //源语言
        $to = TranslateLang($TargetLanguage); //目标语言

		$result = baiduTranslate( $info['InfoTitle'], $from, $to);
		if( $result['Status'] == 0){
			//翻译信息标题失败
			$result['ErrorMessage'] = "信息{$InfoID}标题翻译失败，{$result['ErrorMessage']}！";
			$this->ajaxReturn($data, $result , 2);
		}
		
		//判断信息是否翻译过=================================
		$info['InfoTitle'] = $result['Content'];
		$where['InfoTitle'] = $info['InfoTitle'];
		$where['LanguageID'] = $info['LanguageID'];
		$n = $m->where($where)->count();
		if($n>0){
			$result['ErrorMessage'] = "信息{$InfoID}重复翻译，跳过！";
			$this->ajaxReturn($data, $result , 2);
		}
		//=============================================
		
		$list = array('infotitle', 'isenable', 'ischeck', 'languageid','ishtml','infoorder','channelid','specialid','memberid','infohit','labelid',
			'infotime', 'html','infoalbum','infoprice','infopicture','readlevel','infoattachment');
		unset( $info['InfoID'], $info['ChannelIDEx'], $info['InfoRelation']);
		$info['ChannelID'] = $ChannelID;
		foreach ($info as $k=>$v){
			if( !in_array( strtolower($k), $list)){
				$result = baiduTranslate( $v , $from, $to);
				if( $result['Status'] == 1){
					$info[ $k ] = $result['Content'];
				}else{
					//返回翻译错误日志
					$result['ErrorMessage'] = "信息{$InfoID}翻译失败，{$result['ErrorMessage']}！";
					$this->ajaxReturn($data, $result , 3);
					break;
				}
			}
		}
		//保存翻译结果======================================
		$b = $m->add($info);
		if($b){
			$this->ajaxReturn($data, '翻译完成!' , 1);
		}else{
			$result['ErrorMessage'] = "信息{$InfoID}翻译成功，但保存失败！";
			$this->ajaxReturn($data, $result , 3);
		}
		//==============================================
	}
	
	//批量设置属性
	function batchLabel(){
		$id = $_POST['InfoID'];  //支持多个
		$id = $this->checkIDPurview( $id );
		$NowChannelID = (int)$_POST["ChannelID"];
		$NowPage = (int)$_POST["NowPage"]; //当前页
	
		$LabelID = $_POST["lid"]; //目标标记
		$DlgIsEnable= $_POST["DlgIsEnable"]; //是否启用
		
		//查询参数=================================================================
		$IsEnable = isset($_REQUEST['IsEnable']) && is_numeric($_REQUEST['IsEnable']) ? (int)$_REQUEST['IsEnable'] : -1; //值存在0，所有不能使用empty判断
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		$MemberID = isset($_REQUEST['MemberID']) && is_numeric($_REQUEST['MemberID']) ? (int)$_REQUEST['MemberID'] : -1;
		$IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck']) ? (int)$_REQUEST['IsCheck'] : -1;
		$parameter = "?IsEnable=$IsEnable&Keywords=$Keywords&MemberID=$MemberID&IsCheck=$IsCheck&p=$NowPage";
		//=======================================================================
		
		if( count($id) > 0 ){
		    $m = D('Admin/Info');
			$m->batchLabel($id, $LabelID, $DlgIsEnable);
			WriteLog("ID:".implode(',', $id), array('LogType'=>1,'UserAction'=>'设置信息属性'));
		}
		redirect(__URL__."/index/ChannelID/$NowChannelID".$parameter);
	}
	
	/**
	 * 信息修改
	 */
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$InfoID = $_GET['InfoID'];
		if(isset($_GET['ChannelModelID'])){
            $ChannelModelID = $_GET['ChannelModelID'];
        }else{
            $ChannelModelID = InfoChannelModelID($InfoID);
        }
		if( !$this->hasInfoPurview($InfoID) || !is_numeric($InfoID) || !is_numeric($ChannelModelID) ){
			alert("非法参数", __URL__.'/index');
		}
		//====================================
		
		//模型属性信息=================================================
	    $gid = intval(session('AdminGroupID'));
        $Attribute = $this->_filterAttribute($ChannelModelID);
        $Group = $this->_filterGroup($ChannelModelID);
        $total = count($Attribute);
		//获取信息数据
        $m = D('Admin/Info');
		$Info = $m->find( $InfoID );
		for($n = 0; $n < $total; $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				if ( $Attribute[$n]['DisplayType'] == 'channelselect'){
					$Attribute[$n]['AdminGroupID'] = $gid;
					$Attribute[$n]['SelectedValue'] = $Info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
					$Attribute[$n]['HasSingleModel'] = true;  //是否是单页频道
					$Attribute[$n]['HasLinkModel'] = true;  //是否是链接频道
					//$Attribute[$n]['FirstValue'] = "0"; //FirstValue
					//$Attribute[$n]['FirstText'] = "所有频道"; //FirstText
				}else if ( $Attribute[$n]['DisplayType'] == 'specialselect'){
					$Attribute[$n]['ChannelID'] = $Info['ChannelID']; //保存当前频道ID
					$Attribute[$n]['SelectedValue'] = explode(',' , $Info[ $Attribute[$n]['FieldName'] ]); //获取频道设置值
				}else if($Attribute[$n]['DisplayType'] == 'labelcheckbox'){ //属性标记
					$Attribute[$n]['ChannelModelID'] = $ChannelModelID;
					$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
				}else if($Attribute[$n]['DisplayType'] == 'membergroupcheckbox'){ //会员分组checkbox
					$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
				}else if( $Attribute[$n]['DisplayType'] == 'channelexselect'){
					$Attribute[$n]['AdminGroupID'] = $gid;
					$Attribute[$n]['SelectedValue'] = explode(',' , $Info[ $Attribute[$n]['FieldName'] ]); //获取频道设置值
				}else if( false !== stripos($Attribute[$n]['DisplayType'], 'areaselect') ){
					$Attribute[$n]['ProvinceSelectedValue'] = $Info['ProvinceID'];
					$Attribute[$n]['CitySelectedValue'] = $Info['CityID'];
					$Attribute[$n]['DistrictSelectedValue'] = $Info['DistrictID'];
					$Attribute[$n]['TownSelectedValue'] = $Info['TownID'];
				}else{ //checkbox,radio
					$Attribute[$n]['SelectedValue'] = $Info[ $Attribute[$n]['FieldName'] ];
				}
			}else if( $Attribute[$n]['DisplayType']=='coordinate' ){
				$Attribute[$n]['Longitude'] = $Info['Longitude'];
				$Attribute[$n]['Latitude'] = $Info['Latitude'];
			}else{
				$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//==============================================================
        //当前选中项，用于配合装修使用
        $TabIndex = isset($_GET["TabIndex"]) ? intval($_GET["TabIndex"]) : 1;
        $this->assign('TabIndex', $TabIndex);

		$this->assign('InfoID', $InfoID);

        $this->assign('ChannelIDFrom', (int)$_GET['ChannelIDFrom']);  //点击左侧菜单的频道ID，用于修改信息时能够正确返回
		$this->assign('ChannelID', $Info['ChannelID']); //这是当前信息所在频道ID
		$this->assign('Action', __URL__.'/saveModify');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function relation(){
		header("Content-Type:text/html; charset=utf-8");
		$ChannelID = $_REQUEST['cid'];
		$InfoID = $_REQUEST['iid'];
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		import("ORG.Util.Page");
		$m = D('Admin/Info');
		//$ChannelID = 0, $IsContainChild = 1, $IsEnable = -1, $Keywords='', $MemberID = -1,
		//$SpecialID = 0, $LabelID = '', $IsCheck=-1
		$TotalPage = $m->getCount($ChannelID, 1, 1, $Keywords, -1, 0, '', 1);
		$PageSize = 10;
		$Page = new Page($TotalPage, $PageSize);
		$Page->parameter = "";
		if( $ChannelID != 0){
			$Page->parameter .= "&cid=$ChannelID";
		}
		if( $Keywords != ''){
			$Page->parameter .= "&Keywords=$Keywords";
		}
		$Page->rollPage = 10;
		$ShowPage = $Page->show();
	
		//参数：$FirstRow, $ListRow, $ChannelID = 0, $IsContainChild = 1, $IsEnable = -1, $LabelID = '',
		//$Keywords='', $MemberID = -1, $SpecialID = 0, $IsCheck=-1
		$data = $m->getInfo($Page->firstRow, $Page->listRows, $ChannelID, 1, 1, '', $Keywords, -1, 0, 1);
		
		$m1 = D('Admin/Channel');
		$AdminGroupID = intval(session('AdminGroupID'));
		$MenuOwner = (strtolower(GROUP_NAME)=='admin') ? 1 : 0;
		$Channel = $m1->getChannelPurview($MenuOwner, $AdminGroupID);
        $ChannelNew = array();
		foreach ($Channel as $v){
			$hasChild = $v['HasChild'];
			$channelModelID = $v['ChannelModelID'];
			if( $hasChild == 0 && ($channelModelID==32 || $channelModelID==33) ) continue;
			$ChannelNew[] = array(	'ChannelID' => $v['ChannelID'], 'ChannelName' => $v['ChannelName']);
		}
		unset( $Channel );
		
		$this->assign('Action', __URL__.'/relation');
		$this->assign('NowPage', $Page->getNowPage());
		$this->assign('Page', $ShowPage);
		$this->assign('PageSize', $PageSize);
		$this->assign('SearchWords', $Keywords);
		$this->assign('AdminGroupID', $AdminGroupID ); //当前频道
		$this->assign('MenuOwner', $MenuOwner ); //当前频道
		$this->assign('Relation', $data);
		$this->assign('Channel', $ChannelNew);
		$this->assign('ChannelID', $ChannelID);
		$this->assign('InfoID', $InfoID);
		$this->display();
	}
	
	/**
	 * 保存信息
	 */
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
		if( !$this->hasInfoPurview($_POST['InfoID'])){
			$this->ajaxReturn(null, '没有权限修改!' , 0);
		}
		$this->prePost($_POST);
		$c = D('Admin/Info');
		if( $c->create() ){
			YdCache::deleteInfoHtml( $_POST['InfoID'], $_POST['Html'] );
			WriteLog("ID:".$_POST['InfoID'] );
			if($GLOBALS['Config']['AUTO_UPLOAD_ENABLE']==1 && stripos($c->InfoContent , '<img') !== false ){
				//上传远程图片有，需要同步更新编辑器内容
				$temp = yd_upload_content($c->InfoContent);
				$c->InfoContent = $temp[2];
				if($c->save() === false){
					$this->ajaxReturn(null, '修改失败!' , 0);
				}else{
					//需要做是否启用自动上传远程图片判断 0:远程地址，1：本地地址
					save_info_type_attribute($_POST['InfoID'], 2);
					$this->ajaxReturn($temp[0], $temp[1] , 1);
				}
			}else{
				if($c->save() === false){
					$this->ajaxReturn(null, '修改失败!' , 0);
				}else{
				    $description = "ID:{$_POST['InfoID']} {$_POST['InfoTitle']}";
					WriteLog($description, array('LogType'=>4,'UserAction'=>'保存信息修改'));
					save_info_type_attribute($_POST['InfoID'], 2);
					$this->ajaxReturn(null, '修改成功!' , 1);
				}
			}
		}else{
			$this->ajaxReturn(null, $c->getError() , 0);
		}
	}
	
	//预处理POST变量
	private function prePost(&$p){
		//先处理相册，相册的最终代码在浏览器端使用js脚本控制
		unset($p['AlbumTitle'], $p['AlbumPicture'], $p['AlbumDescription']);
		//处理复选框显示
		foreach ($p as $k=>$v){
			if( is_array($v) && substr($k, 0, 5) != 'attr_' ){ //不处理类型属性字段
				$p[$k] = implode(',', $v);
			}
		}
		if( !isset($p['ReadLevel']) ) $p['ReadLevel'] = '';
		if( !isset($p['LabelID']) ) $p['LabelID'] = '';
		if( !isset($p['SpecialID']) ) $p['SpecialID'] = '';
		if( !isset($p['ChannelIDEx']) ) $p['ChannelIDEx'] = '';
		
		if( !isset($p['f1']) ) $p['f1'] = '';
		if( !isset($p['f2']) ) $p['f2'] = '';
		if( !isset($p['f3']) ) $p['f3'] = '';
		if( !isset($p['f4']) ) $p['f4'] = '';
		if( !isset($p['f5']) ) $p['f5'] = '';
	}
	
	/**
	 * 显示信息添加界面
	 */
	function add(){
		header("Content-Type:text/html; charset=utf-8");
		$ChannelID = $_GET['ChannelID'];
		
		if( !$this->hasChannelPurview($ChannelID) || !is_numeric($ChannelID) ){
			alert("非法参数", __URL__.'/index/ChannelID/'.$ChannelID);
		}
		$c = D('Admin/Channel');
		$ChannelModelID = $c->getChannelModelID($ChannelID);
		$ChannelName = $c->getFieldByChannelID($ChannelID, "ChannelName");
	
		//模型属性信息==============================================
        $gid = intval(session('AdminGroupID'));
        $Attribute = $this->_filterAttribute($ChannelModelID);
        $Group = $this->_filterGroup($ChannelModelID);
		$total = count($Attribute);
		for($n = 0; $n < $total; $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				if ( $Attribute[$n]['DisplayType'] == 'channelselect'){
					$Attribute[$n]['DisplayType'] = "label";
					$Attribute[$n]['DisplayValue'] = "<b style='color:blue'>$ChannelName</b>";
				}else if( $Attribute[$n]['DisplayType'] == 'specialselect'){
					$Attribute[$n]['ChannelID'] = $ChannelID; //保存当前频道ID
				}else if($Attribute[$n]['DisplayType'] == 'labelcheckbox'){ //属性标记
					$Attribute[$n]['ChannelModelID'] = $ChannelModelID;
				}else if( $Attribute[$n]['DisplayType'] == 'channelexselect'){
					$Attribute[$n]['AdminGroupID'] = $gid;
				}
			}else if($Attribute[$n]['DisplayType'] == 'datetime'){
				$Attribute[$n]['DisplayValue'] = date('Y-m-d H:i:s'); //显示当期时间
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/saveAdd');
		$this->assign('ChannelID', $ChannelID); //当前频道
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	/**
	 * 保存添加
	 */
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
		if(!$this->hasChannelPurview( $_POST['ChannelID'] )){
			$this->ajaxReturn(null, '没有权限' , 0);
		}
		$this->prePost($_POST);
		$info = D('Admin/Info');
		if( $info->create() ){
			$info->MemberID = (int)session('AdminMemberID');
			
			//自动上传远程图片===================================================
			if($GLOBALS['Config']['AUTO_UPLOAD_ENABLE']==1 && stripos($info->InfoContent , '<img') !== false ){
				$temp = yd_upload_content($info->InfoContent); //自动上传远程图片
				$info->InfoContent = $temp[2];
			}
			//==============================================================
			
			//自动获取内容第一个图片作为缩略图=======================================
			if( empty( $info->InfoPicture ) && stripos($info->InfoContent , '<img') !== false ){
				if( $GLOBALS['Config']['THUMB_FIRST'] == 1 ){
					$imageList = yd_extract_image($info->InfoContent, 2);
					if( $imageList !== false ){
						$one = $imageList[0];
						if( stripos($one, 'http://') === false && stripos($one, 'https://') === false
								&& stripos($one, 'ftp://') === false ){ //本地图片
							if($GLOBALS['Config']['THUMB_ENABLE'] == 1){
								$thumbFile = makeThumb($this->DocumentRoot.$one);
								//返回的一定是相对路径,如: ./Upload/1.jpg
								$info->InfoPicture = $this->WebInstallDir.substr($thumbFile, 2);
							}else{
								$info->InfoPicture = $one;
							}
						}else{ //远程图片
							$one = yd_grab_image($one);  //返回一个文件名
							if( $one ) {
                                $uploadDir = GetUploadDir();
								$thumbFile = makeThumb( $uploadDir.$one );
								$info->InfoPicture = $this->WebInstallDir.substr($thumbFile, 2);
							}
					    }
				}
			  }
			}
			//=============================================================
			if($info->add()){
				$lastID = $info->getLastInsID();
                $description = "ID:{$lastID} {$_POST['InfoTitle']}";
				WriteLog( $description, array('LogType'=>2,'UserAction'=>'保存信息添加'));
				save_info_type_attribute($lastID);
				$msg = baidu_push_info($lastID);
				$this->ajaxReturn(null, "添加成功！{$msg}" , 1);
			}else{
				$this->ajaxReturn(null, '添加失败！' , 0);
			}
		}else{
			$this->ajaxReturn(null, $info->getError() , 0);
		}
	}
	
	//导出反馈数据
	function exportFeedback(){
		$csvName = date('Y-m-d_H_i').'.csv';  //导出文件名称
		$channelID = intval($_REQUEST['ChannelID']);
		
		if(!$this->hasChannelPurview( $channelID)){
			return;
		}
		
		$a = D('Admin/Attribute');
		$field = $a->getAttribute(37);
		$group = $a->getGroup(37);
        $colName = array();
		foreach ($group as $g){
			foreach ($field as $f){
				if( $g['AttributeID'] == $f['GroupID']){
					$colName[ $f['FieldName'] ] = $f['DisplayName'];
				}
			}
		}
        $str='';
		foreach ($colName as $k=>$v){
			$v = filter_csv_content($v);
			$str.= "$v,";
		}
		$str = substr($str, 0, strlen($str)-1);
		$str= iconv('utf-8', 'gb2312//IGNORE', $str.PHP_EOL);
		
		$m = D('Admin/Info');
		$data= $m->getInfo(-1, -1, $channelID);
		//注意导入和导出的过程中，因为我们使用的是统一UTF-8编码，
		//遇到中文字符一定要记得转码，否则可能会出现中文乱码的情况。
		foreach($data as $d){
			$temp = "";
			foreach ($colName as $k=>$v){
				$d[$k] = filter_csv_content($d[$k]);
				$temp.= $d[$k].',';
			}
			$temp = substr($temp, 0, strlen($temp)-1);
			$temp= iconv('utf-8', 'gb2312//IGNORE', $temp.PHP_EOL);
			$str .= $temp;
		}
		WriteLog('' , array('LogType'=>6,'UserAction'=>'导出反馈数据'));
		yd_download_csv($csvName, $str); //下载csv
	}

    /**
     * 根据是否存在相关插件过滤指定属性
     */
	private function _filterAttribute($ChannelModelID){
        $m = D('Admin/Attribute');
        $Attribute = $m->getAttribute($ChannelModelID);
        if(36 == $ChannelModelID){
            //是否启用商城插件，如果没有启用，就不显示指定字段
            $isShopInstall = appIsInstall(166);
            if(!$isShopInstall){
                $map = array('Commission'=>1, 'StockCount'=>1, 'GivePoint'=>1, 'ExchangePoint'=>1);
                $temp = array();
                foreach($Attribute as $v){
                    $key = $v['FieldName'];
                    if(!isset($map[$key])){
                        $temp[] = $v;
                    }
                }
                $Attribute = $temp;
            }
        }
        return $Attribute;
    }

    /**
     * 根据是否存在相关插件过滤指定分组
     */
    private function _filterGroup($ChannelModelID){
        $m = D('Admin/Attribute');
        $Group = $m->getGroup($ChannelModelID);
        if(36==$ChannelModelID){
            //是否启用商城插件，如果没有启用，就过滤属性规格分组
            $isShopInstall = appIsInstall(166);
            if(!$isShopInstall){
                $temp = array();
                foreach($Group as $v){
                    if(620 != $v['AttributeID']){ //620：属性规格
                        $temp[] = $v;
                    }
                }
                $Group = $temp;
            }
        }
        return $Group;
    }

    /**
     * 获取目标频道
     */
    function getTargetChannel(){
        $groupid = intval(session('AdminGroupID'));
        $mark= trim($_POST['LanguageMark']);
        $LanguageID = get_language_id($mark);
        if(empty($LanguageID)){
            $this->ajaxReturn(null, "语言参数错误！" , 1);
        }
        $c = D('Admin/Channel');
        $data = $c->getChannelPurview(1, $groupid, '&nbsp;&nbsp;&nbsp;&nbsp;├─ ', $LanguageID);
        if(!is_array($data)) $data = array();
        $this->ajaxReturn($data, "" , 1);
    }

    /**
     * 群*发*微信消息
     */
    function sendWxMessage(){
        $m = D('Admin/Config');
        $data = $m->getConfig('wx'); //配置数据不从缓存中提取
        if(empty($data['WX_APP_ID']) || empty($data['WX_APP_SECRET'])){
            $this->ajaxReturn(null, "微信APPID或APPSECRET未设置！请前往【微信】-【微信绑定设置】设置" , 0);
        }

        $InfoID = trim($_POST['InfoID'], ',');
        $InfoID = YdInput::checkCommaNum($InfoID);
        if(empty($InfoID)){
            $this->ajaxReturn(null, "请至少选择一篇文章！" , 1);
        }
        //判断是是否可以群发
        $m = D('Admin/Info');
        $where['InfoID'] = array('IN', $InfoID);
        //使用f5作为是否发送的标志
        $data = $m->where($where)->field("InfoID,InfoTitle,InfoPicture,InfoContent,IsEnable,f5")->select();
        foreach($data as $k=>$v){
            if(1==$v['f5']){
                $this->ajaxReturn(null, "文章【{$v['InfoID']}】已群发，请取消！" , 0);
            }
            if(0==$v['IsEnable']){
                $this->ajaxReturn(null, "文章【{$v['InfoID']}】已停用，不能发送，请取消！" , 0);
            }
            if(empty($v['InfoContent'])){
                $this->ajaxReturn(null, "文章【{$v['InfoID']}】内容为空，不能发送！" , 0);
            }
        }
        $IsPreview = intval($_POST['IsPrivew']); //是否预览
        //上传文章前需要将里面的图片，上传到资源库

        $result = true;
        if(false!==$result){
            $this->ajaxReturn(null, "微信群发成功！" , 1);
        }else{
            $this->ajaxReturn(null, "微信群发失败！" , 0);
        }
    }
}