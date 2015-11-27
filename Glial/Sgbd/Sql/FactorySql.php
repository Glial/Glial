<?php

namespace Glial\Sgbd\Sql;

/*
 * @since Glial 2.1
 * @description connect to each database present in db.config.php
 * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
 */

class FactorySql {

    private static $driver = array("pgsql", "mysql", "pdo", "oracle", "sybase");
    private static $db = array();
    private static $logger;

    /*
     * @since Glial 2.1
     * @description connect to each database present in db.config.php
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * 
     */

    static function connect($name, $elem) {
        if (!in_array($elem['driver'], self::$driver)) {
            throw new \Exception("GLI-023 : This driver isn't supported : " . $elem['driver']);
        }

        $driver = '\Glial\Sgbd\Sql\\' . ucwords(strtolower($elem['driver'])) . '\\' . ucwords(strtolower($elem['driver']));

        $addr = $elem['hostname'];
        $dbname = $elem['database'];

        if (!empty($elem['port']) && is_numeric($elem['port'])) {
            $port = $elem['port'];
        } else {
            $port = null;
        }

        self::$db[$name] = new $driver($name, $elem);

        if (!self::$db[$name]) {
            return false;
        }

        self::$db[$name]->setLogger(self::$logger);
        self::$db[$name]->sql_connect($addr, $elem['user'], $elem['password'], $dbname, $port);

        return self::$db[$name];
    }
    
    static function setLogger($logger)
    {
        self::$logger = $logger;
    }

}
