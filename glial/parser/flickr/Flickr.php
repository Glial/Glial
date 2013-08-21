<?php

/**
 * Glial Framework
 *
 * LICENSE
 *
 * 
 */
namespace glial\parser\flickr;


use \glial\extract\HtmlDom;

//http://farm8.staticflickr.com/7253/8161959793_a81037254c.jpg
//http://farm8.staticflickr.com/7253/8161959793_a81037254c_s.jpg

class Flickr {


	private static $url = "http://www.flickr.com";
	
	private static $size = array("sq" => "_s", //http://farm8.staticflickr.com/7022/6657652857_34d38960ab_s.jpg 75*75
	"q" => "_q", //http://farm8.staticflickr.com/7022/6657652857_34d38960ab_q.jpg 150*150
	"t" => "_t", //http://farm8.staticflickr.com/7022/6657652857_34d38960ab_t.jpg ~100
	"s" => "_m", //http://farm8.staticflickr.com/7022/6657652857_34d38960ab_m.jpg ~240
	"n" => "_n", //http://farm8.staticflickr.com/7022/6657652857_34d38960ab_n.jpg ~320
	"m" => "", //http://farm8.staticflickr.com/7022/9304372638_c137834ec8.jpg ~500
	"z" => "_z", //http://farm8.staticflickr.com/7022/6657652857_34d38960ab_z.jpg ~640
	"c" => "_c", //http://farm6.staticflickr.com/5449/9304372638_c137834ec8_c.jpg ~800
	"l" => "_b", //http://farm8.staticflickr.com/7022/6657652857_34d38960ab_b.jpg ~1024 //same
	"h" => "_h", //http://farm6.staticflickr.com/5449/9304372638_6f41482d98_h.jpg ~1600
	"k" => "_k", //http://farm6.staticflickr.com/5449/9304372638_7f59c80340_k.jpg ~2048
	"o" => "_o"); //http://farm8.staticflickr.com/7022/6657652857_34d38960ab_b.jpg ~Originale
	
	private static $allowed = array("m","n","c","l");
	
	
	static function curl($url)
	{
		$ch = curl_init();


		$user_agent = 'Mozilla/5.0 (Windows NT 5.1; rv:22.0) Gecko/20100101 Firefox/22.0';
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
		
		return $content;
	
	}

	static function get_links_to_photos($query) {

		//$q = str_replace(" ", "+", $query);

		$q = urlencode($query);

		//version 1 => Flickr get tout les йlйments
		$data = array();

		for ($i = 1; $i < 67; $i++) // 67 pages is a max of Flickr
		{
			$url = self::$url."/search/?q=" . $q . "&s=rec&page=" . $i;

			//echo $url ."\n";
			$content = self::curl($url);
			$contents = HtmlDom::getTagContents($content, '<div class="photo-display-item"', true);

			if (! $contents) //if no any photo we stop here !
			{
				break;
			}
			
			foreach ($contents as $var)
			{
				$author = HtmlDom::getTagContent($var, '<a data-track="owner"', true);
				$brut_img = HtmlDom::getTagContent($var, '<a data-track="photo-click"', true);
				$img = HtmlDom::getTagAttributeValue($brut_img,"data-defer-src");
				$url = HtmlDom::getTagAttributeValue($var,"href");
				$width = HtmlDom::getTagAttributeValue($var,"width");
				$height = HtmlDom::getTagAttributeValue($var,"height");
				$title = HtmlDom::getTagAttributeValue($var,"title");
				
				$ret = array();
				$ret['img2']['url'] = trim($img);
				
				$pattern = "#(http://farm[0-9]+\.staticflickr\.com/[0-9]+/[0-9]+_[a-f0-9]+)(_[a-z]{1,2})?\.jpg#i";
				
				if (preg_match($pattern,trim($img), $mathes ))
				{
					$ret['img']['url'] = $mathes[1]."_s.jpg";
				}
				else
				{
					print_r($mathes);
					die("error url img");
				}
				
				
				$ret['author'] = trim($author);
				$ret['img']['width'] = trim($width);
				$ret['img']['height'] = trim($height);
				$ret['url'] = self::$url . trim($url);
				$ret['title'] = trim($title);
				
				$data[] = $ret;

			}

			sleep(2);
		}
		sleep(1);
		
		$repeat = 50 - mb_strlen($query);
		echo "[" . date("Y-m-d H:i:s") . "] (result : " . count($data) . ") [" . $query . "] \n";

		return $data;

		//grab and draw the contents
	}

	static function get_photo_info($url) {


		$pattern = "#^".self::$url."/photos/([a-zA-Z0-9@]+)/([0-9]*)\/#i";
		
		if (!preg_match($pattern, $url))
		{
			die($url." did not match with REGEX : ".$pattern);
		}

		$data = array();
		$content = self::curl($url);
		
		$contents = HtmlDom::getTagContent($content, '<div id="photo', true);
		if (false === $contents)
		{
			return false;
		}

		$tab_id_photo = explode("/", $url);
		$data['id'] = "flickr_" . $tab_id_photo[5];
		$data['id_photo'] = $tab_id_photo[5];
		$data['url']['main'] = $url;
		
		$brut_canonical = HtmlDom::getTagContent($content, '<span class="photo-name-line-1"');
		if ($brut_canonical)
		{
			$tmp = HtmlDom::getTagAttributeValue($brut_canonical,"href");
			if (preg_match('#photos/([a-z0-9@]+)/#i',$tmp, $out))
			{
				$data['id_author'] = $out[1];
			}
			else
			{
				
				die("Error : Impossible to get id_author\n");
				//return false;
			}
		}
		
		$brut_min = HtmlDom::getTagContent($content, '<div id="photo', true);
		
		$data['url']['img_z'] = HtmlDom::getTagAttributeValue($brut_min,"src");
		$data['legend'] = trim(HtmlDom::getTagContent($content, '<div id="description_div" class="photo-desc"', true));
		$data['legend'] = strip_tags(preg_replace('!\s+!', ' ', $data['legend']));
		
		$brut_author = trim(HtmlDom::getTagContent($content, '<span class="photo-name-line-1"', true));
		$data['author'] = trim(HtmlDom::getTagContent($brut_author, '<a', true));
		
		$elems = trim(HtmlDom::getTagContent($content, '<div id="photo-story-story"', true));
		
		$lis = HtmlDom::getTagContents($elems, '<li', true);
		foreach($lis as $li)
		{
			$tmp = trim(HtmlDom::getTagContent($li, '<a', true));
			//echo $tmp.PHP_EOL;
			
			if (preg_match('/[A-Z]{1}[a-z]+ [0-9]{1,2}, [12]{1}[0-9]{3}$/i', $tmp))
			{
				$data['date-taken'] = $tmp;
			}
			
			if (preg_match('/[a-zA-Z ]+,&nbsp;[a-zA-Z ]+,&nbsp;[a-zA-Z ]+$/i', $tmp))
			{
				$data['location'] = trim(str_replace("&nbsp;", " ", $tmp));
				$data['url']['location'] = self::$url.HtmlDom::getTagAttributeValue($li,"href");
			}
			
			$tmp2 = HtmlDom::getTagAttributeValue($li,"href");
			if (preg_match('#^/cameras/#i', $tmp2))
			{
				$data['camera'] = $tmp;
			}
		}
		
		$tag_brut = HtmlDom::getTagContent($content, '<ul id="thetags"', true);
		
		if ($tag_brut)
		{
			$tags = HtmlDom::getTagContents($tag_brut, '<li', true);
			
			$data['tag'] = array();
			
			foreach($tags as $tag)
			{
				$data['tag'][] = HtmlDom::getTagContent($tag, '<a', true);
			}
		}
		
		$brut_license = HtmlDom::getTagContent($content, '<ul class="icon-inline sidecar-list', true);
		
		$data['license']['text'] = HtmlDom::getTagContents($brut_license, '<a', true)[1];
		$data['license']['url'] = HtmlDom::getTagAttributeValue(HtmlDom::getTagContents($brut_license, '<a', false)[1],"href");
		
		//print_r($brut_license);
		
		$brut_exif = HtmlDom::getTagContent($content, '<a id="exif-details"');
		$data['url']['exif'] = self::$url.HtmlDom::getTagAttributeValue($brut_exif,"href");
		
		
		$data['url']['all_size'] = self::$url."/photos/".$data['id_author']."/".$data['id_photo']."/sizes/sq/";
		
		$brut_latitude = HtmlDom::getTagContent($content, '<meta property="flickr_photos:location:latitude"', false);
		if ($brut_latitude)
		{
			$data['gps']['latitude'] = HtmlDom::getTagAttributeValue($brut_latitude,"content");
		}	
		
		$brut_latitude = HtmlDom::getTagContent($content, '<meta property="flickr_photos:location:longitude"', false);
		if ($brut_latitude)
		{
			$data['gps']['longitude'] = HtmlDom::getTagAttributeValue($brut_latitude,"content");
		}
		
		$data['img'] = self::get_all_size($data['url']['all_size']);
	
		if (! empty($data['url']['exif']))
		{
			$data['exif'] = self::get_photo_exif($data['url']['exif']);
		}
	/*
		$data['legend'] = strip_tags($tmp);

		$tmp1 = trim(HtmlDom::getTagContent($content, '<span class="realname"', true));
		$data['author'] = trim(HtmlDom::getTagContent($tmp1, '<a href="/people/', true));

		if (empty($data['author']))
		{
			$tmp9 = trim(HtmlDom::getTagContent($content, '<div id="photo-story-attribution"', true));
			$tab9 = HtmlDom::getTagContents($tmp9, '<a', true);
			$data['author'] = $tab9[1];
		}

		$tmp2 = HtmlDom::getTagContent($content, '<p id="photo-story-story"', true);
		$tab2 = HtmlDom::getTagContents($tmp2, '<a', false);

		$col = array("date-taken", "country", "camera", "location");

		$data['location'] = "";

		foreach ($tab2 as $a_url)
		{
			foreach ($col as $param)
			{
				if (stristr($a_url, $param))
				{
					$tmp3 = HtmlDom::getTagContent($a_url, '<a', true);
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

		$tmp3 = trim(HtmlDom::getTagContent($content, '<ul id="thetags"', true));
		$data['tag'] = HtmlDom::getTagContents($tmp3, '<a', true);

		$tmp4 = trim(HtmlDom::getTagContent($content, '<li class="Stats license">', true));
		$tmp4 = strip_tags($tmp4, "<a>");
		$gg = HtmlDom::getTagContents($tmp4, '<a', true);
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
		$gps = trim(HtmlDom::getTagContent($content, '<div id="photo-story-map"', true));


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
		*/

		return $data;
	}

	
	
	static function get_photo_exif($url) {

		$data = array();

		$content = self::curl($url);

		$content = HtmlDom::getTagContent($content, '<div class="photo-data"', true);
		$tab = HtmlDom::getTagContents($content, '<h2', true);
		$tab2 = HtmlDom::getTagContents($content, '<table cellspacing="0" cellpadding="0" width="100%">', false);

		$i = 0;
		foreach ($tab as $elem)
		{
			$tr = HtmlDom::getTagContents($tab2[$i], '<tr', true);
			foreach ($tr as $var)
			{
				$th = trim(strip_tags(HtmlDom::getTagContent($var, '<th', true)));
				$td = HtmlDom::getTagContent($var, '<td', true);

				$data[$tab[$i]][$th] = trim(strip_tags(str_replace("<br />", " - ", str_replace("\n", "", $td))));
			}
			$i++;
		}
		
		return $data;
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
	
	static function get_all_size($url)
	{
		$content = self::curl($url);
		$keys = explode('/',$url);
		
		$lis = HtmlDom::getTagContent($content, '<ol class="sizes-list"', true);
		if ($lis)
		{
			$pattern = '#/'.$keys[3].'/'.$keys[4].'/'.$keys[5].'/'.$keys[6].'/([a-z]{1,2})/#i';
			preg_match_all($pattern, $lis, $matches);
			
			
			$tmp['size_available'] = $matches[1];
			
			foreach($tmp['size_available'] as $size)
			{
				if (in_array($size, self::$allowed))
				{
					$tmp['best'] = $size;
				}
			}
			
			if (empty($tmp['best']))
			{
				return false;
			}
			
			$brut_url = HtmlDom::getTagContent($content, '<div id="allsizes-photo"', true);
			$img = HtmlDom::getTagAttributeValue($brut_url,"src");
			
			$tmp['url']['img'] = str_replace("_s.jpg",  self::$size[$tmp['best']].".jpg",$img);
			
		}
		else
		{
			return false;
		}
		
		return $tmp;
	}
}