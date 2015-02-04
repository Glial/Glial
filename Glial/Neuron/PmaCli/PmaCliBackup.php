<?php

/*
 * Adresse IP : 10.7.6.250
Login : logftp
 * Ap4ch3l0g
 */

namespace Glial\Neuron\PmaCli;

trait PmaCliBackup {

    public function backupDeleteOld() {

        $this->view = false;

        $db = $this->di['db']->sql(DB_DEFAULT);

        $sql = "SELECT id,file_name,is_gziped,date_start FROM mysql_dump WHERE date_start < DATE_SUB(now(),INTERVAL 10 DAY) AND is_available = 1";

        foreach ($db->sql_fetch_yield($sql) as $tab) {

            if ($tab['is_gziped'] === '1') {
                $file = $tab['file_name'] . ".gz";
            } else {
                $file = $tab['file_name'];
            }

            $data = array();
            $data['mysql_dump']['id'] = $tab['id'];
            $data['mysql_dump']['is_available'] = 0;

            if ($db->sql_save($data)) {

                try {
                    if (!unlink($tab['file_name'] . ".gz")) {

                        throw new \Exception("GLI-050 Impossible to delete : " . $file);
                    } else {
                        echo "deleted file : " . $file . "\n";
                    }
                } catch (Exception $ex) {
                    echo $ex->getMessage();
                }
            } else {
                debug($data);
                debug($db->sql_error());
                die();
            }
        }
    }


    public function backupCompress() {
        $sql = "SELECT file_name FROM mysql_dump where is_gziped=0";

        $db = $this->di['db']->sql(DB_DEFAULT);

        $res = $db->sql_query($sql);

        while ($ob = $db->sql_fetch_object($res)) {
            try {
                $cmd = "gzip " . $ob->file_name;
                echo $cmd . "\n";
                $this->cmd($cmd);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }

        $sql = "UPDATE mysql_dump SET is_gziped=1 where is_gziped=0";
        $db->sql_query($sql);
    }
    
    
    public function backupShow($param)
    {
        
        $db = $this->di['db']->sql(DB_DEFAULT);
        
        $sql = "SELECT * FROM mysql_dump a
                INNER JOIN mysql_server b ON a.id_mysql_server = b.id
                ORDER BY date_end DESC";
        
        $data['list_dump'] = $db->sql_fetch_yield($res);
        
        
        
        $this->set('data',$data);
        
    }
}
