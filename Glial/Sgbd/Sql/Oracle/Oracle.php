<?php

namespace Glial\Sgbd\Sql\Oracle;

use \Glial\Sgbd\Sql\Sql;

class Oracle extends Sql
{

    const ESC            = '"';
    const HISTORY_ACTIVE = false;

    public $ESC = '';
    public $db;
    public $link;
    private $login;
    protected $stid;

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

    public function sql_connect($host, $login, $password, $database, $port = 1521)
    {
        if (!is_numeric($port)) {
            $port = 1521;
        }

        $string = '//' . $host . ':' . $port . '/' . $database;

        $this->login = $login;
        $this->link  = oci_connect($login, $password, $string);

        if (!$this->link) {
            throw new \Exception('GLI-012 : Impossible to connect to : ' . $host . 'string : ' . $string);
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
        //return oci_select_db($this->link, $dbname);
    }

    /*
     * @since Glial 1.0
     * @return Returns FALSE on failure. For successful SELECT, SHOW, DESCRIBE or EXPLAIN queries _query() will return a oci_result object. For other successful queries _query() will return TRUE.
     * @param string $dbname The database name.
     * @description Performs a query against the database. 
     * @alias make the same as oci_query
     * @see oci_query http://php.net/manual/en/mysqli.query.php
     */

    public function _query($sql)
    {

        $this->stid = oci_parse($this->link, $sql);
        if ($this->stid != false) {
            // parsing empty query != false 
            if (oci_execute($this->stid)) {

                return $this->stid;
                // executing empty query != false 

                /*
                  if (oci_fetch_all($this->stid, $data, 0, -1, OCI_FETCHSTATEMENT_BY_ROW) == false) {
                  // but fetching executed empty query results in error (ORA-24338: statement handle not executed)
                  $e = oci_error($this->stid);
                  echo $e['message'];
                  } */
            } else {
                $e = oci_error($this->stid);
                echo $e['message'];

                return false;
            }
        } else {
            $e = oci_error($this->link);
            echo $e['message'];

            return false;
        }

        //$stid = oci_parse();
        //oci_execute($stid);

        return $this->stid;
    }

    public function sql_num_rows($res)
    {
        return oci_num_rows($res);
    }

    public function sql_close()
    {
        $this->link = oci_close($this->link);
    }

    public function sql_affected_rows()
    {
        return oci_num_rows($this->stid);


        //TODO : have to use in case of SELECT, removed here coz not performent at all
        $res = '';
        return oci_fetch_all($this->stid, $res);
    }

    public function sql_real_escape_string($data)
    {
        return addslashes($data);
    }

    public function sql_insert_id()
    {
        return $this->last_id;
    }

    public function _insert_id()
    {
        return 0;
        //return oci_insert_id($this->link);
    }

    public function _error()
    {
        return oci_error($this->link);
    }

    public function sql_fetch_array($res, $resulttype = OCI_BOTH)
    {
        return oci_fetch_array($res, $resulttype);
    }

    public function sql_to_array($sql, $assoc = OCI_ASSOC)
    {
        $res = $this->sql_query($sql);

        $rep = array();

        while ($tab = oci_fetch_array($res, $assoc)) {
            $rep[] = $tab;
        }

        return $rep;
    }

    public function sql_fetch_object($res)
    {
        return oci_fetch_object($res);
    }

    public function sql_fetch_row($res)
    {
        return oci_fetch_row($res);
    }

    public function sql_num_fields($res)
    {
        return oci_num_fields($res);
    }

    public function sql_field_name($res, $i)
    {
        return oci_fetch_fields($res, $i);
    }

    public function sql_free_result($res)
    {
        return oci_free_result($res);
    }

    public function sql_fetch_field($res, $i = 0)
    {
        return oci_fetch_field($res, $i);
    }

    public function sql_fetch_yield($sql, $oci = OCI_BOTH)
    {
        $res = $this->sql_query($sql);

        while ($tab = $this->sql_fetch_array($res, $oci)) {
            yield $tab;
        }
    }

    /**
     * (Glial 2.1)<br/>
     * get all table name and all view and return in an array
     * @author Aurélien LEQUOY, <aurelien.lequoy@esysteme.com>
     * @license GPL
     * @license http://opensource.org/licenses/gpl-license.php GNU Public License
     * @link http://www.glial-framework-php.org/en/manual/mysql.sql_fetch_all.php
     * @return array
     * @since 3.1
     * @version 3.1
     * 
     */
    public function sql_fetch_all($sql, $oci = OCI_BOTH)
    {
        $stid = $this->sql_query($sql);
        oci_fetch_all($stid, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);

        return $res;
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
        $sql = "select table_name from dba_tables where owner = '" . strtoupper($this->login) . "' ORDER BY table_name";

        $res = $this->_query($sql);

        $table = array();
        $view  = array();


        while ($ar = $this->sql_fetch_array($res)) {
            $table[] = $ar[0];
        }

        $ret['table'] = $table;


        return $ret;
    }

    public function getIndexUnique($table_name)
    {


        $sql = "SELECT cols.column_name as column_name FROM all_constraints cons, all_cons_columns cols
                WHERE cols.table_name = '" . $table_name . "' "
                . "AND cons.constraint_type = 'P' "
                . "AND cons.constraint_name = cols.constraint_name "
                . "AND cons.owner = cols.owner "
                . "AND cons.owner = '" . strtoupper($this->login) . "' "
                . "ORDER BY cols.table_name, cols.position";



        $res = $this->sql_query($sql);

        $index = array();
        while ($ob    = $this->sql_fetch_object($res)) {


            $index[] = $ob->COLUMN_NAME;
        }
        return $index;
    }

    public function getCreateTable($table, $schema = '')
    {
        if (empty($schema)) {
            $schema = $this->login;
        }

        $sql = "select dbms_metadata.get_ddl( 'TABLE', '" . $table . "', '" . strtoupper($schema) . "' ) as data from dual";

        $res = $this->sql_query($sql);

        while ($ar = $this->sql_fetch_object($res)) {

            //debug($ar);
            //$table = $ar[0];
        }

        return 'CREATE TABLE ...';
    }

    public function getDescription($table)
    {
        $sql = "select column_name as Field,data_type as Type,data_length as  Length  from user_tab_columns where table_name = '" . $table . "' order by column_id";
        return $sql;


        /**
         * SELECT 
         * column_name "Name",
         * nullable "Null?",
         * concat(concat(concat(data_type,'('),data_length),')') "Type"
         * FROM user_tab_columns
         * WHERE table_name='TABLE_NAME_TO_DESCRIBE';
         */
    }

}
