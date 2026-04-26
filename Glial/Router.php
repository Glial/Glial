<?php
include_once(CONFIG."router.config.php");



if (empty($_GET['glial_path'])) {

    $_LG_choice = explode(",", LANGUAGE_AVAILABLE);

    if (empty($_SESSION['language']) || !in_array($_SESSION['language'], $_LG_choice)) {
        // Detect from Accept-Language header; fall back to EN
        $lgnew = 'en';
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $detected = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            if (in_array($detected, $_LG_choice)) {
                $lgnew = $detected;
            }
        }
        $_SESSION['language'] = $lgnew;
    }

    header("HTTP/1.1 302 Found");
    header("Location: ".WWW_ROOT.$_SESSION['language']."/".ROUTE_DEFAULT);
    exit;
}

class Router
{
    var $routes = array();

    function parse($url)
    {
        if (strstr($url, '>')) {
            define('IS_AJAX', true);
        } else {
            define('IS_AJAX', false);
        }

        $tab = explode("/", $url);

        $nbparam = count($tab);

        if ($nbparam < 2) {
            trigger_error("url invalid can't find controller & action", E_USER_ERROR);
        }

        $this->routes['controller'] = $tab[1];

        $_GET['lg'] = $tab[0];

        $lang_available = explode(",", LANGUAGE_AVAILABLE);

        if (!in_array($_GET['lg'], $lang_available)) {
            // Unknown lang segment → redirect to EN equivalent of the same path
            $rest = implode('/', array_slice($tab, 1));
            header('HTTP/1.1 302 Found');
            header('Location: '.WWW_ROOT.'en/'.ltrim($rest, '/'));
            exit;
        }

        unset($tab[0]);

        $_GET['url'] = implode("/", $tab);

        if (empty($tab[2])) {
            $this->routes['action'] = "index";
        } else {
            $this->routes['action'] = $tab[2];
        }



        if ($nbparam > 3) {
            for ($i = 3; $i < $nbparam; $i++) {
                $param[] = $tab[$i];

                if (strstr($tab[$i], ":")) {
                    $tb            = explode(":", $tab[$i]);
                    $nb_profondeur = count($tb);

                    if ($nb_profondeur == 2) {
                        $_GET[$tb[0]] = $tb[1];
                    } elseif ($nb_profondeur >= 3) {

                        $tmp = $tb;
                        unset($tmp[0]);
                        unset($tmp[1]);

                        $_GET[$tb[0]][$tb[1]] = implode(":",$tmp);
                    }
                }
            }


            if (!empty($param)) {
                $this->routes['param'] = $param;
            }
        } else {
            $this->routes['param'] = "";
        }
    }

    function get_routes()
    {
        return $this->routes;
    }
}