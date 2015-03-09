<?php

namespace Glial\Html\Form;

class Form
{

    static $data = array();
    static $indice = false;
    static $ajax = false;

    static public function input($table, $field, $options = array())
    {

        $indice = self::getIndice($table, $field);


        $error = "";

        if ($indice !== -1) {
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


        $extra = self::formatOptions($options);

        if ($indice !== -1) {
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
        $indice = self::getIndice($table, $field);

        if (!empty($_SESSION['ERROR'][$table][$field])) {
            $error = " <span class=\"error\">" . $_SESSION['ERROR'][$table][$field] . "</span>";
            $class = " error";
            unset($_SESSION['ERROR'][$table][$field]);
        } else {
            $error = "";
            $class = "";
        }


        $extra = self::formatOptions($options);
        
        
        $ret = "";
        if (!self::$ajax) {
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



        if (!self::$ajax) {
            $ret .= "</select>" . $error;
        }
        return $ret;
    }

    static public function checkBox($table, $field, $value, $text, $options = array(), $options = array())
    {

        return '<label class="checkbox-inline">'
                . '<input type="checkbox" id="' . $table . '-' . $field . '" value="' . $value . '">' . $text . ''
                . '</label>';
    }

    static private function getIndice($table, $field)
    {
        if (!self::$indice) {
            return -1;
        }
        if (isset(self::$data[$table][$field])) {
            self::$data[$table][$field] ++;
        } else {
            self::$data[$table][$field] = 0;
        }
        return self::$data[$table][$field];
    }

    static public function setIndice($val)
    {
        self::$indice = ($val === true) ? true : false;
    }

    static public function autocomplete($table, $field, $options = array())
    {

        $indice = self::getIndice($table, $field);
        $extra = self::formatOptions($options);

        if ($indice != -1) {
            if (!empty($_GET[$table][$indice][$field])) {
                $value = $_GET[$table][$indice][$field];
                $valueauto = $_GET[$table][$indice][$field . "_auto"];
            } else {
                $value = "";
                $valueauto = "";
            }
        } else {
            if (!empty($_GET[$table][$field])) {
                $value = $_GET[$table][$field];
                $valueauto = $_GET[$table][$field . "_auto"];
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

        if ($indice != -1) {
//return "<input id=\"" . $table . "-" . $indice . "-" . $field . "\" class=\"" . $classo . "text" . $class . "\" type=\"text\" name=\"" . $table . "[" . $indice . "][" . $field . "]\" value=\"" . $value . "\" />" . $error;
            return "<input id=\"" . $table . "-" . $indice."-" . $field . "_auto\" $extra type=\"text\" name=\"" . $table . "[" . $indice . "][" . $field . "_auto]\" value=\"" . $valueauto . "\" />"
                    . "<input id=\"" . $table . "-" . $indice . "-" . $field . "\" name=\"" . $table . "[" . $indice. "][" . $field  . "]\" class=\"auto\" type=\"hidden\" value=\"" . $value . "\" />" . $error;
        } else {
            return "<input id=\"" . $table . "-" . $field . "_auto\" $extra type=\"text\" name=\"" . $table . "[" . $field . "_auto]\" value=\"" . $valueauto . "\" />"
                    . "<input id=\"" . $table . "-" . $field . "\" name=\"" . $table . "[" . $field . "]\" class=\"hidden\" type=\"hidden\" value=\"" . $value . "\" />" . $error;
        }
    }

    static private function formatOptions($options = array())
    {
        $extra = "";
        foreach ($options as $key => $val) {
            $extra .= $key . '="' . $val . '" ';
        }

        return $extra;
    }

    static public function setAjax($val)
    {
        self::$ajax = ($val === true) ? true : false;
    }
    
    

}
