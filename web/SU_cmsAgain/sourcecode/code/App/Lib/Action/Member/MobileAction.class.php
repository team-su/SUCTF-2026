<?php
class MobileAction extends HomeBaseAction {
	private $_memberID = ''; //会员ID
	private $_tplPath = '';
	private $_appTplDir = './Public/tpl/user/';
	function _initialize(){
		parent::_initialize();
		//模板变量
		$this->_tplPath = $this->WebPublic.'tpl/user/';
		$Css = $this->_tplPath.'css/';
		$Images = $this->_tplPath.'images/';
		$Js = $this->_tplPath.'js/';
		
		$this->assign('TplPath', $this->_tplPath);
		$this->assign('Css', $Css);
		$this->assign('Images', $Images);
		$this->assign('Js', $Js);

		//无需获取用户身份和微信判断的方法列表
		$allowlist = array('test'=>1);
		$action = strtolower( ACTION_NAME );
		if( isset( $allowlist[$action])) {
			;
		}else{
			if( !$this->isLogin() ) {
				redirect( WapHomeUrl() );
			}
		}
	}
	
	/**
	 * 判断是否登录
	 * @return boolean true/false
	 */
	public function isLogin(){
		$b = session("?MemberID") && session("MemberID")>0;
		return $b;
	}
	
	function index(){
		header("Content-Type:text/html; charset=utf-8");	
		$MenuTopID = 9;
		$mg = D('Admin/MenuGroup');
		$gid = (int)session('MemberGroupID');
		$mid = (int)session('MemberID');
		$MenuGroup = $mg->getMenuGroupPurview(0, $gid, $MenuTopID);
		
		$mm = D('Admin/Member');
		$MemberAvatar = $mm->where("MemberID=$mid")->getField('MemberAvatar');
		$this->assign('MemberAvatar', $MemberAvatar);
		
		$m = D('Admin/Menu');
		$Menu = $m->getMenuPurview(0, $gid);
		if(!empty($Menu) ){

			$list = array('63'=>'memberInfo', '65'=>'pwd', '67'=>'guestbook', '68'=>'comment', '79'=>'order',  '89'=>'resume',
					'130'=>'favorite','132'=>'cash','142'=>'coupon','143'=>'point', '145'=>'consignee');
			//$s['67'] =D('Admin/Guestbook')->getCount("GuestID=$mid");
			//$s['68'] =D('Admin/Comment')->getCount("GuestID=$mid");
			//$s['79'] =D('Admin/Order')->getOrderCount(array('MemberID'=>$mid));
			//$s['89'] =D('Admin/Resume')->getCount("GuestID=$mid");
			//$s['130'] =D('Admin/Favorite')->getFavoriteCount( array('MemberID'=>$mid) );
			$s['143'] = D('Admin/Point')->getTotalPoint( $mid );
			$AvailableQuantity = D('Admin/Cash')->getAvailableQuantity($mid );
			$s['132'] = '<span class="AvailableQuantity">'.$GLOBALS['Config']['CURRENCY_SYMBOL'].$AvailableQuantity."</span>";
			$s['142'] = D('Admin/CouponSend')->getCouponSendCount( array('MemberID'=>$mid) );
			
			$n = count($Menu);
			for ($i=0; $i < $n; $i++){
				$menuid = $Menu[$i]['MenuID'];
				$Menu[$i]['MenuUrl'] = $list[ $menuid ];
				$Menu[$i]['Count'] = isset($s[ $menuid ]) ? $s[ $menuid ] : '';
			}
			
			$list = array('63'=>'memberInfo', '65'=>'pwd', '67'=>'guestbook', '68'=>'comment', '79'=>'order',  '89'=>'resume',
					'130'=>'favorite','132'=>'cash','142'=>'coupon','143'=>'point', '145'=>'consignee');
			$n = count($Menu);
			for ($i=0; $i < $n; $i++){
				$menuid = $Menu[$i]['MenuID'];
				$Menu[$i]['MenuUrl'] = $list[ $menuid ];
			}
		}
		
		$this->assign('MenuGroup',$MenuGroup);
		$this->assign('Menu', $Menu);
		$this->assign('MenuName', '会员中心');
		$this->assign('Tab', '1');
		$this->display($this->_appTplDir.ACTION_NAME.'.html');
	}
	
	function memberInfo(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = (int)session('MemberID');
		if( !is_numeric($MemberID)){
			alert("非法参数", __URL__.'/Index');
		}
	
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(13);
		$Group = $m->getGroup(13);
		 
		//获取会员数据====================================================
		$m = D('Admin/Member');
		$data = $m->find( $MemberID );
		$count = count($Attribute);
		for($n = 0; $n < $count; $n++){
			$FieldName = strtolower( $Attribute[$n]['FieldName'] );
			$FieldType = $Attribute[$n]['DisplayType'];
			if( $this->IsSelectedAttribute( $FieldType ) ){
				$Attribute[$n]['SelectedValue'] = $data[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
			}else{
				$Attribute[$n]['DisplayValue'] = $data[ $Attribute[$n]['FieldName'] ];
			}
			$Attribute[$n]['IsMobile'] = 1; //1表示手机端，手机端解析生成的html不一样
			$Attribute[$n]['DisplayWidth'] = '';
			
			if($FieldType=='text'){
				$Attribute[$n]['DisplayClass'] = 'weui-input';
			}
			
			//会员名称和分组以label形式显示
			switch( $FieldName ){
				case 'membergroupid':
					$Attribute[$n]['DisplayClass'] = '';
					$Attribute[$n]['DisplayType'] = 'label';
					$Attribute[$n]['DisplayValue'] = "<span>".session('MemberGroupName')."</span>";
					break;
				case 'membername':
					if( !empty($Attribute[$n]['DisplayValue'])){
						$Attribute[$n]['DisplayClass'] = '';
						$Attribute[$n]['DisplayType'] = 'label';
						$Attribute[$n]['DisplayValue'] = "<span>".$Attribute[$n]['DisplayValue']."</span>";
					}
					break;
				case 'ischeck':
					$Attribute[$n]['DisplayClass'] = '';
					$Attribute[$n]['DisplayType'] = 'label';
					$Attribute[$n]['DisplayValue'] = "<span style='color:red'>已审核</span>";
					break;
				case 'islock':
					$Attribute[$n]['DisplayClass'] = '';
					$Attribute[$n]['DisplayType'] = 'label';
					$Attribute[$n]['DisplayValue'] = "<span style='color:red'>未锁定</span>";
					break;
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//==============================================================
		$this->_assignMenuName(63);
		$this->assign('HiddenName', 'MemberID');
		$this->assign('HiddenValue', $MemberID);
		$this->assign('Action', __URL__.'/saveMemberInfo');
		 
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->assign('Tab', '3');
		$this->display($this->_appTplDir.strtolower(ACTION_NAME).'.html');
	}
	
	function saveMemberInfo(){
		header("Content-Type:text/html; charset=utf-8");
		//防止注入, 过滤关键字段，防止用户自己修改权限
		unset($_POST['MemberPassword'], $_POST['IsCheck'],$_POST['IsLock'],$_POST['MemberGroupID'],$_POST['IsSystem']);
		unset($_POST['IsDistributor'],$_POST['DistributorLevelID'],$_POST['CashPassword'],$_POST['InviteCode']);
		unset($_POST['InviterID'],$_POST['RegisterTime'],$_POST['LoginCount'],$_POST['OpenID']);
		$_POST = YdInput::checkReg( $_POST ); //xss过滤
		$m = D('Admin/Member');
		if( $m->create() ){
			$m->MemberID = (int)session('MemberID');
			if($m->save() === false){
				$this->ajaxReturn(null, '保存失败!' , 0);
			}else{
				$this->ajaxReturn(null, '保存成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}

	//修改密码
	function pwd(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = (int)session('MemberID');
		$m = D('Admin/Member');
		//如果是第三方注册，没有原始密码
		$oldPassword = $m->where("MemberID=$MemberID")->getField('MemberPassword');
		$HasOldPassword = empty($oldPassword) ? 0 : 1;
		if( $_POST['Action'] == 'save'){ //保存
			$pwd1 = trim($_POST['pwd1']);  //原始密码
			$pwd2 = trim($_POST['pwd2']);
			$pwd3 = trim($_POST['pwd3']);
			if( $HasOldPassword && empty($pwd1) ){
				$this->ajaxReturn(null, '原始密码不能为空!' , 0);
			}
	
			if( empty($pwd2) ){
				$this->ajaxReturn(null, '新密码不能为空!' , 0);
			}
	
			if( empty($pwd3) ){
				$this->ajaxReturn(null, '重复密码不能为空!' , 0);
			}
	
			if( $pwd2 != $pwd3 ){
				$this->ajaxReturn(null, '二次输入的密码不一致!' , 0);
			}
	
			if(  $HasOldPassword && $pwd1 == $pwd3 ){
				$this->ajaxReturn(null, '新密码不能和原始密码相同!' , 0);
			}
			$options['LogType'] = 8;
			
			if($HasOldPassword){
                $isCorrect = $m->isOldPasswordCorrect($MemberID, $pwd1);
				if(!$isCorrect){
					$options['UserAction'] = '修改密码';
					WriteLog(session('MemberName').'修改密码失败，原密码错误', $options);
					$this->ajaxReturn(null, '原密码错误!' , 0);
				}
			}
	
			$MemberID = (int)session('MemberID');
            $pwd2 = yd_password_hash($pwd2);
			$r = $m->where("MemberID=$MemberID")->setField('MemberPassword', $pwd2);
			if($r){
				$options['UserAction'] = '修改密码';
				WriteLog(session('MemberName').'修改密码成功', $options);
				$this->ajaxReturn(null, '修改密码成功!' , 1);
			}else{
				$this->ajaxReturn(null, '修改密码失败!' , 0);
			}
		}
		$this->assign('HasOldPassword', $HasOldPassword);
		$this->assign('Action', __URL__.'/pwd');
		$this->_assignMenuName(65);
		$this->display($this->_appTplDir.ACTION_NAME.'.html');
	}
	
	private function _assignMenuName($MenuID=false){
		$m = D('Admin/Menu');
		$where['MenuID'] = empty($MenuID) ? intval($_GET['MenuID']) : intval($MenuID);
		$name = $m->where($where)->getField('MenuName');
		$this->assign('MenuName', $name);
	}
	
	
	function guestbook(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Guestbook');
		import("ORG.Util.Page");
		$MemberID = (int)session('MemberID');
		$Message= $m->getMessage(-1, -1, -1, $MemberID);
		
		getAllInfo($Message, 6);
		
		$this->assign('Data', $Message);
		$this->_assignMenuName(67);
		$this->assign('Tab', '2');
		$this->display($this->_appTplDir.ACTION_NAME.'.html');
	}
	
	function delMessage(){
		header("Content-Type:text/html; charset=utf-8");
		$MessageID = $_GET["MessageID"];
		if( is_numeric($MessageID) ){
            $MemberID = (int)session('MemberID');
			$where = "MessageID=$MessageID and GuestID=$MemberID";
			$m = D('Admin/Guestbook');
			if( $m->where($where)->delete() ){
				$this->ajaxReturn(null, '删除成功!' , 1);
			}else{
				$this->ajaxReturn(null, '删除失败!' , 0);
			}
		}
	}
	
	function comment(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Comment');
		$p = array(
			'GuestID'=>(int)session('MemberID'),
			'ReplyComments'=>1,
		);
		$Comment = $m->getComment(-1, -1, $p);
		$this->assign('Data', $Comment);
		$this->_assignMenuName(68);
		$this->display($this->_appTplDir.ACTION_NAME.'.html');
	}
	
	function delComment(){
		header("Content-Type:text/html; charset=utf-8");
		$CommentID = $_GET["CommentID"];
		if( is_numeric($CommentID) ){
			$p['GuestID'] = (int)session('MemberID');
			$m = D('Admin/Comment');
			if( $m->delComment($CommentID, $p) ){
				$this->ajaxReturn(null, '删除成功!' , 1);
			}else{
				$this->ajaxReturn(null, '删除失败!' , 0);
			}
		}
	}
	
	function order(){
		$MemberID = (int)session('MemberID');
		$p = array(
				'HasPage' => true,
				'PageSize'=>-1, //不分页
				'Parameter' => array('MemberID' =>$MemberID),
				'DataCallBack'=>'DataCallBack',
				'ModuleName'=>'Order',
				'TemplateFile'=>$this->_appTplDir.ACTION_NAME.'.html'
		);
		if( !empty($_REQUEST['OrderNumber']) ){
			$p['Parameter']['OrderNumber'] = $_REQUEST['OrderNumber'];
		}
		if( is_numeric($_REQUEST['OrderStatus']) ){
			$p['Parameter']['OrderStatus'] = $_REQUEST['OrderStatus'];
		}else{
			$p['Parameter']['OrderStatus'] = -1;
		}
		$this->_assignMenuName(79);
		$this->assign('Tab', '4');
		$this->opIndex($p);
	}
	
	/**
	 * 回调函数
	 */
	protected function DataCallBack(&$data){
		$m = D('Admin/OrderProduct');
		$n = is_array($data) ? count($data) : 0;
		for($i=0; $i < $n; $i++){
			//获取支付链接
			if( $data[$i]['PayStatus'] == 2 ){
				$data[$i]['PayUrl'] = PayUrl($data[$i]['OrderID']);
			}
			//获取订单产品相关信息
			$data[$i]['Product'] = $m->getOrderProduct($data[$i]['OrderID']);
			$data[$i]['ProductCount'] = count($data[$i]['Product']);
		}
	}
	
	function viewOrder(){
		$p = array(
				'ModuleName'=>'Order',
				'DataCallBack'=>'ViewDataCallBack',
				'TemplateFile'=>$this->_appTplDir.'vieworder.html'
		);
		$OrderID = intval( $_REQUEST['id'] );
		$MemberID = (int)session('MemberID');
		//会员只能查看自己的
		$m=D('Admin/Order');
		$b = $m->orderExist($OrderID, $MemberID);
		if($b){
			$this->assign('OrderID', $OrderID);
			$m1 = D('Admin/OrderProduct');
			$Product = $m1->getOrderProduct($OrderID);
			$this->assign('Product', $Product);
			$this->assign('MenuName', "查看订单详情");
			$this->opModify(false,$p);
		}
	}
	
	protected function ViewDataCallBack(&$data){
		if( is_numeric($data['OrderID'])){
			$OrderID = $data['OrderID'];
			$m = D('Admin/OrderLog');
			$data['PayTime'] = $m->getPayTime($OrderID);
			$data['ShippingTime'] = $m->getShippingTime($OrderID);
			$data['ShippingNumber'] = $m->getShippingNumber($OrderID);
			$data['FinishTime'] = $m->getFinishTime($OrderID);
		}
	}
	
	/**
	 * 取消订单
	 */
	function cancelOrder(){
		$m = D('Admin/Order');
		$p['MemberID'] = (int)session('MemberID');
		$p['MemberName'] = session('MemberName');
		$b = $m->cancelOrder($_REQUEST['id'], $p);
		$this->ajaxReturn(null, '取消订单成功' , 1);
	}
	
	/**
	 * 确认收货
	 */
	function receiveOrder(){
		$m = D('Admin/Order');
		$p['MemberID'] = (int)session('MemberID');
		$p['MemberName'] = session('MemberName');
		$b = $m->confirmReceipt($_REQUEST['id'], $p);
		$this->ajaxReturn(null, '确认收货成功' , 1);
	}
	
	function delOrder(){
		header("Content-Type:text/html; charset=utf-8");
		$id = $_REQUEST["id"];
		$p['MemberID'] = (int)session('MemberID');
		$m = D('Admin/Order');
		if( $m->delOrder($id, $p) ){
			$this->ajaxReturn(null, '删除成功!' , 1);
		}else{
			$this->ajaxReturn(null, '删除失败!' , 0);
		}
	}
	
	function resume(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Resume');
		$gid = (int)session('MemberID');
		$data = $m->getResume(-1, -1, $gid);
		$this->assign('Data', $data);
		$this->_assignMenuName(89);
		$this->display($this->_appTplDir.ACTION_NAME.'.html');
	}
	
	function delResume(){
		header("Content-Type:text/html; charset=utf-8");
		$ResumeID = $_GET["ResumeID"];
        $MemberID = (int)session('MemberID');
		if( is_numeric($ResumeID) ){
			$where = "ResumeID=$ResumeID and GuestID={$MemberID}";
			$m = D('Admin/Resume');
			if( $m->where($where)->delete() ){
				$this->ajaxReturn(null, '删除成功!' , 1);
			}else{
				$this->ajaxReturn(null, '删除失败!' , 0);
			}
		}
	}
	
	function modifyResume(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$ResumeID = $_GET['ResumeID'];
		if( !is_numeric($ResumeID)){
			alert("非法参数", __URL__.'/Index');
		}
		//====================================
		$m = D('Admin/Resume');
		$data = $m->findResume( $ResumeID );
		$this->assign('Resume', $data);
		$this->assign('Action', __URL__.'/saveModifyResume');

		$this->assign('MenuName', "修改应聘信息");
		$this->display($this->_appTplDir.strtolower(ACTION_NAME).'.html');
	}
	
	function saveModifyResume(){
		header("Content-Type:text/html; charset=utf-8");
		$_POST = YdInput::checkReg( $_POST ); //xss过滤
		$m = D('Admin/Resume');
		if( $m->create() ){
			if($m->save() === false){
				$this->ajaxReturn(null, '保存失败!' , 0);
			}else{
				$this->ajaxReturn(null, '保存成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function logout(){
		session("MemberID", null);
		session("MemberName", null);
		session("MemberGroupID", null);
		session("MemberGroupName", null);
		session('IsAdmin', null);
		$url = trim($_GET['url']);
		if( empty($url) ){
			$url = WapHomeUrl();
		}
		redirect( $url );
	}
	
	function favorite(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Favorite');
		$p = array(
			'MemberID'=>(int)session('MemberID'),
		);
		$Favorite = $m->getFavorite(-1, -1, $p);
		$this->assign('Favorite', $Favorite);
		$this->_assignMenuName(130);
		$this->display($this->_appTplDir.ACTION_NAME.'.html');
	}
	
	function delFavorite(){
		header("Content-Type:text/html; charset=utf-8");
		$where['FavoriteID'] = intval($_GET["id"]);
		$where['MemberID'] = (int)session('MemberID');
		$m = D('Admin/Favorite');
		if( $m->where($where)->delete() ){
			$this->ajaxReturn(null, '删除成功!' , 1);
		}else{
			$this->ajaxReturn(null, '删除失败!' , 0);
		}
	}
	
	/**
	 * 资金管理
	 */
	function cash(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = (int)session('MemberID');
		$m = D('Admin/Cash');
		$p = array('MemberID'=>$MemberID);
		$Data = $m->getCash(-1, -1, $p);
		$this->assign('Data', $Data);
		
		$TotalQuantity = $m->getQuantity(1, $MemberID);
		$AvailableQuantity = $m->getAvailableQuantity($MemberID);
		$this->assign('TotalQuantity', $TotalQuantity);
		$this->assign('AvailableQuantity', $AvailableQuantity);
		$this->_assignMenuName(131);
		
		$mm = D('Admin/Member');
		$CashPassword = $mm->getCashPassword($MemberID); //获取提现密码
		$this->assign('HasCashPassword', $CashPassword ? 1 : 0);
		
		$WithdrawThreshold = $GLOBALS['Config']['WithdrawThreshold'];
		$CanWithdraw = 0; //是否可以提现
		if($AvailableQuantity >= $WithdrawThreshold){
			$CanWithdraw = 1;
		}
		$this->assign('CanWithdraw', $CanWithdraw);
		$this->assign('WithdrawThreshold', $WithdrawThreshold);
		
		$this->display($this->_appTplDir.ACTION_NAME.'.html');
	}
	
	function delCash(){
		header("Content-Type:text/html; charset=utf-8");
		$id = intval($_GET["id"]);
		$p['MemberID'] = (int)session('MemberID');
		$m = D('Admin/Cash');
		if( $m->delCash($_GET["id"], $p) ){
			$this->ajaxReturn(null, '删除成功!' , 1);
		}else{
			$this->ajaxReturn(null, '删除失败!' , 0);
		}
	}
	
	//在线充值
	function recharge(){
		header("Content-Type:text/html; charset=utf-8");
		if( stripos($_SERVER['HTTP_USER_AGENT'],"MicroMessenger") ){
			import("@.Common.YdPay");
			$wx = new YdWxPay();
			$mp = D('Admin/Pay');
			$result = $mp->where('PayTypeID=10 and IsEnable=1')->field('AccountID,AccountPassword')->find();
			$appid = $result['AccountID'];
			$appsecret = $result['AccountPassword'];
			if( isset( $_GET['code']) ){
				$openid = $wx->getOpenidByCode( $_GET['code'], $appid, $appsecret);
				$this->assign('OpenID', $openid);
			}else{
				if(!empty($appid)){
					$url = urlencode(WeixinRechargeDir().'?');
					$redirectUrl = $wx->getCodeUrl($appid, $url);
					header("Location:{$redirectUrl}"); //页面将跳转至 redirect_uri/?code=CODE&state=STATE
					exit();
				}
			}
		}
		$this->assign('MenuName', '我要充值');
		$this->display($this->_appTplDir.ACTION_NAME.'.html');
	}
	
	function setCashPwd(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = (int)session('MemberID');
		$m = D('Admin/Member');
		//原始提现密码密码
		$oldPassword = $m->where("MemberID=$MemberID")->getField('CashPassword');
		$HasOldPassword = empty($oldPassword) ? 0 : 1;
		
		$pwd1 = trim($_POST['pwd1']);  //原始密码
		$pwd2 = trim($_POST['pwd2']);
		$pwd3 = trim($_POST['pwd3']);
			
		if( $HasOldPassword && empty($pwd1) ){
			$this->ajaxReturn(null, '原始密码不能为空!' , 0);
		}
		
		if( empty($pwd2) ){
			$this->ajaxReturn(null, '新密码不能为空!' , 0);
		}
		
		if( empty($pwd3) ){
			$this->ajaxReturn(null, '重复密码不能为空!' , 0);
		}
		
		if( $pwd2 != $pwd3 ){
			$this->ajaxReturn(null, '二次输入的密码不一致!' , 0);
		}
		
		if( $HasOldPassword && $pwd1 == $pwd3 ){
			$this->ajaxReturn(null, '新密码不能和原始密码相同!' , 0);
		}
		
		$options['LogType'] = 8;
		if($HasOldPassword){
            $isCorrect = $m->isCashPasswordCorrect($MemberID, $pwd1);
			if(!$isCorrect){
				$options['UserAction'] = '修改提现密码';
				WriteLog(session('MemberName').'修改提现密码失败，原密码错误', $options);
				$this->ajaxReturn(null, '原密码错误!' , 0);
			}
		}
        $pwd2 = yd_password_hash($pwd2);
		$r = $m->where("MemberID=$MemberID")->setField('CashPassword', $pwd2);
		if($r){
			$options['UserAction'] = '修改提现密码';
			WriteLog(session('MemberName').'修改提现密码成功', $options);
			$this->ajaxReturn(null, '修改密码成功!' , 1);
		}else{
			$this->ajaxReturn(null, '修改密码失败!' , 0);
		}
	}
	
	function withdraw(){
		header("Content-Type:text/html; charset=utf-8");
	
		$m = D('Admin/Cash');
		$MemberID = (int)session('MemberID');
		$AvailableQuantity = $m->getAvailableQuantity($MemberID);
		$MinWithdraw = $GLOBALS['Config']['MinWithdraw']; //最低提现额度
		$Bank = $m->getBank($MemberID);
	
		$this->assign('Bank', $Bank);
		$this->assign('AvailableQuantity', $AvailableQuantity);
		$this->assign('MinWithdraw', $MinWithdraw);
		$this->assign("Action", __URL__."/saveWithdraw");
		$this->assign('MenuName', '提现申请');
		$this->display($this->_appTplDir.ACTION_NAME.'.html');
	}
	
	/**
	 * 保存提现申请
	 */
	function saveWithdraw(){
		$this->_checkWithdraw();
		$p['ModuleName']='Cash';
		$p['SuccessMsg']='提现申请成功';
		$p['FailMsg']='提现申请失败';
		$this->opSaveAdd( $p );
	}
	
	/**
	 * 校验提现数据
	 * @param unknown_type $data
	 */
	private function _checkWithdraw(){
		//提现金额合法性检查
		if( !is_numeric($_POST['CashQuantity']) ){
			$this->ajaxReturn("", "提现金额必须为数字" , 0);
		}
		$CashQuantity = (double)($_POST['CashQuantity']);
		$MinWithdraw = $GLOBALS['Config']['MinWithdraw']; //最低提现额度
		if($CashQuantity < $MinWithdraw){
			$this->ajaxReturn("", "提现金额必须大于{$MinWithdraw}" , 0);
		}
		$m = D('Admin/Cash');
		$MemberID = (int)session('MemberID');
		$AvailableQuantity = $m->getAvailableQuantity($MemberID);
		if($CashQuantity>$AvailableQuantity){
			$this->ajaxReturn("", "账户余额不足！" , 0);
		}
	
	
		if( empty($_POST['BankName']) ){
			$this->ajaxReturn("", "收款银行不能为空" , 0);
		}
		if( empty($_POST['BankAccount']) ){
			$this->ajaxReturn("", "收款账号不能为空" , 0);
		}
		if( empty($_POST['OwnerName']) ){
			$this->ajaxReturn("", "开户人姓名不能为空" , 0);
		}
	
		//验证密码
		$MemberID = (int)session('MemberID');
		$CashPassword = $_POST['CashPassword'];
		$m = D('Admin/Member');
        $isCorrect = $m->isCashPasswordCorrect($MemberID, $CashPassword);
		if(!$isCorrect){
			$this->ajaxReturn("", "提现密码错误" , 0);
		}
		$_POST['CashType'] = 4;
		$_POST['CashStatus'] = 2; //未转账状态
		$_POST['MemberID'] = $MemberID;
		$_POST['CashQuantity'] = 0 - $_POST['CashQuantity'];
		$_POST['CashTime'] = date('Y-m-d H:i:s');
	}
	
	//立即支付
	function payNow(){
		$MemberID = (int)session('MemberID');
		$PayID = intval($_REQUEST['PayID']);
		$CashQuantity = (double)($_REQUEST['CashQuantity']);
		if($CashQuantity <= 0 ){
			$this->ajaxReturn(null, '充值金额必须大于0', 0);
		}
	
		$m = D('Admin/Pay');
		$data = $m->find($PayID);
		if(empty($data)){
			$this->ajaxReturn(null, '支付方式异常', 0);
		}
	
		//插入充值记录========================
		$mc = D('Admin/Cash');
		$cash['MemberID'] = $MemberID;
		$cash['CashQuantity'] = $CashQuantity;
		$cash['CashType'] = 1;
		$cash['CashStatus'] = 2;
		$cash['CashTime'] = date('Y-m-d H:i:s');
		$cash['PayID'] = $PayID;
		$cash['CashRemark'] = $_REQUEST['CashRemark'];
		$CashID = $mc->add($cash);
		//=================================
	
		$PayRate = (double)($data['PayRate']);
		//当前充值总费用
		$data['TotalOrderPrice'] = sprintf("%.2f", $CashQuantity + $CashQuantity * $PayRate);
		//构造一个唯一的订单号
		$data['OrderNumber'] = 'ZXCZ'.date('YmdHis').'_'.$CashID;
		$PayTypeID = intval($data['PayTypeID']);
		switch ($PayTypeID){
			case 1: //支付宝支付
				import("@.Common.YdPay");
				$obj = pay_factory_create($PayTypeID, 3); //必须是3，这里只用于手机
				$protocol = get_current_protocal();
				$data['ReturnUrl'] = WeixinRechargeDir(); //$protocol.$_SERVER['HTTP_HOST'].__APP__;
				$obj->setConfig( $data );   //设置参数
				$data['PayUrl'] = $obj->getPayUrl();  //获取付款链接
				header("Location: ".$data['PayUrl']);
				exit();
				break;
			case 8: //银联支付
				import("@.Common.YdPay");
				$obj = pay_factory_create($PayTypeID, $data['SiteType']);
				$protocol = get_current_protocal();
				$data['ReturnUrl'] = WeixinRechargeDir(); //$protocol.$_SERVER['HTTP_HOST'].__APP__;
				$obj->setConfig( $data );        //设置参数
				$PayUrl = $obj->getPayUrl();  //返回一个表单并自动提交post
				echo $PayUrl;
				exit();
				break;
			case 10: //微信支付
				import("@.Common.YdPay");
				$obj = pay_factory_create($PayTypeID, $data['SiteType']);
				$obj->setConfig( $data );   //设置参数
				if( $obj->getType() == 'NATIVE'){
					$data['PayUrl'] = $obj->getPayUrl();
					$data['PayTip'] = L('WeiXinPayScanTip');
					$PayImageSrc = empty($data['PayUrl']) ? '' : PayQrcode( $data['PayUrl'] );
					$data['PayIcon'] = -1;
					$data['PayContent'] = '';
					if( !empty($PayImageSrc) ){
						$data['PayContent'] = "<img src='{$PayImageSrc}' class='payqrcode' />";
					}
				}else{
					//必须提前获取
					if( !empty($_GET['openid']) ){
						$obj->openid = $_GET['openid'];
						$data['PayJson'] = $obj->getPayUrl(); //微信公众号支付返回的是json数据
					}else{
						$data['PayJson'] = '';
					}
				}
				break;
		}
		unset($data['AccountName'], $data['AccountPassword'], $data['AccountKey'], $data['AccountID']);
		$this->ajaxReturn($data, false, 1);
	}

	/**
	 * 我的优惠券
	 */
	function coupon(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/CouponSend');
		$p = array('MemberID'=>(int)session('MemberID'));
		$Data= $m->getCouponSend(-1, -1, $p);
		$this->assign('Data', $Data);
		$this->_assignMenuName(142);
		$this->display($this->_appTplDir.ACTION_NAME.'.html');
	}
	
	/**
	 * 删除优惠券
	 */
	function delCoupon(){
		header("Content-Type:text/html; charset=utf-8");
		$id = intval($_GET["id"]);
		$p['MemberID'] = (int)session('MemberID');
		$m = D('Admin/CouponSend');
		if( $m->delCouponSend($_GET["id"], $p) ){
			$this->ajaxReturn(null, '删除成功!' , 1);
		}else{
			$this->ajaxReturn(null, '删除失败!' , 0);
		}
	}
	
	/**
	 * 我的优惠券
	 */
	function point(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = (int)session('MemberID');
		$m = D('Admin/Point');
		$p = array('MemberID'=>$MemberID);
		$Data= $m->getPoint(-1, -1, $p);
		$TotalPoint = $m->getTotalPoint($MemberID);
		
		$this->assign('TotalPoint', $TotalPoint);
		$this->assign('Data', $Data);
		$this->_assignMenuName(143);
		$this->display($this->_appTplDir.ACTION_NAME.'.html');
	}
	
	
	/**
	 * 我的收货地址
	 */
	function consignee(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = (int)session('MemberID');
		$p = array(
			'ModuleName'=>'Consignee',
			'Parameter' => array('MemberID' =>$MemberID),
			'TemplateFile'=>$this->_appTplDir.ACTION_NAME.'.html'
		);
		$m = D('Admin/Consignee');
		$DefaultConsigneeID = $m->getDefaultConsigneeID($MemberID);
		$this->assign('DefaultConsigneeID', $DefaultConsigneeID);
		$this->_assignMenuName(145);
		$this->opIndex($p);
	}
	
	/**
	 * 添加收货地址
	 */
	function addConsignee(){
		$Data = array('IsDefault'=>0);
		$this->assign('Data', $Data);
		
		$MemberID = (int)session('MemberID');
		$p = array(
				'ModuleName'=>'Consignee',
				'Parameter' => array('MemberID' =>$MemberID),
				'TemplateFile'=>$this->_appTplDir.ACTION_NAME.'.html',
				'Action'=>__URL__.'/saveAddConsignee',
		);
		$this->opAdd( false, $p );
	}
	
	/**
	 * 保存收货地址
	 */
	function saveAddConsignee(){
		$this->_checkConsignee();
		$_POST['MemberID'] = (int)session('MemberID');
		$p = array(
			'ModuleName'=>'Consignee',
		);
		$this->opSaveAdd( $p );
	}
	
	//表单输入检查
	private function _checkConsignee(){
		if( empty($_REQUEST['ConsigneeRealName'])){
			$this->ajaxReturn('ConsigneeRealName', L('ConsigneeRealNameRequired') , 0);
		}
		
		if( empty($_REQUEST['ConsigneeMobile'])){
			$this->ajaxReturn('ConsigneeMobile', L('ConsigneeMobileRequired') , 0);
		}
		
		if( empty($_REQUEST['ConsigneeAddress'])){
			$this->ajaxReturn('ConsigneeAddress', L('ConsigneeAddressRequired'), 0);
		}
		$_POST = YdInput::checkReg( $_POST ); //xss过滤
	}
	
	/**
	 * 修改收货地址
	 */
	function modifyConsignee(){
		$MemberID = (int)session('MemberID');
		$p = array(
				'ModuleName'=>'Consignee',
				'Parameter' => array('MemberID' =>$MemberID),
				'TemplateFile'=>$this->_appTplDir.ACTION_NAME.'.html',
				'Action'=>__URL__.'/saveModifyConsignee',
		);
		$this->opModify(false, $p);
	}
	
	/**
	 * 保存收货地址
	 */
	function saveModifyConsignee(){
		$this->_checkConsignee();
        $MemberID = (int)session('MemberID');
		$_POST['MemberID'] = $MemberID;
        $_POST['CurrentMemberID'] = $MemberID;
		$p = array(
				'ModuleName'=>'Consignee',
		);
		$this->opSaveModify($p);
	}
	
	/**
	 * 删除收货地址
	 */
	function delConsignee(){
		$m = D('Admin/Consignee');
		$id = $_GET['id'];
		$p['MemberID'] = (int)session('MemberID');
		$b = $m->delConsignee($id, $p);
		if($b){
			WriteLog("ID:{$id}");
		}
		$this->ajaxReturn(null, '', 1);
	}
	
	/**
	 * 设置默认地址
	 */
	function setConsigneeDefault(){
		$m = D('Admin/Consignee');
		$p['MemberID'] = (int)session('MemberID');
		$m->setDefaultConsignee($_GET['id'], $p);
		$this->ajaxReturn(null, '', 1);
	}
	
	/*==插件 开始==*/
	/**
	 * 我的下线/推广
	 */
	function downline(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = (int)session('MemberID');
		$m = D('Admin/Member');
		$result = $m->getDownline($MemberID);
		$Level = isset($_REQUEST['Level']) ? intval($_REQUEST['Level']) : 1;
		if($Level > 3) $Level = 3;
		if($Level < 1) $Level = 1;
		$DownlineCount = count($result[1]) + count($result[2]) + count($result[3]);
		$data =  $result[$Level];
		if(!empty($data)){
			$n = is_array($data) ? count($data) : 0;
			$m = D('Admin/DistributorLevel');
			for($i=0; $i<$n; $i++){
				$where = 'DistributorLevelID = '.intval($data[$i]['DistributorLevelID']);
				$DistributorLevelName = $m->where($where)->getField('DistributorLevelName');
				$data[$i]['DistributorLevelName'] = $DistributorLevelName;
			}
		}
	
		$this->assign('Level', $Level);
		$this->assign('DownlineCount', $DownlineCount);
		$this->assign('Downline1Count', count($result[1]) );
		$this->assign('Downline2Count', count($result[2]) );
		$this->assign('Downline3Count', count($result[3]) );
		$this->assign('Data',$data);
		$this->assign('MenuName', "我的推广");
		$this->display($this->_appTplDir.ACTION_NAME.'.html');
	}
	
	/**
	 * 我的收益
	 */
	function income(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Cash');
		$CashType = 5; //表示分佣金额
		$MemberID = (int)session('MemberID');
		$p = array(
			'MemberID'=>$MemberID,
			'CashType' => $CashType
		);
		
		$Data = $m->getCash(-1, -1, $p);
		$TotalQuantity = $m->getQuantity($CashType, $MemberID);
		$this->assign('MenuName', "我的收益");
		$this->assign('Data', $Data);
		$this->assign('TotalQuantity', $TotalQuantity);
		$this->display($this->_appTplDir.ACTION_NAME.'.html');
	}
	
	/**
	 * 我的推广链接
	 */
	function mylink(){
		header("Content-Type:text/html; charset=utf-8");
		$WapInviteUrl = WapInviteUrl();
		$this->assign('WapInviteUrl', $WapInviteUrl);
		$this->assign('MenuName', "我的推广链接");
		$this->display($this->_appTplDir.ACTION_NAME.'.html');
	}
	/*==插件 结束==*/
}