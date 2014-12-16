<?php

namespace Glial\Cli\Test;

use \Glial\Cli\Ssh;

class TestSsh extends \PHPUnit_Framework_TestCase
{

    

    public function testRegexPrompt()
    {
        $regex = Ssh::getRegexPrompt();

        $test_ok = array('root@dba-tools-sa-01:~#', 'logftp@srv-backup-01:/data/Save/DB_ITPROD$');
        $test_fail = array("root@dba-tools-sa-01:~#", "logftp@srv-backup-01:/data/Save/DB_ITPROD$");

        foreach ($test_ok as $test) {

            $output_array = [];
            preg_match_all($regex, $test, $output_array);

            $tmp[0][0] = $test;
            
            $this->assertEquals(json_encode($tmp), json_encode($output_array));
        }
    }

}
