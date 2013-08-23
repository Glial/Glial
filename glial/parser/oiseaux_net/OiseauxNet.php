<?php

namespace glial\parser\oiseaux_net;

use \glial\extract\HtmlDom;

class OiseauxNet
{
    public static function get_species_from_family($famliy = "estrildides")
    {
        $url = "http://www.oiseaux.net/oiseaux/" . $famliy . ".html";

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

        $content2 = HtmlDom::getTagContent($content, '<table class="tb_lite">', true);
        if (false === $content2) {
            return false;
        }

        $tab_tr = HtmlDom::getTagContents($content2, '<tr', true);

        $data = array();
        foreach ($tab_tr as $tr) {

            if (! strstr($tr ,"<a")) {
                continue;
            }

            $out = array();

            $out['French'] = HtmlDom::getTagContent($tr, '<a href="', true);
            $url = \wlHtmlDom::getTagContent($tr, '<a href="', false);

            $tab_url = explode('"',$url);
            $out['url'] = "http://www.oiseaux.net/oiseaux/".$tab_url[1];

            $resultat = pathinfo($tab_url[1]);
            $out['reference_id'] = $resultat['filename'];

            $tab_td = HtmlDom::getTagContents($tr, '<td', true);
            $out['scientific_name'] = $tab_td[1];
            $out['English'] = $tab_td[2];

            $data[] = $out;
        }

        return $data;

    }

}
