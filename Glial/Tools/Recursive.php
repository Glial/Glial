<?php

namespace glial\tools;

class Recursive
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


/*
 * @author soywiz at gmail dot com 
 * @since Glial 2.0
 * @description The globRecursive() function searches for all the pathnames matching pattern according to the rules used by the libc glob() function, which is similar to the rules used by common shells.
 *
 */
    static function globRecursive($path, $find, $flags = FNM_PATHNAME)
    {
        $dh   = opendir($path);
        while (($file = readdir($dh)) !== false) {
            if (substr($file, 0, 1) == '.')
                continue;
            $rfile = "{$path}/{$file}";
            if (is_dir($rfile)) {
                foreach (self::globRecursive($rfile, $find, $flags) as $ret) {
                    yield $ret;
                }
            } else {
                if (fnmatch($find, $file, $flags)) {
                    yield $rfile;
                }
            }
        }
        closedir($dh);
    }
    
/**
 * Remove the directory and its content (all files and subdirectories).
 * @param string $dir the directory name
 * @author wang yun
 */
function rmrf($dir) {
    foreach (glob($dir) as $file) {
        if (is_dir($file)) { 
            rmrf("$file/*");
            rmdir($file);
        } else {
            unlink($file);
        }
    }
}



}
