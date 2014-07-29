<?php

namespace Glial\Neuron\Controller\PmaCli\Test;

class TestPmaCli extends \PHPUnit_Framework_TestCase
{
    use \Glial\Neuron\Controller\PmaCli;

    public function testCheckTimeBehind()
    {
        
        

        $output = unserialize('a:2:{s:2:"up";a:1:{s:6:"update";a:1:{s:11:"time_behind";s:1:"0";}}s:4:"down";a:1:{s:6:"update";a:1:{s:11:"time_behind";s:1:"1";}}}');
        
        
        $data = $this->checkDelay($output);
        
        
        $this->assertEquals(3,$data['id_mysql_status']);
        
    }
}
