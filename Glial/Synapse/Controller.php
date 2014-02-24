<?php

namespace Glial\Synapse;

use \Glial\Synapse\Singleton;
use \Glial\Synapse\Variable;
use \Glial\I18n\I18n;
use \Glial\Utility\Inflector;

class Controller
{

    /**
     * 
     * @var string
     * @access private
     */
    var $action;
    var $controller;
    var $param = array();
    var $value = array();
    var $layout = true;
    var $layout_name = "default";
    var $title = "undefined";
    var $view;
    var $menu;
    var $msg_flash = array();
    var $javascript = array();
    var $code_javascript = array();
    var $js;
    var $data = array();
    var $ariane;
    var $ajax = false;
    var $error;
    var $html;
    public $db;

    /**
     * Short description of method okh
     *
     * @access public
     * @author Aurélien LEQUOY, <aurelien.lequoy@esysteme.com>

     * @param string construct of controller
     * @return boolean Success
     * @access public
     */
    final function __construct($controller, $action, $param)
    {

        $controller = Inflector::camelize($controller);


        if (!IS_CLI) {
            if (empty($GLOBALS['_SYSTEM']['acl'][$GLOBALS['_SITE']['id_group']][$controller][$action]) || $GLOBALS['_SYSTEM']['acl'][$GLOBALS['_SITE']['id_group']][$controller][$action] != 1) {
                if ($controller !== "" && $action !== "") {
                    $this->error = __("Acess denied") . " : $controller/$action";
                    return;


                }
            }
        }

        $this->db = $GLOBALS['_DB'];


        $this->controller = $controller;
        $this->action = $action;
        $this->param = $param;
        $this->view = $action;
        $this->recursive = false;
    }

    function __call($name, $arguments)
    {
        $this->layout_name = false;
        $this->view = false;

        if (empty(trim($this->controller)))
        {
            trigger_error(__("The controller is empty :")." $name", E_USER_ERROR);
        }
        
        
        
        $class_name = "\Glial\Neuron\Controller\Neuron" . $this->controller;
        $class = new $class_name;

        $class->db = $GLOBALS['_DB'];
        //debug($class);

        $class->$name($arguments);



        //echo "$class_name,  $name, $arguments \n";
    }

    final function get_controller()
    {
        if (empty($this->controller)) { // certainement un meilleur maniere de procÃƒÂ©der
            return;
        }


        $filename = APP_DIR . DS . "controller" . DS . $this->controller . ".controller.php";
		
		
        if (file_exists($filename)) {
            include_once $filename;
        } else {
            $filename = ROOT . DS ."vendor".DS."glial".DS . "glial" .DS ."Glial" .DS . "Neuron" . DS . "Controller" . DS ."Neuron". $this->controller . ".php";
            if (file_exists($filename)) {
		$this->controller = "\Glial\Neuron\Controller\Neuron". $this->controller;
                include_once $filename;
            } else {
                trigger_error("impossible to get the class file : " . $filename. ":".__FILE__ .":". __LINE__, E_USER_NOTICE);
                exit;
                //throw new Exception("Impossible to load :".$filename);
            }
        }


        $page = new $this->controller($this->controller, $this->action, $this->param);


		$this->db = $GLOBALS['_DB'];
		$page->db = $GLOBALS['_DB'];
        $this->param = json_decode($this->param);

        $this->title = $this->controller;
        $action = $this->action;

        $page->$action($this->param);
        $this->ajax = $page->ajax;
        $this->js = $page->get_javascript();
        $this->layout_name = $page->layout_name;
        $this->view = $page->view;
        $this->menu = $page->menu;


        if ($page->title !== "undefined") {
            $this->title = $page->title;
            $GLOBALS['_SITE']['title_page'] = $this->title;
        }
        if (!empty($page->ariane)) {
            $this->ariane = $page->ariane;
            $GLOBALS['_SITE']['ariane'] = strip_tags($this->ariane);
        }
        $tab = $page->get();

        foreach ($tab as $key => $val) {
            ${$key} = $val;
        }

        if (!$this->recursive) {



            if (!Variable::$_open) {
                ob_start(); //TODO 
            }

            if ($this->view) {
                require APP_DIR . DS . "view" . DS . $this->controller . DS . $this->view . ".view.php";
            }

            if (!Variable::$_open) {
                $this->html = ob_get_contents();
                ob_clean();
            }
        } else {
            if ($this->view) {
                include APP_DIR . DS . "view" . DS . $this->controller . DS . $this->view . ".view.php";
            }
        }


        (ENVIRONEMENT) ? $GLOBALS['_DEBUG']->save($this->controller . "/" . $this->action) : "";
    }

    final function display()
    {
        if (empty($this->controller)) { // certainement une meilleur maniere de procÃƒÂ©der
            return;
        }
        echo $this->html;
    }

    final function set_layout()
    {
        Variable::$_open = false;

		
		
		if (! IS_CLI)
		{
			if (empty($this->html)) { // certainement une meilleur maniere de procÃƒÂ©der

				set_flash("error", "Access denied", $this->error);
				header("location :" . LINK . "user/register/");
				return;
				die();
			}
		
			
			global $_LG, $_SITE;

			//$this->html = $_LG->getTranslation($this->html);

			$GLIALE_CONTENT = $this->html;
			$GLIALE_TITLE = $this->title;
			$GLIALE_ARIANE = $this->ariane;



			ob_implicit_flush(false);
			ob_start();

			Variable::$_open = true;
			include APP_DIR . DS . "layout" . DS . $this->layout_name . ".layout.php";

			if (!$this->ajax) {
				echo $this->js;
			}
			echo "</html>\n"; //TODO a mettre ailleurs


			Variable::$_html = ob_get_clean();

			echo I18n::getTranslation(Variable::$_html);
		
		}
    }

    final function get_javascript()
    {
        $js = "\n<!-- start library javascript -->\n";

        // to prevent problem
        $this->javascript = array_unique($this->javascript);

        foreach ($this->javascript as $script) {

            if (stristr($script, 'http://')) {
                $js .="<script type=\"text/javascript\" src=\"" . $script . "\"></script>\n";
            } else {
                $js .="<script type=\"text/javascript\" src=\"" . JS . $script . "\"></script>\n";
            }
        }

        $js .= "<!-- end library javascript -->\n<script type=\"text/javascript\">\n";
        foreach ($this->code_javascript as $script) {
            $js .= $script;
        }

        $js .= "</script>\n";


        return $js;
    }

    final function set($var, $valeur)
    {
        $this->value[$var] = $valeur;
    }

    final function get()
    {
        return $this->value;
    }

    final function add_javascript($js)
    {
        if (is_array($js)) {
            $this->javascript = array_merge($js, $this->javascript);
        } else {
            $this->javascript[] = $js;
        }
    }

}

