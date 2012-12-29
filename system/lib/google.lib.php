<?php




function simpleXMLToArray($obj, &$arr)
{
    $children = $obj->children();
    foreach ($children as $elementName => $node)
    {
        $nextIdx = count($arr);
        $arr[$nextIdx] = array();
        $arr[$nextIdx]['@name'] = strtolower((string)$elementName);
        $arr[$nextIdx]['@attributes'] = array();
        $attributes = $node->attributes();
        foreach ($attributes as $attributeName => $attributeValue)
        {
            $attribName = strtolower(trim((string)$attributeName));
            $attribVal = trim((string)$attributeValue);
            $arr[$nextIdx]['@attributes'][$attribName] = $attribVal;
        }
        $text = (string)$node;
        $text = trim($text);
        if (strlen($text) > 0)
        {
            $arr[$nextIdx]['@text'] = $text;
        }
        $arr[$nextIdx]['@children'] = array();
        simpleXMLToArray($node, $arr[$nextIdx]['@children']);
    }
    return;
}  



class google
{	
	function search($website, $string)
	{
		$string = urlencode($string);
		$website = urlencode($website);  
		
		$data = file_get_contents("http://www.google.fr/search?q=site:".$website."+".$string."&hl=en&hs=Dqa&filter=0&num=50");
	
		
		$data = iconv("ISO-8859-1","UTF-8",$data);
	
		if (preg_match("!<ol>(.*)</ol>!Ui",$data,$matches))
		{
			$tab = $matches[1];
			
			$tab = strip_tags($tab,"<h3><a><li><div><span>");
			$tab = "<ol>$tab</ol>";
			
			$tab= str_replace("&nbsp;","",$tab);
			$tab=str_replace("\r\n","",$tab);
			$tab=str_replace("\n","",$tab);

			$config = array('indent' => false,   'output-xhtml' => TRUE);
			$tidy = tidy_parse_string($tab, $config, 'UTF8');
			//$tidy = tidy_clean_repair($tidy);
			//echo $tidy;
			
			$obxml = simplexml_load_string($tidy);


			$i=0;
			foreach($obxml->body->ol->li as $li)
			{
	
				//echo $li->h3->a['href']."<br />";
				//iconv  ( string $in_charset  , string $out_charset  , string $str  )
				
				$search[$i]['Title'] = str_replace("\n"," ",(string)$li->h3->a);
				$search[$i]['URL'] = (string)$li->h3->a['href'];
				
				$search[$i]['URL'] = str_replace("/url?q=","",$search[$i]['URL'] );
				
				$data = explode("&", $search[$i]['URL']);
				$search[$i]['URL'] = $data[0];
				
				
				$search[$i]['Data'] = str_replace("\n"," ",(string)$li->div);
				
				$i++;
			}
			return $search;
		}
	}
	
	

}







?>