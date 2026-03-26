<?php
class PointAction extends AdminBaseAction{
	function index(){
		$p = array(
			'HasPage' => true,
		);
		if( !empty($_REQUEST['SearchWord']) ){
			$p['Parameter']['SearchWord'] = $_REQUEST['SearchWord'];
		}
        $p['Parameter']['PointType'] = !empty($_REQUEST['PointType']) ? (int)$_REQUEST['PointType'] : -1;
		$m = D('Admin/Point');
		$PointTypeList = $m->getPointTypeList();
		$this->assign("PointTypeList", $PointTypeList);
		$this->opIndex($p);
	}
	
	
	//给会员转电子币
	function transfer(){
		if( empty($_REQUEST['Member'])){
			$this->ajaxReturn(null, '会员不能为空' , 0);
		}
		$m = D('Admin/Member');
		$MemberID = $m->getMemberIDByKeywords($_REQUEST['Member']);
		if(empty($MemberID)){
			$this->ajaxReturn(null, $_REQUEST['Member'].' 不存在' , 0);
		}
		
		if( !is_numeric($_REQUEST['PointValue'])){
			$this->ajaxReturn(null, '积分必须为数字' , 0);
		}
		if( 0 == $_REQUEST['PointValue'] ){
			$this->ajaxReturn(null, '积分不能为0' , 0);
		}
		$mp = D('Admin/Point');
		$data['PointValue'] = $_REQUEST['PointValue'];
		$data['MemberID'] = $MemberID;
		$data['PointRemark'] = $_REQUEST['PointRemark'];
		$data['PointType'] = 2;    //表示管理员调整
		$data['PointTime'] = date('Y-m-d H:i:s');
		$PointID = $mp->add($data);
		if($PointID){
			WriteLog("给会员{$MemberID}转积分".$_REQUEST['PointValue']);
			$this->ajaxReturn(null, '调整积分成功!' , 1);
		}else{
			$this->ajaxReturn(null, '调整积分失败!' , 0);
		}
	}
}