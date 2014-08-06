<?php

namespace Glial\Neuron\PmaCli;

trait PmaCliArray {

    function getStdArray() {

        $this->layout_name = false;
        $this->view = false;

        $default = $this->di['db']->sql("default");

        $sql = "SELECT ip,port FROM `mysql_server`";

        $res = $default->sql_query($sql);

        $servers = array();
        while ($ob = $default->sql_fetch_object($res)) {
            $servers[$ob->ip."-".$ob->port] = array($ob->ip."-".$ob->port);
        }

        $sql = "SELECT a.`master_host`, c.ip , c.port, a.master_port
         FROM mysql_replication_thread a
         INNER JOIN mysql_replication_stats b ON a.id_mysql_replication_stats = b.id
         INNER JOIN mysql_server c ON c.id = b.id_mysql_server";

        $res = $default->sql_query($sql);

        while ($ob = $default->sql_fetch_object($res)) {


            if (count($servers[$ob->ip."-".$ob->port]) === 1) {
                unset($servers[$ob->ip."-".$ob->port]);
            }


            foreach ($servers as $key => $server) {
                if (in_array($ob->master_host."-".$ob->master_port, $server)) {

                    $servers[$ob->master_host."-".$ob->master_port][] = $ob->ip."-".$ob->port;

                    break;
                }
            }
        }

        return $servers;
    }

}
