<?php

namespace Glial\Parser\Google;

use Glial\Curl\Curl;
use Glial\Extract\Grabber;

class Google
{

    public function search($website, $string)
    {
        $string = urlencode($string);
        $website = urlencode($website);

        //old => http://www.google.fr/search?q=site:www.estrildidae.net%2Ffr%2F+lonchura&hl=en&hs=Dqa&filter=0&num=50
        // new => https://www.google.fr/search?q=site:www.estrildidae.net%2Ffr%2F+lonchura&amp;hl=en&amp;hs=Dqa&amp;filter=0&amp;num=50

        $url = "https://www.google.fr/search?q=site:" . $website . "+" . $string . "&hl=en&hs=Dqa&filter=0";

        
        //echo $url;
        //for look page 2 => add &start=10
        //we can only have 10 result by 10

        $data = Curl::get($url);

        //echo htmlentities($data);
       
        $content = Grabber::getTagContent($data,'<div data-jibp="h" data-jiis="uc" id="search"', true);

        //echo $content;
        //echo htmlentities($content);
        
        
        $search = [];

        if ($content) {
            $list_li = Grabber::getTagContents($data,'<div class="g">', true);

            //print_r($list_li);

            $i =0;
            foreach ($list_li as $li) {

                //echo htmlspecialchars($li);
                //echo "<br />-*--<br />";
                $a = Grabber::getTagContent($li,'<h3', true);
                $search[$i]['URL'] = (string) Grabber::getTagAttributeValue($a, 'href');
                $search[$i]['Title'] = strip_tags($a,"<b>");
                $search[$i]['Data'] = Grabber::getTagContent($li,'<span class="st">', true);
                $search[$i]['Cite'] = Grabber::getTagContent($li,'<cite>', true);

                $i++;
            }

            return $search;
        } else {
            //echo "content not found";
        }
    }

}
