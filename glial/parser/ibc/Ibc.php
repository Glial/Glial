<?php

namespace glial\parser\ibc;

class Ibc
{

    public static function getSpeciesFromFamily($famliy = "waxbills-estrildidae")
    {
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
        if (false === $content2) {
            return false;
        }
        $tab_li = \wlHtmlDom::getTagContents($content2, '<li', true);

        foreach ($tab_li as $li) {
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

    public static function getOrderAndFamily()
    {

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

        foreach ($data_order as $order) {
            $ordername = \wlHtmlDom::getTagContent($order, '<a>', true);
            $li = \wlHtmlDom::getTagContents($order, '<ul style="display: none;">', true);

            foreach ($li as $family) {
                $english = \wlHtmlDom::getTagContent($family, '<strong>', true);
                $url = explode('"', $family);
                preg_match('/\((.*?)\)/', $family, $match);
                $data[$ordername][$match[1]]['url'] = "http://ibc.lynxeds.com" . $url[1];
                $data[$ordername][$match[1]]['english'] = $english;
            }
        }

        return $data;
    }

    public static function getPhotoAndInfos($picture_link)
    {
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
        $data['recorded'] = date("Y-m-d", strtotime(\wlHtmlDom::getTagContent($content, '<span class="date-display-single">', true)));
        $data['uploaded'] = date("Y-m-d", strtotime(\wlHtmlDom::getTagContent($content, '<em>', true) . 'ago'));
        $author = explode('<a href="/users/', $content);
        $author = explode('</a>', $author[1]);
        $author = explode('">', $author[0]);
        $data['author'] = $author[1];
        $data['author_profile_link'] = 'http://ibc.lynxeds.com/users/' . $author[0];
        $locations = explode('<span class="location">Locality</span>', $content);
        $locations = explode('</li>', $locations[1]);
        $locations = explode(',', $locations[0]);
        $i = 0;
        while ( isset($locations[$i]) ) {
            $line = $locations[$i];
            $line = explode('<a href="', $line);
            $line = explode('</a>', $line[1]);
            $tmp = explode('">', $line[0]);
            if ( !(empty($line[1])) )
                $plus = $line[1];
            else
                $plus = '';
            $localised[$i]['Location'] = trim($tmp[1] . ' ' . $plus);
            $localised[$i]['Url'] = trim('http://ibc.lynxeds.com' . $tmp[0]);
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

    public static function getVideoAndInfos($video_link)
    {
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
        $data['recorded'] = date("Y-m-d", strtotime(\wlHtmlDom::getTagContent($content, '<span class="date-display-single">', true)));
        $data['uploaded'] = date("Y-m-d", strtotime(\wlHtmlDom::getTagContent($content, '<em>', true) . 'ago'));
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
        while ( isset($locations[$i]) ) {
            $line = $locations[$i];
            $line = explode('<a href="', $line);
            $line = explode('</a>', $line[1]);
            $tmp = explode('">', $line[0]);
            if ( !(empty($line[1])) )
                $plus = $line[1];
            else
                $plus = '';
            $localised[$i]['Location'] = trim($tmp[1] . ' ' . $plus);
            $localised[$i]['Url'] = trim('http://ibc.lynxeds.com' . $tmp[0]);
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

    public static function getSoundAndInfos($sound_link)
    {
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
        $data['recorded'] = date("Y-m-d", strtotime(\wlHtmlDom::getTagContent($content, '<span class="date-display-single">', true)));
        $data['uploaded'] = date("Y-m-d", strtotime(\wlHtmlDom::getTagContent($content, '<em>', true) . 'ago'));
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
        while ( isset($locations[$i]) ) {
            $line = $locations[$i];
            $line = explode('<a href="', $line);
            $line = explode('</a>', $line[1]);
            $tmp = explode('">', $line[0]);
            if ( !(empty($line[1])) )
                $plus = $line[1];
            else
                $plus = '';
            $localised[$i]['Location'] = trim($tmp[1] . ' ' . $plus);
            $localised[$i]['Url'] = trim('http://ibc.lynxeds.com' . $tmp[0]);
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

    public static function getSpeciesInfosAndLinks($link)
    {
        $url = "http://ibc.lynxeds.com/species/" . $link;

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
        $content2 = \wlHtmlDom::getTagContent($content, '<h1>', true);
        $content2 = explode('(<i>', $content2);
        $data['language']['English'] = $content2[0];
        $content2 = explode('</i>)', $content2[1]);
        $data['sientific_name'] = $content2[0];
        $content2 = \wlHtmlDom::getTagContent($content, '<p class="other-languages">', true);
        $content2 = explode('<br/> <strong>Other common names:</strong> ', $content2);
        $others = $content2[1];
        $others = explode(';', $others);
        $others = explode(',', $others[0]);
        $data['Other common names'] = $others;
        $content2 = explode('<strong>', $content2[0]);
        $i = 0;
        while ( isset($content2[$i]) ) {
            $line = explode(':</strong>', $content2[$i]);
            $data['language'][$line[0]] = $line[1];
            $i++;
        }
        $content2 = \wlHtmlDom::getTagContent($content, '<div class="hovertip" id="all_tax">', true);
        $original = explode('<i>', $content2);
        $original = explode('</i>', $original[1]);
        $data['original_scientific_name'] = $original[0];
        $content2 = explode('<i>' . $data['original_scientific_name'] . '</i>', $content2);
        $data['taxonomy'] = $content2[1];
        /* $content2 = \wlHtmlDom::getTagContent($content, '<div class="hovertip" id="all_ssp"><div class=\'view view-subspecies-in-species\'><div class=\'view-content view-content-subspecies-in-species\'><div class="item-list"><ul>', true);
          $content2 = explode('<li>', $content2);
          $data['subspecies'] = $content2; */ //Petite correction au niveau des subspecies

        return ($data);
    }

    public static function getUserInfos($link)
    {
        $url = "http://ibc.lynxeds.com/users/" . $link;

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
        $pic = explode('"', \wlHtmlDom::getTagContent($content, '<div id="image">', true));
        $data['user_picture_url'] = 'http://ibc.lynxeds.com' . $pic[1];
        $name = explode('|', \wlHtmlDom::getTagContent($content, '<title>', true));
        $data['username'] = trim($name[0]);
        $data['from'] = trim(str_replace('<br>', ', ', \wlHtmlDom::getTagContent($content, '<h4>', true)));
        $locations = \wlHtmlDom::getTagContent($content, '<div class="puntitos-block">');
        $locations = str_replace('View all the localities on a map!', '', $locations);
        $locations = explode('from:', $locations);
        $data['contributions_locations'] = explode('·', trim(strip_tags($locations[1])));
        $infos = \wlHtmlDom::getTagContent($content, '<div class="info">', true);
        $infos = explode('</p>', $infos);
        $infos = explode('<strong>', $infos[2]);
        $data['biography'] = trim(strip_tags($infos[0]));
        $data['first_posted'] = date('Y-m-d', strtotime(\wlHtmlDom::getTagContent(\wlHtmlDom::getTagContent($content, '<li id="first-posted">', true), '<span>', true)));
        $videos_posted = \wlHtmlDom::getTagContent($content, '<li id="videos-posted">', true);
        $videos_posted = explode('<span>', str_replace('</span>', '', $videos_posted));
        $total = explode(' ', $videos_posted[1]);
        $data['videos_posted']['total'] = $total[0];
        $covered = explode(' ', $videos_posted[2]);
        $data['videos_posted']['species_covered'] = $covered[0];
        $videos_posted = preg_split('#[\(\)]#', $videos_posted[3]);
        $data['videos_posted']['species_percentage'] = $videos_posted[1];
        $images_posted = \wlHtmlDom::getTagContent($content, '<li id="images-posted">', true);
        $images_posted = explode('<span>', str_replace('</span>', '', $images_posted));
        $total = explode(' ', $images_posted[1]);
        $data['images_posted']['total'] = $total[0];
        $covered = explode(' ', $images_posted[2]);
        $data['images_posted']['species_covered'] = $covered[0];
        $images_posted = preg_split('#[\(\)]#', $images_posted[3]);
        $data['images_posted']['species_percentage'] = $images_posted[1];
        $sounds_posted = \wlHtmlDom::getTagContent($content, '<li id="sounds-posted">', true);
        $sounds_posted = explode('<span>', str_replace('</span>', '', $sounds_posted));
        $total = explode(' ', $sounds_posted[1]);
        $data['sounds_posted']['total'] = $total[0];
        $covered = explode(' ', $sounds_posted[2]);
        $data['sounds_posted']['species_covered'] = $covered[0];
        $sounds_posted = preg_split('#[\(\)]#', $sounds_posted[3]);
        $data['sounds_posted']['species_percentage'] = $sounds_posted[1];

        return ($data);
    }

}
