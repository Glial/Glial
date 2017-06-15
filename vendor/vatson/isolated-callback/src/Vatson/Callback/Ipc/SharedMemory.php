<?php

namespace Vatson\Callback\Ipc;


class SharedMemory implements IpcInterface
{
    /**
     * @var resource
     */
    protected $shared_memory_segment;

    /**
     * @var int
     */
    protected static $SEGMENT_VAR_ID = 1;

    public function __construct()
    {
        if (!function_exists('shm_attach')) {
            throw new \RuntimeException('You need to enabled Shared Memory System V(see more "Semaphore")');
        }

        $this->shared_memory_segment = shm_attach(time() + rand(1, 1000));
    }

    public function __destruct()
    {
        shm_remove($this->shared_memory_segment);
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        if (shm_has_var($this->shared_memory_segment, self::$SEGMENT_VAR_ID)) {
            $data = shm_get_var($this->shared_memory_segment, self::$SEGMENT_VAR_ID);
            shm_remove_var($this->shared_memory_segment, self::$SEGMENT_VAR_ID);

            return $data;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function put($data)
    {
        shm_put_var($this->shared_memory_segment, self::$SEGMENT_VAR_ID, $data);
    }
}
