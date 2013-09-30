<?php

namespace \glial\sgbd\sql\mysql;

use glial\sgbd\sql\Sql;

class Mysql extends Sql
{
    public $link;

    public function sql_connect($host, $login, $password)
    {
        $this->link = mysqli_connect($host, $login, $password);

        //mysqli_set_charset('utf8',$this->link);
        $this->_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'latin1'");
        $this->_query("SET NAMES 'utf8'");

    }

    public function sql_select_db($db)
    {
        return mysqli_select_db($this->link, $db ) or die (mysqli_error($this->link));
    }

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
        return mysqli_real_escape_string($this->link,$data);
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

    public function sql_fetch_array($res)
    {
        return mysqli_fetch_array($res);
    }

    public function sql_to_array($res)
    {
        $rep = array();

        while ($tab = mysqli_fetch_array($res,MYSQL_ASSOC)) {

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

    public function sql_fetch_field($res, $i=0)
    {
        return mysqli_fetch_field($res, $i);
    }
}
