<?php

namespace Glial\Parser\Google\Test;

use \Glial\Parser\Google\Google;

class GoogleTest extends \PHPUnit_Framework_TestCase
{

    public function testSearch()
    {
        $url = "https://www.google.fr/search?q=site:www.estrildidae.net%2Ffr%2F+lonchura&hl=en&hs=Dqa&filter=0";
        $g = new Google();

        $res = $g->search('www.estrildidae.net', 'lonchura');
        //print_r($res);

        $nb_result = count($res);
        $this->assertEquals($nb_result, 10);
    }

}
