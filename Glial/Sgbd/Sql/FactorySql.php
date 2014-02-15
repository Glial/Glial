<?php

namespace Glial\Sgbd\Sql;


/*
 * @since Glial 2.1
 * @description connect to each database present in db.config.php
 * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
 */




class FactorySql
{
    private static $driver = array("mysql", "mysqli", "pdo", "oracle", "sybase");
    private static $db = array();

    static function init($data)
    {
        foreach ($data as $name => $param) {

            self::connect($name, $param);
        }

        return self::$db;
        
        /*
        if (count(self::$db) === 1) {
            return array_shift(array_values(self::$db));
        } else {
            return self::$db;
        }*/
    }
/*
 * @since Glial 2.1
 * @description connect to each database present in db.config.php
 * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
 * 
 */
    static function connect($name, $elem)
    {
        if (!in_array($elem['driver'], self::$driver)) {
            trigger_error("This driver isn't supported : " . $elem['driver'], E_USER_ERROR);
        }

        $driver = '\Glial\Sgbd\Sql\\'.ucwords(strtolower($elem['driver'])).'\\'.ucwords(strtolower($elem['driver']));
        
        self::$db[$name] = new $driver($name);
        self::$db[$name]->sql_connect($elem['hostname'], $elem['user'], $elem['password']);
        self::$db[$name]->sql_select_db($elem['database']);
    }

}
