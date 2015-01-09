<?php

/**
 * Class ShutdownScheduler_Callback.
 *
 * The callback object contains how to execute the object.
 * from https://github.com/Insphare/ShutdownScheduler/blob/master/lib/ShutdownScheduler/Callback.php
 */

namespace Glial\Scheduler\Shutdown;

class Callback
{

    /**
     * When is a wrapper.
     */
    const TYPE_WRAPPER_CALL = 1;

    /**
     * When is a static call.
     */
    const TYPE_STATIC_CALL = 2;

    /**
     * When is a instance call.
     */
    const TYPE_INSTANCE_CALL = 3;

    
    
   /**
     * execute script only if time limit execed
     */
    const ON_TIME_LIMIT = 1;
    
       /**
     * execute script only if time limit execed
     */
    const ON_SHUTDOWN = 0;
    
    
    /**
     * @var null
     */
    private $type = null;

    /**
     * @var string
     */
    private $callableObject = '';

    /**
     * @var array
     */
    private $constructorArguments = array();

    /**
     * @var array
     */
    private $methodArguments = array();

    /**
     * @var string
     */
    private $methodName = '';

    
    private $timelimit = 0;
    /**
     * @param string $methodName
     */
    public function setMethodName($methodName)
    {
        $this->methodName = (string) $methodName;
    }

    /**
     * @return string
     */
    public function getMethodName()
    {
        return $this->methodName;
    }

    /**
     * @param array $constructorArguments
     */
    public function setConstructorArguments(array $constructorArguments)
    {
        $this->constructorArguments = $constructorArguments;
    }

    /**
     * @return array
     */
    public function getConstructorArguments()
    {
        return $this->constructorArguments;
    }

    /**
     * @param array $methodArguments
     */
    public function setMethodArguments(array $methodArguments)
    {
        $this->methodArguments = $methodArguments;
    }

    /**
     * @return array
     */
    public function getMethodArguments()
    {
        return $this->methodArguments;
    }

    /**
     * @param callable|string $callableObject
     */
    public function setCallableObject($callableObject)
    {
        $this->callableObject = $callableObject;
    }

    /**
     * @return callable|string
     */
    public function getObjectName()
    {
        return $this->callableObject;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = (int) $type;
    }

    /**
     * @return bool
     */
    public function isStaticCall()
    {
        return self::TYPE_STATIC_CALL === $this->type;
    }

    /**
     * @return bool
     */
    public function isWrapper()
    {
        return self::TYPE_WRAPPER_CALL === $this->type;
    }

    /**
     * @return bool
     */
    public function isInstanceCall()
    {
        return self::TYPE_INSTANCE_CALL === $this->type;
    }
    
    /**
     * @param int $timelimit
     */
    public function setTimeLimit($timelimit)
    {
        $this->timelimit = (int) $timelimit;
    }
    
    
        /**
     * @return bool
     */
    public function isTimeLimit()
    {
        return self::ON_TIME_LIMIT === $this->timelimit;
    }
    

}
