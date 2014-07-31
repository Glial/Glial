<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Glial\Neuron\Controller;

trait Alstom
{
    public function autorisation($CDUSER, $CDORG)
    {
        $db = $this->di['db']->sql('default');
        $sql = "SELECT * FROM TABLE(FF_GESTION_AUTORISATION_V2.RECUPERATION_DONNEES('T1', '".$CDUSER."','".$CDORG."','','',''))";
        $res = $db->sql_query($sql);
   
        while ($ob = $db->sql_fetch_array($res))
        {
            if ($ob['AUTORISE'] === "1")
            {
                return true;
            }
        }
        return false;
    }
}
