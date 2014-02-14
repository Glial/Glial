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
	 * @package Controller
	 * @since 2.1 First time this was introduced.
	 * @description create a new MVC and display the output in standard flux
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
	 * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
	 * @license GPL
	 * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
	 * @param string construct of controller
	 * @return boolean Success
	 * @description should be called 1 time by request. It create the root MVC used in boot and display the output in standard flux. it's this controller witch will determine the layout.
	 * @access public
	 * @example \Glial\Synapse\FactoryController::rootNode("class", "function", array('param1','param2'));
	 * @package Controller
	 * @See Also addNode
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
