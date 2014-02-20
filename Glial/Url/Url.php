<?php

/**
 * Glial Framework
 *
 * LICENSE
 *
 *
 */
namespace Glial\Url;

class Url
{
    public static function getSubDomain()
    {
        return substr_count($_SERVER['HTTP_HOST'], '.') > 1 ?
                substr($_SERVER['HTTP_HOST'], 0, strpos($_SERVER['HTTP_HOST'], '.')) : '';
    }

}
