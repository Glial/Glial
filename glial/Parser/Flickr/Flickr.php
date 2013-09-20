<?php

/**
 * Glial Framework
 *
 * LICENSE
 *
 *
 */
namespace Glial\Parser\Flickr;

use \Glial\Extract\Grabber;

//http://farm8.staticflickr.com/7253/8161959793_a81037254c.jpg
//http://farm8.staticflickr.com/7253/8161959793_a81037254c_s.jpg

class Flickr
{
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

    public static function curl($url)
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

    public static function getLinksToPhotos($query)
    {
        //$q = str_replace(" ", "+", $query);

        $q = urlencode($query);

        //version 1 => Flickr get tout les йlйments
        $data = array();

        for ($i = 1; $i < 67; $i++) { // 67 pages is a max of Flickr
            $url = self::$url."/search/?q=" . $q . "&s=rec&page=" . $i;

            //echo $url ."\n";
            $content = self::curl($url);
            $contents = Grabber::getTagContents($content, '<div class="photo-display-item"', true);

            if (! $contents) { //if no any photo we stop here !
                break;
            }

            foreach ($contents as $var) {
                $author = Grabber::getTagContent($var, '<a data-track="owner"', true);
                $brut_img = Grabber::getTagContent($var, '<a data-track="photo-click"', true);
                $img = Grabber::getTagAttributeValue($brut_img,"data-defer-src");
                $url = Grabber::getTagAttributeValue($var,"href");
                $width = Grabber::getTagAttributeValue($var,"width");
                $height = Grabber::getTagAttributeValue($var,"height");
                $title = Grabber::getTagAttributeValue($var,"title");

                $ret = array();
                $ret['img2']['url'] = trim($img);

                $pattern = "#(http://farm[0-9]+\.staticflickr\.com/[0-9]+/[0-9]+_[a-f0-9]+)(_[a-z]{1,2})?\.jpg#i";

                if (preg_match($pattern,trim($img), $mathes )) {
                    $ret['img']['url'] = $mathes[1]."_s.jpg";
                } else {
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

    public static function getPhotoInfo($url)
    {
        $pattern = "#^".self::$url."/photos/([a-zA-Z0-9@]+)/([0-9]*)\/#i";

        if (!preg_match($pattern, $url)) {
            die($url." did not match with REGEX : ".$pattern);
        }

        $data = array();
        $content = self::curl($url);

        $contents = Grabber::getTagContent($content, '<div id="photo', true);
        if (false === $contents) {
            return false;
        }

        $tab_id_photo = explode("/", $url);
        $data['id'] = "flickr_" . $tab_id_photo[5];
        $data['id_photo'] = $tab_id_photo[5];
        $data['url']['main'] = $url;

        $brut_canonical = Grabber::getTagContent($content, '<span class="photo-name-line-1"');
        if ($brut_canonical) {
            $tmp = Grabber::getTagAttributeValue($brut_canonical,"href");
            if (preg_match('#photos/([a-z0-9@]+)/#i',$tmp, $out)) {
                $data['id_author'] = $out[1];
            } else {

                die("Error : Impossible to get id_author\n");
                //return false;
            }
        }

        $brut_min = Grabber::getTagContent($content, '<div id="photo', true);

        $data['url']['img_z'] = Grabber::getTagAttributeValue($brut_min,"src");
        $data['legend'] = trim(Grabber::getTagContent($content, '<div id="description_div" class="photo-desc"', true));
        $data['legend'] = strip_tags(preg_replace('!\s+!', ' ', $data['legend']));

        $brut_author = trim(Grabber::getTagContent($content, '<span class="photo-name-line-1"', true));
        $data['author'] = trim(Grabber::getTagContent($brut_author, '<a', true));

        $elems = trim(Grabber::getTagContent($content, '<div id="photo-story-story"', true));

        $lis = Grabber::getTagContents($elems, '<li', true);
        foreach ($lis as $li) {
            $tmp = trim(Grabber::getTagContent($li, '<a', true));
            //echo $tmp.PHP_EOL;

            if (preg_match('/[A-Z]{1}[a-z]+ [0-9]{1,2}, [12]{1}[0-9]{3}$/i', $tmp)) {
                $data['date-taken'] = $tmp;
            }

            if (preg_match('/[a-zA-Z ]+,&nbsp;[a-zA-Z ]+,&nbsp;[a-zA-Z ]+$/i', $tmp)) {
                $data['location'] = trim(str_replace("&nbsp;", " ", $tmp));
                $data['url']['location'] = self::$url.Grabber::getTagAttributeValue($li,"href");
            }

            $tmp2 = Grabber::getTagAttributeValue($li,"href");
            if (preg_match('#^/cameras/#i', $tmp2)) {
                $data['camera'] = $tmp;
            }
        }

        $tag_brut = Grabber::getTagContent($content, '<ul id="thetags"', true);

        if ($tag_brut) {
            $tags = Grabber::getTagContents($tag_brut, '<li', true);

            $data['tag'] = array();

            foreach ($tags as $tag) {
                $data['tag'][] = Grabber::getTagContent($tag, '<a', true);
            }
        }

        $brut_license = Grabber::getTagContent($content, '<ul class="icon-inline sidecar-list', true);

        $data['license']['text'] = Grabber::getTagContents($brut_license, '<a', true)[1];
        $data['license']['url'] = Grabber::getTagAttributeValue(Grabber::getTagContents($brut_license, '<a', false)[1],"href");

        //print_r($brut_license);

        $brut_exif = Grabber::getTagContent($content, '<a id="exif-details"');
        $data['url']['exif'] = self::$url.Grabber::getTagAttributeValue($brut_exif,"href");

        $data['url']['all_size'] = self::$url."/photos/".$data['id_author']."/".$data['id_photo']."/sizes/sq/";

        $brut_latitude = Grabber::getTagContent($content, '<meta property="flickr_photos:location:latitude"', false);
		
		if ($brut_latitude) {
            $data['gps']['latitude'] = Grabber::getTagAttributeValue($brut_latitude,"content");
        }

        $brut_latitude = Grabber::getTagContent($content, '<meta property="flickr_photos:location:longitude"', false);
        if ($brut_latitude) {
            $data['gps']['longitude'] = Grabber::getTagAttributeValue($brut_latitude,"content");
        }

        $data['img'] = self::get_all_size($data['url']['all_size']);

        if (! empty($data['url']['exif'])) {
            $data['exif'] = self::get_photo_exif($data['url']['exif']);
        }

        return $data;
    }

    public static function get_photo_exif($url)
    {
        $data = array();

        $content = self::curl($url);

        $content = Grabber::getTagContent($content, '<div class="photo-data"', true);
        $tab = Grabber::getTagContents($content, '<h2', true);
        $tab2 = Grabber::getTagContents($content, '<table cellspacing="0" cellpadding="0" width="100%">', false);

        $i = 0;
        foreach ($tab as $elem) {
            $tr = Grabber::getTagContents($tab2[$i], '<tr', true);
            foreach ($tr as $var) {
                $th = trim(strip_tags(Grabber::getTagContent($var, '<th', true)));
                $td = Grabber::getTagContent($var, '<td', true);

                $data[$tab[$i]][$th] = trim(strip_tags(str_replace("<br />", " - ", str_replace("\n", "", $td))));
            }
            $i++;
        }

        return $data;
    }

    public static function fileExists($path)
    {
        return (fopen($path, "r") == true);
    }

    public static function dl_photo($url)
    {
        $cmd = "cd " . TMP . "photos_in_wait/; wget -nc " . $url . "";
        shell_exec($cmd);
    }

    public static function get_photo_id($url)
    {
        $tab = explode("/", $url);

        return "flickr_" . $tab[5];
    }

    public static function get_all_size($url)
    {
        $content = self::curl($url);
        $keys = explode('/',$url);

        $lis = Grabber::getTagContent($content, '<ol class="sizes-list"', true);
        if ($lis) {
            $pattern = '#/'.$keys[3].'/'.$keys[4].'/'.$keys[5].'/'.$keys[6].'/([a-z]{1,2})/#i';
            preg_match_all($pattern, $lis, $matches);

            $tmp['size_available'] = $matches[1];

            foreach ($tmp['size_available'] as $size) {
                if (in_array($size, self::$allowed)) {
                    $tmp['best'] = $size;
                }
            }

            if (empty($tmp['best'])) {
                return false;
            }

            $brut_url = Grabber::getTagContent($content, '<div id="allsizes-photo"', true);
            $img = Grabber::getTagAttributeValue($brut_url,"src");

            $tmp['url']['img'] = str_replace("_s.jpg",  self::$size[$tmp['best']].".jpg",$img);

        } else {
            return false;
        }

        return $tmp;
    }
}
