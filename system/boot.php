<?php

/**
 * Basic Gliale functionality.
 *
 * Handles loading of core files needed on every request
 *
 * PHP versions 5.3
 *
 * GLIALE(tm) : Rapid Development Framework (http://gliale.com)
 * Copyright 2008-2012, Esysteme Software Foundation, Inc. (http://www.esysteme.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2007-2010, Esysteme Software Foundation, Inc. (http://www.esysteme.com)
 * @link          http://www.gliale.com GLIALE(tm) Project
 * @package       gliale
 * @subpackage    gliale.app.webroot
 * @since         Gliale(tm) v 0.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
header("Charset: UTF-8");




if (!empty($_GET['path'])) {
    if (stristr($_GET['path'], "bbclone/")) {
        exit;
    }
}

ini_set('error_log', TMP . 'log'.DS.'error_php.log');
ini_set('APACHE_LOG_DIR', TMP . 'log'. DS);

/*
 // dont work again
$paths = array(realpath('/home/www/species/library/'), '.');
set_include_path(implode(PATH_SEPARATOR, $paths));
*/

session_start();
include(LIBRARY . "Glial/debug/debug.php");
include LIB . 'singleton.lib.php';
$_DEBUG = Singleton::getInstance("debug");

$_DEBUG->save("Starting...");

include CORE_PATH . 'basic.php';
include CORE_PATH . 'environement.php';
include CORE_PATH . 'dispatch.php';

include CORE_PATH . 'controller.php';
include CORE_PATH . 'jstree.php'; //TODO to remove from here ASAP !!!!!!!!

include LIB . 'inflector.lib.php';

include LIB . 'language.lib.php';
include LIB . 'sql.lib.php';
include LIB . 'sql' . DS . SQL_DRIVER . '.lib.php';
//include LIB . 'sql' . DS . 'pdo_sqlsrv' . '.lib.php';

include LIB . 'validation.php';
include LIB . 'geoip.lib.php';
include LIB . 'stats.lib.php';
include LIB . 'variable.lib.php';

include_once CONFIG . 'language.config.php';
include_once(CONFIG . 'photo.config.php');




$paths = array(LIBRARY, '.');

set_include_path(implode(PATH_SEPARATOR, $paths));

$_DEBUG->save("Loading class");
$_SQL = Singleton::getInstance(SQL_DRIVER);
$_SQL->sql_connect(SQL_HOSTNAME, SQL_USER, SQL_PASSWORD);
$_SQL->sql_select_db(SQL_DATABASE);
$_DEBUG->save("Connect to database");


if (empty($_SERVER["argc"])) {
    include CORE_PATH . 'router.php';

    $route = new router();
    $route->parse($_GET['path']);
    $url = $route->get_routes();

    if (isset($_GET['lg'])) {
        $_COOKIE['language'] = $_GET['lg'];
        SetCookie("language", $_GET['lg'], time() + 60 * 60 * 24 * 365, "/", $_SERVER['SERVER_NAME'], false, true);
    }
}


include_once(APP_DIR . '/controller/history.controller.php');

$_SQL->get_table_to_history();


$_DEBUG->save("Rooter loaded");

$_LG = Singleton::getInstance("Language");
$GLOBALS['_LG'] = Singleton::getInstance("Language");

$_LG->SetDefault("en");
$_LG->SetSavePath(TMP . "translations");

if (empty($_COOKIE['language'])) {
    $_COOKIE['language'] = "en";
}

$lg = explode(",", LANGUAGE_AVAILABLE);

if (!in_array($_COOKIE['language'], $lg)) {
    die("language error !");
    $_SESSION['URL_404'] = $_SERVER['QUERY_STRING'];
    header("location: " . WWW_ROOT . "en/error/_404/");
}


$_LG->load($_COOKIE['language']);
$_DEBUG->save("Language loaded");
define('LINK', WWW_ROOT . $_LG->Get() . "/");

//mode with php-cli
if (!empty($_SERVER["argc"])) {
    if ($_SERVER["argc"] >= 3) {
        $_SYSTEM['controller'] = $_SERVER["argv"][1];
        $_SYSTEM['action'] = $_SERVER["argv"][2];
        !empty($_SERVER["argv"][3]) ? $_SYSTEM['param'] = $_SERVER["argv"][3] : $_SYSTEM['param'] = '';
    } else {
        echo "Number of param incorect\n";
        echo "Usage :\n";
        echo "php index.php controlleur action [params]\n";
        die();
    }
} else { //mode with apache
    $dir = TMP . "acl".DS."acl.txt";
    $GLOBALS['_SYSTEM']['acl'] = unserialize(file_get_contents($dir));

    /* remplacer par le code en dessous */

    $GLOBALS['_SITE']['IdUser'] = -1;
    $GLOBALS['_SITE']['id_group'] = 1;

    if (!empty($_COOKIE['IdUser']) && !empty($_COOKIE['Passwd'])) {
        $sql = "select * from user_main where id = '" . $_SQL->sql_real_escape_string($_COOKIE['IdUser']) . "'";
        
        $res = $_SQL->sql_query($sql);

        if ($_SQL->sql_num_rows($res) === 1) {
            $ob = $_SQL->sql_fetch_object($res);

            //empeche le volage de session
            if (sha1($ob->password . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']) === $_COOKIE['Passwd']) {
                $GLOBALS['_SITE']['IdUser'] = $_COOKIE['IdUser'];
                $GLOBALS['_SITE']['Name'] = $ob->name;
                $GLOBALS['_SITE']['FirstName'] = $ob->firstname;
                $GLOBALS['_SITE']['id_group'] = $ob->id_group;

                $sql = "UPDATE user_main SET date_last_connected = now() where id='" . $_SQL->sql_real_escape_string($_SITE['IdUser']) . "'";
                $_SQL->sql_query($sql);

                if ($ob->is_valid == 0) {

                    if ($_SERVER['REQUEST_METHOD'] === "GET") {
                        $msg = $GLOBALS['_LG']->getTranslation(__("Hello,") . "<br />" . __("Thank you for registering.") . "<br />"
                                . __("To finalise your registration, please check your email and click on the confirmation. Once you've done this, your registration will be complete."));

                        $title = $GLOBALS['_LG']->getTranslation(__("Restricted acess"));
                        set_flash("caution", $title, $msg);
                    }

                    //header("location: ".LINK.$url);
                }
            }
        }
    }

    //echo $GLOBALS['_SITE']['id_group'];
    //die("group");


    /*
      remplacer par le code au dessus
      include_once APP_DIR.DS."controller".DS."user".".controller.php";
      $login = new user;
      $login->is_logged();
     */

    $stat = new stats;
    $stat->insert($GLOBALS['_SITE']['IdUser']);

    $_SYSTEM['controller'] = $url['controller'];
    $_SYSTEM['action'] = $url['action'];
    $_SYSTEM['param'] = $url['param'];



    // a mettre dans un class je pense ACL ?
    if (empty($GLOBALS['_SYSTEM']['acl'][$GLOBALS['_SITE']['id_group']][$_SYSTEM['controller']][$_SYSTEM['action']])) {
        //|| $GLOBALS['_SYSTEM']['acl'][$GLOBALS['_SITE']['id_group']][$_SYSTEM['controller']][$_SYSTEM['action']] != 1)
        if ($_SYSTEM['controller'] !== "" && $_SYSTEM['action'] !== "") {
            if ($GLOBALS['_SITE']['id_group'] == 1) {

                $url = "user/register/";
                $msg = $_SYSTEM['controller'] . "/" . $_SYSTEM['action'] . "<br />" . __("You have to be registered to acces to this page");
            } else {
                //die("here");
                $url = "home/index/";
                $msg = $_SYSTEM['controller'] . "/" . $_SYSTEM['action'] . "<br />" . __("Your rank to this website is not enough to acess to this page");
            }


            set_flash("error", __("Acess denied"), __("Acess denied") . " : " . $msg);

            //debug($_SYSTEM);
            
            header("location: " . LINK . $url);
            exit;
			//die("ERROR access");
        }
    }
}

$_DEBUG->save("Tools loaded");

//demarre l'application
$controller = new controller($_SYSTEM['controller'], $_SYSTEM['action'], $_SYSTEM['param']);
$controller->get_controller();


$_DEBUG->save("Controller loaded");

if (!$controller->layout_name) {
    $controller->display();
} else {
    $controller->set_layout();


	$_DEBUG->save("Layout loaed");

    define("_BBC_PAGE_NAME", $_SYSTEM['controller'] . '/' . $_SYSTEM['action']);
    define("_BBCLONE_DIR", "bbclone/");
    define("COUNTER", _BBCLONE_DIR . "mark_page.php");
    if (is_readable(COUNTER))
        include_once(COUNTER);

    
    if ($GLOBALS['_SITE']['IdUser'] == 3 && ENVIRONEMENT && ($controller->layout_name == "default" || $controller->layout_name == "admin")) {//ENVIRONEMENT
        $execution_time = microtime(true) - TIME_START;

        echo "<hr />";
        echo "Temps d'exéution de la page : " . round($execution_time, 5) . " seconds";
        echo "<br />Nombre de requette : " . $_SQL->get_count_query();

        if ($_SQL->get_count_query() != 0) {
            echo "<table class=\"debug\">";
            echo "<tr><th>#</th><th>File</th><th>Line</th><th>Query</th><th>Rows</th><th>Last inserted id</th><th>Time</th></tr>";
            $i = 0;
            $j = 0;
            $k = 0;
            foreach ($_SQL->query as $value) {
                echo "<tr><td>" . $k . "</td><td>" . $value['file'] . "</td><td>" . $value['line'] . "</td><td>" . $value['query'] . "</td><td>" . $value['rows'] . "</td><td>" . $value['last_id'] . "</td><td>" . $value['time'] . "</td></tr>";
                $i += $value['time'];
                $j += $value['rows'];
                $k++;
            }

            echo "<tr><td></td><td></td><td></td><td><b>Total</b></td><td>" . $j . "</td><td><b>" . $i . "</b></td></tr>";
            echo "</table>";
        }
		
		$_DEBUG->print_table();
		echo $_DEBUG->graph();

		

        echo "SESSION";
        debug($_SESSION);
        echo "GET";
        debug($_GET);
        echo "POST";
        debug($_POST);
        echo "COOKIE";
        debug($_COOKIE);
        echo "REQUEST";
        debug($_REQUEST);
        debug($_SERVER);
        debug($_SITE);


        echo "CONSTANTES : <br />";
        echo "<b>ROOT :</b> " . ROOT . "<br/>";
        echo "<b>TMP :</b> " . TMP . "<br/>";
        echo "<b>DATA :</b> " . DATA . "<br/>";
        echo "<b>APP_DIR :</b> " . APP_DIR . "<br/>";
        echo "<b>CONFIG :</b> " . CONFIG . "<br/>";
        echo "<b>CORE_PATH :</b> " . CORE_PATH . "<br/>";
        echo "<b>WEBROOT_DIR :</b> " . WEBROOT_DIR . "<br/>";
        echo "<b>WWW_ROOT :</b> " . WWW_ROOT . "<br/>";
        echo "<b>IMG :</b> " . IMG . "<br/>";
        echo "<b>CSS :</b> " . CSS . "<br/>";
        echo "<b>FILE :</b> " . FILE . "<br/>";
        echo "<b>VIDEO :</b> " . VIDEO . "<br/>";
        echo "<b>JS :</b> " . JS . "<br/>";
        echo "<b>LIBRARY :</b> " . LIBRARY . "<br/>";
    }
}
