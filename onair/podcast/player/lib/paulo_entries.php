<?php

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
	
	function filterbyhour($array, $hour, $duration) {
            $res = array();
            for($i = 0; $i < $duration; ++$i) {
              $nb = "".($hour + $i);
              if (strlen($nb) == 1)
                $nb = "0" . $nb;
               if (is_array($array[$nb]))
                $res = array_merge($res, $array[$nb]);
            }
            return $res;
	}
	
function get_paulo_entries($date, $hour, $bdd, $path, $duration = 1) {
	
	$result = array();
	if ($bdd == null)
	  return $result;

	$fullhour = "" .$hour;
	if (strlen($fullhour) == 1)
	  $fullhour = "0" . $fullhour;
	$datetime = $date . " " . $fullhour . ":00:00";

	$file_logs = "";
	if (nb_day_from_today($date) > 1) {
	  $file_logs = $path . "/paulo/" . $date .".txt";
	  if (file_exists($file_logs)) {
	    $array = get_paulo_entries_from_file($file_logs);
	    return filterbyhour($array, $hour, $duration);
	    
	  }
	  else {
	        $datetimebegin = $date . " " . "00:00:00";
              $datetimeend = $date . " " . "23:59:59";

            $sql = "Select * From titres_paulo where (begin >= ".$bdd->quote($datetimebegin)." and begin <= ".$bdd->quote($datetimeend).") or (end >= ".$bdd->quote($datetimebegin)." and end <= ".$bdd->quote($datetimeend).") order by begin;";
        
            $prep = $bdd->query($sql);
        
          $prep->execute();
          for($i=0; $row = $prep->fetch(); $i++){
            $subtime = explode(" ", $row["begin"]);
            $subhour = explode(":", $subtime[1]);
            $result[$subhour[0]][] = array("title" => $row["title"], "author" => $row["author"], "time" => $subtime[1]);
          }

          if (save_paulo_entries_from_file($file_logs, $result)) {
            
            $sql = "DELETE From titres_paulo where (begin >= ".$bdd->quote($datetimebegin)." and begin <= ".$bdd->quote($datetimeend).") or (end >= ".$bdd->quote($datetimebegin)." and end <= ".$bdd->quote($datetimeend).");";
            $prep = $bdd->query($sql);
            $prep->execute();
	  }
	  return filterbyhour($result, $hour, $duration);
	  }
	}
	
	$sql = "Select * From titres_paulo where (begin >= ".$bdd->quote($datetime)." and begin <= DATE_ADD(".$bdd->quote($datetime).", INTERVAL ".$duration." HOUR)) or (end >= ".$bdd->quote($datetime)." and end <= DATE_ADD(".$bdd->quote($datetime).", INTERVAL ".$duration." HOUR)) order by begin;";
	
	$prep = $bdd->query($sql);
	
	$prep->execute();
	for($i=0; $row = $prep->fetch(); $i++){
	  $subtime = explode(" ", $row["begin"]);
	  $result[] = array("title" => $row["title"], "author" => $row["author"], "time" => $subtime[1]);
	}


	
	return $result;
}

?>