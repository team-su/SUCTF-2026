<?php
class AppAction extends HomeBaseAction {
	private $_memberID = ''; //会员ID
	private $_tplPath = '';
	private $_appTplDir = './Public/tpl/wx/';
	function _initialize(){
		parent::_initialize();
        if(!method_exists($this, ACTION_NAME)){  //方法名称不区分大小写
            send_http_status(404);
            exit();
        }

		//模板变量
		$this->_tplPath = $this->WebPublic.'tpl/wx/';
		$Css = $this->_tplPath.'css/';
		$Images = $this->_tplPath.'images/';
		$Js = $this->_tplPath.'js/';
		$this->assign('AppRoot', $this->_tplPath);
		$this->assign('AppCss', $Css);
		$this->assign('AppImages', $Images);
		$this->assign('AppJs', $Js);
		
		//微信调试，需要注释
		//$this->_isWx = 1; $this->_fromUser='oD8vGjgGWf5nA04vtK3kzaL91gIY';
		//无需获取用户身份和微信判断的方法列表
		$allowlist = array('test'=>1);
		$action = strtolower( ACTION_NAME );
		if( isset( $allowlist[$action])) {
			;
		}else{
			//只有微信才能访问
			if( $this->_isWx == 0) {
				//防止注入攻击
				$agent = $_SERVER['HTTP_USER_AGENT'];
				$agent = htmlspecialchars( strip_tags(trim($agent)) );
				$ErrorInfo = L('WechatAccessTip').' '.$agent;
				$this->assign('ErrorInfo', $ErrorInfo);
				$this->display($this->_appTplDir."error.html");
				exit();
			}
			
			//微信号无效，或无法获取用户身份
			if( wxUserExist( $this->_fromUser ) === false ){
				$ErrorInfo = L('UserIdentityInvalidTip');
				$ErrorInfo .= ' '.L('WechatNo').'：'.$GLOBALS['Config']['WX_ID'];
				$this->assign('ErrorInfo', $ErrorInfo);
				$this->display($this->_appTplDir."error.html");
				exit();
			}
			$this->assign('FromUser', $this->_fromUser);
		}
	}
	
	//抽奖应用 开始========
	//抽奖程序
	function lottery(){
		header("Content-Type:text/html; charset=utf-8");
		$AppID = intval($_REQUEST['appid']);  //应用ID
		
		$m = D('Admin/WxApp');
		$data = $m->findLottery( $AppID );
		
		if( empty($AppID) || empty($data) ){
			$ErrorInfo = L('PageErrorTip');
			$this->assign('ErrorInfo', $ErrorInfo);
			$this->display($this->_appTplDir."error.html");
			exit;
		}
		
		//抽奖次数限定
		$m1 = D('Admin/WxAward');
		//总抽奖次数
		$LotteryNumber = $m1->getLotteryNumber($this->_fromUser, $AppID, 1, 0);
		//每日抽奖次数
		$LotteryDayNumber = $m1->getLotteryNumber($this->_fromUser, $AppID, 1, 1);
		$LotteryRepeat = 0;
		if( $LotteryNumber >= $data['LotteryMax'] ){
			$LotteryRepeat=1;  //超过总抽奖次数
		}
		if( $LotteryDayNumber >= $data['LotteryDayMax'] ){
			$LotteryRepeat=1;  //超过当天抽奖次数
		}
		
		$this->assign('LotteryNumber', $LotteryNumber);
		$this->assign('LotteryNumberLeft', $data['LotteryMax']-$LotteryNumber);
		$this->assign('LotteryDayNumber', $LotteryDayNumber);
		$this->assign('LotteryDayNumberLeft', $data['LotteryDayMax']-$LotteryDayNumber);
		$this->assign('LotteryRepeat', $LotteryRepeat);
		
		//活动状态判断
		$nLotteryStartTime = strtotime( $data['LotteryStartTime'] ) ;
		$nLotteryEndTime = strtotime( $data['LotteryEndTime'] ) ;
		$nNow = time();
		if( $nNow < $nLotteryStartTime){
			$LotteryStatus = 1;  //活动未开始
		}else if( $nNow > $nLotteryEndTime ){
			$LotteryStatus = 2;  //活动已经结束
		}else{
			$LotteryStatus = 3;  //活动进行中
		}
		$this->assign('AppID', $AppID);
		$this->assign('LotteryStatus', $LotteryStatus);
		$this->assign('LotteryStartTime', $data['LotteryStartTime'] );
		$this->assign('LotteryEndTime', $data['LotteryEndTime'] );
		$this->assign('LotteryMax', $data['LotteryMax'] );
		$this->assign('LotteryDayMax', $data['LotteryDayMax'] );
		$this->assign('LotteryDescription', $data['LotteryDescription'] );
		$this->assign('LotteryName', $data['AppName'] );
		$this->assign('LotteryTip', $data['LotteryTip'] );
		
		//活动已结束
		$this->assign('LotteryEndTitle', $data['LotteryEndTitle'] );
		$this->assign('LotteryEndDescription', $data['LotteryEndDescription'] );

		//中奖概率分析
		$winNumber1 = $m1->getAwardWinNumber($AppID, 1);
		$winNumber2 = $m1->getAwardWinNumber($AppID, 2);
		$winNumber3 = $m1->getAwardWinNumber($AppID, 3);
		if( $winNumber1 >= $data['LotteryAward1Num'] ){
			$data['LotteryAward1Probability'] = 0;
		}
		if( $winNumber2 >= $data['LotteryAward2Num'] ){
			$data['LotteryAward2Probability'] = 0;
		}
		if( $winNumber3 >= $data['LotteryAward3Num'] ){
			$data['LotteryAward3Probability'] = 0;
		}
		
		$p = rand(1, 10000); //范围1-10000
		$LotteryAward = 0; //0:表示没有中奖
		$LotteryAwardText = "谢谢参与";
		if( $p <= $data['LotteryAward1Probability'] ){
			$LotteryAward = 1;
			$LotteryAwardText = "一等奖";
		}else if( $p <= $data['LotteryAward2Probability'] ){
			$LotteryAward = 2;
			$LotteryAwardText = "二等奖";
		}else if( $p <= $data['LotteryAward3Probability'] ){
			$LotteryAward = 3;
			$LotteryAwardText = "三等奖";
		}
		$AwardSN = $this->_getAwardSN();
		$this->assign('AwardSN', $AwardSN );
		$this->assign('LotteryAward', $LotteryAward );
		$this->assign('LotteryAwardText', $LotteryAwardText );
		
		//奖品数量
		$this->assign('LotteryAward1', $data['LotteryAward1'] );
		$this->assign('LotteryAward1Num', $data['LotteryAward1Num'] );
		$this->assign('LotteryAward2', $data['LotteryAward2'] );
		$this->assign('LotteryAward2Num', $data['LotteryAward2Num'] );
		$this->assign('LotteryAward3', $data['LotteryAward3'] );
		$this->assign('LotteryAward3Num', $data['LotteryAward3Num'] );
		
		//登记抽奖
		$m1 = D('Admin/WxAward');
		$m1->addAward( $this->_fromUser, $AppID, $LotteryAward, $AwardSN);
		
		//获取模板
		$LotteryType = $data['LotteryType'];
		$tpl = array(0=>'wheel.html', 1=>'scratch.html');
		$tplFile = $tpl[ $LotteryType ];
		
		//获取用户信息=======================================================
		$mb = D('Admin/Member');
		$fromuser = $this->_fromUser;
		$where['FromUser'] = $fromuser;
		$t = $mb->where($where)->field('MemberRealName,MemberMobile')->find();
		$this->assign('MemberRealName', $t['MemberRealName']);
		$this->assign('MemberMobile', $t['MemberMobile']);
		//===============================================================
		
		$this->display($this->_appTplDir.$tplFile);
	}

	//设置为已抽奖状态
	function registerLottery(){
		$action = strtolower( $_POST['action'] );
		if( $action == 'register'){
			$m = D('Admin/WxAward');
			$m->registerLottery( $this->_fromUser, $_POST['sn']);
		}
	}
	
	//用户提交数据
	function registerMobile(){
		$action = strtolower( $_POST['action'] );
		if( $action == 'set'){
			$m = D('Admin/WxAward');
			$b = $m->registerMobile( $this->_fromUser, $_POST['mobile'], $_POST['sn'], $_POST['username']);
			if($b===false){
				$this->ajaxReturn(null, '提交失败！' , 0);
			}else{
				if( $_POST['pwd'] != ''){
					$m1 = D('Admin/WxApp');
					$b = $m1->IsCorrectPwd($_POST['appid'], $_POST['pwd']);
					if($b===true){ //密码正确，则登记抽奖
						$m->checkLottery($this->_fromUser, $_POST['sn']);
						$this->ajaxReturn(null, '兑奖成功！' , 2);
					}else{ //密码错误！
						$this->ajaxReturn(null, '商家兑奖密码错误！' , 3);
					}
				}else{
					$this->ajaxReturn(null, '提交成功！' , 1);
				}
			}
		}
	}
	
	//生成一个唯一的中奖SN码
	private function _getAwardSN(){
		$sn = date('YmdHis');
		$sn .= rand(1000, 9999);
		return $sn;
	}

	//抽奖应用 结束========
	
	//投票应用 开始========
	function vote(){
		header("Content-Type:text/html; charset=utf-8");
		$AppID = intval($_REQUEST['appid']);  //应用ID
		if( !is_numeric($AppID)){
			$ErrorInfo = L('PageErrorTip');
			$this->assign('ErrorInfo', $ErrorInfo);
			$this->display($this->_appTplDir."error.html");
			exit;
		}
		$fromuser = $this->_fromUser;
		$m = D('Admin/WxVote');
		$hasVoted = $m->hasVoted($AppID, $fromuser) ? 1 : 0;
		
		//投票调查ID
		$this->assign('AppID', $AppID);
		$this->assign('HasVoted', $hasVoted);
		$this->display($this->_appTplDir.'vote.html');
	}
	//投票应用 结束========
	
	//调查应用 开始========
	function research(){
		header("Content-Type:text/html; charset=utf-8");
		$AppID = intval($_REQUEST['appid']);  //应用ID
		$Action = !empty($_REQUEST['action']) ? strtolower($_REQUEST['action']) : 'start';  //步骤
		$m1 = D('Admin/WxApp');
		$data = $m1->findApp($AppID, 1);
		if( !is_numeric($AppID) || empty($data)){
			$ErrorInfo = L('PageErrorTip');
			$this->assign('ErrorInfo', $ErrorInfo);
			$this->display($this->_appTplDir."error.html");
			exit;
		}
        $NextUrl = '';
		$fromuser = $this->_fromUser;
		switch($Action){
			case 'start':  //开始
				//判断是否已经参与了调查=======================
				$NextUrl = "";
				$m = D('Admin/WxResearch');
				$hasVoted = $m->hasVoted($AppID, $fromuser);
				if($hasVoted){
					$StartDescription = "你已经参与了调查！";
				}else{
					//活动状态判断
					$StartTime = strtotime( $data['StartTime'] ) ;
					$EndTime = strtotime( $data['EndTime'] ) ;
					$nNow = time();
					if( $nNow < $StartTime){
						$ResearchStatus = 1;  //活动未开始
						$StartDescription = "调查尚未开始！<br/>开始时间：{$data['StartTime']}<br/>结束时间：{$data['EndTime']}";
					}else if( $nNow > $EndTime ){
						$ResearchStatus = 2;  //活动已经结束
						$StartDescription = "调查已经结束！<br/>开始时间：{$data['StartTime']}<br/>结束时间：{$data['EndTime']}";
					}else{
						$ResearchStatus = 3;  //活动进行中
						if( $data['IsAnonymous']==1){ //匿名调查
							$NextUrl = __URL__."/research?action=vote&appid=$AppID";
						}else{
							$NextUrl = __URL__."/research?action=user&appid=$AppID";
						}
						$StartDescription = $data['StartDescription'];
					}
				}
				$this->assign('StartDescription', $StartDescription);
				break;
			case 'user':  //填写用户资料
				$mb = D('Admin/Member');
				$where['FromUser'] = $fromuser;
				$t = $mb->where($where)->field('MemberRealName,MemberMobile')->find();
				$this->assign('MemberRealName', $t['MemberRealName']);
				$this->assign('MemberMobile', $t['MemberMobile']);
				$NextUrl = __URL__."/research?action=saveuser&appid=$AppID";
				break;
			case 'saveuser':  //填写用户资料
				$mb = D('Admin/Member');
				$data = array('MemberRealName'=>$_REQUEST['username'],'MemberMobile'=>$_REQUEST['mobile']);
				$where['FromUser'] = $fromuser;
				$mb->where($where)->setField($data);
				$NextUrl = __URL__."/research?action=vote&appid=$AppID";
				redirect($NextUrl);
				break;
			case 'vote'://填写问卷调查
				$QuestionID = $_REQUEST['questionid'];
				$NowPage = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : 1;
				$mb = D('Admin/WxQuestion');
				$PageSize = 1;
				$TotalPage = $mb->getQuestionCount($AppID, 1);
				if($NowPage > $TotalPage){
					//投票结束，转向最后一步
					$NextUrl = __URL__."/research?action=end&appid=$AppID";
					$Action='end';
                    $Question = array();
				}else{
					$Question = $mb->getQuestionEx($NowPage-1, $PageSize, $AppID, 1);
					$qid = $Question[0]['QuestionID'];
					$NextPage = $NowPage + 1;
					$NextUrl = __URL__."/research?action=vote&appid=$AppID&p=$NextPage&questionid=$qid";
					$Action='vote';
				}
				//保存调查结果==============================
				if( isset($_REQUEST['ans']) && isset($_REQUEST['questionid']) ){
					$rs = D('Admin/WxResearch');
					$itemID = (array)explode(',', $_REQUEST['ans']);
					$rs->submitResearch($AppID, $QuestionID, $itemID, $fromuser);
				}
				//======================================
				$this->assign('Question', $Question);
				$this->assign('NowPage', $NowPage);
				break;
			case 'end':  //结束
				$LinkUrl = !empty($data['LinkUrl']) ? $data['LinkUrl'] : HomeUrl();
				if(!empty($_REQUEST['suggest']) ){
					$sg = D('Admin/WxSuggest');
					$result = $sg->addSuggest($AppID, $fromuser, $_REQUEST['suggest']);
				}
				$this->ajaxReturn($LinkUrl, '提交建议成功!' , 1);
				break;
		}
		//投票调查ID
		$this->assign('NextUrl', $NextUrl);
		$this->assign('ResearchName', $data['AppName']);
		$this->assign('AppID', $AppID);
		$this->assign('Action', $Action);
		$this->display($this->_appTplDir.'research.html');
	}
	//调查应用 结束========

	//微会员卡 开始========
	//会员卡首页
	function card(){
		header("Content-Type:text/html; charset=utf-8");
		$fromUser = $this->_fromUser;
		$m = D('Admin/WxApp');
		$data= $m->findCardConfig();  //会员卡配置数据
		//读取会员卡信息 start=====================
		$mb = D('Admin/Member');
		$where['FromUser'] = $fromUser;
		$where['IsCheck'] = 1;
		$t = $mb->where($where)->field('MemberRealName,MemberMobile,CardNumber,MemberID,IsLock')->find();
		$data['MemberRealName'] = $t['MemberRealName'];
		$data['MemberMobile'] = $t['MemberMobile'];
		$data['CardNumber'] = $t['CardNumber'];
		$data['IsLock'] = $t['IsLock'];
		$data['HasCard'] = empty($t['CardNumber']) ? 0 : 1;
		if( $data['HasCard'] == 1){
			//获取会员卡总余额
			$wx = D('Admin/WxConsume');
			$data['TotalMoney'] = $wx->getTotal($t['MemberID']);
			$data['UsedMoney'] = $wx->getUsed($t['MemberID']);
			$data['UnUsedMoney'] = $data['TotalMoney']-$data['UsedMoney'];
		}
		//读取会员卡信息 end======================
		
		//是否签到
		$ws = D('Admin/WxScore');
		$result = $ws->getCheckin($t['MemberID'], date("Y"), date("n"));
		$data['HasCheckin'] = !empty($result) ? 1 : 0;
		
		//业务关联
		$n = count($data['Link']);
		for($i=0; $i<$n; $i++){
			$LinkName = $data['Link'][$i]['LinkName'];
			$LinkType = $data['Link'][$i]['LinkType'];
			$id = $data['Link'][$i]['LinkUrl'];
			switch( $LinkType ){
				case 1: //内部频道
					$data['Link'][$i]['LinkName'] = empty($LinkName) ? ChannelName($id) : $LinkName;
					$data['Link'][$i]['LinkUrl'] = ChannelUrl($id);
					break;
				case 2:  //微应用
					$data['Link'][$i]['LinkName'] = empty($LinkName) ? WxAppName($id) : $LinkName;
					$data['Link'][$i]['LinkUrl'] = WxAppUrl($id);
					break;
				case 3:  //外部链接: 无需处理
					$data['Link'][$i]['LinkName'] = empty($LinkName) ? $id : $LinkName;
					break;
			}
		}
		
		$this->assign('c', $data);
		$this->assign('Tab', '1');
		$this->display($this->_appTplDir.'card.html');
	}
	
	//会员卡门店
	function cardStore(){
		header("Content-Type:text/html; charset=utf-8");
		$fromUser = $this->_fromUser;
		$m = D('Admin/WxApp');
		$data= $m->findCardConfig();  //会员卡配置数据
		$this->assign('Store', $data['Store']);
		$this->display($this->_appTplDir.'cardstore.html');
	}
	
	//会员卡说明
	function cardInfo(){
		header("Content-Type:text/html; charset=utf-8");
		$fromUser = $this->_fromUser;
		$m = D('Admin/WxApp');
		$data= $m->findCardConfig();  //会员卡配置数据

		$this->assign('c', $data);
		$this->display($this->_appTplDir.'cardinfo.html');
	}
	
	//会员资料
	function cardMember(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Member');
		$data = $m->findMemberByFromUser($this->_fromUser);
		$this->assign('m', $data);
		$this->assign('Tab', 2);
		$this->display($this->_appTplDir.'cardmember.html');
	}
	
	//保存会员资料
	function saveCardMember(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Member');
		$fromUser = $this->_fromUser;
		$data = array();
		if(!empty($_REQUEST['MemberRealName'])){
			$data['MemberRealName'] = $_REQUEST['MemberRealName'];
		}
		if(isset($_REQUEST['MemberGender'])){
			$data['MemberGender'] = $_REQUEST['MemberGender'];
		}
		if(!empty($_REQUEST['MemberMobile'])){
			$data['MemberMobile'] = $_REQUEST['MemberMobile'];
		}
		if(!empty($_REQUEST['WxID'])){
			$data['WxID'] = $_REQUEST['WxID'];
		}
		if(!empty($_REQUEST['MemberQQ'])){
			$data['MemberQQ'] = $_REQUEST['MemberQQ'];
		}
		if(!empty($_REQUEST['MemberEmail'])){
			$data['MemberEmail'] = $_REQUEST['MemberEmail'];
		}
		if(!empty($_REQUEST['MemberAddress'])){
			$data['MemberAddress'] = $_REQUEST['MemberAddress'];
		}
		//5个扩展字段
		if(isset($_REQUEST['f1'])){ $data['f1'] = $_REQUEST['f1'];}
		if(isset($_REQUEST['f2'])){$data['f2'] = $_REQUEST['f2'];}
		if(isset($_REQUEST['f3'])){$data['f3'] = $_REQUEST['f3'];}
		if(isset($_REQUEST['f4'])){$data['f4'] = $_REQUEST['f4'];}
		if(isset($_REQUEST['f5'])){$data['f5'] = $_REQUEST['f5'];}
		
		$where['FromUser'] = $fromUser;
		$result = $m->where($where)->setField($data);
		if($result!==false){
			$this->ajaxReturn(null, '保存成功!' , 1);
		}else{
			$this->ajaxReturn(null, '保存失败!' , 0);
		}
	}
	
	//会员签到情况
	function cardScore(){
		header("Content-Type:text/html; charset=utf-8");
		$year = date("Y"); //获取当期年
		if(!empty($_REQUEST['month'])){
			$month = $_REQUEST['month'];
			$tm = date("n");
			$day = ($month == $tm) ? date("d") : yd_month_lastday($month, $year);
		}else{
			$month = date("n");
			$day = date("d");
		}
		$currentDate = "$year-$month-$day";
		
		$fromUser = $this->_fromUser;
		$mb = D('Admin/Member');
		$m = D('Admin/WxScore');
		$where['FromUser'] = $fromUser;
		$where['IsCheck'] = 1;
		$where['IsLock'] = 0;
		$t= $mb->where($where)->field('MemberID,CardNumber')->find();
		$MemberID = $t['MemberID'];
		$data['CardNumber'] = $t['CardNumber'];
		$data['Month'] = $month;
		$data['TotalScore'] = $m->getTotal($MemberID);  //会员总积分
		$data['CheckinScore'] = $m->getCheckinScore($MemberID);  //会员总积分
		$data['UsedScore'] = $m->getUsed($MemberID);  //获取已消费积分
		$data['Month'] = $month;
		$data['CurrentMonth'] = date("n");
		
		$weekarray=array("日","一","二","三","四","五","六");
		$monthTotal = 0;
		for($d=(int)$day; $d>0; $d--){
			$s = $m->getCheckin($MemberID, $year, $month, $d);
			$dt = "{$month}月{$d}日 星期".$weekarray[date('w',strtotime("{$year}-{$month}-{$d}"))]; //获取星期
			if(!empty($s)){
				$data['Score'][]=array('Date'=>$dt, 'HasCheckin'=>1, 'Description'=>'已签到', 'Score'=>$s['ScoreNumber']);
				$monthTotal += $s['ScoreNumber'];
			}else{
				$data['Score'][]=array('Date'=>$dt, 'HasCheckin'=>0, 'Description'=>'未签到', 'Score'=>0);
			}
			if($d == $day){
				$data['HasCheckin'] = !empty($s) ? 1 : 0;
			}
		}
		$data['MonthTotal'] = $monthTotal;
		$this->assign('c', $data);
		$this->assign('Tab', '3');
		$this->display($this->_appTplDir.'cardscore.html');
	}
	
	//我的消费记录
	function cardMyConsume(){
		header("Content-Type:text/html; charset=utf-8");
		$fromUser = $this->_fromUser;
		$mb = D('Admin/Member');
		$where['FromUser'] = $fromUser;
		$MemberID = $mb->where($where)->getField('MemberID');
		$data = false;
		if(!empty($MemberID)){
			$wx = D('Admin/WxConsume');
			$data = $wx->getMyConsume($MemberID);
		}
		$this->assign('Consume', $data);
		$this->display($this->_appTplDir.'cardmyconsume.html');
	}
	
	//我的积分记录
	function cardMyScore(){
		header("Content-Type:text/html; charset=utf-8");
		$fromUser = $this->_fromUser;
		$mb = D('Admin/Member');
		$where['FromUser'] = $fromUser;
		$MemberID = $mb->where($where)->getField('MemberID');
		$data = false;
		if(!empty($MemberID)){
			$wx = D('Admin/WxScore');
			$data = $wx->getMyScore($MemberID);
		}
		$this->assign('Score', $data);
		$this->display($this->_appTplDir.'cardmyscore.html');
	}
	
	//我的兑换记录
	function cardMyExchange(){
		header("Content-Type:text/html; charset=utf-8");
		$fromUser = $this->_fromUser;
		$mb = D('Admin/Member');
		$where['FromUser'] = $fromUser;
		$MemberID = $mb->where($where)->getField('MemberID');
		$data = false;
		if(!empty($MemberID)){
			$wx = D('Admin/WxScore');
			$data = $wx->getMyExchange($MemberID);
		}
		$this->assign('Exchange', $data);
		$this->display($this->_appTplDir.'cardmyexchange.html');
	}
	
	//签到领积分
	function cardCheckin(){
		header("Content-Type:text/html; charset=utf-8");
		$fromUser = $this->_fromUser;
		$mb = D('Admin/Member');
		$where['FromUser'] = $fromUser;
		$MemberID = $mb->where($where)->getField('MemberID');
		$m = D('Admin/WxScore');
		$result = $m->checkIn( $MemberID );  //客户签到
		if($result!==false){
			$this->ajaxReturn(null, "签到成功!" , 1);
		}else{
			$this->ajaxReturn(null, '签到失败!' , 0);
		}
	}
	
	//会员领卡
	function getCard(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Member');
		$fromUser = $this->_fromUser;
		$CardNumber = $m->makeCardNumber();
		$data = array(
				'MemberRealName'=>$_REQUEST['username'],
				'MemberMobile'=>$_REQUEST['mobile'],
				'CardNumber'=>$CardNumber,
				'CardTime'=>date('Y-m-d H:i:s'),
		);
		$where['FromUser'] = $fromUser;
		$result = $m->where($where)->setField($data);
		if($result!==false){
			$this->ajaxReturn(null, '提交成功!' , 1);
		}else{
			$this->ajaxReturn(null, '提交失败!' , 0);
		}
	}
	
	//通知管理
	function cardNotify(){
		header("Content-Type:text/html; charset=utf-8");
		$fromUser = $this->_fromUser;
		$m = D('Admin/WxNotify');
		$data = $m->getNotify();
		$n = is_array($data) ? count($data) : 0;
		for($i=0; $i<$n; $i++){
			$ts = strtotime( $data[$i]['NotifyTime'] );
			$day = floor( (time()-$ts) / 86400 );
			//7天以内的消息为最新消息
			$data[$i]['Flag'] = ($day <= 7) ? 'new' : '';
		}
		
		$this->assign('Notify', $data);
		$this->assign('Tab', '1');
		$this->display($this->_appTplDir.'cardnotify.html');
	}
	
	//兑换主界面
	function cardexchange(){
		header("Content-Type:text/html; charset=utf-8");
		$fromUser = $this->_fromUser;
		$mb = D('Admin/Member');
		$where['FromUser'] = $fromUser;
		$where['IsLock'] = 0;
		$where['IsCheck'] = 1;
		$t = $mb->where($where)->field('MemberRealName,MemberMobile,MemberID,CardNumber')->find();
		
		$m = D('Admin/WxGift');
		$data= $m->getGift(-1, -1, '', 1, 1);
		$this->assign('Gift', $data);
		
		$this->assign('MemberRealName', $t['MemberRealName']);
		$this->assign('MemberMobile', $t['MemberMobile']);
		$this->assign('MemberID', $t['MemberID']);
		$this->assign('CardNumber', $t['CardNumber']);
		$this->assign('Tab', '4');
		$this->display($this->_appTplDir.'cardexchange.html');
	}
	
	//执行礼品兑换
	function exchangeGift(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = intval($_REQUEST['mid']);
		$CardNumber = $_REQUEST['cn'];
		$GiftID = intval($_REQUEST['gid']);
		$MemberMobile = $_REQUEST['mobile'];
		$Pwd = $_REQUEST['pwd'];
		$MemberRealName = $_REQUEST['username'];
		$fromUser = $this->_fromUser;
		
		//判断会员卡号是否有效 防止作弊 , 可不做任何提示 start========
		$m = D('Admin/Member');
		if( !$m->isValidCardNumber($MemberID, $CardNumber) ){
			return false;
		}
		//判断会员卡号是否有效 end========

		//判断密码是否正确 start========
		$app = D('Admin/WxApp');
		$p = explode('@@@', $app->where("AppTypeID=6")->getField('AppParameter'));
		$CardPassword = $p[17];
		if( $Pwd != $CardPassword){
			$this->ajaxReturn(null, '密码错误!' , 2);
		}
		//判断密码是否正确 end========
		
		//先判断积分是否不足 start========
		//获取总积分
		$ms = D('Admin/WxScore');
		$Total = $ms->getTotal($MemberID);
		$UsedScore = $ms->getUsed($MemberID);
		$LeftScore = $Total - $UsedScore;
		
		//获取礼品兑换所需积分
		$mg = D('Admin/WxGift');
		$t = $mg->where("GiftID=$GiftID")->field('Score,GiftName')->find();
		$GiftScore = $t['Score'];
		
		if( $GiftScore > $LeftScore){
			$this->ajaxReturn(null, $LeftScore , 3);
		}
		if(!is_numeric($GiftScore)){
			$this->ajaxReturn(null, '无效参数！' , 0);
		}
		//先判断积分是否不足 end========

		//保存会员信息 start=================================
		$data = array(
				'MemberRealName'=>$MemberRealName,
				'MemberMobile'=>$MemberMobile,
		);
		$where['FromUser'] = $fromUser;
		$result = $m->where($where)->setField($data);
		//保存会员信息 end==================================

		//保存兑换信息 start==========
		$data = array(
				'MemberID'=>$MemberID,
				'ScoreNumber'=>$GiftScore,
				'ScoreTime'=>date('Y-m-d H:i:s'),
				'ScoreType'=>4,
				'RelationID'=>$GiftID,
				'Remark'=>$t['GiftName'],
		);
		$result = $ms->add($data);
		//保存兑换信息 end==========
		
		if($result !== false){
			$this->ajaxReturn(null, '提交成功!' , 1);
		}else{
			$this->ajaxReturn(null, '提交失败!' , 0);
		}
	}
	
	//优惠卷主界面
	function cardCoupon(){
		header("Content-Type:text/html; charset=utf-8");
		$fromUser = $this->_fromUser;
		$mb = D('Admin/Member');
		$where['FromUser'] = $fromUser;
		$where['IsLock'] = 0;
		$where['IsCheck'] = 1;
		$t = $mb->where($where)->field('MemberRealName,MemberMobile,MemberID,CardNumber')->find();
	
		$m = D('Admin/WxCoupon');
		$mc = D('Admin/WxConsume');
		$data= $m->getCoupon(-1, -1, '', 1, 1);
		$n = is_array($data) ? count($data) : 0;
		for($i=0; $i<$n; $i++){
			$used = $mc->GetCouponUsed( $data[$i]['CouponID'] );
			$left = $data[$i]['CouponNumber'] - $used;
			$data[$i]['CouponLeft'] = ($left>=0) ? $left : 0;
		}
		$this->assign('Coupon', $data);
	
		$this->assign('MemberRealName', $t['MemberRealName']);
		$this->assign('MemberMobile', $t['MemberMobile']);
		$this->assign('MemberID', $t['MemberID']);
		$this->assign('CardNumber', $t['CardNumber']);
		$this->assign('Tab', '5');
		$this->display($this->_appTplDir.'cardcoupon.html');
	}
	
	//执行优惠卷
	function useCoupon(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = intval($_REQUEST['mid']);
		$CardNumber = $_REQUEST['cn'];
		$CouponID = intval($_REQUEST['cid']);
		$Pwd = $_REQUEST['pwd'];
		$ConsumeMoney = $_REQUEST['money'];
		$ConsumeType = $_REQUEST['zf']; //支付方式
		$fromUser = $this->_fromUser;
	
		//判断会员卡号是否有效 防止作弊 , 可不做任何提示 start========
		$m = D('Admin/Member');
		if( !$m->isValidCardNumber($MemberID, $CardNumber) ){
			return false;
		}
		//判断会员卡号是否有效 end========
	
		//判断密码是否正确 start========
		$app = D('Admin/WxApp');
		$p = explode('@@@', $app->where("AppTypeID=6")->getField('AppParameter'));
		$CardPassword = $p[17];
		if( $Pwd != $CardPassword){
			$this->ajaxReturn(null, '密码错误!' , 2);
		}
		//判断密码是否正确 end========
	
		//判断优惠卷是否被领取完 start========
		//获取优惠卷最多使用次数
		$mg = D('Admin/WxCoupon');
		$t = $mg->where("CouponID=$CouponID")->field('CouponNumber,CouponName')->find();
		$CouponNumber = $t['CouponNumber'];  //优惠卷数量
		if(!is_numeric($CouponNumber)){
			$this->ajaxReturn(null, '无效参数！' , 0);
		}
		
		$wc = D('Admin/WxConsume');
		$CouponUsed = $wc->GetCouponUsed( $CouponID ); //已使用数量
		
		if( $CouponUsed >= $CouponNumber){
			$this->ajaxReturn(null, '优惠卷已被领取完了！' , 0);
		}
		//判断优惠卷是否被领取完 end========
	
		//余额消费需要判断余额是否不足
		if( $ConsumeType == 2){
			$UnUsedMoney = $wc->getUnUsed($MemberID);
			if($UnUsedMoney <= $ConsumeMoney){
				$this->ajaxReturn(null, $UnUsedMoney , 3); //返回余额不足的消息
			}
		}
		//保存消费信息 start==========
		$data = array(
				'MemberID'=>$MemberID,
				'ConsumeMoney'=>$ConsumeMoney,
				'ConsumeTime'=>date('Y-m-d H:i:s'),
				'ConsumeType'=>$ConsumeType,  //1:充值，2：余额消费，3：现金消费
				'RelationID'=>$CouponID,
				'Remark'=>$t['CouponName'],
		);
		$result = $wc->add($data);
		//保存消费信息 end==========
	
		if($result !== false){
			$this->ajaxReturn(null, '提交成功!' , 1);
		}else{
			$this->ajaxReturn(null, '提交失败!' , 0);
		}
	}
	//微会员卡 结束========＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝
	
	//应用公共模块 开始========＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝
	function bindMember(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Member');
		$FromUser = $this->_fromUser;
		$IsBind = $m->hasBind($FromUser);
		$this->assign('IsBind', $IsBind);
		$this->assign('FromUser', $FromUser);
		$this->display($this->_appTplDir.'bindmember.html');
	}
	
	//微信绑定会员
	function saveBindMember(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Member');
		$Type = intval($_POST['Type']);
		$FromUser = $this->_fromUser;
		$IsBind = $m->hasBind($FromUser);
		if( $IsBind == 1 ){
			$this->ajaxReturn('', "已经绑定，不能重复绑定！" , 0);
		}
		
		if($Type == 1){ //创建新的会员账号
			$data = array();
			if( empty($_REQUEST['MemberMobile']) && empty($_REQUEST['MemberEmail']) ){
				$this->ajaxReturn('MemberMobile', "请填写手机号码或电子邮件！" , 0);
			}
			
			//必须判断手机号码是否存在
			if(!empty($_REQUEST['MemberMobile'])){
				$data['MemberMobile'] = $_REQUEST['MemberMobile'];
				$b = $m->where( array('MemberMobile'=>$data['MemberMobile']) )->getField('MemberID');
				if($b){
					$this->ajaxReturn('MemberMobile', L('MemberMobileExist') , 0);
				}
			}
			
			//必须判断电子邮件是否存在
			if(!empty($_REQUEST['MemberEmail'])){
				$data['MemberEmail'] = $_REQUEST['MemberEmail'];
				$b = $m->where( array('MemberEmail'=>$data['MemberEmail']) )->getField('MemberID');
				if($b){
					$this->ajaxReturn('MemberEmail', L('MemberEmailExist') , 0);
				}
			}
			
		    $MemberPassword = trim($_POST['MemberPassword']);
	    	if( $MemberPassword == '' ){
	    		$this->ajaxReturn('MemberPassword', L('PasswordRequired') , 0);
	    	}
	    	
	    	$MemberPassword1 = trim($_POST['MemberPassword1']);
	    	if( $MemberPassword1 == '' ){
	    		$this->ajaxReturn('MemberPassword1', L('ConfirmPasswordRequired') , 0);
	    	}
	    	
	    	if( $MemberPassword != $MemberPassword1 ){
	    		$this->ajaxReturn('MemberPassword', L('PasswordUnmatch') , 0);
	    	}
	    	$data['MemberPassword'] = yd_password_hash($MemberPassword);
			$where['FromUser'] = $FromUser;
			$result = $m->where($where)->setField($data);
			if($result!==false){
				$this->ajaxReturn(null, '绑定成功!' , 1);
			}else{
				$this->ajaxReturn(null, '绑定失败!' , 0);
			}
		}else{ //绑定现有账号
			$MemberAccount = trim($_POST['MemberAccount']);
			if( $MemberAccount == '' ){
				$this->ajaxReturn('MemberAccount', L('UserNameRequired') , 0);
			}
			
			$MemberPassword = trim($_POST['MemberPassword']);
			if( $MemberPassword == '' ){
				$this->ajaxReturn('MemberPassword', L('PasswordRequired') , 0);
			}
			
			$MemberID = $m->memberExist( $MemberAccount, $MemberPassword );
			if( empty($MemberID)){
				$this->ajaxReturn(null, L('UserNamePasswordError') , 0);
			}
			
			//先删除，再绑定
			$result = $m->where(array('FromUser'=>$FromUser))->delete(); 
			if( $result ){
				$result = $m->where(array('MemberID'=>$MemberID))->setField('FromUser', $FromUser);
				$this->ajaxReturn(null, '绑定成功!' , 1);
			}else{
				$this->ajaxReturn(null, '绑定失败!' , 0);
			}
		}
	}
	//应用公共模块 结束========＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝
}