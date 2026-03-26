<?php
/**
 * 访客
 */
class VisitorModel extends Model{
    /**
     * 增加访客
     */
    function addVisitor($data){
        $dataToAdd = array();
        $dataToAdd['VisitorIP'] = get_client_ip();
        $dataToAdd['VisitTime'] = date("Y-m-d H:i:s");
        $dataToAdd['XcxType'] = intval($data['XcxType']);
        $result = $this->add($dataToAdd);
        return $result;
    }

    /**
     * 按月分天 统计访问访客（次数，ip数）
     */
    function statVisitorByDay($p){
        $StartDate = YdInput::checkDatetime($p['StartDate']);
        $EndDate = YdInput::checkDatetime($p['EndDate']);
        $where = "VisitTime>='{$StartDate} 00:00:00' AND VisitTime<='{$EndDate} 23:59:59' ";
        if($p['XcxType']>0){
            $p['XcxType'] = intval($p['XcxType']);
            $where .= " AND XcxType={$p['XcxType']}";
        }
        //pv数=========================================================
        $field = "DATE_FORMAT(VisitTime,'%Y-%m-%d') as day, COUNT(VisitorID) AS n1, COUNT(DISTINCT VisitorIP) AS n2";
        $data  = $this->where($where)->field($field)->group("day")->select();
        $map = array();
        foreach ( $data as $v ) {
            $key = $v['day'];
            $map[$key] = $v;
        }
        $result = $this->_getChartParams();
        $result['tooltip'] = array('type'=>'showTip',  'trigger'=>'axis'); //显示提示信息
        $result['legend'] = array('data'=>array('访客PV数', '访客IP数'));
        $result['xAxis'] = array('type'=>'category', 'data'=>array());
        foreach($result['legend']['data'] as $k=>$v){
            $index = ($k==0) ? 0 : 1;
            $type = ($k==0) ? 'bar' : 'line';
            $result['series'][] = array('name'=>$v, 'type'=>$type, 'smooth'=>true,'yAxisIndex'=>$index, 'data'=>array());
            $result['yAxis'][] = array('type'=>'value');
        }
        $allDay = $this->allDay($StartDate, $EndDate);
        foreach ($allDay as $v){
            $key = $v['YearMonth'];
            $result['xAxis']['data'][] = $v['ShowName'];
            $result['series'][0]['data'][] = isset($map[$key] ) ? $map[$key]['n1'] : 0;
            $result['series'][1]['data'][] = isset($map[$key] ) ? $map[$key]['n2'] : 0;
        }
        return $result;
    }

    /**
     * 按月分天 统计访问访客（次数，ip数）
     */
    function statVisitorByHourMinute($p){
        $StartDate = YdInput::checkDatetime($p['EndDate']);
        $EndDate = YdInput::checkDatetime($p['EndDate']); //和开始时间一样，表示当天
        $where = "VisitTime>='{$StartDate} 00:00:00' AND VisitTime<='{$EndDate} 23:59:59' ";
        if($p['XcxType']>0){
            $p['XcxType'] = intval($p['XcxType']);
            $where .= " AND XcxType={$p['XcxType']}";
        }
        $step = 60; //每5分钟统计
        //pv数=========================================================
        //VisitTime因为只有当天，所以不需要年月日
        $field = "DATE_FORMAT(concat('{$StartDate}', ' ', HOUR ( VisitTime ), ':', floor( MINUTE ( VisitTime ) / {$step} ) * {$step} ),'%H:%i' ) AS hm";
        $field .= ",COUNT(VisitorID) AS n1, COUNT(DISTINCT VisitorIP) AS n2";
        $data  = $this->where($where)->field($field)->group("hm")->select();
        $map = array();
        foreach ( $data as $v ) {
            $key = $v['hm'];
            $map[$key] = $v;
        }
        $result = $this->_getChartParams();
        $result['tooltip'] = array('type'=>'showTip',  'trigger'=>'axis'); //显示提示信息
        $result['legend'] = array('data'=>array('访客PV数', '访客IP数'));
        $result['xAxis'] = array('type'=>'category', 'data'=>array());
        foreach($result['legend']['data'] as $k=>$v){
            $index = ($k==0) ? 0 : 1;
            $type = ($k==0) ? 'bar' : 'line';
            $result['series'][] = array('name'=>$v, 'type'=>$type, 'smooth'=>true,'yAxisIndex'=>$index, 'data'=>array());
            $result['yAxis'][] = array('type'=>'value');
        }
        $allMinute = $this->allHourMinute($EndDate, $step);
        foreach ($allMinute as $v){
            $key = $v['YearMonth'];
            $result['xAxis']['data'][] = $v['ShowName'];
            $result['series'][0]['data'][] = isset($map[$key] ) ? $map[$key]['n1'] : 0;
            $result['series'][1]['data'][] = isset($map[$key] ) ? $map[$key]['n2'] : 0;
        }
        return $result;
    }

    /**
     * 按小程序类型
     */
    function statVisitorByXcxType($p){
        $StartDate = YdInput::checkDatetime($p['StartDate']);
        $EndDate = YdInput::checkDatetime($p['EndDate']);
        $where = "VisitTime>='{$StartDate} 00:00:00' AND VisitTime<='{$EndDate} 23:59:59' ";
        //pv数=========================================================
        $field = "XcxType, COUNT(VisitorID) AS n1, COUNT(DISTINCT VisitorIP) AS n2";
        $data  = $this->where($where)->field($field)->group("XcxType")->select();

        $result = $this->_getChartParams();
        unset($result['title']); //必须删除title，否则subtitle无法显示
        $subtextStyle = array('fontSize'=>'18', 'color'=>'#333');
        $result['title'][] = array('subtext'=>'PV数统计', 'left'=>'16.67%', 'top'=>'85%', 'textAlign'=>'center', 'subtextStyle'=>$subtextStyle);
        $result['title'][] = array('subtext'=>'IP数统计', 'left'=>'50%', 'top'=>'85%', 'textAlign'=>'center', 'subtextStyle'=>$subtextStyle);
        $result['tooltip'] = array('trigger'=>'item', 'formatter'=>'{a} <br/>{b} : {c} ({d}%)');
        $charData = array();
        import('@.Common.YdTemplate');
        $map = YdTemplate::getXcxType(true);
        foreach ( $data as $v) {
            $key = $v['XcxType'];
            $name = $map[$key] ? $map[$key] : '其他';
            $result['legend']['data'][] = $name;
            $charData['n1'][] = array('value'=>$v['n1'], 'name'=>$name);
            $charData['n2'][] = array('value'=>$v['n2'], 'name'=>$name);
        }
        $result['series'] = array();
        $result['series'][] = array(
            'type'=>'pie', 'radius'=>'40%', 'name'=>'PV数', 'center'=>array('50%', '50%'),
            'data'=>$charData['n1'],
            'emphasis'=>array('itemStyle'=>array('shadowBlur'=>10, 'shadowOffsetX'=>0, 'shadowColor'=>'rgba(0, 0, 0, 0.5)')),
            'left'=>0, 'right'=>'66.6%', 'top'=>0, 'bottom'=>0
        );
        $result['series'][] = array(
            'type'=>'pie', 'radius'=>'40%', 'name'=>'IP数', 'center'=>array('50%', '50%'),
            'data'=>$charData['n2'],
            'emphasis'=>array('itemStyle'=>array('shadowBlur'=>10, 'shadowOffsetX'=>0, 'shadowColor'=>'rgba(0, 0, 0, 0.5)')),
            'left'=>'33.3%', 'right'=>'33.3%', 'top'=>0, 'bottom'=>0
        );
        return $result;
    }

    /**
     * 获取图表公共参数
     */
    private function _getChartParams(){
        $result = array();
        $result['title'] = array('text'=>'', 'left'=>'center');
        $result ['grid'] = array('left'=>60, 'right'=>60);
        $result['label'] = array('show'=>true, 'position'=>'top','color'=>'#333', 'fontWeight'=>'normal','fontSize'=>16 );
        $result['color'] = array('#4395f8', '#0aa344', '#c23531', '#3dccc0');
        $result['legend']['itemGap'] = 20;
        $result['legend']['textStyle'] = array('fontSize'=>16);
        return $result;
    }

    function getVisitorPVCount($StartDate, $EndDate, $XcxType=-1){
        $where = '1=1';
        if(is_numeric($XcxType) && $XcxType>0){
            $where .= " AND XcxType={$XcxType}";
        }

        $StartDate = YdInput::checkDatetime($StartDate);
        if(!empty($StartDate)){
            $where .= " AND VisitTime>='{$StartDate} 00:00:00'";
        }

        $EndDate = YdInput::checkDatetime($EndDate);
        if(!empty($EndDate)){
            $where .= " AND VisitTime<='{$EndDate} 23:59:59' ";
        }
        $n = $this->where($where)->count('VisitorID');
        if(empty($n)) $n=0;
        return $n;
    }

    function getVisitorIPCount($StartDate, $EndDate, $XcxType=-1){
        $where = "1=1";
        if(is_numeric($XcxType) && $XcxType>0){
            $where .= " AND XcxType={$XcxType}";
        }

        $StartDate = YdInput::checkDatetime($StartDate);
        if(!empty($StartDate)){
            $where .= " AND VisitTime>='{$StartDate} 00:00:00'";
        }

        $EndDate = YdInput::checkDatetime($EndDate);
        if(!empty($EndDate)){
            $where .= " AND VisitTime<='{$EndDate} 23:59:59' ";
        }
        $n = $this->where($where)->count('DISTINCT VisitorIP');
        if(empty($n)) $n=0;
        return $n;
    }

    /**
     * 获取天
     */
    private function allDay($StartDate, $EndDate){
        $totalDays = yd_date_diff($StartDate, $EndDate);
        $tsStart = strtotime($StartDate);
        $data = array();
        for($i=0; $i<=$totalDays; $i++){
            $ts = strtotime("+{$i} day", $tsStart);
            $data[] = array('ShowName'=>date("m-d", $ts), 'YearMonth'=>date("Y-m-d", $ts));
        }
        return $data;
    }

    /**
     *  每step分钟获取今天时分间隔数组
     * $date：格式：年-月-日
     */
    private function allHourMinute($date, $step=5){
        $currentDate = date('Y-m-d');
        if($currentDate==$date){ //如果是当天
            $EndHour = date('G'); //24小时制，没有前导0。如 0 到 23
            $EndMinute = intval(date('i')); //没有前导0。例如 00 到 59
        }else{
            $EndHour = 23;
            $EndMinute = 59;
        }
        $data = array();
        for($h=0; $h<=$EndHour; $h++){
            for($m=0; $m<=59; $m+=$step){
                if($h==$EndHour && $m>$EndMinute) break;
                $h1 = ($h<10) ? "0{$h}" : $h;
                $m1 = ($m<10) ? "0{$m}" : $m;
                $hm = "{$h1}:{$m1}";
                $data[] = array('ShowName' =>$hm, 'YearMonth' =>$hm);
            }
        }
        return $data;
    }
}

