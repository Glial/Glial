<?php

namespace Glial\Sgbd\Sql\Mysql;

use \Glial\Sgbd\Sql\Sql;

class Mysql extends Sql
{

    const ESC = '`';

    public $ESC = '`';
    public $db;
    public $link;
    public $server_type;
    public $version = '';
    public $version_full = '';
    public $version_comment = '';
    public $status = array();
    public $variables = array();
    public $host;
    public $port;

    function __construct($name, $elem)
    {
        $this->setName($name, $elem);
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

        $this->link = mysqli_connect($host, $login, $password, $dbname, $port);
        $this->db = $dbname;

        if (!$this->link) {
            return false;
            //throw new \Exception('GLI-012 : Impossible to connect to : ' . $host . ":" . $port);
        }



        $this->is_connected = true;

        mysqli_set_charset($this->link, 'utf8');
        $this->_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");
        $this->_query("SET NAMES 'utf8'");

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

    protected function _query($sql)
    {
        
       
        return mysqli_query($this->link, $sql);
    }

    public function sql_num_rows($res)
    {
        return mysqli_num_rows($res);
    }

    public function sql_close()
    {
        $this->link = mysqli_close($this->link);
        $this->is_connected = false;
    }

    public function sql_affected_rows($stid = '')
    {
        //$stid='' to maintain compatibility with oracle
        return mysqli_affected_rows($this->link);
    }

    public function sql_real_escape_string($data)
    {
        return mysqli_real_escape_string($this->link, $data);
    }

    public function sql_insert_id()
    {
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

    public function sql_fetch_array($res, $resulttype = MYSQLI_BOTH)
    {
        return mysqli_fetch_array($res, $resulttype);
    }

    public function sql_to_array($res)
    {
        $rep = array();

        while ($tab = mysqli_fetch_array($res, MYSQL_ASSOC)) {

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
     * @return array
     * 
     */
    public function getListTable()
    {
        $sql = "SHOW FULL TABLES";

        $res = $this->_query($sql);

        $table = array();
        $view = array();


        while ($ar = $this->sql_fetch_array($res)) {
            if ($ar['Table_type'] === "VIEW") {
                $view[] = $ar[0];
            } else {
                $table[] = $ar[0];
            }
        }

        $ret['table'] = $table;
        $ret['view'] = $view;


        return $ret;
    }

    public function getIndexUnique($table_name)
    {
        $sql = "show keys from `" . $table_name . "` in `" . $this->db."`";
        $res = $this->_query($sql);

        if (!$res) {
            throw new \Exception("GLI-030 : problem with this query : '" . $sql . "'");
        }

        $index = array();
        while ($ob = $this->sql_fetch_object($res)) {

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

        if (empty($this->version)) {

            $sql = "SHOW GLOBAL VARIABLES LIKE 'version'";

            $res = $this->sql_query($sql);
            $data = $this->sql_fetch_array($res, MYSQLI_ASSOC);

            $version = $data['Value'];
            $this->version_full = $version;

            if (strpos($version, "-")) {


                $this->version = explode("-", $version)[0];
            } else {
                $this->version = $version;
            }
        }

        return $this->version;
    }

    /**
     * Returns server type for current connection
     *
     * Known types are: Drizzle, MariaDB and MySQL (default)
     * @author from phpMyAdmin
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @return string
     * @since 3.0a First time this was introduced.
     * @version 3.0.1a
     */
    public function getServerType()
    {

        if (empty($this->version_full)) {
            $this->getVersion();
        }

        if (empty($this->version_comment)) {
            $this->getVersionComment();
        }


        if (empty($this->server_type)) {

            $this->server_type = 'MySQL';

            if (stripos($this->version_full, 'drizzle')) {
                $this->server_type = 'Drizzle';
            } else if (stripos($this->version_comment, 'mariadb') !== false) {
                $this->server_type = 'MariaDB';
            } else if (stripos($this->version_comment, 'percona') !== false) {
                $this->server_type = 'Percona Server';
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
    public function getVariable()
    {

        if (empty($this->version)) {

            $sql = "SHOW GLOBAL VARIABLES LIKE 'UpTime'";

            $res = $this->sql_query($sql);
            $data = $this->sql_fetch_array($res, MYSQLI_ASSOC);

            $version = $data['Value'];
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
    public function getStatus($var = '')
    {

        if (empty($this->status)) {
            $sql = "SHOW GLOBAL STATUS";
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
            $sql = "SHOW GLOBAL variables ";
            $res = $this->sql_query($sql);

            while ($data = $this->sql_fetch_array($res, MYSQLI_ASSOC)) {
                $this->variables[$data['Variable_name']] = $data['Value'];
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
        $sql = "show grants for current_user;";
        $res = $this->sql_query($sql);
        $data = $this->sql_fetch_array($res, MYSQLI_NUM);

        preg_match("/GRANT ([\w ,]+) ON /", $data[0], $output_array);
        return explode(', ', $output_array[1]);
    }

    public function getCreateTable($table, $schema = '')
    {

        $sql = "SHOW CREATE TABLE `" . $table . "`";

        $res = $this->sql_query($sql);


        while ($data = $this->sql_fetch_array($res, MYSQLI_NUM)) {
            $elem = $data[1];
        }


        if (empty($elem)) {
            throw new \Exception("GLI-101 : couldn't find the table : '" . $table . "'");
        }


        return $elem;
    }

    public function getDescription($table)
    {
        $sql = "SELECT COLUMN_NAME, DATA_TYPE,CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE table_name = '" . $table . "' AND TABLE_SCHEMA = database()
                ORDER BY `COLUMNS`.`CHARACTER_MAXIMUM_LENGTH` ASC";

        //have to switch TABLE_SCHEMA = database() by something else in future if needed

        $res = $this->sql_query($sql);

        $table = array();
        while ($ar = $this->sql_fetch_array($res, MYSQL_NUM)) {

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

            $sql = "SHOW GLOBAL VARIABLES LIKE 'version_comment'";

            $res = $this->sql_query($sql);
            $data = $this->sql_fetch_array($res, MYSQLI_ASSOC);

            $version = $data['Value'];
            $this->version_comment = $version;
        }

        return $this->version_comment;
    }

    
    
    
}
