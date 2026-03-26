<?php

/**
 * 多端小程序模板
 */
class TemplateModel extends Model{
    /**
     * 获取模板
     */
	function getTemplate($p=array()){
        $where = "";
        $this->order("IsDefault desc, TemplateOrder asc, TemplateID desc");
		$result = $this->where($where)->select();
		if(!empty($result)){
            AddResDomain($result, 'TemplatePicture');
            import('@.Common.YdTemplate');
            $map = YdTemplate::getXcxType(true);
            foreach($result as $k=>$v){
                $key = $v['XcxType'];
                $name = isset($map[$key]) ? $map[$key].'专用模板' : '';
                $result[$k]['XcxTypeName'] = $name;
            }
        }
		return $result;
	}

    /**
     * 获取模板总数
     */
	function getTotalTemplateCount(){
        $n = $this->count();
        if(empty($n)) $n=0;
        return $n;
    }

    /**
     * 查看模板
     */
	function findTemplate($TemplateID, $p = array() ){
		$where['TemplateID'] = intval($TemplateID);
		$result = $this->where($where)->find();
        //表示获取模板的所有页面
        if(isset($p['HasTemplatePage']) && $p['HasTemplatePage']==1){
           $m = D('Admin/TemplatePage');
           $params = array();
           $params['TemplateID'] = $TemplateID;
            $result['TemplatePages'] = $m->getTemplatePage($params);
        }
		return $result;
	}

    /**
     *  删除模板
     */
	function deleteTemplate($TemplateID){
	    if( !is_numeric($TemplateID) ) return false;
	    $where = "TemplateID={$TemplateID}";
		$result = $this->where($where)->delete();
		if($result>0){
		    //删除模板的同时还必须删除页面
            $m = D('Admin/TemplatePage');
            $result = $m->where($where)->delete();
        }
		return $result;
	}

    /**
     * 设为默认模板
     */
    function setDefaultTemplate($TemplateID){
        $TemplateID = intval($TemplateID);
        $where = "TemplateID={$TemplateID}";
        $result = $this->where($where)->setField('IsDefault', 1);
        if(false !== $result){
            $where ="TemplateID!={$TemplateID}";
            $result = $this->where($where)->setField('IsDefault', 0);
        }
        return $result;
    }

    /**
     * 模板备份
     */
    function backupTemplate($TemplateID){
        $TemplateID = intval($TemplateID);
        $where ="TemplateID={$TemplateID}";
        $this->field('TemplateID,IsDefault,CreatTime', true);
        $data = $this->where($where)->find();
        if(empty($data)) return false;
        $data['IsDefault'] = 0; //备份的模板一定不是当前默认
        $data['TemplateName']  = "{$data['TemplateName']}_备份";
        $data['CreateTime'] = date("Y-m-d H:i:s");
        $data['IsEnable'] = 1;
        $this->startTrans();
        $result  = $this->add($data);
        if($result > 0){
            $NewTemplateID = $result;
            //备份模板页面
            $m = D('Admin/TemplatePage');
            $m->field('TemplatePageID', true);
            $result = $m->where($where)->select();
            if(!empty($result)){
                //备份模板，必须修改所属模板ID
                foreach($result as $k=>$v){
                    $result[$k]['TemplateID'] = $NewTemplateID;
                }
                $result = $m->addAll($result);
            }
        }
        if(false != $result){
            $this->commit();
        }else{
            $this->rollback();
        }
        return $result;
    }

    /**
     * 修改模板
     */
    function modifyTemplate($data){
        $TemplateID = $data['TemplateID'];
        if( !is_numeric($TemplateID) ) return false;
        $XcxType = intval($data['XcxType']);
        $where = "TemplateID={$TemplateID}";
        $dataToUpdate = array();
        $dataToUpdate['TemplateName'] = $data['TemplateName'];
        $dataToUpdate['TemplatePicture'] = app_remove_domain($data['TemplatePicture']);
        $dataToUpdate['ThemeColor'] = $data['ThemeColor'];
        $dataToUpdate['TemplateOrder'] = $data['TemplateOrder'];
        $dataToUpdate['XcxType'] = $XcxType;
        $result = $this->where($where)->setField($dataToUpdate);
        //同一小程序类型的模板只允许一个
        if(!empty($result) && $XcxType>0){
            $where = "TemplateID!={$TemplateID} AND XcxType={$XcxType}";
            $this->where($where)->setField('XcxType', 0);
        }
        return $result;
    }

    /**
     * 获取默认模板ID
     */
    function getDefaultTemplateID($XcxType=0){
        $XcxType = intval($XcxType);
        $id = 0;
        if($XcxType > 0){
            $where = "XcxType={$XcxType}";
            $id = $this->where($where)->getField('TemplateID');
        }
        if(empty($id)){
            $where = "IsDefault=1";
            $id = $this->where($where)->getField('TemplateID');
        }
        return $id;
    }

    /**
     * 创建一个空模板
     */
    function createTemplate($data){
        //如果当前没有默认模板，就创建为默认模板
        $n = $this->where('IsDefault=1')->count();
        $IsDefault = empty($n) ? 1 : 0;
        $dataToAdd = array();
        $dataToAdd['TemplateName'] = $data['TemplateName'];
        $dataToAdd['IsDefault'] = $IsDefault;
        $dataToAdd['CreateTime'] = date("Y-m-d H:i:s");
        $dataToAdd['TemplateOrder'] = 99;
        $dataToAdd['TemplatePicture'] = ''; //默认为空
        $dataToAdd['IsEnable'] = 1;
        $this->startTrans();
        $result = $this->add($dataToAdd);
        if($result > 0){
            $TemplateID = $result;
            $m = D('Admin/TemplatePage');
            $result = $m->addDefaultTemplatePage($TemplateID);
        }else{
            $TemplateID = 0;
        }
        if(false !== $result){
            $dataToAdd['TemplateID'] = $TemplateID;
            $result = $dataToAdd;
            $this->commit();
        }else{
            $this->rollback();
        }
        return $result;
    }

    /**
     * 获取模板主题颜色
     */
    function getThemeColor($TemplateID, $XcxType=0){
        if(empty($TemplateID)){
            $TemplateID = $this->getDefaultTemplateID($XcxType);
        }
        $where['TemplateID'] = intval($TemplateID);
        $color = $this->where($where)->getField('ThemeColor');
        return $color;
    }

    function templateNameExist($TemplateName){
        $where['TemplateName'] = $TemplateName;
        $id = $this->where($where)->getField('TemplateID');
        if($id>0){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 安装模板
     */
    function installTemplate($data, $TemplateNo){
        if(empty($data) || empty($data['TemplatePages'])) return false;
        $n = $this->where('IsDefault=1')->count();
        $IsDefault = empty($n) ? 1 : 0;
        $this->startTrans();
        $dataToAdd = array();
        $dataToAdd['TemplateName'] = $data['TemplateName'];
        $dataToAdd['TemplatePicture'] = $data['TemplatePicture'];
        $dataToAdd['ThemeColor'] = $data['ThemeColor'];
        $dataToAdd['IsDefault'] = $IsDefault;
        $dataToAdd['CreateTime'] = date("Y-m-d H:i:s");
        $dataToAdd['TemplateOrder'] = $data['TemplateOrder'];
        $dataToAdd['IsEnable'] = 1;
        $result = $this->add($dataToAdd);
        if($result > 0){
            import("@.Common.YdTemplate");
            $t = new YdTemplate();
            $TemplateID = $result;
            $allPages = &$data['TemplatePages'];
            foreach($allPages as $k=>$v){
                $allPages[$k]['TemplateID'] = $TemplateID;
                $content = $t->extractTemplatePageImage($v['TemplatePageContent'], $TemplateNo);
                $allPages[$k]['TemplatePageContentDraft'] = $content;
                $allPages[$k]['TemplatePageContent'] = $content;
            }
            $m = D('Admin/TemplatePage');
            $result = $m->addAll($allPages);
            if(false!==$result){
                $this->commit();
                $this->updatePageLink($TemplateID);
            }else{
                $this->rollback();
            }
        }
        return $result;
    }

    /**
     * 更新链接页面
     */
    private function updatePageLink($TemplateID){
        $TemplateID = intval($TemplateID);
        $m = D('Admin/TemplatePage');
        $where = "TemplateID={$TemplateID}";
        $field = 'TemplatePageID,TemplatePageContent,TemplatePageType';
        $data= $m->where($where)->field($field)->select();
        $map = array();
        foreach($data as $v){
            $key = $v['TemplatePageType'];
            if($key != 99){
                $map[$key] = $v['TemplatePageID'];
            }
        }

        foreach($data as $v){
            $where = "TemplatePageID={$v['TemplatePageID']}";
            $content = $this->setPageLink($v['TemplatePageContent'], $map);
            if(!empty($content)){
                $dataToUpdate = array();
                $dataToUpdate['TemplatePageContent'] = $content;
                $dataToUpdate['TemplatePageContentDraft'] = $content;
                $m->where($where)->setField($dataToUpdate);
            }
        }
    }

    private function setPageLink($content, &$map){
        $data = json_decode($content ,true);
        if(empty($data)) return false;
        //普通组件
        $AllComponents = &$data['Components'];
        foreach($AllComponents as $k=>$v){
            $this->setPageLinkData($AllComponents[$k], $map);
            if(!empty($v['DataList'])){
                $dataList = &$AllComponents[$k]['DataList'];
                foreach($dataList as $m=>$n){
                    $this->setPageLinkData($dataList[$m], $map);
                }
            }
        }

        //特殊组件图片
        $ComponentsSpecial = &$data['ComponentsSpecial'];
        foreach($ComponentsSpecial as $k => $v){
            $this->setPageLinkData($ComponentsSpecial[$k], $map);
        }

        //工具栏
        $tabs = &$data['Tabbar']['DataList'];
        foreach($tabs as $k=>$v){
            $this->setPageLinkData($tabs[$k], $map);
        }

        $content = json_encode($data);
        return $content;
    }

    private function setPageLinkData(&$data, &$map){
        if(empty($data['PageType'])) return;
        $PageType= $data['PageType'];
        if(5==$data['LinkType'] && !empty($data['LinkValue']) && isset($map[$PageType])){
            $data['LinkValue'] = $map[$PageType];
        }
    }

    /**
     * 通过频道模型ID获取信息（主要用于列表页装修获取数据）
     */
    function getInfoByChannelModelID($ChannelModelID){
        if(!is_numeric($ChannelModelID)) return false;
        $field='InfoID,InfoTitle,InfoSContent,a.ChannelID,ChannelName,InfoTime,InfoHit,InfoPicture,InfoPrice,MarketPrice';
        $prefix = $this->tablePrefix;
        $this->table("{$prefix}info a");
        $this->join("{$prefix}channel b On a.ChannelID = b.ChannelID");
        $this->field($field)->order('InfoOrder ASC, InfoTime DESC')->limit('0,30');
        $where = get_language_where('a');
        $where .= " AND ChannelModelID={$ChannelModelID} AND a.IsEnable=1";
        $data = $this->where($where)->select();
        FriendDate($data, 'InfoTime', true);
        return $data;
    }
}
