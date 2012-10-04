<?php

class species_tree {

	static function add_species($kingdom, $phylum, $class, $order, $family, $genus, $species) {

		$_SQL = Singleton::getInstance(SQL_DRIVER);


		$kingdom = ucwords(strtolower($kingdom));
		$phylum = ucwords(strtolower($phylum));
		$class = ucwords(strtolower($class));
		$order = ucwords(strtolower($order));
		$family = ucwords(strtolower($family));
		$genus = ucwords(strtolower($genus));


		$sql = "SELECT * FROM species_kingdom WHERE scientific_name= '" . $kingdom . "'";
		$res = $_SQL->sql_query($sql);

		if ($_SQL->sql_num_rows($res) == 1)
		{
			$ob = $_SQL->sql_fetch_object($res);
			$id_kingdom = $ob->id;
		}
		else
		{
			$data = array();
			$data['species_kingdom']['scientific_name'] = $kingdom;
			$data['species_kingdom']['id_history_etat'] = 1;
			//add history

			$_SQL->set_history_type(14);
			$_SQL->set_history_user(80);

			$id_kingdom = $_SQL->sql_save($data);

			if (!$id_kingdom)
			{
				debug($_SQL->sql_error());
				debug($data);
				die();
			}
		}


		$sql = "SELECT * FROM species_phylum WHERE scientific_name= '" . ucwords(strtolower($phylum)) . "' AND id_species_kingdom ='" . $id_kingdom . "'";
		$res = $_SQL->sql_query($sql);

		if ($_SQL->sql_num_rows($res) == 1)
		{
			$ob = $_SQL->sql_fetch_object($res);
			$id_phylum = $ob->id;
		}
		else
		{
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

			if (!$id_phylum)
			{
				debug($_SQL->sql_error());
				debug($data);
				die();
			}
		}



		$sql = "SELECT * FROM species_class WHERE scientific_name= '" . ucwords(strtolower($class)) . "' AND id_species_phylum ='" . $id_phylum . "'";
		$res = $_SQL->sql_query($sql);

		if ($_SQL->sql_num_rows($res) == 1)
		{
			$ob = $_SQL->sql_fetch_object($res);
			$id_class = $ob->id;
		}
		else
		{
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

			if (!$id_class)
			{
				/*				 * ***************** */

				$sql2 = "SELECT * FROM species_class WHERE scientific_name = '" . $_SQL->sql_real_escape_string($class) . "'";
				$res2 = $_SQL->sql_query($sql2);


				if ($_SQL->sql_num_rows($res2) == 1)
				{
					while ($ob = $_SQL->sql_fetch_object($res2)) {

						$data = array();
						$data['species_class']['id'] = $ob->id;
						$data['species_class']['id_species_phylum'] = $id_phylum;
						$data['species_class']['date_updated'] = date('c');

						$_SQL->set_history_type(16);
						$_SQL->set_history_user(80);
						$id_class = $_SQL->sql_save($data);

						if (!$id_class)
						{
							debug($id_class);
							debug($_SQL->sql_error());
							debug($data);
							die();
						}

						$id_class = $ob->id;
					}
				}
				else
				{
					debug($id_class);
					debug($_SQL->sql_error());
					debug($data);
					die();
				}
				/*				 * ***************** */




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

		if ($_SQL->sql_num_rows($res) == 1)
		{
			$ob = $_SQL->sql_fetch_object($res);
			$id_order = $ob->id;
		}
		else
		{
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


			if (!$id_order)
			{
				debug($_SQL->sql_error());
				debug($data);
				die();
			}
		}


		$sql = "SELECT * FROM species_family WHERE scientific_name= '" . ucwords(strtolower($family)) . "' AND id_species_order ='" . $id_order . "'";
		$res = $_SQL->sql_query($sql);

		if ($_SQL->sql_num_rows($res) == 1)
		{
			$ob = $_SQL->sql_fetch_object($res);
			$id_family = $ob->id;
		}
		else
		{
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

			if (!$id_family)
			{

				$sql2 = "SELECT * FROM species_family WHERE scientific_name = '" . $_SQL->sql_real_escape_string($family) . "'";
				$res2 = $_SQL->sql_query($sql2);


				if ($_SQL->sql_num_rows($res2) == 1)
				{
					while ($ob = $_SQL->sql_fetch_object($res2)) {

						$data = array();
						$data['species_family']['id'] = $ob->id;
						$data['species_family']['id_species_order'] = $id_order;
						$data['species_family']['date_updated'] = date('c');

						$_SQL->set_history_type(16);
						$_SQL->set_history_user(80);
						$id_family = $_SQL->sql_save($data);

						if (!$id_family)
						{
							debug($id_family);
							debug($_SQL->sql_error());
							debug($data);
							die();
						}

						$id_family = $ob->id;
					}
				}
				else
				{
					debug($id_family);
					debug($_SQL->sql_error());
					debug($data);
					die();
				}
			}
		}

		$sql = "SELECT * FROM species_genus WHERE scientific_name= '" . ucwords(strtolower($genus)) . "' AND id_species_family ='" . $id_family . "'";
		$res = $_SQL->sql_query($sql);

		if ($_SQL->sql_num_rows($res) == 1)
		{
			$ob = $_SQL->sql_fetch_object($res);
			$id_genus = $ob->id;
		}
		else
		{
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

			if (!$id_genus)
			{


				$sql2 = "SELECT * FROM species_genus WHERE scientific_name = '" . $_SQL->sql_real_escape_string($genus) . "'";
				$res2 = $_SQL->sql_query($sql2);


				if ($_SQL->sql_num_rows($res2) == 1)
				{
					while ($ob = $_SQL->sql_fetch_object($res2)) {

						$data = array();
						$data['species_genus']['id'] = $ob->id;
						$data['species_genus']['id_species_family'] = $id_family;
						$data['species_genus']['date_updated'] = date('c');

						$_SQL->set_history_type(16);
						$_SQL->set_history_user(80);
						$id_genus = $_SQL->sql_save($data);

						if (!$id_genus)
						{
							debug($id_genus);
							debug($_SQL->sql_error());
							debug($data);
							die();
						}

						$id_genus = $ob->id;
					}
				}
				else
				{
					debug($id_genus);
					debug($_SQL->sql_error());
					debug($data);
					die();
				}
			}
		}

		$sql = "SELECT * FROM species_main WHERE scientific_name= '" . $species . "' AND id_species_genus ='" . $id_genus . "'";
		$res = $_SQL->sql_query($sql);

		if ($_SQL->sql_num_rows($res) == 1)
		{
			$ob = $_SQL->sql_fetch_object($res);
			$id_species = $ob->id;
		}
		else
		{
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

			if (!$id_species)
			{
				debug($_SQL->sql_error());
				debug($data);
				die();
			}
		}



		$tab_species = explode(" ", $species);
		$sub_species = $species . " " . $tab_species[1];

		$sql = "SELECT * FROM species_sub WHERE scientific_name= '" . $sub_species . "' AND id_species_main ='" . $id_species . "'";
		$res = $_SQL->sql_query($sql);

		if ($_SQL->sql_num_rows($res) == 1)
		{
			$ob = $_SQL->sql_fetch_object($res);
		}
		else
		{
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

			if (!$id_subspecies)
			{
				debug($_SQL->sql_error());
				debug($data);
				die();
			}
		}


		return $id_species;
	}

	static function get_id_species_distribution_information($libelle) {
		$_SQL = Singleton::getInstance(SQL_DRIVER);

		$libelle = ucwords(strtolower($libelle));

		$sql = "SELECT * FROM species_distribution_information WHERE libelle = '" . $libelle . "'";

		$res = $_SQL->sql_query($sql);

		if ($_SQL->sql_num_rows($res) == 1)
		{
			$ob = $_SQL->sql_fetch_object($res);
			$id = $ob->id;
		}
		else
		{
			$data = array();
			$data['species_distribution_information']['libelle'] = $libelle;

			$id = $_SQL->sql_save($data);

			if (!$id)
			{
				echo "ERROR\n";
				debug($_SQL->sql_error());
				debug($data);
				die();
			}
		}

		return $id;
	}

	static function add_country_to_species($id_species_main, $id_geolocalisation_country, $id_species_distribution_information) {
		$_SQL = Singleton::getInstance(SQL_DRIVER);

		$data = array();
		$data['link__geolocalisation_country__species_main']['id_species_main'] = $id_species_main;
		$data['link__geolocalisation_country__species_main']['id_geolocalisation_country'] = $id_geolocalisation_country;
		$data['link__geolocalisation_country__species_main']['id_species_distribution_information'] = $id_species_distribution_information;

		$id = $_SQL->sql_save($data);

		if (!$id)
		{
			echo "ERROR\n";
			debug($_SQL->sql_error());
			debug($data);
			die();
		}
		
		return $id;
	}

}