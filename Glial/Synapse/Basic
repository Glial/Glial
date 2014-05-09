<?php

namespace Glial\Synapse;


class Basic
{

  static public function from()
  {
    

    $calledFrom = debug_backtrace();
    $var = explode(DS, substr(str_replace(ROOT, '', $calledFrom[1]['file']), 1));
    return( strtolower(end($var)));

  
  }



}
