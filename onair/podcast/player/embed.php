<!DOCTYPE html>
<html prefix="og: http://ogp.me/ns#">
<head>
<meta charset=utf-8 />

<meta name="viewport" content="width=device-width" />
<link rel="icon" type="image/png" href="favicon.ico" />
<?php

include 'configPaulo.php';



function get_time(&$time) {
  $time = $_GET['time'];
  if (!ctype_digit($time) || ($time < 0) || ($time > 24))
    $time = "";
}

function get_minsec(&$minsec, $desc) {
  $minsec = $_GET[$desc];
  if (!ctype_digit($minsec) || ($minsec < 0) || ($minsec > 60)) {
    $minsec = "";
    return false;
  }
  else {
    return true;
  }
}

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
  var $ecoutes;
  var $timesec;
  var $timemin;


  function __construct($array_from_ws) {
     $this->mp3 = $array_from_ws->mp3;
     $this->time = $array_from_ws->time;

     $this->title = $array_from_ws->title;
     $titles = explode('|', $this->title);
     if (count($titles) == 1) {
      $this->titleItems = $this->title;
    }
    else {
      $this->titleItems = "<ul>";
      foreach($titles as $t) {
	  $this->titleItems = $this->titleItems . "<li>" . $t . "</li>";
      }
      $this->titleItems = $this->titleItems . "</ul>";
    }
    $this->ok = $array_from_ws->ok;
    $this->paulo_entries = $array_from_ws->paulo_entries;
    $this->duration = $array_from_ws->duration;
    $this->shortTitle = $array_from_ws->shortTitle;
    $this->future = $array_from_ws->future;
    $this->url = $array_from_ws->url;
    $this->podcastable = $array_from_ws->podcastable;
    $this->image = $array_from_ws->image;
    $this->ecoutes = $array_from_ws->ecoutes;
    if (isset($array_from_ws->timemin))
        $this->timemin = $array_from_ws->timemin;
    else
        $this->timemin = "00";
    if (isset($array_from_ws->timesec))
        $this->timesec = $array_from_ws->timesec;
    else
        $this->timesec = "00";
  }

  static function emptyToItem($hour) {
      echo "<p class=\"time_empty\">".$hour."h</p>";
  }

  function toItem($date) {
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

      if (isset($this->ecoutes) && strlen($this->mp3) != 0) {
	$add = false;
	$nb = $this->ecoutes[0] + $this->ecoutes[1];
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
      echo "<li id=\"entry-".$this->time."-".$id."\" title=\"".$entry->time.": ".$entry->title.", ".$entry->author."\"><span>".$entry->time. "</span><em>" .$entry->title ."</em>, ".$entry->author."</li>";
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



}

	function load_podcasts_from_ws($date) {
	  $podcasts = json_decode(file_get_contents("http://" . $_SERVER['HTTP_HOST'] . "/onair/podcast/player/ws/?date=" . $date));
	  // conversion to object
	  $result = array();
	  if (count($podcasts) != 0)
            foreach($podcasts as &$podcast) {
                $result[$podcast->time] = new Podcast($podcast);
            }
          return $result;
	}
	
	function get_ecoutes_from_podcasts($podcasts) {
            $result = array();
            // build an array (one value per hour) corresponding to the ecoutes
            for($i = 0; $i != 24; ++$i) {
                if (isset($podcasts[$i]) && isset($podcasts[$i]->ecoutes)) {
                    $result[$i] = $podcasts[$i]->ecoutes;
                }
                else {
                    $result[$i] = array(0, 0);
                }
                
            }
            
            return $result;
	}


	setlocale (LC_TIME, 'fr_FR.utf8','fra'); 
	date_default_timezone_set('Europe/Paris');
	get_date($date, $day, $month, $year);
	get_time($time);
	
	if (get_minsec($initial_min, "min")) {
            $minsec_first = get_minsec($initial_sec, "sec");
	}

	$datex = explode('-',$date);
	$datprev = date ("Y-m-d", mktime (0,0,0,$datex[1],$datex[2]-1,$datex[0]));
	$datnext = date ("Y-m-d", mktime (0,0,0,$datex[1],$datex[2]+1,$datex[0]));


	$podcasts = load_podcasts_from_ws($date);
	
	$actionSearch = $_GET["search"];
	$actionLive = $_GET["live"];
	
	$actionRecent = $_GET["recent"];
        if (isset($actionRecent) && $time == "") {
	  $time = intval(date("H"));
	  if (!isset($podcasts[$time]) || $podcasts[$time]->ok) {
	    $time = "";
	    $actionLive = true;
	    unset($actionRecent);
	    }
	}
	
	$fulldate = strftime("%A %e %B %Y",strtotime($date));
?>

<title><?php 
if (!isset($time) || $time == "")
  echo "Podcast Radio Campus Clermont-Ferrand";
else
 echo  htmlspecialchars ($podcasts[$time]->title) . " - Radio Campus, ".$fulldate . ", ".$time."h";
?></title>
<meta property="og:locale" content="fr_FR" />
<meta property="og:type" content="article" />
<?php if (!isset($time) || $time == "") { ?>
<meta property="og:title" content="Radio Campus <?php echo $fulldate;?>" />
<meta property="og:site_name" content="Le podcast de Radio Campus Clermont-Ferrand" />
<?php }
else if (isset($time) && $time != "") { ?>
<meta property="og:title" content="<?php echo htmlspecialchars ($podcasts[$time]->title) . " - Radio Campus, ".$fulldate . ", ".$time."h";?>" />
<meta property="og:description" content="Podcast de l'émission <?php echo htmlspecialchars ($podcasts[$time]->title) . " du ".$fulldate . " ".$time."h sur Radio Campus Clermont-Ferrand";?>" />
<meta property="og:site_name" content="Le podcast de Radio Campus Clermont-Ferrand" />
<?php }
else if (isset($actionLive)) { ?>
<meta property="og:title" content="Streaming Radio Campus Clermont-Ferrand" />
<meta property="og:description" content="Streaming en direct de Radio Campus Clermont-Ferrand" />
<meta property="og:site_name" content="Le streaming de Radio Campus Clermont-Ferrand" />
<?php } ?>

<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
<link rel="stylesheet" media="screen" href="cssembed/circle.player.css?date=<?php echo filemtime('cssembed/circle.player.css');?>" />
<link rel="stylesheet" href="skin/circle.skin/jquery.mCustomScrollbar.css" />
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery.jplayer.min.js"></script>
<script type="text/javascript" src="js/jquery.transform2d.js"></script>
<script type="text/javascript" src="js/jquery.grab.js"></script>
<script type="text/javascript" src="js/mod.csstransforms.min.js"></script>
<script type="text/javascript" src="js/circle.player.js"></script>
<script type="text/javascript" src="js/jquery.mCustomScrollbar.concat.min.js"></script>
<script type="text/javascript" src="js/jquery.ui.datepicker-fr.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.10.4.custom.min.js"></script>

<link rel="alternate" type="application/rss+xml" title="Tous les podcasts de Radio Campus" href="http://<?php echo $_SERVER['HTTP_HOST'];?>/onair/podcast/player/rss/" />

<?php 
foreach($podcasts as $podcast) {
  if ($podcast->ok && $podcast->mp3 != "") { ?>
    <link rel="alternate" type="application/rss+xml" title="Tous les podcasts de l'émission <?php echo str_replace("\"", "&quot;", $podcast->title); ?>" href="http://<?php echo $_SERVER['HTTP_HOST'];?>/onair/podcast/player/rss/?q=<?php echo rawurlencode($podcast->title); ?>" />
    <?php
  }
}
?>
<!-- load Twitter script -->
<script type="text/javascript">!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>


<script type="text/javascript">
//<![CDATA[
var myCirclePlayer;
$(document).ready(function(){

	/*
	 * Instance CirclePlayer inside jQuery doc ready
	 *
	 * CirclePlayer(jPlayerSelector, media, options)
	 *   jPlayerSelector: String - The css selector of the jPlayer div.
	 *   media: Object - The media object used in jPlayer("setMedia",media).
	 *   options: Object - The jPlayer options.
	 *
	 * Multiple instances must set the cssSelectorAncestor in the jPlayer options. Defaults to "#cp_container_1" in CirclePlayer.
	 *
	 * The CirclePlayer uses the default supplied:"m4a, oga" if not given, which is different from the jPlayer default of supplied:"mp3"
	 * Note that the {wmode:"window"} option is set to ensure playback in Firefox 3.6 with the Flash solution.
	 * However, the OGA format would be used in this case with the HTML solution."../OK/2014-04-29-1800.mp3"
	 */
      window.number_entries = [];
      window.time_entries = [];
      window.logged = [];
      window.entryOpen = [];
      window.pImages = [];
      window.firstcanplay = false;
      
      $('#submit').click(function(){
      $.post("send.php", $("#mycontactform").serialize() + "&url=" + encodeURIComponent(window.location.href),  function(response) {
      $('#success').html(response);
      });
      return false;
      });

      <?php 
	  for($i = 0; $i != 24; $i++)
	    if (isset($podcasts[$i]) && !$podcasts[$i]->ok)  {
	      echo "window.number_entries[".$podcasts[$i]->time."] = ".count($podcasts[$i]->paulo_entries).";";
	      $j = 0;

	    }
	    else if (isset($podcasts[$i]) && isset($podcasts[$i]->image) && $podcasts[$i]->image != "")
	      	      echo "window.pImages[".$podcasts[$i]->time."] = '".addslashes($podcasts[$i]->image) ."';";

	if ($time != "" || isset($actionRecent)) {
		if (isset($podcasts[$time])) {
		  if ($podcasts[$time]->ok) {
		    //if (!isset($actionRecent))
		    //  $podcasts[$time]->toLaunchTrack($date, false);
		  }
		  else {
		      $podcasts[$time]->toDisplayEntries(false);
		  }
		    echo ";";
	    }
            if ($minsec_first) {
                echo "window.minsec_first = true;";
                echo "window.initial_min = ".$initial_min.";";
                echo "window.initial_sec = ".$initial_sec.";";
            }
            else 
                echo "minsec_first = false;";
            
      }
	?>
		var showTimeLeft = function(event) {
			if (!window.live) {
                         var myDiv = document.getElementById("time");
			 if(window.activeTime != undefined) {
                            var time = event.jPlayer.status.currentTime;
			  
                            var timeDisplay = window.activeTime+":"+$.jPlayer.convertTime(time);
                            var myDiv = document.getElementById("time");
                            myDiv.innerHTML = "<span>"+timeDisplay+"</span>";
                         }
                         else {
                            myDiv.innerHTML = "";
                         }
			}
		};
	<?php    if ($time != "") {
		if (isset($podcasts[$time]) && $podcasts[$time]->ok) {
		  echo 'window.var_time_string = "'.$time.'";';
		}
		} ?>
	myCirclePlayer = new CirclePlayer("#jquery_jplayer_1",
	{
		<?php    if ($time != "") {
		if (isset($podcasts[$time]) && $podcasts[$time]->ok) {
                        if ($time < 10)
                            echo 'mp3: "../OK/'.$date.'/'.$date.'-0'.$time.'00.mp3",'; 
                        else
                            echo 'mp3: "../OK/'.$date.'/'.$date.'-'.$time.'00.mp3",'; 

	  } }
		    else {
		      if (isset($actionLive)) {
			echo 'mp3: "http://campus.abeille.com:8000/campus",';
			//echo 'mp3: "http://imperatorium.org:8000/campus",';
		      }
		  }
		?>
	
	}, {
		timeupdate: showTimeLeft,
		durationchange: showTimeLeft,
		supplied: "mp3",
		cssSelectorAncestor: "#cp_container_1",
		swfPath: "js",
		preload: "auto",
		wmode: "window",
<?php if (isset($actionLive)) { ?>
		canplay: function (event) {
                        if (!window.firstcanplay) {
                            play_live(false, true);
                            window.firstcanplay = true;
                        }
                        
		},
<?php }
else if ($time != "" && (isset($podcasts[$time]) && $podcasts[$time]->ok)) {
                    echo "canplay: function (event) { ";
                        echo "if (!window.firstcanplay) {";
                        $podcasts[$time]->toLaunchTrack($date, false, true);
                        echo ";window.firstcanplay = true; }";
                    echo ";},"; 
                    }
?>
	});

	        		

	jQuery('#jquery_jplayer_1').bind(jQuery.jPlayer.event.play, function(event) { 
		  if (event.jPlayer.status.paused===false) {
		  if (window.var_time_string != "-1") {
		      //alert("http://" + window.location.hostname + "/onair/podcast/player/ws/log_ecoute.php?d=<?php echo $date;?>&h=" + window.var_time_string);
		      $.ajax({
 			type: "GET",
 			async: false,
 			timeout: 5000,
 			url: "http://" + window.location.hostname + "/onair/podcast/player/ws/log_ecoute.php?d=<?php echo $date;?>&h=" + window.var_time_string,
 			success:function(data) {},
 			error: function (textStatus, errorThrown) {}});
		    }}
		  });

	
<?php if (isset($actionSearch)) { 
      echo "open_search();";
      echo "rechercher(false);";

} ?> 
	precharger_image("images/chargement.gif");
	
    window.minTime = [];
    window.secTime = [];
    <?php for ($i = 0; $i != 24; ++$i) 
       if($podcasts[$i]) { ?>
        window.secTime[<?php echo $i; ?>] = "<?php echo $podcasts[$i]->timesec; ?>";
        window.minTime[<?php echo $i; ?>] = "<?php echo $podcasts[$i]->timemin; ?>";
       <?php
       }
       ?>


});



function add_telecharger(var_time) {
	var myDiv = document.getElementById("dl_podcast");
	myDiv.innerHTML = "<a href=\"mp3.php?d=<?php echo $date; ?>&h=" + var_time + "&dl=true\">télécharger ▼</a>";
}

function escapeHtml(text) {
  return text
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/\"/g, "\\\"")
      .replace(/\'/g,"\\\'")
}


function remove_telecharger() {
var myDiv = document.getElementById("dl_podcast");
	myDiv.innerHTML = "";
}


function clear_previous() {
  $("#jquery_jplayer_1").jPlayer("clearMedia");
  var play = document.getElementById("main-play");
  play.style.display = "none";
  var play = document.getElementById("main-control");
  play.style.display = "none";
  var play = document.getElementById("progess-holder");
  play.style.display = "none";
  var play = document.getElementById("buffer-holder");
  play.style.display = "none";	


    var myDiv = document.getElementById("time");
	myDiv.innerHTML = "";
    var myDiv = document.getElementById("time"+window.activeTime);
    if (myDiv != undefined) {
      var lastIndex = myDiv.className.lastIndexOf(" ")
      myDiv.className = myDiv.className.substring(0, lastIndex);
    }

    
    
    var myDiv = document.getElementById("directlink");
    if (myDiv != undefined)
      myDiv.className = "";
    window.live = false;

    var myDiv = document.getElementById("hour_of_podcast");
    myDiv.innerHTML = "";

    var myDiv = document.getElementById("description_of_podcast");
    myDiv.innerHTML = "";

    var myDiv = document.getElementById("ecoutes_of_podcast");
    myDiv.innerHTML = "";
    
    remove_telecharger();
    remove_image();
}

function set_image_default(var_time) {
  var t = parseInt(var_time);
  if (t < 6) {
    window.pImages[var_time] = 'http://<?php echo $_SERVER["HTTP_HOST"]?>/onair/podcast/player/images/fond-bleu.png';
  }
  else if (t < 12) {
    window.pImages[var_time] = 'http://<?php echo $_SERVER["HTTP_HOST"]?>/onair/podcast/player/images/fond-jaune.png';
  }
  else if (t < 18) {
    window.pImages[var_time] = 'http://<?php echo $_SERVER["HTTP_HOST"]?>/onair/podcast/player/images/fond-rouge.png';
  }
  else {
    window.pImages[var_time] = 'http://<?php echo $_SERVER["HTTP_HOST"]?>/onair/podcast/player/images/fond-vert.png';
  }
  set_image(window.pImages[var_time]);

}

function set_image(url) {
  
				  // crate a new image
				  var pic_real_width, pic_real_height;
				  var span = $("#img-pst");
				  // get its size
				  $("<img/>") // Make in memory copy of image to avoid css issues
					.attr("src", url).load(function() {
					pic_real_width = this.width;   // Note: $(this).width() will not
					pic_real_height = this.height; // work for in memory images.
					var spdiv = $("#image-podcast");
					span.css("width", "");
					span.css("height", "");
					span.css("margin-left", "");
					span.css("margin-top", "");
					if (pic_real_height < pic_real_width) {
					  span.css("height", "300px");
					  var new_width = pic_real_width * 300 / pic_real_height;
					  var left = (new_width - 300) / 2;
					  span.css("margin-left", "-" + left.toString() + "px");
					}
					else {
					  span.css("width", "300px");
					  var new_height = pic_real_height * 300 / pic_real_width;
					  var top = (new_height - 300) / 2;
					  span.css("margin-top", "-" + top.toString() + "px");
					}

					span.attr("src", url);
					spdiv.css("display", "block");
				  });

}

function add_image(var_title, var_time, var_year, var_month, var_day, force) {

		
		if (window.pImages[var_time] != undefined && !force)
		  set_image(window.pImages[var_time]);
		else
		  $.ajax({
			type: "GET",
			async: false,
			timeout: 5000,
			url: "http://" + window.location.hostname + "/ws/?req=image&t=" + encodeURIComponent(var_title) + "&h=" + var_time + "&y=" + var_year + "&m=" + var_month + "&d=" + var_day,
			success:function(data)
			{
			
				var span = $("#img-pst");
				if (span != undefined) {
				if (data != undefined && data.length != 0 && data[0].uri != null)
				{
				  window.pImages[var_time] = 'http://<?php echo $_SERVER["HTTP_HOST"]?>' + data[0].uri;
 				  set_image(window.pImages[var_time]);
				}
				else
				  set_image_default(var_time);
				  }
				else
				  remove_image();
			},
			error: function (textStatus, errorThrown) {
            }
		});

}

function remove_image() {
    var img = document.getElementById("img-pst");
    img.src = "";
    var mydiv = document.getElementById("image-podcast");
    mydiv.style.display = "none";
}

function display_downloads(var_time) {
  var ecoutes = <?php echo json_encode(get_ecoutes_from_podcasts($podcasts)); ?>;
  
  if (ecoutes[var_time] === undefined) {
    return "";
  }
  else {
    var result = "";

    var add = false;
    if (ecoutes[var_time][0] != 0) {
      result += ecoutes[var_time][0] + " écoute";
      add = true;
      if (ecoutes[var_time][0] > 1)
	result += "s";
    }
    if (ecoutes[var_time][1] != 0) {
      if (add)
	result += ", ";
      result += ecoutes[var_time][1] + " téléchargement";
      if (ecoutes[var_time][1] > 1)
	result += "s";
    }
    
    return result;
  }
}


function jump(h) {
    document.getElementById(h).scrollIntoView(true);
}

function launch_track(var_mp3, var_title, var_time, var_play, var_url)
	{	
		if (window.activeTime == var_time) {
		  if ($("#jquery_jplayer_1").data().jPlayer.status.paused == false)
		    $("#jquery_jplayer_1").jPlayer("pause");
		  else
		    $("#jquery_jplayer_1").jPlayer("play");
		  return;
		}

		clear_previous();

		var play = document.getElementById("main-play");
		play.style.display = "block";
		var play = document.getElementById("main-control");
		play.style.display = "block";
		var play = document.getElementById("progess-holder");
		play.style.display = "block";	
		var play = document.getElementById("buffer-holder");
		play.style.display = "block";
		
			
		window.activeTime = var_time;

		
		var hourDisplay = var_time+"h";
		if (hourDisplay == "0h")
		  hourDisplay = "minuit";

		var myDiv = document.getElementById("hour_of_podcast");
		myDiv.innerHTML = hourDisplay;

		var myDiv = document.getElementById("description_of_podcast");
		if (var_url != "")
		  myDiv.innerHTML = '<span><a href="' + var_url + '" target="_blank">' + var_title + '</a></span>';
		else
		  myDiv.innerHTML = var_title;

		var myDiv = document.getElementById("ecoutes_of_podcast");
		myDiv.innerHTML = display_downloads(var_time);
		


		//$("#jquery_jplayer_1").jPlayer("clearMedia");
		
		var complement = "";
		if (window.logged[var_time] !== undefined) {
		    complement = "&nl=true";
		}
		
		if (var_time < 10)
		  window.var_time_string = "0" + var_time;
		else
		  window.var_time_string = var_time;
		  
		  setexacturl = false;
                  if (var_play) {
                    if (window.minsec_first) {
                        sec = window.initial_sec;
                    }
                    else if (window.secTime[var_time])
                        sec = parseInt(window.secTime[var_time]);
                    else
                        sec = 0;
                    if (window.minsec_first) {
                        min = window.initial_min;
                        window.minsec_first = false;
                        setexacturl = true;
                    }
                    else if (window.minTime[var_time])
                        min = parseInt(window.minTime[var_time]);
                    else
                        min = 0;
                    start = min * 60 + sec;
                    $("#jquery_jplayer_1").jPlayer("setMedia", { 
			mp3: "../OK/<?php echo $date; ?>/<?php echo $date; ?>-" + var_time_string + "00.mp3",
		}).jPlayer("play", start);
                }
                else {
                		$("#jquery_jplayer_1").jPlayer("setMedia", { 
			mp3: "../OK/<?php echo $date; ?>/<?php echo $date; ?>-" + var_time_string + "00.mp3",
		});
                }

                if (setexacturl) {
                    var complement = "&min="+min+"&sec="+sec;
                }
                else
                    var complement = "";
                if (var_play) {
		  window.history.pushState({ state: 'play', mp3: var_mp3,  title: var_title, time: var_time, url: var_url}, 'Radio Campus <?php echo $date;?>'+var_time+'h', '<?php echo $prefix_url;?>?date=<?php echo $date;?>&time='+var_time+complement+"#campus_player");
		  jump("campus_player");
                }
		


		window.logged[var_time] = true;
		
		
		document.title = var_title + ' - Radio Campus, <?php echo $fulldate;?>, '+var_time+'h';
		
		update_reseaux_sociaux('http://<?php echo $_SERVER["HTTP_HOST"].$prefix_url;?>?date=<?php echo $date;?>&time='+var_time+complement);
		add_telecharger(var_time);
		add_image(var_title, var_time, <?php echo $datex[0];?>, <?php echo $datex[1];?>, <?php echo $datex[2]; ?>, false);
	}


function loop_reload(){
		$.ajax({
			type: "GET",
			async: false,
			timeout: 5000,
			url: "http://" + window.location.hostname + "/ws/?req=onair&d=" + new Date().getTime(),
			success:function(data)
			{
				var span = $("#titre-live");
				if (span != undefined) {
				
				var date = new Date();
				var time = date.getHours();
				if (data.type == null || data.type == "paulo")
				{
					span.html(data.titre+" - "+data.auteur);
					set_image_default(time);
				}
				else if (data.type == "emission")
				{
					span.html('émission <a href="' + data.url + '">' + data.titre + "</a>");
					if (window.lastHour != time)
					  add_image(data.titre, time, date.getUTCFullYear(), date.getUTCMonth() + 1, date.getUTCDate(), true);
				}
				else
				{
					span.html("-");
				}
				}
				else {
				  clearInterval(window.interval);
				}
				window.lastHour = time;
			},
			error: function (textStatus, errorThrown) {
            }
		});
	return false;
}

function play_live(var_start, var_history) 
      {

		if (window.live == true) {
		  clear_previous();
		  return;
		}
		clear_previous();

		var play = document.getElementById("main-play");
		play.style.display = "block";
		var play = document.getElementById("main-control");
		play.style.display = "block";
		var play = document.getElementById("progess-holder");
		play.style.display = "none";	
		var play = document.getElementById("buffer-holder");
		play.style.display = "none";	


		window.live = true;
		window.var_time_string = -1;


		var myDiv = document.getElementById("hour_of_podcast");
		myDiv.innerHTML = "direct";

		var myDiv = document.getElementById("description_of_podcast");
		myDiv.innerHTML = "<span><span id=\"titre-live\"></span></span>";

		//myDiv.innerHTML = "<span class=\"small\">en cas de problème, <a href=\"http://imperatorium.org:8000/campus\">ouvrir directement le flux</a></span>";

    ;

		var myDiv = document.getElementById("time");
		myDiv.innerHTML = "<em>en direct</em>";

		if (var_history) {
		  window.history.pushState({ state: 'direct' }, 'Radio Campus en direct', '<?php echo $prefix_url;?>?live=true#campus_player');
		  jump("campus_player");
                }


		//$("#jquery_jplayer_1").jPlayer("clearMedia");
		
		if (var_start) {
                    $("#jquery_jplayer_1").jPlayer("setMedia", { 
                            title: "Radio Campus live",
                            mp3: "http://campus.abeille.com:8000/campus",
                            //mp3: "http://imperatorium.org:8000/campus",
                    }).jPlayer("play");
		}
		else {
                    $("#jquery_jplayer_1").jPlayer("setMedia", { 
                            title: "Radio Campus live",
                            mp3: "http://campus.abeille.com:8000/campus",
                            //mp3: "http://imperatorium.org:8000/campus",
                    });
		}
		jQuery('#jquery_jplayer_1').bind(jQuery.jPlayer.event.ended +'.jp-repeat', function() { 
		  false;
		  });

		

		if (window.interval == undefined)
		  clearInterval(window.interval);
		set_image_default(<?php echo date("G"); ?>);
		window.lastHour = -1;
		loop_reload();
		window.interval = setInterval('loop_reload();',10000);
  
		document.title = "Radio Campus live";
		update_reseaux_sociaux('http://<?php echo $_SERVER["HTTP_HOST"].$prefix_url;?>?live=true');
		
}






function precharger_image(url)
{
        var img = new Image();
	img.src=url;
        return img;
}


function getUrlFromDateTime(var_datetime) {
  return "?date=" + var_datetime[0] + '&time=' + parseInt(var_datetime[1]);
}
function getDateHumanFormat(var_datetime) {
  if ((typeof var_datetime == 'undefined') || (typeof var_datetime[0] == 'undefined'))
    return "";
  var m2Txt = [ "janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août", "septembre", "octobre", "novembre", "décembre" ];
  var jour = var_datetime[0].split("-");
  return parseInt(jour[2], 10) + " " + m2Txt[parseInt(jour[1], 10) - 1] + " " + jour[0] + ", " + parseInt(var_datetime[1], 10) + "h";
}



function update_reseaux_sociaux(url) {

$('.fb-like').attr('data-href',url);
$("#gpluswrapper").html('<div class="g-plusone" data-size="medium"></div>');

	$('#twitterwrapper').html('<a href="https://twitter.com/share" class="twitter-share-button" data-url="' + url + '" data-text="'+ document.title + ' ' + url +'">Tweet</a>');
	
    try{
	    gapi.plusone.render("plusone", { "href": url });
            twttr.widgets.load();
            FB.XFBML.parse();
            gapi.plusone.go();
        }catch(ex){}

}


//]]>
</script>
<style type="text/css">

</style>


<!-- load Google+ script :: this should go just before </body> tag -->
<script type="text/javascript">
  (function() {
	var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
	po.src = 'https://apis.google.com/js/plusone.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
  })();
</script>

</head>


<body>

<!-- load Facebook script :: this should go right after <body> tag -->
<div id="fb-root"></div>
<script type="text/javascript">(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/fr_FR/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>

			<!-- The jPlayer div must not be hidden. Keep it at the root of the body element to avoid any such problems. -->
			<div id="jquery_jplayer_1" class="cp-jplayer"></div>
<div id="main"><div id="fixed-size">
                        <!--<a href="http://www.campus-clermont.net/"><img src="image_aleatoire.php" alt="Radio Campus" /><br /></a>-->
                        


			<div id="date_of_podcast">
				<?php echo "<p>".$fulldate."</p>"; ?>
				<div id="time">
				</div>
			</div>
			  <div id="hour_of_podcast">			
			  </div>
			<div id="center_description">
			  <div id="description_of_podcast">			
			  </div>
			  <div id="ecoutes_of_podcast">			
			  </div>
			  <div id="RCCF"><a href="http://campus-clermont.net">Radio Campus Clermont-Ferrand</a></div>
			</div>

			<div />


			<div id="campus_player" <?php 
  if (!isset($time) || $time == "") {
      echo 'class="hidden"';
      }; ?>>
				<div id="cp_container_1" class="cp-container">
					<div id="image-podcast"><img id="img-pst" src="<?php if ($time != "") {
					  echo $podcasts[$time]->image;
					}?>" /></div>
					<div class="cp-buffer-holder" id="buffer-holder"> <!-- .cp-gt50 only needed when buffer is > than 50% -->
						<div class="cp-buffer-1"></div>
						<div class="cp-buffer-2"></div>
					</div>
					<div class="cp-progress-holder" id="progess-holder"> <!-- .cp-gt50 only needed when progress is > than 50% -->
						<div class="cp-progress-1"></div>
						<div class="cp-progress-2"></div>
					</div>
					<div class="cp-circle-control" id="main-control"></div>
					<ul class="cp-controls" id="main-play">
						<li><a class="cp-play" tabindex="1">play</a></li>
						<li><a class="cp-pause" style="display:none;" tabindex="1">pause</a></li> 
					</ul>
				</div>
			</div>
			
	

			<div id="tools">
				<div id="reseauxsociaux">
				<div class="fb-like" data-send="false" data-layout="button_count" data-width="90" data-show-faces="false" data-font="arial"></div>
				<div id="twitterwrapper"><a href="https://twitter.com/share" class="twitter-share-button">Tweet</a></div>
				<div id="gpluswrapper"><div class="g-plusone" data-size="medium"></div></div>
			</div>
			<div id="dl_podcast">
			</div>
			<div id="directlink">
			<?php if (isset($actionLive)) { ?>
				<a href="http://campus-clermont.net//onair/podcast/player/?live=true" target="_blank">lien direct ▶</a>
			<?php } else { ?>
				<a href="http://campus-clermont.net//onair/podcast/player/?date=<?php echo $date; ?>&amp;time=<?php echo $time; ?>" target="_blank">lien direct ▶</a>
				<?php } ?>
			</div>

	</div>
</body>

</html>
