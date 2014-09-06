<?php

namespace Glial\Neuron\Menu;

trait Menu
{

    public function install()
    {
        $sqls = array();
        
       $sqls[] = "CREATE TABLE `menu_group` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `title` varchar(255) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
       
       $sqls[] = "CREATE TABLE `menu` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `parent_id` int(11) NOT NULL DEFAULT '0',
 `title` varchar(255) NOT NULL DEFAULT '',
 `url` varchar(255) NOT NULL DEFAULT '',
 `class` varchar(255) NOT NULL DEFAULT '',
 `position` int(11) unsigned NOT NULL DEFAULT '0',
 `group_id` int(11) unsigned NOT NULL DEFAULT '1',
 PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8";
       
       
    }

    public function unInstall()
    {
        $sqls = array();
        
        $sqls[] = "DROP TABLE `menu`";
        $sqls[] = "DROP TABLE `menu_group`";
    }
    
    
    public function admin_menu()
    {
        
    }

}
