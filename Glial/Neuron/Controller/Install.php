<?php

namespace Glial\Neuron\Controller;

use \Glial\Cli\Glial;
use \Glial\Cli\Color;
use \Glial\Security\Crypt\Crypt;
use \Glial\Synapse\Config;
use \Glial\Sgbd\Sgbd;
use \Monolog\Logger;
use \Monolog\Formatter\LineFormatter;
use \Monolog\Handler\StreamHandler;

trait Install
{

    function all()
    {
        $this->index();
    }

    function index()
    {
        //remove view 
        $this->view = false;

        //to make sexy install ?
        //header
        echo PHP_EOL.Glial::header().PHP_EOL;

        $this->generate_key();
        $this->cadre("Select MySQL server for Glial");
        $server = $this->testMysqlServer();


        $this->updateConfig($server);
        usleep(1000);


        $config = new Config;
        $config->load(CONFIG);

        $db = $config->get("db");



        $log = new Logger('Glial');


        $file_log = LOG_FILE;

        $handler = new StreamHandler($file_log, Logger::DEBUG);
        $handler->setFormatter(new LineFormatter(null, null, false, true));
        $log->pushHandler($handler);

        Sgbd::setConfig($db);
        Sgbd::setLogger($log);


        $_DB = Sgbd::sql(DB_DEFAULT);


        $log = new Logger('Glial');


        $file_log = TMP.'log/glial.log';

        $handler = new StreamHandler($file_log, Logger::NOTICE);
        $handler->setFormatter(new LineFormatter(null, null, false, true));
        $log->pushHandler($handler);

        $_DB->setLogger($log);

        $this->installLanguage($_DB);

        $this->importData($server);
        $this->updateCache();
        $this->cmd("echo 1", "Testing system & configuration");

    }

    public function composer()
    {
        $this->view = false;
        echo PHP_EOL.Glial::header().PHP_EOL;

        echo "Source files successfully imported !".PHP_EOL;
        echo "To add databases edit the file : '".$_SERVER['PWD']."/configuration/db.config.ini.php'".PHP_EOL;
        echo "To finish install run : '".Color::getColoredString("cd ".$_SERVER['PWD']."; php glial install all", "purple")."'".PHP_EOL;
    }

    public function out($msg, $type)
    {
        switch ($type) {
            case 'OK':
                $status = Color::getColoredString("OK", "green");
                break;

            case 'KO':
                $status = Color::getColoredString("KO", "red");
                $msg    = Color::getColoredString($msg, "red");
                $err    = true;
                break;
            case 'NA': $status = Color::getColoredString("!!", "blue");
                break;

            default:
                throw new \Exception("GLI-024 : Arguement '".$msg.$type."' not valid {OK|KO|NA}", 21);
        }


        $msg .= " ";

        $size = strlen(Color::strip($msg));
        if ($size < 0) {
            $size = 0;
        }

        $ret = $msg.str_repeat(".", 73 - $size)." [ ".$status." ]".PHP_EOL;


        if (!empty($err)) {
            echo $ret;
            $this->onError();
        }

        return $ret;
    }

    public function onError()
    {

        echo PHP_EOL."To understand what happen : ".Color::getColoredString($_SERVER['PWD']."/tmp/log/error_php.log", "cyan").PHP_EOL;
        echo "To resume the setup : ".Color::getColoredString("php composer.phar update", "cyan").PHP_EOL;
        exit(10);
    }

    public function cmd($cmd, $msg)
    {
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

        echo $this->displayResult($msg, $fine);
    }

    public function anonymous($function, $msg)
    {
        list($fine, $message) = $function($msg);

        echo $this->out($message, $fine);
    }

    function testPhpComponent($ext)
    {

        // test php version
        $fct = function($msg) {
            $err     = "";
            $version = '5.5.10';
            if (version_compare(PHP_VERSION, $version, '<')) {
                $err = " (Should be highter than : ".$version.")";

                $msg .= $err;
            }

            return array(true, $msg);
        };
        $this->anonymous($fct, "Check PHP version : ".PHP_VERSION);

        //test all extention php required
        $extentions = array_merge($ext, array('gd', 'curl', 'phar'));

        foreach ($extentions as $ext) {

            $fct = function($msg) use ($ext) {

                return array(extension_loaded($ext), $msg);
            };
            $this->anonymous($fct, "Check PHP extention : ".$ext);
        }
    }

    public function testDatabases()
    {

        $drivers = array();

        foreach ($this->di['db']->getAll() as $name) {
            try {
                $ret = Sgbd::sql($name);
                echo $this->out("Connected to database : $name", "OK");
            } catch (\Exception $ex) {
                echo $this->out($ex->getMessage(), "KO");
            }

            $drivers[] = $this->di['db']->getParam($name)['driver'];
        }

        $drivers = array_unique($drivers);
        return $drivers;
    }

    private function generate_key()
    {

        $key = str_replace("'", "", $this->rand_char(256));


        $data = "<?php

if (! defined('CRYPT_KEY'))
{
    define('CRYPT_KEY', '".$key."');
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

    private function testMysqlServer()
    {

        $good = false;
        do {
            echo "Name of connection into configuration/db.config.ini.php : [glial]\n";

            $hostname = trim($this->prompt('Hostname/IP of MySQL [default : 127.0.0.1] : '));
            $port     = trim($this->prompt('Port of MySQL        [default : 3306]      : '));

            if (empty($port)) {
                $port = 3306;
            }
            if (empty($hostname)) {
                $hostname = "127.0.0.1";
            }

            $fp = @fsockopen($hostname, $port, $errno, $errstr, 30);
            if (!$fp) {
                echo Color::getColoredString("$errstr ($errno)", "grey", "red")."\n";
                echo "MySQL server : ".$hostname.":".$port." -> ".Color::getColoredString("KO", "grey", "red")."\n";
                echo str_repeat("-", 80)."\n";
            } else {
                $this->cmd("echo 1", "MySQL server : ".$hostname.":".$port." available");

                fclose($fp);
                $good = true;
            }
        } while ($good === false);



        //login & password mysql
        $good = false;

        do {
            echo "MySQL account on (".$hostname.":".$port.")\n";

            $user = readline('User     [default : root]    : ');
            $password = readline('Password [default : (empty)] : ');

            if (empty($user)) {
                $user = "root";
            }

            $link = @mysqli_connect($hostname, $user, trim($password), "mysql", $port);

            if ($link) {
                $good = true;
                $this->cmd("echo 1", "Login/password for MySQL's server");
            } else {
                echo Color::getColoredString('Connect Error ('.mysqli_connect_errno().') '.mysqli_connect_error(), "grey", "red")."\n";
                //echo "credential (".$user." // ".$password.")\n";
                echo str_repeat("-", 80)."\n";
            }

            sleep(1);
        } while ($good === false);

        wrong_db:
        $good = false;
        do {
            echo "Name of database who will be used by Glial\n";

            $database = readline('Database     [default : glial]    : ');

            if (empty($database)) {
                $database = "glial";
            }

            $sql    = "SELECT count(1) as cpt FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '".mysqli_real_escape_string($link, $database)."'";
            $result = mysqli_query($link, $sql);

            $ob = mysqli_fetch_object($result);

            if ($ob->cpt == "1") {
                echo Color::getColoredString('Database -> KO (this database already exist)', "grey", "red")."\n";
                echo str_repeat("-", 80)."\n";
            } else {
                $good = true;
                $this->cmd("echo 1", "Database's name");
            }
        } while ($good === false);

        //create database

        $sql = "CREATE DATABASE ".mysqli_real_escape_string($link, $database)."";
        $res = mysqli_query($link, $sql);

        if ($res) {


            $this->cmd("echo 1", 'The database "'.mysqli_real_escape_string($link, $database).'" has been created');
        } else {
            echo Color::getColoredString('The database "'.mysqli_real_escape_string($link, $database).'" couldn\'t be created', "black", "red")."\n";
            goto wrong_db;
            echo str_repeat("-", 80)."\n";
        }


        Crypt::$key = CRYPT_KEY;

        $passwd = Crypt::encrypt($password);




        $mysql['hostname'] = $hostname;
        $mysql['port']     = $port;
        $mysql['user']     = $user;
        $mysql['password'] = $passwd;
        $mysql['database'] = $database;
        return $mysql;
    }

    private function rand_char($length)
    {
        $random = '';
        for ($i = 0; $i < $length; $i++) {
            $random .= chr(mt_rand(33, 126));
        }
        return $random;
    }

    function displayResult($msg, $fine)
    {
        echo $this->out(Color::getColoredString("[".date("Y-m-d H:i:s")."] ", "purple").$msg, $fine);
    }

    private function cadre($text, $elem = '#')
    {
        echo str_repeat($elem, 80)."\n";
        echo $elem.str_repeat(' ', ceil((80 - strlen($text) - 2) / 2)).$text.str_repeat(' ', floor((80 - strlen($text) - 2) / 2)).$elem."\n";
        echo str_repeat($elem, 80)."\n";
    }

    private function prompt($test)
    {
        echo $test;
        $handle = fopen("php://stdin", "r");
        $line   = fgets($handle);
        return $line;
    }

    private function updateConfig($server)
    {
        //update DB config

        $config = "
;[name_of_connection] => will be acceded in framework with \Sgbd::sql('name_of_connection')->method()
;driver => list of SGBD avaible {mysql, pgsql, sybase, oracle}
;hostname => server_name of ip of server SGBD (better to put localhost or real IP)
;user => user who will be used to connect to the SGBD
;password => password who will be used to connect to the SGBD
;database => database / schema witch will be used to access to datas

[glial]
driver=mysql
hostname=".$server["hostname"]."
user=".$server['user']."
password='".$server['password']."'
crypted='1'
database=".$server['database']."";

        $fp = fopen(CONFIG."/db.config.ini.php", 'w');
        fwrite($fp, $config);
        fclose($fp);

        $this->cmd("echo 1", "Generate config file for DB");
    }

    public function installLanguage($db)
    {


        \Glial\I18n\I18n::injectDb($db);
        \Glial\I18n\I18n::install();
    }

    private function importData($server)
    {
        //$path = ROOT."/sql/*.sql";
        $path = ROOT."/sql/full/glial.sql";


        Crypt::$key         = CRYPT_KEY;
        $server['password'] = Crypt::decrypt($server['password']);

        foreach (glob($path) as $filename) {
            //echo "$filename size ".filesize($filename)."\n";
            $cmd = "mysql -h ".$server["hostname"]." -u ".$server['user']." -P ".$server['port']." -p'".$server['password']."' ".$server['database']." < ".$filename."";
            $this->cmd($cmd, "Loading ".pathinfo($filename)['basename']);
        }
    }

    private function updateCache()
    {
        $this->cmd("php glial administration admin_index_unique", "Generating DDL cash for index");
        $this->cmd("php glial administration admin_table", "Generating DDL cash for databases");
        $this->cmd("php glial administration generate_model", "Making model with reverse engineering of databases");
    }

    public function createOrganisation()
    {
        $this->view = false;
        $DB         = Sgbd::sql(DB_DEFAULT);

        createOragnisation:
        $this->cadre("Create organisation");

        do {
            $organization = readline('Your Organization : ');
        } while (strlen($organization) < 3);


        $sql = "INSERT INTO client (`id`,`libelle`,`date`) VALUES (1,'".$oraganisation."', '".date('Y-m-d H:i:s')."')";
        $DB->sql_query($sql);
    }

    public function createAdmin()
    {
        $this->view = false;

        /*
         * email
         * first name
         * last name
         * country
         * city
         * password
         * password repeat
         */

        createUser:
        $this->cadre("create administrator user");

        $email_is_valid = false;
        do {
            $email = readline('Your email : ');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->displayResult("This email considered as valid !", "KO");
            } else {
                $this->displayResult("This email considered as valid !", "OK");

                $email_is_valid = true;
                /*
                  $domain = explode('@', $email)[1];
                  if (checkdnsrr($domain, 'MX')) {

                  $this->displayResult("This MX records exists !", "OK");
                  $email_is_valid = true;
                  } else {
                  $this->displayResult("This MX records exists !", "KO");
                  }
                 */
            }
        } while ($email_is_valid === false);


        $login = readline('Your login : ');
        //first name
        $firstname = readline('Your firstname : ');


        //last name
        $lastname = readline('Your lastname : ');
        


        //country
        $sql = "SELECT libelle FROM geolocalisation_country where libelle != '' ORDER BY libelle";
        $DB  = Sgbd::sql(DB_DEFAULT);

        $res     = $DB->sql_query($sql);
        $country = [];
        while ($ob      = $DB->sql_fetch_object($res)) {
            $country[] = $ob->libelle;
        }

        do {
            $country2 = readline('Your country [First letter in upper case, then tab for help] : ');


            $sql = "select id from geolocalisation_country where libelle = '".$DB->sql_real_escape_string($country2)."'";
            $res = $DB->sql_query($sql);


            if ($DB->sql_num_rows($res) == 1) {
                $ob         = $DB->sql_fetch_object($res);
                $id_country = $ob->id;
                $this->displayResult("Country found in database !", "OK");
            } else {
                $this->displayResult("Country found in database !", "KO");
            }
        } while ($DB->sql_num_rows($res) != 1);


        //city
        $sql = "SELECT libelle FROM geolocalisation_city where id_geolocalisation_country = '".$id_country."' ORDER BY libelle";
        $DB  = Sgbd::sql(DB_DEFAULT);

        $res  = $DB->sql_query($sql);
        $city = [];
        while ($ob   = $DB->sql_fetch_object($res)) {
            $city[] = $ob->libelle;
        }

        do {
            $city2 = readLine('Your city [First letter in upper case, then tab for help] : ');

            $sql = "select id from geolocalisation_city where libelle = '".$DB->sql_real_escape_string($city2)."'";
            $res = $DB->sql_query($sql);

            if ($DB->sql_num_rows($res) == 1) {
                $ob      = $DB->sql_fetch_object($res);
                $id_city = $ob->id;
                $this->displayResult("City found in database !", "OK");
            } else {
                $this->displayResult("City found in database !", "KO");
            }
        } while ($DB->sql_num_rows($res) != 1);


        //password


        $good = false;
        do {
            $pwd  = readline('Password : ');
            $pwd2 = readline('Password (repeat) : ');

            if (!empty($pwd) && $pwd === $pwd2) {
                $good = true;
                $this->displayResult("The passwords must be the same & not empty", "OK");
            } else {
                $this->displayResult("The passwords must be the same & not empty", "KO");
            }
        } while ($good !== true);


        //$ip = trim(@file_get_contents("http://icanhazip.com")); //to much slow

        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip = "127.0.0.1";
        }

        $data['user_main']['is_valid'] = 1;
        $data['user_main']['email']    = $email;
        $data['user_main']['login']    = $email;
        $data['user_main']['password'] = \Glial\Auth\Auth::hashPassword($email, $pwd);

        //to set uppercase to composed name like 'Jean-Louis'
        $firstname = str_replace("-", " - ", $firstname);
        $firstname = mb_convert_case($firstname, MB_CASE_TITLE, "UTF-8");

        $data['user_main']['firstname'] = str_replace(" - ", "-", $firstname);

        $data['user_main']['name']                       = mb_convert_case($lastname, MB_CASE_UPPER, "UTF-8");
        $data['user_main']['ip']                         = $ip;
        $data['user_main']['date_created']               = date('Y-m-d H:i:s');
        $data['user_main']['id_group']                   = 4; // 4 = super admin
        $data['user_main']['id_geolocalisation_country'] = $id_country;
        $data['user_main']['id_geolocalisation_city']    = $id_city;
        $data['user_main']['id_company']                 = 1;
        $data['user_main']['date_last_login']            = date('Y-m-d H:i:s');
        $data['user_main']['date_last_connected']        = date('Y-m-d H:i:s');
        $data['user_main']['key_auth']                   = '';

        $id_user = $DB->sql_save($data);

        if ($id_user) {
            $this->displayResult("Admin account successfully created", "OK");
        } else {
            print_r($data);
            $error = $DB->sql_error();
            print_r($error);

            $this->displayResult("Admin account successfully created", "KO");

            goto createUser;
        }

        echo Color::getColoredString("\nAdministrator successfully created !\n", "green");
    }

    public function success()
    {

        $this->view = false;

        echo Color::getColoredString("New project powered by ".Glial::name()." ".Glial::version()." has been successfully installed !\n", "green");
        echo "\n";

        $ip_list = shell_exec('ifconfig -a | grep "inet ad" | cut -d ":" -f 2 | cut -d " " -f 1');

        $ips = explode("\n", $ip_list);


        echo "You can connect to the application on this url : \n";

        foreach ($ips as $ip) {
            if (empty($ip)) {
                continue;
            }

            echo "\t - ".Color::getColoredString("http://".$ip.WWW_ROOT, "yellow")."\n";
        }
        echo "\t - ".Color::getColoredString("http://".gethostname().WWW_ROOT, "yellow")."\n";


        echo "Install completed !";
    }
}