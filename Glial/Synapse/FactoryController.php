<?php

namespace Glial\Synapse;

use \Glial\Synapse\Controller;
use \Glial\Sgbd\Sql\Sql;

class FactoryController
{

	/**
	
	 * (Glial 2.1)<br/>
     	* Add a MVC node in a view 
	 * @author Aurélien LEQUOY, <aurelien.lequoy@esysteme.com>
	 * @param string construct of controller
	 * @return boolean Success
	 * @access public
	 */
	public static function addNode($controller, $action, $param)
	{
		$node = new Controller($controller, $action, json_encode($param));
		$node->recursive = true;
		$node->get_controller();
	}

	/**
	 * Short description of method okh
	 *
	 * @author Aurélien LEQUOY, <aurelien.lequoy@esysteme.com>
	 * @license GPL
	 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
	 * @param string construct of controller
	 * @return boolean Success
	 * @access public
	 * @since 2.1 First time this was introduced.
	 * @version 2.1
	 */
	public static function rootNode($controller, $action, $param = array())
	{

		$node = new Controller($controller, $action, json_encode($param));
		$node->get_controller();

		if ( !$node->layout_name )
		{
			$node->display();
			return false;
		}
		else
		{
			$node->set_layout();
			return true;
		}
	}

}
