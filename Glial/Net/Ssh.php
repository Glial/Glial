<?php

namespace Glial\Net;

class Ssh
{
    private $connection;
    private $host;
    private $stdio;

    public function __construct($host, $port, $user, $passwd = "", $pulic_key = "", $private_key = "")
    {

        $this->host       = $host;
        $this->connection = ssh2_connect($host, $port);


        if (empty($passwd)) {
            //resource $session , string $username , string $pubkeyfile , string $privkeyfile [, string $passphrase ]
            $ssh = ssh2_auth_pubkey_file($this->connection, $user, $pulic_key, $private_key);
            
        } else if (empty($private_key) || empty($pulic_key)) {

            
            $ssh = ssh2_auth_password ( $this->connection, $user, $passwd);
        }

        if ($ssh) {
            if (!($this->stdio = ssh2_shell($this->connection, "xterm"))) {
                throw new \Exception("GLI-014 : Connexion to ssh impossible on : ".$user."@".$host.":".$port."");
            }
        } else {
            echo "Connexion to ssh impossible on : " . $user . "@" . $host . ":" . $port."\n";
            //throw new \Exception("GLI-014 : Connexion to ssh impossible on : " . $user . "@" . $host . ":" . $port . "");
        }
    }

    public function disconnect()
    {

        //echo "Bye to ".$this->host.PHP_EOL;
        $this->exec('echo "EXITING" && exit;');
        $this->connection = null;
    }

    public function exec($cmd)
    {

        if (!($stream = ssh2_exec($this->connection, $cmd))) {

            //throw new \Exception('SSH command failed');
            return false;
        }
        stream_set_blocking($stream, true);
        $data = "";
        while ($buf  = fread($stream, 4096)) {
            $data .= $buf;
        }
        fclose($stream);
        return $data;
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    public function close()
    {
        
    }
}