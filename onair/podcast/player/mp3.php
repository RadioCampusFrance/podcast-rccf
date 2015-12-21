<?php

$granted = true; // Bypass validation.

if (!$granted) {
	header ("HTTP/1.1 404 Not Found");
	die();
}

include 'configPaulo.php';




$date = $_GET["d"];
$hour = $_GET["h"];
$dl = $_GET["dl"];

if (isset($_GET["dh"])) {
    list($date, $hour) = explode('_', $_GET["dh"]);
    $dl = "true";
}
$nolog = $_GET["nl"];

if (isset($date) && isset($hour)) {
  $fullhour = "" .$hour;
  if (strlen($fullhour) == 1)
    $fullhour = "0" . $fullhour;

  $datetime = $date . " " . $fullhour . ":00:00";

if (!isset($nolog)) {
$ip = getenv('HTTP_CLIENT_IP')?:
getenv('HTTP_X_FORWARDED_FOR')?:
getenv('HTTP_X_FORWARDED')?:
getenv('HTTP_FORWARDED_FOR')?:
getenv('HTTP_FORWARDED')?:
getenv('REMOTE_ADDR');

setlocale (LC_TIME, 'fr_FR.utf8','fra'); 
date_default_timezone_set('Europe/Paris');

$today = date ("Y-m-d");

$useragent=getenv("HTTP_USER_AGENT");

if (strpos($useragent, 'Googlebot') === FALSE) {

try {

  $options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

  $bdd = new PDO('mysql:host='._PAULODB_SERVEUR.';dbname='._PAULODB_BD, _PAULODB_LOGIN, _PAULODB_MDP, $options);

  if (isset($dl))
    $sql = "INSERT INTO log_ecoute (timeslot, download, dl_ip, dl_day, dl_useragent) VALUES ('".$datetime."', 1, '".$ip."', '".$today."', '".$useragent."') on duplicate key update timeslot = timeslot";
  else
    $sql = "INSERT INTO log_ecoute (timeslot, download, dl_ip, dl_day, dl_useragent) VALUES ('".$datetime."', 0, '".$ip."', '".$today."', '".$useragent."') on duplicate key update timeslot = timeslot";

     //echo $sql;
    $prep = $bdd->query($sql);
}
catch(Exception $e) {
	// En cas d'erreur précédemment, on affiche un message et on arrête tout	
	header ("HTTP/1.1 404 Not Found");
	die('Erreur : '.$e->getMessage());

}
}


}
else {
	header ("HTTP/1.1 404 Not Found");
  die("No hour or date defined");
}
}

$file = $date."-".$fullhour."00.mp3";
$path = "../OK/".$date."/".$file;

$type = "audio/mpeg";

require_once('smartReadFile.php');
smartReadFile($path, $file, $type, $dl);


?>