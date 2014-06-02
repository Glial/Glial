<?php

namespace Glial\Sgbd\Sql\Mysql;

use \Glial\Sgbd\Sql\Sql;

class Mysql extends Sql
{

    public $db;
    public $link;

    function __construct($name, $elem)
    {
        $this->setName($name, $elem);
    }

    /*
     * @since Glial 1.0
     * @return Returns an object which represents the connection to a MySQL Server.
     * @parameters dbname The database name.
     * @alias make the same as mysqli::select_db and init charset connection in utf-8
     */

    public function sql_connect($host, $login, $password, $dbname, $port = 3306)
    {
        $this->link = mysqli_connect($host, $login, $password, $dbname, $port);

        if (!$this->link) {
            throw new \Exception('GLI-012 : Impossible to connect to : ' . $host . ":" . $port);
        }

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

    public function _query($sql)
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
    }

    public function sql_affected_rows()
    {
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
        $sql = "show keys from `" . $table_name . "` in " . $this->db;

        $res = $this->_query($sql);

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
        return mysqli_multi_query ($this->link,  $sql );
    }

}
