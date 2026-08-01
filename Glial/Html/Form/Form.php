<?php

namespace Glial\Html\Form;

class Form
{
    static $data               = array();
    static $indice             = false;
    static $ajax               = false;
    static $select_display_msg = true;
    static $select_multiple    = false;

    static public function input($table, $field, $options = array())
    {
    
        $indice = self::getIndice($table, $field);

        if (empty($options['class'])) {
            $options['class'] = "";
        }
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
            $error = " <span class=\"error\">".$_SESSION['ERROR'][$table][$field]."</span>";
            //$options['value'] .= (empty($options['value'])) ? "error" : $options['value']." error";

            unset($_SESSION['ERROR'][$table][$field]);
        }

        if (empty($options['class'])) {
            unset($options['class']);
        }

        $extra = self::formatOptions($options);

        if ($indice !== -1) {
            $id   = $table."-".$indice."-".$field;
            $name = $table."[".$indice."][".$field."]";
        } else {
            $id   = $table."-".$field;
            $name = $table."[".$field."]";
        }

        return "<input id=\"".self::escapeAttribute($id)."\" name=\"".self::escapeAttribute($name)."\" ".$extra." />".$error;
    }

    static public function select($table, $field, $data, $default_id = "", $options = array(), $indice = -1)
    {
        if ($indice === -1) {
            $indice = self::getIndice($table, $field);
        }
        if (!empty($_SESSION['ERROR'][$table][$field])) {
            $error = " <span class=\"error\">".$_SESSION['ERROR'][$table][$field]."</span>";
            $class = " error";
            unset($_SESSION['ERROR'][$table][$field]);
        } else {
            $error = "";
            $class = "";
        }

        $all_available = false;
        if (!empty($options['all_selectable']) && $options['all_selectable'] === "true"){
            $all_available = true;
            unset($options['all_selectable']);
        }
        $extra = self::formatOptions($options);

        $ret = "";
        if (self::$select_multiple) {
            $add_array = "[]";
            $extra     .= " multiple";
        } else {
            $add_array = "";
        }

        if (!self::$ajax) {
            if ($indice != -1) {
                $id   = $table."-".$indice."-".$field;
                $name = $table."[".$indice."]"."[".$field."]".$add_array;
            } else {
                $id   = $table."-".$field;
                $name = $table."[".$field."]".$add_array;
            }
            $ret .= "<select id=\"".self::escapeAttribute($id)."\" $extra name=\"".self::escapeAttribute($name)."\">\n";
        }

        if (count($data) != 1 && self::$select_display_msg) {
            $ret .= "<option value=\"\">".self::escapeText(__("Nothing selected"))."</option>";
        }

        $i = 0;


        $tab_to_compare = array();
        if (self::$select_multiple === true) {
            $selection = $default_id;
            if (isset($_GET[$table]) && is_array($_GET[$table])) {
                if (
                    $indice !== -1
                    && isset($_GET[$table][$indice])
                    && is_array($_GET[$table][$indice])
                    && array_key_exists($field, $_GET[$table][$indice])
                ) {
                    $selection = $_GET[$table][$indice][$field];
                } elseif (array_key_exists($field, $_GET[$table])) {
                    $selection = $_GET[$table][$field];
                }
            }

            $tab_to_compare = self::normalizeSelectValues($selection);
        }

        //debug($tab_to_compare);

        foreach ($data as $val) {
            if (!empty($val['group']) && 1 === $val['group']) {

                if ($i != 0) $ret .= "</optgroup>";
                $ret .= "<optgroup LABEL=\"".self::escapeAttribute($val['libelle'])."\">";

                $i++;
            }
            else {
                $disable = "";
                $style   = "";
                if (!empty($val['error']) && $val['error'] === "1") {
                    $style   = 'style="background: #d9534f; color: #fff;"';
                }
                if (!empty($val['disabled']) && $val['disabled'] === "1") {

                    if ($all_available === false){
                        $disable = "disabled";
                    }
                   
                }

                $extra = "";
                if (!empty($val['extra'])) {

                    foreach ($val['extra'] as $opt => $v) {
                        $extra .= ' '.$opt.'="'.self::escapeAttribute($v).'"';
                    }
                }

                if (self::$select_multiple === true) {
                    $selected = in_array((string) $val['id'], $tab_to_compare, true);
                } else {
                    $selected = (
                        !empty($_GET[$table][$field])
                        && $_GET[$table][$field] == $val['id']
                    ) || (
                        !empty($default_id)
                        && $default_id == $val['id']
                    );
                }

                if ($selected) {
                    

                    $ret .= "<option value=\"".self::escapeAttribute($val['id'])."\" selected=\"selected\" $style $disable$extra>".self::escapeText($val['libelle'])."</option>\n";
                } else {
                    $ret .= "<option value=\"".self::escapeAttribute($val['id'])."\" $style $disable$extra>".self::escapeText($val['libelle'])."</option>\n";
                }
            }
        }
        if ($i > 0) {
            $ret .= "</optgroup>";
        }


        if (!self::$ajax) {
            $ret .= "</select>".$error;
        }
        self::$select_multiple = false;

        return $ret;
    }

    /**
     * Normalize multi-select values from current arrays, JSON payloads and
     * legacy bracketed/comma-separated strings.
     *
     * @param mixed $selection
     * @return array<int,string>
     */
    static private function normalizeSelectValues($selection)
    {
        $current = $selection;

        for ($depth = 0; $depth < 5 && is_string($current); $depth++) {
            $text = trim($current);
            if ($text === '') {
                return array();
            }

            $decoded = json_decode($text, true, 512, JSON_BIGINT_AS_STRING);
            if (json_last_error() !== JSON_ERROR_NONE || (!is_array($decoded) && !is_string($decoded))) {
                break;
            }

            $current = $decoded;
        }

        if (is_array($current)) {
            $values = array_values($current);
        } elseif (is_scalar($current)) {
            $text = trim((string) $current);
            if ($text === '') {
                return array();
            }

            if (strlen($text) >= 2 && $text[0] === '[' && $text[strlen($text) - 1] === ']') {
                $text = substr($text, 1, -1);
            }

            $values = str_getcsv($text, ',', '"', '\\');
        } else {
            return array();
        }

        $normalized = array();
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $value = trim((string) $value);
            while (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first !== '"' || $last !== '"') && ($first !== "'" || $last !== "'")) {
                    break;
                }

                $value = trim(substr($value, 1, -1));
            }

            if ($value !== '') {
                $normalized[$value] = true;
            }
        }

        return array_map('strval', array_keys($normalized));
    }

    static public function checkBox($table, $field, $value, $text, $options = array())
    {

        return '<label class="checkbox-inline">'
            .'<input type="checkbox" id="'.self::escapeAttribute($table.'-'.$field).'" value="'.self::escapeAttribute($value).'">'.self::escapeText($text).''
            .'</label>';
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
        $extra  = self::formatOptions($options);

        if ($indice != -1) {
            if (!empty($_GET[$table][$indice][$field])) {
                $value     = $_GET[$table][$indice][$field];
                $valueauto = $_GET[$table][$indice][$field."_auto"];
            } else {
                $value     = "";
                $valueauto = "";
            }
        } else {
            if (!empty($_GET[$table][$field])) {
                $value     = $_GET[$table][$field];
                $valueauto = $_GET[$table][$field."_auto"];
            } else {
                $value     = "";
                $valueauto = "";
            }
        }

        if (!empty($_SESSION['ERROR'][$table][$field])) {
            $error = " <span class=\"error\">".$_SESSION['ERROR'][$table][$field]."</span>";
            $class = " error";
            unset($_SESSION['ERROR'][$table][$field]);
        } else {
            $error = "";
            $class = "";
        }

        if ($indice != -1) {
//return "<input id=\"" . $table . "-" . $indice . "-" . $field . "\" class=\"" . $classo . "text" . $class . "\" type=\"text\" name=\"" . $table . "[" . $indice . "][" . $field . "]\" value=\"" . $value . "\" />" . $error;
            return "<input id=\"".self::escapeAttribute($table."-".$indice."-".$field."_auto")."\" $extra type=\"text\" name=\"".self::escapeAttribute($table."[".$indice."][".$field."_auto]")."\" value=\"".self::escapeAttribute($valueauto)."\" />"
                ."<input id=\"".self::escapeAttribute($table."-".$indice."-".$field)."\" name=\"".self::escapeAttribute($table."[".$indice."][".$field."]")."\" class=\"auto\" type=\"hidden\" value=\"".self::escapeAttribute($value)."\" />".$error;
        } else {
            return "<input id=\"".self::escapeAttribute($table."-".$field."_auto")."\" $extra type=\"text\" name=\"".self::escapeAttribute($table."[".$field."_auto]")."\" value=\"".self::escapeAttribute($valueauto)."\" />"
                ."<input id=\"".self::escapeAttribute($table."-".$field)."\" name=\"".self::escapeAttribute($table."[".$field."]")."\" class=\"hidden\" type=\"hidden\" value=\"".self::escapeAttribute($value)."\" />".$error;
        }
    }

    static private function formatOptions($options = array())
    {
        $extra = "";
        foreach ($options as $key => $val) {

            if ($key === "multiple") {
                self::$select_multiple = true;
            }

            $extra .= $key.'="'.self::escapeAttribute($val).'" ';
        }

        return $extra;
    }

    static private function escapeAttribute($value)
    {
        // Some legacy callers already encode values before calling Form.
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8", false);
    }

    static private function escapeText($value)
    {
        // Keep the same logical browser text for raw and already encoded callers.
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8", false);
    }

    static public function setAjax($val)
    {
        self::$ajax = ($val === true) ? true : false;
    }
}
