<?php
include 'outilEtRequete.php';
include 'configPaulo.php';

$arrContextOptions=array(
    "ssl"=>array(
        "verify_peer"=> false,
        "verify_peer_name"=> false,
    ),
);
$json = file_get_contents("https://89.83.10.85/api/live-info?type=show_content", false, stream_context_create($arrContextOptions)); // IP 22 bis
$entries = json_decode($json);
$entries = $entries->currentShowContent;


function convert_date($timestamp) {
    $dt = new DateTime($timestamp, new DateTimeZone('UTC'));

    $dt->setTimeZone(new DateTimeZone('Europe/Paris'));

    return $dt->format('Y-m-d H:i:s');
}

try {
	$pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $pdo_options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8';
	
	

	$bdd = new PDO('mysql:host='._PAULODB_SERVEUR.';dbname='._PAULODB_BD, _PAULODB_LOGIN, _PAULODB_MDP, $pdo_options);

	foreach ($entries as $entry)
	  if (isset($entry->sched_starts) && isset($entry->sched_ends)) {
            $entry->sched_ends = convert_date($entry->sched_ends);
            $entry->sched_starts = convert_date($entry->sched_starts);
	    $sql = "INSERT INTO titres_paulo (begin, end, title, author) VALUES ('".$entry->sched_starts."', '".$entry->sched_ends."', ". $bdd->quote($entry->file_track_title).", ".$bdd->quote($entry->file_artist_name).") ON DUPLICATE KEY UPDATE end='".$entry->sched_ends."';";
	    $prep = $bdd->query($sql);
	  }

}
catch(Exception $e) {
	// En cas d'erreur précédemment, on affiche un message et on arrête tout	
	die('Erreur : '.$e->getMessage());

}



?>
