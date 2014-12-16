<?php

namespace Glial\Cli;

define("CRONTAB_FILE", "/tmp/crontab_php");

class Crontab
{

    static $debut = '#Les lignes suivantes sont gerees automatiquement via un script PHP. - Merci de ne pas editer manuellement';
    static $fin = '#Les lignes suivantes ne sont plus gerees automatiquement';

    static public function insert($chpMinute, $chpHeure, $chpJourMois, $chpMois, $chpJourSemaine, $chpCommande, $chpCommentaire, $new_id = 0)
    {
        $new_elem = $chpMinute . ' ' . $chpHeure . ' ' . $chpJourMois . ' ' . $chpMois . ' ' . $chpJourSemaine;
        
        /* TODO : Add check
        if (!self::assertLineIsValid($new_elem))
        {
            throw new \Exception("GLI-125 : Crontab malformed : '".$new_elem."'");
        }*/
        
        $new_elem .= ' ' . $chpCommande;
        
        

        $maxNb = 0;     /* le plus grand numéro de script trouvé */
        $crontab_list = Array();    /* pour chaque cellule une ligne du crontab actuel */
        $newCrontab = Array();    /* pour chaque cellule une ligne du nouveau crontab */
        $isSection = false;

        //$crontab_list = $this->view();
        exec('crontab -l', $crontab_list);

        foreach ($crontab_list as $index => $ligne) /* copie $crontab_list dans $newCrontab et ajoute le nouveau script */ {
            if ($isSection == true) /* on est dans la section gérée automatiquement */ {
                $motsLigne = explode(' ', $ligne);

                if ($motsLigne[0] == '#' && $motsLigne[1] > $maxNb) /* si on trouve un numéro plus grand */ {
                    $maxNb = $motsLigne[1];
                }
            }

            if ($ligne == self::$debut) {
                $isSection = true;
            }

            if ($ligne == self::$fin) /* on est arrivé à la fin, on rajoute le nouveau script */ {


                if (empty($new_id)) {
                    $id = $maxNb + 1;
                } else {
                    $id = $new_id;
                }

                $newCrontab[] = '# ' . $id . ' : ' . $chpCommentaire;
                $newCrontab[] = $new_elem;
            }

            $newCrontab[] = $ligne;   /* copie $crontab_list, ligne après ligne */
        }

        if ($isSection == false) /* s'il n'y a pas de section gérée par le script */ { /*  on l'ajoute maintenant */

            if (empty($new_id)) {
                $id = 1;
            } else {
                $id = $new_id;
            }


            $newCrontab[] = self::$debut;
            $newCrontab[] = '# 1 : ' . $chpCommentaire;
            $newCrontab[] = $chpMinute . ' ' . $chpHeure . ' ' . $chpJourMois . ' ' . $chpMois . ' ' . $chpJourSemaine . ' ' . $chpCommande;
            $newCrontab[] = self::$fin;
        }

        $f = fopen(CRONTAB_FILE, 'w');   /* on crée le fichier temporaire */

        foreach ($newCrontab as $line) {
            fwrite($f, $line . "\n");
        }
        fclose($f);

        $output = shell_exec('crontab ' . CRONTAB_FILE . ' 2>&1');    /* on le soumet comme crontab */

        if (!empty($output)) {
            throw new \Exception("GLI-075 : Impossible to install new crontab : '" . $new_elem . "'\n" . $output . "");
        }
        
        unlink(CRONTAB_FILE);

        return $id;
    }

    static public function delete($id)
    {
        $crontab_list = Array();    /* pour chaque cellule une ligne du crontab actuel */
        $newCrontab = Array();    /* pour chaque cellule une ligne du nouveau crontab */
        $isSection = false;
        $delete_next = false;

        exec('crontab -l', $crontab_list);  /* on récupère l'ancienne crontab dans $crontab_list */

        foreach ($crontab_list as $ligne) /* copie $crontab_list dans $newCrontab sans le script à effacer */ {
            if ($delete_next) {
                $delete_next = false;
                continue;
            }

            if ($isSection == true) /* on est dans la section gérée automatiquement */ {
                $motsLigne = explode(' ', $ligne);

                if ($motsLigne[0] != '#' || $motsLigne[1] != $id) /* ce n est pas le script à effacer */ {
                    $newCrontab[] = $ligne;   /* copie $crontab_list, ligne après ligne */
                } else {
                    $delete_next = true;
                }
            } else {
                $newCrontab[] = $ligne;  /* copie $crontab_list, ligne après ligne */
            }

            if ($ligne == self::$debut) {
                $isSection = true;
            }

            if ($ligne == self::$fin) {
                $isSection = false;
            }
        }

        $f = fopen(CRONTAB_FILE, 'w');   /* on crée le fichier temporaire */

        foreach ($newCrontab as $line) {
            fwrite($f, $line . "\n");
        }
        fclose($f);
        exec('crontab ' . CRONTAB_FILE);   /* on le soumet comme crontab */

        return $id;
    }

    
    /*
    static public function assertFileIsValidUserCrontab($file = CRONTAB_FILE)
    {
        $f = @fopen($file, 'r', 1);
        $this->assertTrue($f !== false, 'Crontab file must exist');
        while (($line = fgets($f)) !== false) {
            $this->assertLineIsValid($line);
        }
    }*/

    static public function assertLineIsValid($line)
    {
        $regexp = self::buildRegexp();
        if (preg_match("/$regexp/", $line))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    static public function buildRegexp()
    {
        $numbers = array(
            'min' => '[0-5]?\d',
            'hour' => '[01]?\d|2[0-3]',
            'day' => '0?[1-9]|[12]\d|3[01]',
            'month' => '[1-9]|1[012]',
            'dow' => '[0-7]'
        );

        foreach ($numbers as $field => $number) {
            $range = "($number)(-($number)(\/\d+)?)?";
            $field_re[$field] = "\*(\/\d+)?|$range(,$range)*";
        }

        $field_re['month'].='|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec';
        $field_re['dow'].='|mon|tue|wed|thu|fri|sat|sun';
        $fields_re = '(' . join(')\s+(', $field_re) . ')';
        $replacements = '@reboot|@yearly|@annually|@monthly|@weekly|@daily|@midnight|@hourly';

        return '^\s*(' .
                '$' .
                '|#' .
                '|\w+\s*=' .
                "|$fields_re\s+\S" .
                "|($replacements)\s+\S" .
                ')';
    }
}
