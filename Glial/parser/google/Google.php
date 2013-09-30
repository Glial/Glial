<?php

namespace glial\parser\google;

use glial\curl\Curl;
use glial\extract\HtmlDom;

class Google
{

    public function search($website, $string)
    {
        $string = urlencode($string);
        $website = urlencode($website);

        //old => http://www.google.fr/search?q=site:www.estrildidae.net%2Ffr%2F+lonchura&hl=en&hs=Dqa&filter=0&num=50
        // new => https://www.google.fr/search?q=site:www.estrildidae.net%2Ffr%2F+lonchura&amp;hl=en&amp;hs=Dqa&amp;filter=0&amp;num=50

        $url = "https://www.google.fr/search?q=site:" . $website . "+" . $string . "&hl=en&hs=Dqa&filter=0";

        //for look page 2 => add &start=10
        //we can only have 10 result by 10

        $data = Curl::get($url);

        $content = HtmlDom::getTagContent($data,'<div id="search"', true);

        if ($content) {
            $list_li = HtmlDom::getTagContents($data,'<li class="g">', true);

            $i =0;
            foreach ($list_li as $li) {

                //echo htmlspecialchars($li);
                //echo "<br />-*--<br />";
                $a = HtmlDom::getTagContent($li,'<h3', true);
                $search[$i]['URL'] = (string) HtmlDom::getTagAttributeValue($a, 'href');
                $search[$i]['Title'] = strip_tags($a,"<b>");
                $search[$i]['Data'] = HtmlDom::getTagContent($li,'<span class="st">', true);
                $search[$i]['Cite'] = HtmlDom::getTagContent($li,'<cite>', true);

                $i++;
            }

            return $search;
        } else {
            //echo "content not found";
        }
    }

}
