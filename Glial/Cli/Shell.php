<?php

namespace Glial\Cli;

class Shell
{

    static public function prompt($msg, $filter, $allow_empty = false)
    {
        do {
            echo $msg;
            $val = trim(fgets(STDIN));
        } while (!$filter($val) && !(empty($val) && strlen($val) === 0 && $allow_empty)); // 
    }

}
