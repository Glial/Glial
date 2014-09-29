<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Form\test;

use Glial\Form\Upload;

class UploadTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Upload
     */
    protected $_object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $_FILES = array(
            'test' => array(
                'name' => 'test.jpg',
                'type' => 'image/jpeg',
                'size' => 542,
                'tmp_name' => __DIR__ . '/_files/source-test.jpg',
                'error' => 0
            )
        );

        $this->_object = new Upload(__DIR__ . '/_files/');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        unset($_FILES);
        unset($this->_object);

        if (file_exists(__DIR__ . '/_files/test.jpg')) {
            unlink(__DIR__ . '/_files/test.jpg');
        }
    }

    /**
     * @covers Upload::receive
     */
    public function testReceive()
    {
        $this->assertTrue($this->_object->receive('test'));
    }

    public function testReceiveWithUnknowFile()
    {
        $this->assertFalse($this->_object->receive('test2'));
    }

}
