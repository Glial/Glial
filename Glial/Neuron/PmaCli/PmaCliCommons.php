<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Neuron\PmaCli;

trait PmaCliCommons
{

    public function getServerId($param)
    {
        if (is_array($param)) {
            $server_name = $param[0];
        } else {
            $server_name = $param;
        }
        $server_name = str_replace("-", "_", $server_name);

        $default = Sgbd::sql(DB_DEFAULT);

        $sql = "SELECT `id` FROM `mysql_server` WHERE `name` = '" . $server_name . "'";
        $res = $default->sql_query($sql);

        if ($default->sql_num_rows($res) != 1) {
            throw new \Exception("PMACTRL-058 : Impossible to find this server '" . $server_name . "'");
        }


        $ob = $default->sql_fetch_object($res);
        $id_mysql_server = $ob->id;

        return $id_mysql_server;
    }

    public function getReplicationStatsId($param)
    {
        $default = Sgbd::sql(DB_DEFAULT);

        //$id_mysql_server = $this->getServerId($param);
        if (is_array($param)) {
            $id_mysql_server = $param[0];
        } else {
            $id_mysql_server = $param;
        }
        

        $sql = "SELECT id FROM mysql_replication_stats WHERE id_mysql_server ='" . $id_mysql_server . "'";
        $res = $default->sql_query($sql);
        if ($default->sql_num_rows($res) == 1) {
            $data = $default->sql_to_array($res);
            $id_mysql_replication_stats = $data[0]['id'];

            return $id_mysql_replication_stats;
        }
        
        return false;
    }

}
