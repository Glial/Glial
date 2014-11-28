<?php

namespace Glial\Html\Form;

class Form
{

    static public function input($table, $field, $options = array(), $indice = -1)
    {
        $error = "";

        if ($indice != -1) {
            if (!empty($_GET[$table][$indice][$field])) {
                $options['value'] = $_GET[$table][$indice][$field];
            }
        } else {
            if (!empty($_GET[$table][$field])) {
                $options['value'] = $_GET[$table][$field];
            }
        }

        if (!empty($_SESSION['ERROR'][$table][$field])) {
            $error = " <span class=\"error\">" . $_SESSION['ERROR'][$table][$field] . "</span>";
            $options['class'] = (empty($options['value'])) ? "error" : $options['value'] . " error";

            unset($_SESSION['ERROR'][$table][$field]);
        }

        $extra = "";
        foreach ($options as $key => $val) {
            $extra .= $key . '="' . $val . '" ';
        }

        if ($indice != -1) {
            $id = $table . "-" . $indice . "-" . $field;
            $name = $table . "[" . $indice . "][" . $field . "]";
        } else {
            $id = $table . "-" . $field;
            $name = $table . "[" . $field . "]";
        }

        return "<input id=\"" . $id . "\" name=\"" . $name . "\" " . $extra . " />" . $error;
    }

    static public function select($table, $field, $data, $default_id = "", $options = array(), $ajax = 0, $indice = -1)
    {
        if (!empty($_SESSION['ERROR'][$table][$field])) {
            $error = " <span class=\"error\">" . $_SESSION['ERROR'][$table][$field] . "</span>";
            $class = " error";
            unset($_SESSION['ERROR'][$table][$field]);
        } else {
            $error = "";
            $class = "";
        }


        $extra = "";
        foreach ($options as $key => $val) {
            $extra .= $key . '="' . $val . '" ';
        }
        
        

        $ret = "";
        if ($ajax == 0) {
            if ($indice != -1) {
                $ret .= "<select id=\"" . $table . "-" . $indice . "-" . $field . "\" $extra name=\"" . $table . "[" . $indice . "]" . "[" . $field . "]\">";
            } else {
                $ret .= "<select id=\"" . $table . "-" . $field . "\" $extra name=\"" . $table . "[" . $field . "]\">";
            }
        }

        if (count($data) != 1) {
            $ret .= "<option value=\"\">--- " . __("Select") . " ---</option>";
        }

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

}
