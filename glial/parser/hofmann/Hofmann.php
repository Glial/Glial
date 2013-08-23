<?php

/**
 * Glial Framework
 *
 * LICENSE
 *
 *
 */

namespace glial\parser\hofmann;

class Hofmann
{
    /*
     * $url = "http://www.flickr.com/photos/maholyoak/5847734660/meta/";
      $url = "http://www.hofmann-photography.de/index/02_estrild/index_estrild/asia/";
      $url = "http://www.hofmann-photography.de/index/02_estrild/index_estrild/afrika/";
      //$url = "http://www.hofmann-photography.de/index/02_estrild/index_estrild/gould_pa/";
      //$url = "http://www.hofmann-photography.de/index/02_estrild/index_estrild/australia/";
      //$url = "http://www.hofmann-photography.de/index/02_estrild/index_estrild/mouthmark/";
      //$url = "http://www.hofmann-photography.de/index/0000Wellenastrild/";
     */

    public function get_photos($url)
    {
        $data = array();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_PROXY, 'proxy.int.world.socgen:8080');
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, "aurelien.lequoy:Zeb33tln1$");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $content = curl_exec($ch);
        curl_close($ch);

        $tab = wlHtmlDom::getTagContents($content, '<div class="thumbnail_container">', true);

        foreach ($tab as $photo) {

            $tmp1 = wlHtmlDom::getTagContent($photo, '<a', true);

            //echo $tmp1."\n";
            $pic = explode('"', $tmp1);
            //$exp['pic'] = $url.str_replace("thumbnails","photos",$pic[1]);

            preg_match('/thumbnails[^\s]*jpg/i', $tmp1, $match);
            $exp['pic'] = $url . str_replace("thumbnails", "photos", $match[0]);

            $exif = wlHtmlDom::getTagContent($photo, 'class="highslide-heading hs_text"', true);
            $exp['exif'] = trim(wlHtmlDom::getTagContent($exif, '<p', true));

            preg_match_all("/\((.*)\)/i", $pic[7], $out, PREG_PATTERN_ORDER);

            if (isset($out[1][0])) {

                $out[1][0] = str_replace("), (", "$", $out[1][0]);
                $out[1][0] = str_replace(") (", "$", $out[1][0]);
                $out[1][0] = str_replace(", (", "$", $out[1][0]);
                $out[1][0] = str_replace("),(", "$", $out[1][0]);

                if (strstr($out[1][0], "$")) {
                    $tmp = explode("$", $out[1][0]);
                    foreach ($tmp as $val) {
                        $exp['name'][] = trim($val);
                    }
                } else {

                    $exp['name'][] = trim($out[1][0]);
                }
            } else {
                $tmp2 = wlHtmlDom::getTagContent($photo, '<div class="image_info_sub">', true);
                $tmp2 = wlHtmlDom::getTagContent($tmp2, '<p>', true);

                preg_match_all("/\((.*)\)/i", $tmp2, $out, PREG_PATTERN_ORDER);

                if (isset($out[1][0])) {

                    $out[1][0] = str_replace("), (", "$", $out[1][0]);
                    $out[1][0] = str_replace(") (", "$", $out[1][0]);
                    $out[1][0] = str_replace(", (", "$", $out[1][0]);
                    $out[1][0] = str_replace("),(", "$", $out[1][0]);

                    if (strstr($out[1][0], "$")) {
                        $tmp = explode("$", $out[1][0]);
                        foreach ($tmp as $val) {
                            $exp['name'][] = trim($val);
                        }
                    } else {
                        $exp['name'][] = trim($out[1][0]);
                    }
                }
            }

            if (!empty($exp['name'])) {
                $data[] = $exp;
            } else {
                $error[] = $exp;
            }
            unset($exp);

            //preg_match('/\([a-ZA-Z]\ [a-ZA-Z]\)/i',$tmp1,$match);
            //preg_match('/\((.*)\)/',$tmp1,$match);
            //$data[] = $match[0];
        }
    }

}
