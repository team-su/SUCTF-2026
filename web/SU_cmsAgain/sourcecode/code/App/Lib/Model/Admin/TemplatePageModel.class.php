<?php
//模板页面
class TemplatePageModel extends Model{
    /**
     * 获取指定模板的所有页面
     */
    function getTemplatePage($p=array()){
        $TemplateID = intval($p['TemplateID']);
        $where = "TemplateID={$TemplateID}";
        if(isset($p['Field'])){
            $field = $p['Field'];
        }else{
            $field = "TemplatePageID,TemplatePageName,TemplatePagePicture,TemplatePageType";
        }
        //按类型升序，保证首页在最前面
        $this->order("TemplatePageType ASC");
		$field = YdInput::checkTableField($field);
        $data = $this->where($where)->field($field)->select();
        AddResDomain($data, 'TemplatePagePicture');
        return $data;
    }

    /**
     * 查看模板页面数据
     */
    function findTemplatePage($TemplatePageID, $p = array() ){
        $where['TemplatePageID'] = intval($TemplatePageID);
        $result = $this->where($where)->find();
        if(!empty($result)){
            $type = $result['TemplatePageType'];
            //允许装修类型类别，1首页，2分类页，3列表页，4内容页，5联系我们，10会员中心是不允许装修的，组件列表页必须显示空
            $map = array(99=>'1', 1=>'1');
            $result['CanAddComponent'] = isset($map[$type]) ? 1 : 0;
            //表示获取模板相关数据并解析
            if(isset($p['HasTemplateData']) && $p['HasTemplateData']==1){
                //模板装修都需要返回Tabbar ，所有模板页面都复用首页的Tabbar，Tabbar仅保存在首页
                if($p['HasTabbar']==1){
                    $tabbar = $this->getTabbar($result['TemplateID']);
                }else{ //小程序端从不返回Tabbar（在配置里返回）
                    $tabbar = false;
                }
                //APP端模板编辑的永远是草稿数据
                import('@.Common.YdTemplate');
                $t = new YdTemplate();
                $result['TemplatePageData'] = $t->parsePageData($result['TemplatePageContentDraft'], array('Tabbar'=>$tabbar));
                $this->_setSystemComponent($result['TemplatePageData'], $result['TemplatePageType']);
                unset($result['TemplatePageContentDraft']);
                //分类页，列表页的Components只有1个
                $Components = &$result['TemplatePageData']['Components'];
                if( ($type==2 || $type==3) && count($Components)>1){
                    foreach($Components as $k=>$v){
                        if($k>0) unset($Components[$k]);
                    }
                }
            }
        }
        return $result;
    }

    /**
     *  设置系统组件，系统组件必须返回一个值
     */
    private function _setSystemComponent(&$PageData, $PageType){
        if(!isset($PageData['Parameters'])){
            $PageData['Parameters'] = array();
        }
        $PageType = intval($PageType);
        $map = array(2=>'category', 3=>'list', 4=>'detail', 5=>'contact', 10=>'member');
        if(!isset($PageData['Components'])){
            if(isset($map[$PageType])){
                $PageData['Components'] = array(
                    array('Type'=>$map[$PageType])
                );
            }else{
                $PageData['Components'] = array();
            }
        }
    }

    /**
     * 删除模板页面
     */
    function deleteTemplatePage($TemplatePageID){
        if( !is_numeric($TemplatePageID) ) return false;
        $where = "TemplatePageID={$TemplatePageID}";
        $result = $this->where($where)->delete();
        return $result;
    }

    /**
     * 保存店铺模板装修
     * IsDraft：是否是草稿
     */
    function saveTemplatePage($TemplatePageID, $data){
        $TemplatePageID = intval($TemplatePageID);
        $dataToUpdate = array();
        //保存内容
        if(isset($data['TemplatePageContent'])){
            $field = 'TemplatePageContent';
            //替换其中的图片地址
            $webUrl = get_current_url();
            $TemplatePageContent = str_ireplace($webUrl, '', $data['TemplatePageContent']);
            $dataToUpdate[$field.'Draft'] = $TemplatePageContent; //保存草稿
            if(0 == $data['IsDraft']){ //保存并发布
                $dataToUpdate[$field] = $TemplatePageContent;
            }
            $this->_saveTabbar($data);
        }
        //保存页面名称
        if(isset($data['TemplatePageName'])){
            $dataToUpdate['TemplatePageName'] = $data['TemplatePageName'];
        }
        $where ="TemplatePageID={$TemplatePageID}";
        $result  = $this->where($where)->setField($dataToUpdate);
        return $result;
    }

    /**
     * 保存Tabbar的值
     * Tabbar仅仅保存在首页中
     */
    private function _saveTabbar(&$data){
        if(1==$data['TemplatePageType']) return; //如果是首页立即返回
        $pageData = json_decode($data['TemplatePageContent'], true);
        if(!isset($pageData['Tabbar'])) return;
        //获取并替换首页Tabbar数据========================================
        $data['TemplateID'] = intval($data['TemplateID']);
        $where = "TemplateID={$data['TemplateID']} AND TemplatePageType=1";
        $PageContentDraft = $this->where($where)->getField('TemplatePageContentDraft');
        //更新首页Tabbar，只有首页才有tabbar数据
        $indexPageData = json_decode($PageContentDraft, true);
        $indexPageData['Tabbar'] = $pageData['Tabbar'];
        //========================================================

        //重新保存首页
        $indexPageContent = json_encode($indexPageData);
        $result  = $this->where($where)->setField('TemplatePageContentDraft', $indexPageContent);
        //删除原来的tabbar数据，非首页不保存tabbar数据
        unset($pageData['Tabbar']);
        $data['TemplatePageContent'] =  json_encode($pageData);
        return $result;
    }

    /**
     * 返回tabbar数据
     * $isDraft：是否获取草稿状态的tabbar，预览模式调用的就是草稿
     */
    function getTabbar($TemplateID=false, $returnString=false, $isDraft=true, $XcxType=0){
        if(empty($TemplateID)){ //获取默认模板
            $m = D('Admin/Template');
            $TemplateID = $m->getDefaultTemplateID($XcxType);
        }
        $TemplateID = intval($TemplateID);
        $where = "TemplateID={$TemplateID} AND TemplatePageType=1";
        $contentField = $isDraft ? 'TemplatePageContentDraft' : 'TemplatePageContent';
        $content = $this->where($where)->getField($contentField);
        $data = json_decode($content, true);
        if($returnString){
            return json_encode($data['Tabbar']);
        }else{
            return $data['Tabbar'];
        }
    }

    /**
     * 添加自定义默认页面
     */
    function addTemplatePage($data){
        $dataToAdd = array();
        $dataToAdd['TemplateID']= intval($data['TemplateID']);
        $dataToAdd['TemplatePageName'] = $data['TemplatePageName'];
        $dataToAdd['TemplatePageType'] = 99;  //99表示自定义页面
        $result  = $this->add($dataToAdd);
        return $result;
    }

    /**
     *  给模板添加默认系统页面（如：首页、联系我们、详情页面等）
     * @param $TemplateID
     */
    function addDefaultTemplatePage($TemplateID){
        $TemplateID = intval($TemplateID);
        $pagelist = array();
        $pagelist[] = array(
            'TemplateID'=>$TemplateID, 'TemplatePageName'=>'首页', 'TemplatePageType'=>1,
            'TemplatePageContentDraft'=>''
        );
        $pagelist[] = array(
            'TemplateID'=>$TemplateID, 'TemplatePageName'=>'分类页', 'TemplatePageType'=>2,
            'TemplatePageContentDraft'=>''
        );
        $pagelist[] = array(
            'TemplateID'=>$TemplateID, 'TemplatePageName'=>'列表页', 'TemplatePageType'=>3,
            'TemplatePageContentDraft'=>''
        );
        $pagelist[] = array(
            'TemplateID'=>$TemplateID, 'TemplatePageName'=>'详情页', 'TemplatePageType'=>4,
            'TemplatePageContentDraft'=>''
        );
        //联系我们页面=============================================================
        $pageContent = array('Components'=>array(
            array('Type'=>'contact', 'Order'=>0),
        ));
        $pagelist[] = array(
            'TemplateID'=>$TemplateID, 'TemplatePageName'=>'联系我们', 'TemplatePageType'=>5,
            'TemplatePageContentDraft'=>json_encode($pageContent)
        );
        //=====================================================================
        $result = $this->addAll($pagelist);
        return $result;
    }

    /**
     * 获取模板页面内容
     */
    function findTemplatePageContent($TemplatePageID, $IsPreview=0){
        $TemplatePageID = intval($TemplatePageID);
        $where ="TemplatePageID={$TemplatePageID}";
        $field = $IsPreview ? 'TemplatePageContentDraft' : 'TemplatePageContent';
        $result = $this->where($where)->getField($field);
        return $result;
    }

    /**
     * 根据模板页面类型，获取当前模板页面ID
     */
    function findDefaultTemplatePageID($TemplatePageType, $TemplateID=0, $XcxType=0){
        $m = D('Admin/Template');
        if(empty($TemplateID)){ //$TemplateID大于0表示预览
            $TemplateID = $m->getDefaultTemplateID($XcxType);
        }
        $TemplateID = intval($TemplateID);
        $TemplatePageType = intval($TemplatePageType);
        $where ="TemplateID={$TemplateID} AND TemplatePageType={$TemplatePageType}";
        $id = $this->where($where)->getField('TemplatePageID');
        return $id;
    }

    /**
     * 获取模板列表页样式
     */
    function findListPageStyle($TemplateID){
        $TemplateID = intval($TemplateID);
        $TemplatePageID = $this->findDefaultTemplatePageID(3, $TemplateID);
        $where ="TemplateID={$TemplateID} AND TemplatePageID={$TemplatePageID}";
        //当前是装修，所以用于读取的是草稿数据
        $content = $this->where($where)->getField('TemplatePageContentDraft');
        if(empty($content)) return '';
        $temp = json_decode($content, true);
        $data = array_merge($temp['Parameters'], $temp['Components'][0]);
        unset($data['Type'], $data['DataList'], $data['Order'], $data['Title']);
        return $data;
    }

    /**
     * 发布当前模板所有页面
     */
    function publishAllTemplatePage($TemplateID){
        $TemplateID = intval($TemplateID);
        $prefix = $this->tablePrefix;
        $sql = "Update {$prefix}template_page SET TemplatePageContent=TemplatePageContentDraft
        WHERE TemplateID={$TemplateID}";
        $result = $this->execute($sql);
        return $result;
    }
}
