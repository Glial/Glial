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

    static public $_user;
    static public $_idGroup;


    static private $_name_cookie_login;
    static private $_name_cookie_passwd;


    static private  $_fctToHashCookie;

    public function __construct()
    {
        
        
    }
    
    
    public static function setInstance($DbLink,$TableName,$Param )
    {

        if (get_parent_class($DbLink) != "Glial\Sgbd\Sql\Sql" )
        {
            throw new \DomainException('GLI-001 : DbLink should be an object create by a children of the class Glial\Sgbd\Sql\Sql');
        }
        
        if (!in_array($TableName,$DbLink->getListTable()['table']))
        {
            throw new \DomainException('GLI-002 : TableName "'.$TableName.'" seem doesnt exist in the database');
        }

        if (!file_exists(TMP . "/database/" . $TableName . ".table.txt")) {
            throw new \Exception('GLI-003 : the file cash of "'. $TableName .'" doent exist');
        }


        $fields = $DbLink->getInfosTable($TableName)['field'];

        if (!in_array($Param[0],$fields ))
        {
            throw new \Exception('GLI-004 : the field login referenced by "'.$TableName.'.'.$Param[0].'" doent exist');
        }


        if (!in_array($Param[1],$fields ))
        {
            throw new \Exception('GLI-005 : the field password referenced by "'.$TableName.'.'.$Param[1].'" doent exist');
        }

        if ((string)$Param[0] === (string)$Param[1])
        {
            throw new \Exception('GLI-006 : the field login and password must be different');
        }


        self::$_dbLink = $DbLink;
        self::$_tableName = $TableName;
        self::$_login = $Param[0];
        self::$_passwd = $Param[1];

    }

    public function setFctToHashCookie($function)
    {
        self::$_fctToHashCookie = $function;
    }


    public function authenticate($Identity, $Credential)
    {

        if ((string) $Identity === (string) $Credential)
        {
            throw new \Exception ("GLI-007 : the cookie name for login and password must be different.");
        }


        if (empty($Identity))
        {
            throw new \Exception ("GLI-008 : the cookie name for login can't be empty.");
        }
        if (empty($Credential))
        {
            throw new \Exception ("GLI-009 : the cookie name for password can't be empty.");
        }


        self::$_name_cookie_login = (!empty($_COOKIE[sha1($Identity)]))? $_COOKIE[sha1($Identity)]:'';
        self::$_name_cookie_passwd = (!empty($_COOKIE[sha1($Credential)])) ? $_COOKIE[sha1($Credential)] : '';

        if (!empty(self::$_name_cookie_login) && !empty(self::$_name_cookie_passwd)) {
            $sql = "select * from ".self::$_tableName." where ".self::$_login." = '" . self::$_dbLink->sql_real_escape_string(self::$_name_cookie_login) . "'";

            $res = self::$_dbLink->sql_query($sql);

            if (self::$_dbLink->sql_num_rows($res) === 1) {
                $ob = self::$_dbLink->sql_fetch_object($res);

                if (self::$_fctToHashCookie($ob->{self::$_passwd}) === self::$_name_cookie_passwd) {
                    $GLOBALS['_SITE']['IdUser'] = $_COOKIE['IdUser'];
                    $GLOBALS['_SITE']['Name'] = $ob->name;
                    $GLOBALS['_SITE']['FirstName'] = $ob->firstname;
                    $GLOBALS['_SITE']['id_group'] = $ob->id_group;


                    //have to be deported in &
                    $sql = "UPDATE user_main SET date_last_connected = now() where id=" . self::$_dbLink->sql_real_escape_string($_SITE['IdUser']);
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

        return false;
    }

}
