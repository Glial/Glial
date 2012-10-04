<?php

/**
 * Glial Framework
 *
 * LICENSE
 *
 * 
 */
//namespace gliale\flickr;

class iucn {

	static function get_all_species($i) {

		//version 1 => Flickr get tout les йlйments
		$data = array();

		//for ($i = 1; $i < 1278; $i++) // 1277 pages is a max of IUCN
		//{
		$url = "http://www.iucnredlist.org/search?page=" . $i;

		//echo $url ."\n";

		$ch = curl_init();


		$user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.79 Safari/537.1'; // simule Firefox 4.
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Keep-Alive: 300";
		$header[] = "Accept-Charset: utf-8";
		$header[] = "Accept-Language: en"; // langue fr. 
		$header[] = "Pragma: "; // Simule un navigateur
		//curl_setopt($ch, CURLOPT_PROXY, 'proxy.int.world.socgen:8080');
		//curl_setopt($ch, CURLOPT_PROXYUSERPWD, "aurelien.lequoy:xxxxx");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
		$content = curl_exec($ch);
		curl_close($ch);

		$content = wlHtmlDom::getTagContent($content, '<ul id="results"', true);
		if (false === $content)
		{
			break;
		}

		$contents = wlHtmlDom::getTagContents($content, '<li>', true);

		foreach ($contents as $var)
		{

			$link = wlHtmlDom::getTagContent($var, '<a href="/details/', false);

			$tab = explode('href="', $link);
			$tab = explode('"', $tab[1]);
			$tab[0];

			$ret = array();
			$ret['url'] = "http://www.iucnredlist.org" . $tab[0];
			$ret['scientific_name'] = strip_tags($link);
			$ret['date'] = date("c");

			$tab = explode('/', $tab[0]);
			$ret['reference_id'] = $tab[2];

			$nb = explode(" ", $ret['scientific_name']);
			if (count($nb) != 2)
			{
				continue;
			}

			$data[] = $ret;
		}




		echo "[" . date("Y-m-d H:i:s") . "] [page : " . $i . "] (result : " . count($data) . ")\n";
		//}



		return $data;

		//grab and draw the contents
	}

	static function get_species_summary($id) {
		$data = array();

		$url = "http://www.iucnredlist.org/details/" . $id . "/0";
		$ch = curl_init();

		$user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.79 Safari/537.1'; // simule Firefox 4.
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Keep-Alive: 300";
		$header[] = "Accept-Charset: utf-8";
		$header[] = "Accept-Language: en"; // langue fr. 
		$header[] = "Pragma: "; // Simule un navigateur
		//curl_setopt($ch, CURLOPT_PROXY, 'proxy.int.world.socgen:8080');
		//curl_setopt($ch, CURLOPT_PROXYUSERPWD, "aurelien.lequoy:xxxxx");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
		$content = curl_exec($ch);
		curl_close($ch);

		$content = wlHtmlDom::getTagContent($content, '<div id="detailsPage"', true);
		$table = wlHtmlDom::getTagContents($content, '<table class="tab_data" cellpadding="0" cellspacing="0">', true);



		$taxo = wlHtmlDom::getTagContents($table[0], '<td', true);
		$scientific_name = wlHtmlDom::getTagContent($content, '<span class="sciname"', true);

		$genus = explode(" ", $scientific_name);


		$data = array();
		$data['taxo']['Kingdom'] = trim($taxo[0]);
		$data['taxo']['Phylum'] = trim($taxo[1]);
		$data['taxo']['Class'] = trim($taxo[2]);
		$data['taxo']['Order'] = trim($taxo[3]);
		$data['taxo']['Family'] = trim($taxo[4]);
		$data['taxo']['Genus'] = trim($genus[0]);
		$data['taxo']['scientific_name'] = trim($scientific_name);

		$td = wlHtmlDom::getTagContents($table[1], '<td>', true);



		$lg = wlHtmlDom::getTagContent($table[1], '<table cellpadding="0" cellspacing="0" style="padding-left: 15px">', true);
		$lg = wlHtmlDom::getTagContents($lg, '<tr>', true);

		foreach ($lg as $tr_lang)
		{
			$td_lg = wlHtmlDom::getTagContents($tr_lang, '<td>', true);

			$data['lang'][trim($td_lg[0])] = trim($td_lg[2]);
		}


		$tr = wlHtmlDom::getTagContents($table[1], '<tr>', true);

		foreach ($tr as $line_td)
		{
			$title = wlHtmlDom::getTagContent($line_td, '<td class', true);
			$value = wlHtmlDom::getTagContent($line_td, '<td>', true);

			if (stristr($title, "Taxonomic Notes:"))
			{
				$data['taxo']['notes'] = $value;
			}
			if (stristr($title, "Authority"))
			{
				$data['taxo']['author'] = $value;
			}
		}





		//assement information

		$tr = wlHtmlDom::getTagContents($table[2], '<tr>', true);

		foreach ($tr as $line_td)
		{
			$title = wlHtmlDom::getTagContent($line_td, '<td class', true);
			$value = wlHtmlDom::getTagContent($line_td, '<td>', true);
			$value2 = wlHtmlDom::getTagContent($line_td, '<td colspan="2"', true);


			if (stristr($title, "Red List Category & Criteria"))
			{
				$statut = explode('<a', $value);
				$data['Information']['statut'] = trim($statut[0]);
				$data['Information']['version'] = trim(wlHtmlDom::getTagContent($value, '<a', true));
			}


			if (stristr($title, "Year Published"))
			{
				$data['Information']['Year Published'] = trim($value);
			}

			if (stristr($title, "Annotations"))
			{
				$data['Information']['Annotations'] = trim(strip_tags($value));
			}

			if (stristr($title, "Assessor"))
			{
				$data['Information']['Assessor'] = trim($value);
			}

			if (stristr($title, "Reviewer"))
			{
				$data['Information']['Reviewer'] = trim($value);
			}

			if (stristr($title, "Contributor"))
			{
				$data['Information']['Contributor'] = trim($value);
			}

			if (stristr($title, "Contributor"))
			{
				$data['Information']['Contributor'] = trim($value);
			}



			if (stristr($title, "History"))
			{
				$table_histo = wlHtmlDom::getTagContent($value, '<table', true);
				$tr_histo = wlHtmlDom::getTagContents($table_histo, '<tr', true);
				foreach ($tr_histo as $tr2)
				{
					$td_histo = wlHtmlDom::getTagContents($tr2, '<td>', true);
					$data['History'][$td_histo[0]] = str_replace("\n", "", trim($td_histo[2]));
				}
			}

			if (!empty($value2))
			{
				$Justification = explode('</strong><br>', $value2);
				$data['Information']['Justification'] = trim($Justification[1]);
			}
		}




		//geographique range


		$tr = wlHtmlDom::getTagContents($table[3], '<tr>', true);

		foreach ($tr as $line_td)
		{
			$title = wlHtmlDom::getTagContent($line_td, '<td class="label', true);
			$value = wlHtmlDom::getTagContent($line_td, '<td class="range', true);
			$value2 = wlHtmlDom::getTagContent($line_td, '<td>', true);

			if (stristr($title, "Description"))
			{
				$data['Geographic']['Range Description'] = $value2;
			}
			if (stristr($title, "FAO"))
			{


				$country = wlHtmlDom::getTagContents($value, '<div class="group">', true);

				foreach ($country as $pays)
				{
					$var = wlHtmlDom::getTagContent($pays, '<div', true);
					$list = explode("</div>", $pays);
					$nation = explode("; ", $list[1]);
					$var = str_replace(":", "", $var);

					$nation = array_map("strip_tags", $nation);
					$nation = array_map("trim", $nation);

					$data['Geographic']['FAO Marine Fishing Areas'][$var] = $nation;
				}
			}
			if (stristr($title, "Countries"))
			{

				$country = wlHtmlDom::getTagContents($value, '<div class="group">', true);

				foreach ($country as $pays)
				{
					$var = wlHtmlDom::getTagContent($pays, '<div', true);
					$list = explode("</div>", $pays);
					$nation = explode("; ", $list[1]);
					$var = str_replace(":", "", $var);

					$nation = array_map("strip_tags", $nation);
					$nation = array_map("trim", $nation);

					$data['Geographic']['Countries'][$var] = $nation;
				}
			}
		}


		//population (else Habitat and Ecology)


		$nb_table = count($table);
		for ($i = 4; $i < $nb_table; $i++)
		{
			$tr = wlHtmlDom::getTagContents($table[$i], '<tr>', true);

			foreach ($tr as $line_td)
			{
				$title = wlHtmlDom::getTagContent($line_td, '<td class', true);
				$value = wlHtmlDom::getTagContent($line_td, '<td>', true);
				$value2 = wlHtmlDom::getTagContent($line_td, '<td id=', true);

				if (stristr($title, "Population:"))
				{
					$data['Population']['Infos'] = trim($value);
				}

				if (stristr($title, "Trend"))
				{
					$data['Population']['Trend'] = trim(strip_tags($value2));
				}

				if (stristr($title, "Habitat and Ecology"))
				{
					$data['Habitat']['Habitat and Ecology'] = trim(strip_tags($value));
				}
				
				if (stristr($title, "Systems"))
				{
					$data['Habitat']['Systems'] = trim(strip_tags($value));
				}
				
				if (stristr($title, "Major Threat"))
				{
					$data['Threat']['Major Threat'] = trim(strip_tags($value));
				}
				
				
				if (stristr($title, "Conservation Actions"))
				{
					$data['Conservation']['Conservation Actions'] = trim(strip_tags($value));
				}
				
				
			}
		}




		return $data;
	}

	static function get_species_classification($id) {
		$data = array();

		$url = "http://www.iucnredlist.org/details/classify/" . $id . "/0";
		$ch = curl_init();

		$user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.79 Safari/537.1'; // simule Firefox 4.
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Keep-Alive: 300";
		$header[] = "Accept-Charset: utf-8";
		$header[] = "Accept-Language: en"; // langue fr. 
		$header[] = "Pragma: "; // Simule un navigateur
		//curl_setopt($ch, CURLOPT_PROXY, 'proxy.int.world.socgen:8080');
		//curl_setopt($ch, CURLOPT_PROXYUSERPWD, "aurelien.lequoy:xxxxx");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
		$content = curl_exec($ch);
		curl_close($ch);

		$content = wlHtmlDom::getTagContent($content, '<div id="detailsPage"', true);
		$table = wlHtmlDom::getTagContents($content, '<table class="tab_data" cellpadding="0" cellspacing="0">', true);

		$Habitats = wlHtmlDom::getTagContents($table[0], '<table cellpadding="0" cellspacing="0">', true);

		$tr_habitat = wlHtmlDom::getTagContents($Habitats[0], '<tr>', true);

		//print_r($Habitats);
		print_r($tr_habitat);


		foreach ($tr_habitat as $tr)
		{

			$td_habitat = wlHtmlDom::getTagContents($tr, '<td>', true);

			$hab = array();
			$hab['code'] = $td_habitat[0];
			$hab['libelle'] = $td_habitat[1];

			$data['Habitats'][] = $hab;
		}

		return $data;
	}

	static function get_species_bibliography($id) {
		$data = array();

		$url = "http://www.iucnredlist.org/details/biblio/" . $id . "/0";
		$ch = curl_init();

		$user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.79 Safari/537.1'; // simule Firefox 4.
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Keep-Alive: 300";
		$header[] = "Accept-Charset: utf-8";
		$header[] = "Accept-Language: en"; // langue fr. 
		$header[] = "Pragma: "; // Simule un navigateur
		//curl_setopt($ch, CURLOPT_PROXY, 'proxy.int.world.socgen:8080');
		//curl_setopt($ch, CURLOPT_PROXYUSERPWD, "aurelien.lequoy:xxxxx");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
		$content = curl_exec($ch);
		curl_close($ch);

		$content = wlHtmlDom::getTagContent($content, '<div id="detailsPage"', true);
		$table = wlHtmlDom::getTagContents($content, '<table class="tab_data" cellpadding="0" cellspacing="0">', true);

		$book = wlHtmlDom::getTagContents($table[0], '<p>', true);


		if (stristr($book[count($book) - 1], 'IUCN'))
		{
			unset($book[count($book) - 1]);
		}

		$data['book'] = $book;

		return $data;
	}

	//' ⇡'' ⇣'
}