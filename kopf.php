<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
 <html><head>
 <link rel="icon" href="M1.ico" type="image/x-icon">
 <meta http-equiv="content-type" content="text/html; charset=utf-8" />
 <meta name="Author" content="Dr. Hermann Meier, Mensa in Deutschland e.V.">
 <meta name="robots" content="nofollow">
 <meta name="description" content="Wahlinfo-Seite des Vereins Mensa in Deutschland e.V.">
 <link href="../aktiv.css" rel="stylesheet" type="text/css">

<?php // Dieser Teil startet die Seiten, schreibt den Kopf, enthält die Funktionen, definiert die DB-Tabellen, speichert übergebene Werte und lädt die Variablen zur Umfrage

 error_reporting(E_ALL & ~E_WARNING);
 ini_set('display_errors', 1);

// Gemeinsame Funktionen zuladen
 include ('../fstandard.php');	// Standardfunktionen laden
 include ('funktionen.php'); // Funktionen für die spezielle Anwendung laden
 include_once ("config.php"); // Datenbank-Zugangsdaten
 include ($daten.'.php');	// Die individuellen Daten der jeweiligen Umfrage - $daten steht am Anfang jeder einzelnen Seite

// Funktionen und Texte nur für dieses Projekt:

// Funktionen

 
 function abbruch($text) { // Abbruch nach Manipulation
	echo '<p class="text1b fa55">'.$text.'<br>';
	$spalten = 7; weiter(1,1,1,1,1);
	echo '</table></td></tr>';
	$schwanztext = '';
	$weiter = "";
	include ("schwanz.php");
	die;
	}

 function skala5($i,$skala5,$mitfarbe,$mitbem) {//Skala drucken mit i=nxxxx mit n=Wert und x=Bemerkungs-ID
	global $skala5f,$eingabe,$offset,$MNr,$link;
	if ($i) {$q = round($i/10000,0); $b=$i-$q*10000; //echo "<br>i=$i q=$q b=$b";
	echo '<td align="center" valign="top" class="st ';
	$bem = ""; if ($b>0) $bem = db_result(mysqli_query($link,'SELECT bem FROM bemerkungenwahl WHERE id='.$b),0,'bem');

	if ($MNr <> "04912113") { // "0495018x"
	if (($mitbem) AND (laenge($bem) > 2)) echo ' hell"';// title="'.$bem; // Bemerkung erscheint im title-Tag
	echo '"><span class="text1'; if ($skala5[1] == 1) {echo 'n';} else {echo 'b';}
	if ($mitfarbe) echo ' fa'.$skala5f[$q];
	echo ' center">';
	if ($mitbem) echo '<br>';
	echo $skala5[$q]; echo '</span>';
	if ($mitbem){ 
		echo '<a class="tip" href="#" ><br><br>'.substr($bem,0,19);
		if (laenge($bem) > 19) echo ' ...';
		//echo '<span style="left: '.$offset.'px;">'.$bem.'</span></a>';
		echo '<span><br>'.$bem.'<br><br></span></a>';
		//	if (laenge($bem)) {echo '<span class="text4n" style="font-size: 7px;"><br>'.substr($bem,0,19);
		echo '</span>';
		}
	} else {
		if (($mitbem) AND (laenge($bem) > 2)) echo ' hell" title="'.$bem; // Bemerkung erscheint im title-Tag
		echo '"><span class="text1'; if ($skala5[1] == 1) {echo 'n';} else {echo 'b';}
		if ($mitfarbe) echo ' fa'.$skala5f[$q];
		echo ' center">';
		if ($mitbem) echo '<br>';
		echo $skala5[$q].'</span>';
		if ($mitbem){
		if (laenge($bem)) {echo '<span class="text4n" style="font-size: 7px;"><br>'.substr($bem,0,19);
		if (laenge($bem) > 19) echo ' ...';
		echo '</span>';}}
		}
	echo '</td>';}
	else {echo '<td class="st" align="center"> </td>';}
 }

 function skalalegende($i0,$ue,$skalafarbe,$skalazeichen,$skalatext) {
	echo '<span class="text2n"><br><b>'.$ue.':</b><br>';
	for ($j=1;$j<$i0;$j++) {echo '<span class="text2n fa'.$skalafarbe[$j].'">'.$j.'/'.$skalazeichen[$j].'</span><span class="text2n"> = '.$skalatext[$j].'; ';
	} 
	$j=$i0; 
	echo '<span class="text2n f'.$skalafarbe[$j].'">'.$j.'/'.$skalazeichen[$j].'</span><span class="text2n"> = '.$skalatext[$j].'</span>';
 }

 function welchesamt($kand) {
	global $rs;
	$q = 0; $qa = ""; for ($i=1;$i<6;$i++) {
		if ($kand["amt$i"]) {
			if ($q>0) {$qa .= ', ';}
	$qa .=$rs[$i*30]; $q++;}}
	return $qa;
 }

 function weiter ($einzeln,$ue,$ressorts,$anforderungsliste,$fragen) { // zeigt das untere Menu oberhalb der Fußleiste
	global $linkerg,$spalten, $privat,$mobil,$editieren_bis,$einzelkand,$spielwiese,$link;
	
	$spintern = $einzeln+$ue+$ressorts+$anforderungsliste+$fragen;
	$erg="";
	if ($privat) {$erg = '?privat=1';
	if ($spielwiese) $erg .= '&spielwiese=1';

	echo '<tr><td colspan="'.$spalten.'" valign="top" align="left" style="margin:0; padding:0; background-color: #cccccc;">'; //Menuleiste
	//	echo '<tr style="margin:0; padding:0; border: 1px solid #0a1f6e;"><td colspan="'.$spalten.'" valign="top" align="left" style="margin:0; padding:0; background-color: #cccccc;">'; //Menuleiste
	//echo '<span class="text3n fa55">Hinweis: Dieses Menu sieht M (au&szliger den Kandidaten) erst nach Beendigung der Editierfrist ('.$editieren_bis.')!<br></span>';	
	
	echo '<table width="100%" border="0" style="margin:0; padding:0;">';
	if ($spintern) {
	 echo '<tr><td style="padding-left:10px;"><h2>Weiter:</h2></td>';
	 if ($mobil>1) echo '</tr><tr>';
	 if ($einzeln) {
		echo '<td align="center" class="text1b fa58">Einzelvorstellung der Kandidaten:<br>Klick oben auf das Foto!</td>';
		if ($mobil>1) echo '</tr><tr>';
		}
	 if ($ue) {
		echo '<td align="center"><a class="menu" style="width:100px;" href="index.php'.$erg.'"><span class=" fa58 center">Alle Kandidaten</span></a></td>';	 
		if ($mobil>1) echo '</tr><tr>';
		}
	 if ($ressorts) {
		echo '<td align="center"><a class="menu" style="width:100px;" href="ressorts.php'.$erg.'"><span class=" fa58 center">Ressort-Pr&auml;ferenzen</span></a></td>';
	 	if ($mobil>1) echo '</tr><tr>';
		}
	 if ($anforderungsliste) {
		echo '<td align="center"><a class="menu" style="width:100px;" href="anforderungen.php'.$erg.'"><span class=" fa58 center">Anforderungen</span></a></td>';
	 	if ($mobil>1) echo '</tr><tr>';
		}
	 if ($fragen) echo '<td align="center"><a class="menu" style="width:100px;" href="fragen.php?privat=2"><span class=" fa58 center">Fragen & Antworten</span></a></td>';
	 echo '</tr>';
	 }
	echo '<tr>';
	
	echo '<td colspan="'.$spintern.'" class="text1b nuroben nurlinks"><br>Zum <a class="menu" style="width:350px;" href="diskussion.php">Diskussionstool zur Wahl</a><br><br></td>';
	
	// Der WahlOMat:
	if ($fragen) echo '<td class="nuroben nurlinks text1b" style="padding-left: 20px">Zu Hermanns <a class="menu" style="width:260px;"	href="fragen.php?privat=2"'.$erg.'><span class=" fa58 center">M-Wahl-O-Mat</span></a></td>';
	
	echo '</tr></table></td></tr>';

	} else { // Wahlausschuss-Seite  - ist das überhaupt noch gefragt??? **********************
	
	if ($spielwiese) $erg = '&spielwiese=1';
	echo '<tr><td valign="middle" colspan="'.$spalten.'" class="rh">';
	//	echo '<span class="text3b"><br>Klicke auf das jeweilige Foto, um Details über den Kandidaten zu sehen!<br><br>';
	if ($ue) {
		echo '<br><a class="menu" style="width:600px; margin: 5 5 5 5;" href="index.php?privat='.$privat;
		if ($spielwiese) echo '&spielwiese=1';
		echo $linkerg.'">Hier geht es zur&uuml;ck zur Seite mit allen Kandidaten.</a><br><br>';}
	echo '</td></tr>'; 
	
	echo '<tr><td colspan="'.$spalten.'" class="backp"><table>';
	$w = 250+($mobil>1)*150; $w1 = $w+130;
	//echo '<span class="text3n fa55">Hinweis zu dieser Musterseite: Sobald die "wirklichen" Kandidaten eingetragen sind, wird dieses Menu ausgeblendet - das sieht M erst nach Beendigung der Editierfrist ('.$editieren_bis.'), nachdem die Kandidaten Gelegenheit hatten, ihre Informationen einzutragen.<br></span>';
	echo '<tr><td width="'.$w.'" class="backp" valign="top" style="padding: 5 5 0 0;"><span class="text3b">&Uuml;ber das Diskussionstool gibt es schon jetzt die M&ouml;glichkeit, online mit den Kandidaten zu kommunizieren:</td>';
	echo '<td colspan="2" class="backp nurlinks" valign="top" style="padding: 5 5 0 10;"><span class="text3b">Wem die "offiziellen" Wahlinformationen noch nicht reichen: Au&szlig;erhalb des normalen Wahlverfahrens haben die Kandidaten die Gelegenheit, auf privat organisierten Seiten weitere Informationen &uuml;ber sich bereitzustellen:</td></tr>';  
	echo '<tr><td rowspan="2" valign="middle" class=" backp"><a class="menu" style="width:250px;" href="diskussionstool.php">Diskussionstool zur Wahl</a><br><br></td>';
	echo '<td class="nurlinks backp"  style="padding: 5 5 0 10;">';
	//echo '<span class="text3b">Seit 2016 werden mit dieser Seite einige weitere Informationen bei den Kandidaten angefragt. Dies kam bei den W&auml;hlerinnen und W&auml;hlern seither sehr gut an. Deswegen wiederholen wir das auch zu dieser Wahl.</span></td>';
	//echo '<td class="nurlinks backp"  style="padding: 5 5 0 10;"><span class="text3b">Hermann Meier hat (als rein private, vom Verein unabh&auml;ngige Initiative) den "Wahl-O-Mat" aus dem Jahre 2005 neu aufgelegt</span></td>';
	echo '</tr><tr>';

	echo '<td class=" nurlinks backp"  style="padding: 5 5 0 10;">';
	if ((time()>dzut($editieren_bis)) OR ($spielwiese)) {
		echo '<a class="menu" style="width:'.$w1.';" href="';
		if (laenge($einzelkand)) {echo 'einzeln.php?privat=1&'.$einzelkand.$linkerg.$erg;} else {echo 'ressorts.php?privat=1'.$erg;}
		echo '"><span class=" fa58 center">Weitere Informationen</span></a>';}
	else {
		echo '<span class="text3b fa51">Sie stehen nach dem '.$editieren_bis.' hier zur Verf&uuml;gung.<br>Wie das dann aussehen k&ouml;nnte, sieht man auf der <a href="ressorts.php?privat=1&spielwiese=1">Spielwiese</a></span>';}
	echo '<br><br></td>';
	//echo '<td class="nurlinks backp" style="padding-left: 20px"><a class="menu" style="width:'.$w1.';" href="fragen.php?privat=2'.$erg.'"><span class=" fa58 center">Der M-Wahl-O-Mat</span></a><br><br></td>';
	echo '</tr></table></td></tr>';
	} // Ende Wahlausschuss-Seite
	//	echo '</table></td></tr>';
 }

 function speichernmitbem ($name,$is0,$is8,$file) {  // Antworten und Bemerkungen ins Antwortfile schreiben
	//$name ist der Buchstabe, unter dem der Wert in der Datenbank gespeichert werden soll; $is0 bis $is8 sind die lfdNr dieser Werte
	//$name.lfdNr ist auch der per POST übergebene Wert; b.$name.lfdNr ist die zugehörige Bemerkung
	//Die Daten werden jeweils in der Form n*10000+b gespeichert - dabei ist n die Bewertung und b die ID der Bemerkung (1000<b<=9999)

	global $datum, $dbzeigen,$key,$link;

	//echo "XXX $name,$is0,$is8,$file ;".$name."1".'='.ipost($name."1");
	$aendern = 0;
	$ein = 0; // Das ist der Zähler, ob überhaupt etwas übergeben wurde
	$eintrag = 'UPDATE '.$file.' SET ';
	for ($i=$is0;$i<=$is8;$i++){ // die lfdNrn durchgehen
		$f = $name.$i; // Feldbezeichnung in der Datenbank und bei der Datenübergabe per POST der jeweilige Wert
		//echo "<br> f ist $f und ipost ist ".ipost($f);
		$wert = ipost($f); if ($wert > 5) $wert= 5; // Keine Werte über 5 möglich
		if ($wert > 0) { 
			$ein++; // Es wurde für diese Position etwas übergeben
			$wert = ABS($wert*10000); 
			} else {
			if ($file=="antwortenwahl") {$wert=10000;} else {$wert=0;}
			}

		$b = 'b'.$f; $bid = 0; 
		$bem = umlaute(ipost($b)); //echo "$i: wert $wert bem $bem ein $ein - ";
		if (laenge($bem) > 1) { 
			$ein++; // Es wurde ein Bemerkungstring mit Länge > 1 übergeben
			$bid = bemerkungspeichern($bem); } // Hier wurde die Bewertung gespeichert (falls neu) und die ID übergeben
		$wert = $wert+$bid;
		$eintrag .= $f.'='.$wert.', ';
		}
		$eintrag .= 'anz = '.$ein.',';
		$eintrag .= ' letzteintrag = "'.$datum;
		$eintrag .= '" WHERE schl='.$key;
		if ($ein) dbspeichern($eintrag);
 }

 // Ende Funktionsteil

// Kopf erzeugen, Basisdaten laden
 // $MNr = $_SERVER['REMOTE_USER']; Ist schon in textewahl
 // Header schreiben
 $privat = ubeide('privat');
 if ($dieseseite == "fragen") $privat=2;


 //mobilumschaltung(1); // =1, wenn es eine Mobilversion gibt
 if ($privat) $linkerg .= '&privat='.$privat;
 if ($spielwiese) {$linkerg .= '&spielwiese=1'; 
 $kandidatendb = "spielwiesewahl";}


 echo "<title>$title</title>";
 echo '</head>';
 

 echo '<body id="main"><center>';
 echo '<table width="';
  if ((($mobil >1) OR ($privat)) AND ($dieseseite <> 'einzelnxxx')) {echo '100%';} else {echo '960px';} 
  if (($mobil<2) AND ($dieseseite <> "fragen") OR ($dieseseite == 'einzeln')) echo '" style="max-width: 960px;';
  echo '" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#EEF3F9" ';
  echo '<tr><td>';
  echo '<div id="header"><div id="R1"><div id="headerBGneu"><div id="headerR3"><div id="headerTITEL">';

 echo '<a href="diskussionstool.php" style="text-decoration: none;"><div id="headerSLOGAN">'.$slogan.'</div></a>';
 echo '</div></div></div></div></div></div>';

 echo '</td></tr>';
 echo '<tr class="mit" style="margin 10 0 0 0;"><td><table width="';
 if (($mobil >1) OR ($privat)) {echo '99%';} else {echo '960px';} 
 echo '" bgcolor="#EEF3F9" style="padding-left: 2px;">';


//Datenbank verbinden
 error_reporting(0); // Verhindert die Warnmeldung zu mysqli
 $link = mysqli_connect(MYSQL_HOST,MYSQL_USER, MYSQL_PASS) OR die("Keine Verbindung zur Datenbank. Fehlermeldung:".mysqli_error());
 mysqli_select_db($link,'wahl') OR die("Konnte Datenbank nicht benutzen, Fehlermeldung: ".mysqli_error());
 error_reporting(E_ALL); ini_set('display_errors','1');

// Authentifizierungs- und Anonymisierungsteil
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




// Speicherteil: Wenn Daten übergeben werden, werden sie hier gespeichert
 $speichern = ipost('eingabe'); 
 if ($speichern) { // Die Links und die Team-Werte speichern
	$hplink = ipost('hplink'); 
	$videolink = ipost('videolink'); 
	$ein = laenge($hplink)+laenge($videolink); 
	if ((laenge($hplink) AND (substr($hplink,0,4) <> 'http'))) $hplink = 'http://'.$hplink;
	if ((laenge($videolink) AND (substr($videolink,0,4) <> 'http'))) $videolink = 'http://'.$videolink;
	$anz = ubeide('anz');
	$eintrag = 'UPDATE '.$kandidatendb.' SET hplink="'.$hplink.'", videolink="'.$videolink.'", team1="'.ipost('team1').'", team2="'.ipost('team2').'", team3="'.ipost('team3').'", team4="'.ipost('team4').'", team5="'.ipost('team5').'", ';
	$eintrag .= 'letzteintrag = "'.$datum;
	$eintrag .= '" WHERE schl='.$key;
	dbspeichern($eintrag);
	// Ressortangaben und Anforderungen pp speichern
	speichernmitbem ('r',1,27,$kandidatendb); // Die Felder rn = Ressortangaben werden gespeichert
	speichernmitbem ('a',1,16,$kandidatendb); // Die Felder an = Anforderungsangaben werden gespeichert
 }

 $speichern = ipost('fragen'); 
 $verwerfen = 0; if (strpos($speichern,"verwerfen")) $verwerfen = 1; 
 if (strpos($speichern,"endg")) { // individuellen Datensatz löschen, aber als Basis für Meinungsbild erhalten
	srand((double)microtime()*100000000);
	$eintrag = 'UPDATE antwortenwahl SET schl="'.rand(1000000,9999999).'"';
	$eintrag .= ', letzteintrag = "'.$datum;
	$eintrag .= '" WHERE schl='.$key;
		dbspeichern($eintrag);
	echo '<span class="text2b fa55">Die fr&uuml;heren Eingabewerte sind wunschgem&auml;&szlig nicht mehr abrufbar - sie gehen beim Verlassen dieser Seite endg&uuml;tig verloren!<br></span>';
 }

 if (($speichern) AND (!$verwerfen)) { // WahlOMat: Es wurden Antworten abgeschickt; die Antworten-Werte speichern
	$anzfragen = mysqli_num_rows(mysqli_query($link,'SELECT id FROM fragenwahl'));
	if (!mysqli_num_rows(mysqli_query($link,'SELECT schl FROM antwortenwahl WHERE schl='.$key))) { // unter dieser Nummer gibt es noch keinen Datensatz in der Antwortentabelle
	$eintrag = 'INSERT INTO antwortenwahl SET schl='.$key.', ersteintrag="'.$datum.'"';
	dbspeichern($eintrag);
	}
	speichernmitbem ('f',1,$anzfragen,'antwortenwahl'); // Die Felder fn werden gespeichert
	speichernmitbem ('w',1,$anzfragen,'antwortenwahl'); // Die Felder wn werden gespeichert

	$neust = ipost('neust'); $neufr = ipost('neufr'); $neuth = ipost('neuth');
	if (laenge($neust)+laenge($neufr)+laenge($neuth) > 20) {//Neue Frage abspeichern
		  $eintrag = 'INSERT INTO fragenwahl SET Stichwort="'.umlaute($neust).'", Status="'.umlaute($neufr).'", Ziel="'.umlaute($neuth).'", von="'.$MNr.'", datum="'.$datum.'"';
		dbspeichern($eintrag);
		// In der Antwortentabelle eine Spalte hinzufügen	
		$i = mysqli_num_rows(mysqli_query($link,'SELECT id FROM fragenwahl')); // die letzte Ziffer
		$eintrag = 'ALTER TABLE antwortenwahl ADD COLUMN f'.$i.' MEDIUMINT, ADD COLUMN w'.$i.' MEDIUMINT';
		$aendern = mysqli_query($link,$eintrag); 
		if (!$aendern) {
			echo '<br>Achtung: Dein Text wurde nicht gespeichert! Wende dich bitte an den <a href="mailto:admin@ms4ms.de">Admin</a> und gib die folgende Zeile an:'; $dbzeigen = 1; }
		if ($dbzeigen) echo '<br>'.$aendern . $eintrag.'<br>';
		}
 } // Ende Speichern WahlOMat


// Das Formular einstellen
 echo '<form action="'.$naechsteseite.'.php?key='.$key.$linkerg.'" method="post" enctype="multipart/form-data">';
 echo '<input type="hidden" name="key" value=""'.$key.'">';
 $row = mysqli_fetch_array(mysqli_query($link,'SELECT * FROM '.$antwortenfile.' where schl = "'.$key.'"'));
 $schwanztext =  'Die Eintr&auml;ge werden gespeichert und k&ouml;nnen sp&auml;ter erneut bearbeitet werden.';

?>