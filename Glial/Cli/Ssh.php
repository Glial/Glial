<?php

namespace Glial\Cli;

class Ssh
{
    static public function testAccount($host, $port, $login, $password)
    {
        $connection = ssh2_connect($host, $port);
        
        if (ssh2_auth_password($connection, $login, $password)) {
            return true;
        } else {
            return false;
        }
    }

}
