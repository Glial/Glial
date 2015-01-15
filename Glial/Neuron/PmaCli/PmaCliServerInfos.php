<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Neuron\PmaCli;

use Glial\Scheduler\Scheduler;

use Glial\Sgbd\Sql\Mysql\MasterSlave;

use Glial\Scheduler\Background\Background;

trait PmaCliServerInfos
{

    //use Glial\Neuron\PmaCli\PmaCliCommons;

    static public function test ($param)
    {
        echo "a $param";
        sleep(10);
    }
    
    
    public function setServerInfosMain($param)
    {
        $this->view = false;

        //set_time_limit(5);

        $server_name = $param[0];

        $default = $this->di['db']->sql(DB_DEFAULT);

        $MS = new MasterSlave();

        //if timeout expire set server as down
        
        Background::backgroundExec('\Glial\Synapse\FactoryController::addNode', 'ff', '', 5);
        
        /*
        Scheduler::onTimeLimit ('\Glial\Synapse\FactoryController', 'addNode', array('PmaCli', 'setServerAsDown', array($server_name)));

        shell_exec("sleep 2");
        
        $id_mysql_server = $this->getServerId($server_name);


        // insert or update into mysql_replication_stats
        $mysql_replication_stats = [];


        $db = $this->di['db']->sql($server_name);

        if ($db->is_connected) {
            $MS->setInstance($db);
            
            $master = $MS->isMaster();
            $slave = $MS->isSlave();
            $mysql_replication_stats['mysql_replication_stats']['ping'] = 1;
        } else {
            $mysql_replication_stats['mysql_replication_stats']['ping'] = 0;
            $master = false;
            $slave = false;

            //log mysql server is down and email it
        }


        //save infos server and master position
        $id_mysql_replication_stats = $this->GetReplicationStatsId($id_mysql_server);
        if ($id_mysql_replication_stats) {
            $mysql_replication_stats['mysql_replication_stats']['id'] = $id_mysql_replication_stats;
        }
        $mysql_replication_stats['mysql_replication_stats']['id_mysql_server'] = $id_mysql_server;
        $mysql_replication_stats['mysql_replication_stats']['date'] = date("Y-m-d H:i:s");

        if ($mysql_replication_stats['mysql_replication_stats']['ping']) {

            $sql = "SELECT now() as date_time";
            $res = $db->sql_query($sql);
            $date_time = $db->sql_fetch_object($res);
            $mysql_replication_stats['mysql_replication_stats']['date'] = $date_time->date_time;


            if (version_compare($db->getVersion(), '10.0') >= 0) {
                $this->clusterGalera($db);
            }

            $mysql_replication_stats['mysql_replication_stats']['version'] = $db->getServerType() . " : " . $db->getVersion();
            $mysql_replication_stats['mysql_replication_stats']['is_master'] = ($master) ? 1 : 0;
            $mysql_replication_stats['mysql_replication_stats']['is_slave'] = ($slave) ? 1 : 0;
            $mysql_replication_stats['mysql_replication_stats']['uptime'] = ($db->getStatus('Uptime')) ? $db->getStatus('Uptime') : '-1';
            $mysql_replication_stats['mysql_replication_stats']['time_zone'] = ($db->getVariables('system_time_zone')) ? $db->getVariables('system_time_zone') : '-1';
            $mysql_replication_stats['mysql_replication_stats']['ping'] = 1;
            $mysql_replication_stats['mysql_replication_stats']['last_sql_error'] = '';
            $mysql_replication_stats['mysql_replication_stats']['binlog_format'] = ($db->getVariables('binlog_format')) ? $db->getVariables('binlog_format') : 'N/A';


            $dblist = array();
            $res3 = $db->sql_query("SHOW databases");
            while ($ob3 = $db->sql_fetch_object($res3)) {
                $dblist[] = $ob3->Database;
            }

            $mysql_replication_stats['mysql_replication_stats']['databases'] = implode(',', $dblist);
            
            
            if ($master) {
                $data['mysql_replication_stats']['file'] = $master['File'];
                $data['mysql_replication_stats']['position'] = $master['Position'];
            }
        }

        $default->sql_save($mysql_replication_stats);
        
        
        debug($db->getVariables());
        /**/
        
    }

    public function setServerAsDown($param)
    {
        $this->view = false;
        $server_name = $param[0];

        $default = $this->di['db']->sql(DB_DEFAULT);
        $id_mysql_server = $this->getServerId($server_name);

        //set server status to off to prevent time out / or no answer.
        $mysql_replication_stats = [];

        $id_mysql_replication_stats = $this->getReplicationStatsId($id_mysql_server);
        if ($id_mysql_replication_stats) {
            $mysql_replication_stats['mysql_replication_stats']['id'] = $id_mysql_replication_stats;
        }
        $mysql_replication_stats['mysql_replication_stats']['id_mysql_server'] = $id_mysql_server;
        $mysql_replication_stats['mysql_replication_stats']['ping'] = -1;
        $mysql_replication_stats['mysql_replication_stats']['date'] = date("Y-m-d H:i:s");

        $res = $default->sql_save($mysql_replication_stats);

        if ($res) {
            echo "Server '" . $server_name . "' marked as down\n";
        } else {
            throw new Exception("PMACTRL-058 : Impossible to save in mysql_replication_stats");
        }
    }

    public function pushToInflux()
    {
        $client = new \crodas\InfluxPHP\Client(
                "dev.metrics.noc2.photobox.com", 8086, "root", "root"
        );
        $influxDB = $client->mysqlmetrics;

        $sql = "SELECT * FROM information_schema.GLOBAL_STATUS ORDER BY VARIABLE_NAME";
        $global_status = $dblink->sql_fetch_yield($sql);


        foreach ($global_status as $status) {

            $value = (int) $status['VARIABLE_VALUE'];
            $influxDB->insert(str_replace('_', '-', $db) . "." . $status['VARIABLE_NAME'], ['value' => $value]);
        }
    }

}
