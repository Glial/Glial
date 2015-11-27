<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Sgbd\Sql\Mysql;

class Comment
{

    /**
     * Take off comments from an sql string
     *
     * Referring documentation at:
     * http://dev.mysql.com/doc/refman/5.6/en/comments.html
     *
     * @return string Query without comments
     */
    public static function takeOffComments($query)
    {
        /*
         * Commented version
         * $sqlComments = '@
         *     (([\'"]).*?[^\\\]\2) # $1 : Skip single & double quoted expressions
         *     |(                   # $3 : Match comments
         *         (?:\#|--).*?$    # - Single line comments
         *         |                # - Multi line (nested) comments
         *          /\*             #   . comment open marker
         *             (?: [^/*]    #   . non comment-marker characters
         *                 |/(?!\*) #   . ! not a comment open
         *                 |\*(?!/) #   . ! not a comment close
         *                 |(?R)    #   . recursive case
         *             )*           #   . repeat eventually
         *         \*\/             #   . comment close marker
         *     )\s*                 # Trim after comments
         *     |(?<=;)\s+           # Trim after semi-colon
         *     @msx';
         */
        $sqlComments = '@(([\'"]).*?[^\\\]\2)|((?:\#|--).*?$|/\*(?:[^/*]|/(?!\*)|\*(?!/)|(?R))*\*\/)\s*|(?<=;)\s+@ms';

        $query2 = trim(preg_replace($sqlComments, '$1', $query));

        //Eventually remove the last ;
        if (strrpos($query2, ";") === strlen($query2) - 1) {
            $query2 = substr($query2, 0, strlen($query2) - 1);
        }

        return $query2;
    }

    function strip_sqlcomment($string = '')
    {
        $RXSQLComments = "@('(''|[^'])*')|(--[^\r\n]*)|(\#[^\r\n]*)|(/\*[\w\W]*?(?=\*/)\*/)@ms";
        return (($string == '') ? '' : preg_replace($RXSQLComments, '', $string));
    }
}