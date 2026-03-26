<?php

/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class SiteModel extends Model {
    function getSiteList($offset = -1, $length = -1, $p = array()) {
        if (is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0) {
            $this->limit($offset . ',' . $length);
        }
        $where = $this->_getSiteListWhere($p);
        $result = $this->where($where)->order('SiteOrder asc, SiteID asc')->select();
        if (!empty($result)) {
            $protocal = get_current_protocal();
            foreach ($result as $k => $v) {
                $result[$k]['SiteUrl'] = $protocal . $v['SiteDomain'];
            }
        }
        return $result;
    }

    private function _getSiteListWhere($p = array()) {
        $where = array();
        if (isset($p['IsEnable'])) {
            $where['IsEnable'] = intval($p['IsEnable']);
        }
        if (!empty($p['Keywords'])) {
            $Keywords = YdInput::checkKeyword($p['Keywords']);
            $where['SiteName|SiteDomain|SiteTitle'] = array('like', "%{$Keywords}%");
        }
        return $where;
    }

    function getSiteListCount($p = array()) {
        $where = $this->_getSiteListWhere($p);
        $n = $this->where($where)->count();
        return $n;
    }

    function getSite($IsEnable = -1,$SiteList = "") {
        $where = array();
        if ($IsEnable != -1) {
            $where['IsEnable'] = intval($IsEnable);
        }
        $SiteList = YdInput::filterCommaNum($SiteList);
        if(!empty($SiteList)){
            $where['_string'] = "FIND_IN_SET(SiteID,'{$SiteList}')";
        }
        $result = $this->where($where)->order('SiteOrder asc, SiteID asc')->select();
        if (!empty($result)) {
            $protocal = get_current_protocal();
            foreach ($result as $k => $v) {
                    $result[$k]['SiteUrl'] = $protocal . $v['SiteDomain'];
            }
        }
        return $result;
    }

    function delSite($id = array(), $p = array()) {
        $id = YdInput::filterCommaNum($id);
        if (is_array($id)) {
            $where = 'SiteID in(' . implode(',', $id) . ')';
        } else {
            $where = "SiteID={$id}";
        }
        $result = $this->where($where)->delete();
        return $result;
    }

    function saveAll($data) {
        $n = is_array($data['SiteID']) ? count($data['SiteID']) : 0;
        for ($i = 0; $i < $n; $i++) {
            if (is_numeric($data['SiteID'][$i])) {
                $value = array();
                $value['SiteName'] = $data['SiteName'][$i];
                $value['SiteDomain'] = $data['SiteDomain'][$i];
                $value['SiteOrder'] = $data['SiteOrder'][$i];
                $value['SiteTitle'] = $data['SiteTitle'][$i];
                $value['SiteKeywords'] = $data['SiteKeywords'][$i];
                $value['SiteDescription'] = $data['SiteDescription'][$i];
                $this->where('SiteID=' . intval($data['SiteID'][$i]))->setField($value);
            }
        }
    }

    /**
     *  是否有重复的分站名称
     */
    function siteNameExist($SiteName=''){
        $where['SiteName'] = $SiteName;
        $SiteID = $this->where($where)->getField("SiteID");
        if($SiteID>0){
            return $SiteID;
        }else{
            return false;
        }
    }

    /**
     *  是否有重复的分站域名
     */
    function siteDomainExist($SiteDomain=''){
        $where['SiteDomain'] = $SiteDomain;
        $SiteID = $this->where($where)->getField("SiteID");
        if($SiteID>0){
            return $SiteID;
        }else{
            return false;
        }
    }
}
