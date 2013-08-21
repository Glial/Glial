<?php

namespace glial\shell;

/*
 *  Example of use :

  // Test some basic printing with Colors class
  echo color::getColoredString("Testing Colors class, this is purple string on yellow background.", "purple", "yellow") . "\n";
 */

class Color
{

// Set up shell colors
	static private $foreground_colors = array("black" => '0;30',
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
	static private $background_colors = array("black" => '40',
		"red" => '41',
		"green" => '42',
		"yellow" => '43',
		"blue" => '44',
		"magenta" => '45',
		"cyan" => '46',
		"light_gray" => '47');

	static public function getColoredString($string, $foreground_color = null, $background_color = null)
	{
		$colored_string = "";

// Check if given foreground color found
		if ( isset(self::$foreground_colors[$foreground_color]) )
		{
			$colored_string .= "\033[" . self::$foreground_colors[$foreground_color] . "m";
		}
// Check if given background color found
		if ( isset(self::$background_colors[$background_color]) )
		{
			$colored_string .= "\033[" . self::$background_colors[$background_color] . "m";
		}

// Add string and end coloring
		$colored_string .= $string . "\033[0m";
		return $colored_string;
	}

// Returns all foreground color names
	static public function getForegroundColor()
	{
		return array_keys(self::$foreground_colors);
	}

// Returns all background color names
	static public function getBackgroundColor()
	{
		return array_keys(self::$background_colors);
	}

	static public function printAll()
	{
		$fgs = $this->get_foreground_color();
// Get Background Colors
		$bgs = $this->get_background_color();

// Loop through all foreground and background colors
		$count = count($fgs);
		for ( $i = 0; $i < $count; $i++ )
		{
			echo self::get_colored_string("Test Foreground colors", $fgs[$i]) . "\t";
			if ( isset($bgs[$i]) )
			{
				echo self::get_colored_string("Test Background colors", null, $bgs[$i]);
			}
			echo "\n";
		}
		echo "\n";

// Loop through all foreground and background colors
		foreach ( $fgs as $fg )
		{
			foreach ( $bgs as $bg )
			{
				echo self::get_colored_string("Test Colors", $fg, $bg) . "\t";
			}
			echo "\n";
		}
	}

}