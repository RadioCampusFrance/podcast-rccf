<!DOCTYPE html>
<html prefix="og: http://ogp.me/ns#">
<head>
<meta charset=utf-8 />

<link rel="icon" type="image/png" href="favicon.ico" />
<?php

include 'configPaulo.php';


	$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

	try {
		$bdd = new PDO('mysql:host='._PAULODB_SERVEUR.';dbname='._PAULODB_BD, _PAULODB_LOGIN, _PAULODB_MDP, $options);
	}
	catch (PDOException $Exception ) {
	$bdd = null;
	//exit("Des problèmes techniques nous empêchent temporairement de vous proposer les podcasts... Veuillez nous excuser pour la gêne occasionnée.");
	}

	function get_paulo_entries_from_file($filename) {
	  return unserialize(file_get_contents($filename));
	}
	
	function save_paulo_entries_from_file($file_logs, $entries) {
	  $string = serialize($entries);
	  
	  $fh = fopen($file_logs, 'w');
	  if ($fh === false)
	    return false;
	  fwrite($fh, $string);
	  fclose($fh);
	  
	  return true;
	}
	
        function nb_day_from_today($mdate) {
          $now = time(); // or your date as well
          $your_date = strtotime($mdate);
          $datediff = $now - $your_date;
          return floor($datediff/(60*60*24));

          
        }
	
function convert_paulo_entries($date, $duration = 1) {
	global $bdd;
	
	$result = array();
	if ($bdd == null)
	  return $result;

	$fullhour = "" .$hour;
	if (strlen($fullhour) == 1)
	  $fullhour = "0" . $fullhour;
	$datetimebegin = $date . " " . "00:00:00";
	$datetimeend = $date . " " . "23:59:59";

        if (nb_day_from_today($date) < 7)
          return true;
	
        $file_logs = "paulo/" . $date .".txt";
	  if (file_exists($file_logs)) {
            $sql = "DELETE From titres_paulo where (begin >= ".$bdd->quote($datetimebegin)." and begin <= ".$bdd->quote($datetimeend).") or (end >= ".$bdd->quote($datetimebegin)." and end <= ".$bdd->quote($datetimeend).");";
            echo $sql."<br/>";
            $prep = $bdd->query($sql);
            $prep->execute();
	    return false;
	  }
	
	
	$sql = "Select * From titres_paulo where (begin >= ".$bdd->quote($datetimebegin)." and begin <= ".$bdd->quote($datetimeend).") or (end >= ".$bdd->quote($datetimebegin)." and end <= ".$bdd->quote($datetimeend).") order by begin;";
	
	$prep = $bdd->query($sql);
	
	$prep->execute();
	for($i=0; $row = $prep->fetch(); $i++){
	  $subtime = explode(" ", $row["begin"]);
	  $subhour = explode(":", $subtime[1]);
	  $result[$subhour[0]][] = array("title" => $row["title"], "author" => $row["author"], "time" => $subtime[1]);
	}

	if ($file_logs != "") {
	  if (save_paulo_entries_from_file($file_logs, $result)) {
	    /*$sql = "DELETE From titres_paulo where (begin >= ".$bdd->quote($datetime)." and begin <= DATE_ADD(".$bdd->quote($datetime).", INTERVAL ".$duration." HOUR)) or (end >= ".$bdd->quote($datetime)." and end <= DATE_ADD(".$bdd->quote($datetime).", INTERVAL ".$duration." HOUR));";
	    $prep = $bdd->query($sql);
	    $prep->execute();*/
	  }
	}
	
	return true;
}
?>

<html>
  <head>
  </head>
  <body>
    <?php
      $dateDeb = new DateTime('2014-05-24');  
      $dateFin = new DateTime();
 
  $date = '';  
  while ($dateDeb -> format('Y-m-d') <= $dateFin -> format('Y-m-d'))
  {
    if (!convert_paulo_entries($dateDeb->format('Y-m-d'))) 
       echo "Données supprimées " . $dateDeb->format('Y-m-d') . " <br/>";
    else
      echo "Conversion pour ". $dateDeb->format('Y-m-d') . " ok <br/>";
    $dateDeb -> modify('+1 day');
  }
    ?>
  </body>
</html>