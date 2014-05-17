<?php

//library to move cursor on screen

namespace Glial\Cli;



class Ansi
{

    use Ansi\Geometry;
    
    private $current_position_x = 1;
    private $current_position_y = 1;
    private $screen_size_x = 0;
    private $screen_size_y = 0;

    public function __construct()
    {
        $sizes = exec("stty size");
        $size = explode(" ", $sizes);

        $this->screen_size_x = $size[1];
        $this->screen_size_y = $size[0];
    }

    public function moveCursorTo($x, $y)
    {
        $this->checkCoordInput($x, $y);

        $this->current_position_x = $x;
        $this->current_position_y = $y;

        echo "\033[{$this->current_position_y};{$this->current_position_x}H";
    }

    public function checkCoordInput($x, $y)
    {
        if (!is_int($x) || !is_int($y)) {
            throw new \InvalidArgumentException('GLI-022 : The coordonates (' . $x . ',' . $y . ') are not valide');
        }

        if ($this->screen_size_x < $x || $x < 1) {
            throw new \OutOfRangeException('GLI-023 : The coordonate x(' . $x . ') are outside the screen');
        }

        if ($this->screen_size_x < $y || $y < 1) {
            throw new \OutOfRangeException('GLI-023 : The coordonate y(' . $y . ') are outside the screen');
        }
    }

    public function printSprite($sprite, $x, $y)
    {
        $this->moveCursorTo($x, $y);

        $i = 0;
        foreach ($sprite->getSprite() as $line) {
            echo $line;
            $this->moveCursorTo($x, $y + $i);

            $i++;
        }
    }

    public function clear()
    {
        $this->moveCursorTo(1,1);
        
        for ($i = 1; $i <= $this->screen_size_x; $i++) {
            for ($j = 1; $j <= $this->screen_size_y; $j++) {
                echo " ";
            }

            echo "\n";
        }

        $this->moveCursorTo(1,1);
    }

}
