<?php

/**
 * Glial Framework
 *
 * LICENSE
 *
 * 
 */
//namespace gliale\flickr;

class url {

    static function get_sub_domain() {
        return substr_count($_SERVER['HTTP_HOST'], '.') > 1 ?
                substr($_SERVER['HTTP_HOST'], 0, strpos($_SERVER['HTTP_HOST'], '.')) : '';
    }

}