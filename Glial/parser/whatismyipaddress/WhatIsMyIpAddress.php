<?php

/**
 * Glial Framework
 *
 * LICENSE
 *
 * 
 */

namespace glial\parser\whatismyipaddress;

class WhatIsMyIpAddress
{

	static function getInfoFromIp($adresse_ip)
	{


		$url = "http://whatismyipaddress.com/ip/" . $adresse_ip;

		$ch = curl_init();
		//curl_setopt($ch, CURLOPT_PROXY, 'proxy.int.world.socgen:8080');
		//curl_setopt($ch, CURLOPT_PROXYUSERPWD, "aurelien.lequoy:xxxxx");
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; rv:14.0) Gecko/20100101 Firefox/14.0.1");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data_brut = curl_exec($ch);
		curl_close($ch);

		//$data_brut = file_get_contents("gg"); //pour les test

		if ( !$data_brut )
		{
			return false;
		}

		//$data_brut = mb_convert_variables("UTF-8", "ISO-8859-1", $data_brut);
		$tab = html_dom::getTagContents($data_brut, '<table', true);

		if ( !is_array($tab) )
		{
			return false;
		}

		if ( empty($tab[1]) )
		{
			return false;
		}


		$mapping = array(
			array(
				'var' => 'IP:',
				'val' => 'ip',
			),
			array(
				'var' => 'Decimal:',
				'val' => 'decimal',
			),
			array(
				'var' => 'Hostname:',
				'val' => 'hostname',
			),
			array(
				'var' => 'ISP:',
				'val' => 'isp',
			),
			array(
				'var' => 'Organization:',
				'val' => 'organization',
			),
			array(
				'var' => 'Services:',
				'val' => 'services',
			),
			array(
				'var' => 'Type:',
				'val' => 'type',
			),
			array(
				'var' => 'Assignment:',
				'val' => 'assignment',
			),
			array(
				'var' => 'Country:',
				'val' => 'country',
			),
			array(
				'var' => 'Area Code:',
				'val' => 'area_code',
			),
			array(
				'var' => 'City:',
				'val' => 'city',
			),
			array(
				'var' => 'Latitude:',
				'val' => 'latitude',
			),
			array(
				'var' => 'Longitude:',
				'val' => 'longitude',
			),
			array(
				'var' => 'Postal Code:',
				'val' => 'postal_code',
			),
			array(
				'var' => 'State/Region:',
				'val' => 'region',
			),
		);



		$tab1 = html_dom::getTagContents($tab[0], '<td', true);
		$tab2 = html_dom::getTagContents($tab[1], '<td', true);


		$tab3 = html_dom::getTagContents($tab[0], '<th', true);
		$tab4 = html_dom::getTagContents($tab[1], '<th', true);

		print_r($tab3);
		print_r($tab4);


		$ip = array();
		$ip['ip'] = $adresse_ip;
		$ip['decimal'] = $tab1[1];
		$ip['hostname'] = $tab1[2];
		$ip['isp'] = $tab1[3];
		$ip['organization'] = $tab1[3];
		$ip['services'] = $tab1[4];
		$ip['type'] = html_dom::getTagContent($tab1[5], '<a', true);
		$ip['assignment'] = html_dom::getTagContent($tab1[6], '<a', true);

		$tab_iso = explode("flags/", $tab2[0]);
		$tab_iso = explode(".png", $tab_iso[1]);

		$ip['iso'] = $tab_iso[0];
		$ip['country'] = trim(strip_tags($tab2[0]));
		$ip['area'] = $tab2[1];
		$ip['city'] = $tab2[2];
		$ip['latitude'] = $tab2[3];
		$ip['longitude'] = $tab2[4];

		print_r($ip);

		return $ip;
	}

}

