<?php

namespace Glial\Neuron\Controller;

use \Glial\Cli\Glial;
use \Glial\Cli\Color;

trait Install {

    function index() {
        //remove view 
        $this->view = false;


        //header
        echo PHP_EOL . Glial::header() . PHP_EOL;


        $this->testPhpComponent();


        //making tree directory
        $fct = function($msg) {
            $dirs = array("data", "data/img", "documentation", "tmp/crop", "tmp/documentation", "application/webroot/js",
                "application/webroot/css", "application/webroot/file", "application/webroot/video", "application/webroot/image");

            $error = array();
            foreach ($dirs as $dir) {

                $dir = $_SERVER['PWD'] . "/" . $dir;

                if (!file_exists($dir)) {
                    if (!mkdir($dir)) {
                        echo $this->out("Impossible to create this directory : " . $key . " ", "KO");
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


        $this->cmd("chown www-data:www-data -R *", "Setting right to www-data:www-data");


        $this->cmd("php glial administration admin_index_unique", "Generating DDL cash for index");
        $this->cmd("php glial administration admin_table", "Generating DDL cash for databases");
        $this->cmd("php glial administration generate_model", "Making model with reverse engineering of databases");


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


        $this->testDatabases();


        //echo $this->di['acl'];
        //echo $this->di['db'];
    }

    function composer() {
        $this->view = false;
        echo PHP_EOL . Glial::header() . PHP_EOL;

        echo "Source files successfuly imported !" . PHP_EOL;
        echo "To finish install run : '" . Color::getColoredString("glial install", "purple") . "'" . PHP_EOL;
    }

    public function out($msg, $type) {
        switch ($type) {
            case 'OK':
            case true: $status = Color::getColoredString("OK", "green");
                break;

            case 'KO':
            case false:
                $status = Color::getColoredString("KO", "red");
                $msg = Color::getColoredString($msg, "red");
                $err = true;
                break;
            case 'NA': $status = Color::getColoredString("!!", "blue");
                break;

            default:
                throw new \Exception("GLI-024 : Arguement '" . $type . "' not valid {OK|KO|NA}", 21);
        }


        $msg .= " ";
        $ret = $msg . str_repeat(".", 76 - strlen(Color::strip($msg))) . " [ " . $status . " ]" . PHP_EOL;


        if (!empty($err)) {
            echo $ret;
            $this->onError();
        }

        return $ret;
    }

    public function onError() {

        echo PHP_EOL . "To understand what happen : " . Color::getColoredString("glial/tmp/log/error_php.log", "cyan") . PHP_EOL;
        echo "To resume the setup : " . Color::getColoredString("php composer.phar update", "cyan") . PHP_EOL;
        exit(10);
    }

    public function cmd($cmd, $msg) {
        $code_retour = 0;


        ob_start();
        passthru($cmd, $code_retour);

        if ($code_retour !== 0) {
            $fine = false;
            ob_end_flush();
        } else {
            $fine = true;
            ob_end_clean();
        }

        echo $this->out($msg, $fine);
    }

    public function anonymous($function, $msg) {
        list($fine, $message) = $function($msg);

        echo $this->out($message, $fine);
    }

    function testPhpComponent() {

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
        $extentions = array('gd', 'mysqli', 'curl', 'ssh2', 'phar');

        foreach ($extentions as $ext) {

            $fct = function($msg) use ($ext) {

                return array(extension_loaded($ext), $msg);
            };
            $this->anonymous($fct, "Check PHP extention : " . $ext);
        }
    }

    function testDatabases() {

        $this->out("checking db.config.php", true);

        foreach ($this->di['db']->connectAll() as $name => $dblink) {
            
            if ($dblink)
            {
                $res = "OK";
            }
            else
            {
                $res = "KO";
            }
            $this->out("Connected to database : $name", $res);
        }
    }

}
