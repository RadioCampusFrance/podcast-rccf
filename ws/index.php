<?php
include 'outilEtRequete.php';
include 'config.php';
include 'configPaulo.php';
header("Content-Type: 'text/html'; charset=utf8");
$data = RestUtils::processRequest();  


try
{	
	$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

	$bdd = new PDO('mysql:host='._DRUPAL_SERVEUR.';dbname='._DRUPAL_BD, _DRUPAL_LOGIN, _DRUPAL_MDP, $options);

	$requete = $_GET['req'];

	switch($requete)
	{
		case "onair":
			$resultatGlobal = getOnAir($bdd);
			break;
		case "recent":
			$resultatGlobal = getRecent($bdd);
			break;
		case "wtpodcast":
			$resultatGlobal = whatIsForPodcast($bdd);
			break;
		case "similaire":
			$resultatGlobal = emissionsSimilaires($bdd);
			break;
		case "image":
			$resultatGlobal = imageEmission($bdd);
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
		RestUtils::sendResponse(200, json_encode($resultatGlobal), 'application/json'); 
        break;
	case 'post':  
		RestUtils::sendResponse(200, json_encode($resultatGlobal), 'application/json'); 
        break; 		
	default:
		break;
}


function get_emission($m,$d,$y, $jour, $heure, $bdd) {
	$nthJour = ceil(date('j', mktime(0,0,0,$m,$d,$y)) / 7);
if ($nthJour == 5)
	$sql = "SELECT title, field_podcastable_value as podcastable, nid FROM `drupal_field_data_field_jour` as j, `drupal_field_data_field_heure` as h, `drupal_field_data_field_en_cours` as c, `drupal_field_data_field_duree` as d, `drupal_field_data_field_podcastable` as p, drupal_node as n
			WHERE j.bundle LIKE 'emission'
			AND j.deleted=0 
			AND j.entity_id=h.entity_id
			AND c.entity_id=h.entity_id
			AND d.entity_id=h.entity_id
			AND p.entity_id=h.entity_id
			AND j.field_jour_value = {$jour}
			AND (h.field_heure_value = {$heure} OR (h.field_heure_value = ".($heure-1)." AND (d.field_duree_value = '2h' or d.field_duree_value = '3h'))
			    OR (h.field_heure_value = ".($heure-2)." AND (d.field_duree_value = '3h')))
			AND c.field_en_cours_value = 1
			AND j.entity_id = n.nid;";
	else
	$sql = "SELECT DISTINCT title, field_podcastable_value as podcastable, nid FROM `drupal_field_data_field_jour` as j, `drupal_field_data_field_heure` as h, `drupal_field_data_field_en_cours` as c, `drupal_field_data_field_duree` as d, `drupal_field_data_field_podcastable` as p, `drupal_field_data_field_semaines` as s, `drupal_field_data_field_frequence` as f, drupal_node as n
			WHERE j.bundle LIKE 'emission'
			AND j.deleted=0 
			AND j.entity_id=h.entity_id
			AND c.entity_id=h.entity_id
			AND d.entity_id=h.entity_id
			AND p.entity_id=h.entity_id
			AND f.entity_id = h.entity_id
			AND (f.field_frequence_value = 1 OR (s.entity_id=h.entity_id AND s.field_semaines_value = 'semaine{$nthJour}'))
			AND j.field_jour_value = {$jour}
			AND (h.field_heure_value = {$heure} OR (h.field_heure_value = ".($heure-1)." AND (d.field_duree_value = '2h' or d.field_duree_value = '3h'))
			    OR (h.field_heure_value = ".($heure-2)." AND (d.field_duree_value = '3h')))
			AND c.field_en_cours_value = 1
			AND j.entity_id = n.nid;";
			
	  $prep = $bdd->query($sql);
	  $tab_d = array();
	  $podcastable = false;
	  $nid = "";
	   while ($donnees = $prep->fetch(PDO::FETCH_OBJ))	{
		$tab_d[]=$donnees->title;
		if ($donnees->podcastable == "1")
		  $podcastable = true;
		if ($nid == "")
		  $nid = $donnees->nid;
		else
		  $nid = "-1";
	    }
	    return array($tab_d, $podcastable, $nid);
}

function create_json_paulo($fromPaulo) {
  $resultat = array();
  $resultat['type'] = "paulo";
  $resultat['titre'] = utf8_encode($fromPaulo['title']);
  $resultat['auteur'] = utf8_encode($fromPaulo['author']);
  $resultat['begin'] = $fromPaulo["begin"];
  $resultat['end'] = $fromPaulo["end"];
  $resultat['podcastable'] = false;
  return $resultat;
}

function create_json_emission($titre, $podcastable, $nid, $begin) {
  $resultat = array();
  $resultat['type'] = "emission";
  $resultat['titre'] = $titre;
  $resultat['auteur'] = "";
  $resultat['podcastable'] = $podcastable;
  $resultat['begin'] = $begin;
  if ($nid != "-1")
    $resultat['url'] = "/node/".$nid;
  return $resultat;
}
function getRecent($bdd) {
  date_default_timezone_set('Europe/Paris');
	
  $time = new DateTime();
  
  $oneSecond = new DateInterval(PT1S);
  
  $resultat = array();
  for($i = 0; $i != 10; $i++) {
    $y = $time->format('Y');
    $m = $time->format('m');
    $d = $time->format('d');
    $jour = $time->format('N');
    $heure = $time->format('H');
    $minute = $time->format('i');
    $seconde = $time->format('s');

    list($tab_d, $podcastable, $nid) = get_emission($m,$d,$y, $jour, $heure, $bdd);
    if (count($tab_d) > 0) {
      $resultStart = $y . "-" . $m . "-" . $d . " " . $heure . ":00:00";
      $resultat[$i] = create_json_emission(implode("|",$tab_d), $podcastable, $nid, $resultStart);
      $time = new DateTime($resultStart);
    }
    else {
      $paulo = getFromPaulo($y, $m, $d, $heure, $minute, $seconde);
      if (isset($paulo) && isset($paulo["begin"]) && $paulo["begin"] != "") {
	$resultat[$i] = create_json_paulo($paulo);
	$time = new DateTime($paulo["begin"]);
      }
      else {
	return $resultat;
      }
    }
    $time->sub($oneSecond);
  }
  
  return $resultat;
}



function getOnAir($bdd)
{
	date_default_timezone_set('Europe/Paris');
	
	$d = date("d");
	$m = date("m");
	$y = date("Y");
	$jour = date("N");
	$heure = date("H");
	$minute = date("i");
	$seconde = date("s");
	$forcePaulo = isset($_GET['paulo']);
	
	if(isset($_GET['d']) && isset($_GET['m']) && isset($_GET['y']) && isset($_GET['h']))
	{
		$d = $_GET['d'];
		$m = $_GET['m'];
		$y = $_GET['y'];
		$jour = date('N', mktime(0,0,0,$m,$d,$y));
		$heure=$_GET['h'];
		if ($_GET['mn'])
		  $minute=$_GET['mn'];
		if ($_GET['s'])
		  $seconde=$_GET['s'];
	}


	
	if (!$forcePaulo) {

	    list($tab_d, $podcastable, $nid) = get_emission($m,$d,$y, $jour, $heure, $bdd);

	}

	if(!$forcePaulo && count($tab_d) > 0) {
	      $resultat = create_json_emission(implode("|",$tab_d), $podcastable, $nid, $y . "-" . $m . "-" . $d . " " . $heure . ":00:00");
	}
	else {
		$resultat = create_json_paulo(getFromPaulo($y, $m, $d, $heure, $minute, $seconde));

	}
	//	$resultat['Err'] = "Erreur";
	
	return $resultat;
}

function whatIsForPodcast($bdd)
{
	$resultat = array();
	date_default_timezone_set('Europe/Paris');
	
	
	$d = $_GET['d'];
	$m = $_GET['m'];
	$y = $_GET['y'];
	$heure = date("H");
			
	$jour = date('N', mktime(0,0,0,$m,$d,$y));
		
	$sql = "SELECT distinct field_heure_value
			FROM `drupal_field_revision_field_jour` as j, `drupal_field_revision_field_heure` as h, `drupal_field_data_field_en_cours` as c, `drupal_field_data_field_duree` as d,
			WHERE j.bundle LIKE 'emission'
			AND j.deleted=0
			AND c.field_en_cours_value = 1
			AND j.entity_id=h.entity_id
			AND c.entity_id=h.entity_id
			AND d.entity_id=h.entity_id
			AND j.field_jour_value = ".$jour;
			
	$prep = $bdd->query($sql);

	$tab_d = array();
	while ($donnees = $prep->fetch(PDO::FETCH_OBJ))
	{
		$tab_d[]=$donnees->field_heure_value;
	}
	
	$resultat['forpodcast'] = $tab_d;
	
	return $resultat;
}

function getFromPaulo($y, $m, $d, $heure, $minute, $seconde, $again = true) {
	date_default_timezone_set('Europe/Paris');

	$bdd = new PDO('mysql:host='._PAULODB_SERVEUR.';dbname='._PAULODB_BD, _PAULODB_LOGIN, _PAULODB_MDP, $options);

	$datetime = $y."-".$m."-".$d." ".$heure.':'.$minute.':'.$seconde;
	$sql = "Select * From titres_paulo where begin <= ".$bdd->quote($datetime)." and end >= ".$bdd->quote($datetime)." and begin >= date_sub(".$bdd->quote($datetime).", interval 1 hour);";
	
	$prep = $bdd->query($sql);
	
	$prep->execute();
	$row = $prep->fetch();

	//print_r($row);
	$resultat["title"]=$row["title"];
	$resultat["author"]=$row["author"];
	$resultat["begin"]=$row["begin"];
	$resultat["end"]=$row["end"];

	if (!$resultat["author"] && $again) {
	  $sql = "Select max(begin) > DATE_SUB(NOW(), INTERVAL 15 MINUTE) as toBeUpdated From titres_paulo  where begin < now()";
	  $prep = $bdd->query($sql);
	
	  $prep->execute();
	  $row = $prep->fetch();

	  if ($row["toBeUpdated"] == "1") {
	    $html = implode('', file('http://www.campus-clermont.net/ws/log_paulo.php'));

	    return getFromPaulo($y, $m, $d, $heure, $minute, $seconde, false);
	    }
	}
	
	return $resultat;	
}

function emissionsSimilaires($bdd) {
	$title = $bdd->quote($_GET["t"]);
	$sql = "SELECT DISTINCT n2.nid as nid, n2.title as title FROM `drupal_field_data_field_emissions_similaires` as s, drupal_node as n, drupal_node as n2
			WHERE s.bundle LIKE 'emission'
			AND n.title = ".$title."
			AND ((s.entity_id = n.vid
			AND s.field_emissions_similaires_target_id = n2.vid) OR 
			(s.entity_id = n2.vid
			AND s.field_emissions_similaires_target_id = n.vid));";
	$prep = $bdd->query($sql);
	  $resultat = array();
	   while ($donnees = $prep->fetch(PDO::FETCH_OBJ)) {
		 $resultat[] = $donnees;
	    }

  return $resultat;
}

function imageEmission($bdd) {
	date_default_timezone_set('Europe/Paris');
	
	
	$d = $_GET['d'];
	$m = $_GET['m'];
	$y = $_GET['y'];
	
	$heure=$_GET['h'];

	if (isset($d) && isset($m) && isset($y))
	  $jour = date('N', mktime(0,0,0,$m,$d,$y));

	$title = $bdd->quote($_GET["t"]);

	  $sql = "SELECT n.title, j.field_jour_value, h.field_heure_value, fm.uri from drupal_node as n
			LEFT JOIN drupal_field_data_field_heure as h ON h.entity_id = n.nid
			LEFT JOIN drupal_field_data_field_duree as d ON d.entity_id = n.nid
			LEFT JOIN drupal_field_data_field_jour as j ON j.entity_id = n.nid
			LEFT JOIN drupal_field_data_field_photo as ph ON ph.entity_id = n.nid
			LEFT JOIN drupal_file_managed as fm ON fm.fid = ph.field_photo_fid
			WHERE n.type LIKE 'emission'";
	if (isset($jour))
	    $sql .= " AND j.field_jour_value = {$jour}";
	if (isset($heure))
		$sql .= " AND (h.field_heure_value = {$heure} OR (h.field_heure_value = ".($heure-1)." AND (d.field_duree_value = '2h' or d.field_duree_value = '3h'))
			OR (h.field_heure_value = ".($heure-2)." AND (d.field_duree_value = '3h')))";
	$sql .= " AND n.title = ".$title."
			GROUP BY n.nid;";

//   $sql = "SHOW COLUMNS FROM drupal_field_data_field_photo ";
	$prep = $bdd->query($sql);
	  $resultat = array();
	   while ($donnees = $prep->fetch(PDO::FETCH_OBJ)) {
                 $donnees->uri = str_replace("public://", "", $donnees->uri);
                 if ($donnees->uri != "")
                    $donnees->uri = '/sites/default/files/' . $donnees->uri;
		 $resultat[] = $donnees;
		 
	    }

  return $resultat;
}

?>

