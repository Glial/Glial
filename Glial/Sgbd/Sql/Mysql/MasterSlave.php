<?php

namespace Glial\Sgbd\Sql\Mysql;

class MasterSlave {

    private $instance;

    /*
     * @since Glial 2.1.2
     * @return void
     * @parameters $instance of object from db.config.ini.php
     */

    public function setInstance($instance) {
        $this->instance = $instance;
    }

    public function cashMAsterSlave() {
        
    }

    public function __wakeup() {
        
    }

    public function __sleep() {
        
    }

    /**
     * This method return an array of master status, is the server is not confgured as master return false
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param string name of connection
     * @return array all param of master status
     * @description if the connection exist return the instance else it create it 
     * @access public
     * @package Sgbd
     * @since 3.0a First time this was introduced.
     * @version 3.0.1a
     */
    public function isMaster() {
        $sql = "SHOW MASTER STATUS";

        $res = $this->instance->sql_query($sql);

        if ($this->instance->sql_num_rows($res) === 0) {
            return false;
        } elseif ($this->instance->sql_num_rows($res) !== 1) {
            throw new \Exception("GLI-011 : more than one line returned in SHOW MASTER STATUS");
        }

        return $this->instance->sql_fetch_array($res, MYSQLI_ASSOC);
    }

    /**
     * This method return an array of all slave thread, is the server is not confgured as slave return false
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param string name of connection
     * @return array all param of slave status for each thread
     * @description if the connection exist return the instance else it create it 
     * @access public
     * @example echo $this->di['db']->sql('defaul');
     * @package Sgbd
     * @since 3.0a First time this was introduced.
     * @version 3.0.1a
     */
    public function isSlave() {

        if (version_compare($this->getVersion(), 10, '>')) {
            $sql = "SHOW all SLAVES STATUS";
        } else {
            $sql = "SHOW SLAVE STATUS";
        }

        $res = $this->instance->sql_query($sql);

        if ($this->instance->sql_num_rows($res) === 0) {
            return false;
        } else {

            $tab_ret = array();
            while ($arr = $this->instance->sql_fetch_array($res, MYSQLI_ASSOC)) {
                $tab_ret[] = $arr;
            }
            return $tab_ret;
        }
    }

    public function getMasterStatus() {
        return isMaster($this->instance);
    }

    public function getSlaveStatus() {
        return isSlave($this->instance);
    }

    /**
     * This method return the number of mysql/mariadb/percona version
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param string name of connection
     * @return string
     * @description if the connection exist return the instance else it create it 
     * @access public
     * @package Sgbd/
     * @since 3.0a First time this was introduced.
     * @version 3.0.1a
     */
    
    public function getVersion() {
        $sql = "SHOW GLOBAL VARIABLES LIKE 'version'";
        $res = $this->instance->sql_query($sql);
        $data = $this->instance->sql_fetch_array($res, MYSQLI_ASSOC);

        if (strpos($data['Value'], "-")) {
            $version = strstr($data['Value'], '-', true);
        } else {
            $version = $data['Value'];
        }

        return $version;
    }

}
