<?php

class img
{

	function crop_image2($source,$destination,$sizeouput=0,$x1=0,$y1=0,$x2=0,$y2=0)
	{
		list($width, $height, $type, $attr) = getimagesize($source);
		
		if (empty($x1) && empty($x2) && empty($x2) && empty($y2))
		{
			$x1 = 0;
			$y1 = 0;
			$x2 = $width;
			$y2 = $height;
		}
		
		// Avec création d'une miniature
		$command_magick = "/usr/local/bin/convert +profile \"*\" $source -quality 100 -crop ".$x1."x".$y1."+".$x2."+".$y2." +repage +resize 100x100 $destination";
		
		echo $command_magick;
		
		exec($command_magick);

		// Sans création d'une miniature
		//$command_magick = "/usr/local/bin/convert +profile \"*\" adresse-de-votre-image.jpg -quality 100 -crop ".$_POST['ex']."x".$_POST['ey']."+".$_POST['sx']."+".$_POST['sy']." +repage adresse-de-votre-image-mini.jpg";
		//exec($command_magick);
	}
	
	
	function crop_image($source,$destination,$sizeouput=0,$x1=0,$y1=0,$x2=0,$y2=0)
	{
		$debug =2;
		
		list($WidthImgSource, $HeightImgSource, $typeImgSource, $attr) = getimagesize($source);
		
		IF ($debug ==1) echo "<br />width: $WidthImgSource<br />height: $HeightImgSource<br />type: $typeImgSource";
		
		
		if (empty($x1) && empty($x2) && empty($x2) && empty($y2))
		{
			$cropStartX = 0;
			$cropStartY = 0;
			$cropW = $WidthImgSource;
			$cropH = $HeightImgSource;
		}
		else
		{
			$cropStartX = $x1;
			$cropStartY = $y1;
			$cropW = $x2-$x1;
			$cropH = $y2-$y1  ;
		}		
		
		IF ($debug ==1) echo "<br />cropStartX: $cropStartX		<br />cropStartY: $cropStartY		<br />cropW: $cropW		<br />cropH: $cropH";
		
		//définition des variables
		$imgfile = $source;
		
		
		// création  image temporaire
		switch($typeImgSource)
		{
			case 1: $origimg = imagecreatefromgif($imgfile); break;
			case 2: $origimg = imagecreatefromjpeg($imgfile);break;
			case 3: $origimg = imagecreatefrompng($imgfile); break;
			case 0: $origimg = imagecreatefrombmp($imgfile); break;
		}		


		// résolution de l'image de destination
		
		$crop = explode ("x",$sizeouput);

		IF ($debug ==1) echo "<br />".$crop[0]."x".$crop[1]."<br />";
		
		if (empty($crop[0]) && !empty($crop[1])) 
		{
			if ($crop[1] < $HeightImgSource)
			{
				$crop[0] = ceil($WidthImgSource / $HeightImgSource * $crop[1]);
				IF ($debug ==1) echo "1111111111111111!!!!!!!!!!!!!!!!!!";
			}
			else
			{
				$crop[1] = $HeightImgSource;
				$crop[0] = $WidthImgSource;
				IF ($debug ==1) echo "222222222222!!!!!!!!!!!!!!!!!!";
			}
		}
		
		if (empty($crop[1]) && !empty($crop[0]))
		{
			
			IF ($debug ==1) echo "{$crop[0]} > $WidthImgSource<br/>";
			
			
			if ($crop[0] < $WidthImgSource)
			{
				$crop[1] = ceil($HeightImgSource / $WidthImgSource * $crop[0]);
				
				
				IF ($debug ==1) echo "33333333333!!!!!!!!!!!!!!!!";
			}
			else
			{
				$crop[1] = $HeightImgSource;
				$crop[0] = $WidthImgSource;
				IF ($debug ==1) echo "4444444444!!!!!!!!!!!!!!!";
			}
		}
		
		
		IF ($debug ==1) echo "<br />".$crop[0]."xxx".$crop[1]."";

		//$cropimg = imagecreatetruecolor($cropW,$cropH);
		$cropimg = imagecreatetruecolor($crop[0],$crop[1]);
		
		
		IF ($debug ==1) echo "$cropimg, $origimg,0, 0, $cropStartX, $cropStartY,$crop[0],$crop[1], $cropW,$cropH";
		
		
		// obliger de le faire en 2 fois pour un meilleu qualité !
		//imagecopyresized($cropimg, $origimg,0, 0, $cropStartX, $cropStartY, $cropW,$cropH, $cropW,$cropH);
		imagecopyresampled($cropimg, $origimg, 0, 0, $cropStartX, $cropStartY,$crop[0],$crop[1], $cropW, $cropH); //amélioration en un seul passage grace à l'extrapolation
		
		imagejpeg($cropimg, $destination,100);


		IF ($debug ==1) echo "<br /><img src=\"$destination\" /><br />";

	}
	
	
	
	
	
	
	
}



?>