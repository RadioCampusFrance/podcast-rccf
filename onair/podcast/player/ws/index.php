<?php

include 'configPaulo.php';
include 'outilEtRequete.php';
header("Content-Type: 'text/html'; charset=utf8");
$data = RestUtils::processRequest();  


function get_json($date) {
  $file_day = "../OK/".$date."/config.txt";
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

function get_time(&$time) {
  $time = $_GET['time'];
  if (!ctype_digit($time) || ($time < 0) || ($time > 24))
    $time = "";
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
  var $titleItems;
  var $ok;
  var $paulo_entries;
  var $duration;
  var $shortTitle;
  var $future;
  var $url;
  var $podcastable;
  var $image;


  function __construct() {
    $a = func_get_args(); 
        $i = func_num_args(); 
        if (method_exists($this,$f='__construct'.$i)) { 
            call_user_func_array(array($this,$f),$a); 
        } 
  }

  function __construct3($time, $entries, $duration) {
    $this->time = $time;
    $this->ok = false;
    $this->paulo_entries = $entries;
    $this->duration = $duration;
    $this->future = false;
    $this->url = "";
    $this->podcastable = false;
    $this->image = "";
  }

  function __construct2($jsonEntry, $date = "") {
    $this->mp3 = ltrim($jsonEntry->mp3);
    $this->future = $this->mp3 == "future";
    if ($this->future)
      $this->mp3 = "";
    $this->time = intval($jsonEntry->time);
    $titles = explode('|', $jsonEntry->title);
    $this->title = $jsonEntry->title;
    if (count($titles) == 1) {
      $this->titleItems = $jsonEntry->title;
    }
    else {
      $this->titleItems = "<ul>";
      foreach($titles as $t) {
	  $this->titleItems = $this->titleItems . "<li>" . $t . "</li>";
      }
      $this->titleItems = $this->titleItems . "</ul>";
    }
    $this->ok = true;
    $this->duration = 1;
    $this->url = ltrim($jsonEntry->url);
    $this->podcastable = $jsonEntry->podcastable;
    
    if ($date != "") {
      get_details_from_date($date, $day, $month, $year);
      $jsonObject = json_decode(file_get_contents("http://" .$_SERVER['HTTP_HOST']. "/ws/?req=image&t=" . urlencode($this->title) . "&h=" . $this->time . "&y=" . $year . "&m=" . $month . "&d=" . $day));
      $this->image = $jsonObject[0]->uri;
    }
    else 
      $this->image = "";
  }

  static function emptyToItem($hour) {
      echo "<p class=\"time_empty\">".$hour."h</p>";
  }

  function toItem($date, $ecoutes) {
    if ($this->ok) {
      if (strlen($this->mp3) != 0) {
	echo '<p class="time_elem';
      }
      else {
	echo '<p class="time_titles';
      }
      if ($this->duration != 1 || isset($this->$shortTitle))
	echo " large";
      if (strlen($this->mp3) != 0) {
	echo '" onclick="';
	$this->toLaunchTrack($date, true, true);
	echo '" ';
      }
      else {
	echo " time_empty\"";
	if ($this->future && $this->podcastable)
	  echo ' title="Bientôt en ligne&nbsp;!"';
      }
      echo  'onmouseover="document.getElementById(\'title'.$this->time.'\').style.display=\'block\';"  onmouseout="document.getElementById(\'title'.$this->time.'\').style.display=\'none\';">';
      if (isset($this->shortTitle))
	echo $this->shortTitle;
      else {
	echo $this->time.'h';
	if ($this->duration != 1)
	  echo "-".($this->time+$this->duration - 1).'h';
      }
      echo '';
      echo "<div id='title".$this->time."' class=\"time_popup\">".$this->titleItems;

      $h = intval($this->time);
      if (isset($ecoutes[$h]) && strlen($this->mp3) != 0) {
	$add = false;
	$nb = $ecoutes[$h][0] + $ecoutes[$h][1];
	if ($nb != 0) {
	  echo "<br /><span style=\"font-size: 70%; text-align:right\">".$nb . " écoute";
	  $add = true;
	  if ($nb > 1)
	    echo "s";
	  echo "</span>";
	}
      }
      if (strlen($this->mp3) == 0 && $this->future && $this->podcastable) {
	 echo "<br /><span style=\"font-size: 70%; text-align:right\">Bientôt en ligne&nbsp;!</span>";
      }
      echo "</div></p>\n";
    }
    else {
      echo '<p class="';
      if (count($this->paulo_entries) == 0) {
	echo 'time_empty';
	if ($this->duration != 1 || isset($this->$shortTitle))
	  echo " large";
	echo '">';
	if (isset($this->shortTitle))
	  echo $this->shortTitle;
	else {
	  echo $this->time.'h';
	  if ($this->duration != 1)
	    echo "-".($this->time+$this->duration).'h';
	}
	echo '</p>';
      } else {
	echo 'time_titles';
	if ($this->duration != 1 || isset($this->$shortTitle))
	  echo " large";
	echo '" onclick="';
	$this->toDisplayEntries(true);
	echo '" onmouseover="document.getElementById(\'title'.$this->time.'\').style.display=\'block\';"  onmouseout="document.getElementById(\'title'.$this->time.'\').style.display=\'none\';" >';
	if (isset($this->shortTitle))
	  echo $this->shortTitle;
	else {
	  echo $this->time.'h';
	  if ($this->duration != 1)
	    echo "-".($this->time+$this->duration).'h';
	}
	echo '';
	echo "<div id='title".$this->time."' class=\"time_popup\">Programmation musicale</div></p>\n";
      }
    }
  }

  function toMusicEntries() {
    echo "<ul>";
    $id = 0;
    foreach($this->paulo_entries as $entry) {
      echo "<li id=\"entry-".$this->time."-".$id."\" title=\"".$entry["time"].": ".$entry["title"].", ".$entry["author"]."\"><span>".$entry["time"]. "</span><em>" .$entry["title"] ."</em>, ".$entry["author"]."</li>";
      $id = $id + 1;
    }
    echo "</ul>";
  }

  function toLaunchTrack($date, $play, $quotes = false) {
    $t = str_replace("'", "\'", $this->title);
    if ($quotes)
      $t = str_replace("\"", "&quot;", $t);
    echo 'launch_track(\''.$date.'/'.$this->mp3.'\',\''.$t.'\',\''.$this->time.'\'';
    if ($play)
      echo ", true";
    else
      echo ", false";
    echo ", '".$this->url."')";
  }

  function toDisplayEntries($active) {
    if ($active)
      echo 'display_entries('.$this->time.', true)';
    else
      echo 'display_entries('.$this->time.', false)';
  }
  

}

function load_podcasts($jsonDay, $date, $time) {
  $result = array();
  if ($jsonDay->track)
    foreach($jsonDay->track as $track) {
	$result[intval($track->time)] = new Podcast($track, $time == $track->time ? $date : "");
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
	get_time($time);

	$datex = explode('-',$date);
	$datprev = date ("Y-m-d", mktime (0,0,0,$datex[1],$datex[2]-1,$datex[0]));
	$datnext = date ("Y-m-d", mktime (0,0,0,$datex[1],$datex[2]+1,$datex[0]));


	$jsonDay = get_json($date);

	$ecoutes = load_ecoutes($date);
	
	$podcasts = load_podcasts($jsonDay, $date, $time);

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
	  $podcasts[0] = new Podcast(0, $entries, $first);
	  if ($first != 1)
	    $podcasts[0]->shortTitle = "la nuit";
	}
	if ($first == 0 && $second > 0) {
	  $entries = get_paulo_entries($date, $first + 1, $bdd, "..", $second);
	  $podcasts[$first + 1] = new Podcast($first + 1, $entries, $second-1);
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
	      $podcasts[$i] = new Podcast($i, $entries, 1);
	    }
	  }

	
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