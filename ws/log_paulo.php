<?php
include 'outilEtRequete.php';
include 'configPaulo.php';


$html = implode('', file('http://80.11.133.224/cgi-bin/www.x'));

$pattern= '/<TR><TD><FONT ([^>]*)>([^<]*)<\/font><\/TD><TD><FONT [^>]*>([^<]*)<\/font><\/TD><TD><FONT [^>]*>([^<]*)</';

preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

date_default_timezone_set('Europe/Paris');

$yesterday=date("Y-m-d", time() - 60 * 60 * 24);
$today=date("Y-m-d");
$tomorrow=date("Y-m-d", time() + 60 * 60 * 24);

$hour=date("H");
$pm=$hour > 12;

$entries = array();

// create the elements 

echo "Logging Paulo\n";
$found=false;
foreach($matches as $var) {
    $color = $var[1];
    $timestamp = $var[2];
    $title = $var[3];
    $author = $var[4];

    $patternbis='/([0-9][0-9]):[0-9][0-9]:[0-9][0-9]/';
    preg_match_all($patternbis, $timestamp, $matchesbis, PREG_SET_ORDER);
    $line_hour=$matchesbis[0][0];
    if ($pm and $line_hour < 12) {
	$datetime = $tomorrow . " " . $timestamp;
    }
    else if (!$pm and $line_hour > 12) {
	$datetime = $yesturday . " " . $timestamp;
    }
    else {
	$datetime = $today . " " . $timestamp;
    }

    $next = false;($color == "COLOR=\"#00B4FF\" face=\"Trebuchet, Tahoma, Verdana, sans-serif\"");

    $index = count( $entries ) - 1;
    if ($index >= 0)
      $entries[$index]["end"] = $datetime;
    $entries[] = array( "datetime" => $datetime, "title" => $title, "author" => $author);
    if ($next) {
      $found = true;
      break;
    }
}
print_r($entries);

if (!$found) {
  $patternbis= '/fin = correct_date\(([0-9]*), ([0-9]*), ([0-9]*), ([0-9]*), ([0-9]*), ([0-9]*)/';
  preg_match_all($patternbis, $html, $matchesbis, PREG_SET_ORDER);
  if (count($matchesbis) != 0) {
    $id = count($entries) - 1;
    $entries[$id]["end"] = $matchesbis[0][1]."-".$matchesbis[0][2]."-".$matchesbis[0][3]." ".$matchesbis[0][4].":".$matchesbis[0][5].":".$matchesbis[0][6];
  }
  else {
    array_pop($entries);
  }
}



// store it in the database


try {
	$pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;		

	$bdd = new PDO('mysql:host='._PAULODB_SERVEUR.';dbname='._PAULODB_BD, _PAULODB_LOGIN, _PAULODB_MDP, $pdo_options);

	foreach ($entries as $entry)
	  if (isset($entry["datetime"]) && isset($entry["end"])) {
	    $sql = "INSERT INTO titres_paulo (begin, end, title, author) VALUES ('".$entry["datetime"]."', '".$entry["end"]."', ". $bdd->quote($entry["title"]).", ".$bdd->quote($entry["author"]).") ON DUPLICATE KEY UPDATE end='".$entry["end"]."';";
	    $prep = $bdd->query($sql);
	  }

}
catch(Exception $e) {
	// En cas d'erreur précédemment, on affiche un message et on arrête tout	
	die('Erreur : '.$e->getMessage());

}



?>