<?php // Sammlung der speziell in den diversen Umfragen gebräuchlichen Funktionen
// Hinweis: Für die spezifischen Notwendigkeiten in den Wahl-Extra-Seiten gibt es noch Funktionen in kopf.php

function textfeld($name,$wert,$spalten,$r,$colspan,$drueber) { // Textfeld-Eingabe
	$s=$spalten/1.5;
	if (laenge($wert) > $s) $r = round(laenge($wert)/$s);
    echo '<td '; if ($colspan) {echo 'align="left" colspan="'.$colspan.'"';} else {echo 'align="center"';}
	echo 'class="nurunten">'; if (laenge($drueber)) echo '<span class="text4n">'.$drueber.'<br></span>';
	if (laenge($wert) > 1024) {echo '<font color="red"><br>Hinweis: Dein gespeicherter Text hat '.laenge($wert).' Zeichen. Das ist mehr, als die meisten Browser im PopUp-Fenster darstellen - du solltest versuchen, ihn etwas k&uuml;rzer zu fassen und unter 1024 Zeichen zu bleiben.<br></font><br>';}
	echo '<textarea class="';
	if ($colspan) {echo 'text3b';} else {echo 'text2n';}
	echo '" name="'.$name.'" cols="'.$spalten.'" rows="'.$r.'">'.$wert.'</textarea>';
	echo '</td>';}

function skalaselect ($name,$f,$size,$a) { //Select-Abfrage Feldname, Vorgabe, Länge Feld, Wert
	echo '<select name="'.$name.'" size="'.$size.'">';
	for ($j=1;$j<=$size;$j++)   {
	echo '<option';
	if ($f == $j) {echo ' selected';}
	echo ' value="'.$j.'">&nbsp;'.$a[$j].'&nbsp;</option>';}
	echo '</select>';}

function skalaeinfachmittext ($name,$f,$size,$u,$a,$breite) {// Eine Skalenabfrage mit Einzeltexten
	echo '<tr>';
	if (laenge($u)) {echo '<td valign="top" align="left"';
	if ($breite) echo ' width="'.$breite.'"';
	echo '><span class="text1b">'.umlaute($u).'</span></td>';}// Frage
    for ($j=1;$j<=$size;$j++)   {
	$q = '<td class="text1n" valign="bottom" style="text-align: center;">';
	$q .= '&nbsp;<input id="'.$name.$j.'" class="radiobutton" type="radio" name="'.$name.'" value="'.$j;
	if ($f == $j) {$q .= '" checked="checked';}
		$q .= '">';
	if (laenge($a[$j]) > 1) $q .= '<label for="'.$name.$j.'"><br>'.$a[$j].'</label>';
	echo '</td>'.$q;} echo '</tr>';}

  function bemerkungspeichern($bem) {
	  global $key,$dbzeigen,$link;
	  //echo "<br>XXX Fehlersuche1: $bem";
	  $bem = umlaute($bem); // $bem = utf8_decode($bem);  /// ********sollte das IOS-Problem lösen
	  //echo "<br>XXX Fehlersuche2: $bem";
	if ($bem) { // Es gibt eine; diese Bemerkung in der DB suchen
		$q = mysqli_query($link,'SELECT id FROM bemerkungenwahl WHERE bem ="'.umlaute($bem).'"');
		if (!$q) multipartmail ('MinD-Wahl: Fehlermeldung',$key.' '.$bem,"mensa@as-tt.de",0,'mensa@as-tt.de');
		if (mysqli_num_rows($q) > 0) { // diese Bemerkung ist schon vorhanden - id übergeben
		  return db_result($q,0,'id');}
		else { // diese Bemerkung gibt es noch nicht - neu eintragen
		if (laenge($bem)>1024) {echo '<font color="red"><br>Hinweis: Dein Text hat '.laenge($bem).' Zeichen. Das ist mehr, als die meisten Browser im PopUp-Fenster darstellen - du solltest versuchen, ihn etwas k&uuml;rzer zu fassen und unter 1024 Zeichen zu bleiben.<br></font><br>';}
		  $eintrag = 'INSERT INTO bemerkungenwahl SET schl='.$key.', bem="'.umlaute($bem).'"';
		  dbspeichern($eintrag);
		  return mysqli_insert_id($link);}
	} else {return "";}
	}

function schluessel ($MNr,$antwortenfile) {// Auslesen bzw. Erzeugung Zufalls-Schlüssel, Einrichtung Datensätze, letzten Zugriff  in DB eintragen
	// Variante ohne Einladungsliste - also alle Ms können zugreifen
	global $datum,$dbzeigen,$adressfile,$link;
	$filter = 'SELECT schl,MNr,letzteintrag FROM '.$adressfile.' WHERE MNr = "'.$MNr.'"';
	$result = mysqli_query($link,$filter); 
	if (!mysqli_num_rows($result)) {//Diese MNr gibt es in der DB noch nicht: Anlegen
		srand((double)microtime()*10000000000);
		$key = rand(1000000000,3999999999);
		$eintrag = 'INSERT INTO '.$adressfile.' SET MNr="'.$MNr.'", schl='.$key.', ersteintrag="'.$datum.'"';
		dbspeichern($eintrag);
		if (laenge($antwortenfile)) {$eintrag = 'INSERT INTO '.$antwortenfile.' SET schl='.$key.', ersteintrag="'.$datum.'"';
		dbspeichern($eintrag);
		}
	} else { // Die MNr ist schon im Adressverzeichnis
		if (substr(db_result($result,0,"letzteintrag"),0,10) <> substr($datum,0,10)) {
		$eintrag = 'UPDATE '.$adressfile.' SET letzteintrag="'.$datum.'" WHERE MNr="'.$MNr.'"';
		dbspeichern($eintrag);
		}}
	$result = mysqli_query($link,$filter); 
	if (mysqli_num_rows($result)) {return db_result(mysqli_query($link,$filter),0,'schl');} else  {return 0;} // Der zu übergebende Key bzw. 0, wenn gelöscht
}

function multipartmail2 ($betreff,$text,$email,$kopie,$reply) { // $text ohne Umlaute-Codierung
	$text = str_replace("\n","<br>",$text);
	$id = strtoupper(md5(uniqid(time())));
	$header = "FROM: Werner Kelnhofer <mensa@as-tt.de>\n";
	if (laenge($reply) > 5) {$header .= "Reply-To: ".$reply."\n";} else {$header .= "Reply-To: mensa@as-tt.de\n";} 
	$header .= "X-Mailer: PHP/" . phpversion(). "\n";          
	$header .= "X-Sender-IP: ".$_SERVER['REMOTE_ADDR']." \n"; 

	$header .= "MIME-Version: 1.0\nContent-Type: multipart/alternative; boundary=$id";
	$body = "--$id\nContent-Type: text/plain; charset=UTF-8\n\n".utf8_encode(strip_tags(str_replace("<br>","\n",$text)));
	$body .= "\n--$id\nContent-Type: text/html; charset=UTF-8\n\n <html>\n".umlaute($text)."\n</html>";
	if ($kopie) mail("mensa@as-tt.de",$betreff,$body,$header);
	return mail($email,$betreff,$body,$header);}


?>
