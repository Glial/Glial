<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\I18n\Test;

use \Glial\I18n\I18n;
use \App\Library\Debug;

// a checker pour faire mieux
define("DB_DEFAULT", "wdfwfg");

use \PHPUnit\Framework\TestCase;

class TestI18n extends TestCase
{

    public function testGoogleTranslate()
    {
        I18n::SetDefault("fr");
        I18n::load("en");


        $this->assertEquals(true, true);
    }

    public function testGoogleTranslate2()
    {
        I18n::SetDefault("en");
        I18n::load("en");

        $this->assertEquals(true, true);


        I18n::SetDefault("fr");
        I18n::load("fr");


        $this->assertEquals(true, true);
    }
}