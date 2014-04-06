<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Shell;

class Glial
{

    public static function logo()
    {

        $logo = " ██████╗ ██╗     ██╗ █████╗ ██╗     
██╔════╝ ██║     ██║██╔══██╗██║     
██║  ███╗██║     ██║███████║██║     
██║   ██║██║     ██║██╔══██║██║     
╚██████╔╝███████╗██║██║  ██║███████╗
 ╚═════╝ ╚══════╝╚═╝╚═╝  ╚═╝╚══════╝";

        return $logo;
    }

    
    public static function header()
    {
        $str = self::logo();
        
        $str .= PHP_EOL."Glial 2.1.2 (2014-04-05) by Aurélien LEQUOY.".PHP_EOL;
        
        return $str;
    }
    
    
}
