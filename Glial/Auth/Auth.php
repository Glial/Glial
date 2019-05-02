<?php
/**
 * Glial Framework
 *
 * LICENSE
 *
 *
 */

namespace Glial\Auth;

use \Glial\Acl\Acl;
use \Glial\I18n\I18n;

class Auth
{
    /*
     * 
     * Link to connect to database
     */
    static protected $_dbLink;

    /*
     *
     * Table name where are stored Loign // password
     */
    static protected $_tableName;

    /*
     * Name of the field where are stored the login
     */
    static protected $_login;
    /*
     * Name of the field where are stored the password
     */
    static protected $_passwd;

    /*
     * 
     * All informations from the table $_tableName
     */
    private $_user;

    /*
     *
     * name used to store login in cookie
     */
    static private $_name_cookie_login = "3QafE7C6RSXTzGw6";

    /*
     * 
     * name used to store password in cookie
     */
    static private $_name_cookie_passwd = "RU2M5wpaAvpEqeDz";
    private $log;

    /*
     *
     * function will be used to hash / crypt password
     */
    private $_fctToHashCookie;
    private $_fctToCryptCookie;
    private $_fctToUnCryptCookie;
    private $id_user = 0;

    public function __construct()
    {
        
    }

    public static function setInstance($DbLink, $TableName, $Param)
    {
        if (get_parent_class($DbLink) != "Glial\Sgbd\Sql\Sql") {
            throw new \DomainException('GLI-001 : DbLink should be an object create by a children of the class Glial\Sgbd\Sql\Sql');
        }

        if (!in_array($TableName, $DbLink->getListTable()['table'])) {
            throw new \DomainException('GLI-002 : TableName "'.$TableName.'" seem doesnt exist in the database');
        }

        $path = TMP."/database/".$TableName.".table.txt";
        if (!file_exists($path)) {
            throw new \Exception('GLI-003 : the file cash of "'.$TableName.'" ('.$path.') doent exist');
        }

        $fields = $DbLink->getInfosTable($TableName)['field'];

        if (!in_array($Param[0], $fields)) {
            throw new \Exception('GLI-004 : the field login referenced by "'.$TableName.'.'.$Param[0].'" doent exist');
        }

        if (!in_array($Param[1], $fields)) {
            throw new \Exception('GLI-005 : the field password referenced by "'.$TableName.'.'.$Param[1].'" doent exist');
        }

        if ((string) $Param[0] === (string) $Param[1]) {
            throw new \Exception('GLI-006 : the field login and password must be different');
        }


        self::$_dbLink    = $DbLink;
        self::$_tableName = $TableName;
        self::$_login     = $Param[0];
        self::$_passwd    = $Param[1];
    }

    public function setFctToHashCookie($function)
    {
        $this->_fctToHashCookie = $function;
    }

    public function authenticate($check_post = true)
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST" && $check_post) {

            if (empty($_POST[self::$_tableName][self::$_login])) {
                return false;
            }

            if (empty($_POST[self::$_tableName][self::$_passwd])) {
                return false;
            }

            if (empty($_POST[self::$_tableName][self::$_login]) || empty($_POST[self::$_tableName][self::$_passwd])) {
                return true;
            }

            $Identity   = self::$_dbLink->sql_real_escape_string($_POST[self::$_tableName][self::$_login]);
            $Credential = self::$_dbLink->sql_real_escape_string($_POST[self::$_tableName][self::$_passwd]);

            $hash_password = $this->hashPassword($Identity, $Credential);

            //$this->$log->info('hash : '.$hash_password);

            if (LDAP_CHECK) {

                if ($ldap = $this->checkLdap($Identity, $Credential)) {



                    $sql = "select * from ".self::$_tableName." where ".self::$_login." = '".$Identity."'";
                    $res = self::$_dbLink->sql_query($sql);

                    $data = array();

                    if (self::$_dbLink->sql_num_rows($res) === 1) {

                        $this->log->info("[AUTH][LDAP] $Identity : Login Successful");

                        $ob          = self::$_dbLink->sql_fetch_object($res);
                        $this->_user = $ob;

                        $data[self::$_tableName]['id']                  = $ob->id;
                        $data[self::$_tableName][self::$_passwd]        = $hash_password;
                        $data[self::$_tableName]['date_last_login']     = date('Y-m-d H:i:s');
                        $data[self::$_tableName]['date_last_connected'] = date('Y-m-d H:i:s');

                        $id_group = $this->getIdGroup($id_user_main, $ldap);

                        $data[self::$_tableName]['id_group'] = $id_group;

                    } elseif (self::$_dbLink->sql_num_rows($res) === 0) {

                        $this->log->info("[AUTH][ADD USER] $Identity");
                        $this->log->info("[AUTH][LDAP] $Identity : Login Successful");
//debug($ldap);

                        $sql = "SELECT id FROM geolocalisation_country WHERE iso = '".$ldap['c'][0]."'";
                        $res = self::$_dbLink->sql_query($sql);
                        while ($ob  = self::$_dbLink->sql_fetch_object($res)) {
                            $data[self::$_tableName]['id_geolocalisation_country'] = $ob->id;
                        }

//to do find the good city
                        $sql = "select * from geolocalisation_city where libelle ='".$ldap['l'][0]."' LIMIT 1";
                        $res = self::$_dbLink->sql_query($sql);
                        while ($ob  = self::$_dbLink->sql_fetch_object($res)) {
                            $data[self::$_tableName]['id_geolocalisation_city'] = $ob->id;
                        }

                        $data[self::$_tableName]['key_auth']            = "";
                        $data[self::$_tableName]['date_last_login']     = date('Y-m-d H:i:s');
                        $data[self::$_tableName]['date_last_connected'] = date('Y-m-d H:i:s');
                        $data[self::$_tableName][self::$_passwd]        = $hash_password;
                        $data[self::$_tableName][self::$_login]         = $ldap['samaccountname'][0];
                        $data[self::$_tableName]['email']               = $ldap['mail'][0];
                        $data[self::$_tableName]['name']                = strtoupper($ldap['sn'][0]);
                        $data[self::$_tableName]['firstname']           = $ldap['givenname'][0];
                        $data[self::$_tableName]['ip']                  = $_SERVER["REMOTE_ADDR"];
                        $data[self::$_tableName]['date_created']        = substr($ldap['whencreated'][0], 0, 4)."-".substr($ldap['whencreated'][0], 4, 2)."-".substr($ldap['whencreated'][0], 6, 2).
                            " ".substr($ldap['whencreated'][0], 8, 2).":".substr($ldap['whencreated'][0], 10, 2).":".substr($ldap['whencreated'][0], 12, 2);
                        $data[self::$_tableName]['id_group']            = 1;
                        $data[self::$_tableName]['is_valid']            = 1;
                        $data[self::$_tableName]['is_ldap']             = 1;
                    } else {
                        throw new \Exception('GLI-999 : Whilte list failed');
                    }

                    $id_user_main = self::$_dbLink->sql_save($data);

                    if (!$id_user_main) {
                        debug($data);
                        debug(self::$_dbLink->sql_error());
                        die();
                    } else {

                        $id_group = $this->getIdGroup($id_user_main, $ldap);

                        $data                                = array();
                        $data[self::$_tableName]['id_group'] = $id_group;
                        $data[self::$_tableName]['id']       = $id_user_main;
                        $ret                                 = self::$_dbLink->sql_save($data);


                        if (!$ret) {
                            debug($data);
                            debug(self::$_dbLink->sql_error());
                            die();
                        }


                        $this->log->info("[AUTH][GROUP] change group : ".$id_group." for user ($id_user_main)");
                    }

                    $sql         = "select * from `".self::$_tableName."` where `".self::$_login."` = '".self::$_dbLink->sql_real_escape_string($ldap['samaccountname'][0])."';";
                    $res         = self::$_dbLink->sql_query($sql);
                    $ob          = self::$_dbLink->sql_fetch_object($res);
                    $this->_user = $ob;

                    $this->id_user = $ob->id;

                    setcookie(self::$_name_cookie_login, $ob->{self::$_login}, time() + AUTH_SESSION_TIME, '/', $_SERVER['SERVER_NAME'], false, true);
                    setcookie(self::$_name_cookie_passwd, $hash_password, time() + AUTH_SESSION_TIME, '/', $_SERVER['SERVER_NAME'], false, true);
                    return true;
                }
            }


            //test this in anycase for root account
            $sql = "select * from `".self::$_tableName."` where `".self::$_login."` = '".$Identity."' AND is_ldap=0;";
            $this->log->info("[AUTH][SQL] $sql");


            $res = self::$_dbLink->sql_query($sql);

            if (self::$_dbLink->sql_num_rows($res) === 1) {
                $ob            = self::$_dbLink->sql_fetch_object($res);
                $this->id_user = $ob->id;

                $hash2 = self::hashPassword($Identity, $Credential);

                if ($this->checkPassword($Identity, $Credential, $ob->{self::$_passwd})) {

                    $this->log->info("[AUTH][POST] $Identity : Login Successful");

                    $this->_user = $ob;

                    $data[self::$_tableName][self::$_passwd] = $hash_password;
                    $data[self::$_tableName]['id']           = $ob->id;

                    if (!self::$_dbLink->sql_save($data)) {
                        debug($data);
                        debug(self::$_dbLink->sql_error());
                        die();
                    }

                    setcookie(self::$_name_cookie_login, $ob->{self::$_login}, time() + AUTH_SESSION_TIME, '/', $_SERVER['SERVER_NAME'], false, true);
                    setcookie(self::$_name_cookie_passwd, $hash_password, time() + AUTH_SESSION_TIME, '/', $_SERVER['SERVER_NAME'], false, true);

                    return true;
                }
            }

            $this->log->info("[AUTH][POST] $Identity : Login FAILED ! ");

            return false;
        }


        if (empty($_POST[self::$_tableName][self::$_login])) {
            if (!empty($_COOKIE[self::$_name_cookie_login]) && !empty($_COOKIE[self::$_name_cookie_passwd])) {
                $sql = "select * from `".self::$_tableName."` where `".self::$_login."` = '".self::$_dbLink->sql_real_escape_string($_COOKIE[self::$_name_cookie_login])."';";

                $res = self::$_dbLink->sql_query($sql);

                if (self::$_dbLink->sql_num_rows($res) === 1) {
                    $ob = self::$_dbLink->sql_fetch_object($res);

                    if ($_COOKIE[self::$_name_cookie_passwd] === $ob->{self::$_passwd}) {

                        $this->_user = $ob;

                        if ($ob->is_valid == 0) {

                            if ($_SERVER['REQUEST_METHOD'] === "GET") {
                                $msg = I18n::getTranslation(__("Hello,")."<br />".__("Thank you for registering.")."<br />"
                                        .__("To finalise your registration, an administrator have to give you a role."));

                                $title = I18n::getTranslation(__("Restricted access"));
                                set_flash("caution", $title, $msg);
                            }
                        }
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function checkAuth($login, $password)
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            
        }
    }

    public function getUser()
    {
        return $this->_user;
    }

    public function getAccess()
    {
        if (empty($this->_user->id_group)) {
            return 1;
        } else {
            return $this->_user->id_group;
        }
    }

    public function logout()
    {
        setcookie(self::$_name_cookie_login, '', time() - 1000, '/', $_SERVER['SERVER_NAME'], false, true);
        setcookie(self::$_name_cookie_passwd, '', time() - 1000, '/', $_SERVER['SERVER_NAME'], false, true);
    }

    public function checkLdap($login, $password)
    {

        $ds = ldap_connect(LDAP_URL, LDAP_PORT);  // doit être un serveur LDAP valide !
        $r  = ldap_bind($ds, LDAP_BIND_DN, LDAP_BIND_PASSWD);     // connexion anonyme, typique

        if ($r) {
            $sr = ldap_search($ds, LDAP_ROOT_DN, "sAMAccountName=".$login);

            $entries = ldap_count_entries($ds, $sr);

            if ($entries === 1) {
                $info = ldap_get_entries($ds, $sr);
                if ($info["count"] === 1) {

                    $dn = $info[0]["dn"];
                    $r  = ldap_bind($ds, $dn, $password);     // try credentials

                    if ($r) {
//debug($info[0]);
                        return $info[0];
                    }
                }
            }

            ldap_close($ds);
        } else {
            throw new \Exception('GLI-067 : Impossible to connect to LDAP server :"'.LDAP_URL.':'.LDAP_PORT.'"');
        }
        return false;
    }

    public function getIdUserTriingLogin()
    {
        return $this->id_user;
    }

    static public function hashPassword($login, $password)
    {
        return password_hash(self::saltPassword($login, $password), PASSWORD_DEFAULT);
    }

    public function setFctToCryptCookie($function)
    {
        $this->_fctToCryptCookie = $function;
    }

    public function setFctToUnCryptCookie($function)
    {
        $this->_fctToUnCryptCookie = $function;
    }

    static public function checkPassword($login, $password, $hash)
    {
        return password_verify(self::saltPassword($login, $password), $hash);
    }

    public function setLog($log)
    {
        $this->log = $log;
    }

    public function setIdGroup($id_group)
    {
        if (!is_int($id_group)) {
            throw new \Exception("GLI-057 : id_group should be an int (id_group : ".$id_group.")");
        }

        $this->_user->id_group = $id_group;
    }

    private function getIdGroup($id_user_main, $ldap)
    {
        //in case we cannot linkto any group
        $id_group = 1;

        $sql = "SELECT id_group, cn FROM `ldap_group`";
        $res = self::$_dbLink->sql_query($sql);

        $cn = array();
        while ($ob = self::$_dbLink->sql_fetch_object($res)) {
            $cn[$ob->id_group] = $ob->cn;
        }

        $acl = new Acl(CONFIG."acl.config.ini");

        $tree  = $acl->obtenirHierarchie();
        $alias = $acl->getAlias();
        $alias = array_flip($alias);

        unset($ldap['memberof']['count']);

        $memberof = $ldap['memberof'];

        foreach ($memberof as $key => $value) {
            $memberof[$key] = utf8_encode($value);
        }

        $resultat = array_intersect($cn, $memberof);

        $this->log->info("[LDAP][CONFIGURED]", $cn);
        $this->log->info("[LDAP][MEMBEROF]", $memberof);
        $this->log->info("[LDAP][MATCH]", $resultat);

        $id_group_available = array_keys($resultat);

        $this->log->info("Tree ACL ", $tree);

        foreach ($tree as $levels) {
            foreach ($levels as $level) {
                if (in_array($alias[$level], $id_group_available)) {
                    $id_group = $alias[$level];
                }
            }
        }

        return $id_group;
    }

    static public function saltPassword($login, $password)
    {
        return sha1($password.sha1($login));
    }
}