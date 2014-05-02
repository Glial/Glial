<?php

namespace Glial\String;

use Glial\CLi\Color;

/*
 *  Example of use :

  // Test some basic printing with Colors class
  echo color::getColoredString("Testing Colors class, this is purple string on yellow background.", "purple", "yellow") . "\n";
 */

class String {

    static public function strSplitUnicode($str, $l = 0) {
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

    /**
     * wordwrap for utf8 encoded strings
     *
     * @param string $str
     * @param integer $len
     * @param string $what
     * @return string
     * @author Milian Wolff <mail@milianw.de> & Aurélien LEQUOY <aurélien.lequoy@esysteme.com>
     * @since 3.0
     * @version 3.0
     */
    static public function utf8_wordwrap($str, $width, $break, $cut = false) {
        if (!$cut) {
            $regexp = '#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){' . $width . ',}\b#U';
        } else {
            $regexp = '#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){' . $width . '}#';
        }
        if (function_exists('mb_strlen')) {
            $str_len = mb_strlen($str, 'UTF-8');
        } else {
            $str_len = preg_match_all('/[\x00-\x7F\xC0-\xFD]/', $str, $var_empty);
        }
        $while_what = ceil($str_len / $width);
        $i = 1;
        $return = '';
        while ($i < $while_what) {
            preg_match($regexp, $str, $matches);
            $string = $matches[0];
            $return .= $string . $break;
            $str = substr($str, strlen($string));
            $i++;
        }
        return $return . $str;
    }

    static public function str_pad_unicode($str, $pad_len, $pad_str = ' ', $dir = STR_PAD_RIGHT) {
        $str_len = mb_strlen($str);
        $pad_str_len = mb_strlen($pad_str);
        if (!$str_len && ($dir == STR_PAD_RIGHT || $dir == STR_PAD_LEFT)) {
            $str_len = 1; // @debug
        }
        if (!$pad_len || !$pad_str_len || $pad_len <= $str_len) {
            return $str;
        }

        $result = null;
        if ($dir == STR_PAD_BOTH) {
            $length = ($pad_len - $str_len) / 2;
            $repeat = ceil($length / $pad_str_len);
            $result = mb_substr(str_repeat($pad_str, $repeat), 0, floor($length))
                    . $str
                    . mb_substr(str_repeat($pad_str, $repeat), 0, ceil($length));
        } else {
            $repeat = ceil($str_len - $pad_str_len + $pad_len);
            if ($dir == STR_PAD_RIGHT) {
                $result = $str . str_repeat($pad_str, $repeat);
                $result = mb_substr($result, 0, $pad_len);
            } else if ($dir == STR_PAD_LEFT) {
                $result = str_repeat($pad_str, $repeat);
                $result = mb_substr($result, 0, $pad_len - (($str_len - $pad_str_len) + $pad_str_len))
                        . $str;
            }
        }

        return $result;
    }

}
