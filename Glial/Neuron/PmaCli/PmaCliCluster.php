<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Neuron\PmaCli;

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

}
