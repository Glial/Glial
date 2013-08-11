<?php

/**
 * Glial Framework
 *
 * LICENSE
 *
 * 
 */

namespace gliale\parser\google_image;

class GoogleImage
{

	function get_image()
	{

		$var = "White headed munia";

		$var2 = urlencode($var);

		$url = "http://www.google.fr/search?q=%22Lonchura+maja%22&num=50&hl=fr&client=firefox-a&hs=Dcl&rls=org.mozilla:fr:official&prmd=imvns&tbm=isch&tbo=u&source=univ&sa=X&ei=3TTWTpoQh4HiBPrHzZUB&ved=0CDEQsAQ&biw=1173&bih=748";
		$url = "http://www.google.fr/search?hl=fr&client=firefox-a&hs=Vin&rls=org.mozilla:fr:official&biw=1173&bih=775&tbm=isch&q=lonchura+maja&btnG=Rechercher&oq=lonchura+maja&aq=f&aqi=&gs_upl=0l0l0l160005l0l0l0l0l0l0l0l0ll0l0&gbv=1&sei=XovXTuGSCKL74QSRp73LDQ";
		$url = "http://www.google.fr/search?q=%22Lonchura+maja%22&hl=fr&client=firefox-a&rls=org.mozilla:fr:official&biw=1173&bih=775&gbv=1&tbm=isch&ei=zJzXTo7xGaz44QSnnqnVDQ&start=0&sa=N";
		$url = "http://www.google.fr/search?q=%22Lonchura+maja%22&hl=fr&client=firefox-a&rls=org.mozilla:fr:official&biw=1173&bih=775&gbv=1&tbm=isch&ei=fJzXTpq5A4aj4gTjt73rDQ&start=20&sa=N";
		$url = "http://www.google.fr/search?q=%22" . $var2 . "%22&hl=fr&client=firefox-a&rls=org.mozilla:fr:official&biw=1173&bih=775&gbv=1&tbm=isch&ei=jpzXTsqaE6bb4QSohJneDQ&start=0&sa=N";

		$data = array();

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_PROXY, 'proxy.int.world.socgen:8080');
		curl_setopt($ch, CURLOPT_PROXYUSERPWD, "aurelien.lequoy:Zeb33tln1$");
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 5.1; rv:8.0) Gecko/20100101 Firefox/8.0");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		$content = curl_exec($ch);
		curl_close($ch);



		$tab = wlHtmlDom::getTagContent($content, '<table width="100%" class="images_table" style="table-layout:fixed"', true);


		$grid = wlHtmlDom::getTagContents($tab, '<td', true);




		echo "<pre>";

		print_r($grid);
		echo "</pre>";


		foreach ( $grid as $img )
		{
			preg_match('/http[^\s]*&amp;/i', $img, $match);
			$res = explode("&amp;", $match[0]);
			$elem['url'] = urldecode(urldecode($res[0]));

			$ref = stristr($img, "imgrefurl=");
			$ref = stristr($ref, "&amp;", true);
			$elem['ref'] = urldecode(stristr($ref, "http"));

			$ff = explode("<br>", $img);
			$elem['legend'] = strip_tags($ff[1]);
			$gg = explode("-", strip_tags($ff[2]));

			$elem['file_size'] = str_replace("&nbsp;", "", trim($gg[1]));
			$size_img = explode("&times;", $gg[0]);

			$elem['width'] = trim($size_img[0]);
			$elem['height'] = trim($size_img[1]);

			$elem['ext'] = str_replace("&nbsp;", "", trim($gg[2]));

			$url = explode('"', trim($ff[3]));

			$elem['site'] = $url[1];

			//if (stristr($img, $var))	continue;

			if ( $elem['width'] < 250 || $elem['height'] < 250 )
			{
				$error[] = $elem;
				continue;
			}


			//1: we already extract these element from speficific interface
			//2: we remove website with low or bad quality image


			$exclude = array("flickr.com", "lonchuramyworld.monempire.net", "hofmann-photography.de", "ibc.lynxeds.com", "flickriver.com", "photozoo.org");

			if ( in_array($elem['site'], $exclude) )
			{
				$error[] = $elem;
				continue;
			}

			if ( stristr($elem['legend'], $var) === false )
			{
				$error[] = $elem;
				continue;
			}

			$data[] = $elem;
		}
		echo "<pre>";

		print_r($data);
		print_r($error);
		echo "</pre>";

		/*
		  preg_match_all("#\[\[.*\]\]#",$tab,$res);


		  $tmp = substr ($res[0][0],0,-2);
		  $tmp = substr ($tmp,2);
		  $tmp = str_replace(",[],",",",$tmp );
		  $res = explode("],[", $tmp );



		  $var1 = array("\\x3d", "\\x3c", "\\x3e", "\\x26");
		  $var2 = array("=", "<", ">", "&");


		  foreach($res as $var)
		  {


		  $var = substr ($var,1,-1);


		  //$var = str_replace('"",','',$var );
		  $var = str_replace('"','',$var );

		  $var = rawurldecode ($var);
		  $var = str_replace($var1,$var2,$var );

		  $elem[] = explode(',', $var );
		  } */
	}

}