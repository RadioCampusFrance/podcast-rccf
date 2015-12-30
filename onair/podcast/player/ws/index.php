<?php

include 'configPaulo.php';
include 'outilEtRequete.php';
header("Content-Type: 'text/html'; charset=utf8");
$data = RestUtils::processRequest();  


function get_json($date) {
  $file_day = "../../OK/".$date."/config.txt";
  if (file_exists($file_day))
    return json_decode(file_get_contents($file_day));
  else
    return null;
}

function get_program_at($y, $m, $d, $hour) {

  $jsonObject = json_decode(file_get_contents("http://" . $_SERVER['HTTP_HOST'] . "/ws/index.php?req=onair&y={$y}&m={$m}&d={$d}&h={$hour}"));

  if($jsonObject->type == "emission") {
    return array($jsonObject->titre, $jsonObject->podcastable);
  }
  else {
    return array();
  }
  
}

function get_current_date(&$date, &$day, &$month, &$year) {
  $date = date ("Y-m-d");
  $day = date("d");
  $month = date("m");
  $year = date("Y");
}

function get_details_from_date($date, &$day, &$month, &$year) {
  $pattern = '/^([0-9][0-9][0-9][0-9])-([0-9][0-9])-([0-9][0-9])/';
  preg_match($pattern, $date, $matches);
  if (checkdate ($matches[2], $matches[3], $matches[1])) {
    $day = $matches[3];
    $month = $matches[2];
    $year = $matches[1];
  }
}
function get_date(&$date, &$day, &$month, &$year) {
  $date = $_GET['date'];
  $pattern = '/^([0-9][0-9][0-9][0-9])-([0-9][0-9])-([0-9][0-9])/';
  preg_match($pattern, $date, $matches);
  if (count($matches) != 4) {
    get_current_date($date, $day, $month, $year);
  }
  else if (checkdate ($matches[2], $matches[3], $matches[1])) {
    $day = $matches[3];
    $month = $matches[2];
    $year = $matches[1];
  }
  else {
    get_current_date($date, $day, $month, $year);
  }

}

function load_ecoutes($date) {
  $result = array();
  
  for($i = 0; $i < 24; ++$i) {
    $result[$i][0] = "0";
    $result[$i][1] = "0";
    }

  $datetime = $date . " 00:00:00";

	$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

	try {
	$bdd = new PDO('mysql:host='._PAULODB_SERVEUR.';dbname='._PAULODB_BD, _PAULODB_LOGIN, _PAULODB_MDP, $options);

  $sql = "Select timeslot, download, count(*) as nb From log_ecoute where (timeslot >= ".$bdd->quote($datetime)." and timeslot <= DATE_ADD(".$bdd->quote($datetime).", INTERVAL 24 HOUR)) group by timeslot, download;";
	
  $prep = $bdd->query($sql);
	
  $prep->execute();
  for($i=0; $row = $prep->fetch(); $i++){
    $elems = explode(" ", $row["timeslot"]);
    $elems2 = explode(":", $elems[1]);
    $h = intval($elems2[0]);
    $download = intval($row["download"]);
    $result[$h][$download] = $row["nb"];
  }
	}
	catch (PDOException $Exception ) {
	}


  return $result;
}


class Podcast {
  var $mp3;
  var $time;
  var $title;
  var $ok;
  var $paulo_entries;
  var $duration;
  var $shortTitle;
  var $future;
  var $url;
  var $podcastable;
  var $image;
  var $ecoutes;


  function __construct() {
    $a = func_get_args(); 
        $i = func_num_args(); 
        if (method_exists($this,$f='__construct'.$i)) { 
            call_user_func_array(array($this,$f),$a); 
        } 
  }

  function __construct4($time, $entries, $duration, $date) {
    $this->time = $time;
    $this->ok = false;
    $this->paulo_entries = $entries;
    $this->duration = $duration;
    $this->future = false;
    $this->url = "";
    $this->podcastable = false;
    $this->loadImage($date);
    
  }

  function __construct2($jsonEntry, $date) {
    $this->mp3 = ltrim($jsonEntry->mp3);
    $this->future = $this->mp3 == "future";
    if ($this->future)
      $this->mp3 = "";
    $this->time = intval($jsonEntry->time);
    
    $this->title = $jsonEntry->title;
    $this->ok = true;
    $this->duration = 1;
    $this->url = ltrim($jsonEntry->url);
    $this->podcastable = $jsonEntry->podcastable;
    $this->loadImage($date);
  }
  
  function loadImage($date) {
    $this->image = "";
    if ($date != "") {
      get_details_from_date($date, $day, $month, $year);
      $jsonObject = json_decode(file_get_contents("http://" .$_SERVER['HTTP_HOST']. "/ws/?req=image&t=" . urlencode($this->title) . "&h=" . $this->time . "&y=" . $year . "&m=" . $month . "&d=" . $day));
      
      $this->image = $jsonObject[0]->uri;
    }
    if ($this->image == "") {
      $this->image = "/onair/podcast/player/images/fond-";
      if ($this->time < 6) {
            $this->image .= "bleu.png";
      }
      else if ($this->time < 12) {
            $this->image .= "jaune.png";
      }
      else if ($this->time < 18) {
            $this->image .= "rouge.png";
      }
      else {
            $this->image .= "vert.png";
      }
    }
  }


  function setEcoutes($ecoutes) {
        $h = intval($this->time);
      if (isset($ecoutes[$h]) && strlen($this->mp3) != 0) {
	$this->ecoutes = $ecoutes[$h];
      }
  }
}

function load_podcasts($jsonDay, $date) {
  $result = array();
  if ($jsonDay->track)
    foreach($jsonDay->track as $track) {
	$result[intval($track->time)] = new Podcast($track, $date);
    }
  return $result;
}

	$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

	try {
		$bdd = new PDO('mysql:host='._PAULODB_SERVEUR.';dbname='._PAULODB_BD, _PAULODB_LOGIN, _PAULODB_MDP, $options);
	}
	catch (PDOException $Exception ) {
	$bdd = null;
	//exit("Des problèmes techniques nous empêchent temporairement de vous proposer les podcasts... Veuillez nous excuser pour la gêne occasionnée.");
	}

        include("../lib/paulo_entries.php");


	$url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$prefix_url = parse_url($url, PHP_URL_PATH);

	setlocale (LC_TIME, 'fr_FR.utf8','fra'); 
	date_default_timezone_set('Europe/Paris');
	get_date($date, $day, $month, $year);

	$datex = explode('-',$date);


	$jsonDay = get_json($date);

	$ecoutes = load_ecoutes($date);
	
	$podcasts = load_podcasts($jsonDay, $date);

	$first = -1;
	$second = -1;
	for($i = 0; $i != 8; $i++)
	  if (isset($podcasts[$i])) {
            if ($first == -1)
                $first = $i;
            else if ($second == -1) {
                $second = -1;
                break;
            } 
	  }
        if ($first == -1)
            $first = 7;
        if ($second == -1)
            $second = 7;
        

	if ($first != 0) {
	  $entries = get_paulo_entries($date, 0, $bdd, "..", $first);
	  $podcasts[0] = new Podcast(0, $entries, $first, $date);
	  if ($first != 1)
	    $podcasts[0]->shortTitle = "la nuit";
	}
	if ($first == 0 && $second > 0) {
	  $entries = get_paulo_entries($date, $first + 1, $bdd, "..", $second);
	  $podcasts[$first + 1] = new Podcast($first + 1, $entries, $second-1, $date);
	  if ($second != 1)
	    $podcasts[$first + 1]->shortTitle = "la nuit";
	 }
	 
	// ajout des créneaux de podcast pas encore récupérés, mais qui vont arriver
	$heureCourante = intval(date("G"));
	$firstH = $heureCourante - 2;
	if ($firstH < 0)
	  $firstH = 0;
	for($i = $firstH; $i != $heureCourante + 1; $i++) {
	  if (!isset($podcasts[$i])) {
	    $program = get_program_at($year, $month, $day, $i);
	    if (count($program) != 0) {
	      $elem->mp3 = "future";
	      $elem->time = $i;
	      $elem->title = $program[0];
	      $elem->podcastable = $program[1];
	      $podcasts[$i] = new Podcast($elem, $date);
	    }
	  }
	}

	for($i = $second; $i != 24; $i++)
	  if (!isset($podcasts[$i])) {
	    $entries = get_paulo_entries($date, $i, $bdd, "..");
	    if ($entries && count($entries) > 0) {
	      $podcasts[$i] = new Podcast($i, $entries, 1, $date);
	    }
	  }

	  
        // on modifie les écoutes
        foreach($podcasts as $p)
            $p->setEcoutes($ecoutes);
	  
        // retour
	
	switch($data->getMethod())  
        {  
            case 'get':  
                        RestUtils::sendResponse(200, json_encode($podcasts, JSON_HEX_APOS), 'application/json'); 
                break;
                case 'post':  
                        RestUtils::sendResponse(200, json_encode($podcasts, JSON_HEX_APOS), 'application/json'); 
                break; 		
                default:
                        break;
        }

?>