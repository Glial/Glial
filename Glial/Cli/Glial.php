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
        $str .= Color::getColoredString("Glial ".self::version(), "green");
        $str .= " (".self::date().") written by Aurélien LEQUOY.".PHP_EOL;
        
        return $str;
    }
    
        public static function name()
    {
        return "Glial";
    }
    
    
    public static function version()
    {
        return "4.1.7";
    }
    
    
        public static function date()
    {
        return "2017-05-25";
    }
    
}
