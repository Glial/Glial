<?php

/**
 * Glial Framework
 *
 * LICENSE
 *
 *
 */

namespace Glial\Auth;

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

    /*
     * 
     * function will be used to hash / crypt password
     */
    private $_fctToHashCookie;
    
    
    
    private $id_user=0;

    public function __construct()
    {
        
    }

    public static function setInstance($DbLink, $TableName, $Param)
    {
        if (get_parent_class($DbLink) != "Glial\Sgbd\Sql\Sql") {
            throw new \DomainException('GLI-001 : DbLink should be an object create by a children of the class Glial\Sgbd\Sql\Sql');
        }

        if (!in_array($TableName, $DbLink->getListTable()['table'])) {
            throw new \DomainException('GLI-002 : TableName "' . $TableName . '" seem doesnt exist in the database');
        }

        if (!file_exists(TMP . "/database/" . $TableName . ".table.txt")) {
            throw new \Exception('GLI-003 : the file cash of "' . $TableName . '" doent exist');
        }

        $fields = $DbLink->getInfosTable($TableName)['field'];

        if (!in_array($Param[0], $fields)) {
            throw new \Exception('GLI-004 : the field login referenced by "' . $TableName . '.' . $Param[0] . '" doent exist');
        }

        if (!in_array($Param[1], $fields)) {
            throw new \Exception('GLI-005 : the field password referenced by "' . $TableName . '.' . $Param[1] . '" doent exist');
        }

        if ((string) $Param[0] === (string) $Param[1]) {
            throw new \Exception('GLI-006 : the field login and password must be different');
        }


        self::$_dbLink = $DbLink;
        self::$_tableName = $TableName;
        self::$_login = $Param[0];
        self::$_passwd = $Param[1];
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
            
            if (empty($_POST[self::$_tableName][self::$_login]) || empty($_POST[self::$_tableName][self::$_passwd]))
            {
                return true;
            }
            

            $Identity = self::$_dbLink->sql_real_escape_string($_POST[self::$_tableName][self::$_login]);
            $Credential = self::$_dbLink->sql_real_escape_string($_POST[self::$_tableName][self::$_passwd]);

            $hash_password = $this->hash_password($Identity, $Credential);


            if (LDAP_CHECK) {
                if ($ldap = $this->checkLdap($Identity, $Credential)) {
                    $sql = "select * from " . self::$_tableName . " where " . self::$_login . " = '" . $Identity . "'";
                    $res = self::$_dbLink->sql_query($sql);

                    $data = array();

                    if (self::$_dbLink->sql_num_rows($res) === 1) {

                        $ob = self::$_dbLink->sql_fetch_object($res);
                        $this->_user = $ob;

                        $data[self::$_tableName]['id'] = $ob->id;
                        $data[self::$_tableName][self::$_passwd] = $hash_password;
                        $data[self::$_tableName]['date_last_login'] = date('Y-m-d H:i:s');
                        $data[self::$_tableName]['date_last_connected'] = date('Y-m-d H:i:s');
                    } elseif (self::$_dbLink->sql_num_rows($res) === 0) {

                        $data[self::$_tableName]['date_last_login'] = date('Y-m-d H:i:s');
                        $data[self::$_tableName]['date_last_connected'] = date('Y-m-d H:i:s');
                        $data[self::$_tableName][self::$_passwd] = $hash_password;
                        $data[self::$_tableName][self::$_login] = $ldap['samaccountname'][0];
                        $data[self::$_tableName]['email'] = $ldap['mail'][0];
                        $data[self::$_tableName]['name'] = strtoupper($ldap['sn'][0]);
                        $data[self::$_tableName]['firstname'] = $ldap['givenname'][0];
                        $data[self::$_tableName]['ip'] = $_SERVER["REMOTE_ADDR"];
                        $data[self::$_tableName]['date_created'] = substr($ldap['whencreated'][0], 0, 4) . "-" . substr($ldap['whencreated'][0], 4, 2) . "-" . substr($ldap['whencreated'][0], 6, 2) .
                                " " . substr($ldap['whencreated'][0], 8, 2) . ":" . substr($ldap['whencreated'][0], 10, 2) . ":" . substr($ldap['whencreated'][0], 12, 2);
                        $data[self::$_tableName]['id_group'] = 2;
                        $data[self::$_tableName]['is_valid'] = 0;
                    } else {
                        throw new \Exception('GLI-999 : Whilte list failed');
                    }

                    if (!self::$_dbLink->sql_save($data)) {
                        debug($data);
                        debug(self::$_dbLink->sql_error());
                        die();
                    }

                    $sql = "select * from " . self::$_tableName . " where " . self::$_login . " = '" . self::$_dbLink->sql_real_escape_string($ldap['samaccountname'][0]) . "'";
                    $res = self::$_dbLink->sql_query($sql);
                    $ob = self::$_dbLink->sql_fetch_object($res);
                    $this->_user = $ob;
                    
                    $this->id_user = $ob->id;

                    setcookie(self::$_name_cookie_login, $ob->{self::$_login}, time() + AUTH_SESSION_TIME, '/', $_SERVER['SERVER_NAME'], false, true);
                    setcookie(self::$_name_cookie_passwd, $hash_password, time() + AUTH_SESSION_TIME, '/', $_SERVER['SERVER_NAME'], false, true);
                    return true;
                }

                return false;
            } else {
                $sql = "select * from " . self::$_tableName . " where " . self::$_login . " = '" . $Identity . "'";
                
                $res = self::$_dbLink->sql_query($sql);

                if (self::$_dbLink->sql_num_rows($res) === 1) {
                    $ob = self::$_dbLink->sql_fetch_object($res);
                    $this->id_user = $ob->id;
                    
                    if ($hash_password === $ob->{self::$_passwd}) {
                        $this->_user = $ob;

                        setcookie(self::$_name_cookie_login, $ob->{self::$_login}, time() + AUTH_SESSION_TIME, '/', $_SERVER['SERVER_NAME'], false, true);
                        setcookie(self::$_name_cookie_passwd, $hash_password, time() + AUTH_SESSION_TIME, '/', $_SERVER['SERVER_NAME'], false, true);

                        return true;
                    }
                }
            }
        }

        if (empty($_POST[self::$_tableName][self::$_login])) {
            if (!empty($_COOKIE[self::$_name_cookie_login]) && !empty($_COOKIE[self::$_name_cookie_passwd])) {
                $sql = "select * from " . self::$_tableName . " where " . self::$_login . " = '" . self::$_dbLink->sql_real_escape_string($_COOKIE[self::$_name_cookie_login]) . "'";

                $res = self::$_dbLink->sql_query($sql);

                if (self::$_dbLink->sql_num_rows($res) === 1) {
                    $ob = self::$_dbLink->sql_fetch_object($res);



                    if ($_COOKIE[self::$_name_cookie_passwd] === $ob->{self::$_passwd}) {
                        //if (password_verify($ob->{self::$_passwd}, $_COOKIE[self::$_name_cookie_passwd]) ) {

                        $this->_user = $ob;

                        if ($ob->is_valid == 0) {

                            if ($_SERVER['REQUEST_METHOD'] === "GET") {
                                $msg = I18n::getTranslation(__("Hello,") . "<br />" . __("Thank you for registering.") . "<br />"
                                                . __("To finalise your registration, an administrator have to give you a role."));

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

        $r = ldap_bind($ds, LDAP_BIND_DN, LDAP_BIND_PASSWD);     // connexion anonyme, typique

        if ($r) {
            $sr = ldap_search($ds, "CN=Users,DC=pws,DC=com", "sAMAccountName=" . $login);
            if (ldap_count_entries($ds, $sr) === 1) {
                $info = ldap_get_entries($ds, $sr);
                if ($info["count"] === 1) {

                    $dn = $info[0]["dn"];
                    $r = @ldap_bind($ds, $dn, $password);     // try credentials

                    if ($r) {
                        //debug($info[0]);
                        return $info[0];
                    }
                }
            }
            ldap_close($ds);
        } else {
            throw new \Exception('GLI-067 : Impossible to connect to LDAP server :"' . LDAP_URL . ':' . LDAP_PORT . '"');
        }
        return false;
    }

    public function getIdUserTriingLogin()
    {
        return $this->id_user;
    }
    
    public function hash_password($login, $password)
    {
        return sha1(sha1($password . sha1($login)));;
    }
}
