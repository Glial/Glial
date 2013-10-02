
<?php

namespace Glial\Sgbd\Sql\Sybase;

use \Glial\Sgbd\Sql\Sql;

class Sybase extends Sql
{
    public $link;

    public function sql_connect($host, $login, $password)
    {
        sybase_min_server_severity(11);
        sybase_min_client_severity(11);

        $this->link = sybase_connect($host, $login, $password);

    }

    public function sql_select_db($db)
    {
        return sybase_select_db($db, $this->link);
    }

    public function _query($sql)
    {
        return sybase_query($sql, $this->link);
    }

    public function sql_num_rows($res)
    {
        return sybase_num_rows($res);
    }
    public function sql_close()
    {
        $this->link = sybase_close($this->link);
    }

    public function sql_affected_rows()
    {
        if ($this->res != "") {
            echo $this->res;
            die();

            return sybase_num_rows($this->res);
        } else {
            return sybase_affected_rows($this->link);
        }

    }

    public function sql_real_escape_string($data)
    {
        return addslashes($data);
    }

    public function sql_fetch_object($data)
    {
        return sybase_fetch_object($data);
    }

    public function _insert_id()
    {
        //TODO : récuperer la derniere query en insert et extraire le nom de la table avec un REGEX
        $patern = "/INSERT INTO/";
        $table = "matable";
        /*
        (?<=(INTO)\s)[^\s]*(?=\(())

        \bjoin\s+(?<Retrieve>[a-zA-Z\._\d]+)\b|\bfrom\s+(?<Retrieve>[a-zA-Z\._\d]+)\b|\bupdate\s+(?<Update>[a-zA-Z\._\d]+)\b|\binsert\s+(?:\binto\b)?\s+(?<Insert>[a-zA-Z\._\d]+)\b|\btruncate\s+table\s+(?<Delete>[a-zA-Z\._\d]+)\b|\bdelete\s+(?:\bfrom\b)?\s+(?<Delete>[a-zA-Z\._\d]+)\b

        (?<=(from|join)\s)[^\s]*(?=\s(on|join|where))

        (?i)(?<=VALUES[ ]*\().*(?=\))

        $sql = 'select max(id) from '.$table;
        $this->_query($sql);

        return sybase_insert_id();*/
    }

}

?>
