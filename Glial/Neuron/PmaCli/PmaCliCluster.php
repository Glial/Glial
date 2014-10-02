<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Neuron\PmaCli;

use \Glial\Cli\Color;

trait PmaCliCluster
{

    public function clusterGalera($dblink)
    {

        $out = array();

        $sql = "show global variables WHERE Variable_name='wsrep_cluster_name'";
        $res = $dblink->sql_query($sql);

        while ($ob = $dblink->sql_fetch_object($res)) {
            $out['cluster_name'] = $ob->Value;
        }

        $elems = array("WSREP_INCOMING_ADDRESSES");

        foreach ($elems as $elem) {
            $sql = "SELECT VARIABLE_VALUE FROM INFORMATION_SCHEMA.GLOBAL_STATUS WHERE VARIABLE_NAME ='" . $elem . "';";
            $res = $dblink->sql_query($sql);

            while ($ob = $dblink->sql_fetch_object($res)) {

                switch ($elem) {
                    case 'WSREP_CLUSTER_SIZE':
                        $out['cluster_size'] = $ob->VARIABLE_VALUE;
                        break;

                    case 'WSREP_INCOMING_ADDRESSES':
                        $out['address'] = $ob->VARIABLE_VALUE;
                        break;
                }
            }
        }

        if (count($out) === 2) {

            $default = $this->di['db']->sql("default");



            $sql = "SELECT * FROM mysql_cluster WHERE name ='" . $default->sql_real_escape_string($out['cluster_name']) . "'";


            $res = $default->sql_query($sql);

            if ($default->sql_num_rows($res) === 1) {
                $ob = $default->sql_fetch_object($res);


                $id_cluster = $ob->id;
            }


            if (empty($id_cluster)) {

                $data = array();
                $data['mysql_cluster']['name'] = $out['cluster_name'];
                //$data['mysql_cluster']['cluster_size'] = count(explode(',', $out['address']));
                //$data['mysql_cluster']['ip'] = $out['address'];


                $id_cluster = $default->sql_save($data);

                if (!$id_cluster) {

                    debug($data);
                    debug($default->sql_error());
                }
            }


            if ($id_cluster) {


                $sql = "SELECT id FROM mysql_server WHERE ip = '" . $dblink->host . "' AND port= '" . $dblink->port . "'";

                $res = $default->sql_query($sql);

                if ($default->sql_num_rows($res) !== 1) {
                    throw new \Exception('GLI-050 : Impossible to select the right MySQL serveur ! ' . "\n" . $sql);
                } else {



                    $ob = $default->sql_fetch_object($res);


                    $sql = "select count(1) as cpt FROM link__mysql_cluster__mysql_server where id_mysql_server= '" . $ob->id . "' AND id_mysql_cluster = '" . $id_cluster . "'";
                    $res10 = $default->sql_query($sql);

                    while ($ob10 = $default->sql_fetch_object($res10)) {
                        if ($ob10->cpt === "0") {

                            $data = array();
                            $data['link__mysql_cluster__mysql_server']['id_mysql_server'] = $ob->id;
                            $data['link__mysql_cluster__mysql_server']['id_mysql_cluster'] = $id_cluster;
                            $data['link__mysql_cluster__mysql_server']['cluster_size'] = count(explode(',', $out['address']));
                            $data['link__mysql_cluster__mysql_server']['node_connected'] = $out['address'];

                            if (!$default->sql_save($data)) {

                                debug($data);
                                debug($default->sql_error());
                            }
                        }
                    }


                    $sql = "select count(1) as cpt FROM mysql_cluster_node where id_mysql_server= '" . $ob->id . "' AND id_mysql_cluster = '" . $id_cluster . "'";
                    $res11 = $default->sql_query($sql);

                    while ($ob11 = $default->sql_fetch_object($res11)) {
                        if ($ob11->cpt === "0") {

                            $data = array();
                            $data['mysql_cluster_node']['id_mysql_server'] = $ob->id;
                            $data['mysql_cluster_node']['id_mysql_cluster'] = $id_cluster;
                            if (!$default->sql_save($data)) {

                                debug($data);
                                debug($default->sql_error());
                            }
                        }
                    }
                }
            }
        }
        return $out;
    }

    public function clusterTest($param)
    {
        $this->view = false;

        foreach ($param as $server) {
            $db = $this->di['db']->sql(str_replace('-', '_', $server));

            $sql = "CREATE DATABASE IF NOT EXISTS `" . $server . "`;";
            $db->sql_query($sql);

            echo "[" . date("Y-m-d H:i:s") . "] " . $server . "> " . \SqlFormatter::highlight($sql);

        }


        foreach ($param as $server) {
            $db = $this->di['db']->sql(str_replace('-', '_', $server));

            $sql = "SHOW DATABASES";
            $databases = $db->sql_fetch_yield($sql);

            $i = 0;
            foreach ($databases as $database) {
                if (in_array($database['Database'], $param)) {


                    echo "[NOTICE] " . $server . "> " . $database['Database'] . " : Found !" . PHP_EOL;

                    $i++;
                }
            }

            if ($i != count($param)) {

                echo Color::getColoredString("[ERROR] Only $i DB found !", "grey", "red") . PHP_EOL;
            } else {
                echo Color::getColoredString("[NOTICE] $server> OK !", "black", "green") . PHP_EOL;
            }

        }

        foreach ($param as $server) {
            $db = $this->di['db']->sql(str_replace('-', '_', $server));

            $sql = "DROP DATABASE `" . $server . "`;";
            $db->sql_query($sql);

            echo "[" . date("Y-m-d H:i:s") . "] " . $server . "> " . \SqlFormatter::highlight($sql);
        }


        foreach ($param as $server) {
            $db = $this->di['db']->sql(str_replace('-', '_', $server));

            $sql = "SHOW DATABASES";
            $databases = $db->sql_fetch_yield($sql);

            $i = 0;
            foreach ($databases as $database) {
                if (in_array($database['Database'], $param)) {
                    $i++;
                }
            }

            if ($i != 0) {
                echo Color::getColoredString("[ERROR] $i DB found on $server !", "grey", "red") . PHP_EOL;
            } else {
                echo Color::getColoredString("[NOTICE] $server> No databases found !", "black", "green") . PHP_EOL;
            }
        }

    }

}
