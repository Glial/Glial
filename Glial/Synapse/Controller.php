<?php

namespace Glial\Synapse;

//use \Glial\Synapse\Singleton;
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
    var $msg_flash = array();
    var $javascript = array();
    var $code_javascript = array();
    var $js;
    var $data = array();
    var $ariane;
    var $ajax = false;
    var $error;
    var $html;
    public $di = array();
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
                if (!$GLOBALS['acl']->isAllowed($GLOBALS['auth']->getAccess(), $controller . "/" . $action)) {
                    return;
                }
            }
        }


        $this->controller = $controller;
        $this->action = $action;
        $this->param = $param;
        $this->view = $action;
        $this->recursive = false;
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

        $filename = APP_DIR . DS . "controller" . DS . $this->controller . ".controller.php";

        if (file_exists($filename)) {
            require_once $filename;
        } else {
            throw new \Exception("GLI-654 Error controller not found : '" . $this->controller . "'");
        }

        $page = new $this->controller($this->controller, $this->action, $this->param);
        $page->setDi($this->di);

        $this->param = json_decode($this->param);

        $this->title = $this->controller;
        $action = $this->action;


        $page->before($this->param);


        if (method_exists($page, $action)) {
            $resultat = $page->$action($this->param);
        } else {
            throw new \Exception("GLI-026 Impossible to access to this controller/action => '$this->controller/$action'");
        }

        $page->after($this->param);


        if (!IS_CLI) {
            $this->ajax = $page->ajax;
            //$this->js = $page->getJavascript();
            $this->js = $this->di['js']->getJavascript();
        }

        $this->layout_name = $page->layout_name;
        $this->view = $page->view;


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
                ob_start();
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

        //TODO to fix it
        // (ENVIRONEMENT) ? $GLOBALS['_DEBUG']->save($this->controller . "/" . $this->action) : "";
        return $resultat;
    }

    final function display()
    {
        if (empty($this->controller)) { // certainement une meilleur maniere de procÃƒÂ©der
            return;
        }
        echo $this->html;
    }

    final function setLayout()
    {
        Variable::$_open = false;

        if (!IS_CLI) {

            global $_SITE;

            $GLIALE_CONTENT = $this->html;
            $GLIALE_TITLE = $this->title;
            $GLIALE_ARIANE = $this->ariane;

            ob_implicit_flush(false);

            ob_start();

            Variable::$_open = true;

            include APP_DIR . DS . "layout" . DS . $this->layout_name . ".layout.php";

            if (!$this->ajax) {
                //echo $this->js;
                echo $this->di['js']->getJavascript();
                //echo $this->js;
            }
            echo "</html>\n"; //TODO a mettre ailleurs

            Variable::$_html = ob_get_clean();
            Variable::$_html = I18n::getTranslation(Variable::$_html);

            echo Variable::$_html;
        }
    }

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

    function after($param)
    {
        
    }

    function before($param)
    {
        
    }

    function setJs($js)
    {
        $this->js = $js;
    }

}
