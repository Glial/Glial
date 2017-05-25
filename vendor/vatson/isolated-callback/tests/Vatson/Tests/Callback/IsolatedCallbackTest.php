<?php

namespace Vatson\Tests\Callback;

use Vatson\Callback\Ipc\IpcInterface;
use Vatson\Callback\IsolatedCallback;
use Fumocker\Fumocker;

/**
 * @author Vadim Tyukov <brainreflex@gmail.com>
 * @since 9/26/12
 */
class IsolatedCallbackTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Fumocker
     */
    protected $fumocker;

    /**
     * @var string
     */
    protected $shared_memory_segment_stub = 'stub';

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->fumocker = new Fumocker();
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        $this->fumocker->cleanup();
    }

    /**
     * @test
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage You need to enable PCNTL
     */
    public function throwExceptionWhenPcntlIsDisabled()
    {
        $this->fumocker
            ->getMock('Vatson\Callback', 'function_exists')
            ->expects($this->once())
            ->method('function_exists')
            ->with('pcntl_fork')
            ->will($this->returnValue(false));

        new IsolatedCallback(function () {
        }, $this->createIpcMock());
    }

    /**
     * @test
     *
     * @dataProvider provideInvalidCallback
     *
     * @expectedException \InvalidArgumentException
     */
    public function throwExceptionWhenConstructWithInvalidCallback($invalid_callback)
    {
        $this->createIsolatedCallback($invalid_callback, $this->createIpcMock());
    }

    /**
     * @test
     *
     * @dataProvider provideValidCallback
     */
    public function shouldBeConstructedWithValidCallback($valid_callback)
    {
        $this->createIsolatedCallback($valid_callback, $this->createIpcMock());
    }

    /**
     * @test
     */
    public function shouldInvokeCallbackInForkAndPutResultInIpc()
    {
        $test = $this;
        $ipc = $this->createIpcMock();

        $callback = function () use ($ipc, $test) {
            $result = uniqid();
            $ipc->expects($test->once())
                ->method('put')
                ->with($result);
            return $result;
        };

        $isolated_callback = $this->createIsolatedCallback($callback, $ipc);
        $isolated_callback();
    }

    /**
     * @test
     */
    public function shouldInvokeCallbackWitArgsGivenThroughIsolatedCallback()
    {
        $test = $this;
        $ipc = $this->createIpcMock();
        $callback_argument = array(uniqid());

        $callback = function () use ($callback_argument, $ipc, $test) {
            $ipc->expects($test->once())
                ->method('put')
                ->with(array($callback_argument));
            return func_get_args();
        };

        $isolated_callback = $this->createIsolatedCallback($callback, $ipc);
        $isolated_callback($callback_argument);
    }

    /**
     * @return array
     */
    public static function provideValidCallback()
    {
        return array(
            array(function () {}),
            array(array(__CLASS__, 'provideValidCallback')),
            array(array(new self, 'provideInvalidCallback')),
            array('rand'),
        );
    }

    /**
     * @return array
     */
    public static function provideInvalidCallback()
    {
        return array(
            array(array(new \stdClass(), 'unknownMethod')),
            array(array('stdClass', 'unknownStaticMethod')),
            array('string'),
            array(false),
            array(1),
            array(1.0),
            array(null),
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createIpcMock()
    {
        return $this->getMock('Vatson\Callback\Ipc\IpcInterface');
    }

    /**
     * Helps to automate the passing extensions checks
     *
     * @param callable $callback
     * @param IpcInterface $ipc
     * @return IsolatedCallback
     */
    protected function createIsolatedCallback($callback, IpcInterface $ipc = null)
    {
        $this->fumocker
            ->getMock('Vatson\Callback', 'function_exists')
            ->expects($this->once())
            ->method('function_exists')
            ->with('pcntl_fork')
            ->will($this->returnValue(true));

        return new IsolatedCallback($callback, $ipc);
    }
}
