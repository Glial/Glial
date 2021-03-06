<?php

namespace Glial\Neuron\PmaCli;

use \Glial\Cli\Table;
use \Glial\Cli\Window;
use \Glial\Sgbd\Sql\Mysql\MasterSlave;
use \Glial\Security\Crypt\Crypt;
use \Glial\Date\Date;

trait PmaCli
{

    use \Glial\Neuron\PmaCli\PmaCliBackup;

use \Glial\Neuron\PmaCli\PmaCliCluster;

use \Glial\Neuron\PmaCli\PmaCliArray;

use \Glial\Neuron\PmaCli\PmaCliFailOver;

use \Glial\Neuron\PmaCli\PmaCliDaemon;

use \Glial\Neuron\PmaCli\PmaCliServerInfos;

use \Glial\Neuron\PmaCli\PmaCliCommons;

    public function load($param)
    {

        $this->view = false;

        $db = Sgbd::sql(DB_DEFAULT);

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
    public function replicationDrawGraph($file_name)
    {

        $path_parts = pathinfo($file_name);

        $path = $path_parts['dirname'];
        $type = $path_parts['extension'];
        $file = $path_parts['filename'];


        /*
          if (file_exists($file_name)) {
          unlink($file_name);
          } */

        if ($fp = fopen($path . '/' . $file . '.dot', "w")) {

            $db = Sgbd::sql(DB_DEFAULT);

            fwrite($fp, "digraph Replication { rankdir = LR; " . PHP_EOL);
//fwrite($fp, "\t size=\"10,1000\";");

            fwrite($fp, "\t edge [color=green];" . PHP_EOL);
            fwrite($fp, "\t node [color=green shape=rect style=filled fontsize=8 ranksep=0 concentrate=true splines=true overlap=false];" . PHP_EOL);
//fwrite($fp, "\t node [color=none shape=rect fontsize=8 ranksep=4 concentrate=false splines=false overlap=false];");
// gestion sandbox
            $ip_sand_box = $this->getServerWithSandBox($db);


            $sql = "SELECT a.`id`,a.`ip`,a.`name`,a.`port`,b.`databases`,b.`version`,b.`date`,b.`uptime`, b.`time_zone`, b.`binlog_format`
            FROM `mysql_server` a
            INNER JOIN mysql_replication_stats b ON a.id = b.id_mysql_server 
            LEFT JOIN mysql_cluster_node c  ON c.id_mysql_server = a.id
            WHERE c.id is null
            order by a.`ip`";



            $res = $db->sql_query($sql);

            $ip = array();


// display server alone
            $nb_cluster = 0;
            $sandbox = "";
            $sandbox_open = false;

            $elem = 1;
            while ($ob = $db->sql_fetch_object($res)) {

                if ($sandbox_open && $sandbox != $ob->ip) {
                    fwrite($fp, "}\n");
                    $sandbox_open = false;
                }

                if (in_array($ob->ip, $ip_sand_box) && !$sandbox_open) {
                    fwrite($fp, 'subgraph cluster_' . $elem . ' {');
                    fwrite($fp, 'color=black;fontname="arial";rankdir = TB;');
                    fwrite($fp, 'label = "Sandbox : ' . $ob->ip . '";');

                    $sandbox_open = true;
                    $elem++;
                }
                $sandbox = $ob->ip;


                /*
                  $data = [];


                  if (empty($ob->version)) {
                  $data['color'] = "red";
                  } else {
                  $data['color'] = "green";
                  }

                  $data['hostname'] = $ob->name;
                  $data['ip'] = $ob->ip;

                  $this->displayServer($fp, $data);
                 */
//start of tab



                if (empty($ob->version)) {
                    fwrite($fp, "\t node [color=red];" . PHP_EOL);
                } else {
                    fwrite($fp, "\t node [color=green];" . PHP_EOL);
                }
// shape=Mrecord

                $hostname = str_replace('_', '-', $ob->name);
                fwrite($fp, '  "' . $ob->id . '" [style="" penwidth="3" fillcolor="yellow" fontname="arial" label =<<table border="0" cellborder="0" cellspacing="0" cellpadding="2" bgcolor="white"><tr><td bgcolor="black" color="white" align="center" title="'. str_replace('_', '-', $ob->name).'" href="' . LINK . 'monitoring/query/' . $ob->id . '/' . '"><font color="white">' . str_replace('_', '-', $ob->name) . '</font></td></tr><tr><td bgcolor="grey" align="left">' . $ob->ip . ':' . $ob->port . '</td></tr>');
                fwrite($fp, '<tr><td bgcolor="grey" align="left">' . $ob->version . '</td></tr>' . PHP_EOL);
                fwrite($fp, '<tr><td bgcolor="grey" align="left">Uptime : ' . Date::secToTime($ob->uptime) . '</td></tr>');
                fwrite($fp, '<tr><td bgcolor="grey" align="left">(' . $ob->date . ') : ' . $ob->time_zone . '</td></tr>');
                fwrite($fp, '<tr><td bgcolor="grey" align="left">Binlog format : ' . $ob->binlog_format . '</td></tr>');
//fwrite($fp, '<tr><td bgcolor="red" align="left">Date : <b>' . $ob->date.'</b></td></tr>');


                fwrite($fp, '<tr><td bgcolor="grey" align="left">');


                fwrite($fp, $this->displayDatabase($ob->id, $hostname) . PHP_EOL);

                fwrite($fp, '</td></tr>' . PHP_EOL);
                fwrite($fp, '</table>> ];' . PHP_EOL);
                /*                 * */
// end of tab


                $ip[$ob->ip] = $ob->id;
            }



// display cluster



            $sql = "SELECT * FROM mysql_cluster";
            $res2 = $db->sql_query($sql);

            while ($cluster = $db->sql_fetch_object($res2)) {

                $elem++;

                fwrite($fp, 'subgraph cluster_' . $elem . ' {');
                fwrite($fp, 'rankdir="LR";');
                fwrite($fp, 'color=black;fontname="arial";');
                fwrite($fp, 'label = "Galera cluster : ' . $cluster->name . '";' . PHP_EOL);



                $sql = "SELECT * FROM `link__mysql_cluster__mysql_server` WHERE id_mysql_cluster = " . $cluster->id . " ORDER BY cluster_size DESC LIMIT 1";
                $res10 = $db->sql_query($sql);

                while ($ob10 = $db->sql_fetch_object($res10)) {
                    $nodes = $ob10->node_connected;
                    $list_node = explode(",", $nodes);
                }

//debug($list_node);

                $sql = "SELECT a.`id`,a.`ip`,a.`name`,a.`port`,b.`databases`,b.`version`,b.`date`,b.`uptime`, b.`time_zone`, c.node_connected
            FROM `mysql_server` a
            INNER JOIN mysql_replication_stats b ON a.id = b.id_mysql_server 
            INNER JOIN mysql_cluster_node d  ON d.id_mysql_server = a.id
            LEFT JOIN link__mysql_cluster__mysql_server c ON c.id_mysql_server = a.id
            WHERE d.id_mysql_cluster = " . $cluster->id . "
            order by a.`ip`";

//echo $sql;
                $res = $db->sql_query($sql);

                $nb_cluster = 0;
                $nodes = array();

                while ($ob = $db->sql_fetch_object($res)) {

                    if (empty($ob->version)) {
                        $color = "red";
                    } elseif (in_array($ob->ip . ':' . $ob->port, $list_node)) {
                        $color = "green";
                    } else {
                        $color = "blue";
                    }



                    fwrite($fp, "\t node [color=" . $color . "];" . "\n");

// shape=Mrecord
                    fwrite($fp, '  "' . $ob->id . '" [style="" penwidth="3" fontname="arial" label =<<table border="0" cellborder="0" cellspacing="0" cellpadding="2" bgcolor="white"><tr><td bgcolor="black" color="white" align="center"><font color="white">' . str_replace('_', '-', $ob->name) . '</font></td></tr><tr><td bgcolor="grey" align="left">' . $ob->ip . ':' . $ob->port . '</td></tr>');
                    fwrite($fp, '<tr><td bgcolor="grey" align="left">' . $ob->version . '</td></tr>' . PHP_EOL);
                    fwrite($fp, '<tr><td bgcolor="grey" align="left">Uptime : ' . Date::secToTime($ob->uptime) . '</td></tr>');
                    fwrite($fp, '<tr><td bgcolor="grey" align="left">(' . $ob->date . ') : ' . $ob->time_zone . '</td></tr>');
//fwrite($fp, '<tr><td bgcolor="red" align="left">Date : <b>' . $ob->date.'</b></td></tr>');


                    $databases = explode(',', $ob->databases);

                    foreach ($databases as $database) {
                        fwrite($fp, '<tr><td bgcolor="#dddddd" align="left">' . $database . '</td></tr>' . PHP_EOL);
                    }

                    fwrite($fp, '</table>> ];' . PHP_EOL);

                    $ip[$ob->ip] = $ob->id;


                    $nodes[] = $ob->id;

                    /*
                      foreach ($nodes as $node) {

                      if ($node !== $ob->id) {
                      fwrite($fp, "" . $node . " -> " . $ob->id . '[ dir=both arrowsize="1.5" penwidth="2" fontname="arial" fontsize=8 color ="green" label =""  edgetarget="http://www.google.fr" edgeURL="http://www.google.fr"];' . PHP_EOL);
                      //fwrite($fp, "" . $ob->id . " -> " . $node . '[ arrowsize="1.5" penwidth="2" fontname="arial" fontsize=8 color ="green" label =""  edgetarget="http://www.google.fr" edgeURL="http://www.google.fr"];' . PHP_EOL);
                      }
                      }
                      /** */
                }


//to set cluster display horizontally

                /*
                  $last_node = "";
                  foreach ($nodes as $node) {

                  if (!empty($last_node)) {
                  fwrite($fp, "" . $last_node . " -> " . $node . '[ dir=both style=invis arrowsize="1.5" penwidth="2" fontname="arial" fontsize=8 color ="green" label =""  edgetarget="http://www.google.fr" edgeURL="http://www.google.fr"];' . PHP_EOL);
                  }

                  $last_node = $node;
                  } */


                fwrite($fp, '}');
            }






            $sql = "SELECT a.`id`,a.`name`,a.ip,c.`master_host`,c.thread_io,c.thread_sql,c.time_behind,c.id as id_thread, c.last_sql_error, c.last_io_error,c.last_sql_errno,
                c.last_io_errno,c.thread_name
                FROM `mysql_server` a
                    INNER JOIN mysql_replication_stats b ON a.id = b.id_mysql_server
                    INNER JOIN mysql_replication_thread c ON b.id = c.id_mysql_replication_stats";
            $res = $db->sql_query($sql);

            while ($ob = $db->sql_fetch_object($res)) {


                $label = "";
                if ($ob->thread_io && $ob->thread_sql && $ob->time_behind === "0") {
                    $color = "green";
                } elseif ($ob->thread_io === "1" && $ob->thread_sql === "1" && $ob->time_behind !== "0") {

                    $delay = Date::secToTime($ob->time_behind);

                    $label = "Delay : " . $delay . " sec";
                    $color = "orange";
                } elseif (($ob->last_io_error !== "" || $ob->last_sql_error !== "") && ($ob->thread_io === "1" || $ob->thread_sql === "1")) {

                    $error = '';
                    $error .= empty($ob->last_sql_errno) ? '' : $ob->last_sql_errno . " ";
                    $error .= empty($ob->last_io_errno) ? '' : $ob->last_io_errno;

                    $label = "Error : " . $error;

//$label = "Error : " . $ob->last_sql_errno . $ob->last_io_errno;
                    $color = "#DA6200";
                } elseif ($ob->thread_io === "0" && $ob->thread_sql === "0" && ($ob->last_io_error !== "" && $ob->last_sql_error !== "")) {
                    $color = "black";

                    if ($ob->last_sql_errno !== $ob->last_io_errno) {
                        $error = '';
                        $error .= empty($ob->last_sql_errno) ? '' : $ob->last_sql_errno . " ";
                        $error .= empty($ob->last_io_errno) ? '' : $ob->last_io_errno;

                        $label = "Error : " . $error;
                    } else {
                        $label = "Error : " . $ob->last_sql_errno;
                    }
                } else {
                    $label = "Not started";
                    $color = "blue";
                }

                fwrite($fp, "" . $ip[$ob->master_host] . " -> " . $ob->id . '[ arrowsize="1.5" penwidth="2" fontname="arial" fontsize=8 color ="' . $color . '" label ="' . $label . '"  edgetarget="' . LINK . 'mysql/thread/' . str_replace('_', '-', $ob->name) . '/" edgeURL="' . LINK . 'mysql/thread/' . str_replace('_', '-', $ob->name) . '/' . $ob->thread_name . '"];' . PHP_EOL);
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
    public function replicationUpdate()
    {




        $this->layout_name = false;
        $this->view = false;

        $default = Sgbd::sql(DB_DEFAULT);

        $MS = new MasterSlave();

        $ip = array();
        $masters = array();
        $i = 0;

        /*
          try {

          $default->sql_query('SET AUTOCOMMIT=0;');
          $default->sql_query('START TRANSACTION;');
         */

        $sql = "DELETE FROM mysql_replication_stats";
        $default->sql_query($sql);

        $sql = "DELETE FROM `link__mysql_cluster__mysql_server`";
        $default->sql_query($sql);


        $sql = "DELETE FROM `mysql_replication_stats`";
        $default->sql_query($sql);

        $sql = "ALTER TABLE mysql_replication_stats AUTO_INCREMENT = 1";
        $default->sql_query($sql);
        $sql = "ALTER TABLE mysql_replication_thread AUTO_INCREMENT = 1";
        $default->sql_query($sql);
        $sql = "ALTER TABLE mysql_cluster AUTO_INCREMENT = 1";
        $default->sql_query($sql);
        $sql = "ALTER TABLE link__mysql_cluster__mysql_server AUTO_INCREMENT = 1";
        $default->sql_query($sql);



        $sql = "SELECT * FROM mysql_server";
        $res50 = $default->sql_query($sql);

        while ($ob50 = $default->sql_fetch_object($res50)) {


            $db = $ob50->name;

            $i++;


            echo "[" . date("Y-m-d H:i:s") . "] Try to connect to : " . $db . "\n";
            $server_config = $this->di['db']->getParam($db);

            $server_on = 1;


            $server_config['port'] = empty($server_config['port']) ? 3306 : $server_config['port'];


            $dblink = Sgbd::sql($db);


            

            if ($dblink->is_connected) {
                
                
                
                
                
                $MS->setInstance($dblink);
                $master = $MS->isMaster();
                $slave = $MS->isSlave();


                /*
                  $client = new \crodas\InfluxPHP\Client(
                  "url", 8086, "root", "root"
                  );
                  $influxDB = $client->mysqlmetrics;

                  $sql = "SELECT * FROM information_schema.GLOBAL_STATUS ORDER BY VARIABLE_NAME";
                  $global_status = $dblink->sql_fetch_yield($sql);


                  foreach ($global_status as $status) {

                  $value =  (int) $status['VARIABLE_VALUE'];
                  $influxDB->insert(str_replace('_','-', $db) . "." . $status['VARIABLE_NAME'], ['value' => $value]);
                  }
*/
                 
            } else {

                $server_on = 0;
                $master = false;
                $slave = false;

                echo " server Mysql : " . $server_config['hostname'] . " is down\n";
            }



            $sql = "SELECT id FROM mysql_server WHERE name = '" . $db . "'";

//echo $sql . PHP_EOL;
            $res = $default->sql_query($sql);

            while ($ob = $default->sql_fetch_object($res)) {


                $data = array();
                $data['mysql_replication_stats']['id_mysql_server'] = $ob->id;
                $data['mysql_replication_stats']['date'] = date("Y-m-d H:i:s");
                $data['mysql_replication_stats']['ping'] = $server_on;



                if ($server_on === 1) {
                    $sql = "SELECT now() as date_time";
                    $res = $dblink->sql_query($sql);
                    $date_time = $dblink->sql_fetch_object($res);  // can be empty ???????????


                    $this->clusterGalera($dblink);

                    $data['mysql_replication_stats']['version'] = $dblink->getServerType() . " : " . $dblink->getVersion();
                    $data['mysql_replication_stats']['date'] = $date_time->date_time;
                    $data['mysql_replication_stats']['is_master'] = ($master) ? 1 : 0;
                    $data['mysql_replication_stats']['is_slave'] = ($slave) ? 1 : 0;
                    $data['mysql_replication_stats']['uptime'] = ($dblink->getStatus('Uptime')) ? $dblink->getStatus('Uptime') : '-1';
                    $data['mysql_replication_stats']['time_zone'] = ($dblink->getVariables('system_time_zone')) ? $dblink->getVariables('system_time_zone') : '-1';
                    $data['mysql_replication_stats']['ping'] = 1;
                    $data['mysql_replication_stats']['last_sql_error'] = '';
                    $data['mysql_replication_stats']['binlog_format'] = ($dblink->getVariables('binlog_format')) ? $dblink->getVariables('binlog_format') : 'N/A';

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

                $thread = [];

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
                        $data['mysql_replication_thread']['master_port'] = $thread['Master_Port'];

//suuport for mysql 5.0
                        $data['mysql_replication_thread']['last_sql_error'] = empty($thread['Last_SQL_Error']) ? $thread['Last_Error'] : $thread['Last_SQL_Error'];
                        $data['mysql_replication_thread']['last_io_error'] = empty($thread['Last_IO_Error']) ? $thread['Last_Error'] : $thread['Last_IO_Error'];

                        $data['mysql_replication_thread']['last_sql_errno'] = empty($thread['Last_SQL_Errno']) ? $thread['Last_Errno'] : $thread['Last_SQL_Errno'];
                        $data['mysql_replication_thread']['last_io_errno'] = empty($thread['Last_IO_Errno']) ? $thread['Last_Errno'] : $thread['Last_IO_Errno'];

                        $id_mysql_replication_thread = $default->sql_save($data);

                        if (!$id_mysql_replication_thread) {
                            debug($default->sql_error());
                            debug($data);
//throw new \Exception("GLI-032 : Impossible to save row in mysql_replication_thread");
                        } else {
                            $thread['id_mysql_replication_thread'] = $default->sql_insert_id();
                        }
                    }
                }

                $this->saveDatabase($dblink, $ob->id, $master, $thread);
            }
        }


        $default->sql_query('COMMIT;');

        /*
          } catch (\Exception $ex) {


          $default->sql_query('ROLLBACK;');
          } */
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
    public function install()
    {


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

    public function uninstall()
    {
        
    }

    public function all()
    {
//$this->testDaemon();
        ini_set('mysql.connect_timeout', '5');
        ini_set('max_execution_time', '20');


        $this->updateServerList();


        
        
        $this->view = false;
        $previous_data = $this->sql_to_array();

        $this->replicationUpdate();

        $actual_data = $this->sql_to_array();
        $this->monitoring($actual_data, $previous_data);

        $this->replicationDrawGraph(ROOT . '/tmp/img/replication.svg');

        $this->deleteBackup();
//$this->saveVariable();
    }

    public function daemon()
    {
//$this->testDaemon();


        /*
          $mutex = new \SyncMutex("UniqueName");

          if (!$mutex->lock(3000)) {
          echo "Impossible de verrouiller le mutex.";

          exit();
          } */


        $i = 0;

        while (true) {

            $i++;
            passthru("php ".ROOT."/application/webroot/index.php pma_cli all");

            if ($i % 10 === 0) {
                passthru("php ".ROOT."/application/webroot/index.php pma_cli updateServerList");
                $i = 0;
            }

            sleep(10);
        }

        //$mutex->unlock();
    }

    public function backupDeleteOld()
    {
        $sql = "SELECT * FROM `mysql_dump` WHERE  day(now()) - day(`date_end`) > 10 and is_available = 1 order by date_end;";

        $this->layout_name = false;
        $this->view = false;

        $db = Sgbd::sql(DB_DEFAULT);
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
                echo $ex->getMessage() . PHP_EOL;
            }

            $sqls = "UPDATE `mysql_dump` SET is_available =0 WHERE id=" . $backup['id'] . ";";
            $db->sql_query($sqls);
        }

//shell_exec('find /data/backup* -mtime +15 -exec rm {} \;');
    }

    public function updateServerList()
    {
        $this->view = false;
        $db = Sgbd::sql(DB_DEFAULT);
        $sql = "SELECT * FROM `mysql_server`";
        $servers_mysql = $db->sql_fetch_yield($sql);
        $all_server = array();
        foreach ($servers_mysql as $mysql) {
            $all_server[$mysql['name']] = $mysql;
        }
        Crypt::$key = CRYPT_KEY;

        $all = array();
        foreach ($this->di['db']->getAll() as $server) {

            $all[] = $server;
            $info_server = Sgbd::getParam($server);
            $data = array();

            if (!empty($all_server[$server])) {
                $data['mysql_server']['id'] = $all_server[$server]['id'];

                unset($all_server[$server]);
            } else {
                echo "Add : " . $server . " to monitoring\n";
            }

            $data['mysql_server']['name'] = $server;
            $data['mysql_server']['ip'] = $info_server['hostname'];
            $data['mysql_server']['login'] = $info_server['user'];
            $data['mysql_server']['passwd'] = Crypt::encrypt($info_server['password']);
            $data['mysql_server']['port'] = empty($info_server['port']) ? 3306 : $info_server['port'];

            if (!empty($info_server['ssh_login'])) {
                $data['mysql_server']['ssh_login'] = Crypt::encrypt($info_server['ssh_login']);
            }
            if (!empty($info_server['ssh_password'])) {
                $data['mysql_server']['ssh_password'] = Crypt::encrypt($info_server['ssh_password']);
            }

            if (!$db->sql_save($data)) {
                debug($data);
                debug($db->sql_error());
                exit;
            } else {
//echo $data['mysql_server']['name'] . PHP_EOL;
            }
        }


        foreach ($all_server as $to_delete) {
            $sql = "DELETE FROM `mysql_server` WHERE id=" . $to_delete['id'] . "";
            $db->sql_query($sql);


            echo "[Warning] Removed : " . $to_delete['name'] . " from monitoring\n";
        }
    }

    private function compare($tab_from = array(), $tab_to)
    {
        $tab_update = array_intersect_key($tab_from, $tab_to);
        foreach ($tab_update as $key => $value) {
            if ($tab_from[$key] != $tab_to[$key]) {
                $update[$key] = $tab_to[$key];
                $update2[$key] = $tab_from[$key];
            }
        }
        foreach ($tab_to as $key => $value) {
            if (!isset($tab_update[$key])) {
                $add[$key] = $value;
            }
        }
        foreach ($tab_from as $key => $value) {
            if (!isset($tab_update[$key])) {
                $del[$key] = $value;
            }
        }

        $finale = array();
        empty($add) ? "" : $finale['add'] = $add;
        empty($delete) ? "" : $finale['delete'] = $del;
        empty($update) ? "" : $finale['update'] = $update;
        empty($update2) ? "" : $finale2['update'] = $update2;

        $param['up'] = $finale;
        empty($finale2) ? $param['down'] = array() : $param['down'] = $finale2;

        return ($param);
    }

    private function sql_to_array()
    {


        $sql = "SELECT a.`id`,a.ip,a.port,c.`master_host`,c.thread_io,c.thread_sql,c.time_behind,c.id as id_thread, c.last_sql_error,
            c.last_io_error,c.last_sql_errno, c.last_io_errno
                FROM `mysql_server` a
                    INNER JOIN mysql_replication_stats b ON a.id = b.id_mysql_server
                    INNER JOIN mysql_replication_thread c ON b.id = c.id_mysql_replication_stats";

        $db = Sgbd::sql(DB_DEFAULT);

        $arr = $db->sql_fetch_all($sql);

        $data = array();
        foreach ($arr as $tab) {

            $data[$tab['ip'] . "-" . $tab['master_host']] = $tab;
        }

        return $data;
    }

    private function monitoring($previous_data, $actual_data)
    {
        $db = Sgbd::sql(DB_DEFAULT);

        foreach ($previous_data as $key => $tab) {
            $cmp = $this->compare($previous_data[$key], $actual_data[$key]);

            if (count($cmp['up']) !== 0 && count($cmp['down']) !== 0) {

                if ($behind = $this->checkDelay($cmp)) {


                    $behind['message'] = sprintf($behind['message'], $tab['master_host'], $tab['ip']);
//debug($behind);

                    $data = array();
                    $data['mysql_event']['id_mysql_server'] = $tab['id'];
                    $data['mysql_event']['date'] = date("Y-m-d H:i:s");
                    $data['mysql_event']['id_mysql_status'] = $behind['id_mysql_status'];
                    $data['mysql_event']['message'] = $behind['message'];
                    $data['mysql_event']['serialized'] = json_encode($cmp);


                    if (!$db->sql_save($data)) {
                        debug($data);
                        debug($db->sql_error());
                    }
                }
            }
        }
    }

    /*
     * 1 : replication is up to date
     * 5 : replication out of date
     * 4 : time increasing
     * 3 : time decreasing
     */

    private function checkDelay($data)
    {

        if (!isset($data['down']['update']['time_behind']) || !isset($data['down']['update']['time_behind'])) {
            return false;
        }

// TIME_BEHING_MAX =1        
        $delay_current = $data['down']['update']['time_behind'];
        $delay_last = $data['up']['update']['time_behind'];

        $delay_before = Date::secToTime($delay_last);
        $delay_after = Date::secToTime($delay_current);


        if ($delay_last < self::TIME_BEHING_MAX && $delay_current < self::TIME_BEHING_MAX) {
            return false;
        } elseif ($delay_last >= self::TIME_BEHING_MAX && $delay_current < self::TIME_BEHING_MAX) {
            $data['id_mysql_status'] = 1;
            $data['message'] = "The replication between %s and %s is now up to date [$delay_last:$delay_current]";
        } elseif ($delay_last < self::TIME_BEHING_MAX && $delay_current >= self::TIME_BEHING_MAX) {
            $data['id_mysql_status'] = 5;
            $data['message'] = "The replication between %s and %s is OUT OF DATE (" . $delay_after . " sec) [$delay_last:$delay_current]";
        } elseif ($delay_last >= self::TIME_BEHING_MAX && $delay_current >= self::TIME_BEHING_MAX) {

            if ($delay_last < $delay_current) {
                $data['id_mysql_status'] = 4;
                $data['message'] = "The replication between %s and %s is INCREASING (" . $delay_after . " sec) [$delay_last:$delay_current]";
            } else {
                $data['id_mysql_status'] = 3;
                $data['message'] = "The replication between %s and %s is DECREASING (" . $delay_after . " sec) [$delay_last:$delay_current]";
            }
        } else {
            $data['id_mysql_status'] = 10;
        }



        return $data;
    }

    private function saveVariable()
    {
        $default = Sgbd::sql(DB_DEFAULT);

        $sql = "SELECT id,name FROM mysql_server";

        foreach ($default->sql_fetch_yield($sql) as $tab) {

            $db = $tab['name'];

            $server_config = $this->di['db']->getParam($db);

            if ($server_config['driver'] === "mysql") {

                $dblink = Sgbd::sql($db);

                if ($dblink->is_connected) {


                    /*
                      $sql = "(SELECT VARIABLE_NAME,VARIABLE_VALUE FROM INFORMATION_SCHEMA.GLOBAL_VARIABLES) ";
                      $sql .= " UNION (SELECT VARIABLE_NAME,VARIABLE_VALUE FROM INFORMATION_SCHEMA.GLOBAL_STATUS)  order by VARIABLE_NAME";

                      $res2 = $dblink->sql_query($sql);
                     */



                    $status = $dblink->getStatus();
                    $variables = $dblink->getVariables();

                    $allvar = array_merge($status, $variables);


                    foreach ($allvar as $key => $value) {
                        $data = array();
                        $data['mysql_variable']['id_mysql_server'] = $tab['id'];
                        $data['mysql_variable']['date'] = date("Y-m-d H:i:s");
                        $data['mysql_variable']['name'] = $key;
                        $data['mysql_variable']['value'] = $value;

                        $saved = $default->sql_save($data);


                        if (!$saved) {
                            debug($default->sql_error());
                            debug($data);
                            die();
                        }
                    }
                }
            }
        }
    }

    public function playback($param)
    {
        
    }

    public function extractLog($param)
    {
        
    }

    private function getServerWithSandBox($db)
    {

        $ip_sand_box = array();
        $sql = "select a.`ip`, count(1) as cpt 
            FROM `mysql_server` a
            INNER JOIN mysql_replication_stats b ON a.id = b.id_mysql_server
            GROUP BY a.ip HAVING count(1) > 1";
        $res = $db->sql_query($sql);
        while ($ob = $db->sql_fetch_object($res)) {
            $ip_sand_box[] = $ob->ip;
        }

        return $ip_sand_box;
    }

    private function displayNode($data)
    {
        
    }

    private function deleteBackup()
    {
        $cmd = "find /data/backup/ -type d -empty -delete";
        shell_exec($cmd);

        $cmd = "find /data/backup/10.*/* -type f -mtime +10 -exec rm {} \;";
        shell_exec($cmd);
    }

    private function saveDatabase($db, $id_mysql_server, $binlog, $replicate)
    {
        $default = Sgbd::sql(DB_DEFAULT);


        $binlog_do_db = explode(",", $binlog['Binlog_Do_DB']);
        $binlog_ignore_db = explode(",", $binlog['Binlog_Ignore_DB']);

        //$sql = "DELETE FROM mysql_database where id_mysql_server = '" . $id_mysql_server . "';";
        //$default->sql_query($sql);
        //echo $sql;

        $sql = "SELECT * FROM `information_schema`.`SCHEMATA`";
        $databases = $db->sql_fetch_yield($sql);

        foreach ($databases as $database) {

            $sql = "SELECT id FROM mysql_database WHERE `name` ='" . $database['SCHEMA_NAME'] . "' AND id_mysql_server=" . $id_mysql_server . ";";
            $res = $default->sql_query($sql);

            $data = array();

            if ($default->sql_num_rows($res) === 1) {
                $ob = $default->sql_fetch_object($res);
                $data['mysql_database']['id'] = $ob->id;
            }

            $data['mysql_database']['id_mysql_server'] = $id_mysql_server;
            $data['mysql_database']['name'] = $database['SCHEMA_NAME'];
            $data['mysql_database']['collation_name'] = $database['DEFAULT_COLLATION_NAME'];
            $data['mysql_database']['character_set_name'] = $database['DEFAULT_CHARACTER_SET_NAME'];
            $data['mysql_database']['binlog_do_db'] = (in_array($database['SCHEMA_NAME'], $binlog_do_db)) ? 1 : 0;
            $data['mysql_database']['binlog_ignore_db'] = (in_array($database['SCHEMA_NAME'], $binlog_ignore_db)) ? 1 : 0;

            $saved = $default->sql_save($data);

            if (!$saved) {
                debug($default->sql_error());
                debug($data);
                die();
            } else {

                if (!empty($replicate['id_mysql_replication_thread'])) {
                    $id_mysql_database = $default->sql_insert_id();

                    $replicate_do_db = explode(",", $replicate['Replicate_Do_DB']);
                    $replicate_ignore_db = explode(",", $replicate['Replicate_Ignore_DB']);


                    $data = array();
                    $data['link__mysql_database__mysql_replication_thread']['id_mysql_database'] = $id_mysql_database;
                    $data['link__mysql_database__mysql_replication_thread']['id_mysql_replication_thread'] = $replicate['id_mysql_replication_thread'];
                    $data['link__mysql_database__mysql_replication_thread']['replicate_do_db'] = (in_array($database['SCHEMA_NAME'], $replicate_do_db)) ? 1 : 0;
                    $data['link__mysql_database__mysql_replication_thread']['replicate_ignore_db'] = (in_array($database['SCHEMA_NAME'], $replicate_ignore_db)) ? 1 : 0;

                    $saved = $default->sql_replace($data);
                    if (!$saved) {
                        debug($default->sql_error());
                        debug($data);
                        die();
                    }
                }
            }
        }
    }

    private function saveReplicateDb($id_mysql_database)
    {
        
    }

    private function displayServer(&$fp, $data)
    {
        $color = ['blue', 'red', 'green'];

        if (!in_array($data['color'], $color)) {
            throw new Exception("PMACLI-085 Impossible to get the color !");
        }
        fwrite($fp, "\t node [color=" . $data['color'] . "];" . PHP_EOL);

        fwrite($fp, '  "' . $data['id_mysql_server'] . '" [style="" penwidth="3" fillcolor="yellow" fontname="arial" label =<<table border="0" cellborder="0" cellspacing="0" cellpadding="2" bgcolor="white"><tr><td bgcolor="black" color="white" align="center" href="' . LINK . 'monitoring/query/' . str_replace('_', '-', $data['hostname']) . '/' . '"><font color="white">' . str_replace('_', '-', $data['hostname']) . '</font></td></tr><tr><td bgcolor="grey" align="left">' . $data['ip'] . ':' . $data['port'] . '</td></tr>');
        fwrite($fp, '<tr><td bgcolor="grey" align="left">' . $data['version'] . '</td></tr>' . PHP_EOL);
        fwrite($fp, '<tr><td bgcolor="grey" align="left">Uptime : ' . Date::secToTime($data['uptime']) . '</td></tr>');
        fwrite($fp, '<tr><td bgcolor="grey" align="left">(' . $data['date'] . ') : ' . $data['timezone'] . '</td></tr>');
        fwrite($fp, '<tr><td bgcolor="grey" align="left">Binlog format : ' . $data['binlog_format'] . '</td></tr>');
//fwrite($fp, '<tr><td bgcolor="red" align="left">Date : <b>' . $ob->date.'</b></td></tr>');
        // DATABASES


        fwrite($fp, '</table>> ];' . PHP_EOL);
    }

    private function displayDatabase($id, $hostname)
    {
        $db = Sgbd::sql(DB_DEFAULT);
        $ret = '';

        if (!empty($id)) {
            $sql = "SELECT * FROM mysql_database a"
                    . " LEFT JOIN link__mysql_database__mysql_replication_thread b ON a.id = b.id_mysql_database "
                    . "WHERE a.id_mysql_server='" . $id . "' order by a.name";
            $res = $db->sql_query($sql);

            if ($db->sql_num_rows($res) > 0) {
                $ret .= '<table border="0" cellborder="0" cellspacing="1" cellpadding="1">';

                $ret .= '<tr><td bgcolor="#eeeeee">M</td><td bgcolor="#eeeeee">S</td>'
                        . '<td bgcolor="#dddddd" align="left">Databases</td></tr>' . PHP_EOL;

                while ($database = $db->sql_fetch_array($res)) {

                    $binlog = (empty($database['binlog_do_db'])) ? "" : "&#10004;";


                    if (empty($binlog)) {
                        $binlog = (empty($database['binlog_ignore_db'])) ? "-" : "&#10006;";
                    }

                    $replicate = (empty($database['replicate_ignore_db'])) ? "" : "&#10006;";

                    if (empty($replicate)) {
                        $replicate = (empty($database['replicate_do_db'])) ? "-" : "&#10004;";
                    }

                    $ret .= '<tr><td bgcolor="#eeeeee">' . $binlog . '</td><td bgcolor="#eeeeee">' . $replicate . '</td>'
                            . '<td bgcolor="#dddddd" align="left" title="MPD of ' . $database['name'] . '" href="' . LINK . 'mysql/mpd/' .
                            str_replace('_', '-', $hostname) . '/' . $database['name'] . '">' . $database['name'] . '</td></tr>' . PHP_EOL;
                }
                $ret .= '</table>';
            }
        } else {
            throw new \Exception('PMACLI-072 Impossible to get id of server !');
        }

        return $ret;
    }

    function influxDB()
    {
        $client = new \crodas\InfluxPHP\Client(
                "localhost" /* default */, 8086 /* default */, "root" /* by default */, "root" /* by default */
        );
    }

}
