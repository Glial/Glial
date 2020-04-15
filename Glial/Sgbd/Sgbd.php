<?php

namespace Glial\Sgbd;

use \Glial\Cli\Table;
use \Glial\Sgbd\Sql\FactorySql;

class Sgbd
{
    static $db     = array();
    static $config = array();
    static $logger;
    static $number = 1;

    /**
     * Set the configuration to know which connection is available
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param array 
     * @return void
     * @description construct the object and set the connection available
     * @access public
     * @example new Sgbd(array from \Glial\Synapse\Config);
     * @package Sgbd
     * @See Glial\Sgbd\Sgbd->sql()
     * @since 3.0 First time this was introduced.
     * @since 5.1.5 Switched to static, update __construc to setConfig
     * @version 4.0
     */
    static public function setConfig($config)
    {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * This method return an child's objet of the class sql/nosql 
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param string name of connection
     * @return object of a child from class sql/nosql 
     * @description if the connection exist return the instance else it create it 
     * @access public
     * @example $db = Sgbd::sql('defaul'); $db = Sgbd::sql('defaul',2); $db = Sgbd::sql('defaul',3);
     * @package Sgbd
     * @since 3.0 First time this was introduced.
     * @since 5.1.5 Switched to static
     * @since 5.1.6 Added one more parameter optional, multiple connexion for same MySQL server (to prevent problem with current database)
     * @version 3.0
     */
    static public function sql($name, $num = '')
    {

        if (empty($num)) {
            $num = self::$number;
        }

        if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) {
            throw new \Exception("GLI-025 : The name of identifier is invalid : '".$name."' (only letter / number and underscore are allowed) !", 50);
        }

        if (array_key_exists($name, self::$config)) {

            if (empty(self::$db[$name][$num]) || self::$db[$name][$num]->is_connected === false) {

                FactorySql::setLogger(self::$logger);
                self::$db[$name][$num] = FactorySql::connect($name, self::$config[$name]);
            }

            return self::$db[$name][$num];
        } else {
            throw new \Exception("GLI-19 : This connection was not configured : '".$name."' !");
        }
    }

    /**
     * This method return an array of all name's connections defined
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param void
     * @return array name of connection
     * @description give a status o all databases 
     * @access public
     * @example echo $this->di['db']->getAll();
     * @package Sgbd
     * @See Glial\Sgbd\Sgbd->sql()
     * @since 3.0 First time this was introduced.
     * @since 5.1.5 Switched to static
     * @version 3.0
     */
    static public function getAll()
    {
        return array_keys(self::$config);
    }

    /**
     * This method return a string, printing a table with all connection configured and said which one is connected
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param void
     * @return string return a table with all main's information and tell us with one is connected
     * @description give a status o all databases 
     * @access public
     * @example echo Sgbd::sql(DB_DEFAULT);
     * @package Sgbd
     * @See Also Glial\Cli\Table
     * @since 3.0 First time this was introduced.
     * @since 5.1.5 Switched to static, renamme to toString
     * @version 3.0
     */
    static public function toString()
    {
        $tab = new Table(1);
        $tab->addHeader(array("Id", "Name", "Is connected ?", "Driver", "IP", "Port", "User", "Password"));

        $i = 1;
        foreach (self::$config as $name => $params) {

            foreach ($params as $num => $param) {
                $port        = (empty($param['port'])) ? "3306" : $param['port'];
                $isconnected = (empty(self::$db[$name])) ? "" : "■";

                $tab->addLine(array((string) $i, $name, $isconnected, $param['driver'], $param['hostname'], $port, $param['user'], str_repeat("*", strlen($param['password']))));
                $i++;
            }
        }

        return $tab->display();
    }

    /**
     * This method return the params of set in db.config.php
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param void
     * @return array with all options set i db.config.php (hostname / port / 
     * @description connect to all database in db.config.ini.php and return the object / dblink created
     * @access public
     * @example $this->di['db']->getParam(DB_DEFAULT); // DB_DEFAULT is string
     * @package Sgbd
     * @See Also sql
     * @since 3.0 First time this was introduced.
     * @since 5.1.5 Switched to static
     * @version 3.0
     */
    static public function getParam($db)
    {

        if (empty($num)) {
            $num = self::$number;
        }

        if (!empty(self::$config[$db])) {
            return self::$config[$db];
        } else {
            throw new \Exception("GLI-021 : Error this instances \"".$db."\" doesn't exit", 21);
        }
    }

    /**
     * This method is used to make an operation on all database (example create generic model)
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param void
     * @return object of the sgbd class (MySQL / PostGreSQL / Oracle / Sybase) 
     * @description connect to all database in db.config.ini.php and return the object / dblink created
     * @access public
     * @example foreach($this->di['db']->connectAll() as $db) {}
     * @package Sgbd
     * @See Also sql
     * @since 3.0 First time this was introduced.
     * @since 5.1.5 Switched to static
     * @version 3.0
     */
    static public function connectAll()
    {
        foreach (self::$config as $name => $config) {
            yield $name => $this->sql($name);
        }
    }

    static public function setLogger(\Monolog\Logger $logger)
    {
        self::$logger = $logger;
    }

    /**
     * This method is used to return all name of thread connected 
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param void
     * @return array with name of connections
     * @description This method is used to return all name of thread connected 
     * @access public
     * @example foreach($this->di['db']->getConnected() as $db) {}
     * @package Sgbd
     * @See Also sql
     * @since 4.1.8 First time this was introduced.
     * @since 5.1.5 Switched to static
     * @version 4.18
     */
    static public function getConnected()
    {
        return array_keys(self::$db);
    }

    static public function ifExit($name)
    {
        if (array_key_exists($name, self::$config)) {
            return true;
        } else {
            return false;
        }
    }
}