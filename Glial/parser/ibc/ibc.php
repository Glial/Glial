<?php

namespace glial\parser\ibc;

class ibc {

	static function get_species_from_family($famliy = "waxbills-estrildidae") {
		$url = "http://ibc.lynxeds.com/family/" . $famliy;

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

		$data = array();

		$content2 = \wlHtmlDom::getTagContent($content, '<ul id="media-status-specie" class="media-status-specie">', true);
		if (false === $content2)
		{
			return false;
		}
		$tab_li = \wlHtmlDom::getTagContents($content2, '<li', true);


		foreach ($tab_li as $li)
		{
			$out = array();

			$url = \wlHtmlDom::getTagContent($li, '<a href="/species/', false);

			$tab_url = explode('"', $url);
			$out['url'] = "http://ibc.lynxeds.com" . $tab_url[1];


			$tab_ref = explode("/", $tab_url[1]);
			$out['reference_id'] = $tab_ref[2];

			$out['scientific_name'] = \wlHtmlDom::getTagContent($li, '<span class="scientific">', true);

			$data [] = $out;
		}

		return $data;
	}

	static function get_order_and_family() {

		$url = "http://ibc.lynxeds.com/";

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

		$data = array();
		$content2 = \wlHtmlDom::getTagContent($content, '<div class="order-content">', true);
		$data_order = \wlHtmlDom::getTagContents($content, '<ul class="menu" id="', true);

		foreach ($data_order as $order)
		{
			$ordername = \wlHtmlDom::getTagContent($order, '<a>', true);
			$li = \wlHtmlDom::getTagContents($order, '<ul style="display: none;">', true);

			foreach ($li as $family)
			{
				$english = \wlHtmlDom::getTagContent($family, '<strong>', true);
				$url = explode('"', $family);
				preg_match('/\((.*?)\)/', $family, $match);
				$data[$ordername][$match[1]]['url'] = "http://ibc.lynxeds.com" . $url[1];
				$data[$ordername][$match[1]]['english'] = $english;
			}
		}
		return $data;
	}

	static function get_photo_and_infos($picture_link) {
		$url = "http://ibc.lynxeds.com/photo/" . $picture_link;

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

		$data = array();
		$content2 = \wlHtmlDom::getTagContent($content, "<h4>", true);
		$content2 = \wlHtmlDom::getTagContent($content2, "<a", true);
		$family_name = preg_split("#[\(\)]+#", $content2);
		$data['family_name'] = $family_name[0];
		$data['family_english'] = $family_name[1];
		$content2 = \wlHtmlDom::getTagContent($content, "<h3><a", true);
		$name = preg_split("#[\(\)]+#", $content2);
		$data['name'] = $name[0];
		$data['scientific_name'] = \wlHtmlDom::getTagContent($name[1], "<i>", true);
		$content2 = \wlHtmlDom::getTagContent($content, '<ul class="status">', true);
		$data['status'] = \wlHtmlDom::getTagContent($content2, '<li', true);
		$content2 = \wlHtmlDom::getTagContent($content, '<div class="photo-frame">', true);
		$photo = \wlHtmlDom::getTagContent($content2, '<img', false);
		$photo = preg_split("#[\"]#", $photo);
		$data['photo_url'] = $photo[1];
		$data['legend'] = \wlHtmlDom::getTagContent($content2, '<div class="caption-photo"', true);
		$data['recorded'] = \wlHtmlDom::getTagContent($content, '<span class="date-display-single">', true);
		$data['uploaded'] = \wlHtmlDom::getTagContent($content, '<em>', true) . ' ago';
		$author = explode('<a href="/users/', $content);
		$author = explode('</a>', $author[1]);
		$author = explode('">', $author[0]);
		$data['author'] = $author[1];
		$data['author_profile_link'] = 'http://ibc.lynxeds.com/users/' . $author[0];
		$locations = explode('<span class="location">Locality</span>', $content);
		$locations = explode('</li>', $locations[1]);
		$locations = explode(',', $locations[0]);
		$i = 0;
		while (isset($locations[$i])) {
			$line = $locations[$i];
			$line = explode('<a href="', $line);
			$line = explode('</a>', $line[1]);
			$tmp = explode('">', $line[0]);
			if (!(empty($line[1])))
				$plus = $line[1];
			else
				$plus = '';
			$localised['Location_' . $i] = trim($tmp[1] . ' ' . $plus);
			$localised['Location_link_' . $i] = trim('http://ibc.lynxeds.com' . $tmp[0]);
			$i++;
		}
		$data['locations'] = $localised;
		$content2 = \wlHtmlDom::getTagContent($content, '<div id="gmaps">', true);
		$content2 = \wlHtmlDom::getTagContent($content2, '<script>', true);
		$coords = preg_replace('#.+var coordX = \'(.+)\';.+var coordY = \'(.+)\';.+#isU', '$1 ($2)', $content2);
		$coords = preg_split('#[\(\)]#', $coords);
		$data['longitude'] = $coords[0];
		$data['latitude'] = $coords[1];
		$data['ranking'] = \wlHtmlDom::getTagContent($content, '<div class="star star-1 star-odd star-first"><span class="on">', true);
		return ($data);
	}

	static function get_video_and_infos($video_link) {
		$url = "http://ibc.lynxeds.com/video/" . $video_link;

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

		$data = array();
		$content2 = \wlHtmlDom::getTagContent($content, "<h4>", true);
		$content2 = \wlHtmlDom::getTagContent($content2, "<a", true);
		$family_name = preg_split("#[\(\)]+#", $content2);
		$data['family_name'] = $family_name[0];
		$data['family_english'] = $family_name[1];
		$content2 = \wlHtmlDom::getTagContent($content, "<h3><a", true);
		$name = preg_split("#[\(\)]+#", $content2);
		$data['name'] = $name[0];
		$data['scientific_name'] = \wlHtmlDom::getTagContent($name[1], "<i>", true);
		$content2 = \wlHtmlDom::getTagContent($content, '<ul class="status">', true);
		$data['status'] = \wlHtmlDom::getTagContent($content2, '<li', true);
		$content2 = \wlHtmlDom::getTagContent($content, '<div class="video-frame">', true);
		$video = \wlHtmlDom::getTagContent($content2, '<script', true);
		$video = explode('"', $video);
		$video = explode('\x', $video[19]);
		$video = explode('=', $video[0]);
		$data['video_url'] = 'http://ibc.lynxeds.com/files/videos/transcoded/' . $video[1] . '.mp4';
		$data['legend'] = \wlHtmlDom::getTagContent($content2, '<div class="caption-video"', true);
		$data['recorded'] = \wlHtmlDom::getTagContent($content, '<span class="date-display-single">', true);
		$data['uploaded'] = \wlHtmlDom::getTagContent($content, '<em>', true) . ' ago';
		$author = explode('<a href="/users/', $content);
		$author = explode('</a>', $author[1]);
		$author = explode('">', $author[0]);
		$data['author'] = $author[1];
		$data['author_profile_link'] = 'http://ibc.lynxeds.com/users/' . $author[0];
		$duration = explode('<span class="duration">', $content);
		$duration = explode('</span>', $duration[1]);
		$duration = explode('</li>', $duration[1]);
		$data['duration'] = trim($duration[0]);
		$locations = explode('<span class="location">Location </span>', $content);
		$locations = explode('</li>', $locations[1]);
		$locations = explode(',', $locations[0]);
		$i = 0;
		while (isset($locations[$i])) {
			$line = $locations[$i];
			$line = explode('<a href="', $line);
			$line = explode('</a>', $line[1]);
			$tmp = explode('">', $line[0]);
			if (!(empty($line[1])))
				$plus = $line[1];
			else
				$plus = '';
			$localised['Location_' . $i] = trim($tmp[1] . ' ' . $plus);
			$localised['Location_link_' . $i] = trim('http://ibc.lynxeds.com' . $tmp[0]);
			$i++;
		}
		$data['locations'] = $localised;
		$content2 = \wlHtmlDom::getTagContent($content, '<div id="gmaps">', true);
		$content2 = \wlHtmlDom::getTagContent($content2, '<script>', true);
		$coords = preg_replace('#.+var coordX = \'(.+)\';.+var coordY = \'(.+)\';.+#isU', '$1 ($2)', $content2);
		$coords = preg_split('#[\(\)]#', $coords);
		$data['longitude'] = $coords[0];
		$data['latitude'] = $coords[1];
		$data['ranking'] = \wlHtmlDom::getTagContent($content, '<div class="star star-1 star-odd star-first"><span class="on">', true);
		return ($data);
	}

	static function get_sound_and_infos($sound_link) {
		$url = "http://ibc.lynxeds.com/sound/" . $sound_link;

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

		$data = array();
		$content2 = \wlHtmlDom::getTagContent($content, "<h4>", true);
		$content2 = \wlHtmlDom::getTagContent($content2, "<a", true);
		$family_name = preg_split("#[\(\)]+#", $content2);
		$data['family_name'] = $family_name[0];
		$data['family_english'] = $family_name[1];
		$content2 = \wlHtmlDom::getTagContent($content, "<h3><a", true);
		$name = preg_split("#[\(\)]+#", $content2);
		$data['name'] = $name[0];
		$data['scientific_name'] = \wlHtmlDom::getTagContent($name[1], "<i>", true);
		$content2 = \wlHtmlDom::getTagContent($content, '<ul class="status">', true);
		$data['status'] = \wlHtmlDom::getTagContent($content2, '<li', true);
		$sound = explode('soundFile=http%3A%2F%2Fibc.lynxeds.com%2Faudio%2Fplay%2F', $content);
		$sound = explode('" />', $sound[1]);
		$data['sound_url'] = trim('http://ibc.lynxeds.com/audio/play/' . $sound[0]);
		$data['legend'] = \wlHtmlDom::getTagContent($content, '<div class="caption-sound">', true);
		$data['recorded'] = \wlHtmlDom::getTagContent($content, '<span class="date-display-single">', true);
		$data['uploaded'] = \wlHtmlDom::getTagContent($content, '<em>', true) . ' ago';
		$author = explode('<a href="/users/', $content);
		$author = explode('</a>', $author[1]);
		$author = explode('">', $author[0]);
		$data['author'] = $author[1];
		$data['author_profile_link'] = 'http://ibc.lynxeds.com/users/' . $author[0];
		$duration = explode('<span class="duration">', $content);
		$duration = explode('</span>', $duration[1]);
		$duration = explode('</li>', $duration[1]);
		$data['duration'] = trim($duration[0]);
		$locations = explode('<span class="location">Location </span>', $content);
		$locations = explode('</li>', $locations[1]);
		$locations = explode(',', $locations[0]);
		$i = 0;
		while (isset($locations[$i])) {
			$line = $locations[$i];
			$line = explode('<a href="', $line);
			$line = explode('</a>', $line[1]);
			$tmp = explode('">', $line[0]);
			if (!(empty($line[1])))
				$plus = $line[1];
			else
				$plus = '';
			$localised['Location_' . $i] = trim($tmp[1] . ' ' . $plus);
			$localised['Location_link_' . $i] = trim('http://ibc.lynxeds.com' . $tmp[0]);
			$i++;
		}
		$data['locations'] = $localised;
		$content2 = \wlHtmlDom::getTagContent($content, '<div id="gmaps">', true);
		$content2 = \wlHtmlDom::getTagContent($content2, '<script>', true);
		$coords = preg_replace('#.+var coordX = \'(.+)\';.+var coordY = \'(.+)\';.+#isU', '$1 ($2)', $content2);
		$coords = preg_split('#[\(\)]#', $coords);
		$data['longitude'] = $coords[0];
		$data['latitude'] = $coords[1];
		$data['ranking'] = \wlHtmlDom::getTagContent($content, '<div class="star star-1 star-odd star-first"><span class="on">', true);
		return ($data);
	}

}