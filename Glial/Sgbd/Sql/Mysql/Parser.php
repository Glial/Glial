<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Sgbd\Sql\Mysql;

use Glial\Sgbd\Sql\Mysql\Comment;

class Parser
{
    /**
     * @purpose : Parses SQL file
     * @params string $content Text containing sql instructions
     * @return array List of sql parsed from $content
     */
    public static function parse($content)
    {
        $sqlList = array();

        // Processing the SQL file content
        $lines = explode("\n", $content);

        $query = "";

        // Parsing the SQL file content
        foreach ($lines as $sql_line) {
            $sql_line = trim($sql_line);
            if ($sql_line === "") continue;
            else if (strpos($sql_line, "--") === 0) continue;
            else if (strpos($sql_line, "#") === 0) continue;

            $query .= $sql_line;
            // Checking whether the line is a valid statement
            if (preg_match("/(.*);/", $sql_line)) {
                $query = trim($query);
                $query = substr($query, 0, strlen($query) - 1);
                $query = Comment::takeOffComments($query);

                //store this query
                $sqlList[] = $query;
                //reset the variable
                $query     = "";
            }
        }
        return $sqlList;
    }
}