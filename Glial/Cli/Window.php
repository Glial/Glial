<?php

namespace Glial\Cli;

use Glial\Cli\Color;

class Window {

    private $max_default = 140;
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
    private $position_input;
    private $position_input2;
    private $before;
    private $after;

    public function __construct($title, $msg) {

        $value = '';
        $sizes = exec("stty size");


        $size = explode(" ", $sizes);

        $this->max_width = $size[1];
        $this->max_height = $size[0];
//$this->max_height--;
        //echo "sizes : " . $sizes . PHP_EOL;

        echo $this->windows($title, $msg);


        echo "\033[" . ($this->after + $this->position_input2 ) . "A";
        echo "\033[" . ceil($this->before) . "C";
        echo "\033[37;44m";

//$value = trim(fgets(STDIN));
        //system("stty -icanon");
        while (true) {

            echo "\033[s";
            $c = fread(STDIN, 1);

            echo "\033[u";
            echo "      ";
            echo "\033[6D";


            if ($c === "\n") {
                break;
            }

            if (preg_match('/^[a-z0-9\.\$]$/i', $c) && strlen($c) === 1) {
                echo $c;
            } else {
                echo "refused: -" . $c . "-";
            }
        }




        echo "\033[0m";
        echo "\033[H";

        for ($i = 0; $i < $this->max_height; $i++) {
            echo Color::getColoredString(str_repeat(" ", $this->max_width), $this->color_shadow, $this->color_shadow);
            echo PHP_EOL;
        }


        echo "\033[H";

        return $value;
    }

    public function windows($title, $msg) {
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


        $i = 0;
        
        
        debug($lines);
        
        foreach ($lines as $line) {
            $this->borderLeft();

            if ($line === "[[INPUT]]") {
                $this->windows .= Color::getColoredString(str_pad(" ", strlen($this->msg_width) - mb_strlen(Color::strip($this->msg_width) - 6)), $this->color_foreground_msg, $this->color_background);
                $this->position_input = $i;
            } else {
                echo (strlen($this->msg_width) - mb_strlen(Color::strip($this->msg_width)) - 6).PHP_EOL;
                $this->windows .= Color::getColoredString(str_pad($line, strlen($this->msg_width) - mb_strlen(Color::strip($this->msg_width) - 6)), $this->color_foreground_msg, $this->color_window);
            }

            $this->borderRight();
            $i++;
        }

        $this->position_input2 = $i - $this->position_input;

        $this->borderBottom();

        return $this->encapsulate();
    }

    private function borderLeft() {
        $this->windows .= Color::getColoredString(" ", $this->color_shadow, $this->color_background);
        $this->windows .= Color::getColoredString("│ ", $this->color_shadow, $this->color_window);
    }

    private function borderRight() {
        $this->windows .= Color::getColoredString(" │", $this->color_shadow, $this->color_window);
        $this->windows .= Color::getColoredString(" ", $this->color_shadow, $this->color_shadow);
        $this->windows .= PHP_EOL;
    }

    private function borderBottom() {
        $this->windows .= Color::getColoredString(" ", $this->color_shadow, $this->color_background);
        $this->windows .= Color::getColoredString("└", $this->color_shadow, $this->color_window);
        $this->windows .= Color::getColoredString(str_repeat("─", ($this->msg_width - 4)), $this->color_shadow, $this->color_window);
        $this->windows .= Color::getColoredString("┘", $this->color_shadow, $this->color_window);
        $this->windows .= Color::getColoredString(" ", $this->color_shadow, $this->color_shadow);
        $this->windows .= PHP_EOL;

        $this->windows .= Color::getColoredString("  ", $this->color_shadow, $this->color_background);
        $this->windows .= Color::getColoredString(str_repeat("─", ($this->msg_width - 2)), $this->color_shadow, $this->color_shadow);
    }

    private function encapsulate() {
        $out = "";

        $lines = explode("\n", $this->windows);

        $before_after = ($this->max_height - count($lines) ) / 2;

        $this->after = $before_after;

        for ($i = 0; $i < floor($before_after); $i++) {
            $out .= Color::getColoredString(str_repeat(" ", $this->max_width), $this->color_shadow, $this->color_background);
            $out .= PHP_EOL;
        }


        $before = ($this->max_width - $this->msg_width) / 2;

        $this->before = $before;

        echo $before;
        
        foreach ($lines as $line) {
            $out .= Color::getColoredString(str_repeat(" ", $before), $this->color_shadow, $this->color_background);
            $out .= $line;
            $out .= Color::getColoredString(str_repeat(" ", ceil($before)), $this->color_shadow, $this->color_background);
            $out .= PHP_EOL;
        }



        for ($i = 0; $i < floor($before_after); $i++) {
            $out .= Color::getColoredString(str_repeat(" ", $this->max_width), $this->color_shadow, $this->color_background);
            $out .= PHP_EOL;
        }

        return $out;
    }

}
