<?php

namespace Glial\String;

/*
 *  Example of use :

  // Test some basic printing with Colors class
  echo color::getColoredString("Testing Colors class, this is purple string on yellow background.", "purple", "yellow") . "\n";
 */

class String
{

    static public function strSplitUnicode($str, $l = 0)
    {
        if ($l > 0) {
            $ret = array();
            $len = mb_strlen($str, "UTF-8");
            for ($i = 0; $i < $len; $i += $l) {
                $ret[] = mb_substr($str, $i, $l, "UTF-8");
            }
            return $ret;
        }
        return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
    }

}
