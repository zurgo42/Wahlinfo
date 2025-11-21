<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html><head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="Author" content="Dr. Hermann Meier, Mensa in Deutschland e.V.">
<meta name="robots" content="nofollow">
<link rel="icon" href="M1.ico" type="image/x-icon">
<meta name="description" content="Wahlinfo-Seite des Vereins Mensa in Deutschland e.V.">

<?php // Dieser Teil startet die Seiten, schreibt den Kopf, enthält die Funktionen, definiert die DB-Tabellen, speichert übergebene Werte und lädt die Variablen zur Umfrage

include ($daten.'.php');	// Die individuellen Daten der jeweiligen Umfrage - $daten steht am Anfang jeder einzelnen Seite
include ('fstandard.php');	// Standardfunktionen laden
include ('funktionen.php'); // Funktionen für die spezielle Anwendung laden
include_once ("config.php"); // Datenbank-Zugangsdaten

// Funktionen nur für dieses Projekt:
$skala5 = array("","&mdash; &mdash;","&mdash;","&plusmn;","+","+ +"); // Skala 1 bis 5
$skala5 = array("","- -","&mdash;","&plusmn;","**","***"); // Skala 1 bis 5 Wichtigkeit
$skala5p = array("","<b>&dArr;&dArr;&dArr;</b>","&dArr;","&hArr;","&uArr;","<b>&uArr;&uArr;&uArr;</b>"); // Skala pro/contra
$skala5ptext = array("nix","klar dagegen","dagegen","indifferent","daf&uuml;r","unbedingt daf&uuml;r");
$skala5z = array(0,1,2,3,4,5); // Zahlen zeigen
$skala5w = array("","&mdash;","!","!!","!!!","!!!!"); // Skala Wichtigkeit 
$skala5wtext = array("nix","&uuml;berhaupt nicht","eher nicht","vielleicht","sehr","extrem"); // .. wichtig  Skala Wichtigkeit 
$skala5r = array("","auf keinen Fall","nein","wenn es sein muss","ja, gern","unbedingt");
$skala5a = array("","&uuml;berhaupt nicht meins","eher nein","ja, kriege ich wohl hin","ja, darin bin ich gut","ja, genau das ist eine meiner St&auml;rken");
$skala5f = array("55","55","55","56","51","51");


function skala5($i,$skala5,$mitfarbe) {//Skala drucken mit i=nxxxx mit n=Wert und x=Bemerkungs-ID
	global $skala5f,$eingabe;
	if ($i) {$q = round($i/10000,0); $b=$i-$q*10000;
	echo '<td align="center" class="mit';
	if ($b) $bem = db_result(mysqli_query($link,'SELECT bem FROM bemerkungenwahl WHERE id='.$b),0,'bem');
	if (laenge($bem) > 2) echo ' hell" title="'.$bem;
	echo '"><span class="text1'; if ($skala5[1] == 1) {echo 'n';} else {echo 'b';}
	if ($mitfarbe) echo ' fa'.$skala5f[$q];
	echo ' center">'.$skala5[$q].'</span></td>';}
	else {echo '<td class=" mit" align="center"> </td>';}
}

function weiter ($einzeln,$ue,$ressorts,$anforderungsliste,$fragen) { // zeigt das untere Menu oberhalb der Fußleiste
	global $spalten;
	$spintern = 1+$einzeln+$ue+$ressorts+$anforderungsliste+$fragen;
	echo '<tr style="margin:0; padding:0; border: 1px solid #0a1f6e;"><td colspan="'.$spalten.'" valign="top" align="left" style="margin:0; padding:0; background-color: #cccccc;">'; //Menuleiste
	echo '<span class="text3n fa55">Hinweis: Dieses Menu sieht M (au&szliger den Kandidaten) erst nach Beendigung der Editierfrist!<br></span>';
	echo '<table width="100%" border="0" style="margin:0; padding:0;"><tr><td><h2>Weiter:</h2></td>';
	if ($einzeln) echo '<td class="text1b fa58">Kandidatenseiten:<br>Klick oben auf das Foto<br>des Kandidaten!</td>';
	if ($ue) echo '<td align="center"><a class="menu" style="width:100px;" href="kandidaten.php"><span class=" fa58 center">&Uuml;bersicht</span></a></td>';
	if ($ressorts) echo '<td align="center"><a class="menu" style="width:100px;" href="ressorts.php"><span class=" fa58 center">Ressort-Pr&auml;ferenzen</span></a></td>';
	if ($anforderungsliste) echo '<td align="center"><a class="menu" style="width:100px;" href="anforderungen.php"><span class=" fa58 center">Anforderungen</span></a></td>';
	if ($fragen) echo '<td align="center"><a class="menu" style="width:100px;" href="fragen.php"><span class=" fa58 center">Fragen & Antworten</span></a></td>';
	echo '</tr>';
	echo '<tr><td colspan="'.$spintern.'" class="text1b"><br>Im <a class="menu" style="width:250px;" href="https://aktive.mensa.de/diskussionstool/wahl2016.php" target="MinD">Diskussionstool zur Wahl</a> kannst du mit den Kandidaten direkt ins Gespr&auml;ch kommen.<br><br></td></tr>';
	echo '</table></td></tr>';
}

function speichernmitbem ($name,$is0,$is8,$file) {  // Antworten und Bemerkungen ins Antwortfile schreiben
	//$name ist der Buchstabe, unter der der Wert in der Datenbank gespeichert werden soll; $is0 bis $is8 sind die lfdNr dieser Werte
	//$name.lfdNr ist auch der per POST übergebene Wert; b.$name.lfdNr ist die zugehörige Bemerkung
	//Die Daten werden jeweils in der Form n*10000+b gespeichert - dabei ist n die Bewertung und b die ID der Bemerkung (1000<b<=9999)

	global $datum, $dbzeigen,$key;
	$aendern = 0;
	$eintrag = 'UPDATE '.$file.' SET ';
	$ein = 0; // Das ist der Zähler, ob überhaupt etwas übergeben wurde
	for ($i=$is0;$i<=$is8;$i++){
	$f = $name.$i; // Feldbezeichnung in der Datenbank und bei der Datenübergabe per POST der jeweilige Wert
	$wert = ipost($f)*10000;
	if ($wert) { $ein++; }// es wurde ein Wert > 0 übergeben; noch entscheiden, ob es Bemerkungen ohne Wert geben darf!
	$b = 'b'.$f; $bid = 0; 
	$bem = umlaute(ipost($b)); //echo "$i: wert $wert bem $bem ein $ein - ";
	if (laenge($bem) > 1) { $ein++; // Es wurde ein Bemerkungstring mit Länge > 1 übergeben
	$bid = bemerkungspeichern($bem); } // Hier wurde die Bewertung gespeichert (falls neu) und die ID übergeben
	$wert = $wert+$bid;
	$eintrag .= $f.'='.$wert.', ';
	}
	
	$eintrag .= 'letzteintrag = "'.$datum;
	$eintrag .= '" WHERE schl='.$key;
	if ($ein) $aendern = mysqli_query($link,$eintrag); 
	if ($dbzeigen) echo '<br>'.$aendern . $eintrag.'<br>';
}


// ***************************** Kopf erzeugen, Basisdaten laden **********************************************************


// Header schreiben
mobilumschaltung(0); // =1, wenn es eine Mobilversion gibt
$privat = ubeide('privat');

echo "<title>$title</title>";
echo '</head><body bgcolor="#ffffff"><center><table width="';
if (($mobil >1) OR ($privat)) {echo '100%';} else {echo '760px';} 

echo '" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#EEF3F9">';
echo '<tr class="rahmen" style="border-top: 1px #0a1f6e solid;"><td>
<div id="header"><div id="headerBG"><div id="headerR1"><div id="headerR2"><div id="headerR3">
<div id="headerTITEL"><div id="headerSLOGAN">';
echo $slogan;
echo '</div></div></div></div></div></div></div></td></tr>';

echo '<tr class="rahmen"><td><table width="';
if (($mobil >1) OR ($privat)) {echo '100%';} else {echo '760px';} 


echo '" border="0" bgcolor="#EEF3F9" style="padding-left: 2px;">';


//Datenbank verbinden
error_reporting(0); // Verhindert die Warnmeldung zu mysqli
$link = mysqli_connect(MYSQL_HOST,MYSQL_USER, MYSQL_PASS) OR die("Keine Verbindung zur Datenbank. Fehlermeldung:".mysqli_error());
mysqli_select_db('wahl') OR die("Konnte Datenbank nicht benutzen, Fehlermeldung: ".mysqli_error());
error_reporting(E_ALL); ini_set('display_errors', "Off"); //error_reporting(E_ALL); ini_set('display_errors','1');


// Authentifizierungs- und Anonymisierungsteil
$MNr = $_SERVER['REMOTE_USER'];
$key = schluessel($MNr,$antwortenfile); // Hier wird ein Datensatz in der Adressen-DB und in der Antworten-DB angelegt

// Nur Ms erreichen diese Seiten, indem der Mensa-Server deren MNr übergibt
// Um die Anonymität zu erreichen, wird im Adressfile der MNr ein Key zugeordnet
// Unter diesem Key werden im Antwortenfile die Antworten gespeichert
// Wird der Key gelöscht (am Ende der Umfrage bzw. auf Anforderung des Nutzers), ist eine Zuordnung Key-MNr nicht mehr möglich

// Deshalb muss zu allen Seiten der Key übergeben (durch GET oder POST) oder die MNr abgerufen werden
// Was im Einzelnen geschieht, wenn kein Key übergeben wird, wird auf den Einzelseiten geregelt


// Spezieller Teil: Kandidaten 
// Die Identifizierung der Kandidaten in den spezifischen Tabellen kann über die M-Nummer erfolgen, weil hier keine Anonymisierung gewollt ist.
// In der Antwortendatei zu Fragen/Antworten muss allerdings wie bei allen anderen Ms über den Schlüssel identifiziert werden (Einheitlichkeit).
// Deshalb dürfen die Kandidaten ihren Schlüssel nicht vorzeitig löschen!



//Testeinstellungen
if (substr($_SERVER['REMOTE_USER'],3) == "5018") {$dbzeigen = 1; 
$eingabe = 0; // Das steuert, ob gezeigt oder eingegeben wird.
// Muss noch durch die Authentifiizierung konkretisiert werden


//echo "&Uuml;bergeben: ".ipost('r1').ipost('br1');

} // Nur für die Testphase




// Speicherteil: Wenn Daten übergeben werden, werden sie hier gespeichert
$speichern = ipost('ressort'); 
if ($speichern) { // Die Ressort-Werte speichern
speichernmitbem ('r',1,17,'kandidatenwahl'); // Die Felder rn werden gespeichert
}
$speichern = ipost('anforderungen'); 
if ($speichern) { // Die Ressort-Werte speichern
speichernmitbem ('a',1,44,'kandidatenwahl'); // Die Felder an werden gespeichert
}
$speichern = ipost('fragen'); 
if ($speichern) { // Die Ressort-Werte speichern
if (!mysqli_num_rows(mysqli_query($link,'SELECT schl FROM antwortenwahl WHERE schl='.$key))) { // unter dieser Nummer gibt es noch keinen Datensatz in der Antwortentabelle
	$eintrag = 'INSERT INTO antwortenwahl SET schl='.$key.', ersteintrag="'.$datum.'"';
	$aendern = mysqli_query($link,$eintrag); if ($dbzeigen) echo '<br>'.$aendern . $eintrag.'<br>';}
speichernmitbem ('f',1,18,'antwortenwahl'); // Die Felder fn werden gespeichert
speichernmitbem ('w',1,18,'antwortenwahl'); // Die Felder wn werden gespeichert
}


// Füllen der Musterkandidaten mit Zufallszahlen
/*
for ($j=100000000;$j<100000007;$j++) {	$q = 'UPDATE antwortenwahl SET ';

for ($i=1;$i<22;$i++) {$x = round(rand(0,5)*10000+1000+rand(0.6,5)); $y = round(rand(0,5)*10000+1000+rand(0.3,5)); 
$q .= 'f'.$i.' = '.$x;
$q .= ', w'.$i.' = '.$x;
$q .= ", ";
}
$q .= 'ersteintrag="'.$datum.'" WHERE schl='.$j;

		$aendern = mysqli_query($link,$q); if ($dbzeigen) echo '<br>'.$aendern . $q.'<br>';
}

*/


// zu den einzelnen Seiten - entsprechend angepasst - verlagern

// Das Formular einstellen
echo '<form action="'.$naechsteseite.'.php?key='.$key.$linkerg.'" method="post" enctype="multipart/form-data">';
echo '<input type="hidden" name="key" value=""'.$key.'">';
$row = mysqli_fetch_array(mysqli_query($link,'SELECT * FROM '.$antwortenfile.' where schl = "'.$key.'"'));












$schwanztext =  'Die Eintr&auml;ge werden gespeichert und k&ouml;nnen sp&auml;ter erneut bearbeitet werden.';

?>