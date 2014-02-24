<?php

namespace \Glial\Péarser\XenoCanto;

use Glial\Extract\Grabber;

class XenoCanto
{

    public static function getAllLink()
    {
        $url = "http://www.xeno-canto.org/all_species.php";

        $file = file_get_contents("/var/www/xc/all_species.php.htm");

        echo "start OK !\n";
        $contents = Grabber::getTagContent($file, '<table class="results', true);

        echo "contents OK !\n";

        $line = Grabber::getTagContents($contents, '<tr>', true);

        echo "line OK !\n";

        if ($line) {
            $data = array();
            $i = 0;

            foreach ($line as $row) {
                $i++;
                echo "line : " . $i . "\n";

                if ($i < 3) {
                    continue;
                }

                $cell = Grabber::getTagContents($row, '<td', true);

                $tab = explode('href="', $cell[0]);
                $tab = explode('"', $tab[1]);

                $species = array();
                $species['url'] = $tab[0];
                $species['scientific_name'] = $cell[1];
                $species['name_en'] = Grabber::getTagContent($cell[0], '<a', true);
                $species['foreground'] = $cell[2];
                $species['background'] = $cell[3];

                $data[] = $species;
            }
        } else {
            return false;
        }

        return $data;
    }

    public static function getKmz()
    {

        $sql = "SELECT * FROM species_tree_nominal where class='Aves'";

        $_SQL = Singleton::getInstance(SQL_DRIVER);

        $res = $_SQL->sql_query($sql);

        while ( $ob = $_SQL->sql_fetch_object($res) ) {
            shell_exec("cd /home/www/species/tmp/kmz/; wget http://www.xeno-canto.org/ranges/" . str_replace(" ", "_", $ob->nominal) . ".kmz");
        }
    }

}
