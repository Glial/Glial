<?php

namespace Glial\Parser\LinkedIn;

use Glial\Extract\Grabber;

class LinkedIn
{

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

    public static function getExperience($generate_url)
    {
        $content = self::curl($generate_url);
        
        
        $content = Grabber::getTagContent($content, '<div class="section subsection-reorder" id="profile-experience" style="display:block">', true);

        $experiences = Grabber::getTagContents($content, '<div class="position', true);
       
        $nb_exp = count($experiences);
        
        for($i = 0; $i < $nb_exp; $i++)
        {
            $to_del = Grabber::getTagContent($experiences[$i], '<p class="orgstats', false);
            
            $experiences[$i] = str_replace($to_del, '', $experiences[$i]);
        }
        
        
        return $experiences;
    }

}
