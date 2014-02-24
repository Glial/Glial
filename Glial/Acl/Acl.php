<?php

/**
 * Glial Framework
 *
 * LICENSE
 *
 *
 */
namespace Glial\Acl;


use \Glial\Synapse\Controller;
use \Glial\Shell\Color;

class Acl
{
    public $data = array();
    public $id_group = 0;
	
	/****/
	
	protected $roles = array(); 
    protected $resources = array(); 
    public $access = array(); 
	

    public function __construct($id_group)
    {
        $dir = TMP . "acl/acl.txt";
        $this->data = unserialize(file_get_contents($dir));
        $this->id_group = $id_group;
    }

    public function isAllowed3($controller = NULL, $action = NULL)
    {
        if (!empty($controller)) {
            if (!empty($action)) {
                if (!empty($this->data[$this->id_group][$controller][$action])) {

                    if ($this->data[$this->id_group][$controller][$action] == 1) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

	/***********/
	
	function setRessource()
	{
	
		$class = new \ReflectionClass("\Glial\Synapse\Controller");
		$methods = $class->getMethods();
	
		$data = unserialize(serialize($methods));
		
		$class_controller_method = array();
		
		
		foreach($data as $tab)
		{
			$class_controller_method[] = $tab->name;
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
						

						

						$tab3 = array_diff($tab3, $class_controller_method);
						
						foreach ($tab3 as $action) {
							
							//echo $controller."/".$action . PHP_EOL;
							$this->addResource($controller."/".$action); 
							
						}
					}
				}

				closedir($dh);
			}
		}
	
	}
	
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
			throw new Exception('El recurso no se trata ni de un string ni de un array de strings');
		}
	}
	
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
	
	private function setAccess($role,$resource,$access = 'deny')
	{
		if($this->checkIfRoleExist($role) || $this->checkIfResourceExist($resource))
		{
			$this->access[$role][$resource] = $access;
		}
	}	
	
	/**
	 * Este metodo permite conocer si este 'Rol' tiene derecho a acceder
	 * un recurso.
	 * 
	 * @param $role (String), $resource (String)
	 * @return BOOL 
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
					if($this->access[$role][$resource] == 'allow')
					{	
						return true;
					}
					
					//If he is not allowe we return false
					if($this->access[$role][$resource] == 'deny')
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
	
	/********************************************/
	/*              METODOS PRIVATE             */
	/********************************************/
	
	/*
		Comprueba que existe el rol
	*/
	private function checkIfRoleExist($role)
	{
		return array_key_exists($role,$this->roles);
	}
	
	/*
		Comprueba que existe el recurso
	*/
	private function checkIfResourceExist($resource)
	{
		return array_key_exists($resource,$this->resources);
	}
	
	/*private function getResource($role,$resource)
	{
		if(array_key_exist($role,$this->access)
	}*/
	

	/*
	 * List all roles & ressources defined
	 * @since Glial 2.1.1
	 * @version 2.1.1
	 * @return String or display in standard output when asked in CLI mode
	 * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
	 * @description Display avaiable roles & ressources and the combinaisons allowed and denied 
	 * @access public
	 */
	function __toString()
	{
		if (IS_CLI)
		{
			echo Color::getColoredString("Available Roles", "black", "yellow") . "\n";
			foreach($this->roles as $role => $parents)
			{
				echo "\t".$role."\n";
				foreach($parents as $parent)
				{
					echo "\t".Color::getColoredString("inherits", "purple")."\t".$parent."\n";
				}
			}
			
			echo Color::getColoredString("Available Resources", "black", "yellow") . "\n";
			foreach($this->resources as $resource => $parent)
			{
				echo "\t".$resource."\n";
				
			}

			echo Color::getColoredString("Allow / Deny", "black", "yellow") . "\n";
			foreach($this->access as $group => $tab_ressource)
			{
				foreach($tab_ressource as $ressource => $allowed)
				{
					echo "\t"
					.Color::getColoredString($group, "light_green").str_repeat(" ",20-mb_strlen($group))
					.Color::getColoredString($ressource, "light_red").str_repeat(" ",20-mb_strlen($ressource))
					.Color::getColoredString($allowed, "light_blue")."\n";
				}
			}
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
