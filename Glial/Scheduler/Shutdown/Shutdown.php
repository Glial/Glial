<?php

/**
 * Class ShutdownScheduler
 *
 * A lot of useful services may be delegated to this useful trigger.
 * It is very effective because it is executed at the end of the script but before any object destruction,
 * so all instantiations are still alive.
 * Here's a simple shutdown events manager class which allows to manage
 * either functions or static/dynamic methods, with an indefinite number of arguments availing on a internal handling
 * through call_user_func_array() specific functions.
 */

namespace Glial\Scheduler\Shutdown;

use Glial\Scheduler\Shutdown\Callback;

class Shutdown
{

    /**
     * Array to store user callbacks.
     *
     * @var Callback[]
     */
    private $callbacks;

    /**
     * @var null | ShutdownScheduler
     */
    private static $instance = null;

    /**
     * The constructor is only accessible through the getInstance method.
     */
    private function __construct()
    {
        $this->callbacks = array();
        $arguments = array(
            $this,
            'callRegisteredShutdown'
        );
        register_shutdown_function($arguments);
    }

    /**
     * Get a single instance.
     *
     * @return ShutdownScheduler|null
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a class call to handle on shutdown.
     */
    public function registerClass($class, $methodName, array $methodParams = array(), array $constructorParams = array())
    {
        $callback = new Callback();
        $callback->setType(Callback::TYPE_INSTANCE_CALL);
        $callback->setCallableObject($class);
        $callback->setMethodName($methodName);
        $callback->setConstructorArguments($constructorParams);
        $callback->setMethodArguments($methodParams);
        $this->addCallback($callback);
    }

    /**
     * Register a static class call to handle on shutdown.
     */
    public function registerStaticClassOnTime($className, $methodName, array $methodParams = array())
    {
        $callback = new Callback();
        $callback->setType(Callback::TYPE_STATIC_CALL);
        $callback->setMethodName($methodName);
        $callback->setTimeLimit(Callback::ON_TIME_LIMIT);
        $callback->setCallableObject($className);
        $callback->setMethodArguments($methodParams);
        $this->addCallback($callback);
    }

    /**
     * Register a static class call to handle on shutdown.
     */
    public function registerStaticClass($className, $methodName, array $methodParams = array())
    {
        $callback = new Callback();
        $callback->setType(Callback::TYPE_STATIC_CALL);
        $callback->setMethodName($methodName);
        $callback->setCallableObject($className);
        $callback->setMethodArguments($methodParams);
        $this->addCallback($callback);
    }

    /**
     * Register a global function to handle on shutdown.
     */
    public function registerWrapper($methodName, array $methodParams = array())
    {
        $callback = new Callback();
        $callback->setType(Callback::TYPE_WRAPPER_CALL);
        $callback->setMethodName($methodName);
        $callback->setMethodArguments($methodParams);
        $this->addCallback($callback);
    }

    /**
     * Adds a callback unique.
     */
    private function addCallback(Callback $callback)
    {
        $hash = md5(json_encode($callback));
        $this->callbacks[$hash] = $callback;
    }

    /**
     * Processing method on shutdown. Executes all callbacks.
     */
    public function callRegisteredShutdown()
    {
        foreach ($this->callbacks as $callback) {


            $time_limit_execed = "0";
            $error = error_get_last();
            if (($error['type'] === E_ERROR) || ($error['type'] === E_USER_ERROR)) {
                
                if (strpos($error['message'], "Maximum") !== false) {
                    $time_limit_execed = 1;
                }
            }
            
            if ($time_limit_execed === 1 || !$callback->isTimeLimit()) {
                switch (true) {
                    case $callback->isStaticCall():
                        call_user_func_array($callback->getObjectName() . "::" . $callback->getMethodName(), $callback->getMethodArguments());
                        break;
                    case $callback->isWrapper():
                        call_user_func_array($callback->getMethodName(), $callback->getMethodArguments());
                        break;
                    case $callback->isInstanceCall():
                        $reflectionClass = new \ReflectionClass($callback->getObjectName());
                        if (null !== $reflectionClass->getConstructor()) {
                            $realClass = $reflectionClass->newInstanceArgs($callback->getConstructorArguments());
                        } else {
                            $realClass = $reflectionClass->newInstance();
                        }
                        $callableData = array(
                            $realClass,
                            $callback->getMethodName()
                        );
                        call_user_func_array($callableData, $callback->getMethodArguments());
                        break;
                }
            }
        }
    }

}

/*
 * 
 * 
example
<?php
function wrapperCall($a, $b) {
	echo 'Wrapper: ' . $a . '+' . $b;
}
class TestClass {
	public function __construct($c, $d) {
		$this->c = $c;
		$this->d = $d;
	}
	public function instanceCall($a, $b) {
		echo 'Instance-Call: ' . $a . '+' . $b . '+' . $this->c . '+' . $this->d;
	}
	public static function staticCall($a, $b) {
		echo 'Static-Call: ' . $a . '+' . $b;
	}
}
$a = '0';
$b = '1';
$c = '2';
$d = '3';
ShutdownScheduler::getInstance()->registerClass('TestClass', 'instanceCall', array($a, $b), array($c, $d));
ShutdownScheduler::getInstance()->registerStaticClass('TestClass', 'staticCall', array($a, $b));
ShutdownScheduler::getInstance()->registerWrapper('wrapperCall', array($a, $b));
ShutdownScheduler::getInstance()->registerWrapper('session_write_close');
 */