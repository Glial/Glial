<?php

namespace glial\species;

use \glial\synapse\Singleton;

class Species
{

    public static $_TaxoTree ="";
    
    public static function addSpecies($kingdom, $phylum, $class, $order, $family, $genus, $species)
    {
        //$_SQL = Singleton::getInstance(SQL_DRIVER);

        $kingdom = ucwords(strtolower($kingdom));
        $phylum = ucwords(strtolower($phylum));
        $class = ucwords(strtolower($class));
        $order = ucwords(strtolower($order));
        $family = ucwords(strtolower($family));
        $genus = ucwords(strtolower($genus));

        $sql = "SELECT * FROM species_kingdom WHERE scientific_name= '" . $kingdom . "'";
        $res = $_SQL->sql_query($sql);

        if ($_SQL->sql_num_rows($res) == 1) {
            $ob = $_SQL->sql_fetch_object($res);
            $id_kingdom = $ob->id;
        } else {
            $data = array();
            $data['species_kingdom']['scientific_name'] = $kingdom;
            $data['species_kingdom']['id_history_etat'] = 1;
            //add history

            $_SQL->set_history_type(14);
            $_SQL->set_history_user(80);

            $id_kingdom = $_SQL->sql_save($data);

            if (!$id_kingdom) {
                debug($_SQL->sql_error());
                debug($data);
                die();
            }
        }

        $sql = "SELECT * FROM species_phylum WHERE scientific_name= '" . ucwords(strtolower($phylum)) . "' AND id_species_kingdom ='" . $id_kingdom . "'";
        $res = $_SQL->sql_query($sql);

        if ($_SQL->sql_num_rows($res) == 1) {
            $ob = $_SQL->sql_fetch_object($res);
            $id_phylum = $ob->id;
        } else {
            $data = array();
            $data['species_phylum']['id_species_kingdom'] = $id_kingdom;
            $data['species_phylum']['scientific_name'] = $phylum;
            $data['species_phylum']['id_history_etat'] = 1;
            $data['species_phylum']['date_created'] = date('c');
            $data['species_phylum']['date_updated'] = date('c');
            //add history

            $_SQL->set_history_type(14);
            $_SQL->set_history_user(80);
            $id_phylum = $_SQL->sql_save($data);

            if (!$id_phylum) {
                debug($_SQL->sql_error());
                debug($data);
                die();
            }
        }

        $sql = "SELECT * FROM species_class WHERE scientific_name= '" . ucwords(strtolower($class)) . "' AND id_species_phylum ='" . $id_phylum . "'";
        $res = $_SQL->sql_query($sql);

        if ($_SQL->sql_num_rows($res) == 1) {
            $ob = $_SQL->sql_fetch_object($res);
            $id_class = $ob->id;
        } else {
            $data = array();
            $data['species_class']['id_species_phylum'] = $id_phylum;
            $data['species_class']['scientific_name'] = $class;
            $data['species_class']['id_history_etat'] = 1;
            $data['species_class']['date_created'] = date('c');
            $data['species_class']['date_updated'] = date('c');
            //add history

            $_SQL->set_history_type(14);
            $_SQL->set_history_user(80);

            $id_class = $_SQL->sql_save($data);

            if (!$id_class) {
                /*                 * ***************** */

                $sql2 = "SELECT * FROM species_class WHERE scientific_name = '" . $_SQL->sql_real_escape_string($class) . "'";
                $res2 = $_SQL->sql_query($sql2);

                if ($_SQL->sql_num_rows($res2) == 1) {
                    while ($ob = $_SQL->sql_fetch_object($res2)) {

                        $data = array();
                        $data['species_class']['id'] = $ob->id;
                        $data['species_class']['id_species_phylum'] = $id_phylum;
                        $data['species_class']['date_updated'] = date('c');

                        $_SQL->set_history_type(16);
                        $_SQL->set_history_user(80);
                        $id_class = $_SQL->sql_save($data);

                        if (!$id_class) {
                            debug($id_class);
                            debug($_SQL->sql_error());
                            debug($data);
                            die();
                        }

                        $id_class = $ob->id;
                    }
                } else {
                    debug($id_class);
                    debug($_SQL->sql_error());
                    debug($data);
                    die();
                }
                /*                 * ***************** */

                /*
                  echo 'phylum : '.$phylum."\n";
                  debug($_SQL->sql_error());
                  debug($data);
                  die();
                 *
                 */
            }
        }

        $sql = "SELECT * FROM species_order WHERE scientific_name= '" . ucwords(strtolower($order)) . "' AND id_species_class ='" . $id_class . "'";
        $res = $_SQL->sql_query($sql);

        if ($_SQL->sql_num_rows($res) == 1) {
            $ob = $_SQL->sql_fetch_object($res);
            $id_order = $ob->id;
        } else {
            $data = array();
            $data['species_order']['id_species_class'] = $id_class;
            $data['species_order']['scientific_name'] = $order;
            $data['species_order']['id_history_etat'] = 1;
            $data['species_order']['date_created'] = date('c');
            $data['species_order']['date_updated'] = date('c');
            //add history

            $_SQL->set_history_type(14);
            $_SQL->set_history_user(80);
            $id_order = $_SQL->sql_save($data);

            if (!$id_order) {
                debug($_SQL->sql_error());
                debug($data);
                die();
            }
        }

        $sql = "SELECT * FROM species_family WHERE scientific_name= '" . ucwords(strtolower($family)) . "' AND id_species_order ='" . $id_order . "'";
        $res = $_SQL->sql_query($sql);

        if ($_SQL->sql_num_rows($res) == 1) {
            $ob = $_SQL->sql_fetch_object($res);
            $id_family = $ob->id;
        } else {
            $data = array();
            $data['species_family']['id_species_order'] = $id_order;
            $data['species_family']['date_created'] = date('c');
            $data['species_family']['date_updated'] = date('c');
            $data['species_family']['scientific_name'] = $family;
            $data['species_family']['id_history_etat'] = 1;
            //add history

            $_SQL->set_history_type(14);
            $_SQL->set_history_user(80);
            $id_family = $_SQL->sql_save($data);

            if (!$id_family) {

                $sql2 = "SELECT * FROM species_family WHERE scientific_name = '" . $_SQL->sql_real_escape_string($family) . "'";
                $res2 = $_SQL->sql_query($sql2);

                if ($_SQL->sql_num_rows($res2) == 1) {
                    while ($ob = $_SQL->sql_fetch_object($res2)) {

                        $data = array();
                        $data['species_family']['id'] = $ob->id;
                        $data['species_family']['id_species_order'] = $id_order;
                        $data['species_family']['date_updated'] = date('c');

                        $_SQL->set_history_type(16);
                        $_SQL->set_history_user(80);
                        $id_family = $_SQL->sql_save($data);

                        if (!$id_family) {
                            debug($id_family);
                            debug($_SQL->sql_error());
                            debug($data);
                            die();
                        }

                        $id_family = $ob->id;
                    }
                } else {
                    debug($id_family);
                    debug($_SQL->sql_error());
                    debug($data);
                    die();
                }
            }
        }

        $sql = "SELECT * FROM species_genus WHERE scientific_name= '" . ucwords(strtolower($genus)) . "' AND id_species_family ='" . $id_family . "'";
        $res = $_SQL->sql_query($sql);

        if ($_SQL->sql_num_rows($res) == 1) {
            $ob = $_SQL->sql_fetch_object($res);
            $id_genus = $ob->id;
        } else {
            $data = array();
            $data['species_genus']['id_species_family'] = $id_family;
            $data['species_genus']['date_created'] = date('c');
            $data['species_genus']['date_updated'] = date('c');
            $data['species_genus']['scientific_name'] = $genus;
            $data['species_genus']['id_history_etat'] = 1;
            //add history

            $_SQL->set_history_type(14);
            $_SQL->set_history_user(80);
            $id_genus = $_SQL->sql_save($data);

            if (!$id_genus) {

                $sql2 = "SELECT * FROM species_genus WHERE scientific_name = '" . $_SQL->sql_real_escape_string($genus) . "'";
                $res2 = $_SQL->sql_query($sql2);

                if ($_SQL->sql_num_rows($res2) == 1) {
                    while ($ob = $_SQL->sql_fetch_object($res2)) {

                        $data = array();
                        $data['species_genus']['id'] = $ob->id;
                        $data['species_genus']['id_species_family'] = $id_family;
                        $data['species_genus']['date_updated'] = date('c');

                        $_SQL->set_history_type(16);
                        $_SQL->set_history_user(80);
                        $id_genus = $_SQL->sql_save($data);

                        if (!$id_genus) {
                            debug($id_genus);
                            debug($_SQL->sql_error());
                            debug($data);
                            die();
                        }

                        $id_genus = $ob->id;
                    }
                } else {
                    debug($id_genus);
                    debug($_SQL->sql_error());
                    debug($data);
                    die();
                }
            }
        }

        $sql = "SELECT * FROM species_main WHERE scientific_name= '" . $species . "' AND id_species_genus ='" . $id_genus . "'";
        $res = $_SQL->sql_query($sql);

        if ($_SQL->sql_num_rows($res) == 1) {
            $ob = $_SQL->sql_fetch_object($res);
            $id_species = $ob->id;
        } else {
            $data = array();
            $data['species_main']['id_species_genus'] = $id_genus;
            $data['species_main']['date_created'] = date('c');
            $data['species_main']['date_updated'] = date('c');
            $data['species_main']['scientific_name'] = $species;
            $data['species_main']['id_history_etat'] = 1;
            //add history
            $_SQL->set_history_type(15);
            $_SQL->set_history_user(80);

            $id_species = $_SQL->sql_save($data);

            if (!$id_species) {
                debug($_SQL->sql_error());
                debug($data);
                die();
            }
        }

        $tab_species = explode(" ", $species);
        $sub_species = $species . " " . $tab_species[1];

        $sql = "SELECT * FROM species_sub WHERE scientific_name= '" . $sub_species . "' AND id_species_main ='" . $id_species . "'";
        $res = $_SQL->sql_query($sql);

        if ($_SQL->sql_num_rows($res) == 1) {
            $ob = $_SQL->sql_fetch_object($res);
        } else {
            $data = array();
            $data['species_sub']['id_species_main'] = $id_species;
            $data['species_sub']['date_created'] = date('c');
            $data['species_sub']['date_updated'] = date('c');
            $data['species_sub']['scientific_name'] = $sub_species;
            $data['species_sub']['id_history_etat'] = 1;
            //add history
            $_SQL->set_history_type(15);
            $_SQL->set_history_user(80);

            $id_subspecies = $_SQL->sql_save($data);

            if (!$id_subspecies) {
                debug($_SQL->sql_error());
                debug($data);
                die();
            }
        }

        return $id_species;
    }

    public static function get_id_species_distribution_information($libelle)
    {
        $_SQL = Singleton::getInstance(SQL_DRIVER);

        $libelle = ucwords(strtolower($libelle));

        $sql = "SELECT * FROM species_distribution_information WHERE libelle = '" . $libelle . "'";

        $res = $_SQL->sql_query($sql);

        if ($_SQL->sql_num_rows($res) == 1) {
            $ob = $_SQL->sql_fetch_object($res);
            $id = $ob->id;
        } else {
            $data = array();
            $data['species_distribution_information']['libelle'] = $libelle;

            $id = $_SQL->sql_save($data);

            if (!$id) {
                echo "ERROR\n";
                debug($_SQL->sql_error());
                debug($data);
                die();
            }
        }

        return $id;
    }

    public static function add_country_to_species($id_species_main, $id_geolocalisation_country, $id_species_distribution_information)
    {
        $_SQL = Singleton::getInstance(SQL_DRIVER);

        $data = array();
        $data['link__geolocalisation_country__species_main']['id_species_main'] = $id_species_main;
        $data['link__geolocalisation_country__species_main']['id_geolocalisation_country'] = $id_geolocalisation_country;
        $data['link__geolocalisation_country__species_main']['id_species_distribution_information'] = $id_species_distribution_information;

        $id = $_SQL->sql_save($data);

        if (!$id) {
            echo "ERROR\n";
            debug($_SQL->sql_error());
            debug($data);
            die();
        }

        return $id;
    }

    public static function html_pic($url, $img, $name, $alt)
    {
        echo "<span class=\"shadowImage\">";
        echo '<div class="photo_link passive">';
        echo "<a href=\"" . $url . "\">";

        echo '<div class="bigleaderpix">
<div class="caption">
<p>' . $name . '</p>
</div>
<div class="bigleaderlien"></div>
<img width="158" height="158" alt="' . $alt . '" title="' . $alt . '" src="' . $img . '">
</div>';

        echo '</a></div></span>';
    }

    public static function get($id, $source)
    {

        $sql = "select c.id_species_author, x.surname, b.id as id_link,b.id,c.miniature ,
			(select count(1) as cpt from link__species_picture_id__species_picture_search f where  c.id = f.id_species_picture_id) as gg,
		
			(select count(1) as cpt from link__species_picture_id__species_picture_search i
			inner join species_picture_search j ON j.id = i.id_species_picture_search
			where  c.id = i.id_species_picture_id AND j.id_species_main = a.id_species_main) as gg2,
			GROUP_CONCAT(DISTINCT a.tag_search ORDER BY a.tag_search DESC SEPARATOR '\n') as tag_search,
			
			(SELECT GROUP_CONCAT(DISTINCT t.scientific_name ORDER BY t.scientific_name DESC SEPARATOR '\n')
			FROM link__species_picture_id__species_picture_search r
			INNER JOIN species_picture_id s ON s.id = r.id_species_picture_id
			INNER JOIN species_picture_search u ON u.id = r.id_species_picture_search
			INNER JOIN species_main t ON t.id = u.id_species_main
			WHERE b.id_species_picture_id = s.id) as species
			
			
    from species_picture_search a
    inner join link__species_picture_id__species_picture_search b ON a.id = b.id_species_picture_search
    inner join  species_picture_id c ON c.id = b.id_species_picture_id
	inner join species_main z ON z.id = a.id_species_main
	inner join species_author x ON x.id = c.id_species_author
    inner JOIN species_source_main n ON n.id = a.id_species_source_main

	
    where z.scientific_name = '" . str_replace('_', ' ', $id) . "'
        AND n.name ='" . $source . "'
	group by x.id, b.id_species_picture_id
    order by  c.id_species_author, c.photo_id limit 50";


        //echo $sql;
        $_SQL = Singleton::getInstance(SQL_DRIVER);
        $res = $_SQL->sql_query($sql);

        $data = $_SQL->sql_to_array($res);

        return $data;
    }

    public static function miniature($data)
    {
        $data['class'] = (empty($data['class'])) ? "" : $data['class'];


        echo '<li class="' . $data['class'] . '">';
        echo '<a href="' . $data['url'] . '" data-target="' . $data['data-target'] . '" data-link="' . $data['data-link'] . '">';

        echo '<div class="bigleaderpix">';


        if (!empty($data['display-name'])) {
            echo '<div class="caption"><p>' . $data['display-name'] . '</p></div>';
        }
        echo '<div class="bigleaderlien"></div>
        <img width="158" height="158" alt="' . $data['name'] . '" title="' . $data['name'] . '" src="' . $data['photo'] . '">
        </div>';
        echo '</a></li>';
    }
    
    public static function getTaxoTree()
    {
        
        if (empty(self::$_TaxoTree))
        {
             switch ($_SERVER['SERVER_NAME'])
             {
                 case "www.gdol.eu":
                     
                     break;
                 case "www.estrildidae.net":
                     
                     break;
                 
                 
                 default:
                     break;
                 
                 
             }
        }
        else
        {
            return self::$_TaxoTree;
        }
       
    }

}
