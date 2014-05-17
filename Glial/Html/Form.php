<?php

namespace Glial\Html;


class Form
{

  static public function input($table, $field, $options=array(), $indice=-1)
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


    $extra="";
    foreach($options as $key => $val)
    {
      $extra .= " ".$key."=".$val." ";
    }


    if ($indice != -1) {
        return "<input id=\"" . $table . "-" . $indice . "-" . $field . "\" class=\"" . $classo . "text" . $class . "\" type=\"text\" name=\"" . $table . "[" . $indice . "][" . $field . "]\" value=\"" . $value . "\" />" . $error;
    } else {
        return "<input id=\"" . $table . "-" . $field . "\" class=\"" . $classo . $class . "\" type=\"text\" name=\"" . $table . "[" . $field . "]\" value=\"" . $value . "\" />" . $error;
    }
  
  
  }





}
