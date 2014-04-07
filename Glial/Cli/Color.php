<?php

namespace Glial\Cli;

/*
 *  Example of use :

  // Test some basic printing with Colors class
  echo color::getColoredString("Testing Colors class, this is purple string on yellow background.", "purple", "yellow") . "\n";
 */

class Color
{

// Set up shell colors
    private static $foreground_colors = array("black" => '0;30',
        "dark_gray" => '1;30',
        "blue" => '0;34',
        "light_blue" => '1;34',
        "green" => '0;32',
        "light_green" => '1;32',
        "cyan" => '0;36',
        "light_cyan" => '1;36',
        "red" => '0;31',
        "light_red" => '1;31',
        "purple" => '0;35',
        "light_purple" => '1;35',
        "brown" => '0;33',
        "yellow" => '1;33',
        "light_gray" => '0;37',
        "white" => '1;37',
    );
    private static $background_colors = array("black" => '40',
        "red" => '41',
        "green" => '42',
        "yellow" => '43',
        "blue" => '44',
        "magenta" => '45',
        "cyan" => '46',
        "light_gray" => '47');
        
        
        /* @since Glial 1.1
         * @description put text in color on CLI mode (16 colors foreground & 8 colors background)
         * @param $string string text to put in color
         * @param $foreground_color string the color of foreground to know witch color available have a look on $foreground_colors
         * @param $background_colors string the color of foreground to know witch color available have a look on $background_colors
         * @return return the string with Ansi code, if one color is not found generate a trow exception
         */

    public static function getColoredString($string, $foreground_color = null, $background_color = null)
    {
        $colored_string = "";

// Check if given foreground color found
        if ( isset(self::$foreground_colors[$foreground_color]) ) {
            $colored_string .= "\033[" . self::$foreground_colors[$foreground_color] . "m";
        }
// Check if given background color found
        if ( isset(self::$background_colors[$background_color]) ) {
            $colored_string .= "\033[" . self::$background_colors[$background_color] . "m";
        }

// Add string and end coloring
        $colored_string .= $string . "\033[0m";

        return $colored_string;
    }


        /* @since Glial 1.1
         * @description Returns all foreground color names
         * @param void void This function has no parameters.
         * @return Returns all colors available for the foreground
         */ 
    public static function getForegroundColor()
    {
        return array_keys(self::$foreground_colors);
    }



        /* @since Glial 1.1
         * @description Returns all foreground color names
         * @param void void This function has no parameters.
         * @return Returns all background color names
         */ 
    public static function getBackgroundColor()
    {
        return array_keys(self::$background_colors);
    }

        /* @since Glial 1.1
         * @description Make a preview of all combinaison between foreground and background color
         * @param void void This function has no parameters.
         * @return Returns a string sample with all combinaison available
         */ 


    public static function printAll()
    {
        $fgs = self::getForegroundColor();
// Get Background Colors
        $bgs = self::getBackgroundColor();

// Loop through all foreground and background colors
        $count = count($fgs);
        for ($i = 0; $i < $count; $i++) {
            echo self::getColoredString("Test Foreground colors : ". $fgs[$i], $fgs[$i]) . "\t";
            if ( isset($bgs[$i]) ) {
                echo self::getColoredString("Test Background colors : ". $bgs[$i], null, $bgs[$i]);
            }
            echo "\n";
        }
        echo "\n";

// Loop through all foreground and background colors
        foreach ($fgs as $fg) {
            foreach ($bgs as $bg) {
                echo self::getColoredString("Test Colors", $fg, $bg) . "\t";
            }
            echo "\n";
        }
    }

}
