<?php
/**
 +---------------------------
 * 达梦数据库驱动类
 +---------------------------
 */
class DbDm extends Db{

    /**
     * 架构函数 读取数据库配置信息
     * @param array $config 数据库配置数组
     +----------------------------------------------------------
     */
    public function __construct($config='') {
        if ( !extension_loaded('dm') ) {
            throw_exception(L('_NOT_SUPPERT_').':dm');
        }
        if(!empty($config)) {
            $this->config   =   $config;
            if(empty($this->config['params'])) {
                $this->config['params'] =   array();
            }
        }
    }

    /**
     * 连接数据库方法
     */
    public function connect($config='',$linkNum=0) {
        if ( !isset($this->linkID[$linkNum]) ) {
            if(empty($config))  $config =   $this->config;
            $pconnect   = !empty($config['params']['persist'])? $config['params']['persist']:$this->pconnect;
            $conn = $pconnect ? 'dm_pconnect':'dm_connect';
            $this->linkID[$linkNum] =  $conn("{$config['hostname']}:{$config['hostport']}:{$config['database']}", $config['username'], $config['password']);
            if (!$this->linkID[$linkNum]){
                throw_exception($this->error(false));
            }else{
                //设置 dm 连接和语句的相关属性。参数2：1：conn2：stmt。12345：DSQL_ATTR_LOCAL_CODE
                dm_setoption($this->linkID[$linkNum],1,12345,1);
            }
            // 标记连接成功
            $this->connected    =   true;
            //注销数据库安全信息
            if(1 != C('DB_DEPLOY_TYPE')) unset($this->config);
        }
        return $this->linkID[$linkNum];
    }

    /**
     * 释放查询结果
     */
    public function free() {
        dm_free_result($this->queryID);
        $this->queryID = null;
    }

    /**
     * 执行查询 返回数据集
     * @param string $str  sql指令
     * @return mixed
     */
    public function query($str) {
        $this->initConnect(false);
        if ( !$this->_linkID ) return false;
        $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) $this->free();
        N('db_query',1);
        // 记录开始执行时间
        G('queryStartTime');
        //官方文档：PHP7.x用dm_exec替代。dm_query在5.5.0起已废弃
        $this->queryID = dm_exec($this->_linkID,$str);
        $this->debug();
        if ( false === $this->queryID ) {
            $this->error();
            return false;
        } else {
            $this->numRows = dm_num_rows($this->queryID);
            return $this->getAll();
        }
    }

    /**
     * 执行语句
     * @param string $str  sql指令
     * @return integer
     */
    public function execute($str) {
        $this->initConnect(true);
        if ( !$this->_linkID ) return false;
        $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) $this->free();
        N('db_write',1);
        // 记录开始执行时间
        G('queryStartTime');
        $result =   dm_exec($this->_linkID,$str);
        $this->debug();
        if ( false === $result ) {
            $this->error();
            return false;
        } else {
            $this->numRows = dm_affected_rows($this->_linkID);
            //只有插入数据才返回最后一次插入的id
            if(preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $str)) {
                $this->lastInsID = dm_insert_id($this->_linkID);
            }
            return $this->numRows;
        }
    }

    /**
     * 启动事务
     * @return void
     */
    public function startTrans() {
        $this->initConnect(true);
        if ( !$this->_linkID ) return false;
        //数据rollback 支持
        if ($this->transTimes == 0) {
            dm_exec($this->_linkID,'begin;');
        }
        $this->transTimes++;
        return ;
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @return boolen
     */
    public function commit() {
        if ($this->transTimes > 0) {
            $result = dm_exec($this->_linkID,'end;');
            if(!$result){
                throw_exception($this->error());
            }
            $this->transTimes = 0;
        }
        return true;
    }

    /**
     * 事务回滚
     * @return boolen
     */
    public function rollback() {
        if ($this->transTimes > 0) {
            $result = dm_exec($this->_linkID,'abort;');
            if(!$result){
                throw_exception($this->error());
            }
            $this->transTimes = 0;
        }
        return true;
    }

    /**
     * 获得所有的查询数据
     * @return array
     */
    private function getAll() {
        //返回数据集
        $result = array();
        if($this->numRows>0) {
            //返回数据集
            // dm_fetch_assoc返回根据指定行号从结果集取得生成的数组，列名为索引。如果没有更多行则返回
            //FALSE。如果 rownum=0 或缺省，则获取游标的下一行；如果 rownum 缺省，可代替 PHP
            //5.x 中的 dm_fetch_assoc
            for($i=0; $i<$this->numRows; $i++ ){
                $result[$i] = dm_fetch_assoc ($this->queryID);
            }
           //dm_data_seek($this->queryID, 0);

            /*
            while($row = dm_fetch_array($this->queryID, 1)){
                $result[]   =   $row;
            }
            */
        }
        return $result;
    }

    /**
     * 取得数据表的字段信息
     */
    public function getFields($tableName) {
        $tableName = $this->parseKey($tableName);
        $sql = "SELECT COLUMN_NAME,DATA_TYPE,DATA_DEFAULT,NULLABLE FROM USER_TAB_COLUMNS WHERE TABLE_NAME = '{$tableName}'";
        $result   =  $this->query($sql);
        $info   =   array();
        if($result) {
            foreach ($result as $key => $val) {
                $info[$val['COLUMN_NAME']] = array(
                    'name'    => $val['COLUMN_NAME'],
                    'type'    => $val['DATA_TYPE'],
                    'notnull' => strtolower($val['NULLABLE']) == 'n' ? true : false,
                    'default' => $val['DATA_DEFAULT'],
                    'primary' => ($key == 0),  //默认第一个就是主键
                    'autoinc' => '',
                );
            }
        }
        return $info;
    }

    /**
     * 取得数据库的表信息
     */
    public function getTables($dbName='') {
        $prefix = C('DB_PREFIX');
        $sql = "SELECT TABLE_NAME FROM user_tables WHERE TABLE_NAME LIKE '{$prefix}%'";
        $result = $this->query($sql);
        $info   =   array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }
    /**
     * 关闭数据库
     */
    public function close() {
        if($this->_linkID){
            dm_close($this->_linkID);
        }
        $this->_linkID = null;
    }

    /**
     * 数据库错误信息
     * 并显示当前的SQL语句
     * @return string
     */
    public function error($result = true) {
        $this->error = $result && $this->queryID ?dm_error($this->queryID): dm_error($this->_linkID);
        if($this->debug && '' != $this->queryStr){
            $this->error .= "\n [ SQL语句 ] : ".$this->queryStr;
        }
        return $this->error;
    }

    /**
     * SQL指令安全过滤
     * @param string $str  SQL指令
     * @return string
     */
    public function escapeString($str) {
        return dm_escape_string($str);
    }

    /**
     * limit
     * @return string
     */
    public function parseLimit($limit) {
        $limitStr    = '';
        if(!empty($limit)) {
            $limit  =   explode(',',$limit);
            if(count($limit)>1) {
                $limitStr .= ' LIMIT '.$limit[1].' OFFSET '.$limit[0].' ';
            }else{
                $limitStr .= ' LIMIT '.$limit[0].' ';
            }
        }
        return $limitStr;
    }

}