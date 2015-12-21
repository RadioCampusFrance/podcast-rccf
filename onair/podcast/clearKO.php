<?php
	$path = "./KO";
	
	$files = scanDir($path);
	
	// on garde le fichiers pendant 30 jours
	$datarchiv = date ("Y-m-d", mktime (0,0,0,date('m'),date('d')-30,date('Y')));
	echo "---".$datarchiv."</br>";
	
	$datarchiv = new DateTime( $datarchiv );
	$datarchiv = $datarchiv->format("Ymd");
			
	foreach($files as $mp3)
	{
		if(strpos($mp3,".mp3"))
		{
			$mp3_expl =explode("-",$mp3);
			$d = $mp3_expl[2];
			$m = $mp3_expl[1];
			$y = $mp3_expl[0];
			
			$date_mp3 = "{$y}-{$m}-{$d}";
			echo $date_mp3;
			

			// test
			$date_mp3 = new DateTime( $date_mp3 );
			$date_mp3 = $date_mp3->format("Ymd");
			

			if( $date_mp3 < $datarchiv )
			{
				unlink("./KO/".$mp3);
				//echo " -> supp </br>";
			}
			/*else
			{
				echo ". </br>";
			}*/
			
			
			
		}
	}

?>