<?php



use \glial\synapse\Singleton;
use \glial\synapse\Controller;
use \glial\synapse\Statistics as SynapseStatistics;

class Statistics extends Controller
{

	function insert($param)
	{
		debug($param);
		$this->view = false;
		$this->layout_name = false;
		
		$_SQL = Singleton::getInstance(SQL_DRIVER);
		
		$stats = new SynapseStatistics;
		
		$stats->decode64DeflateUnserialize($param[0]);
		$data = $stats->get();
		
		
		debug($data);
	}
	
	
}