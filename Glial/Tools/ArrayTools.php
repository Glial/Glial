<?php

namespace glial\tools;

class ArrayTools
{

    static function array_map_recursive($callback, $array)
    {
        foreach ($array as $key => $value) {
            if (is_array($array[$key])) {
                $array[$key] = self::array_map_recursive($callback, $array[$key]);
            } else {
                $array[$key] = \call_user_func($callback, $array[$key]);
            }
        }
        return $array;
    }

}