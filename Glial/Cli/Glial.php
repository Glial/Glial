<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Cli;


use  \Glial\Cli\Color;

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
        
        $str .= PHP_EOL;
        $str .= Color::getColoredString("Glial 2.1.2", "green");
        $str .= " (2014-04-05) by Aurélien LEQUOY.".PHP_EOL;
        
        return $str;
    }
    
    
}
