<?php

namespace Glial\Neuron\Controller;

use \Glial\Synapse\Controller;


//curl -sS https://raw.github.com/Esysteme/Init-Glial/master/install.php | php -- --install-dir="/home/www/test" --application="Esysteme/Estrildidae"



class NeuronAdministration extends Controller
{
    function __construct()
    {
        
        
        
    }
    
	function admin_index_unique()
	{
        $this->layout_name = false;
        $this->view = false;

        $listTable = $this->db['default']->getListTable();
        
        
        $list_index = array();
        foreach($listTable['table'] as $table_name)
        {
             $list_index[$table_name] = $this->db['default']->getIndexUnique($table_name);
        }

        $json = json_encode($list_index);
        
        
        if (is_writable ( TMP."keys/" ))
        {
            file_put_contents(TMP."keys/default_index_unique.txt", $json);
        }
        else
        {
            trigger_error(__("This directory should be writable : "). TMP."keys/");
        }
        
        
        
        
	}
    
    function all()
    {
        $this->admin_index_unique();
        $this->admin_table();
        $this->admin_init();
        
    }
    
    
    function install()
    {
        $this->all();
    }
	
	
}