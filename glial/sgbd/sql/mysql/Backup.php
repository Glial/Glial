<?php

namespace glial\sgbd\sql\mysql;

class Backup
{

    public $DbLink;

    public function generateBackup($db)
    {
        $this->Db = $db;
    }

    public static function insert()
    {

        $sql = "SELECT * FROM INFORMATION_SCHEMA.TABLES where TABLE_SCHEMA ='species' and TABLE_TYPE = 'BASE TABLE'";
        $res = $this->Db->sql_query($sql);

        while ( $ob = $this->Db->sql_fetch_object($res) ) {
            $sql = "INSERT IGNORE INTO table_history (table_name,structure, data, date_insterted, date_updated, date_data_updated, date_structure_updated) values ('" . $ob->TABLE_NAME . "', 1,1, now(), now(), now(),now())";
            $this->Db->sql_query($sql);
        }
    }

}
