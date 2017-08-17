<?php

namespace Glial\Sgbd;

use \Glial\Cli\Table;
use \Glial\Sgbd\Sql\FactorySql;

class Sgbd
{
    private $db     = array();
    private $config = array();
    private $logger;

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
     * @version 3.0
     */
    function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
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
     * @example echo $this->di['db']->sql('defaul');
     * @package Sgbd
     * @since 3.0 First time this was introduced.
     * @version 3.0
     */
    public function sql($name)
    {

        if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name)) {
            throw new \Exception("GLI-025 : The name of identifier is invalid : '".$name."' (only letter / number and underscore are allowed) !",
            50);
        }

        if (array_key_exists($name, $this->config)) {

            if (empty($this->db[$name]) || $this->db[$name]->is_connected === false) {

                FactorySql::setLogger($this->logger);
                $this->db[$name] = FactorySql::connect($name, $this->config[$name]);
            }

            return $this->db[$name];
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
     * @version 3.0
     */
    public function getAll()
    {
        return array_keys($this->config);
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
     * @example echo $this->di['db']->sql(DB_DEFAULT);
     * @package Sgbd
     * @See Also Glial\Cli\Table
     * @since 3.0 First time this was introduced.
     * @version 3.0
     */
    public function __toString()
    {
        $tab = new Table(1);
        $tab->addHeader(array("Id", "Name", "Is connected ?", "Driver", "IP", "Port", "User", "Password"));

        $i = 1;
        foreach ($this->config as $name => $param) {
            $port        = (empty($param['port'])) ? "3306" : $param['port'];
            $isconnected = (empty($this->db[$name])) ? "" : "■";

            $tab->addLine(array((string) $i, $name, $isconnected, $param['driver'], $param['hostname'], $port, $param['user'], str_repeat("*",
                    strlen($param['password']))));
            $i++;
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
     * @example $this->di['db']->getParam('default');
     * @package Sgbd
     * @See Also sql
     * @since 3.0 First time this was introduced.
     * @version 3.0
     */
    public function getParam($db)
    {
        if (!empty($this->config[$db])) {
            return $this->config[$db];
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
     * @version 3.0
     */
    public function connectAll()
    {
        foreach ($this->config as $name => $config) {
            yield $name => $this->sql($name);
        }
    }

    public function setLogger(\Monolog\Logger $logger)
    {
        $this->logger = $logger;
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
     * @version 4.18
     */
    public function getConnected()
    {
        return array_keys($this->db);
    }
}