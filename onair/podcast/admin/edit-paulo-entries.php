<!DOCTYPE html>
<html prefix="og: http://ogp.me/ns#">
<head>
<meta charset=utf-8 />
    <title>Édition des titres de Paulo</title>
    <link rel="icon" type="image/png" href="../player/favicon.ico" />
     <style type="text/css">
        .c1, .c4 {
            width: 9em;
        }
        .c4 {
            text-align: center;
        }
        .c2, .c3 {
            width: 30em;
        }
    </style>
</head>


<body>
    <h1>Édition des titres de Paulo</h1>
    <p>Cette page permet de modifier les titres qui ont été programmés par Paulo/Airtime. Le service est expérimental, mais il permet notamment de mettre les titres des 100% lorsqu'ils sont programmés en un seul fichier mais contiennent plusieurs titres.</p>


<?php

function cmp($a, $b) {
    return strcmp($a["time"],$b["time"]);
}

function trier_array(&$array) {
    usort($array, "cmp");
}



    include '../player/lib/paulo_entries.php';

  $date = $_GET['date'];
  $pattern = '/^([0-9][0-9][0-9][0-9])-([0-9][0-9])-([0-9][0-9])/';
  preg_match($pattern, $date, $matches);
  if (count($matches) != 4) {
    echo "<p>Format de date incorrect. Retour <a href=\"./index.php\">à l'administration</a>.</p>";
  }
  else {
    echo "<p>Retour <a href=\"./index.php\">à l'administration</a>.</p>";

    $file_logs = "../player/paulo/" . $date .".txt";
    if ((nb_day_from_today($date) > 1) && (file_exists($file_logs))) {
        $action = $_GET['action'];
        $array = get_paulo_entries_from_file($file_logs);
        if (isset($action)) {
            $file_logs_backup = "../player/paulo/" . $date ."-backup.txt";
            if (strcmp($action, "supprimer") == 0) {
                $key_action = $_GET['key'];
                $id_action = $_GET['id'];
                $entry = $array[$key_action][$id_action];
                if (!isset($id_action) || !isset($key_action) || !isset($entry)) {
                    echo "<p><strong>Erreur&nbsp;:</strong> impossible de trouver l'entrée sélectionnée.</p>";
                }
                else {
                    copy($file_logs, $file_logs_backup);
                    echo "<p>Le titre suivant a été supprimé&nbsp;: ".$entry["time"].", ".$entry["title"].", ".$entry["author"]."</p>";
                    unset($array[$key_action][$id_action]);
                    $array[$key_action] = array_values($array[$key_action]);
                    trier_array($array[$key_action]);
                    if (save_paulo_entries_from_file($file_logs, $array))
                        echo "<p>La suppression a été faite correctement.</p>";
                    else
                        echo "<p>Erreur pendant la suppression.</p>";
                }
            }
            else if (strcmp($action, "ajouter") == 0) {
                $timestamp_action = $_GET['timestamp'];
                $title_action = $_GET['title'];
                $author_action = $_GET['author'];
                $key_action = $_GET['key'];

                if (!isset($title_action) || !isset($title_action) || !isset($author_action) || !isset($key_action)) {
                    echo "<p><strong>Erreur&nbsp;:</strong> impossible d'ajouter l'entrée: informations incomplètes.</p>";
                }
                else {
                    copy($file_logs, $file_logs_backup);
                    $array[$key_action][] = array("title" => $title_action, "author" => $author_action, "time" => $timestamp_action);
                    trier_array($array[$key_action]);
                    if (save_paulo_entries_from_file($file_logs, $array))
                        echo "<p>L'ajout a été fait correctement.</p>";
                    else
                        echo "<p>Erreur pendant l'ajout au fichier.</p>";
                }
            }
            else if (strcmp($action, "ajouter_horaire") == 0) {
                $horaire_action = $_GET['horaire_action'];
                if (!isset($horaire_action)) {
                    echo "<p><strong>Erreur&nbsp;:</strong> impossible d'ajouter l'horaire: informations incomplètes.</p>";                
                }
                else {
                    if (!isset($array[$horaire_action])) {
                        copy($file_logs, $file_logs_backup);
                        $array[$horaire_action] = array();
                        if (save_paulo_entries_from_file($file_logs, $array))
                            echo "<p>L'ajout a été fait correctement.</p>";
                        else
                            echo "<p>Erreur pendant l'ajout au fichier.</p>";
                    }
                    else {
                    echo "<p><strong>Erreur&nbsp;:</strong> impossible d'ajouter l'horaire: l'horaire existe déjà.</p>";                
                    }
                }
            }
        }
        
        
        
        foreach($array as $key=>$heure) {
            echo "<h2>Horaire ".$key."</h2>\n";
            echo "<table>\n";
            echo "<tr><th>Heure</th><th>Titre</th><th>Auteur</th><th>Supprimer</th></tr>\n";
            $id=0;
            foreach($heure as $entry) {
                ?>
                <tr>
                    <td class="c1"><?php echo $entry["time"]; ?></td>
                    <td class="c2"><?php echo $entry["title"]; ?></td>
                    <td class="c3"><?php echo $entry["author"]; ?></td>
                    <td class="c4"><form action="edit-paulo-entries.php">
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="date" value="<?php echo $date; ?>">
                        <input type="hidden" name="key" value="<?php echo $key; ?>">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <input type="submit" value="Supprimer"></form>
                    </td>
                    </tr>
                <?php
                $id = $id + 1;
            }
            echo "</table>\n";
            
            ?>
            <form action="edit-paulo-entries.php">
                <input type="hidden" name="action" value="ajouter">
                <input type="hidden" name="date" value="<?php echo $date; ?>">
                <input type="hidden" name="key" value="<?php echo $key; ?>">
                <input type="text" name="timestamp" class="c1"><input type="text" name="title" class="c2"> <input type="text" name="author" class="c3"><input type="submit" value="Ajouter" class="c4">
            </form>
            <?php
        }
        
        ?>
        <form action="edit-paulo-entries.php">
                <input type="hidden" name="action" value="ajouter_horaire">
                <input type="hidden" name="date" value="<?php echo $date; ?>">
                <input type="text" name="horaire"><input type="submit" value="Ajouter">
        </form>
        
        <?php
    
    }
    else {
        echo "<p>Pas de titres pour la date indiquée. Deux raisons possibles&nbsp;: la date est trop récente, ou personne n'a encore consulté les titres, ils sont encore dans la base de données temporaire. Retour <a href=\"./index.php\">à l'administration</a>.</p>";
    }
  }
?>
</body>

</html>