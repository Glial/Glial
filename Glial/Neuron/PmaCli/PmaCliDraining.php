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
    public $debug           = true;
    public $color           = true;
    public $prefix          = "DELETE_";
    public $link_to_purge;
    public $schema_to_purge;
    public $schema_delete   = "CLEANER";
    public $table_to_purge  = array();
    public $main_field      = array(); // => needed
    public $main_table;
    public $init_where;
    private $table_in_error = array();
    private $di             = array();
    private $rows_to_delete = array();
    public $foreign_keys    = array();
    private $table_impacted = array();
    public $id_cleaner      = 0;

    function __construct($di)
    {
        $this->di['db'] = $di;
    }

    public function start()
    {
        $db = $this->di['db']->sql($this->link_to_purge);
        $db->sql_select_db($this->schema_to_purge);

        // to not affect history server, read : https://mariadb.com/kb/en/mariadb/documentation/replication/standard-replication/selectively-skipping-replication-of-binlog-events/
        $sql = "SET @@skip_replication = ON;";

        $this->log($sql);
        $db->sql_query($sql);

        $sql = "CREATE DATABASE IF NOT EXISTS ".$this->schema_delete;

        if ($this->debug) {
            echo $sql."\n";
        }

        $db->sql_query($sql);

        $this->rows_to_delete = array();


        if ($this->debug) {
            echo "CREATE TEMP TABLE !\n";
        }
        $this->createAllTemporaryTable();

        if ($this->debug) {
            echo "INIT !\n";
        }

        $this->init();

        //feed table in the right order to delete later
        $this->feedDeleteTableWithFk();

        //delete items
        $temp = $this->rows_to_delete;
        //purge table with empty row
        foreach ($temp as $key => $val) {
            if (empty($val)) {
                unset($this->rows_to_delete[$key]);
            }
        }

        if (!empty($this->rows_to_delete[$this->main_table])) {
            $this->delete();
        }

        $db->sql_close();

        /*
          debug($this->rows_to_delete);
          debug($db->query);
          exit;
          /** */
        return $this->rows_to_delete;
    }

    public function createTemporaryTable($table)
    {
        $db = $this->di['db']->sql($this->link_to_purge);
        $db->sql_select_db($this->schema_to_purge);

        $fields = $this->getTypeOfPrimaryKey($table);

        if (count($fields) === 0) {
            throw new \Exception('GLI-071 : No primary key found on table "'.$table.'"');

            if ($this->debug) {
                echo Color::getColoredString("--No Primary key found : '".$table."'", 'black', 'yellow', 'bold').PHP_EOL;
            }

            $this->table_in_error[] = $table;
            return false;
        }

        $line  = array();
        $index = array();

        foreach ($fields as $field) {
            $line[]  = "`".$field['name']."` ".$field['type'];
            $index[] = "`".$field['name']."`";
        }

        $sql = "CREATE TABLE IF NOT EXISTS `".$this->schema_delete."`.`".$this->prefix.$table."`(";
        $sql .= implode(",", $line);
        $sql .= ", PRIMARY KEY (".implode(",", $index)."));";
        $db->sql_query($sql);

        //$this->log($sql);
        $sql = "DELETE FROM `".$this->schema_delete."`.`".$this->prefix.$table."`;";
        $db->sql_query($sql);
        //$this->log($sql);
    }

    public function init()
    {
        $this->view = false;
        $db         = $this->di['db']->sql($this->link_to_purge);
        $db->sql_select_db($this->schema_to_purge);

        //feed id with main table
        $pri         = $this->getPrimaryKey($this->main_table);
        $primary_key = "`".implode('`,`', $pri)."`";

        $sql  = "SELECT ".$primary_key." FROM `".$this->main_table."` WHERE ".$this->init_where.";"; // LOCK IN SHARE MODE
        $res  = $db->sql_query($sql);
        $data = $db->sql_fetch_yield($sql);

        $this->log($sql);

        $have_data = false;

        $sql = "INSERT IGNORE INTO `".$this->schema_delete."`.`".$this->prefix.$this->main_table."` (".$primary_key.") VALUES ";
        foreach ($data as $line) {
            $have_data = true;
            $sql .= "('".implode("','", $line)."'),";
        }

        if ($have_data) {
            $sql = rtrim($sql, ",");
            $db->sql_query($sql);
            $this->setAffectedRows($this->main_table);
            $this->log($sql);
        } else {
            $this->rows_to_delete[$this->main_table] = 0;
            return $this->rows_to_delete;
        }
    }

    public function feedDeleteTableWithFk()
    {
        if ($this->debug) {
            echo "--###################################################################".PHP_EOL;
            echo "--##################### FEED FROM FK ################################".PHP_EOL;
            echo "--###################################################################".PHP_EOL;
        }

        $db = $this->di['db']->sql($this->link_to_purge);
        $db->sql_select_db($this->schema_to_purge);

        $list_tables = $this->getOrderBy();

        $tables_impacted = $this->getAffectedTables();

        foreach ($list_tables as $sub_array) {

            foreach ($sub_array as $table_name) {

                if ($table_name === $this->main_table) {
                    continue;
                }

                $sql = "SELECT * FROM `information_schema`.`KEY_COLUMN_USAGE` "
                    ."WHERE CONSTRAINT_SCHEMA ='".$this->schema_to_purge."' "
                    ."AND REFERENCED_TABLE_SCHEMA='".$this->schema_to_purge."' "
                    ."AND TABLE_NAME ='".$table_name."';";

                $res = $db->sql_query($sql);
                $this->log($sql);

                $fks = $db->sql_to_array($res);

                //get virtual FK and merge to real FK
                if (!empty($this->foreign_keys[$this->schema_to_purge][$table_name])) {
                    foreach ($this->foreign_keys[$this->schema_to_purge][$table_name] as $constraint_column => $line) {
                        $tab = explode("-", $line);

                        $tmp = [];

                        $tmp['REFERENCED_TABLE_SCHEMA'] = $tab[0];
                        $tmp['REFERENCED_TABLE_NAME']   = $tab[1];
                        $tmp['REFERENCED_COLUMN_NAME']  = $tab[2];
                        $tmp['COLUMN_NAME']             = $constraint_column;

                        $fks[] = $tmp;
                    }
                }


                foreach ($fks as $fk) {

                    //don't take in consideration the table not impacted by cleaner
                    if (!in_array($fk['REFERENCED_TABLE_NAME'], $tables_impacted)) {
                        continue;
                    }

                    $pri          = $this->getPrimaryKey($table_name);
                    $primary_key  = "a.`".implode('`,a.`', $pri)."`";
                    $primary_keys = "`".implode('`,`', $pri)."`";

                    $sql  = "SELECT ".$primary_key." FROM `".$this->schema_to_purge."`.`".$table_name."` a
                    INNER JOIN `".$this->schema_delete."`.`".$this->prefix.$fk['REFERENCED_TABLE_NAME']."` b ON b.`".$fk['REFERENCED_COLUMN_NAME']."` = a.`".$fk['COLUMN_NAME']."`";
                    $data = $db->sql_fetch_yield($sql);

                    $this->log($sql);

                    $have_data = false;
                    $sql       = "INSERT IGNORE INTO `".$this->schema_delete."`.`".$this->prefix.$table_name."` (".$primary_keys.") VALUES ";
                    foreach ($data as $line) {

                        $have_data = true;
                        $sql .= "('".implode("','", $line)."'),";
                    }

                    if ($have_data) {
                        $sql = rtrim($sql, ",");
                        $db->sql_query($sql);
                        $this->setAffectedRows($table_name);
                        $this->log($sql);
                    }
                }
            }
        }
    }

    public function getPrimaryKey($table)
    {
        $db = $this->di['db']->sql($this->link_to_purge);
        $db->sql_select_db($this->schema_to_purge);

        $sql = "SHOW INDEX FROM `".$table."` WHERE Key_name ='PRIMARY'";
        $res = $db->sql_query($sql);

        $this->log($sql);

        if ($db->sql_num_rows($res) == "0") {
            throw new \Exception("GLI-067 : this table '".$table."' haven't primary key !");
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

        $sql = "SELECT COLUMN_TYPE, COLUMN_NAME FROM `information_schema`.`COLUMNS` WHERE `TABLE_NAME` = '".$table."' AND COLUMN_KEY ='PRI' AND `TABLE_SCHEMA` = '".$this->schema_to_purge."'";
        $res = $db->sql_query($sql);

        //$this->log($sql);

        if ($db->sql_num_rows($res) === "0") { // should be == 1 have to fix it for PROD_LOT_ITEM
            throw new \Exception("GLI-067 : this table [".$table."] haven't primary key !");
        } else {

            $ret = array();
            while ($ob  = $db->sql_fetch_object($res)) {
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
        //$this->getImpactedTable();

        $tables = $this->getOrderBy();


        foreach ($tables as $table2) {
            foreach ($table2 as $table) {
                if (substr($table, 0, 7) !== $this->prefix) {
                    $this->createTemporaryTable($table);
                }
            }
        }
    }

    public function log($sql)
    {

        //echo "GGGGGGGGGGGGGGGGGGGG";
        if ($this->debug) {

            $db = $this->di['db']->sql($this->link_to_purge);
            $db->sql_select_db($this->schema_to_purge);

            if ($this->color) {
                echo \SqlFormatter::highlight($sql);
            } else {
                echo \SqlFormatter::format($sql, false).PHP_EOL;
            }

            if ($this->color) {
                echo Color::getColoredString("--Row affected : ".end($db->query)['rows']." - Time : ".end($db->query)['time'], 'black',
                    'green', 'bold')."\n";
            } else {
                echo "--Row affected : ".end($db->query)['rows'].PHP_EOL;
            }
        }
    }

    public function getTableError()
    {
        return $this->table_in_error;
    }

    private function getForeignKeys()
    {
        //get list of FK and put in array
        $db = $this->di['db']->sql($this->link_to_purge);

        //$db->sql_select_db($this->schema_to_purge);

        $sql = "SELECT * FROM `information_schema`.`KEY_COLUMN_USAGE` "
            ."WHERE CONSTRAINT_SCHEMA ='".$this->schema_to_purge."' "
            ."AND REFERENCED_TABLE_SCHEMA='".$this->schema_to_purge."' "
            ."AND REFERENCED_TABLE_NAME IS NOT NULL ";


        if (!empty($this->prefix)) {
            $sql .= "AND TABLE_NAME not like '".$this->prefix."%';";
        }


        $res = $db->sql_query($sql);
        if ($this->debug) {
            $this->log($sql);
        }

        $order_to_feed = array();

        while ($ob = $db->sql_fetch_object($res)) {
            $order_to_feed[$ob->REFERENCED_TABLE_NAME][] = $ob->TABLE_NAME;
        }

        if ($this->debug) {
            debug($order_to_feed);
        }

        return $order_to_feed;
    }

    private function getVirtualForeignKeys()
    {
        $default = $this->di['db']->sql(DB_DEFAULT);

        //get and set virtual Foreign keys.
        $params = $this->di['db']->sql(DB_DEFAULT)->getParams();
        $sql    = "SELECT * FROM `".$params['database']."`.cleaner_foreign_key WHERE id_cleaner_main = '".$this->id_cleaner."'";


        $foreign_keys = $default->sql_fetch_yield($sql);

        $fk = array();
        foreach ($foreign_keys as $line) {

            if (empty($line['constraint_schema']) || empty($line['constraint_table']) || empty($line['constraint_column']) || empty($line['referenced_schema'])
                || empty($line['referenced_table']) || empty($line['referenced_column'])) {
                throw new \Exception("PMACTRL-334 : Value empty in virtual FK");
            }

            $fk[$line['constraint_schema']][$line['constraint_table']][$line['constraint_column']] = $line['referenced_schema']."-".$line['referenced_table']."-".$line['referenced_column'];
        }

        if (count($fk) != 0) {
            $purge->foreign_keys = $fk;
        }

        $order_to_feed = array();

        foreach ($this->foreign_keys as $db => $tab_table) {
            foreach ($tab_table as $constraint_table => $lines) {

                foreach ($lines as $line) {
                    $tab_referenced                      = explode('-', $line);
                    $order_to_feed[$tab_referenced[1]][] = $constraint_table;
                }
            }
        }



        return $order_to_feed;
    }

    private function getOrderBy($order = 'ASC')
    {
        $real_fk = $this->getForeignKeys();


        if ($this->debug) {
            echo "REAL FOREIGN KEY :";
            print_r($real_fk);
        }

        $virtual_fk = $this->getVirtualForeignKeys();

        //debug($virtual_fk);

        if ($this->debug) {
            echo "VIRTUAL FOREIGN KEY :";
            print_r($real_fk);
        }

        //debug($real_fk);

        $fks = array_merge_recursive($real_fk, $virtual_fk);
        $tmp = $fks;


        //debug($fks);


        if ($this->debug) {
            echo "ALL FOREIGN KEY :";
            //print_r($real_fk);
        }

        foreach ($tmp as $key => $tab) {
            $fks[$key] = array_unique($fks[$key]);
        }


        if ($this->debug) {
            echo "Remove key in dubble :";
            //print_r($real_fk);
        }

        //remove all tables with no father from $this->main_table
        $fks = $this->removeTableNotImpacted($fks);

        if ($this->debug) {
            echo "removed table not impacted :";
            //debug($real_fk);
        }

        $level   = array();
        $level[] = $this->table_to_purge;

        $array = $fks;


        //debug($array);

        $i    = 0;
        while ($last = count($array) != 0) {

            //echo "level " . $i . PHP_EOL;
            $temp = $array;

            foreach ($temp as $father_name => $tab_father) {
                foreach ($tab_father as $key_child => $table_child) {
                    if (!in_array($table_child, array_keys($array))) {

                        if (empty($level[$i]) || !in_array($table_child, $level[$i])) {
                            $level[$i][] = $table_child;
                        }
                        //debug($level);
                        unset($array[$father_name][$key_child]);
                        //debug($array);
                    }
                }
            }

            $temp = $array;

            // retirer les tableaux vides, et remplissage avec clefs
            foreach ($temp as $key => $tmp) {
                if (count($tmp) == 0) {
                    unset($array[$key]);
                    if (empty($level[$i + 1]) || !in_array($key, $level[$i + 1])) {
                        $level[$i + 1][] = $key;
                    }
                }
            }


            if ($last == count($array)) {


                $cas_found = false;

                //cas de deux chemins differents pour arrivé à la même table fille
                $temp = $array;
                foreach ($temp as $key1 => $tab2) {
                    foreach ($tab2 as $key2 => $val) {
                        //echo $val."\n";

                        foreach ($level as $tab3) {
                            if (in_array($val, $tab3)) {
                                //echo "-- -- ".$array[$key1][$key2]." -- \n";

                                unset($array[$key1][$key2]);
                                $cas_found = true;
                            }
                        }
                    }
                }

                if (!$cas_found) {
                    echo "\n";
                    throw new \Exception("PMACTRL-333 Circular definition (table <-> table)");
                }
            }


            sort($level[$i]);
            $i++;
        }

        if ($order === "ASC") {
            krsort($level);
        } else {
            ksort($level);
        }
        return $level;
    }

    public function delete()
    {
        $db          = $this->di['db']->sql($this->link_to_purge);
        $db->sql_select_db($this->schema_to_purge);
        $list_tables = $this->getOrderBy("DESC");

        foreach ($list_tables as $levels) {
            foreach ($levels as $table) {

                $primary_keys = $this->getPrimaryKey($table);

                $join   = array();
                $fields = array();
                foreach ($primary_keys as $primary_key) {
                    $join[]   = " `a`.`".$primary_key."` = b.`".$primary_key."` ";
                    $fields[] = " b.`".$primary_key."` ";
                }

                $field = implode(" ", $join);
                $sql   = "DELETE a FROM ".$table." a
                    INNER JOIN `".$this->schema_delete."`.".$this->prefix.$table." as b ON  ".implode(" AND ", $join);

                $db->sql_query($sql);
                $this->log($sql);

                if (end($db->query)['rows'] == "-1") {
                    throw new \Exception('PMACLI-666 : Foreign key error, have to update lib of cleaner or order of table set in param');
                }

                $sql = "DELETE FROM `".$this->schema_delete."`.`".$this->prefix.$table."`";
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

    public function getAffectedTables()
    {
        if (count($this->table_impacted) === 0) {
            $list_tables = $this->getOrderBy();

            foreach ($list_tables as $tables) {
                $this->table_impacted = array_merge($this->table_impacted, $tables);
            }
        }

        return $this->table_impacted;
    }

    private function removeTableNotImpacted($fks)
    {


        do {
            $tmp  = $fks;
            $tmp2 = $fks;

            $nbfound = 0;
            foreach ($fks as $table => $data) {
                //we want keep main table
                if (trim($table) == $this->main_table) {
                    continue;
                }
                $found = false;

                foreach ($tmp2 as $data_to_cmp) {
                    if (in_array($table, $data_to_cmp)) {
                        $found = true;
                    }
                }
                if (!$found) {

                    if ($this->debug) {
                        echo Color::getColoredString("We removed this table (Not a child of : `".$this->schema_to_purge."`.`".$this->main_table."`) : ".$table,
                            'black', 'yellow', 'bold').PHP_EOL;
                    }

                    unset($tmp2[$table]);
                    $nbfound++;
                }
            }
            $fks = $tmp2;

            if ($this->debug) {
                echo str_repeat("-", 80)."\n";
            }
        } while ($nbfound != 0);

        //debug($tmp2);

        return $tmp2;
    }

    function getImpactedTable()
    {
        $real_fk    = $this->getForeignKeys();
        $virtual_fk = $this->getVirtualForeignKeys();
        $fks        = array_merge_recursive($real_fk, $virtual_fk);
        $keys       = array_keys($fks);

        $list = array($this->main_table);
        $last = $list;

        do {
            $tmp = [];
            foreach ($last as $table) {
                if (!empty($fks[$table])) {
                    $list = array_merge($list, $fks[$table]);
                    $tmp  = array_merge($tmp, $fks[$table]);

                    unset($fks[$table]);
                }
            }
            $last = $tmp;
        } while (count($tmp) != 0);

        return $list;
    }
}