<?php

namespace Glial\Synapse;

class Basic
{
        /**
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param string __FILE__
     * @return boolean Success / or file where it's come from
     * @description to know who called the script, the goal is not to launch two times a long method
     * @access public
     * @example \Glial\Synapse\FactoryController::init($array);
     * @package Controller
     * @since 4.1 Accept the param __FILE__ if defined return true or false
     */

    static public function from($file = "")
    {
        $calledFrom = debug_backtrace();
        $var        = explode(DS, substr(str_replace(ROOT, '', $calledFrom[1]['file']), 1));
        $source = strtolower(end($var));

        if (! empty($file)) {

            if (strtolower(pathinfo($file)["basename"]) === $source) {
                return true;
            } else {
                return false;
            }
        }

        return($source );
    }
}