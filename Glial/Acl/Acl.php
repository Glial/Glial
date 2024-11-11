<?php
/**
 * Glial Framework
 *
 * LICENSE
 *
 *
 */

namespace Glial\Acl;

use Glial\Cli\Color;

class Acl
{
    protected $roles     = array();
    protected $resources = array();
    protected $access    = array();
    protected $alias     = array();
    protected $maxLength = array();
    var $inifile;

    /*
     * if acl/acl.txt is undefined parse the right in CONFIG ."acl.config.ini.php" and serialize it in acl/acl.txt
     * if acl/acl.txt is defined just unserialize acl/acl.txt and setup the object
     * @since Glial 2.1.1
     * @version 4.1.12 compare last update if neede we delete acl.ser
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @description return an array to be serialized in a flat file
     * @access public
     */

    public function __construct($inifile)
    {

        $this->inifile = $inifile;

        $path_to_acl_tmp = TMP."acl/acl.ser";


        if (file_exists($path_to_acl_tmp)) {
            if (filemtime($this->inifile) > filemtime($path_to_acl_tmp)) {
                unlink($path_to_acl_tmp);
            }
        }

        if (file_exists($path_to_acl_tmp)) {

            if (is_file($path_to_acl_tmp)) {
                $s               = implode('', file($path_to_acl_tmp));
                $tmp             = unserialize($s);
                $this->roles     = $tmp->roles;
                $this->resources = $tmp->resources;
                $this->access    = $tmp->access;
                $this->maxLength = $tmp->maxLength;
                $this->alias     = $tmp->alias;
                return true;
            }
        }

        $this->setResource();
        $this->loadIniFile($inifile);

        file_put_contents($path_to_acl_tmp, serialize($this));
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
        return array('roles', 'resources', 'access', 'maxLength', 'alias');
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
        $this->maxLength['ressource'] = 0;

        $class                   = new \ReflectionClass("\Glial\Synapse\Controller");
        $methods                 = $class->getMethods();
        $class_controller_method = array();

        foreach ($methods as $method) {
            $class_controller_method[] = $method->name;
        }
        
        $dir = APP_DIR.DS."Controller".DS;
        if (is_dir($dir)) {
            $dh = opendir($dir);
            if ($dh) {
                while (($file = readdir($dh)) !== false) {

                    

                    if (strstr($file, '.php')) {

                        if (filetype($dir.$file) != "file" || substr($file, 0, 1) === ".") {
                            continue;
                        }

                        $class_name = explode(".", $file);
                        $controller = $class_name[0];




                        $path = '\\App\\Controller\\';
                        $name = $controller;

                        $class = $path.$name;

    
                        
                        // On ajoute le fichier a la main sans passer par l'autolaoder
                        if (!class_exists($class)) {
                            require_once($dir.$file);
                        }

                        //si le chemin de class ne correspond pas au nom de la class, penser a vérifier le namespace également.
                        if (!class_exists($class)) {
                            throw new \Exception('GLI-034 : The class must be with the same name (check '.ROOT.'App/Controller/'.$controller.'.php)');
                        }

                        $tab  = get_class_methods($class);
                        //substract methods from class parent (\Glial\Synapse\Controller)
                        $tab3 = array_diff($tab, $class_controller_method);

                        foreach ($tab3 as $action) {
                            $this->addResource($controller."/".$action);

                            if (strlen($controller."/".$action) > $this->maxLength['ressource']) {
                                $this->maxLength['ressource'] = strlen($controller."/".$action);
                            }
                        }
                    }
                }

                closedir($dh);
            }
        } else {
            throw new \Exception('GLI-035 : impossible to open dir "'.$dir.'"');
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
        if (!is_file($filename)) {
            throw new \Exception('GLI-008 : impossible to load the ini file : "'.$filename.'".');
        }

        $this->maxLength['role'] = 0;

        $tab = parse_ini_file($filename, true);


        //add alias
        foreach ($tab['alias'] as $role => $alias) {
            $this->alias[$alias] = $role;
        }

        //definistion des roles
        foreach ($tab['role']['add'] as $role) {
            $this->addRole($role);
            if (strlen($role) > $this->maxLength['role']) {
                $this->maxLength['role'] = strlen($role);
            }
        }

        unset($tab['role']['add']);

        //imbrication des roles (hierarchiquement)
        foreach ($tab['role'] as $role1 => $tab_role) {
            foreach ($tab_role as $role2)
                $this->addRole($role1, $role2);
        }

        //allow
        foreach ($tab['allow'] as $role => $tab_ressource) {
            foreach ($tab_ressource as $ressource) {

                if (strpos($ressource, '*') === false) {
                    $this->allow($role, $ressource);
                } else {
                    if (strlen($ressource) === 1) {
                        foreach ($this->resources as $key => $val) {
                            $this->allow($role, $key);
                        }
                    } else {
                        $ressource = str_replace("*", "", $ressource);

                        foreach ($this->resources as $key => $val) {
                            if (strstr($key, $ressource)) {
                                $this->allow($role, $key);
                            }
                        }
                    }
                }
            }
        }

        //deny
        foreach ($tab['deny'] as $role => $tab_ressource) {
            foreach ($tab_ressource as $ressource) {
                $this->deny($role, $ressource);
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
        if (is_string($resources)) {
            $this->resources[$resources] = '';
        } else if (is_array($resources)) {
            foreach ($resources as $resource) {
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

    function addRole($role, $parents = '')
    {
        if (is_string($parents)) {
            if ($parents == '') {
                $this->roles[$role] = array();
            } else {
                $this->roles[$role][] = $parents;
            }
        } else if (is_array($parents)) {
            foreach ($parents as $parent) {
                $this->roles[$role][] = $parent;
            }
        } else {
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

    function deny($role, $resources)
    {
        if (is_string($resources)) {
            $this->setAccess($role, $resources, 'deny');
        } else if (is_array($resources)) {
            foreach ($resources as $resource) {
                $this->setAccess($role, $resource, 'deny');
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

    function allow($role, $resources)
    {
        if (is_string($resources)) {
            $this->setAccess($role, $resources, 'allow');
        } else if (is_array($resources)) {
            foreach ($resources as $resource) {
                $this->setAccess($role, $resource, 'allow');
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

    private function setAccess($role, $resource, $access = 'deny')
    {
        if ($this->checkIfRoleExist($role) || $this->checkIfResourceExist($resource)) {
            $this->access[$role][$resource] = $access;
        }
    }
    /*
     * Return true if match between role and resource is allowed
     * Version 2.1.2 add Alias
     * @since Glial 2.1.1
     * @version 2.1.2
     * @param  string $role the roles are defined in acl.config.ini.php
     * @param  string $resource should validate this regex /[\w]+\/[\w]+/i => example : "controller/action"
     * @return boolean Return true if role and resource match
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @description Return true if match between role and resource is allowed
     * @access public
     */

    function isAllowed($role, $resource)
    {

        $role = $this->getAliasIfExist($role);

        //We first check that the resource & role exist
        if ($this->checkIfRoleExist($role) && $this->checkIfResourceExist($resource)) {
            //He has access to something
            if (array_key_exists($role, $this->access)) {
                //Maybe to this resource
                if (array_key_exists($resource, $this->access[$role])) {
                    //Is he allowed
                    if ($this->access[$role][$resource] === 'allow') {
                        return true;
                    }

                    //If he is not allowed we return false
                    if ($this->access[$role][$resource] === 'deny') {
                        return false;
                    }
                }
            }

            //Maybe a parent...?
            if (count($this->roles[$role]) > 0) {
                //We ask his parents				
                foreach ($this->roles[$role] as $parent) {
                    //We go deeper in the rabbit hole...
                    if ($this->isAllowed($parent, $resource)) {
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
        return array_key_exists($role, $this->roles);
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
        return array_key_exists($resource, $this->resources);
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
        if (IS_CLI) {
            $number_length = ceil(log(count($this->resources), 10));

            $cli = "┌"
                .str_repeat("─", 2 + $this->maxLength['ressource'] + $number_length)
                .str_repeat("┬─", count($this->roles))
                ."┐\n";


            ksort($this->resources);
            ksort($this->roles);

            $tab_role = array();
            foreach ($this->roles as $role => $var) {
                $tab_role[] = str_split(str_repeat(" ", $this->maxLength['role'] - mb_strlen($role)).$role);
            }

            for ($i = 0; $i < $this->maxLength['role']; $i++) {
                $cli .= "│ ".str_repeat(" ", $number_length + $this->maxLength['ressource'])." ";

                foreach ($tab_role as $role) {
                    $cli .= "│".Color::getColoredString($role[$i], "grey");
                }

                $cli .= "│\n";
            }

            $cli .= "├"
                .str_repeat("─", $number_length + 2 + $this->maxLength['ressource'])
                .str_repeat("┼─", count($this->roles))
                ."┤\n";

            $i = 1;

            foreach ($this->resources as $resource => $parent) {

                $background = (($i - 1) % 3 === 0) ? "blue" : "black";

                $cli .= Color::getColoredString("│", null, $background)
                    .Color::getColoredString(str_repeat(" ", $number_length - strlen($i)).$i, null, $background)
                    .Color::getColoredString(" ".$resource, null, $background)
                    .Color::getColoredString(str_repeat(" ", $this->maxLength['ressource'] - mb_strlen($resource)), null, $background)
                    .Color::getColoredString(" ", null, $background);

                foreach ($this->roles as $role => $var) {
                    $cli .= ($this->isAllowed($role, $resource)) ? Color::getColoredString("│■", null, $background) : Color::getColoredString("│ ", null, $background);
                }

                $cli .= Color::getColoredString("│", null, $background)."\n";

                $i++;
            }

            $cli .= "└"
                .str_repeat("─", $number_length + 2 + $this->maxLength['ressource'])
                .str_repeat("┴─", count($this->roles))
                ."┘\n";

            return $cli;
        } else {

            $html = '<ul><h1>Available Roles</h1>';
            foreach ($this->roles as $role => $parents) {
                $html .= '<li>'.$role.'<br />';
                foreach ($parents as $parent) {
                    $html .= '<i>inherits</i>  '.$parent.'</li>';
                }
                $html .= '</li>';
            }
            $html .= '</ul><ul><h1>Available Resources</h1>';
            foreach ($this->resources as $resource => $parent) {
                $html .= '<li>'.$resource.'</li>';
            }
            $html .= '</ul><ul><h1>Allow / Deny</h1>';
            foreach ($this->access as $group => $tab_ressource) {
                foreach ($tab_ressource as $ressource => $allowed) {
                    $html .= '<li>'.$group.'+'.$ressource.': '.$allowed.'</li>';
                }
            }
            return $html.'</ul>';
        }
    }
    /*
     * This function looking for the index in array alias if exist return the value else return the string set in param
     * @return String role
     * @since Glial 2.1.2
     * @version 2.1.2
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @description Return the real name of the role if exist
     * @access private
     */

    private function getAliasIfExist($role)
    {

        return (!empty($this->alias[$role])) ? $this->alias[$role] : $role;
    }

    public function getResources()
    {
        return $this->resources;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getAlias()
    {
        return $this->alias;
    }
    /*
     * 
     * @return array
     * @since Glial 4.1.11
     * @version 4.1.11
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @description Return all combinaison of roles / ressources
     * @access public
     */

    public function exportCombinaison()
    {
        $export = array();

        ksort($this->resources);

        foreach ($this->resources as $ressource => $vide) {
            foreach ($this->roles as $role => $vide2) {
                $export[$ressource][$role] = $this->isAllowed($role, $ressource);
            }
        }

        return $export;
    }
    /*
     * 
     * @return array
     * @since Glial 4.1.11
     * @version 4.1.11
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @description Return all combinaison of roles / ressources
     * @access public
     */

    public function getPathIniFile()
    {
        return $this->inifile;
    }
    /*
     * 
     * @return array
     * @since Glial 4.1.11
     * @version 4.1.11
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @description Return all combinaison of roles / ressources
     * @access public
     */

    public function export()
    {
        return parse_ini_file($filename, true);
    }
    /*
     * 
     * @return void
     * @param array
     * @since Glial 4.1.11
     * @version 4.1.11
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @description Return all combinaison of roles / ressources
     * @access public
     */

    public function import($data)
    {

        if ($this->checkValidity($data)) {



            $fp = fopen($this->getPathIniFile(), "w");

            if ($fp) {
                fwrite($fp, "[alias]");

                foreach ($data['alias'] as $id_group => $alias) {
                    fwrite($fp, $id_group." = ".$alias."\n");
                }

                fwrite($fp, "[role]");

                if (!empty($data['role']['add'])) {
                    foreach ($data['role']['add'] as $value) {
                        fwrite($fp, "add[] = ".$value."\n");
                    }


                    unset($data['role']['add']);
                }


                if (!empty($data['role'])) {

                    foreach ($data['role'] as $item => $parents) {

                        foreach ($parents as $parent) {
                            fwrite($fp, $item."[] = ".$parent."\n");
                        }
                    }
                }


                fwrite($fp, "[allow]");
                foreach ($data['role'] as $role => $alias) {
                    fwrite($fp, $id_group." = ".$alias."\n");
                }

                fwrite($fp, "[deny]");
            }
        }
    }

    private function checkValidity($data)
    {
        $test = array('alias', 'role', 'allow', 'deny');

        foreach ($test as $value) {
            if (empty($data[$value])) {

                throw new \Exception("GLI-581 : the format of array is not correct ('alias', 'role', 'allow', 'deny')", 80);
                return false;
            }
        }


        return true;
    }
    /*
     *
     * @return string
     * @param array
     * @since Glial 4.1.11
     * @version 4.1.11
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @description défini le niveau le plus bas, celui qui n'a pas de parents.
     * @access private
     */

    private function obtenirRangLePlusBas($roles)
    {
        $values = array();
        foreach ($roles as $key => $role) {
            if (count($role) === 0) {

                unset($roles[$key]);
                continue;
            }

            foreach ($role as $val) {
                $values[] = $val;
            }
        }

        $keys   = array_keys($roles);
        $values = array_unique($values);
        $main   = array_diff($values, $keys);

        if (count($main) !== 1) {
            throw new \Exception('GLI-127 : There is two lowest rank');
        }

        return end($main);
    }
    /*
     *
     * @return private
     * @param array
     * @since Glial 4.1.11
     * @version 4.1.11
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @description Return all combinaison of roles / ressources
     * @access public
     */

    private function calculHierarchie($roles, $lowest, &$ret = array())
    {

        if (empty($ret)) {
            $ret[1][] = $lowest;
            $indice   = 2;
        } else {
            $indice = (max(array_keys($ret)) + 1);
        }

        foreach ($roles as $key => $role) {

            foreach ($role as $val) {
                if ($val == $lowest) {
                    $ret[$indice][] = $key;

                    unset($roles[$key]);

                    if (count($roles) !== 0) {
                        $this->calculHierarchie($roles, $key, $ret);
                    }
                }
            }
        }

        return $ret;
    }
    /*
     *
     * @return array
     * @param void
     * @since Glial 4.1.11
     * @version 4.1.11
     * @author Aurélien LEQUOY <aurelien.lequoy@esysteme.com>
     * @description retourne la hiérarchie des groupes utilisateurs, le plus élévé est celui avec le plus de droit
     * @access public
     */

    public function obtenirHierarchie()
    {
        $roles = $this->roles;

        if (!empty($roles['add'])) {
            unset($roles['add']);
        }

        $lowest = $this->obtenirRangLePlusBas($roles);
        $ret    = $this->calculHierarchie($roles, $lowest);

        return $ret;
    }
}
