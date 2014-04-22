<?php

namespace Glial\Net;

class Ssh {

    private $connection;
    private $host;
    private $stdio;

    public function __construct($host, $port, $user, $passwd) {

        $this->host = $host;
        $this->connection = ssh2_connect($host, $port);

        if (ssh2_auth_password($this->connection, $user, $passwd)) {


            if (!($this->stdio = ssh2_shell($this->connection, "xterm"))) {
                throw new \Exception("GLI-014 : Connexion to ssh impossible on : " . $user . "@" . $host . ":" . $port . "");
            }
        } else {
            throw new \Exception("GLI-014 : Connexion to ssh impossible on : " . $user . "@" . $host . ":" . $port . "");
        }
    }

    public function disconnect() {

        //echo "Bye to ".$this->host.PHP_EOL;
        $this->exec('echo "EXITING" && exit;');
        $this->connection = null;
    }

    public function exec($cmd) {

        if (!($stream = ssh2_exec($this->connection, $cmd))) {
            throw new \Exception('SSH command failed');
        }
        stream_set_blocking($stream, true);
        $data = "";
        while ($buf = fread($stream, 4096)) {
            $data .= $buf;
        }
        fclose($stream);
        return $data;
    }

    public function __destruct() {
        $this->disconnect();
    }

    public function close() {
        
    }

}
