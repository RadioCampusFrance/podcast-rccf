<!DOCTYPE html>
<html>
<head>
<meta charset=utf-8 />
<?php

include 'configPaulo.php';

function get_current_date(&$date, &$day, &$month, &$year) {
  $date = date ("Y-m-d");
  $day = date("d");
  $month = date("m");
  $year = date("Y");
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

  $datetime = $date . " 00:00:00";

	$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

	try {
	$bdd = new PDO('mysql:host='._PAULODB_SERVEUR.';dbname='._PAULODB_BD, _PAULODB_LOGIN, _PAULODB_MDP, $options);
      }
      	catch (PDOException $Exception ) {
	  return $result;
      	}
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


  return $result;
}

class Podcast {
  var $mp3;
  var $time;
  var $title;
  var $available;
  var $url;

  function __construct() {
    $a = func_get_args(); 
        $i = func_num_args(); 
        if (method_exists($this,$f='__construct'.$i)) { 
            call_user_func_array(array($this,$f),$a); 
        } 
  }
  function __construct2($mp3, $time) {
    $this->mp3 = ltrim($mp3);
    $this->time = $time;
    $this->title = "Non défini";
    $this->available = 0;
    $this->url = "";
  }

  function __construct1($jsonEntry) {
    $this->mp3 = ltrim($jsonEntry->mp3);
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
    if ($this->mp3 == "")
	$this->available = 1;
    else
	$this->available = 2;
    $this->url = ltrim($jsonEntry->url);
  }
  

}


function load_podcasts($jsonDay, $day) {
  $result = array();
  if ($jsonDay->track)
    foreach($jsonDay->track as $track) {
	$result[intval($track->time)] = new Podcast($track);
    }

  // ajouter les podcasts "autre"
  $dirname = "../KO/";
  $dir = opendir($dirname); 
  $pattern = "/^".$day."-([0-9][0-9])[0-9][0-9].mp3/i";
  while($file = readdir($dir)) {
    if($file != '.' && $file != '..' && !is_dir($dirname.$file) && preg_match($pattern,$file, $matches)) {
      $time = intval($matches[1]);
      if (!$result[$time] && $time < 24 && $time >= 0) {
	$result[$time] = new Podcast($file, $time);
      }
    }
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

	setlocale (LC_TIME, 'fr_FR.utf8','fra'); 
	get_date($date, $day, $month, $year);


	$jsonDay = get_json($date);

	
	
	$podcasts = load_podcasts($jsonDay, $date);

	$ecoutes = load_ecoutes($date);

?>
<title>Administration Podcast Radio Campus Clermont-Ferrand</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
<link rel="stylesheet" href="css/circle.player.css">
<link rel="stylesheet" href="css/admin.css?date=<?php echo filemtime('css/admin.css');?>">
<link rel="stylesheet" href="css/ui-lightness/jquery-ui-1.10.4.custom.min.css">
<script type="text/javascript" src="js/jquery-1.10.2.js"></script>
<script type="text/javascript" src="js/jquery.jplayer.min.js"></script>
<script type="text/javascript" src="js/jquery.transform2d.js"></script>
<script type="text/javascript" src="js/jquery.grab.js"></script>
<script type="text/javascript" src="js/jquery.ui.datepicker-fr.js"></script>
<script type="text/javascript" src="js/mod.csstransforms.min.js"></script>
<script type="text/javascript" src="js/circle.player.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.10.4.custom.min.js"></script>
<script>


var myCirclePlayer;
$(document).ready(function(){
	$( document ).tooltip();
		var showTimeLeft = function(event) {
			var time = event.jPlayer.status.currentTime;
			var timeDisplay = window.activeTime+":"+$.jPlayer.convertTime(time);
			var myDiv = document.getElementById("time");
			myDiv.innerHTML = timeDisplay;
		};

	myCirclePlayer = new CirclePlayer("#jquery_jplayer_1",
	{
	  }, {
		timeupdate: showTimeLeft,
		durationchange: showTimeLeft,
		supplied: "mp3",
		cssSelectorAncestor: "#cp_container_1",
		preload: "auto",
		swfPath: "js",
		wmode: "window",
		keyEnabled: true
	});

      $( "#datepicker" ).datepicker({changeMonth: true,
				      changeYear: true,
				      dateFormat: "yy-mm-dd",
				      regional: "fr",
				      defaultDate: "<?php echo $date; ?>"});
      $( "#change-date" ).button().click(function() {changeDate()});
      
    window.stateEntries = [];
    window.futureState = [];
  <?php for ($i = 0; $i != 24; ++$i) 
       if($podcasts[$i]) { ?>
    window.stateEntries[<?php echo $i;?>] = <?php echo $podcasts[$i]->available;?>;   
    $("#heure<?php echo $i; ?>").button().click(function() {
      <?php if ($podcasts[$i]->available == 2)
	echo "launch_track('".$podcasts[$i]->mp3."', 'OK', ".$i.");";
      else {
	if($podcasts[$i]->mp3 == "")
          if ($podcasts[$i]->time < 10)
            echo "launch_track('".$date."-0".$podcasts[$i]->time."00.mp3', 'KO', ".$i.");";
          else
            echo "launch_track('".$date."-".$podcasts[$i]->time."00.mp3', 'KO', ".$i.");";
	else
	  echo "launch_track('".$podcasts[$i]->mp3."', 'KO', ".$i.");";
	} ?>
      });
    $("#download<?php echo $i; ?>").button();

    $("#title<?php echo $i; ?>")
      .button()
      .css({
          'font' : 'inherit',
         'color' : 'inherit',
         'text-align' : 'left',
	 'width' : '290px',
	  'outline' : 'none',
	  'cursor' : 'text'}).focus(function() {
	  focusTitle(<?php echo $i; ?>);}).keyup(function(e) { keypressedTitle(<?php echo $i; ?>, e);});

	$("#url<?php echo $i; ?>")
      .button()
      .css({
          'font' : 'inherit',
         'color' : 'inherit',
         'text-align' : 'left',
	 'width' : '290px',
	  'outline' : 'none',
	  'cursor' : 'text'}).focus(function() {
	  focusUrl(<?php echo $i; ?>);}).keyup(function(e) { keypressedUrl(<?php echo $i; ?>, e);});

    $( "#radio<?php echo $i; ?>" ).buttonset();

      $( "#radio<?php echo $i; ?>" ).change(function() {
	  window.setActiveItem = <?php echo $i; ?>;
	  if ($( "#radio<?php echo $i; ?>-1" ).is(':checked'))
	    window.futureState[<?php echo $i; ?>] = 1;
	  if ($( "#radio<?php echo $i; ?>-2" ).is(':checked'))
	    window.futureState[<?php echo $i; ?>] = 2;
	  if ($( "#radio<?php echo $i; ?>-3" ).is(':checked'))
	    window.futureState[<?php echo $i; ?>] = 3;

	  if ($( "#radio<?php echo $i; ?>-2" ).is(':checked') && window.stateEntries[<?php echo $i; ?>] == 1) {
	      $( "#dialog-ko" ).dialog( "open" );
	   }
	  else if ($( "#radio<?php echo $i; ?>-1" ).is(':checked') || $( "#radio<?php echo $i; ?>-2" ).is(':checked')) {
	    var title = document.getElementById("title" + window.setActiveItem);
	    title.style.display = "block";
	    setVisibleValidation(<?php echo $i; ?>, true);
	    $("#title" + <?php echo $i; ?>).focus();
	  }
	  else if ($( "#radio<?php echo $i; ?>-3" ).is(':checked')) {
	    $( "#dialog-paulo" ).dialog( "open" );
            var url = document.getElementById("url<?php echo $i; ?>");
            url.style.display = "none";

	  }
	});

      $("#ok<?php echo $i; ?>").button().click(function() {
        setTrackOn(<?php echo $i; ?>);        
      });

      $("#cancel<?php echo $i; ?>").button().click(function() {cancelEditTitle(<?php echo $i; ?>);});

       $("#okurl<?php echo $i; ?>").button().click(function() {setUrl(<?php echo $i; ?>);});

      $("#cancelurl<?php echo $i; ?>").button().click(function() {cancelEditUrl(<?php echo $i; ?>);});


<?php } ?>
   $( "#dialog-fail" ).dialog({
      autoOpen: false,
      height:300,
      width: 400,
      modal: true,
      buttons: {
        "Valider": function() {
          $( this ).dialog( "close" );
        }
	}
      });

   $( "#dialog-success" ).dialog({
      autoOpen: false,
      height:300,
      width: 400,
      modal: true,
      buttons: {
        "Valider": function() {
	  
          $( this ).dialog( "close" );
        }
	},
      open: function(event, ui) { $(this).scrollTop(0); }
      });
   $( "#dialog-ko" ).dialog({
      autoOpen: false,
      height:300,
      width: 400,
      modal: true,
      buttons: {
        "Mettre hors ligne": function() {
          $( this ).dialog( "close" ); processResultDialogKO(true);
        },
        Cancel: function() {
          $( this ).dialog( "close" ); processResultDialogKO(false);
        }
	}
      });
   $( "#dialog-paulo" ).dialog({
      autoOpen: false,
      height:300,
      width: 400,
      modal: true,
      buttons: {
        "Mettre paulo": function() {
          $( this ).dialog( "close" ); processResultDialogPaulo(true);
        },
        Cancel: function() {
          $( this ).dialog( "close" ); processResultDialogPaulo(false);
        }
	}
      });

    $("#dialog-changedate").dialog({
      autoOpen: false,
      height:300,
      width: 400,
      modal: true,
      buttons: {
        "Changer de date": function() {
          $( this ).dialog( "close" ); 
	  var date = $.datepicker.formatDate("yy-mm-dd", $("#datepicker").datepicker('getDate'));
    window.location.assign("/onair/podcast/admin/?date=" + date);
        },
        Cancel: function() {
          $( this ).dialog( "close" );
        }
	}
      });

    window.prevTitle = [];
    window.prevUrl = [];
    
});

function xmlRequestAdmin(var_action, var_time, var_title) {
url = "http://" + window.location.hostname + "/onair/podcast/admin/ws/?action=" + var_action + "&d=<?php echo $date; ?>&t=" + var_time;
var result = true;
if (var_action == "seturl")
  url = url + "&u=" + encodeURIComponent(var_title);
else
if (var_action != "2paulo")
  url = url + "&tt=" + encodeURIComponent(var_title);

$.ajax({
			type: "GET",
			async: false,
			timeout: 5000,
			url: url,
			success:function(data)
			{
				//alert(JSON.stringify(data));
				if (data.error || data.action != var_action) {
				  message = document.getElementById("error-message");
				  message.innerHTML = data.error;
				  alert(JSON.stringify(data));
				  if (!data.error)
				    message.innerHTML = "action non réalisée";
				  $("#dialog-fail").dialog("open");
				  result = false;
				}
				else {
				  $("#dialog-success").dialog("open");
				  result = true;
				}
			},
			error: function (textStatus, errorThrown) {
				message = document.getElementById("error-message");
				message.innerHTML = textStatus.responseText;
				$("#dialog-fail").dialog("open");
				result = false;
            }
		});
return result;
}



function changeDate() {
  ready = true;
  for(i = 0; i < 24; ++i) {
    var title = document.getElementById("title" + i)
    if ((window.futureState && window.futureState[i] && window.futureState[i] != 0) || (window.prevTitle[i] && (window.prevTitle[i] != title.value))) {
      ready = false;
      break;
    }
  }

  if (ready) {
    var date = $.datepicker.formatDate("yy-mm-dd", $("#datepicker").datepicker('getDate'));
    window.location.assign("/onair/podcast/admin/?date=" + date);
  }
  else
    $( "#dialog-changedate" ).dialog( "open" );
}

function focusTitle(var_time) {

  // sauver le contenu
  var title = document.getElementById("title" + var_time);
  window.prevTitle[var_time] = title.value;
}

function focusUrl(var_time) {

  // sauver le contenu
  var url = document.getElementById("url" + var_time);
  window.prevUrl[var_time] = url.value;
}

function keypressedUrl(var_time, e) {
  // si entrée ou échappe, on valide ou on annule
  if(e.keyCode == $.ui.keyCode.ESCAPE) {
    cancelEditUrl(var_time);
  }
  else if (e.keyCode == $.ui.keyCode.ENTER) {
    setUrl(var_time);
  }
  else {
    // rendre visible les boutons
    setVisibleValidationUrl(var_time, true);
  }
}

function keypressedTitle(var_time, e) {
  // si entrée ou échappe, on valide ou on annule
  if(e.keyCode == $.ui.keyCode.ESCAPE) {
    cancelEditTitle(var_time);
  }
  else if (e.keyCode == $.ui.keyCode.ENTER) {
    setTrackOn(var_time);
  }
  else {
    // rendre visible les boutons
    setVisibleValidation(var_time, true);
  }
}


function setUrl(var_time) {
  var url = document.getElementById("url" + var_time);

  success = xmlRequestAdmin("seturl", var_time, url.value);

  if (success) {
    setVisibleValidationUrl(var_time, false);
    url.blur();
  }
}

function setTrackOn(var_time) {
  var title = document.getElementById("title" + var_time);

  // envoyer la requête xml pour rendre la track OK si nécessaire, et changer le titre
  if (window.futureState[var_time] == 1) {
    success = xmlRequestAdmin("2ok", var_time, title.value);
  }
  else if (window.futureState[var_time] == 2) {
    success = xmlRequestAdmin("2ko", var_time, title.value);
  }
  else {
    success = xmlRequestAdmin("rename", var_time, title.value);
  }
  if (success) {
    setVisibleValidation(var_time, false);
    if (window.futureState[var_time] != 0) {
      window.stateEntries[var_time] = window.futureState[var_time];
      window.futureState[var_time] = 0;

    }
    title.blur();
    var url = document.getElementById('url' + var_time);
    url.style.display = "block";
  }
}

function cancelEditTitle(var_time) {
  var title = document.getElementById("title" + var_time);
  title.blur();
  title.value = window.prevTitle[var_time];
  setVisibleValidation(var_time, false);
  if (typeof window.futureState[var_time] != 'undefined' && window.futureState[var_time] != 0) {
    window.futureState[var_time] = 0;
    resetActiveItem(window.setActiveItem);
    var title = document.getElementById("title" + var_time);
    title.style.display = "none";
  }
}

function setVisibleValidationUrl(var_time, var_visible) {
  // rendre visible les boutons de validation si nécessaire
  var buttons = document.getElementById("buttonsurl" + var_time);
  if (var_visible)
    buttons.style.display = "block";
  else
    buttons.style.display = "none";
}


function cancelEditUrl(var_time) {
  var url = document.getElementById("url" + var_time);
  url.blur();
  url.value = window.prevUrl[var_time];
  setVisibleValidationUrl(var_time, false);
}

function setVisibleValidation(var_time, var_visible) {
  // rendre visible les boutons de validation si nécessaire
  var buttons = document.getElementById("buttons" + var_time);
  if (var_visible)
    buttons.style.display = "block";
  else
    buttons.style.display = "none";
}




function processResultDialogKO(valid) {
	  if (valid) {

	    if (xmlRequestAdmin("2ko", window.setActiveItem, "")) {
	      window.stateEntries[window.setActiveItem] = 2;
	    }

	    
	  }
	  else {
	    resetActiveItem(window.setActiveItem);	    
	  }
		    window.futureState[window.setActiveItem] = 0;

}
function processResultDialogPaulo(valid) {
	  if (valid) {

	    if (xmlRequestAdmin("2paulo", window.setActiveItem, "")) {
	      var title = document.getElementById("title" + window.setActiveItem);
	      var buttons = document.getElementById("buttons" + window.setActiveItem);
	      title.value = "";
	      title.style.display = "none";
	      buttons.style.display = "none";
	      window.stateEntries[window.setActiveItem] = 3;
	    }

	    
	  }
	  else {
	    resetActiveItem(window.setActiveItem);
	    
	  }
		    window.futureState[window.setActiveItem] = 0;

}

function resetActiveItem(var_item) {
      if (window.stateEntries[var_item] == 1) {
	    $( "#radio" + window.setActiveItem + "-3").blur();
	    $( "#radio" + window.setActiveItem + "-2").blur();
	    $( "#radio" + window.setActiveItem + "-1" ).prop("checked", true).button("refresh");
      }
      else if (window.stateEntries[var_item] == 2) {
	    $( "#radio" + window.setActiveItem + "-3").blur();
	    $( "#radio" + window.setActiveItem + "-1").blur();
	    $( "#radio" + window.setActiveItem + "-2" ).prop("checked", true).button("refresh");
      }
      else if (window.stateEntries[var_item] == 3) {
	    $( "#radio" + window.setActiveItem + "-1").blur();
	    $( "#radio" + window.setActiveItem + "-2").blur();
	    $( "#radio" + window.setActiveItem + "-3" ).prop("checked", true).button("refresh");
      }

}

function launch_track(var_mp3, var_ok, var_time)
	{	
		var play = document.getElementById("main-play");
		play.style.display = "block";

		if (window.activeTime) {
		  var predhour = document.getElementById("heure" + window.activeTime);
		  predhour.style.backgroundColor="#fff";
		  }
		window.activeTime = var_time;

		$("#jquery_jplayer_1").jPlayer("clearMedia");
		
		if (var_ok == "OK") {
		    $("#jquery_jplayer_1").jPlayer("setMedia", { 			
			mp3: "../OK/<?php echo $date; ?>/" + var_mp3,
		    });
		}
		else  {
		    $("#jquery_jplayer_1").jPlayer("setMedia", { 			
			mp3: "../KO/" + var_mp3,
		    });
		}

		$("#jquery_jplayer_1").jPlayer("play");

		var hour = document.getElementById("heure" + var_time);
		hour.style.backgroundColor="#ddd";

		var hour = document.getElementById("hour");
		hour.innerHTML = var_time + "h";
	}
  </script>
</head>
<body>
<div id="left">
<span class="ui-helper-hidden-accessible"><input type="text" tabindex="-1" autofocus="autofocus" /></span>
  <h1><?php echo strftime("%A %e %B %Y",strtotime($date)); ?></h1>
			<!-- The jPlayer div must not be hidden. Keep it at the root of the body element to avoid any such problems. -->
			<div id="jquery_jplayer_1" class="cp-jplayer"></div>

		      <div id="time">
				</div>

			<div id="campus_player">
				<div id="hour"></div>
				<div id="cp_container_1" class="cp-container">
					<div class="cp-buffer-holder"> <!-- .cp-gt50 only needed when buffer is > than 50% -->
						<div class="cp-buffer-1"></div>
						<div class="cp-buffer-2"></div>
					</div>
					<div class="cp-progress-holder"> <!-- .cp-gt50 only needed when progress is > than 50% -->
						<div class="cp-progress-1"></div>
						<div class="cp-progress-2"></div>
					</div>
					<div class="cp-circle-control"></div>
					<ul class="cp-controls" id="main-play">
						<li><a class="cp-play" tabindex="1">play</a></li>
						<li><a class="cp-pause" style="display:none;" tabindex="1">pause</a></li> <!-- Needs the inline style here, or jQuery.show() uses display:inline instead of display:block -->
					</ul>
				</div>
			</div>
			<div style="float: right; font-size: 80%"><a href="/onair/podcast/player/?date=<?php echo $date;?>" target="_blank">voir le player</a></div>
			<div style="clear: both;float: right; font-size: 80%"><a href="/onair/podcast/admin/stats.php?date=<?php echo $date;?>&amp;period=month">voir les statistiques</a></div>
			<div style="clear: both;float: right; font-size: 80%"><a href="/onair/podcast/admin/edit-paulo-entries.php?date=<?php echo $date;?>">modifier les titres de Paulo</a></div>

  <h2 style="clear:both">Réécoute des émission</h2>
  <div><p>Pour repérer rapidement si une émission n'a pas eu lieu, il suffit de repérer les jingles que Paulo diffuse, par exemple aux alentours de 15mn et 30mn de chaque heure...</p></div>
  <h2 style="clear:both">Navigation</h2>
  <div id="datepicker"></div>
  <input id="change-date" type="submit" value="Aller à la date"></input> 
</div>
<div id="right">
    <div id="dialog-ko" title="Confirmation de mise hors ligne">
      <p>Le créneau sélectionné va être mis hors ligne. Voulez-vous mettre hors ligne ce créneau&nbsp;?</p>
    </div>
    <div id="dialog-paulo" title="Confirmation de mise hors ligne complète">
      <p>Le créneau sélectionné va être mis hors ligne, et remplacé par Paulo. En faisant ça, vous perdrez le titre saisi. Voulez-vous mettre hors ligne ce créneau, et le remplacer par Paulo&nbsp;?</p>
    </div>
    <div id="dialog-changedate" title="Changer de jour">
      <p>Un créneau est en cours d'édition. En quittant cette page, vous perdrez les modifications non sauvées. Voulez-vous vraiment changer de jour&nbsp;?</p>
    </div>
    <div id="dialog-success" title="Opération réussie">
      <p>L'opération a été réalisée avec succès sur le serveur.</p>
    </div>
    <div id="dialog-fail" title="Opération échouée">
      <p>L'opération a échoué pour la raison suivante: <em id="error-message"></em>. Veuillez contacter les administrateurs.</p>
    </div>


<?php 
  for ($i = 0; $i != 24; ++$i) {
    ?>
    <div class="colonne download">
    <?php if ($podcasts[$i]) { ?><a href="../<?php 
	if ($podcasts[$i]->available == 2) 
	  echo "OK/".$date."/".$podcasts[$i]->mp3;
	else if ($podcasts[$i]->mp3 == "")
           if ($podcasts[$i]->time < 10)
            echo "KO/".$date."-0".$podcasts[$i]->time."00.mp3";
          else
            echo "KO/".$date."-".$podcasts[$i]->time."00.mp3";
	else
	  echo "KO/".$podcasts[$i]->mp3;
	  ?>" title="Télécharger le podcast de <?php echo $i;?> heure" id="download<?php echo $i;?>">⇓</a><?php } ?>
    </div>
    <div class="heure colonne"> <div id="heure<?php echo $i;?>" <?php
    if(!$podcasts[$i]) {
      echo "style=\"color: #ccc\"";
      }
    else {
      echo " title=\"écouter le podcast de ".$i." heure. ";
      if (isset($ecoutes[$i])) {
	if ($ecoutes[$i][0] != 0)
	  echo $ecoutes[$i][0]." écoute(s). ";
	if ($ecoutes[$i][1] != 0)
	  echo $ecoutes[$i][1]." téléchargement(s)";
      }
      echo "\"";
    }
      ?>><?php if ($podcasts[$i]) {echo "▶&nbsp;";} echo $i;?>:00</div>
    </div>
    <div class="colonne second">
    <?php
    if($podcasts[$i]) {?>
      <div id="radio<?php echo $i;?>" class="toggle-podcast">
    <input type="radio" id="radio<?php echo $i;?>-1" name="radio<?php echo $i;?>" value="ok" <?php if ($podcasts[$i]->available == 2) echo "checked=\"checked\""; ?>><label for="radio<?php echo $i;?>-1" title="Mettre en ligne">OK</label>
    <input type="radio" id="radio<?php echo $i;?>-2" name="radio<?php echo $i;?>" value="ko" <?php if ($podcasts[$i]->available == 1) echo "checked=\"checked\""; ?>><label for="radio<?php echo $i;?>-2" title="Mettre hors ligne">KO</label>
    <input type="radio" id="radio<?php echo $i;?>-3" name="radio<?php echo $i;?>" value="paulo" <?php if ($podcasts[$i]->available == 0) echo "checked=\"checked\""; ?>><label for="radio<?php echo $i;?>-3" title="Mettre paulo">Paulo</label>
    </div>
      <?php
    }?>
    </div>
    <div class="colonne third">
    <?php if($podcasts[$i]) { ?>
      <input id="title<?php echo $i; ?>" type="text" value="<?php echo $podcasts[$i]->title; ?>" <?php 
    if ($podcasts[$i]->available == 0) { 
	echo ' style="display: none"';
    } ?>/>
    <?php } ?>
    </div>
    <div class="colonne col-buttons">
      <div id="buttons<?php echo $i; ?>" style="display: none">
    <?php if($podcasts[$i]) { ?>
      <button id="ok<?php echo $i; ?>">Valider</button><button id="cancel<?php echo $i; ?>">Annuler</button>
    <?php } ?></div>
    </div>
    <div class="colonne third col-newline">
    <?php if($podcasts[$i]) { ?>
      <input id="url<?php echo $i; ?>" type="text" value="<?php echo $podcasts[$i]->url; ?>" <?php 
    if ($podcasts[$i]->available == 0) { 
	echo ' style="display: none"';
    } ?>/>
    <?php } ?>
    </div>
    <div class="colonne col-buttons">
      <div id="buttonsurl<?php echo $i; ?>" style="display: none">
    <?php if($podcasts[$i]) { ?>
      <button id="okurl<?php echo $i; ?>">Valider</button><button id="cancelurl<?php echo $i; ?>">Annuler</button>
    <?php } ?></div>
    </div>
  <?php
  }  
?>
</div>
</body>
</html>

