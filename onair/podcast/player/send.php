<?php 
// Here we get all the information from the fields sent over by the form.
$name = $_POST['name'];
$email = $_POST['email'];
$raison = $_POST['raison'];
$message = $_POST['message'];
$url = $_POST['url'];
 
$to = 'webmaster@clermont.radio-campus.org';
$subject = 'Message depuis le podcast';
$message = 'Auteur: '.$name."\nCourriel: ".$email."\nUrl: ".$url."\nRaison: ".$raison."\nMessage:\n".$message;
$headers = 'From: webmaster@clermont.radio-campus.org' . "\r\n";
 
if (filter_var($email, FILTER_VALIDATE_EMAIL)) { // this line checks that we have a valid email address
mail($to, $subject, $message, $headers); //This method sends the mail.
echo "Votre message a été envoyé au webmaster&nbsp;!"; // success message
}else{
echo "Adresse invalide, veuillez indiquer un courriel valide.";
}

?>