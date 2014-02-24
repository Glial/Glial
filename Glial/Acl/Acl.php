<?php

/**
 * Glial Framework
 *
 * LICENSE
 *
 *
 */
namespace Glial\Acl;

class Acl
{
	protected $roles = array(); 
    protected $resources = array(); 
    protected $access = array(); 
	
	
	/*
	 * if acl/acl.txt is undefined parse the right in CONFIG ."acl.config.ini.php" and serialize it in acl/acl.txt
	 * if acl/acl.txt is defined just unserialize acl/acl.txt and setup the object
	 * @since Glial 2.1.1
	 * @version 2.1.1
	 * @return array contain the data to be serialized
	 * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
	 * @description return an array to be serialized in a flat file
	 * @access public
	 */
	
	
    public function __construct()
    {
		$path_to_acl_tmp = TMP . "acl/acl.txt";

	    if (file_exists ($path_to_acl_tmp))
		{
			if (is_file($path_to_acl_tmp))
			{
				$s = implode('', file($path_to_acl_tmp));
				$tmp = unserialize($s);
				$this->roles = $tmp->roles;
				$this->resources = $tmp->resources;
				$this->access = $tmp->access;

				return true;
			}
		}
		
		$this->setResource();	
		$this->loadIniFile( CONFIG ."acl.config.ini.php");

		file_put_contents($path_to_acl_tmp,serialize($this));

    }
	
	/*
	 * return an array with data to serialize
	 * @since Glial 2.1.1
	 * @version 2.1.1
	 * @return array contain the data to be serialized
	 * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
	 * @description return an array to be serialized in a flat file
	 * @access public
	 */

	public function __sleep()
	{
		return array('roles', 'resources', 'access');
	}
	

	/*
	 * parse application/controller/*.controller.php and define all the resources
	 * @since Glial 2.1.1
	 * @version 2.1.1
	 * @param  string $filename full path where to find the ini file (usually configuration/acl.config.ini.php)
	 * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
	 * @description load ini file and setup the roles and access
	 * @access public
	 */
	
	function setResource()
	{
		$class = new \ReflectionClass("\Glial\Synapse\Controller");
		$methods = $class->getMethods();
		$class_controller_method = array();

		foreach($methods as $method)
		{
			$class_controller_method[] = $method->name;
		}
		
		$dir = APP_DIR . DS . "controller" . DS;
		if (is_dir($dir)) {
			$dh = opendir($dir);
			if ($dh) {
				while (($file = readdir($dh)) !== false) {
					if (strstr($file, '.controller.php')) {

						if (filetype($dir . $file) != "file" || substr($file, 0, 1) === ".") {
							continue;
						}

						$class_name = explode(".", $file);
						$controller = $class_name[0];

						if (!class_exists($controller)) {
							
							require($dir . $file);
						}

						$tab3 = get_class_methods($controller);
						//substract methods from class parent (\Glial\Synapse\Controller)
						$tab3 = array_diff($tab3, $class_controller_method);
						
						foreach ($tab3 as $action) {
							$this->addResource($controller."/".$action); 
						}
					}
				}

				closedir($dh);
			}
		}
	
	}
	
	
	/*
	 * load an ini file and setup the roles and access
	 * @since Glial 2.1.1
	 * @version 2.1.1
	 * @param  string $filename full path where to find the ini file (usually configuration/acl.config.ini.php)
	 * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
	 * @description load ini file and setup the roles and access
	 * @access public
	 */
	
	function loadIniFile($filename)
	{
		if (!is_file ($filename))
		{
			 trigger_error("impossible to load the ini file : " . $filename, E_USER_NOTICE);
		}
		
		$tab = parse_ini_file($filename , true);
		
		
		//definistion des roles
		foreach($tab['role']['add'] as $role)
		{
			$this->addRole($role);
		}
		
		unset($tab['role']['add']);
		
		//imbrication des roles (hierarchiquement)
		foreach($tab['role'] as $role1 => $tab_role)
		{
			foreach($tab_role as $role2)
			
			$this->addRole($role1,$role2);
		}
		
		//allow
		foreach($tab['allow'] as $role => $tab_ressource)
		{
			foreach($tab_ressource as $ressource)
			{
				$this->allow($role,$ressource);
			}
			
		}

		//deny
		foreach($tab['deny'] as $role => $tab_ressource)
		{
			foreach($tab_ressource as $ressource)
			{
				$this->deny($role,$ressource);
			}
		}

	}
	
	/*
	 * Add a resource
	 * @since Glial 2.1.1
	 * @version 2.1.1
	 * @param  mixed $resources should validate this regex /[\w]+\/[\w]+/i => example : "controller/action"
	 * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
	 * @description Add a role and put it in array $this->roles
	 * @access public
	 */
	
	function addResource($resources)
	{
		if(is_string($resources))
		{
			$this->resources[$resources] = '';
		}
		else if(is_array($resources))
		{
			foreach($resources as $resource)
			{
				$this->resources[$resource] = '';
			}
		}
	}
	
	/*
	 * Add a role or add a parent to a role
	 * @since Glial 2.1.1
	 * @version 2.1.1
	 * @param  mixed $role the roles are defined in acl.config.ini.php
	 * @param  mixed $parents optionally set a parent of a role to allow recursive rights
	 * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
	 * @description Add a role and put it in array $this->roles
	 * @access public
	 */
	
	function addRole($role,$parents = '')
	{
		if(is_string($parents))
		{
			if($parents == '')
			{
				$this->roles[$role] = array();
			}
			else
			{
				$this->roles[$role][] = $parents;
			}
			
		}
		else if(is_array($parents))
		{
			foreach($parents as $parent)
			{
				$this->roles[$role][] = $parent;
			}
		}
		else
		{
			throw new Exception('Not an array or a string');
		}
	}
	
	
	/*
	 * deny an access between a role and a resource
	 * @since Glial 2.1.1
	 * @version 2.1.1
	 * @param  string $role the roles are defined in acl.config.ini.php
	 * @param  string $resource should validate this regex /[\w]+\/[\w]+/i => example : "controller/action"
	 * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
	 * @description Deny an access between a role and a resource
	 * @access public
	 */
	 
	function deny($role,$resources)
	{
		if(is_string($resources))
		{
			$this->setAccess($role,$resources,'deny');
		}
		else if(is_array($resources))
		{
			foreach($resources as $resource)
			{
				$this->setAccess($role,$resource,'deny');
			}
		}
	}
	
	/*
	 * allow an access between a role and a resource
	 * @since Glial 2.1.1
	 * @version 2.1.1
	 * @param  string $role the roles are defined in acl.config.ini.php
	 * @param  string $resource should validate this regex /[\w]+\/[\w]+/i => example : "controller/action"
	 * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
	 * @description Allow an access between a role and a resource
	 * @access public
	 */
	
	function allow($role,$resources)
	{
		if(is_string($resources))
		{
			$this->setAccess($role,$resources,'allow');
		}
		else if(is_array($resources))
		{
			foreach($resources as $resource)
			{
				$this->setAccess($role,$resource,'allow');
			}
		}
	}
	
	
	/*
	 * set the access in function of roles and resources
	 * @since Glial 2.1.1
	 * @version 2.1.1
	 * @param  string $role the roles are defined in acl.config.ini.php
	 * @param  string $resource should validate this regex /[\w]+\/[\w]+/i => example : "controller/action"
	 * @param  string $access can be allow or deny, by default deny
	 * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
	 * @description Return true if match between role and resource is allowed
	 * @access public
	 */
	
	private function setAccess($role,$resource,$access = 'deny')
	{
		if($this->checkIfRoleExist($role) || $this->checkIfResourceExist($resource))
		{
			$this->access[$role][$resource] = $access;
		}
	}	
	
	 
	/*
	 * Return true if match between role and resource is allowed
	 * @since Glial 2.1.1
	 * @version 2.1.1
	 * @param  string $role the roles are defined in acl.config.ini.php
	 * @param  string $resource should validate this regex /[\w]+\/[\w]+/i => example : "controller/action"
	 * @return boolean Return true if role and resource match
	 * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
	 * @description Return true if match between role and resource is allowed
	 * @access public
	 */
	 
	function isAllowed($role,$resource)
	{
		//We first check that the resource & role exist
		if($this->checkIfRoleExist($role) && $this->checkIfResourceExist($resource))
		{
			//He has access to something
			if(array_key_exists($role,$this->access))
			{
				//Maybe to this resource
				if(array_key_exists($resource,$this->access[$role]))
				{
					//Is he allowed
					if($this->access[$role][$resource] === 'allow')
					{	
						return true;
					}
					
					//If he is not allowe we return false
					if($this->access[$role][$resource] === 'deny')
					{	
						return false;
					}	
				}			
			}
			
			//Maybe a parent...?
			if(count($this->roles[$role]) > 0)
			{
				//We ask his parents				
				foreach($this->roles[$role] as $parent)
				{
					//We go deeper in the rabbit hole...
					if($this->isAllowed($parent,$resource))
					{
						return true;
					}
				}
			}			
		}
		//If we arrive here it means that he's not allowed
		return false;
	}
	

	/*
	 * Return true if role exist else false
	 * @since Glial 2.1.1
	 * @version 2.1.1
	 * @param  string $role the roles are defined in acl.config.ini.php
	 * @return boolean Return true if role exist else false
	 * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
	 * @description check if resource  exist or not
	 * @access public
	 */
	private function checkIfRoleExist($role)
	{
		return array_key_exists($role,$this->roles);
	}
	
	/*
	 * Return true if resource exist else false
	 * @since Glial 2.1.1
	 * @version 2.1.1
	 * @param  string $resource should validate this regex /[\w]+\/[\w]+/i => example : "controller/action"
	 * @return boolean Return true if resource exist else false
	 * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
	 * @description check if resource  exist or not
	 * @access public
	 */
	public function checkIfResourceExist($resource)
	{
		return array_key_exists($resource,$this->resources);
	}
	
	/*
	 * Return the string representation of the current element (List all roles, ressources and access defined)
	 * @since Glial 2.1.1
	 * @version 2.1.1
	 * @return String or display in standard output when asked in CLI mode
	 * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
	 * @description Display avaiable roles & ressources and the combinaisons allowed and denied 
	 * @access public
	 */
	public function __toString()
	{
		if (IS_CLI)
		{
			$cli = \Glial\Shell\Color::getColoredString("Available Roles", "black", "yellow") . "\n";
			foreach($this->roles as $role => $parents)
			{
				$cli .= "\t".$role."\n";
				foreach($parents as $parent)
				{
					$cli .= "\t".\Glial\Shell\Color::getColoredString("inherits", "purple")."\t".$parent."\n";
				}
			}
			
			$cli .= \Glial\Shell\Color::getColoredString("Available Resources", "black", "yellow") . "\n";
			foreach($this->resources as $resource => $parent)
			{
				$cli .= "\t".$resource."\n";
				
			}

			$cli .= \Glial\Shell\Color::getColoredString("Allow / Deny", "black", "yellow") . "\n";
			foreach($this->access as $group => $tab_ressource)
			{
				foreach($tab_ressource as $ressource => $allowed)
				{
					$cli .= "\t"
					.\Glial\Shell\Color::getColoredString($group, "light_green").str_repeat(" ",20-mb_strlen($group))
					.\Glial\Shell\Color::getColoredString($ressource, "light_red").str_repeat(" ",20-mb_strlen($ressource))
					.\Glial\Shell\Color::getColoredString($allowed, "light_blue")."\n";
				}
			}
			
			return $cli;
		}
		else
		{
			$html = '<ul><h1>Available Roles</h1>';
			foreach($this->roles as $role => $parents)
			{
				$html .= '<li>'.$role.'<br />';
				foreach($parents as $parent)
				{
					$html .= '<i>inherits</i>  '.$parent.'</li>';
				}
				$html .= '</li>';
			}
			$html .= '</ul><ul><h1>Available Resources</h1>';
			foreach($this->resources as $resource => $parent)
			{
				$html .= '<li>'.$resource.'</li>';
			}
			$html .= '</ul><ul><h1>Allow / Deny</h1>';
			foreach($this->access as $group => $tab_ressource)
			{
				foreach($tab_ressource as $ressource => $allowed)
				{
					$html .= '<li>'.$group .'+'. $ressource.': '. $allowed.'</li>';
				}
			}
			return $html.'</ul>';
		}
	}
}
