<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Form;

class Form
{

    static public function input($table, $field, $classo = "", $indice = -1)
    {


        if ($indice != -1) {
            if (!empty($_GET[$table][$indice][$field])) {
                $value = $_GET[$table][$indice][$field];
            } else {
                $value = "";
            }
        } else {
            if (!empty($_GET[$table][$field])) {
                $value = $_GET[$table][$field];
            } else {
                $value = "";
            }
        }

        if (!empty($_SESSION['ERROR'][$table][$field])) {
            $error = " <span class=\"error\">" . $_SESSION['ERROR'][$table][$field] . "</span>";
            $class = " error";

            unset($_SESSION['ERROR'][$table][$field]);
        } else {
            $error = "";
            $class = "";
        }

        if (!empty($classo))
            $classo = "$classo ";


        if ($indice != -1) {
            return "<input id=\"" . $table . "-" . $indice . "-" . $field . "\" class=\"" . $classo . "text" . $class . "\" type=\"text\" name=\"" . $table . "[" . $indice . "][" . $field . "]\" value=\"" . $value . "\" />" . $error;
        } else {
            return "<input id=\"" . $table . "-" . $field . "\" class=\"" . $classo . "text" . $class . "\" type=\"text\" name=\"" . $table . "[" . $field . "]\" value=\"" . $value . "\" />" . $error;
        }
    }

    static public function textarea($table, $field, $classo = "")
    {
        if (!empty($_GET[$table][$field])) {
            $value = $_GET[$table][$field];
        } else {
            $value = "";
        }

        if (!empty($_SESSION['ERROR'][$table][$field])) {
            $error = " <span class=\"error\">" . $_SESSION['ERROR'][$table][$field] . "</span>";
            $class = " error";

            unset($_SESSION['ERROR'][$table][$field]);
        } else {
            $error = "";
            $class = "";
        }

        if (!empty($classo))
            $classo = "$classo ";
        return "<textarea id=\"" . $table . "-" . $field . "\" class=\"" . $classo . "text" . $class . "\" type=\"text\" name=\"" . $table . "[" . $field . "]\" />" . $value . "</textarea>" . $error;
    }

    static public function hidden($table, $field, $default_value)
    {
        if (!empty($_GET[$table][$field])) {
            $value = $_GET[$table][$field];
        } else {
            if (!empty($default_value)) {
                $value = $default_value;
            } else {
                $value = "";
            }
        }



        if (!empty($classo))
            $classo = "$classo ";
        return "<input id=\"" . $table . "-" . $field . "\" type=\"hidden\" name=\"" . $table . "[" . $field . "]\" value=\"" . $value . "\" />";
    }

    static public function autocomplete($table, $field, $classo = "", $indice = -1)
    {


        if ($indice != -1) {
            if (!empty($_GET[$table][$indice][$field])) {
                $value = $_GET[$table][$indice][$field];
                $valueauto = $_GET[$table][$indice][$field . "-auto"];
            } else {
                $value = "";
                $valueauto = "";
            }
        } else {
            if (!empty($_GET[$table][$field])) {
                $value = $_GET[$table][$field];
                $valueauto = $_GET[$table][$field . "-auto"];
            } else {
                $value = "";
                $valueauto = "";
            }
        }

        if (!empty($_SESSION['ERROR'][$table][$field])) {
            $error = " <span class=\"error\">" . $_SESSION['ERROR'][$table][$field] . "</span>";
            $class = " error";
            unset($_SESSION['ERROR'][$table][$field]);
        } else {
            $error = "";
            $class = "";
        }

        if (!empty($classo))
            $classo = "$classo ";


        if ($indice != -1) {
//return "<input id=\"" . $table . "-" . $indice . "-" . $field . "\" class=\"" . $classo . "text" . $class . "\" type=\"text\" name=\"" . $table . "[" . $indice . "][" . $field . "]\" value=\"" . $value . "\" />" . $error;
            return "<input id=\"" . $table . "-" . $indice . "-" . $field . "-auto\" class=\"" . $classo . "text" . $class . "\" type=\"text\" name=\"" . $table . "[" . $indice . "][" . $field . "-auto]\" value=\"" . $valueauto . "\" />"
                    . "<input id=\"" . $table . "-" . $indice . "-" . $field . "\" name=\"" . $table . "[" . $indice . "][" . $field . "]\" class=\"hidden\" type=\"text\" value=\"" . $value . "\" />" . $error;


        } else {
            return "<input id=\"" . $table . "-" . $field . "-auto\" class=\"" . $classo . "text" . $class . "\" type=\"text\" name=\"" . $table . "[" . $field . "-auto]\" value=\"" . $valueauto . "\" />"
                    . "<input id=\"" . $table . "-" . $field . "\" name=\"" . $table . "[" . $field . "]\" class=\"hidden\" type=\"text\" value=\"" . $value . "\" />" . $error;
        }
    }

    static public function password($table, $field, $classo = "")
    {

        if (!empty($_SESSION['ERROR'][$table][$field])) {
            $error = " <span class=\"error\">" . $_SESSION['ERROR'][$table][$field] . "</span>";
            $class = " error";
            unset($_SESSION['ERROR'][$table][$field]);
        } else {
            $error = "";
            $class = "";
        }
        if (!empty($classo))
            $classo = "$classo ";
        return "<input id=\"" . $table . "-" . $field . "\" class=\"" . $classo . "text" . $class . "\" type=\"password\" name=\"" . $table . "[" . $field . "]\" />" . $error;
    }

    static public function select($table, $field, $data, $default_id = "", $classo = "", $ajax = 0, $indice = -1)
    {
        if (!empty($_SESSION['ERROR'][$table][$field])) {
            $error = " <span class=\"error\">" . $_SESSION['ERROR'][$table][$field] . "</span>";
            $class = " error";
            unset($_SESSION['ERROR'][$table][$field]);
        } else {
            $error = "";
            $class = "";
        }


        if (!empty($classo))
            $classo = "$classo ";

        $ret = "";
        if ($ajax == 0) {
            if ($indice != -1) {
                $ret .= "<select id=\"" . $table . "-" . $indice . "-" . $field . "\" class=\"" . $classo . "select" . $class . "\" name=\"" . $table . "[" . $indice . "]" . "[" . $field . "]\">";
            } else {
                $ret .= "<select id=\"" . $table . "-" . $field . "\" class=\"" . $classo . "select" . $class . "\" name=\"" . $table . "[" . $field . "]\">";
            }
        }

        if (count($data) != 1) {
            $ret .= "<option value=\"\">--- " . __("Select") . " ---</option>";
        }
//$_SQL = Singleton::getInstance(SQL_DRIVER);
//$table_to_get = substr($field,3);
//$sql = "SELECT id, `".$libelle."` FROM `".$table_source."` WHERE ".$libelle." != '' ORDER BY ".$libelle."";
//$res = $_SQL->sql_query($sql);
//$var = $_SQL->sql_to_array($res);

        $i = 0;

        foreach ($data as $val) {


            if (!empty($val['group']) && 1 === $val['group']) {

                if ($i != 0)
                    $ret .= "</optgroup>";
                $ret .= "<optgroup LABEL=\"" . $val['libelle'] . "\">";

                $i++;
            }
            else {

                if ((!empty($_GET[$table][$field]) && $_GET[$table][$field] == $val['id']) || (!empty($default_id) && $default_id == $val['id'])) {
                    $ret .= "<option value=\"" . $val['id'] . "\" selected=\"selected\">" . $val['libelle'] . "</option>";
                } else {
                    $ret .= "<option value=\"" . $val['id'] . "\">" . $val['libelle'] . "</option>";
                }
            }
        }
        if ($i > 0)
            $ret .= "</optgroup>";



        if ($ajax == 0) {
            $ret .= "</select>" . $error;
        }
        return $ret;
    }

    static public function radio($table, $field, $value, $default)
    {
        $checked = '';
        
        if (empty($_GET[$table][$field]) && !empty($default) ) {
            if ($default === $value) {
                $checked = ' checked="checked" ';
            }
        }
        
        if ((!empty($_GET[$table][$field])) && $_GET[$table][$field] === $value)
        {
            $checked = ' checked="checked" ';
        }

        return "\n".'<input type="radio" id="' . $value . '" name="' . $table . '[' . $field . ']" value="' . $value . '" '.$checked.' />'."\n";
    }
    
    
    static public function file($table)
    {
        
        $getsize = (int) substr(ini_get("upload_max_filesize"),0,-1) * 1024*1024;

        return '<input type="hidden" name="MAX_FILE_SIZE" value="'.$getsize.'" /><input name="'.$table.'" type="file" /> (Max file size : '.ini_get("upload_max_filesize").')';
        
        
    }
    
    

}
