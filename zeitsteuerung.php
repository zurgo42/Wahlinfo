<?php // Steuert am Anfang der Eingabeseiten, ob sie den Ms gezeigt oder den Kandidaten zum Ausfüllen angeboten werden sollen
if (isset($_COOKIE['testserver'])) {$Mnr = $_COOKIE['testserver'];
	$vorname="Testvorname"; $name="Testnachname"; $email = "werner.kelnhofer@mensa.de";// nur zum Testen
	} else {
	$MNr = $_SERVER['REMOTE_USER'];}

//$MNr = $_SERVER['REMOTE_USER'];
$key = schluessel($MNr,"");
$kanz = mysqli_num_rows($ks); // Anzahl Kandidaten
$eingabe = 0;
/*
if (time()<dzut($Editieren_bis)) {// Zeitraum bis zum Editier-Ende: Nur für Kandidaten zugänglich
for ($i=0;$i<$kanz;$i++) {

 if (db_result($ks,$i,'mnummer') == $MNr) {$eingabe = 1; continue;} // Ja, das ist ein Kandidat.
}
$key = schluessel($MNr,$kandidatendb); // Hier wird der Zeitstempel in die entsprechende Tabelle eingetragen

if (time()>dzut($Editieren_bis)) {$eingabe = 0; // Danach ist keine Eingabe mehr zugelassen   ************************* am 06.02.16 bzw.nach Eintrag der Kandidaten das > auf < ändern, damit nicht die Vergleichsseiten erscheinen. ******************
//echo '<span class="text32 fa55">Das ist die Ansicht nach dem Editier-Ende!</span><br>';
}
} else { echo 'Diese Seite ist erst ab dem  '.$Editieren_bis.' zug&auml;nglich!';
		$weiter = ""; include ("schwanz.php"); die;}
if ($eingabe) {
$spalten = 4; $kanz= 1;
$ks = mysqli_query($link,'SELECT * FROM '.$kandidatendb.' WHERE amt1=1 AND mnummer='.$MNr); //nur die Vorstandskandidaten
$kanz = 1;} else {$spalten = $kanz+3;}
*/ $spalten = $kanz+3;
//echo "MNr: $MNr eingabe: $eingabe<br>";
?>