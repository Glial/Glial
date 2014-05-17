<?php

namespace Glial\Cli;

/*
 *  Example of use :

  // Test some basic printing with Colors class
  echo color::getColoredString("Testing Colors class, this is purple string on yellow background.", "purple", "yellow") . "\n";
 */

class Color
{
    /*
     * http://en.wikipedia.org/wiki/ANSI_escape_code
      Code	Effect	Note
      0	Reset / Normal	all attributes off
      1	Bold or increased intensity
      2	Faint (decreased intensity)	not widely supported
      3	Italic: on	not widely supported. Sometimes treated as inverse.
      4	Underline: Single
      5	Blink: Slow	less than 150 per minute
      6	Blink: Rapid	MS-DOS ANSI.SYS; 150 per minute or more; not widely supported
      7	Image: Negative	inverse or reverse; swap foreground and background (reverse video)
      8	Conceal	not widely supported
      9	Crossed-out	Characters legible, but marked for deletion. Not widely supported.
      10	Primary(default) font
      11–19	n-th alternate font	Select the n-th alternate font. 14 being the fourth alternate font, up to 19 being the 9th alternate font.
      20	Fraktur	hardly ever supported
      21	Bold: off or Underline: Double	bold off not widely supported, double underline hardly ever
      22	Normal color or intensity	neither bold nor faint
      23	Not italic, not Fraktur
      24	Underline: None	not singly or doubly underlined
      25	Blink: off
      26	Reserved
      27	Image: Positive
      28	Reveal	conceal off
      29	Not crossed out
      30–37	Set text color (foreground)	30 + x, where x is from the color table below
      38	Set xterm-256 text color (foreground)[dubious – discuss]	next arguments are 5;x where x is color index (0..255)
      39	Default text color (foreground)	implementation defined (according to standard)
      40–47	Set background color	40 + x, where x is from the color table below
      48	Set xterm-256 background color	next arguments are 5;x where x is color index (0..255)
      49	Default background color	implementation defined (according to standard)
      50	Reserved
      51	Framed
      52	Encircled
      53	Overlined
      54	Not framed or encircled
      55	Not overlined
      56–59	Reserved
      60	ideogram underline or right side line	hardly ever supported
      61	ideogram double underline or double line on the right side	hardly ever supported
      62	ideogram overline or left side line	hardly ever supported
      63	ideogram double overline or double line on the left side	hardly ever supported
      64	ideogram stress marking	hardly ever supported
      90–99	Set foreground text color, high intensity	aixterm (not in standard)
      100–109	Set background color, high intensity	aixterm (not in standard)
     */

// Set up shell colors
    private static $color = array(
        'black' => 30,
        'red' => 31,
        'green' => 32,
        'yellow' => 33,
        'blue' => 34,
        'purple' => 35,
        'cyan' => 36,
        'grey' => 37
    );
    private static $background = array(
        'black' => 40,
        'red' => 41,
        'green' => 42,
        'yellow' => 43,
        'blue' => 44,
        'purple' => 45,
        'cyan' => 46,
        'grey' => 47);
    private static $style = array(
        'normal' => 0,
        'bold' => 1, //Bold or increased intensity
        'light' => 1,
        'faint' => 2, //not widely supported
        'italic' => 3, //not widely supported. Sometimes treated as inverse.
        'underline' => 4,
        'blink' => 5, //less than 150 per minute
        'blink_fast' => 6, //MS-DOS ANSI.SYS; 150 per minute or more; not widely supported
        'inverse' => 7, //inverse or reverse; swap foreground and background
        'conceal' => 8,
    );

    /* @since Glial 1.1
     * @since Glial 2.1.2 split style and background, put background in 3rd arg and style in 4th arg.
     * @description put text in color on CLI mode (16 colors foreground & 8 colors background)
     * @param $string string text to put in color
     * @param $foreground_color string the color of foreground to know witch color available have a look on $foreground_colors
     * @param $background_colors string the color of foreground to know witch color available have a look on $background_colors
     * @return return the string with Ansi code, if one color is not found generate a trow exception
     */

    public function __construct()
    {
        
    }

    public static function getColoredString($string, $color = null, $background = null, $style = null)
    {

        ($style) ? self::testColor($color, self::$color) : '';
        ($style) ? self::testColor($background, self::$background) : '';
        ($style) ? self::testColor($style, self::$style) : '';

        $colored_string = "";

        $ansi = array();

        $ansi[] = ($style) ? self::$style[$style] : '0';
        $ansi[] = ($color) ? self::$color[$color] : '37';
        $ansi[] = ($background) ? self::$background[$background] : '40';

        $str = implode(';', $ansi);

        $colored_string = "\033[" . $str . "m";
        $colored_string .= $string . "\033[0m";

        return $colored_string;
    }

    /* @since Glial 1.1
     * @description Returns all foreground color names
     * @param void void This function has no parameters.
     * @return Returns all colors available for the foreground
     */

    public static function getColor()
    {
        return array_keys(self::$color);
    }

    /**
     *  @since Glial 1.1
     * @description Returns all foreground color names
     * @param void void This function has no parameters.
     * @return Returns all background color names
     */
    public static function getBackground()
    {
        return array_keys(self::$background);
    }

    /**
     *  @since Glial 1.1
     * @description Make a preview of all combinaison between color, background and style color
     * @param void void This function has no parameters.
     * @return Returns a string sample with all combinaison available
     */
    public static function printAll()
    {
        foreach (array_keys(self::$style) as $style) {

            foreach (array_keys(self::$color) as $color) {

                foreach (array_keys(self::$background) as $background) {
                    echo self::getColoredString(str_pad($color, 7) . str_pad($background, 7) . str_pad($style, 9), $color, $background, $style) . " ";
                }
                echo PHP_EOL;
            }
            echo PHP_EOL;
        }
        echo PHP_EOL;
    }

    /**
     * Strips ANSI color codes from a string
     *
     * @param string $string String to strip
     *
     * @acess public
     * @return string
     */
    public static function strip($string)
    {
        return preg_replace('/\033\[[\d;]+[\d]?m/', '', $string);
    }

    private static function testColor($color, $array)
    {
        //echo $color."--";
        if (!array_key_exists($color, $array)) {
            throw new \DomainException("GLI-016 : Color code not found : " . $color);
        }
    }
    
    static public function setColor($color = null, $background = null, $style = null)
    {
        ($style) ? self::testColor($color, self::$color) : '';
        ($style) ? self::testColor($background, self::$background) : '';
        ($style) ? self::testColor($style, self::$style) : '';

        $colored_string = "";

        $ansi = array();

        $ansi[] = ($style) ? self::$style[$style] : '0';
        $ansi[] = ($color) ? self::$color[$color] : '37';
        $ansi[] = ($background) ? self::$background[$background] : '40';

        $str = implode(';', $ansi);

        $color = "\033[" . $str . "m";
        
        return $color;
    }

}
