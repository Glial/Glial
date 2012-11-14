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

		foreach($data_order as $order)
		{
			$ordername = \wlHtmlDom::getTagContent($order, '<a>', true);
			$li = \wlHtmlDom::getTagContents($order, '<ul style="display: none;">', true);
			
			foreach($li as $family)
			{
				$english = \wlHtmlDom::getTagContent($family, '<strong>', true);
				$url = explode('"', $family);
				preg_match('/\((.*?)\)/',$family,$match);
				$data[$ordername][$match[1]]['url'] = "http://ibc.lynxeds.com".$url[1];
				$data[$ordername][$match[1]]['english'] = $english;
			}
		}
		return $data;
	}
}