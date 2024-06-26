<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Neuron\Controller;

use \Glial\Utility\Inflector;
use \Glial\Synapse\Basic;
use \Glial\Sgbd\Sgbd;

trait Administration
{

    function admin_table()
    {
        if (IS_CLI) {

            $this->view        = false;
            $this->layout_name = false;
        }

        $module                = array();
        $module['picture']     = "administration/tables.png";
        $module['name']        = __("Tables");
        $module['description'] = __("Make the dictionary of field");

        if (Basic::from() !== "administration.controller.php") {

            if (true) { //ENVIRONEMENT
                $dir = TMP."database/";

                if (is_dir($dir)) {
                    $dh = opendir($dir);
                    if ($dh) {
                        while (($file = readdir($dh)) !== false) {

                            if (substr($file, 0, 1) === ".") {
                                continue;
                            }

                            unlink($dir.$file);
                        }
                    }
                }

                $tables = Sgbd::sql(DB_DEFAULT)->getListTable();

                //debug($tables);

                foreach ($tables['table'] as $table) {
                    //echo $table . "\n";
                    $fp          = fopen(TMP."/database/".strtolower($table).".table.txt", "w");
                    $description = Sgbd::sql(DB_DEFAULT)->getDescription($table);
                    $data        = array();


                    foreach ($description as $line) {
                        $data['field'][] = $line[0];
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
        $this->view        = false;

        //foreach ($this->di['db']->connectAll() as $key => $db) {

        $key = DB_DEFAULT;
        $db = Sgbd::sql(DB_DEFAULT);

        $listTable = $db->getListTable();

        $list_index = array();
        foreach ($listTable['table'] as $table_name) {
            $list_index[$table_name] = $db->getIndexUnique($table_name);
        }

        $json = json_encode($list_index);

        if (is_writable(TMP."keys/")) {
            file_put_contents(TMP."keys/".$key."_index_unique.txt", $json);
        } else {
            throw new \Exception("GLI-016 : This directory should be writable : ".TMP."keys/", 16);
        }
        //}
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
        $this->view        = false;

        //foreach ($this->di['db']->getAll() as $key) {
        //$dbLink = Sgbd::sql($key);
        $dbLink = Sgbd::sql(DB_DEFAULT);

        $key = DB_DEFAULT;

        $tab_object = $dbLink->getListTable();

        foreach ($tab_object['table'] as $table_name) {

            if ($key == DB_DEFAULT) {
                $table = $table_name;

                $model_name = "Identifier".Inflector::camelize(str_replace('-', '_', $key));
                $dir        = APP_DIR."/model/Identifier".ucfirst(strtolower($key));

                if (!is_dir($dir)) {
                    mkdir($dir);
                }

                $file = $dir."/".strtolower($table).".php";

                if (!file_exists($file)) {
                    $fp = fopen($file, "w");

                    echo "model : ".$file."\n";

                    $text = "<?php\n\nnamespace Application\Model\\".$model_name.";\n";
                    $text .= "use \Glial\Synapse\Model;\n";
                    $text .= "class ".$table." extends Model\n{\nvar \$schema = \"";

                    $create_table = $dbLink->getCreateTable($table);
                    $des_table    = $dbLink->getDescription($table);

                    $i = 0;

                    $data  = array();
                    $field = array();

                    foreach ($des_table as $tab) {
                        $field[]                    = "\"".$tab[0]."\"";
                        $data[$table][$i]['field']  = $tab[0];
                        $data[$table][$i]['type']   = $tab[1];
                        $data[$table][$i]['length'] = $tab[2];
                        $i++;
                    }

                    $text .= $create_table;
                    $text .= "\";\n\nvar \$field = array(".implode(",", $field).");\n\nvar \$validate = array(\n";

                    foreach ($data[$table] as $field) {
                        if ($field['field'] == "id") {
                            continue;
                        }else if (mb_substr($field['field'], 0, 9) === "id_parent") {
                            $text .= "\t'".$field['field']."' => array(\n\t\t'reference_to' => array('The constraint to ".$table.".id isn\'t respected.','".$table."', 'id')\n\t),\n";
                        }
                        if (mb_substr($field['field'], 0, 2) === "id") {

                            $new_name = mb_substr($field['field'], 3);
                            $new_name = explode("__",$new_name)[0];

                            $text .= "\t'".$field['field']."' => array(\n\t\t'reference_to' => array('The constraint to ".$new_name.".id isn\'t respected.','".$new_name."', 'id')\n\t),\n";
                        } elseif (mb_substr($field['field'], 0, 2) === "ip") {
                            $text .= "\t'".$field['field']."' => array(\n\t\t'ip' => array('your IP is not valid')\n\t),\n";
                        } elseif ($field['field'] === "email") {
                            $text .= "\t'".$field['field']."' => array(\n\t\t'email' => array('your email is not valid')\n\t),\n";
                        } else {

                            if (mb_strstr($field['type'], "int")) {
                                $text .= "\t'".$field['field']."' => array(\n\t\t'numeric' => array('This must be an int.')\n\t),\n";
                            } elseif (mb_stristr($field['type'], "datetime")) {
                                $text .= "\t'".$field['field']."' => array(\n\t\t'dateTime' => array('This must be a datetime.')\n\t),\n";
                            } elseif (mb_stristr($field['type'], "time")) {
                                $text .= "\t'".$field['field']."' => array(\n\t\t'time' => array('This must be a time.')\n\t),\n";
                            } elseif (mb_stristr($field['type'], "date")) {
                                $text .= "\t'".$field['field']."' => array(\n\t\t'date' => array('This must be a date.')\n\t),\n";
                            } elseif (mb_stristr($field['type'], "float")) {
                                $text .= "\t'".$field['field']."' => array(\n\t\t'decimal' => array('This must be a float.')\n\t),\n";
                            } elseif (mb_stristr($field['type'], "VARCHAR2")) {
                                $text .= "\t'".$field['field']."' => array(\n\t\t'maxLength' => array('You execed the max length (".$field['length']." chars)', ".$field['length'].")\n\t),\n";
                            } elseif (mb_stristr($field['type'], "NUMBER")) {
                                $text .= "\t'".$field['field']."' => array(\n\t\t'numeric' => array('This must be an int.')\n\t),\n";
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
        //}
    }

    public function index()
    {

        //$this->layout_name = "admin";
        $this->title  = __("Administration");
        $this->ariane = "> ".$this->title;
        $dir          = APP_DIR.DS."controller";
        // Add your class dir to include path
        if (is_dir($dir)) {

            //$acl = new Acl($GLOBALS['_SITE']['id_group']);
            //$acl = $this->di['acl'];


            $path       = $dir."/*.controller.php";
            $list_class = glob($path);
            //$method_class_controller = get_class_methods("\Glial\Synapse\Controller");


            foreach ($list_class as $file) {
                if (strstr($file, '.controller.php')) {
                    $full_name = pathinfo($file);
                    list($className, ) = explode(".", $full_name['filename']);
                    if ($className != __CLASS__) {
                        //require_once($file);
                    }

                    $class = new \ReflectionClass($className);


                    $tab_methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
                    $methods     = array();



                    foreach ($tab_methods as $method) {
                        if ($method->class === $className) {
                            if (strstr($method->name, 'admin')) {




                                $methods[] = $method->name;
                            }
                        }
                    }


                    //$tab3 = array_diff($methods, $method_class_controller);

                    $data['method'] = $methods;


                    foreach ($methods as $name) {
                        //if ($GLOBALS['acl']->isAllowed($className, $name)) {



                        if (property_exists($className, "module_group")) {
                            $admin                                                   = new $className("", "", "");
                            $tmp                                                     = $admin->$name();
                            $data['link'][$admin->module_group][$tmp['name']]        = $admin->$name();
                            $data['link'][$admin->module_group][$tmp['name']]['url'] = $className."/".$name."/";
                        }
                        //}
                    }

                    // echo "memory : " . (memory_get_usage() / 1024 / 1024) . " M  fichier : $file : type : " . filetype($file) . "\n<br />";
                }
            }
        }
        $this->set("data", $this->data);
    }

    function acl()
    {

        $this->view = false;

        echo $GLOBALS['acl'];
    }
}
