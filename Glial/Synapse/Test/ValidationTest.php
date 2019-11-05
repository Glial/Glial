<?php

namespace Glial\Synapse\Test;

use \Glial\Synapse\Validation;

use PHPUnit\Framework\TestCase;

class TestValidation extends TestCase
{
    
    
    public function testDate()
    {
        $val = new Validation('db');

        $date['value'] = '1983-06-16';
        $this->assertTrue($val->date($date));

    }
        
    public function testDateTime()
    {
        $val = new Validation('db');
        
        $dateTime['value'] = '1983-06-16 05:15:25';
        $this->assertTrue($val->dateTime($dateTime));

        $dateTime['value'] = date("c");
        $this->assertTrue($val->dateTime($dateTime));
    }
    
    
    public function testTime()
    {
        $val = new Validation('db');
        
        $time['value'] = '14:42:49+01:00';
        $this->assertTrue($val->time($time));

        $time['value'] = '14:42:49';
        $this->assertTrue($val->time($time));

        $time['value'] = '64:42:49';
        $this->assertFalse($val->time($time));
        
        $time['value'] = '04:60:49';
        $this->assertFalse($val->time($time));
        
        $time['value'] = '14:42:61';
        $this->assertFalse($val->time($time));
        
    }
}