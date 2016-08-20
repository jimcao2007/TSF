<?php
class Mysql
{
    /**
     * 配置标识
     * 用于上报等操作
     * @var string
     */
    public $configId;

    /**
     * 数据库机器ip
     *
     * @var string
     */
    private $host;

    /**
     * 数据库端口
     *
     * @var string
     */
    private $port;

    /**
     * 用户名
     *
     * @var string
     */
    private $user;

    /**
     * 密码
     *
     * @var string
     */
    private $password;

    /**
     * db对象
     *
     * @var string
     */
    private $db;
    /**
     * 数据库名称
     */
    private $db_name;

    /**
     * 数据库连接
     *
     * @var object
     */
    private $con;

    private $_connected = false;

    /**
     * 错误编码
     *
     * @var int
     */
    public $errCode = 0;

    /**
     * 错误信息
     *
     * @var string
     */
    public $errMsg = '';

    /**
     * 清除错误标识，在每个函数调用前调用
     */
    private function clearERR()
    {
        $this->errCode = 0;
        $this->errMsg  = '';
    }

    const DEFAULT_PORT = 3306;
    /**
     *
     * @param string host     ip
     * @param int    port     端口
     * @param string db_name  数据库名称
     * @param string user     用户名称
     * @param string password 密码
     */
    public function __construct($conf)
    {
        $this->host = $conf['host'];
        $this->port = empty($conf['port'])? self::DEFAULT_PORT: $conf['port'];
        $this->db_name = empty($conf['dbname'])? '':$conf['dbname'];
        $this->user = $conf['dbuser'];
        $this->password = $conf['dbpwd'];
        $this->magic_quotes = false;
        $this->connect();
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * 初始化对象
     */
    function connect()
    {
        $this->con = mysqli_connect($this->host, $this->user, $this->password, $this->db_name, $this->port);

        if (!$this->con)
        {
            $this->errCode = \mysqli_connect_errno();
            $this->errMsg = \mysqli_connect_error();
            Logger::err(Dispatch::ERR_SYSTEM,'mysql conn err '.$this->errCode.' '.$this->errMsg);
            return false;
        }
        $this->_connected = true;
        return true;
    }

    function getErr()
    {
        $this->errCode = $this->con->errno;
        $this->errMsg = $this->con->error;
    }

    public function getErrMsg()
    {
        return $this->con->error;
    }
    /**
     * 更换默认数据库名称
     */
    function selectDB($db_name)
    {
        $this->db_name = $db_name;

        $ret = mysqli_select_db($this->con, $db_name);

        if (!$ret)
        {
            $this->getErr();
            return false;
        }
        return 0;
    }

    /**
     * 关闭数据库连接
     */
    function close()
    {
        if ($this->con) {
            return @mysqli_close($this->con);
        }

        return 0;
    }

    /**
     * 检查数据库连接,是否有效，无效则重新建立
     */
    private function checkConnection()
    {
        if (!@mysqli_ping($this->con))
        {
            $this->close();
            return $this->connect();
        }
        return true;
    }

    /**
     * 拼装insert的sql语句
     *
     * @param string table    表名称
     * @param array data    数据
     *
     * @return string
     */
    public function getInsertString($table, $data)
    {
        $n_str = '';
        $v_str = '';
        $table = $this->filterString($table);
        foreach ($data as $k => $v)
        {
            $n_str .= '`'.$this->filterString($k).'`,';
            $v_str .= "'".$this->filterString($v)."',";
        }
        $n_str = preg_replace( "/,$/", "", $n_str );
        $v_str = preg_replace( "/,$/", "", $v_str );
        $str = 'INSERT INTO '.$table.' ('.$n_str.') VALUES('.$v_str.')';
        return $str;
    }

    /**
     * 拼装update的sql语句
     *
     * @param string  table    表名称
     * @param array data    数据
     * @param string condtion    条件
     *
     * @return string
     */
    public function getUpdateString($table, $data, $condtion)
    {
        $str = '';
        $table = $this->filterString($table);
        foreach ($data as $k => $v)
        {
            $str .= '`'.$this->filterString($k)."`='".$this->filterString($v)."',";
        }
        $str = preg_replace("/,$/", "", $str);
        $sql = 'UPDATE '.$table.' SET '.$str;
        if (!empty($condtion) && is_string($condtion))
        {
            $sql .= ' WHERE '.$condtion;
        }
        return $sql;
    }

    /**
     * 拼装insert or update的sql语句
     *
     * @param string  table    表名称
     * @param array idata    插入数据
     * @param array udata    更新数据
     *
     * @return string
     */
    public function getInsertOrUpdateString($table, $idata, $udata)
    {
        $n_str = '';
        $v_str = '';
        $u_str = '';
        $table = $this->filterString($table);

        foreach ($idata as $k => $v)
        {
            $n_str .= '`'.$this->filterString($k).'`,';
            $v_str .= "'".$this->filterString($v)."',";
        }

        $n_str = preg_replace( "/,$/", "", $n_str );
        $v_str = preg_replace( "/,$/", "", $v_str );

        foreach ($udata as $k => $v)
        {
            $u_str .= $this->filterString($k)."='".$this->filterString($v)."',";
        }

        $u_str = preg_replace("/,$/", "", $u_str);


        $sql = 'INSERT INTO '.$table.' ('.$n_str.') VALUES('.$v_str.') ON DUPLICATE KEY UPDATE '.$u_str;
        return $sql;
    }

    /**
     * 新增数据,返回数组,格式:
     * code:0为成功，其他为失败
     * msg:错误消息
     *
     *
     * @param string table   表名称
     * @param array  data    数据
     * @return 正确返回true，否则返回false
     */
    public function insert($table, $data)
    {
        $sql = $this->getInsertString($table, $data);
        return $this->query($sql);
    }

    /**
     * ,更新数据,返回数组,格式:
     * code:0为成功，其他为失败
     * msg:错误消息
     *
     * @param string table    表名称
     * @param array data    数据
     * @param string condtion    查询条件
     * @return bool 正确返回true，否则返回false
     */
    public function update($table, $data, $condtion)
    {
        $sql = $this->getUpdateString($table, $data, $condtion);
        return $this->query($sql);
    }

    public function getAffectedRows()
    {
        return mysqli_affected_rows($this->con);
    }



    /**
     * 插入或更新数据
     *
     */

    public function insertOrUpdate($table, $add_data, $dup_data=null)
    {
        if(empty($dup_data))
        {
            $dup_data = $add_data;
        }
        $sql = $this->getInsertOrUpdateString($table, $add_data, $dup_data);
        return $this->query( $sql );
    }





    /**
     * 删除指定条件的数据,,返回数组,格式:
     * code:0为成功，其他为失败
     * msg:错误消息
     *
     * @param  string table     表名称
     * @param  string condtion  查询条件
     * @return bool 正确返回true，否则返回false
     *
     */
    public function remove($table, $condtion)
    {
        $table = $this->filterString($table);
        $sql = 'DELETE FROM '.$table.' WHERE '.$condtion;
        return $this->query($sql);
    }

    /**
     * 执行指定的sql语句,,返回数组,格式:
     * code:0为成功，其他为失败
     * msg:错误消息
     * data:结果数据
     *
     * @param  string sql    	sql语句
     * @return bool 正确返回true 否则返回false
     */
    public function query($sql)
    {
        $sql = trim($sql);
        //$r = $this->checkConnection();
        $result = false;
        for ($i = 0; $i < 2; $i++)
        {
            $result = mysqli_query($this->con, $sql);
            if ($result === false)
            {
                if (($this->con->errno == 2013 || $this->con->errno == 2006) && $i < 1)
                {
                    $r = $this->checkConnection();
                    if ($r === true)
                    {
                        continue;
                    }
                }

                $this->getErr();
                Logger::err(100,'Mysql err: '.$sql.'|'.$this->getErrMsg());
                return false;
            }
            break;
        }

        return $result;
    }

    /**
     * 获得满足条件的记录数量
     *
     * @param  string table     表名称
     * @param  string condtion  查询条件
     *
     * @return  bool 正确返回true,否则返回false
     */
    public function getRowsCount($table, $condtion,$count_field='*')
    {
        $table = $this->filterString($table);
        //$condtion = $this->filterString($condtion);
        $sql = 'SELECT count('.$count_field.') as c FROM '.$table;
        if (!empty($condtion))
        {
            $sql .= ' WHERE '.$condtion;
        }
        $data = $this->queryRows($sql);
        if($data === false)
        {
            return false;
        }
        if ($data < 0)
        {
            return $data;
        }
        return ((count($data)<=0) ? 0 : $data[0]['c']);
    }

    /**
     * 返回自增字段值
     *
     */
    public function getInsertId()
    {
        $this->clearERR();
        $ret = $this->checkConnection();
        if($ret<0)
        {
            return $ret;
        }
        return mysqli_insert_id($this->con);
    }

    /**
     * 根据查询sql语句获得指定的数据
     *
     * @param  string sql    sql语句
     * @return bool
     */
    public function queryRows($sql)
    {
        $result = $this->query($sql);
        if($result === false)
        {
            return false;
        }

        $data = array();
        while (($row = mysqli_fetch_assoc($result)))
        {
            $data[] = $row;
        }

        mysqli_free_result($result);
        return $data;
    }

    /**
     * 获取多条记录
     *
     * @param   string table    表名称
     * @param   array fields    需要获取的列名称，字符串或数组都支持
     * @param   string condtion    查询条件
     * @param   int startIndex    开始记录位置
     * @param   int length    需要取得的条数量
     *
     * @return  bool 正确返回true，否则返回false
     */
    public function getRows($table, $fields='', $condtion='', $startIndex=0, $length=0)
    {
        $n_str = '';
        $table = $this->filterString($table);
        if (!empty($fields) && is_array($fields))
        {
            foreach ($fields as $v)
            {
                $n_str .= $this->filterString($v).',';
            }
            $n_str = preg_replace("/,$/", "", $n_str);
        }
        if (empty($n_str))
        {
            $n_str = '*';
        }
        $sql = 'SELECT '.$n_str.' FROM '.$table;
        if (!empty($condtion))
        {
            $sql .= ' WHERE '.$condtion;
        }
        if (is_int($startIndex) && is_int($length) && $startIndex >= 0 && $length > 0)
        {
            $sql .=' LIMIT '.$startIndex.','.$length;
        }
//        echo $sql;
        return $this->queryRows($sql);
    }


    /**
     * 获取多条记录
     *
     * @param   string table    表名称
     * @param   array fields    需要获取的列名称，字符串或数组都支持
     * @param   string condtion    查询条件
     * @param   int startIndex    开始记录位置
     * @param   int length    需要取得的条数量
     *
     * @return  bool 正确返回true，否则返回false
     */
    public function getPageRows($table, $fields='', $condtion='', $page=1,$length=10,$last_count = 0)
    {
        $startIndex=($page-1)*$length;
        $n_str = '';
        $table = $this->filterString($table);
        $count = $this->getRowsCount($table,$condtion);
        if($count === false)
        {
            return false;
        }

        if($last_count > 0 && $count > $last_count)
        {
            $startIndex += $count - $last_count;
        }

        if (!empty($fields) && is_array($fields))
        {
            foreach ($fields as $v)
            {
                $n_str .= $this->filterString($v).',';
            }
            $n_str = preg_replace("/,$/", "", $n_str);
        }
        if (empty($n_str))
        {
            $n_str = '*';
        }
        $sql = 'SELECT '.$n_str.' FROM '.$table;
        if (!empty($condtion))
        {
            $sql .= ' WHERE '.$condtion;
        }
        if ($startIndex >= 0 && $length > 0)
        {
            $sql .=' LIMIT '.$startIndex.','.$length;
        }
        $list = $this->queryRows($sql);
        if($list === false)
        {
            return false;
        }
        return [
            'count'=>intval($count),
            'list'=>$list
        ];
    }


    /**
     * 获取一条数据记录
     *
     * @param   string table    表名称
     * @param   array fields    需要获取的列名称
     * @param   string condtion    查询条件
     * @param   int startIndex    开始记录位置
     * @param   int length    需要取得的条数量
     *
     * @return  bool 正确返回true，否则返回false
     */
    public function getOneRows($table, $fields='', $condtion='')
    {
        $ret = $this->getRows($table,$fields,$condtion);
        if($ret === false)
        {
            return false;
        }
        if(empty($ret))
        {
            return array();
        }
        return $ret[0];
    }

    /**
     * 直接返回结果集, 以便在特殊应用场景下, 业务逻辑可自行处理返回结果集
     *
     * @param		string		$sql, sql语句
     *
     * @return		object/bool	正确返回数据,错误返回false
     */
    public function getRS($sql)
    {
        $this->clearERR();
        if(!$this->_connected)
        {
            $ret = $this->connect();
            if($ret < 0)
            {
                return $ret;
            }
        }

        return $this->query($sql);
    }

    public function getFieldDataType($result){
        $len = mysqli_num_fields($result);
        $fields = array();
        $typeNames = array(
            MYSQLI_TYPE_DECIMAL			=> "DECIMAL",
            MYSQLI_TYPE_TINY			=> "TINY",
            MYSQLI_TYPE_SHORT			=> "SHORT",
            MYSQLI_TYPE_LONG			=> "LONG",
            MYSQLI_TYPE_FLOAT			=> "FLOAT",
            MYSQLI_TYPE_DOUBLE			=> "DOUBLE",
            MYSQLI_TYPE_NULL			=> "NULL",
            MYSQLI_TYPE_TIMESTAMP		=> "TIMESTAMP",
            MYSQLI_TYPE_LONGLONG		=> "LONGLONG",
            MYSQLI_TYPE_INT24			=> "INT24",
            MYSQLI_TYPE_DATE			=> "DATE",
            MYSQLI_TYPE_TIME			=> "TIME",
            MYSQLI_TYPE_DATETIME		=> "DATETIME",
            MYSQLI_TYPE_YEAR			=> "YEAR",
            MYSQLI_TYPE_NEWDATE			=> "NEWDATE",
            MYSQLI_TYPE_ENUM			=> "ENUM",
            MYSQLI_TYPE_SET				=> "SET",
            MYSQLI_TYPE_TINY_BLOB		=> "TINY_BLOB",
            MYSQLI_TYPE_MEDIUM_BLOB		=> "MEDIUM_BLOB",
            MYSQLI_TYPE_LONG_BLOB		=> "LONG_BLOB",
            MYSQLI_TYPE_BLOB			=> "BLOB",
            MYSQLI_TYPE_VAR_STRING		=> "VAR_STRING",
            MYSQLI_TYPE_STRING			=> "STRING",
            MYSQLI_TYPE_GEOMETRY		=> "GEOMETRY"
        );
        for ($i = 0 ; $i < $len; $i++) {
            $medata = mysqli_fetch_field_direct($result, $i);
            $fields[$i] = (object)array(
                "name"			=> $medata->name,
                "table"			=> $medata->table,
                "max_length"	=> $medata->max_length,
                "length"		=> $medata->length,
                "auto"			=> (MYSQLI_AUTO_INCREMENT_FLAG & $medata->flags) ? 1 : 0,
                "not_null"		=> (MYSQLI_NOT_NULL_FLAG & $medata->flags) ? 1 : 0,
                "primary_key"	=> (MYSQLI_PRI_KEY_FLAG & $medata->flags) ? 1 : 0,
                "unique_key"	=> (MYSQLI_UNIQUE_KEY_FLAG & $medata->flags) ? 1 : 0,
                "multiple_key"	=> (MYSQLI_MULTIPLE_KEY_FLAG & $medata->flags) ? 1 : 0,
                "numeric"		=> (MYSQLI_NUM_FLAG & $medata->flags) ? 1 : 0,
                "blob"			=> (MYSQLI_BLOB_FLAG & $medata->flags) ? 1 : 0,
                "type"			=> $typeNames[$medata->type],
                "unsigned"		=> (MYSQLI_UNSIGNED_FLAG & $medata->flags) ? 1 : 0,
                "zerofill"		=> (MYSQLI_ZEROFILL_FLAG & $medata->flags) ? 1 : 0,
                "decimals"		=> $medata->decimals
            );

        }
        return $fields;
    }

    /**
     * 过滤特殊字符
     *
     * @param string str    字符串
     * @return string
     */
    public function filterString($str)
    {
        if ($this->magic_quotes)
        {
            $str = stripslashes($str);
        }
        if ( is_numeric($str) ) {
            return $str;
        } else {
            $ret = @mysqli_real_escape_string($this->con, $str);

            if ( strlen($str) && !isset($ret) ) {
                $r = $this->checkConnection();
                if ($r !== true) {
                    $this->close();
                    $ret = $str;
                }
            }

            return $ret;
        }
    }

    /**
     * 批量新增数据,减少数据库压力,格式: code:0为成功，其他为失败; msg:错误消息
     * 正确返回true，否则返回false
     * @param string table	表名称
     * @param array  items	数据
     */
    public function batchInsert($table, $items,$up_items=false)
    {
        $sql = $this->getBatchInsertString($table, $items,$up_items);
        return $this->query($sql);
    }

    /**
     * 拼装insert的sql语句
     *
     * @param string table	表名称
     * @param array  items	保存记录的数组
     *
     * @return string
     */
    public function getBatchInsertString($table, $items,$up_items=false)
    {
        $sFields = '';
        $sValue  = '';
        $table = $this->filterString($table);
        $item = $items[0];

        $up_str = '';

        //保证字段的顺序，依照第一个为准
        $keyList = array();
        foreach ($item as $k => $v){
            $f ='`'.$this->filterString($k).'`';
            $sFields .=  $f.',';
            $keyList[] = $k;
            $up_str .= $f.'=VALUES('.$f.'),';
        }
        $up_str = substr($up_str,0,-1);
        $len = count($keyList);
        foreach ($items as $k => $item){
            $sTmp = '';
            for ($i = 0; $i < $len; $i++){
                $v = $item[$keyList[$i]];
                $sTmp .= "'".$this->filterString($v)."',";
            }
            $sTmp  = preg_replace( "/,$/", "", $sTmp );
            $sValue .='(' .$sTmp. '),';
        }
        $sFields = substr($sFields,0,-1);
        $sValue  = substr($sValue,0,-1);
        $str = 'INSERT INTO '.$table.' ('.$sFields.') VALUES '.$sValue.' ';
        if($up_items)
        {
            $str .= ' ON DUPLICATE KEY UPDATE '.$up_str;
        }
        return $str;
    }

    public function beginTransaction()
    {
        return mysqli_begin_transaction($this->con);
    }

    public function commitTransaction()
    {
        return mysqli_commit($this->con);
    }

    public function rollback()
    {
        return mysqli_rollback($this->con);
    }
}