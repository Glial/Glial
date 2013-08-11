<?php

/**
 * Glial Framework
 *
 * LICENSE
 *
 * 
 */
//namespace gliale\flickr;

class Url {

    static function getSubDomain() {
        return substr_count($_SERVER['HTTP_HOST'], '.') > 1 ?
                substr($_SERVER['HTTP_HOST'], 0, strpos($_SERVER['HTTP_HOST'], '.')) : '';
    }

}