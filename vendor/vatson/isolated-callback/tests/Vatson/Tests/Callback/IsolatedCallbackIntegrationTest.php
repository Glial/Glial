<?php

namespace Vatson\Tests\Callback;

use Vatson\Callback\IsolatedCallback;
use Fumocker\Fumocker;

/**
 * @author Vadim Tyukov <brainreflex@gmail.com>
 * @since 9/27/12
 */
class IsolatedCallbackIntegrationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        if (!function_exists('shm_attach') || !function_exists('pcntl_fork')) {
            $this->markTestSkipped('Required extensions are disabled');
        }

        // Dirty trick to override the native functions in the next unit tests
        if (version_compare(PHP_VERSION, '5.4', '>')) {
            $fumocker = new Fumocker();
            $fumocker->getMock('Vatson\Callback', 'function_exists');
            $fumocker->getMock('Vatson\Callback', 'shm_attach');
            $fumocker->getMock('Vatson\Callback', 'shm_remove');
            $fumocker->cleanup();
        }
    }

    /**
     * @test
     */
    public function shouldReturnCallbackResult()
    {
        $callback = function () {
            return 'result!!!';
        };

        $isolatedCallback = new IsolatedCallback($callback);
        $this->assertEquals($callback(), $isolatedCallback());
    }

    /**
     * @test
     *
     * @depends shouldReturnCallbackResult
     */
    public function shouldInvokeCallbackInChildProcess()
    {
        $parent_pid = getmypid();
        $callback = function () {
            // returns the child's pid
            return getmypid();
        };

        $isolatedCallback = new IsolatedCallback($callback);
        $child_pid = $isolatedCallback();

        $this->assertGreaterThan(0, $parent_pid);
        $this->assertGreaterThan(0, $child_pid);
        $this->assertNotEquals($parent_pid, $child_pid);
    }

    /**
     * @test
     *
     * @depends shouldReturnCallbackResult
     */
    public function shouldPassArgumentsToCallback()
    {
        $arg1 = $arg2 = 1;
        $callback = function ($arg1, $arg2) {
            return $arg1 + $arg2;
        };

        $isolatedCallback = new IsolatedCallback($callback);

        $this->assertEquals($callback($arg1, $arg2), $isolatedCallback($arg1, $arg2));
    }

    /**
     * @test
     *
     * @expectedException \Vatson\Callback\Exception\IsolatedCallbackExecutionException
     * @expectedExceptionMessage The exception should be caught and wrapped
     * @expectedExceptionCode 100
     */
    public function rethrowExceptionWhenExceptionWasThrownInChild()
    {
        $callback = function () {
            throw new \Exception('The exception should be caught and wrapped', 100);
        };

        $isolatedCallback = new IsolatedCallback($callback);
        $isolatedCallback();
    }

    /**
     * @test
     *
     * @expectedException \Vatson\Callback\Exception\IsolatedCallbackExecutionException
     * @expectedExceptionMessage Call to undefined method stdClass::method()
     */
    public function throwExceptionWhenErrorOccursInChild()
    {
        $callback = function () {
            @\stdClass::method();
        };

        $isolatedCallback = new IsolatedCallback($callback);
        $isolatedCallback();
    }

    /**
     * @test
     */
    public function shouldIgnoreNonfatalErrorDuringCallbackExecution()
    {
        \PHPUnit_Framework_Error_Notice::$enabled = FALSE;

        $callback = function () {
            trigger_error('Notice!', E_USER_NOTICE);
            return 'The execution was not interrupted';
        };

        $isolatedCallback = new IsolatedCallback($callback);
        $this->assertEquals('The execution was not interrupted', $isolatedCallback());
    }
}
