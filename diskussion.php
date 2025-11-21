<?php // zentrales Skript Diskussionstool - es ruft sich immer wieder selbst auf, Steuerung über $steuer; die User identifizieren sich über die MNummer
 error_reporting(E_ALL & ~E_WARNING);
 ini_set('display_errors', 1);

 include("../fstandard.php"); // Standardfunktionen
 include("textewahl.php"); // Daten zur Wahl; Namen der Datentabellen
 include('diskdaten.php'); // Die spezifischen Daten und Tabellennamen für diese Umfrage
 include("header.php"); // Der einheitliche Header für alle Diskussionen und die entspr. Funktionen
 $anb=1; // Die Steuerung, ob jemand die ANB anerkannt hat (=1)
 
 if (isset($_COOKIE['testserver'])) {$Mnr = $_COOKIE['testserver'];
	 $vorname="Testvorname"; $name="Testnachname"; $email = "mensa@zurgo.de"; $anb=0; $Sperre=1;// nur zum Testen
	 } else {
	 $Mnr = $_SERVER['REMOTE_USER'];}
	 $dbzeigen = 0;
	 $admin = (($Mnr == "04912113")  OR ($Mnr == "049005018"));
	 $debug = 0;

 // Daran denken, bei neuen Projekten ein erstes Statement mit der ID 2000 in die DB zu schreiben, damit die Nummerierung klappt

 //if ((time() < dzut($kandidatenzeigen_ab)) AND (!$admin)) {$spielwiese=1; //$kandidatendb = "spielwiesewahl"; $bildquelle="";} // vor Kandidaturschluss und Prüfung durch den Wahlausschuss wird eine Musterseite gezeigt 

 if (time() > dzut($Enddatum)) $Sperre = 1; // Ab diesen Datum keine Eingabe durch Ms
 
 if (!laenge($Teilnehmer)) { // Falls jemand die Browserzeile manuell eingibt, ohne das Datenfile anzugeben 
	echo 'Bitte verwende zum Navigieren ausschlie&szlig;lich den Button am Ende der Seite. Nur so ist sicherzustellen, dass die Kommentare an der richtigen Stelle stehen.<br><br><a href="'.$Einstieg.'">Zur&uuml;ck zur Einstiegsseite</a>'; die;
	} 

 $Anz=0;
// Übergebene Werte
 $steuer = ubeide('s');
 $Knr = ubeide('Knr');
 $Problem = ubeide('problem');
 if ((isset($_POST['Anz'])) OR (isset($_GET['Anz']))) {$Anz = ubeide('Anz');}
  else {
  if ((strstr(getenv("HTTP_USER_AGENT"),'Mobile')) OR (strstr(getenv("HTTP_USER_AGENT"),'Android'))) $Anz = 2;
 } // das ist die Vorgabe für die Anzeige bei mobilen Browsern, die mit mouseover in der Regel nicht gut umgehen können; es wird beim ersten Aufruf als Vorgabe übergeben, kann aber auf der Diskussionsseite geändert werden.


// **************************** Interimsmeldung bei Störungen ************************
//           if ($Mnr <> "04912113") {die('Entschuldigung, hier wird gerade am Skript gearbeitet - geht gleich weiter!');} 

if ($admin) { // Ermitteln, wieviele und welche Nutzer die ANB (nicht) anerkannt haben
	$db = mysqli_query($link,"SELECT Mnr, Erstzugriff, Letzter FROM Wahl2025Teilnehmer ORDER BY Erstzugriff");
	$q=$ja=$nein=0;
	echo 'Nur Admin:<br> (lfd.Nr. | M-Nr. | Vorname, Name | ErsterZugriff | LetzterZugriff)<br>';
	while ($x = db_result($db,$q,'Mnr')) {
	$DerStart = db_result($db,$q,'Erstzugriff'); 
	$DerLetzte = db_result($db,$q,'Letzter'); 
	ldapident($x,1); if ($vorname <> 'nicht gefunden') {$ja++; $vn=$vorname; $nn = $name; //alle
	ldapident($x,2); if ($vorname == 'nicht gefunden') {$nein++; echo "$q  |  $x  |  $vn, $nn  |  $DerStart  |  $DerLetzte<br>";}// ohne ANB
	}
	$q++;}
	echo "<br>Insgesamt $ja Teilnehmer, davon ohne ANB-Zustimmung: $nein<br><br>";
	}

//$Mnr = "04920002";   // zum Testen, um die ANB-Funktion auszuprobieren
// LDAP-Abfrage und Eintrag von Ms in die Datenbank
if (isset($_COOKIE['testserver'])) {$MNr = $_COOKIE['testserver'];
	$vorname="Testvorname"; $name="Testname"; $email = "mensa@zurgo.de"; // nur zum Testen
	} else { 
		ldapident($Mnr,2); if ($vorname == 'nicht gefunden') {$anb = 0; $Sperre=1;}
		ldapident($Mnr,1); // Alle MIGS, auch ohne ANB
		}
	if ($name=="") die("Zugang nur f&uuml;r Mitglieder");
	//echo "<br>Test LDAP-Abfrage $Mnr: $vorname $name $email";
	//$Mnr = "0499999999998"; $vorname="Testuser"; $name="ohne ANB"; // nur zum Testen
	if (mysqli_num_rows(mysqli_query($link,'SELECT Mnr FROM '.$Teilnehmer.' WHERE Mnr="'.$Mnr.'"')) == 0) {// Teilnehmer anlegen
		$eintrag = 'INSERT INTO '.$Teilnehmer.' SET Mnr="'.$Mnr.'", Vorname="'.umlaute($vorname).'", Name="'.umlaute($name).'", Email="'.$email.'", IP="'.$ipad.'", Erstzugriff="'.$datum.'"';
		$aendern = mysqli_query($link,$eintrag);
		if ($dbzeigen) echo "<br>47 $aendern $eintrag";
	}

// Die Kandidaten haben ein EXTRA-Schreibrecht:
 if  ($Mnr=="0497161" OR $Mnr=="04916454" OR $Mnr=="0495018" OR $Mnr=="0492537"  OR $Mnr=="04916452" OR $Mnr=="0497651" OR $Mnr=="0499898" OR $Mnr=="04917135"  OR $Mnr=="04914733" OR $Mnr=="04917074" OR $Mnr=="049215" OR $Mnr=="04923217" ) {$anb=1; $Sperre=0;}
// Das sind hier: Tanja Baudson, Stefan Langer,     Hermann Meier,     Elisabeth Rahe,     Kathrin Fuchs-Portela, Erwin Klein,    Yvonne Lindenberg, Jojo Lüken,          Anna Geiger,        Ole Oldenburg,     Jörg Benthien,     Mark Schmidt
$anb=1;
 $Sperre=1; //wenn aktiv, dann komplett gesperrt. - - während des Betriebs auskommentieren ! ! ! ! ! ! ! ! ! ! ! !


/*
if ($admin) { // Ermitteln, wieviele und welche Nutzer die ANB (nicht) anerkannt haben
echo '<br>LDAP-Test ANB='.$anb;
ldapident($Mnr,0); echo "<br>alle: $vorname $name"; // Alle MIGS, auch ohne ANB
ldapident($Mnr,1); echo "<br>alle auch ohne ANB: $vorname $name"; // Alle MIGS, auch ohne ANB
ldapident($Mnr,2); echo "<br>alle nur wenn ANB: $vorname $name"; // Alle MIGS, auch ohne ANB
echo '<br><br>';
}
*/ 



// Adminteil - nur für die Testphase und bei Verdacht auf Missbrauch, im Normalbetrieb deaktiviert
  if (($admin) AND ($debug)) { 
	$dbzeigen = 1; // Es werden alle DB-Befehle angezeigt
	//if ($dbzeigen) echo "<br>Nur 12113: Steuer $steuer Knr $Knr Mnr $Mnr Anz $Anz"; // Testen, wenn Speichern Merkwürdigkeiten zeigt

	if(getenv("SCRIPT_NAME")) {$Stich = getenv("SCRIPT_NAME");}
	if(getenv("HTTP_CLIENT_IP")) {$client = getenv("HTTP_CLIENT_IP");} else {$client = "?";}
	if(getenv("HTTP_X_FORWARDED_FOR")) {$forw = getenv("HTTP_X_FORWARDED_FOR");} else {$forw = "?";}
	if(getenv ("REMOTE_ADDR")) {$ipad = getenv("REMOTE_ADDR");} else {$ipad = "direkt";}
	//$ipad = substr($ipad, 0,14);
	$dns = @gethostbyaddr($ipad);
	$Senden = date("d.m.Y - H:i:s ");
	if(getenv("HTTP_REFERER")) {$Ref = substr(getenv("HTTP_REFERER"),7);} else {$Ref = "direkt";}
	$user = getenv("REMOTE_USER");
	$br = getenv("HTTP_USER_AGENT");
	} // Ende Admin

// ************************************Hauptteil********************************************
 // Vorab: Speichern
  if ($steuer == 7) {//Teilnehmerdaten speichern
	//$vorname = ubeide('vorname'); $name = ubeide('name'); $email = ubeide('email');
	// Deaktivieren, wenn LDAP-Anbindung
	if ((laenge($vorname) > 1) AND (laenge($name) > 2)) {//Zumindest Vorname/Name/MNr vorhanden
	$eintrag = 'UPDATE '.$Teilnehmer.' SET Vorname="'.umlaute($vorname).'", Name="'.umlaute($name).'", EMail="'.$email.'", IP="'.$ipad.'", Erstzugriff="'.$datum.'" WHERE Mnr="'.$Mnr.'"';
	$aendern = mysqli_query($link,$eintrag);
	if ($dbzeigen) echo "<br>194 $aendern $eintrag <br>";
	$steuer = 1;
	} else {echo '<b>Bitte Angaben vervollst&auml;ndigen</b><br>'; $Mnr = 0; $steuer = 1;}
	}
	//Feststellen, ob der Teilnehmer schon registriert ist
	$teiln = mysqli_query($link,'SELECT * FROM '.$Teilnehmer.' WHERE Mnr = "'.$Mnr.'"');
	if (mysqli_num_rows($teiln) > 0) {  //Mnr ist schon registriert
	$eintrag = 'UPDATE '.$Teilnehmer.' SET IP="'.$ipad.'", Letzter="'.$datum.'"';
	$eintrag .= ' WHERE Mnr="'.$Mnr.'"';
	$aendern = mysqli_query($link,$eintrag);
	if ($dbzeigen) echo "<br>$aendern $eintrag <br>";

	$vorname = db_result($teiln,0,'Vorname');
	$name = db_result($teiln,0,'Name');
	$email = db_result($teiln,0,'Email');
	} else { // Teilnehmer neu anlegen
	$eintrag = 'INSERT INTO '.$Teilnehmer.' SET IP="'.$ipad.'", Erstzugriff="'.$datum.'", Mnr="'.$Mnr.'"';
	//$eintrag = 'INSERT INTO '.$Teilnehmer.' SET Vorname="'.$vorname.'", Name="'.$name.'", Email="'.$email.'", IP="'.$ipad.'", Erstzugriff="'.$datum.'", Mnr="'.$Mnr.'"';
	$aendern = mysqli_query($link,$eintrag);
	if ($dbzeigen) echo "<br>98 $aendern $eintrag";
	$vorname = "";}

  if (($steuer == 9) OR ($steuer == 10)) {
	  //Kommentar editieren
  if ($steuer == 10) { 
	 $Komm = "durch den/die Autor/in gelöscht"; $steuer = 9;
	 } else {$Komm = ubeide('komm');}
	 $Komm = umlaute($Komm);
	 if (laenge($Komm) > 1) { //Leereinträge vermeiden
	 $eintrag = 'UPDATE '.$Kommentar.' SET These="'.$Komm.'" , Hinweis="ge&auml;ndert am '.$ddatum.' Uhr';
	 // .' von '.$Mnr.' '.db_result($teiln,0,'Vorname').' '.db_result($teiln,0,'Name');
	 $eintrag .= '" WHERE Knr="'.$Knr.'"';
	 $aendern = mysqli_query($link,$eintrag);
	 if ($dbzeigen) echo "<br> $aendern $eintrag";
	 $steuer = 0;
	 }
   }

  if ($steuer == 11) {//posneg speichern - wird derzeit nicht genutzt
    // Hier stellt sich die Frage, ob das noch hinzugefügt werden sollte

	$steuer = 0;
   }

 if ($steuer == 8) {//Kommentar speichern
  $Komm = ubeide('komm');
  $Komm = umlaute($Komm);
  if ((laenge($Komm) > 1) OR ($Problem)) { //Leereinträge vermeiden

	$letzter = mysqli_query($link,'SELECT * FROM '.$Kommentar.' ORDER BY Knr DESC');
	$q = mysqli_query($link,'SELECT Knr FROM '.$Kommentar.' WHERE These ="'.umlaute($Komm).'"');
	if (mysqli_num_rows($q) > 0) { // diese Bemerkung ist schon vorhanden - id übergeben
		//if ((db_result($letzter,0,'These') == $Komm) AND (db_result($letzter,0,'Mnr') == $Mnr)) {//doppelter Eintrag - kein Speichern!
		} else {
		$k = db_result($letzter,0,'Knr'); $k0 = $k+1;
		$eintrag = 'UPDATE '.$Kommentar.' SET Kommentar="'.$k0;
		if ($Problem) {
			$teiln = mysqli_query($link,"SELECT * FROM ETeilnehmer WHERE Mnr = '".$Mnr."'");
			$eintrag .= '", Verbergen = "4", Hinweis ="Gemeldet am '.$datum.' von '.$Mnr.' '.db_result($teiln,0,'Vorname').' '.db_result($teiln,0,'Name');
			// Meldung an Admin fehlt noch!
			}
		$eintrag .= '" WHERE Knr="'.$Knr.'"';
		$aendern = mysqli_query($link,$eintrag);
		if ($dbzeigen) echo "<br>$aendern $eintrag <br>";
		if (laenge($Komm) > 1) {
			$eintrag = 'INSERT INTO '.$Kommentar.' SET Knr="'.$k0.'", These="'.$Komm.'", Bezug="'.$Knr.'", Mnr="'.$Mnr.'", IP="'.$ipad.'", Datum="'.$datum.'", Medium="Internet"';
			$aendern = mysqli_query($link,$eintrag);
		if ($dbzeigen) echo "<br>$aendern $eintrag <br>";
		if ($aendern) {include('benachrichtigen.php'); benachrichtigen($k0);}
		// Wenn der neue Beitrag im Feld $feld des Kandidaten $kand steht und dieser in der Adress-DB bei $Nachricht eine 1 stehen hat, bekommt er eine Mail mit dem Kommentar $Komm des Mitglieds mit der Nummer $Mnr $vorname $name
	}}}
   $steuer = 0;
  }


if (($steuer == 1) OR ($steuer == 2)) { // Kommentar eingeben 2= editieren
	if (!$Knr) {die ("Eingabefehler");}
	if (laenge($vorname) < 2) {  // Kommentar angewählt, aber noch nicht registriert
	 echo '<b>Gern nehmen wir deine Meinung auf!<br>Aber nicht anonym, bitte. ';
	 echo 'Hier kannst du dich zu erkennen geben:</td></tr>';
	 echo '<form action="diskussion.php" method="post"><tr><td><span class="text2">Vorname:<br><input name="vorname" type="text" style="background-color: lightgreen;" size="30" maxlength="32" value=""></span></td><td><span class="text2">Name:<br><input name="name" type="text" style="background-color: lightgreen;" size="30" maxlength="32" value=""></span></td>';
	 echo '<td><span class="text2">M-Nr:<br>'.$Mnr.'</span></td>';
	 echo '<td><span class="text2">E-Mail:<br><input name="email" type="text" style="background-color: lightgreen;" size="30" maxlength="64" value=""></span></td></tr>';
	 echo '<input name="s" type="hidden" value="7">';
	 echo '<input name="Knr" type="hidden" value="'.$Knr.'">';
	 echo '<input name="Anz" type="hidden" value="'.$Anz.'">';
	 //echo '<input type="hidden" name="datenfile" value="'.$Datenfile.'">';
	 echo '<tr><td colspan="5"><span class="text2"><input type="submit" value="Eintragen - und mit Enter abschlie&szlig;en"></span></form>
	 </td></tr></table></td></tr>';
	 include ("disclaimer.php");
	 echo '</table></body></html>';
	 die;
	} else
	{ //Knr vorhanden, MNr vorhanden: Kommentar eingeben
	 //echo "$Knr $Mnr $steuer";
	 $texte = 'SELECT * FROM ';
	 if ($Knr < 2000) {$texte .= $Texte;} else {$texte .= $Kommentar;}
	 $texte .= ' WHERE Knr="'.$Knr.'"';
	 $texte = mysqli_query($link,$texte);
	 echo '<form action="diskussion.php" method="post"><tr>';
	 echo '<td width="45%"><span class="text2">'.nl2br(db_result($texte,0,'These')).'</span></td>';
	 echo '<td><span class="text2">';
 if ($steuer == 2) echo '<b>Bearbeiten/L&ouml;schen: </b>';
	//echo "Zur Verbesserung der Lesbarkeit kannst du durch Einf&uuml;gen von '&#60;br>'<br>eine Leerzeile erzeugen.<br><br>";
	// *************************************************************************************************

	// Die Kandidaten bekommen das Eingabefeld, die anderen einen entsprechenden Hinweis.
	if ($Mnr == "14912113") echo 'Um den Kandidaten vor der MV ausreichend Zeit zum Antworten zu geben, endet die Fragerunde am 3. April<br>';
	if ($anb) {echo '<font color="darkred"><b>Kopiere bitte KEINE Texte aus Word oder dergleichen in ein Eingabefeld. Es k&ouml;nnte sein, dass du damit unsichtbare Formatierungen oder Tags mitnimmst, die ein Abspeichern verhindern.<br> Wenn du in Word vorgeschrieben hast: Kopiere den Text in einen sogenannten Plain-Text-Editor, z.B. den im Zubeh&ouml;r von Windows. Markiere dort den Abschnitt erneut und kopiere ihn dann in das Eingabefeld. Dann sollte es klappen.<br><br>Bitte verwende keine html-Tags und m&ouml;glichst auch keine Sonderzeichen - so verhindert z.B. ein verwendetes Paragraphenzeichen das Abspeichern!<br><br></b></font>';
	echo 'Kommentar von '.$vorname.' '.$name.' '.$Mnr.':<br>';

	echo '<textarea name="komm" cols="55" rows="10" style="border: solid #0a1f6e 1px;">';
	if ($steuer == 2) echo db_result($texte,0,'These');
	echo '</textarea>';
	if ($steuer == 1) echo '<input name="s" type="hidden" value="8">';
	if ($steuer == 2) {echo '<input name="s" type="hidden" value="9">';
		echo '<br><i>Diesen Beitrag unwiderruflich l&ouml;schen.</i><input name="s" type="checkbox" value="10"><br>';}
	echo '</span></td>';
	echo '<input name="Knr" type="hidden" value="'.$Knr.'">';
	echo '<input name="Mnr" type="hidden" value="'.$Mnr.'"></td><td><tr><td>';
	echo '<input name="Anz" type="hidden" value="'.$Anz.'">';
	//echo '<input type="hidden" name="datenfile" value="'.$Datenfile.'">';
	// echo '<span class="text2">Diesen Beitrag als nicht den Regeln entsprechend kennzeichnen.<input name="problem" type="checkbox" value="4"></span>';
	echo '</td><td><span class="text2">';
	if (!$Sperre) {
		echo '<input type="submit" class="button" value="Absenden"></span></form>';
		} else {
		echo '<font color="red"><h2><b><i>Ab dem '.$Enddatum.' ist keine Eingabe mehr m&ouml;glich!</i></b></h2></font>';
		}
	} else {echo 'Mit dem Zur&uuml;ck-Button deines Browsers kommst du wieder auf die Diskussionsseite.';}
	echo '</td></tr></table><br><br><br>';
	include ("disclaimer.php");
	echo '</body></html>';
	die;
	} // End Knr vorhanden
 } // Ende steuer=1 oder 2


// steuer = 0
echo '<b>';
echo "Hallo $vorname, ";
echo '<br>willkommen zur&uuml;ck zum Gespr&auml;ch mit den Kandidaten (*) zur Wahl '.$jahr.'!</b><br><br>';

if (!$anb) echo '<span class="text3"><font color="red">Du hast derzeit nur einen lesenden Zugriff auf diese Seite.</font><br>Um diesen Dienst aktiv nutzen und mitdiskutieren zu k&ouml;nnen, bedarf es deiner Zustimmung zu den Allgemeinen Nutzungsbedingungen und den Regeln f&uuml;r Online-Dienste <a href="../ANB_und_Regeln.pdf">(ANB)</a>. Diese Zustimmung kannst du auf <a href=https://db.mensa.de> db.mensa.de </a> erteilen. Allerdings dauert es bis zu einem Tag, bis das auf dem aktive.mensa.de-Server angekommen ist. Bis dahin kannst du dich ja schon mal einlesen...<br><br></span>';


echo '<span class="text3">'.umlaute($Anfangstext); // siehe textewahl
echo '<br></span>';

if ($spielwiese OR iget('sp')) {
	$kandidatendb = 'spielwiesewahl';
	echo '<h3>Dies ist die "Spielwiesen-Version", die echten Kandidaten kommen, sobald der Wahlausschuss das freigegeben hat. Bis dahin kannst du hier frei experimentieren - alle Testeintr&auml;ge werden gel&ouml;scht, wenn hier die echten Kandidaten erscheinen.</h3>';}

$result = mysqli_query($link,'SELECT * FROM '.$Kommentar.' ORDER BY Knr DESC'); 
$Anzkomm = mysqli_num_rows($result) -1; //echo $Anzkomm.'Kommentare'; mit Korrektur, da die Leerzeile in der DB  mitgezählt wird
$letztenummer = db_result($result,0,'Knr');
echo '<span class="text5"><br>'.$Anzkomm.' Kommentare, letzte Nummer: '.$letztenummer.'</span>';
if ($Mnr == "04912113") {  // Nur für den Admin zur Kontrolle, ob Merkwürdiges geschieht
	$tln = mysqli_query($link,'SELECT * FROM '.$Teilnehmer.' ORDER BY Letzter DESC');
	$mit = mysqli_query($link,'SELECT * FROM '.$Teilnehmer.' WHERE vorname <> ""');
	echo '<span class="text5"><br>@WK: '.mysqli_num_rows($tln).' Teilnehmer, davon sind '.mysqli_num_rows($mit).' registriert ';
	for ($i = 0;$i<MIN(600,mysqli_num_rows($tln));$i++) {
	$M = db_result($tln,$i,'name');
//	$M .= ' ';
//	$M .= db_result($tln,$i,'vorname');

//	if ($M == "") $M = db_result($tln,$i,'MNr');
		$result = mysqli_query($link,'SELECT * FROM '.$Kommentar.' WHERE Mnr = "'.db_result($tln,$i,'Mnr').'"');
	if ($M<>"")	echo $M.' ('.mysqli_num_rows($result).'), ';
	}


	echo '<br></span>';
	}

echo '<br></span></td></tr>';

echo '<tr><td width="35%" valign="top" colspan="2"><span class="text2"><font color="green">';
echo '<b><u>Hilfreiche Links:</u><br>'.$Links.'</b></span></td>';

echo '<td><span class="text3" valign="top">';
echo '<form action="diskussion.php" method="post"><b><h2>Anzeigearten:</h2></b><br></span><span class="text3">';
echo '<input type="radio" name="Anz" value="1" '; if ($Anz > 0) echo 'checked="checked"';
echo ' >Es werden die kompletten Kommentare angezeigt';
if ($Anz == 2) echo ' (Vorgabe, wenn ein mobiler Browser erkannt wurde)';
echo '.<br>';
echo '<input type="radio" name="Anz" value="0" '; if ($Anz == 0) echo 'checked="checked"';
echo ' >L&auml;ngere Texte werden auf eine Zeile gek&uuml;rzt und beim Ber&uuml;hren mit dem Mauszeiger vollst&auml;ndig angezeigt.<br>';
//echo '<input type="hidden" name="datenfile" value="'.$Datenfile.'">';
echo '<input type="submit" class="button"  value="Anzeigeart w&auml;hlen">';
echo '</form></span></td></tr>';

echo '<tr><td colspan="2"><span class="text2"><b>Hier die Kandidaten</b></span><span class="text5"><br>';
if (!$Sperre) echo '(Um dem jeweiligen Kandidaten eine Frage zu stellen, dessen Namen anklicken)';
echo '</span></td>';
echo '<td><span class="text2" valign="top"><b>Hier die Fragen und Antworten </b></span><span class="text5">Lfd.Nr, Autor, MNr., Text</span><span class="text5"><br>'; 
if (!$Sperre) {echo '(Zum Kommentieren eines Kommentars den jeweiligen Kommentar anklicken)';} else {if ($anb) echo '<font color="red"><b><u>Mit dem '.$Enddatum.' wurde die Eingabefunktion f&uuml;r Fragen deaktiviert.</u></b></font>';} 
echo '</span></td></tr>';

$texte = mysqli_query($link,'SELECT * FROM '.$Texte.' ORDER BY lfdnr ASC');
$kandidaten = mysqli_query($link,'SELECT * FROM '.$kandidatendb.' ORDER BY Knr ASC');
$k0 = mysqli_num_rows($kandidaten);


for ($k = 0;$k<=$k0;$k++) { // Alle Kandidaten hintereinander schreiben
	if ($k==$k0) {$Knr = 97; $These = "#97 = Allgemeine Frage - letztlich an alle Kandidaten - und allgemeine Diskussion.";
	} else {
	$kandi = mysqli_fetch_array($kandidaten);
	$Knr = $kandi['Knr'];
	$These = '#'.$Knr.' '.$kandi['vorname'].' '.$kandi['name'].', M-Nr. '.substr($kandi['mnummer'],3).' <br>kandidiert als ';
	if ($kandi['nachricht'] > 0) {$EMailKontakt = $kandi['email'];} else { $EMailKontakt = '';}  // Anzeige der E-Mail-Adresse erwünscht		
		$n=1;
	for ($i=1;$i<=$aemter;$i++) {
	if ($kandi["amt$i"]) { 
		if ($n>1) $These .= ', ';
		$These .= $rs[30*$i]; $n++;}}

	//if ($kandi['amt1']=="1") $These .= 'f&uuml;r den Vorstand<br>'; if ($kandi['amt2']) $These .= 'als Finanzpr&uuml;fer<br>';if ($kandi['amt3']) $These .= 'als Schlichter';
	
	}
	echo '<tr><td>';
	if ($k<$k0) {//echo '<IMG SRC="'.$bildquelle.'img/'.$kandi['bildfile'].'" width="75">';
	if (laenge($kandi['bildfile'])) {echo '<IMG SRC="'.$bildquelle.'img/'.$kandi['bildfile'].'" width="75">';} else {echo '<IMG SRC="/leer.jpg" width="55">';}
	}
	// ldapident($kandi['mnummer'],2);if ($vorname=='nicht gefunden') echo '<br>Keine ANB-Zustimmung'; // Kandidaten ohne ANB-Zustimmung

	echo '</td><td><span class="text2"><a name="'.$Knr.'"></a>';
	if (db_result($texte,$k,'Leer')) { // Leerzeile
		echo '<span class="text5"><i>'.nl2br($These).'</i></span></td></tr>';
		} else { 
	echo '<a class="tooltip" href="diskussion.php?Knr='.$Knr.'&Mnr='.$Mnr.'&s=1&Anz='.$Anz.'"> <span style="left: 0px; width: 210px;"><i>#'.$Knr.' anklicken, um eine Frage zu stellen.</i></span>';  // '.$kandi['vorname'].'
		echo $These;
	echo '</a>';
	if ($EMailKontakt AND $Knr <97) {echo '<br><br> <a style="font-size: 10px;"  href="mailto:'.$EMailKontakt.'">E-Mail: '.$EMailKontakt.'</a><br>';} // E-Mail wird neben dem Bild gezeigt
	
	// posneg($Knr,strpos(db_result($texte,0,'pos'),$Mnr),strpos(db_result($texte,0,'pos'),$Mnr));

		echo '<br></td>';
		echo '<td><span class="text5">';
	kaus ($Knr,"",$Mnr,-1);
	echo '<br></span></td></tr>';
	}
}
echo '</span></td></tr>';
echo '<tr><td colspan="2" align="center">';
//echo '<input type="hidden" name="datenfile" value="'.$Datenfile.'">';
echo '<input name="Anz" type="hidden" value="'.$Anz.'">';
//if (!$Sperre) echo '<br><input type="submit" class="button" value="Absenden und speichern"></form>';
echo '<br><br><br>';
echo '<a class="button" href="diskussion.php">&nbsp; nach oben &nbsp;</a>'; // Zur&uuml;ck zur Startseite &nbsp;</a>';
echo '<br><br><br><br>';
echo '<a class="button" href="index.php">&nbsp; Zur&uuml;ck zur Seite mit den Kandidaten &nbsp;</a>';
echo '<br><br><br><br><br><br><br><br></td>';
echo '<td class="text5">Bei den letzten Eintr&auml;gen steht vor der Kommentarnummer in rot ein "neu:". So kann man auf einen Blick die neuesten Beitr&auml;ge erkennen.<br><br><br><br></td>';
echo '</tr>';
echo '</table>';
include ("disclaimer.php");

?>
</body>
</html>

