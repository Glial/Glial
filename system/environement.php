<?php


header("Charset: UTF-8");
header("Cache-Control: max-age=3600, must-revalidate");


include_once(CONFIG."environement.config.php");

if (ENVIRONEMENT)
{
	error_reporting (-1);
	ini_set('display_errors', 1);
	ini_set('log_errors', 1);
	ini_set('error_log', TMP. 'LOG/error_log_'.Date("Y-m-d").'.txt');

	if (version_compare(PHP_VERSION, '5.0') < 0)
	{
		trigger_error("Get PHP 5 or highter",E_USER_ERROR);
	}
}


?>