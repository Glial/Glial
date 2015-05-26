<?php

namespace Glial\Synapse;

class Javascript
{

    public $javascript = array();
    public $code_javascript = array();
    public $code_js_from = array();

    function __construct()
    {
        $jss = explode(",", \GLIAL_JAVASCRIPT);

        foreach ($jss as $js) {
            $this->addJavascript($js, "Loaded from GLIAL_JAVASCRIPT (configuration/javascript.config.php)");
        }
    }

    final function addJavascript($js, $file = "")
    {
        if (empty($file))
        {
            $file = \Glial\Synapse\Basic::from();
        }
        
        if (is_array($js)) {
            foreach ($js as $line) {
                $this->add_js($line, $file);
            }
        } else {
            $this->add_js($js, $file);
        }
    }

    public function code_javascript($js)
    {
        $this->code_javascript[] = $js;
    }

    final function getJavascript()
    {
        $js = "\n<!-- start library javascript -->\n";

        // to prevent problem
        //$this->javascript = array_unique($this->javascript);


        $js = "\n<script type=\"text/javascript\">\n";
        $js .= "var GLIAL_LINK ='".LINK."';\n";   
        $js .= "</script>\n";
        
        $i = 0;
        foreach ($this->javascript as $script) {

            if (stristr($script, 'http://') || stristr($script, 'https://')) {
                $js .="<script type=\"text/javascript\" src=\"" . $script . "\"></script>";
            } else {
                $js .="<script type=\"text/javascript\" src=\"" . JS . $script . "\"></script>";
            }


            $js .="<!-- " . $this->code_js_from[$i] . " -->\n";
            $i++;
        }

        $js .= "<!-- end library javascript -->\n<script type=\"text/javascript\">\n";
        foreach ($this->code_javascript as $script) {
            $js .= $script;
        }

        $js .= "</script>";

        return $js;
    }

    final function add_js($js, $file)
    {

        
        
        if (!in_array($js, $this->javascript)) {
            $this->javascript[] = $js;
            $this->code_js_from[] = $file;
        }
    }

}
