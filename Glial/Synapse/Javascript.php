<?php

namespace Glial\Synapse;

class Javascript
{

    public $javascript = array();
    public $code_javascript = array();

    final function addJavascript($js)
    {
        if (is_array($js)) {
            $this->javascript = array_merge($js, $this->javascript);
        } else {
            $this->javascript[] = $js;
        }
    }
    
    
    public function code_javascript($js)
    {
        $this->di['js']->code_javascript[] = $js;
    }

    final function getJavascript()
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

}
