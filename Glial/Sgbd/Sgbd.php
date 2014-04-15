<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

// factory for any connection ? db / ssh / memecached etc...

//use Glial\Sgbd\Sql\FactorySql;


namespace Glial\Sgbd;

class Sgbd {

    private static $db = array();
    private static $config = array();

    //from  Glial\Synapse\Config
    static function init($config) {

        self::$config = array_merge(self::$config, $config);
    }

    public static function sql($name) {

        if (array_key_exists($name, self::$config)) {
            if (empty(self::$db[$name])) {
                
                
                debug($name);
                debug(self::$config[$name]);
                
                self::$db[$name] = \Glial\Sgbd\Sql\FactorySql::connect($name, self::$config[$name]);
                
                
                debug(self::$db[$name]);
            }

            return self::$db[$name];
        } else {
            throw new \Exception("GLI-19 : This connection was not configured : '" . $name . "' !");
        }
    }
    
    static public function getAll()
    {
        return array_key_exists(self::$config);
    }

}
