<?php
class IndexAction extends HomeBaseAction {
    public function index(){
        header("Content-Type:text/html; charset=utf-8");
        $data = IndexChannelData();
        $ChannelID = $data['ChannelID'];

        $SitePath = $this->_getSitePath($ChannelID, 0, 0, $data['ChannelName']); //获取当前路径信息
        $this->assign('SitePath', $SitePath);

        //是否有阅读权限
        $data['ReadLevel'] = has_read_level( $data['ReadLevel'] ) ? 1 : 0;

        //频道优化设置, 会覆盖网站设置
        $Title = empty($data['Title']) ? $GLOBALS['Config']['TITLE'] : $data['Title'];
        $Keywords = empty($data['Keywords']) ? $GLOBALS['Config']['KEYWORDS'] : $data['Keywords'];
        $Description = empty($data['Description']) ? $GLOBALS['Config']['DESCRIPTION'] : $data['Description'];
        $data['Title'] = YdInput::checkSeoString($Title);
        $data['Keywords'] = YdInput::checkSeoString($Keywords);
        $data['Description'] = YdInput::checkSeoString($Description);

        $data['ChannelContent'] = ParseTag( $data['ChannelContent'] );
        tag('channel_content', $data['ChannelContent']);
        $data['HasParent'] = ( $data['Parent'] > 0 ) ? 1 : 0;
        $data['TopChannelID'] = $ChannelID;
        $data['TopHasChild'] = 0;
        $this->assign($data);

        $IndexTemplate = $data['IndexTemplate'];
        $IndexTemplate = str_ireplace('.html', '', trim($IndexTemplate) );
        $IndexTemplate = "Channel:$IndexTemplate";
        unset($data);
        $this->display($IndexTemplate);
    }
}