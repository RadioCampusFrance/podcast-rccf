<?php
include 'outilEtRequete.php';
header("Content-Type: 'text/html'; charset=utf8");
$data = RestUtils::processRequest();  


try
{	

	$requete = $_GET['action'];


	switch($requete)
	{
		case "similaire":
			$resultatGlobal = searchSimilaire();
			break;
		case "all":
			$resultatGlobal = searchPodcast(true);
			break;
                case "sans100":
			$resultatGlobal = searchPodcast(false, false);
			break;
		case "list":
		default:
			$resultatGlobal = searchPodcast(false);
			break;
	}
	
}catch(Exception $e)
{
	// En cas d'erreur précédemment, on affiche un message et on arrête tout	
	die('Erreur : '.$e->getMessage());
}

switch($data->getMethod())  
{  
    case 'get':  
		RestUtils::sendResponse(200, json_encode($resultatGlobal, JSON_HEX_APOS), 'application/json'); 
        break;
	case 'post':  
		RestUtils::sendResponse(200, json_encode($resultatGlobal, JSON_HEX_APOS), 'application/json'); 
        break; 		
	default:
		break;
}



function get_json($date) {
  $file_day = "../../OK/".$date."/config.txt";
  if (file_exists($file_day))
    return json_decode(file_get_contents($file_day));
  else
    return null;
  }

function simplify_strings($string) {
	//Normalisation de la chaine utf8 en mode caractère + accents
	$string = Normalizer::normalize($string, Normalizer::FORM_D);
	//Suppression des accents et minuscules
        return strtolower(preg_replace('~\p{Mn}~u', '', $string));
}


function sortEntries($a, $b) {
    if ($a[0] == $b[0])
      return $a[1] < $b[1];
    else {
      return -strcmp($a[0], $b[0]);
    }
}


function searchPodcast($all, $cent = true) {
  $result = array();
  $q = $_GET["q"];
  if ((!isset($q) || $q == "") && !$all && $cent) {
    return $result;
  }
  else {
    if ((isset($q) && $q == "") && $cent)
        $all = true;
  }

  $pattern = '/^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]/';
  preg_match($pattern, $date, $matches);

  $q = "/".simplify_strings(preg_quote(preg_replace('!\s+!', ' ', $q)))."/";

  if($dossier = opendir('../../OK/')) {
    while(false !== ($fichier = readdir($dossier))) {
      if(preg_match($pattern, $fichier))  {
	$jsonDay = get_json($fichier);

	  if ($jsonDay->track)
	    foreach($jsonDay->track as $track) {
	      if ($all || ($cent && preg_match($q, simplify_strings($track->title)))
                        || (!$cent && !preg_match('/^100% /', simplify_strings($track->title)))) {
		$result[] = array($fichier, $track->time, $track->title, $track->mp3 == "" ? "0" : "1");
	      }
	    }

      }
    }
  }
  usort($result, "sortEntries");
  return $result;
}



function getEmissions($date) {
  $podcasts = get_json($date->format("Y-m-d"));
  if (!isset($podcasts) || count($podcasts->track) == 0) {
	return $podcasts;
  }
  foreach ($podcasts->track as $key => $podcast) {
    if(strlen($podcast->mp3) == 0) {
      unset($podcasts->track[$key]);
    }
  }
  return $podcasts;
}

function filterByEmissions($podcasts, $emissions) {
  foreach ($podcasts->track as $key => $podcast) {
    if(!in_array($podcast->title, $emissions)) {
        unset($podcasts->track[$key]);
    }
  }
  return $podcasts;
}

function filterByHour($podcasts, $date, $after) {
  $a = $after ? -1 : 1;
  foreach ($podcasts->track as $key => $podcast) {
    if(($podcast->time - $date->format("H")) * $a >= 0) {
        unset($podcasts->track[$key]);
    }
  }
  return $podcasts;
}

function getLastEntry($eDay, $dir) {
  if ($dir)
    $v = array("00", "");
  else
    $v = array("24", "");
  foreach($eDay->track as $entry) {
    if (($dir && $v[0] < $entry->time) || ((!$dir) && $v[0] > $entry->time))
      $v = array($entry->time, $entry->title);
  }
  return $v;
}

function findSimilaires($emissions, $date) {
  $interval = new DateInterval("P1D");
  
  $result = array();
  foreach($emissions as $e) {
    $result[$e] = array();
  }
  $result["podcast"] = array();
  
  
  $directions = array("-1", "1");
  foreach($directions as $dir) {
    // chercher dans la direction dir
    $nbToFind = count($emissions);
    $curDate = clone($date);
    $first = true;

    for($i = 0; $i != 100 && $nbToFind != 0; ++$i) {
      $eDay = getEmissions($curDate);      
      // on stoppe si c'est vide
      if (!isset($eDay) || count($eDay->track) == 0) {

	if ($dir == "-1")
	  $curDate->sub($interval);
	else
	  $curDate->add($interval);
	continue;
      }

      if ($first) {
	// même jour: on garde uniquement les heures avant
	$eDay = filterByHour($eDay, $date, $dir == "1");
      }
      
      // on stoppe si c'est vide
      if (!isset($eDay) || count($eDay->track) == 0) {
	$first = false;
	if ($dir == "-1")
	  $curDate->sub($interval);
	else
	  $curDate->add($interval);
	continue;
      }
      
      if (!isset($result["podcast"][$dir])) {
	// on affecte le plus vieux du lot
	$entry = getLastEntry($eDay, $dir == "-1");
	$result["podcast"][$dir] = array(clone($curDate), $entry[1]);
	$result["podcast"][$dir][0]->setTime($entry[0], 0);
      }
      
      // on retire les émissions qui ne matchent pas
      $eDay = filterByEmissions($eDay, $emissions);
      if (!isset($eDay) || count($eDay->track) == 0) {
	$first = false;
	if ($dir == "-1")
	  $curDate->sub($interval);
	else
	  $curDate->add($interval);
	continue;
	}
      
      // finalement, on met à jour si nécessaire
      foreach($eDay->track as $e) {
	if (!isset($result[$e->title][$dir])) {
	  $result[$e->title][$dir] = clone($curDate);
	  $result[$e->title][$dir]->setTime($e->time, 0);
	  $nbToFind = $nbToFind - 1;
	}
      }
      
      $first = false;
	if ($dir == "-1")
	  $curDate->sub($interval);
	else
	  $curDate->add($interval);
      if ($nbToFind <= 0)
	break;
    }
  }
    
  return $result;
}

function ajouterSimilaires($emissions) {
  $result = $emissions;
  $jsonObject = json_decode(file_get_contents("http://".$_SERVER["HTTP_HOST"]."/ws/index.php?req=similaire&t=".urlencode($emissions[0])));
  if (isset($jsonObject) && count($jsonObject) != 0)
  foreach($jsonObject as $emission) {
    $result[] = $emission->title;
  }
  return $result;
}

function searchSimilaire() {
  $result = array();
  $q = $_GET["q"];
  if (!isset($q) || $q == "") {
    return $result;
  }

  $d = $_GET['d'];
  if (!isset($d) || $d == "") {
    return $result;
  }
  $m = $_GET['m'];
  if (!isset($m) || $m == "") {
    return $result;
  }
  $y = $_GET['y'];
  if (!isset($y) || $y == "") {
    return $result;
  }


  $heure=$_GET['h'];
  if (!isset($heure) || $heure == "") {
    return $result;
  }

  $date = new DateTime($y."-".$m."-".$d." ".$heure.":00:00");
  
  $emissions = array();
  $emissions[] = $q;
  
  $emissions = ajouterSimilaires($emissions);
  

  $emissions = findSimilaires($emissions, $date);

  // post-process
  // garder une seule émission pour chaque émission similaire
  foreach($emissions as $key => $emission) {
    if ($key != $q && $key != "podcast") 
      if (count($emission) > 0){
      if (count($emission) > 1) {
	if ($date->diff($emission["-1"]) < $emission["1"]->diff($date))
	  $emissions[$key] = $emission["-1"];
	else
	  $emissions[$key] = $emission["1"];
      }
      else
	if (isset($emission["1"]))
	  $emissions[$key] = $emission["1"];
	else
	  $emissions[$key] = $emission["-1"];
      $emissions[$key] = array($emissions[$key]->format("Y-m-d"), $emissions[$key]->format("H"));
    }
    
    foreach($emissions[$key] as $ke => $ee) {
      if ($key == "podcast")
	$emissions[$key][$ke] = array($ee[0]->format("Y-m-d"), $ee[0]->format("H"), $ee[1]);
      else if ($key == $q)
	$emissions[$key][$ke] = array($ee->format("Y-m-d"), $ee->format("H"));	
    }
  }
  
  return $emissions;
}

?>

