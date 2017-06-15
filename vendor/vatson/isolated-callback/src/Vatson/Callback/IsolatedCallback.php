<?php

namespace Vatson\Callback;

use Vatson\Callback\Exception\ExceptionDataHolder;
use Vatson\Callback\Exception\IsolatedCallbackExecutionException;
use Vatson\Callback\Ipc\SharedMemory;
use Vatson\Callback\Ipc\IpcInterface;

/**
 * @author Vadim Tyukov <brainreflex@gmail.com>
 * @since 9/26/12
 */
class IsolatedCallback
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var IpcInterface
     */
    protected $ipc;

    /**
     * @param $callback
     * @param IpcInterface $ipc
     */
    public function __construct($callback, IpcInterface $ipc = null)
    {
        if (!function_exists('pcntl_fork')) {
            throw new \RuntimeException('You need to enable PCNTL');
        }

        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Given callback is not callable');
        }

        $this->callback = $callback;
        $this->ipc = $ipc ? : new SharedMemory();
    }

    /**
     * Calls a callback in a separate fork and returns the received result
     *
     * @throws \RuntimeException when fork can not be created
     *
     * @return mixed
     */
    public function __invoke()
    {
        $arguments = func_get_args();

        switch ($pid = pcntl_fork()) {
            case -1:
                throw new \RuntimeException();
            case 0:
                $this->registerChildShutdown();
                $this->handleChildProcess($arguments);
                exit;
            default:
                return $this->handleParentProcess();
        }
    }

    /**
     * Avoids the closing of resources in child process
     */
    protected function registerChildShutdown()
    {
        $ipc = $this->ipc;

        register_shutdown_function(function () use ($ipc) {
            $error = error_get_last();
            if ($error && isset($error['type']) && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
                $ipc->put(new ExceptionDataHolder(
                    new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line'])
                ));
            }
        });

        register_shutdown_function(function () {
            posix_kill(getmypid(), SIGKILL);
        });
    }

    /**
     * @throws \Exception when child process ends with an Exception
     *
     * @return mixed
     */
    protected function handleParentProcess()
    {
        pcntl_wait($status);
        $result = $this->ipc->get();

        if ($result instanceof ExceptionDataHolder) {
            throw new IsolatedCallbackExecutionException($result);
        }

        return $result;
    }

    /**
     * @param array $arguments
     */
    protected function handleChildProcess(array $arguments)
    {
        $result = null;

        try {
            $result = call_user_func_array($this->callback, $arguments);
        } catch (\Exception $e) {
            $result = new ExceptionDataHolder($e);
        }

        $this->ipc->put($result);
    }
}
