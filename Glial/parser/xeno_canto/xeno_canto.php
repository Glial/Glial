<?php

class xeno_canto {

	static function get_all_link() {
		$url = "http://www.xeno-canto.org/all_species.php";

		$file = file_get_contents("/var/www/xc/all_species.php.htm");

		echo "start OK !\n";
		$contents = wlHtmlDom::getTagContent($file, '<table class="results', true);

		echo "contents OK !\n";
		
		$line = wlHtmlDom::getTagContents($contents, '<tr>', true);

		echo "line OK !\n";
		
		if ($line)
		{
			$data = array();
			$i = 0;

			foreach ($line as $row)
			{
				$i++;
				echo "line : ".$i."\n";

				if ($i < 3)
				{
					continue;
				}

				$cell = wlHtmlDom::getTagContents($row, '<td', true);

				$tab = explode('href="', $cell[0]);
				$tab = explode('"', $tab[1]);

				$species = array();
				$species['url'] = $tab[0];
				$species['scientific_name'] = $cell[1];
				$species['name_en'] = wlHtmlDom::getTagContent($cell[0], '<a', true);
				$species['foreground'] = $cell[2];
				$species['background'] = $cell[3];

				$data[] = $species;

			}
		}
		else
		{
			return false;
		}

		
		return $data;
	}

}