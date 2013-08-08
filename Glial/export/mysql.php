<?php

class mysql {
	/* backup the db OR just a table */

	//example backup_tables('localhost', 'username', 'password', 'blog');

	function backup_tables($host, $user, $pass, $name, $tables = '*') {

		$link = mysql_connect($host, $user, $pass);
		mysql_select_db($name, $link);

		//get all of the tables
		if ($tables == '*')
		{
			$tables = array();
			$result = mysql_query('SHOW TABLES');
			while ($row = mysql_fetch_row($result)) {
				$tables[] = $row[0];
			}
		}
		else
		{
			$tables = is_array($tables) ? $tables : explode(',', $tables);
		}

		//cycle through
		foreach ($tables as $table)
		{
			$result = mysql_query('SELECT * FROM ' . $table);
			$num_fields = mysql_num_fields($result);

			$return.= 'DROP TABLE ' . $table . ';';
			$row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE ' . $table));
			$return.= "\n\n" . $row2[1] . ";\n\n";

			for ($i = 0; $i < $num_fields; $i++)
			{
				while ($row = mysql_fetch_row($result)) {
					$return.= 'INSERT INTO ' . $table . ' VALUES(';
					for ($j = 0; $j < $num_fields; $j++)
					{
						$row[$j] = addslashes($row[$j]);
						$row[$j] = ereg_replace("\n", "\\n", $row[$j]);
						if (isset($row[$j]))
						{
							$return.= '"' . $row[$j] . '"';
						}
						else
						{
							$return.= '""';
						}
						if ($j < ($num_fields - 1))
						{
							$return.= ',';
						}
					}
					$return.= ");\n";
				}
			}
			$return.="\n\n\n";
		}

		//save file
		$handle = fopen('db-backup-' . time() . '-' . (md5(implode(',', $tables))) . '.sql', 'w+');
		fwrite($handle, $return);
		fclose($handle);
	}

	function dumpMySQL($serveur, $login, $password, $base, $mode) {
		$connexion = mysql_connect($serveur, $login, $password);
		mysql_select_db($base, $connexion);

		$entete = "-- ----------------------\n";
		$entete .= "-- dump de la base " . $base . " au " . date("d-M-Y") . "\n";
		$entete .= "-- ----------------------\n\n\n";
		$creations = "";
		$insertions = "\n\n";

		$listeTables = mysql_query("show tables", $connexion);
		while ($table = mysql_fetch_array($listeTables)) {
			// si l'utilisateur a demandé la structure ou la totale
			if ($mode == 1 || $mode == 3)
			{
				$creations .= "-- -----------------------------\n";
				$creations .= "-- creation de la table " . $table[0] . "\n";
				$creations .= "-- -----------------------------\n";
				$listeCreationsTables = mysql_query("show create table " . $table[0], $connexion);
				while ($creationTable = mysql_fetch_array($listeCreationsTables)) {
					$creations .= $creationTable[1] . ";\n\n";
				}
			}
			// si l'utilisateur a demandé les données ou la totale
			if ($mode > 1)
			{
				$donnees = mysql_query("SELECT * FROM " . $table[0]);
				$insertions .= "-- -----------------------------\n";
				$insertions .= "-- insertions dans la table " . $table[0] . "\n";
				$insertions .= "-- -----------------------------\n";
				while ($nuplet = mysql_fetch_array($donnees)) {
					$insertions .= "INSERT INTO " . $table[0] . " VALUES(";
					for ($i = 0; $i < mysql_num_fields($donnees); $i++)
					{
						if ($i != 0)
							$insertions .= ", ";
						if (mysql_field_type($donnees, $i) == "string" || mysql_field_type($donnees, $i) == "blob")
							$insertions .= "'";
						$insertions .= addslashes($nuplet[$i]);
						if (mysql_field_type($donnees, $i) == "string" || mysql_field_type($donnees, $i) == "blob")
							$insertions .= "'";
					}
					$insertions .= ");\n";
				}
				$insertions .= "\n";
			}
		}

		mysql_close($connexion);

		$fichierDump = fopen("dump.sql", "wb");
		fwrite($fichierDump, $entete);
		fwrite($fichierDump, $creations);
		fwrite($fichierDump, $insertions);
		fclose($fichierDump);
		echo "Sauvegarde réalisée avec succès !!";
	}

}