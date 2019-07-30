<?php
// You should use an autoloader instead of including the files directly.
// This is done here only to make the examples work out of the box.
include 'feedwriter/Item.php';
include 'feedwriter/Feed.php';
include 'feedwriter/RSS2.php';

setlocale (LC_TIME, 'fr_FR.utf8','fra'); 
date_default_timezone_set('Europe/Paris');

use \FeedWriter\RSS2;

require_once 'cache.class.php';

$c = new Cache("rss");
$c->eraseExpired();
 

$emission = $_GET["q"];
if (!isset($emission))
  $emission = "";
$sans100 = isset($_GET["sans-100"]);
  
header("Content-Type: application/rss+xml; charset=utf-8");

$feedkey = $sans100 ? "sans100" : ($emission == "" ? "all" : $emission);
if ($c->isCached($feedkey)) {

  echo $c->retrieve($feedkey);
  return;
}

  
$TestFeed = new RSS2;

if ($emission != "") {
  $TestFeed->setDescription('Tous les podcasts de l\'émission ' . $emission . ' diffusés sur Radio Campus Clermont-Ferrand');
  $url = 'http://www.campus-clermont.net/onair/podcast/player/?search='.urlencode($emission);
  $title = 'Podcasts de l\'émission ' . $emission . ' sur Radio Campus Clermont-Ferrand';
  $jsonObject = json_decode(file_get_contents("http://" .$_SERVER['HTTP_HOST']. "/ws/?req=image&t=" . urlencode($emission)));
  $image = 'http://'. $_SERVER["HTTP_HOST"] . $jsonObject[0]->uri;
}
else {
  $TestFeed->setDescription('Tous les podcasts de Radio Campus Clermont-Ferrand');
  $url = 'http://www.campus-clermont.net/onair/podcast/player/';
  $title = 'Le podcast de Radio Campus';
  $image = "http://www.campus-clermont.net/onair/podcast/player/images/logo.png";
}

$TestFeed->setTitle($title);

$TestFeed->setLink($url);
$TestFeed->setImage($title, $url, $image);

if ($emission != "") {
  $jsonObject = json_decode(file_get_contents("http://" .$_SERVER['HTTP_HOST']. "/onair/podcast/player/ws/search.php?q=" . urlencode($emission)));
} else if ($sans100) {
  $jsonObject = json_decode(file_get_contents("http://" .$_SERVER['HTTP_HOST']. "/onair/podcast/player/ws/search.php?action=sans100"));
}
else {
    $jsonObject = json_decode(file_get_contents("http://" .$_SERVER['HTTP_HOST']. "/onair/podcast/player/ws/search.php?action=all"));
}
  
$m2Txt = array("janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août", "septembre", "octobre", "novembre", "décembre");

$i = 0;
foreach($jsonObject as $entry) {
  if ($i > 64)
    break;
  if ($entry[3] == "1") {
    $item = $TestFeed->createNewItem();
    
    $details = explode("-", $entry[0]);
    
    
    $item->setTitle($entry[2] . "  du " . intval($details[2]) . " ". $m2Txt[$details[1] - 1] . " " . $details[0]);
    $item->setLink("http://www.campus-clermont.net/onair/podcast/player/?date=".$entry[0]."&time=".intval($entry[1]));
    $item->setEnclosure("http://www.campus-clermont.net/onair/podcast/player/mp3.php?dh=".$entry[0]."_".$entry[1], 0, "audio/mp3", false);
    $item->setDate(strtotime($entry[0] . " " . $entry[1] . ":00:00"));
    $item->setDescription("Émission " . $entry[2] . " diffusée le " . intval($details[2]) . " ". $m2Txt[$details[1] - 1] . " à " . intval($entry[1]) ."h sur les ondes de Radio Campus Clermont-Ferrand");
    $TestFeed->addItem($item);
    $i = $i + 1;
  }
}

$content = $TestFeed->generateFeed();
$c->store($feedkey, $content, 3600);

echo $content;

?>
