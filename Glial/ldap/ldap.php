<?php

/**
 * Glial Framework
 *
 * LICENSE
 *
 * 
 */
//namespace gliale\flickr;

class ldap {
    /*
     * This function searchs in LDAP tree ($ad -LDAP link identifier)
     * entry specified by samaccountname and returns its DN or epmty
     * string on failure.
     */

    
    static function getDN($ad, $samaccountname, $basedn) {
        $attributes = array('dn');
        $result = ldap_search($ad, $basedn, "(samaccountname={$samaccountname})", $attributes);
        if ($result === FALSE) {
            return '';
        }
        $entries = ldap_get_entries($ad, $result);
        if ($entries['count'] > 0) {
            return $entries[0]['dn'];
        } else {
            return '';
        };
    }

    /*
     * This function retrieves and returns CN from given DN
     */

    static function getCN($dn) {
        preg_match('/[^,]*/', $dn, $matchs, PREG_OFFSET_CAPTURE, 3);
        return $matchs[0][0];
    }

    /*
     * This function checks group membership of the user, searching only
     * in specified group (not recursively).
     */

    static function checkGroup($ad, $userdn, $groupdn) {
        $attributes = array('members');
        $result = ldap_read($ad, $userdn, "(memberof={$groupdn})", $attributes);
        if ($result === FALSE) {
            return FALSE;
        };
        $entries = ldap_get_entries($ad, $result);
        return ($entries['count'] > 0);
    }

    /*
     * This function checks group membership of the user, searching
     * in specified group and groups which is its members (recursively).
     */

    function checkGroupEx($ad, $userdn, $groupdn) {
        $attributes = array('memberof');
        $result = ldap_read($ad, $userdn, '(objectclass=*)', $attributes);
        if ($result === FALSE) {
            return FALSE;
        };
        $entries = ldap_get_entries($ad, $result);
        if ($entries['count'] <= 0) {
            return FALSE;
        };
        if (empty($entries[0]['memberof'])) {
            return FALSE;
        } else {
            for ($i = 0; $i < $entries[0]['memberof']['count']; $i++) {
                if ($entries[0]['memberof'][$i] == $groupdn) {
                    return TRUE;
                } elseif (self::checkGroupEx($ad, $entries[0]['memberof'][$i], $groupdn)) {
                    return TRUE;
                };
            };
        };
        return FALSE;
    }

}