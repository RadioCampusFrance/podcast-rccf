<!DOCTYPE html>
<?php

    $styles = array("tout" => "", "metal" => "Métal", "rock" => "Rock", "chanson" => "Chanson", "hip-hop" => "Hip Hop", "rdw" => "Reggae Dub World", "electro" => "Electro", "jazz" => "Jazz", "pop" => "Pop");

        // définition du style sélectionné
    if (isset($_GET["style"])) {
        $selected_style = $_GET["style"];
        if (!in_array($selected_style, $styles))
            $selected_style = $styles["tout"];            
    }
    else
        $selected_style = $styles["tout"];
        

    ?>

<html prefix="og: http://ogp.me/ns#">
<head>
    <meta charset=utf-8 />
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->

    <!-- Bootstrap -->
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet"></link>

    <link rel="icon" type="image/png" href="favicon.ico" />
    <title>Les 100% Radio Campus Clermont-Ferrand</title>
    <link href="css/main.css" rel="stylesheet"></link>
    <link href="css/styles.css" rel="stylesheet"></link>
    <link href="css/player.css" rel="stylesheet"></link>
    
    <link rel="alternate" type="application/rss+xml" title="Tous les podcasts <?php echo $selected_style; ?>" href="http://www.campus-clermont.net/onair/podcast/player/rss/?q=<?php echo rawurlencode ("100% ".$selected_style); ?>" />
</head>
<?php

        include '../podcast/player/configPaulo.php';
    	$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

	try {
		$bdd = new PDO('mysql:host='._PAULODB_SERVEUR.';dbname='._PAULODB_BD, _PAULODB_LOGIN, _PAULODB_MDP, $options);
	}
	catch (PDOException $Exception ) {
	$bdd = null;
	exit("Des problèmes techniques nous empêchent temporairement de vous proposer les 100%... Veuillez nous excuser pour la gêne occasionnée.");
	}

    function afficher_items_liste($selected_style) {
        global $styles;
        foreach($styles as $nb => $style) {
            $real_style = $style;
            if ($style == "")
                $real_style = "tous";
            if ($style == $selected_style) {
                echo '<li class="small-screen selected style-'.$nb.'"><span class="circle-style">100%</span> '.$real_style.'</li>';
            }
            else {
                echo '<li class="small-screen style-'.$nb.'"><a href="?style='.$style.'#my-player-anchor"><span class="circle-style">100%</span> '.$real_style.'</a></li>';
            }
        }
    }
    
    function afficher_liste($selected_style, $supclass) {
        global $styles;
        echo '<div class="menu-styles col-xs-12 col-sm-4 '.$supclass.'" id="sidebar-'.$supclass.'">';
        foreach($styles as $nb => $style) {
            $real_style = $style;
            if ($style == "")
                $real_style = "tous";
            if ($style == $selected_style) {
                echo '<div class="list-group-item active selected style-'.$nb.'"><span class="circle-style">100%</span> '.$real_style.'</div>';
            }
            else {
                echo '<div class="list-group-item style-'.$nb.'"><a href="?style='.$style.'#my-player-anchor"><span class="circle-style">100%</span> '.$real_style.'</a></div>';
            }
        }
        echo "</div>";
    }
    
    function charger_podcasts($selected_style) {
        global $styles;
        // on charge les émissions similaires
        $req = "http://" . $_SERVER['HTTP_HOST'] . "/onair/podcast/player/ws/search.php?action=list&q=100%" . urlencode($selected_style);
        $jsonObject = json_decode(file_get_contents($req));
        $result = array();
        foreach($jsonObject as $json) {
            $result[$json[0] . " " . $json[1]] = $json[2];
        }
        return $result;
    }
    
    function to_text($timecode) {
        $elems = explode(' ', $timecode);
        $heure = intval($elems[1])."h";
        if ($heure == "0h")
            $heure = "minuit";
        $date = explode('-', $elems[0]);
        $mois = array( "janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août", "septembre", "octobre", "novembre", "décembre");
        return intval($date[2]). " " .$mois[intval($date[1]) - 1] . " " .intval($date[0]).", ".$heure;
    }
    

        

       
       
    function get_player_url($timecode) {
        $elems = explode(' ', $timecode);
        $hour = intval($elems[1]);
        $date = $elems[0];
        $fullhour = "" .$hour;
	if (strlen($fullhour) == 1)
	  $fullhour = "0" . $fullhour;
        return "http://www.campus-clermont.net/onair/podcast/player/?date=".$date."&time=".$fullhour;
    }

    function get_mp3_url($timecode, $dl = true) {
        $elems = explode(' ', $timecode);
        $hour = intval($elems[1]);
        $date = $elems[0];
        $fullhour = "" .$hour;
	if (strlen($fullhour) == 1)
	  $fullhour = "0" . $fullhour;
        if ($dl)
            return "http://www.campus-clermont.net/onair/podcast/player/mp3.php?dh=".$date."_".$fullhour;
        else {
            return "http://www.campus-clermont.net/onair/podcast/OK/".$date."/".$date."-".$fullhour."00.mp3";
        }
            
    }

    include ("../podcast/player/lib/paulo_entries.php");
    function charger_titres($timecode) {
        $elems = explode(' ', $timecode);
        $hour = intval($elems[1]);
        $date = $elems[0];
        $duration = 1;
        
        
	global $bdd;
	return get_paulo_entries($date, $hour, $bdd, "../podcast/player/");
    }
    
    function load_ecoutes($timecode) {
        $result = array();
        
            $result[0] = "0";
            $result[1] = "0";

        $datetime = $timecode.":00:00";

                $options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

        global $bdd;
        
        $sql = "Select timeslot, download, count(*) as nb From log_ecoute where timeslot = ".$bdd->quote($datetime)." group by download;";
                
        $prep = $bdd->query($sql);
                
        $prep->execute();
        for($i=0; $row = $prep->fetch(); $i++){
            $download = intval($row["download"]);
            $result[$download] = $row["nb"];
        }

        return $result;
    }

    
    function afficher_titres($timecode) {
        $titres = charger_titres($timecode);
        
        echo "<ul>";
        foreach($titres as $titre) {
            echo "<li>";
            echo "<strong>".$titre["time"].":</strong> ".$titre["author"].", <em>".$titre["title"]."</em>";
            echo "</li>";
        }
        echo "</ul>";
        //echo '<p style="color: #ccc">Certains titres de cette playlist peuvent ne pas être affichés, pour des problèmes techniques indépendants de notre volonté.</p>';
    }
    
    function afficher_podcast($podcast, $timecode, $visible) {
        echo '<div id="podcast-p-'.str_replace(" ", "_", $timecode).'" class="panel panel-default';
        if ($visible)
            echo " selected";
        echo '">';
        echo '<div class="entete-podcast panel-heading"><span class="glyphicon glyphicon-headphones"></span> <a href="'.get_player_url($timecode).'">Podcast du '.to_text($timecode).': '.$podcast.'</a>';
        echo '</div>';
        echo '<div class="content-podcast panel-body"';
        
        $mp3 = get_mp3_url($timecode);
        echo '>';
            echo '<div class="list-group-horizontal">';
                echo '<a href="'.$mp3.'" class="list-group-item pull-right"><span class="showopacity glyphicon glyphicon-download-alt"></span></a>';
                echo '<a href="#my-player-anchor" class="list-group-item pull-right play" id="p-'.str_replace(" ", "_", $timecode).'"><span class="showopacity glyphicon glyphicon-play"></span></a>';
                echo '<a class="list-group-item pull-right pause hide" id="p-'.str_replace(" ", "_", $timecode).'-pause"><span class="showopacity glyphicon glyphicon-pause"></span></a>';
            echo '</div>';
            echo '<div class="ecoute">';
            
            $ecoutes = load_ecoutes($timecode);
            $add = false;
            if ($ecoutes[0] != 0) {
                    echo $ecoutes[0] . " écoute";
                    $add = true;
                    if ($ecoutes[0] > 1)
                        echo "s";
            }
            if ($ecoutes[1] != 0) {
                    if ($add)
                        echo "<br />";
                    echo $ecoutes[1] . " téléchargement";
                    if ($ecoutes[1] > 1)
                        echo "s";
            }
    
            // TODO
            echo '</div>';
        echo '<div class="playlist">';
        afficher_titres($timecode);
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    
    function afficher_podcasts($selected_style, $selected_podcast) {
        $podcasts = charger_podcasts($selected_style);
        $timecodes = array();
        $styles = array();
        
        echo '<div id="podcasts">';
        $nb = 0;
        if (count($podcasts) != 0) {
            $first = true;
            foreach($podcasts as $timecode => $podcast) {
                afficher_podcast($podcast, $timecode, ($first && $selected_podcast == "") || $timecode == $selected_podcast);
                $first = false;
                $timecodes[] = $timecode;
                $styles[] = $podcast;
                ++$nb;
                if ($nb > 15)
                    break;
            }
        }
        else {
            echo "<p>Il n'y a pas encore de podcast pour ce style musical.</p>";
        }
        echo "</div>";
        return array($timecodes, $styles);
    }
    

    $selected_podcast = "";
    if (isset($_GET["podcast"])) {
        $selected_podcast = $_GET["podcast"];
    }

  
?>
<body>
    <nav class="navbar navbar-fixed-top navbar-inverse">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">Les 100% musique</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li class="active"><a href="#">Écouter</a></li>
            <li><a href="http://www.campus-clermont.net/onair/podcast/player/">Les autres podcasts</a></li>
            <li><a href="http://campus-clermont.net">Radio Campus Clermont-Ferrand</a></li>
            <?php afficher_items_liste($selected_style); ?>
          </ul>
        </div><!-- /.nav-collapse -->
      </div><!-- /.container -->
    </nav><!-- /.navbar -->
    
<div class="container">

      <div class="row">

        <div class="col-xs-12 col-sm-8">
          <div class="jumbotron">
            <h1>Les 100% musique</h1>
            <p>Les programmateurs de <a href="http://campus-clermont.net">Radio Campus Clermont-Ferrand</a> vous proposent leur sélection musicale.</p>
          </div>
        </div>
        
        
        <div class="col-xs-12 col-sm-4" id="my-player">
          <div id="my-player-anchor" style="margin-top: -50px; margin-bottom: 50px"></div>
          <div id="jquery_jplayer_1"></div>
          <div id="style-podcast">
            <?php 
                    echo $selected_style;
            ?>
          </div>
          <div class="jp-player">
            <span type="button" style="cursor: pointer; font-size:120px;" class="main-pause hide showopacity glyphicon glyphicon-pause"></span>
            <span type="button" style="cursor: pointer; font-size:120px;" class="showopacity glyphicon glyphicon-play main-play"></span>
            <div class="time-info">
                <span id="currentTime-podcast"></span>
                <span id="duration-podcast"></span>
            </div>
        <div class="jp-progress">
          <div class="jp-seek-bar">
            <div class="jp-play-bar"></div>
          </div>
        </div>
            <span id="title-podcast"></span>
          </div>
        </div>



        <div class="col-xs-12 col-sm-8">
          <?php list($podcasts, $p_styles) = afficher_podcasts($selected_style, $selected_podcast);    ?>  
        </div><!--/row-->


        <?php afficher_liste($selected_style, "large-screen");
        ?>      
        <div class="col-xs-12 col-sm-4 logo"><a href="http://campus-clermont.net"><img src="images/logo.png" style="width: 100%; margin-top: 1em;" alt="Radio Campus Clermont-Ferrand"/></a></div>
        
      </div>
        <footer>
        <p>&copy; Radio Campus Clermont-Ferrand 2015</p>
      </footer>
      </div>

      <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="jquery/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="bootstrap/js/bootstrap.min.js"></script>
    <script src="jplayer/jquery.jplayer.js"></script>
    <script type="text/javascript">
    //<![CDATA[ 
    <?php
        echo "var podcasts = {";
        $first = true;
        foreach($podcasts as $podcast) {
            if (!$first)
                echo ",";
            $first = false;
            echo '"p-'.str_replace(" ", "_", $podcast).'": "'.get_mp3_url($podcast, false).'"';
        }
        echo "};";

        echo "var podcasts_name = {";
        $first = true;
        foreach($podcasts as $podcast) {
            if (!$first)
                echo ",";
            $first = false;
            echo '"p-'.str_replace(" ", "_", $podcast).'": "'.to_text($podcast).'"';
        }
        echo "};\n";
    
        echo "var podcasts_style = {";
        $first = true;
        $nb = 0;
        foreach($podcasts as $podcast) {
            if (!$first)
                echo ",";
            $first = false;
            echo '"p-'.str_replace(" ", "_", $podcast).'": "'.str_replace("100% ", "", $p_styles[$nb]).'"';
            $nb = $nb + 1;
        }
        echo "};\n";
        
        if ($selected_podcast != "") {
            echo "var selected = 'p-".str_replace(" ", "_", $selected_podcast)."';\n";
            echo "firstmp3 = podcasts[selected];\n";
            echo "firstmp3name = podcasts_name[selected];\n";
        }
        else {
            echo "var selected = 'p-".str_replace(" ", "_", $podcasts[0])."';\n";
            echo "firstmp3 = '".get_mp3_url($podcasts[0], false)."';\n";
            echo "firstmp3name = '".to_text($podcasts[0])."';\n";
        }
        ?>

    <?php 
    $url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    $prefix_url = parse_url($url, PHP_URL_PATH);
    ?>

    function reorder_entries(ss) {
        var main = document.getElementById( 'podcasts' );

        var s = "podcast-" + ss;
                            [].map.call( main.children, Object ).sort( function ( a, b ) {
                            if (a.id === s)
                                    return -1;
                                else if (b.id === s)
                                    return  +1;
                                else
                                    return -a.id.localeCompare(b.id);
                        }).forEach( function ( elem ) {
                            main.appendChild( elem );
                        });
    }

    var bg_images = {};
    function set_style_name(ss) {
         $('#style-podcast').replaceWith('<div id="style-podcast">' + podcasts_style[ss] + '</div>');
    }
    function load_background_image(ss) {
    
        if (ss == "tout")
            bg_images[ss] = "";
        else {
             $.ajax({
			type: "GET",
			async: false,
			timeout: 5000,
			url: "http://" + window.location.hostname + "/ws/?req=image&t=100% " + encodeURIComponent(ss),
			success:function(data)
			{
                            bg_images[ss] = data[0]["uri"];    
			},
			error: function (textStatus, errorThrown) {
            }});
        }
            
    }
    function set_background_image(ss) {
        if (!(ss in bg_images)) {
            load_background_image(ss);
        }
        imageUrl = bg_images[ss];
        if (imageUrl != undefined)
            $('#style-podcast').css('background-image', 'url(' + imageUrl + ')');
    }

    for(var podcast in podcasts) {
        $('.pause').click(function() {
                        $('.pause').addClass('hide');
                        $('.play').removeClass('hide');
                        $('.main-play').removeClass('hide');
                        $('.main-pause').addClass('hide');
                        $("#jquery_jplayer_1").jPlayer("pause");
        });
        $('.main-pause').click(function() {
                        $('.pause').addClass('hide');
                        $('.play').removeClass('hide');
                        $('.main-play').removeClass('hide');
                        $('.main-pause').addClass('hide');
                        $("#jquery_jplayer_1").jPlayer("pause");
        });
        $('.main-play').click(function() {
                        $("#jquery_jplayer_1").jPlayer("play");
                        $('#'+ selected + '-pause').removeClass('hide');
                        $('#'+ selected).addClass('hide');
                        $('.main-play').addClass('hide');
                        $('.main-pause').removeClass('hide');
        });
        $('#' + podcast).click(function(event) {
                        $('.pause').addClass('hide');
                        $('.play').removeClass('hide');
                        $('.main-play').addClass('hide');
                        $('.main-pause').removeClass('hide');
                        $("#jquery_jplayer_1").jPlayer("pause");
                        if (selected != this.id) {
                            $("#jquery_jplayer_1").jPlayer("clearMedia");
                            $("#jquery_jplayer_1").jPlayer("setMedia", { title : podcasts_name[this.id],
                                                                    mp3 : podcasts[this.id] });
                        }
                        $('#'+ this.id + '-pause').removeClass('hide');
                        $('#'+ this.id).addClass('hide');
                        
                        $('.panel-default').removeClass('selected');
                        $('#podcast-' + selected).addClass('seen');
                        selected = this.id;

                        $("#jquery_jplayer_1").jPlayer("play");


                        // reorder the entries
                        reorder_entries(selected);
                        // set the style name
                         set_style_name(selected);
                        // set background image
                        set_background_image(podcasts_style[selected]);
                        
                        $('#podcast-' + selected).addClass('selected');
                        $('#podcast-' + selected).removeClass('seen');

                        var s2 = selected.replace("p-", "").replace("_", " ");  
                        window.history.pushState({ state: 'play', title : podcasts_name[this.id]}, podcasts_name[this.id], '<?php echo $prefix_url;?>?style=<?php echo $selected_style;?>&podcast='+s2);

                        
        });
    }
    
        $(document).ready(function(){
      $("#jquery_jplayer_1").jPlayer({
        swfPath: "jplayer/js",
        supplied: "mp3",
        preload: "auto",
        cssSelectorAncestor: "",
        cssSelector: {
            play: '#main-play',
            pause: '#main-pause',
            currentTime: "#currentTime-podcast",
            duration: "#duration-podcast",
            title: "#title-podcast",
            seekBar: ".jp-seek-bar",
            playBar: ".jp-play-bar"
        },
        ready: function() {jQuery(this).jPlayer("setMedia", {
            title: firstmp3name,
            mp3: firstmp3,
        });}

      });
      
      jQuery('#jquery_jplayer_1').bind(jQuery.jPlayer.event.play, function(event) { 
		  if (event.jPlayer.status.paused===false) {
                        var hour = selected.split("_")[1];
                        var date = selected.split("_")[0].replace("p-", "");
// 		      alert("http://" + window.location.hostname + "/onair/podcast/player/ws/log_ecoute.php?d=" + date + "&h=" + hour);
		      $.ajax({
 			type: "GET",
 			async: false,
 			timeout: 5000,
 			url: "http://" + window.location.hostname + "/onair/podcast/player/ws/log_ecoute.php?d=" + date + "&h=" + hour,
 			success:function(data) {},
 			error: function (textStatus, errorThrown) {}});
		    }
		  });
		
            jQuery('#jquery_jplayer_1').bind(jQuery.jPlayer.event.ended +'.jp-repeat', function() { 
                $('.pause').addClass('hide');
                $('.play').removeClass('hide');
                $('.main-pause').addClass('hide');
                $('.main-play').removeClass('hide');
            });
          <?php 
        if ($selected_podcast != "") {
            echo "reorder_entries(selected);";
        }
    ?>
        set_style_name(selected);
        set_background_image(podcasts_style[selected]);

    });
    
    
    //]]> 
    </script>
</body>
</html>
