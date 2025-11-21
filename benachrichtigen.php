<?php // Auf Wunsch von Jörg nachträglich erstellter Teil, mit dem Kandidaten benachrichtigt werden weil sie angesprochen wurden

function bezug ($beitrag) {
	global $Kommentar,$link;
	$q = 'SELECT Bezug FROM '.$Kommentar.' WHERE Knr='.$beitrag;
	return db_result(mysqli_query($link,$q),0,'Bezug');}

function frager ($beitrag) {
	global $Kommentar,$link;
	$q = 'SELECT Mnr FROM '.$Kommentar.' WHERE Knr='.$beitrag;
	return db_result(mysqli_query($link,$q),0,'Mnr');}

function benachrichtigen ($beitrag) {
global $Kommentar,$Texte,$Teilnehmer,$kandidatendb,$link;
$n = 0; $a[0] = $beitrag;  // Nur für den Test!!!! Das ist der zu untersuchende Beitrag
$komm = mysqli_query($link,'SELECT These FROM '.$Kommentar.' WHERE Knr='.$beitrag);
//echo "lfdNr $n Knr= ".$a[$n].' = der zu untersuchende Beitrag<br>';
while ($a[$n]>1000) {$n++;
	$a[$n] = bezug($a[$n-1]); 
//	echo "lfdNr $n a[$n] = Knr= ".$a[$n].'<br>';
	}
//echo "lfdNr $n Knr= ".$a[$n].'<br>';
$frager = frager($a[0]);
if ($Texte == "Wahl2025") {$kand = mysqli_query($link,'SELECT * FROM '.$kandidatendb.' WHERE Knr='.$a[$n]);}
else {$kand = mysqli_query($link,'SELECT * FROM '.$Teilnehmer.' WHERE Mnr='.frager($a[1]));}
// Wenn das auf alle Teilnehmer ausgedehnt wird, muss das über die Tabelle mit den Antworten abgefragt werden; das ist hier noch nicht zu Ende gedacht und ausgeführt.
// ToDo; Beim Abfragen der Teilnehmerdaten abfragen, ob überhaupt gewünscht; und nur unmittelbare Antworten oder  bei allen Antworten in einem Thread? Das wären viele Mails!


//echo db_result($kand,0,'mnummer').' vergleichen mit '.$frager;
// Nur wenn Nachricht erwünscht und keine Benachrichtigung über den eigenen Beitrag
//if ((db_result($kand,0,'nachricht')) AND (frager($n)) <> $frager)) { // nur dann Nachricht senden
//if (db_result($kand,0,'nachricht')) { // nur dann Nachricht senden
if (db_result($kand,0,'mnummer') <> $frager) { // nur dann Nachricht senden, wenn fremder Eintrag
$vn = db_result($kand,0,'These'); $vn = substr($vn,0,strpos($vn," "));
$email = db_result($kand,0,'email');
$fname = mysqli_query($link,'SELECT Vorname,Name FROM '.$Teilnehmer.' WHERE Mnr="'.$frager.'"');
//echo "<br>Knr= ".$a[$n]." Mail an $email:<br>";

$text = "Hallo ".db_result($kand,0,'vorname').",\n";
$text .= "im Diskussionstool gibt es in deinem Feld einen neuen Beitrag von ".db_result($fname,0,'Vorname')." ";
$text .= db_result($fname,0,'Name')." (MNr. $frager):\n\n";
$text .= utf8_decode(db_result($komm,0,'These'));
$text .= "\n_________________________________\n\nUm darauf zu antworten, kannst Du den folgenden Link nutzen:\n";
$text .=  " https://aktive.mensa.de/wahl/diskussion.php? ";
// '<a href="https://aktive.mensa.de/wahl/diskussion.php?datenfile=wahl2021">https://aktive.mensa.de/wahl/diskussion.php?datenfile=wahl2021</a>';
$text .= "\n\nViele Grüße\nWerner Kelnhofer\nals Admin dieser Seite\n\n(diese Mail wurde vom Diskussionstool automatisch erstellt)";
// $text .= "\n\nPS: Diese Funktion ist noch nicht komplett getestet. Wenn du Ungereimtheiten feststellst, bitte ich um einen entsprechenden Hinweis. Wenn dir diese Mails zuviel werden, schreib mir 'ne Mail - dann schalte ich das für dich ab.";

function multipartmail2 ($betreff,$text,$email,$kopie,$reply) { // $text ohne Umlaute-Codierung
	$text = str_replace("\n","<br>",$text);
	$id = strtoupper(md5(uniqid(time())));
	$header = "FROM: MinD-Wahl_Diskussionstool <mensa@as-tt.de>\n";
	if (laenge($reply) > 5) {$header .= "Reply-To: ".$reply."\n";} else {$header .= "Reply-To: mensa@as-tt.de\n";} 
	$header .= "X-Mailer: PHP/" . phpversion(). "\n";          
	$header .= "X-Sender-IP: ".$_SERVER['REMOTE_ADDR']." \n"; 

	$header .= "MIME-Version: 1.0\nContent-Type: multipart/alternative; boundary=$id";
	$body = "--$id\nContent-Type: text/plain; charset=UTF-8\n\n".utf8_encode(strip_tags(str_replace("<br>","\n",$text)));
	$body .= "\n--$id\nContent-Type: text/html; charset=UTF-8\n\n <html>\n".umlaute($text)."\n</html>";
	if ($kopie) mail("mensa@as-tt.de",$betreff,$body,$header);
	return mail($email,$betreff,$body,$header);}

//multipartmail2 ('MinD-Wahl: Diskussionstool',$text,$email,1,'admin@ms4ms.de');
multipartmail2 ('MinD-Wahl: Diskussionstool',$text,$email,1,'mensa@as-tt.de');
//echo umlaute($text);

} //else {echo 'keine Nachricht';}

//echo '<br>***************************************************************<br><br>';
}
?>