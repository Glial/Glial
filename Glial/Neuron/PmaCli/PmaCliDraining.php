<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Neuron\PmaCli;

use \Glial\Cli\Color;

class PmaCliDraining
{

    const NB_DELETE = 800; //nombre de delete en mÃªme temps
    const NB_PROCESS = 1;
    const NB_THREAD = 1; //must be below that the number of CPU
    const DEBUG = false;
    const COLOR = true;
    const PREFIX = "DELETE_";

    public $link_to_purge;
    public $schema_to_purge;
    public $table_to_purge = array();
    public $main_field = array(); // => needed
    public $main_table;
    public $init_where;
    private $table_in_error = array();
    private $di = array();
    private $rows_to_delete = array();

    function __construct($di)
    {
        $this->di['db'] = $di;
        
        // set @@skip_replication = ON
    }

    public function start()
    {
        $db = $this->di['db']->sql($this->link_to_purge);
        $db->sql_select_db($this->schema_to_purge);
        
        
        // to not affect history server, read : https://mariadb.com/kb/en/mariadb/documentation/replication/standard-replication/selectively-skipping-replication-of-binlog-events/
        $sql = "SET @@skip_replication = ON;";
        $db->sql_query($sql);
        
        
        $this->view = false;


        $this->rows_to_delete = array();
        //create temp table
        $this->createAllTemporaryTable();

        //get id to delete
        $this->init();

        //delete items
        $temp = $this->rows_to_delete;
        //purge table with empty row
        foreach ($temp as $key => $val) {
            if (empty($val)) {
                unset($this->rows_to_delete[$key]);
            }
        }

        $this->delete(1);
        $this->deleteOther();

        return $this->rows_to_delete;
    }

    public function createTemporaryTable($table)
    {
        $db = $this->di['db']->sql($this->link_to_purge);
        $db->sql_select_db($this->schema_to_purge);


        //$sql = "DROP TABLE IF EXISTS `" . self::PREFIX . $table . "`;";
        //$db->sql_query($sql);
        //$this->log($sql);

        $fields = $this->getTypeOfPrimaryKey($table);

        if (count($fields) === 0) {
            //throw new \Exception('GLI-071 : No primary key found'); 

            if (self::DEBUG) {
                echo Color::getColoredString("--No Primary key found : '" . $table . "'", 'black', 'yellow', 'bold') . PHP_EOL;
            }


            $this->table_in_error[] = $table;
            return false;
        }

        $line = array();
        $index = array();

        foreach ($fields as $field) {
            $line[] = "`" . $field['name'] . "` " . $field['type'];
            $index[] = "`" . $field['name'] . "`";
        }

        $sql = "CREATE TEMPORARY TABLE IF NOT EXISTS `" . self::PREFIX . $table . "`(";
        $sql .= implode(",", $line);
        $sql .= ", PRIMARY KEY (" . implode(",", $index) . "));";
        $db->sql_query($sql);
        
        
        //$this->log($sql);
        $sql = "TRUNCATE TABLE `" . self::PREFIX . $table . "`;";
        $db->sql_query($sql);
        //$this->log($sql);
    }

    public function init()
    {
        $this->view = false;
        $db = $this->di['db']->sql($this->link_to_purge);
        $db->sql_select_db($this->schema_to_purge);

        //feed id with main table
        $pri = $this->getPrimaryKey($this->main_table);
        $primary_key = "`" . implode('`,`', $pri) . "`";

        $sql = "INSERT INTO `" . self::PREFIX . $this->main_table . "`  SELECT " . $primary_key . " FROM `" . $this->main_table . "`
                WHERE " . $this->init_where . ";";

        $db->sql_query($sql);
        $this->log($sql);

        $this->setAffectedRows($this->main_table);

        
        if (empty($this->rows_to_delete[$this->main_table]))
        {
            
            return $this->rows_to_delete;
        }
        
        
        $this->feedDeleteTableWithJoin();
        $this->feedDeleteTableWithFk();

        if (self::DEBUG) {
            echo Color::getColoredString("--Table without primary key", 'grey', 'red') . PHP_EOL;
        }
    }

    public function feedDeleteTableWithFk()
    {

        if (self::DEBUG) {
            echo "--###################################################################" . PHP_EOL;
            echo "--##################### FEED FROM FK ################################" . PHP_EOL;
            echo "--###################################################################" . PHP_EOL;
        }

        $db = $this->di['db']->sql($this->link_to_purge);
        $db->sql_select_db($this->schema_to_purge);

        $table_to_order = $this->getForeignKeys();
        $list_tables = $this->orderBy($table_to_order, "ASC");

        //debug($list_tables);

        foreach ($list_tables as $sub_array) {

            foreach ($sub_array as $table_name) {
                $sql = "SELECT * FROM `information_schema`.`KEY_COLUMN_USAGE` "
                        . "WHERE CONSTRAINT_SCHEMA ='" . $this->schema_to_purge . "' "
                        . "AND REFERENCED_TABLE_SCHEMA='" . $this->schema_to_purge . "' "
                        . "AND TABLE_NAME ='" . $table_name . "';";

                $res = $db->sql_query($sql);
                $this->log($sql . "\n");
                //$this->log($sql);

                while ($ob = $db->sql_fetch_object($res)) {

                    /*
                      if (in_array($ob->TABLE_NAME, $this->table_in_error)) {
                      continue;
                      } */

                    $pri = $this->getPrimaryKey($table_name);
                    $primary_key = "a.`" . implode('`,a.`', $pri) . "`";

                    $sql = "INSERT IGNORE INTO `" . self::PREFIX . $table_name . "`
                    SELECT " . $primary_key . " FROM `" . $table_name . "` a
                    INNER JOIN `" . self::PREFIX . $ob->REFERENCED_TABLE_NAME . "` b ON b.`" . $ob->REFERENCED_COLUMN_NAME . "` = a.`" . $ob->COLUMN_NAME . "`;";
                    $db->sql_query($sql);

                    $this->setAffectedRows($table_name);

                    $this->log($sql);
                }
            }
        }
    }

    public function getPrimaryKey($table)
    {
        $db = $this->di['db']->sql($this->link_to_purge);
        $db->sql_select_db($this->schema_to_purge);

        $sql = "SHOW INDEX FROM `" . $table . "` WHERE Key_name ='PRIMARY'";
        $res = $db->sql_query($sql);

        //$this->log($sql);

        if ($db->sql_num_rows($res) == "0") { // should be == 1 have to fix it for PROD_LOT_ITEM
            throw new \Exception("GLI-067 : this table '" . $table . "' haven't primary key !");
        } else {

            $index = array();

            while ($ob = $db->sql_fetch_object($res)) {
                $index[] = $ob->Column_name;
            }

            return $index;
        }
    }

    public function getTypeOfPrimaryKey($table)
    {
        $db = $this->di['db']->sql($this->link_to_purge);
        $db->sql_select_db($this->schema_to_purge);

        $sql = "SELECT COLUMN_TYPE, COLUMN_NAME FROM `information_schema`.`COLUMNS` WHERE `TABLE_NAME` = '" . $table . "' AND COLUMN_KEY ='PRI' AND `TABLE_SCHEMA` = '" . $this->schema_to_purge . "'";
        $res = $db->sql_query($sql);

        //$this->log($sql);

        if ($db->sql_num_rows($res) === "0") { // should be == 1 have to fix it for PROD_LOT_ITEM
            throw new \Exception("GLI-067 : this table [" . $table . "] haven't primary key !");
        } else {

            $ret = array();
            while ($ob = $db->sql_fetch_object($res)) {
                $line = array();

                $line['name'] = $ob->COLUMN_NAME;
                $line['type'] = $ob->COLUMN_TYPE;

                $ret[] = $line;
            }

            return $ret;
        }
    }

    public function createAllTemporaryTable()
    {
        $db = $this->di['db']->sql($this->link_to_purge);
        $db->sql_select_db($this->schema_to_purge);

        foreach ($db->getListTable()['table'] as $table) {
            if (substr($table, 0, 7) !== self::PREFIX) {
                $this->createTemporaryTable($table);
            }
        }
    }

    public function feedDeleteTableWithJoin()
    {

        if (self::DEBUG) {
            echo "###################################################################" . PHP_EOL;
            echo "##################### FEED FROM FIELD #############################" . PHP_EOL;
            echo "###################################################################" . PHP_EOL;
        }


        $db = $this->di['db']->sql($this->link_to_purge);
        $db->sql_select_db($this->schema_to_purge);


        $in_clause = "'" . implode("','", array_keys($this->main_field)) . "'";


        $table_to_purge = array_merge($this->table_to_purge, $db->getListTable()['table']);

        //debug($table_to_purge);

        $table_to_purge = array_unique($table_to_purge);

        //debug($table_to_purge);

        foreach ($table_to_purge as $table) {

            if ($table === $this->main_table) { //prevent duplicate key on feed of father table 
                continue;
            }

            if (substr($table, 0, strlen(self::PREFIX)) == self::PREFIX) {
                continue;
            }


            if (in_array($table, $this->table_in_error)) {
                continue;
            }

            $sql = "SELECT * "
                    . "FROM `information_schema`.`COLUMNS` "
                    . "WHERE TABLE_SCHEMA='" . $this->schema_to_purge . "' "
                    . "AND TABLE_NAME ='" . $table . "' "
                    . "AND COLUMN_NAME in (" . $in_clause . ");";

            $colones = $db->sql_fetch_yield($sql);

            //$this->log($sql);

            foreach ($colones as $colone) {

                if (self::PREFIX . $table != self::PREFIX . $this->main_field[$colone['COLUMN_NAME']]) {

                    $primary_key = "a.`" . implode('`,a.`', $this->getPrimaryKey($table)) . "`";

                    $sql = "INSERT IGNORE INTO `" . self::PREFIX . $table . "`
                    SELECT " . $primary_key . " FROM `" . $table . "` a
                    INNER JOIN `" . self::PREFIX . $this->main_field[$colone['COLUMN_NAME']] . "` b ON a.`" . $colone['COLUMN_NAME'] . "` = b.`" . $colone['COLUMN_NAME'] . "`;";

                    $db->sql_query($sql);
                    $this->setAffectedRows($table);


                    $this->log($sql);
                }
            }
        }
    }

    public function log($sql)
    {
        if (self::DEBUG) {

            $db = $this->di['db']->sql($this->link_to_purge);
            $db->sql_select_db($this->schema_to_purge);

            if (self::COLOR) {
                echo \SqlFormatter::highlight($sql);
            } else {
                echo \SqlFormatter::format($sql, false) . PHP_EOL;
            }

            if (self::COLOR) {
                echo Color::getColoredString("--Row affected : " . end($db->query)['rows'] . " - Time : " . end($db->query)['time'], 'black', 'green', 'bold') . "\n";
            } else {
                echo "--Row affected : " . end($db->query)['rows'] . PHP_EOL;
            }
        }
    }

    public function getTableError()
    {
        return $this->table_in_error;
    }

    public function getForeignKeys()
    {
        //get list of FK and put in array
        $db = $this->di['db']->sql($this->link_to_purge);
        $db->sql_select_db($this->schema_to_purge);

        $sql = "SELECT * FROM `information_schema`.`KEY_COLUMN_USAGE` "
                . "WHERE CONSTRAINT_SCHEMA ='" . $this->schema_to_purge . "' "
                . "AND REFERENCED_TABLE_SCHEMA='" . $this->schema_to_purge . "' "
                . "AND REFERENCED_TABLE_NAME IS NOT NULL "
                . "AND TABLE_NAME not like '" . self::PREFIX . "%';";

        $res = $db->sql_query($sql);
        if (self::DEBUG) {
            $this->log($sql);
        }

        $order_to_feed = array();

        while ($ob = $db->sql_fetch_object($res)) {
            $order_to_feed[$ob->REFERENCED_TABLE_NAME][] = $ob->TABLE_NAME;
        }

        return $order_to_feed;
    }

    public function orderBy($array, $order = "ASC")
    {
        $level = array();
        $i = 0;
        while (count($array) != 0) {

            //echo "level " . $i . PHP_EOL;
            $temp = $array;

            foreach ($temp as $father_name => $tab_father) {
                foreach ($tab_father as $key_child => $table_child) {
                    if (!in_array($table_child, array_keys($array))) {

                        if (empty($level[$i]) || !in_array($table_child, $level[$i])) {
                            $level[$i][] = $table_child;
                        }
                        unset($array[$father_name][$key_child]);
                    }
                }
            }

            $temp = $array;

            foreach ($temp as $key => $tmp) {
                if (count($tmp) == 0) {
                    unset($array[$key]);

                    if (empty($level[$i + 1]) || !in_array($key, $level[$i + 1])) {
                        $level[$i + 1][] = $key;
                    }
                }
            }
            sort($level[$i]);
            $i++;
        }

        if ($order === "ASC") {
            sort($level);
        } elseif ($order === "DESC") {
            rsort($level);
        }

        return $level;
    }

    public function delete($thread_number)
    {
        $db = $this->di['db']->sql($this->link_to_purge);
        $db->sql_select_db($this->schema_to_purge);

        $table_to_order = $this->getForeignKeys();
        $list_tables = $this->orderBy($table_to_order, "DESC");

        foreach ($list_tables as $levels) {
            foreach ($levels as $table) {

                $primary_keys = $this->getPrimaryKey($table);

                $join = array();
                $fields = array();
                foreach ($primary_keys as $primary_key) {
                    $join[] = " `a`.`" . $primary_key . "` = b.`" . $primary_key . "` ";
                    $fields[] = " b.`" . $primary_key . "` ";
                }

                $field = implode(" ", $join);

                do {
                    $sql = "DELETE a FROM " . $table . " a
                    INNER JOIN " . self::PREFIX . $table . " as b ON  " . implode(" AND ", $join);

                    $db->sql_query($sql);
                    $this->log($sql);


                    if (end($db->query)['rows'] == "-1") {
                        throw new \Exception('PMACLI-666 : Foreign key error, have to update lib of cleaner or order of table set in param');
                    }
                } while (end($db->query)['rows'] == self::NB_DELETE);


                $sql = "TRUNCATE TABLE `" . self::PREFIX . $table . "`";
                $db->sql_query($sql);
            }
        }
    }

    public function setAffectedRows($table)
    {
        $db = $this->di['db']->sql($this->link_to_purge);
        $db->sql_select_db($this->schema_to_purge);

        if (empty($this->rows_to_delete[$table])) {
            $this->rows_to_delete[$table] = end($db->query)['rows'];
        } else {
            $this->rows_to_delete[$table] += end($db->query)['rows'];
        }
    }

    public function deleteOther()
    {

        $db = $this->di['db']->sql($this->link_to_purge);
        $db->sql_select_db($this->schema_to_purge);

        foreach ($db->getListTable()['table'] as $table) {
            if (substr($table, 0, strlen(self::PREFIX)) === self::PREFIX) {

                $primary_keys = $this->getPrimaryKey($table);

                $join = array();
                foreach ($primary_keys as $primary_key) {
                    $join[] = " a.`" . $primary_key . "` = b.`" . $primary_key . "` ";
                }

                $sql = "DELETE a FROM " . substr($table, strlen(self::PREFIX)) . " a
                    INNER JOIN " . $table . " b ON " . implode(" AND ", $join) . ";";

                $db->sql_query($sql);
                $this->log($sql);
            }
        }
    }

}
