<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Neuron\Controller;

use \Glial\Utility\Inflector;
use \Glial\Synapse\Basic;


trait Administration
{

    function admin_table()
    {

        if (IS_CLI) {

            $this->view = false;
            $this->layout_name = false;
        }

        $module = array();
        $module['picture'] = "administration/tables.png";
        $module['name'] = __("Tables");
        $module['description'] = __("Make the dictionary of field");

        if (Basic::from() !== "administration.controller.php") {

            if (true) { //ENVIRONEMENT
                $dir = TMP . "database/";

                if (is_dir($dir)) {
                    $dh = opendir($dir);
                    if ($dh) {
                        while (($file = readdir($dh)) !== false) {

                            if (substr($file, 0, 1) === ".") {
                                continue;
                            }

                            unlink($dir . $file);
                        }
                    }
                }

                $tables = $this->di['db']->sql('default')->getListTable();

                foreach ($tables['table'] as $table) {
                    echo $table . "\n";

                    $fp = fopen(TMP . "/database/" . strtolower($table) . ".table.txt", "w");

                    $sql = $this->di['db']->sql('default')->getDescription($table);

                    $res2 = $this->di['db']->sql('default')->sql_query($sql);
                    while ($ob = $this->di['db']->sql('default')->sql_fetch_object($res2)) {
                        $data['field'][] = $ob->FIELD;
                    }
                    $data = serialize($data);
                    fwrite($fp, $data);
                    fclose($fp);
                    unset($data);
                }
            }
        }

        return $module;
    }

    function admin_index_unique()
    {
        $this->layout_name = false;
        $this->view = false;

        foreach ($this->di['db']->connectAll() as $key => $db) {
            $listTable = $db->getListTable();

            $list_index = array();
            foreach ($listTable['table'] as $table_name) {
                $list_index[$table_name] = $db->getIndexUnique($table_name);
            }

            $json = json_encode($list_index);

            if (is_writable(TMP . "keys/")) {
                file_put_contents(TMP . "keys/" . $key . "_index_unique.txt", $json);
            } else {
                throw new \Exception("GLI-016 : This directory should be writable : " . TMP . "keys/", 16);
            }
        }
        
        //exit(95);
    }

    function all()
    {
        $this->admin_index_unique();
        $this->admin_table();
        $this->generate_model();
    }

    function install()
    {
        $this->all();
    }

    function generate_model()
    {

        //php index.php administration generate_model

        $this->layout_name = false;
        $this->view = false;


        foreach ($this->di['db']->getAll() as $key) {

            $dbLink = $this->di['db']->sql($key);

            $tab_object = $dbLink->getListTable();

            foreach ($tab_object['table'] as $table_name) {

                $table = $table_name;

                $model_name = "Identifier" . Inflector::camelize(str_replace('-', '_', $key));
                $dir = APP_DIR . "/model/Identifier" . ucfirst(strtolower($key));

                if (!is_dir($dir)) {
                    mkdir($dir);
                }

                $file = $dir . "/" . strtolower($table) . ".php";

                if (!file_exists($file)) {
                    $fp = fopen($file, "w");

                    echo "model : " . $file . "\n";

                    $text = "<?php\n\nnamespace Application\Model\\" . $model_name . ";\n";
                    $text .= "use \Glial\Synapse\Model;\n";
                    $text .= "class " . $table . " extends Model\n{\nvar \$schema = \"";

                    $create_table = $dbLink->getCreateTable($table);
                    $des_table = $dbLink->getDescription($table);

                  
                    $i = 0;

                    $data = array();
                    $field = array();

                    foreach ($des_table as $tab) {
                        $field[] = "\"" . $tab[0] . "\"";
                        $data[$table][$i]['field'] = $tab[0];
                        $data[$table][$i]['type'] = $tab[1];
                        $data[$table][$i]['length'] = $tab[2];
                        $i++;
                    }


                    $text .= $create_table;
                    $text .= "\";\n\nvar \$field = array(" . implode(",", $field) . ");\n\nvar \$validate = array(\n";

                    foreach ($data[$table] as $field) {
                        if ($field['field'] == "id") {
                            continue;
                        }
                        if (mb_substr($field['field'], 0, 2) === "id") {
                            $text .= "\t'" . $field['field'] . "' => array(\n\t\t'reference_to' => array('The constraint to " . mb_substr($field['field'], 3) . ".id isn\'t respected.','" . mb_substr($field['field'], 3) . "', 'id')\n\t),\n";
                        } elseif (mb_substr($field['field'], 0, 2) === "ip") {
                            $text .= "\t'" . $field['field'] . "' => array(\n\t\t'ip' => array('your IP is not valid')\n\t),\n";
                        } elseif ($field['field'] === "email") {
                            $text .= "\t'" . $field['field'] . "' => array(\n\t\t'email' => array('your email is not valid')\n\t),\n";
                        } else {

                            if (mb_strstr($field['type'], "int")) {
                                $text .= "\t'" . $field['field'] . "' => array(\n\t\t'numeric' => array('This must be an int.')\n\t),\n";
                            } elseif (mb_stristr($field['type'], "datetime")) {
                                $text .= "\t'" . $field['field'] . "' => array(\n\t\t'dateTime' => array('This must be a datetime.')\n\t),\n";
                            } elseif (mb_stristr($field['type'], "time")) {
                                $text .= "\t'" . $field['field'] . "' => array(\n\t\t'time' => array('This must be a time.')\n\t),\n";
                            } elseif (mb_stristr($field['type'], "date")) {
                                $text .= "\t'" . $field['field'] . "' => array(\n\t\t'date' => array('This must be a date.')\n\t),\n";
                            } elseif (mb_stristr($field['type'], "float")) {
                                $text .= "\t'" . $field['field'] . "' => array(\n\t\t'decimal' => array('This must be a float.')\n\t),\n";
                            } elseif (mb_stristr($field['type'], "VARCHAR2")) {
                                $text .= "\t'" . $field['field'] . "' => array(\n\t\t'maxLength' => array('You execed the max length (" . $field['length'] . " chars)', " . $field['length'] . ")\n\t),\n";
                            } elseif (mb_stristr($field['type'], "NUMBER")) {
                                $text .= "\t'" . $field['field'] . "' => array(\n\t\t'numeric' => array('This must be an int.')\n\t),\n";
                            } else {
                                //$text .= "\t'" . $field['field'] . "' => array(\n\t\t'not_empty' => array('This field is requiered.')\n\t),\n";
                            }
                        }
                    }

                    $text .= ");\n\nfunction get_validate()\n{\nreturn \$this->validate;\n}\n}\n";

                    fwrite($fp, $text);
                    fclose($fp);

                    unset($data);
                }
            }
        }
    }

    public function test()
    {

        $this->view = false;
        echo "trait";
    }

}
