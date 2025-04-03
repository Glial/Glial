<?php

namespace Glial\Sgbd\Mysql\Test;

use \Glial\Sgbd\Mysql\Mysql;





use PHPUnit\Framework\TestCase;


class TestMySQL extends TestCase
{

    $to_check = []


    public function testCheckVersion()
    {
        $to_check[1]['version'] = '10.11.11-MariaDB-log';
        $to_check[1]['version_comment'] = 'managed by https://aws.amazon.com/rds/';
        $to_check[1]['type_version'] = '10.11.11';
        $to_check[1]['type_comment'] = 'MariaDB';

        $to_check[2]['version'] = '10.11.11-MariaDB-deb12-log';
        $to_check[2]['version_comment'] = 'mariadb.org binary distribution';
        $to_check[2]['type_version'] = '10.11.11';
        $to_check[2]['type_comment'] = 'MariaDB';


        

        foreach ($to_check as $key => $data) {
            // À chaque itération, on affecte les valeurs 'version' et 'version_comment'
            $this->variables['version'] = $data['version'];
            $this->variables['version_comment'] = $data['version_comment'];

            // Vérification que getVersion retourne bien type_version du dernier élément
            $this->assertEquals(
                $to_check[$key]['type_version'],
                $versionManager->getVersion(),
                "La méthode getVersion() doit retourner la valeur type_version du dernier élément."
            );

            // Vérification que getServerType retourne bien type_comment du dernier élément
            $this->assertEquals(
                $to_check[$key]['type_comment'],
                $versionManager->getServerType(),
                "La méthode getServerType() doit retourner la valeur type_comment du dernier élément."
            );

        }
    
    }

}
