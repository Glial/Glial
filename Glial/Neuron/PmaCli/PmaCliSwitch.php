<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Neuron\PmaCli;

use \Glial\Sgbd\Sql\Mysql\MasterSlave;
use \Glial\Cli\Table;

trait PmaCliSwitch
{
    /*
     * 
     * glial pma_cli switchMaster NewSlave NewMaster
     */
    
    
    public function switchMaster($servers)
    {
        $this->view = false;

        $slave = $this->di['db']->sql(str_replace('-', '_', $servers[0]));
        $master = $this->di['db']->sql(str_replace('-', '_', $servers[1]));

// first test that the master have log_slave_updates = ON

        $log_slave_updates = $master->getVariables("log_slave_updates");

        if ($log_slave_updates !== 'ON') {
            throw new \Exception("[ERROR] PMACLI-001 : log_slave_updates must be unabled on new master : '" . $servers[1] . "'");
        }

        $MS = new MasterSlave();
        $MS->setInstance($slave);
        $thread_slave = $MS->isSlave();

        $slave_master_host = array();

        if ($thread_slave) {
            foreach ($thread_slave as $thread) {

                $conn = empty($thread['Connection_name']) ? '' : " '" . $thread['Connection_name'] . "'";


                $slave_master_host[$thread['Master_Host']] = $conn;
            }
        } else {
            throw new \Exception("[ERROR] PMACLI-002 : the server should configured as slave : '" . $servers[0] . "'");
        }

        $MS->setInstance($master);
        $thread_master = $MS->isSlave();

        $master_master_host = array();

        if ($thread_master) {
            foreach ($thread_master as $thread) {
                $conn = empty($thread['Connection_name']) ? '' : " '" . $thread['Connection_name'] . "'";
                $master_master_host[$thread['Master_Host']] = $conn;
            }
        } else {
            throw new \Exception("[ERROR] PMACLI-002 : the server should configured as slave : '" . $servers[1] . "'");
        }


        $cmp = array_intersect_key($slave_master_host, $master_master_host);

        if (count($cmp) !== 1) {
            throw new \Exception("[ERROR] PMACLI-003 : the servers must have the same master");
        }
        $ip_of_master = array_keys($cmp)[0];


//stop du slave 
        $sql = "STOP SLAVE" . $slave_master_host[$ip_of_master] . ";";
        echo $servers[0] . "> " . $sql . "\n";
        $slave->sql_query($sql);

        $MS->setInstance($slave);
        $thread_slave = $MS->isSlave();
        foreach ($thread_slave as $thread) {
            if ($thread['Master_Host'] == $ip_of_master) {
                $SLAVE_MASTER_LOG_FILE = $thread['Master_Log_File'];
                $SLAVE_MASTER_LOG_POS = $thread['Exec_Master_Log_Pos'];
            }
        }

        sleep(1);


//stop slave n°2 (future master)
        $sql = "STOP SLAVE" . $master_master_host[$ip_of_master] . ";";
        echo $servers[1] . "> " . $sql . "\n";
        $master->sql_query($sql);


        $MS->setInstance($master);
        $thread_master = $MS->isSlave();
        foreach ($thread_master as $thread) {
            if ($thread['Master_Host'] == $ip_of_master) {
                $MASTER_MASTER_LOG_FILE = $thread['Master_Log_File'];
                $MASTER_MASTER_LOG_POS = $thread['Exec_Master_Log_Pos'];
            }
        }


        if ($MASTER_MASTER_LOG_FILE !== $SLAVE_MASTER_LOG_FILE || $SLAVE_MASTER_LOG_POS > $MASTER_MASTER_LOG_POS) {
            throw new \Exception("[ERROR] PMACLI-005 : Error the new master is behind the slave");
        }

        $tab = new Table(1);

        $tab->addHeader(array("name", "IP", "port", "Connection_name", "Master_Log_File", "Master_Log_Pos"));
        
        $param['master'] = $master->getParams();
        $param['slave'] = $slave->getParams();
        
        
        $tab->addLine(array($servers[0], $param['slave']['hostname'],  empty($param['slave']['port']) ?3306:$param['slave']['port'] , $master_master_host[$ip_of_master], $SLAVE_MASTER_LOG_FILE, $SLAVE_MASTER_LOG_POS));
        $tab->addLine(array($servers[1], $param['master']['hostname'],  empty($param['master']['port']) ?3306:$param['master']['port'], $slave_master_host[$ip_of_master], $MASTER_MASTER_LOG_FILE, $MASTER_MASTER_LOG_POS));


        $msg = $tab->display();

        echo $msg;


        $sql = "SHOW MASTER STATUS";
        echo $servers[1] . "> " . $sql . "\n";

        $MS->setInstance($master);
        $master_status = $MS->isMaster();


        $tab2 = new Table(1);

        $header = array();
        $var = array();
        foreach ($master_status as $key => $value) {
            $header[] = $key;
            $var[] = $value;
        }
        
        $master_file =  $var[0];
        $master_position =  $var[1];
        
        
        
        $tab2->addHeader($header);
        $tab2->addLine($var);
        echo $tab2->display();



        $sql = "START SLAVE" . $slave_master_host[$ip_of_master] . " UNTIL MASTER_LOG_FILE='" . $MASTER_MASTER_LOG_FILE . "', MASTER_LOG_POS=" . $MASTER_MASTER_LOG_POS . ";";
        echo $servers[0] . "> " . $sql . "\n";
        $slave->sql_query($sql);

        $MS->setInstance($slave);

        do {
            $thread_slave = $MS->isSlave();
            foreach ($thread_slave as $thread) {
                if ($thread['Master_Host'] == $ip_of_master) {
                    $SLAVE_MASTER_LOG_FILE = $thread['Master_Log_File'];
                    $SLAVE_MASTER_LOG_POS = $thread['Exec_Master_Log_Pos'];
                }
            }

            $sql = "SHOW SLAVE" . $slave_master_host[$ip_of_master] . " STATUS;";
            echo $servers[0] . "> " . $sql . "\n";

            $tab3 = new Table(1);
            $tab3->addHeader(array("Master_Log_File", "Exec_Master_Log_Pos"));
            $tab3->addLine(array($SLAVE_MASTER_LOG_FILE, $SLAVE_MASTER_LOG_POS));
            echo $tab3->display();


            sleep(1);
        } while ($MASTER_MASTER_LOG_POS != $SLAVE_MASTER_LOG_POS);
        
        
        $sql = "STOP SLAVE" . $master_master_host[$ip_of_master] . ";";
        echo $servers[0] . "> " . $sql . "\n";
        $slave->sql_query($sql);

        
       
        $sql ="CHANGE MASTER".$slave_master_host[$ip_of_master]." TO MASTER_HOST = '".$param['master']['hostname']."', MASTER_LOG_FILE='".$master_file."', MASTER_LOG_POS=".$master_position.";";
        echo $servers[0] . "> " . $sql . "\n";
        $slave->sql_query($sql);


        $sql = "START SLAVE" . $slave_master_host[$ip_of_master] . ";";
        echo $servers[1] . "> " . $sql . "\n";
        $master->sql_query($sql);


        $sql = "START SLAVE" . $master_master_host[$ip_of_master] . ";";
        echo $servers[0] . "> " . $sql . "\n";
        $slave->sql_query($sql);
//$slave->sql_query($sql);
    }

}
