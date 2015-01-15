<?php

namespace Glial\Cli;

class Ssh
{

    const DEBUG_ON = 1;
    const DEBUG_OFF = 0;
    const DEBUG_PARTIAL = 2; //only display cmd

    // debug

    private $debug = 0;
    // SSH Host 
    private $ssh_host;
    // SSH Port 
    private $ssh_port;
    // SSH Server Fingerprint 
    private $ssh_server_fp = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    // SSH Username 
    private $ssh_auth_user;
    // SSH Private Key Passphrase (null == no passphrase) 
    private $ssh_auth_pass;
    // SSH Public Key File 
    private $ssh_auth_pub = '/home/username/.ssh/id_rsa.pub';
    // SSH Private Key File 
    private $ssh_auth_priv = '/home/username/.ssh/id_rsa';
    // SSH Connection 
    private $connection;
    private $stdio;

    static public function testAccount($host, $port, $login, $password)
    {
        $connection = ssh2_connect($host, $port);

        if (ssh2_auth_password($connection, $login, $password)) {
            return true;
        } else {
            return false;
        }
    }

    public function __construct($host, $port, $login, $password)
    {
        $this->ssh_host = $host;
        $this->ssh_port = $port;
        $this->ssh_auth_user = $login;
        $this->ssh_auth_pass = $password;
    }

    public function connect($debug = self::DEBUG_OFF)
    {

        $this->debug = $debug;

        if (!($this->connection = @ssh2_connect($this->ssh_host, $this->ssh_port))) {

            return false;
            //throw new \Exception('Cannot connect to server');
        }

        /*
          $fingerprint = ssh2_fingerprint($this->connection, SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX);

          if (strcmp($this->ssh_server_fp, $fingerprint) !== 0) {
          throw new Exception('Unable to verify server identity!');
          } */

        /*
          if (!ssh2_auth_pubkey_file($this->connection, $this->ssh_auth_user, $this->ssh_auth_pub, $this->ssh_auth_priv, $this->ssh_auth_pass)) {
          throw new Exception('Autentication rejected by server');
          } */


        if (!@ssh2_auth_password($this->connection, $this->ssh_auth_user, $this->ssh_auth_pass)) {
            return false;
        }

        return true;
    }

    public function exec($cmd)
    {
        if (!($stream = @ssh2_exec($this->connection, $cmd))) {

            return false;
            //throw new \Exception('GLI-885 : SSH command failed');
        }
        stream_set_blocking($stream, true);
        $data = "";
        while ($buf = fread($stream, 4096)) {
            $data .= $buf;
        }
        fclose($stream);
        return $data;
    }

    public function disconnect()
    {
        $this->exec('echo "EXITING" && exit;');
        $this->connection = null;
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    public function scp($cmd, $password, $password2)
    {
        
    }

    public function getconnection()
    {
        return $this->connection;
    }

    public function shellCmd($cmd)
    {
        if ($this->debug === self::DEBUG_PARTIAL) {
            echo $cmd."\n";
        }

        fwrite($this->stdio, $cmd . "\n");
    }

    static public function getRegexPrompt()
    {
        // best tools => http://www.regexper.com/
        //[alequoy@SEQ-DWS-02 ~]
        
        
        //return '/[\w-\d_-]+@[\w\d_-]+:[\~]?(?:\/[\w-\d_-]+)*(?:\$|\#)[\s]?/';
        
        return '/(?:[\w-\d_-]+@[\w\d_-]+:[\~]?(?:\/[\w-\d_-]+)*(?:\$|\#)[\s]?)|(?:\[(?:[\d\w-_]+)@(?:[\d\w-_]+)\s+\~?\](?:\$|\#)\s*)/';
        
        
        
        
    }

    public function waitPrompt($testPhrase = '')
    {
        $regex = self::getRegexPrompt();
        $wait = true;
        do {
            $buffer = fgets($this->stdio);
            // add pause if nothing chose waiting prompt
            if (empty($buffer)) {

                if ($this->debug === self::DEBUG_ON) {
                    echo " [Waiting] ";
                }
                sleep(1);
                continue;
            }

            if ($this->debug === self::DEBUG_ON) {
                echo $buffer;
            }


            \preg_match_all(self::getRegexPrompt(), $buffer, $output_array);

            if (count($output_array[0]) === 1) {
                return false;
            }

            if (!empty($testPhrase)) {
                
                //debug($buffer);
                
                \preg_match_all("/" . $testPhrase . "/", $buffer, $output_array);

                if (count($output_array[0]) === 1) {
                    return true;
                }
            }
        } while ($wait);
    }

    public function whereis($cmd)
    {
        $paths = $this->exec("whereis " . $cmd);
        return trim(explode(" ", trim(explode(":", $paths)[1]))[0]);
    }

    public function testPrompt($line)
    {
        $output_array = [];

        \preg_match_all(self::getRegexPrompt(), $line, $output_array);

        if (count($output_array[0]) === 1) {
            return true;
        } else {
            return false;
        }
    }

    public function userAdd($stdio, $login, $password)
    {

        $cmd = "useradd -ou 0 -g 0 pmacontrol";
        $cmd = "passwd pmacontrol";
    }

    public function openShell()
    {

        if (!($stdio = ssh2_shell($this->connection, "xterm"))) {
            echo "[FAILED] to open a virtual shell\n";
            exit(1);
        }

        echo "Virtual shell opened\n";

        $this->stdio = $stdio;
    }

    public function test()
    {
        
    }

}
