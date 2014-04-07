<?php

namespace Glial\Sgbd\Sql\Mysql;

class Tools {

    private $instance = array();

    /*
     * @since Glial 2.1.2
     * @return void
     * @parameters $instance array of object from db.config.ini.php
     */

    public function setInstance($instance) {
        $this->$instance = $instance;
    }


    public function __wakeup() {
        
    }

    public function __sleep() {
        
    }
        
    public function getVersion($instance)
    {
        $sql = "SHOW GLOBAL VARIABLES LIKE 'version'";
        
        $res = $instance->sql_query($sql);
        
        if ($instance->sql_num_rows($res) === 0)
        {
            return false;
        }
        elseif ($instance->sql_num_rows($res) !== 1)
        {
            throw new \Exception("GLI-011 : more than one line returned in SHOW MASTER STATUS");
        }
        
        return $instance->sql_fetch_array($res, MYSQLI_ASSOC);

    }
    
    
}
    