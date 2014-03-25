<?php

namespace Glial\Sgbd\Sql\Mysql;

class MasterSlave {

    private $instance = array();

    /*
     * @since Glial 2.1.2
     * @return void
     * @parameters $instance array of object from db.config.ini.php
     */

    public function setInstance($instance) {
        $this->$instance = $instance;
    }

    public function cashMAsterSlave() {
        
    }

    public function __wakeup() {
        
    }

    public function __sleep() {
        
    }

    
    
    public function isMaster($instance)
    {
        $sql = "SHOW MASTER STATUS";
        
        $res = $instance->sql_query($sql);
        
        if ($instance->sql_num_rows($res) === 0)
        {
            return false;
        }
        elseif ($instance->sql_num_rows($res) !== 1)
        {
            throw new \Exception("GLI-011 : more than one line returned in SHOW MASTER STATUS");
        }
        
        return $instance->sql_fetch_array($res);

    }
    
    
    public function isSlave($instance)
    {
        $sql = "SHOW SLAVE STATUS";
        
        $res = $instance->sql_query($sql);
        
        if ($instance->sql_num_rows($res) === 0)
        {
            return false;
        }
        elseif ($instance->sql_num_rows($res) !== 1)
        {
            throw new \Exception("GLI-012 : more than one line returned in SHOW SLAVE STATUS");
        }
        
        return $instance->sql_fetch_array($res, MYSQLI_ASSOC);

    }
    
    
    
}
