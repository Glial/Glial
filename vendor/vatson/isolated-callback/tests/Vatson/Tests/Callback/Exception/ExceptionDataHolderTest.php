<?php

namespace Vatson\Tests\Callback\Exception;

use Vatson\Callback\Exception\ExceptionDataHolder;

/**
 * @author Vadim Tyukov <brainreflex@gmail.com>
 * @since 9/27/12
 */
class ExceptionDataHolderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeConstructedWithException()
    {
        new ExceptionDataHolder(new \Exception());
    }

    /**
     * @test
     */
    public function shouldAllowToGetExceptionMessage()
    {
        $message = 'Message';
        $holder = new ExceptionDataHolder(new \Exception($message));

        $this->assertEquals($message, $holder->getMessage());
    }

    /**
     * @test
     */
    public function shouldAllowToGetExceptionCode()
    {
        $code = rand(1,100);
        $holder = new ExceptionDataHolder(new \Exception('', $code));

        $this->assertEquals($code, $holder->getCode());
    }

    /**
     * @test
     */
    public function shouldAllowToGetExceptionLine()
    {
        $exception = new \Exception();
        $holder = new ExceptionDataHolder($exception);

        $this->assertEquals($exception->getLine(), $holder->getLine());
    }

    /**
     * @test
     */
    public function shouldAllowToGetExceptionFile()
    {
        $exception = new \Exception();
        $holder = new ExceptionDataHolder($exception);

        $this->assertEquals($exception->getFile(), $holder->getFile());
    }

    /**
     * @test
     */
    public function shouldAllowToGetExceptionOriginalClass()
    {
        $exception = new \InvalidArgumentException();
        $holder = new ExceptionDataHolder($exception);

        $this->assertEquals(get_class($exception), $holder->getOriginalClass());
    }
}
