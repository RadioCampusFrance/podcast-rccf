<?php
include 'outilEtRequete.php';
include 'config.php';
header("Content-Type: 'text/html'; charset=utf8");
$data = RestUtils::processRequest();  


try
{	

	$requete = $_GET['action'];


	switch($requete)
	{
		case "rename":
			$resultatGlobal = renameTitle();
			break;
		case "ko2ok":
		case "2ok":
			$resultatGlobal = toOk();
			break;
		case "2paulo":
		case "ok2ko":
			$resultatGlobal = toPaulo();
			break;
		case "2ko":
		case "ok2ko-keep":
			$resultatGlobal = toKo();
			break;
		case "seturl":
			$resultatGlobal = setUrl();
			break;
		default:
			$resultatGlobal = '';
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


function get_filename($date, $time, $ok) {
  if ($ok)
    return "../../OK/".$date."/".$date."-".$time."00.mp3";
  else
    return "../../KO/".$date."-".$time."00.mp3";
}

function save_json($date, $jsonDay) {
  $file_day = "../../OK/".$date."/config.txt";
  file_put_contents($file_day, json_encode($jsonDay, JSON_HEX_APOS));
}

function get_json($date) {
  $file_day = "../../OK/".$date."/config.txt";
  $json = null;
  if (file_exists($file_day))
    $json = json_decode(file_get_contents($file_day));
  if ($json == null) {
    $json->track = array();
  }
  return $json;
}

function renameTitle() {
  $resultat = array();

  $date = $_GET['d'];
  $time = $_GET['t'];
  $title = $_GET['tt'];
  
  $jsonDay = get_json($date);

  if (!$jsonDay) {
    $resultat["error"] = "Impossible de trouver une description correspondante (" . $date . ")";
    return $resultat;
  }
  if ($jsonDay->track)
    foreach($jsonDay->track as $track) {
      if (intval($track->time) == intval($time)) {
	$track->title = $title;
	save_json($date, $jsonDay);
	$resultat["action"] = "rename";
	break;
	}
    }
  if ($resultat["action"] != "rename")
      $resultat["error"] = "Impossible de trouver l'entrée correspondante a l'heure donnee";
  return $resultat;
}

function setUrl() {
  $resultat = array();

  $date = $_GET['d'];
  $time = $_GET['t'];
  $url = $_GET['u'];
  
  $jsonDay = get_json($date);

  if (!$jsonDay) {
    $resultat["error"] = "Impossible de trouver une description correspondante (" . $date . ")";
    return $resultat;
  }
  if ($jsonDay->track)
    foreach($jsonDay->track as $track) {
      if (intval($track->time) == intval($time)) {
	$track->url = $url;
	save_json($date, $jsonDay);
	$resultat["action"] = "seturl";
	break;
	}
    }
  if ($resultat["action"] != "seturl")
      $resultat["error"] = "Impossible de trouver l'entrée correspondante a l'heure donnee";
  return $resultat;
}
function toOk() {
  $resultat = array();

  $date = $_GET['d'];
  $title = $_GET['tt'];
  $time = "" . $_GET['t'];
  if (strlen($time) == 1)
    $time = "0".$_GET['t'];
  
  $jsonDay = get_json($date);

  if (!$jsonDay) {
    $resultat["error"] = "Impossible de trouver une description correspondante (" . $date . ")";
    return $resultat;
  }

  $found = false;
  if ($jsonDay->track)
    foreach($jsonDay->track as $track) {
      if (intval($track->time) == intval($time)) {
	$found = true;
	$track->mp3 = $date."-".$time."00.mp3";
      }
    }

    $fileOK = get_filename($date, $time, true);
  $fileKO = get_filename($date, $time, false);
  $jsonDayCopy = $jsonDay;
  if (!$found)
    $jsonDay->track[] = array("mp3"=>$date."-".$time."00.mp3","time"=>$time,"title"=>$title);
  save_json($date, $jsonDay);

  $valid = @rename($fileKO, $fileOK);
  if (!$valid) {
    save_json($date, $jsonDayCopy);
    $resultat["error"] = "Impossible de deplacer le fichier";
  }
  $resultat["action"] = "2ok";

  return $resultat;
}

function toKo() {
  $resultat = array();

  $date = $_GET['d'];
  $time = "" . $_GET['t'];
  if (strlen($time) == 1)
    $time = "0".$_GET['t'];
  
  $jsonDay = get_json($date);

  if (!$jsonDay) {
    // from Paulo to KO
    $title = $_GET['tt'];
    $jsonDay->track[] = array("mp3"=>"","time"=>$time,"title"=>$title);
    save_json($date, $jsonDay);
    $resultat["action"] = "2ko";
    return $resultat;
  }

  $jsonNewDay->track = array();

  $found = false;
  if ($jsonDay->track)
    foreach($jsonDay->track as $track) {
      if (intval($track->time) != intval($time)) {
	$jsonNewDay->track[] = $track;
      }
      else {
      	$jsonNewDay->track[] = $track;
      	$jsonNewDay->track[count($jsonNewDay->track) - 1]->mp3 =  "";
      	$found = true;
      }
  }
  if (!$found) {
    // from Paulo to KO
    $title = $_GET['tt'];
    $jsonDay->track[] = array("mp3"=>"","time"=>$time,"title"=>$title);
    save_json($date, $jsonDay);
    $resultat["action"] = "2ko";
    return $resultat;
  }
  
  $fileOK = get_filename($date, $time, true);
  $fileKO = get_filename($date, $time, false);

  if (file_exists($fileOK))  {
    $valid = @rename($fileOK, $fileKO);
    if (!$valid) {
      $resultat["error"] = "Impossible de deplacer le fichier";
      return $resultat;
      }
  }
  save_json($date, $jsonNewDay);

  $resultat["action"] = "2ko";

  return $resultat;
}


function toPaulo() {
  $resultat = array();

  $date = $_GET['d'];
  $time = "" . $_GET['t'];
  if (strlen($time) == 1)
    $time = "0".$_GET['t'];
  
  $jsonDay = get_json($date);

  if (!$jsonDay) {
    $resultat["error"] = "Impossible de trouver l'entrée correspondante";
  }

  $jsonNewDay->track = array();

  if ($jsonDay->track)
    foreach($jsonDay->track as $track) {
      if (intval($track->time) != intval($time)) {
	$jsonNewDay->track[] = $track;
      }
  }
  $fileOK = get_filename($date, $time, true);
  $fileKO = get_filename($date, $time, false);

  if (file_exists($fileOK))  {
    $valid = @rename($fileOK, $fileKO);
    if (!$valid) {
      $resultat["error"] = "Impossible de deplacer le fichier";
      return $resultat;
      }
  }
  save_json($date, $jsonNewDay);
  
  $resultat["action"] = "2paulo";

  return $resultat;
}

?>

