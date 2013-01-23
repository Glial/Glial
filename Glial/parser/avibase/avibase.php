<?php

/**
 * Glial Framework
 *
 * LICENSE
 *
 * 
 */
//namespace gliale\flickr;

namespace glial\parser\avibase;


class avibase
{

	static function get_species_by_reference($id_avibase = "01A3BE3CBE9C7B39")
	{



		$data = array();


		$url = "http://avibase.bsc-eoc.org/species.jsp?lang=EN&avibaseid=" . $id_avibase;

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


		$content2 = wlHtmlDom::getTagContents($content, '<td class="AVBContainerText" bordercolor="#FFFFFF" width=50%>', true);
		if ( false === $content2 )
		{
			return false;
		}

		$data = array();


		$p = wlHtmlDom::getTagContents($content2[0], '<p>', true);
		foreach ( $p as $p_part )
		{
			$ret = self::extract_p($p_part);
			$data = array_merge($data, $ret);
		}
		$data['TSN'] = wlHtmlDom::getTagContent($p[2], '<a', true);


		$ret = self::extract_p($content2[1]);
		$data['Language'] = $ret;


		$ret = wlHtmlDom::getTagContent($content, '<td class="AVBSynonyms">', true);

		$ret = str_replace('<font size="2"><center><b>Other synonyms</b></center>', "", $ret);


		$syn = explode("<br>", $ret);


		unset($syn[0]);


		foreach ( $syn as $tab_syn )
		{
			$var = trim(str_replace(":", "", wlHtmlDom::getTagContent($tab_syn, '<b>', true)));


			$val = strip_tags(strstr($tab_syn, "</b>"));

			$synonym = explode(",", $val);


			foreach ( $synonym as $tab_gg )
			{
				$data['Synonyms'][$var][] = trim($tab_gg);
			}
		}

		$ret = wlHtmlDom::getTagContent($content, '<span class="AVBContainerText">', true);

		$ret = str_replace("<P><b>Authorities recognizing this taxonomic concept:</b></P>", "", $ret);
		$ret = str_replace("&nbsp;&nbsp;&nbsp;", " ", $ret);
		$ret = str_replace("( <I>", "(<I>", $ret);

		$ret = strip_tags($ret, "<i>");


		$ret = explode("\r\n\r\n", $ret);
		$ret = array_map("trim", $ret);

		unset($ret[count($ret) - 1]);
		unset($ret[0]);

		$data['Authorities'] = $ret;

		//debug($data);

		$i = 0;
		//echo "[" . date("Y-m-d H:i:s") . "] [page : " . $i . "] (result : " . count($data) . ")\r\n";




		return $data;
	}

	static function extract_p($p)
	{
		$p = trim($p);

		$data = array();
		$res = explode("&nbsp;&nbsp;", $p);

		$title = wlHtmlDom::getTagContents($p, '<b>', true);

		foreach ( $title as &$elem )
		{
			$elem = str_replace(":", "", $elem);
		}

		$i = 0;
		foreach ( $res as $elem2 )
		{
			if ( $i == 0 )
			{
				$i++;
				continue;
			}

			$var = explode('<br>', $elem2);
			$data[$title[$i - 1]] = trim(strip_tags($var[0]));

			$i++;
		}

		return $data;
	}

	static function get_regions()
	{
		$url = "http://avibase.bsc-eoc.org/checklist.jsp?lang=EN";

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
		$content2 = \wlHtmlDom::getTagContent($content, '<table border="0" width="860" cellspacing="1">', true);
		$content2 = str_replace('<a href="checklist.jsp?region=', '.', $content2);
		$content2 = str_replace('&list=clements">', '.', $content2);
		$content2 = str_replace('&nbsp;', '', $content2);
		$content2 = strip_tags($content2);
		$content2 = explode('.', $content2);
		$i = 0;
		while ( isset($content2[$i]) )
		{
			if ( preg_match('#[A-Z\s]+#', $content2[$i]) )
				unset($content2[$i]);
			$i++;
		}
		$data = $content2;
		return $data;
	}

	static function get_ids($region)
	{
		$url = "http://avibase.bsc-eoc.org/checklist.jsp?region=" . $region . "&list=clements";

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
		$content2 = \wlHtmlDom::getTagContent($content, '<table class="AVBlist">', true);
		$content2 = str_replace('<a href="species.jsp?avibaseid=', '.', $content2);
		$content2 = str_replace('"><i>', '.', $content2);
		$content2 = str_replace('&nbsp;', '', $content2);
		$content2 = strip_tags($content2);
		$content2 = explode('.', $content2);
		$i = 0;
		while ( isset($content2[$i]) )
		{
			if ( !(preg_match('#[0-9]#', $content2[$i])) )
				unset($content2[$i]);
			$i++;
		}
		$data = $content2;
		return $data;
	}

	//' ⇡'' ⇣'
}