<?php

namespace Glial\Sgbd\Sql\Oracle;

use \Glial\Sgbd\Sql\Sql;

class Oracle extends Sql {

    public $db;
    public $link;

    function __construct($name, $elem) {
        $this->setName($name, $elem);
    }

    /*
     * @since Glial 1.0
     * @return Returns an object which represents the connection to a MySQL Server.
     * @parameters dbname The database name.
     * @alias make the same as mysqli::select_db and init charset connection in utf-8
     */

    public function sql_connect($host, $login, $password, $database, $port = 1521) {
        if (!is_numeric($port)) {
            $port = 1521;
        }

         $string = '//'.$host.':'.$port.'/'.$database;
         
         
        $this->link = oci_connect($login,$password, $string);

       
        
        
        
        if (!$this->link) {
            throw new \Exception('GLI-012 : Impossible to connect to : ' . $host. 'string : '.$string);
        }

        return $this->link;
    }
    /*
     * @since Glial 1.0
     * @return Returns TRUE on success or FALSE on failure.
     * @param string $dbname The database name.
     * @alias make the same as mysqli::select_db
     */

    public function sql_select_db($dbname) {
        $this->db = $dbname;
        return oci_select_db($this->link, $dbname);
    }

    /*
     * @since Glial 1.0
     * @return Returns FALSE on failure. For successful SELECT, SHOW, DESCRIBE or EXPLAIN queries _query() will return a oci_result object. For other successful queries _query() will return TRUE.
     * @param string $dbname The database name.
     * @description Performs a query against the database. 
     * @alias make the same as oci_query
     * @see oci_query http://php.net/manual/en/mysqli.query.php
     */

    public function _query($sql) {
        return oci_query($this->link, $sql);
    }

    public function sql_num_rows($res) {
        return oci_num_rows($res);
    }

    public function sql_close() {
        $this->link = oci_close($this->link);
    }

    public function sql_affected_rows() {
        return oci_affected_rows($this->link);
    }

    public function sql_real_escape_string($data) {
        return oci_real_escape_string($this->link, $data);
    }

    public function sql_insert_id() {
        return $this->last_id;
    }

    public function _insert_id() {
        return oci_insert_id($this->link);
    }

    public function _error() {
        return oci_error($this->link);
    }

    public function sql_fetch_array($res, $resulttype = oci_BOTH) {
        return oci_fetch_array($res, $resulttype);
    }

    public function sql_to_array($res) {
        $rep = array();

        while ($tab = oci_fetch_array($res, MYSQL_ASSOC)) {

            $rep[] = $tab;
        }

        return $rep;
    }

    public function sql_fetch_object($res) {
        return oci_fetch_object($res);
    }

    public function sql_fetch_row($res) {
        return oci_fetch_row($res);
    }

    public function sql_num_fields($res) {
        return oci_num_fields($res);
    }

    public function sql_field_name($res, $i) {
        return oci_fetch_fields($res, $i);
    }

    public function sql_free_result($res) {
        return oci_free_result($res);
    }

    public function sql_fetch_field($res, $i = 0) {
        return oci_fetch_field($res, $i);
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
    public function getListTable() {
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

    public function getIndexUnique($table_name) {
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

}
