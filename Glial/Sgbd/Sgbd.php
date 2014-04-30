<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

// factory for any connection ? db / ssh / memecached etc...

namespace Glial\Sgbd;

use \Glial\Cli\Table;
use \Glial\Cli\Shell;
use \Glial\Sgbd\Sql\FactorySql;

class Sgbd
{

    private $db = array();
    private $config = array();

    //from  Glial\Synapse\Config
    function __construct($config)
    {

        $this->config = array_merge($this->config, $config);
    }

    public function sql($name)
    {

        if (array_key_exists($name, $this->config)) {
            if (empty($this->db[$name])) {

                $this->db[$name] = \Glial\Sgbd\Sql\FactorySql::connect($name, $this->config[$name]);
            }

            return $this->db[$name];
        } else {
            throw new \Exception("GLI-19 : This connection was not configured : '" . $name . "' !");
        }
    }

    public function getAll()
    {
        return array_keys($this->config);
    }

    public function __toString()
    {

        $tab = new Table(1);

        $tab->addHeader(array("Id", "Name", "Is connected ?", "Driver", "IP", "Port", "User", "Password"));

        
        $i = 1;
        foreach ($this->config as $name => $param) {
            $port = (empty($param['port'])) ? "3306" : $param['port'];
            $isconnected = (empty($this->db[$name])) ? "" : "■";

            $tab->addLine(array((string)$i, $name, $isconnected, $param['driver'], $param['hostname'], $port, $param['user'], str_repeat("*", strlen($param['password']))));
            $i++;
        }

        return $tab->display();
    }

    public function mainMenu()
    {
        $tab = new Table(0);

        $tab->addHeader(array("============== Main Menu =============="));
        $tab->addLine(array(""));

        $options = array("[L]ist connections", "[A]dd a connection", "[E]dit a connection", "[D]elete a connection", "[T]est all","E[x]it");

        $i = 1;
        foreach ($options as $option) {
            $tab->addLine(array(str_pad($i . ".", 3) . $option));
            $i++;
        }
        
        echo $tab->display();
        
        
        $filter = function ($val){
          
           return preg_match("/([1-5]|l|a|e|d|t|x)/i", $val);
        };
        
        Shell::prompt("Enter your choice [1~".count($options)."] (empty for exit) : ", $filter, true);
    }
    
    public function getParam($db)
    {
        if (!empty($this->config[$db]))
        {
            return $this->config[$db];
        }
        else
        {
            throw new \Exception("GLI-021 : Error this instances \"".$db."\" doesn't exit", 21);
        }
    }
}
