<?php

namespace Glial\Synapse;

class Statistics
{

	var $hash;
	var $data = array();

	function getData($iduser = 0)
	{
		if ( empty($_SERVER["HTTP_REFERER"]) )
		{
			$_SERVER["HTTP_REFERER"] = "";
		}

		if ( !filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) )
		{
			

			$this->data['id_user_main'] = $iduser;
			$this->data['query_string'] = $_SERVER["QUERY_STRING"];
			$this->data['referer'] = $_SERVER["HTTP_REFERER"];
			$this->data['date_created'] = date("Y-m-d H:i:s");
			$this->data['ip'] = $_SERVER['REMOTE_ADDR'];
			$this->data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
			
			if (!IS_CLI) {
				$this->data['accept_language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
			}
			else {
				$this->data['accept_language'] = "cli mode";
			}
			$this->serializeCompressEncode64();

			return $this->data;
		}
	}

	function insert($param)
	{
		$data['statistique_main'] = $param[0];
	}

	function set($var, $val)
	{
		if ( !is_string($var) )
		{
			throw new Exception;
		}
		$this->data[$var] = $val;
	}

	function callDeamon()
	{
		shell_exec("cd /home/www/species/application/webroot/; php index.php Statistics insert " . $this->hash . " >> stats.log &");
	}

	function serializeCompressEncode64()
	{
		$this->hash = base64_encode(json_encode($this->data));
	}

	function decode64DeflateUnserialize($string_encoded)
	{
		$this->data = json_decode((base64_decode($string_encoded)));
	}

	function get()
	{
		return $this->data;
	}

}

