<?php

namespace Glial\Sgbd\Sql\Mysql;

use \Glial\Sgbd\Sql\Sql;
use \Glial\Cli\Color;
use Glial\Cli\Table;
use \App\Library\Debug;

class Mysql extends Sql
{
    const ESC = '`';

    public $ESC               = '`';
    public $db;
    public $link;
    public $server_type;
    public $version           = '';
    public $version_full      = '';
    public $version_comment   = '';
    public $status            = array();
    public $variables         = array();
    public $host;
    public $port;
    public $name;
    public $grant;
    public $mysqli_flag_names = array(
        MYSQLI_NUM_FLAG => 'num',
        MYSQLI_PART_KEY_FLAG => 'part_key',
        MYSQLI_SET_FLAG => 'set',
        MYSQLI_TIMESTAMP_FLAG => 'timestamp',
        MYSQLI_AUTO_INCREMENT_FLAG => 'auto_increment',
        MYSQLI_ENUM_FLAG => 'enum',
        MYSQLI_ZEROFILL_FLAG => 'zerofill',
        MYSQLI_UNSIGNED_FLAG => 'unsigned',
        MYSQLI_BLOB_FLAG => 'blob',
        MYSQLI_MULTIPLE_KEY_FLAG => 'multiple_key',
        MYSQLI_UNIQUE_KEY_FLAG => 'unique_key',
        MYSQLI_PRI_KEY_FLAG => 'primary_key',
        MYSQLI_NOT_NULL_FLAG => 'not_null',
    );

    static public $table_list = array();

    /*
     * Store in array cash
     */
    public $primary_key       = array();
    public $primary_key_field = array();

    function __construct($name, $elem)
    {
        $this->setName($name, $elem);
        $this->name = $name;
    }
    /*
     * @since Glial 1.0
     * @version 3.1
     * @return Returns an object which represents the connection to a MySQL Server.
     * @parameters dbname The database name.
     * @alias make the same as mysqli::select_db and init charset connection in utf-8
     */

    public function sql_connect($host, $login, $password, $dbname, $port = 3306)
    {
        if (empty($port)) {
            $port = 3306;
        }

        $this->host = $host;
        $this->port = $port;

        $this->link = mysqli_init();
        mysqli_options($this->link, MYSQLI_OPT_CONNECT_TIMEOUT, 11);
        mysqli_real_connect($this->link, $host, $login, $password, $dbname, $port);

        //$this->link = mysqli_connect($host, $login, $password, $dbname, $port);
        $this->db   = $dbname;

        if (!$this->link) {

            $this->is_connected = false;

            if ($this->name === DB_DEFAULT) {
                $level = 80;
            } else {
                $level = 60;
            }

            $this->is_connected = false;

            if ($level === 80) {
                throw new \Exception('GLI-012 : Can\'t connect to ('.$login.'@'.$host.":".$port.') MySQL server'.' {'.error_get_last()['message'].'}', $level);
            } else {
                //return $this->link;
                //return 'Can\'t connect to (' . $login . '@' . $host . ":" . $port . ') MySQL server' . ' {' . error_get_last()['message'] . '}';
            }
        } else {
            $this->is_connected = true;

            /*
             * test on évite les A/R à la DB
              mysqli_set_charset($this->link, 'utf8');
              $this->_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");
              $this->_query("SET NAMES 'utf8'");

             */
        }

        return $this->link;
    }
    /*
     * @since Glial 1.0
     * @return Returns TRUE on success or FALSE on failure.
     * @param string $dbname The database name.
     * @alias make the same as mysqli::select_db
     */

    public function sql_select_db($dbname)
    {
        $this->db = $dbname;
        return mysqli_select_db($this->link, $dbname);
    }
    /*
     * @since Glial 1.0
     * @return Returns FALSE on failure. For successful SELECT, SHOW, DESCRIBE or EXPLAIN queries _query() will return a mysqli_result object. For other successful queries _query() will return TRUE.
     * @param string $dbname The database name.
     * @description Performs a query against the database.
     * @alias make the same as mysqli_query
     * @see mysqli_query http://php.net/manual/en/mysqli.query.php
     */

    public function _query($sql)
    {
        try{
            $ret = mysqli_query($this->link, $sql);
        }
        catch (\Exception $e) {
            $called_from = debug_backtrace();

            $indice = 0;
            if (strstr($called_from[0]['file'], "/Sgbd/Sql/Sql.php")) {
                $indice = 1;
            }

            $file = $called_from[$indice]['file'];
            $line = $called_from[$indice]['line'];

            $level = 60;

            \SqlFormatter::$cli = true;
            $msg = Color::getColoredString("[".date("Y-m-d H:i:s")."]", "purple")." ".Color::getColoredString(" [ERROR] ", "black", "red")."\n".\SqlFormatter::format($sql);

            error_log($msg."{".$file.":".$line."}\n".Color::getColoredString("ERROR: ".mysqli_error($this->link),"black","red"), 3, TMP."log/sql.log");

            throw new \Exception("GLI-562 : ERROR SQL : (".$this->host.":".$this->port.") {".$file.":".$line.", ERROR: ".mysqli_error($this->link)."}\n$sql", $level);
        }

        if (mysqli_warning_count($this->link)) {
            $e = mysqli_get_warnings($this->link);

            \SqlFormatter::$cli = true;

            if ($e->errno != 0) {
                if ($result = $this->_query("SHOW WARNINGS")) {

                    $called_from = debug_backtrace();

                    $indice = 0;
                    if (strstr($called_from[0]['file'], "/Sgbd/Sql/Sql.php")) {
                        $indice = 1;
                    }

                    $file = $called_from[$indice]['file'];
                    $line = $called_from[$indice]['line'];

                    $table = new Table(2);
                    $table->addHeader(array(" Level ", " Code ", " Message "));

                    $msg = Color::getColoredString("[".date("Y-m-d H:i:s")."]", "purple")." (".$this->host.":".$this->port.") ".Color::getColoredString(" [SHOW WARNINGS] ", "black", "yellow")."\n"
                        .\SqlFormatter::format($sql)."\n";

                    $msg .= Color::getColoredString($file.":".$line, "cyan")."\n";

                    $i   = 0;
                    while ($row = $result->fetch_row()) {

                        $table->addLine(array(" ".$row[0]." ", " ".$row[1]." ", " ".$row[2]." "));
                        $i++;
                    }

                    $result->close();

                    $msg .= $table->display();
                    $msg .= $i." rows\n";

                    if (IS_CLI) {
                        fwrite(STDERR, $msg);
                    } else {
                        \SqlFormatter::$cli = false;
                        //$msg                = '<span style="background:yellow; color:#000000">[SHOW WARNINGS]</span>'.\SqlFormatter::format($sql)."\n";
                        //Debug::debug($msg);
                        // echo dans le navigateur if debug = yes ?
                        // => if --sql then display listig of query
                    }

                    error_log($msg, 3, TMP."log/sql.log");
                }
            }
        }

        return $ret;
    }

    public function sql_num_rows($res)
    {
        return mysqli_num_rows($res);
    }

    public function sql_close()
    {
        if ($this->is_connected === true) {
            $this->link         = mysqli_close($this->link);
            $this->is_connected = false;
        }
    }

    public function sql_affected_rows($stid = '')
    {
        //$stid='' to maintain compatibility with oracle
        return mysqli_affected_rows($this->link);
    }

    public function sql_real_escape_string($data)
    {
        $data = $data ?? '';
        return mysqli_real_escape_string($this->link, $data);
    }

    public function sql_insert_id()
    {

        if (empty($this->last_id)) {
            return $this->_insert_id();
        }

        return $this->last_id;
    }

    public function _insert_id()
    {
        return mysqli_insert_id($this->link);
    }

    public function _error()
    {
        return mysqli_error($this->link);
    }

    public function _error_num()
    {
        return mysqli_errno($this->link);
    }

    public function sql_fetch_array($res, $resulttype = MYSQLI_BOTH)
    {
        return mysqli_fetch_array($res, $resulttype);
    }

    public function sql_to_array($res)
    {
        $rep = array();

        while ($tab = mysqli_fetch_array($res, MYSQLI_ASSOC)) {

            $rep[] = $tab;
        }

        return $rep;
    }

    public function sql_fetch_object($res)
    {
        return mysqli_fetch_object($res);
    }

    public function sql_fetch_row($res)
    {
        return mysqli_fetch_row($res);
    }

    public function sql_num_fields($res)
    {
        return mysqli_num_fields($res);
    }

    public function sql_field_name($res, $i)
    {
        return mysqli_fetch_fields($res, $i);
    }

    public function sql_free_result($res)
    {
        return mysqli_free_result($res);
    }

    public function sql_fetch_field($res, $i = 0)
    {
        return mysqli_fetch_field($res, $i);
    }

    /**
     * (Glial 2.1)<br/>
     * get all table name and all view and return in an array
     * @author Aurélien LEQUOY, <aurelien.lequoy@esysteme.com>
     * @license GPL
     * @license http://opensource.org/licenses/gpl-license.php GNU Public License
     * @link http://www.glial-framework-php.org/en/manual/mysql.getListTable.php
     * 
     *
     */
    public function getListTable($table_name='', $type='table')
    {

        $database = $this->getDatabase();

        if (empty(self::$table_list[$database]))
        {
            $sql = "SHOW FULL TABLES";

            $res = $this->_query($sql);
            
            $table = array();
            $view  = array();
            
            while ($ar = $this->sql_fetch_array($res)) {
                if ($ar['Table_type'] === "VIEW") {
                    $view[] = $ar[0];
                } else {
                    $table[] = $ar[0];
                }
            }
            self::$table_list[$database]['table'] = $table;
            self::$table_list[$database]['view']  = $view;
        }
        
        if (!empty($table_name)) {
            if (in_array($table_name, self::$table_list[$database][$type])) {
                return true;
            }
            else{
                return false;
            }
        }

        return self::$table_list[$database];
    }

    public function getIndexUnique($table_name)
    {
        $sql = "show keys from `".$table_name."` in `".$this->db."`";
        $res = $this->_query($sql);

        if (!$res) {
            throw new \Exception("GLI-030 : problem with this query : '".$sql."'");
        }

        $index = array();
        while ($ob    = $this->sql_fetch_object($res)) {

            if ($ob->Key_name === "PRIMARY") {
                continue;
            }
            if ($ob->Non_unique === "0") {
                $index[] = $ob->Column_name;
            }
        }
        return $index;
    }

    /**
     * This method return the number of mysql/mariadb/percona version
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param string name of connection
     * @return string
     * @description if the connection exist return the instance else it create it
     * @access public
     * @package Sgbd/
     * @since 3.0a First time this was introduced.
     * @version 3.0.1a
     */
    public function getVersion()
    {
        if (empty($this->variables['version'])) {
            $this->getVariables();
        }

        $version            = $this->variables['version'];
        $this->version_full = $version;

        if (strpos($version, "-")) {
            $this->version = explode("-", $version)[0];
        } else {
            $this->version = $version;
        }


        return $this->version;
    }

    /**
     * DEPRECATED !
     * Returns server type for current connection
     *
     * Known types are: Drizzle, MariaDB and MySQL (default)
     * @author from phpMyAdmin
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @return string
     * @since 3.0a First time this was introduced.
     * @version 3.0.1a
     *
     */
    public function getServerType()
    {

        if (empty($this->version_full)) {
            $this->version_full = $this->getVersion();
        }

        if (empty($this->version_comment)) {
            $version_comment = $this->getVersionComment();
        }


        if (empty($this->server_type)) {

            $this->server_type = 'MySQL';

            if (stripos($this->version_full, 'drizzle')) {
                $this->server_type = 'Drizzle';
            } else if (stripos($version_comment, 'mariadb') !== false) {
                $this->server_type = 'MariaDB';
            } else if (stripos($version_comment, 'percona') !== false) {
                $this->server_type = 'Percona Server';
            } else if (stripos($version_comment, 'amazon') !== false) {

                $main = explode('.',$this->version_full)[0];
                if ($main >= 10 ) {
                    $this->server_type = 'MariaDB';
                } else {
                    $this->server_type = 'MySQL';
                }
            }
        }
        return $this->server_type;
    }

    public function sql_fetch_yield($sql, $resulttype = MYSQLI_ASSOC)
    {
        $res = $this->sql_query($sql);

        while ($ob = $this->sql_fetch_array($res, $resulttype)) {
            yield $ob;
        }
    }

    public function sql_fetch_all($sql, $resulttype = MYSQLI_ASSOC)
    {
        $res = $this->sql_query($sql);

        $data = array();

        while ($ob = $this->sql_fetch_array($res, $resulttype)) {
            $data[] = $ob;
        }

        return $data;
    }

    public function sql_multi_query($sql)
    {
        return mysqli_multi_query($this->link, $sql);
    }

    public function sql_next_result()
    {
        $ret = mysqli_next_result($this->link);
        return $ret;
    }

    public function sql_more_results()
    {
        $ret = mysqli_more_results($this->link);
        return $ret;
    }

    public function sql_store_result()
    {
        $ret = mysqli_store_result($this->link);
        return $ret;
    }

    /**
     * Returns true or false is the server support multi master
     * MariaDB >= 10.x
     *
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @return boolean
     * @since 3.0 First time this was introduced.
     * @version 3.0
     */
    public function isMultiMaster()
    {
        if ($this->getServerType() === "MariaDB" && version_compare($this->getVersion(), "10", ">=")) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * This method return the number of mysql/mariadb/percona version
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param string name of connection
     * @return string
     * @description if the connection exist return the instance else it create it
     * @access public
     * @package Sgbd/
     * @since 3.0 First time this was introduced.
     * @version 3.0.1
     */
    public function XgetVariable()
    {

        if (empty($this->version)) {

            $sql = "SHOW GLOBAL VARIABLES LIKE 'UpTime'";

            $res  = $this->sql_query($sql);
            $data = $this->sql_fetch_array($res, MYSQLI_ASSOC);

            $version            = $data['Value'];
            $this->version_full = $version;

            if (strpos($version, "-")) {
                $this->version = strstr($version, '-', true);
            } else {
                $this->version = $version;
            }
        }

        return $this->version;
    }

    /**
     * This method return the global status of server MySQL, if one var is specified return this if exist else it doesn't exist return false.
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param string name of connection
     * @return string
     * @description if the connection exist return the instance else it create it
     * @access public
     * @package Sgbd
     * @since 3.0.2 First time this was introduced.
     * @version 3.0.2
     */
    public function getStatus($var = '', $refresh = false)
    {
        if ($refresh) {
            unset($this->status);
        }

        if (empty($this->status)) {

            $sql = "SHOW /*!40003 GLOBAL*/ STATUS;";

            $res = $this->sql_query($sql);

            while ($data = $this->sql_fetch_array($res, MYSQLI_ASSOC)) {
                $this->status[$data['Variable_name']] = $data['Value'];
            }
        }

        if (empty($var)) {
            return $this->status;
        } else {
            if (!empty($this->status[$var])) {
                return $this->status[$var];
            } else {
                return false;
            }
        }
    }

    /**
     * This method return the global status of server MySQL, if one var is specified return this if exist else it doesn't exist return false.
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param string name of connection
     * @return string
     * @description if the connection exist return the instance else it create it
     * @access public
     * @package Sgbd
     * @since 3.0.2 First time this was introduced.
     * @version 3.0.2
     */
    public function getVariables($var = '')
    {

        if (empty($this->variables)) {
            $sql = "SHOW /*!40003 GLOBAL*/ VARIABLES;";
            $res = $this->sql_query($sql);

            while ($data = $this->sql_fetch_array($res, MYSQLI_ASSOC)) {
                $this->variables[$data['Variable_name']] = $data['Value'];
            }
        }


        if (version_compare($this->getVersion(), 5.5, '>=')) {
            $sql  = "select @@version_comment limit 1";
            $res  = $this->sql_query($sql);
            while ($data = $this->sql_fetch_array($res, MYSQLI_NUM)) {

                if ($data[0] === "(ProxySQL)") {
                    $data['Value'] = 1;
                } else {
                    $data['Value'] = 0;
                }

                $this->variables['is_proxysql'] = $data['Value'];
            }
        }

        if (empty($var)) {
            return $this->variables;
        } else {
            if (!empty($this->variables[$var])) {
                return $this->variables[$var];
            } else {
                return false;
            }
        }
    }

    public function getGrants()
    {
        if (empty($this->grant)) {

            $sql  = "SHOW grants for current_user;";
            $res  = $this->sql_query($sql);
            $data = $this->sql_fetch_array($res, MYSQLI_NUM);

            preg_match("/GRANT ([\w ,]+) ON /", $data[0], $output_array);

            $this->grant = explode(', ', $output_array[1]);

            return $this->grant;
        } else {
            return $this->grant;
        }
    }

    public function getCreateTable($table, $schema = '')
    {
        $sql = "SHOW CREATE TABLE `".$table."`";
        $res = $this->sql_query($sql);

        while ($data = $this->sql_fetch_array($res, MYSQLI_NUM)) {
            $elem = $data[1];
        }

        if (empty($elem)) {
            throw new \Exception("GLI-101 : couldn't find the table : '".$table."'");
        }

        return $elem;
    }

    public function getDescription($table)
    {
        $sql = "SELECT COLUMN_NAME, DATA_TYPE,CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS
                WHERE table_name = '".$table."' AND TABLE_SCHEMA = database()
                ORDER BY `COLUMNS`.`CHARACTER_MAXIMUM_LENGTH` ASC";

        //have to switch TABLE_SCHEMA = database() by something else in future if needed

        $res = $this->sql_query($sql);

        $table = array();
        while ($ar    = $this->sql_fetch_array($res, MYSQLI_NUM)) {

            $table[] = $ar;
        }

        return $table;
    }

    /**
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @return string
     * @description get version_comment
     * @access public
     * @package Sgbd/
     * @since 3.1.2 First time this was introduced.
     * @version 3.1.2
     */
    public function getVersionComment()
    {

        if (empty($this->version_comment)) {

            $sql = "SHOW /*!40003 GLOBAL*/ VARIABLES LIKE 'version_comment'";

            $res  = $this->sql_query($sql);
            $data = $this->sql_fetch_array($res, MYSQLI_ASSOC);

            $version               = $data['Value'];
            $this->version_comment = $version;
        }

        return $this->version_comment;
    }

    /**
     * This method return an array of master status, is the server is not confgured as master return false
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param string name of connection
     * @return array all param of master status
     * @description if the connection exist return the instance else it create it
     * @access public
     * @package Sgbd
     * @since 3.0a First time this was introduced.
     * @version 3.1 add testAccess
     */
    public function isMaster()
    {

        //$grants = $this->getGrants();

        if ($this->testAccess()) {

            $sql = "SHOW MASTER STATUS";
            $res = $this->sql_query($sql);

            if ($this->sql_num_rows($res) === 0) {
                return false;
            } elseif ($this->sql_num_rows($res) !== 1) {
                throw new \Exception("GLI-011 : more than one line returned in SHOW MASTER STATUS");
            }


            return $this->sql_fetch_array($res, MYSQLI_ASSOC);
        }
        return false;
    }

    /**
     * This method return an array of all slave thread, is the server is not confgured as slave return false
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param string name of connection
     * @return array all param of slave status for each thread
     * @description if the connection exist return the instance else it create it
     * @access public
     * @example echo Sgbd::sql('defaul');
     * @package Sgbd
     * @since 3.0a First time this was introduced.
     * @version 3.1 add testAccess
     */
    public function isSlave()
    {

        if ($this->testAccess()) {

            if (version_compare($this->getVersion(), 10, '>')) {
                $sql = "SHOW ALL SLAVES STATUS";
            } else {
                if (version_compare($this->getVersion(), '8.0.33', '>')) {
                    $sql = "SHOW REPLICA STATUS";
                }
                else {
                    $sql = "SHOW SLAVE STATUS";
                }
            }

            $res = $this->sql_query($sql);

            $tab_ret = array();
            if ($this->sql_num_rows($res) === 0) {
                return $tab_ret;
            } else {
                while ($arr     = $this->sql_fetch_array($res, MYSQLI_ASSOC)) {
                    $tab_ret[] = $arr;
                }

                return $tab_ret;
            }
        }

        return false;
    }

    /**
     * This method return true or false
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @return boolean
     * @description if access is enough return true else false
     * @access public
     * @package Sgbd
     * @since 3.1 First time this was introduced.
     * @version 3.1 add testAccess
     */
    public function testAccess()
    {

        $grants = $this->getGrants();
        if (in_array("ALL PRIVILEGES", $grants)) {
            return true;
        }

        if (in_array("SUPER", $grants)) {
            return true;
        }

        if (in_array("REPLICATION CLIENT", $grants)) {
            return true;
        }

        if (in_array("BINLOG MONITOR", $grants)) {
            return true;
        }

        return false;
    }

    /**
     * returns metainfo for fields in $result
     *
     * @param \mysqli_result $result result set identifier
     * @license GNU/GPL
     * @return array meta info for fields in $result
      @since 4.2.11 First time this was introduced.
     * @version 4.2.11 init
     * @from PhpMyAdmin
     */
    public function getFieldsMeta($result)
    {
        // Build an associative array for a type look up
        $typeAr                          = array();
        $typeAr[MYSQLI_TYPE_DECIMAL]     = 'real';
        $typeAr[MYSQLI_TYPE_NEWDECIMAL]  = 'real';
        $typeAr[MYSQLI_TYPE_BIT]         = 'int';
        $typeAr[MYSQLI_TYPE_TINY]        = 'int';
        $typeAr[MYSQLI_TYPE_SHORT]       = 'int';
        $typeAr[MYSQLI_TYPE_LONG]        = 'int';
        $typeAr[MYSQLI_TYPE_FLOAT]       = 'real';
        $typeAr[MYSQLI_TYPE_DOUBLE]      = 'real';
        $typeAr[MYSQLI_TYPE_NULL]        = 'null';
        $typeAr[MYSQLI_TYPE_TIMESTAMP]   = 'timestamp';
        $typeAr[MYSQLI_TYPE_LONGLONG]    = 'int';
        $typeAr[MYSQLI_TYPE_INT24]       = 'int';
        $typeAr[MYSQLI_TYPE_DATE]        = 'date';
        $typeAr[MYSQLI_TYPE_TIME]        = 'time';
        $typeAr[MYSQLI_TYPE_DATETIME]    = 'datetime';
        $typeAr[MYSQLI_TYPE_YEAR]        = 'year';
        $typeAr[MYSQLI_TYPE_NEWDATE]     = 'date';
        $typeAr[MYSQLI_TYPE_ENUM]        = 'unknown';
        $typeAr[MYSQLI_TYPE_SET]         = 'unknown';
        $typeAr[MYSQLI_TYPE_TINY_BLOB]   = 'blob';
        $typeAr[MYSQLI_TYPE_MEDIUM_BLOB] = 'blob';
        $typeAr[MYSQLI_TYPE_LONG_BLOB]   = 'blob';
        $typeAr[MYSQLI_TYPE_BLOB]        = 'blob';
        $typeAr[MYSQLI_TYPE_VAR_STRING]  = 'string';
        $typeAr[MYSQLI_TYPE_STRING]      = 'string';
        // MySQL returns MYSQLI_TYPE_STRING for CHAR
        // and MYSQLI_TYPE_CHAR === MYSQLI_TYPE_TINY
        // so this would override TINYINT and mark all TINYINT as string
        // https://sourceforge.net/p/phpmyadmin/bugs/2205/
        //$typeAr[MYSQLI_TYPE_CHAR]        = 'string';
        $typeAr[MYSQLI_TYPE_GEOMETRY]    = 'geometry';
        $typeAr[MYSQLI_TYPE_BIT]         = 'bit';
        $typeAr[MYSQLI_TYPE_JSON]        = 'json';

        $fields = mysqli_fetch_fields($result);

        // this happens sometimes (seen under MySQL 4.0.25)
        if (!is_array($fields)) {
            return false;
        }

        foreach ($fields as $k => $field) {
            $fields[$k]->_type  = $field->type;
            $fields[$k]->type   = $typeAr[$field->type];
            $fields[$k]->_flags = $field->flags;
            $fields[$k]->flags  = $this->fieldFlags($result, $k);

            // Enhance the field objects for mysql-extension compatibility
            //$flags = explode(' ', $fields[$k]->flags);
            //array_unshift($flags, 'dummy');
            $fields[$k]->multiple_key = (int) (bool) ($fields[$k]->_flags & MYSQLI_MULTIPLE_KEY_FLAG);
            $fields[$k]->primary_key  = (int) (bool) ($fields[$k]->_flags & MYSQLI_PRI_KEY_FLAG);
            $fields[$k]->unique_key   = (int) (bool) ($fields[$k]->_flags & MYSQLI_UNIQUE_KEY_FLAG);
            $fields[$k]->not_null     = (int) (bool) ($fields[$k]->_flags & MYSQLI_NOT_NULL_FLAG);
            $fields[$k]->unsigned     = (int) (bool) ($fields[$k]->_flags & MYSQLI_UNSIGNED_FLAG);
            $fields[$k]->zerofill     = (int) (bool) ($fields[$k]->_flags & MYSQLI_ZEROFILL_FLAG);
            $fields[$k]->numeric      = (int) (bool) ($fields[$k]->_flags & MYSQLI_NUM_FLAG);
            $fields[$k]->blob         = (int) (bool) ($fields[$k]->_flags & MYSQLI_BLOB_FLAG);
        }
        return $fields;
    }

    /**
     * returns concatenated string of human readable field flags
     *
     * @param mysqli_result $result result set identifier
     * @param int           $i      field
     *
     * @return string field flags
     */
    public function fieldFlags($result, $i)
    {
        $f         = mysqli_fetch_field_direct($result, $i);
        $type      = $f->type;
        $charsetnr = $f->charsetnr;
        $f         = $f->flags;
        $flags     = array();
        foreach ($this->mysqli_flag_names as $flag => $name) {
            if ($f & $flag) {
                $flags[] = $name;
            }
        }
        // See https://dev.mysql.com/doc/refman/6.0/en/c-api-datatypes.html:
        // to determine if a string is binary, we should not use MYSQLI_BINARY_FLAG
        // but instead the charsetnr member of the MYSQL_FIELD
        // structure. Watch out: some types like DATE returns 63 in charsetnr
        // so we have to check also the type.
        // Unfortunately there is no equivalent in the mysql extension.
        if (($type == MYSQLI_TYPE_TINY_BLOB || $type == MYSQLI_TYPE_BLOB || $type == MYSQLI_TYPE_MEDIUM_BLOB || $type == MYSQLI_TYPE_LONG_BLOB || $type == MYSQLI_TYPE_VAR_STRING
            || $type == MYSQLI_TYPE_STRING) && 63 == $charsetnr
        ) {
            $flags[] = 'binary';
        }
        return implode(' ', $flags);
    }

    public function getFields($result)
    {



        //$f = mysqli_fetch_field($result);
    }

    public function getPrimaryKey($table, $database)
    {
        if (empty($this->primary_key[$database][$table])) {

            $sql = "SHOW INDEX FROM `".$database."`.`".$table."` WHERE `Key_name` ='PRIMARY';";
            $res = $this->sql_query($sql);

            if ($this->sql_num_rows($res) == "0") {
                throw new \Exception("GLI-067 : this table '".$table."' haven't primary key !");
            } else {

                $index = array();

                while ($ob = $this->sql_fetch_object($res)) {
                    $this->primary_key[$database][$table][] = $ob->Column_name;
                }
            }
        }

        return $this->primary_key[$database][$table];
    }
    /*
     *
     * on accepte les tableau uniquement pour cashé les éléments
     */

    public function getTypeOfPrimaryKey($tables, $database)
    {

        if (is_array($tables)) {
            $table = implode("','", $tables);
        } else {
            $table = $tables;
        }

        if (is_array($tables) || empty($this->primary_key_field[$database][$table])) {

            $sql = "SELECT `COLUMN_TYPE`, `COLUMN_NAME`, `TABLE_NAME` FROM `information_schema`.`COLUMNS` WHERE `TABLE_NAME` IN ('".$table."') AND `COLUMN_KEY` ='PRI' AND `TABLE_SCHEMA` = '".$database."';";
            $res = $this->sql_query($sql);

            if ($this->sql_num_rows($res) === "0") {
                throw new \Exception("GLI-067 : this table [".$table."] haven't primary key !");
            } else {
                while ($ob = $this->sql_fetch_object($res)) {

                    $tmp = array();

                    $tmp['name'] = $ob->COLUMN_NAME;
                    $tmp['type'] = $ob->COLUMN_TYPE;

                    $this->primary_key_field[$database][$ob->TABLE_NAME][] = $tmp;
                }
            }
        }

        if (!is_array($tables)) {
            return $this->primary_key_field[$database][$table];
        }
    }
    /*
     *  Initialise la récupération d'un jeu de résultats
     */

    public function sql_use_result()
    {
        return mysqli_use_result($this->link);
    }
    /*
     * Déplace le pointeur interne de résultat
     * @param $result Un identifiant de jeu de résultats retourné par la fonction sql_query(), sql_store_result() ou sql_use_result().
     * @param Le paramètre offset doit être compris entre zéro et sql_num_rows() - 1 (0..sql_num_rows() - 1).
     * @return bool
     * @more : http://php.net/manual/fr/mysqli-result.data-seek.php
     */

    public function sql_data_seek($result, $offset)
    {
        return mysqli_data_seek($result, $offset);
    }
    /*
     * Compare la version de MySQL / MariaDB / Percona Server, si une des occurances correspond retourne "true" sinon "false"
     * @param array provider => version
     * @return bool
     * @see
     */

    public function checkVersion($versions)
    {
        $v = $this->getVersion();
        $p = $this->getServerType();

        foreach ($versions as $provider => $version) {
            if (version_compare($v, $version) >= 0 && $p === $provider) {
                return true;
            }
        }
        return false;
    }

    public function provider()
    {
        return array('MySQL', 'MariaDB', 'Percona Server');
    }

    public function getProcesslist($time = 1)
    {
        if ($this->checkVersion(array('MySQL' => '5.1', 'Percona Server' => '5.1', 'MariaDB' => '5.1'))) {
            $time = intval($time);

            if ($this->checkVersion(array('MySQL' => '8.0')))
            {
                $sql  = "select * from performance_schema.processlist where command NOT IN ('Sleep', 'Binlog Dump')
                AND user NOT IN ('system user', 'event_scheduler') AND TIME > ".$time;
            }
            else
            {
                $sql  = "select * from information_schema.processlist where command NOT IN ('Sleep', 'Binlog Dump')
                AND user NOT IN ('system user', 'event_scheduler') AND TIME > ".$time;
            }

            $res  = $this->sql_query($sql);
            $ret  = array();
            while ($data = $this->sql_fetch_array($res, MYSQLI_ASSOC)) {
                $ret[] = $data;
            }

            return $ret;
        }
    }

    public function getForeignKey()
    {
        if ($this->checkVersion(array('MySQL' => '5.0', 'Percona Server' => '5.0', 'MariaDB' => '5.0'))) {
            //TODO
        }
    }

    public function sql_thread_id()
    {
        return mysqli_thread_id($this->link);
    }

    public function getProxysqlVariables($var = '')
    {

        if (empty($this->proxysql_variables)) {
            $sql = "select * from main.runtime_global_variables;";
            $res = $this->sql_query($sql);

            while ($data = $this->sql_fetch_array($res, MYSQLI_ASSOC)) {
                $this->proxysql_variables[$data['variable_name']] = $data['variable_value'];
            }
        }

        if (empty($var)) {
            return $this->proxysql_variables;
        } else {
            if (!empty($this->proxysql_variables[$var])) {
                return $this->proxysql_variables[$var];
            } else {
                return false;
            }
        }
    }


    public function sql_connect_error()
    {
        return mysqli_connect_error();
    }

}
