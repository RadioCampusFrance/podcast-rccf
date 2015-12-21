<?php
include 'outilEtRequete.php';
include 'configPaulo.php';


$json = file_get_contents("http://80.11.133.224/api/live-info?type=show_content");
$entries = json_decode($json);
$entries = $entries->currentShowContent;


function convert_date($timestamp) {
    $dt = new DateTime($timestamp, new DateTimeZone('UTC'));

    $dt->setTimeZone(new DateTimeZone('Europe/Paris'));

    return $dt->format('Y-m-d H:i:s');
}

try {
	$pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;		

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