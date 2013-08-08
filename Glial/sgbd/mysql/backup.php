<?php


namespace glial\sgbd\mysql;

class backup
{
	function generate_backup()
	{
		$_SQL = Singleton::getInstance(SQL_DRIVER);
		
		
		
	}
	
	
	static function insert()
	{
		$_SQL = \Singleton::getInstance(SQL_DRIVER);
		$sql = "SELECT * FROM INFORMATION_SCHEMA.TABLES where TABLE_SCHEMA ='species' and TABLE_TYPE = 'BASE TABLE'";
		$res = $_SQL->sql_query($sql);
		
		while($ob = $_SQL->sql_fetch_object($res))
		{
			$sql = "INSERT IGNORE INTO table_history (table_name,structure, data, date_insterted, date_updated, date_data_updated, date_structure_updated) values ('".$ob->TABLE_NAME."', 1,1, now(), now(), now(),now())";
			$_SQL->sql_query($sql);
		}

	}
	
	
}