<?php

namespace Glial\Neuron\Controller;

use Glial\Cli\Table;
use Glial\Cli\Window;

trait PmaCli {

    public function load($param) {

        $this->view = false;
        
        $db = $this->di['db']->sql('default');
        
        $server_dest = $param[0];
        $databases = explode(",", $param[1]);
        
        $dump =array();
        
        foreach($databases as $database)
        {
            $sql = "SELECT a.id,b.`name`,b.ip,b.port,a.date_start, a.`time`, a.`database` FROM mysql_dump a"
                    . " inner join mysql_server b ON a.id_mysql_server = b.id"
                    . " WHERE `database` ='".$db->sql_real_escape_string($database)."'"
                    . " order by a.id";
            $res = $db->sql_query($sql);
            
            $tab = new Table(1);
            
            $tab->addHeader(array("ID","name","IP", "port","date_start", "time","database" ));
            
            while($ob = $db->sql_fetch_object($res))
            {
                $tab->addLine(array($ob->id, $ob->name, $ob->ip, $ob->port, $ob->date_start, $ob->time, $ob->database));
            }
            
            $msg = $tab->display();
            
            
            $msg .= "Select the dump (id) to restore on '".$server_dest."' ? \n";
            $msg .= "[[INPUT]]\n";
            
            new Window("Load database", $msg);
            
            
            
        }
        
        
        
    }

}
