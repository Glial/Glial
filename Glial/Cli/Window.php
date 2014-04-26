<?php

namespace Glial\Cli;

use Glial\Cli\Color;

class Window
{

    private $max_default = 80;
    private $max_width;
    private $max_height;
    private $title_length;
    private $msg_width;
    private $msg_height;
    private $msg;
    public $color_background = "blue";
    public $color_window = "grey";
    public $color_foreground_title = "red";
    public $color_foreground_msg = "black";
    public $color_shadow = "black";
    private $windows;

    public function __construct($title, $msg)
    {
        $value = '';

        $sizes = exec("stty size");


        $size = explode(" ", $sizes);

        $this->max_width = $size[1];
        $this->max_height = $size[0];


        echo $this->windows($title, $msg);

        return $value;
    }

    public function windows($title, $msg)
    {
        if (strpos($title, "\n")) {
            throw new Exception("GLI-020 : The title must be on one line", 20);
        }

        $this->title_length = mb_strlen($title, "utf-8");

        $this->msg = wordwrap($msg, $this->max_default, "\n", true);

        $lines = explode("\n", $this->msg);
        $this->msg_height = count($lines);


        foreach ($lines as $line) {
            $len = mb_strlen($line, "utf-8");

            if ($len > $this->msg_width) {
                $this->msg_width = $len;
            }
        }

        $this->msg_width += 6;
        //top
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


        foreach ($lines as $line) {
            $this->borderLeft();
            $this->windows .= Color::getColoredString(str_pad($line, $this->msg_width-6),$this->color_foreground_msg,$this->color_window);
            $this->borderRight();
        }

        return $this->windows;
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
        $this->windows = Color::getColoredString(" ", $this->color_shadow, $this->color_background);
        $this->windows .= Color::getColoredString("└", $this->color_shadow, $this->color_window);
    }

}
