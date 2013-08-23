<?php

/**
 * Glial Framework
 *
 * LICENSE
 *
 *
 */
namespace glial\acl;

class acl
{
    public $data = array();
    public $id_group = 0;

    public function __construct($id_group)
    {
        $dir = TMP . "acl/acl.txt";
        $this->data = unserialize(file_get_contents($dir));
        $this->id_group = $id_group;
    }

    public function is_allowed($controller = NULL, $action = NULL)
    {
        if (!empty($controller)) {
            if (!empty($action)) {
                if (!empty($this->data[$this->id_group][$controller][$action])) {
                    if ($this->data[$this->id_group][$controller][$action] == 1) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

}
