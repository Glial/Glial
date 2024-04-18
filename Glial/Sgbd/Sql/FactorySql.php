<?php


namespace Glial\Sgbd\Sql;

use \Glial\Security\Crypt\Crypt;

/*
 * @since Glial 2.1
 * @description connect to each database present in db.config.php
 * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
 */

class FactorySql
{
    private static $driver = array("pgsql", "mysql", "pdo", "oracle", "sybase");
    private static $db     = array();
    private static $logger;

    /*
     * @since Glial 2.1
     * @description connect to each database present in db.config.php
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * 
     */

    static function connect($name,$num, $elem)
    {
        if (!in_array($elem['driver'], self::$driver)) {
            throw new \Exception("GLI-023 : This driver isn't supported : '".$elem['driver']."' on connection name [".$name."]");
        }

        $driver = '\Glial\Sgbd\Sql\\'.ucwords(strtolower($elem['driver'])).'\\'.ucwords(strtolower($elem['driver']));

        $addr   = $elem['hostname'];
        $dbname = $elem['database'];

        if (!empty($elem['port']) && is_numeric($elem['port'])) {
            $port = $elem['port'];
        } else {
            $port = null;
        }

        self::$db[$name][$num] = new $driver($name, $elem);

        if (!self::$db[$name][$num]) {
            return false;
        }

        self::$db[$name][$num]->setLogger(self::$logger);

        if (!empty($elem['crypted']) && $elem['crypted'] === "1") {
            $elem['password'] = Crypt::decrypt($elem['password'],CRYPT_KEY);
        }

        self::$db[$name][$num]->sql_connect($addr, $elem['user'], $elem['password'], $dbname, $port);

        return self::$db[$name][$num];
    }

    static function setLogger($logger)
    {
        self::$logger = $logger;
    }
}