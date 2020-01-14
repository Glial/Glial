<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Neuron\PmaCli;

use \Glial\Cli\ProcessManager;
use \Glial\Cli\Shmop;

trait PmaCliDaemon
{

    /**
     * 
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param void 
     * @return void
     * @description connect to each MySQL server and get status of all replication thread to save in database
     * @access public
     * @package PmaCli
     * @See Glial\Neuron\Controller\PmaCli\replicationDrawGraph
     * @since 3.0 First time this was introduced.
     * @version 3.0
     */
    public function daemonStart()
    {

        $this->layout_name = false;
        $this->view = false;

        $default = Sgbd::sql(DB_DEFAULT);
        $ip = array();
        $masters = array();
        $i = 0;

        $sql = "SELECT * FROM mysql_server";
        $res50 = $default->sql_query($sql);

        $fork = new ProcessManager(20);

        //creation ressource partagé
        $mem = new Shmop('/tmp/', 'sharedmemory/');

        for ($i = 1; $i < 53; $i++) {

            $shmkey = $mem->get_key(1, "a" . $i);
            $mem->writemem($shmkey, "0");
        }

        $i = 0;
        while ($ob50 = $default->sql_fetch_object($res50)) {
            $i++;
            $fork->fork(__NAMESPACE__ . '\\' . __CLASS__ . '::daemonCheckServer', array($ob50->name, $i));
        }

        $fork->waitAll();


        echo "FIN DU PERE\n";

        debug($fork->getStatus());
    }

    static public function daemonCheckServer($name, $i)
    {
        $mem = new Shmop('/tmp/', 'sharedmemory/');
        $shmkey = $mem->get_key(1, "a" . $i);
        $shm_size = $mem->writemem($shmkey, "1");
        $time = mt_rand(15, 100);

        self::daemonDisplay($i, "[THREAD STARTED ($i WAIT => $time)]");

        sleep($time);

        self::daemonDisplay($i, "[THREAD STOPED ($i WAIT => $time)]");

        $shmkey = $mem->get_key(1, "a" . $i);
        $mem->writemem($shmkey, "0");

    }

    static public function daemonDisplay($i, $msg)
    {
        $mem = new Shmop('/tmp/', 'sharedmemory/');
        $bits = '';
        $nbthread = 0;

        for ($j = 1; $j < 53; $j++) {
            $shmkey = $mem->get_key(1, "a" . $j);
            $out = $mem->readmem($shmkey, 1);


            if ($out == 1) {
                if ($i === $j) {
                    $bits .= '*';
                } else {
                    $bits .= '|';
                    $nbthread++;
                }
            } else {
                $bits .= ' ';
            }
        }
        echo "[Total : " . str_pad($nbthread, 3) . "] [" . date("Y-m-d H:i:s") . "] " . $bits . " " . $msg . "\n";
    }

}
