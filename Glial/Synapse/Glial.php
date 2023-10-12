<?php

namespace Glial\Synapse;

class Glial
{

    static public function getOut()
    {

        self::AddStat();


        exit;
    }

    static public function AddStat()
    {


        if (!IS_CLI) {
	    $db   = Sgbd::sql(DB_DEFAULT);
	    $data = array();

            $data['statistics']['id_user_main'] = 2;
            $data['statistics']['date']         = date('Y-m-d H:i:s');
            $data['statistics']['http_status']  = http_response_code();
            $data['statistics']['http_method']  = $_SERVER['REQUEST_METHOD'];
            $data['statistics']['link']         = $_GET['glial_path'];
            $data['statistics']['ip']           = $_SERVER['REMOTE_ADDR'];
            $data['statistics']['variables']    = json_encode($_POST);
            $data['statistics']['user_agent']   = $_SERVER['HTTP_USER_AGENT'];

            $db->sql_save($data);
        }
    }
}
