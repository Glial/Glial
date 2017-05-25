<?php

namespace Vatson\Callback\Ipc;


interface IpcInterface
{
    /**
     * @return mixed
     */
    public function get();

    /**
     * @param mixed $data
     *
     * @return void
     */
    public function put($data);
}