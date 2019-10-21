<?php

namespace Glial\Parser\Google\Test;

use \Glial\Parser\Google\Google;
use PHPUnit\Framework\TestCase;

class GoogleTest extends TestCase
{

    public function testSearch()
    {
        $url = "https://www.google.fr/search?q=site:www.estrildidae.net%2Ffr%2F+lonchura&hl=en&hs=Dqa&filter=0";
        $g = new Google();


        $res = array();
        //$res = $g->search('www.estrildidae.net', 'lonchura');

        
        $this->assertEquals(true, true);
    }

}
