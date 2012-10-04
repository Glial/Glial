<?php

/**
 * Glial Framework
 *
 * LICENSE
 *
 * 
 */
//namespace gliale\flickr;

class flickr {
	/*
	  function looking_for($param) {

	  $q = str_replace(" ", "+", $param);

	  fopen("http://www.flickr.com/search/?q=" . urlencode($q) . "&f=hp", "r");

	  //looking for
	  //<div class="ResultsThumbs"
	  }

	  static function get_resultat() {
	  $url = "http://www.flickr.com/photos/maholyoak/5847734660/";

	  $ch = curl_init();
	  curl_setopt($ch, CURLOPT_PROXY, 'proxy.int.world.socgen:8080');
	  curl_setopt($ch, CURLOPT_PROXYUSERPWD, "aurelien.lequoy:Zeb33tln1$");

	  // configuration des options
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	  curl_setopt($ch, CURLOPT_URL, $url);
	  //curl_setopt($ch, CURLOPT_HEADER, 0);
	  // exйcution de la session
	  $gg = curl_exec($ch);

	  // fermeture des ressources
	  curl_close($ch);

	  if (!preg_match("/^http:\/\/www.flickr.com\/photos\/([a-zA-Z0-9@]*)\/([0-9]*)\/$/i", $url))
	  {
	  die("$url did not match with REGEX : /^http:\/\/www.flickr.com\/photos\/([a-zA-Z0-9]*)\/([0-9]*)\/$/i");
	  }

	  //fopen($url, "r");
	  //http://www.flickr.com/photos/maholyoak/5847734660/
	  }
	 */

	static function get_links_to_photos($query) {

		$q = str_replace(" ", "+", $query);

		$q = urlencode($q);

		//version 1 => Flickr get tout les йlйments
		$data = array();

		for ($i = 1; $i < 67; $i++) // 67 pages is a max of Flickr
		{
			$url = "http://www.flickr.com/search/?q=" . $q . "&s=rec&page=" . $i;

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

			$content = wlHtmlDom::getTagContent($content, '<div class="ResultsThumbs"', true);
			if (false === $content)
			{
				break;
			}

			$contents = wlHtmlDom::getTagContents($content, '<div class="ResultsThumbsChild"', true);

			foreach ($contents as $var)
			{
				$author = wlHtmlDom::getTagContent($var, '<a data-track="user"', false);

				$tab3 = explode('title="', $author);
				$tab3 = explode('"', $tab3[1]);

				$var = wlHtmlDom::getTagContent($var, '<a data-track="thumb" href="/photos/');


				$tab2 = explode('src="', $var);
				$tab2 = explode('"', $tab2[1]);




				$tab = explode('"', trim($var));

				$rank = 3;

				if (!empty($tab[$rank]))
				{
					$ret = array();
					$ret['url'] = "http://www.flickr.com" . trim($tab[$rank]);
					$ret['img']['url'] = trim($tab2[0]);
					$ret['img']['width'] = trim($tab2[2]);
					$ret['img']['height'] = trim($tab2[4]);
					$ret['author'] = trim($tab3[0]);
					//print_r($ret);
					//die();

					$data[] = $ret;
					//echo "New img on : " . "http://www.flickr.com" . $tab[$rank] . "\n";
				}
				else
				{
					echo "ERROR : URL not found ! have to update script !\n";
				}
			}

			sleep(2);
		}
		sleep(1);
		echo "[" . date("Y-m-d H:i:s") . "] [" . $query . "] (result : " . count($data) . ")\n";

		return $data;

		//grab and draw the contents
	}

	static function get_photo_info($url) {


		/*
		  if (!preg_match("/^http:\/\/www.flickr.com\/photos\/([a-zA-Z0-9]*)\/([0-9]*)\/$/i", $url))
		  {
		  die("$url did not match with REGEX : /^http:\/\/www.flickr.com\/photos\/([a-zA-Z0-9]*)\/([0-9]*)\/$/i");
		  } */

		$data = array();


		$user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.79 Safari/537.1'; // simule Firefox 4.
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Keep-Alive: 300";
		$header[] = "Accept-Charset: utf-8";
		$header[] = "Accept-Language: fr"; // langue fr. 
		$header[] = "Pragma: "; // Simule un navigateur



		$ch = curl_init();
		//curl_setopt($ch, CURLOPT_PROXY, 'proxy.int.world.socgen:8080');
		//curl_setopt($ch, CURLOPT_PROXYUSERPWD, "aurelien.lequoy:xxxxx");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
		$content = curl_exec($ch);
		curl_close($ch);


		$tmp = wlHtmlDom::getTagContent($content, '<div id="photo', true);
		if (false === $tmp)
		{
			return false;
		}

		$tab_id_photo = explode("/", $url);
		$data['id'] = "flickr_" . $tab_id_photo[5];

		$var = trim(htmlentities(wlHtmlDom::getTagContent($content, '<div class="photo-div', true)));
		preg_match('/http[^\s]*jpg/i', $var, $match);
		$data['photo'] = $match[0];
		$data['photo_o'] = $data['photo'];
		$part = substr($data['photo'], 0, -5);

		if (self::fileExists($part . "b.jpg"))
		{
			$data['photo'] = $part . "b.jpg";
		}
		else
		{
			if (self::fileExists($part . "o.jpg"))
			{
				$data['photo'] = $part . "o.jpg";
			}
		}

		$data['title'] = wlHtmlDom::getTagContent($content, '<h1', true);
		$tmp = wlHtmlDom::getTagContent($content, '<div id="description_div"', true);
		$tmp = str_replace("\r\n", " ", $tmp);
		$tmp = str_replace("\n", " ", $tmp);

		$data['legend'] = strip_tags($tmp);

		$tmp1 = trim(wlHtmlDom::getTagContent($content, '<span class="realname"', true));
		$data['author'] = trim(wlHtmlDom::getTagContent($tmp1, '<a href="/people/', true));

		if (empty($data['author']))
		{
			$tmp9 = trim(wlHtmlDom::getTagContent($content, '<div id="photo-story-attribution"', true));
			$tab9 = wlHtmlDom::getTagContents($tmp9, '<a', true);
			$data['author'] = $tab9[1];
		}

		$tmp2 = wlHtmlDom::getTagContent($content, '<p id="photo-story-story"', true);
		$tab2 = wlHtmlDom::getTagContents($tmp2, '<a', false);

		$col = array("date-taken", "country", "camera", "location");

		$data['location'] = "";

		foreach ($tab2 as $a_url)
		{
			foreach ($col as $param)
			{
				if (stristr($a_url, $param))
				{
					$tmp3 = wlHtmlDom::getTagContent($a_url, '<a', true);
					$data[$param] = trim($tmp3);
				}
			}
		}

		if (empty($data['country']) && !empty($data['location']))
		{
			$data['country'] = $data['location'];
		}

		if (!empty($data['date-taken']))
		{
			$var = explode(" ", $data['date-taken']);

			switch ($var[1])
			{
				case "janvier": $mois = "01";
					break;
				case "février": $mois = "02";
					break;
				case "mars": $mois = "03";
					break;
				case "avril": $mois = "04";
					break;
				case "mai": $mois = "05";
					break;
				case "juin": $mois = "06";
					break;
				case "juillet": $mois = "07";
					break;
				case "août": $mois = "08";
					break;
				case "septembre": $mois = "09";
					break;
				case "octobre": $mois = "10";
					break;
				case "novembre": $mois = "11";
					break;
				case "décembre": $mois = "12";
					break;
				default:
					echo "date invalide : " . $var[1] . " -\n";
					exit;
					break;
			}

			$data['date-taken'] = $var[2] . "-" . $mois . "-" . $var[0];
		}

		//$data['tab'] = $tab2;


		if (!empty($tab2[2]))
		{
			$tab = explode('"', $tab2[2]); //check
			$data['exif_url'] = "http://www.flickr.com" . $tab[1]; //check
		}


		$data['url'] = $url;

		$tmp3 = trim(wlHtmlDom::getTagContent($content, '<ul id="thetags"', true));
		$data['tag'] = wlHtmlDom::getTagContents($tmp3, '<a', true);

		$tmp4 = trim(wlHtmlDom::getTagContent($content, '<li class="Stats license">', true));
		$tmp4 = strip_tags($tmp4, "<a>");
		$gg = wlHtmlDom::getTagContents($tmp4, '<a', true);
		$tab = explode('"', $tmp4);
		$data['license']['url'] = $tab[1];
		$data['license']['text'] = $gg[1];

		$elem = explode("/", $data['photo']);
		$data['name'] = $elem[count($elem) - 1];


		flickr::dl_photo($data['photo']);

		$tmp = getimagesize(TMP . "photos_in_wait/" . $data['name']);

		$data['image']['width'] = $tmp[0];
		$data['image']['height'] = $tmp[1];
		$data['image']['mime'] = $tmp['mime'];


		if ($data['image']['mime'] != "image/jpeg")
		{

			shell_exec("rm " . TMP . "photos_in_wait/" . $data['name']);

			flickr::dl_photo($data['photo_o']);

			$elem = explode("/", $data['photo_o']);
			$data['name'] = $elem[count($elem) - 1];


			$tmp = getimagesize(TMP . "photos_in_wait/" . $data['name']);
			$data['image']['width'] = $tmp[0];
			$data['image']['height'] = $tmp[1];
			$data['image']['mime'] = $tmp['mime'];


			$data['photo'] = $data['photo_o'];

			if ($data['image']['mime'] != "image/jpeg")
			{
				echo "ERROR on : " . $data['url'] . "\n";
				return false;
			}
		}


		$data['image']['md5'] = md5_file(TMP . "photos_in_wait/" . $data['name']);

		//matitude & longitude
		$gps = trim(wlHtmlDom::getTagContent($content, '<div id="photo-story-map"', true));


		$data['gps']['latitude'] = '0';
		$data['gps']['longitude'] = '0';

		if (mb_strlen($gps) != 0)
		{
			$tab = explode("&c=", $gps);
			$tab = explode("&", $tab[1]);
			$tab = explode(",", $tab[0]);

			$data['gps']['latitude'] = (float) $tab[0];
			$data['gps']['longitude'] = (float) $tab[1];
		}

		return $data;
	}

	function get_photo_exif($url) {

		$data = array();

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_PROXY, 'proxy.int.world.socgen:8080');
		curl_setopt($ch, CURLOPT_PROXYUSERPWD, "aurelien.lequoy:Zeb33tln1$");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		$content = curl_exec($ch);
		curl_close($ch);

		$content = wlHtmlDom::getTagContent($content, '<div class="photo-data"', true);
		$tab = wlHtmlDom::getTagContents($content, '<h2', true);
		$tab2 = wlHtmlDom::getTagContents($content, '<table cellspacing="0" cellpadding="0" width="100%">', false);

		$i = 0;
		foreach ($tab as $elem)
		{
			$tr = wlHtmlDom::getTagContents($tab2[$i], '<tr', true);
			foreach ($tr as $var)
			{
				$th = trim(strip_tags(wlHtmlDom::getTagContent($var, '<th', true)));
				$td = wlHtmlDom::getTagContent($var, '<td', true);

				$data[$tab[$i]][$th] = trim(strip_tags(str_replace("<br />", " - ", str_replace("\n", "", $td))));
			}
			$i++;
		}
	}

	static function fileExists($path) {
		return (fopen($path, "r") == true);
	}

	static function dl_photo($url) {
		$cmd = "cd " . TMP . "photos_in_wait/; wget -nc " . $url . "";
		shell_exec($cmd);
	}

	static function get_photo_id($url) {

		$tab = explode("/", $url);

		return "flickr_" . $tab[5];
	}

}