<?php

namespace Glial\Neuron\Controller;

use Glial\Cli\Table;
use Glial\Cli\Window;
use \Glial\Sgbd\Sql\Mysql\MasterSlave;

trait PmaCli {

    public function load($param) {

        $this->view = false;

        $db = $this->di['db']->sql('default');

        $server_dest = $param[0];
        $databases = explode(",", $param[1]);

        $dump = array();

        foreach ($databases as $database) {
            $sql = "SELECT a.id,b.`name`,b.ip,b.port,a.date_start, a.`time`, a.`database` FROM mysql_dump a"
                    . " inner join mysql_server b ON a.id_mysql_server = b.id"
                    . " WHERE `database` ='" . $db->sql_real_escape_string($database) . "'"
                    . " order by a.id";
            $res = $db->sql_query($sql);

            $tab = new Table(1);

            $tab->addHeader(array("ID", "name", "IP", "port", "date_start", "time", "database"));

            while ($ob = $db->sql_fetch_object($res)) {
                $tab->addLine(array($ob->id, $ob->name, $ob->ip, $ob->port, $ob->date_start, $ob->time, $ob->database));
            }

            $msg = $tab->display();


            $msg .= "Select the dump (id) to restore on '" . $server_dest . "' ? \n";
            $msg .= "[[INPUT]]\n";

            new Window("Load database", $msg);
        }
    }

    /**
     * 
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param string the path and file name 
     * @return boolean
     * @description make a graph with graphviz who represent the replication mysql with their status
     * @access public
     * @package PmaCli
     * @See Glial\Neuron\Controller\PmaCli\replicationRefresh
     * @since 3.0 First time this was introduced.
     * @version 3.0
     */
    public function replicationDrawGraph($file_name) {

        $path_parts = pathinfo($file_name);

        $path = $path_parts['dirname'];
        $type = $path_parts['extension'];
        $file = $path_parts['filename'];

        if (file_exists($file_name)) {
            unlink($file_name);
        }

        if ($fp = fopen($path . '/' . $file . '.dot', "w")) {

            $db = $this->di['db']->sql("default");

            fwrite($fp, "digraph G { rankdir = LR; " . PHP_EOL);
            //fwrite($fp, "\t size=\"10,1000\";");

            fwrite($fp, "\t edge [color=green];" . PHP_EOL);
            fwrite($fp, "\t node [color=green shape=rect style=filled fontsize=8 ranksep=0 concentrate=true splines=true overlap=false];" . PHP_EOL);
            //fwrite($fp, "\t node [color=none shape=rect fontsize=8 ranksep=4 concentrate=false splines=false overlap=false];");

            $sql = "SELECT a.`id`,a.`ip`,a.`name`,a.`port`,b.`databases`,b.`version`,b.`date` FROM `mysql_server` a
            INNER JOIN mysql_replication_stats b ON a.id = b.id_mysql_server 
            order by ip";
            $res = $db->sql_query($sql);

            $ip = array();
            while ($ob = $db->sql_fetch_object($res)) {

                if (empty($ob->version)) {
                    fwrite($fp, "\t node [color=red];" . PHP_EOL);
                } else {
                    fwrite($fp, "\t node [color=green];" . PHP_EOL);
                }
                // shape=Mrecord
                fwrite($fp, '  "' . $ob->id . '" [style="" penwidth="3" fillcolor="yellow" fontname="Courier New" label =<<table border="0" cellborder="0" cellspacing="0" cellpadding="1" bgcolor="white"><tr><td bgcolor="black" align="center"><font color="white">' . str_replace('_', '-', $ob->name) . '</font></td></tr><tr><td bgcolor="grey" align="left">' . $ob->ip . ':' . $ob->port . '</td></tr>');
                fwrite($fp, '<tr><td bgcolor="grey" align="left">' . $ob->version . '</td></tr>' . PHP_EOL);
                //fwrite($fp, '<tr><td bgcolor="grey" align="left">' . $ob->date.'</td></tr>');

                $databases = explode(',', $ob->databases);

                foreach ($databases as $database) {
                    fwrite($fp, '<tr><td bgcolor="#dddddd" align="left">' . $database . '</td></tr>' . PHP_EOL);
                }

                fwrite($fp, '</table>> ];' . PHP_EOL);
                $ip [$ob->ip] = $ob->id;
            }

            $sql = "SELECT a.`id`,a.ip,c.`master_host`,c.thread_io,c.thread_sql,c.time_behind,c.id as id_thread FROM `mysql_server` a
                    INNER JOIN mysql_replication_stats b ON a.id = b.id_mysql_server
                    INNER JOIN mysql_replication_thread c ON b.id = c.id_mysql_replication_stats";
            $res = $db->sql_query($sql);

            while ($ob = $db->sql_fetch_object($res)) {

                if ($ob->thread_io && $ob->thread_sql && $ob->time_behind === "0") {
                    $color = "green";
                } elseif ($ob->thread_io === "0" && $ob->thread_sql === "0") {
                    $color = "blue";
                } else {
                    $color = "#DA6200";
                }
                fwrite($fp, "" . $ip[$ob->master_host] . " -> " . $ob->id . '[ arrowsize="2" penwidth="2" color ="' . $color . '" label = ""  edgetarget="http://www.google.fr" edgeURL="http://www.google.fr"];' . PHP_EOL);
            }

            fwrite($fp, "}");
            fclose($fp);
            exec('dot -T' . $type . ' ' . $path . '/' . $file . '.dot -o ' . $path . '/' . $file . '.' . $type . '');

            return true;
        } else {
            throw new \Exception("GLI-035 : Impossible to write to : '" . $file_name . "'");
        }

        return false;
    }

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
    public function replicationUpdate() {

        $this->layout_name = false;
        $this->view = false;

        $default = $this->di['db']->sql("default");

        $MS = new MasterSlave();

        $ip = array();
        $masters = array();
        $i = 0;


        $sql = "DELETE FROM mysql_replication_stats";
        $default->sql_query($sql);


        $sql = "ALTER TABLE mysql_replication_stats AUTO_INCREMENT = 1";
        $default->sql_query($sql);
        $sql = "ALTER TABLE mysql_replication_thread AUTO_INCREMENT = 1";
        $default->sql_query($sql);


        foreach ($this->di['db']->getAll() as $db) {
            $i++;
            //$server_config = $this->di['db']->sql($db)->getParams();

            $server_on = true;

            try {
                $dblink = $this->di['db']->sql($db);

                $MS->setInstance($dblink);
                $master = $MS->isMaster();
                $slave = $MS->isSlave();
            } catch (\Exception $ex) {
                $server_on = false;
                $master = false;
                $slave = false;
            }


            $sql = "SELECT id FROM mysql_server WHERE name = '" . $db . "'";

            //echo $sql . PHP_EOL;
            $res = $default->sql_query($sql);

            while ($ob = $default->sql_fetch_object($res)) {

                $data = array();
                $data['mysql_replication_stats']['id_mysql_server'] = $ob->id;
                $data['mysql_replication_stats']['date'] = date("Y-m-d H:i:s");
                $data['mysql_replication_stats']['ping'] = $server_on;

                if ($server_on) {



                    $data['mysql_replication_stats']['version'] = $dblink->getServerType() . " : " . $dblink->getVersion();
                    $data['mysql_replication_stats']['date'] = date("Y-m-d H:i:s");
                    $data['mysql_replication_stats']['is_master'] = ($master) ? 1 : 0;
                    $data['mysql_replication_stats']['is_slave'] = ($slave) ? 1 : 0;

                    $sql = "SHOW databases";
                    $dblist = array();
                    $res3 = $dblink->sql_query($sql);
                    while ($ob3 = $dblink->sql_fetch_object($res3)) {
                        $dblist[] = $ob3->Database;
                    }

                    $data['mysql_replication_stats']['databases'] = implode(',', $dblist);



                    if ($master) {
                        $data['mysql_replication_stats']['file'] = $master['File'];
                        $data['mysql_replication_stats']['position'] = $master['Position'];
                    }
                }

                $id_mysql_replication_stats = $default->sql_save($data);

                if (!$id_mysql_replication_stats) {
                    debug($default->sql_error());
                    debug($data);
                    throw new \Exception("GLI-031 : Impossible to get id_mysql_replication_stats");
                }

                if ($slave) {
                    foreach ($slave as $thread) {
                        $data = array();

                        $data['mysql_replication_thread']['id_mysql_replication_stats'] = $id_mysql_replication_stats;
                        $data['mysql_replication_thread']['relay_master_log_file'] = $thread['Relay_Master_Log_File'];
                        $data['mysql_replication_thread']['exec_master_log_pos'] = $thread['Exec_Master_Log_Pos'];
                        $data['mysql_replication_thread']['thread_io'] = ($thread['Slave_IO_Running'] === 'Yes') ? 1 : 0;
                        $data['mysql_replication_thread']['thread_sql'] = ($thread['Slave_SQL_Running'] === 'Yes') ? 1 : 0;


                        //only for MariaDB 10
                        if (version_compare($dblink->getVersion(), "10", ">=")) {
                            $data['mysql_replication_thread']['thread_name'] = $thread['Connection_name'];
                        }

                        $data['mysql_replication_thread']['time_behind'] = $thread['Seconds_Behind_Master'];
                        $data['mysql_replication_thread']['master_host'] = $thread['Master_Host'];

                        $id_mysql_replication_thread = $default->sql_save($data);

                        if (!$id_mysql_replication_thread) {
                            debug($default->sql_error());
                            debug($data);
                            throw new \Exception("GLI-032 : Impossible to save row in mysql_replication_thread");
                        }
                    }
                }
            }
        }
    }

    /**
     * 
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @license GNU/GPL
     * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
     * @param string the path and file name 
     * @return boolean
     * @description make a graph with graphviz who represent the replication mysql with their status
     * @access public
     * @package PmaCli
     * @See Glial\Neuron\Controller\PmaCli\replicationRefresh
     * @since 3.0 First time this was introduced.
     * @version 3.0
     */
    public function install() {


        $sql = "CREATE TABLE `mysql_replication_stats` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `id_mysql_server` int(11) NOT NULL,
        `version` varchar(20) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
        `date` datetime NOT NULL,
        `is_master` int(11) NOT NULL,
        `is_slave` int(11) NOT NULL,
        `ping` int(11) NOT NULL,
        `file` varchar(200) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
        `position` int(11) NOT NULL,
        `databases` varchar(200) CHARACTER SET utf8 NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `id_mysql_server` (`id_mysql_server`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='3.0'";

        $sql = "CREATE TABLE `mysql_replication_thread` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `id_mysql_replication_stats` int(11) NOT NULL,
        `relay_master_log_file` varchar(200) CHARACTER SET utf8 NOT NULL,
        `exec_master_log_pos` int(11) NOT NULL,
        `thread_io` int(11) NOT NULL,
        `thread_sql` int(11) NOT NULL,
        `thread_name` varchar(100) CHARACTER SET utf8 NOT NULL,
        `time_behind` int(11) NOT NULL,
        `master_host` char(15) CHARACTER SET utf8 NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `id_mysql_replication_stats` (`id_mysql_replication_stats`,`thread_name`),
        CONSTRAINT `mysql_replication_thread_ibfk_1` FOREIGN KEY (`id_mysql_replication_stats`) REFERENCES `mysql_replication_stats` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8  COMMENT='3.0'";
    }

    public function uninstall() {
        
    }

    public function daemon() {
        $this->view = false;

        $this->replicationUpdate();
        $this->replicationDrawGraph(ROOT . '/tmp/img/replication.svg');
        $this->backupDeleteOld();
    }

    public function backupDeleteOld() {
        $sql = "SELECT * FROM `mysql_dump` WHERE  day(now()) - day(`date_end`) > 10 and is_available = 1 order by date_end;";

        $this->layout_name = false;
        $this->view = false;

        $db = $this->di['db']->sql("default");
        $sqls = '';

        foreach ($db->sql_fetch_yield($sql) as $backup) {

            $file = $backup['file_name'];
            if ($backup['is_gziped'] === '1') {
                $file = $file . ".gz";
            }


            try {
                if (!unlink($file)) {
                    throw new \Exception('GLI-040 Impossible to delete file : "' . $file . '"');
                }
            } catch (\Exception $ex) {
                echo $ex->getMessage().PHP_EOL;
            }

            $sqls = "UPDATE `mysql_dump` SET is_available =0 WHERE id=" . $backup['id'] . ";";
            $db->sql_query($sqls);
        }
        
        //shell_exec('find /data/backup* -mtime +15 -exec rm {} \;');

    }

}
