<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Neuron\PmaCli\Test;

class Test2PmaCli extends \PHPUnit_Framework_TestCase
{

    const TIME_BEHING_MAX = 1;
    
    use \Glial\Neuron\PmaCli\PmaCli;

    public function testCheckDelay()
    {
        
        //out of date
        
        $data = json_decode('{"up":{"update":{"time_behind":"0"}},"down":{"update":{"time_behind":"16"}}}', true);
        $ret = $this->checkDelay($data);
        $this->assertEquals(5,$ret['id_mysql_status']);
        
        
        $data = json_decode('{"up":{"update":{"time_behind":"0"}},"down":{"update":{"time_behind":"1"}}}', true);
        $ret = $this->checkDelay($data);
        $this->assertEquals(5,$ret['id_mysql_status']);
        
        
        //up to date
        $data = json_decode('{"up":{"update":{"time_behind":"16"}},"down":{"update":{"time_behind":"0"}}}', true);
        $ret = $this->checkDelay($data);
        $this->assertEquals(1,$ret['id_mysql_status']);
        $data = json_decode('{"up":{"update":{"time_behind":"1"}},"down":{"update":{"time_behind":"0"}}}', true);
        $ret = $this->checkDelay($data);
        $this->assertEquals(1,$ret['id_mysql_status']);
        
          //increase
        $data = json_decode('{"up":{"update":{"time_behind":"16"}},"down":{"update":{"time_behind":"222"}}}', true);
        $ret = $this->checkDelay($data);
        $this->assertEquals(4,$ret['id_mysql_status']);
        
        // decrease
        $data = json_decode('{"up":{"update":{"time_behind":"222"}},"down":{"update":{"time_behind":"16"}}}', true);
        $ret = $this->checkDelay($data);
        $this->assertEquals(3,$ret['id_mysql_status']);
        
    }

}
