<?php

/**
 * Glial Framework
 *
 * LICENSE
 *
 *
 */

namespace Glial\Auth;

class Auth
{

    static protected $_dbLink;
    static protected $_tableName;
    static protected $_login;
    static protected $_passwd;
    private $_user;
    private $_idGroup = 1;
    static private $_name_cookie_login = "3QafE7C6RSXTzGw6";
    static private $_name_cookie_passwd = "RU2M5wpaAvpEqeDz";
    private $_fctToHashCookie;

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

    public function authenticate($Identity, $Credential)
    {
        if ((string) $Identity === (string) $Credential) {
            return false;
            throw new \Exception("GLI-007 : the cookie name for login and password must be different.");
        }
        if (empty($Identity)) {
            throw new \Exception("GLI-008 : the cookie name for login can't be empty.");
        }
        if (empty($Credential)) {
            throw new \Exception("GLI-009 : the cookie name for password can't be empty.");
        }

        if ($_SERVER['REQUEST_METHOD'] == "POST") {

            $sql = "select * from " . self::$_tableName . " where " . self::$_login . " = '" . self::$_dbLink->sql_real_escape_string($Identity) . "'";
            $res = self::$_dbLink->sql_query($sql);

            if (self::$_dbLink->sql_num_rows($res) === 1) {
                $ob = self::$_dbLink->sql_fetch_object($res);

                if (sha1(sha1($Credential . sha1($Identity))) === $ob->{self::$_passwd}) {

                    setcookie(self::$_name_cookie_login, $ob->{self::$_login}, time() + 3600 * 24 * 365, '/', $_SERVER['SERVER_NAME'], false, true);
                    setcookie(self::$_name_cookie_passwd, $ob->{self::$_passwd}, time() + 3600 * 24 * 365, '/', $_SERVER['SERVER_NAME'], false, true);

                    $this->_user = $ob;

                    return true;
                }
            }
        } else {
            if (!empty($_COOKIE[self::$_name_cookie_login]) && !empty($_COOKIE[self::$_name_cookie_passwd])) {
                $sql = "select * from " . self::$_tableName . " where " . self::$_login . " = '" . self::$_dbLink->sql_real_escape_string($_COOKIE[self::$_name_cookie_login]) . "'";
                $res = self::$_dbLink->sql_query($sql);

                if (self::$_dbLink->sql_num_rows($res) === 1) {
                    $ob = self::$_dbLink->sql_fetch_object($res);

                    if ($_COOKIE[self::$_name_cookie_passwd] === $ob->{self::$_passwd}) {

                        $this->_user = $ob;

                        //have to be deported in &
                        $sql = "UPDATE user_main SET date_last_connected = now() where `" . self::$_login . "`='" . self::$_dbLink->sql_real_escape_string($_COOKIE[self::$_name_cookie_login]) . "'";
                        self::$_dbLink->sql_query($sql);

                        if ($ob->is_valid == 0) {

                            if ($_SERVER['REQUEST_METHOD'] === "GET") {
                                $msg = I18n::getTranslation(__("Hello,") . "<br />" . __("Thank you for registering.") . "<br />"
                                                . __("To finalise your registration, please check your email and click on the confirmation. Once you've done this, your registration will be complete."));

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

}
