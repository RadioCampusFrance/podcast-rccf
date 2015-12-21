<?php
	$path = "./";
	
	$files = scanDir($path);
	
	foreach($files as $mp3)
	{
		if(strpos($mp3,".mp3"))
		{
		 $taille = filesize($mp3);
		 usleep(500000);
		 $taille2 = filesize($mp3);
		
		 if ($taille == $taille2) {
			$mp3_expl =explode("-",$mp3);
			$d = $mp3_expl[2];
			$m = $mp3_expl[1];
			$y = $mp3_expl[0];
			$h = $mp3_expl[3][0].$mp3_expl[3][1];
			//echo "http://campus-clermont.net/ws/index.php?req=onair&y={$y}&m={$m}&d={$d}&h={$h}";
			
			$nom_rep = "{$y}-{$m}-{$d}"; // Le nom du répertoire à créer

			// vérifie si le répertoire existe :
			if (!file_exists("./OK/".$nom_rep)) {
				mkdir("./OK/".$nom_rep);
				$fp=fopen("./OK/".$nom_rep."/config.txt",'w');
				fclose($fp);
			}
		
			$file_day = "./OK/".$nom_rep."/config.txt";
			$jsonDay = json_decode(file_get_contents($file_day));

			$jsonObject = json_decode(file_get_contents("http://campus-clermont.net/ws/index.php?req=onair&y={$y}&m={$m}&d={$d}&h={$h}"));
			$emission = $jsonObject->type=="emission";
			if (!$emission) {
			    // on refait le test avec mn=30 pour vérifier qu'il n'y a rien à diffuser à 30 (typiquement starting bloc)
			    $jsonObject = json_decode(file_get_contents("http://campus-clermont.net/ws/index.php?req=onair&y={$y}&m={$m}&d={$d}&h={$h}&mn=30"));
			    $emission = $jsonObject->type=="emission";
			  }
	
			if($emission)
			{
				echo "OK";
				if ($jsonObject->podcastable==true) {
				  $jsonDay->track[] = array("mp3"=>$mp3,"time"=>$h,"title"=>$jsonObject->titre, "url"=>$jsonObject->url);
				  file_put_contents($file_day, json_encode($jsonDay));
				  rename($mp3,"./OK/".$nom_rep."/".$mp3);
				  }
				else {
				  $jsonDay->track[] = array("mp3"=>"","time"=>$h,"title"=>$jsonObject->titre, "url"=>$jsonObject->url);
				  file_put_contents($file_day, json_encode($jsonDay));
				  rename($mp3,"./KO/".$mp3);
				}
			}
			else
			{
				echo "KO";
				rename($mp3,"./KO/".$mp3);
			}
			
			echo "</br>";
			
		}
		}
	}

?>