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
    const FIELD_LOOP = "pmactrol_purge_loop";

    public $debug                  = false;
    public $color                  = true;
    public $prefix                 = "DELETE_";
    public $link_to_purge;
    public $schema_to_purge;
    public $schema_delete          = "CLEANER";
    public $table_to_purge         = array();
    public $main_field             = array(); // => needed
    public $main_table;
    public $init_where;
    private $table_in_error        = array();
    private $di                    = array();
    private $rows_to_delete        = array();
    public $foreign_keys           = array();
    private $table_impacted        = array();
    public $id_cleaner             = 0;
    public $backup_dir             = DATA."cleaner/";
    private $path_to_orderby_tmp;
    private $orderby               = array();
    public $id_backup_storage_area = 0;
    private $sql_hex_for_binary    = false;
    private $fk_circulaire         = array();

    function __construct($di)
    {
        $this->di['db'] = $di;

        $this->path_to_orderby_tmp = TMP."cleaner/orderby.ser";


        $this->testDirectory(pathinfo($this->path_to_orderby_tmp)['dirname']);


        if (file_exists($this->path_to_orderby_tmp)) {
            unlink($this->path_to_orderby_tmp);
        }
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

        if (Debug::$debug) {
            echo $sql."\n";
        }

        $db->sql_query($sql);

        $this->rows_to_delete = array();


        if (Debug::$debug) {
            echo "CREATE TEMP TABLE !\n";
        }
        $this->createAllTemporaryTable();

        if (Debug::$debug) {
            echo "INIT !\n";
        }

        $this->init();

//feed table in the right order to delete later
        $this->feedDeleteTableWithFk();


        //exit;
        echo "\n";


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

            if (Debug::$debug) {
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


        if (in_array($table, $this->fk_circulaire)) {
            $sql .= ", ".self::FIELD_LOOP." int(11) DEFAULT 0";
            $sql .= ", KEY `idx_".uniqid()."` (`".self::FIELD_LOOP."`)";
        }



        $sql .= ", PRIMARY KEY (".implode(",", $index)."));";

        $db->sql_query($sql);

//$this->log($sql);
        $sql = "DELETE FROM `".$this->schema_delete."`.`".$this->prefix.$table."`;";
        $db->sql_query($sql);


        /*
         * get these and compare
         * Com_alter_table
         * Com_create_table
         * Com_drop_table
         */

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
            $sql       .= "('".implode("','", $line)."'),";
        }

        if ($have_data) {
            $sql = rtrim($sql, ",");
            $db->sql_query($sql);
            $this->setAffectedRows($this->main_table);
            $this->log($sql);

            $this->exportToFile($this->main_table);
        } else {
            $this->rows_to_delete[$this->main_table] = 0;
            return $this->rows_to_delete;
        }
    }

    public function feedDeleteTableWithFk()
    {
        if (Debug::$debug) {
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


                $fk_circular = array();

                //get virtual FK and merge to real FK
                if (!empty($this->foreign_keys[$this->schema_to_purge][$table_name])) {
                    foreach ($this->foreign_keys[$this->schema_to_purge][$table_name] as $constraint_column => $line) {
                        $tab = explode("-", $line);

                        $tmp = [];

                        $tmp['REFERENCED_TABLE_SCHEMA'] = $tab[0];
                        $tmp['REFERENCED_TABLE_NAME']   = $tab[1];
                        $tmp['REFERENCED_COLUMN_NAME']  = $tab[2];
                        $tmp['COLUMN_NAME']             = $constraint_column;

                        //cas des deifinition circulaire à gérer en dernier (afin de remplir toute la table avant de boucler sur elle même)
                        if ($table_name !== $tmp['REFERENCED_TABLE_NAME']) {
                            $fks[] = $tmp;
                        } else {
                            $fk_circular[] = $tmp;
                        }
                    }
                }


                if (count($fk_circular) == 2) {
                    throw new \Exception("PMACTRL-549 : We do not support 2 circulars definitions in same time on same table");
                }


                $fks = array_merge($fks, $fk_circular);

                foreach ($fks as $fk) {

                    //don't take in consideration the table not impacted by cleaner
                    if (!in_array($fk['REFERENCED_TABLE_NAME'], $tables_impacted)) {
                        continue;
                    }

                    $pri          = $this->getPrimaryKey($table_name);
                    $primary_key  = "a.`".implode('`,a.`', $pri)."`";
                    $primary_keys = "`".implode('`,`', $pri)."`";

                    if ($fk['REFERENCED_TABLE_NAME'] == $table_name) {
                        $circular = true;

                        echo "################# CIRCULAR ###########################\n";
                    } else {
                        $circular = false;
                    }

                    $loop = 1;
                    do {




                        $sql = "SELECT ".$primary_key." FROM `".$this->schema_to_purge."`.`".$table_name."` a
                    INNER JOIN `".$this->schema_delete."`.`".$this->prefix.$fk['REFERENCED_TABLE_NAME']."` b ON b.`".$fk['REFERENCED_COLUMN_NAME']."` = a.`".$fk['COLUMN_NAME']."`";

                        if ($circular) {
                            $sql .= " WHERE b.`".self::FIELD_LOOP."` = ".($loop - 1);

                            $circular_field = ",`".self::FIELD_LOOP."`";
                            $circular_data  = ",".$loop;
                        } else {
                            $circular_field = "";
                            $circular_data  = "";
                        }


                        $data = $db->sql_fetch_yield($sql);

                        $this->log($sql);


                        $have_data = false;
                        $sql       = "INSERT IGNORE INTO `".$this->schema_delete."`.`".$this->prefix.$table_name."` (".$primary_keys."".$circular_field.") VALUES ";

                        $count = 0;
                        foreach ($data as $line) {

                            $have_data = true;
                            $sql       .= "('".implode("','", $line)."'".$circular_data."),";
                            $count++;
                        }

                        if ($circular) {
                            echo Color::getColoredString("COUNT(1) = ".$count, "yellow")."\n";
                            echo Color::getColoredString("LOOP = ".$loop, "grey", "green")."\n";
                        }



                        // archivation en fichier palt
                        if ($have_data) {
                            $sql = rtrim($sql, ",").";";
                            $db->sql_query($sql);

                            $this->setAffectedRows($table_name);
                            $this->log($sql);
                            //export to file
                            $this->exportToFile($table_name);
                            // fin export
                        }

                        $loop++;
                    } while ($circular && $count !== 0);
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
        if (Debug::$debug) {

            $db = $this->di['db']->sql($this->link_to_purge);
            $db->sql_select_db($this->schema_to_purge);

            if ($this->color) {
                echo \SqlFormatter::highlight($sql);
            } else {
                echo \SqlFormatter::format($sql, false).PHP_EOL;
            }

            if ($this->color) {
                echo Color::getColoredString("--Row affected : ".end($db->query)['rows']." - Time : ".end($db->query)['time'], 'black', 'green', 'bold')."\n";
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
        if (Debug::$debug) {
            $this->log($sql);
        }

        $order_to_feed = array();

        while ($ob = $db->sql_fetch_object($res)) {
            $order_to_feed[$ob->REFERENCED_TABLE_NAME][] = $ob->TABLE_NAME;
        }

        if (Debug::$debug) {
            debug($order_to_feed);
        }

        return $order_to_feed;
    }

    private function getVirtualForeignKeys()
    {
        $default = $this->di['db']->sql(DB_DEFAULT);

//get and set virtual Foreign keys.
        $params = $this->di['db']->sql(DB_DEFAULT)->getParams();
        $sql    = "SELECT * FROM `".$params['database']."`.`cleaner_foreign_key` WHERE `id_cleaner_main` = ".$this->id_cleaner.";";


        $foreign_keys = $default->sql_fetch_yield($sql);

        $fk = array();
        foreach ($foreign_keys as $line) {

            if (empty($line['constraint_schema']) || empty($line['constraint_table']) || empty($line['constraint_column']) || empty($line['referenced_schema']) || empty($line['referenced_table']) || empty($line['referenced_column'])) {
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

        if (!file_exists($this->path_to_orderby_tmp)) {


            $real_fk = $this->getForeignKeys();


            if (Debug::$debug) {
                echo "REAL FOREIGN KEY :";
                print_r($real_fk);
            }

            $virtual_fk = $this->getVirtualForeignKeys();

//debug($virtual_fk);

            if (Debug::$debug) {
                echo "VIRTUAL FOREIGN KEY :";
                print_r($real_fk);
            }

//debug($real_fk);

            $fks = array_merge_recursive($real_fk, $virtual_fk);
            $tmp = $fks;


//debug($fks);


            if (Debug::$debug) {
                echo "ALL FOREIGN KEY :";
//print_r($real_fk);
            }

            foreach ($tmp as $key => $tab) {
                $fks[$key] = array_unique($fks[$key]);
            }


            if (Debug::$debug) {
                echo "Remove key in dubble :";
//print_r($real_fk);
            }

//remove all tables with no father from $this->main_table
            $fks = $this->removeTableNotImpacted($fks);

            if (Debug::$debug) {
                echo "removed table not impacted :";
//debug($real_fk);
            }

            $level   = array();
            $level[] = $this->table_to_purge;

            $array = $fks;



            // test des tables qui boucle sur elle même
            $tmp2 = $array;
            foreach ($tmp2 as $table_name => $childs) {

                foreach ($childs as $key => $child) {
                    if ($table_name === $child) {


                        $this->fk_circulaire[] = $table_name;

                        unset($array[$table_name][$key]);
                        $cas_found = true;
                    }
                }
            }

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

                    //cas de deux chemins differents pour arriver à la même table enfant
                    $temp = $array;
                    foreach ($temp as $key1 => $tab2) {
                        foreach ($tab2 as $key2 => $val) {


                            foreach ($level as $tab3) {

                                if (in_array($val, $tab3)) {


                                    unset($array[$key1][$key2]);
                                    $cas_found = true;
                                }
                            }
                        }
                    }

                    if (!$cas_found) {
                        echo "\n";

                        debug($tab2);
                        debug($level);
                        debug($array);
                        throw new \Exception("PMACTRL-333 Circular definition (table <-> table)");
                    }
                }

                sort($level[$i]);
                $i++;
            }


//dans le cas où il a pas au moins table fille on ajoute la table principale
            if (count($level[0]) == 0) {
                $level[0][0] = $this->main_table;
            }

            if ($order === "ASC") {
                krsort($level);
            } else {
                ksort($level);
            }

            $this->orderby = $level;

            file_put_contents($this->path_to_orderby_tmp, serialize($this));
        } else {

            //on load le fichier précédement enregistré
            if (is_file($this->path_to_orderby_tmp)) {
                $s             = implode('', file($this->path_to_orderby_tmp));
                $tmp           = unserialize($s);
                $this->orderby = $tmp->orderby;
            }
        }


        if ($order === "ASC") {
            krsort($this->orderby);
        } else {
            ksort($this->orderby);
        }

        return $this->orderby;
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

                $sql = "DELETE a FROM ".$table." a
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

                    if (Debug::$debug) {
                        echo "\n".Color::getColoredString("We removed this table (Not a child of : `".$this->schema_to_purge."`.`".$this->main_table."`) : ".$table, 'black', 'yellow', 'bold').PHP_EOL;
                    }

                    unset($tmp2[$table]);
                    $nbfound++;
                }
            }
            $fks = $tmp2;

            if (Debug::$debug) {
                echo str_repeat("-", 80)."\n";
            }
        } while ($nbfound != 0);

//debug($tmp2);

        return $tmp2;
    }

    private function exportToFile($table)
    {

        if (!empty($this->id_backup_storage_area)) {
            $db = $this->di['db']->sql($this->link_to_purge);

            $primary_keys = $this->getPrimaryKey($table);

            $max      = 0;
            $circular = false;
            if (in_array($table, $this->fk_circulaire)) {
                $circular = true;
                $loop     = 0;

                // moyen d'enregistrer le nombre en cash au lieu de refaire une requette
                $sql = "SELECT MAX(`".self::FIELD_LOOP."`) as max FROM `".$this->schema_delete."`.".$this->prefix.$table."";
                $res = $db->sql_query($sql);


                while ($ob = $db->sql_fetch_object($res)) {
                    $max = $ob->max;
                }
            }

            $loop = $max;

            do {

                $join = array();
                foreach ($primary_keys as $primary_key) {
                    $join[] = " `a`.`".$primary_key."` = b.`".$primary_key."` ";
                }

                $sql = "SELECT a.* FROM ".$table." a
                    INNER JOIN `".$this->schema_delete."`.".$this->prefix.$table." as b ON  ".implode(" AND ", $join)."";


                if ($circular) {
                    $sql .= " WHERE `".self::FIELD_LOOP."`=".$loop.";";
                }

                $res = $db->sql_query($sql);


                $res2 = $db->sql_query("DESCRIBE `".$table."`;");
                while ($ob   = $db->sql_fetch_object($res2)) {
                    $fields[] = "`".$ob->Field."`";
                }

                $fields_list = implode(",", $fields);

                $query = "INSERT IGNORE INTO ".$table." (".$fields_list.") VALUES ".$this->get_rows($res).";\n";

                $this->testDirectory($this->backup_dir);

                $file = $this->backup_dir."/".date('Y-m-d')."_log.sql";

                $fp = fopen($file, "a");

                if ($fp) {
                    fwrite($fp, $query);
                    fclose($fp);
                }

                $loop--;
            } while ($circular && $loop >= 0);
        }
    }

    private function testDirectory($path)
    {

        if (!is_dir($path)) {
            mkdir($path, 0700, true);
        }


        if (!is_writable($path)) {
            throw new \Exception("GLI-985 : Impossible to write in directory : (".$path.")", 80);
        }
    }
    /*
     * return an array with data to serialize
     * @since Glial 4.2.11
     * @version 4.2.11
     * @return array contain the data to be serialized
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @description return an array to be serialized in a flat file
     * @access public
     */

    public function __sleep()
    {
        return array('orderby');
    }

    private function get_rows($result)
    {

        $db          = $this->di['db']->sql($this->link_to_purge);
        $current_row = 0;

        $fields_cnt = $db->sql_num_fields($result);

        // Get field information
        $fields_meta = $db->getFieldsMeta($result);
        $field_flags = array();
        for ($j = 0; $j < $fields_cnt; $j++) {
            $field_flags[$j] = $db->fieldFlags($result, $j);
        }

        while ($row = $db->sql_fetch_array($result, MYSQLI_NUM)) {


            $values = array();
            for ($j = 0; $j < $fields_cnt; $j++) {
                // NULL
                if (!isset($row[$j]) || is_null($row[$j])) {
                    $values[] = 'NULL';
                } elseif ($fields_meta[$j]->numeric && $fields_meta[$j]->type != 'timestamp' && !$fields_meta[$j]->blob) {
                    // a number
                    // timestamp is numeric on some MySQL 4.1, BLOBs are
                    // sometimes numeric
                    $values[] = $row[$j];
                } elseif (stristr($field_flags[$j], 'BINARY') !== false && $this->sql_hex_for_binary) {
                    // a true BLOB
                    // - mysqldump only generates hex data when the --hex-blob
                    //   option is used, for fields having the binary attribute
                    //   no hex is generated
                    // - a TEXT field returns type blob but a real blob
                    //   returns also the 'binary' flag
                    // empty blobs need to be different, but '0' is also empty
                    // :-(
                    if (empty($row[$j]) && $row[$j] != '0') {
                        $values[] = '\'\'';
                    } else {
                        $values[] = '0x'.bin2hex($row[$j]);
                    }
                } elseif ($fields_meta[$j]->type == 'bit') {
                    // detection of 'bit' works only on mysqli extension
                    $values[] = "b'".$db->sql_real_escape_string(
                            $this->printableBitValue(
                                $row[$j], $fields_meta[$j]->length
                            )
                        )
                        ."'";
                } else {
                    // something else -> treat as a string
                    $values[] = '\''.$db->sql_real_escape_string($row[$j]).'\'';
                } // end if
            } // end for

            $insert_elem[] = '('.implode(',', $values).')';
        }

        $insert_line = implode(',', $insert_elem);



        return $insert_line;
    }

    public static function printableBitValue($value, $length)
    {
        // if running on a 64-bit server or the length is safe for decbin()
        if (PHP_INT_SIZE == 8 || $length < 33) {
            $printable = decbin($value);
        } else {
            // FIXME: does not work for the leftmost bit of a 64-bit value
            $i         = 0;
            $printable = '';
            while ($value >= pow(2, $i)) {
                ++$i;
            }
            if ($i != 0) {
                --$i;
            }

            while ($i >= 0) {
                if ($value - pow(2, $i) < 0) {
                    $printable = '0'.$printable;
                } else {
                    $printable = '1'.$printable;
                    $value     = $value - pow(2, $i);
                }
                --$i;
            }
            $printable = strrev($printable);
        }
        $printable = str_pad($printable, $length, '0', STR_PAD_LEFT);
        return $printable;
    }

    function getImpactedTable()
    {
        $list = $this->getOrderby();

        $tables = array();
        foreach ($list as $elem) {
            $tables = array_merge($elem, $tables);
        }
        return $tables;
    }
}