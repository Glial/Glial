<?php

namespace Glial\Sgbd\Sql;

use \Glial\Synapse\Singleton;
use \Glial\Synapse\Validation;
use \Glial\Utility\Inflector;
use \Glial\Cli\Color;


abstract class Sql
{

    public $link;
    public $number_of_query = 0;
    public $query = array();
    public $error = array();
    public $data = array();
    public $rows_affected;
    public $last_id;
    public $called_from;
    public $validate = array();
    public $res;
    public $_history_type = 4; // default 4 made by system
    public $_history_active = false; // default 4 made by system
    public $_history_user = null; // default 4 made by system
    public $_type_query = '';
    public $_table_to_history = '';
    private $_table = '';
    private $_name = '';
    private $_keys = array();
    public $_param = array();
    public $is_connected = false;

    //to be surcharged
    public function get_table_to_history()
    {
        if ($this->$_history_active) {
            $this->_table_to_history = \history::get_table_with_history();
        }
    }

    abstract protected function __construct($name, $elem);

    abstract protected function sql_connect($var1, $var2, $var3, $db, $port);

    abstract protected function sql_select_db($var1);

    abstract protected function sql_close();

    abstract protected function sql_real_escape_string($var1);

    abstract protected function sql_affected_rows();

    abstract protected function sql_num_rows($var1);

    abstract protected function sql_num_fields($res);

    abstract protected function sql_field_name($result, $i);

    abstract protected function sql_free_result($result);

    abstract protected function _insert_id();

    abstract protected function _error();

    abstract protected function getListTable();

    abstract protected function getIndexUnique($table_name);

    public function sql_fetch_field($res, $field_offset = 0)
    {
        
    }

    //function mutualised

    final public function sql_query($sql, $table = "", $type = "")
    {



        if (IS_CLI) { //to save memory with crawler & bot
            $this->serializeQuery();
        }

        if (!is_string($sql)) {
            throw new \Exception('GLI-056 : the var $sql must be a string in sql_query !');
        }

        $this->res = "";
        $this->stid = "";

        $called_from = debug_backtrace();
        $startmtime = microtime(true);

        if (!$res = $this->_query($sql)) {

<<<<<<< HEAD

=======
            $indice = 0;
            if (strstr($called_from[0]['file'],  "/Sgbd/Sql/Sql.php"))
            {
                $indice = 1;
            }
            
>>>>>>> be8a7164c72562fd2b1af0239df5cffdd36ad055
            //error
            if (IS_CLI) {
                echo "SQL : ".Color::getColoredString($sql,"yellow")."\n" . Color::getColoredString($this->_error(),"grey","red") . "" .
                "\nFILE : " . $called_from[$indice]['file'] . " LINE : " . $called_from[$indice]['line']."\n";
            } else {
                echo "SQL : $sql<br /><b>" . $this->_error() . "</b>" .
                "<br />FILE : " . $called_from[$indice]['file'] . ":" . $called_from[$indice]['line']."<br />";
            }
        }

        $this->res = $res;

        $totaltime = round(microtime(true) - $startmtime, 5);

        $this->query[$this->number_of_query]['query'] = $sql;
        $this->query[$this->number_of_query]['time'] = $totaltime;
        $this->query[$this->number_of_query]['file'] = $called_from[0]['file'];
        $this->query[$this->number_of_query]['line'] = $called_from[0]['line'];


        $this->rows_affected = $this->sql_affected_rows();

        $this->query[$this->number_of_query]['rows'] = $this->rows_affected;
        $this->query[$this->number_of_query]['last_id'] = $this->_insert_id();


        $this->number_of_query++;

        return $res;
    }

    public function sql_error()
    {
        return $this->error;
    }

    public function sql_save($data = null, $validate = true, $fieldList = array())
    {

        unset($this->error);
        $this->error = array();


        if (count($this->_keys) === 0) {
            $this->unserializeKeys();
        }


        $table = array_keys($data);
        $table = $table[0];
        $keys = array_keys($data[$table]);


        $this->getInfosTable($table);

        $validation = new Validation($this);

        include_once APP_DIR . DS . "model" . DS . "Identifier" . ucwords(strtolower($this->_name)) . DS . $table . ".php";


        $model_name = "Identifier" . Inflector::camelize($this->_name);
        $table2 = str_replace("-", "", $table);


        //$my_table = singleton::getInstance('glial\synapse\model\table\\'.$table2);
        $my_table = Singleton::getInstance('application\\model\\' . $model_name . '\\' . $table2);
        $validate = $my_table->validate;

        //debug($validate);

        foreach ($keys as $field) {
            if (!empty($validate[$field])) {
                foreach ($validate[$field] as $rule => $param) {
                    if (!empty($rule)) {
                        $elem['table'] = $table;
                        $elem['field'] = $field;
                        $elem['value'] = $data[$table][$field];

                        if (in_array("id", $keys, true)) {
                            $elem['id'] = "AND id != " . $data[$table]['id'];
                        }

                        if (!empty($param[0])) {
                            $msg_error = $param[0];
                        } else {
                            $msg_error = NULL;
                        }
                        unset($param[0]);

                        if (!empty($param)) {
                            if (is_array($param)) {
                                $nb_var = count($param);

                                switch ($nb_var) {
                                    case 0: $return = $validation->$rule($elem);
                                        break;
                                    case 1: $return = $validation->$rule($elem, $param[1]);
                                        break;
                                    case 2: $return = $validation->$rule($elem, $param[1], $param[2]);
                                        break;
                                    case 3: $return = $validation->$rule($elem, $param[1], $param[2], $param[3]);
                                        break;
                                }
                            } else {
                                $return = $validation->$rule($elem, $param);
                            }
                        } else {
                            $return = $validation->$rule($elem);
                        }

                        if ($return === false) {
                            //$this->error[$table][$field][] = __($param['message']);
                            $this->error[$table][$field] = $msg_error;
                        }
                    }
                }
            }
        }

        unset($validation);

        $nb = count($keys);

        for ($i = 0; $i < $nb; $i++) {

            if (!in_array($keys[$i], $this->_table[$table]['field'])) {
                unset($data[$table][$keys[$i]]);
                unset($keys[$i]);
            } else {
                $data[$table][$keys[$i]] = $this->sql_real_escape_string($data[$table][$keys[$i]]);
            }
        }

<<<<<<< HEAD
        
        if (count($this->error) == 0) {
=======



        if (count($this->error) == 0) {


>>>>>>> be8a7164c72562fd2b1af0239df5cffdd36ad055
            if ($this->_history_active) { //traitement specifique
                if (strstr($this->_table_to_history, $table)) {

                    if (in_array("id", $keys, true)) {
                        $sql = "SELECT * FROM " . static::ESC . "" . $table . "" . static::ESC . " WHERE id ='" . $data[$table]['id'] . "'";
                        $res = $this->sql_query($sql);

                        if ($this->sql_num_rows($res) === 1) {
                            $before_update = $this->sql_to_array($res);

                            //\history::insert($table, $data[$table]['id'], $param, $this->_history_type);
                        }
                    }
                }
            }

            if (in_array("id", $keys, true)) {

                $id = $data[$table]['id'];
                unset($data[$table]['id']);

                $str = array();
                foreach ($keys as $key) {
                    if ($key === 'id')
                        continue;

                    $str[] = "" . static::ESC . "" . $key . "" . static::ESC . " = '" . $data[$table][$key] . "'";
                }

                $sql = "UPDATE " . static::ESC . "" . $table . "" . static::ESC . " SET " . implode(",", $str) . " WHERE id= " . $this->sql_real_escape_string($id) . "";
                $this->sql_query($sql, $table, "UPDATE");

                if ($this->query[$this->number_of_query - 1]['rows'] === 0) {
                    $this->query[$this->number_of_query - 1]['last_id'] = $id;
                }

                if ($this->query[$this->number_of_query - 1]['rows'] == 0) {
                    //$sql = "INSERT INTO ".static::ESC."".$table."".static::ESC." SET ".implode(",", $str)."";
                    //$sql = "INSERT INTO ".static::ESC."".$table."".static::ESC." (".implode(",", $keys).") VALUES (".$this->sql_real_escape_string($id).",'".implode("','", $data[$table])."') --";
                    $sql = "INSERT INTO " . static::ESC . "" . $table . "" . static::ESC . " SET id=" . $this->sql_real_escape_string($id) . " , " . implode(",", $str) . ""; //not supported by sybase A amÃ©liorer
                    $this->sql_query($sql, $table, "INSERT");
                }
            } else {
                $sql = "INSERT INTO " . static::ESC . "" . $table . "" . static::ESC . " (" . static::ESC . "" . implode("" . static::ESC . "," . static::ESC . "", $keys) . "" . static::ESC . ") VALUES ('" . implode("','", $data[$table]) . "') --";
                $this->sql_query($sql, $table, "INSERT");
            }


            if (static::ESC === '`') {
                //case where ignore insert 0 line and we need the id inserted with these infos, focus on index unique
                $this->last_id = $this->query[$this->number_of_query - 1]['last_id'];
                if ($this->last_id == 0) {


<<<<<<< HEAD
=======

>>>>>>> be8a7164c72562fd2b1af0239df5cffdd36ad055
                    $sql = "SELECT id FROM " . static::ESC . "" . $table . "" . static::ESC . " WHERE 1=1 ";

                    if (!empty($this->_keys[$table])) {
                        foreach ($data[$table] as $key => $value) {

                            //select only unique key
                            if (in_array($key, $this->_keys[$table])) {
                                $sql .= " AND " . static::ESC . "" . $key . "" . static::ESC . " = '" . $value . "' ";
                            }
                        }
                    }


<<<<<<< HEAD
                    debug($sql);
=======
                    //debug($sql);
>>>>>>> be8a7164c72562fd2b1af0239df5cffdd36ad055

                    $res = $this->sql_query($sql, $table, "SELECT");
                    $tab = $this->sql_to_array($res);

                    if (!empty($tab[0]['id'])) {
                        $this->last_id = $tab[0]['id'];
                    } else {
                        $this->error[] = $sql;
                        $this->error[] = "impossible to select the right row plz have a look on date('c')";

                        throw new \Exception('GLI-031 : Impossible to fine last id inserted in case of insert ignore');
                    }
                }
            }

            if ($this->_history_active) { //traitement specifique
                if (strstr($this->_table_to_history, $table)) {
                    if (!empty($before_update)) {
                        $param = \history::compare($before_update[0], $data[$table]);
                        $id_table = $id;
                        $type_query = 'UPDATE';
                    } else {
                        $param = \history::compare(array(), $data[$table]);
                        $id_table = $this->last_id;
                        $type_query = 'INSERT';
                    }

                    \history::insert($table, $id_table, $param, $this->_history_type, $this->_history_user, $type_query);
                    $this->_history_type = HISTORY_TYPE;
                    $this->_history_user = null;

                    $this->last_id = $id_table;
                }
            }

            //return $this->query[$this->number_of_query-1]['last_id'];

            if (static::ESC === '"') {
                return true;
            } else {
                return $this->sql_insert_id();
            }
        } else {
            return false;
        }
    }

    public function get_count_query()
    {
        return $this->number_of_query;
    }

    public function get_validate()
    {
        return $this->validate;
    }

    public function set_history_type($type)
    {
        $this->_history_type = $type;
    }

    public function set_history_user($id_user_main)
    {
        $this->_history_user = $id_user_main;
    }

    public function sql_delete($data = null)
    {
        unset($this->error);

        $this->error = array();

        if (count($this->_keys) === 0) {
            $this->unserializeKeys();
        }
        //TODO implement verification of child table before delete

        foreach ($data as $table => $field) {
            if (file_exists(TMP . "/database/" . $table . ".table.txt")) {
                if (!empty($field['id'])) {

                    if (static::HISTORY_ACTIVE) { //traitement specifique
                        if (strstr($this->_table_to_history, $table)) {

                            $sql = "SELECT * FROM " . static::ESC . "" . $table . "" . static::ESC . " WHERE id ='" . $data[$table]['id'] . "'";
                            $res = $this->sql_query($sql);

                            if ($this->sql_num_rows($res) === 1) {
                                $before_update = $this->sql_to_array($res);
                            } else {
                                return false;
                            }

                            $param = \history::compare($before_update[0], array());
                            $id_table = $data[$table]['id'];

                            \history::insert($table, $id_table, $param, $this->_history_type, $this->_history_user, 'DELETE');
                            $this->_history_type = HISTORY_TYPE;
                            $this->_history_user = null;
                        }
                    }

                    $sql = "UPDATE " . $table . " SET id_history_etat = 3 WHERE id =" . $field['id'];
                    $this->sql_query($sql, $table, "UPDATE");
                }
            }
        }
    }

    private function serializeQuery()
    {
        if (count($this->query) > 500) {
            array_splice($this->query, 0, -10);
        }
    }

    private function unserializeKeys()
    {
        //set
        $filename = TMP . "keys/" . $this->_name . "_index_unique.txt";

        if (file_exists($filename)) {
            $this->_keys = json_decode(file_get_contents($filename), true);
        } else {
            $listTable = $this->getListTable();

            $list_index = array();
            foreach ($listTable['table'] as $table_name) {
                $list_index[$table_name] = $this->getIndexUnique($table_name);
            }

            $this->_keys = $list_index;
            $json = json_encode($list_index);
            if (!file_put_contents(TMP . "keys/" . $this->_name . "_index_unique.txt", $json)) {
                trigger_error("make sure is writable : " . $filename, E_USER_NOTICE);
            }
        }
    }

    public function getInfosTable($table)
    {
        if (empty($this->_table[$table])) {
            try {
                $this->_table[$table] = unserialize(file_get_contents(TMP . "database" . DS . $table . ".table.txt"));
                return $this->_table[$table];
            } catch (\Exception $e) {
                throw new \Exception("GLI-010 : This table cash doesn't exist, please run 'php index.php administration admin_table'", 0, $e);
            }
        }
    }

    /*
     * @since Glial 2.1.2
     * @param $name String Name of the db link defined in db.config.ini
     * @parameters dbname The database name.
     */

    public function setName($name, $elem)
    {
        $this->_name = $name;
        $this->_param = $elem;
    }

    public function getParams()
    {
        return $this->_param;
    }

    /*
     *
     * @since glial 3.1.1
     * @param void
     * @return the driver used for this connection (oracle|mysql|sybase|pgsql)
     */

    public function getDriver()
    {
        return $this->_param['driver'];
    }

    public function sql_check($data = null)
    {
        
        unset($this->error);
        $this->error = array();


        if (count($this->_keys) === 0) {
            $this->unserializeKeys();
        }


        $table = array_keys($data);
        $table = $table[0];
        $keys = array_keys($data[$table]);


        $this->getInfosTable($table);

        $validation = new Validation($this);

        include_once APP_DIR . DS . "model" . DS . "Identifier" . ucwords(strtolower($this->_name)) . DS . $table . ".php";


        $model_name = "Identifier" . Inflector::camelize($this->_name);
        $table2 = str_replace("-", "", $table);


        //$my_table = singleton::getInstance('glial\synapse\model\table\\'.$table2);
        $my_table = Singleton::getInstance('application\\model\\' . $model_name . '\\' . $table2);
        $validate = $my_table->validate;

        //debug($validate);

        foreach ($keys as $field) {
            if (!empty($validate[$field])) {
                foreach ($validate[$field] as $rule => $param) {
                    if (!empty($rule)) {
                        $elem['table'] = $table;
                        $elem['field'] = $field;
                        $elem['value'] = $data[$table][$field];

                        if (in_array("id", $keys, true)) {
                            $elem['id'] = "AND id != " . $data[$table]['id'];
                        }

                        if (!empty($param[0])) {
                            $msg_error = $param[0];
                        } else {
                            $msg_error = NULL;
                        }
                        unset($param[0]);

                        if (!empty($param)) {
                            if (is_array($param)) {
                                $nb_var = count($param);

                                switch ($nb_var) {
                                    case 0: $return = $validation->$rule($elem);
                                        break;
                                    case 1: $return = $validation->$rule($elem, $param[1]);
                                        break;
                                    case 2: $return = $validation->$rule($elem, $param[1], $param[2]);
                                        break;
                                    case 3: $return = $validation->$rule($elem, $param[1], $param[2], $param[3]);
                                        break;
                                }
                            } else {
                                $return = $validation->$rule($elem, $param);
                            }
                        } else {
                            $return = $validation->$rule($elem);
                        }

                        if ($return === false) {
                            //$this->error[$table][$field][] = __($param['message']);
                            $this->error[$table][$field] = $msg_error;
                        }
                    }
                }
            }
        }

        
        if (empty($this->error))
        {
            return true;
        }
        else
        {
            return $this->error;
        }
        
        
    }

}
