<?php

/**
 * Glial Framework
 *
 * LICENSE
 *
 *
 */

namespace gliale\parser\pbase;

class Pbase
{

    public function getAllResults($url)
    {

        $url = "http://www.pbase.com/wongtsushi/whiteeyes_munias_buntings";
        $url = "http://www.pbase.com/ingotkfr/chestnut_munia";

        $data = array();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_PROXY, 'proxy.int.world.socgen:8080');
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, "aurelien.lequoy:Zeb33tln1$");
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 5.1; rv:8.0) Gecko/20100101 Firefox/8.0");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $content = curl_exec($ch);
        curl_close($ch);

        $tab = wlHtmlDom::getTagContent($content, '<DIV class="thumbnails">', true);

        $tab = wlHtmlDom::getTagContents($tab, '<TD', true);

        foreach ($tab as $line) {
            $elem = explode('"', $line);
            $data[]['url'] = $elem[1];
        }

        echo "<pre>";
        print_r($data);
        echo "</pre>";
    }

    public function getImage($url, $fullpath)
    {

        $ci = curl_init();
        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_PROXY, 'proxy.int.world.socgen:8080');
        curl_setopt($ci, CURLOPT_PROXYUSERPWD, "aurelien.lequoy:Zeb33tln1$");
        curl_setopt($ci, CURLOPT_HEADER, 0);
        curl_setopt($ci, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);

        $data = curl_exec($ci);
        curl_close($ci);

        if ( file_exists($fullpath) ) {
            unlink($fullpath);
        }

        $fp = fopen($fullpath, 'x');
        fwrite($fp, $data);
        fclose($fp);
    }

    public function getInfo()
    {

        $link = "http://www.pbase.com/wongtsushi/image/80484674&exif=Y";
        $link = "http://www.pbase.com/ingotkfr/image/132082042";
        $link = "http://www.pbase.com/ingotkfr/image/93124337";
        $link = "http://www.pbase.com/wongtsushi/image/80273259";
//$url = "http://www.pbase.com/ingotkfr/image/102977507&exif=Y";

        $url = $link . "&exif=Y";

        $data = array();

        $ele = explode("/", $url);
        $data['author'] = $ele[3];
        $data['url_context'] = $link;
        $data['url_md5'] = md5($data['url_context']);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_PROXY, 'proxy.int.world.socgen:8080');
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, "aurelien.lequoy:Zeb33tln1$");
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 5.1; rv:8.0) Gecko/20100101 Firefox/8.0");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $content = curl_exec($ch);
        curl_close($ch);

        $tab = wlHtmlDom::getTagContent($content, '<table width=0 border=0 align="center" class="imagetable">', true);
        $img = wlHtmlDom::getTagContent($tab, '<IMG');
        $elem = explode('"', $img);
        $data['url_found'] = $elem[3];

        $img_name = pathinfo($data['url_found']);
        $data['name'] = $img_name['basename'];

        get_image($data['url_found'], $data['name']);

        $info = getimagesize($data['name']);
        $data['width'] = $info[0];
        $data['height'] = $info[1];

        $data['md5'] = md5_file($data['name']);

        $title = wlHtmlDom::getTagContent($content, '<h3 class="title"', true);
        $data['title'] = trim(strip_tags($title));

        $location = wlHtmlDom::getTagContent($content, '<h3 class="location"', true);
        $data['location'] = trim(strip_tags($location));

        $legend = wlHtmlDom::getTagContent($content, '<div id="imagecaption" class="imagecaption">', true);
        $data['legend'] = trim(strip_tags($legend));

        $exif = wlHtmlDom::getTagContent($content, '<div id="techinfo" class="techinfo">', true);
        $camera = wlHtmlDom::getTagContent($exif, '<span class="camera">', true);
        $data['camera'] = trim(strip_tags($camera));

        $data_exif = wlHtmlDom::getTagContents($exif, '<tr', true);

        $hh = array();
        foreach ($data_exif as $line) {

            $dd = wlHtmlDom::getTagContents($line, '<td class=lid', true);

            if ($dd == false) {
                continue;
            }

            $hh[$dd[0]] = $dd[1];
        }

        $data['exif'] = $hh;

        echo "<pre>";
        print_r($data);
        echo "</pre>";
    }

}
