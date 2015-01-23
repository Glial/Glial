<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Neuron\PmaCli;

trait PmaCliFailOver
{

    public $connection_name = array('iways_node1', 'iways_node2', 'iways_node3');

    public function checkReplication()
    {
        $this->view = false;

        $db = $this->di['db']->sql("iways_db_node_sa_01");

        $default = $this->di['db']->sql(DB_DEFAULT);


        $sql = "SELECT b.id,b.`ip`,b.name,b.port FROM mysql_cluster a
          INNER JOIN mysql_server b ON b.id = a.id_mysql_server WHERE a.id=1";

        $res = $default->sql_query($sql);
        while ($ob = $default->sql_fetch_object($res)) {

            $current_slave = $ob;
        }

        debug($current_slave);

        $sql = "SHOW ALL SLAVES STATUS";
        $res = $db->sql_query($sql);


        $slave = array();

        while ($ob = $db->sql_fetch_object($res)) {

            if (in_array($ob->Connection_name, $this->connection_name)) {
                $slave[$ob->Connection_name] = $ob;
            }

            if ($ob->Master_Host === $current_slave->ip && $ob->Master_Port === $current_slave->port) {
                $current_thread = $ob->Connection_name;
            }
        }


        if (trim($slave[$current_thread]->Slave_IO_Running) == "Yes" && trim($slave[$current_thread]->Slave_SQL_Running) == "Yes") {
            echo "TRHEAD OK \n";
        } else {

            echo "change slave to next node galera\n";

            $GTID = $slave[$current_thread]->Gtid_IO_Pos;

            
            
            // un hook to remove
            if (empty($GTID))
            {
                foreach($slave as $key => $slv)
                {
                    if (!empty($slv->Gtid_IO_Pos))
                    {
                        echo "[WARNING] désyncronisation du serveur courrant [$key au lieu de $current_thread] ?\n";
                        
                        $current_thread = $key;
                        $GTID = $slv->Gtid_IO_Pos;
                        break;
                    }
                    
                }
            }
            
            
            
            if (empty($GTID))
            {
                throw new \Exception("GLI-066 : GTID empty on : ".$current_thread);
            }
            
            
            //stop all slave from the pool
            foreach($slave as $key => $slv)
            {
                $db->sql_query("STOP SLAVE '" . $key . "'");
            }
            

            
            
            $key_next_thread = array_search($current_thread, $this->connection_name);

            $key_next_thread++;

            if ($key_next_thread >= count($this->connection_name)) {
                $key_next_thread = 0;
            }

            
            
            $next_thread = $this->connection_name[$key_next_thread];
                    
            debug($next_thread);
                    
            //$db->sql_query("STOP SLAVE '" . $this->connection_name[$key] . "'");
            
            
            $db->sql_query("SET @@default_master_connection='" . $next_thread . "'");
            $db->sql_query("SET GLOBAL gtid_slave_pos = '" . $GTID . "';");
            $db->sql_query("START SLAVE '" . $next_thread . "'");


            $sql = "SELECT id FROM `mysql_server` WHERE ip='" . $slave[$next_thread]->Master_Host . "' AND port='" . $slave[$next_thread]->Master_Port . "'";

            $res = $default->sql_query($sql);

            if ($default->sql_num_rows($res) === "1") {

                $ob = $default->sql_fetch_object($res);

                $data = array();
                $data['mysql_cluster']['id'] = 1;
                $data['mysql_cluster']['id_mysql_server'] = $ob->id;

                $ret = $this->sql_save($data);
                
                if (! $ret)
                {
                    debug($data);
                    debug($default->sql_error());
                    
                    throw new \Exception("GLI-089 : Impossible to select the id server (".$slave[$next_thread]->Master_Host.":".$slave[$next_thread]->Master_Port.") corresponding to the thread : ".$this->connection_name[$next_thread]);
                }
            }
        }
    }

    public function ha()
    {
        while (true) {
            passthru("php /data/www/photobox/application/webroot/index.php pma_cli checkReplication");
            sleep(2);
        }
    }

}
