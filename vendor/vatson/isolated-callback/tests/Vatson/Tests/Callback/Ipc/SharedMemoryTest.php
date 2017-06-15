<?php

namespace Vatson\Tests\Callback\Ipc;

use Fumocker\Fumocker;
use Vatson\Callback\Ipc\SharedMemory;

class SharedMemoryTest extends \PHPUnit_Framework_TestCase
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
     */
    public function shouldImplementsIpcInterface()
    {
        $rc = new \ReflectionClass('Vatson\Callback\Ipc\SharedMemory');
        $this->assertTrue($rc->implementsInterface('Vatson\Callback\Ipc\IpcInterface'));
    }

    /**
     * @test
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage You need to enabled Shared Memory System V(see more "Semaphore")
     */
    public function throwExceptionWhenIPCIsDisabled()
    {
        $this->fumocker
            ->getMock('Vatson\Callback\Ipc', 'function_exists')
            ->expects($this->once())
            ->method('function_exists')
            ->with('shm_attach')
            ->will($this->returnValue(false));

        new SharedMemory();
    }

    /**
     * @test
     */
    public function shouldRemoveSharedMemorySegmentDuringDestruction()
    {
        $this->fumocker
            ->getMock('Vatson\Callback\Ipc', 'function_exists')
            ->expects($this->once())
            ->method('function_exists')
            ->with('shm_attach')
            ->will($this->returnValue(true));

        $this->fumocker
            ->getMock('Vatson\Callback\Ipc', 'shm_attach')
            ->expects($this->once())
            ->method('shm_attach')
            ->will($this->returnValue($this->shared_memory_segment_stub));

        $this->fumocker
            ->getMock('Vatson\Callback\Ipc', 'shm_remove')
            ->expects($this->once())
            ->method('shm_remove')
            ->with($this->shared_memory_segment_stub);

        $isolated_callback = new SharedMemory();
        unset($isolated_callback);
    }
}
 