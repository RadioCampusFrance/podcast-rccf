<!DOCTYPE html>
<html>
<head>
<meta charset=utf-8 />
<?php

include 'configPaulo.php';

function get_period($year, $month, $day, &$beginDate, &$endDate, &$period) {
  $period = $_GET["period"];
  if (isset($period)) {
    if ($period != "month") // set here the allowed periods
      $period = "month";
  }
  else {
    $period = "month";
  }
  
  if ($period == "month") {
    $beginDate = $year."-".$month."-01";
    $mois = mktime( 0, 0, 0, $month, 1, $year ); 
    $endDate = $year."-".$month."-".date("t", $mois);
  }
  else {
    $beginDate = $endDate = $year."-".$month."-".$day;
    }
}

function get_current_date(&$date, &$day, &$month, &$year) {
  $date = date ("Y-m-d");
  $month = date("m");
  $year = date("Y");
}

function get_date(&$date, &$day, &$month, &$year) {
  $date = $_GET['date'];
  $pattern = '/^([0-9][0-9][0-9][0-9])-([0-9][0-9])-([0-9][0-9])/';
  preg_match($pattern, $date, $matches);
  if (count($matches) != 4) {
    $pattern = '/^([0-9][0-9][0-9][0-9])-([0-9][0-9])/';
    preg_match($pattern, $date, $matches);
    if (count($matches) != 3) {
      get_current_date($date, $day, $month, $year);
      return;
    }
    else {
      $day = "01";
      $month = $matches[2];
      $year = $matches[1];
    }
  }
  else {
    $day = $matches[3];
    $month = $matches[2];
    $year = $matches[1];
  }

  if (!checkdate ($month, $day, $year)) {
    get_current_date($date, $day, $month, $year);
  }

}


function load_podcasts_day($day) {
  $result = array();

  
	$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

	$bdd = new PDO('mysql:host='._PAULODB_SERVEUR.';dbname='._PAULODB_BD, _PAULODB_LOGIN, _PAULODB_MDP, $options);

	$sql = "SET SESSION group_concat_max_len = 1000000;";
	$prep = $bdd->query($sql);
	
    $sql = "Select *, count(*) as count, sum(if (download = 1, 1, 0)) as nb_download, sum(if (download = 1, 0, 1)) as nb_player, group_concat(\"<strong>\", dl_ip, '</strong>: <span>', dl_useragent, '</span>' ORDER BY dl_ip DESC SEPARATOR \"<br />\") as infos From log_ecoute where TO_DAYS( timeslot ) = TO_DAYS(".$bdd->quote($day).") group by timeslot order by count(*) desc, timeslot desc;";
	
  $prep = $bdd->query($sql);
	
  $prep->execute();
  for($i=0; $row = $prep->fetch(); $i++){
    $result[$row["timeslot"]] = $row;
  }


  return $result;


}

function load_ecoutes_day($day) {
  $result = array();

  
	$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

	$bdd = new PDO('mysql:host='._PAULODB_SERVEUR.';dbname='._PAULODB_BD, _PAULODB_LOGIN, _PAULODB_MDP, $options);

		$sql = "SET SESSION group_concat_max_len = 1000000;";
	$prep = $bdd->query($sql);

    $sql = "Select *, count(*) as count, sum(if (download = 1, 1, 0)) as nb_download, sum(if (download = 1, 0, 1)) as nb_player, group_concat(\"<strong>\", dl_ip, '</strong>: <span>', dl_useragent, '</span>' ORDER BY dl_ip DESC SEPARATOR \"<br />\") as infos  From log_ecoute where dl_day = ".$bdd->quote($day)." group by timeslot order by count(*) desc, timeslot desc;";
	
  $prep = $bdd->query($sql);
	
  $prep->execute();
  for($i=0; $row = $prep->fetch(); $i++){
    $result[$row["timeslot"]] = $row;
  }


  return $result;


}

function load_auditeur($start, $end, $byEcoute) {
  $result = array();

  
	$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

	$bdd = new PDO('mysql:host='._PAULODB_SERVEUR.';dbname='._PAULODB_BD, _PAULODB_LOGIN, _PAULODB_MDP, $options);

		$sql = "SET SESSION group_concat_max_len = 1000000;";
	$prep = $bdd->query($sql);

	if ($byEcoute)
	  $sql = "Select count(*) as count, sum(if (download = 1, 1, 0)) as nb_download, sum(if (download = 1, 0, 1)) as nb_player, dl_ip, dl_useragent From log_ecoute where dl_day >= ".$bdd->quote($start)." and dl_day <= ".$bdd->quote($end)." group by dl_ip, dl_useragent order by count(*) desc;";
	else
	$sql = "Select count(*) as count, sum(if (download = 1, 1, 0)) as nb_download, sum(if (download = 1, 0, 1)) as nb_player, dl_ip, dl_useragent From log_ecoute where to_days(timeslot) >= to_days(".$bdd->quote($start).") and to_days(timeslot) <= to_days(".$bdd->quote($end).") group by dl_ip, dl_useragent order by count(*) desc;";
	
  $prep = $bdd->query($sql);
	
  $prep->execute();
  for($i=0; $row = $prep->fetch(); $i++){
    $result[] = $row;
  }


  return $result;


}

function load_auditeur_day($day, $byEcoute) {
  $result = array();

  
	$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

	$bdd = new PDO('mysql:host='._PAULODB_SERVEUR.';dbname='._PAULODB_BD, _PAULODB_LOGIN, _PAULODB_MDP, $options);

		$sql = "SET SESSION group_concat_max_len = 1000000;";
	$prep = $bdd->query($sql);

	if ($byEcoute)
	  $sql = "Select count(*) as count, sum(if (download = 1, 1, 0)) as nb_download, sum(if (download = 1, 0, 1)) as nb_player, dl_ip, dl_useragent From log_ecoute where dl_day = ".$bdd->quote($day)." group by dl_ip, dl_useragent order by count(*) desc;";
	else
	$sql = "Select count(*) as count, sum(if (download = 1, 1, 0)) as nb_download, sum(if (download = 1, 0, 1)) as nb_player, dl_ip, dl_useragent From log_ecoute where to_days(timeslot) = to_days(".$bdd->quote($day).") group by dl_ip, dl_useragent order by count(*) desc;";
	
  $prep = $bdd->query($sql);
	
  $prep->execute();
  for($i=0; $row = $prep->fetch(); $i++){
    $result[] = $row;
  }


  return $result;


}

function load_ecoutes($start, $end, $byEcoute) {
  $result = array();

  
	$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

	$bdd = new PDO('mysql:host='._PAULODB_SERVEUR.';dbname='._PAULODB_BD, _PAULODB_LOGIN, _PAULODB_MDP, $options);

  if ($byEcoute) {
    $sql = "Select dl_day as day, sum(if (download = 1, 1, 0)) as nb_download, sum(if (download = 1, 0, 1)) as nb_player, count(*) as nb From log_ecoute where (dl_day >= ".$bdd->quote($start)." and dl_day <= ".$bdd->quote($end).") group by dl_day order by count(*) desc;";
  }
  else {
    $datetimeStart = $start . " 00:00:00";
    $datetimeEnd = $end . " 23:59:59";
    $sql = "Select timeslot as day, sum(if (download = 1, 1, 0)) as nb_download, sum(if (download = 1, 0, 1)) as nb_player,  count(*) as nb From log_ecoute where (timeslot >= ".$bdd->quote($datetimeStart)." and timeslot <= ".$bdd->quote($datetimeEnd).") group by timeslot order by count(*) desc;";
  }
	
  $prep = $bdd->query($sql);
	
  $prep->execute();
  for($i=0; $row = $prep->fetch(); $i++){
    $result[$row["day"]][0] = $row["nb_player"];
    $result[$row["day"]][1] = $row["nb_download"];
    $result[$row["day"]]["nb"] = $row["nb"];
  }


  return $result;
}

function load_ecart($start, $end, $nb = 1) {
  $result = array();

	$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

	$bdd = new PDO('mysql:host='._PAULODB_SERVEUR.';dbname='._PAULODB_BD, _PAULODB_LOGIN, _PAULODB_MDP, $options);

    $sql = "Select count(*) as nb, FLOOR((TO_DAYS( dl_day ) - TO_DAYS( timeslot )) / ".$nb.") as ecart From log_ecoute where (dl_day >= ".$bdd->quote($start)." and dl_day <= ".$bdd->quote($end).") group by FLOOR((to_days(dl_day) - to_days(timeslot)) / ".$nb.") order by FLOOR((TO_DAYS( dl_day ) - TO_DAYS( timeslot )) / ".$nb.")";
  
  $prep = $bdd->query($sql);
	
  $prep->execute();
  for($i=0; $row = $prep->fetch(); $i++){
    $download = intval($row["download"]);
    $result[intval($row["ecart"])] = $row["nb"];
  }

  return $result;
}

function load_day_podcast($start, $end) {
  $result = array();

	$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

	$bdd = new PDO('mysql:host='._PAULODB_SERVEUR.';dbname='._PAULODB_BD, _PAULODB_LOGIN, _PAULODB_MDP, $options);

    $sql = "Select count(*) as nb, EXTRACT(DAY from timeslot ) as day From log_ecoute where (timeslot >= ".$bdd->quote($start)." and timeslot <= ".$bdd->quote($end).") group by to_days(timeslot)";
  
  $prep = $bdd->query($sql);
	
  $prep->execute();
  for($i=0; $row = $prep->fetch(); $i++){
    $download = intval($row["download"]);
    $result[intval($row["day"])] = $row["nb"];
  }

  return $result;
}

function load_hour_podcast($start, $end) {
  $result = array();

	$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

	$bdd = new PDO('mysql:host='._PAULODB_SERVEUR.';dbname='._PAULODB_BD, _PAULODB_LOGIN, _PAULODB_MDP, $options);

    $sql = "Select count(*) as nb, EXTRACT(HOUR from timeslot ) as hour From log_ecoute where (timeslot >= ".$bdd->quote($start)." and timeslot <= ".$bdd->quote($end).") group by EXTRACT(HOUR from timeslot )";
  
  $prep = $bdd->query($sql);
	
  $prep->execute();
  for($i=0; $row = $prep->fetch(); $i++){
    $download = intval($row["download"]);
    $result[intval($row["hour"])] = $row["nb"];
  }

  return $result;
}

function get_json($date) {
  $file_day = "../OK/".$date."/config.txt";
  if (file_exists($file_day))
    return json_decode(file_get_contents($file_day));
  else
    return null;
}

// ajoute à chaque entrée le nom du podcast
function load_podcasts(&$ecoutes) {
  $jsons = array();
  
  
  foreach($ecoutes as $when => $ecoute) {
    $datetime = explode(" ", $when);
    $hour = $datetime[1];
    $date = $datetime[0];
    $hour = explode(":", $hour);
    $hour = $hour[0];
    // on charge d'abord le json si nécessaire
    
    if (!isset($jsons[$date]))
      $jsons[$date] = get_json($date);

    $ecoutes[$when]["title"] = "Titre non défini";
    if (isset($jsons[$date]))
      foreach($jsons[$date]->track as $entry) {
	// puis on remplis le titre s'il existe	
	if ($entry->time == $hour) {
	  $ecoutes[$when]["title"] = $entry->title;
	  break;
	}
      }
  }
  
  
}


function get_hour($day) {
     $day = explode(" ",$day);
     $hour = $day[1];
     $hour = explode(":",$hour);
     return intval($hour[0]);
}


function get_day($day) {
      $day = explode(" ",$day);
      $day = $day[0];
      $day = explode("-",$day);
      return intval($day[2]);
}

function get_mois($day, $month, $year) {
  $val = date("D", mktime( 0, 0, 0, $month, $day, $year ));
  switch ($val) {
    case "Mon":
      $val = "lun";
      break;
    case "Tue":
      $val = "mar";
      break;
    case "Wed":
      $val = "mer";
      break;
    case "Thu":
      $val = "jeu";
      break;
    case "Fri":
      $val = "ven";
      break;
    case "Sat":
      $val = "sam";
      break;
    case "Sun":
      $val = "dim";
      break;
    default:
      
  }
  return $val;
}


function adjust_when($when, $m) {
  $f = explode(" ", $when);
  $day = explode("-", $f[0]);
  $day = intval($day[2]);

  $hour = explode(":", $f[1]);
  $hour = intval($hour[0]);

  return $day . " " . $m . " ". $hour . "h";
}

	setlocale (LC_ALL, 'fr_FR.utf8','fra');
	date_default_timezone_set('Europe/Paris');
	get_date($date, $day, $month, $year);
	get_period($year, $month, $day, $beginDate, $endDate, $period);
	
	$byEcoute = $_GET["ecoute"];
	if (!isset($byEcoute))
	  $byEcoute = false;
	else
	  $byEcoute = true;
	  
	/*$display = $_GET["display"];
	if (isset($display)) {
	  if ($display != "list" && $display != "diagrams") // set here the displays
	    $display = "list";
	}
	else
	  $display = "list";*/

	$ecoutes = load_ecoutes($beginDate, $endDate, $byEcoute);
	
	if (!$byEcoute) {
	  load_podcasts($ecoutes);
	}

?>
<title>Statistiques du podcast</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
<link rel="stylesheet" href="css/circle.player.css">
<link rel="stylesheet" href="css/admin.css?date=<?php echo filemtime('css/admin.css');?>">
<link rel="stylesheet" href="css/stats.css?date=<?php echo filemtime('css/stats.css');?>">
<link rel="stylesheet" href="css/ui-lightness/jquery-ui-1.10.4.custom.min.css">
<script type="text/javascript" src="js/jquery-1.10.2.js"></script>
<script type="text/javascript" src="js/jquery.jplayer.min.js"></script>
<script type="text/javascript" src="js/jquery.transform2d.js"></script>
<script type="text/javascript" src="js/jquery.grab.js"></script>
<script type="text/javascript" src="js/jquery.ui.datepicker-fr.js"></script>
<script type="text/javascript" src="js/mod.csstransforms.min.js"></script>
<script type="text/javascript" src="js/circle.player.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.10.4.custom.min.js"></script>
<!--[if lt IE 9]><script language="javascript" type="text/javascript" src="js/excanvas.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="js/jquery.jqplot.min.js"></script>
<link rel="stylesheet" type="text/css" href="css/jquery.jqplot.min.css" />
<script type="text/javascript" src="js/plugins/jqplot.barRenderer.min.js"></script>
<script type="text/javascript" src="js/plugins/jqplot.pointLabels.min.js"></script>
<script type="text/javascript" src="js/plugins/jqplot.canvasAxisLabelRenderer.min.js"></script>
<script type="text/javascript" src="js/plugins/jqplot.canvasTextRenderer.min.js"></script>
<script type="text/javascript" src="js/plugins/jqplot.canvasAxisTickRenderer.min.js"></script>
<script type="text/javascript" src="js/plugins/jqplot.categoryAxisRenderer.min.js"></script>
<script>
$(document).ready(function(){
     $( document ).tooltip();
      $('.title').tooltip({
    content: function() {
        return $(this).attr('title');
    },
    open: function (event, ui) {
        ui.tooltip.css("max-width", "500px");
    }
    });
      $( "#datepicker" ).datepicker({changeMonth: true,
				      changeYear: true,
				      dateFormat: "yy-mm-dd",
				      regional: "fr",
				      defaultDate: "<?php echo $date; ?>",
				      onChangeMonthYear: function (year, month, inst) {
				      $(this).datepicker( "setDate", year + "-" + month + '-01');}});
      $( "#change-date" ).button().click(function() {changeDate()});
      $( "#change-date-aujourdhui" ).button().click(function() {aujourdHui()});
      
      $( "#tabs" ).tabs();
      
      <?php if ($byEcoute) {
      
	echo "var plot1 = $.jqplot('chartdiv',  [[";
	$begin = explode("-", $beginDate);
	$begin = $begin[count($begin) - 1];
	$end = explode("-", $endDate);
	$end = $end[count($end) - 1];
	for($i = intval($begin); $i < intval($end) + 1; $i++) {
	  if (intval($begin) != $i)
	    echo ", ";
	  /*echo "[ '".get_mois($i, $month, $year)." ".$i ."', ";*/
	  if ($i < 10)
	    $d = $year . "-" . $month . "-0".$i;
	  else
	    $d = $year . "-" . $month . "-".$i;
	  if (isset($ecoutes[$d]["0"])) {
	    echo $ecoutes[$d]["0"];
	  }
	  else echo "0";
	  //echo "]";
	}
	echo "],[";
		for($i = intval($begin); $i < intval($end) + 1; $i++) {
	  if (intval($begin) != $i)
	    echo ", ";
	  /*echo "[ '".get_mois($i, $month, $year)." ".$i ."', ";*/
	  if ($i < 10)
	    $d = $year . "-" . $month . "-0".$i;
	  else
	    $d = $year . "-" . $month . "-".$i;
	  if (isset($ecoutes[$d]["1"])) {
	    echo $ecoutes[$d]["1"];
	  }
	  else echo "0";
	  //echo "]";
	}
/*for($i = intval($begin); $i < intval($end) + 1; $i++) {
	  if (intval($begin) != $i)
	    echo ", ";
	  echo "[ '".get_mois($i, $month, $year)." ".$i ."', ";
	  if ($i < 10)
	    $d = $year . "-" . $month . "-0".$i;
	  else
	    $d = $year . "-" . $month . "-".$i;
	  if (isset($ecoutes[$d]["nb"])) {
	    echo $ecoutes[$d]["nb"];
	  }
	  else echo "0";
	  echo "]";
	}*/
	
	?>]], {
	    stackSeries: true,
	    seriesDefaults:{
	      renderer:$.jqplot.BarRenderer,
	      rendererOptions: {
	      barDirection: 'vertical',
	      barMargin: 25
	     },
	     pointLabels: {show: true}
	     },
	     axesDefaults: {
        labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
	tickRenderer: $.jqplot.CanvasAxisTickRenderer,
        tickOptions: {
          angle: -30,
          fontSize: '10pt'
	}
      },
      "legend":{
    "show":true,"location":"ne"
	},
	"series":[
    {
      "label":"Écoutes par player"
    },{
      "label":"Téléchargements"
    }
  ],
	      axes: {
	  xaxis: {
	  renderer: $.jqplot.CategoryAxisRenderer,
          label: "Jour du mois",
          "ticks": [<?php
          
          for($i = intval($begin); $i < intval($end) + 1; $i++) {
	  if (intval($begin) != $i)
	    echo ", ";
	  echo "'".get_mois($i, $month, $year)." ".$i ."'";
	  }
          
          ?>]
      },
      yaxis: {
          label: "Nombre d'écoutes",
          padMin: 0
      }},
 	    title: "Nombre d'écoutes par jour",
	    });
	    
      $('#chartdiv').bind('jqplotDataClick', function (ev, seriesIndex, pointIndex, data) {
	  var day = (pointIndex + 1).toString();
	  if (day.length < 2)
	    day = "0" + day;
	  var date = '<?php echo $year . "-" . $month . "-"; ?>' + day;
	  window.parent.location.href = "/onair/podcast/admin/stats.php?date=" + date + "&ecoute=1#list-day";
      });
$('#chartdiv').on('jqplotDataHighlight', function () {
   $('.jqplot-event-canvas').css( 'cursor', 'pointer' );
});
$('#chartdiv').on('jqplotDataUnhighlight', function() {
    $('.jqplot-event-canvas').css('cursor', 'auto');
});

      <?php
	echo "var plot2 = $.jqplot('chartdiv2',  [[";
      $ecarts = load_ecart($beginDate, $endDate);	
      $first = true;
      foreach($ecarts as $ec => $nb) {
	  if ($ec > 30)
	    break;
	  if (!$first)
	    echo ", ";
	    echo "['".($ec+1). "e jour', " .$nb."]";
	  $first = false;
	}
	?>]], {
	    seriesDefaults:{
	      renderer:$.jqplot.BarRenderer,
	      rendererOptions: {
	     },
	     pointLabels: {show: true}
	     },
	     axesDefaults: {
        labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
	tickRenderer: $.jqplot.CanvasAxisTickRenderer,
        tickOptions: {
          angle: -30,
          fontSize: '10pt'
        }
      },
	      axes: {
	  xaxis: {
	  renderer: $.jqplot.CategoryAxisRenderer,
          label: "Nombre de jours d'écart (30 premiers jours)",
      },
      yaxis: {
          label: "Nombre d'écoutes",
          padMin: 0
      }
    },

	    title: "Nombre d'écoutes par écart entre enregistrement et écoute"
	    });
      <?php
	echo "var plot3 = $.jqplot('chartdiv3',  [[";
      $ecarts = load_ecart($beginDate, $endDate, 7);
      $first = true;
      foreach($ecarts as $ec => $nb) {
	  if ($ec > 30)
	    break;
	  if (!$first)
	    echo ", ";
	  echo "['".($ec+1). "e semaine', " .$nb."]";
	  $first = false;
	}
	?>]], {
	    seriesDefaults:{
	      renderer:$.jqplot.BarRenderer,
	      rendererOptions: {
	     },
	     pointLabels: {show: true}
	     },
	     axesDefaults: {
        labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
	tickRenderer: $.jqplot.CanvasAxisTickRenderer,
        tickOptions: {
          angle: -30,
          fontSize: '10pt'
          }
      },
	      axes: {
	  xaxis: {
	  renderer: $.jqplot.CategoryAxisRenderer,
          label: "Nombre de semaines d'écart",
      },
      yaxis: {
          label: "Nombre d'écoutes",
          padMin: 0
      }
    },

	    title: "Nombre d'écoutes par écart entre enregistrement et écoute"
	    });
      <?php } else { 
	if (count($ecoutes) != 0) {

	echo "var plot1 = $.jqplot('chartdiv',  [[";
	$first = true;
	$monthTxt = strftime("%B",strtotime($date));
	$nbit = 0;
	foreach($ecoutes as $when => $ecoute) {
	  $nb = $ecoute["nb"];
	    if (!$first)
	      echo ", ";
	    echo "[ '".str_replace("'", "\\'", substr($ecoute["title"], 0, 32))." ".adjust_when($when, $monthTxt)."', ".$nb."]";
	    $first = false;
	 $nbit += 1;
	  if ($nbit > 25)
	    break;
	  
	}
	?>]], {
	    seriesDefaults:{
	      renderer:$.jqplot.BarRenderer,
	      rendererOptions: {
	     },
	     pointLabels: {show: true}
	     },
	     axesDefaults: {
        labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
	tickRenderer: $.jqplot.CanvasAxisTickRenderer,
        tickOptions: {
          angle: -30,
          fontSize: '10pt'
        }
      },
	      axes: {
	  xaxis: {
	  renderer: $.jqplot.CategoryAxisRenderer,
          label: "Podcast",
      },
      yaxis: {
          label: "Nombre d'écoutes",
          padMin: 0
      }
    },

	    title: "Podcasts par nombre d'écoute (25 premiers)"
	    });
<?php }
      $numb = load_day_podcast($beginDate, $endDate);	
      if (count($numb) != 0) {
    
      $first = true;
      echo "window.line = [[";
      foreach($numb as $ec => $nb) {
	  if (!$first)
	    echo ", ";
	  echo "['".get_mois($ec, $month, $year)." ".$ec."', " .$nb."]";
	  $first = false;
	}
	
	?>]];
	
	var plot2 = $.jqplot('chartdiv2', window.line, {
	    seriesDefaults:{
	      renderer:$.jqplot.BarRenderer,
	      rendererOptions: {
	     },
	     pointLabels: {show: true}
	     },
	     axesDefaults: {
        labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
	tickRenderer: $.jqplot.CanvasAxisTickRenderer,
        tickOptions: {
          angle: -30,
          fontSize: '10pt'
        }
      },
	      axes: {
	  xaxis: {
	  renderer: $.jqplot.CategoryAxisRenderer,
          label: "Jour du mois",
      },
      yaxis: {
          label: "Nombre d'écoutes",
          padMin: 0
      }
    },

	    title: "Nombre d'écoutes de podcast par jour"
	    });

      $('#chartdiv2').bind('jqplotDataClick', function (ev, seriesIndex, pointIndex, data) {
	  var day = window.line[0][pointIndex][0].split(" ")[1];
	  if (day.length < 2)
	    day = "0" + day;
	  var date = '<?php echo $year . "-" . $month . "-"; ?>' + day;
	  window.parent.location.href = "/onair/podcast/admin/stats.php?date=" + date + "#list-day";
      });
$('#chartdiv2').on('jqplotDataHighlight', function () {
   $('.jqplot-event-canvas').css( 'cursor', 'pointer' );
            });
$('#chartdiv2').on('jqplotDataUnhighlight', function() {
    $('.jqplot-event-canvas').css('cursor', 'auto');
});

<?php }

      $numb = load_hour_podcast($beginDate, $endDate);	
      if (count($numb) != 0) {
	echo "var plot3 = $.jqplot('chartdiv3',  [[";
    
      $first = true;
      foreach($numb as $ec => $nb) {
	  if (!$first)
	    echo ", ";
	  echo "['".$ec. "h', " .$nb."]";
	  $first = false;
	}
	?>]], {        labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
	    seriesDefaults:{
	      renderer:$.jqplot.BarRenderer,
	      rendererOptions: {
	     },
	     pointLabels: {show: true}
	     },
	     axesDefaults: {
        labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
	tickRenderer: $.jqplot.CanvasAxisTickRenderer,
        tickOptions: {
          angle: -30,
          fontSize: '10pt'
        }
      },
	      axes: {
	  xaxis: {
	  renderer: $.jqplot.CategoryAxisRenderer,
          label: "Heure du jour",
      },
      yaxis: {
          label: "Nombre d'écoutes",
          padMin: 0
      }
    },

	    title: "Nombre d'écoutes de podcast par heure"
	    });

	<?php } } ?>
       $('#tabs').bind('tabsactivate', function(event, ui) {
      if (ui.newTab.index() === 0 && plot1._drawCount === 0) {
        plot1.replot();
      }
      if (ui.newTab.index() === 0 && plot2._drawCount === 0) {
        plot2.replot();
      }
      if (ui.newTab.index() === 0 && plot3._drawCount === 0) {
        plot3.replot();
      }
    });
    });

function get_selected_display() {
 var active = $( "#tabs" ).tabs( "option", "active" );
 if (active == 1) 
  return "list";
 else if (active == 0)
  return "diagrams";
 else return "";
}
    
function changeDate() {
    var display = get_selected_display();
    var date = $.datepicker.formatDate("yy-mm-dd", $("#datepicker").datepicker('getDate'));
    window.location.assign("/onair/podcast/admin/stats.php?date=" + date + "<?php 
    if ($byEcoute)
      echo "&ecoute=1";
    echo "&period=".$period;
?>#"+ display);
}

function aujourdHui() {
    var display = get_selected_display();
    window.location.assign("/onair/podcast/admin/stats.php?date=<?php 
    echo date ("Y-m-d");
    if ($byEcoute)
      echo "&ecoute=1";
    echo "&period=".$period;
?>#"+ display);
}
  </script>
</head>
<body>
<div id="left">
			<div style="clear: both;float: right; font-size: 80%"><a href="/onair/podcast/admin/index.php?date=<?php echo $date;?>&amp;period=month">Administrer les podcasts</a></div>

  <h2 style="clear:both">Navigation</h2>
  <div id="datepicker"></div>
  <input id="change-date" type="submit" value="Aller à la date"></input>
  <input id="change-date-aujourdhui" type="submit" value="Aujourd'hui"></input> 
</div>
<div id="right">
<?php 
  if ($period == "month") {
    echo "<h2>Statistiques de ".strftime("%B %Y",strtotime($date))."</h2>";
  }
  if ($byEcoute) {
    echo '<p>Affichage des statistiques par jour d\'écoute. Pour afficher par jour du contenu, <a href="stats.php?date='.$date.'&amp;period='.$period.'">suivez ce lien</a>.</p>';
  }
  else {
    echo '<p>Affichage des statistiques par jour du contenu. Pour afficher par jour d\'écoute, <a href="stats.php?date='.$date.'&amp;period='.$period.'&ecoute=1">suivez ce lien</a>.</p>';
  }
  
  ?>
  <div id="tabs">
  <ul><li><a href="#diagrams">Diagrammes</a></li>
    <li><a href="#list"><?php if ($byEcoute) echo "Écoutes"; else echo "Écoutes des podcasts"; ?> du mois</a></li>
    <li><a href="#list-day"><?php if ($byEcoute) echo "Écoutes"; else echo "Écoutes des podcasts"; ?> du jour</a></li>
    </ul>
  <div id="list">
  <?php
    echo "<h2>Nombre d'écoutes par jour</h2>";
    echo "<ul class=\"ui-widget ui-widget-content ui-helper-clearfix ui-corner-all array\">";
    echo '<li class="ui-widget-header ui-helper-clearfix ui-corner-all header"><span class="day first" title="jour du mois">Jour</span>';
    if (!$byEcoute) {
      echo '<span class="heure">Heure</span>';
    }
    echo '<span class="nombre_ecoutes" title="total">Total</span>';
    echo '<span class="nombre_ecoutes" title="téléchargement">Téléchargement</span>';
    echo '<span class="nombre_ecoutes" title="player">Player</span>';
    if (!$byEcoute) {
      echo '<span class="title">Titre</span>';
    }
    echo "</li>";
    $nb = 0;
    foreach($ecoutes as $day => $ecoute) {
      echo '<li><span class="day">'.get_day($day, $period).' '.strftime("%B",strtotime($date)).'</span>';
      if (!$byEcoute) {
	  echo ' <span class="heure">'.get_hour($day).':00</span>';
      }
      echo '<span class="nombre_ecoutes" title="total"><strong>'.$ecoute["nb"].'</strong></span>';
      echo '<span class="nombre_ecoutes" title="téléchargement"><strong>'.$ecoute[1].'</strong></span>';
      echo '<span class="nombre_ecoutes';
      if ($byEcoute) {
	echo " last";
      }
      echo '" title="player"><strong>'.$ecoute[0].'</strong></span>';
      $nb = $nb + $ecoute["nb"];
      if (!$byEcoute) {
	  echo ' <span class="title" title="'.str_replace("\"", "\\\"", $ecoute["title"]).'">'.$ecoute["title"].'</span>';
      }
      echo "</li>";
    }
    echo "</ul>";
    echo "<ul class=\"ui-widget ui-widget-content ui-helper-clearfix ui-corner-all array\">";
    echo '<li><span class="day">Total</span> <span class="nombre_ecoutes">'.$nb.'</span></li>';
    echo "</ul>";
    
        echo "<p>Affichage par auditeur&nbsp;:</p>";
    // on s'intéresse maintenant aux auditeurs
        
            $auditeurs = load_auditeur($beginDate, $endDate, $byEcoute);
   
  echo "<ul class=\"ui-widget ui-widget-content ui-helper-clearfix ui-corner-all array\">";
    echo '<li class="ui-widget-header ui-helper-clearfix ui-corner-all header">';
    echo '<span class="first ip" title="IP auditeur">IP auditeur</span>';
    echo '<span class="useragent" title="Navigateur auditeur">Navigateur auditeur</span>';
    echo '<span class="nombre_ecoutes" title="total">Total</span>';
    echo '<span class="nombre_ecoutes" title="téléchargements">Téléchargements</span>';
    echo '<span class="nombre_ecoutes" title="player">Player</span></li>';
      foreach($auditeurs as $ecoute) {
	echo '<li><span class="ip" title="IP auditeur"><a href="http://www.localiser-ip.com/?ip='.$ecoute["dl_ip"].'">'.$ecoute["dl_ip"].'</a></span>';
	echo '<span class="useragent" title="Navigateur auditeur">'.$ecoute["dl_useragent"].'</span>';
	echo '<span class="nombre_ecoutes" title="Nombre d\'écoutes">'.$ecoute["count"].'</span>';
	echo '<span class="nombre_ecoutes" title="Nombre de téléchargements">'.$ecoute["nb_download"].'</span>';
	echo '<span class="nombre_ecoutes" title="Nombre d\'écoutes par player">'.$ecoute["nb_player"].'</span></li>';

      }

    echo "</ul>";


      echo "</div>";
      echo '<div id="list-day">';
      
    if ($byEcoute) {
      $ecoutesDay = load_ecoutes_day($date);

      echo "<h2 id=\"podcast-day\">Podcasts écoutés le ".strftime("%e %B %Y",strtotime($date))."</h2>";
    } else {
      $ecoutesDay = load_podcasts_day($date);

      echo "<h2 id=\"podcast-day\">Podcasts du ".strftime("%e %B %Y",strtotime($date))."</h2>";


    }
	  load_podcasts($ecoutesDay);

    echo "<ul class=\"ui-widget ui-widget-content ui-helper-clearfix ui-corner-all array\">";
    echo '<li class="ui-widget-header ui-helper-clearfix ui-corner-all header"><span class="first day" title="date">Date</span>';
    echo '<span class="heure" title="Heure">Heure</span>';
    echo '<span class="nombre_ecoutes" title="total">Total</span>';
    echo '<span class="nombre_ecoutes" title="téléchargements">Téléchargments</span>';
    echo '<span class="nombre_ecoutes" title="player">Player</span>';
    echo '<span class="title" title="Titre">Titre</span></li>';
      foreach($ecoutesDay as $ecoute) {
	$ddate = explode(" ", $ecoute["timeslot"]);
	$heure = explode(":", $ddate[1]);
	$jour = explode("-", $ddate[0]);
	if ($byEcoute)
	  echo '<li><span class="first day" title="date"><a href="/onair/podcast/admin/stats.php?date='.$ddate[0].'#list-day" title="voir les autres podcasts de ce jour">'.intval($jour[2]).' '.strftime("%B",strtotime($ecoute["timeslot"])).'</a></span>';
	else
	  echo '<li><span class="first day" title="date"><a href="/onair/podcast/admin/?date='.$ddate[0].'" title="aller à l\'administration de ce jour">'.intval($jour[2]).' '.strftime("%B",strtotime($ecoute["timeslot"])).'</a></span>';
	
	echo '<span class="heure" title="Heure">'.intval($heure[0]).'h</span>';
	echo '<span class="nombre_ecoutes" title="Nombre d\'écoutes">'.$ecoute["count"].'</span>';
	echo '<span class="nombre_ecoutes" title="Nombre de téléchargements">'.$ecoute["nb_download"].'</span>';
	echo '<span class="nombre_ecoutes" title="Nombre d\'écoutes par player">'.$ecoute["nb_player"].'</span>';
	echo '<span class="title" title="'.$ecoute["infos"].'">'.$ecoute["title"].'</span></li>';

      }

    echo "</ul>";
    
    echo "<p>Affichage par auditeur&nbsp;:</p>";
    // on s'intéresse maintenant aux auditeurs
        
            $auditeurs = load_auditeur_day($date, $byEcoute);
   
  echo "<ul class=\"ui-widget ui-widget-content ui-helper-clearfix ui-corner-all array\">";
    echo '<li class="ui-widget-header ui-helper-clearfix ui-corner-all header">';
    echo '<span class="first ip" title="IP auditeur">IP auditeur</span>';
    echo '<span class="useragent" title="Navigateur auditeur">Navigateur auditeur</span>';
    echo '<span class="nombre_ecoutes" title="total">Total</span>';
    echo '<span class="nombre_ecoutes" title="téléchargements">Téléchargements</span>';
    echo '<span class="nombre_ecoutes" title="Player">Player</span></li>';
    foreach($auditeurs as $ecoute) {
	echo '<li><span class="ip" title="IP auditeur"><a href="http://www.localiser-ip.com/?ip='.$ecoute["dl_ip"].'">'.$ecoute["dl_ip"].'</a></span>';
	echo '<span class="useragent" title="Navigateur auditeur">'.$ecoute["dl_useragent"].'</span>';
	echo '<span class="nombre_ecoutes" title="Nombre d\'écoutes">'.$ecoute["count"].'</span>';
	echo '<span class="nombre_ecoutes" title="Nombre de téléchargements">'.$ecoute["nb_download"].'</span>';
	echo '<span class="nombre_ecoutes" title="Nombre d\'écoutes par player">'.$ecoute["nb_player"].'</span></li>';

      }

    echo "</ul>";
    

  ?>
    </div>
    <div id="diagrams">
      <?php if ($byEcoute) { ?>
      <p>On s'intéresse dans le diagramme qui suit au nombre d'écoutes par jour. <em>Cliquer sur une barre pour avoir le détail du jour.</em></p>
      <?php } ?>
      <div id="chartdiv" style="height:700px;width:1200px;"></div>
      <?php if ($byEcoute) { ?>
      <p>On s'intéresse dans le diagramme qui suit à l'écart entre le jour d'écoute et le jour de l'enregistrement du podcast.</p>
      <?php } else { ?>
      <p>On s'intéresse dans le diagramme qui suit au nombre de podcasts écoutés d'un jour donné. <em>Cliquer sur une barre pour avoir le détail du jour.</em></p>
      <?php } ?>
      <div id="chartdiv2" style="height:700px;width:1200px;"></div>
      <?php if (!$byEcoute) { ?>
      <p>On s'intéresse dans le diagramme qui suit au nombre de podcasts écoutés d'un horaire donné.</p>
      <div id="chartdiv3" style="height:700px;width:1200px;"></div>
      <?php } else { ?>
      <div id="chartdiv3" style="height:700px;width:1200px;"></div>
      <?php } ?>
    </div>
  </div>
</div>
</body>
</html>

