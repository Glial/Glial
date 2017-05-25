<?php

namespace Glial\Neuron\Controller;

use \Glial\Cli\Glial;
use \Glial\Cli\Color;

trait Install {

    function all() {
        $this->index();
    }

    function index() {
        //remove view 
        $this->view = false;

        //to make sexy install ?
        //header
        echo PHP_EOL . Glial::header() . PHP_EOL;



        $this->generate_key();


        $this->cadre("Select MySQL server for Glial");
        $server = $this->testMysqlServer();



//		ini_set('display_errors', '0');
        $drivers = $this->testDatabases();
        ini_set('display_errors', '1');

        $map_driver_with_ext = array(
            "mysql" => "mysqli",
            "pgsql" => "pgsql",
            "sybase" => "sybase",
            "oracle" => "oci8");

        $ext = array();
        foreach ($drivers as $driver) {
            $ext[] = $map_driver_with_ext[$driver];
        }

        $this->testPhpComponent($ext);


        //making tree directory
        $fct = function($msg) {
            $dirs = array("data", "data/img", "documentation", "tmp/crop", "tmp/documentation", "application/webroot/js",
                "application/webroot/css", "application/webroot/file", "application/webroot/video", "application/webroot/image");

            $error = array();
            foreach ($dirs as $dir) {

                $dir = $_SERVER['PWD'] . "/" . $dir;

                if (!file_exists($dir)) {
                    if (!mkdir($dir)) {
                        echo $this->out("Impossible to create this directory : " . $dir . " ", "KO");
                    }
                }
            }

            return array(true, $msg);
        };
        $this->anonymous($fct, "Making tree directory");


        // replace and install lastest jQuery
        $fct = function ($msg) {
            $name = "jquery-latest.min.js";
            $jQuery = $_SERVER['PWD'] . "/application/webroot/js/" . $name;

            $old_version = "";
            if (file_exists($jQuery)) {
                $data = file_get_contents($jQuery);
                preg_match("/v[\d]+\.[\d]+\.[\d]+/", $data, $version);

                $old_version = $version[0] . " => ";
                $this->cmd("rm " . $jQuery, "Delete old jQuery");
            }

            $this->cmd("cd " . $_SERVER['PWD'] . "/application/webroot/js && wget -q http://code.jquery.com/" . $name, "Download lastest jQuery");

            if (file_exists($jQuery)) {
                $data = file_get_contents($jQuery);

                preg_match("/v[\d]+\.[\d]+\.[\d]+/", $data, $version);

                $msg = sprintf($msg, $old_version . Color::getColoredString($version[0], "green"));

                return array(true, $msg);
            } else {
                $msg = sprintf($msg, "NOT INSTALLED");
                return array(false, $msg);
            }
        };
        $this->anonymous($fct, "jQuery installed (%s)");


        //$this->cmd("chown www-data:www-data -R *", "Setting right to www-data:www-data");


        $this->cmd("php glial administration admin_index_unique", "Generating DDL cash for index");
        $this->cmd("php glial administration admin_table", "Generating DDL cash for databases");
        $this->cmd("php glial administration generate_model", "Making model with reverse engineering of databases");


        $fct = function ($msg) {
            $file = $_SERVER['PWD'] . "/glial";
            $data = file_get_contents($file);

            $new_data = str_replace("php application", "php " . $_SERVER['PWD'] . "/application", $data);
            if (!file_put_contents($file, $new_data)) {
                return array(false, $msg);
            }
            return array(true, $msg);
        };

        $this->anonymous($fct, "Replace relative path by full path in Glial exec");

        $fct = function ($msg) {

            $file = $_SERVER['PWD'] . "/glial";
            $path_to_php = exec("which php", $res, $code);

            if ($code !== 0) {
                return array(false, $msg . " $code:$path_to_php: can't find php");
            }

            $data = file($file);
            $data[0] = "#!" . $path_to_php . PHP_EOL;
            file_put_contents($file, implode("", $data));

            return array(true, $msg);
        };

        $this->anonymous($fct, "get full path of php");

        $this->cmd("chmod +x glial", "Setting chmod +x to executable 'glial'");
        $this->cmd("cp -a glial /usr/local/bin/glial", "Copy glial to /usr/local/bin/");





        \Glial\I18n\I18n::install();
        // \Glial\I18n\I18n::unInstall();


        /*
          shell_exec("find " . $_SERVER['PWD'] . " -type f -exec chmod 740 {} \;;");
          echo $this->out("Setting chmod 440 to all files", "OK");

          shell_exec("find " . $_SERVER['PWD'] . " -type d -exec chmod 750 {} \;;");
          echo $this->out("Setting chmod 550 to all files", "OK");


          shell_exec("find " . $_SERVER['PWD'] . "/tmp -type f -exec chmod 770 {} \;;");
          echo $this->out("Setting chmod 660 to all files of /tmp", "OK");

          shell_exec("find " . $_SERVER['PWD'] . "/tmp -type d -exec chmod 770 {} \;;");
          echo $this->out("Setting chmod 660 to all directory of /tmp", "OK");

         */




        //echo $this->di['acl'];
        //echo $this->di['db'];
    }

    public function composer() {
        $this->view = false;
        echo PHP_EOL . Glial::header() . PHP_EOL;

        echo "Source files successfully imported !" . PHP_EOL;
        echo "To add databases edit the file : '" . $_SERVER['PWD'] . "/configuration/db.config.ini.php'" . PHP_EOL;
        echo "To finish install run : '" . Color::getColoredString("cd " . $_SERVER['PWD'] . "; php glial install all", "purple") . "'" . PHP_EOL;
    }

    public function out($msg, $type) {
        switch ($type) {
            case 'OK':
                $status = Color::getColoredString("OK", "green");
                break;

            case 'KO':
                $status = Color::getColoredString("KO", "red");
                $msg = Color::getColoredString($msg, "red");
                $err = true;
                break;
            case 'NA': $status = Color::getColoredString("!!", "blue");
                break;

            default:
                throw new \Exception("GLI-024 : Arguement '" . $msg . $type . "' not valid {OK|KO|NA}", 21);
        }


        $msg .= " ";

        $size = strlen(Color::strip($msg));
        if ($size < 0) {
            $size = 0;
        }

        $ret = $msg . str_repeat(".", 80 - $size) . " [ " . $status . " ]" . PHP_EOL;


        if (!empty($err)) {
            echo $ret;
            $this->onError();
        }

        return $ret;
    }

    public function onError() {

        echo PHP_EOL . "To understand what happen : " . Color::getColoredString($_SERVER['PWD'] . "/tmp/log/error_php.log", "cyan") . PHP_EOL;
        echo "To resume the setup : " . Color::getColoredString("php composer.phar update", "cyan") . PHP_EOL;
        exit(10);
    }

    public function cmd($cmd, $msg) {
        $code_retour = 0;


        ob_start();
        passthru($cmd, $code_retour);

        if ($code_retour !== 0) {
            $fine = "KO";
            ob_end_flush();
        } else {
            $fine = "OK";
            ob_end_clean();
        }

        echo $this->out($msg, $fine);
    }

    public function anonymous($function, $msg) {
        list($fine, $message) = $function($msg);

        echo $this->out($message, $fine);
    }

    function testPhpComponent($ext) {

        // test php version
        $fct = function($msg) {
            $err = "";
            $version = '5.5.10';
            if (version_compare(PHP_VERSION, $version, '<')) {
                $err = " (Should be highter than : " . $version . ")";

                $msg .= $err;
            }

            return array(true, $msg);
        };
        $this->anonymous($fct, "Check PHP version : " . PHP_VERSION);

        //test all extention php required
        $extentions = array_merge($ext, array('gd', 'curl', 'phar'));

        foreach ($extentions as $ext) {

            $fct = function($msg) use ($ext) {

                return array(extension_loaded($ext), $msg);
            };
            $this->anonymous($fct, "Check PHP extention : " . $ext);
        }
    }

    public function testDatabases() {

        $drivers = array();

        foreach ($this->di['db']->getAll() as $name) {
            try {
                $ret = $this->di['db']->sql($name);
                echo $this->out("Connected to database : $name", "OK");
            } catch (\Exception $ex) {
                echo $this->out($ex->getMessage(), "KO");
            }

            $drivers[] = $this->di['db']->getParam($name)['driver'];
        }

        $drivers = array_unique($drivers);
        return $drivers;
    }

    private function generate_key() {

        $key = str_replace("'", "", $this->rand_char(256));


        $data = "<?php

if (! defined('CRYPT_KEY'))
{
    define('CRYPT_KEY', '" . $key . "');
}
";
        $path = "configuration/crypt.config.php";


        $msg = "Generate key for encryption";

        if (!file_exists($path)) {
            file_put_contents($path, $data);
            $this->displayResult($msg, "OK");
            require_once $path;
        } else {
            $this->displayResult($msg, "NA");
        }
    }

    private function testMysqlServer() {

        $good = false;
        do {
            echo "Name of connection into configuration/db.config.ini.php : [glial]\n";

            $hostname = trim($this->prompt('Hostname/IP of MySQL [default : 127.0.0.1] : '));
            $port = trim($this->prompt('Port of MySQL        [default : 3306]      : '));

            if (empty($port)) {
                $port = 3306;
            }
            if (empty($hostname)) {
                $hostname = "127.0.0.1";
            }

            $fp = @fsockopen($hostname, $port, $errno, $errstr, 30);
            if (!$fp) {
                echo Color::getColoredString("$errstr ($errno)", "grey", "red") . "\n";
                echo "MySQL server : " . $hostname . ":" . $port . " -> " . Color::getColoredString("KO", "grey", "red") . "\n";
                echo str_repeat("-", 80) . "\n";
            } else {
                $this->cmd("echo 1", "MySQL server : " . $hostname . ":" . $port . " available");

                fclose($fp);
                $good = true;
            }
        } while ($good === false);



        //login & password mysql
        $good = false;

        do {
            echo "MySQL account on (" . $hostname . ":" . $port . ")\n";

            $rl = new \Hoa\Console\Readline\Readline ();
            $user = $rl->readLine('User     [default : root]    : ');

            $rl = new \Hoa\Console\Readline\Password();
            $password = $rl->readLine('Password [default : (empty)] : ');

            if (empty($user)) {
                $user = "root";
            }

            $link = mysqli_connect($hostname . ":" . $port, $user, trim($password));

            if ($link) {
                $good = true;
                $this->cmd("echo 1", "Login/password for MySQL's server");
            } else {
                echo Color::getColoredString('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error(), "grey", "red") . "\n";
                //echo "credential (".$user." // ".$password.")\n";
                echo str_repeat("-", 80) . "\n";
            }

            sleep(1);
        } while ($good === false);




        wrong_db:
        $good = false;
        do {
            echo "Name of database who will be used by Glial\n";


            $rl = new \Hoa\Console\Readline\Readline ();
            $database = $rl->readLine('Database     [default : glial]    : ');

            if (empty($database)) {
                $database = "glial";
            }

            $sql = "SELECT count(1) as cpt FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . mysqli_real_escape_string($link, $database) . "'";
            $result = mysqli_query($link, $sql);

            $ob = mysqli_fetch_object($result);

            if ($ob->cpt == "1") {
                echo Color::getColoredString('Database -> KO (this database already exist)', "grey", "red") . "\n";
                echo str_repeat("-", 80) . "\n";
            } else {
                $good = true;
                $this->cmd("echo 1", "Database's name");
            }
        } while ($good === false);

        //create database

        $sql = "CREATE DATABASE " . mysqli_real_escape_string($link, $database) . "";
        $res = mysqli_query($link, $sql);

        if ($res) {


            $this->cmd("echo 1", 'The database "' . mysqli_real_escape_string($link, $database) . '" has been created');
        } else {
            echo Color::getColoredString('The database "' . mysqli_real_escape_string($link, $database) . '" couldn\'t be created', "black", "red") . "\n";
            goto wrong_db;
            echo str_repeat("-", 80) . "\n";
        }


        Crypt::$key = CRYPT_KEY;

        $passwd = Crypt::encrypt($password);




        $mysql['hostname'] = $hostname;
        $mysql['port'] = $port;
        $mysql['user'] = $user;
        $mysql['password'] = $passwd;
        $mysql['database'] = $database;
        return $mysql;
    }

    private function rand_char($length) {
        $random = '';
        for ($i = 0; $i < $length; $i++) {
            $random .= chr(mt_rand(33, 126));
        }
        return $random;
    }

    function displayResult($msg, $fine) {
        echo $this->out(Color::getColoredString("[" . date("Y-m-d H:i:s") . "] ", "purple") . $msg, $fine);
    }

    private function cadre($text, $elem = '#') {
        echo str_repeat($elem, 80) . "\n";
        echo $elem . str_repeat(' ', ceil((80 - strlen($text) - 2) / 2)) . $text . str_repeat(' ', floor((80 - strlen($text) - 2) / 2)) . $elem . "\n";
        echo str_repeat($elem, 80) . "\n";
    }

    private function prompt($test) {
        echo $test;
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        return $line;
    }

}
