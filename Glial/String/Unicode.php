<?php

namespace Glial\String;

/*
 *  Example of use :

  // Test some basic printing with Colors class
  echo color::getColoredString("Testing Colors class, this is purple string on yellow background.", "purple", "yellow") . "\n";
 */

class Unicode
{

    private $latin1_to_utf8;
    private $utf8_to_latin1;

    public function __construct()
    {
        for ($i = 32; $i <= 255; $i++) {
            $this->latin1_to_utf8[chr($i)] = utf8_encode(chr($i));
            $this->utf8_to_latin1[utf8_encode(chr($i))] = chr($i);
        }
    }

    public function mixed_to_latin1($text)
    {
        foreach ($this->utf8_to_latin1 as $key => $val) {
            $text = str_replace($key, $val, $text);
        }
        return $text;
    }

    public function mixed_to_utf8($text)
    {
        return utf8_encode($this->mixed_to_latin1($text));
    }

    function Utf8ToIso8859_11($string)
    {

        if (!preg_match("/[\241-\377]/", $string)) {
            return $string;
        }
        $UTF8 = array(
            "\xe0\xb8\x81" => "\xa1",
            "\xe0\xb8\x82" => "\xa2",
            "\xe0\xb8\x83" => "\xa3",
            "\xe0\xb8\x84" => "\xa4",
            "\xe0\xb8\x85" => "\xa5",
            "\xe0\xb8\x86" => "\xa6",
            "\xe0\xb8\x87" => "\xa7",
            "\xe0\xb8\x88" => "\xa8",
            "\xe0\xb8\x89" => "\xa9",
            "\xe0\xb8\x8a" => "\xaa",
            "\xe0\xb8\x8b" => "\xab",
            "\xe0\xb8\x8c" => "\xac",
            "\xe0\xb8\x8d" => "\xad",
            "\xe0\xb8\x8e" => "\xae",
            "\xe0\xb8\x8f" => "\xaf",
            "\xe0\xb8\x90" => "\xb0",
            "\xe0\xb8\x91" => "\xb1",
            "\xe0\xb8\x92" => "\xb2",
            "\xe0\xb8\x93" => "\xb3",
            "\xe0\xb8\x94" => "\xb4",
            "\xe0\xb8\x95" => "\xb5",
            "\xe0\xb8\x96" => "\xb6",
            "\xe0\xb8\x97" => "\xb7",
            "\xe0\xb8\x98" => "\xb8",
            "\xe0\xb8\x99" => "\xb9",
            "\xe0\xb8\x9a" => "\xba",
            "\xe0\xb8\x9b" => "\xbb",
            "\xe0\xb8\x9c" => "\xbc",
            "\xe0\xb8\x9d" => "\xbd",
            "\xe0\xb8\x9e" => "\xbe",
            "\xe0\xb8\x9f" => "\xbf",
            "\xe0\xb8\xa0" => "\xc0",
            "\xe0\xb8\xa1" => "\xc1",
            "\xe0\xb8\xa2" => "\xc2",
            "\xe0\xb8\xa3" => "\xc3",
            "\xe0\xb8\xa4" => "\xc4",
            "\xe0\xb8\xa5" => "\xc5",
            "\xe0\xb8\xa6" => "\xc6",
            "\xe0\xb8\xa7" => "\xc7",
            "\xe0\xb8\xa8" => "\xc8",
            "\xe0\xb8\xa9" => "\xc9",
            "\xe0\xb8\xaa" => "\xca",
            "\xe0\xb8\xab" => "\xcb",
            "\xe0\xb8\xac" => "\xcc",
            "\xe0\xb8\xad" => "\xcd",
            "\xe0\xb8\xae" => "\xce",
            "\xe0\xb8\xaf" => "\xcf",
            "\xe0\xb8\xb0" => "\xd0",
            "\xe0\xb8\xb1" => "\xd1",
            "\xe0\xb8\xb2" => "\xd2",
            "\xe0\xb8\xb3" => "\xd3",
            "\xe0\xb8\xb4" => "\xd4",
            "\xe0\xb8\xb5" => "\xd5",
            "\xe0\xb8\xb6" => "\xd6",
            "\xe0\xb8\xb7" => "\xd7",
            "\xe0\xb8\xb8" => "\xd8",
            "\xe0\xb8\xb9" => "\xd9",
            "\xe0\xb8\xba" => "\xda",
            "\xe0\xb8\xbf" => "\xdf",
            "\xe0\xb9\x80" => "\xe0",
            "\xe0\xb9\x81" => "\xe1",
            "\xe0\xb9\x82" => "\xe2",
            "\xe0\xb9\x83" => "\xe3",
            "\xe0\xb9\x84" => "\xe4",
            "\xe0\xb9\x85" => "\xe5",
            "\xe0\xb9\x86" => "\xe6",
            "\xe0\xb9\x87" => "\xe7",
            "\xe0\xb9\x88" => "\xe8",
            "\xe0\xb9\x89" => "\xe9",
            "\xe0\xb9\x8a" => "\xea",
            "\xe0\xb9\x8b" => "\xeb",
            "\xe0\xb9\x8c" => "\xec",
            "\xe0\xb9\x8d" => "\xed",
            "\xe0\xb9\x8e" => "\xee",
            "\xe0\xb9\x8f" => "\xef",
            "\xe0\xb9\x90" => "\xf0",
            "\xe0\xb9\x91" => "\xf1",
            "\xe0\xb9\x92" => "\xf2",
            "\xe0\xb9\x93" => "\xf3",
            "\xe0\xb9\x94" => "\xf4",
            "\xe0\xb9\x95" => "\xf5",
            "\xe0\xb9\x96" => "\xf6",
            "\xe0\xb9\x97" => "\xf7",
            "\xe0\xb9\x98" => "\xf8",
            "\xe0\xb9\x99" => "\xf9",
            "\xe0\xb9\x9a" => "\xfa",
            "\xe0\xb9\x9b" => "\xfb",
        );

        $string = strtr($string, $UTF8);
        return $string;
    }

    function urlize($url)
    {
        $search = array('/[^a-z0-9]/', '/--+/', '/^-+/', '/-+$/');
        $replace = array('-', '-', '', '');
        return preg_replace($search, $replace, utf2ascii($url));
    }

    function utf2ascii($string)
    {
        $iso88591 = "\\xE0\\xE1\\xE2\\xE3\\xE4\\xE5\\xE6\\xE7";
        $iso88591 .= "\\xE8\\xE9\\xEA\\xEB\\xEC\\xED\\xEE\\xEF";
        $iso88591 .= "\\xF0\\xF1\\xF2\\xF3\\xF4\\xF5\\xF6\\xF7";
        $iso88591 .= "\\xF8\\xF9\\xFA\\xFB\\xFC\\xFD\\xFE\\xFF";
        $ascii = "aaaaaaaceeeeiiiidnooooooouuuuyyy";
        return strtr(mb_strtolower(utf8_decode($string), 'ISO-8859-1'), $iso88591, $ascii);
    }

}


// test : echo urlize("Fucking �m�l"); 