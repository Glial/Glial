<?php

namespace Glial\Cli\Ansi;

class Point
{

    private $x;
    private $y;

    public function __construct($x, $y)
    {
        if (!is_int($x) || !is_int($y)) {
            throw new \InvalidArgumentException('GLI-022 : The coordonates (' . $x . ',' . $y . ') are not valide');
        }
        
        $this->x = $x;
        $this->y = $y;
    }
    
    public function getX(){
        return $this->x;
    }
    
    public function getY(){
        return $this->y;
    }
    

}
