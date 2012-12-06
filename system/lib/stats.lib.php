<?php

class stats
{


	function insert($iduser=0)
	{

		
		if (empty($_SERVER["HTTP_REFERER"]))
		{
			$_SERVER["HTTP_REFERER"]="";
		}
		
		if (! strstr( $_SERVER['REMOTE_ADDR'] , "192.168.1"))
		{
			$data['statistique_main']['id_user_main'] = $iduser;
			$data['statistique_main']['query_string'] = $_SERVER["QUERY_STRING"];
			$data['statistique_main']['referer'] = $_SERVER["HTTP_REFERER"];
			$data['statistique_main']['date_created'] = date("Y-m-d H:i:s");
			$data['statistique_main']['ip'] = $_SERVER['REMOTE_ADDR'];
			$data['statistique_main']['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
			$data['statistique_main']['accept_language'] = @$_SERVER['HTTP_ACCEPT_LANGUAGE'];
			$data['statistique_main']['country'] = remote_country($_SERVER['REMOTE_ADDR']);
			
			
			$GLOBALS['_SQL']->sql_save($data);
		}

	}


}
	
?>