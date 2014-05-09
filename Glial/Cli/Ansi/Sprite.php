<?php

// save an image and ansi code

namespace Glial\Cli\Ansi;

use Glial\Cli\Color;
use Glial\String\String;

class Sprite
{

    private $height = 0;
    private $width = 0;
    private $sprite = array();

    public function __construct($sprite)
    {
        $lines = explode("\n", $sprite);

        foreach ($lines as $line) {
            $len = mb_strlen(Color::strip($line), "utf8");

            if ($len > $this->width) {
                $this->width = $len;
            }
        }

        foreach ($lines as $line) {

            $this->sprite[] = String::str_pad_unicode($line, $this->width );
            $this->height++;
        }
        
        return $this;
    }
    
    public function getSprite()
    {
        
        foreach ($this->sprite as $line)
        {
            yield $line;
        }
    }
    
    public  function getHeight()
    {
        return $this->height;
    }

    
    public  function getWidth()
    {
        return $this->width;
    }

}
