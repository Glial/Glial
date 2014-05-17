<?php

namespace Glial\Cli;

use Glial\Cli\Color;
use Glial\String\String;

class Window
{

    public $max_default = 180;
    public $max_width;
    public $max_height;
    public $title_length;
    public $msg_width;
    public $msg_height;
    public $msg;
    public $color_background = "blue";
    public $color_window = "grey";
    public $color_foreground_title = "red";
    public $color_foreground_msg = "black";
    public $color_shadow = "black";
    public $color_cursor = "red";
    private $windows;
    private $position_input;
    private $position_input2;
    private $before;
    private $after;
    private $cursor_position_horizontal = 1;
    private $cursor_position_vertical = 1;
    private $cursor_position_input = array();

    public function __construct($title, $msg)
    {

        $value = '';
        $sizes = exec("stty size");
        $size = explode(" ", $sizes);


        $this->max_width = $size[1];
        $this->max_height = $size[0];



        echo $this->windows($title, $msg);

        echo "\033[{$this->cursor_position_input[0]['y']};{$this->cursor_position_input[0]['x']}H";

        $read = new \Hoa\Console\Readline\Readline();

        $read->setAutocompleter(new \Hoa\Console\Readline\Autocompleter\Word(
                get_defined_functions()['internal']
        ));


        $read->setAutocompleter(new \Hoa\Console\Readline\Autocompleter\Word(
                get_defined_functions()['internal']
        ));


        $color = Color::setColor("grey","red");
        
       // $line = $read->readLine($color);

        $line = fgets(STDIN);
          echo "\033[0m";
          echo "\033[H";

          for ($i = 0; $i < $this->max_height; $i++) {
          echo Color::getColoredString(str_repeat(" ", $this->max_width), $this->color_shadow, $this->color_shadow);
          echo PHP_EOL;
          }


          echo "\033[H";
         * *
         */

        return $value;
    }

    public function windows($title, $msg)
    {
        if (strpos($title, "\n")) {
            throw new Exception("GLI-020 : The title must be on one line", 20);
        }

        $this->title_length = mb_strlen($title, "utf-8");

        //$this->msg = wordwrap($msg, $this->max_default, "\n", true);

        $this->msg = $msg;


        $lines = explode("\n", $this->msg);
        $this->msg_height = count($lines);


        foreach ($lines as $line) {
            $len = mb_strlen($line, "utf-8");

            if ($len > $this->msg_width) {
                $this->msg_width = $len;
            }
        }

        $this->msg_width += 6;

        $this->borderTop($title);

        $i = 0;

        foreach ($lines as $line) {
            $this->borderLeft();

            if ($line === "[[INPUT]]") {

                $cursor = array();

                $cursor['x'] = ($this->max_width - $this->msg_width) / 2 + 4;
                $cursor['y'] = ceil(($this->max_height - $this->msg_height) / 2) + $i;

                $this->cursor_position_input[] = $cursor;

                $this->windows .= Color::getColoredString(str_pad(" ", $this->msg_width - 6), $this->color_foreground_msg, $this->color_cursor);
                //$this->position_input = $i;
            } else {

                $this->windows .= Color::getColoredString(String::str_pad_unicode($line, Color::strip($this->msg_width) - 6), $this->color_foreground_msg, $this->color_window);

                //$this->windows .= Color::getColoredString(str_pad($line, mb_strlen(Color::strip($this->msg_width) - 6)-strlen($this->msg_width) ), $this->color_foreground_msg, $this->color_window);
            }

            $this->borderRight();
            $i++;
        }

        $this->position_input2 = $i - $this->position_input;

        $this->borderBottom();


        debug($this);

        return $this->encapsulate();
    }

    private function borderTop($title)
    {
        $this->windows = Color::getColoredString(" ", $this->color_shadow, $this->color_background);
        $this->windows .= Color::getColoredString("┌", $this->color_shadow, $this->color_window);
        $this->windows .= Color::getColoredString(str_repeat("─", ($this->msg_width - $this->title_length) / 2 - 4), $this->color_shadow, $this->color_window);
        $this->windows .= Color::getColoredString("┤ ", $this->color_shadow, $this->color_window);
        $this->windows .= Color::getColoredString($title, $this->color_foreground_title, $this->color_window);
        $this->windows .= Color::getColoredString(" ├", $this->color_shadow, $this->color_window);
        $this->windows .= Color::getColoredString(str_repeat("─", ($this->msg_width - $this->title_length) / 2 - 4), $this->color_shadow, $this->color_window);
        $this->windows .= Color::getColoredString("┐", $this->color_shadow, $this->color_window);
        $this->windows .= Color::getColoredString(" ", $this->color_shadow, $this->color_background);
        $this->windows .= PHP_EOL;
    }

    private function borderLeft()
    {
        $this->windows .= Color::getColoredString(" ", $this->color_shadow, $this->color_background);
        $this->windows .= Color::getColoredString("│ ", $this->color_shadow, $this->color_window);
    }

    private function borderRight()
    {
        $this->windows .= Color::getColoredString(" │", $this->color_shadow, $this->color_window);
        $this->windows .= Color::getColoredString(" ", $this->color_shadow, $this->color_shadow);
        $this->windows .= PHP_EOL;
    }

    private function borderBottom()
    {
        $this->windows .= Color::getColoredString(" ", $this->color_shadow, $this->color_background);
        $this->windows .= Color::getColoredString("└", $this->color_shadow, $this->color_window);
        $this->windows .= Color::getColoredString(str_repeat("─", ($this->msg_width - 4)), $this->color_shadow, $this->color_window);
        $this->windows .= Color::getColoredString("┘", $this->color_shadow, $this->color_window);
        $this->windows .= Color::getColoredString(" ", $this->color_shadow, $this->color_shadow);
        $this->windows .= PHP_EOL;

        $this->windows .= Color::getColoredString("  ", $this->color_shadow, $this->color_background);
        $this->windows .= Color::getColoredString(str_repeat("─", ($this->msg_width - 2)), $this->color_shadow, $this->color_shadow);
    }

    private function encapsulate()
    {
        $out = "";
        $top = 1;


        debug($this);


        $lines = explode("\n", $this->windows);
        $before_after = ($this->max_height - count($lines) ) / 2;
        $this->after = $before_after;

        for ($i = 0; $i < floor($before_after); $i++) {
            $out .= Color::getColoredString(str_pad($this->cursor_position_vertical . ":" . $top, 5), "grey", $this->color_background);
            $out .= Color::getColoredString(str_repeat(" ", $this->max_width - 5), $this->color_shadow, $this->color_background);
            $out .= $this->eol();

            $top++;
        }

        $before = ($this->max_width - $this->msg_width) / 2;
        $this->before = $before;

        foreach ($lines as $line) {

            $out .= Color::getColoredString(str_pad($this->cursor_position_vertical . ":" . $top, 5), "grey", $this->color_background);
            $out .= Color::getColoredString(str_repeat(" ", $before - 5), $this->color_shadow, $this->color_background);
            $out .= $line;
            $out .= Color::getColoredString(str_repeat(" ", ceil($before)), $this->color_shadow, $this->color_background);
            $out .= $this->eol();
            $top++;
        }


        for ($i = $this->cursor_position_vertical; $i <= $this->max_height; $i++) {


            //echo $i.":";
            $out .= Color::getColoredString(str_pad($this->cursor_position_vertical . ":" . $top . ":" . $i . ":" . $this->max_height, 11), "grey", $this->color_background);
            $out .= Color::getColoredString(str_repeat(" ", $this->max_width - 11), $this->color_shadow, $this->color_background);

            if ($i != $this->max_height) {
                $out .= $this->eol();
            }


            $top++;
        }

        return $out;

        //echo $this->cursor_position_vertical;
    }

    private function eol()
    {
        $this->cursor_position_vertical++;
        return PHP_EOL;
    }

}
