<?php

namespace Glial\Synapse;

//use \Glial\Synapse\Singleton;
use \Glial\Synapse\Variable;
use \Glial\I18n\I18n;
use \Glial\Utility\Inflector;
use \Glial\Synapse\FactoryController;

if (!defined('AUTH_ACTIVE')) {
    define("AUTH_ACTIVE", false);
}

class Controller
{
    /**
     *
     * @var string
     * @access private
     */
    var $action;
    var $controller;
    var $param           = array();
    var $value           = array();
    var $layout          = true;
    var $layout_name     = "default";
    var $title           = "undefined";
    var $view;
    var $msg_flash       = array();
    var $javascript      = array();
    var $code_javascript = array();
    var $js;
    var $ariane;
    var $ajax            = false;
    var $error;
    var $html;
    var $out;
    var $cli;
    public $di              = array();
    private $isRootNode;
    public $db;

    /**
     * Short description of method
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

        if (AUTH_ACTIVE) {
            if (!IS_CLI) {
                if (!$GLOBALS['acl']->isAllowed($GLOBALS['auth']->getAccess(), $controller."/".$action)) {
                    return;
                }
            }
        }


        $this->controller = $controller;
        $this->action     = $action;
        $this->param      = $param;
        $this->view       = $action;
        $this->recursive  = false;
    }

    final public function setDi($di)
    {
        $this->di = $di;
    }

    final function getController()
    {
        if (empty($this->controller)) {
            return;
        }

        $path = '\\App\\Controller\\';
        $name = $this->controller;

        $class = $path.$name;

        $page = new $class($this->controller, $this->action, $this->param);
        $page->setDi($this->di);

        $this->param = json_decode($this->param, true);

        $this->title = $this->controller;
        $action      = $this->action;

        $page->before($this->param);

        if (method_exists($page, $action)) {
            $resultat = $page->$action($this->param);
        } else {
            throw new \Exception("GLI-026 Impossible to access to this controller/action => '$this->controller/$action'");
        }

        $page->after($this->param);

        $this->value = $page->value;

        if (FactoryController::RESULT === $this->out) {
            return $resultat;
        }

        if (!IS_CLI) {
            $this->ajax = $page->ajax;
            $this->js   = $this->di['js']->getJavascript();
        }

        $this->layout_name = $page->layout_name;
        $this->view        = $page->view;

        // @title deprecated ?
        if ($page->title !== "undefined") {
            $this->title                    = $page->title;
            $GLOBALS['_SITE']['title_page'] = $this->title;
        }
        if (!empty($page->ariane)) {
            $this->ariane               = $page->ariane;
            $GLOBALS['_SITE']['ariane'] = strip_tags($this->ariane);
        }
        $tab = $page->get();

        foreach ($tab as $key => $val) {
            ${$key} = $val;
        }


        if (!$this->recursive) {


            if (!Variable::$_open) {
                ob_start();
            }

            if ($this->view) {

                //used for rootNode
                if (IS_CLI) {

                    if (file_exists(APP_DIR.DS."view".DS.$this->controller.DS.$this->view.".view.php")) {
                        require APP_DIR.DS."view".DS.$this->controller.DS.$this->view.".view.php";
                    }
                } else {
                    require APP_DIR.DS."view".DS.$this->controller.DS.$this->view.".view.php";
                }
            }

            if (!Variable::$_open) {
                $this->html = ob_get_contents();
                ob_clean();
            }
        } else {
            if ($this->view) {


                if (FactoryController::EXPORT === $this->out) {
                    ob_start();
                }

                //used by addNode
                require APP_DIR.DS."view".DS.$this->controller.DS.$this->view.".view.php";

                if (FactoryController::EXPORT === $this->out) {
                    $this->html = ob_get_contents();
                    ob_clean();
                }
            }
        }

        //TODO to fix it
        // (ENVIRONEMENT) ? $GLOBALS['_DEBUG']->save($this->controller . "/" . $this->action) : "";

        if (FactoryController::EXPORT == $this->out) {
            return $this->html;
        }

        return $resultat;
    }

    /**
     * (Glial 2.1)<br/>
     * What's that ?
     * @author Aurélien LEQUOY, <aurelien.lequoy@esysteme.com>
     * @param none
     * @return html
     * @package Controller
     * @since 2.1 First time this was introduced.
     * @description return one node of MVC
     * @access public
     */
    final function display()
    {
        if (empty($this->controller)) { // certainement une meilleur maniere de procÃƒÂ©der
            return;
        }
        echo $this->html;
    }

    /**
     * (Glial 2.1)<br/>
     * What's that ?
     * @author Aurélien LEQUOY, <aurelien.lequoy@esysteme.com>
     * @param none
     * @return html
     * @package Controller
     * @since 2.1 First time this was introduced.
     * @since 4.2.1 Added GLIALE_DATA to push data/GET from main page to other MVC
     * @description return one node of MVC
     * @access public
     */
    final function setLayout()
    {
        Variable::$_open = false;

        if (!IS_CLI) {

            global $_SITE;

            $GLIALE_CONTENT = $this->html; /* deprecated */
            $GLIALE_TITLE   = $this->title; /* deprecated */
            $GLIALE_ARIANE  = $this->ariane; /* deprecated */
            $GLIALE_DATA    = (array) $this->value; /* deprecated */

            ob_implicit_flush(false);

            ob_start();

            Variable::$_open = true;

            include APP_DIR.DS."layout".DS.$this->layout_name.".layout.php";

            if (!$this->ajax) {
                //echo $this->js;
                echo $this->di['js']->getJavascript();
                //echo $this->js;
            }
            echo "</html>\n"; //TODO a mettre ailleurs

            Variable::$_html = ob_get_clean();
            //Variable::$_html = I18n::getTranslation(Variable::$_html);

            return Variable::$_html;
        }
    }

    /**
     * (Glial 2.1)<br/>
     * This method set a variable in the array value in controller to be acceded from the view
     * @author Aurélien LEQUOY, <aurelien.lequoy@esysteme.com>
     * @param string, $variable
     * @example $this->set('data',$data);
     * @return void
     * @package Controller
     * @since 2.1 First time this was introduced.
     * @description give variable to the view
     * @access public
     */
    final function set($var, $valeur)
    {
        $this->value[$var] = $valeur;
    }

    final function get()
    {
        return $this->value;
    }

    final function setRootNode()
    {
        $this->isRootNode = true;
    }

    /**
     * (Glial 2.0)<br/>
     * @author Aurélien LEQUOY, <aurelien.lequoy@esysteme.com>
     * @return void
     * @package Controller
     * @description this function is executed after controller/action (only overwritten)
     * @access public
     */
    function after($param)
    {

    }

    /**
     * (Glial 2.0)<br/>
     * @author Aurélien LEQUOY, <aurelien.lequoy@esysteme.com>
     * @return void
     * @package Controller
     * @description this function is executed before controller/action  (only overwritten)
     * @access public
     */
    public function before($param)
    {

    }

    /**
     * (Glial 1.0)<br/>
     * DEPRACATED deplaced in package javascript
     * @author Aurélien LEQUOY, <aurelien.lequoy@esysteme.com>
     * @return void
     * @package Controller
     * @since 2.1 DEPRACATED.
     * @description give javascript name to be add at the bottom of the page
     * @access public
     */
    function setJs($js)
    {
        $this->js = $js;
    }

    function setOut($out = FactoryController::DISPLAY)
    {
        $this->out = $out;
    }

    function getClass()
    {
        $gg = new \ReflectionClass($this);
        return $gg->getShortName();
    }
}