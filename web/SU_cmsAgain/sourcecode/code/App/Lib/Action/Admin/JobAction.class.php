<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class JobAction extends AdminBaseAction {
	function index(){
		header("Content-Type:text/html; charset=utf-8");
        $JobClassID = isset($_REQUEST['JobClassID']) && is_numeric($_REQUEST['JobClassID']) ? (int)$_REQUEST['JobClassID'] : -1;
		$m = D('Admin/Job');
		import("ORG.Util.Page");
		$TotalPage = $m->getJobCount($JobClassID);
		$PageSize = $this->AdminPageSize;
		
		$Page = new Page($TotalPage, $PageSize);
		$Page->rollPage = $this->AdminRollPage;
        if( $JobClassID>0){
            $Page->parameter = "&JobClassID=$JobClassID";
        }
		$ShowPage = $Page->show();
		$Job = $m->getJob($Page->firstRow, $Page->listRows, -1, $JobClassID);
		
		//计算应聘简历数=============================================
		$r = D('Admin/Resume');
		$n = is_array($Job) ? count($Job) : 0;
		for ($i=0; $i<$n; $i++){
			$Job[$i]['ResumeCount'] = $r->getResumeCount( $Job[$i]['JobID'] );
		}
		//======================================================

        $this->assign('JobClassID', $JobClassID);
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Job', $Job);
		$this->assign('Page', $ShowPage); //分页条
		$this->display();
	}
	
	function add(){
		header("Content-Type:text/html; charset=utf-8");
	
		//模型属性信息==============================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(7);
        $this->_extraAttribute($Attribute);
		$Group = $m->getGroup(7);
		
		for($n = 0; $n < count($Attribute); $n++){
			if($Attribute[$n]['DisplayType'] == 'datetime'){
				$Attribute[$n]['DisplayValue'] = date('Y-m-d H:i:s'); //显示当期时间
				break;
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/SaveAdd');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Job');
		if( $m->create() ){
			if($m->add()){
				WriteLog("ID:".$m->getLastInsID() );
				$this->ajaxReturn(null, '添加成功!' , 1);
			}else{
				$this->ajaxReturn(null, '添加失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}

    /**
     * 单个删除岗位
     */
	function del(){
		header("Content-Type:text/html; charset=utf-8");
        $JobClassID = isset($_REQUEST['JobClassID']) && is_numeric($_REQUEST['JobClassID']) ? (int)$_REQUEST['JobClassID'] : -1;
		$JobID = $_GET["JobID"];
		$p = $_GET["p"];
		$m = D('Admin/Job');
		if( is_numeric($JobID) && is_numeric($p) && !$m->hasData( $JobID ) ){
			$m->delete($JobID);
			WriteLog("ID:$JobID");
		}
		redirect(__URL__."/index/p/$p/JobClassID/{$JobClassID}");
	}

    /**
     * 批量删除岗位
     */
	function batchDel(){
		$id = $_POST['JobID'];
		$NowPage = (int)$_POST["NowPage"];
        $JobClassID = isset($_REQUEST['JobClassID']) && is_numeric($_REQUEST['JobClassID']) ? (int)$_REQUEST['JobClassID'] : -1;
		$m = D('Admin/Job');
		foreach($id as $k=>$v){
			if( $m->hasData($v) ){
				unset( $id[$k] );
			}
		}
		
		if( count($id) > 0 ){
			$m->batchDelJob( $id );
			WriteLog("ID:".implode(',', $id));
		}
		redirect(__URL__."/index/p/{$NowPage}/JobClassID/{$JobClassID}");
	}
	
	//批量排序Banner
	function batchSort(){
		$JobOrder = $_POST['JobOrder']; //排序
		$JobID = $_POST['JobOrderID']; //排序
		$NowPage = (int)$_POST["NowPage"];
        $JobClassID = isset($_REQUEST['JobClassID']) && is_numeric($_REQUEST['JobClassID']) ? (int)$_REQUEST['JobClassID'] : -1;
		if( is_array($JobID) && is_array($JobOrder) && count($JobID) > 0 && count($JobOrder) > 0 ){
			D('Admin/Job')->batchSortJob($JobID, $JobOrder);
			WriteLog();
		}
		redirect(__URL__."/index?p=$NowPage&JobClassID={$JobClassID}");
	}
	
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$JobID = $_GET['JobID'];
		if( !is_numeric($JobID)){
			alert("非法参数", __URL__.'/index');
		}
		//====================================
	
		//模型属性信息=================================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(7);
		$this->_extraAttribute($Attribute);
		$Group = $m->getGroup(7);
	
		//获取专题数据======================================================
		$m = D('Admin/Job');
		$Info = $m->find( $JobID );
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				$Attribute[$n]['SelectedValue'] = $Info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
			}else{
				$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//==============================================================
	
		$this->assign('HiddenName', 'JobID');
		$this->assign('HiddenValue', $JobID);
		$this->assign('Action', __URL__.'/saveModify');
	
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Job');
		if( $m->create() ){
			if($m->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				WriteLog( "ID:".$_POST['JobID'] );
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}

	//应聘简历管理
	function resume(){
		header("Content-Type:text/html; charset=utf-8");
		$JobID = isset($_REQUEST['JobID']) && is_numeric($_REQUEST['JobID']) ? (int)$_REQUEST['JobID'] : -1;
        $Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		
		$m = D('Admin/Resume');
		import("ORG.Util.Page");
		$TotalPage = $m->getResumeCount($JobID); 
		$PageSize = $this->AdminPageSize;
		
		$Page = new Page($TotalPage, $PageSize);
		$Page->rollPage = $this->AdminRollPage;
		$Page->parameter = "&JobID={$JobID}&Keywords={$Keywords}";
		$ShowPage = $Page->show();
		
		$Resume= $m->getResume($Page->firstRow, $Page->listRows, -1, $JobID, $Keywords);
		
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('JobID', $JobID);
        $this->assign('Keywords', $Keywords);
		$this->assign('Resume', $Resume);
		$this->assign('Page', $ShowPage); //分页条
		$this->display();
	}
	
	function delResume(){
		header("Content-Type:text/html; charset=utf-8");
		$ResumeID =  (int)$_GET["ResumeID"];  //单个删除简历
		$JobID = isset($_REQUEST['JobID'])  && is_numeric($_REQUEST['JobID']) ?  (int)$_REQUEST['JobID'] : -1;
        $Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		$p = (int)$_GET["p"];
	
		if( is_numeric($ResumeID) && is_numeric($p)){
			D('Admin/Resume')->delete($ResumeID);
			WriteLog("ID:$ResumeID");
		}
		redirect(__URL__."/resume?JobID=$JobID&p=$p&Keywords={$Keywords}");
	}
	
	function batchDelResume(){
		$id = $_POST['ResumeID'];  //数组
		$JobID = isset($_REQUEST['JobID']) && is_numeric($_REQUEST['JobID']) ? (int)$_REQUEST['JobID'] : -1;
        $Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		$NowPage = (int)$_POST["NowPage"];
		if( count($id) > 0 ){
		    $m = D('Admin/Resume');
            $m->batchDelResume($id);
			WriteLog("ID:".implode(',', $id));
		}
		redirect(__URL__."/resume?JobID=$JobID&p=$NowPage&Keywords={$Keywords}");
	}
	
	function viewResume(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$ResumeID = (int)$_GET['ResumeID'];
		if( !is_numeric($ResumeID) ) return;
		$m = D('Admin/Resume');
		$Info = $m->findResume($ResumeID);
		$this->assign('Resume', $Info);
		$this->display();
	}

	private function _extraAttribute(&$data){
	    $temp[] = array(
	        'AttributeID'=>930,
            'ChannelModelID'=>7,
            'FieldName'=>'JobClassID',
            'FieldType'=>'int',
            'DisplayName'=>'所属分类',

            'DisplayType'=>'jobclassselect',
            'DisplayOrder'=>5,
            'DisplayWidth'=>'',
            'DisplayHeight'=>'',
            'DisplayClass'=>'textinput',

            'DisplayValue'=>'',
            'DisplayHelpText'=>'',
            'GroupID'=>128,
            'IsRequire'=>'1',
            'IsEnable'=>'1',
            'IsSystem'=>'0',
        );
        $temp[] = array(
            'AttributeID'=>931,
            'ChannelModelID'=>7,
            'FieldName'=>'ReceiveEmail',
            'FieldType'=>'text',
            'DisplayName'=>'简历接收邮箱',

            'DisplayType'=>'text',
            'DisplayOrder'=>6,
            'DisplayWidth'=>'450px',
            'DisplayHeight'=>'',
            'DisplayClass'=>'textinput',

            'DisplayValue'=>'',
            'DisplayHelpText'=>'多个邮箱以英文逗号隔开。如果设置为空，则会发送到全局设置的邮箱',
            'GroupID'=>128,
            'IsRequire'=>'0',
            'IsEnable'=>'1',
            'IsSystem'=>'0',
        );
        array_splice($data, 10, 0, $temp);
    }

    //招聘分类
    function classIndex(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/JobClass');
        $p = array('Count'=>1);
        $JobClass = $m->getJobClass($p);
        $this->assign('JobClass', $JobClass);
        $this->display();
    }

    /**
     * 添加分类
     */
    function addClass(){
        header("Content-Type:text/html; charset=utf-8");

        //模型属性信息==============================================
        $m = D('Admin/Attribute');
        $Attribute = $this->_getAttribute();
        $Group = $this->_getGroup();
        $Attribute = parent::parseAttribute($Attribute);  //解析属性信息
        //======================================================
        $this->assign('Action', __URL__.'/saveAddClass');
        $this->assign('Group', $Group);
        $this->assign('Attribute', $Attribute);
        $this->display();
    }

    function saveAddClass(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/JobClass');
        if( $m->create() ){
            if($m->add()){
                WriteLog("ID:".$m->getLastInsID() );
                $this->ajaxReturn(null, '添加成功!' , 1);
            }else{
                $this->ajaxReturn(null, '添加失败!' , 0);
            }
        }else{
            $this->ajaxReturn(null, $m->getError() , 0);
        }
    }

    function delClass(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/JobClass');
        $id = $_GET["JobClassID"];
        $data = "#tr$id";
        if( !is_numeric($id) ){
            $this->ajaxReturn($data, '参数非法!' , 3);
        }

        if( $m->hasData($id) ){
            $this->ajaxReturn($data, '当前分类包含友情链接数据，请先删除!' , 2);
        }

        //删除操作
        if( $m->delete($id) ){
            WriteLog( "ID:$id");
            $this->ajaxReturn($data, '删除成功!' , 1);
        }else{
            $this->ajaxReturn($data, '删除失败!' , 0);
        }
    }

    function batchDelClass(){
        $id = $_POST['JobClassID'];
        //若分类存在数据，则不删除
        $m = D('Admin/JobClass');
        foreach($id as $k=>$v){
            if( $m->hasData($v) ){
                unset( $id[$k] );
            }
        }

        if( count($id) > 0 ){
            $m->batchDelJobClass($id);
            WriteLog("ID:".implode(',', $id));
        }
        redirect(__URL__."/classIndex");
    }

    function batchSortClass(){
        $JobClassOrder = $_POST['JobClassOrder']; //排序
        $JobClassID = $_POST['JobClassOrderID']; //排序
        if( is_array($JobClassID) && is_array($JobClassOrder) && count($JobClassID) > 0 && count($JobClassOrder) > 0 ){
            D('Admin/JobClass')->batchSortJobClass($JobClassID, $JobClassOrder);
            WriteLog();
        }
        redirect(__URL__."/classIndex");
    }

    function modifyClass(){
        header("Content-Type:text/html; charset=utf-8");
        //参数有效性检查===========================
        $JobClassID = $_GET['JobClassID'];
        if( !is_numeric($JobClassID)){
            alert("非法参数", __URL__.'/classIndex');
        }
        //====================================

        //模型属性信息=================================================
        $m = D('Admin/Attribute');
        $Attribute = $this->_getAttribute();
        $Group = $this->_getGroup();

        //获取专题数据======================================================
        $m = D('Admin/JobClass');
        $Info = $m->find( $JobClassID );
        for($n = 0; $n < count($Attribute); $n++){
            if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
                $Attribute[$n]['SelectedValue'] = $Info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
            }else{
                $Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
            }
        }
        $Attribute = parent::parseAttribute($Attribute);  //解析属性信息
        //==============================================================

        $this->assign('HiddenName', 'JobClassID');
        $this->assign('HiddenValue', $JobClassID);
        $this->assign('Action', __URL__.'/saveModifyClass');

        $this->assign('Group', $Group);
        $this->assign('Attribute', $Attribute);
        $this->display();
    }

    function saveModifyClass(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/JobClass');
        if( $m->create() ){
            if($m->save() === false){
                $this->ajaxReturn(null, '修改失败!' , 0);
            }else{
                WriteLog("ID:".$_POST['JobClassID'] );
                $this->ajaxReturn(null, '修改成功!' , 1);
            }
        }else{
            $this->ajaxReturn(null, $m->getError() , 0);
        }
    }

    private function _getAttribute(){
        $data = array (
                array (
                    'AttributeID' => '114',  'FieldName' => 'JobClassName', 'FieldType' => 'varchar',
                    'DisplayName' => '招聘分类名称', 'DisplayType' => 'text',  'DisplayOrder' => '2',
                    'DisplayWidth' => '270px', 'DisplayHeight' => NULL, 'DisplayClass' => 'textinput', 'DisplayValue' => NULL,
                    'DisplayHelpText' => NULL, 'IsValidate' => NULL, 'ValidateRule' => NULL,
                    'GroupID' => '117', 'IsRequire' => '1', 'IsEnable' => '1', 'IsSystem' => '0',
                ),
                array (
                    'AttributeID' => '115', 'FieldName' => 'JobClassOrder', 'FieldType' => 'int',
                    'DisplayName' => '招聘分类排序', 'DisplayType' => 'number', 'DisplayOrder' => '3',
                    'DisplayWidth' => '270px', 'DisplayHeight' => NULL, 'DisplayClass' => 'textinput',
                    'DisplayValue' => '0', 'DisplayHelpText' => '请输入数字，值越小排名越靠前！',
                    'IsValidate' => NULL, 'ValidateRule' => NULL,
                    'GroupID' => '117', 'IsRequire' => '0', 'IsEnable' => '1', 'IsSystem' => '0',
                ),
        );
        return $data;
    }

    private function _getGroup(){
        $data[] = array ('AttributeID' => '117', 'DisplayName' => '基本信息');
        return $data;
    }
}