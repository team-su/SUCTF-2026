<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class ResumeModel extends Model{
	protected $_validate = array(
			array('JobID', 'require', '{%JobNameRequired}'),
	);
	
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getResume($offset = -1, $length = -1, $GuestID = -1, $JobID=-1, $Keywords=''){
		$this->field('b.MemberName,c.*,a.*');
		$this->table($this->tablePrefix.'resume a');
		$this->join('Left Join '.$this->tablePrefix.'member b On a.GuestID = b.MemberID');
		$this->join('Left Join '.$this->tablePrefix.'job c On a.JobID = c.JobID');
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);	
		}
		$where = get_language_where('a');
		if( $GuestID != -1){
			$GuestID = intval($GuestID);
			$where .= " and a.GuestID=$GuestID";
		}
		
		if( $JobID != -1){
			$JobID = intval($JobID);
			$where .= " and a.JobID=$JobID";
		}
        if(!empty($Keywords) ){
            $Keywords = YdInput::checkKeyword( $Keywords );
            $where .= " and (a.GuestName like '%{$Keywords}%' OR a.Telephone like '%{$Keywords}%')";
        }

		$result = $this->where($where)->order('a.ResumeID desc')->select();
		return $result;
	}
	
	function findResume($ResumeID,  $GuestID = -1){
		$ResumeID = intval($ResumeID);
		$this->field('b.MemberName,c.JobName,c.ReceiveEmail,a.*');
		$this->table($this->tablePrefix.'resume a');
		$this->join('Left Join '.$this->tablePrefix.'member b On a.GuestID = b.MemberID');
		$this->join('Left Join '.$this->tablePrefix.'job c On a.JobID = c.JobID');
		$where = " a.ResumeID=$ResumeID";
		if(is_numeric($GuestID) && $GuestID != -1){
            $where .= " and GuestID=$GuestID";
        }
		$result = $this->where($where)->order('a.ResumeID desc')->find();
		return $result;
	}
	
	/**
	 * 批量删除简历
	 * @param int $id
	 * @param int $GuestID
	 */
	function batchDelResume( $id = array(),  $GuestID = -1){
		$id = YdInput::filterCommaNum($id);
		$where = 'ResumeID in('.implode(',', $id).')';
		if( $GuestID != -1){
			$GuestID = intval($GuestID);
			$where .= " and GuestID=$GuestID";
		}
		$result = $this->where($where)->delete();
		return $result;
	}
	
	function getResumeCount($JobID=-1){
		$where = get_language_where();
		if($JobID != -1){
			$JobID = intval($JobID);
			$where .= " and JobID=$JobID";
		}
		$n = $this->where($where)->count();
		return $n;
	}

	function sendResumeEmail($ResumeID){
        $ResumeID = intval($ResumeID);
        $c = &$GLOBALS['Config'];
        if(empty($c['JOB_SENDEMAIL'])) return true;
        $data = $this->findResume($ResumeID);
        if(empty($data)) return false;
        //邮件标题
        $emailtitle = $c['JOB_EMAIL_TITLE'];
        if(empty($emailtitle)) return false;
        $emailtitle = str_ireplace('{$Name}', $data['GuestName'], $emailtitle);
        $emailtitle = str_ireplace('{$JobName}', $data['JobName'], $emailtitle);
        //邮件内容
        $emailbody = $this->_getResumeContent($data);
        if(!empty($data['ReceiveEmail'])){
            $emailto = $data['ReceiveEmail'];
        }else{
            $emailto = empty($c['JOB_EMAIL']) ? $c['EMAIL'] : $c['JOB_EMAIL'];
        }
        if(empty($emailto)) return false;
        //发送邮件
        $b = sendwebmail($emailto, $emailtitle, $emailbody);
        if(false===$b){
            $errMsg = PHP_MAILER_ERROR;
        }
        return $b;
    }

    private function _getResumeContent($data){
	    $color = "#000";
	    $fontsize = "15px";
	    $html = "<style>
	                    .resume-container{ width:720px; padding:10px 0; text-align:center;  margin: 0 auto;}
	                    .resume-container .paddingright{ padding-right: 12px; }
                		.resume-table {width:100%; border:2px solid {$color}; border-collapse:collapse;border-spacing:0; outline:0;}
                		.resume-table caption{font-size: 25px; padding: 10px 0; font-weight:normal;letter-spacing: 6px;}
                		.resume-table th, .resume-table td{ border:1px solid {$color}; text-align:center; padding:6px 5px;  color:#000; font-size:{$fontsize};}
                		.resume-table th{ font-weight: bold; }
                        .resume-table td { }
                        .resume-container .red{ color: red; }
                        .resume-container .detail{ line-height:1.7em; min-height:20em; overflow:hidden; word-break: break-all;word-wrap:break-word;text-align:left; }
                        .resume-bottom{font-size:12px; padding:6px 0; text-align:right;}
                </style>
	          <div class='resume-container'>
                  <table align='center' class='resume-table'>
                        <caption>个人简历</caption>
                        <tr>
                              <th style='width:100px; '>姓名</th><td style='width:150px;'>{$data['GuestName']}</td>
                              <th style='width:100px;'>性别</th><td style='width:150px;'>{$data['Gender']}</td>
                              <td style='width:120px;' rowspan='5'>照片</td>
                        </tr>
                        <tr>
                               <th>出生年月</th><td>{$data['Birthday']}</td>
                               <th>民族</th><td>{$data['Ethnic']}</td>
                        </tr>
                        <tr>
                              <th>籍贯</th><td>{$data['Birthplace']}</td>
                              <th>政治面貌</th><td>{$data['Political']}</td>
                        </tr>
                        <tr>
                              <th>毕业院校</th><td>{$data['School']}</td>
                              <th>所学专业</th><td>{$data['Specialty']}</td>
                        </tr>
                        <tr>
                              <th>电子邮件</th><td>{$data['Email']}</td>
                              <th>最高学历</th><td>{$data['Education']}</td>
                        </tr>
                        <tr>
                              <th>联系电话</th><td>{$data['Telephone']}</td>
                              <th>待遇要求</th><td colspan='2'>{$data['Salary']}</td>
                        </tr>
                        <tr><th>住址</th><td colspan='4' style='text-align: left'>{$data['Address']}</td></tr>
                        <tr><th  style='text-align:left;padding-left:28px' colspan='5'>详细介绍</th></tr>
                        <tr><td  colspan='5'><div class='detail'>{$data['Detail']}</div></td></tr>
                  </table>
                  <div class='resume-bottom'>
                        应聘岗位：<b class='red paddingright'>{$data['JobName']}</b>
                        投递时间：<b class='red'>{$data['Time']}</b>
                   </div>
              </div>";
	    return $html;
    }
}
