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
		$content2 = \wlHtmlDom::getTagContent($content, "<h4><a", true);
		$family_name = preg_split("#[\(\)]+#", $content2);
		$data['family_name'] = $family_name[0];
		$data['family_english'] = $family_name[1];
		$content2 = \wlHtmlDom::getTagContent($content, "<h3><a", true);
		$content2 = preg_replace("#<li>(.+)</li>#", "$1", $content2);
		$name = preg_split("#[\(\)]+#", $content2);
		$data['name'] = $name[0];
		$data['scientific_name'] = $name[1];
		$content2 = \wlHtmlDom::getTagContent($content, '<ul class="status"><li', true);
		$data['status'] = $content2;
		$content2 = \wlHtmlDom::getTagContent($content, '<div class="photo-frame"', true);
		$content2 = preg_replace('#<a href="(.+)" .+<div class="caption-photo">(.+)#', "$1 ($2)", $content2);
		$photo_infos = preg_split("#[\(\)]+#", $content2);
		$data['photo_url'] = $photo_infos[0];
		$data['legende'] = $photo_infos[1];
		$upload_info = \wlHtmlDom::getTagContents($content, '<ul class="datails no-margin"', true);
		foreach ($upload_info as $info)
		{
			$info = preg_replace('#.+<li><span class="(.+)".+</span>(.+)#isU', '$1 ($2)', $info);
			if (preg_match("#photo_info#", $info))
				$data['recorded'] = preg_replace('#.+ \(<span class=".+">(.+)</span>\)#isU', '$1', $info);
			else if (preg_match("#uploaded#", $info))
				$data['uploaded'] = preg_replace('#.+<em>(.+)</em>.+#isU', '$1', $info);
			else if (preg_match("#author#", $info))
			{
				$data['author_profile_url'] = 'http://ibc.lynxeds.com/' . preg_replace('#<a href="(.+)"#isU', '$1', $info);
				$data['author'] = preg_replace('#<a href=".+">(.+)</a>#isU', '$1', $info);
			}
			else if (preg_match("#location#", $info))
			{
				$locations = explode(',', $info);
				$i = 0;
				foreach ($locations as $location)
				{
					$location = preg_replace('#<a href="(.+)">(.+)</a>#isU', '$1 - $2', $location);
					$location = explode('-', $location);
					$data['location_' . $i] = $location[0];
					$data['location_' . $i . '_link'] = 'http://ibc.lynxeds.com/' . $location[1];
					$i++;
				}
			}
		}
		$content2 = \wlHtmlDom::getTagContent($content, '<div id="borderMap"', true);
		$data['longitude'] = preg_replace('#var coordX = \'(.+)\';#isU', '$1', $content2);
		$data['latitude'] = preg_replace('#var coordY = \'(.+)\';#isU', '$1', $content2);
		return ($data);
	}

	static function get_video_and_infos($video_link) {
		$url = "http://ibc.lynxeds.com/video/" . $picture_link;

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
		$content2 = \wlHtmlDom::getTagContent($content, "<h4><a", true);
		$family_name = preg_split("#[\(\)]+#", $content2);
		$data['family_name'] = $family_name[0];
		$data['family_english'] = $family_name[1];
		$content2 = \wlHtmlDom::getTagContent($content, "<h3><a", true);
		$content2 = preg_replace("#<li>(.+)</li>#", "$1", $content2);
		$name = preg_split("#[\(\)]+#", $content2);
		$data['name'] = $name[0];
		$data['scientific_name'] = $name[1];
		$content2 = \wlHtmlDom::getTagContent($content, '<ul class="status"><li', true);
		$data['status'] = $content2;
		$content2 = \wlHtmlDom::getTagContent($content, '<div class="video-frame"', true);
		$content2 = preg_replace('#<script type="text/javascript>"(.+)</script> .+<div class="caption-video">(.+)#', "$1 ($2)", $content2);
		$video_infos = preg_split("#[>]+#", $content2);
		$data['video'] = $video_infos[0] . '>';
		$data['legende'] = $video_infos[1];
		$upload_info = \wlHtmlDom::getTagContents($content, '<ul class="details no-margin"', true);
		foreach ($upload_info as $info)
		{
			$info = preg_replace('#.+<li><span class="(.+)".+</span>(.+)#isU', '$1 ($2)', $info);
			if (preg_match("#photo_info#", $info))
				$data['recorded'] = preg_replace('#.+ \(<span class=".+">(.+)</span>\)#isU', '$1', $info);
			else if (preg_match("#uploaded#", $info))
				$data['uploaded'] = preg_replace('#.+<em>(.+)</em>.+#isU', '$1', $info);
			else if (preg_match("#author#", $info))
			{
				$data['author_profile_url'] = 'http://ibc.lynxeds.com/' . preg_replace('#<a href="(.+)"#isU', '$1', $info);
				$data['author'] = preg_replace('#<a href=".+">(.+)</a>#isU', '$1', $info);
			}
			else if (preg_match("#location#", $info))
			{
				$locations = explode(',', $info);
				$i = 0;
				foreach ($locations as $location)
				{
					$location = preg_replace('#<a href="(.+)">(.+)</a>#isU', '$1 - $2', $location);
					$location = explode('-', $location);
					$data['location_' . $i] = $location[0];
					$data['location_' . $i . '_link'] = 'http://ibc.lynxeds.com/' . $location[1];
					$i++;
				}
			}
		}
		$content2 = \wlHtmlDom::getTagContent($content, '<div id="borderMap"', true);
		$data['longitude'] = preg_replace('#var coordX = \'(.+)\';#isU', '$1', $content2);
		$data['latitude'] = preg_replace('#var coordY = \'(.+)\';#isU', '$1', $content2);
		return ($data);
	}

}