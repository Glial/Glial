<?php

namespace Vatson\Tests\Callback\Exception;

use Vatson\Callback\Exception\IsolatedCallbackExecutionException;

/**
 * @author Vadim Tyukov <brainreflex@gmail.com>
 * @since 9/26/12
 */
class IsolatedCallbackExecutionExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeSubclassOfRuntimeException()
    {
        $rc = new \ReflectionClass('Vatson\Callback\Exception\IsolatedCallbackExecutionException');
        $this->assertTrue($rc->isSubclassOf('RuntimeException'));
    }

    /**
     * @test
     */
    public function shouldConstructedWithExceptionDataHolder()
    {
        $holder = $this->getMock('Vatson\Callback\Exception\ExceptionDataHolder', array(), array(), '', false);

        new IsolatedCallbackExecutionException($holder);
    }

    /**
     * @test
     */
    public function shouldAllowToGetCodeThatWasObtainedFromHolder()
    {
        $code = rand(0, 100);
        $holder = $this->getMock('Vatson\Callback\Exception\ExceptionDataHolder', array(), array(), '', false);
        $holder
            ->expects($this->once())
            ->method('getCode')
            ->will($this->returnValue($code))
        ;

        $exception = new IsolatedCallbackExecutionException($holder);
        $this->assertEquals($code, $exception->getCode());
    }

    /**
     * @test
     */
    public function shouldAllowToGetLineThatWasObtainedFromHolder()
    {
        $line = rand(0, 100);
        $holder = $this->getMock('Vatson\Callback\Exception\ExceptionDataHolder', array(), array(), '', false);
        $holder
            ->expects($this->once())
            ->method('getLine')
            ->will($this->returnValue($line))
        ;

        $exception = new IsolatedCallbackExecutionException($holder);
        $this->assertEquals($line, $exception->getLine());
    }

    /**
     * @test
     */
    public function shouldAllowToGetMessageThatWasObtainedFromHolder()
    {
        $message = 'Exception message';
        $holder = $this->getMock('Vatson\Callback\Exception\ExceptionDataHolder', array(), array(), '', false);

        $holder
            ->expects($this->once())
            ->method('getMessage')
            ->will($this->returnValue($message))
        ;

        $exception = new IsolatedCallbackExecutionException($holder);
        $this->assertEquals($message, $exception->getMessage());
    }
    /**
     * @test
     */
    public function shouldAllowToGetFileThatWasObtainedFromHolder()
    {
        $file = '/file.php';
        $holder = $this->getMock('Vatson\Callback\Exception\ExceptionDataHolder', array(), array(), '', false);

        $holder
            ->expects($this->once())
            ->method('getFile')
            ->will($this->returnValue($file))
        ;

        $exception = new IsolatedCallbackExecutionException($holder);

        $this->assertEquals($file, $exception->getFile());
    }
    /**
     * @test
     */
    public function shouldAllowToGetOriginalClassThatWasObtainedFromHolder()
    {
        $original_class = 'MyCustomExceptionThatYouCanCatch';
        $holder = $this->getMock('Vatson\Callback\Exception\ExceptionDataHolder', array(), array(), '', false);

        $holder
            ->expects($this->once())
            ->method('getOriginalClass')
            ->will($this->returnValue($original_class))
        ;

        $exception = new IsolatedCallbackExecutionException($holder);

        $this->assertEquals($original_class, $exception->getOriginalClass());
    }
}
