<?php

namespace glial\synapse;

class Singleton
{

	 static $instances = array();

	protected function __construct()
	{
		//Thou shalt not construct that which is unconstructable!
		trigger_error('Construct is not allowed.', E_USER_ERROR);
	}

	protected function __clone()
	{
		trigger_error('Clone is not allowed.', E_USER_ERROR);
		//Me not like clones! Me smash clones!
	}

	static function getInstance($class)
	{
		if ( !array_key_exists($class, self::$instances) )
		{
			self::$instances[$class] = new $class;
		}

		return self::$instances[$class];
	}
	
	static function getDefined()
	{
		echo "<pre>";
		print_r(array_keys (self::$instances));
		echo "</pre>";
	}

}

