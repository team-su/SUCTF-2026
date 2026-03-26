<?php
class ChannelAction extends HomeBaseAction {
	private $_data;
	/**
	 * 网站引导页
	 */
	public function welcome(){
		header("Content-Type:text/html; charset=utf-8");
		$this->display();
	}
	
	//频道首页
    public function index(){
        header("Content-Type:text/html; charset=utf-8");
        $this-> _getChannel();
        $data = &$this->_data;
        $ChannelID = $data['ChannelID'];
        $ChannelModelID = $data['ChannelModelID'];
        $IndexTemplate = str_ireplace('.html', '', trim($data['IndexTemplate']) );
        switch ($ChannelModelID) {
        	case 32:  $this->_single(); break; //单页频道
        	case 33:    //内部转向链接（非网络协议如：http://开头）
                //$html = strtolower($data['Html']); //通过HMTL名称判断频道
                //频道首页模板，通过模板文件判断更准确
                if($IndexTemplate == 'index'){ //首页
                    $this->_home();
                }elseif($IndexTemplate == 'resume'){ //投递简历
                    $this->_resume();
                }elseif($IndexTemplate == 'job'){ //人才招聘
                    $this->_job();
                }elseif($IndexTemplate == 'guestbook'){ //在线留言
                    $this->_guestbook();
                }
        		break;
        	default:  
        		$this->_info(); 
        		break;   //Info模型
        }
        $SitePath = $this->_getSitePath( $ChannelID, 0,  $data['Parent'], $data['ChannelName'], $data['ChannelUrl']);
        $this->assign('SitePath', $SitePath);
        $this->display($IndexTemplate);
    }
    
    private function _home(){

    }
    
    private function _info(){
    	if(isset($_REQUEST['keywords'])){
            $keywords = YdInput::checkKeyword( $_REQUEST['keywords'] );
            $_REQUEST['keywords'] = $keywords; //旧模板有Think.request.keywords需要过滤
        }else{
            $keywords = '';
        }
    	$labelid = isset($_REQUEST['labelid']) ? YdInput::checkCommaNum( $_REQUEST['labelid'] ) : '';
    	$specialid = isset($_REQUEST['specialid']) ? YdInput::checkNum( $_REQUEST['specialid'] ) : 0;
    	$minprice = isset($_REQUEST['minprice']) ? YdInput::checkNum( $_REQUEST['minprice'] ) : -1;
    	$maxprice = isset($_REQUEST['maxprice']) ? YdInput::checkNum( $_REQUEST['maxprice'] ) : -1;
    	$attr = isset($_REQUEST['attr']) ? YdInput::checkLetterNumber($_REQUEST['attr']) : '';
    	$PageSize = isset($_REQUEST['pagesize']) ? YdInput::checkNum( $_REQUEST['pagesize'] ) : 0;
    	//省市区
    	$provinceid = isset($_REQUEST['provinceid']) ? YdInput::checkNum( $_REQUEST['provinceid'] ) : -1;
    	$cityid = isset($_REQUEST['cityid']) ? YdInput::checkNum( $_REQUEST['cityid'] ) : -1;
    	$districtid = isset($_REQUEST['districtid']) ? YdInput::checkNum( $_REQUEST['districtid'] ) : -1;
 
    	import("ORG.Util.Page");
    	$m = D('Admin/Info');
    	$options = array('Time'=>1, 'MinPrice'=>$minprice, 'MaxPrice'=>$maxprice, 'Attr'=>$attr,
    			'ProvinceID'=>$provinceid, 'CityID'=>$cityid, 'DistrictID'=>$districtid);
    	$Total = $m->getCount( $this->_data['ChannelID'], 1, 1, $keywords, -1, $specialid, $labelid, 1, -1, $options );  //总记录数
    	
    	if(empty($PageSize)){
    		$PageSize = $this->_data['PageSize'] > 0 ? $this->_data['PageSize'] : 20 ;
    	}
        $PageSize = intval($PageSize);
    	$Page = new Page($Total, $PageSize);
    	
    	$Page->parameter = '';
    	if( !empty($keywords) ) {
    		$Page->parameter .= "&keywords=$keywords"; 
    	}
    	if( !empty($labelid) ) {
    		$Page->parameter .= "&labelid=$labelid";
    	}
    	if( !empty($specialid) ) {
    		$Page->parameter .= "&specialid=$specialid";
    	}
    	if( !empty($minprice) && $minprice != -1) {
    		$Page->parameter .= "&minprice=$minprice";
    	}
    	if( !empty($maxprice) && $maxprice != -1) {
    		$Page->parameter .= "&maxprice=$maxprice";
    	}
    	if( !empty($PageSize) ) {
    		$Page->parameter .= "&pagesize=$PageSize";
    	}
    	
    	if( $provinceid != -1) {
    		$Page->parameter .= "&provinceid=$provinceid";
    	}
        if( $cityid != -1) {
    		$Page->parameter .= "&cityid=$cityid";
    	}
    	if( $districtid != -1) {
    		$Page->parameter .= "&districtid=$districtid";
    	}
    	
    	//分页相关信息
    	$PageInfo = $Page->getPageInfo();
    	$this->assign($PageInfo); 
    	
    	$Page->rollPage = $GLOBALS['Config']['HomeRollPage'];
    	$ShowPage = $Page->show();
    	$this->assign('InfoCount', $Total);   //信息总数
    	$this->assign('Page', $ShowPage);   //分页条
    }
    
    private function _single(){
    	
    }
    
    private function _job(){
    	import("ORG.Util.Page");
    	$m = D('Admin/Job');
    	$Total = $m->getCount("IsEnable=1");  //总记录数
    	$PageSize = $this->_data['PageSize'] > 0 ? $this->_data['PageSize']: 20 ;
        $PageSize = intval($PageSize);
    	$Page = new Page($Total, $PageSize);
    	$Page->rollPage = $GLOBALS['Config']['HomeRollPage'];
    	$ShowPage = $Page->show();
    	$this->assign('NowPage', $Page->getNowPage()); //分页条
    	$this->assign('Page', $ShowPage); //分页条
    }
    
    private function _guestbook(){
    	import("ORG.Util.Page");
    	$m = D('Admin/Guestbook');
    	$Total = $m->getCount("IsCheck=1");  //总记录数
    	$PageSize = $this->_data['PageSize'] > 0 ? $this->_data['PageSize'] : 20 ;
        $PageSize = intval($PageSize);
    	$Page = new Page($Total, $PageSize);
    	$Page->rollPage = $GLOBALS['Config']['HomeRollPage'];
    	$ShowPage = $Page->show();
    	
    	$this->assign('NowPage', $Page->getNowPage()); //分页条
    	$this->assign('Page', $ShowPage); //分页条
    	
    	$tip = L('ChangeVerifycode');
    	
    	//验证码
    	$GuestbookCode = "<script type='text/javascript'>\n";
    	$GuestbookCode .= "function ChangeGuestbookCode(){\n";
    	$GuestbookCode .= "    var timenow = new Date().getTime();\n";
    	$GuestbookCode .= "    var obj = document.getElementById('GuestbookCode'); \n";
    	$GuestbookCode .= "    if( obj ) obj.src = '".__GROUP__."/public/guestbookCode/'+timenow;\n";
    	$GuestbookCode .= "}\n";
    	$GuestbookCode .= "</script>\n";
    	$GuestbookCode .= '<img  src="'.__GROUP__.'/public/guestbookCode/"  onclick="ChangeGuestbookCode()"';
    	$GuestbookCode .= ' style="cursor:pointer;" id="GuestbookCode"  align="absMiddle"  alt="'.$tip.'"  title="'.$tip.'" />';
    	$this->assign('GuestbookCode', $GuestbookCode); //分页条

    	$this->assign('GuestbookAction', GuestbookAddAction()); 
    }
    
    public function guestbookAdd(){
    	header("Content-Type:text/html; charset=utf-8");
    	foreach ($_POST as $k=>$v){
    		if( is_array($v) ){
    			$_POST[$k] = implode(',', $v);
    		}
    	}
    	$c = &$GLOBALS['Config'];
    	//留言权限
    	switch( $c['GUEST_BOOK_ALLOW'] ){
    		case 0:  //禁止留言
    			$this->ajaxReturn(null, L('GuestbookForbidden') , 0);
    			break;
    		case 1: //允许匿名留言
    			break;
    		case 2: //允许会员留言
    			if( !$this->MemberIsLogin() ){
                    session('guestbookcode', rand(1000, 9999) );
    				$this->ajaxReturn(null, L('GuestbookLevel') , 0);
    			}
    			break;
    	}
    	
    	$_POST = YdInput::checkTextbox($_POST);
    	if( $c['GUEST_BOOK_VERIFYCODE'] == 1){ //启用验证码
    		$verifycode = $_POST['verifycode'];
    		if( empty($verifycode) ){
    			session('guestbookcode', rand(1000, 9999) );
    			$this->ajaxReturn(null, L('VerifyCodeRequired') , 0);
    		}
    		if( md5($verifycode) != session('guestbookcode')  ){
    			session('guestbookcode', rand(1000, 9999) );
    			$this->ajaxReturn(null, L('VerifyCodeError')  , 0);
    		}
    	}
    	$this->_checkGuestbookIp();
    	$m = D('Admin/Guestbook');
        session('guestbookcode', rand(1000, 9999) );
    	if( $m->create() ){
    		if( $this->MemberIsLogin() ){
    			$m->GuestID = (int)session('MemberID');
    			$m->GuestName = session('MemberName');
    			$_POST['GuestName'] = $m->GuestName;
    		}else{
    			$m->GuestID = 0;
    		}
    		$m->IsCheck = ($c['GUEST_BOOK_CHECK'] == 1) ? 0 : 1;
    		$MessageTime = date('Y-m-d H:i:s');
    		$m->MessageTime = $MessageTime;
    		$m->GuestIP = get_client_ip();

    		if($m->add()){
                $emailbody = '';
    			$success = ($c['GUEST_BOOK_CHECK'] == 1) ? L('GuestbookSuccess') : L('GuestbookSuccessNoCheck');
    			//留言邮件 开始=============================================================
    			if( $c['GUEST_BOOK_SENDEMAIL'] == 1){
    				//获取留言内容====
    				$m = D('Admin/Attribute');
    				$Attribute = $m->getAttribute(6);
    				foreach ($Attribute as $a){
    					$name = explode(',', $a['DisplayName']);
    					$value = $_POST[ $a['FieldName'] ];
    					if(  $value != '' ){
    						$emailbody .= $name[0].':'.$value.'<br/>';
    					}
    				}
    				$emailbody .= '时间:'.$MessageTime;
    				$emailtitle = $c['GUEST_BOOK_EMAIL_TITLE'];
    				$emailto = empty($c['GUEST_BOOK_EMAIL']) ? $c['EMAIL'] : $c['GUEST_BOOK_EMAIL'];
    				//====
    				//提交留言时自动发送电子邮件
    				$b = sendwebmail($emailto, $emailtitle, $emailbody);
    			}
    			//留言邮件 结束=============================================================
    			
    			//短信通知 开始=====================================
    			if( $c['GUEST_BOOK_SMS'] == 1){
    				//获取留言内容
    				$GuestBookSmsTemplate = $c['GUEST_BOOK_SMS_TEMPLATE'];
    				if( empty( $emailbody) && stripos($GuestBookSmsTemplate, '{$Content}')){
	    				$m = D('Admin/Attribute');
	    				$Attribute = $m->getAttribute(6);
	    				foreach ($Attribute as $a){
	    					$name = explode(',', $a['DisplayName']);
	    					$value = $_POST[ $a['FieldName'] ];
	    					if(  $value != '' ){
	    						$emailbody .= $name[0].':'.$value.'<br/>';
	    					}
	    				}
    				}
    				$placeholder = array('{$Time}'=>$MessageTime, '{$Content}'=>$emailbody);
    				send_sms($c['GUEST_BOOK_SMS_TO'], $GuestBookSmsTemplate, $placeholder);
    				//短信通知 结束============================================
    			}
    			//==========================================    			
    			$this->ajaxReturn(null, $success , 1);
    		}else{
    			session('guestbookcode', rand(1000, 9999) );
    			$this->ajaxReturn(null, L('GuestbookFail') , 0);
    		}
    	}else{
    		session('guestbookcode', rand(1000, 9999) );
    		$this->ajaxReturn(null, $m->getError() , 0);
    	}
    }
    
    private function _checkGuestbookIp(){
    	$MaxPerIp = 50; //定义每个ID每天最大留言数
    	$m = D('Admin/Guestbook');
    	$ip = get_client_ip();
    	$where = "to_days(MessageTime) = to_days(now()) and GuestIP='{$ip}'";
    	$n = $m->where($where)->count();
    	if( $n > $MaxPerIp){
    		session('guestbookcode', rand(1000, 9999) );
    		$this->ajaxReturn(null, L('GuestbookFail') , 0);
    	}
        if(IsDbReadonly()){
            $this->ajaxReturn(null, L('GuestbookFail') , 0);
        }
    }
    
    private function _resume(){
    	$JobID = intval($_GET['jobid']);
    	$tip = L('ChangeVerifycode');
    	//验证码
    	$ResumeCode = "<script type='text/javascript'>
    	function ChangeResumeCode(){
    	    var timenow = new Date().getTime();
    	    var obj = document.getElementById('ResumeCode');
    	    if( obj ) obj.src = '__GROUP__/public/ResumeCode/'+timenow;
        }
        </script>
	    <img  src='__GROUP__/public/ResumeCode/'  onclick='ChangeResumeCode()'
	    style='cursor:pointer;' id='ResumeCode'  align='absMiddle'  alt='$tip' title='$tip' />";
    	 
    	$this->assign('ResumeCode', $ResumeCode); 
    	$this->assign('ResumeAction', __URL__.'/ResumeAdd/l/'.LANG_SET);
    	
    	$m = D('Admin/Job');
    	$JobName = $m->where("JobID=$JobID")->getField('JobName');
    	$this->assign('JobName', $JobName);
    	$this->assign('JobID', $JobID);
    	 
    	if( session('?MemberID') ){
    		$this->assign('MemberID', (int)session('MemberID'));
    		$this->assign('MemberName', session('MemberName'));
    	}else{
    		$this->assign('MemberID', '0');
    		$this->assign('MemberName', '');
    	}
    }
    
    public function resumeAdd(){
    	header("Content-Type:text/html; charset=utf-8");
    	$_POST = YdInput::checkTextbox( $_POST ); //xss过滤
    	if( empty( $_POST['GuestName'] ) ){
            session('resumecode', rand(1000, 9999) );
    		$this->ajaxReturn(null, L('JobPeopleRequired') , 0);
    	}
    	
    	if( empty( $_POST['Telephone'] ) ){
            session('resumecode', rand(1000, 9999) );
    		$this->ajaxReturn(null, L('TelephoneRequired') , 0);
    	}
    	
    	$verifycode = $_POST['verifycode'];
    	if( empty($verifycode) ){
    		session('resumecode', rand(1000, 9999) );
    		$this->ajaxReturn(null, L('VerifyCodeRequired') , 0);
    	}
    
    	if( md5($verifycode) != session('resumecode')  ){
    		session('resumecode', rand(1000, 9999) );
    		$this->ajaxReturn(null, L('VerifyCodeError')  , 0);
    	}
    	$this->_checkResumeIp();
    	$m = D('Admin/Resume');
        session('resumecode', rand(1000, 9999) );
    	if( $m->create() ){
    		if( $this->MemberIsLogin() ){
    			$m->GuestID = (int)session('MemberID');
    		}else{
    			$m->GuestID = 0;
    		}
    		$m->Time = date('Y-m-d H:i:s');
    		$m->GuestIP = get_client_ip();
            $ResumeID = $m->add();
    		if($ResumeID>0){
                $m->sendResumeEmail($ResumeID);
    			$this->ajaxReturn(null, L('ResumeSuccess') , 1);
    		}else{
    			session('resumecode', rand(1000, 9999) );
    			$this->ajaxReturn(null, L('ResumeFail') , 0);
    		}
    	}else{
    		session('resumecode', rand(1000, 9999) );
    		$this->ajaxReturn(null, $m->getError() , 0);
    	}
    }
    
    private function _checkResumeIp(){
    	$MaxPerIp = 50; //定义每个ID每天最大留言数
    	$m = D('Admin/Resume');
    	$ip = get_client_ip();
    	$where = "to_days(Time) = to_days(now()) and GuestIP='{$ip}'";
    	$n = $m->where($where)->count();
    	if( $n > $MaxPerIp){
    		session('resumecode', rand(1000, 9999) );
    		$this->ajaxReturn(null, L('ResumeFail') , 0);
    	}
        if(IsDbReadonly()){
            $this->ajaxReturn(null, L('ResumeFail') , 0);
        }
    }
    
    //频道信息检索
    public function search(){
    	header("Content-Type:text/html; charset=utf-8");
    	$ChannelID = isset($_REQUEST['id']) ? intval( $_REQUEST['id'] ) : 0;
    	$Keywords = isset($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
    	
    	import("ORG.Util.Page");
    	$m = D('Admin/Info');
    	$Total = $m->getCount( $ChannelID, 1, 1, $Keywords);  //总记录数
    	$PageSize = $GLOBALS['Config']['SearchPageSize'];
    	$PageSize = ( $PageSize > 0 ) ? $PageSize : 20;
        $PageSize = intval($PageSize);
    	$Page = new Page($Total, $PageSize);
    	$Page->parameter = "&Keywords=$Keywords&id=$ChannelID";
    	$Page->rollPage = $GLOBALS['Config']['HomeRollPage'];
    	$ShowPage = $Page->show();
    	
    	$SitePath = $this->_getSitePath( 'search' );
    	$this->assign('SitePath', $SitePath);
    	
    	$this->assign('NowPage', $Page->getNowPage()); //分页条
    	$this->assign('Page', $ShowPage); //分页条
    	 
    	$this->assign('SearchWord', $Keywords);
    	$this->assign('Searchwords', $Keywords);
    	$this->assign('ChannelID', $ChannelID);
    	$this->display();
    }
    
    //用户发表评论
    public function commentAdd(){
    	header("Content-Type:text/html; charset=utf-8");
    	$c = &$GLOBALS['Config'];
    	switch( $c['COMMENT_ENABLE'] ){
    		case 0:  //禁止留言
    			$this->ajaxReturn(null, L('CommentForbidden') , 0);
    			break;
    		case 1: //允许匿名留言
    			break;
    		case 2: //允许会员留言
    			if( !$this->MemberIsLogin() ){
    				$this->ajaxReturn(null, L('CommentLevel') , 0);
    			}
    			break;
    	}
    	
    	$this->_checkCommentIp();
    	//是否购买商品后才能评论，购买后可发布多条评论
    	$MemberID = (int)session('MemberID');
    	$m = D('Admin/Info');
    	$b = $m->isGoods($_POST['InfoID']);
    	if($b && $c['COMMENT_BUY'] == 1){
    		$hasBuy = $m->hasBuy($_POST['InfoID'], $MemberID);
    		if(!$hasBuy){
    			$this->ajaxReturn(null, L('CommentNoBuy') , 0);
    		}
    	}
    	
    	if( $c['COMMENT_VERIFYCODE'] == 1){ //启用验证码
    		$verifycode = $_POST['verifycode'];
    		if( empty($verifycode) ){
    			session('commentcode', rand(1000, 9999) );
    			$this->ajaxReturn(null, L('VerifyCodeRequired') , 0);
    		}
    		if( md5($verifycode) != session('commentcode')  ){
    			session('commentcode', rand(1000, 9999) );
    			$this->ajaxReturn(null, L('VerifyCodeError')  , 0);
    		}
    	}
    	
    	$_POST = YdInput::checkTextbox( $_POST ); //xss过滤
    	$m = D('Admin/Comment');
    	if( $m->create() ){
    		if( $this->MemberIsLogin() ){
    			$m->GuestID = (int)session('MemberID');
    			$m->GuestName = session('MemberName');
    		}else{
    			$m->GuestID = 0;
    		}
    		$m->IsCheck = ($c['COMMENT_CHECK'] == 1) ? 0 : 1;
    		$m->CommentTime = date('Y-m-d H:i:s');
    		$m->GuestIP = get_client_ip();
    
    		if($m->add()){
    			$success = ($c['COMMENT_CHECK'] == 1) ? L('CommentSuccessNoCheck') : L('CommentSuccess');
    			$this->ajaxReturn(null, $success , 1);
    		}else{
    			session('commentcode', rand(1000, 9999) );
    			$this->ajaxReturn(null, L('CommentFail') , 0);
    		}
    	}else{
    		session('commentcode', rand(1000, 9999) );
    		$this->ajaxReturn(null, $m->getError() , 0);
    	}
    }
    
    private function _checkCommentIp(){
    	$MaxPerIp = 30; //定义每个ID每天最大留言数
    	$m = D('Admin/Comment');
    	$ip = get_client_ip();
    	$ip = addslashes(stripslashes($ip)); //过滤危险字符
    	$where = "to_days(CommentTime) = to_days(now()) and GuestIP='{$ip}'";
    	$n = $m->where($where)->count();
    	if( $n > $MaxPerIp){
    		session('commentcode', rand(1000, 9999) );
    		$this->ajaxReturn(null, L('CommentFail') , 0);
    	}
        if(IsDbReadonly()){
            $this->ajaxReturn(null, L('CommentFail') , 0);
        }
    }
    
    //获取频道信息
    private function _getChannel(){
    	$html = $_GET['id'];  //频道静态文件名称，不带扩展名
    	$m = D('Admin/Channel');
    	$this->_data = $m->findChannelByHtml( $html );
    	if( empty( $this->_data ) ) { //若频道不存在，则转向404页面
    		$this->_empty('channel');
    		exit();
    	}
    	$id = $this->_data['ChannelID'];
    	$Parent = $this->_data['Parent'];
    	
    	//计算特殊字段值start==================================================
    	//是否有阅读权限
    	$ReadLevel = $this->_data['ReadLevel'];
    	$ReadLevel= ( !empty($ReadLevel) || $Parent==0 ) ? $ReadLevel : get_read_level( $Parent );
    	$this->_data['HasReadLevel'] = has_read_level( $ReadLevel ) ? 1 : 0;
    	
    	//详细内容特殊标签解析
    	$this->_data['ChannelContent'] = ParseTag( $this->_data['ChannelContent'] );
    	tag('channel_content', $this->_data['ChannelContent']);
    	
    	//搜索引擎优化字段
    	if( empty($this->_data['Title']) ) {
    		$this->_data['Title'] = get_title( $Parent );
    	}
    	$this->_data['Title'] = YdInput::checkSeoString( $this->_data['Title'] );
    	
    	if( empty($this->_data['Keywords']) ){
    		$this->_data['Keywords'] = get_keywords( $Parent );
    	}
    	$this->_data['Keywords'] = YdInput::checkSeoString( $this->_data['Keywords'] );
    	
    	if( empty($this->_data['Description']) ){
    		$this->_data['Description'] = get_description( $Parent );
    	}
    	$this->_data['Description'] = YdInput::checkSeoString( $this->_data['Description'] );
    	
    	//其它
    	$this->_data['HasParent'] = ($Parent > 0 ) ? 1 : 0 ;  //是否有父频道
    	$this->_data['TopChannelID'] = ($Parent == 0) ? $id : $m->getTopChannel( $id ); //顶级频道ID
    	$this->_data['TopHasChild'] = ( $this->_data['HasChild'] == 1 ||  $Parent != 0 ) ? 1 : 0;
    	$this->_data['ChannelUrl'] = ChannelUrl($id, $this->_data['Html'], $this->_data['LinkUrl']);
    	$this->_data['FullChannelUrl'] = get_current_url().$this->_data['ChannelUrl'];
    	//计算特殊字段值end==================================================
    	$this->assign($this->_data);
    }
    
    
    //反馈提交
    public function feedbackAdd(){
    	header("Content-Type:text/html; charset=utf-8");
    	//处理checkbox的情况====================
    	foreach ($_POST as $k=>$v){
    		if( is_array($v) ){
    			$_POST[$k] = implode(',', $v);
    		}
    	}
    	//===================================
    	$c = &$GLOBALS['Config'];
    	if( $c['FEEDBACK_VERIFYCODE'] == 1){ //启用验证码
    		$verifycode = $_POST['verifycode'];
    		if( empty($verifycode) ){
    			session('feedbackcode', rand(1000, 9999) );
    			$this->ajaxReturn(null, L('VerifyCodeRequired') , 0);
    		}
    		if( md5($verifycode) != session('feedbackcode')  ){
    			session('feedbackcode', rand(1000, 9999) );
    			$this->ajaxReturn(null, L('VerifyCodeError')  , 0);
    		}
    	}
    	$this->_checkFeedbackIp();
    	$_POST = YdInput::checkTextbox( $_POST ); //xss过滤
    	$m = D('Admin/Info');
        session('feedbackcode', rand(1000, 9999) );
    	if( $m->create() ){
    		$m->MemberID =$this->MemberIsLogin() ? (int)session('MemberID') : 0;
    		$m->IsCheck = ($c['FEEDBACK_CHECK'] == 1) ? 0 : 1;
    		$m->IsEnable = 1;
    		$InfoTime = date('Y-m-d H:i:s');
    		$m->InfoTime = $InfoTime;
    		$m->InfoIP = get_client_ip();
    		$result = $m->add();
    		if($result){
                $emailbody = '';
    			if( $c['FEEDBACK_SENDEMAIL'] == 1){
    				//获取留言内容===============================================================
    				$m = D('Admin/Attribute');
    				$Attribute = $m->getAttribute(37);
    				$Group = $m->getGroup(37);
    				foreach($Group as $g){
    					foreach ($Attribute as $a){
    						if( $g['AttributeID'] == $a['GroupID']){
	    						$name = explode(',', $a['DisplayName']);
	    						$value = $_POST[ $a['FieldName'] ];
	    						if(  $value != '' ){
	    							$emailbody .= $name[0].':'.$value.'<br/>';
	    						}
    						}
    					}
    				}
    				$emailbody .= '时间:'.$InfoTime;
    				$emailtitle = $c['FEEDBACK_EMAIL_TITLE'];
    				$emailto = empty($c['FEEDBACK_EMAIL']) ? $c['EMAIL'] : $c['FEEDBACK_EMAIL'];
    				//=======================================================＝===============
    				//提交留言时自动发送电子邮件
    				$b = sendwebmail($emailto, $emailtitle, $emailbody);
    			}
    			
    			//短信通知 开始=====================================
    			if( $c['FEEDBACK_SMS'] == 1){
    				//获取留言内容
    				$SmsTemplate = $c['FEEDBACK_SMS_TEMPLATE'];
    				if( empty( $emailbody) && stripos($SmsTemplate, '{$Content}')){
    					$m = D('Admin/Attribute');
    					$Attribute = $m->getAttribute(37);
    					foreach ($Attribute as $a){
    						$name = explode(',', $a['DisplayName']);
    						$value = $_POST[ $a['FieldName'] ];
    						if(  $value != '' ){
    							$emailbody .= $name[0].':'.$value.'<br/>';
    						}
    					}
    				}
    				$placeholder = array('{$Time}'=>$InfoTime, '{$Content}'=>$emailbody);
    				send_sms($c['FEEDBACK_SMS_TO'], $SmsTemplate, $placeholder);
    				//短信通知 结束============================================
    			}
    			//==========================================
    			
    			$this->ajaxReturn(null, L('FeedbackSuccess') , 1);
    		}else{
    			session('feedbackcode', rand(1000, 9999) );
    			$this->ajaxReturn(null, L('FeedbackFail') , 0);
    		}
    	}else{
    		session('feedbackcode', rand(1000, 9999) );
    		$this->ajaxReturn(null, $m->getError() , 0);
    	}
    }
    
    private function _checkFeedbackIp(){
    	$MaxPerIp = 50; //定义每个ID每天最大发布数
    	$m = D('Admin/Info');
    	$ip = get_client_ip();
    	$ip = addslashes(stripslashes($ip)); //过滤危险字符
        $ChannelID = intval($_POST['ChannelID']); //当前频道ID
    	$where = "to_days(InfoTime) = to_days(now()) and InfoIP='{$ip}' AND ChannelID={$ChannelID}";
    	$n = $m->where($where)->count();
    	if( $n > $MaxPerIp){
    		session('feedbackcode', rand(1000, 9999) );
    		$this->ajaxReturn(null, L('FeedbackFail') , 0);
    	}

    	if(IsDbReadonly()){
            $this->ajaxReturn(null, L('FeedbackFail') , 0);
        }
    }
    
    //邮件订阅
    public function subscibe(){
    	header("Content-Type:text/html; charset=utf-8");
    	if( !isset($_POST['MailClassID'])){
    		$c = D('Admin/MailClass');
    		$_POST['MailClassID'] = $c->getFirstClassID(); //如果不存在，则添加到第一个分类 
    	}else{
            $_POST['MailClassID'] = intval($_POST['MailClassID']);
        }
    	$_POST = YdInput::checkTextbox( $_POST ); //xss过滤
    	$m = D('Admin/Mail');
    	if( $m->create() ){
    		$m->AddTime = date('Y-m-d H:i:s');
    		if( $m->add() ){
    			$this->ajaxReturn(null, L('SubscibeSuccess') , 1);
    		}else{
    			$this->ajaxReturn(null, L('SubscibeFail') , 0);
    		}
    	}else{
    		$this->ajaxReturn(null, $m->getError() , 0);
    	}
    }
    
    public function test(){
        header("Content-Type:text/html; charset=utf-8");
    }
    
    /**
     * 用于临时测试，发布以后删除里面的代码
     */
    public function ut(){
    	header("Content-Type:text/html; charset=utf-8");
    }
    
    //保存用户投票
    public function voteAdd(){
    	header("Content-Type:text/html; charset=utf-8");
    	$item = $_REQUEST['item'];
    	$appid = intval($_REQUEST['appid']);
    	$fromUser = !empty($this->_fromUser) ? $this->_fromUser : get_client_ip();
    	$_REQUEST = YdInput::checkTextbox( $_REQUEST );
    	$m = D('Admin/WxVote');
    	if($m->hasVoted($appid, $fromUser) ){
    		$this->ajaxReturn(null, '', 2);
    	}
    	
    	if(false === $m->submitVote($appid, $item, $fromUser)){
    		$this->ajaxReturn(null, '', 0);
    	}else{
    		$this->ajaxReturn(null, '', 1);
    	}
    }
}