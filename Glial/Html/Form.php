<?php

namespace Glial\Html;


class Form
{

  static public function input($table, $field, $options=array(), $indice=-1)
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
        $options['class'] = (empty($options['value']))? "error": $options['value']." error";

        unset($_SESSION['ERROR'][$table][$field]);
    } 


    $extra="";
    foreach($options as $key => $val)
    {
      $extra .= $key.'="'.$val.'" ';
    }


    if ($indice != -1) {
        $id = $table . "-" . $indice . "-" . $field;
        $name = $table . "[" . $indice . "][" . $field . "]";
        
          } else {
        $id = $table . "-" . $field;
        $name = $table . "[" . $field . "]";
         }
    
    
    return "<input id=\"" . $id . "\"  type=\"text\" name=\"" . $name ."\" value=\"" . $value . "\" ".$extra." />" . $error;
  
  
  }





}
